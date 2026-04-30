<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount — <?= $discount['discount_no'] ?></title>
    <?php $a5 = isset($_GET['a5']); ?>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Courier New', Courier, monospace;
            font-size:12px;
            color:#000;
            background:#fff;
            padding:20px 4px;
        }

        .no-print {
            padding:8px 12px;
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            margin:-20px -4px 16px;
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

        .wrap {
            max-width:480px;
            margin:0 auto;
            padding:16px 6px;
            border:1px solid #e5e7eb;
            border-radius:10px;
        }
        body.a5 {
            font-family:'Segoe UI', Arial, sans-serif;
            font-size:10px;
            color:#1a1a1a;
            padding:20px;
        }
        body.a5 .wrap {
            max-width:148mm;
            border:1px solid #e0e7ff;
            border-radius:8px;
            padding:10mm;
        }
        body.a5 .company-name { font-size:20px; }
        body.a5 .receipt-row { font-size:10.5px; }
        body.a5 .amount-value { font-size:22px; }

        .receipt-header {
            text-align:center;
            margin-bottom:20px;
            padding-bottom:16px;
            border-bottom:2px dashed #e5e7eb;
        }
        .company-name { font-size:18px; font-weight:800; color:#1e3a5f; }

        .receipt-title {
            text-align:center;
            font-size:13px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:2px;
            color:#6366f1;
            margin:14px 0 6px;
        }
        .receipt-subtitle { text-align:center; margin-bottom:14px; }
        .receipt-subtitle span {
            display:inline-block;
            padding:4px 18px;
            border-radius:20px;
            font-size:11px;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:1px;
            background:#dbeafe;
            color:#1e40af;
        }

        .receipt-row {
            display:flex;
            justify-content:space-between;
            gap:12px;
            padding:6px 0;
            border-bottom:1px solid #f3f4f6;
            font-size:11.5px;
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
        .amount-value {
            font-size:26px;
            font-weight:800;
            color:#1e3a5f;
            margin-top:4px;
        }

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

        .receipt-footer {
            text-align:center;
            margin-top:18px;
            padding-top:14px;
            border-top:2px dashed #e5e7eb;
            font-size:10px;
            color:#888;
        }

        @media print {
            body { padding:0; background:#fff; }
            .no-print { display:none !important; }
            .wrap { border:none; padding:0; width:100%; max-width:100%; }
            @page { size: <?= $a5 ? 'A5 portrait' : 'auto' ?>; margin:<?= $a5 ? '8mm 10mm' : '8mm 3mm' ?>; }
        }
    </style>
</head>
<body class="<?= $a5 ? 'a5' : '' ?>">

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
    <div class="receipt-subtitle">
        <span>&#128179; Discount Applied</span>
    </div>

    <div class="receipt-row"><span class="lbl">Discount No</span><span class="val"><?= htmlspecialchars($discount['discount_no']) ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($discount['date'])) ?></span></div>
    <div class="receipt-row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($discount['party_name']) ?></span></div>

    <?php if ($discount['party_phone']): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars($discount['party_phone']) ?></span></div>
    <?php endif; ?>

    <?php if ($discount['item_name']): ?>
    <div class="receipt-row"><span class="lbl">Item</span><span class="val"><?= htmlspecialchars($discount['item_name']) ?></span></div>
    <?php endif; ?>

    <?php if ($discount['reason']): ?>
    <div class="receipt-row"><span class="lbl">Reason</span><span class="val"><?= htmlspecialchars($discount['reason']) ?></span></div>
    <?php endif; ?>

    <div class="amount-box">
        <div class="amount-label">Discount Amount</div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($discount['amount'], DECIMAL_PLACES) ?></div>
    </div>

    <div class="balance-section">
        <div class="balance-final">
            <span class="lbl">Remaining Balance</span>
            <span class="val" style="color:<?= $remainingBalance > 0.001 ? '#dc2626' : '#059669' ?>;">
                <?= $remainingBalance > 0.001 ? APP_CURRENCY . ' ' . number_format($remainingBalance, DECIMAL_PLACES) : '✓ Clear' ?>
            </span>
        </div>
    </div>

    <div class="receipt-row" style="margin-top:8px;">
        <span class="lbl">Issued By</span>
        <span class="val"><?= htmlspecialchars($discount['created_by_name'] ?? '—') ?></span>
    </div>
    <div class="receipt-row">
        <span class="lbl">Created</span>
        <span class="val"><?= date('d M Y, h:i A', strtotime($discount['created_at'])) ?></span>
    </div>

    <div class="receipt-footer">
        <p>This discount has been applied to your account.</p>
        <p style="margin-top:4px;"><?= htmlspecialchars($companyName) ?></p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var el = document.querySelector('.wrap');
    var opt = {
        margin:      [8, 10, 8, 10],
        filename:    '<?= $discount['discount_no'] ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: '<?= $a5 ? 'a5' : 'a4' ?>', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(el).save();
}
window.onload = function() { window.print(); };
</script>
</body>
</html>
