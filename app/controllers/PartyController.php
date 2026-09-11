<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Payment.php';

class PartyController extends BaseController {
    private Party $partyModel;
    private Payment $paymentModel;

    private const TRADE_LICENSE_MAX_BYTES = 5242880; // 5 MB
    private const TRADE_LICENSE_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    public function __construct() {
        parent::__construct();
        $this->partyModel   = new Party();
        $this->paymentModel = new Payment();
    }

    /** Supplier-side party types that store a trade license. */
    private function isSupplierSideType(string $type): bool {
        return in_array($type, ['supplier', 'both', 'freight_forwarder'], true);
    }

    /**
     * Handle trade license upload / remove. Returns [relativePath|null, error|null].
     * Relative path is under assets/uploads/ (e.g. parties/tl_xxx.pdf).
     */
    private function processTradeLicenseUpload(?string $existingFile, string $type): array {
        if (!$this->isSupplierSideType($type)) {
            if ($existingFile) {
                $this->deleteTradeLicenseFile($existingFile);
            }
            return [null, null];
        }

        $remove = !empty($_POST['remove_trade_license']);
        $file   = $_FILES['trade_license_file'] ?? null;
        $hasUpload = is_array($file)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($remove && !$hasUpload) {
            if ($existingFile) {
                $this->deleteTradeLicenseFile($existingFile);
            }
            return [null, null];
        }

        if (!$hasUpload) {
            return [$existingFile, null];
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return [$existingFile, 'Trade license upload failed. Please try again.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::TRADE_LICENSE_MAX_BYTES) {
            return [$existingFile, 'Trade license must be a PDF or image under 5 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [$existingFile, 'Trade license upload was invalid.'];
        }

        $mime = $this->detectUploadedMime($tmp);
        if ($mime === null || !isset(self::TRADE_LICENSE_MIME[$mime])) {
            return [$existingFile, 'Trade license must be PDF, JPG, PNG, or WEBP.'];
        }

        $ext = self::TRADE_LICENSE_MIME[$mime];
        $dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'parties';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [$existingFile, 'Could not create upload folder for trade license.'];
        }

        $basename = 'tl_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destAbs  = $dir . DIRECTORY_SEPARATOR . $basename;
        if (!move_uploaded_file($tmp, $destAbs)) {
            return [$existingFile, 'Could not save trade license file.'];
        }

        if ($existingFile) {
            $this->deleteTradeLicenseFile($existingFile);
        }

        return ['parties/' . $basename, null];
    }

    /** Detect MIME of an uploaded/temp file; null if unknown. */
    private function detectUploadedMime(string $path): ?string {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        return null;
    }

    private function deleteTradeLicenseFile(?string $relativePath): void {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        if (!str_starts_with($relativePath, 'parties/')) {
            return;
        }
        $abs = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /**
     * @return array{0:?string,1:?string} [date Y-m-d or null, error or null]
     */
    private function parseTradeLicenseExpiry(string $type): array {
        if (!$this->isSupplierSideType($type)) {
            return [null, null];
        }
        $raw = trim((string) $this->input('trade_license_expires_on'));
        if ($raw === '') {
            return [null, null];
        }
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) {
            return [null, 'Trade license expiry date is invalid.'];
        }
        return [$raw, null];
    }

    /** Authenticated download of a party's trade license file. */
    public function downloadTradeLicense(): void {
        $id    = $this->inputInt('id', 0, 'get');
        $party = $this->partyModel->find($id);
        if (!$party) {
            $this->flash('error', 'Party not found.');
            $this->redirect('?page=parties');
            return;
        }

        $this->authorizeParty((string) $party['type'], 'view');

        if (!$this->partyModel->isVisibleInCurrentWarehouse($id)) {
            $this->flash('error', 'This party is not linked to the current branch.');
            $this->redirect('?page=parties');
            return;
        }

        $rel = (string) ($party['trade_license_file'] ?? '');
        $rel = str_replace(['\\', '..'], ['/', ''], $rel);
        if ($rel === '' || !str_starts_with($rel, 'parties/')) {
            $this->flash('error', 'No trade license on file.');
            $this->redirect('?page=parties&action=detail&id=' . $id);
            return;
        }

        $abs = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $realBase = realpath(rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'parties');
        $realFile = realpath($abs);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            $this->flash('error', 'Trade license file is missing.');
            $this->redirect('?page=parties&action=detail&id=' . $id);
            return;
        }

        $mime = $this->detectUploadedMime($realFile) ?? 'application/octet-stream';
        $name = basename($realFile);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($realFile);
        exit;
    }

