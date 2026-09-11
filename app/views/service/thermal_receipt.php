<?php
/**
 * @var array $record
 * @var array $settings
 * @var array $stages
 * @var string $trackUrl  Staff link with token (no-print only)
 */
require_once __DIR__ . '/../../helpers/ServiceLockPattern.php';

$customerName = trim((string) ($record['party_name'] ?: $record['customer_name'] ?: ''));
$deviceLine     = trim((string) ($record['device_brand'] . ' ' . $record['device_model']));
$receivedTs     = $record['received_date'] ?: $record['created_at'];
$companyName = (string) ($settings['company_name'] ?? APP_NAME);

$rawImei = preg_replace('/\s+/', '', (string) ($record['imei'] ?? ''));
$imeiDigits = preg_replace('/\D/', '', $rawImei);
$imeiDisplay = $rawImei;
if (strlen($imeiDigits) >= 14) {
    $imeiDisplay = trim(chunk_split($imeiDigits, 3, ' '));
}

$trackShort = function_exists('app_service_track_short_label') ? app_service_track_short_label() : 'website/service';
$lockPatternRaw = $record['lock_pattern'] ?? null;
$lockPatternSvg = ServiceLockPattern::toGridSvg($lockPatternRaw, 132);
$screenPinRaw = trim((string) ($record['screen_pin'] ?? ''));
$screenPin = ServiceLockPattern::parseScreenPin($screenPinRaw);
if ($screenPin === null && $screenPinRaw !== '') {
    $screenPin = $screenPinRaw;
}
$pinDigitChars = $screenPin !== null
    ? preg_split('//u', $screenPin, -1, PREG_SPLIT_NO_EMPTY)
    : [];
$pinUseCells = count($pinDigitChars) > 0 && count($pinDigitChars) <= 6;
$printUnlockOnReceipt = !empty($record['print_unlock_on_receipt']);
$hasLockPattern = ServiceLockPattern::hasPattern($lockPatternRaw);
$showUnlockOnReceipt = $printUnlockOnReceipt && ($hasLockPattern || $screenPin !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($record['service_no']) ?> — Service Receipt</title>
<style>
<?php include __DIR__ . '/../partials/thermal_print_font.css.php'; ?>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 8px 2px;
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
    font-family: monospace;
}

.receipt-header {
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 2px dashed #cbd5e1;
    margin-bottom: 10px;
}
.company-name {
    font-size: 13px;
    color: #000;
    letter-spacing: 0;
    line-height: 1.25;
    text-transform: uppercase;
}
.company-meta {
    font-size: 11px;
    color: #000;
    margin-top: 5px;
    line-height: 1.35;
}

.doc-title {
    text-align: center;
    margin: 12px 0 4px;
}
.doc-title h1 {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
}
.doc-title p {
    font-size: 11px;
    color: #000;
    margin-top: 3px;
}

.section {
    margin: 12px 0;
}
.section-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
    margin-bottom: 6px;
    padding-bottom: 3px;
    border-bottom: 1px solid #000;
}

.row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    padding: 2px 0;
    font-size: 12px;
    line-height: 1.3;
}
.row .lbl {
    color: #000;
    flex-shrink: 0;
}
.row .val {
    text-align: right;
    color: #000;
    word-break: break-word;
    font-variant-numeric: tabular-nums;
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
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
    margin-bottom: 6px;
}
.imei-hero .imei-digits {
    font-size: 14px;
    letter-spacing: 0.06em;
    color: #000;
    font-variant-numeric: tabular-nums;
    line-height: 1.3;
    word-break: break-all;
}
.issue-box {
    margin-top: 12px;
    border: 2px solid #000;
    border-radius: 8px;
    overflow: hidden;
}
.issue-box .issue-label {
    display: block;
    text-align: center;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
    padding: 5px 8px 4px;
    border-bottom: 2px dashed #000;
}
.issue-box .issue-text {
    padding: 8px 9px 9px;
    font-size: 12px;
    line-height: 1.35;
    color: #000;
    white-space: pre-wrap;
    word-break: break-word;
}

