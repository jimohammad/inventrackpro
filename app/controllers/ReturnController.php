<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Return.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Purchase.php';

class ReturnController extends BaseController {
    private SaleReturn $returnModel;
    private Party      $partyModel;
    private Item       $itemModel;
    private Sale       $saleModel;

    public function __construct() {
        parent::__construct();
        $this->returnModel = new SaleReturn();
        $this->partyModel  = new Party();
        $this->itemModel   = new Item();
        $this->saleModel   = new Sale();
    }

    /**
     * Buffer the full HTML response for returns screens. On any Throwable, discard all
     * nested buffers and emit a standalone returns error page (no half-rendered layout).
     */
    private function runReturnsHtml(callable $body): void {
        $baseLevel = ob_get_level();
        ob_start();
        try {
            $body();
            $out = ob_get_clean();
            if ($out !== false && $out !== '') {
                echo $out;
            }
        } catch (\Throwable $e) {
            while (ob_get_level() > $baseLevel) {
                ob_end_clean();
            }
            error_log('[returns] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
            }
            include __DIR__ . '/../views/returns/module_error.php';
        }
    }

    /**
     * AJAX JSON handlers: never leak HTML/PHP warnings into JSON consumers.
     */
    private function runReturnsJson(callable $body, string $failureJson): void {
        $baseLevel = ob_get_level();
        try {
            $body();
        } catch (\Throwable $e) {
            while (ob_get_level() > $baseLevel) {
                ob_end_clean();
            }
            error_log('[returns-json] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo $failureJson;
            exit;
        }
    }

    private function parsePostedImeis($rawImeis): array {
        if (!is_string($rawImeis) || $rawImeis === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $rawImeis);
        if (!is_array($lines)) {
            return [];
        }
        $out = [];
        foreach ($lines as $line) {
            $imei = ImeiFormat::normalize((string) $line);
            if ($imei !== '') {
                $out[] = $imei;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Accept phone IMEI (13 or 15–18 digits) or tablet serial (8–20 alnum with a letter).
     */
    private function validateScannedImei(string $imei): ?string {
        $imei = ImeiFormat::normalize($imei);
        if ($imei === '') {
            return 'Serial is empty.';
        }
        if (ImeiFormat::isPlausible($imei)) {
            return null;
        }
        return 'Not a phone IMEI (13 or 15–18 digits) or tablet serial (11–20 letters/numbers).';
    }

    /**
     * Always use the logged-in session branch. Posted warehouse_id is ignored when it mismatches.
     */
    private function resolveReturnWarehouseId(string $via = 'post'): ?int {
        $sessionWh = (int) (Auth::warehouseId() ?: 0);
        if ($sessionWh <= 0) {
            return null;
        }

        $posted = $this->inputInt('warehouse_id', 0, $via);
        if ($posted > 0 && $posted !== $sessionWh) {
            return null;
        }

        foreach (self::getWarehouses() as $w) {
            if ((int) ($w['id'] ?? 0) === $sessionWh) {
                return $sessionWh;
            }
        }

        return null;
    }

    /** Persist sale-return form in session so validation errors do not wipe the cashier's work. */
    private function saveReturnDraftFromPost(): void {
        $_SESSION['return_create_draft'] = $this->buildReturnDraftPayloadFromPost();
    }

    /** Persist purchase-return form in session on validation/model errors. */
    private function savePurchaseReturnDraftFromPost(): void {
        $_SESSION['purchase_return_create_draft'] = $this->buildReturnDraftPayloadFromPost();
    }

    /**
     * @return array{party_id:int, ref_id:int, warehouse_id:int, date:string, items:array}
     */
    private function buildReturnDraftPayloadFromPost(): array {
        $rawItems = $_POST['items'] ?? [];
        $items    = [];
        foreach ($rawItems as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $items[] = [
                'item_id'    => $itemId,
                'quantity'   => (int) ($row['quantity'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'imeis'      => (string) ($row['imeis'] ?? ''),
            ];
        }

        return [
            'party_id'     => (int) ($_POST['party_id'] ?? 0),
            'ref_id'       => (int) ($_POST['ref_id'] ?? 0),
            'warehouse_id' => (int) (Auth::warehouseId() ?: 0),
            'date'         => (string) ($_POST['date'] ?? date('Y-m-d')),
            'items'        => $items,
        ];
    }

    private function consumeReturnDraft(): ?array {
        return $this->hydrateReturnDraft(
            $_SESSION['return_create_draft'] ?? null,
            'return_create_draft',
            'sales',
            'invoice_no'
        );
    }

    /** UI preview only — no lock; assigned number may differ if another return saves first. */
    private function previewNextReturnNo(): string {
        $db   = Database::getInstance();
        $last = $db->fetchOne('SELECT return_no FROM returns ORDER BY id DESC LIMIT 1');
        $num  = $last ? (int) substr((string) ($last['return_no'] ?? ''), strlen(RETURN_PREFIX)) : 0;
        return RETURN_PREFIX . str_pad((string) ($num + 1), 6, '0', STR_PAD_LEFT);
    }

    private function consumePurchaseReturnDraft(): ?array {
        return $this->hydrateReturnDraft(
            $_SESSION['purchase_return_create_draft'] ?? null,
            'purchase_return_create_draft',
            'purchases',
            'invoice_no'
        );
    }

    /**
     * @param 'sales'|'purchases' $refTable
     */
    private function hydrateReturnDraft(?array $draft, string $sessionKey, string $refTable, string $refNoColumn): ?array {
        unset($_SESSION[$sessionKey]);
        if (!is_array($draft)) {
            return null;
        }

        $db = Database::getInstance();

        $partyId = (int) ($draft['party_id'] ?? 0);
        if ($partyId > 0) {
            $party = $db->fetchOne(
                'SELECT id, name, phone FROM parties WHERE id = ?',
                [$partyId]
            );
            if ($party) {
                $draft['party'] = $party;
            }
        }

        $refId = (int) ($draft['ref_id'] ?? 0);
        if ($refId > 0 && in_array($refTable, ['sales', 'purchases'], true)) {
            $refRow = $db->fetchOne(
                "SELECT id, {$refNoColumn} AS ref_no FROM {$refTable} WHERE id = ?",
                [$refId]
            );
            if ($refRow) {
                $draft['ref_invoice'] = (string) ($refRow['ref_no'] ?? '');
            }
        }

        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn($r) => (int) ($r['item_id'] ?? 0),
            $draft['items'] ?? []
        ))));
        if (!empty($itemIds)) {
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $rows = $db->fetchAll("SELECT id, name FROM items WHERE id IN ({$ph})", $itemIds);
            $nameMap = [];
            foreach ($rows as $r) {
                $nameMap[(int) $r['id']] = $r['name'];
            }
            foreach ($draft['items'] as &$item) {
                $iid = (int) ($item['item_id'] ?? 0);
                $item['item_name'] = $nameMap[$iid] ?? ('Item #' . $iid);
            }
            unset($item);
        }

        return $draft;
    }

    /**
     * Validate IMEI-tracked return lines before save.
     *
     * @param array<int, array{item_id:int, quantity:int, imeis:array}> $items
     * @return string|null User-facing error, or null when valid
     */
    private function validateReturnLineImeis(array $items, string $flow, ?int $refId = null): ?string {
        $db = Database::getInstance();
        foreach ($items as $item) {
            $itemId    = (int) ($item['item_id'] ?? 0);
            $qty       = (int) ($item['quantity'] ?? 0);
            $imeis     = $item['imeis'] ?? [];
            $imeiCount = is_array($imeis) ? count($imeis) : 0;
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }

            $meta = $db->fetchOne('SELECT name, has_imei FROM items WHERE id = ?', [$itemId]);
            if (!$meta || (int) ($meta['has_imei'] ?? 0) !== 1) {
                continue;
            }

            $name = (string) ($meta['name'] ?? 'item');

            if ($imeiCount > 0 && $imeiCount !== $qty) {
                return "IMEI count ({$imeiCount}) must match quantity ({$qty}) for \"{$name}\".";
            }

            if ($flow === 'sale') {
                if ($imeiCount === 0 && !$refId) {
                    return "Scan {$qty} IMEI(s) for \"{$name}\" or link the original sale invoice.";
                }
                continue;
            }

            // Purchase return: IMEI-tracked lines must be scanned (no silent auto-pick on create).
            if ($imeiCount === 0) {
                return "Scan {$qty} IMEI(s) for \"{$name}\".";
            }
        }

        return null;
    }

    /**
     * @param array<int, array{item_id:int, quantity:int, imeis:array}> $items
     */
    private function returnItemsHaveImeis(array $items): bool {
        foreach ($items as $item) {
            foreach ($item['imeis'] ?? [] as $imei) {
                if (trim((string) $imei) !== '') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array<int, array{item_id:int, quantity:int, imeis:array}> $items
     * @return list<int>
     */
    private function collectSaleIdsFromScannedItems(array $items): array {
        $db      = Database::getInstance();
        $saleIds = [];
        foreach ($items as $item) {
            foreach ($item['imeis'] ?? [] as $imei) {
                $imei = trim((string) $imei);
                if ($imei === '') {
                    continue;
                }
                $row = $db->fetchOne(
                    "SELECT sale_id FROM imei_records WHERE imei = ? AND status = 'sold' LIMIT 1",
                    [$imei]
                );
                if ($row && !empty($row['sale_id'])) {
                    $saleIds[(int) $row['sale_id']] = true;
                }
            }
        }
        return array_map('intval', array_keys($saleIds));
    }

    /**
     * Validate IMEI-scanned sale returns (supports multiple source invoices / original buyers).
     * Credit party is the selected return party — IMEIs may have been sold to another customer.
     *
     * @param array<int, array{item_id:int, quantity:int, imeis:array}> $items
     */
    private function validateScannedSaleReturnImeis(array $items, int $warehouseId): ?string {
        $db     = Database::getInstance();
        $counts = [];

        foreach ($items as $item) {
            foreach ($item['imeis'] ?? [] as $imei) {
                $imei = trim((string) $imei);
                if ($imei === '') {
                    continue;
                }

                $row = $db->fetchOne(
                    "SELECT ir.id AS imei_id, ir.sale_id, ir.item_id, s.status, s.warehouse_id, s.invoice_no
                     FROM imei_records ir
                     JOIN sales s ON s.id = ir.sale_id
                     WHERE ir.imei = ? AND ir.status = 'sold'
                     LIMIT 1",
                    [$imei]
                );
                if (!$row) {
                    return "IMEI {$imei} is not a sold unit. Scan a serial that was sold from this branch.";
                }
                if ((int) $row['warehouse_id'] !== $warehouseId) {
                    return "IMEI {$imei} belongs to another branch.";
                }
                if (($row['status'] ?? '') === 'cancelled') {
                    return 'Cannot return IMEI from cancelled invoice ' . ($row['invoice_no'] ?? '') . '.';
                }

                $saleId = (int) $row['sale_id'];
                $dup    = $this->returnModel->isImeiAlreadyReturned((int) $row['imei_id'], $saleId, $warehouseId);
                if ($dup) {
                    return "IMEI {$imei} was already returned in {$dup['return_no']} on {$dup['date']}.";
                }

                $key          = $saleId . ':' . (int) $row['item_id'];
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        foreach ($counts as $key => $qty) {
            [$saleId, $itemId] = array_map('intval', explode(':', $key, 2));
            $limits            = $this->saleReturnLimitMap($saleId);
            $lim               = $limits[$itemId] ?? null;
            $max               = (int) ($lim['remaining'] ?? 0);
            if ($qty > $max) {
                $name = (string) ($lim['name'] ?? 'item');
                $inv  = $db->fetchOne('SELECT invoice_no FROM sales WHERE id = ?', [$saleId]);
                $invNo = (string) ($inv['invoice_no'] ?? ('invoice #' . $saleId));
                return "Cannot return {$qty} of \"{$name}\" from {$invNo} — only {$max} remaining on that invoice.";
            }
        }

        return null;
    }

    /** Remaining returnable qty (and unique sold price when unambiguous) per item for a linked sale. */
    private function saleReturnLimitMap(int $refId): array {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT si.item_id, i.name,
                    SUM(si.quantity) AS sold_qty,
                    COALESCE(ret.returned_qty, 0) AS already_returned,
                    COUNT(DISTINCT ROUND(si.unit_price, 3)) AS price_count,
                    MIN(si.unit_price) AS unit_price
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             LEFT JOIN (
                 SELECT ri.item_id, SUM(ri.quantity) AS returned_qty
                 FROM return_items ri
                 JOIN returns r ON r.id = ri.return_id
                 WHERE r.ref_id = ? AND r.type = 'sale_return' AND r.status = 'approved'
                   AND NOT EXISTS (
                       SELECT 1
                       FROM return_items ri_rs
                       INNER JOIN return_item_imei rii_rs ON rii_rs.return_item_id = ri_rs.id
                       INNER JOIN sale_item_imei sii_rs ON sii_rs.imei_id = rii_rs.imei_id
                       INNER JOIN sale_items si_rs ON si_rs.id = sii_rs.sale_item_id
                       INNER JOIN sales s_rs ON s_rs.id = si_rs.sale_id
                       INNER JOIN sales orig_rs ON orig_rs.id = r.ref_id
                       WHERE ri_rs.return_id = r.id
                         AND s_rs.id != orig_rs.id
                         AND s_rs.status != 'cancelled'
                         AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 23:59:59'))
                             > COALESCE(orig_rs.created_at, CONCAT(orig_rs.date,' 00:00:00'))
                         AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 00:00:00'))
                             <= COALESCE(r.created_at, CONCAT(r.date,' 23:59:59'))
                   )
                 GROUP BY ri.item_id
             ) ret ON ret.item_id = si.item_id
             WHERE si.sale_id = ?
             GROUP BY si.item_id, i.name",
            [$refId, $refId]
        );
        $out = [];
        foreach ($rows as $row) {
            $remaining = (int) $row['sold_qty'] - (int) $row['already_returned'];
            $priceCount = (int) ($row['price_count'] ?? 0);
            $out[(int) $row['item_id']] = [
                'name'       => (string) $row['name'],
                'remaining'  => max(0, $remaining),
                // Unique sold price only — never catalog. Null when mixed prices on invoice.
                'unit_price' => $priceCount === 1
                    ? number_format((float) $row['unit_price'], 3, '.', '')
                    : null,
            ];
        }
        return $out;
    }

    private function redirectReturnCreateWithDraft(string $flashType, string $message): void {
        $this->saveReturnDraftFromPost();
        $this->flash($flashType, $message);
        $this->redirect('?page=returns&action=create');
    }

    private function redirectPurchaseReturnCreateWithDraft(string $flashType, string $message): void {
        $this->savePurchaseReturnDraftFromPost();
        $this->flash($flashType, $message);
        $this->redirect('?page=returns&action=purchaseCreate');
    }

    public function index(): void {
        $this->runReturnsHtml(function (): void {
            Auth::authorize('returns', 'view');
            $filters = [
                'from_date' => $this->input('from_date', date('Y-m-01'), 'get'),
                'to_date'   => $this->input('to_date', date('Y-m-d'), 'get'),
                'type'      => $this->input('type', '', 'get'),
                'party_id'  => $this->inputInt('party_id', 0, 'get') ?: null,
            ];
            $returns   = $this->returnModel->getAll($filters);
            $filterParty = null;
            $partyId = (int) ($filters['party_id'] ?? 0);
            if ($partyId > 0) {
                $partyRow = $this->partyModel->find($partyId);
                if ($partyRow) {
                    $filterParty = [
                        'id'   => (int) $partyRow['id'],
                        'name' => (string) ($partyRow['name'] ?? ''),
                    ];
                }
            }
            $pageTitle = 'Returns';
            $page      = 'returns';

            ob_start();
            include __DIR__ . '/../views/returns/index.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        });
    }

    public function create(): void {
        $this->runReturnsHtml(function (): void {
            Auth::authorize('returns', 'add');
            $returnDraft = $this->consumeReturnDraft();
            $pageTitle  = 'New Return';
            $page       = 'returns';
            $skipListAssets = true;
            $nextReturnNo = $this->previewNextReturnNo();

            // One-time token to prevent double-submit duplicate returns
            $_SESSION['return_form_nonce'] = bin2hex(random_bytes(16));
            $returnFormNonce               = $_SESSION['return_form_nonce'];

            ob_start();
            include __DIR__ . '/../views/returns/create.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        });
    }

    public function store(): void {
        try {
            Auth::authorize('returns', 'add');

            if (!$this->isPost()) {
                $this->redirect('?page=returns&action=create');
                return;
            }

            $postedNonce = isset($_POST['return_form_nonce']) ? trim((string)$_POST['return_form_nonce']) : '';
            $sessNonce   = $_SESSION['return_form_nonce'] ?? '';
            if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
                $this->flash('warning', 'This return form was already submitted or expired. Please check Returns list before trying again.');
                $this->redirect('?page=returns');
                return;
            }
            unset($_SESSION['return_form_nonce']);

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
                $this->redirectReturnCreateWithDraft('error', 'Add at least one item.');
                return;
            }

            $warehouseId = $this->resolveReturnWarehouseId('post');
            if ($warehouseId === null) {
                $postedWh = $this->inputInt('warehouse_id');
                $sessionWh = (int) Auth::warehouseId();
                $msg = ($postedWh > 0 && $sessionWh > 0 && $postedWh !== $sessionWh)
                    ? 'Warehouse mismatch. Return must use the active branch.'
                    : 'Select a valid branch for this return.';
                $this->redirectReturnCreateWithDraft('error', $msg);
                return;
            }

            $refId   = $this->inputInt('ref_id') ?: null;
            $partyId = $this->inputInt('party_id');
            $db      = Database::getInstance();
            $hasScannedImeis = $this->returnItemsHaveImeis($items);

            if ($hasScannedImeis) {
                if ($partyId <= 0) {
                    $this->redirectReturnCreateWithDraft('error', 'Please select a customer.');
                    return;
                }
                $scanErr = $this->validateScannedSaleReturnImeis($items, $warehouseId);
                if ($scanErr !== null) {
                    $this->redirectReturnCreateWithDraft('error', $scanErr);
                    return;
                }
                $saleIds = $this->collectSaleIdsFromScannedItems($items);
                // Header ref_id links one invoice for reporting; null when units span multiple sales.
                $refId = count($saleIds) === 1 ? (int) $saleIds[0] : null;
            } elseif ($refId) {
                // Bulk return against one invoice (no IMEI scan).
                $sale = $db->fetchOne(
                    "SELECT id, party_id, status, warehouse_id FROM sales WHERE id = ? AND warehouse_id = ?",
                    [$refId, $warehouseId]
                );
                if (!$sale) {
                    $this->redirectReturnCreateWithDraft('error', 'Selected invoice was not found in the selected branch.');
                    return;
                }
                if ($sale['status'] === 'cancelled') {
                    $this->redirectReturnCreateWithDraft('error', 'Cannot post a return against a cancelled invoice.');
                    return;
                }
                $partyId = (int) $sale['party_id'];
            } elseif ($partyId <= 0) {
                $this->redirectReturnCreateWithDraft('error', 'Please select a customer.');
                return;
            }

            $imeiErr = $this->validateReturnLineImeis($items, 'sale', $refId);
            if ($imeiErr !== null) {
                $this->redirectReturnCreateWithDraft('error', $imeiErr);
                return;
            }

            $result = $this->returnModel->create([
                'ref_id'       => $refId,
                'party_id'     => $partyId,
                'warehouse_id' => $warehouseId,
                'date'         => $this->input('date'),
                'reason'       => $this->input('reason'),
                'items'        => $items,
            ]);

            if ($result['success']) {
                self::clearDashboardCache($warehouseId);
                $this->logActivity('create_return', 'returns', $result['id'], $result['return_no']);
                $this->flash('success', "Return {$result['return_no']} saved.");
                if ($this->input('print_mode') === '1') {
                    $tpl = Auth::printTemplate();
                    if ($tpl === 'thermal') {
                        $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1&thermal=1');
                        return;
                    }
                    $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1');
                    return;
                }
                if ($this->input('print_mode') === '2') {
                    $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1&thermal=1');
                    return;
                }
                $this->redirect('?page=returns');
                return;
            } else {
                $this->redirectReturnCreateWithDraft('error', $result['error']);
                return;
            }
        } catch (\Throwable $e) {
            error_log('[returns-store] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->redirectReturnCreateWithDraft('error', 'Could not save the return. Please try again.');
        }
    }

    /** AJAX: remaining returnable qty per item for a linked sale (pre-submit validation). */
    public function saleReturnLimits(): void {
        $failJson = '{"limits":{}}';
        $this->runReturnsJson(function () use ($failJson): void {
            Auth::authorize('returns', 'add');
            header('Content-Type: application/json');
            $refId = $this->inputInt('ref_id', 0, 'get');
            if ($refId <= 0) {
                echo json_encode(['limits' => []]);
                return;
            }
            $warehouseId = $this->resolveReturnWarehouseId('get');
            if ($warehouseId === null) {
                echo json_encode(['limits' => [], 'message' => 'Select a valid branch first.']);
                return;
            }
            $sale = Database::getInstance()->fetchOne(
                'SELECT id FROM sales WHERE id = ? AND warehouse_id = ? AND status != ?',
                [$refId, $warehouseId, 'cancelled']
            );
            if (!$sale) {
                echo json_encode(['limits' => [], 'message' => 'Invoice not found in this branch.']);
                return;
            }
            echo json_encode(['limits' => $this->saleReturnLimitMap($refId)]);
        }, $failJson);
    }

    public function purchaseCreate(): void {
        $this->runReturnsHtml(function (): void {
            Auth::authorize('returns', 'add');
            $warehouses = self::getWarehouses();
            $pageTitle  = 'New Purchase Return';
            $page       = 'returns';
            $skipListAssets = true;
            $nextReturnNo = $this->previewNextReturnNo();

            $purchaseReturnDraft = $this->consumePurchaseReturnDraft();

            $prefillPurchase = null;
            $prefillId       = $this->inputInt('ref_id', 0, 'get');
            $sessionWarehouseId = (int) (Auth::warehouseId() ?: 0);
            if (!$purchaseReturnDraft && $prefillId > 0 && $sessionWarehouseId > 0) {
                $prefillPurchase = Database::getInstance()->fetchOne(
                    "SELECT p.id, p.invoice_no, p.party_id, par.name as party_name
                     FROM purchases p
                     JOIN parties par ON par.id = p.party_id
                     WHERE p.id = ? AND p.warehouse_id = ? AND p.status != 'cancelled'",
                    [$prefillId, $sessionWarehouseId]
                );
            }

            $_SESSION['return_form_nonce'] = bin2hex(random_bytes(16));
            $returnFormNonce               = $_SESSION['return_form_nonce'];

            ob_start();
            include __DIR__ . '/../views/returns/purchase_create.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        });
    }

    public function storePurchase(): void {
        try {
            Auth::authorize('returns', 'add');

            if (!$this->isPost()) {
                $this->redirect('?page=returns&action=purchaseCreate');
                return;
            }

            $postedNonce = isset($_POST['return_form_nonce']) ? trim((string) $_POST['return_form_nonce']) : '';
            $sessNonce   = $_SESSION['return_form_nonce'] ?? '';
            if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
                $this->flash('warning', 'This form was already submitted or expired. Check the returns list before trying again.');
                $this->redirect('?page=returns');
                return;
            }
            unset($_SESSION['return_form_nonce']);

            $warehouseId = $this->resolveReturnWarehouseId('post');
            if ($warehouseId === null) {
                $this->redirectPurchaseReturnCreateWithDraft('error', 'Select a valid branch for this return.');
                return;
            }

            $refId = $this->inputInt('ref_id');
            if ($refId <= 0) {
                $this->redirectPurchaseReturnCreateWithDraft('error', 'Please link the original purchase invoice.');
                return;
            }

            $rawItems = $_POST['items'] ?? [];
            $items    = [];
            foreach ($rawItems as $row) {
                if (empty($row['item_id']) || empty($row['quantity'])) {
                    continue;
                }
                $imeis = [];
                if (!empty($row['imeis'])) {
                    $imeis = array_filter(array_map('trim', explode("\n", (string) $row['imeis'])));
                }
                $items[] = [
                    'item_id'    => (int) $row['item_id'],
                    'quantity'   => (int) $row['quantity'],
                    'unit_price' => (float) $row['unit_price'],
                    'imeis'      => $imeis,
                ];
            }
            if (empty($items)) {
                $this->redirectPurchaseReturnCreateWithDraft('error', 'Add at least one item.');
                return;
            }

            $db       = Database::getInstance();
            $purchase = $db->fetchOne(
                "SELECT id, party_id, status, warehouse_id FROM purchases WHERE id = ? AND warehouse_id = ?",
                [$refId, $warehouseId]
            );
            if (!$purchase) {
                $this->redirectPurchaseReturnCreateWithDraft('error', 'Selected purchase was not found in the selected branch.');
                return;
            }
            if (($purchase['status'] ?? '') === 'cancelled') {
                $this->redirectPurchaseReturnCreateWithDraft('error', 'Cannot return goods on a cancelled purchase.');
                return;
            }

            $imeiErr = $this->validateReturnLineImeis($items, 'purchase', $refId);
            if ($imeiErr !== null) {
                $this->redirectPurchaseReturnCreateWithDraft('error', $imeiErr);
                return;
            }

            $result = $this->returnModel->createPurchaseReturn([
                'ref_id'       => $refId,
                'party_id'     => (int) $purchase['party_id'],
                'warehouse_id' => (int) $purchase['warehouse_id'],
                'date'         => $this->input('date'),
                'reason'       => $this->input('reason'),
                'items'        => $items,
            ]);

            if ($result['success']) {
                self::clearDashboardCache($warehouseId);
                $this->logActivity('create_purchase_return', 'returns', $result['id'], $result['return_no']);
                $this->flash('success', "Purchase return {$result['return_no']} saved.");
                if ($this->input('print_mode') === '1' || $this->input('print_mode') === '2') {
                    $thermal = $this->input('print_mode') === '2' ? '&thermal=1' : '';
                    $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1' . $thermal);
                    return;
                }
                $this->redirect('?page=returns&action=detail&id=' . $result['id']);
                return;
            }

            $this->redirectPurchaseReturnCreateWithDraft('error', $result['error']);
            return;
        } catch (\Throwable $e) {
            error_log('[returns-store-purchase] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->redirectPurchaseReturnCreateWithDraft('error', 'Could not save the purchase return. Please try again.');
        }
    }

    public function searchPurchases(): void {
        $this->runReturnsJson(function (): void {
            Auth::authorize('returns', 'add');
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 1) {
                echo json_encode([]);
                return;
            }
            $warehouseId = $this->resolveReturnWarehouseId('get');
            if ($warehouseId === null) {
                echo json_encode([]);
                return;
            }
            $db   = Database::getInstance();
            $like = "%$q%";
            $rows = $db->fetchAll(
                "SELECT p.id, p.party_id, p.invoice_no, par.name as party_name, p.grand_total, p.date
                 FROM purchases p
                 JOIN parties par ON par.id = p.party_id
                 WHERE p.status != 'cancelled'
                   AND p.warehouse_id = ?
                   AND (p.invoice_no LIKE ? OR par.name LIKE ?)
                 ORDER BY p.date DESC, p.id DESC
                 LIMIT 15",
                [$warehouseId, $like, $like]
            );
            echo json_encode($rows);
        }, '[]');
    }

    public function lookupImeiPurchase(): void {
        $failJson = '{"found":false,"accepted":false,"message":"Something went wrong. Please try again."}';
        $this->runReturnsJson(function () use ($failJson): void {
            Auth::authorize('returns', 'add');
            header('Content-Type: application/json');
            $imei        = ImeiFormat::normalize(trim($_GET['imei'] ?? ''));
            $purchaseId  = $this->inputInt('purchase_id', 0, 'get');

            $imeiError = $this->validateScannedImei($imei);
            if ($imeiError !== null) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => $imeiError]);
                return;
            }

            Item::ensureSerialKindColumn();
            $warehouseId = $this->resolveReturnWarehouseId('get');
            if ($warehouseId === null) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => 'Select a valid branch first.']);
                return;
            }

            $db  = Database::getInstance();
            $sql = "SELECT ir.id as imei_id, ir.imei, ir.status, ir.item_id, ir.purchase_id, ir.sale_id, ir.warehouse_id,
                           i.name as item_name, i.sku, i.purchase_price,
                           COALESCE(i.serial_kind, 'phone') AS serial_kind,
                           p.invoice_no as purchase_invoice, p.party_id as purchase_party_id, par.name as party_name,
                           pi.unit_price as historical_price
                    FROM imei_records ir
                    JOIN items i ON i.id = ir.item_id
                    LEFT JOIN purchases p ON p.id = ir.purchase_id
                    LEFT JOIN parties par ON par.id = p.party_id
                    LEFT JOIN purchase_items pi ON pi.purchase_id = ir.purchase_id AND pi.item_id = ir.item_id
                    WHERE ir.imei = ? AND ir.warehouse_id = ?";
            $row = $db->fetchOne($sql . ' LIMIT 1', [$imei, $warehouseId]);

            if (!$row) {
                echo json_encode([
                    'found'    => false,
                    'accepted' => false,
                    'message'  => 'IMEI not in stock at this branch — cannot return to supplier.',
                ]);
                return;
            }

            $rowPurchaseId = (int) ($row['purchase_id'] ?? 0);
            if ($purchaseId > 0 && $rowPurchaseId > 0 && $purchaseId !== $rowPurchaseId) {
                $linkedInv = $db->fetchOne('SELECT invoice_no FROM purchases WHERE id = ?', [$purchaseId]);
                $linkedNo  = (string) ($linkedInv['invoice_no'] ?? ('purchase #' . $purchaseId));
                $fromNo    = (string) ($row['purchase_invoice'] ?? ('purchase #' . $rowPurchaseId));
                echo json_encode([
                    'found'    => true,
                    'accepted' => false,
                    'message'  => "This IMEI is from {$fromNo} but this return is linked to {$linkedNo}. Scan units from one purchase only.",
                ]);
                return;
            }

            if (!in_array($row['status'], ['in_stock', 'returned'], true)) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => 'IMEI is not in stock (status: ' . $row['status'] . ').']);
                return;
            }
            if (!empty($row['sale_id'])) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => 'IMEI has already been sold.']);
                return;
            }
            if (empty($row['purchase_id'])) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => 'IMEI is not linked to a purchase invoice.']);
                return;
            }

            $alreadyReturned = $this->returnModel->isImeiAlreadyReturnedToSupplier(
                (int) $row['imei_id'],
                $rowPurchaseId > 0 ? $rowPurchaseId : null,
                $warehouseId > 0 ? $warehouseId : null
            );
            if ($alreadyReturned) {
                echo json_encode([
                    'found'    => true,
                    'accepted' => false,
                    'message'  => 'IMEI already returned in ' . $alreadyReturned['return_no'] .
                        ' on ' . $alreadyReturned['date'] . '.',
                ]);
                return;
            }

            $unitPrice = isset($row['historical_price']) ? $row['historical_price'] : $row['purchase_price'];
            echo json_encode([
                'found'             => true,
                'accepted'          => true,
                'item_id'           => (int) $row['item_id'],
                'item_name'         => $row['item_name'],
                'serial_kind'       => $row['serial_kind'] ?? 'phone',
                'sku'               => $row['sku'] ?? '',
                'unit_price'        => number_format((float) $unitPrice, 3, '.', ''),
                'imei'              => $row['imei'],
                'purchase_id'       => (int) $row['purchase_id'],
                'party_id'          => (int) ($row['purchase_party_id'] ?? 0),
                'party_name'        => $row['party_name'] ?? '',
                'purchase_invoice'  => $row['purchase_invoice'] ?? '',
                'message'           => 'Found: ' . $row['item_name'] . ($row['purchase_invoice'] ? ' (from ' . $row['purchase_invoice'] . ')' : ''),
            ]);
        }, $failJson);
    }

    // AJAX: search sale invoices for ref lookup
    public function searchSales(): void {
        $this->runReturnsJson(function (): void {
            Auth::authorize('returns', 'add');
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 1) { echo json_encode([]); return; }
            $warehouseId = $this->resolveReturnWarehouseId('get');
            if ($warehouseId === null) {
                echo json_encode([]);
                return;
            }
            $db   = Database::getInstance();
            $like = "%$q%";
            $rows = $db->fetchAll(
                "SELECT s.id, s.party_id, s.invoice_no, p.name as party_name, s.grand_total, s.date
                 FROM sales s
                 JOIN parties p ON p.id = s.party_id
                 WHERE s.status != 'cancelled'
                   AND s.warehouse_id = ?
                   AND (s.invoice_no LIKE ? OR p.name LIKE ?)
                 ORDER BY s.date DESC LIMIT 15",
                [$warehouseId, $like, $like]
            );
            echo json_encode($rows);
        }, '[]');
    }

    // AJAX: Lookup IMEI — returns item info with ORIGINAL sold unit price (never catalog)
    public function lookupImei(): void {
        $failJson = '{"found":false,"accepted":false,"message":"Something went wrong. Please try again."}';
        $this->runReturnsJson(function (): void {
            Auth::authorize('returns', 'add');
            header('Content-Type: application/json');
            $imei = ImeiFormat::normalize(trim($_GET['imei'] ?? ''));

            $imeiError = $this->validateScannedImei($imei);
            if ($imeiError !== null) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => $imeiError]);
                return;
            }

            Item::ensureSerialKindColumn();
            $warehouseId = $this->resolveReturnWarehouseId('get');
            if ($warehouseId === null) {
                echo json_encode(['found' => false, 'accepted' => false, 'message' => 'Select a valid branch first.']);
                return;
            }

            $db = Database::getInstance();

            $row = $db->fetchOne(
                "SELECT ir.id as imei_id, ir.imei, ir.status, ir.item_id, ir.sale_id, ir.warehouse_id as imei_warehouse_id,
                        i.name as item_name, i.sku, i.has_imei,
                        COALESCE(i.serial_kind, 'phone') AS serial_kind,
                        s.invoice_no as sold_invoice, s.party_id as sale_party_id, s.warehouse_id as sale_warehouse_id,
                        p.name as party_name
                 FROM imei_records ir
                 JOIN items i ON i.id = ir.item_id
                 LEFT JOIN sales s ON s.id = ir.sale_id
                 LEFT JOIN parties p ON p.id = s.party_id
                 WHERE ir.imei = ?
                   AND (ir.warehouse_id = ? OR s.warehouse_id = ?)
                 LIMIT 1",
                [$imei, $warehouseId, $warehouseId]
            );

            // IMEI NOT in system — still accept it, but sold price requires invoice link later
            if (!$row) {
                echo json_encode([
                    'found'    => false,
                    'accepted' => true,
                    'imei'     => $imei,
                    'message'  => 'IMEI not in system — select item and link the original invoice for sold price.',
                ]);
                return;
            }

            if ($row['status'] !== 'sold') {
                $statusMsg = match ($row['status']) {
                    'in_stock', 'returned' => 'IMEI is already in stock — it may have been returned already.',
                    'transferred'          => 'IMEI was returned to supplier and cannot be received as a sale return.',
                    'dumped'               => 'IMEI was dumped (party credited). Void the dump first, or do not use a sale return.',
                    'defective'            => 'IMEI is on a warranty replacement and cannot be sale-returned.',
                    default                => 'IMEI is not currently sold and cannot be returned.',
                };
                echo json_encode(['found' => false, 'accepted' => false, 'message' => $statusMsg]);
                return;
            }

            $saleId  = !empty($row['sale_id']) ? (int) $row['sale_id'] : null;
            $partyId = !empty($row['sale_party_id']) ? (int) $row['sale_party_id'] : null;

            if ($saleId) {
                $alreadyReturned = $this->returnModel->isImeiAlreadyReturned(
                    (int) $row['imei_id'],
                    $saleId,
                    $warehouseId > 0 ? $warehouseId : null
                );
                if ($alreadyReturned) {
                    echo json_encode([
                        'found'    => true,
                        'accepted' => false,
                        'message'  => 'IMEI already returned in ' . $alreadyReturned['return_no'] .
                            ' on ' . $alreadyReturned['date'] . '.',
                    ]);
                    return;
                }
            }

            try {
                $resolved = $this->returnModel->resolveSaleReturnUnitPrice(
                    (int) $row['item_id'],
                    [$imei],
                    $saleId,
                    null
                );
            } catch (\Exception $e) {
                echo json_encode([
                    'found'    => true,
                    'accepted' => false,
                    'message'  => $e->getMessage(),
                ]);
                return;
            }

            $unitPrice = number_format($resolved, 3, '.', '');
            echo json_encode([
                'found'        => true,
                'accepted'     => true,
                'item_id'      => (int) $row['item_id'],
                'item_name'    => $row['item_name'],
                'serial_kind'  => $row['serial_kind'] ?? 'phone',
                'sku'          => $row['sku'] ?? '',
                'sale_price'   => $unitPrice, // original sold price (field name kept for UI compat)
                'unit_price'   => $unitPrice,
                'price_locked' => true,
                'has_imei'     => (bool) $row['has_imei'],
                'imei'         => $row['imei'],
                'status'       => $row['status'],
                'sold_invoice' => $row['sold_invoice'] ?? '',
                'sale_id'      => $saleId,
                'party_id'     => $partyId,
                'party_name'   => $row['party_name'] ?? '',
                'message'      => "Found: {$row['item_name']}" . ($row['sold_invoice'] ? " (from {$row['sold_invoice']})" : ''),
            ]);
        }, $failJson);
    }

    public function print(): void {
        $this->runReturnsHtml(function (): void {
            Auth::authorize('returns', 'view');
            $id     = $this->inputInt('id', 0, 'get');
            $return = $this->returnModel->findFull($id);
            if (!$return) {
                if (!headers_sent()) {
                    http_response_code(404);
                }
                $returnsErrorTitle = 'Return not found';
                $returnsErrorMsg   = 'This return does not exist or you do not have access.';
                include __DIR__ . '/../views/returns/module_error.php';
                return;
            }

            $db       = Database::getInstance();
            $settings = self::getSettings();

            // Party balance for print
            $partyBalance    = $this->partyModel->findWithBalance((int) $return['party_id']);
            $currentBalance  = (float) ($partyBalance['net_balance'] ?? 0);
            $returnAmount    = (float) $return['grand_total'];
            if (($return['type'] ?? '') === 'purchase_return') {
                $previousBalance = $currentBalance - $returnAmount;
            } else {
                $previousBalance = $currentBalance + $returnAmount;
            }

            // A5 vs thermal: explicit query wins; otherwise use session default from layout "Default Print".
            $tplParam = strtolower(trim((string)($_GET['template'] ?? '')));
            if ($tplParam === 'a5') {
                $returnPrintThermal = false;
            } elseif (isset($_GET['thermal'])) {
                $returnPrintThermal = true;
            } else {
                $returnPrintThermal = (Auth::printTemplate() === 'thermal');
            }

            include __DIR__ . '/../views/returns/print.php';
        });
    }

    public function detail(): void {
        $this->runReturnsHtml(function (): void {
            Auth::authorize('returns', 'view');
            $id     = $this->inputInt('id', 0, 'get');
            $return = $this->returnModel->findFull($id);
            if (!$return) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }

            $pageTitle = 'Return: ' . $return['return_no'];
            $page      = 'returns';

            ob_start();
            include __DIR__ . '/../views/returns/view.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        });
    }

    public function cancel(): void {
        try {
            Auth::authorize('returns', 'delete');

            if (!$this->isPost()) {
                $this->flash('error', 'Invalid request method.');
                $this->redirect('?page=returns');
                return;
            }

            $id = $this->inputInt('id');
            if ($id <= 0) {
                $this->flash('error', 'Return not found.');
                $this->redirect('?page=returns');
                return;
            }

            $return = $this->returnModel->findFull($id);
            if (!$return) {
                $this->flash('error', 'Return not found.');
                $this->redirect('?page=returns');
                return;
            }

            $result = $this->returnModel->cancel($id);
            if ($result['success']) {
                self::clearDashboardCache((int) ($return['warehouse_id'] ?? 0));
                $this->logActivity('cancel_return', 'returns', $id, $return['return_no'] ?? null);
                $this->flash('success', 'Return ' . ($return['return_no'] ?? '') . ' voided.');
                $this->redirect('?page=returns&action=detail&id=' . $id);
                return;
            }

            $this->flash('error', $result['error'] ?? 'Could not void this return.');
            $this->redirect('?page=returns&action=detail&id=' . $id);
        } catch (\Throwable $e) {
            error_log('[returns-cancel] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->flash('error', 'Could not void this return. Please try again.');
            $this->redirect('?page=returns');
        }
    }

    public function edit(): void {
        $this->runReturnsHtml(function (): void {
            if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=returns'); return; }

            $id = $this->inputInt('id', 0, 'get');
            $editReturn = $this->returnModel->findFull($id);
            if (!$editReturn) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }
            if (($editReturn['status'] ?? '') === 'cancelled') {
                $this->flash('error', 'Voided returns cannot be edited.');
                $this->redirect('?page=returns&action=detail&id=' . $id);
                return;
            }
            if (($editReturn['type'] ?? '') === 'purchase_return') {
                $this->flash('error', 'Purchase returns cannot be edited here yet. Void the return if it was posted in error.');
                $this->redirect('?page=returns&action=detail&id=' . $id);
                return;
            }
            if (!isset($_SESSION['return_edit_nonce']) || !is_array($_SESSION['return_edit_nonce'])) {
                $_SESSION['return_edit_nonce'] = [];
            }
            $_SESSION['return_edit_nonce'][$id] = bin2hex(random_bytes(16));
            $returnEditNonce = $_SESSION['return_edit_nonce'][$id];

            $pageTitle = 'Edit Return: ' . $editReturn['return_no'];
            $page      = 'returns';

            ob_start();
            include __DIR__ . '/../views/returns/edit.php';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        });
    }

    public function update(): void {
        try {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=returns'); return; }
        if (!$this->isPost()) { $this->redirect('?page=returns'); return; }

        $id = $this->inputInt('id');
        $db = Database::getInstance();
        $postedEditNonce = isset($_POST['return_edit_nonce']) ? trim((string)$_POST['return_edit_nonce']) : '';
        $sessionEditNonce = $_SESSION['return_edit_nonce'][$id] ?? '';
        if ($sessionEditNonce === '' || !hash_equals((string)$sessionEditNonce, $postedEditNonce)) {
            $this->flash('warning', 'Edit form expired. Please reopen the return and try again.');
            $this->redirect("?page=returns&action=edit&id={$id}");
            return;
        }

        $return = $this->returnModel->findFull($id);
        if (!$return) { $this->flash('error', 'Return not found.'); $this->redirect('?page=returns'); }
        if (($return['status'] ?? '') === 'cancelled') {
            $this->flash('error', 'Voided returns cannot be edited.');
            $this->redirect('?page=returns&action=detail&id=' . $id);
            return;
        }
        if (($return['type'] ?? '') === 'purchase_return') {
            $this->flash('error', 'Purchase returns cannot be edited here yet.');
            $this->redirect('?page=returns&action=detail&id=' . $id);
            return;
        }

        $newDate   = $this->input('date') ?: $return['date'];
        $newReason = $this->input('reason');
        $warehouseId = (int)$return['warehouse_id'];

        $rawItems    = $_POST['items'] ?? [];

        if (!empty($return['ref_id'])) {
            $saleId = (int)$return['ref_id'];
            $saleItems = $db->fetchAll(
                "SELECT si.item_id, SUM(si.quantity) as sold_qty,
                        COALESCE(ret.returned_qty, 0) as already_returned
                 FROM sale_items si
                 LEFT JOIN (
                     SELECT ri.item_id, SUM(ri.quantity) as returned_qty
                     FROM return_items ri
                     JOIN returns r ON r.id = ri.return_id
                     WHERE r.ref_id = ? AND r.status = 'approved' AND r.id != ?
                       AND NOT EXISTS (
                           SELECT 1
                           FROM return_items ri_rs
                           INNER JOIN return_item_imei rii_rs ON rii_rs.return_item_id = ri_rs.id
                           INNER JOIN sale_item_imei sii_rs ON sii_rs.imei_id = rii_rs.imei_id
                           INNER JOIN sale_items si_rs ON si_rs.id = sii_rs.sale_item_id
                           INNER JOIN sales s_rs ON s_rs.id = si_rs.sale_id
                           INNER JOIN sales orig_rs ON orig_rs.id = r.ref_id
                           WHERE ri_rs.return_id = r.id
                             AND s_rs.id != orig_rs.id
                             AND s_rs.status != 'cancelled'
                             AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 23:59:59'))
                                 > COALESCE(orig_rs.created_at, CONCAT(orig_rs.date,' 00:00:00'))
                             AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 00:00:00'))
                                 <= COALESCE(r.created_at, CONCAT(r.date,' 23:59:59'))
                       )
                     GROUP BY ri.item_id
                 ) ret ON ret.item_id = si.item_id
                 WHERE si.sale_id = ?
                 GROUP BY si.item_id",
                [$saleId, $id, $saleId]
            );
            $saleLimits = [];
            foreach ($saleItems as $si) {
                $saleLimits[(int)$si['item_id']] = (int)$si['sold_qty'] - (int)$si['already_returned'];
            }

            // Check quantities: gather total new quantities per item in this return edit
            $itemTotals = [];
            if (!empty($rawItems)) {
                $existingReturnItems = $db->fetchAll("SELECT id, item_id FROM return_items WHERE return_id = ?", [$id]);
                $existingItemMap = [];
                foreach ($existingReturnItems as $eri) {
                    $existingItemMap[(int)$eri['id']] = (int)$eri['item_id'];
                }
                foreach ($rawItems as $retItemId => $row) {
                    if (!empty($row['deleted'])) continue;
                    $itemId = $existingItemMap[(int)$retItemId] ?? 0;
                    if (!$itemId) continue;
                    $qty = max(1, (int)($row['quantity'] ?? 1));
                    $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0) + $qty;
                }
            }
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId = (int)($row['item_id'] ?? 0);
                if (!$itemId) continue;
                $qty = max(1, (int)($row['quantity'] ?? 1));
                $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0) + $qty;
            }

            foreach ($itemTotals as $itemId => $qty) {
                $maxAllowed = $saleLimits[$itemId] ?? 0;
                if ($qty > $maxAllowed) {
                    $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
                    $this->flash('error', "Cannot return {$qty} of \"{$itemName['name']}\" — only {$maxAllowed} available to return from this sale.");
                    $this->redirect("?page=returns&action=edit&id={$id}");
                    return;
                }
            }
        }

        $db->beginTransaction();
        try {
            if (!empty($rawItems)) {
                foreach ($rawItems as $retItemId => $row) {
                    $retItemId = (int)$retItemId;

                    $oldItem = $db->fetchOne(
                        "SELECT ri.item_id, ri.quantity, ri.unit_price, ri.total, i.has_imei
                         FROM return_items ri
                         JOIN items i ON i.id = ri.item_id
                         WHERE ri.id = ? AND ri.return_id = ?",
                        [$retItemId, $id]
                    );
                    if (!$oldItem) continue;
                    $imeiLinkCountRow = $db->fetchOne(
                        "SELECT COUNT(*) as c FROM return_item_imei WHERE return_item_id = ?",
                        [$retItemId]
                    );
                    $imeiLinkCount = (int)($imeiLinkCountRow['c'] ?? 0);

                    // Lock stock row for the duration of this item's update
                    $db->fetchOne(
                        "SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
                        [(int)$oldItem['item_id'], $warehouseId]
                    );

                    // Handle deletion — remove item and reverse its stock effect
                    if (!empty($row['deleted'])) {
                        if ($imeiLinkCount > 0) {
                            $imeiRows = $db->fetchAll(
                                "SELECT ir.id, ir.imei
                                 FROM return_item_imei rii
                                 JOIN imei_records ir ON ir.id = rii.imei_id
                                 WHERE rii.return_item_id = ?",
                                [$retItemId]
                            );
                            foreach ($imeiRows as $imeiRow) {
                                $imeiAff = $db->execute(
                                    "UPDATE imei_records
                                     SET status = 'sold', sale_id = ?, warehouse_id = ?
                                     WHERE id = ? AND status IN ('in_stock','returned')",
                                    [!empty($return['ref_id']) ? (int)$return['ref_id'] : null, $warehouseId, (int)$imeiRow['id']]
                                );
                                if ($imeiAff === 0) {
                                    throw new Exception("Unable to restore IMEI {$imeiRow['imei']} to sold state.");
                                }
                            }
                            $db->execute("DELETE FROM return_item_imei WHERE return_item_id = ?", [$retItemId]);
                        }
                        // A returned item added stock when approved — removing it means deducting stock back
                        $affected = $db->execute(
                            "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [(int)$oldItem['quantity'], $oldItem['item_id'], $warehouseId, (int)$oldItem['quantity']]
                        );
                        if ($affected === 0) {
                            throw new Exception("Cannot remove returned item — stock would go negative for item {$oldItem['item_id']}.");
                        }
                        $db->execute("DELETE FROM return_items WHERE id = ?", [$retItemId]);
                        continue;
                    }

                    $newQty   = max(1, (int)($row['quantity'] ?? 1));
                    $oldQty   = (int)$oldItem['quantity'];
                    $qtyDiff  = $newQty - $oldQty;
                    $isImeiItem = ((int)($oldItem['has_imei'] ?? 0) === 1);
                    $postedImeis = $this->parsePostedImeis($row['imeis'] ?? '');

                    // Always use original sold price — ignore posted unit_price.
                    $imeisForPrice = $postedImeis;
                    if (empty($imeisForPrice) && $imeiLinkCount > 0) {
                        $linkedImeis = $db->fetchAll(
                            "SELECT ir.imei
                             FROM return_item_imei rii
                             JOIN imei_records ir ON ir.id = rii.imei_id
                             WHERE rii.return_item_id = ?",
                            [$retItemId]
                        );
                        foreach ($linkedImeis as $li) {
                            $t = trim((string) ($li['imei'] ?? ''));
                            if ($t !== '') {
                                $imeisForPrice[] = $t;
                            }
                        }
                    }
                    $refSaleIdForPrice = !empty($return['ref_id']) ? (int) $return['ref_id'] : null;
                    $newPrice = $this->returnModel->resolveSaleReturnUnitPrice(
                        (int) $oldItem['item_id'],
                        $imeisForPrice,
                        $refSaleIdForPrice,
                        (float) $oldItem['unit_price']
                    );
                    $newTotal = round($newQty * $newPrice, 3);

                    $db->execute(
                        "UPDATE return_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?",
                        [$newQty, $newPrice, $newTotal, $retItemId]
                    );

                    if ($qtyDiff > 0) {
                        $db->execute(
                            "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                            [$oldItem['item_id'], $warehouseId, $qtyDiff, $qtyDiff]
                        );
                    } elseif ($qtyDiff < 0) {
                        $absDiff = abs($qtyDiff);
                        $affected = $db->execute(
                            "UPDATE stock SET quantity = quantity - ?
                             WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                            [$absDiff, $oldItem['item_id'], $warehouseId, $absDiff]
                        );
                        if ($affected === 0) {
                            throw new Exception("Insufficient stock to reduce return quantity for item {$oldItem['item_id']}.");
                        }
                    }

                    if ($isImeiItem) {
                        $currentLinks = $db->fetchAll(
                            "SELECT rii.imei_id, ir.imei
                             FROM return_item_imei rii
                             JOIN imei_records ir ON ir.id = rii.imei_id
                             WHERE rii.return_item_id = ?",
                            [$retItemId]
                        );
                        $currentByImei = [];
                        foreach ($currentLinks as $lnk) {
                            $key = (string)($lnk['imei'] ?? '');
                            if ($key !== '') {
                                $currentByImei[$key] = (int)$lnk['imei_id'];
                            }
                        }

                        if ($newQty !== $oldQty || !empty($postedImeis) || !empty($currentByImei)) {
                            if (count($postedImeis) !== $newQty) {
                                throw new Exception("IMEI count must match quantity ({$newQty}) for item {$oldItem['item_id']}.");
                            }

                            foreach ($postedImeis as $postedImei) {
                                if (isset($currentByImei[$postedImei])) {
                                    continue;
                                }
                                $imeiRow = $db->fetchOne(
                                    "SELECT id, status, sale_id, item_id, warehouse_id
                                     FROM imei_records
                                     WHERE imei = ? LIMIT 1",
                                    [$postedImei]
                                );
                                if (!$imeiRow) {
                                    throw new Exception("IMEI {$postedImei} not found in system.");
                                }
                                if ((int)$imeiRow['item_id'] !== (int)$oldItem['item_id']) {
                                    throw new Exception("IMEI {$postedImei} does not belong to item {$oldItem['item_id']}.");
                                }
                                if ((int)$imeiRow['warehouse_id'] !== $warehouseId) {
                                    throw new Exception("IMEI {$postedImei} belongs to another warehouse.");
                                }

                                $refSaleId = !empty($return['ref_id']) ? (int)$return['ref_id'] : 0;
                                if ($refSaleId > 0) {
                                    $aff = $db->execute(
                                        "UPDATE imei_records
                                         SET status='in_stock', sale_id=NULL, warehouse_id=?
                                         WHERE id=? AND status='sold' AND sale_id=?",
                                        [$warehouseId, (int)$imeiRow['id'], $refSaleId]
                                    );
                                } else {
                                    $aff = $db->execute(
                                        "UPDATE imei_records
                                         SET status='in_stock', sale_id=NULL, warehouse_id=?
                                         WHERE id=? AND status='sold'",
                                        [$warehouseId, (int)$imeiRow['id']]
                                    );
                                }
                                if ($aff === 0) {
                                    throw new Exception("IMEI {$postedImei} is not returnable for this sale.");
                                }
                                $db->insert(
                                    "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                                    [$retItemId, (int)$imeiRow['id']]
                                );
                                $currentByImei[$postedImei] = (int)$imeiRow['id'];
                            }

                            foreach ($currentByImei as $imeiText => $imeiId) {
                                if (in_array($imeiText, $postedImeis, true)) {
                                    continue;
                                }
                                $soldAff = $db->execute(
                                    "UPDATE imei_records
                                     SET status='sold', sale_id=?, warehouse_id=?
                                     WHERE id=? AND status IN ('in_stock','returned')",
                                    [!empty($return['ref_id']) ? (int)$return['ref_id'] : null, $warehouseId, (int)$imeiId]
                                );
                                if ($soldAff === 0) {
                                    throw new Exception("Unable to restore IMEI {$imeiText} to sold state.");
                                }
                                $db->execute(
                                    "DELETE FROM return_item_imei WHERE return_item_id = ? AND imei_id = ?",
                                    [$retItemId, (int)$imeiId]
                                );
                            }
                        }
                    }

                }
            }

            // ── Handle new items added during edit ────────────────────────────
            $newItems = $_POST['new_items'] ?? [];
            foreach ($newItems as $row) {
                $itemId   = (int)($row['item_id'] ?? 0);
                $newQty   = max(1, (int)($row['quantity'] ?? 1));
                if (!$itemId) continue;
                $itemMeta = $db->fetchOne("SELECT has_imei FROM items WHERE id = ?", [$itemId]);
                $postedImeis = $this->parsePostedImeis($row['imeis'] ?? '');
                if ($itemMeta && (int)$itemMeta['has_imei'] === 1 && count($postedImeis) !== $newQty) {
                    throw new Exception("IMEI count must match quantity ({$newQty}) for new item {$itemId}.");
                }

                $refSaleIdForPrice = !empty($return['ref_id']) ? (int) $return['ref_id'] : null;
                $newPrice = $this->returnModel->resolveSaleReturnUnitPrice(
                    $itemId,
                    $postedImeis,
                    $refSaleIdForPrice,
                    null
                );
                $newTotal = round($newQty * $newPrice, 3);

                $retItemId = $db->insert(
                    "INSERT INTO return_items (return_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$id, $itemId, $newQty, $newPrice, $newTotal]
                );

                // A new return item adds stock back
                $db->execute(
                    "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                    [$itemId, $warehouseId, $newQty, $newQty]
                );

                if ($itemMeta && (int)$itemMeta['has_imei'] === 1) {
                    $refSaleId = !empty($return['ref_id']) ? (int)$return['ref_id'] : 0;
                    foreach ($postedImeis as $postedImei) {
                        $imeiRow = $db->fetchOne(
                            "SELECT id, status, sale_id, item_id, warehouse_id
                             FROM imei_records
                             WHERE imei = ? LIMIT 1",
                            [$postedImei]
                        );
                        if (!$imeiRow) {
                            throw new Exception("IMEI {$postedImei} not found in system.");
                        }
                        if ((int)$imeiRow['item_id'] !== $itemId) {
                            throw new Exception("IMEI {$postedImei} does not belong to item {$itemId}.");
                        }
                        if ((int)$imeiRow['warehouse_id'] !== $warehouseId) {
                            throw new Exception("IMEI {$postedImei} belongs to another warehouse.");
                        }

                        if ($refSaleId > 0) {
                            $aff = $db->execute(
                                "UPDATE imei_records
                                 SET status='in_stock', sale_id=NULL, warehouse_id=?
                                 WHERE id=? AND status='sold' AND sale_id=?",
                                [$warehouseId, (int)$imeiRow['id'], $refSaleId]
                            );
                        } else {
                            $aff = $db->execute(
                                "UPDATE imei_records
                                 SET status='in_stock', sale_id=NULL, warehouse_id=?
                                 WHERE id=? AND status='sold'",
                                [$warehouseId, (int)$imeiRow['id']]
                            );
                        }
                        if ($aff === 0) {
                            throw new Exception("IMEI {$postedImei} is not returnable for this sale.");
                        }
                        $db->insert(
                            "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                            [(int)$retItemId, (int)$imeiRow['id']]
                        );
                    }
                }
            }
            $dbSubtotalRow = $db->fetchOne(
                "SELECT COALESCE(SUM(total),0) as sum_total FROM return_items WHERE return_id = ?",
                [$id]
            );
            $newSubtotal = (float)($dbSubtotalRow['sum_total'] ?? 0);

            $updatedRows = $db->execute(
                "UPDATE returns SET date = ?, subtotal = ?, grand_total = ?, reason = ? WHERE id = ? AND warehouse_id = ?",
                [$newDate, $newSubtotal, $newSubtotal, $newReason, $id, $warehouseId]
            );
            if ($updatedRows !== 1) {
                throw new Exception("Return header update failed for return {$id}.");
            }
            $sumReturnItems = $newSubtotal;

            // Sale return totals change party ledger only (grand_total on returns row).
            // Invoice sales.balance is not adjusted.

            $db->commit();
            unset($_SESSION['return_edit_nonce'][$id]);
            $this->logActivity('edit_return', 'returns', $id, "Edited {$return['return_no']}");
            $this->flash('success', "Return {$return['return_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed: ' . $e->getMessage());
        }

        $this->redirect("?page=returns&action=detail&id={$id}");
        } catch (\Throwable $e) {
            try {
                Database::getInstance()->rollback();
            } catch (\Throwable $ignored) {
            }
            error_log('[returns-update] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->flash('error', 'Could not update the return. Please try again.');
            $fallbackId = $this->inputInt('id');
            $this->redirect($fallbackId > 0 ? "?page=returns&action=detail&id={$fallbackId}" : '?page=returns');
        }
    }
}
