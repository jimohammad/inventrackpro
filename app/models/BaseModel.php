<?php

require_once __DIR__ . '/../helpers/ListPage.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Base Model
 * All models extend this. Common CRUD operations live here.
 */
abstract class BaseModel {
    protected Database $db;
    protected string $table;       // each model sets this
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Find by ID
    public function find(int $id): array|false {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    // Get all records
    public function all(string $orderBy = 'id', string $direction = 'DESC'): array {
        $orderBy   = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}"
        );
    }

    // Get active records
    public function allActive(string $orderBy = 'id', string $direction = 'DESC'): array {
        $orderBy   = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY {$orderBy} {$direction}"
        );
    }

    // Count all records
    public function count(array $where = []): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $k => $v) {
                $col = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $k);
                if ($col === '') {
                    continue;
                }
                $conditions[] = "{$col} = ?";
                $params[] = $v;
            }
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
        }

        $result = $this->db->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    // Paginate results
    public function paginate(int $page = 1, int $perPage = ROWS_PER_PAGE, array $where = [], string $orderBy = 'id', string $direction = 'DESC'): array {
        $orderBy   = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $perPage   = max(1, $perPage);
        $offset    = ($page - 1) * $perPage;
        $params = [];
        $whereClause = '';

        if (!empty($where)) {
            $conditions = array_map(fn($k) => "{$k} = ?", array_keys($where));
            $whereClause = " WHERE " . implode(' AND ', $conditions);
            $params = array_values($where);
        }

        $total = $this->count($where);
        $data  = $this->db->fetchAll(
            "SELECT * FROM {$this->table}{$whereClause} ORDER BY {$orderBy} {$direction} LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    // Delete by ID
    public function delete(int $id): int {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    // Soft delete (sets is_active = 0)
    public function softDelete(int $id): int {
        return $this->db->execute(
            "UPDATE {$this->table} SET is_active = 0 WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    // Generate a unique invoice/reference number
    protected function generateNumber(string $prefix, string $column): string {
        // Must be called inside an active transaction so FOR UPDATE is effective.
        if (!$this->db->getConnection()->inTransaction()) {
            throw new Exception('generateNumber() must be called inside an active DB transaction.');
        }

        // Defensive: column name is interpolated into SQL
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($column === '') {
            throw new Exception('Invalid column for generateNumber().');
        }

        $lastRow = $this->db->fetchOne(
            "SELECT {$column} FROM {$this->table} ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );

        if ($lastRow && isset($lastRow[$column])) {
            $lastNum = (int) substr($lastRow[$column], strlen($prefix));
            $newNum  = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . str_pad($newNum, 6, '0', STR_PAD_LEFT);
    }
}
