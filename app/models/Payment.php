<?php

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel {
    protected string $table = 'payments';
    private string $lastError = '';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    /** Import logistics payments — no sale/purchase FIFO allocation. */
    private const SHIPMENT_LEG_REFS = [
        'shipment_freight_hk',
        'shipment_packing_dxb',
        'shipment_freight_dxb',
        'shipment_partner',
        'shipment_cost',
    ];

    private function usesFifoAllocation(array $pay): bool {
        if ($this->isStandaloneOutPayment($pay)) {
            return false;
        }
        $ref = (string) ($pay['ref_type'] ?? '');
        if ($ref === 'purchase_order' || $ref === 'expense') {
            return false;
        }
        return !in_array($ref, self::SHIPMENT_LEG_REFS, true);
    }

    /** Lock the account row then apply a signed delta to the cached current_balance. */
    private function applyAccountCacheDelta(int $accountId, float $delta): void {
        if ($accountId <= 0) {
            return;
        }
        $this->db->fetchOne('SELECT id FROM accounts WHERE id = ? FOR UPDATE', [$accountId]);
        if (abs($delta) < 0.0005) {
            return;
        }
        $this->db->execute(
            'UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?',
            [round($delta, 3), $accountId]
        );
    }

    /** Generic supplier pay (not linked to a purchase invoice) — e.g. old partner profit lump sum. */
    private function isStandaloneOutPayment(array $pay): bool {
        return ($pay['payment_type'] ?? '') === 'out'
            && (string) ($pay['ref_type'] ?? '') === 'purchase'
            && (int) ($pay['ref_id'] ?? 0) <= 0;
    }

    /**
     * Apply an inbound receipt to sale invoices (oldest first). When $prioritySaleId is set,
     * that invoice is paid first — used when Receive Payment is opened from a sale.
     *
     * @return float unallocated remainder (customer advance)
     */
    private function applyInPaymentToSaleInvoices(int $partyId, int $whId, float $amount, int $prioritySaleId = 0): float {
        $remaining = round($amount, 3);
        if ($remaining < 0.001 || $partyId <= 0 || $whId <= 0) {
            return $remaining;
        }

        if ($prioritySaleId > 0) {
            $remaining = $this->applyAmountToOneSale($partyId, $whId, $prioritySaleId, $remaining);
        }
        if ($remaining < 0.001) {
            return 0.0;
        }

        $unpaidInvoices = $this->db->fetchAll(
            "SELECT id, balance FROM sales
             WHERE party_id = ? AND warehouse_id = ?
               AND balance > 0.001 AND status NOT IN ('cancelled','paid')
             ORDER BY date ASC, id ASC
             FOR UPDATE",
            [$partyId, $whId]
        );
        foreach ($unpaidInvoices as $inv) {
            if ($remaining < 0.001) {
                break;
            }
            if ($prioritySaleId > 0 && (int) $inv['id'] === $prioritySaleId) {
                continue;
            }
            $remaining = $this->applyAmountToOneSale($partyId, $whId, (int) $inv['id'], $remaining);
        }

        return max(0.0, round($remaining, 3));
    }

    /**
     * Apply an outbound payment to purchase invoices (oldest first). When $priorityPurchaseId is set,
     * that invoice is paid first — used when Make Payment is opened from a purchase.
     *
     * @return float unallocated remainder
     */
    private function applyOutPaymentToPurchaseInvoices(int $partyId, int $whId, float $amount, int $priorityPurchaseId = 0): float {
        $remaining = round($amount, 3);
        if ($remaining < 0.001 || $partyId <= 0 || $whId <= 0) {
            return $remaining;
        }

        if ($priorityPurchaseId > 0) {
            $remaining = $this->applyAmountToOnePurchase($partyId, $whId, $priorityPurchaseId, $remaining);
        }
        if ($remaining < 0.001) {
            return 0.0;
        }

        $unpaidPurchases = $this->db->fetchAll(
            "SELECT id, balance FROM purchases
             WHERE party_id = ? AND warehouse_id = ?
               AND balance > 0.001 AND status NOT IN ('cancelled','paid')
             ORDER BY date ASC, id ASC
             FOR UPDATE",
            [$partyId, $whId]
        );
        foreach ($unpaidPurchases as $inv) {
            if ($remaining < 0.001) {
                break;
            }
            if ($priorityPurchaseId > 0 && (int) $inv['id'] === $priorityPurchaseId) {
                continue;
            }
            $remaining = $this->applyAmountToOnePurchase($partyId, $whId, (int) $inv['id'], $remaining);
        }

        return max(0.0, round($remaining, 3));
    }

    private function applyAmountToOneSale(int $partyId, int $whId, int $saleId, float $remaining): float {
        if ($remaining < 0.001 || $saleId <= 0) {
            return $remaining;
        }
        $inv = $this->db->fetchOne(
            "SELECT id, balance FROM sales
             WHERE id = ? AND party_id = ? AND warehouse_id = ?
               AND balance > 0.001 AND status NOT IN ('cancelled','paid')
             FOR UPDATE",
            [$saleId, $partyId, $whId]
        );
        if (!$inv) {
            return $remaining;
        }
        $invBalance  = (float) $inv['balance'];
        $applyAmount = min($remaining, $invBalance);
        $newBalance  = $invBalance - $applyAmount;
        $newStatus   = $newBalance < 0.001 ? 'paid' : 'partial';
        $this->db->execute(
            "UPDATE sales SET paid_amount = paid_amount + ?, balance = ?, status = ? WHERE id = ?",
            [$applyAmount, round($newBalance, 3), $newStatus, $saleId]
        );

        return round($remaining - $applyAmount, 3);
    }

    private function applyAmountToOnePurchase(int $partyId, int $whId, int $purchaseId, float $remaining): float {
        if ($remaining < 0.001 || $purchaseId <= 0) {
            return $remaining;
        }
        $inv = $this->db->fetchOne(
            "SELECT id, balance FROM purchases
             WHERE id = ? AND party_id = ? AND warehouse_id = ?
               AND balance > 0.001 AND status NOT IN ('cancelled','paid')
             FOR UPDATE",
            [$purchaseId, $partyId, $whId]
        );
        if (!$inv) {
            return $remaining;
        }
        $invBalance  = (float) $inv['balance'];
        $applyAmount = min($remaining, $invBalance);
        $newBalance  = $invBalance - $applyAmount;
        $newStatus   = $newBalance < 0.001 ? 'paid' : 'partial';
        $this->db->execute(
            "UPDATE purchases SET paid_amount = paid_amount + ?, balance = ?, status = ? WHERE id = ?",
            [$applyAmount, round($newBalance, 3), $newStatus, $purchaseId]
        );

        return round($remaining - $applyAmount, 3);
    }

    /** SQL AND-clause fragment: count only active (non-voided) payments in ledger totals. */
    public static function sqlActiveOnly(string $alias = ''): string {
        $col = $alias !== '' ? "{$alias}.status" : 'status';
        return " AND {$col} = '" . self::STATUS_ACTIVE . "'";
    }

    /**
     * List-page date filter: match payment date OR entry date (created_at).
     * Receipts recorded today must appear in the current-month view even when the payment
     * date is backdated to an older invoice.
     *
     * @param list<mixed> $params
     */
    private static function appendListDateFilters(string &$where, array &$params, array $filters): void {
        $from = trim((string) ($filters['from_date'] ?? ''));
        $to   = trim((string) ($filters['to_date'] ?? ''));
        if ($from === '' && $to === '') {
            return;
        }
        if ($from !== '' && $to !== '') {
            $where .= " AND (
                (py.date >= ? AND py.date <= ?)
                OR (DATE(py.created_at) >= ? AND DATE(py.created_at) <= ?)
            )";
            array_push($params, $from, $to, $from, $to);
            return;
        }
        if ($from !== '') {
            $where .= " AND (py.date >= ? OR DATE(py.created_at) >= ?)";
            array_push($params, $from, $from);
            return;
        }
        $where .= " AND (py.date <= ? OR DATE(py.created_at) <= ?)";
        array_push($params, $to, $to);
    }

    public function getLastError(): string {
        return $this->lastError;
    }

    /**
     * Rows painted on Payment In / Payment Out lists (newest first).
     * Filters still find older rows; this is a DOM cap, not a data cap.
     */
    public const INDEX_LIST_LIMIT = 25;

    /**
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public function getIndexPage(array $filters = [], int $limit = self::INDEX_LIST_LIMIT): array {
        $where  = "WHERE 1=1" . self::sqlActiveOnly('py');
        $params = [];

        if (Auth::warehouseId()) {
            $where .= " AND py.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['ref_type'])) {
            $where .= " AND py.ref_type = ?";
            $params[] = $filters['ref_type'];
        } else {
            $where .= " AND py.ref_type != 'discount'";
        }
        $paymentType = strtolower(trim((string) ($filters['payment_type'] ?? '')));
        if ($paymentType === 'in' || $paymentType === 'out') {
            $where .= " AND py.payment_type = ?";
            $params[] = $paymentType;
        }
        if (!empty($filters['party_id'])) {
            $where .= " AND py.party_id = ?";
            $params[] = $filters['party_id'];
        }
        if (!empty($filters['account_id'])) {
            $where .= " AND py.account_id = ?";
            $params[] = $filters['account_id'];
        }
        if (!empty($filters['created_by'])) {
            $where .= " AND py.created_by = ?";
            $params[] = (int) $filters['created_by'];
        }
        self::appendListDateFilters($where, $params, $filters);
        if (!empty($filters['search'])) {
            $like   = '%' . $filters['search'] . '%';
            $where .= " AND (py.payment_no LIKE ? OR pa.name LIKE ?)";
            $params = array_merge($params, [$like, $like]);
        }

        $limit    = max(1, min(ListPage::MAX_ROWS, $limit));
        $fetchCap = $limit + 1;

        $rows = $this->db->fetchAll(
            "SELECT py.id, py.payment_no, py.date, py.created_at, py.ref_type,
                    py.amount, py.payment_type, pa.name as party_name,
                    a.name as account_name, u.name as created_by_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             LEFT JOIN users u ON u.id = py.created_by
             {$where}
             ORDER BY py.id DESC
             LIMIT {$fetchCap}",
            $params
        );

        return ListPage::capRows($rows, $limit);
    }

    public function findFull(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT py.*, pa.name as party_name, pa.phone as party_phone,
                    a.name as account_name, u.name as created_by_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.id = ?",
            [$id]
        );
    }

    // Create a standalone payment (not attached to invoice)
    // AUDIT FIX F1: FOR UPDATE prevents duplicate payment numbers under concurrency
    public function nextPaymentNo(): string {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(payment_no, 5) AS UNSIGNED)), 0) AS max_no
             FROM payments
             WHERE payment_no LIKE 'PAY-%'
             FOR UPDATE"
        );
        $num = (int)($row['max_no'] ?? 0);
        return 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    public function createStandalone(array $data): int|false {
        $this->lastError = '';

        $paymentType = (string) ($data['payment_type'] ?? 'in');
        if ($paymentType !== 'in' && $paymentType !== 'out') {
            $this->lastError = 'Invalid payment type.';
            return false;
        }
        $refType = (string) ($data['ref_type'] ?? ($paymentType === 'out' ? 'purchase' : 'sale'));
        $allowedRefs = $paymentType === 'out'
            ? array_merge(['purchase'], self::SHIPMENT_LEG_REFS)
            : ['sale'];
        // Block ledger/cash side-channels that belong to other modules.
        if (in_array($refType, ['discount', 'expense', 'purchase_order', ''], true)
            || !in_array($refType, $allowedRefs, true)
        ) {
            $this->lastError = 'Invalid payment reference type for createStandalone.';
            return false;
        }
        $partyId = (int) ($data['party_id'] ?? 0);
        if ($partyId <= 0) {
            $this->lastError = 'Party is required.';
            return false;
        }
        $totalAmount = (float) ($data['amount'] ?? 0);
        if ($totalAmount <= 0) {
            $this->lastError = 'Amount must be greater than zero.';
            return false;
        }

        $this->db->beginTransaction();
        try {
            // AUDIT FIX F1: Use shared method with FOR UPDATE
            $payNo = $this->nextPaymentNo();

            $id = false;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $id = $this->db->insert(
                        "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, phone_no, payment_type, account_id, amount, payment_method, cheque_no, date, notes, warehouse_id, status, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        [
                            $payNo,
                            $refType,
                            $data['ref_id'] ?? 0,
                            $partyId,
                            $data['phone_no'] ?? null,
                            $paymentType,
                            $data['account_id'],
                            $totalAmount,
                            $data['payment_method'] ?? 'cash',
                            $data['cheque_no'] ?? null,
                            $data['date'] ?? date('Y-m-d'),
                            $data['notes'] ?? null,
                            Auth::warehouseId(),
                            self::STATUS_ACTIVE,
                            Auth::id(),
                        ]
                    );
                    break;
                } catch (Exception $e) {
                    $isDuplicatePayNo = str_contains($e->getMessage(), "Duplicate entry")
                        && str_contains($e->getMessage(), "payment_no");
                    if (!$isDuplicatePayNo || $attempt === 3) {
                        throw $e;
                    }
                    // Regenerate and retry in rare race/legacy numbering collisions.
                    $payNo = $this->nextPaymentNo();
                }
            }

            // Update account balance: IN adds, OUT subtracts (row lock avoids lost updates)
            $accId = (int) ($data['account_id'] ?? 0);
            if ($paymentType === 'in') {
                $this->applyAccountCacheDelta($accId, $totalAmount);
            } else {
                $this->applyAccountCacheDelta($accId, -$totalAmount);
            }

            // FIFO auto-allocation for SALE payments — apply to oldest unpaid sales first (branch-scoped)
            $whId = (int) (Auth::warehouseId() ?? 0);
            $allocProbe = [
                'payment_type' => $paymentType,
                'ref_type'     => $refType,
                'ref_id'       => (int) ($data['ref_id'] ?? 0),
            ];
            if ($paymentType === 'in' && $partyId > 0 && $whId > 0 && $this->usesFifoAllocation($allocProbe)) {
                $prioritySaleId = ($refType === 'sale')
                    ? (int) ($data['ref_id'] ?? 0)
                    : 0;
                $this->applyInPaymentToSaleInvoices($partyId, $whId, $totalAmount, $prioritySaleId);
            }

            // FIFO auto-allocation for PURCHASE payments — apply to oldest unpaid purchases first (branch-scoped)
            if ($paymentType === 'out' && $partyId > 0 && $whId > 0 && $this->usesFifoAllocation($allocProbe)) {
                $priorityPurchaseId = ($refType === 'purchase')
                    ? (int) ($data['ref_id'] ?? 0)
                    : 0;
                $this->applyOutPaymentToPurchaseInvoices($partyId, $whId, $totalAmount, $priorityPurchaseId);
            }

            $this->db->commit();
            return $id;

        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log("Payment createStandalone failed: " . $this->lastError);
            return false;
        }
    }

    /**
     * Open POs for a supplier on the session branch (unpaid draft/paid).
     * Used by Make Payment after a party is selected.
     *
     * @return list<array<string, mixed>>
     */
    public function listOpenPurchaseOrdersForParty(int $partyId, int $warehouseId): array {
        if ($partyId <= 0 || $warehouseId <= 0) {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT id, po_no, date, status, currency,
                    subtotal_kwd, COALESCE(other_charges_kwd, 0) AS other_charges_kwd,
                    COALESCE(adjustment_kwd, 0) AS adjustment_kwd, paid_kwd,
                    COALESCE(subtotal_foreign, 0) AS subtotal_foreign
             FROM purchase_orders
             WHERE party_id = ? AND warehouse_id = ?
               AND status IN ('draft', 'paid')
             ORDER BY date ASC, id ASC",
            [$partyId, $warehouseId]
        );

        $out = [];
        foreach ($rows as $row) {
            $total  = $this->poTotalKwd($row);
            $paid   = round((float) ($row['paid_kwd'] ?? 0), 3);
            $unpaid = max(0, round($total - $paid, 3));
            $foreign = round((float) ($row['subtotal_foreign'] ?? 0), 3);
            $currency = (string) ($row['currency'] ?? 'KWD');
            $kwdUnknown = $currency !== 'KWD' && $foreign > 0.001 && $total <= 0.001 && $paid <= 0.001;
            if ($unpaid <= 0.001 && !$kwdUnknown) {
                continue;
            }
            $remainingForeign = $foreign;
            if ($paid > 0.001 && $total > 0.001 && $foreign > 0.001) {
                $remainingForeign = round($foreign * ($unpaid / $total), 3);
            }
            $out[] = [
                'id'               => (int) $row['id'],
                'po_no'            => (string) ($row['po_no'] ?? ''),
                'date'             => (string) ($row['date'] ?? ''),
                'status'           => (string) ($row['status'] ?? 'draft'),
                'currency'         => $currency,
                'subtotal_kwd'     => round((float) ($row['subtotal_kwd'] ?? 0), 3),
                'other_charges_kwd'=> round((float) ($row['other_charges_kwd'] ?? 0), 3),
                'adjustment_kwd'   => round((float) ($row['adjustment_kwd'] ?? 0), 3),
                'subtotal_foreign' => $foreign,
                'remaining_foreign'=> $remainingForeign,
                'total_kwd'        => $total,
                'paid_kwd'         => $paid,
                'unpaid_kwd'       => $unpaid,
                'kwd_unknown'      => $kwdUnknown,
            ];
        }

        return $out;
    }

    /**
     * Post Payment Out advances against open POs (same ledger effect as Mark as Paid).
     * One PAY row per PO. Optional signed adjustment is added to purchase_orders.adjustment_kwd.
     * Client cannot invent ref_type=purchase_order — only this server path writes it.
     *
     * @param array<string, mixed> $header party_id, account_id, date, payment_method, cheque_no, notes
     * @param list<array{po_id:int, amount:float, adjustment:float}> $lines
     * @return array{id:int, payment_no:string, payment_nos:list<string>, po_nos:list<string>, amount:float}|false
     */
    public function createPurchaseOrderAdvances(array $header, array $lines): array|false {
        $this->lastError = '';

        $partyId   = (int) ($header['party_id'] ?? 0);
        $accountId = (int) ($header['account_id'] ?? 0);
        $whId      = (int) (Auth::warehouseId() ?? 0);
        if ($partyId <= 0) {
            $this->lastError = 'Party is required.';
            return false;
        }
        if ($accountId <= 0) {
            $this->lastError = 'Please select an account.';
            return false;
        }
        if ($whId <= 0) {
            $this->lastError = 'No branch selected.';
            return false;
        }
        if ($lines === []) {
            $this->lastError = 'Select at least one purchase order to pay.';
            return false;
        }

        $this->db->beginTransaction();
        try {
            try {
                $acc = $this->db->fetchOne(
                    'SELECT id, COALESCE(is_active, 1) AS is_active FROM accounts WHERE id = ? FOR UPDATE',
                    [$accountId]
                );
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1054) {
                    throw $e;
                }
                $acc = $this->db->fetchOne('SELECT id FROM accounts WHERE id = ? FOR UPDATE', [$accountId]);
                if ($acc) {
                    $acc['is_active'] = 1;
                }
            }
            if (!$acc) {
                throw new Exception('Account not found.');
            }
            if ((int) ($acc['is_active'] ?? 1) !== 1) {
                throw new Exception('That account is inactive.');
            }

            $firstId   = 0;
            $payNos    = [];
            $poNos     = [];
            $bankTotal = 0.0;
            $payDate   = (string) ($header['date'] ?? date('Y-m-d'));
            $method    = (string) ($header['payment_method'] ?? 'cash');
            $chequeNo  = $header['cheque_no'] ?? null;
            $baseNotes = trim((string) ($header['notes'] ?? ''));

            foreach ($lines as $line) {
                $poId = (int) ($line['po_id'] ?? 0);
                $payAmt = round((float) ($line['amount'] ?? 0), 3);
                $adjDelta = round((float) ($line['adjustment'] ?? 0), 3);
                if ($poId <= 0 || $payAmt <= 0.001) {
                    continue;
                }

                $po = $this->db->fetchOne(
                    'SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE',
                    [$poId]
                );
                if (!$po) {
                    throw new Exception('Purchase order not found.');
                }
                if ((int) ($po['party_id'] ?? 0) !== $partyId) {
                    throw new Exception('Purchase order belongs to a different supplier.');
                }
                if ((int) ($po['warehouse_id'] ?? 0) !== $whId) {
                    throw new Exception('Purchase order is not in the current branch.');
                }
                $status = (string) ($po['status'] ?? '');
                if (!in_array($status, ['draft', 'paid'], true)) {
                    throw new Exception(($po['po_no'] ?? 'PO') . ' is not open for payment.');
                }

                $oldAdj     = round((float) ($po['adjustment_kwd'] ?? 0), 3);
                $alreadyPaid = round((float) ($po['paid_kwd'] ?? 0), 3);
                $currency = (string) ($po['currency'] ?? 'KWD');
                $foreignTotal = round((float) ($po['subtotal_foreign'] ?? 0), 3);
                $oldTotal = $this->poTotalKwd($po);
                if ($currency !== 'KWD' && $oldTotal <= 0.001 && $alreadyPaid <= 0.001 && $payAmt > 0.001 && abs($adjDelta) < 0.001) {
                    $adjDelta = $payAmt;
                }
                $newAdj     = round($oldAdj + $adjDelta, 3);
                $po['adjustment_kwd'] = $newAdj;
                $newTotal   = $this->poTotalKwd($po);
                if ($newTotal < -0.0005) {
                    throw new Exception(($po['po_no'] ?? 'PO') . ' total cannot be negative after adjustment.');
                }

                if ($newTotal + 0.0005 < $alreadyPaid) {
                    throw new Exception(
                        ($po['po_no'] ?? 'PO') . ' adjustment would make the total below the amount already paid.'
                    );
                }

                $unpaid = max(0, round($newTotal - $alreadyPaid, 3));
                if ($payAmt > $unpaid && ($payAmt - $unpaid) <= $this->poPaidTolerance($newTotal)) {
                    $payAmt = $unpaid;
                }
                if ($payAmt > $unpaid + 0.0005) {
                    throw new Exception(
                        'Payment exceeds unpaid balance on ' . ($po['po_no'] ?? 'PO')
                        . ' (' . number_format($unpaid, 3) . ' KWD).'
                    );
                }
                if ($payAmt <= 0.001) {
                    throw new Exception('Pay amount must be greater than zero for ' . ($po['po_no'] ?? 'PO') . '.');
                }

                $newPaid = round($alreadyPaid + $payAmt, 3);
                if ($this->isPoFullyPaid($newPaid, $newTotal)) {
                    $newPaid = $newTotal;
                }
                $newStatus = $this->isPoFullyPaid($newPaid, $newTotal) ? 'paid' : 'draft';

                $poNo = (string) ($po['po_no'] ?? ('#' . $poId));
                $notes = $baseNotes === '' ? $poNo : ($baseNotes . ' · ' . $poNo);
                if (abs($adjDelta) > 0.001) {
                    $sign = $adjDelta > 0 ? '+' : '';
                    $notes .= ' · Adj ' . $sign . number_format($adjDelta, 3);
                }

                $exchangeRate = (float) ($po['exchange_rate'] ?? 1);
                $subtotalKwd = round((float) ($po['subtotal_kwd'] ?? 0), 3);
                $otherCharges = round((float) ($po['other_charges_kwd'] ?? 0), 3);
                if ($newStatus === 'paid' && $currency !== 'KWD' && $foreignTotal > 0.001) {
                    $exchangeRate = round($newTotal / $foreignTotal, 6);
                    $notes .= ' · TT 1 ' . $currency . ' = ' . rtrim(rtrim(number_format($exchangeRate, 6, '.', ''), '0'), '.') . ' KWD';
                    [$subtotalKwd, $newAdj] = $this->applySettledKwdToPoLines($poId, $newTotal, $otherCharges);
                }

                $payNo = $this->nextPaymentNo();
                $payId = false;
                for ($attempt = 1; $attempt <= 3; $attempt++) {
                    try {
                        $payId = $this->db->insert(
                            "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, phone_no, payment_type, account_id, amount, payment_method, cheque_no, date, notes, warehouse_id, status, created_by)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                            [
                                $payNo,
                                'purchase_order',
                                $poId,
                                $partyId,
                                null,
                                'out',
                                $accountId,
                                $payAmt,
                                $method,
                                $chequeNo,
                                $payDate,
                                $notes,
                                $whId,
                                self::STATUS_ACTIVE,
                                Auth::id(),
                            ]
                        );
                        break;
                    } catch (Exception $e) {
                        $isDuplicatePayNo = str_contains($e->getMessage(), 'Duplicate entry')
                            && str_contains($e->getMessage(), 'payment_no');
                        if (!$isDuplicatePayNo || $attempt === 3) {
                            throw $e;
                        }
                        $payNo = $this->nextPaymentNo();
                    }
                }

                $this->db->execute(
                    "UPDATE purchase_orders
                     SET subtotal_kwd = ?, adjustment_kwd = ?, paid_kwd = ?, paid_foreign = subtotal_foreign,
                         account_id = ?, status = ?, exchange_rate = ?
                     WHERE id = ?",
                    [$subtotalKwd, $newAdj, $newPaid, $accountId, $newStatus, $exchangeRate, $poId]
                );

                $bankTotal = round($bankTotal + $payAmt, 3);
                $payNos[]  = $payNo;
                $poNos[]   = $poNo;
                if ($firstId <= 0) {
                    $firstId = (int) $payId;
                }
            }

            if ($firstId <= 0 || $bankTotal <= 0.001) {
                throw new Exception('Select at least one purchase order to pay.');
            }

            $this->applyAccountCacheDelta($accountId, -$bankTotal);
            $this->db->commit();

            return [
                'id'          => $firstId,
                'payment_no'  => $payNos[0],
                'payment_nos' => $payNos,
                'po_nos'      => $poNos,
                'amount'      => $bankTotal,
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('Payment createPurchaseOrderAdvances failed: ' . $this->lastError);
            return false;
        }
    }

    /**
     * Spread settled bank KWD across PO lines by foreign weight. Last line takes remainder.
     * Header adj keeps only 3dp leftover so total stays equal to the bank amount.
     *
     * @return array{0:float,1:float} subtotal_kwd, adjustment_kwd
     */
    private function applySettledKwdToPoLines(int $poId, float $targetKwd, float $otherCharges): array {
        $items = $this->db->fetchAll(
            'SELECT id, quantity, unit_price_foreign, total_foreign FROM purchase_order_items WHERE po_id = ? ORDER BY id',
            [$poId]
        );
        if (!$items) {
            return [0.0, round($targetKwd - $otherCharges, 3)];
        }

        $itemTarget = round($targetKwd - $otherCharges, 3);
        if ($itemTarget < 0) {
            $itemTarget = 0.0;
        }

        $weights = [];
        $sumW = 0.0;
        foreach ($items as $it) {
            $qty = (int) ($it['quantity'] ?? 0);
            $w = round((float) ($it['total_foreign'] ?? 0), 3);
            if ($w <= 0.001) {
                $w = round((float) ($it['unit_price_foreign'] ?? 0) * $qty, 3);
            }
            $weights[] = max(0.0, $w);
            $sumW += max(0.0, $w);
        }

        $n = count($items);
        $allocated = 0.0;
        $subtotal = 0.0;
        foreach ($items as $i => $it) {
            $qty = (int) ($it['quantity'] ?? 0);
            if ($qty < 1) {
                $qty = 1;
            }
            if ($i === $n - 1) {
                $lineTotal = round($itemTarget - $allocated, 3);
            } elseif ($sumW > 0.001) {
                $lineTotal = round($itemTarget * ($weights[$i] / $sumW), 3);
            } else {
                $lineTotal = round($itemTarget / $n, 3);
            }
            if ($lineTotal < 0) {
                $lineTotal = 0.0;
            }
            $unit = round($lineTotal / $qty, 3);
            $lineTotal = round($unit * $qty, 3);
            $allocated = round($allocated + $lineTotal, 3);
            $subtotal = round($subtotal + $lineTotal, 3);
            $this->db->execute(
                'UPDATE purchase_order_items SET unit_price_kwd = ?, total_kwd = ? WHERE id = ?',
                [$unit, $lineTotal, (int) $it['id']]
            );
        }

        return [$subtotal, round($targetKwd - $subtotal - $otherCharges, 3)];
    }

    /** PO KWD total: items + other charges + bank/rounding adjustment. */
    private function poTotalKwd(array $po): float {
        return round(
            (float) ($po['subtotal_kwd'] ?? 0)
            + (float) ($po['other_charges_kwd'] ?? 0)
            + (float) ($po['adjustment_kwd'] ?? 0),
            3
        );
    }

    private function poPaidTolerance(float $totalKwd): float {
        return min(0.100, max(0.001, round($totalKwd * 0.00001, 3)));
    }

    private function isPoFullyPaid(float $paidKwd, float $totalKwd): bool {
        if ($paidKwd <= 0) {
            return false;
        }
        return round($totalKwd - $paidKwd, 3) <= $this->poPaidTolerance($totalKwd);
    }

    /**
     * Delete a standalone payment and reverse its effects (account + FIFO invoice allocation).
     * Uses LIFO on purchases/sales to undo createStandalone FIFO. LIFO is only equivalent to FIFO
     * reversal when no NEWER payments exist for the same party — otherwise this would reverse the
     * wrong invoices. H1 fix: refuse the delete when newer payments exist; admin must delete the
     * newer ones first (or rebuild the allocation manually).
     */
    public function deleteWithReversal(int $id): bool {
        $this->lastError = '';
        $this->db->beginTransaction();
        try {
            $pay = $this->db->fetchOne('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$id]);
            if (!$pay) {
                $this->db->rollback();
                $this->lastError = 'Payment not found.';
                return false;
            }
            if (($pay['ref_type'] ?? '') === 'discount') {
                throw new Exception('Discount-linked payments must be removed from the Discounts module.');
            }
            if (($pay['ref_type'] ?? '') === 'purchase_order') {
                throw new Exception(
                    'PO advances cannot be deleted. The bank transfer is posted. '
                    . 'Cancel the PO to leave this amount as unallocated supplier credit (bank is not restored).'
                );
            }

            if (($pay['status'] ?? self::STATUS_ACTIVE) === self::STATUS_CANCELLED) {
                throw new Exception('This payment was voided with its invoice and cannot be deleted individually.');
            }

            $amount    = (float) $pay['amount'];
            $type      = $pay['payment_type'] ?? 'in';
            $accId     = (int) $pay['account_id'];
            $partyId   = (int) ($pay['party_id'] ?? 0);
            $whId      = (int) ($pay['warehouse_id'] ?? 0);

            // Block delete only when a newer FIFO-applicable payment exists on this branch (import legs are independent).
            if ($partyId > 0 && $this->usesFifoAllocation($pay)) {
                $legPh = implode(',', array_fill(0, count(self::SHIPMENT_LEG_REFS), '?'));
                $scopeWh = $whId > 0 ? $whId : 1;
                $params = array_merge(
                    [$partyId, $type, $scopeWh],
                    self::SHIPMENT_LEG_REFS,
                    [$id, $pay['created_at'], $pay['created_at'], $id]
                );
                $newer = $this->db->fetchOne(
                    "SELECT id, payment_no, date, created_at
                     FROM payments
                     WHERE party_id = ? AND payment_type = ? AND ref_type != 'discount'
                       AND COALESCE(warehouse_id, 1) = ?
                       AND ref_type NOT IN ({$legPh})
                       AND status = 'active'
                       AND id != ?
                       AND (created_at > ? OR (created_at = ? AND id > ?))
                     ORDER BY created_at ASC, id ASC
                     LIMIT 1",
                    $params
                );
                if ($newer) {
                    throw new Exception(
                        'Cannot delete: a newer payment ' . ($newer['payment_no'] ?? '') . ' exists for this party. '
                        . 'Delete the newer payments first, or open Party Master → this party → '
                        . 'Admin: rebuild invoice allocation.'
                    );
                }
            }

            if ($type === 'in') {
                $this->applyAccountCacheDelta($accId, -$amount);
            } else {
                $this->applyAccountCacheDelta($accId, $amount);
            }

            if ($partyId > 0) {
                require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
                LandedCostPaymentLinker::reopenPaymentLinks($this->db, $id);
                if ($this->usesFifoAllocation($pay)) {
                    $scopeWh = $whId > 0 ? $whId : (int) (Auth::warehouseId() ?? 0);
                    if ($type === 'in') {
                        $this->reverseFifoSaleApplications($partyId, $amount, $scopeWh);
                    } else {
                        $this->reverseFifoPurchaseApplications($partyId, $amount, $scopeWh);
                    }
                }
            }

            $this->db->execute('DELETE FROM payments WHERE id = ?', [$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('Payment deleteWithReversal failed: ' . $this->lastError);
            return false;
        }
    }

    /** Undo OUT payment FIFO against purchases (newest paid rows first, branch-scoped). */
    private function reverseFifoPurchaseApplications(int $partyId, float $amount, int $warehouseId = 0): void {
        if ($warehouseId <= 0) {
            $warehouseId = (int) (Auth::warehouseId() ?? 0);
        }
        $remaining = $amount;
        $rows      = $this->db->fetchAll(
            "SELECT id, paid_amount, balance, grand_total
             FROM purchases
             WHERE party_id = ? AND warehouse_id = ? AND paid_amount > 0.001 AND status != 'cancelled'
             ORDER BY date DESC, id DESC
             FOR UPDATE",
            [$partyId, $warehouseId]
        );
        foreach ($rows as $row) {
            if ($remaining < 0.001) {
                break;
            }
            $paid = (float) $row['paid_amount'];
            $take = min($remaining, $paid);
            if ($take < 0.001) {
                continue;
            }
            $newPaid = round($paid - $take, 3);
            
            $returnsTot = (float)($this->db->fetchOne("SELECT SUM(grand_total) as tot FROM `returns` WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'", [$row['id']])['tot'] ?? 0);
            $newBal  = max(0, round((float) $row['grand_total'] - $newPaid - $returnsTot, 3));
            
            $status  = 'confirmed';
            if ($newPaid > 0.001) {
                $status = ($newBal < 0.001) ? 'paid' : 'partial';
            }
            $this->db->execute(
                'UPDATE purchases SET paid_amount = ?, balance = ?, status = ? WHERE id = ?',
                [$newPaid, $newBal, $status, $row['id']]
            );
            $remaining -= $take;
        }
        if ($remaining > 0.001 && ($amount - $remaining) > 0.001) {
            throw new Exception('Could not reverse full purchase allocation; aborting delete.');
        }
    }

    /** Undo IN payment FIFO against sales (newest paid rows first, branch-scoped). */
    private function reverseFifoSaleApplications(int $partyId, float $amount, int $warehouseId = 0): void {
        if ($warehouseId <= 0) {
            $warehouseId = (int) (Auth::warehouseId() ?? 0);
        }
        $remaining = $amount;
        $rows      = $this->db->fetchAll(
            "SELECT id, paid_amount, balance, grand_total
             FROM sales
             WHERE party_id = ? AND warehouse_id = ? AND paid_amount > 0.001 AND status != 'cancelled'
             ORDER BY date DESC, id DESC
             FOR UPDATE",
            [$partyId, $warehouseId]
        );
        foreach ($rows as $row) {
            if ($remaining < 0.001) {
                break;
            }
            $paid = (float) $row['paid_amount'];
            $take = min($remaining, $paid);
            if ($take < 0.001) {
                continue;
            }
            $newPaid = round($paid - $take, 3);
            // Invoice AR = grand − paid (sale returns are party credits, not invoice deductions)
            $newBal  = max(0, round((float) $row['grand_total'] - $newPaid, 3));
            
            $status  = 'confirmed';
            if ($newPaid > 0.001) {
                $status = ($newBal < 0.001) ? 'paid' : 'partial';
            }
            $this->db->execute(
                'UPDATE sales SET paid_amount = ?, balance = ?, status = ? WHERE id = ?',
                [$newPaid, $newBal, $status, $row['id']]
            );
            $remaining -= $take;
        }
        if ($remaining > 0.001 && ($amount - $remaining) > 0.001) {
            throw new Exception('Could not reverse full sale allocation; aborting delete.');
        }
    }

    /**
     * Rebuild sale/purchase invoice paid_amount, balance, and status for one party
     * on one branch by replaying active payments FIFO (oldest invoice first).
     *
     * Does NOT change payments rows or account balances — only derived invoice fields.
     * Discounts and import-logistics legs are excluded (same as createStandalone / delete rules).
     * Legacy NULL payment warehouse_id is treated as matching the branch (Party ledger rule).
     *
     * @return array{sales_changed:int,purchases_changed:int,sale_leftover:float,purchase_leftover:float}|false
     */
    public function rebuildFifoAllocationForParty(int $partyId, int $warehouseId): array|false {
        $this->lastError = '';
        if ($partyId <= 0 || $warehouseId <= 0) {
            $this->lastError = 'Invalid party or warehouse.';
            return false;
        }

        $this->db->beginTransaction();
        try {
            $sales = $this->db->fetchAll(
                "SELECT id, invoice_no, grand_total, paid_amount, balance, status
                 FROM sales
                 WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'
                 ORDER BY date ASC, id ASC
                 FOR UPDATE",
                [$partyId, $warehouseId]
            );
            $purchases = $this->db->fetchAll(
                "SELECT id, invoice_no, grand_total, paid_amount, balance, status
                 FROM purchases
                 WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'
                 ORDER BY date ASC, id ASC
                 FOR UPDATE",
                [$partyId, $warehouseId]
            );

            $purchaseReturns = [];
            foreach ($purchases as $p) {
                $purchaseReturns[(int) $p['id']] = (float) ($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total), 0) AS tot FROM `returns`
                     WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
                    [(int) $p['id']]
                )['tot'] ?? 0);
            }

            // Reset invoice allocation to unpaid (returns still reduce purchase due).
            foreach ($sales as $s) {
                $grand = round((float) $s['grand_total'], 3);
                $this->db->execute(
                    "UPDATE sales SET paid_amount = 0, balance = ?, status = 'confirmed' WHERE id = ?",
                    [$grand, (int) $s['id']]
                );
            }
            foreach ($purchases as $p) {
                $pid   = (int) $p['id'];
                $due   = max(0, round((float) $p['grand_total'] - ($purchaseReturns[$pid] ?? 0), 3));
                $status = $due < 0.001 ? 'paid' : 'confirmed';
                $this->db->execute(
                    "UPDATE purchases SET paid_amount = 0, balance = ?, status = ? WHERE id = ?",
                    [$due, $status, $pid]
                );
            }

            $whPay = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';

            $inPayments = $this->db->fetchAll(
                "SELECT id, amount, ref_type, ref_id, payment_type
                 FROM payments
                 WHERE party_id = ? AND payment_type = 'in' AND status = '" . self::STATUS_ACTIVE . "'
                   AND (ref_type IS NULL OR ref_type NOT IN ('discount', 'expense'))
                   {$whPay}
                 ORDER BY date ASC, id ASC",
                [$partyId, $warehouseId]
            );

            $outPayments = $this->db->fetchAll(
                "SELECT id, amount, ref_type, ref_id, payment_type
                 FROM payments
                 WHERE party_id = ? AND payment_type = 'out' AND status = '" . self::STATUS_ACTIVE . "'
                   AND (ref_type IS NULL OR ref_type NOT IN ('discount', 'expense', 'purchase_order'))
                   {$whPay}
                 ORDER BY date ASC, id ASC",
                [$partyId, $warehouseId]
            );

            $saleAlloc = [];
            foreach ($sales as $s) {
                $saleAlloc[(int) $s['id']] = [
                    'grand' => round((float) $s['grand_total'], 3),
                    'paid'  => 0.0,
                ];
            }
            $saleLeftover = 0.0;
            foreach ($inPayments as $pay) {
                if (!$this->usesFifoAllocation($pay)) {
                    continue;
                }
                $rem = round((float) $pay['amount'], 3);
                $prioritySid = ((string) ($pay['ref_type'] ?? '') === 'sale' && (int) ($pay['ref_id'] ?? 0) > 0)
                    ? (int) $pay['ref_id']
                    : 0;
                if ($prioritySid > 0 && isset($saleAlloc[$prioritySid])) {
                    $due = round($saleAlloc[$prioritySid]['grand'] - $saleAlloc[$prioritySid]['paid'], 3);
                    if ($due >= 0.001) {
                        $take = min($rem, $due);
                        $saleAlloc[$prioritySid]['paid'] = round($saleAlloc[$prioritySid]['paid'] + $take, 3);
                        $rem                              = round($rem - $take, 3);
                    }
                }
                foreach ($saleAlloc as $sid => &$row) {
                    if ($rem < 0.001) {
                        break;
                    }
                    if ($prioritySid > 0 && $sid === $prioritySid) {
                        continue;
                    }
                    $due = round($row['grand'] - $row['paid'], 3);
                    if ($due < 0.001) {
                        continue;
                    }
                    $take       = min($rem, $due);
                    $row['paid'] = round($row['paid'] + $take, 3);
                    $rem         = round($rem - $take, 3);
                }
                unset($row);
                $saleLeftover = round($saleLeftover + $rem, 3);
            }

            $purAlloc = [];
            foreach ($purchases as $p) {
                $pid = (int) $p['id'];
                $purAlloc[$pid] = [
                    'grand'   => round((float) $p['grand_total'], 3),
                    'returns' => $purchaseReturns[$pid] ?? 0.0,
                    'paid'    => 0.0,
                ];
            }
            $purchaseLeftover = 0.0;
            foreach ($outPayments as $pay) {
                if (!$this->usesFifoAllocation($pay)) {
                    continue;
                }
                $rem = round((float) $pay['amount'], 3);
                $priorityPid = ((string) ($pay['ref_type'] ?? '') === 'purchase' && (int) ($pay['ref_id'] ?? 0) > 0)
                    ? (int) $pay['ref_id']
                    : 0;
                if ($priorityPid > 0 && isset($purAlloc[$priorityPid])) {
                    $due = max(0, round($purAlloc[$priorityPid]['grand'] - $purAlloc[$priorityPid]['returns'] - $purAlloc[$priorityPid]['paid'], 3));
                    if ($due >= 0.001) {
                        $take = min($rem, $due);
                        $purAlloc[$priorityPid]['paid'] = round($purAlloc[$priorityPid]['paid'] + $take, 3);
                        $rem                              = round($rem - $take, 3);
                    }
                }
                foreach ($purAlloc as $pid => &$row) {
                    if ($rem < 0.001) {
                        break;
                    }
                    if ($priorityPid > 0 && $pid === $priorityPid) {
                        continue;
                    }
                    $due = max(0, round($row['grand'] - $row['returns'] - $row['paid'], 3));
                    if ($due < 0.001) {
                        continue;
                    }
                    $take       = min($rem, $due);
                    $row['paid'] = round($row['paid'] + $take, 3);
                    $rem         = round($rem - $take, 3);
                }
                unset($row);
                $purchaseLeftover = round($purchaseLeftover + $rem, 3);
            }

            $salesChanged = 0;
            foreach ($sales as $s) {
                $sid     = (int) $s['id'];
                $newPaid = $saleAlloc[$sid]['paid'];
                $newBal  = max(0, round($saleAlloc[$sid]['grand'] - $newPaid, 3));
                if ($newBal < 0.001) {
                    $newStatus = 'paid';
                    $newBal    = 0.0;
                } elseif ($newPaid > 0.001) {
                    $newStatus = 'partial';
                } else {
                    $newStatus = 'confirmed';
                }
                $this->db->execute(
                    'UPDATE sales SET paid_amount = ?, balance = ?, status = ? WHERE id = ?',
                    [$newPaid, $newBal, $newStatus, $sid]
                );
                if (
                    abs((float) $s['paid_amount'] - $newPaid) > 0.001
                    || abs((float) $s['balance'] - $newBal) > 0.001
                    || (string) $s['status'] !== $newStatus
                ) {
                    $salesChanged++;
                }
            }

            $purchasesChanged = 0;
            foreach ($purchases as $p) {
                $pid     = (int) $p['id'];
                $newPaid = $purAlloc[$pid]['paid'];
                $newBal  = max(0, round($purAlloc[$pid]['grand'] - $purAlloc[$pid]['returns'] - $newPaid, 3));
                if ($newBal < 0.001) {
                    $newStatus = 'paid';
                    $newBal    = 0.0;
                } elseif ($newPaid > 0.001) {
                    $newStatus = 'partial';
                } else {
                    $newStatus = 'confirmed';
                }
                $this->db->execute(
                    'UPDATE purchases SET paid_amount = ?, balance = ?, status = ? WHERE id = ?',
                    [$newPaid, $newBal, $newStatus, $pid]
                );
                if (
                    abs((float) $p['paid_amount'] - $newPaid) > 0.001
                    || abs((float) $p['balance'] - $newBal) > 0.001
                    || (string) $p['status'] !== $newStatus
                ) {
                    $purchasesChanged++;
                }
            }

            $this->db->commit();
            return [
                'sales_changed'      => $salesChanged,
                'purchases_changed'  => $purchasesChanged,
                'sale_leftover'      => $saleLeftover,
                'purchase_leftover'  => $purchaseLeftover,
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('Payment rebuildFifoAllocationForParty failed: ' . $this->lastError);
            return false;
        }
    }

    /**
     * Receipts vs sum of sales.paid_amount for one party/branch (excludes discounts).
     * Positive gap = money on ledger not fully allocated to invoice badges.
     */
    public function saleAllocationGap(int $partyId, int $warehouseId): float {
        if ($partyId <= 0 || $warehouseId <= 0) {
            return 0.0;
        }
        $receipts = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS tot FROM payments
             WHERE party_id = ? AND payment_type = 'in' AND status = '" . self::STATUS_ACTIVE . "'
               AND (ref_type IS NULL OR ref_type NOT IN ('discount', 'expense'))
               AND (warehouse_id = ? OR warehouse_id IS NULL)",
            [$partyId, $warehouseId]
        )['tot'] ?? 0);
        $invoicePaid = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(paid_amount), 0) AS tot FROM sales
             WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'",
            [$partyId, $warehouseId]
        )['tot'] ?? 0);
        return round($receipts - $invoicePaid, 3);
    }

    // Get payment totals by method for a date range (branch-scoped)
    public function getSummary(string $fromDate, string $toDate, ?string $paymentType = null): array {
        $whId = (int) (Auth::warehouseId() ?? 0);
        $sql = "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                FROM payments
                WHERE date BETWEEN ? AND ? AND ref_type != 'discount'
                  AND COALESCE(warehouse_id, 1) = ?
                  AND status = 'active'";
        $params = [$fromDate, $toDate, $whId > 0 ? $whId : 1];
        if ($paymentType === 'in' || $paymentType === 'out') {
            $sql .= ' AND payment_type = ?';
            $params[] = $paymentType;
        }
        $sql .= ' GROUP BY payment_method';
        return $this->db->fetchAll($sql, $params);
    }
}
