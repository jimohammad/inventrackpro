<?php
/**
 * Reinstate voided (cancelled) sale invoices — same logic as SalesController::reopen / Sale::reopenCancelled.
 *
 * Run on the server (SSH or local with working .env), from repo root:
 *   php scripts/reopen_voided_sales.php --yes SAL-000055 SAL-000108
 *
 * Requires: stock and IMEIs must still be available (as after a void). Remove or protect this file after use.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$args = array_slice($argv, 1);
if ($args === [] || ($args[0] ?? '') !== '--yes') {
    fwrite(STDERR, "Usage: php scripts/reopen_voided_sales.php --yes SAL-000055 [SAL-000108 ...]\n");
    fwrite(STDERR, "  --yes is required to avoid accidental runs.\n");
    exit(1);
}

$invoices = array_slice($args, 1);
if ($invoices === []) {
    fwrite(STDERR, "No invoice numbers after --yes.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/models/BaseModel.php';
require_once dirname(__DIR__) . '/app/models/Sale.php';

$saleModel = new Sale();
$db        = Database::getInstance();
$exitCode  = 0;

foreach ($invoices as $invNo) {
    $invNo = trim($invNo);
    if ($invNo === '') {
        continue;
    }
    $row = $db->fetchOne('SELECT id, invoice_no, status FROM sales WHERE invoice_no = ?', [$invNo]);
    if (!$row) {
        fwrite(STDERR, "[FAIL] Not found: {$invNo}\n");
        $exitCode = 1;
        continue;
    }
    if (($row['status'] ?? '') !== 'cancelled') {
        fwrite(STDERR, "[SKIP] {$invNo} (id {$row['id']}) status is not cancelled: {$row['status']}\n");
        continue;
    }
    $result = $saleModel->reopenCancelled((int) $row['id']);
    if (!empty($result['success'])) {
        fwrite(STDOUT, "[OK] {$invNo} (id {$row['id']}) reinstated — paid=0, balance=grand_total, stock/IMEI updated.\n");
    } else {
        fwrite(STDERR, "[FAIL] {$invNo} (id {$row['id']}): " . ($result['error'] ?? 'unknown') . "\n");
        $exitCode = 1;
    }
}

exit($exitCode);
