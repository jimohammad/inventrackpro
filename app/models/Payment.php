<?php

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel {
    protected string $table = 'payments';
    private string $lastError = '';

    public function getLastError(): string {
        return $this->lastError;
    }

    public function getAll(array $filters = []): array {
        return $this->getIndexPage($filters)['items'];
    }

    /**
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public function getIndexPage(array $filters = [], int $limit = ListPage::MAX_ROWS): array {
        $where  = "WHERE 1=1";
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
        if (!empty($filters['party_id'])) {
            $where .= " AND py.party_id = ?";
            $params[] = $filters['party_id'];
        }
        if (!empty($filters['account_id'])) {
            $where .= " AND py.account_id = ?";
            $params[] = $filters['account_id'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND py.date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND py.date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like   = '%' . $filters['search'] . '%';
            $where .= " AND (py.payment_no LIKE ? OR pa.name LIKE ?)";
            $params = array_merge($params, [$like, $like]);
        }

        $limit    = max(1, min(ListPage::MAX_ROWS, $limit));
        $fetchCap = $limit + 1;

        $rows = $this->db->fetchAll(
            "SELECT py.*, pa.name as party_name, a.name as account_name, u.name as created_by_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             LEFT JOIN users u ON u.id = py.created_by
             {$where}
             ORDER BY py.created_at DESC
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
        $this->db->beginTransaction();
        try {
            // AUDIT FIX F1: Use shared method with FOR UPDATE
            $payNo = $this->nextPaymentNo();

            $totalAmount = (float) $data['amount'];
            $partyId     = (int)   $data['party_id'];
            $paymentType = $data['payment_type'] ?? 'in';

            $id = false;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $id = $this->db->insert(
                        "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, phone_no, payment_type, account_id, amount, payment_method, cheque_no, date, notes, warehouse_id, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        [
                            $payNo,
                            $data['ref_type'] ?? 'sale',
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

            // Update account balance: IN adds, OUT subtracts
            if ($paymentType === 'in') {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [$totalAmount, $data['account_id']]
                );
            } else {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$totalAmount, $data['account_id']]
                );
            }

            // FIFO auto-allocation for SALE payments — apply to oldest unpaid sales first
            if ($paymentType === 'in' && $partyId > 0) {
                $remaining = $totalAmount;
                $unpaidInvoices = $this->db->fetchAll(
                    "SELECT id, balance FROM sales
                     WHERE party_id = ? AND balance > 0.001 AND status NOT IN ('cancelled','paid')
                     ORDER BY date ASC, id ASC",
                    [$partyId]
                );
                foreach ($unpaidInvoices as $inv) {
                    if ($remaining <= 0) break;
                    $invBalance  = (float) $inv['balance'];
                    $applyAmount = min($remaining, $invBalance);
                    $newBalance  = $invBalance - $applyAmount;
                    $newStatus   = $newBalance < 0.001 ? 'paid' : 'partial';
                    $this->db->execute(
                        "UPDATE sales SET paid_amount = paid_amount + ?, balance = ?, status = ? WHERE id = ?",
                        [$applyAmount, round($newBalance, 3), $newStatus, $inv['id']]
                    );
                    $remaining -= $applyAmount;
                }
            }

            // FIFO auto-allocation for PURCHASE payments — apply to oldest unpaid purchases first
            if ($paymentType === 'out' && $partyId > 0) {
                $remaining = $totalAmount;
                $unpaidPurchases = $this->db->fetchAll(
                    "SELECT id, balance FROM purchases
                     WHERE party_id = ? AND balance > 0.001 AND status NOT IN ('cancelled','paid')
                     ORDER BY date ASC, id ASC",
                    [$partyId]
                );
                foreach ($unpaidPurchases as $inv) {
                    if ($remaining <= 0) break;
                    $invBalance  = (float) $inv['balance'];
                    $applyAmount = min($remaining, $invBalance);
                    $newBalance  = $invBalance - $applyAmount;
                    $newStatus   = $newBalance < 0.001 ? 'paid' : 'partial';
                    $this->db->execute(
                        "UPDATE purchases SET paid_amount = paid_amount + ?, balance = ?, status = ? WHERE id = ?",
                        [$applyAmount, round($newBalance, 3), $newStatus, $inv['id']]
                    );
                    $remaining -= $applyAmount;
                }
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

            $amount    = (float) $pay['amount'];
            $type      = $pay['payment_type'] ?? 'in';
            $accId     = (int) $pay['account_id'];
            $partyId   = (int) ($pay['party_id'] ?? 0);
            $whId      = (int) ($pay['warehouse_id'] ?? 0);

            // H1 gate: block delete if any newer payment exists for this party (same in/out direction).
            // FIFO allocation made when this payment was saved is no longer the last applied, so LIFO
            // reversal would unwind the wrong invoices and corrupt their balances.
            if ($partyId > 0) {
                $newer = $this->db->fetchOne(
                    "SELECT id, payment_no, date, created_at
                     FROM payments
                     WHERE party_id = ? AND payment_type = ? AND ref_type != 'discount'
                       AND id != ?
                       AND (created_at > ? OR (created_at = ? AND id > ?))
                     ORDER BY created_at ASC, id ASC
                     LIMIT 1",
                    [$partyId, $type, $id, $pay['created_at'], $pay['created_at'], $id]
                );
                if ($newer) {
                    throw new Exception(
                        'Cannot delete: a newer payment ' . ($newer['payment_no'] ?? '') . ' exists for this party. '
                        . 'Delete the newer payments first, or contact support to rebuild the allocation.'
                    );
                }
            }

            if ($type === 'in') {
                $this->db->execute(
                    'UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?',
                    [$amount, $accId]
                );
            } else {
                $this->db->execute(
                    'UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?',
                    [$amount, $accId]
                );
            }

            if ($partyId > 0 && $whId > 0) {
                if ($type === 'in') {
                    $this->reverseFifoSaleApplications($partyId, $whId, $amount);
                } else {
                    $this->reverseFifoPurchaseApplications($partyId, $whId, $amount);
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

    /** Undo OUT payment FIFO against purchases (newest paid rows first). */
    private function reverseFifoPurchaseApplications(int $partyId, int $warehouseId, float $amount): void {
        $remaining = $amount;
        $rows      = $this->db->fetchAll(
            "SELECT id, paid_amount, balance, grand_total
             FROM purchases
             WHERE party_id = ? AND warehouse_id = ? AND paid_amount > 0.001 AND status != 'cancelled'
             ORDER BY date DESC, id DESC",
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
        if ($remaining > 0.001) {
            throw new Exception('Could not reverse full purchase allocation; aborting delete.');
        }
    }

    /** Undo IN payment FIFO against sales (newest paid rows first). */
    private function reverseFifoSaleApplications(int $partyId, int $warehouseId, float $amount): void {
        $remaining = $amount;
        $rows      = $this->db->fetchAll(
            "SELECT id, paid_amount, balance, grand_total
             FROM sales
             WHERE party_id = ? AND warehouse_id = ? AND paid_amount > 0.001 AND status != 'cancelled'
             ORDER BY date DESC, id DESC",
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
            
            $returnsTot = (float)($this->db->fetchOne("SELECT SUM(grand_total) as tot FROM `returns` WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'", [$row['id']])['tot'] ?? 0);
            $newBal  = max(0, round((float) $row['grand_total'] - $newPaid - $returnsTot, 3));
            
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
        if ($remaining > 0.001) {
            throw new Exception('Could not reverse full sale allocation; aborting delete.');
        }
    }

    // Get payment totals by method for a date range
    public function getSummary(string $fromDate, string $toDate): array {
        return $this->db->fetchAll(
            "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
             FROM payments
             WHERE date BETWEEN ? AND ? AND ref_type != 'discount'
             GROUP BY payment_method",
            [$fromDate, $toDate]
        );
    }
}
