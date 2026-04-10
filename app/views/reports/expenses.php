<!-- Expenses Report -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="page-title">Expenses Report</h1>
        <p class="page-subtitle">All expenses by date range, category, or account</p>
    </div>
    <?php if (!empty($expenses)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('expensesRptTable','Expenses_Report')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="expenses">
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>" <?= $accountId == $acc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Ref or description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($expenses)): ?>


<!-- Breakdown Row -->
<div class="row g-3 mb-4">

    <!-- By Category -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:12px;">
                    <i class="bi bi-tag me-1"></i> By Category
                </p>
                <?php foreach ($catSummary as $cat):
                    $pct = $totalAmount > 0 ? ($cat['total'] / $totalAmount * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
                        <span style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($cat['category'] ?? 'Uncategorized') ?></span>
                        <span style="color:#f59e0b;font-weight:700;"><?= number_format($cat['total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;">
                        <div style="height:6px;background:linear-gradient(90deg,#f59e0b,#fbbf24);border-radius:3px;width:<?= round($pct) ?>%;"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;"><?= $cat['count'] ?> entries · <?= number_format($pct, 1) ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- By Account -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:12px;">
                    <i class="bi bi-bank me-1"></i> By Account
                </p>
                <?php foreach ($accSummary as $acc):
                    $pct = $totalAmount > 0 ? ($acc['total'] / $totalAmount * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
                        <span style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($acc['account'] ?? 'Unknown') ?></span>
                        <span style="color:#6366f1;font-weight:700;"><?= number_format($acc['total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;">
                        <div style="height:6px;background:linear-gradient(90deg,#6366f1,#818cf8);border-radius:3px;width:<?= round($pct) ?>%;"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;"><?= $acc['count'] ?> entries · <?= number_format($pct, 1) ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">

        <!-- Print header -->
        <div class="print-only" style="padding:20px 24px 10px;border-bottom:2px solid #e2e8f0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h2 style="margin:0;font-size:1.3rem;color:#1e293b;"><?= APP_NAME ?? 'Expenses Report' ?></h2>
                    <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Expenses Report</p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-weight:700;font-size:1rem;color:#f59e0b;">Total: <?= number_format($totalAmount, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                    <p style="margin:2px 0 0;color:#64748b;font-size:0.82rem;"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></p>
                </div>
            </div>
        </div>

        <table id="expensesRptTable" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;">#</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Date</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Reference</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Category</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Account</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Description</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">By</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $n = 1; foreach ($expenses as $exp): ?>
                <tr style="background:#fff;" onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;"><?= $n++ ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($exp['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#1e293b;white-space:nowrap;">
                        <?= htmlspecialchars($exp['expense_no'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;">
                        <?php if ($exp['category_name']): ?>
                        <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:6px;font-size:0.75rem;font-weight:600;">
                            <?= htmlspecialchars($exp['category_name']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#cbd5e1;font-size:0.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;font-size:0.82rem;">
                        <?= htmlspecialchars($exp['account_name'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#1e293b;max-width:200px;">
                        <?= htmlspecialchars($exp['description'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;font-size:0.78rem;white-space:nowrap;">
                        <?= htmlspecialchars($exp['created_by_name'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#f59e0b;">
                        <?= number_format($exp['amount'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:linear-gradient(135deg,#fffbeb,#fef3c7);">
                    <td colspan="7" style="padding:12px 14px;font-weight:700;color:#92400e;">
                        Total — <?= count($expenses) ?> expenses
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:1.05rem;font-weight:800;color:#f59e0b;">
                        <?= number_format($totalAmount, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-receipt" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">No expenses found for the selected filters.</p>
    </div>
</div>
<?php endif; ?>

<script>$(document).ready(function(){
    if($('#expensesRptTable tbody tr').length){
        $('#expensesRptTable').DataTable({ pageLength:50, order:[[1,'desc']], language:{search:'',searchPlaceholder:'Search...'} });
    }
});</script>
<style>
@media print {
    .no-print, .sidebar, nav, .topbar { display:none !important; }
    .print-only { display:block !important; }
    body { background:#fff !important; }
    .card { box-shadow:none !important; border:none !important; }
    .stat-card { border:1px solid #e2e8f0 !important; box-shadow:none !important; }
}
.print-only { display:none; }
</style>
