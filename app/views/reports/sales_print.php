<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$saleCount   = (int) ($summary['count'] ?? count($data));
$money = function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$totalAmt   = (float) ($summary['total'] ?? 0);
$paidAmt    = (float) ($summary['paid'] ?? 0);
$balanceAmt = (float) ($summary['balance'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .sbox-paid { color: #047857; }
        .sbox-bal { color: #b45309; }
        table.detail tbody td.paid { color: #047857; }
        table.detail tbody td.bal-due { color: #b45309; }
        table.detail tbody td.bal-clear { color: #047857; }
        .status-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            background: #f0fdfa;
            color: #0f766e;
            border: 1px solid #99f6e4;
        }
        table.detail tfoot td.paid { color: #047857; }
        table.detail tfoot td.bal-due { color: #b45309; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Sales Report</div>
        </div>
        <div>
            <div class="doc-title">Sales Report</div>
            <div class="doc-meta"><?= $saleCount ?> invoice<?= $saleCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="period-box">
        <strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?>
    </div>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Invoices</div>
            <div class="sbox-value"><?= $saleCount ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total</div>
            <div class="sbox-value"><?= $money($totalAmt) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Paid</div>
            <div class="sbox-value sbox-paid"><?= $money($paidAmt) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Balance</div>
            <div class="sbox-value sbox-bal"><?= $money($balanceAmt) ?></div>
        </div>
    </div>

    <?php if (empty($data)): ?>
    <div class="empty">No sales in this period.</div>
    <?php else: ?>

    <div class="detail-wrap">
        <div class="detail-bar">Sales — <?= $saleCount ?></div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:16px;">#</th>
                    <th style="width:72px;">Invoice No.</th>
                    <th style="width:52px;">Date</th>
                    <th>Customer</th>
                    <th class="num" style="width:58px;">Total</th>
                    <th class="num" style="width:58px;">Paid</th>
                    <th class="num" style="width:58px;">Balance</th>
                    <th style="width:44px;">Status</th>
                    <th style="width:48px;">By</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $i => $s):
                $bal = (float) $s['balance'];
            ?>
            <tr>
                <td style="text-align:center;color:#64748b;"><?= $i + 1 ?></td>
                <td class="inv"><?= htmlspecialchars((string) $s['invoice_no']) ?></td>
                <td><?= date('d/m/y', strtotime($s['date'])) ?></td>
                <td class="party"><strong><?= htmlspecialchars($s['party_name']) ?></strong></td>
                <td class="num" style="color:#0e7490;"><?= $money((float) $s['grand_total']) ?></td>
                <td class="num paid"><?= $money((float) $s['paid_amount']) ?></td>
                <td class="num <?= $bal > 0 ? 'bal-due' : 'bal-clear' ?>"><?= $money($bal) ?></td>
                <td><span class="status-tag"><?= htmlspecialchars(ucfirst((string) $s['status'])) ?></span></td>
                <td><?= htmlspecialchars($s['created_by_name'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Totals</td>
                    <td class="num" style="color:#0e7490;"><?= $money($totalAmt) ?></td>
                    <td class="num paid"><?= $money($paidAmt) ?></td>
                    <td class="num bal-due"><?= $money($balanceAmt) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Sales Report</span>
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
