<style>
.sp-page { max-width: 900px; }
.sp-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
.sp-head h1 { font-size:1.15rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }
.sp-info { display:flex;gap:16px;flex-wrap:wrap;font-size:.82rem;color:var(--text-muted);margin-bottom:16px; }
.sp-info span { display:flex;align-items:center;gap:4px; }
.sp-info strong { color:var(--text-main); }

.sp-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-bottom:16px; }
.sp-sep { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.sp-sep i { font-size:.8rem; }

.sp-item-list { display:flex;flex-direction:column;gap:8px;margin-bottom:16px; }
.sp-item {
    border:1.5px solid var(--border-color);border-radius:10px;padding:12px 16px;cursor:pointer;
    display:flex;justify-content:space-between;align-items:center;transition:all .15s;
}
.sp-item:hover { border-color:var(--primary);transform:translateY(-1px); }
.sp-item.active { border-color:var(--primary);background:rgba(99,102,241,.06);box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.sp-item.done { border-color:#22c55e;background:rgba(34,197,94,.04); }
.sp-item-name { font-weight:600;font-size:.88rem;color:var(--text-main); }
.sp-item-meta { font-size:.72rem;color:var(--text-muted);margin-top:2px; }
.sp-item-badge { display:flex;align-items:center;gap:8px; }
.sp-progress { font-size:.82rem;font-weight:700; }
.sp-progress .done { color:#22c55e; }
.sp-progress .pending { color:#f59e0b; }

.sp-scan-input {
    flex:1;padding:12px 16px;border:2px solid var(--primary);border-radius:10px;
    font-size:1.1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);
    color:var(--text-main);outline:none;width:100%;
}
.sp-scan-input:focus { box-shadow:0 0 0 4px rgba(99,102,241,.15); }
.sp-scan-input::placeholder { color:var(--text-muted);font-size:.85rem;font-family:inherit;letter-spacing:normal; }

.sp-msg { padding:6px 12px;border-radius:8px;font-size:.82rem;margin:8px 0;min-height:28px; }
.sp-msg.ok { background:#d1fae5;color:#065f46; }
.sp-msg.err { background:#fee2e2;color:#991b1b; }

.sp-scanned-item {
    display:flex;align-items:center;justify-content:space-between;
    padding:5px 10px;border-bottom:1px solid var(--border-color);font-size:.82rem;
    animation:spSlideIn .2s ease;
}
@keyframes spSlideIn { from{opacity:0;transform:translateX(-10px)} to{opacity:1;transform:translateX(0)} }
.sp-scanned-num { width:28px;font-weight:700;color:var(--text-muted);font-size:.75rem; }
.sp-scanned-imei { font-family:monospace;font-weight:600;color:var(--text-main);letter-spacing:.5px; }
.sp-scanned-ok { color:#16a34a;font-size:.85rem; }

.sp-done-bar { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:rgba(34,197,94,.08);border:1.5px solid rgba(34,197,94,.2);border-radius:10px;margin-top:16px; }
.sp-done-bar a { padding:8px 20px;background:var(--primary);color:#fff;border-radius:8px;text-decoration:none;font-size:.85rem;font-weight:600; }
</style>

<div class="sp-page">
    <div class="sp-head">
        <h1><i class="bi bi-upc-scan" style="color:var(--primary);"></i> Scan IMEIs — <?= htmlspecialchars($purchase['invoice_no']) ?></h1>
    </div>

    <div class="sp-info">
        <span><i class="bi bi-file-earmark-text"></i> <strong><?= htmlspecialchars($purchase['invoice_no']) ?></strong></span>
        <span><i class="bi bi-truck"></i> <strong><?= htmlspecialchars($purchase['party_name'] ?? '—') ?></strong></span>
        <span><i class="bi bi-calendar3"></i> <?= $purchase['date'] ?></span>
    </div>

    <!-- Items needing IMEI -->
    <div class="sp-card">
        <div class="sp-sep"><i class="bi bi-box-seam"></i> Items to Scan</div>
        <div class="sp-item-list">
            <?php foreach ($items as $it):
                $done = (int)$it['imei_count'] >= (int)$it['quantity'];
            ?>
            <div class="sp-item <?= $done ? 'done' : '' ?>"
                 id="spItem_<?= $it['item_id'] ?>"
                 onclick="selectPurchaseItem(<?= $it['item_id'] ?>, '<?= htmlspecialchars(addslashes($it['name'])) ?>', <?= (int)$it['quantity'] ?>, <?= (int)$it['imei_count'] ?>)">
                <div>
                    <div class="sp-item-name"><?= htmlspecialchars($it['name']) ?></div>
                    <div class="sp-item-meta">Qty: <?= $it['quantity'] ?> · <?= htmlspecialchars($it['sku'] ?? '') ?></div>
                </div>
                <div class="sp-item-badge">
                    <span class="sp-progress">
                        <span id="spCount_<?= $it['item_id'] ?>" class="<?= $done ? 'done' : 'pending' ?>"><?= $it['imei_count'] ?></span> / <?= $it['quantity'] ?>
                    </span>
                    <?php if ($done): ?>
                    <i class="bi bi-check-circle-fill" style="color:#22c55e;font-size:1.1rem;"></i>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Scanner -->
    <div class="sp-card" id="spScanPanel" style="display:none;">
        <div class="sp-sep"><i class="bi bi-upc-scan"></i> Scanning: <strong id="spItemName" style="color:var(--text-main);margin-left:4px;"></strong></div>
        <input type="text" class="sp-scan-input" id="spScanInput" placeholder="Scan barcode or type IMEI..." autocomplete="off"
               onkeydown="if(event.key==='Enter'){event.preventDefault();submitPurchaseScan();}">
        <div class="sp-msg" id="spMsg"></div>
        <div id="spScannedList" style="max-height:250px;overflow-y:auto;"></div>
    </div>

    <!-- Done bar -->
    <div class="sp-done-bar">
        <span style="font-size:.85rem;color:var(--text-muted);">When all items are scanned, you're done.</span>
        <a href="?page=purchases&action=detail&id=<?= $purchase['id'] ?>"><i class="bi bi-check-lg me-1"></i> Go to Purchase</a>
    </div>
</div>

<script>
var spItemId = null;
var spPurchaseId = <?= (int)$purchase['id'] ?>;
var spCsrf = '<?= Auth::csrfToken() ?>';

function selectPurchaseItem(id, name, qty, count) {
    document.querySelectorAll('.sp-item').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('spItem_' + id).classList.add('active');

    spItemId = id;
    document.getElementById('spItemName').textContent = name;
    document.getElementById('spScannedList').innerHTML = '';
    document.getElementById('spMsg').className = 'sp-msg';
    document.getElementById('spMsg').textContent = '';
    document.getElementById('spScanPanel').style.display = 'block';

    setTimeout(function() { document.getElementById('spScanInput').focus(); }, 100);
}

function submitPurchaseScan() {
    var input = document.getElementById('spScanInput');
    var imei = input.value.trim();
    if (!imei || !spItemId) return;

    input.disabled = true;

    fetch('?page=imei&action=savePurchaseImei', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + spCsrf + '&imei=' + encodeURIComponent(imei) + '&item_id=' + spItemId + '&purchase_id=' + spPurchaseId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        input.disabled = false;
        input.value = '';
        input.focus();

        var msg = document.getElementById('spMsg');
        if (data.error) {
            msg.className = 'sp-msg err';
            msg.textContent = data.error;
            return;
        }

        msg.className = 'sp-msg ok';
        msg.textContent = 'Added: ' + imei;

        // Update count on item card
        var countEl = document.getElementById('spCount_' + spItemId);
        if (countEl) {
            countEl.textContent = data.count;
            countEl.className = '';
        }

        // Add to scanned list
        var list = document.getElementById('spScannedList');
        var div = document.createElement('div');
        div.className = 'sp-scanned-item';
        div.innerHTML = '<span class="sp-scanned-num">' + data.count + '</span>' +
            '<span class="sp-scanned-imei">' + imei + '</span>' +
            '<span class="sp-scanned-ok"><i class="bi bi-check-circle-fill"></i></span>';
        list.insertBefore(div, list.firstChild);
    })
    .catch(function() {
        input.disabled = false;
        input.focus();
        document.getElementById('spMsg').className = 'sp-msg err';
        document.getElementById('spMsg').textContent = 'Network error';
    });
}
</script>
