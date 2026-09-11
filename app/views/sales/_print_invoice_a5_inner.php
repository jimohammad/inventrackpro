<?php
/** @var array $sale */
/** @var array $settings */

if (!function_exists('thermalBiLabel')) {
    require __DIR__ . '/_thermal_labels_ar.php';
}

$companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
if ($companyNameAr === '') {
    $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
}
$companyAddressAr = trim((string) ($settings['company_address_ar'] ?? ''));

$invoiceFooter = trim((string) ($settings['invoice_footer'] ?? ''));
$useDefaultThankYou = ($invoiceFooter === '' || $invoiceFooter === 'Thank you for your business!');

$a5BiLabel = static function (string $key): string {
    $pair = $GLOBALS['thermalLabels'][$key] ?? ['en' => $key, 'ar' => ''];
    $en = htmlspecialchars((string) $pair['en'], ENT_QUOTES, 'UTF-8');
    $ar = htmlspecialchars((string) $pair['ar'], ENT_QUOTES, 'UTF-8');

    return '<span class="t-en">' . $en . '</span>'
        . '<bdi class="ar t-ar" dir="rtl" lang="ar">' . $ar . '</bdi>';
};
$a5Money = static function ($n, string $prefix = ''): string {
    return $prefix . APP_CURRENCY . '&nbsp;' . number_format((float) $n, DECIMAL_PLACES);
};

$totalQty = array_sum(array_column($sale['items'] ?? [], 'quantity'));
$qtyDisplay = abs($totalQty - round($totalQty)) < 0.001
    ? (string) (int) round($totalQty)
    : rtrim(rtrim(number_format((float) $totalQty, 2, '.', ''), '0'), '.');
$prevBalance = (float) ($sale['prev_balance'] ?? 0);
$hasPrev = abs($prevBalance) > 0.001;
$hasDueOnly = !$hasPrev && ((float) ($sale['balance'] ?? 0) > 0);
$hasDiscount = ((float) ($sale['discount'] ?? 0)) > 0;
$summaryRows = 1 + ($hasDiscount ? 1 : 0) + ($hasPrev ? 2 : ($hasDueOnly ? 1 : 0));
$qtyCell = '<td class="qty-cell" rowspan="' . (int) $summaryRows . '">'
    . $a5BiLabel('total_qty')
    . '<span class="qty-num">' . htmlspecialchars($qtyDisplay) . '</span>'
    . '</td>';
