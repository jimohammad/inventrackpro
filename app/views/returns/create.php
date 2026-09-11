<style>
.sale-wrap { display:flex; flex-direction:column; gap:0; }
.sale-topbar {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 20px;
    background:#1e3a5f;
    border-radius:0;
    position:sticky; top:58px; z-index:90;
}
.sale-topbar .sale-title {
    font-size:1.05rem; font-weight:700; color:#fff;
    display:flex; align-items:center; gap:8px;
}
.sale-composer {
    background:#fff;
    border-left:1px solid #e5e7eb;
    border-right:1px solid #e5e7eb;
    border-top:none; border-bottom:none;
}
.sale-composer-row {
    display:grid;
    grid-template-columns: minmax(0,1fr) 158px 150px;
    gap:10px; align-items:stretch;
    padding:12px 20px; position:relative; z-index:3;
}
.sale-composer-scan { z-index:1; padding-top:10px; padding-bottom:12px; background:#fff; }
.sale-field { position:relative; min-width:0; z-index:2; }
.sale-field .search-icon,
.sale-field .scan-bar-inner-icon {
    position:absolute; left:12px; top:50%; transform:translateY(-50%);
    color:#6366f1; font-size:1rem; z-index:2; pointer-events:none;
}
.sale-field input:not([type="hidden"]) {
    width:100%; height:44px; min-height:44px;
    padding:0 12px 0 40px;
    border:1.5px solid #e2e8f0; border-radius:0;
    font-size:0.92rem; font-weight:600; color:#1a1a2e; background:#fff;
    transition:border-color 0.15s, box-shadow 0.15s; outline:none;
}
.sale-field input:not([type="hidden"]):focus {
    border-color:#6366f1; background:#fff;
    box-shadow:0 0 0 3px rgba(99,102,241,0.12);
}
.sale-field input.selected { border-color:#10b981; background:#f0fdf4; color:#065f46; }
.sale-chip {
    height:44px; min-height:44px;
    display:flex; flex-direction:column; justify-content:center;
    padding:0 12px; border-radius:0;
    background:#f8fafc; border:1.5px solid #e2e8f0;
}
.sale-chip-label {
    font-size:0.62rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.08em; color:#94a3b8; line-height:1.1;
}
.sale-chip-value {
    font-size:0.82rem; font-weight:800; color:#4338ca;
    letter-spacing:0.03em; line-height:1.25;
}
.sale-chip-date { padding:3px 10px 3px 12px; }
.sale-chip-date input[type="date"] {
    width:100%; border:none; background:transparent; outline:none;
    padding:0; height:auto; min-height:0;
    font-size:0.82rem; font-weight:700; color:#334155; color-scheme:light;
}
.scan-bar-wrap { position:relative; min-width:0; }
.scan-bar-input { letter-spacing:0.3px; font-weight:500; }
.scan-bar-input::placeholder,
.sale-field input::placeholder {
    font-family:inherit; font-size:0.82rem; font-weight:500; letter-spacing:normal; color:#94a3b8;
}
.scan-bar-msg {
    font-size:0.78rem; font-weight:600; text-align:center;
    display:none; align-items:center; justify-content:center; gap:6px;
    padding:0 20px 8px; background:#fff;
}
.scan-bar-msg:not(:empty) { display:flex; }
.scan-bar-msg.ok { color:#065f46; }
.scan-bar-msg.warn { color:#92400e; }
.scan-bar-msg.err { color:#991b1b; }
.scan-bar-count {
    display:inline-flex; align-items:center; justify-content:center;
    height:44px; padding:0 12px; border-radius:0;
    font-size:0.82rem; color:#4338ca; font-weight:800; white-space:nowrap;
    background:#e0e7ff; border:1.5px solid #c7d2fe; letter-spacing:0.02em;
}
.ret-bal-badge {
    margin:0 20px 8px; padding:6px 14px; border-radius:8px;
    font-size:0.82rem; font-weight:700; white-space:nowrap; width:fit-content;
}
.items-card {
    border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb;
    border-top:none; border-bottom:1px solid #e5e7eb;
    border-radius:0; background:#fff; overflow:visible;
}
table.items-tbl {
    width:100%; table-layout:fixed; border-collapse:collapse; border-spacing:0; font-size:0.92rem;
    border:1px solid #e2e8f0;
}
table.items-tbl th,
table.items-tbl td { box-shadow:none !important; }
table.items-tbl th {
    padding:8px 8px; font-size:0.68rem; font-weight:800;
    text-transform:uppercase; letter-spacing:0.08em; color:#4338ca;
    background:#eef2ff;
    white-space:nowrap;
    border:1px solid #c7d2fe !important;
}
table.items-tbl th.col-num { color:#6366f1; padding-left:8px; }
table.items-tbl th.col-act { padding-right:8px; }
table.items-tbl tbody td {
    padding:0 !important; vertical-align:middle; background:#fff;
    border:1px solid #e2e8f0 !important; height:48px;
}
table.items-tbl tbody tr { background:#fff; }
table.items-tbl tbody tr:hover td.col-num,
table.items-tbl tbody tr:hover td.col-item,
table.items-tbl tbody tr:hover td.col-imei,
table.items-tbl tbody tr:hover td.col-qty,
table.items-tbl tbody tr:hover td.col-act { background:#f8faff; }
table.items-tbl tfoot td { background:#fff !important; border:none !important; }
.col-num { width:36px; text-align:center; color:#94a3b8; font-size:0.85rem; font-weight:600; background:#f8fafc; }
table.items-tbl tbody td.col-num { background:#f8fafc; line-height:48px; }
.col-item { min-width:220px; }
.col-imei { width:48px; text-align:center; }
.col-qty { width:72px; text-align:center; }
.col-price { width:110px; text-align:right; }
.col-amt { width:118px; text-align:right; font-weight:700; }
.col-act { width:36px; text-align:center; }
.sale-cell-input {
    display:block; width:100%; height:48px; box-sizing:border-box;
    border:none !important; border-radius:0; background:#fff; outline:none;
    font-size:0.92rem; font-weight:500; color:#1e293b;
    padding:0 10px; line-height:48px;
    box-shadow:none !important;
}
.sale-cell-input::placeholder { color:#94a3b8; font-weight:400; font-size:0.85rem; }
.sale-cell-input:focus {
    background:#fff; outline:2px solid #6366f1; outline-offset:-2px;
    position:relative; z-index:2;
}
.sale-cell-input.item-search { font-weight:400; padding-left:10px; }
.sale-cell-input.qty { text-align:center; font-variant-numeric:tabular-nums; }
.sale-cell-input.qty::-webkit-inner-spin-button,
.sale-cell-input.qty::-webkit-outer-spin-button,
.sale-cell-input.unit::-webkit-inner-spin-button,
.sale-cell-input.unit::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
.sale-cell-input.qty,
.sale-cell-input.unit { -moz-appearance:textfield; appearance:textfield; }
.sale-cell-input.unit {
    text-align:right; color:#b45309; background:#fffbeb;
    font-variant-numeric:tabular-nums;
}
.sale-cell-input.unit:focus { background:#fffbeb; }
.sale-cell-input.unit[readonly] { cursor:default; }
.sale-cell-input.total {
    display:flex; align-items:center; justify-content:flex-end;
    text-align:right; color:#4338ca; background:#eef2ff;
    font-weight:700; font-variant-numeric:tabular-nums;
}
.sale-row-remove {
    display:flex; align-items:center; justify-content:center;
    width:100%; height:48px; border-radius:0;
    background:none; border:none; color:#94a3b8; cursor:pointer;
    font-size:1.15rem; line-height:1; padding:0;
}
.sale-row-remove:hover { color:#dc2626; background:#fef2f2; }
.imei-btn {
    background:#f8fafc; border:none; color:#6366f1;
    border-radius:0; width:100%; height:48px; min-width:0; padding:0;
    font-size:0.85rem; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:2px;
    font-weight:700; white-space:nowrap; box-sizing:border-box;
}
.imei-btn i { font-size:1.1rem; line-height:1; }
.imei-btn:hover { background:#eef2ff; }
.imei-btn.has-imei { background:#d1fae5; color:#059669; }
.sale-gt-cell { padding:10px 0 0 !important; vertical-align:top; background:#fff !important; }
.sale-btns-cell { padding:8px 0 12px !important; vertical-align:middle; background:#fff !important; }
.sale-cancel-cell { padding:8px 8px 12px 0 !important; text-align:right; vertical-align:middle; background:#fff !important; }
.gt-card {
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; padding:0 14px;
    width:100%; height:64px; min-height:64px; box-sizing:border-box;
    background:#1e3a5f;
    border-radius:0; box-shadow:none; position:relative; overflow:hidden;
}
.gt-card::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:3px;
    border-radius:0; background:#818cf8;
}
.gt-meta { display:flex; flex-direction:column; gap:2px; padding-left:8px; min-width:0; }
.gt-label {
    font-size:0.62rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.12em; color:rgba(255,255,255,0.62); line-height:1.2;
}
.gt-qty { font-size:0.92rem; font-weight:800; color:#e0e7ff; letter-spacing:0.01em; line-height:1.2; }
.gt-amount { display:flex; align-items:baseline; gap:8px; font-variant-numeric:tabular-nums; white-space:nowrap; }
.gt-currency { font-size:0.82rem; font-weight:700; color:#a5b4fc; letter-spacing:0.04em; }
#returnTotal { font-size:1.45rem; font-weight:800; color:#fff; letter-spacing:-0.03em; line-height:1; }
.save-actions { display:flex; align-items:stretch; gap:8px; width:100%; }
.btn-cancel-sale {
    padding:0 18px; border-radius:0; font-size:0.88rem; height:48px; min-height:48px;
    border:1px solid #e2e8f0; color:#64748b; background:#fff;
    cursor:pointer; font-weight:500; text-decoration:none;
    display:inline-flex; align-items:center; justify-content:center; white-space:nowrap;
    box-sizing:border-box;
}
.btn-cancel-sale:hover { border-color:#94a3b8; background:#f8fafc; }
.btn-save-sale, .btn-print-sale {
    flex:1 1 0; min-width:0; height:48px; min-height:48px; padding:0 14px; border-radius:0;
    font-size:0.9rem; font-weight:700; white-space:nowrap;
    border:none; color:#fff; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:none;
}
.btn-save-sale { background:#2563eb; }
.btn-save-sale:hover { background:#1d4ed8; }
.btn-print-sale { background:#047857; }
.btn-print-sale:hover { background:#065f46; }
@media (max-width: 700px) {
    .sale-composer-row { grid-template-columns: 1fr 1fr; }
    .sale-field { grid-column: 1 / -1; }
    .gt-card { width:100%; height:64px; min-height:64px; }
    #returnTotal { font-size:1.25rem; }
    .gt-qty { font-size:0.85rem; }
}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:300px;overflow-y:auto;margin-top:4px;}
.autocomplete-box.item-dropdown{position:fixed;margin-top:0;min-width:380px;width:auto;right:auto;}
.autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;transition:background 0.1s;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
.autocomplete-item.active,.autocomplete-item.active:hover{background:#eff6ff;box-shadow:inset 3px 0 0 #6366f1;outline:none;}
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
        <input type="hidden" name="warehouse_id" id="retWhSelect" value="<?= (int) Auth::warehouseId() ?>">
    </div>

    <!-- ② CUSTOMER + IMEI COMPOSER -->
    <div class="sale-composer">
        <div class="sale-composer-row">
            <div class="sale-field customer-search-wrap" id="retPartySearchWrap">
                <i class="bi bi-person-circle search-icon"></i>
                <input type="text" id="retPartySearch" placeholder="Search customer / agent..." autocomplete="off">
                <div class="autocomplete-box" id="retPartyDrop" style="display:none;"></div>
                <input type="hidden" name="party_id" id="retPartyId" required>
                <input type="hidden" name="ref_id" id="refIdInput">
            </div>
            <div class="sale-chip" title="Next return number — assigned on save">
                <span class="sale-chip-label">Return No</span>
                <span class="sale-chip-value"><?= htmlspecialchars($nextReturnNo ?? '') ?></span>
            </div>
            <div class="sale-chip sale-chip-date">
                <label class="sale-chip-label" for="retDateInput">Date</label>
                <input type="date" name="date" id="retDateInput" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="sale-composer-row sale-composer-scan">
            <div class="sale-field scan-bar-wrap">
                <i class="bi bi-upc-scan scan-bar-inner-icon" aria-hidden="true"></i>
                <input type="text" class="scan-bar-input" id="quickScanInput"
                       placeholder="Scan IMEI barcode — auto-detects model & price..."
                       autocomplete="off" aria-describedby="quickScanMsg">
            </div>
            <span></span>
            <span class="scan-bar-count" id="retQtyBadge">0 items</span>
        </div>
        <span class="scan-bar-msg" id="quickScanMsg" role="status" aria-live="polite"></span>
        <div id="retPartyBalBadge" class="ret-bal-badge" style="display:none;"></div>
    </div>

    <!-- ③ ITEMS TABLE -->
    <div class="items-card">
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="retItemsTable">
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
                        <th class="col-price">UNIT PRICE</th>
                        <th class="col-amt">TOTAL</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="returnItemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td colspan="3" class="sale-gt-cell">
                            <div class="gt-card" id="refundCard">
                                <div class="gt-meta">
                                    <span class="gt-label">Total Refund</span>
                                    <span class="gt-qty" id="gtQtyHint">0 pcs</span>
                                </div>
                                <div class="gt-amount">
                                    <span class="gt-currency"><?= defined('APP_CURRENCY') ? APP_CURRENCY : 'KWD' ?></span>
                                    <span id="returnTotal">0.000</span>
                                </div>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="sale-cancel-cell">
                            <a href="?page=returns" class="btn-cancel-sale">Cancel</a>
                        </td>
                        <td colspan="3" class="sale-btns-cell">
                            <div class="save-actions">
                                <button type="submit" class="btn-save-sale" onclick="document.getElementById('retPrintMode').value='0'">
                                    <i class="bi bi-check-lg"></i> Save Return
                                </button>
                                <button type="submit" class="btn-print-sale" onclick="document.getElementById('retPrintMode').value='1'">
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
            <input type="text" id="retImeiScanInput" placeholder="Scan or type IMEI / tablet serial..." maxlength="20"
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
let quickScanBusy = false;
let retAllowFormSubmit = false;
let retSubmitValidated = false;
const returnDraft = <?= json_encode($returnDraft ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function focusReturnQuickScan() {
    setTimeout(() => document.getElementById('quickScanInput')?.focus(), 50);
}

function clearReturnInvoiceRef() {
    const ref = document.getElementById('refIdInput');
    if (ref) ref.value = '';
    const inv = document.getElementById('invoiceSearch');
    if (inv) {
        inv.value = '';
        inv.style.borderColor = '';
        inv.style.background = '';
    }
    const drop = document.getElementById('invoiceDrop');
    if (drop) drop.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    addReturnRow(); addReturnRow();
    restoreReturnDraft();
    if (document.getElementById('retPartyId').value) {
        focusReturnQuickScan();
    } else {
        document.getElementById('retPartySearch').focus();
    }
    const whSel = document.getElementById('retWhSelect');
    if (whSel) whSel.addEventListener('change', clearReturnInvoiceRef);
});

function restoreReturnDraft() {
    if (!returnDraft || !Array.isArray(returnDraft.items) || !returnDraft.items.length) return;

    document.getElementById('returnItemsBody').innerHTML = '';
    retImeiData = {};
    returnRowCount = 0;

    const dateEl = document.getElementById('retDateInput') || document.querySelector('input[name="date"]');
    if (dateEl && returnDraft.date) dateEl.value = returnDraft.date;

    if (returnDraft.ref_id) {
        document.getElementById('refIdInput').value = String(returnDraft.ref_id);
    }

    if (returnDraft.party && returnDraft.party.id) {
        document.getElementById('retPartyId').value = String(returnDraft.party.id);
        const ps = document.getElementById('retPartySearch');
        if (ps) {
            ps.value = returnDraft.party.name || '';
            ps.classList.add('selected');
        }
    } else if (returnDraft.party_id) {
        document.getElementById('retPartyId').value = String(returnDraft.party_id);
    }

    returnDraft.items.forEach(item => {
        addReturnRow({
            item_id: item.item_id,
            item_name: item.item_name || ('Item #' + item.item_id),
            quantity: item.quantity,
            unit_price: item.unit_price,
        });
        const rid = 'rrow_' + returnRowCount;
        const imeiList = String(item.imeis || '').split(/\r?\n|,/).map(v => v.trim()).filter(Boolean);
        if (imeiList.length) {
            retImeiData[rid] = imeiList;
            const imeiEl = document.getElementById('rImei_' + rid);
            const qtyEl  = document.getElementById('rQty_' + rid);
            if (imeiEl) imeiEl.value = imeiList.join('\n');
            if (qtyEl) qtyEl.value = imeiList.length;
            const btn = document.getElementById('retImeiBtn_' + rid);
            if (btn) {
                btn.classList.add('has-imei');
                btn.innerHTML = `<i class="bi bi-upc-scan"></i> ${imeiList.length}`;
            }
        }
        calcReturnRow(rid);
    });

    addReturnRow();
    calcReturnTotal();
}

// ── ADD ROW ──
function addReturnRow(item = null) {
    returnRowCount++;
    const rid = 'rrow_' + returnRowCount;
    retImeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num">${returnRowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="sale-cell-input item-search ret-item-search" placeholder="Search item by name or SKU…" autocomplete="off"
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
            <input type="number" class="sale-cell-input qty" name="items[${returnRowCount}][quantity]" id="rQty_${rid}"
                   value="${item ? item.quantity : 1}" min="1" oninput="calcReturnRow('${rid}')">
        </td>
        <td class="col-price">
            <input type="number" class="sale-cell-input unit" name="items[${returnRowCount}][unit_price]" id="rPrice_${rid}"
                   step="0.001" placeholder="0.000"
                   value="${item ? item.unit_price : ''}" readonly title="Original sold price (locked)">
        </td>
        <td class="col-amt">
            <span class="sale-cell-input total" id="rAmt_${rid}">0.000</span>
        </td>
        <td class="col-act">
            <button type="button" class="sale-row-remove" onclick="removeReturnRow('${rid}')" title="Remove row" aria-label="Remove row">×</button>
        </td>
    `;
    document.getElementById('returnItemsBody').appendChild(tr);
    if (item) calcReturnRow(rid);
}

function removeReturnRow(rid) {
    document.getElementById(rid)?.remove();
    delete retImeiData[rid];
    ensureTrailingEmptyReturnRow();
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
    const qtyBadge = document.getElementById('retQtyBadge');
    if (qtyBadge) qtyBadge.textContent = totalQty + ' item' + (totalQty !== 1 ? 's' : '');
    const gtQty = document.getElementById('gtQtyHint');
    if (gtQty) gtQty.textContent = totalQty + ' pc' + (totalQty !== 1 ? 's' : '');
    const gtCard = document.getElementById('refundCard');
    if (gtCard) gtCard.classList.toggle('has-amount', total > 0.0005);
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
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; return; }
    retSearchTimers[rid] = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; return; }
        const whId = document.getElementById('retWhSelect')?.value || '';
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(qNow)}&warehouse_id=${encodeURIComponent(whId)}`)
            .then(r => r.json())
            .then(items => {
                if (input.value.trim() !== qNow) return;
                if (!items.length) { drop.style.display = 'none'; return; }
                returnItemStore[rid] = items;
                drop.innerHTML = items.map((it, idx) => `
                    <div class="autocomplete-item" data-rid="${rid}" data-idx="${idx}">
                        <strong>${it.name}</strong> ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <br><small style="color:#94a3b8;">sold price from invoice/IMEI${it.has_imei ? ' · <span style="color:#059669;font-weight:600;">IMEI</span>' : ''}</small>
                    </div>
                `).join('');
                drop.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectReturnItem(this.dataset.rid, returnItemStore[this.dataset.rid][parseInt(this.dataset.idx)]);
                    });
                });
                positionDropdown(input, drop);
                if (document.activeElement === input) drop.style.display = 'block';
            });
    }, 250);
}

function reopenReturnItemDropdown(input) {
    if (!input || !input.classList.contains('ret-item-search')) return;
    const rid = input.closest('tr[id^="rrow_"]')?.id;
    if (!rid) return;
    const drop = document.getElementById('rDrop_' + rid);
    if (!drop) return;
    if (document.getElementById('rItemId_' + rid)?.value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (returnItemStore[rid] || []).length) {
        positionDropdown(input, drop);
        drop.style.display = 'block';
        return;
    }
    searchReturnItem(input, rid);
}

function getRetImeiRule(row) {
    return IqbalImei.rule(
        (window.retRowSerialKindMap && window.retRowSerialKindMap[row]) || '',
        (window.retRowItemNameMap && window.retRowItemNameMap[row]) || '',
        '',
        { phoneMin: 15, phoneMax: 18 }
    );
}

function selectReturnItem(rid, item) {
    document.getElementById('rSearch_' + rid).value = item.name;
    document.getElementById('rItemId_' + rid).value = item.id;
    document.getElementById('rDrop_'   + rid).style.display = 'none';
    if (!window.retRowItemNameMap) window.retRowItemNameMap = {};
    if (!window.retRowSerialKindMap) window.retRowSerialKindMap = {};
    window.retRowItemNameMap[rid] = (item.name || '').toLowerCase();
    window.retRowSerialKindMap[rid] = item.serial_kind || 'phone';
    // Reset any highlight from quick-scan unknown IMEI
    const searchEl = document.getElementById('rSearch_' + rid);
    searchEl.style.borderColor = '';
    searchEl.style.outline = '';
    searchEl.style.background = '';
    searchEl.placeholder = 'Search item...';

    // Never use catalog sale_price — fill original sold price from linked invoice (or leave blank for IMEI scan).
    const priceEl = document.getElementById('rPrice_' + rid);
    const refId = document.getElementById('refIdInput')?.value || '';
    if (priceEl) priceEl.value = '';
    if (refId) {
        applySoldPriceFromInvoice(rid, item.id, item.name);
    } else if (retImeiData[rid] && retImeiData[rid].length > 0) {
        // Unknown IMEI + manual model: still need invoice for sold price
        showScanMsg('Link the original sale invoice so the sold price can be applied.', 'warn');
    }

    calcReturnRow(rid);
    if (item.has_imei && (!retImeiData[rid] || retImeiData[rid].length === 0)) {
        setTimeout(() => openRetImeiModal(rid, item.name), 120);
    }
    ensureTrailingEmptyReturnRow();
    // If this was a quick-scan row, refocus the scan bar
    if (retImeiData[rid] && retImeiData[rid].length > 0) {
        setTimeout(() => quickScanInput.focus(), 150);
    }
}

function applySoldPriceFromInvoice(rid, itemId, itemName) {
    const refId = document.getElementById('refIdInput')?.value || '';
    const whId = document.getElementById('retWhSelect')?.value || '';
    if (!refId || !itemId) return;
    fetch(`?page=returns&action=saleReturnLimits&ref_id=${encodeURIComponent(refId)}&warehouse_id=${encodeURIComponent(whId)}`)
        .then(r => r.json())
        .then(data => {
            const lim = (data.limits || {})[itemId];
            const priceEl = document.getElementById('rPrice_' + rid);
            if (!priceEl) return;
            if (lim && lim.unit_price != null && lim.unit_price !== '') {
                priceEl.value = parseFloat(lim.unit_price).toFixed(3);
                calcReturnRow(rid);
            } else if (lim) {
                priceEl.value = '';
                showScanMsg(`"${itemName || lim.name}" has mixed sold prices on this invoice — scan IMEIs.`, 'warn');
            } else {
                priceEl.value = '';
                showScanMsg(`"${itemName || 'Item'}" was not on the linked invoice.`, 'err');
            }
        })
        .catch(() => {});
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item') && !e.target.closest('.autocomplete-box.item-dropdown')) {
        document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    }
    if (!e.target.closest('#retPartySearchWrap')) {
        document.getElementById('retPartyDrop').style.display = 'none';
        retPartyHighlightIdx = -1;
    }
});
window.addEventListener('scroll', (e) => {
    const t = e.target;
    if (t && t.nodeType === 1 && typeof t.closest === 'function' && t.closest('.autocomplete-box')) {
        return;
    }
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
}, true);
document.getElementById('returnItemsBody').addEventListener('focusin', function(e) {
    reopenReturnItemDropdown(e.target);
});
document.getElementById('returnItemsBody').addEventListener('click', function(e) {
    reopenReturnItemDropdown(e.target);
});

// ── CUSTOMER SEARCH (names first, balances after — same as New Sale) ──
const retPartyStore = {};
let retPartyTimer;
let retPartyHighlightIdx = -1;
let retPartySearchAbort = null;

function updateRetPartyHighlight() {
    const drop = document.getElementById('retPartyDrop');
    if (!drop) return;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === retPartyHighlightIdx);
    });
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function applyReturnPartyBadge(p) {
    const badge = document.getElementById('retPartyBalBadge');
    if (!badge) return;
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
}

function selectReturnParty(p) {
    if (!p) return;
    const drop = document.getElementById('retPartyDrop');
    const inp = document.getElementById('retPartySearch');
    inp.value = p.name;
    inp.classList.add('selected');
    document.getElementById('retPartyId').value = p.id;
    drop.style.display = 'none';
    retPartyHighlightIdx = -1;

    if (!Object.prototype.hasOwnProperty.call(p, 'balance')) {
        fetch('?page=sales&action=searchPartyBalances&ids=' + encodeURIComponent(p.id) + '&type=customer')
            .then(r => r.json())
            .then(rows => {
                if (String(document.getElementById('retPartyId').value) !== String(p.id)) return;
                const extra = Array.isArray(rows) && rows[0] ? rows[0] : { balance: 0 };
                applyReturnPartyBadge(Object.assign({}, p, extra));
                focusReturnQuickScan();
            })
            .catch(() => {
                if (String(document.getElementById('retPartyId').value) !== String(p.id)) return;
                applyReturnPartyBadge({ balance: 0 });
                focusReturnQuickScan();
            });
        return;
    }

    applyReturnPartyBadge(p);
    focusReturnQuickScan();
}

function retPartyBalanceHtml(p) {
    if (!Object.prototype.hasOwnProperty.call(p, 'balance')) {
        return '<span style="color:#94a3b8;">…</span>';
    }
    const bal = parseFloat(p.balance || 0);
    if (bal > 0.001) {
        return `<span style="color:#ef4444;font-weight:700;">${bal.toFixed(3)}</span>`;
    }
    if (bal < -0.001) {
        return `<span style="color:#6366f1;font-weight:700;">-${Math.abs(bal).toFixed(3)}</span>`;
    }
    return '<span style="color:#10b981;">Clear</span>';
}

function renderRetPartyDropdown(parties, keepHighlight) {
    const drop = document.getElementById('retPartyDrop');
    const input = document.getElementById('retPartySearch');
    if (!drop) return;
    if (!parties.length) {
        drop.innerHTML = '<div style="padding:12px 14px;color:#94a3b8;font-size:0.85rem;">No matching customer</div>';
        drop.style.display = 'block';
        retPartyHighlightIdx = -1;
        retPartyStore['results'] = [];
        return;
    }
    retPartyStore['results'] = parties;
    if (!keepHighlight) retPartyHighlightIdx = -1;
    drop.innerHTML = parties.map((p, idx) => {
        const code = p.party_code ? ` <small style="color:#94a3b8;"> · ${retEscapeHtml(p.party_code)}</small>` : '';
        const phone = p.phone ? `<br><small style="color:#94a3b8;">${retEscapeHtml(p.phone)}</small>` : '';
        return `<div class="autocomplete-item" data-idx="${idx}" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <strong>${retEscapeHtml(p.name)}</strong>${code}${phone}
            </div>
            <div style="text-align:right;font-size:0.8rem;">
                ${retPartyBalanceHtml(p)}
            </div>
        </div>`;
    }).join('');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectReturnParty(retPartyStore['results'][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            retPartyHighlightIdx = parseInt(this.dataset.idx, 10);
            updateRetPartyHighlight();
        });
    });
    if (document.activeElement === input) drop.style.display = 'block';
}

function runRetPartySearch() {
    const input = document.getElementById('retPartySearch');
    const drop = document.getElementById('retPartyDrop');
    if (!input || !drop) return;
    clearTimeout(retPartyTimer);
    const q = input.value.trim();
    retPartyHighlightIdx = -1;
    if (q.length < 1) { drop.style.display = 'none'; return; }
    retPartyTimer = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; retPartyHighlightIdx = -1; return; }
        if (retPartySearchAbort) retPartySearchAbort.abort();
        retPartySearchAbort = new AbortController();
        const signal = retPartySearchAbort.signal;
        fetch('?page=sales&action=searchParties&q=' + encodeURIComponent(qNow) + '&type=customer&balances=0', { signal })
            .then(r => r.json())
            .then(parties => {
                if (input.value.trim() !== qNow) return;
                const rows = Array.isArray(parties) ? parties : [];
                renderRetPartyDropdown(rows, false);
                if (!rows.length) return;
                const ids = rows.map(p => p.id).join(',');
                return fetch('?page=sales&action=searchPartyBalances&ids=' + encodeURIComponent(ids) + '&type=customer', { signal })
                    .then(r => r.json())
                    .then(enriched => {
                        if (input.value.trim() !== qNow) return;
                        if (!Array.isArray(enriched) || !enriched.length) return;
                        renderRetPartyDropdown(enriched, true);
                    });
            })
            .catch(err => {
                if (err && err.name === 'AbortError') return;
                renderRetPartyDropdown([], false);
            });
    }, 300);
}

function reopenRetPartyDropdown() {
    const input = document.getElementById('retPartySearch');
    const drop = document.getElementById('retPartyDrop');
    if (!input || !drop) return;
    if (document.getElementById('retPartyId').value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (retPartyStore['results'] || []).length) {
        drop.style.display = 'block';
        return;
    }
    runRetPartySearch();
}

document.getElementById('retPartySearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('retPartyId').value = '';
    document.getElementById('retPartyBalBadge').style.display = 'none';
    runRetPartySearch();
});
document.getElementById('retPartySearch').addEventListener('focus', reopenRetPartyDropdown);
document.getElementById('retPartySearch').addEventListener('click', reopenRetPartyDropdown);

document.getElementById('retPartySearch').addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey && document.getElementById('retPartyId').value) {
        e.preventDefault();
        focusReturnQuickScan();
        return;
    }

    const drop = document.getElementById('retPartyDrop');
    const visible = drop.style.display !== 'none';
    const parties = retPartyStore['results'] || [];
    if (!visible || !parties.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        retPartyHighlightIdx = retPartyHighlightIdx < parties.length - 1 ? retPartyHighlightIdx + 1 : 0;
        updateRetPartyHighlight();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        retPartyHighlightIdx = retPartyHighlightIdx > 0 ? retPartyHighlightIdx - 1 : parties.length - 1;
        updateRetPartyHighlight();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        const idx = retPartyHighlightIdx >= 0 ? retPartyHighlightIdx : 0;
        selectReturnParty(parties[idx]);
    }
}, true);

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
                        focusReturnQuickScan();
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
    const imei  = IqbalImei.normalize(input.value);
    if (!imei) return;
    const rule = getRetImeiRule(retCurrentRow);
    if (!rule.test(imei)) {
        showRetImeiMsg('Need ' + rule.label + '.', 'err'); input.select(); return;
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
    const prevCount = (retImeiData[retCurrentRow] || []).length;
    if (retActiveImeis.length === 0 && prevCount > 0) {
        if (!confirm('Remove all scanned IMEIs from this row?')) return;
    }
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
    ensureTrailingEmptyReturnRow();
    closeRetImeiModal();
}

document.querySelectorAll('#retForm button[type="submit"]').forEach(function(btn) {
    btn.addEventListener('click', function() { retAllowFormSubmit = true; });
});

// Prevent accidental form submit on Enter (barcode scanners send Enter after each scan).
document.getElementById('retForm').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' && e.keyCode !== 13) return;
    // quickScanInput has its own handler below → processQuickScan()
    if (e.target && e.target.id === 'quickScanInput') return;
    if (e.target && e.target.tagName === 'INPUT' &&
        !['submit', 'hidden', 'button'].includes((e.target.type || 'text').toLowerCase())) {
        e.preventDefault();
    }
}, true);

document.getElementById('retForm').addEventListener('submit', function(e) {
    if (!retAllowFormSubmit) { e.preventDefault(); return; }

    if (this.dataset.submitting === '1') { e.preventDefault(); return; }
    if (quickScanBusy) { e.preventDefault(); retAllowFormSubmit = true; return; }

    if (!document.getElementById('retPartyId').value) {
        e.preventDefault(); alert('Please select a customer.'); retAllowFormSubmit = true; return;
    }
    let hasItem = false;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (rid && document.getElementById('rItemId_' + rid)?.value) hasItem = true;
    });
    if (!hasItem) {
        e.preventDefault(); alert('Please add at least one item.'); retAllowFormSubmit = true; return;
    }

    if (retSubmitValidated) {
        retAllowFormSubmit = false;
        retSubmitValidated = false;
        this.dataset.submitting = '1';
        this.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => { btn.disabled = true; });
        return;
    }

    const refId = document.getElementById('refIdInput').value;
    if (refId && !returnFormHasScannedImeis()) {
        e.preventDefault();
        retAllowFormSubmit = false;
        validateReturnSaleLimits(refId).then(function(errMsg) {
            if (errMsg) {
                showScanMsg('✗ ' + errMsg, 'err');
                retAllowFormSubmit = true;
                return;
            }
            retSubmitValidated = true;
            retAllowFormSubmit = true;
            const form = document.getElementById('retForm');
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        }).catch(function() {
            showScanMsg('✗ Could not verify return limits — try again.', 'err');
            retAllowFormSubmit = true;
        });
        return;
    }

    retAllowFormSubmit = false;
    this.dataset.submitting = '1';
    this.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => { btn.disabled = true; });
});

