<?php
$row       = $row ?? [];
$today     = $today ?? date('Y-m-d');
$name      = (string) ($row['name'] ?? 'Mandoob');
$intervalM = max(1, min(24, (int) ($row['interval_months'] ?? 3)));
$isPaused  = (int) ($row['is_paused'] ?? 0) === 1;
$last      = (string) ($row['last_count_date'] ?? '');
$next      = (string) ($row['next_due_date'] ?? '');
$id        = (int) ($row['id'] ?? 0);

$previewNext = $today;
$dt = DateTimeImmutable::createFromFormat('Y-m-d', $today);
if ($dt) {
    $previewNext = $dt->modify('+' . $intervalM . ' months')->format('Y-m-d');
}
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="?page=mandoob_inventory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title mb-0"><i class="bi bi-arrow-counterclockwise me-2 text-success"></i>Reset inventory date</h1>
            <p class="page-subtitle mb-0">
                <?= htmlspecialchars($name) ?>
                <?php if (!empty($row['phone'])): ?>
                · <?= htmlspecialchars((string) $row['phone']) ?>
                <?php endif; ?>
                · every <?= $intervalM ?> mo
                <?php if ($isPaused): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1">Paused — saving will resume</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4" style="max-width:720px;">
    <div class="col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Current last count</div>
                <div class="fw-semibold"><?= $last !== '' ? htmlspecialchars($last) : '—' ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Current next due</div>
                <div class="fw-semibold"><?= $next !== '' ? htmlspecialchars($next) : '—' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width:720px;">
    <div class="card-body">
        <?php if ($last !== ''): ?>
        <div class="alert alert-warning border-0 py-2 px-3 small mb-3">
            Inventory was already marked on <strong><?= htmlspecialchars($last) ?></strong>.
            Saving replaces that date and restarts the countdown.
        </div>
        <?php endif; ?>

        <form method="post" action="?page=mandoob_inventory&action=record_count" id="miResetForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="return_to" value="list">

            <div class="mb-3">
                <label class="form-label fw-semibold" for="miCountDate">Date inventory was done <span class="text-danger">*</span></label>
                <input type="date" name="count_date" id="miCountDate" class="form-control" required
                       max="<?= htmlspecialchars($today) ?>" value="<?= htmlspecialchars($today) ?>">
                <div class="form-text">
                    Next due will be <strong id="miPreviewNext"><?= htmlspecialchars($previewNext) ?></strong>
                    (this date + <?= $intervalM ?> month<?= $intervalM === 1 ? '' : 's' ?>).
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" for="miCountNotes">Notes <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="notes" id="miCountNotes" class="form-control" maxlength="500"
                       value="Inventory already done — date reset"
                       placeholder="e.g. counted last week at shop">
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i>Reset last count &amp; next due
                </button>
                <a href="?page=mandoob_inventory" class="btn btn-outline-secondary">Cancel</a>
                <a href="?page=mandoob_inventory&action=record&id=<?= $id ?>" class="btn btn-outline-primary ms-sm-auto">
                    Record counted items
                </a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('miCountDate');
    var preview = document.getElementById('miPreviewNext');
    var months = <?= (int) $intervalM ?>;
    var last = <?= json_encode($last) ?>;
    if (!input || !preview) return;

    function pad(n) { return String(n).padStart(2, '0'); }
    function addMonths(ymd, m) {
        var p = String(ymd || '').split('-');
        if (p.length !== 3) return '';
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        if (isNaN(d.getTime())) return '';
        d.setMonth(d.getMonth() + m);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    function refresh() {
        preview.textContent = addMonths(input.value, months) || '—';
    }
    input.addEventListener('change', refresh);
    refresh();

    document.getElementById('miResetForm').addEventListener('submit', function (e) {
        var date = input.value || '';
        var next = addMonths(date, months);
        var msg = 'Reset inventory date for <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>?\n\n';
        if (last) msg += 'Current last count: ' + last + '\n';
        msg += 'New last count: ' + date + '\nNext due: ' + next;
        if (!window.confirm(msg)) e.preventDefault();
    });
})();
</script>
