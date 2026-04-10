<?php

require_once __DIR__ . '/BaseModel.php';

class Expense extends BaseModel {
    protected string $table = 'expenses';

    public function getAll(array $filters = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        // Always scope to selected warehouse session
        if (Auth::warehouseId()) {
            $where .= " AND e.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['category_id'])) {
            $where .= " AND e.category_id = ?"; $params[] = $filters['category_id'];
        }
        if (!empty($filters['account_id'])) {
            $where .= " AND e.account_id = ?"; $params[] = $filters['account_id'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND e.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND e.date <= ?"; $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like    = '%' . $filters['search'] . '%';
            $where  .= " AND (e.expense_no LIKE ? OR e.description LIKE ?)";
            $params  = array_merge($params, [$like, $like]);
        }

        return $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name, a.name as account_name, u.name as created_by_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN accounts a ON a.id = e.account_id
             LEFT JOIN users u ON u.id = e.created_by
             {$where}
             ORDER BY e.date DESC, e.created_at DESC
             LIMIT 500",
            $params
        );
    }

    public function nextExpenseNo(): string {
        $last = $this->db->fetchOne("SELECT expense_no FROM expenses ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int) substr($last['expense_no'], strlen(EXPENSE_PREFIX)) : 0;
        return EXPENSE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int|false {
        $this->db->beginTransaction();
        try {
            $id = $this->db->insert(
                "INSERT INTO expenses (expense_no, category_id, account_id, warehouse_id, amount, date, description, created_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $this->nextExpenseNo(),
                    $data['category_id'] ?: null,
                    $data['account_id'],
                    Auth::warehouseId(),
                    (float) $data['amount'],
                    $data['date'] ?? date('Y-m-d'),
                    $data['description'] ?: null,
                    Auth::id(),
                ]
            );

            // Deduct from account (inside transaction)
            if ($id) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [(float)$data['amount'], $data['account_id']]
                );
            }

            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Expense create failed: " . $e->getMessage());
            return false;
        }
    }

    // BUG FIX: Wrapped in transaction. Previously the account balance reversal and
    // the expense deletion were not atomic — if the DELETE failed after the UPDATE,
    // the account balance would be corrupted (money added back but expense still exists).
    public function delete(int $id): int {
        $exp = $this->find($id);
        if (!$exp) return 0;

        $this->db->beginTransaction();
        try {
            // Reverse the account deduction
            $this->db->execute(
                "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                [(float)$exp['amount'], $exp['account_id']]
            );
            $result = $this->db->execute("DELETE FROM expenses WHERE id = ?", [$id]);
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Expense delete failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getCategories(): array {
        return $this->db->fetchAll("SELECT * FROM expense_categories ORDER BY name ASC");
    }

    public function getSummaryByCategory(string $fromDate, string $toDate): array {
        return $this->db->fetchAll(
            "SELECT ec.name as category, COUNT(*) as count, SUM(e.amount) as total
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             WHERE e.date BETWEEN ? AND ?
             GROUP BY e.category_id
             ORDER BY total DESC",
            [$fromDate, $toDate]
        );
    }
}