function collectReturnQtyByItem() {
    const totals = {};
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid) return;
        const itemId = parseInt(document.getElementById('rItemId_' + rid)?.value, 10);
        if (!itemId) return;
        const qty = parseInt(document.getElementById('rQty_' + rid)?.value, 10) || 0;
        totals[itemId] = (totals[itemId] || 0) + qty;
    });
    return totals;
}

function returnFormHasScannedImeis() {
    for (const rid in retImeiData) {
        if (retImeiData[rid] && retImeiData[rid].length > 0) {
            return true;
        }
    }
    return false;
}

function validateReturnSaleLimits(refId) {
    const whId = document.getElementById('retWhSelect')?.value || '';
    return fetch(`?page=returns&action=saleReturnLimits&ref_id=${encodeURIComponent(refId)}&warehouse_id=${encodeURIComponent(whId)}`)
        .then(r => r.json())
        .then(data => {
            if (data.message) return data.message;
            const limits = data.limits || {};
            const totals = collectReturnQtyByItem();
            for (const itemId in totals) {
                const qty = totals[itemId];
                const lim = limits[itemId];
                if (!lim) continue;
                const max = parseInt(lim.remaining, 10) || 0;
                if (qty > max) {
                    return `Cannot return ${qty} of "${lim.name}" — only ${max} remaining from this sale.`;
                }
            }
            return null;
        });
}

