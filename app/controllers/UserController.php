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
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }
        $pageTitle = 'New User';
        $page      = 'users';
        ob_start();
        include __DIR__ . '/../views/settings/user_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }
        if (!$this->isPost()) { $this->redirect('?page=users'); }

        $db   = Database::getInstance();
        $pass = Auth::hashPassword($this->input('password'));
        $role = $this->normalizeUserRole($this->input('role'), []);

        $userId = 0;
        try {
            $db->beginTransaction();
            $userId = (int) $db->insert(
                "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)",
                [$this->input('name'), $this->input('email'), $pass, $role]
            );
            if ($userId <= 0) {
                throw new RuntimeException('User insert did not return an id.');
            }
            $this->savePermissions($db, $userId);
            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $ignored) {
            }
            error_log('UserController::store failed: ' . $e->getMessage() . $this->pdoErrorSuffix($e));
            $this->flash('error', $this->userSaveErrorMessage($e, true));
            $this->redirect('?page=users&action=create');
        }

        $this->flash('success', 'User created successfully.');
        $this->redirect('?page=users');
    }

    public function edit(): void {
        Auth::authorize('settings', 'edit');
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }
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
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }
        if (!$this->isPost()) { $this->redirect('?page=users'); }

        // Form action puts id in the URL (?action=update&id=N); POST body may omit it.
        $id = $this->inputInt('id', 0, 'post') ?: $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $existing = $db->fetchOne("SELECT id, role FROM users WHERE id = ?", [$id]);
        if (!$existing) { $this->flash('error', 'User not found.'); $this->redirect('?page=users'); }

        // Update basic info (role must match ENUM; never use invalid placeholder like "user")
        $fields = "name = ?, email = ?, role = ?";
        $role    = $this->normalizeUserRole($this->input('role'), $existing);
        $params  = [$this->input('name'), $this->input('email'), $role];

        // Only update password if provided
        $pass = $this->input('password');
        if (!empty($pass)) {
            $fields  .= ", password = ?";
            $params[] = Auth::hashPassword($pass);
        }
        $params[] = $id;

        try {
            $db->beginTransaction();
            $db->execute("UPDATE users SET {$fields} WHERE id = ?", $params);
            $this->savePermissions($db, $id);
            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $ignored) {
            }
            error_log('UserController::update failed: ' . $e->getMessage() . $this->pdoErrorSuffix($e));
            $this->flash('error', $this->userSaveErrorMessage($e));
            $this->redirect('?page=users&action=edit&id=' . $id);
        }

        // Refresh session permissions if editing self
        if ((int) $id === (int) Auth::id()) {
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
            $_SESSION['user_role']   = $role;
        }

        $this->flash('success', 'User updated successfully.');
        $this->redirect('?page=users');
    }

    public function toggleStatus(): void {
        Auth::authorize('settings', 'edit');
        if (!Auth::isAdmin()) {
            $this->redirect('?page=dashboard');
        }
        if (!$this->isPost()) {
            $this->redirect('?page=users');
            return;
        }

        $id = $this->inputInt('id', 0, 'post') ?: $this->inputInt('id', 0, 'get');
        $selfId = (int) Auth::id();
        if ($id <= 0 || ($selfId > 0 && $id === $selfId)) {
            $this->flash('error', 'Invalid user or you cannot change your own status here.');
            $this->redirect('?page=users');
        }

        $db = Database::getInstance();
        $u  = $db->fetchOne('SELECT id, is_active FROM users WHERE id = ?', [$id]);
        if (!$u) {
            $this->flash('error', 'User not found.');
            $this->redirect('?page=users');
        }

        $cur = (int) ($u['is_active'] ?? 0);
        $new = $cur === 1 ? 0 : 1;
        $db->execute('UPDATE users SET is_active = ? WHERE id = ?', [$new, $id]);

        $this->flash('success', $new === 1 ? 'User activated.' : 'User deactivated.');
        $this->redirect('?page=users');
    }

    /**
     * Save permission rows without INSERT…ON DUPLICATE…VALUES() (unsupported or broken on some MariaDB builds).
     */
    private function savePermissions(object $db, int $userId): void {
        if ($userId <= 0) {
            return;
        }

        $perms   = $_POST['perms'] ?? [];
        $modules = [
            'dashboard', 'sales', 'purchases', 'returns', 'inventory', 'stock', 'payments', 'expenses',
            'customers', 'suppliers', 'reports', 'imei', 'service', 'warranty', 'supplier_contacts',
            'mandoob_inventory', 'settings',
            'rpt_daybook', 'rpt_sales', 'rpt_profit', 'rpt_stock', 'rpt_payments', 'rpt_party', 'rpt_item_sales',
            'rpt_reconciliation', 'rpt_account_stmt', 'rpt_expenses', 'rpt_sales_returns', 'rpt_supplier_stmt',
            'rpt_balance_sheet', 'rpt_customer_imei',
        ];

        foreach ($modules as $mod) {
            $v = isset($perms[$mod]['view'])   ? 1 : 0;
            $a = isset($perms[$mod]['add'])    ? 1 : 0;
            $e = isset($perms[$mod]['edit'])   ? 1 : 0;
            $d = isset($perms[$mod]['delete']) ? 1 : 0;

            $row = $db->fetchOne(
                'SELECT id FROM permissions WHERE user_id = ? AND module = ?',
                [$userId, $mod]
            );
            if ($row) {
                $db->execute(
                    'UPDATE permissions SET can_view = ?, can_add = ?, can_edit = ?, can_delete = ? WHERE id = ?',
                    [$v, $a, $e, $d, (int) ($row['id'] ?? 0)]
                );
            } else {
                $db->execute(
                    'INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete) VALUES (?,?,?,?,?,?)',
                    [$userId, $mod, $v, $a, $e, $d]
                );
            }
        }
    }

    /** @param array<string,mixed> $existingUser row with optional `role` */
    private function normalizeUserRole(string $fromForm, array $existingUser): string {
        $allowed = ['admin', 'manager', 'cashier', 'viewer'];
        $t       = strtolower(trim($fromForm));
        if (in_array($t, $allowed, true)) {
            return $t;
        }
        $current = strtolower(trim((string) ($existingUser['role'] ?? '')));
        if (in_array($current, $allowed, true)) {
            return $current;
        }

        return 'cashier';
    }

    private function pdoErrorSuffix(Throwable $e): string {
        if (!$e instanceof PDOException) {
            return '';
        }
        $info = $e->errorInfo ?? [];
        $sqlState = (string) ($info[0] ?? '');
        $driver   = (string) ($info[1] ?? '');
        $msg      = (string) ($info[2] ?? '');

        return ' | SQLSTATE=' . $sqlState . ' driver=' . $driver . ' info=' . $msg;
    }

    private function userSaveErrorMessage(Throwable $e, bool $creating = false): string {
        if ($e instanceof PDOException) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if ($code === 1062) {
                return 'That email address is already used by another user.';
            }
            if ($code === 1265) {
                return 'Could not save user (invalid data for role or another field).';
            }
            $driverMsg = trim((string) ($e->errorInfo[2] ?? ''));
            if ($driverMsg === '') {
                $driverMsg = trim($e->getMessage());
            }
            if ($driverMsg !== '' && strlen($driverMsg) < 400) {
                return 'Save failed: ' . $driverMsg;
            }
        }
        $msg = $e->getMessage();
        if ($msg !== '' && str_contains($msg, 'Duplicate entry')) {
            return 'That email address is already used by another user.';
        }

        return $creating
            ? 'Could not create user. Please try again.'
            : 'Could not save user. Please try again.';
    }
}
