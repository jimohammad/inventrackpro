<?php

/**
 * API: stock-only receive from the peer shop.
 * POST /api/?endpoint=intershop
 * Header: X-API-KEY
 * Permission JSON on the key: { "intershop": ["write"], "warehouse_id": 1 }
 */

$db = Database::getInstance();
require_once __DIR__ . '/../../app/models/IntershopTransfer.php';
require_once __DIR__ . '/../../app/helpers/ImeiFormat.php';

if ($method !== 'POST') {
    apiError(405, 'Use POST to receive a shop stock transfer.');
}

if (!hasPermission($keyPermissions, 'intershop', 'write') && !hasPermission($keyPermissions, 'intershop', 'add')) {
    apiError(403, 'API key has no intershop write permission.');
}

if (!IntershopTransfer::ensureSchema($db)) {
    apiError(500, 'Shop transfer tables could not be created.');
}

$allowedWhIds = apiAllowedWarehouseIds();
$warehouseId  = 0;
if (count($allowedWhIds) === 1) {
    $warehouseId = $allowedWhIds[0];
} elseif (count($allowedWhIds) > 1) {
    apiError(400, 'Intershop API key must be scoped to one warehouse.');
} else {
    $def = $db->fetchOne(
        "SELECT id FROM warehouses WHERE is_active = 1 ORDER BY is_default DESC, id ASC LIMIT 1"
    );
    $warehouseId = (int) ($def['id'] ?? 0);
}
if ($warehouseId <= 0) {
    apiError(400, 'No warehouse available to receive stock.');
}

$body = getInput();
try {
    $model  = new IntershopTransfer();
    $result = $model->receiveInbound($warehouseId, $body);
    apiSuccess([
        'inbound_no' => $result['inbound_no'],
        'idempotent' => !empty($result['idempotent']),
    ], !empty($result['idempotent']) ? 200 : 201);
} catch (InvalidArgumentException $e) {
    apiError(400, $e->getMessage());
} catch (Throwable $e) {
    error_log('[api/intershop] ' . $e->getMessage());
    apiError(422, $e->getMessage());
}
