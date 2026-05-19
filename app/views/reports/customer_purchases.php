<!-- Customer Purchases Report -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Customer Purchases</h1>
        <p class="page-subtitle">Items purchased by a customer within a date range</p>
    </div>
    <?php if ($party && empty($reportError) && !empty($rows)): ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success js-export-report-csv" data-table-id="customerPurchasesRptTable" data-title="Customer_Purchases"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button type="button" class="btn btn-danger js-export-report-pdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <a href="?page=reports&action=customerPurchasesPrint&party_id=<?= (int) $partyId ?>&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
    </div>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="customerPurchases">
            <div class="col-md-5">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Customer</label>
                <select name="party_id" class="form-select" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $partyId === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                        <?php if (!empty($c['phone'])): ?> (<?= htmlspecialchars((string) $c['phone']) ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From</label>
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars((string) $fromDate) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars((string) $toDate) ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reportError)): ?>
<?php
    $reportAlertMessage = $reportError;
    include __DIR__ . '/../partials/report_ledger_alerts.php';
?>
<?php endif; ?>

<?php if ($party && empty($reportError)): ?>

<!-- Customer Info Banner -->
<div class="card mb-4" style="border-left:4px solid #8b5cf6;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:46px;height:46px;background:rgba(139,92,246,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-check" style="font-size:1.3rem;color:#8b5cf6;"></i>
            </div>
            <div>
                <div style="font-size:1.05rem;font-weight:700;color:var(--text-main);"><?= htmlspecialchars($party['name']) ?></div>
                <div style="font-size:0.78rem;color:var(--text-muted);">
                    <?php if (!empty($party['phone'])): ?><span class="me-3"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $party['phone']) ?></span><?php endif; ?>
                    <?php if (!empty($party['party_code'])): ?><span class="me-3"><i class="bi bi-hash me-1"></i><?= htmlspecialchars((string) $party['party_code']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="ms-auto text-end" style="font-size:0.78rem;color:var(--text-muted);">
                <?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?>
            </div>
        </div>
    </div>
</div>

<?php if ($listTruncated): ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Showing the first <?= (int) $listLimit ?> line items only. Narrow the date range for a complete list.
</div>
<?php endif; ?>

<?php if (!empty($rows)): ?>
<div class="row g-4 mb-4">

    <!-- Item Breakdown -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header" style="font-weight:700;font-size:0.85rem;">
                <i class="bi bi-box-seam me-2" style="color:#8b5cf6;"></i>Purchases by Item
            </div>
            <div class="card-body p-0">
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="background:#1e3a5f;">
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">#</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Item</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:center;">Qty</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itemBreakdown as $i => $ib): ?>
                    <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(139,92,246,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                        <td style="padding:8px 14px;color:var(--text-muted);font-size:0.75rem;"><?= $i + 1 ?></td>
                        <td style="padding:8px 14px;font-weight:600;color:var(--text-main);">
                            <?= htmlspecialchars($ib['name']) ?>
                            <?php if (!empty($ib['sku'])): ?>
                            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:400;"><?= htmlspecialchars((string) $ib['sku']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 14px;text-align:center;">
                            <span style="background:rgba(139,92,246,0.1);color:#8b5cf6;border-radius:5px;padding:2px 8px;font-weight:700;"><?= (int) $ib['qty'] ?></span>
                        </td>
                        <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--success);">
                            <?= APP_CURRENCY ?> <?= number_format($ib['total'], DECIMAL_PLACES) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:rgba(139,92,246,0.06);border-top:2px solid rgba(139,92,246,0.2);">
                            <td colspan="2" style="padding:8px 14px;font-weight:700;font-size:0.82rem;">Total (<?= (int) $summary['items'] ?> items)</td>
                            <td style="padding:8px 14px;text-align:center;font-weight:700;"><?= (int) $summary['qty'] ?></td>
                            <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header" style="font-weight:700;font-size:0.85rem;">
                <i class="bi bi-bar-chart me-2" style="color:#10b981;"></i>Monthly Trend
            </div>
            <div class="card-body p-0">
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="background:#1e3a5f;">
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Month</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:center;">Qty</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Amount</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($monthlyBreakdown as $i => $mb): ?>
                    <?php $share = $summary['revenue'] > 0 ? ($mb['total'] / $summary['revenue'] * 100) : 0; ?>
                    <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(16,185,129,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                        <td style="padding:8px 14px;font-weight:600;"><?= date('M Y', strtotime($mb['month'] . '-01')) ?></td>
                        <td style="padding:8px 14px;text-align:center;"><?= (int) $mb['qty'] ?></td>
                        <td style="padding:8px 14px;text-align:right;font-weight:600;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($mb['total'], DECIMAL_PLACES) ?></td>
                        <td style="padding:8px 14px;text-align:right;">
                            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                                <div style="width:50px;height:5px;background:var(--border-color);border-radius:3px;overflow:hidden;">
                                    <div style="width:<?= round($share) ?>%;height:100%;background:#10b981;border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.75rem;color:var(--text-muted);"><?= number_format($share, 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Line items -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="font-weight:700;font-size:0.85rem;">
        <span><i class="bi bi-list-ul me-2" style="color:#8b5cf6;"></i>All Purchases</span>
        <span style="font-size:0.78rem;font-weight:400;color:var(--text-muted);"><?= count($rows) ?> line items · <?= (int) $summary['invoices'] ?> invoices</span>
    </div>
    <div class="card-body p-0">
        <table id="customerPurchasesRptTable" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
            <thead>
                <tr style="background:#1e3a5f;">
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Date</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Invoice</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Item</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Warehouse</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:center;">Qty</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Unit Price</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Disc</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(139,92,246,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                <td style="padding:8px 14px;color:var(--text-muted);"><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td style="padding:8px 14px;">
                    <a href="?page=sales&action=detail&id=<?= (int) $r['sale_id'] ?>" style="color:#8b5cf6;font-weight:600;text-decoration:none;">
                        <?= htmlspecialchars((string) $r['invoice_no']) ?>
                    </a>
                </td>
                <td style="padding:8px 14px;font-weight:600;">
                    <?= htmlspecialchars($r['item_name']) ?>
                    <?php if (!empty($r['sku'])): ?>
                    <div style="font-size:0.72rem;color:var(--text-muted);font-weight:400;"><?= htmlspecialchars((string) $r['sku']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="padding:8px 14px;color:var(--text-muted);font-size:0.78rem;"><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
                <td style="padding:8px 14px;text-align:center;font-weight:700;color:#8b5cf6;"><?= (int) $r['quantity'] ?></td>
                <td style="padding:8px 14px;text-align:right;"><?= APP_CURRENCY ?> <?= number_format($r['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="padding:8px 14px;text-align:right;color:var(--text-muted);">
                    <?= $r['discount'] > 0 ? APP_CURRENCY . ' ' . number_format($r['discount'], DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($r['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:rgba(139,92,246,0.06);border-top:2px solid rgba(139,92,246,0.2);">
                    <td colspan="4" style="padding:9px 14px;font-weight:700;">Total</td>
                    <td style="padding:9px 14px;text-align:center;font-weight:700;color:#8b5cf6;"><?= (int) $summary['qty'] ?></td>
                    <td colspan="2"></td>
                    <td style="padding:9px 14px;text-align:right;font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($summary['revenue'], DECIMAL_PLACES) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-cart-x" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        No purchases found for <strong><?= htmlspecialchars($party['name']) ?></strong> in this date range.
    </div>
</div>
<?php endif; ?>

<?php elseif ($partyId && !$party): ?>
<div class="alert alert-danger">Customer not found or is not a customer account.</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-person-lines-fill" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        Select a customer and date range, then click <strong>Generate Report</strong>.
    </div>
</div>
<?php endif; ?>
<script>
$(document).ready(function () {
    if ($('#customerPurchasesRptTable tbody tr').length) {
        $('#customerPurchasesRptTable').DataTable({
            pageLength: 50,
            order: [[0, 'desc']],
            language: { search: '', searchPlaceholder: 'Search...' },
        });
    }
});
</script>
