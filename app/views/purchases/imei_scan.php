<style>
.scan-wrap{max-width:780px;margin:0 auto;}
.scan-header{background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px;padding:18px 24px;margin-bottom:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.scan-header h1{font-size:1.05rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.scan-header .inv-ref{font-size:0.8rem;color:rgba(255,255,255,0.7);margin-top:2px;}
.item-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.item-tab{padding:8px 16px;border-radius:8px;border:2px solid #e0e7ff;background:#fff;cursor:pointer;font-size:0.82rem;font-weight:600;color:#64748b;transition:all 0.15s;text-align:center;}
.item-tab:hover{border-color:#6366f1;color:#6366f1;}
.item-tab.active{border-color:#6366f1;background:linear-gradient(135deg,#eff6ff,#e0e7ff);color:#4338ca;}
.item-tab .tab-count{display:block;font-size:0.75rem;margin-top:2px;}
.item-tab .tab-count.done{color:#10b981;}
.item-tab .tab-count.warn{color:#f59e0b;}
.scan-station{background:#fff;border-radius:12px;border:2px solid #e0e7ff;overflow:hidden;}
.scan-station-header{padding:14px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.scan-station-header .item-label{font-size:0.95rem;font-weight:700;color:#1e293b;}
.progress-info{display:flex;align-items:center;gap:12px;}
.progress-count{font-size:1.4rem;font-weight:800;color:#6366f1;}
.progress-count .need{font-size:0.85rem;font-weight:500;color:#94a3b8;}
.progress-bar-wrap{width:140px;height:8px;background:#e0e7ff;border-radius:99px;overflow:hidden;}
.progress-bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#6366f1,#3b82f6);transition:width 0.3s;}
.progress-bar-fill.done{background:linear-gradient(90deg,#10b981,#059669);}
.scan-input-wrap{padding:20px;}
.scan-input-label{font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.scan-input-label .dot{width:8px;height:8px;border-radius:50%;background:#10b981;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}
#scanInput{width:100%;padding:16px 20px;font-size:1.4rem;font-family:'JetBrains Mono',monospace;font-weight:700;letter-spacing:3px;border:2.5px solid #6366f1;border-radius:10px;outline:none;color:#1e293b;background:#fafbff;box-shadow:0 0 0 4px rgba(99,102,241,0.1);transition:all 0.15s;}
#scanInput:focus{border-color:#4338ca;box-shadow:0 0 0 5px rgba(99,102,241,0.15);}
#scanInput.flash-ok{background:#d1fae5;border-color:#10b981;box-shadow:0 0 0 5px rgba(16,185,129,0.15);}
#scanInput.flash-err{background:#fee2e2;border-color:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,0.15);}
#scanFeedback{min-height:32px;padding:6px 12px;border-radius:8px;font-size:0.88rem;font-weight:600;margin-top:10px;display:flex;align-items:center;gap:8px;transition:all 0.2s;}
#scanFeedback.ok{background:#d1fae5;color:#065f46;}
#scanFeedback.err{background:#fee2e2;color:#991b1b;}
#scanFeedback.idle{background:transparent;color:transparent;}
.scan-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
.btn-undo{padding:8px 18px;border-radius:8px;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.83rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-undo:hover{background:#fee2e2;}
.btn-undo:disabled{opacity:0.4;cursor:not-allowed;}
.imei-list-wrap{border-top:1px solid #e0e7ff;padding:16px 20px;max-height:340px;overflow-y:auto;}
.imei-list-title{font-size:0.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;}
.imei-row{display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border-radius:7px;margin-bottom:4px;background:#f8fafc;}
.imei-row:first-child{background:#f0fdf4;border:1px solid #bbf7d0;}
.imei-row .imei-num{font-family:'JetBrains Mono',monospace;font-size:0.85rem;font-weight:600;color:#1e293b;}
.imei-row .imei-time{font-size:0.72rem;color:#94a3b8;}
.imei-row .del-imei{background:none;border:none;color:#fca5a5;cursor:pointer;font-size:1rem;padding:2px 6px;border-radius:5px;line-height:1;}
.imei-row .del-imei:hover{background:#fee2e2;color:#dc2626;}
.empty-list{text-align:center;padding:24px;color:#94a3b8;font-size:0.85rem;}
</style>

<div class="scan-wrap">

    <!-- Header -->
    <div class="scan-header">
        <div>
            <h1><i class="bi bi-upc-scan"></i> IMEI Scan Station</h1>
            <div class="inv-ref">
                <?= htmlspecialchars($purchase['invoice_no']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($purchase['party_name']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($purchase['warehouse_name'] ?? '') ?>
            </div>
        </div>
        <a href="?page=purchases&action=detail&id=<?= $purchase['id'] ?>"
           style="background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;padding:7px 16px;border-radius:8px;font-size:0.82rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchase
        </a>
    </div>

    <?php if (empty($items)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-upc-scan fs-2 d-block mb-2"></i>
        No IMEI-tracked items in this purchase.
    </div></div>
    <?php else: ?>

    <!-- Item Tabs -->
    <div class="item-tabs" id="itemTabs">
        <?php foreach ($items as $idx => $item): ?>
        <?php
            $pct    = $item['quantity'] > 0 ? min(100, round($item['scanned'] / $item['quantity'] * 100)) : 0;
            $isDone = $item['scanned'] >= $item['quantity'];
        ?>
        <div class="item-tab <?= $idx === 0 ? 'active' : '' ?>"
             onclick="switchItem(<?= $item['item_id'] ?>, <?= $item['quantity'] ?>, '<?= htmlspecialchars(addslashes($item['item_name']), ENT_QUOTES) ?>')"
             id="tab_<?= $item['item_id'] ?>">
            <?= htmlspecialchars($item['item_name']) ?>
            <span class="tab-count <?= $isDone ? 'done' : 'warn' ?>">
                <?= $item['scanned'] ?> / <?= $item['quantity'] ?>
                <?= $isDone ? '✓' : '' ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Scan Station -->
    <div class="scan-station">
        <div class="scan-station-header">
            <div class="item-label" id="stationItemName"><?= htmlspecialchars($items[0]['item_name']) ?></div>
            <div class="progress-info">
                <div>
                    <span class="progress-count" id="stationCount"><?= $items[0]['scanned'] ?></span>
                    <span class="progress-count need" id="stationNeed">/ <?= $items[0]['quantity'] ?></span>
                </div>
                <div>
                    <div class="progress-bar-wrap">
                        <?php $pct0 = $items[0]['quantity'] > 0 ? min(100, round($items[0]['scanned'] / $items[0]['quantity'] * 100)) : 0; ?>
                        <div class="progress-bar-fill <?= $pct0 >= 100 ? 'done' : '' ?>"
                             id="stationBar" style="width:<?= $pct0 ?>%"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#94a3b8;text-align:right;margin-top:2px;" id="stationPct"><?= $pct0 ?>%</div>
                </div>
            </div>
        </div>

        <div class="scan-input-wrap">
            <div class="scan-input-label">
                <span class="dot"></span> Ready — scan or type IMEI then press Enter
            </div>
            <input type="text" id="scanInput" placeholder="Scan barcode here..."
                   maxlength="18" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                   inputmode="numeric">
            <div id="scanFeedback" class="idle">—</div>
            <div class="scan-actions">
                <button type="button" class="btn-undo" id="btnUndo" onclick="undoLast()" disabled>
                    <i class="bi bi-arrow-counterclockwise"></i> Undo Last
                </button>
                <span style="font-size:0.78rem;color:#94a3b8;align-self:center;">
                    <i class="bi bi-keyboard me-1"></i> Enter to confirm · Undo removes last scan
                </span>
            </div>
        </div>

        <div class="imei-list-wrap" id="imeiListWrap">
            <div class="imei-list-title">Scanned IMEIs (most recent first)</div>
            <div id="imeiList"><div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div></div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
const PURCHASE_ID = <?= $purchase['id'] ?>;
let currentItemId  = <?= !empty($items) ? $items[0]['item_id'] : 0 ?>;
let currentQty     = <?= !empty($items) ? $items[0]['quantity'] : 0 ?>;
let currentScanned = <?= !empty($items) ? $items[0]['scanned'] : 0 ?>;
let lastImei       = null;
let feedbackTimer  = null;

// Item data map
const itemData = {
    <?php foreach ($items as $item): ?>
    <?= $item['item_id'] ?>: { qty: <?= $item['quantity'] ?>, scanned: <?= $item['scanned'] ?>, name: '<?= htmlspecialchars(addslashes($item['item_name']), ENT_QUOTES) ?>' },
    <?php endforeach; ?>
};

const scanInput = document.getElementById('scanInput');

// Always keep input focused
document.addEventListener('click', () => { if (document.activeElement !== scanInput) scanInput.focus(); });
document.addEventListener('keydown', e => {
    if (document.activeElement !== scanInput && !['INPUT','SELECT','TEXTAREA','BUTTON'].includes(e.target.tagName)) {
        scanInput.focus();
    }
});
scanInput.focus();

// Enter key = submit scan
scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doScan(this.value.trim()); }
});

function doScan(imei) {
    if (!imei) return;
    if (!/^\d{15,18}$/.test(imei)) {
        flashInput('err');
        showFeedback('err', '<i class="bi bi-x-circle me-1"></i> Must be 15–18 digits — got "' + imei + '"');
        scanInput.select();
        return;
    }
    scanInput.value = '';
    scanInput.disabled = true;

    fetch('?page=purchases&action=imeiScanAdd', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            imei:        imei,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(r => r.json())
    .then(res => {
        scanInput.disabled = false;
        scanInput.focus();
        if (res.ok) {
            lastImei = imei;
            flashInput('ok');
            showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> ' + imei + ' — saved');
            updateProgress(res.scanned, res.qty);
            prependImeiRow(imei);
            document.getElementById('btnUndo').disabled = false;
        } else {
            flashInput('err');
            showFeedback('err', '<i class="bi bi-exclamation-triangle me-1"></i> ' + res.msg);
        }
    })
    .catch(() => {
        scanInput.disabled = false;
        scanInput.focus();
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error — try again');
    });
}

function undoLast() {
    if (!lastImei) return;
    if (!confirm('Remove IMEI ' + lastImei + '?')) return;
    const imeiToDelete = lastImei;
    fetch('?page=purchases&action=imeiScanDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            imei:        imeiToDelete,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            lastImei = null;
            document.getElementById('btnUndo').disabled = true;
            updateProgress(res.scanned, currentQty);
            removeImeiRow(imeiToDelete);
            showFeedback('ok', 'Removed ' + imeiToDelete);
        } else {
            showFeedback('err', res.msg);
        }
        scanInput.focus();
    });
}

function deleteImei(imei) {
    if (!confirm('Remove IMEI ' + imei + '?')) { scanInput.focus(); return; }
    fetch('?page=purchases&action=imeiScanDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            imei:        imei,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            if (lastImei === imei) { lastImei = null; document.getElementById('btnUndo').disabled = true; }
            updateProgress(res.scanned, currentQty);
            removeImeiRow(imei);
        } else {
            showFeedback('err', res.msg);
        }
        scanInput.focus();
    });
}

function switchItem(itemId, qty, name) {
    // Update tab active state
    document.querySelectorAll('.item-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab_' + itemId)?.classList.add('active');

    currentItemId  = itemId;
    currentQty     = qty;
    lastImei       = null;
    document.getElementById('btnUndo').disabled = true;
    document.getElementById('stationItemName').textContent = name;
    document.getElementById('stationNeed').textContent     = '/ ' + qty;
    document.getElementById('scanFeedback').className      = 'idle';

    // Load scanned list from server
    fetch('?page=purchases&action=imeiScanList&purchase_id=' + PURCHASE_ID + '&item_id=' + itemId)
        .then(r => r.json())
        .then(rows => {
            const scanned = rows.length;
            updateProgress(scanned, qty);
            renderImeiList(rows);
            if (rows.length > 0) {
                lastImei = rows[0].imei;
                document.getElementById('btnUndo').disabled = false;
            }
        });
    scanInput.value = '';
    scanInput.focus();
}

function updateProgress(scanned, qty) {
    currentScanned = scanned;
    document.getElementById('stationCount').textContent = scanned;
    const pct = qty > 0 ? Math.min(100, Math.round(scanned / qty * 100)) : 0;
    const bar = document.getElementById('stationBar');
    bar.style.width = pct + '%';
    bar.className   = 'progress-bar-fill' + (pct >= 100 ? ' done' : '');
    document.getElementById('stationPct').textContent = pct + '%';

    // Update tab counter
    const tab = document.getElementById('tab_' + currentItemId);
    if (tab) {
        const span = tab.querySelector('.tab-count');
        if (span) {
            span.textContent = scanned + ' / ' + qty + (scanned >= qty ? ' ✓' : '');
            span.className   = 'tab-count ' + (scanned >= qty ? 'done' : 'warn');
        }
    }
    if (itemData[currentItemId]) itemData[currentItemId].scanned = scanned;
}

function prependImeiRow(imei) {
    const list = document.getElementById('imeiList');
    const empty = list.querySelector('.empty-list');
    if (empty) empty.remove();
    const now  = new Date().toLocaleTimeString();
    const div  = document.createElement('div');
    div.className   = 'imei-row';
    div.id          = 'ir_' + imei;
    div.innerHTML   = `<span class="imei-num">${imei}</span><div style="display:flex;align-items:center;gap:8px;"><span class="imei-time">${now}</span><button class="del-imei" onclick="deleteImei('${imei}')" title="Remove">×</button></div>`;
    list.insertBefore(div, list.firstChild);
}

function removeImeiRow(imei) {
    document.getElementById('ir_' + imei)?.remove();
    if (!document.getElementById('imeiList').children.length) {
        document.getElementById('imeiList').innerHTML = '<div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div>';
    }
}

function renderImeiList(rows) {
    const list = document.getElementById('imeiList');
    if (!rows.length) {
        list.innerHTML = '<div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div>';
        return;
    }
    list.innerHTML = rows.map(r => `
        <div class="imei-row" id="ir_${r.imei}">
            <span class="imei-num">${r.imei}</span>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="imei-time">${r.created_at}</span>
                <button class="del-imei" onclick="deleteImei('${r.imei}')" title="Remove">×</button>
            </div>
        </div>
    `).join('');
}

function flashInput(type) {
    scanInput.classList.remove('flash-ok', 'flash-err');
    void scanInput.offsetWidth; // reflow
    scanInput.classList.add('flash-' + type);
    setTimeout(() => scanInput.classList.remove('flash-ok', 'flash-err'), 600);
}

function showFeedback(type, msg) {
    clearTimeout(feedbackTimer);
    const el = document.getElementById('scanFeedback');
    el.className  = type;
    el.innerHTML  = msg;
    if (type === 'ok') feedbackTimer = setTimeout(() => { el.className = 'idle'; el.innerHTML = '—'; }, 2500);
}

// Load initial list for first item
<?php if (!empty($items)): ?>
switchItem(<?= $items[0]['item_id'] ?>, <?= $items[0]['quantity'] ?>, '<?= htmlspecialchars(addslashes($items[0]['item_name']), ENT_QUOTES) ?>');
<?php endif; ?>
</script>
