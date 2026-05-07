<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return <?= $return['return_no'] ?></title>
<?php
$thermal = isset($returnPrintThermal)
    ? (bool) $returnPrintThermal
    : isset($_GET['thermal']);
?>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 LAYOUT — compact
══════════════════════════════════ */
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9px; color: #1a1a1a; background: #fff; }
.wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 8mm 10mm; }

.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; border-bottom: 2px solid #dc2626; padding-bottom: 7px; }
.company-name { font-size: 12px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 8px; color: #555; margin-top: 3px; line-height: 1.5; }
.inv-title { text-align: right; }
.inv-title h1 { font-size: 18px; font-weight: 800; color: #dc2626; letter-spacing: 1px; }
.inv-title p { font-size: 8px; color: #444; margin-top: 3px; line-height: 1.7; }

.customer-row { background:#fff5f5; border:1px solid #fecaca; border-radius:5px; padding:5px 8px; margin-bottom:8px; display:flex; align-items:center; gap:10px; font-size:9px; }
.customer-row .clbl { font-weight:700; text-transform:uppercase; color:#dc2626; letter-spacing:0.5px; white-space:nowrap; }
.customer-row .cname { font-weight:700; color:#1a1a1a; }
.customer-row .cphone { color:#555; }

table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
thead th { background:#1e3a5f; color:#fff; padding:5px 6px; font-size:8px; text-transform:uppercase; letter-spacing:0.3px; white-space:nowrap; }
tbody td { padding:4px 6px; border-bottom:1px solid #f0f0f0; font-size:8.5px; vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:nth-child(even) { background:#fff5f5; }
tfoot td { padding:4px 6px; font-size:8.5px; font-weight:700; border-top:1.5px solid #1e3a5f; }

.totals-section { display:flex; justify-content:flex-end; margin-bottom:8px; }
.totals-box { width:55mm; }
.total-row { display:flex; justify-content:space-between; padding:3px 0; font-size:8.5px; border-bottom:1px solid #f0f0f0; }
.total-row:last-child { border-bottom:none; }
.total-row .label { color:#555; }
.total-row.grand { font-size:10px; font-weight:800; color:#dc2626; border-top:1.5px solid #dc2626 !important; padding-top:5px !important; margin-top:2px; }

.inv-footer { border-top:1px solid #e5e7eb; padding-top:6px; text-align:center; color:#888; font-size:8px; margin-top:8px; }

.no-print { text-align:right; margin-bottom:14px; }
.no-print button { padding:7px 18px; border-radius:5px; font-size:13px; cursor:pointer; border:none; margin-left:6px; }

@media screen { body { background:#e5e7eb; padding:20px; } .wrap { box-shadow:0 2px 16px rgba(0,0,0,0.15); background:#fff; } }
@media print {
    body { background:#fff; padding:0; }
    .no-print { display:none !important; }
    .wrap { max-width:100%; width:100%; padding:0; margin:0; }
    @page { size: A5 portrait; margin: 8mm 10mm; }
}

<?php else: ?>
/* ══════════════════════════════════
   THERMAL — Receipt Style
══════════════════════════════════ */
    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; background: #fff; padding: 8px 2px; }

    .no-print { padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; margin: -8px -2px 12px; text-align: center; }
    .no-print a, .no-print button { display: inline-block; background: #6366f1; color: #fff; border: none; padding: 6px 14px; border-radius: 5px; font-size: 13px; cursor: pointer; margin: 4px; text-decoration: none; font-family: system-ui, sans-serif; }
    .no-print .btn-print { background: #059669; }
    .no-print .btn-danger { background: #dc2626; }
    .no-print .btn-secondary { background: #e5e7eb; color: #444; }

    .wrap { max-width: 72mm; margin: 0 auto; padding: 8px 3px; }

    .receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
    .company-name { font-size: 18px; font-weight: 800; color: #000; }
    .company-info { font-size: 10px; color: #666; margin-top: 4px; line-height: 1.6; }

    .receipt-title { text-align: center; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #000; margin: 14px 0 6px; }
    .receipt-subtitle { text-align: center; margin-bottom: 14px; }

    .amount-box { margin: 18px 0; background: transparent; border: 2px solid #000; border-radius: 0; padding: 14px; text-align: center; }
    .amount-label { font-size: 10px; color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    .amount-value { font-size: 26px; font-weight: 800; color: #000; margin-top: 4px; }

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
    .balance-final { display: flex; justify-content: space-between; padding: 8px 0 0; border-top: 2px solid #000; margin-top: 4px; }
    .balance-final .lbl { font-weight: 800; color: #000; font-size: 12px; }
    .balance-final .val { font-weight: 800; font-size: 13px; }

    .receipt-footer { text-align: center; margin-top: 18px; padding-top: 14px; border-top: 2px dashed #e5e7eb; font-size: 10px; color: #888; }

    @media print {
        body { padding: 0; background: #fff; -webkit-print-color-adjust: economy; print-color-adjust: economy; }
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
     A5 LAYOUT — unchanged
════════════════════════════════ -->
<div class="no-print">
    <button onclick="window.print()" style="background:#dc2626;color:#fff;">⎙ Print</button>
    <a href="?page=returns&action=print&id=<?= $return['id'] ?>&thermal=1"
        style="background:#059669;color:#fff;border:none;padding:7px 18px;border-radius:5px;font-size:13px;font-weight:700;text-decoration:none;display:inline-block;margin-left:6px;cursor:pointer;">
        🖨 Thermal
    </a>
    <button onclick="exportPDF()" style="background:#7c3aed;color:#fff;">⤓ PDF</button>
    <button onclick="window.location='?page=returns'" style="background:#f3f4f6;color:#444;border:1px solid #e5e7eb!important;">Close</button>
</div>

<div class="wrap">
    <div class="inv-header">
        <div>
            <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
            <div class="company-info">
                <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
                <?= htmlspecialchars($settings['company_phone'] ?? '') ?>
            </div>
        </div>
        <div class="inv-title">
            <h1>SALE RETURN</h1>
            <p>
                <strong># <?= $return['return_no'] ?></strong><br>
                Date: <?= date('d M Y', strtotime($return['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($return['warehouse_name'] ?? '—') ?>
            </p>
        </div>
    </div>

    <div class="customer-row">
        <span class="clbl">Customer:</span>
        <span class="cname"><?= htmlspecialchars($return['party_name']) ?></span>
        <?php if (!empty($return['party_phone'])): ?>
        <span class="cphone"><?= htmlspecialchars($return['party_phone']) ?></span>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Item</th>
                <th style="width:70px;text-align:center;">Qty</th>
                <th style="width:110px;text-align:right;">Unit Price</th>
                <th style="width:110px;text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($return['items'] as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <span style="font-weight:600;"><?= htmlspecialchars($item['item_name']) ?></span>
                    <?php if (!empty($item['sku'])): ?><span style="font-size:7.5px;color:#888;margin-left:4px;"><?= $item['sku'] ?></span><?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:600;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="border-top:2px solid #1e3a5f;font-weight:700;">
                <td></td>
                <td style="text-align:right;color:#555;">Total Devices Returned:</td>
                <td style="text-align:center;"><?= array_sum(array_column($return['items'], 'quantity')) ?></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span class="label">Previous Balance</span>
                <span style="font-weight:700;"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row" style="color:#dc2626;">
                <span class="label" style="color:#dc2626;">Return Amount</span>
                <span style="font-weight:700;">- <?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row grand">
                <span>Balance Due</span>
                <span><?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>

    <div class="inv-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you!') ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?></p>
    </div>
</div>

<?php else: ?>
<!-- ════════════════════════════════
     THERMAL — Receipt Style
════════════════════════════════ -->
<div class="no-print">
    <button onclick="window.print()">⎙ Print</button>
    <button onclick="exportPDF()" style="background:#dc2626;">⤓ PDF</button>
    <a href="?page=returns&action=print&id=<?= (int) $return['id'] ?>&template=a5"
        style="background:#6366f1;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:13px;font-weight:700;text-decoration:none;display:inline-block;margin-right:6px;">
        ⬚ A5
    </a>
    <button class="close-btn" onclick="window.location='?page=returns'">Close</button>
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
    <div class="receipt-title">Sale Return</div>
    <div class="receipt-subtitle">
        <span style="display:inline-block;padding:4px 12px;border:2px solid #000;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#000;background:transparent;">
            ↩ Return
        </span>
    </div>

    <!-- Details -->
    <div class="receipt-row"><span class="lbl">Return No</span><span class="val"><?= $return['return_no'] ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($return['date'])) ?></span></div>
    <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($return['party_name']) ?></span></div>
    <?php if (!empty($return['party_phone'])): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars($return['party_phone']) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl">Branch</span><span class="val"><?= htmlspecialchars($return['warehouse_name'] ?? '—') ?></span></div>
    <?php if (!empty($return['reason'])): ?>
    <div class="receipt-row"><span class="lbl">Reason</span><span class="val"><?= htmlspecialchars($return['reason']) ?></span></div>
    <?php endif; ?>

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
                <?php foreach ($return['items'] as $item): ?>
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

    <!-- Return Amount Box -->
    <div class="amount-box">
        <div class="amount-label">Return Amount</div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></div>
    </div>

    <!-- Balance Section -->
    <div class="balance-section">
        <div class="balance-row">
            <span class="lbl">Previous Balance</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
        </div>
        <div class="balance-row">
            <span class="lbl">Return Deducted</span>
            <span class="val">- <?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="balance-final">
            <span class="lbl">Current Balance</span>
            <span class="val" style="color:#000;">
                <?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?>
            </span>
        </div>
    </div>

    <div class="receipt-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you!') ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?> &nbsp;·&nbsp; <?= htmlspecialchars(Auth::name()) ?></p>
    </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = 'Generating...';
    html2pdf().set({
        margin: [10, 12, 10, 12],
        filename: '<?= $return['return_no'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(document.querySelector('.wrap')).save().then(function() {
        btn.disabled = false;
        btn.innerHTML = '⤓ PDF';
    });
}

window.addEventListener('load', () => {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') setTimeout(() => window.print(), 400);
    if (params.get('autopdf') === '1') setTimeout(() => exportPDF(), 600);
});
</script>
</body>
</html>
