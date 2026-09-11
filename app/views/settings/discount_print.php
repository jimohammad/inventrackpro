<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount — <?= htmlspecialchars($discount['discount_no']) ?></title>
    <?php $a5 = isset($_GET['a5']); ?>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        .no-print {
            padding:8px 12px;
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            margin:0 0 16px;
        }
        .no-print button {
            background:#6366f1;
            color:#fff;
            border:none;
            padding:6px 16px;
            border-radius:5px;
            font-size:13px;
            cursor:pointer;
            margin-right:6px;
            font-weight:700;
        }
        .no-print .close-btn { background:#e5e7eb; color:#444; }

<?php if ($a5): ?>
        body {
            font-family:'Segoe UI', Arial, sans-serif;
            font-size:10px;
            color:#1a1a1a;
            background:#fff;
            padding:20px;
        }
        .wrap {
            max-width:148mm;
            margin:0 auto;
            padding:10mm;
            border:1px solid #e0e7ff;
            border-radius:8px;
        }
        .receipt-header {
            text-align:center;
            margin-bottom:20px;
            padding-bottom:16px;
            border-bottom:2px dashed #e5e7eb;
        }
        .company-name { font-size:20px; font-weight:800; color:#1e3a5f; }
        .receipt-title {
            text-align:center;
            font-size:13px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:2px;
            color:#6366f1;
            margin:14px 0 10px;
        }
        .receipt-row {
            display:flex;
            justify-content:space-between;
            gap:12px;
            padding:6px 0;
            border-bottom:1px solid #f3f4f6;
            font-size:10.5px;
        }
        .receipt-row:last-child { border-bottom:none; }
        .receipt-row .lbl { color:#666; }
        .receipt-row .val { font-weight:600; color:#1a1a1a; text-align:right; }
        .amount-box {
            margin:16px 0 14px;
            background:linear-gradient(135deg,#eff6ff,#eef2ff);
            border:2px solid #c7d2fe;
            border-radius:10px;
            padding:14px;
            text-align:center;
        }
        .amount-label {
            font-size:10px;
            color:#6366f1;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:1px;
        }
        .amount-value { font-size:22px; font-weight:800; color:#1e3a5f; margin-top:4px; }
        .balance-section {
            margin-top:14px;
            padding-top:12px;
            border-top:2px dashed #e5e7eb;
        }
        .balance-final {
            display:flex;
            justify-content:space-between;
            padding:8px 0 0;
            border-top:2px solid #1e3a5f;
            margin-top:4px;
        }
        .balance-final .lbl { font-weight:800; color:#1e3a5f; font-size:12px; }
        .balance-final .val { font-weight:800; font-size:13px; }
        .balance-final .val.due { color:#dc2626; }
        .balance-final .val.clear { color:#059669; }
        .receipt-footer {
            text-align:center;
            margin-top:18px;
            padding-top:14px;
            border-top:2px dashed #e5e7eb;
            font-size:10px;
            color:#888;
        }
        @media print {
            html, body { height:auto !important; min-height:0 !important; padding:0; background:#fff; }
            .no-print { display:none !important; }
            .wrap { border:none; padding:0; margin:0; width:100%; max-width:100%; border-radius:0; }
            @page { size: A5 portrait; margin:8mm 10mm; }
        }
<?php else: ?>
<?php include __DIR__ . '/../partials/thermal_print_font.css.php'; ?>
        body {
            font-size:12px;
            color:#000;
            background:#fff;
            padding:8px 2px;
        }
        .wrap {
            max-width:72mm;
            margin:0 auto;
            padding:8px 3px;
        }
        .receipt-header {
            text-align:center;
            margin-bottom:16px;
            padding-bottom:12px;
            border-bottom:2px dashed #000;
        }
        .company-name { font-size:14px; font-weight:800; color:#000; text-transform:uppercase; }
        .receipt-title {
            text-align:center;
            font-size:12px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:0.04em;
            color:#000;
            margin:12px 0 10px;
        }
        .receipt-row {
            display:flex;
            justify-content:space-between;
            gap:8px;
            padding:4px 0;
            border-bottom:none;
            font-size:12px;
            line-height:1.3;
        }
        .receipt-row:last-child { border-bottom:none; }
        .receipt-row .lbl { color:#000; }
        .receipt-row .val { font-weight:400; color:#000; text-align:right; word-break:break-word; }
        .amount-box {
            margin:14px 0;
            padding:12px 8px;
            text-align:center;
            background:transparent;
            border:2px solid #000;
            border-radius:0;
        }
        .amount-label {
            font-size:10px;
            color:#000;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.04em;
        }
        .amount-value { font-size:22px; font-weight:800; color:#000; margin-top:4px; }
        .balance-section {
            margin-top:12px;
            padding-top:10px;
            border-top:2px dashed #000;
        }
        .balance-final {
            display:flex;
            justify-content:space-between;
            gap:8px;
            padding:8px 0 0;
            border-top:2px solid #000;
            margin-top:4px;
        }
        .balance-final .lbl,
        .balance-final .val {
            font-weight:800;
            font-size:13px;
            color:#000;
        }
        .balance-final .val { text-align:right; }
        .receipt-footer {
            text-align:center;
            margin-top:14px;
            padding-top:10px;
            border-top:2px dashed #000;
            font-size:10px;
            color:#000;
            line-height:1.35;
        }
        .receipt-meta {
            margin-top:12px;
            border-top:2px solid #000;
            padding-top:6px;
        }
        .receipt-tail {
            height:28mm;
            margin:0;
            padding:0;
        }
        @media print {
            html, body {
                height:auto !important;
                min-height:0 !important;
                padding:0;
                background:#fff;
                overflow:visible;
                font-family:monospace;
                font-weight:400;
                -webkit-font-smoothing:none;
                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }
            .no-print { display:none !important; }
            .wrap {
                border:none;
                padding:0;
                margin:0;
                width:100%;
                max-width:100%;
                border-radius:0;
                font-family:monospace;
            }
            @page { size: 72mm auto; margin:2mm; }
        }
<?php endif; ?>
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">⎙ Print</button>
    <button onclick="exportPDF()" style="background:#dc2626;">⤓ PDF</button>
    <?php if ($a5): ?>
    <a href="?page=discounts&action=print&id=<?= (int)$discount['id'] ?>"
       style="background:#059669;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:13px;cursor:pointer;margin-right:6px;font-weight:700;text-decoration:none;display:inline-block;">
        🖨 Thermal
    </a>
    <?php else: ?>
    <a href="?page=discounts&action=print&id=<?= (int)$discount['id'] ?>&a5=1"
       style="background:#6366f1;color:#fff;border:none;padding:6px 16px;border-radius:5px;font-size:13px;cursor:pointer;margin-right:6px;font-weight:700;text-decoration:none;display:inline-block;">
        ⬚ A5
    </a>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=discounts'">Close</button>
</div>

<div class="wrap">
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
    </div>

    <div class="receipt-title">Discount Note</div>

    <div class="receipt-row"><span class="lbl">Discount No</span><span class="val"><?= htmlspecialchars($discount['discount_no']) ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($discount['date'])) ?></span></div>
    <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($discount['party_name']) ?></span></div>

    <?php if ($discount['party_phone']): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars($discount['party_phone']) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($discount['sale_invoice_no'])): ?>
    <div class="receipt-row"><span class="lbl">Invoice</span><span class="val"><?= htmlspecialchars($discount['sale_invoice_no']) ?></span></div>
    <?php endif; ?>

    <div class="amount-box">
        <div class="amount-label">Discount Amount</div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($discount['amount'], DECIMAL_PLACES) ?></div>
    </div>

    <div class="balance-section">
        <div class="balance-final">
            <span class="lbl">Remaining Balance</span>
            <span class="val<?= $a5 ? ($remainingBalance > 0.001 ? ' due' : ' clear') : '' ?>">
                <?= $remainingBalance > 0.001 ? APP_CURRENCY . ' ' . number_format($remainingBalance, DECIMAL_PLACES) : ($a5 ? '✓ Clear' : 'Clear') ?>
            </span>
        </div>
    </div>

    <div class="receipt-footer">
        <p>This discount has been applied to your account.</p>
        <p style="margin-top:4px;"><?= htmlspecialchars($companyName) ?></p>
    </div>

    <div class="receipt-row receipt-meta">
        <span class="lbl">Issued By</span>
        <span class="val"><?= htmlspecialchars($discount['created_by_name'] ?? '—') ?></span>
    </div>
    <div class="receipt-row">
        <span class="lbl">Created</span>
        <span class="val"><?= date('d M Y, h:i A', strtotime($discount['created_at'])) ?></span>
    </div>
    <?php if (!$a5): ?>
    <div class="receipt-tail" aria-hidden="true">&nbsp;</div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var el = document.querySelector('.wrap');
    var opt = {
        margin:      [8, 10, 8, 10],
        filename:    <?= json_encode($discount['discount_no'] . '.pdf') ?>,
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: '<?= $a5 ? 'a5' : 'a4' ?>', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save();
}
window.onload = function() { window.print(); };
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    e.preventDefault();
    window.location.href = '?page=dashboard';
});
</script>
</body>
</html>
