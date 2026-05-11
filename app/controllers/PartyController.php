<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';

class PartyController extends BaseController {
    private Party $partyModel;

    public function __construct() {
        parent::__construct();
        $this->partyModel = new Party();
    }

    // Helper: get permission module for a party type
    private function partyModule(string $type): string {
        return $type === 'supplier' ? 'suppliers' : 'customers';
    }

    // Helper: can user access parties at all?
    private function canViewCustomers(): bool { return Auth::can('customers', 'view'); }
    private function canViewSuppliers(): bool { return Auth::can('suppliers', 'view'); }

    public function index(): void {
        // Must have at least one of customers or suppliers permission
        if (!$this->canViewCustomers() && !$this->canViewSuppliers()) {
            Auth::authorize('customers', 'view'); // will show 403
        }

        $type = $this->input('type', 'all', 'get');

        // Restrict based on permissions
        if (!$this->canViewSuppliers()) {
            $type = 'customer';
        } elseif (!$this->canViewCustomers()) {
            $type = 'supplier';
        }

        $parties   = $this->partyModel->getByType($type);
        $pageTitle = 'Party Master';
        $page      = 'parties';

        ob_start();
        include __DIR__ . '/../views/parties/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        // Must have add permission for at least one
        if (!Auth::can('customers', 'add') && !Auth::can('suppliers', 'add')) {
            Auth::authorize('customers', 'add');
        }
        $nextCode  = $this->partyModel->nextPartyCode();
        $pageTitle = 'New Party';
        $page      = 'parties';

        ob_start();
        include __DIR__ . '/../views/parties/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        if (!$this->isPost()) { $this->redirect('?page=parties&action=create'); }

        $errors = $this->validate(['name' => 'required', 'type' => 'required']);
        if (!empty($errors)) { $this->flash('error', implode(' ', $errors)); $this->redirect('?page=parties&action=create'); }

        // Check permission based on type being created
        $type = $this->input('type');
        Auth::authorize($this->partyModule($type), 'add');

        $id = $this->partyModel->create([
            'name'            => $this->input('name'),
            'contact_person'  => $this->input('contact_person'),
            'type'            => $this->input('type'),
            'phone'           => $this->input('phone'),
            'phone2'          => $this->input('phone2'),
            'email'           => $this->input('email'),
            'address'         => $this->input('address'),
            'city'            => $this->input('city'),
            'country'         => $this->input('country'),
            'tax_no'          => $this->input('tax_no'),
            'id_card'         => $this->input('id_card'),
            'credit_limit'    => $this->inputFloat('credit_limit'),
            'opening_balance' => $this->inputFloat('opening_balance'),
            'notes'           => $this->input('notes'),
        ]);

        if ($id) { $this->flash('success', 'Party added.'); } else { $this->flash('error', 'Failed.'); }
        $this->redirect('?page=parties');
    }

    public function edit(): void {
        $id    = $this->inputInt('id', 0, 'get');
        $party = $this->partyModel->find($id);
        if (!$party) { $this->flash('error', 'Party not found.'); $this->redirect('?page=parties'); }

        // Check edit permission based on party type
        Auth::authorize($this->partyModule($party['type']), 'edit');

        $editMode  = true;
        $pageTitle = 'Edit Party';
        $page      = 'parties';

        ob_start();
        include __DIR__ . '/../views/parties/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        if (!$this->isPost()) { $this->redirect('?page=parties'); }

        $id    = $this->inputInt('id');
        $party = $this->partyModel->find($id);
        if (!$party) { $this->flash('error', 'Party not found.'); $this->redirect('?page=parties'); return; }

        Auth::authorize($this->partyModule($party['type']), 'edit');
        $this->partyModel->update($id, [
            'name'            => $this->input('name'),
            'contact_person'  => $this->input('contact_person'),
            'type'            => $this->input('type'),
            'phone'           => $this->input('phone'),
            'phone2'          => $this->input('phone2'),
            'email'           => $this->input('email'),
            'address'         => $this->input('address'),
            'city'            => $this->input('city'),
            'country'         => $this->input('country'),
            'tax_no'          => $this->input('tax_no'),
            'id_card'         => $this->input('id_card'),
            'credit_limit'    => $this->inputFloat('credit_limit'),
            'opening_balance' => $this->inputFloat('opening_balance'),
            'notes'           => $this->input('notes'),
            'is_active'       => $this->inputInt('is_active'),
        ]);

        $this->flash('success', 'Party updated.');
        $this->redirect('?page=parties');
    }

