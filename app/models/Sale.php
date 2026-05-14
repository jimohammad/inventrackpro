<?php

require_once __DIR__ . '/BaseModel.php';

class Sale extends BaseModel {
    protected string $table = 'sales';

    /**
     * Shared WHERE + params for sales list / bulk print (same rules as getAll).
     *
     * @return array{0:string,1:array<int,mixed>}
     */
    private function buildSalesListWhere(array $filters): array {
        $voidedOnly    = !empty($filters['voided_only']);
        $includeVoided = !empty($filters['include_voided']);

        if ($voidedOnly) {
            $where = "WHERE s.status = 'cancelled'";
        } elseif ($includeVoided) {
            $where = 'WHERE 1=1';
        } else {
            $where = "WHERE s.status != 'cancelled'";
        }
        $params = [];

        $wid = Auth::warehouseId();
        if ($wid) {
            if ($voidedOnly && Auth::isAdmin()) {
                // voided-only list: company-wide
            } elseif ($includeVoided && Auth::isAdmin()) {
                $where .= " AND (s.status = 'cancelled' OR s.warehouse_id = ?)";
                $params[] = $wid;
            } else {
                $where .= " AND s.warehouse_id = ?";
                $params[] = $wid;
            }
        }

        if (!empty($filters['party_id'])) {
            $where .= " AND s.party_id = ?"; $params[] = $filters['party_id'];
        }
        if (!empty($filters['status']) && !$voidedOnly) {
            $where .= " AND s.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND s.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND s.date <= ?"; $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where .= " AND (s.invoice_no LIKE ? OR s.notes LIKE ? OR p.name LIKE ? OR p.party_code LIKE ?"
                . " OR p.contact_person LIKE ? OR p.phone LIKE ? OR p.phone2 LIKE ? OR p.email LIKE ? OR p.city LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like, $like]);
        }

        return [$where, $params];
    }

    private static function salesListFromJoins(): string {
        return 'FROM sales s
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN warehouses w ON w.id = s.warehouse_id';
    }

    // Get all sales with party name
    // BUG FIX: Changed JOIN to LEFT JOIN on parties. If a party is hard-deleted
    // (BaseModel::delete exists), INNER JOIN silently drops all their sales from listings.
    public function getAll(array $filters = []): array {
        [$where, $params] = $this->buildSalesListWhere($filters);

        return $this->db->fetchAll(
            "SELECT s.*, p.name as party_name, p.phone as party_phone,
                    u.name as created_by_name, w.name as warehouse_name
             " . self::salesListFromJoins() . "
             {$where}
             ORDER BY s.created_at DESC
             LIMIT 500",
            $params
        );
    }

    /**
     * Sale IDs for bulk A5/PDF print, chronological. Fetches at most maxInvoices + 1 to detect truncation.
     *
     * @return array{ids: array<int,int>, truncated: bool}
     */
    public function getIdsForBulkPrint(array $filters, int $maxInvoices = 200): array {
        $maxInvoices = max(1, min(300, $maxInvoices));
        $fetchCap    = $maxInvoices + 1;
        [$where, $params] = $this->buildSalesListWhere($filters);

        $rows = $this->db->fetchAll(
            "SELECT s.id
             " . self::salesListFromJoins() . "
             {$where}
             ORDER BY s.date ASC, s.id ASC
             LIMIT " . (int) $fetchCap,
            $params
        );

        $truncated = count($rows) > $maxInvoices;
        if ($truncated) {
            $rows = array_slice($rows, 0, $maxInvoices);
        }

        return [
            'ids'        => array_map('intval', array_column($rows, 'id')),
            'truncated'  => $truncated,
        ];
    }

