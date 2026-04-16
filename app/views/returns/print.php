<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return <?= $return['return_no'] ?></title>
<?php $thermal = isset($_GET['thermal']); ?>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 LAYOUT — unchanged
══════════════════════════════════ */
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 12px; color: #1a1a1a;
        background: #fff; padding: 20px;
    }
    .wrap { max-width: 680px; margin: 0 auto; padding: 24px; }

    .inv-header { display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
    .company-name { font-size: 17px; font-weight: 800; color: #1e3a5f; }
    .company-info { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }
    .inv-title { text-align: right; }
    .inv-title h1 { font-size: 22px; font-weight: 800; color: #dc2626; letter-spacing: 1px; }
    .inv-title p { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.7; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th {
        background: #1e3a5f; color: #fff;
        padding: 8px 10px; font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.4px;
    }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:nth-child(even) { background: #fff5f5; }

    .totals-section { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .totals-box { min-width: 240px; }
    .total-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
    .total-row:last-child { border-bottom: none; }
    .total-row .label { color: #666; }
    .total-row.grand { font-size: 15px; font-weight: 800; color: #dc2626; border-top: 2px solid #dc2626; padding-top: 8px; margin-top: 4px; }

    .inv-footer { border-top: 1px dashed #e5e7eb; padding-top: 12px; text-align: center; color: #888; font-size: 11px; margin-top: 16px; }

    .no-print { text-align: right; margin-bottom: 14px; }
    .no-print button {
        padding: 7px 18px; border-radius: 5px; font-size: 13px; cursor: pointer; border: none; margin-left: 6px;
    }

    @media print {
        body { padding: 0; background: #fff; }
        .no-print { display: none !important; }
        .wrap { padding: 0; width: 100%; max-width: 100%; }
        @page { margin: 15mm 18mm 12mm 18mm; }
    }

<?php else: ?>
/* ══════════════════════════════════
   THERMAL — Receipt Style
══════════════════════════════════ */
    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; background: #fff; padding: 20px 4px; }

    .no-print { padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; margin: -20px -4px 16px; }
    .no-print button { background: #6366f1; color: #fff; border: none; padding: 6px 16px; border-radius: 5px; font-size: 13px; cursor: pointer; margin-right: 6px; }
    .no-print button.close-btn { background: #e5e7eb; color: #444; }

    .wrap { max-width: 480px; margin: 0 auto; padding: 16px 6px; border: 1px solid #e5e7eb; border-radius: 10px; }

    .receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
    .company-name { font-size: 18px; font-weight: 800; color: #1e3a5f; }
    .company-info { font-size: 10px; color: #666; margin-top: 4px; line-height: 1.6; }

    .receipt-title { text-align: center; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #dc2626; margin: 14px 0 6px; }
    .receipt-subtitle { text-align: center; margin-bottom: 14px; }

    .amount-box { margin: 18px 0; background: linear-gradient(135deg, #fff5f5, #fee2e2); border: 2px solid #fecaca; border-radius: 10px; padding: 14px; text-align: center; }
    .amount-label { font-size: 10px; color: #dc2626; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    .amount-value { font-size: 26px; font-weight: 800; color: #991b1b; margin-top: 4px; }

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

    <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:7px 14px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:12px;">
        <span style="font-weight:700;text-transform:uppercase;color:#dc2626;letter-spacing:0.5px;white-space:nowrap;">Customer:</span>
        <span style="font-weight:700;color:#1a1a1a;"><?= htmlspecialchars($return['party_name']) ?></span>
        <?php if (!empty($return['party_phone'])): ?>
        <span style="color:#555;"><?= htmlspecialchars($return['party_phone']) ?></span>
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
                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                    <?php if (!empty($item['sku'])): ?>
                    <br><small style="color:#888;"><?= $item['sku'] ?></small>
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
    <a href="?page=returns&action=print&id=<?= $return['id'] ?>"
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
        <span style="display:inline-block;padding:4px 18px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;background:#fee2e2;color:#991b1b;">
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
            <span class="lbl" style="color:#dc2626;">Return Deducted</span>
            <span class="val" style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="balance-final">
            <span class="lbl">Current Balance</span>
            <span class="val" style="color:<?= $currentBalance > 0.001 ? '#dc2626' : ($currentBalance < -0.001 ? '#7c3aed' : '#059669') ?>;">
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
