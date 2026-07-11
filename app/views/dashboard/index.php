<?php
// Format currency helper
function money($val) {
    return APP_CURRENCY . ' ' . number_format($val, DECIMAL_PLACES);
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars(Auth::name()) ?> — <?= date('l, d F Y') ?></p>
    </div>
</div>

<?php if (!empty($mandoobInvDash) && (($mandoobInvDash['overdue'] ?? 0) > 0 || ($mandoobInvDash['due_soon'] ?? 0) > 0)): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="status">
    <div>
        <i class="bi bi-truck-front me-2"></i>
        <strong>Mandoob Inventory:</strong>
        <?= (int) ($mandoobInvDash['overdue'] ?? 0) ?> overdue,
        <?= (int) ($mandoobInvDash['due_soon'] ?? 0) ?> due within 7 days.
    </div>
    <?php if (Auth::can('mandoob_inventory', 'view')): ?>
    <a class="btn btn-sm btn-dark" href="?page=mandoob_inventory">Open schedule</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Stat Cards - All 6 in one row -->
<style>
.dash-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 12px 14px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s, box-shadow 0.15s;
    min-height: 118px;
    height: 100%;
}
.dash-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); cursor: pointer; }
.dash-card .dc-stripe { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 10px 0 0 10px; }
.dash-card .dc-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.dash-card .dc-label { font-size: 0.68rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
.dash-card .dc-value { font-size: 0.95rem; font-weight: 800; color: var(--text-main); line-height: 1.2; }
.dash-card .dc-sub { font-size: 0.7rem; color: var(--text-muted); margin-top: 4px; }
.dash-link { display: block; height: 100%; text-decoration: none; color: inherit; }
</style>

<div class="row g-2 mb-4">
    <!-- Today's Sales -->
    <div class="col-6 col-md-2">
        <a href="?page=sales" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#6366f1;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(99,102,241,0.12);"><i class="bi bi-bag-check" style="color:#6366f1;"></i></div>
                <div class="dc-label">Today Sales</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#6366f1;"><?= money($todaySales['total']) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-receipt me-1"></i><?= $todaySales['count'] ?> invoices</div>
        </div>
        </a>
    </div>

    <!-- Today's Amount Received -->
    <div class="col-6 col-md-2">
        <a href="?page=payments" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#10b981;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(16,185,129,0.12);"><i class="bi bi-cash-coin" style="color:#10b981;"></i></div>
                <div class="dc-label">Amount Received</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#10b981;"><?= money($todayCash['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;">
                <i class="bi bi-wallet2 me-1"></i><?= $todayCash['count'] ?? 0 ?> payments
                <div>Main Cash: <?= money($todayCash['main_cash'] ?? 0) ?></div>
                <div>All Banks: <?= money($todayCash['bank_total'] ?? 0) ?></div>
            </div>
        </div>
        </a>
    </div>

    <!-- Receivables (net they owe you — any party type) -->
    <div class="col-6 col-md-2">
        <a href="?page=parties&type=all" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#f59e0b;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(245,158,11,0.12);"><i class="bi bi-clock-history" style="color:#f59e0b;"></i></div>
                <div class="dc-label">Receivables</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#f59e0b;"><?= money($pendingReceivables['total']) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-people me-1"></i><?= (int)$pendingReceivables['count'] ?> parties owing you</div>
        </div>
        </a>
    </div>

    <!-- Payables (net you owe — any party type) -->
    <div class="col-6 col-md-2">
        <a href="?page=parties&type=all" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#ef4444;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(239,68,68,0.12);"><i class="bi bi-credit-card" style="color:#ef4444;"></i></div>
                <div class="dc-label">Payables</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#ef4444;"><?= money($pendingPayables['total']) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-truck me-1"></i><?= (int)$pendingPayables['count'] ?> parties you owe</div>
        </div>
        </a>
    </div>

    <!-- Stock Value -->
    <div class="col-6 col-md-2">
        <a href="?page=stock" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#8b5cf6;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(139,92,246,0.12);"><i class="bi bi-boxes" style="color:#8b5cf6;"></i></div>
                <div class="dc-label">Stock Value</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#8b5cf6;"><?= money($stockValue['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-box-seam me-1"></i><?= number_format($stockValue['units'] ?? 0) ?> units</div>
        </div>
        </a>
    </div>

    <!-- Pending POs -->
    <div class="col-6 col-md-2">
        <a href="?page=purchaseorders" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#3b82f6;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(59,130,246,0.12);"><i class="bi bi-file-earmark-text" style="color:#3b82f6;"></i></div>
                <div class="dc-label">Pending POs</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#3b82f6;"><?= money($pendingPOs['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-box-arrow-up-right me-1"></i><?= $pendingPOs['count'] ?? 0 ?> awaiting</div>
        </div>
        </a>
    </div>
</div>

<!-- Bottom Cards Row -->
<div class="row g-2">
    <!-- Account Balances -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-wallet2 me-1" style="color:#3b82f6;"></i>Accounts</span>
                <span style="font-size:0.68rem;color:var(--text-muted);"><?= count($accounts) ?></span>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php
                $typeIcons  = ['cash' => 'bi-cash', 'bank' => 'bi-bank', 'other' => 'bi-wallet2'];
                $typeColors = ['cash' => '#10b981', 'bank' => '#3b82f6', 'other' => '#8b5cf6'];
                $totalBal   = 0;
                ?>
                <?php foreach ($accounts as $acc):
                    $totalBal += (float)$acc['current_balance'];
                    $accType = $acc['normalized_type'] ?? $acc['type'];
                    $icon  = $typeIcons[$accType]  ?? 'bi-wallet2';
                    $color = $typeColors[$accType] ?? '#8b5cf6';
                    $bal   = (float)$acc['current_balance'];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(99,102,241,0.04)'" onmouseout="this.style.background=''">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:0.72rem;width:14px;text-align:center;"></i>
                        <span style="font-size:0.72rem;font-weight:500;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><span style="font-family:monospace;color:#6366f1;font-weight:700;">#<?= (int)$acc['id'] ?></span> <?= htmlspecialchars($acc['name']) ?></span>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:<?= $bal >= 0 ? $color : '#ef4444' ?>;font-family:monospace;flex-shrink:0;">
                        <?= number_format($bal, DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($accounts)): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:5px 8px;margin-top:2px;border-top:1.5px solid var(--border-color);">
                    <span style="font-size:0.68rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total</span>
                    <span style="font-size:0.78rem;font-weight:800;color:#1e293b;font-family:monospace;">
                        <?= APP_CURRENCY ?> <?= number_format($totalBal, DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Items This Month -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-trophy me-1" style="color:#f59e0b;"></i>Top Items</span>
                <a href="?page=reports&action=itemSales" style="font-size:0.68rem;color:var(--text-muted);text-decoration:none;">View All</a>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php if (empty($topItems)): ?>
                <p class="text-muted text-center py-2 mb-0" style="font-size:0.75rem;">No sales this month</p>
                <?php else: ?>
                <?php foreach ($topItems as $i => $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(245,158,11,0.04)'" onmouseout="this.style.background=''">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                        <span style="font-size:0.68rem;font-weight:800;color:<?= $i === 0 ? '#f59e0b' : '#94a3b8' ?>;width:12px;"><?= $i+1 ?></span>
                        <div style="min-width:0;">
                            <span style="font-size:0.72rem;font-weight:500;color:var(--text-main);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                <?= htmlspecialchars($item['name']) ?>
                            </span>
                            <span style="font-size:0.64rem;color:var(--text-muted);"><?= $item['qty_sold'] ?> sold</span>
                        </div>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:#10b981;flex-shrink:0;font-family:monospace;">
                        <?= number_format($item['revenue'], DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-receipt me-1" style="color:#6366f1;"></i>Recent Sales</span>
                <a href="?page=sales" style="font-size:0.68rem;color:#6366f1;text-decoration:none;font-weight:600;">View All →</a>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php if (empty($recentSales)): ?>
                <p class="text-muted text-center py-2 mb-0" style="font-size:0.75rem;">No sales yet</p>
                <?php else: ?>
                <?php foreach ($recentSales as $s): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(99,102,241,0.04)'" onmouseout="this.style.background=''">
                    <div style="min-width:0;">
                        <span style="font-size:0.72rem;font-weight:600;color:#6366f1;"><?= $s['invoice_no'] ?></span>
                        <span style="font-size:0.66rem;color:var(--text-muted);margin-left:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($s['party_name']) ?></span>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:var(--text-main);flex-shrink:0;font-family:monospace;">
                        <?= number_format($s['grand_total'], DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Comparison (horizontal bars) -->
    <?php
    $mc = $monthCompare ?? [
        'this_label' => date('M Y'),
        'last_label' => date('M Y', strtotime('first day of last month')),
        'sales' => ['this_month' => 0, 'last_month' => 0],
        'purchases' => ['this_month' => 0, 'last_month' => 0],
        'expenses' => ['this_month' => 0, 'last_month' => 0],
        'receipts' => ['this_month' => 0, 'last_month' => 0],
    ];
    $mc['receipts'] = $mc['receipts'] ?? ['this_month' => 0, 'last_month' => 0];
    ?>
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-bar-chart me-1" style="color:#6366f1;"></i>Monthly Compare</span>
                <span style="font-size:0.64rem;color:var(--text-muted);">vs last month</span>
            </div>
            <div class="card-body" style="padding:6px 10px 10px;">
                <div style="height:220px;">
                    <canvas id="monthCompareChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const monthCompare = <?= json_encode($mc) ?>;

    function initCompareChart() {
        if (typeof Chart === 'undefined' || window._dashCompareChartsInit) {
            return;
        }
        window._dashCompareChartsInit = true;

        const el = document.getElementById('monthCompareChart');
        if (!el) {
            return;
        }

        const fmt = function (val) {
            return '<?= APP_CURRENCY ?> ' + Number(val).toLocaleString(undefined, {
                minimumFractionDigits: <?= DECIMAL_PLACES ?>,
                maximumFractionDigits: <?= DECIMAL_PLACES ?>
            });
        };

        const chartFont = {
            family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            size: 11,
            weight: '500',
            lineHeight: 1.2
        };

        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Sales', 'Purchases', 'Received', 'Expenses'],
                datasets: [
                    {
                        label: monthCompare.last_label,
                        data: [
                            monthCompare.sales.last_month,
                            monthCompare.purchases.last_month,
                            monthCompare.receipts.last_month,
                            monthCompare.expenses.last_month
                        ],
                        backgroundColor: 'rgba(148,163,184,0.5)',
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: 10
                    },
                    {
                        label: monthCompare.this_label,
                        data: [
                            monthCompare.sales.this_month,
                            monthCompare.purchases.this_month,
                            monthCompare.receipts.this_month,
                            monthCompare.expenses.this_month
                        ],
                        backgroundColor: ['#6366f1', '#3b82f6', '#22c55e', '#ef4444'],
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: 10
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            boxWidth: 10,
                            boxHeight: 10,
                            font: chartFont,
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + fmt(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8',
                            font: { ...chartFont, size: 9, weight: '400' },
                            maxTicksLimit: 5,
                            callback: function (val) {
                                return Number(val).toLocaleString();
                            }
                        },
                        grid: { color: 'rgba(148,163,184,0.12)' }
                    },
                    y: {
                        ticks: {
                            color: '#334155',
                            font: chartFont,
                            padding: 6
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    window.addEventListener('load', initCompareChart);
})();
</script>
