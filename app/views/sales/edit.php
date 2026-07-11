<?php
function editMoney($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); }
// Form page — no Select2/DataTables (same as sales/create).
$skipListAssets = true;
?>

<style>
/* ═══ EDIT SALE PAGE ═══ */
.se-wrap { max-width: 1400px; margin: 0 auto; }

.se-topbar {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 20px; margin-bottom: 16px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a9e 100%);
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(30, 58, 95, 0.28);
}
.se-back {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22);
    color: #fff; text-decoration: none; transition: background .15s;
}
.se-back:hover { background: rgba(255,255,255,0.22); color: #fff; }
.se-topbar-title { flex: 1; min-width: 180px; }
.se-topbar-title h1 {
    margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.se-inv-no { font-family: ui-monospace, monospace; letter-spacing: 0.02em; }
.se-status {
    display: inline-flex; align-items: center; padding: 3px 12px;
    border-radius: 999px; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.se-status-paid { background: rgba(16,185,129,0.25); color: #6ee7b7; border: 1px solid rgba(110,231,183,0.35); }
.se-status-partial { background: rgba(245,158,11,0.25); color: #fcd34d; border: 1px solid rgba(252,211,77,0.35); }
.se-status-confirmed { background: rgba(99,102,241,0.25); color: #c7d2fe; border: 1px solid rgba(199,210,254,0.35); }
.se-status-default { background: rgba(255,255,255,0.15); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); }

.se-topbar-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.se-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px; font-size: 0.82rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; transition: all .15s;
    white-space: nowrap;
}
.se-btn-ghost { background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); }
.se-btn-ghost:hover { background: rgba(255,255,255,0.18); color: #fff; }
.se-btn-primary { background: #fff; color: #1e3a5f; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.se-btn-primary:hover { background: #f0f9ff; transform: translateY(-1px); }
.se-btn-outline { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,0.45); }
.se-btn-outline:hover { background: rgba(255,255,255,0.12); color: #fff; }
.se-btn-thermal { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(110,231,183,0.4); }
.se-btn-thermal:hover { background: rgba(16,185,129,0.32); color: #fff; }

.se-grid { display: grid; grid-template-columns: minmax(300px, 360px) 1fr; gap: 16px; align-items: start; }
@media (max-width: 991px) { .se-grid { grid-template-columns: 1fr; } }

.se-panel {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
}
.se-panel-head {
    padding: 12px 18px;
    background: linear-gradient(135deg, #f8faff, #f0f4ff);
    border-bottom: 1px solid #e0e7ff;
    font-size: 0.78rem; font-weight: 800; color: #4338ca;
    text-transform: uppercase; letter-spacing: 0.06em;
    display: flex; align-items: center; gap: 8px;
}
.se-panel-body { padding: 18px; }

.se-field { margin-bottom: 16px; }
.se-field:last-child { margin-bottom: 0; }
.se-label {
    display: block; font-size: 0.72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;
}
.se-input {
    width: 100%; padding: 10px 12px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 0.9rem; color: #1e293b; background: #fafbff;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.se-input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.se-input-group { display: flex; align-items: stretch; }
.se-input-prefix {
    display: flex; align-items: center; padding: 0 12px;
    background: #f1f5f9; border: 1.5px solid #e2e8f0; border-right: none;
    border-radius: 10px 0 0 10px; font-size: 0.82rem; font-weight: 700; color: #64748b;
}
.se-input-group .se-input { border-radius: 0 10px 10px 0; }

.se-info-chip {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px; border-radius: 10px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
}
.se-info-chip i { color: #94a3b8; font-size: 0.85rem; margin-top: 2px; }
.se-info-chip .name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.3; }
.se-info-chip .sub { font-size: 0.78rem; color: #64748b; margin-top: 2px; }

.se-totals {
    background: linear-gradient(145deg, #eff6ff 0%, #eef2ff 100%);
    border: 2px solid #c7d2fe; border-radius: 12px; padding: 14px 16px;
}
.se-totals-row {
    display: flex; justify-content: space-between; align-items: baseline;
    gap: 12px; padding: 4px 0;
}
.se-totals-row.grand {
    padding-bottom: 10px; margin-bottom: 8px;
    border-bottom: 1px dashed #a5b4fc;
}
.se-totals-row .lbl { font-size: 0.72rem; font-weight: 700; color: #4338ca; text-transform: uppercase; letter-spacing: 0.04em; }
.se-totals-row.grand .val { font-size: 1.35rem; font-weight: 900; color: #1e3a5f; }
.se-totals-row .val { font-weight: 700; font-size: 0.9rem; color: #334155; }
.se-totals-row .val.paid { color: #059669; }
.se-totals-row .val.balance { color: #dc2626; }
.se-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 6px; }

/* Items panel — scrollable table, scan bar stays visible */
.se-panel.se-items-panel { overflow: visible; }
.se-items-panel {
    display: flex; flex-direction: column;
    max-height: calc(100vh - 140px);
}
.se-items-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 18px;
    background: linear-gradient(135deg, #f8faff, #f0f4ff);
    border-bottom: 1px solid #e0e7ff;
}
.se-items-head-title {
    font-size: 0.78rem; font-weight: 800; color: #4338ca;
    text-transform: uppercase; letter-spacing: 0.06em;
    display: flex; align-items: center; gap: 8px;
}
.se-items-hint { font-size: 0.72rem; color: #64748b; font-weight: 500; }

.se-scan {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 18px;
    background: linear-gradient(135deg, #eff6ff, #e0e7ff);
    border-bottom: 1px solid #c7d2fe;
}
.se-scan-wrap { position: relative; flex: 1; min-width: 260px; }
.se-scan-wrap i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #6366f1; font-size: 1.05rem; pointer-events: none;
}
.se-scan-input {
    width: 100%; padding: 12px 14px 12px 42px;
    border: 2px solid #94a3b8; border-radius: 11px; min-height: 46px;
    font-size: 0.95rem; font-family: ui-monospace, monospace; letter-spacing: 0.4px;
    background: #fafbff; color: #1e293b; outline: none; transition: all .2s;
}
.se-scan-input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
.se-scan-input::placeholder { font-family: inherit; letter-spacing: normal; font-size: 0.82rem; color: #94a3b8; }
.se-scan-meta { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.se-scan-msg { font-size: 0.76rem; font-weight: 600; min-width: 80px; }
.se-scan-msg.ok { color: #065f46; }
.se-scan-msg.err { color: #991b1b; }
.se-scan-count {
    display: inline-flex; align-items: baseline; gap: 6px;
    padding: 8px 18px; border-radius: 999px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff; font-size: 0.78rem; font-weight: 700;
    box-shadow: 0 3px 12px rgba(79,70,229,0.35); white-space: nowrap;
}
.se-scan-count-num { font-size: 1.25rem; font-weight: 900; line-height: 1; }
.se-btn-paste {
    padding: 10px 16px; border-radius: 10px; border: 1.5px solid #0ea5e9;
    background: #fff; color: #0ea5e9; font-size: 0.82rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
    flex-shrink: 0; transition: all .15s;
}
.se-btn-paste:hover { background: #0ea5e9; color: #fff; }
.se-paste-textarea {
    width: 100%; padding: 12px; border: 1.5px solid #c7d2fe; border-radius: 8px;
    font-family: ui-monospace, monospace; font-size: 0.85rem; resize: vertical;
    background: #fafbff; color: #1e293b; outline: none; min-height: 200px; letter-spacing: 0.5px;
}
.se-paste-textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.se-paste-summary { display: flex; gap: 14px; margin-bottom: 10px; font-size: 0.85rem; font-weight: 700; flex-wrap: wrap; }
.se-paste-summary span { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; }
.se-paste-ok { color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; }
.se-paste-err { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
.se-paste-err-list { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 12px; max-height: 160px; overflow-y: auto; }
.se-paste-err-row { display: flex; gap: 12px; font-size: 0.78rem; padding: 3px 0; border-bottom: 1px solid #fecaca; }
.se-paste-err-row:last-child { border-bottom: none; }
.se-paste-err-imei { font-family: ui-monospace, monospace; font-weight: 600; color: #7f1d1d; width: 160px; flex-shrink: 0; }
.se-paste-err-reason { color: #991b1b; }

.se-tbl-scroll {
    flex: 1; min-height: 0;
    overflow-x: auto; overflow-y: auto;
    max-height: calc(100vh - 380px);
}
.se-tbl-scroll .se-tbl thead th {
    position: sticky; top: 0; z-index: 2;
    box-shadow: 0 1px 0 #e2e8f0;
}
.se-tbl-wrap { overflow-x: auto; }
.se-subtotal-bar {
    display: flex; justify-content: flex-end; align-items: center; gap: 12px;
    padding: 10px 18px; background: #f8fafc;
    border-top: 2px solid #e2e8f0; flex-shrink: 0;
}
.se-subtotal-bar .lbl {
    font-size: 0.72rem; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.05em;
}
.se-subtotal-bar .val { font-weight: 800; color: #4338ca; font-size: 0.95rem; white-space: nowrap; }
.se-tbl { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.se-tbl th {
    padding: 10px 12px; font-size: 0.68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.06em; color: #64748b;
    background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
.se-tbl td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.se-tbl tbody tr { transition: background .1s; }
.se-tbl tbody tr:hover { background: #f8faff; }
.se-tbl tr.deleted-row { opacity: 0.4; background: #fff5f5 !important; }
.se-tbl tr.deleted-row td { text-decoration: line-through; }
.se-tbl tr.new-row { background: #f0fdf4; }

.se-row-num { color: #94a3b8; font-weight: 700; font-size: 0.8rem; width: 32px; }
.se-item-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.35; }
.se-item-sku { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }
.se-imei-badge {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
    font-size: 0.68rem; font-weight: 700; padding: 3px 9px; border-radius: 6px;
}
.se-imei-ok { color: #16a34a; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25); }
.se-imei-warn {
    color: #d97706; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.28);
    text-decoration: none; transition: background .15s;
}
.se-imei-warn:hover { background: rgba(245,158,11,0.18); color: #b45309; }

.se-cell-input {
    width: 100%; max-width: 88px; padding: 7px 8px;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.85rem; font-weight: 700; color: #1e293b;
    background: #fff; text-align: center; outline: none;
}
.se-cell-input.price { max-width: 100px; text-align: right; margin-left: auto; display: block; }
.se-cell-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.1); }
.se-row-total { font-weight: 800; color: #4338ca; white-space: nowrap; }

.se-btn-remove {
    width: 30px; height: 30px; border-radius: 8px; border: none;
    background: transparent; color: #cbd5e1; cursor: pointer;
    font-size: 1.1rem; line-height: 1; transition: all .15s;
    display: inline-flex; align-items: center; justify-content: center;
}
.se-btn-remove:hover { background: #fef2f2; color: #dc2626; }

.se-add-row {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px; cursor: pointer;
    border-top: 2px dashed #c7d2fe; color: #64748b;
    font-size: 0.82rem; font-weight: 700; background: #fff;
    transition: all .15s;
}
.se-add-row:hover { background: #f5f7ff; color: #6366f1; border-top-color: #6366f1; }
.se-add-row-icon {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(99,102,241,0.12); color: #6366f1;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
}

.se-autocomplete {
    position: fixed;
    background: #fff; border: 1.5px solid #e0e7ff; border-radius: 10px;
    z-index: 9999; box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    max-height: 220px; overflow-y: auto; min-width: 280px;
}
.se-autocomplete-item {
    padding: 10px 14px; cursor: pointer; font-size: 0.82rem;
    border-bottom: 1px solid #f1f5f9; transition: background .1s;
}
.se-autocomplete-item:hover { background: #f0f4ff; }
.se-autocomplete-item:last-child { border-bottom: none; }

.new-row-flash { animation: seRowFlash .65s ease-out; }
@keyframes seRowFlash { 0% { background: #dbeafe; } 100% { background: #f0fdf4; } }

@media (max-width: 767px) {
    .se-topbar { padding: 12px 14px; }
    .se-topbar-actions { width: 100%; justify-content: flex-end; }
    .se-scan { padding: 12px 14px; }
}
</style>

<?php
$statusClass = match ($editSale['status'] ?? '') {
    'paid'      => 'se-status-paid',
    'partial'   => 'se-status-partial',
    'confirmed' => 'se-status-confirmed',
    default     => 'se-status-default',
};
$itemCount = count($editSale['items']);
?>

<div class="se-wrap">
<form method="POST" action="?page=sales&action=update&id=<?= $editSale['id'] ?>" id="editSaleForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="sale_edit_nonce" value="<?= htmlspecialchars($saleEditNonce ?? '') ?>">
    <input type="hidden" name="id" value="<?= $editSale['id'] ?>">
    <input type="hidden" name="print_after_save" id="printAfterSave" value="0">
    <input type="hidden" name="print_mode" id="editPrintMode" value="0">

    <div class="se-topbar">
        <a href="?page=sales&action=detail&id=<?= $editSale['id'] ?>" class="se-back" title="Back to invoice">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="se-topbar-title">
            <h1>
                <span>Edit</span>
                <span class="se-inv-no"><?= htmlspecialchars($editSale['invoice_no']) ?></span>
                <span class="se-status <?= $statusClass ?>"><?= ucfirst($editSale['status']) ?></span>
            </h1>
        </div>
        <div class="se-topbar-actions">
            <a href="?page=sales&action=detail&id=<?= $editSale['id'] ?>" class="se-btn se-btn-ghost">Cancel</a>
            <button type="submit" class="se-btn se-btn-primary" data-print-mode="0">
                <i class="bi bi-check-lg"></i> Save
            </button>
            <button type="submit" class="se-btn se-btn-outline" data-print-mode="1">
                <i class="bi bi-printer"></i> Save &amp; Print
            </button>
            <button type="submit" class="se-btn se-btn-thermal" data-print-mode="2">
                <i class="bi bi-receipt"></i> Thermal
            </button>
        </div>
    </div>

    <div class="se-grid">
        <!-- Sidebar: invoice details -->
        <aside>
            <div class="se-panel">
                <div class="se-panel-head"><i class="bi bi-sliders"></i> Invoice Details</div>
                <div class="se-panel-body">
                    <div class="se-field">
                        <label class="se-label" for="editDate">Invoice Date</label>
                        <input type="date" name="date" id="editDate" class="se-input" required
                               value="<?= htmlspecialchars($editSale['date']) ?>">
                    </div>

                    <div class="se-field">
                        <label class="se-label">Customer</label>
                        <div class="se-info-chip">
                            <i class="bi bi-person-circle"></i>
                            <div>
                                <div class="name"><?= htmlspecialchars($editSale['party_name']) ?></div>
                                <?php if ($editSale['party_phone']): ?>
                                <div class="sub"><?= htmlspecialchars($editSale['party_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="discount" id="editDiscount"
                           value="<?= number_format($editSale['discount'], DECIMAL_PLACES, '.', '') ?>">

                    <div class="se-field">
                        <div class="se-totals">
                            <div class="se-totals-row grand">
                                <span class="lbl">Grand Total</span>
                                <span class="val" id="newGrandTotal"><?= editMoney($editSale['grand_total']) ?></span>
                            </div>
                            <?php if ((float)$editSale['paid_amount'] > 0): ?>
                            <div class="se-totals-row">
                                <span class="lbl" style="text-transform:none;font-size:0.78rem;color:#64748b;">Already Paid</span>
                                <span class="val paid"><?= editMoney($editSale['paid_amount']) ?></span>
                            </div>
                            <div class="se-totals-row">
                                <span class="lbl" style="text-transform:none;font-size:0.78rem;color:#64748b;">Balance Due</span>
                                <span class="val balance" id="newBalance"><?= editMoney($editSale['balance']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="se-field">
                        <label class="se-label" for="editNotes">Notes</label>
                        <textarea name="notes" id="editNotes" class="se-input" rows="3"
                                  placeholder="Optional notes…" style="resize:vertical;min-height:72px;"><?= htmlspecialchars($editSale['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main: items + scan -->
        <section class="se-panel se-items-panel">
            <div class="se-items-head">
                <span class="se-items-head-title"><i class="bi bi-box-seam"></i> Line Items</span>
                <span class="se-items-hint"><?= $itemCount ?> line<?= $itemCount !== 1 ? 's' : '' ?> · scan to add</span>
            </div>

            <div class="se-scan">
                <div class="se-scan-wrap">
                    <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    <input type="text" class="se-scan-input" id="editImeiScanBar"
                           placeholder="Scan IMEI — auto-adds item with price" autocomplete="off"
                           aria-describedby="editScanMsg">
                </div>
                <button type="button" class="se-btn-paste" id="btnEditScanPaste">
                    <i class="bi bi-clipboard-plus"></i> Paste IMEIs
                </button>
                <div class="se-scan-meta">
                    <span class="se-scan-msg" id="editScanMsg" role="status" aria-live="polite"></span>
                    <span class="se-scan-count" id="editScanCount">
                        <span class="se-scan-count-num">0</span> new scanned
                    </span>
                </div>
            </div>

            <div class="se-tbl-scroll" id="editItemsScroll">
                <table class="se-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th class="text-center" style="width:90px;">Qty</th>
                            <th class="text-end" style="width:110px;">Price</th>
                            <th class="text-end" style="width:120px;">Total</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody">
                        <?php foreach ($editSale['items'] as $i => $item):
                            $lineImeis = [];
                            if (!empty($item['imei_list'])) {
                                $lineImeis = array_values(array_filter(array_map('trim', explode('||', $item['imei_list']))));
                            }
                            $imeiCount = count($lineImeis);
                            $needsImei = !empty($item['has_imei']) && $imeiCount < (int)$item['quantity'];
                        ?>
                        <tr id="row_<?= $item['id'] ?>">
                            <td class="se-row-num"><?= $i + 1 ?></td>
                            <td>
                                <div class="se-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if ($item['sku']): ?>
                                <div class="se-item-sku"><?= htmlspecialchars((string) $item['sku']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['has_imei'])): ?>
                                    <?php if ($needsImei): ?>
                                    <a href="?page=sales&action=scanItemImeis&id=<?= $editSale['id'] ?>&sale_item_id=<?= $item['id'] ?>"
                                       class="se-imei-badge se-imei-warn">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Scan IMEIs (<?= $imeiCount ?>/<?= $item['quantity'] ?>)
                                    </a>
                                    <?php else: ?>
                                    <span class="se-imei-badge se-imei-ok">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <?= $imeiCount ?> IMEI<?= $imeiCount !== 1 ? 's' : '' ?>
                                    </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <input type="hidden" name="items[<?= $item['id'] ?>][sale_item_id]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="items[<?= $item['id'] ?>][deleted]" id="del_<?= $item['id'] ?>" value="0">
                            </td>
                            <td class="text-center">
                                <input type="number" name="items[<?= $item['id'] ?>][quantity]"
                                       value="<?= $item['quantity'] ?>" min="1"
                                       class="se-cell-input edit-qty" data-row="<?= $item['id'] ?>">
                            </td>
                            <td class="text-end">
                                <input type="number" name="items[<?= $item['id'] ?>][unit_price]"
                                       value="<?= number_format((float)$item['unit_price'], 3, '.', '') ?>"
                                       step="0.001" min="0"
                                       class="se-cell-input price edit-price" data-row="<?= $item['id'] ?>">
                            </td>
                            <td class="text-end se-row-total row-total" id="rowTotal_<?= $item['id'] ?>"><?= editMoney($item['total']) ?></td>
                            <td class="text-center">
                                <button type="button" class="se-btn-remove btn-del-row" title="Remove item"
                                        data-row-id="<?= $item['id'] ?>">×</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="se-subtotal-bar">
                <span class="lbl">Subtotal</span>
                <span class="val" id="editSubtotal"><?= editMoney($editSale['subtotal']) ?></span>
            </div>

            <div class="se-add-row" id="btnAddNewItemRow" role="button" tabindex="0">
                <span class="se-add-row-icon"><i class="bi bi-plus-lg"></i></span>
                Add item manually
            </div>
        </section>
    </div>
</form>
</div>

<!-- Bulk paste IMEIs modal -->
<div class="modal fade" id="editSalePasteModal" tabindex="-1" aria-hidden="true">
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
                    Paste IMEIs below — one per line, or separated by commas/semicolons. Each IMEI is looked up and added as a new line item.
                </p>
                <div style="font-size:0.78rem;color:#475569;margin-bottom:8px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span><i class="bi bi-stickies me-1"></i> Lines pasted: <strong id="editSpLineCount">0</strong></span>
                    <span><i class="bi bi-upc-scan me-1"></i> Already on invoice: <strong id="editSpOnInvoiceCount">0</strong></span>
                </div>
                <textarea id="editSpTextarea" class="se-paste-textarea"
                          placeholder="Paste IMEIs here (one per line)...&#10;&#10;354720736995668&#10;354720736995675"></textarea>
                <div id="editSpPreview" style="margin-top:12px;display:none;"></div>
                <div id="editSpProgress" style="margin-top:10px;display:none;font-size:0.82rem;font-weight:600;color:#6366f1;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="editSpValidateBtn"
                        style="background:#0ea5e9;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="editSpConfirmBtn" disabled
                        style="background:#6366f1;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-cloud-upload me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
<?php
$editExistingImeis = [];
foreach ($editSale['items'] as $ei) {
    if (!empty($ei['imei_list'])) {
        foreach (explode('||', $ei['imei_list']) as $im) {
            $im = trim($im);
            if ($im !== '') $editExistingImeis[] = $im;
        }
    }
}
?>
var paidAmount  = <?= (float)$editSale['paid_amount'] ?>;
var warehouseId = <?= (int)$editSale['warehouse_id'] ?>;
var currency    = '<?= APP_CURRENCY ?>';
var newRowCount = 0;
var searchTimers = {};
var newItemImeiData = {};
var existingInvoiceImeis = <?= json_encode($editExistingImeis, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
var editSpValidList = [];
var editBulkScanQueue = [];
var editBulkScanRunning = false;
var editBulkScanStats = { saved: 0, skipped: [] };

function renumberItemRows() {
    var n = 1;
    document.querySelectorAll('#itemsTbody tr').forEach(function(tr) {
        var numCell = tr.querySelector('.se-row-num');
        if (!numCell) return;
        if (tr.classList.contains('deleted-row')) {
            numCell.textContent = '—';
            return;
        }
        numCell.textContent = String(n++);
    });
}

function positionEditDropdown(input, drop) {
    if (!input || !drop) return;
    var rect = input.getBoundingClientRect();
    drop.style.top = (rect.bottom + 4) + 'px';
    drop.style.left = rect.left + 'px';
    drop.style.width = Math.max(rect.width, 320) + 'px';
}

function scrollEditRowIntoView(tr) {
    if (!tr) return;
    var wrap = document.getElementById('editItemsScroll');
    if (!wrap) {
        try { tr.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
        return;
    }
    var trRect = tr.getBoundingClientRect();
    var wrapRect = wrap.getBoundingClientRect();
    if (trRect.top < wrapRect.top + 8 || trRect.bottom > wrapRect.bottom - 8) {
        var delta = (trRect.top + trRect.height / 2) - (wrapRect.top + wrapRect.height / 2);
        wrap.scrollBy({ top: delta, behavior: 'smooth' });
    }
}

function toggleDelete(id, btn) {
    const tr  = document.getElementById('row_' + id);
    const del = document.getElementById('del_' + id);
    if (del.value === '0') {
        del.value = '1';
        tr.classList.add('deleted-row');
        btn.innerHTML = '↩';
        btn.title = 'Restore item';
        btn.style.color = '#059669';
        tr.querySelectorAll('input[type=number]').forEach(i => i.disabled = true);
    } else {
        del.value = '0';
        tr.classList.remove('deleted-row');
        btn.innerHTML = '×';
        btn.title = 'Remove item';
        btn.style.color = '';
        tr.querySelectorAll('input[type=number]').forEach(i => i.disabled = false);
    }
    recalcItems();
    renumberItemRows();
}

function addNewItemRow(prefill) {
    newRowCount++;
    const n = newRowCount;
    newItemImeiData[n] = prefill && prefill.imeis ? prefill.imeis.slice() : [];
    const tr = document.createElement('tr');
    tr.id = 'newrow_' + n;
    tr.className = 'new-row';
    tr.dataset.newRow = String(n);
    const imeiBadge = newItemImeiData[n].length
        ? '<span class="se-imei-badge se-imei-ok" id="newImeiBadge_' + n + '"><i class="bi bi-check-circle-fill"></i> ' + newItemImeiData[n].length + ' IMEI' + (newItemImeiData[n].length !== 1 ? 's' : '') + '</span>'
        : '<span class="se-imei-badge se-imei-ok" id="newImeiBadge_' + n + '" style="display:none;"></span>';
    tr.innerHTML =
        '<td class="se-row-num">+</td>' +
        '<td style="position:relative;">' +
            '<input type="text" class="se-cell-input new-item-search" style="max-width:none;text-align:left;font-weight:500;"' +
                ' id="newSearch_' + n + '" placeholder="Search item…" autocomplete="off">' +
            '<input type="hidden" name="new_items[' + n + '][item_id]" id="newItemId_' + n + '" value="' + (prefill ? prefill.itemId : '') + '">' +
            '<input type="hidden" name="new_items[' + n + '][imeis]" id="newImeis_' + n + '" value="' + newItemImeiData[n].join('\\n') + '">' +
            '<input type="hidden" id="newHasImei_' + n + '" value="' + (prefill && prefill.hasImei ? '1' : '0') + '">' +
            '<input type="hidden" id="newImeiOpt_' + n + '" value="0">' +
            '<div id="newItemLabel_' + n + '" class="se-item-name" style="' + (prefill ? '' : 'display:none;') + '">' + (prefill ? prefill.name : '') + '</div>' +
            imeiBadge +
            '<div class="se-autocomplete" id="newDrop_' + n + '" style="display:none;"></div>' +
        '</td>' +
        '<td class="text-center">' +
            '<input type="number" name="new_items[' + n + '][quantity]" id="newQty_' + n + '"' +
                ' value="' + (prefill ? (newItemImeiData[n].length || 1) : 1) + '" min="1" class="se-cell-input new-qty">' +
        '</td>' +
        '<td class="text-end">' +
            '<input type="number" name="new_items[' + n + '][unit_price]" id="newPrice_' + n + '"' +
                ' value="' + (prefill ? prefill.price : '') + '" step="0.001" min="0" class="se-cell-input price new-price">' +
        '</td>' +
        '<td class="text-end se-row-total new-total" id="newTotal_' + n + '">—</td>' +
        '<td class="text-center">' +
            '<button type="button" class="se-btn-remove" title="Remove" data-new-row="' + n + '">×</button>' +
        '</td>';
    document.getElementById('itemsTbody').appendChild(tr);
    if (prefill) {
        document.getElementById('newSearch_' + n).style.display = 'none';
        recalcItems();
        renderEditScanCount();
    } else {
        document.getElementById('newSearch_' + n).focus();
    }
    renumberItemRows();
    scrollEditRowIntoView(tr);
    return n;
}

function updateNewImeiBadge(n) {
    const badge = document.getElementById('newImeiBadge_' + n);
    const list  = newItemImeiData[n] || [];
    if (!badge) return;
    if (!list.length) { badge.style.display = 'none'; return; }
    badge.style.display = 'inline-flex';
    badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + list.length + ' IMEI' + (list.length !== 1 ? 's' : '');
}

function syncNewRowImeis(n) {
    const list = newItemImeiData[n] || [];
    const el = document.getElementById('newImeis_' + n);
    if (el) el.value = list.join('\n');
    const qtyEl = document.getElementById('newQty_' + n);
    if (qtyEl && list.length > 0) qtyEl.value = list.length;
    updateNewImeiBadge(n);
    recalcItems();
    renderEditScanCount();
    scrollEditRowIntoView(document.getElementById('newrow_' + n));
}

function removeNewRow(n) {
    document.getElementById('newrow_' + n)?.remove();
    delete newItemImeiData[n];
    renderEditScanCount();
    recalcItems();
    renumberItemRows();
}

function searchNewItem(n, q) {
    clearTimeout(searchTimers['new_' + n]);
    const drop = document.getElementById('newDrop_' + n);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers['new_' + n] = setTimeout(() => {
        fetch('?page=sales&action=searchItems&q=' + encodeURIComponent(q) + '&warehouse_id=' + warehouseId)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = items.map(it =>
                    '<div class="se-autocomplete-item" data-n="' + n + '" data-id="' + it.id + '"' +
                    ' data-name="' + it.name.replace(/"/g, '&quot;') + '"' +
                    ' data-price="' + parseFloat(it.sale_price || 0).toFixed(3) + '"' +
                    ' data-has-imei="' + (it.has_imei ? 1 : 0) + '">' +
                    '<strong>' + it.name + '</strong>' +
                    (it.sku ? ' <small style="color:#94a3b8;">· ' + it.sku + '</small>' : '') +
                    '<small style="float:right;color:#6366f1;font-weight:700;">' + currency + ' ' + parseFloat(it.sale_price || 0).toFixed(3) + '</small>' +
                    '<br><small style="color:#94a3b8;">Stock: ' + (it.current_stock ?? it.stock ?? 0) + '</small>' +
                    '</div>'
                ).join('');
                positionEditDropdown(document.getElementById('newSearch_' + n), drop);
                drop.style.display = 'block';
            });
    }, 250);
}

function selectNewItem(n, id, name, price, hasImei) {
    // Same model already added as another new row → merge into it instead of
    // keeping a duplicate line (mirrors the scan-merge behaviour).
    const dupN = findNewRowByItemId(id, n);
    if (dupN !== null) {
        if (hasImei) {
            const merged = (newItemImeiData[dupN] || []).concat(newItemImeiData[n] || []);
            newItemImeiData[dupN] = merged.filter(function(v, i) { return merged.indexOf(v) === i; });
            syncNewRowImeis(dupN);
        } else {
            const dupQtyEl = document.getElementById('newQty_' + dupN);
            const curQtyEl = document.getElementById('newQty_' + n);
            const addQty   = parseInt(curQtyEl && curQtyEl.value, 10) || 1;
            if (dupQtyEl) dupQtyEl.value = (parseInt(dupQtyEl.value, 10) || 0) + addQty;
        }
        removeNewRow(n);
        recalcItems();
        flashNewRow(dupN);
        setEditScanMsg('Merged into existing line', 'ok');
        return;
    }

    document.getElementById('newItemId_' + n).value  = id;
    document.getElementById('newSearch_' + n).value  = name;
    document.getElementById('newPrice_' + n).value   = parseFloat(price).toFixed(3);
    document.getElementById('newHasImei_' + n).value = hasImei ? '1' : '0';
    const label = document.getElementById('newItemLabel_' + n);
    if (label) { label.textContent = name; label.style.display = ''; }
    document.getElementById('newDrop_' + n).style.display = 'none';
    recalcItems();
}

function hideNewDrop(n) {
    const d = document.getElementById('newDrop_' + n);
    if (d) d.style.display = 'none';
}

function recalcItems() {
    var newSubtotal = 0;

    document.querySelectorAll('.edit-qty').forEach(function(qEl) {
        const rowId    = qEl.getAttribute('data-row');
        const delInput = document.getElementById('del_' + rowId);
        if (delInput && delInput.value === '1') return;
        const priceEl  = document.querySelector('.edit-price[data-row="' + rowId + '"]');
        const qty      = parseFloat(qEl.value) || 0;
        const price    = parseFloat(priceEl?.value) || 0;
        const rowTotal = qty * price;
        newSubtotal   += rowTotal;
        const totalEl  = document.getElementById('rowTotal_' + rowId);
        if (totalEl) totalEl.textContent = currency + ' ' + rowTotal.toFixed(3);
    });

    document.querySelectorAll('.new-qty').forEach(function(qEl) {
        const n     = qEl.closest('tr').id.replace('newrow_', '');
        const price = parseFloat(document.getElementById('newPrice_' + n)?.value) || 0;
        const qty   = parseFloat(qEl.value) || 0;
        const total = qty * price;
        newSubtotal += total;
        const totalEl = document.getElementById('newTotal_' + n);
        if (totalEl) totalEl.textContent = total > 0 ? currency + ' ' + total.toFixed(3) : '—';
    });

    document.getElementById('editSubtotal').textContent = currency + ' ' + newSubtotal.toFixed(3);
    window._subtotal = newSubtotal;
    recalcTotal();
}

function recalcTotal() {
    const disc     = parseFloat(document.getElementById('editDiscount').value) || 0;
    const subtotal = window._subtotal || 0;
    const newTotal = Math.max(0, subtotal - disc);
    const newBal   = Math.max(0, newTotal - paidAmount);
    document.getElementById('newGrandTotal').textContent = currency + ' ' + newTotal.toFixed(3);
    const balEl = document.getElementById('newBalance');
    if (balEl) balEl.textContent = currency + ' ' + newBal.toFixed(3);
}

document.addEventListener('DOMContentLoaded', function() {
    recalcItems();
    renumberItemRows();

    document.querySelectorAll('.edit-qty, .edit-price').forEach(function(el) {
        el.addEventListener('input', recalcItems);
    });

    document.getElementById('itemsTbody').addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-del-row');
        if (delBtn) {
            toggleDelete(parseInt(delBtn.dataset.rowId, 10), delBtn);
            return;
        }
        const newDel = e.target.closest('.se-btn-remove[data-new-row]');
        if (newDel) removeNewRow(parseInt(newDel.dataset.newRow, 10));
    });

    document.getElementById('itemsTbody').addEventListener('input', function(e) {
        if (e.target.classList.contains('new-item-search')) {
            const n = e.target.id.replace('newSearch_', '');
            searchNewItem(n, e.target.value);
        }
        if (e.target.classList.contains('new-qty') || e.target.classList.contains('new-price')) {
            recalcItems();
        }
    });

    document.getElementById('itemsTbody').addEventListener('blur', function(e) {
        if (e.target.classList.contains('new-item-search')) {
            const n = e.target.id.replace('newSearch_', '');
            setTimeout(function() { hideNewDrop(n); }, 200);
        }
    }, true);

    document.getElementById('itemsTbody').addEventListener('mousedown', function(e) {
        const item = e.target.closest('.se-autocomplete-item');
        if (!item) return;
        e.preventDefault();
        selectNewItem(
            parseInt(item.dataset.n, 10),
            parseInt(item.dataset.id, 10),
            item.dataset.name,
            item.dataset.price,
            item.dataset.hasImei === '1'
        );
    });

    document.getElementById('btnAddNewItemRow').addEventListener('click', function() { addNewItemRow(); });
    document.getElementById('btnAddNewItemRow').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); addNewItemRow(); }
    });

    document.getElementById('editImeiScanBar').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); scanEditImeiToRow(); }
    });

    document.getElementById('btnEditScanPaste').addEventListener('click', openEditSalePasteModal);
    document.getElementById('editSpTextarea').addEventListener('input', updateEditSalePasteLineCount);
    document.getElementById('editSpValidateBtn').addEventListener('click', validateEditSalePaste);
    document.getElementById('editSpConfirmBtn').addEventListener('click', confirmEditSalePaste);

    document.querySelectorAll('[data-print-mode]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const mode = btn.getAttribute('data-print-mode');
            document.getElementById('printAfterSave').value = mode === '0' ? '0' : '1';
            document.getElementById('editPrintMode').value = mode;
        });
    });

    document.getElementById('editImeiScanBar').focus();
});

function normalizeScanImei(raw) {
    if (!raw) return '';
    return String(raw).replace(/[\r\n\t]/g, '').trim().toUpperCase().replace(/\s+/g, '');
}

function getAllNewScannedImeis() {
    const all = [];
    Object.keys(newItemImeiData).forEach(function(n) {
        (newItemImeiData[n] || []).forEach(function(im) { all.push(im); });
    });
    return all;
}

function getAllInvoiceImeisForScan() {
    return existingInvoiceImeis.concat(getAllNewScannedImeis());
}

function renderEditScanCount() {
    const el = document.getElementById('editScanCount');
    if (!el) return;
    const n = getAllNewScannedImeis().length;
    el.innerHTML = '<span class="se-scan-count-num">' + n + '</span> new scanned';
}

let _editScanMsgTimer = null;

function setEditScanMsg(text, type) {
    const msg = document.getElementById('editScanMsg');
    if (!msg) return;
    if (_editScanMsgTimer) { clearTimeout(_editScanMsgTimer); _editScanMsgTimer = null; }
    msg.className = 'se-scan-msg ' + (type || '');
    msg.textContent = text || '';
    if (text) {
        _editScanMsgTimer = setTimeout(function() {
            msg.textContent = '';
            msg.className = 'se-scan-msg';
            _editScanMsgTimer = null;
        }, type === 'err' ? 3500 : 1600);
    }
}

function flashNewRow(n) {
    const tr = document.getElementById('newrow_' + n);
    if (!tr) return;
    tr.classList.remove('new-row-flash');
    void tr.offsetWidth;
    tr.classList.add('new-row-flash');
    scrollEditRowIntoView(tr);
}

function findNewRowByItemId(itemId, excludeN) {
    let found = null;
    document.querySelectorAll('#itemsTbody tr[data-new-row]').forEach(function(tr) {
        const n = tr.dataset.newRow;
        if (excludeN != null && parseInt(n, 10) === parseInt(excludeN, 10)) return;
        const idEl = document.getElementById('newItemId_' + n);
        if (idEl && parseInt(idEl.value, 10) === itemId) found = parseInt(n, 10);
    });
    return found;
}

function findEmptyNewRow() {
    let empty = null;
    document.querySelectorAll('#itemsTbody tr[data-new-row]').forEach(function(tr) {
        const n = tr.dataset.newRow;
        const idEl = document.getElementById('newItemId_' + n);
        const searchEl = document.getElementById('newSearch_' + n);
        const hasDraft = searchEl && searchEl.value.trim() !== '';
        if (!empty && idEl && !idEl.value && !hasDraft) empty = parseInt(n, 10);
    });
    return empty;
}

function applyEditScannedImei(data, imei) {
    if (!data.found) {
        if (data.accepted) return { ok: false, msg: 'Not registered in system' };
        return { ok: false, msg: data.message || 'Not found' };
    }
    if (getAllInvoiceImeisForScan().includes(imei)) {
        return { ok: false, msg: 'Already on this invoice' };
    }

    let targetN = findNewRowByItemId(data.item_id);
    let affectedN = null;

    if (targetN !== null) {
        if (!newItemImeiData[targetN]) newItemImeiData[targetN] = [];
        newItemImeiData[targetN].push(imei);
        syncNewRowImeis(targetN);
        affectedN = targetN;
    } else {
        let emptyN = findEmptyNewRow();
        if (emptyN === null) {
            emptyN = addNewItemRow({
                itemId: data.item_id,
                name: data.item_name,
                price: parseFloat(data.sale_price).toFixed(3),
                hasImei: data.has_imei,
                imeis: [imei]
            });
            document.getElementById('newSearch_' + emptyN).style.display = 'none';
            const label = document.getElementById('newItemLabel_' + emptyN);
            if (label) label.style.display = '';
            affectedN = emptyN;
        } else {
            document.getElementById('newItemId_' + emptyN).value = data.item_id;
            document.getElementById('newHasImei_' + emptyN).value = data.has_imei ? '1' : '0';
            document.getElementById('newSearch_' + emptyN).value = data.item_name;
            document.getElementById('newSearch_' + emptyN).style.display = 'none';
            document.getElementById('newPrice_' + emptyN).value = parseFloat(data.sale_price).toFixed(3);
            const label = document.getElementById('newItemLabel_' + emptyN);
            if (label) { label.textContent = data.item_name; label.style.display = ''; }
            newItemImeiData[emptyN] = [imei];
            syncNewRowImeis(emptyN);
            affectedN = emptyN;
        }
    }

    return { ok: true, n: affectedN };
}

function lookupAndApplyEditImei(imei) {
    return fetch('?page=imei&action=lookupImei&imei=' + encodeURIComponent(imei))
        .then(function(r) { return r.json(); })
        .then(function(data) { return applyEditScannedImei(data, imei); });
}

function scanEditImeiToRow() {
    const input = document.getElementById('editImeiScanBar');
    const imei  = normalizeScanImei(input.value);
    if (!imei) return;
    input.value = '';
    input.focus();
    setEditScanMsg('Looking up…', '');

    lookupAndApplyEditImei(imei)
        .then(function(result) {
            if (!result.ok) {
                setEditScanMsg(result.msg, 'err');
                return;
            }
            setEditScanMsg('Added', 'ok');
            if (result.n) flashNewRow(result.n);
            renderEditScanCount();
        })
        .catch(function() { setEditScanMsg('Network error', 'err'); });
}

function openEditSalePasteModal() {
    document.getElementById('editSpTextarea').value = '';
    document.getElementById('editSpLineCount').textContent = '0';
    document.getElementById('editSpOnInvoiceCount').textContent = String(getAllInvoiceImeisForScan().length);
    document.getElementById('editSpPreview').style.display = 'none';
    document.getElementById('editSpPreview').innerHTML = '';
    document.getElementById('editSpProgress').style.display = 'none';
    document.getElementById('editSpProgress').textContent = '';
    document.getElementById('editSpConfirmBtn').disabled = true;
    document.getElementById('editSpConfirmBtn').innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    document.getElementById('editSpValidateBtn').disabled = false;
    editSpValidList = [];

    var modal = new bootstrap.Modal(document.getElementById('editSalePasteModal'));
    modal.show();
    setTimeout(function() { document.getElementById('editSpTextarea').focus(); }, 350);
}

function updateEditSalePasteLineCount() {
    var raw = document.getElementById('editSpTextarea').value;
    var lines = raw.split(/[\r\n,;]+/).filter(function(s) { return s.trim().length > 0; });
    document.getElementById('editSpLineCount').textContent = lines.length;
}

function validateEditSalePaste() {
    var raw = document.getElementById('editSpTextarea').value;
    var lines = raw.split(/[\r\n,;]+/);
    var valid = [];
    var errors = [];
    var seen = {};
    var onInvoice = getAllInvoiceImeisForScan();

    lines.forEach(function(line) {
        var imei = normalizeScanImei(line);
        if (!imei) return;

        if (!/^\d{15,18}$/.test(imei)) {
            errors.push({ imei: imei, reason: 'Must be 15–18 digits (got ' + imei.length + ')' });
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

    editSpValidList = valid;

    var html = '<div class="se-paste-summary">';
    html += '<span class="se-paste-ok"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' valid</span>';
    if (errors.length > 0) {
        html += '<span class="se-paste-err"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="se-paste-err-list">';
        errors.forEach(function(e) {
            html += '<div class="se-paste-err-row"><span class="se-paste-err-imei">' + e.imei + '</span><span class="se-paste-err-reason">' + e.reason + '</span></div>';
        });
        html += '</div>';
    }

    var preview = document.getElementById('editSpPreview');
    preview.innerHTML = html;
    preview.style.display = 'block';
    document.getElementById('editSpConfirmBtn').disabled = (valid.length === 0);
}

function confirmEditSalePaste() {
    if (editSpValidList.length === 0) return;

    var btn = document.getElementById('editSpConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';
    document.getElementById('editSpValidateBtn').disabled = true;

    editBulkScanQueue = editSpValidList.slice();
    editBulkScanStats = { saved: 0, skipped: [] };
    document.getElementById('editSpProgress').style.display = 'block';
    updateEditBulkPasteProgress();
    processEditBulkScanQueue();
}

function updateEditBulkPasteProgress() {
    var total = editSpValidList.length;
    var done = editBulkScanStats.saved + editBulkScanStats.skipped.length;
    var pending = editBulkScanQueue.length + (editBulkScanRunning ? 1 : 0);
    document.getElementById('editSpProgress').textContent =
        'Processing ' + done + ' / ' + total + ' · ' + pending + ' remaining · ' + editBulkScanStats.saved + ' added';
}

function processEditBulkScanQueue() {
    if (editBulkScanRunning) return;

    if (editBulkScanQueue.length === 0) {
        var btn = document.getElementById('editSpConfirmBtn');
        if (btn.disabled && editSpValidList.length > 0) {
            finishEditBulkPaste();
        }
        return;
    }

    editBulkScanRunning = true;
    var imei = editBulkScanQueue.shift();
    updateEditBulkPasteProgress();

    lookupAndApplyEditImei(imei)
        .then(function(result) {
            if (result.ok) {
                editBulkScanStats.saved++;
                renderEditScanCount();
                if (result.n) flashNewRow(result.n);
            } else {
                editBulkScanStats.skipped.push({ imei: imei, reason: result.msg });
            }
        })
        .catch(function() {
            editBulkScanStats.skipped.push({ imei: imei, reason: 'Network error' });
        })
        .finally(function() {
            editBulkScanRunning = false;
            updateEditBulkPasteProgress();
            processEditBulkScanQueue();
        });
}

function finishEditBulkPaste() {
    recalcItems();

    editSpValidList = [];
    editBulkScanQueue = [];

    var btn = document.getElementById('editSpConfirmBtn');
    var validateBtn = document.getElementById('editSpValidateBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    validateBtn.disabled = false;

    bootstrap.Modal.getInstance(document.getElementById('editSalePasteModal')).hide();

    var msg = editBulkScanStats.saved + ' IMEI(s) added';
    if (editBulkScanStats.skipped.length > 0) {
        msg += ' · ' + editBulkScanStats.skipped.length + ' skipped';
    }
    var scanMsg = document.getElementById('editScanMsg');
    if (_editScanMsgTimer) { clearTimeout(_editScanMsgTimer); _editScanMsgTimer = null; }
    scanMsg.className = 'se-scan-msg ok';
    scanMsg.textContent = msg;
    _editScanMsgTimer = setTimeout(function() {
        scanMsg.textContent = '';
        scanMsg.className = 'se-scan-msg';
        _editScanMsgTimer = null;
    }, 4000);

    editBulkScanStats = { saved: 0, skipped: [] };
}

document.getElementById('editSaleForm').addEventListener('submit', function(e) {
    let err = null;
    document.querySelectorAll('#itemsTbody tr[data-new-row]').forEach(function(tr) {
        const n = tr.dataset.newRow;
        const itemId = document.getElementById('newItemId_' + n)?.value;
        if (!itemId) return;
        const qty     = parseInt(document.getElementById('newQty_' + n)?.value, 10) || 0;
        const hasImei = document.getElementById('newHasImei_' + n)?.value === '1';
        const imeiOpt = document.getElementById('newImeiOpt_' + n)?.value === '1';
        const imeis   = (newItemImeiData[n] || []).length;
        if (qty <= 0) err = 'Quantity required for all new items.';
        if (hasImei && !imeiOpt && imeis !== qty) {
            const name = document.getElementById('newItemLabel_' + n)?.textContent || 'item';
            err = 'Item "' + name + '": must scan ' + qty + ' IMEIs (currently ' + imeis + ').';
        }
    });
    if (err) { e.preventDefault(); alert(err); }
});
</script>
