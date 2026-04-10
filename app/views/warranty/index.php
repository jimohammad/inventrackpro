<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="page-title"><i class="bi bi-shield-check me-2"></i>Warranty Replacements</span>
    <a href="?page=warranty&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Replacement
    </a>
</div>

<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="warranty">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:200px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Customer, IMEI, item, ref no..."
                   value="<?= htmlspecialchars($search) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars($toDate ?: date('Y-m-d')) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=warranty"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<div class="card" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:0.85rem;">
            <thead style="background:var(--bg-subtle);">
                <tr>
                    <th style="padding:10px 16px;">Ref No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Faulty Device</th>
                    <th>Old IMEI</th>
                    <th>Replacement Device</th>
                    <th>New IMEI</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($replacements)): ?>
                <tr><td colspan="9" class="text-center py-5 text-muted">No warranty replacements found.</td></tr>
            <?php else: ?>
                <?php foreach ($replacements as $r): ?>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:10px 16px;">
                        <a href="?page=warranty&action=view&id=<?= $r['id'] ?>"
                           style="font-weight:700;color:#6366f1;text-decoration:none;">
                            <?= htmlspecialchars($r['replacement_no']) ?>
                        </a>
                        <?php if ($r['sale_invoice_no']): ?>
                        <br><small class="text-muted">Orig: <?= $r['sale_invoice_no'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                    <td><?= htmlspecialchars($r['customer_name']) ?></td>
                    <td><?= htmlspecialchars($r['old_item_name']) ?></td>
                    <td>
                        <?php if ($r['old_imei']): ?>
                        <code style="font-size:0.78rem;background:#fef2f2;color:#dc2626;padding:2px 5px;border-radius:4px;">
                            <?= $r['old_imei'] ?>
                        </code>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['new_item_name']) ?></td>
                    <td>
                        <?php if ($r['new_imei']): ?>
                        <code style="font-size:0.78rem;background:#f0fdf4;color:#16a34a;padding:2px 5px;border-radius:4px;">
                            <?= $r['new_imei'] ?>
                        </code>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['status'] === 'completed'): ?>
                        <span class="badge" style="background:#dcfce7;color:#15803d;border-radius:6px;">Completed</span>
                        <?php else: ?>
                        <span class="badge" style="background:#fef3c7;color:#92400e;border-radius:6px;">Pending Supplier</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?page=warranty&action=view&id=<?= $r['id'] ?>"
                           class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;padding:3px 8px;">
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
