<style>
.lc-page { max-width:720px;margin:0 auto; }
.lc-head { margin-bottom:20px; }
.lc-head h1 { font-size:1.15rem;font-weight:700;display:flex;align-items:center;gap:8px; }

/* Search bar */
.lc-search {
    display:flex;gap:8px;margin-bottom:24px;
}
.lc-search-input {
    flex:1;padding:12px 16px;border:2px solid var(--primary);border-radius:10px;
    font-size:1.05rem;font-family:monospace;letter-spacing:1px;
    background:var(--bg-main);color:var(--text-main);outline:none;
}
.lc-search-input:focus { box-shadow:0 0 0 4px rgba(99,102,241,.15); }
.lc-search-input::placeholder { font-family:inherit;font-size:.85rem;letter-spacing:normal;color:var(--text-muted); }
.lc-search-btn {
    padding:12px 20px;background:var(--primary);color:#fff;border:none;border-radius:10px;
    font-size:.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;
}
.lc-search-btn:hover { opacity:.9; }

/* Device card */
.lc-device {
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;
    padding:20px;margin-bottom:20px;display:flex;gap:20px;align-items:flex-start;
}
.lc-device-icon {
    width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;
    font-size:1.5rem;flex-shrink:0;
}
.lc-device-body { flex:1; }
.lc-device-name { font-size:1.05rem;font-weight:700;color:var(--text-main); }
.lc-device-imei { font-family:monospace;font-size:.88rem;color:var(--primary);font-weight:600;letter-spacing:1px;margin-top:2px; }
.lc-device-meta { display:flex;flex-wrap:wrap;gap:8px;margin-top:8px; }
.lc-device-meta span {
    font-size:.72rem;font-weight:600;padding:3px 10px;border-radius:20px;
}
.lc-status-in_stock { background:rgba(34,197,94,.1);color:#16a34a; }
.lc-status-sold { background:rgba(239,68,68,.1);color:#dc2626; }
.lc-status-returned { background:rgba(245,158,11,.1);color:#d97706; }
.lc-status-transferred { background:rgba(59,130,246,.1);color:#3b82f6; }
.lc-status-defective { background:rgba(127,29,29,.1);color:#7f1d1d; }

/* Timeline — layered rail + event-colored nodes */
.lc-timeline {
    position:relative;
    padding-left:44px;
    margin-top:4px;
}
/* Soft outer track */
.lc-timeline::before {
    content:'';
    position:absolute;
    left:11px;
    top:2px;
    bottom:2px;
    width:12px;
    border-radius:999px;
    background:linear-gradient(
        180deg,
        rgba(37,99,235,.22) 0%,
        rgba(99,102,241,.12) 42%,
        rgba(148,163,184,.16) 100%
    );
    z-index:0;
}
/* Inner spine with depth */
.lc-timeline::after {
    content:'';
    position:absolute;
    left:15px;
    top:6px;
    bottom:6px;
    width:4px;
    border-radius:999px;
    z-index:0;
    background:linear-gradient(
        180deg,
        var(--primary) 0%,
        #6366f1 32%,
        #a5b4fc 58%,
        #cbd5e1 82%,
        rgba(203,213,225,.35) 100%
    );
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.45),
        inset 0 -1px 0 rgba(15,23,42,.07),
        0 0 0 1px rgba(37,99,235,.2);
}
.lc-event {
    position:relative;
    z-index:1;
    margin-bottom:18px;
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;
    padding:14px 16px;transition:border-color .15s, box-shadow .15s, transform .15s;
}
.lc-event:hover { border-color:var(--primary);transform:translateX(3px);box-shadow:0 4px 14px rgba(15,23,42,.07); }
.lc-event:last-child { margin-bottom:0; }
.lc-dot {
    position:absolute;
    left:-34px;
    top:17px;
    width:14px;
    height:14px;
    border-radius:50%;
    z-index:2;
    background:var(--lc-event-color, var(--primary));
    border:3px solid var(--bg-card);
    box-shadow:
        0 0 0 3px var(--bg-card),
        0 0 0 4px var(--border-color),
        0 4px 14px rgba(15,23,42,.16);
}
.lc-event-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:4px; }
.lc-event-title { font-weight:700;font-size:.88rem;display:flex;align-items:center;gap:6px; }
.lc-event-date { font-size:.72rem;color:var(--text-muted);font-weight:600; }
.lc-event-desc { font-size:.8rem;color:var(--text-muted);line-height:1.5; }
.lc-event-desc a { color:var(--primary);text-decoration:none;font-weight:600; }
.lc-event-desc a:hover { text-decoration:underline; }
.lc-event-link { display:inline-flex;align-items:center;gap:4px;font-size:.75rem;color:var(--primary);font-weight:600;text-decoration:none;margin-top:6px; }
.lc-event-link:hover { text-decoration:underline; }

/* Not found */
.lc-notfound {
    text-align:center;padding:40px 20px;background:var(--bg-card);border:1px solid var(--border-color);
    border-radius:14px;color:var(--text-muted);
}
.lc-notfound i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3; }

/* Empty state */
.lc-empty { text-align:center;padding:60px 20px;color:var(--text-muted); }
.lc-empty i { font-size:3rem;display:block;margin-bottom:12px;opacity:.2; }
.lc-empty p { font-size:.88rem;margin-top:8px; }
</style>

<div class="lc-page">
    <div class="lc-head">
        <h1><i class="bi bi-clock-history" style="color:var(--primary);"></i> IMEI Lifecycle</h1>
    </div>

    <!-- Search -->
    <form method="GET" class="lc-search">
        <input type="hidden" name="page" value="imei">
        <input type="hidden" name="action" value="lifecycle">
        <input type="text" name="imei" class="lc-search-input" value="<?= htmlspecialchars($imei) ?>"
               placeholder="Scan or type IMEI number..." autofocus
               onkeydown="if(event.key==='Enter'){this.form.submit();}">
        <button type="submit" class="lc-search-btn"><i class="bi bi-search"></i> Track</button>
    </form>

    <?php if ($imei && !$record): ?>
    <!-- Not found -->
    <div class="lc-notfound">
        <i class="bi bi-upc-scan"></i>
        <strong>IMEI not found</strong>
        <p style="margin-top:6px;font-size:.85rem;"><?= htmlspecialchars($imei) ?> is not registered in the system.</p>
    </div>

    <?php elseif ($record): ?>
    <!-- Device card -->
    <div class="lc-device">
        <div class="lc-device-icon" style="background:rgba(99,102,241,.1);color:var(--primary);">
            <i class="bi bi-phone"></i>
        </div>
        <div class="lc-device-body">
            <div class="lc-device-name"><?= htmlspecialchars($record['item_name']) ?></div>
            <div class="lc-device-imei"><?= htmlspecialchars($record['imei']) ?></div>
            <?php if (!empty($record['imei2'])): ?>
            <div class="lc-device-imei" style="font-size:.78rem;color:var(--text-muted);">IMEI2: <?= htmlspecialchars($record['imei2']) ?></div>
            <?php endif; ?>
            <div class="lc-device-meta">
                <span class="lc-status-<?= $record['status'] ?>"><?= strtoupper(str_replace('_', ' ', $record['status'])) ?></span>
                <span style="background:rgba(99,102,241,.08);color:var(--primary);"><?= htmlspecialchars($record['warehouse_name'] ?? '—') ?></span>
                <?php if (!empty($record['sku'])): ?>
                <span style="background:var(--bg-main);color:var(--text-muted);border:1px solid var(--border-color);"><?= htmlspecialchars($record['sku']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <?php if (!empty($timeline)): ?>
    <div class="lc-timeline">
        <?php foreach ($timeline as $event): ?>
        <div class="lc-event" style="--lc-event-color: <?= htmlspecialchars((string)($event['color'] ?? '#6366f1'), ENT_QUOTES, 'UTF-8') ?>;">
            <div class="lc-dot" aria-hidden="true"></div>
            <div class="lc-event-head">
                <span class="lc-event-title" style="color:<?= $event['color'] ?>;">
                    <i class="bi <?= $event['icon'] ?>"></i> <?= $event['title'] ?>
                </span>
                <span class="lc-event-date"><?= date('d M Y', strtotime($event['date'])) ?></span>
            </div>
            <div class="lc-event-desc"><?= $event['desc'] ?></div>
            <?php if ($event['link']): ?>
            <a href="<?= $event['link'] ?>" class="lc-event-link"><i class="bi bi-box-arrow-up-right"></i> View</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty state -->
    <div class="lc-empty">
        <i class="bi bi-clock-history"></i>
        <strong>Track any device</strong>
        <p>Scan or type an IMEI number to see its full lifecycle — purchase, registration, sale, returns, warranty.</p>
    </div>
    <?php endif; ?>
</div>
