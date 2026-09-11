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

        // Default to the session branch; other branches only when explicitly requested.
        $filters = [
            'search'       => $this->input('search', '', 'get'),
            'status'       => $this->input('status', '', 'get'),
            'item_id'      => $this->inputInt('item_id', 0, 'get') ?: null,
            'warehouse_id' => isset($_GET['warehouse_id'])
                ? $this->inputInt('warehouse_id', 0, 'get')
                : Auth::warehouseId(),
        ];
        if (empty($filters['item_id'])) {
            unset($filters['item_id']);
        }

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
            // Basic rate limit to slow brute force scanning (atomic, REMOTE_ADDR-keyed).
            if (self::ipRateLimited('imei_track_rl')) {
                http_response_code(429);
                $error = 'Too many lookups. Please try again in a few minutes.';
                include __DIR__ . '/../views/public/imei_track.php';
                return;
            }

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
     * Removed: free-form IMEI Register (scan onto existing stock without a purchase).
     * Serials enter stock via purchase scan, sale return, or Stock Audit register-missing.
     */
    public function register(): void {
        Auth::authorize('imei', 'view');
        $this->flash('error', 'IMEI Register has been removed. Scan serials on the purchase invoice.');
        $this->redirect('?page=purchases');
    }

    /** Removed with IMEI Register. Old scan UI must not write stock serials. */
    public function saveImei(): void {
        Auth::authorize('imei', 'add');
        header('Content-Type: application/json');
        http_response_code(410);
        echo json_encode(['error' => 'IMEI Register has been removed. Scan serials on the purchase invoice.']);
    }

    /**
     * Scan IMEIs for a specific purchase invoice (after PO conversion)
     */
    public function scanPurchase(): void {
        Auth::authorize('imei', 'add');

        $purchaseId = $this->inputInt('purchase_id', 0, 'get');
        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT p.*, pa.name as party_name FROM purchases p LEFT JOIN parties pa ON pa.id = p.party_id
             WHERE p.id = ? AND p.warehouse_id = ?",
            [$purchaseId, Auth::warehouseId()]
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

        // Purchase must belong to the session branch — blocks cross-warehouse IMEI creation.
        $purchase = $db->fetchOne(
            "SELECT warehouse_id FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$purchaseId, Auth::warehouseId()]
        );
        if (!$purchase) { echo json_encode(['error' => 'Purchase not found in this branch']); return; }
        $whId = (int) $purchase['warehouse_id'];

        try {
            $db->beginTransaction();
            $result = $this->imeiModel->attachToPurchase($imei, $itemId, $whId, $purchaseId);
            if (empty($result['ok'])) {
                $db->rollback();
                echo json_encode(['error' => $result['msg'] ?? 'Could not save IMEI.']);
                return;
            }
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            error_log('savePurchaseImei failed: ' . $ex->getMessage());
            echo json_encode(['error' => 'Could not save IMEI. Please retry.']);
            return;
        }

        $count = $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
            [$purchaseId, $itemId]
        );

        echo json_encode([
            'success'   => true,
            'imei'      => $imei,
            'count'     => (int)$count['c'],
            'restocked' => !empty($result['restocked']),
        ]);
    }

    /**
     * AJAX: bulk save IMEIs from paste/notepad for a specific purchase
     */
    public function bulkSavePurchaseImei(): void {
        Auth::authorize('imei', 'add');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $raw        = $this->inputImeiBulk('imeis');
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

        // Purchase must belong to the session branch — blocks cross-warehouse IMEI creation.
        $purchase = $db->fetchOne(
            "SELECT warehouse_id FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$purchaseId, Auth::warehouseId()]
        );
        if (!$purchase) { echo json_encode(['error' => 'Purchase not found in this branch']); return; }
        $whId = (int) $purchase['warehouse_id'];

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

                $attach = $this->imeiModel->attachToPurchase($imei, $itemId, $whId, $purchaseId);
                if (empty($attach['ok'])) {
                    $skipped[] = ['imei' => $imei, 'reason' => $attach['msg'] ?? 'Could not save'];
                    continue;
                }
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

    /** Sales scan bar — imei view or sales add permission. */
    private function authorizeSaleImeiLookup(): void {
        if (!Auth::can('imei', 'view') && !Auth::can('sales', 'add')) {
            Auth::authorize('imei', 'view');
        }
    }

    /**
     * Resolve one IMEI for the scan-first sales flow (shared by single + bulk lookup).
     *
     * @return array<string, mixed>
     */
    private function resolveSaleLookupImei(string $imei): array {
        $db  = Database::getInstance();
        $whId = Auth::warehouseId();
        Item::ensureSerialKindColumn();
        $imei = ImeiFormat::normalize($imei);

        $row = $db->fetchOne(
            "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                    i.name as item_name, i.sale_price, i.sku, i.has_imei,
                    COALESCE(i.serial_kind, 'phone') AS serial_kind,
                    COALESCE(i.max_sale_qty, 0) AS max_sale_qty,
                    COALESCE(c.name, '') AS category_name
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN categories c ON c.id = i.category_id
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
            [$imei, $whId, $whId]
        );

        if (!$row) {
            return [
                'found'    => false,
                'accepted' => true,
                'imei'     => $imei,
                'message'  => 'IMEI not registered — select item',
            ];
        }

        if ($row['status'] === 'sold') {
            $this->imeiModel->validateList([$imei], (int) $row['item_id'], $whId);

            $row = $db->fetchOne(
                "SELECT ir.id, ir.imei, ir.item_id, ir.status, ir.warehouse_id,
                        i.name as item_name, i.sale_price, i.sku, i.has_imei,
                        COALESCE(i.serial_kind, 'phone') AS serial_kind,
                        COALESCE(i.max_sale_qty, 0) AS max_sale_qty,
                        COALESCE(c.name, '') AS category_name
                 FROM imei_records ir
                 JOIN items i ON i.id = ir.item_id
                 LEFT JOIN categories c ON c.id = i.category_id
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
                [$imei, $whId, $whId]
            );
            if (!$row) {
                return ['found' => false, 'message' => 'IMEI not found after refresh.'];
            }
            if ($row['status'] === 'sold') {
                return ['found' => false, 'message' => "Already sold ({$row['item_name']})."];
            }
        }

        if (!in_array($row['status'], ['in_stock', 'returned'], true)) {
            return ['found' => false, 'message' => "Not available — status: {$row['status']}"];
        }

        return [
            'found'        => true,
            'imei'         => $row['imei'],
            'item_id'      => (int) $row['item_id'],
            'item_name'    => $row['item_name'],
            'sale_price'   => $row['sale_price'],
            'sku'          => $row['sku'],
            'has_imei'     => (int) $row['has_imei'],
            'serial_kind'  => ImeiFormat::kindFromItem($row, (string) ($row['item_name'] ?? ''), (string) ($row['category_name'] ?? '')),
            'category_name'=> (string) ($row['category_name'] ?? ''),
            'max_sale_qty' => (int) ($row['max_sale_qty'] ?? 0),
        ];
    }

    /**
     * AJAX: lookup IMEI — returns item info if found (for scan-first sales)
     */
    public function lookupImei(): void {
        $this->authorizeSaleImeiLookup();
        header('Content-Type: application/json');

        $imei = ImeiFormat::normalize($this->input('imei', '', 'get'));
        if ($imei === '') {
            echo json_encode(['found' => false]);
            return;
        }

        echo json_encode($this->resolveSaleLookupImei($imei));
    }

    /**
     * AJAX: bulk IMEI lookup for paste-import (max 50 per request).
     */
    public function lookupImeiBulk(): void {
        $this->authorizeSaleImeiLookup();
        header('Content-Type: application/json');

        $imeis = $this->parseBulkImeiInput();
        if ($imeis === []) {
            echo json_encode(['results' => (object) []]);
            return;
        }

        $results = [];
        foreach ($imeis as $imei) {
            $results[$imei] = $this->resolveSaleLookupImei($imei);
        }

        echo json_encode(['results' => $results]);
    }

    /** @return list<string> */
    private function parseBulkImeiInput(): array {
        $rawList = [];

        $getRaw = trim((string) $this->input('imeis', '', 'get'));
        if ($getRaw !== '') {
            $rawList = preg_split('/[\s,;]+/', strtoupper($getRaw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } elseif ($this->isPost()) {
            $body = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($body['imeis'] ?? null)) {
                foreach ($body['imeis'] as $part) {
                    $part = ImeiFormat::normalize((string) $part);
                    if ($part !== '') {
                        $rawList[] = $part;
                    }
                }
            }
        }

        $unique = [];
        foreach ($rawList as $imei) {
            $imei = ImeiFormat::normalize((string) $imei);
            if ($imei !== '') {
                $unique[$imei] = true;
            }
        }

        return array_slice(array_keys($unique), 0, 50);
    }

    /**
     * IMEI Lifecycle — full timeline for any IMEI
     */
    public function lifecycle(): void {
        // Visible to all logged-in users, but cost/supplier details below are
        // masked unless the user can view purchases.
        $canSeeCost = Auth::can('purchases', 'view');

        $imei   = trim($this->input('imei', '', 'get'));
        $record = null;
        $timeline = [];

        if ($imei) {
            $db = Database::getInstance();

            // Find the IMEI record
            $record = $db->fetchOne(
                "SELECT ir.*, i.name as item_name, i.sku, i.sale_price, i.has_imei,
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

                // desc strings are rendered as raw HTML in the view (they carry <br>),
                // so every DB-sourced value must be escaped at assembly time.
                $e = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

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
                        $purchDesc = "Invoice: {$e($purch['invoice_no'])}";
                        if ($canSeeCost) {
                            $purchDesc .= "<br>Supplier: {$e($purch['supplier_name'])}<br>Cost: " . APP_CURRENCY . " " . number_format($purch['unit_price'] ?? 0, DECIMAL_PLACES);
                        }
                        $timeline[] = [
                            'date'  => $purch['date'],
                            'icon'  => 'bi-cart-plus',
                            'color' => '#3b82f6',
                            'title' => 'Purchased',
                            'desc'  => $purchDesc,
                            'link'  => $canSeeCost ? "?page=purchases&action=detail&id={$record['purchase_id']}" : null,
                        ];
                    }
                }

                // Registration event
                $timeline[] = [
                    'date'  => $record['created_at'],
                    'icon'  => 'bi-upc-scan',
                    'color' => '#6366f1',
                    'title' => 'Registered in System',
                    'desc'  => "Warehouse: {$e($record['warehouse_name'])}" . ($record['notes'] ? "<br>Note: {$e($record['notes'])}" : ""),
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
                        'desc'  => "Invoice: {$e($sl['invoice_no'])}<br>Customer: " . $e($sl['customer_name'] ?? '—') .
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
                            'desc'  => "imei_records.sale_id still points to <strong>{$e($orphanInv)}</strong> ({$e($orphanCust)}) but the line was removed."
                                       . " Lifecycle ignores it. Clear stale sale_id on this IMEI (set sale_id = NULL, status = 'in_stock') or fix via IMEI audit.",
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
                        'desc'  => "Return: {$e($ret['return_no'])}<br>Party: {$e($ret['party_name'])}",
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
                        'desc'  => "Ref: {$e($wr['replacement_no'])}<br>Customer: {$e($wr['customer_name'])}<br>Fault: {$e($wr['fault_description'])}",
                        'link'  => null,
                    ];
                }

                $dumps = [];
                try {
                    $dumps = $db->fetchAll(
                        "SELECT d.id, d.dump_no, d.date, d.status, d.grand_total, p.name AS party_name, di.unit_price
                         FROM device_dump_items di
                         JOIN device_dumps d ON d.id = di.dump_id
                         LEFT JOIN parties p ON p.id = d.party_id
                         WHERE di.imei_id = ?
                         ORDER BY d.date, d.id",
                        [$id]
                    ) ?: [];
                } catch (Throwable $e) {
                    $dumps = [];
                }
                foreach ($dumps as $dp) {
                    $voided = (($dp['status'] ?? '') === 'cancelled') ? ' (voided)' : '';
                    $timeline[] = [
                        'date'  => $dp['date'],
                        'icon'  => 'bi-recycle',
                        'color' => '#c2410c',
                        'title' => 'Dump credit' . $voided,
                        'desc'  => "Ref: {$e($dp['dump_no'])}<br>Party: {$e($dp['party_name'])}<br>Credit: " . APP_CURRENCY . ' ' . number_format((float) ($dp['unit_price'] ?? 0), DECIMAL_PLACES),
                        'link'  => '?page=dumps&action=view&id=' . (int) $dp['id'],
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
        $whId   = (int) $whId;

        require_once __DIR__ . '/../services/StockQuantityService.php';
        StockQuantityService::ensureAdjustmentsSchema($db);

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

            $diag = null;
            if ((int) $item['imei_count'] > (int) $item['stock']) {
                $diag = StockQuantityService::imeiOverDiagnosis($db, $itemId, $whId);
            }
            $pendingWriteOff = StockQuantityService::pendingWriteOffPreview($db, $itemId, $whId);

            $pageTitle = 'Audit: ' . $item['name'];
            $page      = 'imei';

            ob_start();
            if (!empty($diag)) {
                include __DIR__ . '/../views/partials/imei_mismatch_diag.php';
            }
            if (StockQuantityService::writeOffEnabled()
                && (int) ($item['has_imei'] ?? 0) === 1
                && (
                    (int) ($pendingWriteOff['write_off_qty'] ?? 0) > 0
                    || (int) ($pendingWriteOff['stock_qty'] ?? 0) > (int) ($pendingWriteOff['imei_count'] ?? 0)
                )
            ) {
                include __DIR__ . '/../views/partials/imei_pending_writeoff.php';
            }
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
             WHERE i.has_imei = 1
               AND (i.is_active = 1 OR COALESCE(s.quantity, 0) > 0)
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
     * AJAX bulk scan during audit — validate many pasted IMEIs in one request.
     * Returns per-IMEI ok/code/msg using the same rules as auditScan().
     */
    public function auditScanBulk(): void {
        header('Content-Type: application/json');

        if (!Auth::isAdmin()) {
            echo json_encode(['ok' => false, 'msg' => 'Admin only.']);
            return;
        }
        if (!$this->isPost()) {
            echo json_encode(['ok' => false, 'msg' => 'POST required.']);
            return;
        }

        $itemId = $this->inputInt('item_id', 0);
        $whId   = Auth::warehouseId();
        $raw    = $this->inputImeiBulk('imeis');

        if ($itemId <= 0 || $raw === '') {
            echo json_encode(['ok' => false, 'msg' => 'Missing parameters.']);
            return;
        }

        $parts = preg_split('/[\r\n,;\s]+/', strtoupper($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $unique = [];
        foreach ($parts as $imei) {
            $imei = trim($imei);
            if ($imei === '' || !preg_match('/^[A-Z0-9\/\-]+$/i', $imei)) {
                continue;
            }
            $unique[$imei] = true;
        }
        $imeis = array_slice(array_keys($unique), 0, 500);

        if ($imeis === []) {
            echo json_encode(['ok' => false, 'msg' => 'No valid IMEIs in paste.']);
            return;
        }

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($imeis), '?'));
        $rows = $db->fetchAll(
            "SELECT id, imei, status, warehouse_id, item_id FROM imei_records WHERE imei IN ($placeholders)",
            $imeis
        );
        $byImei = [];
        foreach ($rows as $row) {
            $byImei[strtoupper((string) $row['imei'])] = $row;
        }

        $results = [];
        $matched = 0;
        foreach ($imeis as $imei) {
            $rec = $byImei[$imei] ?? null;
            if (!$rec) {
                $results[] = ['imei' => $imei, 'ok' => false, 'code' => 'not_found', 'msg' => 'Not in system'];
                continue;
            }
            if ((int) $rec['item_id'] !== $itemId) {
                $results[] = ['imei' => $imei, 'ok' => false, 'code' => 'wrong_item', 'msg' => 'Different item'];
                continue;
            }
            if (!in_array($rec['status'], ['in_stock', 'returned'], true)) {
                $results[] = ['imei' => $imei, 'ok' => false, 'code' => 'wrong_status', 'msg' => 'Status: ' . $rec['status']];
                continue;
            }
            if ((int) $rec['warehouse_id'] !== $whId) {
                $results[] = ['imei' => $imei, 'ok' => false, 'code' => 'wrong_wh', 'msg' => 'Different warehouse'];
                continue;
            }
            $matched++;
            $results[] = ['imei' => $imei, 'ok' => true, 'id' => (int) $rec['id'], 'msg' => '✓'];
        }

        echo json_encode([
            'ok'      => true,
            'matched' => $matched,
            'total'   => count($imeis),
            'results' => $results,
        ]);
    }

    /**
     * Removed with IMEI Register. Old Clear buttons on that page must not wipe serials.
     */
    public function clearItemImeis(): void {
        Auth::authorize('imei', 'add');
        header('Content-Type: application/json');
        http_response_code(410);
        echo json_encode(['ok' => false, 'msg' => 'IMEI Register has been removed.']);
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
     * AJAX: register multiple missing IMEIs during audit paste (physical phones not yet in system).
     */
    public function auditRegisterBulk(): void {
        header('Content-Type: application/json');

        if (!Auth::isAdmin()) {
            echo json_encode(['ok' => false, 'msg' => 'Admin only.']);
            return;
        }
        if (!$this->isPost()) {
            echo json_encode(['ok' => false, 'msg' => 'POST required.']);
            return;
        }

        $itemId = $this->inputInt('item_id', 0);
        $whId   = Auth::warehouseId();
        $raw    = $this->inputImeiBulk('imeis');

        if ($itemId <= 0 || $raw === '') {
            echo json_encode(['ok' => false, 'msg' => 'Missing parameters.']);
            return;
        }

        $parts = preg_split('/[\r\n,;\s]+/', strtoupper($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $unique = [];
        foreach ($parts as $imei) {
            $imei = trim($imei);
            if ($imei === '' || !preg_match('/^[A-Z0-9\/\-]+$/i', $imei)) {
                continue;
            }
            $unique[$imei] = true;
        }
        $imeis = array_slice(array_keys($unique), 0, 100);

        if ($imeis === []) {
            echo json_encode(['ok' => false, 'msg' => 'No valid IMEIs to register.']);
            return;
        }

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($imeis), '?'));
        $existingRows = $db->fetchAll(
            "SELECT imei, status FROM imei_records WHERE imei IN ($placeholders)",
            $imeis
        );
        $existing = [];
        foreach ($existingRows as $row) {
            $existing[strtoupper((string) $row['imei'])] = (string) $row['status'];
        }

        $registered = [];
        $skipped = [];
        $note = 'Registered during stock audit on ' . date('Y-m-d H:i');

        $db->beginTransaction();
        try {
            foreach ($imeis as $imei) {
                if (isset($existing[$imei])) {
                    $skipped[] = ['imei' => $imei, 'msg' => 'Already exists (status: ' . $existing[$imei] . ')'];
                    continue;
                }
                $newId = $db->insert(
                    "INSERT INTO imei_records (imei, item_id, warehouse_id, status, notes, created_at)
                     VALUES (?, ?, ?, 'in_stock', ?, NOW())",
                    [$imei, $itemId, $whId, $note]
                );
                $registered[] = ['imei' => $imei, 'id' => (int) $newId];
                $existing[$imei] = 'in_stock';
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('auditRegisterBulk failed: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Failed to register IMEIs. Please retry.']);
            return;
        }

        if ($registered !== []) {
            $this->logActivity(
                'audit_register_imei_bulk',
                'imei_records',
                $itemId,
                'Audit-registered ' . count($registered) . ' IMEI(s) for item #' . $itemId
            );
        }

        echo json_encode([
            'ok'         => true,
            'registered' => count($registered),
            'results'    => $registered,
            'skipped'    => $skipped,
            'msg'        => count($registered) . ' IMEI(s) registered',
        ]);
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
        // Bulk scan lists easily exceed the default 1000-char input cap; a silent
        // truncation here would wrongly mark real stock as 'transferred'.
        $scannedRaw   = $this->inputImeiBulk('scanned_imeis');
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

    /**
     * Admin: write off book qty that is higher than scanned IMEIs (physically missing units).
     */
    public function auditWriteOffPending(): void {
        require_once __DIR__ . '/../services/StockQuantityService.php';
        if (!StockQuantityService::writeOffEnabled()) {
            $this->flash('error', 'Stock qty write-off is temporarily disabled.');
            $itemId = $this->inputInt('item_id');
            $this->redirect($itemId > 0 ? ('?page=imei&action=audit&item_id=' . $itemId) : '?page=imei&action=audit');
            return;
        }
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=imei');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=imei&action=audit');
            return;
        }

        $itemId = $this->inputInt('item_id');
        $whId   = (int) Auth::warehouseId();
        $notes  = trim($this->input('notes', ''));

        if ($itemId <= 0 || $whId <= 0) {
            $this->flash('error', 'Item and warehouse are required.');
            $this->redirect('?page=imei&action=audit');
            return;
        }

        $db = Database::getInstance();
        StockQuantityService::ensureAdjustmentsSchema($db);

        $db->beginTransaction();
        try {
            $result = StockQuantityService::writeOffImeiPending(
                $db,
                $itemId,
                $whId,
                Auth::id(),
                $notes
            );
            $db->commit();
            self::clearDashboardCache($whId);

            $this->logActivity(
                'stock_writeoff_imei_pending',
                'stock',
                $itemId,
                'Wrote off ' . (int) $result['written_off']
                . ' missing unit(s) for item #' . $itemId
                . ' warehouse #' . $whId
                . ' qty ' . (int) $result['before'] . ' → ' . (int) $result['after']
            );

            if (!empty($result['rebuilt_only'])) {
                $this->flash(
                    'success',
                    'Stock qty was stale. Rebuilt to ' . (int) $result['after']
                    . ' to match documents / IMEIs. No write-off row needed.'
                );
            } else {
                $this->flash(
                    'success',
                    'Wrote off ' . (int) $result['written_off'] . ' missing unit(s). '
                    . 'Qty is now ' . (int) $result['after'] . ' (matches scanned IMEIs).'
                );
            }
        } catch (Exception $e) {
            $db->rollbackQuiet();
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('?page=imei&action=audit&item_id=' . $itemId);
    }
}
