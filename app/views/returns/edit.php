<?php function retMoney($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); } ?>

<style>
.re-wrap { max-width: 1400px; margin: 0 auto; }

.re-topbar {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 20px; margin-bottom: 16px;
    background: linear-gradient(135deg, #064e3b 0%, #059669 100%);
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(5, 150, 105, 0.28);
}
.re-back {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22);
    color: #fff; text-decoration: none; transition: background .15s;
}
.re-back:hover { background: rgba(255,255,255,0.22); color: #fff; }
.re-topbar-title { flex: 1; min-width: 180px; }
.re-topbar-title h1 {
    margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.re-doc-no { font-family: ui-monospace, monospace; letter-spacing: 0.02em; }
.re-status {
    display: inline-flex; align-items: center; padding: 3px 12px;
    border-radius: 999px; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em;
    background: rgba(255,255,255,0.2); color: #d1fae5; border: 1px solid rgba(255,255,255,0.28);
}

.re-topbar-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.re-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px; font-size: 0.82rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; transition: all .15s;
    white-space: nowrap;
}
.re-btn-ghost { background: rgba(255,255,255,0.1); color: #d1fae5; border: 1px solid rgba(255,255,255,0.22); }
.re-btn-ghost:hover { background: rgba(255,255,255,0.18); color: #fff; }
.re-btn-primary { background: #fff; color: #065f46; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.re-btn-primary:hover { background: #ecfdf5; transform: translateY(-1px); }

.re-grid { display: grid; grid-template-columns: minmax(300px, 340px) 1fr; gap: 16px; align-items: start; }
@media (max-width: 991px) { .re-grid { grid-template-columns: 1fr; } }

.re-panel {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
}
.re-panel-head {
    padding: 12px 18px;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border-bottom: 1px solid #bbf7d0;
    font-size: 0.78rem; font-weight: 800; color: #047857;
    text-transform: uppercase; letter-spacing: 0.06em;
    display: flex; align-items: center; gap: 8px;
}
.re-panel-body { padding: 18px; }

.re-field { margin-bottom: 16px; }
.re-field:last-child { margin-bottom: 0; }
.re-label {
    display: block; font-size: 0.72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;
}
.re-input {
    width: 100%; padding: 10px 12px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 0.9rem; color: #1e293b; background: #fafbff;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.re-input:focus { border-color: #059669; background: #fff; box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }

.re-info-chip {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px; border-radius: 10px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
}
.re-info-chip i { color: #94a3b8; font-size: 0.85rem; margin-top: 2px; }
.re-info-chip .name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.3; }
.re-info-chip .sub { font-size: 0.78rem; color: #64748b; margin-top: 2px; }

.re-totals {
    background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%);
    border: 2px solid #6ee7b7; border-radius: 12px; padding: 14px 16px;
}
.re-totals-row {
    display: flex; justify-content: space-between; align-items: baseline; gap: 12px;
}
.re-totals-row .lbl { font-size: 0.72rem; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.04em; }
.re-totals-row .val { font-size: 1.35rem; font-weight: 900; color: #064e3b; }
.re-totals-hint { font-size: 0.75rem; color: #64748b; margin-top: 8px; }

.re-items-panel { display: flex; flex-direction: column; }
.re-items-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 18px;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border-bottom: 1px solid #bbf7d0;
}
.re-items-head-title {
    font-size: 0.78rem; font-weight: 800; color: #047857;
    text-transform: uppercase; letter-spacing: 0.06em;
    display: flex; align-items: center; gap: 8px;
}
.re-items-hint { font-size: 0.72rem; color: #64748b; font-weight: 500; }

.re-scan {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border-bottom: 1px solid #86efac;
}
.re-scan-wrap { position: relative; flex: 1; min-width: 260px; }
.re-scan-wrap i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #059669; font-size: 1.05rem; pointer-events: none;
}
.re-scan-input {
    width: 100%; padding: 12px 14px 12px 42px;
    border: 2px solid #86efac; border-radius: 11px; min-height: 46px;
    font-size: 0.95rem; font-family: ui-monospace, monospace; letter-spacing: 0.4px;
    background: #fafbff; color: #1e293b; outline: none; transition: all .2s;
}
.re-scan-input:focus { border-color: #059669; background: #fff; box-shadow: 0 0 0 3px rgba(5,150,105,0.12); }
.re-scan-input::placeholder { font-family: inherit; letter-spacing: normal; font-size: 0.82rem; color: #94a3b8; }
.re-scan-meta { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.re-scan-msg { font-size: 0.76rem; font-weight: 600; min-width: 80px; max-width: 220px; }
.re-scan-msg.ok { color: #065f46; }
.re-scan-msg.err { color: #991b1b; }
.re-scan-msg.warn { color: #b45309; }
.re-scan-count {
    display: inline-flex; align-items: baseline; gap: 6px;
    padding: 8px 18px; border-radius: 999px;
    background: linear-gradient(135deg, #059669, #047857);
    color: #fff; font-size: 0.78rem; font-weight: 700;
    box-shadow: 0 3px 12px rgba(5,150,105,0.35); white-space: nowrap;
}
.re-scan-count-num { font-size: 1.25rem; font-weight: 900; line-height: 1; }

.re-tbl-wrap { overflow-x: auto; }
.re-tbl { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.re-tbl th {
    padding: 10px 12px; font-size: 0.68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.06em; color: #64748b;
    background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
.re-tbl td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.re-tbl tbody tr { transition: background .1s; }
.re-tbl tbody tr:hover { background: #f0fdf4; }
.re-tbl tr.deleted-row { opacity: 0.4; background: #fff5f5 !important; }
.re-tbl tr.deleted-row td { text-decoration: line-through; }
.re-tbl tr.new-row { background: #f0fdf4; }
.re-tbl tfoot td { background: #f8fafc; border-top: 2px solid #e2e8f0; }

.re-row-num { color: #94a3b8; font-weight: 700; font-size: 0.8rem; width: 32px; }
.re-item-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.35; }
.re-item-sku { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }
.re-imei-badge {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
    font-size: 0.68rem; font-weight: 700; padding: 3px 9px; border-radius: 6px;
    color: #047857; background: rgba(5,150,105,0.1); border: 1px solid rgba(5,150,105,0.22);
}

.re-cell-input {
    width: 100%; max-width: 88px; padding: 7px 8px;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.85rem; font-weight: 700; color: #1e293b;
    background: #fff; text-align: center; outline: none;
}
.re-cell-input.price { max-width: 100px; text-align: right; margin-left: auto; display: block; }
.re-cell-input:focus { border-color: #059669; box-shadow: 0 0 0 2px rgba(5,150,105,0.1); }
.re-row-total { font-weight: 800; color: #047857; white-space: nowrap; }

.re-btn-remove {
    width: 30px; height: 30px; border-radius: 8px; border: none;
    background: transparent; color: #cbd5e1; cursor: pointer;
    font-size: 1.1rem; line-height: 1; transition: all .15s;
    display: inline-flex; align-items: center; justify-content: center;
}
.re-btn-remove:hover { background: #fef2f2; color: #dc2626; }

.re-add-row {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px; cursor: pointer;
    border-top: 2px dashed #86efac; color: #64748b;
    font-size: 0.82rem; font-weight: 700; background: #fff;
    transition: all .15s;
}
.re-add-row:hover { background: #f0fdf4; color: #059669; border-top-color: #059669; }
.re-add-row-icon {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(5,150,105,0.12); color: #059669;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
}

.re-autocomplete {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #bbf7d0; border-radius: 10px;
    z-index: 9999; box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    max-height: 220px; overflow-y: auto;
}
.re-autocomplete-item {
    padding: 10px 14px; cursor: pointer; font-size: 0.82rem;
    border-bottom: 1px solid #f1f5f9; transition: background .1s;
}
.re-autocomplete-item:hover { background: #ecfdf5; }
.re-autocomplete-item:last-child { border-bottom: none; }

.re-imei-hidden { display: none !important; }
.new-row-flash { animation: reRowFlash .65s ease-out; }
@keyframes reRowFlash { 0% { background: #bbf7d0; } 100% { background: #f0fdf4; } }

@media (max-width: 767px) {
    .re-topbar { padding: 12px 14px; }
    .re-topbar-actions { width: 100%; justify-content: flex-end; }
}
</style>

<?php $itemCount = count($editReturn['items']); ?>

<div class="re-wrap">
<form method="POST" action="?page=returns&action=update" id="editReturnForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $editReturn['id'] ?>">
    <input type="hidden" name="return_edit_nonce" value="<?= htmlspecialchars($returnEditNonce ?? '') ?>">

    <div class="re-topbar">
        <a href="?page=returns&action=detail&id=<?= $editReturn['id'] ?>" class="re-back" title="Back to return">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="re-topbar-title">
            <h1>
                <span>Edit Return</span>
                <span class="re-doc-no"><?= htmlspecialchars($editReturn['return_no']) ?></span>
                <span class="re-status"><?= ucfirst($editReturn['status']) ?></span>
            </h1>
        </div>
        <div class="re-topbar-actions">
            <a href="?page=returns&action=detail&id=<?= $editReturn['id'] ?>" class="re-btn re-btn-ghost">Cancel</a>
            <button type="submit" class="re-btn re-btn-primary pin-protect">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="re-grid">
        <aside>
            <div class="re-panel">
                <div class="re-panel-head"><i class="bi bi-sliders"></i> Return Details</div>
                <div class="re-panel-body">
                    <div class="re-field">
                        <label class="re-label" for="retDate">Date</label>
                        <input type="date" name="date" id="retDate" class="re-input" required
                               value="<?= htmlspecialchars($editReturn['date']) ?>">
                    </div>

                    <div class="re-field">
                        <label class="re-label">Customer</label>
                        <div class="re-info-chip">
                            <i class="bi bi-person-circle"></i>
                            <div>
                                <div class="name"><?= htmlspecialchars($editReturn['party_name']) ?></div>
                                <?php if (!empty($editReturn['party_phone'])): ?>
                                <div class="sub"><?= htmlspecialchars($editReturn['party_phone']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($editReturn['original_invoice'])): ?>
                                <div class="sub">Ref: <?= htmlspecialchars($editReturn['original_invoice']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="re-field">
                        <div class="re-totals">
                            <div class="re-totals-row">
                                <span class="lbl">Return Total</span>
                                <span class="val" id="newGrandTotal"><?= retMoney($editReturn['grand_total']) ?></span>
                            </div>
                            <div class="re-totals-hint">Subtotal: <strong id="leftSubtotal"><?= retMoney($editReturn['subtotal']) ?></strong></div>
                        </div>
                    </div>

                    <div class="re-field">
                        <label class="re-label" for="retReason">Reason</label>
                        <textarea name="reason" id="retReason" class="re-input" rows="3"
                                  placeholder="Optional reason…" style="resize:vertical;min-height:72px;"><?= htmlspecialchars($editReturn['reason'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </aside>

        <section class="re-panel re-items-panel">
            <div class="re-items-head">
                <span class="re-items-head-title"><i class="bi bi-arrow-return-left"></i> Return Items</span>
                <span class="re-items-hint"><?= $itemCount ?> line<?= $itemCount !== 1 ? 's' : '' ?> · scan to add</span>
            </div>

            <div class="re-scan">
                <div class="re-scan-wrap">
                    <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    <input type="text" class="re-scan-input" id="retEditScanBar"
                           placeholder="Scan IMEI — auto-adds return line" autocomplete="off"
                           aria-describedby="retScanMsg">
                </div>
                <div class="re-scan-meta">
                    <span class="re-scan-msg" id="retScanMsg" role="status" aria-live="polite"></span>
                    <span class="re-scan-count" id="retScanCount">
                        <span class="re-scan-count-num">0</span> scanned
                    </span>
                </div>
            </div>

            <div class="re-tbl-wrap">
                <table class="re-tbl">
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
                        <?php foreach ($editReturn['items'] as $i => $item):
                            $lineImeis = [];
                            if (!empty($item['imei_list'])) {
                                $lineImeis = array_values(array_filter(array_map('trim', explode('||', $item['imei_list']))));
                            }
                            $imeiCount = count($lineImeis);
                        ?>
                        <tr id="row_<?= $item['id'] ?>" data-item-id="<?= (int)$item['item_id'] ?>" data-row-key="row_<?= $item['id'] ?>">
                            <td class="re-row-num"><?= $i + 1 ?></td>
                            <td>
                                <div class="re-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if (!empty($item['sku'])): ?>
                                <div class="re-item-sku"><?= htmlspecialchars((string) $item['sku']) ?></div>
                                <?php endif; ?>
                                <?php if ($imeiCount > 0): ?>
                                <span class="re-imei-badge" id="imeiBadge_row_<?= $item['id'] ?>">
                                    <i class="bi bi-upc-scan"></i> <?= $imeiCount ?> IMEI<?= $imeiCount !== 1 ? 's' : '' ?>
                                </span>
                                <?php else: ?>
                                <span class="re-imei-badge" id="imeiBadge_row_<?= $item['id'] ?>" style="display:none;"></span>
                                <?php endif; ?>
                                <textarea name="items[<?= $item['id'] ?>][imeis]" id="imeiField_row_<?= $item['id'] ?>"
                                          class="re-imei-hidden"><?= htmlspecialchars(implode("\n", $lineImeis)) ?></textarea>
                                <input type="hidden" name="items[<?= $item['id'] ?>][deleted]" id="del_<?= $item['id'] ?>" value="0">
                            </td>
                            <td class="text-center">
                                <input type="number" name="items[<?= $item['id'] ?>][quantity]"
                                       value="<?= (int)$item['quantity'] ?>" min="1"
                                       class="re-cell-input edit-qty" data-row="<?= $item['id'] ?>">
                            </td>
                            <td class="text-end">
                                <input type="number" name="items[<?= $item['id'] ?>][unit_price]"
                                       value="<?= number_format((float)$item['unit_price'], DECIMAL_PLACES, '.', '') ?>"
                                       step="0.001" min="0" readonly
                                       title="Original sold price (locked)"
                                       class="re-cell-input price edit-price" data-row="<?= $item['id'] ?>"
                                       style="background:#f8fafc;">
                            </td>
                            <td class="text-end re-row-total row-total" id="rowTotal_<?= $item['id'] ?>"><?= retMoney($item['total']) ?></td>
                            <td class="text-center">
                                <button type="button" class="re-btn-remove btn-del-row" title="Remove item"
                                        data-row-id="<?= $item['id'] ?>">×</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end" style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Subtotal</td>
                            <td class="text-end re-row-total" id="editSubtotal" style="font-size:0.95rem;"><?= retMoney($editReturn['subtotal']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="re-add-row" id="btnAddNewItemRow" role="button" tabindex="0">
                <span class="re-add-row-icon"><i class="bi bi-plus-lg"></i></span>
                Add item manually
            </div>
        </section>
    </div>
</form>
</div>

<script>
<?php
$initImeiData = [];
foreach ($editReturn['items'] as $item) {
    $ims = [];
    if (!empty($item['imei_list'])) {
        $ims = array_values(array_filter(array_map('trim', explode('||', $item['imei_list']))));
    }
    $initImeiData['row_' . $item['id']] = $ims;
}
?>
var currency       = '<?= APP_CURRENCY ?>';
var warehouseId    = <?= (int)$editReturn['warehouse_id'] ?>;
var returnPartyId  = <?= (int)($editReturn['party_id'] ?? 0) ?>;
var returnRefId    = <?= (int)($editReturn['ref_id'] ?? 0) ?>;
var newRowCount    = 0;
var searchTimers   = {};
var retImeiData    = <?= json_encode($initImeiData, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
var retScanBusy    = false;

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
    recalcReturn();
}

function updateImeiBadge(rowKey) {
    const badge = document.getElementById('imeiBadge_' + rowKey);
    const list  = retImeiData[rowKey] || [];
    if (!badge) return;
    if (!list.length) { badge.style.display = 'none'; return; }
    badge.style.display = 'inline-flex';
    badge.innerHTML = '<i class="bi bi-upc-scan"></i> ' + list.length + ' IMEI' + (list.length !== 1 ? 's' : '');
}

function syncImeiField(rowKey) {
    const el = document.getElementById('imeiField_' + rowKey);
    const list = retImeiData[rowKey] || [];
    if (el) el.value = list.join('\n');
    updateImeiBadge(rowKey);
    renderRetScanCount();
}

function attachImeiToRow(rowKey, imei, syncQty) {
    if (!retImeiData[rowKey]) retImeiData[rowKey] = [];
    if (retImeiData[rowKey].includes(imei)) return false;
    retImeiData[rowKey].push(imei);
    syncImeiField(rowKey);
    if (syncQty) {
        let qtyInput = null;
        if (rowKey.startsWith('row_')) {
            qtyInput = document.querySelector('#' + rowKey + ' .edit-qty');
        } else if (rowKey.startsWith('new_')) {
            qtyInput = document.getElementById('newQty_' + rowKey.replace('new_', ''));
        }
        if (qtyInput) qtyInput.value = retImeiData[rowKey].length;
    }
    recalcReturn();
    return true;
}

function addNewItemRow(prefill) {
    newRowCount++;
    const n = newRowCount;
    const rowKey = 'new_' + n;
    retImeiData[rowKey] = prefill && prefill.imeis ? prefill.imeis.slice() : [];
    const tr = document.createElement('tr');
    tr.id = 'newrow_' + n;
    tr.className = 'new-row';
    tr.dataset.newRow = String(n);
    tr.dataset.itemId = prefill ? String(prefill.itemId) : '';
    tr.dataset.rowKey = rowKey;
    const badgeHtml = retImeiData[rowKey].length
        ? '<span class="re-imei-badge" id="imeiBadge_' + rowKey + '"><i class="bi bi-upc-scan"></i> ' + retImeiData[rowKey].length + ' IMEI' + (retImeiData[rowKey].length !== 1 ? 's' : '') + '</span>'
        : '<span class="re-imei-badge" id="imeiBadge_' + rowKey + '" style="display:none;"></span>';
    tr.innerHTML =
        '<td class="re-row-num">+</td>' +
        '<td style="position:relative;">' +
            '<input type="text" class="re-cell-input new-item-search" style="max-width:none;text-align:left;font-weight:500;"' +
                ' id="newSearch_' + n + '" placeholder="Search item…" autocomplete="off">' +
            '<input type="hidden" name="new_items[' + n + '][item_id]" id="newItemId_' + n + '" value="' + (prefill ? prefill.itemId : '') + '">' +
            '<textarea name="new_items[' + n + '][imeis]" id="imeiField_' + rowKey + '" class="re-imei-hidden">' + retImeiData[rowKey].join('\n') + '</textarea>' +
            '<div id="newItemLabel_' + n + '" class="re-item-name" style="' + (prefill ? '' : 'display:none;') + '">' + (prefill ? prefill.name : '') + '</div>' +
            badgeHtml +
            '<div class="re-autocomplete" id="newDrop_' + n + '" style="display:none;"></div>' +
        '</td>' +
        '<td class="text-center">' +
            '<input type="number" name="new_items[' + n + '][quantity]" id="newQty_' + n + '"' +
                ' value="' + (prefill ? (retImeiData[rowKey].length || 1) : 1) + '" min="1" class="re-cell-input new-qty">' +
        '</td>' +
        '<td class="text-end">' +
            '<input type="number" name="new_items[' + n + '][unit_price]" id="newPrice_' + n + '"' +
                ' value="' + (prefill ? prefill.price : '') + '" step="0.001" min="0" readonly' +
                ' title="Original sold price (locked)" class="re-cell-input price new-price" style="background:#f8fafc;">' +
        '</td>' +
        '<td class="text-end re-row-total new-total" id="newTotal_' + n + '">—</td>' +
        '<td class="text-center">' +
            '<button type="button" class="re-btn-remove" title="Remove" data-new-row="' + n + '">×</button>' +
        '</td>';
    document.getElementById('itemsTbody').appendChild(tr);
    if (prefill) {
        document.getElementById('newSearch_' + n).style.display = 'none';
        recalcReturn();
        renderRetScanCount();
    } else {
        document.getElementById('newSearch_' + n).focus();
    }
    return n;
}

function removeNewRow(n) {
    const rowKey = 'new_' + n;
    delete retImeiData[rowKey];
    document.getElementById('newrow_' + n)?.remove();
    renderRetScanCount();
    recalcReturn();
}

function searchNewItem(n, q) {
    clearTimeout(searchTimers['n' + n]);
    const drop = document.getElementById('newDrop_' + n);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers['n' + n] = setTimeout(() => {
        fetch('?page=sales&action=searchItems&q=' + encodeURIComponent(q) + '&warehouse_id=' + warehouseId)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = items.map(it =>
                    '<div class="re-autocomplete-item" data-n="' + n + '" data-id="' + it.id + '"' +
                    ' data-name="' + it.name.replace(/"/g, '&quot;') + '">' +
                    '<strong>' + it.name + '</strong>' +
                    (it.sku ? ' <small style="color:#94a3b8;">· ' + it.sku + '</small>' : '') +
                    '<small style="float:right;color:#64748b;">sold price from invoice</small>' +
                    '</div>'
                ).join('');
                drop.style.display = 'block';
            });
    }, 250);
}

function selectNewItem(n, id, name, price) {
    document.getElementById('newItemId_' + n).value = id;
    document.getElementById('newSearch_' + n).value = name;
    const priceEl = document.getElementById('newPrice_' + n);
    if (price != null && price !== '' && parseFloat(price) > 0) {
        priceEl.value = parseFloat(price).toFixed(3);
    } else {
        priceEl.value = '';
        applyEditSoldPriceFromInvoice(n, id, name);
    }
    const label = document.getElementById('newItemLabel_' + n);
    if (label) { label.textContent = name; label.style.display = ''; }
    const tr = document.getElementById('newrow_' + n);
    if (tr) tr.dataset.itemId = String(id);
    document.getElementById('newDrop_' + n).style.display = 'none';
    document.getElementById('newSearch_' + n).style.display = 'none';
    recalcReturn();
}

function applyEditSoldPriceFromInvoice(n, itemId, itemName) {
    if (!returnRefId || !itemId) {
        setRetScanMsg('Scan IMEI or use linked invoice sold price — catalog price is not used.', 'err');
        return;
    }
    fetch('?page=returns&action=saleReturnLimits&ref_id=' + encodeURIComponent(returnRefId) +
          '&warehouse_id=' + encodeURIComponent(warehouseId))
        .then(r => r.json())
        .then(data => {
            const lim = (data.limits || {})[itemId];
            const priceEl = document.getElementById('newPrice_' + n);
            if (!priceEl) return;
            if (lim && lim.unit_price != null && lim.unit_price !== '') {
                priceEl.value = parseFloat(lim.unit_price).toFixed(3);
                recalcReturn();
            } else {
                priceEl.value = '';
                setRetScanMsg(
                    lim
                        ? ('"' + (itemName || lim.name) + '" has mixed sold prices — scan IMEIs.')
                        : ('"' + (itemName || 'Item') + '" was not on the linked invoice.'),
                    'err'
                );
            }
        })
        .catch(function() {});
}

function hideNewDrop(n) {
    const d = document.getElementById('newDrop_' + n);
    if (d) d.style.display = 'none';
}

function recalcReturn() {
    var subtotal = 0;

    document.querySelectorAll('.edit-qty').forEach(function(qEl) {
        const rowId = qEl.getAttribute('data-row');
        const del   = document.getElementById('del_' + rowId);
        if (del && del.value === '1') return;
        const price    = parseFloat(document.querySelector('.edit-price[data-row="' + rowId + '"]')?.value) || 0;
        const qty      = parseFloat(qEl.value) || 0;
        const rowTotal = qty * price;
        subtotal += rowTotal;
        const el = document.getElementById('rowTotal_' + rowId);
        if (el) el.textContent = currency + ' ' + rowTotal.toFixed(3);
    });

    document.querySelectorAll('.new-qty').forEach(function(qEl) {
        const n     = qEl.closest('tr').id.replace('newrow_', '');
        const price = parseFloat(document.getElementById('newPrice_' + n)?.value) || 0;
        const qty   = parseFloat(qEl.value) || 0;
        const total = qty * price;
        subtotal   += total;
        const el    = document.getElementById('newTotal_' + n);
        if (el) el.textContent = total > 0 ? currency + ' ' + total.toFixed(3) : '—';
    });

    document.getElementById('editSubtotal').textContent  = currency + ' ' + subtotal.toFixed(3);
    document.getElementById('leftSubtotal').textContent  = currency + ' ' + subtotal.toFixed(3);
    document.getElementById('newGrandTotal').textContent = currency + ' ' + subtotal.toFixed(3);
}

function getAllReturnImeis() {
    const all = [];
    Object.keys(retImeiData).forEach(function(k) {
        (retImeiData[k] || []).forEach(function(im) { all.push(im); });
    });
    return all;
}

function renderRetScanCount() {
    const el = document.getElementById('retScanCount');
    if (!el) return;
    el.innerHTML = '<span class="re-scan-count-num">' + getAllReturnImeis().length + '</span> scanned';
}

function setRetScanMsg(text, type) {
    const msg = document.getElementById('retScanMsg');
    if (!msg) return;
    msg.className = 're-scan-msg ' + (type || '');
    msg.textContent = text || '';
}

function normalizeReturnScanImei(raw) {
    return (window.IqbalImei && IqbalImei.normalize) ? IqbalImei.normalize(raw) : String(raw || '').toUpperCase().replace(/[\r\n\t\s]/g, '');
}

function findRowKeyForItem(itemId, unitPrice) {
    let found = null;
    const wantPrice = unitPrice != null && unitPrice !== ''
        ? parseFloat(unitPrice).toFixed(3)
        : null;
    document.querySelectorAll('#itemsTbody tr').forEach(function(tr) {
        if (found) return;
        if (tr.classList.contains('deleted-row')) return;
        const del = tr.id.startsWith('row_') ? document.getElementById('del_' + tr.id.replace('row_', '')) : null;
        if (del && del.value === '1') return;
        if (parseInt(tr.dataset.itemId, 10) !== itemId) return;
        if (wantPrice !== null) {
            const rowKey = tr.dataset.rowKey || '';
            let priceEl = null;
            if (rowKey.startsWith('new_')) {
                priceEl = document.getElementById('newPrice_' + rowKey.replace('new_', ''));
            } else if (rowKey.startsWith('row_')) {
                priceEl = tr.querySelector('.edit-price');
            }
            const rowPrice = parseFloat(priceEl?.value || 0).toFixed(3);
            if (rowPrice !== wantPrice) return;
        }
        found = tr.dataset.rowKey;
    });
    return found;
}

function findEmptyNewRow() {
    let found = null;
    document.querySelectorAll('#itemsTbody tr[data-new-row]').forEach(function(tr) {
        if (found) return;
        const n = tr.dataset.newRow;
        if (!document.getElementById('newItemId_' + n)?.value) found = 'new_' + n;
    });
    return found;
}

function flashRetRow(rowKey) {
    let tr = null;
    if (rowKey.startsWith('row_')) {
        tr = document.getElementById(rowKey);
    } else if (rowKey.startsWith('new_')) {
        tr = document.getElementById('newrow_' + rowKey.replace('new_', ''));
    }
    if (!tr) return;
    tr.classList.remove('new-row-flash');
    void tr.offsetWidth;
    tr.classList.add('new-row-flash');
    try { tr.scrollIntoView({ behavior: 'auto', block: 'nearest' }); } catch (e) {}
}

function processRetEditScan() {
    const input = document.getElementById('retEditScanBar');
    const imei  = normalizeReturnScanImei(input.value);
    if (!imei || retScanBusy) return;

    if (!IqbalImei.isPlausible(imei)) {
        setRetScanMsg('Need a phone IMEI or tablet serial', 'err');
        input.value = '';
        input.focus();
        return;
    }
    if (getAllReturnImeis().includes(imei)) {
        setRetScanMsg('Already on this return', 'err');
        input.value = '';
        input.focus();
        return;
    }

    retScanBusy = true;
    input.value = '';
    setRetScanMsg('Looking up…', '');

    fetch('?page=returns&action=lookupImei&imei=' + encodeURIComponent(imei) + '&warehouse_id=' + warehouseId)
        .then(function(r) {
            if (!r.ok) throw new Error('Server error');
            return r.json();
        })
        .then(function(data) {
            if (!data.accepted) {
                setRetScanMsg(data.message || 'Cannot return this IMEI', 'err');
                return;
            }

            if (data.found) {
                // Cross-party OK: credit stays on this return's customer; IMEI may be from another buyer.
                const soldPartyId = parseInt(data.party_id || data.sale_party_id || 0, 10);
                const crossParty = returnPartyId > 0 && soldPartyId > 0 && soldPartyId !== returnPartyId;

                const soldPriceMatch = parseFloat(data.unit_price || data.sale_price || 0).toFixed(3);
                let rowKey = findRowKeyForItem(parseInt(data.item_id, 10), soldPriceMatch);
                if (rowKey) {
                    attachImeiToRow(rowKey, data.imei, true);
                } else {
                    let emptyKey = findEmptyNewRow();
                    if (!emptyKey) {
                        const soldPrice = parseFloat(data.unit_price || data.sale_price || 0).toFixed(3);
                        const n = addNewItemRow({
                            itemId: data.item_id,
                            name: data.item_name,
                            price: soldPrice,
                            imeis: [data.imei]
                        });
                        rowKey = 'new_' + n;
                    } else {
                        const n = emptyKey.replace('new_', '');
                        const soldPrice = parseFloat(data.unit_price || data.sale_price || 0).toFixed(3);
                        selectNewItem(n, data.item_id, data.item_name, soldPrice);
                        attachImeiToRow(emptyKey, data.imei, true);
                        rowKey = emptyKey;
                    }
                }
                setRetScanMsg(
                    crossParty
                        ? ('Added — originally sold to ' + (data.party_name || 'another customer'))
                        : 'Added',
                    crossParty ? 'warn' : 'ok'
                );
                flashRetRow(rowKey);
                renderRetScanCount();
                return;
            }

            let emptyKey = findEmptyNewRow();
            if (!emptyKey) {
                const n = addNewItemRow({ imeis: [data.imei] });
                emptyKey = 'new_' + n;
            } else {
                attachImeiToRow(emptyKey, data.imei, true);
            }
            const n = emptyKey.replace('new_', '');
            const searchEl = document.getElementById('newSearch_' + n);
            if (searchEl) {
                searchEl.style.display = '';
                searchEl.style.borderColor = '#f59e0b';
                searchEl.placeholder = 'Select item for scanned IMEI…';
            }
            setRetScanMsg('Select item model for IMEI', 'warn');
            renderRetScanCount();
        })
        .catch(function() { setRetScanMsg('Network error', 'err'); })
        .finally(function() {
            retScanBusy = false;
            input.focus();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    recalcReturn();
    renderRetScanCount();

    document.getElementById('itemsTbody').addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-del-row');
        if (delBtn) {
            toggleDelete(parseInt(delBtn.dataset.rowId, 10), delBtn);
            return;
        }
        const newDel = e.target.closest('.re-btn-remove[data-new-row]');
        if (newDel) removeNewRow(parseInt(newDel.dataset.newRow, 10));
    });

    document.getElementById('itemsTbody').addEventListener('input', function(e) {
        if (e.target.classList.contains('edit-qty') || e.target.classList.contains('edit-price')) {
            recalcReturn();
        }
        if (e.target.classList.contains('new-item-search')) {
            searchNewItem(e.target.id.replace('newSearch_', ''), e.target.value);
        }
        if (e.target.classList.contains('new-qty') || e.target.classList.contains('new-price')) {
            recalcReturn();
        }
    });

    document.getElementById('itemsTbody').addEventListener('blur', function(e) {
        if (e.target.classList.contains('new-item-search')) {
            const n = e.target.id.replace('newSearch_', '');
            setTimeout(function() { hideNewDrop(n); }, 200);
        }
    }, true);

    document.getElementById('itemsTbody').addEventListener('mousedown', function(e) {
        const item = e.target.closest('.re-autocomplete-item');
        if (!item) return;
        e.preventDefault();
        selectNewItem(
            parseInt(item.dataset.n, 10),
            parseInt(item.dataset.id, 10),
            item.dataset.name,
            null
        );
    });

    document.getElementById('btnAddNewItemRow').addEventListener('click', function() { addNewItemRow(); });
    document.getElementById('btnAddNewItemRow').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); addNewItemRow(); }
    });

    const scanBar = document.getElementById('retEditScanBar');
    scanBar.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); processRetEditScan(); }
    });
    scanBar.addEventListener('input', function() {
        const val = normalizeReturnScanImei(this.value);
        if (IqbalImei.shouldAutoConfirmAny(val)) {
            setTimeout(processRetEditScan, 120);
        }
    });
    scanBar.focus();
});
</script>