// ── QUICK SCAN ──
const quickScanInput = document.getElementById('quickScanInput');
const quickScanMsg   = document.getElementById('quickScanMsg');
let quickScanInputTimer = null;

function normalizeReturnScanImei(raw) {
    return (window.IqbalImei && IqbalImei.normalize) ? IqbalImei.normalize(raw) : String(raw || '').toUpperCase().replace(/[\r\n\t\s]/g, '');
}

function findFirstEmptyReturnRow() {
    let found = null;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rowId = tr.dataset.rowId;
        if (!rowId || found) return;
        if (document.getElementById('rItemId_' + rowId)?.value) return;
        if (retImeiData[rowId] && retImeiData[rowId].length > 0) return;
        found = rowId;
    });
    return found;
}

function ensureTrailingEmptyReturnRow() {
    let hasBlank = false;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rowId = tr.dataset.rowId;
        if (!rowId) return;
        if (!document.getElementById('rItemId_' + rowId)?.value &&
            (!retImeiData[rowId] || retImeiData[rowId].length === 0)) {
            hasBlank = true;
        }
    });
    if (!hasBlank) addReturnRow();
}

function applyReturnScanRefData(data) {
    // Prefill customer only when empty. Keep the selected return party even if the
    // IMEI was originally sold to someone else (cross-party return is allowed).
    if (data.party_id) {
        const partyEl = document.getElementById('retPartyId');
        if (partyEl && !partyEl.value) {
            partyEl.value = String(data.party_id);
            const ps = document.getElementById('retPartySearch');
            if (ps && data.party_name) {
                ps.value = data.party_name;
                ps.classList.add('selected');
            }
        }
    }
    return true;
}

