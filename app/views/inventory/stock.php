<!-- Stock List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Stock List</h1><p class="page-subtitle">Current inventory levels per warehouse</p></div>
    <a href="?page=transfers&action=create" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left-right me-1"></i> Transfer Stock</a>
</div>

<!-- Filters -->
<div style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:200px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search Items
            </label>
            <input type="text" id="stockSearch" placeholder="Search items by name..."
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-building me-1"></i>Warehouse
            </label>
            <div style="padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;font-weight:600;">
                <?= htmlspecialchars(Auth::warehouseName()) ?>
            </div>
        </div>

    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="stockTable">
            <thead>
                <tr>
                    <th class="th-blue" style="width:40px;">#</th>
                    <th class="th-blue">Item</th>
                    <th class="th-blue text-center">Quantity</th>
                    <th class="th-blue text-center">Min Stock</th>
                    <th class="th-blue">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockList)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No stock data found</td></tr>
                <?php else: ?>
                <?php foreach ($stockList as $i => $s): ?>
                <?php $isLow = (int)$s['quantity'] <= (int)$s['min_stock'] && (int)$s['min_stock'] > 0; ?>
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                    <td class="text-center">
                        <span class="fw-bold" style="font-size:1rem;color:<?= $isLow ? 'var(--danger)' : 'var(--success)' ?>;">
                            <?= $s['quantity'] ?>
                        </span>
                    </td>
                    <td class="text-center text-muted"><?= $s['min_stock'] ?></td>
                    <td>
                        <?php if ($isLow): ?>
                        <span class="badge" style="background:rgba(245,158,11,0.15);color:var(--warning);border-radius:5px;">Low Stock</span>
                        <?php else: ?>
                        <span class="badge badge-paid" style="border-radius:5px;">OK</span>
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
document.getElementById('stockSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#stockTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>
