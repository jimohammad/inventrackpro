<!-- Items List -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div><h1 class="page-title">Items</h1></div>
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
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:12px;position:relative;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:240px;">
                <i class="bi bi-search" style="color:#6366f1;font-size:1rem;"></i>
                <input type="text" id="itemSearchBox" placeholder="Search items to edit — type name or SKU..."
                       style="flex:1;border:none;outline:none;font-size:0.9rem;color:var(--text-main);background:transparent;"
                       autocomplete="off">
            </div>

            <label style="display:flex;align-items:center;gap:8px;margin-left:auto;font-weight:700;color:#334155;font-size:0.85rem;user-select:none;">
                <input type="checkbox" id="inStockOnly" checked style="width:16px;height:16px;accent-color:#6366f1;">
                In Stock only
            </label>
        </div>
        <table class="table mb-0" id="itemsTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Real Cost</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-center">Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-box-seam fs-2 d-block mb-2"></i>No items yet</td></tr>
                <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                <tr data-stock="<?= (int)($it['total_stock'] ?? 0) ?>">
                    <td style="text-align:center;color:var(--text-muted);font-size:0.8rem;"><?= $idx + 1 ?></td>
                    <td class="fw-semibold">
                        <?= htmlspecialchars($it['name']) ?>
                        <?php if (!empty($it['sku'])): ?>
                        <div style="font-size:0.72rem;color:var(--text-muted);font-weight:400;"><?= htmlspecialchars((string) $it['sku']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($it['category_name'] ?? '—') ?></td>
                    <td class="text-end"><?= APP_CURRENCY ?> <?= number_format($it['purchase_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-end"><?= APP_CURRENCY ?> <?= number_format($it['sale_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-center">
                        <span style="font-weight:600;color:<?= (int)$it['total_stock'] === 0 ? 'var(--danger)' : ((int)$it['total_stock'] <= (int)$it['min_stock'] ? 'var(--warning)' : 'var(--success)') ?>;">
                            <?= $it['total_stock'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($it['is_active']): ?>
                        <span class="badge badge-paid px-2" style="border-radius:5px;">Active</span>
                        <?php else: ?>
                        <span class="badge badge-draft px-2" style="border-radius:5px;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if (Auth::can('inventory','edit')): ?>
                            <a href="?page=items&action=edit&id=<?= $it['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (Auth::isAdmin() && Auth::can('inventory', 'delete')): ?>
                            <form method="POST" action="?page=items&action=delete" style="display:inline;">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                                <button type="submit" class="btn btn-sm pin-protect"
                                        data-confirm="Permanently delete this item? Only unused items (no sales/purchases/IMEI/stock) can be deleted. Admin PIN required."
                                        style="background:rgba(239,68,68,0.15);color:var(--danger);border:none;" title="Delete (Admin PIN)">
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
<script>
function applyItemsFilter() {
    var q = (document.getElementById('itemSearchBox')?.value || '').toLowerCase().trim();
    var inStockOnly = !!document.getElementById('inStockOnly')?.checked;
    var rows = document.querySelectorAll('#itemsTable tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var stock = parseInt(row.dataset.stock || '0', 10) || 0;
        var okSearch = (!q || text.includes(q));
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
