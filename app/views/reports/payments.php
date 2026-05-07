<!-- Payments Report -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Payments Report</h1>
    <div class="ms-auto d-flex gap-2">
        <button onclick="exportReportCSV('payRptTable','Payments_Report')" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="payments">
            <div class="col-6 col-md-3"><label class="form-label mb-1">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label mb-1">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>"></div>
            <div class="col-6 col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100 mt-3">Generate</button></div>
        </form>
    </div>
</div>

<!-- By Method -->
<div class="row g-3 mb-4">
    <?php if (empty($totals)): ?>
    <div class="col-12"><p class="text-muted">No payments in this period.</p></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="payRptTable">
            <thead>
                <tr>
                    <th>Payment No</th>
                    <th>Date</th>
                    <th>Party</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No payments in this period</td></tr>
                <?php else: ?>
                <?php foreach ($data as $p): ?>
                <tr>
                    <td class="fw-semibold"><?= $p['payment_no'] ?></td>
                    <td><?= date('d M Y', strtotime($p['date'])) ?></td>
                    <td><?= htmlspecialchars($p['party_name'] ?? '—') ?></td>
                    <td><span class="badge" style="background:rgba(99,102,241,0.12);color:var(--primary);border-radius:5px;"><?= ucfirst($p['ref_type']) ?></span></td>
                    <td><?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></td>
                    <td><?= htmlspecialchars($p['account_name'] ?? '—') ?></td>
                    <td class="text-end fw-semibold" style="color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($p['amount'], DECIMAL_PLACES) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Total</th>
                    <th class="text-end" style="color:var(--success);">
                        <?= APP_CURRENCY ?> <?= number_format(array_sum(array_column($data,'amount')), DECIMAL_PLACES) ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#payRptTable').DataTable({ pageLength:50, order:[[1,'desc']], language:{search:'',searchPlaceholder:'Search...'}, pageLength:50 }); });</script>
