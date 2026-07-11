<!-- Sales Returns Report -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="page-title">Sales Returns Report</h1>
        <p class="page-subtitle">All sale returns by date range, customer, or reference</p>
    </div>
    <?php if (!empty($returns)):
        $salesReturnsPrintUrl = '?page=reports&action=salesReturnsPrint'
            . '&from_date=' . urlencode((string) $fromDate)
            . '&to_date=' . urlencode((string) $toDate)
            . ($partyId ? '&party_id=' . (int) $partyId : '')
            . ($search !== '' ? '&search=' . urlencode($search) : '');
    ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success js-export-report-csv" data-table-id="salesReturnsRptTable" data-title="Sales_Returns_Report"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <a href="<?= htmlspecialchars($salesReturnsPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="<?= htmlspecialchars($salesReturnsPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
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
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars((string) $toDate) ?>">
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
                    <span style="color:#0e7490;font-weight:700;"><?= number_format($cs['total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:3px;">
                    <div style="height:6px;background:linear-gradient(90deg,#0e7490,#06b6d4);border-radius:3px;width:<?= round($pct) ?>%;"></div>
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

        <table id="salesReturnsRptTable" class="sales-returns-table" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
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
                    $items    = $itemsByReturn[$ret['id']] ?? [];
                    $itemQty  = array_sum(array_column($items, 'quantity'));
                    $itemCnt  = count($items);
                ?>
                <tr class="sr-row">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;"><?= $n++ ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($ret['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;white-space:nowrap;">
                        <a href="?page=returns&action=detail&id=<?= $ret['id'] ?>" style="color:#0e7490;font-weight:700;text-decoration:none;">
                            <?= htmlspecialchars($ret['return_no']) ?>
                        </a>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#1e293b;font-weight:600;">
                        <?= htmlspecialchars($ret['party_name']) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#6366f1;">
                        <?= $ret['original_invoice'] ? htmlspecialchars($ret['original_invoice']) : '<span style="color:#cbd5e1;">—</span>' ?>
                    </td>
                    <td class="sr-items-cell" style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;font-size:0.8rem;">
                        <?php if (empty($items)): ?>
                        <span style="color:#cbd5e1;">—</span>
                        <?php else: ?>
                        <div class="sr-items-head">
                            <span class="sr-items-badge"><?= $itemCnt ?> line<?= $itemCnt === 1 ? '' : 's' ?> · <?= (int) $itemQty ?> units</span>
                        </div>
                        <div class="sr-items-grid">
                            <?php foreach ($items as $item): ?>
                            <div class="sr-item-line">
                                <span class="sr-item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                <span class="sr-item-qty">×<?= (int) $item['quantity'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:0.8rem;max-width:140px;">
                        <?= htmlspecialchars($ret['reason'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;font-size:0.78rem;white-space:nowrap;">
                        <?= htmlspecialchars($ret['created_by_name'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#0e7490;">
                        <?= number_format($ret['grand_total'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:linear-gradient(135deg,#ecfeff,#ccfbf1);">
                    <td colspan="8" style="padding:12px 14px;font-weight:700;color:#0f766e;">
                        Total — <?= count($returns) ?> returns · <?= $totalQty ?> items
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:1.05rem;font-weight:800;color:#0e7490;">
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
    if ($('#salesReturnsRptTable tbody tr').length) {
        $('#salesReturnsRptTable').DataTable({
            pageLength: 50,
            order: [[1, 'desc']],
            language: { search: '', searchPlaceholder: 'Search...' },
            columnDefs: [{ orderable: false, targets: 5 }]
        });
    }
});</script>
<style>
.sr-row:hover { background: #ecfeff !important; }
.sr-items-cell { min-width: 280px; }
.sr-items-head { margin-bottom: 4px; }
.sr-items-badge {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #0f766e;
    background: #ccfbf1;
    border: 1px solid #99f6e4;
    border-radius: 4px;
    padding: 2px 6px;
}
.sr-items-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 3px 14px;
}
.sr-item-line {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
    font-size: 0.78rem;
    line-height: 1.35;
}
.sr-item-name {
    flex: 1;
    min-width: 0;
    color: #334155;
}
.sr-item-qty {
    flex-shrink: 0;
    font-weight: 700;
    color: #0e7490;
    font-variant-numeric: tabular-nums;
}
@media (max-width: 992px) {
    .sr-items-grid { grid-template-columns: 1fr; }
}
</style>
