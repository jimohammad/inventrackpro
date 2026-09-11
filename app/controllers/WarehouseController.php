<?php

require_once __DIR__ . '/BaseController.php';

class WarehouseController extends BaseController {

    // Show warehouse selector (skipped when only one operational branch / switcher disabled)
    public function index(): void {
        Auth::required();

        $switcherOn = defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER;
        $switching  = $switcherOn && isset($_GET['switch']);

        if (Auth::warehouseId() && !$switching) {
            $this->redirect(APP_URL . '/?page=dashboard');
        }

        // Main-only UX: never block login on the picker.
        if (!$switcherOn && !$switching) {
            try {
                Auth::autoSelectOperationalWarehouse();
            } catch (Throwable $e) {
                error_log('[ERP] warehouse page auto-select: ' . $e->getMessage());
                Auth::setWarehouse(1, 'Main Branch');
            }
            if (Auth::warehouseId()) {
                $this->redirect(APP_URL . '/?page=dashboard');
            }
        }

        $db = Database::getInstance();
        $warehouses = [];
        try {
            if (Auth::isAdmin()) {
                $warehouses = $db->fetchAll(
                    "SELECT id, name, location FROM warehouses WHERE is_active = 1 ORDER BY name ASC"
                );
            } else {
                $warehouses = $db->fetchAll(
                    "SELECT w.id, w.name, w.location FROM warehouses w
                     JOIN warehouse_users wu ON wu.warehouse_id = w.id
                     WHERE wu.user_id = ? AND w.is_active = 1
                     ORDER BY w.name ASC",
                    [Auth::id()]
                );
            }
        } catch (Throwable $e) {
            error_log('[ERP] warehouse list: ' . $e->getMessage());
        }

        if (!$switcherOn || count($warehouses) === 1) {
            $pick = $this->preferOperationalWarehouse($warehouses);
            if ($pick !== null) {
                Auth::setWarehouse((int) $pick['id'], (string) $pick['name']);
                $this->redirect(APP_URL . '/?page=dashboard');
            }
        }

        include __DIR__ . '/../views/warehouse_select.php';
    }

    // Handle warehouse selection
    public function select(): void {
        Auth::required();

        if (!$this->isPost()) {
            $this->redirect('?page=warehouse');
        }

        // Switcher disabled — always land on preferred operational branch
        if (!(defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER)) {
            $this->redirect('?page=warehouse');
            return;
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

    /**
     * Prefer Main (id=1) when present among active warehouses; otherwise first row.
     *
     * @param list<array<string, mixed>> $warehouses
     * @return array<string, mixed>|null
     */
    private function preferOperationalWarehouse(array $warehouses): ?array {
        if ($warehouses === []) {
            return null;
        }
        foreach ($warehouses as $wh) {
            if ((int) ($wh['id'] ?? 0) === 1) {
                return $wh;
            }
        }
        return $warehouses[0];
    }
}
