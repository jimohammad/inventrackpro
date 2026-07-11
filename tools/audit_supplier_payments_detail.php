<?php
if (PHP_SAPI !== 'cli') exit('CLI only');

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') === false || ($line[0] ?? '') === '#') continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}
$host = ($env['DB_HOST'] ?? '') === 'localhost' ? 'srv2219.hstgr.io' : ($env['DB_HOST'] ?? 'srv2219.hstgr.io');
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $env['DB_PORT'] ?? '3306', $env['DB_NAME']),
    $env['DB_USER'], $env['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$name = $argv[1] ?? 'Mobi Home';
$st = $pdo->prepare("SELECT id FROM parties WHERE name LIKE ? LIMIT 1");
$st->execute(['%' . $name . '%']);
$pid = (int) ($st->fetchColumn() ?: 0);
echo "Party id={$pid}\n\n";

$st = $pdo->prepare(
    "SELECT payment_no, date, amount, payment_type, ref_type, ref_id, status, notes
     FROM payments WHERE party_id = ? AND status = 'active'
     ORDER BY date, id"
);
$st->execute([$pid]);
$byRef = [];
foreach ($st->fetchAll() as $p) {
    $key = ($p['payment_type'] ?? '') . ':' . ($p['ref_type'] ?? '');
    $byRef[$key] = ($byRef[$key] ?? 0) + (float) $p['amount'];
    echo implode(' | ', [
        $p['date'], $p['payment_no'], $p['amount'], $p['payment_type'], $p['ref_type'], $p['ref_id'] ?? '', substr($p['notes'] ?? '', 0, 40),
    ]) . "\n";
}
echo "\nTotals by type:\n";
foreach ($byRef as $k => $v) {
    echo "  {$k}: {$v}\n";
}

echo "\nPurchases:\n";
$st = $pdo->prepare("SELECT invoice_no, date, grand_total, paid_amount, balance, status FROM purchases WHERE party_id = ? ORDER BY date");
$st->execute([$pid]);
foreach ($st->fetchAll() as $p) {
    echo implode(' | ', [$p['date'], $p['invoice_no'], $p['grand_total'], 'paid='.$p['paid_amount'], 'bal='.$p['balance'], $p['status']]) . "\n";
}
