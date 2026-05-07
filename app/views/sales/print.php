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
   A5 LAYOUT
══════════════════════════════════ */
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9px; color: #1a1a1a; background: #fff; }
.invoice-wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 8mm 10mm; }

.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; border-bottom: 2px solid #1e3a5f; padding-bottom: 7px; }
.company-name { font-size: 12px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 8px; color: #555; margin-top: 3px; line-height: 1.5; }
.inv-title { text-align: right; }
.inv-title h1 { font-size: 18px; font-weight: 800; color: #1e3a5f; letter-spacing: 1px; }
.inv-title p { font-size: 8px; color: #444; margin-top: 3px; line-height: 1.7; }

.customer-row { background:#f8f9ff; border:1px solid #e0e7ff; border-radius:5px; padding:5px 8px; margin-bottom:8px; display:flex; align-items:center; gap:10px; font-size:9px; }
.customer-row .clbl { font-weight:700; text-transform:uppercase; color:#6366f1; letter-spacing:0.5px; white-space:nowrap; }
.customer-row .cname { font-weight:700; color:#1a1a1a; }
.customer-row .cphone { color:#555; }

table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
thead th { background:#1e3a5f; color:#fff; padding:5px 6px; font-size:8px; text-transform:uppercase; letter-spacing:0.3px; white-space:nowrap; }
tbody td { padding:4px 6px; border-bottom:1px solid #f0f0f0; font-size:8.5px; vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:nth-child(even) { background:#f8f9ff; }
.item-name { font-weight:600; }
.item-sku  { font-size:7.5px; color:#888; }

.totals-section { display:flex; justify-content:flex-end; margin-bottom:8px; }
.totals-box { width:55mm; }
.total-row { display:flex; justify-content:space-between; padding:3px 0; font-size:8.5px; border-bottom:1px solid #f0f0f0; }
.total-row:last-child { border-bottom:none; }
.total-row .lbl { color:#555; }
.t-grand { font-size:10px; font-weight:800; color:#1e3a5f; border-top:1.5px solid #1e3a5f !important; padding-top:5px !important; margin-top:2px; }
.t-prev { color:#92400e !important; border-top:1px dashed #fde68a !important; padding-top:4px !important; margin-top:2px; }
.t-outstanding { font-size:10px; font-weight:800; color:#6366f1; border-top:1.5px solid #6366f1 !important; padding-top:5px !important; margin-top:2px; }

.notes-box { margin-bottom:7px; padding:5px 7px; background:#fffbeb; border:1px solid #fde68a; border-radius:4px; font-size:8px; color:#555; }
.notes-box strong { display:block; color:#888; font-size:7.5px; text-transform:uppercase; margin-bottom:2px; }
.inv-footer { border-top:1px solid #e5e7eb; padding-top:6px; text-align:center; color:#888; font-size:8px; }

@media screen { body { background:#e5e7eb; padding:20px; } .invoice-wrap { box-shadow:0 2px 16px rgba(0,0,0,0.15); background:#fff; } }
@media print {
    body { background:#fff; padding:0; }
    .no-print { display:none !important; }
    .invoice-wrap { max-width:100%; width:100%; padding:0; margin:0; }
    @page { size: A5 portrait; margin: 8mm 10mm; }
}

<?php else: ?>
body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: <?= $thermal ? '8px 2px' : '20px 4px' ?>;
}

.no-print {
    padding: 8px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    margin: -20px -20px 16px;
}
.no-print button {
    background: #6366f1; color: #fff; border: none;
    padding: 6px 16px; border-radius: 5px; font-size: 13px;
    cursor: pointer; margin-right: 6px;
}
.no-print button.close-btn { background: #e5e7eb; color: #444; }
.no-print button.edit-btn  { background: #f59e0b; }

.wrap {
    max-width: <?= $thermal ? '72mm' : '480px' ?>;
    margin: 0 auto;
    padding: <?= $thermal ? '8px 3px' : '16px 6px' ?>;
    border: <?= $thermal ? 'none' : '1px solid #e5e7eb' ?>;
    border-radius: <?= $thermal ? '0' : '10px' ?>;
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #000; }
.company-info { font-size: <?= $thermal ? '9px' : '10px' ?>; color: #666; margin-top: 4px; line-height: 1.6; }

.receipt-row {
    display: flex; justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #666; }
.receipt-row .val { font-weight: 600; color: #1a1a1a; text-align: right; }

.items-section {
    margin: 16px 0;
    padding: 12px 0;
    border-top: 2px dashed #e5e7eb;
    border-bottom: 2px dashed #e5e7eb;
}
.items-header {
    display: flex;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 6px;
}
.items-header .col-item { flex: 1; }
.items-header .col-qty  { width: 40px; text-align: center; }
.items-header .col-rate { width: 70px; text-align: right; }
.items-header .col-amt  { width: 80px; text-align: right; }

.item-row {
    padding: 6px 0;
    border-bottom: 1px dotted #f3f4f6;
    font-size: 11px;
}
.item-row:last-child { border-bottom: none; }
.item-name { font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
.item-line { display: flex; margin-top: 3px; }
.item-line .col-item { flex: 1; color: #666; font-size: 10px; }
.item-line .col-qty  { width: 40px; text-align: center; }
.item-line .col-rate { width: 70px; text-align: right; color: #666; }
.item-line .col-amt  { width: 80px; text-align: right; font-weight: 700; }

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
.amount-label { font-size: 10px; color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
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
    body { padding: 0; background: #fff; -webkit-print-color-adjust: economy; print-color-adjust: economy; }
    .no-print { display: none !important; }
    .wrap { border: none; padding: 0; width: 100%; max-width: 100%; }
    <?php if ($thermal): ?>
    @page { size: 72mm auto; margin: 2mm; }
    <?php else: ?>
    @page { margin: 8mm 3mm 8mm 3mm; }
    <?php endif; ?>
}
<?php endif; ?>
</style>
</head>
<body>

<?php if (!$thermal): ?>
<div class="no-print" style="text-align:center;padding:12px 0 0;margin-bottom:10px;">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="window.location='?page=sales&action=thermalPrint&id=<?= (int) $sale['id'] ?>&thermal=1&autoprint=1'" style="background:#059669;color:#fff;"><b>🖨 Thermal</b></button>
    <button type="button" id="btnExportPdf" onclick="exportPDF()" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('sales', 'edit') && ($sale['status'] ?? '') !== 'cancelled'): ?>
    <button onclick="window.location='?page=sales&action=edit&id=<?= (int) $sale['id'] ?>'" style="background:#f59e0b;color:#fff;"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=sales'">Close</button>
</div>

<div class="invoice-wrap">
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
        <span class="cname"><?= htmlspecialchars($sale['party_name']) ?></span>
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
</div>

<?php else: ?>
<?php
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
?>
<div class="no-print">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="window.location='?page=sales&action=print&id=<?= (int) $sale['id'] ?>'" style="background:#6366f1;color:#fff;"><b>⬚ A5</b></button>
    <button type="button" id="btnExportPdf" onclick="exportPDF()" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('sales', 'edit') && ($sale['status'] ?? '') !== 'cancelled'): ?>
    <button class="edit-btn" onclick="window.location='?page=sales&action=edit&id=<?= (int) $sale['id'] ?>'"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=sales'">Close</button>
</div>

<div class="wrap">
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars((string) ($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string) ($settings['company_address'] ?? ''))) ?><br>
            <?= htmlspecialchars((string) ($settings['company_phone'] ?? '')) ?>
        </div>
    </div>

    <div style="text-align:center;margin-bottom:14px;">
        <span style="display:inline-block;padding:4px 12px;border:2px solid #000;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#000;background:transparent;">
            <?= $isReturn ? '↩ Sale Return' : '↗ Sale Invoice' ?>
        </span>
    </div>

    <div class="receipt-row"><span class="lbl">Invoice No</span><span class="val"><?= htmlspecialchars((string) ($sale['invoice_no'] ?? '')) ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($sale['date'] ?? 'now')) ?></span></div>
    <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars((string) ($sale['party_name'] ?? '—')) ?></span></div>
    <?php if (!empty($sale['party_phone'])): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars((string) $sale['party_phone']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($sale['warehouse_name'])): ?>
    <div class="receipt-row"><span class="lbl">Warehouse</span><span class="val"><?= htmlspecialchars((string) $sale['warehouse_name']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($sale['salesman_name'])): ?>
    <div class="receipt-row"><span class="lbl">Salesman</span><span class="val"><?= htmlspecialchars((string) $sale['salesman_name']) ?></span></div>
    <?php endif; ?>

    <div class="items-section">
        <div class="items-header">
            <div class="col-item">Item</div>
            <div class="col-qty">Qty</div>
            <div class="col-rate">Rate</div>
            <div class="col-amt">Amount</div>
        </div>

        <?php foreach ($itemsList as $it):
            $qty  = (float) ($it['quantity'] ?? $it['qty'] ?? 0);
            $rate = (float) ($it['unit_price'] ?? $it['price'] ?? $it['rate'] ?? 0);
            $disc = (float) ($it['discount'] ?? 0);
            $line = (float) ($it['total'] ?? ($qty * $rate - $disc));
            $iname = (string) ($it['item_name'] ?? $it['product_name'] ?? $it['name'] ?? '—');
            ?>
        <div class="item-row">
            <div class="item-name"><?= htmlspecialchars($iname) ?></div>
            <div class="item-line">
                <div class="col-item">
                    <?php if ($disc > 0.001): ?>
                        Disc: <?= number_format($disc, DECIMAL_PLACES) ?>
                    <?php endif; ?>
                </div>
                <div class="col-qty"><?= rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') ?></div>
                <div class="col-rate"><?= number_format($rate, DECIMAL_PLACES) ?></div>
                <div class="col-amt"><?= number_format($line, DECIMAL_PLACES) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($thermalShowSubtotalBreakdown): ?>
    <div class="totals-section">
        <div class="receipt-row"><span class="lbl">Subtotal</span><span class="val"><?= APP_CURRENCY ?> <?= number_format($subtotal, DECIMAL_PLACES) ?></span></div>
        <?php if ($totalDisc > 0.001): ?>
        <div class="receipt-row"><span class="lbl">Discount</span><span class="val">- <?= APP_CURRENCY ?> <?= number_format($totalDisc, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
        <?php if ($taxAmount > 0.001): ?>
        <div class="receipt-row"><span class="lbl">Tax</span><span class="val"><?= APP_CURRENCY ?> <?= number_format($taxAmount, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
        <?php if ($shipping > 0.001): ?>
        <div class="receipt-row"><span class="lbl">Shipping</span><span class="val"><?= APP_CURRENCY ?> <?= number_format($shipping, DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="amount-box">
        <div class="amount-label">Grand Total</div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($grandTotal, DECIMAL_PLACES) ?></div>
    </div>

    <?php if ($thermalShowBalanceDue): ?>
    <div class="receipt-row"><span class="lbl" style="font-weight:700;">Balance Due</span><span class="val" style="font-weight:800;"><?= APP_CURRENCY ?> <?= number_format($dueAmount, DECIMAL_PLACES) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($sale['notes'])): ?>
    <div class="receipt-row"><span class="lbl">Notes</span><span class="val"><?= htmlspecialchars((string) $sale['notes']) ?></span></div>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:2px dashed #e5e7eb;">
        <div class="receipt-row">
            <span class="lbl">Previous Balance</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
        </div>
        <?php if ($paid > 0.001): ?>
        <div class="receipt-row">
            <span class="lbl">Paid Now</span>
            <span class="val">- <?= APP_CURRENCY ?> <?= number_format($paid, DECIMAL_PLACES) ?></span>
        </div>
        <?php endif; ?>
        <div class="receipt-row" style="border-top:2px solid #000;padding-top:8px;margin-top:4px;border-bottom:none;">
            <span class="lbl" style="font-weight:800;color:#000;font-size:12px;">Current Balance</span>
            <span class="val" style="font-weight:800;color:#000;font-size:13px;">
                <?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?>
            </span>
        </div>
    </div>

    <div class="footer">
        <p><?= htmlspecialchars((string) ($settings['invoice_footer'] ?? 'Thank you for your business!')) ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
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
        html2canvas: { scale: 2, useCORS: true },
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
</script>
</body>
</html>
