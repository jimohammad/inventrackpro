<?php
$stages = ServiceController::stages();
$currentStage = $record ? (int)$record['device_stage'] : -1;
$companyName = 'Iqbal Electronics Co. LLC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Service status — <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="apple-touch-icon" href="/assets/pwa/apps/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/pwa/apps/icons/icon-192.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --card: #ffffff;
    --accent: #7c3aed;
    --accent-deep: #5b21b6;
    --surface: #f8fafc;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Inter, system-ui, -apple-system, sans-serif;
    color: var(--ink);
    min-height: 100vh;
    background:
        radial-gradient(900px 420px at 8% -12%, rgba(124,58,237,0.16), transparent 55%),
        radial-gradient(780px 380px at 108% 4%, rgba(15,23,42,0.07), transparent 52%),
        linear-gradient(180deg, #f5f3ff 0%, #f8fafc 48%, #ffffff 100%);
}
.wrap {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 20px 16px;
    padding-bottom: max(32px, env(safe-area-inset-bottom));
}
.shell { width: 100%; max-width: 440px; }
.back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    text-decoration: none;
    font-size: 0.86rem;
    font-weight: 600;
    margin-bottom: 14px;
    transition: color .15s;
}
.back:hover { color: var(--ink); }

.card {
    background: var(--card);
    border: 1px solid rgba(255,255,255,0.9);
    border-radius: 22px;
    box-shadow: 0 18px 48px rgba(15,23,42,0.10), 0 2px 8px rgba(15,23,42,0.04);
    overflow: hidden;
    animation: rise .45s cubic-bezier(.16,1,.3,1) both;
}
@keyframes rise {
    from { opacity: 0; transform: translateY(14px) scale(.985); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@media (prefers-reduced-motion: reduce) {
    .card, .pt-step.active .pt-step-icon { animation: none; }
}

.card-head {
    padding: 22px 20px 18px;
    background:
        radial-gradient(320px 140px at 90% -30%, rgba(124,58,237,0.45), transparent 70%),
        linear-gradient(155deg, #1e293b 0%, #0f172a 55%, #312e81 100%);
    color: #fff;
}
.head-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}
.head-ic {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    color: #fff;
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    box-shadow: 0 8px 18px rgba(124,58,237,0.35);
}
.head-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.65);
}
.card-head h1 {
    margin: 0 0 6px;
    font-size: 1.28rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.25;
}
.card-head .sub {
    margin: 0;
    color: rgba(255,255,255,0.72);
    font-size: 0.88rem;
    line-height: 1.45;
    font-weight: 500;
}
.card-body { padding: 18px 16px 20px; }

.pt-search {
    display: flex;
    gap: 8px;
    align-items: stretch;
}
.pt-search input {
    flex: 1;
    min-width: 0;
    border: 1.5px solid var(--line);
    border-radius: 14px;
    padding: 13px 14px;
    font: 600 0.95rem Inter, system-ui, sans-serif;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
    color: var(--ink);
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.pt-search input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(124,58,237,0.22);
    background: #faf5ff;
}
.pt-search button {
    width: 52px;
    flex: none;
    border: 0;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, var(--accent), var(--accent-deep));
    cursor: pointer;
    font-size: 1.15rem;
    box-shadow: 0 8px 18px rgba(91,33,182,0.28);
    transition: transform .12s, filter .15s;
}
.pt-search button:hover { filter: brightness(1.05); }
.pt-search button:active { transform: scale(0.96); }

.pt-panel {
    margin-top: 14px;
    background: var(--surface);
    border: 1.5px solid var(--line);
    border-radius: 16px;
    padding: 16px;
}
.pt-notfound {
    text-align: center;
    padding: 22px 12px;
    color: var(--muted);
}
.pt-notfound i {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin: 0 auto 12px;
    border-radius: 14px;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 1.35rem;
}
.pt-notfound strong {
    display: block;
    color: var(--ink);
    font-size: 1rem;
    margin-bottom: 6px;
}
.pt-notfound p { font-size: 0.88rem; line-height: 1.45; }

