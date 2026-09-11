<?php
/** @var list<array<string,mixed>> $rows */
/** @var string $status */
/** @var int $pendingCount */
$status = $status ?? 'pending';
?>
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-1">Sale edit requests</h1>
        <div class="text-muted" style="font-size:0.85rem;">
            Salesman asks to edit a saved invoice. Approve to unlock Edit for <?= (int) SaleEditRequest::UNLOCK_MINUTES ?> minutes or until they Save.
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= (int) $pendingCount ?> pending</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mb-3 d-flex flex-wrap gap-2">
    <?php
    $tabs = [
        'pending'  => 'Pending',
        'approved' => 'Unlocked',
        'used'     => 'Used',
        'rejected' => 'Rejected',
        'expired'  => 'Expired',
        'all'      => 'All',
    ];
    foreach ($tabs as $key => $label):
        $active = $status === $key;
    ?>
    <a href="?page=saleedits&status=<?= urlencode($key) ?>"
       class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= htmlspecialchars($label) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:0.9rem;">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Salesman</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>When</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No edit requests.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <?php
                    $st = (string) ($r['status'] ?? '');
                    $badge = match ($st) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'used'     => 'secondary',
                        'expired'  => 'dark',
                        default    => 'warning',
                    };
                    $preview = (string) ($r['reason'] ?? '');
                    if (mb_strlen($preview) > 80) {
                        $preview = mb_substr($preview, 0, 80) . '…';
                    }
                ?>
                <tr>
                    <td>
                        <a href="?page=sales&action=detail&id=<?= (int) $r['sale_id'] ?>" class="fw-semibold text-decoration-none">
                            <?= htmlspecialchars((string) $r['invoice_no']) ?>
                        </a>
                        <div class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars((string) ($r['party_name'] ?? '')) ?></div>
                    </td>
                    <td><?= htmlspecialchars((string) ($r['requested_by_name'] ?? '')) ?></td>
                    <td style="max-width:280px;"><?= htmlspecialchars($preview) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span></td>
                    <td class="text-muted" style="white-space:nowrap;font-size:0.82rem;">
                        <?= htmlspecialchars((string) ($r['created_at'] ?? '')) ?>
                    </td>
                    <td class="text-end">
                        <a href="?page=saleedits&action=view&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
