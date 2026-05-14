<style>
.ai-page { max-width: 760px; }
.ai-head { display:flex;align-items:center;gap:12px;margin-bottom:18px; }
.ai-back { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none; }
.ai-back:hover { border-color:var(--primary);color:var(--primary); }
.ai-head h1 { font-size:1.15rem;font-weight:700;margin:0; }

.ai-meta { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px; }
.ai-meta-card { background:var(--bg-card);border:1.5px solid var(--border-color);border-radius:10px;padding:12px 14px;text-align:center; }
.ai-meta-card .label { font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700; }
.ai-meta-card .value { font-size:1.6rem;font-weight:800;font-family:monospace;margin-top:2px; }
.ai-meta-card.stock .value { color:#3b82f6; }
.ai-meta-card.imei  .value { color:#7c3aed; }
.ai-meta-card.scan  .value { color:#16a34a; }

.ai-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:18px;margin-bottom:16px; }
.ai-sep { font-size:.72rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }

.ai-scan { display:flex;gap:8px;margin-bottom:8px; }
.ai-scan input { flex:1;padding:11px 14px;border:2px solid var(--primary);border-radius:10px;font-size:1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);color:var(--text-main);outline:none; }
.ai-scan input:focus { box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.ai-msg { padding:5px 10px;border-radius:6px;font-size:.78rem;min-height:24px;margin-bottom:6px; }
.ai-msg.ok  { background:#d1fae5;color:#065f46; }
.ai-msg.err { background:#fee2e2;color:#991b1b; }

.ai-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;max-height:340px;overflow-y:auto;padding:6px;background:var(--bg-main);border-radius:8px; }
.ai-row { display:flex;justify-content:space-between;align-items:center;padding:7px 10px;border-radius:6px;font-size:.82rem;background:var(--bg-card);border:1px solid var(--border-color); }
.ai-row.scanned { background:#dcfce7;border-color:#86efac; }
.ai-row .imei { font-family:monospace;color:var(--text-main); }
.ai-row .icon { font-size:.85rem; }
.ai-row.scanned .icon { color:#16a34a; }
.ai-row .icon.miss { color:#94a3b8; }

.ai-actions { display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:1.5px solid var(--border-color); }
.ai-warn { font-size:.78rem;color:#dc2626;font-weight:600; }
.ai-warn-ok { font-size:.78rem;color:#16a34a;font-weight:600; }
.ai-btn-cancel { padding:9px 18px;background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:8px;text-decoration:none;font-size:.85rem; }
.ai-btn-submit { padding:9px 22px;background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(99,102,241,.3); }
.ai-btn-submit:disabled { opacity:.5;cursor:not-allowed; }
</style>

<div class="ai-page">
    <div class="ai-head">
        <a href="?page=imei&action=audit" class="ai-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-tools" style="color:var(--primary);"></i> Reconcile: <?= htmlspecialchars($item['name']) ?></h1>
    </div>

    <div class="ai-meta">
        <div class="ai-meta-card stock"><div class="label">Stock Qty</div><div class="value"><?= (int)$item['stock'] ?></div></div>
        <div class="ai-meta-card imei"><div class="label">IMEI Count</div><div class="value"><?= (int)$item['imei_count'] ?></div></div>
        <div class="ai-meta-card scan"><div class="label">Scanned</div><div class="value" id="aiScanCount">0</div></div>
    </div>

    <div class="ai-card">
        <div class="ai-sep"><i class="bi bi-upc-scan"></i> Scan Physical IMEIs Present</div>
        <div class="ai-scan">
            <input type="text" id="aiInput" placeholder="Scan IMEI on phone..." autocomplete="off"
                   oninput="aiAuto()"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();aiScan();}">
        </div>
        <div class="ai-msg" id="aiMsg"></div>
    </div>

    <form method="POST" action="?page=imei&action=auditSubmit" id="aiForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="scanned_imeis" id="aiScannedField" value="">

        <div class="ai-card">
            <div class="ai-sep"><i class="bi bi-list-ul"></i> Currently in Stock (<?= count($imeis) ?>)</div>
            <div class="ai-grid" id="aiList">
                <?php foreach ($imeis as $im): ?>
                <div class="ai-row" id="row_<?= htmlspecialchars($im['imei']) ?>">
                    <span class="imei"><?= htmlspecialchars($im['imei']) ?></span>
                    <i class="bi bi-circle icon miss"></i>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ai-actions">
                <div id="aiSummary" class="ai-warn">No IMEIs scanned yet.</div>
                <div style="display:flex;gap:8px;">
                    <a href="?page=imei&action=audit" class="ai-btn-cancel">Cancel</a>
                    <button type="submit" class="ai-btn-submit" id="aiSubmit" disabled>
                        <i class="bi bi-check-lg"></i> Mark Unscanned as Transferred
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
var aiItemId = <?= (int)$item['id'] ?>;
var aiInStock = <?= json_encode(array_column($imeis, 'imei')) ?>;
var aiScanned = [];
var _aiAutoT = null;

function aiAuto() {
    clearTimeout(_aiAutoT);
    var v = document.getElementById('aiInput').value.trim();
    if (v.length >= 13 && /^[A-Z0-9\/\-]+$/i.test(v)) {
        _aiAutoT = setTimeout(aiScan, 150);
    }
}

function aiScan() {
    clearTimeout(_aiAutoT);
    var input = document.getElementById('aiInput');
    var imei  = input.value.trim().toUpperCase();
    input.value = '';
    if (!imei) return;
    if (aiScanned.indexOf(imei) !== -1) { aiMsg('Already scanned: ' + imei, 'err'); input.focus(); return; }

    fetch('?page=imei&action=auditScan&imei=' + encodeURIComponent(imei) + '&item_id=' + aiItemId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) {
                if (d.code === 'not_found') {
                    aiPromptRegister(imei);
                } else {
                    aiMsg(d.msg + ': ' + imei, 'err');
                    input.focus();
                }
                return;
            }
            aiAcceptScanned(imei);
            input.focus();
        })
        .catch(function() { aiMsg('Network error', 'err'); input.focus(); });
}

function aiAcceptScanned(imei) {
    aiScanned.push(imei);
    var row = document.getElementById('row_' + imei);
    if (row) {
        row.classList.add('scanned');
        row.querySelector('.icon').className = 'bi bi-check-circle-fill icon';
    } else {
        // New IMEI registered inline — add row to list
        var grid = document.getElementById('aiList');
        var div = document.createElement('div');
        div.id = 'row_' + imei;
        div.className = 'ai-row scanned';
        div.innerHTML = '<span class="imei">' + imei + '</span><i class="bi bi-plus-circle-fill icon" title="New IMEI registered"></i>';
        grid.insertBefore(div, grid.firstChild);
        aiInStock.push(imei);
    }
    aiUpdate();
    aiMsg('✓ ' + imei, 'ok');
}

function aiPromptRegister(imei) {
    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg err';
    msgEl.innerHTML =
        'IMEI <strong style="font-family:monospace;">' + imei + '</strong> not in system. ' +
        '<button type="button" onclick="aiDoRegister(\'' + imei + '\')" ' +
        'style="margin-left:8px;background:#16a34a;color:#fff;border:none;border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:700;cursor:pointer;">' +
        '<i class="bi bi-plus-lg"></i> Register Here</button> ' +
        '<button type="button" onclick="aiDismiss()" ' +
        'style="margin-left:4px;background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:5px;padding:3px 10px;font-size:.75rem;cursor:pointer;">Skip</button>';
    document.getElementById('aiInput').focus();
}

function aiDismiss() {
    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg';
    msgEl.innerHTML = '';
    document.getElementById('aiInput').focus();
}

function aiDoRegister(imei) {
    var fd = new FormData();
    fd.append('imei', imei);
    fd.append('item_id', aiItemId);
    fd.append('csrf_token', '<?= Auth::csrfToken() ?>');
    fetch('?page=imei&action=auditRegister', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) { aiMsg(d.msg, 'err'); return; }
            aiAcceptScanned(imei);
            document.getElementById('aiInput').focus();
        })
        .catch(function() { aiMsg('Network error during register', 'err'); });
}

function aiMsg(t, type) {
    var el = document.getElementById('aiMsg');
    el.className = 'ai-msg ' + type;
    el.textContent = t;
    if (type === 'ok') setTimeout(function() { el.textContent = ''; el.className = 'ai-msg'; }, 1500);
}

function aiUpdate() {
    document.getElementById('aiScanCount').textContent = aiScanned.length;
    document.getElementById('aiScannedField').value = aiScanned.join('\n');
    var unscanned = aiInStock.length - aiScanned.length;
    var sum = document.getElementById('aiSummary');
    var btn = document.getElementById('aiSubmit');

    if (aiScanned.length === 0) {
        if (aiInStock.length > 0) {
            sum.className = 'ai-warn';
            sum.textContent = 'None scanned — submit to mark all ' + aiInStock.length + ' IMEI(s) as transferred (e.g. zero on hand).';
            btn.disabled = false;
        } else {
            sum.className = 'ai-warn';
            sum.textContent = 'No IMEIs in this warehouse for this item.';
            btn.disabled = true;
        }
    } else if (unscanned === 0) {
        sum.className = 'ai-warn-ok';
        sum.textContent = '✓ All ' + aiInStock.length + ' IMEIs scanned. No changes needed.';
        btn.disabled = true;
    } else {
        sum.className = 'ai-warn';
        sum.textContent = 'Submit will mark ' + unscanned + ' unscanned IMEI(s) as transferred.';
        btn.disabled = false;
    }
}

document.getElementById('aiForm').addEventListener('submit', function(e) {
    var unscanned = aiInStock.length - aiScanned.length;
    if (unscanned > 0 && !confirm('Mark ' + unscanned + ' unscanned IMEI(s) as transferred? This cannot be undone.')) {
        e.preventDefault();
    }
});

aiUpdate();
setTimeout(function() { document.getElementById('aiInput').focus(); }, 200);
</script>
