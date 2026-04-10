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
// Sliding window: max API_RATE_LIMIT requests per 60 seconds per key
// Uses the database to track request counts (no Redis/memcached needed)
try {
    // Create rate limit table if not exists (runs once, then cached by MySQL)
    $db->execute("CREATE TABLE IF NOT EXISTS api_rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key_id INT NOT NULL,
        request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_key_time (api_key_id, request_time)
    )");

    // Clean old entries (older than 2 minutes) to keep table small
    $db->execute(
        "DELETE FROM api_rate_limits WHERE request_time < DATE_SUB(NOW(), INTERVAL 2 MINUTE)"
    );

    // Count requests in the last 60 seconds for this key
    $rateCheck = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM api_rate_limits
         WHERE api_key_id = ? AND request_time > DATE_SUB(NOW(), INTERVAL 60 SECOND)",
        [$keyData['id']]
    );

    if ((int)($rateCheck['cnt'] ?? 0) >= API_RATE_LIMIT) {
        header('Retry-After: 60');
        apiError(429, 'Rate limit exceeded. Maximum ' . API_RATE_LIMIT . ' requests per minute.');
    }

    // Log this request
    $db->insert(
        "INSERT INTO api_rate_limits (api_key_id) VALUES (?)",
        [$keyData['id']]
    );
} catch (Exception $e) {
    // Rate limiting failure should not block the API — log and continue
    error_log("API rate limit check failed: " . $e->getMessage());
}

// Parse permissions
$keyPermissions = json_decode($keyData['permissions'] ?? '{}', true) ?? [];

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

function getInput(): array {
    global $body;
    return $body;
}
