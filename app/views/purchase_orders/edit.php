<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#1e3a5f;border-radius:0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.warehouse-select{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:pointer;outline:none;}
.warehouse-select option{background:#1e3a5f;color:#fff;}
.customer-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.customer-search-wrap{position:relative;flex:1;min-width:300px;max-width:560px;}
.customer-search-wrap .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.customer-search-wrap input{width:100%;padding:11px 12px 11px 40px;min-height:44px;border:2px solid #e0e7ff;border-radius:10px;font-size:1.02rem;font-weight:600;color:#1a1a2e;background:#fafbff;transition:all 0.2s;outline:none;}
.customer-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.customer-search-wrap input.selected{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;}
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
table.items-tbl{width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed;font-size:0.94rem;}
table.items-tbl th{padding:10px 12px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.45px;color:#64748b;background:#f1f5f9;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.items-tbl td{border-bottom:1px solid #e2e8f0;padding:10px 8px;vertical-align:middle;}
table.items-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.items-tbl tbody tr:hover{background:#f8faff;}
table.items-tbl tfoot tr{background:#f8f9ff;}
.col-num{width:44px;text-align:center;color:#94a3b8;font-size:0.88rem;font-weight:600;}
.col-item{width:auto;position:relative;}
.col-qty{width:88px;text-align:center;}
.col-fprice{width:132px;text-align:right;}
.col-price{width:132px;text-align:right;}
.col-ktotal{width:140px;text-align:right;}
.col-act{width:44px;text-align:center;}
.po-cell-input{
    display:block;width:100%;box-sizing:border-box;
    border:1.5px solid #dbe2f0;border-radius:8px;
    background:#fafbff;outline:none;
    font-size:0.92rem;font-weight:600;color:#1e293b;
    padding:8px 10px;line-height:1.3;
    transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;
}
.po-cell-input::placeholder{color:#94a3b8;font-weight:500;font-size:0.85rem;}
.po-cell-input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.12);}
.po-cell-input.item-search{font-weight:500;padding-left:12px;}
.po-cell-input.qty{text-align:center;font-variant-numeric:tabular-nums;}
.po-cell-input.foreign{text-align:right;color:#b45309;background:#fffbeb;border-color:#fde68a;font-variant-numeric:tabular-nums;}
.po-cell-input.foreign:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.15);background:#fff;}
.po-cell-input.unit{text-align:right;color:#1e3a5f;font-variant-numeric:tabular-nums;}
.po-cell-input.total{text-align:right;color:#4338ca;background:#eef2ff;border-color:#c7d2fe;font-weight:700;font-variant-numeric:tabular-nums;}
.po-cell-input.total:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.12);background:#fff;}
.po-row-remove{
    display:inline-flex;align-items:center;justify-content:center;
    width:32px;height:32px;border-radius:8px;
    background:none;border:none;color:#94a3b8;cursor:pointer;
    font-size:1.2rem;line-height:1;padding:0;transition:color 0.15s,background 0.15s;
}
.po-row-remove:hover{color:#dc2626;background:#fef2f2;}
@media(max-width:900px){
    .col-qty{width:72px;}.col-fprice,.col-price{width:110px;}.col-ktotal{width:118px;}
}
.sale-bottom{display:flex;justify-content:space-between;gap:0;border:1px solid #e5e7eb;border-top:none;background:linear-gradient(135deg,#f8faff,#f5f7ff);border-radius:0 0 12px 12px;overflow:hidden;flex-wrap:wrap;}
.sale-bottom-left{flex:1;min-width:260px;padding:20px 24px;border-right:1px solid #e0e7ff;}
.sale-bottom-left label{font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.4px;display:block;margin-bottom:8px;}
.sale-bottom-left textarea{width:100%;border:1.5px solid #dbe2f0;border-radius:10px;padding:10px 14px;font-size:0.88rem;resize:vertical;outline:none;color:#475569;background:#fff;min-height:80px;transition:border-color 0.15s,box-shadow 0.15s;}
.sale-bottom-left textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.po-summary-panel{min-width:380px;max-width:440px;background:#fff;box-shadow:-4px 0 24px rgba(30,58,95,0.06);}
.po-summary-head{display:flex;align-items:center;gap:8px;padding:12px 20px;background:#1e3a5f;color:#fff;font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;}
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
.po-balance-box.over{background:linear-gradient(135deg,#fef2f2,#fee2e2);border-color:#fca5a5;box-shadow:0 2px 8px rgba(220,38,38,0.08);}
.po-balance-box.over .po-balance-icon{background:rgba(220,38,38,0.12);color:#dc2626;}
.po-balance-box.over .po-balance-label,.po-balance-box.over .po-balance-amt{color:#991b1b;}
.po-balance-box.over .po-balance-hint{color:#dc2626;}
@media(max-width:760px){.po-summary-panel{max-width:none;width:100%;box-shadow:none;border-top:1px solid #e0e7ff;}}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);margin-top:-1px;}
.btn-save-sale{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-save-sale:hover{transform:translateY(-1px);}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:280px;overflow-y:auto;margin-top:4px;}
.autocomplete-box.item-dropdown{position:fixed;margin-top:0;min-width:380px;width:auto;right:auto;}
.autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
.autocomplete-item.active,.autocomplete-item.active:hover{background:#eff6ff;box-shadow:inset 3px 0 0 #6366f1;outline:none;}
</style>

<form method="POST" action="?page=purchaseorders&action=update" id="poForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $po['id'] ?>">
    <input type="hidden" name="party_id" id="partyIdInput" value="<?= $po['party_id'] ?>">
    <?php $poCur = in_array(($po['currency'] ?? ''), ['AED','USD','KWD'], true) ? $po['currency'] : 'AED'; ?>

<div class="sale-wrap">

    <!-- TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-pencil-square"></i> Edit Purchase Order: <?= $po['po_no'] ?>
        </div>
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;">
                <span style="color:rgba(255,255,255,0.6);margin-bottom:2px;">Branch</span>
                <select name="warehouse_id" class="warehouse-select" id="whSelect">
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= ($po['warehouse_id'] ?? Auth::warehouseId()) == $wh['id'] ? 'selected' : '' ?>>
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
                <input type="text" id="supplierSearch" value="<?= htmlspecialchars($po['supplier_name']) ?>" readonly
                    style="padding-left:36px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;cursor:default;">
            </div>
            <div class="customer-search-wrap meta currency-mini">
                <i class="bi bi-currency-exchange search-icon" style="color:#1d4ed8;"></i>
                <select name="currency" id="poCurrency" style="padding-left:34px;" title="Foreign currency for the supplier price (record only)">
                    <option value="AED" <?= $poCur === 'AED' ? 'selected' : '' ?>>AED</option>
                    <option value="USD" <?= $poCur === 'USD' ? 'selected' : '' ?>>USD</option>
                    <option value="KWD" <?= $poCur === 'KWD' ? 'selected' : '' ?>>KWD</option>
                </select>
            </div>
        </div>
        <div class="customer-search-wrap meta" style="max-width:220px;">
            <i class="bi bi-file-earmark-text search-icon" style="color:#f59e0b;"></i>
            <input type="text" name="supplier_ref" placeholder="Proforma / Ref No" style="padding-left:34px;" value="<?= htmlspecialchars($po['supplier_ref'] ?? '') ?>">
        </div>
        <div class="customer-search-wrap meta" style="max-width:160px;margin-left:auto;">
            <i class="bi bi-hash search-icon" style="color:#6366f1;"></i>
            <input type="text" value="<?= $po['po_no'] ?>" readonly
                style="padding-left:36px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#6366f1;font-weight:700;letter-spacing:0.5px;cursor:default;">
        </div>
        <div class="customer-search-wrap meta" style="max-width:165px;">
            <i class="bi bi-calendar3 search-icon" style="color:#f59e0b;"></i>
            <input type="date" name="date" value="<?= $po['date'] ?>" style="padding-left:36px;">
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
                    <th class="col-fprice" style="text-align:right;">Foreign (<span id="fcurLabel"><?= htmlspecialchars($poCur) ?></span>)</th>
                    <th class="col-price" style="text-align:right;">Unit (KWD)</th>
                    <th class="col-ktotal" style="text-align:right;">Total (KWD)</th>
                    <th class="col-act"></th>
                </tr>
            </thead>
            <tbody id="poTbody"></tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;padding:12px 10px;font-size:0.78rem;color:#94a3b8;font-weight:700;letter-spacing:0.4px;">SUBTOTAL</td>
                    <td style="text-align:right;font-weight:700;color:#b45309;padding:12px 10px;font-size:0.94rem;font-variant-numeric:tabular-nums;" id="subtotalForeign">0.000</td>
                    <td></td>
                    <td style="text-align:right;font-weight:700;color:#4338ca;padding:12px 10px;font-size:0.98rem;font-variant-numeric:tabular-nums;" id="subtotalKwd">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- BOTTOM -->
    <div class="sale-bottom">
        <div class="sale-bottom-left">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Any notes about this order..."><?= htmlspecialchars($po['notes'] ?? '') ?></textarea>
        </div>
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
                    <input type="number" name="other_charges_kwd" id="otherChargesKwd" class="sum-input" step="0.001" min="0"
                        value="<?= number_format((float)($po['other_charges_kwd'] ?? 0), 3, '.', '') ?>"
                        title="Delivery or other supplier charges">
                </div>
                <div class="po-sum-row">
                    <span class="sum-label"><i class="bi bi-plus-slash-minus"></i> Bank Adj. (+/−)</span>
                    <input type="number" name="adjustment_kwd" id="adjustmentKwd" class="sum-input" step="0.001"
                        value="<?= number_format((float)($po['adjustment_kwd'] ?? 0), 3, '.', '') ?>"
                        title="Increase or decrease total to match exact bank amount deducted">
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
                            <option value="<?= $acc['id'] ?>" <?= ($po['account_id'] ?? 0) == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(BaseController::formatAccountLabel($acc)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="po-sum-row payment">
                    <span class="sum-label"><i class="bi bi-cash-coin"></i> Amount Paid</span>
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
        <button type="submit" class="btn-save-sale" id="poSaveBtn">
            <i class="bi bi-check-lg"></i> Update Purchase Order
        </button>
    </div>

</div>
</form>

<script>
let rowCount   = 0;
let supplierId = '<?= $po['party_id'] ?? 0 ?>';
const itemStore    = {};
const searchTimers = {};

// ── Add row ────────────────────────────────────────────────────────────────
function addRow() {
    rowCount++;
    const rid = 'porow_' + rowCount;
    const tr  = document.createElement('tr');
    tr.id = rid;
    tr.innerHTML = `
        <td class="col-num">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="po-cell-input item-search" placeholder="Search item by name or SKU…"
                autocomplete="off" data-row="${rid}"
                oninput="searchItem(this,'${rid}')">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <div class="autocomplete-box item-dropdown" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-qty">
            <input type="number" class="po-cell-input qty" name="items[${rowCount}][quantity]" id="qty_${rid}"
                value="1" min="1" data-row="${rid}" data-calc="price"
                oninput="calcFromPrice('${rid}', false)" onblur="calcFromPrice('${rid}', true)">
        </td>
        <td class="col-fprice">
            <input type="number" class="po-cell-input foreign" name="items[${rowCount}][foreign_price]" id="fprice_${rid}"
                value="" step="0.001" min="0" placeholder="0.000"
                oninput="calcForeign('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" class="po-cell-input unit" name="items[${rowCount}][kwd_price]" id="kwdprice_${rid}"
                value="" step="0.001" min="0" placeholder="after TT"
                data-row="${rid}" data-calc="price"
                oninput="calcFromPrice('${rid}', false)" onblur="calcFromPrice('${rid}', true)">
        </td>
        <td class="col-ktotal">
            <input type="number" class="po-cell-input total" name="items[${rowCount}][kwd_total]" id="kwdtotal_${rid}"
                value="" step="0.001" min="0" placeholder="after TT"
                data-row="${rid}" data-calc="total"
                oninput="calcFromTotal('${rid}', false)" onblur="calcFromTotal('${rid}', true)">
        </td>
        <td class="col-act">
            <button type="button" class="po-row-remove" onclick="removeRow('${rid}')"
                title="Remove row" aria-label="Remove row">×</button>
        </td>
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
    document.querySelectorAll('#poTbody tr[id^="porow_"]').forEach(tr => {
        const col = tr.querySelector('.col-num');
        if (col) col.textContent = i++;
    });
}

function positionPoItemDrop(input, drop) {
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
function escPoHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    const q    = input.value.trim();
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; itemStore[rid] = []; return; }
    const whEl = document.getElementById('whSelect');
    const whId = whEl ? whEl.value : '';
    searchTimers[rid] = setTimeout(() => {
        const reqQ = q;
        fetch(`?page=purchaseorders&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${encodeURIComponent(whId)}`)
            .then(r => {
                if (!r.ok) throw new Error('search failed');
                return r.json();
            })
            .then(items => {
                if (input.value.trim() !== reqQ) return;
                if (!Array.isArray(items) || !items.length) {
                    itemStore[rid] = [];
                    drop.innerHTML = `<div style="padding:12px 14px;color:#94a3b8;font-size:0.85rem;">No items found for “${escPoHtml(reqQ)}”</div>`;
                    positionPoItemDrop(input, drop);
                    drop.style.display = 'block';
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
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectItem(this.dataset.rid, itemStore[this.dataset.rid][parseInt(this.dataset.idx, 10)]);
                    });
                });
                positionPoItemDrop(input, drop);
                drop.style.display = 'block';
            })
            .catch(() => {
                if (input.value.trim() !== reqQ) return;
                itemStore[rid] = [];
                drop.innerHTML = `<div style="padding:12px 14px;color:#ef4444;font-size:0.85rem;">Item search failed. Try again.</div>`;
                positionPoItemDrop(input, drop);
                drop.style.display = 'block';
            });
    }, 250);
}

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
    document.getElementById('itemDrop_' + rid).style.display = 'none';
    prefillForeign(rid, item);
    if ((document.getElementById('poCurrency')?.value || 'KWD') !== 'KWD') {
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
    const subF = document.getElementById('subtotalForeign');
    if (subF) subF.textContent = sumF.toFixed(3);
    const otherCharges = parseFloat(document.getElementById('otherChargesKwd')?.value || 0);
    const adjustment = parseFloat(document.getElementById('adjustmentKwd')?.value || 0);
    const grand = sumK + otherCharges + adjustment;
    document.getElementById('subtotalKwd').textContent = sumK.toFixed(3);
    document.getElementById('subtotalKwdDisplay').textContent = sumK.toFixed(3);
    document.getElementById('grandKwd').textContent = grand.toFixed(3);
    updateBalanceDisplay(grand);
    checkSaveBtn();
}

function updateBalanceDisplay(grand) {
    const paid = parseFloat(document.getElementById('paidKwd')?.value || 0);
    const raw = Math.round((grand - paid + Number.EPSILON) * 1000) / 1000;
    const isOver = raw < -0.0005;
    const isPaid = Math.abs(raw) <= 0.0005;
    const balanceEl = document.getElementById('balanceKwd');
    const balanceRow = document.getElementById('balanceRow');
    const balanceHint = document.getElementById('balanceHint');
    const balanceIcon = document.getElementById('balanceIcon');
    const balanceLabel = balanceRow?.querySelector('.po-balance-label');
    if (balanceEl) balanceEl.textContent = isPaid ? '0.000' : Math.abs(raw).toFixed(3);
    if (balanceRow) {
        balanceRow.classList.toggle('zero', isPaid);
        balanceRow.classList.toggle('due', !isPaid && !isOver);
        balanceRow.classList.toggle('over', isOver);
    }
    if (balanceLabel) balanceLabel.textContent = isOver ? 'Overpaid' : 'Balance Due';
    if (balanceHint) {
        balanceHint.textContent = isPaid ? 'Fully paid' : (isOver ? 'Paid more than total' : 'Outstanding amount');
    }
    if (balanceIcon) {
        balanceIcon.innerHTML = isPaid
            ? '<i class="bi bi-check-circle-fill"></i>'
            : (isOver ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-wallet2"></i>');
    }
}

function checkSaveBtn() {
    const rows = [...document.querySelectorAll('#poTbody tr[id^="porow_"]')];
    const filled = rows.filter(tr => document.getElementById('itemId_' + tr.id)?.value);
    const foreign = (document.getElementById('poCurrency')?.value || 'KWD') !== 'KWD';
    const pricesOk = filled.length > 0 && filled.every(tr => {
        const rid = tr.id;
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value || 0);
        if (qty < 1) return false;
        if (foreign) return parseFloat(document.getElementById('fprice_' + rid)?.value || 0) > 0;
        return parseFloat(document.getElementById('kwdprice_' + rid)?.value || 0) > 0
            || parseFloat(document.getElementById('kwdtotal_' + rid)?.value || 0) > 0;
    });
    document.getElementById('poSaveBtn').disabled = !pricesOk || !supplierId;
}

document.getElementById('otherChargesKwd')?.addEventListener('input', recalcTotals);
document.getElementById('adjustmentKwd')?.addEventListener('input', recalcTotals);
document.getElementById('paidKwd')?.addEventListener('input', recalcTotals);

// Pre-load existing items
document.addEventListener('DOMContentLoaded', function() {
    const initPaid = <?= (float)($po['paid_kwd'] ?? 0) ?>;
    document.getElementById('paidKwd').value = initPaid.toFixed(3);

    <?php foreach ($po['items'] as $item): ?>
    addRow();
    (function() {
        const rows = document.querySelectorAll('#poTbody tr[id^="porow_"]');
        const tr = rows[rows.length - 1];
        const rid = tr.id;
        tr.querySelector('.item-search').value = '<?= addslashes($item['item_name']) ?>';
        document.getElementById('itemId_' + rid).value = '<?= $item['item_id'] ?>';
        document.getElementById('qty_' + rid).value = '<?= $item['quantity'] ?>';
        <?php $uk = (float) ($item['unit_price_kwd'] ?? 0); $tk = (float) ($item['total_kwd'] ?? 0); ?>
        document.getElementById('kwdprice_' + rid).value = '<?= $uk > 0.0005 ? number_format($uk, 3, '.', '') : '' ?>';
        document.getElementById('kwdtotal_' + rid).value = '<?= $tk > 0.0005 ? number_format($tk, 3, '.', '') : '' ?>';
        <?php $fp = (float)($item['unit_price_foreign'] ?? 0); ?>
        document.getElementById('fprice_' + rid).value = '<?= $fp > 0 ? number_format($fp, 3, '.', '') : '' ?>';
        // Snap total = qty × unit (3dp) so display matches what Full Pay / bank should use.
        calcFromPrice(rid, true);
    })();
    <?php endforeach; ?>

    addRow(); // one empty row at the end
    recalcTotals();
    // Legacy rows could store paid 0.001–0.050 off from qty×unit rounding; snap when clearly "full".
    const grandNow = parseFloat(document.getElementById('grandKwd')?.textContent || 0);
    const paidNow = parseFloat(document.getElementById('paidKwd')?.value || 0);
    const tol = Math.min(0.100, Math.max(0.001, Math.round(grandNow * 0.00001 * 1000) / 1000));
    if (paidNow > 0 && Math.abs(paidNow - grandNow) <= tol) {
        document.getElementById('paidKwd').value = grandNow.toFixed(3);
        updateBalanceDisplay(grandNow);
    }
});

// Keep the foreign-price column header label in sync with the chosen currency.
document.getElementById('poCurrency').addEventListener('change', function() {
    document.getElementById('fcurLabel').textContent = this.value;
});
</script>