    /** Map party type to permission module. */
    private function partyModule(string $type): string {
        return in_array($type, ['supplier', 'freight_forwarder'], true) ? 'suppliers' : 'customers';
    }

    /** Customers: Party Master or customers module. Suppliers: suppliers module only (not Party Master). */
    private function authorizeParty(string $type, string $action): void {
        if (in_array($type, ['supplier', 'freight_forwarder'], true)) {
            Auth::authorize('suppliers', $action);
            return;
        }
        Auth::authorizeAny(['parties', 'customers'], $action);
    }

    private function canViewCustomers(): bool {
        return Auth::canAny(['parties', 'customers'], 'view');
    }

    private function canViewSuppliers(): bool {
        return Auth::can('suppliers', 'view');
    }

    public function index(): void {
        if (!$this->canViewCustomers() && !$this->canViewSuppliers()) {
            Auth::authorize('parties', 'view');
        }

        $hasPartyMaster = Auth::can('parties', 'view');
        $type = $this->resolveListType($hasPartyMaster);

        $balanceFilter = $this->input('balance', 'all', 'get');
        if (!in_array($balanceFilter, ['all', 'due', 'clear'], true)) {
            $balanceFilter = 'all';
        }

        // Names only — ledger UNION runs after first paint via listBalances AJAX.
        $parties = $this->partyModel->getByType($type, false);

        if ($type === 'customer') {
            $pageTitle = 'Customers';
        } elseif ($type === 'freight_forwarder') {
            $pageTitle = 'Freight Forwarders';
        } elseif ($type === 'supplier') {
            $pageTitle = 'Suppliers';
        } elseif ($hasPartyMaster) {
            $pageTitle = 'Party Master';
        } else {
            $pageTitle = 'Parties';
        }
        $page = 'parties';
        // List uses a plain table + JS filter — DataTables CSS/JS here only adds download
        // weight and a resize/scrollbar shake loop. Ledger detail still loads DataTables.
        $skipListAssets = true;
        $skipJquery     = true;

        ob_start();
        include __DIR__ . '/../views/parties/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * AJAX: Party Master balance column (same UNION as getByType, after HTML paint).
     */
    public function listBalances(): void {
        header('Content-Type: application/json');
        if (!$this->canViewCustomers() && !$this->canViewSuppliers()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'balances' => []]);
            return;
        }

        $hasPartyMaster = Auth::can('parties', 'view');
        $type = $this->resolveListType($hasPartyMaster);
        $parties = $this->partyModel->getByType($type, true);

        $balances = [];
        $logixZero = null;
        $hkName = defined('IMPORT_FREIGHT_HK_FORWARDER_NAME')
            ? (string) IMPORT_FREIGHT_HK_FORWARDER_NAME
            : 'Logix One FZE';

