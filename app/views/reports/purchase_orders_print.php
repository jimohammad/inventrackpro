<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$poCount = count($orders);

$statusLabels = [
    'draft'     => 'Draft',
    'paid'      => 'Paid — Awaiting',
    'converted' => 'Converted',
    'cancelled' => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Orders Report — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        .amt { font-weight: 700; color: #c2410c; white-space: nowrap; }
        .paid { color: #059669; }
        .kwd { color: #4338ca; }
        table.detail tbody td.ref { font-family: monospace; font-size: 9px; }
        table.detail tbody td.sku { font-size: 9px; color: #64748b; }

        .po-block {
            margin-bottom: 12px;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .po-head {
            background: #f0fdfa;
            padding: 7px 10px;
            border-bottom: 1px solid #99f6e4;
            display: flex;
            flex-wrap: wrap;
            gap: 4px 14px;
            align-items: center;
            font-size: 11px;
        }
        .po-no {
            font-family: monospace;
            font-weight: 800;
            color: #0e7490;
            font-size: 12px;
        }
        .po-meta { color: #475569; }
        .po-meta strong { color: #0f766e; }
        .po-status {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            background: #ecfeff;
            color: #0f766e;
        }
        .po-items-title {
            background: #ccfbf1;
            color: #0f766e;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            padding: 4px 10px;
            border-bottom: 1px solid #99f6e4;
        }
        table.po-items { width: 100%; border-collapse: collapse; }
        table.po-items thead th {
            padding: 4px 7px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f766e;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        table.po-items thead th.num { text-align: right; }
        table.po-items tbody td {
            padding: 4px 7px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: top;
        }
        table.po-items tbody tr:nth-child(even) { background: #f8fafc; }
        table.po-items tbody td.num { text-align: right; }
        table.po-items tfoot td {
            padding: 4px 7px;
            font-size: 10px;
            font-weight: 700;
            background: #f0fdfa;
            border-top: 1px solid #99f6e4;
        }
        table.po-items tfoot td.num { text-align: right; }
        .po-empty {
            padding: 8px 10px;
            font-size: 10px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Purchase Orders Report — with item details</div>
        </div>
        <div>
            <div class="doc-title">Purchase Orders</div>
            <div class="doc-total">KWD Total: <?= number_format((float) $summary['totalKwd'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></div>
            <div class="doc-meta"><?= (int) $poCount ?> PO<?= $poCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty">No purchase orders found for the selected filters.</div>
    <?php else: ?>

    <div class="period-box">
        <strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?>
        <?php if ($supplierId): ?>
        · <strong>Supplier filter applied</strong>
        <?php endif; ?>
        <?php if ($status !== ''): ?>
        · <strong>Status:</strong> <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?>
        <?php endif; ?>
        <?php if ($currency !== ''): ?>
        · <strong>Currency:</strong> <?= htmlspecialchars($currency) ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($summary['byCurrency'])): ?>
    <div class="summary-grid">
        <div class="summary-panel">
            <h3>By Currency</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>Currency</th><th class="num">Count</th><th class="num">Foreign</th><th class="num">KWD</th></tr>
                </thead>
                <tbody>
                <?php foreach ($summary['byCurrency'] as $cur): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $cur['currency']) ?></td>
                    <td class="num"><?= (int) $cur['count'] ?></td>
                    <td class="num amt"><?= number_format((float) $cur['foreign_total'], DECIMAL_PLACES) ?></td>
                    <td class="num"><?= number_format((float) $cur['kwd_total'], DECIMAL_PLACES) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="summary-panel">
            <h3>By Status</h3>
            <table class="mini-table">
                <thead>
                    <tr><th>Status</th><th class="num">Count</th></tr>
                </thead>
                <tbody>
                <?php foreach ($summary['statusCounts'] as $st => $cnt): if (!$cnt) continue; ?>
                <tr>
                    <td><?= htmlspecialchars($statusLabels[$st] ?? ucfirst($st)) ?></td>
                    <td class="num"><?= (int) $cnt ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($orders as $o):
        $poId         = (int) $o['id'];
        $items        = $itemsByPo[$poId] ?? [];
        $otherCharges = (float) ($o['other_charges_kwd'] ?? 0);
        $kwdTotal     = (float) $o['subtotal_kwd'] + $otherCharges;
        $cur          = (string) ($o['currency'] ?? 'KWD');
    ?>
    <div class="po-block">
        <div class="po-head">
            <span class="po-no"><?= htmlspecialchars($o['po_no']) ?></span>
            <span class="po-meta"><strong>Date:</strong> <?= date('d M Y', strtotime($o['date'])) ?></span>
            <span class="po-meta"><strong>Supplier:</strong> <?= htmlspecialchars($o['supplier_name']) ?></span>
            <?php if (!empty($o['supplier_ref'])): ?>
            <span class="po-meta"><strong>Ref:</strong> <?= htmlspecialchars($o['supplier_ref']) ?></span>
            <?php endif; ?>
            <span class="po-meta"><strong>Currency:</strong> <?= htmlspecialchars($cur) ?></span>
            <span class="po-meta"><strong>Paid:</strong> <span class="paid"><?= number_format((float) ($o['paid_kwd'] ?? 0), DECIMAL_PLACES) ?> KWD</span></span>
            <?php if (!empty($o['converted_invoice_no'])): ?>
            <span class="po-meta"><strong>Invoice:</strong> <?= htmlspecialchars($o['converted_invoice_no']) ?></span>
            <?php endif; ?>
            <span class="po-status"><?= htmlspecialchars($statusLabels[$o['status']] ?? ucfirst((string) $o['status'])) ?></span>
        </div>

        <?php if (empty($items)): ?>
        <div class="po-empty">No line items on this PO.</div>
        <?php else: ?>
        <div class="po-items-title">Ordered Items</div>
        <table class="po-items">
            <thead>
                <tr>
                    <th style="width:18px;">#</th>
                    <th>Item</th>
                    <th class="num" style="width:32px;">Qty</th>
                    <th class="num" style="width:52px;">Unit (<?= htmlspecialchars($cur) ?>)</th>
                    <th class="num" style="width:52px;">Total (<?= htmlspecialchars($cur) ?>)</th>
                    <th class="num" style="width:52px;">Unit KWD</th>
                    <th class="num" style="width:52px;">Total KWD</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $n => $item):
                $qty = (int) ($item['quantity'] ?? 0);
                $unitKwd = (float) ($item['unit_price_kwd'] ?? 0);
                if ($unitKwd <= 0 && (float) ($item['total_kwd'] ?? 0) > 0 && $qty > 0) {
                    $unitKwd = round((float) $item['total_kwd'] / $qty, 3);
                }
            ?>
            <tr>
                <td><?= $n + 1 ?></td>
                <td>
                    <?= htmlspecialchars($item['item_name']) ?>
                    <?php if (!empty($item['sku'])): ?>
                    <div class="sku"><?= htmlspecialchars($item['sku']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="num"><?= $qty ?></td>
                <td class="num"><?= number_format((float) $item['unit_price_foreign'], DECIMAL_PLACES) ?></td>
                <td class="num amt"><?= number_format((float) $item['total_foreign'], DECIMAL_PLACES) ?></td>
                <td class="num kwd"><?= $unitKwd > 0 ? number_format($unitKwd, DECIMAL_PLACES) : '—' ?></td>
                <td class="num kwd"><?= number_format((float) $item['total_kwd'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Subtotal</td>
                    <td class="num amt"><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($cur) ?></td>
                    <td></td>
                    <td class="num kwd"><?= number_format((float) $o['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</td>
                </tr>
                <?php if ($otherCharges > 0.001): ?>
                <tr>
                    <td colspan="6">Other Charges</td>
                    <td class="num kwd"><?= number_format($otherCharges, DECIMAL_PLACES) ?> KWD</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4">PO Total</td>
                    <td class="num amt"><?= number_format((float) $o['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($cur) ?></td>
                    <td></td>
                    <td class="num kwd"><?= number_format($kwdTotal, DECIMAL_PLACES) ?> KWD</td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="detail-wrap" style="margin-top:10px;">
        <div class="detail-bar">Report totals (excl. cancelled)</div>
        <table class="detail">
            <tbody>
            <tr>
                <td><strong><?= (int) $poCount ?> purchase orders</strong></td>
                <td class="num kwd" style="text-align:right;">KWD <?= number_format((float) $summary['totalKwd'], DECIMAL_PLACES) ?></td>
                <td class="num paid" style="text-align:right;width:100px;">Paid <?= number_format((float) $summary['totalPaidKwd'], DECIMAL_PLACES) ?> KWD</td>
            </tr>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Purchase Orders Report</span>
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
