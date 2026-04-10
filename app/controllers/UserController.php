<?php

require_once __DIR__ . '/BaseController.php';

class UserController extends BaseController {

    public function index(): void {
        Auth::authorize('settings', 'view');
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }

        $db        = Database::getInstance();
        $users     = $db->fetchAll("SELECT id, name, email, role, is_active, last_login, created_at FROM users ORDER BY id ASC");
        $pageTitle = 'User Management';
        $page      = 'users';

        ob_start();
        include __DIR__ . '/../views/settings/users.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('settings', 'add');
        $pageTitle = 'New User';
        $page      = 'users';
        ob_start();
        include __DIR__ . '/../views/settings/user_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=users'); }

        $db   = Database::getInstance();
        $pass = Auth::hashPassword($this->input('password'));

        $userId = $db->insert(
            "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)",
            [$this->input('name'), $this->input('email'), $pass, $this->input('role')]
        );

        $this->savePermissions($db, (int)$userId);

        $this->flash('success', 'User created successfully.');
        $this->redirect('?page=users');
    }

    public function edit(): void {
        Auth::authorize('settings', 'edit');
        $id      = $this->inputInt('id', 0, 'get');
        $db      = Database::getInstance();
        $editUser = $db->fetchOne("SELECT id, name, email, role FROM users WHERE id = ?", [$id]);
        if (!$editUser) { $this->flash('error', 'User not found.'); $this->redirect('?page=users'); }

        $pageTitle = 'Edit User';
        $page      = 'users';
        ob_start();
        include __DIR__ . '/../views/settings/user_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=users'); }

        $id  = $this->inputInt('id', 0, 'get');
        $db  = Database::getInstance();

        // Update basic info
        $fields = "name = ?, email = ?, role = ?";
        $params = [$this->input('name'), $this->input('email'), $this->input('role')];

        // Only update password if provided
        $pass = $this->input('password');
        if (!empty($pass)) {
            $fields  .= ", password = ?";
            $params[] = Auth::hashPassword($pass);
        }

        $params[] = $id;
        $db->execute("UPDATE users SET {$fields} WHERE id = ?", $params);

        $this->savePermissions($db, $id);

        // Refresh session permissions if editing self
        if ($id === Auth::id()) {
            $perms = $db->fetchAll("SELECT * FROM permissions WHERE user_id = ?", [$id]);
            $permMap = [];
            foreach ($perms as $p) {
                $permMap[$p['module']] = [
                    'view'   => (bool) $p['can_view'],
                    'add'    => (bool) $p['can_add'],
                    'edit'   => (bool) $p['can_edit'],
                    'delete' => (bool) $p['can_delete'],
                ];
            }
            $_SESSION['permissions'] = $permMap;
            $_SESSION['user_role']   = $this->input('role');
        }

        $this->flash('success', 'User updated successfully.');
        $this->redirect('?page=users');
    }

    public function toggleStatus(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=users'); return; }
        $id  = $this->inputInt('id');
        $db  = Database::getInstance();
        $u   = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$id]);
        if ($u) {
            $db->execute("UPDATE users SET is_active = ? WHERE id = ?", [$u['is_active'] ? 0 : 1, $id]);
        }
        $this->flash('success', 'User status updated.');
        $this->redirect('?page=users');
    }

    private function savePermissions(object $db, int $userId): void {
        $perms   = $_POST['perms'] ?? [];
        $modules = ['dashboard','sales','purchases','returns','inventory','stock','payments','expenses','customers','suppliers','reports','warranty','settings',
                     'rpt_daybook','rpt_sales','rpt_profit','rpt_stock','rpt_payments','rpt_party','rpt_item_sales','rpt_reconciliation','rpt_account_stmt','rpt_expenses','rpt_sales_returns','rpt_supplier_stmt','rpt_balance_sheet','rpt_customer_imei'];

        foreach ($modules as $mod) {
            $v = isset($perms[$mod]['view'])   ? 1 : 0;
            $a = isset($perms[$mod]['add'])    ? 1 : 0;
            $e = isset($perms[$mod]['edit'])   ? 1 : 0;
            $d = isset($perms[$mod]['delete']) ? 1 : 0;

            $db->execute(
                "INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_add=VALUES(can_add),
                                         can_edit=VALUES(can_edit), can_delete=VALUES(can_delete)",
                [$userId, $mod, $v, $a, $e, $d]
            );
        }
    }
}
