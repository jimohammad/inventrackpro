<?php



require_once __DIR__ . '/../models/Party.php';

require_once __DIR__ . '/AccountBalanceService.php';



/**

 * NetWorthService

 *

 * Single source of truth for balance-sheet totals (cash, receivables,

 * payables, inventory, net worth) reconstructed to any "as of" date.

 *

 * Used by:

 *   - ReportController::balanceSheet (live + trend; warehouse-scoped in UI)

 *   - cron/networth_snapshot.php (monthly Main Branch recorded snapshot)

 *

 * Pass $warehouseId to scope figures to one branch; omit it for company-wide totals.

 */

final class NetWorthService {



    private static ?bool $snapshotsHaveWarehouse = null;



    private static function warehouseSql(?int $warehouseId, string $column = 'warehouse_id'): string {

        if ($warehouseId === null || $warehouseId <= 0) {

            return '';

        }

        return ' AND ' . $column . ' = ' . (int) $warehouseId;

    }



    private static function snapshotsHaveWarehouseColumn(Database $db): bool {

        if (self::$snapshotsHaveWarehouse !== null) {

            return self::$snapshotsHaveWarehouse;

        }

        try {

            $row = $db->fetchOne(

                "SELECT 1 FROM information_schema.COLUMNS

                 WHERE TABLE_SCHEMA = DATABASE()

                   AND TABLE_NAME = 'net_worth_snapshots'

                   AND COLUMN_NAME = 'warehouse_id'

                 LIMIT 1"

            );

            self::$snapshotsHaveWarehouse = $row !== false;

        } catch (Throwable $e) {

            self::$snapshotsHaveWarehouse = false;

        }

        return self::$snapshotsHaveWarehouse;

    }



    /**

     * Active accounts with their balance reconstructed as of a date.

     *

     * @return list<array<string,mixed>>

     */

    public static function accountsAsOf(Database $db, string $asOfDate, ?int $warehouseId = null): array {

        $accounts = $db->fetchAll(

            'SELECT id, name, type, current_balance, opening_balance, sort_order

             FROM accounts WHERE is_active = 1

             ORDER BY sort_order ASC, name ASC'

        );

        $rows = [];

        foreach ($accounts as $acc) {

            $ledger = AccountBalanceService::computeFromLedgerAsOf(

                $db,

                (int) $acc['id'],

                $asOfDate,

                $warehouseId

            );

            $acc['balance_as_of'] = $ledger['balance'];

            $rows[] = $acc;

        }



        return $rows;

    }



    public static function cashFromAccounts(array $accounts): float {

        $total = 0.0;

        foreach ($accounts as $a) {

            $total += (float) ($a['balance_as_of'] ?? 0);

        }

        return $total;

    }



    public static function cashAsOf(Database $db, string $asOfDate, ?int $warehouseId = null): float {

        return self::cashFromAccounts(self::accountsAsOf($db, $asOfDate, $warehouseId));

    }



    /**

     * PO prepayments awaiting goods — prepaid current asset (cash already reduced by these payments).

     *

     * @return list<array<string,mixed>>

     */

    public static function poAdvancesAsOf(Database $db, string $asOfDate, ?int $warehouseId = null): array {

        if ($warehouseId === null || $warehouseId <= 0) {

            return [];

        }



        return $db->fetchAll(

            "SELECT p.id, p.name, p.party_code,

                    COALESCE(SUM(po.paid_kwd), 0) AS amount

             FROM purchase_orders po

             JOIN parties p ON p.id = po.party_id

             WHERE po.status IN ('paid', 'draft')

               AND po.warehouse_id = ?

               AND po.date <= ?

               AND po.paid_kwd > 0

               AND NOT EXISTS (
                    SELECT 1 FROM payments py
                    WHERE py.status = 'active'
                      AND py.ref_type = 'purchase_order'
                      AND py.ref_id = po.id
               )

             GROUP BY po.party_id

             HAVING amount > 0.001

             ORDER BY amount DESC",

            [(int) $warehouseId, $asOfDate]

        );

    }



