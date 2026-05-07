<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Return.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Sale.php';

class ReturnController extends BaseController {
    private SaleReturn $returnModel;
    private Party      $partyModel;
    private Item       $itemModel;
    private Sale       $saleModel;
    private string     $debugLogPath;

    public function __construct() {
        parent::__construct();
        $this->returnModel = new SaleReturn();
        $this->partyModel  = new Party();
        $this->itemModel   = new Item();
        $this->saleModel   = new Sale();
        // Debug logging is opt-in via environment flag to avoid writing logs in production.
        $this->debugLogPath = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'erp-returns-debug.log';
    }

    private function debugLog(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void {
        if (!getenv('ERP_DEBUG_RETURNS')) {
            return;
        }
        $payload = [
            'sessionId'    => substr((string) session_id(), 0, 12),
            'runId'        => $runId,
            'hypothesisId' => $hypothesisId,
            'location'     => $location,
            'message'      => $message,
            'data'         => $data,
            'timestamp'    => (int) round(microtime(true) * 1000),
        ];
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($line !== false) {
            @file_put_contents($this->debugLogPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    private function parsePostedImeis($rawImeis): array {
        if (!is_string($rawImeis) || $rawImeis === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $rawImeis);
        if (!is_array($lines)) {
            return [];
        }
        $out = [];
        foreach ($lines as $line) {
            $imei = trim((string)$line);
            if ($imei !== '') {
                $out[] = $imei;
            }
        }
        return array_values(array_unique($out));
    }

    public function index(): void {
        Auth::authorize('returns', 'view');
        $filters = [
            'from_date' => $this->input('from_date', date('Y-m-01'), 'get'),
            'to_date'   => $this->input('to_date', date('Y-m-d'), 'get'),
            'status'    => $this->input('status', '', 'get'),
        ];
        $returns   = $this->returnModel->getAll($filters);
        $pageTitle = 'Sale Returns';
        $page      = 'returns';

        ob_start();
        include __DIR__ . '/../views/returns/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('returns', 'add');
        $parties    = []; // Loaded via AJAX search
        $warehouses = self::getWarehouses();
        $pageTitle  = 'New Return';
        $page       = 'returns';

        // One-time token to prevent double-submit duplicate returns
        $_SESSION['return_form_nonce'] = bin2hex(random_bytes(16));
        $returnFormNonce               = $_SESSION['return_form_nonce'];

        ob_start();
        include __DIR__ . '/../views/returns/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('returns', 'add');

        if (!$this->isPost()) {
            $this->redirect('?page=returns&action=create');
        }

        $postedNonce = isset($_POST['return_form_nonce']) ? trim((string)$_POST['return_form_nonce']) : '';
        $sessNonce   = $_SESSION['return_form_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This return form was already submitted or expired. Please check Returns list before trying again.');
            $this->redirect('?page=returns');
        }

        $rawItems = $_POST['items'] ?? [];
        $items    = [];

        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;
            $imeis = [];
            if (!empty($row['imeis'])) {
                $imeis = array_filter(array_map('trim', explode("\n", $row['imeis'])));
            }
            $items[] = [
                'item_id'    => (int)   $row['item_id'],
                'quantity'   => (int)   $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'imeis'      => $imeis,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Add at least one item.');
            $this->redirect('?page=returns&action=create');
        }

        $refId   = $this->inputInt('ref_id') ?: null;
        $partyId = $this->inputInt('party_id');
        $db      = Database::getInstance();

        // Sale return must use the invoice's party_id. Otherwise a duplicate customer
        // name can be selected and the return posts to the wrong ledger while sales
        // stay on the original account (shows 0 invoices + orphan return).
        if ($refId) {
            $sale = $db->fetchOne(
                "SELECT id, party_id, status, warehouse_id FROM sales WHERE id = ? AND warehouse_id = ?",
                [$refId, Auth::warehouseId()]
            );
            // #region agent log
            $this->debugLog(
                'run1',
                'H4',
                'ReturnController.php:store',
                'Store sale reference lookup',
                [
                    'refId' => $refId,
                    'saleFound' => (bool)$sale,
                    'saleWarehouseId' => $sale ? (int)$sale['warehouse_id'] : null,
                    'authWarehouseId' => (int)Auth::warehouseId(),
                ]
            );
            // #endregion
            if (!$sale) {
                $this->flash('error', 'Selected invoice was not found.');
                $this->redirect('?page=returns&action=create');
            }
            if ($sale['status'] === 'cancelled') {
                $this->flash('error', 'Cannot post a return against a cancelled invoice.');
                $this->redirect('?page=returns&action=create');
            }
            $partyId = (int) $sale['party_id'];
        } elseif ($partyId <= 0) {
            $this->flash('error', 'Please select a customer.');
            $this->redirect('?page=returns&action=create');
        }

        $result = $this->returnModel->create([
            'ref_id'       => $refId,
            'party_id'     => $partyId,
            'warehouse_id' => Auth::warehouseId(),
            'date'         => $this->input('date'),
            'reason'       => $this->input('reason'),
            'items'        => $items,
        ]);

        if ($result['success']) {
            unset($_SESSION['return_form_nonce']);
            $this->logActivity('create_return', 'returns', $result['id'], $result['return_no']);
            $this->flash('success', "Return {$result['return_no']} saved.");
            if ($this->input('print_mode') === '1') {
                $tpl = $_SESSION['print_template'] ?? 'a5';
                if ($tpl === 'thermal') {
                    $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1&thermal=1');
                }
                $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            if ($this->input('print_mode') === '2') {
                $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1&thermal=1');
            }
            $this->redirect('?page=returns');
        } else {
            $this->flash('error', $result['error']);
            $this->redirect('?page=returns&action=create');
        }
    }

    // AJAX: search sale invoices for ref lookup
    public function searchSales(): void {
        Auth::authorize('returns', 'add');
        header('Content-Type: application/json');
        // #region agent log
        $this->debugLog(
            'run1',
            'H5',
            'ReturnController.php:searchSales',
            'searchSales endpoint reached',
            [
                'userId' => (int)Auth::id(),
                'authWarehouseId' => (int)Auth::warehouseId(),
                'qLength' => strlen(trim($_GET['q'] ?? '')),
            ]
        );
        // #endregion
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) { echo json_encode([]); return; }
        $db   = Database::getInstance();
        $like = "%$q%";
        $rows = $db->fetchAll(
            "SELECT s.id, s.party_id, s.invoice_no, p.name as party_name, s.grand_total, s.date
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             WHERE s.status != 'cancelled'
               AND s.warehouse_id = ?
               AND (s.invoice_no LIKE ? OR p.name LIKE ?)
             ORDER BY s.date DESC LIMIT 15",
            [Auth::warehouseId(), $like, $like]
        );
        echo json_encode($rows);
    }

    // AJAX: Lookup IMEI — returns item info with CURRENT sale price
    public function lookupImei(): void {
        Auth::authorize('returns', 'add');
        header('Content-Type: application/json');
        // #region agent log
        $this->debugLog(
            'run1',
            'H5',
            'ReturnController.php:lookupImei',
            'lookupImei endpoint reached',
            [
                'userId' => (int)Auth::id(),
                'authWarehouseId' => (int)Auth::warehouseId(),
            ]
        );
        // #endregion
        $imei = trim($_GET['imei'] ?? '');

        if (!$imei || !ctype_digit($imei) || strlen($imei) < 14 || strlen($imei) > 15) {
            echo json_encode(['found' => false, 'accepted' => false, 'message' => 'Invalid IMEI (must be 14-15 digits).']);
            return;
        }

        $db = Database::getInstance();

        // Find IMEI record + item with historical sale_price if sold, fallback to current sale_price
        $row = $db->fetchOne(
            "SELECT ir.id as imei_id, ir.imei, ir.status, ir.item_id, ir.sale_id, ir.warehouse_id as imei_warehouse_id,
                    i.name as item_name, i.sku, i.sale_price, i.has_imei,
                    s.invoice_no as sold_invoice, s.party_id as sale_party_id, p.name as party_name,
                    si.unit_price as historical_price
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN sale_items si ON si.sale_id = ir.sale_id AND si.item_id = ir.item_id
             WHERE ir.imei = ?
               AND ir.warehouse_id = ?",
            [$imei, Auth::warehouseId()]
        );
        // #region agent log
        $this->debugLog(
            'run1',
            'H4',
            'ReturnController.php:lookupImei',
            'IMEI lookup result warehouse scope',
            [
                'imei' => $imei,
                'found' => (bool)$row,
                'imeiWarehouseId' => $row ? (int)$row['imei_warehouse_id'] : null,
                'authWarehouseId' => (int)Auth::warehouseId(),
                'saleId' => $row && !empty($row['sale_id']) ? (int)$row['sale_id'] : null,
            ]
        );
        // #endregion

        // IMEI NOT in system — still accept it, cashier picks item manually
        if (!$row) {
            echo json_encode([
                'found'    => false,
                'accepted' => true,
                'imei'     => $imei,
                'message'  => 'IMEI not in system — select item model manually.',
            ]);
            return;
        }

        if ($row['status'] !== 'sold') {
            echo json_encode(['found' => false, 'accepted' => false, 'message' => "IMEI is not currently sold and cannot be returned."]);
            return;
        }

        // Return item data with historical price + link to originating sale/customer for correct ledger/ref_id
        $saleId  = !empty($row['sale_id']) ? (int)$row['sale_id'] : null;
        $partyId = !empty($row['sale_party_id']) ? (int)$row['sale_party_id'] : null;
        $unitPrice = isset($row['historical_price']) ? $row['historical_price'] : $row['sale_price'];
        echo json_encode([
            'found'      => true,
            'accepted'   => true,
            'item_id'    => (int)$row['item_id'],
            'item_name'  => $row['item_name'],
            'sku'        => $row['sku'] ?? '',
            'sale_price' => number_format((float)$unitPrice, 3, '.', ''),
            'has_imei'   => (bool)$row['has_imei'],
            'imei'       => $row['imei'],
            'status'     => $row['status'],
            'sold_invoice' => $row['sold_invoice'] ?? '',
            'sale_id'    => $saleId,
            'party_id'   => $partyId,
            'party_name' => $row['party_name'] ?? '',
            'message'    => "Found: {$row['item_name']}" . ($row['sold_invoice'] ? " (from {$row['sold_invoice']})" : ''),
        ]);
    }

    public function print(): void {
        Auth::authorize('returns', 'view');
        $id     = $this->inputInt('id', 0, 'get');
        $return = $this->returnModel->findFull($id);
        if (!$return) die('Return not found.');

        $db       = Database::getInstance();
        $settings = self::getSettings();

        // Party balance for print
        $partyBalance = $this->partyModel->findWithBalance($return['party_id']);
        $currentBalance  = (float)($partyBalance['net_balance'] ?? 0);
        $previousBalance = $currentBalance + (float)$return['grand_total']; // before this return

        // A5 vs thermal: explicit query wins; otherwise use session default from layout "Default Print".
        $tplParam = strtolower(trim((string)($_GET['template'] ?? '')));
        if ($tplParam === 'a5') {
            $returnPrintThermal = false;
        } elseif (isset($_GET['thermal'])) {
            $returnPrintThermal = true;
        } else {
            $returnPrintThermal = (($_SESSION['print_template'] ?? 'a5') === 'thermal');
        }

        include __DIR__ . '/../views/returns/print.php';
    }

    public function detail(): void {
        Auth::authorize('returns', 'view');
        $id     = $this->inputInt('id', 0, 'get');
        $return = $this->returnModel->findFull($id);
        if (!$return) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }

        $pageTitle = 'Return: ' . $return['return_no'];
        $page      = 'returns';

        ob_start();
        include __DIR__ . '/../views/returns/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function edit(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=returns'); return; }

        $id = $this->inputInt('id', 0, 'get');
        $editReturn = $this->returnModel->findFull($id);
        if (!$editReturn) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }
        if (!isset($_SESSION['return_edit_nonce']) || !is_array($_SESSION['return_edit_nonce'])) {
            $_SESSION['return_edit_nonce'] = [];
        }
        $_SESSION['return_edit_nonce'][$id] = bin2hex(random_bytes(16));
        $returnEditNonce = $_SESSION['return_edit_nonce'][$id];