        foreach ($parties as $p) {
            $id = (int) ($p['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $due = (float) ($p['balance_due'] ?? 0);
            $balances[(string) $id] = round($due, 3);

            if ($type !== 'freight_forwarder' || $logixZero !== null) {
                continue;
            }
            if (!(Auth::isAdmin() || Auth::can('payments', 'delete'))) {
                continue;
            }
            $pname = (string) ($p['name'] ?? '');
            $pcode = (string) ($p['party_code'] ?? '');
            $isLogix = $pcode === '26049'
                || stripos($pname, 'Logix') !== false
                || strcasecmp($pname, $hkName) === 0;
            if (!$isLogix) {
                continue;
            }
            $rounded = round($due, 3);
            if (abs($rounded) > 0.001) {
                $logixZero = [
                    'party_id'   => $id,
                    'party_name' => $pname,
                    'balance'    => $rounded,
                ];
            }
        }

        echo json_encode([
            'ok'        => true,
            'balances'  => $balances,
            'logixZero' => $logixZero,
        ]);
    }

    /** Lock type tabs to modules the user actually has. */
    private function resolveListType(bool $hasPartyMaster): string {
        $type = $this->input('type', 'all', 'get');
        $allowed = ['all', 'customer', 'supplier', 'both', 'freight_forwarder'];
        if (!in_array($type, $allowed, true)) {
            $type = 'all';
        }

        if (!$hasPartyMaster) {
            if ($this->canViewCustomers() && !$this->canViewSuppliers()) {
                return 'customer';
            }
            if ($this->canViewSuppliers() && !$this->canViewCustomers()) {
                return 'supplier';
            }
            if (!$this->canViewSuppliers()) {
                return 'customer';
            }
            if (!$this->canViewCustomers()) {
                return 'supplier';
            }
            return $type;
        }
        if (!$this->canViewSuppliers()) {
            return 'customer';
        }
        if (!$this->canViewCustomers()) {
            return 'supplier';
        }

        return $type;
    }

    /**
     * Admin: force Logix One freight ledger to Clear on Party Master (opening offset).
     */
    public function zeroLogixBalance(): void {
        // Opening-balance offset is high risk — admin only (not payments:delete).
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Only admin can zero Logix balance.');
            $this->redirect('?page=parties&type=freight_forwarder');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=parties&type=freight_forwarder');
            return;
        }

        require_once __DIR__ . '/../services/PartyLedgerZeroService.php';
        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';

        $db = Database::getInstance();
        $wh = Auth::warehouseId() ? (int) Auth::warehouseId() : 1;
        $hkName = defined('IMPORT_FREIGHT_HK_FORWARDER_NAME')
            ? (string) IMPORT_FREIGHT_HK_FORWARDER_NAME
            : 'Logix One FZE';

        $party = $db->fetchOne(
            "SELECT id, name, party_code FROM parties
             WHERE party_code = '26049'
                OR name LIKE ?
                OR LOWER(name) = LOWER(?)
             ORDER BY CASE WHEN party_code = '26049' THEN 0 ELSE 1 END
             LIMIT 1",
            ['%Logix%', $hkName]
        );
        if (!$party) {
            $this->flash('error', 'Logix One party not found.');
            $this->redirect('?page=parties&type=freight_forwarder');
            return;
        }

        $partyId = (int) $party['id'];

        // Cancel leftover open HK freight so liability does not fight the opening offset.
        $openCharges = $db->fetchAll(
            "SELECT shipment_item_charge_id AS charge_id
             FROM import_payable_accruals
             WHERE party_id = ? AND status = 'open' AND leg = 'freight_hk'",
            [$partyId]
        );
        if ($openCharges !== []) {
            ImportPayableAccrualService::cancelOpenByCharges(
                $db,
                'shipment_freight_hk',
                array_map(static fn ($r) => (int) $r['charge_id'], $openCharges),
                'WRITE-OFF before Logix force-zero on Freight Forwarders'
            );
        }

        $result = PartyLedgerZeroService::forceNetToZero(
            $db,
            $this->partyModel,
            $partyId,
            $wh,
            'Freight Forwarders page'
        );

        $this->logActivity(
            'zero_logix_balance',
            'parties',
            $partyId,
            $result['message']
        );

        $this->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->redirect('?page=parties&type=freight_forwarder');
    }

