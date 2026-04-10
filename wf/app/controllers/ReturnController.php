<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Return.php';
require_once __DIR__ . '/../models/Party.php';
require_once __DIR__ . '/../models/Item.php';

class ReturnController extends BaseController {
    private SaleReturn $returnModel;
    private Party      $partyModel;
    private Item       $itemModel;

    public function __construct() {
        parent::__construct();
        $this->returnModel = new SaleReturn();
        $this->partyModel  = new Party();
        $this->itemModel   = new Item();
    }

    public function index(): void {
        Auth::authorize('returns', 'view');
        $filters = [
            'from_date' => $this->input('from_date', date('Y-m-01'), 'get'),
            'to_date'   => $this->input('to_date', date('Y-m-d'), 'get'),
            'status'    => $this->input('status', '', 'get'),
        ];
        $returns   = $this->returnModel->getAll($filters);
        $pageTitle = 'Sale Returns';
        $page      = 'returns';

        ob_start();
        include __DIR__ . '/../views/returns/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('returns', 'add');
        $db         = Database::getInstance();
        $parties    = $this->partyModel->getForDropdown('customer');
        $warehouses = $this->itemModel->getWarehouses();
        $pageTitle  = 'New Return';
        $page       = 'returns';

        ob_start();
        include __DIR__ . '/../views/returns/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('returns', 'add');

        if (!$this->isPost()) {
            $this->redirect('?page=returns&action=create');
        }

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
            $this->flash('error', 'Add at least one item.');
            $this->redirect('?page=returns&action=create');
        }

        $result = $this->returnModel->create([
            'ref_id'       => $this->inputInt('ref_id') ?: null,
            'party_id'     => $this->inputInt('party_id'),
            'warehouse_id' => Auth::warehouseId(),
            'date'         => $this->input('date'),
            'reason'       => $this->input('reason'),
            'items'        => $items,
        ]);

        if ($result['success']) {
            $this->logActivity('create_return', 'returns', $result['id'], $result['return_no']);
            $this->flash('success', "Return {$result['return_no']} saved.");
            if ($this->input('print_mode') === '1') {
                $this->redirect('?page=returns&action=print&id=' . $result['id'] . '&autoprint=1');
            }
            $this->redirect('?page=returns');
        } else {
            $this->flash('error', $result['error']);
            $this->redirect('?page=returns&action=create');
        }
    }

    public function print(): void {
        Auth::authorize('returns', 'view');
        $id     = $this->inputInt('id', 0, 'get');
        $return = $this->returnModel->findFull($id);
        if (!$return) die('Return not found.');

        $db       = Database::getInstance();
        $settings = [];
        $rows     = $db->fetchAll("SELECT key_name, value FROM settings");
        foreach ($rows as $r) $settings[$r['key_name']] = $r['value'];

        include __DIR__ . '/../views/returns/print.php';
    }

    public function detail(): void {
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
    }
}
