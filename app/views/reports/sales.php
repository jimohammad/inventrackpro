<!-- Sales Report -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Sales Report</h1>
    <div class="ms-auto d-flex gap-2">
        <button onclick="exportReportCSV('salesReportTable','Sales_Report')" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="sales">
            <div class="col-6 col-md-3"><label class="form-label mb-1">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label mb-1">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>"></div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100 mt-3">Generate Report</button>
            </div>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">Sales from <?= date('d M Y', strtotime($fromDate)) ?> to <?= date('d M Y', strtotime($toDate)) ?></div>
    <div class="card-body p-0">
        <table class="table mb-0" id="salesReportTable">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No sales in this period</td></tr>
                <?php else: ?>
                <?php foreach ($data as $s): ?>
                <tr>
                    <td><a href="?page=sales&action=detail&id=<?= $s['id'] ?>" style="color:var(--primary);text-decoration:none;font-weight:600;"><?= $s['invoice_no'] ?></a></td>
                    <td><?= date('d M Y', strtotime($s['date'])) ?></td>
                    <td><?= htmlspecialchars($s['party_name']) ?></td>
                    <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($s['grand_total'], DECIMAL_PLACES) ?></td>
                    <td class="text-end" style="color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($s['paid_amount'], DECIMAL_PLACES) ?></td>
                    <td class="text-end" style="color:<?= $s['balance'] > 0 ? 'var(--warning)' : 'var(--success)' ?>;"><?= APP_CURRENCY ?> <?= number_format($s['balance'], DECIMAL_PLACES) ?></td>
                    <td><span class="badge badge-<?= $s['status'] ?>" style="border-radius:5px;"><?= ucfirst($s['status']) ?></span></td>
                    <td><small class="text-muted"><?= htmlspecialchars($s['created_by_name'] ?? '—') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Totals</th>
                    <th class="text-end"><?= APP_CURRENCY ?> <?= number_format($summary['total'] ?? 0, DECIMAL_PLACES) ?></th>
                    <th class="text-end"><?= APP_CURRENCY ?> <?= number_format($summary['paid'] ?? 0, DECIMAL_PLACES) ?></th>
                    <th class="text-end"><?= APP_CURRENCY ?> <?= number_format($summary['balance'] ?? 0, DECIMAL_PLACES) ?></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#salesReportTable').DataTable({ pageLength:50, order:[[1,'desc']], language:{search:'',searchPlaceholder:'Search...'}, pageLength:50 }); });</script>
