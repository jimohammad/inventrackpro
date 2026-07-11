<?php
/**
 * Diagnose party/supplier statement SQL against production DB.
 * Usage: php -d extension=pdo_mysql tools/diag_statement.php [party_id]
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

$partyIdArg = isset($argv[1]) ? (int) $argv[1] : 0;
if ($partyIdArg > 0) {
    $stmt = $pdo->prepare('SELECT id, name, type FROM parties WHERE id = ?');
    $stmt->execute([$partyIdArg]);
    $party = $stmt->fetch();
} else {
    $party = $pdo->query('SELECT id, name, type FROM parties WHERE is_active=1 ORDER BY id LIMIT 1')->fetch();
}

if (!$party) {
    echo "No party found\n";
    exit(1);
}

$id = (int) $party['id'];
$whId = 1;
$fromDate = '2026-01-01';
$toDate = '2026-06-11';
echo "Testing party id={$id} ({$party['name']}, {$party['type']})\n\n";

$checks = [
    "SHOW TABLES LIKE 'import_payable_accruals'",
    "SHOW COLUMNS FROM purchase_orders LIKE 'subtotal_kwd'",
    "SHOW COLUMNS FROM purchase_orders LIKE 'other_charges_kwd'",
];
foreach ($checks as $c) {
    $r = $pdo->query($c)->fetchAll();
    echo ($r ? 'OK' : 'MISSING') . " — {$c}\n";
}
echo "\n";

$whFilter = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';
$purWhFilter = ' AND (pur.warehouse_id = ? OR pur.warehouse_id IS NULL)';
$dateFilter = ' AND date >= ? AND date <= ?';
$dateParams = [$fromDate, $toDate];
$whParams = [$whId];

$queries = [
    'sales' => [
        "SELECT COUNT(*) c FROM sales WHERE party_id = ? AND status != 'cancelled' {$dateFilter}{$whFilter}",
        array_merge([$id], $dateParams, $whParams),
    ],
    'purchases' => [
        "SELECT COUNT(*) c FROM purchases pur
         LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
         WHERE pur.party_id = ? AND pur.status != 'cancelled' AND pur.date >= ? AND pur.date <= ?{$purWhFilter}",
        array_merge([$id], $dateParams, $whParams),
    ],
    'purchases_credit_expr' => [
        "SELECT pur.id,
                CASE WHEN po_conv.id IS NOT NULL
                    THEN po_conv.subtotal_kwd + COALESCE(po_conv.other_charges_kwd, 0)
                    ELSE pur.grand_total END as credit
         FROM purchases pur
         LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
         WHERE pur.party_id = ? AND pur.status != 'cancelled' AND pur.date >= ? AND pur.date <= ?{$purWhFilter}
         LIMIT 3",
        array_merge([$id], $dateParams, $whParams),
    ],
    'payments' => [
        "SELECT COUNT(*) c FROM payments WHERE party_id = ? AND ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type != '' AND ref_type != 'purchase_order' {$dateFilter}{$whFilter}",
        array_merge([$id], $dateParams, $whParams),
    ],
    'returns' => [
        "SELECT COUNT(*) c FROM `returns` WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}",
        array_merge([$id], $dateParams, $whParams),
    ],
    'import_payable_accruals' => [
        "SELECT COUNT(*) c FROM import_payable_accruals WHERE party_id = ? AND status = 'open' {$dateFilter}{$whFilter}",
        array_merge([$id], $dateParams, $whParams),
    ],
];

foreach ($queries as $label => [$sql, $params]) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        echo "OK {$label}: " . json_encode($rows) . "\n";
    } catch (Throwable $e) {
        echo "FAIL {$label}: " . $e->getMessage() . "\n";
    }
}

// batch balance union (single party)
echo "\nBatch balance union test...\n";
try {
    $ph = '?';
    $whSql = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';
    $purWhSql = ' AND (pur.warehouse_id = ? OR pur.warehouse_id IS NULL)';
    $partyIds = [$id];
    $whParams = [$whId];
    $branchParams = array_merge($partyIds, $whParams);

    $purchaseCredit = 'CASE WHEN po_conv.id IS NOT NULL THEN po_conv.subtotal_kwd + COALESCE(po_conv.other_charges_kwd, 0) ELSE pur.grand_total END';
    $balancePayRef = "ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type != ''";
    $balanceOutPay = "payment_type = 'out' AND {$balancePayRef} AND ref_type != 'purchase_order'";

    $sql = "SELECT party_id, SUM(sales_total) as sales_total FROM (
        SELECT party_id, grand_total as sales_total FROM sales WHERE party_id IN ($ph) AND status != 'cancelled'{$whSql}
        UNION ALL SELECT party_id, 0 FROM payments WHERE party_id IN ($ph) AND payment_type = 'in' AND {$balancePayRef} AND status = 'active'{$whSql}
        UNION ALL SELECT party_id, 0 FROM `returns` WHERE party_id IN ($ph) AND type = 'sale_return' AND status = 'approved'{$whSql}
        UNION ALL SELECT pur.party_id, 0 FROM purchases pur
            LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
            WHERE pur.party_id IN ($ph) AND pur.status != 'cancelled'{$purWhSql}
        UNION ALL SELECT party_id, 0 FROM import_payable_accruals WHERE party_id IN ($ph) AND status = 'open'{$whSql}
        UNION ALL SELECT party_id, 0 FROM payments WHERE party_id IN ($ph) AND {$balanceOutPay} AND status = 'active'{$whSql}
        UNION ALL SELECT party_id, 0 FROM `returns` WHERE party_id IN ($ph) AND type = 'purchase_return' AND status = 'approved'{$whSql}
    ) t GROUP BY party_id";

    $params = array_merge($branchParams, $branchParams, $branchParams, $branchParams, $branchParams, $branchParams, $branchParams);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo 'OK batch union: ' . json_encode($stmt->fetchAll()) . "\n";
} catch (Throwable $e) {
    echo 'FAIL batch union: ' . $e->getMessage() . "\n";
}
