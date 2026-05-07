<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Auth Helper
 * Handles login, logout, session, and permission checks
 */
class Auth {

    // Start a secure session
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Ensure our custom session directory exists
            $sessDir = '/tmp/inventrackpro_sessions';
            if (!is_dir($sessDir)) {
                mkdir($sessDir, 0700, true);
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
                'samesite' => 'Strict'
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
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Load permissions into session
        $perms = $db->fetchAll("SELECT * FROM permissions WHERE user_id = ?", [$user['id']]);
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

        // Update last login timestamp
        $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        return true;
    }

    // Logout
    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    // Check if user is logged in
    public static function check(): bool {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Router pages that skip login (must stay aligned with index.php public routing).
     * Controllers extend BaseController, which also checks this before Auth::required().
     */
    public static function isPublicPage(string $page): bool {
        $page = preg_replace('/[^a-z0-9_]/', '', strtolower($page));
        return in_array($page, ['login', 'logout', 'fieldstatement', 'servicetrack', 'imeitrack'], true);
    }

    // Redirect to login if not authenticated
    public static function required(): void {
        if (!self::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: ' . APP_URL . '/index.php?page=login&redirect=' . $redirect);
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

    // Get user role
    public static function role(): string {
        return $_SESSION['user_role'] ?? '';
    }

    // Check if user is admin
    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }

    // Check specific permission
    public static function can(string $module, string $action = 'view'): bool {
        if (self::isAdmin()) return true; // admin can do everything

        $perms = $_SESSION['permissions'] ?? [];
        return isset($perms[$module][$action]) && $perms[$module][$action] === true;
    }

    // Deny access if no permission (show 403 page)
    public static function authorize(string $module, string $action = 'view'): void {
        if (!self::can($module, $action)) {
            http_response_code(403);
            include __DIR__ . '/../../app/views/errors/403.php';
            exit;
        }
    }

    // Hash a password
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // CSRF Token
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF Token
    public static function verifyCsrf(): bool {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
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
    }

    // Clear warehouse from session (force re-select)
    public static function clearWarehouse() {
        unset($_SESSION['warehouse_id'], $_SESSION['warehouse_name']);
    }

    // Redirect to warehouse selector if no warehouse chosen
    public static function requireWarehouse() {
        if (empty($_SESSION['warehouse_id'])) {
            header('Location: ' . APP_URL . '/?page=warehouse');
            exit;
        }
    }
}
