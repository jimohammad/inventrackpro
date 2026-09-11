<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/DeviceDump.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Return.php';

class DumpController extends BaseController {

    private Database $db;
    private DeviceDump $dumpModel;
    private Party $partyModel;
    private Item $itemModel;

    public function __construct() {
        parent::__construct();
        $this->db        = Database::getInstance();
        $this->dumpModel = new DeviceDump();
        $this->partyModel = new Party();
        $this->itemModel = new Item();
        DeviceDump::ensureSchema($this->db);
    }

    public function index(): void {
        Auth::authorize('dumps', 'view');
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }

        $whId = $this->requireWarehouseId();
        $search = trim((string) $this->input('search', '', 'get'));
        $hasSearch = $search !== '';

        $dateRange = ListPage::resolveDateFiltersFromGet(2);
        if ($hasSearch && !empty($dateRange['dates_defaulted'])) {
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
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ];
        $fetchCap = ListPage::MAX_ROWS + 1;
        $dumps    = $this->dumpModel->getAll($filters, $whId, $fetchCap);
        $capped   = ListPage::capRows($dumps);
        $dumps    = $capped['items'];
        $listTruncated = $capped['truncated'];
        $listLimit     = $capped['limit'];
        $datesDefaulted = !empty($dateRange['dates_defaulted']);
        $listPageName  = 'dumps';
        $listDateDefaultLabel = 'last two months by default';

        $pageTitle = 'Dump Credit';
        $page      = 'dumps';

