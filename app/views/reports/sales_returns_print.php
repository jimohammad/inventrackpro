<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$returnCount = count($returns);
$money = function (float $v): string {
    return number_format($v, DECIMAL_PLACES) . ' ' . APP_CURRENCY;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Returns — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>

        .cust-grid { display: flex; flex-wrap: wrap; gap: 6px 16px; padding: 8px 10px; }
        .cust-chip {
            flex: 1 1 220px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 11px;
            padding: 4px 0;
            border-bottom: 1px dotted #99f6e4;
        }
        .cust-chip:last-child { border-bottom: none; }
        .cust-name { font-weight: 700; color: #134e4a; }
        .cust-amt { font-weight: 700; color: #0e7490; white-space: nowrap; }
        .cust-meta { font-size: 10px; color: #94a3b8; }

        .return-block {
            border: 1px solid #99f6e4;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .return-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 12px;
            background: #ecfeff;
            padding: 7px 10px;
            border-bottom: 1px solid #99f6e4;
        }
        .return-bar-left { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 14px; }
        .ret-no {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 12px;
            font-weight: 800;
            color: #0e7490;
        }
        .ret-date { font-size: 11px; color: #475569; }
        .ret-party { font-size: 11px; font-weight: 700; color: #134e4a; }
        .ret-amt { font-size: 12px; font-weight: 800; color: #0e7490; white-space: nowrap; }

        .return-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 16px;
            padding: 5px 10px;
            font-size: 10px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #ccfbf1;
        }
        .return-meta strong { color: #475569; font-weight: 600; }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead tr { background: #ccfbf1; }
        table.items thead th {
            padding: 4px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f766e;
            text-align: left;
            border-bottom: 1px solid #99f6e4;
        }
        table.items thead th.num { text-align: right; }
        table.items tbody td {
            padding: 4px 8px;
            font-size: 10.5px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.items tbody tr:nth-child(even) { background: #f8fafc; }
        table.items tbody tr:last-child td { border-bottom: none; }
        table.items tbody td.item-name { font-weight: 600; color: #334155; }
        table.items tbody td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        table.items tbody td.qty {
            text-align: center;
            font-weight: 700;
            color: #0e7490;
            width: 36px;
        }
        table.items tfoot td {
            padding: 5px 8px;
            font-size: 10px;
            font-weight: 700;
            background: #ecfeff;
            color: #0f766e;
            border-top: 1px solid #99f6e4;
        }
        table.items tfoot td.num { text-align: right; color: #0e7490; }

        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            padding: 10px 12px;
            background: #ecfeff;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 800;
            color: #0f766e;
        }
        .grand-total .amt { font-size: 14px; color: #0e7490; }

        .returns-list { margin-top: 0; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Sales Returns Report</div>
        </div>
        <div>
            <div class="doc-title">Sales Returns</div>
            <div class="doc-meta"><?= $returnCount ?> return<?= $returnCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
            <div class="doc-total">Total: <?= $money($totalAmount) ?></div>
        </div>
    </div>

    <div class="period-box">
        <strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?>
    </div>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Returns</div>
            <div class="sbox-value"><?= $returnCount ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Items Qty</div>
            <div class="sbox-value"><?= (int) $totalQty ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Amount</div>
            <div class="sbox-value"><?= $money($totalAmount) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Customers</div>
            <div class="sbox-value"><?= count($custSummary) ?></div>
        </div>
    </div>

    <?php if (!empty($custSummary)): ?>
    <div class="summary-panel">
        <h3>Returns by Customer</h3>
        <div class="cust-grid">
            <?php foreach ($custSummary as $cs):
                $pct = $totalAmount > 0 ? ($cs['total'] / $totalAmount * 100) : 0;
            ?>
            <div class="cust-chip">
                <div>
                    <div class="cust-name"><?= htmlspecialchars($cs['party_name']) ?></div>
                    <div class="cust-meta"><?= (int) $cs['count'] ?> returns · <?= number_format($pct, 1) ?>%</div>
                </div>
                <div class="cust-amt"><?= $money((float) $cs['total']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($returns)): ?>
    <div class="empty">No returns found for the selected filters.</div>
    <?php else: ?>

    <div class="detail-wrap returns-list">
        <div class="detail-bar">Return Details — <?= $returnCount ?></div>

    <?php $n = 1; foreach ($returns as $ret):
        $items     = $itemsByReturn[$ret['id']] ?? [];
        $lineQty   = array_sum(array_column($items, 'quantity'));
        $lineTotal = (float) $ret['grand_total'];
        $reason    = trim((string) ($ret['reason'] ?? ''));
    ?>
    <div class="return-block">
        <div class="return-bar">
            <div class="return-bar-left">
                <span style="color:#94a3b8;font-size:10px;">#<?= $n++ ?></span>
                <span class="ret-no"><?= htmlspecialchars($ret['return_no']) ?></span>
                <span class="ret-date"><?= date('d M Y', strtotime($ret['date'])) ?></span>
                <span class="ret-party"><?= htmlspecialchars($ret['party_name']) ?></span>
            </div>
            <span class="ret-amt"><?= $money($lineTotal) ?></span>
        </div>
        <div class="return-meta">
            <?php if (!empty($ret['original_invoice'])): ?>
            <span><strong>Orig. Invoice:</strong> <?= htmlspecialchars($ret['original_invoice']) ?></span>
            <?php endif; ?>
            <?php if ($reason !== ''): ?>
            <span><strong>Reason:</strong> <?= htmlspecialchars($reason) ?></span>
            <?php endif; ?>
            <span><strong>By:</strong> <?= htmlspecialchars($ret['created_by_name'] ?? '—') ?></span>
        </div>
        <?php if (!empty($items)): ?>
        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num" style="width:36px;">Qty</th>
                    <th class="num" style="width:64px;">Unit</th>
                    <th class="num" style="width:72px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="item-name"><?= htmlspecialchars($item['item_name']) ?></td>
                <td class="qty"><?= (int) $item['quantity'] ?></td>
                <td class="num"><?= $money((float) $item['unit_price']) ?></td>
                <td class="num"><?= $money((float) $item['total']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><?= count($items) ?> line<?= count($items) === 1 ? '' : 's' ?> · <?= (int) $lineQty ?> units</td>
                    <td class="qty"><?= (int) $lineQty ?></td>
                    <td></td>
                    <td class="num"><?= $money($lineTotal) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div style="padding:8px 10px;font-size:10px;color:#94a3b8;">No line items recorded.</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    </div>

    <div class="grand-total">
        <span>Total — <?= $returnCount ?> returns · <?= (int) $totalQty ?> items</span>
        <span class="amt"><?= $money($totalAmount) ?></span>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= APP_NAME ?> — Sales Returns Report</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };</script>
</body>
</html>
