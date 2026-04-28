<?php

require_once __DIR__ . '/BaseModel.php';

class Item extends BaseModel {
    protected string $table = 'items';

    // All active items with total stock
    public function getAllWithStock(?int $warehouseId = null): array {
        $params = [];
        $warehouseClause = '';
        if ($warehouseId) {
            $warehouseClause = "AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        return $this->db->fetchAll(
            "SELECT i.*, c.name as category_name,
                    COALESCE(SUM(s.quantity), 0) as total_stock
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN stock s ON s.item_id = i.id {$warehouseClause}
             WHERE i.is_active = 1
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
    public function search(string $query, ?int $warehouseId = null): array {
        $like = "%{$query}%";
        $params = [];
        $warehouseClause = '';
        if ($warehouseId) {
            $warehouseClause = "AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        $params = array_merge($params, [$like, $like, $like]);
        return $this->db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.barcode, i.sale_price, i.purchase_price,
                    i.has_imei, COALESCE(i.imei_optional, 0) as imei_optional, i.unit, i.category_id,
                    COALESCE(c.name, '') as category_name,
                    COALESCE(SUM(s.quantity), 0) as stock
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id {$warehouseClause}
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE i.is_active = 1
               AND (i.name LIKE ? OR i.sku LIKE ? OR i.barcode LIKE ?)
             GROUP BY i.id
             ORDER BY i.name ASC
             LIMIT 15",
            $params
        );
    }

    // Create new item
    public function create(array $data): int|false {
        return $this->db->insert(
            "INSERT INTO items
                (name, sku, barcode, category_id, brand, model, unit, has_imei,
                 purchase_price, sale_price, min_stock, description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'],
                $data['sku'] ?: null,
                $data['barcode'] ?: null,
                $data['category_id'] ?: null,
                $data['brand'] ?: null,
                $data['model'] ?: null,
                $data['unit'] ?? 'pcs',
                (int) ($data['has_imei'] ?? 0),
                (float) ($data['purchase_price'] ?? 0),
                (float) ($data['sale_price'] ?? 0),
                (int) ($data['min_stock'] ?? 0),
                $data['description'] ?: null,
            ]
        );
    }

    // Update item
    public function update(int $id, array $data): int {
        return $this->db->execute(
            "UPDATE items SET
                name=?, sku=?, barcode=?, category_id=?, brand=?, model=?,
                unit=?, has_imei=?, purchase_price=?, sale_price=?,
                min_stock=?, description=?, is_active=?
             WHERE id=?",
            [
                $data['name'],
                $data['sku'] ?: null,
                $data['barcode'] ?: null,
                $data['category_id'] ?: null,
                $data['brand'] ?: null,
                $data['model'] ?: null,
                $data['unit'] ?? 'pcs',
                (int) ($data['has_imei'] ?? 0),
                (float) ($data['purchase_price'] ?? 0),
                (float) ($data['sale_price'] ?? 0),
                (int) ($data['min_stock'] ?? 0),
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
