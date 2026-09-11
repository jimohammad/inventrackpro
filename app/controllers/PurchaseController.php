<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Purchase.php';
require_once __DIR__ . '/../services/SaleInvoiceFile.php';

class PurchaseController extends BaseController {

    private Purchase $purchaseModel;
    private IMEI $imeiModel;

    public function __construct() {
        parent::__construct();
        $this->purchaseModel = new Purchase();
        $this->imeiModel     = new IMEI();
    }

    public function index(): void {
        Auth::authorize('purchases', 'view');

        $search = $this->inputSearch('search', '', 'get');
        $item   = $this->input('item', '', 'get');
        $status = $this->input('status', '', 'get');
        $hasEntity = ($item !== '') || ($search !== '');

        $dateRange = ListPage::resolveDateFiltersFromGet(2);
        // Item / supplier (search) looks at all dates unless the user set a range.
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
            'search'    => $search,
            'item'      => $item,
            'status'    => $status,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
            'all_dates' => ($fromDate === '' && $toDate === ''),
        ];

        $listPage       = $this->purchaseModel->getIndexList($filters, Auth::warehouseId(), Purchase::INDEX_LIST_LIMIT);
        $purchases      = $listPage['items'];
        $listTruncated  = $listPage['truncated'];
        $listLimit      = $listPage['limit'];
        $datesDefaulted = $dateRange['dates_defaulted'];
        $stats     = $this->purchaseModel->getMonthStats(Auth::warehouseId());

        $filterItem = null;
        if (ctype_digit((string) $item) && (int) $item > 0) {
            $itemRow = (new Item())->find((int) $item);
            if ($itemRow) {
                $filterItem = [
                    'id'   => (int) $itemRow['id'],
                    'name' => (string) ($itemRow['name'] ?? ''),
                    'sku'  => (string) ($itemRow['sku'] ?? ''),
                ];
            }
        }

