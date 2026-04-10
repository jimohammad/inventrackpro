<!-- Items List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Items</h1><p class="page-subtitle">Product and inventory catalog</p></div>
    <?php if (Auth::can('inventory','add')): ?>
    <a href="?page=items&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Item</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:12px;position:relative;">
            <i class="bi bi-search" style="color:#6366f1;font-size:1rem;"></i>
            <input type="text" id="itemSearchBox" placeholder="Search items to edit — type name or SKU..."
                   style="flex:1;border:none;outline:none;font-size:0.9rem;color:var(--text-main);background:transparent;"
                   autocomplete="off">
        </div>
        <table class="table mb-0" id="itemsTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Purchase Price</th>
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
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-size:0.8rem;"><?= $idx + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($it['name']) ?></td>
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
                        <?php if (Auth::can('inventory','edit')): ?>
                        <a href="?page=items&action=edit&id=<?= $it['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.getElementById('itemSearchBox').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    var rows = document.querySelectorAll('#itemsTable tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>
