<?php
/**
 * One-time repair: set accounts.current_balance for account 9 to the ledger-derived
 * value (7534.073), fixing the double deduction from PO-000023/PO-000024 conversion.
 * Usage: php -d extension=pdo_mysql tools/fix_account9_balance.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') === false || $line[0] === '#') {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$pdo = new PDO(
    "mysql:host=srv2219.hstgr.io;port=3306;dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER'],
    $env['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
);

const ACC = 9;
const EXPECTED_OLD = -8401.406;
const NEW_BALANCE  = 7534.073;

$pdo->beginTransaction();
try {
    $st = $pdo->prepare("SELECT name, current_balance FROM accounts WHERE id = ? FOR UPDATE");
    $st->execute([ACC]);
    $acc = $st->fetch(PDO::FETCH_ASSOC);

    if (!$acc) {
        throw new Exception('Account 9 not found.');
    }
    if (abs((float) $acc['current_balance'] - EXPECTED_OLD) > 0.001) {
        throw new Exception(
            'Balance changed since diagnosis (now ' . $acc['current_balance'] . '), aborting. Re-run diagnostics.'
        );
    }

    $upd = $pdo->prepare("UPDATE accounts SET current_balance = ? WHERE id = ?");
    $upd->execute([NEW_BALANCE, ACC]);

    $log = $pdo->prepare(
        "INSERT INTO activity_log (user_id, action, module, ref_id, description, ip_address)
         VALUES (NULL, 'recalc_account_balance', 'accounts', ?, ?, 'cli-repair')"
    );
    $log->execute([
        ACC,
        'Repair double deduction from PO-000023/PO-000024 conversion: old=-8401.406 new=7534.073 (ledger-verified)',
    ]);

    $pdo->commit();
    echo "DONE. {$acc['name']}: {$acc['current_balance']} -> " . NEW_BALANCE . "\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'ABORTED: ' . $e->getMessage() . "\n";
    exit(1);
}
