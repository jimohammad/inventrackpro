<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Return.php';
require_once __DIR__ . '/../helpers/ImeiFormat.php';

/**
 * Dump Credit — unrepairable sold units credited to a party and marked dumped.
 * Does not restock the phone. Parts harvest is not in v1.
 */
class DeviceDump extends BaseModel {
    protected string $table = 'device_dumps';

    public const STATUS_APPROVED  = 'approved';
    public const STATUS_CANCELLED = 'cancelled';
    public const MAX_IMEIS        = 50;

    private static ?bool $schemaReady = null;
    private static string $schemaError = 'Dump credit table could not be created. Check database permissions.';

    public static function ensureSchema(Database $db): bool {
        if (self::$schemaReady === true) {
            return true;
        }
        if (self::$schemaReady === false) {
            return false;
        }

        if ($db->inTransaction()) {
            try {
                $db->fetchOne('SELECT 1 AS ok FROM device_dumps LIMIT 1');
                self::$schemaReady = true;
                return true;
            } catch (Throwable $e) {
                return false;
            }
        }

        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS device_dumps (
                    id             INT AUTO_INCREMENT PRIMARY KEY,
                    dump_no        VARCHAR(20) NOT NULL,
                    party_id       INT NOT NULL,
                    warehouse_id   INT NOT NULL,
                    date           DATE NOT NULL,
                    grand_total    DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                    reason         VARCHAR(500) NULL,
                    status         VARCHAR(20) NOT NULL DEFAULT 'approved',
                    created_by     INT NULL,
                    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_dump_no (dump_no),
                    INDEX idx_dd_party_wh (party_id, warehouse_id, status),
                    INDEX idx_dd_wh_date (warehouse_id, date),
                    INDEX idx_dd_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $db->execute(
                "CREATE TABLE IF NOT EXISTS device_dump_items (
                    id            INT AUTO_INCREMENT PRIMARY KEY,
                    dump_id       INT NOT NULL,
                    item_id       INT NOT NULL,
                    imei_id       INT NOT NULL,
                    imei          VARCHAR(32) NOT NULL,
                    sale_id       INT NULL,
                    sale_item_id  INT NULL,
                    unit_price    DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                    is_legacy     TINYINT(1) NOT NULL DEFAULT 0,
                    INDEX idx_ddi_dump (dump_id),
                    INDEX idx_ddi_imei (imei_id),
                    INDEX idx_ddi_sale (sale_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            try {
                self::grantDefaultPermissions($db);
            } catch (Throwable $e) {
                error_log('[DeviceDump] grantDefaultPermissions: ' . $e->getMessage());
            }
            if (!self::ensureLegacyColumn($db)) {
                self::$schemaReady = false;
                return false;
            }
            if (!self::ensureImeiDumpedStatus($db)) {
                self::$schemaReady = false;
                return false;
            }
            self::$schemaReady = true;
            return true;
        } catch (Throwable $e) {
            error_log('[DeviceDump] ensureSchema failed: ' . $e->getMessage());
            self::$schemaReady = false;
            return false;
        }
    }

    public static function schemaIsReady(): bool {
        return self::$schemaReady === true;
    }

    public static function schemaErrorMessage(): string {
        return self::$schemaError;
    }

    /**
     * Hostinger has no migration runner — add `dumped` to imei_records.status.
     * DDL must use PDO::exec (native prepares reject ALTER). Append dumped; do not reorder ENUM values.
     */
    private static function ensureImeiDumpedStatus(Database $db): bool {
        try {
            $stmt = $db->getConnection()->query("SHOW COLUMNS FROM imei_records LIKE 'status'");
            $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$col) {
                self::$schemaError = 'imei_records.status column was not found.';
                return false;
            }
            $type = strtolower(trim((string) (self::rowVal($col, 'Type') ?? '')));
            if ($type !== '' && !str_starts_with($type, 'enum(')) {
                return true;
            }
            if (str_contains($type, "'dumped'")) {
                return true;
            }

            $sql = "ALTER TABLE imei_records MODIFY COLUMN status "
                . "ENUM('in_stock','sold','returned','transferred','defective','dumped') DEFAULT 'in_stock'";
            $db->getConnection()->exec($sql);
            return true;
        } catch (Throwable $e) {
            error_log('[DeviceDump] ensureImeiDumpedStatus: ' . $e->getMessage());
            $msg = trim($e->getMessage());
            if (strlen($msg) > 220) {
                $msg = substr($msg, 0, 220) . '…';
            }
            self::$schemaError = $msg !== ''
                ? ('Could not add dumped to IMEI status: ' . $msg)
                : 'Could not add dumped to IMEI status. Run the ALTER on imei_records.status in phpMyAdmin.';
            return false;
        }
    }

    /** Case-insensitive row key (PDO native prepares may lowercase SHOW COLUMNS). */
    private static function rowVal(array $row, string $key): mixed {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }
        $want = strtolower($key);
        foreach ($row as $k => $v) {
            if (strtolower((string) $k) === $want) {
                return $v;
            }
        }
        return null;
    }

