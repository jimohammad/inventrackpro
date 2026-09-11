<?php

require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/ListPage.php';
require_once __DIR__ . '/../helpers/ImeiFormat.php';
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
            self::migrateCompanyNameWllToLlc($db);
            self::migrateCompanyAddressSharq($db);
        }
        return self::$settingsCache;
    }

    /** Insert Sharq before Block 5 when the area is missing. One-time persist. */
    private static function migrateCompanyAddressSharq(Database $db): void {
        $addr = trim((string) (self::$settingsCache['company_address'] ?? ''));
        if ($addr === '' || preg_match('/sharq/i', $addr) || !preg_match('/\bBlock\s*5\b/i', $addr)) {
            return;
        }
        $updated = trim((string) preg_replace('/\bBlock\s*5\b/i', 'Sharq, Block 5', $addr, 1));
        if ($updated === '' || $updated === $addr) {
            return;
        }
        $db->execute(
            "INSERT INTO settings (key_name, value) VALUES ('company_address', ?)
             ON DUPLICATE KEY UPDATE value = ?",
            [$updated, $updated]
        );
        self::$settingsCache['company_address'] = $updated;
    }

    /** Legal entity suffix: WLL → LLC (Kuwait Companies Law). One-time persist. */
    private static function migrateCompanyNameWllToLlc(Database $db): void {
        $name = (string) (self::$settingsCache['company_name'] ?? '');
        if ($name === '' || !preg_match('/\bW\.?L\.?L\.?\b/i', $name)) {
            return;
        }
        $updated = trim((string) preg_replace('/\bW\.?L\.?L\.?\b/i', 'LLC', $name));
        if ($updated === '' || $updated === $name) {
            return;
        }
        $db->execute(
            "INSERT INTO settings (key_name, value) VALUES ('company_name', ?)
             ON DUPLICATE KEY UPDATE value = ?",
            [$updated, $updated]
        );
        self::$settingsCache['company_name'] = $updated;
    }

    /** Active accounts — cached once per request (near-static table). */
    public static function getAccounts(): array {
        if (self::$accountsCache === null) {
            require_once __DIR__ . '/../services/AccountLedgerLoader.php';
            $db = Database::getInstance();
            self::$accountsCache = self::normalizeAccountRows(
                AccountLedgerLoader::listForDropdown($db)
            );
        }
        return self::$accountsCache;
    }

    /** Clear cached accounts after create/update/delete. */
    public static function clearAccountsCache(): void {
        self::$accountsCache = null;
    }

    /**
     * @deprecated Use AccountLedgerLoader::listForDropdown() — kept for callers that still reference this.
     * @return list<array<string,mixed>>
     */
    private static function loadAccountsForDropdown(Database $db): array {
        require_once __DIR__ . '/../services/AccountLedgerLoader.php';
        return AccountLedgerLoader::listForDropdown($db);
    }

    /** First-time setup when the ledger table is empty. */
    public static function seedDefaultLedgerAccountsIfEmpty(Database $db): void {
        require_once __DIR__ . '/../services/AccountLedgerLoader.php';
        AccountLedgerLoader::ensureTable($db);
        AccountLedgerLoader::seedIfEmpty($db);
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    protected static function normalizeAccountRows(array $rows): array {
        require_once __DIR__ . '/../services/AccountLedgerLoader.php';
        $rows = AccountLedgerLoader::normalizeRows($rows);
        foreach ($rows as &$acc) {
            $acc['normalized_type'] = self::normalizeAccountType(
                (string) ($acc['type'] ?? ''),
                (string) ($acc['name'] ?? '')
            );
        }
        unset($acc);
        return $rows;
    }

    /** Human-readable account label with database id (e.g. "#3 — NBK Bank Account"). */
    public static function formatAccountLabel(array $acc, bool $withBalance = false): string {
        $id   = (int) ($acc['id'] ?? 0);
        $name = trim((string) ($acc['name'] ?? ''));
        $label = '#' . $id . ' — ' . ($name !== '' ? $name : 'Account');
        if ($withBalance) {
            $label .= ' (' . APP_CURRENCY . ' ' . number_format((float) ($acc['current_balance'] ?? 0), DECIMAL_PLACES) . ')';
        }
        if (isset($acc['is_active']) && (int) $acc['is_active'] !== 1) {
            $label .= ' [inactive]';
        }
        return $label;
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

    /**
     * Dashboard stats cache directory — must match where DashboardController reads/writes.
     * Prefers OS temp when the project lives on a synced drive (Google Drive etc.).
     */
    protected static function dashboardCacheDir(): string {
        static $dir = null;
        if ($dir !== null) {
            return $dir;
        }

        $preferred = dirname(__DIR__, 2) . '/backups/cache';
        $rootReal  = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
        $onSynced  = stripos($rootReal, 'My Drive') !== false
            || stripos($rootReal, 'Google Drive') !== false;

        $dir = $onSynced
            ? rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_dash_cache'
            : $preferred;

        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        return $dir;
    }

    /**
     * Clear persisted dashboard stat cards after writes that affect sales, stock,
     * receivables, payables, or recent activity.
     */
    protected static function clearDashboardCache(?int $warehouseId = null): void {
        $cacheDir = self::dashboardCacheDir();
        if (is_dir($cacheDir)) {
            $pattern = $warehouseId && $warehouseId > 0
                ? $cacheDir . DIRECTORY_SEPARATOR . 'dash_*_' . $warehouseId . '_*.cache'
                : $cacheDir . DIRECTORY_SEPARATOR . 'dash_*.cache';

            foreach (glob($pattern) ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        // Party Master balance list shares the same write triggers (sales/payments/etc.).
        if (class_exists('Party', false) || is_file(__DIR__ . '/../models/Party.php')) {
            require_once __DIR__ . '/../models/Party.php';
            Party::clearBalanceListCache();
        }
    }

    public function __construct() {
        // Auth/session bootstrap runs in index.php — avoid duplicate work here.

        // Auto CSRF check on every POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifyCsrf()) {
                if (self::wantsJson()) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'msg' => 'Session expired. Refresh the page and try again.']);
                    exit;
                }
                $this->flash('error', 'Invalid request. Please try again.');
                header('Location: ' . self::safePostRedirectUrl());
                exit;
            }
        }
    }

    /** AJAX fetch with Accept: application/json — do not redirect to HTML. */
    protected static function wantsJson(): bool {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr    = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return $xhr === 'xmlhttprequest' || stripos($accept, 'application/json') !== false;
    }

    /**
     * Redirect after CSRF failure — same-host Referer only (no open redirect).
     */
    protected static function safePostRedirectUrl(): string {
        $fallback = defined('APP_URL') ? (string) APP_URL : '/';
        $ref      = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref === '') {
            return $fallback;
        }
        $refHost = parse_url($ref, PHP_URL_HOST);
        $appHost = parse_url($fallback, PHP_URL_HOST);
        if ($refHost && $appHost && strcasecmp((string) $refHost, (string) $appHost) === 0) {
            return $ref;
        }
        return $fallback;
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
        if (array_key_exists('erp_prefetched_flash', $GLOBALS)) {
            $flash = $GLOBALS['erp_prefetched_flash'];
            unset($GLOBALS['erp_prefetched_flash']);
            return $flash;
        }
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Sanitize input.
     *
     * SEC3 fix: input() no longer HTML-escapes at INPUT time. Previously every value
     * was stored pre-escaped (O'Brien -> O&#039;Brien), then views would call
     * htmlspecialchars() on it again at OUTPUT, producing double-escaped display.
     * Views that emit stored strings have been audited and now wrap user-controllable
     * fields with htmlspecialchars on output. This change keeps the contract simple:
     * data goes in raw, escape happens at the boundary you actually display from.
     *
     * Caps the value at $maxLen UTF-8 chars to protect against oversize input.
     * inputRaw() is kept as an alias for new code that wants to be explicit.
     */
    protected function input(string $key, string $default = '', string $from = 'post', int $maxLen = 1000): string {
        $source = $from === 'get' ? $_GET : $_POST;
        $value  = trim((string) ($source[$key] ?? $default));
        if ($maxLen > 0) {
            $value = function_exists('mb_substr')
                ? mb_substr($value, 0, $maxLen, 'UTF-8')
                : substr($value, 0, $maxLen);
        }
        return $value;
    }

    /**
     * Alias of input() — kept so callers that want to be explicit about reading
     * raw (un-escaped) user input can self-document the intent.
     */
    protected function inputRaw(string $key, string $default = '', string $from = 'post', int $maxLen = 1000): string {
        return $this->input($key, $default, $from, $maxLen);
    }

    /**
     * Bulk IMEI paste field — default input() cap (1000 chars) truncates at ~62 IMEIs.
     */
    protected function inputImeiBulk(string $key, string $default = '', string $from = 'post'): string {
        return $this->input($key, $default, $from, 200000);
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
            [Auth::id(), $action, $module, $refId, $description, self::clientIp()]
        );
    }

    protected static function clientIp(): string {
        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $remote = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';

        // Proxy headers (CF-Connecting-IP, X-Forwarded-For) are attacker-controlled
        // unless a trusted proxy sets them. Without one, trusting them lets callers
        // spoof a new IP per request and bypass every per-IP rate limit.
        if (!defined('TRUSTED_PROXY') || !TRUSTED_PROXY) {
            return $remote;
        }

        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_REAL_IP'] ?? '',
        ];
        $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            // XFF may contain a list; first is original client.
            $parts = array_map('trim', explode(',', $xff));
            if (!empty($parts[0])) {
                $candidates[] = $parts[0];
            }
        }
        foreach ($candidates as $ip) {
            $ip = trim((string) $ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $remote;
    }

    /**
     * File-based per-IP rate limiter for public endpoints.
     * Atomic via flock so concurrent requests cannot double the allowed burst.
     * Returns true when the caller exceeded the limit. Fails open on IO errors.
     */
    protected static function ipRateLimited(string $bucket, int $maxHits = 60, int $windowSec = 300): bool {
        $ip  = self::clientIp();
        $dir = sys_get_temp_dir() . '/' . $bucket;
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        // Best-effort cleanup to prevent unbounded growth.
        foreach (@scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            if (@is_file($p) && @filemtime($p) && @filemtime($p) < time() - 86400) {
                @unlink($p);
            }
        }

        $fh = @fopen($dir . '/' . md5($ip), 'c+');
        if (!$fh) {
            return false;
        }
        $limited = false;
        if (flock($fh, LOCK_EX)) {
            $now  = time();
            $raw  = stream_get_contents($fh);
            $hits = $raw ? (array) @json_decode($raw, true) : [];
            $hits = array_values(array_filter($hits, static fn($t) => is_numeric($t) && $t > $now - $windowSec));
            if (count($hits) >= $maxHits) {
                $limited = true;
            } else {
                $hits[] = $now;
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, json_encode($hits));
                fflush($fh);
            }
            flock($fh, LOCK_UN);
        }
        fclose($fh);
        return $limited;
    }
}
