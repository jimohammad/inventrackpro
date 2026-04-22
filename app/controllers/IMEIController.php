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
        Auth::authorize('imei', 'view');

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
        Auth::authorize('imei', 'view');
        $imei = $this->input('imei', '', 'get');
        $data = $this->imeiModel->findByIMEI($imei);

        header('Content-Type: application/json');
        echo json_encode($data ?: ['error' => 'Not found']);
    }

    /**
     * Bulk IMEI registration page — scan IMEIs for existing stock
     */
    public function register(): void {
        Auth::authorize('imei', 'add');

        $db = Database::getInstance();
        // Get IMEI-trackable items with current stock in this warehouse
        $whId = Auth::warehouseId();
        $items = $db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.sale_price, COALESCE(s.quantity, 0) as stock,
                    (SELECT COUNT(*) FROM imei_records ir WHERE ir.item_id = i.id AND ir.warehouse_id = ? AND ir.status IN ('in_stock','returned')) as imei_count
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
        Auth::authorize('imei', 'add');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $imei    = trim($this->input('imei'));
        $itemId  = $this->inputInt('item_id');
        $whId    = Auth::warehouseId();

        if (!$imei || !$itemId) {
            echo json_encode(['error' => 'IMEI and item are required']);
            return;
        }

        // Validate IMEI is numeric and minimum length
        if (!ctype_digit($imei)) {
            echo json_encode(['error' => 'IMEI must contain digits only']);
            return;
        }

        $db = Database::getInstance();

        // Get item name to determine required IMEI length
        $itemRow = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
        $itemName = strtolower($itemRow['name'] ?? '');
        $minLen = (strpos($itemName, 'h40') !== false) ? 13 : 15;

        if (strlen($imei) < $minLen) {
            echo json_encode(['error' => "IMEI too short (" . strlen($imei) . " digits) — need at least {$minLen}"]);
            return;
        }

        // Check if IMEI already exists
        $existing = $db->fetchOne("SELECT id, status, item_id, warehouse_id FROM imei_records WHERE imei = ?", [$imei]);
        if ($existing) {
            if ($existing['status'] === 'in_stock') {
                // Already in stock — real duplicate, block it
                $existingItem = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$existing['item_id']]);
                echo json_encode(['error' => "Already in stock — {$existingItem['name']}"]);
                return;
            }

            // Previously sold or transferred — re-stock it
            $db->execute(
                "UPDATE imei_records SET item_id = ?, warehouse_id = ?, status = 'in_stock', notes = 'Re-stocked via bulk scan', updated_at = NOW() WHERE id = ?",
                [$itemId, $whId, $existing['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO imei_records (imei, item_id, warehouse_id, status, notes, created_at) VALUES (?, ?, ?, 'in_stock', 'Bulk scan registration', NOW())",
                [$imei, $itemId, $whId]
            );
        }

        // Get updated count
        $count = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
            [$itemId, $whId]
        );

        echo json_encode(['success' => true, 'imei' => $imei, 'count' => (int)$count['c']]);
    }

    /**
     * Scan IMEIs for a specific purchase invoice (after PO conversion)
     */
    public function scanPurchase(): void {
        Auth::authorize('imei', 'add');

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
        Auth::authorize('imei', 'add');
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
     * AJAX: bulk save IMEIs from paste/notepad for a specific purchase
     */
    public function bulkSavePurchaseImei(): void {
        Auth::authorize('imei', 'add');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $raw        = $this->input('imeis');
        $itemId     = $this->inputInt('item_id');
        $purchaseId = $this->inputInt('purchase_id');

        if (!$raw || !$itemId || !$purchaseId) {
            echo json_encode(['error' => 'imeis, item_id and purchase_id are required']);
            return;
        }

        $db = Database::getInstance();

        $itemRow  = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
        if (!$itemRow) { echo json_encode(['error' => 'Item not found']); return; }
        $minLen = (stripos($itemRow['name'], 'h40') !== false) ? 13 : 15;

        $purchase = $db->fetchOne("SELECT warehouse_id FROM purchases WHERE id = ?", [$purchaseId]);
        if (!$purchase) { echo json_encode(['error' => 'Purchase not found']); return; }
        $whId = $purchase['warehouse_id'] ?? Auth::warehouseId();

        // Parse — split on newlines, commas, semicolons; strip non-digits per token
        $lines = preg_split('/[\r\n,;]+/', $raw);
        $saved   = [];
        $skipped = [];
        $seen    = [];

        $db->beginTransaction();
        try {
            foreach ($lines as $raw_imei) {
                $imei = preg_replace('/\D/', '', trim($raw_imei));
                if (!$imei) continue;

                if (strlen($imei) < $minLen) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Too short (' . strlen($imei) . ' digits, need ' . $minLen . ')'];
                    continue;
                }
                if (!$this->luhn($imei)) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Invalid IMEI (check digit failed)'];
                    continue;
                }
                if (isset($seen[$imei])) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Duplicate in list'];
                    continue;
                }
                $seen[$imei] = true;

                $existing = $db->fetchOne("SELECT id, status FROM imei_records WHERE imei = ?", [$imei]);
                if ($existing) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Already in system (status: ' . $existing['status'] . ')'];
                    continue;
                }

                $db->insert(
                    "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status, created_at) VALUES (?, ?, ?, ?, 'in_stock', NOW())",
                    [$imei, $itemId, $whId, $purchaseId]
                );
                $saved[] = $imei;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('bulkSavePurchaseImei error: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error. Please try again.']);
            return;
        }

        $count = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
            [$purchaseId, $itemId]
        );

        echo json_encode([
            'success' => true,
            'saved'   => count($saved),
            'skipped' => $skipped,
            'total'   => (int)$count['c'],
        ]);
    }

    /**
     * Luhn algorithm — validates IMEI check digit
     */
    private function luhn(string $number): bool {
        $sum  = 0;
        $flip = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $d = (int)$number[$i];
            if ($flip) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d;
            $flip = !$flip;
        }
        return ($sum % 10 === 0);
    }

    /**
     * AJAX: lookup IMEI — returns item info if found (for scan-first sales)
     */
    public function lookupImei(): void {
        header('Content-Type: application/json');

        $imei = trim($this->input('imei', '', 'get'));
        if (!$imei) { echo json_encode(['found' => false]); return; }

        $db = Database::getInstance();

        // Check all statuses so we can give a precise error message
        $row = $db->fetchOne(
            "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                    i.name as item_name, i.sale_price, i.sku, i.has_imei
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ?",
            [$imei]
        );

        if (!$row) {
            echo json_encode([
                'found'    => false,
                'accepted' => true,
                'imei'     => $imei,
                'message'  => 'IMEI not registered — select item',
            ]);
            return;
        }
        if ($row['status'] === 'sold') {
            echo json_encode(['found' => false, 'message' => "Already sold ({$row['item_name']})."]);
            return;
        }
        if (!in_array($row['status'], ['in_stock', 'returned'])) {
            echo json_encode(['found' => false, 'message' => "Not available — status: {$row['status']}"]);
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

    /**
     * IMEI Lifecycle — full timeline for any IMEI
     */
    public function lifecycle(): void {
        // Visible to all logged-in users — no permission required

        $imei   = trim($this->input('imei', '', 'get'));
        $record = null;
        $timeline = [];

        if ($imei) {
            $db = Database::getInstance();

            // Find the IMEI record
            $record = $db->fetchOne(
                "SELECT ir.*, i.name as item_name, i.sku, i.sale_price, i.purchase_price, i.has_imei,
                        w.name as warehouse_name
                 FROM imei_records ir
                 JOIN items i ON i.id = ir.item_id
                 LEFT JOIN warehouses w ON w.id = ir.warehouse_id
                 WHERE ir.imei = ? OR ir.imei2 = ?",
                [$imei, $imei]
            );

            if ($record) {
                $id = $record['id'];
                $itemId = $record['item_id'];

                // 1. Registration / Purchase
                if ($record['purchase_id']) {
                    $purch = $db->fetchOne(
                        "SELECT p.invoice_no, p.date, pa.name as supplier_name, pi.unit_price
                         FROM purchases p
                         LEFT JOIN parties pa ON pa.id = p.party_id
                         LEFT JOIN purchase_items pi ON pi.purchase_id = p.id AND pi.item_id = ?
                         WHERE p.id = ?",
                        [$itemId, $record['purchase_id']]
                    );
                    if ($purch) {
                        $timeline[] = [
                            'date'  => $purch['date'],
                            'icon'  => 'bi-cart-plus',
                            'color' => '#3b82f6',
                            'title' => 'Purchased',
                            'desc'  => "Invoice: {$purch['invoice_no']}<br>Supplier: {$purch['supplier_name']}<br>Cost: " . APP_CURRENCY . " " . number_format($purch['unit_price'] ?? 0, DECIMAL_PLACES),
                            'link'  => "?page=purchases&action=detail&id={$record['purchase_id']}",
                        ];
                    }
                }

                // Registration event
                $timeline[] = [
                    'date'  => $record['created_at'],
                    'icon'  => 'bi-upc-scan',
                    'color' => '#6366f1',
                    'title' => 'Registered in System',
                    'desc'  => "Warehouse: {$record['warehouse_name']}" . ($record['notes'] ? "<br>Note: {$record['notes']}" : ""),
                    'link'  => null,
                ];

                // 2. Sale
                if ($record['sale_id']) {
                    $sale = $db->fetchOne(
                        "SELECT s.invoice_no, s.date, s.grand_total, pa.name as customer_name, si.unit_price
                         FROM sales s
                         LEFT JOIN parties pa ON pa.id = s.party_id
                         LEFT JOIN sale_items si ON si.sale_id = s.id AND si.item_id = ?
                         WHERE s.id = ?",
                        [$itemId, $record['sale_id']]
                    );
                    if ($sale) {
                        $timeline[] = [
                            'date'  => $sale['date'],
                            'icon'  => 'bi-receipt',
                            'color' => '#22c55e',
                            'title' => 'Sold',
                            'desc'  => "Invoice: {$sale['invoice_no']}<br>Customer: {$sale['customer_name']}<br>Price: " . APP_CURRENCY . " " . number_format($sale['unit_price'] ?? 0, DECIMAL_PLACES),
                            'link'  => "?page=sales&action=detail&id={$record['sale_id']}",
                        ];
                    }
                }

                // 3. Returns (check if this IMEI was returned)
                $returns = $db->fetchAll(
                    "SELECT r.return_no, r.date, r.type, pa.name as party_name
                     FROM return_item_imei rii
                     JOIN return_items ri ON ri.id = rii.return_item_id
                     JOIN returns r ON r.id = ri.return_id
                     LEFT JOIN parties pa ON pa.id = r.party_id
                     WHERE rii.imei_id = ?
                     ORDER BY r.date",
                    [$id]
                );
                foreach ($returns as $ret) {
                    $type = $ret['type'] === 'sale_return' ? 'Sale Return' : 'Purchase Return';
                    $timeline[] = [
                        'date'  => $ret['date'],
                        'icon'  => 'bi-arrow-return-left',
                        'color' => '#f59e0b',
                        'title' => $type,
                        'desc'  => "Return: {$ret['return_no']}<br>Party: {$ret['party_name']}",
                        'link'  => null,
                    ];
                }

                // 4. Warranty replacements
                $warranties = $db->fetchAll(
                    "SELECT wr.replacement_no, wr.date, wr.fault_description, wr.status,
                            wr.old_imei, wr.new_imei, pa.name as customer_name,
                            oi.name as old_item_name, ni.name as new_item_name
                     FROM warranty_replacements wr
                     LEFT JOIN parties pa ON pa.id = wr.party_id
                     LEFT JOIN items oi ON oi.id = wr.old_item_id
                     LEFT JOIN items ni ON ni.id = wr.new_item_id
                     WHERE wr.old_imei = ? OR wr.new_imei = ?
                     ORDER BY wr.date",
                    [$imei, $imei]
                );
                foreach ($warranties as $wr) {
                    $role = ($wr['old_imei'] === $imei) ? 'Replaced (defective)' : 'Replacement (new)';
                    $timeline[] = [
                        'date'  => $wr['date'],
                        'icon'  => 'bi-shield-check',
                        'color' => '#dc2626',
                        'title' => "Warranty — {$role}",
                        'desc'  => "Ref: {$wr['replacement_no']}<br>Customer: {$wr['customer_name']}<br>Fault: {$wr['fault_description']}",
                        'link'  => null,
                    ];
                }

                // Sort timeline by date
                usort($timeline, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
            }
        }

        $pageTitle = 'IMEI Lifecycle';
        $page      = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/lifecycle.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
