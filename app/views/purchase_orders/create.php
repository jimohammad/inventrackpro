<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px 12px 0 0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.warehouse-select{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:pointer;outline:none;}
.warehouse-select option{background:#1e3a5f;color:#fff;}
.customer-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.customer-search-wrap{position:relative;flex:1;min-width:220px;max-width:340px;}
.customer-search-wrap .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.customer-search-wrap input{width:100%;padding:9px 12px 9px 36px;border:2px solid #e0e7ff;border-radius:10px;font-size:0.875rem;color:#1a1a2e;background:#fafbff;transition:all 0.2s;outline:none;}
.customer-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.customer-search-wrap input.selected{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;}
.items-card{border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:visible;}
.items-card-header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;}
.items-card-header span{font-size:0.8rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:6px;}
table.items-tbl{width:100%;border-collapse:collapse;font-size:0.83rem;}
table.items-tbl th{padding:9px 10px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.items-tbl td{border-bottom:1px solid #cbd5e1;padding:5px 6px;vertical-align:middle;}
table.items-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.items-tbl tbody tr:hover{background:#f8faff;}
table.items-tbl input{border:none;outline:none;background:transparent;width:100%;font-size:0.83rem;color:#1e293b;padding:3px;}
table.items-tbl input:focus{background:#eff6ff;border-radius:4px;}
table.items-tbl tfoot tr{background:#f8f9ff;}
.col-num{width:32px;text-align:center;color:#cbd5e1;}
.col-item{min-width:200px;position:relative;}
.col-qty{width:75px;text-align:center;}
.col-price{width:130px;text-align:right;}
.col-ktotal{width:130px;text-align:right;}
.col-act{width:32px;text-align:center;}
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;cursor:pointer;border-top:2px dashed #c7d2fe;color:#94a3b8;font-size:0.82rem;font-weight:600;transition:all 0.15s;background:#fff;}
.add-row-strip:hover{background:#f5f7ff;color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:#6366f1;flex-shrink:0;}
.sale-bottom{display:flex;gap:0;border:1px solid #e5e7eb;border-top:none;background:#fff;border-radius:0 0 12px 12px;overflow:hidden;flex-wrap:wrap;}
.sale-bottom-left{flex:1;min-width:260px;padding:16px 20px;border-right:1px solid #f1f5f9;}
.sale-totals{min-width:320px;padding:16px 20px;background:linear-gradient(135deg,#f8faff,#f5f7ff);}
.totals-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #e8edf5;font-size:0.85rem;color:#64748b;}
.totals-row:last-child{border-bottom:none;}
.totals-row.grand{font-size:1.05rem;font-weight:800;color:#1e293b;border-top:2px solid #c7d2fe;padding-top:10px;margin-top:4px;border-bottom:none;}
.totals-row.grand span:last-child{color:#6366f1;}
.totals-row input{width:130px;text-align:right;border:1.5px solid #e0e7ff;border-radius:7px;padding:3px 8px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;}
.totals-row input:focus{border-color:#6366f1;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);margin-top:-1px;}
.btn-save-sale{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-save-sale:hover{transform:translateY(-1px);}
.autocomplete-box{position:fixed;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:220px;overflow-y:auto;}
.autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
</style>

<form method="POST" action="?page=purchaseorders&action=store" id="poForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="party_id" id="partyIdInput">
    <input type="hidden" name="currency" value="KWD">
    <input type="hidden" name="exchange_rate" value="1">

<div class="sale-wrap">

    <!-- TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-file-earmark-text"></i> New Purchase Order
        </div>
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;">
                <span style="color:rgba(255,255,255,0.6);margin-bottom:2px;">Branch</span>
                <select name="warehouse_id" class="warehouse-select" id="whSelect">
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= Auth::warehouseId() == $wh['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- SUPPLIER + META -->
    <div class="customer-bar">
        <div class="customer-search-wrap">
            <i class="bi bi-building search-icon"></i>
            <input type="text" id="supplierSearch" placeholder="Search supplier..." autocomplete="off">
            <div class="autocomplete-box" id="supplierDrop" style="display:none;"></div>
        </div>
        <div class="customer-search-wrap" style="max-width:260px;">
            <i class="bi bi-file-earmark-text search-icon" style="color:#f59e0b;"></i>
            <input type="text" name="supplier_ref" placeholder="Proforma / Ref No" style="padding-left:36px;">
        </div>
        <div class="customer-search-wrap" style="max-width:180px;margin-left:auto;">
            <i class="bi bi-hash search-icon" style="color:#6366f1;"></i>
            <input type="text" value="<?= $nextPoNo ?>" readonly
                style="padding-left:36px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#6366f1;font-weight:700;letter-spacing:0.5px;cursor:default;">
        </div>
        <div class="customer-search-wrap" style="max-width:180px;">
            <i class="bi bi-calendar3 search-icon" style="color:#f59e0b;"></i>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" style="padding-left:36px;">
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="items-card">
        <div class="items-card-header">
            <span><i class="bi bi-list-ul"></i> Order Items</span>
            <span id="poQtyBadge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:20px;font-weight:700;font-size:0.78rem;">0 rows</span>
        </div>
        <table class="items-tbl">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty" style="text-align:center;">Qty</th>
                    <th class="col-price" style="text-align:right;">Price (KWD)</th>
                    <th class="col-ktotal" style="text-align:right;">Total (KWD)</th>
                    <th class="col-act"></th>
                </tr>
            </thead>
            <tbody id="poTbody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                    <td style="padding:8px 6px;font-size:0.7rem;color:#94a3b8;text-align:right;">SUBTOTAL</td>
                    <td style="text-align:right;font-weight:700;color:#6366f1;padding:8px 6px;" id="subtotalKwd">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <div class="add-row-strip" onclick="addRow()">
            <span class="plus-c"><i class="bi bi-plus"></i></span>
            Click to add another item row
        </div>
    </div>

    <!-- BOTTOM -->
    <div class="sale-bottom">
        <div class="sale-bottom-left">
            <label style="font-size:0.75rem;color:#94a3b8;font-weight:600;display:block;margin-bottom:6px;">Notes</label>
            <textarea name="notes" rows="3"
                style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:0.83rem;resize:none;outline:none;color:#475569;"
                placeholder="Any notes about this order..."></textarea>
        </div>
        <div class="sale-totals">
            <div class="totals-row">
                <span>Subtotal (KWD)</span>
                <span id="totalKwd2" style="font-weight:600;">0.000</span>
            </div>
            <div class="totals-row">
                <span>Pay From Account</span>
                <select name="account_id" id="payAccount" style="width:160px;padding:4px 8px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:0.82rem;font-weight:600;color:#1e293b;outline:none;">
                    <option value="">— None / On Credit —</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="totals-row">
                <span>Amount Paid (KWD)</span>
                <input type="number" name="paid_kwd" id="paidKwd" step="0.001" min="0" value="0"
                    oninput="recalcTotals();">
            </div>
            <div class="totals-row grand">
                <span>Total in KWD</span>
                <span id="grandKwd">0.000 KWD</span>
            </div>
        </div>
    </div>

    <!-- SAVE BAR -->
    <div class="save-bar">
        <a href="?page=purchaseorders"
           style="padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;text-decoration:none;display:inline-flex;align-items:center;">
            Cancel
        </a>
        <button type="submit" class="btn-save-sale" id="poSaveBtn" disabled>
            <i class="bi bi-check-lg"></i> Save Purchase Order
        </button>
    </div>

</div>
</form>

<script>
let rowCount   = 0;
let supplierId = 0;
const itemStore    = {};
const searchTimers = {};

// ── Supplier autocomplete ────────────────────────────────────────────────────
const suppliers = <?= json_encode($suppliers) ?>;

document.getElementById('supplierSearch').addEventListener('input', function() {
    const q    = this.value.trim().toLowerCase();
    const drop = document.getElementById('supplierDrop');
    if (!q) { drop.style.display = 'none'; return; }
    const matches = suppliers.filter(s => s.name.toLowerCase().includes(q)).slice(0, 10);
    if (!matches.length) { drop.style.display = 'none'; return; }
    drop.innerHTML = matches.map(s =>
        `<div class="autocomplete-item" onmousedown="selectSupplier(${s.id},'${s.name.replace(/'/g,"\\'")}')"> ${s.name}</div>`
    ).join('');
    drop.style.display = 'block';
});
document.getElementById('supplierSearch').addEventListener('blur', () => {
    setTimeout(() => document.getElementById('supplierDrop').style.display = 'none', 200);
});
function selectSupplier(id, name) {
    supplierId = id;
    document.getElementById('partyIdInput').value = id;
    const inp = document.getElementById('supplierSearch');
    inp.value = name; inp.className = 'selected';
    document.getElementById('supplierDrop').style.display = 'none';
    checkSaveBtn();
}

// ── Add row ────────────────────────────────────────────────────────────────
function addRow() {
    rowCount++;
    const rid = 'porow_' + rowCount;
    const tr  = document.createElement('tr');
    tr.id = rid;
    tr.innerHTML = `
        <td class="col-num">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="item-search" placeholder="Search item..."
                autocomplete="off"
                oninput="searchItem(this,'${rid}')"
                onblur="hideItemDrop('${rid}')">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
        </td>
        <td class="col-qty">
            <input type="number" name="items[${rowCount}][quantity]" id="qty_${rid}"
                value="1" min="1" style="text-align:center;" oninput="calcFromPrice('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" name="items[${rowCount}][kwd_price]" id="kwdprice_${rid}"
                value="" step="0.001" placeholder="Unit" style="text-align:right;font-weight:600;color:#1e3a5f;"
                oninput="calcFromPrice('${rid}')">
        </td>
        <td class="col-ktotal">
            <input type="number" name="items[${rowCount}][kwd_total]" id="kwdtotal_${rid}"
                value="" step="0.001" placeholder="Total" style="text-align:right;font-weight:700;color:#6366f1;border:none;background:transparent;width:100%;outline:none;"
                oninput="calcFromTotal('${rid}')">
        </td>
        <td class="col-act">
            <button type="button" onclick="removeRow('${rid}')"
                style="background:none;border:none;color:#c7d2fe;cursor:pointer;font-size:1.1rem;"
                onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#c7d2fe'">×</button>
        </td>
        <input type="hidden" name="items[${rowCount}][unit_price]" value="0">
    `;
    document.getElementById('poTbody').appendChild(tr);
    tr.querySelector('.item-search').focus();
}

function removeRow(rid) {
    document.getElementById('hist_' + rid)?.remove();
    document.getElementById(rid)?.remove();
    delete itemStore[rid];
    renumber(); recalcTotals();
}
function renumber() {
    let i = 1;
    document.querySelectorAll('#poTbody tr').forEach(tr => {
        tr.querySelector('.col-num').textContent = i++;
    });
}

// ── Item search per row ──────────────────────────────────────────────────────
function getOrCreateDrop(rid) {
    var drop = document.getElementById('itemDrop_' + rid);
    if (!drop) {
        drop = document.createElement('div');
        drop.id = 'itemDrop_' + rid;
        drop.className = 'autocomplete-box';
        drop.style.display = 'none';
        document.body.appendChild(drop);
    }
    return drop;
}

function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    const q    = input.value.trim();
    const drop = getOrCreateDrop(rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    const whId = document.getElementById('whSelect').value;
    searchTimers[rid] = setTimeout(() => {
        fetch(`?page=purchaseorders&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${whId}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
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
                        <strong>${it.name}</strong>
                        ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <small style="float:right;color:#6366f1;font-weight:600;">KWD ${parseFloat(it.purchase_price||0).toFixed(3)}</small>
                        <br><small style="color:#94a3b8;">Stock: ${it.current_stock}</small>
                        ${foreignBadges ? `&nbsp;${foreignBadges}` : ''}
                    </div>`;
                }).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectItem(this.dataset.rid, itemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                    });
                });
                // Position fixed — above or below based on space
                var inputEl = document.querySelector('#' + rid + ' .item-search');
                var rect = inputEl.getBoundingClientRect();
                var spaceBelow = window.innerHeight - rect.bottom;
                drop.style.left = rect.left + 'px';
                drop.style.width = Math.max(380, rect.width) + 'px';
                if (spaceBelow < 240) {
                    drop.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                    drop.style.top = 'auto';
                } else {
                    drop.style.top = (rect.bottom + 4) + 'px';
                    drop.style.bottom = 'auto';
                }
                drop.style.display = 'block';
            });
    }, 250);
}

function hideItemDrop(rid) {
    setTimeout(() => {
        const d = getOrCreateDrop(rid);
        d.style.display = 'none';
    }, 200);
}
function hideAllDropdowns() {
    document.querySelectorAll('.autocomplete-box').forEach(d => d.style.display = 'none');
}
window.addEventListener('scroll', hideAllDropdowns, true);

function selectItem(rid, item) {
    const kwd = parseFloat(item.purchase_price || 0);
    document.querySelector('#' + rid + ' .item-search').value = item.name;
    document.getElementById('itemId_'   + rid).value = item.id;
    document.getElementById('kwdprice_' + rid).value = kwd.toFixed(3);
    document.getElementById('itemDrop_' + rid).style.display = 'none';
    calcFromPrice(rid);
    const rows = document.querySelectorAll('#poTbody tr');
    if (rows[rows.length - 1]?.id === rid) addRow();
    // Load price history for this item
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
                const price = parseFloat(r.unit_price_kwd||0).toFixed(3);
                const qty   = r.quantity;
                const date  = r.date;
                const sup   = r.supplier;
                const pono  = r.po_no;
                return `<tr>
                    <td style="padding:3px 8px;color:#475569;font-size:0.75rem;">${date}</td>
                    <td style="padding:3px 8px;color:#64748b;font-size:0.75rem;">${pono}</td>
                    <td style="padding:3px 8px;color:#334155;font-size:0.75rem;font-weight:600;">${sup}</td>
                    <td style="padding:3px 8px;text-align:center;color:#475569;font-size:0.75rem;">${qty}</td>
                    <td style="padding:3px 8px;text-align:right;color:#1e3a5f;font-weight:700;font-size:0.75rem;">${price} KWD</td>
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
function calcFromPrice(rid) {
    const qty      = parseFloat(document.getElementById('qty_'      + rid)?.value || 0);
    const kwdPrice = parseFloat(document.getElementById('kwdprice_' + rid)?.value || 0);
    const kwdTotal = qty * kwdPrice;
    document.getElementById('kwdtotal_' + rid).value = kwdTotal > 0 ? kwdTotal.toFixed(3) : '';
    recalcTotals();
}

function calcFromTotal(rid) {
    const qty      = parseFloat(document.getElementById('qty_'      + rid)?.value || 1);
    const kwdTotal = parseFloat(document.getElementById('kwdtotal_' + rid)?.value || 0);
    const kwdPrice = qty > 0 ? kwdTotal / qty : 0;
    document.getElementById('kwdprice_' + rid).value = kwdPrice > 0 ? kwdPrice.toFixed(3) : '';
    recalcTotals();
}

function recalcTotals() {
    let sumK = 0;
    document.querySelectorAll('[id^="kwdtotal_porow_"]').forEach(el => {
        sumK += parseFloat(el.value || 0);
    });
    document.getElementById('subtotalKwd').textContent = sumK.toFixed(3);
    document.getElementById('totalKwd2').textContent   = sumK.toFixed(3);
    document.getElementById('grandKwd').textContent    = sumK.toFixed(3) + ' KWD';
    const totalRows = document.querySelectorAll('#poTbody tr').length;
    document.getElementById('poQtyBadge').textContent = totalRows + ' row' + (totalRows !== 1 ? 's' : '');
    checkSaveBtn();
}

function checkSaveBtn() {
    const hasItems = [...document.querySelectorAll('[id^="itemId_porow_"]')].some(el => el.value !== '');
    document.getElementById('poSaveBtn').disabled = !hasItems || !supplierId;
}

// Start with 2 empty rows
addRow(); addRow();
</script>
