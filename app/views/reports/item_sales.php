<!-- Item Sales Report -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Item Sales Report</h1>
        <p class="page-subtitle">See exactly who bought an item and how much</p>
    </div>
    <?php if ($item && !empty($rows)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('itemSalesRptTable','Item_Sales_Report')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <a href="?page=reports&action=itemSalesPrint&item_id=<?= $itemId ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
    </div>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="itemSales">
            <div class="col-md-5">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Item</label>
                <select name="item_id" class="form-select" required>
                    <option value="">-- Select Item --</option>
                    <?php foreach ($items as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= $itemId == $it['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['name']) ?>
                        <?php if ($it['sku']): ?>(<?= $it['sku'] ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From</label>
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To</label>
                <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($item): ?>

<!-- Item Info Banner -->
<div class="card mb-4" style="border-left:4px solid #6366f1;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:46px;height:46px;background:rgba(99,102,241,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-box-seam" style="font-size:1.3rem;color:#6366f1;"></i>
            </div>
            <div>
                <div style="font-size:1.05rem;font-weight:700;color:var(--text-main);"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size:0.78rem;color:var(--text-muted);">
                    <?php if ($item['sku']): ?><span class="me-3"><i class="bi bi-upc me-1"></i><?= $item['sku'] ?></span><?php endif; ?>
                    <?php if ($item['brand']): ?><span class="me-3"><i class="bi bi-tag me-1"></i><?= $item['brand'] ?></span><?php endif; ?>
                    Sale Price: <?= APP_CURRENCY ?> <?= number_format($item['sale_price'], DECIMAL_PLACES) ?>
                </div>
            </div>
            <div class="ms-auto text-end" style="font-size:0.78rem;color:var(--text-muted);">
                <?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?>
            </div>
        </div>
    </div>
</div>


<?php if (!empty($rows)): ?>
<div class="row g-4 mb-4">

    <!-- Party Breakdown -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header" style="font-weight:700;font-size:0.85rem;">
                <i class="bi bi-people me-2" style="color:#6366f1;"></i>Sales by Party
            </div>
            <div class="card-body p-0">
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="background:#1e3a5f;">
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">#</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Party</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:center;">Qty</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($partyBreakdown as $i => $pb): ?>
                    <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(99,102,241,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                        <td style="padding:8px 14px;color:var(--text-muted);font-size:0.75rem;"><?= $i + 1 ?></td>
                        <td style="padding:8px 14px;font-weight:600;color:var(--text-main);"><?= htmlspecialchars($pb['name']) ?></td>
                        <td style="padding:8px 14px;text-align:center;">
                            <span style="background:rgba(99,102,241,0.1);color:#6366f1;border-radius:5px;padding:2px 8px;font-weight:700;"><?= $pb['qty'] ?></span>
                        </td>
                        <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--success);">
                            <?= APP_CURRENCY ?> <?= number_format($pb['total'], DECIMAL_PLACES) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:rgba(99,102,241,0.06);border-top:2px solid rgba(99,102,241,0.2);">
                            <td colspan="2" style="padding:8px 14px;font-weight:700;font-size:0.82rem;">Total</td>
                            <td style="padding:8px 14px;text-align:center;font-weight:700;"><?= $summary['qty'] ?></td>
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
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Revenue</th>
                            <th style="color:#fff;padding:8px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($monthlyBreakdown as $i => $mb): ?>
                    <?php $share = $summary['revenue'] > 0 ? ($mb['total'] / $summary['revenue'] * 100) : 0; ?>
                    <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(16,185,129,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                        <td style="padding:8px 14px;font-weight:600;"><?= date('M Y', strtotime($mb['month'] . '-01')) ?></td>
                        <td style="padding:8px 14px;text-align:center;"><?= $mb['qty'] ?></td>
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

<!-- Full Transaction Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="font-weight:700;font-size:0.85rem;">
        <span><i class="bi bi-list-ul me-2" style="color:#6366f1;"></i>All Transactions</span>
        <span style="font-size:0.78rem;font-weight:400;color:var(--text-muted);"><?= count($rows) ?> line items</span>
    </div>
    <div class="card-body p-0">
        <table id="itemSalesRptTable" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
            <thead>
                <tr style="background:#1e3a5f;">
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Date</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Invoice</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Party</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;">Warehouse</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:center;">Qty</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Unit Price</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Disc</th>
                    <th style="color:#fff;padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr style="background:<?= $i % 2 === 0 ? 'transparent' : 'rgba(99,102,241,0.03)' ?>;border-bottom:1px solid var(--border-color);">
                <td style="padding:8px 14px;color:var(--text-muted);"><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td style="padding:8px 14px;">
                    <a href="?page=sales&action=detail&id=<?= $r['sale_id'] ?>" style="color:#6366f1;font-weight:600;text-decoration:none;">
                        <?= $r['invoice_no'] ?>
                    </a>
                </td>
                <td style="padding:8px 14px;font-weight:600;"><?= htmlspecialchars($r['party_name']) ?></td>
                <td style="padding:8px 14px;color:var(--text-muted);font-size:0.78rem;"><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
                <td style="padding:8px 14px;text-align:center;font-weight:700;color:#6366f1;"><?= $r['quantity'] ?></td>
                <td style="padding:8px 14px;text-align:right;"><?= APP_CURRENCY ?> <?= number_format($r['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="padding:8px 14px;text-align:right;color:var(--text-muted);">
                    <?= $r['discount'] > 0 ? APP_CURRENCY . ' ' . number_format($r['discount'], DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($r['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:rgba(99,102,241,0.06);border-top:2px solid rgba(99,102,241,0.2);">
                    <td colspan="4" style="padding:9px 14px;font-weight:700;">Total</td>
                    <td style="padding:9px 14px;text-align:center;font-weight:700;color:#6366f1;"><?= $summary['qty'] ?></td>
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
        <i class="bi bi-box" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        No sales found for <strong><?= htmlspecialchars($item['name']) ?></strong> in this date range.
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-box-seam" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        Select an item above and click <strong>Generate Report</strong>.
    </div>
</div>
<?php endif; ?>
<script>$(document).ready(function(){
    if($('#itemSalesRptTable tbody tr').length){
        $('#itemSalesRptTable').DataTable({ pageLength:50, order:[[0,'desc']], language:{search:'',searchPlaceholder:'Search...'}, pageLength:50, order:[[0,'desc']] });
    }
});</script>
