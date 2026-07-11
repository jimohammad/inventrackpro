<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= htmlspecialchars((string)($payment['payment_no'] ?? '')) ?></title>
<?php
// Colorful A5 for PDF export and normal print; thermal only when explicitly requested.
$thermal = isset($_GET['thermal']) && !isset($_GET['autopdf']);
?>
<?php $isIn = ($payment['payment_type'] ?? $payment['type'] ?? 'in') === 'in'; ?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 PDF — colorful voucher
══════════════════════════════════ */
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9px; color: #1a1a1a; background: #fff; }
.wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 8mm 10mm; }

.inv-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 3px solid #1e3a5f;
}
.company-name { font-size: 13px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 8px; color: #555; margin-top: 3px; line-height: 1.55; }
.inv-title { text-align: right; }
.inv-title h1 {
    font-size: 20px;
    font-weight: 800;
    color: <?= $isIn ? '#059669' : '#dc2626' ?>;
    letter-spacing: 1px;
}
.inv-title p { font-size: 8px; color: #444; margin-top: 4px; line-height: 1.7; }

.party-row {
    background: <?= $isIn ? '#ecfdf5' : '#fef2f2' ?>;
    border: 1px solid <?= $isIn ? '#a7f3d0' : '#fecaca' ?>;
    border-radius: 6px;
    padding: 6px 10px;
    margin-bottom: 10px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;
    font-size: 9px;
}
.party-row .plbl {
    font-weight: 700;
    text-transform: uppercase;
    color: <?= $isIn ? '#047857' : '#b91c1c' ?>;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
.party-row .pval { font-weight: 700; color: #1a1a1a; }
.party-row .pmeta { color: #555; }

.amount-hero {
    text-align: center;
    margin: 12px 0 14px;
    padding: 14px 12px;
    border-radius: 10px;
    background: linear-gradient(135deg, <?= $isIn ? '#ecfdf5, #d1fae5' : '#fef2f2, #fee2e2' ?>);
    border: 2px solid <?= $isIn ? '#6ee7b7' : '#fca5a5' ?>;
}
.amount-hero .amount-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: <?= $isIn ? '#047857' : '#b91c1c' ?>;
}
.amount-hero .amount-value {
    font-size: 28px;
    font-weight: 800;
    color: #1e3a5f;
    margin-top: 4px;
    line-height: 1.1;
}

.meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-bottom: 12px;
}
.meta-card {
    background: #f8f9ff;
    border: 1px solid #e0e7ff;
    border-radius: 6px;
    padding: 6px 8px;
}
.meta-card .mlbl {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #6366f1;
    margin-bottom: 2px;
}
.meta-card .mval { font-size: 9px; font-weight: 600; color: #1a1a1a; word-break: break-word; }
.meta-card.full { grid-column: 1 / -1; }

.notes-box {
    margin-bottom: 10px;
    padding: 6px 8px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 5px;
    font-size: 8px;
    color: #555;
}
.notes-box strong {
    display: block;
    color: #92400e;
    font-size: 7.5px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.totals-section { display: flex; justify-content: flex-end; margin-bottom: 10px; }
.totals-box {
    width: 62mm;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
}
.total-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 8.5px;
    border-bottom: 1px solid #f0f0f0;
}
.total-row:last-child { border-bottom: none; }
.total-row .lbl { color: #555; }
.total-row .val { font-weight: 600; text-align: right; }
.total-row.t-payment .lbl,
.total-row.t-payment .val {
    color: <?= $isIn ? '#059669' : '#dc2626' ?> !important;
    font-weight: 700;
}
.total-row.t-current {
    border-top: 2px solid #1e3a5f !important;
    margin-top: 4px;
    padding-top: 7px !important;
}
.total-row.t-current .lbl,
.total-row.t-current .val {
    font-size: 10px !important;
    font-weight: 800 !important;
    color: <?= ($currentBalance > 0.001 ? '#dc2626' : ($currentBalance < -0.001 ? '#7c3aed' : '#059669')) ?> !important;
}

.inv-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 7px;
    text-align: center;
    color: #888;
    font-size: 8px;
    margin-top: 6px;
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
    background: #6366f1;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    font-family: system-ui, sans-serif;
}
.no-print button.close-btn { background: #e5e7eb; color: #444; }
.no-print button.edit-btn  { background: #f59e0b; }

@media screen {
    body { background: #e5e7eb; padding: 20px; }
    .wrap { box-shadow: 0 2px 16px rgba(0,0,0,0.15); background: #fff; border-radius: 4px; }
}
@media print {
    body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .wrap { max-width: 100%; width: 100%; padding: 0; margin: 0; box-shadow: none; }
    @page { size: A5 portrait; margin: 8mm 10mm; }
}

<?php else: ?>
/* ══════════════════════════════════
   THERMAL — Receipt Style
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
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #000; }
.company-info { font-size: 11px; color: #000; margin-top: 4px; line-height: 1.35; }

.receipt-title {
    text-align: center;
    font-size: 12px; font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #000;
    margin: 14px 0;
}

.receipt-row {
    display: flex; justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #000; }
.receipt-row .val { font-weight: 400; color: #000; text-align: right; }

.current-balance-row {
    border-top: 2px solid #000;
    padding-top: 8px;
    margin-top: 4px;
    border-bottom: none;
}
.current-balance-row .lbl,
.current-balance-row .val {
    font-weight: bold !important;
    font-size: 14px !important;
    color: #000 !important;
}
.current-balance-row .val { font-size: 16px !important; }

.amount-box {
    margin: 18px 0;
    padding: 14px;
    text-align: center;
    background: transparent;
    border: 2px solid #000;
    border-radius: 0;
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

<div class="no-print">
    <button type="button" id="btnPrint"><i>⎙</i> Print</button>
    <?php if (!$thermal): ?>
    <button type="button" id="btnThermal" style="background:#059669;color:#fff;"><b>🖨 Thermal</b></button>
    <?php else: ?>
    <button type="button" id="btnA5" style="background:#6366f1;color:#fff;"><b>⬚ A5 PDF</b></button>
    <?php endif; ?>
    <button type="button" id="btnPdf" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (Auth::can('payments','edit')): ?>
    <button type="button" class="edit-btn" id="btnEdit"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button type="button" class="close-btn" id="btnClose">Close</button>
</div>

<div class="wrap">
<?php if (!$thermal): ?>
    <div class="inv-header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string)($settings['company_name'] ?? APP_NAME)) ?></div>
            <div class="company-info">
                <?= nl2br(htmlspecialchars((string)($settings['company_address'] ?? ''))) ?><br>
                <?= htmlspecialchars((string)($settings['company_phone'] ?? '')) ?>
            </div>
        </div>
        <div class="inv-title">
            <h1>Payment Receipt</h1>
            <p>
                <strong># <?= htmlspecialchars((string)($payment['payment_no'] ?? '')) ?></strong><br>
                Date: <?= date('d M Y', strtotime($payment['date'] ?? 'now')) ?><br>
                Type: <?= $isIn ? 'Amount Received' : 'Amount Paid' ?>
            </p>
        </div>
    </div>

    <div class="party-row">
        <span class="plbl">Party</span>
        <span class="pval"><?= htmlspecialchars((string)($payment['party_name'] ?? '—')) ?></span>
        <?php if (!empty($payment['phone_no'])): ?>
        <span class="plbl">Phone</span>
        <span class="pmeta"><?= htmlspecialchars((string)$payment['phone_no']) ?></span>
        <?php endif; ?>
        <span class="plbl">Account</span>
        <span class="pmeta"><?= htmlspecialchars((string)($payment['account_name'] ?? '—')) ?></span>
        <?php if (!empty($payment['cheque_no'])): ?>
        <span class="plbl">Cheque</span>
        <span class="pmeta"><?= htmlspecialchars((string)$payment['cheque_no']) ?></span>
        <?php endif; ?>
    </div>

    <div class="amount-hero">
        <div class="amount-label">Amount <?= $isIn ? 'Received' : 'Paid' ?></div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format((float)$payment['amount'], DECIMAL_PLACES) ?></div>
    </div>

    <?php if (!empty($payment['notes'])): ?>
    <div class="notes-box">
        <strong>Notes</strong>
        <?= nl2br(htmlspecialchars((string)$payment['notes'])) ?>
    </div>
    <?php endif; ?>

    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span class="lbl">Previous Balance</span>
                <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row t-payment">
                <span class="lbl">Payment <?= $isIn ? 'Received' : 'Made' ?></span>
                <span class="val"><?= $isIn ? '−' : '+' ?> <?= APP_CURRENCY ?> <?= number_format((float)$payment['amount'], DECIMAL_PLACES) ?></span>
            </div>
            <div class="total-row t-current">
                <span class="lbl">Current Balance</span>
                <span class="val"><?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>

    <div class="inv-footer">
        <p><?= htmlspecialchars((string)($settings['invoice_footer'] ?? 'Thank you for your business!')) ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?></p>
        <p style="margin-top:4px;font-size:7px;color:#666;">This is a computer generated receipt.</p>
    </div>

<?php else: ?>
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars((string)($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string)($settings['company_address'] ?? ''))) ?><br>
            <?= htmlspecialchars((string)($settings['company_phone'] ?? '')) ?>
        </div>
    </div>

    <div class="receipt-title">Payment Receipt</div>

    <div class="amount-box">
        <div class="amount-label">Amount <?= $isIn ? 'Received' : 'Paid' ?></div>
        <div class="amount-value"><?= APP_CURRENCY ?> <?= number_format((float)$payment['amount'], DECIMAL_PLACES) ?></div>
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
            <span class="lbl">Payment <?= $isIn ? 'Received' : 'Made' ?></span>
            <span class="val"><?= $isIn ? '-' : '+' ?> <?= APP_CURRENCY ?> <?= number_format((float)$payment['amount'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="receipt-row current-balance-row">
            <span class="lbl"><strong>Current Balance</strong></span>
            <span class="val"><strong><?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?></strong></span>
        </div>
    </div>

    <div class="footer">
        <p><?= htmlspecialchars((string)($settings['invoice_footer'] ?? 'Thank you for your business!')) ?></p>
        <p style="margin-top:4px;">Printed <?= date('d M Y, h:i A') ?></p>
        <p style="margin-top:8px;font-size:9px;color:#666;">This is a computer generated receipt.</p>
    </div>
<?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
(function () {
    var paymentId = <?= (int)$payment['id'] ?>;
    var isThermal = <?= $thermal ? 'true' : 'false' ?>;
    var paymentNo = <?= json_encode((string)($payment['payment_no'] ?? 'receipt')) ?>;

    document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });

    var btnThermal = document.getElementById('btnThermal');
    if (btnThermal) {
        btnThermal.addEventListener('click', function () {
            window.location = '?page=payments&action=print&id=' + paymentId + '&autoprint=1&thermal=1';
        });
    }

    var btnA5 = document.getElementById('btnA5');
    if (btnA5) {
        btnA5.addEventListener('click', function () {
            window.location = '?page=payments&action=print&id=' + paymentId;
        });
    }

    var btnEdit = document.getElementById('btnEdit');
    if (btnEdit) {
        btnEdit.addEventListener('click', function () {
            window.location = '?page=payments&action=edit&id=' + paymentId;
        });
    }

    document.getElementById('btnClose').addEventListener('click', function () {
        window.location = '?page=payments';
    });

    function exportPDF() {
        if (isThermal) {
            window.location = '?page=payments&action=print&id=' + paymentId + '&autopdf=1';
            return;
        }
        var btn = document.getElementById('btnPdf');
        btn.disabled = true;
        btn.innerHTML = '<b>Generating...</b>';
        html2pdf().set({
            margin: [8, 10, 8, 10],
            filename: paymentNo + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
        }).from(document.querySelector('.wrap')).save().then(function () {
            btn.disabled = false;
            btn.innerHTML = '<b>⤓ PDF</b>';
        });
    }

    document.getElementById('btnPdf').addEventListener('click', exportPDF);

    window.addEventListener('load', function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('autoprint') === '1') setTimeout(function () { window.print(); }, 400);
        if (params.get('autopdf') === '1') setTimeout(exportPDF, 600);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        e.preventDefault();
        window.location.href = '?page=dashboard';
    });
})();
</script>
</body>
</html>
