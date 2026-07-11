<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';

class ItemController extends BaseController {
    private Item $itemModel;

    public function __construct() {
        parent::__construct();
        $this->itemModel = new Item();
    }

    public function index(): void {
        Auth::authorize('inventory', 'view');
        $items      = $this->itemModel->getAllWithStock(Auth::warehouseId(), true);
        $categories = $this->itemModel->getCategories();
        $pageTitle  = 'Items';
        $page       = 'items';

        ob_start();
        include __DIR__ . '/../views/inventory/items.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('inventory', 'add');
        $categories = $this->itemModel->getCategories();
        $pageTitle  = 'New Item';
        $page       = 'items';

        ob_start();
        include __DIR__ . '/../views/inventory/item_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('inventory', 'add');
        if (!$this->isPost()) { $this->redirect('?page=items&action=create'); return; }

        $name = $this->input('name');
        if ($name === '') {
            $this->flash('error', 'Item name is required.');
            $this->redirect('?page=items&action=create');
            return;
        }

        try {
            $id = $this->itemModel->create([
                'name'           => $name,
                'sku'            => $this->input('sku'),
                'barcode'        => $this->input('barcode'),
                'category_id'    => $this->inputInt('category_id') ?: null,
                'brand'          => $this->input('brand'),
                'model'          => $this->input('model'),
                'unit'           => $this->input('unit'),
                'has_imei'       => $this->inputInt('has_imei'),
                'imei_optional'  => $this->inputInt('imei_optional'),
                'purchase_price' => $this->inputFloat('purchase_price'),
                'price_aed'      => $this->inputFloat('price_aed'),
                'price_usd'      => $this->inputFloat('price_usd'),
                'sale_price'     => $this->inputFloat('sale_price'),
                'min_stock'      => $this->inputInt('min_stock'),
                'description'    => $this->input('description'),
            ]);
        } catch (Exception $e) {
            error_log('ItemController::store failed: ' . $e->getMessage());
            $this->flash('error', 'Failed to create item. Please try again.');
            $this->redirect('?page=items&action=create');
            return;
        }

        if ($id) {
            $this->logActivity('create_item', 'inventory', (int)$id, $name);
            $this->flash('success', 'Item created.');
            $this->redirect('?page=items');
            return;
        }
        $this->flash('error', 'Failed to create item.');
        $this->redirect('?page=items&action=create');
    }

    public function edit(): void {
        Auth::authorize('inventory', 'edit');
        $id   = $this->inputInt('id', 0, 'get');
        $item = $this->itemModel->find($id);
        if (!$item) { $this->flash('error', 'Item not found.'); $this->redirect('?page=items'); return; }

        require_once __DIR__ . '/../services/ItemCostService.php';
        $latestCost = ItemCostService::latestRealUnitCost(Database::getInstance(), $id);

        $categories = $this->itemModel->getCategories();
        $pageTitle  = 'Edit Item';
        $page       = 'items';
        $editMode   = true;

        ob_start();
        include __DIR__ . '/../views/inventory/item_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorize('inventory', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=items'); return; }

        $id   = $this->inputInt('id');
        $name = $this->input('name');
        if ($name === '') {
            $this->flash('error', 'Item name is required.');
            $this->redirect('?page=items&action=edit&id=' . $id);
            return;
        }

        try {
            $affected = $this->itemModel->update($id, [
                'name'           => $name,
                'sku'            => $this->input('sku'),
                'barcode'        => $this->input('barcode'),
                'category_id'    => $this->inputInt('category_id') ?: null,
                'brand'          => $this->input('brand'),
                'model'          => $this->input('model'),
                'unit'           => $this->input('unit'),
                'has_imei'       => $this->inputInt('has_imei'),
                'imei_optional'  => $this->inputInt('imei_optional'),
                'purchase_price' => $this->inputFloat('purchase_price'),
                'price_aed'      => $this->inputFloat('price_aed'),
                'price_usd'      => $this->inputFloat('price_usd'),
                'sale_price'     => $this->inputFloat('sale_price'),
                'min_stock'      => $this->inputInt('min_stock'),
                'description'    => $this->input('description'),
                'is_active'      => $this->inputInt('is_active'),
            ]);
        } catch (Exception $e) {
            error_log('ItemController::update failed: ' . $e->getMessage());
            $this->flash('error', 'Failed to update item. Please try again.');
            $this->redirect('?page=items&action=edit&id=' . $id);
            return;
        }

        if ($affected === 0) {
            $this->flash('error', 'Item not found or could not be updated.');
            $this->redirect('?page=items&action=edit&id=' . $id);
            return;
        }

        $this->logActivity('update_item', 'inventory', $id);
        $this->flash('success', 'Item updated.');
        $this->redirect('?page=items');
    }

    /** Admin: rebuild item master costs from latest purchase lines (incl. landed logistics). */
    public function syncCosts(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=items');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=items');
            return;
        }

        require_once __DIR__ . '/../services/ItemCostService.php';
        $result = ItemCostService::syncAllFromPurchases(Database::getInstance());
        $this->logActivity('sync_item_costs', 'inventory', 0, 'Updated ' . $result['updated'] . ' item(s)');
        $this->flash(
            'success',
            'Real costs synced from latest purchases. Updated ' . (int) $result['updated'] . ' item(s).'
        );
        $this->redirect('?page=items');
    }

    // Stock list
    public function stock(): void {
        Auth::authorize('inventory', 'view');

        $db         = Database::getInstance();
        $warehouses = self::getWarehouses();
        // Always filter by session warehouse — strict separation
        $whId = Auth::warehouseId();
        if (!$whId) {
            $this->redirect('/?page=warehouse');
            return;
        }

        $stockList = $db->fetchAll(
            "SELECT i.name, i.sku, i.brand, i.model, i.has_imei, i.min_stock, i.sale_price,
                    w.name as warehouse_name, s.quantity
             FROM stock s
             JOIN items i ON i.id = s.item_id
             JOIN warehouses w ON w.id = s.warehouse_id
             WHERE i.is_active = 1 AND s.warehouse_id = ?
             ORDER BY w.name, i.name",
            [$whId]
        );

        $pageTitle = 'Stock List';
        $page      = 'stock';

        ob_start();
        include __DIR__ . '/../views/inventory/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
