<!-- Create Return -->
<style>
.autocomplete-box {
    position: absolute;
    top: 100%; left: 0; right: 0;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    z-index: 999;
    max-height: 220px;
    overflow-y: auto;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.autocomplete-item {
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--text-main);
}
.autocomplete-item:last-child { border-bottom: none; }
.autocomplete-item:hover { background: rgba(99,102,241,0.1); color: var(--primary); }

/* IMEI scan button */
.rimei-btn {
    background: linear-gradient(135deg,#eff6ff,#e0e7ff);
    border: 1px solid #c7d2fe;
    color: #6366f1;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
    box-shadow: 0 1px 3px rgba(99,102,241,0.15);
    white-space: nowrap;
}
.rimei-btn:hover { background: linear-gradient(135deg,#e0e7ff,#c7d2fe); transform: translateY(-1px); }
.rimei-btn.has-imei { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border-color:#6ee7b7; color:#059669; }

/* IMEI Modal */
.rimei-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9999;
    display: none; align-items: center; justify-content: center;
}
.rimei-overlay.open { display: flex; }
.rimei-modal {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 24px;
    width: 100%; max-width: 480px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.rimei-modal-title {
    font-size: 1rem; font-weight: 700;
    color: var(--text-main);
    margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: center;
}
.rimei-modal-title button {
    background: none; border: none;
    color: var(--text-muted); font-size: 1.4rem;
    cursor: pointer; line-height: 1;
}
.rimei-input-wrap { position: relative; margin-bottom: 10px; }
.rimei-input-wrap input {
    width: 100%;
    background: var(--bg-main);
    border: 1.5px solid var(--border-color);
    color: var(--text-main);
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 0.95rem;
    font-family: monospace;
    letter-spacing: 2px;
    outline: none;
    transition: border-color 0.15s;
}
.rimei-input-wrap input:focus { border-color: #6366f1; }
.rimei-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; min-height: 28px; }
.rimei-tag {
    background: #eff6ff; border: 1px solid #bfdbfe;
    color: #1d4ed8; border-radius: 5px;
    padding: 3px 8px; font-size: 0.78rem;
    font-family: monospace; letter-spacing: 1px;
    display: inline-flex; align-items: center; gap: 5px;
}
.rimei-tag .rm { cursor: pointer; color: #6b7280; font-size: 0.9rem; line-height:1; }
.rimei-tag .rm:hover { color: #dc2626; }
.rimei-counter {
    font-size: 0.8rem; font-weight: 600; margin-bottom: 12px;
}
.rimei-msg { font-size: 0.8rem; padding: 5px 0; min-height: 20px; }
.rimei-msg.err { color: #dc2626; }
.rimei-msg.ok  { color: #059669; }
.rimei-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
.rimei-footer button {
    padding: 7px 20px; border-radius: 8px;
    font-size: 0.875rem; font-weight: 600; cursor: pointer; border: none;
}
.rimei-cancel { background: transparent; border: 1px solid var(--border-color) !important; color: var(--text-muted); }
.rimei-done   { background: #6366f1; color: #fff; }

/* Override Bootstrap table header */
.ret-table thead tr,
.ret-table thead tr th {
    background: #1e3a5f !important;
    color: #fff !important;
    border: none !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
    padding: 9px 10px !important;
}</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=returns" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">New Sale Return</h1>
</div>


<form method="POST" action="?page=returns&action=store">
<div class="row g-3">

    <!-- LEFT: Items Table (main area) -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center" style="font-weight:700;">
                <i class="bi bi-box-seam me-2" style="color:var(--primary);"></i> Return Items
            </div>
            <div class="card-body p-0">
                <table class="ret-table" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="background-color:#1e3a5f;">
                            <th style="width:36px;color:#fff;background-color:#1e3a5f;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:9px 10px;border:none;">#</th>
                            <th style="color:#fff;background-color:#1e3a5f;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:9px 10px;border:none;">ITEM</th>
                            <th style="width:44px;color:#fff;background-color:#1e3a5f;border:none;padding:9px 6px;"></th>
                            <th style="width:80px;color:#fff;background-color:#1e3a5f;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:9px 10px;border:none;text-align:center;">QTY</th>
                            <th style="width:120px;color:#fff;background-color:#1e3a5f;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:9px 10px;border:none;text-align:right;">UNIT PRICE</th>
                            <th style="width:110px;color:#fff;background-color:#1e3a5f;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:9px 10px;border:none;text-align:right;">TOTAL</th>
                            <th style="width:36px;color:#fff;background-color:#1e3a5f;border:none;padding:9px 6px;"></th>
                        </tr>
                    </thead>
                    <tbody id="returnItemsBody"></tbody>
                </table>

                <!-- Add Row Strip -->
                <div onclick="addReturnRow()" id="addRowStrip"
                     style="display:flex;align-items:center;justify-content:center;gap:8px;
                            padding:11px 16px;cursor:pointer;
                            border-top:1px dashed var(--border-color);
                            color:var(--text-muted);font-size:0.85rem;font-weight:600;
                            transition:all 0.15s;user-select:none;"
                     onmouseover="this.style.background='rgba(99,102,241,0.06)';this.style.color='#6366f1';"
                     onmouseout="this.style.background='';this.style.color='var(--text-muted)';">
                    <span style="width:22px;height:22px;border-radius:50%;
                                 background:rgba(99,102,241,0.12);
                                 display:inline-flex;align-items:center;justify-content:center;
                                 font-size:1rem;color:#6366f1;line-height:1;">+</span>
                    Add Item Row
                </div>

                <table class="table mb-0" style="border-top:2px solid var(--border-color);">
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold" style="padding:10px 16px;">Total</td>
                            <td class="fw-bold" id="returnTotal" style="padding:10px 0;">0.000</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT: Details + Actions -->
    <div class="col-md-4">

        <!-- Customer & Date Fields -->
        <div class="card mb-3">
            <div class="card-header" style="font-weight:700;font-size:0.875rem;">
                <i class="bi bi-person-circle me-2" style="color:var(--primary);"></i>Return Details
            </div>
            <div class="card-body" style="padding:14px;">
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.8rem;font-weight:600;">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" class="form-select select2-party" required>
                        <option value="">Select customer...</option>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.8rem;font-weight:600;">Warehouse</label>
                    <input type="hidden" name="warehouse_id" value="<?= Auth::warehouseId() ?>">
                    <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:7px 12px;color:#10b981;font-size:0.82rem;font-weight:600;">
                        <i class="bi bi-building me-1"></i><?= htmlspecialchars(Auth::warehouseName()) ?>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label" style="font-size:0.8rem;font-weight:600;">Return Date</label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                <input type="hidden" name="print_mode" id="retPrintMode" value="0">
                <button type="submit" class="btn btn-primary w-100 mb-2"
                        onclick="document.getElementById('retPrintMode').value='0'">
                    <i class="bi bi-check-lg me-1"></i> Save Return
                </button>
                <button type="submit" class="btn w-100 mb-2"
                        style="background:#059669;border:none;color:#fff;font-weight:600;"
                        onclick="document.getElementById('retPrintMode').value='1'">
                    <i class="bi bi-printer me-1"></i> Print & Save
                </button>
                <a href="?page=returns" class="btn btn-outline-secondary w-100">Cancel</a>
            </div>
        </div>
    </div>

</div>
</form>

<!-- IMEI Modal -->
<div class="rimei-overlay" id="retImeiOverlay">
    <div class="rimei-modal">
        <div class="rimei-modal-title">
            <span><i class="bi bi-upc-scan me-2" style="color:#6366f1;"></i><span id="retImeiTitle">Scan IMEI</span></span>
            <button onclick="closeRetImei()">×</button>
        </div>
        <div class="rimei-input-wrap">
            <input type="text" id="retImeiInput" maxlength="15" placeholder="Scan or type 15-digit IMEI...">
        </div>
        <div class="rimei-msg" id="retImeiMsg"></div>
        <div class="rimei-tags" id="retImeiTags"></div>
        <div class="rimei-counter" id="retImeiCounter"></div>
        <div class="rimei-footer">
            <button class="rimei-cancel" onclick="closeRetImei()">Cancel</button>
            <button class="rimei-done" onclick="confirmRetImei()"><i class="bi bi-check-lg me-1"></i> Done</button>
        </div>
    </div>
</div>

<script>
let returnRowCount = 0;
let retImeiData   = {};   // { rid: [imei, imei, ...] }
let retCurrentRow = null;

// ── Add Row ──
function addReturnRow(item = null) {
    returnRowCount++;
    const rid = 'rrow_' + returnRowCount;
    retImeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid;
    tr.innerHTML = `
        <td style="color:var(--text-muted);font-weight:600;font-size:0.85rem;text-align:center;">${returnRowCount}</td>
        <td style="position:relative;">
            <input type="text" class="form-control form-control-sm" placeholder="Item name..."
                   id="rSearch_${rid}" value="${item ? item.item_name : ''}" oninput="searchReturnItem(this,'${rid}')">
            <input type="hidden" name="items[${returnRowCount}][item_id]" id="rItemId_${rid}" value="${item ? item.item_id : ''}">
            <div class="autocomplete-box" id="rDrop_${rid}" style="display:none;"></div>
        </td>
        <td>
            <button type="button" class="rimei-btn" id="retImeiBtn_${rid}" onclick="openRetImei('${rid}')">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td><input type="number" name="items[${returnRowCount}][quantity]" id="rQty_${rid}"
                class="form-control form-control-sm"
                value="${item ? item.quantity : 1}" min="1" oninput="calcReturnRow('${rid}')"></td>
        <td><input type="number" name="items[${returnRowCount}][unit_price]" id="rPrice_${rid}"
                class="form-control form-control-sm" step="0.001"
                value="${item ? item.unit_price : ''}" oninput="calcReturnRow('${rid}')"></td>
        <td id="rAmt_${rid}" class="fw-semibold">0.000</td>
        <td>
            <input type="hidden" name="items[${returnRowCount}][imeis]" id="rImei_${rid}">
            <button type="button" class="btn btn-sm" style="color:#dc2626;background:none;border:none;font-size:1rem;padding:2px 6px;"
                    onclick="this.closest('tr').remove();calcReturnTotal()">×</button>
        </td>
    `;
    document.getElementById('returnItemsBody').appendChild(tr);
    if (item) calcReturnRow(rid);
}

// ── Calc ──
function calcReturnRow(rid) {
    const qty   = parseFloat(document.getElementById('rQty_'   + rid)?.value) || 0;
    const price = parseFloat(document.getElementById('rPrice_' + rid)?.value) || 0;
    document.getElementById('rAmt_' + rid).textContent = (qty * price).toFixed(3);
    calcReturnTotal();
}

function calcReturnTotal() {
    let total = 0;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const amt = parseFloat(tr.querySelector('[id^="rAmt_"]')?.textContent) || 0;
        total += amt;
    });
    document.getElementById('returnTotal').textContent = total.toFixed(3);
}

// ── Autocomplete ──
const returnItemStore = {};
function searchReturnItem(input, rid) {
    const q    = input.value.trim();
    const drop = document.getElementById('rDrop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }

    fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(items => {
            if (!items.length) { drop.style.display = 'none'; return; }
            returnItemStore[rid] = items;
            drop.innerHTML = items.map((i, idx) => `
                <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                    <strong>${i.name}</strong> <small style="color:#888;">· ${i.sku || ''}</small>
                </div>
            `).join('');
            drop.querySelectorAll('.autocomplete-item').forEach(el => {
                el.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectReturnItem(this.dataset.rid, returnItemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                });
            });
            drop.style.display = 'block';
        });
}

function selectReturnItem(rid, item) {
    document.getElementById('rSearch_' + rid).value = item.name;
    document.getElementById('rItemId_' + rid).value = item.id;
    document.getElementById('rPrice_'  + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('rDrop_'   + rid).style.display = 'none';
    calcReturnRow(rid);
    // Auto-open IMEI modal after selecting item
    setTimeout(() => openRetImei(rid, item.name), 120);

    // Auto-add new row if this is the last row
    const rows = document.querySelectorAll('#returnItemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addReturnRow();
}

// ── Load from invoice ──
function loadSaleItems() {
    const saleId = document.getElementById('refId').value;
    if (!saleId) { alert('Enter a sale invoice ID first.'); return; }
    fetch(`?page=sales&action=getSaleItems&sale_id=${saleId}`)
        .then(r => r.json())
        .then(items => {
            document.getElementById('returnItemsBody').innerHTML = '';
            returnRowCount = 0;
            items.forEach(item => addReturnRow(item));
        });
}

// ══════════════ IMEI MODAL ══════════════
function openRetImei(rid, itemName) {
    retCurrentRow = rid;
    document.getElementById('retImeiTitle').textContent =
        itemName ? 'Scanning IMEI for: ' + itemName : 'Scan IMEI';
    document.getElementById('retImeiInput').value = '';
    document.getElementById('retImeiMsg').textContent   = '';
    document.getElementById('retImeiMsg').className     = 'rimei-msg';
    renderRetImeiTags();
    updateRetImeiCounter();
    document.getElementById('retImeiOverlay').classList.add('open');
    setTimeout(() => document.getElementById('retImeiInput').focus(), 80);
}

function closeRetImei() {
    document.getElementById('retImeiOverlay').classList.remove('open');
    retCurrentRow = null;
}

function renderRetImeiTags() {
    const rid   = retCurrentRow;
    const imeis = retImeiData[rid] || [];
    document.getElementById('retImeiTags').innerHTML = imeis.map(imei => `
        <span class="rimei-tag">
            ${imei}
            <span class="rm" onclick="removeRetImei('${rid}','${imei}')">×</span>
        </span>
    `).join('');
}

function removeRetImei(rid, imei) {
    retImeiData[rid] = retImeiData[rid].filter(i => i !== imei);
    renderRetImeiTags();
    updateRetImeiCounter();
}

function updateRetImeiCounter() {
    const rid   = retCurrentRow;
    if (!rid) return;
    const count = (retImeiData[rid] || []).length;
    const el    = document.getElementById('retImeiCounter');
    el.textContent = count > 0 ? `${count} IMEI${count > 1 ? 's' : ''} scanned` : '';
    el.style.color = count > 0 ? '#059669' : '#6b7280';

    // Update button style
    const btn = document.getElementById('retImeiBtn_' + rid);
    if (btn) btn.className = count > 0 ? 'rimei-btn has-imei' : 'rimei-btn';

    // Update qty
    if (count > 0) {
        const qtyEl = document.getElementById('rQty_' + rid);
        if (qtyEl) { qtyEl.value = count; calcReturnRow(rid); }
    }

    // Update hidden input
    document.getElementById('rImei_' + rid).value = (retImeiData[rid] || []).join(',');
}

function showRetImeiMsg(msg, type) {
    const el = document.getElementById('retImeiMsg');
    el.textContent = msg;
    el.className = 'rimei-msg ' + type;
    if (type === 'ok') setTimeout(() => { el.textContent = ''; }, 2000);
}

function confirmRetImei() {
    const rid   = retCurrentRow;
    const input = document.getElementById('retImeiInput');
    const imei  = input.value.trim();
    if (!imei) { closeRetImei(); return; }

    if (!/^\d{15}$/.test(imei)) {
        showRetImeiMsg('IMEI must be exactly 15 digits.', 'err');
        return;
    }
    if ((retImeiData[rid] || []).includes(imei)) {
        showRetImeiMsg('This IMEI is already added.', 'err');
        return;
    }

    // Check across other rows
    let duplicate = false;
    Object.keys(retImeiData).forEach(r => {
        if (r !== rid && retImeiData[r].includes(imei)) duplicate = true;
    });
    if (duplicate) {
        showRetImeiMsg('This IMEI is used in another row.', 'err');
        return;
    }

    retImeiData[rid].push(imei);
    input.value = '';
    renderRetImeiTags();
    updateRetImeiCounter();
    showRetImeiMsg('✓ Added', 'ok');
    input.focus();
}

// Enter key triggers confirm
document.getElementById('retImeiInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); confirmRetImei(); }
});

// Close on overlay click
document.getElementById('retImeiOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeRetImei();
});

// Start with one row
addReturnRow();

$(document).ready(() => { $('.select2-party').select2({ placeholder: 'Search customer...' }); });
</script>
