<?php

require_once __DIR__ . '/BaseModel.php';

class Party extends BaseModel {
    protected string $table = 'parties';

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

    // Get all parties of a specific type, scoped to current warehouse
    public function getByType(string $type): array {
        $wid = Auth::warehouseId();

        $where = "WHERE p.is_active = 1";
        $params = [];

        if ($type !== 'all') {
            $where .= " AND (p.type = ? OR p.type = 'both')";
            $params[] = $type;
        }
        if ($wid) {
            $where .= " AND p.warehouse_id = ?";
            $params[] = $wid;
        }

        // Fast: fetch parties first, then compute balances in one pass
        $parties = $this->db->fetchAll("SELECT p.* FROM parties p {$where} ORDER BY p.name ASC", $params);
        if (empty($parties)) return [];

        $ids = array_column($parties, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Single query: all balance components aggregated per party
        $balances = $this->db->fetchAll(
            "SELECT party_id,
                    SUM(sales_total) as sales_total,
                    SUM(sale_payments) as sale_payments,
                    SUM(sale_returns) as sale_returns,
                    SUM(purchase_total) as purchase_total,
                    SUM(purchase_payments) as purchase_payments,
                    SUM(purchase_returns) as purchase_returns
             FROM (
                SELECT party_id, grand_total as sales_total, 0 as sale_payments, 0 as sale_returns, 0 as purchase_total, 0 as purchase_payments, 0 as purchase_returns
                FROM sales WHERE party_id IN ($placeholders) AND status != 'cancelled'
                UNION ALL
                SELECT party_id, 0, CASE WHEN payment_type='in' THEN amount ELSE -amount END, 0, 0, 0, 0
                FROM payments WHERE party_id IN ($placeholders) AND ref_type IN ('sale','discount')
                UNION ALL
                SELECT party_id, 0, 0, grand_total, 0, 0, 0
                FROM returns WHERE party_id IN ($placeholders) AND type = 'sale_return' AND status = 'approved'
                UNION ALL
                SELECT party_id, 0, 0, 0, grand_total, 0, 0
                FROM purchases WHERE party_id IN ($placeholders) AND status != 'cancelled'
                UNION ALL
                SELECT party_id, 0, 0, 0, 0, amount, 0
                FROM payments WHERE party_id IN ($placeholders) AND ref_type = 'purchase'
                UNION ALL
                SELECT party_id, 0, 0, 0, 0, 0, grand_total
                FROM returns WHERE party_id IN ($placeholders) AND type = 'purchase_return' AND status = 'approved'
             ) t GROUP BY party_id",
            array_merge($ids, $ids, $ids, $ids, $ids, $ids)
        );

        $balMap = [];
        foreach ($balances as $b) $balMap[$b['party_id']] = $b;

        foreach ($parties as &$p) {
            $b = $balMap[$p['id']] ?? null;
            $p['balance_due'] = (float)$p['opening_balance']
                + (float)($b['sales_total'] ?? 0)
                - (float)($b['sale_payments'] ?? 0)
                - (float)($b['sale_returns'] ?? 0)
                - (float)($b['purchase_total'] ?? 0)
                + (float)($b['purchase_payments'] ?? 0)
                + (float)($b['purchase_returns'] ?? 0);
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
        $party = $this->db->fetchOne("SELECT * FROM parties WHERE id = ?", [$id]);
        if (!$party) return false;

        $agg = $this->db->fetchOne(
            "SELECT
                SUM(sales_total)       as sales_total,
                SUM(sale_payments)     as sale_payments,
                SUM(sale_returns)      as sale_returns,
                SUM(purchase_total)    as purchase_total,
                SUM(purchase_payments) as purchase_payments,
                SUM(purchase_returns)  as purchase_returns
             FROM (
                SELECT grand_total as sales_total, 0 as sale_payments, 0 as sale_returns, 0 as purchase_total, 0 as purchase_payments, 0 as purchase_returns
                FROM sales WHERE party_id=? AND status!='cancelled'
                UNION ALL
                SELECT 0, CASE WHEN payment_type='in' THEN amount ELSE -amount END, 0, 0, 0, 0
                FROM payments WHERE party_id=? AND ref_type IN ('sale','discount')
                UNION ALL
                SELECT 0, 0, grand_total, 0, 0, 0
                FROM returns WHERE party_id=? AND type='sale_return' AND status='approved'
                UNION ALL
                SELECT 0, 0, 0, grand_total, 0, 0
                FROM purchases WHERE party_id=? AND status!='cancelled'
                UNION ALL
                SELECT 0, 0, 0, 0, amount, 0
                FROM payments WHERE party_id=? AND ref_type='purchase'
                UNION ALL
                SELECT 0, 0, 0, 0, 0, grand_total
                FROM returns WHERE party_id=? AND type='purchase_return' AND status='approved'
             ) t",
            [$id, $id, $id, $id, $id, $id]
        );

        $party['net_balance'] = (float)$party['opening_balance']
            + (float)($agg['sales_total']       ?? 0)
            - (float)($agg['sale_payments']      ?? 0)
            - (float)($agg['sale_returns']       ?? 0)
            - (float)($agg['purchase_total']     ?? 0)
            + (float)($agg['purchase_payments']  ?? 0)
            + (float)($agg['purchase_returns']   ?? 0);

        return $party;
    }

    // Party ledger — ALL transactions in one unified timeline
    // BUG FIX: Date filter params must be replicated for each UNION ALL branch.
    // Previously only one copy of date params was appended, but the SQL has 5 branches
    // each with {$dateFilter} placeholders, causing PDO parameter count mismatch.
    public function getLedger(int $partyId, string $fromDate = '', string $toDate = ''): array {
        $dateFilter = '';
        $dateParams = [];

        if ($fromDate) {
            $dateFilter .= " AND date >= ?";
            $dateParams[] = $fromDate;
        }
        if ($toDate) {
            $dateFilter .= " AND date <= ?";
            $dateParams[] = $toDate;
        }

        // Each UNION ALL branch needs its own copy of partyId + date params
        $params = array_merge(
            [$partyId], $dateParams,
            [$partyId], $dateParams,
            [$partyId], $dateParams,
            [$partyId], $dateParams,
            [$partyId], $dateParams
        );

        return $this->db->fetchAll(
            "SELECT 'sale' as type, invoice_no as ref_no, date, grand_total as debit, 0 as credit, balance, status
             FROM sales WHERE party_id = ? AND status != 'cancelled' {$dateFilter}
             UNION ALL
             SELECT 'purchase', invoice_no, date, 0, grand_total, balance, status
             FROM purchases WHERE party_id = ? AND status != 'cancelled' {$dateFilter}
             UNION ALL
             SELECT 'payment', payment_no, date,
                    CASE WHEN payment_type = 'out' THEN amount ELSE 0 END,
                    CASE WHEN payment_type = 'in' THEN amount ELSE 0 END,
                    0, 'paid'
             FROM payments WHERE party_id = ? {$dateFilter}
             UNION ALL
             SELECT 'return', return_no, date,
                    CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END,
                    CASE WHEN type = 'sale_return' THEN grand_total ELSE 0 END,
                    0, status
             FROM returns WHERE party_id = ? AND status = 'approved' {$dateFilter}
             UNION ALL
             SELECT 'expense', expense_no, date, amount, 0, 0, 'paid'
             FROM expenses WHERE party_id = ? {$dateFilter}
             ORDER BY date ASC, type",
            $params
        );
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
        $token = bin2hex(random_bytes(16)); // 32-char unique token for public statement
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
    // balance only for matched IDs. Avoids scanning full sales/payments/returns tables.
    public function search(string $query, string $type = 'all'): array {
        $like = "%{$query}%";
        $params = [];
        $typeClause = '';

        if ($type !== 'all') {
            $typeClause = "AND (p.type = ? OR p.type = 'both')";
            $params[] = $type;
        }

        $wid = Auth::warehouseId();
        $whClause = '';
        if ($wid) {
            $whClause = "AND p.warehouse_id = ?";
            $params[] = $wid;
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

        // Batch-compute sale-side balance for matched IDs only (same formula as party statement)
        $ids = array_column($parties, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $bals = $this->db->fetchAll(
            "SELECT party_id,
                    SUM(sales_total)   as sales_total,
                    SUM(sale_payments) as sale_payments,
                    SUM(sale_returns)  as sale_returns
             FROM (
                SELECT party_id, grand_total as sales_total, 0 as sale_payments, 0 as sale_returns
                FROM sales WHERE party_id IN ($ph) AND status != 'cancelled'
                UNION ALL
                SELECT party_id, 0, CASE WHEN payment_type='in' THEN amount ELSE -amount END, 0
                FROM payments WHERE party_id IN ($ph) AND ref_type IN ('sale','discount')
                UNION ALL
                SELECT party_id, 0, 0, grand_total
                FROM returns WHERE party_id IN ($ph) AND type='sale_return' AND status='approved'
             ) t GROUP BY party_id",
            array_merge($ids, $ids, $ids)
        );

        $balMap = [];
        foreach ($bals as $b) $balMap[$b['party_id']] = $b;

        foreach ($parties as &$p) {
            $b = $balMap[$p['id']] ?? null;
            $p['balance'] = (float)$p['opening_balance']
                + (float)($b['sales_total']   ?? 0)
                - (float)($b['sale_payments']  ?? 0)
                - (float)($b['sale_returns']   ?? 0);
        }
        unset($p);

        return $parties;
    }
}
