<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase <?= htmlspecialchars($purchase['invoice_no'] ?? '') ?></title>
<?php
$purchasePrintThermal = isset($purchasePrintThermal) ? (bool) $purchasePrintThermal : false;
$act                  = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
$thermal              = $purchasePrintThermal
    || (isset($isThermal) && $isThermal)
    || isset($_GET['thermal'])
    || ($act === 'thermalprint');
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 / FULL LAYOUT
══════════════════════════════════ */
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px; color: #1a1a1a;
    background: #fff; padding: 20px;
}
.invoice-wrap { max-width: 800px; margin: 0 auto; padding: 24px; }

.inv-header { display: flex; justify-content: space-between; margin-bottom: 24px; }
.company-name { font-size: 20px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }
.inv-title { text-align: right; }
.inv-title h1 { font-size: 24px; font-weight: 800; color: #8b5cf6; letter-spacing: 1px; }
.inv-title p { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }

.party-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
.party-box { background: #f8f9ff; border: 1px solid #e0e7ff; border-radius: 8px; padding: 12px 16px; min-width: 200px; }
.party-box label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #6366f1; letter-spacing: 0.5px; }
.party-box p { font-size: 12px; font-weight: 600; color: #1a1a1a; margin-top: 2px; }
.party-box small { color: #666; }

table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
thead th {
    background: #1e3a5f; color: #fff;
    padding: 8px 10px; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.4px;
}
tbody td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:nth-child(even) { background: #f8f9ff; }

.totals-section { display: flex; justify-content: flex-end; margin-bottom: 20px; }
.totals-box { min-width: 240px; }
.total-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
.total-row:last-child { border-bottom: none; }
.total-row.grand { font-size: 14px; font-weight: 800; color: #1e3a5f; border-top: 2px solid #1e3a5f; padding-top: 8px; margin-top: 4px; }
.total-row .label { color: #666; }

.status-badge {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
}
.status-paid     { background: #d1fae5; color: #065f46; }
.status-partial  { background: #fef3c7; color: #92400e; }
.status-confirmed{ background: #dbeafe; color: #1e40af; }

.inv-footer { border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center; color: #888; font-size: 11px; }

.no-print {
    display: flex; flex-wrap: nowrap; justify-content: center; align-items: center;
    gap: 6px; margin: 0 auto 16px; padding: 10px 12px;
    background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px;
    width: max-content; max-width: 100%; overflow-x: auto;
}
.no-print button {
    flex: 0 0 auto; white-space: nowrap;
    background: #8b5cf6; color: #fff; border: none;
    padding: 7px 16px; border-radius: 5px; font-size: 13px; cursor: pointer;
    font-family: system-ui, sans-serif;
}
.no-print .close-btn { background: #f3f4f6; color: #444; border: 1px solid #e5e7eb; }

@media print {
    body { padding: 0; }
    .no-print { display: none !important; }
    .invoice-wrap { padding: 12px; }
    @page { margin: 10mm; }
}
<?php else: ?>
/* ══════════════════════════════════
   THERMAL — 72mm Receipt
══════════════════════════════════ */
<?php include __DIR__ . '/../partials/thermal_print_font.css.php'; ?>
body {
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 8px 2px;
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
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #000; }
.company-info { font-size: 11px; color: #000; margin-top: 4px; line-height: 1.35; }

.receipt-row {
    display: flex; justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #000; }
.receipt-row .val { font-weight: 400; color: #000; text-align: right; }

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

.items-total-qty-row {
    padding-top: 10px;
    margin-top: 6px;
    border-top: 1px solid #000;
    text-align: center;
}
.items-total-qty-center {
    font-weight: bold !important;
    font-size: 16px !important;
    color: #000 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
    body {
        padding: 0;
        background: #fff;
        font-family: monospace;
        font-weight: 400;
        -webkit-font-smoothing: none;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .no-print { display: none !important; }
    .wrap { border: none; padding: 0; width: 100%; max-width: 100%; font-family: monospace; }
    @page { size: 72mm auto; margin: 2mm; }
}
<?php endif; ?>
</style>
</head>
<body>

<?php if (!$thermal): ?>
<div class="no-print">
    <button onclick="window.print()"><b>⎙ Print</b></button>
    <button onclick="window.location='?page=purchases&action=thermalPrint&id=<?= (int) $purchase['id'] ?>&thermal=1&autoprint=1'" style="background:#059669;color:#fff;"><b>🖨 Thermal</b></button>
    <button class="close-btn" onclick="window.location='?page=purchases&action=detail&id=<?= (int) $purchase['id'] ?>'">Close</button>
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
            <h1>PURCHASE</h1>
            <p>
                <strong># <?= htmlspecialchars($purchase['invoice_no'] ?? '') ?></strong><br>
                <?php if (!empty($purchase['supplier_invoice_no'])): ?>
                Supplier Ref: <strong><?= htmlspecialchars($purchase['supplier_invoice_no']) ?></strong><br>
                <?php endif; ?>
                Date: <?= date('d M Y', strtotime($purchase['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($purchase['warehouse_name'] ?? '—') ?>
            </p>
        </div>
    </div>

    <div class="party-section">
        <div class="party-box">
            <label>Supplier</label>
            <p><?= htmlspecialchars($purchase['party_name']) ?></p>
            <?php if (!empty($purchase['party_phone'])): ?>
            <small><?= htmlspecialchars($purchase['party_phone']) ?></small>
            <?php endif; ?>
        </div>
        <div class="party-box">
            <label>Payment Status</label>
            <p style="margin-top:6px;">
                <span class="status-badge status-<?= htmlspecialchars($purchase['status'] ?? '') ?>">
                    <?= htmlspecialchars(ucfirst($purchase['status'] ?? '')) ?>
                </span>
            </p>
            <?php if ((float) ($purchase['balance'] ?? 0) > 0): ?>
            <small style="color:#dc2626;">Balance: <?= APP_CURRENCY ?> <?= number_format($purchase['balance'], DECIMAL_PLACES) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Item</th>
                <th style="width:50px;text-align:center;">Qty</th>
                <th style="width:100px;text-align:right;">Price</th>
                <th style="width:110px;text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchase['items'] as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                    <?php if (!empty($item['sku'])): ?>
                    <br><small style="color:#888;"><?= htmlspecialchars((string) $item['sku']) ?></small>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:600;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span class="label">Subtotal</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['subtotal'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if ((float) ($purchase['discount'] ?? 0) > 0): ?>
            <div class="total-row">
                <span class="label">Discount</span>
                <span style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format($purchase['discount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>Total</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['grand_total'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if ((float) ($purchase['paid_amount'] ?? 0) > 0): ?>
            <div class="total-row" style="color:#059669;">
                <span class="label">Paid</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['paid_amount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float) ($purchase['balance'] ?? 0) > 0): ?>
            <div class="total-row" style="color:#dc2626;font-weight:700;">
                <span>Balance Due</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($purchase['notes'])): ?>
    <div style="margin-bottom:16px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;">
        <p style="font-size:11px;color:#888;font-weight:700;margin-bottom:3px;">NOTES</p>
        <p style="font-size:12px;color:#555;"><?= nl2br(htmlspecialchars($purchase['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <div class="inv-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you for your business!') ?></p>
        <p style="margin-top:4px;">Printed on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars(Auth::name()) ?></p>
    </div>
</div>

<?php else: ?>
<?php
    $itemsList  = $purchase['items'] ?? [];
    $subtotal   = (float) ($purchase['subtotal'] ?? 0);
    $discount   = (float) ($purchase['discount'] ?? 0);
    $grandTotal = (float) ($purchase['grand_total'] ?? 0);
    $paid       = (float) ($purchase['paid_amount'] ?? 0);
    $dueAmount  = (float) ($purchase['balance'] ?? max(0, $grandTotal - $paid));
    $prevBal    = (float) ($purchase['prev_balance'] ?? 0);
    $currBal    = (float) ($purchase['total_balance'] ?? 0);

    $thermalShowSubtotalBreakdown = $discount > 0.001 || abs($subtotal - $grandTotal) > 0.001;
    $thermalShowBalanceDue        = $dueAmount > 0.001 && abs($dueAmount - $grandTotal) > 0.001;

    $totalQty = 0.0;
    foreach ($itemsList as $__it) {
        $totalQty += (float) ($__it['quantity'] ?? 0);
    }
?>
<div class="no-print">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="window.location='?page=purchases&action=print&id=<?= (int) $purchase['id'] ?>'" style="background:#6366f1;color:#fff;"><b>⬚ A5</b></button>
    <?php if (Auth::can('purchases', 'edit') && ($purchase['status'] ?? '') !== 'cancelled'): ?>
    <button class="edit-btn" onclick="window.location='?page=purchases&action=edit&id=<?= (int) $purchase['id'] ?>'"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=purchases&action=detail&id=<?= (int) $purchase['id'] ?>'">Close</button>
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
            Purchase
        </span>
    </div>

    <div class="receipt-row"><span class="lbl">Ref No</span><span class="val"><?= htmlspecialchars((string) ($purchase['invoice_no'] ?? '')) ?></span></div>
    <?php if (!empty($purchase['supplier_invoice_no'])): ?>
    <div class="receipt-row"><span class="lbl">Supplier Ref</span><span class="val"><?= htmlspecialchars((string) $purchase['supplier_invoice_no']) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($purchase['date'] ?? 'now')) ?></span></div>
    <div class="receipt-row"><span class="lbl">Supplier</span><span class="val"><?= htmlspecialchars((string) ($purchase['party_name'] ?? '—')) ?></span></div>
    <?php if (!empty($purchase['party_phone'])): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars((string) $purchase['party_phone']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($purchase['warehouse_name'])): ?>
    <div class="receipt-row"><span class="lbl">Warehouse</span><span class="val"><?= htmlspecialchars((string) $purchase['warehouse_name']) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl">Status</span><span class="val"><?= htmlspecialchars(ucfirst((string) ($purchase['status'] ?? ''))) ?></span></div>

    <div class="items-section">
        <div class="items-header">
            <div class="col-item">Item</div>
            <div class="col-qty">Qty</div>
            <div class="col-rate">Rate</div>
            <div class="col-amt">Amount</div>
        </div>

        <?php foreach ($itemsList as $it):
            $qty   = (float) ($it['quantity'] ?? 0);
            $rate  = (float) ($it['unit_price'] ?? 0);
            $line  = (float) ($it['total'] ?? ($qty * $rate));
            $iname = (string) ($it['item_name'] ?? '—');
            $sku   = (string) ($it['sku'] ?? '');
            ?>
        <div class="item-row">
            <div class="item-name"><?= htmlspecialchars($iname) ?></div>
            <div class="item-line">
                <div class="col-item"><?= $sku !== '' ? htmlspecialchars($sku) : '' ?></div>
                <div class="col-qty"><?= rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') ?></div>
                <div class="col-rate"><?= number_format($rate, DECIMAL_PLACES) ?></div>
                <div class="col-amt"><?= number_format($line, DECIMAL_PLACES) ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="items-total-qty-row">
            <div class="items-total-qty-center">
                <strong>Total Qty</strong>
                <span class="items-total-qty-box"><strong><?= rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') ?></strong></span>
            </div>
        </div>
    </div>

    <?php if ($thermalShowSubtotalBreakdown): ?>
    <div class="totals-section">
        <div class="receipt-row"><span class="lbl">Subtotal</span><span class="val"><?= APP_CURRENCY ?> <?= number_format($subtotal, DECIMAL_PLACES) ?></span></div>
        <?php if ($discount > 0.001): ?>
        <div class="receipt-row"><span class="lbl">Discount</span><span class="val">- <?= APP_CURRENCY ?> <?= number_format($discount, DECIMAL_PLACES) ?></span></div>
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

    <?php if (!empty($purchase['notes'])): ?>
    <div class="receipt-row"><span class="lbl">Notes</span><span class="val"><?= htmlspecialchars((string) $purchase['notes']) ?></span></div>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:2px dashed #e5e7eb;">
        <div class="receipt-row">
            <span class="lbl">Previous Balance</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($prevBal, DECIMAL_PLACES) ?></span>
        </div>
        <?php if ($paid > 0.001): ?>
        <div class="receipt-row">
            <span class="lbl">Paid Now</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($paid, DECIMAL_PLACES) ?></span>
        </div>
        <?php endif; ?>
        <div class="receipt-row current-balance-row">
            <span class="lbl"><strong>Current Balance</strong></span>
            <span class="val"><strong><?= APP_CURRENCY ?> <?= number_format($currBal, DECIMAL_PLACES) ?></strong></span>
        </div>
    </div>

    <div class="footer">
        <p><?= htmlspecialchars((string) ($settings['invoice_footer'] ?? 'Thank you for your business!')) ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
        <p style="margin-top:8px;font-size:9px;color:#666;">This is a computer generated receipt.</p>
    </div>
</div>
<?php endif; ?>

<script>
window.addEventListener('load', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(function() { window.print(); }, 400);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    e.preventDefault();
    window.location.href = '?page=purchases&action=detail&id=<?= (int) ($purchase['id'] ?? 0) ?>';
});
</script>
</body>
</html>
