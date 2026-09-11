<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/PurchaseOrderConverter.php';

class PurchaseOrderController extends BaseController {

    private Database $db;

    private const PO_DOC_MAX_BYTES = 10485760; // 10 MB
    private const PO_DOC_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];
    private const PO_DOC_TYPES = [
        'supplier_invoice' => 'Supplier Invoice',
        'money_transfer'   => 'TT Copy',
    ];

    /** Notes marker: payment linked from existing supplier advance — no second bank deduction. */
    private const PO_CREDIT_APPLY_MARKER = '#po_credit_apply';

    /** Rows painted on the PO list (newest first). Filters still find older POs. */
    private const INDEX_LIST_LIMIT = 25;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // ─── List all POs ─────────────────────────────────────────────────────────
    public function index(): void {
        Auth::authorize('purchases', 'view');

        $partyId  = (int) $this->input('party_id', '0', 'get');
        $itemQ    = $this->input('item',      '', 'get');
        $status   = $this->input('status',    '', 'get');
        $hasEntity = ($partyId > 0) || ($itemQ !== '');

        $dateRange = ListPage::resolveDateFiltersFromGet(2);
        // Supplier / item looks at all dates unless the user set a range.
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

        $where  = "WHERE 1=1";
        $params = [];

        if (Auth::warehouseId()) {
            $where .= " AND po.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }
        if ($partyId > 0) {
            $where .= " AND po.party_id = ?";
            $params[] = $partyId;
        }
        if ($itemQ !== '') {
            if (ctype_digit((string)$itemQ)) {
                // Selected from dropdown: match by exact item id
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_order_items poi
                    WHERE poi.po_id = po.id AND poi.item_id = ?
                )";
                $params[] = (int)$itemQ;
            } else {
                // Fallback (e.g. old bookmarked URLs): match by name/SKU text
                $where .= " AND EXISTS (
                    SELECT 1 FROM purchase_order_items poi
                    JOIN items i ON i.id = poi.item_id
                    WHERE poi.po_id = po.id AND (i.name LIKE ? OR i.sku LIKE ?)
                )";
                $itemLike = "%$itemQ%";
                $params[] = $itemLike; $params[] = $itemLike;
            }
        }
        if ($status) {
            $where .= " AND po.status = ?";
            $params[] = $status;
        }
        // Dates are optional. Item / supplier search covers all dates unless a range is set.
        if ($fromDate !== '') {
            $where .= " AND po.date >= ?";
            $params[] = $fromDate;
        }
        if ($toDate !== '') {
            $where .= " AND po.date <= ?";
            $params[] = $toDate;
        }

        $listLimit = self::INDEX_LIST_LIMIT;
        $fetchCap  = $listLimit + 1;
        $orders = $this->db->fetchAll(
            "SELECT po.id, po.po_no, po.date, po.supplier_ref, po.currency,
                    po.subtotal_foreign, po.subtotal_kwd, po.other_charges_kwd, po.adjustment_kwd,
                    po.paid_kwd, po.status, po.warehouse_id, p.name as supplier_name
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             $where
             ORDER BY po.created_at DESC
             LIMIT {$fetchCap}",
            $params
        ) ?: [];
        $this->reconcileDraftPaidOrders($orders);
        $capped         = ListPage::capRows($orders, $listLimit);
        $orders         = $capped['items'];
        $listTruncated  = $capped['truncated'];
        $listLimit      = $capped['limit'];
        $this->attachPoDocFlags($orders);

        $filterSupplier = null;
        if ($partyId > 0) {
            $partyRow = $this->db->fetchOne(
                'SELECT id, name FROM parties WHERE id = ? AND is_active = 1',
                [$partyId]
            );
            if ($partyRow) {
                $filterSupplier = [
                    'id'   => (int) $partyRow['id'],
                    'name' => (string) ($partyRow['name'] ?? ''),
                ];
            }
        }
        $filterItem = null;
        if (ctype_digit((string) $itemQ) && (int) $itemQ > 0) {
            $itemRow = $this->db->fetchOne(
                'SELECT id, name, sku FROM items WHERE id = ? AND is_active = 1',
                [(int) $itemQ]
            );
            if ($itemRow) {
                $filterItem = [
                    'id'   => (int) $itemRow['id'],
                    'name' => (string) ($itemRow['name'] ?? ''),
                    'sku'  => (string) ($itemRow['sku'] ?? ''),
                ];
            }
        }

        $pageTitle = 'Purchase Orders';
        $page      = 'purchaseorders';

        ob_start();
        include __DIR__ . '/../views/purchase_orders/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Create form ──────────────────────────────────────────────────────────
    public function create(): void {
        Auth::authorize('purchases', 'add');

        $nextPoNo   = $this->previewNextPoNo();

        $pageTitle = 'New Purchase Order';
        $page      = 'purchaseorders';
        $skipListAssets = true; // create uses neither DataTables nor Select2

        ob_start();
        include __DIR__ . '/../views/purchase_orders/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ─── Save PO ──────────────────────────────────────────────────────────────
    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=purchaseorders&action=create'); }

        $currency     = $this->input('currency') ?: 'AED';
        // Foreign price is a manual record/reminder only — no KWD conversion.
        $exchangeRate = 1;
        $items        = $this->collectPostedPoItems($currency, '?page=purchaseorders&action=create');

        $subtotalForeign = array_sum(array_column($items, 'total_foreign'));
        $subtotalKwd     = array_sum(array_column($items, 'total_kwd'));
        $otherChargesKwd = 0.0;
        $adjustmentKwd   = 0.0;
        $totalKwd        = round($subtotalKwd, 3);
        $paidKwd         = 0.0;
        $paidForeign     = 0;
        $warehouseId     = Auth::warehouseId();
        $accountId       = null;
        $status          = 'draft';

        $this->db->beginTransaction();
        try {

            $poId = $this->db->insert(
                "INSERT INTO purchase_orders
                    (po_no, party_id, warehouse_id, date, currency, exchange_rate,
                     subtotal_foreign, subtotal_kwd, other_charges_kwd, adjustment_kwd, paid_foreign, paid_kwd,
                     status, supplier_ref, notes, created_by, account_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $this->nextPoNo(),
                    $this->inputInt('party_id'),
                    $warehouseId,
                    $this->input('date') ?: date('Y-m-d'),
                    $currency,
                    $exchangeRate,
                    $subtotalForeign, $subtotalKwd, $otherChargesKwd, $adjustmentKwd,
                    $paidForeign, $paidKwd,
                    $status,
                    $this->input('supplier_ref') ?: null,
                    $this->input('notes') ?: null,
                    Auth::id(),
                    $accountId,
                ]
            );

            foreach ($items as $item) {
                $this->db->insert(
                    "INSERT INTO purchase_order_items
                        (po_id, item_id, quantity, unit_price_foreign, unit_price_kwd, total_foreign, total_kwd)
                     VALUES (?,?,?,?,?,?,?)",
                    [$poId, $item['item_id'], $item['quantity'],
                     $item['unit_price_foreign'], $item['unit_price_kwd'],
                     $item['total_foreign'], $item['total_kwd']]
                );
            }

            // Deduct paid amount from selected account and record payment
            if ($paidKwd > 0 && $accountId) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paidKwd, $accountId]
                );
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $poId, $this->inputInt('party_id'), 'out', $accountId, $paidKwd, 'bank',
                     $this->input('date') ?: date('Y-m-d'), $warehouseId, Auth::id()]
                );
            }

            $this->db->commit();
            self::clearDashboardCache($warehouseId);
            $this->flash('success', 'Purchase Order saved. You can attach Supplier Invoice / TT Copy below.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId . '#poDocumentsCard');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error saving PO: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=create');
        }
    }

    // ─── Show PO detail ───────────────────────────────────────────────────────
    public function show(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $po = $this->db->fetchOne(
            "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                    w.name as warehouse_name, u.name as created_by_name,
                    pur.invoice_no as purchase_invoice_no,
                    pur.created_at as purchase_created_at,
                    pur.date as purchase_date
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             JOIN warehouses w ON w.id = po.warehouse_id
             LEFT JOIN users u ON u.id = po.created_by
             LEFT JOIN purchases pur ON pur.id = po.converted_to
             WHERE po.id = ?",
            [$id]
        );

        if (!$po) { $this->flash('error', 'Purchase Order not found.'); $this->redirect('?page=purchaseorders'); }

        $po = $this->reconcileConvertedPoAfterCancelledPurchase($po);
        if (PurchaseOrderConverter::reconcilePoPaymentLink($this->db, (int) $po['id'])) {
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $refetched = $this->db->fetchOne(
                "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                        w.name as warehouse_name, u.name as created_by_name,
                        pur.invoice_no as purchase_invoice_no,
                        pur.created_at as purchase_created_at,
                        pur.date as purchase_date
                 FROM purchase_orders po
                 JOIN parties p ON p.id = po.party_id
                 JOIN warehouses w ON w.id = po.warehouse_id
                 LEFT JOIN users u ON u.id = po.created_by
                 LEFT JOIN purchases pur ON pur.id = po.converted_to
                 WHERE po.id = ?",
                [(int) $po['id']]
            );
            if ($refetched) {
                $this->flash('info', 'Bank payment re-linked to ' . ($po['po_no'] ?? 'PO') . '.');
                $po = $refetched;
            }
        }
        $po = $this->reconcilePoPaidStatus($po);

        $items = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.sku, i.unit
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?",
            [$id]
        );

        $accounts = self::getAccounts();

        // One-time token to prevent double "Mark as Paid"
        if (empty($_SESSION['po_markpaid_nonce']) || !is_array($_SESSION['po_markpaid_nonce'])) {
            $_SESSION['po_markpaid_nonce'] = [];
        }
        $_SESSION['po_markpaid_nonce'][$id] = bin2hex(random_bytes(16));
        $poMarkPaidNonce = $_SESSION['po_markpaid_nonce'][$id];

        if (empty($_SESSION['po_applycredit_nonce']) || !is_array($_SESSION['po_applycredit_nonce'])) {
            $_SESSION['po_applycredit_nonce'] = [];
        }
        $_SESSION['po_applycredit_nonce'][$id] = bin2hex(random_bytes(16));
        $poApplyCreditNonce = $_SESSION['po_applycredit_nonce'][$id];

        $poUnpaidKwd = max(0, round($this->poTotalKwd($po) - (float) ($po['paid_kwd'] ?? 0), 3));
        $supplierCredits = [];
        if (in_array(($po['status'] ?? ''), ['draft', 'paid'], true) && $poUnpaidKwd > 0.001) {
            $supplierCredits = $this->listUnallocatedSupplierCredits(
                (int) $po['party_id'],
                (int) $po['warehouse_id']
            );
        }

        $openShipment = $this->db->fetchOne(
            "SELECT s.id, s.shipment_no, s.status
             FROM shipment_purchase_orders spo
             JOIN shipments s ON s.id = spo.shipment_id
             WHERE spo.po_id = ? AND s.status != 'applied'
             ORDER BY s.id DESC LIMIT 1",
            [$id]
        );

        $canReverseToPo = false;
        if (($po['status'] ?? '') === 'converted' && !empty($po['converted_to'])) {
            $linkedPurchase = $this->db->fetchOne(
                "SELECT id, status FROM purchases WHERE id = ?",
                [(int) $po['converted_to']]
            );
            $appliedShipment = $this->db->fetchOne(
                "SELECT s.id FROM shipment_purchases sp
                 JOIN shipments s ON s.id = sp.shipment_id
                 WHERE sp.purchase_id = ? AND s.status = 'applied'
                 LIMIT 1",
                [(int) $po['converted_to']]
            );
            $canReverseToPo = $linkedPurchase
                && ($linkedPurchase['status'] ?? '') !== 'cancelled'
                && !$appliedShipment;
        }

        if (!$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'This purchase order is not in the current branch.');
            $this->redirect('?page=purchaseorders');
        }

        $poDocuments = $this->listPoDocuments((int) $po['id'], (int) $po['warehouse_id']);
        $poDocTypes  = self::PO_DOC_TYPES;
        $poProgress  = $this->buildPoProgressHistory($po);

        $pageTitle = 'PO — ' . $po['po_no'];
        $page      = 'purchaseorders';
        $skipListAssets = true;

        ob_start();
        include __DIR__ . '/../views/purchase_orders/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Build Progress-card steps with date/time for each completed milestone.
     *
     * @param array<string, mixed> $po
     * @return list<array{label: string, done: bool, icon: string, at: ?string}>
     */
    private function buildPoProgressHistory(array $po): array {
        $poId = (int) ($po['id'] ?? 0);
        $status = (string) ($po['status'] ?? '');
        $isPaidOrConverted = in_array($status, ['paid', 'converted'], true);
        $isConverted = $status === 'converted';

        $paidAt = null;
        if ($isPaidOrConverted && $poId > 0) {
            $payRow = $this->db->fetchOne(
                "SELECT created_at, date
                 FROM payments
                 WHERE ref_type = 'purchase_order' AND ref_id = ? AND status = 'active'
                 ORDER BY created_at ASC, id ASC
                 LIMIT 1",
                [$poId]
            );
            if ($payRow) {
                $paidAt = $payRow['created_at'] ?? null;
                // Prefer datetime; if created_at missing, use payment date only.
                if (!$paidAt && !empty($payRow['date'])) {
                    $paidAt = $payRow['date'] . ' 00:00:00';
                }
            }
        }

        $convertedAt = null;
        $goodsReceivedAt = null;
        if ($isConverted) {
            $convertedAt = $po['purchase_created_at'] ?? null;
            if (!$convertedAt && !empty($po['purchase_date'])) {
                $convertedAt = $po['purchase_date'] . ' 00:00:00';
            }

            // Goods received: shipment received_date when linked via Import Logistics, else conversion time.
            $shipRow = $this->db->fetchOne(
                "SELECT s.received_date, s.created_at, s.status
                 FROM shipment_purchase_orders spo
                 JOIN shipments s ON s.id = spo.shipment_id
                 WHERE spo.po_id = ? AND s.status = 'applied'
                 ORDER BY s.id DESC
                 LIMIT 1",
                [$poId]
            );
            if ($shipRow && !empty($shipRow['received_date'])) {
                $goodsReceivedAt = $shipRow['received_date'] . ' 00:00:00';
            } elseif ($shipRow && !empty($shipRow['created_at'])) {
                $goodsReceivedAt = $shipRow['created_at'];
            } else {
                $goodsReceivedAt = $convertedAt;
            }
        }

        return [
            [
                'label' => 'PO Created',
                'done'  => true,
                'icon'  => 'bi-file-earmark-plus',
                'at'    => $po['created_at'] ?? null,
            ],
            [
                'label' => 'Payment Sent',
                'done'  => $isPaidOrConverted,
                'icon'  => 'bi-cash-coin',
                'at'    => $isPaidOrConverted ? $paidAt : null,
            ],
            [
                'label' => 'Goods Received',
                'done'  => $isConverted,
                'icon'  => 'bi-box-seam',
                'at'    => $isConverted ? $goodsReceivedAt : null,
            ],
            [
                'label' => 'Converted to Invoice',
                'done'  => $isConverted,
                'icon'  => 'bi-receipt-cutoff',
                'at'    => $isConverted ? $convertedAt : null,
            ],
        ];
    }

    // ─── PO document attachments ──────────────────────────────────────────────
    public function uploadDoc(): void {
        if (!Auth::can('purchases', 'edit') && !Auth::can('purchases', 'add')) {
            Auth::authorize('purchases', 'edit');
        }
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
        }

        $poId = $this->inputInt('po_id');
        $po   = $this->findPoHeader($poId);
        if (!$po) {
            $this->flash('error', 'Purchase Order not found.');
            $this->redirect('?page=purchaseorders');
        }
        if (!$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'This purchase order is not in the current branch.');
            $this->redirect('?page=purchaseorders');
        }
        // Allowed on draft/paid (ordered) and converted; only cancelled is locked.
        if (($po['status'] ?? '') === 'cancelled') {
            $this->flash('error', 'Cannot attach documents to a cancelled purchase order.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
        }

        $docType = trim((string) $this->input('doc_type'));
        if (!isset(self::PO_DOC_TYPES[$docType])) {
            $this->flash('error', 'Invalid document type.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
        }

        $notes = trim((string) $this->input('notes'));
        if (mb_strlen($notes) > 500) {
            $notes = mb_substr($notes, 0, 500);
        }
        if ($notes === '') {
            $notes = null;
        }

        $this->ensurePoDocumentsTable();
        [$storedPath, $mime, $size, $originalName, $error] = $this->processPoDocUpload($poId);
        if ($error !== null) {
            $this->flash('error', $error);
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
        }

        $this->db->insert(
            "INSERT INTO purchase_order_documents
                (po_id, warehouse_id, doc_type, original_name, stored_path, mime_type, file_size, notes, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $poId,
                (int) $po['warehouse_id'],
                $docType,
                $originalName,
                $storedPath,
                $mime,
                $size,
                $notes,
                Auth::id(),
            ]
        );

        $this->flash('success', 'Document uploaded.');
        $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
    }

    public function downloadDoc(): void {
        if (!Auth::can('purchases', 'view') && !Auth::can('reports', 'view') && !Auth::can('rpt_bank_kyc', 'view')) {
            Auth::authorize('purchases', 'view');
        }

        $docId = $this->inputInt('id', 0, 'get');
        $doc = $this->findPoDocument($docId);
        if (!$doc) {
            $this->flash('error', 'Document not found.');
            $this->redirect($this->poDocDownloadFallback());
        }

        $po = $this->findPoHeader((int) $doc['po_id']);
        if (!$po || !$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'Document not available for this branch.');
            $this->redirect($this->poDocDownloadFallback());
        }

        $rel = str_replace(['\\', '..'], ['/', ''], (string) $doc['stored_path']);
        if ($rel === '' || !str_starts_with($rel, 'po_docs/')) {
            $this->flash('error', 'Document file path is invalid.');
            $this->redirect($this->poDocDownloadFallback((int) $doc['po_id']));
        }

        $abs = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $realBase = realpath(rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'po_docs');
        $realFile = realpath($abs);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            $this->flash('error', 'Document file is missing on the server.');
            $this->redirect($this->poDocDownloadFallback((int) $doc['po_id']));
        }

        $mime = (string) ($doc['mime_type'] ?: 'application/octet-stream');
        $downloadName = preg_replace('/[^a-zA-Z0-9._ -]/', '_', (string) $doc['original_name']) ?: basename($realFile);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($realFile);
        exit;
    }

    public function deleteDoc(): void {
        Auth::authorize('purchases', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
        }

        $docId = $this->inputInt('id');
        $doc = $this->findPoDocument($docId);
        if (!$doc) {
            $this->flash('error', 'Document not found.');
            $this->redirect('?page=purchaseorders');
        }

        $poId = (int) $doc['po_id'];
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Only an admin can delete uploaded PO documents.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
        }

        $po = $this->findPoHeader($poId);
        if (!$po || !$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'Document not available for this branch.');
            $this->redirect('?page=purchaseorders');
        }

        $this->db->execute("DELETE FROM purchase_order_documents WHERE id = ?", [$docId]);
        $this->deletePoDocFile((string) $doc['stored_path']);

        $this->flash('success', 'Document deleted.');
        $this->redirect('?page=purchaseorders&action=show&id=' . $poId);
    }

    /**
     * Pack page: merge uploaded Supplier Invoice + TT Copy into one PDF for the bank.
     * Merge runs in the browser (pdf-lib). Files are still fetched via downloadDoc.
     */
    public function printDocs(): void {
        Auth::authorize('purchases', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $po = $this->db->fetchOne(
            "SELECT po.*, p.name AS supplier_name
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             WHERE po.id = ?",
            [$id]
        );
        if (!$po) {
            $this->flash('error', 'Purchase Order not found.');
            $this->redirect('?page=purchaseorders');
        }
        if (!$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'This purchase order is not in the current branch.');
            $this->redirect('?page=purchaseorders');
        }

        $this->ensurePoDocumentsTable();
        $poDocuments = $this->orderPoDocumentsForBankPack(
            $this->listPoDocuments((int) $po['id'], (int) $po['warehouse_id'])
        );
        if ($poDocuments === []) {
            $this->flash('error', 'No documents uploaded on this purchase order.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }

        $settings     = self::getSettings();
        $companyName  = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
        $companyPhone = (string) ($settings['company_phone'] ?? (defined('PDF_COMPANY_PHONE') ? PDF_COMPANY_PHONE : ''));
        $companyAddress = trim((string) ($settings['company_address'] ?? ''));
        if ($companyAddress === '' || strcasecmp($companyAddress, 'Your Address Here') === 0) {
            $companyAddress = defined('PDF_COMPANY_ADDRESS') && PDF_COMPANY_ADDRESS !== 'Your Address Here'
                ? (string) PDF_COMPANY_ADDRESS
                : '';
        }
        $poTotalKwd   = $this->poTotalKwd($po);
        $paidKwd     = round((float) ($po['paid_kwd'] ?? 0), 3);
        $poNoSafe    = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($po['po_no'] ?? 'PO')) ?: 'PO';
        $packFilename = $poNoSafe . '_bank_docs.pdf';

        $packDocs = [];
        $docIds = [];
        $invCount = 0;
        $ttCount = 0;
        foreach ($poDocuments as $doc) {
            $dtype = (string) ($doc['doc_type'] ?? '');
            $docIds[] = (int) $doc['id'];
            if ($dtype === 'money_transfer') {
                $ttCount++;
            } else {
                $invCount++;
            }
            $packDocs[] = [
                'id'    => (int) $doc['id'],
                'url'   => '?page=purchaseorders&action=downloadDoc&id=' . (int) $doc['id'],
                'mime'  => (string) ($doc['mime_type'] ?? ''),
                'name'  => (string) ($doc['original_name'] ?? ''),
                'type'  => $dtype,
                'label' => self::PO_DOC_TYPES[$dtype] ?? $dtype,
            ];
        }

        require_once __DIR__ . '/../helpers/PoDocsVerify.php';
        $verifyToken = PoDocsVerify::signPo(
            $this->db,
            (int) $po['warehouse_id'],
            (int) $po['id'],
            $invCount,
            $ttCount,
            PoDocsVerify::fingerprint($docIds)
        );
        $verifyCover = PoDocsVerify::qrCoverFields($verifyToken);

        include __DIR__ . '/../views/purchase_orders/print_docs.php';
    }

    // ─── Mark as paid (goods not yet received) ────────────────────────────────
    public function markPaid(): void {
        Auth::authorize('purchases', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id        = $this->inputInt('id');
        $accountId = $this->inputInt('account_id');

        $postedNonce = isset($_POST['po_markpaid_nonce']) ? trim((string)$_POST['po_markpaid_nonce']) : '';
        $sessNonce   = $_SESSION['po_markpaid_nonce'][$id] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This PO payment request was already submitted or expired. Please refresh the page and check the PO status.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        unset($_SESSION['po_markpaid_nonce'][$id]);

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);

        if (!$po || $po['status'] !== 'draft') {
            $this->flash('error', 'Only draft POs can be marked as paid.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        if (!$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'This purchase order is not in the current branch.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (!$accountId) {
            $this->flash('error', 'Please select an account to deduct payment from.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        if (($po['currency'] ?? 'KWD') !== 'KWD' && $this->poTotalKwd($po) <= 0.001) {
            $this->flash('error', 'KWD is not known yet. Use Make Payment and type the exact amount the bank took.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Only deduct the unpaid portion (accounts for partial payments during PO creation)
            $alreadyPaid  = (float)($po['paid_kwd'] ?? 0);
            $totalKwd     = $this->poTotalKwd($po);
            $deductAmount = round($totalKwd - $alreadyPaid, 3);
            if ($deductAmount <= $this->poPaidTolerance($totalKwd)) {
                $deductAmount = 0;
            }

            $this->db->execute(
                "UPDATE purchase_orders
                 SET status='paid', paid_foreign=subtotal_foreign,
                     paid_kwd=(subtotal_kwd + COALESCE(other_charges_kwd, 0) + COALESCE(adjustment_kwd, 0)), account_id=?
                 WHERE id=?",
                [$accountId, $id]
            );
            if ($deductAmount > 0.001) {
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$deductAmount, $accountId]
                );
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $id, $po['party_id'], 'out', $accountId, $deductAmount, 'bank',
                     date('Y-m-d'), $po['warehouse_id'], Auth::id()]
                );
            }
            $this->db->commit();
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            require_once __DIR__ . '/../models/Party.php';
            Party::clearBalanceListCache();
            $this->flash('success', 'Purchase Order marked as Paid and account updated. Waiting for goods to arrive.');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
        }
        $this->redirect('?page=purchaseorders&action=show&id=' . $id);
    }

    /**
     * Link one or more unallocated supplier Payment Out rows to this PO (no second bank deduction).
     * Oldest selected PAY is applied first; splits the last source when only part covers the PO.
     */
    public function applyCredit(): void {
        Auth::authorize('purchases', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }

        $id       = $this->inputInt('id');
        $amountIn = round($this->inputFloat('amount'), 3);
        $paymentIds = $this->postedCreditPaymentIds();

        $postedNonce = isset($_POST['po_applycredit_nonce']) ? trim((string) $_POST['po_applycredit_nonce']) : '';
        $sessNonce   = $_SESSION['po_applycredit_nonce'][$id] ?? '';
        if ($sessNonce === '' || !hash_equals((string) $sessNonce, $postedNonce)) {
            $this->flash('warning', 'This apply-credit request was already submitted or expired. Refresh and try again.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        unset($_SESSION['po_applycredit_nonce'][$id]);

        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po || !in_array(($po['status'] ?? ''), ['draft', 'paid'], true)) {
            $this->flash('error', 'Only open (draft/paid) POs can use supplier credit.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }
        if (!$this->poBelongsToSessionWarehouse($po)) {
            $this->flash('error', 'This purchase order is not in the current branch.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if ($paymentIds === []) {
            $this->flash('error', 'Select at least one supplier payment to apply.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $this->db->beginTransaction();
        try {
            $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE", [$id]);
            if (!$po || !in_array(($po['status'] ?? ''), ['draft', 'paid'], true)) {
                throw new Exception('PO is no longer open.');
            }

            $totalKwd    = $this->poTotalKwd($po);
            $alreadyPaid = (float) ($po['paid_kwd'] ?? 0);
            $unpaid      = max(0, round($totalKwd - $alreadyPaid, 3));
            if ($unpaid <= 0.001) {
                throw new Exception('This PO is already fully paid.');
            }

            $poWh = (int) ($po['warehouse_id'] ?? 0);
            $lockIds = $paymentIds;
            sort($lockIds, SORT_NUMERIC);
            $sources = [];
            foreach ($lockIds as $paymentId) {
                $src = $this->db->fetchOne(
                    "SELECT * FROM payments WHERE id = ? FOR UPDATE",
                    [$paymentId]
                );
                $this->assertUnallocatedSupplierCredit($src, $po);
                $sources[] = $src;
            }
            usort($sources, static function (array $a, array $b): int {
                $da = (string) ($a['date'] ?? '');
                $db = (string) ($b['date'] ?? '');
                if ($da !== $db) {
                    return $da <=> $db;
                }
                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });

            $remaining = $amountIn > 0 ? min($amountIn, $unpaid) : $unpaid;
            if (abs($remaining - $unpaid) <= $this->poPaidTolerance($totalKwd)) {
                $remaining = $unpaid;
            }
            $appliedTotal = 0.0;
            $appliedNos   = [];
            $accountId    = 0;
            foreach ($sources as $src) {
                if ($remaining <= 0.001) {
                    break;
                }
                $slice = $this->linkSupplierCreditToPo($po, $src, $remaining);
                $appliedTotal = round($appliedTotal + $slice['amount'], 3);
                $remaining    = round($remaining - $slice['amount'], 3);
                $appliedNos[] = $slice['payment_no'];
                if ($slice['account_id'] > 0) {
                    $accountId = $slice['account_id'];
                }
            }
            if ($appliedTotal <= 0.001) {
                throw new Exception('Apply amount must be greater than zero.');
            }

            $newPaid = round($alreadyPaid + $appliedTotal, 3);
            if ($this->isPoFullyPaid($newPaid, $totalKwd)) {
                $newPaid = $totalKwd;
            }
            $newStatus = $this->resolvePoStatus($newPaid, $totalKwd);
            $this->db->execute(
                "UPDATE purchase_orders
                 SET paid_kwd = ?, paid_foreign = subtotal_foreign, account_id = COALESCE(?, account_id), status = ?
                 WHERE id = ?",
                [$newPaid, $accountId > 0 ? $accountId : null, $newStatus, $id]
            );

            $this->db->commit();
            self::clearDashboardCache($poWh);
            require_once __DIR__ . '/../models/Party.php';
            Party::clearBalanceListCache();
            $srcList = implode(', ', $appliedNos);
            $this->logActivity(
                'po_apply_credit',
                'purchase_orders',
                $id,
                "Applied {$appliedTotal} KWD from {$srcList} to {$po['po_no']} (no bank movement)"
            );
            $this->flash(
                'success',
                'Applied ' . number_format($appliedTotal, DECIMAL_PLACES) . ' KWD from ' . $srcList
                . ' to this PO. Bank was not deducted again. Remaining supplier credit stays on the statement.'
            );
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('?page=purchaseorders&action=show&id=' . $id);
    }

    // ─── Convert PO to Purchase Invoice (disabled — use Import Logistics only) ─
    public function convert(): void {
        Auth::authorize('purchases', 'add');
        $id = $this->inputInt('id');
        $this->flash(
            'error',
            'Direct Convert PO → Purchase is disabled to prevent mistakes. '
            . 'Add the PO to an Import Logistics shipment and receive it there.'
        );
        if ($id > 0) {
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }
        $this->redirect('?page=landedcost');
    }

    // ─── Reverse converted PO back to open PO (cancel linked purchase) ────────
    public function reverseToPo(): void {
        Auth::authorize('purchases', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=purchaseorders');
            return;
        }

        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po) {
            $this->flash('error', 'PO not found.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if ((int) ($po['warehouse_id'] ?? 0) !== Auth::warehouseId()) {
            $this->flash('error', 'This PO belongs to a different warehouse.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (($po['status'] ?? '') !== 'converted') {
            $this->flash('error', 'Only converted POs can be reversed.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $purchaseId = (int) ($po['converted_to'] ?? 0);
        if ($purchaseId <= 0) {
            $this->flash('error', 'No linked purchase invoice found for this PO.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        require_once __DIR__ . '/../models/Purchase.php';
        $purchaseModel = new Purchase($this->db);

        try {
            $result = $purchaseModel->reverseToPoWithReversals($purchaseId, (int) Auth::warehouseId());
            $this->logActivity(
                'reverse_purchase_to_po',
                'purchase_orders',
                $id,
                'Reversed PO ' . ($po['po_no'] ?? '') . ' — cancelled ' . ($result['invoice_no'] ?? '')
            );
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $this->flash(
                'success',
                'Purchase ' . ($result['invoice_no'] ?? '')
                . ' cancelled. ' . ($po['po_no'] ?? 'PO') . ' is open again — edit and convert when ready.'
            );
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        } catch (Exception $e) {
            if ($e->getMessage() === 'NOT_FROM_PO') {
                $this->flash('error', 'Linked purchase could not be reversed to this PO.');
            } elseif ($e->getMessage() === 'ALREADY_CANCELLED') {
                PurchaseOrderConverter::reopenConvertedPo($this->db, $id);
                self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
                $this->flash('info', 'Purchase was already cancelled — PO reopened.');
                $this->redirect('?page=purchaseorders&action=show&id=' . $id);
                return;
            } elseif (str_starts_with($e->getMessage(), 'Cannot cancel: approved purchase return')
                || str_starts_with($e->getMessage(), 'Cannot reverse:')) {
                $this->flash('error', $e->getMessage());
            } else {
                $this->flash('error', 'Failed to reverse: ' . $e->getMessage());
            }
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        }
    }

    // ─── Cancel PO ────────────────────────────────────────────────────────────
    public function cancel(): void {
        Auth::authorize('purchases', 'delete');
        // AUDIT FIX S5: Require POST for destructive action
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if ($po && $po['status'] !== 'converted') {
            $this->db->beginTransaction();
            try {
                $released = $this->releasePoPrepaidPayments($id);
                
                // Zero out paid amounts to prevent state leak on reactivation
                $this->db->execute(
                    "UPDATE purchase_orders SET status='cancelled', paid_kwd=0, paid_foreign=0, account_id=NULL WHERE id=?", 
                    [$id]
                );
                
                $this->db->commit();
                self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
                require_once __DIR__ . '/../models/Party.php';
                Party::clearBalanceListCache();
                $msg = 'Purchase Order cancelled.';
                if ($released['credit_returned'] > 0.001) {
                    $msg .= ' Bank transfer stays posted — ' . number_format($released['credit_returned'], DECIMAL_PLACES)
                        . ' KWD remains as supplier credit on the ledger.';
                }
                $this->flash('success', $msg);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->flash('error', 'Cancellation failed. Please try again.');
            }
        }
        $this->redirect('?page=purchaseorders');
    }

    // Reactivate cancelled PO (admin only)
    public function reactivate(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if ($po && $po['status'] === 'cancelled') {
            $this->db->execute("UPDATE purchase_orders SET status='draft' WHERE id=?", [$id]);
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $this->logActivity('reactivate_po', 'purchase_orders', $id, "Reactivated {$po['po_no']}");
            $this->flash('success', "PO {$po['po_no']} reactivated to Draft.");
        } else {
            $this->flash('error', 'Only cancelled POs can be reactivated.');
        }
        $this->redirect('?page=purchaseorders&action=show&id=' . $id);
    }

    // Edit PO form (admin only)
    public function edit(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        $id = $this->inputInt('id', 0, 'get');
        $po = $this->db->fetchOne(
            "SELECT po.*, p.name as supplier_name
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             WHERE po.id = ?", [$id]
        );
        if (!$po) { $this->flash('error', 'PO not found.'); $this->redirect('?page=purchaseorders'); return; }
        if ($po['status'] === 'converted') {
            $this->flash('error', 'Converted POs cannot be edited.');
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
            return;
        }

        $po['items'] = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.sku
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?
             ORDER BY poi.id", [$id]
        );

        $warehouses = self::getWarehouses();
        $accounts   = self::getAccounts();
        $pageTitle  = 'Edit: ' . $po['po_no'];
        $page       = 'purchaseorders';
        $skipListAssets = true; // edit uses neither DataTables nor Select2

        ob_start();
        include __DIR__ . '/../views/purchase_orders/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update PO (admin only)
    public function update(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=purchaseorders');
            return;
        }
        if (!$this->isPost()) { $this->redirect('?page=purchaseorders'); return; }

        $id = $this->inputInt('id');
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        if (!$po || $po['status'] === 'converted') {
            $this->flash('error', 'Cannot edit this PO.');
            $this->redirect('?page=purchaseorders');
            return;
        }

        $currency     = $this->input('currency') ?: $po['currency'];
        // Foreign price is a manual record/reminder only — no KWD conversion.
        $exchangeRate = 1;

        $items = $this->collectPostedPoItems($currency, '?page=purchaseorders&action=edit&id=' . $id);

        $subtotalForeign = array_sum(array_column($items, 'total_foreign'));
        $subtotalKwd     = array_sum(array_column($items, 'total_kwd'));
        $otherChargesKwd = max(0, round($this->inputFloat('other_charges_kwd'), 3));
        $adjustmentKwd   = round($this->inputFloat('adjustment_kwd'), 3);
        $totalKwd        = round($subtotalKwd + $otherChargesKwd + $adjustmentKwd, 3);
        if ($totalKwd < 0) {
            $this->flash('error', 'PO total cannot be negative. Check bank adjustment.');
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
            return;
        }
        $paidKwd         = round($this->inputFloat('paid_kwd'), 3);
        if ($paidKwd > 0 && abs($paidKwd - $totalKwd) <= $this->poPaidTolerance($totalKwd)) {
            $paidKwd = $totalKwd;
        }
        $paidForeign     = 0; // payment is recorded in KWD only
        $status          = $this->resolvePoStatus($paidKwd, $totalKwd);

        $newAccountId = $this->inputInt('account_id') ?: null;
        if ($paidKwd > 0 && !$newAccountId) {
            $this->flash('error', 'Please select an account for the paid amount.');
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
            return;
        }

        // Account handling: detect change in paid amount and deduct/refund accordingly
        $oldPaidKwd  = (float)$po['paid_kwd'];
        $oldAccountId = (int)($po['account_id'] ?? 0);
        $paidDiff = round($paidKwd - $oldPaidKwd, 3);

        $poPayOnFile = $this->db->fetchOne(
            "SELECT id FROM payments
             WHERE ref_type = 'purchase_order' AND ref_id = ? AND status = 'active'
             LIMIT 1",
            [$id]
        );
        if ($poPayOnFile && ($paidDiff != 0.0 || $newAccountId !== $oldAccountId)) {
            $this->flash(
                'error',
                'This PO already has a posted payment. Amount Paid and account cannot be changed. '
                . 'Cancel the PO to leave the amount as supplier credit (bank is not restored), then create a new PO if needed.'
            );
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Update PO header
            $this->db->execute(
                "UPDATE purchase_orders SET
                    date=?, currency=?, exchange_rate=?,
                    subtotal_foreign=?, subtotal_kwd=?, other_charges_kwd=?, adjustment_kwd=?, paid_foreign=?, paid_kwd=?,
                    status=?, supplier_ref=?, notes=?, account_id=?
                 WHERE id=?",
                [
                    $this->input('date') ?: $po['date'],
                    $currency, $exchangeRate,
                    $subtotalForeign, $subtotalKwd, $otherChargesKwd, $adjustmentKwd, $paidForeign, $paidKwd,
                    $status,
                    $this->input('supplier_ref') ?: null,
                    $this->input('notes') ?: null,
                    $newAccountId,
                    $id,
                ]
            );

            if ($poPayOnFile) {
                // Posted PAY rows are immutable — do not delete/recreate on item or header edits.
            } else {
            // Adjust account balances if paid amount changed
            if ($paidDiff != 0) {
                // Reverse old deduction if account changed or amount changed
                if ($oldAccountId && $oldPaidKwd > 0) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [$oldPaidKwd, $oldAccountId]
                    );
                }
                // Apply new deduction
                if ($newAccountId && $paidKwd > 0) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                        [$paidKwd, $newAccountId]
                    );
                }
            } elseif ($newAccountId !== $oldAccountId && $paidKwd > 0) {
                // Same amount but account changed — move deduction between accounts
                if ($oldAccountId) {
                    $this->db->execute(
                        "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                        [$paidKwd, $oldAccountId]
                    );
                }
                $this->db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$paidKwd, $newAccountId]
                );
            }

            // Sync payments audit record — delete old, insert fresh if paid
            $this->db->execute(
                "DELETE FROM payments WHERE ref_type = 'purchase_order' AND ref_id = ?",
                [$id]
            );
            if ($paidKwd > 0 && $newAccountId) {
                $lastPay = $this->db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase_order', $id, $po['party_id'], 'out', $newAccountId, $paidKwd, 'bank',
                     $this->input('date') ?: $po['date'], $po['warehouse_id'], Auth::id()]
                );
            }
            }

            // Delete old items and re-insert
            $this->db->execute("DELETE FROM purchase_order_items WHERE po_id = ?", [$id]);
            foreach ($items as $item) {
                $this->db->insert(
                    "INSERT INTO purchase_order_items
                        (po_id, item_id, quantity, unit_price_foreign, unit_price_kwd, total_foreign, total_kwd)
                     VALUES (?,?,?,?,?,?,?)",
                    [$id, $item['item_id'], $item['quantity'],
                     $item['unit_price_foreign'], $item['unit_price_kwd'],
                     $item['total_foreign'], $item['total_kwd']]
                );
            }

            $this->db->commit();
            $this->logActivity('edit_po', 'purchase_orders', $id, "Edited {$po['po_no']}");
            $this->flash('success', "PO {$po['po_no']} updated.");
            $this->redirect('?page=purchaseorders&action=show&id=' . $id);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=purchaseorders&action=edit&id=' . $id);
        }
    }

    // ─── AJAX item search ─────────────────────────────────────────────────────
    public function searchItems(): void {
        header('Content-Type: application/json');
        // Soft auth — never return HTML 403 (breaks autocomplete JSON parse)
        if (!Auth::can('purchases', 'view') && !Auth::can('purchases', 'add')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }

        $q = $this->inputSearch('q', '', 'get');
        if ($q === '') {
            echo json_encode([]);
            return;
        }

        // Always scope stock to the active session branch
        $whId = (int) Auth::warehouseId();
        $like = '%' . $q . '%';
        $prefix = $q . '%';

        // Optional legacy foreign-price columns (may be missing on some DBs)
        $foreignCols = $this->itemsForeignPriceSelect();

        try {
            $items = $this->db->fetchAll(
                "SELECT i.id, i.name, i.sku, i.purchase_price,
                        {$foreignCols}
                        COALESCE(SUM(s.quantity), 0) AS current_stock
                 FROM items i
                 LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
                 WHERE i.is_active = 1
                   AND (
                        i.name LIKE ? OR COALESCE(i.name_ar, '') LIKE ?
                        OR COALESCE(i.sku, '') LIKE ? OR COALESCE(i.barcode, '') LIKE ?
                        OR COALESCE(i.brand, '') LIKE ? OR COALESCE(i.model, '') LIKE ?
                   )
                 GROUP BY i.id
                 ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(i.sku, '')) = LOWER(?) THEN 0
                        WHEN LOWER(COALESCE(i.barcode, '')) = LOWER(?) THEN 1
                        WHEN LOWER(i.name) = LOWER(?) THEN 2
                        WHEN i.sku LIKE ? OR i.barcode LIKE ? OR i.name LIKE ? THEN 3
                        ELSE 4
                    END,
                    i.name ASC
                 LIMIT 20",
                [
                    $whId,
                    $like, $like, $like, $like, $like, $like,
                    $q, $q, $q,
                    $prefix, $prefix, $prefix,
                ]
            );
        } catch (Throwable $e) {
            // Fallback without name_ar / brand / model / legacy AED-USD columns
            $items = $this->db->fetchAll(
                "SELECT i.id, i.name, i.sku, i.purchase_price,
                        0 AS price_aed, 0 AS price_usd,
                        COALESCE(SUM(s.quantity), 0) AS current_stock
                 FROM items i
                 LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
                 WHERE i.is_active = 1
                   AND (i.name LIKE ? OR COALESCE(i.sku, '') LIKE ? OR COALESCE(i.barcode, '') LIKE ?)
                 GROUP BY i.id
                 ORDER BY i.name ASC
                 LIMIT 20",
                [$whId, $like, $like, $like]
            );
        }

        echo json_encode($items ?: []);
    }

    /** SQL fragment for optional items.price_aed / price_usd (legacy). No schema probe. */
    private function itemsForeignPriceSelect(): string {
        return "COALESCE(i.price_aed, 0) AS price_aed, COALESCE(i.price_usd, 0) AS price_usd,";
    }

    // ─── AJAX: price history for an item ─────────────────────────────────────
    public function itemHistory(): void {
        header('Content-Type: application/json');
        $itemId = $this->inputInt('item_id', 0, 'get');
        if (!$itemId) { echo json_encode([]); return; }

        $rows = $this->db->fetchAll(
            "SELECT po.date, po.po_no, p.name AS supplier,
                    poi.quantity, poi.unit_price_kwd,
                    poi.unit_price_foreign, po.currency
             FROM purchase_order_items poi
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = po.party_id
             WHERE poi.item_id = ? AND po.status NOT IN ('cancelled')
             ORDER BY po.date DESC, po.id DESC
             LIMIT 6",
            [$itemId]
        );
        echo json_encode($rows);
    }

        // ─── Helpers ──────────────────────────────────────────────────────────────
    /**
     * Posted PO lines. AED/USD may have blank KWD (filled at TT). Local KWD requires a price.
     *
     * @return list<array<string, mixed>>
     */
    private function collectPostedPoItems(string $currency, string $redirectUrl): array {
        $rawItems = $_POST['items'] ?? [];
        $items    = [];
        foreach ($rawItems as $row) {
            if (empty($row['item_id']) || empty($row['quantity'])) {
                continue;
            }
            $qty      = (int) $row['quantity'];
            $kwdTotal = (float) ($row['kwd_total'] ?? 0);
            $kwdPrice = (float) ($row['kwd_price'] ?? 0);
            if ($kwdTotal > 0 && $qty > 0) {
                $kwdPrice = round($kwdTotal / $qty, 3);
            }
            if ($kwdPrice > 0 && $qty > 0) {
                $kwdTotal = round($kwdPrice * $qty, 3);
            }
            $foreignPrice = round((float) ($row['foreign_price'] ?? 0), 3);
            $foreignTotal = round($foreignPrice * $qty, 3);
            $items[] = [
                'item_id'            => (int) $row['item_id'],
                'quantity'           => $qty,
                'unit_price_foreign' => $foreignPrice,
                'unit_price_kwd'     => $kwdPrice,
                'total_foreign'      => $foreignTotal,
                'total_kwd'          => $kwdTotal,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Add at least one item.');
            $this->redirect($redirectUrl);
        }

        $isForeign = $currency !== 'KWD';
        foreach ($items as $it) {
            if ($isForeign && $it['unit_price_foreign'] <= 0.001) {
                $this->flash('error', 'Enter the supplier foreign price on every line. Leave KWD blank — it is filled when you pay the bank TT.');
                $this->redirect($redirectUrl);
            }
            if (!$isForeign && $it['total_kwd'] <= 0.001) {
                $this->flash('error', 'Enter the KWD price on every line.');
                $this->redirect($redirectUrl);
            }
        }

        return $items;
    }

    /** PO KWD total: items + other charges + bank/rounding adjustment. */
    private function poTotalKwd(array $po): float {
        return round(
            (float) ($po['subtotal_kwd'] ?? 0)
            + (float) ($po['other_charges_kwd'] ?? 0)
            + (float) ($po['adjustment_kwd'] ?? 0),
            3
        );
    }

    private function isPoCreditApplyPayment(array $pay): bool {
        return str_contains((string) ($pay['notes'] ?? ''), self::PO_CREDIT_APPLY_MARKER);
    }

    /**
     * Payment IDs posted from Apply Supplier Credit (multi-select, or legacy single).
     *
     * @return list<int>
     */
    private function postedCreditPaymentIds(): array {
        $ids = [];
        $raw = $_POST['payment_ids'] ?? null;
        if (is_array($raw)) {
            foreach ($raw as $v) {
                $id = (int) $v;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        $single = $this->inputInt('payment_id');
        if ($single > 0) {
            $ids[] = $single;
        }
        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed>|null $src
     * @param array<string, mixed> $po
     */
    private function assertUnallocatedSupplierCredit(?array $src, array $po): void {
        if (!$src || ($src['status'] ?? '') !== 'active') {
            throw new Exception('Selected payment is not available.');
        }
        if ((string) ($src['payment_type'] ?? '') !== 'out'
            || (string) ($src['ref_type'] ?? '') !== 'purchase'
            || (int) ($src['ref_id'] ?? 0) > 0
        ) {
            throw new Exception('Only unallocated supplier Payment Out rows can be applied.');
        }
        if ((int) ($src['party_id'] ?? 0) !== (int) $po['party_id']) {
            throw new Exception('Payment belongs to a different supplier.');
        }
        $payWh = (int) ($src['warehouse_id'] ?? 0);
        $poWh  = (int) ($po['warehouse_id'] ?? 0);
        if ($payWh > 0 && $poWh > 0 && $payWh !== $poWh) {
            throw new Exception('Payment is on a different branch.');
        }
        $available = round((float) ($src['amount'] ?? 0), 3);
        if ($available <= 0.001) {
            throw new Exception('Selected payment has no remaining amount.');
        }
    }

    /**
     * Move part/all of an already-locked unallocated Payment Out onto this PO.
     *
     * @param array<string, mixed> $po
     * @param array<string, mixed> $src
     * @return array{amount: float, payment_no: string, account_id: int}
     */
    private function linkSupplierCreditToPo(array $po, array $src, float $maxApply): array {
        $poId      = (int) ($po['id'] ?? 0);
        $paymentId = (int) ($src['id'] ?? 0);
        $available = round((float) ($src['amount'] ?? 0), 3);
        $applyAmt  = round(min($maxApply, $available), 3);
        if ($applyAmt <= 0.001) {
            throw new Exception('Apply amount must be greater than zero.');
        }

        $srcNo = (string) ($src['payment_no'] ?? ('#' . $paymentId));
        $markerNote = self::PO_CREDIT_APPLY_MARKER . ' from ' . $srcNo
            . ' — no bank movement (advance already paid)';
        $accountId = (int) ($src['account_id'] ?? 0);
        $payWh = (int) ($src['warehouse_id'] ?? 0);
        $poWh  = (int) ($po['warehouse_id'] ?? 0);

        if ($applyAmt + 0.0005 >= $available) {
            $oldNotes = trim((string) ($src['notes'] ?? ''));
            $newNotes = $oldNotes === '' ? $markerNote : ($oldNotes . ' · ' . $markerNote);
            $this->db->execute(
                "UPDATE payments
                 SET ref_type = 'purchase_order', ref_id = ?, notes = ?
                 WHERE id = ?",
                [$poId, $newNotes, $paymentId]
            );
        } else {
            $residual = round($available - $applyAmt, 3);
            $this->db->execute(
                "UPDATE payments SET amount = ? WHERE id = ?",
                [$residual, $paymentId]
            );

            require_once __DIR__ . '/../models/Payment.php';
            $paymentModel = new Payment();
            $payNo = $paymentModel->nextPaymentNo();
            $this->db->insert(
                "INSERT INTO payments
                    (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount,
                     payment_method, cheque_no, date, notes, warehouse_id, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $payNo,
                    'purchase_order',
                    $poId,
                    (int) $po['party_id'],
                    'out',
                    $accountId > 0 ? $accountId : null,
                    $applyAmt,
                    $src['payment_method'] ?? 'bank',
                    $src['cheque_no'] ?? null,
                    $src['date'] ?? date('Y-m-d'),
                    $markerNote,
                    $poWh > 0 ? $poWh : ($payWh > 0 ? $payWh : Auth::warehouseId()),
                    'active',
                    Auth::id(),
                ]
            );
        }

        return [
            'amount'     => $applyAmt,
            'payment_no' => $srcNo,
            'account_id' => $accountId,
        ];
    }

    /**
     * Unallocated Payment Out rows on this supplier/branch (ref_type=purchase, no invoice).
     *
     * @return list<array<string, mixed>>
     */
    private function listUnallocatedSupplierCredits(int $partyId, int $warehouseId): array {
        if ($partyId <= 0) {
            return [];
        }
        $params = [$partyId];
        $whSql = '';
        if ($warehouseId > 0) {
            // Legacy NULL warehouse payments count on Main operational branch.
            $whSql = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';
            $params[] = $warehouseId;
        }
        return $this->db->fetchAll(
            "SELECT id, payment_no, date, amount, account_id, notes, warehouse_id
             FROM payments
             WHERE party_id = ? AND payment_type = 'out' AND status = 'active'
               AND ref_type = 'purchase' AND (ref_id IS NULL OR ref_id = 0)
               AND amount > 0.001{$whSql}
             ORDER BY date ASC, id ASC",
            $params
        );
    }

    /**
     * Unlink PO prepaid payments when cancelling.
     * Posted bank transfers are never reversed — they stay as unallocated supplier credit.
     *
     * @return array{bank_restored: float, credit_returned: float}
     */
    private function releasePoPrepaidPayments(int $poId): array {
        $creditReturned = 0.0;
        $poPays = $this->db->fetchAll(
            "SELECT id, amount
             FROM payments
             WHERE ref_type = 'purchase_order' AND ref_id = ?
             FOR UPDATE",
            [$poId]
        );
        foreach ($poPays as $pay) {
            $amt = round((float) ($pay['amount'] ?? 0), 3);
            $this->db->execute(
                "UPDATE payments
                 SET ref_type = 'purchase', ref_id = 0
                 WHERE id = ?",
                [(int) $pay['id']]
            );
            $creditReturned = round($creditReturned + max(0, $amt), 3);
        }
        return ['bank_restored' => 0.0, 'credit_returned' => $creditReturned];
    }

    /**
     * Remaining balance at or below this (KWD) is treated as fully paid — covers
     * per-unit rounding when qty × unit price differs slightly from bank amount.
     */
    private function poPaidTolerance(float $totalKwd): float {
        return min(0.100, max(0.001, round($totalKwd * 0.00001, 3)));
    }

    private function isPoFullyPaid(float $paidKwd, float $totalKwd): bool {
        if ($paidKwd <= 0) {
            return false;
        }
        return round($totalKwd - $paidKwd, 3) <= $this->poPaidTolerance($totalKwd);
    }

    private function resolvePoStatus(float $paidKwd, float $totalKwd): string {
        return $this->isPoFullyPaid($paidKwd, $totalKwd) ? 'paid' : 'draft';
    }

    /** Reopen PO when its linked purchase invoice was cancelled (e.g. mistaken conversion). */
    private function reconcileConvertedPoAfterCancelledPurchase(array $po): array {
        if (($po['status'] ?? '') !== 'converted') {
            return $po;
        }

        $convertedTo = (int) ($po['converted_to'] ?? 0);
        if ($convertedTo <= 0) {
            return $po;
        }

        $purchase = $this->db->fetchOne(
            "SELECT id, status, invoice_no FROM purchases WHERE id = ?",
            [$convertedTo]
        );
        if ($purchase && ($purchase['status'] ?? '') !== 'cancelled') {
            return $po;
        }

        if (PurchaseOrderConverter::reopenConvertedPo($this->db, (int) $po['id'])) {
            self::clearDashboardCache((int) ($po['warehouse_id'] ?? 0));
            $refetched = $this->db->fetchOne(
                "SELECT po.*, p.name as supplier_name, p.phone as supplier_phone,
                        w.name as warehouse_name, u.name as created_by_name,
                        pur.invoice_no as purchase_invoice_no
                 FROM purchase_orders po
                 JOIN parties p ON p.id = po.party_id
                 JOIN warehouses w ON w.id = po.warehouse_id
                 LEFT JOIN users u ON u.id = po.created_by
                 LEFT JOIN purchases pur ON pur.id = po.converted_to
                 WHERE po.id = ?",
                [(int) $po['id']]
            );
            if ($refetched) {
                $this->flash(
                    'info',
                    'Linked purchase was cancelled — ' . ($po['po_no'] ?? 'PO') . ' reopened. Edit items and convert again.'
                );
                return $refetched;
            }
        }

        return $po;
    }

    /** Upgrade legacy draft POs that were fully paid but left draft due to rounding. */
    private function reconcilePoPaidStatus(array $po): array {
        if (($po['status'] ?? '') !== 'draft') {
            return $po;
        }
        $totalKwd = $this->poTotalKwd($po);
        $paidKwd  = (float)($po['paid_kwd'] ?? 0);
        if (!$this->isPoFullyPaid($paidKwd, $totalKwd)) {
            return $po;
        }
        $this->db->execute("UPDATE purchase_orders SET status = 'paid' WHERE id = ?", [(int)$po['id']]);
        self::clearDashboardCache((int)($po['warehouse_id'] ?? 0));
        $po['status'] = 'paid';
        return $po;
    }

    private function reconcileDraftPaidOrders(array &$orders): void {
        $cacheCleared = false;
        foreach ($orders as &$o) {
            if (($o['status'] ?? '') !== 'draft') {
                continue;
            }
            $totalKwd = $this->poTotalKwd($o);
            $paidKwd  = (float)($o['paid_kwd'] ?? 0);
            if (!$this->isPoFullyPaid($paidKwd, $totalKwd)) {
                continue;
            }
            $this->db->execute("UPDATE purchase_orders SET status = 'paid' WHERE id = ?", [(int)$o['id']]);
            $o['status'] = 'paid';
            if (!$cacheCleared) {
                self::clearDashboardCache((int)($o['warehouse_id'] ?? 0));
                $cacheCleared = true;
            }
        }
        unset($o);
    }

    /** UI preview only — no lock; assigned number may differ if another PO saves first. */
    private function previewNextPoNo(): string {
        $last = $this->db->fetchOne('SELECT po_no FROM purchase_orders ORDER BY id DESC LIMIT 1');
        return $this->formatPoNo($last ? (int) substr((string) $last['po_no'], 3) : 0);
    }

    /** Next PO number — MUST be called inside a transaction (row lock). */
    private function nextPoNo(): string {
        $last = $this->db->fetchOne('SELECT po_no FROM purchase_orders ORDER BY id DESC LIMIT 1 FOR UPDATE');
        return $this->formatPoNo($last ? (int) substr((string) $last['po_no'], 3) : 0);
    }

    private function formatPoNo(int $lastNum): string {
        return 'PO-' . str_pad((string) ($lastNum + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Attach doc flags for list icons (one query for all visible POs).
     * Sets has_invoice_doc, has_tt_doc, doc_count on each order row.
     * Does not run schema migration — soft-fails if table is missing.
     *
     * @param list<array<string,mixed>> $orders
     */
    private function attachPoDocFlags(array &$orders): void {
        if ($orders === []) {
            return;
        }
        foreach ($orders as &$o) {
            $o['has_invoice_doc'] = false;
            $o['has_tt_doc']      = false;
            $o['doc_count']       = 0;
        }
        unset($o);

        $ids = array_values(array_unique(array_map(
            static fn(array $o): int => (int) ($o['id'] ?? 0),
            $orders
        )));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = $ids;
            $sql = "SELECT po_id,
                           SUM(CASE WHEN doc_type = 'supplier_invoice' THEN 1 ELSE 0 END) AS invoice_count,
                           SUM(CASE WHEN doc_type = 'money_transfer' THEN 1 ELSE 0 END) AS tt_count,
                           COUNT(*) AS doc_count
                    FROM purchase_order_documents
                    WHERE po_id IN ($placeholders)";
            if (Auth::warehouseId()) {
                $sql .= " AND warehouse_id = ?";
                $params[] = Auth::warehouseId();
            }
            $sql .= " GROUP BY po_id";

            $rows = $this->db->fetchAll($sql, $params) ?: [];
        } catch (Throwable $e) {
            // Table may not exist yet — skip icons without blocking the PO list.
            return;
        }

        $byPo = [];
        foreach ($rows as $row) {
            $byPo[(int) $row['po_id']] = $row;
        }

        foreach ($orders as &$o) {
            $info = $byPo[(int) $o['id']] ?? null;
            if ($info === null) {
                continue;
            }
            $o['has_invoice_doc'] = ((int) ($info['invoice_count'] ?? 0)) > 0;
            $o['has_tt_doc']      = ((int) ($info['tt_count'] ?? 0)) > 0;
            $o['doc_count']       = (int) ($info['doc_count'] ?? 0);
        }
        unset($o);
    }

    private function findPoHeader(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $row = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        return $row ?: null;
    }

    private function poBelongsToSessionWarehouse(array $po): bool {
        $sessionWh = Auth::warehouseId();
        if (!$sessionWh) {
            return true;
        }
        return (int) ($po['warehouse_id'] ?? 0) === (int) $sessionWh;
    }

    private static function poDocumentsSchemaFlagPath(): string {
        $dir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_schema';
        return $dir . DIRECTORY_SEPARATOR . 'purchase_order_documents.ok';
    }

    /**
     * Ensure purchase_order_documents exists.
     * After first success, skips information_schema via a temp-file flag (cheap on Hostinger).
     */
    private function ensurePoDocumentsTable(): void {
        static $ready = null;
        if ($ready === true) {
            return;
        }
        if ($ready === false) {
            return;
        }

        $flag = self::poDocumentsSchemaFlagPath();
        if (is_file($flag)) {
            $ready = true;
            return;
        }

        try {
            // Fast path: table already usable (avoids information_schema when possible)
            try {
                $this->db->fetchOne("SELECT 1 FROM purchase_order_documents LIMIT 1");
                $flagDir = dirname($flag);
                if (!is_dir($flagDir)) {
                    @mkdir($flagDir, 0700, true);
                }
                @file_put_contents($flag, (string) time(), LOCK_EX);
                $ready = true;
                return;
            } catch (Throwable $e) {
                // Fall through to create
            }

            $this->db->execute(
                "CREATE TABLE IF NOT EXISTS purchase_order_documents (
                    id              INT AUTO_INCREMENT PRIMARY KEY,
                    po_id           INT NOT NULL,
                    warehouse_id    INT NOT NULL,
                    doc_type        VARCHAR(40) NOT NULL DEFAULT 'other',
                    original_name   VARCHAR(255) NOT NULL,
                    stored_path     VARCHAR(255) NOT NULL,
                    mime_type       VARCHAR(100) NOT NULL,
                    file_size       INT NOT NULL DEFAULT 0,
                    notes           VARCHAR(500) DEFAULT NULL,
                    uploaded_by     INT DEFAULT NULL,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_po_docs_po (po_id),
                    INDEX idx_po_docs_wh (warehouse_id),
                    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
                    FOREIGN KEY (uploaded_by) REFERENCES users(id)
                )"
            );

            $flagDir = dirname($flag);
            if (!is_dir($flagDir)) {
                @mkdir($flagDir, 0700, true);
            }
            @file_put_contents($flag, (string) time(), LOCK_EX);
            $ready = true;
        } catch (Throwable $e) {
            error_log('[PurchaseOrder] ensurePoDocumentsTable failed: ' . $e->getMessage());
            $ready = false;
        }
    }

    /**
     * Bank pack order: Supplier Invoice then TT Copy, oldest first within each type.
     *
     * @param list<array<string,mixed>> $docs
     * @return list<array<string,mixed>>
     */
    private function orderPoDocumentsForBankPack(array $docs): array {
        $rank = [
            'supplier_invoice' => 0,
            'money_transfer'   => 1,
        ];
        $filtered = [];
        foreach ($docs as $doc) {
            $dtype = (string) ($doc['doc_type'] ?? '');
            if (!isset($rank[$dtype])) {
                continue;
            }
            $filtered[] = $doc;
        }
        usort($filtered, static function (array $a, array $b) use ($rank): int {
            $ra = $rank[(string) ($a['doc_type'] ?? '')] ?? 99;
            $rb = $rank[(string) ($b['doc_type'] ?? '')] ?? 99;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $ta = (string) ($a['created_at'] ?? '');
            $tb = (string) ($b['created_at'] ?? '');
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });
        return $filtered;
    }

    private function poDocDownloadFallback(?int $poId = null): string {
        if (Auth::can('purchases', 'view')) {
            return $poId ? ('?page=purchaseorders&action=show&id=' . $poId) : '?page=purchaseorders';
        }
        return '?page=reports&action=bankPoDocs';
    }

    /** @return list<array<string,mixed>> */
    private function listPoDocuments(int $poId, int $warehouseId): array {
        try {
            return $this->db->fetchAll(
                "SELECT d.*, u.name AS uploaded_by_name
                 FROM purchase_order_documents d
                 LEFT JOIN users u ON u.id = d.uploaded_by
                 WHERE d.po_id = ? AND d.warehouse_id = ?
                 ORDER BY d.created_at DESC, d.id DESC",
                [$poId, $warehouseId]
            ) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function findPoDocument(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        try {
            $row = $this->db->fetchOne(
                "SELECT * FROM purchase_order_documents WHERE id = ?",
                [$id]
            );
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{0:?string,1:?string,2:int,3:?string,4:?string}
     *         [stored_path, mime, size, original_name, error]
     */
    private function processPoDocUpload(int $poId): array {
        $file = $_FILES['doc_file'] ?? null;
        $hasUpload = is_array($file)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (!$hasUpload) {
            return [null, null, 0, null, 'Please choose a file to upload.'];
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $msg = match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large for the server upload limit. Use a file under 10 MB (or a smaller PDF/photo).',
                UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder is missing. Contact admin.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file. Contact admin.',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension. Contact admin.',
                default => 'Upload failed. Please try again.',
            };
            return [null, null, 0, null, $msg];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::PO_DOC_MAX_BYTES) {
            return [null, null, 0, null, 'File must be a PDF or image under 10 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [null, null, 0, null, 'Upload was invalid.'];
        }

        $mime = $this->detectUploadedMime($tmp);
        if ($mime === null || !isset(self::PO_DOC_MIME[$mime])) {
            return [null, null, 0, null, 'File must be PDF, JPG, PNG, or WEBP.'];
        }

        $ext = self::PO_DOC_MIME[$mime];
        $dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'po_docs';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [null, null, 0, null, 'Could not create upload folder.'];
        }

        $basename = 'po' . $poId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destAbs  = $dir . DIRECTORY_SEPARATOR . $basename;
        if (!move_uploaded_file($tmp, $destAbs)) {
            return [null, null, 0, null, 'Could not save the uploaded file.'];
        }

        $original = trim((string) ($file['name'] ?? ''));
        $original = $original !== '' ? basename($original) : $basename;
        if (mb_strlen($original) > 255) {
            $original = mb_substr($original, 0, 255);
        }

        return ['po_docs/' . $basename, $mime, $size, $original, null];
    }

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

    private function deletePoDocFile(?string $relativePath): void {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        if (!str_starts_with($relativePath, 'po_docs/')) {
            return;
        }
        $abs = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
