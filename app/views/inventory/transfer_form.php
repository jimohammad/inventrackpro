<style>
.tf-page { max-width: 900px; }
.tf-head { display:flex;align-items:center;gap:12px;margin-bottom:20px; }
.tf-head h1 { font-size:1.2rem;font-weight:700;margin:0; }
.tf-back { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;font-size:.85rem; }
.tf-back:hover { border-color:var(--primary);color:var(--primary); }

.tf-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-bottom:16px; }
.tf-label { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.tf-label i { font-size:.8rem; }
.tf-row { display:grid;gap:12px; }
.tf-row-3 { grid-template-columns:1fr 1fr 1fr; }
.tf-row-2 { grid-template-columns:1fr 1fr; }

.tf-field label { display:block;font-size:.72rem;font-weight:600;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px; }
.tf-field select,.tf-field input,.tf-field textarea {
    width:100%;padding:8px 12px;border:1.5px solid var(--border-color);border-radius:8px;
    font-size:.85rem;background:var(--bg-main);color:var(--text-main);outline:none;font-family:inherit;
}
.tf-field select:focus,.tf-field input:focus { border-color:var(--primary); }

.tf-items-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:10px; }
.tf-items-head span { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px; }
.tf-count { font-size:.72rem;color:var(--text-muted);font-weight:600; }
.tf-tbl { width:100%;border-collapse:collapse; }
.tf-tbl th { font-size:.68rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:6px 8px;border-bottom:1.5px solid var(--border-color); }
.tf-tbl td { padding:6px 8px;border-bottom:1px solid var(--border-color);vertical-align:middle; }
.tf-tbl tr:last-child td { border-bottom:none; }
.tf-tbl .tf-item-select { width:100%;padding:7px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.84rem;background:var(--bg-main);color:var(--text-main);outline:none; }
.tf-tbl .tf-item-select:focus { border-color:var(--primary); }
.tf-tbl .tf-qty { width:70px;padding:7px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.84rem;background:var(--bg-main);color:var(--text-main);outline:none;text-align:center; }
.tf-tbl .tf-qty:focus { border-color:var(--primary); }
.tf-del { width:28px;height:28px;border-radius:6px;border:none;background:rgba(239,68,68,.1);color:#ef4444;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;transition:all .15s; }
.tf-del:hover { background:rgba(239,68,68,.2); }
.tf-row-num { font-size:.75rem;color:var(--text-muted);font-weight:600;text-align:center;width:30px; }

.tf-imei-btn {
    background:rgba(99,102,241,.1);border:1.5px solid rgba(99,102,241,.3);color:var(--primary);
    border-radius:7px;padding:5px 10px;font-size:.75rem;font-weight:600;cursor:pointer;
    display:inline-flex;align-items:center;gap:4px;white-space:nowrap;transition:all .15s;
}
.tf-imei-btn:hover { background:rgba(99,102,241,.2); }
.tf-imei-btn.done { background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.4);color:#16a34a; }
.tf-imei-btn.partial { background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.4);color:#d97706; }

.tf-actions { display:flex;flex-direction:column;gap:8px; }
.tf-btn-submit { padding:10px 20px;background:var(--primary);border:none;color:#fff;border-radius:10px;font-size:.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px; }
.tf-btn-submit:hover { opacity:.9; }
.tf-btn-cancel { padding:10px 20px;background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:10px;font-size:.85rem;cursor:pointer;text-align:center;text-decoration:none; }
.tf-btn-cancel:hover { border-color:var(--primary);color:var(--primary); }

/* IMEI MODAL */
.tim-overlay { position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px); }
.tim-overlay.show { display:flex; }
.tim-box { background:var(--bg-card);border-radius:14px;padding:22px 26px;width:100%;max-width:520px;max-height:90vh;display:flex;flex-direction:column;border:1px solid var(--border-color);box-shadow:0 20px 60px rgba(0,0,0,.2); }
.tim-title { font-size:1rem;font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-start;color:var(--text-main); }
.tim-title small { display:block;font-size:.72rem;color:var(--text-muted);font-weight:400;margin-bottom:3px; }
.tim-close-x { background:none;border:none;font-size:1.4rem;color:var(--text-muted);cursor:pointer;line-height:1;padding:0; }
.tim-meta { display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:.78rem; }
.tim-count { font-weight:700; }
.tim-input-row { display:flex;gap:8px;margin-bottom:6px; }
.tim-input-row input { flex:1;border:2px solid var(--border-color);border-radius:8px;padding:9px 12px;font-size:.92rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);color:var(--text-main);outline:none; }
.tim-input-row input:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.tim-confirm { background:linear-gradient(135deg,var(--primary),#4f46e5);border:none;color:#fff;border-radius:8px;width:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem; }
.tim-paste-btn { background:rgba(99,102,241,.12);color:var(--primary);border:none;border-radius:8px;padding:9px 12px;cursor:pointer;font-size:.78rem;font-weight:600;white-space:nowrap; }
.tim-msg { font-size:.78rem;padding:5px 9px;border-radius:6px;margin:5px 0;min-height:24px; }
.tim-msg.ok { background:#d1fae5;color:#065f46; }
.tim-msg.err { background:#fee2e2;color:#991b1b; }
.tim-tag { display:inline-flex;align-items:center;gap:5px;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.25);border-radius:6px;padding:4px 10px;margin:3px;font-size:.78rem;color:var(--primary);font-family:monospace; }
.tim-tag .x { cursor:pointer;color:var(--text-muted);font-weight:700; }
.tim-tag .x:hover { color:#ef4444; }
.tim-list { flex:1;overflow-y:auto;min-height:60px;max-height:240px;padding:4px 2px; }
.tim-paste-area { display:none;margin-top:8px; }
.tim-paste-area textarea { width:100%;border:2px solid rgba(99,102,241,.3);border-radius:8px;padding:10px;font-size:.82rem;font-family:monospace;resize:vertical;background:var(--bg-main);color:var(--text-main);outline:none; }
.tim-foot { display:flex;justify-content:flex-end;gap:10px;margin-top:14px; }
.tim-btn-cancel { background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);padding:7px 16px;border-radius:8px;cursor:pointer;font-weight:500; }
.tim-btn-save { background:linear-gradient(135deg,var(--primary),#4f46e5);border:none;color:#fff;padding:7px 22px;border-radius:8px;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(99,102,241,.3); }
</style>

<div class="tf-page">
    <div class="tf-head">
        <a href="?page=transfers" class="tf-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-arrow-left-right me-2" style="color:var(--primary);"></i>New Stock Transfer</h1>
    </div>

    <form method="POST" action="?page=transfers&action=store" id="transferForm">
        <?= Auth::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-9">
                <div class="tf-card">
                    <div class="tf-label"><i class="bi bi-arrow-left-right"></i> Transfer Details</div>
                    <div class="tf-row tf-row-3" style="margin-bottom:12px;">
                        <div class="tf-field">
                            <label>From Warehouse *</label>
                            <select name="from_warehouse_id" id="fromWh" required onchange="onWarehouseChange()">
                                <option value="">Select...</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= Auth::warehouseId() == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tf-field">
                            <label>To Warehouse *</label>
                            <select name="to_warehouse_id" id="toWh" required>
                                <option value="">Select...</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= Auth::warehouseId() != $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tf-field">
                            <label>Date</label>
                            <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="tf-field">
                        <label>Notes</label>
                        <input type="text" name="notes" placeholder="Optional transfer notes...">
                    </div>
                </div>

                <div class="tf-card">
                    <div class="tf-items-head">
                        <span><i class="bi bi-box-seam me-1"></i> Items to Transfer</span>
                        <span class="tf-count" id="itemCount">0 items</span>
                    </div>
                    <table class="tf-tbl">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Item</th>
                                <th style="width:80px;">Qty</th>
                                <th style="width:130px;">IMEI</th>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="transferBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-3">
                <div class="tf-card">
                    <div class="tf-actions">
                        <button type="submit" class="tf-btn-submit"><i class="bi bi-check-lg"></i> Transfer</button>
                        <a href="?page=transfers" class="tf-btn-cancel">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- IMEI Scan Modal -->
<div class="tim-overlay" id="timModal">
    <div class="tim-box">
        <div class="tim-title">
            <div>
                <small>Transfer IMEIs for:</small>
                <span id="timItemName" style="color:var(--primary);"></span>
            </div>
            <button type="button" class="tim-close-x" onclick="timClose()">&times;</button>
        </div>
        <div class="tim-meta">
            <span id="timCount" style="color:#f59e0b;">0 / 0</span>
            <span style="color:var(--text-muted);font-size:.7rem;">Source: <strong id="timFromName" style="color:var(--text-main);"></strong></span>
        </div>
        <div class="tim-input-row">
            <input type="text" id="timInput" placeholder="Scan or type IMEI" autocomplete="off"
                   oninput="timAutoTrigger()"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();timConfirm();}">
            <button type="button" class="tim-confirm" onclick="timConfirm()"><i class="bi bi-check-lg"></i></button>
            <button type="button" class="tim-paste-btn" onclick="timTogglePaste()"><i class="bi bi-clipboard-plus"></i> Paste</button>
        </div>
        <div class="tim-paste-area" id="timPasteArea">
            <textarea id="timPasteInput" rows="4" placeholder="Paste IMEIs — one per line"></textarea>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <button type="button" onclick="timProcessPaste()" style="background:var(--primary);color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:.78rem;font-weight:600;cursor:pointer;">Import All</button>
                <button type="button" onclick="timTogglePaste()" style="background:transparent;color:var(--text-muted);border:1px solid var(--border-color);border-radius:6px;padding:6px 12px;font-size:.78rem;cursor:pointer;">Cancel</button>
                <span id="timPasteResult" style="font-size:.75rem;align-self:center;"></span>
            </div>
        </div>
        <div id="timMsg"></div>
        <div id="timList" class="tim-list"></div>
        <div class="tim-foot">
            <button type="button" class="tim-btn-cancel" onclick="timClose()">Cancel</button>
            <button type="button" class="tim-btn-save" onclick="timSave()"><i class="bi bi-check-lg"></i> Done</button>
        </div>
    </div>
</div>

<script>
// Item data with has_imei flag
var tfItems = <?= json_encode(array_map(fn($it) => [
    'id'             => (int)$it['id'],
    'name'           => $it['name'],
    'has_imei'       => (int)($it['has_imei'] ?? 0),
    'imei_optional'  => (int)($it['imei_optional'] ?? 0),
    'total_stock'    => (int)$it['total_stock'],
], $items)) ?>;

var tfRow = 0;
var tfImeiData = {};   // { rowId: [imei, imei, ...] }
var currentImeiRow = null;
var activeImeis = [];

function buildItemOptions() {
    var html = '<option value="">Select item...</option>';
    tfItems.forEach(function(it) {
        var label = it.name + ' (Stock: ' + it.total_stock + ')';
        html += '<option value="' + it.id + '" data-has-imei="' + it.has_imei + '" data-imei-optional="' + it.imei_optional + '" data-name="' + it.name.replace(/"/g, '&quot;') + '">' + label + '</option>';
    });
    return html;
}

function addRow() {
    tfRow++;
    var rid = 'r' + tfRow;
    tfImeiData[rid] = [];
    var tr = document.createElement('tr');
    tr.setAttribute('data-row', rid);
    tr.innerHTML =
        '<td class="tf-row-num">' + tfRow + '</td>' +
        '<td><select name="items[' + tfRow + '][item_id]" class="tf-item-select" onchange="onItemSelect(this,\'' + rid + '\')">' + buildItemOptions() + '</select></td>' +
        '<td><input type="number" name="items[' + tfRow + '][quantity]" class="tf-qty" min="1" value="1" oninput="updateImeiBtn(\'' + rid + '\')"></td>' +
        '<td>' +
          '<button type="button" class="tf-imei-btn" id="imeiBtn_' + rid + '" onclick="openImeiModal(\'' + rid + '\')" style="display:none;">' +
            '<i class="bi bi-upc-scan"></i> Scan' +
          '</button>' +
          '<input type="hidden" name="items[' + tfRow + '][imeis]" id="imeiInput_' + rid + '" value="">' +
        '</td>' +
        '<td><button type="button" class="tf-del" onclick="removeRow(this,\'' + rid + '\')" title="Remove"><i class="bi bi-x"></i></button></td>';
    document.getElementById('transferBody').appendChild(tr);
    updateCount();
    return tr;
}

function removeRow(btn, rid) {
    var tbody = document.getElementById('transferBody');
    btn.closest('tr').remove();
    delete tfImeiData[rid];
    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function(r, i) { r.querySelector('.tf-row-num').textContent = i + 1; });
    updateCount();
    if (tbody.querySelectorAll('tr').length === 0) addRow();
}

function onItemSelect(sel, rid) {
    if (!sel.value) {
        document.getElementById('imeiBtn_' + rid).style.display = 'none';
        return;
    }
    var opt = sel.options[sel.selectedIndex];
    var hasImei = opt.dataset.hasImei === '1';
    var imeiOpt = opt.dataset.imeiOptional === '1';
    // Reset IMEIs when item changes
    tfImeiData[rid] = [];
    document.getElementById('imeiInput_' + rid).value = '';

    var btn = document.getElementById('imeiBtn_' + rid);
    if (hasImei && !imeiOpt) {
        btn.style.display = 'inline-flex';
    } else {
        btn.style.display = 'none';
    }
    updateImeiBtn(rid);

    var tbody = document.getElementById('transferBody');
    var lastRow = tbody.querySelectorAll('tr')[tbody.querySelectorAll('tr').length - 1];
    if (sel.closest('tr') === lastRow) addRow();
    updateCount();
}

function updateCount() {
    var rows = document.getElementById('transferBody').querySelectorAll('tr');
    var filled = 0;
    rows.forEach(function(r) {
        if (r.querySelector('select') && r.querySelector('select').value) filled++;
    });
    document.getElementById('itemCount').textContent = filled + ' item' + (filled !== 1 ? 's' : '');
}

function updateImeiBtn(rid) {
    var btn = document.getElementById('imeiBtn_' + rid);
    if (!btn || btn.style.display === 'none') return;
    var qty   = parseInt(document.querySelector('[data-row="' + rid + '"] .tf-qty').value) || 0;
    var count = (tfImeiData[rid] || []).length;
    if (count === 0) {
        btn.className = 'tf-imei-btn';
        btn.innerHTML = '<i class="bi bi-upc-scan"></i> Scan';
    } else if (count === qty) {
        btn.className = 'tf-imei-btn done';
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + count;
    } else {
        btn.className = 'tf-imei-btn partial';
        btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + count + '/' + qty;
    }
}

// IMEI MODAL
function openImeiModal(rid) {
    var fromWh = document.getElementById('fromWh').value;
    if (!fromWh) { alert('Please select source warehouse first.'); return; }

    var sel  = document.querySelector('[data-row="' + rid + '"] select');
    var qty  = parseInt(document.querySelector('[data-row="' + rid + '"] .tf-qty').value) || 0;
    var opt  = sel.options[sel.selectedIndex];
    var name = opt.dataset.name || '';
    var fromName = document.getElementById('fromWh').options[document.getElementById('fromWh').selectedIndex].textContent;

    currentImeiRow = rid;
    activeImeis = (tfImeiData[rid] || []).slice();

    document.getElementById('timItemName').textContent = name;
    document.getElementById('timFromName').textContent = fromName;
    document.getElementById('timInput').value = '';
    document.getElementById('timMsg').innerHTML = '';
    document.getElementById('timPasteArea').style.display = 'none';
    timRender();
    document.getElementById('timModal').classList.add('show');
    setTimeout(function() { document.getElementById('timInput').focus(); }, 80);
}

function timClose() { document.getElementById('timModal').classList.remove('show'); }

function timTogglePaste() {
    var area = document.getElementById('timPasteArea');
    area.style.display = area.style.display === 'none' ? 'block' : 'none';
    if (area.style.display === 'block') document.getElementById('timPasteInput').focus();
}

var _timAutoTimer = null;
function timAutoTrigger() {
    clearTimeout(_timAutoTimer);
    var v = document.getElementById('timInput').value.trim();
    if (v.length >= 13 && /^[A-Z0-9\/\-]+$/i.test(v)) {
        _timAutoTimer = setTimeout(timConfirm, 150);
    }
}

function timConfirm() {
    clearTimeout(_timAutoTimer);
    var input = document.getElementById('timInput');
    var imei  = input.value.trim().toUpperCase();
    input.value = '';
    if (!imei) return;
    if (!/^[A-Z0-9\/\-]+$/.test(imei)) { timMsg('Invalid characters', 'err'); input.focus(); return; }
    if (activeImeis.indexOf(imei) !== -1) { timMsg('Duplicate', 'err'); input.focus(); return; }

    var sel    = document.querySelector('[data-row="' + currentImeiRow + '"] select');
    var itemId = parseInt(sel.value);
    var fromWh = parseInt(document.getElementById('fromWh').value);

    fetch('?page=transfers&action=validateImei&imei=' + encodeURIComponent(imei) + '&item_id=' + itemId + '&warehouse_id=' + fromWh)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) { timMsg(d.msg, 'err'); input.focus(); return; }
            activeImeis.push(imei);
            timRender();
            timMsg('✓ ' + imei, 'ok');
            input.focus();
        })
        .catch(function() { timMsg('Network error', 'err'); input.focus(); });
}

function timProcessPaste() {
    var raw = document.getElementById('timPasteInput').value;
    var list = raw.split(/[\n,;\s\t]+/).map(function(s) { return s.trim().toUpperCase(); }).filter(Boolean);
    var sel    = document.querySelector('[data-row="' + currentImeiRow + '"] select');
    var itemId = parseInt(sel.value);
    var fromWh = parseInt(document.getElementById('fromWh').value);
    var added = 0, skipped = 0, invalid = 0;
    var unique = [];
    list.forEach(function(im) {
        if (!/^[A-Z0-9\/\-]+$/.test(im)) { invalid++; return; }
        if (activeImeis.indexOf(im) !== -1) { skipped++; return; }
        if (unique.indexOf(im) !== -1) { skipped++; return; }
        unique.push(im);
    });
    if (unique.length === 0) {
        document.getElementById('timPasteResult').textContent = invalid + ' invalid, ' + skipped + ' duplicate';
        document.getElementById('timPasteResult').style.color = '#ef4444';
        return;
    }
    // Validate each via batch (sequential to avoid hammering)
    var i = 0;
    function nextOne() {
        if (i >= unique.length) {
            document.getElementById('timPasteResult').textContent = added + ' added, ' + (skipped + (unique.length - added)) + ' skipped';
            document.getElementById('timPasteResult').style.color = '#16a34a';
            document.getElementById('timPasteInput').value = '';
            timRender();
            return;
        }
        var im = unique[i++];
        fetch('?page=transfers&action=validateImei&imei=' + encodeURIComponent(im) + '&item_id=' + itemId + '&warehouse_id=' + fromWh)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.ok) { activeImeis.push(im); added++; }
                nextOne();
            })
            .catch(function() { nextOne(); });
    }
    nextOne();
}

