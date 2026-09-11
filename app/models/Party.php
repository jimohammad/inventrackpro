<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/WarehouseScope.php';

class Party extends BaseModel {
    protected string $table = 'parties';

    public const CUSTOMER_KIND_WHOLESALE = 'wholesale';
    public const CUSTOMER_KIND_RETAIL    = 'retail';

    /** Wholesale list at or above this KWD uses the higher retail markup. */
    public const RETAIL_PRICE_TIER_KWD = 40.0;
    /** Retail add-on when wholesale is below 40 KWD. */
    public const RETAIL_PRICE_MARKUP_LOW = 0.5;
    /** Retail add-on when wholesale is 40 KWD or more. */
    public const RETAIL_PRICE_MARKUP_HIGH = 1.0;

    private static ?bool $tradeLicenseSchemaReady = null;
    private static ?bool $customerKindSchemaReady = null;

    /** Outbound payments that reduce supplier payable (excludes blank ref_type PO advance duplicates). */
    private const SUPPLIER_PAYMENT_REF_TYPES = [
        'purchase',
        'purchase_order',
        'shipment_cost',
        'shipment_freight_hk',
        'shipment_packing_dxb',
        'shipment_freight_dxb',
        'shipment_partner',
    ];

    /** @return non-empty-string */
    private static function quotedRefTypeList(array $types): string {
        return implode(',', array_map(static fn(string $t): string => "'" . $t . "'", $types));
    }

    /** SQL fragment: ref_type is a real supplier / purchase payment (not blank duplicate advances). */
    private static function supplierPaymentRefTypeSql(string $column = 'ref_type'): string {
        return $column . ' IN (' . self::quotedRefTypeList(self::SUPPLIER_PAYMENT_REF_TYPES) . ')';
    }

    /** SQL fragment: payment row affects party balance (excludes discount, expense, blank ref_type). */
    private static function balancePaymentRefTypeSql(string $column = 'ref_type'): string {
        return $column . " NOT IN ('discount','expense') AND {$column} IS NOT NULL AND {$column} != ''";
    }

    /** Extract DISC-* ref from discount payment notes (DiscountController format). */
    private static function discountRefNoFromNotesSql(string $alias = 'py'): string {
        return "CASE WHEN {$alias}.notes LIKE 'Discount DISC-%'"
            . " THEN TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX({$alias}.notes, ' — ', 1), 'Discount ', -1))"
            . " ELSE {$alias}.payment_no END";
    }

    /**
     * Purchase credit for party balance — PO-converted invoices may have grand_total inflated
     * by import logistics; supplier position follows the PO KWD total until separately accrued.
     */
    private static function purchasePartyCreditSql(string $purchaseAlias = 'pur'): string {
        return 'CASE WHEN po_conv.id IS NOT NULL'
            . ' THEN po_conv.subtotal_kwd + COALESCE(po_conv.other_charges_kwd, 0) + COALESCE(po_conv.adjustment_kwd, 0)'
            . " ELSE {$purchaseAlias}.grand_total END";
    }

    /** Outbound payments counted in party balance (posted cash, including PO advances). */
    private static function balanceOutboundPaymentSql(string $refColumn = 'ref_type'): string {
        return "payment_type = 'out' AND " . self::balancePaymentRefTypeSql($refColumn);
    }

