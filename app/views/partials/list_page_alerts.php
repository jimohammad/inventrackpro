<?php
/**
 * List page notices: default date range + row cap warning.
 * Expects: $listTruncated, $listLimit, $filters, $listPageName, optional $listPageExtra (query params for all_dates link).
 */
$listPageExtra = $listPageExtra ?? [];
?>
<?php if (!empty($datesDefaulted) && empty($filters['all_dates'])): ?>
<div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="status">
    <i class="bi bi-calendar3"></i>
    <span class="small mb-0">
        Showing <strong><?= htmlspecialchars((string) ($filters['from_date'] ?? '')) ?></strong>
        to <strong><?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?></strong> (current month by default).
        Change dates and click Filter, or
        <a href="<?= htmlspecialchars(ListPage::allDatesUrl($listPageName, $listPageExtra)) ?>" class="fw-semibold">show all dates</a>
        (still limited to <?= (int) ListPage::MAX_ROWS ?> rows).
    </span>
</div>
<?php elseif (!empty($filters['all_dates'])): ?>
<div class="alert alert-secondary border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="status">
    <i class="bi bi-calendar-range"></i>
    <span class="small mb-0">Showing all dates (newest first, max <?= (int) ListPage::MAX_ROWS ?> rows). Narrow the date range to load faster.</span>
</div>
<?php endif; ?>

<?php if (!empty($listTruncated)): ?>
<div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span class="small mb-0">
        <strong>Showing the latest <?= (int) ($listLimit ?? ListPage::MAX_ROWS) ?> records</strong> matching your filters.
        Narrow the date range or search to find specific entries.
        <?php if (empty($filters['all_dates'])): ?>
        <a href="<?= htmlspecialchars(ListPage::allDatesUrl($listPageName, $listPageExtra)) ?>" class="fw-semibold ms-1">Try all dates</a>
        <?php endif; ?>
    </span>
</div>
<?php endif; ?>
