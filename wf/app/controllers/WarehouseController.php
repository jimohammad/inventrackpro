<?php

require_once __DIR__ . '/BaseController.php';

class WarehouseController extends BaseController {

    // Show warehouse selector
    public function index(): void {
        Auth::required();

        // If already has warehouse and not switching - go to dashboard
        if (Auth::warehouseId() && !isset($_GET['switch'])) {
            $this->redirect('?page=dashboard');
        }

        $db = Database::getInstance();

        // Non-admins see only assigned warehouses; admins see all
        if (Auth::isAdmin()) {
            $warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name ASC");
        } else {
            $warehouses = $db->fetchAll(
                "SELECT w.* FROM warehouses w
                 JOIN warehouse_users wu ON wu.warehouse_id = w.id
                 WHERE wu.user_id = ? AND w.is_active = 1
                 ORDER BY w.name ASC",
                [Auth::id()]
            );
        }

        include __DIR__ . '/../views/warehouse_select.php';
    }

    // Handle warehouse selection
    public function select(): void {
        Auth::required();

        if (!$this->isPost()) {
            $this->redirect('?page=warehouse');
        }

        $id = $this->inputInt('warehouse_id');
        $db = Database::getInstance();
        $wh = $db->fetchOne("SELECT * FROM warehouses WHERE id = ? AND is_active = 1", [$id]);

        if (!$wh) {
            $this->redirect('?page=warehouse');
        }

        // Non-admin users must be assigned to the warehouse
        if (!Auth::isAdmin()) {
            $assigned = $db->fetchOne(
                "SELECT id FROM warehouse_users WHERE user_id = ? AND warehouse_id = ?",
                [Auth::id(), $id]
            );
            if (!$assigned) {
                $this->flash('error', 'You are not assigned to this warehouse.');
                $this->redirect('?page=warehouse');
            }
        }

        Auth::setWarehouse($wh['id'], $wh['name']);
        $this->redirect('?page=dashboard');
    }
}