    public function create(): void {
        if (!Auth::canAny(['parties', 'customers', 'suppliers'], 'add')) {
            Auth::authorize('parties', 'add');
        }
        $nextCode  = $this->partyModel->nextPartyCode();
        $createType = $this->input('type', 'customer', 'get');
        if (!in_array($createType, ['customer', 'supplier', 'freight_forwarder', 'both'], true)) {
            $createType = 'customer';
        }
        if (in_array($createType, ['supplier', 'freight_forwarder', 'both'], true) && !Auth::can('suppliers', 'add')) {
            $createType = 'customer';
        }
        $pageTitle = match ($createType) {
            'supplier' => 'New Supplier',
            'freight_forwarder' => 'New Freight Forwarder',
            'both' => 'New Party',
            default => 'New Customer',
        };
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
        $this->authorizeParty($type, 'add');

        [$licenseFile, $uploadError] = $this->processTradeLicenseUpload(null, $type);
        if ($uploadError !== null) {
            $this->flash('error', $uploadError);
            $this->redirect('?page=parties&action=create');
            return;
        }

        [$expiresOn, $expiryError] = $this->parseTradeLicenseExpiry($type);
        if ($expiryError !== null) {
            if ($licenseFile) {
                $this->deleteTradeLicenseFile($licenseFile);
            }
            $this->flash('error', $expiryError);
            $this->redirect('?page=parties&action=create');
            return;
        }

        $id = $this->partyModel->create([
            'name'                     => $this->input('name'),
            'contact_person'           => $this->input('contact_person'),
            'type'                     => $type,
            'customer_kind'            => $this->input('customer_kind'),
            'phone'                    => $this->input('phone'),
            'phone2'                   => $this->input('phone2'),
            'email'                    => $this->input('email'),
            'address'                  => $this->input('address'),
            'city'                     => $this->input('city'),
            'country'                  => $this->input('country'),
            'tax_no'                   => $this->input('tax_no'),
            'id_card'                  => $this->input('id_card'),
            'trade_license_file'       => $licenseFile,
            'trade_license_expires_on' => $expiresOn,
            'credit_limit'             => $this->inputFloat('credit_limit'),
            // Opening balance is locked in UI; new parties always start at 0.
            'opening_balance'          => 0.0,
            'notes'                    => $this->input('notes'),
        ]);

        if ($id) {
            Party::clearFilterListCache();
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', 'Party added.');
        } else {
            if ($licenseFile) {
                $this->deleteTradeLicenseFile($licenseFile);
            }
            $this->flash('error', 'Failed.');
        }
        $this->redirect('?page=parties');
    }

    public function edit(): void {
        $id    = $this->inputInt('id', 0, 'get');
        $party = $this->partyModel->find($id);
        if (!$party) { $this->flash('error', 'Party not found.'); $this->redirect('?page=parties'); }

        // Check edit permission based on party type
        $this->authorizeParty((string) $party['type'], 'edit');

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

        $this->authorizeParty((string) $party['type'], 'edit');

        $type = $this->input('type');
        $this->authorizeParty($type, 'edit');

        $existingFile = $party['trade_license_file'] ?? null;
        [$licenseFile, $uploadError] = $this->processTradeLicenseUpload(
            is_string($existingFile) ? $existingFile : null,
            $type
        );
        if ($uploadError !== null) {
            $this->flash('error', $uploadError);
            $this->redirect('?page=parties&action=edit&id=' . $id);
            return;
        }

        [$expiresOn, $expiryError] = $this->parseTradeLicenseExpiry($type);
        if ($expiryError !== null) {
            $this->flash('error', $expiryError);
            $this->redirect('?page=parties&action=edit&id=' . $id);
            return;
        }

        $this->partyModel->update($id, [
            'name'                     => $this->input('name'),
            'contact_person'           => $this->input('contact_person'),
            'type'                     => $type,
            'customer_kind'            => $this->input('customer_kind'),
            'phone'                    => $this->input('phone'),
            'phone2'                   => $this->input('phone2'),
            'email'                    => $this->input('email'),
            'address'                  => $this->input('address'),
            'city'                     => $this->input('city'),
            'country'                  => $this->input('country'),
            'tax_no'                   => $this->input('tax_no'),
            'id_card'                  => $this->input('id_card'),
            'trade_license_file'       => $licenseFile,
            'trade_license_expires_on' => $expiresOn,
            'credit_limit'             => $this->inputFloat('credit_limit'),
            'notes'                    => $this->input('notes'),
            'is_active'                => $this->inputInt('is_active'),
        ]);

        Party::clearFilterListCache();
        self::clearDashboardCache(Auth::warehouseId());

        $this->flash('success', 'Party updated.');
        $this->redirect('?page=parties');
    }

