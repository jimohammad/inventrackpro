<?php
$env = []; foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (strpos($l, '=') === false) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v);
}
$pdo = new PDO('mysql:host=srv2219.hstgr.io;dbname=' . $env['DB_NAME'], $env['DB_USER'], $env['DB_PASS']);
$rows = $pdo->query(
    "SELECT p.name,
            COALESCE(SUM(CASE WHEN pur.status != 'cancelled' THEN pur.balance ELSE 0 END), 0) AS sum_bal,
            COALESCE(SUM(CASE WHEN pur.status != 'cancelled' THEN pur.grand_total - pur.paid_amount ELSE 0 END), 0) AS unpaid
     FROM parties p
     LEFT JOIN purchases pur ON pur.party_id = p.id AND (pur.warehouse_id = 1 OR pur.warehouse_id IS NULL)
     WHERE p.type IN ('supplier','freight_forwarder') AND p.is_active = 1
     GROUP BY p.id ORDER BY p.name"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    printf("%-45s sum(balance)=%10.3f  grand-paid=%10.3f\n", $r['name'], $r['sum_bal'], $r['unpaid']);
}
