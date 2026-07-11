<?php

/**
 * Rebuild account balances from ledger tables (single source of truth).
 *
 * Used by Settings → Recalculate, Balance Audit, Reconciliation, Account Statement,
 * and NetWorthService cash totals.
 */
class AccountBalanceService {

    /** @return array{0:string,1:list<int>} */
    public static function warehouseSqlAndParams(?int $warehouseId, string $column = 'warehouse_id'): array {
        if ($warehouseId === null || $warehouseId <= 0) {
            return ['', []];
        }
        return [" AND ({$column} = ? OR {$column} IS NULL)", [(int) $warehouseId]];
    }

    /** @return array{0:string,1:list<string>} */
    private static function dateLimitSql(string $asOfDate, string $column = 'date'): array {
        if ($asOfDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate)) {
            return ['', []];
        }
        return [" AND {$column} <= ?", [$asOfDate]];
    }

    /** @return array{0:string,1:list<string>} */
    public static function dateBetweenSql(string $fromDate, string $toDate, string $column = 'date'): array {
        $sql    = '';
        $params = [];
        if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $sql     .= " AND {$column} >= ?";
            $params[] = $fromDate;
        }
        if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $sql     .= " AND {$column} <= ?";
            $params[] = $toDate;
        }
        return [$sql, $params];
    }

    /** Active payment rows that affect account balance (excludes discount; skips cancelled PO/purchase refs). */
    public static function sqlPaymentLedgerWhere(string $paymentAlias = 'p'): string {
        return " {$paymentAlias}.ref_type != 'discount'
             AND {$paymentAlias}.status = 'active'
             AND (
                 {$paymentAlias}.ref_type NOT IN ('purchase', 'purchase_order')
                 OR ({$paymentAlias}.ref_type = 'purchase' AND NOT EXISTS (
                     SELECT 1 FROM purchases pur
                     WHERE pur.id = {$paymentAlias}.ref_id AND pur.status = 'cancelled'
                 ))
                 OR ({$paymentAlias}.ref_type = 'purchase_order' AND NOT EXISTS (
                     SELECT 1 FROM purchase_orders po
                     WHERE po.id = {$paymentAlias}.ref_id AND po.status = 'cancelled'
                 ))
             )";
    }

    /**
     * @return array{
     *   opening: float,
     *   payments_net: float,
     *   expenses: float,
     *   transfers_net: float,
     *   adjustments_net: float,
     *   po_unlinked_out: float,
     *   balance: float,
     *   payments_out: float,
     *   payments_in: float,
     *   account_name: string
     * }
     */
    public static function computeFromLedger(Database $db, int $accountId): array {
        return self::computeFromLedgerAsOf($db, $accountId, '', null);
    }

    /**
     * Ledger balance for one account as of a date (inclusive), optionally branch-scoped.
     * Omit $asOfDate for “all history”; omit $warehouseId for company-wide.
     *
     * @return array{
     *   opening: float,
     *   payments_net: float,
     *   expenses: float,
     *   transfers_net: float,
     *   adjustments_net: float,
     *   po_unlinked_out: float,
     *   balance: float,
     *   payments_out: float,
     *   payments_in: float,
     *   account_name: string
     * }
     */
    public static function computeFromLedgerAsOf(
        Database $db,
        int $accountId,
        string $asOfDate = '',
        ?int $warehouseId = null
    ): array {
        $acc = $db->fetchOne('SELECT opening_balance, name FROM accounts WHERE id = ?', [$accountId]);
        if (!$acc) {
            throw new Exception('Account not found.');
        }

        $opening = (float) ($acc['opening_balance'] ?? 0);

        [$payWhSql, $payWhParams] = self::warehouseSqlAndParams($warehouseId);
        [$payDateSql, $payDateParams] = self::dateLimitSql($asOfDate);
        $payParams = array_merge([$accountId], $payWhParams, $payDateParams);

        $payRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN p.payment_type='in' THEN p.amount ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN p.payment_type='out' THEN p.amount ELSE 0 END), 0) AS total_out,
                COALESCE(SUM(CASE WHEN p.payment_type='out' THEN -p.amount ELSE p.amount END), 0) AS net
             FROM payments p
             WHERE p.account_id = ?
               AND " . self::sqlPaymentLedgerWhere('p') . "
               {$payWhSql}{$payDateSql}",
            $payParams
        );
        $paymentsIn  = (float) ($payRow['total_in'] ?? 0);
        $paymentsOut = (float) ($payRow['total_out'] ?? 0);
        $paymentsNet = (float) ($payRow['net'] ?? 0);

        [$expWhSql, $expWhParams] = self::warehouseSqlAndParams($warehouseId);
        [$expDateSql, $expDateParams] = self::dateLimitSql($asOfDate);
        $expRow = $db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM expenses
             WHERE account_id = ?{$expWhSql}{$expDateSql}",
            array_merge([$accountId], $expWhParams, $expDateParams)
        );
        $expensesTotal = (float) ($expRow['total'] ?? 0);

        [$trDateSql, $trDateParams] = self::dateLimitSql($asOfDate);
        $trRow = $db->fetchOne(
            "SELECT
                COALESCE((SELECT SUM(amount) FROM account_transfers WHERE to_account_id = ?{$trDateSql}), 0) AS in_total,
                COALESCE((SELECT SUM(amount) FROM account_transfers WHERE from_account_id = ?{$trDateSql}), 0) AS out_total",
            array_merge([$accountId], $trDateParams, [$accountId], $trDateParams)
        );
        $transferNet = (float) ($trRow['in_total'] ?? 0) - (float) ($trRow['out_total'] ?? 0);

        [$adjDateSql, $adjDateParams] = self::dateLimitSql($asOfDate);
        $adjRow = $db->fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN direction = 'add' THEN amount WHEN direction = 'subtract' THEN -amount END), 0) AS net
             FROM account_balance_adjustments
             WHERE account_id = ?{$adjDateSql}",
            array_merge([$accountId], $adjDateParams)
        );
        $adjustmentsNet = (float) ($adjRow['net'] ?? 0);

        $poUnlinkedOut = self::sumUnlinkedPoPaidKwd($db, $accountId, $asOfDate, $warehouseId);

        $balance = round(
            $opening + $paymentsNet - $expensesTotal + $transferNet + $adjustmentsNet - $poUnlinkedOut,
            3
        );

        return [
            'opening'         => $opening,
            'payments_net'    => $paymentsNet,
            'payments_in'     => $paymentsIn,
            'payments_out'    => $paymentsOut,
            'expenses'        => $expensesTotal,
            'transfers_net'   => $transferNet,
            'adjustments_net' => $adjustmentsNet,
            'po_unlinked_out' => $poUnlinkedOut,
            'balance'         => $balance,
            'account_name'    => (string) ($acc['name'] ?? ''),
        ];
    }

    /**
     * Reconciliation rows for all active accounts (same formula as Recalculate).
     *
     * @return list<array<string, mixed>>
     */
    public static function reconciliationRows(Database $db, string $asOfDate, ?int $warehouseId = null): array {
        $accounts = $db->fetchAll(
            'SELECT id, name, type, opening_balance, current_balance FROM accounts WHERE is_active = 1 ORDER BY name'
        );
        $results = [];
        foreach ($accounts as $acc) {
            $id     = (int) $acc['id'];
            $ledger = self::computeFromLedgerAsOf($db, $id, $asOfDate, $warehouseId);
            $recorded = (float) ($acc['current_balance'] ?? 0);
            $calculated = $ledger['balance'];
            $difference = round($calculated - $recorded, 3);

            $results[] = [
                'account'             => (string) $acc['name'],
                'type'                => (string) ($acc['type'] ?? ''),
                'opening'             => $ledger['opening'],
                'total_in'            => $ledger['payments_in'],
                'total_out'           => $ledger['payments_out'],
                'total_expenses'      => $ledger['expenses'],
                'net_transfers'       => $ledger['transfers_net'],
                'net_adjustments'     => $ledger['adjustments_net'],
                'po_unlinked_payouts' => $ledger['po_unlinked_out'],
                'calculated'          => $calculated,
                'recorded'            => $recorded,
                'difference'          => $difference,
                'status'              => abs($difference) < 0.001 ? 'ok' : 'mismatch',
            ];
        }

        return $results;
    }

    /**
     * Cancel duplicate/stale active payments that inflate recalc (safe before recalculate).
     *
     * @return string[] Payment numbers voided
     */
    public static function cleanupLedgerBeforeRecalc(Database $db, int $accountId): array {
        $voided = [];

        $dupPoPay = $db->fetchAll(
            "SELECT p1.id, p1.payment_no
             FROM purchase_orders po
             JOIN payments p1 ON p1.ref_type = 'purchase_order' AND p1.ref_id = po.id
                AND p1.status = 'active' AND p1.account_id = ?
             JOIN payments p2 ON p2.ref_type = 'purchase' AND p2.ref_id = po.converted_to
                AND p2.status = 'active'
             WHERE po.status = 'converted' AND po.converted_to IS NOT NULL",
            [$accountId]
        );
        foreach ($dupPoPay as $row) {
            $db->execute('UPDATE payments SET status = ? WHERE id = ?', ['cancelled', (int) $row['id']]);
            $voided[] = (string) $row['payment_no'] . ' (duplicate PO payment on converted order)';
        }

        $blankRefDupes = $db->fetchAll(
            "SELECT p.id, p.payment_no
             FROM payments p
             WHERE p.account_id = ?
               AND p.status = 'active'
               AND p.payment_type = 'out'
               AND (p.ref_type IS NULL OR p.ref_type = '')
               AND EXISTS (
                   SELECT 1 FROM payments p2
                   WHERE p2.account_id = p.account_id
                     AND p2.party_id = p.party_id
                     AND p2.status = 'active'
                     AND p2.payment_type = 'out'
                     AND p2.id != p.id
                     AND p2.ref_type IS NOT NULL
                     AND p2.ref_type != ''
                     AND p2.ref_type != 'discount'
                     AND ABS(p2.amount - p.amount) < 0.001
                     AND p2.date BETWEEN DATE_SUB(p.date, INTERVAL 14 DAY) AND DATE_ADD(p.date, INTERVAL 14 DAY)
               )",
            [$accountId]
        );
        foreach ($blankRefDupes as $row) {
            $db->execute('UPDATE payments SET status = ? WHERE id = ?', ['cancelled', (int) $row['id']]);
            $voided[] = (string) $row['payment_no'] . ' (blank ref_type duplicate advance)';
        }

        $stalePurchase = $db->fetchAll(
            "SELECT p.id, p.payment_no
             FROM payments p
             INNER JOIN purchases pur ON pur.id = p.ref_id AND pur.status = 'cancelled'
             WHERE p.account_id = ? AND p.ref_type = 'purchase' AND p.status = 'active'",
            [$accountId]
        );
        foreach ($stalePurchase as $row) {
            $db->execute('UPDATE payments SET status = ? WHERE id = ?', ['cancelled', (int) $row['id']]);
            $voided[] = (string) $row['payment_no'] . ' (payment on cancelled purchase)';
        }

        $stalePoRef = $db->fetchAll(
            "SELECT p.id, p.payment_no
             FROM payments p
             INNER JOIN purchase_orders po ON po.id = p.ref_id AND po.status = 'cancelled'
             WHERE p.account_id = ? AND p.ref_type = 'purchase_order' AND p.status = 'active'",
            [$accountId]
        );
        foreach ($stalePoRef as $row) {
            $db->execute('UPDATE payments SET status = ? WHERE id = ?', ['cancelled', (int) $row['id']]);
            $voided[] = (string) $row['payment_no'] . ' (payment on cancelled PO)';
        }

        return $voided;
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildAuditReport(Database $db, int $accountId): array {
        $ledger = self::computeFromLedger($db, $accountId);

        $dupes = $db->fetchAll(
            "SELECT po.po_no, po.status, p1.payment_no AS po_payment, p2.payment_no AS purchase_payment,
                    p1.amount AS amount
             FROM purchase_orders po
             JOIN payments p1 ON p1.ref_type = 'purchase_order' AND p1.ref_id = po.id
                AND p1.status = 'active' AND p1.account_id = ?
             JOIN payments p2 ON p2.ref_type = 'purchase' AND p2.ref_id = po.converted_to
                AND p2.status = 'active'
             WHERE po.converted_to IS NOT NULL
             ORDER BY po.date DESC
             LIMIT 50",
            [$accountId]
        );

        $blankDupes = $db->fetchAll(
            "SELECT p.payment_no, p.date, p.amount, pa.name AS party_name
             FROM payments p
             LEFT JOIN parties pa ON pa.id = p.party_id
             WHERE p.account_id = ?
               AND p.status = 'active'
               AND p.payment_type = 'out'
               AND (p.ref_type IS NULL OR p.ref_type = '')
               AND EXISTS (
                   SELECT 1 FROM payments p2
                   WHERE p2.account_id = p.account_id
                     AND p2.party_id = p.party_id
                     AND p2.status = 'active'
                     AND p2.payment_type = 'out'
                     AND p2.id != p.id
                     AND p2.ref_type IS NOT NULL AND p2.ref_type != '' AND p2.ref_type != 'discount'
                     AND ABS(p2.amount - p.amount) < 0.001
                     AND p2.date BETWEEN DATE_SUB(p.date, INTERVAL 14 DAY) AND DATE_ADD(p.date, INTERVAL 14 DAY)
               )
             ORDER BY p.amount DESC
             LIMIT 25",
            [$accountId]
        );

        $unlinkedPos = $db->fetchAll(
            "SELECT po.po_no, po.status, po.paid_kwd, po.converted_to
             FROM purchase_orders po
             WHERE po.account_id = ?
               AND " . self::sqlUnlinkedPoLedgerWhere() . "
             ORDER BY po.paid_kwd DESC
             LIMIT 50",
            [$accountId]
        );

        $topOut = $db->fetchAll(
            "SELECT p.payment_no, p.date, p.amount, p.ref_type, p.status, pa.name AS party_name
             FROM payments p
             LEFT JOIN parties pa ON pa.id = p.party_id
             WHERE p.account_id = ? AND p.payment_type = 'out' AND " . self::sqlPaymentLedgerWhere('p') . "
             ORDER BY p.amount DESC
             LIMIT 15",
            [$accountId]
        );

        $activeOutSum = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS t FROM payments p
             WHERE p.account_id = ? AND p.payment_type = 'out' AND " . self::sqlPaymentLedgerWhere('p'),
            [$accountId]
        )['t'] ?? 0);

        return [
            'ledger'         => $ledger,
            'duplicates'     => $dupes,
            'blank_ref_dupes'=> $blankDupes,
            'unlinked_pos'   => $unlinkedPos,
            'top_out'        => $topOut,
            'active_out_sum' => $activeOutSum,
        ];
    }

    /**
     * PO amounts already represented by an active payment must NOT be subtracted again.
     */
    public static function sumUnlinkedPoPaidKwd(
        Database $db,
        int $accountId,
        string $asOfDate = '',
        ?int $warehouseId = null
    ): float {
        [$poWhSql, $poWhParams] = self::warehouseSqlAndParams($warehouseId, 'po.warehouse_id');
        [$poDateSql, $poDateParams] = self::dateLimitSql($asOfDate, 'po.date');

        $row = $db->fetchOne(
            "SELECT COALESCE(SUM(po.paid_kwd), 0) AS total
             FROM purchase_orders po
             WHERE po.account_id = ?
               AND " . self::sqlUnlinkedPoLedgerWhere() . "
               {$poWhSql}{$poDateSql}",
            array_merge([$accountId], $poWhParams, $poDateParams)
        );

        return (float) ($row['total'] ?? 0);
    }

    public static function sqlUnlinkedPoLedgerWhere(): string {
        return "po.paid_kwd > 0.001
                AND po.status IN ('paid', 'draft')
                AND NOT EXISTS (
                    SELECT 1 FROM payments p
                    WHERE p.status = 'active'
                      AND (
                          (p.ref_type = 'purchase_order' AND p.ref_id = po.id)
                          OR (
                              po.converted_to IS NOT NULL
                              AND p.ref_type = 'purchase'
                              AND p.ref_id = po.converted_to
                          )
                          OR (
                              p.ref_type = 'purchase'
                              AND EXISTS (
                                  SELECT 1 FROM purchases pur
                                  WHERE pur.id = p.ref_id
                                    AND pur.notes LIKE CONCAT('%Converted from PO: ', po.po_no, '%')
                              )
                          )
                      )
                )";
    }
}
