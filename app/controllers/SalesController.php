<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../helpers/WhatsApp.php';
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
        $accounts   = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $last       = $db->fetchOne("SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1");
        $lastNum    = $last ? (int) substr($last['invoice_no'], strlen(SALE_PREFIX)) : 0;
        $nextInv    = SALE_PREFIX . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
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

        // Block new invoice if agent has outstanding unpaid balance OR exceeds credit limit
        $partyId = $this->inputInt('party_id');
        if ($partyId) {
            $db = Database::getInstance();

            $party = $db->fetchOne(
                "SELECT name, credit_limit FROM parties WHERE id = ?",
                [$partyId]
            );
            $name        = $party['name'] ?? 'This agent';
            $creditLimit = (float) ($party['credit_limit'] ?? 0);

            // Skip heavy balance query if no credit limit is set
            if ($creditLimit <= 0) goto skipCreditCheck;

            // Calculate real outstanding balance (same formula as party statement)
            $balRow = $db->fetchOne(
                "SELECT
                    p.opening_balance
                    + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled'), 0)
                    - COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount')), 0)
                    - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved'), 0)
                    as net_balance
                 FROM parties p WHERE p.id = ?",
                [$partyId]
            );
            $outstandingTotal = max(0, (float)($balRow['net_balance'] ?? 0));

            // Check credit limit if one is set
            if ($creditLimit > 0) {
                // Calculate new invoice total from submitted items
                $rawItems   = $_POST['items'] ?? [];
                $newTotal   = 0;
                foreach ($rawItems as $row) {
                    if (empty($row['item_id']) || empty($row['quantity'])) continue;
                    $lineDisc  = (float) ($row['discount'] ?? 0);
                    $lineTotal = ((float)($row['unit_price'] ?? 0) * (int)$row['quantity']) - $lineDisc;
                    $newTotal += $lineTotal;
                }
                $newTotal   -= (float) ($_POST['discount'] ?? 0);

                $totalAfterInvoice = $outstandingTotal + $newTotal;

                if ($totalAfterInvoice > $creditLimit) {
                    $this->flash('error',
                        "{$name}'s credit limit is " . APP_CURRENCY . " " . number_format($creditLimit, DECIMAL_PLACES) .
                        ". Current outstanding: " . APP_CURRENCY . " " . number_format($outstandingTotal, DECIMAL_PLACES) .
                        " + this invoice: " . APP_CURRENCY . " " . number_format($newTotal, DECIMAL_PLACES) .
                        " = " . APP_CURRENCY . " " . number_format($totalAfterInvoice, DECIMAL_PLACES) .
                        " — exceeds limit. Collect payment first or increase the credit limit."
                    );
                    $this->redirect('?page=sales&action=create');
                }
            }
            skipCreditCheck:
        }

        // Parse items from POST
        $rawItems   = $_POST['items'] ?? [];
        $items      = [];
        $hasError   = false;

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

            // Cashier/viewer: price can be higher than catalog, but never lower
            $requestedPrice = (float)$row['unit_price'];
            if (in_array(Auth::role(), ['cashier','viewer']) && $itemInfo) {
                $catalogPrice = (float)$itemInfo['sale_price'];
                if ($requestedPrice < $catalogPrice) {
                    $requestedPrice = $catalogPrice; // enforce minimum
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
            'warehouse_id'   => $this->inputInt('warehouse_id'),
            'date'           => $this->input('date'),
            'discount'       => $this->inputFloat('discount'),
            'tax'            => 0,
            'paid_amount'    => 0, // Payment collected separately via Payments page
            'account_id'     => $this->inputInt('account_id'),
            'payment_method' => $this->input('payment_method'),
            'notes'          => $this->input('notes'),
            'items'          => $items,
        ]);

        if ($result['success']) {
            $this->logActivity('create_sale', 'sales', $result['id'], "Invoice {$result['invoice_no']}");
            $this->flash('success', "Sale {$result['invoice_no']} saved successfully.");

            // WhatsApp notification
            $db      = Database::getInstance();
            $sale    = $db->fetchOne("SELECT s.*, p.name as party_name, w.name as branch_name FROM sales s LEFT JOIN parties p ON p.id = s.party_id LEFT JOIN warehouses w ON w.id = s.warehouse_id WHERE s.id = ?", [$result['id']]);
            WhatsApp::sale([
                'invoice_no' => $result['invoice_no'],
                'party'      => $sale['party_name'] ?? '—',
                'branch'     => $sale['branch_name'] ?? '—',
                'total'      => number_format($sale['grand_total'], 3),
                'paid'       => number_format($sale['paid_amount'], 3),
                'currency'   => APP_CURRENCY,
            ]);
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
        $accounts  = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
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

        // Party balance — use unified method
        $partyBalance = $this->partyModel->findWithBalance($sale['party_id']);
        $currentBalance = (float)($partyBalance['net_balance'] ?? 0);
        // Previous balance = current balance minus this invoice's unpaid portion
        $sale['prev_balance']  = $currentBalance - (float)$sale['balance'];
        $sale['total_balance'] = $currentBalance;

        include __DIR__ . '/../views/sales/print.php';
    }

    // Add payment to existing sale (AJAX)
    public function addPayment(): void {
        Auth::authorize('sales', 'edit');
        header('Content-Type: application/json');

        $id   = $this->inputInt('sale_id');
        $sale = $this->saleModel->find($id);

        // Lock paid invoices
        if ($sale && $sale['status'] === 'paid') {
            echo json_encode(['success' => false, 'error' => 'This invoice is already fully paid.']);
            return;
        }
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

    // Edit sale form (admin only)
    public function edit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }

        $id       = $this->inputInt('id', 0, 'get');
        $editSale = $this->saleModel->findFull($id);

        if (!$editSale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }

        if ($editSale['status'] === 'cancelled') {
            $this->flash('error', 'Cancelled invoices cannot be edited.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }

        $pageTitle = 'Edit: ' . $editSale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update sale (admin only — date, discount, notes)
    public function update(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('?page=sales');
            return;
        }

        $id   = $this->inputInt('id', 0, 'get') ?: $this->inputInt('id');
        $sale = $this->saleModel->find($id);

        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }

        if ($sale['status'] === 'cancelled') {
            $this->flash('error', 'Cancelled invoices cannot be edited.');
            $this->redirect('?page=sales');
            return;
        }

        $db = Database::getInstance();

        $newDate     = $this->input('date') ?: $sale['date'];
        $newDiscount = $this->inputFloat('discount');
        $newNotes    = $this->input('notes');
        $warehouseId = (int)$sale['warehouse_id'];

        // ── Handle item changes (qty/price) ──
        $rawItems    = $_POST['items'] ?? [];
        $newSubtotal = 0;

        $db->beginTransaction();
        try {
            if (!empty($rawItems)) {
                foreach ($rawItems as $saleItemId => $row) {
                    $saleItemId = (int)$saleItemId;

                    // Get old item data first
                    $oldItem = $db->fetchOne(
                        "SELECT item_id, quantity, unit_price, total FROM sale_items WHERE id = ? AND sale_id = ?",
                        [$saleItemId, $id]
                    );
                    if (!$oldItem) continue;

                    // Handle deletion
                    if (!empty($row['deleted'])) {
                        // Restore full qty back to stock
                        $db->execute(
                            "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                            [(int)$oldItem['quantity'], $oldItem['item_id'], $warehouseId]
                        );
                        $db->execute("DELETE FROM sale_items WHERE id = ?", [$saleItemId]);
                        continue;
                    }

                    $newQty   = max(1, (int)($row['quantity'] ?? 1));
                    $newPrice = (float)($row['unit_price'] ?? 0);
                    $newTotal = round($newQty * $newPrice, 3);
                    $oldQty   = (int)$oldItem['quantity'];
                    $qtyDiff  = $oldQty - $newQty;

                    $db->execute(
                        "UPDATE sale_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $saleItemId]
                    );

                    if ($qtyDiff != 0) {
                        $db->execute(
                            "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                            [$qtyDiff, $oldItem['item_id'], $warehouseId]
                        );
                    }

                    $newSubtotal += $newTotal;
                }
            } else {
                $newSubtotal = (float)$sale['subtotal'];
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
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, discount, tax, total)
                     VALUES (?,?,?,?,0,0,?)",
                    [$id, $itemId, $newQty, $newPrice, $newTotal]
                );

                // Deduct stock (check affected rows to prevent silent failure)
                $affected = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$newQty, $itemId, $warehouseId, $newQty]
                );
                if ($affected === 0) {
                    $stock = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $warehouseId]);
                    $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
                    throw new Exception("Insufficient stock for \"{$itemName['name']}\". Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$newQty}.");
                }

                $newSubtotal += $newTotal;
            }

            // Recalculate grand_total and balance
            $paidAmount    = (float)$sale['paid_amount'];
            $newGrandTotal = $newSubtotal - $newDiscount;
            $newBalance    = max(0, $newGrandTotal - $paidAmount);

            // Determine new status
            if ($newBalance < 0.001) {
                $newStatus = 'paid';
                $newBalance = 0;
            } elseif ($paidAmount > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = $sale['status'] === 'paid' ? 'confirmed' : $sale['status'];
            }

            $db->execute(
                "UPDATE sales SET date=?, subtotal=?, discount=?, grand_total=?, balance=?, status=?, notes=? WHERE id=?",
                [$newDate, $newSubtotal, $newDiscount, $newGrandTotal, $newBalance, $newStatus, $newNotes ?: null, $id]
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Error updating: ' . $e->getMessage());
            $this->redirect("?page=sales&action=edit&id={$id}");
            return;
        }

        $this->logActivity('edit_sale', 'sales', $id, "Edited {$sale['invoice_no']}: items/prices updated");
        $this->flash('success', "Invoice {$sale['invoice_no']} updated.");

        if ($this->input('print_after_save') === '1') {
            $this->redirect("?page=sales&action=print&id={$id}");
            return;
        }

        $this->redirect("?page=sales&action=detail&id={$id}");
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

        // Lock paid invoices
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
        $whId = (int) ($_GET['warehouse_id'] ?? 0);

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
