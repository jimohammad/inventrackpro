<style>
.ai-page { max-width: 1100px; }
.ai-head { display:flex;align-items:center;gap:14px;margin-bottom:18px; }
.ai-back { width:34px;height:34px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none; }
.ai-back:hover { border-color:var(--primary);color:var(--primary); }
.ai-head h1 { font-size:1.18rem;font-weight:700;margin:0; }
.ai-head .ai-inv { color:var(--primary);font-family:monospace; }

.ai-info { background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1.5px solid #6ee7b7;border-radius:10px;padding:11px 16px;margin-bottom:14px;display:flex;gap:18px;flex-wrap:wrap;font-size:.84rem; }
.ai-info b { color:#065f46;font-weight:700; }
.ai-info span { color:#0f766e; }

.ai-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:visible; }
.ai-card-head { padding:11px 18px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;font-size:.78rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:.5px;display:flex;justify-content:space-between;align-items:center; }
.ai-tbl { width:100%;border-collapse:collapse;font-size:.84rem; }
.ai-tbl th { padding:9px 10px;font-size:.7rem;font-weight:700;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:left; }
.ai-tbl td { padding:5px 6px;border-bottom:1px solid #cbd5e1;vertical-align:middle; }
.ai-tbl tr:hover { background:#f8faff; }
.ai-tbl input, .ai-tbl select { width:100%;padding:6px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;background:#fafbff;color:#1e293b;outline:none; }
.ai-tbl input:focus { border-color:#6366f1;background:#fff; }
.ai-col-num { width:32px;text-align:center;color:#94a3b8; }
.ai-col-qty { width:75px; }
.ai-col-imei { width:110px;text-align:center; }
.ai-col-price { width:120px; }
.ai-col-amt { width:120px;text-align:right;font-weight:700;color:#4338ca; }
.ai-col-act { width:36px;text-align:center; }

.ai-imei-btn { background:linear-gradient(135deg,#eff6ff,#e0e7ff);border:1px solid #c7d2fe;color:#6366f1;border-radius:7px;padding:5px 10px;font-size:.74rem;cursor:pointer;display:inline-flex;align-items:center;gap:4px;font-weight:600;white-space:nowrap; }
.ai-imei-btn:hover { background:linear-gradient(135deg,#e0e7ff,#c7d2fe); }
.ai-imei-btn.has-imei { background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-color:#6ee7b7;color:#059669; }

.ai-actions { display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#f8fafc;border-top:1px solid #e0e7ff; }
.ai-totals { font-size:.92rem; }
.ai-totals .lbl { color:var(--text-muted); }
.ai-totals .val { font-weight:800;color:#4338ca;margin-left:6px; }
.ai-btn-cancel { padding:8px 18px;background:transparent;border:1.5px solid #e2e8f0;color:var(--text-muted);border-radius:8px;text-decoration:none;font-size:.85rem; }
.ai-btn-save { padding:8px 22px;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(59,130,246,.4); }

/* Item search dropdown */
.ai-drop { position:absolute;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,.12);max-height:280px;overflow-y:auto;min-width:380px; }
.ai-drop-item { padding:9px 14px;cursor:pointer;font-size:.85rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:12px; }
.ai-drop-item:hover { background:#f0f4ff; }

/* IMEI Modal (reused from create.php) */
.imei-modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px); }
.imei-modal-overlay.show { display:flex; }
.imei-modal { background:#fff;border-radius:14px;padding:22px 26px;width:100%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;border:1px solid #e0e7ff;box-shadow:0 20px 60px rgba(0,0,0,.2); }
.imei-modal-title { font-size:1rem;font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-start; }
.imei-modal-title small { font-size:.72rem;color:#94a3b8;font-weight:400;display:block;margin-bottom:3px; }
.imei-modal-title .close-x { background:none;border:none;font-size:1.4rem;color:#94a3b8;cursor:pointer;line-height:1;padding:0; }
.imei-input-row { display:flex;gap:8px;align-items:center;margin-bottom:6px; }
.imei-input-row input { flex:1;border:2px solid #e0e7ff;border-radius:8px;padding:9px 12px;font-size:.92rem;color:#1e293b;font-family:monospace;letter-spacing:1px;outline:none; }
.imei-input-row input:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.imei-confirm-btn { background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;border-radius:8px;width:42px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer; }
.imei-msg { font-size:.78rem;padding:5px 9px;border-radius:6px;margin:5px 0;min-height:24px; }
.imei-msg.ok { background:#d1fae5;color:#065f46; }
.imei-msg.err { background:#fee2e2;color:#991b1b; }
.imei-tag { display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:4px 10px;margin:3px;font-size:.78rem;color:#1d4ed8;font-family:monospace; }
.imei-tag .x { cursor:pointer;color:#94a3b8;font-weight:700; }
.imei-tag .x:hover { color:#ef4444; }
.imei-modal-foot { display:flex;justify-content:flex-end;gap:10px;margin-top:14px; }
.imei-paste-btn { background:rgba(99,102,241,.12);color:#6366f1;border:none;border-radius:8px;padding:9px 12px;cursor:pointer;font-size:.78rem;font-weight:600;white-space:nowrap; }
.imei-paste-area { display:none;margin-top:8px; }
.imei-paste-area textarea { width:100%;border:2px solid #c7d2fe;border-radius:8px;padding:10px;font-size:.82rem;font-family:monospace;resize:vertical;background:#fff;color:#1e293b;outline:none; }
</style>

<div class="ai-page">
    <div class="ai-head">
        <a href="?page=sales&action=edit&id=<?= $sale['id'] ?>" class="ai-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-plus-circle me-2" style="color:var(--primary);"></i>Add Item to <span class="ai-inv"><?= htmlspecialchars($sale['invoice_no']) ?></span></h1>
    </div>

    <div class="ai-info">
        <span><b>Customer:</b> <?= htmlspecialchars($sale['party_name']) ?></span>
        <span><b>Warehouse:</b> <?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?></span>
        <span><b>Date:</b> <?= date('d M Y', strtotime($sale['date'])) ?></span>
        <span><b>Current Total:</b> <?= APP_CURRENCY ?> <?= number_format($sale['grand_total'], DECIMAL_PLACES) ?></span>
    </div>

    <form method="POST" action="?page=sales&action=addItemStore" id="addItemForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= $sale['id'] ?>">

        <div class="ai-card">
            <div class="ai-card-head">
                <span><i class="bi bi-box-seam me-1"></i> New Items</span>
                <span style="font-size:.72rem;color:#94a3b8;font-weight:500;text-transform:none;letter-spacing:0;">Scanner-friendly. IMEI items must scan all serials before save.</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="ai-tbl" id="itemsTable">
                    <thead>
                        <tr>
                            <th class="ai-col-num">#</th>
                            <th>Item</th>
                            <th class="ai-col-qty">Qty</th>
                            <th class="ai-col-imei">IMEI</th>
                            <th class="ai-col-price">Unit Price</th>
                            <th class="ai-col-amt">Amount</th>
                            <th class="ai-col-act"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
            <div class="ai-actions">
                <div class="ai-totals">
                    <span class="lbl">Total to add:</span>
                    <span class="val" id="aiTotalDisplay"><?= APP_CURRENCY ?> 0.000</span>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="?page=sales&action=edit&id=<?= $sale['id'] ?>" class="ai-btn-cancel">Cancel</a>
                    <button type="submit" class="ai-btn-save"><i class="bi bi-check-lg"></i> Add Items & Update Invoice</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- IMEI Modal -->
<div class="imei-modal-overlay" id="imeiModal">
    <div class="imei-modal">
        <div class="imei-modal-title">
            <div>
                <small>Scanning IMEI for:</small>
                <div id="imeiModalItemName" style="color:#1e3a5f;"></div>
            </div>
            <button type="button" class="close-x" onclick="closeImeiModal()">&times;</button>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label style="font-size:.83rem;color:#475569;font-weight:600;">IMEI / Serial</label>
            <span id="imeiCount" style="font-size:.8rem;"></span>
        </div>
        <div class="imei-input-row">
            <input type="text" id="imeiScanInput" placeholder="Scan or type IMEI..." maxlength="18"
                   oninput="autoTriggerImei()" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmImei();}">
            <button type="button" class="imei-confirm-btn" onclick="confirmImei()"><i class="bi bi-check-lg"></i></button>
            <button type="button" class="imei-paste-btn" onclick="togglePasteMode()"><i class="bi bi-clipboard-plus"></i> Paste</button>
        </div>
        <div class="imei-paste-area" id="imeiPasteBox">
            <textarea id="imeiPasteInput" rows="4" placeholder="Paste IMEIs — one per line..."></textarea>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <button type="button" onclick="processPastedImeis()" style="background:#6366f1;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:.78rem;font-weight:600;cursor:pointer;">Import All</button>
                <button type="button" onclick="togglePasteMode()" style="background:transparent;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;padding:6px 12px;font-size:.78rem;cursor:pointer;">Cancel</button>
                <span id="pasteResult" style="font-size:.75rem;align-self:center;"></span>
            </div>
        </div>
        <div id="imeiMsg"></div>
        <div id="imeiTagList" style="flex:1;overflow-y:auto;min-height:60px;max-height:240px;padding:4px 2px;"></div>
        <div class="imei-modal-foot">
            <button type="button" onclick="closeImeiModal()" style="background:#f1f5f9;border:1.5px solid #e2e8f0;color:#64748b;padding:7px 18px;border-radius:8px;cursor:pointer;font-weight:500;">Cancel</button>
            <button type="button" onclick="saveImeiModal()" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;padding:7px 22px;border-radius:8px;font-weight:700;cursor:pointer;"><i class="bi bi-check-lg"></i> Done</button>
        </div>
    </div>
</div>

<script>
var saleId       = <?= (int)$sale['id'] ?>;
var warehouseId  = <?= (int)$sale['warehouse_id'] ?>;
var rowCount     = 0;
var imeiData     = {};
var currentImeiRow = null;
var activeImeis  = [];
var currentItemName = '';
window.rowItemNameMap = {};
window.rowCategoryMap = {};

document.addEventListener('DOMContentLoaded', function() { addRow(); });

// ROWS
function addRow() {
    rowCount++;
    var rid = 'row_' + rowCount;
    imeiData[rid] = [];
    var tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML =
        '<td class="ai-col-num">' + rowCount + '</td>' +
        '<td style="position:relative;">' +
            '<input type="text" class="ai-item-search" placeholder="Search item..." data-row="' + rid + '" autocomplete="off" oninput="searchItem(this,\'' + rid + '\')">' +
            '<input type="hidden" name="items[' + rowCount + '][item_id]" id="itemId_' + rid + '">' +
            '<input type="hidden" name="items[' + rowCount + '][has_imei]" id="hasImei_' + rid + '" value="0">' +
            '<input type="hidden" name="items[' + rowCount + '][imei_optional]" id="imeiOpt_' + rid + '" value="0">' +
            '<div class="ai-drop" id="drop_' + rid + '" style="display:none;"></div>' +
        '</td>' +
        '<td class="ai-col-qty"><input type="number" name="items[' + rowCount + '][quantity]" id="qty_' + rid + '" value="" min="1" placeholder="1" style="text-align:center;" oninput="calcRow(\'' + rid + '\')"></td>' +
        '<td class="ai-col-imei">' +
            '<button type="button" class="ai-imei-btn" id="imeiBtn_' + rid + '" onclick="openImeiModal(\'' + rid + '\')" style="display:none;"><i class="bi bi-upc-scan"></i></button>' +
            '<input type="hidden" name="items[' + rowCount + '][imeis]" id="imeiInput_' + rid + '" value="">' +
        '</td>' +
        '<td class="ai-col-price"><input type="number" name="items[' + rowCount + '][unit_price]" id="price_' + rid + '" value="" step="0.001" placeholder="0.000" style="text-align:right;" oninput="calcRow(\'' + rid + '\')"></td>' +
        '<td class="ai-col-amt" id="amt_' + rid + '">0.000</td>' +
        '<td class="ai-col-act"><button type="button" onclick="removeRow(\'' + rid + '\')" style="background:none;border:none;color:#fca5a8;cursor:pointer;font-size:1.1rem;">&times;</button></td>';
    document.getElementById('itemsBody').appendChild(tr);
}

function removeRow(rid) {
    document.getElementById(rid)?.remove();
    delete imeiData[rid];
    calcTotals();
    if (document.getElementById('itemsBody').querySelectorAll('tr').length === 0) addRow();
}

// ITEM SEARCH
var searchTimers = {};
var itemStore = {};
function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    var q = input.value.trim();
    var drop = document.getElementById('drop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers[rid] = setTimeout(function() {
        fetch('?page=sales&action=searchItems&q=' + encodeURIComponent(q) + '&warehouse_id=' + warehouseId)
            .then(function(r) { return r.json(); })
            .then(function(items) {
                if (!items.length) { drop.style.display = 'none'; return; }
                itemStore[rid] = items;
                drop.innerHTML = items.map(function(it, idx) {
                    return '<div class="ai-drop-item" data-rid="' + rid + '" data-idx="' + idx + '">' +
                           '<div style="flex:1;min-width:0;"><strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + it.name + '</strong>' +
                           '<small style="color:#94a3b8;">' + (it.sku || '') + (it.has_imei ? ' · <span style="color:#059669;font-weight:600;">IMEI</span>' : '') + '</small></div>' +
                           '<div style="text-align:right;white-space:nowrap;flex-shrink:0;"><span style="font-weight:700;color:#4338ca;">' + it.sale_price + '</span><br><small style="color:#3b82f6;font-weight:600;">Stock: ' + it.stock + '</small></div></div>';
                }).join('');
                drop.querySelectorAll('.ai-drop-item').forEach(function(el) {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectItem(el.dataset.rid, itemStore[el.dataset.rid][parseInt(el.dataset.idx)]);
                    });
                });
                var rect = input.getBoundingClientRect();
                drop.style.position = 'fixed';
                drop.style.top  = (rect.bottom + 4) + 'px';
                drop.style.left = rect.left + 'px';
                drop.style.minWidth = Math.max(380, rect.width) + 'px';
                drop.style.display = 'block';
            });
    }, 250);
}

function selectItem(rid, item) {
    document.querySelector('#' + rid + ' .ai-item-search').value = item.name;
    document.getElementById('itemId_'  + rid).value = item.id;
    document.getElementById('hasImei_' + rid).value = item.has_imei;
    document.getElementById('imeiOpt_' + rid).value = item.imei_optional || 0;
    document.getElementById('price_'   + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('drop_'    + rid).style.display = 'none';

    var qtyEl = document.getElementById('qty_' + rid);
    if (qtyEl && !qtyEl.value) qtyEl.value = 1;

    window.rowItemNameMap[rid] = (item.name || '').toLowerCase();
    window.rowCategoryMap[rid] = (item.category_name || '').toLowerCase();

    var btn = document.getElementById('imeiBtn_' + rid);
    if (parseInt(item.has_imei) && !parseInt(item.imei_optional || 0)) {
        btn.style.display = 'inline-flex';
        btn.innerHTML = '<i class="bi bi-upc-scan"></i> Scan';
    } else {
        btn.style.display = 'none';
    }
    calcRow(rid);

    // Auto-add next row if last
    var rows = document.querySelectorAll('#itemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addRow();

    setTimeout(function() { if (parseInt(item.has_imei) && !parseInt(item.imei_optional || 0)) openImeiModal(rid, item.name); }, 120);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('td') && !e.target.closest('.ai-drop')) {
        document.querySelectorAll('.ai-drop').forEach(function(d) { d.style.display = 'none'; });
    }
});

// CALC
function calcRow(rid) {
    var qty   = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
    var price = parseFloat(document.getElementById('price_' + rid)?.value) || 0;
    document.getElementById('amt_' + rid).textContent = (qty * price).toFixed(3);
    calcTotals();
}

function calcTotals() {
    var subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        var rid = tr.dataset.rowId; if (!rid) return;
        var qty   = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
        var price = parseFloat(document.getElementById('price_' + rid)?.value) || 0;
        subtotal += qty * price;
    });
    document.getElementById('aiTotalDisplay').textContent = '<?= APP_CURRENCY ?> ' + subtotal.toFixed(3);
}

// IMEI MODAL
function getImeiRule(row) {
    var name     = (window.rowItemNameMap && window.rowItemNameMap[row]) || '';
    var category = (window.rowCategoryMap && window.rowCategoryMap[row]) || '';
    if (name.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13' };
    if (category.indexOf('bud') !== -1) return { min: 15, max: 18, label: '15-18' };
    return { min: 15, max: 15, label: '15' };
}

function openImeiModal(rid, itemName) {
    currentImeiRow = rid;
    currentItemName = itemName || document.querySelector('#' + rid + ' .ai-item-search')?.value || 'Item';
    activeImeis = (imeiData[rid] || []).slice();
    document.getElementById('imeiModalItemName').textContent = currentItemName;
    document.getElementById('imeiScanInput').value = '';
    document.getElementById('imeiMsg').innerHTML = '';
    document.getElementById('imeiPasteBox').style.display = 'none';
    renderImeiTags();
    document.getElementById('imeiModal').classList.add('show');
    setTimeout(function() { document.getElementById('imeiScanInput').focus(); }, 80);
}

function closeImeiModal() { document.getElementById('imeiModal').classList.remove('show'); }

function togglePasteMode() {
    var box = document.getElementById('imeiPasteBox');
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
    document.getElementById('pasteResult').textContent = '';
    if (box.style.display === 'block') document.getElementById('imeiPasteInput').focus();
}

function getAllEnteredImeis(excludeRow) {
    var all = [];
    Object.keys(imeiData).forEach(function(r) { if (r !== excludeRow) all.push.apply(all, imeiData[r]); });
    return all;
}

function processPastedImeis() {
    var raw = document.getElementById('imeiPasteInput').value;
    var list = raw.split(/[\n,;\s\t]+/).map(function(s) { return s.trim(); }).filter(Boolean);
    var rule = getImeiRule(currentImeiRow);
    var added = 0, skipped = 0, invalid = 0;
    var allOther = getAllEnteredImeis(currentImeiRow);
    list.forEach(function(imei) {
        if (!/^\d+$/.test(imei)) { invalid++; return; }
        if (imei.length < rule.min || imei.length > rule.max) { invalid++; return; }
        if (activeImeis.indexOf(imei) !== -1) { skipped++; return; }
        if (allOther.indexOf(imei) !== -1) { skipped++; return; }
        activeImeis.push(imei); added++;
    });
    renderImeiTags();
    document.getElementById('pasteResult').textContent = '✓ ' + added + ' added' + (skipped ? ', ' + skipped + ' duplicates' : '') + (invalid ? ', ' + invalid + ' invalid' : '');
    document.getElementById('pasteResult').style.color = added > 0 ? '#059669' : '#ef4444';
    document.getElementById('imeiPasteInput').value = '';
}

var _imeiAutoTimer = null;
function autoTriggerImei() {
    clearTimeout(_imeiAutoTimer);
    var v = document.getElementById('imeiScanInput').value.trim();
    var rule = getImeiRule(currentImeiRow);
    if (v.length >= rule.min && v.length <= rule.max) _imeiAutoTimer = setTimeout(confirmImei, 150);
}

function confirmImei() {
    clearTimeout(_imeiAutoTimer);
    var input = document.getElementById('imeiScanInput');
    var imei  = input.value.trim();
    if (!imei) return;
    input.value = '';
    var rule = getImeiRule(currentImeiRow);
    if (!/^\d+$/.test(imei)) { showImeiMsg('Not digits: ' + imei, 'err'); input.focus(); return; }
    if (imei.length < rule.min || imei.length > rule.max) { showImeiMsg('Invalid length (' + imei.length + '). Need ' + rule.label, 'err'); input.focus(); return; }
    if (activeImeis.indexOf(imei) !== -1) { showImeiMsg('⚠ Duplicate', 'err'); input.focus(); return; }
    if (getAllEnteredImeis(currentImeiRow).indexOf(imei) !== -1) { showImeiMsg('⚠ Used in another row', 'err'); input.focus(); return; }
    activeImeis.push(imei);
    renderImeiTags();
    showImeiMsg('✓ ' + imei, 'ok');
    input.focus();
}

function showImeiMsg(msg, type) {
    var el = document.getElementById('imeiMsg');
    el.innerHTML = '<div class="imei-msg ' + type + '">' + msg + '</div>';
    if (type === 'ok') setTimeout(function() { el.innerHTML = ''; }, 1500);
}

function renderImeiTags() {
    var qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;
    document.getElementById('imeiCount').innerHTML =
        '<span style="color:' + (activeImeis.length >= qty && qty > 0 ? '#059669' : '#f59e0b') + ';font-weight:600;">' + activeImeis.length + ' entered</span>' +
        (qty > 0 ? ' <span style="color:#94a3b8;">/ ' + qty + ' needed</span>' : '');
    document.getElementById('imeiTagList').innerHTML = activeImeis.map(function(im, i) {
        return '<span class="imei-tag">' + im + ' <span class="x" onclick="removeImei(' + i + ')">&times;</span></span>';
    }).join('');
}

function removeImei(idx) { activeImeis.splice(idx, 1); renderImeiTags(); }

function saveImeiModal() {
    if (!currentImeiRow) return;
    var qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;
    if (qty > 0 && activeImeis.length !== qty) {
        if (!confirm(activeImeis.length + ' IMEI(s) entered but quantity is ' + qty + '. Quantity will update to match. Continue?')) return;
    }
    imeiData[currentImeiRow] = activeImeis.slice();
    document.getElementById('imeiInput_' + currentImeiRow).value = activeImeis.join('\n');
    var btn = document.getElementById('imeiBtn_' + currentImeiRow);
    if (btn) {
        btn.classList.toggle('has-imei', activeImeis.length > 0);
        btn.innerHTML = activeImeis.length > 0 ? '<i class="bi bi-upc-scan"></i> ' + activeImeis.length : '<i class="bi bi-upc-scan"></i> Scan';
    }
    var qtyEl = document.getElementById('qty_' + currentImeiRow);
    if (qtyEl && activeImeis.length > 0) { qtyEl.value = activeImeis.length; calcRow(currentImeiRow); }
    closeImeiModal();
}

// Submit guard — block if IMEI items don't have full IMEI list, prevent Enter from submitting
document.getElementById('addItemForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !['submit','hidden','button'].includes(e.target.type)) {
        e.preventDefault();
    }
});

document.getElementById('addItemForm').addEventListener('submit', function(e) {
    var hasItem = false;
    var error = null;
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        var rid = tr.dataset.rowId;
        var itemId = document.getElementById('itemId_' + rid)?.value;
        if (!itemId) return;
        hasItem = true;
        var qty     = parseInt(document.getElementById('qty_' + rid)?.value) || 0;
        var hasImei = document.getElementById('hasImei_' + rid)?.value === '1';
        var imeiOpt = document.getElementById('imeiOpt_' + rid)?.value === '1';
        var imeis   = (imeiData[rid] || []).length;
        if (qty <= 0) error = 'Quantity required for all items.';
        if (hasImei && !imeiOpt && imeis !== qty) {
            var name = tr.querySelector('.ai-item-search')?.value || 'item';
            error = 'Item "' + name + '": must scan ' + qty + ' IMEIs (currently ' + imeis + ').';
        }
    });
    if (!hasItem)  { e.preventDefault(); alert('Please add at least one item.'); return; }
    if (error)     { e.preventDefault(); alert(error); }
});
</script>
