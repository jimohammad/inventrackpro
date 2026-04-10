<style>
.tf-page { max-width: 900px; }
.tf-head { display:flex;align-items:center;gap:12px;margin-bottom:20px; }
.tf-head h1 { font-size:1.2rem;font-weight:700;margin:0; }
.tf-back { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;font-size:.85rem; }
.tf-back:hover { border-color:var(--primary);color:var(--primary); }

.tf-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-bottom:16px; }
.tf-label { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.tf-label i { font-size:.8rem; }
.tf-row { display:grid;gap:12px; }
.tf-row-3 { grid-template-columns:1fr 1fr 1fr; }
.tf-row-2 { grid-template-columns:1fr 1fr; }

.tf-field label { display:block;font-size:.72rem;font-weight:600;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px; }
.tf-field select,.tf-field input,.tf-field textarea {
    width:100%;padding:8px 12px;border:1.5px solid var(--border-color);border-radius:8px;
    font-size:.85rem;background:var(--bg-main);color:var(--text-main);outline:none;font-family:inherit;
}
.tf-field select:focus,.tf-field input:focus { border-color:var(--primary); }

/* Items table */
.tf-items-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:10px; }
.tf-items-head span { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px; }
.tf-count { font-size:.72rem;color:var(--text-muted);font-weight:600; }
.tf-tbl { width:100%;border-collapse:collapse; }
.tf-tbl th { font-size:.68rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:6px 8px;border-bottom:1.5px solid var(--border-color); }
.tf-tbl td { padding:6px 8px;border-bottom:1px solid var(--border-color);vertical-align:middle; }
.tf-tbl tr:last-child td { border-bottom:none; }
.tf-tbl .tf-item-select { width:100%;padding:7px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.84rem;background:var(--bg-main);color:var(--text-main);outline:none; }
.tf-tbl .tf-item-select:focus { border-color:var(--primary); }
.tf-tbl .tf-qty { width:70px;padding:7px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.84rem;background:var(--bg-main);color:var(--text-main);outline:none;text-align:center; }
.tf-tbl .tf-qty:focus { border-color:var(--primary); }
.tf-del { width:28px;height:28px;border-radius:6px;border:none;background:rgba(239,68,68,.1);color:#ef4444;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;transition:all .15s; }
.tf-del:hover { background:rgba(239,68,68,.2); }
.tf-row-num { font-size:.75rem;color:var(--text-muted);font-weight:600;text-align:center;width:30px; }

/* Actions */
.tf-actions { display:flex;flex-direction:column;gap:8px; }
.tf-btn-submit { padding:10px 20px;background:var(--primary);border:none;color:#fff;border-radius:10px;font-size:.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px; }
.tf-btn-submit:hover { opacity:.9; }
.tf-btn-cancel { padding:10px 20px;background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:10px;font-size:.85rem;cursor:pointer;text-align:center;text-decoration:none; }
.tf-btn-cancel:hover { border-color:var(--primary);color:var(--primary); }
</style>

<div class="tf-page">
    <div class="tf-head">
        <a href="?page=transfers" class="tf-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-arrow-left-right me-2" style="color:var(--primary);"></i>New Stock Transfer</h1>
    </div>

    <form method="POST" action="?page=transfers&action=store" id="transferForm">
        <?= Auth::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-9">
                <!-- Transfer Details -->
                <div class="tf-card">
                    <div class="tf-label"><i class="bi bi-arrow-left-right"></i> Transfer Details</div>
                    <div class="tf-row tf-row-3" style="margin-bottom:12px;">
                        <div class="tf-field">
                            <label>From Warehouse *</label>
                            <select name="from_warehouse_id" id="fromWh" required>
                                <option value="">Select...</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= Auth::warehouseId() == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tf-field">
                            <label>To Warehouse *</label>
                            <select name="to_warehouse_id" id="toWh" required>
                                <option value="">Select...</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= Auth::warehouseId() != $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tf-field">
                            <label>Date</label>
                            <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="tf-field">
                        <label>Notes</label>
                        <input type="text" name="notes" placeholder="Optional transfer notes...">
                    </div>
                </div>

                <!-- Items -->
                <div class="tf-card">
                    <div class="tf-items-head">
                        <span><i class="bi bi-box-seam me-1"></i> Items to Transfer</span>
                        <span class="tf-count" id="itemCount">0 items</span>
                    </div>
                    <table class="tf-tbl">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Item</th>
                                <th style="width:80px;">Qty</th>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="transferBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-3">
                <div class="tf-card">
                    <div class="tf-actions">
                        <button type="submit" class="tf-btn-submit"><i class="bi bi-check-lg"></i> Transfer</button>
                        <a href="?page=transfers" class="tf-btn-cancel">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
var tRow = 0;
var itemOptions = `<option value="">Select item...</option><?php foreach ($items as $it): ?><option value="<?= $it['id'] ?>"><?= htmlspecialchars(addslashes($it['name'])) ?> (Stock: <?= $it['total_stock'] ?>)</option><?php endforeach; ?>`;

function addRow() {
    tRow++;
    var tr = document.createElement('tr');
    tr.setAttribute('data-row', tRow);
    tr.innerHTML =
        '<td class="tf-row-num">' + tRow + '</td>' +
        '<td><select name="items[' + tRow + '][item_id]" class="tf-item-select" onchange="onItemSelect(this)">' + itemOptions + '</select></td>' +
        '<td><input type="number" name="items[' + tRow + '][quantity]" class="tf-qty" min="1" value="1" required></td>' +
        '<td><button type="button" class="tf-del" onclick="removeRow(this)" title="Remove"><i class="bi bi-x"></i></button></td>';
    document.getElementById('transferBody').appendChild(tr);
    updateCount();
    return tr;
}

function removeRow(btn) {
    var tbody = document.getElementById('transferBody');
    btn.closest('tr').remove();
    // Renumber rows
    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function(r, i) { r.querySelector('.tf-row-num').textContent = i + 1; });
    updateCount();
    // Ensure at least one empty row exists
    if (tbody.querySelectorAll('tr').length === 0) addRow();
}

function onItemSelect(sel) {
    if (!sel.value) return;
    // If this is the last row and an item was selected, auto-add next empty row
    var tbody = document.getElementById('transferBody');
    var rows = tbody.querySelectorAll('tr');
    var lastRow = rows[rows.length - 1];
    if (sel.closest('tr') === lastRow) {
        addRow();
    }
    updateCount();
}

function updateCount() {
    var rows = document.getElementById('transferBody').querySelectorAll('tr');
    var filled = 0;
    rows.forEach(function(r) {
        if (r.querySelector('select') && r.querySelector('select').value) filled++;
    });
    document.getElementById('itemCount').textContent = filled + ' item' + (filled !== 1 ? 's' : '');
}

// Remove empty rows on submit (keep only rows with selected items)
document.getElementById('transferForm').addEventListener('submit', function(e) {
    var rows = document.getElementById('transferBody').querySelectorAll('tr');
    rows.forEach(function(r) {
        var sel = r.querySelector('select');
        if (sel && !sel.value) r.remove();
    });
    if (document.getElementById('transferBody').querySelectorAll('tr').length === 0) {
        e.preventDefault();
        alert('Please add at least one item to transfer.');
    }
});

// Start with one empty row
addRow();
</script>
