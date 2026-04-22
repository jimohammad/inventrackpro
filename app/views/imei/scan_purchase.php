<style>
.sp-page { max-width: 900px; }
.sp-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
.sp-head h1 { font-size:1.15rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }
.sp-info { display:flex;gap:16px;flex-wrap:wrap;font-size:.82rem;color:var(--text-muted);margin-bottom:16px; }
.sp-info span { display:flex;align-items:center;gap:4px; }
.sp-info strong { color:var(--text-main); }

.sp-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-bottom:16px; }
.sp-sep { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;display:flex;align-items:center;gap:6px; }

/* Item rows */
.sp-item-row {
    border:1.5px solid var(--border-color);border-radius:10px;padding:12px 16px;
    margin-bottom:10px;transition:border-color .15s;
}
.sp-item-row:last-child { margin-bottom:0; }
.sp-item-row.active { border-color:var(--primary);background:rgba(99,102,241,.04); }
.sp-item-row.done   { border-color:#22c55e;background:rgba(34,197,94,.03); }

.sp-item-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:8px; }
.sp-item-name { font-weight:600;font-size:.88rem;color:var(--text-main); }
.sp-item-meta { font-size:.72rem;color:var(--text-muted);margin-top:1px; }

.sp-item-right { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.sp-prog { font-size:.85rem;font-weight:700;white-space:nowrap; }
.sp-prog .cnt { font-size:1rem;font-weight:800; }
.sp-prog .cnt.ok   { color:#22c55e; }
.sp-prog .cnt.pend { color:#f59e0b; }

/* Progress bar */
.sp-bar-wrap { height:5px;background:var(--border-color);border-radius:4px;overflow:hidden; }
.sp-bar      { height:5px;border-radius:4px;background:var(--primary);transition:width .4s ease; }

/* Action buttons */
.sp-btn {
    padding:5px 12px;border-radius:7px;font-size:.78rem;font-weight:600;
    border:1.5px solid;cursor:pointer;display:flex;align-items:center;gap:4px;
    transition:all .15s;background:transparent;white-space:nowrap;
}
.sp-btn-scan  { border-color:var(--primary);color:var(--primary); }
.sp-btn-scan:hover  { background:var(--primary);color:#fff; }
.sp-btn-paste { border-color:#0ea5e9;color:#0ea5e9; }
.sp-btn-paste:hover { background:#0ea5e9;color:#fff; }

/* Inline scanner */
.sp-scan-input {
    width:100%;padding:12px 16px;border:2px solid var(--primary);border-radius:10px;
    font-size:1.1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);
    color:var(--text-main);outline:none;
}
.sp-scan-input:focus { box-shadow:0 0 0 4px rgba(99,102,241,.15); }
.sp-scan-input::placeholder { color:var(--text-muted);font-size:.85rem;font-family:inherit;letter-spacing:normal; }

.sp-msg { padding:6px 12px;border-radius:8px;font-size:.82rem;margin:8px 0;min-height:28px; }
.sp-msg.ok  { background:#d1fae5;color:#065f46; }
.sp-msg.err { background:#fee2e2;color:#991b1b; }

.sp-scanned-item {
    display:flex;align-items:center;justify-content:space-between;
    padding:5px 10px;border-bottom:1px solid var(--border-color);font-size:.82rem;
    animation:spSlideIn .2s ease;
}
@keyframes spSlideIn { from{opacity:0;transform:translateX(-10px)} to{opacity:1;transform:translateX(0)} }
.sp-scanned-item:last-child { border-bottom:none; }
.sp-scanned-num  { width:28px;font-weight:700;color:var(--text-muted);font-size:.75rem; }
.sp-scanned-imei { font-family:monospace;font-weight:600;color:var(--text-main);letter-spacing:.5px;flex:1; }
.sp-status-icon  { min-width:20px;text-align:center; }

/* Done bar */
.sp-done-bar { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:rgba(34,197,94,.08);border:1.5px solid rgba(34,197,94,.2);border-radius:10px; }
.sp-done-bar a { padding:8px 20px;background:var(--primary);color:#fff;border-radius:8px;text-decoration:none;font-size:.85rem;font-weight:600; }

/* Paste modal */
.pm-textarea {
    width:100%;padding:12px;border:1.5px solid var(--border-color);border-radius:8px;
    font-family:monospace;font-size:.85rem;resize:vertical;background:var(--bg-main);
    color:var(--text-main);outline:none;min-height:200px;
}
.pm-textarea:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.1); }

.pm-summary { display:flex;gap:14px;margin-bottom:10px;font-size:.85rem;font-weight:700; }
.pm-ok-count  { color:#16a34a;display:flex;align-items:center;gap:5px; }
.pm-err-count { color:#dc2626;display:flex;align-items:center;gap:5px; }

.pm-err-list   { background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;max-height:160px;overflow-y:auto; }
.pm-err-header { font-size:.75rem;font-weight:700;color:#991b1b;margin-bottom:6px; }
.pm-err-row    { display:flex;gap:12px;font-size:.78rem;padding:2px 0;border-bottom:1px solid #fecaca; }
.pm-err-row:last-child { border-bottom:none; }
.pm-err-imei   { font-family:monospace;font-weight:600;color:#7f1d1d;width:160px;flex-shrink:0; }
.pm-err-reason { color:#991b1b; }
</style>

<div class="sp-page">
    <div class="sp-head">
        <h1><i class="bi bi-upc-scan" style="color:var(--primary);"></i> Receive IMEIs — <?= htmlspecialchars($purchase['invoice_no']) ?></h1>
    </div>

    <div class="sp-info">
        <span><i class="bi bi-file-earmark-text"></i> <strong><?= htmlspecialchars($purchase['invoice_no']) ?></strong></span>
        <span><i class="bi bi-truck"></i> <strong><?= htmlspecialchars($purchase['party_name'] ?? '—') ?></strong></span>
        <span><i class="bi bi-calendar3"></i> <?= $purchase['date'] ?></span>
    </div>

    <!-- Items -->
    <div class="sp-card">
        <div class="sp-sep"><i class="bi bi-box-seam"></i> Items to Receive</div>

        <?php foreach ($items as $it):
            $qty     = (int)$it['quantity'];
            $scanned = (int)$it['imei_count'];
            $done    = $scanned >= $qty;
            $pct     = $qty > 0 ? min(100, round($scanned / $qty * 100)) : 0;
            $nameJs  = htmlspecialchars(addslashes($it['name']));
        ?>
        <div class="sp-item-row <?= $done ? 'done' : '' ?>" id="spItem_<?= $it['item_id'] ?>">
            <div class="sp-item-top">
                <div>
                    <div class="sp-item-name"><?= htmlspecialchars($it['name']) ?></div>
                    <div class="sp-item-meta"><?= htmlspecialchars($it['sku'] ?? '') ?> &middot; Ordered: <?= $qty ?></div>
                </div>
                <div class="sp-item-right">
                    <span class="sp-prog">
                        <span id="spCount_<?= $it['item_id'] ?>" class="cnt <?= $done ? 'ok' : 'pend' ?>"><?= $scanned ?></span>
                        <span style="color:var(--text-muted);font-weight:400;"> / <?= $qty ?></span>
                    </span>
                    <?php if ($done): ?>
                    <i id="spDone_<?= $it['item_id'] ?>" class="bi bi-check-circle-fill" style="color:#22c55e;font-size:1.1rem;"></i>
                    <?php else: ?>
                    <i id="spDone_<?= $it['item_id'] ?>" class="bi bi-check-circle-fill" style="color:#22c55e;font-size:1.1rem;display:none;"></i>
                    <?php endif; ?>
                    <button class="sp-btn sp-btn-scan"
                            onclick="openScanMode(<?= $it['item_id'] ?>, '<?= $nameJs ?>', <?= $qty ?>, <?= $scanned ?>)">
                        <i class="bi bi-upc-scan"></i> Scan
                    </button>
                    <button class="sp-btn sp-btn-paste"
                            onclick="openPasteMode(<?= $it['item_id'] ?>, '<?= $nameJs ?>', <?= $qty ?>)">
                        <i class="bi bi-clipboard-plus"></i> Paste
                    </button>
                </div>
            </div>
            <div class="sp-bar-wrap">
                <div class="sp-bar" id="spBar_<?= $it['item_id'] ?>"
                     style="width:<?= $pct ?>%;<?= $done ? 'background:#22c55e;' : '' ?>"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Inline scanner panel -->
    <div class="sp-card" id="spScanPanel" style="display:none;">
        <div class="sp-sep">
            <i class="bi bi-upc-scan"></i> Scanning:
            <strong id="spScanItemName" style="color:var(--text-main);margin-left:4px;"></strong>
        </div>
        <input type="text" class="sp-scan-input" id="spScanInput"
               placeholder="Scan barcode or type IMEI and press Enter..."
               autocomplete="off"
               onkeydown="if(event.key==='Enter'){event.preventDefault();submitScan();}">
        <div class="sp-msg" id="spMsg"></div>
        <div id="spScannedList" style="max-height:260px;overflow-y:auto;margin-top:6px;"></div>
    </div>

    <div class="sp-done-bar">
        <span style="font-size:.85rem;color:var(--text-muted);">Scan or paste all IMEIs, then go to the purchase.</span>
        <a href="?page=purchases&action=detail&id=<?= $purchase['id'] ?>">
            <i class="bi bi-check-lg me-1"></i> Go to Purchase
        </a>
    </div>
</div>

<!-- ── Paste Modal ───────────────────────────────────────── -->
<div class="modal fade" id="pasteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-color);">
            <div class="modal-header" style="border-color:var(--border-color);">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-clipboard-plus" style="color:#0ea5e9;"></i>
                    Paste IMEIs — <span id="pmItemName" style="color:var(--primary);"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:10px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Scan all IMEIs into Notepad first, then select all (Ctrl+A), copy (Ctrl+C), and paste below.
                    One IMEI per line.
                </p>
                <textarea id="pmTextarea" class="pm-textarea"
                          placeholder="Paste IMEIs here (one per line)...&#10;&#10;358441234567890&#10;358441234567891&#10;358441234567892"></textarea>
                <div id="pmPreview" style="margin-top:12px;display:none;"></div>
            </div>
            <div class="modal-footer" style="border-color:var(--border-color);">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm"
                        style="background:#0ea5e9;color:#fff;border:none;"
                        onclick="validatePaste()">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="pmConfirmBtn" disabled
                        style="background:var(--primary);color:#fff;border:none;"
                        onclick="confirmPaste()">
                    <i class="bi bi-cloud-upload me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var spPurchaseId = <?= (int)$purchase['id'] ?>;
var spCsrf       = '<?= Auth::csrfToken() ?>';

// ── Scan mode state ──
var spItemId   = null;
var spItemName = '';
var spItemQty  = 0;
var spQueue    = [];
var spQueueRun = false;
var spLocalSeen = {};
var spSeq      = 0;

// ── Paste mode state ──
var pmItemId   = null;
var pmItemName = '';
var pmItemQty  = 0;
var pmValidList = [];  // validated IMEIs ready to import

// ════════════════════════════════════════════
//  SCAN MODE
// ════════════════════════════════════════════
function openScanMode(itemId, name, qty, count) {
    document.querySelectorAll('.sp-item-row').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('spItem_' + itemId).classList.add('active');

    spItemId    = itemId;
    spItemName  = name;
    spItemQty   = qty;
    spSeq       = count;
    spQueue     = [];
    spQueueRun  = false;
    spLocalSeen = {};

    document.getElementById('spScanItemName').textContent = name;
    document.getElementById('spScannedList').innerHTML    = '';
    document.getElementById('spMsg').className  = 'sp-msg';
    document.getElementById('spMsg').textContent = '';
    document.getElementById('spScanPanel').style.display  = 'block';
    document.getElementById('spScanPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    setTimeout(function() { document.getElementById('spScanInput').focus(); }, 150);
}

function submitScan() {
    var input = document.getElementById('spScanInput');
    var imei  = input.value.trim().replace(/\D/g, '');
    input.value = '';
    input.focus();

    if (!imei || !spItemId) return;

    var minLen = (spItemName.toLowerCase().indexOf('h40') !== -1) ? 13 : 15;
    if (imei.length < minLen) {
        showScanMsg('err', 'Too short (' + imei.length + ' digits) — need ' + minLen);
        return;
    }
    if (!luhn(imei)) {
        showScanMsg('err', 'Invalid IMEI — check digit failed');
        return;
    }
    if (spLocalSeen[imei]) {
        showScanMsg('err', 'Already scanned this session: ' + imei);
        return;
    }

    // Add to UI immediately (optimistic)
    spSeq++;
    var rowId = 'spr_' + imei;
    var list  = document.getElementById('spScannedList');
    var div   = document.createElement('div');
    div.className = 'sp-scanned-item';
    div.id = rowId;
    div.innerHTML =
        '<span class="sp-scanned-num">' + spSeq + '</span>' +
        '<span class="sp-scanned-imei">' + imei + '</span>' +
        '<span class="sp-status-icon"><i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:.85rem;"></i></span>';
    list.insertBefore(div, list.firstChild);
    spLocalSeen[imei] = div;

    updateCount(spItemId, spSeq, spItemQty);

    spQueue.push(imei);
    processQueue();
}

function processQueue() {
    if (spQueueRun || spQueue.length === 0) return;
    spQueueRun = true;
    var imei = spQueue.shift();

    fetch('?page=imei&action=savePurchaseImei', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + spCsrf + '&imei=' + encodeURIComponent(imei) +
              '&item_id=' + spItemId + '&purchase_id=' + spPurchaseId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var row  = document.getElementById('spr_' + imei);
        var icon = row ? row.querySelector('.sp-status-icon') : null;

        if (data.error) {
            showScanMsg('err', data.error);
            if (icon) icon.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#dc2626;" title="' + data.error + '"></i>';
            delete spLocalSeen[imei];
            spSeq--;
            if (row) row.style.opacity = '0.4';
            updateCount(spItemId, spSeq, spItemQty);
        } else {
            showScanMsg('ok', 'Saved: ' + imei);
            if (icon) icon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>';
            spSeq = data.count;
            updateCount(spItemId, data.count, spItemQty);
        }
    })
    .catch(function() {
        showScanMsg('err', 'Network error — retrying...');
        spQueue.unshift(imei);
    })
    .finally(function() {
        spQueueRun = false;
        processQueue();
    });
}

function showScanMsg(type, text) {
    var msg = document.getElementById('spMsg');
    msg.className    = 'sp-msg ' + type;
    msg.textContent  = text;
}

// ════════════════════════════════════════════
//  PASTE MODE
// ════════════════════════════════════════════
function openPasteMode(itemId, name, qty) {
    pmItemId    = itemId;
    pmItemName  = name;
    pmItemQty   = qty;
    pmValidList = [];

    document.getElementById('pmItemName').textContent  = name;
    document.getElementById('pmTextarea').value        = '';
    document.getElementById('pmPreview').style.display = 'none';
    document.getElementById('pmPreview').innerHTML     = '';
    document.getElementById('pmConfirmBtn').disabled   = true;
    document.getElementById('pmConfirmBtn').innerHTML  = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';

    var modal = new bootstrap.Modal(document.getElementById('pasteModal'));
    modal.show();
    setTimeout(function() { document.getElementById('pmTextarea').focus(); }, 350);
}

function validatePaste() {
    var raw    = document.getElementById('pmTextarea').value;
    var lines  = raw.split(/[\r\n,;]+/);
    var minLen = (pmItemName.toLowerCase().indexOf('h40') !== -1) ? 13 : 15;

    var valid  = [];
    var errors = [];
    var seen   = {};

    lines.forEach(function(line) {
        var imei = line.replace(/\D/g, '').trim();
        if (!imei) return;

        if (imei.length < minLen) {
            errors.push({ imei: imei, reason: 'Too short (' + imei.length + ' digits, need ' + minLen + ')' });
        } else if (!luhn(imei)) {
            errors.push({ imei: imei, reason: 'Invalid IMEI (check digit failed)' });
        } else if (seen[imei]) {
            errors.push({ imei: imei, reason: 'Duplicate in list' });
        } else {
            seen[imei] = true;
            valid.push(imei);
        }
    });

    pmValidList = valid;

    // Build preview
    var html = '<div class="pm-summary">';
    html += '<span class="pm-ok-count"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' valid</span>';
    if (errors.length > 0) {
        html += '<span class="pm-err-count"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="pm-err-list"><div class="pm-err-header">Invalid IMEIs:</div>';
        errors.forEach(function(e) {
            html += '<div class="pm-err-row">' +
                    '<span class="pm-err-imei">' + e.imei + '</span>' +
                    '<span class="pm-err-reason">' + e.reason + '</span>' +
                    '</div>';
        });
        html += '</div>';
    }

    var preview = document.getElementById('pmPreview');
    preview.innerHTML     = html;
    preview.style.display = 'block';

    document.getElementById('pmConfirmBtn').disabled = (valid.length === 0);
}

function confirmPaste() {
    if (pmValidList.length === 0) return;

    var btn = document.getElementById('pmConfirmBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';

    fetch('?page=imei&action=bulkSavePurchaseImei', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + spCsrf +
              '&imeis=' + encodeURIComponent(pmValidList.join('\n')) +
              '&item_id=' + pmItemId +
              '&purchase_id=' + spPurchaseId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            alert('Error: ' + data.error);
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('pasteModal')).hide();
        updateCount(pmItemId, data.total, pmItemQty);

        var msg = data.saved + ' IMEIs imported';
        if (data.skipped && data.skipped.length > 0) {
            msg += ', ' + data.skipped.length + ' skipped (already in system)';
        }
        showToast(msg);
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    });
}

// ════════════════════════════════════════════
//  SHARED HELPERS
// ════════════════════════════════════════════

/** Luhn algorithm — validates IMEI check digit */
function luhn(n) {
    var sum = 0, flip = false;
    for (var i = n.length - 1; i >= 0; i--) {
        var d = parseInt(n[i], 10);
        if (flip) { d *= 2; if (d > 9) d -= 9; }
        sum += d;
        flip = !flip;
    }
    return (sum % 10 === 0);
}

function updateCount(itemId, count, qty) {
    var countEl = document.getElementById('spCount_' + itemId);
    var barEl   = document.getElementById('spBar_'   + itemId);
    var doneEl  = document.getElementById('spDone_'  + itemId);
    var rowEl   = document.getElementById('spItem_'  + itemId);

    if (countEl) {
        countEl.textContent = count;
        countEl.className   = 'cnt ' + (count >= qty ? 'ok' : 'pend');
    }
    if (barEl) {
        var pct = qty > 0 ? Math.min(100, Math.round(count / qty * 100)) : 0;
        barEl.style.width      = pct + '%';
        barEl.style.background = pct >= 100 ? '#22c55e' : 'var(--primary)';
    }
    if (doneEl)  doneEl.style.display  = count >= qty ? 'inline' : 'none';
    if (rowEl && count >= qty) rowEl.classList.add('done');
}

function showToast(msg) {
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;' +
        'background:#d1fae5;color:#065f46;padding:12px 20px;border-radius:10px;' +
        'font-size:.85rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.12);' +
        'border:1px solid #a7f3d0;display:flex;align-items:center;gap:8px;';
    t.innerHTML = '<i class="bi bi-check-circle-fill"></i>' + msg;
    document.body.appendChild(t);
    setTimeout(function() { t.style.opacity='0';t.style.transition='opacity .4s'; }, 3500);
    setTimeout(function() { t.remove(); }, 4000);
}

// Keep scan input focused when clicking outside item rows
document.addEventListener('click', function(e) {
    if (spItemId &&
        !e.target.closest('.sp-item-row') &&
        !e.target.closest('#pasteModal') &&
        !e.target.closest('.sp-btn')) {
        document.getElementById('spScanInput').focus();
    }
});
</script>
