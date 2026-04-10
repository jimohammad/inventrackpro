<?php

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel {
    protected string $table = 'payments';

    public function getAll(array $filters = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        // Always scope to selected warehouse via party
        if (Auth::warehouseId()) {
            $where .= " AND pa.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['ref_type'])) {
            $where .= " AND py.ref_type = ?"; $params[] = $filters['ref_type'];
        } else {
            // Hide discounts from payments list — they belong on the Discounts page
            $where .= " AND py.ref_type != 'discount'";
        }
        if (!empty($filters['party_id'])) {
            $where .= " AND py.party_id = ?"; $params[] = $filters['party_id'];
        }
        if (!empty($filters['account_id'])) {
            $where .= " AND py.account_id = ?"; $params[] = $filters['account_id'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND py.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND py.date <= ?"; $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like    = '%' . $filters['search'] . '%';
            $where  .= " AND (py.payment_no LIKE ? OR pa.name LIKE ?)";
            $params  = array_merge($params, [$like, $like]);
        }

        return $this->db->fetchAll(
            "SELECT py.*, pa.name as party_name, a.name as account_name, u.name as created_by_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             LEFT JOIN users u ON u.id = py.created_by
             {$where}
             ORDER BY py.created_at DESC
             LIMIT 500",
            $params
        );
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
        $last = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int) substr($last['payment_no'], 4) : 0;
        return 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    public function createStandalone(array $data): int|false {
        $this->db->beginTransaction();
        try {
            // AUDIT FIX F1: Use shared method with FOR UPDATE
            $payNo = $this->nextPaymentNo();

            $totalAmount = (float) $data['amount'];
            $partyId     = (int)   $data['party_id'];
            $paymentType = $data['payment_type'] ?? 'in';

            $id = $this->db->insert(
                "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, phone_no, payment_type, account_id, amount, payment_method, cheque_no, date, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
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
                    Auth::id(),
                ]
            );

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
            error_log("Payment createStandalone failed: " . $e->getMessage());
            return false;
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
