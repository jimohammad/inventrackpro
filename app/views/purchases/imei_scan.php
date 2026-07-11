<style>
.scan-wrap{max-width:780px;margin:0 auto;}
.scan-header{background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px;padding:18px 24px;margin-bottom:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.scan-header h1{font-size:1.05rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.scan-header .inv-ref{font-size:0.8rem;color:rgba(255,255,255,0.7);margin-top:2px;}
.item-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.item-tab{padding:8px 16px;border-radius:8px;border:2px solid #e0e7ff;background:#fff;cursor:pointer;font-size:0.82rem;font-weight:600;color:#64748b;transition:all 0.15s;text-align:center;}
.item-tab:hover{border-color:#6366f1;color:#6366f1;}
.item-tab.active{border-color:#6366f1;background:linear-gradient(135deg,#eff6ff,#e0e7ff);color:#4338ca;}
.item-tab .tab-count{display:block;font-size:0.75rem;margin-top:2px;}
.item-tab .tab-count.done{color:#10b981;}
.item-tab .tab-count.warn{color:#f59e0b;}
.scan-station{background:#fff;border-radius:12px;border:2px solid #e0e7ff;overflow:hidden;}
.scan-station-header{padding:14px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.scan-station-header .item-label{font-size:0.95rem;font-weight:700;color:#1e293b;}
.progress-info{display:flex;align-items:center;gap:12px;}
.progress-count{font-size:1.4rem;font-weight:800;color:#6366f1;}
.progress-count .need{font-size:0.85rem;font-weight:500;color:#94a3b8;}
.progress-bar-wrap{width:140px;height:8px;background:#e0e7ff;border-radius:99px;overflow:hidden;}
.progress-bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#6366f1,#3b82f6);transition:width 0.3s;}
.progress-bar-fill.done{background:linear-gradient(90deg,#10b981,#059669);}
.scan-input-wrap{padding:20px;}
.scan-input-label{font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.scan-input-label .dot{width:8px;height:8px;border-radius:50%;background:#10b981;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}
#scanInput{width:100%;padding:16px 20px;font-size:1.4rem;font-family:'JetBrains Mono',monospace;font-weight:700;letter-spacing:3px;border:2.5px solid #6366f1;border-radius:10px;outline:none;color:#1e293b;background:#fafbff;box-shadow:0 0 0 4px rgba(99,102,241,0.1);transition:all 0.15s;}
#scanInput:focus{border-color:#4338ca;box-shadow:0 0 0 5px rgba(99,102,241,0.15);}
#scanInput.flash-ok{background:#d1fae5;border-color:#10b981;box-shadow:0 0 0 5px rgba(16,185,129,0.15);}
#scanInput.flash-err{background:#fee2e2;border-color:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,0.15);}
#scanFeedback{min-height:32px;padding:6px 12px;border-radius:8px;font-size:0.88rem;font-weight:600;margin-top:10px;display:flex;align-items:center;gap:8px;transition:all 0.2s;}
#scanFeedback.ok{background:#d1fae5;color:#065f46;}
#scanFeedback.err{background:#fee2e2;color:#991b1b;}
#scanFeedback.idle{background:transparent;color:transparent;}
.scan-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;align-items:center;}
.btn-undo{padding:8px 18px;border-radius:8px;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.83rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-undo:hover{background:#fee2e2;}
.btn-undo:disabled{opacity:0.4;cursor:not-allowed;}
.btn-paste{padding:8px 18px;border-radius:8px;border:1.5px solid #0ea5e9;background:#fff;color:#0ea5e9;font-size:0.83rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-paste:hover{background:#0ea5e9;color:#fff;}
.btn-clear-all{padding:8px 18px;border-radius:8px;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.83rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-clear-all:hover{background:#fee2e2;}
.btn-clear-all:disabled{opacity:0.4;cursor:not-allowed;}
.btn-bulk-remove{padding:8px 18px;border-radius:8px;border:1.5px solid #f97316;background:#fff;color:#ea580c;font-size:0.83rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-bulk-remove:hover{background:#fff7ed;}
.btn-bulk-remove:disabled{opacity:0.4;cursor:not-allowed;}
.btn-copy-sm{padding:5px 12px;border-radius:7px;border:1.5px solid #10b981;background:#fff;color:#059669;font-size:0.78rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.btn-copy-sm:hover:not(:disabled){background:#ecfdf5;}
.btn-copy-sm:disabled{opacity:0.4;cursor:not-allowed;}
.copy-qty-wrap{display:inline-flex;align-items:center;gap:6px;padding:3px 8px 3px 10px;border-radius:8px;border:1.5px solid #a7f3d0;background:#ecfdf5;}
.copy-qty-label{font-size:0.72rem;font-weight:700;color:#047857;text-transform:uppercase;letter-spacing:0.3px;white-space:nowrap;}
.copy-qty-input{width:58px;padding:4px 6px;border:1.5px solid #10b981;border-radius:6px;font-size:0.85rem;font-weight:700;text-align:center;color:#065f46;background:#fff;outline:none;}
.copy-qty-input:focus{border-color:#059669;box-shadow:0 0 0 3px rgba(16,185,129,0.15);}
.copy-qty-max{font-size:0.7rem;color:#64748b;white-space:nowrap;}
.queue-badge{font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;display:none;align-items:center;gap:4px;}
.queue-badge.active{display:inline-flex;}
.imei-list-wrap{border-top:1px solid #e0e7ff;padding:16px 20px;max-height:340px;overflow-y:auto;}
.imei-list-header{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap;}
.imei-list-title{font-size:0.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0;display:flex;align-items:center;gap:8px;}
.imei-select-all{display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;user-select:none;}
.imei-select-all input[type=checkbox]{width:15px;height:15px;cursor:pointer;accent-color:#6366f1;}
.btn-delete-selected{padding:5px 12px;border-radius:7px;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.78rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.btn-delete-selected:hover:not(:disabled){background:#fee2e2;}
.btn-delete-selected:disabled{opacity:0.4;cursor:not-allowed;}
.imei-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 10px;border-radius:7px;margin-bottom:4px;background:#f8fafc;transition:background 0.2s,border-color 0.2s;}
.imei-row.is-selected{background:#fff1f2;border-color:#fecaca;}
.imei-row .imei-check{display:flex;align-items:center;flex-shrink:0;}
.imei-row .imei-check input[type=checkbox]{width:15px;height:15px;cursor:pointer;accent-color:#6366f1;}
.imei-row .imei-main{display:flex;align-items:center;gap:8px;flex:1;min-width:0;}
.imei-row.is-pending{background:#fffbeb;border:1px solid #fde68a;}
.imei-row.is-failed{background:#fef2f2;border:1px solid #fecaca;opacity:0.85;}
.imei-row.is-ok{background:#f0fdf4;border:1px solid #bbf7d0;}
.imei-row .imei-num{font-family:'JetBrains Mono',monospace;font-size:0.85rem;font-weight:600;color:#1e293b;}
.imei-row .imei-time{font-size:0.72rem;color:#94a3b8;}
.imei-row .imei-status{font-size:0.85rem;min-width:18px;text-align:center;}
.imei-row .del-imei{background:none;border:none;color:#fca5a5;cursor:pointer;font-size:1rem;padding:2px 6px;border-radius:5px;line-height:1;}
.imei-row .del-imei:hover{background:#fee2e2;color:#dc2626;}
.empty-list{text-align:center;padding:24px;color:#94a3b8;font-size:0.85rem;}

/* Paste modal */
.pm-textarea{width:100%;padding:12px;border:1.5px solid #e0e7ff;border-radius:8px;font-family:'JetBrains Mono',monospace;font-size:0.85rem;resize:vertical;background:#fafbff;color:#1e293b;outline:none;min-height:200px;letter-spacing:0.5px;}
.pm-textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.pm-summary{display:flex;gap:14px;margin-bottom:10px;font-size:0.85rem;font-weight:700;flex-wrap:wrap;}
.pm-summary span{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;}
.pm-ok-count{color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;}
.pm-err-count{color:#dc2626;background:#fef2f2;border:1px solid #fecaca;}
.pm-cap-warn{color:#b45309;background:#fffbeb;border:1px solid #fde68a;}
.pm-err-list{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;max-height:160px;overflow-y:auto;}
.pm-err-header{font-size:0.75rem;font-weight:700;color:#991b1b;margin-bottom:6px;}
.pm-err-row{display:flex;gap:12px;font-size:0.78rem;padding:3px 0;border-bottom:1px solid #fecaca;}
.pm-err-row:last-child{border-bottom:none;}
.pm-err-imei{font-family:monospace;font-weight:600;color:#7f1d1d;width:160px;flex-shrink:0;}
.pm-err-reason{color:#991b1b;}
</style>

<div class="scan-wrap">

    <!-- Header -->
    <div class="scan-header">
        <div>
            <h1><i class="bi bi-upc-scan"></i> IMEI Scan Station</h1>
            <div class="inv-ref">
                <?= htmlspecialchars($purchase['invoice_no']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($purchase['party_name']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($purchase['warehouse_name'] ?? '') ?>
            </div>
        </div>
        <a href="?page=purchases&action=detail&id=<?= $purchase['id'] ?>"
           style="background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;padding:7px 16px;border-radius:8px;font-size:0.82rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchase
        </a>
    </div>

    <?php if (empty($items)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-upc-scan fs-2 d-block mb-2"></i>
        No IMEI-tracked items in this purchase.
    </div></div>
    <?php else: ?>

    <!-- Item Tabs -->
    <div class="item-tabs" id="itemTabs">
        <?php foreach ($items as $idx => $item): ?>
        <?php
            $pct    = $item['quantity'] > 0 ? min(100, round($item['scanned'] / $item['quantity'] * 100)) : 0;
            $isDone = $item['scanned'] >= $item['quantity'];
        ?>
        <div class="item-tab <?= $idx === 0 ? 'active' : '' ?>"
             onclick="switchItem(<?= $item['item_id'] ?>, <?= $item['quantity'] ?>, '<?= htmlspecialchars(addslashes($item['item_name']), ENT_QUOTES) ?>')"
             id="tab_<?= $item['item_id'] ?>">
            <?= htmlspecialchars($item['item_name']) ?>
            <span class="tab-count <?= $isDone ? 'done' : 'warn' ?>">
                <?= $item['scanned'] ?> / <?= $item['quantity'] ?>
                <?= $isDone ? '✓' : '' ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Scan Station -->
    <div class="scan-station">
        <div class="scan-station-header">
            <div class="item-label" id="stationItemName"><?= htmlspecialchars($items[0]['item_name']) ?></div>
            <div class="progress-info">
                <div>
                    <span class="progress-count" id="stationCount"><?= $items[0]['scanned'] ?></span>
                    <span class="progress-count need" id="stationNeed">/ <?= $items[0]['quantity'] ?></span>
                </div>
                <div>
                    <div class="progress-bar-wrap">
                        <?php $pct0 = $items[0]['quantity'] > 0 ? min(100, round($items[0]['scanned'] / $items[0]['quantity'] * 100)) : 0; ?>
                        <div class="progress-bar-fill <?= $pct0 >= 100 ? 'done' : '' ?>"
                             id="stationBar" style="width:<?= $pct0 ?>%"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#94a3b8;text-align:right;margin-top:2px;" id="stationPct"><?= $pct0 ?>%</div>
                </div>
            </div>
        </div>

        <div class="scan-input-wrap">
            <div class="scan-input-label">
                <span class="dot"></span> Ready — scan or type IMEI then press Enter
            </div>
            <input type="text" id="scanInput" placeholder="Scan barcode here..."
                   maxlength="18" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                   inputmode="numeric">
            <div id="scanFeedback" class="idle">—</div>
            <div class="scan-actions">
                <button type="button" class="btn-undo" id="btnUndo" onclick="undoLast()" disabled>
                    <i class="bi bi-arrow-counterclockwise"></i> Undo Last
                </button>
                <button type="button" class="btn-paste" id="btnPaste" onclick="openPasteMode()">
                    <i class="bi bi-clipboard-plus"></i> Paste IMEIs
                </button>
                <button type="button" class="btn-bulk-remove" id="btnBulkRemove" disabled>
                    <i class="bi bi-clipboard-x"></i> Bulk Remove
                </button>
                <button type="button" class="btn-clear-all" id="btnClearAll" disabled>
                    <i class="bi bi-trash3"></i> Clear All
                </button>
                <span class="queue-badge" id="queueBadge" title="Pending scans">
                    <i class="bi bi-hourglass-split"></i> <span id="queueCount">0</span> queued
                </span>
                <span style="font-size:0.78rem;color:#94a3b8;align-self:center;">
                    <i class="bi bi-keyboard me-1"></i> Enter to confirm · Paste to import · Select rows or Bulk Remove to delete
                </span>
            </div>
        </div>

        <div class="imei-list-wrap" id="imeiListWrap">
            <div class="imei-list-header">
                <label class="imei-list-title imei-select-all">
                    <input type="checkbox" id="selectAllImeis" disabled>
                    <span>Scanned IMEIs (most recent first)</span>
                </label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <div class="copy-qty-wrap" title="Copy and select the most recently scanned IMEIs">
                        <span class="copy-qty-label">Copy latest</span>
                        <input type="number" id="copyQtyInput" class="copy-qty-input" value="80" min="1" max="1" inputmode="numeric">
                        <span class="copy-qty-max">of <span id="copyQtyMax">0</span></span>
                        <button type="button" class="btn-copy-sm" id="btnCopyLatest" disabled>
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <button type="button" class="btn-copy-sm" id="btnCopySelected" disabled>
                        <i class="bi bi-clipboard"></i> Copy Selected (<span id="copySelectedCount">0</span>)
                    </button>
                    <button type="button" class="btn-delete-selected" id="btnDeleteSelected" disabled>
                        <i class="bi bi-trash3"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div id="imeiList"><div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div></div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- ── Bulk Remove IMEIs Modal ───────────────────────────────────── -->
<div class="modal fade" id="bulkRemoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border-bottom:1px solid #fdba74;">
                <h5 class="modal-title" style="font-size:0.95rem;font-weight:700;display:flex;align-items:center;gap:8px;color:#9a3412;">
                    <i class="bi bi-clipboard-x" style="color:#ea580c;"></i>
                    Bulk Remove IMEIs — <span id="brItemName" style="color:#6366f1;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.82rem;color:#64748b;margin-bottom:10px;">
                    <i class="bi bi-info-circle me-1" style="color:#ea580c;"></i>
                    Paste IMEIs to remove — one per line, or separated by commas/semicolons. Only IMEIs scanned for this item on this purchase will be deleted.
                </p>
                <div style="font-size:0.78rem;color:#475569;margin-bottom:8px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span><i class="bi bi-list-check me-1"></i> Currently scanned: <strong id="brScannedCount" style="color:#6366f1;">0</strong></span>
                    <span><i class="bi bi-stickies me-1"></i> Lines pasted: <strong id="brLineCount">0</strong></span>
                </div>
                <textarea id="brTextarea" class="pm-textarea"
                          placeholder="Paste IMEIs to remove (one per line)...&#10;&#10;355851156907021&#10;355851156749589"></textarea>
                <div id="brPreview" style="margin-top:12px;display:none;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="brValidateBtn"
                        style="background:#ea580c;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="brConfirmBtn" disabled
                        style="background:#dc2626;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-trash3 me-1"></i> Confirm Remove
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Paste IMEIs Modal ───────────────────────────────────────── -->
<div class="modal fade" id="pasteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:1px solid #bae6fd;">
                <h5 class="modal-title" style="font-size:0.95rem;font-weight:700;display:flex;align-items:center;gap:8px;color:#0c4a6e;">
                    <i class="bi bi-clipboard-plus" style="color:#0ea5e9;"></i>
                    Paste IMEIs — <span id="pmItemName" style="color:#6366f1;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.82rem;color:#64748b;margin-bottom:10px;">
                    <i class="bi bi-info-circle me-1" style="color:#0ea5e9;"></i>
                    Paste IMEIs below — one per line, or separated by commas/semicolons. Duplicates and invalid entries will be skipped automatically.
                </p>
                <div style="font-size:0.78rem;color:#475569;margin-bottom:8px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span><i class="bi bi-box-seam me-1"></i> Remaining for this item: <strong id="pmRemaining" style="color:#6366f1;">0</strong></span>
                    <span><i class="bi bi-stickies me-1"></i> Lines pasted: <strong id="pmLineCount">0</strong></span>
                </div>
                <textarea id="pmTextarea" class="pm-textarea"
                          placeholder="Paste IMEIs here (one per line)...&#10;&#10;355851156907021&#10;355851156749589&#10;355851156748334"
                          oninput="updateLineCount()"></textarea>
                <div id="pmPreview" style="margin-top:12px;display:none;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="pmValidateBtn"
                        style="background:#0ea5e9;color:#fff;border:none;font-weight:600;"
                        onclick="validatePaste()">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="pmConfirmBtn" disabled
                        style="background:#6366f1;color:#fff;border:none;font-weight:600;"
                        onclick="confirmPaste()">
                    <i class="bi bi-cloud-upload me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const PURCHASE_ID = <?= $purchase['id'] ?>;
const CSRF_TOKEN  = '<?= Auth::csrfToken() ?>';
let currentItemId  = <?= !empty($items) ? $items[0]['item_id'] : 0 ?>;
let currentQty     = <?= !empty($items) ? $items[0]['quantity'] : 0 ?>;
let currentScanned = <?= !empty($items) ? $items[0]['scanned'] : 0 ?>;
let currentItemName = '<?= !empty($items) ? htmlspecialchars(addslashes($items[0]['item_name']), ENT_QUOTES) : '' ?>';
let lastImei       = null;
let feedbackTimer  = null;

// Async scan queue — prevents skipped scans on fast barcode input
let scanQueue       = [];
let scanQueueRun    = false;
let scanLocalSeen   = {};

// Item data map
const itemData = {
    <?php foreach ($items as $item): ?>
    <?= $item['item_id'] ?>: { qty: <?= $item['quantity'] ?>, scanned: <?= $item['scanned'] ?>, name: '<?= htmlspecialchars(addslashes($item['item_name']), ENT_QUOTES) ?>' },
    <?php endforeach; ?>
};

function getScanImeiRule(name) {
    const n = (name || currentItemName || '').toLowerCase();
    if (n.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13', test: imei => /^\d{13}$/.test(imei) };
    return { min: 15, max: 18, label: '15–18', test: imei => /^\d{15,18}$/.test(imei) };
}

const scanInput = document.getElementById('scanInput');

// Always keep input focused (skip when paste modal is open)
document.addEventListener('click', e => {
    if (document.querySelector('#pasteModal.show, #bulkRemoveModal.show')) return;
    if (e.target.closest('#imeiListWrap') && e.target.closest('.imei-check, .btn-delete-selected, .btn-copy-sm, #selectAllImeis, #copyQtyInput, .copy-qty-wrap')) return;
    if (document.activeElement !== scanInput) scanInput.focus();
});
document.addEventListener('keydown', e => {
    if (document.querySelector('#pasteModal.show, #bulkRemoveModal.show')) return;
    if (document.activeElement !== scanInput && !['INPUT','SELECT','TEXTAREA','BUTTON'].includes(e.target.tagName)) {
        scanInput.focus();
    }
});
scanInput.focus();

// Enter key = enqueue scan (no input disable — prevents drops on fast scanning)
scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const raw = this.value;
        this.value = '';
        enqueueScan(raw);
    }
});

function normalizeImei(raw) {
    if (!raw) return '';
    // Strip control chars, whitespace, common scanner prefixes/suffixes
    let s = String(raw).replace(/[\r\n\t]/g, '').trim().toUpperCase();
    // Strip ALL whitespace inside
    s = s.replace(/\s+/g, '');
    return s;
}

function enqueueScan(raw) {
    const imei = normalizeImei(raw);
    if (!imei) return;

    const rule = getScanImeiRule(currentItemName);
    if (!rule.test(imei)) {
        flashInput('err');
        showFeedback('err', '<i class="bi bi-x-circle me-1"></i> Must be ' + rule.label + ' digits — got "' + imei + '"');
        return;
    }

    // Local in-session duplicate guard (instant feedback)
    if (scanLocalSeen[imei] === 'ok' || scanLocalSeen[imei] === 'pending') {
        flashInput('err');
        showFeedback('err', '<i class="bi bi-exclamation-triangle me-1"></i> Already scanned this session: ' + imei);
        return;
    }
    scanLocalSeen[imei] = 'pending';

    // Optimistic row (pending state)
    prependImeiRow(imei, 'pending');

    // Queue and process
    scanQueue.push({ imei: imei, itemId: currentItemId });
    updateQueueBadge();
    processScanQueue();

    // Quick visual feedback that scan was registered
    flashInput('ok');
    showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> Queued: ' + imei);
}

function processScanQueue() {
    if (scanQueueRun || scanQueue.length === 0) return;
    scanQueueRun = true;
    const job = scanQueue.shift();
    updateQueueBadge();

    fetch('?page=purchases&action=imeiScanAdd', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     job.itemId,
            imei:        job.imei,
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            scanLocalSeen[job.imei] = 'ok';
            markImeiRow(job.imei, 'ok');
            // Only update progress if scan was for currently active item
            if (job.itemId === currentItemId) {
                lastImei = job.imei;
                updateProgress(res.scanned, res.qty);
                document.getElementById('btnUndo').disabled = false;
            } else {
                // Refresh the other item's tab counter
                refreshTabCount(job.itemId);
            }
        } else {
            scanLocalSeen[job.imei] = 'failed';
            markImeiRow(job.imei, 'failed', res.msg || 'Failed');
            flashInput('err');
            showFeedback('err', '<i class="bi bi-exclamation-triangle me-1"></i> ' + (res.msg || 'Save failed'));
        }
    })
    .catch(() => {
        // Network error — retry by re-queuing
        scanQueue.unshift(job);
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error — retrying...');
    })
    .finally(() => {
        scanQueueRun = false;
        updateQueueBadge();
        processScanQueue();
    });
}

function updateQueueBadge() {
    const badge = document.getElementById('queueBadge');
    const count = document.getElementById('queueCount');
    const pending = scanQueue.length + (scanQueueRun ? 1 : 0);
    if (pending > 0) {
        badge.classList.add('active');
        count.textContent = pending;
    } else {
        badge.classList.remove('active');
    }
}

function refreshTabCount(itemId) {
    fetch('?page=purchases&action=imeiScanList&purchase_id=' + PURCHASE_ID + '&item_id=' + itemId)
        .then(r => r.json())
        .then(rows => {
            if (itemData[itemId]) itemData[itemId].scanned = rows.length;
            const tab = document.getElementById('tab_' + itemId);
            if (tab) {
                const span = tab.querySelector('.tab-count');
                const qty = itemData[itemId] ? itemData[itemId].qty : 0;
                if (span) {
                    span.textContent = rows.length + ' / ' + qty + (rows.length >= qty ? ' ✓' : '');
                    span.className   = 'tab-count ' + (rows.length >= qty ? 'done' : 'warn');
                }
            }
        })
        .catch(() => {});
}

function undoLast() {
    if (!lastImei) return;
    if (!confirm('Remove IMEI ' + lastImei + '?')) return;
    const imeiToDelete = lastImei;
    fetch('?page=purchases&action=imeiScanDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            imei:        imeiToDelete,
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            lastImei = null;
            document.getElementById('btnUndo').disabled = true;
            updateProgress(res.scanned, currentQty);
            removeImeiRow(imeiToDelete);
            showFeedback('ok', 'Removed ' + imeiToDelete);
        } else {
            showFeedback('err', res.msg);
        }
        scanInput.focus();
    });
}

function deleteImei(imei) {
    if (!confirm('Remove IMEI ' + imei + '?')) { scanInput.focus(); return; }
    fetch('?page=purchases&action=imeiScanDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            imei:        imei,
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            if (lastImei === imei) { lastImei = null; document.getElementById('btnUndo').disabled = true; }
            updateProgress(res.scanned, currentQty);
            removeImeiRow(imei);
        } else {
            showFeedback('err', res.msg);
        }
        scanInput.focus();
    });
}

function getListedImeis() {
    return Array.from(document.querySelectorAll('#imeiList .imei-row.is-ok .imei-num'))
        .map(el => el.textContent.trim())
        .filter(Boolean);
}

function getCopyQty() {
    const listed = getListedImeis();
    if (!listed.length) return 0;
    const raw = parseInt(document.getElementById('copyQtyInput').value, 10);
    const qty = Number.isFinite(raw) && raw > 0 ? raw : 80;
    return Math.min(qty, listed.length);
}

async function copyImeisToClipboard(imeis, label) {
    if (!imeis.length) {
        showFeedback('err', '<i class="bi bi-clipboard-x me-1"></i> Nothing to copy');
        return;
    }
    const text = imeis.join('\n');
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        showFeedback('ok', '<i class="bi bi-clipboard-check me-1"></i> Copied ' + imeis.length + ' IMEI(s)' + (label ? ' (' + label + ')' : ''));
    } catch (err) {
        showFeedback('err', '<i class="bi bi-clipboard-x me-1"></i> Could not copy — select and copy manually from the list');
    }
}

function selectImeisByValues(imeis) {
    const wanted = new Set(imeis);
    document.querySelectorAll('#imeiList .imei-cb:not(:disabled)').forEach(cb => {
        cb.checked = wanted.has(cb.value);
    });
    updateSelectionUI();
}

function copyLatestImeis() {
    const listed = getListedImeis();
    if (!listed.length) {
        showFeedback('err', '<i class="bi bi-clipboard-x me-1"></i> No IMEIs to copy');
        return;
    }
    const qty = getCopyQty();
    const slice = listed.slice(0, qty);
    selectImeisByValues(slice);
    copyImeisToClipboard(slice, 'latest ' + slice.length);
}

function copySelectedImeis() {
    copyImeisToClipboard(getSelectedImeis(), 'selected');
}

function updateCopyButtons() {
    const listed = getListedImeis();
    const total = listed.length;
    const qtyInput = document.getElementById('copyQtyInput');
    const maxEl = document.getElementById('copyQtyMax');

    maxEl.textContent = total;
    qtyInput.max = Math.max(1, total);
    document.getElementById('btnCopyLatest').disabled = total === 0;

    const selected = getSelectedImeis();
    document.getElementById('copySelectedCount').textContent = selected.length;
    document.getElementById('btnCopySelected').disabled = selected.length === 0;
}

function updateDeleteButtons() {
    const hasScanned = currentScanned > 0;
    document.getElementById('btnClearAll').disabled    = !hasScanned;
    document.getElementById('btnBulkRemove').disabled  = !hasScanned;
    document.getElementById('btnDeleteSelected').style.display = hasScanned ? 'inline-flex' : 'none';
    if (!hasScanned) {
        resetSelection();
    } else {
        updateSelectionUI();
    }
    updateCopyButtons();
}

function getSelectedImeis() {
    return Array.from(document.querySelectorAll('#imeiList .imei-cb:checked')).map(cb => cb.value);
}

function resetSelection() {
    const selectAll = document.getElementById('selectAllImeis');
    selectAll.checked = false;
    selectAll.indeterminate = false;
    selectAll.disabled = true;
    document.getElementById('selectedCount').textContent = '0';
    document.getElementById('btnDeleteSelected').disabled = true;
}

function updateSelectionUI() {
    const selected = getSelectedImeis();
    const count = selected.length;
    const allCbs = document.querySelectorAll('#imeiList .imei-cb:not(:disabled)');
    const selectAll = document.getElementById('selectAllImeis');

    document.getElementById('selectedCount').textContent = count;
    document.getElementById('btnDeleteSelected').disabled = count === 0;

    if (allCbs.length > 0) {
        selectAll.disabled = false;
        selectAll.indeterminate = count > 0 && count < allCbs.length;
        selectAll.checked = count > 0 && count === allCbs.length;
    } else {
        resetSelection();
    }

    document.querySelectorAll('#imeiList .imei-row').forEach(row => {
        const cb = row.querySelector('.imei-cb');
        row.classList.toggle('is-selected', !!(cb && cb.checked));
    });
    updateCopyButtons();
}

function deleteSelectedImeis() {
    const selected = getSelectedImeis();
    if (selected.length === 0) return;

    const msg = 'Remove ' + selected.length + ' selected IMEI(s) from "' + currentItemName + '"?';
    if (!confirm(msg)) { scanInput.focus(); return; }

    const btn = document.getElementById('btnDeleteSelected');
    btn.disabled = true;

    fetch('?page=purchases&action=imeiScanBulkDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            scope:       'list',
            imeis:       selected.join('\n'),
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.ok) {
            showFeedback('err', res.msg || 'Delete failed');
            updateSelectionUI();
            scanInput.focus();
            return;
        }

        selected.forEach(imei => {
            if (lastImei === imei) {
                lastImei = null;
                document.getElementById('btnUndo').disabled = true;
            }
            removeImeiRow(imei);
        });

        updateProgress(res.scanned, currentQty);
        resetSelection();
        updateSelectionUI();

        let msg = res.deleted + ' IMEI(s) removed';
        if (res.skipped && res.skipped.length > 0) {
            msg += ' · ' + res.skipped.length + ' skipped';
        }
        showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> ' + msg);
        scanInput.focus();
    })
    .catch(() => {
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error during delete');
        updateSelectionUI();
        scanInput.focus();
    });
}

function clearAllImeis() {
    if (currentScanned === 0) return;
    const msg = 'Remove all ' + currentScanned + ' scanned IMEI(s) for "' + currentItemName + '"?\n\nThis cannot be undone.';
    if (!confirm(msg)) { scanInput.focus(); return; }

    const btn = document.getElementById('btnClearAll');
    btn.disabled = true;

    fetch('?page=purchases&action=imeiScanBulkDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            scope:       'all',
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.ok) {
            showFeedback('err', res.msg || 'Bulk delete failed');
            updateDeleteButtons();
            scanInput.focus();
            return;
        }

        lastImei = null;
        document.getElementById('btnUndo').disabled = true;
        scanLocalSeen = {};
        updateProgress(res.scanned, currentQty);
        renderImeiList([]);
        resetSelection();

        let msg = res.deleted + ' IMEI(s) removed';
        if (res.skipped && res.skipped.length > 0) {
            msg += ' · ' + res.skipped.length + ' skipped';
        }
        showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> ' + msg);
        scanInput.focus();
    })
    .catch(() => {
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error during bulk delete');
        updateDeleteButtons();
        scanInput.focus();
    });
}

// ════════════════════════════════════════════
//  BULK REMOVE — delete pasted IMEI list
// ════════════════════════════════════════════
let brValidList = [];

function openBulkRemoveMode() {
    if (!currentItemId || currentScanned === 0) return;
    document.getElementById('brItemName').textContent      = currentItemName;
    document.getElementById('brScannedCount').textContent  = currentScanned;
    document.getElementById('brTextarea').value            = '';
    document.getElementById('brLineCount').textContent     = '0';
    document.getElementById('brPreview').style.display     = 'none';
    document.getElementById('brPreview').innerHTML         = '';
    document.getElementById('brConfirmBtn').disabled       = true;
    document.getElementById('brConfirmBtn').innerHTML        = '<i class="bi bi-trash3 me-1"></i> Confirm Remove';
    brValidList = [];

    const modal = new bootstrap.Modal(document.getElementById('bulkRemoveModal'));
    modal.show();
    setTimeout(() => document.getElementById('brTextarea').focus(), 350);
}

function updateBulkRemoveLineCount() {
    const raw = document.getElementById('brTextarea').value;
    const lines = raw.split(/[\r\n,;]+/).filter(s => s.trim().length > 0);
    document.getElementById('brLineCount').textContent = lines.length;
}

function validateBulkRemove() {
    const raw   = document.getElementById('brTextarea').value;
    const lines = raw.split(/[\r\n,;]+/);

    const valid  = [];
    const errors = [];
    const seen   = {};

    lines.forEach(line => {
        const imei = normalizeImei(line);
        if (!imei) return;

        const rule = getScanImeiRule(currentItemName);
        if (!rule.test(imei)) {
            errors.push({ imei: imei, reason: 'Must be ' + rule.label + ' digits (got ' + imei.length + ')' });
            return;
        }
        if (seen[imei]) {
            errors.push({ imei: imei, reason: 'Duplicate in list' });
            return;
        }
        seen[imei] = true;
        valid.push(imei);
    });

    brValidList = valid;

    let html = '<div class="pm-summary">';
    html += '<span class="pm-ok-count"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' to remove</span>';
    if (errors.length > 0) {
        html += '<span class="pm-err-count"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="pm-err-list"><div class="pm-err-header">Invalid entries:</div>';
        errors.forEach(e => {
            html += '<div class="pm-err-row"><span class="pm-err-imei">' + e.imei + '</span><span class="pm-err-reason">' + e.reason + '</span></div>';
        });
        html += '</div>';
    }

    const preview = document.getElementById('brPreview');
    preview.innerHTML     = html;
    preview.style.display = 'block';
    document.getElementById('brConfirmBtn').disabled = (valid.length === 0);
}

function confirmBulkRemove() {
    if (brValidList.length === 0) return;
    if (!confirm('Remove ' + brValidList.length + ' IMEI(s) from "' + currentItemName + '"?')) return;

    const btn = document.getElementById('brConfirmBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Removing...';

    fetch('?page=purchases&action=imeiScanBulkDelete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     currentItemId,
            scope:       'list',
            imeis:       brValidList.join('\n'),
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.ok) {
            showFeedback('err', res.msg || 'Bulk remove failed');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirm Remove';
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('bulkRemoveModal')).hide();
        switchItem(currentItemId, currentQty, currentItemName);

        let msg = res.deleted + ' IMEI(s) removed';
        if (res.skipped && res.skipped.length > 0) {
            msg += ' · ' + res.skipped.length + ' skipped';
        }
        showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> ' + msg);
    })
    .catch(() => {
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error during bulk remove');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirm Remove';
    });
}

document.getElementById('btnClearAll').addEventListener('click', clearAllImeis);
document.getElementById('btnBulkRemove').addEventListener('click', openBulkRemoveMode);
document.getElementById('btnCopyLatest').addEventListener('click', copyLatestImeis);
document.getElementById('btnCopySelected').addEventListener('click', copySelectedImeis);
document.getElementById('copyQtyInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        copyLatestImeis();
    }
});
document.getElementById('btnDeleteSelected').addEventListener('click', deleteSelectedImeis);
document.getElementById('selectAllImeis').addEventListener('change', function() {
    const checked = this.checked;
    document.querySelectorAll('#imeiList .imei-cb:not(:disabled)').forEach(cb => {
        cb.checked = checked;
    });
    updateSelectionUI();
});
document.getElementById('imeiList').addEventListener('change', function(e) {
    if (e.target.classList.contains('imei-cb')) {
        updateSelectionUI();
    }
});
document.getElementById('imeiList').addEventListener('click', function(e) {
    const delBtn = e.target.closest('.del-imei');
    if (!delBtn) return;
    const imei = delBtn.dataset.imei;
    if (imei) deleteImei(imei);
});
document.getElementById('brTextarea').addEventListener('input', updateBulkRemoveLineCount);
document.getElementById('brValidateBtn').addEventListener('click', validateBulkRemove);
document.getElementById('brConfirmBtn').addEventListener('click', confirmBulkRemove);

// ════════════════════════════════════════════
//  PASTE MODE — bulk import with validation
// ════════════════════════════════════════════
let pmValidList = [];

function openPasteMode() {
    if (!currentItemId) return;
    const remaining = Math.max(0, currentQty - currentScanned);
    document.getElementById('pmItemName').textContent  = currentItemName;
    document.getElementById('pmRemaining').textContent = remaining;
    document.getElementById('pmTextarea').value        = '';
    document.getElementById('pmLineCount').textContent = '0';
    document.getElementById('pmPreview').style.display = 'none';
    document.getElementById('pmPreview').innerHTML     = '';
    document.getElementById('pmConfirmBtn').disabled   = true;
    document.getElementById('pmConfirmBtn').innerHTML  = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    pmValidList = [];

    const modal = new bootstrap.Modal(document.getElementById('pasteModal'));
    modal.show();
    setTimeout(() => document.getElementById('pmTextarea').focus(), 350);
}

function updateLineCount() {
    const raw = document.getElementById('pmTextarea').value;
    const lines = raw.split(/[\r\n,;]+/).filter(s => s.trim().length > 0);
    document.getElementById('pmLineCount').textContent = lines.length;
}

function validatePaste() {
    const raw   = document.getElementById('pmTextarea').value;
    const lines = raw.split(/[\r\n,;]+/);

    const valid    = [];
    const errors   = [];
    const seen     = {};
    const remaining = Math.max(0, currentQty - currentScanned);

    lines.forEach(line => {
        const imei = normalizeImei(line);
        if (!imei) return;

        const rule = getScanImeiRule(currentItemName);
        if (!rule.test(imei)) {
            errors.push({ imei: imei, reason: 'Must be ' + rule.label + ' digits (got ' + imei.length + ')' });
            return;
        }
        if (seen[imei]) {
            errors.push({ imei: imei, reason: 'Duplicate in list' });
            return;
        }
        if (scanLocalSeen[imei] === 'ok') {
            errors.push({ imei: imei, reason: 'Already scanned in this purchase' });
            return;
        }
        seen[imei] = true;
        valid.push(imei);
    });

    pmValidList = valid;

    // Build preview
    const overCap = valid.length > remaining;
    let html = '<div class="pm-summary">';
    html += '<span class="pm-ok-count"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' valid</span>';
    if (errors.length > 0) {
        html += '<span class="pm-err-count"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    if (overCap) {
        const extra = valid.length - remaining;
        html += '<span class="pm-cap-warn"><i class="bi bi-exclamation-triangle-fill"></i> ' + extra + ' over remaining (' + remaining + ') — extras will be skipped server-side</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="pm-err-list"><div class="pm-err-header">Skipped IMEIs:</div>';
        errors.forEach(e => {
            html += '<div class="pm-err-row"><span class="pm-err-imei">' + e.imei + '</span><span class="pm-err-reason">' + e.reason + '</span></div>';
        });
        html += '</div>';
    }

    const preview = document.getElementById('pmPreview');
    preview.innerHTML     = html;
    preview.style.display = 'block';

    document.getElementById('pmConfirmBtn').disabled = (valid.length === 0);
}

function confirmPaste() {
    if (pmValidList.length === 0) return;
    const targetItemId = currentItemId;
    const targetItemName = currentItemName;

    const btn = document.getElementById('pmConfirmBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';

    fetch('?page=purchases&action=imeiScanBulk', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            purchase_id: PURCHASE_ID,
            item_id:     targetItemId,
            imeis:       pmValidList.join('\n'),
            csrf_token:  CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.ok) {
            showFeedback('err', res.msg || 'Bulk import failed');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
            return;
        }

        // Refresh active item's list & progress from server (single source of truth)
        if (targetItemId === currentItemId) {
            switchItem(targetItemId, currentQty, targetItemName);
        } else {
            refreshTabCount(targetItemId);
        }

        bootstrap.Modal.getInstance(document.getElementById('pasteModal')).hide();

        let msg = res.saved + ' IMEI(s) imported';
        if (res.skipped && res.skipped.length > 0) {
            msg += ' · ' + res.skipped.length + ' skipped';
        }
        showFeedback('ok', '<i class="bi bi-check-circle me-1"></i> ' + msg);
    })
    .catch(() => {
        showFeedback('err', '<i class="bi bi-wifi-off me-1"></i> Network error during bulk import');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    });
}

function switchItem(itemId, qty, name) {
    // Update tab active state
    document.querySelectorAll('.item-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab_' + itemId)?.classList.add('active');

    currentItemId   = itemId;
    currentQty      = qty;
    currentItemName = name;
    lastImei        = null;
    scanLocalSeen   = {};
    resetSelection();
    document.getElementById('btnUndo').disabled = true;
    document.getElementById('stationItemName').textContent = name;
    document.getElementById('stationNeed').textContent     = '/ ' + qty;
    document.getElementById('scanFeedback').className      = 'idle';

    // Load scanned list from server
    fetch('?page=purchases&action=imeiScanList&purchase_id=' + PURCHASE_ID + '&item_id=' + itemId)
        .then(r => r.json())
        .then(rows => {
            const scanned = rows.length;
            updateProgress(scanned, qty);
            renderImeiList(rows);
            // Mark all loaded IMEIs as already-seen for this session
            rows.forEach(r => { scanLocalSeen[r.imei] = 'ok'; });
            if (rows.length > 0) {
                lastImei = rows[0].imei;
                document.getElementById('btnUndo').disabled = false;
            }
        });
    scanInput.value = '';
    scanInput.focus();
}

function updateProgress(scanned, qty) {
    currentScanned = scanned;
    document.getElementById('stationCount').textContent = scanned;
    const pct = qty > 0 ? Math.min(100, Math.round(scanned / qty * 100)) : 0;
    const bar = document.getElementById('stationBar');
    bar.style.width = pct + '%';
    bar.className   = 'progress-bar-fill' + (pct >= 100 ? ' done' : '');
    document.getElementById('stationPct').textContent = pct + '%';

    // Update tab counter
    const tab = document.getElementById('tab_' + currentItemId);
    if (tab) {
        const span = tab.querySelector('.tab-count');
        if (span) {
            span.textContent = scanned + ' / ' + qty + (scanned >= qty ? ' ✓' : '');
            span.className   = 'tab-count ' + (scanned >= qty ? 'done' : 'warn');
        }
    }
    if (itemData[currentItemId]) itemData[currentItemId].scanned = scanned;
    updateDeleteButtons();
}

function buildImeiRowHtml(imei, state, timeLabel, reason) {
    const canSelect = state === 'ok';
    const checkbox = canSelect
        ? '<label class="imei-check"><input type="checkbox" class="imei-cb" value="' + imei + '"></label>'
        : '<span class="imei-check" style="width:15px;"></span>';
    const icon = state === 'pending'
        ? '<span class="imei-status" title="Saving..."><i class="bi bi-hourglass-split" style="color:#f59e0b;"></i></span>'
        : (state === 'failed'
            ? '<span class="imei-status" title="' + (reason || 'Failed') + '"><i class="bi bi-x-circle-fill" style="color:#dc2626;"></i></span>'
            : '<span class="imei-status" title="Saved"><i class="bi bi-check-circle-fill" style="color:#16a34a;"></i></span>');
    return '<div class="imei-row is-' + state + '" id="ir_' + imei + '" data-imei="' + imei + '">' +
        checkbox +
        '<div class="imei-main"><span class="imei-num">' + imei + '</span></div>' +
        '<div style="display:flex;align-items:center;gap:8px;">' + icon +
        '<span class="imei-time">' + timeLabel + '</span>' +
        '<button type="button" class="del-imei" data-imei="' + imei + '" title="Remove">×</button></div></div>';
}

function prependImeiRow(imei, state) {
    state = state || 'ok';
    const list = document.getElementById('imeiList');
    const empty = list.querySelector('.empty-list');
    if (empty) empty.remove();

    const existing = document.getElementById('ir_' + imei);
    if (existing) { markImeiRow(imei, state); return; }

    const now = new Date().toLocaleTimeString();
    const wrapper = document.createElement('div');
    wrapper.innerHTML = buildImeiRowHtml(imei, state, now);
    const row = wrapper.firstElementChild;
    list.insertBefore(row, list.firstChild);
    updateSelectionUI();
}

function markImeiRow(imei, state, reason) {
    const row = document.getElementById('ir_' + imei);
    if (!row) return;
    row.classList.remove('is-pending', 'is-ok', 'is-failed');
    row.classList.add('is-' + state);
    const status = row.querySelector('.imei-status');
    if (status) {
        if (state === 'pending') {
            status.innerHTML = '<i class="bi bi-hourglass-split" style="color:#f59e0b;" title="Saving..."></i>';
        } else if (state === 'failed') {
            status.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#dc2626;" title="' + (reason || 'Failed') + '"></i>';
        } else {
            status.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a;" title="Saved"></i>';
        }
    }
    const checkWrap = row.querySelector('.imei-check');
    if (state === 'ok' && checkWrap && !checkWrap.querySelector('.imei-cb')) {
        checkWrap.outerHTML = '<label class="imei-check"><input type="checkbox" class="imei-cb" value="' + imei + '"></label>';
    }
    updateSelectionUI();
}

function removeImeiRow(imei) {
    document.getElementById('ir_' + imei)?.remove();
    delete scanLocalSeen[imei];
    if (!document.getElementById('imeiList').children.length) {
        document.getElementById('imeiList').innerHTML = '<div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div>';
        resetSelection();
    } else {
        updateSelectionUI();
    }
}

function renderImeiList(rows) {
    const list = document.getElementById('imeiList');
    if (!rows.length) {
        list.innerHTML = '<div class="empty-list"><i class="bi bi-upc-scan d-block mb-1 fs-4"></i> No IMEIs scanned yet</div>';
        resetSelection();
        return;
    }
    list.innerHTML = rows.map(r => buildImeiRowHtml(r.imei, 'ok', r.created_at)).join('');
    updateSelectionUI();
}

function flashInput(type) {
    // Color-flash animation disabled: the forced reflow + CSS transition + timer
    // on every scan slowed down rapid barcode scanning. Feedback still shown via
    // showFeedback() text banner, which is cheap and non-blocking.
}

function showFeedback(type, msg) {
    clearTimeout(feedbackTimer);
    const el = document.getElementById('scanFeedback');
    el.className  = type;
    el.innerHTML  = msg;
    if (type === 'ok') feedbackTimer = setTimeout(() => { el.className = 'idle'; el.innerHTML = '—'; }, 2500);
}

// Load initial list for first item
<?php if (!empty($items)): ?>
switchItem(<?= $items[0]['item_id'] ?>, <?= $items[0]['quantity'] ?>, '<?= htmlspecialchars(addslashes($items[0]['item_name']), ENT_QUOTES) ?>');
<?php endif; ?>
</script>
