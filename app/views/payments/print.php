<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= $payment['payment_no'] ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 20px 4px;
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
    max-width: 480px;
    margin: 0 auto;
    padding: 16px 6px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 10px; color: #666; margin-top: 4px; line-height: 1.6; }

.receipt-title {
    text-align: center;
    font-size: 13px; font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #6366f1;
    margin: 14px 0;
}

.receipt-row {
    display: flex; justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #666; }
.receipt-row .val { font-weight: 600; color: #1a1a1a; text-align: right; }

.amount-box {
    margin: 18px 0;
    background: linear-gradient(135deg, #eff6ff, #eef2ff);
    border: 2px solid #c7d2fe;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.amount-label { font-size: 10px; color: #6366f1; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.amount-value { font-size: 26px; font-weight: 800; color: #1e3a5f; margin-top: 4px; }

.footer {
    text-align: center;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 2px dashed #e5e7eb;
    font-size: 10px;
    color: #888;
}

@media print {
    body { padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .wrap { border: none; padding: 0; width: 100%; max-width: 100%; }
    @page { margin: 8mm 3mm 8mm 3mm; }
}
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i>⎙</i> Print</button>
    <button onclick="exportPDF()" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('payments','edit')): ?>
    <button class="edit-btn" onclick="window.location='?page=payments&action=edit&id=<?= $payment['id'] ?>'"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button class="close-btn" onclick="window.location='?page=payments'">Close</button>
</div>

<div class="wrap">
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars((string)($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string)($settings['company_address'] ?? ''))) ?><br>
            <?= htmlspecialchars((string)($settings['company_phone'] ?? '')) ?>
        </div>
    </div>

    <div class="receipt-title">Payment Receipt</div>

    <?php $isIn = ($payment['payment_type'] ?? $payment['type'] ?? 'in') === 'in'; ?>
    <div style="text-align:center;margin-bottom:14px;">
        <span style="display:inline-block;padding:4px 18px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;
            background:<?= $isIn ? '#d1fae5' : '#fee2e2' ?>;color:<?= $isIn ? '#065f46' : '#991b1b' ?>;">
            <?= $isIn ? '↓ Payment In' : '↑ Payment Out' ?>
        </span>
    </div>

    <div class="amount-box">
        <div class="amount-label">Amount <?= $isIn ? 'Received' : 'Paid' ?></div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format($payment['amount'], DECIMAL_PLACES) ?></div>
    </div>

    <div class="receipt-row"><span class="lbl">Receipt No</span><span class="val"><?= htmlspecialchars((string)($payment['payment_no'] ?? '')) ?></span></div>
    <div class="receipt-row"><span class="lbl">Date</span><span class="val"><?= date('d M Y', strtotime($payment['date'] ?? 'now')) ?></span></div>
    <div class="receipt-row"><span class="lbl">Party</span><span class="val"><?= htmlspecialchars((string)($payment['party_name'] ?? '—')) ?></span></div>
    <?php if (!empty($payment['phone_no'])): ?>
    <div class="receipt-row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars((string)$payment['phone_no']) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl">Account</span><span class="val"><?= htmlspecialchars((string)($payment['account_name'] ?? '—')) ?></span></div>
    <?php if (!empty($payment['cheque_no'])): ?>
    <div class="receipt-row"><span class="lbl">Cheque No</span><span class="val"><?= htmlspecialchars((string)$payment['cheque_no']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($payment['notes'])): ?>
    <div class="receipt-row"><span class="lbl">Notes</span><span class="val"><?= htmlspecialchars((string)$payment['notes']) ?></span></div>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:2px dashed #e5e7eb;">
        <div class="receipt-row">
            <span class="lbl">Previous Balance</span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
        </div>
        <div class="receipt-row">
            <span class="lbl" style="color:<?= $isIn ? '#059669' : '#dc2626' ?>;">Payment <?= $isIn ? 'Received' : 'Made' ?></span>
            <span class="val" style="color:<?= $isIn ? '#059669' : '#dc2626' ?>;"><?= $isIn ? '-' : '+' ?> <?= APP_CURRENCY ?> <?= number_format($payment['amount'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="receipt-row" style="border-top:2px solid #1e3a5f;padding-top:8px;margin-top:4px;border-bottom:none;">
            <span class="lbl" style="font-weight:800;color:#1e3a5f;font-size:12px;">Current Balance</span>
            <span class="val" style="font-weight:800;color:<?= $currentBalance > 0.001 ? '#dc2626' : ($currentBalance < -0.001 ? '#7c3aed' : '#059669') ?>;font-size:13px;">
                <?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?>
            </span>
        </div>
    </div>

    <div class="footer">
        <p><?= htmlspecialchars((string)($settings['invoice_footer'] ?? 'Thank you for your business!')) ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?></p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    var btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<b>Generating...</b>';
    html2pdf().set({
        margin: [10, 12, 10, 12],
        filename: '<?= $payment['payment_no'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(document.querySelector('.wrap')).save().then(function() {
        btn.disabled = false;
        btn.innerHTML = '<b>⤓ PDF</b>';
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
