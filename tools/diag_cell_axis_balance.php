<?php
if (PHP_SAPI !== 'cli') exit('CLI only');
$env = []; foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (strpos($l, '=') === false) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v);
}
$pdo = new PDO('mysql:host=srv2219.hstgr.io;dbname=' . $env['DB_NAME'], $env['DB_USER'], $env['DB_PASS']);
$pid = 23;
$wh = 1;
$p = (float) $pdo->query(
    "SELECT COALESCE(SUM(CASE WHEN po.id IS NOT NULL
        THEN po.subtotal_kwd + COALESCE(po.other_charges_kwd, 0) ELSE pur.grand_total END), 0)
     FROM purchases pur
     LEFT JOIN purchase_orders po ON po.converted_to = pur.id AND po.status = 'converted'
     WHERE pur.party_id = $pid AND pur.status != 'cancelled'
       AND (pur.warehouse_id = $wh OR pur.warehouse_id IS NULL)"
)->fetchColumn();
$o = (float) $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments
     WHERE party_id = $pid AND status = 'active' AND payment_type = 'out'
       AND ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type != ''
       AND ref_type != 'purchase_order'
       AND (warehouse_id = $wh OR warehouse_id IS NULL)"
)->fetchColumn();
echo "purchases=$p payments=$o unified=" . (-$p + $o) . " payable=" . ($p - $o) . "\n";
