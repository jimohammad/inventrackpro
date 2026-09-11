<?php
$periodQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate)
    . '&imei_only=' . ($imeiOnly ? '1' : '0');
$printUrl = '?page=reports&action=bankKycPrint&' . $periodQs;
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Bank KYC — Sales with IMEI</h1>
            <p class="page-subtitle mb-0">A4 register of sales invoices with device serial numbers for bank / KYC submission</p>
        </div>
    </div>
    <?php if (!empty($invoices)): ?>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF (A4)
        </a>
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="bankKyc">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars((string) $toDate) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Scope</label>
                <select name="imei_only" class="form-select form-select-sm">
                    <option value="1" <?= $imeiOnly ? 'selected' : '' ?>>Invoices with IMEI only</option>
                    <option value="0" <?= !$imeiOnly ? 'selected' : '' ?>>All sales invoices (IMEI when available)</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                    <a href="?page=reports&action=bankKyc" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reportError)): ?>
<div class="alert alert-warning"><?= htmlspecialchars((string) $reportError) ?></div>
<?php elseif ($generated): ?>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Invoices</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= (int) $summary['invoice_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">IMEI Units</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= (int) $summary['imei_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Line Items</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= (int) $summary['line_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Sales Total</div>
                <div class="fs-5 fw-bold" style="color:#0e7490;"><?= $money((float) $summary['grand_total']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($invoices)): ?>
<div class="alert alert-info mb-0">No sales invoices found for this period<?= $imeiOnly ? ' with linked IMEI numbers' : '' ?>.</div>
<?php else: ?>

<div class="card border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">IMEIs</th>
                    <th>Items / Serials</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $i => $inv): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <a href="?page=sales&action=view&id=<?= (int) $inv['id'] ?>" class="fw-semibold text-decoration-none">
                            <?= htmlspecialchars((string) $inv['invoice_no']) ?>
                        </a>
                    </td>
                    <td><?= date('d M Y', strtotime((string) $inv['date'])) ?></td>
                    <td><?= htmlspecialchars((string) $inv['party_name']) ?></td>
                    <td class="text-end fw-semibold"><?= $money((float) $inv['grand_total']) ?></td>
                    <td class="text-center"><?= (int) $inv['imei_count'] ?></td>
                    <td style="max-width:320px;">
                        <?php
                        $preview = [];
                        foreach ($inv['items'] as $line) {
                            if (empty($line['imeis'])) {
                                continue;
                            }
                            foreach ($line['imeis'] as $im) {
                                $preview[] = $im['imei'];
                                if (count($preview) >= 4) {
                                    break 2;
                                }
                            }
                        }
                        if (empty($preview)):
                        ?>
                        <span class="text-muted">—</span>
                        <?php else: ?>
                        <code style="font-size:0.75rem;"><?= htmlspecialchars(implode(', ', $preview)) ?><?= (int) $inv['imei_count'] > count($preview) ? '…' : '' ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">
    Branch: <?= htmlspecialchars((string) ($warehouse['name'] ?? 'Current')) ?> ·
    Open <strong>Download PDF (A4)</strong> and choose <em>Save as PDF</em> in the print dialog for a bank-ready file.
</p>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-secondary mb-0">
    Select a period and click <strong>Generate</strong>. The PDF lists every sales invoice in that range with item lines and IMEI/serial numbers — suitable for company bank KYC.
</div>
<?php endif; ?>
