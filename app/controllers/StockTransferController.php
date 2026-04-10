<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';

class StockTransferController extends BaseController {

    public function index(): void {
        Auth::authorize('inventory', 'view');
        $db        = Database::getInstance();
        $transfers = $db->fetchAll(
            "SELECT st.*, fw.name as from_warehouse, tw.name as to_warehouse, u.name as created_by_name
             FROM stock_transfers st
             JOIN warehouses fw ON fw.id = st.from_warehouse_id
             JOIN warehouses tw ON tw.id = st.to_warehouse_id
             LEFT JOIN users u ON u.id = st.created_by
             ORDER BY st.created_at DESC
             LIMIT 200"
        );
        $pageTitle = 'Stock Transfers';
        $page      = 'transfers';

        ob_start();
        include __DIR__ . '/../views/inventory/transfers.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('inventory', 'add');
        $itemModel  = new Item();
        $warehouses = $itemModel->getWarehouses();
        $items      = $itemModel->getAllWithStock();
        $pageTitle  = 'New Transfer';
        $page       = 'transfers';

        ob_start();
        include __DIR__ . '/../views/inventory/transfer_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('inventory', 'add');
        if (!$this->isPost()) { $this->redirect('?page=transfers'); }

        $db     = Database::getInstance();
        $fromWh = $this->inputInt('from_warehouse_id');
        $toWh   = $this->inputInt('to_warehouse_id');

        if ($fromWh === $toWh) {
            $this->flash('error', 'Source and destination warehouse cannot be the same.');
            $this->redirect('?page=transfers&action=create');
        }

        $last    = $db->fetchOne("SELECT transfer_no FROM stock_transfers ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num     = $last ? (int) substr($last['transfer_no'], strlen(TRANSFER_PREFIX)) : 0;
        $transNo = TRANSFER_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

        $db->beginTransaction();
        try {
            $transferId = $db->insert(
                "INSERT INTO stock_transfers (transfer_no, from_warehouse_id, to_warehouse_id, date, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?)",
                [$transNo, $fromWh, $toWh, $this->input('date') ?: date('Y-m-d'), 'completed', $this->input('notes'), Auth::id()]
            );

            foreach ($_POST['items'] ?? [] as $row) {
                if (empty($row['item_id']) || empty($row['quantity'])) continue;
                $qty    = (int) $row['quantity'];
                $itemId = (int) $row['item_id'];

                $db->insert(
                    "INSERT INTO stock_transfer_items (transfer_id, item_id, quantity) VALUES (?,?,?)",
                    [$transferId, $itemId, $qty]
                );

                // Deduct from source (atomic check prevents negative stock)
                $affected = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$qty, $itemId, $fromWh, $qty]
                );
                if ($affected === 0) {
                    $stock = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $fromWh]);
                    $itemName = $db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
                    throw new Exception("Insufficient stock for \"{$itemName['name']}\". Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$qty}.");
                }

                // Add to destination (create row if not exists)
                $exists = $db->fetchOne("SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $toWh]);
                if ($exists) {
                    $db->execute("UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?", [$qty, $itemId, $toWh]);
                } else {
                    $db->insert("INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)", [$itemId, $toWh, $qty]);
                }
            }

            $db->commit();
            $this->flash('success', "Transfer {$transNo} completed.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Transfer failed: ' . $e->getMessage());
        }

        $this->redirect('?page=transfers');
    }
}
