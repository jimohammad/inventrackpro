<style>
/* ═══ SHARED WITH SALE PAGE ═══ */
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
.inv-meta{display:flex;gap:20px;align-items:center;margin-left:auto;flex-wrap:wrap;}
.inv-meta-item{display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;}
.inv-meta-item .label{color:#94a3b8;margin-bottom:2px;font-weight:500;}
.inv-meta-item .value{font-weight:700;color:#1e293b;font-size:0.85rem;}
.inv-meta-item input{border:1.5px solid #e5e7eb;border-radius:7px;padding:3px 8px;font-size:0.8rem;color:#1e293b;background:#f8fafc;outline:none;font-weight:600;}
.inv-meta-item input:focus{border-color:#6366f1;}
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
.col-price{width:130px;text-align:right;}
.col-amt{width:130px;text-align:right;font-weight:700;}
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
.totals-row input{width:130px;text-align:right;border:1.5px solid #e0e7ff;border-radius:7px;padding:3px 8px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;}
.totals-row input:focus{border-color:#6366f1;}
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
</style>

<form method="POST" action="?page=purchases&action=store" id="purForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="purchase_form_nonce" value="<?= htmlspecialchars($purchaseFormNonce ?? '') ?>">
    <input type="hidden" name="tax" value="0">
    <input type="hidden" name="paid_amount" id="purPaidHidden" value="0">
    <input type="hidden" name="payment_method" value="cash">
    <input type="hidden" name="account_id" id="purAccountHidden" value="<?= $accounts[0]['id'] ?? '' ?>">
    <input type="hidden" name="print_mode" id="purPrintMode" value="0">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-cart-plus"></i> New Purchase Invoice
        </div>
        <select name="warehouse_id" class="warehouse-select" id="whSelect" onchange="purWarehouse=this.value" required>
            <option value="">Select Branch</option>
            <?php foreach ($warehouses as $w): ?>
            <option value="<?= $w['id'] ?>" <?= $w['id'] == Auth::warehouseId() ? 'selected' : '' ?>>
                <?= htmlspecialchars($w['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ② SUPPLIER + INVOICE META -->
    <div class="customer-bar">
        <div class="customer-search-wrap">
            <i class="bi bi-building search-icon"></i>
            <input type="text" id="supplierSearch" placeholder="Search supplier..." autocomplete="off">
            <div class="autocomplete-box" id="supplierDrop" style="display:none;"></div>
            <input type="hidden" name="party_id" id="supplierIdInput" required>
        </div>
        <div class="customer-search-wrap" style="max-width:220px;">
            <i class="bi bi-file-earmark-text search-icon" style="color:#f59e0b;"></i>
            <input type="text" name="supplier_invoice_no" placeholder="Supplier Ref No" style="padding-left:36px;">
        </div>

        <div class="customer-search-wrap" style="max-width:180px;margin-left:auto;">
            <i class="bi bi-hash search-icon" style="color:#6366f1;"></i>
            <input type="text" value="<?= $nextInv ?>" readonly
                style="padding-left:36px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#6366f1;font-weight:700;letter-spacing:0.5px;cursor:default;">
        </div>
        <div class="customer-search-wrap" style="max-width:180px;">
            <i class="bi bi-calendar3 search-icon" style="color:#f59e0b;"></i>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" style="padding-left:36px;">
        </div>
    </div>

    <!-- ③ ITEMS TABLE -->
    <div class="items-card">
        <div class="items-card-header">
            <span><i class="bi bi-grid-3x3-gap-fill"></i> Items</span>
            <span style="font-size:0.75rem;color:#94a3b8;font-weight:400;">
                <span id="purQtyBadge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:20px;font-weight:700;">0 items</span>
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="purItemsTable">
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
                        <td class="col-qty" style="text-align:center;font-weight:800;color:#4338ca;font-size:0.9rem;" id="purQtyFoot">0</td>
                        <td></td>
                        <td class="col-amt" style="color:#6366f1;font-size:0.9rem;padding-right:10px;" id="purSubFoot">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="add-row-strip" onclick="addPurRow()">
            <span class="plus-c">+</span> Add Item Row
        </div>
    </div>

    <!-- ④ BOTTOM: totals -->
    <div class="sale-bottom">
        <div class="sale-bottom-left" style="display:flex;align-items:center;">
            <p class="mb-0" style="color:#94a3b8;font-size:0.82rem;font-style:italic;">
                <i class="bi bi-info-circle me-1"></i> Payment can be recorded separately from the Payments module.
            </p>
        </div>
        <div class="sale-totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span id="purSubDisplay" style="font-weight:600;color:#1e293b;">0.000</span>
            </div>
            <div class="totals-row">
                <span>Discount</span>
                <input type="number" name="discount" id="purDiscInput" step="0.001" min="0" value="0.000" oninput="calcPurTotals()" placeholder="0.000">
            </div>
            <div class="totals-row grand">
                <span>Grand Total</span>
                <span id="purGrandDisplay">0.000</span>
            </div>
            <!-- Payment section -->
            <div style="margin-top:10px;padding:10px 12px;background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.85rem;font-weight:600;color:#475569;">Pay From Account</span>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none;">
                        <input type="checkbox" id="purPayFullChk" onchange="togglePurPayFull(this)"
                               style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer;">
                        <span style="font-size:0.82rem;font-weight:700;color:#6366f1;">Pay in Full</span>
                    </label>
                </div>
                <select id="purAccountSelect" onchange="document.getElementById('purAccountHidden').value=this.value"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e0e7ff;border-radius:8px;font-size:0.85rem;font-weight:600;color:#1e293b;background:#fff;margin-bottom:8px;">
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:0.82rem;font-weight:600;color:#475569;white-space:nowrap;">Amount Paid</span>
                    <div style="display:flex;align-items:center;gap:4px;flex:1;">
                        <span style="font-size:0.8rem;font-weight:700;color:#6366f1;"><?= APP_CURRENCY ?></span>
                        <input type="number" id="purPaidInput" step="0.001" min="0" value="0.000"
                               oninput="onPurPaidInput()"
                               style="flex:1;padding:7px 10px;border:1.5px solid #e0e7ff;border-radius:8px;font-size:0.9rem;font-weight:700;color:#1e293b;background:#fff;text-align:right;">
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;margin-top:8px;background:linear-gradient(135deg,#fff5f5,#fee2e2);border:1.5px solid #fca5a5;border-radius:8px;">
                <span style="font-size:0.9rem;font-weight:700;color:#dc2626;"><i class="bi bi-clock-history me-1"></i> Balance Due</span>
                <span id="purBalDisplay" style="font-size:0.9rem;font-weight:800;color:#dc2626;">0.000</span>
            </div>
        </div>
    </div>

    <!-- ⑤ SAVE BAR -->
    <div class="save-bar">
        <a href="?page=purchases" class="btn-cancel-sale">Cancel</a>
        <button type="submit" class="btn-save-sale" onclick="document.getElementById('purPrintMode').value='0'">
            <i class="bi bi-check-lg"></i> Save
        </button>
        <button type="submit" class="btn-print-sale" onclick="document.getElementById('purPrintMode').value='1'">
            <i class="bi bi-printer"></i> Print & Save
        </button>
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
            <input type="text" id="purImeiScanInput" placeholder="Scan or type IMEI (15-18 digits)..." maxlength="18"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();confirmPurImei();}">
            <button type="button" class="imei-confirm-btn" onclick="confirmPurImei()">
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
        <div id="purImeiMsg" class="imei-msg"></div>
        <div id="purImeiTagList" style="flex:1;overflow-y:auto;min-height:60px;max-height:260px;padding:4px 2px;"></div>
        <div class="imei-modal-footer">
            <button type="button" class="btn-close-modal" onclick="closePurImeiModal()">Cancel</button>
            <button type="button" class="btn-save-modal" onclick="savePurImeiModal()">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
let purRowCount       = 0;
let purWarehouse      = document.getElementById('whSelect').value;
let purImeiData       = {};
let purCurrentImeiRow = null;
let purActiveImeis    = [];
let purCurrentItemName= '';

document.addEventListener('DOMContentLoaded', () => { addPurRow(); addPurRow(); });

function addPurRow() {
    purRowCount++;
    const rid = 'pur_' + purRowCount;
    purImeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num" style="text-align:center;color:#cbd5e1;">${purRowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="pur-item-search" placeholder="Search item..." autocomplete="off" data-row="${rid}" oninput="searchPurItem(this,'${rid}')">
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
        <td class="col-qty"><input type="number" name="items[${purRowCount}][quantity]" id="purQty_${rid}" value="1" min="1" style="text-align:center;" oninput="calcPurRow('${rid}')"></td>
        <td class="col-price"><input type="number" name="items[${purRowCount}][unit_price]" id="purPrice_${rid}" value="" step="0.001" placeholder="0.000" style="text-align:right;" oninput="calcPurRow('${rid}')"></td>
        <td class="col-amt" id="purAmt_${rid}" style="text-align:right;padding-right:8px;color:#6366f1;">0.000</td>
        <td class="col-act">
            <button type="button" onclick="removePurRow('${rid}')" style="background:none;border:none;color:#fca5a8;cursor:pointer;font-size:1.1rem;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#fca5a8'">×</button>
        </td>
    `;
    document.getElementById('purItemsBody').appendChild(tr);
}

function removePurRow(rid) {
    document.getElementById(rid)?.remove();
    delete purImeiData[rid];
    calcPurTotals();
}

const purItemStore = {};
let purSearchTimers = {};

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
    if (q.length < 1) { drop.style.display = 'none'; return; }
    purSearchTimers[rid] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${purWarehouse}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                purItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                        <strong>${it.name}</strong> ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <br><small style="color:#94a3b8;">Cost: ${parseFloat(it.purchase_price||0).toFixed(3)}${it.has_imei ? ' · <span style="color:#6366f1;font-weight:600;">IMEI</span>' : ''}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectPurItem(this.dataset.rid, purItemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                    });
                });
                positionDropdown(input, drop);
                drop.style.display = 'block';
            });
    }, 250);
}

function selectPurItem(rid, item) {
    document.querySelector('#' + rid + ' .pur-item-search').value = item.name;
    document.getElementById('purItemId_'  + rid).value = item.id;
    document.getElementById('purHasImei_' + rid).value = item.has_imei;
    document.getElementById('purPrice_'   + rid).value = parseFloat(item.purchase_price || 0).toFixed(3);
    document.getElementById('purDrop_'    + rid).style.display = 'none';
    if (!window.purRowItemNameMap) window.purRowItemNameMap = {};
    window.purRowItemNameMap[rid] = (item.name || '').toLowerCase();
    calcPurRow(rid);
    setTimeout(() => openPurImeiModal(rid, item.name), 120);
    const rows = document.querySelectorAll('#purItemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addPurRow();
}

function getPurImeiRule(row) {
    var name = (window.purRowItemNameMap && window.purRowItemNameMap[row]) || '';
    if (name.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13' };
    return { min: 15, max: 18, label: '15-18' };
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item')) document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    if (!e.target.closest('.customer-search-wrap')) document.getElementById('supplierDrop').style.display = 'none';
});
window.addEventListener('scroll', () => {
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
}, true);

// Supplier search
const supplierStore = {};
let supTimer;
document.getElementById('supplierSearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('supplierIdInput').value = '';
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
                    <div class="autocomplete-item" data-idx="${idx}">
                        <strong>${p.name}</strong>
                        <small class="float-end" style="color:#94a3b8;">${p.phone || ''}</small>
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
    const el = document.getElementById('supplierSearch');
    el.value = p.name; el.classList.add('selected');
    document.getElementById('supplierIdInput').value = p.id;
    document.getElementById('supplierDrop').style.display = 'none';
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
        subtotal += qty * price; totalQty += qty;
    });
    const discount = parseFloat(document.getElementById('purDiscInput').value) || 0;
    const grand    = subtotal - discount;
    document.getElementById('purSubDisplay').textContent   = subtotal.toFixed(3);
    document.getElementById('purSubFoot').textContent      = subtotal.toFixed(3);
    document.getElementById('purQtyFoot').textContent      = totalQty;
    document.getElementById('purGrandDisplay').textContent = grand.toFixed(3);
    document.getElementById('purQtyBadge').textContent     = totalQty + ' item' + (totalQty !== 1 ? 's' : '');
    // If Pay in Full is ticked, keep paid = grand
    const chk = document.getElementById('purPayFullChk');
    if (chk && chk.checked) {
        document.getElementById('purPaidInput').value = grand.toFixed(3);
        document.getElementById('purPaidHidden').value = grand.toFixed(3);
    }
    const paid = parseFloat(document.getElementById('purPaidInput')?.value) || 0;
    const bal  = Math.max(0, grand - paid);
    document.getElementById('purBalDisplay').textContent = bal.toFixed(3);
}

function togglePurPayFull(chk) {
    const grand = parseFloat(document.getElementById('purGrandDisplay').textContent) || 0;
    if (chk.checked) {
        document.getElementById('purPaidInput').value      = grand.toFixed(3);
        document.getElementById('purPaidHidden').value     = grand.toFixed(3);
        document.getElementById('purBalDisplay').textContent = '0.000';
    } else {
        document.getElementById('purPaidInput').value      = '0.000';
        document.getElementById('purPaidHidden').value     = '0';
        document.getElementById('purBalDisplay').textContent = grand.toFixed(3);
    }
}

function onPurPaidInput() {
    // Un-tick "Pay in Full" if user manually edits the amount
    const chk = document.getElementById('purPayFullChk');
    if (chk) chk.checked = false;
    const paid  = parseFloat(document.getElementById('purPaidInput').value) || 0;
    const grand = parseFloat(document.getElementById('purGrandDisplay').textContent) || 0;
    document.getElementById('purPaidHidden').value        = paid.toFixed(3);
    document.getElementById('purBalDisplay').textContent  = Math.max(0, grand - paid).toFixed(3);
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

function getAllPurImeis(excludeRow) {
    const all = [];
    Object.keys(purImeiData).forEach(r => { if (r !== excludeRow) all.push(...purImeiData[r]); });
    return all;
}

function confirmPurImei() {
    const input = document.getElementById('purImeiScanInput');
    const imei  = input.value.trim();
    if (!imei) return;
    const rule = getPurImeiRule(purCurrentImeiRow);
    if (!/^\d+$/.test(imei) || imei.length < rule.min || imei.length > rule.max) {
        showPurImeiMsg('IMEI must be ' + rule.label + ' digits.', 'err'); input.select(); return;
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
    const qty = parseInt(document.getElementById('purQty_' + purCurrentImeiRow)?.value) || 0;
    if (qty > 0 && purActiveImeis.length !== qty) {
        if (!confirm(`${purActiveImeis.length} IMEI(s) entered but quantity is ${qty}. Quantity will update. Continue?`)) return;
    }
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

document.getElementById('purForm').addEventListener('submit', e => {
    if (!document.getElementById('supplierIdInput').value) { e.preventDefault(); alert('Please select a supplier.'); return; }
    if (!document.getElementById('whSelect').value) { e.preventDefault(); alert('Please select a branch.'); return; }
    let hasItem = false;
    document.querySelectorAll('#purItemsBody tr').forEach(tr => {
        if (tr.dataset.rowId && document.getElementById('purItemId_' + tr.dataset.rowId)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); }
});
</script>