    // Get single sale with all details
    // BUG FIX: Changed JOIN to LEFT JOIN on parties (same reason as getAll).
    public function findFull(int $id): array|false {
        $sale = $this->db->fetchOne(
            "SELECT s.*, p.name as party_name, p.phone as party_phone, p.address as party_address,
                    w.name as warehouse_name, u.name as created_by_name
             FROM sales s
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN warehouses w ON w.id = s.warehouse_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) return false;

        // Get items with IMEI list
        $sale['items'] = $this->db->fetchAll(
            "SELECT si.*, i.name as item_name, i.sku, i.unit, i.has_imei,
                    GROUP_CONCAT(ir.imei ORDER BY ir.imei SEPARATOR '||') as imei_list
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             LEFT JOIN sale_item_imei sii ON sii.sale_item_id = si.id
             LEFT JOIN imei_records ir ON ir.id = sii.imei_id
             WHERE si.sale_id = ?
             GROUP BY si.id
             ORDER BY si.id ASC",
            [$id]
        );

        // Get payments
        $sale['payments'] = $this->db->fetchAll(
            "SELECT py.*, a.name as account_name
             FROM payments py
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.ref_type = 'sale' AND py.ref_id = ?
             ORDER BY py.date ASC",
            [$id]
        );

        return $sale;
    }

    // Fetch for invoice printing (IMEI list per line; no payments join)
    public function findForPrint(int $id): array|false {
        $sale = $this->db->fetchOne(
            "SELECT s.*, p.name as party_name, p.phone as party_phone, p.address as party_address,
                    w.name as warehouse_name
             FROM sales s
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN warehouses w ON w.id = s.warehouse_id
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) return false;

        $sale['items'] = $this->db->fetchAll(
            "SELECT si.*, i.name as item_name, i.sku, i.unit, i.has_imei,
                    GROUP_CONCAT(ir.imei ORDER BY ir.imei SEPARATOR '||') as imei_list
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             LEFT JOIN sale_item_imei sii ON sii.sale_item_id = si.id
             LEFT JOIN imei_records ir ON ir.id = sii.imei_id
             WHERE si.sale_id = ?
             GROUP BY si.id
             ORDER BY si.id ASC",
            [$id]
        );

        return $sale;
    }

