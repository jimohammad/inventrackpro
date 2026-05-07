<?php
/**
 * @var array $record
 * @var array $settings
 * @var array $stages
 * @var string $trackUrl  Staff link with token (no-print only)
 */
$curr = APP_CURRENCY;
$customerName = trim((string) ($record['party_name'] ?: $record['customer_name'] ?: ''));
$deviceLine     = trim((string) ($record['device_brand'] . ' ' . $record['device_model']));
$receivedTs     = $record['received_date'] ?: $record['created_at'];
$ds             = (int) $record['device_stage'];
$stageLabel     = $ds === 3
    ? 'Replaced (Factory)'
    : (($stages[$ds] ?? null)['label'] ?? '—');
$companyName = (string) ($settings['company_name'] ?? APP_NAME);

$rawImei = preg_replace('/\s+/', '', (string) ($record['imei'] ?? ''));
$imeiDigits = preg_replace('/\D/', '', $rawImei);
$imeiDisplay = $rawImei;
if (strlen($imeiDigits) >= 14) {
    $imeiDisplay = trim(chunk_split($imeiDigits, 3, ' '));
}

$trackShort = function_exists('app_service_track_short_label') ? app_service_track_short_label() : 'website/service';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($record['service_no']) ?> — Service Receipt</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    color: #000;
    background: #fff;
    padding: 8px 2px;
    -webkit-print-color-adjust: economy;
    print-color-adjust: economy;
}
.no-print {
    padding: 8px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    margin: -8px -2px 12px;
    text-align: center;
}
.no-print a, .no-print button {
    display: inline-block;
    background: #6366f1;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 5px;
    font-size: 13px;
    cursor: pointer;
    margin: 4px;
    text-decoration: none;
    font-family: system-ui, sans-serif;
}
.no-print .btn-secondary { background: #e5e7eb; color: #444; }
.no-print .btn-print { background: #059669; }

.wrap {
    max-width: 72mm;
    margin: 0 auto;
    padding: 10px 6px 12px;
}

.receipt-header {
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 2px dashed #cbd5e1;
    margin-bottom: 10px;
}
.company-name {
    font-size: 13px;
    font-weight: 800;
    color: #000;
    letter-spacing: 0.02em;
    line-height: 1.25;
}
.company-meta {
    font-size: 9px;
    color: #64748b;
    margin-top: 5px;
    line-height: 1.45;
}

.doc-title {
    text-align: center;
    margin: 12px 0 4px;
}
.doc-title h1 {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #000;
}
.doc-title p {
    font-size: 9px;
    color: #64748b;
    margin-top: 3px;
}

.section {
    margin: 12px 0;
}
.section-label {
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #000;
    margin-bottom: 6px;
    padding-bottom: 3px;
    border-bottom: 1px solid #e2e8f0;
}

.row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    padding: 4px 0;
    font-size: 11px;
    line-height: 1.35;
}
.row .lbl {
    color: #64748b;
    flex-shrink: 0;
    font-weight: 600;
}
.row .val {
    text-align: right;
    font-weight: 700;
    color: #000;
    word-break: break-word;
}

/* IMEI — primary focus */
.imei-hero {
    margin: 14px 0 16px;
    padding: 12px 10px;
    text-align: center;
    background: transparent;
    border: 2px solid #000;
    border-radius: 10px;
}
.imei-hero .imei-caption {
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #000;
    margin-bottom: 6px;
}
.imei-hero .imei-digits {
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #000;
    font-variant-numeric: tabular-nums;
    line-height: 1.35;
    word-break: break-all;
}
.block-note {
    margin-top: 8px;
    font-size: 10px;
    line-height: 1.45;
    color: #334155;
    white-space: pre-wrap;
    word-break: break-word;
}
.block-note .bn-lbl {
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #94a3b8;
    display: block;
    margin-bottom: 4px;
}

.track-box {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 2px dashed #cbd5e1;
    text-align: center;
}
.track-box .track-title {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000;
    margin-bottom: 6px;
}
.track-box .track-url {
    margin-top: 0;
    margin-bottom: 2px;
    font-size: 16px;
    font-weight: 800;
    color: #000;
    line-height: 1.35;
    letter-spacing: 0.03em;
    word-break: break-all;
}
.track-box .track-hint {
    font-size: 9px;
    color: #64748b;
    line-height: 1.45;
    margin-top: 8px;
    max-width: 100%;
}
.no-print .track-full-link {
    margin-top: 8px;
    font-size: 12px;
}
.no-print .track-full-link a {
    color: #4338ca;
    font-weight: 600;
}

.footer {
    text-align: center;
    font-size: 9px;
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px dashed #cbd5e1;
    color: #64748b;
    line-height: 1.45;
}

@media screen {
    body { background: #fff; padding: 12px 4px 24px; }
    .wrap {
        background: transparent;
        box-shadow: none;
        border-radius: 0;
    }
}
@media print {
    body { padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .wrap { box-shadow: none; max-width: 100%; border-radius: 0; }
    @page { size: 72mm auto; margin: 2mm; }
}
</style>
</head>
<body>

<div class="no-print">
    <button type="button" class="btn-print" id="svcThermalPrintBtn">Print</button>
    <a href="?page=service&action=detail&id=<?= (int) $record['id'] ?>" class="btn-secondary">Back to service</a>
    <?php if ($trackUrl !== ''): ?>
    <div class="track-full-link">
        <a href="<?= htmlspecialchars($trackUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open tracking page</a>
        <span style="color:#64748b;font-size:11px;"> — for copy/share on this device only</span>
    </div>
    <?php endif; ?>
</div>

<div class="wrap">
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
        <?php if (!empty($settings['company_address'])): ?>
        <div class="company-meta"><?= nl2br(htmlspecialchars((string) $settings['company_address'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($settings['company_phone'])): ?>
        <div class="company-meta" style="margin-top:3px;"><?= htmlspecialchars((string) $settings['company_phone']) ?></div>
        <?php endif; ?>
    </div>

    <div class="doc-title">
        <h1>Service Receipt</h1>
        <p>Keep for pickup &amp; warranty</p>
    </div>

    <div class="section">
        <div class="section-label">Service</div>
        <div class="row"><span class="lbl">Service #</span><span class="val"><?= htmlspecialchars((string) $record['service_no']) ?></span></div>
        <div class="row"><span class="lbl">Received</span><span class="val"><?= date('d M Y', strtotime((string) $receivedTs)) ?></span></div>
        <?php if (!empty($record['warehouse_name'])): ?>
        <div class="row"><span class="lbl">Location</span><span class="val"><?= htmlspecialchars((string) $record['warehouse_name']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-label">Customer</div>
        <div class="row"><span class="lbl">Name</span><span class="val"><?= htmlspecialchars($customerName ?: '—') ?></span></div>
        <?php if (!empty($record['customer_phone'])): ?>
        <div class="row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars((string) $record['customer_phone']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="imei-hero">
        <div class="imei-caption">IMEI · Serial</div>
        <div class="imei-digits"><?= htmlspecialchars($imeiDisplay !== '' ? $imeiDisplay : '—') ?></div>
    </div>

    <div class="section">
        <div class="section-label">Device</div>
        <?php if ($deviceLine !== ''): ?>
        <div class="row"><span class="lbl">Model</span><span class="val"><?= htmlspecialchars($deviceLine) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($record['fault_category'])): ?>
        <div class="row"><span class="lbl">Category</span><span class="val"><?= htmlspecialchars((string) $record['fault_category']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($record['fault_description'])): ?>
        <div class="block-note">
            <span class="bn-lbl">Reported issue</span>
            <?= htmlspecialchars((string) $record['fault_description']) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-label">Status</div>
        <div class="row"><span class="lbl">Status</span><span class="val"><?= htmlspecialchars((string) $record['status']) ?></span></div>
        <div class="row"><span class="lbl">Stage</span><span class="val"><?= htmlspecialchars($stageLabel) ?></span></div>
        <?php if ((float) $record['repair_cost'] > 0): ?>
        <div class="row"><span class="lbl">Est. repair</span><span class="val"><?= htmlspecialchars($curr) ?> <?= number_format((float) $record['repair_cost'], DECIMAL_PLACES) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($record['technician_name'])): ?>
        <div class="row"><span class="lbl">Technician</span><span class="val"><?= htmlspecialchars((string) $record['technician_name']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="track-box">
        <div class="track-title">Track repair status</div>
        <div class="track-url"><?= htmlspecialchars($trackShort) ?></div>
        <div class="track-hint" style="margin-top:6px;">
            Enter your device <strong>IMEI</strong> on that page to see live status.
        </div>
    </div>

    <div class="footer">
        <?= htmlspecialchars((string) ($settings['invoice_footer'] ?? 'Thank you for your business.')) ?><br>
        Printed <?= date('d M Y, H:i') ?> · <?= htmlspecialchars(Auth::name()) ?>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('svcThermalPrintBtn');
    if (btn) btn.addEventListener('click', function () { window.print(); });
    window.addEventListener('load', function () {
        var p = new URLSearchParams(window.location.search);
        if (p.get('autoprint') === '1') {
            setTimeout(function () { window.print(); }, 400);
        }
    });
})();
</script>
</body>
</html>
