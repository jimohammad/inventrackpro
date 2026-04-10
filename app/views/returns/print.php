<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return <?= $return['return_no'] ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
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

    .party-section { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
    .party-box { background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; flex: 1; }
    .party-box label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #dc2626; letter-spacing: 0.5px; }
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
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#dc2626;color:#fff;">⎙ Print</button>
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
