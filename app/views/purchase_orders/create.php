<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px 12px 0 0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.topbar-actions{display:flex;align-items:flex-end;gap:14px;}
.btn-new-item-link{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;border:1.5px solid rgba(255,255,255,0.35);background:rgba(255,255,255,0.16);color:#fff;text-decoration:none;font-size:0.78rem;font-weight:600;transition:all 0.15s;}
.btn-new-item-link:hover{background:rgba(255,255,255,0.24);border-color:rgba(255,255,255,0.55);color:#fff;transform:translateY(-1px);}
.branch-wrap{display:flex;flex-direction:column;align-items:flex-end;gap:2px;font-size:0.75rem;}
.branch-wrap .branch-label{color:rgba(255,255,255,0.6);line-height:1;}
.warehouse-select{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:pointer;outline:none;}
.warehouse-select option{background:#1e3a5f;color:#fff;}
.customer-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.customer-search-wrap{position:relative;flex:1;min-width:300px;max-width:560px;}
.customer-search-wrap .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.customer-search-wrap input{width:100%;padding:11px 12px 11px 40px;min-height:44px;border:2px solid #94a3b8;border-radius:10px;font-size:1.02rem;font-weight:600;color:#1a1a2e;background:#fafbff;transition:all 0.2s;outline:none;}
.customer-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.customer-search-wrap input.selected{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;}
.customer-search-wrap.inv-field{max-width:260px;flex:0 0 auto;}
.customer-search-wrap.inv-field-narrow{max-width:180px;flex:0 0 auto;}
.customer-search-wrap.inv-field-auto{margin-left:auto;}
.supplier-currency-wrap{display:flex;align-items:center;gap:10px;flex:1;min-width:320px;max-width:660px;}
.supplier-currency-wrap .customer-search-wrap{min-width:0;}
.customer-search-wrap.meta{flex:0 0 auto;min-width:0;}
.customer-search-wrap.meta .search-icon{font-size:0.92rem;left:10px;}
.customer-search-wrap.meta input,
.customer-search-wrap.meta select{
    width:100%;
    padding:7px 10px 7px 34px;
    min-height:36px;
    border-width:1.5px;
    border-radius:9px;
    font-size:0.92rem;
}
.customer-search-wrap.meta input:focus,
.customer-search-wrap.meta select:focus{box-shadow:0 0 0 3px rgba(99,102,241,0.10);}
.customer-search-wrap.currency-mini{max-width:92px;flex:0 0 92px;}
.customer-search-wrap.currency-mini select{cursor:pointer;}
.items-card{border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:visible;}
table.items-tbl{width:100%;border-collapse:collapse;font-size:0.94rem;}
table.items-tbl th{padding:11px 12px;font-size:0.76rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.items-tbl td{border-bottom:1px solid #e2e8f0;padding:8px 10px;vertical-align:middle;}
table.items-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.items-tbl tbody tr:hover{background:#f8faff;}
table.items-tbl tbody tr[id^="porow_"]{min-height:44px;}
table.items-tbl input{border:none;outline:none;background:transparent;width:100%;font-size:0.94rem;font-weight:500;color:#1e293b;padding:6px 4px;line-height:1.35;}
table.items-tbl input::placeholder{color:#94a3b8;font-weight:400;font-size:0.9rem;}
table.items-tbl input:focus{background:#eff6ff;border-radius:6px;}
table.items-tbl tfoot tr{background:#f8f9ff;}
table.items-tbl tfoot td{font-size:0.94rem;}
.col-num{width:36px;text-align:center;color:#94a3b8;font-size:0.88rem;font-weight:600;}
.col-item{min-width:220px;position:relative;}
.col-qty{width:82px;text-align:center;}
.col-fprice{width:128px;text-align:right;}
.col-price{width:138px;text-align:right;}
.col-ktotal{width:138px;text-align:right;}
.col-act{width:36px;text-align:center;}
.po-row-remove{font-size:1.25rem!important;line-height:1;padding:2px 4px;}
.sale-bottom{display:flex;justify-content:flex-end;border:1px solid #e5e7eb;border-top:none;background:linear-gradient(135deg,#f8faff,#f5f7ff);border-radius:0 0 12px 12px;overflow:hidden;padding:0;}
.po-summary-panel{min-width:380px;max-width:440px;width:100%;background:#fff;border-left:1px solid #e0e7ff;box-shadow:-4px 0 24px rgba(30,58,95,0.06);}
.po-summary-head{display:flex;align-items:center;gap:8px;padding:12px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);color:#fff;font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;}
.po-summary-head i{font-size:0.95rem;opacity:0.85;}
.po-summary-body{padding:4px 0 8px;}
.po-sum-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 20px;font-size:0.88rem;color:#475569;border-bottom:1px solid #f1f5f9;}
.po-sum-row:last-child{border-bottom:none;}
.po-sum-row .sum-label{display:flex;align-items:center;gap:6px;font-weight:600;color:#64748b;white-space:nowrap;}
.po-sum-row .sum-label i{color:#6366f1;font-size:0.9rem;}
.po-sum-row .sum-val{font-weight:700;color:#1e293b;font-variant-numeric:tabular-nums;min-width:90px;text-align:right;}
.po-sum-row .sum-input{width:130px;text-align:right;border:1.5px solid #dbe2f0;border-radius:8px;padding:7px 10px;font-size:0.9rem;font-weight:700;color:#1e293b;background:#fafbff;outline:none;transition:border-color 0.15s,box-shadow 0.15s;}
.po-sum-row .sum-input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.po-sum-row .sum-select{width:100%;max-width:200px;border:1.5px solid #dbe2f0;border-radius:8px;padding:7px 10px;font-size:0.85rem;font-weight:600;color:#1e293b;background:#fafbff;outline:none;cursor:pointer;transition:border-color 0.15s,box-shadow 0.15s;}
.po-sum-row .sum-select:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.po-sum-row.subtotal .sum-val{color:#6366f1;}
.po-sum-row.grand{background:linear-gradient(135deg,#f0f4ff,#eef2ff);padding:14px 20px;margin-top:2px;}
.po-sum-row.grand .sum-label{font-size:0.92rem;font-weight:700;color:#1e3a5f;text-transform:uppercase;letter-spacing:0.3px;}
.po-sum-row.grand .sum-val{font-size:1.35rem;font-weight:800;color:#1e3a5f;}
.po-sum-row.payment{background:#fafbff;}
.po-sum-row.payment .sum-control{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex:1;max-width:200px;}
.po-sum-row.payment .sum-control .sum-select,.po-sum-row.payment .sum-control .sum-input{width:100%;max-width:200px;}
.po-sum-divider{height:1px;background:linear-gradient(90deg,transparent,#c7d2fe,transparent);margin:6px 20px;}
.po-balance-wrap{padding:6px 16px 14px;}
.po-balance-box{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-radius:10px;border:1.5px solid transparent;transition:background 0.2s,border-color 0.2s,box-shadow 0.2s;}
.po-balance-left{display:flex;align-items:center;gap:11px;min-width:0;}
.po-balance-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0;}
.po-balance-text{display:flex;flex-direction:column;gap:1px;}
.po-balance-label{font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.45px;line-height:1.2;}
.po-balance-hint{font-size:0.72rem;font-weight:600;line-height:1.2;}
.po-balance-amt{font-size:1.28rem;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:0.3px;flex-shrink:0;}
.po-balance-box.zero{background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-color:#6ee7b7;box-shadow:0 2px 8px rgba(5,150,105,0.08);}
.po-balance-box.zero .po-balance-icon{background:rgba(5,150,105,0.14);color:#059669;}
.po-balance-box.zero .po-balance-label,.po-balance-box.zero .po-balance-amt{color:#065f46;}
.po-balance-box.zero .po-balance-hint{color:#059669;}
.po-balance-box.due{background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;box-shadow:0 2px 8px rgba(180,83,9,0.08);}
.po-balance-box.due .po-balance-icon{background:rgba(180,83,9,0.12);color:#b45309;}
.po-balance-box.due .po-balance-label,.po-balance-box.due .po-balance-amt{color:#92400e;}
.po-balance-box.due .po-balance-hint{color:#b45309;}
.full-toggle{display:inline-flex;align-items:center;gap:4px;cursor:pointer;font-size:0.72rem;color:#6366f1;font-weight:700;margin-left:6px;}
.full-toggle input{accent-color:#6366f1;width:14px;height:14px;cursor:pointer;margin:0;}
@media(max-width:760px){.sale-bottom{justify-content:stretch;}.po-summary-panel{max-width:none;border-left:none;border-top:1px solid #e0e7ff;box-shadow:none;}}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);margin-top:-1px;}
.btn-save-sale{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-save-sale:hover{transform:translateY(-1px);}
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
    <input type="hidden" name="exchange_rate" value="1">

<div class="sale-wrap">

    <!-- TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-file-earmark-text"></i> New Purchase Order
        </div>
        <div class="topbar-actions">
            <?php if (Auth::can('inventory','add')): ?>
            <a href="?page=items&action=create" class="btn-new-item-link">
                <i class="bi bi-plus-lg"></i> Create Item
            </a>
            <?php endif; ?>
            <div class="branch-wrap">
                <span class="branch-label">Branch</span>
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
        <div class="supplier-currency-wrap">
            <div class="customer-search-wrap">
                <i class="bi bi-building search-icon"></i>
                <input type="text" id="supplierSearch" placeholder="Search supplier..." autocomplete="off">
                <div class="autocomplete-box" id="supplierDrop" style="display:none;"></div>
            </div>
            <div class="customer-search-wrap meta currency-mini">
                <i class="bi bi-currency-exchange search-icon" style="color:#1d4ed8;"></i>
                <select name="currency" id="poCurrency" style="padding-left:34px;" title="Foreign currency for the supplier price (record only)">
                    <option value="AED">AED</option>
                    <option value="USD">USD</option>
                    <option value="KWD">KWD</option>
                </select>
            </div>
        </div>
        <div class="customer-search-wrap inv-field meta" style="max-width:220px;">
            <i class="bi bi-file-earmark-text search-icon" style="color:#f59e0b;"></i>
            <input type="text" id="supplierRefInput" name="supplier_ref" placeholder="Proforma / Ref No" style="padding-left:34px;">
        </div>
        <div class="customer-search-wrap inv-field inv-field-narrow inv-field-auto meta" style="max-width:160px;">
            <i class="bi bi-hash search-icon" style="color:#6366f1;"></i>
            <input type="text" value="<?= $nextPoNo ?>" readonly
                style="padding-left:36px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#6366f1;font-weight:700;letter-spacing:0.5px;cursor:default;">
        </div>
        <div class="customer-search-wrap inv-field inv-field-narrow meta" style="max-width:165px;">
            <i class="bi bi-calendar3 search-icon" style="color:#f59e0b;"></i>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" style="padding-left:36px;">
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="items-card">
        <table class="items-tbl">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty" style="text-align:center;">Qty</th>
                    <th class="col-fprice" style="text-align:right;">Price (<span id="fcurLabel">AED</span>)</th>
                    <th class="col-price" style="text-align:right;">Price (KWD)</th>
                    <th class="col-ktotal" style="text-align:right;">Total (KWD)</th>
                    <th class="col-act"></th>
                </tr>
            </thead>
            <tbody id="poTbody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                    <td style="text-align:right;font-weight:600;color:#b45309;padding:10px 10px;font-size:0.94rem;" id="subtotalForeign">0.000</td>
                    <td style="padding:10px 10px;font-size:0.78rem;color:#94a3b8;text-align:right;font-weight:700;letter-spacing:0.4px;">SUBTOTAL</td>
                    <td style="text-align:right;font-weight:700;color:#6366f1;padding:10px 10px;font-size:0.98rem;" id="subtotalKwd">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <input type="hidden" name="notes" value="">

    <!-- BOTTOM: right-aligned ERP summary -->
    <div class="sale-bottom">
        <div class="po-summary-panel">
            <div class="po-summary-head">
                <i class="bi bi-calculator"></i> Payment &amp; Summary
            </div>
            <div class="po-summary-body">
                <div class="po-sum-row subtotal">
                    <span class="sum-label">Items Subtotal (KWD)</span>
                    <span class="sum-val" id="subtotalKwdDisplay">0.000</span>
                </div>
                <div class="po-sum-row">
                    <span class="sum-label"><i class="bi bi-truck"></i> Other Charges</span>
                    <input type="number" name="other_charges_kwd" id="otherChargesKwd" class="sum-input" step="0.001" min="0" value="0"
                        title="Delivery or other supplier charges">
                </div>
                <div class="po-sum-row grand">
                    <span class="sum-label">Total Amount</span>
                    <span class="sum-val" id="grandKwd">0.000</span>
                </div>
                <div class="po-sum-divider"></div>
                <div class="po-sum-row payment">
                    <span class="sum-label"><i class="bi bi-bank"></i> Pay From Account</span>
                    <div class="sum-control">
                        <select name="account_id" id="payAccount" class="sum-select">
                            <option value="">— None / On Credit —</option>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= strcasecmp(trim($acc['name']), 'NBK Bank Account') === 0 ? 'selected' : '' ?>><?= htmlspecialchars(BaseController::formatAccountLabel($acc)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="po-sum-row payment">
                    <span class="sum-label">
                        <i class="bi bi-cash-coin"></i> Amount Paid
                        <label class="full-toggle">
                            <input type="checkbox" id="fullPayChk" checked>
                            Full
                        </label>
                    </span>
                    <div class="sum-control">
                        <input type="number" name="paid_kwd" id="paidKwd" class="sum-input" step="0.001" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="po-balance-wrap">
                <div class="po-balance-box zero" id="balanceRow">
                    <div class="po-balance-left">
                        <span class="po-balance-icon" id="balanceIcon"><i class="bi bi-check-circle-fill"></i></span>
                        <div class="po-balance-text">
                            <span class="po-balance-label">Balance Due</span>
                            <span class="po-balance-hint" id="balanceHint">Fully paid</span>
                        </div>
                    </div>
                    <span class="po-balance-amt" id="balanceKwd">0.000</span>
                </div>
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
let supplierMatches = [];
let supplierHighlightIdx = -1;

function updateSupplierHighlight() {
    const drop = document.getElementById('supplierDrop');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === supplierHighlightIdx);
    });
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function renderSupplierDropdown(matches) {
    const drop = document.getElementById('supplierDrop');
    if (!matches.length) { drop.style.display = 'none'; supplierHighlightIdx = -1; return; }
    supplierMatches = matches;
    supplierHighlightIdx = -1;
    drop.innerHTML = matches.map((s, idx) =>
        `<div class="autocomplete-item" data-idx="${idx}"><strong>${s.name}</strong></div>`
    ).join('');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            const s = supplierMatches[parseInt(this.dataset.idx, 10)];
            selectSupplier(s.id, s.name);
        });
        el.addEventListener('mouseenter', function() {
            supplierHighlightIdx = parseInt(this.dataset.idx, 10);
            updateSupplierHighlight();
        });
    });
    drop.style.display = 'block';
}

document.getElementById('supplierSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) {
        document.getElementById('supplierDrop').style.display = 'none';
        supplierHighlightIdx = -1;
        return;
    }
    renderSupplierDropdown(suppliers.filter(s => s.name.toLowerCase().includes(q)).slice(0, 10));
});
document.getElementById('supplierSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey && document.getElementById('partyIdInput').value) {
        e.preventDefault();
        focusFirstPoItem();
        return;
    }

    const drop = document.getElementById('supplierDrop');
    const visible = drop.style.display !== 'none';
    if (!visible || !supplierMatches.length) return;

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
        selectSupplier(s.id, s.name);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        drop.style.display = 'none';
        supplierHighlightIdx = -1;
    }
}, true);
document.getElementById('supplierRefInput').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        focusFirstPoItem();
    }
});
document.getElementById('supplierSearch').addEventListener('blur', () => {
    setTimeout(() => document.getElementById('supplierDrop').style.display = 'none', 200);
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
    inp.value = name; inp.className = 'selected';
    document.getElementById('supplierDrop').style.display = 'none';
    supplierHighlightIdx = -1;
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
            <input type="text" class="item-search" placeholder="Search item..." autocomplete="off" data-row="${rid}">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <input type="hidden" name="items[${rowCount}][unit_price]" value="0">
            <div class="autocomplete-box item-dropdown" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${rowCount}][quantity]" id="qty_${rid}"
                value="1" min="1" style="text-align:center;" data-row="${rid}" data-calc="price">
        </td>
        <td class="col-fprice">
            <input type="number" name="items[${rowCount}][foreign_price]" id="fprice_${rid}"
                value="" step="0.001" min="0" placeholder="Foreign" style="text-align:right;font-weight:600;color:#b45309;"
                data-row="${rid}" data-calc="foreign">
        </td>
        <td class="col-price">
            <input type="number" name="items[${rowCount}][kwd_price]" id="kwdprice_${rid}"
                value="" step="0.001" placeholder="Unit" style="text-align:right;font-weight:600;color:#1e3a5f;"
                data-row="${rid}" data-calc="price">
        </td>
        <td class="col-ktotal">
            <input type="number" name="items[${rowCount}][kwd_total]" id="kwdtotal_${rid}"
                value="" step="0.001" placeholder="Total" style="text-align:right;font-weight:700;color:#6366f1;"
                data-row="${rid}" data-calc="total">
        </td>
        <td class="col-act">
            <button type="button" class="po-row-remove" data-row="${rid}"
                style="background:none;border:none;color:#94a3b8;cursor:pointer;">×</button>
        </td>
    `;
    document.getElementById('poTbody').appendChild(tr);
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
    if (q.length < 1) { drop.style.display = 'none'; poItemHighlightIdx[rid] = -1; return; }
    const whId = document.getElementById('whSelect').value;
    searchTimers[rid] = setTimeout(() => {
        fetch(`?page=purchaseorders&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${encodeURIComponent(whId)}`)
            .then(r => {
                if (!r.ok) throw new Error('search failed');
                return r.json();
            })
            .then(items => {
                if (!Array.isArray(items) || !items.length) { drop.style.display = 'none'; return; }
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
                bindPoItemDropdown(rid, drop);
                positionPoItemDrop(input, drop);
                drop.style.display = 'block';
            })
            .catch(() => { drop.style.display = 'none'; });
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
    if (calc === 'price') calcFromPrice(rid);
    else if (calc === 'total') calcFromTotal(rid);
    else if (calc === 'foreign') calcForeign(rid);
});
document.getElementById('poTbody').addEventListener('click', function(e) {
    const btn = e.target.closest('.po-row-remove');
    if (btn?.dataset.row) removeRow(btn.dataset.row);
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
    if (!e.target.closest('.customer-search-wrap')) {
        const drop = document.getElementById('supplierDrop');
        if (drop) drop.style.display = 'none';
        supplierHighlightIdx = -1;
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
    const kwd = parseFloat(item.purchase_price || 0);
    document.querySelector('#' + rid + ' .item-search').value = item.name;
    document.getElementById('itemId_'   + rid).value = item.id;
    document.getElementById('kwdprice_' + rid).value = kwd.toFixed(3);
    const drop = document.getElementById('itemDrop_' + rid);
    if (drop) drop.style.display = 'none';
    poItemHighlightIdx[rid] = -1;
    prefillForeign(rid, item);
    calcFromPrice(rid);
    const rows = document.querySelectorAll('#poTbody tr[id^="porow_"]');
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

// Foreign price is a manual record only (no KWD conversion); just keep the subtotal in sync.
function calcForeign(rid) {
    recalcTotals();
}

function recalcTotals() {
    let sumK = 0;
    document.querySelectorAll('[id^="kwdtotal_porow_"]').forEach(el => {
        sumK += parseFloat(el.value || 0);
    });
    let sumF = 0;
    document.querySelectorAll('[id^="fprice_porow_"]').forEach(el => {
        const rid = el.id.replace('fprice_', '');
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
        sumF += (parseFloat(el.value || 0) * qty);
    });
    const otherCharges = parseFloat(document.getElementById('otherChargesKwd')?.value || 0);
    const grand = sumK + otherCharges;
    document.getElementById('subtotalForeign').textContent = sumF.toFixed(3);
    document.getElementById('subtotalKwd').textContent = sumK.toFixed(3);
    document.getElementById('subtotalKwdDisplay').textContent = sumK.toFixed(3);
    document.getElementById('grandKwd').textContent = grand.toFixed(3);
    if (document.getElementById('fullPayChk').checked) {
        document.getElementById('paidKwd').value = grand.toFixed(3);
    }
    updateBalanceDisplay(grand);
    checkSaveBtn();
}

function toggleFullPay() {
    if (document.getElementById('fullPayChk').checked) recalcTotals();
}

function updateBalanceDisplay(grand) {
    const paid = parseFloat(document.getElementById('paidKwd')?.value || 0);
    const balance = Math.max(0, grand - paid);
    const isPaid = balance <= 0.0005;
    const balanceEl = document.getElementById('balanceKwd');
    const balanceRow = document.getElementById('balanceRow');
    const balanceHint = document.getElementById('balanceHint');
    const balanceIcon = document.getElementById('balanceIcon');
    if (balanceEl) balanceEl.textContent = balance.toFixed(3);
    if (balanceRow) {
        balanceRow.classList.toggle('zero', isPaid);
        balanceRow.classList.toggle('due', !isPaid);
    }
    if (balanceHint) balanceHint.textContent = isPaid ? 'Fully paid' : 'Outstanding amount';
    if (balanceIcon) {
        balanceIcon.innerHTML = isPaid
            ? '<i class="bi bi-check-circle-fill"></i>'
            : '<i class="bi bi-wallet2"></i>';
    }
}

function checkSaveBtn() {
    const hasItems = [...document.querySelectorAll('[id^="itemId_porow_"]')].some(el => el.value !== '');
    document.getElementById('poSaveBtn').disabled = !hasItems || !supplierId;
}

// Keep the foreign-price column header label in sync with the chosen currency.
document.getElementById('poCurrency').addEventListener('change', function() {
    document.getElementById('fcurLabel').textContent = this.value;
});

// Start with 2 empty rows (no auto-focus on items — supplier first)
document.getElementById('otherChargesKwd')?.addEventListener('input', recalcTotals);
document.getElementById('paidKwd')?.addEventListener('input', function() {
    document.getElementById('fullPayChk').checked = false;
    recalcTotals();
});
document.getElementById('fullPayChk')?.addEventListener('change', toggleFullPay);

document.addEventListener('DOMContentLoaded', () => {
    addRow(true); addRow(true);
    recalcTotals();
    document.getElementById('supplierSearch').focus();
});
</script>
