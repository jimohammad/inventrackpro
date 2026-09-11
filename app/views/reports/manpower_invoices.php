<?php
$periodQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate)
    . '&include_copies=' . ($includeCopies ? '1' : '0');
$printUrl = '?page=reports&action=manpowerInvoicesPrint&' . $periodQs;
$toToday  = ListPage::defaultToDate();
$copiesQs = '&include_copies=' . ($includeCopies ? '1' : '0');
$periodPresets = [
    [
        'label' => '15 days',
        'from'  => ListPage::defaultFromDateDays(15),
        'to'    => $toToday,
    ],
    [
        'label' => '30 days',
        'from'  => ListPage::defaultFromDateDays(30),
        'to'    => $toToday,
    ],
    [
        'label' => '1 month',
        'from'  => ListPage::defaultFromDate(1),
        'to'    => $toToday,
    ],
    [
        'label' => '3 months',
        'from'  => ListPage::defaultFromDate(3),
        'to'    => $toToday,
    ],
];
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Manpower — Formal Invoices</h1>
            <p class="page-subtitle mb-0">Certified A4 copies of sales invoices for Public Authority of Manpower</p>
        </div>
    </div>
    <?php if (!empty($invoices) && empty($reportError)): ?>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF (A4)
        </a>
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <?php if (!empty($verifyUrl)): ?>
        <a href="<?= htmlspecialchars((string) $verifyUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm">
            <i class="bi bi-qr-code me-1"></i> Verification page
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="manpowerInvoices">
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
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">PDF contents</label>
                <select name="include_copies" class="form-select form-select-sm">
                    <option value="1" <?= $includeCopies ? 'selected' : '' ?>>Cover + full invoice copies</option>
                    <option value="0" <?= !$includeCopies ? 'selected' : '' ?>>Cover + invoice list only</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap gap-1 pt-1">
                    <?php foreach ($periodPresets as $preset):
                        $isActive = (string) $fromDate === $preset['from'] && (string) $toDate === $preset['to'];
                        $presetUrl = '?page=reports&action=manpowerInvoices&from_date=' . urlencode($preset['from'])
                            . '&to_date=' . urlencode($preset['to']) . $copiesQs;
                    ?>
                    <a href="<?= htmlspecialchars($presetUrl) ?>"
                       class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= htmlspecialchars($preset['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reportError)): ?>
<div class="alert alert-warning"><?= htmlspecialchars((string) $reportError) ?></div>
<?php else: ?>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Invoices</div>
                <div class="fs-4 fw-bold" style="color:#1e3a5f;"><?= (int) $summary['invoice_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Line items</div>
                <div class="fs-4 fw-bold" style="color:#1e3a5f;"><?= (int) $summary['line_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Sales total</div>
                <div class="fs-5 fw-bold" style="color:#1e3a5f;"><?= $money((float) $summary['grand_total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Branch</div>
                <div class="fs-6 fw-bold" style="color:#1e3a5f;"><?= htmlspecialchars((string) ($warehouse['name'] ?? 'Current')) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($summary['by_month'])): ?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($summary['by_month'] as $m):
        $monthStart = $m['month'] . '-01';
        $monthEndTs = strtotime('last day of ' . $m['month'] . '-01');
        $monthEnd   = $monthEndTs ? date('Y-m-d', $monthEndTs) : $monthStart;
        $monthUrl   = '?page=reports&action=manpowerInvoices&from_date=' . urlencode($monthStart)
            . '&to_date=' . urlencode($monthEnd)
            . '&include_copies=' . ($includeCopies ? '1' : '0');
    ?>
    <a href="<?= htmlspecialchars($monthUrl) ?>" class="badge rounded-pill text-decoration-none"
       style="background:#1e3a5f;font-weight:600;padding:6px 10px;">
        <?= htmlspecialchars((string) $m['label']) ?>
        · <?= (int) $m['count'] ?> inv
        · <?= $money((float) $m['total']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($invoices)): ?>
<div class="alert alert-info mb-0">No sales invoices found for this period (cancelled invoices are excluded).</div>
<?php else: ?>

<?php if ((int) $summary['invoice_count'] > 200): ?>
<div class="alert alert-warning">
    This pack has <strong><?= (int) $summary['invoice_count'] ?></strong> invoices.
    If PDF generation is slow, click a month chip above to print that month only.
</div>
<?php endif; ?>

<div class="card border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $previewLimit = 500;
            $previewTrunc = count($invoices) > $previewLimit;
            $previewRows  = $previewTrunc ? array_slice($invoices, 0, $previewLimit) : $invoices;
            foreach ($previewRows as $i => $inv):
            ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <a href="?page=sales&action=view&id=<?= (int) $inv['id'] ?>" class="fw-semibold text-decoration-none">
                            <?= htmlspecialchars((string) $inv['invoice_no']) ?>
                        </a>
                    </td>
                    <td><?= date('d M Y', strtotime((string) $inv['date'])) ?></td>
                    <td><?= htmlspecialchars((string) $inv['party_name']) ?></td>
                    <td class="text-center"><?= (int) $inv['line_count'] ?></td>
                    <td class="text-end fw-semibold"><?= $money((float) $inv['grand_total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($previewTrunc)): ?>
<p class="text-muted small mt-2 mb-0">Showing the first <?= (int) $previewLimit ?> of <?= (int) $summary['invoice_count'] ?> invoices on this screen. The PDF includes the full period.</p>
<?php endif; ?>
<p class="text-muted small mt-3 mb-0">
    Open <strong>Download PDF (A4)</strong> and choose <em>Save as PDF</em> in the print dialog.
    The file starts with a signed cover for the Public Authority of Manpower, then
    <?= $includeCopies ? 'a certified copy of each invoice.' : 'the invoice list only.' ?>
    Cancelled invoices are excluded.
    <?php if (!empty($verifyUrl)): ?>
    The PDF includes a verification QR. Officers can scan it to confirm this pack against live sales records.
    <?php endif; ?>
</p>
<?php endif; ?>
<?php endif; ?>
