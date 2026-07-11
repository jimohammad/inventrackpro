<?php
$poPrintQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate);
if ($supplierId) {
    $poPrintQs .= '&supplier_id=' . (int) $supplierId;
}
if ($status !== '') {
    $poPrintQs .= '&status=' . urlencode((string) $status);
}
if ($currency !== '') {
    $poPrintQs .= '&currency=' . urlencode((string) $currency);
}
if ($search !== '') {
    $poPrintQs .= '&search=' . urlencode((string) $search);
}
$poPrintUrl = '?page=reports&action=purchaseOrdersPrint&' . $poPrintQs;

$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));

$statusConfig = [
    'draft'     => ['bg' => '#eef2ff', 'color' => '#4338ca', 'label' => 'Draft',             'icon' => 'bi-pencil-square'],
    'paid'      => ['bg' => '#fffbeb', 'color' => '#b45309', 'label' => 'Paid — Awaiting',   'icon' => 'bi-hourglass-split'],
    'converted' => ['bg' => '#ecfdf5', 'color' => '#047857', 'label' => 'Converted',         'icon' => 'bi-check-circle'],
    'cancelled' => ['bg' => '#f8fafc', 'color' => '#94a3b8', 'label' => 'Cancelled',         'icon' => 'bi-x-circle'],
];

$currencyColors = [
    'AED' => ['#dbeafe', '#1d4ed8'],
    'USD' => ['#fef9c3', '#854d0e'],
    'KWD' => ['#fce7f3', '#be185d'],
];
?>

<style>
.por { --por-accent: #f59e0b; --por-accent-dark: #d97706; --por-indigo: #6366f1; max-width: 1200px; }

.por-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.por-top-left { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
.por-back {
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    text-decoration: none;
    flex-shrink: 0;
    transition: all 0.15s;
}
.por-back:hover { border-color: var(--por-accent); color: var(--por-accent); background: rgba(245,158,11,0.06); }
.por-title { font-size: 1.45rem; font-weight: 800; color: var(--text-main); margin: 0 0 4px; letter-spacing: -0.02em; }
.por-sub { font-size: 0.82rem; color: var(--text-muted); margin: 0; }
.por-period {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 8px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(245,158,11,0.12);
    color: #b45309;
    font-size: 0.72rem;
    font-weight: 700;
}
.por-actions { display: flex; flex-wrap: wrap; gap: 8px; }

.por-filters {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fde68a;
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 20px;
}
.por-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    align-items: end;
}
.por-flabel {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #b45309;
    margin-bottom: 5px;
}
.por-filters .form-control,
.por-filters .form-select {
    border: 1.5px solid #fde68a;
    border-radius: 10px;
    font-size: 0.85rem;
    background: #fff;
}
.por-filters .form-control:focus,
.por-filters .form-select:focus {
    border-color: var(--por-accent);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
.por-filter-actions { display: flex; gap: 8px; }
.por-btn-go {
    flex: 1;
    padding: 8px 16px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(245,158,11,0.35);
    transition: transform 0.15s;
}
.por-btn-go:hover { transform: translateY(-1px); }
.por-btn-clear {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1.5px solid #fde68a;
    background: #fff;
    color: #64748b;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.por-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 992px) { .por-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .por-kpis { grid-template-columns: 1fr; } }

.por-kpi {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    overflow: hidden;
}
.por-kpi::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--kpi-color, var(--por-accent));
}
.por-kpi-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    background: var(--kpi-bg, rgba(245,158,11,0.12));
    color: var(--kpi-color, var(--por-accent));
    flex-shrink: 0;
}
.por-kpi-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin: 0 0 2px; }
.por-kpi-value { font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0; line-height: 1.2; }
.por-kpi-sub { font-size: 0.68rem; color: var(--text-muted); margin: 2px 0 0; }

.por-insights {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
@media (max-width: 768px) { .por-insights { grid-template-columns: 1fr; } }

.por-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 16px 18px;
}
.por-panel-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-muted);
    margin: 0 0 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.por-bar-row { margin-bottom: 12px; }
.por-bar-row:last-child { margin-bottom: 0; }
.por-bar-head { display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; margin-bottom: 5px; }
.por-bar-name { font-weight: 700; color: var(--text-main); }
.por-bar-amt { font-weight: 700; color: var(--por-accent); }
.por-bar-track { height: 7px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.por-bar-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
.por-bar-meta { font-size: 0.68rem; color: var(--text-muted); margin-top: 3px; }

.por-status-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.por-chip {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    border: 1px solid transparent;
}
.por-chip-count { font-size: 1.1rem; font-weight: 800; }

.por-list-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    gap: 12px;
    flex-wrap: wrap;
}
.por-list-title { font-size: 0.85rem; font-weight: 800; color: var(--text-main); margin: 0; }
.por-list-meta { font-size: 0.75rem; color: var(--text-muted); }
.por-expand-all {
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s;
}
.por-expand-all:hover { border-color: var(--por-accent); color: var(--por-accent); }