.pt-device {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 18px;
}
.pt-device-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    box-shadow: 0 8px 16px rgba(124,58,237,0.28);
}
.pt-device h2 { font-size: 1.02rem; font-weight: 800; letter-spacing: -0.02em; }
.pt-device p {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 3px;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

.pt-journey {
    display: flex;
    justify-content: space-between;
    gap: 4px;
    position: relative;
    padding: 8px 0 16px;
}
.pt-step { flex: 1; text-align: center; position: relative; }
.pt-step-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    margin: 0 auto 8px;
    background: #fff;
    border: 2.5px solid #e2e8f0;
    color: #94a3b8;
    display: grid;
    place-items: center;
    font-size: 1rem;
    transition: all .25s;
}
.pt-step.active .pt-step-icon {
    border-color: currentColor;
    box-shadow: 0 0 0 5px rgba(currentColor, .18);
    animation: pulse 1.6s infinite;
}
.pt-step.done .pt-step-icon {
    background: currentColor;
    color: #fff !important;
    border-color: currentColor;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}
.pt-step-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.pt-step.active .pt-step-label,
.pt-step.done .pt-step-label { color: currentColor; }
.pt-step::after {
    content: '';
    position: absolute;
    top: 22px;
    left: calc(50% + 22px);
    right: calc(-50% + 22px);
    height: 3px;
    background: #e2e8f0;
    z-index: -1;
}
.pt-step:last-child::after { display: none; }
.pt-step.done::after { background: currentColor; }

.pt-status {
    padding: 12px 14px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-top: 4px;
}
.pt-rows {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 12px;
}
.pt-row {
    padding: 10px 12px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
}
.pt-row-label {
    font-size: 0.68rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
}
.pt-row-value {
    font-size: 0.9rem;
    color: var(--ink);
    font-weight: 700;
    margin-top: 3px;
}

.pt-hist {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--line);
}
.pt-hist-title {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 10px;
}
.pt-hist-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 8px 0;
    font-size: 0.84rem;
}
.pt-hist-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--accent);
    margin-top: 5px;
    flex-shrink: 0;
}
.pt-hist-body { flex: 1; color: #475569; font-weight: 500; line-height: 1.4; }
.pt-hist-date { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; font-weight: 600; }

.pt-footer {
    text-align: center;
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 16px;
    line-height: 1.5;
    font-weight: 500;
}
</style>
</head>
<body>
<div class="wrap">
    <div class="shell">
        <a class="back" href="/apps"><i class="bi bi-arrow-left"></i> Apps menu</a>

        <div class="card">
            <div class="card-head">
                <div class="head-badge">
                    <span class="head-ic"><i class="bi bi-tools"></i></span>
                    <span class="head-kicker"><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h1>Service status</h1>
                <p class="sub">Enter your tracking code or IMEI to see repair progress.</p>
            </div>
            <div class="card-body">
                <form class="pt-search" method="GET" action="<?= htmlspecialchars(app_service_track_url(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="text" name="token" value="<?= htmlspecialchars($token ?? '') ?>" placeholder="Tracking code or IMEI…" autocomplete="off" autofocus aria-label="Tracking code or IMEI">
                    <button type="submit" title="Search" aria-label="Search"><i class="bi bi-search"></i></button>
                </form>

                <?php if (!empty($token) && !$record): ?>
                <div class="pt-panel pt-notfound">
                    <i class="bi bi-search"></i>
                    <strong>No record found</strong>
                    <p>Check your tracking code or IMEI and try again.</p>
                </div>

                <?php elseif ($record): ?>
                <div class="pt-panel">
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
                        <div class="pt-step <?= $class ?>" style="color:<?= htmlspecialchars($s['color']) ?>;">
                            <div class="pt-step-icon"><i class="bi <?= htmlspecialchars($s['icon']) ?>"></i></div>
                            <div class="pt-step-label"><?= htmlspecialchars($s['label']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-status" style="background:<?= htmlspecialchars(ServiceController::statusColor($record['status'])) ?>18;color:<?= htmlspecialchars(ServiceController::statusColor($record['status'])) ?>;">
                        <i class="bi bi-info-circle-fill"></i>
                        Status: <strong><?= htmlspecialchars($record['status']) ?></strong>
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
                        <div class="pt-hist-title">Progress updates</div>
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
                <?php endif; ?>
            </div>
        </div>

        <div class="pt-footer">
            Need help? Contact us at the shop.
        </div>
    </div>
</div>
<script src="/assets/pwa/apps/nav.js" defer></script>
</body>
</html>
