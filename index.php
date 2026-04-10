<?php

/**
 * InvenTrack Pro - Main Entry Point & Router
 * All requests come through here
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Auth.php';
require_once __DIR__ . '/app/controllers/BaseController.php';
require_once __DIR__ . '/app/models/BaseModel.php';

Auth::startSession();

// Get the requested page from URL e.g. ?page=sales&action=add
$page   = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action'] ?? 'index');
if (empty($action)) $action = 'index';

// Public pages that don't need login
$publicPages = ['login', 'logout', 'fieldstatement'];
$warehouseExempt = ['login', 'logout', 'warehouse', 'fieldstatement'];

if (!in_array($page, $publicPages)) {
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
    'fieldstatement'  => 'FieldStatementController',
    'landedcost'      => 'LandedCostController',
    'discounts'       => 'DiscountController',
    'reports'         => 'ReportController',
    'parties'         => 'PartyController',
    'suppliercontacts'=> 'SupplierContactController',
    'imei'            => 'IMEIController',
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
