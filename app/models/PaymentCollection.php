<?php

require_once __DIR__ . '/BaseModel.php';

/**
 * Memory that a cashier/manager Payment In was handed to admin.
 * Does not move party or account balances.
 */
class PaymentCollection extends BaseModel {
    protected string $table = 'payment_collections';

    /** Shop staff whose receipts wait for admin. Admin's own receipts are already with admin. */
    public const HOLDER_ROLES = ['cashier', 'manager'];

    public const SETTING_FROM = 'payment_collection_from';

    private static bool $tableReady = false;

    private static ?string $cutoff = null;

    private string $lastError = '';

    public function getLastError(): string {
        return $this->lastError;
    }

    public static function ensureTable(Database $db): bool {
        if (self::$tableReady) {
            return true;
        }
        if ($db->inTransaction()) {
            try {
                $db->fetchOne('SELECT 1 AS ok FROM payment_collections LIMIT 1');
                self::$tableReady = true;
            } catch (Throwable $e) {
                return false;
            }
            return true;
        }
        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS payment_collections (
                    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    warehouse_id    INT UNSIGNED NOT NULL,
                    holder_user_id  INT UNSIGNED NOT NULL,
                    collected_by    INT UNSIGNED NOT NULL,
                    collected_at    DATETIME NOT NULL,
                    note            VARCHAR(255) NULL,
                    undone_at       DATETIME NULL,
                    undone_by       INT UNSIGNED NULL,
                    KEY idx_pcol_wh_at (warehouse_id, collected_at),
                    KEY idx_pcol_holder (warehouse_id, holder_user_id, undone_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $db->execute(
                "CREATE TABLE IF NOT EXISTS payment_collection_items (
                    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    collection_id   INT UNSIGNED NOT NULL,
                    payment_id      INT UNSIGNED NOT NULL,
                    amount          DECIMAL(15,3) NOT NULL,
                    bucket          ENUM('cash','bank') NOT NULL,
                    KEY idx_pcoli_collection (collection_id),
                    KEY idx_pcoli_payment (payment_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::ensureCutoff($db);
            self::$tableReady = true;
            return true;
        } catch (Throwable $e) {
            error_log('[ERP] payment_collections schema: ' . $e->getMessage());
            return false;
        }
    }

    /** Receipts saved before the first use of this page are already with admin. */
    public static function ensureCutoff(Database $db): void {
        if (self::$cutoff !== null) {
            return;
        }
        if ($db->inTransaction()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $db->execute(
            "INSERT INTO settings (key_name, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = IF(value IS NULL OR value = '', VALUES(value), value)",
            [self::SETTING_FROM, $now]
        );
        $row = $db->fetchOne(
            'SELECT value FROM settings WHERE key_name = ? LIMIT 1',
            [self::SETTING_FROM]
        );
        $stored = trim((string) ($row['value'] ?? ''));
        self::$cutoff = $stored !== '' ? $stored : $now;
    }

    public static function cutoff(Database $db): string {
        if (self::$cutoff !== null) {
            return self::$cutoff;
        }
        self::ensureCutoff($db);
        if (self::$cutoff !== null) {
            return self::$cutoff;
        }
        $row = $db->fetchOne(
            'SELECT value FROM settings WHERE key_name = ? LIMIT 1',
            [self::SETTING_FROM]
        );
        $stored = trim((string) ($row['value'] ?? ''));
        return $stored !== '' ? $stored : date('Y-m-d H:i:s');
    }

    public static function bucket(string $method, string $accountType): string {
        $method = strtolower(trim($method));
        $bankMethods = ['bank_transfer', 'cheque', 'card', 'mobile_wallet'];
        if (in_array($method, $bankMethods, true)) {
            return 'bank';
        }
        $type = strtolower(trim($accountType));
        if (in_array($type, ['bank', 'bank_transfer', 'card', 'mobile_wallet'], true)) {
            return 'bank';
        }
        return 'cash';
    }

    public static function isActivelyCollected(Database $db, int $paymentId): bool {
        if ($paymentId <= 0 || !self::ensureTable($db)) {
            return false;
        }
        $row = $db->fetchOne(
            "SELECT i.id
             FROM payment_collection_items i
             INNER JOIN payment_collections c ON c.id = i.collection_id AND c.undone_at IS NULL
             WHERE i.payment_id = ?
             LIMIT 1",
            [$paymentId]
        );
        return is_array($row);
    }

    /** @param list<int> $paymentIds @return array<int,true> */
    public static function lockedIdSet(Database $db, array $paymentIds): array {
        $paymentIds = array_values(array_unique(array_filter(
            array_map('intval', $paymentIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($paymentIds === [] || !self::ensureTable($db)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($paymentIds), '?'));
        $rows = $db->fetchAll(
            "SELECT i.payment_id
             FROM payment_collection_items i
             INNER JOIN payment_collections c ON c.id = i.collection_id AND c.undone_at IS NULL
             WHERE i.payment_id IN ({$ph})",
            $paymentIds
        );
        $set = [];
        foreach ($rows as $row) {
            $set[(int) $row['payment_id']] = true;
        }
        return $set;
    }

    /** @return array<string,mixed>|null */
    public static function activeForPayment(Database $db, int $paymentId): ?array {
        if ($paymentId <= 0 || !self::ensureTable($db)) {
            return null;
        }
        $row = $db->fetchOne(
            "SELECT c.id, c.collected_at, c.collected_by, c.note,
                    au.name AS admin_name, i.amount AS snapshot_amount, i.bucket
             FROM payment_collection_items i
             INNER JOIN payment_collections c ON c.id = i.collection_id AND c.undone_at IS NULL
             LEFT JOIN users au ON au.id = c.collected_by
             WHERE i.payment_id = ?
             LIMIT 1",
            [$paymentId]
        );
        if (!is_array($row)) {
            return null;
        }
        $row['same_day'] = self::isSameDay((string) ($row['collected_at'] ?? ''));
        return $row;
    }

    /** @return array{cash:float,bank:float} */
    public static function heldTotals(Database $db, int $warehouseId, int $userId): array {
        $empty = ['cash' => 0.0, 'bank' => 0.0];
        if ($warehouseId <= 0 || $userId <= 0 || !self::ensureTable($db)) {
            return $empty;
        }
        $rows = $db->fetchAll(
            self::openSql() . ' AND py.created_by = ?',
            [$warehouseId, self::cutoff($db), $userId]
        );
        return self::sumBuckets($rows);
    }

    public static function openCount(Database $db, int $warehouseId): int {
        if ($warehouseId <= 0 || !self::ensureTable($db)) {
            return 0;
        }
        $row = $db->fetchOne(
            'SELECT COUNT(*) AS n FROM (' . self::openSql() . ') open_rows',
            [$warehouseId, self::cutoff($db)]
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @return list<array{
     *   user_id:int,name:string,role:string,
     *   cash:list<array<string,mixed>>,bank:list<array<string,mixed>>,
     *   cash_total:float,bank_total:float
     * }>
     */
    public static function openByHolder(Database $db, int $warehouseId): array {
        if ($warehouseId <= 0 || !self::ensureTable($db)) {
            return [];
        }
        $rows = $db->fetchAll(
            self::openSql() . ' ORDER BY u.name ASC, py.date ASC, py.id ASC',
            [$warehouseId, self::cutoff($db)]
        );
        $groups = [];
        foreach ($rows as $row) {
            $hid = (int) ($row['created_by'] ?? 0);
            if ($hid <= 0) {
                continue;
            }
            if (!isset($groups[$hid])) {
                $groups[$hid] = [
                    'user_id'    => $hid,
                    'name'       => (string) ($row['holder_name'] ?? ''),
                    'role'       => (string) ($row['holder_role'] ?? ''),
                    'cash'       => [],
                    'bank'       => [],
                    'cash_total' => 0.0,
                    'bank_total' => 0.0,
                ];
            }
            $bucket = self::bucket(
                (string) ($row['payment_method'] ?? ''),
                (string) ($row['account_type'] ?? '')
            );
            $amt = round((float) ($row['amount'] ?? 0), 3);
            $row['bucket'] = $bucket;
            $groups[$hid][$bucket][] = $row;
            $groups[$hid][$bucket . '_total'] = round($groups[$hid][$bucket . '_total'] + $amt, 3);
        }
        return array_values($groups);
    }

    /** @return list<array<string,mixed>> */
    public static function recent(Database $db, int $warehouseId, int $limit = 30): array {
        if ($warehouseId <= 0 || !self::ensureTable($db)) {
            return [];
        }
        $limit = max(1, min(50, $limit));
        $rows = $db->fetchAll(
            "SELECT c.id, c.holder_user_id, c.collected_by, c.collected_at, c.note, c.undone_at,
                    hu.name AS holder_name, au.name AS admin_name,
                    COALESCE(SUM(CASE WHEN i.bucket = 'cash' THEN i.amount ELSE 0 END), 0) AS cash_total,
                    COALESCE(SUM(CASE WHEN i.bucket = 'bank' THEN i.amount ELSE 0 END), 0) AS bank_total,
                    COUNT(i.id) AS receipt_count,
                    SUM(CASE WHEN py.id IS NULL OR py.status <> 'active' THEN 1 ELSE 0 END) AS dropped_count
             FROM payment_collections c
             LEFT JOIN users hu ON hu.id = c.holder_user_id
             LEFT JOIN users au ON au.id = c.collected_by
             LEFT JOIN payment_collection_items i ON i.collection_id = c.id
             LEFT JOIN payments py ON py.id = i.payment_id
             WHERE c.warehouse_id = ?
             GROUP BY c.id, c.holder_user_id, c.collected_by, c.collected_at, c.note, c.undone_at,
                      hu.name, au.name
             ORDER BY c.collected_at DESC, c.id DESC
             LIMIT {$limit}",
            [$warehouseId]
        );
        foreach ($rows as &$row) {
            $row['same_day'] = ($row['undone_at'] ?? null) === null
                && self::isSameDay((string) ($row['collected_at'] ?? ''));
            $row['cash_total'] = round((float) ($row['cash_total'] ?? 0), 3);
            $row['bank_total'] = round((float) ($row['bank_total'] ?? 0), 3);
        }
        unset($row);
        return $rows;
    }

    /** @param list<int> $paymentIds */
    public function collect(int $warehouseId, int $adminId, int $holderId, array $paymentIds, string $note): int|false {
        $this->lastError = '';
        if (!self::ensureTable($this->db)) {
            $this->lastError = 'Could not prepare collection records.';
            return false;
        }
        $paymentIds = array_values(array_unique(array_filter(
            array_map('intval', $paymentIds),
            static fn(int $id): bool => $id > 0
        )));
        sort($paymentIds);
        if ($paymentIds === []) {
            $this->lastError = 'Select at least one receipt.';
            return false;
        }
        if (count($paymentIds) > 200) {
            $this->lastError = 'Select at most 200 receipts at a time.';
            return false;
        }
        if ($warehouseId <= 0 || $adminId <= 0 || $holderId <= 0) {
            $this->lastError = 'Missing branch or user.';
            return false;
        }
        $note = trim($note);
        if (function_exists('mb_substr')) {
            $note = mb_substr($note, 0, 255, 'UTF-8');
        } elseif (strlen($note) > 255) {
            $note = substr($note, 0, 255);
        }
        $cutoff = self::cutoff($this->db);

        $this->db->beginTransaction();
        try {
            $ph = implode(',', array_fill(0, count($paymentIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT py.id, py.amount, py.payment_method, py.payment_type, py.status, py.ref_type,
                        py.warehouse_id, py.created_by, a.type AS account_type, u.role AS holder_role
                 FROM payments py
                 INNER JOIN users u ON u.id = py.created_by
                 LEFT JOIN accounts a ON a.id = py.account_id
                 WHERE py.id IN ({$ph})
                   AND py.warehouse_id = ?
                   AND py.created_at >= ?
                   AND py.created_by = ?
                 FOR UPDATE",
                array_merge($paymentIds, [$warehouseId, $cutoff, $holderId])
            );
            if (count($rows) !== count($paymentIds)) {
                throw new Exception('One of these receipts cannot be collected. Refresh and try again.');
            }
            $byId = [];
            foreach ($rows as $row) {
                $byId[(int) $row['id']] = $row;
            }
            $locked = $this->db->fetchAll(
                "SELECT i.payment_id
                 FROM payment_collection_items i
                 INNER JOIN payment_collections c ON c.id = i.collection_id AND c.undone_at IS NULL
                 WHERE i.payment_id IN ({$ph})
                 FOR UPDATE",
                $paymentIds
            );
            if ($locked !== []) {
                throw new Exception('One of these receipts was already given to admin.');
            }
            foreach ($paymentIds as $pid) {
                $row = $byId[$pid];
                if ((string) ($row['payment_type'] ?? '') !== 'in'
                    || (string) ($row['status'] ?? '') !== Payment::STATUS_ACTIVE
                    || (string) ($row['ref_type'] ?? '') === 'discount'
                    || !in_array((string) ($row['holder_role'] ?? ''), self::HOLDER_ROLES, true)
                ) {
                    throw new Exception('One of these receipts cannot be collected. Refresh and try again.');
                }
            }

            $cid = $this->db->insert(
                "INSERT INTO payment_collections
                    (warehouse_id, holder_user_id, collected_by, collected_at, note)
                 VALUES (?,?,?,?,?)",
                [
                    $warehouseId,
                    $holderId,
                    $adminId,
                    date('Y-m-d H:i:s'),
                    $note !== '' ? $note : null,
                ]
            );
            if ($cid === false) {
                throw new Exception('Could not save the collection.');
            }
            $collectionId = (int) $cid;
            foreach ($paymentIds as $pid) {
                $row = $byId[$pid];
                $bucket = self::bucket(
                    (string) ($row['payment_method'] ?? ''),
                    (string) ($row['account_type'] ?? '')
                );
                $itemId = $this->db->insert(
                    "INSERT INTO payment_collection_items (collection_id, payment_id, amount, bucket)
                     VALUES (?,?,?,?)",
                    [$collectionId, $pid, round((float) $row['amount'], 3), $bucket]
                );
                if ($itemId === false) {
                    throw new Exception('Could not save a receipt on this collection.');
                }
            }
            $this->db->commit();
            return $collectionId;
        } catch (Throwable $e) {
            $this->db->rollbackQuiet();
            $this->lastError = $e->getMessage() !== ''
                ? $e->getMessage()
                : 'Could not save the collection.';
            return false;
        }
    }

    public function undo(int $collectionId, int $adminId, int $warehouseId): bool {
        $this->lastError = '';
        if ($collectionId <= 0 || $adminId <= 0 || $warehouseId <= 0) {
            $this->lastError = 'Invalid collection.';
            return false;
        }
        if (!self::ensureTable($this->db)) {
            $this->lastError = 'Could not prepare collection records.';
            return false;
        }
        $this->db->beginTransaction();
        try {
            $row = $this->db->fetchOne(
                "SELECT id, collected_at, undone_at, warehouse_id
                 FROM payment_collections
                 WHERE id = ? AND warehouse_id = ?
                 FOR UPDATE",
                [$collectionId, $warehouseId]
            );
            if (!is_array($row)) {
                throw new Exception('Collection not found.');
            }
            if (($row['undone_at'] ?? null) !== null && (string) $row['undone_at'] !== '') {
                throw new Exception('This collection was already undone.');
            }
            if (!self::isSameDay((string) ($row['collected_at'] ?? ''))) {
                throw new Exception('Undo is only allowed on the same day.');
            }
            $this->db->execute(
                "UPDATE payment_collections SET undone_at = ?, undone_by = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $adminId, $collectionId]
            );
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollbackQuiet();
            $this->lastError = $e->getMessage() !== ''
                ? $e->getMessage()
                : 'Could not undo the collection.';
            return false;
        }
    }

    private static function isSameDay(string $collectedAt): bool {
        $ts = strtotime($collectedAt);
        if ($ts === false) {
            return false;
        }
        return date('Y-m-d', $ts) === date('Y-m-d');
    }

    private static function openSql(): string {
        $roles = "'" . implode("','", self::HOLDER_ROLES) . "'";
        return "SELECT py.id, py.payment_no, py.date, py.amount, py.payment_method, py.created_by,
                       py.cheque_no, pa.name AS party_name, a.type AS account_type, a.name AS account_name,
                       u.name AS holder_name, u.role AS holder_role
                FROM payments py
                INNER JOIN users u ON u.id = py.created_by AND u.role IN ({$roles})
                LEFT JOIN parties pa ON pa.id = py.party_id
                LEFT JOIN accounts a ON a.id = py.account_id
                WHERE py.payment_type = 'in'
                  AND py.status = 'active'
                  AND py.warehouse_id = ?
                  AND py.created_at >= ?
                  AND COALESCE(py.ref_type, '') <> 'discount'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM payment_collection_items i
                      INNER JOIN payment_collections c
                              ON c.id = i.collection_id AND c.undone_at IS NULL
                      WHERE i.payment_id = py.id
                  )";
    }

    /** @param list<array<string,mixed>> $rows @return array{cash:float,bank:float} */
    private static function sumBuckets(array $rows): array {
        $cash = 0.0;
        $bank = 0.0;
        foreach ($rows as $row) {
            $bucket = self::bucket(
                (string) ($row['payment_method'] ?? ''),
                (string) ($row['account_type'] ?? '')
            );
            $amt = round((float) ($row['amount'] ?? 0), 3);
            if ($bucket === 'bank') {
                $bank = round($bank + $amt, 3);
            } else {
                $cash = round($cash + $amt, 3);
            }
        }
        return ['cash' => $cash, 'bank' => $bank];
    }
}
