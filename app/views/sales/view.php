<?php
function money($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); }
$totalQty = 0;
foreach ($sale['items'] as $item) $totalQty += (int)$item['quantity'];
$invoiceAr   = Sale::deriveInvoiceAr((float) ($sale['grand_total'] ?? 0), (float) ($sale['paid_amount'] ?? 0));
$hasBalance  = $invoiceAr['balance'] > 0.001;
$displayStatus = ($sale['status'] ?? '') === 'cancelled' ? 'cancelled' : $invoiceAr['status'];
?>

<!-- Header -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=sales" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= $sale['invoice_no'] ?></h1>
    <span class="badge px-3 py-1" style="border-radius:20px;font-size:0.78rem;font-weight:700;
        background:<?= $hasBalance ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' ?>;
        color:<?= $hasBalance ? '#d97706' : '#059669' ?>;">
        <?= ucfirst($displayStatus) ?>
    </span>
    <div class="ms-auto d-flex gap-2">
        <?php if ($hasBalance && ($sale['status'] ?? '') !== 'cancelled' && Auth::can('payments', 'add')): ?>
        <a href="?page=payments&action=receive&ref_type=sale&ref_id=<?= (int) $sale['id'] ?>"
           class="btn btn-sm btn-success">
            <i class="bi bi-cash-coin me-1"></i> Receive Payment
        </a>
        <?php endif; ?>
        <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&thermal=1&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <a href="?page=sales&action=thermalPrint&id=<?= $sale['id'] ?>&thermal=1&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success">
            <i class="bi bi-receipt me-1"></i> Thermal
        </a>
        <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&autopdf=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:1px solid rgba(220,38,38,0.3);">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <?php if (($sale['status'] ?? '') !== 'cancelled'): ?>
        <a href="?page=sales&action=exportInvoice&id=<?= (int) $sale['id'] ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Export invoice
        </a>
        <?php endif; ?>
        <?php if (!empty($canSaleEditNow) && $sale['status'] !== 'cancelled'): ?>
        <a href="?page=sales&action=edit&id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <?php if (Auth::can('sales','delete') && $sale['status'] !== 'cancelled' && $sale['status'] !== 'paid'): ?>
        <form method="POST" action="?page=sales&action=cancel" style="display:inline;" onsubmit="return confirm('Cancel this sale?')">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $sale['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i> Cancel</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$saleStatus = (string) ($sale['status'] ?? '');
$isCashierViewer = Auth::role() === 'cashier';
$pendingReq = $saleEditPending ?? null;
$unlockReq  = $saleEditUnlock ?? null;
$latestReq  = $saleEditLatest ?? null;
?>
<?php if ($saleStatus !== 'cancelled' && ($isCashierViewer || (Auth::isAdmin() && !empty($pendingReq)))): ?>
<div class="alert mb-3" style="border-radius:12px;<?php
    if (!empty($unlockReq)) {
        echo 'background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.35);';
    } elseif (!empty($pendingReq)) {
        echo 'background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.35);';
    } else {
        echo 'background:var(--bg-card);border:1px solid var(--border-color);';
    }
?>">
    <?php if (!empty($unlockReq)): ?>
        <strong>Edit unlocked</strong> until <?= htmlspecialchars((string) $unlockReq['unlocked_until']) ?>.
        Use <strong>Edit</strong> now. Saving the invoice closes this unlock.
    <?php elseif (!empty($pendingReq)): ?>
        <strong>Waiting for admin</strong>
        <?php if (!empty($pendingReq['requested_by_name'])): ?>
            — requested by <?= htmlspecialchars((string) $pendingReq['requested_by_name']) ?>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
            <a class="btn btn-sm btn-warning ms-2" href="?page=saleedits&action=view&id=<?= (int) $pendingReq['id'] ?>">Review</a>
        <?php endif; ?>
    <?php elseif ($isCashierViewer): ?>
        <?php if (!empty($latestReq) && ($latestReq['status'] ?? '') === 'rejected'): ?>
            <div class="mb-2"><strong>Last request was rejected.</strong>
                <?php if (!empty($latestReq['staff_note'])): ?>
                    <?= htmlspecialchars((string) $latestReq['staff_note']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="?page=saleedits&action=request" class="d-flex flex-wrap gap-2 align-items-end mb-0">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="sale_id" value="<?= (int) $sale['id'] ?>">
            <div class="flex-grow-1" style="min-width:220px;">
                <label class="form-label mb-1" style="font-size:0.78rem;font-weight:600;">Request edit (admin must approve)</label>
                <input type="text" name="reason" class="form-control form-control-sm" required minlength="5" maxlength="500"
                       placeholder="Why does this invoice need to change?">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-unlock me-1"></i> Request edit
            </button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (($sale['status'] ?? '') === 'cancelled'): ?>
<div class="alert alert-secondary border mb-3" style="border-radius:12px;">
    <strong>Cancelled invoice</strong> — it is excluded from customer ledgers and active sales totals.
    <?php if (!empty($cancelAudit)): ?>
    <p class="small mb-1 mt-2"><strong>Recorded in app audit log (Cancel action):</strong></p>
    <ul class="small mb-0">
        <?php foreach ($cancelAudit as $row): ?>
        <li>
            <?= date('Y-m-d H:i', strtotime($row['created_at'])) ?>
            <?php if (!empty($row['user_name'])): ?> — user: <strong><?= htmlspecialchars($row['user_name']) ?></strong><?php endif; ?>
            <?php if (!empty($row['ip_address'])): ?> — IP: <code><?= htmlspecialchars($row['ip_address']) ?></code><?php endif; ?>
            <?php if (!empty($row['description'])): ?> — <?= htmlspecialchars($row['description']) ?><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="small mb-0 mt-2 text-muted">No matching <code>cancel_sale</code> row in <code>activity_log</code> for this invoice.
        That usually means the status was changed outside the ERP (e.g. direct database edit) or the log predates this feature.</p>
    <?php endif; ?>

    <?php if (!empty($sale['payments'])): ?>
    <div class="border-top pt-3 mt-3">
        <p class="fw-bold mb-2"><i class="bi bi-cash-coin me-1"></i> Linked receipts</p>
        <ul class="small mb-0">
            <?php foreach ($sale['payments'] as $pay): ?>
            <?php $payVoided = (($pay['status'] ?? 'active') === 'cancelled'); ?>
            <li class="<?= $payVoided ? 'text-muted text-decoration-line-through' : '' ?>">
                <?= htmlspecialchars($pay['payment_no'] ?? '') ?>
                — <?= money((float)($pay['amount'] ?? 0)) ?>
                — <?= htmlspecialchars($pay['date'] ?? '') ?>
                <?php if ($payVoided): ?><span class="badge bg-secondary ms-1">voided with invoice</span><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (Auth::isAdmin()): ?>
    <div class="border-top pt-3 mt-3">
        <p class="fw-bold mb-2" style="color:#92400e;">Reinstate this voided invoice</p>
        <p class="small mb-2 text-muted">Runs in one transaction: restores stock deductions and serials for this invoice, and re-activates any payment rows that were voided with this invoice (account balances updated). If the sale was voided before this feature, re-enter receipts in Payments.</p>
        <form method="POST" action="?page=sales&action=reopen" id="formReopenSale">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning" id="btnReopenSale">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reinstate invoice
            </button>
        </form>
    </div>
    <script>
    (function() {
        var f = document.getElementById('formReopenSale');
        var b = document.getElementById('btnReopenSale');
        if (!f || !b) return;
        f.addEventListener('submit', function(e) {
            if (!confirm('Reinstate this voided invoice? Stock will be reduced again and serials marked sold. This cannot be undone from one button — use Cancel again if wrong.')) {
                e.preventDefault();
            }
        });
    })();
    </script>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">

        <!-- Amount Card -->
        <div class="card mb-3" style="border:none;background:linear-gradient(135deg,#eff6ff,#dbeafe);overflow:hidden;">
            <div class="card-body text-center py-4">
                <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#3b82f6;margin-bottom:8px;">
                    Grand Total
                </p>
                <p style="font-size:2rem;font-weight:800;color:#1e3a5f;margin:0;">
                    <?= money($sale['grand_total']) ?>
                </p>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card mb-3" style="border:none;">
            <div class="card-body px-0 py-0">

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Invoice No</span>
                    <span style="font-weight:700;font-size:0.88rem;color:var(--primary);"><?= $sale['invoice_no'] ?></span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Date</span>
                    <span style="font-weight:600;font-size:0.88rem;">
                        <span style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:6px;font-size:0.78rem;">
                            <?= date('d M Y', strtotime($sale['date'])) ?>
                        </span>
                    </span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border-color);gap:10px;">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Customer</span>
                    <span style="font-weight:700;font-size:0.88rem;text-align:right;">
                        <?= htmlspecialchars($sale['party_name'] ?? '—') ?>
                        <?php if (!empty($sale['party_phone'])): ?>
                        <small class="text-muted ms-1"><?= htmlspecialchars($sale['party_phone']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($sale['party_id']) && Auth::can('customers', 'view')): ?>
                        <a href="?page=parties&action=detail&id=<?= (int)$sale['party_id'] ?>" class="btn btn-sm btn-outline-primary ms-2" style="font-size:0.72rem;border-radius:6px;vertical-align:middle;">Ledger</a>
                        <?php endif; ?>
                    </span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Branch</span>
                    <span style="font-weight:600;font-size:0.88rem;">
                        <i class="bi bi-building me-1" style="color:var(--primary);"></i><?= htmlspecialchars($sale['warehouse_name'] ?? '—') ?>
                    </span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Paid</span>
                    <span style="font-weight:700;font-size:0.88rem;color:#059669;"><?= money($sale['paid_amount']) ?></span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Invoice Balance</span>
                    <span style="font-weight:700;font-size:0.88rem;color:<?= $hasBalance ? '#d97706' : '#059669' ?>;"><?= money($sale['balance']) ?></span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Customer Outstanding</span>
                    <span style="font-weight:700;font-size:0.88rem;color:#6366f1;"><?= money($sale['party_total_balance'] ?? 0) ?></span>
                </div>

                <div style="display:flex;justify-content:space-between;padding:14px 20px;">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Created by</span>
                    <span style="font-size:0.82rem;color:var(--text-muted);">
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($sale['created_by_name'] ?? '—') ?>
                    </span>
                </div>

            </div>
        </div>

        <!-- Items Card -->
        <div class="card" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:8px 20px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;display:flex;justify-content:space-between;">
                    <span><i class="bi bi-box-seam me-1" style="color:var(--primary);"></i> Items</span>
                    <span style="color:var(--primary);"><?= count($sale['items']) ?> item<?= count($sale['items']) > 1 ? 's' : '' ?> / <?= $totalQty ?> pcs</span>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:0.8rem;">
                    <thead>
                        <tr style="background:rgba(99,102,241,0.06);">
                            <th style="padding:5px 12px 5px 20px;width:28px;color:var(--text-muted);font-weight:600;font-size:0.72rem;text-transform:uppercase;">#</th>
                            <th style="padding:5px 8px;font-weight:600;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.3px;color:var(--text-muted);">Item</th>
                            <th style="padding:5px 8px;text-align:center;width:40px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Qty</th>
                            <th style="padding:5px 8px;text-align:right;width:90px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Price</th>
                            <th style="padding:5px 20px 5px 8px;text-align:right;width:100px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sale['items'] as $i => $item): ?>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <td style="padding:4px 12px 4px 20px;color:var(--text-muted);font-size:0.72rem;"><?= $i+1 ?></td>
                            <td style="padding:4px 8px;font-weight:600;">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </td>
                            <td style="padding:4px 8px;text-align:center;font-weight:600;"><?= $item['quantity'] ?></td>
                            <td style="padding:4px 8px;text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                            <td style="padding:4px 20px 4px 8px;text-align:right;font-weight:600;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($sale['discount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 20px;border-top:1px solid var(--border-color);font-size:0.82rem;">
                    <span class="text-muted">Discount</span>
                    <span style="color:var(--danger);font-weight:600;">- <?= money($sale['discount']) ?></span>
                </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;padding:10px 20px;font-weight:800;color:var(--primary);font-size:0.9rem;border-top:2px solid var(--primary);">
                    <span>Grand Total (<?= $totalQty ?> pcs)</span>
                    <span><?= money($sale['grand_total']) ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($linkedReturns)): ?>
        <?php
        $linkedReturnTotal = 0.0;
        foreach ($linkedReturns as $lrSum) {
            $linkedReturnTotal += (float) ($lrSum['grand_total'] ?? 0);
        }
        ?>
        <div class="card mb-3" style="border:none;border:1px solid rgba(220,38,38,0.2);">
            <div class="card-body px-0 py-0">
                <div style="padding:10px 20px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;display:flex;justify-content:space-between;align-items:center;background:rgba(220,38,38,0.06);">
                    <span><i class="bi bi-arrow-return-left me-1" style="color:#dc2626;"></i> Sale returns (credit notes)</span>
                    <span style="color:#dc2626;font-size:0.75rem;"><?= count($linkedReturns) ?> · <?= money($linkedReturnTotal) ?></span>
                </div>
                <p class="small mb-0 px-3 pt-2" style="color:#7f1d1d;line-height:1.45;">
                    These returns already reduced <strong>Customer Outstanding</strong> for <strong>this customer</strong>.
                    They do <strong>not</strong> change <strong>Invoice Balance</strong> — that stays unpaid until cash is received on this invoice.
                </p>
                <table style="width:100%;border-collapse:collapse;font-size:0.8rem;margin-top:4px;">
                    <thead>
                        <tr style="background:rgba(220,38,38,0.04);">
                            <th style="padding:6px 12px 6px 20px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Return</th>
                            <th style="padding:6px 8px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Date</th>
                            <th style="padding:6px 8px;text-align:right;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Amount</th>
                            <th style="padding:6px 20px 6px 8px;font-weight:600;font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linkedReturns as $lr): ?>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <td style="padding:8px 12px 8px 20px;font-weight:700;">
                                <?php if (Auth::can('returns', 'view')): ?>
                                <a href="?page=returns&action=detail&id=<?= (int) $lr['id'] ?>" style="color:#dc2626;text-decoration:none;">
                                    <?= htmlspecialchars((string) $lr['return_no']) ?>
                                </a>
                                <?php else: ?>
                                <?= htmlspecialchars((string) $lr['return_no']) ?>
                                <?php endif; ?>
                                <?php if (!empty($lr['created_by_name'])): ?>
                                <div class="small text-muted" style="font-weight:500;">by <?= htmlspecialchars((string) $lr['created_by_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:8px;">
                                <span style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                                    <?= date('m/d/Y', strtotime((string) ($lr['created_at'] ?? $lr['date']))) ?>
                                </span>
                            </td>
                            <td style="padding:8px;text-align:right;font-weight:700;color:#dc2626;">
                                <?= money((float) ($lr['grand_total'] ?? 0)) ?>
                            </td>
                            <td style="padding:8px 20px 8px 8px;">
                                <span class="badge px-2 py-1" style="border-radius:6px;background:rgba(16,185,129,0.15);color:#059669;">
                                    <?= htmlspecialchars(ucfirst((string) ($lr['status'] ?? ''))) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sale['notes']): ?>
        <div style="margin-top:12px;padding:10px 20px;font-size:0.82rem;color:var(--text-muted);">
            <i class="bi bi-sticky me-1"></i><?= nl2br(htmlspecialchars($sale['notes'])) ?>
        </div>
        <?php endif; ?>

    </div>
</div>