function showScanMsg(msg, type) {
    if (!quickScanMsg) return;
    quickScanMsg.textContent = msg || '';
    quickScanMsg.className = 'scan-bar-msg' + (type ? ' ' + type : '');
    if (type === 'ok') {
        setTimeout(() => {
            quickScanMsg.textContent = '';
            quickScanMsg.className = 'scan-bar-msg';
        }, 3000);
    }
}

// Check if IMEI is already in any row
function isImeiAlreadyAdded(imei) {
    for (const rid in retImeiData) {
        if (retImeiData[rid].includes(imei)) return true;
    }
    return false;
}

// Find row for same item_id AND same sold price (do not mix different sold prices)
function findExistingRowForItem(itemId, unitPrice) {
    let found = null;
    const wantPrice = unitPrice != null && unitPrice !== ''
        ? parseFloat(unitPrice).toFixed(3)
        : null;
    document.querySelectorAll('#returnItemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid) return;
        if (document.getElementById('rItemId_' + rid)?.value != itemId) return;
        if (wantPrice !== null) {
            const rowPrice = parseFloat(document.getElementById('rPrice_' + rid)?.value || 0).toFixed(3);
            if (rowPrice !== wantPrice) return;
        }
        found = rid;
    });
    return found;
}

quickScanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
        e.stopPropagation();
        processQuickScan();
    }
});

