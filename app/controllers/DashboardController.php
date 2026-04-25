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
        $cacheLife = 300; // 5 minutes

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
            "SELECT COALESCE(SUM(amount),0) as t, COUNT(*) as c
             FROM payments WHERE date = CURDATE() AND payment_type = 'in' AND ref_type != 'discount' AND warehouse_id = ?",
            [$whId]
        );
        $todayCash = ['total' => (float)$todayCashFresh['t'], 'count' => (int)$todayCashFresh['c']];

        $expectedKeys = ['todaySales','todayExpenses','stockValue','pendingReceivables','pendingPayables','lowStockItems','recentSales','topItems','pendingPOs'];
        $usedCache = false;
        if ($cached && !array_diff($expectedKeys, array_keys($cached))) {
            extract($cached);
            $usedCache = true;
        } else {
            // Combined today's totals in single round-trip — filtered by warehouse
            $todayTotals = $db->fetchOne(
                "SELECT
                    (SELECT COALESCE(SUM(grand_total),0) FROM sales WHERE date = CURDATE() AND status != 'cancelled' AND warehouse_id = ?) as sales_total,
                    (SELECT COUNT(*) FROM sales WHERE date = CURDATE() AND status != 'cancelled' AND warehouse_id = ?) as sales_count,
                    (SELECT COALESCE(SUM(amount),0) FROM expenses WHERE date = CURDATE() AND warehouse_id = ?) as expenses_total",
                [$whId, $whId, $whId]
            );
            $todaySales    = ['total' => (float)$todayTotals['sales_total'], 'count' => (int)$todayTotals['sales_count']];
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

            // Pending balances — compute aggregates directly (no PHP loop over all parties)
            $balAgg = $db->fetchOne(
                "SELECT
                    SUM(CASE WHEN bal > 0.001 THEN bal ELSE 0 END) as rec_total,
                    SUM(CASE WHEN bal > 0.001 THEN 1 ELSE 0 END) as rec_count,
                    SUM(CASE WHEN bal < -0.001 THEN ABS(bal) ELSE 0 END) as pay_total,
                    SUM(CASE WHEN bal < -0.001 THEN 1 ELSE 0 END) as pay_count
                 FROM (
                    SELECT p.opening_balance
                        + COALESCE(s.total, 0)
                        - COALESCE(ps.total, 0)
                        - COALESCE(rs.total, 0)
                        - COALESCE(pr.total, 0)
                        + COALESCE(pp.total, 0)
                        + COALESCE(rp.total, 0) as bal
                    FROM parties p
                    LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM sales WHERE status != 'cancelled' GROUP BY party_id) s ON s.party_id = p.id
                    LEFT JOIN (SELECT party_id, SUM(CASE WHEN payment_type='in' THEN amount ELSE -amount END) as total FROM payments WHERE ref_type IN ('sale','discount') GROUP BY party_id) ps ON ps.party_id = p.id
                    LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM returns WHERE type = 'sale_return' AND status = 'approved' GROUP BY party_id) rs ON rs.party_id = p.id
                    LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM purchases WHERE status != 'cancelled' GROUP BY party_id) pr ON pr.party_id = p.id
                    LEFT JOIN (SELECT party_id, SUM(amount) as total FROM payments WHERE ref_type = 'purchase' GROUP BY party_id) pp ON pp.party_id = p.id
                    LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM returns WHERE type = 'purchase_return' AND status = 'approved' GROUP BY party_id) rp ON rp.party_id = p.id
                    WHERE p.is_active = 1
                 ) party_bal"
            );
            $pendingReceivables = ['total' => (float)($balAgg['rec_total'] ?? 0), 'count' => (int)($balAgg['rec_count'] ?? 0)];
            $pendingPayables    = ['total' => (float)($balAgg['pay_total'] ?? 0), 'count' => (int)($balAgg['pay_count'] ?? 0)];

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
                'todaySales','todayExpenses','stockValue',
                'pendingReceivables','pendingPayables','lowStockItems',
                'recentSales','topItems','pendingPOs'
            );
            @file_put_contents($cacheFile, json_encode($toCache), LOCK_EX);
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
