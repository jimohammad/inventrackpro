<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= $sale['invoice_no'] ?></title>
<?php $thermal = isset($_GET['thermal']); ?>
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
/* ══════════════════════════════════
   THERMAL LAYOUT — Receipt Style
══════════════════════════════════ */
body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; background: #fff; padding: 20px 4px; }

.no-print { padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; margin: -20px -4px 16px; }
.no-print button { background: #6366f1; color: #fff; border: none; padding: 6px 16px; border-radius: 5px; font-size: 13px; cursor: pointer; margin-right: 6px; }
.no-print button.close-btn { background: #e5e7eb; color: #444; }

.wrap { max-width: 480px; margin: 0 auto; padding: 16px 6px; border: 1px solid #e5e7eb; border-radius: 10px; }

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 10px; color: #666; margin-top: 4px; line-height: 1.6; }

.receipt-title { text-align: center; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #6366f1; margin: 14px 0 6px; }
.receipt-subtitle { text-align: center; margin-bottom: 14px; }
.receipt-subtitle span { display: inline-block; padding: 4px 18px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: #d1fae5; color: #065f46; }

.amount-box { margin: 18px 0; background: linear-gradient(135deg, #eff6ff, #eef2ff); border: 2px solid #c7d2fe; border-radius: 10px; padding: 14px; text-align: center; }
.amount-label { font-size: 10px; color: #6366f1; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.amount-value { font-size: 26px; font-weight: 800; color: #1e3a5f; margin-top: 4px; }

.receipt-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 11.5px; }
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #666; }
.receipt-row .val { font-weight: 600; color: #1a1a1a; text-align: right; }

.items-section { margin: 14px 0; padding: 10px 0; border-top: 2px dashed #e5e7eb; border-bottom: 2px dashed #e5e7eb; }
.items-section table { width: 100%; border-collapse: collapse; font-size: 11px; }
.items-section thead th { font-size: 10px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #000; padding: 3px 2px; }
.items-section tbody td { padding: 4px 2px; border-bottom: 1px dashed #e5e7eb; vertical-align: top; }
.items-section tbody tr:last-child td { border-bottom: none; }
.item-name { font-weight: 700; }

.balance-section { margin-top: 14px; padding-top: 12px; border-top: 2px dashed #e5e7eb; }
.balance-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 11.5px; }
.balance-row .lbl { color: #666; }
.balance-row .val { font-weight: 600; text-align: right; }
.balance-final { display: flex; justify-content: space-between; padding: 8px 0 0; border-top: 2px solid #1e3a5f; margin-top: 4px; }
.balance-final .lbl { font-weight: 800; color: #1e3a5f; font-size: 12px; }
.balance-final .val { font-weight: 800; font-size: 13px; }

.receipt-footer { text-align: center; margin-top: 18px; padding-top: 14px; border-top: 2px dashed #e5e7eb; font-size: 10px; color: #888; }

@media print {
    body { padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .wrap { border: none; padding: 0; width: 100%; max-width: 100%; }
    @page { margin: 8mm 3mm; }
}
<?php endif; ?>
</style>
</head>
<body>

<?php if (!$thermal): ?>
<!-- ════════════════════════════════
     A5 LAYOUT
════════════════════════════════ -->
<div class="no-print" style="text-align:center;padding:12px 0 0;margin-bottom:10px;">
    <button onclick="window.print()"
        style="background:#1e3a5f;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;">
        ⎙ Print A5
    </button>
    <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&thermal=1"
        style="background:#059669;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;margin-left:6px;">
        🖨 Thermal
    </a>
    <button onclick="exportPDF()"
        style="background:#dc2626;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;margin-left:6px;">
        ⤓ PDF
    </button>
    <?php if (Auth::isAdmin() && $sale['status'] !== 'cancelled'): ?>
    <button onclick="window.location='?page=sales&action=edit&id=<?= $sale['id'] ?>'"
        style="background:#f59e0b;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;margin-left:6px;">
        ✎ Edit
    </button>
    <?php endif; ?>
    <button onclick="window.close()"
        style="background:#f3f4f6;color:#444;border:1px solid #e5e7eb;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;margin-left:6px;">
        Close
    </button>
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
                <strong># <?= $sale['invoice_no'] ?></strong><br>
                Date: <?= date('d M Y', strtotime($sale['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?>
            </p>
        </div>
    </div>

    <div class="customer-row">
        <span class="clbl">Customer: </span>
        <span class="cname"><?= htmlspecialchars($sale['party_name']) ?></span>
        <?php if ($sale['party_phone']): ?>
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
                <td style="text-align:center;color:#888;"><?= $i+1 ?></td>
                <td>
                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                    <?php if ($item['sku']): ?>
                    <span class="item-sku"> &nbsp;<?= $item['sku'] ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:700;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <?php if ($sale['discount'] > 0): ?>
            <div class="total-row">
                <span class="lbl">Discount</span>
                <span style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format($sale['discount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row t-grand">
                <span>Total</span>
                <span><?= APP_CURRENCY ?> <?= number_format($sale['grand_total'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if (abs($sale['prev_balance']) > 0.001): ?>
            <div class="total-row t-prev">
                <span class="lbl">Previous Balance</span>
                <span><?= APP_CURRENCY ?> <?= number_format($sale['prev_balance'], DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row t-outstanding">
                <span>Total Outstanding</span>
                <span><?= APP_CURRENCY ?> <?= number_format($sale['total_balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php elseif ($sale['balance'] > 0): ?>
            <div class="total-row t-outstanding">
                <span>Total Outstanding</span>
                <span><?= APP_CURRENCY ?> <?= number_format($sale['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($sale['notes']): ?>
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
<!-- ════════════════════════════════
     THERMAL — Receipt Style (like payment receipt)
════════════════════════════════ -->
<div class="no-print">
    <button onclick="window.print()">⎙ Print</button>
    <button onclick="exportPDF()" style="background:#dc2626;">⤓ PDF</button>
    <a href="?page=sales&action=print&id=<?= $sale['id'] ?>"
        style="background:#6366f1;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:13px;font-weight:700;text-decoration:none;display:inline-block;margin-right:6px;">
        ⬚ A5
    </a>
    <?php if (Auth::isAdmin() && $sale['status'] !== 'cancelled'): ?>
    <button onclick="window.location='?page=sales&action=edit&id=<?= $sale['id'] ?>'" style="background:#f59e0b;">✎ Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=sales'">Close</button>
</div>

<div class="wrap">
    <!-- Header -->
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
            <?= htmlspecialchars($settings['company_phone'] ?? '') ?>
        </div>
    </div>

    <!-- Title -->
    <div class="receipt-title">Invoice</div>
    <div class="receipt-subtitle">
        <span style="background:#dbeafe;color:#1e40af;">&#128203; Sale Invoice</span>
    </div>

    <!-- Invoice # and date as receipt rows -->
    <div class="receipt-row"><span class="lbl">Invoice No</span><span class="val"><?= $sale['invoice_no'] ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($sale['date'])) ?></span></div>
    <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($sale['party_name']) ?></span></div>
    <?php if ($sale['party_phone']): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars($sale['party_phone']) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl">Branch</span><span class="val"><?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?></span></div>

    <!-- Items -->
    <div class="items-section">
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Item</th>
                    <th style="text-align:center;width:30px;">Qty</th>
                    <th style="text-align:right;width:55px;">Price</th>
                    <th style="text-align:right;width:55px;">Amt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td><span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span></td>
                    <td style="text-align:center;"><?= $item['quantity'] ?></td>
                    <td style="text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                    <td style="text-align:right;font-weight:700;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Grand Total box -->
    <?php if ($sale['discount'] > 0): ?>
    <div class="receipt-row"><span class="lbl">Subtotal</span><span class="val"><?= APP_CURRENCY ?> <?= number_format($sale['subtotal'], DECIMAL_PLACES) ?></span></div>
    <div class="receipt-row"><span class="lbl" style="color:#dc2626;">Discount</span><span class="val" style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format($sale['discount'], DECIMAL_PLACES) ?></span></div>
    <?php endif; ?>

    <div class="amount-box">
        <div class="amount-label">Total Amount</div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($sale['grand_total'], DECIMAL_PLACES) ?></div>
    </div>

    <!-- Balance section -->
    <?php if (abs($sale['prev_balance']) > 0.001): ?>
    <div class="balance-section">
        <div class="balance-row">
            <span class="lbl">Previous Balance</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($sale['prev_balance'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="balance-row">
            <span class="lbl" style="color:#1e40af;">This Invoice</span>
            <span class="val" style="color:#1e40af;">+ <?= APP_CURRENCY ?> <?= number_format($sale['grand_total'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="balance-final">
            <span class="lbl">Total Outstanding</span>
            <span class="val" style="color:#dc2626;"><?= APP_CURRENCY ?> <?= number_format($sale['total_balance'], DECIMAL_PLACES) ?></span>
        </div>
    </div>
    <?php elseif ($sale['balance'] > 0): ?>
    <div class="balance-section">
        <div class="balance-final">
            <span class="lbl">Total Outstanding</span>
            <span class="val" style="color:#dc2626;"><?= APP_CURRENCY ?> <?= number_format($sale['balance'], DECIMAL_PLACES) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($sale['notes']): ?>
    <div style="margin-top:12px;padding:8px;border:1px dashed #e5e7eb;border-radius:6px;font-size:10px;color:#555;">
        <strong style="display:block;font-size:9px;text-transform:uppercase;color:#888;margin-bottom:3px;">Notes</strong>
        <?= nl2br(htmlspecialchars($sale['notes'])) ?>
    </div>
    <?php endif; ?>

    <div class="receipt-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you for your business!') ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
    </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var btn = event.target.closest('button');
    btn.disabled = true; btn.innerHTML = '<b>Generating...</b>';
    var el = document.querySelector('.invoice-wrap, .wrap');
    var opt = {
        margin:      [8, 10, 8, 10],
        filename:    '<?= $sale['invoice_no'] ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: '<?= $thermal ? 'a4' : 'a5' ?>', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save().then(function() {
        btn.disabled = false; btn.innerHTML = '<?= $thermal ? '<b>⤓ PDF</b>' : '<b>⤓ PDF</b>' ?>';
    });
}
<?php if (isset($_GET['autoprint'])): ?>
window.addEventListener('load', () => setTimeout(() => window.print(), 400));
<?php endif; ?>
<?php if (isset($_GET['autopdf'])): ?>
window.addEventListener('load', () => setTimeout(() => exportPDF(), 600));
<?php endif; ?>
</script>
</body>
</html>
