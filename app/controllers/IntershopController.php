<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../models/IntershopTransfer.php';
require_once __DIR__ . '/../services/IntershopPeerClient.php';

class IntershopController extends BaseController {

    private Database $db;
    private IntershopTransfer $model;
    private Item $itemModel;

    public function __construct() {
        parent::__construct();
        $this->db        = Database::getInstance();
        $this->model     = new IntershopTransfer();
        $this->itemModel = new Item();
        IntershopTransfer::ensureSchema($this->db);
    }

    public function index(): void {
        Auth::authorize('intershop', 'view');
        $this->requireSchema();

        $whId = (int) Auth::warehouseId();
        $rows = $this->model->listForWarehouse($whId);
        $peerConfigured = IntershopPeerClient::isConfigured();
        $peerName = IntershopPeerClient::peerName();

        $pageTitle = 'Shop stock transfer';
        $page      = 'intershop';
        ob_start();
        include __DIR__ . '/../views/intershop/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('intershop', 'add');
        $this->requireSchema();
        $peerName = IntershopPeerClient::peerName();
        $peerConfigured = IntershopPeerClient::isConfigured();

        $pageTitle = 'Send stock to ' . $peerName;
        $page      = 'intershop';
        ob_start();
        include __DIR__ . '/../views/intershop/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('intershop', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=intershop');
        }
        $this->requireSchema();

        $whId  = (int) Auth::warehouseId();
        $date  = trim((string) $this->input('date')) ?: date('Y-m-d');
        $notes = trim((string) $this->input('notes'));
        $lines = $this->parsePostedLines();

        try {
            $created = $this->model->createOutbound($whId, $date, $notes, Auth::id(), $lines);
        } catch (Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=intershop&action=create');
            return;
        }

        $this->deliverOutbound((int) $created['id'], $created['payload']);
        $this->redirect('?page=intershop&action=view&id=' . (int) $created['id']);
    }

    public function view(): void {
        Auth::authorize('intershop', 'view');
        $this->requireSchema();
        $id  = $this->inputInt('id', 0, 'get');
        $doc = $this->model->findWithLines($id, (int) Auth::warehouseId());
        if (!$doc) {
            $this->flash('error', 'Transfer not found.');
            $this->redirect('?page=intershop');
        }

        $pageTitle = (string) $doc['transfer_no'];
        $page      = 'intershop';
        ob_start();
        include __DIR__ . '/../views/intershop/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function retry(): void {
        Auth::authorize('intershop', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=intershop');
        }
        $this->requireSchema();
        $id  = $this->inputInt('id');
        $whId = (int) Auth::warehouseId();
        $doc = $this->model->findWithLines($id, $whId);
        if (!$doc || ($doc['direction'] ?? '') !== 'outbound') {
            $this->flash('error', 'Outbound transfer not found.');
            $this->redirect('?page=intershop');
        }
        if (!in_array((string) $doc['status'], ['pending', 'failed'], true)) {
            $this->flash('error', 'This transfer does not need a retry.');
            $this->redirect('?page=intershop&action=view&id=' . $id);
        }

        try {
            $payload = $this->model->payloadForRetry($id, $whId);
            $this->deliverOutbound($id, $payload);
        } catch (Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('?page=intershop&action=view&id=' . $id);
    }

    public function cancel(): void {
        Auth::authorize('intershop', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=intershop');
        }
        $this->requireSchema();
        $id = $this->inputInt('id');
        try {
            $this->model->cancelOutbound($id, (int) Auth::warehouseId());
            $this->flash('success', 'Transfer cancelled. Stock and serials are back on this shop.');
        } catch (Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('?page=intershop&action=view&id=' . $id);
    }

    public function searchItems(): void {
        header('Content-Type: application/json');
        if (!Auth::can('intershop', 'view') && !Auth::can('intershop', 'add')) {
            http_response_code(403);
            echo json_encode([]);
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }
        echo json_encode($this->itemModel->search($q, (int) Auth::warehouseId() ?: null, true, false));
    }

    public function validateImei(): void {
        header('Content-Type: application/json');
        if (!Auth::can('intershop', 'add')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Not allowed.']);
            return;
        }
        $imei   = ImeiFormat::normalize((string) ($_GET['imei'] ?? ''));
        $itemId = (int) ($_GET['item_id'] ?? 0);
        $whId   = (int) Auth::warehouseId();
        if ($imei === '' || $itemId <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Scan a serial.']);
            return;
        }
        $row = $this->db->fetchOne(
            "SELECT ir.status, ir.item_id, ir.warehouse_id, i.name
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ?",
            [$imei]
        );
        if (!$row) {
            echo json_encode(['ok' => false, 'msg' => 'Serial not registered.']);
            return;
        }
        if ((int) $row['item_id'] !== $itemId) {
            echo json_encode(['ok' => false, 'msg' => 'Belongs to ' . $row['name'] . '.']);
            return;
        }
        if ((int) $row['warehouse_id'] !== $whId) {
            echo json_encode(['ok' => false, 'msg' => 'Not in this warehouse.']);
            return;
        }
        if (!in_array((string) $row['status'], ['in_stock', 'returned'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Not in stock (' . $row['status'] . ').']);
            return;
        }
        echo json_encode(['ok' => true, 'imei' => $imei]);
    }

    private function requireSchema(): void {
        if (!IntershopTransfer::schemaIsReady()) {
            $this->flash('error', 'Shop transfer tables could not be created. Ask admin to run the SQL migration.');
            $this->redirect('?page=dashboard');
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function deliverOutbound(int $id, array $payload): void {
        $result = IntershopPeerClient::postReceive($payload);
        if (!empty($result['ok'])) {
            $this->model->markSent($id, $result['inbound_no']);
            $this->flash('success', 'Stock left this shop and arrived at ' . IntershopPeerClient::peerName() . '.');
            return;
        }
        $err = (string) ($result['error'] ?? 'Other shop did not accept the transfer.');
        $this->model->markFailed($id, $err);
        $this->flash('error', 'Stock was taken off this shop but the other shop did not receive it yet. Retry from the transfer page. ' . $err);
    }

    /**
     * @return list<array{item_id:int,quantity:int,imeis:list<string>}>
     */
    private function parsePostedLines(): array {
        $out = [];
        foreach ($_POST['items'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty    = (int) ($row['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            $imeis = preg_split('/\R+/', (string) ($row['imeis'] ?? '')) ?: [];
            $out[] = [
                'item_id'  => $itemId,
                'quantity' => $qty,
                'imeis'    => $imeis,
            ];
        }
        return $out;
    }
}
