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
        $items      = $this->itemModel->getAllWithStock();
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
        if (!$this->isPost()) { $this->redirect('?page=items&action=create'); }

        $id = $this->itemModel->create([
            'name'           => $this->input('name'),
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

        if ($id) {
            $this->logActivity('create_item', 'inventory', (int)$id, $this->input('name'));
            $this->flash('success', 'Item created.');
            $this->redirect('?page=items');
        } else {
            $this->flash('error', 'Failed to create item.');
            $this->redirect('?page=items&action=create');
        }
    }

    public function edit(): void {
        Auth::authorize('inventory', 'edit');
        $id   = $this->inputInt('id', 0, 'get');
        $item = $this->itemModel->find($id);
        if (!$item) { $this->flash('error', 'Item not found.'); $this->redirect('?page=items'); }

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
        if (!$this->isPost()) { $this->redirect('?page=items'); }

        $id = $this->inputInt('id');
        $this->itemModel->update($id, [
            'name'           => $this->input('name'),
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

        $this->logActivity('update_item', 'inventory', $id);
        $this->flash('success', 'Item updated.');
        $this->redirect('?page=items');
    }

    // Stock list
    public function stock(): void {
        Auth::authorize('inventory', 'view');

        $db         = Database::getInstance();
        $warehouses = self::getWarehouses();
        // Always filter by session warehouse — strict separation
        $whId       = Auth::warehouseId();

        $warehouseClause = $whId ? "AND s.warehouse_id = ?" : '';
        $params = $whId ? [$whId] : [];

        $stockList = $db->fetchAll(
            "SELECT i.name, i.sku, i.brand, i.model, i.has_imei, i.min_stock, i.sale_price,
                    w.name as warehouse_name, s.quantity
             FROM stock s
             JOIN items i ON i.id = s.item_id
             JOIN warehouses w ON w.id = s.warehouse_id
             WHERE i.is_active = 1 {$warehouseClause}
             ORDER BY w.name, i.name",
            $params
        );

        $pageTitle = 'Stock List';
        $page      = 'stock';

        ob_start();
        include __DIR__ . '/../views/inventory/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
