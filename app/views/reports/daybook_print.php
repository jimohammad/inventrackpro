<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$dateLabel   = date('l, d M Y', strtotime($date));
$money = function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};

$moneyIn  = $summary['sales'] + $summary['payments_in'];
$moneyOut = $summary['purchases'] + $summary['payments_out'] + $summary['returns'] + $summary['expenses'];
$netFlow  = $moneyIn - $moneyOut;
$dayTotal = (float) array_sum(array_column($transactions, 'amount'));
$txnCount = count($transactions);

$typeStyles = [
    'Sale'        => ['bg' => '#e0e7ff', 'color' => '#4338ca'],
    'Purchase'    => ['bg' => '#fef3c7', 'color' => '#b45309'],
    'Payment In'  => ['bg' => '#d1fae5', 'color' => '#047857'],
    'Payment Out' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
    'Return'      => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
    'Expense'     => ['bg' => '#f0fdfa', 'color' => '#0f766e'],
    'Discount'    => ['bg' => '#fce7f3', 'color' => '#be185d'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Day Book — <?= htmlspecialchars($dateLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .sbox-in { color: #047857; }
        .sbox-out { color: #b91c1c; }
        .sbox-net-pos { color: #047857; }
        .sbox-net-neg { color: #b91c1c; }
        table.detail tbody td.center { text-align: center; color: #64748b; font-size: 10px; }
        .type-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }
        table.detail tfoot td.num { color: #0e7490; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Day Book</div>
        </div>
        <div>
            <div class="doc-title">Day Book Report</div>
            <div class="doc-meta"><?= (int) $txnCount ?> transaction<?= $txnCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="period-box">
        <strong>Date:</strong> <?= htmlspecialchars($dateLabel) ?>
    </div>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Money In</div>
            <div class="sbox-value sbox-in"><?= $money($moneyIn) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Money Out</div>
            <div class="sbox-value sbox-out"><?= $money($moneyOut) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Net Flow</div>
            <div class="sbox-value <?= $netFlow >= 0 ? 'sbox-net-pos' : 'sbox-net-neg' ?>">
                <?= $netFlow < 0 ? '-' : '' ?><?= $money(abs($netFlow)) ?>
            </div>
        </div>
    </div>

    <?php if (empty($transactions)): ?>
    <div class="empty">No transactions on this date.</div>
    <?php else: ?>

    <div class="detail-wrap">
        <div class="detail-bar">Transactions — <?= (int) $txnCount ?></div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:16px;">#</th>
                    <th style="width:44px;">Time</th>
                    <th style="width:58px;">Type</th>
                    <th style="width:68px;">Ref</th>
                    <th>Party / Category</th>
                    <th style="width:52px;">By</th>
                    <th class="num" style="width:62px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $i => $t):
                $ts = $typeStyles[$t['type']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
                $party = $t['party_name'] ?? '—';
            ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td><?= $t['created_at'] ? date('h:i A', strtotime($t['created_at'])) : '—' ?></td>
                <td>
                    <span class="type-tag" style="background:<?= $ts['bg'] ?>;color:<?= $ts['color'] ?>;">
                        <?= htmlspecialchars($t['type']) ?>
                    </span>
                </td>
                <td class="ref"><?= htmlspecialchars((string) $t['ref_no']) ?></td>
                <td class="party">
                    <?php if ($party !== '—'): ?>
                    <strong><?= htmlspecialchars($party) ?></strong>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= htmlspecialchars($t['created_by'] ?? '—') ?></td>
                <td class="num" style="color:<?= $ts['color'] ?>;"><?= $money((float) $t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right;">Day Total</td>
                    <td class="num"><?= $money($dayTotal) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Day Book</span>
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
