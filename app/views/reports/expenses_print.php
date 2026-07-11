<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$expenseCount = count($expenses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenses Report — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .amt { font-weight: 700; color: #0e7490; white-space: nowrap; }
        .pct { color: #94a3b8; font-size: 9px; }
        table.detail tbody td.num { color: #0e7490; }
        table.detail tbody td.desc { max-width: 160px; word-break: break-word; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Expenses Report</div>
        </div>
        <div>
            <div class="doc-title">Expenses Report</div>
            <div class="doc-total">Total: <?= number_format($totalAmount, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></div>
            <div class="doc-meta"><?= (int) $expenseCount ?> expense<?= $expenseCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <?php if (empty($expenses)): ?>
    <div class="empty">No expenses found for the selected filters.</div>
    <?php else: ?>

    <div class="period-box">
        <strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?>
    </div>

    <?php if (!empty($catSummary) || !empty($accSummary)): ?>
    <div class="summary-grid">
        <?php if (!empty($catSummary)): ?>
        <div class="summary-panel">
            <h3>By Category</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>Category</th><th class="num">Amt</th><th class="num">%</th></tr>
                </thead>
                <tbody>
                <?php foreach ($catSummary as $cat):
                    $pct = $totalAmount > 0 ? ($cat['total'] / $totalAmount * 100) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($cat['category'] ?? 'Uncategorized') ?></td>
                    <td class="num amt"><?= number_format((float) $cat['total'], DECIMAL_PLACES) ?></td>
                    <td class="num pct"><?= number_format($pct, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php if (!empty($accSummary)): ?>
        <div class="summary-panel">
            <h3>By Account</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>Account</th><th class="num">Amt</th><th class="num">%</th></tr>
                </thead>
                <tbody>
                <?php foreach ($accSummary as $acc):
                    $pct = $totalAmount > 0 ? ($acc['total'] / $totalAmount * 100) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($acc['account'] ?? 'Unknown') ?></td>
                    <td class="num amt"><?= number_format((float) $acc['total'], DECIMAL_PLACES) ?></td>
                    <td class="num pct"><?= number_format($pct, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="detail-wrap">
    <div class="detail-bar">Expense details</div>
    <table class="detail">
        <thead>
            <tr>
                <th style="width:18px;">#</th>
                <th style="width:52px;">Date</th>
                <th style="width:68px;">Ref</th>
                <th style="width:56px;">Cat</th>
                <th style="width:72px;">Account</th>
                <th>Description</th>
                <th style="width:48px;">By</th>
                <th class="num" style="width:58px;">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($expenses as $exp): ?>
        <tr>
            <td><?= $n++ ?></td>
            <td><?= date('d/m/y', strtotime($exp['date'])) ?></td>
            <td class="ref"><?= htmlspecialchars($exp['expense_no'] ?? '—') ?></td>
            <td><?= htmlspecialchars($exp['category_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($exp['account_name'] ?? '—') ?></td>
            <td class="desc"><?= htmlspecialchars($exp['description'] ?? '—') ?></td>
            <td><?= htmlspecialchars($exp['created_by_name'] ?? '—') ?></td>
            <td class="num"><?= number_format((float) $exp['amount'], DECIMAL_PLACES) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total — <?= (int) $expenseCount ?> expenses</td>
                <td class="num"><?= number_format($totalAmount, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
            </tr>
        </tfoot>
    </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Expenses Report</span>
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
