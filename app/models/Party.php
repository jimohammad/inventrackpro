<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/WarehouseScope.php';

class Party extends BaseModel {
    protected string $table = 'parties';

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
            . ' THEN po_conv.subtotal_kwd + COALESCE(po_conv.other_charges_kwd, 0)'
            . " ELSE {$purchaseAlias}.grand_total END";
    }

    /** Outbound payments counted in party balance (excludes PO advances awaiting goods). */
    private static function balanceOutboundPaymentSql(string $refColumn = 'ref_type'): string {
        return "payment_type = 'out' AND " . self::balancePaymentRefTypeSql($refColumn)
            . " AND {$refColumn} != 'purchase_order'";
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
     * @return array{0:string,1:array<int,int>}
     */
    private function warehouseVisibilitySqlAndParams(int $warehouseId): array {
        if ($warehouseId <= 0) {
            return ['', []];
        }
        $sql = " AND (
            p.warehouse_id IS NULL
            OR p.warehouse_id = ?
            OR EXISTS (SELECT 1 FROM sales s WHERE s.party_id = p.id AND s.warehouse_id = ? AND s.status != 'cancelled')
            OR EXISTS (SELECT 1 FROM purchases pur WHERE pur.party_id = p.id AND pur.warehouse_id = ? AND pur.status != 'cancelled')
            OR EXISTS (SELECT 1 FROM payments pay WHERE pay.party_id = p.id AND pay.warehouse_id = ?)
            OR EXISTS (SELECT 1 FROM `returns` r WHERE r.party_id = p.id AND r.warehouse_id = ?)
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

    /**
     * @param list<int> $partyIds
     * @return array{0:string,1:list<int|string>}
     */
    private function batchBalanceUnionSql(array $partyIds, ?int $warehouseId, string $asOfDate = ''): array {
        if ($partyIds === []) {
            return ['', []];
        }
        $ph = implode(',', array_fill(0, count($partyIds), '?'));
        [$whSql, $whParams] = $this->balanceTransactionWarehouseClause($warehouseId);
        [$purWhSql] = $this->balanceTransactionWarehouseClause($warehouseId, 'pur.warehouse_id');
        [$dateSql, $dateParams] = $this->batchBalanceDateClause($asOfDate);
        [$purDateSql] = $this->batchBalanceDateClause($asOfDate, 'pur.date');
        $branchParams = array_merge($partyIds, $whParams, $dateParams);

        $sql = "SELECT party_id,
                    SUM(sales_total) as sales_total,
                    SUM(sale_payments) as sale_payments,
                    SUM(sale_returns) as sale_returns,
                    SUM(purchase_total) as purchase_total,
                    SUM(purchase_payments) as purchase_payments,
                    SUM(purchase_returns) as purchase_returns
             FROM (
                SELECT party_id, grand_total as sales_total, 0 as sale_payments, 0 as sale_returns, 0 as purchase_total, 0 as purchase_payments, 0 as purchase_returns
                FROM sales WHERE party_id IN ($ph) AND status != 'cancelled'{$whSql}{$dateSql}
                UNION ALL
                SELECT party_id, 0, amount, 0, 0, 0, 0
                FROM payments WHERE party_id IN ($ph) AND payment_type = 'in'
                    AND " . self::balancePaymentRefTypeSql() . " AND status = 'active'{$whSql}{$dateSql}
                UNION ALL
                SELECT party_id, 0, 0, grand_total, 0, 0, 0
                FROM `returns` WHERE party_id IN ($ph) AND type = 'sale_return' AND status = 'approved'{$whSql}{$dateSql}
                UNION ALL
                SELECT pur.party_id, 0, 0, 0, " . self::purchasePartyCreditSql('pur') . ", 0, 0
                FROM purchases pur
                LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
                WHERE pur.party_id IN ($ph) AND pur.status != 'cancelled'{$purWhSql}{$purDateSql}
                UNION ALL
                SELECT party_id, 0, 0, 0, amount, 0, 0
                FROM import_payable_accruals WHERE party_id IN ($ph) AND status = 'open'{$whSql}{$dateSql}
                UNION ALL
                SELECT party_id, 0, 0, 0, 0, amount, 0
                FROM payments WHERE party_id IN ($ph) AND " . self::balanceOutboundPaymentSql() . " AND status = 'active'{$whSql}{$dateSql}
                UNION ALL
                SELECT party_id, 0, 0, 0, 0, 0, grand_total
                FROM `returns` WHERE party_id IN ($ph) AND type = 'purchase_return' AND status = 'approved'{$whSql}{$dateSql}
                UNION ALL
                SELECT party_id, 0, amount, 0, 0, 0, 0
                FROM payments WHERE party_id IN ($ph) AND ref_type = 'discount' AND payment_type = 'in'
                    AND status = 'active'{$whSql}{$dateSql}
             ) t GROUP BY party_id";

        return [
            $sql,
            array_merge($branchParams, $branchParams, $branchParams, $branchParams, $branchParams, $branchParams, $branchParams, $branchParams),
        ];
    }

    /**
     * Receivable / payable totals across active parties (Dashboard, NetWorth, balance sheet header).
     *
     * Unified net: positive = they owe us (receivable); negative = we owe them (payable).
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
     *         + what they owe us  (sales - sale payments - sale returns)
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
        if (!is_dir($cacheDir)) {
            return;
        }
        foreach (glob($cacheDir . DIRECTORY_SEPARATOR . 'parties_*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    // Get all parties of a specific type, scoped to current warehouse
    public function getByType(string $type): array {
        $wid = Auth::warehouseId();

        [$where, $params] = $this->typeFilterClause($type, $wid ? (int) $wid : null);

        // Fast: fetch parties first, then compute balances in one pass
        $parties = $this->db->fetchAll("SELECT p.* FROM parties p {$where} ORDER BY p.name ASC", $params);
        if (empty($parties)) return [];

        $ids = array_column($parties, 'id');
        $whForBalance = $wid ? (int) $wid : null;
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $whForBalance);
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
                $whForBalance
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
        $payments = $this->db->fetchAll(
            "SELECT id, 'payment' as txn_type, payment_no as ref_no, date,
                    CASE WHEN status = 'cancelled' THEN 0 WHEN payment_type = 'out' THEN amount ELSE 0 END as debit,
                    CASE WHEN status = 'cancelled' THEN 0 WHEN payment_type = 'in'  THEN amount ELSE 0 END as credit,
                    notes, status, created_at
             FROM payments WHERE party_id = ? AND " . self::balancePaymentRefTypeSql() . "
                AND ref_type != 'purchase_order' {$dateFilter}{$whFilter}",
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
        $importPayables = $this->db->fetchAll(
            "SELECT id, 'import_payable' as txn_type, accrual_no as ref_no, date,
                    0 as debit, amount as credit, description as notes, status, created_at
             FROM import_payable_accruals
             WHERE party_id = ? AND status = 'open' {$dateFilter}{$whFilter}",
            array_merge([$partyId], $dateParams, $whParams)
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
        $transactions = array_merge($sales, $purchases, $payments, $returns, $importPayables, $discounts);
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

    // Party ledger — ALL transactions in one unified timeline
    // BUG FIX: Date filter params must be replicated for each UNION ALL branch.
    // Previously only one copy of date params was appended, but the SQL has 5 branches
    // each with {$dateFilter} placeholders, causing PDO parameter count mismatch.
    public function getLedger(int $partyId, string $fromDate = '', string $toDate = ''): array {
        $warehouseId = $this->resolveScopeWarehouseId($partyId, null);
        if ($warehouseId <= 0) {
            return [];
        }

        [$dateFilter, $dateParams] = $this->statementDateFilter($fromDate, $toDate);
        [$purDateFilter] = $this->statementDateFilter($fromDate, $toDate, 'pur.date');

        [$whFilter, $whParam] = $this->statementTransactionWarehouseClause($warehouseId);
        [$purWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'pur.warehouse_id');
        $purWhParam = $whParam;

        [$discDateFilter, $discDateParams] = $this->statementDateFilter($fromDate, $toDate, 'py.date');
        [$discWhFilter] = $this->statementTransactionWarehouseClause($warehouseId, 'py.warehouse_id');

        $params = array_merge(
            [$partyId], $dateParams, $whParam,
            [$partyId], $dateParams, $purWhParam,
            [$partyId], $dateParams, $whParam,
            [$partyId], $dateParams, $whParam,
            [$partyId], $dateParams, $whParam,
            [$partyId], $dateParams, $whParam,
            [$partyId], $discDateParams, $whParam
        );

        $rows = $this->db->fetchAll(
            "SELECT 'sale' as type, id, invoice_no as ref_no, date, grand_total as debit, 0 as credit, balance, status, created_at
             FROM sales WHERE party_id = ? AND status != 'cancelled' {$dateFilter}{$whFilter}
             UNION ALL
             SELECT 'purchase', pur.id, pur.invoice_no, pur.date, 0,
                    " . self::purchasePartyCreditSql('pur') . ", pur.balance, pur.status, pur.created_at
             FROM purchases pur
             LEFT JOIN purchase_orders po_conv ON po_conv.converted_to = pur.id AND po_conv.status = 'converted'
             WHERE pur.party_id = ? AND pur.status != 'cancelled' {$purDateFilter}{$purWhFilter}
             UNION ALL
             SELECT 'import_payable', id, accrual_no, date, 0, amount, 0, status, created_at
             FROM import_payable_accruals WHERE party_id = ? AND status = 'open' {$dateFilter}{$whFilter}
             UNION ALL
             SELECT 'payment', id, payment_no, date,
                    CASE WHEN status = 'cancelled' THEN 0 WHEN payment_type = 'out' THEN amount ELSE 0 END,
                    CASE WHEN status = 'cancelled' THEN 0 WHEN payment_type = 'in' THEN amount ELSE 0 END,
                    0, status, created_at
             FROM payments WHERE party_id = ? AND " . self::balancePaymentRefTypeSql() . "
                AND ref_type != 'purchase_order' {$dateFilter}{$whFilter}
             UNION ALL
             SELECT 'return', id, return_no, date,
                    CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END,
                    CASE WHEN type = 'sale_return' THEN grand_total ELSE 0 END,
                    0, status, created_at
             FROM `returns` WHERE party_id = ? AND status = 'approved' {$dateFilter}{$whFilter}
             UNION ALL
             SELECT 'expense', id, expense_no, date, amount, 0, 0, 'paid', created_at
             FROM expenses WHERE party_id = ? {$dateFilter}{$whFilter}
             UNION ALL
             SELECT 'discount', py.id, " . self::discountRefNoFromNotesSql('py') . ", py.date,
                    0, py.amount, 0, py.status, py.created_at
             FROM payments py
             WHERE py.party_id = ? AND py.ref_type = 'discount' AND py.status = 'active'
                {$discDateFilter}{$discWhFilter}",
            $params
        );

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

    // Create party with auto-generated party_code
    public function create(array $data): int|false {
        $code  = $this->nextPartyCode();
        $token = app_statement_token_new();
        return $this->db->insert(
            "INSERT INTO parties (party_code, name, contact_person, type, phone, phone2, email, address, city, country, tax_no, id_card, credit_limit, opening_balance, notes, warehouse_id, statement_token)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $code,
                $data['name'],
                $data['contact_person'] ?: null,
                $data['type'],
                $data['phone'] ?: null,
                $data['phone2'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
                $data['city'] ?: null,
                $data['country'] ?? 'Kuwait',
                $data['tax_no'] ?: null,
                $data['id_card'] ?: null,
                (float) ($data['credit_limit'] ?? 0),
                (float) ($data['opening_balance'] ?? 0),
                $data['notes'] ?: null,
                Auth::warehouseId() ?: null,
                $token,
            ]
        );
    }

    // Update party
    public function update(int $id, array $data): int {
        return $this->db->execute(
            "UPDATE parties SET name=?, contact_person=?, type=?, phone=?, phone2=?, email=?, address=?,
             city=?, country=?, tax_no=?, id_card=?, credit_limit=?, opening_balance=?, notes=?, is_active=?
             WHERE id=?",
            [
                $data['name'],
                $data['contact_person'] ?: null,
                $data['type'],
                $data['phone'] ?: null,
                $data['phone2'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
                $data['city'] ?: null,
                $data['country'] ?? 'Kuwait',
                $data['tax_no'] ?: null,
                $data['id_card'] ?: null,
                (float) ($data['credit_limit'] ?? 0),
                (float) ($data['opening_balance'] ?? 0),
                $data['notes'] ?: null,
                (int) ($data['is_active'] ?? 1),
                $id,
            ]
        );
    }

    // Search parties (for autocomplete). Step 1: match 15 rows. Step 2: batch-compute
    // unified net balance for matched IDs (same rules as Party Master / findWithBalance).
    public function search(string $query, string $type = 'all'): array {
        $like = "%{$query}%";
        $params = [];
        $typeClause = '';

        if ($type === 'payment_out') {
            $typeClause = "AND p.type IN ('supplier', 'both', 'freight_forwarder')";
        } elseif ($type !== 'all') {
            $typeClause = "AND (p.type = ? OR p.type = 'both')";
            $params[] = $type;
        }

        $wid = Auth::warehouseId();
        $whClause = '';
        if ($wid) {
            [$whSql, $whParams] = $this->warehouseVisibilitySqlAndParams((int) $wid);
            $whClause = $whSql;
            $params   = array_merge($params, $whParams);
        }

        $params = array_merge($params, [$like, $like, $like, $like, $like]);

        $parties = $this->db->fetchAll(
            "SELECT p.id, p.name, p.phone, p.type, p.credit_limit, p.opening_balance, p.party_code
             FROM parties p
             WHERE p.is_active = 1 {$typeClause} {$whClause}
               AND (p.name LIKE ? OR p.phone LIKE ? OR p.phone2 LIKE ? OR p.id_card LIKE ? OR p.party_code LIKE ?)
             ORDER BY p.name ASC
             LIMIT 15",
            $params
        );

        if (empty($parties)) return [];

        // Batch-compute unified net balance for matched IDs only (same components as getByType / findWithBalance).
        // Previously this used sales-only math, so Party Master could show "Clear" while New Sale still showed
        // "Due" for the same party after a credit purchase (purchase excluded from autocomplete balance).
        $ids = array_column($parties, 'id');
        $whForBalance = $wid ? (int) $wid : null;
        [$balanceSql, $balanceParams] = $this->batchBalanceUnionSql($ids, $whForBalance);
        $bals = $balanceSql !== ''
            ? $this->db->fetchAll($balanceSql, $balanceParams)
            : [];

        $balMap = [];
        foreach ($bals as $b) {
            $balMap[$b['party_id']] = $b;
        }

        foreach ($parties as &$p) {
            $net = $this->netBalanceFromComponents(
                $p,
                $balMap[$p['id']] ?? null,
                $whForBalance
            );
            $p['balance'] = $type === 'payment_out'
                ? self::displayBalanceDue($p, $net, 'supplier')['amount']
                : $net;
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
            'payment'  => 'Payment',
            'return'   => 'Return',
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
