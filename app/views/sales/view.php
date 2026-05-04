<?php
function money($v) { return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES); }
$totalQty = 0;
foreach ($sale['items'] as $item) $totalQty += (int)$item['quantity'];
$hasBalance = $sale['balance'] > 0.001;
?>

<!-- Header -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=sales" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= $sale['invoice_no'] ?></h1>
    <span class="badge px-3 py-1" style="border-radius:20px;font-size:0.78rem;font-weight:700;
        background:<?= $hasBalance ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' ?>;
        color:<?= $hasBalance ? '#d97706' : '#059669' ?>;">
        <?= ucfirst($sale['status']) ?>
    </span>
    <div class="ms-auto d-flex gap-2">
        <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&thermal=1&autoprint=1" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <a href="?page=sales&action=thermalPrint&id=<?= $sale['id'] ?>&thermal=1&autoprint=1" target="_blank" class="btn btn-sm btn-outline-success">
            <i class="bi bi-receipt me-1"></i> Thermal
        </a>
        <a href="?page=sales&action=print&id=<?= $sale['id'] ?>&autopdf=1" target="_blank" class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:1px solid rgba(220,38,38,0.3);">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <?php if (Auth::isAdmin() && $sale['status'] !== 'cancelled'): ?>
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

    <?php if (Auth::isAdmin()): ?>
    <div class="border-top pt-3 mt-3">
        <p class="fw-bold mb-2" style="color:#92400e;">Reinstate this voided invoice</p>
        <p class="small mb-2 text-muted">Runs in one transaction: restores stock deductions and serials for this invoice, then sets <strong>paid amount to zero</strong> and <strong>balance = <?= money($sale['grand_total']) ?></strong> (fully unpaid). Payment lines were removed when the sale was voided — add receipts in Payments only if money was collected.</p>
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

        <?php if ($sale['notes']): ?>
        <div style="margin-top:12px;padding:10px 20px;font-size:0.82rem;color:var(--text-muted);">
            <i class="bi bi-sticky me-1"></i><?= nl2br(htmlspecialchars($sale['notes'])) ?>
        </div>
        <?php endif; ?>

    </div>
</div>
