<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Payment.php';

class Purchase extends BaseModel {
    protected string $table = 'purchases';

    /**
     * @param array{search?:string,status?:string,from_date?:string,to_date?:string} $filters
     */
    /**
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public function getIndexList(array $filters, ?int $warehouseId, int $limit = ListPage::MAX_ROWS): array {
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

        $rows = $this->db->fetchAll(
            "SELECT p.*, par.name as party_name, w.name as warehouse_name
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

    public function findBlockingOpenPurchaseOrder(int $partyId, ?int $warehouseId, float $grandTotal): array|false {
        return $this->db->fetchOne(
            "SELECT id, po_no, subtotal_kwd, paid_kwd, status
             FROM purchase_orders
             WHERE party_id = ?
               AND warehouse_id = ?
               AND status IN ('draft','paid')
               AND ABS(subtotal_kwd - ?) < 0.001
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

    public function getImeiScanLines(int $purchaseId): array {
        return $this->db->fetchAll(
            "SELECT pi.id as pi_id, pi.item_id, pi.quantity, i.name as item_name,
                    (SELECT COUNT(*) FROM imei_records WHERE purchase_id = pi.purchase_id AND item_id = pi.item_id) as scanned
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.purchase_id = ? AND i.has_imei = 1
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

                foreach ($item['imeis'] as $imei) {
                    if (!$imei) {
                        continue;
                    }
                    $exists = $this->db->fetchOne("SELECT id FROM imei_records WHERE imei = ?", [$imei]);
                    if (!$exists) {
                        $this->db->insert(
                            "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status)
                             VALUES (?,?,?,?,'in_stock')",
                            [$imei, $item['item_id'], $warehouseId, $purchaseId]
                        );
                    }
                }
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
     * Cancel purchase: reverse payments, stock, delete IMEIs from this purchase, set status cancelled.
     *
     * @return string Invoice number (for activity log / flash)
     * @throws Exception
     */
    public function cancelWithReversals(int $id, int $warehouseId): string {
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
                "SELECT id, account_id, amount FROM payments WHERE ref_type = 'purchase' AND ref_id = ? FOR UPDATE",
                [$id]
            );
            foreach ($payments as $py) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [(float) $py['amount'], (int) $py['account_id']]
                );
            }
            $this->db->execute("DELETE FROM payments WHERE ref_type = 'purchase' AND ref_id = ?", [$id]);

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

            $this->db->commit();
            return (string) ($purchase['invoice_no'] ?? '');
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
