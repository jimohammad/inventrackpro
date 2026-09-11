<?php

require_once __DIR__ . '/BaseController.php';

class SupplierContactController extends BaseController {

    private const ALLOWED_COUNTRIES = ['UAE', 'Hongkong', 'China', 'India', 'Netherlands', 'New Zealand', 'Other'];

    private Database $db;
    private static ?bool $schemaReady = null;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    public function index(): void {
        Auth::authorize('supplier_contacts', 'view');

        $contacts = $this->db->fetchAll(
            "SELECT sc.*, u.name as created_by_name
             FROM supplier_contacts sc
             LEFT JOIN users u ON u.id = sc.created_by
             WHERE sc.is_active = 1 OR sc.is_active IS NULL
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

        try {
            $this->db->insert(
                "INSERT INTO supplier_contacts (company_name, contact_person, contact_person_2, address, mobile, email, wechat, mobile_2, wechat_2, country, product_type, notes, is_active, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?)",
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
                    $this->normalizeCountry($this->input('country')),
                    $this->input('product_type') ?: 'Mobile Phones',
                    $this->input('notes') ?: null,
                    Auth::id(),
                ]
            );
            $this->flash('success', 'Supplier contact added.');
        } catch (Throwable $e) {
            error_log('SupplierContactController::store failed: ' . $e->getMessage());
            $this->flash('error', 'Could not save supplier contact. Please try again.');
        }

        $this->redirect('?page=suppliercontacts');
    }

    public function update(): void {
        Auth::authorize('supplier_contacts', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=suppliercontacts'); return; }

        $id = $this->inputInt('id');
        try {
            $this->db->execute(
                "UPDATE supplier_contacts SET company_name=?, contact_person=?, contact_person_2=?, address=?, mobile=?, email=?, wechat=?, mobile_2=?, wechat_2=?, country=?, product_type=?, notes=?, is_active=1 WHERE id=?",
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
                    $this->normalizeCountry($this->input('country')),
                    $this->input('product_type') ?: 'Mobile Phones',
                    $this->input('notes') ?: null,
                    $id,
                ]
            );
            $this->flash('success', 'Supplier contact updated.');
        } catch (Throwable $e) {
            error_log('SupplierContactController::update failed: ' . $e->getMessage());
            $this->flash('error', 'Could not update supplier contact. Please try again.');
        }

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

    private function normalizeCountry(?string $country): string {
        $country = trim((string) $country);
        if ($country === 'Dubai') {
            return 'UAE';
        }
        if ($country === '' || !in_array($country, self::ALLOWED_COUNTRIES, true)) {
            return 'Other';
        }
        return $country;
    }

    /**
     * Country used to be ENUM('Dubai','Hongkong','China','Other').
     * India / UAE values were rejected or stored blank, so contacts vanished from the list.
     */
    private function ensureSchema(): void {
        if (self::$schemaReady === true) {
            return;
        }
        if (self::$schemaReady === false) {
            return;
        }

        try {
            $col = $this->db->fetchOne(
                "SELECT COLUMN_TYPE, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'supplier_contacts'
                   AND COLUMN_NAME = 'country'
                 LIMIT 1"
            );

            if ($col) {
                $dataType = strtolower((string) ($col['DATA_TYPE'] ?? ''));
                if ($dataType === 'enum' || !in_array($dataType, ['varchar', 'char', 'text'], true)) {
                    $this->db->execute(
                        "ALTER TABLE supplier_contacts
                         MODIFY COLUMN country VARCHAR(50) NOT NULL DEFAULT 'UAE'"
                    );
                }

                $this->db->execute(
                    "UPDATE supplier_contacts SET country = 'UAE' WHERE country = 'Dubai'"
                );
                $this->db->execute(
                    "UPDATE supplier_contacts SET country = 'Other' WHERE country = '' OR country IS NULL"
                );
            }

            $activeCol = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'supplier_contacts'
                   AND COLUMN_NAME = 'is_active'
                 LIMIT 1"
            );
            if ($activeCol) {
                $this->db->execute(
                    "UPDATE supplier_contacts SET is_active = 1 WHERE is_active IS NULL"
                );
            }

            self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('[SupplierContacts] ensureSchema failed: ' . $e->getMessage());
            self::$schemaReady = false;
        }
    }
}
