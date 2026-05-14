<?php

require_once __DIR__ . '/BaseController.php';

class SupplierContactController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index(): void {
        Auth::authorize('supplier_contacts', 'view');

        $contacts = $this->db->fetchAll(
            "SELECT sc.*, u.name as created_by_name
             FROM supplier_contacts sc
             LEFT JOIN users u ON u.id = sc.created_by
             WHERE sc.is_active = 1
             ORDER BY sc.country ASC, sc.product_type ASC, sc.company_name ASC"
        );

        $pageTitle = 'Supplier Contacts';
        $page      = 'suppliercontacts';

        ob_start();
        include __DIR__ . '/../views/suppliers/contacts.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('supplier_contacts', 'add');
        if (!$this->isPost()) { $this->redirect('?page=suppliercontacts'); return; }

        $this->db->insert(
            "INSERT INTO supplier_contacts (company_name, contact_person, contact_person_2, address, mobile, email, wechat, mobile_2, wechat_2, country, product_type, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $this->input('company_name'),
                $this->input('contact_person') ?: null,
                $this->input('contact_person_2') ?: null,
                $this->input('address') ?: null,
                $this->input('mobile') ?: null,
                $this->input('email') ?: null,
                $this->input('wechat') ?: null,
                $this->input('mobile_2') ?: null,
                $this->input('wechat_2') ?: null,
                $this->input('country') ?: 'Dubai',
                $this->input('product_type') ?: 'Mobile Phones',
                null,
                Auth::id(),
            ]
        );

        $this->flash('success', 'Supplier contact added.');
        $this->redirect('?page=suppliercontacts');
    }

    public function update(): void {
        Auth::authorize('supplier_contacts', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=suppliercontacts'); return; }

        $id = $this->inputInt('id');
        $this->db->execute(
            "UPDATE supplier_contacts SET company_name=?, contact_person=?, contact_person_2=?, address=?, mobile=?, email=?, wechat=?, mobile_2=?, wechat_2=?, country=?, product_type=?, notes=? WHERE id=?",
            [
                $this->input('company_name'),
                $this->input('contact_person') ?: null,
                $this->input('contact_person_2') ?: null,
                $this->input('address') ?: null,
                $this->input('mobile') ?: null,
                $this->input('email') ?: null,
                $this->input('wechat') ?: null,
                $this->input('mobile_2') ?: null,
                $this->input('wechat_2') ?: null,
                $this->input('country') ?: 'Dubai',
                $this->input('product_type') ?: 'Mobile Phones',
                $this->input('notes') ?: null,
                $id,
            ]
        );

        $this->flash('success', 'Supplier contact updated.');
        $this->redirect('?page=suppliercontacts');
    }

    public function delete(): void {
        Auth::authorize('supplier_contacts', 'delete');
        if (!$this->isPost()) { $this->redirect('?page=suppliercontacts'); return; }

        $id = $this->inputInt('id');
        $this->db->execute("DELETE FROM supplier_contacts WHERE id = ?", [$id]);

        $this->flash('success', 'Supplier contact deleted.');
        $this->redirect('?page=suppliercontacts');
    }
}
