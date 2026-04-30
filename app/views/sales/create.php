<style>
/* ═══ SALE PAGE ═══ */
.sale-wrap { display:flex; flex-direction:column; gap:0; }

/* TOP BAR */
.sale-topbar {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 20px;
    background:linear-gradient(135deg,#1e3a5f,#2d5a9e);
    border-radius:12px 12px 0 0;
    position:sticky; top:58px; z-index:90;
    box-shadow:0 2px 10px rgba(30,58,95,0.3);
}
.sale-topbar .sale-title {
    font-size:1.05rem; font-weight:700; color:#fff;
    display:flex; align-items:center; gap:8px;
}
.topbar-right { display:flex; align-items:center; gap:10px; }

/* Pay mode pill toggle */
.pay-toggle-wrap {
    display:flex; align-items:center; gap:0;
    background:rgba(255,255,255,0.15); border-radius:8px; padding:3px;
}
.pay-pill {
    padding:4px 14px; border-radius:6px; font-size:0.78rem; font-weight:600;
    cursor:pointer; color:rgba(255,255,255,0.7); border:none; background:transparent;
    transition:all 0.2s; white-space:nowrap;
}
.pay-pill.active { background:#fff; color:#1e3a5f; }

/* Warehouse select */
.warehouse-select {
    padding:5px 12px; border-radius:8px; font-size:0.8rem; font-weight:600;
    background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.3);
    color:#fff; cursor:pointer; outline:none;
}
.warehouse-select option { background:#1e3a5f; color:#fff; }

/* CUSTOMER + INVOICE ROW */
.customer-bar {
    display:flex; align-items:center; gap:16px; flex-wrap:wrap;
    padding:14px 20px;
    background:#fff;
    border:1px solid #e5e7eb; border-top:none;
}
.customer-search-wrap {
    position:relative; flex:1; min-width:240px; max-width:380px;
}
.customer-search-wrap .search-icon {
    position:absolute; left:12px; top:50%; transform:translateY(-50%);
    color:#6366f1; font-size:1rem; z-index:2; pointer-events:none;
}
.customer-search-wrap input {
    width:100%; padding:11px 12px 11px 40px;
    border:2px solid #94a3b8; border-radius:10px;
    min-height:44px;
    font-size:1.02rem; font-weight:600; color:#1a1a2e; background:#fafbff;
    transition:all 0.2s; outline:none;
}
.customer-search-wrap input:focus {
    border-color:#6366f1; background:#fff;
    box-shadow:0 0 0 3px rgba(99,102,241,0.1);
}
.customer-search-wrap input.selected {
    border-color:#10b981; background:linear-gradient(135deg,#f0fdf4,#ecfdf5);
    color:#065f46; font-weight:600;
}
.inv-meta {
    display:flex; gap:20px; align-items:center;
    margin-left:auto; flex-wrap:wrap;
}
.inv-meta-item {
    display:flex; flex-direction:column; align-items:flex-end;
    font-size:0.75rem;
}
.inv-meta-item .label { color:#94a3b8; margin-bottom:2px; font-weight:500; }
.inv-meta-item .value { font-weight:700; color:#1e293b; font-size:0.85rem; }
.inv-meta-item input[type=date] {
    border:1.5px solid #e5e7eb; border-radius:7px;
    padding:3px 8px; font-size:0.8rem; color:#1e293b;
    background:#f8fafc; outline:none; font-weight:600;
}
.inv-meta-item input[type=date]:focus { border-color:#6366f1; }

/* ITEMS TABLE CARD */
.items-card {
    border:1px solid #e5e7eb; border-top:none;
    background:#fff; overflow:visible;
}
.items-card-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 20px;
    background:linear-gradient(135deg,#f8faff,#f0f4ff);
    border-bottom:1px solid #e0e7ff;
}
.items-card-header span {
    font-size:0.8rem; font-weight:700; color:#4338ca;
    text-transform:uppercase; letter-spacing:0.5px;
    display:flex; align-items:center; gap:6px;
}
table.items-tbl {
    width:100%; border-collapse:collapse; font-size:0.9rem;
}
table.items-tbl th {
    padding:9px 10px; font-size:0.74rem; font-weight:700;
    text-transform:uppercase; letter-spacing:0.5px;
    color:#64748b; background:#f8fafc;
    border-bottom:2px solid #e2e8f0;
    white-space:nowrap;
}
table.items-tbl td {
    border-bottom:1px solid #cbd5e1;
    padding:5px 6px; vertical-align:middle;
}
table.items-tbl tbody tr { background:#fff; transition:background 0.1s; }
table.items-tbl tbody tr:hover { background:#f8faff; }
table.items-tbl input, table.items-tbl select {
    border:none; outline:none; background:transparent;
    width:100%; font-size:0.88rem; color:#1e293b; padding:3px;
}
table.items-tbl input:focus {
    background:#eff6ff; border-radius:4px; outline:none;
}
table.items-tbl tfoot tr { background:#f8f9ff; }
.col-num   { width:36px; text-align:center; color:#94a3b8; }
.col-item  { min-width:220px; }
.col-imei  { width:50px; text-align:center; }
.col-qty   { width:75px; text-align:center; }
.col-price { width:120px; text-align:right; }
.col-amt   { width:120px; text-align:right; font-weight:700; }
.col-act   { width:36px; text-align:center; }

/* IMEI btn */
.imei-btn {
    background:linear-gradient(135deg,#eff6ff,#e0e7ff);
    border:1px solid #c7d2fe; color:#6366f1;
    border-radius:7px; padding:4px 8px;
    font-size:0.79rem; cursor:pointer;
    display:inline-flex; align-items:center; gap:3px;
    font-weight:600; transition:all 0.15s;
    white-space:nowrap;
}
.imei-btn:hover {
    background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
    transform:translateY(-1px);
    box-shadow:0 2px 6px rgba(99,102,241,0.2);
}
.imei-btn.has-imei {
    background:linear-gradient(135deg,#d1fae5,#a7f3d0);
    border-color:#6ee7b7; color:#059669;
}

/* Scan-first IMEI bar — padding + field width match .customer-bar / .customer-search-wrap */
.scan-bar {
    display:flex; align-items:center; gap:16px; flex-wrap:wrap;
    padding:14px 20px;
    background:linear-gradient(135deg,#eff6ff,#e0e7ff);
    border:1px solid #c7d2fe; border-top:none;
}
.scan-bar-wrap {
    position:relative; flex:1; min-width:480px; max-width:760px;
}
.scan-bar-wrap .scan-bar-inner-icon {
    position:absolute; left:12px; top:50%; transform:translateY(-50%);
    color:#6366f1; font-size:1rem; z-index:2; pointer-events:none;
}
.scan-bar-input {
    width:100%; padding:11px 16px 11px 40px;
    border:2px solid #94a3b8; border-radius:10px; min-height:44px;
    font-size:1rem; font-family:monospace; letter-spacing:0.5px;
    background:#fafbff; color:#1e293b; outline:none;
    transition:all 0.2s;
}
.scan-bar-input:focus {
    border-color:#6366f1; background:#fff;
    box-shadow:0 0 0 3px rgba(99,102,241,0.1);
}
.scan-bar-input::placeholder { font-family:inherit; font-size:0.82rem; letter-spacing:normal; color:#94a3b8; }
.scan-bar-meta {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    margin-left:auto; min-width:0;
}
.scan-bar-msg {
    font-size:0.78rem; font-weight:600; text-align:left;
    padding:4px 0; border-radius:6px; min-height:1.25em;
}
.scan-bar-msg.ok { background:transparent; color:#065f46; }
.scan-bar-msg.err { background:transparent; color:#991b1b; }
.scan-bar-count {
    font-size:0.75rem; color:#6366f1; font-weight:700; white-space:nowrap;
}
@media (max-width: 640px) {
    .scan-bar-wrap { min-width:0; max-width:100%; flex:1 1 100%; }
}

/* Add row strip */

/* BOTTOM: payment + totals */
.sale-bottom {
    display:flex; gap:0;
    border:1px solid #e5e7eb; border-top:none;
    background:#fff; border-radius:0 0 12px 12px;
    overflow:hidden; flex-wrap:wrap;
}
.sale-bottom-left {
    flex:1; min-width:260px;
    padding:16px 20px;
    border-right:1px solid #f1f5f9;
}

/* Payment box */
.payment-box {
    background:linear-gradient(135deg,#f0fdf4,#ecfdf5);
    border:1.5px solid #6ee7b7; border-radius:10px;
    padding:14px; margin-top:10px;
    display:none;
}
.payment-box label { font-size:0.78rem; color:#065f46; font-weight:600; margin-bottom:4px; }
.payment-box .form-control, .payment-box .form-select {
    font-size:0.82rem; border:1.5px solid #a7f3d0;
    background:#fff; color:#1e293b; border-radius:8px;
    padding:6px 10px; height:34px; outline:none;
}
.payment-box .form-control:focus, .payment-box .form-select:focus {
    border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,0.1);
}

/* TOTALS */
.sale-totals {
    min-width:300px; padding:16px 20px;
    background:linear-gradient(135deg,#f8faff,#f5f7ff);
}
.totals-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:6px 0; border-bottom:1px solid #e8edf5;
    font-size:0.9rem; color:#64748b;
}
.totals-row:last-child { border-bottom:none; }
.totals-row.grand {
    font-size:1.05rem; font-weight:800; color:#1e293b;
    border-top:2px solid #c7d2fe; padding-top:10px; margin-top:4px;
    border-bottom:none;
}
.totals-row.grand span:last-child { color:#6366f1; }
.totals-row input {
    width:130px; text-align:right;
    border:1.5px solid #e2e8f0; border-radius:7px;
    padding:3px 8px; font-size:0.85rem;
    background:#fff; color:#1e293b; outline:none;
}
.totals-row input:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,0.1); }
.balance-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:8px 12px; margin-top:8px;
    background:linear-gradient(135deg,#fff5f5,#fee2e2);
    border:1.5px solid #fca5a5; border-radius:8px;
    font-size:0.9rem; font-weight:700; color:#dc2626;
    display:none;
}

/* SAVE BAR */
.save-bar {
    display:flex; justify-content:flex-end; align-items:center; gap:10px;
    padding:12px 20px;
    background:#fff;
    border:1px solid #e5e7eb; border-top:2px solid #e0e7ff;
    border-radius:0 0 12px 12px;
    position:sticky; bottom:0; z-index:90;
    box-shadow:0 -4px 12px rgba(0,0,0,0.06);
    margin-top:-1px;
}
.btn-cancel-sale {
    padding:8px 20px; border-radius:8px; font-size:0.88rem;
    border:1.5px solid #e5e7eb; color:#64748b; background:#fff;
    cursor:pointer; font-weight:500; transition:all 0.15s;
}
.btn-cancel-sale:hover { border-color:#94a3b8; background:#f8fafc; }
.btn-save-sale {
    padding:8px 28px; border-radius:8px; font-size:0.9rem; font-weight:700;
    background:linear-gradient(135deg,#3b82f6,#2563eb);
    border:none; color:#fff; cursor:pointer;
    box-shadow:0 2px 8px rgba(59,130,246,0.4);
    transition:all 0.15s; display:flex; align-items:center; gap:6px;
}
.btn-save-sale:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(59,130,246,0.5); }
.btn-print-sale {
    padding:8px 22px; border-radius:8px; font-size:0.9rem; font-weight:700;
    background:linear-gradient(135deg,#059669,#047857);
    border:none; color:#fff; cursor:pointer;
    box-shadow:0 2px 8px rgba(5,150,105,0.35);
    transition:all 0.15s; display:flex; align-items:center; gap:6px;
}
.btn-print-sale:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(5,150,105,0.45); }

/* AUTOCOMPLETE */
.autocomplete-box {
    position:absolute; top:100%; left:0;
    background:#fff; border:1.5px solid #e0e7ff;
    border-radius:10px; z-index:9999;
    box-shadow:0 6px 20px rgba(0,0,0,0.12);
    max-height:300px; overflow-y:auto; margin-top:4px;
    min-width:320px; width:max-content; max-width:90vw;
}
.autocomplete-box.item-dropdown {
    position:fixed; margin-top:0;
    min-width:380px; width:auto;
}
.autocomplete-item {
    padding:10px 14px; cursor:pointer; font-size:0.85rem;
    border-bottom:1px solid #f1f5f9; color:#1e293b;
    transition:background 0.1s;
}
.autocomplete-item:last-child { border-bottom:none; }
.autocomplete-item:hover { background:#f0f4ff; }

/* IMEI MODAL */
.imei-modal-overlay {
    position:fixed; inset:0; background:rgba(15,23,42,0.5);
    z-index:9999; display:none; align-items:center; justify-content:center;
    backdrop-filter:blur(2px);
}
.imei-modal-overlay.show { display:flex; }
.imei-modal {
    background:#fff; border-radius:14px; padding:24px 28px;
    width:100%; max-width:480px;
    max-height:90vh; display:flex; flex-direction:column; overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,0.2);
    border:1px solid #e0e7ff;
}
.imei-modal-title {
    font-size:1rem; font-weight:700; margin-bottom:18px;
    display:flex; justify-content:space-between; align-items:flex-start;
}
.imei-modal-title .close-x {
    background:none; border:none; font-size:1.4rem;
    color:#94a3b8; cursor:pointer; line-height:1; padding:0;
}
.imei-input-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
.imei-input-row input {
    flex:1; border:2px solid #e0e7ff; border-radius:8px;
    padding:9px 12px; font-size:0.9rem; color:#1e293b;
    font-family:monospace; letter-spacing:1px; outline:none;
}
.imei-input-row input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.1); }
.imei-confirm-btn {
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    border:none; color:#fff; border-radius:8px;
    width:40px; height:40px; display:flex; align-items:center;
    justify-content:center; cursor:pointer; flex-shrink:0;
    box-shadow:0 2px 6px rgba(99,102,241,0.35);
}
.imei-msg { font-size:0.78rem; padding:4px 8px; border-radius:6px; margin:4px 0 8px; min-height:24px; }
.imei-msg.ok { background:#d1fae5; color:#065f46; }
.imei-msg.err { background:#fee2e2; color:#991b1b; }
.imei-tag {
    display:inline-flex; align-items:center; gap:5px;
    background:#eff6ff; border:1px solid #bfdbfe;
    border-radius:6px; padding:4px 10px; margin:3px;
    font-size:0.78rem; color:#1d4ed8; font-family:monospace;
}
.imei-tag .remove { cursor:pointer; color:#94a3b8; }
.imei-tag .remove:hover { color:#ef4444; }
.imei-modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
.btn-close-modal {
    background:#f1f5f9; border:1.5px solid #e2e8f0; color:#64748b;
    padding:7px 18px; border-radius:8px; cursor:pointer; font-weight:500;
}
.btn-save-modal {
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    border:none; color:#fff; padding:7px 22px;
    border-radius:8px; font-weight:700; cursor:pointer;
    box-shadow:0 2px 6px rgba(99,102,241,0.3);
}
</style>

<form method="POST" action="?page=sales&action=store" id="saleForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="sale_form_nonce" value="<?= htmlspecialchars($saleFormNonce ?? '') ?>">
    <input type="hidden" name="payment_mode" id="paymentMode" value="credit">
    <input type="hidden" name="print_mode" id="salePrintMode" value="0">
    <input type="hidden" name="tax" value="0">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-receipt"></i> New Sale Invoice
        </div>
        <div class="topbar-right">
            <!-- Branch -->
            <select name="warehouse_id" class="warehouse-select" id="warehouseSelect" onchange="updateWarehouse(this.value)" required>
                <option value="">Select Branch</option>
                <?php foreach ($warehouses as $w): ?>
                <option value="<?= $w['id'] ?>" <?= $w['id'] == Auth::warehouseId() ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- ② CUSTOMER + INVOICE META -->
    <div class="customer-bar">
        <div class="customer-search-wrap">
            <i class="bi bi-person-circle search-icon"></i>
            <input type="text" id="partySearch" placeholder="Search agent / customer..." autocomplete="off">
            <div class="autocomplete-box" id="partyDropdown" style="display:none;"></div>
            <input type="hidden" name="party_id" id="partyIdInput" required>
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
        <div class="customer-search-wrap" style="max-width:130px;">
            <i class="bi bi-clock search-icon" style="color:#10b981;"></i>
            <input type="text" id="liveClock" value="<?= date('h:i A') ?>" readonly
                style="padding-left:36px;background:#f8fafc;color:#1e293b;font-weight:700;cursor:default;">
        </div>
    </div>

    <!-- Customer balance strip -->
    <div id="customerBalanceBox" style="display:none;">
        <div id="balanceStripInner" style="display:flex;align-items:center;gap:14px;padding:12px 20px;border-bottom:2px solid #fed7aa;transition:all 0.3s;">
            <i class="bi bi-wallet2" id="balanceIcon" style="font-size:1.3rem;"></i>
            <div style="display:flex;flex-direction:column;gap:1px;">
                <span id="balanceLabel" style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"></span>
                <span id="customerBalanceAmt" style="font-size:1.15rem;font-weight:800;"></span>
            </div>
            <span id="balanceBadge" style="margin-left:auto;font-size:0.72rem;padding:3px 12px;border-radius:20px;font-weight:700;"></span>
        </div>
        <div id="afterSalePreview" style="display:none;font-size:0.75rem;color:#7c3aed;font-weight:600;padding:5px 20px 8px;background:#f5f3ff;"></div>
    </div>

    <!-- IMEI SCAN BAR (aligned with customer row: same inset + icon-inside field) -->
    <div class="scan-bar">
        <div class="scan-bar-wrap">
            <i class="bi bi-upc-scan scan-bar-inner-icon" aria-hidden="true"></i>
            <input type="text" class="scan-bar-input" id="imeiScanBar" placeholder="Scan IMEI barcode — auto-adds item with price" autocomplete="off"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();scanImeiToRow();}">
        </div>
        <div class="scan-bar-meta">
            <span class="scan-bar-msg" id="scanBarMsg"></span>
            <span class="scan-bar-count" id="scanBarCount">0 scanned</span>
        </div>
    </div>

    <!-- ③ ITEMS TABLE -->
    <div class="items-card">
        <div class="items-card-header">
            <span><i class="bi bi-grid-3x3-gap-fill"></i> Items</span>
            <span style="font-size:0.8rem;color:#94a3b8;font-weight:400;">
                <span id="totalQtyBadge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:20px;font-weight:700;">0 items</span>
                <?php if (in_array(Auth::role(), ['cashier','viewer'])): ?>
                <span style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:2px 10px;border-radius:20px;font-size:0.76rem;font-weight:600;margin-left:6px;">
                    <i class="bi bi-shield-check me-1"></i>Min price protected
                </span>
                <?php endif; ?>
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="itemsTable">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th class="col-item">ITEM</th>
                        <th class="col-imei" title="IMEI Scan">IMEI</th>
                        <th class="col-qty">QTY</th>
                        <th class="col-price">UNIT PRICE</th>
                        <th class="col-amt">AMOUNT</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td class="col-qty" style="text-align:center;font-weight:800;color:#4338ca;font-size:0.9rem;" id="totalQtyFoot">0</td>
                        <td></td>
                        <td class="col-amt" style="color:#4338ca;font-size:0.9rem;padding-right:10px;" id="subtotalFoot">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ④ BOTTOM: payment / totals -->
    <div class="sale-bottom">

        <!-- LEFT -->
        <div class="sale-bottom-left">

            <!-- Payment box (cash mode) -->
            <!-- No payment at sale — collect via Payments page -->
            <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.15);border-radius:8px;">
                <i class="bi bi-info-circle" style="color:#6366f1;"></i>
                <span style="font-size:0.82rem;color:#64748b;">Payment will be collected separately via <strong>Payments</strong> page after saving this invoice.</span>
            </div>
            <input type="hidden" name="paid_amount" value="0">
        </div>

        <!-- RIGHT: TOTALS -->
        <div class="sale-totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span id="subtotalDisplay" style="font-weight:600;color:#1e293b;">0.000</span>
            </div>
            <input type="hidden" name="discount" id="discountInput" value="0">
            <div class="totals-row grand">
                <span>Grand Total</span>
                <span id="grandTotalDisplay">0.000</span>
            </div>
        </div>
    </div>

    <!-- ⑤ SAVE BAR -->
    <div class="save-bar">
        <a href="?page=sales" class="btn-cancel-sale">Cancel</a>
        <button type="submit" class="btn-save-sale"
            onclick="document.getElementById('salePrintMode').value='0'">
            <i class="bi bi-check-lg"></i> Save
        </button>
        <button type="submit" class="btn-print-sale"
            onclick="document.getElementById('salePrintMode').value='1'">
            <i class="bi bi-printer"></i> Save &amp; Print A4
        </button>
        <button type="submit" class="btn-print-sale"
            style="background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 2px 8px rgba(124,58,237,.4);"
            onclick="document.getElementById('salePrintMode').value='2'">
            <i class="bi bi-receipt"></i> Save &amp; Thermal
        </button>
    </div>

</div><!-- end sale-wrap -->
</form>

<!-- ═══ IMEI MODAL ═══ -->
<div class="imei-modal-overlay" id="imeiModal">
    <div class="imei-modal">
        <div class="imei-modal-title">
            <div>
                <div style="font-size:0.72rem;color:#94a3b8;font-weight:400;margin-bottom:3px;">Scanning IMEI for:</div>
                <div id="imeiModalItemName" style="color:#1e3a5f;"></div>
            </div>
            <button class="close-x" onclick="closeImeiModal()">×</button>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label style="font-size:0.83rem;color:#475569;font-weight:600;">IMEI / Serial Number</label>
            <div>
                <span id="imeiCount" style="font-size:0.8rem;"></span>
                <span id="imeiRequired" style="font-size:0.75rem;color:#f59e0b;margin-left:4px;"></span>
            </div>
        </div>
        <div class="imei-input-row">
            <input type="text" id="imeiScanInput" placeholder="Scan or type IMEI (15 digits)..." maxlength="18"
                   oninput="autoTriggerImei()" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmImei();}">
            <button type="button" class="imei-confirm-btn" onclick="confirmImei()">
                <i class="bi bi-check-lg"></i>
            </button>
            <button type="button" onclick="togglePasteMode()" style="background:rgba(99,102,241,0.12);color:#6366f1;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;white-space:nowrap;" title="Paste multiple IMEIs">
                <i class="bi bi-clipboard-plus me-1"></i>Paste
            </button>
        </div>
        <!-- Paste/Import area -->
        <div id="imeiPasteBox" style="display:none;margin-top:8px;">
            <textarea id="imeiPasteInput" rows="4" placeholder="Paste IMEIs here — one per line, or comma/space separated..." 
                      style="width:100%;border:2px solid #c7d2fe;border-radius:8px;padding:10px;font-size:0.82rem;font-family:monospace;resize:vertical;"></textarea>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <button type="button" onclick="processPastedImeis()" style="background:#6366f1;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:0.78rem;font-weight:600;cursor:pointer;">
                    <i class="bi bi-check-all me-1"></i>Import All
                </button>
                <button type="button" onclick="togglePasteMode()" style="background:transparent;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;padding:6px 12px;font-size:0.78rem;cursor:pointer;">
                    Cancel
                </button>
                <span id="pasteResult" style="font-size:0.75rem;color:#64748b;margin-left:auto;align-self:center;"></span>
            </div>
        </div>
        <div id="imeiMsg"></div>
        <div id="imeiTagList" style="flex:1;overflow-y:auto;min-height:60px;max-height:260px;padding:4px 2px;"></div>
        <div class="imei-modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeImeiModal()">Cancel</button>
            <button type="button" class="btn-save-modal" onclick="saveImeiModal()">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
// ═══ STATE ═══
let rowCount       = 0;
let currentImeiRow = null;
let imeiData       = {};
let warehouseId    = document.getElementById('warehouseSelect').value;
let payMode        = 'credit';
let activeImeis    = [];
let currentItemName = '';
const saleDraft = <?= json_encode($saleDraft ?? null, JSON_UNESCAPED_UNICODE) ?>;

// ═══ INIT ═══
document.addEventListener('DOMContentLoaded', () => {
    addRow(true); addRow(true);
    restoreSaleDraft();
    document.getElementById('partySearch').focus();
    setInterval(updateClock, 1000);
});

function restoreSaleDraft() {
    if (!saleDraft || !Array.isArray(saleDraft.items) || !saleDraft.items.length) return;

    // Reset auto-created blank rows before restoring draft items.
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    imeiData = {};
    rowCount = 0;

    if (saleDraft.warehouse_id) {
        const whEl = document.getElementById('warehouseSelect');
        whEl.value = String(saleDraft.warehouse_id);
        warehouseId = whEl.value;
    }
    if (saleDraft.date) {
        const dateEl = document.querySelector('input[name="date"]');
        if (dateEl) dateEl.value = saleDraft.date;
    }
    if (typeof saleDraft.discount !== 'undefined') {
        document.getElementById('discountInput').value = parseFloat(saleDraft.discount || 0).toFixed(3);
    }

    saleDraft.items.forEach(item => {
        addRow(true);
        const rid = 'row_' + rowCount;
        const imeiList = String(item.imeis || '').split(/\r?\n/).map(v => v.trim()).filter(Boolean);

        document.querySelector('#' + rid + ' .item-search').value = item.item_name || ('Item #' + item.item_id);
        document.getElementById('itemId_' + rid).value = item.item_id || '';
        document.getElementById('qty_' + rid).value = item.quantity || '';
        document.getElementById('price_' + rid).value = (parseFloat(item.unit_price || 0)).toFixed(3);
        document.getElementById('disc_' + rid).value = (parseFloat(item.discount || 0)).toFixed(3);
        document.getElementById('imeiInput_' + rid).value = imeiList.join('\n');
        imeiData[rid] = imeiList;
        if (imeiList.length > 0) updateImeiBtn(rid);
        calcRow(rid);
    });

    // Keep one clean row available after restored lines.
    addRow(true);

    if (saleDraft.party && saleDraft.party.id) {
        selectParty({
            id: saleDraft.party.id,
            name: saleDraft.party.name || ('Customer #' + saleDraft.party.id),
            phone: saleDraft.party.phone || '',
            balance: saleDraft.party.balance || 0,
            credit_limit: saleDraft.party.credit_limit || 0
        });
    } else if (saleDraft.party_id) {
        document.getElementById('partyIdInput').value = String(saleDraft.party_id);
    }

    calcTotals();
}

function updateClock() {
    const now = new Date();
    let h = now.getHours(); const m = String(now.getMinutes()).padStart(2,'0');
    const ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
    document.getElementById('liveClock').value = `${String(h).padStart(2,'0')}:${m} ${ampm}`;
}
function updateWarehouse(val) { warehouseId = val; }

// ═══ PAY MODE — removed, all sales are credit ═══

// ═══ ROWS ═══
function addRow(noFocus) {
    rowCount++;
    const rid = 'row_' + rowCount;
    imeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num" style="text-align:center;color:#cbd5e1;">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="item-search" placeholder="Search item..." data-row="${rid}" autocomplete="off" oninput="searchItem(this,'${rid}')">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <input type="hidden" name="items[${rowCount}][has_imei]" id="hasImei_${rid}" value="0">
            <input type="hidden" name="items[${rowCount}][unit]" id="unit_${rid}" value="pcs">
            <input type="hidden" name="items[${rowCount}][discount]" id="disc_${rid}" value="0">
            <div class="autocomplete-box item-dropdown" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-imei">
            <button type="button" class="imei-btn" id="imeiBtn_${rid}" onclick="openImeiModal('${rid}')">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${rowCount}][quantity]" id="qty_${rid}" value="" min="1" placeholder="1" style="text-align:center;" oninput="calcRow('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" name="items[${rowCount}][unit_price]" id="price_${rid}" value="" step="0.001" placeholder="0.000"
                   style="text-align:right;"
                   oninput="calcRow('${rid}')">
            <input type="hidden" id="minPrice_${rid}" value="0">
        </td>
        <td class="col-amt" id="amt_${rid}" style="text-align:right;padding-right:8px;color:#4338ca;">0.000</td>
        <td class="col-act">
            <button type="button" onclick="removeRow('${rid}')" style="background:none;border:none;color:#fca5a8;cursor:pointer;font-size:1.1rem;transition:color 0.15s;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#fca5a8'">×</button>
        </td>
        <input type="hidden" name="items[${rowCount}][imeis]" id="imeiInput_${rid}" value="">
    `;
    document.getElementById('itemsBody').appendChild(tr);
}

function removeRow(rid) {
    const el = document.getElementById(rid);
    if (el) el.remove();
    delete imeiData[rid];
    renumberRows(); calcTotals();
}
function renumberRows() {
    let i = 1;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        tr.querySelector('.col-num').textContent = i++;
    });
}

// ═══ ITEM SEARCH ═══
const saleItemStore = {};
let searchTimers = {};
function positionDropdown(input, drop) {
    const rect = input.getBoundingClientRect();
    drop.style.top = (rect.bottom + 4) + 'px';
    drop.style.left = rect.left + 'px';
    drop.style.minWidth = Math.max(380, rect.width) + 'px';
}

function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('itemDrop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers[rid] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${warehouseId}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                saleItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <div style="flex:1;min-width:0;">
                            <strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${it.name}</strong>
                            <small style="color:#94a3b8;">${it.sku || ''}${it.has_imei ? ' · <span style="color:#059669;font-weight:600;">IMEI</span>' : ''}</small>
                        </div>
                        <div style="text-align:right;white-space:nowrap;flex-shrink:0;">
                            <span style="font-weight:700;color:#4338ca;">${it.sale_price}</span>
                            <br><small style="color:#3b82f6;font-weight:600;">Stock: ${it.stock}</small>
                        </div>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectItem(this.dataset.rid, saleItemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                    });
                });
                positionDropdown(input, drop);
                drop.style.display = 'block';
            });
    }, 250);
}

function selectItem(rid, item) {
    document.querySelector('#' + rid + ' .item-search').value = item.name;
    document.getElementById('itemId_'   + rid).value = item.id;
    document.getElementById('hasImei_'  + rid).value = item.has_imei;
    document.getElementById('price_'    + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('minPrice_' + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('unit_'     + rid).value = item.unit;
    document.getElementById('itemDrop_' + rid).style.display = 'none';
    // Set qty to 1 when item is manually selected (if still empty)
    const qtyEl = document.getElementById('qty_' + rid);
    if (qtyEl && !qtyEl.value) qtyEl.value = 1;
    // Store category and item name for IMEI length rules
    if (!window.rowCategoryMap) window.rowCategoryMap = {};
    if (!window.rowItemNameMap) window.rowItemNameMap = {};
    window.rowCategoryMap[rid] = (item.category_name || '').toLowerCase();
    window.rowItemNameMap[rid] = (item.name || '').toLowerCase();
    calcRow(rid);
    setTimeout(() => openImeiModal(rid, item.name), 120);
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addRow();
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item')) document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    if (!e.target.closest('.customer-search-wrap')) document.getElementById('partyDropdown').style.display = 'none';
});
window.addEventListener('scroll', () => {
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
}, true);

// ═══ PARTY SEARCH ═══
const partyStore = {};
let partyTimer;
document.getElementById('partySearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('partyIdInput').value = '';
    document.getElementById('customerBalanceBox').style.display = 'none';
    clearTimeout(partyTimer);
    const q = this.value.trim();
    const drop = document.getElementById('partyDropdown');
    if (q.length < 1) { drop.style.display = 'none'; return; }
    partyTimer = setTimeout(() => {
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(parties => {
                if (!parties.length) { drop.style.display = 'none'; return; }
                partyStore['results'] = parties;
                const curr = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
                drop.innerHTML = parties.map((p, idx) => {
                    const pBal = parseFloat(p.balance) || 0;
                    let balHtml = '';
                    if (pBal > 0.001) {
                        balHtml = `<br><small style="color:#dc2626;font-weight:600;">Due: ${curr} ${pBal.toFixed(3)}</small>`;
                    } else if (pBal < -0.001) {
                        balHtml = `<br><small style="color:#6366f1;font-weight:600;">Balance: -${curr} ${Math.abs(pBal).toFixed(3)}</small>`;
                    } else {
                        balHtml = `<br><small style="color:#16a34a;font-weight:600;">✓ No Outstanding Balance</small>`;
                    }
                    return `
                    <div class="autocomplete-item" data-idx="${idx}">
                        <strong>${p.name}</strong>
                        <small class="float-end" style="color:#94a3b8;">${p.phone || ''}</small>
                        ${balHtml}
                    </div>`;
                }).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectParty(partyStore['results'][parseInt(this.dataset.idx)]);
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
});

function updateAfterSaleBalance(currentBal) {
    const grandTotal = parseFloat(document.getElementById('grandTotalDisplay')?.textContent?.replace(/[^0-9.]/g,'')) || 0;
    if (grandTotal > 0) {
        const afterSale = currentBal + grandTotal;
        const el = document.getElementById('afterSalePreview');
        if (el) {
            el.style.display = 'block';
            el.textContent = 'After this sale: KWD ' + afterSale.toFixed(3);
        }
    }
}

function selectParty(party) {
    const el = document.getElementById('partySearch');
    el.value = party.name; el.classList.add('selected');
    document.getElementById('partyIdInput').value = party.id;
    document.getElementById('partyDropdown').style.display = 'none';

    // Always show balance box when party is selected
    const bal   = parseFloat(party.balance) || 0;
    const box   = document.getElementById('customerBalanceBox');
    const strip = document.getElementById('balanceStripInner');
    const icon  = document.getElementById('balanceIcon');
    const label = document.getElementById('balanceLabel');
    const amt   = document.getElementById('customerBalanceAmt');
    const badge = document.getElementById('balanceBadge');
    const curr  = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';

    if (bal > 0.001) {
        // Has outstanding balance — orange/red warning
        strip.style.background    = 'linear-gradient(135deg,#fff7ed,#ffedd5)';
        strip.style.borderColor   = '#fed7aa';
        icon.style.color          = '#f97316';
        label.style.color         = '#9a3412';
        label.textContent         = 'Previous Balance Due';
        amt.style.color           = '#c2410c';
        amt.textContent           = curr + ' ' + bal.toFixed(3);
        badge.style.background    = '#fef3c7';
        badge.style.color         = '#b45309';
        badge.textContent         = '⚠ Unpaid';
        updateAfterSaleBalance(bal);
    } else if (bal < -0.001) {
        // Company owes this party — blue info with minus sign
        strip.style.background    = 'linear-gradient(135deg,#eff6ff,#dbeafe)';
        strip.style.borderColor   = '#93c5fd';
        icon.style.color          = '#3b82f6';
        label.style.color         = '#1e40af';
        label.textContent         = 'You Owe This Party';
        amt.style.color           = '#1d4ed8';
        amt.textContent           = '-' + curr + ' ' + Math.abs(bal).toFixed(3);
        badge.style.background    = '#dbeafe';
        badge.style.color         = '#1e40af';
        badge.textContent         = 'Payable';
    } else {
        // Zero balance — green all clear
        strip.style.background    = 'linear-gradient(135deg,#f0fdf4,#dcfce7)';
        strip.style.borderColor   = '#86efac';
        icon.style.color          = '#22c55e';
        label.style.color         = '#166534';
        label.textContent         = 'Account Status';
        amt.style.color           = '#15803d';
        amt.textContent           = curr + ' 0.000';
        badge.style.background    = '#dcfce7';
        badge.style.color         = '#166534';
        badge.textContent         = '✓ Clear';
        updateAfterSaleBalance(bal);
    }

    box.style.display = 'block';

    // Show credit limit info
    const limit = parseFloat(party.credit_limit) || 0;
    if (limit > 0) {
        const remaining = limit - Math.max(0, bal);
        const limitInfo = document.createElement('div');
        limitInfo.id = 'creditLimitInfo';
        // Remove old one if exists
        const old = document.getElementById('creditLimitInfo');
        if (old) old.remove();

        if (remaining <= 0) {
            limitInfo.style.cssText = 'padding:8px 20px;background:#fef2f2;border-bottom:2px solid #fecaca;font-size:0.78rem;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:8px;';
            limitInfo.innerHTML = '<i class="bi bi-exclamation-octagon-fill"></i> Credit limit ' + curr + ' ' + limit.toFixed(3) + ' EXCEEDED — Remaining: ' + curr + ' 0.000. Collect payment before new invoice.';
        } else {
            limitInfo.style.cssText = 'padding:8px 20px;background:#f0f9ff;border-bottom:1px solid #bae6fd;font-size:0.78rem;color:#0369a1;display:flex;align-items:center;gap:8px;';
            limitInfo.innerHTML = '<i class="bi bi-shield-check"></i> Credit limit: ' + curr + ' ' + limit.toFixed(3) + ' — Remaining: <strong>' + curr + ' ' + remaining.toFixed(3) + '</strong>';
        }
        box.appendChild(limitInfo);
    }
}

// ═══ CALCULATIONS ═══
const isCashier = <?= in_array(Auth::role(), ['cashier','viewer']) ? 'true' : 'false' ?>;

function calcRow(rid) {
    const qty      = parseFloat(document.getElementById('qty_'      + rid)?.value) || 0;
    const price    = parseFloat(document.getElementById('price_'    + rid)?.value) || 0;
    const disc     = parseFloat(document.getElementById('disc_'     + rid)?.value) || 0;
    const minPrice = parseFloat(document.getElementById('minPrice_' + rid)?.value) || 0;
    const priceEl  = document.getElementById('price_' + rid);

    // Cashier: cannot sell below the item's sale price, but CAN go higher
    if (isCashier && minPrice > 0 && price < minPrice) {
        priceEl.style.border     = '2px solid #dc2626';
        priceEl.style.background = '#fff5f5';
        priceEl.style.color      = '#dc2626';
        priceEl.title = 'Cannot sell below ' + minPrice.toFixed(3) + ' — increase only';
    } else if (isCashier && minPrice > 0 && price > minPrice) {
        priceEl.style.border     = '2px solid #10b981';
        priceEl.style.background = '#f0fdf4';
        priceEl.style.color      = '#065f46';
        priceEl.title = 'Price increased from catalog ' + minPrice.toFixed(3);
    } else {
        priceEl.style.border     = '';
        priceEl.style.background = '';
        priceEl.style.color      = '';
        priceEl.title = '';
    }

    document.getElementById('amt_' + rid).textContent = ((qty * price) - disc).toFixed(3);
    calcTotals();
}

function calcTotals() {
    let subtotal = 0, totalQty = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId; if (!rid) return;
        const qty   = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
        const price = parseFloat(document.getElementById('price_' + rid)?.value) || 0;
        const disc  = parseFloat(document.getElementById('disc_'  + rid)?.value) || 0;
        subtotal += (qty * price) - disc;
        totalQty += qty;
    });
    const discount   = parseFloat(document.getElementById('discountInput').value) || 0;
    const grandTotal = subtotal - discount;

    document.getElementById('subtotalDisplay').textContent   = subtotal.toFixed(3);
    document.getElementById('subtotalFoot').textContent      = subtotal.toFixed(3);
    document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(3);
    document.getElementById('totalQtyFoot').textContent      = totalQty;
    document.getElementById('totalQtyBadge').textContent     = totalQty + ' item' + (totalQty !== 1 ? 's' : '');
}

// ═══ IMEI MODAL ═══
function openImeiModal(rid, itemName) {
    currentImeiRow  = rid;
    currentItemName = itemName || document.querySelector('#' + rid + ' .item-search')?.value || 'Item';
    activeImeis     = [...(imeiData[rid] || [])];
    document.getElementById('imeiModalItemName').textContent = currentItemName;
    const qty = parseInt(document.getElementById('qty_' + rid)?.value) || 0;
    document.getElementById('imeiRequired').textContent = qty > 0 ? `(need ${qty})` : '';
    renderImeiTags();
    document.getElementById('imeiModal').classList.add('show');
    document.getElementById('imeiScanInput').value = '';
    document.getElementById('imeiMsg').innerHTML   = '';
    setTimeout(() => document.getElementById('imeiScanInput').focus(), 80);
}

function closeImeiModal() { document.getElementById('imeiModal').classList.remove('show'); }

function togglePasteMode() {
    var box = document.getElementById('imeiPasteBox');
    var isVisible = box.style.display !== 'none';
    box.style.display = isVisible ? 'none' : 'block';
    document.getElementById('pasteResult').textContent = '';
    if (!isVisible) {
        document.getElementById('imeiPasteInput').value = '';
        document.getElementById('imeiPasteInput').focus();
    } else {
        document.getElementById('imeiScanInput').focus();
    }
}

// Returns {min, max, label} for IMEI digits based on item name / category
function getImeiRule(row) {
    var name     = (window.rowItemNameMap && window.rowItemNameMap[row]) || '';
    var category = (window.rowCategoryMap && window.rowCategoryMap[row]) || '';
    if (name.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13' };
    if (category.indexOf('bud') !== -1) return { min: 15, max: 18, label: '15-18' };
    return { min: 15, max: 15, label: '15' };
}

function processPastedImeis() {
    var raw = document.getElementById('imeiPasteInput').value;
    // Split by newline, comma, space, tab, semicolon
    var list = raw.split(/[\n,;\s\t]+/).map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });

    var rule = getImeiRule(currentImeiRow);

    var added = 0, skipped = 0, invalid = 0;
    var allOtherImeis = getAllEnteredImeis(currentImeiRow);

    list.forEach(function(imei) {
        // Validate digits only
        if (!/^\d+$/.test(imei)) { invalid++; return; }
        // Validate length
        if (imei.length < rule.min || imei.length > rule.max) { invalid++; return; }
        // Check duplicate in current row
        if (activeImeis.includes(imei)) { skipped++; return; }
        // Check duplicate in other rows
        if (allOtherImeis.includes(imei)) { skipped++; return; }

        activeImeis.push(imei);
        added++;
    });

    renderImeiTags();
    var msg = '✓ ' + added + ' added';
    if (skipped > 0) msg += ', ' + skipped + ' duplicates skipped';
    if (invalid > 0) msg += ', ' + invalid + ' invalid';
    document.getElementById('pasteResult').textContent = msg;
    document.getElementById('pasteResult').style.color = added > 0 ? '#059669' : '#ef4444';

    if (added > 0) {
        showImeiMsg('✓ ' + added + ' IMEI(s) imported successfully', 'ok');
    }
    // Clear textarea
    document.getElementById('imeiPasteInput').value = '';
}

function getAllEnteredImeis(excludeRow) {
    const all = [];
    Object.keys(imeiData).forEach(r => { if (r !== excludeRow) all.push(...imeiData[r]); });
    return all;
}

var _imeiAutoTimer = null;

function autoTriggerImei() {
    clearTimeout(_imeiAutoTimer);
    const val  = document.getElementById('imeiScanInput').value.trim();
    const rule = getImeiRule(currentImeiRow);
    if (val.length >= rule.min && val.length <= rule.max) {
        _imeiAutoTimer = setTimeout(function() { confirmImei(); }, 150);
    }
}

function confirmImei() {
    clearTimeout(_imeiAutoTimer);
    const input = document.getElementById('imeiScanInput');
    const imei  = input.value.trim();
    if (!imei) return;

    // Clear input FIRST — instant, so scanner can start next one immediately
    input.value = '';

    // Validate length based on item/category rules
    const rule = getImeiRule(currentImeiRow);

    if (!/^\d+$/.test(imei)) { showImeiMsg('Not digits: ' + imei, 'err'); input.focus(); return; }
    if (imei.length < rule.min || imei.length > rule.max) {
        showImeiMsg('Invalid length (' + imei.length + '). Need ' + rule.label + ' digits.', 'err');
        input.focus(); return;
    }

    // Duplicate check — instant, no server call
    if (activeImeis.includes(imei)) { showImeiMsg('⚠ Duplicate skipped.', 'err'); input.focus(); return; }
    if (getAllEnteredImeis(currentImeiRow).includes(imei)) { showImeiMsg('⚠ Used in another row.', 'err'); input.focus(); return; }

    // Add instantly — no server round-trip
    activeImeis.push(imei);
    renderImeiTags();
    showImeiMsg('✓ ' + imei, 'ok');
    input.focus();
}

function showImeiMsg(msg, type) {
    const el = document.getElementById('imeiMsg');
    el.innerHTML = `<div class="imei-msg ${type}">${msg}</div>`;
    if (type === 'ok') setTimeout(() => el.innerHTML = '', 2000);
}

function renderImeiTags() {
    const qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;
    const entered = activeImeis.length;
    document.getElementById('imeiCount').innerHTML =
        `<span style="color:${entered >= qty && qty > 0 ? '#059669' : '#f59e0b'};font-weight:600;">${entered} entered</span>` +
        (qty > 0 ? ` <span style="color:#94a3b8;">/ ${qty} needed</span>` : '');
    document.getElementById('imeiTagList').innerHTML = activeImeis.map((im, i) => `
        <span class="imei-tag">${im} <span class="remove" onclick="removeImei(${i})">×</span></span>
    `).join('');
}

function removeImei(idx) { activeImeis.splice(idx, 1); renderImeiTags(); }

function saveImeiModal() {
    if (!currentImeiRow) return;
    const qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;
    if (qty > 0 && activeImeis.length !== qty) {
        if (!confirm(`${activeImeis.length} IMEI(s) entered but quantity is ${qty}. Quantity will update to match. Continue?`)) return;
    }
    imeiData[currentImeiRow] = [...activeImeis];
    document.getElementById('imeiInput_' + currentImeiRow).value = activeImeis.join('\n');
    const btn = document.getElementById('imeiBtn_' + currentImeiRow);
    if (btn) {
        btn.classList.toggle('has-imei', activeImeis.length > 0);
        btn.innerHTML = activeImeis.length > 0 ? `<i class="bi bi-upc-scan"></i> ${activeImeis.length}` : `<i class="bi bi-upc-scan"></i>`;
    }
    const qtyField = document.getElementById('qty_' + currentImeiRow);
    if (qtyField && activeImeis.length > 0) { qtyField.value = activeImeis.length; calcRow(currentImeiRow); }
    closeImeiModal();
}

// ═══ PREVENT ACCIDENTAL FORM SUBMIT ON ENTER ═══
// Barcode scanners send Enter after each scan; block it from all text inputs
// except the scan bar (which has its own inline onkeydown → scanImeiToRow)
document.getElementById('saleForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' &&
        !['submit','hidden','button'].includes(e.target.type)) {
        e.preventDefault();
    }
});

// ═══ FORM VALIDATION ═══
document.getElementById('saleForm').addEventListener('submit', function(e) {
    // Client-side submit lock (server also validates one-time nonce)
    if (this.dataset.submitting === '1') { e.preventDefault(); return; }

    if (!document.getElementById('partyIdInput').value) {
        e.preventDefault(); alert('Please select a customer.'); document.getElementById('partySearch').focus(); return;
    }
    if (!document.getElementById('warehouseSelect').value) {
        e.preventDefault(); alert('Please select a branch.'); return;
    }
    let hasItem = false;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (rid && document.getElementById('itemId_' + rid)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); return; }

    // Cashier: block if any item price is below minimum
    if (isCashier) {
        let belowMin = false;
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const rid = tr.dataset.rowId;
            if (!rid || !document.getElementById('itemId_' + rid)?.value) return;
            const price    = parseFloat(document.getElementById('price_'    + rid)?.value) || 0;
            const minPrice = parseFloat(document.getElementById('minPrice_' + rid)?.value) || 0;
            if (minPrice > 0 && price < minPrice) belowMin = true;
        });
        if (belowMin) {
            e.preventDefault();
            alert('One or more items are priced below the minimum catalog price. You can only increase the price, not decrease it. Fix the prices marked in red.');
        }
    }

    if (e.defaultPrevented) return;
    this.dataset.submitting = '1';
    this.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => { btn.disabled = true; });
});

// ═══ SCAN-FIRST IMEI ═══
let scanCount = 0;

function scanImeiToRow() {
    const input = document.getElementById('imeiScanBar');
    const msg   = document.getElementById('scanBarMsg');
    const imei  = input.value.trim().toUpperCase();
    if (!imei) return;

    input.value = '';
    input.focus();
    msg.className = 'scan-bar-msg';
    msg.textContent = 'Looking up...';

    fetch('?page=imei&action=lookupImei&imei=' + encodeURIComponent(imei))
        .then(r => r.json())
        .then(data => {

            if (!data.found) {
                if (data.accepted) {
                    // IMEI not in DB — notification only (no item picker)
                    msg.className = 'scan-bar-msg err';
                    msg.textContent = 'IMEI not registered in system';
                    setTimeout(() => { msg.textContent = ''; msg.className = 'scan-bar-msg'; }, 3000);
                } else {
                    msg.className = 'scan-bar-msg err';
                    msg.textContent = data.message || 'IMEI not found';
                    setTimeout(() => { msg.textContent = ''; msg.className = 'scan-bar-msg'; }, 3000);
                }
                return;
            }

            // Check if this IMEI is already added in any row
            for (const rid in imeiData) {
                if (imeiData[rid] && imeiData[rid].includes(imei)) {
                    msg.className = 'scan-bar-msg err';
                    msg.textContent = 'Already added in this invoice';
                    setTimeout(() => { msg.textContent = ''; msg.className = 'scan-bar-msg'; }, 3000);
                    return;
                }
            }

            // Find an existing row for the same item, or use the last empty row, or add a new one
            let targetRid = null;
            const rows = document.querySelectorAll('#itemsBody tr');

            // Find existing row with same item — always group same item together
            for (const tr of rows) {
                const rid = tr.dataset.rowId;
                const itemIdEl = document.getElementById('itemId_' + rid);
                if (itemIdEl && parseInt(itemIdEl.value) === data.item_id) {
                    targetRid = rid;
                    break;
                }
            }

            if (targetRid) {
                // Add IMEI to existing row and set qty = total IMEIs for this item
                if (!imeiData[targetRid]) imeiData[targetRid] = [];
                imeiData[targetRid].push(imei);
                document.getElementById('qty_' + targetRid).value = imeiData[targetRid].length;
                document.getElementById('imeiInput_' + targetRid).value = imeiData[targetRid].join('\n');
                updateImeiBtn(targetRid);
                calcRow(targetRid);
            } else {
                // Find last empty row or create a new one
                let emptyRid = null;
                for (const tr of rows) {
                    const rid = tr.dataset.rowId;
                    const itemIdEl = document.getElementById('itemId_' + rid);
                    if (itemIdEl && !itemIdEl.value) {
                        emptyRid = rid;
                        break;
                    }
                }
                if (!emptyRid) {
                    addRow();
                    const allRows = document.querySelectorAll('#itemsBody tr');
                    emptyRid = allRows[allRows.length - 1].dataset.rowId;
                }

                // Fill the row
                document.querySelector('#' + emptyRid + ' .item-search').value = data.item_name;
                document.getElementById('itemId_'   + emptyRid).value = data.item_id;
                document.getElementById('hasImei_'  + emptyRid).value = data.has_imei;
                document.getElementById('price_'    + emptyRid).value = parseFloat(data.sale_price).toFixed(3);
                document.getElementById('minPrice_' + emptyRid).value = parseFloat(data.sale_price).toFixed(3);
                document.getElementById('qty_'      + emptyRid).value = 1;

                imeiData[emptyRid] = [imei];
                document.getElementById('imeiInput_' + emptyRid).value = imei;
                updateImeiBtn(emptyRid);
                calcRow(emptyRid);

                // Add an empty row for next manual entry
                addRow();
            }

            scanCount++;
            document.getElementById('scanBarCount').textContent = scanCount + ' scanned';
            msg.className = 'scan-bar-msg ok';
            msg.textContent = data.item_name.substring(0, 25);
            setTimeout(() => { msg.textContent = ''; msg.className = 'scan-bar-msg'; }, 2000);
            calcTotals();
        })
        .catch(() => {
            msg.className = 'scan-bar-msg err';
            msg.textContent = 'Network error';
            setTimeout(() => { msg.textContent = ''; msg.className = 'scan-bar-msg'; }, 3000);
        });
}

function updateImeiBtn(rid) {
    const btn = document.getElementById('imeiBtn_' + rid);
    if (!btn) return;
    const count = imeiData[rid] ? imeiData[rid].length : 0;
    if (count > 0) {
        btn.className = 'imei-btn has-imei';
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + count;
    }
}
</script>
