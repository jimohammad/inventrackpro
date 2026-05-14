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

Auth::startSession();

// Get the requested page from URL e.g. ?page=sales&action=add
$page   = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));
$action = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['action'] ?? 'index'));

// Public pages that don't need login
$publicPages = ['login', 'logout'];
$warehouseExempt = ['login', 'logout', 'warehouse'];

if (!in_array($page, $publicPages)) {
    Auth::required();
}

// Require warehouse selection for all pages except exempt ones
if (!in_array($page, $warehouseExempt)) {
    Auth::requireWarehouse();
}

// Route map: page => controller file
$routes = [
    'dashboard'       => 'DashboardController',
    'purchases'       => 'PurchaseController',
    'sales'           => 'SalesController',
    'payments'        => 'PaymentController',
    'returns'         => 'ReturnController',
    'expenses'        => 'ExpenseController',
    'items'           => 'ItemController',
    'categories'      => 'CategoryController',
    'stock'           => 'StockController',
    'transfers'       => 'StockTransferController',
    'imei'            => 'IMEIController',
    'accounts'        => 'AccountController',
    'landedcost'      => 'LandedCostController',
    'discounts'       => 'DiscountController',
    'reports'         => 'ReportController',
    'parties'         => 'PartyController',
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

// Load model files auto
$modelDir = __DIR__ . '/app/models/';
foreach (glob($modelDir . '*.php') as $modelFile) {
    require_once $modelFile;
}

$controller = new $controllerName();

// Call the action method on the controller
if (method_exists($controller, $action)) {
    $controller->$action();
} else {
    $controller->index();
}
