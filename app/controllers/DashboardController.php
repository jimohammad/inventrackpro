<?php

require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {
    private static function perfLoggingEnabled(): bool {
        if (defined('PERF_LOG_ENABLED') && PERF_LOG_ENABLED === true) {
            return true;
        }
        $raw = $_ENV['PERF_LOG_ENABLED'] ?? getenv('PERF_LOG_ENABLED') ?? '';
        $value = strtolower(trim((string) $raw));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private static function logPerf(string $scope, float $startedAt, int $thresholdMs = 150, array $context = []): void {
        if (!self::perfLoggingEnabled()) {
            return;
        }

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        if ($elapsedMs < $thresholdMs) {
            return;
        }

        $safeContext = [];
        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $safeContext[$k] = $v;
            } elseif (is_array($v)) {
                $safeContext[$k] = 'array(' . count($v) . ')';
            } else {
                $safeContext[$k] = gettype($v);
            }
        }

        error_log('[perf] ' . $scope . ' ' . $elapsedMs . 'ms ' . json_encode($safeContext));
    }

    public function index(): void {
        $startedAt = microtime(true);
        Auth::authorize('dashboard', 'view');

        $db = Database::getInstance();

        $whId = Auth::warehouseId();

        // Cache key based on user + warehouse + date
        $cacheKey  = 'dash_' . Auth::id() . '_' . $whId . '_' . date('Ymd_H');
        $cacheDir  = __DIR__ . '/../../backups/cache';
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0700, true);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.cache';
        $cacheLife = 900; // 15 minutes — reduces heavy receivables/payables (per-party net balance) recomputation

        // Force refresh with ?refresh=1
        if (isset($_GET['refresh'])) {
            @unlink($cacheFile);
        }

        $cached = null;
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLife) {
            $cached = json_decode(file_get_contents($cacheFile), true);
        }

        // Always-fresh data (not cached) — accounts and today's cash need real-time values
        $accounts = $db->fetchAll(
            "SELECT name, type, current_balance FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
        );
        $todayCashFresh = $db->fetchOne(
            "SELECT
                COALESCE(SUM(py.amount),0) as total_received,
                COUNT(*) as payment_count,
                COALESCE(SUM(CASE WHEN a.type = 'cash' THEN py.amount ELSE 0 END),0) as cash_total,
                COALESCE(SUM(CASE WHEN a.type = 'bank' THEN py.amount ELSE 0 END),0) as bank_total,
                COALESCE(SUM(CASE
                    WHEN a.type = 'cash' AND (a.is_default = 1 OR LOWER(TRIM(a.name)) = 'main cash')
                    THEN py.amount ELSE 0 END),0) as main_cash_total
             FROM payments py
             JOIN accounts a ON a.id = py.account_id
             WHERE py.date = CURDATE()
               AND py.payment_type = 'in'
               AND py.ref_type != 'discount'
               AND py.warehouse_id = ?",
            [$whId]
        );
        $mainCashReceived = (float)($todayCashFresh['main_cash_total'] ?? 0);
        $cashFallback = (float)($todayCashFresh['cash_total'] ?? 0);
        if ($mainCashReceived <= 0 && $cashFallback > 0) {
            $mainCashReceived = $cashFallback;
        }
        $todayCash = [
            'total' => (float)($todayCashFresh['total_received'] ?? 0),
            'count' => (int)($todayCashFresh['payment_count'] ?? 0),
            'main_cash' => $mainCashReceived,
            'bank_total' => (float)($todayCashFresh['bank_total'] ?? 0),
        ];

        // Always-fresh data: today's sales card must update immediately after a sale.
        $todaySalesFresh = $db->fetchOne(
            "SELECT
                COALESCE(SUM(grand_total),0) as sales_total,
                COUNT(*) as sales_count
             FROM sales
             WHERE date = CURDATE()
               AND status != 'cancelled'
               AND warehouse_id = ?",
            [$whId]
        );
        $todaySales = [
            'total' => (float)($todaySalesFresh['sales_total'] ?? 0),
            'count' => (int)($todaySalesFresh['sales_count'] ?? 0),
        ];

        $expectedKeys = ['todayExpenses','stockValue','pendingReceivables','pendingPayables','lowStockItems','recentSales','topItems','pendingPOs'];
        $usedCache = false;
        if ($cached && !array_diff($expectedKeys, array_keys($cached))) {
            foreach ($expectedKeys as $key) {
                $$key = $cached[$key];
            }
            $usedCache = true;
        } else {
            // Today's expenses — filtered by warehouse
            $todayTotals = $db->fetchOne(
                "SELECT
                    COALESCE(SUM(amount),0) as expenses_total
                 FROM expenses
                 WHERE date = CURDATE()
                   AND warehouse_id = ?",
                [$whId]
            );
            $todayExpenses = ['total' => (float)$todayTotals['expenses_total']];

            // Stock value — filtered by warehouse
            $stockValue = $db->fetchOne(
                "SELECT COALESCE(SUM(s.quantity * i.purchase_price), 0) as total,
                        COALESCE(SUM(s.quantity), 0) as units
                 FROM stock s
                 JOIN items i ON i.id = s.item_id
                 WHERE s.quantity > 0 AND s.warehouse_id = ?",
                [$whId]
            );

            // Receivables / payables by unified party net balance (Party model semantics), not party type:
            // positive net = they owe you → receivables; negative = you owe them → payables.
            // Transactions are scoped to the active warehouse; opening_balance applies when the party
            // record is unassigned or assigned to this warehouse (avoids duplicating opening on other branches).
            $rpParams = array_fill(0, 7, $whId);
            $rpRow    = $db->fetchOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN party_net > 0.001 THEN party_net ELSE 0 END), 0) AS rec_total,
                    COALESCE(SUM(CASE WHEN party_net < -0.001 THEN -party_net ELSE 0 END), 0) AS pay_total,
                    COALESCE(SUM(CASE WHEN party_net > 0.001 THEN 1 ELSE 0 END), 0) AS rec_count,
                    COALESCE(SUM(CASE WHEN party_net < -0.001 THEN 1 ELSE 0 END), 0) AS pay_count
                 FROM (
                    SELECT
                        (CASE WHEN p.warehouse_id IS NULL OR p.warehouse_id = ? THEN p.opening_balance ELSE 0 END)
                        + COALESCE(g.sales_total, 0)
                        - COALESCE(g.sale_payments, 0)
                        - COALESCE(g.sale_returns, 0)
                        - COALESCE(g.purchase_total, 0)
                        + COALESCE(g.purchase_payments, 0)
                        + COALESCE(g.purchase_returns, 0) AS party_net
                    FROM parties p
                    LEFT JOIN (
                        SELECT party_id,
                               SUM(sales_total) AS sales_total,
                               SUM(sale_payments) AS sale_payments,
                               SUM(sale_returns) AS sale_returns,
                               SUM(purchase_total) AS purchase_total,
                               SUM(purchase_payments) AS purchase_payments,
                               SUM(purchase_returns) AS purchase_returns
                        FROM (
                            SELECT party_id, grand_total AS sales_total, 0 AS sale_payments, 0 AS sale_returns,
                                   0 AS purchase_total, 0 AS purchase_payments, 0 AS purchase_returns
                            FROM sales WHERE status != 'cancelled' AND warehouse_id = ?
                            UNION ALL
                            SELECT party_id, 0,
                                   CASE WHEN payment_type = 'in' THEN amount ELSE -amount END,
                                   0, 0, 0, 0
                            FROM payments WHERE ref_type IN ('sale','discount') AND warehouse_id = ?
                            UNION ALL
                            SELECT party_id, 0, 0, grand_total, 0, 0, 0
                            FROM `returns` WHERE type = 'sale_return' AND status = 'approved' AND warehouse_id = ?
                            UNION ALL
                            SELECT party_id, 0, 0, 0, grand_total, 0, 0
                            FROM purchases WHERE status != 'cancelled' AND warehouse_id = ?
                            UNION ALL
                            SELECT party_id, 0, 0, 0, 0, amount, 0
                            FROM payments WHERE ref_type IN ('purchase','purchase_order') AND warehouse_id = ?
                            UNION ALL
                            SELECT party_id, 0, 0, 0, 0, 0, grand_total
                            FROM `returns` WHERE type = 'purchase_return' AND status = 'approved' AND warehouse_id = ?
                        ) u
                        GROUP BY party_id
                    ) g ON g.party_id = p.id
                    WHERE p.is_active = 1
                 ) z",
                $rpParams
            );
            $pendingReceivables = [
                'total' => (float)($rpRow['rec_total'] ?? 0),
                'count' => (int)($rpRow['rec_count'] ?? 0),
            ];
            $pendingPayables = [
                'total' => (float)($rpRow['pay_total'] ?? 0),
                'count' => (int)($rpRow['pay_count'] ?? 0),
            ];

            // Low stock — filtered by warehouse
            $lowStockItems = $db->fetchAll(
                "SELECT i.name, i.sku, COALESCE(SUM(s.quantity),0) as qty, i.min_stock
                 FROM items i
                 LEFT JOIN stock s ON s.item_id = i.id AND s.warehouse_id = ?
                 WHERE i.is_active = 1
                 GROUP BY i.id, i.name, i.sku, i.min_stock
                 HAVING qty <= i.min_stock AND i.min_stock > 0
                 ORDER BY qty ASC LIMIT 10",
                [$whId]
            );

            // Recent sales — filtered by warehouse
            $recentSales = $db->fetchAll(
                "SELECT s.invoice_no, p.name as party_name, s.grand_total, s.status, s.date
                 FROM sales s
                 JOIN parties p ON p.id = s.party_id
                 WHERE s.warehouse_id = ?
                 ORDER BY s.id DESC LIMIT 5",
                [$whId]
            );

            // Top items this month — filtered by warehouse
            $topItems = $db->fetchAll(
                "SELECT i.name, SUM(si.quantity) as qty_sold, SUM(si.total) as revenue
                 FROM sale_items si
                 JOIN items i ON i.id = si.item_id
                 JOIN sales s ON s.id = si.sale_id
                 WHERE s.date >= DATE_FORMAT(CURDATE(),'%Y-%m-01') AND s.status != 'cancelled' AND s.warehouse_id = ?
                 GROUP BY si.item_id, i.name
                 ORDER BY qty_sold DESC LIMIT 5",
                [$whId]
            );

            // Pending Purchase Orders — filtered by warehouse
            $pendingPOs = $db->fetchOne(
                "SELECT COALESCE(SUM(subtotal_kwd),0) as total, COUNT(*) as count
                 FROM purchase_orders WHERE status IN ('draft','paid') AND warehouse_id = ?",
                [$whId]
            );

            // Cache results (excluding accounts and todayCash — those are fetched live above)
            $toCache = compact(
                'todayExpenses','stockValue',
                'pendingReceivables','pendingPayables','lowStockItems',
                'recentSales','topItems','pendingPOs'
            );
            @file_put_contents($cacheFile, json_encode($toCache), LOCK_EX);
        }

        $mandoobInvDash = null;
        if (Auth::can('mandoob_inventory', 'view')) {
            try {
                $miRow = $db->fetchOne(
                    "SELECT
                        COALESCE(SUM(CASE WHEN next_due_date IS NOT NULL AND next_due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue,
                        COALESCE(SUM(CASE WHEN next_due_date IS NOT NULL AND next_due_date >= CURDATE()
                            AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS due_soon
                     FROM mandoob_inventory_schedules
                     WHERE is_active = 1 AND warehouse_id = ?",
                    [$whId]
                );
                $mandoobInvDash = [
                    'overdue'  => (int) ($miRow['overdue'] ?? 0),
                    'due_soon' => (int) ($miRow['due_soon'] ?? 0),
                ];
            } catch (Throwable $e) {
                $mandoobInvDash = null;
            }
        }

        ob_start();
        include __DIR__ . '/../views/dashboard/index.php';
        $content = ob_get_clean();

        $pageTitle = 'Dashboard';
        $page      = 'dashboard';
        include __DIR__ . '/../views/layout.php';

        self::logPerf('dashboard.index', $startedAt, 300, [
            'user_id' => Auth::id(),
            'warehouse_id' => $whId,
            'cache' => $usedCache ? 'hit' : 'miss',
        ]);
    }

    // Global Search - returns JSON
    public function search(): void {
        $startedAt = microtime(true);
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['results' => []]);
            return;
        }
        $q = mb_substr($q, 0, 80);

        $db      = Database::getInstance();
        $prefix  = "{$q}%";
        $like    = "%{$q}%";
        $whId    = Auth::warehouseId();
        $results = [];

        // Short-lived cache for repeated typeahead terms.
        $cacheDir = __DIR__ . '/../../backups/cache/search';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }
        $cacheKey = 'srch_' . $whId . '_' . md5($q);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        $cacheLife = 20;
        $cacheHit = false;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLife) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false) {
                $cacheHit = true;
                self::logPerf('dashboard.search', $startedAt, 120, [
                    'warehouse_id' => $whId,
                    'q_len' => strlen($q),
                    'cache' => 'hit',
                ]);
                echo $cached;
                return;
            }
        }

        // Search invoices (sales) — warehouse scoped
        $sales = $db->fetchAll(
            "SELECT id, invoice_no, grand_total, status FROM sales
             WHERE warehouse_id = ?
               AND (invoice_no LIKE ? OR invoice_no LIKE ? OR notes LIKE ?)
             LIMIT 3",
            [$whId, $prefix, $like, $like]
        );
        foreach ($sales as $s) {
            $results[] = [
                'type'  => 'Sale',
                'label' => $s['invoice_no'],
                'sub'   => APP_CURRENCY . ' ' . number_format($s['grand_total'], DECIMAL_PLACES),
                'url'   => '?page=sales&action=detail&id=' . $s['id'],
            ];
        }

        // Search purchases — warehouse scoped
        $purchases = $db->fetchAll(
            "SELECT id, invoice_no, grand_total, status FROM purchases
             WHERE warehouse_id = ? AND (invoice_no LIKE ? OR invoice_no LIKE ?) LIMIT 3",
            [$whId, $prefix, $like]
        );
        foreach ($purchases as $p) {
            $results[] = [
                'type'  => 'Purchase',
                'label' => $p['invoice_no'],
                'sub'   => APP_CURRENCY . ' ' . number_format($p['grand_total'], DECIMAL_PLACES),
                'url'   => '?page=purchases&action=detail&id=' . $p['id'],
            ];
        }

        // Search IMEI — warehouse scoped
        $imeis = $db->fetchAll(
            "SELECT ir.imei, i.name as item_name, ir.status
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             WHERE ir.warehouse_id = ?
               AND (ir.imei LIKE ? OR ir.imei2 LIKE ? OR ir.imei LIKE ? OR ir.imei2 LIKE ?)
             LIMIT 3",
            [$whId, $prefix, $prefix, $like, $like]
        );
        foreach ($imeis as $im) {
            $results[] = [
                'type'  => 'IMEI',
                'label' => $im['imei'],
                'sub'   => $im['item_name'] . ' · ' . $im['status'],
                'url'   => '?page=imei&action=detail&imei=' . urlencode($im['imei']),
            ];
        }

        // Search parties
        $parties = $db->fetchAll(
            "SELECT id, name, phone, type FROM parties 
             WHERE name LIKE ? OR phone LIKE ? OR name LIKE ? OR phone LIKE ? LIMIT 3",
            [$prefix, $prefix, $like, $like]
        );
        foreach ($parties as $pa) {
            $results[] = [
                'type'  => ucfirst($pa['type']),
                'label' => $pa['name'],
                'sub'   => $pa['phone'],
                'url'   => '?page=parties&action=detail&id=' . $pa['id'],
            ];
        }

        $payload = json_encode(['results' => $results]);
        if ($payload === false) {
            echo json_encode(['results' => []]);
            return;
        }

        @file_put_contents($cacheFile, $payload, LOCK_EX);
        self::logPerf('dashboard.search', $startedAt, 120, [
            'warehouse_id' => $whId,
            'q_len' => strlen($q),
            'cache' => $cacheHit ? 'hit' : 'miss',
            'results' => count($results),
        ]);
        echo $payload;
    }
}
