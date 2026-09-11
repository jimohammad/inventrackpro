<?php
/**
 * List page notices: default date range.
 * Expects: $filters, $listPageName, optional $listPageExtra (query params for all_dates link).
 */
$listPageExtra = $listPageExtra ?? [];
$listDateDefaultLabel = $listDateDefaultLabel ?? 'current month by default';
$listHideDateDefaultAlert = !empty($listHideDateDefaultAlert);
?>
<?php if (!$listHideDateDefaultAlert && !empty($datesDefaulted) && empty($filters['all_dates'])): ?>
<div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="status">
    <i class="bi bi-calendar3"></i>
    <span class="small mb-0">
        Showing <strong><?= htmlspecialchars((string) ($filters['from_date'] ?? '')) ?></strong>
        to <strong><?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?></strong> (<?= htmlspecialchars($listDateDefaultLabel) ?>).
        Change dates and click Filter, or
        <a href="<?= htmlspecialchars(ListPage::allDatesUrl($listPageName, $listPageExtra)) ?>" class="fw-semibold">show all dates</a>
        (still limited to <?= (int) ListPage::MAX_ROWS ?> rows).
    </span>
</div>
<?php elseif (!$listHideDateDefaultAlert && !empty($filters['all_dates'])): ?>
<div class="alert alert-secondary border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="status">
    <i class="bi bi-calendar-range"></i>
    <span class="small mb-0">Showing all dates (newest first, max <?= (int) ListPage::MAX_ROWS ?> rows). Narrow the date range to load faster.</span>
</div>
<?php endif; ?>