    public static function stockValueAsOf(Database $db, string $asOfDate, ?int $warehouseId = null): float {

        $sWh  = self::warehouseSql($warehouseId, 's.warehouse_id');

        $puWh = self::warehouseSql($warehouseId, 'pu.warehouse_id');

        $saWh = self::warehouseSql($warehouseId, 'sa.warehouse_id');

        $rWh  = self::warehouseSql($warehouseId, 'r.warehouse_id');



        $row = $db->fetchOne(

            "SELECT

                (SELECT COALESCE(SUM(s.quantity * i.purchase_price), 0)

                   FROM stock s JOIN items i ON i.id = s.item_id WHERE s.quantity > 0{$sWh})

                - (

                    COALESCE((SELECT SUM(pi.quantity * i.purchase_price)

                              FROM purchase_items pi

                              JOIN purchases pu ON pu.id = pi.purchase_id

                              JOIN items i ON i.id = pi.item_id

                              WHERE pu.status != 'cancelled' AND pu.date > ?{$puWh}), 0)

                  - COALESCE((SELECT SUM(si.quantity * i.purchase_price)

                              FROM sale_items si

                              JOIN sales sa ON sa.id = si.sale_id

                              JOIN items i ON i.id = si.item_id

                              WHERE sa.status != 'cancelled' AND sa.date > ?{$saWh}), 0)

                  - COALESCE((SELECT SUM(ri.quantity * i.purchase_price)

                              FROM return_items ri

                              JOIN returns r ON r.id = ri.return_id

                              JOIN items i ON i.id = ri.item_id

                              WHERE r.type = 'purchase_return' AND r.status = 'approved' AND r.date > ?{$rWh}), 0)

                  + COALESCE((SELECT SUM(ri.quantity * i.purchase_price)

                              FROM return_items ri

                              JOIN returns r ON r.id = ri.return_id

                              JOIN items i ON i.id = ri.item_id

                              WHERE r.type = 'sale_return' AND r.status = 'approved' AND r.date > ?{$rWh}), 0)

                  ) AS val",

            [$asOfDate, $asOfDate, $asOfDate, $asOfDate]

        );



        return (float) ($row['val'] ?? 0);

    }



    /**

     * @return array{

     *   receivables: list<array<string,mixed>>,

     *   payables: list<array<string,mixed>>,

     *   total_receivable: float,

     *   total_payable: float

     * }

     */

    public static function splitPartyBalances(array $allParties): array {

        $receivables     = [];

        $payables        = [];

        $totalReceivable = 0.0;

        $totalPayable    = 0.0;



        foreach ($allParties as $p) {

            $bal = (float) $p['balance'];

            if ($bal > 0.001) {

                $receivables[] = $p;

                $totalReceivable += $bal;

            } elseif ($bal < -0.001) {

                $p['balance'] = abs($bal);

                $payables[]   = $p;

                $totalPayable += abs($bal);

            }

        }



        usort($receivables, static fn(array $a, array $b): int => (float) $b['balance'] <=> (float) $a['balance']);

        usort($payables, static fn(array $a, array $b): int => (float) $b['balance'] <=> (float) $a['balance']);



        return [

            'receivables'      => $receivables,

            'payables'         => $payables,

            'total_receivable' => round($totalReceivable, 3),

            'total_payable'    => round($totalPayable, 3),

        ];

    }



    /**

     * Full balance-sheet payload for the report UI (single pass — no duplicate ledger rebuilds).

     *

     * @return array{

     *   snapshot: array{

     *     total_cash: float,

     *     total_receivable: float,

     *     total_payable: float,

     *     total_po_advances: float,

     *     stock_val: float,

     *     total_assets: float,

     *     total_liabilities: float,

     *     net_worth: float

     *   },

     *   accounts: list<array<string,mixed>>,

     *   receivables: list<array<string,mixed>>,

     *   payables: list<array<string,mixed>>,

     *   po_advances: list<array<string,mixed>>

     * }

     */

    public static function balanceSheetBundle(Database $db, string $asOfDate, ?int $warehouseId = null): array {

        $accounts    = self::accountsAsOf($db, $asOfDate, $warehouseId);

        $totalCash   = round(self::cashFromAccounts($accounts), 3);

        $partyModel  = new Party();

        $partySplit  = self::splitPartyBalances($partyModel->partyBalanceRows($warehouseId, $asOfDate));

        $poAdvances  = self::poAdvancesAsOf($db, $asOfDate, $warehouseId);

        $totalPoAdv  = round(array_sum(array_map(static fn(array $r): float => (float) $r['amount'], $poAdvances)), 3);

        $stockVal    = self::stockValueAsOf($db, $asOfDate, $warehouseId);

        // Cash is net of PO payments out. Posted advances sit on the supplier ledger (receivable debit).
        // This prepaid line is only legacy paid_kwd with no matching PAY row.
        $totalAssets      = round($totalCash + $partySplit['total_receivable'] + $stockVal + $totalPoAdv, 3);
        $totalLiabilities = $partySplit['total_payable'];



        return [

            'snapshot' => [

                'total_cash'         => $totalCash,

                'total_receivable'   => $partySplit['total_receivable'],

                'total_payable'      => $totalLiabilities,

                'total_po_advances'  => $totalPoAdv,

                'stock_val'          => $stockVal,

                'total_assets'       => $totalAssets,

                'total_liabilities'  => $totalLiabilities,

                'net_worth'          => round($totalAssets - $totalLiabilities, 3),

            ],

            'accounts'    => $accounts,

            'receivables' => $partySplit['receivables'],

            'payables'    => $partySplit['payables'],

            'po_advances' => $poAdvances,

        ];

    }



    /**

     * @return array{receivable: float, payable: float}

     */

    public static function partyTotalsAsOf(Database $db, string $asOfDate, ?int $warehouseId = null): array {

        $partyModel = new Party();

        $totals     = $partyModel->receivablePayableTotals($warehouseId, $asOfDate);



        return [

            'receivable' => (float) ($totals['rec_total'] ?? 0),

            'payable'    => (float) ($totals['pay_total'] ?? 0),

        ];

    }



