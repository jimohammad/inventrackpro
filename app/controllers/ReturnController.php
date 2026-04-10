<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Return.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Item.php';

class ReturnController extends BaseController {
    private SaleReturn $returnModel;
    private Party      $partyModel;
    private Item       $itemModel;

    public function __construct() {
        parent::__construct();
        $this->returnModel = new SaleReturn();
        $this->partyModel  = new Party();
        $this->itemModel   = new Item();
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
        $db         = Database::getInstance();
        $parties    = []; // Loaded via AJAX search
        $warehouses = $this->itemModel->getWarehouses();
        $pageTitle  = 'New Return';
        $page       = 'returns';

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

        $result = $this->returnModel->create([
            'ref_id'       => $this->inputInt('ref_id') ?: null,
            'party_id'     => $this->inputInt('party_id'),
            'warehouse_id' => $this->inputInt('warehouse_id'),
            'date'         => $this->input('date'),
            'reason'       => $this->input('reason'),
            'items'        => $items,
        ]);

        if ($result['success']) {
            $this->logActivity('create_return', 'returns', $result['id'], $result['return_no']);
            $this->flash('success', "Return {$result['return_no']} saved.");
            if ($this->input('print_mode') === '1') {
                $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            $this->redirect('?page=returns');
        } else {
            $this->flash('error', $result['error']);
            $this->redirect('?page=returns&action=create');
        }
    }

    // AJAX: search sale invoices for ref lookup
    public function searchSales(): void {
        header('Content-Type: application/json');
        $q    = trim($_GET['q'] ?? '');
        $whId = (int)($_GET['warehouse_id'] ?? 0);
        if (strlen($q) < 1) { echo json_encode([]); return; }
        $db   = Database::getInstance();
        $like = "%$q%";
        $params = [$like, $like];
        $whCond = '';
        if ($whId) { $whCond = " AND s.warehouse_id = ?"; $params[] = $whId; }
        $rows = $db->fetchAll(
            "SELECT s.id, s.invoice_no, p.name as party_name, s.grand_total, s.date
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             WHERE s.status != 'cancelled'
               AND (s.invoice_no LIKE ? OR p.name LIKE ?)
               $whCond
             ORDER BY s.date DESC LIMIT 15",
            $params
        );
        echo json_encode($rows);
    }

    // AJAX: Lookup IMEI — returns item info with CURRENT sale price
    public function lookupImei(): void {
        header('Content-Type: application/json');
        $imei = trim($_GET['imei'] ?? '');

        if (!$imei || strlen($imei) < 10) {
            echo json_encode(['found' => false, 'accepted' => false, 'message' => 'Invalid IMEI.']);
            return;
        }

        $db = Database::getInstance();

        // Find IMEI record + item with CURRENT sale_price
        $row = $db->fetchOne(
            "SELECT ir.id as imei_id, ir.imei, ir.status, ir.item_id, ir.sale_id,
                    i.name as item_name, i.sku, i.sale_price, i.has_imei,
                    s.invoice_no as sold_invoice
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             WHERE ir.imei = ?",
            [$imei]
        );

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

        if ($row['status'] === 'returned') {
            echo json_encode(['found' => false, 'accepted' => false, 'message' => "IMEI already returned."]);
            return;
        }

        // Return item data with CURRENT price
        echo json_encode([
            'found'      => true,
            'accepted'   => true,
            'item_id'    => (int)$row['item_id'],
            'item_name'  => $row['item_name'],
            'sku'        => $row['sku'] ?? '',
            'sale_price' => number_format((float)$row['sale_price'], 3, '.', ''),
            'has_imei'   => (bool)$row['has_imei'],
            'imei'       => $row['imei'],
            'status'     => $row['status'],
            'sold_invoice' => $row['sold_invoice'] ?? '',
            'message'    => "Found: {$row['item_name']}" . ($row['sold_invoice'] ? " (from {$row['sold_invoice']})" : ''),
        ]);
    }

    public function print(): void {
        Auth::authorize('returns', 'view');
        $id     = $this->inputInt('id', 0, 'get');
        $return = $this->returnModel->findFull($id);
        if (!$return) die('Return not found.');

        $db       = Database::getInstance();
        $settings = [];
        $rows     = $db->fetchAll("SELECT key_name, value FROM settings");
        foreach ($rows as $r) $settings[$r['key_name']] = $r['value'];

        // Party balance for print
        $partyBalance = $this->partyModel->findWithBalance($return['party_id']);
        $currentBalance  = (float)($partyBalance['net_balance'] ?? 0);
        $previousBalance = $currentBalance + (float)$return['grand_total']; // before this return

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

        $return = $this->returnModel->findFull($id);
        if (!$return) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }

        $newDate   = $this->input('date') ?: $return['date'];
        $newReason = $this->input('reason');
        $warehouseId = (int)$return['warehouse_id'];

        $rawItems    = $_POST['items'] ?? [];
        $newSubtotal = 0;

        $db->beginTransaction();
        try {
            if (!empty($rawItems)) {
                foreach ($rawItems as $retItemId => $row) {
                    $retItemId = (int)$retItemId;

                    $oldItem = $db->fetchOne(
                        "SELECT item_id, quantity, unit_price, total FROM return_items WHERE id = ? AND return_id = ?",
                        [$retItemId, $id]
                    );
                    if (!$oldItem) continue;

                    // Handle deletion — remove item and reverse its stock effect
                    if (!empty($row['deleted'])) {
                        // A returned item added stock when approved — removing it means deducting stock back
                        $db->execute(
                            "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [(int)$oldItem['quantity'], $oldItem['item_id'], $warehouseId, (int)$oldItem['quantity']]
                        );
                        $db->execute("DELETE FROM return_items WHERE id = ?", [$retItemId]);
                        continue;
                    }

                    $newQty   = max(1, (int)($row['quantity'] ?? 1));
                    $newPrice = (float)($row['unit_price'] ?? 0);
                    $newTotal = round($newQty * $newPrice, 3);
                    $oldQty   = (int)$oldItem['quantity'];
                    $qtyDiff  = $newQty - $oldQty;

                    $db->execute(
                        "UPDATE return_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $retItemId]
                    );

                    if ($qtyDiff != 0) {
                        $db->execute(
                            "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                            [$oldItem['item_id'], $warehouseId, $qtyDiff, $qtyDiff]
                        );
                    }

                    $newSubtotal += $newTotal;
                }
            } else {
                $newSubtotal = (float)$return['subtotal'];
            }

            // ── Handle new items added during edit ────────────────────────────
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId   = (int)($row['item_id'] ?? 0);
                $newQty   = max(1, (int)($row['quantity'] ?? 1));
                $newPrice = (float)($row['unit_price'] ?? 0);
                $newTotal = round($newQty * $newPrice, 3);
                if (!$itemId || $newPrice <= 0) continue;

                $db->insert(
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

                $newSubtotal += $newTotal;
            }

            $db->execute(
                "UPDATE returns SET date = ?, subtotal = ?, grand_total = ?, reason = ? WHERE id = ?",
                [$newDate, $newSubtotal, $newSubtotal, $newReason, $id]
            );

            $db->commit();
            $this->logActivity('edit_return', 'returns', $id, "Edited {$return['return_no']}");
            $this->flash('success', "Return {$return['return_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed: ' . $e->getMessage());
        }

        $this->redirect("?page=returns&action=detail&id={$id}");
    }
}
