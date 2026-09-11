<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#1e3a5f;border-radius:0;position:sticky;top:58px;z-index:90;}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.sale-composer{background:#fff;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;border-top:none;border-bottom:none;}
.sale-composer-row{
    display:grid;
    grid-template-columns:minmax(220px,1.8fr) 108px minmax(160px,1fr) 148px 150px;
    gap:10px;align-items:stretch;
    padding:12px 20px;
    position:relative;z-index:3;
}
.sale-field{position:relative;min-width:0;z-index:2;}
.sale-field .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.sale-field input:not([type="hidden"]){
    width:100%;height:44px;min-height:44px;padding:0 12px 0 40px;
    border:1.5px solid #e2e8f0;border-radius:0;
    font-size:0.92rem;font-weight:600;color:#1a1a2e;background:#fff;
    transition:border-color .15s,box-shadow .15s;outline:none;
}
.sale-field input:not([type="hidden"]):focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.12);}
.sale-field input.selected{border-color:#10b981;background:#f0fdf4;color:#065f46;}
.sale-field input::placeholder{font-family:inherit;font-size:0.82rem;font-weight:500;color:#94a3b8;}
.sale-chip{
    height:44px;min-height:44px;display:flex;flex-direction:column;justify-content:center;
    padding:0 12px;border-radius:0;background:#f8fafc;border:1.5px solid #e2e8f0;
}
.sale-chip-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;line-height:1.1;}
.sale-chip-value{font-size:0.82rem;font-weight:800;color:#4338ca;letter-spacing:0.03em;line-height:1.25;}
.sale-chip-date,.sale-chip-currency{padding:3px 10px 3px 12px;}
.sale-chip-date input[type="date"]{
    width:100%;border:none;background:transparent;outline:none;padding:0;height:auto;min-height:0;
    font-size:0.82rem;font-weight:700;color:#334155;color-scheme:light;
}
.sale-chip-currency select{
    width:100%;border:none;background:transparent;outline:none;
    padding:0 16px 0 0;margin:0;height:auto;min-height:0;
    font-size:0.82rem;font-weight:800;color:#1d4ed8;cursor:pointer;
    font-family:inherit;appearance:none;-webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M1.6 5.2 8 11.6l6.4-6.4'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right center;
}
.sale-chip-currency select:focus{color:#4338ca;}
#supplierSearchWrap input{padding-right:40px;}
#supplierComboToggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;color:#64748b;cursor:pointer;z-index:3;display:flex;align-items:center;justify-content:center;border-radius:0;padding:0;}
#supplierComboToggle:hover{color:#4338ca;background:#eef2ff;}
#supplierSearchWrap.open #supplierComboToggle{color:#4338ca;}
#supplierSearchWrap.open #supplierComboToggle i{transform:rotate(180deg);transition:transform 0.15s;}
#supplierComboToggle i{transition:transform 0.15s;display:inline-block;}
@media (max-width:960px){
    .sale-composer-row{grid-template-columns:1fr 1fr;}
    .sale-field-primary{grid-column:1/-1;}
}
.items-card{border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;border-top:none;border-bottom:1px solid #e5e7eb;border-radius:0;background:#fff;overflow:visible;}
table.items-tbl{width:100%;table-layout:fixed;border-collapse:collapse;border-spacing:0;font-size:0.92rem;border:1px solid #e2e8f0;}
table.items-tbl th,table.items-tbl td{box-shadow:none !important;}
table.items-tbl th{
    padding:8px 8px;font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;
    color:#4338ca;background:#eef2ff;white-space:nowrap;border:1px solid #c7d2fe !important;
}
table.items-tbl th.col-num{color:#6366f1;padding-left:8px;}
table.items-tbl th.col-act{padding-right:8px;}
table.items-tbl tbody td{padding:0 !important;vertical-align:middle;background:#fff;border:1px solid #e2e8f0 !important;height:48px;}
table.items-tbl tbody tr{background:#fff;}
table.items-tbl tbody tr:hover td.col-num,
table.items-tbl tbody tr:hover td.col-item,
table.items-tbl tbody tr:hover td.col-qty,
table.items-tbl tbody tr:hover td.col-act{background:#f8faff;}
table.items-tbl tfoot td{background:#fff !important;border:none !important;}
.col-num{width:36px;text-align:center;color:#94a3b8;font-size:0.85rem;font-weight:600;background:#f8fafc;}
table.items-tbl tbody td.col-num{background:#f8fafc;line-height:48px;}
.col-item{width:auto;min-width:180px;position:relative;}
.col-qty{width:72px;text-align:center;}
.col-fprice{width:110px;text-align:right;}
.col-price{width:110px;text-align:right;}
.col-ktotal{width:118px;text-align:right;}
.col-act{width:36px;text-align:center;}
.po-cell-input{
    display:block;width:100%;height:48px;box-sizing:border-box;
    border:none !important;border-radius:0;background:#fff;outline:none;
    font-size:0.92rem;font-weight:500;color:#1e293b;
    padding:0 10px;line-height:48px;box-shadow:none !important;
}
.po-cell-input::placeholder{color:#94a3b8;font-weight:400;font-size:0.85rem;}
.po-cell-input:focus{background:#fff;outline:2px solid #6366f1;outline-offset:-2px;position:relative;z-index:2;}
.po-cell-input.item-search{font-weight:400;padding-left:10px;}
.po-cell-input.qty{text-align:center;font-variant-numeric:tabular-nums;}
.po-cell-input.qty::-webkit-inner-spin-button,
.po-cell-input.qty::-webkit-outer-spin-button,
.po-cell-input.foreign::-webkit-inner-spin-button,
.po-cell-input.foreign::-webkit-outer-spin-button,
.po-cell-input.unit::-webkit-inner-spin-button,
.po-cell-input.unit::-webkit-outer-spin-button,
.po-cell-input.total::-webkit-inner-spin-button,
.po-cell-input.total::-webkit-outer-spin-button{-webkit-appearance:none;margin:0;}
.po-cell-input.qty,.po-cell-input.foreign,.po-cell-input.unit,.po-cell-input.total{-moz-appearance:textfield;appearance:textfield;}
.po-cell-input.foreign{text-align:right;color:#b45309;background:#fffbeb;font-variant-numeric:tabular-nums;}
.po-cell-input.foreign:focus{background:#fffbeb;}
.po-cell-input.unit{text-align:right;color:#1e3a5f;font-variant-numeric:tabular-nums;}
.po-cell-input.total{text-align:right;color:#4338ca;background:#eef2ff;font-weight:700;font-variant-numeric:tabular-nums;}
.po-cell-input.total:focus{background:#eef2ff;}
.po-row-remove{
    display:flex;align-items:center;justify-content:center;
    width:100%;height:48px;border-radius:0;
    background:none;border:none;color:#94a3b8;cursor:pointer;
    font-size:1.15rem;line-height:1;padding:0;
}
.po-row-remove:hover{color:#dc2626;background:#fef2f2;}
@media(max-width:900px){
    .col-qty{width:64px;}.col-fprice,.col-price{width:96px;}.col-ktotal{width:104px;}
    .gt-card{width:100%;min-height:64px;}
    #grandKwd{font-size:1.25rem;}
    .gt-qty,.gt-foreign{font-size:0.85rem;}
}
.sale-gt-cell{padding:10px 0 0 !important;vertical-align:top;background:#fff !important;}
.sale-btns-cell{padding:8px 0 12px !important;vertical-align:middle;background:#fff !important;}
.sale-cancel-cell{padding:8px 8px 12px 0 !important;text-align:right;vertical-align:middle;background:#fff !important;}
.gt-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 14px;width:100%;min-height:64px;box-sizing:border-box;background:#1e3a5f;border-radius:0;box-shadow:none;position:relative;overflow:hidden;}
.gt-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:0;background:#818cf8;}
.gt-meta{display:flex;flex-direction:column;gap:2px;padding-left:8px;min-width:0;}
.gt-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.62);line-height:1.2;}
.gt-qty{font-size:0.82rem;font-weight:800;color:#e0e7ff;letter-spacing:0.01em;line-height:1.2;}
.gt-foreign{font-size:0.78rem;font-weight:700;color:#fcd34d;letter-spacing:0.01em;line-height:1.2;font-variant-numeric:tabular-nums;}
.gt-amount{display:flex;align-items:baseline;gap:8px;font-variant-numeric:tabular-nums;white-space:nowrap;}
.gt-currency{font-size:0.82rem;font-weight:700;color:#a5b4fc;letter-spacing:0.04em;}
#grandKwd{font-size:1.45rem;font-weight:800;color:#fff;letter-spacing:-0.03em;line-height:1;}
.save-actions{display:flex;align-items:stretch;gap:8px;width:100%;}
.btn-cancel-sale{padding:0 18px;border-radius:0;font-size:0.88rem;height:48px;min-height:48px;border:1px solid #e2e8f0;color:#64748b;background:#fff;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;box-sizing:border-box;}
.btn-cancel-sale:hover{border-color:#94a3b8;background:#f8fafc;}
.btn-save-sale{flex:1 1 0;min-width:0;height:48px;min-height:48px;padding:0 14px;border-radius:0;font-size:0.9rem;font-weight:700;white-space:nowrap;border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;background:#2563eb;box-shadow:none;}
.btn-save-sale:hover{background:#1d4ed8;}
.btn-save-sale:disabled{opacity:0.5;cursor:not-allowed;}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:280px;overflow-y:auto;margin-top:4px;min-width:100%;}
.autocomplete-box.item-dropdown{position:fixed;margin-top:0;min-width:380px;width:auto;right:auto;z-index:10050;}
.autocomplete-item{padding:10px 14px;cursor:pointer;font-size:0.85rem;border-bottom:1px solid #f1f5f9;color:#1e293b;transition:background 0.1s;line-height:1.35;}
.autocomplete-item strong{display:block;font-weight:700;color:#1e293b;}
.autocomplete-item small{color:#94a3b8;font-size:0.78rem;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
.autocomplete-item.active,.autocomplete-item.active:hover{background:#eff6ff;box-shadow:inset 3px 0 0 #6366f1;outline:none;}
</style>

<form method="POST" action="?page=purchaseorders&action=store" id="poForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="party_id" id="partyIdInput">
    <input type="hidden" name="warehouse_id" id="whSelect" value="<?= (int) Auth::warehouseId() ?>">

<div class="sale-wrap">

    <!-- TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-file-earmark-text"></i> New Purchase Order
        </div>
    </div>

    <!-- SUPPLIER + META -->
    <div class="sale-composer">
        <div class="sale-composer-row">
            <div class="sale-field sale-field-primary customer-search-wrap" id="supplierSearchWrap">
                <i class="bi bi-person-circle search-icon"></i>
                <input type="text" id="supplierSearch" placeholder="Search supplier..." autocomplete="off"
                    autofocus role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="supplierDrop">
                <button type="button" id="supplierComboToggle" tabindex="-1" aria-label="Search suppliers">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="autocomplete-box" id="supplierDrop" style="display:none;" role="listbox"></div>
            </div>
            <div class="sale-chip sale-chip-currency">
                <label class="sale-chip-label" for="poCurrency">Currency</label>
                <select name="currency" id="poCurrency" title="Foreign currency for the supplier price (record only)">
                    <option value="AED">AED</option>
                    <option value="USD">USD</option>
                    <option value="KWD">KWD</option>
                </select>
            </div>
            <div class="sale-field">
                <i class="bi bi-file-earmark-text search-icon"></i>
                <input type="text" id="supplierRefInput" name="supplier_ref" placeholder="Proforma / Ref No">
            </div>
            <div class="sale-chip" title="Next purchase order number">
                <span class="sale-chip-label">PO No</span>
                <span class="sale-chip-value"><?= htmlspecialchars((string) $nextPoNo) ?></span>
            </div>
            <div class="sale-chip sale-chip-date">
                <label class="sale-chip-label" for="poDateInput">Date</label>
                <input type="date" name="date" id="poDateInput" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="items-card">
        <table class="items-tbl">
            <colgroup>
                <col style="width:36px">
                <col>
                <col style="width:72px">
                <col style="width:110px">
                <col style="width:110px">
                <col style="width:118px">
                <col style="width:36px">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty" style="text-align:center;">Qty</th>
                    <th class="col-fprice" style="text-align:right;">Foreign (<span id="fcurLabel">AED</span>)</th>
                    <th class="col-price" style="text-align:right;">Unit (KWD)</th>
                    <th class="col-ktotal" style="text-align:right;">Total (KWD)</th>
                    <th class="col-act"></th>
                </tr>
            </thead>
            <tbody id="poTbody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                    <td colspan="3" class="sale-gt-cell">
                        <div class="gt-card" id="poGrandTotalCard">
                            <div class="gt-meta">
                                <span class="gt-label">Grand Total</span>
                                <span class="gt-qty" id="poGtQtyHint">Total qty 0</span>
                                <span class="gt-foreign">Total foreign <span id="subtotalForeignDisplay">0.000</span> <span id="sumFcurLabel">AED</span></span>
                            </div>
                            <div class="gt-amount">
                                <span class="gt-currency">KWD</span>
                                <span id="grandKwd">0.000</span>
                            </div>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" class="sale-cancel-cell">
                        <a href="?page=purchaseorders" class="btn-cancel-sale">Cancel</a>
                    </td>
                    <td colspan="3" class="sale-btns-cell">
                        <div class="save-actions">
                            <button type="submit" class="btn-save-sale" id="poSaveBtn" disabled>
                                <i class="bi bi-check-lg"></i> Save Purchase Order
                            </button>
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
</form>

<script>
let rowCount   = 0;
let supplierId = 0;
const itemStore    = {};
const searchTimers = {};
const poItemSearchAbort = {};

function escPoHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Supplier combobox (AJAX — type to search) ────────────────────────────────
let supplierMatches = [];
let supplierHighlightIdx = -1;
let supplierTimer = null;
let supplierSearchAbort = null;

function setSupplierDropOpen(open) {
    const wrap = document.getElementById('supplierSearchWrap');
    const input = document.getElementById('supplierSearch');
    const drop = document.getElementById('supplierDrop');
    if (wrap) wrap.classList.toggle('open', !!open);
    if (input) input.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (drop) drop.style.display = open ? 'block' : 'none';
    if (!open) supplierHighlightIdx = -1;
}

function supplierTypeLabel(type) {
    if (type === 'customer') return 'Customer';
    if (type === 'both') return 'Customer & Supplier';
    return 'Supplier';
}

function updateSupplierHighlight() {
    const drop = document.getElementById('supplierDrop');
    if (!drop) return;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === supplierHighlightIdx);
    });
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function renderSupplierDropdown(matches) {
    const drop = document.getElementById('supplierDrop');
    if (!drop) return;
    supplierMatches = Array.isArray(matches) ? matches : [];
    supplierHighlightIdx = -1;
    if (!supplierMatches.length) {
        drop.innerHTML = '<div style="padding:12px 14px;color:#94a3b8;font-size:0.85rem;">No matching supplier</div>';
        setSupplierDropOpen(true);
        return;
    }
    drop.innerHTML = supplierMatches.map((s, idx) => {
        const typeLbl = supplierTypeLabel(s.type);
        const typeHtml = typeLbl !== 'Supplier'
            ? `<small>${escPoHtml(typeLbl)}</small>`
            : '';
        return `<div class="autocomplete-item" data-idx="${idx}" role="option">
            <strong>${escPoHtml(s.name)}</strong>
            ${typeHtml}
        </div>`;
    }).join('');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            const s = supplierMatches[parseInt(this.dataset.idx, 10)];
            if (s) selectSupplier(s.id, s.name);
        });
        el.addEventListener('mouseenter', function() {
            supplierHighlightIdx = parseInt(this.dataset.idx, 10);
            updateSupplierHighlight();
        });
    });
    setSupplierDropOpen(true);
}

function showSupplierTypeHint() {
    const drop = document.getElementById('supplierDrop');
    if (!drop) return;
    supplierMatches = [];
    supplierHighlightIdx = -1;
    drop.innerHTML = '<div style="padding:12px 14px;color:#94a3b8;font-size:0.85rem;">Type a name to search</div>';
    setSupplierDropOpen(true);
}

function runSupplierSearch() {
    const input = document.getElementById('supplierSearch');
    if (!input) return;
    clearTimeout(supplierTimer);
    const q = input.value.trim();
    if (q.length < 1) {
        showSupplierTypeHint();
        return;
    }
    supplierTimer = setTimeout(function () {
        const qNow = input.value.trim();
        if (qNow.length < 1) {
            showSupplierTypeHint();
            return;
        }
        if (supplierSearchAbort) supplierSearchAbort.abort();
        supplierSearchAbort = new AbortController();
        const signal = supplierSearchAbort.signal;
        fetch('?page=sales&action=searchParties&q=' + encodeURIComponent(qNow) + '&type=purchase&balances=0', { signal })
            .then(function (r) { return r.json(); })
            .then(function (parties) {
                if (input.value.trim() !== qNow) return;
                renderSupplierDropdown(Array.isArray(parties) ? parties : []);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                renderSupplierDropdown([]);
            });
    }, 300);
}

function openSupplierDropdown() {
    const input = document.getElementById('supplierSearch');
    if (!input) return;
    const q = input.value.trim();
    if (q.length < 1) {
        showSupplierTypeHint();
        return;
    }
    if (supplierMatches.length && document.getElementById('supplierDrop')?.innerHTML.trim()) {
        setSupplierDropOpen(true);
        return;
    }
    runSupplierSearch();
}

function closeSupplierDropdown() {
    setSupplierDropOpen(false);
}

function clearSupplierSelection() {
    supplierId = 0;
    const idEl = document.getElementById('partyIdInput');
    if (idEl) idEl.value = '';
    document.getElementById('supplierSearch')?.classList.remove('selected');
    checkSaveBtn();
}

let skipNextSupplierFocus = true;
document.getElementById('supplierSearch').addEventListener('input', function() {
    clearSupplierSelection();
    runSupplierSearch();
});
document.getElementById('supplierSearch').addEventListener('focus', function() {
    if (skipNextSupplierFocus) { skipNextSupplierFocus = false; return; }
    openSupplierDropdown();
});
document.getElementById('supplierSearch').addEventListener('click', function() {
    openSupplierDropdown();
});
document.getElementById('supplierComboToggle').addEventListener('mousedown', function(e) {
    e.preventDefault();
    const drop = document.getElementById('supplierDrop');
    const open = drop && drop.style.display !== 'none';
    const input = document.getElementById('supplierSearch');
    if (open) {
        closeSupplierDropdown();
        return;
    }
    input.focus();
    openSupplierDropdown();
});
document.getElementById('supplierSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey && document.getElementById('partyIdInput').value) {
        e.preventDefault();
        closeSupplierDropdown();
        focusFirstPoItem();
        return;
    }

    const drop = document.getElementById('supplierDrop');
    const visible = drop && drop.style.display !== 'none';

    if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !visible) {
        e.preventDefault();
        openSupplierDropdown();
        return;
    }
    if (!visible || !supplierMatches.length) {
        if (e.key === 'Escape' && visible) {
            e.preventDefault();
            closeSupplierDropdown();
        }
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        supplierHighlightIdx = supplierHighlightIdx < supplierMatches.length - 1 ? supplierHighlightIdx + 1 : 0;
        updateSupplierHighlight();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        supplierHighlightIdx = supplierHighlightIdx > 0 ? supplierHighlightIdx - 1 : supplierMatches.length - 1;
        updateSupplierHighlight();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        const idx = supplierHighlightIdx >= 0 ? supplierHighlightIdx : 0;
        const s = supplierMatches[idx];
        if (s) selectSupplier(s.id, s.name);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closeSupplierDropdown();
    }
}, true);
document.getElementById('supplierRefInput').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        focusFirstPoItem();
    }
});
function focusFirstPoItem() {
    const rows = document.querySelectorAll('#poTbody tr[id^="porow_"]');
    for (const tr of rows) {
        const rid = tr.id;
        if (!document.getElementById('itemId_' + rid)?.value) {
            const el = tr.querySelector('.item-search');
            if (el) {
                el.focus();
                return;
            }
        }
    }
    const any = document.querySelector('#poTbody .item-search');
    if (any) any.focus();
}

function selectSupplier(id, name) {
    supplierId = id;
    document.getElementById('partyIdInput').value = id;
    const inp = document.getElementById('supplierSearch');
    inp.value = name;
    inp.classList.add('selected');
    closeSupplierDropdown();
    checkSaveBtn();
    setTimeout(focusFirstPoItem, 0);
}

// ── Add row ────────────────────────────────────────────────────────────────
function addRow(noFocus) {
    rowCount++;
    const rid = 'porow_' + rowCount;
    const tr  = document.createElement('tr');
    tr.id = rid;
    tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="po-cell-input item-search" placeholder="Search item by name or SKU…" autocomplete="off" data-row="${rid}">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <div class="autocomplete-box item-dropdown" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-qty">
            <input type="number" class="po-cell-input qty" name="items[${rowCount}][quantity]" id="qty_${rid}"
                value="1" min="1" data-row="${rid}" data-calc="price">
        </td>
        <td class="col-fprice">
            <input type="number" class="po-cell-input foreign" name="items[${rowCount}][foreign_price]" id="fprice_${rid}"
                value="" step="0.001" min="0" placeholder="0.000"
                data-row="${rid}" data-calc="foreign">
        </td>
        <td class="col-price">
            <input type="number" class="po-cell-input unit" name="items[${rowCount}][kwd_price]" id="kwdprice_${rid}"
                value="" step="0.001" min="0" placeholder="after TT"
                data-row="${rid}" data-calc="price">
        </td>
        <td class="col-ktotal">
            <input type="number" class="po-cell-input total" name="items[${rowCount}][kwd_total]" id="kwdtotal_${rid}"
                value="" step="0.001" min="0" placeholder="after TT"
                data-row="${rid}" data-calc="total">
        </td>
        <td class="col-act">
            <button type="button" class="po-row-remove" data-row="${rid}" title="Remove row" aria-label="Remove row">×</button>
        </td>
    `;
    document.getElementById('poTbody').appendChild(tr);
    refreshKwdPlaceholders();
    if (!noFocus) tr.querySelector('.item-search').focus();
}

function removeRow(rid) {
    document.getElementById('hist_' + rid)?.remove();
    document.getElementById('itemDrop_' + rid)?.remove();
    document.getElementById(rid)?.remove();
    delete itemStore[rid];
    delete poItemHighlightIdx[rid];
    renumber(); recalcTotals();
}
function renumber() {
    let i = 1;
    document.querySelectorAll('#poTbody tr[id^="porow_"]').forEach(tr => {
        const col = tr.querySelector('.col-num');
        if (col) col.textContent = i++;
    });
}

function positionPoItemDrop(input, drop) {
    if (drop.parentElement !== document.body) {
        document.body.appendChild(drop);
    }
    const rect = input.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    drop.style.left = rect.left + 'px';
    drop.style.width = Math.max(380, rect.width) + 'px';
    if (spaceBelow < 240) {
        drop.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
        drop.style.top = 'auto';
    } else {
        drop.style.top = (rect.bottom + 4) + 'px';
        drop.style.bottom = 'auto';
    }
}

// ── Item search per row ──────────────────────────────────────────────────────
const poItemHighlightIdx = {};

function updatePoItemHighlight(rid, scrollActive) {
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    const hi = poItemHighlightIdx[rid] ?? -1;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === hi);
    });
    if (!scrollActive) return;
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function bindPoItemDropdown(rid, drop) {
    poItemHighlightIdx[rid] = -1;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            selectItem(this.dataset.rid, itemStore[this.dataset.rid][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            poItemHighlightIdx[rid] = parseInt(this.dataset.idx, 10);
            updatePoItemHighlight(rid, false);
        });
    });
}

function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    const q    = input.value.trim();
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; poItemHighlightIdx[rid] = -1; itemStore[rid] = []; return; }
    const whEl = document.getElementById('whSelect');
    const whId = whEl ? whEl.value : '';
    searchTimers[rid] = setTimeout(() => {
        const reqQ = input.value.trim();
        if (reqQ.length < 1) { drop.style.display = 'none'; poItemHighlightIdx[rid] = -1; return; }
        if (poItemSearchAbort[rid]) poItemSearchAbort[rid].abort();
        poItemSearchAbort[rid] = new AbortController();
        fetch(`?page=purchaseorders&action=searchItems&q=${encodeURIComponent(reqQ)}&warehouse_id=${encodeURIComponent(whId)}`, { signal: poItemSearchAbort[rid].signal })
            .then(r => {
                if (!r.ok) throw new Error('search failed');
                return r.json();
            })
            .then(items => {
                // Ignore stale responses when the user kept typing
                if (input.value.trim() !== reqQ) return;
                if (!Array.isArray(items) || !items.length) {
                    itemStore[rid] = [];
                    poItemHighlightIdx[rid] = -1;
                    drop.innerHTML = `<div style="padding:12px 14px;color:#94a3b8;font-size:0.85rem;">No items found for “${escPoHtml(reqQ)}”</div>`;
                    positionPoItemDrop(input, drop);
                    if (document.activeElement === input) drop.style.display = 'block';
                    return;
                }
                itemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => {
                    const aed = parseFloat(it.price_aed||0);
                    const usd = parseFloat(it.price_usd||0);
                    const foreignBadges = [
                        aed > 0 ? `<span style="background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:1px 5px;font-size:0.72rem;font-weight:700;">AED ${aed.toFixed(3)}</span>` : '',
                        usd > 0 ? `<span style="background:#fef3c7;color:#854d0e;border-radius:4px;padding:1px 5px;font-size:0.72rem;font-weight:700;">USD ${usd.toFixed(3)}</span>` : ''
                    ].filter(Boolean).join(' ');
                    return `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                        <strong>${escPoHtml(it.name)}</strong>
                        ${it.sku ? `<small style="color:#94a3b8;"> · ${escPoHtml(it.sku)}</small>` : ''}
                        <small style="float:right;color:#6366f1;font-weight:600;">KWD ${parseFloat(it.purchase_price||0).toFixed(3)}</small>
                        <br><small style="color:#94a3b8;">Stock: ${escPoHtml(it.current_stock)}</small>
                        ${foreignBadges ? `&nbsp;${foreignBadges}` : ''}
                    </div>`;
                }).join('');
                bindPoItemDropdown(rid, drop);
                positionPoItemDrop(input, drop);
                if (document.activeElement === input) drop.style.display = 'block';
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') return;
                if (input.value.trim() !== reqQ) return;
                itemStore[rid] = [];
                drop.innerHTML = `<div style="padding:12px 14px;color:#ef4444;font-size:0.85rem;">Item search failed. Try again.</div>`;
                positionPoItemDrop(input, drop);
                if (document.activeElement === input) drop.style.display = 'block';
            });
    }, 250);
}

function hideAllDropdowns() {
    document.querySelectorAll('[id^="itemDrop_porow_"]').forEach(d => d.style.display = 'none');
    Object.keys(poItemHighlightIdx).forEach(rid => { poItemHighlightIdx[rid] = -1; });
}
document.getElementById('poTbody').addEventListener('input', function(e) {
    const t = e.target;
    if (t.classList.contains('item-search')) {
        const rid = t.dataset.row || t.closest('tr[id^="porow_"]')?.id;
        if (rid) searchItem(t, rid);
        return;
    }
    const rid = t.dataset.row;
    if (!rid) return;
    const calc = t.dataset.calc;
    // While typing: update the other field only — never rewrite the active input
    // (rewriting with toFixed on every keystroke blocks editing).
    if (calc === 'price') calcFromPrice(rid, false);
    else if (calc === 'total') calcFromTotal(rid, false);
    else if (calc === 'foreign') calcForeign(rid);
});
document.getElementById('poTbody').addEventListener('focusout', function(e) {
    const t = e.target;
    const rid = t.dataset.row;
    if (!rid) return;
    const calc = t.dataset.calc;
    if (calc === 'price') calcFromPrice(rid, true);
    else if (calc === 'total') calcFromTotal(rid, true);
});
function reopenPoItemDropdown(input) {
    if (!input || !input.classList.contains('item-search')) return;
    const rid = input.dataset.row || input.closest('tr[id^="porow_"]')?.id;
    if (!rid) return;
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    if (document.getElementById('itemId_' + rid)?.value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim()) {
        positionPoItemDrop(input, drop);
        drop.style.display = 'block';
        return;
    }
    searchItem(input, rid);
}
document.getElementById('poTbody').addEventListener('focusin', function(e) {
    reopenPoItemDropdown(e.target);
});
document.getElementById('poTbody').addEventListener('click', function(e) {
    const btn = e.target.closest('.po-row-remove');
    if (btn?.dataset.row) removeRow(btn.dataset.row);
    reopenPoItemDropdown(e.target);
});
window.addEventListener('scroll', function() {
    document.querySelectorAll('#poTbody .item-search').forEach(function(input) {
        const rid = input.dataset.row || input.closest('tr[id^="porow_"]')?.id;
        if (!rid) return;
        const drop = document.getElementById('itemDrop_' + rid);
        if (drop && drop.style.display !== 'none') positionPoItemDrop(input, drop);
    });
}, true);
document.getElementById('poTbody').addEventListener('keydown', function(e) {
    if (!e.target.classList.contains('item-search')) return;
    const rid = e.target.dataset.row || e.target.closest('tr[id^="porow_"]')?.id;
    if (!rid) return;
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    const visible = drop.style.display !== 'none';
    const items = itemStore[rid] || [];

    if (e.key === 'ArrowDown') {
        if (!visible || !items.length) return;
        e.preventDefault();
        const cur = poItemHighlightIdx[rid] ?? -1;
        poItemHighlightIdx[rid] = cur < items.length - 1 ? cur + 1 : 0;
        updatePoItemHighlight(rid, true);
    } else if (e.key === 'ArrowUp') {
        if (!visible || !items.length) return;
        e.preventDefault();
        const cur = poItemHighlightIdx[rid] ?? -1;
        poItemHighlightIdx[rid] = cur > 0 ? cur - 1 : items.length - 1;
        updatePoItemHighlight(rid, true);
    } else if (e.key === 'Enter') {
        if (!visible || !items.length) return;
        e.preventDefault();
        e.stopPropagation();
        const cur = poItemHighlightIdx[rid] ?? -1;
        selectItem(rid, items[cur >= 0 ? cur : 0]);
    } else if (e.key === 'Escape') {
        if (!visible) return;
        e.preventDefault();
        drop.style.display = 'none';
        poItemHighlightIdx[rid] = -1;
    }
}, true);
document.addEventListener('mousedown', function(e) {
    if (!e.target.closest('.item-dropdown') && !e.target.closest('.item-search')) {
        hideAllDropdowns();
    }
    if (!e.target.closest('#supplierSearchWrap')) {
        closeSupplierDropdown();
    }
}, true);

// Prefill the row's foreign-price box from the item's stored AED/USD reference
// price (matching the PO currency). Purely a reminder/record — editable, no conversion.
function prefillForeign(rid, item) {
    const fEl = document.getElementById('fprice_' + rid);
    if (!fEl) return;
    const cur = document.getElementById('poCurrency').value;
    const ref = cur === 'USD' ? parseFloat(item.price_usd || 0)
              : cur === 'KWD' ? parseFloat(item.purchase_price || 0)
              : parseFloat(item.price_aed || 0);
    fEl.value = ref > 0 ? ref.toFixed(3) : '';
    calcForeign(rid);
}

function selectItem(rid, item) {
    document.querySelector('#' + rid + ' .item-search').value = item.name;
    document.getElementById('itemId_'   + rid).value = item.id;
    const drop = document.getElementById('itemDrop_' + rid);
    if (drop) drop.style.display = 'none';
    poItemHighlightIdx[rid] = -1;
    prefillForeign(rid, item);
    if (poIsForeignCurrency()) {
        document.getElementById('kwdprice_' + rid).value = '';
        const tot = document.getElementById('kwdtotal_' + rid);
        if (tot) tot.value = '';
        recalcTotals();
    } else {
        const kwd = parseFloat(item.purchase_price || 0);
        document.getElementById('kwdprice_' + rid).value = kwd > 0 ? kwd.toFixed(3) : '';
        calcFromPrice(rid, true);
    }
    const rows = document.querySelectorAll('#poTbody tr[id^="porow_"]');
    if (rows[rows.length - 1]?.id === rid) addRow();
    loadPriceHistory(rid, item.id, item.name);
}

function loadPriceHistory(rid, itemId, itemName) {
    // Remove any existing history row for this rid
    document.getElementById('hist_' + rid)?.remove();

    fetch(`?page=purchaseorders&action=itemHistory&item_id=${itemId}`)
        .then(r => r.json())
        .then(rows => {
            if (!rows.length) return;
            const tr = document.getElementById(rid);
            if (!tr) return;

            const cols = tr.querySelectorAll('td').length;
            const histTr = document.createElement('tr');
            histTr.id = 'hist_' + rid;
            histTr.style.cssText = 'background:linear-gradient(135deg,#f0f9ff,#e0f2fe);';

            const tableRows = rows.map(r => {
                const price  = parseFloat(r.unit_price_kwd||0).toFixed(3);
                const fprice = parseFloat(r.unit_price_foreign||0);
                const cur    = (r.currency && r.currency !== 'KWD') ? r.currency : '';
                const foreignCell = (cur && fprice > 0) ? `${cur} ${fprice.toFixed(3)}` : '—';
                const qty   = r.quantity;
                const date  = r.date;
                const sup   = r.supplier;
                const pono  = r.po_no;
                return `<tr>
                    <td style="padding:3px 8px;color:#475569;font-size:0.75rem;">${date}</td>
                    <td style="padding:3px 8px;color:#64748b;font-size:0.75rem;">${pono}</td>
                    <td style="padding:3px 8px;color:#334155;font-size:0.75rem;font-weight:600;">${sup}</td>
                    <td style="padding:3px 8px;text-align:center;color:#475569;font-size:0.75rem;">${qty}</td>
                    <td style="padding:3px 8px;text-align:right;color:#b45309;font-weight:700;font-size:0.75rem;">${foreignCell}</td>
                    <td style="padding:3px 8px;text-align:right;color:#1e3a5f;font-weight:700;font-size:0.75rem;">${parseFloat(r.unit_price_kwd||0) > 0 ? price + ' KWD' : '—'}</td>
                </tr>`;
            }).join('');

            histTr.innerHTML = `<td colspan="${cols}" style="padding:0;">
                <div style="padding:6px 12px 8px 40px;">
                    <div style="font-size:0.7rem;font-weight:700;color:#0284c7;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                        <i class="bi bi-clock-history"></i> Last ${rows.length} purchase${rows.length>1?'s':''} — ${itemName}
                    </div>
                    <table style="width:100%;border-collapse:collapse;max-width:680px;">
                        <thead>
                            <tr style="border-bottom:1px solid #bae6fd;">
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:left;">Date</th>
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:left;">PO No</th>
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:left;">Supplier</th>
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:center;">Qty</th>
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:right;">Foreign</th>
                                <th style="padding:2px 8px;font-size:0.68rem;color:#64748b;font-weight:600;text-align:right;">Unit Price</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                </div>
            </td>`;

            tr.insertAdjacentElement('afterend', histTr);
        });
}

// ── Calculations ─────────────────────────────────────────────────────────────
function round3(n) {
    return Math.round((n + Number.EPSILON) * 1000) / 1000;
}

function poIsForeignCurrency() {
    return document.getElementById('poCurrency')?.value !== 'KWD';
}

function refreshKwdPlaceholders() {
    const ph = poIsForeignCurrency() ? 'after TT' : '0.000';
    document.querySelectorAll('#poTbody .po-cell-input.unit, #poTbody .po-cell-input.total').forEach(function(el) {
        el.placeholder = ph;
    });
}

// snap=true: format the edited field to 3dp (blur / programmatic). snap=false: leave it alone while typing.
function calcFromPrice(rid, snap) {
    const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
    const priceEl = document.getElementById('kwdprice_' + rid);
    const totalEl = document.getElementById('kwdtotal_' + rid);
    const kwdPrice = round3(parseFloat(priceEl?.value || 0));
    const kwdTotal = round3(qty * kwdPrice);
    if (snap && priceEl && priceEl.value !== '' && !Number.isNaN(kwdPrice)) {
        priceEl.value = kwdPrice.toFixed(3);
    }
    if (totalEl) totalEl.value = kwdTotal > 0 ? kwdTotal.toFixed(3) : '';
    recalcTotals();
}

function calcFromTotal(rid, snap) {
    const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 1) || 1;
    const priceEl = document.getElementById('kwdprice_' + rid);
    const totalEl = document.getElementById('kwdtotal_' + rid);
    let kwdTotal = round3(parseFloat(totalEl?.value || 0));
    const kwdPrice = qty > 0 ? round3(kwdTotal / qty) : 0;
    if (priceEl) priceEl.value = kwdPrice > 0 ? kwdPrice.toFixed(3) : '';
    // Only re-snap total from qty × unit when leaving the field (or programmatic).
    if (snap) {
        kwdTotal = round3(qty * kwdPrice);
        if (totalEl) totalEl.value = kwdTotal > 0 ? kwdTotal.toFixed(3) : '';
    }
    recalcTotals();
}

// Foreign price is a manual record only (no KWD conversion); just keep the subtotal in sync.
function calcForeign(rid) {
    recalcTotals();
}

function recalcTotals() {
    let sumK = 0, totalQty = 0;
    document.querySelectorAll('#poTbody tr[id^="porow_"]').forEach(tr => {
        const rid = tr.dataset.rowId; if (!rid) return;
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
        if (document.getElementById('itemId_' + rid)?.value) totalQty += qty;
        sumK += parseFloat(document.getElementById('kwdtotal_' + rid)?.value || 0);
    });
    let sumF = 0;
    document.querySelectorAll('[id^="fprice_porow_"]').forEach(el => {
        const rid = el.id.replace('fprice_', '');
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
        sumF += (parseFloat(el.value || 0) * qty);
    });
    const foreignEl = document.getElementById('subtotalForeignDisplay');
    if (foreignEl) foreignEl.textContent = sumF.toFixed(3);
    const qtyEl = document.getElementById('poGtQtyHint');
    if (qtyEl) qtyEl.textContent = 'Total qty ' + totalQty;
    const grandEl = document.getElementById('grandKwd');
    if (grandEl) grandEl.textContent = (sumK > 0.0005) ? sumK.toFixed(3) : (poIsForeignCurrency() ? '—' : '0.000');
    const gtCard = document.getElementById('poGrandTotalCard');
    if (gtCard) gtCard.classList.toggle('has-amount', sumK > 0.0005);
    checkSaveBtn();
}

function checkSaveBtn() {
    const rows = [...document.querySelectorAll('#poTbody tr[id^="porow_"]')];
    const filled = rows.filter(tr => document.getElementById('itemId_' + tr.id)?.value);
    const pricesOk = filled.length > 0 && filled.every(tr => {
        const rid = tr.id;
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
        if (qty < 1) return false;
        if (poIsForeignCurrency()) {
            return parseFloat(document.getElementById('fprice_' + rid)?.value || 0) > 0;
        }
        return parseFloat(document.getElementById('kwdprice_' + rid)?.value || 0) > 0
            || parseFloat(document.getElementById('kwdtotal_' + rid)?.value || 0) > 0;
    });
    document.getElementById('poSaveBtn').disabled = !pricesOk || !supplierId;
}

// Keep the foreign-price column header label in sync with the chosen currency.
document.getElementById('poCurrency').addEventListener('change', function() {
    document.getElementById('fcurLabel').textContent = this.value;
    const sumLabel = document.getElementById('sumFcurLabel');
    if (sumLabel) sumLabel.textContent = this.value;
    refreshKwdPlaceholders();
    recalcTotals();
});

function focusSupplierField() {
    const el = document.getElementById('supplierSearch');
    if (el) el.focus();
}

document.addEventListener('DOMContentLoaded', () => {
    addRow(true); addRow(true);
    refreshKwdPlaceholders();
    recalcTotals();
    focusSupplierField();
    // Body is visibility:hidden during iq-boot, so the first focus is dropped.
    const prevReveal = window.iqbalReveal;
    window.iqbalReveal = function () {
        if (typeof prevReveal === 'function') prevReveal();
        focusSupplierField();
        setTimeout(focusSupplierField, 40);
    };
    if (!document.documentElement.classList.contains('iq-boot')) {
        setTimeout(focusSupplierField, 0);
    }
});
</script>
