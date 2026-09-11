<style>
/* ═══ SALE PAGE ═══ */
.sale-wrap { display:flex; flex-direction:column; gap:0; }

/* TOP BAR */
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
    border-top:none;
    border-bottom:none;
}
.sale-composer-row {
    display:grid;
    grid-template-columns: minmax(0,1fr) 158px 150px;
    gap:10px;
    align-items:stretch;
    padding:12px 20px;
    position:relative;
    z-index:3;
}
.sale-composer-scan {
    z-index:1;
    padding-top:10px; padding-bottom:12px;
    background:#fff;
    border-top:none;
}
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
.sale-field input.selected {
    border-color:#10b981;
    background:#f0fdf4;
    color:#065f46;
}
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

/* ITEMS TABLE CARD */
.items-card {
    border-left:1px solid #e5e7eb;
    border-right:1px solid #e5e7eb;
    border-top:none;
    border-bottom:1px solid #e5e7eb;
    border-radius:0;
    background:#fff; overflow:visible;
}
.items-card-notice {
    display:flex; justify-content:flex-end; align-items:center;
    padding:6px 20px 0; background:#fff;
}
table.items-tbl {
    width:100%; table-layout:fixed; border-collapse:collapse; border-spacing:0; font-size:0.92rem;
    border:1px solid #e2e8f0;
}
table.items-tbl th,
table.items-tbl td { box-shadow:none !important; }
table.items-tbl th {
    padding:8px 8px; font-size:0.68rem; font-weight:800;
    text-transform:uppercase; letter-spacing:0.08em;
    color:#4338ca;
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

.scan-bar-wrap { position:relative; min-width:0; }
.scan-bar-input { letter-spacing:0.3px; font-weight:500; }
.scan-bar-input::placeholder,
.sale-field input::placeholder { font-family:inherit; font-size:0.82rem; font-weight:500; letter-spacing:normal; color:#94a3b8; }
.scan-bar-msg {
    font-size:0.78rem; font-weight:600; text-align:center;
    display:none; align-items:center; justify-content:center; gap:6px;
    padding:0 20px 8px; background:#fff;
}
.scan-bar-msg:not(:empty) { display:flex; }
.scan-bar-msg.ok { color:#065f46; }
.scan-bar-msg.err { color:#991b1b; }
.scan-bar-msg i { font-size:1rem; flex-shrink:0; line-height:1; }

@keyframes scanBarShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-6px); }
    40% { transform: translateX(6px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
}
.scan-bar-wrap.scan-bar-shake {
    animation: scanBarShake 0.42s ease-out;
}

@keyframes scanRowFlash {
    0% { background-color: #d1fae5; }
    100% { background-color: #fff; }
}
table.items-tbl tbody tr.scan-row-flash td.col-num,
table.items-tbl tbody tr.scan-row-flash td.col-item,
table.items-tbl tbody tr.scan-row-flash td.col-imei,
table.items-tbl tbody tr.scan-row-flash td.col-qty,
table.items-tbl tbody tr.scan-row-flash td.col-act {
    animation: scanRowFlash 1s ease-out;
}
.scan-bar-count {
    display:inline-flex; align-items:center; justify-content:center;
    height:44px; padding:0 12px; border-radius:0;
    font-size:0.82rem; color:#4338ca; font-weight:800; white-space:nowrap;
    background:#e0e7ff; border:1.5px solid #c7d2fe; letter-spacing:0.02em;
}
.scan-btn-paste {
    height:44px; padding:0 12px; border-radius:0; border:1.5px solid #6366f1;
    background:#fff; color:#4f46e5; font-size:0.82rem; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; white-space:nowrap;
    transition:background .15s, color .15s;
}
.scan-btn-paste:hover { background:#6366f1; color:#fff; }
.create-paste-textarea {
    width:100%; padding:12px; border:1.5px solid #c7d2fe; border-radius:8px;
 font-size:0.85rem; resize:vertical;
    background:#fafbff; color:#1e293b; outline:none; min-height:200px; letter-spacing:0.5px;
}
.create-paste-textarea:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.1); }
.create-paste-summary { display:flex; gap:14px; margin-bottom:10px; font-size:0.85rem; font-weight:700; flex-wrap:wrap; }
.create-paste-summary span { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:8px; }
.create-paste-ok { color:#16a34a; background:#f0fdf4; border:1px solid #bbf7d0; }
.create-paste-err { color:#dc2626; background:#fef2f2; border:1px solid #fecaca; }
.create-paste-err-list { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; max-height:160px; overflow-y:auto; }
.create-paste-err-row { display:flex; gap:12px; font-size:0.78rem; padding:3px 0; border-bottom:1px solid #fecaca; }
.create-paste-err-row:last-child { border-bottom:none; }
.create-paste-err-imei {  font-weight:600; color:#7f1d1d; width:160px; flex-shrink:0; }
.create-paste-err-reason { color:#991b1b; }
@media (max-width: 700px) {
    .sale-composer-row {
        grid-template-columns: 1fr 1fr;
    }
    .sale-field { grid-column: 1 / -1; }
}

/* Customer credit: Unpaid / Remaining / Total Limit */
.cb-strip {
    display:flex; flex-direction:column; gap:8px;
    margin:0 20px 12px; padding:10px 12px 12px;
    border-radius:12px;
    background:linear-gradient(180deg,#f8f7ff 0%,#f3f0ff 100%);
    border:1px solid #ddd6fe;
}
.cb-metrics {
    display:grid;
    grid-template-columns:repeat(3, minmax(0,1fr));
    gap:8px;
}
.cb-metric {
    display:flex; flex-direction:column; gap:3px;
    min-width:0;
    padding:9px 12px 10px;
    border-radius:10px;
    background:#fff;
    border:1px solid #ede9fe;
}
.cb-metric-label {
    display:flex; align-items:center; gap:5px;
    font-size:0.62rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.07em; color:#94a3b8; line-height:1.2;
}
.cb-metric-label i { font-size:0.78rem; }
.cb-metric strong {
    font-size:0.92rem; font-weight:800; letter-spacing:-0.02em;
    font-variant-numeric:tabular-nums; line-height:1.25;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.cb-metric-unpaid .cb-metric-label { color:#e11d48; }
.cb-metric-unpaid .cb-metric-label i { color:#fb7185; }
.cb-unpaid { color:#dc2626; }
.cb-metric-remain .cb-metric-label { color:#7c3aed; }
.cb-metric-remain .cb-metric-label i { color:#a78bfa; }
.cb-remain { color:#6d28d9; }
.cb-remain.is-none, .cb-total.is-none { color:#94a3b8; font-weight:700; }
.cb-metric-total .cb-metric-label { color:#4338ca; }
.cb-metric-total .cb-metric-label i { color:#818cf8; }
.cb-total { color:#1e3a5f; }
.cb-usage {
    height:5px; border-radius:99px; background:#ede9fe; overflow:hidden;
}
.cb-usage-bar {
    height:100%; width:0; border-radius:99px;
    background:linear-gradient(90deg,#818cf8,#6d28d9);
    transition:width 0.2s ease;
}
.cb-usage-bar.is-warn { background:linear-gradient(90deg,#fbbf24,#f59e0b); }
.cb-usage-bar.is-over { background:linear-gradient(90deg,#fb7185,#dc2626); }
.cb-inline-hint {
    display:none; font-size:0.78rem; font-weight:700; color:#991b1b;
}
.cb-strip.is-exceeded {
    background:linear-gradient(180deg,#fff7f7 0%,#fef2f2 100%);
    border-color:#fecaca;
}
.cb-strip.is-exceeded .cb-metric-remain {
    background:#fff1f2; border-color:#fecaca;
}
.cb-strip.is-exceeded .cb-remain { color:#dc2626; }
.cb-strip.is-exceeded .cb-inline-hint { display:block; }
.cb-strip.is-nolimit .cb-usage { display:none; }
@media (max-width: 700px) {
    .cb-metrics { grid-template-columns:1fr; }
    .cb-metric {
        flex-direction:row; align-items:center; justify-content:space-between; gap:10px;
        padding:8px 12px;
    }
}

/* TOTALS + ACTIONS sit in the same table columns as qty / price / amount */
.sale-gt-cell {
    padding:10px 0 0 !important;
    vertical-align:top; background:#fff !important;
}
.sale-btns-cell {
    padding:8px 0 12px !important;
    vertical-align:middle; background:#fff !important;
}
.sale-cancel-cell {
    padding:8px 8px 12px 0 !important;
    text-align:right; vertical-align:middle; background:#fff !important;
}
table.items-tbl tfoot td { background:#fff !important; border:none !important; }
.gt-card {
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; padding:0 14px;
    width:100%; height:64px; min-height:64px; box-sizing:border-box;
    background:#1e3a5f;
    border-radius:0;
    box-shadow:none;
    position:relative; overflow:hidden;
}
.gt-card::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:3px;
    border-radius:0; background:#818cf8;
}
.gt-card.has-amount {
    box-shadow:none;
}
.gt-meta { display:flex; flex-direction:column; gap:2px; padding-left:8px; min-width:0; }
.gt-label {
    font-size:0.62rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.12em; color:rgba(255,255,255,0.62); line-height:1.2;
}
.gt-qty {
    font-size:0.92rem; font-weight:800; color:#e0e7ff; letter-spacing:0.01em;
    line-height:1.2;
}
.gt-amount {
    display:flex; align-items:baseline; gap:8px;
    font-variant-numeric:tabular-nums; white-space:nowrap;
}
.gt-currency {
    font-size:0.82rem; font-weight:700; color:#a5b4fc; letter-spacing:0.04em;
}
#grandTotalDisplay {
    font-size:1.45rem; font-weight:800; color:#fff;
    letter-spacing:-0.03em; line-height:1;
}
@media (max-width: 700px) {
    .gt-card { width: 100%; height:64px; min-height: 64px; }
    #grandTotalDisplay { font-size: 1.25rem; }
    .gt-qty { font-size: 0.85rem; }
    .cb-strip { margin-left:12px; margin-right:12px; }
}

.save-actions {
    display:flex; align-items:stretch; gap:8px;
    width:100%;
}
.btn-cancel-sale {
    padding:0 18px; border-radius:0; font-size:0.88rem;
    height:48px; min-height:48px;
    border:1px solid #e2e8f0; color:#64748b; background:#fff;
    cursor:pointer; font-weight:500;
    text-decoration:none; display:inline-flex; align-items:center; justify-content:center;
    white-space:nowrap; box-sizing:border-box;
}
.btn-cancel-sale:hover { border-color:#94a3b8; background:#f8fafc; }
.btn-save-sale,
.btn-print-sale {
    flex:1 1 0; min-width:0; height:48px; min-height:48px; padding:0 14px; border-radius:0;
    font-size:0.9rem; font-weight:700; white-space:nowrap;
    border:none; color:#fff; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:6px;
    box-shadow:none;
}
.btn-save-sale {
    background:#2563eb;
}
.btn-save-sale:hover { background:#1d4ed8; }
.btn-save-sale:disabled, .btn-print-sale:disabled {
    opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none;
}
.btn-print-sale {
    background:#047857;
}
.btn-print-sale:hover { background:#065f46; }

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
.autocomplete-item:hover,
.autocomplete-item.active { background:#f0f4ff; }
.item-drop-meta {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
}

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
 letter-spacing:1px; outline:none;
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
    font-size:0.78rem; color:#1d4ed8; }
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
    <input type="hidden" name="print_mode" id="salePrintMode" value="0">
    <input type="hidden" name="warehouse_id" value="<?= (int) Auth::warehouseId() ?>">

<div class="sale-wrap">

    <!-- ① TOP BAR -->
    <div class="sale-topbar">
        <div class="sale-title">
            <i class="bi bi-receipt"></i> New Sale Invoice
        </div>
    </div>

    <!-- ② CUSTOMER + IMEI COMPOSER -->
    <div class="sale-composer" id="saleComposer">
        <div class="sale-composer-row">
            <div class="sale-field customer-search-wrap" id="partySearchWrap">
                <i class="bi bi-person-circle search-icon"></i>
                <input type="text" id="partySearch" placeholder="Search agent / customer..." autocomplete="off" autofocus>
                <div class="autocomplete-box" id="partyDropdown" style="display:none;"></div>
                <input type="hidden" name="party_id" id="partyIdInput" required>
            </div>
            <div class="sale-chip" title="Next invoice number">
                <span class="sale-chip-label">Invoice</span>
                <span class="sale-chip-value"><?= htmlspecialchars($nextInv) ?></span>
            </div>
            <div class="sale-chip sale-chip-date">
                <label class="sale-chip-label" for="saleDateInput">Date</label>
                <input type="date" name="date" id="saleDateInput" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="sale-composer-row sale-composer-scan">
            <div class="sale-field scan-bar-wrap">
                <i class="bi bi-upc-scan scan-bar-inner-icon" aria-hidden="true"></i>
                <input type="text" class="scan-bar-input" id="imeiScanBar" placeholder="Scan IMEI — auto-adds item"
                       autocomplete="off" aria-describedby="scanBarMsg">
            </div>
            <button type="button" class="scan-btn-paste" id="btnCreateScanPaste">
                <i class="bi bi-clipboard-plus"></i> Paste IMEIs
            </button>
            <span class="scan-bar-count" id="scanBarCount">0 scanned</span>
        </div>
        <span class="scan-bar-msg" id="scanBarMsg" role="status" aria-live="polite"></span>
        <div id="customerBalanceBox" class="cb-strip" style="display:none;">
            <div class="cb-metrics">
                <div class="cb-metric cb-metric-unpaid">
                    <span class="cb-metric-label"><i class="bi bi-exclamation-circle"></i> Unpaid</span>
                    <strong id="customerBalanceAmt" class="cb-unpaid"></strong>
                </div>
                <div class="cb-metric cb-metric-remain">
                    <span class="cb-metric-label"><i class="bi bi-wallet2"></i> Remaining Limit</span>
                    <strong id="creditRemainAmt" class="cb-remain"></strong>
                </div>
                <div class="cb-metric cb-metric-total">
                    <span class="cb-metric-label"><i class="bi bi-shield-check"></i> Total Limit</span>
                    <strong id="creditTotalAmt" class="cb-total"></strong>
                </div>
            </div>
            <div class="cb-usage" aria-hidden="true">
                <div class="cb-usage-bar" id="creditUsageBar"></div>
            </div>
            <span id="creditHint" class="cb-inline-hint"></span>
        </div>
    </div>

    <!-- ③ ITEMS TABLE -->
    <div class="items-card">
        <?php if (in_array(Auth::role(), ['cashier','viewer']) && empty($allowSalesmanFreePrice)): ?>
        <div class="items-card-notice">
            <span style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:2px 10px;border-radius:20px;font-size:0.76rem;font-weight:600;">
                <i class="bi bi-shield-check me-1"></i>Min price protected
            </span>
        </div>
        <?php elseif (in_array(Auth::role(), ['cashier','viewer']) && !empty($allowSalesmanFreePrice)): ?>
        <div class="items-card-notice">
            <span style="background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.3);padding:2px 10px;border-radius:20px;font-size:0.76rem;font-weight:600;">
                <i class="bi bi-pencil-square me-1"></i>Price edit allowed (temp)
            </span>
        </div>
        <?php endif; ?>
        <div style="overflow-x:auto;">
            <table class="items-tbl" id="itemsTable">
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
                        <th class="col-amt">AMOUNT</th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td colspan="3" class="sale-gt-cell">
                            <input type="hidden" name="discount" id="discountInput" value="0">
                            <div class="gt-card" id="grandTotalCard">
                                <div class="gt-meta">
                                    <span class="gt-label">Grand Total</span>
                                    <span class="gt-qty" id="gtQtyHint">0 pcs</span>
                                </div>
                                <div class="gt-amount">
                                    <span class="gt-currency"><?= defined('APP_CURRENCY') ? APP_CURRENCY : 'KWD' ?></span>
                                    <span id="grandTotalDisplay">0.000</span>
                                </div>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="sale-cancel-cell">
                            <a href="?page=sales" class="btn-cancel-sale">Cancel</a>
                        </td>
                        <td colspan="3" class="sale-btns-cell">
                            <div class="save-actions">
                                <button type="submit" class="btn-save-sale"
                                    onclick="document.getElementById('salePrintMode').value='0'">
                                    <i class="bi bi-check-lg"></i> Save
                                </button>
                                <button type="submit" class="btn-print-sale" id="btnSavePrint"
                                    title="Prints with your Default Print (A5 or Thermal) from the profile menu. Shortcut: Ctrl+S / F12"
                                    onclick="document.getElementById('salePrintMode').value='1'">
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

</div><!-- end sale-wrap -->
</form>

<!-- Bulk paste IMEIs modal (scan-bar → auto-add line items) -->
<div class="modal fade" id="createSalePasteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:1px solid #bae6fd;">
                <h5 class="modal-title" style="font-size:0.95rem;font-weight:700;display:flex;align-items:center;gap:8px;color:#0c4a6e;">
                    <i class="bi bi-clipboard-plus" style="color:#0ea5e9;"></i>
                    Paste IMEIs — Bulk Add to Invoice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.82rem;color:#64748b;margin-bottom:10px;">
                    <i class="bi bi-info-circle me-1" style="color:#0ea5e9;"></i>
                    Paste IMEIs below — one per line, or separated by commas/semicolons. Press <strong>Enter</strong> to validate, then <strong>Enter</strong> again to import. <span style="color:#94a3b8;">Shift+Enter for a new line.</span>
                </p>
                <div style="font-size:0.78rem;color:#475569;margin-bottom:8px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span><i class="bi bi-stickies me-1"></i> Lines pasted: <strong id="createSpLineCount">0</strong></span>
                    <span><i class="bi bi-upc-scan me-1"></i> Already on invoice: <strong id="createSpOnInvoiceCount">0</strong></span>
                </div>
                <textarea id="createSpTextarea" class="create-paste-textarea"
                          placeholder="Paste IMEIs here (one per line)...&#10;&#10;354720736995668&#10;354720736995675"></textarea>
                <div id="createSpPreview" style="margin-top:12px;display:none;"></div>
                <div id="createSpProgress" style="margin-top:10px;display:none;font-size:0.82rem;font-weight:600;color:#6366f1;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="createSpValidateBtn"
                        style="background:#0ea5e9;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="createSpConfirmBtn" disabled
                        style="background:#6366f1;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-cloud-upload me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

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
            <input type="text" id="imeiScanInput" placeholder="Scan or type IMEI / tablet serial..." maxlength="20"
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
                      style="width:100%;border:2px solid #c7d2fe;border-radius:8px;padding:10px;font-size:0.82rem;resize:vertical;"></textarea>
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
let activeImeis    = [];
let currentItemName = '';
const saleCurrency = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
const saleDraft = <?= json_encode($saleDraft ?? null, JSON_UNESCAPED_UNICODE) ?>;
let selectedPartyCredit = { id: 0, balance: 0, credit_limit: 0, customer_kind: 'wholesale' };
const RETAIL_TIER_KWD = 40;
const RETAIL_MARKUP_LOW = 0.5;
const RETAIL_MARKUP_HIGH = 1;
function isRetailCustomer(kind) {
    return String(kind || selectedPartyCredit.customer_kind || '') === 'retail';
}
function retailMarkupFromCatalog(catalog) {
    return (parseFloat(catalog) || 0) >= RETAIL_TIER_KWD ? RETAIL_MARKUP_HIGH : RETAIL_MARKUP_LOW;
}
function retailMinFromCatalog(catalog) {
    const c = parseFloat(catalog) || 0;
    return c + retailMarkupFromCatalog(c);
}
function applySaleUnitPrice(priceEl, minEl, catalogPrice, catalogEl) {
    const catalog = parseFloat(catalogPrice) || 0;
    if (catalogEl) catalogEl.value = catalog.toFixed(3);
    const min = isRetailCustomer() ? retailMinFromCatalog(catalog) : catalog;
    if (minEl) minEl.value = min.toFixed(3);
    if (!priceEl) return;
    if (isRetailCustomer()) {
        priceEl.value = min.toFixed(3);
        priceEl.placeholder = min.toFixed(3);
        priceEl.min = String(min.toFixed(3));
        priceEl.title = 'Retail minimum ' + min.toFixed(3) + ' (wholesale + ' + retailMarkupFromCatalog(catalog).toFixed(3) + '). You can increase.';
    } else {
        priceEl.value = catalog.toFixed(3);
        priceEl.placeholder = '0.000';
        priceEl.removeAttribute('min');
        priceEl.title = '';
    }
}
function refreshLinePriceFloors() {
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        const rid = tr.dataset.rowId;
        if (!rid || !document.getElementById('itemId_' + rid)?.value) return;
        const catalogEl = document.getElementById('catalogPrice_' + rid);
        const minEl = document.getElementById('minPrice_' + rid);
        const priceEl = document.getElementById('price_' + rid);
        let catalog = parseFloat(catalogEl?.value) || 0;
        if (!catalog) {
            const minNow = parseFloat(minEl?.value) || 0;
            const priceNow = parseFloat(priceEl?.value) || 0;
            catalog = minNow > 0 ? minNow : priceNow;
            if (catalogEl && catalog) catalogEl.value = catalog.toFixed(3);
        }
        if (!catalog) return;
        const min = isRetailCustomer() ? retailMinFromCatalog(catalog) : catalog;
        if (minEl) minEl.value = min.toFixed(3);
        if (!priceEl) return;
        const price = parseFloat(priceEl.value) || 0;
        if (isRetailCustomer()) {
            if (!price || price + 0.0005 < min) {
                priceEl.value = min.toFixed(3);
            }
            priceEl.placeholder = min.toFixed(3);
            priceEl.min = String(min.toFixed(3));
            priceEl.title = 'Retail minimum ' + min.toFixed(3) + ' (wholesale + ' + retailMarkupFromCatalog(catalog).toFixed(3) + '). You can increase.';
        } else {
            if (!price) priceEl.value = catalog.toFixed(3);
            priceEl.placeholder = '0.000';
            priceEl.removeAttribute('min');
            priceEl.title = '';
        }
        calcRow(rid);
    });
}

function currentSaleGrandTotal() {
    return parseFloat(document.getElementById('grandTotalDisplay')?.textContent?.replace(/[^0-9.]/g, '')) || 0;
}

function saleCreditOverAmount(grandTotal) {
    const limit = parseFloat(selectedPartyCredit.credit_limit) || 0;
    if (limit <= 0) return 0;
    const outstanding = Math.max(0, parseFloat(selectedPartyCredit.balance) || 0);
    return (outstanding + grandTotal) - limit;
}

function setSaleSaveEnabled(enabled) {
    document.querySelectorAll('.btn-save-sale, .btn-print-sale').forEach(function(btn) {
        btn.disabled = !enabled;
    });
}

function refreshCreditLimitUI() {
    const strip = document.getElementById('customerBalanceBox');
    const unpaidEl = document.getElementById('customerBalanceAmt');
    const remainEl = document.getElementById('creditRemainAmt');
    const totalEl = document.getElementById('creditTotalAmt');
    const hint = document.getElementById('creditHint');
    const bar = document.getElementById('creditUsageBar');
    const limit = parseFloat(selectedPartyCredit.credit_limit) || 0;
    const grandTotal = currentSaleGrandTotal();
    const outstanding = Math.max(0, parseFloat(selectedPartyCredit.balance) || 0);
    const curr = saleCurrency;

    if (unpaidEl) unpaidEl.textContent = curr + ' ' + outstanding.toFixed(3);

    if (!selectedPartyCredit.id) {
        if (strip) strip.classList.remove('is-exceeded', 'is-nolimit');
        if (hint) hint.textContent = '';
        setSaleSaveEnabled(true);
        return;
    }

    if (limit <= 0) {
        if (remainEl) {
            remainEl.textContent = 'No limit';
            remainEl.classList.add('is-none');
        }
        if (totalEl) {
            totalEl.textContent = 'No limit';
            totalEl.classList.add('is-none');
        }
        if (bar) {
            bar.style.width = '0%';
            bar.classList.remove('is-warn', 'is-over');
        }
        if (strip) {
            strip.classList.remove('is-exceeded');
            strip.classList.add('is-nolimit');
        }
        if (hint) hint.textContent = '';
        setSaleSaveEnabled(true);
        return;
    }

    if (totalEl) {
        totalEl.classList.remove('is-none');
        totalEl.textContent = curr + ' ' + limit.toFixed(3);
    }

    const remaining = limit - (outstanding + grandTotal);
    const exceeded = remaining < -0.001;
    const usedPct = Math.min(100, Math.max(0, ((outstanding + grandTotal) / limit) * 100));
    if (remainEl) {
        remainEl.classList.remove('is-none');
        remainEl.textContent = curr + ' ' + Math.max(0, remaining).toFixed(3);
    }
    if (bar) {
        bar.style.width = usedPct.toFixed(1) + '%';
        bar.classList.toggle('is-over', exceeded);
        bar.classList.toggle('is-warn', !exceeded && usedPct >= 80);
    }
    if (strip) {
        strip.classList.remove('is-nolimit');
        strip.classList.toggle('is-exceeded', exceeded);
    }
    if (hint) hint.textContent = exceeded ? 'Collect payment first.' : '';
    setSaleSaveEnabled(!exceeded);
}

function focusPartyField() {
    const el = document.getElementById('partySearch');
    if (el) el.focus();
}

// ═══ INIT ═══
document.addEventListener('DOMContentLoaded', () => {
    addRow(true); addRow(true);
    restoreSaleDraft();
    focusPartyField();
    // Body is visibility:hidden during iq-boot, so the first focus is dropped.
    const prevReveal = window.iqbalReveal;
    window.iqbalReveal = function () {
        if (typeof prevReveal === 'function') prevReveal();
        focusPartyField();
        setTimeout(focusPartyField, 40);
    };
    if (!document.documentElement.classList.contains('iq-boot')) {
        setTimeout(focusPartyField, 0);
    }
});

function restoreSaleDraft() {
    if (!saleDraft || !Array.isArray(saleDraft.items) || !saleDraft.items.length) return;

    // Reset auto-created blank rows before restoring draft items.
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    imeiData = {};
    rowCount = 0;

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
        if (document.getElementById('catalogPrice_' + rid) && item.unit_price) {
            document.getElementById('catalogPrice_' + rid).value = (parseFloat(item.unit_price || 0)).toFixed(3);
        }
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
            credit_limit: saleDraft.party.credit_limit || 0,
            customer_kind: saleDraft.party.customer_kind || 'wholesale'
        }, { keepPartyFocus: true });
    } else if (saleDraft.party_id) {
        document.getElementById('partyIdInput').value = String(saleDraft.party_id);
    }

    calcTotals();
}

// ═══ PAY MODE — removed, all sales are credit ═══

// ═══ ROWS ═══
function addRow(noFocus) {
    rowCount++;
    const rid = 'row_' + rowCount;
    imeiData[rid] = [];
    const tr = document.createElement('tr');
    tr.id = rid; tr.dataset.rowId = rid;
    tr.innerHTML = `
        <td class="col-num">${rowCount}</td>
        <td class="col-item" style="position:relative;">
            <input type="text" class="sale-cell-input item-search" placeholder="Search item by name or SKU…" data-row="${rid}" autocomplete="off">
            <input type="hidden" name="items[${rowCount}][item_id]" id="itemId_${rid}">
            <input type="hidden" id="hasImei_${rid}" value="0">
            <input type="hidden" name="items[${rowCount}][discount]" id="disc_${rid}" value="0">
            <div class="autocomplete-box item-dropdown" id="itemDrop_${rid}" style="display:none;"></div>
        </td>
        <td class="col-imei">
            <button type="button" class="imei-btn" id="imeiBtn_${rid}" onclick="openImeiModal('${rid}')">
                <i class="bi bi-upc-scan"></i>
            </button>
        </td>
        <td class="col-qty">
            <input type="number" class="sale-cell-input qty" name="items[${rowCount}][quantity]" id="qty_${rid}" value="" min="1" placeholder="1" oninput="calcRow('${rid}')">
            <input type="hidden" id="maxSaleQty_${rid}" value="0">
        </td>
        <td class="col-price">
            <input type="number" class="sale-cell-input unit" name="items[${rowCount}][unit_price]" id="price_${rid}" value="" step="0.001" placeholder="0.000" oninput="calcRow('${rid}')">
            <input type="hidden" id="minPrice_${rid}" value="0">
            <input type="hidden" id="catalogPrice_${rid}" value="0">
        </td>
        <td class="col-amt">
            <span class="sale-cell-input total" id="amt_${rid}">0.000</span>
        </td>
        <td class="col-act">
            <button type="button" class="sale-row-remove" onclick="removeRow('${rid}')" title="Remove row" aria-label="Remove row">×</button>
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
const itemSearchAbort = {};
const itemHighlightIdx = {};
function positionDropdown(input, drop) {
    const rect = input.getBoundingClientRect();
    drop.style.top = (rect.bottom + 4) + 'px';
    drop.style.left = rect.left + 'px';
    drop.style.minWidth = Math.max(380, rect.width) + 'px';
}

function formatSaleStock(n) {
    const v = parseFloat(n);
    if (!isFinite(v)) return '0';
    return Number.isInteger(v) ? String(v) : String(Math.round(v * 1000) / 1000);
}

function updateItemHighlight(rid, scrollActive) {
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    const idx = itemHighlightIdx[rid] ?? -1;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === idx);
    });
    if (!scrollActive) return;
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function renderItemSearchResults(rid, items) {
    const drop = document.getElementById('itemDrop_' + rid);
    const row = document.getElementById(rid);
    const input = row ? row.querySelector('.item-search') : null;
    if (!drop) return;
    if (!items.length) { drop.style.display = 'none'; return; }
    saleItemStore[rid] = items;
    drop.innerHTML = items.map((it, idx) => {
        const stockTxt = Object.prototype.hasOwnProperty.call(it, 'stock') && it.stock !== null && it.stock !== undefined
            ? formatSaleStock(it.stock)
            : '…';
        const skuHtml = it.sku
            ? ` <small style="color:#94a3b8;font-size:0.72rem;">(${escSaleHtml(it.sku)})</small>`
            : '';
        return `<div class="autocomplete-item" data-rid="${escSaleHtml(rid)}" data-idx="${idx}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <div style="flex:1;min-width:0;">
                            <strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escSaleHtml(it.name)}${skuHtml}</strong>
                        </div>
                        <div class="item-drop-meta" style="text-align:right;white-space:nowrap;flex-shrink:0;">
                            <span style="font-weight:700;color:#4338ca;">${escSaleHtml(parseFloat(it.sale_price || 0).toFixed(3))}</span>
                            <br><small style="color:#3b82f6;font-weight:600;">Stock: ${escSaleHtml(stockTxt)}</small>
                        </div>
                    </div>`;
    }).join('');
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectItem(this.dataset.rid, saleItemStore[this.dataset.rid][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            itemHighlightIdx[rid] = parseInt(this.dataset.idx, 10);
            updateItemHighlight(rid, false);
        });
    });
    updateItemHighlight(rid, false);
    if (input) positionDropdown(input, drop);
    if (input && document.activeElement === input) drop.style.display = 'block';
}

function searchItem(input, rid) {
    clearTimeout(searchTimers[rid]);
    const q = input.value.trim();
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    if (q.length < 1) { drop.style.display = 'none'; itemHighlightIdx[rid] = -1; return; }
    searchTimers[rid] = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; return; }
        if (itemSearchAbort[rid]) itemSearchAbort[rid].abort();
        itemSearchAbort[rid] = new AbortController();
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(qNow)}`, { signal: itemSearchAbort[rid].signal })
            .then(r => r.json())
            .then(items => {
                if (input.value.trim() !== qNow) return;
                if (!Array.isArray(items) || !items.length) {
                    drop.style.display = 'none';
                    itemHighlightIdx[rid] = -1;
                    return;
                }
                itemHighlightIdx[rid] = -1;
                renderItemSearchResults(rid, items);
            })
            .catch(err => {
                if (err && err.name === 'AbortError') return;
            });
    }, 250);
}

function reopenItemDropdown(input) {
    if (!input || !input.classList.contains('item-search')) return;
    const rid = input.dataset.row;
    const drop = document.getElementById('itemDrop_' + rid);
    if (!drop) return;
    if (document.getElementById('itemId_' + rid)?.value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (saleItemStore[rid] || []).length) {
        positionDropdown(input, drop);
        drop.style.display = 'block';
        return;
    }
    searchItem(input, rid);
}

document.getElementById('itemsBody').addEventListener('input', function(e) {
    if (!e.target.classList.contains('item-search')) return;
    searchItem(e.target, e.target.dataset.row);
});
document.getElementById('itemsBody').addEventListener('focusin', function(e) {
    reopenItemDropdown(e.target);
});
document.getElementById('itemsBody').addEventListener('click', function(e) {
    reopenItemDropdown(e.target);
});
document.getElementById('itemsBody').addEventListener('keydown', function(e) {
    if (!e.target.classList.contains('item-search')) return;
    const rid = e.target.dataset.row;
    const drop = document.getElementById('itemDrop_' + rid);
    const visible = drop && drop.style.display !== 'none';
    const items = saleItemStore[rid] || [];
    let idx = itemHighlightIdx[rid] ?? -1;

    if (e.key === 'ArrowDown') {
        if (!visible || !items.length) return;
        e.preventDefault();
        itemHighlightIdx[rid] = idx < items.length - 1 ? idx + 1 : 0;
        updateItemHighlight(rid, true);
    } else if (e.key === 'ArrowUp') {
        if (!visible || !items.length) return;
        e.preventDefault();
        itemHighlightIdx[rid] = idx > 0 ? idx - 1 : items.length - 1;
        updateItemHighlight(rid, true);
    } else if (e.key === 'Enter') {
        if (!visible || !items.length) return;
        e.preventDefault();
        e.stopPropagation();
        const pick = idx >= 0 ? idx : 0;
        selectItem(rid, items[pick]);
    } else if (e.key === 'Escape') {
        if (!visible) return;
        e.preventDefault();
        drop.style.display = 'none';
        itemHighlightIdx[rid] = -1;
    }
});

function selectItem(rid, item) {
    document.querySelector('#' + rid + ' .item-search').value = item.name;
    document.getElementById('itemId_'   + rid).value = item.id;
    document.getElementById('hasImei_'  + rid).value = item.has_imei;
    applySaleUnitPrice(
        document.getElementById('price_' + rid),
        document.getElementById('minPrice_' + rid),
        item.sale_price,
        document.getElementById('catalogPrice_' + rid)
    );
    const maxQtyEl = document.getElementById('maxSaleQty_' + rid);
    if (maxQtyEl) maxQtyEl.value = parseInt(item.max_sale_qty, 10) || 0;
    document.getElementById('itemDrop_' + rid).style.display = 'none';
    itemHighlightIdx[rid] = -1;
    // Set qty to 1 when item is manually selected (if still empty)
    const qtyEl = document.getElementById('qty_' + rid);
    if (qtyEl && !qtyEl.value) qtyEl.value = 1;
    if (isCashier && maxQtyEl && parseInt(maxQtyEl.value, 10) > 0 && qtyEl) {
        qtyEl.max = maxQtyEl.value;
    } else if (qtyEl) {
        qtyEl.removeAttribute('max');
    }
    // Store category and item name for IMEI length rules
    if (!window.rowCategoryMap) window.rowCategoryMap = {};
    if (!window.rowItemNameMap) window.rowItemNameMap = {};
    if (!window.rowSerialKindMap) window.rowSerialKindMap = {};
    window.rowCategoryMap[rid] = (item.category_name || '').toLowerCase();
    window.rowItemNameMap[rid] = (item.name || '').toLowerCase();
    window.rowSerialKindMap[rid] = item.serial_kind || 'phone';
    const imeiBtn = document.getElementById('imeiBtn_' + rid);
    if (imeiBtn) {
        imeiBtn.style.display = parseInt(item.has_imei, 10) ? '' : 'none';
    }
    calcRow(rid);
    if (parseInt(item.has_imei, 10)) {
        setTimeout(() => openImeiModal(rid, item.name), 120);
    }
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows[rows.length - 1]?.id === rid) addRow();
}

document.addEventListener('click', e => {
    if (!e.target.closest('.col-item') && !e.target.closest('.autocomplete-box.item-dropdown')) {
        document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
    }
    if (!e.target.closest('#partySearchWrap')) {
        document.getElementById('partyDropdown').style.display = 'none';
        partyHighlightIdx = -1;
    }
});
window.addEventListener('scroll', (e) => {
    const t = e.target;
    if (t && t.nodeType === 1 && typeof t.closest === 'function' && t.closest('.autocomplete-box')) {
        return;
    }
    document.querySelectorAll('.autocomplete-box.item-dropdown').forEach(d => d.style.display = 'none');
}, true);

// ═══ PARTY SEARCH ═══
const partyStore = {};
let partyTimer;
let partyHighlightIdx = -1;
let partySearchAbort = null;

function escSaleHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function updatePartyHighlight(scrollActive) {
    const drop = document.getElementById('partyDropdown');
    if (!drop) return;
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === partyHighlightIdx);
    });
    if (!scrollActive) return;
    const active = drop.querySelector('.autocomplete-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function bindPartyDropdown(drop) {
    drop.querySelectorAll('.autocomplete-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectParty(partyStore['results'][parseInt(this.dataset.idx, 10)]);
        });
        el.addEventListener('mouseenter', function() {
            partyHighlightIdx = parseInt(this.dataset.idx, 10);
            updatePartyHighlight(false);
        });
    });
}

function renderPartySearchResults(parties, keepHighlight) {
    const drop = document.getElementById('partyDropdown');
    const input = document.getElementById('partySearch');
    if (!drop) return;
    if (!parties.length) {
        drop.style.display = 'none';
        partyHighlightIdx = -1;
        return;
    }
    partyStore['results'] = parties;
    if (!keepHighlight) {
        partyHighlightIdx = -1;
    }
    drop.innerHTML = parties.map((p, idx) => `
                    <div class="autocomplete-item" data-idx="${idx}">
                        <strong>${escSaleHtml(p.name)}</strong>${p.customer_kind === 'retail' ? ' <small style="color:#4338ca;font-weight:700;">Retail</small>' : ''}
                    </div>`).join('');
    bindPartyDropdown(drop);
    if (input && document.activeElement === input) drop.style.display = 'block';
}

function runPartySearch() {
    const input = document.getElementById('partySearch');
    const drop = document.getElementById('partyDropdown');
    if (!input || !drop) return;
    clearTimeout(partyTimer);
    const q = input.value.trim();
    if (q.length < 1) { drop.style.display = 'none'; return; }
    partyTimer = setTimeout(() => {
        const qNow = input.value.trim();
        if (qNow.length < 1) { drop.style.display = 'none'; return; }
        if (partySearchAbort) partySearchAbort.abort();
        partySearchAbort = new AbortController();
        const signal = partySearchAbort.signal;
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(qNow)}&type=customer&balances=0`, { signal })
            .then(r => r.json())
            .then(parties => {
                if (input.value.trim() !== qNow) return;
                if (!Array.isArray(parties) || !parties.length) {
                    drop.style.display = 'none';
                    partyHighlightIdx = -1;
                    return;
                }
                renderPartySearchResults(parties, false);
            })
            .catch(err => {
                if (err && err.name === 'AbortError') return;
                drop.style.display = 'none';
            });
    }, 300);
}

function reopenPartyDropdown() {
    const input = document.getElementById('partySearch');
    const drop = document.getElementById('partyDropdown');
    if (!input || !drop) return;
    if (document.getElementById('partyIdInput').value) return;
    if (input.value.trim().length < 1) return;
    if (drop.style.display !== 'none') return;
    if (drop.innerHTML.trim() && (partyStore['results'] || []).length) {
        drop.style.display = 'block';
        return;
    }
    runPartySearch();
}

document.getElementById('partySearch').addEventListener('input', function() {
    this.classList.remove('selected');
    document.getElementById('partyIdInput').value = '';
    const creditBox = document.getElementById('customerBalanceBox');
    creditBox.style.display = 'none';
    creditBox.classList.remove('is-exceeded', 'is-nolimit');
    selectedPartyCredit = { id: 0, balance: 0, credit_limit: 0, customer_kind: 'wholesale' };
    const creditHint = document.getElementById('creditHint');
    if (creditHint) creditHint.textContent = '';
    runPartySearch();
});
document.getElementById('partySearch').addEventListener('focus', reopenPartyDropdown);
document.getElementById('partySearch').addEventListener('click', reopenPartyDropdown);

document.getElementById('partySearch').addEventListener('keydown', function(e) {
    const drop = document.getElementById('partyDropdown');
    const visible = drop && drop.style.display !== 'none';
    const parties = partyStore['results'] || [];

    if (e.key === 'ArrowDown') {
        if (!visible || !parties.length) return;
        e.preventDefault();
        partyHighlightIdx = partyHighlightIdx < parties.length - 1 ? partyHighlightIdx + 1 : 0;
        updatePartyHighlight(true);
    } else if (e.key === 'ArrowUp') {
        if (!visible || !parties.length) return;
        e.preventDefault();
        partyHighlightIdx = partyHighlightIdx > 0 ? partyHighlightIdx - 1 : parties.length - 1;
        updatePartyHighlight(true);
    } else if (e.key === 'Enter') {
        if (!visible || !parties.length) return;
        e.preventDefault();
        e.stopPropagation();
        const idx = partyHighlightIdx >= 0 ? partyHighlightIdx : 0;
        selectParty(parties[idx]);
    } else if (e.key === 'Escape') {
        if (!visible) return;
        e.preventDefault();
        drop.style.display = 'none';
        partyHighlightIdx = -1;
    }
});

function selectParty(party, opts) {
    opts = opts || {};
    if (!party) return;
    if (!Object.prototype.hasOwnProperty.call(party, 'balance')) {
        const el = document.getElementById('partySearch');
        el.value = party.name;
        el.classList.add('selected');
        document.getElementById('partyIdInput').value = party.id;
        document.getElementById('partyDropdown').style.display = 'none';
        partyHighlightIdx = -1;
        fetch('?page=sales&action=searchPartyBalances&ids=' + encodeURIComponent(party.id) + '&type=customer')
            .then(r => r.json())
            .then(rows => {
                if (String(document.getElementById('partyIdInput').value) !== String(party.id)) return;
                const extra = Array.isArray(rows) && rows[0] ? rows[0] : { balance: 0 };
                selectParty(Object.assign({}, party, extra), opts);
            })
            .catch(() => {
                if (String(document.getElementById('partyIdInput').value) !== String(party.id)) return;
                selectParty(Object.assign({}, party, { balance: 0 }), opts);
            });
        return;
    }

    const el = document.getElementById('partySearch');
    el.value = party.name; el.classList.add('selected');
    document.getElementById('partyIdInput').value = party.id;
    document.getElementById('partyDropdown').style.display = 'none';
    partyHighlightIdx = -1;

    const bal = parseFloat(party.balance) || 0;
    const box = document.getElementById('customerBalanceBox');
    if (box) box.style.display = 'flex';

    selectedPartyCredit = {
        id: parseInt(party.id, 10) || 0,
        balance: bal,
        credit_limit: parseFloat(party.credit_limit) || 0,
        customer_kind: party.customer_kind === 'retail' ? 'retail' : 'wholesale'
    };
    refreshLinePriceFloors();
    refreshCreditLimitUI();

    const scanBar = document.getElementById('imeiScanBar');
    if (scanBar && !opts.keepPartyFocus) setTimeout(function() { scanBar.focus(); }, 0);
}

// ═══ CALCULATIONS ═══
const isCashier = <?= in_array(Auth::role(), ['cashier','viewer']) ? 'true' : 'false' ?>;
// TEMP: when allowSalesmanFreePrice, cashier may set any price (incl. below catalog)
const enforcePriceFloor = <?= (in_array(Auth::role(), ['cashier','viewer']) && empty($allowSalesmanFreePrice)) ? 'true' : 'false' ?>;

/** Total qty of itemId already on the invoice (all lines). */
function invoiceQtyForItem(itemId, excludeRid) {
    let total = 0;
    const id = parseInt(itemId, 10) || 0;
    if (!id) return 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid || rid === excludeRid) return;
        const rowItemId = parseInt(document.getElementById('itemId_' + rid)?.value, 10) || 0;
        if (rowItemId !== id) return;
        total += parseInt(document.getElementById('qty_' + rid)?.value, 10) || 0;
    });
    return total;
}

function calcRow(rid) {
    const qty      = parseFloat(document.getElementById('qty_'      + rid)?.value) || 0;
    const price    = parseFloat(document.getElementById('price_'    + rid)?.value) || 0;
    const disc     = parseFloat(document.getElementById('disc_'     + rid)?.value) || 0;
    const minPrice = parseFloat(document.getElementById('minPrice_' + rid)?.value) || 0;
    const maxSale  = parseInt(document.getElementById('maxSaleQty_' + rid)?.value, 10) || 0;
    const itemId   = parseInt(document.getElementById('itemId_' + rid)?.value, 10) || 0;
    const priceEl  = document.getElementById('price_' + rid);
    const qtyEl    = document.getElementById('qty_' + rid);

    // Cashier catalog floor, or any user on a retail customer (list + 0.500 / 1.000)
    const mustMeetFloor = (enforcePriceFloor || isRetailCustomer()) && minPrice > 0;
    if (mustMeetFloor && price < minPrice) {
        priceEl.style.outline = '2px solid #dc2626';
        priceEl.style.outlineOffset = '-2px';
        priceEl.style.background = '#fff5f5';
        priceEl.style.color      = '#dc2626';
        priceEl.title = (isRetailCustomer() ? 'Retail minimum ' : 'Cannot sell below ') + minPrice.toFixed(3)
            + (isRetailCustomer() ? ' (retail floor)' : ' — increase only');
    } else if (mustMeetFloor && minPrice > 0 && price > minPrice) {
        priceEl.style.outline = '2px solid #10b981';
        priceEl.style.outlineOffset = '-2px';
        priceEl.style.background = '#f0fdf4';
        priceEl.style.color      = '#065f46';
        priceEl.title = 'Price increased from catalog ' + minPrice.toFixed(3);
    } else {
        priceEl.style.outline = '';
        priceEl.style.outlineOffset = '';
        priceEl.style.background = '';
        priceEl.style.color      = '';
        priceEl.title = '';
    }

    // Cashier: max sale qty per item on this invoice (sums duplicate lines)
    if (qtyEl) {
        const otherQty = invoiceQtyForItem(itemId, rid);
        const totalQty = otherQty + (parseInt(qty, 10) || 0);
        if (isCashier && maxSale > 0 && totalQty > maxSale) {
            qtyEl.style.outline = '2px solid #dc2626';
            qtyEl.style.outlineOffset = '-2px';
            qtyEl.style.background = '#fff5f5';
            qtyEl.style.color      = '#dc2626';
            qtyEl.title = 'Salesman max qty is ' + maxSale + ' (invoice total ' + totalQty + ')';
        } else {
            qtyEl.style.outline = '';
            qtyEl.style.outlineOffset = '';
            qtyEl.style.background = '';
            qtyEl.style.color      = '';
            qtyEl.title = (isCashier && maxSale > 0) ? ('Max ' + maxSale + ' per invoice') : '';
        }
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

    document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(3);
    const gtQty = document.getElementById('gtQtyHint');
    if (gtQty) gtQty.textContent = totalQty + ' pc' + (totalQty !== 1 ? 's' : '');
    const gtCard = document.getElementById('grandTotalCard');
    if (gtCard) gtCard.classList.toggle('has-amount', grandTotal > 0.0005);
    refreshCreditLimitUI();
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
    return IqbalImei.rule(
        (window.rowSerialKindMap && window.rowSerialKindMap[row]) || '',
        (window.rowItemNameMap && window.rowItemNameMap[row]) || '',
        (window.rowCategoryMap && window.rowCategoryMap[row]) || '',
        { phoneMin: 15, phoneMax: 15 }
    );
}

function processPastedImeis() {
    var raw = document.getElementById('imeiPasteInput').value;
    // Split by newline, comma, space, tab, semicolon
    var list = raw.split(/[\n,;\s\t]+/).map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });

    var rule = getImeiRule(currentImeiRow);

    var added = 0, skipped = 0, invalid = 0;
    var allOtherImeis = getAllEnteredImeis(currentImeiRow);

    list.forEach(function(raw) {
        var imei = IqbalImei.normalize(raw);
        if (!rule.test(imei)) { invalid++; return; }
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
    const val  = IqbalImei.normalize(document.getElementById('imeiScanInput').value);
    const rule = getImeiRule(currentImeiRow);
    if (IqbalImei.shouldAutoConfirm(val, rule)) {
        _imeiAutoTimer = setTimeout(function() { confirmImei(); }, 150);
    }
}

function confirmImei() {
    clearTimeout(_imeiAutoTimer);
    const input = document.getElementById('imeiScanInput');
    const imei  = IqbalImei.normalize(input.value);
    if (!imei) return;

    // Clear input FIRST — instant, so scanner can start next one immediately
    input.value = '';

    // Validate length based on item/category rules
    const rule = getImeiRule(currentImeiRow);

    if (!rule.test(imei)) { showImeiMsg('Need ' + rule.label + ': ' + imei, 'err'); input.focus(); return; }

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
// except the IMEI scan bar (handled below → scanImeiToRow)
document.getElementById('saleForm').addEventListener('keydown', function(e) {
    // Ctrl+S / Cmd+S or F12: same as "Save & Print" (uses Default Print: A5 or Thermal)
    var printKeys = ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) || e.key === 'F12';
    if (printKeys) {
        var modal = document.getElementById('imeiModal');
        if (modal && modal.classList.contains('show')) return;
        e.preventDefault();
        var pm = document.getElementById('salePrintMode');
        if (pm) pm.value = '1';
        if (typeof this.requestSubmit === 'function') {
            this.requestSubmit();
        } else {
            var pb = document.getElementById('btnSavePrint');
            if (pb) pb.click();
        }
        return;
    }
    if (e.target && e.target.id === 'imeiScanBar') return;
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' &&
        !['submit','hidden','button'].includes(e.target.type)) {
        e.preventDefault();
    }
}, true);

// ═══ FORM VALIDATION ═══
document.getElementById('saleForm').addEventListener('submit', function(e) {
    // Client-side submit lock (server also validates one-time nonce)
    if (this.dataset.submitting === '1') { e.preventDefault(); return; }

    if (!document.getElementById('partyIdInput').value) {
        e.preventDefault(); alert('Please select a customer.'); document.getElementById('partySearch').focus(); return;
    }
    let hasItem = false;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (rid && document.getElementById('itemId_' + rid)?.value) hasItem = true;
    });
    if (!hasItem) { e.preventDefault(); alert('Please add at least one item.'); return; }

    // Strict: every IMEI-tracked line must have one scanned serial per unit
    let imeiErr = null;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.dataset.rowId;
        if (!rid || !document.getElementById('itemId_' + rid)?.value) return;
        const hasImei = document.getElementById('hasImei_' + rid)?.value === '1';
        if (!hasImei) return;
        const qty = parseInt(document.getElementById('qty_' + rid)?.value, 10) || 0;
        const imeis = (imeiData[rid] || []).length;
        if (imeis !== qty) {
            const name = tr.querySelector('.item-search')?.value || 'item';
            imeiErr = 'Item "' + name + '": must scan ' + qty + ' IMEIs before selling (currently ' + imeis + ').';
        }
    });
    if (imeiErr) { e.preventDefault(); alert(imeiErr); return; }

    const creditOver = saleCreditOverAmount(currentSaleGrandTotal());
    if (creditOver > 0.001) {
        e.preventDefault();
        alert('This customer’s credit limit would be exceeded by ' + saleCurrency + ' ' + creditOver.toFixed(3)
            + '. Collect payment first. No user (including admin) can override this on the invoice.');
        return;
    }

    // Block below catalog (cashier) or below retail floor (any user)
    if (isCashier || isRetailCustomer()) {
        let belowMin = false;
        let overMax = null;
        const qtyByItem = {};
        const maxByItem = {};
        const nameByItem = {};
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const rid = tr.dataset.rowId;
            if (!rid || !document.getElementById('itemId_' + rid)?.value) return;
            const price    = parseFloat(document.getElementById('price_'    + rid)?.value) || 0;
            const minPrice = parseFloat(document.getElementById('minPrice_' + rid)?.value) || 0;
            if ((enforcePriceFloor || isRetailCustomer()) && minPrice > 0 && price < minPrice) belowMin = true;

            const itemId = parseInt(document.getElementById('itemId_' + rid)?.value, 10) || 0;
            const qty = parseInt(document.getElementById('qty_' + rid)?.value, 10) || 0;
            const maxSale = parseInt(document.getElementById('maxSaleQty_' + rid)?.value, 10) || 0;
            if (!itemId) return;
            qtyByItem[itemId] = (qtyByItem[itemId] || 0) + qty;
            if (maxSale > 0) maxByItem[itemId] = maxSale;
            nameByItem[itemId] = tr.querySelector('.item-search')?.value || ('Item #' + itemId);
        });
        if (belowMin) {
            e.preventDefault();
            alert(isRetailCustomer()
                ? 'Retail prices cannot go below wholesale + 0.500 (under 40 KWD) or + 1.000 (40 KWD+). You may increase the price. Fix the lines marked in red.'
                : 'One or more items are priced below the minimum catalog price. You can only increase the price, not decrease it. Fix the prices marked in red.');
        }
        Object.keys(maxByItem).forEach(id => {
            if (qtyByItem[id] > maxByItem[id]) {
                overMax = 'Item "' + nameByItem[id] + '": salesman max qty is ' + maxByItem[id]
                    + ' per invoice (you entered ' + qtyByItem[id] + ').';
            }
        });
        if (overMax) {
            e.preventDefault();
            alert(overMax);
        }
    }

    if (e.defaultPrevented) return;
    this.dataset.submitting = '1';
    this.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => { btn.disabled = true; });
});

// ═══ SCAN-FIRST IMEI ═══
let scanCount = 0;
let _scanBarMsgTimer = null;
let createSpValidList = [];
let createBulkScanQueue = [];
let createBulkScanRunning = false;
let createBulkScanStats = { saved: 0, skipped: [] };

document.getElementById('imeiScanBar').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    scanImeiToRow();
});

document.getElementById('btnCreateScanPaste').addEventListener('click', openCreateSalePasteModal);
document.getElementById('createSpTextarea').addEventListener('input', updateCreateSalePasteLineCount);
document.getElementById('createSpTextarea').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' || e.shiftKey) return;
    e.preventDefault();
    var confirmBtn = document.getElementById('createSpConfirmBtn');
    var validateBtn = document.getElementById('createSpValidateBtn');
    if (confirmBtn && !confirmBtn.disabled && createSpValidList.length > 0) {
        confirmCreateSalePaste();
        return;
    }
    if (validateBtn && !validateBtn.disabled) {
        validateCreateSalePaste();
    }
});
document.getElementById('createSpValidateBtn').addEventListener('click', validateCreateSalePaste);
document.getElementById('createSpConfirmBtn').addEventListener('click', confirmCreateSalePaste);

function normalizeScanImei(raw) {
    return (window.IqbalImei && IqbalImei.normalize) ? IqbalImei.normalize(raw) : String(raw || '').toUpperCase().replace(/[\r\n\t\s]/g, '');
}

function getAllInvoiceImeisForScan() {
    const all = [];
    Object.keys(imeiData).forEach(function(rid) {
        (imeiData[rid] || []).forEach(function(im) { all.push(im); });
    });
    return all;
}

function shakeScanBar() {
    const wrap = document.querySelector('.scan-bar-wrap');
    if (!wrap) return;
    wrap.classList.remove('scan-bar-shake');
    void wrap.offsetWidth;
    wrap.classList.add('scan-bar-shake');
    setTimeout(function() { wrap.classList.remove('scan-bar-shake'); }, 450);
}

function flashScanRow(rid) {
    const tr = document.getElementById(rid);
    if (!tr) return;
    try {
        tr.scrollIntoView({ behavior: 'auto', block: 'nearest' });
    } catch (err) { /* ignore */ }
}

function setScanBarErr(text) {
    const msg = document.getElementById('scanBarMsg');
    if (_scanBarMsgTimer) { clearTimeout(_scanBarMsgTimer); _scanBarMsgTimer = null; }
    msg.className = 'scan-bar-msg err';
    msg.innerHTML = '';
    const ic = document.createElement('i');
    ic.className = 'bi bi-exclamation-triangle-fill';
    ic.setAttribute('aria-hidden', 'true');
    const span = document.createElement('span');
    span.textContent = text;
    msg.appendChild(ic);
    msg.appendChild(span);
    shakeScanBar();
    _scanBarMsgTimer = setTimeout(function() {
        msg.textContent = '';
        msg.innerHTML = '';
        msg.className = 'scan-bar-msg';
        _scanBarMsgTimer = null;
    }, 3500);
}

function setScanBarOkShort() {
    const msg = document.getElementById('scanBarMsg');
    if (_scanBarMsgTimer) { clearTimeout(_scanBarMsgTimer); _scanBarMsgTimer = null; }
    msg.className = 'scan-bar-msg ok';
    msg.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> <span>Added</span>';
    _scanBarMsgTimer = setTimeout(function() {
        msg.textContent = '';
        msg.innerHTML = '';
        msg.className = 'scan-bar-msg';
        _scanBarMsgTimer = null;
    }, 1600);
}

function setScanBarMsg(text, type) {
    const msg = document.getElementById('scanBarMsg');
    if (!msg) return;
    if (_scanBarMsgTimer) { clearTimeout(_scanBarMsgTimer); _scanBarMsgTimer = null; }
    msg.className = 'scan-bar-msg ' + (type || '');
    msg.textContent = text || '';
    if (text) {
        _scanBarMsgTimer = setTimeout(function() {
            msg.textContent = '';
            msg.className = 'scan-bar-msg';
            _scanBarMsgTimer = null;
        }, type === 'err' ? 4000 : 3500);
    }
}

function applyCreateScannedImei(data, imei) {
    if (!data.found) {
        if (data.accepted) return { ok: false, msg: 'Not registered in system' };
        return { ok: false, msg: data.message || 'IMEI not found' };
    }

    if (getAllInvoiceImeisForScan().includes(imei)) {
        return { ok: false, msg: 'Already on this invoice' };
    }

    let targetRid = null;
    const rows = document.querySelectorAll('#itemsBody tr');

    for (const tr of rows) {
        const rid = tr.dataset.rowId;
        const itemIdEl = document.getElementById('itemId_' + rid);
        if (itemIdEl && parseInt(itemIdEl.value, 10) === data.item_id) {
            targetRid = rid;
            break;
        }
    }

    let affectedRid = null;

    if (targetRid) {
        if (!imeiData[targetRid]) imeiData[targetRid] = [];
        const maxSale = parseInt(document.getElementById('maxSaleQty_' + targetRid)?.value, 10) || 0;
        const nextQty = imeiData[targetRid].length + 1;
        const otherQty = invoiceQtyForItem(data.item_id, targetRid);
        if (isCashier && maxSale > 0 && (otherQty + nextQty) > maxSale) {
            return { ok: false, msg: 'Salesman max qty is ' + maxSale + ' for this item' };
        }
        imeiData[targetRid].push(imei);
        document.getElementById('qty_' + targetRid).value = imeiData[targetRid].length;
        document.getElementById('imeiInput_' + targetRid).value = imeiData[targetRid].join('\n');
        updateImeiBtn(targetRid);
        calcRow(targetRid);
        affectedRid = targetRid;
    } else {
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

        const maxSaleNew = parseInt(data.max_sale_qty, 10) || 0;
        const otherQtyNew = invoiceQtyForItem(data.item_id, emptyRid);
        if (isCashier && maxSaleNew > 0 && (otherQtyNew + 1) > maxSaleNew) {
            return { ok: false, msg: 'Salesman max qty is ' + maxSaleNew + ' for this item' };
        }

        document.querySelector('#' + emptyRid + ' .item-search').value = data.item_name;
        document.getElementById('itemId_'   + emptyRid).value = data.item_id;
        document.getElementById('hasImei_'  + emptyRid).value = data.has_imei;
        applySaleUnitPrice(
            document.getElementById('price_' + emptyRid),
            document.getElementById('minPrice_' + emptyRid),
            data.sale_price,
            document.getElementById('catalogPrice_' + emptyRid)
        );
        const maxEl = document.getElementById('maxSaleQty_' + emptyRid);
        if (maxEl) maxEl.value = maxSaleNew;
        document.getElementById('qty_'      + emptyRid).value = 1;
        const qtyNew = document.getElementById('qty_' + emptyRid);
        if (isCashier && maxSaleNew > 0 && qtyNew) qtyNew.max = String(maxSaleNew);
        if (!window.rowSerialKindMap) window.rowSerialKindMap = {};
        if (!window.rowItemNameMap) window.rowItemNameMap = {};
        if (!window.rowCategoryMap) window.rowCategoryMap = {};
        window.rowSerialKindMap[emptyRid] = data.serial_kind || 'phone';
        window.rowItemNameMap[emptyRid] = (data.item_name || '').toLowerCase();
        window.rowCategoryMap[emptyRid] = (data.category_name || '').toLowerCase();

        imeiData[emptyRid] = [imei];
        document.getElementById('imeiInput_' + emptyRid).value = imei;
        updateImeiBtn(emptyRid);
        calcRow(emptyRid);

        addRow();
        affectedRid = emptyRid;
    }

    return { ok: true, rid: affectedRid };
}

function lookupAndApplyCreateImei(imei) {
    return fetch('?page=imei&action=lookupImei&imei=' + encodeURIComponent(imei))
        .then(function(r) { return r.json(); })
        .then(function(data) { return applyCreateScannedImei(data, imei); });
}

function scanImeiToRow() {
    const input = document.getElementById('imeiScanBar');
    const msg   = document.getElementById('scanBarMsg');
    const imei  = normalizeScanImei(input.value);
    if (!imei) return;

    input.value = '';
    input.focus();
    if (_scanBarMsgTimer) { clearTimeout(_scanBarMsgTimer); _scanBarMsgTimer = null; }
    msg.className = 'scan-bar-msg';
    msg.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> <span>Looking up…</span>';

    lookupAndApplyCreateImei(imei)
        .then(function(result) {
            if (!result.ok) {
                setScanBarErr(result.msg);
                return;
            }
            scanCount++;
            document.getElementById('scanBarCount').textContent = scanCount + ' scanned';
            setScanBarOkShort();
            calcTotals();
            if (result.rid) flashScanRow(result.rid);
        })
        .catch(function() {
            setScanBarErr('Network error');
        });
}

function openCreateSalePasteModal() {
    document.getElementById('createSpTextarea').value = '';
    document.getElementById('createSpLineCount').textContent = '0';
    document.getElementById('createSpOnInvoiceCount').textContent = String(getAllInvoiceImeisForScan().length);
    document.getElementById('createSpPreview').style.display = 'none';
    document.getElementById('createSpPreview').innerHTML = '';
    document.getElementById('createSpProgress').style.display = 'none';
    document.getElementById('createSpProgress').textContent = '';
    document.getElementById('createSpConfirmBtn').disabled = true;
    document.getElementById('createSpConfirmBtn').innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    document.getElementById('createSpValidateBtn').disabled = false;
    createSpValidList = [];
    createBulkScanQueue = [];
    createBulkScanRunning = false;
    createBulkScanStats = { saved: 0, skipped: [] };

    var modal = new bootstrap.Modal(document.getElementById('createSalePasteModal'));
    modal.show();
    setTimeout(function() { document.getElementById('createSpTextarea').focus(); }, 350);
}

function updateCreateSalePasteLineCount() {
    var raw = document.getElementById('createSpTextarea').value;
    var lines = raw.split(/[\r\n,;]+/).map(function(s) { return s.trim(); }).filter(Boolean);
    document.getElementById('createSpLineCount').textContent = lines.length;
    // Editing after validate → require Validate again before Import (Enter flow)
    if (createSpValidList.length > 0) {
        createSpValidList = [];
        document.getElementById('createSpConfirmBtn').disabled = true;
        document.getElementById('createSpPreview').style.display = 'none';
        document.getElementById('createSpPreview').innerHTML = '';
    }
}

function validateCreateSalePaste() {
    var raw = document.getElementById('createSpTextarea').value;
    var lines = raw.split(/[\r\n,;]+/);
    var valid = [];
    var errors = [];
    var seen = {};
    var onInvoice = getAllInvoiceImeisForScan();

    lines.forEach(function(line) {
        var imei = normalizeScanImei(line);
        if (!imei) return;

        if (!IqbalImei.isPlausible(imei)) {
            errors.push({ imei: imei, reason: 'Not a phone IMEI (15–18 digits) or tablet serial (11–20 letters/numbers)' });
            return;
        }
        if (seen[imei]) {
            errors.push({ imei: imei, reason: 'Duplicate in list' });
            return;
        }
        if (onInvoice.includes(imei)) {
            errors.push({ imei: imei, reason: 'Already on this invoice' });
            return;
        }
        seen[imei] = true;
        valid.push(imei);
    });

    createSpValidList = valid;

    var html = '<div class="create-paste-summary">';
    html += '<span class="create-paste-ok"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' valid</span>';
    if (errors.length > 0) {
        html += '<span class="create-paste-err"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="create-paste-err-list">';
        errors.forEach(function(e) {
            html += '<div class="create-paste-err-row"><span class="create-paste-err-imei">' + e.imei + '</span><span class="create-paste-err-reason">' + e.reason + '</span></div>';
        });
        html += '</div>';
    }

    var preview = document.getElementById('createSpPreview');
    preview.innerHTML = html;
    preview.style.display = 'block';
    document.getElementById('createSpConfirmBtn').disabled = (valid.length === 0);
}

function confirmCreateSalePaste() {
    if (createSpValidList.length === 0) return;

    var btn = document.getElementById('createSpConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';
    document.getElementById('createSpValidateBtn').disabled = true;

    createBulkScanQueue = createSpValidList.slice();
    createBulkScanStats = { saved: 0, skipped: [] };
    document.getElementById('createSpProgress').style.display = 'block';
    updateCreateBulkPasteProgress();
    processCreateBulkScanQueue();
}

function updateCreateBulkPasteProgress() {
    var total = createSpValidList.length;
    var done = createBulkScanStats.saved + createBulkScanStats.skipped.length;
    var pending = createBulkScanQueue.length + (createBulkScanRunning ? 1 : 0);
    document.getElementById('createSpProgress').textContent =
        'Processing ' + done + ' / ' + total + ' · ' + pending + ' remaining · ' + createBulkScanStats.saved + ' added';
}

function processCreateBulkScanQueue() {
    if (createBulkScanRunning) return;

    if (createBulkScanQueue.length === 0) {
        var btn = document.getElementById('createSpConfirmBtn');
        if (btn.disabled && createSpValidList.length > 0) {
            finishCreateBulkPaste();
        }
        return;
    }

    createBulkScanRunning = true;
    var imei = createBulkScanQueue.shift();
    updateCreateBulkPasteProgress();

    lookupAndApplyCreateImei(imei)
        .then(function(result) {
            if (result.ok) {
                createBulkScanStats.saved++;
                scanCount++;
                document.getElementById('scanBarCount').textContent = scanCount + ' scanned';
            } else {
                createBulkScanStats.skipped.push({ imei: imei, reason: result.msg });
            }
        })
        .catch(function() {
            createBulkScanStats.skipped.push({ imei: imei, reason: 'Network error' });
        })
        .finally(function() {
            createBulkScanRunning = false;
            updateCreateBulkPasteProgress();
            processCreateBulkScanQueue();
        });
}

function finishCreateBulkPaste() {
    calcTotals();

    createSpValidList = [];
    createBulkScanQueue = [];

    var btn = document.getElementById('createSpConfirmBtn');
    var validateBtn = document.getElementById('createSpValidateBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    validateBtn.disabled = false;

    var modalEl = document.getElementById('createSalePasteModal');
    var modalInst = bootstrap.Modal.getInstance(modalEl);
    if (modalInst) modalInst.hide();

    var msg = createBulkScanStats.saved + ' IMEI(s) added';
    if (createBulkScanStats.skipped.length > 0) {
        msg += ' · ' + createBulkScanStats.skipped.length + ' skipped';
    }
    setScanBarMsg(msg, createBulkScanStats.saved > 0 ? 'ok' : 'err');

    createBulkScanStats = { saved: 0, skipped: [] };
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
