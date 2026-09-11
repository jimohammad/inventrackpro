<?php

require_once __DIR__ . '/BaseModel.php';

/**
 * HR employee records (identity, salary, Kuwait documents).
 * Branch-scoped. Does not create login users or money postings.
 */
class Employee extends BaseModel {
    protected string $table = 'employees';

    /** @var list<string> */
    public const NATIONALITIES = ['Pakistan', 'Indian', 'Bangladesh'];

    private static ?bool $schemaReady = null;

    public static function ensureSchema(Database $db): bool {
        if (self::$schemaReady === true) {
            return true;
        }
        if (self::$schemaReady === false) {
            return false;
        }

        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS employees (
                    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    employee_no           VARCHAR(20)  NOT NULL,
                    warehouse_id          INT          NOT NULL,
                    name                  VARCHAR(150) NOT NULL,
                    job_title             VARCHAR(100) NULL,
                    phone                 VARCHAR(40)  NULL,
                    nationality           VARCHAR(80)  NULL,
                    passport_no           VARCHAR(50)  NULL,
                    passport_expires_on   DATE         NULL,
                    kuwait_id_no          VARCHAR(30)  NULL,
                    residence_expires_on  DATE         NULL,
                    salary                DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                    kuwait_id_file        VARCHAR(255) NULL,
                    passport_file         VARCHAR(255) NULL,
                    work_permit_file      VARCHAR(255) NULL,
                    notes                 TEXT         NULL,
                    is_active             TINYINT(1)   NOT NULL DEFAULT 1,
                    created_by            INT          NULL,
                    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at            TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_emp_no_wh (warehouse_id, employee_no),
                    INDEX idx_emp_wh_active (warehouse_id, is_active, name),
                    INDEX idx_emp_residence (warehouse_id, residence_expires_on, is_active),
                    INDEX idx_emp_passport (warehouse_id, passport_expires_on, is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::ensurePassportExpiryColumn($db);
            self::grantDefaultPermissions($db);
            self::$schemaReady = true;
            return true;
        } catch (Throwable $e) {
            error_log('[Employee] ensureSchema failed: ' . $e->getMessage());
            self::$schemaReady = false;
            return false;
        }
    }

    /** Add passport_expires_on when the table was created before that column existed. */
    private static function ensurePassportExpiryColumn(Database $db): void {
        $col = $db->fetchOne(
            "SELECT 1 AS ok FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'employees'
               AND COLUMN_NAME = 'passport_expires_on'
             LIMIT 1"
        );
        if ($col) {
            return;
        }
        $db->execute(
            'ALTER TABLE employees ADD COLUMN passport_expires_on DATE NULL AFTER passport_no'
        );
        try {
            $db->execute(
                'CREATE INDEX idx_emp_passport ON employees (warehouse_id, passport_expires_on, is_active)'
            );
        } catch (Throwable $e) {
            // Index may already exist
        }
    }

    /** One-time: grant the module to existing admin/manager users. */
    private static function grantDefaultPermissions(Database $db): void {
        $any = $db->fetchOne(
            "SELECT 1 AS ok FROM permissions WHERE module = 'employees' LIMIT 1"
        );
        if ($any) {
            return;
        }
        $users = $db->fetchAll(
            "SELECT id, role FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')"
        );
        foreach ($users as $u) {
            $uid    = (int) $u['id'];
            $canDel = ((string) ($u['role'] ?? '')) === 'admin' ? 1 : 0;
            $db->execute(
                'INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete) VALUES (?,?,?,?,?,?)',
                [$uid, 'employees', 1, 1, 1, $canDel]
            );
        }
    }

    public static function nextNo(Database $db, int $warehouseId): string {
        $row = $db->fetchOne(
            "SELECT employee_no FROM employees
             WHERE warehouse_id = ? AND employee_no LIKE 'EMP-%'
             ORDER BY id DESC LIMIT 1",
            [$warehouseId]
        );
        $seq = 1;
        if ($row && preg_match('/EMP-(\d+)$/', (string) ($row['employee_no'] ?? ''), $m)) {
            $seq = (int) $m[1] + 1;
        }
        return 'EMP-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string,mixed>|false */
    public function findInWarehouse(int $id, int $warehouseId): array|false {
        if ($id <= 0 || $warehouseId <= 0) {
            return false;
        }
        return $this->db->fetchOne(
            'SELECT * FROM employees WHERE id = ? AND warehouse_id = ? LIMIT 1',
            [$id, $warehouseId]
        );
    }

    /**
     * @return array{expired:int,due_soon:int}
     */
    public function residenceExpiryCounts(int $warehouseId): array {
        $row = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN residence_expires_on < CURDATE() THEN 1 ELSE 0 END), 0) AS expired,
                COALESCE(SUM(CASE WHEN residence_expires_on >= CURDATE()
                    AND residence_expires_on <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS due_soon
             FROM employees
             WHERE warehouse_id = ?
               AND is_active = 1
               AND residence_expires_on IS NOT NULL",
            [$warehouseId]
        );
        return [
            'expired'  => (int) ($row['expired'] ?? 0),
            'due_soon' => (int) ($row['due_soon'] ?? 0),
        ];
    }
}
