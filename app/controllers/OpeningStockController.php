<?php

require_once __DIR__ . '/BaseController.php';

class OpeningStockController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index(): void {
        Auth::authorize('settings', 'view');

        $warehouses = $this->db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");
        $warehouseId = $this->inputInt('warehouse_id', Auth::warehouseId() ?: 0, 'get');

        // Load existing opening stock log for selected warehouse
        $existing = [];
        $totalValue = 0;
        if ($warehouseId) {
            $existing = $this->db->fetchAll(
                "SELECT osl.*, i.name as item_name, i.sku, w.name as warehouse_name
                 FROM opening_stock_log osl
                 JOIN items i ON i.id = osl.item_id
                 JOIN warehouses w ON w.id = osl.warehouse_id
                 WHERE osl.warehouse_id = ?
                 ORDER BY i.name ASC",
                [$warehouseId]
            );
            $totalValue = array_sum(array_column($existing, 'total_value'));
        }

        $pageTitle = 'Opening Stock';
        $page      = 'openingstock';

        ob_start();
        include __DIR__ . '/../views/opening_stock/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=openingstock'); }

        $warehouseId = $this->inputInt('warehouse_id');
        $date        = $this->input('date') ?: date('Y-m-d');
        $items       = $_POST['items'] ?? [];

        if (!$warehouseId || empty($items)) {
            $this->flash('error', 'Please select a warehouse and add at least one item.');
            $this->redirect('?page=openingstock');
        }

        $this->db->beginTransaction();
        try {
            $saved = 0;
            foreach ($items as $row) {
                $itemId    = (int)($row['item_id'] ?? 0);
                $qty       = (int)($row['quantity'] ?? 0);
                $costPrice = (float)($row['cost_price'] ?? 0);

                if (!$itemId || $qty <= 0) continue;

                $totalVal = $qty * $costPrice;

                // Check if already exists for this item+warehouse
                $exists = $this->db->fetchOne(
                    "SELECT id FROM opening_stock_log WHERE item_id = ? AND warehouse_id = ?",
                    [$itemId, $warehouseId]
                );

                if ($exists) {
                    // Read old quantity BEFORE updating the log
                    $old = $this->db->fetchOne("SELECT quantity FROM opening_stock_log WHERE id=? FOR UPDATE", [$exists['id']]);
                    $diff = $qty - (int)($old['quantity'] ?? 0);

                    // Update log
                    $this->db->execute(
                        "UPDATE opening_stock_log SET quantity=?, cost_price=?, total_value=?, date=?, created_by=? WHERE id=?",
                        [$qty, $costPrice, $totalVal, $date, Auth::id(), $exists['id']]
                    );
                    $this->db->execute(
                        "INSERT INTO stock (item_id, warehouse_id, quantity)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                        [$itemId, $warehouseId, $diff, $diff]
                    );
                } else {
                    // Insert log
                    $this->db->insert(
                        "INSERT INTO opening_stock_log (warehouse_id, item_id, quantity, cost_price, total_value, date, created_by)
                         VALUES (?,?,?,?,?,?,?)",
                        [$warehouseId, $itemId, $qty, $costPrice, $totalVal, $date, Auth::id()]
                    );
                    // Add to stock
                    $this->db->execute(
                        "INSERT INTO stock (item_id, warehouse_id, quantity)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                        [$itemId, $warehouseId, $qty, $qty]
                    );
                    // Update item purchase_price if not already set
                    if ($costPrice > 0) {
                        $this->db->execute(
                            "UPDATE items SET purchase_price = ? WHERE id = ? AND purchase_price = 0",
                            [$costPrice, $itemId]
                        );
                    }
                }

                $saved++;
            }

            $this->db->commit();
            $this->flash('success', "Opening stock saved. {$saved} item(s) updated.");
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error saving opening stock: ' . $e->getMessage());
        }

        $this->redirect('?page=openingstock&warehouse_id=' . $warehouseId);
    }

    public function deleteItem(): void {
        Auth::authorize('settings', 'delete');

        // AUDIT FIX S5: Require POST for destructive action
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=openingstock');
            return;
        }

        $logId       = $this->inputInt('id');
        $warehouseId = $this->inputInt('warehouse_id');

        if ($logId) {
            $log = $this->db->fetchOne("SELECT * FROM opening_stock_log WHERE id = ?", [$logId]);
            if ($log) {
                // Reverse the stock (prevent going negative)
                $this->db->execute(
                    "UPDATE stock SET quantity = GREATEST(0, quantity - ?) WHERE item_id = ? AND warehouse_id = ?",
                    [$log['quantity'], $log['item_id'], $log['warehouse_id']]
                );
                $this->db->execute("DELETE FROM opening_stock_log WHERE id = ?", [$logId]);
                $this->flash('success', 'Item removed from opening stock.');
            }
        }

        $this->redirect('?page=openingstock&warehouse_id=' . $warehouseId);
    }

    // AJAX: search items for autocomplete
    public function searchItems(): void {
        header('Content-Type: application/json');
        $q    = trim($this->input('q', '', 'get'));
        $whId = $this->inputInt('warehouse_id', 0, 'get');
        if (strlen($q) < 1) { echo json_encode([]); return; }

        $like  = "%{$q}%";
        $items = $this->db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.purchase_price, i.unit,
                    COALESCE(s.quantity, 0) as current_stock
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1 AND (i.name LIKE ? OR i.sku LIKE ?)
             ORDER BY i.name ASC LIMIT 15",
            [$whId, $like, $like]
        );
        echo json_encode($items);
    }
}
