<?php
$env = []; foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (strpos($l, '=') === false) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v);
}
$pdo = new PDO('mysql:host=srv2219.hstgr.io;dbname=' . $env['DB_NAME'], $env['DB_USER'], $env['DB_PASS']);
foreach ($pdo->query("SELECT COALESCE(NULLIF(ref_type,''),'(empty)') rt, COUNT(*) c, SUM(amount) s FROM payments WHERE status='active' AND payment_type='out' GROUP BY rt ORDER BY s DESC") as $r) {
    echo "{$r['rt']} | {$r['c']} | {$r['s']}\n";
}
