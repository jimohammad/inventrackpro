<?php
$doc = $doc ?? [];
$status = (string) ($doc['status'] ?? '');
$outbound = ($doc['direction'] ?? '') === 'outbound';
$canRetry = $outbound && in_array($status, ['pending', 'failed'], true) && Auth::can('intershop', 'add');
$canCancel = $outbound && in_array($status, ['pending', 'failed'], true) && Auth::can('intershop', 'delete');
$statusClass = [
    'sent'      => 'bg-success',
    'received'  => 'bg-primary',
    'pending'   => 'bg-warning text-dark',
    'failed'    => 'bg-danger',
    'cancelled' => 'bg-secondary',
];
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="?page=intershop" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title mb-0"><?= htmlspecialchars((string) ($doc['transfer_no'] ?? '')) ?></h1>
            <p class="text-muted mb-0" style="font-size:0.82rem;">
                <?= $outbound ? 'Sent to' : 'Received from' ?>
                <?= htmlspecialchars((string) ($doc['peer_name'] ?? '')) ?>
                · <?= htmlspecialchars((string) ($doc['date'] ?? '')) ?>
            </p>
        </div>
    </div>
    <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>"><?= htmlspecialchars($status) ?></span>
</div>

<?php if (!empty($doc['last_error'])): ?>
<div class="alert alert-danger"><?= htmlspecialchars((string) $doc['last_error']) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3" style="font-size:0.9rem;">
            <div class="col-md-3"><span class="text-muted">Other shop no</span><div><?= htmlspecialchars((string) ($doc['peer_ref'] ?? '—')) ?></div></div>
            <div class="col-md-3"><span class="text-muted">By</span><div><?= htmlspecialchars((string) ($doc['created_by_name'] ?? '—')) ?></div></div>
            <div class="col-md-6"><span class="text-muted">Notes</span><div><?= htmlspecialchars((string) ($doc['notes'] ?? '—')) ?></div></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="text-end">Qty</th>
                    <th>Serials</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($doc['items'] ?? []) as $line): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $line['item_name']) ?></td>
                    <td><?= htmlspecialchars((string) $line['sku']) ?></td>
                    <td class="text-end"><?= (int) $line['quantity'] ?></td>
                    <td style="font-size:0.8rem;">
                        <?php
                        $imeis = array_map(static fn($r) => (string) ($r['imei'] ?? ''), $line['imeis'] ?? []);
                        echo $imeis ? htmlspecialchars(implode(', ', $imeis)) : '—';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <?php if ($canRetry): ?>
    <form method="POST" action="?page=intershop&action=retry">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
        <button class="btn btn-primary btn-sm" type="submit">Retry send</button>
    </form>
    <?php endif; ?>
    <?php if ($canCancel): ?>
    <form method="POST" action="?page=intershop&action=cancel" onsubmit="return confirm('Put this stock back on this shop? Only if the other shop did not receive it.');">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
        <button class="btn btn-outline-danger btn-sm" type="submit">Cancel &amp; restore stock</button>
    </form>
    <?php endif; ?>
</div>
