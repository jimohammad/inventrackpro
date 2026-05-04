<?php

require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../../config/app.php';

/**
 * Base Controller
 * All controllers extend this
 */
abstract class BaseController {

    private static ?array $settingsCache  = null;
    private static ?array $accountsCache  = null;
    private static ?array $warehousesCache = null;

    public static function getSettings(): array {
        if (self::$settingsCache === null) {
            $db   = Database::getInstance();
            $rows = $db->fetchAll("SELECT key_name, value FROM settings");
            self::$settingsCache = [];
            foreach ($rows as $r) {
                self::$settingsCache[$r['key_name']] = $r['value'];
            }
        }
        return self::$settingsCache;
    }

    /** Active accounts — cached once per request (near-static table). */
    public static function getAccounts(): array {
        if (self::$accountsCache === null) {
            $db = Database::getInstance();
            self::$accountsCache = $db->fetchAll(
                "SELECT id, name, type, current_balance, is_default, sort_order
                 FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
            );
            foreach (self::$accountsCache as &$acc) {
                $acc['normalized_type'] = self::normalizeAccountType(
                    (string)($acc['type'] ?? ''),
                    (string)($acc['name'] ?? '')
                );
            }
            unset($acc);
        }
        return self::$accountsCache;
    }

    /**
     * Normalize legacy/special account names into behavior types used by payment flows.
     */
    protected static function normalizeAccountType(string $type, string $name = ''): string {
        $normalized = strtolower(trim($type));
        $nameKey    = strtolower(trim($name));
        if ($nameKey === 'wamd cbk') {
            return 'bank';
        }
        return $normalized !== '' ? $normalized : 'cash';
    }

    /** Active warehouses — cached once per request (rarely changes). */
    public static function getWarehouses(): array {
        if (self::$warehousesCache === null) {
            $db = Database::getInstance();
            self::$warehousesCache = $db->fetchAll(
                "SELECT id, name, is_default FROM warehouses WHERE is_active = 1 ORDER BY name ASC"
            );
        }
        return self::$warehousesCache;
    }

    public function __construct() {
        Auth::startSession();
        $page = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));
        if (!Auth::isPublicPage($page)) {
            Auth::required();
        }

        // Auto CSRF check on every POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifyCsrf()) {
                $this->flash('error', 'Invalid request. Please try again.');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? APP_URL));
                exit;
            }
        }
    }

    // Render a view file and pass data to it
    protected function renderView(string $viewPath, array $data = []): void {
        extract($data); // makes $data keys available as variables in the view
        $file = __DIR__ . '/../views/' . $viewPath . '.php';

        if (!file_exists($file)) {
            die("View not found: {$viewPath}");
        }

        include $file;
    }

    // Return JSON response (for AJAX calls)
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Redirect to a URL
    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    // Set flash message for next page load
    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    // Get and clear flash message
    public static function getFlash(): array|null {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    // Sanitize input
    protected function input(string $key, string $default = '', string $from = 'post'): string {
        $source = $from === 'get' ? $_GET : $_POST;
        $value  = $source[$key] ?? $default;
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Plain search/query string for use in SQL LIKE with bound parameters only.
     * Do not HTML-escape here (that breaks matching and belongs on output).
     */
    protected function inputSearch(string $key, string $default = '', string $from = 'get', int $maxLen = 160): string {
        $source = $from === 'get' ? $_GET : $_POST;
        $value  = trim((string) ($source[$key] ?? $default));
        if ($maxLen > 0) {
            $value = function_exists('mb_substr')
                ? mb_substr($value, 0, $maxLen, 'UTF-8')
                : substr($value, 0, $maxLen);
        }
        return $value;
    }

    // Get integer input
    protected function inputInt(string $key, int $default = 0, string $from = 'post'): int {
        $source = $from === 'get' ? $_GET : $_POST;
        return (int) ($source[$key] ?? $default);
    }

    // Get float input
    protected function inputFloat(string $key, float $default = 0.0, string $from = 'post'): float {
        $source = $from === 'get' ? $_GET : $_POST;
        return (float) ($source[$key] ?? $default);
    }

    // Validate required fields - returns array of errors
    protected function validate(array $rules): array {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $_POST[$field] ?? '';
            if ($rule === 'required' && empty(trim($value))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        return $errors;
    }

    // Check request method
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    // Log user activity
    protected function logActivity(string $action, string $module = '', int $refId = 0, string $description = ''): void {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO activity_log (user_id, action, module, ref_id, description, ip_address) VALUES (?,?,?,?,?,?)",
            [Auth::id(), $action, $module, $refId, $description, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    }
}