        $pageTitle = 'Edit Return: ' . $editReturn['return_no'];
        $page      = 'returns';

        ob_start();
        include __DIR__ . '/../views/returns/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=returns'); return; }
        if (!$this->isPost()) { $this->redirect('?page=returns'); return; }

        $id = $this->inputInt('id');
        $db = Database::getInstance();
        $postedEditNonce = isset($_POST['return_edit_nonce']) ? trim((string)$_POST['return_edit_nonce']) : '';
        $sessionEditNonce = $_SESSION['return_edit_nonce'][$id] ?? '';
        if ($sessionEditNonce === '' || !hash_equals((string)$sessionEditNonce, $postedEditNonce)) {
            $this->flash('warning', 'Edit form expired. Please reopen the return and try again.');
            $this->redirect("?page=returns&action=edit&id={$id}");
            return;
        }

        $return = $this->returnModel->findFull($id);
        if (!$return) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }

        $newDate   = $this->input('date') ?: $return['date'];
        $newReason = $this->input('reason');
        $warehouseId = (int)$return['warehouse_id'];

        $rawItems    = $_POST['items'] ?? [];
        // #region agent log
        $this->debugLog(
            'run1',
            'H1',
            'ReturnController.php:update',
            'Update started with posted item subsets',
            [
                'returnId' => $id,
                'postedItemsCount' => is_array($rawItems) ? count($rawItems) : 0,
                'postedNewItemsCount' => is_array($_POST['new_items'] ?? null) ? count($_POST['new_items']) : 0,
                'currentReturnSubtotal' => isset($return['subtotal']) ? (float)$return['subtotal'] : null,
                'warehouseId' => $warehouseId,
                'authWarehouseId' => (int)Auth::warehouseId(),
            ]
        );
        // #endregion

        if (!empty($return['ref_id'])) {
            $saleId = (int)$return['ref_id'];
            $saleItems = $db->fetchAll(
                "SELECT si.item_id, SUM(si.quantity) as sold_qty,
                        COALESCE(ret.returned_qty, 0) as already_returned
                 FROM sale_items si
                 LEFT JOIN (
                     SELECT ri.item_id, SUM(ri.quantity) as returned_qty
                     FROM return_items ri
                     JOIN returns r ON r.id = ri.return_id
                     WHERE r.ref_id = ? AND r.status = 'approved' AND r.id != ?
                     GROUP BY ri.item_id
                 ) ret ON ret.item_id = si.item_id
                 WHERE si.sale_id = ?
                 GROUP BY si.item_id",
                [$saleId, $id, $saleId]
            );
            $saleLimits = [];
            foreach ($saleItems as $si) {
                $saleLimits[(int)$si['item_id']] = (int)$si['sold_qty'] - (int)$si['already_returned'];
            }

            // Check quantities: gather total new quantities per item in this return edit
            $itemTotals = [];
            if (!empty($rawItems)) {
                $existingReturnItems = $db->fetchAll("SELECT id, item_id FROM return_items WHERE return_id = ?", [$id]);
                $existingItemMap = [];
                foreach ($existingReturnItems as $eri) {
                    $existingItemMap[(int)$eri['id']] = (int)$eri['item_id'];
                }
                foreach ($rawItems as $retItemId => $row) {
                    if (!empty($row['deleted'])) continue;
                    $itemId = $existingItemMap[(int)$retItemId] ?? 0;
                    if (!$itemId) continue;
                    $qty = max(1, (int)($row['quantity'] ?? 1));
                    $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0) + $qty;
                }
            }
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId = (int)($row['item_id'] ?? 0);
                if (!$itemId) continue;
                $qty = max(1, (int)($row['quantity'] ?? 1));
                $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0) + $qty;
            }

            foreach ($itemTotals as $itemId => $qty) {
                $maxAllowed = $saleLimits[$itemId] ?? 0;
                if ($qty > $maxAllowed) {
                    $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
                    $this->flash('error', "Cannot return {$qty} of \"{$itemName['name']}\" — only {$maxAllowed} available to return from this sale.");
                    $this->redirect("?page=returns&action=edit&id={$id}");
                    return;
                }
            }
        }

        $db->beginTransaction();
        try {
            if (!empty($rawItems)) {
                foreach ($rawItems as $retItemId => $row) {
                    $retItemId = (int)$retItemId;

                    $oldItem = $db->fetchOne(
                        "SELECT ri.item_id, ri.quantity, ri.unit_price, ri.total, i.has_imei
                         FROM return_items ri
                         JOIN items i ON i.id = ri.item_id
                         WHERE ri.id = ? AND ri.return_id = ?",
                        [$retItemId, $id]
                    );
                    if (!$oldItem) continue;
                    $imeiLinkCountRow = $db->fetchOne(
                        "SELECT COUNT(*) as c FROM return_item_imei WHERE return_item_id = ?",
                        [$retItemId]
                    );
                    $imeiLinkCount = (int)($imeiLinkCountRow['c'] ?? 0);

                    // Handle deletion — remove item and reverse its stock effect
                    if (!empty($row['deleted'])) {
                        if ($imeiLinkCount > 0) {
                            $imeiRows = $db->fetchAll(
                                "SELECT ir.id, ir.imei
                                 FROM return_item_imei rii
                                 JOIN imei_records ir ON ir.id = rii.imei_id
                                 WHERE rii.return_item_id = ?",
                                [$retItemId]
                            );
                            foreach ($imeiRows as $imeiRow) {
                                $imeiAff = $db->execute(
                                    "UPDATE imei_records
                                     SET status = 'sold', sale_id = ?, warehouse_id = ?
                                     WHERE id = ? AND status IN ('in_stock','returned')",
                                    [!empty($return['ref_id']) ? (int)$return['ref_id'] : null, $warehouseId, (int)$imeiRow['id']]
                                );
                                if ($imeiAff === 0) {
                                    throw new Exception("Unable to restore IMEI {$imeiRow['imei']} to sold state.");
                                }
                            }
                            $db->execute("DELETE FROM return_item_imei WHERE return_item_id = ?", [$retItemId]);
                        }
                        // A returned item added stock when approved — removing it means deducting stock back
                        $affected = $db->execute(
                            "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [(int)$oldItem['quantity'], $oldItem['item_id'], $warehouseId, (int)$oldItem['quantity']]
                        );
                        if ($affected === 0) {
                            throw new Exception("Cannot remove returned item — stock would go negative for item {$oldItem['item_id']}.");
                        }
                        // #region agent log
                        $this->debugLog(
                            'run1',
                            'H2',
                            'ReturnController.php:update',
                            'Delete return item stock reverse attempt',
                            [
                                'returnId' => $id,
                                'returnItemId' => $retItemId,
                                'itemId' => (int)$oldItem['item_id'],
                                'qtyToDeduct' => (int)$oldItem['quantity'],
                                'stockUpdateAffected' => (int)$affected,
                                'imeiLinkCount' => $imeiLinkCount,
                            ]
                        );
                        // #endregion
                        $db->execute("DELETE FROM return_items WHERE id = ?", [$retItemId]);
                        continue;
                    }

                    $newQty   = max(1, (int)($row['quantity'] ?? 1));
                    $newPrice = (float)($row['unit_price'] ?? 0);
                    $newTotal = round($newQty * $newPrice, 3);
                    $oldQty   = (int)$oldItem['quantity'];
                    $qtyDiff  = $newQty - $oldQty;
                    $isImeiItem = ((int)($oldItem['has_imei'] ?? 0) === 1);
                    $postedImeis = $this->parsePostedImeis($row['imeis'] ?? '');

                    $db->execute(
                        "UPDATE return_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $retItemId]
                    );

                    if ($qtyDiff > 0) {
                        $db->execute(
                            "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                            [$oldItem['item_id'], $warehouseId, $qtyDiff, $qtyDiff]
                        );
                    } elseif ($qtyDiff < 0) {
                        $absDiff = abs($qtyDiff);
                        $affected = $db->execute(
                            "UPDATE stock SET quantity = quantity - ?
                             WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [$absDiff, $oldItem['item_id'], $warehouseId, $absDiff]
                        );
                        if ($affected === 0) {
                            throw new Exception("Insufficient stock to reduce return quantity for item {$oldItem['item_id']}.");
                        }
                    }
                    if ($qtyDiff != 0) {
                        // #region agent log
                        $this->debugLog(
                            'run1',
                            'H2',
                            'ReturnController.php:update',
                            'Quantity diff adjusted stock via upsert',
                            [
                                'returnId' => $id,
                                'returnItemId' => $retItemId,
                                'itemId' => (int)$oldItem['item_id'],
                                'oldQty' => $oldQty,
                                'newQty' => $newQty,
                                'qtyDiff' => $qtyDiff,
                            ]
                        );
                        // #endregion
                    }
                    if ($isImeiItem) {
                        $currentLinks = $db->fetchAll(
                            "SELECT rii.imei_id, ir.imei
                             FROM return_item_imei rii
                             JOIN imei_records ir ON ir.id = rii.imei_id
                             WHERE rii.return_item_id = ?",
                            [$retItemId]
                        );
                        $currentByImei = [];
                        foreach ($currentLinks as $lnk) {
                            $key = (string)($lnk['imei'] ?? '');
                            if ($key !== '') {
                                $currentByImei[$key] = (int)$lnk['imei_id'];
                            }
                        }

                        if ($newQty !== $oldQty || !empty($postedImeis) || !empty($currentByImei)) {
                            if (count($postedImeis) !== $newQty) {
                                throw new Exception("IMEI count must match quantity ({$newQty}) for item {$oldItem['item_id']}.");
                            }

                            foreach ($postedImeis as $postedImei) {
                                if (isset($currentByImei[$postedImei])) {
                                    continue;
                                }
                                $imeiRow = $db->fetchOne(
                                    "SELECT id, status, sale_id, item_id, warehouse_id
                                     FROM imei_records
                                     WHERE imei = ? LIMIT 1",
                                    [$postedImei]
                                );
                                if (!$imeiRow) {
                                    throw new Exception("IMEI {$postedImei} not found in system.");
                                }
                                if ((int)$imeiRow['item_id'] !== (int)$oldItem['item_id']) {
                                    throw new Exception("IMEI {$postedImei} does not belong to item {$oldItem['item_id']}.");
                                }
                                if ((int)$imeiRow['warehouse_id'] !== $warehouseId) {
                                    throw new Exception("IMEI {$postedImei} belongs to another warehouse.");
                                }

                                $refSaleId = !empty($return['ref_id']) ? (int)$return['ref_id'] : 0;
                                if ($refSaleId > 0) {
                                    $aff = $db->execute(
                                        "UPDATE imei_records
                                         SET status='in_stock', sale_id=NULL, warehouse_id=?
                                         WHERE id=? AND status='sold' AND sale_id=?",
                                        [$warehouseId, (int)$imeiRow['id'], $refSaleId]
                                    );
                                } else {
                                    $aff = $db->execute(
                                        "UPDATE imei_records
                                         SET status='in_stock', sale_id=NULL, warehouse_id=?
                                         WHERE id=? AND status='sold'",
                                        [$warehouseId, (int)$imeiRow['id']]
                                    );
                                }
                                if ($aff === 0) {
                                    throw new Exception("IMEI {$postedImei} is not returnable for this sale.");
                                }
                                $db->insert(
                                    "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                                    [$retItemId, (int)$imeiRow['id']]
                                );
                                $currentByImei[$postedImei] = (int)$imeiRow['id'];
                            }

                            foreach ($currentByImei as $imeiText => $imeiId) {
                                if (in_array($imeiText, $postedImeis, true)) {
                                    continue;
                                }
                                $soldAff = $db->execute(
                                    "UPDATE imei_records
                                     SET status='sold', sale_id=?, warehouse_id=?
                                     WHERE id=? AND status IN ('in_stock','returned')",
                                    [!empty($return['ref_id']) ? (int)$return['ref_id'] : null, $warehouseId, (int)$imeiId]
                                );
                                if ($soldAff === 0) {
                                    throw new Exception("Unable to restore IMEI {$imeiText} to sold state.");
                                }
                                $db->execute(
                                    "DELETE FROM return_item_imei WHERE return_item_id = ? AND imei_id = ?",
                                    [$retItemId, (int)$imeiId]
                                );
                            }
                        }
                    }
                    // #region agent log
                    $this->debugLog(
                        'run1',
                        'H3',
                        'ReturnController.php:update',
                        'Return item quantity edited without IMEI reconciliation',
                        [
                            'returnId' => $id,
                            'returnItemId' => $retItemId,
                            'itemId' => (int)$oldItem['item_id'],
                            'oldQty' => $oldQty,
                            'newQty' => $newQty,
                            'imeiLinkCount' => $imeiLinkCount,
                        ]
                    );
                    // #endregion

                }
            }

            // ── Handle new items added during edit ────────────────────────────
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId   = (int)($row['item_id'] ?? 0);
                $newQty   = max(1, (int)($row['quantity'] ?? 1));
                $newPrice = (float)($row['unit_price'] ?? 0);
                $newTotal = round($newQty * $newPrice, 3);
                if (!$itemId || $newPrice <= 0) continue;
                $itemMeta = $db->fetchOne("SELECT has_imei FROM items WHERE id = ?", [$itemId]);
                $postedImeis = $this->parsePostedImeis($row['imeis'] ?? '');
                if ($itemMeta && (int)$itemMeta['has_imei'] === 1 && count($postedImeis) !== $newQty) {
                    throw new Exception("IMEI count must match quantity ({$newQty}) for new item {$itemId}.");
                }

                $retItemId = $db->insert(
                    "INSERT INTO return_items (return_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$id, $itemId, $newQty, $newPrice, $newTotal]
                );

                // A new return item adds stock back
                $db->execute(
                    "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                    [$itemId, $warehouseId, $newQty, $newQty]
                );

                if ($itemMeta && (int)$itemMeta['has_imei'] === 1) {
                    $refSaleId = !empty($return['ref_id']) ? (int)$return['ref_id'] : 0;
                    foreach ($postedImeis as $postedImei) {
                        $imeiRow = $db->fetchOne(
                            "SELECT id, status, sale_id, item_id, warehouse_id
                             FROM imei_records
                             WHERE imei = ? LIMIT 1",
                            [$postedImei]
                        );
                        if (!$imeiRow) {
                            throw new Exception("IMEI {$postedImei} not found in system.");
                        }
                        if ((int)$imeiRow['item_id'] !== $itemId) {
                            throw new Exception("IMEI {$postedImei} does not belong to item {$itemId}.");
                        }
                        if ((int)$imeiRow['warehouse_id'] !== $warehouseId) {
                            throw new Exception("IMEI {$postedImei} belongs to another warehouse.");
                        }

                        if ($refSaleId > 0) {
                            $aff = $db->execute(
                                "UPDATE imei_records
                                 SET status='in_stock', sale_id=NULL, warehouse_id=?
                                 WHERE id=? AND status='sold' AND sale_id=?",
                                [$warehouseId, (int)$imeiRow['id'], $refSaleId]
                            );
                        } else {
                            $aff = $db->execute(
                                "UPDATE imei_records
                                 SET status='in_stock', sale_id=NULL, warehouse_id=?
                                 WHERE id=? AND status='sold'",
                                [$warehouseId, (int)$imeiRow['id']]
                            );
                        }
                        if ($aff === 0) {
                            throw new Exception("IMEI {$postedImei} is not returnable for this sale.");
                        }
                        $db->insert(
                            "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                            [(int)$retItemId, (int)$imeiRow['id']]
                        );
                    }
                }
            }
            $dbSubtotalRow = $db->fetchOne(
                "SELECT COALESCE(SUM(total),0) as sum_total FROM return_items WHERE return_id = ?",
                [$id]
            );
            $newSubtotal = (float)($dbSubtotalRow['sum_total'] ?? 0);

            $updatedRows = $db->execute(
                "UPDATE returns SET date = ?, subtotal = ?, grand_total = ?, reason = ? WHERE id = ? AND warehouse_id = ?",
                [$newDate, $newSubtotal, $newSubtotal, $newReason, $id, $warehouseId]
            );
            if ($updatedRows !== 1) {
                throw new Exception("Return header update failed for return {$id}.");
            }
            $sumReturnItems = $newSubtotal;
            // #region agent log
            $this->debugLog(
                'run1',
                'H1',
                'ReturnController.php:update',
                'Post-update subtotal comparison and header update scope',
                [
                    'returnId' => $id,
                    'computedNewSubtotal' => (float)$newSubtotal,
                    'sumReturnItems' => $sumReturnItems,
                    'headerWarehouseFilter' => $warehouseId,
                    'returnWarehouseId' => $warehouseId,
                ]
            );
            // #endregion

            if (!empty($return['ref_id'])) {
                $this->saleModel->recomputeBalanceAfterReturns((int) $return['ref_id']);
            }

            $db->commit();
            unset($_SESSION['return_edit_nonce'][$id]);
            $this->logActivity('edit_return', 'returns', $id, "Edited {$return['return_no']}");
            $this->flash('success', "Return {$return['return_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed: ' . $e->getMessage());
        }

        $this->redirect("?page=returns&action=detail&id={$id}");
    }
}