.por-po {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow 0.2s, border-color 0.2s;
}
.por-po:hover { border-color: #fde68a; box-shadow: 0 4px 16px rgba(245,158,11,0.08); }
.por-po.is-open { border-color: #fcd34d; box-shadow: 0 6px 20px rgba(245,158,11,0.12); }

.por-po-head {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    cursor: pointer;
    user-select: none;
}
@media (max-width: 768px) {
    .por-po-head { grid-template-columns: auto 1fr; }
    .por-po-amts { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; }
}

.por-po-chevron {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted);
    transition: transform 0.2s, background 0.2s;
    flex-shrink: 0;
}
.por-po.is-open .por-po-chevron { transform: rotate(90deg); background: rgba(245,158,11,0.12); color: var(--por-accent); }

.por-po-main { min-width: 0; }
.por-po-no {
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--por-indigo);
    text-decoration: none;
}
.por-po-no:hover { text-decoration: underline; }
.por-po-supplier { font-weight: 700; font-size: 0.9rem; color: var(--text-main); margin-top: 2px; }
.por-po-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; align-items: center; }
.por-po-date { font-size: 0.75rem; color: var(--text-muted); }
.por-po-ref { font-size: 0.72rem; color: var(--text-muted); font-family: monospace; }

.por-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 700;
}
.por-badge-cur { background: rgba(245,158,11,0.15); color: #b45309; }
.por-badge-items { background: rgba(99,102,241,0.1); color: #4338ca; }

.por-po-amts { text-align: right; flex-shrink: 0; }
.por-po-kwd { font-size: 1rem; font-weight: 800; color: var(--text-main); }
.por-po-foreign { font-size: 0.78rem; color: var(--por-accent); font-weight: 700; margin-top: 2px; }
.por-po-paid { font-size: 0.72rem; color: #059669; font-weight: 600; margin-top: 2px; }

.por-po-body {
    display: none;
    border-top: 1px solid var(--border-color);
    background: linear-gradient(180deg, #fffbeb 0%, var(--bg-card) 40px);
}
.por-po.is-open .por-po-body { display: block; }

.por-items-wrap { padding: 0 16px 14px; overflow-x: auto; }
.por-items {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    margin-top: 12px;
}
.por-items thead th {
    padding: 8px 10px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #b45309;
    background: rgba(245,158,11,0.08);
    border-bottom: 2px solid #fde68a;
    text-align: left;
    white-space: nowrap;
}
.por-items thead th.num { text-align: right; }
.por-items tbody td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}
.por-items tbody tr:last-child td { border-bottom: none; }
.por-items tbody tr:hover { background: rgba(245,158,11,0.04); }
.por-items tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
.por-item-name { font-weight: 700; color: var(--text-main); }
.por-item-sku { font-size: 0.68rem; color: var(--text-muted); font-family: monospace; margin-top: 1px; }
.por-items tfoot td {
    padding: 8px 10px;
    font-weight: 700;
    font-size: 0.78rem;
    background: rgba(245,158,11,0.06);
    border-top: 2px solid #fde68a;
}
.por-items tfoot td.num { text-align: right; }

.por-po-footer {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 16px 14px;
    border-top: 1px dashed var(--border-color);
    align-items: center;
}
.por-po-footer a {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--por-indigo);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.por-po-footer a:hover { text-decoration: underline; }

.por-empty {
    text-align: center;
    padding: 56px 24px;
    background: var(--bg-card);
    border: 1px dashed var(--border-color);
    border-radius: 16px;
}
.por-empty-icon { font-size: 3rem; color: #cbd5e1; }
.por-empty p { color: var(--text-muted); margin: 12px 0 0; }

.por-export-table { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
</style>

<div class="por no-print">

    <!-- Header -->
    <div class="por-top">
        <div class="por-top-left">
            <a href="?page=reports" class="por-back" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="por-title">Purchase Orders Report</h1>
                <p class="por-sub">Track PO value, prepaid amounts, and line-item details</p>
                <span class="por-period"><i class="bi bi-calendar3"></i> <?= htmlspecialchars($periodLabel) ?></span>
            </div>
        </div>
        <?php if (!empty($orders)): ?>
        <div class="por-actions">
            <button type="button" class="btn btn-success btn-sm js-export-report-csv" data-table-id="poRptTable" data-title="Purchase_Orders_Report">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
            <a href="<?= htmlspecialchars($poPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <a href="<?= htmlspecialchars($poPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="por-filters">
        <form method="GET">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="purchaseOrders">
            <div class="por-filters-grid">
                <div>
                    <label class="por-flabel"><i class="bi bi-calendar3 me-1"></i>From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>">
                </div>
                <div>
                    <label class="por-flabel"><i class="bi bi-calendar3 me-1"></i>To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>">
                </div>
                <div style="grid-column: span 2;">
                    <label class="por-flabel"><i class="bi bi-building me-1"></i>Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">All Suppliers</option>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= $supplierId === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="por-flabel"><i class="bi bi-funnel me-1"></i>Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid — Awaiting</option>
                        <option value="converted" <?= $status === 'converted' ? 'selected' : '' ?>>Converted</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="por-flabel"><i class="bi bi-currency-exchange me-1"></i>Currency</label>
                    <select name="currency" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="KWD" <?= $currency === 'KWD' ? 'selected' : '' ?>>KWD</option>
                        <option value="AED" <?= $currency === 'AED' ? 'selected' : '' ?>>AED</option>
                        <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label class="por-flabel"><i class="bi bi-search me-1"></i>Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="PO no, supplier, ref..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="por-filter-actions" style="grid-column: span 2;">
                    <button type="submit" class="por-btn-go"><i class="bi bi-search me-1"></i> Generate Report</button>
                    <a href="?page=reports&action=purchaseOrders" class="por-btn-clear"><i class="bi bi-x-circle me-1"></i> Clear</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($orders)): ?>

    <!-- KPIs -->
    <div class="por-kpis">
        <div class="por-kpi" style="--kpi-color:#f59e0b;--kpi-bg:rgba(245,158,11,0.12);">
            <div class="por-kpi-icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <p class="por-kpi-label">Total POs</p>
                <p class="por-kpi-value"><?= (int) $summary['count'] ?></p>
                <p class="por-kpi-sub"><?= (int) ($summary['statusCounts']['converted'] ?? 0) ?> converted</p>
            </div>
        </div>
        <div class="por-kpi" style="--kpi-color:#6366f1;--kpi-bg:rgba(99,102,241,0.12);">
            <div class="por-kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <p class="por-kpi-label">KWD Value</p>
                <p class="por-kpi-value"><?= number_format((float) $summary['totalKwd'], DECIMAL_PLACES) ?></p>
                <p class="por-kpi-sub">Excl. cancelled</p>
            </div>
        </div>
        <div class="por-kpi" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.12);">
            <div class="por-kpi-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <p class="por-kpi-label">Prepaid</p>
                <p class="por-kpi-value"><?= number_format((float) $summary['totalPaidKwd'], DECIMAL_PLACES) ?></p>
                <p class="por-kpi-sub">KWD paid to suppliers</p>
            </div>
        </div>
        <div class="por-kpi" style="--kpi-color:#b45309;--kpi-bg:rgba(180,83,9,0.12);">
            <div class="por-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <p class="por-kpi-label">Awaiting Goods</p>
                <p class="por-kpi-value"><?= (int) ($summary['statusCounts']['paid'] ?? 0) ?></p>
                <p class="por-kpi-sub"><?= (int) ($summary['statusCounts']['draft'] ?? 0) ?> still draft</p>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <div class="por-insights">
        <?php if (!empty($summary['byCurrency'])): ?>
        <div class="por-panel">
            <p class="por-panel-title"><i class="bi bi-pie-chart"></i> By Currency</p>
            <?php foreach ($summary['byCurrency'] as $cur):
                $pct = $summary['totalKwd'] > 0 ? ((float) $cur['kwd_total'] / (float) $summary['totalKwd'] * 100) : 0;
                $cc = $currencyColors[$cur['currency']] ?? ['rgba(245,158,11,0.2)', '#f59e0b'];
            ?>
            <div class="por-bar-row">
                <div class="por-bar-head">
                    <span class="por-bar-name"><?= htmlspecialchars((string) $cur['currency']) ?> · <?= (int) $cur['count'] ?> POs</span>
                    <span class="por-bar-amt"><?= number_format((float) $cur['kwd_total'], DECIMAL_PLACES) ?> KWD</span>
                </div>
                <div class="por-bar-track">
                    <div class="por-bar-fill" style="width:<?= round($pct) ?>%;background:linear-gradient(90deg,<?= $cc[1] ?>,<?= $cc[0] ?>);"></div>
                </div>
                <div class="por-bar-meta">
                    <?= number_format((float) $cur['foreign_total'], DECIMAL_PLACES) ?> <?= htmlspecialchars((string) $cur['currency']) ?> foreign · <?= number_format($pct, 1) ?>% of KWD total
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="por-panel">
            <p class="por-panel-title"><i class="bi bi-diagram-3"></i> By Status</p>
            <div class="por-status-chips">
                <?php foreach ($statusConfig as $key => $sc):
                    $cnt = (int) ($summary['statusCounts'][$key] ?? 0);
                    if (!$cnt) continue;
                ?>
                <div class="por-chip" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;border-color:<?= $sc['color'] ?>22;">
                    <i class="bi <?= $sc['icon'] ?>"></i>
                    <span><?= $sc['label'] ?></span>
                    <span class="por-chip-count"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- PO list -->
    <div class="por-list-head">
        <div>
            <p class="por-list-title">Purchase Orders</p>
            <p class="por-list-meta">Click a row to expand item details · <?= (int) $summary['count'] ?> records</p>
        </div>
        <button type="button" class="por-expand-all" id="porExpandAll" data-state="collapsed">
            <i class="bi bi-arrows-expand me-1"></i> Expand all
        </button>
    </div>

    <div id="porList">
        <?php foreach ($orders as $o):
            $poId         = (int) $o['id'];
            $items        = $itemsByPo[$poId] ?? [];
            $st           = $statusConfig[$o['status']] ?? $statusConfig['draft'];
            $otherCharges = (float) ($o['other_charges_kwd'] ?? 0);
            $kwdTotal     = (float) $o['subtotal_kwd'] + $otherCharges;
            $cur          = (string) ($o['currency'] ?? 'KWD');
        ?>
        <div class="por-po" data-po-id="<?= $poId ?>">
            <div class="por-po-head por-po-toggle" role="button" tabindex="0" aria-expanded="false">
                <div class="por-po-chevron"><i class="bi bi-chevron-right"></i></div>
                <div class="por-po-main">
                    <a href="?page=purchaseorders&action=show&id=<?= $poId ?>" class="por-po-no" onclick="event.stopPropagation();">
                        <?= htmlspecialchars($o['po_no']) ?>
                    </a>
                    <div class="por-po-supplier"><?= htmlspecialchars($o['supplier_name']) ?></div>
                    <div class="por-po-meta">
                        <span class="por-po-date"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($o['date'])) ?></span>
                        <?php if (!empty($o['supplier_ref'])): ?>
                        <span class="por-po-ref"><?= htmlspecialchars($o['supplier_ref']) ?></span>
                        <?php endif; ?>
                        <span class="por-badge por-badge-cur"><?= htmlspecialchars($cur) ?></span>
                        <span class="por-badge por-badge-items"><?= (int) $o['item_count'] ?> items · <?= (int) $o['total_qty'] ?> qty</span>
                        <span class="por-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                            <i class="bi <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                        </span>
                    </div>
                </div>
                <div class="por-po-amts">
                    <div class="por-po-kwd"><?= number_format($kwdTotal, DECIMAL_PLACES) ?> KWD</div>
                    <div class="por-po-foreign"><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($cur) ?></div>
                    <?php if ((float) ($o['paid_kwd'] ?? 0) > 0): ?>
                    <div class="por-po-paid"><i class="bi bi-check-circle me-1"></i>Paid <?= number_format((float) $o['paid_kwd'], DECIMAL_PLACES) ?> KWD</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="por-po-body">
                <?php if (empty($items)): ?>
                <div class="por-items-wrap"><p class="text-muted small py-3 mb-0">No line items.</p></div>
                <?php else: ?>
                <div class="por-items-wrap">
                    <table class="por-items">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th class="num">Qty</th>
                                <th class="num">Unit (<?= htmlspecialchars($cur) ?>)</th>
                                <th class="num">Total (<?= htmlspecialchars($cur) ?>)</th>
                                <th class="num">Unit KWD</th>
                                <th class="num">Total KWD</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $n => $item):
                            $qty = (int) ($item['quantity'] ?? 0);
                            $unitKwd = (float) ($item['unit_price_kwd'] ?? 0);
                            if ($unitKwd <= 0 && (float) ($item['total_kwd'] ?? 0) > 0 && $qty > 0) {
                                $unitKwd = round((float) $item['total_kwd'] / $qty, 3);
                            }
                        ?>
                        <tr>
                            <td><?= $n + 1 ?></td>
                            <td>
                                <div class="por-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if (!empty($item['sku'])): ?>
                                <div class="por-item-sku"><?= htmlspecialchars($item['sku']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= $qty ?></td>
                            <td class="num"><?= number_format((float) $item['unit_price_foreign'], DECIMAL_PLACES) ?></td>
                            <td class="num" style="color:#f59e0b;font-weight:700;"><?= number_format((float) $item['total_foreign'], DECIMAL_PLACES) ?></td>
                            <td class="num"><?= $unitKwd > 0 ? number_format($unitKwd, DECIMAL_PLACES) : '—' ?></td>
                            <td class="num" style="color:#6366f1;font-weight:700;"><?= number_format((float) $item['total_kwd'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Subtotal</td>
                                <td class="num" style="color:#f59e0b;"><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($cur) ?></td>
                                <td></td>
                                <td class="num" style="color:#6366f1;"><?= number_format((float) $o['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</td>
                            </tr>
                            <?php if ($otherCharges > 0.001): ?>
                            <tr>
                                <td colspan="6">Other Charges</td>
                                <td class="num"><?= number_format($otherCharges, DECIMAL_PLACES) ?> KWD</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="4"><strong>PO Total</strong></td>
                                <td class="num" style="color:#f59e0b;"><strong><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($cur) ?></strong></td>
                                <td></td>
                                <td class="num" style="color:#6366f1;"><strong><?= number_format($kwdTotal, DECIMAL_PLACES) ?> KWD</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>

                <div class="por-po-footer">
                    <a href="?page=purchaseorders&action=show&id=<?= $poId ?>"><i class="bi bi-eye"></i> View PO</a>
                    <?php if (!empty($o['converted_invoice_no'])): ?>
                    <a href="?page=purchases&action=detail&id=<?= (int) $o['converted_to'] ?>"><i class="bi bi-receipt"></i> <?= htmlspecialchars($o['converted_invoice_no']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Hidden table for Excel export -->
    <table id="poRptTable" class="por-export-table">
        <thead>
            <tr>
                <th>PO No</th><th>Date</th><th>Supplier</th><th>Ref</th><th>Currency</th>
                <th>Foreign</th><th>KWD</th><th>Paid KWD</th><th>Items</th><th>Qty</th><th>Status</th><th>Invoice</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o):
            $kwdTotal = (float) $o['subtotal_kwd'] + (float) ($o['other_charges_kwd'] ?? 0);
            $st = $statusConfig[$o['status']]['label'] ?? ucfirst((string) $o['status']);
        ?>
        <tr>
            <td><?= htmlspecialchars($o['po_no']) ?></td>
            <td><?= $o['date'] ?></td>
            <td><?= htmlspecialchars($o['supplier_name']) ?></td>
            <td><?= htmlspecialchars($o['supplier_ref'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['currency']) ?></td>
            <td><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?></td>
            <td><?= number_format($kwdTotal, DECIMAL_PLACES) ?></td>
            <td><?= number_format((float) ($o['paid_kwd'] ?? 0), DECIMAL_PLACES) ?></td>
            <td><?= (int) $o['item_count'] ?></td>
            <td><?= (int) $o['total_qty'] ?></td>
            <td><?= $st ?></td>
            <td><?= htmlspecialchars($o['converted_invoice_no'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>

    <div class="por-empty">
        <div class="por-empty-icon"><i class="bi bi-inbox"></i></div>
        <p>No purchase orders found for the selected filters.</p>
        <a href="?page=reports&action=purchaseOrders" class="btn btn-sm btn-outline-secondary mt-2">Reset filters</a>
    </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('porList');
    if (!list) return;

    function setPoOpen(card, open) {
        card.classList.toggle('is-open', open);
        var head = card.querySelector('.por-po-head');
        if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    list.querySelectorAll('.por-po-toggle').forEach(function (head) {
        head.addEventListener('click', function () {
            var card = head.closest('.por-po');
            if (card) setPoOpen(card, !card.classList.contains('is-open'));
        });
        head.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var card = head.closest('.por-po');
                if (card) setPoOpen(card, !card.classList.contains('is-open'));
            }
        });
    });

    var expandBtn = document.getElementById('porExpandAll');
    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            var expand = expandBtn.getAttribute('data-state') !== 'expanded';
            list.querySelectorAll('.por-po').forEach(function (card) {
                setPoOpen(card, expand);
            });
            expandBtn.setAttribute('data-state', expand ? 'expanded' : 'collapsed');
            expandBtn.innerHTML = expand
                ? '<i class="bi bi-arrows-collapse me-1"></i> Collapse all'
                : '<i class="bi bi-arrows-expand me-1"></i> Expand all';
        });
    }
});
</script>
