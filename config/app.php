<?php

/**
 * Application Configuration
 */

// App Info
define('APP_NAME', 'InvenTrack Pro');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://iqbal.app');
define('APP_TIMEZONE', 'Asia/Kuwait');          // change to your timezone

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
