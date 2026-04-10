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
             ORDER BY p.created_at DESC",
            $params
        );

        $stats = $db->fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(grand_total),0) as total,
                    COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(balance),0) as balance
             FROM purchases WHERE MONTH(date) = MONTH(CURDATE()) AND status != 'cancelled'"
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

        $warehouses = $itemModel->getWarehouses();
        $accounts   = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");
        $nextInv    = $this->nextInvoiceNo($db);
        $pageTitle  = 'New Purchase';
        $page       = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=purchases&action=create'); }

        $db       = Database::getInstance();
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
            $this->redirect('?page=purchases&action=create');
        }

        $invoiceNo = $this->nextInvoiceNo($db);
        $subtotal  = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
        $discount  = $this->inputFloat('discount');
        $tax       = 0;
        $grandTotal = $subtotal - $discount;
        $paid       = $this->inputFloat('paid_amount');
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
                $exists = $db->fetchOne(
                    "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ?",
                    [$item['item_id'], $warehouseId]
                );
                if ($exists) {
                    $db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                        [$item['quantity'], $item['item_id'], $warehouseId]
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
                $last  = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $num   = $last ? (int) substr($last['payment_no'], 4) : 0;
                $payNo = 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
                $db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, account_id, amount, payment_method, date, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase', $purchaseId, $this->inputInt('party_id'),
                     $this->inputInt('account_id') ?: 1,
                     $paid, $this->input('payment_method') ?: 'cash',
                     $this->input('date') ?: date('Y-m-d'), Auth::id()]
                );
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

        $accounts  = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1");
        $pageTitle = 'Purchase: ' . $purchase['invoice_no'];
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
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

    private function nextInvoiceNo(Database $db): string {
        $last = $db->fetchOne("SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int) substr($last['invoice_no'], strlen(PURCHASE_PREFIX)) : 0;
        return PURCHASE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }
}
