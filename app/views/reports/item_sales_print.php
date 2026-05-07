<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Sales — <?= htmlspecialchars($item['name']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#1e293b; background:#fff; }
        .page { padding:28px 32px; max-width:820px; margin:0 auto; }

        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid #1e3a5f; }
        .company-name { font-size:19px; font-weight:800; color:#1e3a5f; }
        .company-sub  { font-size:10px; color:#64748b; margin-top:2px; }
        .doc-title { font-size:16px; font-weight:700; color:#1e3a5f; text-align:right; }
        .doc-sub   { font-size:10px; color:#64748b; text-align:right; margin-top:3px; }

        .item-box {
            background:#f8faff; border:1px solid #e0e7ff; border-radius:8px;
            padding:12px 16px; margin-bottom:16px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .item-name { font-size:14px; font-weight:700; color:#1e3a5f; }
        .item-meta  { font-size:10px; color:#64748b; margin-top:3px; }
        .period     { font-size:11px; color:#64748b; text-align:right; }

        .summary-row { display:flex; gap:10px; margin-bottom:16px; }
        .sbox { flex:1; border:1px solid #e0e7ff; border-radius:7px; padding:9px 12px; text-align:center; }
        .sbox-label { font-size:9px; color:#64748b; text-transform:uppercase; font-weight:600; }
        .sbox-value { font-size:13px; font-weight:700; color:#1e3a5f; margin-top:3px; }

        .section-title { font-size:11px; font-weight:700; color:#1e3a5f; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #e0e7ff; }

        table { width:100%; border-collapse:collapse; margin-bottom:18px; }
        thead tr { background:#1e3a5f; }
        thead th { color:#fff; padding:7px 10px; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
        tbody tr:nth-child(even) { background:#f8faff; }
        tbody td { padding:6px 10px; border-bottom:1px solid #f0f3f8; font-size:11px; }
        tfoot tr { background:#eef2ff; border-top:2px solid #c7d2fe; }
        tfoot td { padding:7px 10px; font-weight:700; font-size:11px; }

        .text-right  { text-align:right; }
        .text-center { text-align:center; }
        .green { color:#059669; font-weight:700; }
        .purple { color:#6366f1; font-weight:700; }

        .two-col { display:flex; gap:16px; margin-bottom:16px; }
        .two-col > div { flex:1; }

        .footer { margin-top:20px; padding-top:12px; border-top:1px solid #e0e7ff; display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
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
            <div class="doc-title">Item Sales Report</div>
            <div class="doc-sub">Printed: <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="item-box">
        <div>
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="item-meta">
                <?php if ($item['sku']): ?>SKU: <?= htmlspecialchars((string) $item['sku']) ?> &nbsp;|&nbsp;<?php endif; ?>
                Sale Price: <?= APP_CURRENCY ?> <?= number_format($item['sale_price'], DECIMAL_PLACES) ?>
            </div>
        </div>
        <div class="period">
            <strong>Period:</strong><br>
            <?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?>
        </div>
    </div>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Total Qty Sold</div>
            <div class="sbox-value purple"><?= number_format($summary['qty']) ?> units</div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Revenue</div>
            <div class="sbox-value green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">No. of Invoices</div>
            <div class="sbox-value"><?= $summary['invoices'] ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Avg Selling Price</div>
            <div class="sbox-value"><?= APP_CURRENCY ?> <?= $summary['qty'] > 0 ? number_format($summary['revenue'] / $summary['qty'], DECIMAL_PLACES) : '0.000' ?></div>
        </div>
    </div>

    <!-- Party Summary -->
    <div class="section-title">Sales by Party</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Party Name</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Share %</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($partyBreakdown as $i => $pb):
            $share = $summary['revenue'] > 0 ? ($pb['total'] / $summary['revenue'] * 100) : 0;
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($pb['name']) ?></td>
            <td class="text-center purple"><?= $pb['qty'] ?></td>
            <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($pb['total'], DECIMAL_PLACES) ?></td>
            <td class="text-right" style="color:#64748b;"><?= number_format($share, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="text-center purple"><?= $summary['qty'] ?></td>
                <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
                <td class="text-right">100%</td>
            </tr>
        </tfoot>
    </table>

    <!-- All Transactions -->
    <div class="section-title">All Transactions</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice</th>
                <th>Party</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td style="font-weight:600;color:#6366f1;"><?= htmlspecialchars($r['invoice_no']) ?></td>
            <td><?= htmlspecialchars($r['party_name']) ?></td>
            <td class="text-center purple"><?= $r['quantity'] ?></td>
            <td class="text-right"><?= APP_CURRENCY ?> <?= number_format($r['unit_price'], DECIMAL_PLACES) ?></td>
            <td class="text-right" style="color:#64748b;"><?= $r['discount'] > 0 ? APP_CURRENCY . ' ' . number_format($r['discount'], DECIMAL_PLACES) : '—' ?></td>
            <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($r['total'], DECIMAL_PLACES) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="text-center purple"><?= $summary['qty'] ?></td>
                <td colspan="2"></td>
                <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span><?= APP_NAME ?> &mdash; Item Sales Report</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };</script>
</body>
</html>
