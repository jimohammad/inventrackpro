<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Auth Helper
 * Handles login, logout, session, and permission checks
 */
class Auth {

    /** Set when check() clears a session because the calendar day changed (APP_TIMEZONE). */
    private static bool $sessionExpiredNewDay = false;

    /** Ensures permissions are reloaded from DB at most once per request. */
    private static bool $permissionsRefreshedThisRequest = false;

    /** Calendar date (Y-m-d) used to invalidate sessions after midnight. */
    private static function sessionDateToday(): string {
        return date('Y-m-d');
    }

    /** True when session was opened on the current calendar day. */
    private static function isSessionDateCurrent(): bool {
        $stored = (string) ($_SESSION['session_date'] ?? '');
        return $stored !== '' && $stored === self::sessionDateToday();
    }

    public static function sessionExpiredForNewDay(): bool {
        return self::$sessionExpiredNewDay;
    }

    // Start a secure session
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $sessDir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'inventrackpro_sessions';
            if (!is_dir($sessDir)) {
                @mkdir($sessDir, 0700, true);
            }
            if (is_dir($sessDir) && is_writable($sessDir)) {
                session_save_path($sessDir);
            }

            session_name(SESSION_NAME);
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || (($_SERVER['HTTP_CF_VISITOR'] ?? '') && str_contains((string) $_SERVER['HTTP_CF_VISITOR'], 'https'));
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                // Lax: Chrome sends the new session cookie on the POST→redirect
                // after Sign In. Strict often drops it, so Firefox stays in and Chrome does not.
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    // Login - returns true on success, string error on failure
    public static function login(string $email, string $password): bool|string {
        $db   = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

        if (!$user) {
            return 'Invalid email or password.';
        }

        if (!password_verify($password, $user['password'])) {
            return 'Invalid email or password.';
        }

        // Prevent session fixation: regenerate immediately after successful auth,
        // before writing any user data into the session.
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = (string) ($user['email'] ?? '');
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['logged_in']  = true;
        $_SESSION['session_date'] = self::sessionDateToday();

        self::loadPermissionsIntoSession((int) $user['id']);
        self::$permissionsRefreshedThisRequest = true;

        // Update last login timestamp
        $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        // Flush Set-Cookie before the 302. Chrome may follow the redirect before
        // it stores a cookie that is still in the PHP output buffer.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return true;
    }

    /**
     * Load (or reload) the user's permission rows into the session.
     * Called on login and when the 2-minute session cache expires (admin edits apply shortly after).
     */
    public static function loadPermissionsIntoSession(int $userId): void {
        if ($userId <= 0) {
            $_SESSION['permissions'] = [];
            $_SESSION['permissions_loaded_at'] = time();
            return;
        }
        try {
            $db = Database::getInstance();
            $perms = $db->fetchAll(
                "SELECT module, can_view, can_add, can_edit, can_delete
                 FROM permissions
                 WHERE user_id = ?",
                [$userId]
            );
            $permMap = [];
            foreach ($perms as $p) {
                $mod = trim((string) ($p['module'] ?? ''));
                if ($mod === '') {
                    continue;
                }
                $permMap[$mod] = [
                    'view'   => (bool) $p['can_view'],
                    'add'    => (bool) $p['can_add'],
                    'edit'   => (bool) $p['can_edit'],
                    'delete' => (bool) $p['can_delete'],
                ];
            }
            $_SESSION['permissions'] = $permMap;
            $_SESSION['permissions_loaded_at'] = time();
        } catch (Throwable $e) {
            error_log('[ERP] permissions load: ' . $e->getMessage());
            $_SESSION['permissions'] = $_SESSION['permissions'] ?? [];
            $_SESSION['permissions_loaded_at'] = time();
        }
    }

    /** Refresh session permissions from DB at most every 2 minutes (skip for admin). */
    private static function ensurePermissionsFresh(): void {
        if (self::$permissionsRefreshedThisRequest) {
            return;
        }
        self::$permissionsRefreshedThisRequest = true;
        if (self::isAdmin()) {
            return;
        }
        $loadedAt = (int) ($_SESSION['permissions_loaded_at'] ?? 0);
        if ($loadedAt > 0
            && (time() - $loadedAt) < 120
            && isset($_SESSION['permissions'])
            && is_array($_SESSION['permissions'])
        ) {
            return;
        }
        $uid = self::id();
        if ($uid) {
            self::loadPermissionsIntoSession((int) $uid);
        }
    }

