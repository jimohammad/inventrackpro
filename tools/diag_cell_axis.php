<?php
if (PHP_SAPI !== 'cli') exit('CLI only');
$env = []; foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (strpos($l, '=') === false) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v);
}
$host = ($env['DB_HOST'] ?? '') === 'localhost' ? 'srv2219.hstgr.io' : ($env['DB_HOST'] ?? 'srv2219.hstgr.io');
$pdo = new PDO('mysql:host='.$host.';dbname='.$env['DB_NAME'], $env['DB_USER'], $env['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$pid = (int)$pdo->query("SELECT id FROM parties WHERE party_code='26023' LIMIT 1")->fetchColumn();

echo "=== POs ===\n";
foreach ($pdo->query("SELECT id, po_no, date, status, currency, subtotal_kwd, paid_kwd, converted_to FROM purchase_orders WHERE party_id=$pid ORDER BY id") as $r) {
    echo json_encode($r)."\n";
}
echo "\n=== Purchases ===\n";
foreach ($pdo->query("SELECT id, invoice_no, date, grand_total, paid_amount, balance, status FROM purchases WHERE party_id=$pid ORDER BY id") as $r) {
    echo json_encode($r)."\n";
}
echo "\n=== Payments ===\n";
foreach ($pdo->query("SELECT id, payment_no, date, amount, payment_type, ref_type, ref_id, status FROM payments WHERE party_id=$pid ORDER BY date, id") as $r) {
    $extra = '';
    if ($r['ref_type'] === 'purchase_order') {
        $po = $pdo->query("SELECT po_no, status, paid_kwd, subtotal_kwd FROM purchase_orders WHERE id=".(int)$r['ref_id'])->fetch();
        $extra = ' po='.json_encode($po);
    }
    if ($r['ref_type'] === 'purchase') {
        $pur = $pdo->query("SELECT invoice_no, grand_total, paid_amount FROM purchases WHERE id=".(int)$r['ref_id'])->fetch();
        $extra = ' pur='.json_encode($pur);
    }
    echo json_encode($r).$extra."\n";
}
