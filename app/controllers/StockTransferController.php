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
        $warehouses = self::getWarehouses();
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

        if (!Auth::isAdmin() && Auth::warehouseId() !== $fromWh && Auth::warehouseId() !== $toWh) {
            $this->flash('error', 'You can only transfer from or to your assigned warehouse.');
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

                $itemInfo = $db->fetchOne("SELECT name, has_imei, COALESCE(imei_optional,0) as imei_optional FROM items WHERE id = ?", [$itemId]);
                if (!$itemInfo) throw new Exception("Invalid item ID {$itemId}.");

                $imeis = [];
                if (!empty($row['imeis'])) {
                    $imeis = array_filter(array_map('trim', explode("\n", $row['imeis'])));
                    $imeis = array_values(array_unique($imeis));
                }

                if ($itemInfo['has_imei'] && empty($itemInfo['imei_optional'])) {
                    if (count($imeis) !== $qty) {
                        throw new Exception("Item \"{$itemInfo['name']}\": scanned {$qty} IMEIs required, got " . count($imeis) . ".");
                    }
                }

                $stiId = $db->insert(
                    "INSERT INTO stock_transfer_items (transfer_id, item_id, quantity) VALUES (?,?,?)",
                    [$transferId, $itemId, $qty]
                );

                $affected = $db->execute(
                    "UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [$qty, $itemId, $fromWh, $qty]
                );
                if ($affected === 0) {
                    $stock = $db->fetchOne("SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $fromWh]);
                    throw new Exception("Insufficient stock for \"{$itemInfo['name']}\". Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$qty}.");
                }

                $exists = $db->fetchOne("SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ?", [$itemId, $toWh]);
                if ($exists) {
                    $db->execute("UPDATE stock SET quantity = quantity + ? WHERE item_id = ? AND warehouse_id = ?", [$qty, $itemId, $toWh]);
                } else {
                    $db->insert("INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)", [$itemId, $toWh, $qty]);
                }

                foreach ($imeis as $imei) {
                    $rec = $db->fetchOne(
                        "SELECT id, status, warehouse_id, item_id FROM imei_records WHERE imei = ?",
                        [$imei]
                    );
                    if (!$rec) throw new Exception("IMEI {$imei} not registered in system.");
                    if ((int)$rec['item_id'] !== $itemId) throw new Exception("IMEI {$imei} belongs to a different item.");
                    if ($rec['status'] !== 'in_stock') throw new Exception("IMEI {$imei} not in stock (status: {$rec['status']}).");
                    if ((int)$rec['warehouse_id'] !== $fromWh) throw new Exception("IMEI {$imei} is not in the source warehouse.");

                    $db->execute(
                        "UPDATE imei_records SET warehouse_id = ?, notes = CONCAT_WS(' | ', notes, ?) WHERE id = ?",
                        [$toWh, "Transferred via {$transNo}", $rec['id']]
                    );
                    $db->insert(
                        "INSERT INTO stock_transfer_imei (transfer_item_id, imei_id) VALUES (?,?)",
                        [$stiId, $rec['id']]
                    );
                }
            }

            $db->commit();
            $this->flash('success', "Transfer {$transNo} completed.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Transfer failed: ' . $e->getMessage());
            $this->redirect('?page=transfers&action=create');
        }

        $this->redirect('?page=transfers');
    }

    public function validateImei(): void {
        Auth::authorize('inventory', 'add');
        header('Content-Type: application/json');

        $imei   = trim($this->input('imei', '', 'get'));
        $itemId = (int) $this->input('item_id', 0, 'get');
        $whId   = (int) $this->input('warehouse_id', 0, 'get');

        if (!$imei || !$itemId || !$whId) {
            echo json_encode(['ok' => false, 'msg' => 'Missing parameters.']);
            return;
        }

        $db  = Database::getInstance();
        $rec = $db->fetchOne(
            "SELECT ir.status, ir.warehouse_id, ir.item_id, i.name as item_name
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.imei = ?",
            [$imei]
        );

        if (!$rec)                                 { echo json_encode(['ok' => false, 'msg' => 'IMEI not in system.']); return; }
        if ((int)$rec['item_id'] !== $itemId)      { echo json_encode(['ok' => false, 'msg' => "Belongs to: {$rec['item_name']}"]); return; }
        if ($rec['status'] !== 'in_stock')         { echo json_encode(['ok' => false, 'msg' => "Status: {$rec['status']}"]); return; }
        if ((int)$rec['warehouse_id'] !== $whId)   { echo json_encode(['ok' => false, 'msg' => 'Not in source warehouse.']); return; }

        echo json_encode(['ok' => true, 'msg' => '✓ Valid']);
    }
}