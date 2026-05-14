<?php

/**
 * Application Configuration
 */

// App Info
define('APP_NAME', 'InvenTrack Pro');
define('APP_VERSION', '1.0.0');
/** Increment when changing `assets/css/layout.css` or `assets/js/app.js` (avoids slow per-request filemtime + CDN/browser cache). */
define('ASSETS_VER', '20260511');
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

// Currency
define('APP_CURRENCY', 'KWD');
define('CURRENCY_CODE', 'KWD');
define('DECIMAL_PLACES', 3);

// Pagination
define('ROWS_PER_PAGE', 25);

// Invoice prefix settings
define('PURCHASE_PREFIX', 'PUR-');
define('SALE_PREFIX', 'SAL-');
define('RETURN_PREFIX', 'RET-');
define('TRANSFER_PREFIX', 'TRF-');
define('EXPENSE_PREFIX', 'EXP-');

// Session
define('SESSION_NAME', 'inventrackpro_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('BACKUP_PATH', __DIR__ . '/../backups/');

// PDF Settings
define('PDF_COMPANY_NAME', 'Iqbal Electronics Co. WLL');
define('PDF_COMPANY_ADDRESS', 'Your Address Here');
define('PDF_COMPANY_PHONE', '+965 55584488');
define('PDF_COMPANY_EMAIL', 'javid@iqbalelectronics.com');
define('PDF_LOGO_PATH', __DIR__ . '/../assets/img/logo.png');

// API Settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting handled by user.ini / server config
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Performance logging toggle (set PERF_LOG_ENABLED=true in .env to enable)
if (!defined('PERF_LOG_ENABLED')) {
    define('PERF_LOG_ENABLED', false);
}
