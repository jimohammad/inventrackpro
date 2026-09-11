<style>
.sale-wrap{display:flex;flex-direction:column;gap:0;}
.sale-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#1e3a5f;border-radius:0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.sale-topbar .sale-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.warehouse-select{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:default;outline:none;}
.customer-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.customer-search-wrap{position:relative;flex:1;min-width:220px;max-width:340px;}
.customer-search-wrap .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;}
.customer-search-wrap input{width:100%;padding:11px 12px 11px 40px;min-height:44px;border:2px solid #e0e7ff;border-radius:10px;font-size:1.02rem;font-weight:600;color:#1a1a2e;background:#fafbff;transition:all 0.2s;outline:none;}
.customer-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.customer-search-wrap input.selected{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:#065f46;font-weight:600;}
.invoice-search-wrap{max-width:280px;}
.invoice-search-wrap .search-icon{color:#b45309;}
.ret-meta{display:flex;gap:10px;align-items:stretch;margin-left:auto;flex-wrap:wrap;justify-content:flex-end;}
.sale-chip{height:44px;min-height:44px;min-width:148px;display:flex;flex-direction:column;justify-content:center;align-items:flex-end;padding:0 14px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;text-align:right;}
.sale-chip-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;line-height:1.1;}
.sale-chip-value{font-size:0.82rem;font-weight:800;color:#4338ca;letter-spacing:0.03em;line-height:1.25;font-family:ui-monospace,monospace;}
.sale-chip-date{padding:3px 12px 3px 10px;min-width:150px;}
.sale-chip-date input[type="date"]{width:100%;border:none;background:transparent;outline:none;padding:0;height:auto;min-height:0;font-size:0.82rem;font-weight:700;color:#334155;color-scheme:light;text-align:right;}
.items-card{border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:hidden;}
.items-card-header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;}
.items-card-header span{font-size:0.8rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:6px;}
table.items-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:0.94rem;}
table.items-tbl th{padding:10px 12px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.45px;color:#64748b;background:#f1f5f9;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.items-tbl td{border-bottom:1px solid #e2e8f0;padding:10px 8px;vertical-align:middle;}
table.items-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.items-tbl tbody tr:hover{background:#f8faff;}
table.items-tbl tfoot tr{background:#f8f9ff;}
table.items-tbl tfoot td{font-size:0.94rem;}
.col-num{width:44px;text-align:center;color:#94a3b8;font-size:0.88rem;font-weight:600;}
.col-item{min-width:220px;}
.col-imei{width:56px;text-align:center;}
.col-qty{width:88px;text-align:center;}
.col-price{width:132px;text-align:right;}
.col-amt{width:140px;text-align:right;font-weight:700;}
.col-act{width:44px;text-align:center;}
.sale-cell-input{display:block;width:100%;box-sizing:border-box;border:1.5px solid #dbe2f0;border-radius:8px;background:#fafbff;outline:none;font-size:0.92rem;font-weight:600;color:#1e293b;padding:8px 10px;line-height:1.3;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;}
.sale-cell-input::placeholder{color:#94a3b8;font-weight:500;font-size:0.85rem;}
.sale-cell-input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.12);}
.sale-cell-input.item-search{font-weight:500;padding-left:12px;}
.sale-cell-input.qty{text-align:center;font-variant-numeric:tabular-nums;}
.sale-cell-input.unit{text-align:right;color:#b45309;background:#fffbeb;border-color:#fde68a;font-variant-numeric:tabular-nums;}
.sale-cell-input.unit:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.15);background:#fff;}
.sale-cell-input.total{text-align:right;color:#4338ca;background:#eef2ff;border-color:#c7d2fe;font-weight:700;font-variant-numeric:tabular-nums;}
.sale-row-remove{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1.2rem;line-height:1;padding:0;transition:color 0.15s,background 0.15s;}
.sale-row-remove:hover{color:#dc2626;background:#fef2f2;}
.imei-btn{background:linear-gradient(135deg,#eff6ff,#e0e7ff);border:1.5px solid #c7d2fe;color:#6366f1;border-radius:8px;min-width:38px;height:38px;padding:0 8px;font-size:0.85rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-weight:700;transition:all 0.15s;white-space:nowrap;box-sizing:border-box;vertical-align:middle;}
.imei-btn i{font-size:1.15rem;line-height:1;}
.imei-btn:hover{background:linear-gradient(135deg,#e0e7ff,#c7d2fe);transform:translateY(-1px);box-shadow:0 2px 6px rgba(99,102,241,0.2);}
.imei-btn.has-imei{background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-color:#6ee7b7;color:#059669;}

/* Return IMEI scan field (below customer bar) */
.ret-scan-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 20px;border:1px solid #e5e7eb;border-top:none;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);}
.ret-scan-wrap{position:relative;flex:1;min-width:260px;max-width:520px;}
.ret-scan-wrap .ret-scan-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#059669;font-size:1rem;z-index:2;pointer-events:none;}
.ret-scan-input{width:100%;padding:11px 16px 11px 40px;border:2px solid #86efac;border-radius:10px;min-height:44px;font-size:1rem;font-family:monospace;letter-spacing:0.5px;background:#fafbff;color:#1e293b;outline:none;transition:all 0.2s;}
.ret-scan-input:focus{border-color:#059669;background:#fff;box-shadow:0 0 0 3px rgba(5,150,105,0.12);}
.ret-scan-input::placeholder{font-family:inherit;font-size:0.82rem;letter-spacing:normal;color:#94a3b8;}
@media (max-width: 640px){.ret-scan-wrap{min-width:0;max-width:100%;flex:1 1 100%;}}
.sale-bottom{display:flex;justify-content:flex-end;border:1px solid #e5e7eb;border-top:none;background:linear-gradient(180deg,#f0fdf4 0%,#fff 100%);padding:12px 20px 14px;}
.sale-totals{width:min(100%,340px);margin-left:auto;}
.gt-card{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:12px 18px 12px 16px;background:linear-gradient(135deg,#064e3b 0%,#047857 55%,#059669 100%);border-radius:12px;box-shadow:0 6px 18px rgba(5,150,105,0.22),inset 0 1px 0 rgba(255,255,255,0.14);position:relative;overflow:hidden;transition:box-shadow 0.2s ease;}
.gt-card::before{content:'';position:absolute;left:0;top:8px;bottom:8px;width:3px;border-radius:0 3px 3px 0;background:#6ee7b7;}
.gt-card.has-amount{box-shadow:0 8px 22px rgba(5,150,105,0.32),inset 0 1px 0 rgba(255,255,255,0.16);}
.gt-meta{display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding-left:8px;min-width:0;text-align:left;}
.gt-label{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.7);line-height:1.2;}
.gt-qty{font-size:0.78rem;font-weight:600;color:#a7f3d0;letter-spacing:0.01em;}
.gt-amount{display:flex;align-items:baseline;justify-content:flex-end;gap:8px;font-variant-numeric:tabular-nums;white-space:nowrap;}
.gt-currency{font-size:0.78rem;font-weight:700;color:#6ee7b7;letter-spacing:0.04em;}
#returnTotal{font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-0.03em;line-height:1;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);margin-top:-1px;}
.save-actions{display:flex;align-items:center;gap:10px;width:min(100%,340px);}
@media(max-width:700px){.sale-bottom{padding:10px 12px 12px;}.sale-totals,.save-actions{width:100%;}.gt-card{width:100%;}#returnTotal{font-size:1.28rem;}.ret-meta{margin-left:0;width:100%;}.sale-chip{flex:1;}.invoice-search-wrap{max-width:100%;flex:1 1 100%;}}
.btn-cancel-sale{padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;}
.btn-save-sale{flex:1;padding:8px 12px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;justify-content:center;gap:6px;}
.btn-save-sale:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,0.5);}
.btn-print-sale{flex:1;padding:8px 12px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(5,150,105,0.35);transition:all 0.15s;display:flex;align-items:center;justify-content:center;gap:6px;}
.btn-print-sale:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,0.45);}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:300px;overflow-y:auto;margin-top:4px;}
.autocomplete-box.item-dropdown{position:fixed;margin-top:0;min-width:380px;width:auto;right:auto;max-width:90vw;z-index:10050;}
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

</style>

<form method="POST" action="?page=returns&action=storePurchase" id="retForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="return_form_nonce" value="<?= htmlspecialchars($returnFormNonce ?? '') ?>">
    <input type="hidden" name="print_mode" id="retPrintMode" value="0">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-arrow-return-left"></i> New Purchase Return
        </div>
        <?php
        $sessionWhId = (int) (Auth::warehouseId() ?: 0);
        $sessionWhLabel = htmlspecialchars((string) (Auth::warehouseName() ?: 'Branch'));
        foreach ($warehouses as $w) {
            if ((int) ($w['id'] ?? 0) === $sessionWhId) {
                $sessionWhLabel = htmlspecialchars((string) ($w['name'] ?? $sessionWhLabel));
                break;
            }
        }
        ?>
        <?php if ($sessionWhId > 0): ?>
        <input type="hidden" name="warehouse_id" id="retWhSelect" value="<?= $sessionWhId ?>">
        <span class="warehouse-select" style="cursor:default;pointer-events:none;"><?= $sessionWhLabel ?></span>
        <?php else: ?>
        <span class="warehouse-select" style="cursor:default;pointer-events:none;color:#fecaca;">Select a branch in the header first</span>
        <?php endif; ?>
    </div>

    <!-- ② SUPPLIER + INVOICE + META -->
    <div class="customer-bar">
        <div class="customer-search-wrap" id="retPartySearchWrap">
            <i class="bi bi-person-circle search-icon"></i>
            <input type="text" id="retPartySearch" placeholder="Search supplier or customer..." autocomplete="off">
            <div class="autocomplete-box" id="retPartyDrop" style="display:none;"></div>
            <input type="hidden" name="party_id" id="retPartyId" required>
            <input type="hidden" name="ref_id" id="refIdInput">
        </div>
        <div id="retPartyBalBadge" style="display:none;padding:6px 14px;border-radius:8px;font-size:0.82rem;font-weight:700;white-space:nowrap;"></div>

        <div class="customer-search-wrap invoice-search-wrap" id="retInvoiceSearchWrap">
            <i class="bi bi-receipt search-icon"></i>
            <input type="text" id="invoiceSearch" placeholder="Link purchase invoice (required)..." autocomplete="off" required>
            <div class="autocomplete-box" id="invoiceDrop" style="display:none;"></div>
        </div>

        <div class="ret-meta">
            <div class="sale-chip" title="Next return number — assigned on save">
                <span class="sale-chip-label">Return No</span>
                <span class="sale-chip-value"><?= htmlspecialchars($nextReturnNo ?? '') ?></span>
            </div>
            <div class="sale-chip sale-chip-date">
                <label class="sale-chip-label" for="retDateInput">Date</label>
                <input type="date" name="date" id="retDateInput" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <!-- ③ QUICK SCAN (original position, no label) -->
    <div class="ret-scan-bar">
        <div class="ret-scan-wrap">
            <i class="bi bi-upc-scan ret-scan-icon" aria-hidden="true"></i>
            <input type="text" id="quickScanInput" class="ret-scan-input"
                   placeholder="Scan IMEI barcode — auto-detects model & price..." autocomplete="off">
        </div>
        <div id="quickScanMsg" style="display:none;"></div>
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
    </div>

    <!-- ④ BOTTOM -->
    <div class="sale-bottom">
        <div class="sale-totals">
            <div class="gt-card" id="refundCard">
                <div class="gt-meta">
                    <span class="gt-label">Return Total</span>
                    <span class="gt-qty" id="gtQtyHint">0 pcs</span>
                </div>
                <div class="gt-amount">
                    <span class="gt-currency"><?= defined('APP_CURRENCY') ? APP_CURRENCY : 'KWD' ?></span>
                    <span id="returnTotal">0.000</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ⑤ SAVE BAR -->
    <div class="save-bar">
        <a href="?page=returns" class="btn-cancel-sale">Cancel</a>
        <div class="save-actions">
            <button type="submit" class="btn-save-sale" onclick="document.getElementById('retPrintMode').value='0'">
                <i class="bi bi-check-lg"></i> Save Return
            </button>
            <button type="submit" class="btn-print-sale" onclick="document.getElementById('retPrintMode').value='1'">
                <i class="bi bi-printer"></i> Save &amp; Print
            </button>
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
const purchaseReturnDraft = <?= json_encode($purchaseReturnDraft ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

document.addEventListener('DOMContentLoaded', () => {
    addReturnRow(); addReturnRow();
    if (!restorePurchaseReturnDraft()) {
        applyPurchaseReturnPrefill();
    }
    document.getElementById('retPartySearch').focus();
});

function restorePurchaseReturnDraft() {
    if (!purchaseReturnDraft || !Array.isArray(purchaseReturnDraft.items) || !purchaseReturnDraft.items.length) {
        return false;
    }

    document.getElementById('returnItemsBody').innerHTML = '';
    retImeiData = {};
    returnRowCount = 0;

    const dateEl = document.getElementById('retDateInput') || document.querySelector('input[name="date"]');
    if (dateEl && purchaseReturnDraft.date) {
        dateEl.value = purchaseReturnDraft.date;
    }

    if (purchaseReturnDraft.ref_id) {
        document.getElementById('refIdInput').value = String(purchaseReturnDraft.ref_id);
        const inv = document.getElementById('invoiceSearch');
        if (inv) {
            inv.value = purchaseReturnDraft.ref_invoice || '';
            inv.classList.add('selected');
            inv.style.borderColor = '#10b981';
            inv.style.background = '#f0fdf4';
        }
    }

    if (purchaseReturnDraft.party && purchaseReturnDraft.party.id) {
        document.getElementById('retPartyId').value = String(purchaseReturnDraft.party.id);
        const ps = document.getElementById('retPartySearch');
        if (ps) {
            ps.value = purchaseReturnDraft.party.name || '';
            ps.classList.add('selected');
        }
    } else if (purchaseReturnDraft.party_id) {
        document.getElementById('retPartyId').value = String(purchaseReturnDraft.party_id);
    }

    purchaseReturnDraft.items.forEach(item => {
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
    return true;
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
                   value="${item ? item.unit_price : ''}" oninput="calcReturnRow('${rid}')">
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
    const gtQty = document.getElementById('gtQtyHint');
    if (gtQty) gtQty.textContent = totalQty + ' pc' + (totalQty !== 1 ? 's' : '');
    const gtCard = document.getElementById('refundCard');
    if (gtCard) gtCard.classList.toggle('has-amount', total > 0.0005);
}

// ── ITEM SEARCH ──
const returnItemStore = {};
let retSearchTimers = {};

function positionDropdown(input, drop) {
    if (!input || !drop) return;
    if (drop.parentNode !== document.body) {
        document.body.appendChild(drop);
    }
    const rect = input.getBoundingClientRect();
    const minW = Math.max(380, rect.width);
    const maxH = 300;
    const spaceBelow = window.innerHeight - rect.bottom - 8;
    const spaceAbove = rect.top - 8;
    const openUp = spaceBelow < Math.min(maxH, 160) && spaceAbove > spaceBelow;
    drop.style.minWidth = minW + 'px';
    drop.style.maxHeight = Math.min(maxH, openUp ? spaceAbove : Math.max(spaceBelow, 120)) + 'px';
    drop.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - minW - 8)) + 'px';
    drop.style.top = openUp
        ? Math.max(8, rect.top - Math.min(maxH, spaceAbove) - 4) + 'px'
        : (rect.bottom + 4) + 'px';
}

function searchReturnItem(input, rid) {
    clearTimeout(retSearchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('rDrop_' + rid);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    retSearchTimers[rid] = setTimeout(() => {
        const whId = document.getElementById('retWhSelect')?.value || '';
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${encodeURIComponent(whId)}`)
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
    document.getElementById('rPrice_'  + rid).value = parseFloat(item.sale_price).toFixed(3);
    document.getElementById('rDrop_'   + rid).style.display = 'none';
    if (!window.retRowItemNameMap) window.retRowItemNameMap = {};
    if (!window.retRowSerialKindMap) window.retRowSerialKindMap = {};
    window.retRowItemNameMap[rid] = (item.name || '').toLowerCase();
    window.retRowSerialKindMap[rid] = item.serial_kind || 'phone';
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
    if (!e.target.closest('.col-item') && !e.target.closest('.autocomplete-box.item-dropdown')) {
        document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    }
    if (!e.target.closest('#retPartySearchWrap')) {
        const partyDrop = document.getElementById('retPartyDrop');
        if (partyDrop) partyDrop.style.display = 'none';
    }
});
window.addEventListener('scroll', () => {
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => {
        if (d.style.display === 'none') return;
        const rid = (d.id || '').replace('rDrop_', '');
        const searchEl = document.getElementById('rSearch_' + rid);
        if (searchEl) positionDropdown(searchEl, d);
    });
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
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(q)}&type=purchase`)
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
        fetch(`?page=returns&action=searchPurchases&q=${encodeURIComponent(q)}&warehouse_id=${encodeURIComponent(whId)}`)
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
                            invEl.classList.add('selected');
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
    document.getElementById('invoiceSearch').addEventListener('input', function() {
        searchInvoice(this.value.trim());
    });
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
    closeRetImeiModal();
}

document.querySelectorAll('#retForm button[type="submit"]').forEach(function(btn) {
    btn.addEventListener('click', function() { retAllowFormSubmit = true; });
});

// Prevent accidental form submit on Enter (barcode scanners send Enter after each scan).
document.getElementById('retForm').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' && e.keyCode !== 13) return;
    if (e.target && e.target.id === 'quickScanInput') return;
    if (e.target && e.target.tagName === 'INPUT' &&
        !['submit', 'hidden', 'button'].includes((e.target.type || 'text').toLowerCase())) {
        e.preventDefault();
    }
}, true);

document.getElementById('retForm').addEventListener('submit', function(e) {
    if (!retAllowFormSubmit) { e.preventDefault(); return; }
    retAllowFormSubmit = false;

    // Client-side submit lock (server also validates one-time nonce)
    if (this.dataset.submitting === '1') { e.preventDefault(); return; }
    if (quickScanBusy) { e.preventDefault(); return; }

    if (!document.getElementById('retPartyId').value) { e.preventDefault(); alert('Please select a supplier.'); return; }
    if (!document.getElementById('refIdInput').value) { e.preventDefault(); alert('Please link the original purchase invoice.'); return; }
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

function applyPurchaseReturnScanRefData(data) {
    if (data.purchase_id) {
        const ref = document.getElementById('refIdInput');
        const purchaseId = String(data.purchase_id);
        if (ref && ref.value && ref.value !== purchaseId) {
            return false;
        }
        if (ref && !ref.value) {
            ref.value = purchaseId;
        }
    }
    if (data.party_id) {
        const partyEl = document.getElementById('retPartyId');
        if (partyEl && (!partyEl.value || partyEl.value === String(data.party_id))) {
            partyEl.value = String(data.party_id);
            const ps = document.getElementById('retPartySearch');
            if (ps && data.party_name) {
                ps.value = data.party_name;
                ps.classList.add('selected');
            }
        }
    }
}

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
    if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
        e.stopPropagation();
        processQuickScan();
    }
});

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
    priceEl.value = parseFloat(data.unit_price || 0).toFixed(3);
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
    searchInput.style.borderColor = '#f59e0b';
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
    fetch(`?page=returns&action=lookupImeiPurchase&imei=${encodeURIComponent(imei)}&warehouse_id=${encodeURIComponent(whId)}&purchase_id=${encodeURIComponent(document.getElementById('refIdInput').value || '')}`)
        .then(parseReturnLookupResponse)
        .then(data => {
            try {
                if (!data.accepted) {
                    showScanMsg('✗ ' + (data.message || 'IMEI cannot be returned.'), 'err');
                    return;
                }

                if (data.found) {
                    if (applyPurchaseReturnScanRefData(data) === false) {
                        showScanMsg('✗ This IMEI belongs to a different purchase than the current return.', 'err');
                        return;
                    }
                    const existingRid = findExistingRowForItem(data.item_id);

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
                    const invoice = data.purchase_invoice ? ` (${data.purchase_invoice})` : '';
                    showScanMsg(`✓ ${data.item_name}${invoice} — ${data.unit_price} ${currency}`, 'ok');
                    return;
                }

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

<?php if (empty($purchaseReturnDraft) && !empty($prefillPurchase)): ?>
function applyPurchaseReturnPrefill() {
    const p = <?= json_encode([
        'id' => (int) $prefillPurchase['id'],
        'invoice_no' => (string) ($prefillPurchase['invoice_no'] ?? ''),
        'party_id' => (int) ($prefillPurchase['party_id'] ?? 0),
        'party_name' => (string) ($prefillPurchase['party_name'] ?? ''),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    document.getElementById('refIdInput').value = String(p.id);
    document.getElementById('retPartyId').value = String(p.party_id);
    const ps = document.getElementById('retPartySearch');
    if (ps) { ps.value = p.party_name; ps.classList.add('selected'); }
    const inv = document.getElementById('invoiceSearch');
    if (inv) {
        inv.value = p.invoice_no;
        inv.classList.add('selected');
        inv.style.borderColor = '#10b981';
        inv.style.background = '#f0fdf4';
    }
}
<?php else: ?>
function applyPurchaseReturnPrefill() {}
<?php endif; ?>
</script>
