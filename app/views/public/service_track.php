<?php
$stages = ServiceController::stages();
$currentStage = $record ? (int)$record['device_stage'] : -1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Your Device — <?= defined('APP_TITLE') ? APP_TITLE : 'Iqbal Electronics' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { box-sizing:border-box;margin:0;padding:0; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#f8fafc,#e0e7ff);min-height:100vh;padding:20px; }
.pt-wrap { max-width:600px;margin:0 auto; }
.pt-logo { text-align:center;margin-bottom:20px; }
.pt-logo h1 { font-size:1.5rem;font-weight:800;color:#1e3a5f; }
.pt-logo p { font-size:.88rem;color:#64748b;margin-top:4px; }

.pt-search { background:#fff;border-radius:16px;padding:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);margin-bottom:20px; }
.pt-search form { display:flex;gap:8px; }
.pt-search input { flex:1;padding:12px 16px;border:2px solid #e5e7eb;border-radius:10px;font-size:1rem;font-family:monospace;letter-spacing:.5px;outline:none; }
.pt-search input:focus { border-color:#6366f1; }
.pt-search button { padding:12px 24px;background:#6366f1;color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer; }

.pt-card { background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06);margin-bottom:16px; }

.pt-device { display:flex;gap:14px;align-items:center;margin-bottom:20px; }
.pt-device-icon { width:56px;height:56px;border-radius:14px;background:rgba(99,102,241,.1);color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0; }
.pt-device h2 { font-size:1.05rem;color:#1e293b; }
.pt-device p { font-size:.82rem;color:#64748b;margin-top:2px;font-family:monospace; }

/* Journey */
.pt-journey { display:flex;justify-content:space-between;gap:4px;position:relative;padding:10px 0 20px; }
.pt-step { flex:1;text-align:center;position:relative; }
.pt-step-icon { width:48px;height:48px;border-radius:50%;margin:0 auto 10px;background:#f1f5f9;border:3px solid #e2e8f0;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .3s; }
.pt-step.active .pt-step-icon { border-color:currentColor;box-shadow:0 0 0 5px rgba(currentColor,.2);animation:pulse 1.5s infinite; }
.pt-step.done .pt-step-icon { background:currentColor;color:#fff !important;border-color:currentColor; }
@keyframes pulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.08); } }
.pt-step-label { font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px; }
.pt-step.active .pt-step-label, .pt-step.done .pt-step-label { color:currentColor; }
.pt-step::after { content:'';position:absolute;top:24px;left:calc(50% + 24px);right:calc(-50% + 24px);height:3px;background:#e2e8f0;z-index:-1; }
.pt-step:last-child::after { display:none; }
.pt-step.done::after { background:currentColor; }

.pt-status { padding:14px 18px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:10px;display:flex;align-items:center;gap:10px;font-weight:600;color:#0369a1;font-size:.92rem;margin-top:12px; }

/* Rows */
.pt-rows { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px; }
.pt-row { padding:10px 12px;background:#f8fafc;border-radius:8px; }
.pt-row-label { font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:600; }
.pt-row-value { font-size:.9rem;color:#1e293b;font-weight:600;margin-top:2px; }

/* History timeline */
.pt-hist { margin-top:16px;padding-top:14px;border-top:1px solid #e5e7eb; }
.pt-hist-title { font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px; }
.pt-hist-item { display:flex;gap:10px;align-items:flex-start;padding:8px 0;font-size:.82rem; }
.pt-hist-dot { width:10px;height:10px;border-radius:50%;background:#6366f1;margin-top:5px;flex-shrink:0; }
.pt-hist-body { flex:1;color:#475569; }
.pt-hist-date { font-size:.72rem;color:#94a3b8; }

.pt-notfound { text-align:center;padding:40px 20px;color:#64748b; }
.pt-notfound i { font-size:3rem;color:#cbd5e1;display:block;margin-bottom:10px; }

.pt-footer { text-align:center;font-size:.78rem;color:#94a3b8;margin-top:24px; }
.pt-footer a { color:#6366f1;text-decoration:none; }
</style>
</head>
<body>
<div class="pt-wrap">
    <div class="pt-logo">
        <h1>📱 Iqbal Electronics</h1>
        <p>Service repair status — <strong><?= htmlspecialchars(app_service_track_short_label()) ?></strong></p>
    </div>

    <!-- Search -->
    <div class="pt-search">
        <form method="GET" action="<?= htmlspecialchars(app_service_track_url(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="token" value="<?= htmlspecialchars($token ?? '') ?>" placeholder="Tracking code or IMEI…" autocomplete="off" autofocus>
            <button type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <?php if (!empty($token) && !$record): ?>
    <div class="pt-card pt-notfound">
        <i class="bi bi-search"></i>
        <strong>No record found</strong>
        <p style="margin-top:6px;">Check your tracking code or IMEI, or visit <strong><?= htmlspecialchars(app_service_track_short_label()) ?></strong> and try again.</p>
    </div>

    <?php elseif ($record): ?>
    <div class="pt-card">
        <div class="pt-device">
            <div class="pt-device-icon"><i class="bi bi-phone"></i></div>
            <div>
                <h2><?= htmlspecialchars(trim(($record['device_brand'] ?? '') . ' ' . ($record['device_model'] ?? '')) ?: 'Device') ?></h2>
                <p><?= htmlspecialchars($record['imei']) ?></p>
            </div>
        </div>

        <div class="pt-journey">
            <?php foreach ($stages as $k => $s):
                $class = $k < $currentStage ? 'done' : ($k === $currentStage ? 'active' : '');
            ?>
            <div class="pt-step <?= $class ?>" style="color:<?= $s['color'] ?>;">
                <div class="pt-step-icon"><i class="bi <?= $s['icon'] ?>"></i></div>
                <div class="pt-step-label"><?= $s['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-status" style="background:<?= ServiceController::statusColor($record['status']) ?>15;color:<?= ServiceController::statusColor($record['status']) ?>;">
            <i class="bi bi-info-circle-fill"></i>
            Status: <strong><?= $record['status'] ?></strong>
        </div>

        <div class="pt-rows">
            <div class="pt-row">
                <div class="pt-row-label">Service #</div>
                <div class="pt-row-value"><?= htmlspecialchars($record['service_no']) ?></div>
            </div>
            <div class="pt-row">
                <div class="pt-row-label">Received</div>
                <div class="pt-row-value"><?= date('d M Y', strtotime($record['received_date'])) ?></div>
            </div>
            <?php if ($record['customer_name']): ?>
            <div class="pt-row">
                <div class="pt-row-label">Customer</div>
                <div class="pt-row-value"><?= htmlspecialchars($record['customer_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($record['delivered_date']): ?>
            <div class="pt-row">
                <div class="pt-row-label">Delivered</div>
                <div class="pt-row-value"><?= date('d M Y', strtotime($record['delivered_date'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($history)): ?>
        <div class="pt-hist">
            <div class="pt-hist-title">Progress Updates</div>
            <?php foreach (array_reverse($history) as $h): ?>
            <div class="pt-hist-item">
                <div class="pt-hist-dot"></div>
                <div class="pt-hist-body">
                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $h['event_type']))) ?>: <?= htmlspecialchars($h['new_value'] ?? '') ?>
                    <div class="pt-hist-date"><?= date('d M Y · h:i A', strtotime($h['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="pt-card pt-notfound">
        <i class="bi bi-ticket-detailed"></i>
        <strong>Track your repair</strong>
        <p style="margin-top:6px;">Enter the <strong>tracking code</strong> from your receipt, or your device <strong>IMEI</strong>, above.</p>
    </div>
    <?php endif; ?>

    <div class="pt-footer">
        <p>Need help? Contact us at the shop.</p>
        <p style="margin-top:6px;"><a href="/">← Back to Home</a></p>
    </div>
</div>
</body>
</html>
