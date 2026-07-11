<?php
/**
 * Monthly Net Worth Snapshot — Cron Job
 *
 * Records the company's real net worth (cash + receivables − payables + inventory)
 * for the month that just closed. Designed to run on the 1st of every month so the
 * Balance Sheet "Net Worth Trend" is backed by true month-end figures instead of
 * reconstructed estimates.
 *
 * Setup in Hostinger hPanel > Cron Jobs:
 *   Command: /usr/bin/php /home/u793102776/public_html/cron/networth_snapshot.php
 *   Schedule: 1st of the month (e.g. "0 1 1 * *" — 01:00 on day 1)
 *
 * Manual backfill for a specific month-end:
 *   php cron/networth_snapshot.php 2026-05-31
 *
 * Idempotent: re-running for the same snapshot_date updates the existing row.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/NetWorthService.php';

$db = Database::getInstance();

// Resolve the snapshot date: explicit CLI arg, else the last day of the previous month.
$argDate = $argv[1] ?? '';
if ($argDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argDate)) {
    $snapshotDate = $argDate;
    $source       = 'manual';
} else {
    $snapshotDate = (new DateTimeImmutable('first day of this month'))
        ->modify('-1 day')
        ->format('Y-m-d');
    $source = 'auto';
}

try {
    $mainWarehouseId = 1;
    $totals = NetWorthService::snapshot($db, $snapshotDate, $mainWarehouseId);
    NetWorthService::storeSnapshot($db, $snapshotDate, $totals, $source, null, $mainWarehouseId);

    printf(
        "%s - Net worth snapshot saved for %s: %s %s (assets %s, liabilities %s) [%s]\n",
        date('Y-m-d H:i:s'),
        $snapshotDate,
        APP_CURRENCY,
        number_format($totals['net_worth'], DECIMAL_PLACES),
        number_format($totals['total_assets'], DECIMAL_PLACES),
        number_format($totals['total_liabilities'], DECIMAL_PLACES),
        $source
    );
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, date('Y-m-d H:i:s') . ' - FAILED to record net worth snapshot: ' . $e->getMessage() . "\n");
    exit(1);
}
