<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/OrderRequest.php';

/**
 * Public order request form — iqbal.app/order (also /apps/order).
 * Customers submit for prior confirmation; staff approve in ERP.
 */
class AppsOrderController extends BaseController {

    public function index(): void {
        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $token = trim((string) ($_GET['token'] ?? ''));
        $order = null;
        if ($token !== '') {
            $order = OrderRequest::findByToken($db, $token);
        }

        $companyName = self::getSettings()['company_name'] ?? APP_NAME;
        $stockItems = [];
        if (!$order) {
            $stockItems = self::loadAvailableStockItems($db);
        }
        include __DIR__ . '/../views/public/apps_order.php';
    }

    public function store(): void {
        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $name  = trim((string) ($_POST['customer_name'] ?? ''));
        $phone = preg_replace('/\s+/', '', (string) ($_POST['customer_phone'] ?? ''));
        $notes = trim((string) ($_POST['customer_notes'] ?? ''));
        $honeypot = trim((string) ($_POST['website'] ?? ''));

        if ($honeypot !== '') {
            header('Location: /apps/order?ok=1');
            exit;
        }

        $warehouseId = self::resolvePublicWarehouseId($db);
        $catalog = self::loadAvailableStockItems($db, $warehouseId);
        $byId = [];
        foreach ($catalog as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $rawIds  = $_POST['item_id'] ?? [];
        $rawQtys = $_POST['item_qty'] ?? [];
        if (!is_array($rawIds)) {
            $rawIds = [];
        }
        if (!is_array($rawQtys)) {
            $rawQtys = [];
        }

        $lines = [];
        $errors = [];
        $seen = [];
        $n = min(count($rawIds), count($rawQtys), 40);
        for ($i = 0; $i < $n; $i++) {
            $id = (int) $rawIds[$i];
            $qty = (int) $rawQtys[$i];
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            if ($qty < 1 || $qty > 999) {
                $errors[] = 'Invalid quantity for one of the items.';
                continue;
            }
            if (!isset($byId[$id])) {
                $errors[] = 'One selected item is no longer available in stock.';
                continue;
            }
            $stock = (int) ($byId[$id]['stock'] ?? 0);
            if ($qty > $stock) {
                $errors[] = ($byId[$id]['name'] ?? 'Item') . ' — only ' . $stock . ' available.';
                continue;
            }
            $seen[$id] = true;
            $lines[] = [
                'id'   => $id,
                'name' => (string) $byId[$id]['name'],
                'qty'  => $qty,
            ];
        }

        $itemParts = [];
        foreach ($lines as $line) {
            $itemParts[] = $line['qty'] . 'x ' . $line['name'];
        }
        $items = trim(implode("\n", $itemParts));

        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'Please enter your name.';
        }
        if ($phone === '' || !preg_match('/^[0-9+\-]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        if ($lines === []) {
            $errors[] = 'Please select at least one item from stock.';
        }
        if (mb_strlen($items) > 4000) {
            $errors[] = 'Order items are too long.';
        }
        if ($notes !== '' && mb_strlen($notes) > 2000) {
            $errors[] = 'Notes are too long.';
        }

        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        if ($errors === [] && $ip !== '') {
            $recentIp = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM order_requests
                 WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)",
                [$ip]
            );
            if ((int) ($recentIp['c'] ?? 0) >= 10) {
                $errors[] = 'Too many requests. Please try again later.';
            }
        }
        if ($errors === [] && $phone !== '') {
            $recentPhone = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM order_requests
                 WHERE customer_phone = ? AND status = 'pending'
                   AND created_at >= (NOW() - INTERVAL 1 HOUR)",
                [$phone]
            );
            if ((int) ($recentPhone['c'] ?? 0) >= 5) {
                $errors[] = 'You already have pending requests. Please wait for our confirmation.';
            }
        }

        if ($errors !== []) {
            $this->flash('error', implode(' ', $errors));
            header('Location: /apps/order');
            exit;
        }

        $token = OrderRequest::newPublicToken();
        $requestNo = OrderRequest::nextRequestNo($db);

        $db->insert(
            "INSERT INTO order_requests
                (request_no, public_token, customer_name, customer_phone, items_text, customer_notes, status, warehouse_id, ip_address)
             VALUES (?,?,?,?,?,?, 'pending', ?, ?)",
            [
                $requestNo,
                $token,
                $name,
                $phone,
                $items,
                $notes !== '' ? $notes : null,
                $warehouseId,
                $ip !== '' ? $ip : null,
            ]
        );

        header('Location: /apps/order?token=' . rawurlencode($token) . '&new=1');
        exit;
    }

    /** JSON status for customer polling / notifications. */
    public function status(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $token = trim((string) ($_GET['token'] ?? ''));
        $row = OrderRequest::findByToken($db, $token);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            exit;
        }

        echo json_encode([
            'ok'            => true,
            'request_no'    => $row['request_no'],
            'status'        => $row['status'],
            'staff_note'    => $row['staff_note'] ?? '',
            'reviewed_at'   => $row['reviewed_at'] ?? null,
            'customer_name' => $row['customer_name'],
        ]);
        exit;
    }

    private static function resolvePublicWarehouseId(Database $db): int {
        $defaultWh = $db->fetchOne(
            'SELECT id FROM warehouses WHERE is_active = 1 ORDER BY is_default DESC, id ASC LIMIT 1'
        );
        return $defaultWh ? (int) $defaultWh['id'] : 1;
    }

    /**
     * In-stock active items for the public order picker (branch-scoped).
     *
     * @return list<array{id:int,name:string,brand:?string,category_name:?string,stock:int}>
     */
    private static function loadAvailableStockItems(Database $db, ?int $warehouseId = null): array {
        $wh = $warehouseId ?? self::resolvePublicWarehouseId($db);
        $rows = $db->fetchAll(
            "SELECT i.id, i.name, i.brand, c.name AS category_name,
                    COALESCE(SUM(s.quantity), 0) AS stock
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1
             GROUP BY i.id, i.name, i.brand, c.name
             HAVING stock > 0
             ORDER BY c.name ASC, i.name ASC
             LIMIT 800",
            [$wh]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'            => (int) $r['id'],
                'name'          => (string) $r['name'],
                'brand'         => $r['brand'] !== null && $r['brand'] !== '' ? (string) $r['brand'] : null,
                'category_name' => $r['category_name'] !== null && $r['category_name'] !== '' ? (string) $r['category_name'] : null,
                'stock'         => (int) $r['stock'],
            ];
        }
        return $out;
    }
}