.track-box {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 2px dashed #cbd5e1;
    text-align: center;
}
.track-box .track-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
    margin-bottom: 6px;
}
.track-box .track-url {
    margin-top: 0;
    margin-bottom: 2px;
    font-size: 14px;
    color: #000;
    line-height: 1.3;
    letter-spacing: 0;
    word-break: break-all;
}
.track-box .track-hint {
    font-size: 11px;
    color: #000;
    line-height: 1.35;
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

.disclaimer {
    margin-top: 12px;
    padding: 10px 8px;
    border: 1px dashed #94a3b8;
    border-radius: 6px;
    text-align: center;
    line-height: 1.45;
    color: #000;
}
.disclaimer .disclaimer-en {
    font-size: 11px;
}
.disclaimer .disclaimer-ar {
    margin-top: 6px;
    font-size: 11px;
    direction: rtl;
    unicode-bidi: embed;
}

.pattern-lock-box {
    margin: 14px 0 12px;
    padding: 10px 8px 12px;
    text-align: center;
    border: 2px solid #000;
    border-radius: 10px;
    color: #000;
    background: #f1f5f9;
}
.pattern-lock-box .pl-section-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
    padding-bottom: 4px;
    border-bottom: 1px dashed #000;
}
.pin-hero {
    margin: 0 0 12px;
    padding: 10px 8px 11px;
    text-align: center;
    background: #fff;
    border: 2px solid #000;
    border-radius: 8px;
}
.pin-hero .pin-caption {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #000;
    margin-bottom: 8px;
}
.pin-digits-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.pin-cell {
    min-width: 28px;
    height: 32px;
    padding: 0 5px;
    border: 2px solid #000;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-variant-numeric: tabular-nums;
    line-height: 1;
    background: #fff;
    box-sizing: border-box;
    font-family: inherit;
}
.pin-cell.empty {
    border-style: dashed;
    border-color: #64748b;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
}
.pin-digits-wide {
    font-size: 15px;
    letter-spacing: 0.16em;
    font-variant-numeric: tabular-nums;
    line-height: 1.3;
    word-break: break-all;
    font-family: inherit;
}
.pl-pattern-divider {
    margin: 0 0 8px;
    border-top: 1px dashed #94a3b8;
}
.pattern-lock-box .pl-caption {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
}
.pattern-lock-box svg {
    display: block;
    margin: 0 auto;
    width: 132px;
    height: 132px;
}

.footer {
    text-align: center;
    font-size: 11px;
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px dashed #000;
    color: #000;
    line-height: 1.35;
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
    body {
        padding: 0;
        background: #fff;
        font-family: monospace;
        font-weight: 400;
        -webkit-font-smoothing: none;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .wrap {
        font-family: monospace;
        box-shadow: none;
        max-width: 100%;
        border-radius: 0;
    }
    .no-print { display: none !important; }
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
        <a href="<?= htmlspecialchars($trackUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Open tracking page</a>
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
        <div class="issue-box">
            <span class="issue-label">Reported Issue</span>
            <div class="issue-text"><?= htmlspecialchars(trim((string) $record['fault_description'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($showUnlockOnReceipt): ?>
    <div class="pattern-lock-box">
        <div class="pl-section-title">Device unlock (for return)</div>

        <?php if ($screenPin !== null): ?>
        <div class="pin-hero">
            <div class="pin-caption">Screen PIN</div>
            <?php if ($pinUseCells): ?>
            <div class="pin-digits-row" aria-label="Screen PIN">
                <?php foreach ($pinDigitChars as $digit): ?>
                <span class="pin-cell"><?= htmlspecialchars($digit) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="pin-digits-wide" aria-label="Screen PIN"><?= htmlspecialchars(implode(' ', $pinDigitChars)) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($hasLockPattern): ?>
        <?php if ($screenPin !== null): ?>
        <div class="pl-pattern-divider"></div>
        <?php endif; ?>
        <div class="pl-caption">Screen lock pattern</div>
        <?= $lockPatternSvg ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-label">Service</div>
        <div class="row"><span class="lbl">Service #</span><span class="val"><?= htmlspecialchars((string) $record['service_no']) ?></span></div>
        <div class="row"><span class="lbl">Received</span><span class="val"><?= date('d M Y', strtotime((string) $receivedTs)) ?></span></div>
        <?php if (!empty($record['warehouse_name'])): ?>
        <div class="row"><span class="lbl">Location</span><span class="val"><?= htmlspecialchars((string) $record['warehouse_name']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="track-box">
        <div class="track-title">Track repair status</div>
        <div class="track-url"><?= htmlspecialchars($trackShort) ?></div>
        <div class="track-hint" style="margin-top:6px;">
            Enter your device <strong>IMEI</strong> on that page to see live status.
        </div>
    </div>

    <div class="disclaimer">
        <div class="disclaimer-en">Company is not responsible in case customer personal data is deleted.</div>
        <div class="disclaimer-ar">الشركة غير مسؤولة في حال حذف البيانات الشخصية للعميل.</div>
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
            setTimeout(function () { window.print(); }, 300);
        }
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
