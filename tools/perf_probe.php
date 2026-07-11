<?php
/**
 * CLI perf probe — measures DB connect + heavy query timings.
 * Usage: php tools/perf_probe.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__);
require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
require_once $root . '/app/models/Party.php';

function ms(float $start): int {
    return (int) round((microtime(true) - $start) * 1000);
}

function bench(string $label, callable $fn): void {
    $t0 = microtime(true);
    $fn();
    echo str_pad($label, 42) . ms($t0) . " ms\n";
}

$db = Database::getInstance();
$whId = 1;

echo "=== ERP perf probe ===\n";
echo 'DB_HOST: ' . DB_HOST . "\n";
echo 'PHP: ' . PHP_VERSION . ' | PDO mysql: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'NO') . "\n\n";

bench('SELECT 1', static fn() => $db->fetchOne('SELECT 1 AS ok'));

bench('Dashboard today cash', static function () use ($db, $whId) {
    $db->fetchOne(
        "SELECT COALESCE(SUM(py.amount),0) as total
         FROM payments py
         JOIN accounts a ON a.id = py.account_id
         WHERE py.date = CURDATE()
           AND py.payment_type = 'in'
           AND py.ref_type != 'discount'
           AND py.warehouse_id = ?
           AND py.status = 'active'",
        [$whId]
    );
});

bench('Stock value aggregate', static function () use ($db, $whId) {
    $db->fetchOne(
        "SELECT COALESCE(SUM(s.quantity * i.purchase_price), 0) as total
         FROM stock s
         JOIN items i ON i.id = s.item_id
         WHERE s.quantity > 0 AND s.warehouse_id = ?",
        [$whId]
    );
});

$partyModel = new Party();

bench('Party receivablePayableTotals', static function () use ($partyModel, $whId) {
    $partyModel->receivablePayableTotals($whId);
});

bench('Party getByType(all)', static function () use ($partyModel) {
    // Simulate party master without Auth — pass warehouse via reflection not needed; uses session wh
    // For CLI, receivablePayableTotals is the heavier shared path.
    $partyModel->receivablePayableTotals(1);
});

bench('NetWorth stockValueAsOf (today)', static function () use ($root, $db, $whId) {
    require_once $root . '/app/services/NetWorthService.php';
    NetWorthService::stockValueAsOf($db, date('Y-m-d'), $whId);
});

bench('NetWorth snapshot (full)', static function () use ($root, $db, $whId) {
    require_once $root . '/app/services/NetWorthService.php';
    NetWorthService::snapshot($db, date('Y-m-d'), $whId);
});

echo "\nDone. Check error_log for SLOW QUERY lines (threshold " . DB_SLOW_QUERY_MS . " ms).\n";