function timMsg(txt, type) {
    var el = document.getElementById('timMsg');
    el.innerHTML = '<div class="tim-msg ' + type + '">' + txt + '</div>';
    if (type === 'ok') setTimeout(function() { el.innerHTML = ''; }, 1800);
}

function timRender() {
    var qty = parseInt(document.querySelector('[data-row="' + currentImeiRow + '"] .tf-qty').value) || 0;
    var n = activeImeis.length;
    var c = document.getElementById('timCount');
    c.textContent = n + ' / ' + qty;
    c.style.color = (n === qty && qty > 0) ? '#16a34a' : '#f59e0b';

    document.getElementById('timList').innerHTML = activeImeis.map(function(im, idx) {
        return '<span class="tim-tag">' + im + ' <span class="x" onclick="timRemove(' + idx + ')">&times;</span></span>';
    }).join('');
}

function timRemove(idx) { activeImeis.splice(idx, 1); timRender(); }

function timSave() {
    if (!currentImeiRow) return;
    var qty = parseInt(document.querySelector('[data-row="' + currentImeiRow + '"] .tf-qty').value) || 0;
    if (qty > 0 && activeImeis.length !== qty) {
        if (!confirm('IMEI count (' + activeImeis.length + ') does not match qty (' + qty + '). Save anyway? (Submit will be blocked until match.)')) return;
    }
    tfImeiData[currentImeiRow] = activeImeis.slice();
    document.getElementById('imeiInput_' + currentImeiRow).value = activeImeis.join('\n');
    updateImeiBtn(currentImeiRow);
    timClose();
}

