<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px 12px 0 0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.warehouse-select{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:pointer;outline:none;}
.warehouse-select option{background:#1e3a5f;color:#fff;}
.customer-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.customer-search-wrap{position:relative;flex:1;min-width:220px;max-width:340px;}
.customer-search-wrap .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.customer-search-wrap input{width:100%;padding:11px 12px 11px 40px;min-height:44px;border:2px solid #e0e7ff;border-radius:10px;font-size:1.02rem;font-weight:600;color:#1a1a2e;background:#fafbff;transition:all 0.2s;outline:none;}
.customer-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.customer-search-wrap input.selected{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;}
.inv-meta{display:flex;gap:20px;align-items:center;margin-left:auto;flex-wrap:wrap;}
.inv-meta-item{display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;}
.inv-meta-item .label{color:#94a3b8;margin-bottom:2px;font-weight:500;}
.inv-meta-item .value{font-weight:700;color:#1e293b;font-size:0.85rem;}
.inv-meta-item input,.inv-meta-item select{border:1.5px solid #e5e7eb;border-radius:7px;padding:4px 8px;font-size:0.8rem;color:#1e293b;background:#f8fafc;outline:none;font-weight:600;}
.inv-meta-item input:focus,.inv-meta-item select:focus{border-color:#6366f1;}
.items-card{border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:hidden;}
.items-card-header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;}
.items-card-header span{font-size:0.8rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:6px;}
table.items-tbl{width:100%;border-collapse:collapse;font-size:0.83rem;}
table.items-tbl th{padding:9px 10px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.items-tbl td{border-bottom:1px solid #cbd5e1;padding:5px 6px;vertical-align:middle;}
table.items-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.items-tbl tbody tr:hover{background:#f8faff;}
table.items-tbl input,table.items-tbl select{border:none;outline:none;background:transparent;width:100%;font-size:0.83rem;color:#1e293b;padding:3px;}
table.items-tbl input:focus{background:#eff6ff;border-radius:4px;}
table.items-tbl tfoot tr{background:#f8f9ff;}
.col-num{width:36px;text-align:center;color:#94a3b8;}
.col-item{min-width:220px;}
.col-imei{width:50px;text-align:center;}
.col-qty{width:75px;text-align:center;}
.col-price{width:120px;text-align:right;}
.col-amt{width:120px;text-align:right;font-weight:700;}
.col-act{width:36px;text-align:center;}
.imei-btn{background:linear-gradient(135deg,#eff6ff,#e0e7ff);border:1px solid #c7d2fe;color:#6366f1;border-radius:7px;padding:4px 8px;font-size:0.75rem;cursor:pointer;display:inline-flex;align-items:center;gap:3px;font-weight:600;transition:all 0.15s;white-space:nowrap;}
.imei-btn:hover{background:linear-gradient(135deg,#e0e7ff,#c7d2fe);transform:translateY(-1px);box-shadow:0 2px 6px rgba(99,102,241,0.2);}
.imei-btn.has-imei{background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-color:#6ee7b7;color:#059669;}
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;cursor:pointer;border-top:2px dashed #c7d2fe;color:#94a3b8;font-size:0.82rem;font-weight:600;transition:all 0.15s;background:#fff;}
.add-row-strip:hover{background:#f5f7ff;color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:#6366f1;flex-shrink:0;}
.sale-bottom{display:flex;gap:0;border:1px solid #e5e7eb;border-top:none;background:#fff;border-radius:0 0 12px 12px;overflow:hidden;flex-wrap:wrap;}
.sale-bottom-left{flex:1;min-width:260px;padding:16px 20px;border-right:1px solid #f1f5f9;}
.sale-totals{min-width:300px;padding:16px 20px;background:linear-gradient(135deg,#f8faff,#f5f7ff);}
.totals-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #e8edf5;font-size:0.85rem;color:#64748b;}
.totals-row:last-child{border-bottom:none;}
.totals-row.grand{font-size:1.05rem;font-weight:800;color:#1e293b;border-top:2px solid #c7d2fe;padding-top:10px;margin-top:4px;border-bottom:none;}
.totals-row.grand span:last-child{color:#6366f1;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);margin-top:-1px;}
.btn-cancel-sale{padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;}
.btn-save-sale{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-save-sale:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,0.5);}
.btn-print-sale{padding:8px 22px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(5,150,105,0.35);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-print-sale:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,0.45);}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:300px;overflow-y:auto;margin-top:4px;}
.autocomplete-box.item-dropdown{position:fixed;margin-top:0;min-width:380px;width:auto;right:auto;}
.autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;transition:background 0.1s;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
/* IMEI Modal */
.imei-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.imei-modal-overlay.show{display:flex;}
.imei-modal{background:#fff;border-radius:14px;padding:24px 28px;width:100%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);border:1px solid #e0e7ff;}
.imei-modal-title{font-size:1rem;font-weight:700;margin-bottom:18px;display:flex;justify-content:space-between;align-items:flex-start;}
.imei-modal-title .close-x{background:none;border:none;font-size:1.4rem;color:#94a3b8;cursor:pointer;line-height:1;padding:0;}
.imei-input-row{display:flex;gap:8px;align-items:center;margin-bottom:6px;}
.imei-input-row input{flex:1;border:2px solid #e0e7ff;border-radius:8px;padding:9px 12px;font-size:0.9rem;color:#1e293b;font-family:'JetBrains Mono',monospace;letter-spacing:1px;outline:none;}
.imei-input-row input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.imei-confirm-btn{background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;border-radius:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;}
.imei-msg{font-size:0.78rem;padding:4px 8px;border-radius:6px;margin:4px 0 8px;min-height:24px;}
.imei-msg.ok{background:#d1fae5;color:#065f46;}
.imei-msg.err{background:#fee2e2;color:#991b1b;}
.imei-tag{display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #c7d2fe;border-radius:6px;padding:4px 10px;margin:3px;font-size:0.78rem;color:#3730a3;font-family:'JetBrains Mono',monospace;}
.imei-tag .remove{cursor:pointer;color:#94a3b8;}
.imei-tag .remove:hover{color:#ef4444;}
.imei-modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
.btn-close-modal{background:#f1f5f9;border:1.5px solid #e2e8f0;color:#64748b;padding:7px 18px;border-radius:8px;cursor:pointer;font-weight:500;}
.btn-save-modal{background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;padding:7px 22px;border-radius:8px;font-weight:700;cursor:pointer;}
/* Load from invoice box */

</style>

<form method="POST" action="?page=returns&action=store" id="retForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="return_form_nonce" value="<?= htmlspecialchars($returnFormNonce ?? '') ?>">
    <input type="hidden" name="print_mode" id="retPrintMode" value="0">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-arrow-return-left"></i> New Sale Return
        </div>
        <select name="warehouse_id" class="warehouse-select" id="retWhSelect" required>
            <option value="">Select Branch</option>
            <?php foreach ($warehouses as $w): ?>
            <option value="<?= $w['id'] ?>" <?= ($w['is_default'] ?? false) ? 'selected' : '' ?>>
                <?= htmlspecialchars($w['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ② CUSTOMER + META -->
    <div class="customer-bar">
        <div class="customer-search-wrap">
            <i class="bi bi-person-circle search-icon"></i>
            <input type="text" id="retPartySearch" placeholder="Search customer / agent..." autocomplete="off">
            <div class="autocomplete-box" id="retPartyDrop" style="display:none;"></div>
            <input type="hidden" name="party_id" id="retPartyId" required>
            <input type="hidden" name="ref_id" id="refIdInput">
        </div>
        <div id="retPartyBalBadge" style="display:none;padding:6px 14px;border-radius:8px;font-size:0.82rem;font-weight:700;white-space:nowrap;"></div>

        <div class="customer-search-wrap" style="max-width:220px;margin-left:auto;">
            <i class="bi bi-calendar3 search-icon" style="color:#f59e0b;"></i>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" style="padding-left:36px;">
        </div>
    </div>

    <!-- ③ QUICK SCAN BAR -->
    <div style="border:1px solid #e5e7eb;border-top:none;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);padding:12px 20px;display:flex;align-items:center;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            <i class="bi bi-upc-scan" style="font-size:1.3rem;color:#059669;"></i>
            <span style="font-size:0.82rem;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:0.5px;">Quick Scan</span>
        </div>
        <input type="text" id="quickScanInput" placeholder="Scan IMEI barcode — auto-detects model & price..."
               autocomplete="off" style="flex:1;padding:10px 14px;border:2px solid #86efac;border-radius:10px;font-size:0.95rem;
               font-family:'JetBrains Mono',monospace;letter-spacing:1.5px;outline:none;background:#fff;color:#1e293b;font-weight:600;"
               onfocus="this.style.borderColor='#059669';this.style.boxShadow='0 0 0 3px rgba(5,150,105,0.15)'"
               onblur="this.style.borderColor='#86efac';this.style.boxShadow='none'">
        <div id="quickScanMsg" style="font-size:0.82rem;font-weight:600;min-width:200px;padding:6px 12px;border-radius:8px;display:none;"></div>
    </div>

    <!-- ④ ITEMS TABLE -->
    <div class="items-card">
        <div class="items-card-header">
            <span><i class="bi bi-grid-3x3-gap-fill"></i> Return Items</span>
            <span style="font-size:0.75rem;color:#94a3b8;font-weight:400;">
                <span id="retQtyBadge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:20px;font-weight:700;">0 items</span>
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="retItemsTable">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th class="col-item">ITEM</th>
                        <th class="col-imei" title="IMEI Scan">IMEI</th>
                        <th class="col-qty">QTY</th>
                        <th class="col-price">UNIT PRICE</th>
                        <th class="col-amt">TOTAL</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="returnItemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td class="col-qty" style="text-align:center;font-weight:800;color:#4338ca;font-size:0.9rem;" id="retQtyFoot">0</td>
                        <td></td>
                        <td class="col-amt" style="color:#6366f1;font-size:0.9rem;padding-right:10px;" id="retSubFoot">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="add-row-strip" onclick="addReturnRow()">
            <span class="plus-c">+</span> Add Item Row
        </div>
    </div>

    <!-- ④ BOTTOM -->
    <div class="sale-bottom">
        <div class="sale-bottom-left" style="display:flex;align-items:center;">
            <p class="mb-0" style="color:#94a3b8;font-size:0.82rem;font-style:italic;">
                <i class="bi bi-info-circle me-1"></i> Returned stock will be added back to the selected branch.
            </p>
        </div>
        <div class="sale-totals">
            <div class="totals-row grand">
                <span>Total Refund</span>
                <span id="returnTotal">0.000</span>
            </div>
        </div>
    </div>

    <!-- ⑤ SAVE BAR -->
    <div class="save-bar">
        <a href="?page=returns" class="btn-cancel-sale">Cancel</a>
        <button type="submit" class="btn-save-sale" onclick="document.getElementById('retPrintMode').value='0'">
            <i class="bi bi-check-lg"></i> Save Return
        </button>
        <button type="submit" class="btn-print-sale" onclick="document.getElementById('retPrintMode').value='1'">
            <i class="bi bi-printer"></i> Print & Save
        </button>
    </div>

</div>
</form>

<!-- ═══ IMEI MODAL ═══ -->
<div class="imei-modal-overlay" id="retImeiModal">
    <div class="imei-modal">
        <div class="imei-modal-title">
            <div>
                <div style="font-size:0.72rem;color:#94a3b8;font-weight:400;margin-bottom:3px;">Scanning IMEI for:</div>
                <div id="retImeiModalItemName" style="color:#4338ca;"></div>
            </div>
            <button class="close-x" onclick="closeRetImeiModal()">×</button>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label style="font-size:0.83rem;color:#475569;font-weight:600;">IMEI / Serial Number</label>
            <span id="retImeiCount" style="font-size:0.8rem;"></span>
        </div>
        <div class="imei-input-row">
            <input type="text" id="retImeiScanInput" placeholder="Scan or type IMEI (15-18 digits)..." maxlength="18"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();confirmRetImei();}">
            <button type="button" class="imei-confirm-btn" onclick="confirmRetImei()">
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
        <div id="retImeiMsg" class="imei-msg"></div>
        <div id="retImeiTagList" style="flex:1;overflow-y:auto;min-height:60px;max-height:260px;padding:4px 2px;"></div>
        <div class="imei-modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeRetImeiModal()">Cancel</button>
            <button type="button" class="btn-save-modal" onclick="saveRetImeiModal()">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
let returnRowCount  = 0;
let retImeiData     = {};
let retCurrentRow   = null;
let retActiveImeis  = [];
let retCurrentItemName = '';

document.addEventListener('DOMContentLoaded', () => {
    addReturnRow(); addReturnRow();
    document.getElementById('retPartySearch').focus();
});

// ── ADD ROW ──
function addReturnRow(item = null) {
    returnRowCount++;
    const rid = 'rrow_' + returnRowCount;
    retImeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num" style="text-align:center;color:#cbd5e1;">${returnRowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="ret-item-search" placeholder="Search item..." autocomplete="off"
                   id="rSearch_${rid}" value="${item ? item.item_name : ''}" oninput="searchReturnItem(this,'${rid}')">
            <input type="hidden" name="items[${returnRowCount}][item_id]" id="rItemId_${rid}" value="${item ? item.item_id : ''}">
            <div class="autocomplete-box item-dropdown" id="rDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-imei">
            <input type="hidden" name="items[${returnRowCount}][imeis]" id="rImei_${rid}">
            <button type="button" class="imei-btn" id="retImeiBtn_${rid}" onclick="openRetImeiModal('${rid}')">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${returnRowCount}][quantity]" id="rQty_${rid}"
                   value="${item ? item.quantity : 1}" min="1" style="text-align:center;" oninput="calcReturnRow('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" name="items[${returnRowCount}][unit_price]" id="rPrice_${rid}"
                   step="0.001" placeholder="0.000" style="text-align:right;"
                   value="${item ? item.unit_price : ''}" oninput="calcReturnRow('${rid}')">
        </td>
        <td class="col-amt" id="rAmt_${rid}" style="text-align:right;padding-right:8px;color:#6366f1;">0.000</td>
        <td class="col-act">
            <button type="button" onclick="removeReturnRow('${rid}')"
                style="background:none;border:none;color:#c7d2fe;cursor:pointer;font-size:1.1rem;"
                onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#c7d2fe'">×</button>
        </td>
    `;
    document.getElementById('returnItemsBody').appendChild(tr);
    if (item) calcReturnRow(rid);
}

function removeReturnRow(rid) {
    document.getElementById(rid)?.remove();
    delete retImeiData[rid];
    calcReturnTotal();
}

// ── CALC ──
function calcReturnRow(rid) {
    const qty   = parseFloat(document.getElementById('rQty_'   + rid)?.value) || 0;
    const price = parseFloat(document.getElementById('rPrice_' + rid)?.value) || 0;
    document.getElementById('rAmt_' + rid).textContent = (qty * price).toFixed(3);
    calcReturnTotal();
}

function calcReturnTotal() {
    let total = 0, totalQty = 0;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId; if (!rid) return;
        if (!document.getElementById('rItemId_' + rid)?.value) return;
        total    += parseFloat(document.getElementById('rAmt_' + rid)?.textContent) || 0;
        totalQty += parseFloat(document.getElementById('rQty_' + rid)?.value) || 0;
    });
    document.getElementById('returnTotal').textContent  = total.toFixed(3);
    document.getElementById('retSubFoot').textContent   = total.toFixed(3);
    document.getElementById('retQtyFoot').textContent   = totalQty;
    document.getElementById('retQtyBadge').textContent  = totalQty + ' item' + (totalQty !== 1 ? 's' : '');
}

// ── ITEM SEARCH ──
const returnItemStore = {};
let retSearchTimers = {};

function positionDropdown(input, drop) {
    const rect = input.getBoundingClientRect();
    drop.style.top = (rect.bottom + 4) + 'px';
    drop.style.left = rect.left + 'px';
    drop.style.minWidth = Math.max(380, rect.width) + 'px';
}

function searchReturnItem(input, rid) {
    clearTimeout(retSearchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('rDrop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    retSearchTimers[rid] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                returnItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                        <strong>${it.name}</strong> ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <br><small style="color:#94a3b8;">${it.sale_price}${it.has_imei ? ' · <span style="color:#059669;font-weight:600;">IMEI</span>' : ''}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectReturnItem(this.dataset.rid, returnItemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                    });
                });
                positionDropdown(input, drop);
                drop.style.display = 'block';
            });
    }, 250);
}

function getRetImeiRule(row) {
    var name = (window.retRowItemNameMap && window.retRowItemNameMap[row]) || '';
    if (name.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13' };
    return { min: 15, max: 18, label: '15-18' };
}

function selectReturnItem(rid, item) {
    document.getElementById('rSearch_' + rid).value = item.name;
    document.getElementById('rItemId_' + rid).value = item.id;
    document.getElementById('rPrice_'  + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('rDrop_'   + rid).style.display = 'none';
    if (!window.retRowItemNameMap) window.retRowItemNameMap = {};
    window.retRowItemNameMap[rid] = (item.name || '').toLowerCase();
    // Reset any highlight from quick-scan unknown IMEI
    const searchEl = document.getElementById('rSearch_' + rid);
    searchEl.style.borderColor = '';
    searchEl.style.background = '';
    searchEl.placeholder = 'Search item...';
    calcReturnRow(rid);
    if (item.has_imei && (!retImeiData[rid] || retImeiData[rid].length === 0)) {
        setTimeout(() => openRetImeiModal(rid, item.name), 120);
    }
    const rows = document.querySelectorAll('#returnItemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addReturnRow();
    // If this was a quick-scan row, refocus the scan bar
    if (retImeiData[rid] && retImeiData[rid].length > 0) {
        setTimeout(() => quickScanInput.focus(), 150);
    }
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item')) document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    if (!e.target.closest('.customer-search-wrap')) document.getElementById('retPartyDrop').style.display = 'none';
});
window.addEventListener('scroll', () => {
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
}, true);

// ── CUSTOMER SEARCH ──
const retPartyStore = {};
let retPartyTimer;
document.getElementById('retPartySearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('retPartyId').value = '';
    document.getElementById('retPartyBalBadge').style.display = 'none';
    clearTimeout(retPartyTimer);
    const q = this.value.trim();
    const drop = document.getElementById('retPartyDrop');
    if (q.length < 1) { drop.style.display = 'none'; return; }
    retPartyTimer = setTimeout(() => {
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(parties => {
                if (!parties.length) { drop.style.display = 'none'; return; }
                retPartyStore['results'] = parties;
                drop.innerHTML = parties.map((p, idx) => {
                    const bal = parseFloat(p.balance || 0);
                    const balStr = bal > 0.001
                        ? `<span style="color:#ef4444;font-weight:700;">${bal.toFixed(3)}</span>`
                        : bal < -0.001
                        ? `<span style="color:#6366f1;font-weight:700;">-${Math.abs(bal).toFixed(3)}</span>`
                        : `<span style="color:#10b981;">Clear</span>`;
                    return `<div class="autocomplete-item" data-idx="${idx}" style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong>${p.name}</strong>
                            ${p.party_code ? `<small style="color:#94a3b8;"> · ${p.party_code}</small>` : ''}
                            ${p.phone ? `<br><small style="color:#94a3b8;">${p.phone}</small>` : ''}
                        </div>
                        <div style="text-align:right;font-size:0.8rem;">
                            ${balStr}
                        </div>
                    </div>`;
                }).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const p = retPartyStore['results'][parseInt(this.dataset.idx)];
                        document.getElementById('retPartySearch').value = p.name;
                        document.getElementById('retPartySearch').classList.add('selected');
                        document.getElementById('retPartyId').value = p.id;
                        drop.style.display = 'none';
                        // Show balance badge
                        const badge = document.getElementById('retPartyBalBadge');
                        const bal = parseFloat(p.balance || 0);
                        if (bal > 0.001) {
                            badge.textContent = 'Balance: ' + bal.toFixed(3);
                            badge.style.background = 'rgba(239,68,68,0.1)';
                            badge.style.color = '#ef4444';
                        } else if (bal < -0.001) {
                            badge.textContent = 'Balance: -' + Math.abs(bal).toFixed(3);
                            badge.style.background = 'rgba(99,102,241,0.1)';
                            badge.style.color = '#6366f1';
                        } else {
                            badge.textContent = '✓ Clear';
                            badge.style.background = 'rgba(16,185,129,0.1)';
                            badge.style.color = '#10b981';
                        }
                        badge.style.display = 'block';
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
});

// ── LOAD FROM INVOICE ──
let invoiceTimer;
function retEscapeHtml(text) {
    const s = document.createElement('span');
    s.textContent = text == null ? '' : String(text);
    return s.innerHTML;
}
function searchInvoice(q) {
    clearTimeout(invoiceTimer);
    const drop = document.getElementById('invoiceDrop');
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; return; }
    const whId = document.getElementById('retWhSelect').value;
    invoiceTimer = setTimeout(() => {
        fetch(`?page=returns&action=searchSales&q=${encodeURIComponent(q)}&warehouse_id=${whId}`)
            .then(r => r.json())
            .then(invoices => {
                if (!invoices.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = invoices.map(inv => {
                    const invNo = retEscapeHtml(inv.invoice_no);
                    const pnEsc = retEscapeHtml(inv.party_name);
                    const dtEsc = retEscapeHtml(inv.date);
                    const pid = parseInt(inv.party_id, 10) || 0;
                    return `<div class="autocomplete-item ret-inv-pick" data-sale-id="${inv.id}" data-party-id="${pid}" data-pname="${encodeURIComponent(inv.party_name || '')}">
                        <strong>${invNo}</strong>
                        <small style="float:right;color:#6366f1;font-weight:600;">${parseFloat(inv.grand_total).toFixed(3)} KWD</small>
                        <br><small style="color:#94a3b8;">${pnEsc} · ${dtEsc}</small>
                    </div>`;
                }).join('');
                drop.querySelectorAll('.ret-inv-pick').forEach(el => {
                    el.addEventListener('mousedown', function(ev) {
                        ev.preventDefault();
                        document.getElementById('refIdInput').value = this.dataset.saleId;
                        document.getElementById('retPartyId').value = this.dataset.partyId;
                        const ps = document.getElementById('retPartySearch');
                        if (ps) {
                            ps.value = decodeURIComponent(this.dataset.pname || '');
                            ps.classList.add('selected');
                        }
                        const invEl = document.getElementById('invoiceSearch');
                        if (invEl) {
                            const strong = this.querySelector('strong');
                            invEl.value = strong ? strong.textContent : '';
                            invEl.style.borderColor = '#10b981';
                            invEl.style.background  = '#f0fdf4';
                        }
                        drop.style.display = 'none';
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
}
if (document.getElementById('invoiceSearch')) {
    document.getElementById('invoiceSearch').addEventListener('blur', () => {
        setTimeout(() => { var d = document.getElementById('invoiceDrop'); if (d) d.style.display = 'none'; }, 200);
    });
}


// ── IMEI MODAL ──
function openRetImeiModal(rid, itemName) {
    retCurrentRow      = rid;
    retCurrentItemName = itemName || document.getElementById('rSearch_' + rid)?.value || 'Item';
    retActiveImeis     = [...(retImeiData[rid] || [])];
    document.getElementById('retImeiModalItemName').textContent = retCurrentItemName;
    renderRetImeiTags();
    document.getElementById('retImeiModal').classList.add('show');
    document.getElementById('retImeiScanInput').value = '';
    document.getElementById('retImeiMsg').innerHTML   = '';
    setTimeout(() => document.getElementById('retImeiScanInput').focus(), 80);
}

function closeRetImeiModal() { document.getElementById('retImeiModal').classList.remove('show'); }

function getAllRetImeis(excludeRow) {
    const all = [];
    Object.keys(retImeiData).forEach(r => { if (r !== excludeRow) all.push(...retImeiData[r]); });
    return all;
}

function confirmRetImei() {
    const input = document.getElementById('retImeiScanInput');
    const imei  = input.value.trim();
    if (!imei) return;
    const rule = getRetImeiRule(retCurrentRow);
    if (!/^\d+$/.test(imei) || imei.length < rule.min || imei.length > rule.max) {
        showRetImeiMsg('IMEI must be ' + rule.label + ' digits.', 'err'); input.select(); return;
    }
    if (retActiveImeis.includes(imei)) { showRetImeiMsg('Already added.', 'err'); input.select(); return; }
    if (getAllRetImeis(retCurrentRow).includes(imei)) { showRetImeiMsg('IMEI used in another row.', 'err'); input.select(); return; }
    retActiveImeis.push(imei); renderRetImeiTags();
    showRetImeiMsg('✓ IMEI added.', 'ok');
    input.value = ''; input.focus();
}

function showRetImeiMsg(msg, type) {
    const el = document.getElementById('retImeiMsg');
    el.className = 'imei-msg ' + type; el.innerHTML = msg;
    if (type === 'ok') setTimeout(() => el.innerHTML = '', 2000);
}

function renderRetImeiTags() {
    const qty = parseInt(document.getElementById('rQty_' + retCurrentRow)?.value) || 0;
    const entered = retActiveImeis.length;
    document.getElementById('retImeiCount').innerHTML =
        `<span style="color:${entered >= qty && qty > 0 ? '#059669' : '#f59e0b'};font-weight:600;">${entered} entered</span>` +
        (qty > 0 ? ` <span style="color:#94a3b8;">/ ${qty} needed</span>` : '');
    document.getElementById('retImeiTagList').innerHTML = retActiveImeis.map((im, i) => `
        <span class="imei-tag">${im} <span class="remove" onclick="removeRetImei(${i})">×</span></span>
    `).join('');
}

function removeRetImei(idx) { retActiveImeis.splice(idx, 1); renderRetImeiTags(); }

function saveRetImeiModal() {
    if (!retCurrentRow) return;
    const qty = parseInt(document.getElementById('rQty_' + retCurrentRow)?.value) || 0;
    if (qty > 0 && retActiveImeis.length !== qty) {
        if (!confirm(`${retActiveImeis.length} IMEI(s) entered but quantity is ${qty}. Quantity will update. Continue?`)) return;
    }
    retImeiData[retCurrentRow] = [...retActiveImeis];
    document.getElementById('rImei_' + retCurrentRow).value = retActiveImeis.join('\n');
    const btn = document.getElementById('retImeiBtn_' + retCurrentRow);
    if (btn) {
        btn.classList.toggle('has-imei', retActiveImeis.length > 0);
        btn.innerHTML = retActiveImeis.length > 0 ? `<i class="bi bi-upc-scan"></i> ${retActiveImeis.length}` : `<i class="bi bi-upc-scan"></i>`;
    }
    const qtyField = document.getElementById('rQty_' + retCurrentRow);
    if (qtyField && retActiveImeis.length > 0) { qtyField.value = retActiveImeis.length; calcReturnRow(retCurrentRow); }
    closeRetImeiModal();
}

document.getElementById('retForm').addEventListener('submit', function(e) {
    // Client-side submit lock (server also validates one-time nonce)
    if (this.dataset.submitting === '1') { e.preventDefault(); return; }

    if (!document.getElementById('retPartyId').value) { e.preventDefault(); alert('Please select a customer.'); return; }
    let hasItem = false;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (rid && document.getElementById('rItemId_' + rid)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); return; }

    if (e.defaultPrevented) return;
    this.dataset.submitting = '1';
    this.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => { btn.disabled = true; });
});

// ── QUICK SCAN ──
const quickScanInput = document.getElementById('quickScanInput');
const quickScanMsg   = document.getElementById('quickScanMsg');
let quickScanBusy = false;

function showScanMsg(msg, type) {
    quickScanMsg.style.display = 'block';
    quickScanMsg.textContent = msg;
    if (type === 'ok') {
        quickScanMsg.style.background = '#d1fae5';
        quickScanMsg.style.color = '#065f46';
    } else if (type === 'warn') {
        quickScanMsg.style.background = '#fef3c7';
        quickScanMsg.style.color = '#92400e';
    } else {
        quickScanMsg.style.background = '#fee2e2';
        quickScanMsg.style.color = '#991b1b';
    }
    if (type === 'ok') setTimeout(() => { quickScanMsg.style.display = 'none'; }, 3000);
}

// Check if IMEI is already in any row
function isImeiAlreadyAdded(imei) {
    for (const rid in retImeiData) {
        if (retImeiData[rid].includes(imei)) return true;
    }
    return false;
}

// Find if there's already a row for this item_id with room to add more IMEIs
function findExistingRowForItem(itemId) {
    let found = null;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid) return;
        if (document.getElementById('rItemId_' + rid)?.value == itemId) {
            found = rid;
        }
    });
    return found;
}

quickScanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        processQuickScan();
    }
});

// Also auto-trigger when 15-18 digits are entered (barcode scanner)
quickScanInput.addEventListener('input', function() {
    const val = this.value.trim();
    if (/^\d{15,18}$/.test(val)) {
        setTimeout(() => processQuickScan(), 100);
    }
});

function processQuickScan() {
    const imei = quickScanInput.value.trim();
    if (!imei) return;
    if (quickScanBusy) return;

    if (isImeiAlreadyAdded(imei)) {
        showScanMsg('⚠ IMEI already added in this return.', 'err');
        quickScanInput.select();
        return;
    }

    quickScanBusy = true;
    quickScanInput.style.borderColor = '#fbbf24';

    fetch(`?page=returns&action=lookupImei&imei=${encodeURIComponent(imei)}`)
        .then(r => r.json())
        .then(data => {
            quickScanBusy = false;
            quickScanInput.style.borderColor = '#86efac';

            // Rejected entirely (already returned, invalid, etc.)
            if (!data.accepted) {
                showScanMsg('✗ ' + data.message, 'err');
                quickScanInput.select();
                return;
            }

            // IMEI found in system — auto-fill item + price
            if (data.found) {
                if (data.sale_id) {
                    document.getElementById('refIdInput').value = String(data.sale_id);
                }
                if (data.party_id) {
                    document.getElementById('retPartyId').value = String(data.party_id);
                    const ps = document.getElementById('retPartySearch');
                    if (ps && data.party_name) {
                        ps.value = data.party_name;
                        ps.classList.add('selected');
                    }
                }
                const existingRid = findExistingRowForItem(data.item_id);

                if (existingRid) {
                    if (!retImeiData[existingRid]) retImeiData[existingRid] = [];
                    retImeiData[existingRid].push(data.imei);
                    document.getElementById('rImei_' + existingRid).value = retImeiData[existingRid].join('\n');
                    document.getElementById('rQty_' + existingRid).value = retImeiData[existingRid].length;
                    const btn = document.getElementById('retImeiBtn_' + existingRid);
                    if (btn) {
                        btn.classList.add('has-imei');
                        btn.innerHTML = `<i class="bi bi-upc-scan"></i> ${retImeiData[existingRid].length}`;
                    }
                    calcReturnRow(existingRid);
                } else {
                    // Use first empty row if available, otherwise add new one
                    let targetRid = null;
                    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
                        const rowId = tr.dataset.rowId;
                        if (!rowId || targetRid) return;
                        if (!document.getElementById('rItemId_' + rowId)?.value) targetRid = rowId;
                    });
                    if (!targetRid) {
                        addReturnRow();
                        const rows = document.querySelectorAll('#returnItemsBody tr');
                        targetRid = rows[rows.length - 1].dataset.rowId;
                    }
                    document.getElementById('rSearch_' + targetRid).value = data.item_name;
                    document.getElementById('rItemId_' + targetRid).value = data.item_id;
                    document.getElementById('rPrice_'  + targetRid).value = parseFloat(data.sale_price).toFixed(3);
                    if (!window.retRowItemNameMap) window.retRowItemNameMap = {};
                    window.retRowItemNameMap[targetRid] = (data.item_name || '').toLowerCase();
                    document.getElementById('rQty_' + targetRid).value = 1;
                    retImeiData[targetRid] = [data.imei];
                    document.getElementById('rImei_' + targetRid).value = data.imei;
                    const btn = document.getElementById('retImeiBtn_' + targetRid);
                    if (btn) {
                        btn.classList.add('has-imei');
                        btn.innerHTML = `<i class="bi bi-upc-scan"></i> 1`;
                    }
                    calcReturnRow(targetRid);
                }

                const invoice = data.sold_invoice ? ` (${data.sold_invoice})` : '';
                showScanMsg(`✓ ${data.item_name}${invoice} — ${data.sale_price} ${currency}`, 'ok');
                quickScanInput.value = '';
                quickScanInput.focus();
                return;
            }

            // IMEI NOT in system — accepted, cashier picks item manually
            // Use first empty row if available, otherwise add new one
            let rid = null;
            document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
                const rowId = tr.dataset.rowId;
                if (!rowId || rid) return;
                if (!document.getElementById('rItemId_' + rowId)?.value) rid = rowId;
            });
            if (!rid) {
                addReturnRow();
                const rows = document.querySelectorAll('#returnItemsBody tr');
                rid = rows[rows.length - 1].dataset.rowId;
            }

            // Pre-attach the IMEI to this new row
            retImeiData[rid] = [data.imei];
            document.getElementById('rImei_' + rid).value = data.imei;
            document.getElementById('rQty_' + rid).value = 1;
            const btn = document.getElementById('retImeiBtn_' + rid);
            if (btn) {
                btn.classList.add('has-imei');
                btn.innerHTML = `<i class="bi bi-upc-scan"></i> 1`;
            }

            // Highlight the item search field so cashier picks the model
            const searchInput = document.getElementById('rSearch_' + rid);
            if (searchInput) {
                searchInput.style.borderColor = '#f59e0b';
                searchInput.style.background = '#fefce8';
                searchInput.placeholder = '← Select item model for scanned IMEI...';
                searchInput.focus();
            }

            showScanMsg('⚠ IMEI accepted — now select the item model ↓', 'warn');
            quickScanInput.value = '';
        })
        .catch(err => {
            quickScanBusy = false;
            quickScanInput.style.borderColor = '#86efac';
            showScanMsg('Network error — try again.', 'err');
        });
}

const currency = '<?= APP_CURRENCY ?>';
</script>
