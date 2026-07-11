<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/NetWorthService.php';
require_once __DIR__ . '/../services/AccountBalanceService.php';
require_once __DIR__ . '/../models/Party.php';

class ReportController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * @param list<array<string,mixed>> $transactions
     * @return array{
     *   reportError: string|null,
     *   transactions: list<array<string,mixed>>,
     *   transactionsAll: list<array<string,mixed>>,
     *   listTruncated: bool,
     *   listLimit: int,
     *   ledgerTotalCount: int
     * }
     */
    private function finalizeReportLedger(array $transactions, string $fromDate, string $toDate): array {
        $reportError = ListPage::validateReportDateRange($fromDate, $toDate);
        if ($reportError !== null) {
            return [
                'reportError'      => $reportError,
                'transactions'     => [],
                'transactionsAll'  => [],
                'listTruncated'    => false,
                'listLimit'        => ListPage::REPORT_LEDGER_MAX,
                'ledgerTotalCount' => 0,
            ];
        }

        $prep = ListPage::prepareLedgerDisplay($transactions);
        if ($prep['error'] !== null) {
            return [
                'reportError'      => $prep['error'],
                'transactions'     => [],
                'transactionsAll'  => [],
                'listTruncated'    => false,
                'listLimit'        => $prep['limit'],
                'ledgerTotalCount' => $prep['total_count'],
            ];
        }

        return [
            'reportError'      => null,
            'transactions'     => $prep['display'],
            'transactionsAll'  => $prep['all'],
            'listTruncated'    => $prep['truncated'],
            'listLimit'        => $prep['limit'],
            'ledgerTotalCount' => $prep['total_count'],
        ];
    }

    // Check if user can access a specific report (master OR individual permission)
    private function authorizeReport(string $reportKey): void {
        if (!Auth::can('reports', 'view') && !Auth::can($reportKey, 'view')) {
            http_response_code(403);
            include __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    /** Active session warehouse — all reports are scoped to this branch only. */
    private function reportWarehouseId(): int {
        return (int) Auth::warehouseId();
    }

    public function index(): void {
        // Allow access if user has master reports OR any individual report permission
        if (!Auth::can('reports', 'view')) {
            $hasAny = false;
            foreach (['rpt_daybook','rpt_sales','rpt_profit','rpt_stock','rpt_payments','rpt_party','rpt_item_sales','rpt_customer_purchases','rpt_reconciliation','rpt_account_stmt','rpt_expenses','rpt_sales_returns','rpt_supplier_stmt','rpt_balance_sheet','rpt_customer_imei','rpt_purchase_imei','rpt_purchase_orders','rpt_partner_profit'] as $rk) {
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
        $report = $this->fetchDaybookReport($date);
        $transactions = $report['transactions'];
        $summary      = $report['summary'];

        $pageTitle = 'Day Book';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/daybook.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function daybookPrint(): void {
        $this->authorizeReport('rpt_daybook');

        $date     = $this->input('date', date('Y-m-d'), 'get');
        $report   = $this->fetchDaybookReport($date);
        $transactions = $report['transactions'];
        $summary      = $report['summary'];
        $settings     = self::getSettings();

        include __DIR__ . '/../views/reports/daybook_print.php';
    }

    /** @return array{transactions: list<array<string, mixed>>, summary: array<string, float|int>} */
    private function fetchDaybookReport(string $date): array {
        $whId = $this->reportWarehouseId();

        $sales = $this->db->fetchAll(
            "SELECT s.id, s.invoice_no as ref_no, 'Sale' as type, s.date, s.grand_total as amount,
                    p.name as party_name, s.status, s.created_at,
                    u.name as created_by
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.date = ? AND s.status != 'cancelled' AND s.warehouse_id = ?
             ORDER BY s.created_at ASC",
            [$date, $whId]
        );

        $purchases = $this->db->fetchAll(
            "SELECT pr.id, pr.invoice_no as ref_no, 'Purchase' as type, pr.date, pr.grand_total as amount,
                    p.name as party_name, pr.status, pr.created_at,
                    u.name as created_by
             FROM purchases pr
             JOIN parties p ON p.id = pr.party_id
             LEFT JOIN users u ON u.id = pr.created_by
             WHERE pr.date = ? AND pr.status != 'cancelled' AND pr.warehouse_id = ?
             ORDER BY pr.created_at ASC",
            [$date, $whId]
        );

        $paymentsIn = $this->db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Payment In' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.payment_type = 'in' AND py.ref_type != 'discount'
               AND py.warehouse_id = ? AND py.status = 'active'
             ORDER BY py.created_at ASC",
            [$date, $whId]
        );

        $discountsGiven = $this->db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Discount' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.ref_type = 'discount' AND py.warehouse_id = ? AND py.status = 'active'
             ORDER BY py.created_at ASC",
            [$date, $whId]
        );

        $paymentsOut = $this->db->fetchAll(
            "SELECT py.id, py.payment_no as ref_no, 'Payment Out' as type, py.date, py.amount,
                    p.name as party_name, 'paid' as status, py.created_at,
                    u.name as created_by
             FROM payments py
             JOIN parties p ON p.id = py.party_id
             LEFT JOIN users u ON u.id = py.created_by
             WHERE py.date = ? AND py.payment_type = 'out' AND py.warehouse_id = ? AND py.status = 'active'
             ORDER BY py.created_at ASC",
            [$date, $whId]
        );

        $returns = $this->db->fetchAll(
            "SELECT r.id, r.return_no as ref_no, 'Return' as type, r.date, r.grand_total as amount,
                    p.name as party_name, r.status, r.created_at,
                    u.name as created_by
             FROM returns r
             JOIN parties p ON p.id = r.party_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.date = ? AND r.status != 'cancelled' AND r.warehouse_id = ?
             ORDER BY r.created_at ASC",
            [$date, $whId]
        );

        $expenses = $this->db->fetchAll(
            "SELECT e.id, e.expense_no as ref_no, 'Expense' as type, e.date, e.amount,
                    ec.name as party_name, 'paid' as status, e.created_at,
                    u.name as created_by
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.date = ? AND e.warehouse_id = ?
             ORDER BY e.created_at ASC",
            [$date, $whId]
        );

        $transactions = array_merge($sales, $purchases, $paymentsIn, $paymentsOut, $returns, $expenses, $discountsGiven);
        usort($transactions, function ($a, $b) {
            return strtotime((string) $a['created_at']) <=> strtotime((string) $b['created_at']);
        });

        $summary = [
            'sales'               => (float) array_sum(array_column($sales, 'amount')),
            'sales_count'         => count($sales),
            'purchases'           => (float) array_sum(array_column($purchases, 'amount')),
            'purchases_count'     => count($purchases),
            'payments_in'         => (float) array_sum(array_column($paymentsIn, 'amount')),
            'payments_in_count'   => count($paymentsIn),
            'payments_out'        => (float) array_sum(array_column($paymentsOut, 'amount')),
            'payments_out_count'  => count($paymentsOut),
            'returns'             => (float) array_sum(array_column($returns, 'amount')),
            'returns_count'       => count($returns),
            'expenses'            => (float) array_sum(array_column($expenses, 'amount')),
            'expenses_count'      => count($expenses),
            'discounts'           => (float) array_sum(array_column($discountsGiven, 'amount')),
            'discounts_count'     => count($discountsGiven),
        ];

        return ['transactions' => $transactions, 'summary' => $summary];
    }

    public function sales(): void {
        $this->authorizeReport('rpt_sales');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $report = $this->fetchSalesReport($fromDate, $toDate);
        $data    = $report['rows'];
        $summary = $report['summary'];

        $parties    = (new Party())->listForFilter('customer');
        $pageTitle  = 'Sales Report';
        $page       = 'reports';
        $reportType = 'sales';

        ob_start();
        include __DIR__ . '/../views/reports/sales.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function salesPrint(): void {
        $this->authorizeReport('rpt_sales');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $report   = $this->fetchSalesReport($fromDate, $toDate);
        $data     = $report['rows'];
        $summary  = $report['summary'];
        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/sales_print.php';
    }

    /** @return array{rows: list<array<string, mixed>>, summary: array<string, mixed>} */
    private function fetchSalesReport(string $fromDate, string $toDate): array {
        $whId = $this->reportWarehouseId();

        $rows = $this->db->fetchAll(
            "SELECT s.id, s.invoice_no, s.date, p.name as party_name,
                    s.grand_total, s.paid_amount, s.balance, s.status,
                    u.name as created_by_name
             FROM sales s
             JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled' AND s.warehouse_id = ?
             ORDER BY s.date DESC, s.id DESC",
            [$fromDate, $toDate, $whId]
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) as count,
                    COALESCE(SUM(grand_total), 0) as total,
                    COALESCE(SUM(paid_amount), 0) as paid,
                    COALESCE(SUM(balance), 0) as balance
             FROM sales
             WHERE date BETWEEN ? AND ? AND status != 'cancelled' AND warehouse_id = ?",
            [$fromDate, $toDate, $whId]
        );

        return ['rows' => $rows, 'summary' => $summary ?: []];
    }

    public function stock(): void {
        $this->authorizeReport('rpt_stock');

        $report      = $this->fetchStockReport();
        $data        = $report['rows'];
        $totalValue  = $report['totalValue'];
        $lowCount    = $report['lowCount'];
        $warehouse   = $report['warehouse'];

        $pageTitle  = 'Stock Report';
        $page       = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function stockPrint(): void {
        $this->authorizeReport('rpt_stock');

        $report      = $this->fetchStockReport();
        $data        = $report['rows'];
        $totalValue  = $report['totalValue'];
        $lowCount    = $report['lowCount'];
        $warehouse   = $report['warehouse'];
        $settings    = self::getSettings();

        include __DIR__ . '/../views/reports/stock_print.php';
    }

    /** @return array{rows: list<array<string, mixed>>, totalValue: float, lowCount: int, warehouse: array<string, mixed>} */
    private function fetchStockReport(): array {
        $warehouseId = $this->reportWarehouseId();
        $wClause     = 'AND s.warehouse_id = ?';
        $params      = [$warehouseId];

        $rows = $this->db->fetchAll(
            "SELECT i.name, i.sku, i.brand, i.model, i.min_stock,
                    i.purchase_price, i.sale_price,
                    COALESCE(SUM(s.quantity), 0) as stock,
                    COALESCE(SUM(s.quantity), 0) * i.purchase_price as stock_value
             FROM items i
             LEFT JOIN stock s ON s.item_id = i.id {$wClause}
             WHERE i.is_active = 1
             GROUP BY i.id
             HAVING COALESCE(SUM(s.quantity), 0) > 0
             ORDER BY i.name",
            $params
        );

        $lowCount = 0;
        foreach ($rows as $row) {
            if ((int) $row['stock'] <= (int) $row['min_stock'] && (int) $row['min_stock'] > 0) {
                $lowCount++;
            }
        }

        $warehouse = $this->db->fetchOne(
            'SELECT id, name FROM warehouses WHERE id = ? AND is_active = 1',
            [$warehouseId]
        ) ?: ['id' => $warehouseId, 'name' => Auth::warehouseName()];

        return [
            'rows'       => $rows,
            'totalValue' => (float) array_sum(array_column($rows, 'stock_value')),
            'lowCount'   => $lowCount,
            'warehouse'  => $warehouse,
        ];
    }

    public function profit(): void {
        $this->authorizeReport('rpt_profit');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');
        $whId     = $this->reportWarehouseId();

        // Sales revenue
        $salesRev = $this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) as revenue FROM sales
             WHERE date BETWEEN ? AND ? AND status != 'cancelled' AND warehouse_id = ?",
            [$fromDate, $toDate, $whId]
        )['revenue'];

        // Cost of goods — uses cost_price locked at sale time (falls back to current purchase_price for pre-migration rows)
        $cogs = $this->db->fetchOne(
            "SELECT COALESCE(SUM(si.quantity * IF(si.cost_price > 0, si.cost_price, i.purchase_price)),0) as cost
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled' AND s.warehouse_id = ?",
            [$fromDate, $toDate, $whId]
        )['cost'];

        // Expenses
        $expenses = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE date BETWEEN ? AND ? AND warehouse_id = ?",
            [$fromDate, $toDate, $whId]
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
             WHERE s.date BETWEEN ? AND ? AND s.status != 'cancelled' AND s.warehouse_id = ?
             GROUP BY s.date ORDER BY s.date",
            [$fromDate, $toDate, $whId]
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

        $parties = (new Party())->listForFilter('all');

        $party            = null;
        $transactions     = [];
        $transactionsAll  = [];
        $openingBal       = 0;
        $reportError      = null;
        $listTruncated    = false;
        $listLimit        = ListPage::REPORT_LEDGER_MAX;
        $ledgerTotalCount = 0;
        $summary          = ['total_invoiced' => 0, 'total_paid' => 0, 'total_returned' => 0,
                              'total_purchases' => 0, 'total_paid_out' => 0, 'total_discounts' => 0, 'balance' => 0];

        if ($partyId) {
            $reportError = ListPage::validateReportDateRange($fromDate, $toDate);

            $party = $this->db->fetchOne(
                "SELECT * FROM parties WHERE id = ?", [$partyId]
            );

            if ($party && $reportError === null) {
                $whId       = $this->reportWarehouseId();
                $partyModel = new Party();
                $transactions = $partyModel->getPartyStatementTransactions($partyId, $fromDate, $toDate, $whId);

                // Same rules as Party Master / getPartyStatementTransactions (incl. NULL warehouse rows).
                $openingBal = $partyModel->computeStatementOpeningBalance($partyId, $fromDate, $whId);

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

                $ledgerView = $this->finalizeReportLedger($transactions, $fromDate, $toDate);
                if ($ledgerView['reportError'] !== null) {
                    $reportError = $ledgerView['reportError'];
                    $transactions = [];
                    $transactionsAll = [];
                } else {
                    $transactions     = $ledgerView['transactions'];
                    $transactionsAll  = $ledgerView['transactionsAll'];
                    $listTruncated    = $ledgerView['listTruncated'];
                    $listLimit        = $ledgerView['listLimit'];
                    $ledgerTotalCount = $ledgerView['ledgerTotalCount'];
                }
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

        $whId         = $this->reportWarehouseId();
        $partyModel   = new Party();
        $transactions = $partyModel->getPartyStatementTransactions($partyId, $fromDate, $toDate, $whId);

        $openingBal = $partyModel->computeStatementOpeningBalance($partyId, $fromDate, $whId);

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

        $items = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name ASC"
        );

        $item             = null;
        $rows             = [];
        $summary          = ['qty' => 0, 'revenue' => 0, 'avg_price' => 0, 'invoices' => 0];
        $partyBreakdown   = [];
        $monthlyBreakdown = [];

        if ($itemId) {
            $report = $this->fetchItemSalesReport($itemId, $fromDate, $toDate);
            if ($report !== null) {
                $item             = $report['item'];
                $rows             = $report['rows'];
                $summary          = $report['summary'];
                $partyBreakdown   = $report['partyBreakdown'];
                $monthlyBreakdown = $report['monthlyBreakdown'];
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

        $report = $this->fetchItemSalesReport($itemId, $fromDate, $toDate);
        if ($report === null) {
            $this->redirect('?page=reports&action=itemSales');
        }

        $item             = $report['item'];
        $rows             = $report['rows'];
        $summary          = $report['summary'];
        $partyBreakdown   = $report['partyBreakdown'];
        $monthlyBreakdown = $report['monthlyBreakdown'];
        $settings         = self::getSettings();

        include __DIR__ . '/../views/reports/item_sales_print.php';
    }

    /**
     * @return array{
     *     item: array<string, mixed>,
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, float|int>,
     *     partyBreakdown: list<array<string, mixed>>,
     *     monthlyBreakdown: list<array<string, mixed>>
     * }|null
     */
    private function fetchItemSalesReport(int $itemId, string $fromDate, string $toDate): ?array {
        $item = $this->db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
        if (!$item) {
            return null;
        }

        $whId = $this->reportWarehouseId();

        $rows = $this->db->fetchAll(
            "SELECT s.id as sale_id, s.invoice_no, s.date, p.name as party_name, p.id as party_id,
                    si.quantity, si.unit_price, si.discount, si.total,
                    s.status, w.name as warehouse_name
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN parties p ON p.id = s.party_id
             LEFT JOIN warehouses w ON w.id = s.warehouse_id
             WHERE si.item_id = ? AND s.warehouse_id = ? AND s.date BETWEEN ? AND ? AND s.status != 'cancelled'
             ORDER BY s.date DESC, s.id DESC",
            [$itemId, $whId, $fromDate, $toDate]
        );

        $qty     = (float) array_sum(array_column($rows, 'quantity'));
        $revenue = (float) array_sum(array_column($rows, 'total'));

        $partyMap = [];
        foreach ($rows as $r) {
            $pid = $r['party_id'];
            if (!isset($partyMap[$pid])) {
                $partyMap[$pid] = ['name' => $r['party_name'], 'qty' => 0, 'total' => 0];
            }
            $partyMap[$pid]['qty']   += $r['quantity'];
            $partyMap[$pid]['total'] += $r['total'];
        }
        usort($partyMap, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        $partyBreakdown = array_values($partyMap);

        $monthMap = [];
        foreach ($rows as $r) {
            $mon = date('Y-m', strtotime($r['date']));
            if (!isset($monthMap[$mon])) {
                $monthMap[$mon] = ['month' => $mon, 'qty' => 0, 'total' => 0];
            }
            $monthMap[$mon]['qty']   += $r['quantity'];
            $monthMap[$mon]['total'] += $r['total'];
        }
        ksort($monthMap);
        $monthlyBreakdown = array_values($monthMap);

        return [
            'item'             => $item,
            'rows'             => $rows,
            'summary'          => [
                'qty'       => $qty,
                'revenue'   => $revenue,
                'invoices'  => count(array_unique(array_column($rows, 'invoice_no'))),
                'avg_price' => $qty > 0 ? $revenue / $qty : 0,
            ],
            'partyBreakdown'   => $partyBreakdown,
            'monthlyBreakdown' => $monthlyBreakdown,
        ];
    }

    public function customerPurchases(): void {
        $this->authorizeReport('rpt_customer_purchases');

        $partyId  = $this->inputInt('party_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $customers = (new Party())->listForFilter('customer');

        $party            = null;
        $rows             = [];
        $reportError      = null;
        $listTruncated    = false;
        $listLimit        = ListPage::REPORT_LEDGER_MAX;
        $summary          = ['qty' => 0, 'revenue' => 0, 'avg_price' => 0, 'invoices' => 0, 'items' => 0];
        $itemBreakdown    = [];
        $monthlyBreakdown = [];

        if ($partyId) {
            $whId = $this->reportWarehouseId();
            $party = $this->db->fetchOne(
                "SELECT id, name, phone, party_code, type FROM parties WHERE id = ? AND type IN ('customer','both')",
                [$partyId]
            );

            if ($party) {
                $reportError = ListPage::validateReportDateRange($fromDate, $toDate);

                if ($reportError === null) {
                    $allRows = $this->db->fetchAll(
                        "SELECT s.id as sale_id, s.invoice_no, s.date,
                                i.id as item_id, i.name as item_name, i.sku, i.brand,
                                si.quantity, si.unit_price, si.discount, si.total,
                                w.name as warehouse_name
                         FROM sale_items si
                         JOIN sales s ON s.id = si.sale_id
                         JOIN items i ON i.id = si.item_id
                         LEFT JOIN warehouses w ON w.id = s.warehouse_id
                         WHERE s.party_id = ? AND s.warehouse_id = ?
                           AND s.date BETWEEN ? AND ?
                           AND s.status != 'cancelled'
                         ORDER BY s.date DESC, s.id DESC, i.name ASC",
                        [$partyId, $whId, $fromDate, $toDate]
                    );

                    $capped = ListPage::capRows($allRows, ListPage::REPORT_LEDGER_MAX);
                    $rows           = $capped['items'];
                    $listTruncated  = $capped['truncated'];
                    $listLimit      = $capped['limit'];

                    $summary['qty']      = array_sum(array_column($rows, 'quantity'));
                    $summary['revenue']  = array_sum(array_column($rows, 'total'));
                    $summary['invoices'] = count(array_unique(array_column($rows, 'sale_id')));

                    $itemMap = [];
                    foreach ($rows as $r) {
                        $iid = $r['item_id'];
                        if (!isset($itemMap[$iid])) {
                            $itemMap[$iid] = [
                                'name'  => $r['item_name'],
                                'sku'   => $r['sku'] ?? '',
                                'brand' => $r['brand'] ?? '',
                                'qty'   => 0,
                                'total' => 0,
                                'count' => 0,
                            ];
                        }
                        $itemMap[$iid]['qty']   += $r['quantity'];
                        $itemMap[$iid]['total'] += $r['total'];
                        $itemMap[$iid]['count'] += 1;
                    }
                    $summary['items'] = count($itemMap);
                    $summary['avg_price'] = $summary['qty'] > 0
                        ? $summary['revenue'] / $summary['qty'] : 0;

                    usort($itemMap, function ($a, $b) {
                        if ($b['total'] == $a['total']) {
                            return 0;
                        }
                        return ($b['total'] > $a['total']) ? 1 : -1;
                    });
                    $itemBreakdown = array_values($itemMap);

                    $monthMap = [];
                    foreach ($rows as $r) {
                        $mon = date('Y-m', strtotime($r['date']));
                        if (!isset($monthMap[$mon])) {
                            $monthMap[$mon] = ['month' => $mon, 'qty' => 0, 'total' => 0];
                        }
                        $monthMap[$mon]['qty']   += $r['quantity'];
                        $monthMap[$mon]['total'] += $r['total'];
                    }
                    ksort($monthMap);
                    $monthlyBreakdown = array_values($monthMap);
                }
            }
        }

        $pageTitle = 'Customer Purchases Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/customer_purchases.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function customerPurchasesPrint(): void {
        $this->authorizeReport('rpt_customer_purchases');

        $partyId  = $this->inputInt('party_id', 0, 'get');
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');

        $party = $this->db->fetchOne(
            "SELECT id, name, phone, party_code FROM parties WHERE id = ? AND type IN ('customer','both')",
            [$partyId]
        );
        if (!$party) {
            $this->redirect('?page=reports&action=customerPurchases');
        }

        $reportError = ListPage::validateReportDateRange($fromDate, $toDate);
        if ($reportError !== null) {
            $this->redirect('?page=reports&action=customerPurchases&party_id=' . $partyId);
        }

        $whId = $this->reportWarehouseId();
        $rows = $this->db->fetchAll(
            "SELECT s.invoice_no, s.date, i.name as item_name, i.sku,
                    si.quantity, si.unit_price, si.discount, si.total
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN items i ON i.id = si.item_id
             WHERE s.party_id = ? AND s.warehouse_id = ? AND s.date BETWEEN ? AND ? AND s.status != 'cancelled'
             ORDER BY s.date DESC, s.id DESC, i.name ASC",
            [$partyId, $whId, $fromDate, $toDate]
        );

        $itemMap = [];
        foreach ($rows as $r) {
            $k = $r['item_name'];
            if (!isset($itemMap[$k])) {
                $itemMap[$k] = ['name' => $k, 'sku' => $r['sku'] ?? '', 'qty' => 0, 'total' => 0];
            }
            $itemMap[$k]['qty']   += $r['quantity'];
            $itemMap[$k]['total'] += $r['total'];
        }
        usort($itemMap, function ($a, $b) {
            if ($b['total'] == $a['total']) {
                return 0;
            }
            return ($b['total'] > $a['total']) ? 1 : -1;
        });
        $itemBreakdown = array_values($itemMap);

        $summary = [
            'qty'      => array_sum(array_column($rows, 'quantity')),
            'revenue'  => array_sum(array_column($rows, 'total')),
            'invoices' => count(array_unique(array_column($rows, 'invoice_no'))),
            'items'    => count($itemMap),
        ];

        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/customer_purchases_print.php';
    }

    public function payments(): void {
        $this->authorizeReport('rpt_payments');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');
        $whId     = $this->reportWarehouseId();

        $data = $this->db->fetchAll(
            "SELECT py.*, pa.name as party_name, a.name as account_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             LEFT JOIN accounts a ON a.id = py.account_id
             WHERE py.date BETWEEN ? AND ? AND py.warehouse_id = ? AND py.status = 'active'
             ORDER BY py.date DESC",
            [$fromDate, $toDate, $whId]
        );

        $totals = $this->db->fetchAll(
            "SELECT payment_method, SUM(amount) as total, COUNT(*) as count
             FROM payments WHERE date BETWEEN ? AND ? AND warehouse_id = ? AND status = 'active'
             GROUP BY payment_method",
            [$fromDate, $toDate, $whId]
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
        $whId = $this->reportWarehouseId();

        $results = AccountBalanceService::reconciliationRows($db, $date, $whId > 0 ? $whId : null);

        // Today's sales total vs payments received today
        $salesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM sales
             WHERE date = ? AND warehouse_id = ? AND status != 'cancelled'",
            [$date, $whId]
        );
        $paymentsToday = $db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM payments
             WHERE date = ? AND warehouse_id = ? AND payment_type = 'in' AND ref_type != 'discount' AND status = 'active'",
            [$date, $whId]
        );
        $expensesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date = ? AND warehouse_id = ?",
            [$date, $whId]
        );
        $purchasesToday = $db->fetchOne(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM purchases
             WHERE date = ? AND warehouse_id = ? AND status != 'cancelled'",
            [$date, $whId]
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
        $scopeAll  = $this->input('scope', '', 'get') === 'all';

        $account          = null;
        $transactions     = [];
        $transactionsAll  = [];
        $openingBalance   = 0;
        $closingBalance   = 0;
        $reportError      = null;
        $listTruncated    = false;
        $listLimit        = ListPage::REPORT_LEDGER_MAX;
        $ledgerTotalCount = 0;
        $statementScopeLabel = '';

        if ($accountId) {
            $reportError = ListPage::validateReportDateRange($fromDate, $toDate);
            $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);

            if ($account && $reportError === null) {
            $whId = $this->reportWarehouseId();
            $whForLedger = $scopeAll ? null : ($whId > 0 ? $whId : null);
            $statementScopeLabel = $scopeAll
                ? 'Company-wide (all branches — matches Settings → Recalculate)'
                : trim((string) Auth::warehouseName()) . ' branch + legacy rows with no warehouse';

            [$payWhSql, $payWhParams] = AccountBalanceService::warehouseSqlAndParams($whForLedger);
            [$payRangeSql, $payRangeParams] = AccountBalanceService::dateBetweenSql($fromDate, $toDate);
            $payWhere = AccountBalanceService::sqlPaymentLedgerWhere('p');
            $payBaseParams = array_merge([$accountId], $payWhParams, $payRangeParams);

            $paymentsIn = $db->fetchAll(
                "SELECT p.date, p.payment_no as ref, COALESCE(pa.name,'—') as party_name,
                        p.amount as credit, 0 as debit,
                        'Payment In' as type, p.notes as notes, p.id as sort_id
                 FROM payments p
                 LEFT JOIN parties pa ON pa.id = p.party_id
                 WHERE p.account_id = ? AND p.payment_type = 'in' AND {$payWhere}{$payWhSql}{$payRangeSql}
                 ORDER BY p.date, p.id",
                $payBaseParams
            );

            $paymentsOut = $db->fetchAll(
                "SELECT p.date, p.payment_no as ref, COALESCE(pa.name,'—') as party_name,
                        0 as credit, p.amount as debit,
                        'Payment Out' as type, p.notes as notes, p.id as sort_id
                 FROM payments p
                 LEFT JOIN parties pa ON pa.id = p.party_id
                 WHERE p.account_id = ? AND p.payment_type = 'out' AND {$payWhere}{$payWhSql}{$payRangeSql}
                 ORDER BY p.date, p.id",
                $payBaseParams
            );

            [$expWhSql, $expWhParams] = AccountBalanceService::warehouseSqlAndParams($whForLedger);
            [$expRangeSql, $expRangeParams] = AccountBalanceService::dateBetweenSql($fromDate, $toDate);

            $expenses = $db->fetchAll(
                "SELECT e.date, e.expense_no as ref, COALESCE(ec.name,'—') as party_name,
                        0 as credit, e.amount as debit,
                        'Expense' as type, e.description as notes, e.id as sort_id
                 FROM expenses e
                 LEFT JOIN expense_categories ec ON ec.id = e.category_id
                 WHERE e.account_id = ?{$expWhSql}{$expRangeSql}
                 ORDER BY e.date, e.id",
                array_merge([$accountId], $expWhParams, $expRangeParams)
            );

            // Transfers INTO this account
            $transfersIn = $db->fetchAll(
                "SELECT t.date, t.transfer_no as ref, fa.name as party_name,
                        t.amount as credit, 0 as debit,
                        'Transfer In' as type, t.notes as notes, t.id as sort_id
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
                        'Transfer Out' as type, t.notes as notes, t.id as sort_id
                 FROM account_transfers t
                 JOIN accounts ta ON ta.id = t.to_account_id
                 WHERE t.from_account_id = ? AND t.date BETWEEN ? AND ?
                 ORDER BY t.date, t.id",
                [$accountId, $fromDate, $toDate]
            );

                // Manual adjustments (aligned with Accounts page)
                $adjustmentsStmt = $db->fetchAll(
                    "SELECT aba.date,
                            CONCAT('ADJ-', LPAD(aba.id, 6, '0')) as ref,
                            'Manual adjustment' as party_name,
                            CASE WHEN aba.direction = 'add' THEN aba.amount ELSE 0 END as credit,
                            CASE WHEN aba.direction = 'subtract' THEN aba.amount ELSE 0 END as debit,
                            'Balance Adjustment' as type,
                            CASE WHEN aba.reason IS NOT NULL AND aba.reason != '' THEN aba.reason ELSE NULL END as notes,
                            aba.id as sort_id
                     FROM account_balance_adjustments aba
                     WHERE aba.account_id = ? AND aba.date BETWEEN ? AND ?
                     ORDER BY aba.date, aba.id",
                    [$accountId, $fromDate, $toDate]
                );

                // PO paid amounts without a mirrored payments row (same rules as AccountBalanceService)
                $poLedgerWhere = AccountBalanceService::sqlUnlinkedPoLedgerWhere();
                [$poWhSql, $poWhParams] = AccountBalanceService::warehouseSqlAndParams($whForLedger, 'po.warehouse_id');
                [$poRangeSql, $poRangeParams] = AccountBalanceService::dateBetweenSql($fromDate, $toDate, 'po.date');
                $poStmt = $db->fetchAll(
                    "SELECT po.date, po.po_no as ref, p.name as party_name,
                            0 as credit, po.paid_kwd as debit,
                            'PO Payment (no ledger)' as type,
                            CONCAT(po.currency, ' @ ', po.exchange_rate) as notes,
                            po.id as sort_id
                     FROM purchase_orders po
                     JOIN parties p ON p.id = po.party_id
                     WHERE po.account_id = ? AND {$poLedgerWhere}{$poWhSql}{$poRangeSql}
                     ORDER BY po.date, po.id",
                    array_merge([$accountId], $poWhParams, $poRangeParams)
                );

                // Merge; stable-sort by date + ledger id within each source
                $transactions = array_merge(
                    $paymentsIn,
                    $paymentsOut,
                    $expenses,
                    $transfersIn,
                    $transfersOut,
                    $adjustmentsStmt,
                    $poStmt
                );
                usort($transactions, function ($a, $b) {
                    $da = (string) ($a['date'] ?? '');
                    $dbt = (string) ($b['date'] ?? '');
                    if ($da !== $dbt) {
                        return strcmp($da, $dbt);
                    }
                    return ((int) ($a['sort_id'] ?? 0)) <=> ((int) ($b['sort_id'] ?? 0));
                });

                $dayBefore = date('Y-m-d', strtotime($fromDate . ' -1 day'));
                $openingBalance = AccountBalanceService::computeFromLedgerAsOf(
                    $db,
                    $accountId,
                    $dayBefore,
                    $whForLedger
                )['balance'];
                $closingBalance = AccountBalanceService::computeFromLedgerAsOf(
                    $db,
                    $accountId,
                    $toDate,
                    $whForLedger
                )['balance'];

            // Add running balance to each row
            $running = $openingBalance;
            foreach ($transactions as &$tx) {
                $running += (float)$tx['credit'] - (float)$tx['debit'];
                $tx['running'] = $running;
            }
            unset($tx);

                $ledgerView = $this->finalizeReportLedger($transactions, $fromDate, $toDate);
                if ($ledgerView['reportError'] !== null) {
                    $reportError = $ledgerView['reportError'];
                    $transactions = [];
                    $transactionsAll = [];
                } else {
                    $transactions     = $ledgerView['transactions'];
                    $transactionsAll  = $ledgerView['transactionsAll'];
                    $listTruncated    = $ledgerView['listTruncated'];
                    $listLimit        = $ledgerView['listLimit'];
                    $ledgerTotalCount = $ledgerView['ledgerTotalCount'];
                }
            }
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

        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');
        $categoryId = $this->inputInt('category_id', 0, 'get');
        $accountId  = $this->inputInt('account_id', 0, 'get');
        $search     = $this->input('search', '', 'get');

        $categories = Database::getInstance()->fetchAll("SELECT * FROM expense_categories ORDER BY name");
        $accounts   = self::getAccounts();

        $report = $this->fetchExpensesReport($fromDate, $toDate, $categoryId, $accountId, $search);
        $expenses     = $report['expenses'];
        $totalAmount  = $report['totalAmount'];
        $catSummary   = $report['catSummary'];
        $accSummary   = $report['accSummary'];

        $pageTitle = 'Expenses Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/expenses.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function expensesPrint(): void {
        $this->authorizeReport('rpt_expenses');

        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');
        $categoryId = $this->inputInt('category_id', 0, 'get');
        $accountId  = $this->inputInt('account_id', 0, 'get');
        $search     = $this->input('search', '', 'get');

        $report = $this->fetchExpensesReport($fromDate, $toDate, $categoryId, $accountId, $search);
        $expenses     = $report['expenses'];
        $totalAmount  = $report['totalAmount'];
        $catSummary   = $report['catSummary'];
        $accSummary   = $report['accSummary'];
        $settings     = self::getSettings();

        include __DIR__ . '/../views/reports/expenses_print.php';
    }

    /** @return array{expenses: list<array<string, mixed>>, totalAmount: float, catSummary: list<array<string, mixed>>, accSummary: list<array<string, mixed>>} */
    private function fetchExpensesReport(string $fromDate, string $toDate, int $categoryId, int $accountId, string $search): array {
        $where  = "WHERE e.date BETWEEN ? AND ? AND e.warehouse_id = ?";
        $params = [$fromDate, $toDate, $this->reportWarehouseId()];

        if ($categoryId) {
            $where .= " AND e.category_id = ?";
            $params[] = $categoryId;
        }
        if ($accountId) {
            $where .= " AND e.account_id = ?";
            $params[] = $accountId;
        }
        if ($search !== '') {
            $where .= " AND (e.expense_no LIKE ? OR e.description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $expenses = $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name, a.name as account_name, u.name as created_by_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN accounts a ON a.id = e.account_id
             LEFT JOIN users u ON u.id = e.created_by
             {$where}
             ORDER BY e.date DESC, e.id DESC",
            $params
        );

        $catSummary = $this->db->fetchAll(
            "SELECT ec.name as category, COUNT(*) as count, SUM(e.amount) as total
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             {$where}
             GROUP BY e.category_id ORDER BY total DESC",
            $params
        );

        $accSummary = $this->db->fetchAll(
            "SELECT a.name as account, COUNT(*) as count, SUM(e.amount) as total
             FROM expenses e
             LEFT JOIN accounts a ON a.id = e.account_id
             {$where}
             GROUP BY e.account_id ORDER BY total DESC",
            $params
        );

        return [
            'expenses'    => $expenses,
            'totalAmount' => (float) array_sum(array_column($expenses, 'amount')),
            'catSummary'  => $catSummary,
            'accSummary'  => $accSummary,
        ];
    }

    public function salesReturns(): void {
        $this->authorizeReport('rpt_sales_returns');

        $parties = (new Party())->listForFilter('customer');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $search   = $this->input('search', '', 'get');

        $report = $this->fetchSalesReturnsReport($fromDate, $toDate, $partyId, $search);
        extract($report);

        $pageTitle = 'Sales Returns Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/sales_returns.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function salesReturnsPrint(): void {
        $this->authorizeReport('rpt_sales_returns');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-d'), 'get');
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $search   = $this->input('search', '', 'get');

        $report   = $this->fetchSalesReturnsReport($fromDate, $toDate, $partyId, $search);
        $settings = self::getSettings();
        extract($report);

        include __DIR__ . '/../views/reports/sales_returns_print.php';
    }

    /** @return array{
     *   returns: list<array<string, mixed>>,
     *   totalAmount: float,
     *   totalQty: int,
     *   itemsByReturn: array<int, list<array<string, mixed>>>,
     *   custSummary: list<array<string, mixed>>
     * }
     */
    private function fetchSalesReturnsReport(string $fromDate, string $toDate, int $partyId, string $search): array {
        $db   = Database::getInstance();
        $whId = $this->reportWarehouseId();

        $where  = "WHERE r.type = 'sale_return' AND r.date BETWEEN ? AND ? AND r.warehouse_id = ?";
        $params = [$fromDate, $toDate, $whId];

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

        $totalAmount = (float) array_sum(array_column($returns, 'grand_total'));
        $totalQty    = 0;
        $itemsByReturn = [];

        if (!empty($returns)) {
            $idsArr       = array_column($returns, 'id');
            $placeholders = implode(',', array_fill(0, count($idsArr), '?'));
            $returnItems  = $db->fetchAll(
                "SELECT ri.return_id, i.name as item_name, ri.quantity, ri.unit_price, ri.total
                 FROM return_items ri
                 JOIN items i ON i.id = ri.item_id
                 WHERE ri.return_id IN ($placeholders)
                 ORDER BY ri.id",
                $idsArr
            );
            $totalQty = (int) array_sum(array_column($returnItems, 'quantity'));

            foreach ($returnItems as $ri) {
                $itemsByReturn[$ri['return_id']][] = $ri;
            }
        }

        $custSummary = $db->fetchAll(
            "SELECT p.name as party_name, COUNT(*) as count, SUM(r.grand_total) as total
             FROM returns r
             JOIN parties p ON p.id = r.party_id
             $where
             GROUP BY r.party_id ORDER BY total DESC LIMIT 8",
            $params
        );

        return compact('returns', 'totalAmount', 'totalQty', 'itemsByReturn', 'custSummary');
    }

    public function supplierStatement(): void {
        $this->authorizeReport('rpt_supplier_stmt');

        $suppliers = (new Party())->listForFilter('supplier');

        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');

        $supplier         = null;
        $transactions     = [];
        $transactionsAll  = [];
        $openingBal       = 0;
        $reportError      = null;
        $listTruncated    = false;
        $listLimit        = ListPage::REPORT_LEDGER_MAX;
        $ledgerTotalCount = 0;
        $summary          = ['total_purchases' => 0, 'total_paid' => 0, 'balance' => 0];

        if ($supplierId) {
            $reportError = ListPage::validateReportDateRange($fromDate, $toDate);
            $supplier = $this->db->fetchOne("SELECT * FROM parties WHERE id = ?", [$supplierId]);

            if ($supplier && $reportError === null) {
                $whId       = $this->reportWarehouseId();
                $partyModel = new Party();
                $rawTxns    = $partyModel->getPartyStatementTransactions($supplierId, $fromDate, $toDate, $whId);
                $openingUnified = $partyModel->computeStatementOpeningBalance($supplierId, $fromDate, $whId);
                $openingBal = Party::displayBalanceDue($supplier, $openingUnified, 'supplier')['amount'];

                $runningPayable   = $openingBal;
                $totalPurchases   = 0.0;
                $totalPaid        = 0.0;
                $transactions     = [];

                foreach ($rawTxns as $t) {
                    $txnType = (string) ($t['txn_type'] ?? '');
                    $debit   = (float) ($t['debit'] ?? 0);
                    $credit  = (float) ($t['credit'] ?? 0);
                    $amount  = 0.0;
                    $displayType = 'payment';

                    if ($txnType === 'purchase' || $txnType === 'import_payable') {
                        $amount      = $credit;
                        $displayType = 'purchase';
                        $totalPurchases += $amount;
                    } elseif ($txnType === 'return') {
                        $amount = $debit > 0 ? $debit : $credit;
                        if ($debit > 0) {
                            $totalPaid += $amount;
                        } else {
                            $totalPurchases += $amount;
                        }
                    } elseif ($txnType === 'payment') {
                        $amount = $debit > 0 ? $debit : $credit;
                        if ($debit > 0) {
                            $totalPaid += $debit;
                        }
                        if ($credit > 0) {
                            $totalPaid += $credit;
                        }
                    } elseif ($txnType === 'sale') {
                        $amount      = $debit;
                        $displayType = 'payment';
                        $totalPaid  += $amount;
                    }

                    $runningPayable += $credit - $debit;

                    $transactions[] = [
                        'id'         => $t['id'],
                        'txn_type'   => $displayType === 'purchase' ? 'purchase' : 'payment',
                        'ref_no'     => $t['ref_no'],
                        'date'       => $t['date'],
                        'amount'     => $amount,
                        'notes'      => $t['notes'] ?? '',
                        'status'     => $t['status'] ?? '',
                        'created_at' => $t['created_at'] ?? '',
                        'running'    => $runningPayable,
                    ];
                }

                $summary['total_purchases'] = $totalPurchases;
                $summary['total_paid']      = $totalPaid;
                $summary['balance']         = $runningPayable;

                $ledgerView = $this->finalizeReportLedger($transactions, $fromDate, $toDate);
                if ($ledgerView['reportError'] !== null) {
                    $reportError = $ledgerView['reportError'];
                    $transactions = [];
                    $transactionsAll = [];
                } else {
                    $transactions     = $ledgerView['transactions'];
                    $transactionsAll  = $ledgerView['transactionsAll'];
                    $listTruncated    = $ledgerView['listTruncated'];
                    $listLimit        = $ledgerView['listLimit'];
                    $ledgerTotalCount = $ledgerView['ledgerTotalCount'];
                }
            }
        }

        $pageTitle = 'Supplier Statement';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/supplier_statement.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Validate and normalize a report as-of / range date (YYYY-MM-DD).
     */
    private function parseReportDate(string $input, string $fallback = ''): string {
        $fallback = $fallback !== '' ? $fallback : date('Y-m-d');
        if ($input === ''
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)
            || ($dt = DateTimeImmutable::createFromFormat('Y-m-d', $input)) === false
            || $dt->format('Y-m-d') !== $input) {
            return $fallback;
        }
        if ($input > date('Y-m-d')) {
            return date('Y-m-d');
        }
        return $input;
    }

    /**
     * Active accounts with their balance reconstructed as of a date.
     *
     * @return list<array<string,mixed>>
     */
    private function accountsAsOf(string $asOfDate): array {
        return NetWorthService::accountsAsOf($this->db, $asOfDate, $this->reportWarehouseId());
    }

    /**
     * Balance sheet totals as of a date (same rules as balanceSheet report).
     *
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
    private function balanceSheetTotalsAsOf(string $asOfDate): array {
        return NetWorthService::snapshot($this->db, $asOfDate, $this->reportWarehouseId());
    }

    /**
     * Net worth for a month-end: prefer a recorded branch snapshot, fall back
     * to live reconstruction when none was captured for that month.
     *
     * @return array{net_worth: float, recorded: bool}
     */
    private function monthEndNetWorth(string $endDate): array {
        $whId     = $this->reportWarehouseId();
        $recorded = NetWorthService::recordedSnapshot($this->db, $endDate, $whId);
        if ($recorded !== null) {
            return [
                'net_worth' => (float) ($recorded['net_worth'] ?? 0),
                'recorded'  => true,
            ];
        }

        return [
            'net_worth' => $this->balanceSheetTotalsAsOf($endDate)['net_worth'],
            'recorded'  => false,
        ];
    }

    /**
     * Month-end net worth snapshots for the three months before the report as-of date.
     *
     * @param array<string,float>|null $currentSnapshot Reuse the live as-of snapshot for the final trend point.
     * @return list<array{label: string, date: string, net_worth: float, recorded: bool, change: float|null, change_pct: float|null}>
     */
    private function buildNetWorthTrend(string $asOfDate, ?array $currentSnapshot = null): array {
        $ref = new DateTimeImmutable($asOfDate);
        $points = [];

        for ($monthsBack = 3; $monthsBack >= 1; $monthsBack--) {
            $monthEnd = $ref->modify('first day of this month')
                ->modify("-{$monthsBack} months")
                ->modify('last day of this month');
            $endDate = $monthEnd->format('Y-m-d');
            $mw = $this->monthEndNetWorth($endDate);
            $points[] = [
                'label'     => $monthEnd->format('M Y'),
                'date'      => $endDate,
                'net_worth' => $mw['net_worth'],
                'recorded'  => $mw['recorded'],
            ];
        }

        $currentTotals = $currentSnapshot ?? $this->balanceSheetTotalsAsOf($asOfDate);
        $points[] = [
            'label'     => $ref->format('Y-m-d') === date('Y-m-d') ? 'Today' : date('d M Y', strtotime($asOfDate)),
            'date'      => $asOfDate,
            'net_worth' => (float) ($currentTotals['net_worth'] ?? 0),
            'recorded'  => false,
        ];

        $prevNet = null;
        foreach ($points as $i => $point) {
            $change    = $prevNet !== null ? $point['net_worth'] - $prevNet : null;
            $changePct = ($prevNet !== null && abs($prevNet) > 0.001)
                ? ($change / abs($prevNet)) * 100
                : null;
            $points[$i]['change']     = $change;
            $points[$i]['change_pct'] = $changePct;
            $prevNet = $point['net_worth'];
        }

        return $points;
    }

    /**
     * Capture (or refresh) a net worth snapshot for a chosen month-end.
     * POST-only; CSRF is auto-verified by BaseController. Stored as 'manual'.
     */
    public function captureNetWorth(): void {
        $this->authorizeReport('rpt_balance_sheet');

        $asOf = $this->input('as_of', date('Y-m-d'), 'get');
        $back = '?page=reports&action=balanceSheet&as_of=' . urlencode($asOf);

        if (!$this->isPost()) {
            $this->redirect($back);
        }

        $snapshotDate = $this->input('snapshot_date', '', 'post');
        if ($snapshotDate === ''
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $snapshotDate)
            || ($dt = DateTimeImmutable::createFromFormat('Y-m-d', $snapshotDate)) === false
            || $dt->format('Y-m-d') !== $snapshotDate) {
            $this->flash('error', 'Please choose a valid snapshot date.');
            $this->redirect($back);
        }

        if ($snapshotDate > date('Y-m-d')) {
            $this->flash('error', 'Cannot capture a snapshot for a future date.');
            $this->redirect($back);
        }

        try {
            $whId   = $this->reportWarehouseId();
            $totals = NetWorthService::snapshot($this->db, $snapshotDate, $whId);
            NetWorthService::storeSnapshot($this->db, $snapshotDate, $totals, 'manual', null, $whId);
            $this->logActivity('capture_networth_snapshot', 'reports', 0,
                'Snapshot ' . $snapshotDate . ' = ' . APP_CURRENCY . ' ' . number_format($totals['net_worth'], DECIMAL_PLACES));
            $this->flash('success', 'Net worth snapshot saved for ' . date('d M Y', strtotime($snapshotDate))
                . ' (' . APP_CURRENCY . ' ' . number_format($totals['net_worth'], DECIMAL_PLACES) . ').');
        } catch (Throwable $e) {
            error_log('captureNetWorth failed: ' . $e->getMessage());
            $this->flash('error', 'Could not save snapshot. Ensure the net_worth_snapshots table exists (see database/schema.sql).');
        }

        $this->redirect($back);
    }

    public function balanceSheet(): void {
        $this->authorizeReport('rpt_balance_sheet');

        $date = $this->parseReportDate($this->input('as_of', date('Y-m-d'), 'get'));
        $whId = $this->reportWarehouseId();

        $bundle           = NetWorthService::balanceSheetBundle($this->db, $date, $whId);
        $snapshot         = $bundle['snapshot'];
        $accounts         = $bundle['accounts'];
        $receivables      = $bundle['receivables'];
        $payables         = $bundle['payables'];
        $poAdvances       = $bundle['po_advances'];
        $totalCash        = $snapshot['total_cash'];
        $totalReceivable  = $snapshot['total_receivable'];
        $totalPayable     = $snapshot['total_payable'];
        $totalPoAdvances  = $snapshot['total_po_advances'];
        $stockVal         = $snapshot['stock_val'];
        $totalAssets      = $snapshot['total_assets'];
        $totalLiabilities = $snapshot['total_liabilities'];
        $netWorth         = $snapshot['net_worth'];
        $netWorthTrend    = $this->buildNetWorthTrend($date, $snapshot);

        $compareDate = (new DateTimeImmutable($date))
            ->modify('first day of this month')
            ->modify('-1 day')
            ->format('Y-m-d');
        $compareSnapshot = NetWorthService::snapshot($this->db, $compareDate, $whId);
        $compareLabel    = date('d M Y', strtotime($compareDate));

        $pageTitle = 'Balance Sheet';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/balance_sheet.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function balanceSheetPrint(): void {
        $this->authorizeReport('rpt_balance_sheet');

        $date = $this->parseReportDate($this->input('as_of', date('Y-m-d'), 'get'));
        $whId = $this->reportWarehouseId();

        $bundle           = NetWorthService::balanceSheetBundle($this->db, $date, $whId);
        $snapshot         = $bundle['snapshot'];
        $accounts         = $bundle['accounts'];
        $receivables      = $bundle['receivables'];
        $payables         = $bundle['payables'];
        $poAdvances       = $bundle['po_advances'];
        $totalCash        = $snapshot['total_cash'];
        $totalReceivable  = $snapshot['total_receivable'];
        $totalPayable     = $snapshot['total_payable'];
        $totalPoAdvances  = $snapshot['total_po_advances'];
        $stockVal         = $snapshot['stock_val'];
        $totalAssets      = $snapshot['total_assets'];
        $totalLiabilities = $snapshot['total_liabilities'];
        $netWorth         = $snapshot['net_worth'];

        $compareDate = (new DateTimeImmutable($date))
            ->modify('first day of this month')
            ->modify('-1 day')
            ->format('Y-m-d');
        $compareSnapshot = NetWorthService::snapshot($this->db, $compareDate, $whId);
        $compareLabel    = date('d M Y', strtotime($compareDate));
        $settings        = self::getSettings();

        include __DIR__ . '/../views/reports/balance_sheet_print.php';
    }

    // Customer IMEI Report — list of IMEIs sold to a customer with item, invoice, date
    public function customerImei(): void {
        $this->authorizeReport('rpt_customer_imei');

        $partyId   = $this->inputInt('party_id', 0, 'get');
        $itemId    = $this->inputInt('item_id', 0, 'get');
        $fromDate  = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate    = $this->input('to_date', date('Y-m-d'), 'get');
        $invoiceNo = trim((string) $this->input('invoice_no', '', 'get'));

        $customers = (new Party())->listForFilter('customer');
        $items = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE is_active = 1 ORDER BY name ASC"
        );

        $records = $partyId
            ? $this->fetchCustomerImeiRecords($partyId, $fromDate, $toDate, $invoiceNo, $itemId)
            : [];

        $pageTitle = 'Customer IMEI Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/customer_imei.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function customerImeiPrint(): void {
        $this->authorizeReport('rpt_customer_imei');

        $partyId   = $this->inputInt('party_id', 0, 'get');
        $itemId    = $this->inputInt('item_id', 0, 'get');
        $fromDate  = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate    = $this->input('to_date', date('Y-m-d'), 'get');
        $invoiceNo = trim((string) $this->input('invoice_no', '', 'get'));

        if (!$partyId) {
            $this->redirect('?page=reports&action=customerImei');
        }

        $party = $this->db->fetchOne(
            "SELECT id, name, phone, party_code FROM parties
             WHERE id = ? AND is_active = 1 AND (type = 'customer' OR type = 'both')",
            [$partyId]
        );
        if (!$party) {
            $this->redirect('?page=reports&action=customerImei');
        }

        $itemName = '';
        if ($itemId > 0) {
            $itemRow = $this->db->fetchOne(
                "SELECT name FROM items WHERE id = ? AND is_active = 1",
                [$itemId]
            );
            $itemName = $itemRow ? (string) $itemRow['name'] : '';
        }

        $records  = $this->fetchCustomerImeiRecords($partyId, $fromDate, $toDate, $invoiceNo, $itemId);
        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/customer_imei_print.php';
    }

    public function customerImeiExport(): void {
        $this->authorizeReport('rpt_customer_imei');

        $partyId   = $this->inputInt('party_id', 0, 'get');
        $itemId    = $this->inputInt('item_id', 0, 'get');
        $fromDate  = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate    = $this->input('to_date', date('Y-m-d'), 'get');
        $invoiceNo = trim((string) $this->input('invoice_no', '', 'get'));

        if (!$partyId) {
            $this->redirect('?page=reports&action=customerImei');
        }

        $party = $this->db->fetchOne(
            "SELECT id, name FROM parties
             WHERE id = ? AND is_active = 1 AND (type = 'customer' OR type = 'both')",
            [$partyId]
        );
        if (!$party) {
            $this->redirect('?page=reports&action=customerImei');
        }

        $records = $this->fetchCustomerImeiRecords($partyId, $fromDate, $toDate, $invoiceNo, $itemId);

        $safeName = preg_replace('/[^a-z0-9]+/i', '_', (string) $party['name']) ?: 'Customer';
        $filename = 'Customer_IMEI_' . $safeName . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            exit;
        }

        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['#', 'Sales Invoice', 'Date', 'Customer', 'Item', 'Brand', 'Model', 'IMEI', 'IMEI 2']);

        $num = 1;
        foreach ($records as $r) {
            fputcsv($out, [
                $num++,
                $r['invoice_no'] ?? '',
                $r['date'] ?? '',
                $r['party_name'] ?? '',
                $r['item_name'] ?? '',
                $r['brand'] ?? '',
                $r['model'] ?? '',
                $r['imei'] ?? '',
                $r['imei2'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    /** @return list<array<string, mixed>> */
    private function fetchCustomerImeiRecords(
        int $partyId,
        string $fromDate,
        string $toDate,
        string $invoiceNo,
        int $itemId = 0
    ): array {
        $where  = "WHERE s.party_id = ? AND s.warehouse_id = ? AND s.status != 'cancelled' AND ir.id IS NOT NULL";
        $params = [$partyId, $this->reportWarehouseId()];

        if ($fromDate !== '') {
            $where .= " AND s.date >= ?";
            $params[] = $fromDate;
        }
        if ($toDate !== '') {
            $where .= " AND s.date <= ?";
            $params[] = $toDate;
        }
        if ($invoiceNo !== '') {
            $where .= " AND s.invoice_no LIKE ?";
            $params[] = '%' . $invoiceNo . '%';
        }
        if ($itemId > 0) {
            $where .= " AND si.item_id = ?";
            $params[] = $itemId;
        }

        return $this->db->fetchAll(
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
             ORDER BY s.date DESC, s.id DESC, i.name ASC, ir.imei ASC",
            $params
        );
    }

    // Purchase IMEI Report — IMEIs received on purchase invoices, grouped by invoice & item
    public function purchaseImei(): void {
        $this->authorizeReport('rpt_purchase_imei');

        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $fromDate   = $this->input('from_date', '', 'get');
        $toDate     = $this->input('to_date', '', 'get');
        $invoiceNo  = trim((string) $this->input('invoice_no', '', 'get'));

        $suppliers = (new Party())->listForFilter('supplier');

        $records = $supplierId
            ? $this->fetchPurchaseImeiRecords($supplierId, $fromDate, $toDate, $invoiceNo)
            : [];

        $pageTitle = 'Purchase IMEI Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/purchase_imei.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function purchaseImeiPrint(): void {
        $this->authorizeReport('rpt_purchase_imei');

        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $fromDate   = $this->input('from_date', '', 'get');
        $toDate     = $this->input('to_date', '', 'get');
        $invoiceNo  = trim((string) $this->input('invoice_no', '', 'get'));

        if (!$supplierId) {
            $this->redirect('?page=reports&action=purchaseImei');
        }

        $supplier = $this->db->fetchOne(
            "SELECT id, name, phone, party_code FROM parties
             WHERE id = ? AND is_active = 1 AND (type = 'supplier' OR type = 'both')",
            [$supplierId]
        );
        if (!$supplier) {
            $this->redirect('?page=reports&action=purchaseImei');
        }

        $records  = $this->fetchPurchaseImeiRecords($supplierId, $fromDate, $toDate, $invoiceNo);
        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/purchase_imei_print.php';
    }

    public function purchaseImeiExport(): void {
        $this->authorizeReport('rpt_purchase_imei');

        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $fromDate   = $this->input('from_date', '', 'get');
        $toDate     = $this->input('to_date', '', 'get');
        $invoiceNo  = trim((string) $this->input('invoice_no', '', 'get'));

        if (!$supplierId) {
            $this->redirect('?page=reports&action=purchaseImei');
        }

        $supplier = $this->db->fetchOne(
            "SELECT id, name FROM parties
             WHERE id = ? AND is_active = 1 AND (type = 'supplier' OR type = 'both')",
            [$supplierId]
        );
        if (!$supplier) {
            $this->redirect('?page=reports&action=purchaseImei');
        }

        $records = $this->fetchPurchaseImeiRecords($supplierId, $fromDate, $toDate, $invoiceNo);

        $safeName = preg_replace('/[^a-z0-9]+/i', '_', (string) $supplier['name']) ?: 'Supplier';
        $filename = 'Purchase_IMEI_' . $safeName . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            exit;
        }

        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['#', 'Purchase Invoice', 'Date', 'Supplier', 'Item', 'Brand', 'Model', 'IMEI', 'IMEI 2']);

        $num = 1;
        foreach ($records as $r) {
            fputcsv($out, [
                $num++,
                $r['invoice_no'] ?? '',
                $r['date'] ?? '',
                $r['party_name'] ?? '',
                $r['item_name'] ?? '',
                $r['brand'] ?? '',
                $r['model'] ?? '',
                $r['imei'] ?? '',
                $r['imei2'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    public function purchaseOrders(): void {
        $this->authorizeReport('rpt_purchase_orders');

        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');
        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $status     = $this->input('status', '', 'get');
        $currency   = $this->input('currency', '', 'get');
        $search     = trim((string) $this->input('search', '', 'get'));

        $suppliers = (new Party())->listForFilter('supplier');
        $report    = $this->fetchPurchaseOrdersReport($fromDate, $toDate, $supplierId, $status, $currency, $search);
        $orders    = $report['orders'];
        $summary   = $report['summary'];
        $itemsByPo = $this->fetchPurchaseOrderItemsGrouped(array_map(fn($o) => (int) $o['id'], $orders));

        $pageTitle = 'Purchase Orders Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/purchase_orders.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function purchaseOrdersPrint(): void {
        $this->authorizeReport('rpt_purchase_orders');

        $fromDate   = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate     = $this->input('to_date', date('Y-m-d'), 'get');
        $supplierId = $this->inputInt('supplier_id', 0, 'get');
        $status     = $this->input('status', '', 'get');
        $currency   = $this->input('currency', '', 'get');
        $search     = trim((string) $this->input('search', '', 'get'));

        $report     = $this->fetchPurchaseOrdersReport($fromDate, $toDate, $supplierId, $status, $currency, $search);
        $orders     = $report['orders'];
        $summary    = $report['summary'];
        $itemsByPo  = $this->fetchPurchaseOrderItemsGrouped(array_map(fn($o) => (int) $o['id'], $orders));
        $settings   = self::getSettings();

        include __DIR__ . '/../views/reports/purchase_orders_print.php';
    }

    /** @param list<int> $poIds @return array<int, list<array<string, mixed>>> */
    private function fetchPurchaseOrderItemsGrouped(array $poIds): array {
        $poIds = array_values(array_filter(array_map('intval', $poIds)));
        if ($poIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.sku, i.unit
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id IN ({$placeholders})
             ORDER BY poi.po_id ASC, poi.id ASC",
            $poIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['po_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * @return array{
     *   orders: list<array<string, mixed>>,
     *   summary: array{
     *     count: int,
     *     statusCounts: array<string, int>,
     *     totalKwd: float,
     *     totalPaidKwd: float,
     *     byCurrency: list<array<string, mixed>>
     *   }
     * }
     */
    private function fetchPurchaseOrdersReport(
        string $fromDate,
        string $toDate,
        int $supplierId,
        string $status,
        string $currency,
        string $search
    ): array {
        $where  = "WHERE po.warehouse_id = ? AND po.date BETWEEN ? AND ?";
        $params = [$this->reportWarehouseId(), $fromDate, $toDate];

        if ($supplierId) {
            $where .= " AND po.party_id = ?";
            $params[] = $supplierId;
        }
        if ($status !== '') {
            $where .= " AND po.status = ?";
            $params[] = $status;
        }
        if ($currency !== '') {
            $where .= " AND po.currency = ?";
            $params[] = $currency;
        }
        if ($search !== '') {
            $where .= " AND (po.po_no LIKE ? OR p.name LIKE ? OR po.supplier_ref LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $orders = $this->db->fetchAll(
            "SELECT po.*, p.name as supplier_name, p.party_code,
                    pur.invoice_no as converted_invoice_no,
                    (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id = po.id) as item_count,
                    (SELECT COALESCE(SUM(poi.quantity), 0) FROM purchase_order_items poi WHERE poi.po_id = po.id) as total_qty
             FROM purchase_orders po
             JOIN parties p ON p.id = po.party_id
             LEFT JOIN purchases pur ON pur.id = po.converted_to
             {$where}
             ORDER BY po.date DESC, po.id DESC",
            $params
        );

        $statusCounts = ['draft' => 0, 'paid' => 0, 'converted' => 0, 'cancelled' => 0];
        $totalKwd     = 0.0;
        $totalPaidKwd = 0.0;
        $byCurrency   = [];

        foreach ($orders as $o) {
            $st = (string) ($o['status'] ?? '');
            if (isset($statusCounts[$st])) {
                $statusCounts[$st]++;
            }

            if ($st === 'cancelled') {
                continue;
            }

            $kwdTotal = (float) $o['subtotal_kwd'] + (float) ($o['other_charges_kwd'] ?? 0);
            $totalKwd += $kwdTotal;
            $totalPaidKwd += (float) ($o['paid_kwd'] ?? 0);

            $cur = (string) ($o['currency'] ?? 'KWD');
            if (!isset($byCurrency[$cur])) {
                $byCurrency[$cur] = ['currency' => $cur, 'count' => 0, 'foreign_total' => 0.0, 'kwd_total' => 0.0];
            }
            $byCurrency[$cur]['count']++;
            $byCurrency[$cur]['foreign_total'] += (float) ($o['subtotal_foreign'] ?? 0);
            $byCurrency[$cur]['kwd_total'] += $kwdTotal;
        }

        usort($byCurrency, fn($a, $b) => (float) $b['kwd_total'] <=> (float) $a['kwd_total']);

        return [
            'orders'  => $orders,
            'summary' => [
                'count'        => count($orders),
                'statusCounts' => $statusCounts,
                'totalKwd'     => $totalKwd,
                'totalPaidKwd' => $totalPaidKwd,
                'byCurrency'   => array_values($byCurrency),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fetchPurchaseImeiRecords(int $supplierId, string $fromDate, string $toDate, string $invoiceNo): array {
        $where  = "WHERE pur.party_id = ? AND pur.warehouse_id = ? AND pur.status != 'cancelled' AND ir.purchase_id IS NOT NULL";
        $params = [$supplierId, $this->reportWarehouseId()];

        if ($fromDate !== '') {
            $where .= " AND pur.date >= ?";
            $params[] = $fromDate;
        }
        if ($toDate !== '') {
            $where .= " AND pur.date <= ?";
            $params[] = $toDate;
        }
        if ($invoiceNo !== '') {
            $where .= " AND pur.invoice_no LIKE ?";
            $params[] = '%' . $invoiceNo . '%';
        }

        return $this->db->fetchAll(
            "SELECT ir.imei, ir.imei2, i.name as item_name, i.brand, i.model,
                    pur.invoice_no, pur.date, pur.id as purchase_id,
                    par.name as party_name, par.phone as party_phone, par.party_code
             FROM imei_records ir
             JOIN purchases pur ON pur.id = ir.purchase_id
             JOIN items i ON i.id = ir.item_id
             JOIN parties par ON par.id = pur.party_id
             {$where}
             ORDER BY pur.date DESC, pur.id DESC, i.name ASC, ir.imei ASC",
            $params
        );
    }

    public function partnerProfit(): void {
        $this->authorizeReport('rpt_partner_profit');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-t'), 'get');
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $status   = $this->input('status', '', 'get');

        $report   = $this->fetchPartnerProfitReport($fromDate, $toDate, $partyId, $status);
        $rows     = $report['rows'];
        $payments = $report['payments'];
        $partners = $report['partners'];
        $summary  = $report['summary'];

        $pageTitle = 'Partner Profit Report';
        $page      = 'reports';

        ob_start();
        include __DIR__ . '/../views/reports/partner_profit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function partnerProfitPrint(): void {
        $this->authorizeReport('rpt_partner_profit');

        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-t'), 'get');
        $partyId  = $this->inputInt('party_id', 0, 'get');
        $status   = $this->input('status', '', 'get');

        $report   = $this->fetchPartnerProfitReport($fromDate, $toDate, $partyId, $status);
        $rows     = $report['rows'];
        $payments = $report['payments'];
        $summary  = $report['summary'];
        $settings = self::getSettings();

        include __DIR__ . '/../views/reports/partner_profit_print.php';
    }

    /**
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   payments: list<array<string, mixed>>,
     *   partners: list<array<string, mixed>>,
     *   summary: array<string, float|int>
     * }
     */
    private function fetchPartnerProfitReport(string $fromDate, string $toDate, int $partyId, string $status): array {
        $wh       = $this->reportWarehouseId();
        $whClause = 'AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)';

        $where  = "WHERE ipa.leg = 'partner' AND ipa.status != 'cancelled'
                   AND ipa.date BETWEEN ? AND ? {$whClause}";
        $params = [$fromDate, $toDate, $wh, $wh];

        if ($partyId > 0) {
            $where .= ' AND ipa.party_id = ?';
            $params[] = $partyId;
        }
        if ($status === 'open' || $status === 'paid') {
            $where .= ' AND ipa.status = ?';
            $params[] = $status;
        }

        $rows = $this->db->fetchAll(
            "SELECT ipa.id, ipa.accrual_no, ipa.date, ipa.amount, ipa.status,
                    ipa.shipment_id, ipa.shipment_item_charge_id,
                    s.shipment_no, s.received_date,
                    p.id as partner_party_id, p.name as partner_name,
                    sic.partner_profit_per_pc, sic.quantity,
                    i.name as item_name, po.po_no,
                    pay.payment_no, pay.date as payment_date
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = ipa.party_id
             LEFT JOIN payments pay ON pay.id = ipa.payment_id AND pay.status = 'active'
             {$where}
             ORDER BY ipa.date DESC, ipa.id DESC",
            $params
        );

        $payWhere  = "WHERE p.ref_type = 'shipment_partner' AND p.status = 'active' AND p.payment_type = 'out'
                      AND p.date BETWEEN ? AND ? AND p.warehouse_id = ?";
        $payParams = [$fromDate, $toDate, $wh];
        if ($partyId > 0) {
            $payWhere .= ' AND p.party_id = ?';
            $payParams[] = $partyId;
        }

        $payments = $this->db->fetchAll(
            "SELECT p.id, p.payment_no, p.date, p.amount, p.notes, p.ref_id,
                    pa.name as partner_name, pa.id as partner_party_id,
                    sic.quantity, sic.partner_profit_per_pc,
                    s.shipment_no, po.po_no, i.name as item_name
             FROM payments p
             JOIN parties pa ON pa.id = p.party_id
             LEFT JOIN shipment_item_charges sic ON sic.id = p.ref_id
             LEFT JOIN shipments s ON s.id = sic.shipment_id
             LEFT JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             LEFT JOIN purchase_orders po ON po.id = poi.po_id
             LEFT JOIN items i ON i.id = sic.item_id
             {$payWhere}
             ORDER BY p.date DESC, p.id DESC",
            $payParams
        );

        $openWhere  = "WHERE ipa.leg = 'partner' AND ipa.status = 'open' {$whClause}";
        $openParams = [$wh, $wh];
        if ($partyId > 0) {
            $openWhere .= ' AND ipa.party_id = ?';
            $openParams[] = $partyId;
        }

        $totalOpen = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(ipa.amount), 0) as total
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             {$openWhere}",
            $openParams
        )['total'] ?? 0);

        $partners = $this->db->fetchAll(
            "SELECT DISTINCT p.id, p.name
             FROM parties p
             JOIN import_payable_accruals ipa ON ipa.party_id = p.id AND ipa.leg = 'partner'
             JOIN shipments s ON s.id = ipa.shipment_id
             WHERE ipa.status != 'cancelled' {$whClause}
             ORDER BY p.name",
            [$wh, $wh]
        );

        $totalAccrued     = 0.0;
        $totalPaidAccrual = 0.0;
        $totalOpenPeriod  = 0.0;
        $totalQty         = 0;
        foreach ($rows as $row) {
            $amt = (float) $row['amount'];
            $totalAccrued += $amt;
            $totalQty += (int) ($row['quantity'] ?? 0);
            if (($row['status'] ?? '') === 'paid') {
                $totalPaidAccrual += $amt;
            } elseif (($row['status'] ?? '') === 'open') {
                $totalOpenPeriod += $amt;
            }
        }

        $totalPaidCash = (float) array_sum(array_column($payments, 'amount'));

        return [
            'rows'     => $rows,
            'payments' => $payments,
            'partners' => $partners,
            'summary'  => [
                'totalAccrued'     => $totalAccrued,
                'totalPaidAccrual' => $totalPaidAccrual,
                'totalOpenPeriod'  => $totalOpenPeriod,
                'totalPaidCash'    => $totalPaidCash,
                'totalOpenAllTime' => $totalOpen,
                'totalQty'         => $totalQty,
                'lineCount'        => count($rows),
                'paymentCount'     => count($payments),
            ],
        ];
    }

}
