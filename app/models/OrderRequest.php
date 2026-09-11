<?php

require_once __DIR__ . '/BaseModel.php';

/**
 * Public customer order requests awaiting staff prior confirmation.
 */
class OrderRequest extends BaseModel {
    protected string $table = 'order_requests';

    /** @var bool */
    private static bool $tableReady = false;

    public static function ensureTable(Database $db): void {
        if (self::$tableReady) {
            return;
        }
        $db->execute(
            "CREATE TABLE IF NOT EXISTS order_requests (
                id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_no           VARCHAR(32)  NOT NULL,
                public_token         VARCHAR(64)  NOT NULL,
                customer_name        VARCHAR(150) NOT NULL,
                customer_phone       VARCHAR(40)  NOT NULL,
                items_text           TEXT         NOT NULL,
                customer_notes       TEXT         NULL,
                status               ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
                staff_note           TEXT         NULL,
                warehouse_id         INT UNSIGNED NOT NULL DEFAULT 1,
                reviewed_by          INT UNSIGNED NULL,
                reviewed_at          DATETIME     NULL,
                customer_notified_at DATETIME     NULL,
                ip_address           VARCHAR(45)  NULL,
                created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at           DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_order_requests_token (public_token),
                UNIQUE KEY uk_order_requests_no (request_no),
                KEY idx_order_requests_status_created (status, created_at),
                KEY idx_order_requests_wh_status (warehouse_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        self::$tableReady = true;
    }

    public static function nextRequestNo(Database $db): string {
        $prefix = 'ORQ-' . date('Ymd') . '-';
        $row = $db->fetchOne(
            "SELECT request_no FROM order_requests
             WHERE request_no LIKE ?
             ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = 1;
        if ($row && preg_match('/-(\d+)$/', (string) ($row['request_no'] ?? ''), $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function newPublicToken(): string {
        return bin2hex(random_bytes(16));
    }

    /** @return array<string,mixed>|null */
    public static function findByToken(Database $db, string $token): ?array {
        $token = trim($token);
        if ($token === '' || strlen($token) > 64) {
            return null;
        }
        $row = $db->fetchOne(
            'SELECT * FROM order_requests WHERE public_token = ? LIMIT 1',
            [$token]
        );
        return $row ?: null;
    }

    public static function countPending(Database $db, ?int $warehouseId = null): int {
        if ($warehouseId !== null && $warehouseId > 0) {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM order_requests WHERE status = 'pending' AND warehouse_id = ?",
                [$warehouseId]
            );
        } else {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM order_requests WHERE status = 'pending'"
            );
        }
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Badge count + latest pending id in one query (ERP desktop poll).
     *
     * @return array{count: int, latest_id: int}
     */
    public static function pendingPoll(Database $db, ?int $warehouseId = null): array {
        if ($warehouseId !== null && $warehouseId > 0) {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c, COALESCE(MAX(id), 0) AS latest_id
                 FROM order_requests WHERE status = 'pending' AND warehouse_id = ?",
                [$warehouseId]
            );
        } else {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS c, COALESCE(MAX(id), 0) AS latest_id
                 FROM order_requests WHERE status = 'pending'"
            );
        }
        return [
            'count'     => (int) ($row['c'] ?? 0),
            'latest_id' => (int) ($row['latest_id'] ?? 0),
        ];
    }

    /**
     * Latest pending row id (for desktop “new request” detection).
     */
    public static function latestPendingId(Database $db, ?int $warehouseId = null): int {
        if ($warehouseId !== null && $warehouseId > 0) {
            $row = $db->fetchOne(
                "SELECT id FROM order_requests WHERE status = 'pending' AND warehouse_id = ? ORDER BY id DESC LIMIT 1",
                [$warehouseId]
            );
        } else {
            $row = $db->fetchOne(
                "SELECT id FROM order_requests WHERE status = 'pending' ORDER BY id DESC LIMIT 1"
            );
        }
        return (int) ($row['id'] ?? 0);
    }
}
