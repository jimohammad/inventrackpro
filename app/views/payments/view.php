<?php $isIn = ($payment['payment_type'] ?? 'in') === 'in'; ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=payments" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= $payment['payment_no'] ?></h1>
    <span class="badge px-3 py-1" style="border-radius:20px;font-size:0.78rem;font-weight:700;
        background:<?= $isIn ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' ?>;
        color:<?= $isIn ? '#059669' : '#dc2626' ?>;">
        <?= $isIn ? 'Payment In' : 'Payment Out' ?>
    </span>
    <div class="ms-auto d-flex flex-wrap gap-2 justify-content-end align-items-center" style="row-gap:8px;">
        <a href="?page=payments&action=print&id=<?= $payment['id'] ?>&autoprint=1" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <a href="?page=payments&action=print&id=<?= $payment['id'] ?>&autoprint=1&thermal=1" target="_blank" class="btn btn-sm btn-outline-success">
            <i class="bi bi-receipt me-1"></i> Thermal
        </a>
        <?php if (Auth::can('payments','edit')): ?>
        <a href="?page=payments&action=edit&id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <?php if (Auth::can('payments','delete') && ($payment['ref_type'] ?? '') !== 'discount'): ?>
        <form method="POST" action="?page=payments&action=delete" style="display:inline;"
              onsubmit="return confirm('Delete this payment permanently? Account and invoice balances will be reversed.');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$payment['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger pin-protect">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">

        <!-- Amount Card -->
        <div class="card mb-3" style="border:none;background:linear-gradient(135deg,<?= $isIn ? '#ecfdf5,#d1fae5' : '#fef2f2,#fee2e2' ?>);overflow:hidden;">
            <div class="card-body text-center py-4">
                <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:<?= $isIn ? '#059669' : '#dc2626' ?>;margin-bottom:8px;">
                    Amount <?= $isIn ? 'Received' : 'Paid' ?>
                </p>
                <p style="font-size:2rem;font-weight:800;color:<?= $isIn ? '#065f46' : '#991b1b' ?>;margin:0;">
                    <?= APP_CURRENCY ?> <?= number_format($payment['amount'], DECIMAL_PLACES) ?>
                </p>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card" style="border:none;">
            <div class="card-body px-0 py-0">

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Receipt No</span>
                    <span style="font-weight:700;font-size:0.88rem;color:var(--primary);"><?= $payment['payment_no'] ?></span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Date</span>
                    <span style="font-weight:600;font-size:0.88rem;">
                        <span style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:6px;font-size:0.78rem;">
                            <?= date('d M Y', strtotime($payment['date'])) ?>
                        </span>
                    </span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Party</span>
                    <span style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($payment['party_name'] ?? '—') ?></span>
                </div>

                <?php if (!empty($payment['phone_no'])): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Phone</span>
                    <span style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($payment['phone_no']) ?></span>
                </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Account</span>
                    <span style="font-weight:600;font-size:0.88rem;">
                        <i class="bi bi-wallet2 me-1" style="color:var(--primary);"></i><?= htmlspecialchars($payment['account_name'] ?? '—') ?>
                    </span>
                </div>

                <?php if ($payment['cheque_no']): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Cheque No</span>
                    <span style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($payment['cheque_no']) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($payment['notes']): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Notes</span>
                    <span style="font-size:0.85rem;text-align:right;max-width:60%;"><?= nl2br(htmlspecialchars($payment['notes'])) ?></span>
                </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Recorded by</span>
                    <span style="font-size:0.82rem;color:var(--text-muted);">
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($payment['created_by_name'] ?? '—') ?>
                    </span>
                </div>

            </div>
        </div>

    </div>
</div>
