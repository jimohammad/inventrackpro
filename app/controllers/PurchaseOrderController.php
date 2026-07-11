<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/PurchaseOrderConverter.php';
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
        $itemQ    = $this->input('item',      '', 'get');
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
        if ($itemQ !== '') {
            if (ctype_digit((string)$itemQ)) {
                // Selected from dropdown: match by exact item id
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_order_items poi
                    WHERE poi.po_id = po.id AND poi.item_id = ?
                )";
                $params[] = (int)$itemQ;
            } else {
                // Fallback (e.g. old bookmarked URLs): match by name/SKU text
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_order_items poi
                    JOIN items i ON i.id = poi.item_id
                    WHERE poi.po_id = po.id AND (i.name LIKE ? OR i.sku LIKE ?)
                )";
                $itemLike = "%$itemQ%";
                $params[] = $itemLike; $params[] = $itemLike;
            }
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
        $this->reconcileDraftPaidOrders($orders);

        // Items for the search dropdown
        $allItems = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name ASC"
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
        // Foreign price is a manual record/reminder only — no KWD conversion.
        $exchangeRate = 1;

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
            // Foreign price is typed by the user (AED/USD), stored as-is for reference.
            $foreignPrice = round((float)($row['foreign_price'] ?? 0), 3);
            $foreignTotal = round($foreignPrice * $qty, 3);
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
        $otherChargesKwd = max(0, round($this->inputFloat('other_charges_kwd'), 3));
        $totalKwd        = round($subtotalKwd + $otherChargesKwd, 3);
        $paidKwd         = $this->inputFloat('paid_kwd');
        $paidForeign     = 0; // payment is recorded in KWD only
        $warehouseId     = $this->inputInt('warehouse_id') ?: Auth::warehouseId();
        
        $accountId = $this->inputInt('account_id') ?: null;
        if ($paidKwd > 0 && !$accountId) {
            $this->flash('error', 'Please select an account for the paid amount.');
            $this->redirect('?page=purchaseorders&action=create');
            return;
        }

        $status = $this->resolvePoStatus($paidKwd, $totalKwd);

        $this->db->beginTransaction();
        try {

            $poId = $this->db->insert(
                "INSERT INTO purchase_orders
                    (po_no, party_id, warehouse_id, date, currency, exchange_rate,
                     subtotal_foreign, subtotal_kwd, other_charges_kwd, paid_foreign, paid_kwd,
                     status, supplier_ref, notes, created_by, account_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $this->nextPoNo(),
                    $this->inputInt('party_id'),
                    $warehouseId,
                    $this->input('date') ?: date('Y-m-d'),
                    $currency,
                    $exchangeRate,
                    $subtotalForeign, $subtotalKwd, $otherChargesKwd,
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
            self::clearDashboardCache($warehouseId);
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

        $po = $this->reconcileConvertedPoAfterCancelledPurchase($po);
        if (PurchaseOrderConverter::reconcilePoPaymentLink($this->db, (int) $po['id'])) {
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $refetched = $this->db->fetchOne(
                "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                        w.name as warehouse_name, u.name as created_by_name,
                        pur.invoice_no as purchase_invoice_no
                 FROM purchase_orders po
                 JOIN parties p ON p.id = po.party_id
                 JOIN warehouses w ON w.id = po.warehouse_id
                 LEFT JOIN users u ON u.id = po.created_by
                 LEFT JOIN purchases pur ON pur.id = po.converted_to
                 WHERE po.id = ?",
                [(int) $po['id']]
            );
            if ($refetched) {
                $this->flash('info', 'Bank payment re-linked to ' . ($po['po_no'] ?? 'PO') . '.');
                $po = $refetched;
            }
        }
        $po = $this->reconcilePoPaidStatus($po);

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

        $openShipment = $this->db->fetchOne(
            "SELECT s.id, s.shipment_no, s.status
             FROM shipment_purchase_orders spo
             JOIN shipments s ON s.id = spo.shipment_id
             WHERE spo.po_id = ? AND s.status != 'applied'
             ORDER BY s.id DESC LIMIT 1",
            [$id]
        );

        $canReverseToPo = false;
        if (($po['status'] ?? '') === 'converted' && !empty($po['converted_to'])) {
            $linkedPurchase = $this->db->fetchOne(
                "SELECT id, status FROM purchases WHERE id = ?",
                [(int) $po['converted_to']]
            );
            $appliedShipment = $this->db->fetchOne(
                "SELECT s.id FROM shipment_purchases sp
                 JOIN shipments s ON s.id = sp.shipment_id
                 WHERE sp.purchase_id = ? AND s.status = 'applied'
                 LIMIT 1",
                [(int) $po['converted_to']]
            );
            $canReverseToPo = $linkedPurchase
                && ($linkedPurchase['status'] ?? '') !== 'cancelled'
                && !$appliedShipment;
        }

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
            $alreadyPaid  = (float)($po['paid_kwd'] ?? 0);
            $totalKwd     = (float)$po['subtotal_kwd'] + (float)($po['other_charges_kwd'] ?? 0);
            $deductAmount = round($totalKwd - $alreadyPaid, 3);
            if ($deductAmount <= $this->poPaidTolerance($totalKwd)) {
                $deductAmount = 0;
            }

            $this->db->execute(
                "UPDATE purchase_orders
                 SET status='paid', paid_foreign=subtotal_foreign,
                     paid_kwd=(subtotal_kwd + COALESCE(other_charges_kwd, 0)), account_id=?
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
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
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

        $this->db->beginTransaction();
        try {
            $purchaseId = PurchaseOrderConverter::convert($this->db, $id);
            $invoiceNo  = $this->db->fetchOne("SELECT invoice_no FROM purchases WHERE id = ?", [$purchaseId])['invoice_no'] ?? '';

            $this->db->commit();
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));

            $hasImeiItems = (int) $this->db->fetchOne(
                "SELECT COUNT(*) as c FROM purchase_items pi
                 JOIN items i ON i.id = pi.item_id
                 WHERE pi.purchase_id = ? AND i.has_imei = 1",
                [$purchaseId]
            )['c'] > 0;

            if ($hasImeiItems) {
                $this->flash('success', "Converted to Purchase Invoice {$invoiceNo}. Now scan IMEIs for received items.");
                $this->redirect('?page=imei&action=scanPurchase&purchase_id=' . $purchaseId);
            }
            $this->flash('success', "Converted to Purchase Invoice {$invoiceNo}. Stock updated.");
            $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Conversion failed: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }
    }

    // ─── Reverse converted PO back to open PO (cancel linked purchase) ────────
    public function reverseToPo(): void {
        Auth::authorize('purchases', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=purchaseorders');
            return;
        }

        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po) {
            $this->flash('error', 'PO not found.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if ((int) ($po['warehouse_id'] ?? 0) !== Auth::warehouseId()) {
            $this->flash('error', 'This PO belongs to a different warehouse.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (($po['status'] ?? '') !== 'converted') {
            $this->flash('error', 'Only converted POs can be reversed.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $purchaseId = (int) ($po['converted_to'] ?? 0);
        if ($purchaseId <= 0) {
            $this->flash('error', 'No linked purchase invoice found for this PO.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        require_once __DIR__ . '/../models/Purchase.php';
        $purchaseModel = new Purchase($this->db);

        try {
            $result = $purchaseModel->reverseToPoWithReversals($purchaseId, (int) Auth::warehouseId());
            $this->logActivity(
                'reverse_purchase_to_po',
                'purchase_orders',
                $id,
                'Reversed PO ' . ($po['po_no'] ?? '') . ' — cancelled ' . ($result['invoice_no'] ?? '')
            );
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $this->flash(
                'success',
                'Purchase ' . ($result['invoice_no'] ?? '')
                . ' cancelled. ' . ($po['po_no'] ?? 'PO') . ' is open again — edit and convert when ready.'
            );
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        } catch (Exception $e) {
            if ($e->getMessage() === 'NOT_FROM_PO') {
                $this->flash('error', 'Linked purchase could not be reversed to this PO.');
            } elseif ($e->getMessage() === 'ALREADY_CANCELLED') {
                PurchaseOrderConverter::reopenConvertedPo($this->db, $id);
                self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
                $this->flash('info', 'Purchase was already cancelled — PO reopened.');
                $this->redirect('?page=purchaseorders&action=show&id=' . $id);
                return;
            } elseif (str_starts_with($e->getMessage(), 'Cannot cancel: approved purchase return')
                || str_starts_with($e->getMessage(), 'Cannot reverse:')) {
                $this->flash('error', $e->getMessage());
            } else {
                $this->flash('error', 'Failed to reverse: ' . $e->getMessage());
            }
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
                // Reverse each PO payment back to its original account (supports partial multi-account pays).
                // Mirror Sale::cancel(): reverse account effect first, then delete payment rows.
                $poPays = $this->db->fetchAll(
                    "SELECT id, account_id, amount, payment_type
                     FROM payments
                     WHERE ref_type = 'purchase_order' AND ref_id = ?
                     FOR UPDATE",
                    [$id]
                );
                foreach ($poPays as $pay) {
                    $accId = (int)($pay['account_id'] ?? 0);
                    $amt   = (float)($pay['amount'] ?? 0);
                    if ($accId <= 0 || $amt <= 0) {
                        continue;
                    }
                    // payment_type='out' means we previously deducted from account; cancelling should restore it.
                    // If a legacy row has payment_type='in', reverse the opposite way.
                    $ptype = (string)($pay['payment_type'] ?? 'out');
                    $delta = ($ptype === 'out') ? $amt : -$amt;
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [$delta, $accId]
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
                self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
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
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
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
        // Foreign price is a manual record/reminder only — no KWD conversion.
        $exchangeRate = 1;

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
            // Foreign price is typed by the user (AED/USD), stored as-is for reference.
            $foreignPrice = round((float)($row['foreign_price'] ?? 0), 3);
            $foreignTotal = round($foreignPrice * $qty, 3);
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
        $otherChargesKwd = max(0, round($this->inputFloat('other_charges_kwd'), 3));
        $totalKwd        = round($subtotalKwd + $otherChargesKwd, 3);
        $paidKwd         = $this->inputFloat('paid_kwd');
        $paidForeign     = 0; // payment is recorded in KWD only
        $status          = $this->resolvePoStatus($paidKwd, $totalKwd);

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
                    subtotal_foreign=?, subtotal_kwd=?, other_charges_kwd=?, paid_foreign=?, paid_kwd=?,
                    status=?, supplier_ref=?, notes=?, account_id=?
                 WHERE id=?",
                [
                    $this->input('date') ?: $po['date'],
                    $currency, $exchangeRate,
                    $subtotalForeign, $subtotalKwd, $otherChargesKwd, $paidForeign, $paidKwd,
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
        Auth::authorize('purchases', 'view');

        $q    = $this->inputSearch('q', '', 'get');
        $whId = $this->inputInt('warehouse_id', 0, 'get');
        if ($q === '') {
            echo json_encode([]);
            return;
        }

        $like  = '%' . $q . '%';
        $items = $this->db->fetchAll(
            "SELECT i.id, i.name, i.sku, i.purchase_price,
                    COALESCE(i.price_aed, 0) AS price_aed,
                    COALESCE(i.price_usd, 0) AS price_usd,
                    i.unit,
                    COALESCE(s.quantity, 0) AS current_stock
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
             WHERE i.is_active = 1
               AND (i.name LIKE ? OR i.sku LIKE ? OR i.barcode LIKE ?)
             ORDER BY i.name ASC
             LIMIT 15",
            [$whId, $like, $like, $like]
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
                    poi.unit_price_foreign, po.currency, po.exchange_rate
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
    /**
     * Remaining balance at or below this (KWD) is treated as fully paid — covers
     * per-unit rounding when qty × unit price differs slightly from bank amount.
     */
    private function poPaidTolerance(float $totalKwd): float {
        return min(0.100, max(0.001, round($totalKwd * 0.00001, 3)));
    }

    private function isPoFullyPaid(float $paidKwd, float $totalKwd): bool {
        if ($paidKwd <= 0) {
            return false;
        }
        return round($totalKwd - $paidKwd, 3) <= $this->poPaidTolerance($totalKwd);
    }

    private function resolvePoStatus(float $paidKwd, float $totalKwd): string {
        return $this->isPoFullyPaid($paidKwd, $totalKwd) ? 'paid' : 'draft';
    }

    /** Reopen PO when its linked purchase invoice was cancelled (e.g. mistaken conversion). */
    private function reconcileConvertedPoAfterCancelledPurchase(array $po): array {
        if (($po['status'] ?? '') !== 'converted') {
            return $po;
        }

        $convertedTo = (int) ($po['converted_to'] ?? 0);
        if ($convertedTo <= 0) {
            return $po;
        }

        $purchase = $this->db->fetchOne(
            "SELECT id, status, invoice_no FROM purchases WHERE id = ?",
            [$convertedTo]
        );
        if ($purchase && ($purchase['status'] ?? '') !== 'cancelled') {
            return $po;
        }

        if (PurchaseOrderConverter::reopenConvertedPo($this->db, (int) $po['id'])) {
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $refetched = $this->db->fetchOne(
                "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                        w.name as warehouse_name, u.name as created_by_name,
                        pur.invoice_no as purchase_invoice_no
                 FROM purchase_orders po
                 JOIN parties p ON p.id = po.party_id
                 JOIN warehouses w ON w.id = po.warehouse_id
                 LEFT JOIN users u ON u.id = po.created_by
                 LEFT JOIN purchases pur ON pur.id = po.converted_to
                 WHERE po.id = ?",
                [(int) $po['id']]
            );
            if ($refetched) {
                $this->flash(
                    'info',
                    'Linked purchase was cancelled — ' . ($po['po_no'] ?? 'PO') . ' reopened. Edit items and convert again.'
                );
                return $refetched;
            }
        }

        return $po;
    }

    /** Upgrade legacy draft POs that were fully paid but left draft due to rounding. */
    private function reconcilePoPaidStatus(array $po): array {
        if (($po['status'] ?? '') !== 'draft') {
            return $po;
        }
        $totalKwd = (float)$po['subtotal_kwd'] + (float)($po['other_charges_kwd'] ?? 0);
        $paidKwd  = (float)($po['paid_kwd'] ?? 0);
        if (!$this->isPoFullyPaid($paidKwd, $totalKwd)) {
            return $po;
        }
        $this->db->execute("UPDATE purchase_orders SET status = 'paid' WHERE id = ?", [(int)$po['id']]);
        self::clearDashboardCache((int)($po['warehouse_id'] ?? 0));
        $po['status'] = 'paid';
        return $po;
    }

    private function reconcileDraftPaidOrders(array &$orders): void {
        $cacheCleared = false;
        foreach ($orders as &$o) {
            if (($o['status'] ?? '') !== 'draft') {
                continue;
            }
            $totalKwd = (float)$o['subtotal_kwd'] + (float)($o['other_charges_kwd'] ?? 0);
            $paidKwd  = (float)($o['paid_kwd'] ?? 0);
            if (!$this->isPoFullyPaid($paidKwd, $totalKwd)) {
                continue;
            }
            $this->db->execute("UPDATE purchase_orders SET status = 'paid' WHERE id = ?", [(int)$o['id']]);
            $o['status'] = 'paid';
            if (!$cacheCleared) {
                self::clearDashboardCache((int)($o['warehouse_id'] ?? 0));
                $cacheCleared = true;
            }
        }
        unset($o);
    }

    private function nextPoNo(): string {
        $last = $this->db->fetchOne("SELECT po_no FROM purchase_orders ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int)substr($last['po_no'], 3) : 0;  // PO-000001
        return 'PO-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }
}
