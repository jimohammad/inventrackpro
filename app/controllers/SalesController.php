<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../helpers/WhatsApp.php';
require_once __DIR__ . '/../services/SaleValidator.php';
require_once __DIR__ . '/../services/SaleInvoiceFile.php';
require_once __DIR__ . '/../models/SaleEditRequest.php';

class SalesController extends BaseController {

    private Sale    $saleModel;
    private Item    $itemModel;
    private Party   $partyModel;
    private IMEI    $imeiModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel    = new Sale();
        $this->itemModel    = new Item();
        $this->partyModel   = new Party();
        $this->imeiModel    = new IMEI();
    }

    // Sales list page
    public function index(): void {
        Auth::authorize('sales', 'view');

        $viewVoided      = Auth::isAdmin() && ($this->input('view', '', 'get') === 'voided');
        $includeVoided   = Auth::isAdmin() && ($this->input('include_voided', '', 'get') === '1');

        $search  = $this->inputSearch('search', '', 'get');
        $status  = $this->input('status', '', 'get');
        $partyId = $this->inputInt('party_id', 0, 'get');
        $item    = $this->input('item', '', 'get');
        $hasEntity = ($item !== '') || ($search !== '') || ($partyId > 0);

        $dateRange = ListPage::resolveDateFiltersFromGet(1, 7);
        if ($hasEntity && !empty($dateRange['dates_defaulted'])) {
            $dateRange = [
                'from_date'       => '',
                'to_date'         => '',
                'all_dates'       => true,
                'dates_defaulted' => false,
            ];
        }
        $fromDate = (string) $dateRange['from_date'];
        $toDate   = (string) $dateRange['to_date'];
        if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = '';
        }
        if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $toDate = '';
        }

        $filters = [
            'search'         => $search,
            'status'         => $status,
            'party_id'       => $partyId,
            'item'           => $item,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'all_dates'      => ($fromDate === '' && $toDate === ''),
            'voided_only'    => $viewVoided,
            'include_voided' => $includeVoided && !$viewVoided,
        ];

        $listPage = $this->saleModel->getIndexPage($filters, Sale::INDEX_LIST_LIMIT);
        $sales    = $listPage['items'];
        $listTruncated = $listPage['truncated'];
        $listLimit     = $listPage['limit'];
        $datesDefaulted = $dateRange['dates_defaulted'];

        $filterCustomer = null;
        if ($partyId > 0) {
            $partyRow = $this->partyModel->find($partyId);
            if ($partyRow) {
                $filterCustomer = [
                    'id'   => (int) $partyRow['id'],
                    'name' => (string) ($partyRow['name'] ?? ''),
                ];
            }
        }
        $filterItem = null;
        if (ctype_digit((string) $item) && (int) $item > 0) {
            $itemRow = $this->itemModel->find((int) $item);
            if ($itemRow) {
                $filterItem = [
                    'id'   => (int) $itemRow['id'],
                    'name' => (string) ($itemRow['name'] ?? ''),
                    'sku'  => (string) ($itemRow['sku'] ?? ''),
                ];
            }
        }

        $pageTitle = $viewVoided ? 'Sales — voided invoices' : 'Sales';
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Create sale form
    /**
     * TEMP: cashier/viewer may set any unit price on new sales
     * (including below catalog). Keep false to enforce the min-price floor.
     */
    private const TEMP_ALLOW_SALESMAN_FREE_PRICE = false;

    public function create(): void {
        Auth::authorize('sales', 'add');

        $db         = Database::getInstance();
        $last       = $db->fetchOne("SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1");
        $lastNum    = $last ? (int) substr($last['invoice_no'], strlen(SALE_PREFIX)) : 0;
        $nextInv    = SALE_PREFIX . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        $saleDraft  = $this->consumeSaleDraft();
        $pageTitle  = 'New Sale';
        $page       = 'sales';
        $skipListAssets = true; // create uses neither DataTables nor Select2
        $allowSalesmanFreePrice = self::TEMP_ALLOW_SALESMAN_FREE_PRICE;

        // One-time token to prevent double-submit duplicate sales
        $_SESSION['sale_form_nonce'] = bin2hex(random_bytes(16));
        $saleFormNonce               = $_SESSION['sale_form_nonce'];

        ob_start();
        include __DIR__ . '/../views/sales/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    private function saveSaleDraftFromPost(): void {
        $rawItems = $_POST['items'] ?? [];
        $items    = [];
        foreach ($rawItems as $row) {
            $itemId = (int)($row['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $items[] = [
                'item_id'    => $itemId,
                'quantity'   => (int)($row['quantity'] ?? 0),
                'unit_price' => (float)($row['unit_price'] ?? 0),
                'discount'   => (float)($row['discount'] ?? 0),
                'imeis'      => (string)($row['imeis'] ?? ''),
            ];
        }

        $_SESSION['sale_create_draft'] = [
            'party_id'     => (int)($_POST['party_id'] ?? 0),
            'warehouse_id' => (int) Auth::warehouseId(),
            'date'         => (string)($_POST['date'] ?? date('Y-m-d')),
            'discount'     => (float)($_POST['discount'] ?? 0),
            'items'        => $items,
        ];
    }

    /**
     * Keep unsaved edit-page lines (scanned IMEIs) after a failed Save
     * (credit limit, stock, validation). Restored on the next edit load
     * until the invoice actually saves.
     */
    private function saveSaleEditDraftFromPost(int $saleId): void {
        if ($saleId <= 0) {
            return;
        }

        $newItems = [];
        foreach ($_POST['new_items'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $imeis = [];
            if (!empty($row['imeis'])) {
                if (is_array($row['imeis'])) {
                    $imeis = array_values(array_unique(array_filter(array_map(
                        static fn($v) => strtoupper(trim((string) $v)),
                        $row['imeis']
                    ))));
                } else {
                    $imeis = array_values(array_unique(array_filter(array_map(
                        static fn($v) => strtoupper(trim((string) $v)),
                        preg_split('/[\r\n,;]+/', (string) $row['imeis']) ?: []
                    ))));
                }
            }
            $newItems[] = [
                'itemId' => $itemId,
                'price'  => number_format((float) ($row['unit_price'] ?? 0), 3, '.', ''),
                'qty'    => max(1, (int) ($row['quantity'] ?? 1)),
                'imeis'  => $imeis,
            ];
        }

        $existing = [];
        foreach ($_POST['items'] ?? [] as $saleItemId => $row) {
            if (!is_array($row)) {
                continue;
            }
            $existing[(int) $saleItemId] = [
                'quantity'   => (int) ($row['quantity'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'deleted'    => !empty($row['deleted']) && (string) $row['deleted'] !== '0',
            ];
        }

        if (!isset($_SESSION['sale_edit_draft']) || !is_array($_SESSION['sale_edit_draft'])) {
            $_SESSION['sale_edit_draft'] = [];
        }
        $_SESSION['sale_edit_draft'][$saleId] = [
            'date'     => (string) ($_POST['date'] ?? ''),
            'discount' => (float) ($_POST['discount'] ?? 0),
            'notes'    => (string) ($_POST['notes'] ?? ''),
            'party_id' => (int) ($_POST['party_id'] ?? 0),
            'items'    => $newItems,
            'existing' => $existing,
        ];
    }

    private function peekSaleEditDraft(int $saleId): ?array {
        $draft = $_SESSION['sale_edit_draft'][$saleId] ?? null;
        if (!is_array($draft) || $saleId <= 0) {
            return null;
        }

        $newItems = is_array($draft['items'] ?? null) ? $draft['items'] : [];
        $existing = is_array($draft['existing'] ?? null) ? $draft['existing'] : [];
        $draftPartyId = (int) ($draft['party_id'] ?? 0);
        if ($newItems === [] && $existing === [] && $draftPartyId <= 0) {
            return null;
        }

        $itemIds = [];
        foreach ($newItems as $row) {
            $iid = (int) ($row['itemId'] ?? $row['item_id'] ?? 0);
            if ($iid > 0) {
                $itemIds[] = $iid;
            }
        }
        $itemIds = array_values(array_unique($itemIds));
        $meta = [];
        if (!empty($itemIds)) {
            $db = Database::getInstance();
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $rows = $db->fetchAll(
                "SELECT id, name, has_imei FROM items WHERE id IN ({$ph})",
                $itemIds
            );
            foreach ($rows as $r) {
                $meta[(int) $r['id']] = $r;
            }
        }

        foreach ($newItems as &$item) {
            $iid  = (int) ($item['itemId'] ?? $item['item_id'] ?? 0);
            $info = $meta[$iid] ?? null;
            $item['itemId']  = $iid;
            $item['name']    = (string) ($info['name'] ?? ('Item #' . $iid));
            $item['hasImei'] = !empty($info['has_imei']);
            $item['price']   = (string) ($item['price'] ?? number_format((float) ($item['unit_price'] ?? 0), 3, '.', ''));
            $item['imeis']   = array_values(array_filter(array_map('strval', $item['imeis'] ?? [])));
        }
        unset($item);

        $draft['items']    = $newItems;
        $draft['existing'] = $existing;
        return $draft;
    }

    private function clearSaleEditDraft(int $saleId): void {
        if (isset($_SESSION['sale_edit_draft'][$saleId])) {
            unset($_SESSION['sale_edit_draft'][$saleId]);
        }
    }

    /**
     * Block cross-branch access to a sale. Returns false after flash/JSON response.
     */
    private function assertSaleWarehouseAccess(?array $sale, bool $asJson = false): bool {
        if (!$sale) {
            if ($asJson) {
                echo json_encode(['success' => false, 'error' => 'Sale not found.']);
                return false;
            }
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return false;
        }

        $sessionWh = (int) Auth::warehouseId();
        if ($sessionWh > 0 && (int) ($sale['warehouse_id'] ?? 0) !== $sessionWh) {
            if ($asJson) {
                echo json_encode(['success' => false, 'error' => 'This sale belongs to a different warehouse.']);
                return false;
            }
            $this->flash('error', 'This sale belongs to a different warehouse.');
            $this->redirect('?page=sales');
            return false;
        }

        return true;
    }

    private function isCashierSaleEditor(): bool {
        return Auth::role() === 'cashier';
    }

    private function canEditSaleInvoice(array $sale): bool {
        if (Auth::isAdmin()) {
            return true;
        }
        if (!$this->isCashierSaleEditor()) {
            return false;
        }
        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);
        return SaleEditRequest::findActiveUnlock($db, (int) $sale['id'], (int) Auth::id()) !== null;
    }

    private function requireSaleEditAccess(?array $sale): bool {
        if (!$this->assertSaleWarehouseAccess($sale)) {
            return false;
        }
        if ($this->canEditSaleInvoice($sale)) {
            return true;
        }
        $id = (int) ($sale['id'] ?? 0);
        $this->flash('error', 'Admin must approve an edit request before a salesman can change this invoice.');
        $this->redirect($id > 0 ? ('?page=sales&action=detail&id=' . $id) : '?page=sales');
        return false;
    }

    /** @return array{pending: ?array, unlock: ?array, latest: ?array} */
    private function loadSaleEditRequestState(int $saleId): array {
        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);
        $userId = (int) Auth::id();
        $cashier = $this->isCashierSaleEditor();
        return [
            'pending' => SaleEditRequest::findPendingForSale($db, $saleId),
            'unlock'  => $cashier ? SaleEditRequest::findActiveUnlock($db, $saleId, $userId) : null,
            'latest'  => $cashier ? SaleEditRequest::findLatestForRequester($db, $saleId, $userId) : null,
        ];
    }

    private function consumeSaleDraft(): ?array {
        $draft = $_SESSION['sale_create_draft'] ?? null;
        unset($_SESSION['sale_create_draft']);
        if (!is_array($draft)) {
            return null;
        }

        $db = Database::getInstance();
        $partyId = (int)($draft['party_id'] ?? 0);
        if ($partyId > 0) {
            $this->partyModel->ensureCustomerKindSchema();
            $party = $db->fetchOne(
                "SELECT id, name, phone, credit_limit, customer_kind FROM parties WHERE id = ?",
                [$partyId]
            );
            if ($party) {
                $draft['party'] = $party;
                // Branch-scoped ledger (same as Party Master / credit check)
                $draft['party']['balance'] = $this->partyModel->currentNetBalance($partyId);
            }
        }

        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn($r) => (int)($r['item_id'] ?? 0),
            $draft['items'] ?? []
        ))));
        if (!empty($itemIds)) {
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $rows = $db->fetchAll("SELECT id, name FROM items WHERE id IN ({$ph})", $itemIds);
            $nameMap = [];
            foreach ($rows as $r) {
                $nameMap[(int)$r['id']] = $r['name'];
            }
            foreach ($draft['items'] as &$item) {
                $iid = (int)($item['item_id'] ?? 0);
                $item['item_name'] = $nameMap[$iid] ?? ('Item #' . $iid);
            }
            unset($item);
        }

        return $draft;
    }

    // Save new sale
    public function store(): void {
        Auth::authorize('sales', 'add');

        if (!$this->isPost()) {
            $this->redirect('?page=sales&action=create');
        }

        $postedNonce = isset($_POST['sale_form_nonce']) ? trim((string)$_POST['sale_form_nonce']) : '';
        $sessNonce   = $_SESSION['sale_form_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This sale form was already submitted or expired. Please check Sales list before trying again.');
            $this->redirect('?page=sales');
        }
        unset($_SESSION['sale_form_nonce']);

        $warehouseId = (int) Auth::warehouseId();
        if ($warehouseId <= 0) {
            $this->saveSaleDraftFromPost();
            $this->flash('error', 'Select a warehouse before creating a sale.');
            $this->redirect('?page=sales&action=create');
            return;
        }
        $postedWh = $this->inputInt('warehouse_id');
        if ($postedWh > 0 && $postedWh !== $warehouseId) {
            $this->saveSaleDraftFromPost();
            $this->flash('error', 'Warehouse mismatch. Sale must use the active branch.');
            $this->redirect('?page=sales&action=create');
            return;
        }

        $db = Database::getInstance();
        $headerDisc = 0.0;
        try {
            $rawItems = $_POST['items'] ?? [];
            $isSalesRestricted = in_array(Auth::role(), ['cashier', 'viewer'], true);
            // TEMP: free price when TEMP_ALLOW_SALESMAN_FREE_PRICE; otherwise clamp below catalog
            $priceFloorMode = ($isSalesRestricted && !self::TEMP_ALLOW_SALESMAN_FREE_PRICE) ? 'clamp' : 'none';

            // Credit limit + retail floor — party must be active customer visible on this branch
            $partyId = $this->inputInt('party_id');
            $this->partyModel->ensureCustomerKindSchema();
            $partyRow = $db->fetchOne(
                "SELECT id, type, is_active, customer_kind FROM parties WHERE id = ?",
                [$partyId]
            );
            if (!$partyRow || !(int) ($partyRow['is_active'] ?? 0)) {
                throw new Exception('Customer is inactive or does not exist.');
            }
            $ptype = (string) ($partyRow['type'] ?? '');
            if (!in_array($ptype, ['customer', 'both'], true)) {
                throw new Exception('Selected party is not a customer.');
            }
            if (!$this->partyModel->isVisibleInCurrentWarehouse($partyId)) {
                throw new Exception('Customer is not available on this branch.');
            }
            $customerKind = Party::normalizeCustomerKind($partyRow['customer_kind'] ?? null, $ptype);
            if (Party::isRetailCustomer($customerKind)) {
                $priceFloorMode = 'reject';
            }

            $norm = SaleValidator::normalizeItems(
                $db,
                $this->imeiModel,
                $rawItems,
                $warehouseId,
                $priceFloorMode,
                0.001,
                $isSalesRestricted,
                $customerKind
            );
            $items = $norm['items'];

            $headerDisc = SaleValidator::normalizeHeaderDiscount(
                $this->inputFloat('discount'),
                (float) $norm['subtotal'],
                (float) ($norm['catalog_floor'] ?? 0),
                $priceFloorMode
            );

            $newInvoiceTotal = (float) $norm['subtotal'] - (float) $headerDisc;

            SaleValidator::enforceCreditLimit($db, $partyId, $newInvoiceTotal);
        } catch (Throwable $e) {
            $this->saveSaleDraftFromPost();
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=sales&action=create');
            return;
        }

        $result = $this->saleModel->createFull([
            'party_id'       => $this->inputInt('party_id'),
            'warehouse_id'   => $warehouseId,
            'date'           => $this->input('date'),
            'discount'       => $headerDisc,
            'tax'            => 0,
            'paid_amount'    => 0, // Payment collected separately via Payments page
            'account_id'     => 0,
            'payment_method' => '',
            'notes'          => null,
            'items'          => $items,
        ]);

        if ($result['success']) {
            self::clearDashboardCache($warehouseId);
            $this->logActivity('create_sale', 'sales', $result['id'], "Invoice {$result['invoice_no']}");
            $this->flash('success', "Sale {$result['invoice_no']} saved successfully.");

            // Build WhatsApp payload from already-known data — no extra DB query
            $partyName  = '';
            $branchName = '';
            $partyIdVal = $this->inputInt('party_id');
            if ($partyIdVal) {
                $db = Database::getInstance();
                $partyName  = $db->fetchOne("SELECT name FROM parties WHERE id=?", [$partyIdVal])['name'] ?? '—';
            }
            foreach (self::getWarehouses() as $wh) {
                if ((int)$wh['id'] === $warehouseId) { $branchName = $wh['name']; break; }
            }
            WhatsApp::sale([
                'invoice_no' => $result['invoice_no'],
                'party'      => $partyName ?: '—',
                'branch'     => $branchName ?: '—',
                'total'      => number_format($result['grand_total'] ?? 0, 3),
                'paid'       => number_format(0, 3),
                'currency'   => APP_CURRENCY,
            ]);
            $printMode = $this->input('print_mode');
            if ($printMode === '1') {
                $tpl = Auth::printTemplate();
                if ($tpl === 'thermal') {
                    $this->redirect('?page=sales&action=thermalPrint&id=' . $result['id'] . '&thermal=1&autoprint=1');
                }
                $this->redirect('?page=sales&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            if ($printMode === '2') {
                $this->redirect('?page=sales&action=thermalPrint&id=' . $result['id'] . '&thermal=1&autoprint=1');
            }
            $this->redirect('?page=sales&action=detail&id=' . $result['id']);
        } else {
            $this->saveSaleDraftFromPost();
            $this->flash('error', 'Failed to save sale: ' . $result['error']);
            $this->redirect('?page=sales&action=create');
        }
    }

    // View single sale
    public function detail(): void {
        Auth::authorize('sales', 'view');

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);

        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->assertSaleWarehouseAccess($sale)) {
            return;
        }

        // Fix mis-allocated receipts and stale paid/balance/status badges.
        if ($this->saleModel->refreshInvoicePaymentState($id)) {
            $sale = $this->saleModel->findFull($id);
            if (!$sale || !$this->assertSaleWarehouseAccess($sale)) {
                return;
            }
        }

        $partyBalance = $this->partyModel->findWithBalance((int)$sale['party_id']);
        $sale['party_total_balance'] = (float)($partyBalance['net_balance'] ?? 0);
        $sale['prev_balance']        = (float)$sale['party_total_balance'] - (float)$sale['balance'];

        $db        = Database::getInstance();
        $accounts  = self::getAccounts();
        $pageTitle = 'Sale: ' . $sale['invoice_no'];
        $page      = 'sales';

        // Linked sale returns — only this customer's credit notes that reversed THIS
        // invoice. After phones go back to stock they are company property; a later
        // sale/return (another party) must not appear on the original invoice.
        $linkedReturns = [];
        if (Auth::can('returns', 'view') || Auth::can('sales', 'view')) {
            require_once __DIR__ . '/../models/Return.php';
            $linkedReturns = (new SaleReturn())->getLinkedToSale(
                $id,
                (int) ($sale['warehouse_id'] ?? Auth::warehouseId() ?: 0)
            );
        }

        $saleEditReq = $this->loadSaleEditRequestState($id);
        $saleEditPending = $saleEditReq['pending'];
        $saleEditUnlock  = $saleEditReq['unlock'];
        $saleEditLatest  = $saleEditReq['latest'];
        $canSaleEditNow  = $this->canEditSaleInvoice($sale);

        $cancelAudit = [];
        if (($sale['status'] ?? '') === 'cancelled') {
            $cancelAudit = $db->fetchAll(
                "SELECT al.created_at, al.user_id, al.description, al.ip_address, u.name AS user_name
                 FROM activity_log al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE al.action = 'cancel_sale' AND al.module = 'sales' AND al.ref_id = ?
                 ORDER BY al.created_at DESC",
                [$id]
            );
        }

        ob_start();
        include __DIR__ . '/../views/sales/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** Download a portable JSON of this sale (items, prices, IMEIs) for another shop to import as a purchase. */
    public function exportInvoice(): void {
        Auth::authorize('sales', 'view');

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);
        if (!$sale || !$this->assertSaleWarehouseAccess($sale)) {
            return;
        }

        try {
            $payload = SaleInvoiceFile::buildFromSale($sale, Database::getInstance());
            $json    = SaleInvoiceFile::encode($payload);
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }

        $filename = SaleInvoiceFile::filename($payload);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $json;
        exit;
    }

    // Print/PDF invoice — thermal when `thermal` appears in query (same rule as print.php / payment receipts).
    public function print(): void {
        $this->renderInvoicePrint(isset($_GET['thermal']));
    }

    // Dedicated thermal print route (does not rely on query flag parsing)
    public function thermalPrint(): void {
        $this->renderInvoicePrint(true);
    }

    private function renderInvoicePrint(bool $isThermal): void {
        Auth::authorize('sales', 'view');

        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findForPrint($id);

        if (!$sale) die('Invoice not found.');
        if (!$this->assertSaleWarehouseAccess($sale)) {
            return;
        }

        $settings = self::getSettings();
        $partyBalance = $this->partyModel->findWithBalance((int)$sale['party_id']);
        $currentBalance = (float)($partyBalance['net_balance'] ?? 0);
        // Previous balance = current balance minus this invoice's unpaid portion
        $sale['prev_balance']  = $currentBalance - (float)$sale['balance'];
        $sale['total_balance'] = $currentBalance;

        // Explicit flag for print view (some hosts / includes: avoid relying only on local $isThermal).
        $salePrintThermal = (bool) $isThermal;

        include __DIR__ . '/../views/sales/print.php';
    }

    // Add payment to existing sale (AJAX)
    public function addPayment(): void {
        Auth::authorize('sales', 'edit');
        // Same cash path as Receive Payment — require payments add permission.
        if (!Auth::can('payments', 'add')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Payment permission required.']);
            return;
        }
        header('Content-Type: application/json');
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST required.']);
            return;
        }

        $id   = $this->inputInt('sale_id');
        $sale = $this->saleModel->find($id);
        if (!$this->assertSaleWarehouseAccess($sale ?: null, true)) {
            return;
        }

        // Lock paid invoices
        if ($sale['status'] === 'paid') {
            echo json_encode(['success' => false, 'error' => 'This invoice is already fully paid.']);
            return;
        }
        $amount = $this->inputFloat('amount');
        $accId  = $this->inputInt('account_id');
        $method = $this->input('payment_method');
        $date   = $this->input('date') ?: date('Y-m-d');
        $notes  = $this->input('notes');

        if ($amount <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
            return;
        }
        if ($accId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Please select a valid account.']);
            return;
        }

        $ok = $this->saleModel->addPayment($id, $amount, $accId, $method, $date, $notes);
        if ($ok === true) {
            self::clearDashboardCache((int) ($sale['warehouse_id'] ?? 0));
            echo json_encode(['success' => true]);
            return;
        } else {
            echo json_encode(['success' => false, 'error' => is_string($ok) ? $ok : 'Failed to add payment.']);
            return;
        }
    }

    /**
     * One-time form nonce — prevents double-submit on admin edit / add-item / scan paths.
     */
    private function consumeSaleActionNonce(string $bucket, int $key, string $postField, string $failUrl): bool {
        if (!isset($_SESSION[$bucket]) || !is_array($_SESSION[$bucket])) {
            $_SESSION[$bucket] = [];
        }
        $posted = isset($_POST[$postField]) ? trim((string) $_POST[$postField]) : '';
        $sess   = (string) ($_SESSION[$bucket][$key] ?? '');
        unset($_SESSION[$bucket][$key]);
        if ($sess === '' || $posted === '' || !hash_equals($sess, $posted)) {
            $this->flash('warning', 'This form was already submitted or expired. Please try again.');
            $this->redirect($failUrl);
            return false;
        }
        return true;
    }

    private function issueSaleActionNonce(string $bucket, int $key): string {
        if (!isset($_SESSION[$bucket]) || !is_array($_SESSION[$bucket])) {
            $_SESSION[$bucket] = [];
        }
        $nonce = bin2hex(random_bytes(16));
        $_SESSION[$bucket][$key] = $nonce;
        return $nonce;
    }

    // Edit sale form (admin, or cashier with an approved unlock)
    public function edit(): void {
        $id       = $this->inputInt('id', 0, 'get');
        $editSale = $this->saleModel->findFull($id);

        if (!$editSale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }

        if (!$this->requireSaleEditAccess($editSale)) {
            return;
        }

        if ($editSale['status'] === 'cancelled') {
            $this->flash('error', 'Cancelled invoices cannot be edited.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }

        $saleEditNonce = $this->issueSaleActionNonce('sale_edit_nonce', $id);
        $saleEditDraft = $this->peekSaleEditDraft($id);
        $saleEditLockParty = !Auth::isAdmin();
        $saleEditUnlock = $this->isCashierSaleEditor()
            ? SaleEditRequest::findActiveUnlock(Database::getInstance(), $id, (int) Auth::id())
            : null;
        $allowSalesmanFreePrice = self::TEMP_ALLOW_SALESMAN_FREE_PRICE;
        $partyCreditLimit = 0.0;
        $partyOutstanding = 0.0;
        $partyCustomerKind = Party::CUSTOMER_KIND_WHOLESALE;
        $invoicePartyId = (int) ($editSale['party_id'] ?? 0);
        $partyId = $invoicePartyId;
        $draftPartyId = is_array($saleEditDraft) ? (int) ($saleEditDraft['party_id'] ?? 0) : 0;
        if (!Auth::isAdmin()) {
            $draftPartyId = 0;
        }
        if ($draftPartyId > 0) {
            $partyId = $draftPartyId;
        }
        if ($partyId > 0) {
            $db = Database::getInstance();
            $this->partyModel->ensureCustomerKindSchema();
            $prow = $db->fetchOne('SELECT name, phone, credit_limit, customer_kind FROM parties WHERE id = ?', [$partyId]);
            $partyCreditLimit = (float) ($prow['credit_limit'] ?? 0);
            $partyCustomerKind = Party::normalizeCustomerKind($prow['customer_kind'] ?? null, 'customer');
            $partyOutstanding = SaleValidator::partyOutstanding($db, $partyId);
            if ($draftPartyId > 0 && $draftPartyId !== $invoicePartyId && $prow) {
                $editSale['party_id']    = $draftPartyId;
                $editSale['party_name']  = (string) ($prow['name'] ?? $editSale['party_name']);
                $editSale['party_phone'] = (string) ($prow['phone'] ?? '');
            }
        }
        $pageTitle = 'Edit: ' . $editSale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update sale (admin, or cashier with an approved unlock)
    public function update(): void {
        if (!$this->isPost()) {
            $this->redirect('?page=sales');
            return;
        }

        $id   = $this->inputInt('id', 0, 'get') ?: $this->inputInt('id');
        $sale = $this->saleModel->find($id);

        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }

        if (!$this->requireSaleEditAccess($sale)) {
            return;
        }

        $cashierEdit = $this->isCashierSaleEditor();
        $priceFloorMode = ($cashierEdit && !self::TEMP_ALLOW_SALESMAN_FREE_PRICE) ? 'clamp' : 'none';

        if ($sale['status'] === 'cancelled') {
            $this->flash('error', 'Cancelled invoices cannot be edited.');
            $this->redirect('?page=sales');
            return;
        }

        if (!$this->consumeSaleActionNonce('sale_edit_nonce', $id, 'sale_edit_nonce', "?page=sales&action=edit&id={$id}")) {
            return;
        }

        $db = Database::getInstance();

        $newDate     = $this->input('date') ?: $sale['date'];
        $newDiscount = $this->inputFloat('discount');
        $newNotes    = $this->input('notes');
        $warehouseId = (int)$sale['warehouse_id'];
        $oldPartyId  = (int) ($sale['party_id'] ?? 0);
        $postedPartyId = $cashierEdit ? $oldPartyId : $this->inputInt('party_id');
        $partyChanged = false;
        $newParty = null;
        $this->partyModel->ensureCustomerKindSchema();
        $kindPartyId = $postedPartyId > 0 ? $postedPartyId : $oldPartyId;
        $kindRow = $kindPartyId > 0
            ? $db->fetchOne('SELECT customer_kind, type FROM parties WHERE id = ?', [$kindPartyId])
            : null;
        $updateCustomerKind = Party::normalizeCustomerKind(
            $kindRow['customer_kind'] ?? null,
            (string) ($kindRow['type'] ?? 'customer')
        );

        // ── Handle item changes (qty/price) ──
        $rawItems    = $_POST['items'] ?? [];
        $newSubtotal = 0;

        // Schema ensure must run outside the sale txn (DDL = MySQL implicit commit).
        require_once __DIR__ . '/../services/PackingVendorBillService.php';
        PackingVendorBillService::ensureSchema($db);

        $db->beginTransaction();
        try {
            $locked = $db->fetchOne('SELECT id FROM sales WHERE id = ? FOR UPDATE', [$id]);
            if (!$locked) {
                throw new Exception('Sale not found.');
            }
            if (!empty($rawItems)) {
                foreach ($rawItems as $saleItemId => $row) {
                    $saleItemId = (int)$saleItemId;

                    // Get old item data first
                    $oldItem = $db->fetchOne(
                        "SELECT si.item_id, si.quantity, si.unit_price, si.discount, si.total,
                                i.has_imei, i.sale_price, i.name AS item_name
                         FROM sale_items si 
                         JOIN items i ON i.id = si.item_id
                         WHERE si.id = ? AND si.sale_id = ?",
                        [$saleItemId, $id]
                    );
                    if (!$oldItem) continue;

                    // Handle deletion
                    if (!empty($row['deleted'])) {
                        $linkedImeiIds = $db->fetchAll(
                            'SELECT sii.imei_id, ir.status, ir.imei
                             FROM sale_item_imei sii
                             JOIN imei_records ir ON ir.id = sii.imei_id
                             WHERE sii.sale_item_id = ?',
                            [$saleItemId]
                        );
                        foreach ($linkedImeiIds as $lim) {
                            if (($lim['status'] ?? '') === 'dumped') {
                                throw new Exception(
                                    'Cannot remove this line: IMEI ' . ($lim['imei'] ?? '') . ' was dumped. Void the dump credit first.'
                                );
                            }
                        }
                        foreach ($linkedImeiIds as $lim) {
                            $db->execute(
                                "UPDATE imei_records SET status = 'in_stock', sale_id = NULL WHERE id = ? AND status = 'sold'",
                                [(int) $lim['imei_id']]
                            );
                        }
                        // Restore full qty back to stock
                        $db->execute(
                            "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                            [(int)$oldItem['quantity'], $oldItem['item_id'], $warehouseId]
                        );
                        $db->execute("DELETE FROM sale_items WHERE id = ?", [$saleItemId]);
                        continue;
                    }

                    $newQty   = max(1, (int)($row['quantity'] ?? 1));
                    $newPrice = (float)($row['unit_price'] ?? 0);
                    if ($newPrice < 0) {
                        throw new Exception('Item price cannot be negative.');
                    }
                    if ($priceFloorMode === 'clamp') {
                        $newPrice = SaleValidator::applyCashierUnitFloor(
                            $newPrice,
                            (float) ($oldItem['sale_price'] ?? 0),
                            $updateCustomerKind
                        );
                    }
                    $lineDiscount = (float) ($oldItem['discount'] ?? 0);
                    SaleValidator::assertRetailUnitPrice(
                        (string) ($oldItem['item_name'] ?? 'Item'),
                        $newPrice,
                        $lineDiscount,
                        $newQty,
                        (float) ($oldItem['sale_price'] ?? 0),
                        $updateCustomerKind
                    );
                    $lineGross    = $newQty * $newPrice;
                    if ($lineDiscount > $lineGross + 0.001) {
                        throw new Exception('Item discount cannot exceed the line amount.');
                    }
                    $newTotal = round($lineGross - $lineDiscount, 3);
                    $oldQty   = (int)$oldItem['quantity'];
                    $qtyDiff  = $oldQty - $newQty;
                    
                    if ($qtyDiff != 0 && !empty($oldItem['has_imei'])) {
                        throw new Exception("Cannot change quantity of IMEI-tracked items during edit. Please delete the line and re-add it to properly scan or release serials.");
                    }

                    $db->execute(
                        "UPDATE sale_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $saleItemId]
                    );

                    if ($qtyDiff < 0) { // Increasing quantity
                        $additionalNeeded = abs($qtyDiff);
                        $affected = $db->execute(
                            "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [$additionalNeeded, $oldItem['item_id'], $warehouseId, $additionalNeeded]
                        );
                        if ($affected === 0) {
                            $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$oldItem['item_id']]);
                            throw new Exception("Insufficient stock for \"{$itemName['name']}\" to increase quantity by {$additionalNeeded}.");
                        }
                    } elseif ($qtyDiff > 0) { // Decreasing quantity
                        $db->execute(
                            "UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?",
                            [$qtyDiff, $oldItem['item_id'], $warehouseId]
                        );
                    }

                    $newSubtotal += $newTotal;
                }
            } else {
                $newSubtotal = (float)$sale['subtotal'];
            }

            // ── Handle new items added during edit (incl. IMEI paste/scan rows) ─
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId   = (int)($row['item_id'] ?? 0);
                $newQty   = max(1, (int)($row['quantity'] ?? 1));
                $newPrice = (float)($row['unit_price'] ?? 0);
                $newTotal = round($newQty * $newPrice, 3);
                if (!$itemId || $newPrice <= 0) continue;

                $itemMeta = $db->fetchOne(
                    "SELECT name, has_imei, purchase_price, sale_price FROM items WHERE id = ?",
                    [$itemId]
                );
                if (!$itemMeta) {
                    throw new Exception("Invalid item #{$itemId}.");
                }
                if ($priceFloorMode === 'clamp') {
                    $newPrice = SaleValidator::applyCashierUnitFloor(
                        $newPrice,
                        (float) ($itemMeta['sale_price'] ?? 0),
                        $updateCustomerKind
                    );
                    $newTotal = round($newQty * $newPrice, 3);
                }
                SaleValidator::assertRetailUnitPrice(
                    (string) ($itemMeta['name'] ?? 'Item'),
                    $newPrice,
                    0.0,
                    $newQty,
                    (float) ($itemMeta['sale_price'] ?? 0),
                    $updateCustomerKind
                );

                $imeis = [];
                if (!empty($row['imeis'])) {
                    if (is_array($row['imeis'])) {
                        $imeis = array_values(array_unique(array_filter(array_map(
                            static fn($v) => trim((string) $v),
                            $row['imeis']
                        ))));
                    } else {
                        $imeis = array_values(array_unique(array_filter(array_map(
                            'trim',
                            preg_split('/[\r\n,;]+/', (string) $row['imeis']) ?: []
                        ))));
                    }
                }

                // Strict: every IMEI-tracked unit must be scanned (no imei_optional bypass)
                if (!empty($itemMeta['has_imei']) && count($imeis) !== $newQty) {
                    throw new Exception(
                        "Item \"{$itemMeta['name']}\": must scan {$newQty} IMEI(s) before selling (currently " . count($imeis) . "). "
                        . 'Selling without serials causes stock / IMEI mismatch.'
                    );
                }
                if (!empty($imeis)) {
                    $imeiErrors = $this->imeiModel->validateList($imeis, $itemId, $warehouseId);
                    if (!empty($imeiErrors)) {
                        throw new Exception(implode(' | ', $imeiErrors));
                    }
                }

                $costPrice = (float) ($itemMeta['purchase_price'] ?? 0);
                $saleItemId = $db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, cost_price, discount, tax, total)
                     VALUES (?,?,?,?,?,0,0,?)",
                    [$id, $itemId, $newQty, $newPrice, $costPrice, $newTotal]
                );

                // Deduct stock (check affected rows to prevent silent failure)
                $affected = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$newQty, $itemId, $warehouseId, $newQty]
                );
                if ($affected === 0) {
                    $stock = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $warehouseId]);
                    throw new Exception(
                        "Insufficient stock for \"{$itemMeta['name']}\". Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$newQty}."
                    );
                }

                foreach ($imeis as $imei) {
                    $rec = $db->fetchOne(
                        "SELECT id
                         FROM imei_records
                         WHERE imei = ?
                         ORDER BY
                            CASE
                                WHEN warehouse_id = ? AND status IN ('in_stock','returned') THEN 0
                                WHEN status IN ('in_stock','returned') THEN 1
                                WHEN warehouse_id = ? THEN 2
                                ELSE 3
                            END,
                            id DESC
                         LIMIT 1",
                        [$imei, $warehouseId, $warehouseId]
                    );
                    if (!$rec) {
                        throw new Exception(
                            "IMEI {$imei} is not in stock. Receive it via purchase (or return) before selling."
                        );
                    }
                    $imeiId = (int) $rec['id'];
                    $aff = $db->execute(
                        "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                        [$id, $warehouseId, $imeiId]
                    );
                    if ($aff === 0) {
                        throw new Exception("IMEI {$imei} not available for sale.");
                    }
                    $db->insert(
                        "INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)",
                        [$saleItemId, $imeiId]
                    );
                }

                $newSubtotal += $newTotal;
            }

            // Strict gate: no IMEI-tracked line may remain without a full serial list
            $incomplete = $db->fetchAll(
                "SELECT si.id, i.name, si.quantity,
                        COALESCE(COUNT(sii.imei_id), 0) AS imei_count
                 FROM sale_items si
                 JOIN items i ON i.id = si.item_id
                 LEFT JOIN sale_item_imei sii ON sii.sale_item_id = si.id
                 WHERE si.sale_id = ? AND i.has_imei = 1
                 GROUP BY si.id, i.name, si.quantity
                 HAVING imei_count <> si.quantity",
                [$id]
            );
            if (!empty($incomplete)) {
                $first = $incomplete[0];
                throw new Exception(
                    "Item \"{$first['name']}\": must scan " . (int) $first['quantity']
                    . ' IMEI(s) before saving (currently ' . (int) $first['imei_count'] . '). '
                    . 'Use Scan IMEIs on the line, then save. Selling without serials causes stock / IMEI mismatch.'
                );
            }

            if ($cashierEdit) {
                SaleValidator::assertMaxSaleQtyForSale($db, $id);
                $catalogFloor = SaleValidator::catalogFloorForSale($db, $id, $updateCustomerKind);
                $newDiscount = SaleValidator::normalizeHeaderDiscount(
                    $newDiscount,
                    $newSubtotal,
                    $catalogFloor,
                    $priceFloorMode
                );
            }

            // Recalculate grand_total and balance (invoice AR = grand - paid; returns are party credits)
            if ($newDiscount < 0 || $newDiscount > $newSubtotal + 0.001) {
                throw new Exception('Invoice discount must be between zero and the item subtotal.');
            }
            $paidAmount    = (float)$sale['paid_amount'];
            $newGrandTotal = $newSubtotal - $newDiscount;
            $newBalance = max(0, $newGrandTotal - $paidAmount);

            $oldGrand = (float) $sale['grand_total'];
            $deltaExposure = $newGrandTotal - $oldGrand;

            if ($postedPartyId <= 0) {
                throw new Exception('Please select a customer.');
            }
            $newParty = $db->fetchOne(
                "SELECT id, name, type, is_active, warehouse_id
                 FROM parties
                 WHERE id = ?
                   AND is_active = 1
                   AND type IN ('customer', 'both')
                   AND (warehouse_id IS NULL OR warehouse_id = ?)",
                [$postedPartyId, $warehouseId]
            );
            if (!$newParty) {
                throw new Exception('Customer is inactive, not allowed for sales, or belongs to another branch.');
            }
            $partyChanged = $postedPartyId !== $oldPartyId;
            if ($partyChanged) {
                // New party does not yet have this invoice; exposure is the unpaid remainder.
                SaleValidator::enforceCreditLimit($db, $postedPartyId, $newBalance);
            } elseif ($deltaExposure > 0.001) {
                SaleValidator::enforceCreditLimit($db, $oldPartyId, $deltaExposure);
            }

            // Determine new status
            if ($newBalance < 0.001) {
                $newStatus = 'paid';
                $newBalance = 0;
            } elseif ($paidAmount > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = $sale['status'] === 'paid' ? 'confirmed' : $sale['status'];
            }

            $db->execute(
                "UPDATE sales SET party_id=?, date=?, subtotal=?, discount=?, grand_total=?, balance=?, status=?, notes=? WHERE id=? AND warehouse_id=?",
                [$postedPartyId, $newDate, $newSubtotal, $newDiscount, $newGrandTotal, $newBalance, $newStatus, $newNotes ?: null, $id, (int) ($sale['warehouse_id'] ?? 0)]
            );

            if ($partyChanged) {
                $this->reassignSalePartyLinks($db, $id, $postedPartyId, $warehouseId);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollbackQuiet();
            $this->saveSaleEditDraftFromPost($id);
            $this->flash('error', 'Error updating: ' . $e->getMessage());
            $this->redirect("?page=sales&action=edit&id={$id}");
            return;
        }

        $this->clearSaleEditDraft($id);

        if ($cashierEdit) {
            try {
                SaleEditRequest::markUsed($db, $id, (int) Auth::id());
            } catch (Throwable $e) {
                error_log('[SalesController::update] markUsed failed: ' . $e->getMessage());
            }
        }

        try {
            $logDetail = "Edited {$sale['invoice_no']}: items/prices updated";
            if (!empty($partyChanged) && $postedPartyId !== $oldPartyId) {
                $logDetail .= '; customer #' . $oldPartyId . ' → #' . $postedPartyId
                    . ' ' . (string) ($newParty['name'] ?? '');
            }
            $this->logActivity('edit_sale', 'sales', $id, $logDetail);
        } catch (Throwable $e) {
            error_log('[SalesController::update] logActivity failed: ' . $e->getMessage());
        }
        self::clearDashboardCache((int) ($sale['warehouse_id'] ?? 0));
        if (!empty($partyChanged)) {
            $this->flash('success', "Invoice {$sale['invoice_no']} updated. Customer is now " . (string) ($newParty['name'] ?? 'selected party') . '.');
        } else {
            $this->flash('success', "Invoice {$sale['invoice_no']} updated.");
        }

        $printMode = $this->input('print_mode');
        if ($printMode === '2') {
            $this->redirect("?page=sales&action=thermalPrint&id={$id}&thermal=1");
            return;
        }

        if ($printMode === '1' || $this->input('print_after_save') === '1') {
            $tpl = Auth::printTemplate();
            if ($tpl === 'thermal') {
                $this->redirect("?page=sales&action=thermalPrint&id={$id}&thermal=1");
                return;
            }
            $this->redirect("?page=sales&action=print&id={$id}");
            return;
        }

        $this->redirect("?page=sales&action=detail&id={$id}");
    }

    /**
     * Move invoice-linked ledger rows when the sale customer changes.
     * Sale returns stay on their own party (cross-party returns are allowed).
     */
    private function reassignSalePartyLinks(Database $db, int $saleId, int $newPartyId, int $warehouseId): void {
        $db->execute(
            "UPDATE payments SET party_id = ? WHERE ref_type = 'sale' AND ref_id = ?",
            [$newPartyId, $saleId]
        );

        $discRows = $db->fetchAll(
            'SELECT id, payment_id FROM customer_discounts WHERE sale_id = ?',
            [$saleId]
        );
        if (!empty($discRows)) {
            $db->execute(
                'UPDATE customer_discounts SET party_id = ? WHERE sale_id = ?',
                [$newPartyId, $saleId]
            );
            $payIds = [];
            foreach ($discRows as $dr) {
                $pid = (int) ($dr['payment_id'] ?? 0);
                if ($pid > 0) {
                    $payIds[] = $pid;
                }
            }
            if ($payIds !== []) {
                $ph = implode(',', array_fill(0, count($payIds), '?'));
                $db->execute(
                    "UPDATE payments SET party_id = ? WHERE id IN ({$ph}) AND ref_type = 'discount'",
                    array_merge([$newPartyId], $payIds)
                );
            }
        }

        $db->execute(
            'UPDATE warranty_replacements SET party_id = ? WHERE sale_id = ? AND warehouse_id = ?',
            [$newPartyId, $saleId, $warehouseId]
        );
    }

    /**
     * Show scan page to add IMEIs to an existing sale line item.
     * Admin, or cashier with an approved unlock.
     */
    public function scanItemImeis(): void {
        $saleId     = $this->inputInt('id', 0, 'get');
        $saleItemId = $this->inputInt('sale_item_id', 0, 'get');

        Item::ensureSerialKindColumn();
        $db   = Database::getInstance();
        $sale = $this->saleModel->find($saleId);
        if (!$sale || $sale['status'] === 'cancelled') {
            $this->flash('error', 'Sale not available.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->requireSaleEditAccess($sale)) {
            return;
        }

        $line = $db->fetchOne(
            "SELECT si.*, i.name as item_name, i.sku, i.has_imei,
                    COALESCE(i.serial_kind, 'phone') AS serial_kind,
                    (SELECT COUNT(*) FROM sale_item_imei sii WHERE sii.sale_item_id = si.id) as imei_count
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             WHERE si.id = ? AND si.sale_id = ?",
            [$saleItemId, $saleId]
        );
        if (!$line) {
            $this->flash('error', 'Item line not found.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
            return;
        }
        if (!$line['has_imei']) {
            $this->flash('error', 'This item is not IMEI-tracked.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
            return;
        }

        $existingImeis = $db->fetchAll(
            "SELECT ir.imei FROM sale_item_imei sii
             JOIN imei_records ir ON ir.id = sii.imei_id
             WHERE sii.sale_item_id = ?
             ORDER BY ir.imei",
            [$saleItemId]
        );

        $saleScanNonce = $this->issueSaleActionNonce('sale_scan_imei_nonce', $saleItemId);
        $pageTitle = 'Scan IMEIs — ' . $sale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/scan_imeis.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Process scanned IMEIs for an existing sale_item.
     */
    public function scanItemImeisStore(): void {
        if (!$this->isPost()) { $this->redirect('?page=sales'); return; }

        $saleId     = $this->inputInt('id');
        $saleItemId = $this->inputInt('sale_item_id');

        if (!$this->consumeSaleActionNonce(
            'sale_scan_imei_nonce',
            $saleItemId,
            'sale_scan_nonce',
            "?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}"
        )) {
            return;
        }

        $db = Database::getInstance();
        $sale = $this->saleModel->find($saleId);
        if (!$sale || $sale['status'] === 'cancelled') { $this->flash('error', 'Sale not available.'); $this->redirect('?page=sales'); return; }
        if (!$this->requireSaleEditAccess($sale)) {
            return;
        }

        $line = $db->fetchOne(
            "SELECT si.*, i.has_imei FROM sale_items si JOIN items i ON i.id = si.item_id WHERE si.id = ? AND si.sale_id = ?",
            [$saleItemId, $saleId]
        );
        if (!$line || !$line['has_imei']) { $this->flash('error', 'Invalid item line.'); $this->redirect("?page=sales&action=edit&id={$saleId}"); return; }

        $rawImeis = $this->input('imeis');
        $imeis    = array_values(array_unique(array_filter(array_map('trim', explode("\n", $rawImeis)))));

        $existingCount = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM sale_item_imei WHERE sale_item_id = ?",
            [$saleItemId]
        )['c'] ?? 0);
        $remaining = (int)$line['quantity'] - $existingCount;

        if (count($imeis) > $remaining) {
            $this->flash('error', "Only {$remaining} more IMEI(s) needed for this line. You scanned " . count($imeis) . ".");
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }
        if (empty($imeis)) {
            $this->flash('error', 'No IMEIs scanned.');
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }

        // Validate IMEIs against item
        $errors = $this->imeiModel->validateList($imeis, (int)$line['item_id'], Auth::warehouseId());
        if (!empty($errors)) {
            $this->flash('error', implode(' | ', $errors));
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
            return;
        }

        $whId = (int)$sale['warehouse_id'];

        $db->beginTransaction();
        try {
            foreach ($imeis as $imei) {
                $rec = $db->fetchOne(
                    "SELECT id
                     FROM imei_records
                     WHERE imei = ?
                     ORDER BY
                        CASE
                            WHEN warehouse_id = ? AND status IN ('in_stock','returned') THEN 0
                            WHEN status IN ('in_stock','returned') THEN 1
                            WHEN warehouse_id = ? THEN 2
                            ELSE 3
                        END,
                        id DESC
                     LIMIT 1",
                    [$imei, $whId, $whId]
                );
                if (!$rec) {
                    throw new Exception(
                        "IMEI {$imei} is not in stock. Receive it via purchase (or return) before linking."
                    );
                }
                $imeiId = (int)$rec['id'];
                $aff = $db->execute(
                    "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                    [$saleId, $whId, $imeiId]
                );
                if ($aff === 0) throw new Exception("IMEI {$imei} not available for sale.");
                $db->insert("INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)", [$saleItemId, $imeiId]);
            }

            $db->commit();
            $this->logActivity('retro_scan_imeis', 'sale_items', $saleItemId,
                "Scanned " . count($imeis) . " IMEIs for sale_item #{$saleItemId} of {$sale['invoice_no']}");
            $this->flash('success', count($imeis) . ' IMEI(s) linked to this line.');
            $this->redirect("?page=sales&action=edit&id={$saleId}");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Scan failed: ' . $e->getMessage());
            $this->redirect("?page=sales&action=scanItemImeis&id={$saleId}&sale_item_id={$saleItemId}");
        }
    }

    /**
     * Show "Add item" page for an existing sale (admin only).
     * Mini create-style form with IMEI scan support.
     */
    public function addItem(): void {
        $id   = $this->inputInt('id', 0, 'get');
        $sale = $this->saleModel->findFull($id);

        if (!$sale)                              { $this->flash('error', 'Sale not found.');                $this->redirect('?page=sales');                                     return; }
        if (!$this->requireSaleEditAccess($sale)) { return; }
        if ($sale['status'] === 'cancelled')     { $this->flash('error', 'Cancelled invoices cannot be edited.'); $this->redirect("?page=sales&action=detail&id={$id}");          return; }

        $saleAddItemNonce = $this->issueSaleActionNonce('sale_add_item_nonce', $id);
        $partyCreditLimit = 0.0;
        $partyOutstanding = 0.0;
        $partyCustomerKind = Party::CUSTOMER_KIND_WHOLESALE;
        $partyId = (int) ($sale['party_id'] ?? 0);
        if ($partyId > 0) {
            $db = Database::getInstance();
            $this->partyModel->ensureCustomerKindSchema();
            $prow = $db->fetchOne('SELECT credit_limit, customer_kind FROM parties WHERE id = ?', [$partyId]);
            $partyCreditLimit = (float) ($prow['credit_limit'] ?? 0);
            $partyCustomerKind = Party::normalizeCustomerKind($prow['customer_kind'] ?? null, 'customer');
            $partyOutstanding = SaleValidator::partyOutstanding($db, $partyId);
        }
        $pageTitle = 'Add Item to ' . $sale['invoice_no'];
        $page      = 'sales';

        ob_start();
        include __DIR__ . '/../views/sales/add_item.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Process new item additions (admin or cashier with an approved unlock).
     */
    public function addItemStore(): void {
        if (!$this->isPost()) { $this->redirect('?page=sales'); return; }

        $id   = $this->inputInt('id');
        if (!$this->consumeSaleActionNonce('sale_add_item_nonce', $id, 'sale_add_item_nonce', "?page=sales&action=addItem&id={$id}")) {
            return;
        }

        $sale = $this->saleModel->find($id);
        if (!$sale || $sale['status'] === 'cancelled') {
            $this->flash('error', 'Invalid sale.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->requireSaleEditAccess($sale)) {
            return;
        }

        $cashierEdit = $this->isCashierSaleEditor();
        $priceFloorMode = ($cashierEdit && !self::TEMP_ALLOW_SALESMAN_FREE_PRICE) ? 'clamp' : 'none';

        $db   = Database::getInstance();
        $whId = (int)$sale['warehouse_id'];
        $this->partyModel->ensureCustomerKindSchema();
        $saleParty = $db->fetchOne('SELECT customer_kind, type FROM parties WHERE id = ?', [(int) $sale['party_id']]);
        $addKind = Party::normalizeCustomerKind(
            $saleParty['customer_kind'] ?? null,
            (string) ($saleParty['type'] ?? 'customer')
        );

        $rawItems = $_POST['items'] ?? [];
        $items    = [];

        // Validate
        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) continue;
            $itemId = (int)$row['item_id'];
            $qty    = (int)$row['quantity'];
            $price  = (float)($row['unit_price'] ?? 0);

            if ($qty <= 0)   { $this->flash('error', 'Quantity must be greater than zero.'); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }
            if ($price <= 0) { $this->flash('error', 'Price must be greater than zero.');    $this->redirect("?page=sales&action=addItem&id={$id}"); return; }

            $imeis = [];
            if (!empty($row['imeis'])) {
                $imeis = array_values(array_unique(array_filter(array_map('trim', explode("\n", $row['imeis'])))));
            }

            $itemInfo = $db->fetchOne(
                "SELECT name, has_imei, sale_price FROM items WHERE id = ?",
                [$itemId]
            );
            if (!$itemInfo) { $this->flash('error', 'Invalid item.'); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }

            if ($priceFloorMode === 'clamp') {
                $price = SaleValidator::applyCashierUnitFloor(
                    $price,
                    (float) ($itemInfo['sale_price'] ?? 0),
                    $addKind
                );
            }

            try {
                SaleValidator::assertRetailUnitPrice(
                    (string) ($itemInfo['name'] ?? 'Item'),
                    $price,
                    0.0,
                    $qty,
                    (float) ($itemInfo['sale_price'] ?? 0),
                    $addKind
                );
            } catch (Exception $e) {
                $this->flash('error', $e->getMessage());
                $this->redirect("?page=sales&action=addItem&id={$id}");
                return;
            }

            // Strict: every IMEI-tracked unit must be scanned (no imei_optional bypass)
            if (!empty($itemInfo['has_imei']) && count($imeis) !== $qty) {
                $this->flash(
                    'error',
                    "Item \"{$itemInfo['name']}\": must scan {$qty} IMEI(s) before selling (currently " . count($imeis) . "). "
                    . 'Selling without serials causes stock / IMEI mismatch.'
                );
                $this->redirect("?page=sales&action=addItem&id={$id}");
                return;
            }

            if (!empty($imeis)) {
                $errors = $this->imeiModel->validateList($imeis, $itemId, Auth::warehouseId());
                if (!empty($errors)) { $this->flash('error', implode(' | ', $errors)); $this->redirect("?page=sales&action=addItem&id={$id}"); return; }
            }

            $items[] = compact('itemId', 'qty', 'price', 'imeis');
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one item.');
            $this->redirect("?page=sales&action=addItem&id={$id}");
            return;
        }

        $db->beginTransaction();
        try {
            $locked = $db->fetchOne('SELECT id FROM sales WHERE id = ? FOR UPDATE', [$id]);
            if (!$locked) {
                throw new Exception('Sale not found.');
            }
            $addedSubtotal = 0;

            foreach ($items as $it) {
                $lineTotal = round($it['qty'] * $it['price'], 3);

                // Insert sale_item
                $costPrice = (float)($db->fetchOne("SELECT purchase_price FROM items WHERE id = ?", [$it['itemId']])['purchase_price'] ?? 0);
                $saleItemId = $db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, cost_price, discount, tax, total)
                     VALUES (?,?,?,?,?,0,0,?)",
                    [$id, $it['itemId'], $it['qty'], $it['price'], $costPrice, $lineTotal]
                );

                // Decrement stock atomically
                $aff = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$it['qty'], $it['itemId'], $whId, $it['qty']]
                );
                if ($aff === 0) {
                    $stk = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$it['itemId'], $whId]);
                    throw new Exception("Insufficient stock (have " . ($stk['quantity'] ?? 0) . ", need {$it['qty']}).");
                }

                // Mark IMEIs sold + link
                foreach ($it['imeis'] as $imei) {
                    $rec = $db->fetchOne(
                        "SELECT id
                         FROM imei_records
                         WHERE imei = ?
                         ORDER BY
                            CASE
                                WHEN warehouse_id = ? AND status IN ('in_stock','returned') THEN 0
                                WHEN status IN ('in_stock','returned') THEN 1
                                WHEN warehouse_id = ? THEN 2
                                ELSE 3
                            END,
                            id DESC
                         LIMIT 1",
                        [$imei, $whId, $whId]
                    );
                    if (!$rec) {
                        throw new Exception(
                            "IMEI {$imei} is not in stock. Receive it via purchase (or return) before selling."
                        );
                    }
                    $imeiId = (int)$rec['id'];
                    $aff = $db->execute(
                        "UPDATE imei_records SET status='sold', sale_id=?, warehouse_id=? WHERE id=? AND status IN ('in_stock','returned')",
                        [$id, $whId, $imeiId]
                    );
                    if ($aff === 0) {
                        throw new Exception("IMEI {$imei} not available for sale.");
                    }
                    $db->insert("INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)", [$saleItemId, $imeiId]);
                }

                $addedSubtotal += $lineTotal;
            }

            // Recalculate sale totals (invoice AR = grand - paid; returns are party credits)
            $newSubtotal   = (float)$sale['subtotal'] + $addedSubtotal;
            $discount      = (float)$sale['discount'];
            $newGrandTotal = $newSubtotal - $discount;
            $paid          = (float)$sale['paid_amount'];
            $newBalance    = max(0, $newGrandTotal - $paid);

            if ($addedSubtotal > 0.001) {
                SaleValidator::enforceCreditLimit($db, (int) $sale['party_id'], $addedSubtotal);
            }

            if ($newBalance < 0.001)      { $newStatus = 'paid'; $newBalance = 0; }
            elseif ($paid > 0)            { $newStatus = 'partial'; }
            else                          { $newStatus = $sale['status'] === 'paid' ? 'confirmed' : $sale['status']; }

            $db->execute(
                "UPDATE sales SET subtotal=?, grand_total=?, balance=?, status=? WHERE id=?",
                [$newSubtotal, $newGrandTotal, $newBalance, $newStatus, $id]
            );

            if ($cashierEdit) {
                SaleValidator::assertMaxSaleQtyForSale($db, $id);
            }

            $db->commit();
            $this->logActivity('add_items_to_sale', 'sales', $id, "Added " . count($items) . " item(s) to {$sale['invoice_no']}");
            self::clearDashboardCache($whId);
            $this->flash('success', "Added " . count($items) . " item(s) to {$sale['invoice_no']}.");
            $this->redirect("?page=sales&action=detail&id={$id}");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Add failed: ' . $e->getMessage());
            $this->redirect("?page=sales&action=addItem&id={$id}");
        }
    }

    // Cancel sale
    public function cancel(): void {
        Auth::authorize('sales', 'delete');

        // AUDIT FIX S5: Require POST to prevent CSRF via GET links
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=sales');
            return;
        }

        $id   = $this->inputInt('id');
        $sale = $this->saleModel->find($id);
        if (!$this->assertSaleWarehouseAccess($sale ?: null)) {
            return;
        }

        // Lock paid invoices
        if ($sale['status'] === 'paid') {
            $this->flash('error', 'Paid invoices cannot be cancelled. Contact admin to unlock.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }
        require_once __DIR__ . '/../models/DeviceDump.php';
        $dumpedImei = (new DeviceDump())->firstDumpedImeiOnSale($id);
        if ($dumpedImei) {
            $this->flash('error', 'Cannot cancel: IMEI ' . $dumpedImei . ' was dumped. Void the dump credit first.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }
        if ($this->saleModel->cancel($id)) {
            self::clearDashboardCache((int) ($sale['warehouse_id'] ?? 0));
            $this->logActivity('cancel_sale', 'sales', $id);
            $this->flash('success', 'Sale cancelled successfully.');
        } else {
            $this->flash('error', 'Could not cancel this sale.');
        }
        $this->redirect('?page=sales');
    }

    /**
     * Admin-only: reverse a voided invoice (Sale::reopenCancelled).
     * Transactional stock + IMEI; payments are NOT recreated — full balance due unless re-recorded.
     */
    public function reopen(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }

        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=sales');
            return;
        }

        $id = $this->inputInt('id');
        if ($id <= 0) {
            $this->flash('error', 'Invalid invoice.');
            $this->redirect('?page=sales');
            return;
        }

        $sale = $this->saleModel->find($id);
        if (!$this->assertSaleWarehouseAccess($sale ?: null)) {
            return;
        }

        $result = $this->saleModel->reopenCancelled($id);

        if (!empty($result['success'])) {
            self::clearDashboardCache((int) ($sale['warehouse_id'] ?? 0));
            $this->logActivity('reopen_sale', 'sales', $id, 'Reinstated voided invoice (stock/IMEI); payments must be re-entered if applicable');
            $this->flash('success', 'Invoice reinstated: stock deducted and serials marked sold again. Payment rows were not restored — record receipts in Payments if the customer paid.');
            $this->redirect('?page=sales&action=detail&id=' . $id);
            return;
        }

        $this->flash('error', $result['error'] ?? 'Could not reinstate this invoice.');
        $this->redirect('?page=sales&action=detail&id=' . $id);
    }

    // AJAX: search items for autocomplete
    public function searchItems(): void {
        header('Content-Type: application/json');
        if (!Auth::can('sales', 'view') && !Auth::can('returns', 'view') && !Auth::can('purchases', 'view')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $q    = trim($_GET['q'] ?? '');
        // Always scope to the active session branch (ignore client-posted warehouse_id)
        $whId = (int) Auth::warehouseId();

        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        // stock=0 → names/price/IMEI only (fast). Default keeps branch stock in dropdowns.
        $withStock = !isset($_GET['stock']) || (string) $_GET['stock'] !== '0';
        $includeCost = Auth::can('purchases', 'view');
        $items = $this->itemModel->search($q, $whId ?: null, $withStock, $includeCost);
        echo json_encode($items);
    }

    /**
     * AJAX: attach branch stock to a small set of item ids (progressive autocomplete enrich).
     */
    public function searchItemStocks(): void {
        header('Content-Type: application/json');
        if (!Auth::can('sales', 'view') && !Auth::can('returns', 'view') && !Auth::can('purchases', 'view')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }

        $rawIds = trim((string) ($_GET['ids'] ?? ''));
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $rawIds)),
            static fn(int $id): bool => $id > 0
        )));
        $ids = array_slice($ids, 0, 15);
        if ($ids === []) {
            echo json_encode([]);
            return;
        }

        $stubs = [];
        foreach ($ids as $id) {
            $stubs[] = ['id' => $id];
        }
        echo json_encode($this->itemModel->attachSearchStock($stubs, Auth::warehouseId() ?: null));
    }

    // AJAX: search parties
    public function searchParties(): void {
        header('Content-Type: application/json');
        if (
            !Auth::can('sales', 'view')
            && !Auth::can('returns', 'view')
            && !Auth::can('purchases', 'view')
            && !Auth::can('payments', 'view')
            && !Auth::can('payments', 'add')
            && !Auth::can('payments_out', 'view')
            && !Auth::can('payments_out', 'add')
            && !Auth::can('dumps', 'view')
            && !Auth::can('dumps', 'add')
        ) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $q = trim($_GET['q'] ?? '');
        $type = trim((string)($_GET['type'] ?? 'customer'));

        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        // Allow supplier/customer/freight searches from other modules (purchase/returns/sales/payments).
        // payment_out → any ledger party (customer / supplier / both / freight).
        // purchase → customer / supplier / both (buy from customer-only allowed).
        $allowedTypes = ['all', 'customer', 'supplier', 'both', 'freight_forwarder', 'payment_out', 'purchase'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'customer';
        }
        $type = Auth::sanitizePartySearchType($type);

        // balances=0 → names/phones only (fast). Default keeps Due amounts in dropdowns.
        $withBalance = !isset($_GET['balances']) || (string) $_GET['balances'] !== '0';
        $parties = $this->partyModel->search($q, $type, $withBalance);
        echo json_encode($parties);
    }

    /**
     * AJAX: attach balances to a small set of party ids (progressive autocomplete enrich).
     */
    public function searchPartyBalances(): void {
        header('Content-Type: application/json');
        if (
            !Auth::can('sales', 'view')
            && !Auth::can('returns', 'view')
            && !Auth::can('purchases', 'view')
            && !Auth::can('payments', 'view')
            && !Auth::can('payments', 'add')
            && !Auth::can('payments_out', 'view')
            && !Auth::can('payments_out', 'add')
            && !Auth::can('dumps', 'view')
            && !Auth::can('dumps', 'add')
        ) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }

        $type = trim((string) ($_GET['type'] ?? 'customer'));
        $allowedTypes = ['all', 'customer', 'supplier', 'both', 'freight_forwarder', 'payment_out', 'purchase'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'customer';
        }
        $type = Auth::sanitizePartySearchType($type);

        $rawIds = trim((string) ($_GET['ids'] ?? ''));
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $rawIds)),
            static fn(int $id): bool => $id > 0
        )));
        $ids = array_slice($ids, 0, 15);
        if ($ids === []) {
            echo json_encode([]);
            return;
        }

        $ordered = $this->partyModel->findActiveByIds($ids);
        echo json_encode($this->partyModel->attachSearchBalances($ordered, $type));
    }

    // AJAX: validate IMEI
    public function checkImei(): void {
        header('Content-Type: application/json');
        if (!Auth::can('sales', 'view') && !Auth::can('sales', 'add')) {
            http_response_code(403);
            echo json_encode(['valid' => false, 'message' => 'Forbidden.']);
            return;
        }
        $imei   = trim($_GET['imei'] ?? '');
        $itemId = (int) ($_GET['item_id'] ?? 0);

        if (!$imei) {
            echo json_encode(['valid' => false, 'message' => 'Empty IMEI.']);
            return;
        }

        $db = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT ir.*, i.name as item_name FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ? AND ir.warehouse_id = ?
             ORDER BY
                CASE WHEN ir.status IN ('in_stock','returned') THEN 0 ELSE 1 END,
                ir.id DESC
             LIMIT 1",
            [$imei, Auth::warehouseId()]
        );

        if (!$row) {
            echo json_encode(['valid' => false, 'message' => 'IMEI not in stock — receive via purchase first.']);
            return;
        }

        if ($row['status'] === 'sold') {
            $targetItemId = $itemId > 0 ? $itemId : (int)$row['item_id'];
            $errors = $this->imeiModel->validateList([$imei], $targetItemId, Auth::warehouseId(), false);
            if (!empty($errors)) {
                echo json_encode(['valid' => false, 'message' => $errors[0]]);
                return;
            }
            echo json_encode(['valid' => true, 'message' => "In stock: {$row['item_name']}."]);
            return;
        }

        if ($itemId && (int)$row['item_id'] !== $itemId) {
            echo json_encode(['valid' => false, 'message' => "IMEI belongs to: {$row['item_name']}."]);
            return;
        }

        if (!in_array((string) ($row['status'] ?? ''), ['in_stock', 'returned'], true)) {
            echo json_encode(['valid' => false, 'message' => 'IMEI is not available for sale.']);
            return;
        }

        echo json_encode(['valid' => true, 'message' => "In stock: {$row['item_name']}."]);
    }

    // AJAX: get items for a sale (pre-fill return form)
    public function getSaleItems(): void {
        header('Content-Type: application/json');
        if (!Auth::can('sales', 'view') && !Auth::can('returns', 'view')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $id = (int) ($_GET['sale_id'] ?? 0);

        $items = Database::getInstance()->fetchAll(
            "SELECT si.id, si.item_id, si.quantity, si.unit_price,
                    i.name as item_name, i.has_imei
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             JOIN sales s ON s.id = si.sale_id
             WHERE si.sale_id = ? AND s.warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );

        echo json_encode($items);
    }
}
