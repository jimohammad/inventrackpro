<?php

/**
 * InvenTrack Pro - Main Entry Point & Router
 * All requests come through here
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/helpers/WebExceptionHandler.php';
WebExceptionHandler::register();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Auth.php';
require_once __DIR__ . '/app/controllers/BaseController.php';
require_once __DIR__ . '/app/models/BaseModel.php';

// Prevent browser from caching pages so changes show immediately
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

Auth::startSession();

// Opt-in request debug log (disabled by default).
if (getenv('ERP_DEBUG_ROUTER')) {
    $debugPayload = [
        'sessionId' => substr((string) session_id(), 0, 12),
        'location'  => 'index.php:router',
        'message'   => 'Request routed',
        'data'      => [
            'requestUri'    => $_SERVER['REQUEST_URI'] ?? '',
            'requestMethod' => $_SERVER['REQUEST_METHOD'] ?? '',
            'queryPage'     => $_GET['page'] ?? '',
            'queryAction'   => $_GET['action'] ?? '',
        ],
        'timestamp' => (int) round(microtime(true) * 1000),
    ];
    $debugLine = json_encode($debugPayload, JSON_UNESCAPED_SLASHES);
    if ($debugLine !== false) {
        $debugPath = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'erp-router-debug.log';
        @file_put_contents($debugPath, $debugLine . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Get the requested page from URL e.g. ?page=sales&action=add
$page   = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action'] ?? 'index');
if (empty($action)) $action = 'index';

// Public pages that don't need login (see Auth::isPublicPage)
$warehouseExempt = ['login', 'logout', 'warehouse', 'fieldstatement', 'servicetrack', 'imeitrack'];

if (!Auth::isPublicPage($page)) {
    Auth::required();
}

// Require warehouse selection for all pages except exempt ones
if (!in_array($page, $warehouseExempt)) {
    Auth::requireWarehouse();
}

// Redirect users without dashboard access to their default landing page
if ($page === 'dashboard' && !Auth::isAdmin() && !Auth::can('dashboard', 'view')) {
    header('Location: ' . APP_URL . '/?page=sales');
    exit;
}

// Route map: page => controller file
$routes = [
    'dashboard'       => 'DashboardController',
    'purchases'       => 'PurchaseController',
    'purchaseorders'  => 'PurchaseOrderController',
    'warranty'        => 'WarrantyController',
    'sales'           => 'SalesController',
    'payments'        => 'PaymentController',
    'returns'         => 'ReturnController',
    'expenses'        => 'ExpenseController',
    'items'           => 'ItemController',
    'categories'          => 'CategoryController',
    'stock'           => 'StockController',
    'transfers'       => 'StockTransferController',
    'accounts'        => 'AccountController',
    'openingstock'    => 'OpeningStockController',
    'mandoob_inventory' => 'MandoobInventoryController',
    'fieldstatement'  => 'FieldStatementController',
    'landedcost'      => 'LandedCostController',
    'discounts'       => 'DiscountController',
    'reports'         => 'ReportController',
    'parties'         => 'PartyController',
    'suppliercontacts'=> 'SupplierContactController',
    'imei'            => 'IMEIController',
    'service'         => 'ServiceController',
    'servicetrack'    => 'ServiceController',
    'imeitrack'       => 'IMEIController',
    'users'           => 'UserController',
    'warehouses'      => 'WarehouseAdminController',
    'settings'        => 'SettingsController',
    'backups'         => 'BackupController',
    'login'           => 'AuthController',
    'logout'          => 'AuthController',
    'warehouse'       => 'WarehouseController',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    include __DIR__ . '/app/views/errors/404.php';
    exit;
}

$controllerName = $routes[$page];
$controllerFile = __DIR__ . "/app/controllers/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    http_response_code(404);
    include __DIR__ . '/app/views/errors/404.php';
    exit;
}

require_once $controllerFile;

// Autoload models on demand (instead of loading all 8 models on every request)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/app/models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$controller = new $controllerName();

// Call the action method on the controller
if (method_exists($controller, $action)) {
    $controller->$action();
} else {
    $controller->index();
}
