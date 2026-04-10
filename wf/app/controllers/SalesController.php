<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../models/Payment.php';

class SalesController extends BaseController {

    private Sale    $saleModel;
    private Item    $itemModel;
    private Party   $partyModel;
    private IMEI    $imeiModel;
    private Payment $paymentModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel    = new Sale();
        $this->itemModel    = new Item();
        $this->partyModel   = new Party();
        $this->imeiModel    = new IMEI();
        $this->paymentModel = new Payment();
    }

    // Sales list page
    public function index(): void {
        Auth::authorize('sales', 'view');

        $filters = [
            'search'    => $this->input('search', '', 'get'),
            'status'    => $this->input('status', '', 'get'),
            'party_id'  => $this->inputInt('party_id', 0, 'get'),
            'from_date' => $this->input('from_date', '', 'get'),
            'to_date'   => $this->input('to_date', '', 'get'),
        ];

        $sales     = $this->saleModel->getAll($filters);
        $stats     = $this->saleModel->getStats('month');
        $parties   = $this->partyModel->getForDropdown('customer');
        $pageTitle = 'Sales';
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Create sale form
    public function create(): void {
        Auth::authorize('sales', 'add');

        $db         = Database::getInstance();
        $warehouses = $this->itemModel->getWarehouses();
        $accounts   = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");
        $nextInv    = $this->saleModel->nextInvoiceNo();
        $pageTitle  = 'New Sale';
        $page       = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Save new sale
    public function store(): void {
        Auth::authorize('sales', 'add');

        if (!$this->isPost()) {
            $this->redirect('?page=sales&action=create');
        }

        // Parse items from POST
        $rawItems   = $_POST['items'] ?? [];
        $items      = [];
        $hasError   = false;

        // Credit limit enforcement (matching main app)
        $partyId = $this->inputInt('party_id');
        if ($partyId) {
            $db = Database::getInstance();
            $party = $db->fetchOne("SELECT name, credit_limit FROM parties WHERE id = ?", [$partyId]);
            $creditLimit = (float)($party['credit_limit'] ?? 0);
            if ($creditLimit > 0) {
                $balRow = $db->fetchOne(
                    "SELECT p.opening_balance
                        + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled'), 0)
                        - COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount')), 0)
                        - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved'), 0)
                        as net_balance
                     FROM parties p WHERE p.id = ?",
                    [$partyId]
                );
                $outstanding = max(0, (float)($balRow['net_balance'] ?? 0));
                $newTotal = 0;
                foreach ($rawItems as $r) {
                    if (empty($r['item_id']) || empty($r['quantity'])) continue;
                    $newTotal += ((float)($r['unit_price'] ?? 0) * (int)$r['quantity']) - (float)($r['discount'] ?? 0);
                }
                $newTotal -= (float)($_POST['discount'] ?? 0);
                if ($outstanding + $newTotal > $creditLimit) {
                    $this->flash('error', ($party['name'] ?? 'Agent') . "'s credit limit exceeded. Outstanding: " . number_format($outstanding, 3) . ", Limit: " . number_format($creditLimit, 3));
                    $this->redirect('?page=sales&action=create');
                }
            }
        }

        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;

            // Validate positive values
            if ((int)$row['quantity'] <= 0) {
                $this->flash('error', 'Quantity must be greater than zero.');
                $hasError = true; break;
            }
            if ((float)($row['unit_price'] ?? 0) < 0) {
                $this->flash('error', 'Price cannot be negative.');
                $hasError = true; break;
            }

            $imeis = [];
            if (!empty($row['imeis'])) {
                $imeis = array_filter(array_map('trim', explode("\n", $row['imeis'])));
            }

            // Validate IMEI count matches qty for IMEI items
            $itemInfo = $this->itemModel->find((int)$row['item_id']);

            // Price floor enforcement: cashier/viewer cannot sell below catalog price
            $requestedPrice = (float)$row['unit_price'];
            if (in_array(Auth::role(), ['cashier','viewer']) && $itemInfo) {
                $catalogPrice = (float)$itemInfo['sale_price'];
                if ($requestedPrice < $catalogPrice) {
                    $requestedPrice = $catalogPrice;
                }
            }

            if ($itemInfo && $itemInfo['has_imei'] && count($imeis) !== (int)$row['quantity']) {
                $this->flash('error', "Item \"{$itemInfo['name']}\": IMEI count must match quantity ({$row['quantity']}).");
                $hasError = true;
                break;
            }

            // Validate IMEI availability
            if (!empty($imeis)) {
                $errors = $this->imeiModel->validateList($imeis, (int)$row['item_id']);
                if (!empty($errors)) {
                    $this->flash('error', implode(' | ', $errors));
                    $hasError = true;
                    break;
                }
            }

            $items[] = [
                'item_id'    => (int)   $row['item_id'],
                'quantity'   => (int)   $row['quantity'],
                'unit_price' => $requestedPrice,
                'discount'   => (float) ($row['discount'] ?? 0),
                'imeis'      => $imeis,
            ];
        }

        if ($hasError || empty($items)) {
            if (!$hasError) $this->flash('error', 'Please add at least one item.');
            $this->redirect('?page=sales&action=create');
        }

        $result = $this->saleModel->createFull([
            'party_id'       => $this->inputInt('party_id'),
            'warehouse_id'   => Auth::warehouseId(),
            'date'           => $this->input('date'),
            'discount'       => $this->inputFloat('discount'),
            'tax'            => 0,
            'paid_amount'    => $this->inputFloat('paid_amount'),
            'account_id'     => $this->inputInt('account_id'),
            'payment_method' => $this->input('payment_method'),
            'notes'          => $this->input('notes'),
            'items'          => $items,
        ]);

        if ($result['success']) {
            $this->logActivity('create_sale', 'sales', $result['id'], "Invoice {$result['invoice_no']}");
            $this->flash('success', "Sale {$result['invoice_no']} saved successfully.");
            if ($this->input('print_mode') === '1') {
                $this->redirect('?page=sales&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            $this->redirect('?page=sales&action=detail&id=' . $result['id']);
        } else {
            $this->flash('error', 'Failed to save sale: ' . $result['error']);
            $this->redirect('?page=sales&action=create');
        }
    }

    // View single sale
    public function detail(): void {
        Auth::authorize('sales', 'view');

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);

        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
        }

        $db        = Database::getInstance();
        $accounts  = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1");
        $pageTitle = 'Sale: ' . $sale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Print/PDF invoice
    public function print(): void {
        Auth::authorize('sales', 'view');

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);

        if (!$sale) die('Invoice not found.');

        $db       = Database::getInstance();
        $settings = [];
        $rows     = $db->fetchAll("SELECT key_name, value FROM settings");
        foreach ($rows as $r) {
            $settings[$r['key_name']] = $r['value'];
        }

        // Previous balance = sum of all unpaid/partial sales for this party BEFORE this invoice
        $prevBal = $db->fetchOne(
            "SELECT COALESCE(SUM(balance), 0) as prev_balance
             FROM sales
             WHERE party_id = ? AND id < ? AND status IN ('confirmed','partial')",
            [$sale['party_id'], $id]
        );
        $sale['prev_balance']  = (float) ($prevBal['prev_balance'] ?? 0);
        $sale['total_balance'] = $sale['prev_balance'] + (float)$sale['balance'];

        include __DIR__ . '/../views/sales/print.php';
    }

    // Add payment to existing sale (AJAX)
    public function addPayment(): void {
        Auth::authorize('sales', 'edit');
        header('Content-Type: application/json');

        $id     = $this->inputInt('sale_id');
        $amount = $this->inputFloat('amount');
        $accId  = $this->inputInt('account_id');
        $method = $this->input('payment_method');
        $date   = $this->input('date') ?: date('Y-m-d');
        $notes  = $this->input('notes');

        if ($amount <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
            return;
        }

        $ok = $this->saleModel->addPayment($id, $amount, $accId, $method, $date, $notes);
        if ($ok === true) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => is_string($ok) ? $ok : 'Failed to add payment.']);
        }
    }

    // Cancel sale
    public function cancel(): void {
        Auth::authorize('sales', 'delete');

        // AUDIT FIX S5: Require POST to prevent CSRF via GET links
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=sales');
            return;
        }

        $id   = $this->inputInt('id');
        $sale = $this->saleModel->find($id);

        // AUDIT FIX S7: Lock paid invoices (was missing in wf/ frontend)
        if ($sale && $sale['status'] === 'paid') {
            $this->flash('error', 'Paid invoices cannot be cancelled. Contact admin to unlock.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }

        if ($this->saleModel->cancel($id)) {
            $this->logActivity('cancel_sale', 'sales', $id);
            $this->flash('success', 'Sale cancelled successfully.');
        } else {
            $this->flash('error', 'Could not cancel this sale.');
        }
        $this->redirect('?page=sales');
    }

    // AJAX: search items for autocomplete
    public function searchItems(): void {
        header('Content-Type: application/json');
        $q    = trim($_GET['q'] ?? '');
        $whId = Auth::warehouseId() ?? (int) ($_GET['warehouse_id'] ?? 0);

        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        $items = $this->itemModel->search($q, $whId ?: null);
        echo json_encode($items);
    }

    // AJAX: search parties
    public function searchParties(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        $parties = $this->partyModel->search($q, 'customer');
        echo json_encode($parties);
    }

    // AJAX: validate IMEI
    public function checkImei(): void {
        header('Content-Type: application/json');
        $imei   = trim($_GET['imei'] ?? '');
        $itemId = (int) ($_GET['item_id'] ?? 0);

        if (!$imei) {
            echo json_encode(['valid' => false, 'message' => 'Empty IMEI.']);
            return;
        }

        $row = Database::getInstance()->fetchOne(
            "SELECT ir.*, i.name as item_name FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ?",
            [$imei]
        );

        if (!$row) {
            echo json_encode(['valid' => true, 'message' => 'New IMEI — will be registered.']);
            return;
        }

        if ($row['status'] === 'sold') {
            echo json_encode(['valid' => false, 'message' => "IMEI already sold ({$row['item_name']})."]);
            return;
        }

        if ($itemId && (int)$row['item_id'] !== $itemId) {
            echo json_encode(['valid' => false, 'message' => "IMEI belongs to: {$row['item_name']}."]);
            return;
        }

        echo json_encode(['valid' => true, 'message' => "In stock: {$row['item_name']}."]);
    }

    // AJAX: get items for a sale (pre-fill return form)
    public function getSaleItems(): void {
        header('Content-Type: application/json');
        $id = (int) ($_GET['sale_id'] ?? 0);

        $items = Database::getInstance()->fetchAll(
            "SELECT si.id, si.item_id, si.quantity, si.unit_price,
                    i.name as item_name, i.has_imei
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             WHERE si.sale_id = ?",
            [$id]
        );

        echo json_encode($items);
    }
}
