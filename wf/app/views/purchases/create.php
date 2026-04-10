<!-- Purchase Create -->
<style>
.sale-page-wrap{background:var(--bg-card);color:var(--text-main);min-height:100vh;font-family:'Segoe UI',sans-serif;font-size:0.875rem;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:0.65rem 1.25rem;background:var(--bg-card);border-bottom:1px solid var(--border-color);position:sticky;top:58px;z-index:90;}
.sale-title{font-size:1rem;font-weight:800;color:var(--text-main);display:flex;align-items:center;gap:8px;}
.godown-select{padding:5px 12px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.8rem;background:var(--bg-main);cursor:pointer;color:var(--text-main);font-weight:600;}
.party-row{display:flex;align-items:center;gap:12px;padding:0.65rem 1.25rem;background:var(--bg-card);border-bottom:1px solid var(--border-color);flex-wrap:wrap;}
.party-row .form-control,.party-row .form-select{background:var(--bg-main);border:1px solid var(--border-color);color:var(--text-main);border-radius:6px;font-size:0.82rem;padding:5px 10px;height:36px;}
.inv-info{display:flex;flex-direction:column;gap:3px;font-size:0.8rem;color:var(--text-muted);margin-left:auto;white-space:nowrap;}
.inv-info span{display:flex;justify-content:space-between;gap:16px;align-items:center;}
.inv-info strong{color:var(--text-main);min-width:80px;text-align:right;font-size:0.85rem;}
/* Table */
table.items-tbl{width:100%;border-collapse:collapse;font-size:0.82rem;}
table.items-tbl th{color:#fff;font-weight:700;padding:9px 10px;border:none;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.4px;white-space:nowrap;}
table.items-tbl thead tr{background:#1e3a5f;}
table.items-tbl th.col-imei-h { background:#1e3a5f; }
table.items-tbl th.col-qty-h  { background:#1e3a5f; }
table.items-tbl th.col-cost-h { background:#1e3a5f; }
table.items-tbl th.col-amt-h  { background:#1e3a5f; }
table.items-tbl td{border:none;border-bottom:1px solid var(--border-color);padding:5px 6px;vertical-align:middle;}
table.items-tbl tbody tr{background:var(--bg-card);}
table.items-tbl tbody tr:nth-child(even){background:var(--bg-main);}
table.items-tbl tbody tr:hover{background:rgba(99,102,241,0.05);}
table.items-tbl tbody td.col-qty-td  {}
table.items-tbl tbody td.col-cost-td {}
table.items-tbl tbody td.col-amt-td  {color:#065f46;font-weight:700;}
table.items-tbl tbody tr:hover td.col-qty-td  {}
table.items-tbl tbody tr:hover td.col-cost-td {}
table.items-tbl tbody tr:hover td.col-amt-td  {}
table.items-tbl input,table.items-tbl select{border:none;outline:none;background:transparent;width:100%;font-size:0.82rem;color:var(--text-main);padding:3px;}
table.items-tbl input:focus{background:rgba(99,102,241,0.08);border-radius:3px;}
/* Add row strip */
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;cursor:pointer;border-top:1px dashed #c7d2fe;color:var(--text-muted);font-size:0.83rem;font-weight:600;transition:all 0.15s;user-select:none;background:var(--bg-card);}
.add-row-strip:hover{background:rgba(99,102,241,0.05);color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1rem;color:#6366f1;line-height:1;flex-shrink:0;}
/* Totals */
.sale-bottom{display:flex;justify-content:flex-end;padding:1rem 1.25rem;border-top:1px solid var(--border-color);background:var(--bg-card);}
.sale-totals{min-width:300px;}
.totals-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-color);font-size:0.85rem;color:var(--text-muted);}
.totals-row:last-child{border-bottom:none;}
.totals-row.grand{font-size:1.05rem;font-weight:800;color:var(--text-main);border-top:2px solid var(--border-color);padding-top:10px;margin-top:4px;}
.totals-row input{width:130px;text-align:right;border:1px solid var(--border-color);border-radius:5px;padding:3px 7px;font-size:0.85rem;background:var(--bg-main);color:var(--text-main);}
/* Save bar */
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:0.65rem 1.25rem;background:var(--bg-card);border-top:1px solid var(--border-color);position:sticky;bottom:0;z-index:90;}
.btn-save-main{background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;padding:7px 28px;font-size:0.9rem;font-weight:700;border-radius:8px;cursor:pointer;box-shadow:0 2px 8px rgba(99,102,241,0.3);}
.btn-save-main:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--border-color);border-radius:8px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.15);max-height:220px;overflow-y:auto;}
.autocomplete-item{padding:8px 12px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid var(--border-color);color:var(--text-main);}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:rgba(99,102,241,0.08);color:#6366f1;}
</style>

<form method="POST" action="?page=purchases&action=store" id="purForm">
<div class="sale-page-wrap">

    <!-- Top Bar -->
    <div class="sale-topbar">
        <span class="sale-title">Purchase</span>
        <input type="hidden" name="warehouse_id" id="whSelect" value="<?= Auth::warehouseId() ?>">
        <span style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:5px 12px;color:#10b981;font-size:0.82rem;font-weight:600;">
            <i class="bi bi-building me-1"></i><?= htmlspecialchars(Auth::warehouseName()) ?>
        </span>
    </div>

    <!-- Party Row -->
    <div class="party-row">
        <div style="position:relative;min-width:280px;max-width:340px;">
            <div style="display:flex;align-items:center;
                        background:#fafbff;border:1.5px solid #c7d2fe;
                        border-radius:9px;padding:0 10px;height:38px;
                        transition:border-color 0.15s;gap:8px;"
                 id="supplierFieldWrap">
                <i class="bi bi-building" style="color:#6366f1;font-size:1rem;flex-shrink:0;"></i>
                <input type="text" id="supplierSearch" placeholder="Search supplier..."
                       autocomplete="off"
                       style="border:none;outline:none;background:transparent;
                              width:100%;font-size:0.85rem;color:#1a1a2e;font-weight:500;"
                       onfocus="document.getElementById('supplierFieldWrap').style.borderColor='#6366f1';document.getElementById('supplierFieldWrap').style.boxShadow='0 0 0 3px rgba(99,102,241,0.12)'"
                       onblur="document.getElementById('supplierFieldWrap').style.boxShadow='none'">
            </div>
            <div class="autocomplete-box" id="supplierDrop" style="display:none;"></div>
            <input type="hidden" name="party_id" id="supplierIdInput" required>
        </div>
        <input type="text" id="supplierPhone" class="form-control" placeholder="Phone No."
               readonly style="max-width:130px;background:#f8f9fa;height:38px;font-size:0.82rem;">
        <div class="inv-info ms-auto">
            <span>Invoice Number <strong><?= $nextInv ?></strong></span>
            <span>Supplier Invoice #
                <input type="text" name="supplier_invoice_no"
                       placeholder="Supplier's ref no."
                       style="border:1px solid #d1d5db;border-radius:4px;padding:1px 7px;font-size:0.8rem;color:#222;width:130px;">
            </span>
            <span>Invoice Date
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" style="border:1px solid #d1d5db;border-radius:4px;padding:1px 5px;font-size:0.8rem;color:#222;">
            </span>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-table-wrap">
        <table class="items-tbl" id="purItemsTable">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th style="min-width:240px;">ITEM</th>
                    <th class="col-imei-h" style="width:44px;text-align:center;"></th>
                    <th class="col-qty-h" style="width:80px;text-align:center;">QTY</th>
                    <th class="col-cost-h" style="width:130px;text-align:right;">COST/UNIT</th>
                    <th class="col-amt-h" style="width:130px;text-align:right;">AMOUNT</th>
                    <th style="width:30px;background:#1e3a5f;"></th>
                </tr>
            </thead>
            <tbody id="purItemsBody"></tbody>
            <tfoot>
                <tr style="background:var(--bg-main);border-top:2px solid var(--border-color);">
                    <td colspan="2"></td>
                    <td></td>
                    <td style="text-align:center;font-weight:700;color:#1d4ed8;" id="purQtyFoot">0</td>
                    <td></td>
                    <td style="text-align:right;font-weight:800;padding-right:8px;color:#065f46;" id="purSubFoot">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Add Row Strip -->
    <div class="add-row-strip" onclick="addPurRow()">
        <span class="plus-c">+</span> Add Item Row
    </div>

    <!-- Totals only -->
    <div class="sale-bottom">
        <div class="sale-totals">
            <div class="totals-row"><span>Subtotal</span><span id="purSubDisplay">0.000</span></div>
            <div class="totals-row"><span>Discount</span><input type="number" name="discount" id="purDiscInput" step="0.001" min="0" value="0.000" oninput="calcPurTotals()" placeholder="0.000"></div>
            <input type="hidden" name="tax" value="0">
            <input type="hidden" name="paid_amount" value="0">
            <input type="hidden" name="payment_method" value="cash">
            <input type="hidden" name="account_id" value="<?= $accounts[0]['id'] ?? '' ?>">
            <div class="totals-row grand"><span>Total</span><span id="purGrandDisplay">0.000</span></div>
            <div class="totals-row"><span style="color:#dc2626;font-weight:600;">Balance Due</span><span id="purBalDisplay" style="color:#dc2626;font-weight:800;">0.000</span></div>
        </div>
    </div>

    <input type="hidden" name="print_mode" id="purPrintMode" value="0">
    <div class="save-bar">
        <a href="?page=purchases" style="background:#f8f9fa;border:1px solid #d1d5db;color:#444;padding:6px 16px;font-size:0.85rem;border-radius:5px;text-decoration:none;">Cancel</a>
        <button type="submit" class="btn-save-main"
                onclick="document.getElementById('purPrintMode').value='0'">
            <i class="bi bi-check-lg me-1"></i> Save
        </button>
        <button type="submit"
                onclick="document.getElementById('purPrintMode').value='1'"
                style="background:#059669;border:none;color:#fff;padding:6px 22px;font-size:0.9rem;font-weight:600;border-radius:5px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-printer"></i> Print & Save
        </button>
    </div>
</div>
</form>

<script>
let purRowCount  = 0;
let purWarehouse = document.getElementById('whSelect').value;
let purImeiData  = {};
let purCurrentImeiRow  = null;
let purActiveImeis     = [];
let purCurrentItemName = '';

document.getElementById('whSelect').onchange = e => purWarehouse = e.target.value;
document.addEventListener('DOMContentLoaded', () => { addPurRow(); addPurRow(); });

function addPurRow() {
    purRowCount++;
    const rid = 'pur_' + purRowCount;
    purImeiData[rid] = [];
    const tr  = document.createElement('tr');
    tr.id = rid;
    tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td style="text-align:center;color:#aaa;">${purRowCount}</td>
        <td style="position:relative;">
            <input type="text" class="pur-item-search" placeholder="Search item..." autocomplete="off"
                   data-row="${rid}" oninput="searchPurItem(this,'${rid}')">
            <input type="hidden" name="items[${purRowCount}][item_id]" id="purItemId_${rid}">
            <input type="hidden" name="items[${purRowCount}][has_imei]" id="purHasImei_${rid}" value="0">
            <div class="autocomplete-box" id="purDrop_${rid}" style="display:none;"></div>
        </td>
        <td style="text-align:center;padding:4px;">
            <input type="hidden" name="items[${purRowCount}][imeis]" id="purImei_${rid}">
            <button type="button" id="purImeiBtn_${rid}" onclick="openPurImeiModal('${rid}')"
                style="background:linear-gradient(135deg,#eff6ff,#e0e7ff);border:1px solid #c7d2fe;
                       color:#6366f1;border-radius:6px;padding:3px 8px;font-size:0.78rem;
                       cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;
                       box-shadow:0 1px 3px rgba(99,102,241,0.15);transition:all 0.15s;">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty-td"><input type="number" name="items[${purRowCount}][quantity]" id="purQty_${rid}"
                value="1" min="1" style="text-align:center;" oninput="calcPurRow('${rid}')"></td>
        <input type="hidden" name="items[${purRowCount}][unit]" value="pcs">
        <td class="col-cost-td"><input type="number" name="items[${purRowCount}][unit_price]" id="purPrice_${rid}"
                value="" step="0.001" placeholder="0.000" style="text-align:right;" oninput="calcPurRow('${rid}')"></td>
        <td class="col-amt-td" id="purAmt_${rid}" style="text-align:right;padding-right:8px;">0.000</td>
        <td style="text-align:center;">
            <button type="button" onclick="removeRow('${rid}')" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:1rem;">×</button>
        </td>
    `;
    document.getElementById('purItemsBody').appendChild(tr);
}

function removeRow(rid) {
    document.getElementById(rid)?.remove();
    delete purImeiData[rid];
    calcPurTotals();
}

// Data store for items returned by search - avoids JSON-in-onclick bug
const purItemStore = {};
let purSearchTimers = {};

function searchPurItem(input, rid) {
    clearTimeout(purSearchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('purDrop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }

    purSearchTimers[rid] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${purWarehouse}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                purItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}" style="cursor:pointer;">
                        <strong>${it.name}</strong> ${it.sku ? '<small style="color:#aaa;">· ' + it.sku + '</small>' : ''}
                        <br><small style="color:#888;">Cost: ${parseFloat(it.purchase_price||0).toFixed(3)}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const r   = this.dataset.rid;
                        const idx = parseInt(this.dataset.idx);
                        selectPurItem(r, purItemStore[r][idx]);
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
}

function selectPurItem(rid, item) {
    const searchInput = document.querySelector('#' + rid + ' .pur-item-search');
    if (searchInput) searchInput.value = item.name;
    document.getElementById('purItemId_'  + rid).value = item.id;
    document.getElementById('purHasImei_' + rid).value = item.has_imei;
    document.getElementById('purPrice_'   + rid).value = parseFloat(item.purchase_price || 0).toFixed(3);
    document.getElementById('purDrop_'    + rid).style.display = 'none';

    // Always show IMEI button after item selected
    const imeiBtn = document.getElementById('purImeiBtn_' + rid);
    if (imeiBtn) imeiBtn.style.display = 'inline-block';

    calcPurRow(rid);

    // Auto-open IMEI modal for every item
    setTimeout(() => openPurImeiModal(rid, item.name), 120);

    // Auto-add new row if this is the last row
    const rows = document.querySelectorAll('#purItemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addPurRow();
}

document.addEventListener('click', e => {
    if (!e.target.closest('td')) {
        document.querySelectorAll('.autocomplete-box').forEach(d => d.style.display='none');
    }
});

function calcPurRow(rid) {
    const qty   = parseFloat(document.getElementById('purQty_'   + rid)?.value) || 0;
    const price = parseFloat(document.getElementById('purPrice_' + rid)?.value) || 0;
    document.getElementById('purAmt_' + rid).textContent = (qty * price).toFixed(3);
    calcPurTotals();
}

function calcPurTotals() {
    let subtotal = 0, totalQty = 0;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid) return;
        const qty   = parseFloat(document.getElementById('purQty_'   + rid)?.value) || 0;
        const price = parseFloat(document.getElementById('purPrice_' + rid)?.value) || 0;
        subtotal += qty * price;
        totalQty += qty;
    });
    const discount   = parseFloat(document.getElementById('purDiscInput').value) || 0;
    const grand      = subtotal - discount;

    document.getElementById('purSubDisplay').textContent   = subtotal.toFixed(3);
    document.getElementById('purSubFoot').textContent      = subtotal.toFixed(3);
    document.getElementById('purQtyFoot').textContent      = totalQty;
    document.getElementById('purGrandDisplay').textContent = grand.toFixed(3);
    document.getElementById('purBalDisplay').textContent   = grand.toFixed(3);
}

// Supplier search
const supplierStore = {};
let supTimer;
document.getElementById('supplierSearch').addEventListener('input', function() {
    clearTimeout(supTimer);
    const q = this.value.trim();
    const drop = document.getElementById('supplierDrop');
    if (q.length < 1) { drop.style.display = 'none'; return; }

    supTimer = setTimeout(() => {
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(q)}&type=supplier`)
            .then(r => r.json())
            .then(parties => {
                if (!parties.length) { drop.style.display = 'none'; return; }
                supplierStore['results'] = parties;
                drop.innerHTML = parties.map((p, idx) => `
                    <div class="autocomplete-item" data-idx="${idx}" style="cursor:pointer;">
                        <strong>${p.name}</strong> <small style="color:#888;">${p.phone||''}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectSupplier(supplierStore['results'][parseInt(this.dataset.idx)]);
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
});

function selectSupplier(p) {
    const wrap   = document.getElementById('supplierFieldWrap');
    const search = document.getElementById('supplierSearch');
    search.value = p.name;
    search.style.fontWeight = '700';
    search.style.color = '#1e3a5f';
    wrap.style.borderColor = '#6366f1';
    wrap.style.background = 'linear-gradient(135deg,#f5f3ff,#eff6ff)';
    document.getElementById('supplierIdInput').value = p.id;
    document.getElementById('supplierPhone').value = p.phone || '';
    document.getElementById('supplierDrop').style.display = 'none';
}

document.getElementById('purForm').addEventListener('submit', e => {
    if (!document.getElementById('supplierIdInput').value) {
        e.preventDefault(); alert('Please select a supplier.'); return;
    }
    let hasItem = false;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        if (tr.dataset.rowId && document.getElementById('purItemId_' + tr.dataset.rowId)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); }
});
</script>

<!-- ===== PURCHASE IMEI MODAL ===== -->
<style>
.pur-imei-overlay {
    position:fixed;inset:0;background:rgba(0,0,0,0.45);
    z-index:9999;display:none;align-items:center;justify-content:center;
}
.pur-imei-overlay.show { display:flex; }
.pur-imei-modal {
    background:#fff;border-radius:10px;padding:24px 28px;
    width:100%;max-width:480px;box-shadow:0 8px 30px rgba(0,0,0,0.18);color:#222;
}
.pur-imei-tag {
    display:inline-flex;align-items:center;gap:5px;
    background:#eff6ff;border:1px solid #bfdbfe;
    border-radius:5px;padding:3px 8px;margin:3px;font-size:0.8rem;color:#1d4ed8;
}
.pur-imei-tag .rm { cursor:pointer;color:#94a3b8;font-size:0.9rem; }
.pur-imei-tag .rm:hover { color:#ef4444; }
.pur-imei-msg { font-size:0.78rem;padding:3px 6px;border-radius:4px;margin:4px 0 8px;min-height:22px; }
.pur-imei-msg.ok  { background:#d1fae5;color:#065f46; }
.pur-imei-msg.err { background:#fee2e2;color:#991b1b; }
</style>

<div class="pur-imei-overlay" id="purImeiModal">
    <div class="pur-imei-modal">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
            <div>
                <div style="font-size:0.75rem;color:#888;margin-bottom:2px;">Scanning IMEI for:</div>
                <div id="purImeiItemName" style="font-size:0.95rem;font-weight:700;color:#1e3a5f;"></div>
            </div>
            <button onclick="closePurImeiModal()" style="background:none;border:none;font-size:1.4rem;color:#aaa;cursor:pointer;line-height:1;">×</button>
        </div>



        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
            <label style="font-size:0.85rem;color:#555;font-weight:500;">IMEI / Serial Number:</label>
            <span id="purImeiCount" style="font-size:0.8rem;">0 Entered</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:4px;">
            <input type="text" id="purImeiScanInput"
                placeholder="Scan barcode or type 15-digit IMEI"
                maxlength="15"
                onkeydown="if(event.key==='Enter'){event.preventDefault();confirmPurImei();}"
                style="flex:1;border:1px solid #d1d5db;border-radius:5px;padding:7px 10px;font-size:0.88rem;font-family:monospace;letter-spacing:1px;color:#222;">
            <button type="button" onclick="confirmPurImei()"
                style="background:#3b82f6;border:none;color:#fff;border-radius:5px;width:36px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
        <div id="purImeiMsg" class="pur-imei-msg"></div>
        <div id="purImeiTagList" style="min-height:60px;"></div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
            <button type="button" onclick="closePurImeiModal()"
                style="background:#f3f4f6;border:1px solid #e5e7eb;color:#444;padding:6px 18px;border-radius:5px;cursor:pointer;">
                Cancel
            </button>
            <button type="button" onclick="savePurImeiModal()"
                style="background:#3b82f6;border:none;color:#fff;padding:6px 22px;border-radius:5px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
function openPurImeiModal(rid, itemName) {
    purCurrentImeiRow  = rid;
    purCurrentItemName = itemName || document.querySelector('#' + rid + ' .pur-item-search')?.value || 'Item';
    purActiveImeis     = [...(purImeiData[rid] || [])];

    document.getElementById('purImeiItemName').textContent = purCurrentItemName;
    renderPurImeiTags();
    document.getElementById('purImeiModal').classList.add('show');
    document.getElementById('purImeiScanInput').value = '';
    document.getElementById('purImeiMsg').innerHTML   = '';
    setTimeout(() => document.getElementById('purImeiScanInput').focus(), 80);
}

function closePurImeiModal() {
    document.getElementById('purImeiModal').classList.remove('show');
}

function getAllPurEnteredImeis(excludeRow) {
    const all = [];
    Object.keys(purImeiData).forEach(r => {
        if (r !== excludeRow) all.push(...purImeiData[r]);
    });
    return all;
}

function confirmPurImei() {
    const input = document.getElementById('purImeiScanInput');
    const imei  = input.value.trim();
    if (!imei) return;

    // Must be exactly 15 digits
    if (!/^\d{15}$/.test(imei)) {
        showPurImeiMsg('IMEI must be exactly 15 digits (numbers only).', 'err');
        input.select();
        return;
    }

    // Duplicate in current row
    if (purActiveImeis.includes(imei)) {
        showPurImeiMsg('This IMEI is already added to this item.', 'err');
        input.select();
        return;
    }

    // Duplicate across other rows
    if (getAllPurEnteredImeis(purCurrentImeiRow).includes(imei)) {
        showPurImeiMsg('This IMEI is already used in another row on this invoice.', 'err');
        input.select();
        return;
    }

    purActiveImeis.push(imei);
    renderPurImeiTags();
    showPurImeiMsg('✓ IMEI added.', 'ok');
    input.value = '';
    input.focus();
}

function showPurImeiMsg(msg, type) {
    const el = document.getElementById('purImeiMsg');
    el.className = 'pur-imei-msg ' + type;
    el.textContent = msg;
    if (type === 'ok') setTimeout(() => el.textContent = '', 2000);
}

function renderPurImeiTags() {
    const qty = parseInt(document.getElementById('purQty_' + purCurrentImeiRow)?.value) || 0;
    const entered = purActiveImeis.length;
    document.getElementById('purImeiCount').innerHTML =
        `<span style="color:${entered >= qty && qty > 0 ? '#059669' : '#f59e0b'};">${entered} Entered</span>` +
        (qty > 0 ? ` <span style="color:#aaa;">/ ${qty} needed</span>` : '');

    document.getElementById('purImeiTagList').innerHTML = purActiveImeis.map((im, i) => `
        <span class="pur-imei-tag">
            <span style="font-family:monospace;letter-spacing:0.5px;">${im}</span>
            <span class="rm" onclick="removePurImei(${i})">×</span>
        </span>
    `).join('');
}

function removePurImei(idx) {
    purActiveImeis.splice(idx, 1);
    renderPurImeiTags();
}

function savePurImeiModal() {
    if (!purCurrentImeiRow) return;

    const qty = parseInt(document.getElementById('purQty_' + purCurrentImeiRow)?.value) || 0;
    if (qty > 0 && purActiveImeis.length !== qty) {
        const ok = confirm(
            `You entered ${purActiveImeis.length} IMEI(s) but quantity is ${qty}.\n` +
            `Quantity will be updated to match. Continue?`
        );
        if (!ok) return;
    }

    purImeiData[purCurrentImeiRow] = [...purActiveImeis];
    document.getElementById('purImei_' + purCurrentImeiRow).value = purActiveImeis.join('\n');

    // Update IMEI button
    const btn = document.getElementById('purImeiBtn_' + purCurrentImeiRow);
    if (btn) {
        if (purActiveImeis.length > 0) {
            btn.style.background = 'linear-gradient(135deg,#d1fae5,#a7f3d0)';
            btn.style.borderColor = '#6ee7b7';
            btn.style.color = '#059669';
            btn.innerHTML = `<i class="bi bi-upc-scan"></i> ${purActiveImeis.length}`;
        } else {
            btn.style.background = 'linear-gradient(135deg,#eff6ff,#e0e7ff)';
            btn.style.borderColor = '#c7d2fe';
            btn.style.color = '#6366f1';
            btn.innerHTML = `<i class="bi bi-upc-scan"></i>`;
        }
    }

    // Sync qty
    const qtyField = document.getElementById('purQty_' + purCurrentImeiRow);
    if (qtyField && purActiveImeis.length > 0) {
        qtyField.value = purActiveImeis.length;
        calcPurRow(purCurrentImeiRow);
    }

    closePurImeiModal();
}
</script>
