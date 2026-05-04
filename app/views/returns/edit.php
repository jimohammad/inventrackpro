<?php function retMoney($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); } ?>

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
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;cursor:pointer;border-top:2px dashed #c7d2fe;color:#94a3b8;font-size:0.82rem;font-weight:600;transition:all .15s;background:#fff;}
.add-row-strip:hover{background:#f5f7ff;color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:#6366f1;}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:200px;overflow-y:auto;margin-top:4px;}
.autocomplete-item{padding:8px 12px;cursor:pointer;font-size:0.82rem;border-bottom:1px solid #f8fafc;}
.autocomplete-item:hover{background:#f8faff;}
.imei-edit-box{width:160px;min-height:58px;max-height:84px;resize:vertical;font-size:0.74rem;line-height:1.2;font-family:Consolas,monospace;border:1px solid #cbd5e1;border-radius:6px;padding:6px;background:#fff;}
</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=returns&action=detail&id=<?= $editReturn['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Edit: <?= $editReturn['return_no'] ?></h1>
    <span class="badge ms-2 px-2 py-1" style="background:rgba(16,185,129,0.15);color:#059669;border-radius:6px;"><?= ucfirst($editReturn['status']) ?></span>
</div>

<form method="POST" action="?page=returns&action=update" id="editReturnForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $editReturn['id'] ?>">
    <input type="hidden" name="return_edit_nonce" value="<?= htmlspecialchars($returnEditNonce ?? '') ?>">

<div class="row g-3">

    <!-- Left: Details -->
    <div class="col-md-5">
        <div class="card" style="border-radius:14px;">
            <div class="card-header" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <i class="bi bi-pencil-square me-2" style="color:#d97706;"></i>Edit Details
            </div>
            <div class="card-body" style="padding:1.5rem;">

                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" required value="<?= $editReturn['date'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;">Customer</label>
                    <input type="text" class="form-control" readonly
                           value="<?= htmlspecialchars($editReturn['party_name']) ?>"
                           style="background:#f8fafc;">
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;">Warehouse</label>
                    <input type="text" class="form-control" readonly
                           value="<?= htmlspecialchars($editReturn['warehouse_name'] ?? 'Main Branch') ?>"
                           style="background:#f8fafc;">
                </div>

                <div class="mb-2">
                    <small class="text-muted">Subtotal: <span id="leftSubtotal"><?= retMoney($editReturn['subtotal']) ?></span></small>
                </div>

                <div class="mb-3" style="background:linear-gradient(135deg,#eff6ff,#eef2ff);border:2px solid #c7d2fe;border-radius:10px;padding:12px 18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.78rem;color:#4338ca;font-weight:700;text-transform:uppercase;">Total</span>
                        <span id="newGrandTotal" style="font-size:1.3rem;font-weight:800;color:#1e3a5f;"><?= retMoney($editReturn['grand_total']) ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;">Reason</label>
                    <textarea name="reason" class="form-control" rows="2"
                              placeholder="Optional reason..."><?= htmlspecialchars($editReturn['reason'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="?page=returns&action=detail&id=<?= $editReturn['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary pin-protect">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Items -->
    <div class="col-md-7">
        <div class="card" style="border-radius:14px;overflow:hidden;">
            <div class="card-header d-flex justify-content-between align-items-center" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <span><i class="bi bi-box-seam me-2" style="color:var(--primary);"></i>Return Items</span>
                <small style="color:#059669;font-weight:600;">editable — add / remove / change</small>
            </div>
            <div class="card-body p-0">
                <table class="edit-tbl">
                    <thead>
                        <tr>
                            <th style="width:28px;">#</th>
                            <th>Item</th>
                            <th class="text-center" style="width:180px;">IMEIs</th>
                            <th class="text-center" style="width:80px;">Qty</th>
                            <th class="text-end" style="width:110px;">Price</th>
                            <th class="text-end" style="width:110px;">Total</th>
                            <th style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody">
                        <?php foreach ($editReturn['items'] as $i => $item): ?>
                        <tr id="row_<?= $item['id'] ?>">
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td style="font-weight:500;">
                                <?= htmlspecialchars($item['item_name']) ?>
                                <input type="hidden" name="items[<?= $item['id'] ?>][deleted]" id="del_<?= $item['id'] ?>" value="0">
                            </td>
                            <td class="text-center">
                                <textarea
                                    name="items[<?= $item['id'] ?>][imeis]"
                                    class="imei-edit-box"
                                    placeholder="One IMEI per line"
                                ><?= htmlspecialchars(str_replace('||', "\n", (string)($item['imei_list'] ?? ''))) ?></textarea>
                            </td>
                            <td class="text-center">
                                <input type="number" name="items[<?= $item['id'] ?>][quantity]"
                                       value="<?= (int)$item['quantity'] ?>" min="1"
                                       class="form-control form-control-sm text-center edit-qty"
                                       style="width:68px;margin:0 auto;font-weight:600;"
                                       data-row="<?= $item['id'] ?>" oninput="recalcReturn()">
                            </td>
                            <td class="text-end">
                                <input type="number" name="items[<?= $item['id'] ?>][unit_price]"
                                       value="<?= number_format($item['unit_price'], DECIMAL_PLACES, '.', '') ?>"
                                       step="0.001" min="0"
                                       class="form-control form-control-sm text-end edit-price"
                                       style="width:100px;margin-left:auto;font-weight:600;"
                                       data-row="<?= $item['id'] ?>" oninput="recalcReturn()">
                            </td>
                            <td class="text-end fw-semibold" id="rowTotal_<?= $item['id'] ?>"><?= retMoney($item['total']) ?></td>
                            <td class="text-center">
                                <button type="button" class="btn-del-row" title="Remove item"
                                    onclick="toggleDelete(<?= $item['id'] ?>, this)">×</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold" style="padding:8px 10px;font-size:0.75rem;color:#64748b;text-transform:uppercase;">Subtotal</td>
                            <td class="text-end fw-semibold" id="editSubtotal" style="padding:8px 10px;"><?= retMoney($editReturn['subtotal']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="add-row-strip" onclick="addNewItemRow()">
                    <span class="plus-c"><i class="bi bi-plus"></i></span>
                    Click to add another return item
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<script>
var currency    = '<?= APP_CURRENCY ?>';
var warehouseId = <?= (int)$editReturn['warehouse_id'] ?>;
var newRowCount = 0;
var searchTimers = {};

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

function addNewItemRow() {
    newRowCount++;
    const n  = newRowCount;
    const tr = document.createElement('tr');
    tr.id    = 'newrow_' + n;
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
            <textarea
                name="new_items[${n}][imeis]"
                class="imei-edit-box"
                placeholder="One IMEI per line"></textarea>
        </td>
        <td class="text-center">
            <input type="number" name="new_items[${n}][quantity]" id="newQty_${n}"
                value="1" min="1"
                class="form-control form-control-sm text-center new-qty"
                style="width:68px;margin:0 auto;font-weight:600;"
                oninput="recalcReturn()">
        </td>
        <td class="text-end">
            <input type="number" name="new_items[${n}][unit_price]" id="newPrice_${n}"
                value="" step="0.001" min="0"
                class="form-control form-control-sm text-end new-price"
                style="width:100px;margin-left:auto;font-weight:600;"
                oninput="recalcReturn()">
        </td>
        <td class="text-end fw-semibold new-total" id="newTotal_${n}">—</td>
        <td class="text-center">
            <button type="button" class="btn-del-row" onclick="removeNewRow(${n})">×</button>
        </td>`;
    document.getElementById('itemsTbody').appendChild(tr);
    document.getElementById('newSearch_' + n).focus();
}

function removeNewRow(n) {
    document.getElementById('newrow_' + n)?.remove();
    recalcReturn();
}

function searchNewItem(n, q) {
    clearTimeout(searchTimers['n' + n]);
    const drop = document.getElementById('newDrop_' + n);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimers['n' + n] = setTimeout(() => {
        fetch(`?page=sales&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${warehouseId}`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = items.map(it => `
                    <div class="autocomplete-item" onmousedown="selectNewItem(${n},${it.id},'${it.name.replace(/'/g,"\\'")}',${parseFloat(it.sale_price||0).toFixed(3)})">
                        <strong>${it.name}</strong>
                        ${it.sku ? `<small style="color:#94a3b8;"> · ${it.sku}</small>` : ''}
                        <small style="float:right;color:#6366f1;">${currency} ${parseFloat(it.sale_price||0).toFixed(3)}</small>
                    </div>`).join('');
                drop.style.display = 'block';
            });
    }, 250);
}

function selectNewItem(n, id, name, price) {
    document.getElementById('newItemId_' + n).value  = id;
    document.getElementById('newSearch_' + n).value  = name;
    document.getElementById('newPrice_'  + n).value  = parseFloat(price).toFixed(3);
    document.getElementById('newDrop_'   + n).style.display = 'none';
    recalcReturn();
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
        const price    = parseFloat(document.querySelector(`.edit-price[data-row="${rowId}"]`)?.value) || 0;
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

document.addEventListener('DOMContentLoaded', recalcReturn);
</script>
