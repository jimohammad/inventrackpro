<!-- Returns List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Sale Returns</h1>
        <p class="page-subtitle">Track returned items and refunds</p>
    </div>
    <?php if (Auth::can('returns','add')): ?>
    <a href="?page=returns&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Return
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="returns">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-funnel me-1"></i>Status
            </label>
            <select name="status"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
                <option value="">All Status</option>
                <option value="pending"  <?= $filters['status']==='pending'?'selected':'' ?>>Pending</option>
                <option value="approved" <?= $filters['status']==='approved'?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= $filters['status']==='rejected'?'selected':'' ?>>Rejected</option>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) $filters['from_date']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?: date('Y-m-d'))) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=returns"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="returnsTable">
            <thead>
                <tr>
                    <th class="th-blue">Return No</th>
                    <th class="th-blue">Date</th>
                    <th class="th-blue">Party</th>
                    <th class="th-blue text-end">Amount</th>
                    <th class="th-blue">Status</th>
                    <th class="th-blue">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No returns found</td></tr>
                <?php else: ?>
                <?php foreach ($returns as $r): ?>
                <tr>
                    <td><a href="?page=returns&action=detail&id=<?= $r['id'] ?>" style="color:var(--primary);font-weight:600;text-decoration:none;"><?= $r['return_no'] ?></a></td>
                    <td><span style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap;"><?= date('m/d/Y, h:i A', strtotime($r['created_at'] ?? $r['date'])) ?></span></td>
                    <td><?= htmlspecialchars($r['party_name']) ?></td>
                    <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($r['grand_total'], DECIMAL_PLACES) ?></td>
                    <td>
                        <span class="badge badge-<?= $r['status'] === 'approved' ? 'paid' : ($r['status']==='rejected'?'draft':'pending') ?> px-2 py-1" style="border-radius:6px;">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                        <a href="?page=returns&action=print&id=<?= $r['id'] ?>&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:#059669;border:none;" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="?page=returns&action=print&id=<?= $r['id'] ?>&autopdf=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:none;" title="Download PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="?page=returns&action=detail&id=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (Auth::isAdmin() && $r['status'] !== 'cancelled'): ?>
                        <a href="?page=returns&action=edit&id=<?= $r['id'] ?>" class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
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
<script>$(document).ready(() => { $('#returnsTable').DataTable({ pageLength:25, order:[[1,'desc']], columnDefs:[{orderable:false,targets:[5]}], language:{search:'',searchPlaceholder:'Search...'} }); });</script>
