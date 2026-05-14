<?php
$today = $today ?? date('Y-m-d');
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1"><i class="bi bi-truck-front me-2 text-primary"></i>Mandoob Inventory</h1>
        <p class="page-subtitle mb-0">Physical van stock counts — default reminder every 3 months. Not the same as warehouse stock.</p>
    </div>
    <?php if (Auth::can('mandoob_inventory', 'add')): ?>
    <a href="?page=mandoob_inventory&action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add mandoob</a>
    <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
<div class="alert alert-info border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i>No mandoobs yet for <strong><?= htmlspecialchars(Auth::warehouseName()) ?></strong>. Add names to track last count and next due dates.
</div>
<?php else: ?>
<style>
.mi-next-cell { min-width: 220px; max-width: 320px; vertical-align: middle; }
.mi-next-row { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.35rem 0.65rem; }
.mi-next-date { font-weight: 600; white-space: nowrap; }
.mi-due-progress { height: 6px; border-radius: 4px; background: rgba(100,116,139,0.18); overflow: hidden; }
.mi-due-progress .progress-bar { border-radius: 4px; transition: width 0.25s ease; }
</style>
<div class="table-responsive card border-0 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Interval</th>
                <th>Last count</th>
                <th>Next due</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $next = $r['next_due_date'] ?? null;
            $last = $r['last_count_date'] ?? null;
            $intervalM = max(1, min(24, (int) ($r['interval_months'] ?? 3)));

            $statusText  = '';
            $statusClass = 'text-muted';
            $diffDays    = null;
            $barWidth    = null;
            $barClass    = 'bg-success';

            if (!empty($next)) {
                $t0 = strtotime($today . ' 12:00:00');
                $t1 = strtotime((string) $next . ' 12:00:00');
                if ($t0 !== false && $t1 !== false) {
                    $diffDays = (int) floor(($t1 - $t0) / 86400);

                    if ($diffDays < 0) {
                        $n = abs($diffDays);
                        $statusText  = $n === 1 ? '1 day overdue' : $n . ' days overdue';
                        $statusClass = 'text-danger fw-semibold';
                        $barWidth    = 100;
                        $barClass    = 'bg-danger';
                    } elseif ($diffDays === 0) {
                        $statusText  = 'Due today';
                        $statusClass = 'text-warning fw-semibold';
                        $barWidth    = 4;
                        $barClass    = 'bg-warning';
                    } elseif ($diffDays === 1) {
                        $statusText  = '1 day remaining';
                        $statusClass = 'text-warning';
                    } else {
                        $statusText  = $diffDays . ' days remaining';
                        $statusClass = $diffDays <= 7 ? 'text-warning' : 'text-success';
                    }

                    if ($diffDays >= 0) {
                        $cycleDays = null;
                        if (!empty($last)) {
                            $tsL = strtotime((string) $last . ' 12:00:00');
                            $tsN = $t1;
                            if ($tsL !== false && $tsN !== false && $tsN > $tsL) {
                                $cycleDays = (int) floor(($tsN - $tsL) / 86400);
                            }
                        }
                        if ($cycleDays === null || $cycleDays < 1) {
                            $cycleDays = max(1, (int) round($intervalM * 30.437));
                        }
                        $rawPct = ($cycleDays > 0) ? ($diffDays / $cycleDays) * 100 : 0;
                        $barWidth = (int) round(min(100, max(4, $rawPct)));
                        if ($diffDays === 0) {
                            $barWidth = 4;
                        }
                        if ($diffDays <= 7) {
                            $barClass = 'bg-warning';
                        } else {
                            $barClass = 'bg-success';
                        }
                    }
                }
            } else {
                $statusText  = 'No due date set';
                $statusClass = 'text-muted';
            }
        ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $r['name']) ?></td>
                <td><?= $r['phone'] !== null && $r['phone'] !== '' ? htmlspecialchars((string) $r['phone']) : '—' ?></td>
                <td><?= (int) ($r['interval_months'] ?? 3) ?> mo</td>
                <td><?= !empty($r['last_count_date']) ? htmlspecialchars((string) $r['last_count_date']) : '—' ?></td>
                <td class="mi-next-cell">
                    <div class="mi-next-row">
                        <span class="mi-next-date"><?= !empty($r['next_due_date']) ? htmlspecialchars((string) $r['next_due_date']) : '—' ?></span>
                        <?php if ($statusText !== ''): ?>
                        <span class="small mi-due-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusText) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($barWidth !== null): ?>
                    <div class="progress mi-due-progress mt-1" role="progressbar"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $barWidth ?>"
                         aria-label="Time remaining until next due">
                        <div class="progress-bar <?= htmlspecialchars($barClass) ?>" style="width: <?= (int) $barWidth ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                    <?php if (Auth::can('mandoob_inventory', 'edit')): ?>
                    <form method="post" action="?page=mandoob_inventory&action=record_count"
                          class="d-inline mi-restart-countdown-form"
                          data-interval-months="<?= (int) ($r['interval_months'] ?? 3) ?>">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success"
                                title="Sets last physical count to today and schedules the next due date from your interval (e.g. 3 months).">
                            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Inventory done
                        </button>
                    </form>
                    <a class="btn btn-sm btn-outline-primary" href="?page=mandoob_inventory&action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                    <?php endif; ?>
                    <?php if (Auth::can('mandoob_inventory', 'delete')): ?>
                    <form method="post" action="?page=mandoob_inventory&action=delete" class="d-inline mi-del-form">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
(function () {
    document.querySelectorAll('.mi-del-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Remove this mandoob from the schedule list?')) {
                e.preventDefault();
            }
        });
    });
    document.querySelectorAll('.mi-restart-countdown-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var mo = parseInt(form.getAttribute('data-interval-months') || '3', 10);
            if (!Number.isFinite(mo) || mo < 1) mo = 3;
            var msg = 'Mark inventory as done today and restart the countdown?\n\n'
                + 'Last count → today\n'
                + 'Next due → today + ' + mo + ' month' + (mo === 1 ? '' : 's') + '.';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });
})();
</script>
