<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/ImeiFormat.php';

class Item extends BaseModel {
    protected string $table = 'items';

    /** Hostinger has no migration runner — add items.serial_kind on first use. */
    public static function ensureSerialKindColumn(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $db  = Database::getInstance();
            $col = $db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'items'
                   AND COLUMN_NAME = 'serial_kind'
                 LIMIT 1"
            );
            if ($col) {
                return;
            }
            $db->execute(
                "ALTER TABLE items ADD COLUMN serial_kind VARCHAR(16) NOT NULL DEFAULT 'phone' AFTER has_imei"
            );
        } catch (Throwable $e) {
            error_log('[Item] ensureSerialKindColumn: ' . $e->getMessage());
        }
    }

    // All active items with total stock
    public function getAllWithStock(?int $warehouseId = null, bool $includeInactive = false): array {
        $params = [];
        $warehouseClause = '';
        if ($warehouseId) {
            $warehouseClause = "AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        $activeClause = $includeInactive ? '' : 'WHERE i.is_active = 1';
        return $this->db->fetchAll(
            "SELECT i.*, c.name as category_name,
                    COALESCE(SUM(s.quantity), 0) as total_stock
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN stock s ON s.item_id = i.id {$warehouseClause}
             {$activeClause}
             GROUP BY i.id
             ORDER BY i.name ASC",
            $params
        );
    }

    // Single item with stock
    public function findWithStock(int $id, ?int $warehouseId = null): array|false {
        $params = [];
        $warehouseClause = '';
        if ($warehouseId) {
            $warehouseClause = "AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        $params[] = $id;
        return $this->db->fetchOne(
            "SELECT i.*, c.name as category_name,
                    COALESCE(SUM(s.quantity), 0) as total_stock
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN stock s ON s.item_id = i.id {$warehouseClause}
             WHERE i.id = ?
             GROUP BY i.id",
            $params
        );
    }

    // Search items (for autocomplete in sales/purchase form)
    public function search(string $query, ?int $warehouseId = null, bool $withStock = true, bool $includeCost = false): array {
        self::ensureSerialKindColumn();
        $like = "%{$query}%";
        $costCol = $includeCost ? 'i.purchase_price,' : '';
        $rows = $this->db->fetchAll(
            "SELECT i.id, i.name, i.name_ar, i.sku, i.barcode, i.sale_price, {$costCol}
                    i.has_imei, COALESCE(i.serial_kind, 'phone') as serial_kind,
                    COALESCE(i.imei_optional, 0) as imei_optional,
                    COALESCE(i.max_sale_qty, 0) as max_sale_qty, i.unit, i.category_id,
                    COALESCE(c.name, '') as category_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE i.is_active = 1
               AND (i.name LIKE ? OR i.name_ar LIKE ? OR i.sku LIKE ? OR i.barcode LIKE ?)
             ORDER BY i.name ASC
             LIMIT 15",
            [$like, $like, $like, $like]
        );
        if (!$withStock) {
            return $rows;
        }
        return $this->attachSearchStock($rows, $warehouseId);
    }

    /**
     * Fill stock for a small autocomplete set (New Sale two-phase search).
     *
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    public function attachSearchStock(array $items, ?int $warehouseId = null): array {
        if ($items === []) {
            return $items;
        }

        $ids = [];
        foreach ($items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return $items;
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $whSql = '';
        if ($warehouseId) {
            $whSql = ' AND warehouse_id = ?';
            $params[] = (int) $warehouseId;
        }
        $rows = $this->db->fetchAll(
            "SELECT item_id, COALESCE(SUM(quantity), 0) AS stock
             FROM stock
             WHERE item_id IN ({$ph}){$whSql}
             GROUP BY item_id",
            $params
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['item_id']] = (float) ($r['stock'] ?? 0);
        }
        foreach ($items as &$row) {
            $row['stock'] = $map[(int) ($row['id'] ?? 0)] ?? 0;
        }
        unset($row);

        return $items;
    }

    // Create new item
    public function create(array $data): int|false {
        self::ensureSerialKindColumn();
        $nameAr = trim((string) ($data['name_ar'] ?? ''));
        $kind   = ImeiFormat::normalizeKind((string) ($data['serial_kind'] ?? ImeiFormat::KIND_PHONE));
        return $this->db->insert(
            "INSERT INTO items
                (name, name_ar, sku, barcode, category_id, brand, model, unit, has_imei, serial_kind, imei_optional, has_nfc,
                 purchase_price, sale_price, min_stock, max_sale_qty, description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'],
                $nameAr !== '' ? $nameAr : null,
                $data['sku'] ?: null,
                $data['barcode'] ?: null,
                $data['category_id'] ?: null,
                $data['brand'] ?: null,
                $data['model'] ?: null,
                $data['unit'] ?? 'pcs',
                (int) ($data['has_imei'] ?? 0),
                $kind,
                (int) ($data['imei_optional'] ?? 0),
                (int) ($data['has_nfc'] ?? 0),
                (float) ($data['purchase_price'] ?? 0),
                (float) ($data['sale_price'] ?? 0),
                (int) ($data['min_stock'] ?? 0),
                max(0, (int) ($data['max_sale_qty'] ?? 0)),
                $data['description'] ?: null,
            ]
        );
    }

    // Update item (price_aed / price_usd are legacy columns — not edited from item master)
    public function update(int $id, array $data): int {
        self::ensureSerialKindColumn();
        $nameAr = trim((string) ($data['name_ar'] ?? ''));
        $kind   = ImeiFormat::normalizeKind((string) ($data['serial_kind'] ?? ImeiFormat::KIND_PHONE));
        return $this->db->execute(
            "UPDATE items SET
                name=?, name_ar=?, sku=?, barcode=?, category_id=?, brand=?, model=?,
                unit=?, has_imei=?, serial_kind=?, imei_optional=?, has_nfc=?, purchase_price=?,
                sale_price=?, min_stock=?, max_sale_qty=?, description=?, is_active=?
             WHERE id=?",
            [
                $data['name'],
                $nameAr !== '' ? $nameAr : null,
                $data['sku'] ?: null,
                $data['barcode'] ?: null,
                $data['category_id'] ?: null,
                $data['brand'] ?: null,
                $data['model'] ?: null,
                $data['unit'] ?? 'pcs',
                (int) ($data['has_imei'] ?? 0),
                $kind,
                (int) ($data['imei_optional'] ?? 0),
                (int) ($data['has_nfc'] ?? 0),
                (float) ($data['purchase_price'] ?? 0),
                (float) ($data['sale_price'] ?? 0),
                (int) ($data['min_stock'] ?? 0),
                max(0, (int) ($data['max_sale_qty'] ?? 0)),
                $data['description'] ?: null,
                (int) ($data['is_active'] ?? 1),
                $id,
            ]
        );
    }

    // Get stock for an item in a specific warehouse
    public function getStock(int $itemId, int $warehouseId): int {
        $row = $this->db->fetchOne(
            "SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?",
            [$itemId, $warehouseId]
        );
        return (int) ($row['quantity'] ?? 0);
    }

    // Add or update stock
    // BUG FIX: Race condition + wrong quantity on subtract-with-no-row.
    // Previously used SELECT then INSERT/UPDATE (two queries). Two concurrent
    // 'add' calls could both see no row and both try INSERT, causing duplicate key error.
    // Also, subtract with no existing row inserted quantity=0 instead of -$qty, silently
    // losing the subtraction. Now uses atomic INSERT ... ON DUPLICATE KEY UPDATE.
    public function adjustStock(int $itemId, int $warehouseId, int $qty, string $direction = 'add'): void {
        if (!in_array($direction, ['add', 'subtract'], true)) {
            throw new InvalidArgumentException("adjustStock: direction must be 'add' or 'subtract', got '{$direction}'");
        }
        if ($direction === 'add') {
            $this->db->execute(
                "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                [$itemId, $warehouseId, $qty, $qty]
            );
        } else {
            $this->db->execute(
                "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity - ?",
                [$itemId, $warehouseId, -$qty, $qty]
            );
        }
    }

    // Get all categories
    public function getCategories(): array {
        return $this->db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
    }

    // Get all warehouses
    public function getWarehouses(): array {
        return $this->db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name ASC");
    }
}
