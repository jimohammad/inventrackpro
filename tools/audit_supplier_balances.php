<?php
/**
 * Compare supplier balance formulas against production DB.
 * Usage: php -d extension=pdo_mysql tools/audit_supplier_balances.php [party_name_fragment]
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') === false || ($line[0] ?? '') === '#') {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$host = $env['DB_HOST'] ?? 'localhost';
if ($host === 'localhost' || $host === '127.0.0.1') {
    $host = 'srv2219.hstgr.io';
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? ''),
    $env['DB_USER'] ?? '',
    $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

require_once __DIR__ . '/../app/helpers/WarehouseScope.php';

$filter = $argv[1] ?? '';
$whId   = 1;

$sql = "SELECT id, party_code, name, type, opening_balance, warehouse_id
        FROM parties
        WHERE is_active = 1 AND type IN ('supplier', 'freight_forwarder')";
$params = [];
if ($filter !== '') {
    $sql .= ' AND name LIKE ?';
    $params[] = '%' . $filter . '%';
}
$sql .= ' ORDER BY name LIMIT 15';

$st = $pdo->prepare($sql);
$st->execute($params);
$parties = $st->fetchAll();

$sum = fn(string $sql, array $p) => (float) ($pdo->prepare($sql)->execute($p) || true ? $pdo->prepare($sql) : null);

foreach ($parties as $party) {
    $pid = (int) $party['id'];
    $wh  = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';

    $q = fn(string $sql, array $p) => (float) ($pdo->prepare($sql)->execute($p) ? ($pdo->prepare($sql)->fetch(PDO::FETCH_ASSOC) ?: ['t' => 0]) : ['t' => 0]);

    $fetch = function (string $sql, array $p) use ($pdo): float {
        $st = $pdo->prepare($sql);
        $st->execute($p);
        return (float) ($st->fetchColumn() ?: 0);
    };

    $purchases   = $fetch("SELECT COALESCE(SUM(grand_total),0) FROM purchases WHERE party_id = ? AND status != 'cancelled'{$wh}", [$pid, $whId]);
    $payOutFixed = $fetch(
        "SELECT COALESCE(SUM(amount),0) FROM payments
         WHERE party_id = ? AND status = 'active' AND payment_type = 'out'
           AND ref_type IN ('purchase','purchase_order','shipment_cost','shipment_freight_hk','shipment_packing_dxb','shipment_freight_dxb','shipment_partner'){$wh}",
        [$pid, $whId]
    );
    $payOutAll   = $fetch("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id = ? AND status = 'active' AND payment_type = 'out' AND ref_type NOT IN ('discount','expense'){$wh}", [$pid, $whId]);
    $payOutOld   = $fetch("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id = ? AND status = 'active' AND ref_type IN ('purchase','purchase_order'){$wh}", [$pid, $whId]);
    $payIn       = $fetch("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id = ? AND status = 'active' AND payment_type = 'in' AND ref_type NOT IN ('discount','expense'){$wh}", [$pid, $whId]);
    $importOpen  = $fetch("SELECT COALESCE(SUM(amount),0) FROM import_payable_accruals WHERE party_id = ? AND status = 'open'{$wh}", [$pid, $whId]);
    $purRet      = $fetch("SELECT COALESCE(SUM(grand_total),0) FROM `returns` WHERE party_id = ? AND type = 'purchase_return' AND status = 'approved'{$wh}", [$pid, $whId]);
    $openingScoped = WarehouseScope::openingBalanceForParty($party, $whId);

    $oldUnified  = $openingScoped - $purchases - $importOpen + $payOutOld + $purRet;
    $fixedUnified = $openingScoped - $purchases - $importOpen + $payOutFixed - $payIn + $purRet;
    $brokenUnified = $openingScoped - $purchases - $importOpen + $payOutAll - $payIn + $purRet;

    echo str_repeat('-', 72) . "\n";
    echo "{$party['name']} (#{$party['party_code']}, id={$pid})\n";
    echo "  opening={$openingScoped} purchases={$purchases} import_open={$importOpen}\n";
    echo "  pay_out(fixed)={$payOutFixed} pay_out(broken all)={$payOutAll} pay_in={$payIn}\n";
    echo "  FIXED payable (deploy this):         " . round(-$fixedUnified, 3) . "\n";
    echo "  BROKEN payable (blank ref dupes):    " . round(-$brokenUnified, 3) . "\n";
    echo "  Blank-ref duplicate amount:          " . round($payOutAll - $payOutFixed, 3) . "\n";
}