// Auto-trigger when a complete IMEI is entered (barcode scanner without Enter)
quickScanInput.addEventListener('input', function() {
    if (quickScanInputTimer) clearTimeout(quickScanInputTimer);
    const val = normalizeReturnScanImei(this.value);
    if (IqbalImei.shouldAutoConfirmAny(val)) {
        quickScanInputTimer = setTimeout(() => processQuickScan(), 120);
    }
});

function parseReturnLookupResponse(r) {
    if (!r.ok) {
        throw new Error(r.status === 403 ? 'Permission denied.' : 'Server error (' + r.status + ').');
    }
    const ct = r.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
        throw new Error('Unexpected server response — refresh and try again.');
    }
    return r.json();
}

function attachReturnImeiToRow(rid, imei) {
    if (!rid) return;
    if (!retImeiData[rid]) retImeiData[rid] = [];
    if (!retImeiData[rid].includes(imei)) retImeiData[rid].push(imei);
    const imeiEl = document.getElementById('rImei_' + rid);
    const qtyEl  = document.getElementById('rQty_' + rid);
    if (imeiEl) imeiEl.value = retImeiData[rid].join('\n');
    if (qtyEl) qtyEl.value = retImeiData[rid].length;
    const btn = document.getElementById('retImeiBtn_' + rid);
    if (btn) {
        btn.classList.add('has-imei');
        btn.innerHTML = `<i class="bi bi-upc-scan"></i> ${retImeiData[rid].length}`;
    }
    calcReturnRow(rid);
}

