<!-- Balance Reconciliation -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Balance Reconciliation</h1>
    <div class="ms-auto d-flex gap-2">
        <button onclick="exportReportCSV('reconciliationTable','Balance_Reconciliation')" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<!-- Date Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="reconciliation">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Check as of date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $date) ?>">
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 mt-3">Run Check</button>
            </div>
        </form>
    </div>
</div>


<!-- Mismatch Alert -->
<?php
$mismatches = array_filter($results, fn($r) => $r['status'] === 'mismatch');
if (!empty($mismatches)):
?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:10px;">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Balance mismatch detected</strong> on <?= count($mismatches) ?> account(s).
        Check the rows highlighted in red below.
    </div>
</div>
<?php else: ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:10px;">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><strong>All accounts balanced.</strong> No discrepancies found as of <?= date('d M Y', strtotime($date)) ?>.</div>
</div>
<?php endif; ?>

<!-- Account Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="reconciliationTable" class="table table-hover mb-0" style="font-size:0.85rem;">
                <thead style="background:var(--bg-secondary);">
                    <tr>
                        <th class="px-3 py-2">Account</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2 text-end">Opening</th>
                        <th class="px-3 py-2 text-end">Total In</th>
                        <th class="px-3 py-2 text-end">Total Out</th>
                        <th class="px-3 py-2 text-end">Expenses</th>
                        <th class="px-3 py-2 text-end">Calculated</th>
                        <th class="px-3 py-2 text-end">Recorded</th>
                        <th class="px-3 py-2 text-end">Difference</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr style="<?= $r['status'] === 'mismatch' ? 'background:rgba(220,53,69,0.07);' : '' ?>">
                        <td class="px-3 py-2 fw-500"><?= htmlspecialchars($r['account']) ?></td>
                        <td class="px-3 py-2 text-muted"><?= ucfirst(str_replace('_', ' ', $r['type'])) ?></td>
                        <td class="px-3 py-2 text-end"><?= number_format($r['opening'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end" style="color:var(--success);">+<?= number_format($r['total_in'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end" style="color:var(--danger);">-<?= number_format($r['total_out'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end" style="color:var(--danger);">-<?= number_format($r['total_expenses'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end fw-600"><?= number_format($r['calculated'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end fw-600"><?= number_format($r['recorded'], DECIMAL_PLACES) ?></td>
                        <td class="px-3 py-2 text-end fw-600" style="color:<?= $r['status'] === 'mismatch' ? 'var(--danger)' : 'var(--success)' ?>;">
                            <?= $r['difference'] >= 0 ? '+' : '' ?><?= number_format($r['difference'], DECIMAL_PLACES) ?>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <?php if ($r['status'] === 'ok'): ?>
                            <span class="badge bg-success">✓ OK</span>
                            <?php else: ?>
                            <span class="badge bg-danger">⚠ Mismatch</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>$(document).ready(function(){
    $('#reconciliationTable').DataTable({ pageLength:50, paging:false, order:[], language:{search:'',searchPlaceholder:'Search...'}, pageLength:50, paging:false, order:[] });
});</script>
<p class="text-muted mt-3" style="font-size:0.78rem;">
    <i class="bi bi-info-circle me-1"></i>
    Calculated = Opening Balance + Total In - Total Out - Expenses.
    Recorded = current balance stored in the system.
    Any difference means a payment or adjustment was made outside normal workflow.
</p>
