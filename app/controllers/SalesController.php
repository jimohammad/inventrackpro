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
        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $last       = $db->fetchOne("SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1");
        $lastNum    = $last ? (int) substr($last['invoice_no'], strlen(SALE_PREFIX)) : 0;
        $nextInv    = SALE_PREFIX . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        $pageTitle  = 'New Sale';
        $page       = 'sales';

        // One-time token to prevent double-submit duplicate sales
        $_SESSION['sale_form_nonce'] = bin2hex(random_bytes(16));
        $saleFormNonce               = $_SESSION['sale_form_nonce'];

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

        $postedNonce = isset($_POST['sale_form_nonce']) ? trim((string)$_POST['sale_form_nonce']) : '';
        $sessNonce   = $_SESSION['sale_form_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This sale form was already submitted or expired. Please check Sales list before trying again.');
            $this->redirect('?page=sales');
        }
        unset($_SESSION['sale_form_nonce']);

        // Block new invoice if agent has outstanding unpaid balance OR exceeds credit limit
        $partyId = $this->inputInt('party_id');
        if ($partyId) {
            $db = Database::getInstance();

            $party = $db->fetchOne(
                "SELECT name, credit_limit FROM parties WHERE id = ? AND is_active = 1",
                [$partyId]
            );
            if (!$party) {
                $this->flash('error', 'Customer is inactive or does not exist.');
                $this->redirect('?page=sales&action=create');
                return;
            }
            $name        = $party['name'];
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

        // Batch-fetch all item info upfront — avoids N queries in the loop below
        $allItemIds = array_filter(array_column($rawItems, 'item_id'));
        $itemMap    = [];
        if (!empty($allItemIds)) {
            $ph = implode(',', array_fill(0, count($allItemIds), '?'));
            $db = Database::getInstance();
            foreach ($db->fetchAll("SELECT id, name, has_imei, imei_optional, sale_price FROM items WHERE id IN ({$ph})", array_map('intval', $allItemIds)) as $r) {
                $itemMap[(int)$r['id']] = $r;
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

            $imeis    = [];
            if (!empty($row['imeis'])) {
                $imeis = array_filter(array_map('trim', explode("\n", $row['imeis'])));
            }

            $itemInfo = $itemMap[(int)$row['item_id']] ?? null;

            // Validate IMEI count matches qty for IMEI items
            if ($itemInfo && $itemInfo['has_imei'] && empty($itemInfo['imei_optional']) && count($imeis) !== (int)$row['quantity']) {
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
                    $requestedPrice = $catalogPrice;
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

            // Build WhatsApp payload from already-known data — no extra DB query
            $partyName  = '';
            $branchName = '';
            $partyIdVal = $this->inputInt('party_id');
            $whIdVal    = $this->inputInt('warehouse_id');
            if ($partyIdVal) {
                $db = Database::getInstance();
                $partyName  = $db->fetchOne("SELECT name FROM parties WHERE id=?", [$partyIdVal])['name'] ?? '—';
            }
            foreach (self::getWarehouses() as $wh) {
                if ((int)$wh['id'] === $whIdVal) { $branchName = $wh['name']; break; }
            }
            WhatsApp::sale([
                'invoice_no' => $result['invoice_no'],
                'party'      => $partyName ?: '—',
                'branch'     => $branchName ?: '—',
                'total'      => number_format($result['grand_total'] ?? 0, 3),
                'paid'       => number_format(0, 3),
                'currency'   => APP_CURRENCY,
            ]);
            $printMode = $this->input('print_mode');
            if ($printMode === '1') {
                $this->redirect('?page=sales&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            if ($printMode === '2') {
                $this->redirect('?page=sales&action=print&id=' . $result['id'] . '&autoprint=1&thermal=1');
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
        $accounts  = self::getAccounts();
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
        $settings = self::getSettings();

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
                "UPDATE sales SET date=?, subtotal=?, discount=?, grand_total=?, balance=?, status=?, notes=? WHERE id=? AND warehouse_id=?",
                [$newDate, $newSubtotal, $newDiscount, $newGrandTotal, $newBalance, $newStatus, $newNotes ?: null, $id, Auth::warehouseId()]
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

    /**
     * Show scan page to add IMEIs to an existing sale line item (admin only).
     * Used when item was originally sold without IMEI scanning.
     */
    public function scanItemImeis(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }

        $saleId     = $this->inputInt('id', 0, 'get');
        $saleItemId = $this->inputInt('sale_item_id', 0, 'get');

        $db   = Database::getInstance();
        $sale = $this->saleModel->find($saleId);
        if (!$sale || $sale['status'] === 'cancelled') {
            $this->flash('error', 'Sale not available.');
            $this->redirect('?page=sales');
            return;
        }

        $line = $db->fetchOne(
            "SELECT si.*, i.name as item_name, i.sku, i.has_imei,
                    (SELECT COUNT(*) FROM sale_item_imei sii WHERE sii.sale_item_id = si.id) as imei_count
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             WHERE si.id = ? AND si.sale_id = ?",
            [$saleItemId, $saleId]
        );
        if (!$line) {
            $this->flash('error', 'Item line not found.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
            return;
        }
        if (!$line['has_imei']) {
            $this->flash('error', 'This item is not IMEI-tracked.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
            return;
        }

        $existingImeis = $db->fetchAll(
            "SELECT ir.imei FROM sale_item_imei sii
             JOIN imei_records ir ON ir.id = sii.imei_id
             WHERE sii.sale_item_id = ?
             ORDER BY ir.imei",
            [$saleItemId]
        );

        $pageTitle = 'Scan IMEIs — ' . $sale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/scan_imeis.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Process scanned IMEIs for an existing sale_item (admin only).
     */
    public function scanItemImeisStore(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin access required.'); $this->redirect('?page=sales'); return; }
        if (!$this->isPost()) { $this->redirect('?page=sales'); return; }

        $saleId     = $this->inputInt('id');
        $saleItemId = $this->inputInt('sale_item_id');

        $db = Database::getInstance();
        $sale = $this->saleModel->find($saleId);
        if (!$sale || $sale['status'] === 'cancelled') { $this->flash('error', 'Sale not available.'); $this->redirect('?page=sales'); return; }

        $line = $db->fetchOne(
            "SELECT si.*, i.has_imei FROM sale_items si JOIN items i ON i.id = si.item_id WHERE si.id = ? AND si.sale_id = ?",
            [$saleItemId, $saleId]
        );
        if (!$line || !$line['has_imei']) { $this->flash('error', 'Invalid item line.'); $this->redirect("?page=sales&action=edit&id={$saleId}"); return; }

        $rawImeis = $this->input('imeis');
        $imeis    = array_values(array_unique(array_filter(array_map('trim', explode("\n", $rawImeis)))));

        $existingCount = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM sale_item_imei WHERE sale_item_id = ?",
            [$saleItemId]
        )['c'] ?? 0);
        $remaining = (int)$line['quantity'] - $existingCount;

        if (count($imeis) > $remaining) {
            $this->flash('error', "Only {$remaining} more IMEI(s) needed for this line. You scanned " . count($imeis) . ".");
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }
        if (empty($imeis)) {
            $this->flash('error', 'No IMEIs scanned.');
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }

        // Validate IMEIs against item
        $errors = $this->imeiModel->validateList($imeis, (int)$line['item_id']);
        if (!empty($errors)) {
            $this->flash('error', implode(' | ', $errors));
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }

        $whId = (int)$sale['warehouse_id'];

        $db->beginTransaction();
        try {
            foreach ($imeis as $imei) {
                $rec = $db->fetchOne("SELECT id FROM imei_records WHERE imei = ?", [$imei]);
                if (!$rec) {
                    // IMEI not in system — create it as sold for this sale
                    $imeiId = $db->insert(
                        "INSERT INTO imei_records (imei, item_id, warehouse_id, status, sale_id, notes)
                         VALUES (?,?,?,'sold',?,?)",
                        [$imei, $line['item_id'], $whId, $saleId, "Auto-registered during retro IMEI scan for sale_item #{$saleItemId}"]
                    );
                } else {
                    $imeiId = (int)$rec['id'];
                    $aff = $db->execute(
                        "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                        [$saleId, $whId, $imeiId]
                    );
                    if ($aff === 0) throw new Exception("IMEI {$imei} not available for sale.");
                }
                $db->insert("INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)", [$saleItemId, $imeiId]);
            }

            $db->commit();
            $this->logActivity('retro_scan_imeis', 'sale_items', $saleItemId,
                "Scanned " . count($imeis) . " IMEIs for sale_item #{$saleItemId} of {$sale['invoice_no']}");
            $this->flash('success', count($imeis) . ' IMEI(s) linked to this line.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Scan failed: ' . $e->getMessage());
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
        }
    }

    /**
     * Show "Add item" page for an existing sale (admin only).
     * Mini create-style form with IMEI scan support.
     */
    public function addItem(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);

        if (!$sale)                              { $this->flash('error', 'Sale not found.');                $this->redirect('?page=sales');                                     return; }
        if ($sale['status'] === 'cancelled')     { $this->flash('error', 'Cancelled invoices cannot be edited.'); $this->redirect("?page=sales&action=detail&id={$id}");          return; }

        $pageTitle = 'Add Item to ' . $sale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/add_item.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Process new item additions (admin only) — same IMEI/stock logic as create.
     */
    public function addItemStore(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->isPost()) { $this->redirect('?page=sales'); return; }

        $id   = $this->inputInt('id');
        $sale = $this->saleModel->find($id);
        if (!$sale || $sale['status'] === 'cancelled') {
            $this->flash('error', 'Invalid sale.');
            $this->redirect('?page=sales');
            return;
        }

        $db   = Database::getInstance();
        $whId = (int)$sale['warehouse_id'];

        $rawItems = $_POST['items'] ?? [];
        $items    = [];

        // Validate
        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;
            $itemId = (int)$row['item_id'];
            $qty    = (int)$row['quantity'];
            $price  = (float)($row['unit_price'] ?? 0);

            if ($qty <= 0)   { $this->flash('error', 'Quantity must be greater than zero.'); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }
            if ($price <= 0) { $this->flash('error', 'Price must be greater than zero.');    $this->redirect("?page=sales&action=addItem&id={$id}"); return; }

            $imeis = [];
            if (!empty($row['imeis'])) {
                $imeis = array_values(array_unique(array_filter(array_map('trim', explode("\n", $row['imeis'])))));
            }

            $itemInfo = $db->fetchOne(
                "SELECT name, has_imei, COALESCE(imei_optional,0) as imei_optional FROM items WHERE id = ?",
                [$itemId]
            );
            if (!$itemInfo) { $this->flash('error', 'Invalid item.'); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }

            if ($itemInfo['has_imei'] && empty($itemInfo['imei_optional']) && count($imeis) !== $qty) {
                $this->flash('error', "Item \"{$itemInfo['name']}\": IMEI count must match quantity ({$qty}).");
                $this->redirect("?page=sales&action=addItem&id={$id}");
                return;
            }

            if (!empty($imeis)) {
                $errors = $this->imeiModel->validateList($imeis, $itemId);
                if (!empty($errors)) { $this->flash('error', implode(' | ', $errors)); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }
            }

            $items[] = compact('itemId', 'qty', 'price', 'imeis');
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one item.');
            $this->redirect("?page=sales&action=addItem&id={$id}");
            return;
        }

        $db->beginTransaction();
        try {
            $addedSubtotal = 0;

            foreach ($items as $it) {
                $lineTotal = round($it['qty'] * $it['price'], 3);

                // Insert sale_item
                $costPrice = (float)($db->fetchOne("SELECT purchase_price FROM items WHERE id = ?", [$it['itemId']])['purchase_price'] ?? 0);
                $saleItemId = $db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, cost_price, discount, tax, total)
                     VALUES (?,?,?,?,?,0,0,?)",
                    [$id, $it['itemId'], $it['qty'], $it['price'], $costPrice, $lineTotal]
                );

                // Decrement stock atomically
                $aff = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$it['qty'], $it['itemId'], $whId, $it['qty']]
                );
                if ($aff === 0) {
                    $stk = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$it['itemId'], $whId]);
                    throw new Exception("Insufficient stock (have " . ($stk['quantity'] ?? 0) . ", need {$it['qty']}).");
                }

                // Mark IMEIs sold + link
                foreach ($it['imeis'] as $imei) {
                    $rec = $db->fetchOne("SELECT id FROM imei_records WHERE imei = ?", [$imei]);
                    if (!$rec) {
                        $imeiId = $db->insert(
                            "INSERT INTO imei_records (imei, item_id, warehouse_id, status, sale_id) VALUES (?,?,?,'sold',?)",
                            [$imei, $it['itemId'], $whId, $id]
                        );
                    } else {
                        $imeiId = (int)$rec['id'];
                        $aff = $db->execute(
                            "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                            [$id, $whId, $imeiId]
                        );
                        if ($aff === 0) throw new Exception("IMEI {$imei} not available for sale.");
                    }
                    $db->insert("INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)", [$saleItemId, $imeiId]);
                }

                $addedSubtotal += $lineTotal;
            }

            // Recalculate sale totals
            $newSubtotal   = (float)$sale['subtotal'] + $addedSubtotal;
            $discount      = (float)$sale['discount'];
            $newGrandTotal = $newSubtotal - $discount;
            $paid          = (float)$sale['paid_amount'];
            $newBalance    = max(0, $newGrandTotal - $paid);

            if ($newBalance < 0.001)      { $newStatus = 'paid'; $newBalance = 0; }
            elseif ($paid > 0)            { $newStatus = 'partial'; }
            else                          { $newStatus = $sale['status'] === 'paid' ? 'confirmed' : $sale['status']; }

            $db->execute(
                "UPDATE sales SET subtotal=?, grand_total=?, balance=?, status=? WHERE id=?",
                [$newSubtotal, $newGrandTotal, $newBalance, $newStatus, $id]
            );

            $db->commit();
            $this->logActivity('add_items_to_sale', 'sales', $id, "Added " . count($items) . " item(s) to {$sale['invoice_no']}");
            $this->flash('success', "Added " . count($items) . " item(s) to {$sale['invoice_no']}.");
            $this->redirect("?page=sales&action=detail&id={$id}");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Add failed: ' . $e->getMessage());
            $this->redirect("?page=sales&action=addItem&id={$id}");
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
             WHERE ir.imei = ? AND ir.warehouse_id = ?",
            [$imei, Auth::warehouseId()]
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
             JOIN sales s ON s.id = si.sale_id
             WHERE si.sale_id = ? AND s.warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );

        echo json_encode($items);
    }
}
