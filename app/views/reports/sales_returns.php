<!-- Sales Returns Report -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="page-title">Sales Returns Report</h1>
        <p class="page-subtitle">All sale returns by date range, customer, or reference</p>
    </div>
    <?php if (!empty($returns)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('salesReturnsRptTable','Sales_Returns_Report')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
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
            <input type="hidden" name="action" value="salesReturns">
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Customer</label>
                <select name="party_id" class="form-select">
                    <option value="">All Customers</option>
                    <?php foreach ($parties as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $partyId == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Return no, customer, invoice..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($returns)): ?>


<!-- Top Customers returning -->
<?php if (!empty($custSummary)): ?>
<div class="card mb-4">
    <div class="card-body">
        <p style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:14px;">
            <i class="bi bi-person-lines-fill me-1"></i> Returns by Customer
        </p>
        <div class="row g-3">
            <?php foreach ($custSummary as $cs):
                $pct = $totalAmount > 0 ? ($cs['total'] / $totalAmount * 100) : 0;
            ?>
            <div class="col-md-6">
                <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
                    <span style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($cs['party_name']) ?></span>
                    <span style="color:#f43f5e;font-weight:700;"><?= number_format($cs['total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:3px;">
                    <div style="height:6px;background:linear-gradient(90deg,#f43f5e,#fb7185);border-radius:3px;width:<?= round($pct) ?>%;"></div>
                </div>
                <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;"><?= $cs['count'] ?> returns · <?= number_format($pct, 1) ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Returns Table -->
<div class="card">
    <div class="card-body p-0">

        <!-- Print header -->
        <div class="print-only" style="padding:20px 24px 10px;border-bottom:2px solid #e2e8f0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h2 style="margin:0;font-size:1.3rem;color:#1e293b;"><?= APP_NAME ?? 'Sales Returns Report' ?></h2>
                    <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Sales Returns Report</p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-weight:700;font-size:1rem;color:#f43f5e;">Total: <?= number_format($totalAmount, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                    <p style="margin:2px 0 0;color:#64748b;font-size:0.82rem;"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></p>
                </div>
            </div>
        </div>

        <table id="salesReturnsRptTable" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">#</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Date</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Return No</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Customer</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Orig. Invoice</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Items</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Reason</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">By</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $n = 1; foreach ($returns as $ret):
                    $items = $itemsByReturn[$ret['id']] ?? [];
                ?>
                <tr style="background:#fff;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;"><?= $n++ ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($ret['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;white-space:nowrap;">
                        <a href="?page=returns&action=detail&id=<?= $ret['id'] ?>" style="color:#f43f5e;font-weight:700;text-decoration:none;">
                            <?= htmlspecialchars($ret['return_no']) ?>
                        </a>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#1e293b;font-weight:600;">
                        <?= htmlspecialchars($ret['party_name']) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#6366f1;">
                        <?= $ret['original_invoice'] ? htmlspecialchars($ret['original_invoice']) : '<span style="color:#cbd5e1;">—</span>' ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;font-size:0.8rem;max-width:180px;">
                        <?php foreach ($items as $item): ?>
                        <div><?= htmlspecialchars($item['item_name']) ?> <span style="color:#94a3b8;">×<?= $item['quantity'] ?></span></div>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:0.8rem;max-width:140px;">
                        <?= htmlspecialchars($ret['reason'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;font-size:0.78rem;white-space:nowrap;">
                        <?= htmlspecialchars($ret['created_by_name'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#f43f5e;">
                        <?= number_format($ret['grand_total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:linear-gradient(135deg,#fff5f5,#fee2e2);">
                    <td colspan="8" style="padding:12px 14px;font-weight:700;color:#991b1b;">
                        Total — <?= count($returns) ?> returns · <?= $totalQty ?> items
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:1.05rem;font-weight:800;color:#f43f5e;">
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
        <i class="bi bi-arrow-return-left" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">No returns found for the selected filters.</p>
    </div>
</div>
<?php endif; ?>

<script>$(document).ready(function(){
    if($('#salesReturnsRptTable tbody tr').length){
        $('#salesReturnsRptTable').DataTable({ pageLength:50, order:[[1,'desc']], language:{search:'',searchPlaceholder:'Search...'}, pageLength:50, order:[[1,'desc']] });
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
