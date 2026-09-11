<?php

require_once __DIR__ . '/BaseModel.php';

class Expense extends BaseModel {
    protected string $table = 'expenses';

    /**
     * Rows painted on the Expenses list (newest first).
     * Filters still find older rows; this is a DOM cap, not a data cap.
     */
    public const INDEX_LIST_LIMIT = 25;

    /**
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public function getIndexPage(array $filters = [], int $limit = self::INDEX_LIST_LIMIT): array {
        $where  = 'WHERE 1=1';
        $params = [];

        if (Auth::warehouseId()) {
            $where .= ' AND e.warehouse_id = ?';
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['category_id'])) {
            $where .= ' AND e.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['account_id'])) {
            $where .= ' AND e.account_id = ?';
            $params[] = $filters['account_id'];
        }
        if (!empty($filters['from_date'])) {
            $where .= ' AND e.date >= ?';
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= ' AND e.date <= ?';
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['search'])) {
            $like   = '%' . $filters['search'] . '%';
            $where .= ' AND (e.expense_no LIKE ? OR e.description LIKE ?)';
            $params = array_merge($params, [$like, $like]);
        }

        $limit    = max(1, min(ListPage::MAX_ROWS, $limit));
        $fetchCap = $limit + 1;

        $rows = $this->db->fetchAll(
            "SELECT e.id, e.expense_no, e.category_id, e.account_id, e.amount, e.date, e.created_at, e.description,
                    ec.name AS category_name, a.name AS account_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN accounts a ON a.id = e.account_id
             {$where}
             ORDER BY e.date DESC, e.created_at DESC, e.id DESC
             LIMIT {$fetchCap}",
            $params
        );

        return ListPage::capRows($rows, $limit);
    }

    public function getAll(array $filters = []): array {
        return $this->getIndexPage($filters, ListPage::MAX_ROWS)['items'];
    }

    public function nextExpenseNo(): string {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(expense_no, ?) AS UNSIGNED)), 0) AS max_no
             FROM expenses
             WHERE expense_no LIKE ?
             FOR UPDATE",
            [strlen(EXPENSE_PREFIX) + 1, EXPENSE_PREFIX . '%']
        );
        $num = (int)($row['max_no'] ?? 0);
        return EXPENSE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Insert one expense and deduct the account balance.
     * The CALLER must manage the transaction (used for atomic multi-row saves).
     * Exceptions propagate so the caller can roll back the whole batch.
     */
    public function createInTransaction(array $data): int|false {
        $id = $this->db->insert(
            "INSERT INTO expenses (expense_no, category_id, account_id, warehouse_id, amount, date, description, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->nextExpenseNo(),
                $data['category_id'] ?: null,
                $data['account_id'],
                Auth::warehouseId(),
                (float) $data['amount'],
                $data['date'] ?? date('Y-m-d'),
                $data['description'] ?: null,
                Auth::id(),
                $data['created_at'] ?? date('Y-m-d H:i:s'),
            ]
        );

        if ($id) {
            $this->db->execute(
                "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                [(float)$data['amount'], $data['account_id']]
            );
        }

        return $id;
    }

    // BUG FIX: Wrapped in transaction. Previously the account balance reversal and
    // the expense deletion were not atomic — if the DELETE failed after the UPDATE,
    // the account balance would be corrupted (money added back but expense still exists).
    public function delete(int $id): int {
        $this->db->beginTransaction();
        try {
            // Branch-scoped: never delete (and reverse balances for) another warehouse's expense
            $exp = $this->db->fetchOne(
                "SELECT * FROM expenses WHERE id = ? AND warehouse_id = ? FOR UPDATE",
                [$id, Auth::warehouseId()]
            );
            if (!$exp) {
                $this->db->rollback();
                return 0;
            }

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
        return $this->db->fetchAll("SELECT id, name FROM expense_categories ORDER BY name ASC");
    }

    /**
     * This-month + last-month totals in one query (expense page hero cards).
     * @return array{this_month: float, last_month: float}
     */
    public function sumThisAndLastMonth(string $thisFrom, string $thisTo, string $lastFrom, string $lastTo): array {
        $params = [$thisFrom, $thisTo, $lastFrom, $lastTo, $lastFrom, $thisTo];
        $where  = 'WHERE e.date BETWEEN ? AND ?';
        if (Auth::warehouseId()) {
            $where .= ' AND e.warehouse_id = ?';
            $params[] = Auth::warehouseId();
        }
        // Outer range covers last month start → this month end so one index scan feeds both buckets.
        $row = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN e.date BETWEEN ? AND ? THEN e.amount ELSE 0 END), 0) AS this_month,
                COALESCE(SUM(CASE WHEN e.date BETWEEN ? AND ? THEN e.amount ELSE 0 END), 0) AS last_month
             FROM expenses e
             {$where}",
            $params
        );

        return [
            'this_month' => (float) ($row['this_month'] ?? 0),
            'last_month' => (float) ($row['last_month'] ?? 0),
        ];
    }
}
