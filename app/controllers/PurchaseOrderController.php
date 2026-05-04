<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';

class PurchaseOrderController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // ─── List all POs ─────────────────────────────────────────────────────────
    public function index(): void {
        Auth::authorize('purchases', 'view');

        $search   = $this->input('search',    '', 'get');
        $status   = $this->input('status',    '', 'get');
        $fromDate = $this->input('from_date', '', 'get');
        $toDate   = $this->input('to_date',   '', 'get');

        $where  = "WHERE 1=1";
        $params = [];

        if (Auth::warehouseId()) {
            $where .= " AND po.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }
        if ($search) {
            $where .= " AND (po.po_no LIKE ? OR p.name LIKE ? OR po.supplier_ref LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($status) {
            $where .= " AND po.status = ?";
            $params[] = $status;
        }
        if ($fromDate) { $where .= " AND po.date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $where .= " AND po.date <= ?"; $params[] = $toDate;   }

        $orders = $this->db->fetchAll(
            "SELECT po.*, p.name as supplier_name, w.name as warehouse_name
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             JOIN warehouses w ON w.id = po.warehouse_id
             $where
             ORDER BY po.created_at DESC",
            $params
        );

        $pageTitle = 'Purchase Orders';
        $page      = 'purchaseorders';

        ob_start();
        include __DIR__ . '/../views/purchase_orders/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Create form ──────────────────────────────────────────────────────────
    public function create(): void {
        Auth::authorize('purchases', 'add');

        $suppliers  = $this->db->fetchAll(
            "SELECT id, name FROM parties WHERE type IN ('supplier','both') AND is_active = 1 ORDER BY name"
        );
        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $nextPoNo   = $this->nextPoNo();

        $pageTitle = 'New Purchase Order';
        $page      = 'purchaseorders';

        ob_start();
        include __DIR__ . '/../views/purchase_orders/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Save PO ──────────────────────────────────────────────────────────────
    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=purchaseorders&action=create'); }

        $rawItems = $_POST['items'] ?? [];
        $items    = [];

        $currency     = $this->input('currency') ?: 'AED';
        $exchangeRate = (float)($this->input('exchange_rate') ?: 1);
        if ($exchangeRate <= 0) $exchangeRate = 1;

        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;
            $qty      = (int)$row['quantity'];
            $kwdTotal = (float)($row['kwd_total'] ?? 0);
            $kwdPrice = (float)($row['kwd_price'] ?? 0);
            // Prefer total if provided, back-calc price
            if ($kwdTotal > 0 && $qty > 0) {
                $kwdPrice = round($kwdTotal / $qty, 3);
            } elseif ($kwdPrice > 0 && $qty > 0) {
                $kwdTotal = round($kwdPrice * $qty, 3);
            }
            $foreignPrice = $exchangeRate > 0 ? round($kwdPrice / $exchangeRate, 3) : $kwdPrice;
            $foreignTotal = $exchangeRate > 0 ? round($kwdTotal / $exchangeRate, 3) : $kwdTotal;
            $items[] = [
                'item_id'           => (int)$row['item_id'],
                'quantity'          => $qty,
                'unit_price_foreign'=> $foreignPrice,
                'unit_price_kwd'    => $kwdPrice,
                'total_foreign'     => $foreignTotal,
                'total_kwd'         => $kwdTotal,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Add at least one item.');
            $this->redirect('?page=purchaseorders&action=create');
        }

        $subtotalForeign = array_sum(array_column($items, 'total_foreign'));
        $subtotalKwd     = array_sum(array_column($items, 'total_kwd'));
        $paidKwd         = $this->inputFloat('paid_kwd');
        $paidForeign     = $exchangeRate > 0 ? round($paidKwd / $exchangeRate, 3) : $paidKwd;
        $warehouseId     = $this->inputInt('warehouse_id') ?: Auth::warehouseId();
        
        $accountId = $this->inputInt('account_id') ?: null;
        if ($paidKwd > 0 && !$accountId) {
            $this->flash('error', 'Please select an account for the paid amount.');
            $this->redirect('?page=purchaseorders&action=create');
            return;
        }

        $status = $paidKwd >= $subtotalKwd ? 'paid' : 'draft';

        $this->db->beginTransaction();
        try {

            $poId = $this->db->insert(
                "INSERT INTO purchase_orders
                    (po_no, party_id, warehouse_id, date, currency, exchange_rate,
                     subtotal_foreign, subtotal_kwd, paid_foreign, paid_kwd,
                     status, supplier_ref, notes, created_by, account_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $this->nextPoNo(),
                    $this->inputInt('party_id'),
                    $warehouseId,
                    $this->input('date') ?: date('Y-m-d'),
                    $currency,
                    $exchangeRate,
                    $subtotalForeign, $subtotalKwd,
                    $paidForeign, $paidKwd,
                    $status,
                    $this->input('supplier_ref') ?: null,
                    $this->input('notes') ?: null,
                    Auth::id(),
                    $accountId,
                ]
            );

            foreach ($items as $item) {
                $this->db->insert(
                    "INSERT INTO purchase_order_items
                        (po_id, item_id, quantity, unit_price_foreign, unit_price_kwd, total_foreign, total_kwd)
                     VALUES (?,?,?,?,?,?,?)",
                    [$poId, $item['item_id'], $item['quantity'],
                     $item['unit_price_foreign'], $item['unit_price_kwd'],
                     $item['total_foreign'], $item['total_kwd']]
                );
            }

            // Deduct paid amount from selected account and record payment
            if ($paidKwd > 0 && $accountId) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paidKwd, $accountId]
                );
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $poId, $this->inputInt('party_id'), 'out', $accountId, $paidKwd, 'bank',
                     $this->input('date') ?: date('Y-m-d'), $warehouseId, Auth::id()]
                );
            }

            $this->db->commit();
            $this->flash('success', 'Purchase Order saved successfully.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error saving PO: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=create');
        }
    }

    // ─── Show PO detail ───────────────────────────────────────────────────────
    public function show(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $po = $this->db->fetchOne(
            "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                    w.name as warehouse_name, u.name as created_by_name,
                    pur.invoice_no as purchase_invoice_no
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             JOIN warehouses w ON w.id = po.warehouse_id
             LEFT JOIN users u ON u.id = po.created_by
             LEFT JOIN purchases pur ON pur.id = po.converted_to
             WHERE po.id = ?",
            [$id]
        );

        if (!$po) { $this->flash('error', 'Purchase Order not found.'); $this->redirect('?page=purchaseorders'); }

        $items = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.sku, i.unit
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?",
            [$id]
        );

        $accounts = self::getAccounts();

        // One-time token to prevent double "Mark as Paid"
        if (empty($_SESSION['po_markpaid_nonce']) || !is_array($_SESSION['po_markpaid_nonce'])) {
            $_SESSION['po_markpaid_nonce'] = [];
        }
        $_SESSION['po_markpaid_nonce'][$id] = bin2hex(random_bytes(16));
        $poMarkPaidNonce = $_SESSION['po_markpaid_nonce'][$id];

        $pageTitle = 'PO — ' . $po['po_no'];
        $page      = 'purchaseorders';

        ob_start();
        include __DIR__ . '/../views/purchase_orders/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Mark as paid (goods not yet received) ────────────────────────────────
    public function markPaid(): void {
        Auth::authorize('purchases', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id        = $this->inputInt('id');
        $accountId = $this->inputInt('account_id');

        $postedNonce = isset($_POST['po_markpaid_nonce']) ? trim((string)$_POST['po_markpaid_nonce']) : '';
        $sessNonce   = $_SESSION['po_markpaid_nonce'][$id] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This PO payment request was already submitted or expired. Please refresh the page and check the PO status.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        unset($_SESSION['po_markpaid_nonce'][$id]);

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);

        if (!$po || $po['status'] !== 'draft') {
            $this->flash('error', 'Only draft POs can be marked as paid.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        if (!$accountId) {
            $this->flash('error', 'Please select an account to deduct payment from.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Only deduct the unpaid portion (accounts for partial payments during PO creation)
            $alreadyPaid = (float)($po['paid_kwd'] ?? 0);
            $totalKwd    = (float)$po['subtotal_kwd'];
            $deductAmount = $totalKwd - $alreadyPaid;

            $this->db->execute(
                "UPDATE purchase_orders
                 SET status='paid', paid_foreign=subtotal_foreign, paid_kwd=subtotal_kwd, account_id=?
                 WHERE id=?",
                [$accountId, $id]
            );
            if ($deductAmount > 0.001) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$deductAmount, $accountId]
                );
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $id, $po['party_id'], 'out', $accountId, $deductAmount, 'bank',
                     date('Y-m-d'), $po['warehouse_id'], Auth::id()]
                );
            }
            $this->db->commit();
            $this->flash('success', 'Purchase Order marked as Paid and account updated. Waiting for goods to arrive.');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
        }
        $this->redirect('?page=purchaseorders&action=show&id=' . $id);
    }

    // ─── Convert PO to Purchase Invoice ──────────────────────────────────────
    public function convert(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=purchaseorders'); return; }
        $id = $this->inputInt('id');

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po) { $this->flash('error', 'PO not found.'); $this->redirect('?page=purchaseorders'); }

        if ($po['status'] === 'converted') {
            $this->flash('error', 'This PO is already converted to a Purchase Invoice.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }
        if (!in_array($po['status'], ['paid','draft'])) {
            $this->flash('error', 'Only paid or draft POs can be converted.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }

        $items = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.has_imei
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?",
            [$id]
        );

        if (empty($items)) {
            $this->flash('error', 'No items on this PO.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }

        $this->db->beginTransaction();
        try {
            // Generate purchase invoice number
            $last      = $this->db->fetchOne("SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $num       = $last ? (int)substr($last['invoice_no'], strlen(PURCHASE_PREFIX)) : 0;
            $invoiceNo = PURCHASE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

            $subtotal   = (float)$po['subtotal_kwd'];
            $paid       = (float)$po['paid_kwd'];
            $balance    = max(0, $subtotal - $paid);
            $status     = $balance < 0.001 ? 'paid' : 'partial';

            $purchaseId = $this->db->insert(
                "INSERT INTO purchases
                    (invoice_no, party_id, warehouse_id, date, subtotal, discount, tax,
                     grand_total, paid_amount, balance, status,
                     supplier_invoice_no, notes, created_by)
                 VALUES (?,?,?,?,?,0,0,?,?,?,?,?,?,?)",
                [
                    $invoiceNo,
                    $po['party_id'],
                    $po['warehouse_id'],
                    date('Y-m-d'),              // today = date goods received
                    $subtotal, $subtotal,
                    $paid, $balance, $status,
                    $po['supplier_ref'] ?: $po['po_no'],
                    "Converted from PO: {$po['po_no']}. Currency: {$po['currency']} @ rate {$po['exchange_rate']}. " . ($po['notes'] ?: ''),
                    Auth::id(),
                ]
            );

            // Insert purchase items and update stock
            foreach ($items as $item) {
                $lineTotal = round($item['unit_price_kwd'] * $item['quantity'], 3);
                $this->db->insert(
                    "INSERT INTO purchase_items (purchase_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$purchaseId, $item['item_id'], $item['quantity'], $item['unit_price_kwd'], $lineTotal]
                );

                // Add stock
                $this->db->execute(
                    "INSERT INTO stock (item_id, warehouse_id, quantity)
                     VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                    [$item['item_id'], $po['warehouse_id'], $item['quantity'], $item['quantity']]
                );

                // Update item purchase price with KWD price
                $this->db->execute(
                    "UPDATE items SET purchase_price = ? WHERE id = ?",
                    [$item['unit_price_kwd'], $item['item_id']]
                );
            }

            // Update PO status
            $this->db->execute(
                "UPDATE purchase_orders SET status='converted', converted_to=? WHERE id=?",
                [$purchaseId, $id]
            );

            // Migrate any existing PO-payment rows to ref_type='purchase' so party balance counts them.
            // (Fix B inserts payment rows with ref_type='purchase_order' at PO save/markPaid time.)
            $this->db->execute(
                "UPDATE payments SET ref_type='purchase', ref_id=? WHERE ref_type='purchase_order' AND ref_id=?",
                [$purchaseId, $id]
            );

            // Safety net: if PO had paid_kwd > 0 but no payment row exists yet (legacy PO before Fix B),
            // insert one now so the supplier balance reflects the payment.
            if ($paid > 0) {
                $hasPayment = $this->db->fetchOne(
                    "SELECT id FROM payments WHERE ref_type='purchase' AND ref_id=?",
                    [$purchaseId]
                );
                if (!$hasPayment) {
                    $last  = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                    $num   = $last ? (int) substr($last['payment_no'], 4) : 0;
                    $payNo = 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
                    $accId = (int)($po['account_id'] ?? 0) ?: (int)($this->db->fetchOne("SELECT id FROM accounts WHERE is_active=1 ORDER BY sort_order LIMIT 1")['id'] ?? 0);
                    $this->db->insert(
                        "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                        [$payNo, 'purchase', $purchaseId, $po['party_id'], 'out', $accId, $paid, 'bank', date('Y-m-d'), $po['warehouse_id'], Auth::id()]
                    );
                }
            }

            // IMPORTANT: Purchases list/detail relies on purchases.paid_amount/balance/status.
            // After migrating/inserting payment rows, sync purchase header from its payments.
            $paidSumRow = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE ref_type='purchase' AND ref_id=?",
                [$purchaseId]
            );
            $paidSum = (float)($paidSumRow['total'] ?? 0);
            $newBalance = max(0, (float)$subtotal - $paidSum);
            $newStatus  = $newBalance < 0.001 ? 'paid' : ($paidSum > 0 ? 'partial' : 'confirmed');
            $this->db->execute(
                "UPDATE purchases SET paid_amount=?, balance=?, status=? WHERE id=?",
                [round($paidSum, 3), round($newBalance, 3), $newStatus, $purchaseId]
            );

            $this->db->commit();

            // Check if any items need IMEI registration
            $hasImeiItems = false;
            foreach ($items as $item) {
                if ($item['has_imei']) { $hasImeiItems = true; break; }
            }

            if ($hasImeiItems) {
                $this->flash('success', "Converted to Purchase Invoice {$invoiceNo}. Now scan IMEIs for received items.");
                $this->redirect('?page=imei&action=scanPurchase&purchase_id=' . $purchaseId);
            } else {
                $this->flash('success', "Converted to Purchase Invoice {$invoiceNo}. Stock updated.");
                $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Conversion failed: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }
    }

    // ─── Cancel PO ────────────────────────────────────────────────────────────
    public function cancel(): void {
        Auth::authorize('purchases', 'delete');
        // AUDIT FIX S5: Require POST for destructive action
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if ($po && $po['status'] !== 'converted') {
            $this->db->beginTransaction();
            try {
                // Reverse account deduction for any paid amount (full or partial)
                if ($po['account_id'] && (float)$po['paid_kwd'] > 0) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [(float)$po['paid_kwd'], $po['account_id']]
                    );
                }
                
                // Delete associated payment records so supplier balance stays accurate
                $this->db->execute("DELETE FROM payments WHERE ref_type='purchase_order' AND ref_id=?", [$id]);
                
                // Zero out paid amounts to prevent state leak on reactivation
                $this->db->execute(
                    "UPDATE purchase_orders SET status='cancelled', paid_kwd=0, paid_foreign=0, account_id=NULL WHERE id=?", 
                    [$id]
                );
                
                $this->db->commit();
                $this->flash('success', 'Purchase Order cancelled.' . ((float)$po['paid_kwd'] > 0 ? ' Account balance restored.' : ''));
            } catch (Exception $e) {
                $this->db->rollback();
                $this->flash('error', 'Cancellation failed. Please try again.');
            }
        }
        $this->redirect('?page=purchaseorders');
    }

    // Reactivate cancelled PO (admin only)
    public function reactivate(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if ($po && $po['status'] === 'cancelled') {
            $this->db->execute("UPDATE purchase_orders SET status='draft' WHERE id=?", [$id]);
            $this->logActivity('reactivate_po', 'purchase_orders', $id, "Reactivated {$po['po_no']}");
            $this->flash('success', "PO {$po['po_no']} reactivated to Draft.");
        } else {
            $this->flash('error', 'Only cancelled POs can be reactivated.');
        }
        $this->redirect('?page=purchaseorders&action=show&id=' . $id);
    }

    // Edit PO form (admin only)
    public function edit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id', 0, 'get');
        $po = $this->db->fetchOne(
            "SELECT po.*, p.name as supplier_name
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             WHERE po.id = ?", [$id]
        );
        if (!$po) { $this->flash('error', 'PO not found.'); $this->redirect('?page=purchaseorders'); return; }
        if ($po['status'] === 'converted') {
            $this->flash('error', 'Converted POs cannot be edited.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $po['items'] = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.sku
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?
             ORDER BY poi.id", [$id]
        );

        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $pageTitle  = 'Edit: ' . $po['po_no'];
        $page       = 'purchaseorders';

        ob_start();
        include __DIR__ . '/../views/purchase_orders/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update PO (admin only)
    public function update(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (!$this->isPost()) { $this->redirect('?page=purchaseorders'); return; }

        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po || $po['status'] === 'converted') {
            $this->flash('error', 'Cannot edit this PO.');
            $this->redirect('?page=purchaseorders');
            return;
        }

        $currency     = $this->input('currency') ?: $po['currency'];
        $exchangeRate = (float)($this->input('exchange_rate') ?: $po['exchange_rate']);
        if ($exchangeRate <= 0) $exchangeRate = 1;

        $rawItems = $_POST['items'] ?? [];
        $items = [];
        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;
            $qty      = (int)$row['quantity'];
            $kwdTotal = (float)($row['kwd_total'] ?? 0);
            $kwdPrice = (float)($row['kwd_price'] ?? 0);
            if ($kwdTotal > 0 && $qty > 0) {
                $kwdPrice = round($kwdTotal / $qty, 3);
            } elseif ($kwdPrice > 0 && $qty > 0) {
                $kwdTotal = round($kwdPrice * $qty, 3);
            }
            $foreignPrice = $exchangeRate > 0 ? round($kwdPrice / $exchangeRate, 3) : $kwdPrice;
            $foreignTotal = $exchangeRate > 0 ? round($kwdTotal / $exchangeRate, 3) : $kwdTotal;
            $items[] = [
                'item_id'            => (int)$row['item_id'],
                'quantity'           => $qty,
                'unit_price_foreign' => $foreignPrice,
                'unit_price_kwd'     => $kwdPrice,
                'total_foreign'      => $foreignTotal,
                'total_kwd'          => $kwdTotal,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Add at least one item.');
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
            return;
        }

        $subtotalForeign = array_sum(array_column($items, 'total_foreign'));
        $subtotalKwd     = array_sum(array_column($items, 'total_kwd'));
        $paidKwd         = $this->inputFloat('paid_kwd');
        $paidForeign     = $exchangeRate > 0 ? round($paidKwd / $exchangeRate, 3) : $paidKwd;
        $status          = $paidKwd >= $subtotalKwd ? 'paid' : 'draft';

        $newAccountId = $this->inputInt('account_id') ?: null;
        if ($paidKwd > 0 && !$newAccountId) {
            $this->flash('error', 'Please select an account for the paid amount.');
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
            return;
        }

        // Account handling: detect change in paid amount and deduct/refund accordingly
        $oldPaidKwd  = (float)$po['paid_kwd'];
        $oldAccountId = (int)($po['account_id'] ?? 0);
        $paidDiff = round($paidKwd - $oldPaidKwd, 3);

        $this->db->beginTransaction();
        try {
            // Update PO header
            $this->db->execute(
                "UPDATE purchase_orders SET
                    date=?, currency=?, exchange_rate=?,
                    subtotal_foreign=?, subtotal_kwd=?, paid_foreign=?, paid_kwd=?,
                    status=?, supplier_ref=?, notes=?, account_id=?
                 WHERE id=?",
                [
                    $this->input('date') ?: $po['date'],
                    $currency, $exchangeRate,
                    $subtotalForeign, $subtotalKwd, $paidForeign, $paidKwd,
                    $status,
                    $this->input('supplier_ref') ?: null,
                    $this->input('notes') ?: null,
                    $newAccountId,
                    $id,
                ]
            );

            // Adjust account balances if paid amount changed
            if ($paidDiff != 0) {
                // Reverse old deduction if account changed or amount changed
                if ($oldAccountId && $oldPaidKwd > 0) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [$oldPaidKwd, $oldAccountId]
                    );
                }
                // Apply new deduction
                if ($newAccountId && $paidKwd > 0) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                        [$paidKwd, $newAccountId]
                    );
                }
            } elseif ($newAccountId !== $oldAccountId && $paidKwd > 0) {
                // Same amount but account changed — move deduction between accounts
                if ($oldAccountId) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [$paidKwd, $oldAccountId]
                    );
                }
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paidKwd, $newAccountId]
                );
            }

            // Sync payments audit record — delete old, insert fresh if paid
            $this->db->execute(
                "DELETE FROM payments WHERE ref_type = 'purchase_order' AND ref_id = ?",
                [$id]
            );
            if ($paidKwd > 0 && $newAccountId) {
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $id, $po['party_id'], 'out', $newAccountId, $paidKwd, 'bank',
                     $this->input('date') ?: $po['date'], $po['warehouse_id'], Auth::id()]
                );
            }

            // Delete old items and re-insert
            $this->db->execute("DELETE FROM purchase_order_items WHERE po_id = ?", [$id]);
            foreach ($items as $item) {
                $this->db->insert(
                    "INSERT INTO purchase_order_items
                        (po_id, item_id, quantity, unit_price_foreign, unit_price_kwd, total_foreign, total_kwd)
                     VALUES (?,?,?,?,?,?,?)",
                    [$id, $item['item_id'], $item['quantity'],
                     $item['unit_price_foreign'], $item['unit_price_kwd'],
                     $item['total_foreign'], $item['total_kwd']]
                );
            }

            $this->db->commit();
            $this->logActivity('edit_po', 'purchase_orders', $id, "Edited {$po['po_no']}");
            $this->flash('success', "PO {$po['po_no']} updated.");
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
        }
    }

    // ─── AJAX item search ─────────────────────────────────────────────────────
    public function searchItems(): void {
        header('Content-Type: application/json');
        $q    = trim($this->input('q', '', 'get'));
        $whId = $this->inputInt('warehouse_id', 0, 'get');
        if (strlen($q) < 1) { echo json_encode([]); return; }

        $like  = "%$q%";
        $items = $this->db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.purchase_price, i.price_aed, i.price_usd, i.unit,
                    COALESCE(s.quantity, 0) as current_stock
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1 AND (i.name LIKE ? OR i.sku LIKE ?)
             ORDER BY i.name ASC LIMIT 15",
            [$whId, $like, $like]
        );
        echo json_encode($items);
    }

    // ─── AJAX: price history for an item ─────────────────────────────────────
    public function itemHistory(): void {
        header('Content-Type: application/json');
        $itemId = $this->inputInt('item_id', 0, 'get');
        if (!$itemId) { echo json_encode([]); return; }

        $rows = $this->db->fetchAll(
            "SELECT po.date, po.po_no, p.name AS supplier,
                    poi.quantity, poi.unit_price_kwd, poi.total_kwd,
                    po.currency
             FROM purchase_order_items poi
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = po.party_id
             WHERE poi.item_id = ? AND po.status NOT IN ('cancelled')
             ORDER BY po.date DESC, po.id DESC
             LIMIT 6",
            [$itemId]
        );
        echo json_encode($rows);
    }

        // ─── Helpers ──────────────────────────────────────────────────────────────
    private function nextPoNo(): string {
        $last = $this->db->fetchOne("SELECT po_no FROM purchase_orders ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int)substr($last['po_no'], 3) : 0;  // PO-000001
        return 'PO-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }
}
