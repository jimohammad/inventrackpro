<?php
/** @var array<string,mixed> $row */
$st = (string) ($row['status'] ?? 'pending');
$badge = match ($st) {
    'approved' => 'success',
    'rejected' => 'danger',
    'used'     => 'secondary',
    'expired'  => 'dark',
    default    => 'warning',
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a href="?page=saleedits" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= htmlspecialchars((string) ($row['invoice_no'] ?? 'Edit request')) ?></h1>
    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Invoice</div>
            <div class="card-body">
                <div class="fw-bold">
                    <a href="?page=sales&action=detail&id=<?= (int) ($row['sale_id'] ?? 0) ?>">
                        <?= htmlspecialchars((string) ($row['invoice_no'] ?? '')) ?>
                    </a>
                </div>
                <div class="mt-1"><?= htmlspecialchars((string) ($row['party_name'] ?? '')) ?></div>
                <div class="text-muted mt-2" style="font-size:0.82rem;">
                    Invoice date <?= htmlspecialchars((string) ($row['sale_date'] ?? '')) ?>
                    · status <?= htmlspecialchars((string) ($row['sale_status'] ?? '')) ?>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header fw-semibold">Reason</div>
            <div class="card-body" style="white-space:pre-wrap;line-height:1.5;">
                <?= htmlspecialchars((string) ($row['reason'] ?? '')) ?>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body" style="font-size:0.85rem;">
                Requested by <strong><?= htmlspecialchars((string) ($row['requested_by_name'] ?? '')) ?></strong>
                <div class="text-muted mt-1"><?= htmlspecialchars((string) ($row['created_at'] ?? '')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($st === 'pending'): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header fw-semibold">Unlock this invoice?</div>
            <div class="card-body">
                <p class="text-muted" style="font-size:0.85rem;">
                    Approve lets this salesman open Edit / Add Item / Scan IMEIs for
                    <?= (int) SaleEditRequest::UNLOCK_MINUTES ?> minutes. The invoice Save closes the unlock.
                    Stock and IMEI change only when they Save — not when you approve.
                </p>
                <form method="POST" action="?page=saleedits&action=decide" class="mb-2">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Note (optional)</label>
                        <textarea name="staff_note" class="form-control" rows="3" maxlength="500"
                                  placeholder="Optional note to the salesman"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="decision" value="approved" class="btn btn-success">
                            <i class="bi bi-unlock"></i> Approve unlock
                        </button>
                        <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger" id="btnRejectSaleEdit">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                </form>
                <script>
                (function () {
                    var btn = document.getElementById('btnRejectSaleEdit');
                    if (!btn) return;
                    btn.addEventListener('click', function (e) {
                        if (!confirm('Reject this edit request?')) {
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
                <?php if (!empty($row['reviewed_at'])): ?>
                <div class="text-muted mt-2" style="font-size:0.82rem;">
                    <?= htmlspecialchars((string) $row['reviewed_at']) ?>
                    <?php if (!empty($row['reviewed_by_name'])): ?>
                        · <?= htmlspecialchars((string) $row['reviewed_by_name']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($row['unlocked_until'])): ?>
                <div class="mt-2" style="font-size:0.85rem;">
                    Unlocked until <?= htmlspecialchars((string) $row['unlocked_until']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($row['used_at'])): ?>
                <div class="mt-2" style="font-size:0.85rem;">
                    Used at <?= htmlspecialchars((string) $row['used_at']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($row['staff_note'])): ?>
                <div class="mt-3 p-2 rounded" style="background:var(--bg-body);white-space:pre-wrap;">
                    <?= htmlspecialchars((string) $row['staff_note']) ?>
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="?page=sales&action=detail&id=<?= (int) ($row['sale_id'] ?? 0) ?>">
                        Open invoice
                    </a>
                    <?php if ($st === 'approved'): ?>
                    <a class="btn btn-sm btn-warning" href="?page=sales&action=edit&id=<?= (int) ($row['sale_id'] ?? 0) ?>">
                        Admin edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
