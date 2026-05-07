<!-- IMEI History -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">IMEI History</h1><p class="page-subtitle">Track every device by its IMEI number</p></div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="imei">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search IMEI, item name..."
                    value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="in_stock"    <?= $filters['status']==='in_stock'?'selected':'' ?>>In Stock</option>
                    <option value="sold"        <?= $filters['status']==='sold'?'selected':'' ?>>Sold</option>
                    <option value="returned"    <?= $filters['status']==='returned'?'selected':'' ?>>Returned</option>
                    <option value="transferred" <?= $filters['status']==='transferred'?'selected':'' ?>>Transferred</option>
                    <option value="defective"   <?= $filters['status']==='defective'?'selected':'' ?>>Defective</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $filters['warehouse_id']==$w['id']?'selected':'' ?>><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    <a href="?page=imei" class="btn btn-outline-secondary btn-sm">✕</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="imeiTable">
            <thead>
                <tr>
                    <th>IMEI</th>
                    <th>Item</th>
                    <th>Warehouse</th>
                    <th>Status</th>
                    <th>Purchase Ref</th>
                    <th>Sale Ref</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($imeis)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-upc-scan fs-2 d-block mb-2"></i>No IMEI records found</td></tr>
                <?php else: ?>
                <?php foreach ($imeis as $im): ?>
                <?php
                $statusColor = match($im['status']) {
                    'in_stock'    => 'var(--success)',
                    'sold'        => 'var(--primary)',
                    'returned'    => 'var(--warning)',
                    'transferred' => '#8b5cf6',
                    'defective'   => 'var(--danger)',
                    default       => 'var(--text-muted)',
                };
                $statusBg = match($im['status']) {
                    'in_stock'    => 'rgba(16,185,129,0.12)',
                    'sold'        => 'rgba(99,102,241,0.12)',
                    'returned'    => 'rgba(245,158,11,0.12)',
                    'transferred' => 'rgba(139,92,246,0.12)',
                    'defective'   => 'rgba(239,68,68,0.12)',
                    default       => 'rgba(100,116,139,0.2)',
                };
                ?>
                <tr>
                    <td>
                        <span style="font-family:monospace;font-weight:600;color:var(--primary);"><?= $im['imei'] ?></span>
                        <?php if ($im['imei2']): ?>
                        <br><small class="text-muted" style="font-family:monospace;"><?= $im['imei2'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars($im['item_name']) ?></span>
                        <?php if ($im['sku']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars((string) $im['sku']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($im['warehouse_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge px-2 py-1" style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;border-radius:6px;">
                            <?= ucfirst(str_replace('_', ' ', $im['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($im['purchase_invoice']): ?>
                        <a href="?page=purchases&action=detail&id=<?= $im['purchase_id'] ?>"
                           style="color:var(--primary);text-decoration:none;font-size:0.82rem;">
                            <?= $im['purchase_invoice'] ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($im['sale_invoice']): ?>
                        <a href="?page=sales&action=detail&id=<?= $im['sale_id'] ?>"
                           style="color:var(--primary);text-decoration:none;font-size:0.82rem;">
                            <?= $im['sale_invoice'] ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= date('d M Y', strtotime($im['created_at'])) ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#imeiTable').DataTable({ pageLength: 50, order:[[6,'desc']], language: { search: '', searchPlaceholder: 'Search...' } }); });</script>
