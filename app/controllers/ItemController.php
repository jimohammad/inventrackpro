<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../services/StockQuantityService.php';

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
        Item::ensureSerialKindColumn();
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
                'name_ar'        => $this->input('name_ar'),
                'sku'            => strtoupper(trim((string) $this->input('sku'))),
                'barcode'        => $this->input('barcode'),
                'category_id'    => $this->inputInt('category_id') ?: null,
                'brand'          => $this->input('brand'),
                'model'          => $this->input('model'),
                'unit'           => $this->input('unit'),
                'has_imei'       => $this->inputInt('has_imei'),
                'serial_kind'    => $this->inputInt('has_imei')
                    ? ImeiFormat::normalizeKind((string) $this->input('serial_kind'))
                    : ImeiFormat::KIND_PHONE,
                // Sales always require serials when has_imei=1; keep column at 0
                'imei_optional'  => 0,
                'has_nfc'        => $this->inputInt('has_nfc'),
                'purchase_price' => $this->inputFloat('purchase_price'),
                'sale_price'     => $this->inputFloat('sale_price'),
                'min_stock'      => $this->inputInt('min_stock'),
                'max_sale_qty'   => $this->inputInt('max_sale_qty'),
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
        Item::ensureSerialKindColumn();
        $id   = $this->inputInt('id', 0, 'get');
        $item = $this->itemModel->find($id);
        if (!$item) { $this->flash('error', 'Item not found.'); $this->redirect('?page=items'); return; }

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
                'name_ar'        => $this->input('name_ar'),
                'sku'            => strtoupper(trim((string) $this->input('sku'))),
                'barcode'        => $this->input('barcode'),
                'category_id'    => $this->inputInt('category_id') ?: null,
                'brand'          => $this->input('brand'),
                'model'          => $this->input('model'),
                'unit'           => $this->input('unit'),
                'has_imei'       => $this->inputInt('has_imei'),
                'serial_kind'    => $this->inputInt('has_imei')
                    ? ImeiFormat::normalizeKind((string) $this->input('serial_kind'))
                    : ImeiFormat::KIND_PHONE,
                // Sales always require serials when has_imei=1; keep column at 0
                'imei_optional'  => 0,
                'has_nfc'        => $this->inputInt('has_nfc'),
                'purchase_price' => $this->inputFloat('purchase_price'),
                'sale_price'     => $this->inputFloat('sale_price'),
                'min_stock'      => $this->inputInt('min_stock'),
                'max_sale_qty'   => $this->inputInt('max_sale_qty'),
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

    /**
     * Permanently delete an unused item (Admin PIN required in UI).
     * Blocked when the item appears on any sale/purchase/IMEI/PO/etc. history —
     * mark Inactive on the edit form instead.
     */
    public function delete(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required to delete items.');
            $this->redirect('?page=items');
            return;
        }
        Auth::authorize('inventory', 'delete');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=items');
            return;
        }

        $id = $this->inputInt('id');
        $item = $this->itemModel->find($id);
        if (!$item) {
            $this->flash('error', 'Item not found.');
            $this->redirect('?page=items');
            return;
        }

        $blocker = $this->itemDeleteBlocker($id);
        if ($blocker !== null) {
            $this->flash(
                'error',
                'Cannot delete "' . ($item['name'] ?? 'item') . '" — ' . $blocker
                . ' Mark it Inactive on Edit instead.'
            );
            $this->redirect('?page=items&action=edit&id=' . $id);
            return;
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            // Zero-qty stock rows may exist; cascade also handles them.
            $db->execute('DELETE FROM stock WHERE item_id = ?', [$id]);
            $db->execute('DELETE FROM items WHERE id = ?', [$id]);
            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $ignored) {
                // no active transaction
            }
            error_log('ItemController::delete failed: ' . $e->getMessage());
            $this->flash('error', 'Failed to delete item. It may still be linked to other records.');
            $this->redirect('?page=items');
            return;
        }

        $this->logActivity('delete_item', 'inventory', $id, (string) ($item['name'] ?? ''));
        $this->flash('success', 'Item "' . ($item['name'] ?? '') . '" deleted.');
        $this->redirect('?page=items');
    }

    /** @return string|null Human reason if hard-delete is unsafe */
    private function itemDeleteBlocker(int $itemId): ?string {
        $db = Database::getInstance();
        $checks = [
            ['sale_items', 'item_id', 'used on sale invoices'],
            ['purchase_items', 'item_id', 'used on purchase invoices'],
            ['purchase_order_items', 'item_id', 'used on purchase orders'],
            ['return_items', 'item_id', 'used on returns'],
            ['imei_records', 'item_id', 'has IMEI / serial records'],
            ['opening_stock_log', 'item_id', 'has opening stock'],
            ['stock_transfer_items', 'item_id', 'used on stock transfers'],
            ['shipment_item_charges', 'item_id', 'used on import shipments'],
            ['customer_discounts', 'item_id', 'linked to customer discounts'],
            ['warranty_replacements', 'old_item_id', 'used on warranty replacements'],
            ['warranty_replacements', 'new_item_id', 'used on warranty replacements'],
            ['device_dump_items', 'item_id', 'used on dump credits'],
        ];

        foreach ($checks as [$table, $column, $label]) {
            try {
                $row = $db->fetchOne(
                    "SELECT COUNT(*) AS c FROM {$table} WHERE {$column} = ?",
                    [$itemId]
                );
            } catch (Throwable $e) {
                // Table may not exist on older DBs — skip that check.
                continue;
            }
            if ((int) ($row['c'] ?? 0) > 0) {
                return $label;
            }
        }

        try {
            $stock = $db->fetchOne(
                'SELECT COALESCE(SUM(quantity), 0) AS qty FROM stock WHERE item_id = ?',
                [$itemId]
            );
            if ((float) ($stock['qty'] ?? 0) > 0) {
                return 'still has stock quantity';
            }
        } catch (Throwable $e) {
            // ignore
        }

        return null;
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
        Auth::authorizeAny(['stock', 'inventory'], 'view');

        $db         = Database::getInstance();
        $warehouses = self::getWarehouses();
        // Always filter by session warehouse — strict separation
        $whId = Auth::warehouseId();
        if (!$whId) {
            $this->redirect('/?page=warehouse');
            return;
        }

        // Show active items, plus inactive ones that still have qty (so arrivals stay visible).
        $stockList = $db->fetchAll(
            "SELECT i.id AS item_id, i.name, i.sku, i.brand, i.model, i.has_imei, i.min_stock, i.sale_price,
                    i.is_active, w.name as warehouse_name, s.quantity,
                    (SELECT COUNT(*) FROM imei_records ir
                      WHERE ir.item_id = i.id AND ir.warehouse_id = s.warehouse_id
                        AND ir.status IN ('in_stock','returned')) AS imei_in_stock
             FROM stock s
             JOIN items i ON i.id = s.item_id
             JOIN warehouses w ON w.id = s.warehouse_id
             WHERE s.warehouse_id = ?
               AND (i.is_active = 1 OR s.quantity > 0)
             ORDER BY w.name, i.name",
            [$whId]
        );

        foreach ($stockList as &$row) {
            $qty  = (int) ($row['quantity'] ?? 0);
            $imei = (int) ($row['imei_in_stock'] ?? 0);
            if (!empty($row['has_imei']) && $imei > $qty) {
                $row['imei_diag'] = StockQuantityService::imeiOverDiagnosis(
                    $db,
                    (int) ($row['item_id'] ?? 0),
                    $whId
                );
            }
        }
        unset($row);

        StockQuantityService::ensureAdjustmentsSchema($db);

        $canWriteOff = Auth::isAdmin() && StockQuantityService::writeOffEnabled();

        $pageTitle = 'Stock List';
        $page      = 'stock';

        ob_start();
        include __DIR__ . '/../views/inventory/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Admin: write off book qty down to a physical count (session branch only).
     */
    public function writeOffStock(): void {
        if (!StockQuantityService::writeOffEnabled()) {
            $this->flash('error', 'Stock qty write-off is temporarily disabled.');
            $this->redirect('?page=stock');
            return;
        }
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required to write off stock.');
            $this->redirect('?page=stock');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=stock');
            return;
        }

        $itemId      = $this->inputInt('item_id');
        $notes       = trim($this->input('notes', ''));
        $whId        = (int) Auth::warehouseId();
        $rawPhysical = $_POST['physical_qty'] ?? '';

        if ($itemId <= 0 || $whId <= 0) {
            $this->flash('error', 'Item and warehouse are required.');
            $this->redirect('?page=stock');
            return;
        }
        if ($rawPhysical === '' || !is_numeric($rawPhysical)) {
            $this->flash('error', 'Enter a physical count.');
            $this->redirect('?page=stock');
            return;
        }
        $physicalQty = (int) $rawPhysical;
        if ($physicalQty < 0) {
            $this->flash('error', 'Physical count cannot be negative.');
            $this->redirect('?page=stock');
            return;
        }

        $db = Database::getInstance();
        StockQuantityService::ensureAdjustmentsSchema($db);

        $db->beginTransaction();
        try {
            $result = StockQuantityService::writeOffToQuantity(
                $db,
                $itemId,
                $whId,
                $physicalQty,
                Auth::id(),
                $notes,
                'physical_count'
            );
            $db->commit();
            self::clearDashboardCache($whId);

            $item = $this->itemModel->find($itemId);
            $name = (string) ($item['name'] ?? ('item #' . $itemId));

            $this->logActivity(
                'stock_writeoff_physical',
                'stock',
                $itemId,
                'Physical write-off "' . $name . '": qty '
                . (int) $result['before'] . ' → ' . (int) $result['after']
                . ' (wrote off ' . (int) $result['written_off'] . ')'
            );

            if (!empty($result['rebuilt_only'])) {
                $this->flash(
                    'success',
                    $name . ': stock qty was stale. Rebuilt to ' . (int) $result['after'] . '.'
                );
            } else {
                $this->flash(
                    'success',
                    $name . ': wrote off ' . (int) $result['written_off']
                    . ' missing unit(s). Qty is now ' . (int) $result['after'] . '.'
                );
            }
        } catch (Exception $e) {
            $db->rollbackQuiet();
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('?page=stock');
    }
}