    public static function typeLabel(string $type): string {
        return match ($type) {
            'customer'          => 'Customer / Agent',
            'supplier'          => 'Supplier',
            'both'              => 'Both',
            'freight_forwarder' => 'Freight forwarder',
            default             => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public static function normalizeCustomerKind(?string $kind, string $partyType = 'customer'): string {
        if (!in_array($partyType, ['customer', 'both'], true)) {
            return self::CUSTOMER_KIND_WHOLESALE;
        }
        $kind = strtolower(trim((string) $kind));
        return $kind === self::CUSTOMER_KIND_RETAIL
            ? self::CUSTOMER_KIND_RETAIL
            : self::CUSTOMER_KIND_WHOLESALE;
    }

    public static function isRetailCustomer(?string $kind): bool {
        return strtolower(trim((string) $kind)) === self::CUSTOMER_KIND_RETAIL;
    }

    public static function customerKindLabel(?string $kind): string {
        return self::isRetailCustomer($kind) ? 'Retail' : 'Wholesale';
    }

    /** Extra KWD on retail: +0.500 below 40 wholesale, +1.000 at 40 and above. */
    public static function retailMarkup(float $catalogSalePrice): float {
        return $catalogSalePrice >= self::RETAIL_PRICE_TIER_KWD
            ? self::RETAIL_PRICE_MARKUP_HIGH
            : self::RETAIL_PRICE_MARKUP_LOW;
    }

    /** Catalog sale_price is wholesale. Retail floor is list + retailMarkup(). */
    public static function unitPriceFloor(float $catalogSalePrice, ?string $customerKind): float {
        $floor = max(0.0, $catalogSalePrice);
        if (self::isRetailCustomer($customerKind)) {
            $floor += self::retailMarkup($catalogSalePrice);
        }
        return $floor;
    }

    /** Supplier-side types where payable display uses the inverse of unified net balance. */
    public static function isPayablePerspectiveType(string $type): bool {
        return in_array($type, ['supplier', 'freight_forwarder'], true);
    }

    /**
     * Balance for Party Master / supplier statement lists.
     * Unified net: positive = they owe us. Payable view (suppliers): positive = we owe them.
     *
     * @return array{amount: float, perspective: 'receivable'|'payable'|'unified'}
     */
    public static function displayBalanceDue(array $party, float $netBalance, ?string $listType = null): array {
        $partyType = (string) ($party['type'] ?? '');
        $usePayable = $listType === 'supplier' || $listType === 'freight_forwarder'
            || ($listType === null && self::isPayablePerspectiveType($partyType));

        if ($usePayable && self::isPayablePerspectiveType($partyType)) {
            return ['amount' => -1 * $netBalance, 'perspective' => 'payable'];
        }

        return [
            'amount'      => $netBalance,
            'perspective' => self::isPayablePerspectiveType($partyType) ? 'unified' : 'receivable',
        ];
    }

    /**
     * Party list/search visibility for the active warehouse.
     * Include the party if it is assigned here, unassigned (legacy), OR has any
     * sales/purchase/payment/return in this branch — avoids duplicate names where
     * one record has invoices and another (same display name) is empty on the ledger.
     *
     * Uncorrelated IN subqueries (one scan per table) — not per-row EXISTS.
     *
     * @return array{0:string,1:array<int,int>}
     */
    private function warehouseVisibilitySqlAndParams(int $warehouseId): array {
        if ($warehouseId <= 0) {
            return ['', []];
        }
        $sql = " AND (
            p.warehouse_id IS NULL
            OR p.warehouse_id = ?
            OR p.id IN (SELECT party_id FROM sales WHERE warehouse_id = ? AND status != 'cancelled')
            OR p.id IN (SELECT party_id FROM purchases WHERE warehouse_id = ? AND status != 'cancelled')
            OR p.id IN (SELECT party_id FROM payments WHERE warehouse_id = ?)
            OR p.id IN (SELECT party_id FROM `returns` WHERE warehouse_id = ?)
        )";
        return [$sql, [$warehouseId, $warehouseId, $warehouseId, $warehouseId, $warehouseId]];
    }

    /** First active operational warehouse (Main when Fahaheel is legal-only / inactive). */
    public function defaultOperationalWarehouseId(): int {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $row = $this->db->fetchOne(
            "SELECT id FROM warehouses WHERE is_active = 1
             ORDER BY is_default DESC, id ASC LIMIT 1"
        );
        $cached = max(1, (int) ($row['id'] ?? 1));

        return $cached;
    }

    private function warehouseIsActive(int $warehouseId): bool {
        if ($warehouseId <= 0) {
            return false;
        }
        $row = $this->db->fetchOne('SELECT is_active FROM warehouses WHERE id = ?', [$warehouseId]);

        return $row && (int) ($row['is_active'] ?? 0) === 1;
    }

    /**
     * Branch for public /s/ and fieldstatement links — no session; never inactive Fahaheel.
     */
    public function resolvePublicStatementWarehouseId(int $partyId): int {
        $party = $this->db->fetchOne('SELECT warehouse_id FROM parties WHERE id = ?', [$partyId]);
        $homeWh = (int) ($party['warehouse_id'] ?? 0);
        if ($homeWh > 0 && $this->warehouseIsActive($homeWh)) {
            return $homeWh;
        }

        $row = $this->db->fetchOne(
            "SELECT s.warehouse_id
             FROM sales s
             INNER JOIN warehouses w ON w.id = s.warehouse_id AND w.is_active = 1
             WHERE s.party_id = ? AND s.status != 'cancelled'
             GROUP BY s.warehouse_id
             ORDER BY COUNT(*) DESC, SUM(s.grand_total) DESC
             LIMIT 1",
            [$partyId]
        );
        if ($row) {
            return (int) $row['warehouse_id'];
        }

        return $this->defaultOperationalWarehouseId();
    }

    /**
     * Resolve which branch a party query belongs to (never merge Main + Fahaheel).
     */
    public function resolveScopeWarehouseId(int $partyId, ?int $explicitWarehouseId = null): int {
        if ($explicitWarehouseId !== null && $explicitWarehouseId > 0) {
            return (int) $explicitWarehouseId;
        }
        $sessionWh = WarehouseScope::currentId();
        if ($sessionWh > 0) {
            return $sessionWh;
        }
        $party = $this->db->fetchOne('SELECT warehouse_id FROM parties WHERE id = ?', [$partyId]);
        $homeWh = (int) ($party['warehouse_id'] ?? 0);
        if ($homeWh > 0 && $this->warehouseIsActive($homeWh)) {
            return $homeWh;
        }
        $row = $this->db->fetchOne(
            "SELECT s.warehouse_id
             FROM sales s
             INNER JOIN warehouses w ON w.id = s.warehouse_id AND w.is_active = 1
             WHERE s.party_id = ? AND s.status != 'cancelled'
             GROUP BY s.warehouse_id
             ORDER BY COUNT(*) DESC, SUM(s.grand_total) DESC
             LIMIT 1",
            [$partyId]
        );
        $fromSales = (int) ($row['warehouse_id'] ?? 0);
        if ($fromSales > 0) {
            return $fromSales;
        }

        return $this->defaultOperationalWarehouseId();
    }

    /** Opening balance for one branch only (same rule as Dashboard). */
    public function scopedOpeningBalance(array $party, int $warehouseId): float {
        return WarehouseScope::openingBalanceForParty($party, $warehouseId);
    }

    private function openingBalanceForWarehouse(array $party, ?int $warehouseId): float {
        return WarehouseScope::openingBalanceForParty(
            $party,
            $warehouseId !== null && $warehouseId > 0 ? (int) $warehouseId : 0
        );
    }

    /** @return array{0:string,1:list<int|string>} */
    private function balanceTransactionWarehouseClause(?int $warehouseId, string $column = 'warehouse_id'): array {
        if ($warehouseId === null || $warehouseId <= 0) {
            return ['', []];
        }
        // Legacy rows may have NULL warehouse_id (pre-branch); treat as Main operational branch.
        return [' AND (' . $column . ' = ? OR ' . $column . ' IS NULL)', [(int) $warehouseId]];
    }

    /** @return array{0:string,1:list<string>} */
    private function batchBalanceDateClause(string $asOfDate, string $dateColumn = 'date'): array {
        if ($asOfDate === '') {
            return ['', []];
        }
        return [' AND ' . $dateColumn . ' <= ?', [$asOfDate]];
    }

    /** @return array{0:string,1:list<string>} */
    private function statementDateFilter(string $fromDate, string $toDate, string $dateColumn = 'date'): array {
        $sql    = '';
        $params = [];
        if ($fromDate !== '') {
            $sql      .= ' AND ' . $dateColumn . ' >= ?';
            $params[] = $fromDate;
        }
        if ($toDate !== '') {
            $sql      .= ' AND ' . $dateColumn . ' <= ?';
            $params[] = $toDate;
        }

        return [$sql, $params];
    }

    /** @return array{0:string,1:list<int>} */
    private function statementTransactionWarehouseClause(int $warehouseId, string $column = 'warehouse_id'): array {
        return [' AND (' . $column . ' = ? OR ' . $column . ' IS NULL)', [$warehouseId]];
    }

    /**
     * Unified net balance as of a calendar date (inclusive), using the same debit/credit
     * rules as Reports → Party Statement and Party Master.
     */
    public function computeBalanceAsOf(int $partyId, string $asOfDate, ?int $warehouseId = null): float {
        $warehouseId = $this->resolveScopeWarehouseId($partyId, $warehouseId);
        if ($warehouseId <= 0) {
            return 0.0;
        }

        $party = $this->db->fetchOne('SELECT * FROM parties WHERE id = ?', [$partyId]);
        if (!$party) {
            return 0.0;
        }

        $running = $this->scopedOpeningBalance($party, $warehouseId);
        foreach ($this->getPartyStatementTransactions($partyId, '', $asOfDate, $warehouseId) as $t) {
            $running += (float) ($t['debit'] ?? 0) - (float) ($t['credit'] ?? 0);
        }

        return round($running, 3);
    }

    /** Running balance immediately before the first transaction on $fromDate. */
    public function computeStatementOpeningBalance(int $partyId, string $fromDate, ?int $warehouseId = null): float {
        if ($fromDate === '') {
            $party = $this->db->fetchOne('SELECT * FROM parties WHERE id = ?', [$partyId]);
            if (!$party) {
                return 0.0;
            }
            $warehouseId = $this->resolveScopeWarehouseId($partyId, $warehouseId);

            return round($this->scopedOpeningBalance($party, $warehouseId), 3);
        }

        $dayBefore = date('Y-m-d', strtotime($fromDate . ' -1 day'));

        return $this->computeBalanceAsOf($partyId, $dayBefore, $warehouseId);
    }

    /** Ensure import_vendor_bills exists before balance/statement SQL that references it. */
    private function ensureVendorBillsTable(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $flagDir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_schema';
        $flag = $flagDir . DIRECTORY_SEPARATOR . 'import_vendor_bills.ok';
        if (is_file($flag)) {
            return;
        }
        $path = __DIR__ . '/../services/PackingVendorBillService.php';
        if (!is_readable($path)) {
            // Partial deploy: Party Master list may still work from cache; detail must not fatal.
            error_log('[Party] PackingVendorBillService.php missing — skip vendor-bill schema ensure');
            return;
        }
        require_once $path;
        if (class_exists('PackingVendorBillService', false)) {
            PackingVendorBillService::ensureSchema($this->db);
        }
        if (!is_dir($flagDir)) {
            @mkdir($flagDir, 0700, true);
        }
        @file_put_contents($flag, (string) time(), LOCK_EX);
    }

    private function dumpCreditsReady(): bool {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        $path = __DIR__ . '/DeviceDump.php';
        if (!is_readable($path)) {
            $ready = false;
            return false;
        }
        require_once $path;
        $ready = DeviceDump::ensureSchema($this->db);
        return $ready;
    }

    /**
     * Unified party net for list/credit-check/Due. Empty $asOfDate means today
     * (same cap as Party Statement / force-zero — future-dated rows are excluded).
     *
     * @param list<int> $partyIds
     * @return array{0:string,1:list<int|string>}
     */
    private function batchBalanceUnionSql(array $partyIds, ?int $warehouseId, string $asOfDate = ''): array {
        if ($partyIds === []) {
            return ['', []];
        }
        $this->ensureVendorBillsTable();
        $dumpReady = $this->dumpCreditsReady();
        if ($asOfDate === '') {
            $asOfDate = date('Y-m-d');
        }
        $ph = implode(',', array_fill(0, count($partyIds), '?'));
        [$whSql, $whParams] = $this->balanceTransactionWarehouseClause($warehouseId);
        [$purWhSql] = $this->balanceTransactionWarehouseClause($warehouseId, 'pur.warehouse_id');
        [$dateSql, $dateParams] = $this->batchBalanceDateClause($asOfDate);
        [$purDateSql] = $this->batchBalanceDateClause($asOfDate, 'pur.date');
        [$billDateSql] = $this->batchBalanceDateClause($asOfDate, 'bill_date');
        $branchParams = array_merge($partyIds, $whParams, $dateParams);

        // Aggregate per table first (one row per party per arm) then UNION.
        // Inbound sale_payments = all `in` except expense/blank (discount is included).
        $sql = "SELECT party_id,
                    SUM(sales_total) as sales_total,
                    SUM(sale_payments) as sale_payments,
                    SUM(sale_returns) as sale_returns,
                    SUM(purchase_total) as purchase_total,
                    SUM(purchase_payments) as purchase_payments,
                    SUM(purchase_returns) as purchase_returns
             FROM (
                SELECT party_id, SUM(grand_total) as sales_total, 0 as sale_payments, 0 as sale_returns, 0 as purchase_total, 0 as purchase_payments, 0 as purchase_returns
                FROM sales WHERE party_id IN ($ph) AND status != 'cancelled'{$whSql}{$dateSql}
                GROUP BY party_id
                UNION ALL
                SELECT party_id, 0,
                    SUM(CASE WHEN payment_type = 'in' AND ref_type IS NOT NULL AND ref_type != '' AND ref_type != 'expense' THEN amount ELSE 0 END),
                    0, 0,
                    SUM(CASE WHEN " . self::balanceOutboundPaymentSql() . " THEN amount ELSE 0 END),
                    0
                FROM payments WHERE party_id IN ($ph) AND status = 'active'{$whSql}{$dateSql}
                GROUP BY party_id
                UNION ALL
                SELECT party_id, 0, 0,
                    SUM(CASE WHEN type = 'sale_return' THEN grand_total ELSE 0 END),
                    0, 0,
                    SUM(CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END)
                FROM `returns` WHERE party_id IN ($ph) AND status = 'approved'
                    AND type IN ('sale_return', 'purchase_return'){$whSql}{$dateSql}
                GROUP BY party_id
                " . ($dumpReady ? "UNION ALL
                SELECT party_id, 0, 0, SUM(grand_total), 0, 0, 0
                FROM device_dumps WHERE party_id IN ($ph) AND status = 'approved'{$whSql}{$dateSql}
                GROUP BY party_id
                " : "") . "UNION ALL
                SELECT pur.party_id, 0, 0, 0, SUM(" . self::purchasePartyCreditSql('pur') . "), 0, 0
                FROM purchases pur
                LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
                WHERE pur.party_id IN ($ph) AND pur.status != 'cancelled'{$purWhSql}{$purDateSql}
                GROUP BY pur.party_id
                UNION ALL
                SELECT party_id, 0, 0, 0, SUM(amount), 0, 0
                FROM import_payable_accruals
                WHERE party_id IN ($ph) AND status IN ('open', 'paid') AND leg != 'packing_dxb'{$whSql}{$dateSql}
                GROUP BY party_id
                UNION ALL
                SELECT party_id, 0, 0, 0, SUM(amount), 0, 0
                FROM import_vendor_bills
                WHERE party_id IN ($ph) AND status IN ('open', 'paid'){$whSql}{$billDateSql}
                GROUP BY party_id
             ) t GROUP BY party_id";

        $balanceParams = array_merge(
            $branchParams,
            $branchParams,
            $branchParams
        );
        if ($dumpReady) {
            $balanceParams = array_merge($balanceParams, $branchParams);
        }
        $balanceParams = array_merge(
            $balanceParams,
            $branchParams,
            $branchParams,
            $branchParams
        );

        return [
            $sql,
            $balanceParams,
        ];
    }

    /**
     * Cash already sent on open POs (draft/paid, goods not in), keyed by party.
     *
     * @return array<int, float>
     */
    private function openPoPaidByParty(?int $warehouseId): array {
        if ($warehouseId === null || $warehouseId <= 0) {
            return [];
        }
        $rows = $this->db->fetchAll(
            "SELECT party_id, COALESCE(SUM(paid_kwd), 0) AS paid_awaiting
             FROM purchase_orders
             WHERE warehouse_id = ?
               AND status IN ('draft', 'paid')
               AND paid_kwd > 0.001
             GROUP BY party_id",
            [(int) $warehouseId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['party_id']] = (float) $row['paid_awaiting'];
        }
        return $map;
    }

    /**
     * Dashboard trade AR — customers/both only, after removing open-PO cash already sent.
     * Supplier advances are not collectible receivables (prepaid goods in pipeline).
     *
     * @return array{rec_total: float, rec_count: int}
     */
    public function tradeReceivableTotals(?int $warehouseId = null): array {
        $whForBalance = ($warehouseId !== null && $warehouseId > 0) ? (int) $warehouseId : null;
        $parties      = $this->db->fetchAll(
            "SELECT id, opening_balance, warehouse_id, type
             FROM parties
             WHERE is_active = 1 AND type IN ('customer', 'both')"
        );
        if ($parties === []) {
            return ['rec_total' => 0.0, 'rec_count' => 0];
        }

        $ids = array_map(static fn(array $p): int => (int) $p['id'], $parties);
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $whForBalance, '');
        $balances = $balanceSql !== '' ? $this->db->fetchAll($balanceSql, $balanceParams) : [];

        $balMap = [];
        foreach ($balances as $b) {
            $balMap[(int) $b['party_id']] = $b;
        }
        $poPaidMap = $this->openPoPaidByParty($whForBalance);

        $recTotal = 0.0;
        $recCount = 0;
        foreach ($parties as $party) {
            $pid  = (int) $party['id'];
            $net  = $this->netBalanceFromComponents($party, $balMap[$pid] ?? null, $whForBalance);
            $trade = $net - ($poPaidMap[$pid] ?? 0.0);
            if ($trade > 0.001) {
                $recTotal += $trade;
                $recCount++;
            }
        }

        return [
            'rec_total' => round($recTotal, 3),
            'rec_count' => $recCount,
        ];
    }