?>
    <div class="inv-header">
        <div>
            <div class="company-names">
                <span class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></span>
                <span class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></span>
            </div>
            <div class="company-info">
                <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
                <?php if ($companyAddressAr !== ''): ?>
                <span class="ar" dir="rtl" lang="ar"><?= nl2br(htmlspecialchars($companyAddressAr)) ?></span><br>
                <?php endif; ?>
                <?php
                $companyPhone = trim((string) ($settings['company_phone'] ?? ''));
                $companyEmail = trim((string) ($settings['company_email'] ?? ''));
                ?>
                <?php if ($companyPhone !== '' || $companyEmail !== ''): ?>
                <span class="company-contact">
                    <?php if ($companyPhone !== ''): ?>
                    Mobile: <span dir="ltr"><?= htmlspecialchars($companyPhone) ?></span>
                    <?php endif; ?>
                    <?php if ($companyPhone !== '' && $companyEmail !== ''): ?>
                    <span> · </span>
                    <?php endif; ?>
                    <?php if ($companyEmail !== ''): ?>
                    Email: <span dir="ltr"><?= htmlspecialchars($companyEmail) ?></span>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="inv-title">
            <h1><?= thermalBiLabel('invoice') ?></h1>
            <p>
                <strong># <?= htmlspecialchars($sale['invoice_no']) ?></strong><br>
                <?= thermalBiLabel('date') ?><span dir="ltr">: <?= date('d M Y', strtotime($sale['date'])) ?></span><br>
                <span class="inv-wh"><?= thermalBiLabel('warehouse') ?><span dir="ltr">: <?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?></span></span>
            </p>
        </div>
    </div>

    <table class="customer-card">
        <tr>
            <td>
                <div class="cust-lbl"><?= thermalBiLabel('customer') ?></div>
                <div class="cust-name"><?= htmlspecialchars((string) ($sale['party_name'] ?? '')) ?></div>
            </td>
            <?php if (!empty($sale['party_phone'])): ?>
            <td class="cust-phone">
                <div class="cust-lbl"><?= thermalBiLabel('phone') ?></div>
                <div class="cust-tel"><?= htmlspecialchars((string) $sale['party_phone']) ?></div>
            </td>
            <?php endif; ?>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:16px;text-align:center;">#</th>
                <th class="th-desc"><?= thermalBiLabelStacked('description') ?></th>
                <th style="width:40px;text-align:center;"><?= thermalBiLabelStacked('qty') ?></th>
                <th style="width:56px;text-align:right;"><?= thermalBiLabelStacked('price') ?></th>
                <th style="width:56px;text-align:right;"><?= thermalBiLabelStacked('amt') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $i => $item): ?>
            <tr>
                <td style="text-align:center;color:#888;"><?= $i + 1 ?></td>
                <td>
                    <span class="item-name"><?= thermalMixedBidiHtml((string) ($item['item_name'] ?? '')) ?></span>
                    <?php
                    $lineNameAr = trim((string) ($item['item_name_ar'] ?? ''));
                    if ($lineNameAr !== ''):
                    ?>
                    <div class="item-name-ar ar"><?= thermalMixedBidiHtml($lineNameAr) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= (int) $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format((float) $item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:700;"><?= number_format((float) $item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="inv-summary">
        <?php if ($hasDiscount): ?>
        <tr class="t-disc">
            <?= $qtyCell ?>
            <td class="t-lbl"><?= $a5BiLabel('discount') ?></td>
            <td class="t-amt">− <?= $a5Money($sale['discount']) ?></td>
        </tr>
        <tr class="t-grand">
            <td class="t-lbl"><?= $a5BiLabel($hasPrev ? 'this_invoice' : 'total') ?></td>
            <td class="t-amt"><?= $a5Money($sale['grand_total']) ?></td>
        </tr>
        <?php else: ?>
        <tr class="t-grand">
            <?= $qtyCell ?>
            <td class="t-lbl"><?= $a5BiLabel($hasPrev ? 'this_invoice' : 'total') ?></td>
            <td class="t-amt"><?= $a5Money($sale['grand_total']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($hasPrev): ?>
        <tr class="t-prev">
            <td class="t-lbl"><?= $a5BiLabel('previous_balance') ?></td>
            <td class="t-amt"><?= $a5Money($prevBalance, $prevBalance > 0.001 ? '+ ' : '') ?></td>
        </tr>
        <tr class="t-due">
            <td class="t-lbl"><?= $a5BiLabel('total_outstanding') ?></td>
            <td class="t-amt"><?= $a5Money($sale['total_balance']) ?></td>
        </tr>
        <?php elseif ($hasDueOnly): ?>
        <tr class="t-due">
            <td class="t-lbl"><?= $a5BiLabel('total_outstanding') ?></td>
            <td class="t-amt"><?= $a5Money($sale['balance']) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if (!empty($sale['notes'])): ?>
    <div class="notes-box">
        <strong><?= thermalBiLabel('notes') ?></strong>
        <?= nl2br(htmlspecialchars($sale['notes'])) ?>
    </div>
    <?php endif; ?>

    <div class="inv-footer">
        <?php if ($useDefaultThankYou): ?>
        <p><?= thermalBiLabel('thank_you') ?></p>
        <?php else: ?>
        <p><?= htmlspecialchars($invoiceFooter) ?></p>
        <p style="margin-top:2px;"><span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($GLOBALS['thermalLabels']['thank_you']['ar'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php endif; ?>
        <p style="margin-top:3px;font-size:7.5px;"><?= thermalBiLabel('printed') ?> <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
        <p style="margin-top:4px;font-size:7px;color:#666;"><?= thermalBiLabel('computer_generated') ?></p>
    </div>