        ob_start();
        include __DIR__ . '/../views/dumps/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('dumps', 'add');
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }
        $this->requireWarehouseId();

        $nextNo    = $this->dumpModel->nextNoPreview();
        $pageTitle = 'New Dump Credit';
        $page      = 'dumps';

        ob_start();
        include __DIR__ . '/../views/dumps/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('dumps', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=dumps&action=create');
        }
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }
        $whId = $this->requireWarehouseId();

        $rawLines = $_POST['lines'] ?? [];
        $lines    = [];
        if (is_array($rawLines)) {
            foreach ($rawLines as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $lines[] = [
                    'imei'       => (string) ($row['imei'] ?? ''),
                    'legacy'     => !empty($row['legacy']),
                    'item_id'    => (int) ($row['item_id'] ?? 0),
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                ];
            }
        }

        $result = $this->dumpModel->create(
            [
                'party_id' => $this->inputInt('party_id'),
                'date'     => $this->input('date') ?: date('Y-m-d'),
            ],
            $lines,
            $whId,
            (int) (Auth::id() ?? 0)
        );

        if (empty($result['ok'])) {
            $this->flash('error', $result['error'] ?? 'Could not save dump credit.');
            $this->redirect('?page=dumps&action=create');
        }

        self::clearDashboardCache($whId);
        $this->logActivity('dump_credit', 'dumps', (int) $result['id'], (string) $result['dump_no']);
        $this->flash('success', 'Dump credit ' . $result['dump_no'] . ' recorded. Party credited.');
        $this->redirect('?page=dumps&action=view&id=' . (int) $result['id']);
    }

    public function view(): void {
        Auth::authorize('dumps', 'view');
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }
        $dump = $this->loadCurrent();

        $pageTitle = $dump['dump_no'];
        $page      = 'dumps';

        ob_start();
        include __DIR__ . '/../views/dumps/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function void(): void {
        Auth::authorize('dumps', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=dumps');
        }
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }
        $dump = $this->loadCurrent('post');
        $result = $this->dumpModel->void((int) $dump['id'], $this->requireWarehouseId());
        if (empty($result['ok'])) {
            $this->flash('error', $result['error'] ?? 'Could not void dump.');
            $this->redirect('?page=dumps&action=view&id=' . (int) $dump['id']);
        }

        self::clearDashboardCache((int) $dump['warehouse_id']);
        $this->logActivity('dump_credit_void', 'dumps', (int) $dump['id'], (string) $dump['dump_no']);
        $this->flash('success', $dump['dump_no'] . ' voided. Party credit reversed.');
        $this->redirect('?page=dumps&action=view&id=' . (int) $dump['id']);
    }

    /** AJAX: sold IMEI lookup for dump scan. */
    public function lookupImei(): void {
        Auth::authorize('dumps', 'add');
        header('Content-Type: application/json; charset=UTF-8');
        if (!$this->schemaReady()) {
            echo json_encode(['ok' => false, 'message' => 'Dump table is not ready.']);
            return;
        }

        $imei = ImeiFormat::normalize(trim((string) ($_GET['imei'] ?? '')));
        if ($imei === '' || !ImeiFormat::isPlausible($imei)) {
            echo json_encode(['ok' => false, 'message' => 'Not a phone IMEI (13 or 15–18 digits) or tablet serial.']);
            return;
        }

        $whId = (int) (Auth::warehouseId() ?: 0);
        if ($whId <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Select a warehouse first.']);
            return;
        }

        $row = $this->db->fetchOne(
            "SELECT ir.id AS imei_id, ir.imei, ir.status, ir.item_id, ir.sale_id, ir.warehouse_id,
                    i.name AS item_name, i.sku,
                    s.invoice_no AS sold_invoice, s.date AS sale_date,
                    s.party_id AS sale_party_id, s.warehouse_id AS sale_warehouse_id,
                    s.status AS sale_status, p.name AS party_name
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN parties p ON p.id = s.party_id
             WHERE ir.imei = ? OR ir.imei2 = ?
             LIMIT 1",
            [$imei, $imei]
        );

        if (!$row) {
            $already = $this->db->fetchOne(
                "SELECT d.dump_no
                 FROM device_dump_items di
                 JOIN device_dumps d ON d.id = di.dump_id
                 WHERE di.imei = ? AND d.status = 'approved'
                 LIMIT 1",
                [$imei]
            );
            if ($already) {
                echo json_encode(['ok' => false, 'message' => 'Already dumped on ' . $already['dump_no'] . '.']);
                return;
            }
            echo json_encode([
                'ok'         => false,
                'not_in_app' => true,
                'imei'       => $imei,
                'message'    => 'IMEI is not in the app (sold before April 2026). Pick the model and credit amount.',
            ]);
            return;
        }
        if ((string) $row['status'] === 'dumped') {
            echo json_encode(['ok' => false, 'message' => 'IMEI is already dumped.']);
            return;
        }
        if ((string) $row['status'] !== 'sold') {
            $msg = match ((string) $row['status']) {
                'in_stock', 'returned' => 'IMEI is in stock — use a sale return only if it should go back on the shelf.',
                'defective'            => 'IMEI is already on a warranty replacement.',
                default                => 'IMEI must be sold to dump (status: ' . $row['status'] . ').',
            };
            echo json_encode(['ok' => false, 'message' => $msg]);
            return;
        }

        $saleWh = (int) ($row['sale_warehouse_id'] ?? $row['warehouse_id'] ?? 0);
        if ($saleWh !== $whId) {
            echo json_encode(['ok' => false, 'message' => 'IMEI was sold on another branch.']);
            return;
        }
        if (($row['sale_status'] ?? '') === 'cancelled') {
            echo json_encode(['ok' => false, 'message' => 'Linked sale is cancelled.']);
            return;
        }

        $already = $this->db->fetchOne(
            "SELECT d.dump_no
             FROM device_dump_items di
             JOIN device_dumps d ON d.id = di.dump_id
             WHERE di.imei_id = ? AND d.status = 'approved'
             LIMIT 1",
            [(int) $row['imei_id']]
        );
        if ($already) {
            echo json_encode(['ok' => false, 'message' => 'Already dumped on ' . $already['dump_no'] . '.']);
            return;
        }

        try {
            $price = (new SaleReturn())->resolveSaleReturnUnitPrice(
                (int) $row['item_id'],
                [$imei],
                !empty($row['sale_id']) ? (int) $row['sale_id'] : null,
                null
            );
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
            return;
        }
        if ($price <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Sold price is zero — cannot dump.']);
            return;
        }

        $unitPrice = number_format($price, 3, '.', '');
        $warranty  = $this->warrantyCheck((string) ($row['sale_date'] ?? ''));
        $soldTo    = trim((string) ($row['party_name'] ?? ''));
        $msgParts  = ['Sold price ' . APP_CURRENCY . ' ' . $unitPrice, $warranty['label']];
        if ($soldTo !== '') {
            $msgParts[] = 'Sold to ' . $soldTo;
        }

        echo json_encode([
            'ok'             => true,
            'imei'           => $row['imei'],
            'item_id'        => (int) $row['item_id'],
            'item_name'      => $row['item_name'],
            'sku'            => $row['sku'] ?? '',
            'unit_price'     => $unitPrice,
            'sold_invoice'   => $row['sold_invoice'] ?? '',
            'sale_id'        => !empty($row['sale_id']) ? (int) $row['sale_id'] : null,
            'sold_to'        => $soldTo,
            'warranty_in'    => $warranty['in_warranty'],
            'warranty_label' => $warranty['label'],
            'message'        => implode(' · ', $msgParts),
        ]);
    }

    /**
     * Same 13-month window as public /imei warranty track.
     * @return array{in_warranty:bool,label:string}
     */
    private function warrantyCheck(string $saleDate): array {
        if ($saleDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $saleDate)) {
            return ['in_warranty' => false, 'label' => 'Warranty unknown'];
        }
        try {
            $soldAt = new DateTimeImmutable(substr($saleDate, 0, 10));
        } catch (Throwable $e) {
            return ['in_warranty' => false, 'label' => 'Warranty unknown'];
        }
        $end   = $soldAt->modify('+13 months');
        $today = new DateTimeImmutable('today');
        if ($today > $end) {
            return ['in_warranty' => false, 'label' => 'Warranty expired'];
        }
        $diff   = $today->diff($end);
        $months = ($diff->y * 12) + $diff->m;
        $days   = (int) $diff->d;
        $left   = $months . ' month' . ($months === 1 ? '' : 's')
            . ' ' . $days . ' day' . ($days === 1 ? '' : 's');

        return ['in_warranty' => true, 'label' => 'In warranty · ' . $left . ' left'];
    }

    public function searchParties(): void {
        header('Content-Type: application/json; charset=UTF-8');
        if (!Auth::can('dumps', 'view') && !Auth::can('dumps', 'add')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }
        $parties = $this->partyModel->search($q, 'customer', false);
        echo json_encode($parties);
    }

    /** AJAX: item names for older/not-in-app dump lines. */
    public function searchItems(): void {
        header('Content-Type: application/json; charset=UTF-8');
        if (!Auth::can('dumps', 'add')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }
        $whId = (int) (Auth::warehouseId() ?: 0);
        echo json_encode($this->itemModel->search($q, $whId ?: null, false, false));
    }

    /** @return array<string,mixed> */
    private function loadCurrent(string $from = 'get'): array {
        $id = $this->inputInt('id', 0, $from) ?: $this->inputInt('id', 0, 'get');
        $row = $this->dumpModel->findFull($id, $this->requireWarehouseId());
        if (!$row) {
            $this->flash('error', 'Dump credit not found.');
            $this->redirect('?page=dumps');
        }
        return $row;
    }

    private function requireWarehouseId(): int {
        $whId = (int) (Auth::warehouseId() ?: 0);
        if ($whId <= 0) {
            $this->flash('error', 'Select a warehouse first.');
            $this->redirect('?page=warehouse');
        }
        return $whId;
    }

    private function schemaReady(): bool {
        return DeviceDump::ensureSchema($this->db);
    }

    private function schemaFailureRedirect(): void {
        $this->flash('error', DeviceDump::schemaErrorMessage());
        $this->redirect('?page=dashboard');
    }
}
