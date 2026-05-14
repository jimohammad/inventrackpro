<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Purchase.php';

class PurchaseController extends BaseController {

    private Purchase $purchaseModel;

    public function __construct() {
        parent::__construct();
        $this->purchaseModel = new Purchase();
    }

    public function index(): void {
        Auth::authorize('purchases', 'view');

        $filters = [
            'search'    => $this->input('search', '', 'get'),
            'status'    => $this->input('status', '', 'get'),
            'from_date' => $this->input('from_date', '', 'get'),
            'to_date'   => $this->input('to_date', '', 'get'),
        ];

        $purchases = $this->purchaseModel->getIndexList($filters, Auth::warehouseId());
        $stats     = $this->purchaseModel->getMonthStats(Auth::warehouseId());

        $pageTitle = 'Purchases';
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('purchases', 'add');

        $itemModel  = new Item();
        $partyModel = new Party();

        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $nextInv    = $this->purchaseModel->nextInvoiceNo();
        $pageTitle  = 'New Purchase';
        $page       = 'purchases';

        // One-time token to prevent double-submit duplicate purchases
        $_SESSION['purchase_form_nonce'] = bin2hex(random_bytes(16));
        $purchaseFormNonce               = $_SESSION['purchase_form_nonce'];

        ob_start();
        include __DIR__ . '/../views/purchases/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=purchases&action=create'); return; }

        $db       = Database::getInstance();

        $postedNonce = isset($_POST['purchase_form_nonce']) ? trim((string)$_POST['purchase_form_nonce']) : '';
        $sessNonce   = $_SESSION['purchase_form_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This purchase form was already submitted or expired. Please check Purchases list before trying again.');
            $this->redirect('?page=purchases');
            return;
        }
        unset($_SESSION['purchase_form_nonce']);

        $rawItems = $_POST['items'] ?? [];
        $items    = [];

        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;

            // Validate positive values
            if ((int)$row['quantity'] <= 0) {
                $this->flash('error', 'Quantity must be greater than zero.');
                $this->redirect('?page=purchases&action=create');
                return;
            }
            if ((float)$row['unit_price'] < 0) {
                $this->flash('error', 'Price cannot be negative.');
                $this->redirect('?page=purchases&action=create');
                return;
            }

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
            $this->redirect('?page=purchases&action=create');
            return;
        }

        $partyId = $this->inputInt('party_id');
        if ($partyId <= 0) {
            $this->flash('error', 'Please select a supplier.');
            $this->redirect('?page=purchases&action=create');
            return;
        }

        // Validate IMEI counts for IMEI-tracked items to avoid "ghost stock" without IMEIs.
        $itemIds = array_values(array_unique(array_map(static fn($i) => (int) $i['item_id'], $items)));
        if (!empty($itemIds)) {
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $rows = $db->fetchAll(
                "SELECT id, name, has_imei, imei_optional
                 FROM items
                 WHERE id IN ({$ph})",
                $itemIds
            );
            $itemInfoMap = [];
            foreach ($rows as $r) {
                $itemInfoMap[(int) $r['id']] = $r;
            }

            foreach ($items as $it) {
                $info = $itemInfoMap[(int) $it['item_id']] ?? null;
                if (!$info) {
                    $this->flash('error', 'Invalid item selected.');
                    $this->redirect('?page=purchases&action=create');
                    return;
                }
                if (!empty($info['has_imei']) && empty($info['imei_optional'])) {
                    $qty = (int) ($it['quantity'] ?? 0);
                    $cnt = is_array($it['imeis'] ?? null) ? count($it['imeis']) : 0;
                    if ($qty > 0 && $cnt !== $qty) {
                        $name = (string) ($info['name'] ?? ('Item #' . (int) $it['item_id']));
                        $this->flash('error', "Item \"{$name}\": IMEI count must match quantity ({$qty}).");
                        $this->redirect('?page=purchases&action=create');
                        return;
                    }
                }
            }
        }

        $invoiceNo = $this->purchaseModel->nextInvoiceNo();
        $subtotal  = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
        // C3 fix: clamp negative discount — a negative value would inflate grand_total above subtotal.
        $discount  = max(0.0, $this->inputFloat('discount'));
        $tax       = 0;
        $grandTotal = $subtotal - $discount;
        $paid       = $this->inputFloat('paid_amount');

        // C1 fix: reject overpayment so 20 KWD doesn't silently vanish via max(0, balance) below.
        if ($paid > $grandTotal + 0.001) {
            $this->flash('error',
                'Paid amount (' . number_format($paid, 3) . ') exceeds grand total (' . number_format($grandTotal, 3) . '). '
                . 'Reduce the payment or add it later via the Payments page.'
            );
            $this->redirect('?page=purchases&action=create');
            return;
        }

        $balance    = $grandTotal - $paid;
        $warehouseId = Auth::warehouseId();

        $status = $balance < 0.001 ? 'paid' : ($paid > 0 ? 'partial' : 'confirmed');

        // Duplicate-PO guard: if this supplier already has an unconverted PO for the same total in
        // this warehouse, the user is almost certainly re-keying a PO that should have been
        // converted via "Convert to Purchase Invoice". Without this guard, BOTH the PO payment
        // ('purchase_order') and this Purchase payment ('purchase') get inserted and the bank
        // account is debited twice.
        $openPo = $this->purchaseModel->findBlockingOpenPurchaseOrder($partyId, $warehouseId, $grandTotal);
        if ($openPo) {
            $this->flash('error',
                'Blocked to prevent a duplicate payment: this supplier already has an open Purchase Order '
                . $openPo['po_no'] . ' for ' . number_format((float)$openPo['subtotal_kwd'], DECIMAL_PLACES)
                . ' KWD that has not been converted yet. Open that PO and click "Convert to Purchase Invoice" '
                . 'instead — creating a fresh Purchase here would record the same payment twice and '
                . 'double-deduct the bank account.'
            );
            $this->redirect('?page=purchaseorders&action=show&id=' . (int)$openPo['id']);
            return;
        }

        try {
            $purchaseDate = $this->input('date') ?: date('Y-m-d');
            $purchaseId   = $this->purchaseModel->createFullPurchase(
                $invoiceNo,
                $this->input('supplier_invoice_no') ?: null,
                $partyId,
                (int) $warehouseId,
                $purchaseDate,
                $subtotal,
                $discount,
                $tax,
                $grandTotal,
                $paid,
                $balance,
                $status,
                $this->input('notes'),
                Auth::id(),
                $items,
                $this->inputInt('account_id') ?: 1,
                $this->input('payment_method') ?: 'cash'
            );

            $this->logActivity('create_purchase', 'purchases', $purchaseId, $invoiceNo);
            $this->flash('success', "Purchase {$invoiceNo} saved.");
            if ($this->input('print_mode') === '1') {
                $this->redirect('?page=purchases&action=print&id=' . $purchaseId . '&autoprint=1');
            }
            $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);
        } catch (Throwable $e) {
            $this->flash('error', 'Failed: ' . $e->getMessage());
            $this->redirect('?page=purchases&action=create');
        }
    }

    public function detail(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');

        $purchase = $this->purchaseModel->findHeaderForView($id);

        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
            return;
        }

        $purchase['items']    = $this->purchaseModel->getDetailLineItems($id);
        $purchase['payments'] = $this->purchaseModel->getLinkedPayments($id);
        $this->purchaseModel->syncHeaderWithPaymentSum($id, $purchase);

        $accounts  = self::getAccounts();
        $pageTitle = 'Purchase: ' . $purchase['invoice_no'];
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Cancel (delete) a purchase: reverse stock/IMEIs and reverse/delete linked payments.
     * This is used to fix mistaken double-entry purchases.
     */
    public function cancel(): void {
        Auth::authorize('purchases', 'delete');

        if (!$this->isPost()) {
            $this->redirect('?page=purchases');
            return;
        }

        $id = $this->inputInt('id');
        if ($id <= 0) {
            $this->flash('error', 'Invalid purchase.');
            $this->redirect('?page=purchases');
            return;
        }

        try {
            $invoiceNo = $this->purchaseModel->cancelWithReversals($id, (int) Auth::warehouseId());
            $this->logActivity('cancel_purchase', 'purchases', $id, 'Cancelled ' . $invoiceNo);
            $this->flash('success', 'Purchase ' . $invoiceNo . ' cancelled successfully.');
        } catch (Exception $e) {
            if ($e->getMessage() === 'ALREADY_CANCELLED') {
                $this->flash('warning', 'Purchase is already cancelled.');
            } elseif ($e->getMessage() === 'Purchase not found.') {
                $this->flash('error', 'Purchase not found.');
            } elseif (str_starts_with($e->getMessage(), 'Cannot cancel: approved purchase return')) {
                $this->flash('error', $e->getMessage());
                $this->redirect('?page=purchases&action=detail&id=' . $id);
                return;
            } else {
                $this->flash('error', 'Failed to cancel purchase: ' . $e->getMessage());
            }
        }

        $this->redirect('?page=purchases');
    }

    public function print(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');

        $purchase = $this->purchaseModel->findHeaderForView($id);

        if (!$purchase) {
            die('Purchase not found.');
        }

        $purchase['items'] = $this->purchaseModel->getPrintLineItems($id);
        $settings          = self::getSettings();

        include __DIR__ . '/../views/purchases/print.php';
    }

    // ── IMEI Scan Station ─────────────────────────────────────────────
    public function imeiScan(): void {
        Auth::authorize('purchases', 'edit');
        $id = $this->inputInt('id', 0, 'get');

        $purchase = $this->purchaseModel->findHeaderForView($id);
        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
            return;
        }

        $items = $this->purchaseModel->getImeiScanLines($id);

        $pageTitle = 'Scan IMEIs — ' . $purchase['invoice_no'];
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/imei_scan.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // AJAX: add a single IMEI for a purchase item
    public function imeiScanAdd(): void {
        Auth::authorize('purchases', 'edit');
        header('Content-Type: application/json');

        $purchaseId = $this->inputInt('purchase_id', 0, 'post');
        $itemId     = $this->inputInt('item_id',     0, 'post');
        $imei       = trim($this->input('imei', '', 'post'));

        if (!$purchaseId || !$itemId || !$imei) {
            echo json_encode(['ok' => false, 'msg' => 'Missing data.']); return;
        }
        if (!preg_match('/^\d{15,18}$/', $imei)) {
            echo json_encode(['ok' => false, 'msg' => 'IMEI must be 15–18 digits.']); return;
        }

        $db = Database::getInstance();

        // Check duplicate globally
        $existing = $db->fetchOne("SELECT id, item_id, purchase_id FROM imei_records WHERE imei = ?", [$imei]);
        if ($existing) {
            if ($existing['purchase_id'] == $purchaseId && $existing['item_id'] == $itemId) {
                echo json_encode(['ok' => false, 'msg' => 'Already scanned in this purchase.']); return;
            }
            echo json_encode(['ok' => false, 'msg' => 'IMEI exists in another record (id=' . $existing['id'] . ').']); return;
        }

        // Get warehouse from purchase
        $purchase = $db->fetchOne("SELECT warehouse_id FROM purchases WHERE id = ?", [$purchaseId]);
        $warehouseId = $purchase['warehouse_id'] ?? null;

        $db->insert(
            "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status) VALUES (?,?,?,?,'in_stock')",
            [$imei, $itemId, $warehouseId, $purchaseId]
        );

        $scanned = (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['c'];

        $qty = (int)$db->fetchOne(
            "SELECT quantity FROM purchase_items WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['quantity'];

        echo json_encode(['ok' => true, 'imei' => $imei, 'scanned' => $scanned, 'qty' => $qty]);
    }

    // AJAX: bulk-save many IMEIs from paste with duplicate validation
    public function imeiScanBulk(): void {
        Auth::authorize('purchases', 'edit');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['ok' => false, 'msg' => 'POST required.']); return; }

        $purchaseId = $this->inputInt('purchase_id', 0, 'post');
        $itemId     = $this->inputInt('item_id',     0, 'post');
        $raw        = (string) $this->input('imeis', '', 'post');

        if (!$purchaseId || !$itemId || $raw === '') {
            echo json_encode(['ok' => false, 'msg' => 'Missing data.']); return;
        }

        $db = Database::getInstance();

        $purchase = $db->fetchOne("SELECT warehouse_id FROM purchases WHERE id = ?", [$purchaseId]);
        if (!$purchase) { echo json_encode(['ok' => false, 'msg' => 'Purchase not found.']); return; }
        $warehouseId = $purchase['warehouse_id'] ?? null;

        $item = $db->fetchOne(
            "SELECT pi.quantity,
                    (SELECT COUNT(*) FROM imei_records WHERE purchase_id=? AND item_id=?) AS scanned
             FROM purchase_items pi
             WHERE pi.purchase_id=? AND pi.item_id=?",
            [$purchaseId, $itemId, $purchaseId, $itemId]
        );
        if (!$item) { echo json_encode(['ok' => false, 'msg' => 'Item not in this purchase.']); return; }

        $qty       = (int) $item['quantity'];
        $scanned   = (int) $item['scanned'];
        $remaining = max(0, $qty - $scanned);

        // Parse — split on newlines, commas, semicolons, whitespace fences
        $tokens  = preg_split('/[\r\n,;\s]+/', $raw);
        $skipped = [];
        $clean   = [];
        $seen    = [];

        foreach ($tokens as $tok) {
            $imei = strtoupper(trim((string) $tok));
            if ($imei === '') continue;
            if (!preg_match('/^\d{15,18}$/', $imei)) {
                $skipped[] = ['imei' => $imei, 'reason' => 'Must be 15–18 digits'];
                continue;
            }
            if (isset($seen[$imei])) {
                $skipped[] = ['imei' => $imei, 'reason' => 'Duplicate in list'];
                continue;
            }
            $seen[$imei] = true;
            $clean[]     = $imei;
        }

        if (empty($clean)) {
            echo json_encode([
                'ok'      => true,
                'saved'   => 0,
                'skipped' => $skipped,
                'scanned' => $scanned,
                'qty'     => $qty,
                'msg'     => 'No valid IMEIs to import.',
            ]);
            return;
        }

        // Bulk-fetch existing records for all candidates (single query)
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $existingRows = $db->fetchAll(
            "SELECT imei, item_id, purchase_id FROM imei_records WHERE imei IN ($placeholders)",
            $clean
        );
        $existingMap = [];
        foreach ($existingRows as $row) {
            $existingMap[$row['imei']] = $row;
        }

        $savedCount = 0;
        $db->beginTransaction();
        try {
            foreach ($clean as $imei) {
                if (isset($existingMap[$imei])) {
                    $ex = $existingMap[$imei];
                    if ((int)$ex['purchase_id'] === $purchaseId && (int)$ex['item_id'] === $itemId) {
                        $skipped[] = ['imei' => $imei, 'reason' => 'Already scanned in this purchase'];
                    } else {
                        $skipped[] = ['imei' => $imei, 'reason' => 'Exists in another record'];
                    }
                    continue;
                }

                if ($remaining <= 0) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Exceeds remaining quantity'];
                    continue;
                }

                $db->insert(
                    "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status) VALUES (?,?,?,?,'in_stock')",
                    [$imei, $itemId, $warehouseId, $purchaseId]
                );
                $savedCount++;
                $remaining--;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('imeiScanBulk error: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Database error during bulk import.']);
            return;
        }

        $totalScanned = (int) $db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['c'];

        if ($savedCount > 0) {
            $this->logActivity('imei_bulk_scan_purchase', 'imei_records', $purchaseId,
                "Bulk imported {$savedCount} IMEI(s) for item #{$itemId} on purchase #{$purchaseId}; skipped " . count($skipped));
        }

        echo json_encode([
            'ok'      => true,
            'saved'   => $savedCount,
            'skipped' => $skipped,
            'scanned' => $totalScanned,
            'qty'     => $qty,
        ]);
    }

    // AJAX: delete last scanned IMEI (undo)
    public function imeiScanDelete(): void {
        Auth::authorize('purchases', 'edit');
        header('Content-Type: application/json');

        $purchaseId = $this->inputInt('purchase_id', 0, 'post');
        $itemId     = $this->inputInt('item_id',     0, 'post');
        $imei       = trim($this->input('imei', '', 'post'));

        if (!$purchaseId || !$itemId || !$imei) {
            echo json_encode(['ok' => false, 'msg' => 'Missing data.']); return;
        }

        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT id FROM imei_records WHERE imei=? AND purchase_id=? AND item_id=?",
            [$imei, $purchaseId, $itemId]
        );
        if (!$row) { echo json_encode(['ok' => false, 'msg' => 'IMEI not found.']); return; }

        $db->execute("DELETE FROM imei_records WHERE id=?", [$row['id']]);

        $scanned = (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['c'];

        echo json_encode(['ok' => true, 'scanned' => $scanned]);
    }

    // AJAX: get scanned IMEI list for an item
    public function imeiScanList(): void {
        Auth::authorize('purchases', 'view');
        header('Content-Type: application/json');

        $purchaseId = $this->inputInt('purchase_id', 0, 'get');
        $itemId     = $this->inputInt('item_id',     0, 'get');

        $db   = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT imei, created_at FROM imei_records WHERE purchase_id=? AND item_id=? ORDER BY id DESC",
            [$purchaseId, $itemId]
        );
        echo json_encode($rows);
    }
}