function onWarehouseChange() {
    // Clear all IMEIs when source changes — they'd be invalid
    Object.keys(tfImeiData).forEach(function(rid) {
        tfImeiData[rid] = [];
        var inp = document.getElementById('imeiInput_' + rid);
        if (inp) inp.value = '';
        updateImeiBtn(rid);
    });
}

// Submit guard
document.getElementById('transferForm').addEventListener('submit', function(e) {
    var rows = document.getElementById('transferBody').querySelectorAll('tr');
    var hasError = false;
    rows.forEach(function(r) {
        var sel = r.querySelector('select');
        if (sel && !sel.value) { r.remove(); return; }
        var rid     = r.getAttribute('data-row');
        var opt     = sel.options[sel.selectedIndex];
        var hasImei = opt.dataset.hasImei === '1';
        var imeiOpt = opt.dataset.imeiOptional === '1';
        var qty     = parseInt(r.querySelector('.tf-qty').value) || 0;
        var imeis   = (tfImeiData[rid] || []).length;
        if (hasImei && !imeiOpt && imeis !== qty) {
            hasError = true;
            alert('Item "' + opt.dataset.name + '": must scan ' + qty + ' IMEIs (currently ' + imeis + ').');
        }
    });
    if (hasError) { e.preventDefault(); return; }
    if (document.getElementById('transferBody').querySelectorAll('tr').length === 0) {
        e.preventDefault();
        alert('Please add at least one item to transfer.');
    }
});

addRow();
</script>
