<?php
/** @var array $sale */
/** @var array $settings */
?>
    <div class="inv-header">
        <div>
            <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
            <div class="company-info">
                <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
                <?= htmlspecialchars($settings['company_phone'] ?? '') ?><br>
                <?= htmlspecialchars($settings['company_email'] ?? '') ?>
            </div>
        </div>
        <div class="inv-title">
            <h1>INVOICE</h1>
            <p>
                <strong># <?= htmlspecialchars($sale['invoice_no']) ?></strong><br>
                Date: <?= date('d M Y', strtotime($sale['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?>
            </p>
        </div>
    </div>

    <div class="customer-row">
        <span class="clbl">Customer: </span>
        <span class="cname"><?= htmlspecialchars((string) ($sale['party_name'] ?? '')) ?></span>
        <?php if (!empty($sale['party_phone'])): ?>
        <span class="cphone"><?= htmlspecialchars($sale['party_phone']) ?></span>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:16px;text-align:center;">#</th>
                <th style="text-align:left;">Description</th>
                <th style="width:28px;text-align:center;">Qty</th>
                <th style="width:52px;text-align:right;">Price</th>
                <th style="width:52px;text-align:right;">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $i => $item): ?>
            <tr>
                <td style="text-align:center;color:#888;"><?= $i + 1 ?></td>
                <td>
                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                    <?php if (!empty($item['sku'])): ?>
                    <span class="item-sku"> &nbsp;<?= htmlspecialchars($item['sku']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= (int) $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format((float) $item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:700;"><?= number_format((float) $item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php $totalQty = array_sum(array_column($sale['items'], 'quantity')); ?>
            <tr style="border-top:1.5px solid #1e3a5f;">
                <td colspan="2" style="font-weight:700;font-size:8px;text-align:right;padding-right:6px;">Total Qty:</td>
                <td style="text-align:center;font-weight:800;"><?= $totalQty ?></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <?php if (($sale['discount'] ?? 0) > 0): ?>
            <div class="total-row">
                <span class="lbl">Discount</span>
                <span style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format((float) $sale['discount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row t-grand">
                <span>Total</span>
                <span><?= APP_CURRENCY ?> <?= number_format((float) $sale['grand_total'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if (abs((float) ($sale['prev_balance'] ?? 0)) > 0.001): ?>
            <div class="total-row t-prev">
                <span class="lbl">Previous Balance</span>
                <span><?= APP_CURRENCY ?> <?= number_format((float) $sale['prev_balance'], DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row t-outstanding">
                <span>Total Outstanding</span>
                <span><?= APP_CURRENCY ?> <?= number_format((float) $sale['total_balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php elseif (($sale['balance'] ?? 0) > 0): ?>
            <div class="total-row t-outstanding">
                <span>Total Outstanding</span>
                <span><?= APP_CURRENCY ?> <?= number_format((float) $sale['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($sale['notes'])): ?>
    <div class="notes-box">
        <strong>Notes</strong>
        <?= nl2br(htmlspecialchars($sale['notes'])) ?>
    </div>
    <?php endif; ?>

    <div class="inv-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you for your business!') ?></p>
        <p style="margin-top:3px;font-size:7.5px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
    </div>
