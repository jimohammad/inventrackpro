<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($sale['invoice_no'] ?? '') ?></title>
<?php
// Receipt/thermal layout: controller flag, any `thermal` query key (Hostinger paste behavior), or thermalPrint action (case-insensitive).
$salePrintThermal = isset($salePrintThermal) ? (bool) $salePrintThermal : false;
$act               = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
$thermal           = $salePrintThermal
    || (isset($isThermal) && $isThermal)
    || isset($_GET['thermal'])
    || ($act === 'thermalprint');
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 LAYOUT (bilingual EN/AR)
══════════════════════════════════ */
body { font-family: 'Segoe UI', Tahoma, 'Noto Naskh Arabic', Arial, sans-serif; font-size: 9px; color: #1a1a1a; background: #fff; }
.invoice-wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 8mm 10mm; }
.ar { font-family: 'Segoe UI', Tahoma, 'Noto Naskh Arabic', Arial, sans-serif; unicode-bidi: isolate; }

.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; border-bottom: 2px solid #1e3a5f; padding-bottom: 7px; }
.company-names { display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px; max-width: 92mm; }
.company-name { font-size: 12px; font-weight: 800; color: #1e3a5f; }
.company-name-ar { font-size: 11px; font-weight: 700; color: #1e3a5f; margin-top: 0; line-height: 1.35; text-align: left; }
.company-info { font-size: 8px; color: #555; margin-top: 3px; line-height: 1.5; }
.company-contact { white-space: nowrap; }
.inv-title { text-align: right; flex-shrink: 0; }
.inv-title h1 { font-size: 14px; font-weight: 800; color: #1e3a5f; letter-spacing: 0; line-height: 1.35; }
.inv-title p { font-size: 8px; color: #444; margin-top: 3px; line-height: 1.7; }
.inv-wh { white-space: nowrap; }

.customer-card { width:100%; margin-bottom:8px; border:1px solid #e0e7ff; border-radius:5px; background:#f8f9ff; border-collapse:separate; border-spacing:0; table-layout:fixed; }
.customer-card td { padding:6px 9px; vertical-align:top; border:none; font-size:9px; background:transparent; }
.customer-card tbody tr:nth-child(even) { background:transparent; }
.customer-card .cust-lbl { font-size:7px; font-weight:700; color:#6366f1; line-height:1.3; margin-bottom:2px; }
.customer-card .cust-name { font-size:9px; font-weight:700; color:#1a1a1a; line-height:1.35; }
.customer-card .cust-phone { width:46mm; border-left:1px solid #c7d2fe; background:#eef2ff; }
.customer-card .cust-tel { font-size:9px; font-weight:700; color:#1e3a5f; direction:ltr; unicode-bidi:isolate; letter-spacing:0.2px; }

table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; border-bottom: 1.5px solid #1e3a5f; }
table.items thead th { background:#e8eef5; color:#1e3a5f; padding:5px 6px; font-size:7.5px; text-transform:none; letter-spacing:0; white-space:normal; line-height:1.25; vertical-align:bottom; }
table.items thead th.th-desc { text-align:left; padding-left:16px; }
table.items tbody td { padding:4px 6px; border-bottom:1px solid #f0f0f0; font-size:8.5px; vertical-align:middle; }
table.items tbody tr:last-child td { border-bottom:none; }
table.items tbody tr:nth-child(even) { background:#f8f9ff; }
.item-name { font-weight:600; direction:ltr; unicode-bidi:isolate; }
.item-name .bidi-ltr,
.item-name .bidi-rtl,
.item-name-ar .bidi-ltr,
.item-name-ar .bidi-rtl { display:inline-block; vertical-align:baseline; }
.item-name .bidi-ltr,
.item-name-ar .bidi-ltr { direction:ltr; unicode-bidi:bidi-override; white-space:nowrap; }
.item-name .bidi-rtl,
.item-name-ar .bidi-rtl { direction:rtl; unicode-bidi:isolate; }
.item-name-ar { font-size:8px; font-weight:600; color:#1a1a1a; margin-top:1px; line-height:1.3; direction:rtl; unicode-bidi:isolate; text-align:right; }

.inv-summary { width:100%; border-collapse:collapse; margin:2px 0 8px; border:1px solid #c5d0e0; table-layout:fixed; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.inv-summary .qty-cell { width:28mm; text-align:center; vertical-align:middle; background:#f4f7fb; border-right:1px solid #c5d0e0; padding:8px 6px; }
.inv-summary .qty-cell .t-en { display:block; font-size:6.5px; font-weight:700; color:#5b6b82; letter-spacing:0.2px; }
.inv-summary .qty-cell .t-ar { display:block; font-size:6.5px; font-weight:600; color:#7b8aa0; margin-top:1px; }
.inv-summary .qty-num { display:block; margin-top:4px; font-size:16px; font-weight:800; color:#1e3a5f; line-height:1; letter-spacing:-0.3px; }
.inv-summary .t-lbl { text-align:left; padding:5px 8px; vertical-align:middle; line-height:1.2; }
.inv-summary .t-en { display:block; font-size:7.5px; font-weight:700; color:#334155; }
.inv-summary .t-ar { display:block; font-size:7px; font-weight:600; color:#64748b; margin-top:1px; }
.inv-summary .t-amt { width:42mm; text-align:right; white-space:nowrap; font-weight:700; font-size:8.5px; color:#0f172a; font-variant-numeric:tabular-nums; padding:5px 8px; vertical-align:middle; }
.inv-summary tr.t-disc .t-amt { color:#dc2626; }
.inv-summary tr.t-grand .t-lbl, .inv-summary tr.t-grand .t-amt { background:#eef2f7; }
.inv-summary tr.t-grand .t-lbl .t-en { color:#1e3a5f; font-size:8px; }
.inv-summary tr.t-grand .t-amt { color:#1e3a5f; font-size:9.5px; font-weight:800; }
.inv-summary tr.t-prev .t-lbl, .inv-summary tr.t-prev .t-amt { background:#fffbeb; }
.inv-summary tr.t-prev .t-lbl .t-en, .inv-summary tr.t-prev .t-lbl .t-ar { color:#92400e; }
.inv-summary tr.t-prev .t-amt { color:#92400e; font-weight:700; font-size:8px; }
.inv-summary tr.t-due .t-lbl, .inv-summary tr.t-due .t-amt { background:#fff; color:#1e3a5f; padding:7px 8px; border-top:1.5px solid #1e3a5f; }
.inv-summary tr.t-due .t-lbl .t-en, .inv-summary tr.t-due .t-lbl .t-ar { color:#1e3a5f; }
.inv-summary tr.t-due .t-lbl .t-ar { opacity:0.72; }
.inv-summary tr.t-due .t-amt { color:#1e3a5f; font-size:10.5px; font-weight:800; }

.notes-box { margin-bottom:7px; padding:5px 7px; background:#fffbeb; border:1px solid #fde68a; border-radius:4px; font-size:8px; color:#555; }
.notes-box strong { display:block; color:#888; font-size:7.5px; text-transform:none; margin-bottom:2px; }
.inv-footer { border-top:1px solid #e5e7eb; padding-top:6px; text-align:center; color:#888; font-size:8px; }

.no-print {
    display:flex; flex-wrap:nowrap; justify-content:center; align-items:center;
    gap:6px; margin:0 auto 16px; padding:10px 12px;
    background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;
    width:max-content; max-width:100%; overflow-x:auto;
}
.no-print button {
    flex:0 0 auto; white-space:nowrap;
    background:#6366f1; color:#fff; border:none;
    padding:6px 12px; border-radius:5px; font-size:12px; cursor:pointer;
    font-family:system-ui,sans-serif;
}
.no-print .close-btn { background:#e5e7eb; color:#444; }

@media screen { body { background:#e5e7eb; padding:20px; } .invoice-wrap { box-shadow:0 2px 16px rgba(0,0,0,0.15); background:#fff; } }
@media print {
    body { background:#fff; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .no-print { display:none !important; }
    .invoice-wrap { max-width:100%; width:100%; padding:0; margin:0; }
    @page { size: A5 portrait; margin: 8mm 10mm; }
}

<?php else: ?>
<?php include __DIR__ . '/../partials/thermal_print_font.css.php'; ?>
/* Bilingual thermal: Arabic-capable stack (monospace often lacks Arabic glyphs) */
body {
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 8px 2px;
}
.ar {
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    unicode-bidi: isolate;
}
.company-name-ar {
    font-size: 13px;
    font-weight: 700;
    margin-top: 3px;
    line-height: 1.35;
}

.no-print {
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin: 0 auto 16px;
    width: max-content;
    max-width: 100%;
    overflow-x: auto;
}
.no-print button {
    flex: 0 0 auto;
    white-space: nowrap;
    background: #6366f1; color: #fff; border: none;
    padding: 6px 12px; border-radius: 5px; font-size: 12px;
    cursor: pointer;
    font-family: system-ui, sans-serif;
}
.no-print button.close-btn { background: #e5e7eb; color: #444; }
.no-print button.edit-btn  { background: #f59e0b; }

.wrap {
    max-width: 72mm;
    margin: 0 auto;
    padding: 8px 3px;
    border: none;
    border-radius: 0;
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #000; }
.company-info { font-size: 11px; color: #000; margin-top: 4px; line-height: 1.35; }

.receipt-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #000; font-size: 10.5px; max-width: 58%; line-height: 1.25; }
.receipt-row .val { font-weight: 400; color: #000; text-align: right; flex-shrink: 0; }

.items-section {
    margin: 16px 0;
    padding: 12px 0;
    border-top: 2px dashed #e5e7eb;
    border-bottom: 2px dashed #e5e7eb;
}
.items-header {
    display: flex;
    font-size: 9px;
    font-weight: 800;
    text-transform: none;
    letter-spacing: 0;
    color: #000;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 6px;
}
.items-header .col-item { flex: 1; line-height: 1.2; }
.items-header .col-qty  { width: 36px; text-align: center; line-height: 1.2; }
.items-header .col-rate { width: 56px; text-align: right; line-height: 1.2; }
.items-header .col-amt  { width: 62px; text-align: right; line-height: 1.2; }

.item-row {
    padding: 6px 0;
    border-bottom: 1px dotted #f3f4f6;
    font-size: 11px;
}
.item-row:last-child { border-bottom: none; }
.item-name { font-weight: 600; color: #1a1a1a; margin-bottom: 2px; direction: ltr; unicode-bidi: isolate; }
.item-name .bidi-ltr,
.item-name .bidi-rtl,
.item-name-ar .bidi-ltr,
.item-name-ar .bidi-rtl { display: inline-block; vertical-align: baseline; }
.item-name .bidi-ltr,
.item-name-ar .bidi-ltr { direction: ltr; unicode-bidi: bidi-override; white-space: nowrap; }
.item-name .bidi-rtl,
.item-name-ar .bidi-rtl { direction: rtl; unicode-bidi: isolate; }
.item-name-ar { font-size: 11px; font-weight: 600; color: #000; margin: 1px 0 2px; line-height: 1.3; direction: rtl; unicode-bidi: isolate; text-align: right; }
.item-line { display: flex; margin-top: 3px; }
.item-line .col-item { flex: 1; color: #666; font-size: 10px; }
.item-line .col-qty  { width: 36px; text-align: center; }
.item-line .col-rate { width: 56px; text-align: right; color: #666; }
.item-line .col-amt  { width: 62px; text-align: right; font-weight: 700; }

.items-total-qty-row {
    padding-top: 10px;
    margin-top: 6px;
    border-top: 1px solid #000;
    text-align: center;
}
.items-total-qty-center {
    font-weight: bold !important;
    font-size: 13px !important;
    color: #000 !important;
    text-transform: none;
    letter-spacing: 0;
    line-height: 1.3;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}
.items-total-qty-box {
    display: inline-block;
    border: 2px solid #000;
    border-radius: 6px;
    padding: 4px 12px;
    font-size: 18px !important;
    font-weight: bold !important;
    color: #000 !important;
    min-width: 40px;
    text-align: center;
    line-height: 1.2;
}

/* Thermal drivers often ignore numeric font-weight — <strong> reinforces bold */
.current-balance-row {
    border-top: 2px solid #000;
    padding-top: 8px;
    margin-top: 4px;
    border-bottom: none;
}
.current-balance-row .lbl,
.current-balance-row .val {
    font-weight: bold !important;
    font-size: 12px !important;
    color: #000 !important;
}
.current-balance-row .val {
    font-size: 13px !important;
}

.totals-section { margin-top: 12px; }
.totals-section .receipt-row { border-bottom: none; padding: 4px 0; }

.amount-box {
    margin: 14px 0;
    background: transparent;
    border: 2px solid #000;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.amount-label { font-size: 10px; color: #000; font-weight: 700; text-transform: none; letter-spacing: 0; line-height: 1.3; }
.amount-value { font-size: 26px; font-weight: 800; color: #000; margin-top: 4px; }

.footer {
    text-align: center;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 2px dashed #e5e7eb;
    font-size: 10px;
    color: #888;
}

@media print {
    body {
        padding: 0;
        background: #fff;
        font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
        font-weight: 400;
        -webkit-font-smoothing: none;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .no-print { display: none !important; }
    .wrap {
        border: none;
        padding: 0;
        width: 100%;
        max-width: 100%;
        font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    }
    .ar { font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif; }
    @page { size: 72mm auto; margin: 2mm; }
}
<?php endif; ?>
</style>
</head>
<body>

<?php if (!$thermal): ?>
<?php
    $a5MissingArabic = 0;
    foreach (($sale['items'] ?? []) as $__a5it) {
        if (trim((string) ($__a5it['item_name_ar'] ?? '')) === '') {
            $a5MissingArabic++;
        }
    }
?>
<div class="no-print">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="window.location='?page=sales&action=thermalPrint&id=<?= (int) $sale['id'] ?>&thermal=1&autoprint=1'" style="background:#059669;color:#fff;"><b>🖨 Thermal</b></button>
    <button type="button" id="btnExportPdf" onclick="exportPDF()" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('sales', 'edit') && ($sale['status'] ?? '') !== 'cancelled'): ?>
    <button onclick="window.location='?page=sales&action=edit&id=<?= (int) $sale['id'] ?>'" style="background:#f59e0b;color:#fff;"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=sales'">Close</button>
    <?php if ($a5MissingArabic > 0): ?>
    <span style="font-size:12px;color:#b45309;font-weight:600;margin-left:4px;">
        <?= (int) $a5MissingArabic ?> item(s) missing Arabic name — edit Items → Arabic Name, then reprint
    </span>
    <?php endif; ?>
</div>

<div class="invoice-wrap">
<?php include __DIR__ . '/_print_invoice_a5_inner.php'; ?>
</div>

<?php else: ?>
<?php
    require __DIR__ . '/_thermal_labels_ar.php';

    $previousBalance = (float) ($sale['prev_balance'] ?? 0);
    $currentBalance   = (float) ($sale['total_balance'] ?? 0);
    $itemsList        = $sale['items'] ?? [];
    $isReturn         = (($sale['type'] ?? 'sale') === 'return');
    $subtotal         = (float) ($sale['subtotal'] ?? 0);
    $totalDisc        = (float) ($sale['total_discount'] ?? $sale['discount'] ?? 0);
    $taxAmount        = (float) ($sale['tax_amount'] ?? $sale['tax'] ?? 0);
    $shipping         = (float) ($sale['shipping'] ?? 0);
    $grandTotal       = (float) ($sale['grand_total'] ?? $sale['total'] ?? 0);
    $paid             = (float) ($sale['amount_paid'] ?? $sale['paid_amount'] ?? $sale['paid'] ?? 0);
    $dueAmount        = (float) ($sale['balance_due'] ?? $sale['balance'] ?? max(0, $grandTotal - $paid));

    // Thermal receipt: avoid repeating the same figure as Subtotal, Grand Total, Balance Due, and "This Invoice"
    $thermalShowSubtotalBreakdown = $totalDisc > 0.001 || $taxAmount > 0.001 || $shipping > 0.001
        || abs($subtotal - $grandTotal) > 0.001;
    $thermalShowBalanceDue        = $dueAmount > 0.001 && abs($dueAmount - $grandTotal) > 0.001;

    $totalQty = 0.0;
    foreach ($itemsList as $__it) {
        $totalQty += (float) ($__it['quantity'] ?? $__it['qty'] ?? 0);
    }

    $companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
    if ($companyNameAr === '') {
        $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
    }
    $companyAddressAr = trim((string) ($settings['company_address_ar'] ?? ''));

    $invoiceFooter = trim((string) ($settings['invoice_footer'] ?? ''));
    $useDefaultThankYou = ($invoiceFooter === '' || $invoiceFooter === 'Thank you for your business!');

    $missingArabicNames = 0;
    foreach ($itemsList as $__it) {
        if (trim((string) ($__it['item_name_ar'] ?? $__it['name_ar'] ?? '')) === '') {
            $missingArabicNames++;
        }
    }
?>
<div class="no-print">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="window.location='?page=sales&action=print&id=<?= (int) $sale['id'] ?>'" style="background:#6366f1;color:#fff;"><b>⬚ A5</b></button>
    <button type="button" id="btnExportPdf" onclick="exportPDF()" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('sales', 'edit') && ($sale['status'] ?? '') !== 'cancelled'): ?>
    <button class="edit-btn" onclick="window.location='?page=sales&action=edit&id=<?= (int) $sale['id'] ?>'"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=sales'">Close</button>
    <?php if ($missingArabicNames > 0): ?>
    <span style="font-size:12px;color:#b45309;font-weight:600;margin-left:4px;">
        <?= (int) $missingArabicNames ?> item(s) missing Arabic name — edit Items → Arabic Name, then reprint
    </span>
    <?php endif; ?>
</div>

<div class="wrap">
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars((string) ($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string) ($settings['company_address'] ?? ''))) ?><br>
            <?php if ($companyAddressAr !== ''): ?>
            <span class="ar" dir="rtl" lang="ar"><?= nl2br(htmlspecialchars($companyAddressAr)) ?></span><br>
            <?php endif; ?>
            <?= htmlspecialchars((string) ($settings['company_phone'] ?? '')) ?>
        </div>
    </div>

    <div style="text-align:center;margin-bottom:14px;">
        <span style="display:inline-block;padding:4px 12px;border:2px solid #000;font-size:10px;font-weight:700;letter-spacing:0;color:#000;background:transparent;line-height:1.35;">
            <?= $isReturn ? '↩ ' . thermalBiLabel('sale_return') : '↗ ' . thermalBiLabel('sale_invoice') ?>
        </span>
    </div>

    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('invoice_no') ?></span><span class="val"><?= htmlspecialchars((string) ($sale['invoice_no'] ?? '')) ?></span></div>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('date') ?></span><span class="val"><?= date('d M Y', strtotime($sale['date'] ?? 'now')) ?></span></div>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('customer') ?></span><span class="val"><?= htmlspecialchars((string) ($sale['party_name'] ?? '—')) ?></span></div>
    <?php if (!empty($sale['party_phone'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('phone') ?></span><span class="val"><?= htmlspecialchars((string) $sale['party_phone']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($sale['warehouse_name'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('warehouse') ?></span><span class="val"><?= htmlspecialchars((string) $sale['warehouse_name']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($sale['salesman_name'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('salesman') ?></span><span class="val"><?= htmlspecialchars((string) $sale['salesman_name']) ?></span></div>
    <?php endif; ?>

    <div class="items-section">
        <div class="items-header">
            <div class="col-item"><?= thermalBiLabelStacked('item') ?></div>
            <div class="col-qty"><?= thermalBiLabelStacked('qty') ?></div>
            <div class="col-rate"><?= thermalBiLabelStacked('rate') ?></div>
            <div class="col-amt"><?= thermalBiLabelStacked('amount') ?></div>
        </div>

        <?php foreach ($itemsList as $it):
            $qty  = (float) ($it['quantity'] ?? $it['qty'] ?? 0);
            $rate = (float) ($it['unit_price'] ?? $it['price'] ?? $it['rate'] ?? 0);
            $disc = (float) ($it['discount'] ?? 0);
            $line = (float) ($it['total'] ?? ($qty * $rate - $disc));
            $iname   = (string) ($it['item_name'] ?? $it['product_name'] ?? $it['name'] ?? '—');
            $inameAr = trim((string) ($it['item_name_ar'] ?? $it['name_ar'] ?? ''));
            ?>
        <div class="item-row">
            <div class="item-name"><?= thermalMixedBidiHtml($iname) ?></div>
            <?php if ($inameAr !== ''): ?>
            <div class="item-name-ar ar"><?= thermalMixedBidiHtml($inameAr) ?></div>
            <?php endif; ?>
            <div class="item-line">
                <div class="col-item">
                    <?php if ($disc > 0.001): ?>
                        <?= thermalBiLabel('disc') ?>: <?= number_format($disc, DECIMAL_PLACES) ?>
                    <?php endif; ?>
                </div>
                <div class="col-qty"><?= rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') ?></div>
                <div class="col-rate"><?= number_format($rate, DECIMAL_PLACES) ?></div>
                <div class="col-amt"><?= number_format($line, DECIMAL_PLACES) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="items-total-qty-row">
            <div class="items-total-qty-center">
                <strong><?= thermalBiLabel('total_qty') ?></strong>
                <span class="items-total-qty-box"><strong><?= rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') ?></strong></span>
            </div>
        </div>
    </div>

    <?php if ($thermalShowSubtotalBreakdown): ?>
    <div class="totals-section">
        <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('subtotal') ?></span><span class="val"><?= APP_CURRENCY ?> <?= number_format($subtotal, DECIMAL_PLACES) ?></span></div>
        <?php if ($totalDisc > 0.001): ?>
        <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('discount') ?></span><span class="val">- <?= APP_CURRENCY ?> <?= number_format($totalDisc, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
        <?php if ($taxAmount > 0.001): ?>
        <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('tax') ?></span><span class="val"><?= APP_CURRENCY ?> <?= number_format($taxAmount, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
        <?php if ($shipping > 0.001): ?>
        <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('shipping') ?></span><span class="val"><?= APP_CURRENCY ?> <?= number_format($shipping, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="amount-box">
        <div class="amount-label"><?= thermalBiLabel('grand_total') ?></div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($grandTotal, DECIMAL_PLACES) ?></div>
    </div>

    <?php if ($thermalShowBalanceDue): ?>
    <div class="receipt-row"><span class="lbl" style="font-weight:700;"><?= thermalBiLabel('balance_due') ?></span><span class="val" style="font-weight:800;"><?= APP_CURRENCY ?> <?= number_format($dueAmount, DECIMAL_PLACES) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($sale['notes'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('notes') ?></span><span class="val"><?= htmlspecialchars((string) $sale['notes']) ?></span></div>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:2px dashed #e5e7eb;">
        <div class="receipt-row">
            <span class="lbl"><?= thermalBiLabel('previous_balance') ?></span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
        </div>
        <?php // This invoice must appear here or the column does not add up:
              // previous + this invoice − paid = current balance. ?>
        <div class="receipt-row">
            <span class="lbl"><?= thermalBiLabel('this_invoice') ?></span>
            <span class="val"><?= $isReturn ? '-' : '+' ?> <?= APP_CURRENCY ?> <?= number_format($grandTotal, DECIMAL_PLACES) ?></span>
        </div>
        <?php if ($paid > 0.001): ?>
        <div class="receipt-row">
            <span class="lbl"><?= thermalBiLabel('paid_now') ?></span>
            <span class="val">- <?= APP_CURRENCY ?> <?= number_format($paid, DECIMAL_PLACES) ?></span>
        </div>
        <?php endif; ?>
        <div class="receipt-row current-balance-row">
            <span class="lbl"><strong><?= thermalBiLabel('current_balance') ?></strong></span>
            <span class="val"><strong><?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?></strong></span>
        </div>
    </div>

    <div class="footer">
        <?php if ($useDefaultThankYou): ?>
        <p><?= thermalBiLabel('thank_you') ?></p>
        <?php else: ?>
        <p><?= htmlspecialchars($invoiceFooter) ?></p>
        <p style="margin-top:2px;"><span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($thermalLabels['thank_you']['ar'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php endif; ?>
        <p style="margin-top:4px;"><?= thermalBiLabel('printed') ?> <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
        <p style="margin-top:8px;font-size:9px;color:#666;"><?= thermalBiLabel('computer_generated') ?></p>
    </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var btn = document.getElementById('btnExportPdf');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<b>Generating...</b>';
    }
    var el = document.querySelector('.invoice-wrap') || document.querySelector('.wrap');
    var isThermal = <?= $thermal ? 'true' : 'false' ?>;
    html2pdf().set({
        margin: isThermal ? [10, 12, 10, 12] : [8, 10, 8, 10],
        filename: '<?= htmlspecialchars($sale['invoice_no'] ?? 'invoice', ENT_QUOTES) ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            onclone: function (doc) {
                doc.querySelectorAll('.bidi-ltr').forEach(function (el) {
                    el.style.display = 'inline-block';
                    el.style.direction = 'ltr';
                    el.style.unicodeBidi = 'bidi-override';
                    el.style.whiteSpace = 'nowrap';
                });
                doc.querySelectorAll('.bidi-rtl').forEach(function (el) {
                    el.style.display = 'inline-block';
                    el.style.direction = 'rtl';
                    el.style.unicodeBidi = 'isolate';
                });
            }
        },
        jsPDF: { unit: 'mm', format: isThermal ? 'a7' : 'a5', orientation: 'portrait' }
    }).from(el).save().then(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<b>⤓ PDF</b>';
        }
    });
}

window.addEventListener('load', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(function() { window.print(); }, 400);
    }
    if (params.get('autopdf') === '1') {
        setTimeout(function() { exportPDF(); }, 600);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    e.preventDefault();
    window.location.href = '?page=dashboard';
});
</script>
</body>
</html>
