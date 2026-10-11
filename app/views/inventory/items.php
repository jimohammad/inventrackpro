<!-- Items List -->
<style>
.items-page .items-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.items-page .items-head .page-title { margin: 0; }
.items-page .items-head .btn { padding: 4px 10px; font-size: 0.8rem; }
.items-toolbar {
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.items-search {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 200px;
}
.items-search i { color: #6366f1; font-size: 0.85rem; }
.items-search input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.82rem;
    color: var(--text-main);
    background: transparent;
}
.items-stock-toggle {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    margin-bottom: 0;
    font-weight: 650;
    color: #334155;
    font-size: 0.78rem;
    user-select: none;
}
.items-stock-toggle input { width: 14px; height: 14px; accent-color: #6366f1; }
.items-table { font-size: 0.8rem; }
.items-table thead th {
    padding: 6px 10px;
    font-size: 0.65rem;
    white-space: nowrap;
}
.items-table tbody td {
    padding: 4px 10px;
    vertical-align: middle;
    border-bottom-color: var(--border-color);
}
.items-table tbody tr { height: 32px; }
.items-id { text-align: center; color: var(--text-muted); font-size: 0.72rem; font-variant-numeric: tabular-nums; }
.items-name { font-weight: 650; color: var(--text-main); }
.items-cat { color: var(--text-muted); font-size: 0.76rem; }
.items-money { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
.items-qty {
    display: inline-block;
    min-width: 2.2rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.items-qty.is-ok { color: var(--success); }
.items-qty.is-low { color: var(--warning); }
.items-qty.is-zero { color: var(--danger); }
.items-status {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.4;
}
.items-status.is-on { background: rgba(16,185,129,0.15); color: var(--success); }
.items-status.is-off { background: rgba(100,116,139,0.18); color: #64748b; }
.items-acts { display: flex; gap: 4px; }
.items-acts .btn {
    width: 26px;
    height: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 6px;
    font-size: 0.75rem;
}
.items-acts .btn-edit { background: rgba(99,102,241,0.14); color: var(--primary); }
.items-acts .btn-del { background: rgba(239,68,68,0.14); color: var(--danger); }
</style>

<div class="items-page">
<div class="items-head">
    <h1 class="page-title">Items</h1>
    <div class="d-flex align-items-center gap-2">
        <?php if (Auth::isAdmin()): ?>
        <form method="POST" action="?page=items&action=syncCosts" class="d-inline" id="syncCostsForm">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i> Sync Real Costs</button>
        </form>
        <?php endif; ?>
        <?php if (Auth::can('inventory','add')): ?>
        <a href="?page=items&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Item</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="items-toolbar">
            <div class="items-search">
                <i class="bi bi-search"></i>
                <input type="text" id="itemSearchBox" placeholder="Search items…" autocomplete="off">
            </div>
            <label class="items-stock-toggle">
                <input type="checkbox" id="inStockOnly" checked>
                In Stock only
            </label>
        </div>
        <table class="table mb-0 items-table" id="itemsTable">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Real Cost</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-center">Stock</th>
                    <th>Status</th>
                    <th style="width:72px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4" style="font-size:0.85rem;">No items yet</td></tr>
                <?php else: ?>
                <?php foreach ($items as $idx => $it):
                    $qty = (int) ($it['total_stock'] ?? 0);
                    $min = (int) ($it['min_stock'] ?? 0);
                    $qtyClass = $qty === 0 ? 'is-zero' : ($qty <= $min ? 'is-low' : 'is-ok');
                ?>
                <tr data-stock="<?= $qty ?>" data-sku="<?= htmlspecialchars((string) ($it['sku'] ?? ''), ENT_QUOTES) ?>">
                    <td class="items-id"><?= $idx + 1 ?></td>
                    <td class="items-name"><?= htmlspecialchars($it['name']) ?></td>
                    <td class="items-cat"><?= htmlspecialchars($it['category_name'] ?? '—') ?></td>
                    <td class="text-end items-money"><?= APP_CURRENCY ?> <?= number_format($it['purchase_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-end items-money"><?= APP_CURRENCY ?> <?= number_format($it['sale_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-center"><span class="items-qty <?= $qtyClass ?>"><?= $qty ?></span></td>
                    <td>
                        <?php if ($it['is_active']): ?>
                        <span class="items-status is-on">Active</span>
                        <?php else: ?>
                        <span class="items-status is-off">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="items-acts">
                            <?php if (Auth::can('inventory','edit')): ?>
                            <a href="?page=items&action=edit&id=<?= $it['id'] ?>" class="btn btn-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (Auth::isAdmin() && Auth::can('inventory', 'delete')): ?>
                            <form method="POST" action="?page=items&action=delete" style="display:inline;">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                                <button type="submit" class="btn btn-del pin-protect"
                                        data-confirm="Permanently delete this item? Only unused items (no sales/purchases/IMEI/stock) can be deleted. Admin PIN required."
                                        title="Delete (Admin PIN)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>
function applyItemsFilter() {
    var q = (document.getElementById('itemSearchBox')?.value || '').toLowerCase().trim();
    var inStockOnly = !!document.getElementById('inStockOnly')?.checked;
    var rows = document.querySelectorAll('#itemsTable tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var sku = (row.dataset.sku || '').toLowerCase();
        var stock = parseInt(row.dataset.stock || '0', 10) || 0;
        var okSearch = (!q || text.includes(q) || sku.includes(q));
        var okStock  = (!inStockOnly || stock > 0);
        row.style.display = (okSearch && okStock) ? '' : 'none';
    });
}

document.getElementById('itemSearchBox')?.addEventListener('input', applyItemsFilter);
document.getElementById('inStockOnly')?.addEventListener('change', applyItemsFilter);
document.getElementById('syncCostsForm')?.addEventListener('submit', function(e) {
    if (!confirm('Rebuild all item real costs from latest purchase lines?')) {
        e.preventDefault();
    }
});
applyItemsFilter();
</script>