function fillReturnRowFromScan(targetRid, data) {
    const searchEl = document.getElementById('rSearch_' + targetRid);
    const itemEl   = document.getElementById('rItemId_' + targetRid);
    const priceEl  = document.getElementById('rPrice_' + targetRid);
    const qtyEl    = document.getElementById('rQty_' + targetRid);
    const imeiEl   = document.getElementById('rImei_' + targetRid);
    if (!itemEl || !priceEl || !qtyEl || !imeiEl) {
        throw new Error('Could not update return row.');
    }
    if (searchEl) searchEl.value = data.item_name || '';
    itemEl.value = data.item_id;
    priceEl.value = parseFloat(data.unit_price || data.sale_price || 0).toFixed(3);
    priceEl.readOnly = true;
    priceEl.title = 'Original sold price (locked)';
    priceEl.style.background = '';
    if (!window.retRowItemNameMap) window.retRowItemNameMap = {};
    if (!window.retRowSerialKindMap) window.retRowSerialKindMap = {};
    window.retRowItemNameMap[targetRid] = (data.item_name || '').toLowerCase();
    window.retRowSerialKindMap[targetRid] = data.serial_kind || 'phone';
    retImeiData[targetRid] = [data.imei];
    imeiEl.value = data.imei;
    qtyEl.value = 1;
    const btn = document.getElementById('retImeiBtn_' + targetRid);
    if (btn) {
        btn.classList.add('has-imei');
        btn.innerHTML = `<i class="bi bi-upc-scan"></i> 1`;
    }
    calcReturnRow(targetRid);
}

