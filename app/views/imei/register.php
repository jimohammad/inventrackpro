<style>
.ir-page { max-width: 900px; }
.ir-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
.ir-head h1 { font-size:1.15rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }

.ir-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-bottom:16px; }
.ir-sep { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.ir-sep i { font-size:.8rem; }

/* Item selector */
.ir-item-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;margin-bottom:16px; }
.ir-item {
    border:1.5px solid var(--border-color);border-radius:10px;padding:12px 14px;cursor:pointer;
    transition:all .15s;display:flex;justify-content:space-between;align-items:center;
}
.ir-item:hover { border-color:var(--primary);transform:translateY(-1px); }
.ir-item.active { border-color:var(--primary);background:rgba(99,102,241,.06);box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.ir-item-name { font-weight:600;font-size:.85rem;color:var(--text-main); }
.ir-item-meta { font-size:.72rem;color:var(--text-muted);margin-top:2px; }
.ir-item-badge { display:flex;flex-direction:column;align-items:flex-end;gap:2px; }
.ir-stock-badge { font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:10px; }
.ir-stock-ok { background:rgba(34,197,94,.1);color:#16a34a; }
.ir-imei-badge { font-size:.68rem;font-weight:700;color:var(--primary); }
.ir-clear-btn {
    background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#dc2626;
    border-radius:6px;padding:3px 7px;font-size:.68rem;cursor:pointer;font-weight:600;
    margin-top:4px;display:inline-flex;align-items:center;gap:3px;transition:all .15s;
}
.ir-clear-btn:hover { background:rgba(239,68,68,.16); }

/* Scanner */
.ir-scanner { display:none;margin-top:16px; }
.ir-scanner.show { display:block; }
.ir-scan-wrap {
    display:flex;gap:8px;align-items:center;margin-bottom:12px;
}
.ir-scan-input {
    flex:1;padding:12px 16px;border:2px solid var(--primary);border-radius:10px;
    font-size:1.1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);
    color:var(--text-main);outline:none;
}
.ir-scan-input:focus { box-shadow:0 0 0 4px rgba(99,102,241,.15); }
.ir-scan-input::placeholder { color:var(--text-muted);font-size:.85rem;font-family:inherit;letter-spacing:normal; }

/* Scanned list */
.ir-scanned-list { max-height:300px;overflow-y:auto; }
.ir-scanned-item {
    display:flex;align-items:center;justify-content:space-between;
    padding:6px 10px;border-bottom:1px solid var(--border-color);font-size:.82rem;
    animation:irSlideIn .2s ease;
}
@keyframes irSlideIn { from{opacity:0;transform:translateX(-10px)} to{opacity:1;transform:translateX(0)} }
.ir-scanned-item:last-child { border-bottom:none; }
.ir-scanned-num { width:28px;font-weight:700;color:var(--text-muted);font-size:.75rem; }
.ir-scanned-imei { font-family:monospace;font-weight:600;color:var(--text-main);letter-spacing:.5px; }
.ir-scanned-ok { color:#16a34a;font-size:.85rem; }
.ir-status-icon { min-width:18px;text-align:center; }
.ir-scan-total {
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;background:rgba(99,102,241,.06);border-radius:8px;margin-top:8px;
    font-size:.82rem;font-weight:600;
}
.ir-scan-total .count { font-size:1.1rem;font-weight:800;color:var(--primary); }

.ir-msg { padding:6px 12px;border-radius:8px;font-size:.82rem;margin-bottom:8px;min-height:28px; }
.ir-msg.ok { background:#d1fae5;color:#065f46; }
.ir-msg.err { background:#fee2e2;color:#991b1b; }
</style>

<div class="ir-page">
    <div class="ir-head">
        <h1><i class="bi bi-upc-scan" style="color:var(--primary);"></i> Register IMEI — Existing Stock</h1>
        <span style="font-size:.82rem;color:var(--text-muted);font-weight:600;">
            <i class="bi bi-building me-1"></i><?= htmlspecialchars(Auth::warehouseName()) ?>
        </span>
    </div>

    <!-- Step 1: Select Item -->
    <div class="ir-card">
        <div class="ir-sep"><i class="bi bi-box-seam"></i> Select Item</div>
        <div style="position:relative;margin-bottom:14px;">
            <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:0.9rem;pointer-events:none;"></i>
            <input type="text" id="itemSearch" placeholder="Search item by name..."
                   autocomplete="off"
                   oninput="filterItems(this.value)"
                   style="width:100%;padding:9px 12px 9px 36px;border:1.5px solid #e0e7ff;border-radius:9px;font-size:0.9rem;font-weight:600;background:#fafbff;outline:none;transition:border-color .2s,box-shadow .2s;"
                   onfocus="this.style.borderColor='#818cf8';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                   onblur="this.style.borderColor='#e0e7ff';this.style.boxShadow=''">
        </div>
        <div class="ir-item-grid" id="itemGrid">
            <?php foreach ($items as $it):
                $remaining = max(0, (int)$it['stock'] - (int)$it['imei_count']);
            ?>
            <?php
                $irItemNameJson = json_encode((string)($it['name'] ?? ''), JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            ?>
            <div class="ir-item" onclick='selectScanItem(<?= (int)$it['id'] ?>, <?= $irItemNameJson ?>, <?= (int)$it['stock'] ?>, <?= (int)$it['imei_count'] ?>)' id="irItem_<?= (int)$it['id'] ?>">
                <div>
                    <div class="ir-item-name"><?= htmlspecialchars($it['name']) ?></div>
                    <div class="ir-item-meta"><?= htmlspecialchars($it['sku'] ?? '') ?></div>
                </div>
                <div class="ir-item-badge">
                    <span class="ir-stock-badge ir-stock-ok">Stock: <?= $it['stock'] ?></span>
                    <span class="ir-imei-badge" id="irBadge_<?= $it['id'] ?>"><?= $it['imei_count'] ?> IMEI</span>
                    <?php if (Auth::isAdmin() && (int)$it['imei_count'] > 0): ?>
                    <button type="button" class="ir-clear-btn" onclick='event.stopPropagation();clearItemImeis(<?= (int)$it['id'] ?>, <?= $irItemNameJson ?>, <?= (int)$it['imei_count'] ?>);' title="Delete all in_stock/returned IMEIs for this item (admin)">
                        <i class="bi bi-trash3"></i> Clear
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Scanner (shown after item select) -->
    <div class="ir-card ir-scanner" id="scannerPanel">
        <div class="ir-sep"><i class="bi bi-upc-scan"></i> Scan IMEI for: <strong id="scanItemName" style="color:var(--text-main);margin-left:4px;"></strong></div>

        <div class="ir-scan-wrap">
            <input type="text" class="ir-scan-input" id="scanInput"
                   placeholder="Scan barcode or type IMEI..."
                   autocomplete="off" autofocus
                   onkeydown="if(event.key==='Enter'){event.preventDefault();submitScan();}">
        </div>
        <div class="ir-msg" id="scanMsg"></div>

        <div class="ir-scan-total">
            <span>Scanned for this item</span>
            <span class="count" id="scanCount">0</span>
        </div>

        <div class="ir-scanned-list" id="scannedList"></div>
    </div>
</div>

<script>
var scanItemId   = null;
var scanItemName = '';
var scanTotal    = 0;

// Queue system — prevents dropped scans when scanning fast
var scanQueue      = [];   // IMEIs waiting to be saved
var queueRunning   = false;
var localScanned   = {};   // imei → row element (for dedup within session)
var localSeq       = 0;    // display counter for pending rows

function selectScanItem(id, name, stock, imeiCount) {
    document.querySelectorAll('.ir-item').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('irItem_' + id).classList.add('active');

    scanItemId   = id;
    scanItemName = name;
    scanTotal    = imeiCount;
    scanQueue    = [];
    queueRunning = false;
    localScanned = {};
    localSeq     = imeiCount;

    document.getElementById('scanItemName').textContent = name;
    document.getElementById('scanCount').textContent = imeiCount;
    document.getElementById('scannedList').innerHTML = '';
    document.getElementById('scanMsg').className = 'ir-msg';
    document.getElementById('scanMsg').textContent = '';

    document.getElementById('scannerPanel').classList.add('show');
    setTimeout(function() { document.getElementById('scanInput').focus(); }, 100);
}

function submitScan() {
    var input = document.getElementById('scanInput');
    var imei  = input.value.trim().replace(/\D/g, ''); // strip non-digits
    input.value = '';
    input.focus();

    if (!imei || !scanItemId) return;

    // Minimum length validation (13 for H40, 15 for everything else)
    var minLen = (scanItemName.toLowerCase().indexOf('h40') !== -1) ? 13 : 15;
    if (imei.length < minLen) {
        showMsg('err', 'Too short (' + imei.length + ' digits) — need at least ' + minLen);
        return;
    }

    // Client-side duplicate check within this session
    if (localScanned[imei]) {
        showMsg('err', 'Already scanned: ' + imei);
        return;
    }

    // Add to UI immediately — never wait for server
    localSeq++;
    var rowId = 'row_' + imei.replace(/[^a-zA-Z0-9]/g,'_');
    var list  = document.getElementById('scannedList');
    var div   = document.createElement('div');
    div.className = 'ir-scanned-item';
    div.id = rowId;
    div.innerHTML =
        '<span class="ir-scanned-num">' + localSeq + '</span>' +
        '<span class="ir-scanned-imei">' + imei + '</span>' +
        '<span class="ir-status-icon" style="font-size:.85rem;color:#f59e0b;"><i class="bi bi-hourglass-split"></i></span>';
    list.insertBefore(div, list.firstChild);

    localScanned[imei] = div;
    document.getElementById('scanCount').textContent = localSeq;

    // Add to save queue and process
    scanQueue.push(imei);
    processQueue();
}

function processQueue() {
    if (queueRunning || scanQueue.length === 0) return;
    queueRunning = true;

    var imei = scanQueue.shift();

    fetch('?page=imei&action=saveImei', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= Auth::csrfToken() ?>&imei=' + encodeURIComponent(imei) + '&item_id=' + scanItemId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var rowId = 'row_' + imei.replace(/[^a-zA-Z0-9]/g,'_');
        var row   = document.getElementById(rowId);
        var icon  = row ? row.querySelector('.ir-status-icon') : null;

        if (data.error) {
            showMsg('err', data.error);
            if (icon) icon.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#dc2626;" title="' + data.error + '"></i>';
            // Remove from local map so user can re-scan after correcting
            delete localScanned[imei];
            // Adjust display count
            localSeq--;
            document.getElementById('scanCount').textContent = localSeq;
            if (row) row.style.opacity = '0.4';
        } else {
            showMsg('ok', 'Saved: ' + imei);
            if (icon) icon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>';
            scanTotal = data.count;
            // Update badge on item card
            var badge = document.getElementById('irBadge_' + scanItemId);
            if (badge) badge.textContent = data.count + ' IMEI';
        }
    })
    .catch(function() {
        showMsg('err', 'Network error — retrying ' + imei);
        // Re-queue at front for retry
        scanQueue.unshift(imei);
    })
    .finally(function() {
        queueRunning = false;
        processQueue(); // process next in queue
    });
}

function showMsg(type, text) {
    var msg = document.getElementById('scanMsg');
    msg.className = 'ir-msg ' + type;
    msg.textContent = text;
}

function filterItems(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#itemGrid .ir-item').forEach(function(el) {
        var name = el.querySelector('.ir-item-name').textContent.toLowerCase();
        el.style.display = (!q || name.indexOf(q) > -1) ? '' : 'none';
    });
}

// Keep scan input focused when clicking outside item cards,
// but don't steal focus from the search box
document.addEventListener('click', function(e) {
    if (scanItemId && !e.target.closest('.ir-item') && e.target.id !== 'itemSearch') {
        document.getElementById('scanInput').focus();
    }
});

// Admin: clear all in_stock + returned IMEIs for an item, then user can rescan from scratch
// Gated by admin PIN (requirePin from layout.php)
function clearItemImeis(itemId, itemName, currentCount) {
    var msg = 'Delete ALL ' + currentCount + ' in-stock IMEIs for "' + itemName + '" in this warehouse?\n\n' +
              'Sold/transferred/defective records are kept. You can re-scan from scratch after this.\n\n' +
              'This cannot be undone.';
    if (!confirm(msg)) return;

    requirePin(function() {
        var fd = new FormData();
        fd.append('item_id', itemId);
        fd.append('csrf_token', '<?= Auth::csrfToken() ?>');

        fetch('?page=imei&action=clearItemImeis', { method: 'POST', body: fd })
            .then(function(r) {
                return r.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        var hint = (text && text.indexOf('Invalid request') !== -1)
                            ? 'Refresh the page (session/CSRF) and try again.'
                            : 'Server returned non-JSON (HTTP ' + r.status + ').';
                        throw new Error(hint);
                    }
                });
            })
            .then(function(d) {
                if (!d.ok) { alert(d.msg || 'Request failed'); return; }
                var badge = document.getElementById('irBadge_' + itemId);
                if (badge) badge.textContent = '0 IMEI';
                var card = document.getElementById('irItem_' + itemId);
                if (card) {
                    var btn = card.querySelector('.ir-clear-btn');
                    if (btn) btn.remove();
                }
                if (scanItemId === itemId) {
                    scanTotal    = 0;
                    localSeq     = 0;
                    localScanned = {};
                    scanQueue    = [];
                    document.getElementById('scanCount').textContent = 0;
                    document.getElementById('scannedList').innerHTML = '';
                    document.getElementById('scanMsg').className = 'ir-msg ok';
                    document.getElementById('scanMsg').textContent = d.msg + ' — start scanning from fresh.';
                    document.getElementById('scanInput').focus();
                } else {
                    alert(d.msg);
                }
            })
            .catch(function(err) { alert(err && err.message ? err.message : 'Network error.'); });
    });
}
</script>
