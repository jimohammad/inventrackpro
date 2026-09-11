<style>
/* ═══ SHARED WITH SALE PAGE ═══ */
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#1e3a5f;border-radius:0;position:sticky;top:58px;z-index:90;}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.sale-composer{background:#fff;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;border-top:none;border-bottom:none;}
.sale-composer-row{
    display:grid;
    grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) 148px 150px;
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
.sale-chip-date{padding:3px 10px 3px 12px;}
.sale-chip-date input[type="date"]{
    width:100%;border:none;background:transparent;outline:none;padding:0;height:auto;min-height:0;
    font-size:0.82rem;font-weight:700;color:#334155;color-scheme:light;
}
@media (max-width:800px){
    .sale-composer-row{grid-template-columns:1fr 1fr;}
    .sale-field-primary{grid-column:1/-1;}
    .gt-card{width:100%;height:64px;min-height:64px;}
    #purGrandDisplay{font-size:1.25rem;}
    .gt-qty{font-size:0.85rem;}
}
.items-card{border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;border-top:none;border-bottom:1px solid #e5e7eb;border-radius:0;background:#fff;overflow:visible;}
table.items-tbl{width:100%;table-layout:fixed;border-collapse:collapse;border-spacing:0;font-size:0.92rem;border:1px solid #e2e8f0;}
table.items-tbl th,table.items-tbl td{box-shadow:none !important;}
table.items-tbl th{padding:8px 8px;font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#4338ca;background:#eef2ff;white-space:nowrap;border:1px solid #c7d2fe !important;}
table.items-tbl th.col-num{color:#6366f1;padding-left:8px;}
table.items-tbl th.col-act{padding-right:8px;}
table.items-tbl tbody td{padding:0 !important;vertical-align:middle;background:#fff;border:1px solid #e2e8f0 !important;height:48px;}
table.items-tbl tbody tr{background:#fff;}
table.items-tbl tbody tr:hover td.col-num,
table.items-tbl tbody tr:hover td.col-item,
table.items-tbl tbody tr:hover td.col-imei,
table.items-tbl tbody tr:hover td.col-qty,
table.items-tbl tbody tr:hover td.col-act{background:#f8faff;}
table.items-tbl tfoot td{background:#fff !important;border:none !important;}
.col-num{width:36px;text-align:center;color:#94a3b8;font-size:0.85rem;font-weight:600;background:#f8fafc;}
table.items-tbl tbody td.col-num{background:#f8fafc;line-height:48px;}
.col-item{min-width:220px;}
.col-imei{width:48px;text-align:center;}
.col-qty{width:72px;text-align:center;}
.col-price{width:110px;text-align:right;}
.col-amt{width:118px;text-align:right;font-weight:700;}
.col-act{width:36px;text-align:center;}
.sale-cell-input{display:block;width:100%;height:48px;box-sizing:border-box;border:none !important;border-radius:0;background:#fff;outline:none;font-size:0.92rem;font-weight:500;color:#1e293b;padding:0 10px;line-height:48px;box-shadow:none !important;}
.sale-cell-input::placeholder{color:#94a3b8;font-weight:400;font-size:0.85rem;}
.sale-cell-input:focus{background:#fff;outline:2px solid #6366f1;outline-offset:-2px;position:relative;z-index:2;}
.sale-cell-input.item-search{font-weight:400;padding-left:10px;}
.sale-cell-input.qty{text-align:center;font-variant-numeric:tabular-nums;}
.sale-cell-input.qty::-webkit-inner-spin-button,.sale-cell-input.qty::-webkit-outer-spin-button,
.sale-cell-input.unit::-webkit-inner-spin-button,.sale-cell-input.unit::-webkit-outer-spin-button{-webkit-appearance:none;margin:0;}
.sale-cell-input.qty,.sale-cell-input.unit{-moz-appearance:textfield;appearance:textfield;}
.sale-cell-input.unit{text-align:right;color:#b45309;background:#fffbeb;font-variant-numeric:tabular-nums;}
.sale-cell-input.unit:focus{background:#fffbeb;}
.sale-cell-input.total{display:flex;align-items:center;justify-content:flex-end;text-align:right;color:#4338ca;background:#eef2ff;font-weight:700;font-variant-numeric:tabular-nums;}
.sale-row-remove{display:flex;align-items:center;justify-content:center;width:100%;height:48px;border-radius:0;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1.15rem;line-height:1;padding:0;}
.sale-row-remove:hover{color:#dc2626;background:#fef2f2;}
.imei-btn{background:#f8fafc;border:none;color:#6366f1;border-radius:0;width:100%;height:48px;min-width:0;padding:0;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:2px;font-weight:700;white-space:nowrap;box-sizing:border-box;}
.imei-btn i{font-size:1.1rem;line-height:1;}
.imei-btn:hover{background:#eef2ff;}
.imei-btn.has-imei{background:#d1fae5;color:#059669;}
.sale-gt-cell{padding:10px 0 0 !important;vertical-align:top;background:#fff !important;}
.sale-btns-cell{padding:8px 0 12px !important;vertical-align:middle;background:#fff !important;}
.sale-cancel-cell{padding:8px 8px 12px 0 !important;text-align:right;vertical-align:middle;background:#fff !important;}
.gt-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 14px;width:100%;height:64px;min-height:64px;box-sizing:border-box;background:#1e3a5f;border-radius:0;box-shadow:none;position:relative;overflow:hidden;}
.gt-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:0;background:#818cf8;}
.gt-meta{display:flex;flex-direction:column;gap:2px;padding-left:8px;min-width:0;}
.gt-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.62);line-height:1.2;}
.gt-qty{font-size:0.92rem;font-weight:800;color:#e0e7ff;letter-spacing:0.01em;line-height:1.2;}
.gt-amount{display:flex;align-items:baseline;gap:8px;font-variant-numeric:tabular-nums;white-space:nowrap;}
.gt-currency{font-size:0.82rem;font-weight:700;color:#a5b4fc;letter-spacing:0.04em;}
#purGrandDisplay{font-size:1.45rem;font-weight:800;color:#fff;letter-spacing:-0.03em;line-height:1;}
.save-actions{display:flex;align-items:stretch;gap:8px;width:100%;}
.btn-cancel-sale{padding:0 18px;border-radius:0;font-size:0.88rem;height:48px;min-height:48px;border:1px solid #e2e8f0;color:#64748b;background:#fff;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;box-sizing:border-box;}
.btn-cancel-sale:hover{border-color:#94a3b8;background:#f8fafc;}
.btn-save-sale,.btn-print-sale{flex:1 1 0;min-width:0;height:48px;min-height:48px;padding:0 14px;border-radius:0;font-size:0.9rem;font-weight:700;white-space:nowrap;border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:none;}
.btn-save-sale{background:#2563eb;}
.btn-save-sale:hover{background:#1d4ed8;}
.btn-print-sale{background:#047857;}
.btn-print-sale:hover{background:#065f46;}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:280px;overflow-y:auto;margin-top:4px;min-width:100%;}
.autocomplete-box.item-dropdown{position:fixed;left:auto;right:auto;margin-top:0;min-width:380px;width:auto;max-width:90vw;}
.autocomplete-item{padding:10px 14px;cursor:pointer;font-size:0.85rem;border-bottom:1px solid #f1f5f9;color:#1e293b;transition:background 0.1s;line-height:1.35;display:flow-root;}
.autocomplete-item strong{display:block;font-weight:700;color:#1e293b;}
.autocomplete-item small{color:#94a3b8;font-size:0.78rem;}
.customer-search-wrap .autocomplete-item{display:flex;align-items:center;justify-content:space-between;gap:12px;}
.customer-search-wrap .autocomplete-item strong{flex:1;min-width:0;}
.customer-search-wrap .autocomplete-item small{flex-shrink:0;white-space:nowrap;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
.autocomplete-item.active,.autocomplete-item.active:hover{background:#eff6ff;outline:none;position:relative;}
.autocomplete-item.active::before,.autocomplete-item.active:hover::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:#6366f1;border-radius:2px 0 0 2px;}
/* IMEI Modal */
.imei-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.imei-modal-overlay.show{display:flex;}
.imei-modal{background:#fff;border-radius:14px;padding:24px 28px;width:100%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);border:1px solid #e0e7ff;}
.imei-modal-title{font-size:1rem;font-weight:700;margin-bottom:18px;display:flex;justify-content:space-between;align-items:flex-start;}
.imei-modal-title .close-x{background:none;border:none;font-size:1.4rem;color:#94a3b8;cursor:pointer;line-height:1;padding:0;}
.imei-input-row{display:flex;gap:8px;align-items:center;margin-bottom:6px;}
.imei-input-row input{flex:1;border:2px solid #e0e7ff;border-radius:8px;padding:9px 12px;font-size:0.9rem;color:#1e293b;letter-spacing:1px;outline:none;}
.imei-input-row input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.imei-confirm-btn{background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;border-radius:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;}
.imei-msg{font-size:0.78rem;padding:4px 8px;border-radius:6px;margin:4px 0 8px;min-height:24px;}
.imei-msg.ok{background:#d1fae5;color:#065f46;}
.imei-msg.err{background:#fee2e2;color:#991b1b;}
.imei-tag{display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #c7d2fe;border-radius:6px;padding:4px 10px;margin:3px;font-size:0.78rem;color:#3730a3;}
.imei-tag .remove{cursor:pointer;color:#94a3b8;}
.imei-tag .remove:hover{color:#ef4444;}
.imei-modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
.btn-close-modal{background:#f1f5f9;border:1.5px solid #e2e8f0;color:#64748b;padding:7px 18px;border-radius:8px;cursor:pointer;font-weight:500;}
.btn-save-modal{background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;padding:7px 22px;border-radius:8px;font-weight:700;cursor:pointer;}
</style>

<form method="POST" action="?page=purchases&action=store" id="purForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="purchase_form_nonce" value="<?= htmlspecialchars($purchaseFormNonce ?? '') ?>">
    <input type="hidden" name="tax" value="0">
    <input type="hidden" name="print_mode" id="purPrintMode" value="0">
    <input type="hidden" name="warehouse_id" value="<?= (int) Auth::warehouseId() ?>">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-cart-plus"></i> New Purchase Invoice
        </div>
        <a href="?page=purchases&action=importInvoice" style="color:#fff;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,0.35);padding:6px 12px;border-radius:8px;white-space:nowrap;">
            <i class="bi bi-upload me-1"></i> Import invoice file
        </a>
    </div>

    <!-- ② SUPPLIER + INVOICE META -->
    <div class="sale-composer">
        <div class="sale-composer-row">
            <div class="sale-field sale-field-primary customer-search-wrap" id="supplierSearchWrap">
                <i class="bi bi-building search-icon"></i>
                <input type="text" id="supplierSearch" placeholder="Search supplier or customer..." autocomplete="off" autofocus>
                <div class="autocomplete-box" id="supplierDrop" style="display:none;"></div>
                <input type="hidden" name="party_id" id="supplierIdInput" required>
            </div>
            <div class="sale-field">
                <i class="bi bi-file-earmark-text search-icon"></i>
                <input type="text" id="supplierRefInput" name="supplier_invoice_no" placeholder="Supplier ref no">
            </div>
            <div class="sale-chip" title="Next invoice number">
                <span class="sale-chip-label">Invoice</span>
                <span class="sale-chip-value"><?= htmlspecialchars($nextInv) ?></span>
            </div>
            <div class="sale-chip sale-chip-date">
                <label class="sale-chip-label" for="purDateInput">Date</label>
                <input type="date" name="date" id="purDateInput" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <!-- ③ ITEMS TABLE -->
    <div class="items-card">
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="purItemsTable">
                <colgroup>
                    <col style="width:36px">
                    <col>
                    <col style="width:48px">
                    <col style="width:72px">
                    <col style="width:110px">
                    <col style="width:118px">
                    <col style="width:36px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th class="col-item">ITEM</th>
                        <th class="col-imei" title="IMEI Scan">IMEI</th>
                        <th class="col-qty">QTY</th>
                        <th class="col-price">COST/UNIT</th>
                        <th class="col-amt">AMOUNT</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="purItemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td colspan="3" class="sale-gt-cell">
                            <div class="gt-card" id="purGrandTotalCard">
                                <div class="gt-meta">
                                    <span class="gt-label">Grand Total</span>
                                    <span class="gt-qty" id="purGtQtyHint">Total qty 0</span>
                                </div>
                                <div class="gt-amount">
                                    <span class="gt-currency"><?= defined('APP_CURRENCY') ? APP_CURRENCY : 'KWD' ?></span>
                                    <span id="purGrandDisplay">0.000</span>
                                </div>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="sale-cancel-cell">
                            <a href="?page=purchases" class="btn-cancel-sale">Cancel</a>
                        </td>
                        <td colspan="3" class="sale-btns-cell">
                            <div class="save-actions">
                                <button type="submit" class="btn-save-sale" onclick="document.getElementById('purPrintMode').value='0'">
                                    <i class="bi bi-check-lg"></i> Save
                                </button>
                                <button type="submit" class="btn-print-sale" id="btnPurSavePrint"
                                    title="Prints with your Default Print (A5 or Thermal) from the profile menu. Shortcut: Ctrl+S / F12"
                                    onclick="document.getElementById('purPrintMode').value='1'">
                                    <i class="bi bi-printer"></i> Save &amp; Print
                                </button>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
</form>

<!-- ═══ IMEI MODAL ═══ -->
<div class="imei-modal-overlay" id="purImeiModal">
    <div class="imei-modal">
        <div class="imei-modal-title">
            <div>
                <div style="font-size:0.72rem;color:#94a3b8;font-weight:400;margin-bottom:3px;">Scanning IMEI for:</div>
                <div id="purImeiModalItemName" style="color:#4338ca;"></div>
            </div>
            <button class="close-x" onclick="closePurImeiModal()">×</button>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label style="font-size:0.83rem;color:#475569;font-weight:600;">IMEI / Serial Number</label>
            <div>
                <span id="purImeiCount" style="font-size:0.8rem;"></span>
                <span id="purImeiRequired" style="font-size:0.75rem;color:#f59e0b;margin-left:4px;"></span>
            </div>
        </div>
        <div class="imei-input-row">
            <input type="text" id="purImeiScanInput" placeholder="Scan or type IMEI / tablet serial..." maxlength="20"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();confirmPurImei();}">
            <button type="button" class="imei-confirm-btn" onclick="confirmPurImei()">
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
        <div id="purImeiMsg" class="imei-msg"></div>
        <div id="purImeiTagList" style="flex:1;overflow-y:auto;min-height:60px;max-height:260px;padding:4px 2px;"></div>
        <div class="imei-modal-footer">
            <button type="button" class="btn-close-modal" id="purImeiSkipBtn" onclick="skipPurImeiModal()">
                <i class="bi bi-clock-history me-1"></i> Scan later
            </button>
            <button type="button" class="btn-save-modal" onclick="savePurImeiModal()">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
let purRowCount       = 0;
let purWarehouse      = <?= (int) Auth::warehouseId() ?>;
let purImeiData       = {};
let purCurrentImeiRow = null;
let purActiveImeis    = [];
let purCurrentItemName= '';

function focusSupplierField() {
    const el = document.getElementById('supplierSearch');
    if (el) el.focus();
}

document.addEventListener('DOMContentLoaded', () => {
    addPurRow(); addPurRow();
    focusSupplierField();
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

function addPurRow() {
    purRowCount++;
    const rid = 'pur_' + purRowCount;
    purImeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num">${purRowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="sale-cell-input item-search pur-item-search" placeholder="Search item by name or SKU…" autocomplete="off" data-row="${rid}" oninput="searchPurItem(this,'${rid}')">
            <input type="hidden" name="items[${purRowCount}][item_id]" id="purItemId_${rid}">
            <input type="hidden" name="items[${purRowCount}][has_imei]" id="purHasImei_${rid}" value="0">
            <input type="hidden" name="items[${purRowCount}][unit]" value="pcs">
            <div class="autocomplete-box item-dropdown" id="purDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-imei">
            <input type="hidden" name="items[${purRowCount}][imeis]" id="purImei_${rid}">
            <button type="button" class="imei-btn" id="purImeiBtn_${rid}" onclick="openPurImeiModal('${rid}')">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty">
            <input type="number" class="sale-cell-input qty" name="items[${purRowCount}][quantity]" id="purQty_${rid}" value="" min="1" placeholder="1" oninput="calcPurRow('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" class="sale-cell-input unit" name="items[${purRowCount}][unit_price]" id="purPrice_${rid}" value="" step="0.001" placeholder="0.000" oninput="calcPurRow('${rid}')">
        </td>
        <td class="col-amt">
            <span class="sale-cell-input total" id="purAmt_${rid}">0.000</span>
        </td>
        <td class="col-act">
            <button type="button" class="sale-row-remove" onclick="removePurRow('${rid}')" title="Remove row" aria-label="Remove row">×</button>
        </td>
    `;
    document.getElementById('purItemsBody').appendChild(tr);
}

function removePurRow(rid) {
    document.getElementById(rid)?.remove();
    delete purImeiData[rid];
    ensureTrailingEmptyPurRow();
    calcPurTotals();
}

function ensureTrailingEmptyPurRow() {
    let hasBlank = false;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        const rowId = tr.dataset.rowId;
        if (!rowId) return;
        if (!document.getElementById('purItemId_' + rowId)?.value) {
            hasBlank = true;
        }
    });
    if (!hasBlank) addPurRow();
}

const purItemStore = {};
let purSearchTimers = {};
const purItemHighlightIdx = {};

function updatePurItemHighlight(rid, scrollActive) {
    const drop = document.getElementById('purDrop_' + rid);
    if (!drop) return;
    const hi = purItemHighlightIdx[rid] ?? -1;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === hi);
    });
    // Only keyboard nav should scroll — mouseenter + scrollIntoView was closing the list
    // via the capture-phase window scroll listener.
    if (!scrollActive) return;
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function bindPurItemDropdown(rid, drop) {
    purItemHighlightIdx[rid] = -1;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            selectPurItem(this.dataset.rid, purItemStore[this.dataset.rid][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            purItemHighlightIdx[rid] = parseInt(this.dataset.idx, 10);
            updatePurItemHighlight(rid, false);
        });
    });
}

function positionDropdown(input, drop) {
    const rect = input.getBoundingClientRect();
    drop.style.top = (rect.bottom + 4) + 'px';
    drop.style.left = rect.left + 'px';
    drop.style.minWidth = Math.max(380, rect.width) + 'px';
}

function searchPurItem(input, rid) {
    clearTimeout(purSearchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('purDrop_' + rid);
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; purItemHighlightIdx[rid] = -1; return; }
    purSearchTimers[rid] = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; purItemHighlightIdx[rid] = -1; return; }
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(qNow)}&warehouse_id=${purWarehouse}`)
            .then(r => r.json())
            .then(items => {
                if (input.value.trim() !== qNow) return;
                if (!items.length) { drop.style.display = 'none'; return; }
                purItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                        <strong>${it.name}</strong> ${it.sku ? `<small style="color:#94a3b8;font-size:0.72rem;"> · ${it.sku}</small>` : ''}
                        <br><small style="color:#94a3b8;">Cost: ${parseFloat(it.purchase_price||0).toFixed(3)}${it.has_imei ? ' · <span style="color:#6366f1;font-weight:600;">IMEI</span>' : ''}</small>
                    </div>
                `).join('');
                bindPurItemDropdown(rid, drop);
                positionDropdown(input, drop);
                if (document.activeElement === input) drop.style.display = 'block';
            });
    }, 250);
}

function reopenPurItemDropdown(input) {
    if (!input || !input.classList.contains('pur-item-search')) return;
    const rid = input.dataset.row;
    const drop = document.getElementById('purDrop_' + rid);
    if (!drop) return;
    if (document.getElementById('purItemId_' + rid)?.value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (purItemStore[rid] || []).length) {
        positionDropdown(input, drop);
        drop.style.display = 'block';
        return;
    }
    searchPurItem(input, rid);
}

function selectPurItem(rid, item) {
    document.querySelector('#' + rid + ' .pur-item-search').value = item.name;
    document.getElementById('purItemId_'  + rid).value = item.id;
    document.getElementById('purHasImei_' + rid).value = item.has_imei;
    document.getElementById('purPrice_'   + rid).value = parseFloat(item.purchase_price || 0).toFixed(3);
    document.getElementById('purDrop_'    + rid).style.display = 'none';
    purItemHighlightIdx[rid] = -1;
    const qtyEl = document.getElementById('purQty_' + rid);
    if (qtyEl && !qtyEl.value) qtyEl.value = 1;
    if (!window.purRowItemNameMap) window.purRowItemNameMap = {};
    if (!window.purRowSerialKindMap) window.purRowSerialKindMap = {};
    window.purRowItemNameMap[rid] = (item.name || '').toLowerCase();
    window.purRowSerialKindMap[rid] = item.serial_kind || 'phone';
    calcPurRow(rid);
    ensureTrailingEmptyPurRow();
}

function getPurImeiRule(row) {
    return IqbalImei.rule(
        (window.purRowSerialKindMap && window.purRowSerialKindMap[row]) || '',
        (window.purRowItemNameMap && window.purRowItemNameMap[row]) || '',
        '',
        { phoneMin: 15, phoneMax: 18 }
    );
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item') && !e.target.closest('.autocomplete-box.item-dropdown')) {
        document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
        Object.keys(purItemHighlightIdx).forEach(rid => { purItemHighlightIdx[rid] = -1; });
    }
    if (!e.target.closest('#supplierSearchWrap')) {
        document.getElementById('supplierDrop').style.display = 'none';
        supplierHighlightIdx = -1;
    }
});
document.getElementById('purItemsBody').addEventListener('focusin', function(e) {
    reopenPurItemDropdown(e.target);
});
document.getElementById('purItemsBody').addEventListener('click', function(e) {
    reopenPurItemDropdown(e.target);
});
document.getElementById('purItemsBody').addEventListener('keydown', function(e) {
    if (!e.target.classList.contains('pur-item-search')) return;
    const rid = e.target.dataset.row;
    const drop = document.getElementById('purDrop_' + rid);
    const visible = drop && drop.style.display !== 'none';
    const items = purItemStore[rid] || [];

    if (e.key === 'ArrowDown') {
        if (!visible || !items.length) return;
        e.preventDefault();
        const cur = purItemHighlightIdx[rid] ?? -1;
        purItemHighlightIdx[rid] = cur < items.length - 1 ? cur + 1 : 0;
        updatePurItemHighlight(rid, true);
    } else if (e.key === 'ArrowUp') {
        if (!visible || !items.length) return;
        e.preventDefault();
        const cur = purItemHighlightIdx[rid] ?? -1;
        purItemHighlightIdx[rid] = cur > 0 ? cur - 1 : items.length - 1;
        updatePurItemHighlight(rid, true);
    } else if (e.key === 'Enter') {
        if (!visible || !items.length) return;
        e.preventDefault();
        e.stopPropagation();
        const cur = purItemHighlightIdx[rid] ?? -1;
        selectPurItem(rid, items[cur >= 0 ? cur : 0]);
    } else if (e.key === 'Escape') {
        if (!visible) return;
        e.preventDefault();
        drop.style.display = 'none';
        purItemHighlightIdx[rid] = -1;
    }
}, true);
window.addEventListener('scroll', (e) => {
    // Ignore scrolls inside the dropdown (list scroll / highlight scrollIntoView).
    // Closing on those made the item list vanish before a click could register.
    const t = e.target;
    if (t && t.nodeType === 1 && typeof t.closest === 'function' && t.closest('.autocomplete-box')) {
        return;
    }
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => {
        if (d.style.display === 'none') return;
        d.style.display = 'none';
    });
    Object.keys(purItemHighlightIdx).forEach(rid => { purItemHighlightIdx[rid] = -1; });
}, true);

// Supplier search
const supplierStore = {};
let supTimer;
let supplierHighlightIdx = -1;

function updateSupplierHighlight() {
    const drop = document.getElementById('supplierDrop');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === supplierHighlightIdx);
    });
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function renderSupplierDropdown(parties) {
    const drop = document.getElementById('supplierDrop');
    const input = document.getElementById('supplierSearch');
    if (!parties.length) { drop.style.display = 'none'; supplierHighlightIdx = -1; return; }
    supplierStore['results'] = parties;
    supplierHighlightIdx = -1;
    drop.innerHTML = parties.map((p, idx) => {
        const typeLbl = p.type === 'customer' ? 'Customer' : (p.type === 'both' ? 'Customer & Supplier' : '');
        const typeHtml = typeLbl ? `<small>${typeLbl}</small>` : '';
        return `
        <div class="autocomplete-item" data-idx="${idx}">
            <strong>${p.name}</strong>
            ${typeHtml}
        </div>`;
    }).join('');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectSupplier(supplierStore['results'][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            supplierHighlightIdx = parseInt(this.dataset.idx, 10);
            updateSupplierHighlight();
        });
    });
    if (document.activeElement === input) drop.style.display = 'block';
}

function runSupplierSearch() {
    const input = document.getElementById('supplierSearch');
    const drop = document.getElementById('supplierDrop');
    if (!input || !drop) return;
    clearTimeout(supTimer);
    const q = input.value.trim();
    supplierHighlightIdx = -1;
    if (q.length < 1) {
        drop.style.display = 'none';
        return;
    }
    supTimer = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; supplierHighlightIdx = -1; return; }
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(qNow)}&type=purchase`)
            .then(r => r.json())
            .then(parties => {
                if (input.value.trim() !== qNow) return;
                renderSupplierDropdown(parties);
            });
    }, 250);
}

function reopenSupplierDropdown() {
    const input = document.getElementById('supplierSearch');
    const drop = document.getElementById('supplierDrop');
    if (!input || !drop) return;
    if (document.getElementById('supplierIdInput').value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (supplierStore['results'] || []).length) {
        drop.style.display = 'block';
        return;
    }
    runSupplierSearch();
}

document.getElementById('supplierSearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('supplierIdInput').value = '';
    runSupplierSearch();
});
document.getElementById('supplierSearch').addEventListener('focus', reopenSupplierDropdown);
document.getElementById('supplierSearch').addEventListener('click', reopenSupplierDropdown);

document.getElementById('supplierSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey && document.getElementById('supplierIdInput').value) {
        e.preventDefault();
        focusFirstPurItem();
        return;
    }

    const drop = document.getElementById('supplierDrop');
    const visible = drop.style.display !== 'none';
    const parties = supplierStore['results'] || [];
    if (!visible || !parties.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        supplierHighlightIdx = supplierHighlightIdx < parties.length - 1 ? supplierHighlightIdx + 1 : 0;
        updateSupplierHighlight();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        supplierHighlightIdx = supplierHighlightIdx > 0 ? supplierHighlightIdx - 1 : parties.length - 1;
        updateSupplierHighlight();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        const idx = supplierHighlightIdx >= 0 ? supplierHighlightIdx : 0;
        selectSupplier(parties[idx]);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        drop.style.display = 'none';
        supplierHighlightIdx = -1;
    }
}, true);

document.getElementById('supplierRefInput').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        focusFirstPurItem();
    }
});

function focusFirstPurItem() {
    let target = null;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid || target) return;
        if (!document.getElementById('purItemId_' + rid)?.value) {
            target = tr.querySelector('.pur-item-search');
        }
    });
    if (!target) {
        target = document.querySelector('#purItemsBody .pur-item-search');
    }
    if (!target) return;
    requestAnimationFrame(() => {
        target.focus({ preventScroll: true });
        target.scrollIntoView({ block: 'nearest' });
    });
}

function selectSupplier(p) {
    const el = document.getElementById('supplierSearch');
    el.value = p.name; el.classList.add('selected');
    document.getElementById('supplierIdInput').value = p.id;
    document.getElementById('supplierDrop').style.display = 'none';
    supplierHighlightIdx = -1;
    setTimeout(focusFirstPurItem, 0);
}

function calcPurRow(rid) {
    const qty   = parseFloat(document.getElementById('purQty_'   + rid)?.value) || 0;
    const price = parseFloat(document.getElementById('purPrice_' + rid)?.value) || 0;
    document.getElementById('purAmt_' + rid).textContent = (qty * price).toFixed(3);
    calcPurTotals();
}

function calcPurTotals() {
    let subtotal = 0, totalQty = 0;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId; if (!rid) return;
        const qty   = parseFloat(document.getElementById('purQty_'   + rid)?.value) || 0;
        const price = parseFloat(document.getElementById('purPrice_' + rid)?.value) || 0;
        subtotal += qty * price;
        totalQty += qty;
    });
    document.getElementById('purGrandDisplay').textContent = subtotal.toFixed(3);
    const gtQty = document.getElementById('purGtQtyHint');
    if (gtQty) gtQty.textContent = 'Total qty ' + totalQty;
    const gtCard = document.getElementById('purGrandTotalCard');
    if (gtCard) gtCard.classList.toggle('has-amount', subtotal > 0.0005);
}

// IMEI Modal
function openPurImeiModal(rid, itemName) {
    purCurrentImeiRow  = rid;
    purCurrentItemName = itemName || document.querySelector('#' + rid + ' .pur-item-search')?.value || 'Item';
    purActiveImeis     = [...(purImeiData[rid] || [])];
    document.getElementById('purImeiModalItemName').textContent = purCurrentItemName;
    const qty = parseInt(document.getElementById('purQty_' + rid)?.value) || 0;
    document.getElementById('purImeiRequired').textContent = qty > 0 ? `(need ${qty})` : '';
    renderPurImeiTags();
    document.getElementById('purImeiModal').classList.add('show');
    document.getElementById('purImeiScanInput').value = '';
    document.getElementById('purImeiMsg').innerHTML   = '';
    setTimeout(() => document.getElementById('purImeiScanInput').focus(), 80);
}

function closePurImeiModal() { document.getElementById('purImeiModal').classList.remove('show'); }

function skipPurImeiModal() {
    if (purCurrentImeiRow) {
        purActiveImeis = [];
        purImeiData[purCurrentImeiRow] = [];
        const imeiField = document.getElementById('purImei_' + purCurrentImeiRow);
        if (imeiField) imeiField.value = '';
        const btn = document.getElementById('purImeiBtn_' + purCurrentImeiRow);
        if (btn) {
            btn.classList.remove('has-imei');
            btn.innerHTML = '<i class="bi bi-upc-scan"></i>';
        }
    }
    closePurImeiModal();
}

function getAllPurImeis(excludeRow) {
    const all = [];
    Object.keys(purImeiData).forEach(r => { if (r !== excludeRow) all.push(...purImeiData[r]); });
    return all;
}

function confirmPurImei() {
    const input = document.getElementById('purImeiScanInput');
    const imei  = IqbalImei.normalize(input.value);
    if (!imei) return;
    const rule = getPurImeiRule(purCurrentImeiRow);
    if (!rule.test(imei)) {
        showPurImeiMsg('Need ' + rule.label + '.', 'err'); input.select(); return;
    }
    if (purActiveImeis.includes(imei)) { showPurImeiMsg('Already added to this item.', 'err'); input.select(); return; }
    if (getAllPurImeis(purCurrentImeiRow).includes(imei)) { showPurImeiMsg('IMEI used in another row.', 'err'); input.select(); return; }
    purActiveImeis.push(imei); renderPurImeiTags();
    showPurImeiMsg('✓ IMEI added.', 'ok');
    input.value = ''; input.focus();
}

function showPurImeiMsg(msg, type) {
    const el = document.getElementById('purImeiMsg');
    el.className = 'imei-msg ' + type; el.innerHTML = msg;
    if (type === 'ok') setTimeout(() => el.innerHTML = '', 2000);
}

function renderPurImeiTags() {
    const qty = parseInt(document.getElementById('purQty_' + purCurrentImeiRow)?.value) || 0;
    const entered = purActiveImeis.length;
    document.getElementById('purImeiCount').innerHTML =
        `<span style="color:${entered >= qty && qty > 0 ? '#6366f1' : '#f59e0b'};font-weight:600;">${entered} entered</span>` +
        (qty > 0 ? ` <span style="color:#94a3b8;">/ ${qty} needed</span>` : '');
    document.getElementById('purImeiTagList').innerHTML = purActiveImeis.map((im, i) => `
        <span class="imei-tag">${im} <span class="remove" onclick="removePurImei(${i})">×</span></span>
    `).join('');
}

function removePurImei(idx) { purActiveImeis.splice(idx, 1); renderPurImeiTags(); }

function savePurImeiModal() {
    if (!purCurrentImeiRow) return;
    purImeiData[purCurrentImeiRow] = [...purActiveImeis];
    document.getElementById('purImei_' + purCurrentImeiRow).value = purActiveImeis.join('\n');
    const btn = document.getElementById('purImeiBtn_' + purCurrentImeiRow);
    if (btn) {
        btn.classList.toggle('has-imei', purActiveImeis.length > 0);
        btn.innerHTML = purActiveImeis.length > 0 ? `<i class="bi bi-upc-scan"></i> ${purActiveImeis.length}` : `<i class="bi bi-upc-scan"></i>`;
    }
    const qtyField = document.getElementById('purQty_' + purCurrentImeiRow);
    if (qtyField && purActiveImeis.length > 0) { qtyField.value = purActiveImeis.length; calcPurRow(purCurrentImeiRow); }
    closePurImeiModal();
}

document.getElementById('purForm').addEventListener('keydown', function(e) {
    var thermalKeys = ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) || e.key === 'F12';
    if (thermalKeys) {
        var modal = document.getElementById('purImeiModal');
        if (modal && modal.classList.contains('show')) return;
        e.preventDefault();
        var pm = document.getElementById('purPrintMode');
        if (pm) pm.value = '1';
        if (typeof this.requestSubmit === 'function') {
            this.requestSubmit();
        } else {
            var pb = document.getElementById('btnPurSavePrint');
            if (pb) pb.click();
        }
        return;
    }
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' &&
        !['submit', 'hidden', 'button'].includes(e.target.type) &&
        e.target.id !== 'purImeiScanInput') {
        e.preventDefault();
    }
}, true);

document.getElementById('purForm').addEventListener('submit', e => {
    if (!document.getElementById('supplierIdInput').value) { e.preventDefault(); alert('Please select a supplier or customer.'); return; }
    let hasItem = false;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        if (tr.dataset.rowId && document.getElementById('purItemId_' + tr.dataset.rowId)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); }
});
</script>