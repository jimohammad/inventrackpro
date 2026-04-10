<style>
.os-wrap{display:flex;flex-direction:column;gap:0;}
.os-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px 12px 0 0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.os-topbar .os-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.os-meta-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-top:none;}
.items-card{border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:hidden;}
.items-card-header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;}
.items-card-header span{font-size:0.8rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:6px;}
table.os-tbl{width:100%;border-collapse:collapse;font-size:0.83rem;}
table.os-tbl th{padding:9px 10px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.os-tbl td{border-bottom:1px solid #cbd5e1;padding:5px 6px;vertical-align:middle;}
table.os-tbl tbody tr{background:#fff;transition:background 0.1s;}
table.os-tbl tbody tr:hover{background:#f8faff;}
table.os-tbl input{border:none;outline:none;background:transparent;width:100%;font-size:0.83rem;color:#1e293b;padding:3px;}
table.os-tbl input:focus{background:#eff6ff;border-radius:4px;}
table.os-tbl tfoot tr{background:#f8f9ff;}
.item-search-wrap{position:relative;flex:1;min-width:220px;}
.item-search-wrap .search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:0.9rem;pointer-events:none;}
.item-search-wrap input{width:100%;padding:7px 10px 7px 32px;border:2px solid #e0e7ff;border-radius:8px;font-size:0.83rem;color:#1a1a2e;background:#fafbff;outline:none;}
.item-search-wrap input:focus{border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.autocomplete-box{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:220px;overflow-y:auto;margin-top:4px;}
.autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;transition:background 0.1s;}
.autocomplete-item:last-child{border-bottom:none;}
.autocomplete-item:hover{background:#f8faff;}
.add-row-strip{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;cursor:pointer;border-top:2px dashed #c7d2fe;color:#94a3b8;font-size:0.82rem;font-weight:600;transition:all 0.15s;background:#fff;}
.add-row-strip:hover{background:#f5f7ff;color:#6366f1;border-top-color:#6366f1;}
.add-row-strip .plus-c{width:22px;height:22px;border-radius:50%;background:rgba(99,102,241,0.12);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:#6366f1;flex-shrink:0;}
.os-bottom{border:1px solid #e5e7eb;border-top:none;background:#fff;border-radius:0 0 12px 12px;overflow:hidden;}
.os-totals{padding:14px 20px;background:linear-gradient(135deg,#f8faff,#f5f7ff);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border-top:2px solid #e0e7ff;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);}
.btn-save-os{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(59,130,246,0.4);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.btn-save-os:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,0.5);}
</style>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Opening Stock</h1>
        <p class="page-subtitle">Set starting inventory quantities and cost prices per warehouse</p>
    </div>
</div>

<!-- Warning banner -->
<div style="background:#fef3c7;border:1.5px solid #fbbf24;border-radius:10px;padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:1.2rem;flex-shrink:0;"></i>
    <div style="font-size:0.83rem;color:#92400e;">
        <strong>Important:</strong> Opening stock should only be entered once at the start. After that, use Purchase Invoices to add incoming stock. Saving updates the stock balance directly.
    </div>
</div>

<!-- Warehouse selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="openingstock">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Select Warehouse / Branch</label>
                <select name="warehouse_id" class="form-select" id="warehouseSelect" onchange="this.form.submit()">
                    <option value="">-- Select Branch --</option>
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $warehouseId == $wh['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($warehouseId && !empty($existing)): ?>
            <div class="col-md-4">
                <div style="background:#d1fae5;border:1.5px solid #6ee7b7;border-radius:8px;padding:8px 14px;font-size:0.82rem;color:#065f46;">
                    <i class="bi bi-check-circle me-1"></i>
                    <?= count($existing) ?> items already saved · <?= APP_CURRENCY ?> <?= number_format($totalValue, DECIMAL_PLACES) ?> total value
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($warehouseId): ?>

<!-- Existing saved items -->
<?php if (!empty($existing)): ?>
<div class="card mb-4">
    <div class="card-body p-0">
        <div style="padding:10px 20px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-bottom:1px solid #a7f3d0;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:0.8rem;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:0.5px;">
                <i class="bi bi-check2-all me-1"></i> Saved Opening Stock
            </span>
            <span style="font-size:0.78rem;color:#059669;font-weight:600;"><?= count($existing) ?> items</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Item</th>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">SKU</th>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:center;">Qty</th>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Cost Price</th>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Total Value</th>
                    <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:center;">Date</th>
                    <th style="padding:9px 14px;background:#f8fafc;border-bottom:2px solid #e2e8f0;width:40px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing as $row): ?>
                <tr style="background:#fff;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;"><?= htmlspecialchars($row['item_name']) ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:#94a3b8;"><?= htmlspecialchars($row['sku'] ?? '—') ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;font-weight:700;color:#4338ca;"><?= $row['quantity'] ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;color:#475569;"><?= number_format($row['cost_price'], DECIMAL_PLACES) ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#6366f1;"><?= number_format($row['total_value'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;color:#94a3b8;font-size:0.78rem;"><?= date('d M Y', strtotime($row['date'])) ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <form method="POST" action="?page=openingstock&action=deleteItem" style="display:inline;"
                              onsubmit="return confirm('Remove this item from opening stock? This will reduce stock quantity.')">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="warehouse_id" value="<?= $warehouseId ?>">
                            <button type="submit"
                               style="background:none;border:none;cursor:pointer;color:#cbd5e1;font-size:1.1rem;"
                               onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#cbd5e1'">×</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                    <td colspan="4" style="padding:11px 14px;font-weight:700;color:#4338ca;">Total Stock Value</td>
                    <td style="padding:11px 14px;text-align:right;font-size:1rem;font-weight:800;color:#6366f1;"><?= number_format($totalValue, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add new items form -->
<form method="POST" action="?page=openingstock&action=store" id="osForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="warehouse_id" value="<?= $warehouseId ?>">
    <input type="hidden" name="date" id="osDate" value="<?= date('Y-m-d') ?>">

<div class="os-wrap">

    <!-- Top bar -->
    <div class="os-topbar">
        <div class="os-title">
            <i class="bi bi-box-seam"></i> Add Opening Stock Items
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;">
                <span style="color:rgba(255,255,255,0.6);margin-bottom:2px;">Date</span>
                <input type="date" id="osDatePicker" value="<?= date('Y-m-d') ?>"
                    onchange="document.getElementById('osDate').value=this.value"
                    style="border:1.5px solid rgba(255,255,255,0.3);border-radius:7px;padding:3px 10px;font-size:0.8rem;color:#fff;background:rgba(255,255,255,0.15);outline:none;font-weight:600;">
            </div>
        </div>
    </div>

    <!-- Item search bar -->
    <div class="os-meta-bar">
        <div class="item-search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="itemSearch" placeholder="Search item by name or SKU to add..."
                autocomplete="off" oninput="searchItems(this.value)">
            <div class="autocomplete-box" id="itemDropdown" style="display:none;"></div>
        </div>
        <span id="osQtyBadge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:20px;font-weight:700;font-size:0.82rem;">0 items</span>
    </div>

    <!-- Items table -->
    <div class="items-card">
        <div class="items-card-header">
            <span><i class="bi bi-list-ul"></i> Items to Add</span>
        </div>
        <table class="os-tbl">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">#</th>
                    <th>Item Name</th>
                    <th style="width:100px;text-align:center;">SKU</th>
                    <th style="width:80px;text-align:center;">Current Stock</th>
                    <th style="width:90px;text-align:center;">Qty to Add</th>
                    <th style="width:130px;text-align:right;">Cost Price</th>
                    <th style="width:130px;text-align:right;">Total Value</th>
                    <th style="width:36px;text-align:center;"></th>
                </tr>
            </thead>
            <tbody id="osTbody">
                <tr id="emptyRow">
                    <td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">
                        <i class="bi bi-search" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                        Search and add items above
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td style="text-align:center;font-weight:800;color:#4338ca;font-size:0.9rem;" id="osTotalQty">0</td>
                    <td></td>
                    <td style="text-align:right;font-weight:800;color:#6366f1;font-size:0.9rem;padding-right:10px;" id="osTotalVal">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="add-row-strip" onclick="document.getElementById('itemSearch').focus()">
            <span class="plus-c"><i class="bi bi-plus"></i></span>
            Click here or search above to add another item
        </div>
    </div>

    <!-- Save bar -->
    <div class="os-bottom">
        <div class="save-bar">
            <a href="?page=stock" style="padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;text-decoration:none;display:inline-flex;align-items:center;">
                Cancel
            </a>
            <button type="submit" class="btn-save-os" id="osSaveBtn" disabled>
                <i class="bi bi-check-lg"></i> Save Opening Stock
            </button>
        </div>
    </div>

</div>
</form>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-building" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">Please select a warehouse above to continue.</p>
    </div>
</div>
<?php endif; ?>

<script>
const warehouseId = <?= (int)$warehouseId ?>;
let rowCount = 0;

// Item search autocomplete
let searchTimer;
function searchItems(q) {
    clearTimeout(searchTimer);
    const drop = document.getElementById('itemDropdown');
    if (q.length < 1) { drop.style.display = 'none'; return; }
    searchTimer = setTimeout(() => {
        fetch(`?page=openingstock&action=searchItems&q=${encodeURIComponent(q)}&warehouse_id=${warehouseId}`)
            .then(r => r.json()).then(items => {
                if (!items.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = items.map(it => `
                    <div class="autocomplete-item" onclick='addRow(${JSON.stringify(it)})'>
                        <span style="font-weight:600;">${it.name}</span>
                        <small style="color:#94a3b8;margin-left:6px;">${it.sku || ''}</small>
                        <small style="color:#94a3b8;float:right;">Stock: ${it.current_stock}</small>
                    </div>
                `).join('');
                drop.style.display = 'block';
            });
    }, 250);
}

document.getElementById('itemSearch').addEventListener('blur', () => {
    setTimeout(() => document.getElementById('itemDropdown').style.display = 'none', 200);
});

function addRow(item) {
    document.getElementById('itemDropdown').style.display = 'none';
    document.getElementById('itemSearch').value = '';

    // Prevent duplicate
    if (document.querySelector(`input[name="items[${item.id}][item_id]"]`)) {
        alert(`"${item.name}" is already in the list.`);
        return;
    }

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();

    rowCount++;
    const rid = item.id;
    const tbody = document.getElementById('osTbody');
    const tr = document.createElement('tr');
    tr.id = `row_${rid}`;
    tr.innerHTML = `
        <td style="text-align:center;color:#94a3b8;padding:5px 6px;">${rowCount}</td>
        <td style="padding:5px 6px;font-weight:600;color:#1e293b;">
            ${item.name}
            <input type="hidden" name="items[${rid}][item_id]" value="${rid}">
        </td>
        <td style="padding:5px 6px;text-align:center;font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:#94a3b8;">${item.sku || '—'}</td>
        <td style="padding:5px 6px;text-align:center;color:#94a3b8;">${item.current_stock}</td>
        <td style="padding:5px 6px;text-align:center;">
            <input type="number" name="items[${rid}][quantity]" id="qty_${rid}"
                value="1" min="1" style="text-align:center;width:70px;"
                oninput="calcRow('${rid}')">
        </td>
        <td style="padding:5px 6px;text-align:right;">
            <input type="number" name="items[${rid}][cost_price]" id="price_${rid}"
                value="${parseFloat(item.purchase_price || 0).toFixed(3)}" min="0" step="0.001"
                style="text-align:right;width:110px;"
                oninput="calcRow('${rid}')">
        </td>
        <td style="padding:5px 6px;text-align:right;font-weight:700;color:#6366f1;padding-right:8px;" id="val_${rid}">
            ${(parseFloat(item.purchase_price || 0)).toFixed(3)}
        </td>
        <td style="padding:5px 6px;text-align:center;">
            <button type="button" onclick="removeRow('${rid}')"
                style="background:none;border:none;color:#c7d2fe;cursor:pointer;font-size:1.1rem;"
                onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#c7d2fe'">×</button>
        </td>
    `;
    tbody.appendChild(tr);
    calcTotals();
    document.getElementById('itemSearch').focus();
}

function removeRow(rid) {
    document.getElementById(`row_${rid}`)?.remove();
    if (!document.getElementById('osTbody').children.length) {
        document.getElementById('osTbody').innerHTML = `
            <tr id="emptyRow"><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">
                <i class="bi bi-search" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                Search and add items above
            </td></tr>`;
    }
    calcTotals();
}

function calcRow(rid) {
    const qty   = parseFloat(document.getElementById(`qty_${rid}`)?.value || 0);
    const price = parseFloat(document.getElementById(`price_${rid}`)?.value || 0);
    const val   = qty * price;
    const el    = document.getElementById(`val_${rid}`);
    if (el) el.textContent = val.toFixed(3);
    calcTotals();
}

function calcTotals() {
    let totalQty = 0, totalVal = 0;
    document.querySelectorAll('[id^="qty_"]').forEach(el => {
        const rid = el.id.replace('qty_', '');
        totalQty += parseInt(el.value || 0);
        totalVal += parseFloat(el.value || 0) * parseFloat(document.getElementById(`price_${rid}`)?.value || 0);
    });
    document.getElementById('osTotalQty').textContent = totalQty;
    document.getElementById('osTotalVal').textContent = totalVal.toFixed(3);
    document.getElementById('osQtyBadge').textContent = `${document.querySelectorAll('[id^="qty_"]').length} items`;

    const saveBtn = document.getElementById('osSaveBtn');
    saveBtn.disabled = totalQty === 0;
}
</script>
