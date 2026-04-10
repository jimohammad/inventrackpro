<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../models/Item.php';

class IMEIController extends BaseController {
    private IMEI $imeiModel;
    private Item $itemModel;

    public function __construct() {
        parent::__construct();
        $this->imeiModel = new IMEI();
        $this->itemModel = new Item();
    }

    public function index(): void {
        Auth::authorize('inventory', 'view');

        $filters = [
            'search'       => $this->input('search', '', 'get'),
            'status'       => $this->input('status', '', 'get'),
            'warehouse_id' => $this->inputInt('warehouse_id', 0, 'get'),
        ];

        $imeis      = $this->imeiModel->getAll($filters);
        $warehouses = $this->itemModel->getWarehouses();
        $pageTitle  = 'IMEI History';
        $page       = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function detail(): void {
        Auth::authorize('inventory', 'view');
        $imei = $this->input('imei', '', 'get');
        $data = $this->imeiModel->findByIMEI($imei);

        header('Content-Type: application/json');
        echo json_encode($data ?: ['error' => 'Not found']);
    }

    /**
     * Bulk IMEI registration page — scan IMEIs for existing stock
     */
    public function register(): void {
        Auth::authorize('inventory', 'add');

        $db = Database::getInstance();
        // Get IMEI-trackable items with current stock in this warehouse
        $whId = Auth::warehouseId();
        $items = $db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.sale_price, COALESCE(s.quantity, 0) as stock,
                    (SELECT COUNT(*) FROM imei_records ir WHERE ir.item_id = i.id AND ir.warehouse_id = ? AND ir.status = 'in_stock') as imei_count
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1 AND i.has_imei = 1
             ORDER BY i.name",
            [$whId, $whId]
        );

        $pageTitle = 'Register IMEI - Stock';
        $page      = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/register.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * AJAX: save a single scanned IMEI
     */
    public function saveImei(): void {
        Auth::authorize('inventory', 'add');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $imei    = trim($this->input('imei'));
        $itemId  = $this->inputInt('item_id');
        $whId    = Auth::warehouseId();

        if (!$imei || !$itemId) {
            echo json_encode(['error' => 'IMEI and item are required']);
            return;
        }

        $db = Database::getInstance();

        // Check if IMEI already exists
        $existing = $db->fetchOne("SELECT id, status, item_id FROM imei_records WHERE imei = ?", [$imei]);
        if ($existing) {
            $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$existing['item_id']]);
            echo json_encode(['error' => "IMEI already registered — {$itemName['name']} ({$existing['status']})"]);
            return;
        }

        $db->insert(
            "INSERT INTO imei_records (imei, item_id, warehouse_id, status, notes, created_at) VALUES (?, ?, ?, 'in_stock', 'Bulk scan registration', NOW())",
            [$imei, $itemId, $whId]
        );

        // Get updated count
        $count = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE item_id = ? AND warehouse_id = ? AND status = 'in_stock'",
            [$itemId, $whId]
        );

        echo json_encode(['success' => true, 'imei' => $imei, 'count' => (int)$count['c']]);
    }

    /**
     * Scan IMEIs for a specific purchase invoice (after PO conversion)
     */
    public function scanPurchase(): void {
        Auth::authorize('inventory', 'add');

        $purchaseId = $this->inputInt('purchase_id', 0, 'get');
        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT p.*, pa.name as party_name FROM purchases p LEFT JOIN parties pa ON pa.id = p.party_id WHERE p.id = ?",
            [$purchaseId]
        );
        if (!$purchase) { $this->flash('error', 'Purchase not found.'); $this->redirect('?page=purchases'); return; }

        $items = $db->fetchAll(
            "SELECT pi.item_id, pi.quantity, i.name, i.sku, i.has_imei,
                    (SELECT COUNT(*) FROM imei_records ir WHERE ir.purchase_id = ? AND ir.item_id = pi.item_id) as imei_count
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.purchase_id = ? AND i.has_imei = 1",
            [$purchaseId, $purchaseId]
        );

        $pageTitle = 'Scan IMEIs — ' . $purchase['invoice_no'];
        $page      = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/scan_purchase.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * AJAX: save IMEI for a specific purchase
     */
    public function savePurchaseImei(): void {
        Auth::authorize('inventory', 'add');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $imei       = trim($this->input('imei'));
        $itemId     = $this->inputInt('item_id');
        $purchaseId = $this->inputInt('purchase_id');

        if (!$imei || !$itemId || !$purchaseId) {
            echo json_encode(['error' => 'IMEI, item, and purchase are required']);
            return;
        }

        $db = Database::getInstance();

        $existing = $db->fetchOne("SELECT id, status, item_id FROM imei_records WHERE imei = ?", [$imei]);
        if ($existing) {
            $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$existing['item_id']]);
            echo json_encode(['error' => "IMEI already exists — {$itemName['name']} ({$existing['status']})"]);
            return;
        }

        $purchase = $db->fetchOne("SELECT warehouse_id FROM purchases WHERE id = ?", [$purchaseId]);
        $whId = $purchase['warehouse_id'] ?? Auth::warehouseId();

        $db->insert(
            "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status, created_at) VALUES (?, ?, ?, ?, 'in_stock', NOW())",
            [$imei, $itemId, $whId, $purchaseId]
        );

        $count = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
            [$purchaseId, $itemId]
        );

        echo json_encode(['success' => true, 'imei' => $imei, 'count' => (int)$count['c']]);
    }

    /**
     * AJAX: lookup IMEI — returns item info if found (for scan-first sales)
     */
    public function lookupImei(): void {
        header('Content-Type: application/json');

        $imei = trim($this->input('imei', '', 'get'));
        if (!$imei) { echo json_encode(['found' => false]); return; }

        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                    i.name as item_name, i.sale_price, i.sku, i.has_imei
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ? AND ir.status = 'in_stock'",
            [$imei]
        );

        if (!$row) {
            echo json_encode(['found' => false, 'message' => 'IMEI not found or not in stock']);
            return;
        }

        echo json_encode([
            'found'      => true,
            'imei'       => $row['imei'],
            'item_id'    => (int)$row['item_id'],
            'item_name'  => $row['item_name'],
            'sale_price' => $row['sale_price'],
            'sku'        => $row['sku'],
            'has_imei'   => (int)$row['has_imei'],
        ]);
    }
}