    /**
     * Receivable / payable totals across active parties (NetWorth, balance sheet header).
     *
     * Unified net: positive = they owe us (receivable); negative = we owe them (payable).
     * Includes supplier PO advances — dashboard trade AR uses tradeReceivableTotals() instead.
     *
     * @return array{rec_total: float, pay_total: float, rec_count: int, pay_count: int}
     */
    public function receivablePayableTotals(?int $warehouseId = null, string $asOfDate = ''): array {
        $whForBalance = ($warehouseId !== null && $warehouseId > 0) ? (int) $warehouseId : null;
        $parties      = $this->db->fetchAll(
            'SELECT id, opening_balance, warehouse_id FROM parties WHERE is_active = 1'
        );
        if ($parties === []) {
            return ['rec_total' => 0.0, 'pay_total' => 0.0, 'rec_count' => 0, 'pay_count' => 0];
        }

        $ids = array_map(static fn(array $p): int => (int) $p['id'], $parties);
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $whForBalance, $asOfDate);
        $balances = $balanceSql !== '' ? $this->db->fetchAll($balanceSql, $balanceParams) : [];

        $balMap = [];
        foreach ($balances as $b) {
            $balMap[(int) $b['party_id']] = $b;
        }

        $recTotal = $payTotal = 0.0;
        $recCount = $payCount = 0;
        foreach ($parties as $party) {
            $net = $this->netBalanceFromComponents(
                $party,
                $balMap[(int) $party['id']] ?? null,
                $whForBalance
            );
            if ($net > 0.001) {
                $recTotal += $net;
                $recCount++;
            } elseif ($net < -0.001) {
                $payTotal += abs($net);
                $payCount++;
            }
        }

