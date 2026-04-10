<!-- Sales Create Form - Matching reference UI -->
<style>
    .sale-page-wrap {
        background: #fff;
        color: #1a1a2e;
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
        font-size: 0.875rem;
    }

    /* TOP BAR */
    .sale-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.6rem 1.25rem;
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        position: sticky; top: 58px; z-index: 90;
    }
    .sale-title { font-size: 1rem; font-weight: 700; color: #222; }
    .toggle-wrap { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #555; }
    .toggle-wrap .toggle-label { font-weight: 600; }
    .toggle-switch {
        position: relative; width: 38px; height: 20px;
        display: inline-block;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background: #3b82f6; border-radius: 20px;
        transition: .2s;
    }
    .toggle-slider:before {
        position: absolute; content: "";
        height: 14px; width: 14px;
        left: 3px; bottom: 3px;
        background: white; border-radius: 50%;
        transition: .2s;
    }
    .toggle-switch input:checked + .toggle-slider { background: #3b82f6; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }
    .godown-select {
        padding: 4px 10px; border: 1px solid #d1d5db; border-radius: 5px;
        font-size: 0.8rem; background: #f9fafb; cursor: pointer; color: #333;
    }

    /* PARTY ROW */
    .party-row {
        display: flex; align-items: center; gap: 10px;
        padding: 0.6rem 1.25rem;
        background: #fff; border-bottom: 1px solid #e0e0e0;
        flex-wrap: wrap;
    }
    .party-row .form-control,
    .party-row .form-select {
        background: #fff; border: 1px solid #d1d5db;
        color: #222; border-radius: 5px;
        font-size: 0.8rem; padding: 5px 10px;
        height: 34px;
    }
    .party-row .form-control:focus { border-color: #3b82f6; box-shadow: none; }

    /* INVOICE INFO */
    .inv-info {
        display: flex; flex-direction: column; gap: 2px;
        font-size: 0.8rem; color: #555; margin-left: auto;
        white-space: nowrap;
    }
    .inv-info span { display: flex; justify-content: space-between; gap: 16px; }
    .inv-info strong { color: #222; min-width: 80px; text-align: right; }

    /* ITEMS TABLE */
    .items-section { padding: 0 0 0 0; }
    .items-table-wrap { overflow-x: auto; }
    table.items-tbl {
        width: 100%; border-collapse: collapse; font-size: 0.82rem;
    }
    table.items-tbl th {
        color: #fff; font-weight: 700;
        padding: 9px 10px; border: none;
        font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px;
        white-space: nowrap;
    }
    table.items-tbl thead tr { background: #1e3a5f; }
    table.items-tbl th.col-num   { background: #1e3a5f; }
    table.items-tbl th.col-item  { background: #1e3a5f; }
    table.items-tbl th.col-imei  { background: #1e3a5f; }
    table.items-tbl th.col-qty   { background: #1e3a5f; }
    table.items-tbl th.col-price { background: #1e3a5f; }
    table.items-tbl th.col-amt   { background: #1e3a5f; }
    table.items-tbl th.col-act   { background: #1e3a5f; }

    table.items-tbl td {
        border: none; border-bottom: 1px solid #f0f3f8;
        padding: 5px 6px; vertical-align: middle;
    }
    table.items-tbl tbody tr { background: #fff; }
    table.items-tbl tbody tr:nth-child(even) { background: #fafbff; }
    table.items-tbl tbody tr:hover { background: #eff6ff; }
    table.items-tbl input, table.items-tbl select {
        border: none; outline: none; background: transparent;
        width: 100%; font-size: 0.82rem; color: #222; padding: 3px;
    }
    table.items-tbl input:focus { background: #eff6ff; border-radius: 3px; }

    table.items-tbl td.col-amt { font-weight: 700; }

    .col-num  { width: 36px; text-align: center; color: #999; }
    .col-item { min-width: 240px; }
    .col-qty  { width: 80px; text-align: center; }
    .col-unit { width: 90px; }
    .col-price { width: 120px; text-align: right; }
    .col-disc  { width: 90px; text-align: right; }
    .col-amt   { width: 120px; text-align: right; }
    .col-act   { width: 40px; text-align: center; }
    .col-imei  { width: 44px; text-align: center; }

    /* IMEI button in row */
    .imei-btn {
        background: linear-gradient(135deg, #eff6ff, #e0e7ff);
        border: 1px solid #c7d2fe;
        color: #6366f1;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.78rem;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        transition: all 0.15s;
        box-shadow: 0 1px 3px rgba(99,102,241,0.15);
    }
    .imei-btn:hover {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        border-color: #a5b4fc;
        box-shadow: 0 2px 6px rgba(99,102,241,0.25);
        transform: translateY(-1px);
    }
    .imei-btn.has-imei {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border-color: #6ee7b7;
        color: #059669;
        box-shadow: 0 1px 3px rgba(5,150,105,0.2);
    }
    .imei-btn.has-imei:hover {
        background: linear-gradient(135deg, #a7f3d0, #6ee7b7);
        box-shadow: 0 2px 6px rgba(5,150,105,0.3);
    }

    /* Selected customer chip */
    #partySearch.selected {
        background: linear-gradient(135deg, #eff6ff, #eef2ff) !important;
        border-color: #6366f1 !important;
        color: #4338ca !important;
        font-weight: 600;
    }
    #partySearch:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12) !important;
        background: #fff !important;
        outline: none;
    }
    .add-row-strip {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 11px 16px; cursor: pointer;
        border-top: 1px dashed #c7d2fe;
        color: #94a3b8; font-size: 0.83rem; font-weight: 600;
        transition: all 0.15s; user-select: none;
        background: #fff;
    }
    .add-row-strip:hover { background: rgba(99,102,241,0.05); color: #6366f1; border-top-color: #6366f1; }
    .add-row-strip .plus-circle {
        width: 22px; height: 22px; border-radius: 50%;
        background: rgba(99,102,241,0.1);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1rem; color: #6366f1; line-height: 1; flex-shrink: 0;
        transition: background 0.15s;
    }
    .add-row-strip:hover .plus-circle { background: rgba(99,102,241,0.2); }

    /* BOTTOM AREA */
    .sale-bottom {
        display: flex; gap: 24px; padding: 1rem 1.25rem;
        border-top: 1px solid #e0e0e0; background: #fff;
        flex-wrap: wrap;
    }
    .sale-bottom-left { flex: 1; min-width: 260px; }
    .sale-bottom-left .extra-btn {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 0; color: #555; font-size: 0.82rem;
        cursor: pointer; border: none; background: none; text-decoration: none;
    }
    .sale-bottom-left .extra-btn i { color: #999; }

    .sale-totals { min-width: 280px; }
    .totals-row {
        display: flex; justify-content: space-between;
        padding: 5px 0; border-bottom: 1px solid #f3f4f6;
        font-size: 0.85rem; color: #555;
    }
    .totals-row:last-child { border-bottom: none; }
    .totals-row.grand {
        font-size: 1rem; font-weight: 700; color: #222;
        border-top: 2px solid #e5e7eb; padding-top: 8px; margin-top: 4px;
    }
    .totals-row input {
        width: 140px; text-align: right;
        border: 1px solid #d1d5db; border-radius: 4px;
        padding: 2px 6px; font-size: 0.85rem; background: #fff; color: #222;
    }
    .totals-row input:focus { outline: none; border-color: #3b82f6; }

    /* PAYMENT SECTION */
    .payment-section {
        background: #f8fafc; border: 1px solid #e5e7eb;
        border-radius: 8px; padding: 12px; margin-top: 10px;
    }
    .payment-section label { font-size: 0.8rem; color: #555; font-weight: 500; margin-bottom: 4px; }
    .payment-section .form-control,
    .payment-section .form-select {
        font-size: 0.82rem; border: 1px solid #d1d5db; background: #fff;
        color: #222; border-radius: 5px; padding: 5px 8px; height: 32px;
    }

    /* SAVE BAR */
    .save-bar {
        display: flex; justify-content: flex-end; align-items: center; gap: 10px;
        padding: 0.6rem 1.25rem; background: #fff;
        border-top: 1px solid #e0e0e0;
        position: sticky; bottom: 0; z-index: 90;
    }
    .btn-print-save {
        background: #f8f9fa; border: 1px solid #d1d5db;
        color: #444; padding: 6px 16px; font-size: 0.85rem;
        border-radius: 5px; cursor: pointer;
    }
    .btn-save-main {
        background: #3b82f6; border: none; color: #fff;
        padding: 6px 28px; font-size: 0.9rem; font-weight: 600;
        border-radius: 5px; cursor: pointer;
    }
    .btn-save-main:hover { background: #2563eb; }

    /* AUTOCOMPLETE DROPDOWN */
    .autocomplete-box {
        position: absolute; top: 100%; left: 0; right: 0;
        background: #fff; border: 1px solid #d1d5db;
        border-radius: 5px; z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        max-height: 220px; overflow-y: auto;
    }
    .autocomplete-item {
        padding: 7px 12px; cursor: pointer; font-size: 0.82rem;
        border-bottom: 1px solid #f3f4f6; color: #222;
    }
    .autocomplete-item:hover { background: #eff6ff; }
    .autocomplete-item small { color: #888; }

    /* IMEI MODAL */
    .imei-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.45);
        z-index: 9999; display: none; align-items: center; justify-content: center;
    }
    .imei-modal-overlay.show { display: flex; }
    .imei-modal {
        background: #fff; border-radius: 10px; padding: 24px 28px;
        width: 100%; max-width: 480px; box-shadow: 0 8px 30px rgba(0,0,0,0.18);
        color: #222;
    }
    .imei-modal-title {
        font-size: 1rem; font-weight: 700; margin-bottom: 18px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .imei-modal-title button {
        background: none; border: none; font-size: 1.2rem; color: #888; cursor: pointer;
    }
    .imei-input-row { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; }
    .imei-input-row input {
        flex: 1; border: 1px solid #d1d5db; border-radius: 5px;
        padding: 7px 10px; font-size: 0.88rem; color: #222;
    }
    .imei-input-row input:focus { outline: none; border-color: #3b82f6; }
    .imei-confirm-btn {
        background: #3b82f6; border: none; color: #fff;
        border-radius: 5px; width: 36px; height: 34px;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .imei-entered-count { font-size: 0.8rem; color: #888; margin-bottom: 10px; }
    .imei-list-area { min-height: 80px; }
    .imei-tag {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eff6ff; border: 1px solid #bfdbfe;
        border-radius: 5px; padding: 3px 8px; margin: 3px; font-size: 0.8rem;
        color: #1d4ed8;
    }
    .imei-tag .remove { cursor: pointer; color: #94a3b8; font-size: 0.9rem; }
    .imei-tag .remove:hover { color: #ef4444; }
    .imei-msg { font-size: 0.78rem; padding: 3px 6px; border-radius: 4px; margin-top: 2px; }
    .imei-msg.ok { background: #d1fae5; color: #065f46; }
    .imei-msg.err { background: #fee2e2; color: #991b1b; }
    .imei-modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .btn-close-modal { background: #f3f4f6; border: 1px solid #e5e7eb; color: #444; padding: 6px 18px; border-radius: 5px; cursor: pointer; }
    .btn-save-modal { background: #3b82f6; border: none; color: #fff; padding: 6px 22px; border-radius: 5px; font-weight: 600; cursor: pointer; }
    .btn-save-modal:hover { background: #2563eb; }
</style>

<form method="POST" action="?page=sales&action=store" id="saleForm">
<div class="sale-page-wrap">

    <!-- TOP BAR -->
    <div class="sale-topbar">
        <div class="d-flex align-items-center gap-3">
            <span class="sale-title">Sale</span>
            <div class="toggle-wrap">
                <span class="toggle-label" id="payModeLabel">Credit</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="payModeToggle" onchange="togglePayMode(this)">
                    <span class="toggle-slider"></span>
                </label>
                <span class="toggle-label">Cash</span>
            </div>
            <input type="hidden" name="payment_mode" id="paymentMode" value="credit">
        </div>

        <div class="d-flex align-items-center gap-3">
            <input type="hidden" name="warehouse_id" id="warehouseSelect" value="<?= Auth::warehouseId() ?>">
            <span style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:5px 12px;color:#10b981;font-size:0.82rem;font-weight:600;">
                <i class="bi bi-building me-1"></i><?= htmlspecialchars(Auth::warehouseName()) ?>
            </span>
        </div>
    </div>

    <!-- PARTY ROW -->
    <div class="party-row">
        <div style="position:relative; flex:1; max-width:380px;">
            <div style="position:relative;">
                <i class="bi bi-person-circle" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;"></i>
                <input type="text" id="partySearch" class="form-control" placeholder="Search customer..."
                       autocomplete="off"
                       style="padding-left:34px;height:38px;border:1.5px solid #c7d2fe;border-radius:8px;font-size:0.875rem;background:#fafbff;transition:all 0.15s;">
            </div>
            <div class="autocomplete-box" id="partyDropdown" style="display:none;"></div>
            <input type="hidden" name="party_id" id="partyIdInput" required>
        </div>

        <!-- Invoice info (right side) -->
        <div class="inv-info ms-auto">
            <span>Invoice Number <strong><?= $nextInv ?></strong></span>
            <span>Invoice Date
                <input type="date" name="date" value="<?= date('Y-m-d') ?>"
                       style="border:1px solid #d1d5db;border-radius:4px;padding:1px 5px;font-size:0.8rem;color:#222;">
            </span>
            <span>Time <strong id="liveClock"><?= date('h:i A') ?></strong></span>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="items-section">
        <div class="items-table-wrap">
            <table class="items-tbl" id="itemsTable">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th class="col-item">ITEM</th>
                        <th class="col-imei"></th>
                        <th class="col-qty">QTY</th>
                        <th class="col-price">PRICE/UNIT</th>
                        <th class="col-amt">AMOUNT</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- rows added by JS -->
                </tbody>
                <tfoot>
                    <tr style="background:#f8f9ff;border-top:2px solid #e0e7ff;">
                        <td colspan="3"></td>
                        <td class="col-qty text-center" id="totalQtyFoot" style="font-weight:700;color:var(--text-main);">0</td>
                        <td></td>
                        <td class="col-amt" id="subtotalFoot" style="font-weight:700;color:var(--text-main);text-align:right;padding-right:10px;">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Add Row Strip -->
        <div class="add-row-strip" onclick="addRow()">
            <span class="plus-circle">+</span>
            Add Item Row
        </div>
    </div>

    <!-- BOTTOM AREA -->
    <div class="sale-bottom">

        <!-- Left: description / image / document -->
        <div class="sale-bottom-left">
            <button type="button" class="extra-btn" onclick="toggleNotes()">
                <i class="bi bi-card-text"></i> ADD DESCRIPTION
            </button>
            <div id="notesArea" style="display:none;margin-top:6px;">
                <textarea name="notes" class="form-control" rows="2"
                    placeholder="Notes / description..."
                    style="border:1px solid #d1d5db;color:#222;background:#fff;font-size:0.82rem;"></textarea>
            </div>

            <!-- Payment section (shown only in cash mode) -->
            <div id="paymentSection" class="payment-section mt-3" style="display:none;">
                <div class="row g-2">
                    <div class="col-6">
                        <label>Amount Paid</label>
                        <input type="number" name="paid_amount" id="paidAmountInput" class="form-control"
                            step="0.001" min="0" placeholder="0.000" oninput="calcTotals()">
                    </div>
                    <div class="col-6">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="mobile_wallet">Mobile Wallet</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label>Account</label>
                        <select name="account_id" class="form-select">
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Totals -->
        <div class="sale-totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span id="subtotalDisplay">0.000</span>
            </div>
            <div class="totals-row">
                <span>Discount</span>
                <input type="number" name="discount" id="discountInput" step="0.001" min="0"
                    value="0.000" oninput="calcTotals()" placeholder="0.000">
            </div>
            <input type="hidden" name="tax" value="0">
            <div class="totals-row grand">
                <span>Total</span>
                <span id="grandTotalDisplay">0.000</span>
            </div>
            <div class="totals-row" id="balanceRow" style="display:none;">
                <span>Balance Due</span>
                <span id="balanceDueDisplay" style="color:#dc2626;font-weight:700;">0.000</span>
            </div>
        </div>
    </div>

    <!-- SAVE BAR -->
    <input type="hidden" name="print_mode" id="salePrintMode" value="0">
    <div class="save-bar">
        <a href="?page=sales" class="btn-print-save">Cancel</a>
        <button type="submit" name="action_type" value="save" class="btn-save-main"
                onclick="document.getElementById('salePrintMode').value='0'">
            <i class="bi bi-check-lg me-1"></i> Save
        </button>
        <button type="submit" name="action_type" value="save"
                onclick="document.getElementById('salePrintMode').value='1'"
                style="background:#059669;border:none;color:#fff;padding:6px 22px;font-size:0.9rem;font-weight:600;border-radius:5px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-printer"></i> Print & Save
        </button>
    </div>

</div><!-- end sale-page-wrap -->
</form>

<!-- ===== IMEI SCAN MODAL ===== -->
<div class="imei-modal-overlay" id="imeiModal">
    <div class="imei-modal">
        <div class="imei-modal-title">
            <div>
                <div style="font-size:0.75rem;color:#888;font-weight:400;margin-bottom:2px;">Scanning IMEI for:</div>
                <div id="imeiModalItemName" style="font-size:0.95rem;font-weight:700;color:#1e3a5f;"></div>
            </div>
            <button onclick="closeImeiModal()" style="background:none;border:none;font-size:1.4rem;color:#aaa;cursor:pointer;line-height:1;">×</button>
        </div>



        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label style="font-size:0.85rem;color:#555;font-weight:500;">IMEI / Serial Number:</label>
            <span id="imeiCount" style="font-size:0.8rem;">0 Entered</span>
            <span id="imeiRequired" style="font-size:0.78rem;color:#f59e0b;margin-left:4px;"></span>
        </div>
        <div class="imei-input-row">
            <input type="text" id="imeiScanInput" placeholder="Scan barcode or type 15-digit IMEI"
                   maxlength="15"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();confirmImei();}"
                   style="font-family:monospace;font-size:0.95rem;letter-spacing:1px;">
            <button type="button" class="imei-confirm-btn" onclick="confirmImei()" title="Add IMEI">
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
        <div id="imeiMsg" style="margin:4px 0 8px;min-height:22px;"></div>
        <div class="imei-list-area" id="imeiTagList"></div>
        <div class="imei-modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeImeiModal()">Cancel</button>
            <button type="button" class="btn-save-modal" onclick="saveImeiModal()">
                <i class="bi bi-check-lg me-1"></i> Done
            </button>
        </div>
    </div>
</div>

<script>
// ============ STATE ============
let rowCount     = 0;
let currentImeiRow = null;  // which row is being edited
let imeiData     = {};      // rowId -> array of imeis
let warehouseId  = document.getElementById('warehouseSelect').value;
let payMode      = 'credit';

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
    addRow(); addRow();
    setInterval(updateClock, 1000);
});

function updateClock() {
    const now = new Date();
    let h = now.getHours(); const m = String(now.getMinutes()).padStart(2,'0');
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    document.getElementById('liveClock').textContent = `${String(h).padStart(2,'0')}:${m} ${ampm}`;
}

function updateWarehouse(val) {
    warehouseId = val;
}

// ============ PAY MODE TOGGLE ============
function togglePayMode(chk) {
    payMode = chk.checked ? 'cash' : 'credit';
    document.getElementById('paymentMode').value = payMode;
    document.getElementById('paymentSection').style.display = chk.checked ? 'block' : 'none';
    document.getElementById('balanceRow').style.display = chk.checked ? 'flex' : 'none';
    calcTotals();
}

// ============ ADD/REMOVE ROWS ============
function addRow() {
    rowCount++;
    const rid = 'row_' + rowCount;
    imeiData[rid] = [];

    const tr = document.createElement('tr');
    tr.id = rid;
    tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num" style="text-align:center;color:#aaa;">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="item-search" placeholder="Search item..."
                   data-row="${rid}" autocomplete="off" oninput="searchItem(this,'${rid}')">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <input type="hidden" name="items[${rowCount}][has_imei]" id="hasImei_${rid}" value="0">
            <input type="hidden" name="items[${rowCount}][unit]" id="unit_${rid}" value="pcs">
            <input type="hidden" name="items[${rowCount}][discount]" id="disc_${rid}" value="0">
            <div class="autocomplete-box" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-imei">
            <button type="button" class="imei-btn" id="imeiBtn_${rid}" onclick="openImeiModal('${rid}')"
                    style="display:inline-flex;">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty">
            <input type="number" name="items[${rowCount}][quantity]" id="qty_${rid}"
                   value="1" min="1" style="text-align:center;" oninput="calcRow('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" name="items[${rowCount}][unit_price]" id="price_${rid}"
                   value="" step="0.001" placeholder="0.000"
                   style="text-align:right;" oninput="calcRow('${rid}')">
        </td>
        <td class="col-amt" id="amt_${rid}" style="text-align:right;padding-right:8px;font-weight:600;">
            0.000
        </td>
        <td class="col-act" style="text-align:center;">
            <button type="button" onclick="removeRow('${rid}')"
                    style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:1rem;">×</button>
        </td>
        <input type="hidden" name="items[${rowCount}][imeis]" id="imeiInput_${rid}" value="">
    `;
    document.getElementById('itemsBody').appendChild(tr);
}

function removeRow(rid) {
    const el = document.getElementById(rid);
    if (el) el.remove();
    delete imeiData[rid];
    renumberRows();
    calcTotals();
}

function renumberRows() {
    let i = 1;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        tr.querySelector('.col-num').textContent = i++;
    });
}

// ============ ITEM SEARCH ============
const saleItemStore = {};
let searchTimers = {};
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
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}" style="cursor:pointer;">
                        <strong>${it.name}</strong>
                        ${it.sku ? `<small> · ${it.sku}</small>` : ''}
                        <small class="float-end" style="color:#3b82f6;">Stock: ${it.stock}</small>
                        <br><small style="color:#888;">${it.sale_price} ${it.has_imei ? '· <span style="color:#059669">IMEI</span>' : ''}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const r   = this.dataset.rid;
                        const idx = parseInt(this.dataset.idx);
                        selectItem(r, saleItemStore[r][idx]);
                    });
                });
                drop.style.display = 'block';
            });
    }, 250);
}

function selectItem(rid, item) {
    const searchInput = document.querySelector('#' + rid + ' .item-search');
    if (searchInput) searchInput.value = item.name;
    document.getElementById('itemId_'   + rid).value         = item.id;
    document.getElementById('hasImei_'  + rid).value         = item.has_imei;
    document.getElementById('price_'    + rid).value         = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('unit_'     + rid).value         = item.unit;
    document.getElementById('itemDrop_' + rid).style.display = 'none';

    const imeiBtn = document.getElementById('imeiBtn_' + rid);
    if (imeiBtn && activeImeis.length > 0) imeiBtn.classList.add('has-imei');

    calcRow(rid);

    // Auto-open IMEI modal immediately after item is selected
    setTimeout(() => openImeiModal(rid, item.name), 120);

    // Auto-add new row if this is the last row
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows[rows.length - 1]?.id === rid) {
        addRow();
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', e => {
    if (!e.target.closest('.col-item')) {
        document.querySelectorAll('.autocomplete-box').forEach(d => {
            if (d.id !== 'partyDropdown') d.style.display = 'none';
        });
    }
    if (!e.target.closest('.party-row')) {
        document.getElementById('partyDropdown').style.display = 'none';
    }
});

// ============ PARTY SEARCH ============
const partyStore = {};
let partyTimer;
document.getElementById('partySearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('partyIdInput').value = '';
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
                drop.innerHTML = parties.map((p, idx) => `
                    <div class="autocomplete-item" data-idx="${idx}" style="cursor:pointer;">
                        <strong>${p.name}</strong>
                        <small class="float-end" style="color:#888;">${p.phone || ''}</small>
                        ${parseFloat(p.balance) > 0 ? `<br><small style="color:#dc2626;">Balance: ${parseFloat(p.balance).toFixed(3)}</small>` : ''}
                    </div>
                `).join('');
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

function selectParty(party) {
    const el = document.getElementById('partySearch');
    el.value = party.name;
    el.classList.add('selected');
    document.getElementById('partyIdInput').value   = party.id;
    document.getElementById('partyDropdown').style.display = 'none';
}

// ============ ROW CALCULATIONS ============
function calcRow(rid) {
    const qty   = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
    const price = parseFloat(document.getElementById('price_' + rid)?.value) || 0;
    const disc  = parseFloat(document.getElementById('disc_'  + rid)?.value) || 0;
    const amt   = (qty * price) - disc;
    document.getElementById('amt_' + rid).textContent = amt.toFixed(3);
    calcTotals();
}

function calcTotals() {
    let subtotal = 0;
    let totalQty = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid    = tr.dataset.rowId;
        if (!rid) return;
        const qty    = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
        const price  = parseFloat(document.getElementById('price_' + rid)?.value) || 0;
        const disc   = parseFloat(document.getElementById('disc_'  + rid)?.value) || 0;
        subtotal    += (qty * price) - disc;
        totalQty    += qty;
    });

    const discount    = parseFloat(document.getElementById('discountInput').value) || 0;
    const grandTotal  = subtotal - discount;
    const paid        = parseFloat(document.getElementById('paidAmountInput')?.value) || 0;
    const balance     = grandTotal - paid;

    document.getElementById('subtotalDisplay').textContent   = subtotal.toFixed(3);
    document.getElementById('subtotalFoot').textContent      = subtotal.toFixed(3);
    document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(3);
    document.getElementById('totalQtyFoot').textContent      = totalQty;

    if (document.getElementById('balanceDueDisplay')) {
        document.getElementById('balanceDueDisplay').textContent = Math.max(0, balance).toFixed(3);
    }
}

// ============ NOTES TOGGLE ============
function toggleNotes() {
    const area = document.getElementById('notesArea');
    area.style.display = area.style.display === 'none' ? 'block' : 'none';
}

// ============ IMEI MODAL ============
let activeImeis    = [];
let currentItemName = '';

function openImeiModal(rid, itemName) {
    currentImeiRow  = rid;
    currentItemName = itemName || document.querySelector('#' + rid + ' .item-search')?.value || 'Item';
    activeImeis     = [...(imeiData[rid] || [])];

    // Update modal title with item name
    document.getElementById('imeiModalItemName').textContent = currentItemName;

    // Show required count based on qty
    const qty = parseInt(document.getElementById('qty_' + rid)?.value) || 0;
    document.getElementById('imeiRequired').textContent = qty > 0 ? `(need ${qty})` : '';

    renderImeiTags();
    document.getElementById('imeiModal').classList.add('show');
    document.getElementById('imeiScanInput').value = '';
    document.getElementById('imeiMsg').innerHTML   = '';
    setTimeout(() => document.getElementById('imeiScanInput').focus(), 80);
}

function closeImeiModal() {
    document.getElementById('imeiModal').classList.remove('show');
}

function getAllEnteredImeis(excludeRow) {
    // Collect all IMEIs entered in other rows to prevent global duplicates
    const all = [];
    Object.keys(imeiData).forEach(r => {
        if (r !== excludeRow) all.push(...imeiData[r]);
    });
    return all;
}

function confirmImei() {
    const input = document.getElementById('imeiScanInput');
    const imei  = input.value.trim();
    if (!imei) return;

    // Validate: must be exactly 15 digits
    if (!/^\d{15}$/.test(imei)) {
        showImeiMsg('IMEI must be exactly 15 digits (numbers only).', 'err');
        input.select();
        return;
    }

    // Check duplicate in current row
    if (activeImeis.includes(imei)) {
        showImeiMsg('This IMEI is already added to this item.', 'err');
        input.select();
        return;
    }

    // Check duplicate across other rows in this invoice
    const otherImeis = getAllEnteredImeis(currentImeiRow);
    if (otherImeis.includes(imei)) {
        showImeiMsg('This IMEI is already used in another row on this invoice.', 'err');
        input.select();
        return;
    }

    const itemId = document.getElementById('itemId_' + currentImeiRow)?.value || 0;

    // Check against database (already sold / belongs to different item)
    fetch(`?page=sales&action=checkImei&imei=${encodeURIComponent(imei)}&item_id=${itemId}`)
        .then(r => r.json())
        .then(res => {
            if (res.valid) {
                activeImeis.push(imei);
                renderImeiTags();
                showImeiMsg('✓ ' + res.message, 'ok');
                input.value = '';
                input.focus();
            } else {
                showImeiMsg('✗ ' + res.message, 'err');
                input.select();
            }
        });
}

function showImeiMsg(msg, type) {
    const el = document.getElementById('imeiMsg');
    el.innerHTML = `<div class="imei-msg ${type}">${msg}</div>`;
    if (type === 'ok') {
        setTimeout(() => el.innerHTML = '', 2000);
    }
}

function renderImeiTags() {
    const qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;
    const entered = activeImeis.length;
    document.getElementById('imeiCount').innerHTML =
        `<span style="color:${entered >= qty && qty > 0 ? '#059669' : '#f59e0b'};">` +
        `${entered} Entered</span>` +
        (qty > 0 ? ` <span style="color:#aaa;">/ ${qty} needed</span>` : '');

    document.getElementById('imeiTagList').innerHTML = activeImeis.map((im, i) => `
        <span class="imei-tag">
            <span style="font-family:monospace;letter-spacing:0.5px;">${im}</span>
            <span class="remove" onclick="removeImei(${i})" title="Remove">×</span>
        </span>
    `).join('');
}

function removeImei(idx) {
    activeImeis.splice(idx, 1);
    renderImeiTags();
}

function saveImeiModal() {
    if (!currentImeiRow) return;

    const qty = parseInt(document.getElementById('qty_' + currentImeiRow)?.value) || 0;

    // Warn if count doesn't match qty (but don't block)
    if (qty > 0 && activeImeis.length !== qty) {
        const ok = confirm(
            `You entered ${activeImeis.length} IMEI(s) but quantity is ${qty}.\n` +
            `The quantity will be updated to match. Continue?`
        );
        if (!ok) return;
    }

    imeiData[currentImeiRow] = [...activeImeis];
    document.getElementById('imeiInput_' + currentImeiRow).value = activeImeis.join('\n');

    // Update IMEI button
    const btn = document.getElementById('imeiBtn_' + currentImeiRow);
    if (btn) {
        if (activeImeis.length > 0) {
            btn.classList.add('has-imei');
            btn.innerHTML = `<i class="bi bi-upc-scan"></i> ${activeImeis.length}`;
        } else {
            btn.classList.remove('has-imei');
            btn.innerHTML = `<i class="bi bi-upc-scan"></i>`;
        }
    }

    // Sync qty to IMEI count
    const qtyField = document.getElementById('qty_' + currentImeiRow);
    if (qtyField && activeImeis.length > 0) {
        qtyField.value = activeImeis.length;
        calcRow(currentImeiRow);
    }

    closeImeiModal();
}

// IMEI quick search in party bar
document.getElementById('imeiQuickSearch')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const imei = this.value.trim();
        if (!imei) return;
        fetch(`?page=sales&action=checkImei&imei=${encodeURIComponent(imei)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.valid) {
                    alert('IMEI: ' + res.message);
                } else {
                    alert('IMEI found - ' + res.message);
                }
            });
    }
});

// Form validation before submit
document.getElementById('saleForm').addEventListener('submit', function(e) {
    if (!document.getElementById('partyIdInput').value) {
        e.preventDefault();
        alert('Please select a customer.');
        document.getElementById('partySearch').focus();
        return;
    }
    let hasItem = false;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (rid && document.getElementById('itemId_' + rid)?.value) hasItem = true;
    });
    if (!hasItem) {
        e.preventDefault();
        alert('Please add at least one item.');
    }
});
</script>
