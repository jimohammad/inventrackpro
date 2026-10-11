<?php
$companyName = $settings['company_name'] ?? (defined('PDF_COMPANY_NAME') ? PDF_COMPANY_NAME : (defined('APP_NAME') ? APP_NAME : ''));
$companyPhone = $settings['company_phone'] ?? (defined('PDF_COMPANY_PHONE') ? PDF_COMPANY_PHONE : '');
$totalImei = 0;
foreach ($groups as $item) {
    $totalImei += count($item['imeis']);
}
$cancelled = (($sale['status'] ?? '') === 'cancelled');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMEI barcodes — <?= htmlspecialchars((string) ($sale['invoice_no'] ?? '')) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            background: #fff;
        }
        .page { padding: 8px 10px; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #0e7490;
        }
        .company-name { font-size: 16px; font-weight: 800; color: #0e7490; }
        .company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 16px; font-weight: 700; color: #0e7490; text-align: right; }
        .doc-sub { font-size: 11px; color: #64748b; text-align: right; margin-top: 3px; }
        .party-box {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 8px;
            padding: 6px 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        .party-name { font-size: 14px; }
        .party-name strong { font-weight: 800; }
        .party-meta { font-size: 11px; color: #64748b; margin-top: 3px; }
        .meta-right { text-align: right; font-size: 12px; }
        .meta-right strong { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; }
        .cancelled {
            margin-bottom: 12px;
            padding: 8px 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 6px;
            font-weight: 700;
        }
        .empty-msg {
            padding: 24px;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }
        .bc-item { margin-bottom: 6px; }
        .bc-item-name {
            font-weight: 700;
            font-size: 10px;
            padding: 3px 6px;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
        }
        .bc-item-sub { font-weight: 500; color: #64748b; margin-left: 6px; }
        .bc-item-count { float: right; font-size: 10px; color: #0f766e; }
        .bc-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 4px;
            padding: 4px;
            border: 1px solid #99f6e4;
            border-radius: 0 0 4px 4px;
        }
        .bc-card {
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 2px 3px 3px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .bc-num { font-size: 8px; font-weight: 700; color: #64748b; margin-bottom: 1px; }
        .bc-svg svg { width: 100%; height: 8mm; display: block; }
        .bc-svg-2 svg { height: 6mm; }
        .bc-code {
            font-family: Consolas, 'Courier New', monospace;
            font-weight: 700;
            font-size: 8px;
            letter-spacing: 0;
            text-align: center;
            margin-top: 1px;
        }
        .bc-code-label { font-size: 7px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 2px; text-align: center; }
        .bc-code-2 { font-weight: 600; color: #334155; font-size: 8px; }
        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }
        @page { margin: 8mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <?php if (!empty($settings['company_address'])): ?>
            <div class="company-sub"><?= htmlspecialchars((string) $settings['company_address']) ?></div>
            <?php endif; ?>
            <?php if ($companyPhone !== ''): ?>
            <div class="company-sub">Tel: <?= htmlspecialchars((string) $companyPhone) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="doc-title">Invoice IMEI Barcodes</div>
            <div class="doc-sub"><?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="party-box">
        <div>
            <div class="party-name">Customer: <strong><?= htmlspecialchars((string) ($sale['party_name'] ?? '')) ?></strong></div>
            <div class="party-meta">
                <?php if (!empty($sale['party_code'])): ?>
                Code: <?= htmlspecialchars((string) $sale['party_code']) ?>
                <?php endif; ?>
                <?php if (!empty($sale['party_phone'])): ?>
                <?= !empty($sale['party_code']) ? ' · ' : '' ?><?= htmlspecialchars((string) $sale['party_phone']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="meta-right">
            <strong>Invoice</strong>
            <?= htmlspecialchars((string) ($sale['invoice_no'] ?? '')) ?>
            <?php if (!empty($sale['date'])): ?>
            <div><?= date('d M Y', strtotime((string) $sale['date'])) ?></div>
            <?php endif; ?>
            <div><?= (int) $totalImei ?> IMEI<?= $totalImei === 1 ? '' : 's' ?></div>
        </div>
    </div>

    <?php if ($cancelled): ?>
    <div class="cancelled">This invoice is cancelled.</div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
    <div class="empty-msg">No IMEIs are scanned on this invoice.</div>
    <?php else: ?>
    <?php include __DIR__ . '/_invoice_imei_barcode_sheet.php'; ?>
    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(defined('APP_NAME') ? APP_NAME : '') ?> — Invoice IMEI Barcodes</span>
        <span><?= htmlspecialchars((string) ($sale['invoice_no'] ?? '')) ?></span>
    </div>
</div>
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
</body>
</html>
