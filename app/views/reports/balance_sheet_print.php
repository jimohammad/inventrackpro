<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$asOfLabel      = date('d M Y', strtotime($date));
$totalEquity    = $netWorth;
$liabPlusEquity = round($totalLiabilities + $totalEquity, 3);
$currentAssets  = round($totalCash + $totalReceivable + $stockVal + $totalPoAdvances, 3);
$cmpCurrentAssets = round(
    (float) ($compareSnapshot['total_cash'] ?? 0)
    + (float) ($compareSnapshot['total_receivable'] ?? 0)
    + (float) ($compareSnapshot['stock_val'] ?? 0)
    + (float) ($compareSnapshot['total_po_advances'] ?? 0),
    3
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Balance Sheet — <?= htmlspecialchars($asOfLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        table.detail tbody tr.section td {
            background: #ecfeff;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #0e7490;
        }
        table.detail tbody tr.group td { font-weight: 700; background: #f8fafc; }
        table.detail tbody tr.subgroup td { font-weight: 700; }
        table.detail tbody tr.total td { font-weight: 800; border-top: 2px solid #99f6e4; background: #f0fdfa; }
        table.detail tbody tr.grand td { font-weight: 800; border-top: 3px double #0e7490; background: #ecfeff; font-size: 12px; }
        table.detail tbody tr.check td { font-weight: 700; background: #d1fae5; color: #047857; }
        table.detail tbody tr.detail-row td { font-size: 11px; color: #475569; }
        table.detail tbody tr.memo td { font-size: 10px; color: #64748b; font-style: italic; }
        .indent-1 { padding-left: 18px !important; }
        .indent-2 { padding-left: 32px !important; }
        .indent-3 { padding-left: 46px !important; }
        .footnote { margin-top: 12px; font-size: 10px; color: #64748b; line-height: 1.5; }
        @media print { @page { margin: 12mm; } }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
            <div class="company-sub">Statement of Financial Position</div>
        </div>
        <div>
            <div class="doc-title">Balance Sheet</div>
            <div class="doc-meta">As of <?= htmlspecialchars($asOfLabel) ?></div>
            <div class="doc-total"><?= $money($totalAssets) ?> Total Assets</div>
        </div>
    </div>

    <div class="period-box">
        <strong>Reporting date:</strong> <?= htmlspecialchars($asOfLabel) ?>
        &nbsp;·&nbsp;
        <strong>Comparison:</strong> <?= htmlspecialchars($compareLabel) ?>
        &nbsp;·&nbsp;
        <strong>Equation:</strong> Assets = Liabilities + Equity
    </div>

    <table class="detail">
        <thead>
            <tr>
                <th>Account</th>
                <th class="num"><?= htmlspecialchars($asOfLabel) ?></th>
                <th class="num"><?= htmlspecialchars($compareLabel) ?></th>
            </tr>
        </thead>
        <tbody>
            <tr class="section"><td colspan="3">Assets</td></tr>
            <tr class="group"><td class="indent-1">Current Assets</td><td class="num"></td><td class="num"></td></tr>
            <tr class="subgroup"><td class="indent-2">Cash &amp; Bank</td><td class="num"><?= $money($totalCash) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_cash'] ?? 0)) ?></td></tr>
            <?php foreach ($accounts as $a):
                $acctBal = (float) ($a['balance_as_of'] ?? $a['current_balance']);
            ?>
            <tr class="detail-row">
                <td class="indent-3">#<?= (int) $a['id'] ?> <?= htmlspecialchars($a['name']) ?></td>
                <td class="num"><?= $money($acctBal) ?></td>
                <td class="num">—</td>
            </tr>
            <?php endforeach; ?>
            <tr class="subgroup"><td class="indent-2">Accounts Receivable</td><td class="num"><?= $money($totalReceivable) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_receivable'] ?? 0)) ?></td></tr>
            <?php foreach ($receivables as $r): ?>
            <tr class="detail-row">
                <td class="indent-3"><?= htmlspecialchars($r['name']) ?></td>
                <td class="num"><?= $money((float) $r['balance']) ?></td>
                <td class="num">—</td>
            </tr>
            <?php endforeach; ?>
            <tr class="subgroup"><td class="indent-2">Inventory (at cost)</td><td class="num"><?= $money($stockVal) ?></td><td class="num"><?= $money((float) ($compareSnapshot['stock_val'] ?? 0)) ?></td></tr>
            <tr class="subgroup"><td class="indent-2">Prepaid Supplier Advances (PO)</td><td class="num"><?= $money($totalPoAdvances) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_po_advances'] ?? 0)) ?></td></tr>
            <?php foreach ($poAdvances as $adv): ?>
            <tr class="detail-row">
                <td class="indent-3"><?= htmlspecialchars($adv['name']) ?></td>
                <td class="num"><?= $money((float) $adv['amount']) ?></td>
                <td class="num">—</td>
            </tr>
            <?php endforeach; ?>
            <tr class="total"><td class="indent-1">Total Current Assets</td><td class="num"><?= $money($currentAssets) ?></td><td class="num"><?= $money($cmpCurrentAssets) ?></td></tr>
            <tr class="grand"><td>Total Assets</td><td class="num"><?= $money($totalAssets) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_assets'] ?? 0)) ?></td></tr>

            <tr class="section"><td colspan="3">Liabilities</td></tr>
            <tr class="group"><td class="indent-1">Current Liabilities</td><td class="num"></td><td class="num"></td></tr>
            <tr class="subgroup"><td class="indent-2">Accounts Payable</td><td class="num"><?= $money($totalPayable) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_payable'] ?? 0)) ?></td></tr>
            <?php foreach ($payables as $p): ?>
            <tr class="detail-row">
                <td class="indent-3"><?= htmlspecialchars($p['name']) ?></td>
                <td class="num"><?= $money((float) $p['balance']) ?></td>
                <td class="num">—</td>
            </tr>
            <?php endforeach; ?>
            <tr class="grand"><td>Total Liabilities</td><td class="num"><?= $money($totalLiabilities) ?></td><td class="num"><?= $money((float) ($compareSnapshot['total_liabilities'] ?? 0)) ?></td></tr>

            <tr class="section"><td colspan="3">Equity</td></tr>
            <tr class="subgroup"><td class="indent-1">Retained Earnings / Net Worth</td><td class="num"><?= $money($netWorth) ?></td><td class="num"><?= $money((float) ($compareSnapshot['net_worth'] ?? 0)) ?></td></tr>
            <tr class="grand"><td>Total Equity</td><td class="num"><?= $money($totalEquity) ?></td><td class="num"><?= $money((float) ($compareSnapshot['net_worth'] ?? 0)) ?></td></tr>

            <tr class="check">
                <td>Total Liabilities + Equity</td>
                <td class="num"><?= $money($liabPlusEquity) ?></td>
                <td class="num"><?= $money(round((float) ($compareSnapshot['total_liabilities'] ?? 0) + (float) ($compareSnapshot['net_worth'] ?? 0), 3)) ?></td>
            </tr>
        </tbody>
    </table>

    <p class="footnote">
        Cash is net of PO supplier payments; prepaid advances are shown separately until PO converts to purchase.
        Inventory valued at current item cost. Generated <?= date('d M Y H:i') ?>.
    </p>
</div>
<script>
window.addEventListener('load', function () { window.print(); });
</script>
</body>
</html>
