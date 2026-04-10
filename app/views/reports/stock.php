<!-- Stock Report -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Stock Valuation</h1>
    <div class="ms-auto d-flex gap-2">
        <button onclick="exportReportCSV('stockRptTable','Stock_Valuation')" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="stock">
            <div class="col-6 col-md-3">
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
            </div>
        </form>
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
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#stockRptTable').DataTable({ pageLength:50, language:{search:'',searchPlaceholder:'Search...'}, pageLength:50 }); });</script>
