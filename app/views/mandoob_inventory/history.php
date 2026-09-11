<?php
$schedule        = $schedule ?? [];
$history         = $history ?? [];
$itemsByHistory  = $itemsByHistory ?? [];
$name            = (string) ($schedule['name'] ?? 'Mandoob');
$scheduleId      = (int) ($schedule['id'] ?? 0);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="?page=mandoob_inventory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Inventory History</h1>
            <p class="page-subtitle mb-0"><?= htmlspecialchars($name) ?> — <?= htmlspecialchars(Auth::warehouseName()) ?></p>
        </div>
    </div>
    <?php if (Auth::can('mandoob_inventory', 'edit')): ?>
    <a href="?page=mandoob_inventory&action=record&id=<?= $scheduleId ?>" class="btn btn-sm btn-success">
        <i class="bi bi-check-circle me-1"></i>Record inventory
    </a>
    <?php endif; ?>
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
    <i class="bi bi-info-circle me-2"></i>No inventory counts recorded yet. Use <strong>Inventory done</strong> to log items and reset the countdown.
</div>
<?php else: ?>
<?php foreach ($history as $h):
    $hid = (int) ($h['id'] ?? 0);
    $lines = $itemsByHistory[$hid] ?? [];
    $lineCount = count($lines);
    $totalQty = 0;
    foreach ($lines as $line) {
        $totalQty += (int) ($line['quantity'] ?? 0);
    }
    if ($lineCount === 0) {
        $lineCount = (int) ($h['line_count'] ?? 0);
        $totalQty  = (int) ($h['total_qty'] ?? 0);
    }
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div>
                <div class="fw-semibold"><?= htmlspecialchars((string) ($h['count_date'] ?? '')) ?></div>
                <div class="small text-muted">
                    Next due <?= !empty($h['next_due_after']) ? htmlspecialchars((string) $h['next_due_after']) : '—' ?>
                    · <?= !empty($h['recorded_by_name']) ? htmlspecialchars((string) $h['recorded_by_name']) : '—' ?>
                    <?php if (!empty($h['created_at'])): ?>
                    · <?= htmlspecialchars((string) $h['created_at']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <?php if ($lineCount > 0): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <?= $lineCount ?> item<?= $lineCount === 1 ? '' : 's' ?> · qty <?= $totalQty ?>
                </span>
                <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary">Date only</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($h['notes'])): ?>
        <p class="small mb-2"><?= htmlspecialchars((string) $h['notes']) ?></p>
        <?php endif; ?>
        <?php if ($lines !== []): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th class="text-center">Qty</th>
                        <th>IMEIs</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $line):
                    $imeis = trim((string) ($line['imeis'] ?? ''));
                    $imeiList = $imeis !== '' ? preg_split('/\s+/', $imeis) : [];
                ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string) ($line['item_name'] ?? '')) ?></td>
                        <td class="text-muted small"><?= !empty($line['sku']) ? htmlspecialchars((string) $line['sku']) : '—' ?></td>
                        <td class="text-center"><?= (int) ($line['quantity'] ?? 0) ?></td>
                        <td class="small font-monospace">
                            <?php if ($imeiList === []): ?>
                            —
                            <?php else: ?>
                            <?= htmlspecialchars(implode(', ', $imeiList)) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="mt-3">
    <a href="?page=mandoob_inventory" class="btn btn-outline-secondary btn-sm">Back to list</a>
    <?php if (Auth::can('mandoob_inventory', 'edit')): ?>
    <a href="?page=mandoob_inventory&action=edit&id=<?= $scheduleId ?>" class="btn btn-outline-primary btn-sm">Edit mandoob</a>
    <?php endif; ?>
</div>
