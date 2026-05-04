<?php function editMoney($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); } ?>

<style>
.edit-tbl{width:100%;border-collapse:collapse;font-size:0.85rem;}
.edit-tbl th{padding:9px 10px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
.edit-tbl td{border-bottom:1px solid #f1f5f9;padding:6px 8px;vertical-align:middle;}
.edit-tbl tbody tr:hover{background:#f8faff;}
.edit-tbl tr.deleted-row{opacity:0.35;background:#fff5f5 !important;}
.edit-tbl tr.deleted-row td{text-decoration:line-through;}
.edit-tbl tr.new-row{background:#f0fdf4;}
.btn-del-row{background:none;border:none;color:#c7d2fe;cursor:pointer;font-size:1.1rem;padding:2px 6px;border-radius:4px;transition:color .15s;}
.btn-del-row:hover{color:#dc2626;background:#fef2f2;}
.btn-restore-row{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:0.8rem;padding:2px 6px;border-radius:4px;}
.btn-restore-row:hover{color:#059669;}
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;cursor:pointer;border-top:2px dashed #c7d2fe;color:#94a3b8;font-size:0.82rem;font-weight:600;transition:all .15s;background:#fff;}
.add-row-strip:hover{background:#f5f7ff;color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:#6366f1;}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:200px;overflow-y:auto;margin-top:4px;}
.autocomplete-item{padding:8px 12px;cursor:pointer;font-size:0.82rem;border-bottom:1px solid #f8fafc;}
.autocomplete-item:hover{background:#f8faff;}
.edit-imei-list{margin-top:6px;padding:6px 8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;}
.edit-imei-list code{background:transparent;padding:0;font-size:0.78rem;color:#0f172a;}
</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=sales&action=detail&id=<?= $editSale['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Edit: <?= $editSale['invoice_no'] ?></h1>
    <span class="badge ms-2 badge-<?= $editSale['status'] ?> px-2 py-1" style="border-radius:6px;"><?= ucfirst($editSale['status']) ?></span>
</div>

<form method="POST" action="?page=sales&action=update&id=<?= $editSale['id'] ?>" id="editSaleForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $editSale['id'] ?>">

<div class="row g-3">

    <!-- Left: Edit Details -->
    <div class="col-md-5">
        <div class="card" style="border-radius:14px;overflow:hidden;">
            <div class="card-header" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <i class="bi bi-pencil-square me-2" style="color:#d97706;"></i>Edit Details
            </div>
            <div class="card-body" style="padding:1.5rem;">

                <div class="mb-3">
                    <label class="form-label fw-600">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($editSale['date']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Customer</label>
                    <div style="background:var(--bg-body);border:1.5px solid var(--border-color);border-radius:8px;padding:10px 14px;color:var(--text-muted);">
                        <i class="bi bi-lock-fill me-1" style="font-size:0.7rem;"></i>
                        <?= htmlspecialchars($editSale['party_name']) ?>
                        <?php if ($editSale['party_phone']): ?><small class="ms-2"><?= htmlspecialchars($editSale['party_phone']) ?></small><?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Warehouse</label>
                    <div style="background:var(--bg-body);border:1.5px solid var(--border-color);border-radius:8px;padding:10px 14px;color:var(--text-muted);">
                        <i class="bi bi-lock-fill me-1" style="font-size:0.7rem;"></i>
                        <?= htmlspecialchars($editSale['warehouse_name'] ?? '—') ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Overall Discount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= APP_CURRENCY ?></span>
                        <input type="number" name="discount" class="form-control" step="0.001" min="0"
                               value="<?= number_format($editSale['discount'], DECIMAL_PLACES, '.', '') ?>"
                               id="editDiscount" oninput="recalcTotal()">
                    </div>
                    <small class="text-muted">Subtotal: <span id="leftSubtotal"><?= editMoney($editSale['subtotal']) ?></span></small>
                </div>

                <div class="mb-3" style="background:linear-gradient(135deg,#eff6ff,#eef2ff);border:2px solid #c7d2fe;border-radius:10px;padding:12px 18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.78rem;color:#4338ca;font-weight:700;text-transform:uppercase;">New Grand Total</span>
                        <span id="newGrandTotal" style="font-size:1.3rem;font-weight:800;color:#1e3a5f;"><?= editMoney($editSale['grand_total']) ?></span>
                    </div>
                    <?php if ((float)$editSale['paid_amount'] > 0): ?>
                    <div style="display:flex;justify-content:space-between;margin-top:6px;padding-top:6px;border-top:1px dashed #c7d2fe;">
                        <span style="font-size:0.75rem;color:var(--text-muted);">Already Paid</span>
                        <span style="font-weight:600;color:var(--success);"><?= editMoney($editSale['paid_amount']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:4px;">
                        <span style="font-size:0.75rem;color:var(--text-muted);">New Balance</span>
                        <span id="newBalance" style="font-weight:700;color:#dc2626;"><?= editMoney($editSale['balance']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-600">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes..."><?= htmlspecialchars($editSale['notes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="?page=sales&action=detail&id=<?= $editSale['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"
                        onclick="document.getElementById('printAfterSave').value='0';document.getElementById('editPrintMode').value='0';">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <button type="submit" class="btn btn-outline-primary"
                        onclick="document.getElementById('printAfterSave').value='1';document.getElementById('editPrintMode').value='1';">
                        <i class="bi bi-printer me-1"></i> Save & Print
                    </button>
                    <button type="submit" class="btn"
                        style="background:rgba(5,150,105,0.12);color:#047857;border:1px solid rgba(5,150,105,0.35);"
                        onclick="document.getElementById('printAfterSave').value='1';document.getElementById('editPrintMode').value='2';">
                        <i class="bi bi-receipt me-1"></i> Save & Thermal
                    </button>
                </div>
                <input type="hidden" name="print_after_save" id="printAfterSave" value="0">
                <input type="hidden" name="print_mode" id="editPrintMode" value="0">
            </div>
        </div>
    </div>

    <!-- Right: Items (fully editable) -->
    <div class="col-md-7">
        <div class="card" style="border-radius:14px;overflow:hidden;">
            <div class="card-header d-flex justify-content-between align-items-center" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <span><i class="bi bi-box-seam me-2" style="color:var(--primary);"></i>Invoice Items</span>
                <small style="color:#059669;font-weight:600;">editable — add / remove / change</small>
            </div>
            <div class="card-body p-0">
                <table class="edit-tbl">
                    <thead>
                        <tr>
                            <th style="width:28px;">#</th>
                            <th>Item</th>
                            <th class="text-center" style="width:80px;">Qty</th>
                            <th class="text-end" style="width:110px;">Price</th>
                            <th class="text-end" style="width:110px;">Total</th>
                            <th style="width:36px;"></th>
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
                            <td class="text-muted row-num"><?= $i+1 ?></td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></span>
                                <?php if ($item['sku']): ?><br><small class="text-muted"><?= $item['sku'] ?></small><?php endif; ?>
                                <?php if (!empty($item['has_imei'])): ?>
                                    <?php if ($needsImei): ?>
                                    <br><a href="?page=sales&action=scanItemImeis&id=<?= $editSale['id'] ?>&sale_item_id=<?= $item['id'] ?>"
                                          style="display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;color:#d97706;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);padding:2px 8px;border-radius:5px;text-decoration:none;margin-top:4px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Scan IMEIs (<?= $imeiCount ?>/<?= $item['quantity'] ?>)
                                    </a>
                                    <?php else: ?>
                                    <br><span style="display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:700;color:#16a34a;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);padding:2px 8px;border-radius:5px;margin-top:4px;">
                                        <i class="bi bi-check-circle-fill"></i> <?= $imeiCount ?> IMEI<?= $imeiCount !== 1 ? 's' : '' ?>
                                    </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($lineImeis): ?>
                                <div class="edit-imei-list">
                                    <div style="font-size:0.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.03em;margin-bottom:4px;">IMEI / Serial</div>
                                    <?php foreach ($lineImeis as $im): ?>
                                    <code><?= htmlspecialchars($im) ?></code>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <input type="hidden" name="items[<?= $item['id'] ?>][sale_item_id]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="items[<?= $item['id'] ?>][deleted]" id="del_<?= $item['id'] ?>" value="0">
                            </td>
                            <td class="text-center">
                                <input type="number" name="items[<?= $item['id'] ?>][quantity]"
                                       value="<?= $item['quantity'] ?>" min="1"
                                       class="form-control form-control-sm text-center edit-qty"
                                       style="width:68px;margin:0 auto;font-weight:600;"
                                       data-row="<?= $item['id'] ?>" oninput="recalcItems()">
                            </td>
                            <td class="text-end">
                                <input type="number" name="items[<?= $item['id'] ?>][unit_price]"
                                       value="<?= number_format((float)$item['unit_price'], 3, '.', '') ?>"
                                       step="0.001" min="0"
                                       class="form-control form-control-sm text-end edit-price"
                                       style="width:100px;margin-left:auto;font-weight:600;"
                                       data-row="<?= $item['id'] ?>" oninput="recalcItems()">
                            </td>
                            <td class="text-end fw-semibold row-total" id="rowTotal_<?= $item['id'] ?>"><?= editMoney($item['total']) ?></td>
                            <td class="text-center">
                                <button type="button" class="btn-del-row" title="Remove item"
                                    onclick="toggleDelete(<?= $item['id'] ?>, this)">×</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end text-muted" style="padding:8px 10px;font-size:0.75rem;font-weight:600;text-transform:uppercase;">Subtotal</td>
                            <td class="text-end fw-semibold" id="editSubtotal" style="padding:8px 10px;"><?= editMoney($editSale['subtotal']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Add new item — redirect to dedicated page with IMEI scan -->
                <a class="add-row-strip" href="?page=sales&action=addItem&id=<?= $editSale['id'] ?>" style="text-decoration:none;">
                    <span class="plus-c"><i class="bi bi-plus"></i></span>
                    Add another item (with IMEI scan)
                </a>
            </div>
        </div>
    </div>
</div>
</form>

<script>
var paidAmount  = <?= (float)$editSale['paid_amount'] ?>;
var warehouseId = <?= (int)$editSale['warehouse_id'] ?>;
var currency    = '<?= APP_CURRENCY ?>';
var newRowCount = 0;
var searchTimers = {};

// ── Delete / restore existing row ─────────────────────────────────────────────
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
}

// ── Add new item row ──────────────────────────────────────────────────────────
function addNewItemRow() {
    newRowCount++;
    const n   = newRowCount;
    const tr  = document.createElement('tr');
    tr.id     = 'newrow_' + n;
    tr.className = 'new-row';
    tr.innerHTML = `
        <td class="text-muted">•</td>
        <td style="position:relative;">
            <input type="text" class="form-control form-control-sm new-item-search"
                id="newSearch_${n}" placeholder="Search item..."
                autocomplete="off"
                oninput="searchNewItem(${n}, this.value)"
                onblur="setTimeout(()=>hideNewDrop(${n}),200)">
            <input type="hidden" name="new_items[${n}][item_id]" id="newItemId_${n}">
            <div class="autocomplete-box" id="newDrop_${n}" style="display:none;"></div>
        </td>
        <td class="text-center">
            <input type="number" name="new_items[${n}][quantity]" id="newQty_${n}"
                value="1" min="1"
                class="form-control form-control-sm text-center new-qty"
                style="width:68px;margin:0 auto;font-weight:600;"
                oninput="recalcItems()">
        </td>
        <td class="text-end">
            <input type="number" name="new_items[${n}][unit_price]" id="newPrice_${n}"
                value="" step="0.001" min="0"
                class="form-control form-control-sm text-end new-price"
                style="width:100px;margin-left:auto;font-weight:600;"
                oninput="recalcItems()">
        </td>
        <td class="text-end fw-semibold new-total" id="newTotal_${n}">—</td>
        <td class="text-center">
            <button type="button" class="btn-del-row" title="Remove"
                onclick="removeNewRow(${n})">×</button>
        </td>`;
    document.getElementById('itemsTbody').appendChild(tr);
    document.getElementById('newSearch_' + n).focus();
}

function removeNewRow(n) {
    document.getElementById('newrow_' + n)?.remove();
    recalcItems();
}

// ── Item search for new rows ──────────────────────────────────────────────────
function searchNewItem(n, q) {
    clearTimeout(searchTimers['new_' + n]);
    const drop = document.getElementById('newDrop_' + n);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers['new_' + n] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${warehouseId}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = items.map(it => `
                    <div class="autocomplete-item" onmousedown="selectNewItem(${n},${it.id},'${it.name.replace(/'/g,"\\'")}',${parseFloat(it.sale_price||0).toFixed(3)})">
                        <strong>${it.name}</strong>
                        ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <small style="float:right;color:#6366f1;font-weight:600;">${currency} ${parseFloat(it.sale_price||0).toFixed(3)}</small>
                        <br><small style="color:#94a3b8;">Stock: ${it.current_stock ?? it.stock ?? 0}</small>
                    </div>`).join('');
                drop.style.display = 'block';
            });
    }, 250);
}

function selectNewItem(n, id, name, price) {
    document.getElementById('newItemId_' + n).value   = id;
    document.getElementById('newSearch_' + n).value   = name;
    document.getElementById('newPrice_' + n).value    = parseFloat(price).toFixed(3);
    document.getElementById('newDrop_' + n).style.display = 'none';
    recalcItems();
}

function hideNewDrop(n) {
    const d = document.getElementById('newDrop_' + n);
    if (d) d.style.display = 'none';
}

// ── Recalc totals ─────────────────────────────────────────────────────────────
function recalcItems() {
    var newSubtotal = 0;

    // Existing rows
    document.querySelectorAll('.edit-qty').forEach(function(qEl) {
        const rowId    = qEl.getAttribute('data-row');
        const delInput = document.getElementById('del_' + rowId);
        if (delInput && delInput.value === '1') return; // skip deleted
        const priceEl  = document.querySelector(`.edit-price[data-row="${rowId}"]`);
        const qty      = parseFloat(qEl.value) || 0;
        const price    = parseFloat(priceEl?.value) || 0;
        const rowTotal = qty * price;
        newSubtotal   += rowTotal;
        const totalEl  = document.getElementById('rowTotal_' + rowId);
        if (totalEl) totalEl.textContent = currency + ' ' + rowTotal.toFixed(3);
    });

    // New rows
    document.querySelectorAll('.new-qty').forEach(function(qEl) {
        const n      = qEl.closest('tr').id.replace('newrow_', '');
        const price  = parseFloat(document.getElementById('newPrice_' + n)?.value) || 0;
        const qty    = parseFloat(qEl.value) || 0;
        const total  = qty * price;
        newSubtotal += total;
        const totalEl = document.getElementById('newTotal_' + n);
        if (totalEl) totalEl.textContent = total > 0 ? currency + ' ' + total.toFixed(3) : '—';
    });

    document.getElementById('editSubtotal').textContent  = currency + ' ' + newSubtotal.toFixed(3);
    document.getElementById('leftSubtotal').textContent  = currency + ' ' + newSubtotal.toFixed(3);
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

document.addEventListener('DOMContentLoaded', recalcItems);
</script>
