<?php

require_once __DIR__ . '/BaseController.php';

class ReportController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // Check if user can access a specific report (master OR individual permission)
    private function authorizeReport(string $reportKey): void {
        if (!Auth::can('reports', 'view') && !Auth::can($reportKey, 'view')) {
            http_response_code(403);
            include __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    public function index(): void {
        // Allow access if user has master reports OR any individual report permission
        if (!Auth::can('reports', 'view')) {
            $hasAny = false;
            foreach (['rpt_daybook','rpt_sales','rpt_profit','rpt_stock','rpt_payments','rpt_party','rpt_item_sales','rpt_reconciliation','rpt_account_stmt','rpt_expenses','rpt_sales_returns','rpt_supplier_stmt','rpt_balance_sheet','rpt_customer_imei'] as $rk) {
                if (Auth::can($rk, 'view')) { $hasAny = true; break; }
            }
            if (!$hasAny) { Auth::authorize('reports', 'view'); }
        }
        $pageTitle = 'Reports';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Day Book — all transactions for a specific date
    public function daybook(): void {
        $this->authorizeReport('rpt_daybook');

        $date = $this->input('date', date('Y-m-d'), 'get');
        $db   = Database::getInstance();

        // Sales
        $sales = $db->fetchAll(
            "SELECT s.id, s.invoice_no as ref_no, 'Sale' as type, s.date, s.grand_total as amount,
                    p.name as party_name, s.status, s.created_at,
                    u.name as created_by
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.date = ? AND s.status != 'cancelled'
             ORDER BY s.created_at ASC", [$date]
        );

        // Purchases
        $purchases = $db->fetchAll(
            "SELECT pr.id, pr.invoice_no as ref_no, 'Purchase' as type, pr.date, pr.grand_total as amount,
                    p.name as party_name, pr.status, pr.created_at,
                    u.name as created_by
             FROM purchases pr
             JOIN parties p ON p.id = pr.party_id
             LEFT JOIN users u ON u.id = pr.created_by
             WHERE pr.date = ? AND pr.status != 'cancelled'
             ORDER BY pr.created_at ASC", [$date]
        );

        // Payments In (received from customers) — exclude discounts
        $paymentsIn = $db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Payment In' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.payment_type = 'in' AND py.ref_type != 'discount'
             ORDER BY py.created_at ASC", [$date]
        );

        // Discounts given (separate from cash)
        $discountsGiven = $db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Discount' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.ref_type = 'discount'
             ORDER BY py.created_at ASC", [$date]
        );

        // Payments Out (paid to suppliers)
        $paymentsOut = $db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Payment Out' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.payment_type = 'out'
             ORDER BY py.created_at ASC", [$date]
        );

        // Returns
        $returns = $db->fetchAll(
            "SELECT r.id, r.return_no as ref_no, 'Return' as type, r.date, r.grand_total as amount,
                    p.name as party_name, r.status, r.created_at,
                    u.name as created_by
             FROM returns r
             JOIN parties p ON p.id = r.party_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.date = ? AND r.status != 'cancelled'
             ORDER BY r.created_at ASC", [$date]
        );

        // Expenses
        $expenses = $db->fetchAll(
            "SELECT e.id, e.expense_no as ref_no, 'Expense' as type, e.date, e.amount,
                    ec.name as party_name, 'paid' as status, e.created_at,
                    u.name as created_by
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.date = ?
             ORDER BY e.created_at ASC", [$date]
        );

        // Merge and sort by created_at
        $transactions = array_merge($sales, $purchases, $paymentsIn, $paymentsOut, $returns, $expenses, $discountsGiven);
        usort($transactions, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        // Summaries
        $summary = [
            'sales'        => array_sum(array_column($sales, 'amount')),
            'sales_count'  => count($sales),
            'purchases'       => array_sum(array_column($purchases, 'amount')),
            'purchases_count' => count($purchases),
            'payments_in'       => array_sum(array_column($paymentsIn, 'amount')),
            'payments_in_count' => count($paymentsIn),
            'payments_out'       => array_sum(array_column($paymentsOut, 'amount')),
            'payments_out_count' => count($paymentsOut),
            'returns'       => array_sum(array_column($returns, 'amount')),
            'returns_count' => count($returns),
            'expenses'       => array_sum(array_column($expenses, 'amount')),
            'expenses_count' => count($expenses),
            'discounts'       => array_sum(array_column($discountsGiven, 'amount')),
            'discounts_count' => count($discountsGiven),
        ];

        $pageTitle = 'Day Book';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/daybook.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function sales(): void {
        $this->authorizeReport('rpt_sales');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $data = $this->db->fetchAll(
            "SELECT s.id, s.invoice_no, s.date, p.name as party_name,
                    s.grand_total, s.paid_amount, s.balance, s.status,
                    u.name as created_by_name
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled'
             ORDER BY s.date DESC",
            [$fromDate, $toDate]
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) as count,
                    SUM(grand_total) as total,
                    SUM(paid_amount) as paid,
                    SUM(balance) as balance
             FROM sales
             WHERE date BETWEEN ? AND ? AND status != 'cancelled'",
            [$fromDate, $toDate]
        );

        $parties   = $this->db->fetchAll("SELECT id, name FROM parties WHERE type IN ('customer','both') AND is_active = 1 ORDER BY name");
        $pageTitle = 'Sales Report';
        $page      = 'reports';
        $reportType = 'sales';

        ob_start();
        include __DIR__ . '/../views/reports/sales.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function stock(): void {
        $this->authorizeReport('rpt_stock');

        $warehouseId = $this->inputInt('warehouse_id', 0, 'get');
        $params = [];
        $wClause = '';
        if ($warehouseId) {
            $wClause = "AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }

        $data = $this->db->fetchAll(
            "SELECT i.name, i.sku, i.brand, i.model, i.min_stock,
                    i.purchase_price, i.sale_price,
                    COALESCE(SUM(s.quantity),0) as stock,
                    COALESCE(SUM(s.quantity),0) * i.purchase_price as stock_value
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id {$wClause}
             WHERE i.is_active = 1
             GROUP BY i.id
             ORDER BY i.name",
            $params
        );

        $warehouses  = $this->db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1");
        $totalValue  = array_sum(array_column($data, 'stock_value'));
        $pageTitle   = 'Stock Report';
        $page        = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function profit(): void {
        $this->authorizeReport('rpt_profit');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        // Sales revenue
        $salesRev = $this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as revenue FROM sales
             WHERE date BETWEEN ? AND ? AND status != 'cancelled'",
            [$fromDate, $toDate]
        )['revenue'];

        // Cost of goods — uses cost_price locked at sale time (falls back to current purchase_price for pre-migration rows)
        $cogs = $this->db->fetchOne(
            "SELECT COALESCE(SUM(si.quantity * IF(si.cost_price > 0, si.cost_price, i.purchase_price)),0) as cost
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled'",
            [$fromDate, $toDate]
        )['cost'];

        // Expenses
        $expenses = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE date BETWEEN ? AND ?",
            [$fromDate, $toDate]
        )['total'];

        $grossProfit = $salesRev - $cogs;
        $netProfit   = $grossProfit - $expenses;

        // Daily breakdown
        $dailyData = $this->db->fetchAll(
            "SELECT s.date,
                    SUM(s.grand_total) as revenue,
                    SUM(si.quantity * IF(si.cost_price > 0, si.cost_price, i.purchase_price)) as cost
             FROM sales s
             JOIN sale_items si ON si.sale_id = s.id
             JOIN items i ON i.id = si.item_id
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled'
             GROUP BY s.date ORDER BY s.date",
            [$fromDate, $toDate]
        );

        $pageTitle = 'Profit & Loss';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/profit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function party(): void {
        $this->authorizeReport('rpt_party');

        $partyId  = $this->inputInt('party_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        // Show ALL parties (not just customers) since we use unified accounts
        $parties = $this->db->fetchAll(
            "SELECT id, name, type, party_code FROM parties WHERE is_active = 1 ORDER BY name ASC"
        );

        $party        = null;
        $transactions = [];
        $openingBal   = 0;
        $summary      = ['total_invoiced' => 0, 'total_paid' => 0, 'total_returned' => 0,
                          'total_purchases' => 0, 'total_paid_out' => 0, 'total_discounts' => 0, 'balance' => 0];

        if ($partyId) {
            $party = $this->db->fetchOne(
                "SELECT * FROM parties WHERE id = ?", [$partyId]
            );

            if ($party) {
                // ============================================================
                // UNIFIED ACCOUNT: All transaction types in one timeline
                // Debit = balance goes up (they owe us more / we owe them less)
                // Credit = balance goes down (they owe us less / we owe them more)
                // ============================================================

                // Sales — debit (they owe us)
                $sales = $this->db->fetchAll(
                    "SELECT id, 'sale' as txn_type, invoice_no as ref_no,
                            date, grand_total as debit, 0 as credit, notes
                     FROM sales
                     WHERE party_id = ? AND date BETWEEN ? AND ? AND status != 'cancelled'
                     ORDER BY date ASC, id ASC",
                    [$partyId, $fromDate, $toDate]
                );

                // Purchases — credit (we owe them)
                $purchases = $this->db->fetchAll(
                    "SELECT id, 'purchase' as txn_type, invoice_no as ref_no,
                            date, 0 as debit, grand_total as credit, notes
                     FROM purchases
                     WHERE party_id = ? AND date BETWEEN ? AND ? AND status != 'cancelled'
                     ORDER BY date ASC, id ASC",
                    [$partyId, $fromDate, $toDate]
                );

                // ALL payments — one type, debit/credit tells direction
                // payment_in (they pay us) = credit, payment_out (we pay them) = debit
                $payments = $this->db->fetchAll(
                    "SELECT 'payment' as txn_type, payment_no as ref_no, date,
                            CASE WHEN payment_type = 'out' THEN amount ELSE 0 END as debit,
                            CASE WHEN payment_type = 'in'  THEN amount ELSE 0 END as credit,
                            notes
                     FROM payments
                     WHERE party_id = ? AND date BETWEEN ? AND ?
                     ORDER BY date ASC, id ASC",
                    [$partyId, $fromDate, $toDate]
                );

                // ALL returns — one type, debit/credit tells direction
                // sale_return = credit (reduces their debt), purchase_return = debit (reduces our debt)
                $returns = $this->db->fetchAll(
                    "SELECT 'return' as txn_type, return_no as ref_no, date,
                            CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END as debit,
                            CASE WHEN type = 'sale_return'     THEN grand_total ELSE 0 END as credit,
                            reason as notes
                     FROM returns
                     WHERE party_id = ? AND date BETWEEN ? AND ? AND status = 'approved'
                     ORDER BY date ASC, id ASC",
                    [$partyId, $fromDate, $toDate]
                );

                // Customer discounts — credit (reduces what customer owes)
                $discounts = $this->db->fetchAll(
                    "SELECT id, 'discount' as txn_type, discount_no as ref_no, date,
                            0 as debit, amount as credit, reason as notes
                     FROM customer_discounts
                     WHERE party_id = ? AND date BETWEEN ? AND ?
                     ORDER BY date ASC, id ASC",
                    [$partyId, $fromDate, $toDate]
                );

                // Merge and sort
                $transactions = array_merge($sales, $purchases, $payments, $returns, $discounts);
                usort($transactions, function($a, $b) {
                    $d = strcmp($a['date'], $b['date']);
                    return $d !== 0 ? $d : 0;
                });

                // ============================================================
                // Opening balance from all transactions BEFORE the date range
                // ============================================================
                $salesBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM sales
                     WHERE party_id = ? AND date < ? AND status != 'cancelled'",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $purchasesBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM purchases
                     WHERE party_id = ? AND date < ? AND status != 'cancelled'",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $payInBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount),0) as t FROM payments
                     WHERE party_id = ? AND payment_type = 'in' AND date < ?",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $payOutBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount),0) as t FROM payments
                     WHERE party_id = ? AND payment_type = 'out' AND date < ?",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $saleRetBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM returns
                     WHERE party_id = ? AND type = 'sale_return' AND status = 'approved' AND date < ?",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $purRetBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM returns
                     WHERE party_id = ? AND type = 'purchase_return' AND status = 'approved' AND date < ?",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $discountsBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount),0) as t FROM customer_discounts
                     WHERE party_id = ? AND date < ?",
                    [$partyId, $fromDate]
                )['t'] ?? 0);

                $openingBal = (float)($party['opening_balance'] ?? 0)
                    + $salesBefore - $payInBefore - $saleRetBefore - $discountsBefore
                    - $purchasesBefore + $payOutBefore + $purRetBefore;

                // Running balance
                $running = $openingBal;
                foreach ($transactions as &$t) {
                    $running += (float)$t['debit'] - (float)$t['credit'];
                    $t['running_balance'] = $running;
                }
                unset($t);

                // Summary totals
                $saleTxns = array_filter($transactions, function($t) { return $t['txn_type'] === 'sale'; });
                $purTxns  = array_filter($transactions, function($t) { return $t['txn_type'] === 'purchase'; });
                $payTxns  = array_filter($transactions, function($t) { return $t['txn_type'] === 'payment'; });
                $retTxns  = array_filter($transactions, function($t) { return $t['txn_type'] === 'return'; });
                $discTxns = array_filter($transactions, function($t) { return $t['txn_type'] === 'discount'; });

                $summary['total_invoiced']  = array_sum(array_column(array_values($saleTxns), 'debit'));
                $summary['total_purchases'] = array_sum(array_column(array_values($purTxns), 'credit'));
                $summary['total_paid']      = array_sum(array_column(array_values($payTxns), 'credit'))
                                            + array_sum(array_column(array_values($payTxns), 'debit'));
                $summary['total_returned']  = array_sum(array_column(array_values($retTxns), 'credit'))
                                            + array_sum(array_column(array_values($retTxns), 'debit'));
                $summary['total_discounts'] = array_sum(array_column(array_values($discTxns), 'credit'));
                $summary['balance']         = $running;
            }
        }

        $pageTitle = 'Party Statement';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/party_statement.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function partyPrint(): void {
        $this->authorizeReport('rpt_party');

        $partyId  = $this->inputInt('party_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $party = $this->db->fetchOne("SELECT * FROM parties WHERE id = ?", [$partyId]);
        if (!$party) $this->redirect('?page=reports&action=party');

        // Unified: all transaction types
        $sales = $this->db->fetchAll(
            "SELECT 'sale' as txn_type, invoice_no as ref_no, date,
                    grand_total as debit, 0 as credit, notes
             FROM sales WHERE party_id = ? AND date BETWEEN ? AND ? AND status != 'cancelled'
             ORDER BY date ASC, id ASC",
            [$partyId, $fromDate, $toDate]
        );
        $purchases = $this->db->fetchAll(
            "SELECT 'purchase' as txn_type, invoice_no as ref_no, date,
                    0 as debit, grand_total as credit, notes
             FROM purchases WHERE party_id = ? AND date BETWEEN ? AND ? AND status != 'cancelled'
             ORDER BY date ASC, id ASC",
            [$partyId, $fromDate, $toDate]
        );
        $payments = $this->db->fetchAll(
            "SELECT 'payment' as txn_type, payment_no as ref_no, date,
                    CASE WHEN payment_type = 'out' THEN amount ELSE 0 END as debit,
                    CASE WHEN payment_type = 'in'  THEN amount ELSE 0 END as credit,
                    notes
             FROM payments WHERE party_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC, id ASC",
            [$partyId, $fromDate, $toDate]
        );
        $returns = $this->db->fetchAll(
            "SELECT 'return' as txn_type, return_no as ref_no, date,
                    CASE WHEN type = 'purchase_return' THEN grand_total ELSE 0 END as debit,
                    CASE WHEN type = 'sale_return'     THEN grand_total ELSE 0 END as credit,
                    reason as notes
             FROM returns WHERE party_id = ? AND date BETWEEN ? AND ? AND status = 'approved'
             ORDER BY date ASC, id ASC",
            [$partyId, $fromDate, $toDate]
        );
        $discounts = $this->db->fetchAll(
            "SELECT 'discount' as txn_type, discount_no as ref_no, date,
                    0 as debit, amount as credit, reason as notes
             FROM customer_discounts WHERE party_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC, id ASC",
            [$partyId, $fromDate, $toDate]
        );

        $transactions = array_merge($sales, $purchases, $payments, $returns, $discounts);
        usort($transactions, function($a, $b) { return strcmp($a['date'], $b['date']); });

        // Opening balance
        $salesBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as t FROM sales WHERE party_id = ? AND date < ? AND status != 'cancelled'", [$partyId, $fromDate]
        )['t'] ?? 0);
        $purchasesBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as t FROM purchases WHERE party_id = ? AND date < ? AND status != 'cancelled'", [$partyId, $fromDate]
        )['t'] ?? 0);
        $payInBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE party_id = ? AND payment_type = 'in' AND date < ?", [$partyId, $fromDate]
        )['t'] ?? 0);
        $payOutBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE party_id = ? AND payment_type = 'out' AND date < ?", [$partyId, $fromDate]
        )['t'] ?? 0);
        $saleRetBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as t FROM returns WHERE party_id = ? AND type = 'sale_return' AND status = 'approved' AND date < ?", [$partyId, $fromDate]
        )['t'] ?? 0);
        $purRetBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as t FROM returns WHERE party_id = ? AND type = 'purchase_return' AND status = 'approved' AND date < ?", [$partyId, $fromDate]
        )['t'] ?? 0);
        $discountsBefore = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as t FROM customer_discounts WHERE party_id = ? AND date < ?", [$partyId, $fromDate]
        )['t'] ?? 0);

        $openingBal = (float)($party['opening_balance'] ?? 0)
            + $salesBefore - $payInBefore - $saleRetBefore - $discountsBefore
            - $purchasesBefore + $payOutBefore + $purRetBefore;

        $running = $openingBal;
        foreach ($transactions as &$t) {
            $running += (float)$t['debit'] - (float)$t['credit'];
            $t['running_balance'] = $running;
        }
        unset($t);

        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/party_statement_print.php';
    }

    public function itemSales(): void {
        $this->authorizeReport('rpt_item_sales');

        $itemId   = $this->inputInt('item_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        // All items for dropdown
        $items = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name ASC"
        );

        $item         = null;
        $rows         = array();
        $summary      = array('qty' => 0, 'revenue' => 0, 'avg_price' => 0, 'invoices' => 0);
        $partyBreakdown   = array();
        $monthlyBreakdown = array();

        if ($itemId) {
            $item = $this->db->fetchOne(
                "SELECT * FROM items WHERE id = ?", [$itemId]
            );

            if ($item) {
                // All sales of this item line by line
                $rows = $this->db->fetchAll(
                    "SELECT s.id as sale_id, s.invoice_no, s.date, p.name as party_name, p.id as party_id,
                            si.quantity, si.unit_price, si.discount, si.total,
                            s.status, s.warehouse_id, w.name as warehouse_name
                     FROM sale_items si
                     JOIN sales s ON s.id = si.sale_id
                     JOIN parties p ON p.id = s.party_id
                     LEFT JOIN warehouses w ON w.id = s.warehouse_id
                     WHERE si.item_id = ?
                       AND s.date BETWEEN ? AND ?
                       AND s.status != 'cancelled'
                     ORDER BY s.date DESC, s.id DESC",
                    [$itemId, $fromDate, $toDate]
                );

                // Summary totals
                $summary['qty']      = array_sum(array_column($rows, 'quantity'));
                $summary['revenue']  = array_sum(array_column($rows, 'total'));
                $summary['invoices'] = count(array_unique(array_column($rows, 'invoice_no')));
                $summary['avg_price'] = $summary['qty'] > 0
                    ? $summary['revenue'] / $summary['qty'] : 0;

                // Per-party breakdown
                $partyMap = array();
                foreach ($rows as $r) {
                    $pid = $r['party_id'];
                    if (!isset($partyMap[$pid])) {
                        $partyMap[$pid] = array(
                            'name' => $r['party_name'],
                            'qty'  => 0,
                            'total'=> 0,
                            'count'=> 0
                        );
                    }
                    $partyMap[$pid]['qty']   += $r['quantity'];
                    $partyMap[$pid]['total'] += $r['total'];
                    $partyMap[$pid]['count'] += 1;
                }
                usort($partyMap, function($a, $b) {
                    if ($b['total'] == $a['total']) return 0;
                    return ($b['total'] > $a['total']) ? 1 : -1;
                });
                $partyBreakdown = array_values($partyMap);

                // Monthly breakdown
                $monthMap = array();
                foreach ($rows as $r) {
                    $mon = date('Y-m', strtotime($r['date']));
                    if (!isset($monthMap[$mon])) {
                        $monthMap[$mon] = array('month' => $mon, 'qty' => 0, 'total' => 0);
                    }
                    $monthMap[$mon]['qty']   += $r['quantity'];
                    $monthMap[$mon]['total'] += $r['total'];
                }
                ksort($monthMap);
                $monthlyBreakdown = array_values($monthMap);
            }
        }

        $pageTitle = 'Item Sales Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/item_sales.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function itemSalesPrint(): void {
        $this->authorizeReport('rpt_item_sales');

        $itemId   = $this->inputInt('item_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $item = $this->db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
        if (!$item) $this->redirect('?page=reports&action=itemSales');

        $rows = $this->db->fetchAll(
            "SELECT s.invoice_no, s.date, p.name as party_name,
                    si.quantity, si.unit_price, si.discount, si.total, s.status
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN parties p ON p.id = s.party_id
             WHERE si.item_id = ? AND s.date BETWEEN ? AND ? AND s.status != 'cancelled'
             ORDER BY s.date DESC, s.id DESC",
            [$itemId, $fromDate, $toDate]
        );

        $partyMap = array();
        foreach ($rows as $r) {
            $k = $r['party_name'];
            if (!isset($partyMap[$k])) {
                $partyMap[$k] = array('name' => $k, 'qty' => 0, 'total' => 0);
            }
            $partyMap[$k]['qty']   += $r['quantity'];
            $partyMap[$k]['total'] += $r['total'];
        }
        usort($partyMap, function($a, $b) {
            if ($b['total'] == $a['total']) return 0;
            return ($b['total'] > $a['total']) ? 1 : -1;
        });
        $partyBreakdown = array_values($partyMap);

        $summary = array(
            'qty'      => array_sum(array_column($rows, 'quantity')),
            'revenue'  => array_sum(array_column($rows, 'total')),
            'invoices' => count(array_unique(array_column($rows, 'invoice_no')))
        );

        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/item_sales_print.php';
    }

    public function payments(): void {
        $this->authorizeReport('rpt_payments');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $data = $this->db->fetchAll(
            "SELECT py.*, pa.name as party_name, a.name as account_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.date BETWEEN ? AND ?
             ORDER BY py.date DESC",
            [$fromDate, $toDate]
        );

        $totals = $this->db->fetchAll(
            "SELECT payment_method, SUM(amount) as total, COUNT(*) as count
             FROM payments WHERE date BETWEEN ? AND ?
             GROUP BY payment_method",
            [$fromDate, $toDate]
        );

        $pageTitle = 'Payments Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/payments.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function reconciliation(): void {
        $this->authorizeReport('rpt_reconciliation');

        $date = $this->input('date', date('Y-m-d'), 'get');
        $db   = $this->db;

        // All accounts with their recorded current balance
        $accounts = self::getAccounts();

        $results = [];
        foreach ($accounts as $acc) {
            $id = $acc['id'];

            // Sum of all payments IN (sales receipts)
            $in = $db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments
                 WHERE account_id = ? AND payment_type = 'in' AND ref_type != 'discount' AND date <= ?",
                [$id, $date]
            );

            // Sum of all payments OUT (purchase payments + expenses)
            $out = $db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments
                 WHERE account_id = ? AND payment_type = 'out' AND date <= ?",
                [$id, $date]
            );

            // Expenses from this account
            $exp = $db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM expenses
                 WHERE account_id = ? AND date <= ?",
                [$id, $date]
            );

            $openingBalance  = (float) $acc['opening_balance'];
            $totalIn         = (float) $in['total'];
            $totalOut        = (float) $out['total'];
            $totalExpenses   = (float) $exp['total'];
            $calculatedBal   = $openingBalance + $totalIn - $totalOut - $totalExpenses;
            $recordedBal     = (float) $acc['current_balance'];
            $difference      = round($calculatedBal - $recordedBal, 3);

            $results[] = [
                'account'        => $acc['name'],
                'type'           => $acc['type'],
                'opening'        => $openingBalance,
                'total_in'       => $totalIn,
                'total_out'      => $totalOut,
                'total_expenses' => $totalExpenses,
                'calculated'     => $calculatedBal,
                'recorded'       => $recordedBal,
                'difference'     => $difference,
                'status'         => abs($difference) < 0.001 ? 'ok' : 'mismatch',
            ];
        }

        // Today's sales total vs payments received today
        $salesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM sales
             WHERE date = ? AND status != 'cancelled'",
            [$date]
        );
        $paymentsToday = $db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM payments
             WHERE date = ? AND payment_type = 'in' AND ref_type != 'discount'",
            [$date]
        );
        $expensesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date = ?",
            [$date]
        );
        $purchasesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM purchases
             WHERE date = ? AND status != 'cancelled'",
            [$date]
        );

        $summary = [
            'sales_collected'    => (float) $salesToday['total'],
            'payments_received'  => (float) $paymentsToday['total'],
            'expenses_paid'      => (float) $expensesToday['total'],
            'purchases_paid'     => (float) $purchasesToday['total'],
        ];

        $pageTitle = 'Balance Reconciliation';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/reconciliation.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function accountStatement(): void {
        $this->authorizeReport('rpt_account_stmt');

        $db       = Database::getInstance();
        $accounts = self::getAccounts();

        $accountId = $this->inputInt('account_id', 0, 'get');
        $fromDate  = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate    = $this->input('to_date', date('Y-m-d'), 'get');

        $account     = null;
        $transactions = [];
        $openingBalance = 0;
        $closingBalance = 0;

        if ($accountId) {
            $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);

            // Opening balance = current_balance minus all transactions after fromDate
            // We calculate it by summing all credits and debits before fromDate

            // Payments IN (money received into this account)
            $paymentsIn = $db->fetchAll(
                "SELECT p.date, p.payment_no as ref, COALESCE(pa.name,'—') as party_name,
                        p.amount as credit, 0 as debit,
                        'Payment In' as type, p.notes as notes
                 FROM payments p
                 LEFT JOIN parties pa ON pa.id = p.party_id
                 WHERE p.account_id = ? AND p.payment_type = 'in' AND p.ref_type != 'discount' AND p.date BETWEEN ? AND ?
                 ORDER BY p.date, p.id",
                [$accountId, $fromDate, $toDate]
            );

            // Payments OUT (money paid out from this account)
            $paymentsOut = $db->fetchAll(
                "SELECT p.date, p.payment_no as ref, COALESCE(pa.name,'—') as party_name,
                        0 as credit, p.amount as debit,
                        'Payment Out' as type, p.notes as notes
                 FROM payments p
                 LEFT JOIN parties pa ON pa.id = p.party_id
                 WHERE p.account_id = ? AND p.payment_type = 'out' AND p.date BETWEEN ? AND ?
                 ORDER BY p.date, p.id",
                [$accountId, $fromDate, $toDate]
            );

            // Expenses from this account
            $expenses = $db->fetchAll(
                "SELECT e.date, e.expense_no as ref, COALESCE(ec.name,'—') as party_name,
                        0 as credit, e.amount as debit,
                        'Expense' as type, e.description as notes
                 FROM expenses e
                 LEFT JOIN expense_categories ec ON ec.id = e.category_id
                 WHERE e.account_id = ? AND e.date BETWEEN ? AND ?
                 ORDER BY e.date, e.id",
                [$accountId, $fromDate, $toDate]
            );

            // Transfers INTO this account
            $transfersIn = $db->fetchAll(
                "SELECT t.date, t.transfer_no as ref, fa.name as party_name,
                        t.amount as credit, 0 as debit,
                        'Transfer In' as type, t.notes as notes
                 FROM account_transfers t
                 JOIN accounts fa ON fa.id = t.from_account_id
                 WHERE t.to_account_id = ? AND t.date BETWEEN ? AND ?
                 ORDER BY t.date, t.id",
                [$accountId, $fromDate, $toDate]
            );

            // Transfers OUT from this account
            $transfersOut = $db->fetchAll(
                "SELECT t.date, t.transfer_no as ref, ta.name as party_name,
                        0 as credit, t.amount as debit,
                        'Transfer Out' as type, t.notes as notes
                 FROM account_transfers t
                 JOIN accounts ta ON ta.id = t.to_account_id
                 WHERE t.from_account_id = ? AND t.date BETWEEN ? AND ?
                 ORDER BY t.date, t.id",
                [$accountId, $fromDate, $toDate]
            );

            // Merge and sort all transactions by date
            $transactions = array_merge($paymentsIn, $paymentsOut, $expenses, $transfersIn, $transfersOut);
            usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));

            // Calculate opening balance (sum of all transactions before fromDate)
            $beforeCredits = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as t FROM payments
                 WHERE account_id = ? AND payment_type = 'in' AND ref_type != 'discount' AND date < ?", [$accountId, $fromDate]
            )['t'] ?? 0);
            $beforeDebits = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as t FROM payments
                 WHERE account_id = ? AND payment_type = 'out' AND date < ?", [$accountId, $fromDate]
            )['t'] ?? 0);
            $beforeExpenses = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as t FROM expenses
                 WHERE account_id = ? AND date < ?", [$accountId, $fromDate]
            )['t'] ?? 0);
            $beforeTransIn = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as t FROM account_transfers
                 WHERE to_account_id = ? AND date < ?", [$accountId, $fromDate]
            )['t'] ?? 0);
            $beforeTransOut = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as t FROM account_transfers
                 WHERE from_account_id = ? AND date < ?", [$accountId, $fromDate]
            )['t'] ?? 0);

            $openingBalance = (float)($account['opening_balance'] ?? 0)
                + $beforeCredits - $beforeDebits - $beforeExpenses + $beforeTransIn - $beforeTransOut;

            // Add running balance to each row
            $running = $openingBalance;
            foreach ($transactions as &$tx) {
                $running += (float)$tx['credit'] - (float)$tx['debit'];
                $tx['running'] = $running;
            }
            unset($tx);

            $closingBalance = $running ?: $openingBalance;
        }

        $pageTitle = 'Account Statement';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/account_statement.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function expenses(): void {
        $this->authorizeReport('rpt_expenses');

        $db         = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM expense_categories ORDER BY name");
        $accounts   = self::getAccounts();

        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');
        $categoryId = $this->inputInt('category_id', 0, 'get');
        $accountId  = $this->inputInt('account_id', 0, 'get');
        $search     = $this->input('search', '', 'get');

        $where  = "WHERE e.date BETWEEN ? AND ?";
        $params = [$fromDate, $toDate];

        if ($categoryId) { $where .= " AND e.category_id = ?"; $params[] = $categoryId; }
        if ($accountId)  { $where .= " AND e.account_id = ?";  $params[] = $accountId; }
        if ($search)     {
            $where .= " AND (e.expense_no LIKE ? OR e.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $expenses = $db->fetchAll(
            "SELECT e.*, ec.name as category_name, a.name as account_name, u.name as created_by_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN accounts a ON a.id = e.account_id
             LEFT JOIN users u ON u.id = e.created_by
             $where
             ORDER BY e.date DESC, e.id DESC",
            $params
        );

        $totalAmount = array_sum(array_column($expenses, 'amount'));

        // Summary by category
        $catSummary = $db->fetchAll(
            "SELECT ec.name as category, COUNT(*) as count, SUM(e.amount) as total
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             $where
             GROUP BY e.category_id ORDER BY total DESC",
            $params
        );

        // Summary by account
        $accSummary = $db->fetchAll(
            "SELECT a.name as account, COUNT(*) as count, SUM(e.amount) as total
             FROM expenses e
             LEFT JOIN accounts a ON a.id = e.account_id
             $where
             GROUP BY e.account_id ORDER BY total DESC",
            $params
        );

        $pageTitle = 'Expenses Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/expenses.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function salesReturns(): void {
        $this->authorizeReport('rpt_sales_returns');

        $db      = Database::getInstance();
        $parties = $db->fetchAll("SELECT id, name FROM parties WHERE type IN ('customer','both') ORDER BY name");

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $search   = $this->input('search', '', 'get');

        $where  = "WHERE r.type = 'sale_return' AND r.date BETWEEN ? AND ?";
        $params = [$fromDate, $toDate];

        if ($partyId) { $where .= " AND r.party_id = ?"; $params[] = $partyId; }
        if ($search)  {
            $where .= " AND (r.return_no LIKE ? OR p.name LIKE ? OR s.invoice_no LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $returns = $db->fetchAll(
            "SELECT r.*, p.name as party_name, s.invoice_no as original_invoice,
                    u.name as created_by_name
             FROM returns r
             JOIN parties p ON p.id = r.party_id
             LEFT JOIN sales s ON s.id = r.ref_id
             LEFT JOIN users u ON u.id = r.created_by
             $where
             ORDER BY r.date DESC, r.id DESC",
            $params
        );

        $totalAmount  = array_sum(array_column($returns, 'grand_total'));
        $totalQty     = 0;

        // Get item details for all returns
        if (!empty($returns)) {
            $idsArr       = array_column($returns, 'id');
            $placeholders = implode(',', array_fill(0, count($idsArr), '?'));
            $returnItems  = $db->fetchAll(
                "SELECT ri.return_id, i.name as item_name, ri.quantity, ri.unit_price, ri.total
                 FROM return_items ri
                 JOIN items i ON i.id = ri.item_id
                 WHERE ri.return_id IN ($placeholders)",
                $idsArr
            );
            $totalQty = array_sum(array_column($returnItems, 'quantity'));

            // Group items by return_id
            $itemsByReturn = [];
            foreach ($returnItems as $ri) {
                $itemsByReturn[$ri['return_id']][] = $ri;
            }
        } else {
            $itemsByReturn = [];
        }

        // Summary by customer
        $custSummary = $db->fetchAll(
            "SELECT p.name as party_name, COUNT(*) as count, SUM(r.grand_total) as total
             FROM returns r
             JOIN parties p ON p.id = r.party_id
             $where
             GROUP BY r.party_id ORDER BY total DESC LIMIT 8",
            $params
        );

        $pageTitle = 'Sales Returns Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/sales_returns.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function supplierStatement(): void {
        $this->authorizeReport('rpt_supplier_stmt');

        $suppliers = $this->db->fetchAll(
            "SELECT id, name FROM parties WHERE type IN ('supplier','both') AND is_active = 1 ORDER BY name"
        );

        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');

        $supplier     = null;
        $transactions = [];
        $openingBal   = 0;
        $summary      = ['total_purchases' => 0, 'total_paid' => 0, 'balance' => 0];

        if ($supplierId) {
            $supplier = $this->db->fetchOne("SELECT * FROM parties WHERE id = ?", [$supplierId]);

            if ($supplier) {

                // Purchase invoices in period
                $purchases = $this->db->fetchAll(
                    "SELECT 'purchase' as txn_type, invoice_no as ref_no, id,
                            date, grand_total as amount, paid_amount, balance, status, notes
                     FROM purchases
                     WHERE party_id = ? AND date BETWEEN ? AND ? AND status != 'cancelled'
                     ORDER BY date ASC, id ASC",
                    [$supplierId, $fromDate, $toDate]
                );

                // Payments OUT to supplier in period
                $payments = $this->db->fetchAll(
                    "SELECT 'payment' as txn_type, payment_no as ref_no, id,
                            date, amount, 0 as paid_amount, 0 as balance, payment_type as status, notes
                     FROM payments
                     WHERE party_id = ? AND payment_type = 'out' AND date BETWEEN ? AND ?
                     ORDER BY date ASC, id ASC",
                    [$supplierId, $fromDate, $toDate]
                );

                // Purchase returns in period
                $returns = $this->db->fetchAll(
                    "SELECT 'return' as txn_type, return_no as ref_no, id,
                            date, grand_total as amount, 0 as paid_amount, 0 as balance, status, notes
                     FROM returns
                     WHERE party_id = ? AND type = 'purchase_return' AND status = 'approved' AND date BETWEEN ? AND ?
                     ORDER BY date ASC, id ASC",
                    [$supplierId, $fromDate, $toDate]
                );

                // Merge and sort
                $transactions = array_merge($purchases, $payments, $returns);
                usort($transactions, fn($a, $b) => strcmp($a['date'], $b['date']));

                // Opening balance = total purchases before fromDate minus total payments before fromDate
                $purBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM purchases
                     WHERE party_id = ? AND date < ? AND status != 'cancelled'",
                    [$supplierId, $fromDate]
                )['t'] ?? 0);
                $payBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount),0) as t FROM payments
                     WHERE party_id = ? AND payment_type = 'out' AND date < ?",
                    [$supplierId, $fromDate]
                )['t'] ?? 0);
                $retBefore = (float)($this->db->fetchOne(
                    "SELECT COALESCE(SUM(grand_total),0) as t FROM returns
                     WHERE party_id = ? AND type = 'purchase_return' AND status = 'approved' AND date < ?",
                    [$supplierId, $fromDate]
                )['t'] ?? 0);
                $openingBal = -1 * (float)($supplier['opening_balance'] ?? 0) + $purBefore - $payBefore - $retBefore;

                // Running balance
                $running = $openingBal;
                foreach ($transactions as &$tx) {
                    if ($tx['txn_type'] === 'purchase') {
                        $running += (float)$tx['amount'];  // we owe more
                    } elseif ($tx['txn_type'] === 'return') {
                        $running -= (float)$tx['amount'];  // return reduces what we owe
                    } else {
                        $running -= (float)$tx['amount'];  // we paid
                    }
                    $tx['running'] = $running;
                }
                unset($tx);

                $purTxns = array_filter($transactions, fn($t) => $t['txn_type'] === 'purchase');
                $payTxns = array_filter($transactions, fn($t) => $t['txn_type'] === 'payment');

                $summary['total_purchases'] = array_sum(array_column(array_values($purTxns), 'amount'));
                $summary['total_paid']      = array_sum(array_column(array_values($payTxns), 'amount'));
                $summary['balance']         = $running;
            }
        }

        $pageTitle = 'Supplier Statement';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/supplier_statement.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function balanceSheet(): void {
        $this->authorizeReport('rpt_balance_sheet');

        $db   = Database::getInstance();
        $date = $this->input('as_of', date('Y-m-d'), 'get');

        // ── ASSETS ──

        // 1. Cash & Bank accounts
        $accounts = self::getAccounts();
        $totalCash = 0;
        foreach ($accounts as $a) $totalCash += (float)$a['current_balance'];

        // 2. All parties with net balance (unified formula)
        $allParties = $db->fetchAll(
            "SELECT p.id, p.name, p.party_code, p.type,
                    p.opening_balance
                    + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled' AND date <= ?), 0)
                    - COALESCE((SELECT SUM(CASE WHEN payment_type='in' THEN amount ELSE -amount END) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount') AND date <= ?), 0)
                    - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved' AND date <= ?), 0)
                    - COALESCE((SELECT SUM(grand_total) FROM purchases WHERE party_id = p.id AND status != 'cancelled' AND date <= ?), 0)
                    + COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type = 'purchase' AND date <= ?), 0)
                    + COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'purchase_return' AND status = 'approved' AND date <= ?), 0)
                    as balance
             FROM parties p WHERE p.is_active = 1
             ORDER BY p.name",
            [$date, $date, $date, $date, $date, $date]
        );

        // Split into receivables (positive = they owe us) and payables (negative = we owe them)
        $receivables = [];
        $payables = [];
        $totalReceivable = 0;
        $totalPayable = 0;

        foreach ($allParties as $p) {
            $bal = (float)$p['balance'];
            if ($bal > 0.001) {
                $receivables[] = $p;
                $totalReceivable += $bal;
            } elseif ($bal < -0.001) {
                $p['balance'] = abs($bal);
                $payables[] = $p;
                $totalPayable += abs($bal);
            }
        }

        // Sort by balance descending
        usort($receivables, fn($a, $b) => (float)$b['balance'] <=> (float)$a['balance']);
        usort($payables, fn($a, $b) => (float)$b['balance'] <=> (float)$a['balance']);

        // 3. Stock valuation
        $stockVal = (float)($db->fetchOne(
            "SELECT COALESCE(SUM(s.quantity * i.purchase_price), 0) as val
             FROM stock s JOIN items i ON i.id = s.item_id WHERE s.quantity > 0"
        )['val'] ?? 0);

        $totalAssets = $totalCash + $totalReceivable + $stockVal;
        $totalLiabilities = $totalPayable;
        $netWorth = $totalAssets - $totalLiabilities;

        $pageTitle = 'Balance Sheet';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/balance_sheet.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Customer IMEI Report — list of IMEIs sold to a customer with item, invoice, date
    public function customerImei(): void {
        $this->authorizeReport('rpt_customer_imei');

        $db       = Database::getInstance();
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $fromDate = $this->input('from_date', '', 'get');
        $toDate   = $this->input('to_date', '', 'get');

        // Get customers for dropdown
        $customers = $db->fetchAll(
            "SELECT id, name, phone, party_code FROM parties
             WHERE is_active = 1 AND (type = 'customer' OR type = 'both')
             ORDER BY name ASC"
        );

        $records = [];
        if ($partyId) {
            $where  = "WHERE s.party_id = ? AND s.status != 'cancelled' AND ir.id IS NOT NULL";
            $params = [$partyId];

            if ($fromDate) {
                $where .= " AND s.date >= ?";
                $params[] = $fromDate;
            }
            if ($toDate) {
                $where .= " AND s.date <= ?";
                $params[] = $toDate;
            }

            $records = $db->fetchAll(
                "SELECT ir.imei, ir.imei2, i.name as item_name, i.brand, i.model,
                        s.invoice_no, s.date, s.id as sale_id,
                        p.name as party_name, p.phone as party_phone, p.party_code
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN sales s ON s.id = si.sale_id
                 JOIN imei_records ir ON ir.id = sii.imei_id
                 JOIN items i ON i.id = si.item_id
                 JOIN parties p ON p.id = s.party_id
                 {$where}
                 ORDER BY s.date DESC, s.id DESC, i.name ASC",
                $params
            );
        }

        $pageTitle = 'Customer IMEI Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/customer_imei.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

}
