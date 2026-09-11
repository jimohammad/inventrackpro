<?php

/**
 * Application Configuration
 */

// App Info
define('APP_NAME', 'IqbalErp');
define('APP_VERSION', '1.0.0');
/** Increment when changing `assets/css/layout.css` or `assets/js/app.js` (avoids slow per-request filemtime + CDN/browser cache). */
define('ASSETS_VER', '20260910a');
define('APP_URL', 'https://iqbal.app');
define('APP_TIMEZONE', 'Asia/Kuwait');          // change to your timezone

/**
 * Public service status page (short URL). Requires Apache rewrite: /service → index.php?page=servicetrack
 * @see .htaccess RewriteRule ^service
 */
if (!function_exists('app_service_track_url')) {
    function app_service_track_url(?string $token = null): string {
        $base = rtrim(APP_URL, '/') . '/service';
        if ($token !== null && $token !== '') {
            return $base . '/' . rawurlencode($token);
        }
        return $base;
    }
}
if (!function_exists('app_service_track_short_label')) {
    /** e.g. iqbal.app/service — for receipts */
    function app_service_track_short_label(): string {
        $host = parse_url(APP_URL, PHP_URL_HOST);
        return ($host !== null && $host !== '' ? $host : 'website') . '/service';
    }
}

/**
 * Public customer statement (short URL). Requires Apache rewrite: /s/{token} → statement.php?token={token}
 * @see .htaccess RewriteRule ^s/
 */
if (!function_exists('app_statement_token_new')) {
    /** 8 random bytes, base64url — ~11 chars, 2^64 entropy. */
    function app_statement_token_new(): string {
        return rtrim(strtr(base64_encode(random_bytes(8)), '+/', '-_'), '=');
    }
}
if (!function_exists('app_statement_url')) {
    function app_statement_url(?string $token = null): string {
        $base = rtrim(APP_URL, '/') . '/s';
        if ($token !== null && $token !== '') {
            return $base . '/' . rawurlencode($token);
        }
        return $base;
    }
}
if (!function_exists('app_statement_short_label')) {
    /** e.g. iqbal.app/s — for UI hints */
    function app_statement_short_label(): string {
        $host = parse_url(APP_URL, PHP_URL_HOST);
        return ($host !== null && $host !== '' ? $host : 'website') . '/s';
    }
}

/**
 * Public Manpower invoice-pack verification (QR). Requires Apache rewrite: /v/{token}
 * @see .htaccess RewriteRule ^v/
 */
if (!function_exists('app_manpower_verify_url')) {
    function app_manpower_verify_url(string $token): string {
        return rtrim(APP_URL, '/') . '/v/' . rawurlencode($token);
    }
}

/**
 * Public PO bank-document pack verification (QR). Requires Apache rewrite: /d/{token}
 * @see .htaccess RewriteRule ^d/
 */
if (!function_exists('app_po_docs_verify_url')) {
    function app_po_docs_verify_url(string $token): string {
        return rtrim(APP_URL, '/') . '/d/' . rawurlencode($token);
    }
}

/**
 * Public payment receipt verification (QR). Requires Apache rewrite: /r/{token}
 * @see .htaccess RewriteRule ^r/
 */
if (!function_exists('app_payment_verify_url')) {
    function app_payment_verify_url(string $token): string {
        return rtrim(APP_URL, '/') . '/r/' . rawurlencode($token);
    }
}

// Import logistics — fixed partner for per-piece profit (Party Master account no / party_code)
// Dedicated partner-profit party: Muhammad Faisal ( Partner ) — was 26014
define('IMPORT_PARTNER_PARTY_CODE', '26058');
define('IMPORT_PARTNER_PROFIT_PER_PC', 0.250);
// HK→DXB is not a single permanent forwarder — pick per shipment (first code = default)
define('IMPORT_FREIGHT_HK_PARTY_CODE', '26049'); // Logix One FZE
define('IMPORT_FREIGHT_HK_FORWARDER_NAME', 'Logix One FZE');
define('IMPORT_FREIGHT_HK_ALT_PARTY_CODES', '26045'); // Logiverse FZCO
define('IMPORT_FREIGHT_HK_ALT_FORWARDER_NAME', 'Logiverse FZCO');
define('IMPORT_FREIGHT_DXB_PARTY_CODE', '26044');
define('IMPORT_FREIGHT_DXB_FORWARDER_NAME', 'Hi-iq');
define('IMPORT_PACKING_DXB_FORWARDER_NAME', 'Union Logistics FZCO');

// Currency
define('APP_CURRENCY', 'KWD');
define('CURRENCY_CODE', 'KWD');
define('DECIMAL_PLACES', 3);

/**
 * Day-to-day branch switcher in the header / user menu.
 * false = Main-only UX (no Switch Warehouse); session still uses warehouse_id for data scope.
 * Settings → Warehouses remains for admins. Set true only if a second operational branch goes live.
 */
define('WAREHOUSE_UI_SWITCHER', false);

// Pagination
define('ROWS_PER_PAGE', 25);

// Default accounts pre-selected on the account transfer form (accounts.id, 0 = no pre-selection)
define('TRANSFER_DEFAULT_FROM_ACCOUNT_ID', 17);
define('TRANSFER_DEFAULT_TO_ACCOUNT_ID', 9);

// Invoice prefix settings
define('PURCHASE_PREFIX', 'PUR-');
define('SALE_PREFIX', 'SAL-');
define('RETURN_PREFIX', 'RET-');
define('TRANSFER_PREFIX', 'TRF-');
define('INTERSHOP_PREFIX', 'IST-');
define('EXPENSE_PREFIX', 'EXP-');

// Session
define('SESSION_NAME', 'inventrackpro_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('BACKUP_PATH', __DIR__ . '/../backups/');

// PDF Settings
define('PDF_COMPANY_NAME', 'Iqbal Electronics Co. LLC');
define('PDF_COMPANY_ADDRESS', 'Your Address Here');
define('PDF_COMPANY_PHONE', '+965 55584488');
define('PDF_COMPANY_EMAIL', 'javid@iqbalelectronics.com');
define('PDF_LOGO_PATH', __DIR__ . '/../assets/img/logo.png');

// API Settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute

// Behind a trusted reverse proxy (e.g. Cloudflare)? Only then are client-IP
// headers (CF-Connecting-IP, X-Forwarded-For) trusted. Cloudflare was removed,
// so this must stay false — otherwise rate limits are bypassable via spoofed headers.
define('TRUSTED_PROXY', false);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting handled by user.ini / server config
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Performance logging toggle (set PERF_LOG_ENABLED=true in .env to enable)
if (!defined('PERF_LOG_ENABLED')) {
    define('PERF_LOG_ENABLED', false);
}
