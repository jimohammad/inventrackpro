<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$lineCount   = count($rows);
$money = function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$qty      = (float) ($summary['qty'] ?? 0);
$revenue  = (float) ($summary['revenue'] ?? 0);
$invoices = (int) ($summary['invoices'] ?? 0);
$avgPrice = (float) ($summary['avg_price'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Sales — <?= htmlspecialchars($item['name']) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .item-box {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        .item-name { font-size: 14px; font-weight: 800; color: #0e7490; }
        .item-meta { font-size: 11px; color: #64748b; margin-top: 3px; }
        .item-period { font-size: 11px; color: #0f766e; text-align: right; white-space: nowrap; }
        .item-period strong { color: #0e7490; display: block; font-size: 10px; text-transform: uppercase; margin-bottom: 2px; }
        .sbox-rev { color: #047857; }
        .amt { font-weight: 700; color: #047857; }
        .qty { font-weight: 700; color: #0e7490; }
        .pct { color: #94a3b8; font-size: 9px; }
        .detail-wrap { margin-bottom: 8px; }
        table.detail tbody td.rev { color: #047857; }
        table.detail tfoot td.rev { color: #047857; text-align: right; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Item Sales Report</div>
        </div>
        <div>
            <div class="doc-title">Item Sales Report</div>
            <div class="doc-meta"><?= $lineCount ?> line<?= $lineCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="item-box">
        <div>
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="item-meta">
                <?php if (!empty($item['sku'])): ?>SKU: <?= htmlspecialchars((string) $item['sku']) ?> · <?php endif; ?>
                Sale Price: <?= $money((float) $item['sale_price']) ?>
            </div>
        </div>
        <div class="item-period">
            <strong>Period</strong>
            <?= htmlspecialchars($periodLabel) ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
    <div class="empty">No sales found for this item in the selected period.</div>
    <?php else: ?>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Qty Sold</div>
            <div class="sbox-value"><?= number_format($qty) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Revenue</div>
            <div class="sbox-value sbox-rev"><?= $money($revenue) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Invoices</div>
            <div class="sbox-value"><?= $invoices ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Avg Price</div>
            <div class="sbox-value"><?= $money($avgPrice) ?></div>
        </div>
    </div>

    <?php if (!empty($partyBreakdown) || !empty($monthlyBreakdown)): ?>
    <div class="summary-grid">
        <?php if (!empty($partyBreakdown)): ?>
        <div class="summary-panel">
            <h3>Sales by Party</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>#</th><th>Party</th><th class="num">Qty</th><th class="num">Amt</th><th class="num">%</th></tr>
                </thead>
                <tbody>
                <?php foreach ($partyBreakdown as $i => $pb):
                    $share = $revenue > 0 ? ($pb['total'] / $revenue * 100) : 0;
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="party"><strong><?= htmlspecialchars($pb['name']) ?></strong></td>
                    <td class="num qty"><?= (int) $pb['qty'] ?></td>
                    <td class="num amt"><?= number_format((float) $pb['total'], DECIMAL_PLACES) ?></td>
                    <td class="num pct"><?= number_format($share, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php if (!empty($monthlyBreakdown)): ?>
        <div class="summary-panel">
            <h3>Monthly Trend</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>Month</th><th class="num">Qty</th><th class="num">Revenue</th><th class="num">%</th></tr>
                </thead>
                <tbody>
                <?php foreach ($monthlyBreakdown as $mb):
                    $share = $revenue > 0 ? ($mb['total'] / $revenue * 100) : 0;
                ?>
                <tr>
                    <td><?= date('M Y', strtotime($mb['month'] . '-01')) ?></td>
                    <td class="num qty"><?= (int) $mb['qty'] ?></td>
                    <td class="num amt"><?= number_format((float) $mb['total'], DECIMAL_PLACES) ?></td>
                    <td class="num pct"><?= number_format($share, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="detail-wrap">
        <div class="detail-bar">All Transactions — <?= $lineCount ?></div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:44px;">Date</th>
                    <th style="width:68px;">Invoice</th>
                    <th>Party</th>
                    <th style="width:52px;">Warehouse</th>
                    <th class="num" style="width:28px;">Qty</th>
                    <th class="num" style="width:52px;">Unit</th>
                    <th class="num" style="width:44px;">Disc</th>
                    <th class="num" style="width:52px;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= date('d/m/y', strtotime($r['date'])) ?></td>
                <td class="inv"><?= htmlspecialchars((string) $r['invoice_no']) ?></td>
                <td class="party"><strong><?= htmlspecialchars($r['party_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
                <td class="num" style="color:#0e7490;"><?= (int) $r['quantity'] ?></td>
                <td class="num"><?= number_format((float) $r['unit_price'], DECIMAL_PLACES) ?></td>
                <td class="num" style="color:#64748b;"><?= (float) $r['discount'] > 0 ? number_format((float) $r['discount'], DECIMAL_PLACES) : '—' ?></td>
                <td class="num rev"><?= number_format((float) $r['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Total</td>
                    <td class="num" style="color:#0e7490;"><?= (int) $qty ?></td>
                    <td colspan="2"></td>
                    <td class="num rev"><?= number_format($revenue, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Item Sales Report</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
</body>
</html>
