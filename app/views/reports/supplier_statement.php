<!-- Supplier Statement Report -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="page-title">Supplier Statement</h1>
        <p class="page-subtitle">Full transaction history per supplier — purchases and payments</p>
    </div>
    <?php if ($supplier && !empty($transactions)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('supplierStmtTable','Supplier_Statement')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
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
            <input type="hidden" name="action" value="supplierStatement">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $supplierId == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($supplier): ?>


<?php if (!empty($transactions)): ?>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">

        <!-- Print header -->
        <div class="print-only" style="padding:20px 24px 14px;border-bottom:2px solid #e2e8f0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h2 style="margin:0;font-size:1.3rem;color:#1e293b;"><?= APP_NAME ?? 'Supplier Statement' ?></h2>
                    <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Supplier Statement</p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;"><?= htmlspecialchars($supplier['name']) ?></p>
                    <p style="margin:2px 0 0;color:#64748b;font-size:0.82rem;">
                        <?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?>
                    </p>
                </div>
            </div>
        </div>

        <table id="supplierStmtTable" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Date</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Reference</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Type</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Notes</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Purchase</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Payment</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Balance</th>
                </tr>
            </thead>
            <tbody>

                <!-- Opening Balance Row -->
                <tr style="background:#f8faff;">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;white-space:nowrap;">
                        <?= date('d M Y', strtotime($fromDate)) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;" colspan="3">
                        <span style="color:#6366f1;font-weight:600;">Opening Balance</span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;">—</td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;">—</td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:<?= $openingBal > 0 ? '#dc2626' : '#10b981' ?>;">
                        <?= number_format($openingBal, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>

                <?php foreach ($transactions as $tx):
                    $isPur = $tx['txn_type'] === 'purchase';
                ?>
                <tr style="background:#fff;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($tx['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;white-space:nowrap;">
                        <?php if ($isPur): ?>
                            <a href="?page=purchases&action=detail&id=<?= $tx['id'] ?>" style="color:#f59e0b;font-weight:700;text-decoration:none;">
                                <?= htmlspecialchars($tx['ref_no']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#10b981;font-weight:700;"><?= htmlspecialchars($tx['ref_no']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;">
                        <?php if ($isPur): ?>
                        <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:6px;font-size:0.75rem;font-weight:600;">Purchase</span>
                        <?php else: ?>
                        <span style="background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:6px;font-size:0.75rem;font-weight:600;">Payment</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:0.8rem;max-width:180px;">
                        <?= htmlspecialchars($tx['notes'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:<?= $isPur ? '#f59e0b' : '#cbd5e1' ?>;">
                        <?= $isPur ? number_format($tx['amount'], DECIMAL_PLACES) : '—' ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:<?= !$isPur ? '#10b981' : '#cbd5e1' ?>;">
                        <?= !$isPur ? number_format($tx['amount'], DECIMAL_PLACES) : '—' ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:<?= $tx['running'] > 0 ? '#dc2626' : ($tx['running'] < 0 ? '#6366f1' : '#10b981') ?>;">
                        <?= number_format($tx['running'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Closing Balance Row -->
                <tr style="background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                    <td colspan="4" style="padding:12px 14px;font-weight:700;color:#4338ca;">
                        Closing Balance
                        <span style="color:#94a3b8;font-weight:400;font-size:0.78rem;margin-left:8px;"><?= date('d M Y', strtotime($toDate)) ?></span>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-weight:700;color:#f59e0b;">
                        <?= number_format($summary['total_purchases'], DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-weight:700;color:#10b981;">
                        <?= number_format($summary['total_paid'], DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:1rem;font-weight:800;color:<?= $summary['balance'] > 0 ? '#dc2626' : ($summary['balance'] < 0 ? '#6366f1' : '#10b981') ?>;">
                        <?= number_format($summary['balance'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Balance note -->
<div style="margin-top:12px;font-size:0.78rem;color:#94a3b8;text-align:right;" class="no-print">
    <span style="color:#dc2626;font-weight:600;">Red balance</span> = amount still owed to supplier &nbsp;·&nbsp;
    <span style="color:#6366f1;font-weight:600;">Blue balance</span> = overpaid &nbsp;·&nbsp;
    <span style="color:#10b981;font-weight:600;">Green</span> = fully settled
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-journal-text" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">No transactions found for this supplier in the selected period.</p>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-building" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">Select a supplier and date range to view the statement.</p>
    </div>
</div>
<?php endif; ?>

<script>$(document).ready(function(){
    if($('#supplierStmtTable tbody tr').length){
        $('#supplierStmtTable').DataTable({ pageLength:100, paging:false, order:[], language:{search:'',searchPlaceholder:'Search...'}, pageLength:100, paging:false, order:[] });
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
