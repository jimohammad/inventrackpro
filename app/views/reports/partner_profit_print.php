<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$hasData = !empty($rows) || !empty($payments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Profit Report — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .amt-acc { font-weight: 700; color: #b45309; white-space: nowrap; }
        .amt-paid { font-weight: 700; color: #059669; white-space: nowrap; }
        .badge-open { background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; }
        .badge-paid { background: #d1fae5; color: #047857; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; }
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .summary-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f8fafc;
        }
        .summary-box label {
            display: block;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }
        .summary-box strong { font-size: 12px; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Partner Profit Report</div>
        </div>
        <div>
            <div class="doc-title">Partner Profit</div>
            <div class="doc-total">Accrued: <?= number_format((float) $summary['totalAccrued'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></div>
            <div class="doc-meta"><?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <?php if (!$hasData): ?>
    <div class="empty">No partner profit data found for the selected filters.</div>
    <?php else: ?>

    <div class="period-box">
        <strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?>
    </div>

    <div class="summary-strip">
        <div class="summary-box">
            <label>Accrued (period)</label>
            <strong class="amt-acc"><?= number_format((float) $summary['totalAccrued'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></strong>
        </div>
        <div class="summary-box">
            <label>Paid (period)</label>
            <strong class="amt-paid"><?= number_format((float) $summary['totalPaidCash'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></strong>
        </div>
        <div class="summary-box">
            <label>Unpaid in period</label>
            <strong><?= number_format((float) $summary['totalOpenPeriod'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></strong>
        </div>
        <div class="summary-box">
            <label>Outstanding (all time)</label>
            <strong style="color:#dc2626;"><?= number_format((float) $summary['totalOpenAllTime'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></strong>
        </div>
    </div>

    <?php if (!empty($rows)): ?>
    <div class="detail-wrap">
        <div class="detail-bar">Accrued partner profit (<?= count($rows) ?> lines · <?= (int) $summary['totalQty'] ?> pcs)</div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:16px;">#</th>
                    <th style="width:52px;">Date</th>
                    <th style="width:64px;">Shipment</th>
                    <th style="width:72px;">Partner</th>
                    <th>PO / Item</th>
                    <th class="num" style="width:40px;">/pc</th>
                    <th class="num" style="width:28px;">Qty</th>
                    <th class="num" style="width:52px;">Amount</th>
                    <th style="width:44px;">Status</th>
                    <th style="width:56px;">Payment</th>
                </tr>
            </thead>
            <tbody>
            <?php $n = 1; foreach ($rows as $r): ?>
            <tr>
                <td><?= $n++ ?></td>
                <td><?= date('d/m/y', strtotime($r['date'])) ?></td>
                <td class="ref"><?= htmlspecialchars($r['shipment_no']) ?></td>
                <td><?= htmlspecialchars($r['partner_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars(trim(($r['po_no'] ?? '') . ' — ' . ($r['item_name'] ?? ''), ' —')) ?></td>
                <td class="num"><?= number_format((float) ($r['partner_profit_per_pc'] ?? 0), DECIMAL_PLACES) ?></td>
                <td class="num"><?= (int) ($r['quantity'] ?? 0) ?></td>
                <td class="num amt-acc"><?= number_format((float) $r['amount'], DECIMAL_PLACES) ?></td>
                <td>
                    <?php if (($r['status'] ?? '') === 'paid'): ?>
                    <span class="badge-paid">Paid</span>
                    <?php else: ?>
                    <span class="badge-open">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td class="ref"><?= htmlspecialchars($r['payment_no'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">Total accrued</td>
                    <td class="num"><?= number_format((float) $summary['totalAccrued'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
    <div class="detail-wrap" style="margin-top:14px;">
        <div class="detail-bar">Payments to partner (<?= count($payments) ?>)</div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:16px;">#</th>
                    <th style="width:52px;">Date</th>
                    <th style="width:64px;">Payment</th>
                    <th style="width:72px;">Partner</th>
                    <th>Reference</th>
                    <th class="num" style="width:58px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php $n = 1; foreach ($payments as $p): ?>
            <tr>
                <td><?= $n++ ?></td>
                <td><?= date('d/m/y', strtotime($p['date'])) ?></td>
                <td class="ref"><?= htmlspecialchars($p['payment_no']) ?></td>
                <td><?= htmlspecialchars($p['partner_name'] ?? '—') ?></td>
                <td>
                    <?php if (!empty($p['shipment_no'])): ?>
                    <?= htmlspecialchars(trim(($p['shipment_no'] ?? '') . ' / ' . ($p['po_no'] ?? '') . ' / ' . ($p['item_name'] ?? ''), ' /')) ?>
                    <?php else: ?>
                    <?= htmlspecialchars($p['notes'] ?? '—') ?>
                    <?php endif; ?>
                </td>
                <td class="num amt-paid"><?= number_format((float) $p['amount'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Total paid</td>
                    <td class="num"><?= number_format((float) $summary['totalPaidCash'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Partner Profit Report</span>
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
