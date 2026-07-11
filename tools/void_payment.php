<?php
/**
 * Safely void a mistaken standalone payment (e.g. old partner lump sum PAY-000567).
 *
 * Usage on server:
 *   php -d extension=pdo_mysql tools/void_payment.php PAY-000567
 *   php -d extension=pdo_mysql tools/void_payment.php PAY-000567 --dry-run
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Payment.php';

$payNo  = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);

if ($payNo === '') {
    fwrite(STDERR, "Usage: php tools/void_payment.php PAY-000567 [--dry-run]\n");
    exit(1);
}

$db  = Database::getInstance();
$pay = $db->fetchOne('SELECT * FROM payments WHERE payment_no = ?', [$payNo]);
if (!$pay) {
    fwrite(STDERR, "Payment {$payNo} not found.\n");
    exit(1);
}

echo "Payment: {$payNo}\n";
echo "  Party id: {$pay['party_id']}, amount: {$pay['amount']}, ref: {$pay['ref_type']}/{$pay['ref_id']}\n";
echo "  Account: {$pay['account_id']}, date: {$pay['date']}\n";

if ($dryRun) {
    echo "Dry run — no changes made.\n";
    exit(0);
}

$model = new Payment();
$ok    = $model->deleteWithReversal((int) $pay['id']);
if ($ok) {
    echo "Deleted {$payNo} and reversed account balance.\n";
    exit(0);
}

fwrite(STDERR, 'Delete failed: ' . $model->getLastError() . "\n");
exit(1);
