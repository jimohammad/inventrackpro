<?php

/**
 * API Endpoint: Products / Items
 * GET  /api/?endpoint=products            → list all
 * GET  /api/?endpoint=products&id=5       → get single
 * POST /api/?endpoint=products            → create
 * PUT  /api/?endpoint=products&id=5       → update
 */

$db = Database::getInstance();

switch ($method) {
    case 'GET':
        if (!hasPermission($keyPermissions, 'products', 'read')) {
            apiError(403, 'No permission to read products.');
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            // Single product with stock info
            $product = $db->fetchOne(
                "SELECT i.*, c.name as category_name,
                        COALESCE(SUM(s.quantity), 0) as total_stock
                 FROM items i
                 LEFT JOIN categories c ON c.id = i.category_id
                 LEFT JOIN stock s ON s.item_id = i.id
                 WHERE i.id = ?
                 GROUP BY i.id",
                [$id]
            );

            if (!$product) apiError(404, 'Product not found.');

            apiSuccess(['product' => $product]);
        }

        // List with optional filters
        $category  = (int) ($_GET['category_id'] ?? 0);
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $perPage   = min(100, (int) ($_GET['per_page'] ?? 25));
        $offset    = ($page - 1) * $perPage;

        $where = "WHERE i.is_active = 1";
        $params = [];

        if (!empty($_GET['search'])) {
            $where .= " AND (i.name LIKE ? OR i.sku LIKE ? OR i.barcode LIKE ?)";
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
        }

        if ($category > 0) {
            $where .= " AND i.category_id = ?";
            $params[] = $category;
        }

        $total    = $db->fetchOne("SELECT COUNT(*) as c FROM items i {$where}", $params)['c'];
        $products = $db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.barcode, i.brand, i.model,
                    i.sale_price, i.purchase_price, i.has_imei, i.unit,
                    c.name as category,
                    COALESCE(SUM(s.quantity), 0) as stock
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN stock s ON s.item_id = i.id
             {$where}
             GROUP BY i.id
             ORDER BY i.name ASC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        apiSuccess([
            'products'   => $products,
            'total'      => (int) $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => (int) ceil($total / $perPage),
        ]);
        break;

    case 'POST':
        if (!hasPermission($keyPermissions, 'products', 'write')) {
            apiError(403, 'No permission to create products.');
        }

        $data = getInput();
        if (empty($data['name'])) apiError(422, 'Product name is required.');

        $id = $db->insert(
            "INSERT INTO items (name, sku, barcode, category_id, brand, model, unit, has_imei, purchase_price, sale_price, min_stock)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'],
                $data['sku'] ?? null,
                $data['barcode'] ?? null,
                $data['category_id'] ?? null,
                $data['brand'] ?? null,
                $data['model'] ?? null,
                $data['unit'] ?? 'pcs',
                (int) ($data['has_imei'] ?? 0),
                (float) ($data['purchase_price'] ?? 0),
                (float) ($data['sale_price'] ?? 0),
                (int) ($data['min_stock'] ?? 0),
            ]
        );

        apiSuccess(['message' => 'Product created.', 'id' => (int) $id], 201);
        break;

    case 'PUT':
        if (!hasPermission($keyPermissions, 'products', 'write')) {
            apiError(403, 'No permission to update products.');
        }

        $id   = (int) ($_GET['id'] ?? 0);
        $data = getInput();

        if (!$id) apiError(400, 'Product ID is required.');

        $product = $db->fetchOne("SELECT id FROM items WHERE id = ?", [$id]);
        if (!$product) apiError(404, 'Product not found.');

        $db->execute(
            "UPDATE items SET 
                name = COALESCE(?, name),
                sku = COALESCE(?, sku),
                brand = COALESCE(?, brand),
                sale_price = COALESCE(?, sale_price),
                purchase_price = COALESCE(?, purchase_price),
                min_stock = COALESCE(?, min_stock)
             WHERE id = ?",
            [
                $data['name'] ?? null,
                $data['sku'] ?? null,
                $data['brand'] ?? null,
                isset($data['sale_price']) ? (float)$data['sale_price'] : null,
                isset($data['purchase_price']) ? (float)$data['purchase_price'] : null,
                isset($data['min_stock']) ? (int)$data['min_stock'] : null,
                $id
            ]
        );

        apiSuccess(['message' => 'Product updated.']);
        break;

    default:
        apiError(405, 'Method not allowed.');
}