    /**

     * @return array{

     *   total_cash: float,

     *   total_receivable: float,

     *   total_payable: float,

     *   total_po_advances: float,

     *   stock_val: float,

     *   total_assets: float,

     *   total_liabilities: float,

     *   net_worth: float

     * }

     */

    public static function snapshot(Database $db, string $asOfDate, ?int $warehouseId = null): array {

        if ($warehouseId !== null && $warehouseId > 0) {

            return self::balanceSheetBundle($db, $asOfDate, $warehouseId)['snapshot'];

        }



        $totalCash        = self::cashAsOf($db, $asOfDate, $warehouseId);
        $partyTotals      = self::partyTotalsAsOf($db, $asOfDate, $warehouseId);
        $stockVal         = self::stockValueAsOf($db, $asOfDate, $warehouseId);
        $poAdvances       = self::poAdvancesAsOf($db, $asOfDate, $warehouseId);
        $totalPoAdv       = round(array_sum(array_map(static fn(array $r): float => (float) $r['amount'], $poAdvances)), 3);
        $totalReceivable  = $partyTotals['receivable'];
        $totalPayable     = $partyTotals['payable'];
        $totalAssets      = round($totalCash + $totalReceivable + $stockVal + $totalPoAdv, 3);
        $totalLiabilities = round($totalPayable, 3);

        return [
            'total_cash'         => round($totalCash, 3),
            'total_receivable'   => round($totalReceivable, 3),
            'total_payable'      => $totalLiabilities,
            'total_po_advances'  => $totalPoAdv,

            'stock_val'          => $stockVal,

            'total_assets'       => $totalAssets,

            'total_liabilities'  => $totalLiabilities,

            'net_worth'          => round($totalAssets - $totalLiabilities, 3),

        ];

    }



    public static function recordedSnapshot(Database $db, string $snapshotDate, ?int $warehouseId = null): ?array {

        try {

            if (self::snapshotsHaveWarehouseColumn($db) && $warehouseId !== null && $warehouseId > 0) {

                $row = $db->fetchOne(

                    'SELECT * FROM net_worth_snapshots

                     WHERE snapshot_date = ? AND warehouse_id = ?

                     LIMIT 1',

                    [$snapshotDate, (int) $warehouseId]

                );

                return $row !== false ? $row : null;

            }



            $row = $db->fetchOne(

                'SELECT * FROM net_worth_snapshots WHERE snapshot_date = ? LIMIT 1',

                [$snapshotDate]

            );

            return $row !== false ? $row : null;

        } catch (Throwable $e) {

            error_log('net_worth_snapshots lookup failed (table missing?): ' . $e->getMessage());

            return null;

        }

    }



    public static function storeSnapshot(

        Database $db,

        string $snapshotDate,

        array $totals,

        string $source = 'auto',

        ?string $notes = null,

        ?int $warehouseId = null

    ): void {

        $whId = ($warehouseId !== null && $warehouseId > 0) ? (int) $warehouseId : 1;



        if (self::snapshotsHaveWarehouseColumn($db)) {

            $db->execute(

                "INSERT INTO net_worth_snapshots

                    (snapshot_date, warehouse_id, total_cash, total_receivable, total_payable,

                     stock_value, total_assets, total_liabilities, net_worth, source, notes)

                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

                 ON DUPLICATE KEY UPDATE

                    total_cash = VALUES(total_cash),

                    total_receivable = VALUES(total_receivable),

                    total_payable = VALUES(total_payable),

                    stock_value = VALUES(stock_value),

                    total_assets = VALUES(total_assets),

                    total_liabilities = VALUES(total_liabilities),

                    net_worth = VALUES(net_worth),

                    source = VALUES(source),

                    notes = VALUES(notes)",

                [

                    $snapshotDate,

                    $whId,

                    $totals['total_cash'],

                    $totals['total_receivable'],

                    $totals['total_payable'],

                    $totals['stock_val'],

                    $totals['total_assets'],

                    $totals['total_liabilities'],

                    $totals['net_worth'],

                    $source,

                    $notes,

                ]

            );

            return;

        }



        $db->execute(

            "INSERT INTO net_worth_snapshots

                (snapshot_date, total_cash, total_receivable, total_payable, stock_value, total_assets, total_liabilities, net_worth, source, notes)

             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

             ON DUPLICATE KEY UPDATE

                total_cash = VALUES(total_cash),

                total_receivable = VALUES(total_receivable),

                total_payable = VALUES(total_payable),

                stock_value = VALUES(stock_value),

                total_assets = VALUES(total_assets),

                total_liabilities = VALUES(total_liabilities),

                net_worth = VALUES(net_worth),

                source = VALUES(source),

                notes = VALUES(notes)",

            [

                $snapshotDate,

                $totals['total_cash'],

                $totals['total_receivable'],

                $totals['total_payable'],

                $totals['stock_val'],

                $totals['total_assets'],

                $totals['total_liabilities'],

                $totals['net_worth'],

                $source,

                $notes,

            ]

        );

    }

}