    /**
     * Recalculate sales.balance and sales.status from grand_total, paid_amount,
     * and all approved sale_return rows. Use after creating/editing returns so
     * status never stays "paid" while balance > 0 (see SaleReturn::create legacy CASE ELSE status).
     */
    public function recomputeBalanceAfterReturns(int $saleId): void {
        if ($saleId <= 0) {
            return;
        }
        $saleData = $this->db->fetchOne(
            "SELECT grand_total, paid_amount FROM sales WHERE id = ? AND status != 'cancelled'",
            [$saleId]
        );
        if (!$saleData) {
            return;
        }
        $returnsTot = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total), 0) AS tot FROM `returns` WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'",
            [$saleId]
        )['tot'] ?? 0);
        $newBalance = max(0, round((float) $saleData['grand_total'] - (float) $saleData['paid_amount'] - $returnsTot, 3));
        if ($newBalance < 0.001) {
            $newStatus  = 'paid';
            $newBalance = 0;
        } elseif ((float) $saleData['paid_amount'] > 0.001) {
            $newStatus = 'partial';
        } else {
            $newStatus = 'confirmed';
        }
        $this->db->execute(
            'UPDATE sales SET balance = ?, status = ? WHERE id = ?',
            [$newBalance, $newStatus, $saleId]
        );
    }

    // Get next invoice number — MUST be called inside a transaction
    // AUDIT FIX F1: FOR UPDATE locks the row to prevent duplicate numbers
    public function nextInvoiceNo(): string {
        $last = $this->db->fetchOne("SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $lastNum = $last ? (int) substr($last['invoice_no'], strlen(SALE_PREFIX)) : 0;
        return SALE_PREFIX . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
    }

    // Create sale with items, IMEI, and optional payment
    public function createFull(array $data): array {
        $this->db->beginTransaction();

        try {
            $invoiceNo  = $this->nextInvoiceNo();
            $subtotal   = 0;
            // C3 fix: clamp negative discounts to 0 — a negative header discount would inflate grand_total.
            $totalDisc  = max(0.0, (float) ($data['discount'] ?? 0));
            $tax        = 0;

            // Calculate subtotal from items
            foreach ($data['items'] as $item) {
                // C3 fix: clamp negative line discount to 0 — same inflation risk per line.
                $lineDisc  = max(0.0, (float) ($item['discount'] ?? 0));
                $lineTotal = ((float)$item['unit_price'] * (int)$item['quantity']) - $lineDisc;
                $subtotal += $lineTotal;
            }

            $grandTotal = $subtotal - $totalDisc;
            // Only accept payment if account_id is provided — prevents fake paid_amount
            $paid       = (!empty($data['account_id']) && (float)($data['paid_amount'] ?? 0) > 0)
                          ? (float) $data['paid_amount'] : 0;
            $balance    = $grandTotal - $paid;

            if ($balance < 0.001) {
                $status = 'paid';
                $balance = 0;
            } elseif ($paid > 0) {
                $status = 'partial';
            } else {
                $status = 'confirmed';
            }

            // Insert sale header
            $saleId = $this->db->insert(
                "INSERT INTO sales (invoice_no, party_id, warehouse_id, date, subtotal, discount, tax,
                                    grand_total, paid_amount, balance, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $invoiceNo,
                    $data['party_id'],
                    $data['warehouse_id'],
                    $data['date'] ?? date('Y-m-d'),
                    $subtotal,
                    $totalDisc,
                    $tax,
                    $grandTotal,
                    $paid,
                    $balance,
                    $status,
                    $data['notes'] ?? null,
                    Auth::id(),
                ]
            );

            // Snapshot purchase prices once before the loop (locks COGS at sale time)
            $itemIds    = array_unique(array_column($data['items'], 'item_id'));
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $costMap    = [];
            foreach ($this->db->fetchAll(
                "SELECT id, purchase_price FROM items WHERE id IN ($placeholders)", $itemIds
            ) as $row) {
                $costMap[(int)$row['id']] = (float)$row['purchase_price'];
            }

            // Insert each sale item
            foreach ($data['items'] as $item) {
                // C3 fix: clamp here too so the stored line value matches the subtotal calculation above.
                $lineDisc  = max(0.0, (float) ($item['discount'] ?? 0));
                $lineTotal = ((float)$item['unit_price'] * (int)$item['quantity']) - $lineDisc;
                $costPrice = $costMap[(int)$item['item_id']] ?? 0;

                $saleItemId = $this->db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, cost_price, discount, total)
                     VALUES (?,?,?,?,?,?,?)",
                    [
                        $saleId,
                        $item['item_id'],
                        (int) $item['quantity'],
                        (float) $item['unit_price'],
                        $costPrice,
                        $lineDisc,
                        $lineTotal,
                    ]
                );

                // AUDIT FIX F2: Atomic stock check + deduct in one query
                // Prevents two users from both passing stock check simultaneously
                $affected = $this->db->execute(
                    "UPDATE stock SET quantity = quantity - ?
                     WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [(int)$item['quantity'], $item['item_id'], $data['warehouse_id'], (int)$item['quantity']]
                );
                if ($affected === 0) {
                    $stock = $this->db->fetchOne(
                        "SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?",
                        [$item['item_id'], $data['warehouse_id']]
                    );
                    throw new Exception("Insufficient stock for item ID {$item['item_id']}. Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$item['quantity']}.");
                }

                // Link IMEIs
                if (!empty($item['imeis'])) {
                    foreach ($item['imeis'] as $imei) {
                        $imei = trim($imei);
                        if (!$imei) continue;

                        // Get or create IMEI record
                        $imeiRow = $this->db->fetchOne(
                            "SELECT id
                             FROM imei_records
                             WHERE imei = ?
                             ORDER BY
                                CASE
                                    WHEN warehouse_id = ? AND status IN ('in_stock','returned') THEN 0
                                    WHEN status IN ('in_stock','returned') THEN 1
                                    WHEN warehouse_id = ? THEN 2
                                    ELSE 3
                                END,
                                id DESC
                             LIMIT 1",
                            [$imei, $data['warehouse_id'], $data['warehouse_id']]
                        );

                        if (!$imeiRow) {
                            $imeiId = $this->db->insert(
                                "INSERT INTO imei_records (imei, item_id, warehouse_id, status, sale_id)
                                 VALUES (?,?,?,'sold',?)",
                                [$imei, $item['item_id'], $data['warehouse_id'], $saleId]
                            );
                        } else {
                            $imeiId = $imeiRow['id'];
                            $affected = $this->db->execute(
                                "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                                [$saleId, $data['warehouse_id'], $imeiId]
                            );
                            if ($affected === 0) {
                                throw new Exception("IMEI {$imei} is not available for sale (already sold or scrapped).");
                            }
                        }

                        $this->db->insert(
                            "INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)",
                            [$saleItemId, $imeiId]
                        );
                    }
                }
            }

            // Record payment if any
            if ($paid > 0) {
                $this->recordPayment($saleId, $data);
            }

            $this->db->commit();
            return ['success' => true, 'id' => (int) $saleId, 'invoice_no' => $invoiceNo, 'grand_total' => $grandTotal];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Record a payment receipt against a sale (always payment_type='in').
     *
     * M2 note: hardcodes account_balance += amount. Do NOT reuse for refunds, payouts,
     * or any payment_type='out' direction — money would move the wrong way. If you need
     * an outbound flow, use Payment::createStandalone instead.
     */
    public function recordPayment(int $saleId, array $data): void {
        $amount = (float) $data['paid_amount'];
        if ($amount <= 0) return;
        // Guard against accidental misuse: if a caller passed an explicit type other than 'in',
        // refuse rather than silently update the account in the wrong direction.
        if (isset($data['payment_type']) && $data['payment_type'] !== 'in') {
            throw new Exception('Sale::recordPayment only handles inbound (sale receipt) payments.');
        }

        // C2 fix: use MAX(numeric) generator and retry on duplicate-key collision so a concurrent
        // standalone payment can't crash the sale insert.
        require_once __DIR__ . '/Payment.php';
        $paymentModel = new Payment();
        $payNo = $paymentModel->nextPaymentNo();
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, notes, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $payNo, 'sale', $saleId,
                        $data['party_id'],
                        'in',
                        $data['account_id'] ?? 1,
                        $amount,
                        $data['payment_method'] ?? 'cash',
                        $data['date'] ?? date('Y-m-d'),
                        $data['payment_notes'] ?? null,
                        $data['warehouse_id'] ?? Auth::warehouseId(),
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
                $payNo = $paymentModel->nextPaymentNo();
            }
        }

        // Update account balance
        $this->db->execute(
            "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
            [$amount, $data['account_id'] ?? 1]
        );
    }

    // Add payment to existing sale
    // BUG FIX: Wrapped in transaction. Previously the sale UPDATE and recordPayment
    // (which inserts a payment + updates account balance) were not atomic. If
    // recordPayment failed, the sale would show updated paid_amount/balance but
    // no corresponding payment record or account update would exist.
    public function addPayment(int $saleId, float $amount, int $accountId, string $method, string $date, string $notes = ''): bool|string {
        if (!$this->find($saleId)) return 'Sale not found.';
        if ($amount <= 0) return 'Payment amount must be greater than zero.';

        $this->db->beginTransaction();
        try {
            // H5 fix: lock the sale row inside the transaction. Two simultaneous addPayment requests
            // would otherwise both pass the overpayment check on stale balance and double-pay.
            $sale = $this->db->fetchOne("SELECT * FROM sales WHERE id = ? FOR UPDATE", [$saleId]);
            if (!$sale) {
                $this->db->rollback();
                return 'Sale not found.';
            }
            if (($sale['status'] ?? '') === 'cancelled') {
                $this->db->rollback();
                return 'Cannot add payment to a cancelled invoice.';
            }
            $currentBalance = (float)$sale['balance'];
            if ($amount > $currentBalance + 0.001) {
                $this->db->rollback();
                return 'Payment amount (' . number_format($amount, 3) . ') exceeds remaining balance (' . number_format($currentBalance, 3) . ').';
            }

            $newPaid    = (float)$sale['paid_amount'] + $amount;
            
            $returnsTot = (float)($this->db->fetchOne("SELECT SUM(grand_total) as tot FROM `returns` WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'", [$saleId])['tot'] ?? 0);
            $newBalance = (float)$sale['grand_total'] - $newPaid - $returnsTot;
            
            $newStatus  = $newBalance < 0.001 ? 'paid' : 'partial';

            $this->db->execute(
                "UPDATE sales SET paid_amount=?, balance=?, status=? WHERE id=?",
                [$newPaid, max(0, $newBalance), $newStatus, $saleId]
            );

            $this->recordPayment($saleId, [
                'paid_amount'    => $amount,
                'party_id'       => $sale['party_id'],
                'account_id'     => $accountId,
                'payment_method' => $method,
                'date'           => $date,
                'payment_notes'  => $notes,
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return 'Payment failed: ' . $e->getMessage();
        }
    }

    // Cancel a sale (reverse stock and IMEI status)
    public function cancel(int $id): bool {
        $sale = $this->findFull($id);
        if (!$sale || $sale['status'] === 'cancelled') return false;

        // Check for approved returns against this sale — prevent double stock restoration
        $existingReturns = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM returns WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'",
            [$id]
        );
        if ($existingReturns && (int)$existingReturns['cnt'] > 0) {
            return false; // Cannot cancel a sale that has approved returns
        }

        $this->db->beginTransaction();
        try {
            // Restore stock
            foreach ($sale['items'] as $item) {
                $this->db->execute(
                    "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                    [$item['quantity'], $item['item_id'], $sale['warehouse_id']]
                );
            }

            // Reset IMEI status
            $this->db->execute(
                "UPDATE imei_records SET status='in_stock', sale_id=NULL WHERE sale_id=?",
                [$id]
            );

            // Reverse payments from accounts and delete payment records
            foreach ($sale['payments'] as $pay) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$pay['amount'], $pay['account_id']]
                );
            }
            // Delete the payment records so they don't affect party balance
            $this->db->execute(
                "DELETE FROM payments WHERE ref_type = 'sale' AND ref_id = ?",
                [$id]
            );

            $this->db->execute("UPDATE sales SET status='cancelled' WHERE id=?", [$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Reverse Sale::cancel() for voided invoices: deduct stock again, mark IMEIs sold, restore active status.
     * Safe only when inventory still matches what cancel restored. Payment rows were removed on void — this always
     * sets paid_amount = 0 and balance = grand_total (full amount due). Re-record receipts in Payments if needed.
     *
     * @return array{success:bool, error?:string}
     */
    public function reopenCancelled(int $id): array {
        $sale = $this->findFull($id);
        if (!$sale) {
            return ['success' => false, 'error' => 'Sale not found.'];
        }
        if ($sale['status'] !== 'cancelled') {
            return ['success' => false, 'error' => 'Only voided (cancelled) invoices can be reinstated.'];
        }

        $existingReturns = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM `returns` WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'",
            [$id]
        );
        if ($existingReturns && (int) $existingReturns['cnt'] > 0) {
            return ['success' => false, 'error' => 'Cannot reinstate: an approved sale return is linked to this invoice.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT id FROM sales WHERE id = ? FOR UPDATE', [$id]);

            foreach ($sale['items'] as $item) {
                $qty = (int) $item['quantity'];
                if ($qty <= 0) {
                    continue;
                }
                $affected = $this->db->execute(
                    'UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?',
                    [$qty, $item['item_id'], $sale['warehouse_id'], $qty]
                );
                if ($affected === 0) {
                    $name = $item['item_name'] ?? ('#' . $item['item_id']);
                    throw new Exception(
                        "Insufficient stock to reinstate line: {$name}. Available qty is below what this invoice needs."
                    );
                }
            }

            $imeiLinks = $this->db->fetchAll(
                "SELECT ir.id AS imei_id, ir.imei, ir.status, ir.sale_id
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN imei_records ir ON ir.id = sii.imei_id
                 WHERE si.sale_id = ?",
                [$id]
            );
            // Heal stale `sold` rows: e.g. IMEI was sold on another invoice but that line was deleted
            // (sale_item_imei CASCADE removed) while imei_records stayed sold — manual SQL often misses this.
            foreach ($imeiLinks as $im) {
                $imeiId = (int) ($im['imei_id'] ?? 0);
                $st     = (string) ($im['status'] ?? '');
                $rawSid = $im['sale_id'] ?? null;
                $sid    = ($rawSid !== null && $rawSid !== '') ? (int) $rawSid : 0;
                if ($st !== 'sold' || $imeiId <= 0) {
                    continue;
                }
                $shouldHeal = false;
                if ($sid === 0 || $sid === $id) {
                    $shouldHeal = true;
                } else {
                    $stillOnOther = $this->db->fetchOne(
                        "SELECT 1 AS ok FROM sale_item_imei sii
                         INNER JOIN sale_items si ON si.id = sii.sale_item_id
                         INNER JOIN sales s ON s.id = si.sale_id AND s.status <> 'cancelled'
                         WHERE sii.imei_id = ? AND si.sale_id = ?
                         LIMIT 1",
                        [$imeiId, $sid]
                    );
                    if (!$stillOnOther) {
                        $shouldHeal = true;
                    }
                }
                if ($shouldHeal) {
                    $this->db->execute(
                        "UPDATE imei_records SET status = 'in_stock', sale_id = NULL WHERE id = ? AND status = 'sold'",
                        [$imeiId]
                    );
                }
            }
            $imeiLinks = $this->db->fetchAll(
                "SELECT ir.id AS imei_id, ir.imei, ir.status, ir.sale_id
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN imei_records ir ON ir.id = sii.imei_id
                 WHERE si.sale_id = ?",
                [$id]
            );
            foreach ($imeiLinks as $im) {
                $st = (string) ($im['status'] ?? '');
                if ($st !== 'in_stock' && $st !== 'returned') {
                    throw new Exception(
                        'IMEI ' . ($im['imei'] ?? '') . ' is not in stock (status: ' . ($im['status'] ?? '') . '). Reinstate inventory first.'
                    );
                }
                if (!empty($im['sale_id']) && (int) $im['sale_id'] !== $id) {
                    throw new Exception('IMEI ' . ($im['imei'] ?? '') . ' is tied to another sale; cannot reinstate this invoice.');
                }
                $aff = $this->db->execute(
                    "UPDATE imei_records SET status = 'sold', sale_id = ?, warehouse_id = ?
                     WHERE id = ? AND status IN ('in_stock','returned')",
                    [$id, $sale['warehouse_id'], $im['imei_id']]
                );
                if ($aff === 0) {
                    throw new Exception('Could not mark IMEI ' . ($im['imei'] ?? '') . ' as sold (race or status changed).');
                }
            }

            // Void removed payment rows; row paid_amount may be stale — always reinstate as fully outstanding.
            $grand     = (float) $sale['grand_total'];
            $newPaid   = 0.0;
            $newBal    = $grand;
            $newStatus = $newBal < 0.001 ? 'paid' : 'confirmed';

            $this->db->execute(
                "UPDATE sales SET paid_amount = ?, balance = ?, status = ? WHERE id = ? AND status = 'cancelled'",
                [$newPaid, $newBal, $newStatus, $id]
            );

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Summary stats (scoped to session warehouse when set — matches getAll)
    public function getStats(string $period = 'today'): array {
        $dateClause = match($period) {
            'today'   => "date = CURDATE()",
            'week'    => "date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month'   => "date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND date <= CURDATE()",
            default   => "date = CURDATE()",
        };

        $params    = [];
        $whClause  = '';
        $warehouse = Auth::warehouseId();
        if ($warehouse) {
            $whClause = ' AND warehouse_id = ?';
            $params[] = $warehouse;
        }

        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_invoices,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(balance), 0) as total_balance
             FROM sales
             WHERE {$dateClause} AND status != 'cancelled'{$whClause}",
            $params
        ) ?: [];
    }
}
