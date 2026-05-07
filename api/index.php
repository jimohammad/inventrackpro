<?php

/**
 * InvenTrack Pro - REST API Entry Point
 * URL: /api/index.php?endpoint=products&method=list
 * Header required: X-API-KEY: your_key_here
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://iqbal.app');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -- API Key Validation --
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($apiKey)) {
    apiError(401, 'API key is required. Pass it in X-API-KEY header.');
}

$db      = Database::getInstance();
$keyData = $db->fetchOne("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1", [$apiKey]);

if (!$keyData) {
    apiError(401, 'Invalid or inactive API key.');
}

// Update last used timestamp
$db->execute("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?", [$keyData['id']]);

// ── AUDIT FIX S6: Rate Limiting ────────────────────────────
// Fixed 60s window rate limit: max API_RATE_LIMIT requests per minute per key.
// Uses a counter table (see database migration) to avoid CREATE TABLE per request
// and to serialize increments (prevents phantom-read races).
try {
    $apiKeyId  = (int) ($keyData['id'] ?? 0);
    $windowKey = (int) floor(time() / 60);

    $db->beginTransaction();
    try {
        // Ensure row exists, then lock it and increment safely
        $db->execute(
            "INSERT INTO api_rate_limit_counters (api_key_id, window_key, cnt)
             VALUES (?,?,0)
             ON DUPLICATE KEY UPDATE cnt = cnt",
            [$apiKeyId, $windowKey]
        );

        $row = $db->fetchOne(
            "SELECT cnt FROM api_rate_limit_counters
             WHERE api_key_id = ? AND window_key = ?
             FOR UPDATE",
            [$apiKeyId, $windowKey]
        );

        $cnt = (int) ($row['cnt'] ?? 0);
        if ($cnt >= API_RATE_LIMIT) {
            $db->rollback();
            header('Retry-After: 60');
            apiError(429, 'Rate limit exceeded. Maximum ' . API_RATE_LIMIT . ' requests per minute.');
        }

        $db->execute(
            "UPDATE api_rate_limit_counters SET cnt = cnt + 1 WHERE api_key_id = ? AND window_key = ?",
            [$apiKeyId, $windowKey]
        );

        // Best-effort cleanup (older than 2 minutes). This is cheap with idx_window.
        $db->execute(
            "DELETE FROM api_rate_limit_counters WHERE window_key < ?",
            [$windowKey - 2]
        );

        $db->commit();
    } catch (Exception $inner) {
        $db->rollback();
        throw $inner;
    }
} catch (Exception $e) {
    // Rate limiting failure should not block the API — log and continue
    error_log("API rate limit check failed: " . $e->getMessage());
}

// Parse permissions
$keyPermissions = json_decode($keyData['permissions'] ?? '{}', true) ?? [];

// Warehouse scoping (security): API keys can be restricted to one or more warehouses.
// Supported formats:
// - api_keys.warehouse_id (if column exists in deployed DB)
// - permissions JSON: { "warehouse_id": 1 } or { "warehouse_ids": [1,2] } or { "warehouses": [1,2] }
$apiWarehouseIds = [];
if (!empty($keyData['warehouse_id'])) {
    $apiWarehouseIds = [(int) $keyData['warehouse_id']];
} elseif (isset($keyPermissions['warehouse_id'])) {
    $apiWarehouseIds = [(int) $keyPermissions['warehouse_id']];
} elseif (isset($keyPermissions['warehouse_ids']) && is_array($keyPermissions['warehouse_ids'])) {
    $apiWarehouseIds = array_map('intval', $keyPermissions['warehouse_ids']);
} elseif (isset($keyPermissions['warehouses']) && is_array($keyPermissions['warehouses'])) {
    $apiWarehouseIds = array_map('intval', $keyPermissions['warehouses']);
}
$apiWarehouseIds = array_values(array_unique(array_filter($apiWarehouseIds, fn($id) => $id > 0)));

// -- Route Request --
$endpoint = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['endpoint'] ?? ''));
$method   = $_SERVER['REQUEST_METHOD'];
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

// Load v1 routes
$endpointFile = __DIR__ . '/v1/' . $endpoint . '.php';

if (empty($endpoint) || !file_exists($endpointFile)) {
    apiError(404, "Endpoint '{$endpoint}' not found.");
}

require_once $endpointFile;

// -- Helper Functions --
function apiSuccess(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, ...$data]);
    exit;
}

function apiError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function hasPermission(array $keyPermissions, string $resource, string $action = 'read'): bool {
    return isset($keyPermissions[$resource]) && in_array($action, $keyPermissions[$resource]);
}

function apiAllowedWarehouseIds(): array {
    global $apiWarehouseIds;
    return $apiWarehouseIds;
}

function getInput(): array {
    global $body;
    return $body;
}