    public function detail(): void {
        $id = $this->inputInt('id', 0, 'get');
        try {
            $party = $this->partyModel->findWithBalance($id);
            if (!$party) { $this->flash('error', 'Party not found.'); $this->redirect('?page=parties'); }

            if (!$this->partyModel->isVisibleInCurrentWarehouse($id)) {
                $this->flash('error', 'This party is not linked to the current branch.');
                $this->redirect('?page=parties');
            }

            $this->authorizeParty((string) $party['type'], 'view');

            $whId = (int) Auth::warehouseId();
            $db   = Database::getInstance();

            $linkedSalesCount = (int) ($db->fetchOne(
                "SELECT COUNT(*) AS c FROM sales WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'",
                [$id, $whId]
            )['c'] ?? 0);

            $linkedPurchasesCount = (int) ($db->fetchOne(
                "SELECT COUNT(*) AS c FROM purchases WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'",
                [$id, $whId]
            )['c'] ?? 0);

            $cancelledSalesCount = (int) ($db->fetchOne(
                "SELECT COUNT(*) AS c FROM sales WHERE party_id = ? AND warehouse_id = ? AND status = 'cancelled'",
                [$id, $whId]
            )['c'] ?? 0);

            $cancelledSalesList = [];
            if ($cancelledSalesCount > 0) {
                $cancelledSalesList = $db->fetchAll(
                    "SELECT id, invoice_no, date, grand_total FROM sales
                     WHERE party_id = ? AND warehouse_id = ? AND status = 'cancelled'
                     ORDER BY date ASC, id ASC",
                    [$id, $whId]
                );
            }

            $ledger         = $this->partyModel->getLedger($id);
            $ledgerMismatch = $linkedSalesCount > 0 && empty($ledger);
            $ledgerOpeningBal = $this->partyModel->scopedOpeningBalance($party, $whId);

            // Sale-return mismatch: customer/both only — suppliers with purchases/payments must not trigger this.
            $isCustomerSide = in_array($party['type'] ?? '', ['customer', 'both'], true);
            $saleReturnCount = 0;
            $returnPartyMismatchCount = 0;
            if ($isCustomerSide) {
                $saleReturnCount = (int) ($db->fetchOne(
                    "SELECT COUNT(*) AS c FROM `returns`
                     WHERE party_id = ? AND warehouse_id = ? AND type = 'sale_return' AND status = 'approved'",
                    [$id, $whId]
                )['c'] ?? 0);
                $returnPartyMismatchCount = (int) ($db->fetchOne(
                    "SELECT COUNT(*) AS c FROM `returns` r
                     INNER JOIN sales s ON s.id = r.ref_id
                     WHERE r.party_id = ? AND r.warehouse_id = ? AND r.type = 'sale_return' AND r.status = 'approved'
                     AND s.party_id != r.party_id",
                    [$id, $whId]
                )['c'] ?? 0);
            }
            $ledgerReturnWrongParty = $isCustomerSide && (
                $returnPartyMismatchCount > 0
                || ($saleReturnCount > 0 && $linkedSalesCount === 0)
            );

            // Invoice badge drift vs party receipts (admin repair tool). Does not change net ledger.
            $saleAllocationGap = 0.0;
            if ($isCustomerSide && $whId > 0 && method_exists($this->paymentModel, 'saleAllocationGap')) {
                $saleAllocationGap = $this->paymentModel->saleAllocationGap($id, $whId);
            }

            $partyMonthCompare = null;
            if ($isCustomerSide && $whId > 0) {
                $salesCompare = $db->fetchOne(
                    "SELECT
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) AS this_month,
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                            AND date < DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) AS last_month,
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), '%Y-%m-01')
                            AND date < DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) AS prev_month
                     FROM sales
                     WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'",
                    [$id, $whId]
                );
                $receiptsCompare = $db->fetchOne(
                    "SELECT
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN amount ELSE 0 END), 0) AS this_month,
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                            AND date < DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN amount ELSE 0 END), 0) AS last_month,
                        COALESCE(SUM(CASE WHEN date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), '%Y-%m-01')
                            AND date < DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') THEN amount ELSE 0 END), 0) AS prev_month
                     FROM payments
                     WHERE party_id = ?
                       AND warehouse_id = ?
                       AND payment_type = 'in'
                       AND ref_type != 'discount'
                       AND status = 'active'",
                    [$id, $whId]
                );
                $partyMonthCompare = [
                    'this_label' => date('M Y'),
                    'last_label' => date('M Y', strtotime('first day of last month')),
                    'prev_label' => date('M Y', strtotime('first day of -2 months')),
                    'sales' => [
                        'this_month' => (float) ($salesCompare['this_month'] ?? 0),
                        'last_month' => (float) ($salesCompare['last_month'] ?? 0),
                        'prev_month' => (float) ($salesCompare['prev_month'] ?? 0),
                    ],
                    'receipts' => [
                        'this_month' => (float) ($receiptsCompare['this_month'] ?? 0),
                        'last_month' => (float) ($receiptsCompare['last_month'] ?? 0),
                        'prev_month' => (float) ($receiptsCompare['prev_month'] ?? 0),
                    ],
                ];
            }

            $pageTitle = $party['name'];
            $page      = 'parties';

            ob_start();
            include __DIR__ . '/../views/parties/view.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        } catch (Throwable $e) {
            error_log('[PartyController::detail] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = 'Could not open party ledger.';
            if (Auth::isAdmin()) {
                $msg .= ' ' . $e->getMessage();
            }
            $this->flash('error', $msg);
            $this->redirect('?page=parties');
        }
    }

    /**
     * Admin repair: replay FIFO payment allocation onto sale/purchase invoices for this party.
     * Party ledger (payments) is unchanged — only invoice paid/balance/status badges are rebuilt.
     */
    public function rebuildAllocation(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=parties');
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('?page=parties');
            return;
        }

        $id = $this->inputInt('party_id');
        if ($id <= 0) {
            $this->flash('error', 'Invalid party.');
            $this->redirect('?page=parties');
            return;
        }

        $party = $this->partyModel->find($id);
        if (!$party) {
            $this->flash('error', 'Party not found.');
            $this->redirect('?page=parties');
            return;
        }

        if (!$this->partyModel->isVisibleInCurrentWarehouse($id)) {
            $this->flash('error', 'This party is not linked to the current branch.');
            $this->redirect('?page=parties');
            return;
        }

        $whId = (int) Auth::warehouseId();
        if ($whId <= 0) {
            $this->flash('error', 'Select a warehouse first.');
            $this->redirect('?page=parties&action=detail&id=' . $id);
            return;
        }

        $result = $this->paymentModel->rebuildFifoAllocationForParty($id, $whId);
        if ($result === false) {
            $this->flash('error', 'Rebuild failed: ' . ($this->paymentModel->getLastError() ?: 'unknown error'));
            $this->redirect('?page=parties&action=detail&id=' . $id);
            return;
        }

        $msg = sprintf(
            'Invoice allocation rebuilt for %s: %d sale(s) and %d purchase(s) updated.',
            $party['name'] ?? ('#' . $id),
            (int) $result['sales_changed'],
            (int) $result['purchases_changed']
        );
        $leftoverSale = (float) $result['sale_leftover'];
        $leftoverPur  = (float) $result['purchase_leftover'];
        if ($leftoverSale > 0.001 || $leftoverPur > 0.001) {
            $msg .= sprintf(
                ' Unallocated advance remains on ledger (sales %.3f, purchases %.3f) — party balance is still correct.',
                $leftoverSale,
                $leftoverPur
            );
        }

        $this->logActivity(
            'rebuild_payment_allocation',
            'parties',
            $id,
            $msg
        );
        self::clearDashboardCache($whId);
        $this->flash('success', $msg);
        $this->redirect('?page=parties&action=detail&id=' . $id);
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

        $token = $this->partyModel->ensureStatementToken((int) $party['id']);
        if ($token !== null) {
            $party['statement_token'] = $token;
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
