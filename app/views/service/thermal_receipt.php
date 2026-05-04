<?php
/**
 * @var array $record
 * @var array $settings
 * @var array $stages
 * @var string $trackUrl
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
    padding: 8px 4px;
}
.center { text-align: center; }
.bold { font-weight: 700; }
.title {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin: 10px 0 6px;
}
.divider {
    border: none;
    border-top: 1px dashed #000;
    margin: 8px 0;
}
.row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 3px 0;
    font-size: 11px;
    line-height: 1.35;
}
.row .lbl { color: #333; flex-shrink: 0; }
.row .val { text-align: right; font-weight: 600; word-break: break-word; }
.block {
    margin-top: 6px;
    font-size: 10px;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}
.track {
    font-size: 9px;
    margin-top: 8px;
    word-break: break-all;
    line-height: 1.35;
}
.footer {
    text-align: center;
    font-size: 9px;
    margin-top: 10px;
    color: #444;
}
@media screen {
    body { background: #e5e7eb; padding: 12px 4px 24px; }
    .wrap { background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
}
@media print {
    body { padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .wrap { box-shadow: none; max-width: 100%; }
    @page { size: 72mm auto; margin: 2mm; }
}
</style>
</head>
<body>

<div class="no-print">
    <button type="button" class="btn-print" id="svcThermalPrintBtn">Print</button>
    <a href="?page=service&action=detail&id=<?= (int) $record['id'] ?>" class="btn-secondary">Back to service</a>
</div>

<div class="wrap">
    <div class="center bold" style="font-size:14px;"><?= htmlspecialchars($companyName) ?></div>
    <?php if (!empty($settings['company_address'])): ?>
    <div class="center" style="font-size:9px;margin-top:4px;"><?= nl2br(htmlspecialchars((string) $settings['company_address'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($settings['company_phone'])): ?>
    <div class="center" style="font-size:9px;margin-top:2px;"><?= htmlspecialchars((string) $settings['company_phone']) ?></div>
    <?php endif; ?>

    <hr class="divider">

    <div class="center title">SERVICE RECEIPT</div>
    <div class="center" style="font-size:10px;">Keep for pickup &amp; warranty</div>

    <hr class="divider">

    <div class="row"><span class="lbl">Service #</span><span class="val"><?= htmlspecialchars($record['service_no']) ?></span></div>
    <div class="row"><span class="lbl">Received</span><span class="val"><?= date('d M Y', strtotime((string) $receivedTs)) ?></span></div>
    <?php if (!empty($record['warehouse_name'])): ?>
    <div class="row"><span class="lbl">Location</span><span class="val"><?= htmlspecialchars((string) $record['warehouse_name']) ?></span></div>
    <?php endif; ?>

    <hr class="divider">

    <div class="row"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($customerName ?: '—') ?></span></div>
    <?php if (!empty($record['customer_phone'])): ?>
    <div class="row"><span class="lbl">Phone</span><span class="val"><?= htmlspecialchars((string) $record['customer_phone']) ?></span></div>
    <?php endif; ?>

    <hr class="divider">

    <div class="row"><span class="lbl">IMEI</span><span class="val" style="font-size:10px;"><?= htmlspecialchars((string) $record['imei']) ?></span></div>
    <?php if ($deviceLine !== ''): ?>
    <div class="row"><span class="lbl">Device</span><span class="val"><?= htmlspecialchars($deviceLine) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($record['fault_category'])): ?>
    <div class="row"><span class="lbl">Category</span><span class="val"><?= htmlspecialchars((string) $record['fault_category']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($record['fault_description'])): ?>
    <div class="lbl" style="margin-top:6px;">Reported issue</div>
    <div class="block"><?= htmlspecialchars((string) $record['fault_description']) ?></div>
    <?php endif; ?>

    <hr class="divider">

    <div class="row"><span class="lbl">Status</span><span class="val"><?= htmlspecialchars((string) $record['status']) ?></span></div>
    <div class="row"><span class="lbl">Stage</span><span class="val"><?= htmlspecialchars($stageLabel) ?></span></div>
    <?php if ((float) $record['repair_cost'] > 0): ?>
    <div class="row"><span class="lbl">Est. repair</span><span class="val"><?= $curr ?> <?= number_format((float) $record['repair_cost'], DECIMAL_PLACES) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($record['technician_name'])): ?>
    <div class="row"><span class="lbl">Technician</span><span class="val"><?= htmlspecialchars((string) $record['technician_name']) ?></span></div>
    <?php endif; ?>

    <hr class="divider">

    <div class="center bold" style="font-size:10px;">Track status online</div>
    <div class="track"><?= htmlspecialchars($trackUrl) ?></div>

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
