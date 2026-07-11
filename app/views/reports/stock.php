<!-- Stock Report -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Stock Valuation</h1>
    <span class="badge bg-light text-dark border"><?= htmlspecialchars($warehouse['name'] ?? Auth::warehouseName()) ?></span>
    <?php $stockPrintUrl = '?page=reports&action=stockPrint'; ?>
    <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-sm btn-success js-export-report-csv" data-table-id="stockRptTable" data-title="Stock_Valuation"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <a href="<?= htmlspecialchars($stockPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="<?= htmlspecialchars($stockPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="stockRptTable">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Brand / Model</th>
                    <th class="text-center">Min Stock</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Cost Price</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-end">Stock Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $s): ?>
                <?php $isLow = (int)$s['stock'] <= (int)$s['min_stock'] && (int)$s['min_stock'] > 0; ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                    <td><small class="text-muted"><?= $s['sku'] ?: '—' ?></small></td>
                    <td><?= htmlspecialchars(trim(($s['brand']??'').' '.($s['model']??''))) ?: '—' ?></td>
                    <td class="text-center"><?= $s['min_stock'] ?></td>
                    <td class="text-center fw-bold" style="color:<?= $isLow ? 'var(--danger)':'var(--success)' ?>;"><?= $s['stock'] ?></td>
                    <td class="text-end"><?= APP_CURRENCY ?> <?= number_format($s['purchase_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-end"><?= APP_CURRENCY ?> <?= number_format($s['sale_price'], DECIMAL_PLACES) ?></td>
                    <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($s['stock_value'], DECIMAL_PLACES) ?></td>
                    <td>
                        <?php if ($isLow): ?>
                        <span class="badge" style="background:rgba(239,68,68,0.12);color:var(--danger);">Low</span>
                        <?php else: ?>
                        <span class="badge badge-paid" style="border-radius:5px;">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($data)): ?>
            <tfoot>
                <tr style="background:rgba(14,116,144,0.06);font-weight:700;">
                    <td colspan="4" class="text-end">Total Stock Value</td>
                    <td colspan="3"></td>
                    <td class="text-end" style="color:#0e7490;"><?= APP_CURRENCY ?> <?= number_format($totalValue, DECIMAL_PLACES) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#stockRptTable').DataTable({ pageLength:50, language:{search:'',searchPlaceholder:'Search...'}, pageLength:50 }); });</script>