function highlightReturnRowNeedsItem(rid) {
    const searchInput = document.getElementById('rSearch_' + rid);
    if (!searchInput) return;
    searchInput.style.borderColor = '';
    searchInput.style.outline = '2px solid #f59e0b';
    searchInput.style.outlineOffset = '-2px';
    searchInput.style.background = '#fefce8';
    searchInput.placeholder = '← Select item model for scanned IMEI...';
    const tr = document.getElementById(rid);
    if (tr) {
        tr.style.background = '#fffbeb';
        setTimeout(() => { tr.style.background = ''; }, 4000);
    }
}

function processQuickScan() {
    const imei = normalizeReturnScanImei(quickScanInput.value);
    if (!imei) return;
    if (quickScanBusy) return;

    if (!IqbalImei.isPlausible(imei)) {
        showScanMsg('✗ Need a phone IMEI (13 or 15–18 digits) or tablet serial (11–20 letters/numbers).', 'err');
        quickScanInput.value = '';
        quickScanInput.focus();
        return;
    }

    if (isImeiAlreadyAdded(imei)) {
        showScanMsg('⚠ IMEI already added in this return.', 'err');
        quickScanInput.value = '';
        quickScanInput.focus();
        return;
    }

    quickScanBusy = true;
    quickScanInput.value = '';
    quickScanInput.style.borderColor = '#fbbf24';

    const whId = document.getElementById('retWhSelect')?.value || '';
    fetch(`?page=returns&action=lookupImei&imei=${encodeURIComponent(imei)}&warehouse_id=${encodeURIComponent(whId)}`)
        .then(parseReturnLookupResponse)
        .then(data => {
            try {
                // Rejected entirely (already returned, invalid, etc.) — keep existing rows untouched
                if (!data.accepted) {
                    showScanMsg('✗ ' + (data.message || 'IMEI cannot be returned.'), 'err');
                    return;
                }

                // IMEI found in system — auto-fill item + price
                if (data.found) {
                    applyReturnScanRefData(data);
                    const soldPrice = data.unit_price || data.sale_price;
                    const existingRid = findExistingRowForItem(data.item_id, soldPrice);
                    const selectedPartyId = document.getElementById('retPartyId')?.value || '';
                    const crossParty = data.party_id && selectedPartyId
                        && String(data.party_id) !== String(selectedPartyId);

                    if (existingRid) {
                        attachReturnImeiToRow(existingRid, data.imei);
                    } else {
                        let targetRid = findFirstEmptyReturnRow();
                        if (!targetRid) {
                            addReturnRow();
                            const rows = document.querySelectorAll('#returnItemsBody tr');
                            targetRid = rows[rows.length - 1].dataset.rowId;
                        }
                        fillReturnRowFromScan(targetRid, data);
                    }

                    ensureTrailingEmptyReturnRow();
                    const invoice = data.sold_invoice ? ` (${data.sold_invoice})` : '';
                    const soldTo = crossParty && data.party_name
                        ? ` — originally sold to ${data.party_name}`
                        : '';
                    showScanMsg(`✓ ${data.item_name}${invoice} — sold ${soldPrice} ${currency}${soldTo}`, crossParty ? 'warn' : 'ok');
                    return;
                }

                // IMEI NOT in system — accepted, cashier picks item manually
                let rid = findFirstEmptyReturnRow();
                if (!rid) {
                    addReturnRow();
                    const rows = document.querySelectorAll('#returnItemsBody tr');
                    rid = rows[rows.length - 1].dataset.rowId;
                }

                retImeiData[rid] = [data.imei];
                const imeiEl = document.getElementById('rImei_' + rid);
                const qtyEl  = document.getElementById('rQty_' + rid);
                if (imeiEl) imeiEl.value = data.imei;
                if (qtyEl) qtyEl.value = 1;
                const btn = document.getElementById('retImeiBtn_' + rid);
                if (btn) {
                    btn.classList.add('has-imei');
                    btn.innerHTML = `<i class="bi bi-upc-scan"></i> 1`;
                }

                highlightReturnRowNeedsItem(rid);
                ensureTrailingEmptyReturnRow();
                showScanMsg('⚠ IMEI accepted — select item model in highlighted row ↓', 'warn');
            } catch (err) {
                showScanMsg('✗ ' + (err.message || 'Could not add scan — existing rows kept.'), 'err');
            }
        })
        .catch(err => {
            showScanMsg('✗ ' + (err.message || 'Network error — try again.'), 'err');
        })
        .finally(() => {
            quickScanBusy = false;
            quickScanInput.style.borderColor = '#86efac';
            quickScanInput.focus();
        });
}

const currency = '<?= APP_CURRENCY ?>';
</script>
