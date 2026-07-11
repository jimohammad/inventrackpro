<?php
if (PHP_SAPI !== 'cli') exit('CLI only');
$env = []; foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (strpos($l, '=') === false) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v);
}
$host = ($env['DB_HOST'] ?? '') === 'localhost' ? 'srv2219.hstgr.io' : ($env['DB_HOST'] ?? 'srv2219.hstgr.io');
$pdo = new PDO('mysql:host='.$host.';dbname='.$env['DB_NAME'], $env['DB_USER'], $env['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$party = $pdo->query("SELECT * FROM parties WHERE party_code = '26019' OR name LIKE '%Al Nahar%' LIMIT 1")->fetch();
$pid = (int)$party['id'];
$wh = 1;
$whSql = ' AND (warehouse_id = ? OR warehouse_id IS NULL)';
$p = fn($sql, $bind) => (float)(($st = $pdo->prepare($sql)) && $st->execute($bind) ? $st->fetchColumn() : 0);

$opening = (float)$party['opening_balance'];
$sales = $p("SELECT COALESCE(SUM(grand_total),0) FROM sales WHERE party_id=? AND status!='cancelled'{$whSql}", [$pid,$wh]);
$payIn = $p("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id=? AND status='active' AND payment_type='in' AND ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type!=''{$whSql}", [$pid,$wh]);
$payOutSupplier = $p("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id=? AND status='active' AND payment_type='out' AND ref_type IN ('purchase','purchase_order','shipment_cost','shipment_freight_hk','shipment_packing_dxb','shipment_freight_dxb','shipment_partner'){$whSql}", [$pid,$wh]);
$payOutAllNonBlank = $p("SELECT COALESCE(SUM(amount),0) FROM payments WHERE party_id=? AND status='active' AND payment_type='out' AND ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type!=''{$whSql}", [$pid,$wh]);
$purchases = $p("SELECT COALESCE(SUM(grand_total),0) FROM purchases WHERE party_id=? AND status!='cancelled'{$whSql}", [$pid,$wh]);

echo "Party: {$party['name']} type={$party['type']} opening=$opening\n\nPayments:\n";
foreach ($pdo->prepare("SELECT payment_no,date,amount,payment_type,ref_type,ref_id,created_at FROM payments WHERE party_id=? ORDER BY date, id")->execute([$pid]) || $pdo->query("SELECT payment_no,date,amount,payment_type,ref_type,ref_id,created_at FROM payments WHERE party_id=$pid ORDER BY date, id") as $x) {}
$st = $pdo->prepare("SELECT payment_no,date,amount,payment_type,ref_type,ref_id,created_at FROM payments WHERE party_id=? ORDER BY date, id");
$st->execute([$pid]);
foreach ($st as $row) {
    printf("%s | %s | %s | %s | ref=%s:%s\n", $row['payment_no'], $row['date'], $row['amount'], $row['payment_type'], $row['ref_type'] ?: '(blank)', $row['ref_id']);
}

$batchCurrent = $opening + $sales - $payIn - $purchases + $payOutSupplier;
$batchFixed = $opening + $sales - $payIn - $purchases + $payOutAllNonBlank;
$ledgerChronological = $opening;
foreach ($st->fetchAll() as $x) {} // reset
$st->execute([$pid]);
$rows = $st->fetchAll();
// chronological by date
$st2 = $pdo->prepare("SELECT 'sale' t, invoice_no ref, date, grand_total debit, 0 credit, created_at FROM sales WHERE party_id=? AND status!='cancelled'{$whSql}
UNION ALL SELECT 'payment', payment_no, date, IF(payment_type='out',amount,0), IF(payment_type='in',amount,0), created_at FROM payments WHERE party_id=? AND status='active' AND ref_type NOT IN ('discount','expense') AND ref_type IS NOT NULL AND ref_type!=''{$whSql}
ORDER BY date, created_at");
$st2->execute([$pid,$wh,$pid,$wh]);
$run = $opening;
echo "\nChronological ledger:\n";
foreach ($st2 as $r) {
    $run += (float)$r['debit'] - (float)$r['credit'];
    echo "{$r['date']} {$r['ref']} D={$r['debit']} C={$r['credit']} bal=$run\n";
}

echo "\nbatch (supplier out only): $batchCurrent\n";
echo "batch (all non-blank out): $batchFixed\n";
echo "expected closing: $run\n";
