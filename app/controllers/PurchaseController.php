<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';

class PurchaseController extends BaseController {

    public function index(): void {
        Auth::authorize('purchases', 'view');

        $db = Database::getInstance();
        $filters = [
            'search'    => $this->input('search', '', 'get'),
            'status'    => $this->input('status', '', 'get'),
            'from_date' => $this->input('from_date', '', 'get'),
            'to_date'   => $this->input('to_date', '', 'get'),
        ];

        $where  = "WHERE p.status != 'cancelled'";
        $params = [];

        // Always scope to selected warehouse session
        if (Auth::warehouseId()) {
            $where .= " AND p.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['search'])) {
            $like   = '%' . $filters['search'] . '%';
            $where .= " AND (p.invoice_no LIKE ? OR par.name LIKE ?)";
            $params = array_merge($params, [$like, $like]);
        }
        if (!empty($filters['status'])) {
            $where .= " AND p.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND p.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND p.date <= ?"; $params[] = $filters['to_date'];
        }

        $purchases = $db->fetchAll(
            "SELECT p.*, par.name as party_name, w.name as warehouse_name
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             {$where}
             ORDER BY p.created_at DESC
             LIMIT 500",
            $params
        );

        $stats = $db->fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(grand_total),0) as total,
                    COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(balance),0) as balance
             FROM purchases
             WHERE date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
               AND date <= CURDATE()
               AND status != 'cancelled'
               AND (? = 0 OR warehouse_id = ?)",
            [(int) (Auth::warehouseId() ?? 0), (int) (Auth::warehouseId() ?? 0)]
        );

        $pageTitle = 'Purchases';
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('purchases', 'add');

        $db         = Database::getInstance();
        $itemModel  = new Item();
        $partyModel = new Party();

        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $nextInv    = $this->nextInvoiceNo($db);
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

        $invoiceNo = $this->nextInvoiceNo($db);
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

        $db->beginTransaction();
        try {
            $purchaseId = $db->insert(
                "INSERT INTO purchases (invoice_no, supplier_invoice_no, party_id, warehouse_id, date, subtotal, discount, tax,
                                        grand_total, paid_amount, balance, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $invoiceNo,
                    $this->input('supplier_invoice_no') ?: null,
                    $this->inputInt('party_id'),
                    $warehouseId,
                    $this->input('date') ?: date('Y-m-d'),
                    $subtotal, $discount, $tax, $grandTotal, $paid,
                    max(0, $balance), $status,
                    $this->input('notes'),
                    Auth::id(),
                ]
            );

            foreach ($items as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $purItemId = $db->insert(
                    "INSERT INTO purchase_items (purchase_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$purchaseId, $item['item_id'], $item['quantity'], $item['unit_price'], $lineTotal]
                );

                // Add stock
                $stockRow = $db->fetchOne(
                    "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [$item['item_id'], $warehouseId]
                );
                if ($stockRow) {
                    $db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                        [$item['quantity'], $stockRow['id']]
                    );
                } else {
                    $db->insert(
                        "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                        [$item['item_id'], $warehouseId, $item['quantity']]
                    );
                }

                // Register IMEIs
                foreach ($item['imeis'] as $imei) {
                    if (!$imei) continue;
                    $exists = $db->fetchOne("SELECT id FROM imei_records WHERE imei = ?", [$imei]);
                    if (!$exists) {
                        $db->insert(
                            "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status)
                             VALUES (?,?,?,?,'in_stock')",
                            [$imei, $item['item_id'], $warehouseId, $purchaseId]
                        );
                    }
                }
            }

            // Record payment
            if ($paid > 0) {
                // C2 fix: shared MAX(numeric) generator + retry on duplicate-key collision.
                require_once __DIR__ . '/../models/Payment.php';
                $paymentModel = new Payment();
                $payNo = $paymentModel->nextPaymentNo();
                for ($attempt = 1; $attempt <= 3; $attempt++) {
                    try {
                        $db->insert(
                            "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                            [$payNo, 'purchase', $purchaseId, $this->inputInt('party_id'),
                             'out',
                             $this->inputInt('account_id') ?: 1,
                             $paid, $this->input('payment_method') ?: 'cash',
                             $this->input('date') ?: date('Y-m-d'), Auth::warehouseId(), Auth::id()]
                        );
                        break;
                    } catch (Exception $e) {
                        $isDuplicatePayNo = str_contains($e->getMessage(), 'Duplicate entry')
                            && str_contains($e->getMessage(), 'payment_no');
                        if (!$isDuplicatePayNo || $attempt === 3) {
                            throw $e;
                        }
                        $payNo = $paymentModel->nextPaymentNo();
                    }
                }
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paid, $this->inputInt('account_id') ?: 1]
                );
            }

            $db->commit();
            $this->logActivity('create_purchase', 'purchases', (int)$purchaseId, $invoiceNo);
            $this->flash('success', "Purchase {$invoiceNo} saved.");
            if ($this->input('print_mode') === '1') {
                $this->redirect('?page=purchases&action=print&id=' . $purchaseId . '&autoprint=1');
            }
            $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);

        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Failed: ' . $e->getMessage());
            $this->redirect('?page=purchases&action=create');
        }
    }

    public function detail(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT p.*, par.name as party_name, par.phone as party_phone,
                    w.name as warehouse_name
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.id = ?",
            [$id]
        );

        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
        }

        $purchase['items'] = $db->fetchAll(
            "SELECT pi.*, i.name as item_name, i.sku,
                    GROUP_CONCAT(ir.imei ORDER BY ir.imei SEPARATOR '||') as imei_list
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN imei_records ir ON ir.purchase_id = pi.purchase_id AND ir.item_id = pi.item_id
             WHERE pi.purchase_id = ?
             GROUP BY pi.id",
            [$id]
        );

        $purchase['payments'] = $db->fetchAll(
            "SELECT py.*, a.name as account_name FROM payments py
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.ref_type = 'purchase' AND py.ref_id = ?",
            [$id]
        );

        // Self-heal: if payments exist but purchase header isn't updated (common when migrated from PO),
        // sync paid_amount/balance/status so UI matches the ledger.
        $paidSum = 0.0;
        foreach ($purchase['payments'] as $py) $paidSum += (float)($py['amount'] ?? 0);
        $paidSum = round($paidSum, 3);
        $headerPaid = (float)($purchase['paid_amount'] ?? 0);
        if (abs($paidSum - $headerPaid) > 0.001) {
            $grand = (float)($purchase['grand_total'] ?? 0);
            $newBalance = max(0, $grand - $paidSum);
            $newStatus  = $newBalance < 0.001 ? 'paid' : ($paidSum > 0 ? 'partial' : 'confirmed');
            $db->execute(
                "UPDATE purchases SET paid_amount=?, balance=?, status=? WHERE id=?",
                [$paidSum, round($newBalance, 3), $newStatus, $id]
            );
            $purchase['paid_amount'] = $paidSum;
            $purchase['balance']     = round($newBalance, 3);
            $purchase['status']      = $newStatus;
        }

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
        }

        $id = $this->inputInt('id');
        if ($id <= 0) {
            $this->flash('error', 'Invalid purchase.');
            $this->redirect('?page=purchases');
        }

        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT * FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );
        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
        }
        if (($purchase['status'] ?? '') === 'cancelled') {
            $this->flash('warning', 'Purchase is already cancelled.');
            $this->redirect('?page=purchases');
        }

        // Prevent cancelling if a purchase return was approved against this purchase
        $existingReturns = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM returns WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
            [$id]
        );
        if ($existingReturns && (int)($existingReturns['cnt'] ?? 0) > 0) {
            $this->flash('error', 'Cannot cancel: approved purchase return exists for this purchase.');
            $this->redirect('?page=purchases&action=detail&id=' . $id);
        }

        $items = $db->fetchAll(
            "SELECT item_id, quantity FROM purchase_items WHERE purchase_id = ?",
            [$id]
        );

        $db->beginTransaction();
        try {
            // IMEI safety check (lock rows): cannot cancel if any IMEI from this purchase has moved out of stock
            $badImei = $db->fetchOne(
                "SELECT id, imei, status, sale_id FROM imei_records
                 WHERE purchase_id = ? AND (status != 'in_stock' OR sale_id IS NOT NULL)
                 LIMIT 1 FOR UPDATE",
                [$id]
            );
            if ($badImei) {
                throw new Exception('Cannot cancel: at least one IMEI from this purchase is already used/sold (IMEI: ' . ($badImei['imei'] ?? '') . ').');
            }

            // Reverse payments: add back to accounts, then delete payment records
            $payments = $db->fetchAll(
                "SELECT id, account_id, amount FROM payments WHERE ref_type = 'purchase' AND ref_id = ? FOR UPDATE",
                [$id]
            );
            foreach ($payments as $py) {
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [(float)$py['amount'], (int)$py['account_id']]
                );
            }
            $db->execute("DELETE FROM payments WHERE ref_type = 'purchase' AND ref_id = ?", [$id]);

            // Reverse stock
            foreach ($items as $it) {
                $stockRow = $db->fetchOne(
                    "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [(int)$it['item_id'], (int)$purchase['warehouse_id']]
                );
                $currentQty = (int)($stockRow['quantity'] ?? 0);
                if ($currentQty < (int)$it['quantity']) {
                    throw new Exception('Cannot cancel: stock already used/sold for one or more items.');
                }
                $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE id = ?",
                    [(int)$it['quantity'], (int)$stockRow['id']]
                );
            }

            // Remove IMEIs that were created by this purchase (they are still in_stock due to checks above)
            $db->execute("DELETE FROM imei_records WHERE purchase_id = ?", [$id]);

            // Mark purchase cancelled so it no longer affects balances/reports
            $db->execute("UPDATE purchases SET status='cancelled' WHERE id = ?", [$id]);

            $db->commit();
            $this->logActivity('cancel_purchase', 'purchases', $id, 'Cancelled ' . ($purchase['invoice_no'] ?? ''));
            $this->flash('success', 'Purchase ' . ($purchase['invoice_no'] ?? '') . ' cancelled successfully.');
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Failed to cancel purchase: ' . $e->getMessage());
        }

        $this->redirect('?page=purchases');
    }

    public function print(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT p.*, par.name as party_name, par.phone as party_phone,
                    w.name as warehouse_name
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.id = ?",
            [$id]
        );

        if (!$purchase) die('Purchase not found.');

        $purchase['items'] = $db->fetchAll(
            "SELECT pi.*, i.name as item_name, i.sku
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.purchase_id = ?
             ORDER BY pi.id ASC",
            [$id]
        );

        $settings = [];
        $rows     = $db->fetchAll("SELECT key_name, value FROM settings");
        foreach ($rows as $r) $settings[$r['key_name']] = $r['value'];

        include __DIR__ . '/../views/purchases/print.php';
    }

    // ── IMEI Scan Station ─────────────────────────────────────────────
    public function imeiScan(): void {
        Auth::authorize('purchases', 'edit');
        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $purchase = $db->fetchOne(
            "SELECT p.*, par.name as party_name, w.name as warehouse_name
             FROM purchases p
             JOIN parties par ON par.id = p.party_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.id = ?",
            [$id]
        );
        if (!$purchase) { $this->flash('error', 'Purchase not found.'); $this->redirect('?page=purchases'); }

        // Only items with has_imei=1
        $items = $db->fetchAll(
            "SELECT pi.id as pi_id, pi.item_id, pi.quantity, i.name as item_name,
                    (SELECT COUNT(*) FROM imei_records WHERE purchase_id = pi.purchase_id AND item_id = pi.item_id) as scanned
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.purchase_id = ? AND i.has_imei = 1
             ORDER BY pi.id ASC",
            [$id]
        );

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

    // AUDIT FIX F1: FOR UPDATE prevents duplicate purchase numbers
    private function nextInvoiceNo(Database $db): string {
        $last = $db->fetchOne("SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int) substr($last['invoice_no'], strlen(PURCHASE_PREFIX)) : 0;
        return PURCHASE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }
}
