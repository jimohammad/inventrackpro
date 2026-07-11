<?php function purEditMoney($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); } ?>

<style>
.pe-wrap { max-width: 1400px; margin: 0 auto; }
.pe-topbar {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 20px; margin-bottom: 16px;
    background: linear-gradient(135deg, #3730a3 0%, #6366f1 100%);
    border-radius: 14px; box-shadow: 0 4px 18px rgba(79, 70, 229, 0.28);
}
.pe-back {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22);
    color: #fff; text-decoration: none;
}
.pe-back:hover { background: rgba(255,255,255,0.22); color: #fff; }
.pe-topbar-title { flex: 1; min-width: 180px; }
.pe-topbar-title h1 {
    margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.pe-inv-no { font-family: ui-monospace, monospace; }
.pe-status {
    display: inline-flex; padding: 3px 12px; border-radius: 999px;
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    background: rgba(255,255,255,0.2); color: #e0e7ff; border: 1px solid rgba(255,255,255,0.28);
}
.pe-topbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.pe-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px; font-size: 0.82rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; white-space: nowrap;
}
.pe-btn-ghost { background: rgba(255,255,255,0.1); color: #e0e7ff; border: 1px solid rgba(255,255,255,0.22); }
.pe-btn-ghost:hover { background: rgba(255,255,255,0.18); color: #fff; }
.pe-btn-primary { background: #fff; color: #4338ca; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.pe-btn-primary:hover { background: #f5f3ff; }

.pe-grid { display: grid; grid-template-columns: minmax(300px, 340px) 1fr; gap: 16px; align-items: start; }
@media (max-width: 991px) { .pe-grid { grid-template-columns: 1fr; } }

.pe-panel {
    background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(15,23,42,0.04);
}
.pe-panel-head {
    padding: 12px 18px; background: linear-gradient(135deg, #f8faff, #f0f4ff);
    border-bottom: 1px solid #e0e7ff; font-size: 0.78rem; font-weight: 800;
    color: #4338ca; text-transform: uppercase; letter-spacing: 0.06em;
    display: flex; align-items: center; gap: 8px;
}
.pe-panel-body { padding: 18px; }
.pe-field { margin-bottom: 16px; }
.pe-field:last-child { margin-bottom: 0; }
.pe-label {
    display: block; font-size: 0.72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;
}
.pe-input {
    width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 0.9rem; color: #1e293b; background: #fafbff; outline: none;
}
.pe-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.pe-info-chip {
    display: flex; gap: 10px; padding: 11px 14px; border-radius: 10px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
}
.pe-info-chip i { color: #94a3b8; margin-top: 2px; }
.pe-info-chip .name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
.pe-info-chip .sub { font-size: 0.78rem; color: #64748b; margin-top: 2px; }
.pe-totals {
    background: linear-gradient(145deg, #eef2ff, #e0e7ff);
    border: 2px solid #c7d2fe; border-radius: 12px; padding: 14px 16px;
}
.pe-totals-row { display: flex; justify-content: space-between; align-items: baseline; }
.pe-totals-row .lbl { font-size: 0.72rem; font-weight: 700; color: #4338ca; text-transform: uppercase; }
.pe-totals-row .val { font-size: 1.35rem; font-weight: 900; color: #312e81; }
.pe-totals-hint { font-size: 0.75rem; color: #64748b; margin-top: 8px; }

.pe-items-head {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 12px 18px; background: linear-gradient(135deg, #f8faff, #f0f4ff);
    border-bottom: 1px solid #e0e7ff;
}
.pe-items-head-title { font-size: 0.78rem; font-weight: 800; color: #4338ca; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }
.pe-items-hint { font-size: 0.72rem; color: #64748b; }
.pe-scan-link {
    font-size: 0.72rem; font-weight: 700; color: #4338ca; text-decoration: none;
    padding: 6px 12px; border-radius: 8px; background: rgba(99,102,241,0.1); border: 1px solid #c7d2fe;
}
.pe-scan-link:hover { background: rgba(99,102,241,0.18); color: #3730a3; }

.pe-tbl { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.pe-tbl th {
    padding: 10px 12px; font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
    color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
}
.pe-tbl td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pe-tbl tbody tr:hover { background: #f8faff; }
.pe-tbl tr.deleted-row { opacity: 0.4; background: #fff5f5 !important; }
.pe-tbl tr.deleted-row td { text-decoration: line-through; }
.pe-tbl tr.new-row { background: #f0fdf4; }
.pe-tbl tfoot td { background: #f8fafc; border-top: 2px solid #e2e8f0; }

.pe-row-num { color: #94a3b8; font-weight: 700; width: 32px; }
.pe-item-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
.pe-item-sku { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }
.pe-imei-badge {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
    font-size: 0.68rem; font-weight: 700; padding: 3px 9px; border-radius: 6px;
    color: #4338ca; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.22);
    text-decoration: none;
}
.pe-imei-warn { color: #d97706; background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.28); }

.pe-cell-input {
    width: 100%; max-width: 88px; padding: 7px 8px;
    border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; font-weight: 700;
    text-align: center; outline: none;
}
.pe-cell-input.price { max-width: 100px; text-align: right; margin-left: auto; display: block; }
.pe-cell-input:disabled { background: #f1f5f9; color: #94a3b8; }
.pe-cell-input:focus { border-color: #6366f1; }
.pe-row-total { font-weight: 800; color: #4338ca; white-space: nowrap; }

.pe-btn-remove {
    width: 30px; height: 30px; border-radius: 8px; border: none; background: transparent;
    color: #cbd5e1; cursor: pointer; font-size: 1.1rem;
}
.pe-btn-remove:hover { background: #fef2f2; color: #dc2626; }

.pe-add-row {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px; cursor: pointer; border-top: 2px dashed #c7d2fe;
    color: #64748b; font-size: 0.82rem; font-weight: 700; background: #fff;
}
.pe-add-row:hover { background: #f5f7ff; color: #6366f1; }
.pe-add-row-icon {
    width: 26px; height: 26px; border-radius: 50%; background: rgba(99,102,241,0.12);
    color: #6366f1; display: inline-flex; align-items: center; justify-content: center;
}

.pe-autocomplete {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff;
    border: 1.5px solid #e0e7ff; border-radius: 10px; z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1); max-height: 220px; overflow-y: auto;
}
.pe-autocomplete-item { padding: 10px 14px; cursor: pointer; font-size: 0.82rem; border-bottom: 1px solid #f1f5f9; }
.pe-autocomplete-item:hover { background: #f0f4ff; }
</style>

<?php $itemCount = count($editPurchase['items']); ?>

<div class="pe-wrap">
<form method="POST" action="?page=purchases&action=update&id=<?= $editPurchase['id'] ?>" id="editPurchaseForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $editPurchase['id'] ?>">
    <input type="hidden" name="discount" id="editDiscount"
           value="<?= number_format((float)$editPurchase['discount'], DECIMAL_PLACES, '.', '') ?>">

    <div class="pe-topbar">
        <a href="?page=purchases&action=detail&id=<?= $editPurchase['id'] ?>" class="pe-back"><i class="bi bi-arrow-left"></i></a>
        <div class="pe-topbar-title">
            <h1>
                <span>Edit Purchase</span>
                <span class="pe-inv-no"><?= htmlspecialchars($editPurchase['invoice_no']) ?></span>
                <span class="pe-status"><?= ucfirst($editPurchase['status']) ?></span>
            </h1>
        </div>
        <div class="pe-topbar-actions">
            <a href="?page=purchases&action=detail&id=<?= $editPurchase['id'] ?>" class="pe-btn pe-btn-ghost">Cancel</a>
            <button type="submit" class="pe-btn pe-btn-primary pin-protect"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
    </div>

    <div class="pe-grid">
        <aside>
            <div class="pe-panel">
                <div class="pe-panel-head"><i class="bi bi-sliders"></i> Purchase Details</div>
                <div class="pe-panel-body">
                    <div class="pe-field">
                        <label class="pe-label" for="purDate">Date</label>
                        <input type="date" name="date" id="purDate" class="pe-input" required
                               value="<?= htmlspecialchars($editPurchase['date']) ?>">
                    </div>
                    <div class="pe-field">
                        <label class="pe-label">Supplier</label>
                        <div class="pe-info-chip">
                            <i class="bi bi-building"></i>
                            <div>
                                <div class="name"><?= htmlspecialchars($editPurchase['party_name']) ?></div>
                                <?php if (!empty($editPurchase['party_phone'])): ?>
                                <div class="sub"><?= htmlspecialchars($editPurchase['party_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="pe-field">
                        <label class="pe-label" for="supplierInv">Supplier Invoice #</label>
                        <input type="text" name="supplier_invoice_no" id="supplierInv" class="pe-input"
                               value="<?= htmlspecialchars($editPurchase['supplier_invoice_no'] ?? '') ?>"
                               placeholder="Optional supplier reference">
                    </div>
                    <div class="pe-field">
                        <div class="pe-totals">
                            <div class="pe-totals-row">
                                <span class="lbl">Grand Total</span>
                                <span class="val" id="newGrandTotal"><?= purEditMoney($editPurchase['grand_total']) ?></span>
                            </div>
                            <div class="pe-totals-hint">
                                Subtotal: <strong id="leftSubtotal"><?= purEditMoney($editPurchase['subtotal']) ?></strong>
                                <?php if ((float)$editPurchase['paid_amount'] > 0): ?>
                                · Paid: <strong style="color:#059669;"><?= purEditMoney($editPurchase['paid_amount']) ?></strong>
                                · Balance: <strong style="color:#d97706;" id="newBalance"><?= purEditMoney($editPurchase['balance']) ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="pe-field">
                        <label class="pe-label" for="purNotes">Notes</label>
                        <textarea name="notes" id="purNotes" class="pe-input" rows="3"
                                  placeholder="Optional notes…" style="resize:vertical;min-height:72px;"><?= htmlspecialchars($editPurchase['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </aside>

        <section class="pe-panel">
            <div class="pe-items-head">
                <span class="pe-items-head-title"><i class="bi bi-box-seam"></i> Line Items</span>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="pe-items-hint"><?= $itemCount ?> line<?= $itemCount !== 1 ? 's' : '' ?></span>
                    <?php if (!empty($showImeiScan)): ?>
                    <a href="?page=purchases&action=imeiScan&id=<?= $editPurchase['id'] ?>" class="pe-scan-link">
                        <i class="bi bi-upc-scan"></i> Scan IMEIs
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="pe-tbl">
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
                        <?php foreach ($editPurchase['items'] as $i => $item):
                            $lineImeis = [];
                            if (!empty($item['imei_list'])) {
                                $lineImeis = array_values(array_filter(array_map('trim', explode('||', $item['imei_list']))));
                            }
                            $imeiCount = count($lineImeis);
                            $imeiLocked = !empty($item['has_imei']) || $imeiCount > 0;
                            $needsImei  = !empty($item['has_imei']) && $imeiCount < (int)$item['quantity'];
                        ?>
                        <tr id="row_<?= $item['id'] ?>" data-item-id="<?= (int)$item['item_id'] ?>">
                            <td class="pe-row-num"><?= $i + 1 ?></td>
                            <td>
                                <div class="pe-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if (!empty($item['sku'])): ?>
                                <div class="pe-item-sku"><?= htmlspecialchars((string)$item['sku']) ?></div>
                                <?php endif; ?>
                                <?php if ($imeiCount > 0 || !empty($item['has_imei'])): ?>
                                    <?php if ($needsImei): ?>
                                    <a href="?page=purchases&action=imeiScan&id=<?= $editPurchase['id'] ?>"
                                       class="pe-imei-badge pe-imei-warn">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Scan IMEIs (<?= $imeiCount ?>/<?= $item['quantity'] ?>)
                                    </a>
                                    <?php elseif ($imeiCount > 0): ?>
                                    <span class="pe-imei-badge"><i class="bi bi-upc-scan"></i> <?= $imeiCount ?> IMEI<?= $imeiCount !== 1 ? 's' : '' ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <input type="hidden" name="items[<?= $item['id'] ?>][deleted]" id="del_<?= $item['id'] ?>" value="0">
                            </td>
                            <td class="text-center">
                                <?php if ($imeiLocked): ?>
                                <input type="hidden" name="items[<?= $item['id'] ?>][quantity]" value="<?= (int)$item['quantity'] ?>">
                                <?php endif; ?>
                                <input type="number"<?= $imeiLocked ? '' : ' name="items[' . $item['id'] . '][quantity]"' ?>
                                       value="<?= (int)$item['quantity'] ?>" min="1"
                                       class="pe-cell-input edit-qty" data-row="<?= $item['id'] ?>"
                                       <?= $imeiLocked ? 'disabled data-imei-locked="1" title="IMEI items: remove line & re-add to change qty"' : '' ?>>
                            </td>
                            <td class="text-end">
                                <input type="number" name="items[<?= $item['id'] ?>][unit_price]"
                                       value="<?= number_format((float)$item['unit_price'], DECIMAL_PLACES, '.', '') ?>"
                                       step="0.001" min="0"
                                       class="pe-cell-input price edit-price" data-row="<?= $item['id'] ?>">
                            </td>
                            <td class="text-end pe-row-total row-total" id="rowTotal_<?= $item['id'] ?>"><?= purEditMoney($item['total']) ?></td>
                            <td class="text-center">
                                <button type="button" class="pe-btn-remove btn-del-row" data-row-id="<?= $item['id'] ?>" title="Remove">×</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end" style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;">Subtotal</td>
                            <td class="text-end pe-row-total" id="editSubtotal"><?= purEditMoney($editPurchase['subtotal']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="pe-add-row" id="btnAddNewItemRow" role="button" tabindex="0">
                <span class="pe-add-row-icon"><i class="bi bi-plus-lg"></i></span>
                Add item manually
            </div>
        </section>
    </div>
</form>
</div>

<script>
var currency    = '<?= APP_CURRENCY ?>';
var warehouseId = <?= (int)$editPurchase['warehouse_id'] ?>;
var paidAmount  = <?= (float)$editPurchase['paid_amount'] ?>;
var editDiscount = parseFloat(document.getElementById('editDiscount').value) || 0;
var newRowCount = 0;
var searchTimers = {};

function toggleDelete(id, btn) {
    const tr  = document.getElementById('row_' + id);
    const del = document.getElementById('del_' + id);
    if (del.value === '0') {
        del.value = '1';
        tr.classList.add('deleted-row');
        btn.innerHTML = '↩';
        btn.title = 'Restore';
        btn.style.color = '#059669';
        tr.querySelectorAll('input[type=number]').forEach(i => i.disabled = true);
    } else {
        del.value = '0';
        tr.classList.remove('deleted-row');
        btn.innerHTML = '×';
        btn.title = 'Remove';
        btn.style.color = '';
        tr.querySelectorAll('.edit-qty').forEach(i => {
            if (!i.hasAttribute('data-imei-locked')) i.disabled = false;
        });
        tr.querySelectorAll('.edit-price').forEach(i => i.disabled = false);
    }
    recalcPurchase();
}

function addNewItemRow() {
    newRowCount++;
    const n = newRowCount;
    const tr = document.createElement('tr');
    tr.id = 'newrow_' + n;
    tr.className = 'new-row';
    tr.dataset.newRow = String(n);
    tr.innerHTML =
        '<td class="pe-row-num">+</td>' +
        '<td style="position:relative;">' +
            '<input type="text" class="pe-cell-input new-item-search" style="max-width:none;text-align:left;font-weight:500;"' +
                ' id="newSearch_' + n + '" placeholder="Search item…" autocomplete="off">' +
            '<input type="hidden" name="new_items[' + n + '][item_id]" id="newItemId_' + n + '">' +
            '<input type="hidden" name="new_items[' + n + '][imeis]" value="">' +
            '<div id="newItemLabel_' + n + '" class="pe-item-name" style="display:none;"></div>' +
            '<div class="pe-autocomplete" id="newDrop_' + n + '" style="display:none;"></div>' +
        '</td>' +
        '<td class="text-center"><input type="number" name="new_items[' + n + '][quantity]" id="newQty_' + n + '" value="1" min="1" class="pe-cell-input new-qty"></td>' +
        '<td class="text-end"><input type="number" name="new_items[' + n + '][unit_price]" id="newPrice_' + n + '" value="" step="0.001" min="0" class="pe-cell-input price new-price"></td>' +
        '<td class="text-end pe-row-total new-total" id="newTotal_' + n + '">—</td>' +
        '<td class="text-center"><button type="button" class="pe-btn-remove" data-new-row="' + n + '" title="Remove">×</button></td>';
    document.getElementById('itemsTbody').appendChild(tr);
    document.getElementById('newSearch_' + n).focus();
}

function removeNewRow(n) {
    document.getElementById('newrow_' + n)?.remove();
    recalcPurchase();
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
                    '<div class="pe-autocomplete-item" data-n="' + n + '" data-id="' + it.id + '"' +
                    ' data-name="' + it.name.replace(/"/g, '&quot;') + '"' +
                    ' data-price="' + parseFloat(it.purchase_price || it.sale_price || 0).toFixed(3) + '">' +
                    '<strong>' + it.name + '</strong>' +
                    (it.sku ? ' <small style="color:#94a3b8;">· ' + it.sku + '</small>' : '') +
                    '<small style="float:right;color:#6366f1;font-weight:700;">' + currency + ' ' + parseFloat(it.purchase_price || it.sale_price || 0).toFixed(3) + '</small>' +
                    '</div>'
                ).join('');
                drop.style.display = 'block';
            });
    }, 250);
}

function selectNewItem(n, id, name, price) {
    document.getElementById('newItemId_' + n).value = id;
    document.getElementById('newSearch_' + n).value = name;
    document.getElementById('newSearch_' + n).style.display = 'none';
    document.getElementById('newPrice_' + n).value = parseFloat(price).toFixed(3);
    const label = document.getElementById('newItemLabel_' + n);
    if (label) { label.textContent = name; label.style.display = ''; }
    document.getElementById('newDrop_' + n).style.display = 'none';
    const tr = document.getElementById('newrow_' + n);
    if (tr) tr.dataset.itemId = String(id);
    recalcPurchase();
}

function recalcPurchase() {
    var subtotal = 0;
    document.querySelectorAll('.edit-qty').forEach(function(qEl) {
        const rowId = qEl.getAttribute('data-row');
        const del   = document.getElementById('del_' + rowId);
        if (del && del.value === '1') return;
        const price = parseFloat(document.querySelector('.edit-price[data-row="' + rowId + '"]')?.value) || 0;
        const qty   = parseFloat(qEl.value) || 0;
        const total = qty * price;
        subtotal += total;
        const el = document.getElementById('rowTotal_' + rowId);
        if (el) el.textContent = currency + ' ' + total.toFixed(3);
    });
    document.querySelectorAll('.new-qty').forEach(function(qEl) {
        const n     = qEl.closest('tr').id.replace('newrow_', '');
        const price = parseFloat(document.getElementById('newPrice_' + n)?.value) || 0;
        const qty   = parseFloat(qEl.value) || 0;
        const total = qty * price;
        subtotal += total;
        const el = document.getElementById('newTotal_' + n);
        if (el) el.textContent = total > 0 ? currency + ' ' + total.toFixed(3) : '—';
    });
    const grand = Math.max(0, subtotal - editDiscount);
    document.getElementById('editSubtotal').textContent = currency + ' ' + subtotal.toFixed(3);
    document.getElementById('leftSubtotal').textContent = currency + ' ' + subtotal.toFixed(3);
    document.getElementById('newGrandTotal').textContent = currency + ' ' + grand.toFixed(3);
    const balEl = document.getElementById('newBalance');
    if (balEl) balEl.textContent = currency + ' ' + Math.max(0, grand - paidAmount).toFixed(3);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-qty[data-row]').forEach(function(el) {
        if (el.disabled) el.setAttribute('data-imei-locked', '1');
    });
    recalcPurchase();

    document.getElementById('itemsTbody').addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-del-row');
        if (delBtn) { toggleDelete(parseInt(delBtn.dataset.rowId, 10), delBtn); return; }
        const newDel = e.target.closest('.pe-btn-remove[data-new-row]');
        if (newDel) removeNewRow(parseInt(newDel.dataset.newRow, 10));
    });
    document.getElementById('itemsTbody').addEventListener('input', function(e) {
        if (e.target.classList.contains('new-item-search')) {
            searchNewItem(e.target.id.replace('newSearch_', ''), e.target.value);
        }
        if (e.target.classList.contains('edit-qty') || e.target.classList.contains('edit-price') ||
            e.target.classList.contains('new-qty') || e.target.classList.contains('new-price')) {
            recalcPurchase();
        }
    });
    document.getElementById('itemsTbody').addEventListener('blur', function(e) {
        if (e.target.classList.contains('new-item-search')) {
            const n = e.target.id.replace('newSearch_', '');
            setTimeout(function() {
                const d = document.getElementById('newDrop_' + n);
                if (d) d.style.display = 'none';
            }, 200);
        }
    }, true);
    document.getElementById('itemsTbody').addEventListener('mousedown', function(e) {
        const item = e.target.closest('.pe-autocomplete-item');
        if (!item) return;
        e.preventDefault();
        selectNewItem(parseInt(item.dataset.n, 10), parseInt(item.dataset.id, 10), item.dataset.name, item.dataset.price);
    });
    document.getElementById('btnAddNewItemRow').addEventListener('click', function() { addNewItemRow(); });
    document.getElementById('btnAddNewItemRow').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); addNewItemRow(); }
    });
});
</script>
