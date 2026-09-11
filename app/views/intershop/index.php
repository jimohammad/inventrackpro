<?php
$rows = $rows ?? [];
$peerConfigured = !empty($peerConfigured);
$peerName = (string) ($peerName ?? 'other shop');
$canAdd = Auth::can('intershop', 'add');
$statusClass = [
    'sent'      => 'bg-success',
    'received'  => 'bg-primary',
    'pending'   => 'bg-warning text-dark',
    'failed'    => 'bg-danger',
    'cancelled' => 'bg-secondary',
];
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-1">Shop stock transfer</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Move phones and accessories to <strong><?= htmlspecialchars($peerName) ?></strong>. Stock only — no invoice or money.
        </p>
    </div>
    <?php if ($canAdd): ?>
    <a href="?page=intershop&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-box-arrow-right"></i> Send stock
    </a>
    <?php endif; ?>
</div>

<?php if (!$peerConfigured): ?>
<div class="alert alert-warning">
    Other shop URL / API key is not in <code>.env</code> yet
    (<code>INTERSHOP_PEER_URL</code> and <code>INTERSHOP_PEER_API_KEY</code>).
    You can still prepare a send after that is set.
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Direction</th>
                    <th>Other shop</th>
                    <th>Status</th>
                    <th>Their no</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="text-muted py-4 text-center">No shop transfers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <a href="?page=intershop&action=view&id=<?= (int) $r['id'] ?>">
                            <?= htmlspecialchars((string) $r['transfer_no']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars((string) $r['date']) ?></td>
                    <td><?= ($r['direction'] ?? '') === 'inbound' ? 'Received' : 'Sent' ?></td>
                    <td><?= htmlspecialchars((string) ($r['peer_name'] ?? '')) ?></td>
                    <td>
                        <?php $st = (string) ($r['status'] ?? ''); ?>
                        <span class="badge <?= $statusClass[$st] ?? 'bg-secondary' ?>"><?= htmlspecialchars($st) ?></span>
                    </td>
                    <td><?= htmlspecialchars((string) ($r['peer_ref'] ?? '—')) ?></td>
                    <td class="text-end">
                        <a class="btn btn-outline-secondary btn-sm" href="?page=intershop&action=view&id=<?= (int) $r['id'] ?>">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
