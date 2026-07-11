<?php
$schedule = $schedule ?? [];
$history  = $history ?? [];
$name     = (string) ($schedule['name'] ?? 'Mandoob');
?>
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="?page=mandoob_inventory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Inventory History</h1>
        <p class="page-subtitle mb-0"><?= htmlspecialchars($name) ?> — <?= htmlspecialchars(Auth::warehouseName()) ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Reminder interval</div>
                <div class="fw-semibold"><?= (int) ($schedule['interval_months'] ?? 3) ?> months</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Last count</div>
                <div class="fw-semibold"><?= !empty($schedule['last_count_date']) ? htmlspecialchars((string) $schedule['last_count_date']) : '—' ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Next due</div>
                <div class="fw-semibold">
                    <?= !empty($schedule['next_due_date']) ? htmlspecialchars((string) $schedule['next_due_date']) : '—' ?>
                    <?php if ((int) ($schedule['is_paused'] ?? 0) === 1): ?>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">Paused</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($history)): ?>
<div class="alert alert-info border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i>No inventory counts recorded yet. Use <strong>Inventory done</strong> on the main list to log the first count.
</div>
<?php else: ?>
<div class="table-responsive card border-0 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Count date</th>
                <th>Next due after</th>
                <th>Notes</th>
                <th>Recorded by</th>
                <th>Recorded at</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) ($h['count_date'] ?? '')) ?></td>
                <td><?= !empty($h['next_due_after']) ? htmlspecialchars((string) $h['next_due_after']) : '—' ?></td>
                <td><?= !empty($h['notes']) ? htmlspecialchars((string) $h['notes']) : '—' ?></td>
                <td><?= !empty($h['recorded_by_name']) ? htmlspecialchars((string) $h['recorded_by_name']) : '—' ?></td>
                <td class="text-muted small"><?= !empty($h['created_at']) ? htmlspecialchars((string) $h['created_at']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="mt-3">
    <a href="?page=mandoob_inventory" class="btn btn-outline-secondary btn-sm">Back to list</a>
    <?php if (Auth::can('mandoob_inventory', 'edit')): ?>
    <a href="?page=mandoob_inventory&action=edit&id=<?= (int) ($schedule['id'] ?? 0) ?>" class="btn btn-outline-primary btn-sm">Edit mandoob</a>
    <?php endif; ?>
</div>