    public function detail(): void {
        $id    = $this->inputInt('id', 0, 'get');
        $party = $this->partyModel->findWithBalance($id);
        if (!$party) { $this->flash('error', 'Party not found.'); $this->redirect('?page=parties'); }

        // Check view permission based on party type
        Auth::authorize($this->partyModule($party['type']), 'view');

        $db               = Database::getInstance();
        $linkedSalesCount = (int) ($db->fetchOne(
            "SELECT COUNT(*) AS c FROM sales WHERE party_id = ? AND status != 'cancelled'",
            [$id]
        )['c'] ?? 0);

        $cancelledSalesCount = (int) ($db->fetchOne(
            "SELECT COUNT(*) AS c FROM sales WHERE party_id = ? AND status = 'cancelled'",
            [$id]
        )['c'] ?? 0);

        $cancelledSalesList = [];
        if ($cancelledSalesCount > 0) {
            $cancelledSalesList = $db->fetchAll(
                "SELECT id, invoice_no, date, grand_total FROM sales
                 WHERE party_id = ? AND status = 'cancelled'
                 ORDER BY date ASC, id ASC",
                [$id]
            );
        }

        $ledger         = $this->partyModel->getLedger($id);
        $ledgerMismatch = $linkedSalesCount > 0 && empty($ledger);

        $ledgerHasSale = false;
        foreach ($ledger as $row) {
            if (($row['type'] ?? '') === 'sale') {
                $ledgerHasSale = true;
                break;
            }
        }
        // Only flag “wrong party” if there is no sale history at all (not explained by voided invoices).
        $ledgerReturnWrongParty = $linkedSalesCount === 0 && $cancelledSalesCount === 0 && !$ledgerHasSale && !empty($ledger);

        $pageTitle = $party['name'];
        $page      = 'parties';

        ob_start();
        include __DIR__ . '/../views/parties/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function agentStatement(): void {
        Auth::authorize('sales', 'view');

        $id    = $this->inputInt('id', 0, 'get');
        $db    = Database::getInstance();
        $party = $db->fetchOne("SELECT * FROM parties WHERE id = ?", [$id]);

        if (!$party) {
            $this->flash('error', 'Agent not found.');
            $this->redirect('?page=parties');
        }

        $whId = Auth::warehouseId();

        // All invoices for this agent — scoped to current warehouse
        $invoices = $db->fetchAll(
            "SELECT s.id, s.invoice_no, s.date, s.grand_total, s.paid_amount, s.balance, s.status,
                    w.name as warehouse_name,
                    COUNT(si.id) as item_count,
                    SUM(si.quantity) as total_qty
             FROM sales s
             LEFT JOIN warehouses w ON w.id = s.warehouse_id
             LEFT JOIN sale_items si ON si.sale_id = s.id
             WHERE s.party_id = ? AND s.warehouse_id = ? AND s.status != 'cancelled'
             GROUP BY s.id
             ORDER BY s.date ASC, s.id ASC",
            [$id, $whId]
        );

        // All payments for this agent — scoped to current warehouse
        $payments = $db->fetchAll(
            "SELECT py.*, a.name as account_name
             FROM payments py
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.party_id = ? AND py.warehouse_id = ? AND py.payment_type = 'in'
             ORDER BY py.date ASC, py.id ASC",
            [$id, $whId]
        );

        // Current IMEIs with this agent — scoped to current warehouse
        $imeis = $db->fetchAll(
            "SELECT ir.imei, ir.status, ir.updated_at,
                    i.name as item_name, i.sku,
                    s.invoice_no, s.date as dispatch_date,
                    DATEDIFF(CURDATE(), s.date) as days_out
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             WHERE s.party_id = ? AND s.warehouse_id = ? AND ir.status = 'sold' AND s.status != 'cancelled'
             ORDER BY s.date ASC",
            [$id, $whId]
        );

        // Summary numbers
        $totalDispatched  = array_sum(array_column($invoices, 'grand_total'));
        $totalPaid        = array_sum(array_column($payments, 'amount'));
        $totalOutstanding = array_sum(array_column($invoices, 'balance'));
        $totalIMEIsOut    = count($imeis);

        // Outstanding invoices only
        $unpaidInvoices = array_filter($invoices, fn($inv) => $inv['status'] !== 'paid');

        $pageTitle = 'Agent Statement: ' . $party['name'];
        $page      = 'parties';

        ob_start();
        include __DIR__ . '/../views/parties/agent_statement.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
