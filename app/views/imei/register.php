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
        <div class="ir-item-grid">
            <?php foreach ($items as $it):
                $remaining = max(0, (int)$it['stock'] - (int)$it['imei_count']);
            ?>
            <div class="ir-item" onclick="selectScanItem(<?= $it['id'] ?>, '<?= htmlspecialchars(addslashes($it['name'])) ?>', <?= (int)$it['stock'] ?>, <?= (int)$it['imei_count'] ?>)" id="irItem_<?= $it['id'] ?>">
                <div>
                    <div class="ir-item-name"><?= htmlspecialchars($it['name']) ?></div>
                    <div class="ir-item-meta"><?= htmlspecialchars($it['sku'] ?? '') ?></div>
                </div>
                <div class="ir-item-badge">
                    <span class="ir-stock-badge ir-stock-ok">Stock: <?= $it['stock'] ?></span>
                    <span class="ir-imei-badge" id="irBadge_<?= $it['id'] ?>"><?= $it['imei_count'] ?> IMEI</span>
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
var scanItemId = null;
var scanItemName = '';
var scanTotal = 0;

function selectScanItem(id, name, stock, imeiCount) {
    // Deselect all
    document.querySelectorAll('.ir-item').forEach(function(el) { el.classList.remove('active'); });
    // Select this
    document.getElementById('irItem_' + id).classList.add('active');

    scanItemId = id;
    scanItemName = name;
    scanTotal = imeiCount;

    document.getElementById('scanItemName').textContent = name;
    document.getElementById('scanCount').textContent = imeiCount;
    document.getElementById('scannedList').innerHTML = '';
    document.getElementById('scanMsg').className = 'ir-msg';
    document.getElementById('scanMsg').textContent = '';

    var panel = document.getElementById('scannerPanel');
    panel.classList.add('show');
    setTimeout(function() { document.getElementById('scanInput').focus(); }, 100);
}

function submitScan() {
    var input = document.getElementById('scanInput');
    var imei = input.value.trim();
    if (!imei || !scanItemId) return;

    input.disabled = true;

    fetch('?page=imei&action=saveImei', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= Auth::csrfToken() ?>&imei=' + encodeURIComponent(imei) + '&item_id=' + scanItemId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        input.disabled = false;
        input.value = '';
        input.focus();

        var msg = document.getElementById('scanMsg');
        if (data.error) {
            msg.className = 'ir-msg err';
            msg.textContent = data.error;
            return;
        }

        msg.className = 'ir-msg ok';
        msg.textContent = 'Added: ' + imei;

        scanTotal = data.count;
        document.getElementById('scanCount').textContent = scanTotal;

        // Update badge on item card
        var badge = document.getElementById('irBadge_' + scanItemId);
        if (badge) badge.textContent = scanTotal + ' IMEI';

        // Add to scanned list
        var list = document.getElementById('scannedList');
        var div = document.createElement('div');
        div.className = 'ir-scanned-item';
        div.innerHTML = '<span class="ir-scanned-num">' + scanTotal + '</span>' +
            '<span class="ir-scanned-imei">' + imei + '</span>' +
            '<span class="ir-scanned-ok"><i class="bi bi-check-circle-fill"></i></span>';
        list.insertBefore(div, list.firstChild);
    })
    .catch(function() {
        input.disabled = false;
        input.focus();
        var msg = document.getElementById('scanMsg');
        msg.className = 'ir-msg err';
        msg.textContent = 'Network error — try again';
    });
}
</script>
