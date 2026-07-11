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
.mi-actions-cell .btn { margin-bottom: 0.15rem; }
</style>
<div class="table-responsive card border-0 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Interval</th>
                <th>Status</th>
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
            $isPaused = (int) ($r['is_paused'] ?? 0) === 1;
            $pausedAt = $r['paused_at'] ?? null;

            $statusText  = '';
            $statusClass = 'text-muted';
            $diffDays    = null;
            $barWidth    = null;
            $barClass    = 'bg-success';

            if ($isPaused) {
                $statusText  = 'Paused' . ($pausedAt ? ' since ' . $pausedAt : '');
                $statusClass = 'text-secondary fw-semibold';
                $barWidth    = 100;
                $barClass    = 'bg-secondary';
            } elseif (!empty($next)) {
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
                <td>
                    <?php if ($isPaused): ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                        <i class="bi bi-pause-fill me-1" aria-hidden="true"></i>Paused
                    </span>
                    <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Active
                    </span>
                    <?php endif; ?>
                </td>
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
                         aria-label="<?= $isPaused ? 'Inventory paused' : 'Time remaining until next due' ?>">
                        <div class="progress-bar <?= htmlspecialchars($barClass) ?>" style="width: <?= (int) $barWidth ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-end text-nowrap mi-actions-cell">
                    <?php if (Auth::can('mandoob_inventory', 'edit')): ?>
                    <?php if ($isPaused): ?>
                    <form method="post" action="?page=mandoob_inventory&action=resume" class="d-inline mi-resume-form">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary"
                                title="Resume countdown — extends next due by days spent paused.">
                            <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Start inventory
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="?page=mandoob_inventory&action=pause" class="d-inline mi-pause-form">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                title="Pause countdown (e.g. vacation) — due date frozen until resumed.">
                            <i class="bi bi-pause-fill me-1" aria-hidden="true"></i>Pause
                        </button>
                    </form>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-success mi-open-count-modal"
                            data-id="<?= (int) $r['id'] ?>"
                            data-name="<?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-interval-months="<?= (int) ($r['interval_months'] ?? 3) ?>"
                            title="Record physical count and reset countdown from chosen date.">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Inventory done
                    </button>
                    <a class="btn btn-sm btn-outline-primary" href="?page=mandoob_inventory&action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-secondary" href="?page=mandoob_inventory&action=history&id=<?= (int) $r['id'] ?>"
                       title="View past inventory counts">
                        <i class="bi bi-clock-history me-1" aria-hidden="true"></i>History
                    </a>
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

<?php if (Auth::can('mandoob_inventory', 'edit')): ?>
<div class="modal fade" id="miCountDoneModal" tabindex="-1" aria-labelledby="miCountDoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="?page=mandoob_inventory&action=record_count" id="miCountDoneForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" id="miCountScheduleId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="miCountDoneModalLabel">
                        <i class="bi bi-check-circle text-success me-2" aria-hidden="true"></i>Record inventory
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 text-muted small" id="miCountDoneSubtitle"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="miCountDate">Date of inventory <span class="text-danger">*</span></label>
                        <input type="date" name="count_date" id="miCountDate" class="form-control" required
                               max="<?= htmlspecialchars($today) ?>" value="<?= htmlspecialchars($today) ?>">
                        <div class="form-text">Next due will be reset from this date + your reminder interval.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="miCountNotes">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="notes" id="miCountNotes" class="form-control" maxlength="500"
                               placeholder="e.g. counted at shop, partial stock">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Save &amp; reset countdown
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
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

    document.querySelectorAll('.mi-pause-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Pause inventory countdown?\n\nUse this when the mandoob is on vacation or unavailable. The due date will stay frozen until you press Start inventory.')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.mi-resume-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Start inventory again?\n\nThe countdown will resume and the next due date will be extended by the number of days paused.')) {
                e.preventDefault();
            }
        });
    });

    var modalEl = document.getElementById('miCountDoneModal');
    if (!modalEl) return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var idInput = document.getElementById('miCountScheduleId');
    var dateInput = document.getElementById('miCountDate');
    var notesInput = document.getElementById('miCountNotes');
    var subtitle = document.getElementById('miCountDoneSubtitle');
    var today = <?= json_encode($today) ?>;

    document.querySelectorAll('.mi-open-count-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id') || '';
            var name = btn.getAttribute('data-name') || '';
            var mo = parseInt(btn.getAttribute('data-interval-months') || '3', 10);
            if (!Number.isFinite(mo) || mo < 1) mo = 3;

            if (idInput) idInput.value = id;
            if (dateInput) dateInput.value = today;
            if (notesInput) notesInput.value = '';
            if (subtitle) {
                subtitle.textContent = 'Recording inventory for ' + name + '. Countdown resets — next due = this date + '
                    + mo + ' month' + (mo === 1 ? '' : 's') + '.';
            }
            modal.show();
            if (dateInput) {
                window.setTimeout(function () { dateInput.focus(); }, 200);
            }
        });
    });
})();
</script>
