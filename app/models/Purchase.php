<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Payment.php';
require_once __DIR__ . '/IMEI.php';
require_once __DIR__ . '/../helpers/ImeiFormat.php';

class Purchase extends BaseModel {
    protected string $table = 'purchases';

    /**
     * Rows painted on the Purchases list (newest first).
     * Filters still find older invoices; this is a DOM cap, not a data cap.
     */
    public const INDEX_LIST_LIMIT = 25;

    /**
     * @param array{search?:string,status?:string,from_date?:string,to_date?:string} $filters
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public function getIndexList(array $filters, ?int $warehouseId, int $limit = self::INDEX_LIST_LIMIT): array {
        $where  = "WHERE p.status != 'cancelled'";
        $params = [];

        if ($warehouseId) {
            $where .= " AND p.warehouse_id = ?";
            $params[] = $warehouseId;
        }

        if (!empty($filters['search'])) {
            $like   = '%' . $filters['search'] . '%';
            $where .= " AND (p.invoice_no LIKE ? OR par.name LIKE ?)";
            $params = array_merge($params, [$like, $like]);
        }
        if (($filters['item'] ?? '') !== '') {
            $itemQ = $filters['item'];
            if (ctype_digit((string) $itemQ)) {
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_items pi
                    WHERE pi.purchase_id = p.id AND pi.item_id = ?
                )";
                $params[] = (int) $itemQ;
            } else {
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_items pi
                    JOIN items i ON i.id = pi.item_id
                    WHERE pi.purchase_id = p.id AND (i.name LIKE ? OR i.sku LIKE ?)
                )";
                $itemLike = '%' . $itemQ . '%';
                $params[] = $itemLike;
                $params[] = $itemLike;
            }
        }
        if (!empty($filters['status'])) {
            $where .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND p.date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND p.date <= ?";
            $params[] = $filters['to_date'];
        }

        $limit    = max(1, min(ListPage::MAX_ROWS, $limit));
        $fetchCap = $limit + 1;
        $imeiSql  = self::imeiIndexSelectSql('p');

        $rows = $this->db->fetchAll(
            "SELECT p.*, par.name as party_name, w.name as warehouse_name,
                    {$imeiSql}
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             {$where}
             ORDER BY p.created_at DESC
             LIMIT {$fetchCap}",
            $params
        );

        return ListPage::capRows($rows, $limit);
    }

    public function getMonthStats(?int $warehouseId): array|false {
        $wid = (int) ($warehouseId ?? 0);
        return $this->db->fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(grand_total),0) as total,
                    COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(balance),0) as balance
             FROM purchases
             WHERE date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
               AND date <= CURDATE()
               AND status != 'cancelled'
               AND (? = 0 OR warehouse_id = ?)",
            [$wid, $wid]
        );
    }

    /** AUDIT FIX F1: FOR UPDATE prevents duplicate purchase numbers under concurrency (caller should be in transaction when needed). */
    public function nextInvoiceNo(): string {
        $last = $this->db->fetchOne(
            "SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $num  = $last ? (int) substr($last['invoice_no'], strlen(PURCHASE_PREFIX)) : 0;
        return PURCHASE_PREFIX . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Prior import of the same source invoice number (stored as supplier_invoice_no).
     *
     * @return array<string, mixed>|false
     */
    public function findDuplicateImported(string $supplierInvoiceNo, int $warehouseId): array|false {
        $no = trim($supplierInvoiceNo);
        if ($no === '' || $warehouseId <= 0) {
            return false;
        }

        return $this->db->fetchOne(
            "SELECT id, invoice_no, date, grand_total
             FROM purchases
             WHERE warehouse_id = ?
               AND status != 'cancelled'
               AND supplier_invoice_no = ?
             ORDER BY id DESC
             LIMIT 1",
            [$warehouseId, $no]
        );
    }

    public function findBlockingOpenPurchaseOrder(int $partyId, ?int $warehouseId, float $grandTotal): array|false {
        return $this->db->fetchOne(
            "SELECT id, po_no, subtotal_kwd, other_charges_kwd, adjustment_kwd, paid_kwd, status
             FROM purchase_orders
             WHERE party_id = ?
               AND warehouse_id = ?
               AND status IN ('draft','paid')
               AND ABS((subtotal_kwd + COALESCE(other_charges_kwd, 0) + COALESCE(adjustment_kwd, 0)) - ?) < 0.001
             ORDER BY id DESC
             LIMIT 1",
            [$partyId, $warehouseId, $grandTotal]
        );
    }

    public function findHeaderForView(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT p.*, par.name as party_name, par.phone as party_phone,
                    w.name as warehouse_name
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.id = ?",
            [$id]
        );
    }

    public function findFull(int $id): array|false {
        $purchase = $this->findHeaderForView($id);
        if (!$purchase) {
            return false;
        }

        $purchase['items'] = $this->db->fetchAll(
            "SELECT pi.*, i.name as item_name, i.sku, i.has_imei,
                    GROUP_CONCAT(ir.imei ORDER BY ir.imei SEPARATOR '||') as imei_list
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN imei_records ir ON ir.purchase_id = pi.purchase_id AND ir.item_id = pi.item_id
             WHERE pi.purchase_id = ?
             GROUP BY pi.id
             ORDER BY pi.id ASC",
            [$id]
        );

        return $purchase;
    }

    public function getDetailLineItems(int $purchaseId): array {
        return $this->db->fetchAll(
            "SELECT pi.*, i.name as item_name, i.sku,
                    GROUP_CONCAT(ir.imei ORDER BY ir.imei SEPARATOR '||') as imei_list
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN imei_records ir ON ir.purchase_id = pi.purchase_id AND ir.item_id = pi.item_id
             WHERE pi.purchase_id = ?
             GROUP BY pi.id",
            [$purchaseId]
        );
    }

    /**
     * Attach supplier-currency line prices from the source PO (when purchase came from PO convert).
     *
     * @return array{
     *   items: list<array<string,mixed>>,
     *   foreign: ?array{
     *     po_id: int,
     *     po_no: string,
     *     currency: string,
     *     exchange_rate: float,
     *     subtotal_foreign: float,
     *     subtotal_kwd: float
     *   }
     * }
     */
    public function getDetailLineItemsWithForeignContext(int $purchaseId): array {
        $items = $this->getDetailLineItems($purchaseId);

        $po = $this->db->fetchOne(
            "SELECT id, po_no, currency, exchange_rate, subtotal_foreign, subtotal_kwd
             FROM purchase_orders
             WHERE converted_to = ? AND status = 'converted'
             LIMIT 1",
            [$purchaseId]
        );
        if (!$po) {
            return ['items' => $items, 'foreign' => null];
        }

        $currency = strtoupper(trim((string) ($po['currency'] ?? 'KWD')));
        $hasForeign = $currency !== 'KWD' || (float) ($po['subtotal_foreign'] ?? 0) > 0.001;
        if (!$hasForeign) {
            return ['items' => $items, 'foreign' => null];
        }

        $poItems = $this->db->fetchAll(
            "SELECT item_id, quantity, unit_price_foreign, total_foreign
             FROM purchase_order_items WHERE po_id = ?",
            [(int) $po['id']]
        );
        $map = [];
        foreach ($poItems as $row) {
            $map[(int) $row['item_id'] . ':' . (int) $row['quantity']] = $row;
        }

        foreach ($items as &$line) {
            $key = (int) ($line['item_id'] ?? 0) . ':' . (int) ($line['quantity'] ?? 0);
            if (!isset($map[$key])) {
                continue;
            }
            $line['unit_price_foreign'] = (float) ($map[$key]['unit_price_foreign'] ?? 0);
            $line['total_foreign']      = (float) ($map[$key]['total_foreign'] ?? 0);
        }
        unset($line);

        return [
            'items' => $items,
            'foreign' => [
                'po_id'            => (int) $po['id'],
                'po_no'            => (string) ($po['po_no'] ?? ''),
                'currency'         => $currency,
                'exchange_rate'    => (float) ($po['exchange_rate'] ?? 1),
                'subtotal_foreign' => (float) ($po['subtotal_foreign'] ?? 0),
                'subtotal_kwd'     => (float) ($po['subtotal_kwd'] ?? 0),
            ],
        ];
    }

    public function getLinkedPayments(int $purchaseId): array {
        return $this->db->fetchAll(
            "SELECT py.*, a.name as account_name FROM payments py
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.ref_type = 'purchase' AND py.ref_id = ?",
            [$purchaseId]
        );
    }

    /**
     * Self-heal header paid_amount/balance/status when payment rows disagree (e.g. migrated from PO).
     *
     * @param array<string, mixed> $purchase
     */
    /**
     * Recalculate purchases.balance and status from grand_total, paid_amount,
     * and all approved purchase_return rows for this invoice.
     */
    public function recomputeBalanceAfterReturns(int $purchaseId): void {
        if ($purchaseId <= 0) {
            return;
        }
        $purchase = $this->db->fetchOne(
            "SELECT grand_total, paid_amount FROM purchases WHERE id = ? AND status != 'cancelled'",
            [$purchaseId]
        );
        if (!$purchase) {
            return;
        }
        $returnsTot = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total), 0) AS tot FROM `returns`
             WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
            [$purchaseId]
        )['tot'] ?? 0);
        $newBalance = max(0, round((float) $purchase['grand_total'] - (float) $purchase['paid_amount'] - $returnsTot, 3));
        if ($newBalance < 0.001) {
            $newStatus  = 'paid';
            $newBalance = 0;
        } elseif ((float) $purchase['paid_amount'] > 0.001) {
            $newStatus = 'partial';
        } else {
            $newStatus = 'confirmed';
        }
        $this->db->execute(
            'UPDATE purchases SET balance = ?, status = ? WHERE id = ?',
            [$newBalance, $newStatus, $purchaseId]
        );
    }

    public function syncHeaderWithPaymentSum(int $id, array &$purchase): void {
        $payments = $purchase['payments'] ?? [];
        $paidSum  = 0.0;
        foreach ($payments as $py) {
            $paidSum += (float) ($py['amount'] ?? 0);
        }
        $paidSum    = round($paidSum, 3);
        $headerPaid = (float) ($purchase['paid_amount'] ?? 0);
        if (abs($paidSum - $headerPaid) <= 0.001) {
            return;
        }
        $grand       = (float) ($purchase['grand_total'] ?? 0);
        $newBalance  = max(0, $grand - $paidSum);
        $newStatus   = $newBalance < 0.001 ? 'paid' : ($paidSum > 0 ? 'partial' : 'confirmed');
        $this->db->execute(
            "UPDATE purchases SET paid_amount=?, balance=?, status=? WHERE id=?",
            [$paidSum, round($newBalance, 3), $newStatus, $id]
        );
        $purchase['paid_amount'] = $paidSum;
        $purchase['balance']     = round($newBalance, 3);
        $purchase['status']      = $newStatus;
    }

    public function getPrintLineItems(int $purchaseId): array {
        return $this->db->fetchAll(
            "SELECT pi.*, i.name as item_name, i.sku
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.purchase_id = ?
             ORDER BY pi.id ASC",
            [$purchaseId]
        );
    }

    /** SQL condition: item supports IMEI/serial scanning (flagged or buds). */
    public static function imeiScannableCondition(string $itemAlias = 'i', string $categoryAlias = 'c'): string {
        return "({$itemAlias}.has_imei = 1
                 OR LOWER({$itemAlias}.name) LIKE '%bud%'
                 OR LOWER(COALESCE({$categoryAlias}.name, '')) LIKE '%bud%')";
    }

    /** Correlated subqueries for purchase list: scannable unit count and units still needing scan. */
    public static function imeiIndexSelectSql(string $purchaseAlias = 'p'): string {
        $cond = self::imeiScannableCondition('i', 'c');
        return "(SELECT COALESCE(SUM(pi.quantity), 0)
                 FROM purchase_items pi
                 JOIN items i ON i.id = pi.item_id
                 LEFT JOIN categories c ON c.id = i.category_id
                 WHERE pi.purchase_id = {$purchaseAlias}.id AND {$cond}
                ) AS imei_scannable_qty,
                (SELECT COALESCE(SUM(GREATEST(0, pi.quantity - COALESCE(ir.c, 0))), 0)
                 FROM purchase_items pi
                 JOIN items i ON i.id = pi.item_id
                 LEFT JOIN categories c ON c.id = i.category_id
                 LEFT JOIN (
                     SELECT purchase_id, item_id, COUNT(*) AS c
                     FROM imei_records
                     GROUP BY purchase_id, item_id
                 ) ir ON ir.purchase_id = pi.purchase_id AND ir.item_id = pi.item_id
                 WHERE pi.purchase_id = {$purchaseAlias}.id AND {$cond}
                ) AS imei_pending_qty";
    }

    public function countImeiScannableItems(int $purchaseId): int {
        $cond = self::imeiScannableCondition('i', 'c');
        return (int) ($this->db->fetchOne(
            "SELECT COUNT(*) AS c
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE pi.purchase_id = ? AND {$cond}",
            [$purchaseId]
        )['c'] ?? 0);
    }

    public function getImeiScanLines(int $purchaseId): array {
        Item::ensureSerialKindColumn();
        $cond = self::imeiScannableCondition('i', 'c');
        return $this->db->fetchAll(
            "SELECT pi.id as pi_id, pi.item_id, pi.quantity, i.name as item_name,
                    COALESCE(i.serial_kind, 'phone') AS serial_kind,
                    COALESCE(c.name, '') AS category_name,
                    (SELECT COUNT(*) FROM imei_records WHERE purchase_id = pi.purchase_id AND item_id = pi.item_id) as scanned
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE pi.purchase_id = ? AND {$cond}
             ORDER BY pi.id ASC",
            [$purchaseId]
        );
    }

    /**
     * Create purchase, lines, stock, optional IMEIs, optional outbound payment — single transaction.
     *
     * @param array<int, array{item_id:int, quantity:int, unit_price:float, imeis: array<int, string>}> $items
     * @return int Purchase id
     * @throws Exception on failure (transaction rolled back)
     */
    public function createFullPurchase(
        string $invoiceNo,
        ?string $supplierInvoiceNo,
        int $partyId,
        int $warehouseId,
        string $date,
        float $subtotal,
        float $discount,
        float $tax,
        float $grandTotal,
        float $paid,
        float $balance,
        string $status,
        string $notes,
        int $userId,
        array $items,
        int $accountId,
        string $paymentMethod
    ): int {
        $this->db->beginTransaction();
        try {
            $purchaseId = (int) $this->db->insert(
                "INSERT INTO purchases (invoice_no, supplier_invoice_no, party_id, warehouse_id, date, subtotal, discount, tax,
                                        grand_total, paid_amount, balance, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $invoiceNo,
                    $supplierInvoiceNo,
                    $partyId,
                    $warehouseId,
                    $date,
                    $subtotal,
                    $discount,
                    $tax,
                    $grandTotal,
                    $paid,
                    max(0, $balance),
                    $status,
                    $notes,
                    $userId,
                ]
            );

            $imeiModel = new IMEI();
            foreach ($items as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $this->db->insert(
                    "INSERT INTO purchase_items (purchase_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$purchaseId, $item['item_id'], $item['quantity'], $item['unit_price'], $lineTotal]
                );

                $stockRow = $this->db->fetchOne(
                    "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [$item['item_id'], $warehouseId]
                );
                if ($stockRow) {
                    $this->db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                        [$item['quantity'], $stockRow['id']]
                    );
                } else {
                    $this->db->insert(
                        "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                        [$item['item_id'], $warehouseId, $item['quantity']]
                    );
                }

                foreach ($item['imeis'] ?? [] as $rawImei) {
                    $imei = ImeiFormat::normalize((string) $rawImei);
                    if ($imei === '') {
                        continue;
                    }
                    $attached = $imeiModel->attachToPurchase(
                        $imei,
                        (int) $item['item_id'],
                        $warehouseId,
                        $purchaseId
                    );
                    if (empty($attached['ok'])) {
                        throw new Exception($attached['msg'] ?? ('Could not attach IMEI ' . $imei));
                    }
                }

                require_once __DIR__ . '/../services/ItemCostService.php';
                ItemCostService::syncAfterPurchaseLine(
                    $this->db,
                    (int) $item['item_id'],
                    $purchaseId,
                    (float) $item['unit_price']
                );
            }

            if ($paid > 0) {
                $paymentModel = new Payment();
                $payNo        = $paymentModel->nextPaymentNo();
                for ($attempt = 1; $attempt <= 3; $attempt++) {
                    try {
                        $this->db->insert(
                            "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                            [
                                $payNo,
                                'purchase',
                                $purchaseId,
                                $partyId,
                                'out',
                                $accountId ?: 1,
                                $paid,
                                $paymentMethod,
                                $date,
                                $warehouseId,
                                $userId,
                            ]
                        );
                        break;
                    } catch (Exception $e) {
                        $isDuplicatePayNo = str_contains($e->getMessage(), 'Duplicate entry')
                            && str_contains($e->getMessage(), 'payment_no');
                        if (!$isDuplicatePayNo || $attempt === 3) {
                            throw $e;
                        }
                        $payNo = $paymentModel->nextPaymentNo();
                    }
                }
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paid, $accountId ?: 1]
                );
            }

            $this->db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Reverse a PO-converted purchase back to its source PO (cancel invoice + reopen PO).
     *
     * @return array{invoice_no: string, reopened_po_nos: string[], po_id: int, po_no: string}
     * @throws Exception
     */
    public function reverseToPoWithReversals(int $id, int $warehouseId): array {
        $linkedPo = $this->db->fetchOne(
            "SELECT id, po_no FROM purchase_orders WHERE converted_to = ? AND status = 'converted'",
            [$id]
        );
        if (!$linkedPo) {
            throw new Exception('NOT_FROM_PO');
        }

        $appliedShipment = $this->db->fetchOne(
            "SELECT s.shipment_no
             FROM shipment_purchases sp
             JOIN shipments s ON s.id = sp.shipment_id
             WHERE sp.purchase_id = ? AND s.status = 'applied'
             LIMIT 1",
            [$id]
        );
        if ($appliedShipment) {
            throw new Exception(
                'Cannot reverse: purchase is linked to applied import shipment '
                . ($appliedShipment['shipment_no'] ?? '')
                . '. Undo the shipment receive first.'
            );
        }

        $result = $this->cancelWithReversals($id, $warehouseId);

        return array_merge($result, [
            'po_id' => (int) ($linkedPo['id'] ?? 0),
            'po_no' => (string) ($linkedPo['po_no'] ?? ''),
        ]);
    }

    /**
     * Cancel purchase: reverse payments, stock, delete IMEIs from this purchase, set status cancelled.
     *
     * @return array{invoice_no: string, reopened_po_nos: string[]}
     * @throws Exception
     */
    public function cancelWithReversals(int $id, int $warehouseId): array {
        $purchase = $this->db->fetchOne(
            "SELECT * FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$id, $warehouseId]
        );
        if (!$purchase) {
            throw new Exception('Purchase not found.');
        }
        if (($purchase['status'] ?? '') === 'cancelled') {
            throw new Exception('ALREADY_CANCELLED');
        }

        $existingReturns = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM returns WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
            [$id]
        );
        if ($existingReturns && (int) ($existingReturns['cnt'] ?? 0) > 0) {
            throw new Exception('Cannot cancel: approved purchase return exists for this purchase.');
        }

        $items = $this->db->fetchAll(
            "SELECT item_id, quantity FROM purchase_items WHERE purchase_id = ?",
            [$id]
        );

        $this->db->beginTransaction();
        try {
            $badImei = $this->db->fetchOne(
                "SELECT id, imei, status, sale_id FROM imei_records
                 WHERE purchase_id = ? AND (status != 'in_stock' OR sale_id IS NOT NULL)
                 LIMIT 1 FOR UPDATE",
                [$id]
            );
            if ($badImei) {
                throw new Exception(
                    'Cannot cancel: at least one IMEI from this purchase is already used/sold (IMEI: ' . ($badImei['imei'] ?? '') . ').'
                );
            }

            $payments = $this->db->fetchAll(
                "SELECT id, account_id, amount FROM payments WHERE ref_type = 'purchase' AND ref_id = ? AND status = ? FOR UPDATE",
                [$id, 'active']
            );
            foreach ($payments as $py) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [(float) $py['amount'], (int) $py['account_id']]
                );
            }
            $this->db->execute(
                "UPDATE payments SET status = 'cancelled' WHERE ref_type = 'purchase' AND ref_id = ? AND status = 'active'",
                [$id]
            );

            foreach ($items as $it) {
                $stockRow = $this->db->fetchOne(
                    "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [(int) $it['item_id'], (int) $purchase['warehouse_id']]
                );
                $currentQty = (int) ($stockRow['quantity'] ?? 0);
                if ($currentQty < (int) $it['quantity']) {
                    throw new Exception('Cannot cancel: stock already used/sold for one or more items.');
                }
                $this->db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE id = ?",
                    [(int) $it['quantity'], (int) $stockRow['id']]
                );
            }

            $this->db->execute("DELETE FROM imei_records WHERE purchase_id = ?", [$id]);
            $this->db->execute("UPDATE purchases SET status='cancelled' WHERE id = ?", [$id]);

            require_once __DIR__ . '/../services/PurchaseOrderConverter.php';
            $reopenedPoNos = PurchaseOrderConverter::reopenConvertedPosForPurchase($this->db, $id);

            require_once __DIR__ . '/../services/ItemCostService.php';
            foreach ($items as $it) {
                ItemCostService::syncItemMasterFromLatestPurchase($this->db, (int) $it['item_id']);
            }

            $this->db->commit();
            return [
                'invoice_no'      => (string) ($purchase['invoice_no'] ?? ''),
                'reopened_po_nos' => $reopenedPoNos,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
