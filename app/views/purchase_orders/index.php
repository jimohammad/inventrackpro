<!-- Purchase Orders List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Purchase Orders</h1>
        <p class="page-subtitle">Track overseas supplier orders in AED or USD before goods arrive</p>
    </div>
    <a href="?page=purchaseorders&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Purchase Order
    </a>
</div>

<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="purchaseorders">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="PO no, supplier, ref..."
                   value="<?= htmlspecialchars($search ?? '') ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-funnel me-1"></i>Status
            </label>
            <select name="status"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
                <option value="">All</option>
                <option value="draft"     <?= ($status??'')==='draft'     ?'selected':'' ?>>Draft</option>
                <option value="paid"      <?= ($status??'')==='paid'      ?'selected':'' ?>>Paid — Awaiting Goods</option>
                <option value="converted" <?= ($status??'')==='converted' ?'selected':'' ?>>Converted</option>
                <option value="cancelled" <?= ($status??'')==='cancelled' ?'selected':'' ?>>Cancelled</option>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= $fromDate ?? '' ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= $toDate ?: date('Y-m-d') ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=purchaseorders"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<!-- Status counts -->
<?php
$counts = ['draft'=>0,'paid'=>0,'converted'=>0,'cancelled'=>0];
foreach ($orders as $o) { if (isset($counts[$o['status']])) $counts[$o['status']]++; }
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Draft</p>
            <p class="stat-value" style="color:#64748b;"><?= $counts['draft'] ?></p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Paid — Awaiting Goods</p>
            <p class="stat-value" style="color:#f59e0b;"><?= $counts['paid'] ?></p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Converted</p>
            <p class="stat-value" style="color:#10b981;"><?= $counts['converted'] ?></p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Cancelled</p>
            <p class="stat-value" style="color:#94a3b8;"><?= $counts['cancelled'] ?></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size:2.5rem;color:#cbd5e1;"></i>
            <p class="mt-3 mb-0" style="color:#94a3b8;">No purchase orders found.</p>
            <a href="?page=purchaseorders&action=create" class="btn btn-primary mt-3">Create First PO</a>
        </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;">PO No</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;">Date</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;">Supplier</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;">Ref</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;text-align:center;">Currency</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;text-align:right;">Amount</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;text-align:right;">KWD</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;text-align:center;">Status</th>
                    <th class="th-blue" style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <?php
                $stColor = match($o['status']) {
                    'draft'     => ['#e0e7ff','#3730a3','Draft'],
                    'paid'      => ['#fef3c7','#92400e','Paid — Awaiting'],
                    'converted' => ['#d1fae5','#065f46','Converted'],
                    'cancelled' => ['#f1f5f9','#94a3b8','Cancelled'],
                    default     => ['#f1f5f9','#64748b',$o['status']],
                };
                ?>
                <tr style="background:#fff;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;">
                        <a href="?page=purchaseorders&action=show&id=<?= $o['id'] ?>"
                           style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;font-weight:700;color:#6366f1;text-decoration:none;">
                            <?= htmlspecialchars($o['po_no']) ?>
                        </a>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($o['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;">
                        <?= htmlspecialchars($o['supplier_name']) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;font-size:0.78rem;font-family:'JetBrains Mono',monospace;">
                        <?= htmlspecialchars($o['supplier_ref'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <span style="background:<?= $o['currency']==='AED' ? '#dbeafe' : '#fef9c3' ?>;color:<?= $o['currency']==='AED' ? '#1d4ed8' : '#854d0e' ?>;padding:2px 9px;border-radius:6px;font-size:0.72rem;font-weight:700;">
                            <?= $o['currency'] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#f59e0b;">
                        <?= number_format($o['subtotal_foreign'], DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;color:#475569;">
                        <?= number_format($o['subtotal_kwd'], DECIMAL_PLACES) ?> KWD
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <span style="background:<?= $stColor[0] ?>;color:<?= $stColor[1] ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;">
                            <?= $stColor[2] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <a href="?page=purchaseorders&action=show&id=<?= $o['id'] ?>"
                           style="color:#6366f1;font-size:1rem;margin:0 4px;" title="View"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::isAdmin() && $o['status'] !== 'converted'): ?>
                        <a href="?page=purchaseorders&action=edit&id=<?= $o['id'] ?>"
                           style="color:#f59e0b;font-size:1rem;margin:0 4px;" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($o['status'] !== 'converted' && $o['status'] !== 'cancelled'): ?>
                        <form method="POST" action="?page=purchaseorders&action=convert" style="display:inline;" onsubmit="return confirm('Convert this PO to a Purchase Invoice? Stock will be updated.')">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= $o['id'] ?>">
                            <button type="submit" style="background:none;border:none;color:#10b981;font-size:1rem;margin:0 4px;cursor:pointer;" title="Convert to Invoice"><i class="bi bi-arrow-repeat"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
