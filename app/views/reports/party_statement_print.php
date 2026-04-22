<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement — <?= htmlspecialchars($party['name']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#1e293b; background:#fff; }
        .page { padding:28px 32px; max-width:800px; margin:0 auto; }

        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; padding-bottom:18px; border-bottom:2px solid #1e3a5f; }
        .company-name { font-size:20px; font-weight:800; color:#1e3a5f; }
        .company-sub  { font-size:11px; color:#64748b; margin-top:3px; }
        .doc-title    { font-size:18px; font-weight:700; color:#1e3a5f; text-align:right; }
        .doc-sub      { font-size:11px; color:#64748b; text-align:right; margin-top:3px; }

        .party-box {
            display:flex; justify-content:space-between;
            background:#f8faff; border:1px solid #e0e7ff; border-radius:8px;
            padding:14px 18px; margin-bottom:20px;
        }
        .party-name  { font-size:15px; font-weight:700; color:#1e3a5f; }
        .party-type  { font-size:10px; color:#6366f1; font-weight:600; text-transform:uppercase; margin-top:2px; }
        .date-range  { text-align:right; font-size:11px; color:#64748b; }

        .summary-row { display:flex; gap:12px; margin-bottom:20px; }
        .summary-box { flex:1; border:1px solid #e0e7ff; border-radius:8px; padding:10px 14px; text-align:center; }
        .summary-label { font-size:10px; color:#64748b; text-transform:uppercase; font-weight:600; }
        .summary-value { font-size:14px; font-weight:700; color:#1e3a5f; margin-top:4px; }

        table { width:100%; border-collapse:collapse; }
        thead tr { background:#1e3a5f; }
        thead th { color:#fff; padding:8px 10px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
        tbody tr:nth-child(even) { background:#f8faff; }
        tbody td { padding:7px 10px; border-bottom:1px solid #f0f3f8; font-size:11px; }
        tfoot tr { background:#eef2ff; border-top:2px solid #c7d2fe; }
        tfoot td { padding:8px 10px; font-weight:700; font-size:11px; }

        .type-badge { display:inline-block; padding:2px 7px; border-radius:4px; font-size:9px; font-weight:700; text-transform:uppercase; }
        .type-sale     { background:rgba(99,102,241,0.12); color:#6366f1; }
        .type-purchase { background:rgba(245,158,11,0.12); color:#b45309; }
        .type-payment  { background:rgba(16,185,129,0.12); color:#059669; }
        .type-discount { background:rgba(139,92,246,0.12); color:#8b5cf6; }

        .text-right  { text-align:right; }
        .text-center { text-align:center; }
        .debit  { color:#dc2626; font-weight:600; }
        .credit { color:#059669; font-weight:600; }
        .bal-dr { color:#d97706; font-weight:700; }
        .bal-cr { color:#059669; font-weight:700; }

        .footer { margin-top:24px; padding-top:14px; border-top:1px solid #e0e7ff; display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
            <div class="company-sub"><?= htmlspecialchars($settings['company_address'] ?? '') ?></div>
            <?php if (!empty($settings['company_phone'])): ?>
            <div class="company-sub">Tel: <?= htmlspecialchars($settings['company_phone']) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="doc-title">Account Statement</div>
            <div class="doc-sub">Printed: <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="party-box">
        <div>
            <div class="party-name"><?= htmlspecialchars($party['name']) ?></div>
            <div class="party-type"><?= ucfirst($party['type']) ?></div>
            <?php if (!empty($party['phone'])): ?>
            <div style="font-size:11px;color:#64748b;margin-top:3px;"><?= htmlspecialchars($party['phone']) ?></div>
            <?php endif; ?>
        </div>
        <div class="date-range">
            <div style="font-weight:700;font-size:12px;color:#1e3a5f;">Period</div>
            <div><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></div>
        </div>
    </div>

    <?php
    $debitTxns   = array_filter($transactions, function($t) { return $t['debit'] > 0; });
    $totalDebit  = array_sum(array_column(array_values($debitTxns), 'debit'));
    $totalCredit = array_sum(array_column($transactions, 'credit'));
    $closing     = end($transactions)['running_balance'] ?? 0;
    reset($transactions);
    ?>
    <div class="summary-row">
        <div class="summary-box">
            <div class="summary-label">Total Invoiced</div>
            <div class="summary-value" style="color:#dc2626;"><?= APP_CURRENCY ?> <?= number_format($totalDebit, DECIMAL_PLACES) ?></div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Total Paid</div>
            <div class="summary-value" style="color:#059669;"><?= APP_CURRENCY ?> <?= number_format($totalCredit, DECIMAL_PLACES) ?></div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Closing Balance</div>
            <div class="summary-value" style="color:<?= $closing > 0 ? '#d97706' : '#059669' ?>;">
                <?= APP_CURRENCY ?> <?= number_format(abs($closing), DECIMAL_PLACES) ?>
                <?= $closing > 0 ? 'DR' : 'CR' ?>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:90px;">Date</th>
                <th>Ref No</th>
                <th style="width:75px;">Type</th>
                <th class="text-right" style="width:110px;">Debit</th>
                <th class="text-right" style="width:110px;">Credit</th>
                <th class="text-right" style="width:120px;">Balance</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= date('d M Y', strtotime($t['date'])) ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($t['ref_no']) ?></td>
            <td>
                <span class="type-badge type-<?= $t['txn_type'] ?>">
                    <?= ucfirst($t['txn_type']) ?>
                </span>
            </td>
            <td class="text-right <?= $t['debit'] > 0 ? 'debit' : '' ?>">
                <?= $t['debit'] > 0 ? APP_CURRENCY . ' ' . number_format($t['debit'], DECIMAL_PLACES) : '—' ?>
            </td>
            <td class="text-right <?= $t['credit'] > 0 ? 'credit' : '' ?>">
                <?= $t['credit'] > 0 ? APP_CURRENCY . ' ' . number_format($t['credit'], DECIMAL_PLACES) : '—' ?>
            </td>
            <td class="text-right <?= $t['running_balance'] > 0 ? 'bal-dr' : 'bal-cr' ?>">
                <?= APP_CURRENCY ?> <?= number_format(abs($t['running_balance']), DECIMAL_PLACES) ?>
                <small style="font-size:9px;"><?= $t['running_balance'] > 0 ? 'DR' : 'CR' ?></small>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Closing Balance</td>
                <td class="text-right debit"><?= APP_CURRENCY ?> <?= number_format($totalDebit, DECIMAL_PLACES) ?></td>
                <td class="text-right credit"><?= APP_CURRENCY ?> <?= number_format($totalCredit, DECIMAL_PLACES) ?></td>
                <td class="text-right <?= $closing > 0 ? 'bal-dr' : 'bal-cr' ?>">
                    <?= APP_CURRENCY ?> <?= number_format(abs($closing), DECIMAL_PLACES) ?>
                    <?= $closing > 0 ? 'DR' : 'CR' ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span><?= APP_NAME ?> &mdash; Auto-generated statement</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body>
</html>
