<?php
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$cmp = static function (float $current, float $previous) use ($money): array {
    $delta = round($current - $previous, 3);
    if (abs($delta) < 0.001) {
        return ['delta' => 0.0, 'class' => 'flat', 'label' => '—'];
    }
    $pct = abs($previous) > 0.001 ? ($delta / abs($previous)) * 100 : null;
    return [
        'delta' => $delta,
        'class' => $delta > 0 ? 'up' : 'down',
        'label' => ($delta > 0 ? '+' : '') . $money($delta) . ($pct !== null ? ' (' . ($delta > 0 ? '+' : '') . number_format($pct, 1) . '%)' : ''),
    ];
};
$asOfLabel     = date('d M Y', strtotime($date));
$printUrl      = '?page=reports&action=balanceSheetPrint&as_of=' . urlencode((string) $date);
$totalEquity   = $netWorth;
$liabPlusEquity = round($totalLiabilities + $totalEquity, 3);
$isBalanced    = abs($totalAssets - $liabPlusEquity) < 0.01;
$currentAssets = round($totalCash + $totalReceivable + $stockVal + $totalPoAdvances, 3);
$cmpCurrentAssets = round(
    (float) ($compareSnapshot['total_cash'] ?? 0)
    + (float) ($compareSnapshot['total_receivable'] ?? 0)
    + (float) ($compareSnapshot['stock_val'] ?? 0)
    + (float) ($compareSnapshot['total_po_advances'] ?? 0),
    3
);
$cmpAssets     = $cmp($totalAssets, (float) ($compareSnapshot['total_assets'] ?? 0));
$cmpLiab       = $cmp($totalLiabilities, (float) ($compareSnapshot['total_liabilities'] ?? 0));
$cmpEquity     = $cmp($totalEquity, (float) ($compareSnapshot['net_worth'] ?? 0));
?>
<style>
.bs-report { max-width: 1100px; margin: 0 auto; }
.bs-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.bs-toolbar-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bs-filter-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; }
.bs-filter-row { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.bs-filter-row label { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.35px; margin-bottom: 4px; display: block; }
.bs-filter-row input[type="date"] { border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 10px; font-size: 0.85rem; background: var(--bg-main); color: var(--text-main); min-width: 150px; }

.bs-meta-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; background: rgba(14, 116, 144, 0.06); border: 1px solid rgba(14, 116, 144, 0.15); border-radius: 8px; margin-bottom: 14px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; }
.bs-meta-bar strong { color: var(--text-main); }
.bs-meta-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: var(--bg-card); border: 1px solid var(--border-color); font-weight: 600; }
.bs-meta-pill.ok { color: #059669; border-color: rgba(16, 185, 129, 0.35); background: rgba(16, 185, 129, 0.08); }
.bs-meta-pill.warn { color: #b45309; border-color: rgba(245, 158, 11, 0.35); background: rgba(245, 158, 11, 0.08); }

.bs-table-wrap { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
.bs-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.bs-table thead th { background: #0e7490; color: #fff; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; padding: 10px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.15); }
.bs-table thead th.num { text-align: right; }
.bs-table tbody tr { border-bottom: 1px solid var(--border-color); }
.bs-table tbody tr:last-child { border-bottom: none; }
.bs-table td { padding: 8px 14px; vertical-align: middle; }
.bs-table td.num { text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600; white-space: nowrap; }
.bs-table td.num.muted { color: var(--text-muted); font-weight: 500; }
.bs-table td.num.pos { color: #059669; }
.bs-table td.num.neg { color: #dc2626; }

.bs-row-section td { background: rgba(14, 116, 144, 0.08); font-weight: 800; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.45px; color: #0e7490; padding-top: 12px; padding-bottom: 12px; }
.bs-row-group td { background: rgba(0,0,0,0.02); font-weight: 700; }
[data-theme="dark"] .bs-row-group td { background: rgba(255,255,255,0.03); }
.bs-row-subgroup td { font-weight: 700; color: var(--text-main); }
.bs-row-total td { font-weight: 800; border-top: 2px solid var(--border-color); background: rgba(0,0,0,0.015); }
[data-theme="dark"] .bs-row-total td { background: rgba(255,255,255,0.02); }
.bs-row-grand td { font-weight: 800; font-size: 0.92rem; border-top: 3px double var(--border-color); background: rgba(14, 116, 144, 0.06); }
.bs-row-check td { font-weight: 700; background: rgba(16, 185, 129, 0.06); color: #047857; }
.bs-row-check.unbalanced td { background: rgba(239, 68, 68, 0.06); color: #dc2626; }
.bs-row-memo td { font-size: 0.78rem; color: var(--text-muted); font-style: italic; background: rgba(99, 102, 241, 0.04); }

.bs-name { display: flex; align-items: center; gap: 6px; min-width: 0; }
.bs-indent-0 { padding-left: 14px; }
.bs-indent-1 { padding-left: 28px; }
.bs-indent-2 { padding-left: 42px; }
.bs-indent-3 { padding-left: 56px; }
.bs-toggle { width: 22px; height: 22px; border: none; background: transparent; color: var(--text-muted); border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 0; }
.bs-toggle:hover { background: rgba(14, 116, 144, 0.1); color: #0e7490; }
.bs-toggle .bi { transition: transform 0.15s ease; font-size: 0.75rem; }
.bs-toggle[aria-expanded="true"] .bi { transform: rotate(90deg); }
.bs-toggle-spacer { width: 22px; flex-shrink: 0; }
.bs-line-meta { font-size: 0.72rem; color: var(--text-muted); margin-left: 4px; }
.bs-line-code { font-family: monospace; font-size: 0.72rem; color: #6366f1; font-weight: 700; margin-right: 4px; }

.bs-change { font-size: 0.72rem; font-weight: 700; margin-top: 2px; }
.bs-change.up { color: #059669; }
.bs-change.down { color: #dc2626; }
.bs-change.flat { color: var(--text-muted); }

.bs-footnote { margin-top: 12px; font-size: 0.75rem; color: var(--text-muted); line-height: 1.5; }
.bs-insights { margin-top: 20px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
.bs-insights-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; cursor: pointer; user-select: none; }
.bs-insights-head h2 { margin: 0; font-size: 0.88rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
.bs-insights-body { padding: 0 16px 16px; border-top: 1px solid var(--border-color); }
.bs-insights-body[hidden] { display: none; }
.bs-trend-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 14px; }
.bs-trend-item { border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; background: rgba(99,102,241,0.04); }
.bs-trend-item.is-current { border-color: rgba(99,102,241,0.35); background: rgba(99,102,241,0.08); }
.bs-trend-month { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
.bs-trend-val { font-size: 0.95rem; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: #6366f1; }
.bs-snap-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.bs-snap-form input { border: 1px solid var(--border-color); border-radius: 8px; padding: 5px 10px; font-size: 0.8rem; background: var(--bg-main); color: var(--text-main); }

@media (max-width: 768px) {
    .bs-table thead .bs-col-compare { display: none; }
    .bs-table tbody .bs-col-compare { display: none; }
    .bs-trend-grid { grid-template-columns: repeat(2, 1fr); }
}
@media print {
    .no-print { display: none !important; }
    .bs-report { max-width: none; }
    .bs-table-wrap { border: none; }
    .bs-insights { display: none; }
    .bs-row-detail { display: table-row !important; }
}
</style>

<div class="bs-report">
    <div class="bs-toolbar no-print">
        <div>
            <h1 class="page-title">Balance Sheet</h1>
            <p class="page-subtitle">Statement of financial position — Assets = Liabilities + Equity</p>
        </div>
        <div class="bs-toolbar-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="bsExpandAll" data-state="collapsed">
                <i class="bi bi-arrows-expand me-1"></i> Expand all
            </button>
            <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print
            </a>
            <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <div class="bs-filter-card no-print">
        <form method="GET" class="bs-filter-row">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="balanceSheet">
            <div>
                <label for="bsAsOf">As of date</label>
                <input type="date" id="bsAsOf" name="as_of" value="<?= htmlspecialchars((string) $date) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Update report</button>
            </div>
        </form>
    </div>

    <div class="bs-meta-bar">
        <div>
            <strong>Reporting date:</strong> <?= htmlspecialchars($asOfLabel) ?>
            &nbsp;·&nbsp;
            <strong>Comparison:</strong> <?= htmlspecialchars($compareLabel) ?> (prior month-end)
        </div>
        <div class="bs-meta-pill <?= $isBalanced ? 'ok' : 'warn' ?>">
            <i class="bi bi-<?= $isBalanced ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
            <?= $isBalanced ? 'Balanced' : 'Out of balance' ?>
            — <?= $money($totalAssets) ?> = <?= $money($liabPlusEquity) ?>
        </div>
    </div>

    <div class="bs-table-wrap">
        <table class="bs-table" id="bsReportTable">
            <thead>
                <tr>
                    <th style="width:55%;">Account</th>
                    <th class="num" style="width:22%;"><?= htmlspecialchars($asOfLabel) ?></th>
                    <th class="num bs-col-compare" style="width:23%;"><?= htmlspecialchars($compareLabel) ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- ASSETS -->
                <tr class="bs-row-section">
                    <td colspan="3"><i class="bi bi-arrow-up-circle me-1"></i> Assets</td>
                </tr>
                <tr class="bs-row-group">
                    <td class="bs-indent-1">Current Assets</td>
                    <td class="num"></td>
                    <td class="num bs-col-compare muted"></td>
                </tr>

                <!-- Cash & Bank -->
                <tr class="bs-row-subgroup" data-bs-group="cash">
                    <td class="bs-indent-2">
                        <div class="bs-name">
                            <button type="button" class="bs-toggle" aria-expanded="false" aria-controls="bs-detail-cash" data-bs-target="bs-detail-cash">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            Cash &amp; Bank
                        </div>
                    </td>
                    <td class="num"><?= $money($totalCash) ?></td>
                    <td class="num bs-col-compare"><?= $money((float) ($compareSnapshot['total_cash'] ?? 0)) ?></td>
                </tr>
                <?php if (empty($accounts)): ?>
                <tr class="bs-row-detail bs-row-memo" data-bs-parent="cash" hidden>
                    <td class="bs-indent-3">No accounts</td>
                    <td class="num muted">—</td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php else: ?>
                <?php foreach ($accounts as $a):
                    $acctBal = (float) ($a['balance_as_of'] ?? $a['current_balance']);
                ?>
                <tr class="bs-row-detail" data-bs-parent="cash" hidden>
                    <td class="bs-indent-3">
                        <span class="bs-line-code">#<?= (int) $a['id'] ?></span>
                        <?= htmlspecialchars($a['name']) ?>
                        <span class="bs-line-meta"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $a['type']))) ?></span>
                    </td>
                    <td class="num <?= $acctBal >= 0 ? 'pos' : 'neg' ?>"><?= $money($acctBal) ?></td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Receivables -->
                <tr class="bs-row-subgroup" data-bs-group="recv">
                    <td class="bs-indent-2">
                        <div class="bs-name">
                            <button type="button" class="bs-toggle" aria-expanded="false" aria-controls="bs-detail-recv" data-bs-target="bs-detail-recv">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            Accounts Receivable
                        </div>
                    </td>
                    <td class="num"><?= $money($totalReceivable) ?></td>
                    <td class="num bs-col-compare"><?= $money((float) ($compareSnapshot['total_receivable'] ?? 0)) ?></td>
                </tr>
                <?php if (empty($receivables)): ?>
                <tr class="bs-row-detail bs-row-memo" data-bs-parent="recv" hidden>
                    <td class="bs-indent-3">No outstanding receivables</td>
                    <td class="num muted">—</td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php else: ?>
                <?php foreach ($receivables as $r): ?>
                <tr class="bs-row-detail" data-bs-parent="recv" hidden>
                    <td class="bs-indent-3">
                        <?= htmlspecialchars($r['name']) ?>
                        <?php if (!empty($r['party_code'])): ?>
                        <span class="bs-line-meta"><?= htmlspecialchars((string) $r['party_code']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num pos"><?= $money((float) $r['balance']) ?></td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Inventory -->
                <tr class="bs-row-subgroup">
                    <td class="bs-indent-2">
                        <div class="bs-name"><span class="bs-toggle-spacer"></span> Inventory (at cost)</div>
                    </td>
                    <td class="num"><?= $money($stockVal) ?></td>
                    <td class="num bs-col-compare"><?= $money((float) ($compareSnapshot['stock_val'] ?? 0)) ?></td>
                </tr>

                <!-- Supplier advances (PO prepayments) -->
                <tr class="bs-row-subgroup" data-bs-group="poadv">
                    <td class="bs-indent-2">
                        <div class="bs-name">
                            <?php if (!empty($poAdvances)): ?>
                            <button type="button" class="bs-toggle" aria-expanded="false" aria-controls="bs-detail-poadv" data-bs-target="bs-detail-poadv">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <?php else: ?>
                            <span class="bs-toggle-spacer"></span>
                            <?php endif; ?>
                            Prepaid Supplier Advances (PO)
                        </div>
                    </td>
                    <td class="num"><?= $money($totalPoAdvances) ?></td>
                    <td class="num bs-col-compare"><?= $money((float) ($compareSnapshot['total_po_advances'] ?? 0)) ?></td>
                </tr>
                <?php if (empty($poAdvances)): ?>
                <tr class="bs-row-detail bs-row-memo" data-bs-parent="poadv" hidden>
                    <td class="bs-indent-3">No PO prepayments awaiting goods</td>
                    <td class="num muted">—</td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php else: ?>
                <?php foreach ($poAdvances as $adv): ?>
                <tr class="bs-row-detail" data-bs-parent="poadv" hidden>
                    <td class="bs-indent-3">
                        <?= htmlspecialchars($adv['name']) ?>
                        <?php if (!empty($adv['party_code'])): ?>
                        <span class="bs-line-meta"><?= htmlspecialchars((string) $adv['party_code']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num pos"><?= $money((float) $adv['amount']) ?></td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <tr class="bs-row-total">
                    <td class="bs-indent-1">Total Current Assets</td>
                    <td class="num"><?= $money($currentAssets) ?></td>
                    <td class="num bs-col-compare"><?= $money($cmpCurrentAssets) ?></td>
                </tr>
                <tr class="bs-row-grand">
                    <td class="bs-indent-0">Total Assets</td>
                    <td class="num"><?= $money($totalAssets) ?></td>
                    <td class="num bs-col-compare">
                        <?= $money((float) ($compareSnapshot['total_assets'] ?? 0)) ?>
                        <?php if ($cmpAssets['delta'] !== 0.0): ?>
                        <div class="bs-change <?= htmlspecialchars($cmpAssets['class']) ?>"><?= htmlspecialchars($cmpAssets['label']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- LIABILITIES -->
                <tr class="bs-row-section">
                    <td colspan="3"><i class="bi bi-arrow-down-circle me-1"></i> Liabilities</td>
                </tr>
                <tr class="bs-row-group">
                    <td class="bs-indent-1">Current Liabilities</td>
                    <td class="num"></td>
                    <td class="num bs-col-compare muted"></td>
                </tr>

                <tr class="bs-row-subgroup" data-bs-group="pay">
                    <td class="bs-indent-2">
                        <div class="bs-name">
                            <button type="button" class="bs-toggle" aria-expanded="false" aria-controls="bs-detail-pay" data-bs-target="bs-detail-pay">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            Accounts Payable
                        </div>
                    </td>
                    <td class="num"><?= $money($totalPayable) ?></td>
                    <td class="num bs-col-compare"><?= $money((float) ($compareSnapshot['total_payable'] ?? 0)) ?></td>
                </tr>
                <?php if (empty($payables)): ?>
                <tr class="bs-row-detail bs-row-memo" data-bs-parent="pay" hidden>
                    <td class="bs-indent-3">No outstanding payables</td>
                    <td class="num muted">—</td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php else: ?>
                <?php foreach ($payables as $p): ?>
                <tr class="bs-row-detail" data-bs-parent="pay" hidden>
                    <td class="bs-indent-3">
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if (!empty($p['party_code'])): ?>
                        <span class="bs-line-meta"><?= htmlspecialchars((string) $p['party_code']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num neg"><?= $money((float) $p['balance']) ?></td>
                    <td class="num bs-col-compare muted">—</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <tr class="bs-row-grand">
                    <td class="bs-indent-0">Total Liabilities</td>
                    <td class="num"><?= $money($totalLiabilities) ?></td>
                    <td class="num bs-col-compare">
                        <?= $money((float) ($compareSnapshot['total_liabilities'] ?? 0)) ?>
                        <?php if ($cmpLiab['delta'] !== 0.0): ?>
                        <div class="bs-change <?= htmlspecialchars($cmpLiab['class']) ?>"><?= htmlspecialchars($cmpLiab['label']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- EQUITY -->
                <tr class="bs-row-section">
                    <td colspan="3"><i class="bi bi-pie-chart me-1"></i> Equity</td>
                </tr>
                <tr class="bs-row-subgroup">
                    <td class="bs-indent-1">
                        <div class="bs-name"><span class="bs-toggle-spacer"></span> Retained Earnings / Net Worth</div>
                    </td>
                    <td class="num <?= $netWorth >= 0 ? 'pos' : 'neg' ?>"><?= $money($netWorth) ?></td>
                    <td class="num bs-col-compare <?= ($compareSnapshot['net_worth'] ?? 0) >= 0 ? 'pos' : 'neg' ?>"><?= $money((float) ($compareSnapshot['net_worth'] ?? 0)) ?></td>
                </tr>
                <tr class="bs-row-grand">
                    <td class="bs-indent-0">Total Equity</td>
                    <td class="num"><?= $money($totalEquity) ?></td>
                    <td class="num bs-col-compare">
                        <?= $money((float) ($compareSnapshot['net_worth'] ?? 0)) ?>
                        <?php if ($cmpEquity['delta'] !== 0.0): ?>
                        <div class="bs-change <?= htmlspecialchars($cmpEquity['class']) ?>"><?= htmlspecialchars($cmpEquity['label']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- BALANCE CHECK -->
                <tr class="bs-row-check<?= $isBalanced ? '' : ' unbalanced' ?>">
                    <td class="bs-indent-0"><i class="bi bi-calculator me-1"></i> Total Liabilities + Equity</td>
                    <td class="num"><?= $money($liabPlusEquity) ?></td>
                    <td class="num bs-col-compare"><?= $money(round((float) ($compareSnapshot['total_liabilities'] ?? 0) + (float) ($compareSnapshot['net_worth'] ?? 0), 3)) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="bs-footnote">
        <i class="bi bi-info-circle me-1"></i>
        Cash reflects payments sent to suppliers on open POs; the same amounts appear under <strong>Prepaid Supplier Advances</strong> until goods are received and the PO converts to a purchase.
        Inventory is valued at current item cost (not historical cost on past dates).
    </p>

    <?php if (!empty($netWorthTrend ?? [])): ?>
    <div class="bs-insights no-print" id="bsInsights">
        <div class="bs-insights-head" id="bsInsightsToggle" role="button" tabindex="0" aria-expanded="false" aria-controls="bsInsightsBody">
            <h2><i class="bi bi-graph-up-arrow" style="color:#6366f1;"></i> Net worth history <span style="font-weight:500;color:var(--text-muted);font-size:0.78rem;">(optional)</span></h2>
            <i class="bi bi-chevron-down" id="bsInsightsChevron"></i>
        </div>
        <div class="bs-insights-body" id="bsInsightsBody" hidden>
            <p style="font-size:0.78rem;color:var(--text-muted);margin:12px 0 0;line-height:1.45;">
                Month-end net worth snapshots for management review. Capture a snapshot to store exact figures instead of estimates.
            </p>
            <div class="bs-trend-grid">
                <?php foreach ($netWorthTrend as $idx => $point):
                    $isCurrent = ($idx === count($netWorthTrend) - 1);
                    $nw = (float) $point['net_worth'];
                    $chg = $point['change'] ?? null;
                ?>
                <div class="bs-trend-item<?= $isCurrent ? ' is-current' : '' ?>">
                    <div class="bs-trend-month">
                        <?= htmlspecialchars($point['label']) ?>
                        <?php if (!empty($point['recorded'])): ?>
                        <i class="bi bi-check-circle-fill" style="color:#059669;font-size:0.68rem;" title="Recorded snapshot"></i>
                        <?php elseif (!$isCurrent): ?>
                        <i class="bi bi-clock-history" style="color:var(--text-muted);font-size:0.68rem;" title="Estimated"></i>
                        <?php endif; ?>
                    </div>
                    <div class="bs-trend-val" style="<?= $nw < 0 ? 'color:#dc2626;' : '' ?>"><?= $money($nw) ?></div>
                    <?php if ($chg !== null && abs($chg) > 0.001): ?>
                    <div class="bs-change <?= $chg > 0 ? 'up' : 'down' ?>">
                        <?= $chg > 0 ? '+' : '' ?><?= $money($chg) ?>
                        <?php if (isset($point['change_pct']) && $point['change_pct'] !== null): ?>
                        (<?= $chg > 0 ? '+' : '' ?><?= number_format($point['change_pct'], 1) ?>%)
                        <?php endif; ?>
                    </div>
                    <?php elseif ($chg === null): ?>
                    <div class="bs-change flat">Starting point</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php $defaultSnap = (new DateTimeImmutable('first day of this month'))->modify('-1 day')->format('Y-m-d'); ?>
            <form class="bs-snap-form" method="POST"
                  action="?page=reports&amp;action=captureNetWorth&amp;as_of=<?= urlencode((string) $date) ?>">
                <?= Auth::csrfField() ?>
                <label style="font-size:0.78rem;color:var(--text-muted);">Record month-end snapshot:</label>
                <input type="date" name="snapshot_date" value="<?= htmlspecialchars($defaultSnap) ?>" max="<?= date('Y-m-d') ?>" required>
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-camera me-1"></i> Capture</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var table = document.getElementById('bsReportTable');
    if (!table) return;

    function setGroupExpanded(groupKey, expanded) {
        table.querySelectorAll('[data-bs-parent="' + groupKey + '"]').forEach(function (row) {
            row.hidden = !expanded;
        });
        table.querySelectorAll('[data-bs-target="' + groupKey + '"]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }

    table.querySelectorAll('.bs-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-bs-target');
            var expanded = btn.getAttribute('aria-expanded') !== 'true';
            setGroupExpanded(key, expanded);
        });
    });

    var expandBtn = document.getElementById('bsExpandAll');
    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            var expand = expandBtn.getAttribute('data-state') === 'collapsed';
            table.querySelectorAll('.bs-toggle').forEach(function (btn) {
                setGroupExpanded(btn.getAttribute('data-bs-target'), expand);
            });
            expandBtn.setAttribute('data-state', expand ? 'expanded' : 'collapsed');
            expandBtn.innerHTML = expand
                ? '<i class="bi bi-arrows-collapse me-1"></i> Collapse all'
                : '<i class="bi bi-arrows-expand me-1"></i> Expand all';
        });
    }

    var insightsToggle = document.getElementById('bsInsightsToggle');
    var insightsBody = document.getElementById('bsInsightsBody');
    var insightsChevron = document.getElementById('bsInsightsChevron');
    if (insightsToggle && insightsBody) {
        function toggleInsights() {
            var open = insightsBody.hasAttribute('hidden');
            if (open) {
                insightsBody.removeAttribute('hidden');
                insightsToggle.setAttribute('aria-expanded', 'true');
                if (insightsChevron) insightsChevron.className = 'bi bi-chevron-up';
            } else {
                insightsBody.setAttribute('hidden', '');
                insightsToggle.setAttribute('aria-expanded', 'false');
                if (insightsChevron) insightsChevron.className = 'bi bi-chevron-down';
            }
        }
        insightsToggle.addEventListener('click', toggleInsights);
        insightsToggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleInsights();
            }
        });
    }
})();
</script>
