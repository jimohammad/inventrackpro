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
   THERMAL LAYOUT
══════════════════════════════════ */
body { font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #000; background: #fff; }
.invoice-wrap { width: 100%; max-width: 80mm; margin: 0 auto; padding: 6px 4px; }

.inv-header { text-align:center; margin-bottom:8px; border-bottom:2px solid #000; padding-bottom:6px; }
.company-name { font-size:13px; font-weight:800; color:#000; }
.company-info { font-size:9px; color:#333; margin-top:3px; line-height:1.5; }
.inv-title { margin-top:6px; }
.inv-title h1 { font-size:13px; font-weight:800; color:#000; letter-spacing:2px; }
.inv-title p { font-size:9px; color:#333; margin-top:3px; line-height:1.7; }

.customer-row { border-top:1px dashed #999; border-bottom:1px dashed #999; padding:4px 0; margin-bottom:6px; font-size:10px; }
.customer-row .clbl { font-weight:800; text-transform:uppercase; }
.customer-row .cname { font-weight:800; }
.customer-row .cphone { color:#444; }

table { width:100%; border-collapse:collapse; margin-bottom:6px; }
thead th { border-bottom:1px solid #000; border-top:1px solid #000; padding:4px 2px; font-size:9px; text-transform:uppercase; background:#fff; color:#000; font-weight:800; }
tbody td { padding:3px 2px; border-bottom:1px dashed #ccc; font-size:9px; vertical-align:top; }
tbody tr:last-child td { border-bottom:1px solid #000; }
.item-name { font-weight:800; }
.item-sku  { font-size:8px; color:#555; }

.totals-section { margin-bottom:6px; }
.totals-box { width:100%; }
.total-row { display:flex; justify-content:space-between; padding:2px 0; font-size:9.5px; border-bottom:1px dashed #ccc; }
.total-row:last-child { border-bottom:none; }
.total-row .lbl { color:#444; }
.t-grand { font-size:11px; font-weight:800; color:#000; border-top:1px solid #000 !important; border-bottom:none !important; padding-top:4px !important; margin-top:2px; }
.t-prev { color:#555 !important; border-top:1px dashed #999 !important; padding-top:3px !important; margin-top:2px; }
.t-outstanding { font-size:11px; font-weight:800; color:#000; border-top:1px solid #000 !important; border-bottom:none !important; padding-top:4px !important; margin-top:2px; }

.notes-box { margin-bottom:6px; padding:4px; border:1px dashed #999; font-size:9px; color:#333; }
.notes-box strong { display:block; font-size:8.5px; text-transform:uppercase; margin-bottom:2px; }
.inv-footer { border-top:1px dashed #999; padding-top:5px; text-align:center; color:#555; font-size:9px; }

@media screen { body { background:#e5e7eb; padding:20px; } .invoice-wrap { box-shadow:0 2px 16px rgba(0,0,0,0.15); background:#fff; } }
@media print {
    body { background:#fff; padding:0; }
    .no-print { display:none !important; }
    .invoice-wrap { max-width:100%; width:100%; padding:0; margin:0; }
    @page { margin: 4mm 2mm; }
}
<?php endif; ?>
</style>
</head>
<body>

<div class="no-print" style="text-align:center;padding:12px 0 0;margin-bottom:10px;">
    <button onclick="window.print()"
        style="background:#1e3a5f;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;">
        ⎙ <?= $thermal ? 'Thermal Print' : 'Print A5' ?>
    </button>
    <?php if (!$thermal): ?>
    <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&thermal=1"
        style="background:#059669;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;margin-left:6px;">
        🖨 Thermal
    </a>
    <?php else: ?>
    <a href="?page=sales&action=print&id=<?= $sale['id'] ?>"
        style="background:#6366f1;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:12px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;margin-left:6px;">
        ⬚ A5
    </a>
    <?php endif; ?>
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

    <!-- Header -->
    <div class="inv-header">
        <?php if (!$thermal): ?>
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
        <?php else: ?>
        <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
            <?= htmlspecialchars($settings['company_phone'] ?? '') ?>
        </div>
        <div class="inv-title">
            <h1>INVOICE</h1>
            <p>
                <strong># <?= $sale['invoice_no'] ?></strong><br>
                Date: <?= date('d M Y', strtotime($sale['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Customer -->
    <div class="customer-row">
        <span class="clbl">Customer: </span>
        <span class="cname"><?= htmlspecialchars($sale['party_name']) ?></span>
        <?php if ($sale['party_phone']): ?>
        <span class="cphone"><?= $thermal ? '' : '' ?><?= htmlspecialchars($sale['party_phone']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th style="width:<?= $thermal?'14px':'16px';?>;text-align:center;">#</th>
                <th style="text-align:left;">Description</th>
                <th style="width:<?= $thermal?'24px':'28px';?>;text-align:center;">Qty</th>
                <th style="width:<?= $thermal?'44px':'52px';?>;text-align:right;">Price</th>
                <th style="width:<?= $thermal?'44px':'52px';?>;text-align:right;">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $i => $item): ?>
            <tr>
                <td style="text-align:center;color:#888;"><?= $i+1 ?></td>
                <td>
                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                    <?php if ($item['sku'] && !$thermal): ?>
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

    <!-- Totals -->
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

    <!-- Footer -->
    <div class="inv-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you for your business!') ?></p>
        <p style="margin-top:3px;font-size:<?= $thermal?'8':'7.5' ?>px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var btn = event.target.closest('button');
    btn.disabled = true; btn.innerHTML = '<b>Generating...</b>';
    var el = document.querySelector('.invoice-wrap');
    var opt = {
        margin:      [8, 10, 8, 10],
        filename:    '<?= $sale['invoice_no'] ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: 'a<?= $thermal ? '4' : '5' ?>', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save().then(function() {
        btn.disabled = false; btn.innerHTML = '<b>⤓ PDF</b>';
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
