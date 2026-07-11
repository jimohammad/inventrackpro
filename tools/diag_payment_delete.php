<?php
/**
 * Diagnose why a payment cannot be deleted.
 * Usage: php -d extension=pdo_mysql tools/diag_payment_delete.php PAY-000567
 */
require_once __DIR__ . '/../config/database.php';

$payNo = $argv[1] ?? 'PAY-000567';
$db    = Database::getInstance();

$pay = $db->fetchOne('SELECT * FROM payments WHERE payment_no = ?', [$payNo]);
if (!$pay) {
    fwrite(STDERR, "Payment {$payNo} not found.\n");
    exit(1);
}

$id      = (int) $pay['id'];
$partyId = (int) ($pay['party_id'] ?? 0);
$type    = (string) ($pay['payment_type'] ?? '');

echo "=== Payment {$payNo} (id={$id}) ===\n";
echo json_encode([
    'ref_type'    => $pay['ref_type'],
    'ref_id'      => $pay['ref_id'],
    'party_id'    => $partyId,
    'amount'      => $pay['amount'],
    'account_id'  => $pay['account_id'],
    'status'      => $pay['status'],
    'created_at'  => $pay['created_at'],
], JSON_PRETTY_PRINT) . "\n\n";

$shipmentLegs = [
    'shipment_freight_hk', 'shipment_packing_dxb', 'shipment_freight_dxb',
    'shipment_partner', 'shipment_cost',
];
$legPh = implode(',', array_fill(0, count($shipmentLegs), '?'));

$newerAll = $db->fetchAll(
    "SELECT payment_no, ref_type, amount, created_at
     FROM payments
     WHERE party_id = ? AND payment_type = ? AND status = 'active' AND id != ?
       AND (created_at > ? OR (created_at = ? AND id > ?))
     ORDER BY created_at ASC, id ASC",
    [$partyId, $type, $id, $pay['created_at'], $pay['created_at'], $id]
);
echo "=== All newer payments (same party/direction) ===\n";
foreach ($newerAll as $r) {
    echo "  {$r['payment_no']}  {$r['ref_type']}  {$r['amount']}  {$r['created_at']}\n";
}

$params = array_merge(
    [$partyId, $type, $id, $pay['created_at'], $pay['created_at'], $id],
    $shipmentLegs
);
$newerFifo = $db->fetchOne(
    "SELECT payment_no, ref_type FROM payments
     WHERE party_id = ? AND payment_type = ? AND ref_type != 'discount'
       AND ref_type NOT IN ({$legPh}) AND status = 'active' AND id != ?
       AND (created_at > ? OR (created_at = ? AND id > ?))
     ORDER BY created_at ASC, id ASC LIMIT 1",
    $params
);
echo "\n=== FIFO blocker (would block delete) ===\n";
echo $newerFifo ? json_encode($newerFifo) . "\n" : "(none)\n";

$purchPaid = $db->fetchAll(
    "SELECT id, invoice_no, paid_amount, balance FROM purchases
     WHERE party_id = ? AND paid_amount > 0.001 AND status != 'cancelled'
     ORDER BY date DESC, id DESC LIMIT 10",
    [$partyId]
);
echo "\n=== Purchases with paid_amount for this party ===\n";
if ($purchPaid === []) {
    echo "(none — FIFO reversal should skip)\n";
} else {
    foreach ($purchPaid as $r) {
        echo "  {$r['invoice_no']} paid={$r['paid_amount']} bal={$r['balance']}\n";
    }
}

require_once __DIR__ . '/../app/models/Payment.php';
$model = new Payment();
$ok    = $model->deleteWithReversal($id);
if ($ok) {
    echo "\nDELETE TEST: SUCCESS\n";
} else {
    echo "\nDELETE TEST: FAILED — " . $model->getLastError() . "\n";
}
