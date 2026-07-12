<?php
/**
 * Customer apps hub — single PWA entry for Warranty / Service / Pricelist
 * URL: https://iqbal.app/apps
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="application-name" content="Iqbal">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Iqbal">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Iqbal Apps</title>
    <link rel="manifest" href="/assets/pwa/apps/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/assets/pwa/apps/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/pwa/apps/icons/icon-192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --card: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 10% -10%, rgba(37,99,235,0.12), transparent 55%),
                radial-gradient(800px 400px at 110% 0%, rgba(16,185,129,0.10), transparent 50%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
            padding-bottom: max(28px, env(safe-area-inset-bottom));
        }
        .shell {
            width: 100%;
            max-width: 420px;
        }
        .brand {
            text-align: center;
            margin-bottom: 22px;
        }
        .brand img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 18px;
            background: #000;
            box-shadow: 0 12px 30px rgba(2,6,23,0.25);
        }
        .brand h1 {
            margin: 14px 0 4px;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .brand p {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 500;
        }
        .menu {
            display: grid;
            gap: 12px;
        }
        .tile {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 16px;
            border-radius: 18px;
            background: var(--card);
            border: 1px solid rgba(255,255,255,0.8);
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
            text-decoration: none;
            color: inherit;
            transition: transform .12s, box-shadow .18s;
        }
        .tile:active { transform: scale(0.985); }
        .tile:hover { box-shadow: 0 16px 36px rgba(15,23,42,0.12); }
        .tile-ic {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.35rem;
            color: #fff;
            flex: none;
        }
        .tile-ic.warranty { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .tile-ic.service { background: linear-gradient(135deg, #7c3aed, #5b21b6); }
        .tile-ic.pricelist { background: linear-gradient(135deg, #16a34a, #15803d); }
        .tile-body { flex: 1; min-width: 0; }
        .tile-title { font-weight: 800; font-size: 1.05rem; letter-spacing: -0.02em; }
        .tile-sub { margin-top: 2px; font-size: 0.82rem; color: var(--muted); font-weight: 500; }
        .tile-chevron { color: #94a3b8; font-size: 1.1rem; }
        .actions {
            margin-top: 16px;
            display: grid;
            gap: 10px;
        }
        .btn-install {
            display: none;
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 13px 16px;
            font: 700 0.95rem Inter, system-ui, sans-serif;
            color: #fff;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            cursor: pointer;
        }
        .btn-install.is-visible { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .ios-hint {
            display: none;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--line);
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.45;
        }
        .ios-hint.is-visible { display: block; }
        .ios-hint strong { color: var(--ink); }
        .foot {
            margin-top: 18px;
            text-align: center;
            font-size: 0.78rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="shell">
        <div class="brand">
            <img src="/assets/pwa/apps/icons/icon-192.png" alt="Iqbal">
            <h1>Iqbal Electronics</h1>
            <p>Choose what you need</p>
        </div>

        <nav class="menu" aria-label="Customer apps">
            <a class="tile" href="/imei">
                <span class="tile-ic warranty"><i class="bi bi-shield-check"></i></span>
                <span class="tile-body">
                    <div class="tile-title">Warranty</div>
                    <div class="tile-sub">Check warranty by IMEI / serial</div>
                </span>
                <i class="bi bi-chevron-right tile-chevron"></i>
            </a>
            <a class="tile" href="/service">
                <span class="tile-ic service"><i class="bi bi-tools"></i></span>
                <span class="tile-body">
                    <div class="tile-title">Service</div>
                    <div class="tile-sub">Track repair status</div>
                </span>
                <i class="bi bi-chevron-right tile-chevron"></i>
            </a>
            <a class="tile" href="/pricelist">
                <span class="tile-ic pricelist"><i class="bi bi-tags"></i></span>
                <span class="tile-body">
                    <div class="tile-title">Pricelist</div>
                    <div class="tile-sub">Browse products &amp; prices</div>
                </span>
                <i class="bi bi-chevron-right tile-chevron"></i>
            </a>
        </nav>

        <div class="actions">
            <button type="button" class="btn-install" id="btn-install">
                <i class="bi bi-download"></i> Install app
            </button>
            <div class="ios-hint" id="ios-install-hint">
                <strong>Install on iPhone:</strong> tap Share <i class="bi bi-box-arrow-up"></i> then <strong>Add to Home Screen</strong>.
            </div>
        </div>

        <div class="foot">Iqbal Electronics Co. WLL</div>
    </div>
</div>

<script>
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/assets/pwa/apps/sw.js', { scope: '/' }).catch(function () {});
            // Drop old per-page workers if still registered
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                regs.forEach(function (reg) {
                    var script = reg.active && reg.active.scriptURL ? reg.active.scriptURL : '';
                    if (/\/assets\/pwa\/(imei|pricelist)\/sw\.js/.test(script)) {
                        reg.unregister();
                    }
                });
            }).catch(function () {});
        });
    }

    var installBtn = document.getElementById('btn-install');
    var iosHint = document.getElementById('ios-install-hint');
    var deferredPrompt = null;
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (isStandalone) {
        return;
    }

    if (/iphone|ipad|ipod/i.test(navigator.userAgent) && iosHint) {
        iosHint.classList.add('is-visible');
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) installBtn.classList.add('is-visible');
    });

    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                installBtn.classList.remove('is-visible');
            });
        });
    }

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        if (installBtn) installBtn.classList.remove('is-visible');
        if (iosHint) iosHint.classList.remove('is-visible');
    });
})();
</script>
</body>
</html>
