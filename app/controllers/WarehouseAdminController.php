<?php

require_once __DIR__ . '/BaseController.php';

class WarehouseAdminController extends BaseController {

    // List all warehouses
    public function index(): void {
        Auth::authorize('settings', 'view');
        if (!Auth::isAdmin()) $this->redirect('?page=dashboard');

        $db = Database::getInstance();
        $warehouses = $db->fetchAll(
            "SELECT w.*, COUNT(wu.user_id) as user_count
             FROM warehouses w
             LEFT JOIN warehouse_users wu ON wu.warehouse_id = w.id
             GROUP BY w.id
             ORDER BY w.is_default DESC, w.name ASC"
        );

        $pageTitle = 'Warehouses';
        $page      = 'warehouses';
        ob_start();
        include __DIR__ . '/../views/settings/warehouses.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Show create form
    public function create(): void {
        Auth::authorize('settings', 'add');
        if (!Auth::isAdmin()) $this->redirect('?page=dashboard');

        $pageTitle = 'New Warehouse';
        $page      = 'warehouses';
        ob_start();
        include __DIR__ . '/../views/settings/warehouse_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Save new warehouse
    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) $this->redirect('?page=warehouses');

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO warehouses (name, location, phone, notes, is_default, is_active)
             VALUES (?,?,?,?,?,1)",
            [
                $this->input('name'),
                $this->input('location'),
                $this->input('phone'),
                $this->input('notes'),
                $this->input('is_default') ? 1 : 0,
            ]
        );

        // If set as default, unset others
        if ($this->input('is_default')) {
            $db->execute(
                "UPDATE warehouses SET is_default = 0 WHERE id != LAST_INSERT_ID()"
            );
        }

        $this->flash('success', 'Warehouse created.');
        $this->redirect('?page=warehouses');
    }

    // Show edit form
    public function edit(): void {
        Auth::authorize('settings', 'edit');
        if (!Auth::isAdmin()) $this->redirect('?page=dashboard');

        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();
        $warehouse = $db->fetchOne("SELECT * FROM warehouses WHERE id = ?", [$id]);
        if (!$warehouse) { $this->flash('error', 'Warehouse not found.'); $this->redirect('?page=warehouses'); }

        // Get assigned users
        $assignedUsers = $db->fetchAll(
            "SELECT u.id, u.name, u.email, u.role
             FROM warehouse_users wu
             JOIN users u ON u.id = wu.user_id
             WHERE wu.warehouse_id = ?
             ORDER BY u.name ASC",
            [$id]
        );

        // Get all users not assigned to this warehouse
        $assignedIds = array_column($assignedUsers, 'id');
        $allUsers    = $db->fetchAll(
            "SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY name ASC"
        );
        $availableUsers = array_filter($allUsers, fn($u) => !in_array($u['id'], $assignedIds));

        $pageTitle = 'Edit Warehouse';
        $page      = 'warehouses';
        $editMode  = true;
        ob_start();
        include __DIR__ . '/../views/settings/warehouse_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update warehouse
    public function update(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) $this->redirect('?page=warehouses');

        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $isDefault = $this->input('is_default') ? 1 : 0;

        $db->execute(
            "UPDATE warehouses SET name=?, location=?, phone=?, notes=?, is_default=? WHERE id=?",
            [
                $this->input('name'),
                $this->input('location'),
                $this->input('phone'),
                $this->input('notes'),
                $isDefault,
                $id
            ]
        );

        if ($isDefault) {
            $db->execute("UPDATE warehouses SET is_default = 0 WHERE id != ?", [$id]);
            $db->execute("UPDATE warehouses SET is_default = 1 WHERE id = ?",  [$id]);
        }

        $this->flash('success', 'Warehouse updated.');
        $this->redirect('?page=warehouses&action=edit&id=' . $id);
    }

    // Toggle active status
    public function toggleStatus(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=warehouses');
            return;
        }
        $id = $this->inputInt('id');
        $db = Database::getInstance();
        $w  = $db->fetchOne("SELECT is_active FROM warehouses WHERE id = ?", [$id]);
        if ($w) {
            $db->execute("UPDATE warehouses SET is_active = ? WHERE id = ?", [$w['is_active'] ? 0 : 1, $id]);
        }
        $this->flash('success', 'Warehouse status updated.');
        $this->redirect('?page=warehouses');
    }

    // Assign user to warehouse
    public function assignUser(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) $this->redirect('?page=warehouses');

        $warehouseId = $this->inputInt('warehouse_id');
        $userId      = $this->inputInt('user_id');
        $db          = Database::getInstance();

        $db->execute(
            "INSERT IGNORE INTO warehouse_users (warehouse_id, user_id) VALUES (?,?)",
            [$warehouseId, $userId]
        );

        $this->flash('success', 'User assigned to warehouse.');
        $this->redirect('?page=warehouses&action=edit&id=' . $warehouseId);
    }

    // Remove user from warehouse
    public function removeUser(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=warehouses');
            return;
        }
        $warehouseId = $this->inputInt('warehouse_id');
        $userId      = $this->inputInt('user_id');
        $db          = Database::getInstance();

        $db->execute(
            "DELETE FROM warehouse_users WHERE warehouse_id = ? AND user_id = ?",
            [$warehouseId, $userId]
        );

        $this->flash('success', 'User removed from warehouse.');
        $this->redirect('?page=warehouses&action=edit&id=' . $warehouseId);
    }
}
