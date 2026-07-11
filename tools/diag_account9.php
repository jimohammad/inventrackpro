<?php
/**
 * Local-only read-only diagnostic for account 9 double-deduction after PO conversion.
 * Usage: php -d extension=pdo_mysql tools/diag_account9.php
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

function rows(PDO $pdo, string $sql, array $p = []): array {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

echo "=== 1. Recently converted POs (last 7 days) ===\n";
print_r(rows($pdo,
    "SELECT po.id, po.po_no, po.status, po.paid_kwd, po.account_id, po.converted_to, pur.invoice_no, pur.date
     FROM purchase_orders po
     LEFT JOIN purchases pur ON pur.id = po.converted_to
     WHERE po.status = 'converted' AND pur.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY pur.id DESC"
));

echo "=== 2. Payments on account 9 created in last 7 days ===\n";
print_r(rows($pdo,
    "SELECT p.id, p.payment_no, p.date, p.amount, p.ref_type, p.ref_id, p.status, p.created_at
     FROM payments p
     WHERE p.account_id = ? AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY p.id DESC",
    [ACC]
));

echo "=== 3. Ledger recomputation for account 9 (same math as AccountBalanceService) ===\n";
$acc = rows($pdo, "SELECT opening_balance, current_balance, name FROM accounts WHERE id = ?", [ACC])[0];
$opening = (float) $acc['opening_balance'];

$pay = rows($pdo,
    "SELECT
        COALESCE(SUM(CASE WHEN p.payment_type='in' THEN p.amount ELSE 0 END), 0) AS total_in,
        COALESCE(SUM(CASE WHEN p.payment_type='out' THEN p.amount ELSE 0 END), 0) AS total_out,
        COALESCE(SUM(CASE WHEN p.payment_type='out' THEN -p.amount ELSE p.amount END), 0) AS net
     FROM payments p
     LEFT JOIN purchases pur ON p.ref_type = 'purchase' AND pur.id = p.ref_id
     LEFT JOIN purchase_orders po ON p.ref_type = 'purchase_order' AND po.id = p.ref_id
     WHERE p.account_id = ?
       AND p.ref_type != 'discount'
       AND p.status = 'active'
       AND (
           p.ref_type NOT IN ('purchase', 'purchase_order')
           OR (p.ref_type = 'purchase' AND (pur.id IS NULL OR pur.status != 'cancelled'))
           OR (p.ref_type = 'purchase_order' AND (po.id IS NULL OR po.status != 'cancelled'))
       )",
    [ACC]
)[0];

$exp = (float) rows($pdo, "SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE account_id = ?", [ACC])[0]['t'];
$tr  = rows($pdo,
    "SELECT
        COALESCE((SELECT SUM(amount) FROM account_transfers WHERE to_account_id = ?),0) AS in_total,
        COALESCE((SELECT SUM(amount) FROM account_transfers WHERE from_account_id = ?),0) AS out_total",
    [ACC, ACC]
)[0];
$adj = (float) rows($pdo,
    "SELECT COALESCE(SUM(CASE WHEN direction='add' THEN amount WHEN direction='subtract' THEN -amount END),0) net
     FROM account_balance_adjustments WHERE account_id = ?",
    [ACC]
)[0]['net'];

$poUnlinked = (float) rows($pdo,
    "SELECT COALESCE(SUM(po.paid_kwd), 0) AS total
     FROM purchase_orders po
     WHERE po.account_id = ?
       AND po.paid_kwd > 0.001
       AND po.status IN ('paid', 'draft')
       AND NOT EXISTS (
           SELECT 1 FROM payments p
           WHERE p.status = 'active'
             AND (
                 (p.ref_type = 'purchase_order' AND p.ref_id = po.id)
                 OR (po.converted_to IS NOT NULL AND p.ref_type = 'purchase' AND p.ref_id = po.converted_to)
                 OR (p.ref_type = 'purchase' AND EXISTS (
                     SELECT 1 FROM purchases pur
                     WHERE pur.id = p.ref_id AND pur.notes LIKE CONCAT('%Converted from PO: ', po.po_no, '%')
                 ))
             )
       )",
    [ACC]
)[0]['total'];

$transferNet = (float) $tr['in_total'] - (float) $tr['out_total'];
$ledger = round($opening + (float) $pay['net'] - $exp + $transferNet + $adj - $poUnlinked, 3);

printf("account            : %s\n", $acc['name']);
printf("opening            : %12.3f\n", $opening);
printf("payments in        : %12.3f\n", (float) $pay['total_in']);
printf("payments out       : %12.3f\n", (float) $pay['total_out']);
printf("payments net       : %12.3f\n", (float) $pay['net']);
printf("expenses           : %12.3f\n", $exp);
printf("transfers net      : %12.3f\n", $transferNet);
printf("adjustments net    : %12.3f\n", $adj);
printf("PO unlinked out    : %12.3f\n", $poUnlinked);
printf("LEDGER BALANCE     : %12.3f\n", $ledger);
printf("stored balance     : %12.3f\n", (float) $acc['current_balance']);
printf("difference         : %12.3f\n", round($ledger - (float) $acc['current_balance'], 3));
