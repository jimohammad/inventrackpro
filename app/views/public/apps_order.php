<?php
/** @var array<string,mixed>|null $order */
/** @var string $companyName */
/** @var list<array{id:int,name:string,brand:?string,category_name:?string,stock:int}> $stockItems */
$flash = BaseController::getFlash();
$isNew = isset($_GET['new']);
$status = $order['status'] ?? null;
$token = $order['public_token'] ?? (string) ($_GET['token'] ?? '');
$stockItems = $stockItems ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= $order ? 'Order status' : 'Request order' ?> — Iqbal</title>
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
            --accent: #f59e0b;
            --accent-deep: #d97706;
            --surface: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(920px 440px at 8% -12%, rgba(245,158,11,0.18), transparent 55%),
                radial-gradient(780px 380px at 108% 4%, rgba(15,23,42,0.08), transparent 52%),
                linear-gradient(180deg, #fffbeb 0%, #f8fafc 48%, #ffffff 100%);
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px 16px;
            padding-bottom: max(32px, env(safe-area-inset-bottom));
        }
        .shell { width: 100%; max-width: 420px; }
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
            box-shadow:
                0 18px 48px rgba(15,23,42,0.10),
                0 2px 8px rgba(15,23,42,0.04);
            overflow: hidden;
            animation: rise .45s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(14px) scale(.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .card { animation: none; }
            .btn, .btn-add, .notif-btn, .sel-qty button, .sel-remove, .back { transition: none; }
        }

        .card-head {
            padding: 22px 20px 18px;
            background:
                radial-gradient(320px 140px at 90% -30%, rgba(245,158,11,0.45), transparent 70%),
                linear-gradient(155deg, #1e293b 0%, #0f172a 55%, #334155 100%);
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 18px rgba(245,158,11,0.35);
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
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }
        .card-head .sub {
            margin: 0;
            color: rgba(255,255,255,0.72);
            font-size: 0.88rem;
            line-height: 1.45;
            font-weight: 500;
        }
        .card-body { padding: 20px 18px 22px; }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
            letter-spacing: 0.01em;
        }
        .input-wrap { position: relative; }
        .input-wrap .bi-prefix {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }
        input, textarea, select {
            width: 100%;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 13px 14px;
            font: 500 0.95rem Inter, system-ui, sans-serif;
            color: var(--ink);
            background: #fff;
            transition: border-color .15s, box-shadow .15s, background .15s;
            appearance: none;
            -webkit-appearance: none;
        }
        .input-wrap input { padding-left: 40px; }
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M4.5 6l3.5 4 3.5-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245,158,11,0.22);
            background: #fffbeb;
        }
        textarea {
            min-height: 72px;
            resize: vertical;
            line-height: 1.45;
        }
        .field { margin-bottom: 16px; }
        .field:last-of-type { margin-bottom: 18px; }
        .hp { position: absolute; left: -9999px; opacity: 0; height: 0; width: 0; overflow: hidden; }

        .section-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .section-label label { margin: 0; }
        .section-count {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--accent-deep);
            background: #fff7ed;
            border-radius: 999px;
            padding: 3px 9px;
            display: none;
        }
        .section-count.is-on { display: inline-flex; }

        .picker-panel {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 16px;
            padding: 12px;
        }
        .picker-row {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }
        .picker-row select { flex: 1; min-width: 0; background-color: #fff; }
        .picker-qty {
            width: 64px;
            flex: none;
            text-align: center;
            padding-left: 6px;
            padding-right: 6px;
            font-weight: 700;
            background: #fff;
        }
        .btn-add {
            width: 48px;
            flex: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 14px;
            padding: 0;
            color: #fff;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            cursor: pointer;
            font-size: 1.1rem;
            transition: transform .12s, box-shadow .18s, filter .15s;
            box-shadow: 0 4px 12px rgba(15,23,42,0.2);
        }
        .btn-add:hover { filter: brightness(1.08); }
        .btn-add:active { transform: scale(0.96); }

        .selected-list {
            list-style: none;
            margin: 10px 0 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }
        .selected-list:empty { display: none; }
        .sel-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border: 1.5px solid #fed7aa;
            border-radius: 14px;
            background: linear-gradient(180deg, #fffbeb, #fff);
            animation: pop .22s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes pop {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .sel-name {
            flex: 1;
            min-width: 0;
            font-size: 0.88rem;
            font-weight: 650;
            line-height: 1.3;
        }
        .sel-qty {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border: 1.5px solid var(--line);
            border-radius: 11px;
            background: #fff;
            overflow: hidden;
        }
        .sel-qty button {
            width: 32px;
            height: 32px;
            border: 0;
            background: transparent;
            color: var(--ink);
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .12s;
        }
        .sel-qty button:hover { background: #f1f5f9; }
        .sel-qty span {
            min-width: 24px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 800;
        }
        .sel-remove {
            border: 0;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1rem;
            padding: 6px;
            border-radius: 8px;
            transition: color .12s, background .12s;
        }
        .sel-remove:hover { color: #b91c1c; background: #fef2f2; }
        .empty-stock {
            font-size: 0.88rem;
            color: var(--muted);
            padding: 14px;
            text-align: center;
            background: var(--surface);
            border-radius: 14px;
            border: 1.5px dashed var(--line);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 15px 16px;
            font: 700 0.98rem Inter, system-ui, sans-serif;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-deep));
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(217,119,6,0.28);
            transition: transform .12s, box-shadow .18s, filter .15s;
        }
        .btn:hover { filter: brightness(1.04); box-shadow: 0 12px 28px rgba(217,119,6,0.34); }
        .btn:active { transform: scale(0.985); }

        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .st-pending { background: #fff7ed; color: #c2410c; }
        .st-approved { background: #ecfdf5; color: #047857; }
        .st-rejected { background: #fef2f2; color: #b91c1c; }
        .st-cancelled { background: #f1f5f9; color: #475569; }

        .meta {
            margin-top: 16px;
            padding: 14px;
            border-radius: 14px;
            background: var(--surface);
            border: 1px solid var(--line);
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.5;
        }
        .meta strong { color: var(--ink); display: block; font-size: 1rem; margin-bottom: 2px; }
        .block {
            margin-top: 12px;
            padding: 14px;
            border-radius: 14px;
            background: var(--surface);
            border: 1px solid var(--line);
            white-space: pre-wrap;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .block strong { display: block; margin-bottom: 4px; font-size: 0.78rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .hint { margin-top: 12px; font-size: 0.8rem; color: var(--muted); line-height: 1.4; text-align: center; }
        .notif-btn {
            margin-top: 14px;
            width: 100%;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
            font: 700 0.88rem Inter, system-ui, sans-serif;
            color: var(--ink);
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .notif-btn:hover { border-color: #cbd5e1; background: var(--surface); }
        .trust-row {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }
        .trust-item {
            flex: 1;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--muted);
            line-height: 1.35;
        }
        .trust-item i {
            display: block;
            font-size: 1.05rem;
            color: var(--accent-deep);
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="shell">
        <a class="back" href="/apps"><i class="bi bi-arrow-left"></i> Apps menu</a>

        <div class="card">
            <?php if ($order): ?>
                <div class="card-head">
                    <div class="head-badge">
                        <span class="head-ic"><i class="bi bi-bag-check"></i></span>
                        <span class="head-kicker">Order status</span>
                    </div>
                    <h1><?= htmlspecialchars((string) $order['request_no']) ?></h1>
                    <p class="sub">We review every request before confirming availability.</p>
                </div>
                <div class="card-body">
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type'] === 'error' ? 'error' : 'ok') ?>">
                            <?= htmlspecialchars($flash['message'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $st = (string) ($order['status'] ?? 'pending');
                    $stClass = 'st-' . preg_replace('/[^a-z]/', '', $st);
                    $stLabel = ucfirst($st);
                    ?>
                    <span class="status-pill <?= htmlspecialchars($stClass) ?>" id="status-pill">
                        <i class="bi bi-<?= $st === 'approved' ? 'check-circle' : ($st === 'rejected' ? 'x-circle' : 'hourglass-split') ?>"></i>
                        <span id="status-label"><?= htmlspecialchars($stLabel) ?></span>
                    </span>

                    <?php if ($isNew && $st === 'pending'): ?>
                        <div class="alert alert-ok" style="margin-top:14px;">Request sent. Keep this page open or allow notifications to hear when we confirm.</div>
                    <?php endif; ?>

                    <div class="meta">
                        <strong><?= htmlspecialchars((string) $order['customer_name']) ?></strong>
                        <div><?= htmlspecialchars((string) $order['customer_phone']) ?></div>
                    </div>
                    <div class="block" id="items-block"><?= htmlspecialchars((string) $order['items_text']) ?></div>
                    <?php if (!empty($order['customer_notes'])): ?>
                        <div class="block"><strong>Your notes</strong><?= htmlspecialchars((string) $order['customer_notes']) ?></div>
                    <?php endif; ?>
                    <div class="block" id="staff-note-wrap" style="<?= empty($order['staff_note']) ? 'display:none;' : '' ?>">
                        <strong>Our reply</strong>
                        <span id="staff-note"><?= htmlspecialchars((string) ($order['staff_note'] ?? '')) ?></span>
                    </div>

                    <button type="button" class="notif-btn" id="btn-enable-notif">
                        <i class="bi bi-bell"></i> Enable notification for reply
                    </button>
                    <p class="hint" id="poll-hint">Checking for confirmation…</p>
                </div>

            <?php else: ?>
                <div class="card-head">
                    <div class="head-badge">
                        <span class="head-ic"><i class="bi bi-bag-plus"></i></span>
                        <span class="head-kicker">Iqbal Electronics Co. LLC</span>
                    </div>
                    <h1>Request order</h1>
                    <p class="sub">Pick items from stock. We’ll confirm availability and notify you.</p>
                </div>
                <div class="card-body">
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type'] === 'error' ? 'error' : 'ok') ?>">
                            <?= htmlspecialchars($flash['message'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/apps/order?action=store" id="order-form">
                        <?= Auth::csrfField() ?>
                        <div class="hp" aria-hidden="true">
                            <label>Website</label>
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="field">
                            <label for="customer_name">Your name</label>
                            <div class="input-wrap">
                                <i class="bi bi-person bi-prefix" aria-hidden="true"></i>
                                <input type="text" id="customer_name" name="customer_name" required maxlength="150" autocomplete="name" autofocus placeholder="Full name">
                            </div>
                        </div>
                        <div class="field">
                            <label for="customer_phone">Phone (WhatsApp)</label>
                            <div class="input-wrap">
                                <i class="bi bi-whatsapp bi-prefix" aria-hidden="true"></i>
                                <input type="tel" id="customer_phone" name="customer_phone" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="9xxxxxxx">
                            </div>
                        </div>

                        <div class="field">
                            <div class="section-label">
                                <label for="item_pick">Items to order</label>
                                <span class="section-count" id="item_count" aria-live="polite"></span>
                            </div>
                            <?php if ($stockItems === []): ?>
                                <p class="empty-stock">No stock items available right now. Please try again later.</p>
                            <?php else: ?>
                                <div class="picker-panel">
                                    <div class="picker-row">
                                        <select id="item_pick" aria-label="Select stock item">
                                            <option value="">Select item…</option>
                                            <?php
                                            $lastCat = null;
                                            foreach ($stockItems as $it):
                                                $cat = $it['category_name'] ?: 'Other';
                                                if ($cat !== $lastCat):
                                                    if ($lastCat !== null) {
                                                        echo '</optgroup>';
                                                    }
                                                    echo '<optgroup label="' . htmlspecialchars($cat) . '">';
                                                    $lastCat = $cat;
                                                endif;
                                                $label = $it['name'];
                                                if (!empty($it['brand'])) {
                                                    $label = $it['brand'] . ' — ' . $label;
                                                }
                                            ?>
                                                <option value="<?= (int) $it['id'] ?>"
                                                        data-name="<?= htmlspecialchars($it['name'], ENT_QUOTES) ?>"
                                                        data-stock="<?= (int) $it['stock'] ?>">
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($lastCat !== null) echo '</optgroup>'; ?>
                                        </select>
                                        <input type="number" id="item_pick_qty" class="picker-qty" min="1" max="999" value="1" inputmode="numeric" aria-label="Quantity">
                                        <button type="button" class="btn-add" id="btn_add_item" title="Add item" aria-label="Add item">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    <ul class="selected-list" id="selected_items" aria-live="polite"></ul>
                                    <div id="selected_inputs"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="customer_notes">Notes <span style="font-weight:500;color:var(--muted)">(optional)</span></label>
                            <textarea id="customer_notes" name="customer_notes" maxlength="2000" placeholder="Preferred pickup time, color, etc."></textarea>
                        </div>

                        <button type="submit" class="btn"><i class="bi bi-send-fill"></i> Send for confirmation</button>

                        <div class="trust-row">
                            <div class="trust-item"><i class="bi bi-shield-check"></i> No payment yet</div>
                            <div class="trust-item"><i class="bi bi-bell"></i> We notify you</div>
                            <div class="trust-item"><i class="bi bi-shop"></i> Confirm in store</div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/assets/pwa/apps/nav.js" defer></script>
<script>
(function () {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/assets/pwa/apps/sw.js', { scope: '/' }).catch(function () {});
    }

    var form = document.getElementById('order-form');
    if (form) {
        var pick = document.getElementById('item_pick');
        var pickQty = document.getElementById('item_pick_qty');
        var addBtn = document.getElementById('btn_add_item');
        var listEl = document.getElementById('selected_items');
        var inputsEl = document.getElementById('selected_inputs');
        var countEl = document.getElementById('item_count');
        var selected = {};

        function esc(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function syncInputs() {
            if (!inputsEl) return;
            var html = '';
            Object.keys(selected).forEach(function (id) {
                var row = selected[id];
                html += '<input type="hidden" name="item_id[]" value="' + esc(id) + '">';
                html += '<input type="hidden" name="item_qty[]" value="' + esc(row.qty) + '">';
            });
            inputsEl.innerHTML = html;
        }

        function updateCount() {
            if (!countEl) return;
            var n = Object.keys(selected).length;
            if (n === 0) {
                countEl.textContent = '';
                countEl.classList.remove('is-on');
                return;
            }
            countEl.textContent = n === 1 ? '1 item' : n + ' items';
            countEl.classList.add('is-on');
        }

        function render() {
            if (!listEl) return;
            var ids = Object.keys(selected);
            if (ids.length === 0) {
                listEl.innerHTML = '';
                syncInputs();
                updateCount();
                return;
            }
            listEl.innerHTML = ids.map(function (id) {
                var row = selected[id];
                return '<li class="sel-item" data-id="' + esc(id) + '">' +
                    '<div class="sel-name">' + esc(row.name) + '</div>' +
                    '<div class="sel-qty">' +
                    '<button type="button" data-act="dec" aria-label="Decrease">−</button>' +
                    '<span>' + esc(row.qty) + '</span>' +
                    '<button type="button" data-act="inc" aria-label="Increase">+</button>' +
                    '</div>' +
                    '<button type="button" class="sel-remove" data-act="rm" aria-label="Remove"><i class="bi bi-x-lg"></i></button>' +
                    '</li>';
            }).join('');
            syncInputs();
            updateCount();
        }

        function addSelected() {
            if (!pick) return;
            var opt = pick.options[pick.selectedIndex];
            if (!opt || !opt.value) return;
            var id = String(opt.value);
            var name = opt.getAttribute('data-name') || opt.textContent.trim();
            var stock = parseInt(opt.getAttribute('data-stock') || '0', 10) || 0;
            var qty = parseInt((pickQty && pickQty.value) || '1', 10) || 1;
            if (qty < 1) qty = 1;
            if (stock > 0 && qty > stock) qty = stock;
            if (selected[id]) {
                selected[id].qty = Math.min(stock || 999, selected[id].qty + qty);
            } else {
                selected[id] = { name: name, stock: stock, qty: qty };
            }
            pick.value = '';
            if (pickQty) pickQty.value = '1';
            render();
        }

        if (addBtn) addBtn.addEventListener('click', addSelected);
        if (pick) {
            pick.addEventListener('change', function () {
                var opt = pick.options[pick.selectedIndex];
                if (!opt || !opt.value || !pickQty) return;
                var stock = parseInt(opt.getAttribute('data-stock') || '1', 10) || 1;
                pickQty.max = String(stock);
                if (parseInt(pickQty.value, 10) > stock) pickQty.value = String(stock);
            });
        }
        if (listEl) {
            listEl.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-act]');
                if (!btn) return;
                var li = btn.closest('.sel-item');
                if (!li) return;
                var id = li.getAttribute('data-id');
                if (!id || !selected[id]) return;
                var act = btn.getAttribute('data-act');
                if (act === 'rm') {
                    delete selected[id];
                } else if (act === 'inc') {
                    var max = selected[id].stock || 999;
                    if (selected[id].qty < max) selected[id].qty += 1;
                } else if (act === 'dec') {
                    if (selected[id].qty > 1) selected[id].qty -= 1;
                    else delete selected[id];
                }
                render();
            });
        }

        form.addEventListener('submit', function (e) {
            if (Object.keys(selected).length === 0) {
                e.preventDefault();
                alert('Please select at least one stock item.');
            }
        });
    }

    var token = <?= json_encode($token !== '' ? $token : null) ?>;
    var currentStatus = <?= json_encode($status) ?>;
    if (!token) return;

    var STORAGE_KEY = 'iqbal_order_watches';
    function loadWatches() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) { return []; }
    }
    function saveWatches(list) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, 20)));
    }
    function upsertWatch(tok, status, requestNo) {
        var list = loadWatches().filter(function (w) { return w.token !== tok; });
        list.unshift({ token: tok, status: status || 'pending', request_no: requestNo || '', notified: false });
        saveWatches(list);
    }
    upsertWatch(token, currentStatus, <?= json_encode($order['request_no'] ?? '') ?>);

    function showLocalNotification(title, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        var opts = { body: body, icon: '/assets/pwa/apps/icons/icon-192.png', tag: 'order-' + token };
        if (navigator.serviceWorker && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready.then(function (reg) {
                if (reg.showNotification) {
                    reg.showNotification(title, opts);
                    return;
                }
                new Notification(title, opts);
            }).catch(function () { new Notification(title, opts); });
        } else {
            new Notification(title, opts);
        }
    }

    var btn = document.getElementById('btn-enable-notif');
    if (btn) {
        btn.addEventListener('click', function () {
            if (!('Notification' in window)) {
                alert('Notifications are not supported on this device.');
                return;
            }
            Notification.requestPermission().then(function (p) {
                btn.textContent = p === 'granted' ? 'Notifications enabled' : 'Notifications blocked';
            });
        });
        if ('Notification' in window && Notification.permission === 'granted') {
            btn.textContent = 'Notifications enabled';
        }
    }

    var pill = document.getElementById('status-pill');
    var label = document.getElementById('status-label');
    var noteWrap = document.getElementById('staff-note-wrap');
    var noteEl = document.getElementById('staff-note');
    var hint = document.getElementById('poll-hint');

    function applyStatus(data) {
        if (!data || !data.ok) return;
        var st = data.status;
        if (pill) {
            pill.className = 'status-pill st-' + st;
        }
        if (label) label.textContent = st.charAt(0).toUpperCase() + st.slice(1);
        if (noteEl && data.staff_note) {
            noteEl.textContent = data.staff_note;
            if (noteWrap) noteWrap.style.display = '';
        }
        if (st !== currentStatus && (st === 'approved' || st === 'rejected')) {
            var title = st === 'approved' ? 'Order approved' : 'Order not available';
            var body = (data.request_no || '') + (data.staff_note ? ' — ' + data.staff_note : '');
            showLocalNotification(title, body);
            if (hint) hint.textContent = st === 'approved' ? 'Confirmed — please visit us or wait for our call.' : 'We could not confirm this order.';
        }
        currentStatus = st;
        upsertWatch(token, st, data.request_no);
        if (st === 'pending' && hint) hint.textContent = 'Waiting for our confirmation…';
    }

    function poll() {
        fetch('/apps/order/status?token=' + encodeURIComponent(token), { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(applyStatus)
            .catch(function () {});
    }

    poll();
    setInterval(function () {
        if (currentStatus === 'pending') poll();
    }, 8000);

    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(function () {
            if (btn && currentStatus === 'pending') btn.focus();
        }, 600);
    }
})();
</script>
</body>
</html>
