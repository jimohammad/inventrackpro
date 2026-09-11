<?php
$ppPrintQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate);
if ($partyId) {
    $ppPrintQs .= '&party_id=' . (int) $partyId;
}
if ($status !== '') {
    $ppPrintQs .= '&status=' . urlencode((string) $status);
}
$ppPrintUrl = '?page=reports&action=partnerProfitPrint&' . $ppPrintQs;
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$hasData = !empty($rows) || !empty($payments);
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Partner Profit Report</h1>
            <p class="page-subtitle">Import partner profit accrued on shipments and payments made · <a href="?page=landedcost&action=partnerHistory">Paid history</a></p>
        </div>
    </div>
    <?php if ($hasData): ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success js-export-report-csv" data-table-id="partnerProfitTable" data-title="Partner_Profit"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <a href="<?= htmlspecialchars($ppPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="<?= htmlspecialchars($ppPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="partnerProfit">
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars((string) $toDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Partner</label>
                <select name="party_id" class="form-select">
                    <option value="">All partners</option>
                    <?php foreach ($partners as $pt): ?>
                    <option value="<?= (int) $pt['id'] ?>" <?= $partyId === (int) $pt['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pt['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($hasData): ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100" style="border-left:4px solid #b45309;">
            <div class="card-body">
                <p class="mb-1 text-muted" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Accrued (period)</p>
                <p class="mb-0 fw-bold" style="font-size:1.25rem;color:#b45309;"><?= number_format((float) $summary['totalAccrued'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                <small class="text-muted"><?= (int) $summary['lineCount'] ?> line<?= (int) $summary['lineCount'] === 1 ? '' : 's' ?> · <?= (int) $summary['totalQty'] ?> pcs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100" style="border-left:4px solid #059669;">
            <div class="card-body">
                <p class="mb-1 text-muted" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Paid (period)</p>
                <p class="mb-0 fw-bold" style="font-size:1.25rem;color:#059669;"><?= number_format((float) $summary['totalPaidCash'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                <small class="text-muted"><?= (int) $summary['paymentCount'] ?> payment<?= (int) $summary['paymentCount'] === 1 ? '' : 's' ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100" style="border-left:4px solid #d97706;">
            <div class="card-body">
                <p class="mb-1 text-muted" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Unpaid in period</p>
                <p class="mb-0 fw-bold" style="font-size:1.25rem;color:#d97706;"><?= number_format((float) $summary['totalOpenPeriod'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                <small class="text-muted">Open accruals dated in range</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100" style="border-left:4px solid #dc2626;">
            <div class="card-body">
                <p class="mb-1 text-muted" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Outstanding (all time)</p>
                <p class="mb-0 fw-bold" style="font-size:1.25rem;color:#dc2626;"><?= number_format((float) $summary['totalOpenAllTime'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></p>
                <small class="text-muted"><a href="?page=landedcost&action=partnerDue">Pay via Partner due →</a></small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam me-1"></i> Accrued partner profit</span>
        <span class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($periodLabel) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <p class="text-center text-muted py-4 mb-0">No accruals in this period for the selected filters.</p>
        <?php else: ?>
        <table id="partnerProfitTable" class="table mb-0" style="font-size:0.83rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Shipment</th>
                    <th>Partner</th>
                    <th>PO / Item</th>
                    <th class="text-end">Rate/pc</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
            <?php $n = 1; foreach ($rows as $r): ?>
            <tr>
                <td class="text-muted"><?= $n++ ?></td>
                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td>
                    <a href="?page=landedcost&action=view&id=<?= (int) $r['shipment_id'] ?>"><?= htmlspecialchars($r['shipment_no']) ?></a>
                    <?php if (!empty($r['received_date'])): ?>
                    <small class="text-muted d-block">Rcvd <?= date('d M Y', strtotime($r['received_date'])) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['partner_name'] ?? '—') ?></td>
                <td>
                    <span class="text-muted"><?= htmlspecialchars($r['po_no'] ?? '') ?></span><br>
                    <?= htmlspecialchars($r['item_name'] ?? '—') ?>
                </td>
                <td class="text-end"><?= number_format((float) ($r['partner_profit_per_pc'] ?? 0), DECIMAL_PLACES) ?></td>
                <td class="text-end"><?= (int) ($r['quantity'] ?? 0) ?></td>
                <td class="text-end fw-semibold" style="color:#b45309;"><?= number_format((float) $r['amount'], DECIMAL_PLACES) ?></td>
                <td>
                    <?php if (($r['status'] ?? '') === 'paid'): ?>
                    <span class="badge bg-success">Paid</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <?= !empty($r['payment_no']) ? htmlspecialchars($r['payment_no']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fffbeb;">
                    <td colspan="7" class="fw-bold">Total accrued</td>
                    <td class="text-end fw-bold" style="color:#b45309;"><?= number_format((float) $summary['totalAccrued'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($payments)): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack me-1"></i> Payments to partner</span>
        <span class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($periodLabel) ?></span>
    </div>
    <div class="card-body p-0">
        <table id="partnerPaymentsTable" class="table mb-0" style="font-size:0.83rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Payment</th>
                    <th>Partner</th>
                    <th>Shipment / PO / Item</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php $n = 1; foreach ($payments as $p): ?>
            <tr>
                <td class="text-muted"><?= $n++ ?></td>
                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($p['date'])) ?></td>
                <td><a href="?page=payments&action=view&id=<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['payment_no']) ?></a></td>
                <td><?= htmlspecialchars($p['partner_name'] ?? '—') ?></td>
                <td>
                    <?php if (!empty($p['shipment_no'])): ?>
                    <?= htmlspecialchars($p['shipment_no']) ?>
                    <?php if (!empty($p['po_no']) || !empty($p['item_name'])): ?>
                    <small class="text-muted d-block"><?= htmlspecialchars(trim(($p['po_no'] ?? '') . ' / ' . ($p['item_name'] ?? ''), ' /')) ?></small>
                    <?php endif; ?>
                    <?php else: ?>
                    <?= htmlspecialchars($p['notes'] ?? '—') ?>
                    <?php endif; ?>
                </td>
                <td class="text-end fw-semibold" style="color:#059669;"><?= number_format((float) $p['amount'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#ecfdf5;">
                    <td colspan="5" class="fw-bold">Total paid</td>
                    <td class="text-end fw-bold" style="color:#059669;"><?= number_format((float) $summary['totalPaidCash'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-handshake" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">No partner profit data found for the selected filters.</p>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){
    if ($('#partnerProfitTable tbody tr').length) {
        $('#partnerProfitTable').DataTable({ pageLength: 50, order: [[1, 'desc']], language: { search: '', searchPlaceholder: 'Search...' } });
    }
    if ($('#partnerPaymentsTable tbody tr').length) {
        $('#partnerPaymentsTable').DataTable({ pageLength: 50, order: [[1, 'desc']], language: { search: '', searchPlaceholder: 'Search...' } });
    }
});
</script>
