<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Purchases — <?= htmlspecialchars($party['name']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#1e293b; background:#fff; }
        .page { padding:28px 32px; max-width:820px; margin:0 auto; }

        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid #1e3a5f; }
        .company-name { font-size:19px; font-weight:800; color:#1e3a5f; }
        .company-sub  { font-size:10px; color:#64748b; margin-top:2px; }
        .doc-title { font-size:16px; font-weight:700; color:#1e3a5f; text-align:right; }
        .doc-sub   { font-size:10px; color:#64748b; text-align:right; margin-top:3px; }

        .party-box {
            background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px;
            padding:12px 16px; margin-bottom:16px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .party-name { font-size:14px; font-weight:700; color:#1e3a5f; }
        .party-meta  { font-size:10px; color:#64748b; margin-top:3px; }
        .period     { font-size:11px; color:#64748b; text-align:right; }

        .summary-row { display:flex; gap:10px; margin-bottom:16px; }
        .sbox { flex:1; border:1px solid #e9d5ff; border-radius:7px; padding:9px 12px; text-align:center; }
        .sbox-label { font-size:9px; color:#64748b; text-transform:uppercase; font-weight:600; }
        .sbox-value { font-size:13px; font-weight:700; color:#1e3a5f; margin-top:3px; }

        .section-title { font-size:11px; font-weight:700; color:#1e3a5f; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #e9d5ff; }

        table { width:100%; border-collapse:collapse; margin-bottom:18px; }
        thead tr { background:#1e3a5f; }
        thead th { color:#fff; padding:7px 10px; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
        tbody tr:nth-child(even) { background:#faf5ff; }
        tbody td { padding:6px 10px; border-bottom:1px solid #f0f3f8; font-size:11px; }
        tfoot tr { background:#f3e8ff; border-top:2px solid #d8b4fe; }
        tfoot td { padding:7px 10px; font-weight:700; font-size:11px; }

        .text-right  { text-align:right; }
        .text-center { text-align:center; }
        .green { color:#059669; font-weight:700; }
        .purple { color:#8b5cf6; font-weight:700; }

        .footer { margin-top:20px; padding-top:12px; border-top:1px solid #e9d5ff; display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; }

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
            <div class="doc-title">Customer Purchases Report</div>
            <div class="doc-sub">Printed: <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="party-box">
        <div>
            <div class="party-name"><?= htmlspecialchars($party['name']) ?></div>
            <div class="party-meta">
                <?php if (!empty($party['phone'])): ?>Phone: <?= htmlspecialchars((string) $party['phone']) ?> &nbsp;|&nbsp;<?php endif; ?>
                <?php if (!empty($party['party_code'])): ?>Code: <?= htmlspecialchars((string) $party['party_code']) ?><?php endif; ?>
            </div>
        </div>
        <div class="period">
            <strong>Period:</strong><br>
            <?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?>
        </div>
    </div>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Total Qty</div>
            <div class="sbox-value purple"><?= number_format($summary['qty']) ?> units</div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Amount</div>
            <div class="sbox-value green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Invoices</div>
            <div class="sbox-value"><?= (int) $summary['invoices'] ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Distinct Items</div>
            <div class="sbox-value"><?= (int) $summary['items'] ?></div>
        </div>
    </div>

    <div class="section-title">Purchases by Item</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Share %</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($itemBreakdown as $i => $ib):
            $share = $summary['revenue'] > 0 ? ($ib['total'] / $summary['revenue'] * 100) : 0;
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($ib['name']) ?></td>
            <td class="text-center purple"><?= (int) $ib['qty'] ?></td>
            <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($ib['total'], DECIMAL_PLACES) ?></td>
            <td class="text-right" style="color:#64748b;"><?= number_format($share, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="text-center purple"><?= (int) $summary['qty'] ?></td>
                <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
                <td class="text-right">100%</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">All Purchases</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice</th>
                <th>Item</th>
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
            <td style="font-weight:600;color:#8b5cf6;"><?= htmlspecialchars((string) $r['invoice_no']) ?></td>
            <td><?= htmlspecialchars($r['item_name']) ?></td>
            <td class="text-center purple"><?= (int) $r['quantity'] ?></td>
            <td class="text-right"><?= APP_CURRENCY ?> <?= number_format($r['unit_price'], DECIMAL_PLACES) ?></td>
            <td class="text-right" style="color:#64748b;"><?= $r['discount'] > 0 ? APP_CURRENCY . ' ' . number_format($r['discount'], DECIMAL_PLACES) : '—' ?></td>
            <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($r['total'], DECIMAL_PLACES) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="text-center purple"><?= (int) $summary['qty'] ?></td>
                <td colspan="2"></td>
                <td class="text-right green"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span><?= APP_NAME ?> &mdash; Customer Purchases Report</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };</script>
</body>
</html>
