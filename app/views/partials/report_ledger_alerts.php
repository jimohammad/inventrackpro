<?php
/** Report statement notices: validation errors and row caps. */
?>
<?php if (!empty($reportError)): ?>
<div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-octagon-fill flex-shrink-0"></i>
    <span class="small mb-0"><?= htmlspecialchars((string) $reportError) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($listTruncated)): ?>
<div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <span class="small mb-0">
        <strong>Showing the first <?= (int) ($listLimit ?? ListPage::REPORT_LEDGER_MAX) ?> of <?= number_format((int) ($ledgerTotalCount ?? 0)) ?> transactions</strong>
        in this period. Narrow the date range to load faster, or use
        <strong>Print</strong> / export for the full period (up to <?= number_format(ListPage::REPORT_LEDGER_PROCESS_MAX) ?> lines).
        Closing balance below is still calculated from the full period.
    </span>
</div>
<?php endif; ?>
