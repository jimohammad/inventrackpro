<?php $money = function($v) { return APP_CURRENCY . ' ' . number_format((float)$v, DECIMAL_PLACES); }; ?>

<!-- Day Book Report -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Day Book</h1>
        <p class="page-subtitle">All transactions for <?= date('l, d M Y', strtotime($date)) ?></p>
    </div>
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
</div>

<!-- Date Picker -->
<div class="card mb-4" style="border-radius:12px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="daybook">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar3" style="color:#0d9488;font-size:1.1rem;"></i>
                <input type="date" name="date" value="<?= $date ?>" class="form-control form-control-sm" style="width:180px;font-weight:600;">
            </div>
            <button type="submit" class="btn btn-sm" style="background:#0d9488;color:#fff;font-weight:600;padding:6px 20px;">
                <i class="bi bi-search me-1"></i> View Day
            </button>
            <a href="?page=reports&action=daybook&date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
            <a href="?page=reports&action=daybook&date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i> Prev Day
            </a>
            <a href="?page=reports&action=daybook&date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="btn btn-sm btn-outline-secondary">
                Next Day <i class="bi bi-chevron-right"></i>
            </a>
        </form>
    </div>
</div>


<!-- Net Summary -->
<div class="card mb-4" style="border-radius:12px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border:1.5px solid #c7d2fe;">
    <div class="card-body" style="padding:14px 20px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span style="font-size:0.78rem;color:#64748b;font-weight:600;">MONEY IN</span><br>
                <span style="font-size:1.1rem;font-weight:800;color:#10b981;">
                    <?= $money($summary['sales'] + $summary['payments_in']) ?>
                </span>
            </div>
            <div style="font-size:1.5rem;color:#94a3b8;">−</div>
            <div>
                <span style="font-size:0.78rem;color:#64748b;font-weight:600;">MONEY OUT</span><br>
                <span style="font-size:1.1rem;font-weight:800;color:#ef4444;">
                    <?= $money($summary['purchases'] + $summary['payments_out'] + $summary['returns'] + $summary['expenses']) ?>
                </span>
            </div>
            <div style="font-size:1.5rem;color:#94a3b8;">=</div>
            <div>
                <?php $net = ($summary['sales'] + $summary['payments_in']) - ($summary['purchases'] + $summary['payments_out'] + $summary['returns'] + $summary['expenses']); ?>
                <span style="font-size:0.78rem;color:#64748b;font-weight:600;">NET FLOW</span><br>
                <span style="font-size:1.1rem;font-weight:800;color:<?= $net >= 0 ? '#10b981' : '#ef4444' ?>;">
                    <?= $net < 0 ? '-' : '' ?><?= $money(abs($net)) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card" style="border-radius:12px;">
    <div class="card-header" style="font-weight:700;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;">
        <span><i class="bi bi-journal-text me-2" style="color:#0d9488;"></i>Transactions (<?= count($transactions) ?>)</span>
        <div class="d-flex gap-2">
            <button onclick="exportReportCSV('daybookTable','Day_Book')" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button onclick="exportReportPDF()" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal fs-2 d-block mb-2" style="opacity:0.3;"></i>
            No transactions on this date
        </div>
        <?php else: ?>
        <table class="table mb-0" id="daybookTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Ref No</th>
                    <th>Party / Category</th>
                    <th>Created By</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $typeColors = [
                    'Sale'        => ['bg' => 'rgba(99,102,241,0.12)',  'color' => '#6366f1'],
                    'Purchase'    => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b'],
                    'Payment In'  => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#10b981'],
                    'Payment Out' => ['bg' => 'rgba(59,130,246,0.12)', 'color' => '#3b82f6'],
                    'Return'      => ['bg' => 'rgba(220,38,38,0.12)',  'color' => '#dc2626'],
                    'Expense'     => ['bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6'],
                    'Discount'    => ['bg' => 'rgba(236,72,153,0.12)', 'color' => '#ec4899'],
                ];
                $typeUrls = [
                    'Sale'        => '?page=sales&action=detail&id=',
                    'Purchase'    => '?page=purchases&action=detail&id=',
                    'Payment In'  => '?page=payments&action=detail&id=',
                    'Payment Out' => '?page=payments&action=detail&id=',
                    'Return'      => '?page=returns&action=detail&id=',
                    'Expense'     => '?page=expenses',
                    'Discount'    => '?page=discounts',
                ];
                ?>
                <?php foreach ($transactions as $i => $t):
                    $tc = $typeColors[$t['type']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
                    $url = ($typeUrls[$t['type']] ?? '') . ($t['id'] ?? '');
                ?>
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                    <td style="font-size:0.82rem;color:var(--text-muted);">
                        <?= $t['created_at'] ? date('h:i A', strtotime($t['created_at'])) : '—' ?>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;border-radius:5px;font-size:0.75rem;font-weight:600;">
                            <?= $t['type'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= $url ?>" style="color:var(--primary);font-weight:600;text-decoration:none;">
                            <?= $t['ref_no'] ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($t['party_name'] ?? '—') ?></td>
                    <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($t['created_by'] ?? '—') ?></td>
                    <td class="text-end" style="font-weight:700;color:<?= $tc['color'] ?>;">
                        <?= $money($t['amount']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8faff;font-weight:700;">
                    <td colspan="6" style="text-align:right;color:#4338ca;">Day Total</td>
                    <td class="text-end" style="color:#4338ca;font-size:1rem;">
                        <?= $money(array_sum(array_column($transactions, 'amount'))) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#daybookTable').DataTable({
        pageLength: 100,
        order: [],
        language: { search: '', searchPlaceholder: 'Search...' },
        pageLength: 100
    });
});
</script>

<style>
@media print {
    .sidebar, .topbar, .btn, form, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
}
</style>
