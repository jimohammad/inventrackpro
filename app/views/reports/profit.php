<!-- Profit & Loss Report -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Profit & Loss</h1>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-printer me-1"></i> Print</button>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="profit">
            <div class="col-6 col-md-3"><label class="form-label mb-1">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label mb-1">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>"></div>
            <div class="col-6 col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100 mt-3">Generate</button></div>
        </form>
    </div>
</div>


<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">P&L Summary</div>
            <div class="card-body">
                <?php
                $rows = [
                    ['Sales Revenue', $salesRev, 'var(--success)'],
                    ['Cost of Goods Sold', $cogs, 'var(--danger)'],
                    ['Gross Profit', $grossProfit, $grossProfit >= 0 ? 'var(--success)':'var(--danger)'],
                    ['Expenses', $expenses, 'var(--danger)'],
                    ['Net Profit', $netProfit, $netProfit >= 0 ? 'var(--success)':'var(--danger)'],
                ];
                ?>
                <?php foreach ($rows as [$label, $val, $color]): ?>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);"><?= $label ?></span>
                    <span style="font-weight:700;color:<?= $color ?>;"><?= APP_CURRENCY ?> <?= number_format($val, DECIMAL_PLACES) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Daily Revenue vs Cost</div>
            <div class="card-body">
                <canvas id="plChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const plCtx = document.getElementById('plChart').getContext('2d');
const plData = <?= json_encode($dailyData) ?>;
new Chart(plCtx, {
    type: 'line',
    data: {
        labels: plData.map(d => d.date),
        datasets: [
            { label: 'Revenue', data: plData.map(d => d.revenue), borderColor: '#10b981', fill: false, tension: 0.3 },
            { label: 'Cost',    data: plData.map(d => d.cost),    borderColor: '#ef4444', fill: false, tension: 0.3 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#94a3b8' } } },
        scales: {
            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.5)' } },
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.5)' }, beginAtZero: true }
        }
    }
});
</script>