    /** Hostinger has no migration runner — add is_legacy after first deploy. */
    private static function ensureLegacyColumn(Database $db): bool {
        try {
            $col = $db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'device_dump_items'
                   AND COLUMN_NAME = 'is_legacy'
                 LIMIT 1"
            );
            if ($col) {
                return true;
            }
            $db->execute(
                'ALTER TABLE device_dump_items ADD COLUMN is_legacy TINYINT(1) NOT NULL DEFAULT 0 AFTER unit_price'
            );
            return true;
        } catch (Throwable $e) {
            error_log('[DeviceDump] ensureLegacyColumn: ' . $e->getMessage());
            return false;
        }
    }

    /** One-time: grant the module to existing admin/manager users. */
    private static function grantDefaultPermissions(Database $db): void {
        $any = $db->fetchOne(
            "SELECT 1 AS ok FROM permissions WHERE module = 'dumps' LIMIT 1"
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
                [$uid, 'dumps', 1, 1, 0, $canDel]
            );
        }
    }

    public function nextNoPreview(): string {
        $last = $this->db->fetchOne(
            "SELECT dump_no FROM device_dumps ORDER BY id DESC LIMIT 1"
        );
        $num = 0;
        if ($last && preg_match('/DUMP-(\d+)$/', (string) ($last['dump_no'] ?? ''), $m)) {
            $num = (int) $m[1];
        }
        return 'DUMP-' . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);
    }

    private function nextNoLocked(): string {
        $last = $this->db->fetchOne(
            "SELECT dump_no FROM device_dumps ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $num = 0;
        if ($last && preg_match('/DUMP-(\d+)$/', (string) ($last['dump_no'] ?? ''), $m)) {
            $num = (int) $m[1];
        }
        return 'DUMP-' . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param array{search?:string,from_date?:string,to_date?:string,party_id?:int} $filters
     * @return list<array<string,mixed>>
     */
    public function getAll(array $filters, int $warehouseId, int $limit = 500): array {
        if ($warehouseId <= 0) {
            return [];
        }
        $where  = 'WHERE d.warehouse_id = ?';
        $params = [$warehouseId];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like    = '%' . $search . '%';
            $where  .= ' AND (d.dump_no LIKE ? OR p.name LIKE ?
                OR EXISTS (SELECT 1 FROM device_dump_items di
                           JOIN items i ON i.id = di.item_id
                           WHERE di.dump_id = d.id AND (di.imei LIKE ? OR i.name LIKE ?)))';
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if (!empty($filters['from_date'])) {
            $where   .= ' AND d.date >= ?';
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where   .= ' AND d.date <= ?';
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['party_id'])) {
            $where   .= ' AND d.party_id = ?';
            $params[] = (int) $filters['party_id'];
        }

        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT d.*, p.name AS party_name, u.name AS created_by_name,
                    (SELECT COUNT(*) FROM device_dump_items di2 WHERE di2.dump_id = d.id) AS unit_count
             FROM device_dumps d
             LEFT JOIN parties p ON p.id = d.party_id
             LEFT JOIN users u ON u.id = d.created_by
             {$where}
             ORDER BY d.created_at DESC
             LIMIT {$limit}",
            $params
        ) ?: [];
    }

    /** @return array<string,mixed>|null */
    public function findFull(int $id, int $warehouseId): ?array {
        if ($id <= 0 || $warehouseId <= 0) {
            return null;
        }
        $row = $this->db->fetchOne(
            "SELECT d.*, p.name AS party_name, p.phone AS party_phone,
                    w.name AS warehouse_name, u.name AS created_by_name
             FROM device_dumps d
             JOIN parties p ON p.id = d.party_id
             JOIN warehouses w ON w.id = d.warehouse_id
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.id = ? AND d.warehouse_id = ?",
            [$id, $warehouseId]
        );
        if (!$row) {
            return null;
        }
        $row['items'] = $this->db->fetchAll(
            "SELECT di.*, i.name AS item_name, i.sku,
                    s.invoice_no AS sale_invoice_no
             FROM device_dump_items di
             JOIN items i ON i.id = di.item_id
             LEFT JOIN sales s ON s.id = di.sale_id
             WHERE di.dump_id = ?
             ORDER BY di.id ASC",
            [$id]
        ) ?: [];
        return $row;
    }

    /**
     * @param list<array{imei?:string,legacy?:bool|int,item_id?:int,unit_price?:float|string}> $entries
     * @return array{ok:bool,id?:int,dump_no?:string,error?:string}
     */
    public function create(array $data, array $entries, int $warehouseId, int $userId): array {
        $partyId = (int) ($data['party_id'] ?? 0);
        $date    = (string) ($data['date'] ?? date('Y-m-d'));
        $reason  = trim((string) ($data['reason'] ?? ''));
        if (strlen($reason) > 500) {
            $reason = substr($reason, 0, 500);
        }

        if ($partyId <= 0) {
            return ['ok' => false, 'error' => 'Select a party.'];
        }
        if ($warehouseId <= 0) {
            return ['ok' => false, 'error' => 'Select a warehouse first.'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'error' => 'Invalid date.'];
        }
        if ($date > date('Y-m-d')) {
            return ['ok' => false, 'error' => 'Dump date cannot be in the future.'];
        }

        $normalized = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $imei = ImeiFormat::normalize((string) ($entry['imei'] ?? ''));
            if ($imei === '') {
                continue;
            }
            if (!ImeiFormat::isPlausible($imei)) {
                return ['ok' => false, 'error' => "Not a valid IMEI/serial: {$imei}"];
            }
            if (isset($normalized[$imei])) {
                return ['ok' => false, 'error' => "IMEI {$imei} is duplicated on this dump."];
            }
            $normalized[$imei] = [
                'imei'       => $imei,
                'legacy'     => !empty($entry['legacy']),
                'item_id'    => (int) ($entry['item_id'] ?? 0),
                'unit_price' => (float) ($entry['unit_price'] ?? 0),
            ];
        }
        if ($normalized === []) {
            return ['ok' => false, 'error' => 'Add at least one IMEI.'];
        }
        if (count($normalized) > self::MAX_IMEIS) {
            return ['ok' => false, 'error' => 'Maximum ' . self::MAX_IMEIS . ' units per dump.'];
        }

        $party = $this->db->fetchOne(
            "SELECT id, type, is_active FROM parties WHERE id = ?",
            [$partyId]
        );
        if (!$party || (int) ($party['is_active'] ?? 0) !== 1) {
            return ['ok' => false, 'error' => 'Party not found or inactive.'];
        }
        if (!in_array((string) $party['type'], ['customer', 'both'], true)) {
            return ['ok' => false, 'error' => 'Dump credit is only for customer parties.'];
        }

        $this->db->beginTransaction();
        try {
            $lines = [];
            $total = 0.0;
            foreach ($normalized as $entry) {
                $line = !empty($entry['legacy'])
                    ? $this->buildLegacyLine($entry, $warehouseId)
                    : $this->buildSoldLine($entry['imei'], $warehouseId);
                $lines[] = $line;
                $total  += (float) $line['unit_price'];
            }

            $dumpNo = $this->nextNoLocked();
            $dumpId = $this->db->insert(
                "INSERT INTO device_dumps
                    (dump_no, party_id, warehouse_id, date, grand_total, reason, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $dumpNo,
                    $partyId,
                    $warehouseId,
                    $date,
                    round($total, 3),
                    $reason !== '' ? $reason : null,
                    self::STATUS_APPROVED,
                    $userId > 0 ? $userId : null,
                ]
            );

            foreach ($lines as $line) {
                $imeiId = (int) $line['imei_id'];
                if (!empty($line['is_legacy'])) {
                    $imeiId = $this->insertLegacyImeiRecord(
                        $line['imei'],
                        (int) $line['item_id'],
                        $warehouseId,
                        $dumpNo
                    );
                    $line['imei_id'] = $imeiId;
                }
                $this->db->insert(
                    "INSERT INTO device_dump_items
                        (dump_id, item_id, imei_id, imei, sale_id, sale_item_id, unit_price, is_legacy)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $dumpId,
                        $line['item_id'],
                        $imeiId,
                        $line['imei'],
                        $line['sale_id'],
                        $line['sale_item_id'],
                        $line['unit_price'],
                        !empty($line['is_legacy']) ? 1 : 0,
                    ]
                );
                if (empty($line['is_legacy'])) {
                    $this->db->execute(
                        "UPDATE imei_records
                         SET status = 'dumped',
                             notes = CONCAT(COALESCE(notes,''), ' | Dump credit ', ?)
                         WHERE id = ? AND status = 'sold'",
                        [$dumpNo, $imeiId]
                    );
                    $changed = $this->db->fetchOne(
                        "SELECT status FROM imei_records WHERE id = ?",
                        [$imeiId]
                    );
                    $still = (string) ($changed['status'] ?? '');
                    if ($still !== 'dumped') {
                        throw new Exception(
                            "Could not mark IMEI {$line['imei']} as dumped (status is still '{$still}')."
                        );
                    }
                }
            }

            $this->db->commit();
            return ['ok' => true, 'id' => (int) $dumpId, 'dump_no' => $dumpNo];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{item_id:int,imei_id:int,imei:string,sale_id:?int,sale_item_id:?int,unit_price:float,is_legacy:bool}
     */
    private function buildSoldLine(string $imei, int $warehouseId): array {
        $row = $this->db->fetchOne(
            "SELECT ir.id, ir.imei, ir.status, ir.item_id, ir.sale_id, ir.warehouse_id
             FROM imei_records ir
             WHERE ir.imei = ? OR ir.imei2 = ?
             LIMIT 1
             FOR UPDATE",
            [$imei, $imei]
        );
        if (!$row) {
            throw new Exception("IMEI {$imei} is not in the app. Add it as an older device (not in app).");
        }
        if ((string) $row['status'] === 'dumped') {
            throw new Exception("IMEI {$imei} is already dumped.");
        }
        if ((string) $row['status'] !== 'sold') {
            throw new Exception("IMEI {$imei} must be a sold unit (current status: {$row['status']}).");
        }

        $imeiId = (int) $row['id'];
        $itemId = (int) $row['item_id'];
        $saleId = (int) ($row['sale_id'] ?? 0);
        $this->assertNotAlreadyDumped($imei, $imeiId);

        $sale = null;
        if ($saleId > 0) {
            $sale = $this->db->fetchOne(
                "SELECT id, warehouse_id, status FROM sales WHERE id = ? LIMIT 1",
                [$saleId]
            );
        }
        if (!$sale || ($sale['status'] ?? '') === 'cancelled') {
            throw new Exception("IMEI {$imei} is not linked to an active sale on this branch.");
        }
        if ((int) $sale['warehouse_id'] !== $warehouseId) {
            throw new Exception("IMEI {$imei} was sold on another branch.");
        }

        $saleItem = $this->db->fetchOne(
            "SELECT si.id, si.unit_price
             FROM sale_item_imei sii
             JOIN sale_items si ON si.id = sii.sale_item_id
             WHERE sii.imei_id = ? AND si.sale_id = ? AND si.item_id = ?
             ORDER BY sii.id DESC
             LIMIT 1",
            [$imeiId, $saleId, $itemId]
        );
        $saleItemId = $saleItem ? (int) $saleItem['id'] : null;

        $price = (new SaleReturn())->resolveSaleReturnUnitPrice(
            $itemId,
            [$imei],
            $saleId > 0 ? $saleId : null,
            null
        );
        if ($price <= 0) {
            throw new Exception("Sold price for IMEI {$imei} is zero — cannot dump.");
        }

        return [
            'item_id'      => $itemId,
            'imei_id'      => $imeiId,
            'imei'         => (string) $row['imei'],
            'sale_id'      => $saleId > 0 ? $saleId : null,
            'sale_item_id' => $saleItemId,
            'unit_price'   => $price,
            'is_legacy'    => false,
        ];
    }

    /**
     * @param array{imei:string,item_id:int,unit_price:float} $entry
     * @return array{item_id:int,imei_id:int,imei:string,sale_id:?int,sale_item_id:?int,unit_price:float,is_legacy:bool}
     */
    private function buildLegacyLine(array $entry, int $warehouseId): array {
        $imei   = $entry['imei'];
        $itemId = (int) $entry['item_id'];
        $price  = round((float) $entry['unit_price'], 3);

        $existing = $this->db->fetchOne(
            "SELECT ir.id, ir.status
             FROM imei_records ir
             WHERE ir.imei = ? OR ir.imei2 = ?
             LIMIT 1
             FOR UPDATE",
            [$imei, $imei]
        );
        if ($existing) {
            throw new Exception("IMEI {$imei} is already in the app — scan it as a sold unit, not as older/not-in-app.");
        }
        $this->assertNotAlreadyDumped($imei, 0);

        if ($itemId <= 0) {
            throw new Exception("Pick the model for older IMEI {$imei}.");
        }
        if ($price <= 0) {
            throw new Exception("Enter the credit amount for older IMEI {$imei}.");
        }

        $item = $this->db->fetchOne(
            "SELECT id, name, is_active, COALESCE(serial_kind, 'phone') AS serial_kind
             FROM items WHERE id = ?",
            [$itemId]
        );
        if (!$item || (int) ($item['is_active'] ?? 0) !== 1) {
            throw new Exception('Item not found or inactive.');
        }
        $kind = ImeiFormat::kindFromItem($item, (string) ($item['name'] ?? ''));
        $err  = ImeiFormat::error($imei, $kind, (string) ($item['name'] ?? ''));
        if ($err !== null) {
            throw new Exception("IMEI {$imei}: {$err}");
        }

        return [
            'item_id'      => $itemId,
            'imei_id'      => 0,
            'imei'         => $imei,
            'sale_id'      => null,
            'sale_item_id' => null,
            'unit_price'   => $price,
            'is_legacy'    => true,
        ];
    }

    private function assertNotAlreadyDumped(string $imei, int $imeiId): void {
        $already = $this->db->fetchOne(
            "SELECT d.dump_no
             FROM device_dump_items di
             JOIN device_dumps d ON d.id = di.dump_id
             WHERE d.status = 'approved'
               AND (di.imei = ? OR (di.imei_id > 0 AND di.imei_id = ?))
             LIMIT 1",
            [$imei, $imeiId]
        );
        if ($already) {
            throw new Exception("IMEI {$imei} was already dumped on {$already['dump_no']}.");
        }
    }

    private function insertLegacyImeiRecord(string $imei, int $itemId, int $warehouseId, string $dumpNo): int {
        $id = $this->db->insert(
            "INSERT INTO imei_records (imei, item_id, warehouse_id, status, notes, created_at)
             VALUES (?,?,?,'dumped',?,NOW())",
            [$imei, $itemId, $warehouseId, 'Pre-app dump credit ' . $dumpNo]
        );
        if ((int) $id <= 0) {
            throw new Exception("Could not register older IMEI {$imei}.");
        }
        return (int) $id;
    }

    /**
     * Reverse dump: IMEI sold again, ledger credit removed. No stock change (never restocked).
     * @return array{ok:bool,error?:string}
     */
    public function void(int $id, int $warehouseId): array {
        $row = $this->findFull($id, $warehouseId);
        if (!$row) {
            return ['ok' => false, 'error' => 'Dump not found.'];
        }
        if (($row['status'] ?? '') !== self::STATUS_APPROVED) {
            return ['ok' => false, 'error' => 'Only an approved dump can be voided.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->fetchOne(
                'SELECT id FROM device_dumps WHERE id = ? AND warehouse_id = ? FOR UPDATE',
                [$id, $warehouseId]
            );
            foreach ($row['items'] as $item) {
                $imeiId  = (int) $item['imei_id'];
                $legacy  = (int) ($item['is_legacy'] ?? 0) === 1;
                $locked = $this->db->fetchOne(
                    'SELECT id, status, sale_id FROM imei_records WHERE id = ? FOR UPDATE',
                    [$imeiId]
                );
                if (!$locked || ($locked['status'] ?? '') !== 'dumped') {
                    throw new Exception(
                        'IMEI ' . ($item['imei'] ?? '') . ' is no longer dumped — cannot void.'
                    );
                }
                if ($legacy) {
                    $this->db->execute(
                        "DELETE FROM imei_records
                         WHERE id = ? AND status = 'dumped' AND (sale_id IS NULL OR sale_id = 0)",
                        [$imeiId]
                    );
                    $still = $this->db->fetchOne(
                        'SELECT id FROM imei_records WHERE id = ?',
                        [$imeiId]
                    );
                    if ($still) {
                        throw new Exception(
                            'Could not remove older IMEI ' . ($item['imei'] ?? '') . ' from the registry.'
                        );
                    }
                    continue;
                }
                $this->db->execute(
                    "UPDATE imei_records
                     SET status = 'sold',
                         notes = CONCAT(COALESCE(notes,''), ' | Dump void ', ?)
                     WHERE id = ? AND status = 'dumped'",
                    [$row['dump_no'], $imeiId]
                );
            }
            $this->db->execute(
                "UPDATE device_dumps SET status = ? WHERE id = ? AND status = ?",
                [self::STATUS_CANCELLED, $id, self::STATUS_APPROVED]
            );
            $this->db->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** IMEI on an approved dump for this sale — blocks invoice cancel. */
    public function firstDumpedImeiOnSale(int $saleId): ?string {
        if ($saleId <= 0 || !self::ensureSchema($this->db)) {
            return null;
        }
        $row = $this->db->fetchOne(
            "SELECT di.imei
             FROM device_dump_items di
             JOIN device_dumps d ON d.id = di.dump_id
             WHERE di.sale_id = ? AND d.status = 'approved'
             LIMIT 1",
            [$saleId]
        );
        return $row ? (string) $row['imei'] : null;
    }
}
