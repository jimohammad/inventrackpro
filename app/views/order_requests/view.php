<?php
/** @var array<string,mixed> $order */
/** @var array<string,mixed>|null $reviewer */
$st = (string) ($order['status'] ?? 'pending');
$badge = match ($st) {
    'approved' => 'success',
    'rejected' => 'danger',
    'cancelled' => 'secondary',
    default => 'warning',
};
$phone = preg_replace('/\D+/', '', (string) ($order['customer_phone'] ?? ''));
$waText = rawurlencode(
    'Iqbal Electronics — regarding your order ' . ($order['request_no'] ?? '') . ':'
);
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="?page=orderrequests" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= htmlspecialchars((string) $order['request_no']) ?></h1>
    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Customer</div>
            <div class="card-body">
                <div class="fw-bold"><?= htmlspecialchars((string) $order['customer_name']) ?></div>
                <div class="mt-1">
                    <a href="tel:<?= htmlspecialchars((string) $order['customer_phone']) ?>">
                        <?= htmlspecialchars((string) $order['customer_phone']) ?>
                    </a>
                    <?php if ($phone !== ''): ?>
                    <a class="btn btn-sm btn-success ms-2" target="_blank" rel="noopener"
                       href="https://wa.me/<?= htmlspecialchars($phone) ?>?text=<?= $waText ?>">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                </div>
                <div class="text-muted mt-2" style="font-size:0.82rem;">
                    Submitted <?= htmlspecialchars((string) ($order['created_at'] ?? '')) ?>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header fw-semibold">Requested items</div>
            <div class="card-body" style="white-space:pre-wrap;line-height:1.5;">
                <?= htmlspecialchars((string) $order['items_text']) ?>
            </div>
        </div>
        <?php if (!empty($order['customer_notes'])): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold">Customer notes</div>
            <div class="card-body" style="white-space:pre-wrap;"><?= htmlspecialchars((string) $order['customer_notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <?php if ($st === 'pending'): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header fw-semibold text-warning-emphasis">Confirm this order</div>
            <div class="card-body">
                <p class="text-muted" style="font-size:0.85rem;">
                    Approving notifies the customer on their apps status page (and via browser notification if they allowed it).
                    This does not create a sale invoice — create the sale separately when they pick up / pay.
                </p>
                <form method="POST" action="?page=orderrequests&action=decide" class="mb-2">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Reply note (optional)</label>
                        <textarea name="staff_note" class="form-control" rows="3" maxlength="2000"
                                  placeholder="e.g. Available — ready for pickup today"></textarea>
                    </div>
                    <?php if (Auth::can('sales', 'edit') || Auth::can('sales', 'add')): ?>
                    <div class="d-grid gap-2">
                        <button type="submit" name="decision" value="approved" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Approve / confirm
                        </button>
                        <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger" id="btnRejectOrder">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-secondary mb-0">You need sales permission to confirm.</div>
                    <?php endif; ?>
                </form>
                <script>
                (function () {
                    var btn = document.getElementById('btnRejectOrder');
                    if (!btn) return;
                    btn.addEventListener('click', function (e) {
                        if (!confirm('Reject this order request?')) {
                            e.preventDefault();
                        }
                    });
                })();
                </script>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold">Decision</div>
            <div class="card-body">
                <div><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span></div>
                <?php if (!empty($order['reviewed_at'])): ?>
                <div class="text-muted mt-2" style="font-size:0.82rem;">
                    <?= htmlspecialchars((string) $order['reviewed_at']) ?>
                    <?php if (!empty($reviewer['name'])): ?>
                        · <?= htmlspecialchars((string) $reviewer['name']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['staff_note'])): ?>
                <div class="mt-3 p-2 rounded" style="background:var(--bg-body);white-space:pre-wrap;">
                    <?= htmlspecialchars((string) $order['staff_note']) ?>
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-primary" href="?page=sales&action=create">
                        <i class="bi bi-receipt"></i> Create sale invoice
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="font-size:0.82rem;color:var(--text-muted);">
                Public status link:<br>
                <a href="/apps/order?token=<?= urlencode((string) $order['public_token']) ?>" target="_blank" rel="noopener">
                    iqbal.app/apps/order?token=…
                </a>
            </div>
        </div>
    </div>
</div>
