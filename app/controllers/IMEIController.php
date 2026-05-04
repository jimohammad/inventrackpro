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
        if (($_GET['page'] ?? '') === 'imeitrack') {
            $this->publicTrack();
            return;
        }
        Auth::authorize('imei', 'view');

        $filters = [
            'search'       => $this->input('search', '', 'get'),
            'status'       => $this->input('status', '', 'get'),
            'warehouse_id' => $this->inputInt('warehouse_id', 0, 'get'),
        ];

        $imeis      = $this->imeiModel->getAll($filters);
        $warehouses = self::getWarehouses();
        $pageTitle  = 'IMEI History';
        $page       = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Public IMEI warranty tracking — no login required.
     * Shows only sold date and remaining warranty period (13 months).
     */
    public function publicTrack(): void {
        $imei = strtoupper(trim($this->input('imei', '', 'get')));
        $saleDate = null;
        $remainingText = null;
        $error = null;
        $isExpired = false;
        $warrantyMonths = 13;

        if ($imei !== '') {
            // Basic rate limit to slow brute force scanning.
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $cacheDir = sys_get_temp_dir() . '/imei_track_rl';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0700, true);
            }
            $rlFile = $cacheDir . '/' . md5($ip);
            $now = time();
            $hits = file_exists($rlFile) ? (array) @json_decode((string) file_get_contents($rlFile), true) : [];
            $hits = array_filter($hits, static fn($t) => $t > $now - 300);
            if (count($hits) >= 60) {
                http_response_code(429);
                $error = 'Too many lookups. Please try again in a few minutes.';
                include __DIR__ . '/../views/public/imei_track.php';
                return;
            }
            $hits[] = $now;
            @file_put_contents($rlFile, json_encode(array_values($hits)));

            if (!preg_match('/^[A-Z0-9\\/\\-]{6,20}$/', $imei)) {
                $error = 'Please enter a valid IMEI / serial number.';
                include __DIR__ . '/../views/public/imei_track.php';
                return;
            }

            $db = Database::getInstance();
            $sale = $db->fetchOne(
                "SELECT s.date
                 FROM sale_item_imei sii
                 JOIN imei_records ir ON ir.id = sii.imei_id
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN sales s ON s.id = si.sale_id
                 WHERE (ir.imei = ? OR ir.imei2 = ?)
                   AND s.status != 'cancelled'
                 ORDER BY s.date DESC, s.id DESC
                 LIMIT 1",
                [$imei, $imei]
            );

            if (!$sale || empty($sale['date'])) {
                $error = 'No sold record found for this IMEI.';
                include __DIR__ . '/../views/public/imei_track.php';
                return;
            }

            $saleDate = $sale['date'];
            $soldAt = new DateTimeImmutable($saleDate);
            $warrantyEnd = $soldAt->modify('+' . $warrantyMonths . ' months');
            $today = new DateTimeImmutable('today');

            if ($today > $warrantyEnd) {
                $isExpired = true;
                $remainingText = 'Expired';
            } else {
                $diff = $today->diff($warrantyEnd);
                $months = ($diff->y * 12) + $diff->m;
                $days = (int) $diff->d;
                $remainingText = $months . ' month' . ($months === 1 ? '' : 's')
                    . ' ' . $days . ' day' . ($days === 1 ? '' : 's');
            }
        }

        include __DIR__ . '/../views/public/imei_track.php';
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

        $imei    = strtoupper(trim($this->input('imei')));
        $itemId  = $this->inputInt('item_id');
        $whId    = Auth::warehouseId();

        if (!$imei || !$itemId) {
            echo json_encode(['error' => 'IMEI and item are required']);
            return;
        }

        // Allow phone IMEIs (digits only) and laptop/device serials (alphanumeric + / -)
        if (!preg_match('/^[A-Z0-9\\/\\-]+$/i', $imei)) {
            echo json_encode(['error' => 'Serial contains invalid characters']);
            return;
        }

        $db = Database::getInstance();

        // Min length: 6 for serials, 13-15 for numeric IMEIs
        $isNumeric = ctype_digit($imei);
        $itemRow  = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
        $itemName = strtolower($itemRow['name'] ?? '');
        $minLen   = $isNumeric ? ((strpos($itemName, 'h40') !== false) ? 13 : 15) : 6;

        if (strlen($imei) < $minLen) {
            echo json_encode(['error' => "Serial too short (" . strlen($imei) . " chars) — need at least {$minLen}"]);
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
                $imei = strtoupper(trim($raw_imei));
                // For pure-digit IMEIs strip whitespace only; for alphanumeric serials keep as-is
                if (ctype_digit(str_replace([' '], '', $imei))) {
                    $imei = preg_replace('/\s/', '', $imei);
                }
                if (!$imei || !preg_match('/^[A-Z0-9\\/\\-]+$/', $imei)) continue;

                $isNumericSerial = ctype_digit($imei);
                $serialMinLen    = $isNumericSerial ? $minLen : 6;

                if (strlen($imei) < $serialMinLen) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Too short (' . strlen($imei) . ' chars, need ' . $serialMinLen . ')'];
                    continue;
                }
                if ($isNumericSerial && !$this->luhn($imei)) {
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

        $imei = strtoupper(trim($this->input('imei', '', 'get')));
        if (!$imei) { echo json_encode(['found' => false]); return; }

        $db = Database::getInstance();

        // Prefer sellable rows first (current warehouse in_stock/returned, then other warehouses).
        $row = $db->fetchOne(
            "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                    i.name as item_name, i.sale_price, i.sku, i.has_imei
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ?
             ORDER BY
                CASE
                    WHEN ir.warehouse_id = ? AND ir.status IN ('in_stock','returned') THEN 0
                    WHEN ir.status IN ('in_stock','returned') THEN 1
                    WHEN ir.warehouse_id = ? THEN 2
                    ELSE 3
                END,
                ir.id DESC
             LIMIT 1",
            [$imei, Auth::warehouseId(), Auth::warehouseId()]
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
            // Reuse sale validator auto-heal logic for stale sold rows (returned but status not released).
            $this->imeiModel->validateList([$imei], (int)$row['item_id'], Auth::warehouseId());

            $row = $db->fetchOne(
                "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                        i.name as item_name, i.sale_price, i.sku, i.has_imei
                 FROM imei_records ir
                 JOIN items i ON i.id = ir.item_id
                 WHERE ir.imei = ?
                 ORDER BY
                    CASE
                        WHEN ir.warehouse_id = ? AND ir.status IN ('in_stock','returned') THEN 0
                        WHEN ir.status IN ('in_stock','returned') THEN 1
                        WHEN ir.warehouse_id = ? THEN 2
                        ELSE 3
                    END,
                    ir.id DESC
                 LIMIT 1",
                [$imei, Auth::warehouseId(), Auth::warehouseId()]
            );
            if (!$row) {
                echo json_encode(['found' => false, 'message' => 'IMEI not found after refresh.']);
                return;
            }
            if ($row['status'] === 'sold') {
                echo json_encode(['found' => false, 'message' => "Already sold ({$row['item_name']})."]);
                return;
            }
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

                // 2. Sale events — read from sale_item_imei (real link), not imei_records.sale_id.
                // imei_records.sale_id can lag behind reality when a sale line was deleted/edited.
                // Each surviving link to a non-cancelled sale is a real "Sold" event.
                $saleLinks = $db->fetchAll(
                    "SELECT s.id AS sale_id, s.invoice_no, s.date, s.status,
                            pa.name AS customer_name, si.unit_price
                     FROM sale_item_imei sii
                     JOIN sale_items si ON si.id = sii.sale_item_id
                     JOIN sales s ON s.id = si.sale_id
                     LEFT JOIN parties pa ON pa.id = s.party_id
                     WHERE sii.imei_id = ?
                     ORDER BY s.date ASC, s.id ASC",
                    [$id]
                );
                foreach ($saleLinks as $sl) {
                    $isCancelled = ($sl['status'] ?? '') === 'cancelled';
                    $title = $isCancelled ? 'Sold (voided invoice)' : 'Sold';
                    $color = $isCancelled ? '#94a3b8' : '#22c55e';
                    $timeline[] = [
                        'date'  => $sl['date'],
                        'icon'  => 'bi-receipt',
                        'color' => $color,
                        'title' => $title,
                        'desc'  => "Invoice: {$sl['invoice_no']}<br>Customer: " . ($sl['customer_name'] ?? '—') .
                                   "<br>Price: " . APP_CURRENCY . " " . number_format($sl['unit_price'] ?? 0, DECIMAL_PLACES),
                        'link'  => "?page=sales&action=detail&id={$sl['sale_id']}",
                    ];
                }

                // Stale-link warning: imei_records.sale_id points somewhere but no real link exists.
                // Caused by older edits that deleted a sale line without releasing the IMEI.
                if ($record['sale_id']) {
                    $stillLinked = $db->fetchOne(
                        "SELECT 1 AS ok FROM sale_item_imei sii
                         JOIN sale_items si ON si.id = sii.sale_item_id
                         WHERE sii.imei_id = ? AND si.sale_id = ?
                         LIMIT 1",
                        [$id, $record['sale_id']]
                    );
                    if (!$stillLinked) {
                        $orphanSale = $db->fetchOne(
                            "SELECT s.invoice_no, s.status, s.date, pa.name AS customer_name
                             FROM sales s LEFT JOIN parties pa ON pa.id = s.party_id WHERE s.id = ?",
                            [$record['sale_id']]
                        );
                        $orphanInv  = $orphanSale['invoice_no'] ?? ('#' . (int)$record['sale_id']);
                        $orphanCust = $orphanSale['customer_name'] ?? '—';
                        $timeline[] = [
                            'date'  => $orphanSale['date'] ?? $record['updated_at'] ?? $record['created_at'],
                            'icon'  => 'bi-exclamation-triangle',
                            'color' => '#dc2626',
                            'title' => 'Stale sale link (data fix needed)',
                            'desc'  => "imei_records.sale_id still points to <strong>{$orphanInv}</strong> ({$orphanCust}) but the line was removed."
                                       . " Lifecycle ignores it. Run database/heal_orphan_imei_sale_id.sql to clean status and sale_id.",
                            'link'  => null,
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

    /**
     * Stock Audit — admin tool to reconcile IMEI count vs stock count.
     * Lists items where IMEI != stock for current warehouse.
     * Admin opens an item, scans physically present phones, system marks
     * unscanned IMEIs as 'transferred' so counts realign.
     */
    public function audit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=imei');
            return;
        }

        $db   = Database::getInstance();
        $whId = Auth::warehouseId();

        $itemId = $this->inputInt('item_id', 0, 'get');

        // Single-item reconciliation page
        if ($itemId) {
            $item = $db->fetchOne(
                "SELECT i.id, i.name, i.sku, i.has_imei,
                        COALESCE(s.quantity, 0) as stock,
                        (SELECT COUNT(*) FROM imei_records ir
                         WHERE ir.item_id = i.id AND ir.warehouse_id = ? AND ir.status IN ('in_stock','returned')) as imei_count
                 FROM items i
                 LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
                 WHERE i.id = ?",
                [$whId, $whId, $itemId]
            );
            if (!$item) { $this->flash('error', 'Item not found.'); $this->redirect('?page=imei&action=audit'); return; }

            $imeis = $db->fetchAll(
                "SELECT id, imei, status, updated_at
                 FROM imei_records
                 WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')
                 ORDER BY id ASC",
                [$itemId, $whId]
            );

            $pageTitle = 'Audit: ' . $item['name'];
            $page      = 'imei';

            ob_start();
            include __DIR__ . '/../views/imei/audit_item.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
            return;
        }

        // List all items with IMEI != stock mismatch in this warehouse
        $mismatches = $db->fetchAll(
            "SELECT i.id, i.name, i.sku,
                    COALESCE(s.quantity, 0) as stock,
                    (SELECT COUNT(*) FROM imei_records ir
                     WHERE ir.item_id = i.id AND ir.warehouse_id = ? AND ir.status IN ('in_stock','returned')) as imei_count
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1 AND i.has_imei = 1
             HAVING imei_count != stock
             ORDER BY ABS(imei_count - stock) DESC, i.name ASC",
            [$whId, $whId]
        );

        $warehouses = self::getWarehouses();
        $currentWh  = null;
        foreach ($warehouses as $w) { if ((int)$w['id'] === $whId) { $currentWh = $w; break; } }

        $pageTitle = 'Stock Audit — IMEI Reconciliation';
        $page      = 'imei';

        ob_start();
        include __DIR__ . '/../views/imei/audit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * AJAX scan during audit — returns whether IMEI is in this warehouse's in_stock pool
     */
    public function auditScan(): void {
        if (!Auth::isAdmin()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Admin only.']);
            return;
        }
        header('Content-Type: application/json');

        $imei   = trim($this->input('imei', '', 'get'));
        $itemId = (int) $this->input('item_id', 0, 'get');
        $whId   = Auth::warehouseId();

        if (!$imei || !$itemId) { echo json_encode(['ok' => false, 'msg' => 'Missing parameters.']); return; }

        $db  = Database::getInstance();
        $rec = $db->fetchOne(
            "SELECT id, status, warehouse_id, item_id FROM imei_records WHERE imei = ?",
            [$imei]
        );

        if (!$rec)                                                { echo json_encode(['ok' => false, 'code' => 'not_found', 'msg' => 'Not in system']); return; }
        if ((int)$rec['item_id'] !== $itemId)                     { echo json_encode(['ok' => false, 'code' => 'wrong_item', 'msg' => 'Different item']); return; }
        if (!in_array($rec['status'], ['in_stock','returned']))   { echo json_encode(['ok' => false, 'code' => 'wrong_status', 'msg' => 'Status: ' . $rec['status']]); return; }
        if ((int)$rec['warehouse_id'] !== $whId)                  { echo json_encode(['ok' => false, 'code' => 'wrong_wh', 'msg' => 'Different warehouse']); return; }

        echo json_encode(['ok' => true, 'id' => (int)$rec['id'], 'msg' => '✓']);
    }

    /**
     * Admin: Delete all in_stock + returned IMEI records for an item in current warehouse.
     * Use to wipe and re-scan from scratch. Sold/transferred/defective records are preserved (history).
     */
    public function clearItemImeis(): void {
        if (!Auth::isAdmin()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Admin only.']);
            return;
        }
        if (!$this->isPost()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'POST required.']);
            return;
        }
        header('Content-Type: application/json');

        $itemId = $this->inputInt('item_id');
        $whId   = Auth::warehouseId();
        if (!$itemId) { echo json_encode(['ok' => false, 'msg' => 'Missing item_id.']); return; }

        $db = Database::getInstance();

        // Count first for the response
        $row = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records
             WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
            [$itemId, $whId]
        );
        $count = (int)($row['c'] ?? 0);
        if ($count === 0) { echo json_encode(['ok' => true, 'deleted' => 0, 'msg' => 'Nothing to delete.']); return; }

        try {
            $db->execute(
                "DELETE FROM imei_records
                 WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
                [$itemId, $whId]
            );
            $this->logActivity('clear_imeis', 'imei_records', $itemId,
                "Cleared {$count} in_stock/returned IMEIs for item #{$itemId} in warehouse #{$whId}");
            echo json_encode(['ok' => true, 'deleted' => $count, 'msg' => "Deleted {$count} IMEI(s)."]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Register a new IMEI inline during audit (when physical phone is found but IMEI not in system).
     */
    public function auditRegister(): void {
        if (!Auth::isAdmin()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Admin only.']);
            return;
        }
        header('Content-Type: application/json');

        $imei   = trim($this->input('imei'));
        $itemId = (int) $this->input('item_id');
        $whId   = Auth::warehouseId();

        if (!$imei || !$itemId) { echo json_encode(['ok' => false, 'msg' => 'Missing parameters.']); return; }
        if (!preg_match('/^[A-Z0-9\\/\\-]+$/i', $imei)) { echo json_encode(['ok' => false, 'msg' => 'Invalid characters.']); return; }

        $db = Database::getInstance();

        // Block if IMEI already exists anywhere
        $existing = $db->fetchOne("SELECT id, status, item_id, warehouse_id FROM imei_records WHERE imei = ?", [$imei]);
        if ($existing) {
            echo json_encode(['ok' => false, 'msg' => 'IMEI already exists in system (status: ' . $existing['status'] . ').']);
            return;
        }

        $db->beginTransaction();
        try {
            $newId = $db->insert(
                "INSERT INTO imei_records (imei, item_id, warehouse_id, status, notes, created_at)
                 VALUES (?, ?, ?, 'in_stock', ?, NOW())",
                [$imei, $itemId, $whId, 'Registered during stock audit on ' . date('Y-m-d H:i')]
            );
            $db->commit();
            $this->logActivity('audit_register_imei', 'imei_records', $newId, "Audit-registered IMEI {$imei} for item #{$itemId}");
            echo json_encode(['ok' => true, 'id' => $newId, 'imei' => $imei, 'msg' => '✓ Registered']);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'msg' => 'Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit audit — mark all unscanned IMEIs (for this item+warehouse) as 'transferred'.
     */
    public function auditSubmit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=imei');
            return;
        }
        if (!$this->isPost()) { $this->redirect('?page=imei&action=audit'); return; }

        $itemId       = $this->inputInt('item_id');
        $whId         = Auth::warehouseId();
        $scannedRaw   = $this->input('scanned_imeis');
        $scanned      = array_values(array_filter(array_map('trim', explode("\n", $scannedRaw))));

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Get all current in_stock IMEIs for this item+warehouse
            $current = $db->fetchAll(
                "SELECT id, imei FROM imei_records
                 WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
                [$itemId, $whId]
            );

            $scannedSet = array_flip($scanned);
            $toMark = [];
            foreach ($current as $row) {
                if (!isset($scannedSet[$row['imei']])) {
                    $toMark[] = (int)$row['id'];
                }
            }

            $markedCount = 0;
            if (!empty($toMark)) {
                $placeholders = implode(',', array_fill(0, count($toMark), '?'));
                $note = 'Auto-marked transferred during stock audit on ' . date('Y-m-d H:i') . ' by user ' . (Auth::id() ?: 'admin');
                $db->execute(
                    "UPDATE imei_records
                     SET status = 'transferred',
                         notes = CONCAT_WS(' | ', notes, ?)
                     WHERE id IN ($placeholders)",
                    array_merge([$note], $toMark)
                );
                $markedCount = count($toMark);
            }

            $db->commit();
            $this->logActivity('audit_imei', 'imei_records', $itemId,
                "Audit item #{$itemId}: scanned " . count($scanned) . ", marked {$markedCount} as transferred");
            $this->flash('success', "Audit complete. Scanned: " . count($scanned) . ". Marked transferred: {$markedCount}.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Audit failed: ' . $e->getMessage());
        }

        $this->redirect('?page=imei&action=audit');
    }
}