        $pageTitle = 'Purchases';
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('purchases', 'add');

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
                $imeis = array_values(array_unique(array_filter(array_map(
                    static fn($v) => ImeiFormat::normalize((string) $v),
                    explode("\n", (string) $row['imeis'])
                ))));
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
            $this->flash('error', 'Please select a supplier or customer.');
            $this->redirect('?page=purchases&action=create');
            return;
        }

        // Serials are optional on new purchase; scan from the invoice after save.
        // If any IMEIs were entered on this form, still reject bad format.
        Item::ensureSerialKindColumn();
        $pendingImeiScan = false;
        $itemIds = array_values(array_unique(array_map(static fn($i) => (int) $i['item_id'], $items)));
        if (!empty($itemIds)) {
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $rows = $db->fetchAll(
                "SELECT id, name, has_imei, COALESCE(serial_kind, 'phone') AS serial_kind
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
                if (!empty($info['has_imei'])) {
                    $qty = (int) ($it['quantity'] ?? 0);
                    $cnt = is_array($it['imeis'] ?? null) ? count($it['imeis']) : 0;
                    if ($qty > $cnt) {
                        $pendingImeiScan = true;
                    }
                    foreach ($it['imeis'] ?? [] as $imei) {
                        $formatErr = $this->purchaseImeiFormatError((string) $imei, $info);
                        if ($formatErr !== null) {
                            $this->flash('error', 'Item "' . ($info['name'] ?? '') . '": ' . $formatErr . ' (' . $imei . ')');
                            $this->redirect('?page=purchases&action=create');
                            return;
                        }
                    }
                }
            }
        }

        $invoiceNo = $this->purchaseModel->nextInvoiceNo();
        $subtotal  = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
        // New purchase has no invoice discount or on-invoice payment — pay later via Payment Out.
        $discount   = 0.0;
        $tax        = 0;
        $grandTotal = $subtotal;
        $paid       = 0.0;
        $balance    = $grandTotal;
        $warehouseId = Auth::warehouseId();
        $status     = $balance < 0.001 ? 'paid' : 'confirmed';

        // Duplicate-PO guard: if this supplier already has an unconverted PO for the same total in
        // this warehouse, the user is almost certainly re-keying a PO that should be received via
        // Import Logistics. Without this guard, BOTH the PO payment ('purchase_order') and this
        // Purchase payment ('purchase') get inserted and the bank account is debited twice.
        $openPo = $this->purchaseModel->findBlockingOpenPurchaseOrder($partyId, $warehouseId, $grandTotal);
        if ($openPo) {
            $this->flash('error',
                'Blocked to prevent a duplicate payment: this supplier already has an open Purchase Order '
                . $openPo['po_no'] . ' for ' . number_format((float)$openPo['subtotal_kwd'] + (float)($openPo['other_charges_kwd'] ?? 0) + (float)($openPo['adjustment_kwd'] ?? 0), DECIMAL_PLACES)
                . ' KWD that has not been converted yet. Receive that PO via Import Logistics '
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
                1,
                'cash'
            );

            $this->logActivity('create_purchase', 'purchases', $purchaseId, $invoiceNo);
            self::clearDashboardCache((int) $warehouseId);
            if ($pendingImeiScan) {
                $this->flash(
                    'success',
                    "Purchase {$invoiceNo} saved. Open the invoice and click Scan IMEIs when you are ready to enter serial numbers."
                );
            } else {
                $this->flash('success', "Purchase {$invoiceNo} saved.");
            }
            $printMode = $this->input('print_mode');
            if ($printMode === '1') {
                $tpl = Auth::printTemplate();
                if ($tpl === 'thermal') {
                    $this->redirect('?page=purchases&action=thermalPrint&id=' . $purchaseId . '&thermal=1&autoprint=1');
                }
                $this->redirect('?page=purchases&action=print&id=' . $purchaseId . '&autoprint=1');
            }
            if ($printMode === '2') {
                $this->redirect('?page=purchases&action=thermalPrint&id=' . $purchaseId . '&thermal=1&autoprint=1');
            }
            $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);
        } catch (Throwable $e) {
            $this->flash('error', 'Failed: ' . $e->getMessage());
            $this->redirect('?page=purchases&action=create');
        }
    }

    public static function clearInvoiceImportSession(): void {
        unset($_SESSION['invoice_import']);
    }

    /** Upload a sales-invoice JSON from another shop, then preview as a purchase. */
    public function importInvoice(): void {
        Auth::authorize('purchases', 'add');

        if ($this->isPost()) {
            $this->loadInvoiceImportFile();
        }

        $pack = $_SESSION['invoice_import'] ?? null;
        $step = 'upload';
        $resolved = null;
        $duplicate = null;
        $importNonce = '';

        if (is_array($pack) && !empty($pack['raw'])) {
            try {
                $payload  = SaleInvoiceFile::parse((string) $pack['raw']);
                $resolved = SaleInvoiceFile::resolveForWarehouse($payload, Database::getInstance());
                $step     = 'preview';
                $importNonce = (string) ($pack['nonce'] ?? '');
                $whId = (int) Auth::warehouseId();
                $duplicate = $this->purchaseModel->findDuplicateImported(
                    (string) ($resolved['source_invoice_no'] ?? ''),
                    $whId
                );
            } catch (RuntimeException $e) {
                self::clearInvoiceImportSession();
                $this->flash('error', $e->getMessage());
                $this->redirect('?page=purchases&action=importInvoice');
                return;
            }
        }

        $pageTitle = 'Import invoice';
        $page      = 'purchases';
        ob_start();
        include __DIR__ . '/../views/purchases/import_invoice.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function importInvoiceClear(): void {
        Auth::authorize('purchases', 'add');
        self::clearInvoiceImportSession();
        $this->redirect('?page=purchases&action=importInvoice');
    }

    public function importInvoiceStore(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $pack = $_SESSION['invoice_import'] ?? null;
        if (!is_array($pack) || empty($pack['raw'])) {
            $this->flash('error', 'Upload the invoice file again.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $postedNonce = trim((string) ($_POST['import_nonce'] ?? ''));
        $sessNonce   = (string) ($pack['nonce'] ?? '');
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This import form expired. Upload the file again.');
            self::clearInvoiceImportSession();
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        try {
            $payload  = SaleInvoiceFile::parse((string) $pack['raw']);
            $resolved = SaleInvoiceFile::resolveForWarehouse($payload, Database::getInstance());
        } catch (RuntimeException $e) {
            self::clearInvoiceImportSession();
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        if (empty($resolved['ok'])) {
            $this->flash('error', $resolved['errors'][0] ?? 'This file cannot be imported until every line matches a catalog SKU.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $partyId = $this->inputInt('party_id');
        if ($partyId <= 0) {
            $this->flash('error', 'Please select a supplier.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $db = Database::getInstance();
        $party = $db->fetchOne(
            "SELECT id, name FROM parties WHERE id = ? AND is_active = 1",
            [$partyId]
        );
        if (!$party) {
            $this->flash('error', 'Supplier not found.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $warehouseId = (int) Auth::warehouseId();
        $sourceNo    = (string) ($resolved['source_invoice_no'] ?? '');
        $duplicate   = $this->purchaseModel->findDuplicateImported($sourceNo, $warehouseId);
        $override    = $this->inputInt('confirm_duplicate', 0) === 1;
        if ($duplicate && !$override) {
            $this->flash('error', 'This invoice number was already imported as '
                . ($duplicate['invoice_no'] ?? '') . '. Tick “Import anyway” to create another purchase.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $items      = SaleInvoiceFile::toPurchaseItems($resolved);
        $subtotal   = (float) $resolved['subtotal'];
        $discount   = (float) $resolved['discount'];
        $grandTotal = (float) $resolved['grand_total'];
        $paid       = 0.0;
        $balance    = $grandTotal;
        $status     = $balance < 0.001 ? 'paid' : 'confirmed';

        $openPo = $this->purchaseModel->findBlockingOpenPurchaseOrder($partyId, $warehouseId, $grandTotal);
        if ($openPo) {
            $this->flash('error',
                'Blocked to prevent a duplicate payment: this supplier already has an open Purchase Order '
                . $openPo['po_no'] . ' for ' . number_format((float)$openPo['subtotal_kwd'] + (float)($openPo['other_charges_kwd'] ?? 0) + (float)($openPo['adjustment_kwd'] ?? 0), DECIMAL_PLACES)
                . ' KWD that has not been converted yet. Receive that PO via Import Logistics instead.'
            );
            $this->redirect('?page=purchaseorders&action=show&id=' . (int)$openPo['id']);
            return;
        }

        $purchaseDate = $this->input('date') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)) {
            $purchaseDate = date('Y-m-d');
        }

        try {
            $invoiceNo  = $this->purchaseModel->nextInvoiceNo();
            $purchaseId = $this->purchaseModel->createFullPurchase(
                $invoiceNo,
                $sourceNo !== '' ? $sourceNo : null,
                $partyId,
                $warehouseId,
                $purchaseDate,
                $subtotal,
                $discount,
                0.0,
                $grandTotal,
                $paid,
                $balance,
                $status,
                SaleInvoiceFile::importNotes($resolved),
                Auth::id(),
                $items,
                1,
                'cash'
            );

            $this->logActivity('import_sale_invoice', 'purchases', $purchaseId,
                $invoiceNo . ' from ' . $sourceNo);
            self::clearDashboardCache($warehouseId);
            self::clearInvoiceImportSession();
            $this->flash('success', "Purchase {$invoiceNo} imported from {$sourceNo}.");
            $this->redirect('?page=purchases&action=detail&id=' . $purchaseId);
        } catch (Throwable $e) {
            $this->flash('error', 'Failed: ' . $e->getMessage());
            $this->redirect('?page=purchases&action=importInvoice');
        }
    }

    private function loadInvoiceImportFile(): void {
        $file = $_FILES['invoice_file'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->flash('error', 'Choose an invoice JSON file.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Could not upload the file. Try again.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $this->flash('error', 'Could not read the uploaded file.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            $this->flash('error', 'Invoice file must be a JSON file under 2 MB.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $raw = file_get_contents($tmp);
        if ($raw === false) {
            $this->flash('error', 'Could not read the uploaded file.');
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        try {
            SaleInvoiceFile::parse($raw);
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=purchases&action=importInvoice');
            return;
        }

        $_SESSION['invoice_import'] = [
            'raw'      => $raw,
            'filename' => (string) ($file['name'] ?? 'invoice.iqbal.json'),
            'nonce'    => bin2hex(random_bytes(16)),
        ];
        $this->redirect('?page=purchases&action=importInvoice');
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

        $lineCtx = $this->purchaseModel->getDetailLineItemsWithForeignContext($id);
        $purchase['items'] = $lineCtx['items'];
        $poForeign         = $lineCtx['foreign'];
        $purchase['payments'] = $this->purchaseModel->getLinkedPayments($id);
        $this->purchaseModel->syncHeaderWithPaymentSum($id, $purchase);

        $showImeiScan    = $this->purchaseModel->countImeiScannableItems($id) > 0;
        $imeiScanLines   = $showImeiScan ? $this->purchaseModel->getImeiScanLines($id) : [];
        $imeiPendingQty  = 0;
        $imeiPendingLines = 0;
        foreach ($imeiScanLines as $line) {
            $need = max(0, (int) $line['quantity'] - (int) $line['scanned']);
            if ($need > 0) {
                $imeiPendingLines++;
                $imeiPendingQty += $need;
            }
        }

        $db = Database::getInstance();
        require_once __DIR__ . '/../services/StockQuantityService.php';
        $lineStockSnapshots = StockQuantityService::purchaseLineStockSnapshot(
            $db,
            $id,
            (int) $purchase['warehouse_id']
        );
        $stockByItemId = [];
        foreach ($lineStockSnapshots as $snap) {
            $stockByItemId[(int) $snap['item_id']] = $snap;
        }
        foreach ($purchase['items'] as &$lineItem) {
            $snap = $stockByItemId[(int) ($lineItem['item_id'] ?? 0)] ?? null;
            $lineItem['stock_qty'] = (int) ($snap['stock_qty'] ?? 0);
            $lineItem['imei_scanned'] = (int) ($snap['imei_scanned'] ?? 0);
        }
        unset($lineItem);
        $stockMissingFromList = StockQuantityService::purchaseLooksMissingFromStock($lineStockSnapshots);
        $importShipment = $db->fetchOne(
            "SELECT s.id, s.shipment_no, s.received_date, s.status
             FROM shipment_purchases sp
             JOIN shipments s ON s.id = sp.shipment_id
             WHERE sp.purchase_id = ?
             ORDER BY s.id DESC LIMIT 1",
            [$id]
        );
        $linkedPo = $db->fetchOne(
            "SELECT id, po_no, status, currency
             FROM purchase_orders
             WHERE converted_to = ? AND status = 'converted'",
            [$id]
        );
        if (!$linkedPo && $poForeign) {
            $linkedPo = [
                'id'       => (int) ($poForeign['po_id'] ?? 0),
                'po_no'    => (string) ($poForeign['po_no'] ?? ''),
                'status'   => 'converted',
                'currency' => (string) ($poForeign['currency'] ?? ''),
            ];
        }
        $canReverseToPo = $linkedPo
            && ($purchase['status'] ?? '') !== 'cancelled'
            && (($importShipment['status'] ?? '') !== 'applied');
        $importCostLines = [];
        if ($importShipment) {
            $importCostLines = $db->fetchAll(
                "SELECT description, cost_category, pay_location, amount, is_applied, payment_id
                 FROM shipment_costs WHERE shipment_id = ? ORDER BY id",
                [(int) $importShipment['id']]
            );
        }

        $accounts  = self::getAccounts();
        $pageTitle = 'Purchase: ' . $purchase['invoice_no'];
        $page      = 'purchases';

        $linkedReturns = [];
        if (Auth::can('returns', 'view') || Auth::can('purchases', 'view')) {
            require_once __DIR__ . '/../models/Return.php';
            $linkedReturns = (new SaleReturn())->getLinkedToPurchase(
                $id,
                (int) ($purchase['warehouse_id'] ?? Auth::warehouseId() ?: 0)
            );
        }

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
            $result    = $this->purchaseModel->cancelWithReversals($id, (int) Auth::warehouseId());
            $invoiceNo = $result['invoice_no'];
            $this->logActivity('cancel_purchase', 'purchases', $id, 'Cancelled ' . $invoiceNo);
            self::clearDashboardCache(Auth::warehouseId());
            $msg = 'Purchase ' . $invoiceNo . ' cancelled successfully.';
            if (!empty($result['reopened_po_nos'])) {
                $msg .= ' Linked PO(s) reopened: ' . implode(', ', $result['reopened_po_nos']) . '.';
            }
            $this->flash('success', $msg);
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

    /**
     * Reverse a PO-converted purchase: cancel invoice, restore stock, reopen source PO.
     */
    public function reverseToPo(): void {
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
            $result = $this->purchaseModel->reverseToPoWithReversals($id, (int) Auth::warehouseId());
            $this->logActivity(
                'reverse_purchase_to_po',
                'purchases',
                $id,
                'Reversed ' . ($result['invoice_no'] ?? '') . ' to PO ' . ($result['po_no'] ?? '')
            );
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash(
                'success',
                'Purchase ' . ($result['invoice_no'] ?? '')
                . ' reversed. PO ' . ($result['po_no'] ?? '') . ' is open again — edit and convert when ready.'
            );
            $this->redirect('?page=purchaseorders&action=show&id=' . (int) ($result['po_id'] ?? 0));
        } catch (Exception $e) {
            if ($e->getMessage() === 'NOT_FROM_PO') {
                $this->flash('error', 'This purchase was not created from a PO conversion.');
            } elseif ($e->getMessage() === 'ALREADY_CANCELLED') {
                $this->flash('warning', 'Purchase is already cancelled.');
            } elseif ($e->getMessage() === 'Purchase not found.') {
                $this->flash('error', 'Purchase not found.');
            } elseif (str_starts_with($e->getMessage(), 'Cannot cancel: approved purchase return')
                || str_starts_with($e->getMessage(), 'Cannot reverse:')) {
                $this->flash('error', $e->getMessage());
            } else {
                $this->flash('error', 'Failed to reverse purchase: ' . $e->getMessage());
            }
            $this->redirect('?page=purchases&action=detail&id=' . $id);
        }
    }

    /**
     * Rebuild warehouse stock qty for items on this purchase from documents.
     * Does not require IMEI scans — arrivals belong on Stock List immediately.
     */
    public function repairStock(): void {
        Auth::authorize('purchases', 'edit');
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchases');
            return;
        }
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

        $purchase = $this->purchaseModel->findHeaderForView($id);
        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
            return;
        }
        if ((int) $purchase['warehouse_id'] !== Auth::warehouseId()) {
            $this->flash('error', 'This purchase belongs to a different warehouse.');
            $this->redirect('?page=purchases');
            return;
        }

        require_once __DIR__ . '/../services/StockQuantityService.php';
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $result = StockQuantityService::rebuildForPurchase($db, $id);
            $db->commit();
            $this->logActivity(
                'repair_purchase_stock',
                'purchases',
                $id,
                'Rebuilt stock for ' . (int) $result['rebuilt'] . ' item(s); '
                . (int) $result['changed'] . ' changed'
            );
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash(
                'success',
                'Stock rebuilt from documents for ' . (int) $result['rebuilt']
                . ' item(s) (' . (int) $result['changed'] . ' updated). '
                . 'Quantities now show on Stock List — IMEI scan is separate.'
            );
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Stock rebuild failed: ' . $e->getMessage());
        }

        $this->redirect('?page=purchases&action=detail&id=' . $id);
    }

    public function edit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchases');
            return;
        }

        $id          = $this->inputInt('id', 0, 'get');
        $editPurchase = $this->purchaseModel->findFull($id);

        if (!$editPurchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
            return;
        }

        if ((int) $editPurchase['warehouse_id'] !== Auth::warehouseId()) {
            $this->flash('error', 'This purchase belongs to a different warehouse.');
            $this->redirect('?page=purchases');
            return;
        }

        if (($editPurchase['status'] ?? '') === 'cancelled') {
            $this->flash('error', 'Cancelled purchases cannot be edited.');
            $this->redirect('?page=purchases&action=detail&id=' . $id);
            return;
        }

        $db = Database::getInstance();
        $hasReturns = (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM `returns` WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
            [$id]
        )['c'] ?? 0);
        if ($hasReturns > 0) {
            $this->flash('error', 'Purchases with approved supplier returns cannot be edited here.');
            $this->redirect('?page=purchases&action=detail&id=' . $id);
            return;
        }

        $showImeiScan = $this->purchaseModel->countImeiScannableItems($id) > 0;

        $pageTitle = 'Edit: ' . $editPurchase['invoice_no'];
        $page      = 'purchases';

        ob_start();
        include __DIR__ . '/../views/purchases/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchases');
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('?page=purchases');
            return;
        }

        $id       = $this->inputInt('id', 0, 'get') ?: $this->inputInt('id');
        $purchase = $this->purchaseModel->findFull($id);

        if (!$purchase) {
            $this->flash('error', 'Purchase not found.');
            $this->redirect('?page=purchases');
            return;
        }

        if ((int) $purchase['warehouse_id'] !== Auth::warehouseId()) {
            $this->flash('error', 'This purchase belongs to a different warehouse.');
            $this->redirect('?page=purchases');
            return;
        }

        if (($purchase['status'] ?? '') === 'cancelled') {
            $this->flash('error', 'Cancelled purchases cannot be edited.');
            $this->redirect('?page=purchases');
            return;
        }

        $db          = Database::getInstance();
        $warehouseId = (int) $purchase['warehouse_id'];

        $hasReturns = (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM `returns` WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'",
            [$id]
        )['c'] ?? 0);
        if ($hasReturns > 0) {
            $this->flash('error', 'Purchases with approved supplier returns cannot be edited.');
            $this->redirect('?page=purchases&action=detail&id=' . $id);
            return;
        }

        $newDate             = $this->input('date') ?: $purchase['date'];
        $newSupplierInvoice  = trim($this->input('supplier_invoice_no'));
        $newDiscount         = max(0.0, $this->inputFloat('discount'));
        $newNotes            = $this->input('notes');
        $rawItems            = $_POST['items'] ?? [];
        $newSubtotal         = 0.0;
        $affectedItemIds     = [];

        $db->beginTransaction();
        try {
            if (!empty($rawItems)) {
                foreach ($rawItems as $piId => $row) {
                    $piId = (int) $piId;

                    $oldItem = $db->fetchOne(
                        "SELECT pi.item_id, pi.quantity, pi.unit_price, pi.total, i.has_imei
                         FROM purchase_items pi
                         JOIN items i ON i.id = pi.item_id
                         WHERE pi.id = ? AND pi.purchase_id = ?",
                        [$piId, $id]
                    );
                    if (!$oldItem) {
                        continue;
                    }

                    if (!empty($row['deleted'])) {
                        $badImei = $db->fetchOne(
                            "SELECT imei FROM imei_records
                             WHERE purchase_id = ? AND item_id = ?
                               AND (status != 'in_stock' OR sale_id IS NOT NULL)
                             LIMIT 1",
                            [$id, (int) $oldItem['item_id']]
                        );
                        if ($badImei) {
                            throw new Exception(
                                'Cannot remove line: IMEI ' . ($badImei['imei'] ?? '') . ' is already sold or used.'
                            );
                        }

                        $db->execute(
                            "DELETE FROM imei_records WHERE purchase_id = ? AND item_id = ?",
                            [$id, (int) $oldItem['item_id']]
                        );

                        $stockRow = $db->fetchOne(
                            "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                            [(int) $oldItem['item_id'], $warehouseId]
                        );
                        $deduct = (int) $oldItem['quantity'];
                        if (!$stockRow || (int) $stockRow['quantity'] < $deduct) {
                            throw new Exception('Cannot remove item — stock already used or sold.');
                        }
                        $db->execute(
                            "UPDATE stock SET quantity = quantity - ? WHERE id = ?",
                            [$deduct, (int) $stockRow['id']]
                        );
                        $db->execute("DELETE FROM purchase_items WHERE id = ?", [$piId]);
                        $affectedItemIds[(int) $oldItem['item_id']] = true;
                        continue;
                    }

                    $newQty   = max(1, (int) ($row['quantity'] ?? 1));
                    $newPrice = (float) ($row['unit_price'] ?? 0);
                    if ($newPrice < 0) {
                        throw new Exception('Price cannot be negative.');
                    }
                    $newTotal = round($newQty * $newPrice, 3);
                    $oldQty   = (int) $oldItem['quantity'];
                    $qtyDiff  = $newQty - $oldQty;

                    $imeiCount = (int) ($db->fetchOne(
                        "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
                        [$id, (int) $oldItem['item_id']]
                    )['c'] ?? 0);

                    if ($qtyDiff !== 0 && (!empty($oldItem['has_imei']) || $imeiCount > 0)) {
                        throw new Exception(
                            'Cannot change quantity of IMEI-tracked items during edit. Remove the line or adjust via Scan IMEIs.'
                        );
                    }

                    $db->execute(
                        "UPDATE purchase_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $piId]
                    );
                    $affectedItemIds[(int) $oldItem['item_id']] = true;

                    if ($qtyDiff !== 0) {
                        if ($qtyDiff > 0) {
                            $stockRow = $db->fetchOne(
                                "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                                [(int) $oldItem['item_id'], $warehouseId]
                            );
                            if ($stockRow) {
                                $db->execute(
                                    "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                                    [$qtyDiff, (int) $stockRow['id']]
                                );
                            } else {
                                $db->insert(
                                    "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                                    [(int) $oldItem['item_id'], $warehouseId, $qtyDiff]
                                );
                            }
                        } else {
                            $need = abs($qtyDiff);
                            $aff  = $db->execute(
                                "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                                [$need, (int) $oldItem['item_id'], $warehouseId, $need]
                            );
                            if ($aff === 0) {
                                throw new Exception('Cannot reduce quantity — stock already used or sold.');
                            }
                        }
                    }

                    $newSubtotal += $newTotal;
                }
            } else {
                $newSubtotal = (float) $purchase['subtotal'];
            }

            $newItems = $_POST['new_items'] ?? [];
            Item::ensureSerialKindColumn();
            foreach ($newItems as $row) {
                $itemId   = (int) ($row['item_id'] ?? 0);
                $newQty   = max(1, (int) ($row['quantity'] ?? 1));
                $newPrice = (float) ($row['unit_price'] ?? 0);
                $newTotal = round($newQty * $newPrice, 3);
                if (!$itemId || $newPrice <= 0) {
                    continue;
                }

                $itemMeta = $db->fetchOne(
                    "SELECT id, name, has_imei, COALESCE(serial_kind, 'phone') AS serial_kind FROM items WHERE id = ?",
                    [$itemId]
                );
                if (!$itemMeta) {
                    throw new Exception('Item not found for new line.');
                }

                $imeis = [];
                if (!empty($row['imeis'])) {
                    $imeis = array_values(array_unique(array_filter(array_map(
                        static function ($v) {
                            return ImeiFormat::normalize((string) $v);
                        },
                        preg_split('/[\r\n,;\s]+/', (string) $row['imeis']) ?: []
                    ))));
                }

                if (!empty($itemMeta['has_imei']) && !empty($imeis) && count($imeis) !== $newQty) {
                    throw new Exception(
                        'Item "' . $itemMeta['name'] . '": entered ' . count($imeis)
                        . ' IMEI(s) but quantity is ' . $newQty . '.'
                    );
                }

                foreach ($imeis as $imei) {
                    $formatErr = $this->purchaseImeiFormatError($imei, $itemMeta);
                    if ($formatErr) {
                        throw new Exception('Item "' . $itemMeta['name'] . '": ' . $formatErr . ' (' . $imei . ')');
                    }
                    $exists = $db->fetchOne("SELECT id, purchase_id FROM imei_records WHERE imei = ?", [$imei]);
                    if ($exists) {
                        throw new Exception(
                            'IMEI ' . $imei . ' already exists'
                            . (!empty($exists['purchase_id']) ? ' on another purchase' : '') . '.'
                        );
                    }
                }

                $db->insert(
                    "INSERT INTO purchase_items (purchase_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$id, $itemId, $newQty, $newPrice, $newTotal]
                );

                $stockRow = $db->fetchOne(
                    "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                    [$itemId, $warehouseId]
                );
                if ($stockRow) {
                    $db->execute(
                        "UPDATE stock SET quantity = quantity + ? WHERE id = ?",
                        [$newQty, (int) $stockRow['id']]
                    );
                } else {
                    $db->insert(
                        "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                        [$itemId, $warehouseId, $newQty]
                    );
                }

                foreach ($imeis as $imei) {
                    $db->insert(
                        "INSERT INTO imei_records (imei, item_id, warehouse_id, purchase_id, status)
                         VALUES (?,?,?,?,'in_stock')",
                        [$imei, $itemId, $warehouseId, $id]
                    );
                }

                $newSubtotal += $newTotal;
                $affectedItemIds[$itemId] = true;
            }

            $newGrandTotal = $newSubtotal - $newDiscount;

            $db->execute(
                "UPDATE purchases SET date = ?, supplier_invoice_no = ?, subtotal = ?, discount = ?, grand_total = ?, notes = ?
                 WHERE id = ? AND warehouse_id = ?",
                [
                    $newDate,
                    $newSupplierInvoice !== '' ? $newSupplierInvoice : null,
                    $newSubtotal,
                    $newDiscount,
                    $newGrandTotal,
                    $newNotes ?: null,
                    $id,
                    $warehouseId,
                ]
            );

            $this->purchaseModel->recomputeBalanceAfterReturns($id);

            require_once __DIR__ . '/../services/ItemCostService.php';
            foreach (array_keys($affectedItemIds) as $itemId) {
                ItemCostService::syncItemMasterFromLatestPurchase($db, (int) $itemId);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Error updating: ' . $e->getMessage());
            $this->redirect("?page=purchases&action=edit&id={$id}");
            return;
        }

        $this->logActivity('edit_purchase', 'purchases', $id, "Edited {$purchase['invoice_no']}: items/prices updated");
        self::clearDashboardCache((int) ($purchase['warehouse_id'] ?? 0));
        $this->flash('success', "Purchase {$purchase['invoice_no']} updated.");
        $this->redirect('?page=purchases&action=detail&id=' . $id);
    }

    public function print(): void {
        $this->renderPurchasePrint(isset($_GET['thermal']));
    }

    public function thermalPrint(): void {
        $this->renderPurchasePrint(true);
    }

    private function renderPurchasePrint(bool $isThermal): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');

        $purchase = $this->purchaseModel->findHeaderForView($id);

        if (!$purchase) {
            die('Purchase not found.');
        }

        $purchase['items'] = $this->purchaseModel->getPrintLineItems($id);
        $settings          = self::getSettings();

        $partyModel     = new Party();
        $partyBalance   = $partyModel->findWithBalance((int) $purchase['party_id']);
        $currentBalance = (float) ($partyBalance['net_balance'] ?? 0);
        // Match party statement: balance on the line before this purchase (not sales-style net − invoice balance).
        $purchase['prev_balance']  = $partyModel->runningBalanceBeforePurchase(
            (int) $purchase['party_id'],
            $id
        );
        $purchase['total_balance'] = $currentBalance;

        $purchasePrintThermal = (bool) $isThermal;

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
        Item::ensureSerialKindColumn();
        $imei = ImeiFormat::normalize($imei);

        $db = Database::getInstance();
        $whId = Auth::warehouseId();

        $purchase = $db->fetchOne(
            "SELECT warehouse_id FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$purchaseId, $whId]
        );
        if (!$purchase) {
            echo json_encode(['ok' => false, 'msg' => 'Purchase not found in this branch.']); return;
        }
        $warehouseId = (int) $purchase['warehouse_id'];

        $itemRow = $db->fetchOne(
            "SELECT i.name AS item_name, COALESCE(i.serial_kind, 'phone') AS serial_kind,
                    COALESCE(c.name, '') AS category_name, pi.quantity
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE pi.purchase_id = ? AND pi.item_id = ?",
            [$purchaseId, $itemId]
        );
        if (!$itemRow) {
            echo json_encode(['ok' => false, 'msg' => 'Item not in this purchase.']); return;
        }
        $formatErr = $this->purchaseImeiFormatError($imei, $itemRow);
        if ($formatErr !== null) {
            echo json_encode(['ok' => false, 'msg' => $formatErr]); return;
        }

        $qty = (int) $itemRow['quantity'];
        $already = (int) $db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['c'];
        if ($already >= $qty) {
            echo json_encode(['ok' => false, 'msg' => 'All IMEIs already scanned for this line.']); return;
        }

        $result = ['ok' => false];
        $db->beginTransaction();
        try {
            $result = $this->imeiModel->attachToPurchase($imei, $itemId, $warehouseId, $purchaseId);
            if (empty($result['ok'])) {
                $db->rollback();
                echo json_encode(['ok' => false, 'msg' => $result['msg'] ?? 'Could not save IMEI.']); return;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('imeiScanAdd error: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Database error. Please retry.']); return;
        }

        $scanned = (int) $db->fetchOne(
            "SELECT COUNT(*) as c FROM imei_records WHERE purchase_id=? AND item_id=?",
            [$purchaseId, $itemId]
        )['c'];

        $msg = !empty($result['restocked'])
            ? 'Re-stocked (previously ' . ($result['previous_status'] ?? 'sold') . ')'
            : null;

        echo json_encode([
            'ok'        => true,
            'imei'      => $imei,
            'scanned'   => $scanned,
            'qty'       => $qty,
            'restocked' => !empty($result['restocked']),
            'msg'       => $msg,
        ]);
    }

    // AJAX: bulk-save many IMEIs from paste with duplicate validation
    public function imeiScanBulk(): void {
        Auth::authorize('purchases', 'edit');
        header('Content-Type: application/json');

        if (!$this->isPost()) { echo json_encode(['ok' => false, 'msg' => 'POST required.']); return; }

        $purchaseId = $this->inputInt('purchase_id', 0, 'post');
        $itemId     = $this->inputInt('item_id',     0, 'post');
        $raw        = (string) $this->inputImeiBulk('imeis', '', 'post');

        if (!$purchaseId || !$itemId || $raw === '') {
            echo json_encode(['ok' => false, 'msg' => 'Missing data.']); return;
        }

        Item::ensureSerialKindColumn();
        $db = Database::getInstance();

        $whId = Auth::warehouseId();
        $purchase = $db->fetchOne(
            "SELECT warehouse_id FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$purchaseId, $whId]
        );
        if (!$purchase) { echo json_encode(['ok' => false, 'msg' => 'Purchase not found in this branch.']); return; }
        $warehouseId = (int) $purchase['warehouse_id'];

        $item = $db->fetchOne(
            "SELECT pi.quantity, i.name AS item_name,
                    COALESCE(i.serial_kind, 'phone') AS serial_kind,
                    COALESCE(c.name, '') AS category_name,
                    (SELECT COUNT(*) FROM imei_records WHERE purchase_id=? AND item_id=?) AS scanned
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE pi.purchase_id=? AND pi.item_id=?",
            [$purchaseId, $itemId, $purchaseId, $itemId]
        );
        if (!$item) { echo json_encode(['ok' => false, 'msg' => 'Item not in this purchase.']); return; }

        $qty       = (int) $item['quantity'];
        $scanned   = (int) $item['scanned'];
        $remaining = max(0, $qty - $scanned);
        $preview   = $this->inputInt('preview', 0) === 1;

        // Parse — split on newlines, commas, semicolons, whitespace fences
        $tokens  = preg_split('/[\r\n,;\s]+/', $raw);
        $skipped = [];
        $clean   = [];
        $seen    = [];

        foreach ($tokens as $tok) {
            $imei = ImeiFormat::normalize((string) $tok);
            if ($imei === '') continue;
            $formatErr = $this->purchaseImeiFormatError($imei, $item);
            if ($formatErr !== null) {
                $skipped[] = ['imei' => $imei, 'reason' => $formatErr];
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
                'preview' => $preview,
                'msg'     => 'No valid IMEIs to import.',
            ]);
            return;
        }

        $savedCount = 0;

        if ($preview) {
            foreach ($clean as $imei) {
                if ($remaining <= 0) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Exceeds remaining quantity'];
                    continue;
                }
                $block = $this->imeiModel->attachToPurchaseBlockReason($imei, $itemId, $purchaseId);
                if ($block !== null) {
                    $skipped[] = ['imei' => $imei, 'reason' => $block];
                    continue;
                }
                $savedCount++;
                $remaining--;
            }
            echo json_encode([
                'ok'      => true,
                'saved'   => $savedCount,
                'skipped' => $skipped,
                'scanned' => $scanned,
                'qty'     => $qty,
                'preview' => true,
            ]);
            return;
        }

        $db->beginTransaction();
        try {
            foreach ($clean as $imei) {
                if ($remaining <= 0) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Exceeds remaining quantity'];
                    continue;
                }

                $result = $this->imeiModel->attachToPurchase($imei, $itemId, $warehouseId, $purchaseId);
                if (empty($result['ok'])) {
                    $skipped[] = ['imei' => $imei, 'reason' => $result['msg'] ?? 'Could not save'];
                    continue;
                }
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
            'preview' => false,
        ]);
    }

    // AJAX: bulk-delete scanned IMEIs (clear all or pasted list)
    public function imeiScanBulkDelete(): void {
        Auth::authorize('purchases', 'edit');
        header('Content-Type: application/json');

        if (!$this->isPost()) {
            echo json_encode(['ok' => false, 'msg' => 'POST required.']);
            return;
        }

        $purchaseId = $this->inputInt('purchase_id', 0, 'post');
        $itemId     = $this->inputInt('item_id', 0, 'post');
        $scope      = strtolower($this->input('scope', 'list', 'post'));
        $raw        = (string) $this->inputImeiBulk('imeis', '', 'post');

        if (!$purchaseId || !$itemId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing data.']);
            return;
        }
        if (!in_array($scope, ['all', 'list'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid scope.']);
            return;
        }

        $db = Database::getInstance();

        $item = $db->fetchOne(
            "SELECT pi.quantity
             FROM purchase_items pi
             WHERE pi.purchase_id = ? AND pi.item_id = ?",
            [$purchaseId, $itemId]
        );
        if (!$item) {
            echo json_encode(['ok' => false, 'msg' => 'Item not in this purchase.']);
            return;
        }

        $skipped = [];
        $toDelete = [];

        if ($scope === 'all') {
            $rows = $db->fetchAll(
                "SELECT id, imei, status FROM imei_records WHERE purchase_id = ? AND item_id = ?",
                [$purchaseId, $itemId]
            );
            foreach ($rows as $row) {
                if ($row['status'] !== 'in_stock') {
                    $skipped[] = ['imei' => $row['imei'], 'reason' => 'Cannot remove — status is ' . $row['status']];
                    continue;
                }
                $toDelete[] = (int) $row['id'];
            }
        } else {
            if ($raw === '') {
                echo json_encode(['ok' => false, 'msg' => 'No IMEIs provided.']);
                return;
            }

            $tokens = preg_split('/[\r\n,;\s]+/', $raw);
            $seen   = [];
            $imeis  = [];
            foreach ($tokens as $tok) {
                $imei = ImeiFormat::normalize((string) $tok);
                if ($imei === '') {
                    continue;
                }
                if (!ImeiFormat::isPlausible($imei)) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Not a phone IMEI or tablet serial'];
                    continue;
                }
                if (isset($seen[$imei])) {
                    continue;
                }
                $seen[$imei] = true;
                $imeis[]     = $imei;
            }

            if (empty($imeis)) {
                echo json_encode(['ok' => false, 'msg' => 'No valid IMEIs to remove.']);
                return;
            }

            $placeholders = implode(',', array_fill(0, count($imeis), '?'));
            $rows = $db->fetchAll(
                "SELECT id, imei, status FROM imei_records
                 WHERE purchase_id = ? AND item_id = ? AND imei IN ($placeholders)",
                array_merge([$purchaseId, $itemId], $imeis)
            );
            $rowMap = [];
            foreach ($rows as $row) {
                $rowMap[$row['imei']] = $row;
            }

            foreach ($imeis as $imei) {
                if (!isset($rowMap[$imei])) {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Not found in this purchase item'];
                    continue;
                }
                $row = $rowMap[$imei];
                if ($row['status'] !== 'in_stock') {
                    $skipped[] = ['imei' => $imei, 'reason' => 'Cannot remove — status is ' . $row['status']];
                    continue;
                }
                $toDelete[] = (int) $row['id'];
            }
        }

        $toDelete = array_values(array_unique($toDelete));
        if (empty($toDelete)) {
            echo json_encode([
                'ok'      => true,
                'deleted' => 0,
                'skipped' => $skipped,
                'scanned' => (int) $db->fetchOne(
                    "SELECT COUNT(*) AS c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
                    [$purchaseId, $itemId]
                )['c'],
                'msg'     => 'No IMEIs removed.',
            ]);
            return;
        }

        $deletedCount = 0;
        $db->beginTransaction();
        try {
            foreach ($toDelete as $imeiId) {
                $detach = $this->imeiModel->detachFromPurchase((int) $imeiId, $purchaseId);
                if (!empty($detach['ok'])) {
                    $deletedCount++;
                } else {
                    $skipped[] = [
                        'imei'   => (string) ($db->fetchOne("SELECT imei FROM imei_records WHERE id = ?", [(int) $imeiId])['imei'] ?? $imeiId),
                        'reason' => $detach['msg'] ?? 'Could not remove',
                    ];
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('imeiScanBulkDelete error: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Database error during bulk delete.']);
            return;
        }

        $scanned = (int) $db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records WHERE purchase_id = ? AND item_id = ?",
            [$purchaseId, $itemId]
        )['c'];

        if ($deletedCount > 0) {
            $this->logActivity(
                'imei_bulk_delete_purchase',
                'imei_records',
                $purchaseId,
                "Bulk deleted {$deletedCount} IMEI(s) for item #{$itemId} on purchase #{$purchaseId}; skipped " . count($skipped)
            );
        }

        echo json_encode([
            'ok'      => true,
            'deleted' => $deletedCount,
            'skipped' => $skipped,
            'scanned' => $scanned,
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
        $whId = Auth::warehouseId();
        $purchase = $db->fetchOne(
            "SELECT id FROM purchases WHERE id = ? AND warehouse_id = ?",
            [$purchaseId, $whId]
        );
        if (!$purchase) {
            echo json_encode(['ok' => false, 'msg' => 'Purchase not found in this branch.']); return;
        }

        $row = $db->fetchOne(
            "SELECT id FROM imei_records WHERE imei=? AND purchase_id=? AND item_id=?",
            [strtoupper($imei), $purchaseId, $itemId]
        );
        if (!$row) { echo json_encode(['ok' => false, 'msg' => 'IMEI not found.']); return; }

        $db->beginTransaction();
        try {
            $detach = $this->imeiModel->detachFromPurchase((int) $row['id'], $purchaseId);
            if (empty($detach['ok'])) {
                $db->rollback();
                echo json_encode(['ok' => false, 'msg' => $detach['msg'] ?? 'Could not remove IMEI.']); return;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log('imeiScanDelete error: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Database error. Please retry.']); return;
        }

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

    /** @param array<string, mixed> $itemMeta name/item_name, serial_kind, category_name */
    private function purchaseImeiFormatError(string $imei, array $itemMeta): ?string {
        Item::ensureSerialKindColumn();
        $name = (string) ($itemMeta['name'] ?? $itemMeta['item_name'] ?? '');
        $kind = ImeiFormat::kindFromItem($itemMeta, $name, (string) ($itemMeta['category_name'] ?? ''));
        return ImeiFormat::error($imei, $kind, $name);
    }
}