        return [
            'rec_total' => round($recTotal, 3),
            'pay_total' => round($payTotal, 3),
            'rec_count' => $recCount,
            'pay_count' => $payCount,
        ];
    }

    /**
     * Active parties with unified net balance (Balance Sheet party lists).
     *
     * @return list<array{id: int, name: string, party_code: ?string, type: string, balance: float}>
     */
    public function partyBalanceRows(?int $warehouseId, string $asOfDate = ''): array {
        $whForBalance = ($warehouseId !== null && $warehouseId > 0) ? (int) $warehouseId : null;
        $parties      = $this->db->fetchAll(
            'SELECT id, name, party_code, type, opening_balance, warehouse_id
             FROM parties WHERE is_active = 1 ORDER BY name'
        );
        if ($parties === []) {
            return [];
        }

        $ids = array_map(static fn(array $p): int => (int) $p['id'], $parties);
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $whForBalance, $asOfDate);
        $balances = $balanceSql !== '' ? $this->db->fetchAll($balanceSql, $balanceParams) : [];

        $balMap = [];
        foreach ($balances as $b) {
            $balMap[(int) $b['party_id']] = $b;
        }

        $rows = [];
        foreach ($parties as $party) {
            $rows[] = [
                'id'         => (int) $party['id'],
                'name'       => (string) $party['name'],
                'party_code' => $party['party_code'] ?? null,
                'type'       => (string) $party['type'],
                'balance'    => round(
                    $this->netBalanceFromComponents($party, $balMap[(int) $party['id']] ?? null, $whForBalance),
                    3
                ),
            ];
        }

        return $rows;
    }

    /** Current unified net balance for one party (credit checks, drafts, print views). */
    public function currentNetBalance(int $partyId, ?int $warehouseId = null): float {
        $wh = $warehouseId ?? WarehouseScope::currentId();
        $party = $this->findWithBalanceForWarehouse($partyId, $wh > 0 ? (int) $wh : 0);
        if ($party === false) {
            return 0.0;
        }

        return (float) ($party['net_balance'] ?? 0);
    }

    private function netBalanceFromComponents(array $party, ?array $components, ?int $warehouseId): float {
        $b = $components ?? [];
        return $this->openingBalanceForWarehouse($party, $warehouseId)
            + (float) ($b['sales_total'] ?? 0)
            - (float) ($b['sale_payments'] ?? 0)
            - (float) ($b['sale_returns'] ?? 0)
            - (float) ($b['purchase_total'] ?? 0)
            + (float) ($b['purchase_payments'] ?? 0)
            + (float) ($b['purchase_returns'] ?? 0);
    }

    /**
     * UNIFIED BALANCE LOGIC (single account per party):
     *
     * balance = opening_balance
     *         + what they owe us  (sales - sale payments - sale returns - dump credits)
     *         - what we owe them  (purchases - purchase payments - purchase returns)
     *
     * Positive = they owe us
     * Negative = we owe them (credit)
     */

    public function isVisibleInCurrentWarehouse(int $partyId): bool {
        $wid = WarehouseScope::currentId();
        if ($wid <= 0) {
            return true;
        }
        [$whSql, $whParams] = $this->warehouseVisibilitySqlAndParams($wid);
        $row = $this->db->fetchOne(
            "SELECT p.id FROM parties p WHERE p.id = ? AND p.is_active = 1 {$whSql}",
            array_merge([$partyId], $whParams)
        );
        return $row !== false;
    }

    /** @return array{0:string,1:array<int|string>} */
    private function typeFilterClause(string $type, ?int $wid): array {
        $where  = 'WHERE p.is_active = 1';
        $params = [];

        if ($type !== 'all') {
            if ($type === 'freight_forwarder') {
                $where .= " AND p.type = 'freight_forwarder'";
            } elseif ($type === 'payment_out') {
                $where .= " AND p.type IN ('customer', 'supplier', 'both', 'freight_forwarder')";
            } elseif ($type === 'purchase') {
                $where .= " AND p.type IN ('customer', 'supplier', 'both')";
            } else {
                $where .= ' AND (p.type = ? OR p.type = \'both\')';
                $params[] = $type;
            }
        }
        if ($wid) {
            [$whSql, $whParams] = $this->warehouseVisibilitySqlAndParams((int) $wid);
            $where .= $whSql;
            $params = array_merge($params, $whParams);
        }

        return [$where, $params];
    }

    /**
     * Lightweight party list for filter dropdowns — no balance aggregation.
     * Cached 5 minutes (memory + temp file) per branch and type.
     */
    public function listForFilter(string $type): array {
        static $memory = [];

        $wid    = Auth::warehouseId();
        $widKey = $wid ? (int) $wid : 0;
        $memKey = $type . ':' . $widKey;
        if (isset($memory[$memKey])) {
            return $memory[$memKey];
        }

        $cacheDir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_party_filter';
        $safeType = preg_replace('/[^a-z_]/', '', $type) ?: 'all';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'parties_' . $safeType . '_' . $widKey . '.json';
        $cacheTtl  = 300;

        if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $cacheTtl) {
            $decoded = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($decoded)) {
                $memory[$memKey] = $decoded;
                return $decoded;
            }
        }

        [$where, $params] = $this->typeFilterClause($type, $wid ? (int) $wid : null);
        $rows = $this->db->fetchAll(
            "SELECT p.id, p.name, p.phone, p.party_code, p.type
             FROM parties p {$where}
             ORDER BY p.name ASC",
            $params
        );

        $memory[$memKey] = $rows;
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }
        @file_put_contents($cacheFile, json_encode($rows), LOCK_EX);

        return $rows;
    }

    /** Drop cached filter dropdown lists after party create/update/deactivate. */
    public static function clearFilterListCache(): void {
        $cacheDir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_party_filter';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . 'parties_*.json') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        self::clearBalanceListCache();
    }

    /** Drop Party Master balance list cache (after sales/payments/party writes). */
    public static function clearBalanceListCache(): void {
        $cacheDir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_party_balances';
        if (!is_dir($cacheDir)) {
            return;
        }
        foreach (glob($cacheDir . DIRECTORY_SEPARATOR . 'bal_*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Parties of a type, scoped to current warehouse.
     *
     * @param bool $withBalances When false, skip the ledger UNION (Party Master HTML paint).
     */
    public function getByType(string $type, bool $withBalances = true): array {
        static $memory = [];

        $wid    = Auth::warehouseId();
        $widKey = $wid ? (int) $wid : 0;
        $safeType = preg_replace('/[^a-z_]/', '', $type) ?: 'all';
        $memKey = $safeType . ':' . $widKey . ':' . ($withBalances ? '1' : '0');
        if (isset($memory[$memKey])) {
            return $memory[$memKey];
        }

        $cacheDir  = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_party_balances';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'bal_' . $safeType . '_' . $widKey . '.json';
        $cacheTtl  = 90;
        if ($withBalances && is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $cacheTtl) {
            $decoded = json_decode((string) file_get_contents($cacheFile), true);
            // Older cache omitted statement_token — skip so the link button can appear.
            if (is_array($decoded) && ($decoded === [] || array_key_exists('statement_token', $decoded[0] ?? []))) {
                $memory[$memKey] = $decoded;
                return $decoded;
            }
        }

        $parties = $this->listRowsForType($type, $wid ? (int) $wid : null);
        if ($parties === []) {
            $memory[$memKey] = [];
            return [];
        }

        if (!$withBalances) {
            $memory[$memKey] = $parties;
            return $parties;
        }

        $parties = $this->attachListBalances($parties, $type, $wid ? (int) $wid : null);

        $memory[$memKey] = $parties;
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }
        @file_put_contents($cacheFile, json_encode($parties), LOCK_EX);

        return $parties;
    }

    /**
     * Party Master rows without ledger totals (names, phones, tokens).
     *
     * @return list<array<string,mixed>>
     */
    public function listRowsForType(string $type, ?int $warehouseId = null): array {
        $wid = $warehouseId;
        if ($wid === null) {
            $sessionWid = Auth::warehouseId();
            $wid = $sessionWid ? (int) $sessionWid : null;
        }
        [$where, $params] = $this->typeFilterClause($type, $wid);

        $this->ensureCustomerKindSchema();
        $parties = $this->db->fetchAll(
            "SELECT p.id, p.party_code, p.name, p.type, p.customer_kind, p.phone, p.city, p.is_active,
                    p.opening_balance, p.warehouse_id, p.credit_limit, p.statement_token
             FROM parties p {$where}
             ORDER BY p.name ASC",
            $params
        );
        if ($parties === []) {
            return [];
        }
        $this->backfillMissingStatementTokens($parties);

        return $parties;
    }

    /**
     * Attach unified net / display balance to Party Master rows.
     *
     * @param list<array<string,mixed>> $parties
     * @return list<array<string,mixed>>
     */
    public function attachListBalances(array $parties, string $type, ?int $warehouseId = null): array {
        if ($parties === []) {
            return [];
        }
        $ids = array_column($parties, 'id');
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $warehouseId);
        $balances = $balanceSql !== ''
            ? $this->db->fetchAll($balanceSql, $balanceParams)
            : [];

        $balMap = [];
        foreach ($balances as $b) {
            $balMap[$b['party_id']] = $b;
        }

        foreach ($parties as &$p) {
            $net = $this->netBalanceFromComponents(
                $p,
                $balMap[$p['id']] ?? null,
                $warehouseId
            );
            $p['net_balance'] = $net;
            $display            = self::displayBalanceDue($p, $net, $type !== 'all' ? $type : null);
            $p['balance_due']   = $display['amount'];
            $p['balance_perspective'] = $display['perspective'];
        }
        unset($p);

        return $parties;
    }

    // Get all parties across all warehouses (admin only - for reports)
    public function getAllForReports(): array {
        return $this->db->fetchAll(
            "SELECT p.*, w.name as warehouse_name FROM parties p
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.is_active = 1 ORDER BY w.name ASC, p.name ASC"
        );
    }

    // Get party with unified balance — single aggregation query, no correlated subqueries
    public function findWithBalance(int $id): array|false {
        $wid = Auth::warehouseId();

        return $this->findWithBalanceForWarehouse($id, $wid ? (int) $wid : 0);
    }

    public function findWithBalanceForWarehouse(int $id, int $warehouseId): array|false {
        $party = $this->db->fetchOne("SELECT * FROM parties WHERE id = ?", [$id]);
        if (!$party) {
            return false;
        }

        $whForBalance = $warehouseId > 0 ? $warehouseId : null;
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql([(int) $id], $whForBalance);
        $agg = $balanceSql !== ''
            ? $this->db->fetchOne($balanceSql, $balanceParams)
            : false;

        $party['net_balance'] = $this->netBalanceFromComponents(
            $party,
            $agg !== false ? $agg : null,
            $whForBalance
        );

        return $party;
    }

    /**
     * Date + recorded time for a statement line.
     * Uses created_at when present; otherwise the document date (no fake midnight).
     *
     * @return array{day:string,time:string}
     */
    public static function statementWhenParts(?string $date, ?string $createdAt = null): array {
        $createdAt = trim((string) $createdAt);
        $date      = trim((string) $date);
        $src       = $createdAt !== '' ? $createdAt : $date;
        $ts        = $src !== '' ? strtotime($src) : false;
        if ($ts === false) {
            return ['day' => $date !== '' ? $date : '—', 'time' => ''];
        }

        return [
            'day'  => date('d M Y', $ts),
            'time' => $createdAt !== '' ? date('h:i A', $ts) : '',
        ];
    }

    public static function statementWhenLabel(?string $date, ?string $createdAt = null): string {
        $p = self::statementWhenParts($date, $createdAt);

        return $p['time'] !== '' ? $p['day'] . ', ' . $p['time'] : $p['day'];
    }

    /**
     * Sort party statement rows by transaction date, then recorded time (created_at), then id.
     */
    public static function compareStatementTransactions(array $a, array $b): int {
        $da = (string) ($a['date'] ?? '');
        $db = (string) ($b['date'] ?? '');
        if ($da !== $db) {
            return $da <=> $db;
        }
        $ta = strtotime((string) ($a['created_at'] ?? ''));
        $tb = strtotime((string) ($b['created_at'] ?? ''));
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }
        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    }

    /**
     * Unified party statement lines (Reports → Customer Statement, field link, purchase print).
     *
     * @return list<array{id:int,txn_type:string,ref_no:string,date:string,debit:float,credit:float,notes:?string,status:string,created_at:string}>
     */
    public function getPartyStatementTransactions(int $partyId, string $fromDate = '', string $toDate = '', ?int $warehouseId = null): array {
        $warehouseId = $this->resolveScopeWarehouseId($partyId, $warehouseId);
        if ($warehouseId <= 0) {
            return [];
        }
        $this->ensureVendorBillsTable();

        [$dateFilter, $dateParams] = $this->statementDateFilter($fromDate, $toDate);
        [$purDateFilter] = $this->statementDateFilter($fromDate, $toDate, 'pur.date');

        [$whFilter, $whParams] = $this->statementTransactionWarehouseClause($warehouseId);
        [$purWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'pur.warehouse_id');

        $sales = $this->db->fetchAll(
            "SELECT id, 'sale' as txn_type, invoice_no as ref_no, date,
                    grand_total as debit, 0 as credit, notes, status, created_at
             FROM sales WHERE party_id = ? AND status != 'cancelled' {$dateFilter}{$whFilter}",
            array_merge([$partyId], $dateParams, $whParams)
        );
        $purchases = $this->db->fetchAll(
            "SELECT pur.id, 'purchase' as txn_type, pur.invoice_no as ref_no, pur.date,
                    0 as debit, " . self::purchasePartyCreditSql('pur') . " as credit,
                    pur.notes, pur.status, pur.created_at
             FROM purchases pur
             LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
             WHERE pur.party_id = ? AND pur.status != 'cancelled' {$purDateFilter}{$purWhFilter}",
            array_merge([$partyId], $dateParams, $whParams)
        );
        [$payDateFilter] = $this->statementDateFilter($fromDate, $toDate, 'py.date');
        [$payWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'py.warehouse_id');
        $payments = $this->db->fetchAll(
            "SELECT py.id,
                    CASE WHEN py.ref_type = 'purchase_order' THEN 'po_advance' ELSE 'payment' END as txn_type,
                    py.payment_no as ref_no, py.date,
                    CASE WHEN py.status = 'cancelled' THEN 0 WHEN py.payment_type = 'out' THEN py.amount ELSE 0 END as debit,
                    CASE WHEN py.status = 'cancelled' THEN 0 WHEN py.payment_type = 'in'  THEN py.amount ELSE 0 END as credit,
                    CASE
                        WHEN py.notes IS NOT NULL AND TRIM(py.notes) != '' THEN py.notes
                        WHEN py.ref_type = 'purchase_order' THEN CONCAT('Advance on ', COALESCE(po.po_no, 'PO'))
                        ELSE py.notes
                    END as notes,
                    py.status, py.created_at
             FROM payments py
             LEFT JOIN purchase_orders po ON po.id = py.ref_id AND py.ref_type = 'purchase_order'
             WHERE py.party_id = ? AND " . self::balancePaymentRefTypeSql('py.ref_type') . "
                {$payDateFilter}{$payWhFilter}",
            array_merge([$partyId], $dateParams, $whParams)
        );
        $returns = $this->db->fetchAll(
            "SELECT id, 'return' as txn_type, return_no as ref_no, date,
                    CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END as debit,
                    CASE WHEN type = 'sale_return'     THEN grand_total ELSE 0 END as credit,
                    reason as notes, status, created_at
             FROM `returns` WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}",
            array_merge([$partyId], $dateParams, $whParams)
        );
        $dumps = [];
        if ($this->dumpCreditsReady()) {
            $dumps = $this->db->fetchAll(
                "SELECT id, 'dump' as txn_type, dump_no as ref_no, date,
                        0 as debit, grand_total as credit,
                        reason as notes, status, created_at
                 FROM device_dumps WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}",
                array_merge([$partyId], $dateParams, $whParams)
            ) ?: [];
        }
        $importPayables = $this->db->fetchAll(
            "SELECT id, 'import_payable' as txn_type, accrual_no as ref_no, date,
                    0 as debit, amount as credit, description as notes, status, created_at
             FROM import_payable_accruals
             WHERE party_id = ? AND status IN ('open', 'paid') AND leg != 'packing_dxb' {$dateFilter}{$whFilter}",
            array_merge([$partyId], $dateParams, $whParams)
        );
        [$billDateFilter, $billDateParams] = $this->statementDateFilter($fromDate, $toDate, 'bill_date');
        [$billWhFilter, $billWhParams] = $this->statementTransactionWarehouseClause($warehouseId, 'warehouse_id');
        $vendorBills = $this->db->fetchAll(
            "SELECT id, 'vendor_bill' as txn_type, bill_no as ref_no, bill_date as date,
                    0 as debit, amount as credit,
                    CONCAT('Packing invoice', CASE WHEN vendor_invoice_ref IS NOT NULL AND vendor_invoice_ref != '' THEN CONCAT(' · ', vendor_invoice_ref) ELSE '' END) as notes,
                    status, created_at
             FROM import_vendor_bills
             WHERE party_id = ? AND status IN ('open', 'paid') {$billDateFilter}{$billWhFilter}",
            array_merge([$partyId], $billDateParams, $billWhParams)
        );
        [$discDateFilter, $discDateParams] = $this->statementDateFilter($fromDate, $toDate, 'py.date');
        [$discWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'py.warehouse_id');
        $discountRefNo = self::discountRefNoFromNotesSql('py');
        $discounts = $this->db->fetchAll(
            "SELECT py.id, 'discount' as txn_type,
                    {$discountRefNo} as ref_no, py.date,
                    0 as debit, py.amount as credit,
                    py.notes, py.status, py.created_at
             FROM payments py
             WHERE py.party_id = ? AND py.ref_type = 'discount' AND py.status = 'active'
                {$discDateFilter}{$discWhFilter}",
            array_merge([$partyId], $discDateParams, $whParams)
        );
        $transactions = array_merge($sales, $purchases, $payments, $returns, $dumps, $importPayables, $vendorBills, $discounts);
        usort($transactions, [self::class, 'compareStatementTransactions']);

        return $transactions;
    }

    /**
     * Party statement running balance immediately before a purchase row
     * (same debit/credit rules and created_at ordering as Reports → Party Statement).
     */
    public function runningBalanceBeforePurchase(int $partyId, int $purchaseId): float {
        $purchase = $this->db->fetchOne(
            "SELECT id, warehouse_id FROM purchases WHERE id = ? AND party_id = ? AND status != 'cancelled'",
            [$purchaseId, $partyId]
        );
        if (!$purchase) {
            return 0.0;
        }

        $party = $this->db->fetchOne("SELECT opening_balance, warehouse_id FROM parties WHERE id = ?", [$partyId]);
        if (!$party) {
            return 0.0;
        }

        $whId             = (int) $purchase['warehouse_id'];
        $targetPurchaseId = (int) $purchase['id'];
        $running          = $this->scopedOpeningBalance($party, $whId);

        foreach ($this->getPartyStatementTransactions($partyId, '', '', $whId) as $t) {
            if (($t['txn_type'] ?? '') === 'purchase' && (int) ($t['id'] ?? 0) === $targetPurchaseId) {
                break;
            }
            $running += (float) ($t['debit'] ?? 0) - (float) ($t['credit'] ?? 0);
        }

        return round($running, 3);
    }

    /**
     * Party ledger — all transactions in one unified timeline (Party Master detail).
     * Same debit/credit set as Party Statement / Due (no expenses). Capped at today
     * when $toDate is empty so Closing matches Party Master Due.
     * Separate queries (not SQL UNION) so mixed table collations cannot raise MySQL 1271.
     */
    public function getLedger(int $partyId, string $fromDate = '', string $toDate = ''): array {
        $warehouseId = $this->resolveScopeWarehouseId($partyId, null);
        if ($warehouseId <= 0) {
            return [];
        }
        $this->ensureVendorBillsTable();
        if ($toDate === '') {
            $toDate = date('Y-m-d');
        }

        [$dateFilter, $dateParams] = $this->statementDateFilter($fromDate, $toDate);
        [$purDateFilter] = $this->statementDateFilter($fromDate, $toDate, 'pur.date');

        [$whFilter, $whParam] = $this->statementTransactionWarehouseClause($warehouseId);
        [$purWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'pur.warehouse_id');

        [$discDateFilter, $discDateParams] = $this->statementDateFilter($fromDate, $toDate, 'py.date');
        [$discWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'py.warehouse_id');
        [$billDateFilter, $billDateParams] = $this->statementDateFilter($fromDate, $toDate, 'bill_date');
        [$billWhFilter, $billWhParams] = $this->statementTransactionWarehouseClause($warehouseId, 'warehouse_id');

        $baseParams = array_merge([$partyId], $dateParams, $whParam);

        $sales = $this->db->fetchAll(
            "SELECT 'sale' as type, id, invoice_no as ref_no, date, grand_total as debit, 0 as credit, balance, status, created_at
             FROM sales WHERE party_id = ? AND status != 'cancelled' {$dateFilter}{$whFilter}",
            $baseParams
        );
        $purchases = $this->db->fetchAll(
            "SELECT 'purchase' as type, pur.id, pur.invoice_no as ref_no, pur.date, 0 as debit,
                    " . self::purchasePartyCreditSql('pur') . " as credit, pur.balance, pur.status, pur.created_at
             FROM purchases pur
             LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
             WHERE pur.party_id = ? AND pur.status != 'cancelled' {$purDateFilter}{$purWhFilter}",
            $baseParams
        );
        $importPayables = $this->db->fetchAll(
            "SELECT 'import_payable' as type, id, accrual_no as ref_no, date, 0 as debit, amount as credit, 0 as balance, status, created_at
             FROM import_payable_accruals
             WHERE party_id = ? AND status IN ('open', 'paid') AND leg != 'packing_dxb' {$dateFilter}{$whFilter}",
            $baseParams
        );
        $vendorBills = $this->db->fetchAll(
            "SELECT 'vendor_bill' as type, id, bill_no as ref_no, bill_date as date, 0 as debit, amount as credit, 0 as balance, status, created_at
             FROM import_vendor_bills
             WHERE party_id = ? AND status IN ('open', 'paid') {$billDateFilter}{$billWhFilter}",
            array_merge([$partyId], $billDateParams, $billWhParams)
        );
        [$payDateFilter] = $this->statementDateFilter($fromDate, $toDate, 'py.date');
        [$payWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'py.warehouse_id');
        $payments = $this->db->fetchAll(
            "SELECT CASE WHEN py.ref_type = 'purchase_order' THEN 'po_advance' ELSE 'payment' END as type,
                    py.id, py.payment_no as ref_no, py.date,
                    CASE WHEN py.status = 'cancelled' THEN 0 WHEN py.payment_type = 'out' THEN py.amount ELSE 0 END as debit,
                    CASE WHEN py.status = 'cancelled' THEN 0 WHEN py.payment_type = 'in' THEN py.amount ELSE 0 END as credit,
                    0 as balance, py.status, py.created_at
             FROM payments py
             WHERE py.party_id = ? AND py.status = 'active' AND " . self::balancePaymentRefTypeSql('py.ref_type') . "
                {$payDateFilter}{$payWhFilter}",
            array_merge([$partyId], $dateParams, $whParam)
        );
        $returns = $this->db->fetchAll(
            "SELECT 'return' as type, id, return_no as ref_no, date,
                    CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END as debit,
                    CASE WHEN type = 'sale_return' THEN grand_total ELSE 0 END as credit,
                    0 as balance, status, created_at
             FROM `returns` WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}",
            $baseParams
        );
        $dumps = [];
        if ($this->dumpCreditsReady()) {
            $dumps = $this->db->fetchAll(
                "SELECT 'dump' as type, id, dump_no as ref_no, date,
                        0 as debit, grand_total as credit, 0 as balance, status, created_at
                 FROM device_dumps WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}",
                $baseParams
            ) ?: [];
        }
        $discounts = $this->db->fetchAll(
            "SELECT 'discount' as type, py.id, " . self::discountRefNoFromNotesSql('py') . " as ref_no, py.date,
                    0 as debit, py.amount as credit, 0 as balance, py.status, py.created_at
             FROM payments py
             WHERE py.party_id = ? AND py.ref_type = 'discount' AND py.status = 'active'
                {$discDateFilter}{$discWhFilter}",
            array_merge([$partyId], $discDateParams, $whParam)
        );

        $rows = array_merge($sales, $purchases, $importPayables, $vendorBills, $payments, $returns, $dumps, $discounts);
        usort($rows, [self::class, 'compareStatementTransactions']);

        return $rows;
    }

    // Next party code: 26001, 26002... (year prefix + sequence)
    public function nextPartyCode(): string {
        $yearPrefix = date('y'); // 26 for 2026, 27 for 2027
        $last = $this->db->fetchOne(
            "SELECT party_code FROM parties WHERE party_code LIKE ? ORDER BY party_code DESC LIMIT 1 FOR UPDATE",
            [$yearPrefix . '%']
        );
        if ($last && $last['party_code']) {
            $seq = (int) substr($last['party_code'], 2); // remove year prefix, get sequence
            $seq++;
        } else {
            $seq = 1;
        }
        return $yearPrefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    private static function tradeLicenseSchemaFlagPath(): string {
        $dir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_schema';
        return $dir . DIRECTORY_SEPARATOR . 'parties_trade_license.ok';
    }

    private static function customerKindSchemaFlagPath(): string {
        $dir = rtrim((string) sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'iqbal_erp_schema';
        return $dir . DIRECTORY_SEPARATOR . 'parties_customer_kind.ok';
    }

    /** Ensure parties.customer_kind exists (wholesale | retail). Existing rows default to wholesale. */
    public function ensureCustomerKindSchema(): void {
        if (self::$customerKindSchemaReady === true) {
            return;
        }
        if (self::$customerKindSchemaReady === false) {
            return;
        }

        $flag = self::customerKindSchemaFlagPath();
        if (is_file($flag)) {
            self::$customerKindSchemaReady = true;
            return;
        }

        try {
            $col = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'parties'
                   AND COLUMN_NAME = 'customer_kind'
                 LIMIT 1"
            );
            if (!$col) {
                $this->db->execute(
                    "ALTER TABLE parties
                     ADD COLUMN customer_kind VARCHAR(16) NOT NULL DEFAULT 'wholesale' AFTER type"
                );
            }
            $flagDir = dirname($flag);
            if (!is_dir($flagDir)) {
                @mkdir($flagDir, 0700, true);
            }
            @file_put_contents($flag, (string) time(), LOCK_EX);
            self::$customerKindSchemaReady = true;
        } catch (Throwable $e) {
            error_log('[Party] ensureCustomerKindSchema failed: ' . $e->getMessage());
            self::$customerKindSchemaReady = false;
        }
    }

    /**
     * Ensure trade license columns exist.
     * After first success, skips information_schema via a temp-file flag (cheap on Hostinger).
     */
    public function ensureTradeLicenseSchema(): void {
        if (self::$tradeLicenseSchemaReady === true) {
            return;
        }
        if (self::$tradeLicenseSchemaReady === false) {
            return;
        }

        $flag = self::tradeLicenseSchemaFlagPath();
        if (is_file($flag)) {
            self::$tradeLicenseSchemaReady = true;
            return;
        }

        try {
            $col = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'parties'
                   AND COLUMN_NAME = 'trade_license_expires_on'
                 LIMIT 1"
            );
            if (!$col) {
                $this->db->execute(
                    "ALTER TABLE parties
                     ADD COLUMN trade_license_file VARCHAR(255) NULL AFTER id_card,
                     ADD COLUMN trade_license_expires_on DATE NULL AFTER trade_license_file"
                );
                try {
                    $this->db->execute(
                        "CREATE INDEX idx_parties_trade_license_expiry
                         ON parties (trade_license_expires_on, type, is_active)"
                    );
                } catch (Throwable $e) {
                    // Index may already exist
                }
            }
            $flagDir = dirname($flag);
            if (!is_dir($flagDir)) {
                @mkdir($flagDir, 0700, true);
            }
            @file_put_contents($flag, (string) time(), LOCK_EX);
            self::$tradeLicenseSchemaReady = true;
        } catch (Throwable $e) {
            error_log('[Party] ensureTradeLicenseSchema failed: ' . $e->getMessage());
            self::$tradeLicenseSchemaReady = false;
        }
    }

    /** Active suppliers / freight / both with trade license expiry set. */
    public function tradeLicenseExpiryCounts(): array {
        $this->ensureTradeLicenseSchema();
        try {
            $row = $this->db->fetchOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN trade_license_expires_on < CURDATE() THEN 1 ELSE 0 END), 0) AS expired,
                    COALESCE(SUM(CASE WHEN trade_license_expires_on >= CURDATE()
                        AND trade_license_expires_on <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS due_soon
                 FROM parties
                 WHERE is_active = 1
                   AND type IN ('supplier', 'both', 'freight_forwarder')
                   AND trade_license_expires_on IS NOT NULL"
            );
            return [
                'expired'  => (int) ($row['expired'] ?? 0),
                'due_soon' => (int) ($row['due_soon'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['expired' => 0, 'due_soon' => 0];
        }
    }

    // Create party with auto-generated party_code
    public function create(array $data): int|false {
        $this->ensureTradeLicenseSchema();
        $this->ensureCustomerKindSchema();
        $code  = $this->nextPartyCode();
        $token = app_statement_token_new();
        $type  = (string) ($data['type'] ?? 'customer');
        $kind  = self::normalizeCustomerKind($data['customer_kind'] ?? null, $type);
        return $this->db->insert(
            "INSERT INTO parties (party_code, name, contact_person, type, customer_kind, phone, phone2, email, address, city, country, tax_no, id_card, trade_license_file, trade_license_expires_on, credit_limit, opening_balance, notes, warehouse_id, statement_token)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $code,
                $data['name'],
                $data['contact_person'] ?: null,
                $type,
                $kind,
                $data['phone'] ?: null,
                $data['phone2'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
                $data['city'] ?: null,
                $data['country'] ?? 'Kuwait',
                $data['tax_no'] ?: null,
                $data['id_card'] ?: null,
                $data['trade_license_file'] ?? null,
                $data['trade_license_expires_on'] ?? null,
                (float) ($data['credit_limit'] ?? 0),
                (float) ($data['opening_balance'] ?? 0),
                $data['notes'] ?: null,
                Auth::warehouseId() ?: null,
                $token,
            ]
        );
    }

    // Update party (opening_balance is locked — only PartyLedgerZeroService / dedicated SQL may change it)
    public function update(int $id, array $data): int {
        $this->ensureTradeLicenseSchema();
        $this->ensureCustomerKindSchema();
        $type = (string) ($data['type'] ?? 'customer');
        $kind = self::normalizeCustomerKind($data['customer_kind'] ?? null, $type);
        return $this->db->execute(
            "UPDATE parties SET name=?, contact_person=?, type=?, customer_kind=?, phone=?, phone2=?, email=?, address=?,
             city=?, country=?, tax_no=?, id_card=?, trade_license_file=?, trade_license_expires_on=?,
             credit_limit=?, notes=?, is_active=?
             WHERE id=?",
            [
                $data['name'],
                $data['contact_person'] ?: null,
                $type,
                $kind,
                $data['phone'] ?: null,
                $data['phone2'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
                $data['city'] ?: null,
                $data['country'] ?? 'Kuwait',
                $data['tax_no'] ?: null,
                $data['id_card'] ?: null,
                $data['trade_license_file'] ?? null,
                $data['trade_license_expires_on'] ?? null,
                (float) ($data['credit_limit'] ?? 0),
                $data['notes'] ?: null,
                (int) ($data['is_active'] ?? 1),
                $id,
            ]
        );
    }

    /**
     * Search parties for autocomplete.
     *
     * Performance: match name/phone first with a cheap branch filter (home warehouse /
     * unassigned only — no correlated EXISTS on sales/payments). Optionally batch-compute
     * unified net balance for the small result set.
     *
     * Visibility via cross-branch ledger activity (EXISTS in warehouseVisibilitySqlAndParams)
     * is intentionally omitted here; that path is for Party Master lists. Autocomplete
     * must stay snappy on Receive Payment / New Sale.
     *
     * @param bool $withBalance When false, skip the 8-way balance union (names/phones only).
     */
    public function search(string $query, string $type = 'all', bool $withBalance = true): array {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $like       = '%' . $query . '%';
        $prefixLike = $query . '%';
        $params     = [];
        $typeClause = '';

        if ($type === 'payment_out') {
            // Pay anyone on the ledger: suppliers, freight, both, or customer-only.
            $typeClause = "AND p.type IN ('customer', 'supplier', 'both', 'freight_forwarder')";
        } elseif ($type === 'purchase') {
            // Buy from suppliers, both, or customer-only (trade-in / buy-back).
            $typeClause = "AND p.type IN ('customer', 'supplier', 'both')";
        } elseif ($type !== 'all') {
            $typeClause = "AND (p.type = ? OR p.type = 'both')";
            $params[] = $type;
        }

        $wid = Auth::warehouseId();
        // Cheap branch scope only — avoids 4× EXISTS per candidate row on every keystroke.
        $whClause = '';
        if ($wid) {
            $whClause = ' AND (p.warehouse_id IS NULL OR p.warehouse_id = ?)';
            $params[] = (int) $wid;
        }

        $params = array_merge($params, [
            $like, $like, $like, $like, $like,
            $prefixLike, $prefixLike, $prefixLike,
        ]);

        $this->ensureCustomerKindSchema();
        $parties = $this->db->fetchAll(
            "SELECT p.id, p.name, p.phone, p.type, p.customer_kind, p.credit_limit, p.opening_balance, p.party_code
             FROM parties p
             WHERE p.is_active = 1 {$typeClause} {$whClause}
               AND (p.name LIKE ? OR p.phone LIKE ? OR p.phone2 LIKE ? OR p.id_card LIKE ? OR p.party_code LIKE ?)
             ORDER BY
               CASE
                 WHEN p.name LIKE ? THEN 0
                 WHEN p.phone LIKE ? OR p.phone2 LIKE ? THEN 1
                 ELSE 2
               END,
               p.name ASC
             LIMIT 15",
            $params
        );

        if ($parties === [] || !$withBalance) {
            return $parties;
        }

        return $this->attachSearchBalances($parties, $type, $wid ? (int) $wid : null);
    }

    /**
     * Active party rows by id, preserving the given order (autocomplete enrich).
     *
     * @param list<int> $ids
     * @return list<array<string,mixed>>
     */
    public function findActiveByIds(array $ids): array {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));
        if ($ids === []) {
            return [];
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $whClause = '';
        $wid = Auth::warehouseId();
        if ($wid) {
            // Same cheap branch scope as search() — do not expose other-branch parties by id.
            $whClause = ' AND (warehouse_id IS NULL OR warehouse_id = ?)';
            $params[] = (int) $wid;
        }

        $this->ensureCustomerKindSchema();
        $rows = $this->db->fetchAll(
            "SELECT id, name, phone, type, customer_kind, credit_limit, opening_balance, party_code
             FROM parties WHERE id IN ($ph) AND is_active = 1{$whClause}",
            $params
        );

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * Attach unified net balances to autocomplete rows (same rules as Party Master).
     *
     * @param list<array<string,mixed>> $parties
     * @return list<array<string,mixed>>
     */
    public function attachSearchBalances(array $parties, string $type = 'all', ?int $warehouseId = null): array {
        if ($parties === []) {
            return [];
        }

        $wid = $warehouseId;
        if ($wid === null) {
            $sessionWid = Auth::warehouseId();
            $wid = $sessionWid ? (int) $sessionWid : null;
        }

        $ids = array_map(static fn(array $p): int => (int) $p['id'], $parties);
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $wid);
        $bals = $balanceSql !== ''
            ? $this->db->fetchAll($balanceSql, $balanceParams)
            : [];

        $balMap = [];
        foreach ($bals as $b) {
            $balMap[(int) $b['party_id']] = $b;
        }

        foreach ($parties as &$p) {
            $net = $this->netBalanceFromComponents(
                $p,
                $balMap[(int) $p['id']] ?? null,
                $wid
            );
            // Payment Out: always payable view (positive = we owe), including customer-only parties.
            $p['balance'] = $type === 'payment_out' ? (-1 * $net) : $net;
        }
        unset($p);

        return $parties;
    }

    /**
     * Active customers/both parties visible in the current warehouse (dropdowns; no balance work).
     *
     * @return list<array{id:int,name:string,party_code:?string,phone:?string,phone2:?string}>
     */
    public function getCustomersForSelect(): array {
        $wid = (int) Auth::warehouseId();
        $where = "WHERE p.is_active = 1 AND (p.type = 'customer' OR p.type = 'both')";
        $params = [];
        if ($wid > 0) {
            [$whSql, $whParams] = $this->warehouseVisibilitySqlAndParams($wid);
            $where .= $whSql;
            $params = $whParams;
        }

        return $this->db->fetchAll(
            "SELECT p.id, p.name, p.party_code, p.phone, p.phone2 FROM parties p {$where} ORDER BY p.name ASC",
            $params
        );
    }

    /**
     * Party row for mandoob save if it is a visible customer in this warehouse.
     *
     * @return array{id:int,name:string,phone:?string,phone2:?string}|null
     */
    public function getForMandoobSchedule(int $partyId): ?array {
        if ($partyId <= 0) {
            return null;
        }
        $wid = (int) Auth::warehouseId();
        $where = "WHERE p.id = ? AND p.is_active = 1 AND (p.type = 'customer' OR p.type = 'both')";
        $params = [$partyId];
        if ($wid > 0) {
            [$whSql, $whParams] = $this->warehouseVisibilitySqlAndParams($wid);
            $where .= $whSql;
            $params = array_merge($params, $whParams);
        }

        $row = $this->db->fetchOne(
            "SELECT p.id, p.name, p.phone, p.phone2 FROM parties p {$where}",
            $params
        );

        return $row ?: null;
    }

    /**
     * Public field-statement token for customer/both parties.
     * Creates one when missing (legacy rows predate statement_token on create).
     */
    public function ensureStatementToken(int $partyId): ?string {
        if ($partyId <= 0) {
            return null;
        }
        $row = $this->db->fetchOne(
            'SELECT id, type, statement_token FROM parties WHERE id = ?',
            [$partyId]
        );
        if (!$row) {
            return null;
        }
        if (!in_array((string) ($row['type'] ?? ''), ['customer', 'both'], true)) {
            return null;
        }
        $existing = trim((string) ($row['statement_token'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $token = app_statement_token_new();
        $this->db->execute(
            "UPDATE parties SET statement_token = ?
             WHERE id = ? AND (statement_token IS NULL OR statement_token = '')",
            [$token, $partyId]
        );
        $again = $this->db->fetchOne(
            'SELECT statement_token FROM parties WHERE id = ?',
            [$partyId]
        );
        $final = trim((string) ($again['statement_token'] ?? ''));

        return $final !== '' ? $final : $token;
    }

    /**
     * Assign statement tokens to customer/both rows that still have none (in-place).
     *
     * @param list<array<string,mixed>> $parties
     */
    private function backfillMissingStatementTokens(array &$parties): void {
        foreach ($parties as &$p) {
            $type = (string) ($p['type'] ?? '');
            if (!in_array($type, ['customer', 'both'], true)) {
                continue;
            }
            if (trim((string) ($p['statement_token'] ?? '')) !== '') {
                continue;
            }
            $token = $this->ensureStatementToken((int) ($p['id'] ?? 0));
            if ($token !== null) {
                $p['statement_token'] = $token;
            }
        }
        unset($p);
    }

    /**
     * Party row + unified net balance for a public statement token.
     */
    public function findByStatementToken(string $token): array|false {
        $row = $this->db->fetchOne(
            "SELECT id FROM parties WHERE statement_token = ?",
            [$token]
        );
        if (!$row) {
            return false;
        }
        $partyId = (int) $row['id'];
        $whId    = $this->resolvePublicStatementWarehouseId($partyId);
        $party   = $this->findWithBalanceForWarehouse($partyId, $whId);
        if (!$party) {
            return false;
        }
        $party['opening_balance'] = $this->scopedOpeningBalance($party, $whId);
        $party['statement_warehouse_id'] = $whId;

        return $party;
    }

    /**
     * Unified statement timeline (matches Reports > Customer Statement).
     * Debit = balance increases (they owe more). Credit = balance decreases.
     *
     * @return list<array{type:string,ref_no:string,date:string,debit:float|int,credit:float|int,status:string,created_at:string}>
     */
    public function getUnifiedStatementTransactions(int $partyId, string $fromDate = '', string $toDate = '', ?int $warehouseId = null): array {
        $typeLabels = [
            'sale'     => 'Sale',
            'purchase' => 'Purchase',
            'payment'    => 'Payment',
            'po_advance' => 'PO Advance',
            'return'     => 'Return',
            'dump'       => 'Dump',
            'discount' => 'Discount',
        ];

        $rows = $this->getPartyStatementTransactions($partyId, $fromDate, $toDate, $warehouseId);
        $out  = [];

        foreach ($rows as $t) {
            $out[] = [
                'type'       => $typeLabels[$t['txn_type'] ?? ''] ?? ucfirst((string) ($t['txn_type'] ?? '')),
                'ref_no'     => $t['ref_no'],
                'date'       => $t['date'],
                'debit'      => $t['debit'],
                'credit'     => $t['credit'],
                'status'     => $t['status'] ?? 'paid',
                'created_at' => $t['created_at'],
            ];
        }

        return $out;
    }
}
