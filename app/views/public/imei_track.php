<?php
/** @var string $imei */
/** @var string|null $saleDate */
/** @var string|null $remainingText */
/** @var string|null $error */
/** @var bool $isExpired */
/** @var int $warrantyMonths */

$warrantyMonths = $warrantyMonths ?? 13;
$hasResult = !empty($saleDate) && !empty($remainingText);

// Derive expiry date and a warranty progress percentage for the result view.
$expiryDate = null;
$progress = null;   // 0..100 used (consumed) portion
$daysLeft = null;
if ($hasResult) {
    try {
        $soldAt = new DateTimeImmutable($saleDate);
        $warrantyEnd = $soldAt->modify('+' . (int) $warrantyMonths . ' months');
        $today = new DateTimeImmutable('today');
        $expiryDate = $warrantyEnd->format('d M Y');

        $totalSpan = max(1, $warrantyEnd->getTimestamp() - $soldAt->getTimestamp());
        $used = $today->getTimestamp() - $soldAt->getTimestamp();
        $progress = (int) round(min(100, max(0, ($used / $totalSpan) * 100)));

        $daysLeft = (int) floor(max(0, $warrantyEnd->getTimestamp() - $today->getTimestamp()) / 86400);
    } catch (Throwable $e) {
        $expiryDate = null;
    }
}
$accent = !empty($isExpired) ? '#dc2626' : '#16a34a';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title>Warranty Check &middot; Iqbal Electronics Co. LLC</title>
    <link rel="apple-touch-icon" href="/assets/pwa/apps/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/pwa/apps/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/assets/pwa/apps/icons/icon-512.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e9eef5;
            --brand: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--ink);
            min-height: 100vh;
            background:
                radial-gradient(1100px 520px at 12% -10%, rgba(37,99,235,0.10), transparent 60%),
                radial-gradient(900px 500px at 110% 10%, rgba(14,165,233,0.08), transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f4f7fb 60%, #eef2f8 100%);
        }
        .track-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
        }
        .track-card {
            width: 100%;
            max-width: 480px;
            background: rgba(255,255,255,0.98);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 22px;
            box-shadow: 0 30px 70px -20px rgba(2,6,23,0.55), 0 8px 24px rgba(2,6,23,0.25);
            overflow: hidden;
            animation: rise 0.5s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(18px) scale(.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .track-head {
            padding: 26px 26px 22px;
            background:
                radial-gradient(420px 160px at 80% -40%, rgba(37,99,235,0.30), transparent 70%),
                linear-gradient(160deg, #1d4ed8, #2563eb 55%, #0ea5e9);
            color: #fff;
        }
        .brand-row {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 16px;
        }
        .brand-badge {
            width: 40px; height: 40px;
            display: grid; place-items: center;
            border-radius: 12px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.28);
            font-size: 1.15rem;
            backdrop-filter: blur(4px);
        }
        .brand-name { font-weight: 800; font-size: 1.02rem; letter-spacing: .2px; line-height: 1.1; }
        .brand-sub { font-size: .74rem; opacity: .82; letter-spacing: .4px; text-transform: uppercase; }
        .head-title { font-size: 1.45rem; font-weight: 800; margin: 0; letter-spacing: -.4px; }
        .head-sub { margin-top: 6px; font-size: .9rem; opacity: .9; }
        .policy-chip {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 14px;
            padding: 5px 11px;
            border-radius: 999px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.26);
            font-size: .76rem; font-weight: 600;
        }
        .track-body { padding: 22px 26px 26px; }

        .field-label { font-size: .78rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .input-shell {
            display: flex; align-items: center;
            border: 1.6px solid var(--line);
            border-radius: 14px;
            background: #fff;
            transition: border-color .18s, box-shadow .18s;
            overflow: hidden;
        }
        .input-shell:focus-within {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(37,99,235,0.14);
        }
        .input-shell .ico { padding: 0 4px 0 15px; color: var(--muted); font-size: 1.1rem; }
        .input-shell input {
            flex: 1; border: 0; outline: 0;
            padding: 14px 8px 14px 4px;
            font-size: 1rem; font-weight: 600; letter-spacing: .4px;
            background: transparent; color: var(--ink);
            min-width: 0;
        }
        .input-shell input::placeholder { font-weight: 500; letter-spacing: .2px; color: #94a3b8; }
        .btn-scan {
            flex: none;
            display: grid; place-items: center;
            width: 46px; height: 46px;
            margin: 4px 4px 4px 0;
            border: 0; border-radius: 11px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: var(--brand);
            font-size: 1.2rem;
            cursor: pointer;
            transition: background .15s, transform .12s, box-shadow .15s;
        }
        .btn-scan:hover { background: linear-gradient(135deg, #dbeafe, #bfdbfe); box-shadow: 0 4px 12px rgba(37,99,235,0.18); }
        .btn-scan:active { transform: scale(.96); }
        .scan-hint {
            margin-top: 10px;
            font-size: .8rem;
            color: var(--muted);
            text-align: center;
        }
        .scan-hint button {
            border: 0; background: none; padding: 0;
            color: var(--brand); font-weight: 600; cursor: pointer;
            text-decoration: underline; text-underline-offset: 2px;
        }

        /* Camera scanner overlay */
        .scan-overlay {
            position: fixed; inset: 0; z-index: 1000;
            display: none; align-items: flex-end; justify-content: center;
            background: rgba(15,23,42,0.72);
            backdrop-filter: blur(6px);
            padding: 0;
        }
        .scan-overlay.is-open { display: flex; }
        @media (min-width: 520px) {
            .scan-overlay { align-items: center; padding: 24px; }
        }
        .scan-panel {
            width: 100%; max-width: 420px;
            background: #fff;
            border-radius: 22px 22px 0 0;
            overflow: hidden;
            box-shadow: 0 -8px 40px rgba(2,6,23,0.35);
            animation: sheetUp .35s cubic-bezier(.16,1,.3,1) both;
        }
        @media (min-width: 520px) {
            .scan-panel { border-radius: 22px; animation: rise .35s cubic-bezier(.16,1,.3,1) both; }
        }
        @keyframes sheetUp {
            from { opacity: 0; transform: translateY(100%); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .scan-panel-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 18px 12px;
            border-bottom: 1px solid var(--line);
        }
        .scan-panel-title { font-size: 1rem; font-weight: 800; margin: 0; }
        .scan-panel-sub { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }
        .btn-scan-close {
            width: 36px; height: 36px; border: 0; border-radius: 10px;
            background: #f1f5f9; color: var(--muted); font-size: 1.1rem;
            cursor: pointer; display: grid; place-items: center;
        }
        .btn-scan-close:hover { background: #e2e8f0; color: var(--ink); }
        .scan-viewport {
            position: relative;
            background: #0f172a;
            min-height: 280px;
        }
        .scan-viewport #qr-reader { border: 0 !important; }
        .scan-viewport #qr-reader video { object-fit: cover !important; }
        .scan-viewport #qr-reader__scan_region { border-radius: 0 !important; }
        .scan-status {
            padding: 12px 18px 18px;
            font-size: .85rem; color: var(--muted); text-align: center;
            line-height: 1.45;
        }
        .scan-status.is-error { color: #dc2626; }
        .scan-status.is-ok { color: #16a34a; font-weight: 600; }
        .btn-check {
            width: 100%; margin-top: 14px;
            border: 0; border-radius: 14px;
            padding: 13px 16px;
            font-weight: 700; font-size: 1rem; color: #fff;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 12px 24px -10px rgba(37,99,235,0.7);
            cursor: pointer;
            transition: transform .12s, box-shadow .18s, filter .18s;
        }
        .btn-check:hover { filter: brightness(1.05); box-shadow: 0 16px 30px -10px rgba(37,99,235,0.75); }
        .btn-check:active { transform: translateY(1px); }

        .alert-soft {
            margin-top: 20px;
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: .92rem; font-weight: 500;
            animation: rise .35s ease both;
        }
        .alert-soft i { font-size: 1.15rem; margin-top: 1px; }

        .result {
            margin-top: 22px;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            animation: rise .4s ease both;
        }
        .status-banner {
            padding: 18px;
            display: flex; align-items: center; gap: 14px;
            color: #fff;
        }
        .status-ok  { background: linear-gradient(135deg, #16a34a, #15803d); }
        .status-bad { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .status-ic {
            width: 44px; height: 44px; flex: none;
            display: grid; place-items: center;
            border-radius: 12px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 1.35rem;
        }
        .status-label { font-size: .76rem; text-transform: uppercase; letter-spacing: .6px; opacity: .9; font-weight: 600; }
        .status-text { font-size: 1.18rem; font-weight: 800; line-height: 1.15; }

        .result-grid { padding: 6px 18px 4px; }
        .result-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 13px 0; border-bottom: 1px dashed var(--line); }
        .result-row:last-child { border-bottom: 0; }
        .result-label { font-size: .82rem; color: var(--muted); font-weight: 600; }
        .result-value { font-size: 1rem; font-weight: 700; color: var(--ink); text-align: right; }

        .progress-wrap { padding: 4px 18px 20px; }
        .progress-meta { display: flex; justify-content: space-between; font-size: .76rem; color: var(--muted); font-weight: 600; margin-bottom: 7px; }
        .progress-track { height: 9px; border-radius: 999px; background: #eef2f7; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 999px; transition: width .6s cubic-bezier(.16,1,.3,1); }

        .imei-tag { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .92rem; }

        .foot { text-align: center; margin-top: 18px; font-size: .78rem; color: #94a3b8; }
        .foot a { color: var(--brand); text-decoration: none; font-weight: 600; }
        .foot a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="track-wrap">
    <div style="width:100%; max-width:480px;">
        <div class="track-card">
            <div class="track-head">
                <div class="brand-row">
                    <div class="brand-badge"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="brand-name">Iqbal Electronics Co. LLC</div>
                    </div>
                </div>
                <h1 class="head-title">Warranty Check</h1>
                <span class="policy-chip"><i class="bi bi-calendar-check"></i> Covered for <?= (int) $warrantyMonths ?> months from purchase</span>
            </div>

            <div class="track-body">
                <form method="GET" action="/imei" id="warranty-form">
                    <label class="field-label" for="imei-input">IMEI / Serial Number</label>
                    <div class="input-shell">
                        <span class="ico"><i class="bi bi-upc-scan"></i></span>
                        <input
                            id="imei-input"
                            type="text"
                            name="imei"
                            value="<?= htmlspecialchars($imei ?? '') ?>"
                            placeholder="e.g. 356938035643809"
                            autocomplete="off"
                            inputmode="latin"
                            autofocus
                            required
                        >
                        <button type="button" class="btn-scan" id="btn-open-scan" aria-label="Scan barcode with camera" title="Scan barcode">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                    </div>
                    <p class="scan-hint">Have a QR or 2D barcode on the box? <button type="button" id="btn-open-scan-link">Scan with camera</button></p>
                    <button class="btn-check" type="submit"><i class="bi bi-search me-1"></i> Check Warranty</button>
                </form>

                <?php if (!empty($error)): ?>
                    <div class="alert-soft">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php elseif ($hasResult): ?>
                    <div class="result">
                        <div class="status-banner <?= !empty($isExpired) ? 'status-bad' : 'status-ok' ?>">
                            <div class="status-ic">
                                <i class="bi <?= !empty($isExpired) ? 'bi-x-circle-fill' : 'bi-patch-check-fill' ?>"></i>
                            </div>
                            <div>
                                <div class="status-label">Warranty Status</div>
                                <div class="status-text"><?= !empty($isExpired) ? 'Expired' : 'Active' ?></div>
                            </div>
                        </div>

                        <div class="result-grid">
                            <div class="result-row">
                                <div class="result-label"><i class="bi bi-upc me-1"></i> IMEI / Serial</div>
                                <div class="result-value imei-tag"><?= htmlspecialchars($imei ?? '') ?></div>
                            </div>
                            <div class="result-row">
                                <div class="result-label"><i class="bi bi-bag-check me-1"></i> Date of Selling</div>
                                <div class="result-value"><?= htmlspecialchars(date('d M Y', strtotime($saleDate))) ?></div>
                            </div>
                            <?php if ($expiryDate !== null): ?>
                            <div class="result-row">
                                <div class="result-label"><i class="bi bi-calendar-x me-1"></i> Warranty Ends</div>
                                <div class="result-value"><?= htmlspecialchars($expiryDate) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="result-row">
                                <div class="result-label"><i class="bi bi-hourglass-split me-1"></i> Remaining</div>
                                <div class="result-value" style="color:<?= $accent ?>;"><?= htmlspecialchars($remainingText) ?></div>
                            </div>
                        </div>

                        <?php if ($progress !== null): ?>
                        <div class="progress-wrap">
                            <div class="progress-meta">
                                <span>Warranty period used</span>
                                <span><?= (int) $progress ?>%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= (int) $progress ?>%; background:<?= $accent ?>;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="foot">
            Need help? WhatsApp <a href="https://wa.me/96560727170" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>+965 60727170</a>
            <div style="margin-top:8px;"><a href="/apps">← Back to menu</a></div>
        </div>
    </div>
</div>

<div class="scan-overlay" id="scan-overlay" role="dialog" aria-modal="true" aria-labelledby="scan-panel-title">
    <div class="scan-panel">
        <div class="scan-panel-head">
            <div>
                <h2 class="scan-panel-title" id="scan-panel-title">Scan Barcode</h2>
                <p class="scan-panel-sub">Point your camera at the QR or 2D barcode</p>
            </div>
            <button type="button" class="btn-scan-close" id="btn-close-scan" aria-label="Close scanner">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="scan-viewport">
            <div id="qr-reader"></div>
        </div>
        <div class="scan-status" id="scan-status">Allow camera access when prompted, then hold the code steady in frame.</div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var overlay = document.getElementById('scan-overlay');
    var statusEl = document.getElementById('scan-status');
    var imeiInput = document.getElementById('imei-input');
    var warrantyForm = document.getElementById('warranty-form');
    var scanner = null;
    var scanning = false;
    var handled = false;

    var supportedFormats = [
        Html5QrcodeSupportedFormats.QR_CODE,
        Html5QrcodeSupportedFormats.DATA_MATRIX,
        Html5QrcodeSupportedFormats.AZTEC,
        Html5QrcodeSupportedFormats.PDF_417,
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39,
        Html5QrcodeSupportedFormats.CODE_93,
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
        Html5QrcodeSupportedFormats.ITF,
        Html5QrcodeSupportedFormats.UPC_A,
        Html5QrcodeSupportedFormats.UPC_E
    ];

    function setStatus(msg, kind) {
        statusEl.textContent = msg;
        statusEl.className = 'scan-status' + (kind ? ' is-' + kind : '');
    }

    function normalizeScannedValue(raw) {
        raw = String(raw || '').trim();
        if (!raw) return '';

        var upper = raw.toUpperCase();

        try {
            var urlStr = upper.indexOf('://') !== -1 ? upper : (upper.indexOf('?') === 0 ? 'https://x/' + upper : null);
            if (urlStr) {
                var u = new URL(urlStr);
                var keys = ['imei', 'serial', 'sn', 's', 'barcode'];
                for (var i = 0; i < keys.length; i++) {
                    var v = u.searchParams.get(keys[i]);
                    if (v) {
                        return v.replace(/[^A-Za-z0-9\/\-]/g, '').toUpperCase().slice(0, 20);
                    }
                }
                var parts = u.pathname.split('/').filter(Boolean);
                if (parts.length) {
                    var last = parts[parts.length - 1].replace(/[^A-Za-z0-9\/\-]/g, '').toUpperCase();
                    if (last.length >= 6 && last.length <= 20) return last;
                }
            }
        } catch (e) {}

        var labeled = upper.match(/(?:IMEI|SERIAL|S\/N|SN|BARCODE)[:\s#\-]*([A-Z0-9\/\-]{6,20})/);
        if (labeled) return labeled[1];

        var digitsOnly = upper.replace(/[^0-9]/g, '');
        if (digitsOnly.length >= 14 && digitsOnly.length <= 20) return digitsOnly.slice(0, 20);

        var cleaned = upper.replace(/[^A-Z0-9\/\-]/g, '');
        if (cleaned.length >= 6 && cleaned.length <= 20) return cleaned;

        var tokens = upper.match(/[A-Z0-9]{6,20}/g);
        if (tokens && tokens.length) {
            tokens.sort(function (a, b) { return b.length - a.length; });
            return tokens[0];
        }

        return cleaned.slice(0, 20);
    }

    function isValidImei(value) {
        return /^[A-Z0-9\/\-]{6,20}$/.test(value);
    }

    function stopScanner() {
        if (!scanner || !scanning) return Promise.resolve();
        scanning = false;
        return scanner.stop().catch(function () {}).then(function () {
            return scanner.clear().catch(function () {});
        });
    }

    function closeScanner() {
        handled = false;
        return stopScanner().then(function () {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        });
    }

    function onScanSuccess(decodedText) {
        if (handled) return;
        var value = normalizeScannedValue(decodedText);
        if (!isValidImei(value)) {
            setStatus('Scanned code not recognized. Try again or type the number manually.', 'error');
            return;
        }
        handled = true;
        setStatus('Found: ' + value + ' — checking warranty…', 'ok');
        imeiInput.value = value;
        stopScanner().then(function () {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
            warrantyForm.submit();
        });
    }

    function startScanner() {
        handled = false;
        setStatus('Starting camera…');
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (!scanner) {
            scanner = new Html5Qrcode('qr-reader', { formatsToSupport: supportedFormats, verbose: false });
        }

        var config = {
            fps: 12,
            qrbox: function (viewfinderWidth, viewfinderHeight) {
                var size = Math.min(viewfinderWidth, viewfinderHeight) * 0.72;
                return { width: Math.floor(size), height: Math.floor(size) };
            },
            aspectRatio: 1.0
        };

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || !cameras.length) {
                setStatus('No camera found on this device.', 'error');
                return;
            }
            var backCam = cameras.find(function (c) {
                return /back|rear|environment/i.test(c.label);
            });
            var cameraId = (backCam || cameras[cameras.length - 1]).id;
            scanning = true;
            return scanner.start(cameraId, config, onScanSuccess, function () {});
        }).then(function () {
            if (scanning) {
                setStatus('Hold the QR or 2D barcode inside the frame.');
            }
        }).catch(function (err) {
            var msg = String(err && err.message ? err.message : err);
            if (/notallowed|permission/i.test(msg)) {
                setStatus('Camera permission denied. Allow camera access in your browser settings, then try again.', 'error');
            } else if (/notfound|nomedia|devices/i.test(msg)) {
                setStatus('No camera available on this device.', 'error');
            } else {
                setStatus('Could not open camera. You can type the IMEI manually.', 'error');
            }
        });
    }

    document.getElementById('btn-open-scan').addEventListener('click', startScanner);
    document.getElementById('btn-open-scan-link').addEventListener('click', startScanner);
    document.getElementById('btn-close-scan').addEventListener('click', closeScanner);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeScanner();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeScanner();
    });
})();
</script>
<script src="/assets/pwa/apps/nav.js" defer></script>
</body>
</html>