    /** True if master Reports or any individual rpt_* view permission is granted. */
    public static function hasAnyReportAccess(): bool {
        if (self::can('reports', 'view')) {
            return true;
        }
        $perms = $_SESSION['permissions'] ?? [];
        foreach ($perms as $mod => $actions) {
            if (str_starts_with((string) $mod, 'rpt_') && !empty($actions['view'])) {
                return true;
            }
        }
        return false;
    }

    // Logout
    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    // Check if user is logged in (sessions expire when the calendar day changes)
    public static function check(): bool {
        self::startSession();
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        try {
            if (!self::isSessionDateCurrent()) {
                self::$sessionExpiredNewDay = true;
                self::logout();
                return false;
            }
            self::ensurePermissionsFresh();
        } catch (Throwable $e) {
            error_log('[ERP] Auth::check: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Router pages that skip login (must stay aligned with index.php public routing).
     * Controllers extend BaseController, which also checks this before Auth::required().
     */
    public static function isPublicPage(string $page): bool {
        $page = preg_replace('/[^a-z0-9_]/', '', strtolower($page)) ?? '';
        return in_array($page, ['login', 'logout', 'fieldstatement', 'servicetrack', 'imeitrack', 'appshub', 'appsorder', 'verify', 'podocsverify', 'paymentverify'], true);
    }

    // Redirect to login if not authenticated
    public static function required(): void {
        if (!self::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            $reason   = self::$sessionExpiredNewDay ? '&reason=new_day' : '';
            header('Location: ' . APP_URL . '/index.php?page=login&redirect=' . $redirect . $reason);
            exit;
        }
    }

    // Get currently logged in user ID
    public static function id(): int|null {
        return $_SESSION['user_id'] ?? null;
    }

    // Get currently logged in user name
    public static function name(): string {
        return $_SESSION['user_name'] ?? 'Unknown';
    }

    /** Login email (session only — no DB on page loads). */
    public static function email(): string {
        return (string) ($_SESSION['user_email'] ?? '');
    }

    /**
     * Default print layout for receipts/invoices (session override).
     * Values: a5 | thermal. jimohammad@gmail.com defaults to thermal when not explicitly set.
     */
    public static function printTemplate(): string {
        if (isset($_SESSION['print_template'])) {
            $tpl = (string) $_SESSION['print_template'];
            return in_array($tpl, ['a5', 'thermal'], true) ? $tpl : 'a5';
        }
        $em = (string) ($_SESSION['user_email'] ?? '');
        if (strcasecmp($em, 'jimohammad@gmail.com') === 0) {
            return 'thermal';
        }
        return 'a5';
    }

    // Get user role
    public static function role(): string {
        return $_SESSION['user_role'] ?? '';
    }

    // Check if user is admin
    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }

    /** Shop-floor roles (salesman/cashier and view-only) — never browse suppliers. */
    public static function isSalesFloor(): bool {
        return in_array(self::role(), ['cashier', 'viewer'], true);
    }

    // Check specific permission
    public static function can(string $module, string $action = 'view'): bool {
        if (self::isAdmin()) return true; // admin can do everything

        // Supplier directory is not for salesman/cashier even if a checkbox was left on.
        if (self::isSalesFloor() && in_array($module, ['suppliers', 'supplier_contacts', 'rpt_supplier_stmt'], true)) {
            return false;
        }

        $perms = $_SESSION['permissions'] ?? [];
        return isset($perms[$module][$action]) && $perms[$module][$action] === true;
    }

    /**
     * Party autocomplete type. Salesman may only search customers (not suppliers / freight).
     */
    public static function sanitizePartySearchType(string $type): string {
        if (in_array($type, ['supplier', 'freight_forwarder'], true) && !self::can('suppliers', 'view')) {
            return 'customer';
        }
        if ($type === 'all' && !self::can('suppliers', 'view')) {
            return 'customer';
        }
        if ($type === 'purchase' && !self::can('purchases', 'view')) {
            return 'customer';
        }
        if (in_array($type, ['payment_out', 'payment_out'], true) && !self::can('payments_out', 'view')) {
            return 'customer';
        }
        return $type;
    }

    /** True if the user has the action on any of the given modules. */
    public static function canAny(array $modules, string $action = 'view'): bool {
        foreach ($modules as $module) {
            if (self::can((string) $module, $action)) {
                return true;
            }
        }
        return false;
    }

    // Deny access if no permission (show 403 page)
    public static function authorize(string $module, string $action = 'view'): void {
        if (!self::can($module, $action)) {
            http_response_code(403);
            include __DIR__ . '/../../app/views/errors/403.php';
            exit;
        }
    }

    /** Deny access unless the user has the action on at least one module. */
    public static function authorizeAny(array $modules, string $action = 'view'): void {
        if (self::canAny($modules, $action)) {
            return;
        }
        $first = (string) ($modules[0] ?? 'dashboard');
        self::authorize($first, $action);
    }

    // Hash a password
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // CSRF Token
    public static function csrfToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF Token
    public static function verifyCsrf(): bool {
        $token   = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';
        // SEC2 fix: refuse empty-vs-empty match. hash_equals('','') returns true and would
        // let a POST without a token pass on a fresh session that has never rendered a form.
        if ($token === '' || $session === '') {
            return false;
        }
        return hash_equals($session, $token);
    }

    // Print hidden CSRF input field — use inside every form
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    // ── Warehouse ──────────────────────────────────────────

    // Get current session warehouse ID
    public static function warehouseId() {
        return isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : null;
    }

    // Get current session warehouse name
    public static function warehouseName() {
        return isset($_SESSION['warehouse_name']) ? $_SESSION['warehouse_name'] : 'No Warehouse';
    }

    // Set warehouse in session
    public static function setWarehouse($id, $name) {
        $_SESSION['warehouse_id']   = $id;
        $_SESSION['warehouse_name'] = $name;
        $_SESSION['warehouse_is_active'] = 1;
        $_SESSION['warehouse_verified_at'] = time();
    }

    // Clear warehouse from session (force re-select)
    public static function clearWarehouse() {
        unset(
            $_SESSION['warehouse_id'],
            $_SESSION['warehouse_name'],
            $_SESSION['warehouse_is_active'],
            $_SESSION['warehouse_verified_at']
        );
    }

    /**
     * Put the operational branch in session (Main id=1 when present).
     * Used after login and when the switcher is off so the picker is never required.
     */
    public static function autoSelectOperationalWarehouse(): bool {
        if (self::warehouseId()) {
            return true;
        }
        try {
            $db = Database::getInstance();
            $wh = $db->fetchOne(
                'SELECT id, name FROM warehouses WHERE is_active = 1 ORDER BY (id = 1) DESC, id ASC LIMIT 1'
            );
            if ($wh) {
                self::setWarehouse((int) $wh['id'], (string) $wh['name']);
                return true;
            }
        } catch (Throwable $e) {
            error_log('[ERP] warehouse auto-select: ' . $e->getMessage());
        }
        self::setWarehouse(1, 'Main Branch');
        return true;
    }

    // Redirect to warehouse selector if no warehouse chosen
    public static function requireWarehouse() {
        if (empty($_SESSION['warehouse_id'])) {
            if (!(defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER)) {
                self::autoSelectOperationalWarehouse();
            }
        }
        if (empty($_SESSION['warehouse_id'])) {
            header('Location: ' . APP_URL . '/?page=warehouse');
            exit;
        }
    }

    /**
     * Logged-in branch must be operational (is_active = 1).
     * Re-validates at most once per hour; warehouse pick already checks is_active.
     */
    public static function ensureOperationalWarehouse(): void {
        $id = self::warehouseId();
        if (!$id) {
            return;
        }

        $verifiedAt = (int) ($_SESSION['warehouse_verified_at'] ?? 0);
        if (!empty($_SESSION['warehouse_is_active']) && $verifiedAt > 0 && (time() - $verifiedAt) < 86400) {
            return;
        }

        try {
            $db = Database::getInstance();
            $wh = $db->fetchOne(
                'SELECT id, name, is_active FROM warehouses WHERE id = ?',
                [$id]
            );
        } catch (Throwable $e) {
            error_log('[ERP] warehouse verify: ' . $e->getMessage());
            return;
        }
        if (!$wh || !(int) ($wh['is_active'] ?? 0)) {
            self::clearWarehouse();
            header('Location: ' . APP_URL . '/?page=warehouse');
            exit;
        }

        $_SESSION['warehouse_is_active'] = 1;
        $_SESSION['warehouse_verified_at'] = time();
    }

    /** Release session lock early so parallel tabs/AJAX are not serialized. */
    public static function releaseSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
