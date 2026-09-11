<?php

require_once __DIR__ . '/BaseModel.php';

/**
 * Cashier requests to edit a saved sale; admin approves a short unlock window.
 */
class SaleEditRequest extends BaseModel {
    protected string $table = 'sale_edit_requests';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_USED     = 'used';
    public const STATUS_EXPIRED  = 'expired';

    public const UNLOCK_MINUTES = 30;
    public const REASON_MIN     = 5;
    public const REASON_MAX     = 500;

    private static bool $tableReady = false;

    public static function ensureTable(Database $db): void {
        if (self::$tableReady) {
            return;
        }
        if ($db->inTransaction()) {
            try {
                $db->fetchOne('SELECT 1 AS ok FROM sale_edit_requests LIMIT 1');
                self::$tableReady = true;
            } catch (Throwable $e) {
                // Cannot CREATE TABLE mid-transaction (MySQL implicit commit).
            }
            return;
        }
        $db->execute(
            "CREATE TABLE IF NOT EXISTS sale_edit_requests (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id         INT UNSIGNED NOT NULL,
                warehouse_id    INT UNSIGNED NOT NULL,
                requested_by    INT UNSIGNED NOT NULL,
                reason          VARCHAR(500) NOT NULL,
                status          ENUM('pending','approved','rejected','used','expired') NOT NULL DEFAULT 'pending',
                staff_note      VARCHAR(500) NULL,
                reviewed_by     INT UNSIGNED NULL,
                reviewed_at     DATETIME NULL,
                unlocked_until  DATETIME NULL,
                used_at         DATETIME NULL,
                created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_ser_sale_status (sale_id, status),
                KEY idx_ser_wh_status (warehouse_id, status),
                KEY idx_ser_unlock (requested_by, status, unlocked_until)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        self::$tableReady = true;
    }

    public static function expireOverdue(Database $db): void {
        $db->execute(
            "UPDATE sale_edit_requests
             SET status = 'expired'
             WHERE status = 'approved'
               AND used_at IS NULL
               AND unlocked_until IS NOT NULL
               AND unlocked_until < NOW()"
        );
    }

    /** @return array<string,mixed>|null */
    public static function findPendingForSale(Database $db, int $saleId): ?array {
        if ($saleId <= 0) {
            return null;
        }
        $row = $db->fetchOne(
            "SELECT r.*, u.name AS requested_by_name
             FROM sale_edit_requests r
             LEFT JOIN users u ON u.id = r.requested_by
             WHERE r.sale_id = ? AND r.status = 'pending'
             ORDER BY r.id DESC
             LIMIT 1",
            [$saleId]
        );
        return $row ?: null;
    }

    /**
     * Active approved unlock for this cashier on this invoice.
     *
     * @return array<string,mixed>|null
     */
    public static function findActiveUnlock(Database $db, int $saleId, int $userId): ?array {
        if ($saleId <= 0 || $userId <= 0) {
            return null;
        }
        $row = $db->fetchOne(
            "SELECT * FROM sale_edit_requests
             WHERE sale_id = ?
               AND requested_by = ?
               AND status = 'approved'
               AND used_at IS NULL
               AND unlocked_until IS NOT NULL
               AND unlocked_until > NOW()
             ORDER BY id DESC
             LIMIT 1",
            [$saleId, $userId]
        );
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function findLatestForRequester(Database $db, int $saleId, int $userId): ?array {
        if ($saleId <= 0 || $userId <= 0) {
            return null;
        }
        $row = $db->fetchOne(
            "SELECT r.*, ru.name AS reviewed_by_name
             FROM sale_edit_requests r
             LEFT JOIN users ru ON ru.id = r.reviewed_by
             WHERE r.sale_id = ? AND r.requested_by = ?
             ORDER BY r.id DESC
             LIMIT 1",
            [$saleId, $userId]
        );
        return $row ?: null;
    }

    public static function createPending(
        Database $db,
        int $saleId,
        int $warehouseId,
        int $userId,
        string $reason
    ): int {
        return (int) $db->insert(
            "INSERT INTO sale_edit_requests
                (sale_id, warehouse_id, requested_by, reason, status)
             VALUES (?, ?, ?, ?, 'pending')",
            [$saleId, $warehouseId, $userId, $reason]
        );
    }

    public static function approve(Database $db, int $id, int $adminId, string $staffNote = ''): bool {
        $note = $staffNote !== '' ? $staffNote : null;
        $minutes = self::UNLOCK_MINUTES;
        $aff = $db->execute(
            "UPDATE sale_edit_requests
             SET status = 'approved',
                 reviewed_by = ?,
                 reviewed_at = NOW(),
                 staff_note = ?,
                 unlocked_until = DATE_ADD(NOW(), INTERVAL {$minutes} MINUTE),
                 used_at = NULL
             WHERE id = ? AND status = 'pending'",
            [$adminId, $note, $id]
        );
        return $aff > 0;
    }

    public static function reject(Database $db, int $id, int $adminId, string $staffNote = ''): bool {
        $note = $staffNote !== '' ? $staffNote : null;
        $aff = $db->execute(
            "UPDATE sale_edit_requests
             SET status = 'rejected',
                 reviewed_by = ?,
                 reviewed_at = NOW(),
                 staff_note = ?,
                 unlocked_until = NULL
             WHERE id = ? AND status = 'pending'",
            [$adminId, $note, $id]
        );
        return $aff > 0;
    }

    public static function markUsed(Database $db, int $saleId, int $userId): void {
        $db->execute(
            "UPDATE sale_edit_requests
             SET status = 'used', used_at = NOW()
             WHERE sale_id = ?
               AND requested_by = ?
               AND status = 'approved'
               AND used_at IS NULL
               AND unlocked_until IS NOT NULL
               AND unlocked_until > NOW()",
            [$saleId, $userId]
        );
    }

    public static function countPending(Database $db, ?int $warehouseId = null): int {
        if ($warehouseId !== null && $warehouseId > 0) {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM sale_edit_requests WHERE status = 'pending' AND warehouse_id = ?",
                [$warehouseId]
            );
        } else {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM sale_edit_requests WHERE status = 'pending'"
            );
        }
        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array{count: int, latest_id: int}
     */
    public static function pendingPoll(Database $db, ?int $warehouseId = null): array {
        if ($warehouseId !== null && $warehouseId > 0) {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c, COALESCE(MAX(id), 0) AS latest_id
                 FROM sale_edit_requests WHERE status = 'pending' AND warehouse_id = ?",
                [$warehouseId]
            );
        } else {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c, COALESCE(MAX(id), 0) AS latest_id
                 FROM sale_edit_requests WHERE status = 'pending'"
            );
        }
        return [
            'count'     => (int) ($row['c'] ?? 0),
            'latest_id' => (int) ($row['latest_id'] ?? 0),
        ];
    }
}
