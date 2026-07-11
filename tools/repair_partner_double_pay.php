<?php
/**
 * Repair duplicate partner profit payments (e.g. PAY-569…577 after old PAY-567).
 *
 * Usage:
 *   php -d extension=pdo_mysql tools/repair_partner_double_pay.php --list
 *   php -d extension=pdo_mysql tools/repair_partner_double_pay.php --undo 2026-07-05
 *   php -d extension=pdo_mysql tools/repair_partner_double_pay.php --undo 2026-07-05 --link PAY-000567
 *   php -d extension=pdo_mysql tools/repair_partner_double_pay.php --undo 2026-07-05 --dry-run
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/services/LandedCostPaymentLinker.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$db   = Database::getInstance();
$code = defined('IMPORT_PARTNER_PARTY_CODE') ? IMPORT_PARTNER_PARTY_CODE : '26014';
$party = $db->fetchOne('SELECT id, name FROM parties WHERE party_code = ?', [$code]);
if (!$party) {
    fwrite(STDERR, "Partner party {$code} not found.\n");
    exit(1);
}
$partnerId = (int) $party['id'];

$list    = in_array('--list', $argv, true);
$dryRun  = in_array('--dry-run', $argv, true);
$undoIdx = array_search('--undo', $argv, true);
$linkIdx = array_search('--link', $argv, true);
$date    = $undoIdx !== false ? ($argv[$undoIdx + 1] ?? '') : '';
$linkNo  = $linkIdx !== false ? ($argv[$linkIdx + 1] ?? '') : '';

if ($list) {
    echo "Partner: {$party['name']} (id={$partnerId})\n\n";
    $dups = $db->fetchAll(
        "SELECT p.date, COUNT(*) as cnt, SUM(p.amount) as total,
                GROUP_CONCAT(p.payment_no ORDER BY p.id SEPARATOR ', ') as pay_nos
         FROM payments p
         WHERE p.party_id = ? AND p.ref_type = 'shipment_partner'
           AND p.payment_type = 'out' AND p.status = 'active'
         GROUP BY p.date HAVING cnt > 1 ORDER BY p.date DESC",
        [$partnerId]
    );
    foreach ($dups as $d) {
        echo "  {$d['date']}: {$d['cnt']} payments, total {$d['total']} — {$d['pay_nos']}\n";
    }
    $lumps = $db->fetchAll(
        "SELECT p.payment_no, p.amount, p.date FROM payments p
         WHERE p.party_id = ? AND p.ref_type = 'purchase' AND (p.ref_id IS NULL OR p.ref_id = 0)
           AND p.status = 'active' AND p.payment_type = 'out'
           AND NOT EXISTS (SELECT 1 FROM import_payable_accruals ipa WHERE ipa.payment_id = p.id AND ipa.leg = 'partner')
         ORDER BY p.date DESC",
        [$partnerId]
    );
    echo "\nUnlinked lump payments:\n";
    foreach ($lumps as $l) {
        echo "  {$l['payment_no']} · {$l['date']} · {$l['amount']} KWD\n";
    }
    exit(0);
}

if ($date === '') {
    fwrite(STDERR, "Usage: --list | --undo YYYY-MM-DD [--link PAY-000567] [--dry-run]\n");
    exit(1);
}

$payments = $db->fetchAll(
    "SELECT id, payment_no, amount FROM payments
     WHERE party_id = ? AND ref_type = 'shipment_partner' AND payment_type = 'out'
       AND status = 'active' AND date = ?
     ORDER BY id",
    [$partnerId, $date]
);
if (count($payments) < 2) {
    fwrite(STDERR, "Need at least 2 shipment_partner payments on {$date}.\n");
    exit(1);
}

$total = array_sum(array_map(static fn ($p) => (float) $p['amount'], $payments));
echo count($payments) . " duplicate payment(s) on {$date}, total {$total} KWD\n";
foreach ($payments as $p) {
    echo "  {$p['payment_no']} · {$p['amount']}\n";
}

if ($dryRun) {
    echo "Dry run — no changes.\n";
    exit(0);
}

$model = new Payment();
foreach ($payments as $p) {
    if (!$model->deleteWithReversal((int) $p['id'])) {
        fwrite(STDERR, "Failed {$p['payment_no']}: " . $model->getLastError() . "\n");
        exit(1);
    }
    echo "Removed {$p['payment_no']}\n";
}

if ($linkNo !== '') {
    $lump = $db->fetchOne('SELECT * FROM payments WHERE payment_no = ? AND party_id = ?', [$linkNo, $partnerId]);
    if (!$lump) {
        fwrite(STDERR, "Lump payment {$linkNo} not found.\n");
        exit(1);
    }
    $charges = $db->fetchAll(
        "SELECT shipment_item_charge_id FROM import_payable_accruals
         WHERE party_id = ? AND leg = 'partner' AND status = 'open'",
        [$partnerId]
    );
    $chargeIds = array_map(static fn ($r) => (int) $r['shipment_item_charge_id'], $charges);
    $linked = LandedCostPaymentLinker::linkExistingPartnerPayment($db, (int) $lump['id'], $chargeIds);
    echo "Linked {$linked} lines to {$linkNo}\n";
} else {
    $open = $db->fetchOne(
        "SELECT COUNT(*) as c, COALESCE(SUM(amount),0) as t FROM import_payable_accruals
         WHERE party_id = ? AND leg = 'partner' AND status = 'open'",
        [$partnerId]
    );
    echo "Open partner due: {$open['c']} lines, {$open['t']} KWD\n";
}

echo "Done.\n";
