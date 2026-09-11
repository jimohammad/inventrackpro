<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/IMEI.php';
require_once __DIR__ . '/../helpers/ImeiFormat.php';
require_once __DIR__ . '/../services/IntershopPeerClient.php';

/**
 * Stock-only transfer between this shop and the peer ERP (separate database).
 * No invoices, payments, or party ledger.
 */
class IntershopTransfer extends BaseModel {
    protected string $table = 'intershop_transfers';

    /** @var bool|null */
    private static $schemaReady = null;

    public static function ensureSchema(Database $db): bool {
        if (self::$schemaReady === true) {
            return true;
        }
        if ($db->inTransaction()) {
            return self::$schemaReady === true;
        }
        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS intershop_transfers (
                    id               INT AUTO_INCREMENT PRIMARY KEY,
                    transfer_no      VARCHAR(32) NOT NULL,
                    direction        ENUM('outbound','inbound') NOT NULL,
                    peer_name        VARCHAR(120) NOT NULL DEFAULT '',
                    peer_ref         VARCHAR(32) NULL,
                    idempotency_key  VARCHAR(64) NOT NULL,
                    warehouse_id     INT NOT NULL,
                    date             DATE NOT NULL,
                    status           VARCHAR(24) NOT NULL DEFAULT 'pending',
                    notes            VARCHAR(500) NULL,
                    last_error       VARCHAR(500) NULL,
                    created_by       INT NULL,
                    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_transfer_no (transfer_no),
                    UNIQUE KEY uniq_idem (idempotency_key),
                    INDEX idx_ist_wh_date (warehouse_id, date),
                    INDEX idx_ist_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $db->execute(
                "CREATE TABLE IF NOT EXISTS intershop_transfer_items (
                    id            INT AUTO_INCREMENT PRIMARY KEY,
                    transfer_id   INT NOT NULL,
                    item_id       INT NOT NULL,
                    sku           VARCHAR(80) NOT NULL,
                    item_name     VARCHAR(200) NOT NULL DEFAULT '',
                    quantity      INT NOT NULL,
                    INDEX idx_isti_transfer (transfer_id),
                    INDEX idx_isti_item (item_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $db->execute(
                "CREATE TABLE IF NOT EXISTS intershop_transfer_imei (
                    id                 INT AUTO_INCREMENT PRIMARY KEY,
                    transfer_item_id   INT NOT NULL,
                    imei               VARCHAR(32) NOT NULL,
                    imei_id            INT NULL,
                    INDEX idx_istm_item (transfer_item_id),
                    INDEX idx_istm_imei (imei)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::$schemaReady = true;
            return true;
        } catch (Throwable $e) {
            self::$schemaReady = false;
            error_log('[IntershopTransfer] ensureSchema: ' . $e->getMessage());
            return false;
        }
    }

    public static function schemaIsReady(): bool {
        return self::$schemaReady === true;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listForWarehouse(int $warehouseId, int $limit = 200): array {
        return $this->db->fetchAll(
            "SELECT t.*, u.name AS created_by_name
             FROM intershop_transfers t
             LEFT JOIN users u ON u.id = t.created_by
             WHERE t.warehouse_id = ?
             ORDER BY t.id DESC
             LIMIT " . max(1, min(500, $limit)),
            [$warehouseId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findWithLines(int $id, int $warehouseId): ?array {
        $row = $this->db->fetchOne(
            "SELECT t.*, u.name AS created_by_name
             FROM intershop_transfers t
             LEFT JOIN users u ON u.id = t.created_by
             WHERE t.id = ? AND t.warehouse_id = ?",
            [$id, $warehouseId]
        );
        if (!$row) {
            return null;
        }
        $items = $this->db->fetchAll(
            "SELECT * FROM intershop_transfer_items WHERE transfer_id = ? ORDER BY id ASC",
            [$id]
        );
        foreach ($items as &$item) {
            $item['imeis'] = $this->db->fetchAll(
                "SELECT imei, imei_id FROM intershop_transfer_imei WHERE transfer_item_id = ? ORDER BY id ASC",
                [(int) $item['id']]
            );
        }
        unset($item);
        $row['items'] = $items;
        return $row;
    }

    /**
     * Deduct local stock + mark IMEIs transferred. Caller commits, then POSTs the peer.
     *
     * @param list<array{item_id:int,quantity:int,imeis:list<string>}> $lines
     * @return array{id:int,transfer_no:string,idempotency_key:string,payload:array<string,mixed>}
     */
    public function createOutbound(int $warehouseId, string $date, string $notes, ?int $userId, array $lines): array {
        if ($warehouseId <= 0) {
            throw new InvalidArgumentException('Warehouse is required.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Add at least one item.');
        }

        $peerName = IntershopPeerClient::peerName();
        $key      = bin2hex(random_bytes(16));
        $prefix   = defined('INTERSHOP_PREFIX') ? INTERSHOP_PREFIX : 'IST-';

        $this->db->beginTransaction();
        try {
            $last = $this->db->fetchOne(
                "SELECT transfer_no FROM intershop_transfers ORDER BY id DESC LIMIT 1 FOR UPDATE"
            );
            $num = 0;
            if ($last && preg_match('/(\d+)$/', (string) $last['transfer_no'], $m)) {
                $num = (int) $m[1];
            }
            $transferNo = $prefix . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);

            $transferId = (int) $this->db->insert(
                "INSERT INTO intershop_transfers
                    (transfer_no, direction, peer_name, idempotency_key, warehouse_id, date, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$transferNo, 'outbound', $peerName, $key, $warehouseId, $date, 'pending', $notes !== '' ? $notes : null, $userId]
            );

            $payloadItems = [];
            $imeiModel    = new IMEI();

            foreach ($lines as $line) {
                $itemId = (int) ($line['item_id'] ?? 0);
                $qty    = (int) ($line['quantity'] ?? 0);
                if ($itemId <= 0 || $qty <= 0) {
                    throw new RuntimeException('Each line needs an item and a quantity.');
                }

                $item = $this->db->fetchOne(
                    "SELECT id, name, sku, barcode, has_imei, COALESCE(serial_kind,'phone') AS serial_kind
                     FROM items WHERE id = ? AND is_active = 1 FOR UPDATE",
                    [$itemId]
                );
                if (!$item) {
                    throw new RuntimeException('Item not found.');
                }
                $sku = strtoupper(trim((string) ($item['sku'] ?? '')));
                if ($sku === '') {
                    throw new RuntimeException('Item "' . $item['name'] . '" has no SKU. Set the same SKU on both shops first.');
                }

                $imeis = [];
                foreach ($line['imeis'] ?? [] as $raw) {
                    $n = ImeiFormat::normalize((string) $raw);
                    if ($n !== '') {
                        $imeis[] = $n;
                    }
                }
                $imeis = array_values(array_unique($imeis));

                $hasImei = (int) ($item['has_imei'] ?? 0) === 1;
                if ($hasImei && count($imeis) !== $qty) {
                    throw new RuntimeException('Item "' . $item['name'] . '": scan ' . $qty . ' serial(s), got ' . count($imeis) . '.');
                }
                if (!$hasImei && $imeis !== []) {
                    throw new RuntimeException('Item "' . $item['name'] . '" is not serial-tracked.');
                }

                $this->db->fetchOne(
                    'SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
                    [$itemId, $warehouseId]
                );
                $affected = $this->db->execute(
                    'UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?',
                    [$qty, $itemId, $warehouseId, $qty]
                );
                if ($affected === 0) {
                    $stock = $this->db->fetchOne(
                        'SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?',
                        [$itemId, $warehouseId]
                    );
                    throw new RuntimeException(
                        'Insufficient stock for "' . $item['name'] . '". Available: ' . (int) ($stock['quantity'] ?? 0)
                    );
                }

                $stiId = (int) $this->db->insert(
                    "INSERT INTO intershop_transfer_items (transfer_id, item_id, sku, item_name, quantity)
                     VALUES (?,?,?,?,?)",
                    [$transferId, $itemId, $sku, (string) $item['name'], $qty]
                );

                $kind = ImeiFormat::normalizeKind((string) $item['serial_kind']);
                foreach ($imeis as $imei) {
                    $err = ImeiFormat::error($imei, $kind, (string) $item['name']);
                    if ($err !== null) {
                        throw new RuntimeException($item['name'] . ': ' . $err);
                    }
                    $marked = $imeiModel->markTransferredOut($imei, $itemId, $warehouseId, $transferNo);
                    if (empty($marked['ok'])) {
                        throw new RuntimeException((string) ($marked['msg'] ?? 'IMEI ' . $imei . ' cannot leave stock.'));
                    }
                    $this->db->insert(
                        "INSERT INTO intershop_transfer_imei (transfer_item_id, imei, imei_id) VALUES (?,?,?)",
                        [$stiId, $imei, (int) ($marked['imei_id'] ?? 0)]
                    );
                }

                $payloadItems[] = [
                    'sku'      => $sku,
                    'barcode'  => (string) ($item['barcode'] ?? ''),
                    'name'     => (string) $item['name'],
                    'quantity' => $qty,
                    'has_imei' => $hasImei ? 1 : 0,
                    'imeis'    => $imeis,
                ];
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollbackQuiet();
            throw $e;
        }

        return [
            'id'              => $transferId,
            'transfer_no'     => $transferNo,
            'idempotency_key' => $key,
            'payload'         => [
                'idempotency_key' => $key,
                'transfer_no'     => $transferNo,
                'from_shop'       => defined('APP_NAME') ? APP_NAME : 'shop',
                'date'            => $date,
                'notes'           => $notes,
                'items'           => $payloadItems,
            ],
        ];
    }

    public function markSent(int $id, ?string $peerRef): void {
        $this->db->execute(
            "UPDATE intershop_transfers SET status = 'sent', peer_ref = ?, last_error = NULL WHERE id = ? AND direction = 'outbound'",
            [$peerRef, $id]
        );
    }

    public function markFailed(int $id, string $error): void {
        $this->db->execute(
            "UPDATE intershop_transfers SET status = 'failed', last_error = ? WHERE id = ? AND direction = 'outbound'",
            [mb_substr($error, 0, 500), $id]
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function payloadForRetry(int $id, int $warehouseId): array {
        $doc = $this->findWithLines($id, $warehouseId);
        if (!$doc || ($doc['direction'] ?? '') !== 'outbound') {
            throw new RuntimeException('Outbound transfer not found.');
        }
        $items = [];
        foreach ($doc['items'] as $line) {
            $imeis = [];
            foreach ($line['imeis'] ?? [] as $row) {
                $imeis[] = (string) $row['imei'];
            }
            $item = $this->db->fetchOne("SELECT barcode, has_imei FROM items WHERE id = ?", [(int) $line['item_id']]);
            $items[] = [
                'sku'      => (string) $line['sku'],
                'barcode'  => (string) ($item['barcode'] ?? ''),
                'name'     => (string) $line['item_name'],
                'quantity' => (int) $line['quantity'],
                'has_imei' => (int) ($item['has_imei'] ?? 0) === 1 ? 1 : 0,
                'imeis'    => $imeis,
            ];
        }
        return [
            'idempotency_key' => (string) $doc['idempotency_key'],
            'transfer_no'     => (string) $doc['transfer_no'],
            'from_shop'       => defined('APP_NAME') ? APP_NAME : 'shop',
            'date'            => (string) $doc['date'],
            'notes'           => (string) ($doc['notes'] ?? ''),
            'items'           => $items,
        ];
    }

    public function cancelOutbound(int $id, int $warehouseId): void {
        $this->db->beginTransaction();
        try {
            $doc = $this->db->fetchOne(
                "SELECT * FROM intershop_transfers WHERE id = ? AND warehouse_id = ? FOR UPDATE",
                [$id, $warehouseId]
            );
            if (!$doc || ($doc['direction'] ?? '') !== 'outbound') {
                throw new RuntimeException('Outbound transfer not found.');
            }
            $status = (string) ($doc['status'] ?? '');
            if (!in_array($status, ['pending', 'failed'], true)) {
                throw new RuntimeException('Only pending or failed sends can be cancelled. If the other shop already received the stock, send a reverse transfer.');
            }

            $imeiModel = new IMEI();
            $lines = $this->db->fetchAll(
                "SELECT * FROM intershop_transfer_items WHERE transfer_id = ?",
                [$id]
            );
            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $qty    = (int) $line['quantity'];
                $stockRow = $this->db->fetchOne(
                    "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [$itemId, $warehouseId]
                );
                if ($stockRow) {
                    $this->db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                        [$qty, $stockRow['id']]
                    );
                } else {
                    $this->db->insert(
                        "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                        [$itemId, $warehouseId, $qty]
                    );
                }
                $imeis = $this->db->fetchAll(
                    "SELECT imei FROM intershop_transfer_imei WHERE transfer_item_id = ?",
                    [(int) $line['id']]
                );
                foreach ($imeis as $row) {
                    $restored = $imeiModel->restoreFromIntershopOut((string) $row['imei'], $itemId, $warehouseId);
                    if (empty($restored['ok'])) {
                        throw new RuntimeException((string) ($restored['msg'] ?? 'Could not restore IMEI.'));
                    }
                }
            }

            $this->db->execute(
                "UPDATE intershop_transfers SET status = 'cancelled', last_error = NULL WHERE id = ?",
                [$id]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollbackQuiet();
            throw $e;
        }
    }

    /**
     * Apply inbound payload from the peer. Idempotent on idempotency_key.
     *
     * @param array<string,mixed> $payload
     * @return array{inbound_no:string,idempotent:bool}
     */
    public function receiveInbound(int $warehouseId, array $payload): array {
        $key = trim((string) ($payload['idempotency_key'] ?? ''));
        if ($key === '' || strlen($key) > 64) {
            throw new InvalidArgumentException('Missing transfer key.');
        }

        $existing = $this->db->fetchOne(
            "SELECT id, transfer_no, status FROM intershop_transfers WHERE idempotency_key = ? LIMIT 1",
            [$key]
        );
        if ($existing) {
            return [
                'inbound_no' => (string) $existing['transfer_no'],
                'idempotent' => true,
            ];
        }

        $itemsIn = $payload['items'] ?? [];
        if (!is_array($itemsIn) || $itemsIn === []) {
            throw new InvalidArgumentException('Transfer has no items.');
        }

        $peerName = trim((string) ($payload['from_shop'] ?? IntershopPeerClient::peerName()));
        $peerRef  = trim((string) ($payload['transfer_no'] ?? ''));
        $date     = trim((string) ($payload['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $notes  = trim((string) ($payload['notes'] ?? ''));
        $prefix = defined('INTERSHOP_PREFIX') ? INTERSHOP_PREFIX : 'IST-';

        $this->db->beginTransaction();
        try {
            $dup = $this->db->fetchOne(
                "SELECT transfer_no FROM intershop_transfers WHERE idempotency_key = ? LIMIT 1 FOR UPDATE",
                [$key]
            );
            if ($dup) {
                $this->db->commit();
                return ['inbound_no' => (string) $dup['transfer_no'], 'idempotent' => true];
            }

            $last = $this->db->fetchOne(
                "SELECT transfer_no FROM intershop_transfers ORDER BY id DESC LIMIT 1 FOR UPDATE"
            );
            $num = 0;
            if ($last && preg_match('/(\d+)$/', (string) $last['transfer_no'], $m)) {
                $num = (int) $m[1];
            }
            $transferNo = $prefix . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);

            $transferId = (int) $this->db->insert(
                "INSERT INTO intershop_transfers
                    (transfer_no, direction, peer_name, peer_ref, idempotency_key, warehouse_id, date, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$transferNo, 'inbound', $peerName, $peerRef !== '' ? $peerRef : null, $key, $warehouseId, $date, 'received', $notes !== '' ? $notes : null]
            );

            $imeiModel = new IMEI();
            foreach ($itemsIn as $line) {
                if (!is_array($line)) {
                    throw new RuntimeException('Invalid item line.');
                }
                $sku = strtoupper(trim((string) ($line['sku'] ?? '')));
                $barcode = trim((string) ($line['barcode'] ?? ''));
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new RuntimeException('Quantity must be greater than zero.');
                }
                $local = $this->matchItem($sku, $barcode);
                if ($local === null) {
                    $label = $sku !== '' ? $sku : ($barcode !== '' ? $barcode : (string) ($line['name'] ?? 'item'));
                    throw new RuntimeException('No matching item for SKU "' . $label . '". Create it on this shop with the same SKU, then retry.');
                }

                $imeis = [];
                foreach ($line['imeis'] ?? [] as $raw) {
                    $n = ImeiFormat::normalize((string) $raw);
                    if ($n !== '') {
                        $imeis[] = $n;
                    }
                }
                $imeis = array_values(array_unique($imeis));
                $hasImei = (int) ($local['has_imei'] ?? 0) === 1;
                if ($hasImei && count($imeis) !== $qty) {
                    throw new RuntimeException('Item "' . $local['name'] . '": expected ' . $qty . ' serial(s).');
                }
                if (!$hasImei && $imeis !== []) {
                    throw new RuntimeException('Item "' . $local['name'] . '" is not serial-tracked on this shop.');
                }

                $stockRow = $this->db->fetchOne(
                    "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [(int) $local['id'], $warehouseId]
                );
                if ($stockRow) {
                    $this->db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                        [$qty, $stockRow['id']]
                    );
                } else {
                    $this->db->insert(
                        "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                        [(int) $local['id'], $warehouseId, $qty]
                    );
                }

                $stiId = (int) $this->db->insert(
                    "INSERT INTO intershop_transfer_items (transfer_id, item_id, sku, item_name, quantity)
                     VALUES (?,?,?,?,?)",
                    [$transferId, (int) $local['id'], (string) $local['sku'], (string) $local['name'], $qty]
                );

                $kind = ImeiFormat::normalizeKind((string) ($local['serial_kind'] ?? 'phone'));
                foreach ($imeis as $imei) {
                    $err = ImeiFormat::error($imei, $kind, (string) $local['name']);
                    if ($err !== null) {
                        throw new RuntimeException($local['name'] . ': ' . $err);
                    }
                    $attached = $imeiModel->attachFromIntershop($imei, (int) $local['id'], $warehouseId, $transferNo);
                    if (empty($attached['ok'])) {
                        throw new RuntimeException((string) ($attached['msg'] ?? 'IMEI ' . $imei . ' cannot enter stock.'));
                    }
                    $this->db->insert(
                        "INSERT INTO intershop_transfer_imei (transfer_item_id, imei, imei_id) VALUES (?,?,?)",
                        [$stiId, $imei, (int) ($attached['imei_id'] ?? 0)]
                    );
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollbackQuiet();
            throw $e;
        }

        return ['inbound_no' => $transferNo, 'idempotent' => false];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function matchItem(string $sku, string $barcode): ?array {
        if ($sku !== '') {
            $rows = $this->db->fetchAll(
                "SELECT id, name, sku, has_imei, COALESCE(serial_kind,'phone') AS serial_kind
                 FROM items
                 WHERE is_active = 1 AND sku IS NOT NULL AND TRIM(sku) != ''
                   AND UPPER(TRIM(sku)) = ?",
                [$sku]
            );
            if (count($rows) === 1) {
                return $rows[0];
            }
            if (count($rows) > 1) {
                throw new RuntimeException('SKU "' . $sku . '" matches more than one item on this shop.');
            }
        }
        $barcode = trim($barcode);
        if ($barcode !== '') {
            $rows = $this->db->fetchAll(
                "SELECT id, name, sku, has_imei, COALESCE(serial_kind,'phone') AS serial_kind
                 FROM items
                 WHERE is_active = 1 AND barcode IS NOT NULL AND TRIM(barcode) != ''
                   AND TRIM(barcode) = ?",
                [$barcode]
            );
            if (count($rows) === 1) {
                return $rows[0];
            }
            if (count($rows) > 1) {
                throw new RuntimeException('Barcode "' . $barcode . '" matches more than one item on this shop.');
            }
        }
        return null;
    }

    public static function qtyInbound(Database $db, int $itemId, int $warehouseId): int {
        try {
            return (int) ($db->fetchOne(
                "SELECT COALESCE(SUM(iti.quantity), 0) AS q
                 FROM intershop_transfer_items iti
                 JOIN intershop_transfers it ON it.id = iti.transfer_id
                 WHERE iti.item_id = ? AND it.warehouse_id = ?
                   AND it.direction = 'inbound' AND it.status = 'received'",
                [$itemId, $warehouseId]
            )['q'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function qtyOutbound(Database $db, int $itemId, int $warehouseId): int {
        try {
            return (int) ($db->fetchOne(
                "SELECT COALESCE(SUM(iti.quantity), 0) AS q
                 FROM intershop_transfer_items iti
                 JOIN intershop_transfers it ON it.id = iti.transfer_id
                 WHERE iti.item_id = ? AND it.warehouse_id = ?
                   AND it.direction = 'outbound' AND it.status IN ('pending','sent','failed')",
                [$itemId, $warehouseId]
            )['q'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}
