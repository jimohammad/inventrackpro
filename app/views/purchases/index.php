<!-- Purchases List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Purchases</h1><p class="page-subtitle">All purchase invoices from suppliers</p></div>
    <?php if (Auth::can('purchases','add')): ?>
    <a href="?page=purchases&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Purchase</a>
    <?php endif; ?>
</div>


<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="purchases">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Invoice no, supplier..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
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
                <option value="">All Status</option>
                <option value="confirmed" <?= $filters['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="partial"   <?= $filters['status']==='partial'?'selected':'' ?>>Partial</option>
                <option value="paid"      <?= $filters['status']==='paid'?'selected':'' ?>>Paid</option>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= $filters['from_date'] ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= $filters['to_date'] ?: date('Y-m-d') ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=purchases"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="purchasesTable">
                <thead>
                    <tr>
                        <th class="th-blue">Invoice No</th><th class="th-blue">Date</th><th class="th-blue">Supplier</th><th class="th-blue">Warehouse</th>
                        <th class="th-blue text-end">Total</th><th class="th-blue text-end">Paid</th>
                        <th class="th-blue text-end">Balance</th><th class="th-blue">Status</th><th class="th-blue">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No purchases found</td></tr>
                    <?php else: ?>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><a href="?page=purchases&action=detail&id=<?= $p['id'] ?>" style="color:var(--primary);font-weight:600;text-decoration:none;"><?= $p['invoice_no'] ?></a></td>
                        <td><span style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap;"><?= date('m/d/Y, h:i A', strtotime($p['created_at'] ?? $p['date'])) ?></span></td>
                        <td><?= htmlspecialchars($p['party_name']) ?></td>
                        <td><?= htmlspecialchars($p['warehouse_name'] ?? '—') ?></td>
                        <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($p['grand_total'], DECIMAL_PLACES) ?></td>
                        <td class="text-end" style="color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($p['paid_amount'], DECIMAL_PLACES) ?></td>
                        <td class="text-end" style="color:<?= $p['balance']>0?'var(--warning)':'var(--success)' ?>;"><?= APP_CURRENCY ?> <?= number_format($p['balance'], DECIMAL_PLACES) ?></td>
                        <td><span class="badge badge-<?= $p['status'] ?> px-2" style="border-radius:6px;"><?= ucfirst($p['status']) ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=purchases&action=detail&id=<?= $p['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (Auth::can('purchases','delete') && $p['status'] !== 'cancelled'): ?>
                                <form method="POST" action="?page=purchases&action=cancel" style="display:inline;"
                                      onsubmit="return confirm('Cancel this purchase? Stock and linked payments will be reversed.');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm pin-protect"
                                            style="background:rgba(239,68,68,0.15);color:#dc2626;border:none;" title="Delete">
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
<script>$(document).ready(() => { $('#purchasesTable').DataTable({ pageLength:25, order:[[1,'desc']], columnDefs:[{orderable:false,targets:[8]}], language:{search:'',searchPlaceholder:'Search...'} }); });</script>
