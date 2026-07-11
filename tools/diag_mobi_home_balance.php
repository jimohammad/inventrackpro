<?php
/**
 * Diagnose Mobi Home FZE supplier balance mismatch (party master vs statement).
 * Usage: php tools/diag_mobi_home_balance.php
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Party.php';

$db = Database::getInstance();
$partyModel = new Party();

$party = $db->fetchOne("SELECT * FROM parties WHERE name LIKE '%Mobi Home%' LIMIT 1");
if (!$party) {
    fwrite(STDERR, "Party not found\n");
    exit(1);
}

$partyId = (int) $party['id'];
$whId = 1; // Main branch

echo "=== Party: {$party['name']} (id={$partyId}) ===\n";
echo "opening_balance field: " . ($party['opening_balance'] ?? 0) . "\n";
echo "warehouse_id: " . ($party['warehouse_id'] ?? 'NULL') . "\n\n";

$withBal = $partyModel->findWithBalanceForWarehouse($partyId, $whId);
echo "Party Master net_balance (findWithBalanceForWarehouse wh=1): " . ($withBal['net_balance'] ?? 'N/A') . "\n\n";

// Component breakdown from batchBalanceUnionSql
[$sql, $params] = (new ReflectionMethod($partyModel, 'batchBalanceUnionSql'))->invoke($partyModel, [$partyId], $whId);
$agg = $db->fetchOne($sql, $params);
echo "Balance components (wh=1, incl NULL warehouse rows):\n";
print_r($agg);

// All purchases
echo "\n--- Purchases ---\n";
$purchases = $db->fetchAll(
    "SELECT id, invoice_no, date, grand_total, warehouse_id, status, created_at FROM purchases
     WHERE party_id = ? ORDER BY date, id",
    [$partyId]
);
foreach ($purchases as $p) {
    echo "{$p['date']} {$p['invoice_no']} total={$p['grand_total']} wh={$p['warehouse_id']} status={$p['status']}\n";
}

// All payments
echo "\n--- Payments ---\n";
$payments = $db->fetchAll(
    "SELECT id, payment_no, date, amount, payment_type, ref_type, warehouse_id, status, created_at FROM payments
     WHERE party_id = ? ORDER BY date, id",
    [$partyId]
);
foreach ($payments as $p) {
    echo "{$p['date']} {$p['payment_no']} amt={$p['amount']} type={$p['payment_type']} ref={$p['ref_type']} wh={$p['warehouse_id']} status={$p['status']}\n";
}

// Import payables
echo "\n--- Import payables ---\n";
$accruals = $db->fetchAll(
    "SELECT id, accrual_no, date, amount, warehouse_id, status FROM import_payable_accruals WHERE party_id = ?",
    [$partyId]
);
print_r($accruals ?: ['none']);

// Statement transactions (full, no date filter)
echo "\n--- Full statement timeline ---\n";
$txns = $partyModel->getPartyStatementTransactions($partyId, '', '', $whId);
$running = $partyModel->scopedOpeningBalance($party, $whId);
echo "Scoped opening: {$running}\n";
foreach ($txns as $t) {
    $running += (float) $t['debit'] - (float) $t['credit'];
    echo "{$t['date']} {$t['ref_no']} {$t['txn_type']} D={$t['debit']} C={$t['credit']} bal={$running}\n";
}
echo "Final running balance: {$running}\n";

// Report-style opening for from_date 2026-05-01
$fromDate = '2026-05-01';
echo "\n--- Report opening calc (from {$fromDate}, strict wh={$whId}) ---\n";
$salesBefore = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(grand_total),0) as t FROM sales
     WHERE party_id = ? AND warehouse_id = ? AND date < ? AND status != 'cancelled'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
$purchasesBefore = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(grand_total),0) as t FROM purchases
     WHERE party_id = ? AND warehouse_id = ? AND date < ? AND status != 'cancelled'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
$payInBefore = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as t FROM payments
     WHERE party_id = ? AND warehouse_id = ? AND payment_type = 'in' AND ref_type != 'discount' AND date < ? AND status = 'active'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
$payOutBefore = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as t FROM payments
     WHERE party_id = ? AND warehouse_id = ? AND payment_type = 'out' AND ref_type != 'discount' AND date < ? AND status = 'active'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
$openingStrict = $partyModel->scopedOpeningBalance($party, $whId)
    + $salesBefore - $payInBefore - $purchasesBefore + $payOutBefore;
echo "salesBefore={$salesBefore} purchasesBefore={$purchasesBefore} payIn={$payInBefore} payOut={$payOutBefore}\n";
echo "openingBal (strict wh): {$openingStrict}\n";

// Fixed opening with NULL warehouse
$purchasesBeforeNull = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(grand_total),0) as t FROM purchases
     WHERE party_id = ? AND (warehouse_id = ? OR warehouse_id IS NULL) AND date < ? AND status != 'cancelled'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
$payOutBeforeNull = (float) ($db->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as t FROM payments
     WHERE party_id = ? AND (warehouse_id = ? OR warehouse_id IS NULL) AND payment_type = 'out' AND ref_type != 'discount' AND date < ? AND status = 'active'",
    [$partyId, $whId, $fromDate]
)['t'] ?? 0);
echo "purchasesBefore (with NULL): {$purchasesBeforeNull}, payOut (with NULL): {$payOutBeforeNull}\n";
