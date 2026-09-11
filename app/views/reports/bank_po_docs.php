<?php
$periodQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate)
    . '&date_field=' . urlencode((string) $dateField);
$printUrl = '?page=reports&action=bankPoDocsPrint&' . $periodQs;
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$tooMany = !empty($generated) && empty($reportError) && (int) ($summary['file_count'] ?? 0) > 80;
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Bank — PO invoices &amp; TT</h1>
            <p class="page-subtitle mb-0">Supplier invoices and payment transfer copies uploaded on purchase orders, for the bank</p>
        </div>
    </div>
    <?php if (!empty($groups) && !$tooMany): ?>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> One PDF for bank
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="bankPoDocs">
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
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Date means</label>
                <select name="date_field" class="form-select form-select-sm">
                    <option value="uploaded" <?= ($dateField ?? 'uploaded') === 'uploaded' ? 'selected' : '' ?>>Uploaded on</option>
                    <option value="po" <?= ($dateField ?? '') === 'po' ? 'selected' : '' ?>>PO date</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                    <a href="?page=reports&action=bankPoDocs" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                <div class="text-muted text-uppercase small fw-bold">Purchase orders</div>
                <div class="fs-4 fw-bold" style="color:#1d4ed8;"><?= (int) $summary['po_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Supplier invoices</div>
                <div class="fs-4 fw-bold" style="color:#1d4ed8;"><?= (int) $summary['invoice_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">TT copies</div>
                <div class="fs-4 fw-bold" style="color:#047857;"><?= (int) $summary['tt_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Files</div>
                <div class="fs-4 fw-bold" style="color:#0f172a;"><?= (int) $summary['file_count'] ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($tooMany): ?>
<div class="alert alert-warning">Too many files for one PDF (<?= (int) $summary['file_count'] ?>). Narrow the dates, then use <strong>One PDF for bank</strong>.</div>
<?php endif; ?>

<?php if (empty($groups)): ?>
<div class="alert alert-info mb-0">No supplier invoices or TT copies found for this period.</div>
<?php else: ?>

<div class="card border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>PO</th>
                    <th>PO date</th>
                    <th>Supplier</th>
                    <th class="text-end">Foreign</th>
                    <th class="text-end">Paid</th>
                    <th>Files</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $i => $g): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <?php if (Auth::can('purchases', 'view')): ?>
                        <a href="?page=purchaseorders&action=show&id=<?= (int) $g['po_id'] ?>" class="fw-semibold text-decoration-none">
                            <?= htmlspecialchars((string) $g['po_no']) ?>
                        </a>
                        <?php else: ?>
                        <span class="fw-semibold"><?= htmlspecialchars((string) $g['po_no']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($g['po_date']) ? date('d M Y', strtotime((string) $g['po_date'])) : '—' ?></td>
                    <td><?= htmlspecialchars((string) $g['supplier_name']) ?></td>
                    <td class="text-end fw-semibold"><?php
                        $cur = strtoupper((string) ($g['currency'] ?? 'KWD'));
                        if ($cur !== '' && $cur !== 'KWD') {
                            echo htmlspecialchars(number_format((float) ($g['subtotal_foreign'] ?? 0), DECIMAL_PLACES) . ' ' . $cur);
                        } else {
                            echo '—';
                        }
                    ?></td>
                    <td class="text-end fw-semibold"><?= $money((float) $g['paid_kwd']) ?></td>
                    <td>
                        <?php foreach ($g['docs'] as $doc):
                            $isTt = (($doc['doc_type'] ?? '') === 'money_transfer');
                        ?>
                        <div class="mb-1">
                            <a href="?page=purchaseorders&action=downloadDoc&id=<?= (int) $doc['id'] ?>"
                               target="_blank" rel="noopener"
                               class="text-decoration-none"
                               style="font-size:0.78rem;color:<?= $isTt ? '#047857' : '#1d4ed8' ?>;">
                                <?= $isTt ? 'TT' : 'Invoice' ?>
                                · <?= htmlspecialchars((string) $doc['original_name']) ?>
                            </a>
                            <span class="text-muted" style="font-size:0.7rem;">
                                · <?= !empty($doc['created_at']) ? date('d M H:i', strtotime((string) $doc['created_at'])) : '' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php if (Auth::can('purchases', 'view')): ?>
                        <a href="?page=purchaseorders&action=printDocs&id=<?= (int) $g['po_id'] ?>"
                           target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"
                           title="This PO only">PDF</a>
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
    Default date is when the file was <strong>uploaded</strong>. Switch to <strong>PO date</strong> if the bank asked for that day’s orders.
    Cancelled POs are excluded.
</p>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-secondary mb-0">
    Choose a date (or range) and click <strong>Generate</strong>. The bank pack is every Supplier Invoice and TT Copy uploaded in that period — one PDF.
</div>
<?php endif; ?>
