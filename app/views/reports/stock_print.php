<?php
$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$warehouseLabel = htmlspecialchars($warehouse['name'] ?? Auth::warehouseName());
$itemCount = count($data);
$totalQty  = (int) array_sum(array_column($data, 'stock'));
$money = function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Valuation — <?= $warehouseLabel ?></title>
    <style>
        <?php include __DIR__ . '/partials/print_teal_base.css.php'; ?>
        table.detail tbody td.item-name { font-weight: 700; color: #134e4a; }
        table.detail tbody td.qty-ok { color: #047857; font-weight: 700; text-align: center; }
        table.detail tbody td.qty-low { color: #b91c1c; font-weight: 700; text-align: center; }
        table.detail tbody td.val { color: #0e7490; font-weight: 700; }
        .status-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
        }
        .status-ok { background: #d1fae5; color: #047857; }
        .status-low { background: #fee2e2; color: #b91c1c; }
        .sbox-low { color: #b91c1c; }
        .sbox-val { color: #047857; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <div class="company-sub">Stock Valuation</div>
        </div>
        <div>
            <div class="doc-title">Stock Valuation Report</div>
            <div class="doc-meta"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?> · <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="period-box">
        <strong>Warehouse:</strong> <?= $warehouseLabel ?>
    </div>

    <?php if (empty($data)): ?>
    <div class="empty">No stock items found.</div>
    <?php else: ?>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Items</div>
            <div class="sbox-value"><?= $itemCount ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Qty</div>
            <div class="sbox-value"><?= number_format($totalQty) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Stock Value</div>
            <div class="sbox-value sbox-val"><?= $money($totalValue) ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Low Stock</div>
            <div class="sbox-value sbox-low"><?= $lowCount ?></div>
        </div>
    </div>

    <div class="detail-wrap">
        <div class="detail-bar">Stock — <?= $itemCount ?></div>
        <table class="detail">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width:48px;">SKU</th>
                    <th style="width:56px;">Brand</th>
                    <th class="num" style="width:32px;">Min</th>
                    <th class="num" style="width:32px;">Qty</th>
                    <th class="num" style="width:52px;">Cost</th>
                    <th class="num" style="width:52px;">Sale</th>
                    <th class="num" style="width:58px;">Value</th>
                    <th style="width:36px;">St</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $s):
                $isLow = (int) $s['stock'] <= (int) $s['min_stock'] && (int) $s['min_stock'] > 0;
                $brandModel = trim(($s['brand'] ?? '') . ' ' . ($s['model'] ?? ''));
            ?>
            <tr>
                <td class="item-name"><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['sku'] ?: '—') ?></td>
                <td><?= htmlspecialchars($brandModel !== '' ? $brandModel : '—') ?></td>
                <td class="num"><?= (int) $s['min_stock'] ?></td>
                <td class="num <?= $isLow ? 'qty-low' : 'qty-ok' ?>"><?= (int) $s['stock'] ?></td>
                <td class="num"><?= number_format((float) $s['purchase_price'], DECIMAL_PLACES) ?></td>
                <td class="num"><?= number_format((float) $s['sale_price'], DECIMAL_PLACES) ?></td>
                <td class="num val"><?= number_format((float) $s['stock_value'], DECIMAL_PLACES) ?></td>
                <td>
                    <span class="status-tag <?= $isLow ? 'status-low' : 'status-ok' ?>">
                        <?= $isLow ? 'Low' : 'OK' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Total</td>
                    <td class="num" style="color:#0e7490;"><?= number_format($totalQty) ?></td>
                    <td colspan="2"></td>
                    <td class="num val"><?= number_format($totalValue, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Stock Valuation</span>
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
