<?php

require_once __DIR__ . '/BaseController.php';

class WarrantyController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // ─── List ─────────────────────────────────────────────────────────────────
    public function index(): void {
        Auth::authorize('warranty', 'view');

        $search   = $this->input('search',    '', 'get');
        $fromDate = $this->input('from_date', '', 'get');
        $toDate   = $this->input('to_date',   '', 'get');

        $where  = "WHERE 1=1";
        $params = [];

        if (Auth::warehouseId()) {
            $where .= " AND wr.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }
        if ($search) {
            $like    = "%$search%";
            $where  .= " AND (wr.replacement_no LIKE ? OR p.name LIKE ? OR wr.old_imei LIKE ? OR wr.new_imei LIKE ? OR i_old.name LIKE ?)";
            $params  = array_merge($params, [$like, $like, $like, $like, $like]);
        }
        if ($fromDate) { $where .= " AND wr.date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $where .= " AND wr.date <= ?"; $params[] = $toDate; }

        $replacements = $this->db->fetchAll(
            "SELECT wr.*, p.name AS customer_name, w.name AS warehouse_name,
                    i_old.name AS old_item_name, i_new.name AS new_item_name,
                    s.invoice_no AS sale_invoice_no
             FROM warranty_replacements wr
             JOIN parties p     ON p.id = wr.party_id
             JOIN warehouses w  ON w.id = wr.warehouse_id
             JOIN items i_old   ON i_old.id = wr.old_item_id
             JOIN items i_new   ON i_new.id = wr.new_item_id
             LEFT JOIN sales s  ON s.id = wr.sale_id
             $where
             ORDER BY wr.created_at DESC",
            $params
        );

        // Also load data needed for the New Replacement modal
        $warehouses = $this->db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");
        $customers  = $this->db->fetchAll(
            "SELECT id, name, phone FROM parties WHERE type IN ('customer','both') AND is_active = 1 ORDER BY name"
        );
        $items  = $this->db->fetchAll("SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name");
        $nextNo = $this->nextNo();

        $pageTitle = 'Warranty Replacements';
        $page      = 'warranty';

        ob_start();
        include __DIR__ . '/../views/warranty/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Create form ──────────────────────────────────────────────────────────
    public function create(): void {
        Auth::authorize('warranty', 'add');

        $warehouses = $this->db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");
        $customers  = $this->db->fetchAll(
            "SELECT id, name, phone FROM parties WHERE type IN ('customer','both') AND is_active = 1 ORDER BY name"
        );
        $items      = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name"
        );
        $nextNo    = $this->nextNo();
        $pageTitle = 'New Warranty Replacement';
        $page      = 'warranty';

        ob_start();
        include __DIR__ . '/../views/warranty/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Store ────────────────────────────────────────────────────────────────
    public function store(): void {
        Auth::authorize('warranty', 'add');
        if (!$this->isPost()) { $this->redirect('?page=warranty&action=create'); }

        $partyId     = $this->inputInt('party_id');
        $warehouseId = $this->inputInt('warehouse_id') ?: Auth::warehouseId();
        $saleId      = $this->inputInt('sale_id') ?: null;
        $oldItemId   = $this->inputInt('old_item_id');
        $newItemId   = $this->inputInt('new_item_id');
        $oldImei     = trim($this->input('old_imei'));
        $oldImei2    = trim($this->input('old_imei2'));
        $newImei     = trim($this->input('new_imei'));
        $newImei2    = trim($this->input('new_imei2'));
        $fault       = $this->input('fault_description');
        $notes       = $this->input('notes');
        $status      = $this->input('status') === 'pending_supplier' ? 'pending_supplier' : 'completed';

        if (!$partyId || !$oldItemId || !$newItemId) {
            $this->flash('error', 'Customer and both items are required.');
            $this->redirect('?page=warranty&action=create');
        }

        $this->db->beginTransaction();
        try {
            $no = $this->nextNo();

            $id = $this->db->insert(
                "INSERT INTO warranty_replacements
                    (replacement_no, sale_id, party_id, warehouse_id, date,
                     old_item_id, old_imei, old_imei2,
                     new_item_id, new_imei, new_imei2,
                     fault_description, notes, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $no, $saleId, $partyId, $warehouseId,
                    $this->input('date') ?: date('Y-m-d'),
                    $oldItemId, $oldImei ?: null, $oldImei2 ?: null,
                    $newItemId, $newImei ?: null, $newImei2 ?: null,
                    $fault ?: null, $notes ?: null, $status, Auth::id(),
                ]
            );

            // Mark old IMEI as defective
            if ($oldImei) {
                $this->db->execute(
                    "UPDATE imei_records
                     SET status = 'defective', notes = CONCAT(COALESCE(notes,''), ' | Warranty replacement ', ?)
                     WHERE imei = ? OR imei2 = ?",
                    [$no, $oldImei, $oldImei]
                );
            }

            // Mark new IMEI as sold & link to this replacement
            if ($newImei) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM imei_records WHERE imei = ? OR imei2 = ?",
                    [$newImei, $newImei]
                );
                if ($existing) {
                    $this->db->execute(
                        "UPDATE imei_records SET status = 'sold',
                              notes = CONCAT(COALESCE(notes,''), ' | Warranty out: ', ?)
                         WHERE imei = ? OR imei2 = ?",
                        [$no, $newImei, $newImei]
                    );
                } else {
                    // New IMEI not yet in records — insert it
                    $this->db->insert(
                        "INSERT INTO imei_records (imei, imei2, item_id, warehouse_id, status, notes)
                         VALUES (?,?,?,?,'sold',?)",
                        [$newImei, $newImei2 ?: null, $newItemId, $warehouseId,
                         "Warranty replacement out: $no"]
                    );
                }
            }

            // Always deduct stock for the replacement device given out
            $this->db->execute(
                "UPDATE stock SET quantity = quantity - 1
                 WHERE item_id = ? AND warehouse_id = ? AND quantity > 0",
                [$newItemId, $warehouseId]
            );

            $this->db->commit();
            $this->logActivity('warranty_replacement', 'warranty_replacements', $id, $no);
            $this->flash('success', "Warranty replacement $no recorded successfully.");
            $this->redirect('?page=warranty&action=view&id=' . $id);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=warranty&action=create');
        }
    }

    // ─── View ─────────────────────────────────────────────────────────────────
    public function view(): void {
        Auth::authorize('warranty', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $wr = $this->db->fetchOne(
            "SELECT wr.*, p.name AS customer_name, p.phone AS customer_phone,
                    w.name AS warehouse_name,
                    i_old.name AS old_item_name, i_old.sku AS old_sku,
                    i_new.name AS new_item_name, i_new.sku AS new_sku,
                    s.invoice_no AS sale_invoice_no,
                    u.name AS created_by_name
             FROM warranty_replacements wr
             JOIN parties p     ON p.id = wr.party_id
             JOIN warehouses w  ON w.id = wr.warehouse_id
             JOIN items i_old   ON i_old.id = wr.old_item_id
             JOIN items i_new   ON i_new.id = wr.new_item_id
             LEFT JOIN sales s  ON s.id = wr.sale_id
             LEFT JOIN users u  ON u.id = wr.created_by
             WHERE wr.id = ?",
            [$id]
        );

        if (!$wr) { $this->flash('error', 'Record not found.'); $this->redirect('?page=warranty'); }

        $pageTitle = $wr['replacement_no'];
        $page      = 'warranty';

        ob_start();
        include __DIR__ . '/../views/warranty/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── AJAX: look up IMEI ───────────────────────────────────────────────────
    public function lookupImei(): void {
        header('Content-Type: application/json');
        $imei = trim($this->input('imei', '', 'get'));
        if (!$imei) { echo json_encode(null); return; }

        $row = $this->db->fetchOne(
            "SELECT ir.*, i.name AS item_name, i.id AS item_id, i.sku,
                    s.invoice_no AS sale_invoice_no, s.id AS sale_id,
                    p.name AS customer_name, p.id AS customer_id
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN parties p ON p.id = s.party_id
             WHERE ir.imei = ? OR ir.imei2 = ?",
            [$imei, $imei]
        );
        echo json_encode($row);
    }

    // ─── AJAX: search available (in_stock) IMEI for replacement ─────────────
    public function searchNewImei(): void {
        header('Content-Type: application/json');
        $q      = trim($this->input('q', '', 'get'));
        $itemId = $this->inputInt('item_id', 0, 'get');
        $whId   = $this->inputInt('warehouse_id', 0, 'get');
        if (!$q) { echo json_encode([]); return; }

        $like   = "%$q%";
        $params = [$like, $like];
        $extra  = "";
        if ($itemId) { $extra .= " AND ir.item_id = ?"; $params[] = $itemId; }
        if ($whId)   { $extra .= " AND ir.warehouse_id = ?"; $params[] = $whId; }

        $rows = $this->db->fetchAll(
            "SELECT ir.imei, ir.imei2, i.name AS item_name, i.id AS item_id
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.status = 'in_stock' AND (ir.imei LIKE ? OR ir.imei2 LIKE ?)
             $extra
             LIMIT 10",
            $params
        );
        echo json_encode($rows);
    }

    // ─── AJAX: search sales by invoice no / customer ─────────────────────────
    public function searchSale(): void {
        header('Content-Type: application/json');
        $q = trim($this->input('q', '', 'get'));
        if (strlen($q) < 2) { echo json_encode([]); return; }
        $like = "%$q%";
        $rows = $this->db->fetchAll(
            "SELECT s.id, s.invoice_no, s.date, p.name AS customer_name
             FROM sales s JOIN parties p ON p.id = s.party_id
             WHERE s.status != 'cancelled'
               AND (s.invoice_no LIKE ? OR p.name LIKE ?)
             ORDER BY s.date DESC LIMIT 10",
            [$like, $like]
        );
        echo json_encode($rows);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────
    private function nextNo(): string {
        $last = $this->db->fetchOne("SELECT replacement_no FROM warranty_replacements ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int)substr($last['replacement_no'], 3) : 0;
        return 'WR-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }
}
