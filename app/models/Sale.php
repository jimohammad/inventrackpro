<?php

require_once __DIR__ . '/BaseModel.php';

class Sale extends BaseModel {
    protected string $table = 'sales';

    // Get all sales with party name
    // BUG FIX: Changed JOIN to LEFT JOIN on parties. If a party is hard-deleted
    // (BaseModel::delete exists), INNER JOIN silently drops all their sales from listings.
    public function getAll(array $filters = []): array {
        $where  = "WHERE s.status != 'cancelled'";
        $params = [];

        // Always scope to selected warehouse session
        if (Auth::warehouseId()) {
            $where .= " AND s.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['party_id'])) {
            $where .= " AND s.party_id = ?"; $params[] = $filters['party_id'];
        }
        if (!empty($filters['status'])) {
            $where .= " AND s.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND s.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND s.date <= ?"; $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like    = '%' . $filters['search'] . '%';
            $where  .= " AND (s.invoice_no LIKE ? OR p.name LIKE ? OR p.phone LIKE ?)";
            $params  = array_merge($params, [$like, $like, $like]);
        }

        return $this->db->fetchAll(
            "SELECT s.*, p.name as party_name, p.phone as party_phone,
                    u.name as created_by_name, w.name as warehouse_name
             FROM sales s
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN warehouses w ON w.id = s.warehouse_id
             {$where}
             ORDER BY s.created_at DESC
             LIMIT 500",
            $params
        );
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
            $totalDisc  = (float) ($data['discount'] ?? 0);
            $tax        = 0;

            // Calculate subtotal from items
            foreach ($data['items'] as $item) {
                $lineDisc  = (float) ($item['discount'] ?? 0);
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

            // Insert each sale item
            foreach ($data['items'] as $item) {
                $lineDisc  = (float) ($item['discount'] ?? 0);
                $lineTotal = ((float)$item['unit_price'] * (int)$item['quantity']) - $lineDisc;

                $saleItemId = $this->db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, discount, total)
                     VALUES (?,?,?,?,?,?)",
                    [
                        $saleId,
                        $item['item_id'],
                        (int) $item['quantity'],
                        (float) $item['unit_price'],
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
                            "SELECT id FROM imei_records WHERE imei = ?", [$imei]
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
            return ['success' => true, 'id' => (int) $saleId, 'invoice_no' => $invoiceNo];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Record a payment against a sale
    public function recordPayment(int $saleId, array $data): void {
        $amount = (float) $data['paid_amount'];
        if ($amount <= 0) return;

        // AUDIT FIX F1: FOR UPDATE prevents duplicate payment numbers
        $last   = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num    = $last ? (int) substr($last['payment_no'], 4) : 0;
        $payNo  = 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

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
        $sale = $this->find($saleId);
        if (!$sale) return 'Sale not found.';

        // Reject overpayment — amount cannot exceed remaining balance
        $currentBalance = (float)$sale['balance'];
        if ($amount > $currentBalance + 0.001) {
            return 'Payment amount (' . number_format($amount, 3) . ') exceeds remaining balance (' . number_format($currentBalance, 3) . ').';
        }

        $this->db->beginTransaction();
        try {
            $newPaid    = (float)$sale['paid_amount'] + $amount;
            $newBalance = (float)$sale['grand_total'] - $newPaid;
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

    // Summary stats
    public function getStats(string $period = 'today'): array {
        $dateClause = match($period) {
            'today'   => "date = CURDATE()",
            'week'    => "date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month'   => "date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND date <= CURDATE()",
            default   => "date = CURDATE()",
        };

        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_invoices,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(balance), 0) as total_balance
             FROM sales
             WHERE {$dateClause} AND status != 'cancelled'"
        ) ?: [];
    }
}
