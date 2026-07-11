<?php $totalQty = 0; foreach ($return['items'] as $item) $totalQty += (int)$item['quantity']; ?>

<!-- Header -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=returns" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= $return['return_no'] ?></h1>
    <?php
    $retStatus = $return['status'] ?? '';
    $statusBg  = $retStatus === 'approved' ? 'rgba(16,185,129,0.15)' : ($retStatus === 'cancelled' ? 'rgba(100,116,139,0.15)' : 'rgba(245,158,11,0.15)');
    $statusFg  = $retStatus === 'approved' ? '#059669' : ($retStatus === 'cancelled' ? '#475569' : '#d97706');
    ?>
    <span class="badge px-3 py-1" style="border-radius:20px;font-size:0.78rem;font-weight:700;
        background:<?= $statusBg ?>;
        color:<?= $statusFg ?>;">
        <?= $retStatus === 'cancelled' ? 'Voided' : ucfirst($retStatus) ?>
    </span>
    <div class="ms-auto d-flex gap-2">
        <a href="?page=returns&action=print&id=<?= $return['id'] ?>&autoprint=1" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <a href="?page=returns&action=print&id=<?= $return['id'] ?>&autopdf=1" class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:1px solid rgba(220,38,38,0.3);">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <?php if (Auth::isAdmin() && ($return['type'] ?? '') === 'sale_return' && $retStatus !== 'cancelled'): ?>
        <a href="?page=returns&action=edit&id=<?= $return['id'] ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <?php if (Auth::can('returns', 'delete') && $retStatus === 'approved'): ?>
        <form method="POST" action="?page=returns&action=cancel" class="d-inline">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $return['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger pin-protect">
                <i class="bi bi-x-circle me-1"></i> Void
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($retStatus === 'cancelled'): ?>
<div class="alert alert-secondary border mb-3" style="border-radius:12px;">
    <strong>Voided return</strong> — excluded from stock, party ledger, and linked invoice balances.
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-7">

        <!-- Amount Card -->
        <div class="card mb-3" style="border:none;background:linear-gradient(135deg,#fef2f2,#fee2e2);overflow:hidden;">
            <div class="card-body text-center py-3">
                <p style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#dc2626;margin-bottom:4px;">Return Total</p>
                <p style="font-size:1.6rem;font-weight:800;color:#991b1b;margin:0;"><?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></p>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card mb-3" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Return No</span>
                    <span style="font-weight:700;color:var(--primary);"><?= $return['return_no'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Date</span>
                    <span style="background:#e0f2fe;color:#0369a1;padding:2px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;"><?= date('d M Y', strtotime($return['date'])) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Type</span>
                    <span style="font-weight:700;"><?= ($return['type'] ?? '') === 'purchase_return' ? 'Purchase return (to supplier)' : 'Sale return (from customer)' ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);"><?= ($return['type'] ?? '') === 'purchase_return' ? 'Supplier' : 'Customer' ?></span>
                    <span style="font-weight:700;"><?= htmlspecialchars($return['party_name']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Branch</span>
                    <span style="font-weight:600;"><i class="bi bi-building me-1" style="color:var(--primary);"></i><?= htmlspecialchars($return['warehouse_name'] ?? '—') ?></span>
                </div>
                <?php if (!empty($return['original_invoice'])): ?>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border-color);font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Original Invoice</span>
                    <?php if (($return['type'] ?? '') === 'purchase_return' && !empty($return['ref_id'])): ?>
                    <a href="?page=purchases&action=detail&id=<?= (int) $return['ref_id'] ?>" style="font-weight:600;color:#b45309;text-decoration:none;"><?= htmlspecialchars($return['original_invoice']) ?></a>
                    <?php else: ?>
                    <span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($return['original_invoice']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($return['reason']): ?>
                <div style="display:flex;justify-content:space-between;padding:10px 20px;font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Reason</span>
                    <span style="text-align:right;max-width:60%;"><?= nl2br(htmlspecialchars($return['reason'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Card (compact) -->
        <div class="card" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:8px 20px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;display:flex;justify-content:space-between;">
                    <span><i class="bi bi-box-seam me-1" style="color:#dc2626;"></i> Returned Items</span>
                    <span style="color:#dc2626;"><?= count($return['items']) ?> item<?= count($return['items']) > 1 ? 's' : '' ?> / <?= $totalQty ?> pcs</span>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:0.8rem;">
                    <thead>
                        <tr style="background:rgba(220,38,38,0.06);">
                            <th style="padding:5px 12px 5px 20px;width:28px;color:var(--text-muted);font-weight:600;font-size:0.72rem;">#</th>
                            <th style="padding:5px 8px;font-weight:600;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.3px;color:var(--text-muted);">Item</th>
                            <th style="padding:5px 8px;text-align:center;width:40px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Qty</th>
                            <th style="padding:5px 8px;text-align:right;width:90px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Price</th>
                            <th style="padding:5px 20px 5px 8px;text-align:right;width:100px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($return['items'] as $i => $item): ?>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <td style="padding:4px 12px 4px 20px;color:var(--text-muted);font-size:0.72rem;"><?= $i+1 ?></td>
                            <td style="padding:4px 8px;font-weight:600;"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td style="padding:4px 8px;text-align:center;font-weight:600;"><?= $item['quantity'] ?></td>
                            <td style="padding:4px 8px;text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                            <td style="padding:4px 20px 4px 8px;text-align:right;font-weight:600;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display:flex;justify-content:space-between;padding:10px 20px;font-weight:800;color:#dc2626;font-size:0.9rem;border-top:2px solid #dc2626;">
                    <span>Total (<?= $totalQty ?> pcs)</span>
                    <span><?= APP_CURRENCY ?> <?= number_format($return['grand_total'], DECIMAL_PLACES) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>
