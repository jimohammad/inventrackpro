<!-- Payments List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Payments</h1>
        <p class="page-subtitle">All payment transactions</p>
    </div>
    <?php if (Auth::can('payments','add')): ?>
    <div style="display:flex;gap:8px;">
        <a href="?page=payments&action=receive"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.88rem;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(16,185,129,.35);">
            <i class="bi bi-arrow-down-circle-fill"></i> Receive Payment
        </a>
        <a href="?page=payments&action=pay"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:.88rem;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(239,68,68,.35);">
            <i class="bi bi-arrow-up-circle-fill"></i> Make Payment
        </a>
    </div>
    <?php endif; ?>
</div>


<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="payments">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Payment no, party..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-funnel me-1"></i>Type
            </label>
            <select name="ref_type"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
                <option value="">All Types</option>
                <option value="sale"     <?= $filters['ref_type']==='sale'?'selected':'' ?>>Sales</option>
                <option value="purchase" <?= $filters['ref_type']==='purchase'?'selected':'' ?>>Purchases</option>
                <option value="expense"  <?= $filters['ref_type']==='expense'?'selected':'' ?>>Expenses</option>
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
            <a href="?page=payments"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="paymentsTable">
                <thead>
                    <tr>
                        <th class="th-blue">Payment No</th>
                        <th class="th-blue">Date</th>
                        <th class="th-blue">Party</th>
                        <th class="th-blue">Type</th>
                        <th class="th-blue">Account</th>
                        <th class="th-blue">Amount</th>
                        <th class="th-blue">By</th>
                        <th class="th-blue">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No payments found</td></tr>
                    <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td>
                            <a href="?page=payments&action=detail&id=<?= $p['id'] ?>"
                               style="color:var(--primary);font-weight:600;text-decoration:none;">
                                <?= $p['payment_no'] ?>
                            </a>
                        </td>
                        <td><span style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap;"><?= date('m/d/Y', strtotime($p['date'])) ?>, <?= date('h:i A', strtotime($p['created_at'])) ?></span></td>
                        <td><?= htmlspecialchars($p['party_name'] ?? '—') ?></td>
                        <td>
                            <?php
                            $typeBadges = [
                                'sale'     => ['bg' => 'rgba(99,102,241,0.12)', 'color' => '#6366f1'],
                                'purchase' => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b'],
                                'expense'  => ['bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6'],
                                'discount' => ['bg' => 'rgba(236,72,153,0.12)', 'color' => '#ec4899'],
                            ];
                            $tb = $typeBadges[$p['ref_type']] ?? ['bg' => 'rgba(100,116,139,0.12)', 'color' => '#64748b'];
                            ?>
                            <span class="badge" style="background:<?= $tb['bg'] ?>;color:<?= $tb['color'] ?>;">
                                <?= ucfirst($p['ref_type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($p['account_name'] ?? '—') ?></td>
                        <td class="fw-semibold">
                            <?php $isOut = ($p['payment_type'] ?? 'in') === 'out'; ?>
                            <span style="color:<?= $isOut ? '#dc2626' : '#059669' ?>;">
                                <i class="bi bi-arrow-<?= $isOut ? 'up' : 'down' ?>-circle-fill"
                                   style="font-size:0.85rem;margin-right:3px;"></i>
                                <?= APP_CURRENCY ?> <?= number_format($p['amount'], DECIMAL_PLACES) ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($p['created_by_name'] ?? '—') ?></small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=payments&action=print&id=<?= $p['id'] ?>&autoprint=1" target="_blank"
                                   class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="?page=payments&action=print&id=<?= $p['id'] ?>&autoprint=1&thermal=1" target="_blank"
                                   class="btn btn-sm" style="background:rgba(5,150,105,0.16);color:#047857;border:none;" title="Thermal Print">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a href="?page=payments&action=print&id=<?= $p['id'] ?>&autopdf=1" target="_blank"
                                   class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:none;" title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="?page=payments&action=detail&id=<?= $p['id'] ?>"
                                   class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (Auth::can('payments','edit')): ?>
                                <a href="?page=payments&action=edit&id=<?= $p['id'] ?>"
                                   class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('payments','delete') && ($p['ref_type'] ?? '') !== 'discount'): ?>
                                <form method="POST" action="?page=payments&action=delete" style="display:inline;"
                                      onsubmit="return confirm('Delete this payment permanently? Account and invoice balances will be reversed.');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm pin-protect" style="background:rgba(239,68,68,0.15);color:#dc2626;border:none;" title="Delete">
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
$(document).ready(() => {
    $('#paymentsTable').DataTable({ pageLength: 25, order: [[1,'desc']], columnDefs: [{ orderable: false, targets: [7] }], language: { search: '', searchPlaceholder: 'Search...' } });
});
</script>
