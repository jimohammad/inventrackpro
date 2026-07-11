<?php
function pv_money($v) { return APP_CURRENCY . ' ' . number_format((float)$v, DECIMAL_PLACES); }
$totalQty = 0;
foreach ($purchase['items'] as $item) {
    $totalQty += (int) $item['quantity'];
}
$hasBalance = ($purchase['balance'] ?? 0) > 0.001;
$pStatus = $purchase['status'] ?? '';
$statusStyles = match ($pStatus) {
    'paid'      => ['bg' => 'rgba(16,185,129,0.15)', 'fg' => '#059669'],
    'partial'   => ['bg' => 'rgba(245,158,11,0.15)', 'fg' => '#d97706'],
    'cancelled' => ['bg' => 'rgba(100,116,139,0.15)', 'fg' => '#475569'],
    default     => ['bg' => 'rgba(99,102,241,0.12)', 'fg' => '#4f46e5'],
};
$paidPct = ($purchase['grand_total'] ?? 0) > 0
    ? min(100, round(((float)($purchase['paid_amount'] ?? 0) / (float)$purchase['grand_total']) * 100))
    : 0;
$showForeign = !empty($poForeign);
$foreignCurrency = $showForeign ? (string) ($poForeign['currency'] ?? '') : '';
$showForeignRate = $showForeign
    && abs((float) ($poForeign['exchange_rate'] ?? 1) - 1.0) > 0.0000001;
$foreignBadgeBg = match ($foreignCurrency) {
    'AED'   => '#dbeafe',
    'USD'   => '#fef9c3',
    default => '#e0e7ff',
};
$foreignBadgeFg = match ($foreignCurrency) {
    'AED'   => '#1d4ed8',
    'USD'   => '#854d0e',
    default => '#4338ca',
};
?>

<!-- Header -->
<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <a href="?page=purchases" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= htmlspecialchars($purchase['invoice_no']) ?></h1>
    <span class="badge px-3 py-1" style="border-radius:20px;font-size:0.78rem;font-weight:700;background:<?= $statusStyles['bg'] ?>;color:<?= $statusStyles['fg'] ?>;">
        <?= ucfirst($pStatus) ?>
    </span>
    <div class="ms-auto d-flex gap-2 flex-wrap justify-content-end">
        <a href="?page=purchases&action=print&id=<?= (int)$purchase['id'] ?>&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print A4
        </a>
        <a href="?page=purchases&action=thermalPrint&id=<?= (int)$purchase['id'] ?>&thermal=1&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success">
            <i class="bi bi-receipt me-1"></i> Thermal
        </a>
        <?php if (Auth::isAdmin() && $pStatus !== 'cancelled'): ?>
        <a href="?page=purchases&action=edit&id=<?= (int)$purchase['id'] ?>" class="btn btn-sm btn-outline-warning pin-protect">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <?php if (Auth::can('purchases', 'delete') && $pStatus !== 'cancelled'): ?>
        <form method="POST" action="?page=purchases&action=cancel" class="d-inline" id="formCancelPurchase">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$purchase['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger pin-protect">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
        <?php if (!empty($canReverseToPo) && Auth::can('purchases', 'delete')): ?>
        <form method="POST" action="?page=purchases&action=reverseToPo" class="d-inline" id="formReverseToPo">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$purchase['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary pin-protect">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse to PO
            </button>
        </form>
        <?php endif; ?>
        <?php if (Auth::can('returns', 'add') && $pStatus !== 'cancelled'): ?>
        <a href="?page=returns&action=purchaseCreate&amp;ref_id=<?= (int)$purchase['id'] ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-arrow-return-left me-1"></i> Return
        </a>
        <?php endif; ?>
        <?php if (!empty($showImeiScan) && Auth::can('purchases', 'edit')): ?>
        <a href="?page=purchases&action=imeiScan&id=<?= (int)$purchase['id'] ?>"
           class="btn btn-sm"
           style="background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-weight:600;border:none;">
            <i class="bi bi-upc-scan me-1"></i> Scan IMEIs
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($imeiPendingLines) && $pStatus !== 'cancelled' && Auth::can('purchases', 'edit')): ?>
<div class="alert mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
     style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fde68a;border-radius:12px;color:#92400e;">
    <div style="font-size:0.88rem;font-weight:600;">
        <i class="bi bi-upc-scan me-1"></i>
        <?= (int)$imeiPendingLines ?> line(s) still need <?= (int)$imeiPendingQty ?> IMEI(s) — stock is recorded; scan when ready.
    </div>
    <a href="?page=purchases&action=imeiScan&id=<?= (int)$purchase['id'] ?>"
       class="btn btn-sm"
       style="background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-weight:700;border:none;">
        Scan now
    </a>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Main column -->
    <div class="col-lg-8">

        <!-- Supplier & meta -->
        <div class="card mb-3" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border-color);gap:12px;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);font-size:0.82rem;"><i class="bi bi-truck me-1"></i> Supplier</span>
                    <div class="text-end">
                        <span style="font-weight:700;font-size:0.92rem;"><?= htmlspecialchars($purchase['party_name']) ?></span>
                        <?php if (!empty($purchase['party_phone'])): ?>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $purchase['party_phone'])) ?>" class="d-block small text-muted text-decoration-none"><?= htmlspecialchars($purchase['party_phone']) ?></a>
                        <?php endif; ?>
                        <?php if (!empty($purchase['party_id']) && (Auth::can('suppliers', 'view') || Auth::can('customers', 'view'))): ?>
                        <a href="?page=parties&amp;action=detail&amp;id=<?= (int)$purchase['party_id'] ?>" class="btn btn-sm btn-outline-primary mt-1" style="font-size:0.72rem;border-radius:6px;">Ledger</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Date</span>
                    <span style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;">
                        <?= date('d M Y', strtotime($purchase['date'])) ?>
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Branch</span>
                    <span style="font-weight:600;font-size:0.88rem;">
                        <i class="bi bi-building me-1" style="color:var(--primary);"></i><?= htmlspecialchars($purchase['warehouse_name'] ?? '—') ?>
                    </span>
                </div>
                <?php if (!empty($purchase['supplier_invoice_no'])): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Supplier invoice #</span>
                    <span style="font-weight:700;font-family:ui-monospace,monospace;letter-spacing:0.4px;font-size:0.88rem;"><?= htmlspecialchars($purchase['supplier_invoice_no']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($linkedPo)): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border-color);">
                    <span style="color:var(--text-muted);font-size:0.82rem;">Source PO</span>
                    <a href="?page=purchaseorders&amp;action=show&amp;id=<?= (int)$linkedPo['id'] ?>"
                       style="font-weight:700;font-family:ui-monospace,monospace;font-size:0.88rem;text-decoration:none;">
                        <?= htmlspecialchars($linkedPo['po_no']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items -->
        <div class="card" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:10px 20px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <span><i class="bi bi-box-seam me-1" style="color:#b45309;"></i> Items</span>
                    <span style="display:flex;align-items:center;gap:8px;">
                        <?php if ($showForeign): ?>
                        <span style="background:<?= $foreignBadgeBg ?>;color:<?= $foreignBadgeFg ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;">
                            <?= htmlspecialchars($foreignCurrency) ?><?= $showForeignRate ? ' · Rate: ' . number_format((float)$poForeign['exchange_rate'], 4) : '' ?>
                        </span>
                        <?php endif; ?>
                        <span style="color:#b45309;font-size:0.78rem;"><?= count($purchase['items']) ?> line<?= count($purchase['items']) !== 1 ? 's' : '' ?> · <?= $totalQty ?> pcs</span>
                    </span>
                </div>
                <div class="table-responsive">
                    <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                        <thead>
                            <tr style="background:rgba(180,83,9,0.06);">
                                <th style="padding:8px 12px 8px 20px;width:36px;color:var(--text-muted);font-weight:600;font-size:0.7rem;text-transform:uppercase;">#</th>
                                <th style="padding:8px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);">Item</th>
                                <th style="padding:8px;text-align:center;width:56px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);">Qty</th>
                                <?php if ($showForeign): ?>
                                <th style="padding:8px;text-align:right;width:90px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);">Price (<?= htmlspecialchars($foreignCurrency) ?>)</th>
                                <th style="padding:8px;text-align:right;width:100px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);">Total (<?= htmlspecialchars($foreignCurrency) ?>)</th>
                                <?php endif; ?>
                                <th style="padding:8px;text-align:right;width:100px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);"><?= $showForeign ? 'Price (KWD)' : 'Cost' ?></th>
                                <th style="padding:8px 20px 8px 8px;text-align:right;width:110px;font-weight:600;font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);"><?= $showForeign ? 'Total (KWD)' : 'Total' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchase['items'] as $i => $item): ?>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:10px 12px 10px 20px;color:var(--text-muted);font-size:0.75rem;"><?= $i + 1 ?></td>
                                <td style="padding:10px 8px;font-weight:600;line-height:1.35;"><?= htmlspecialchars($item['item_name']) ?></td>
                                <td style="padding:10px 8px;text-align:center;font-weight:700;"><?= (int)$item['quantity'] ?></td>
                                <?php if ($showForeign): ?>
                                <td style="padding:10px 8px;text-align:right;color:#475569;"><?= isset($item['unit_price_foreign']) ? number_format((float)$item['unit_price_foreign'], DECIMAL_PLACES) : '—' ?></td>
                                <td style="padding:10px 8px;text-align:right;font-weight:600;color:#f59e0b;"><?= isset($item['total_foreign']) ? number_format((float)$item['total_foreign'], DECIMAL_PLACES) : '—' ?></td>
                                <?php endif; ?>
                                <td style="padding:10px 8px;text-align:right;color:var(--text-muted);"><?= number_format((float)$item['unit_price'], DECIMAL_PLACES) ?></td>
                                <td style="padding:10px 20px 10px 8px;text-align:right;font-weight:700;"><?= number_format((float)$item['total'], DECIMAL_PLACES) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($showForeign): ?>
                <div style="padding:8px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;font-size:0.82rem;">
                    <span class="text-muted">Subtotal (<?= htmlspecialchars($foreignCurrency) ?>)</span>
                    <span style="font-weight:600;color:#f59e0b;"><?= number_format((float)($poForeign['subtotal_foreign'] ?? 0), DECIMAL_PLACES) ?> <?= htmlspecialchars($foreignCurrency) ?></span>
                </div>
                <?php endif; ?>
                <div style="padding:8px 20px;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;font-size:0.82rem;">
                    <span class="text-muted"><?= $showForeign ? 'Subtotal (KWD)' : 'Subtotal' ?></span>
                    <span><?= pv_money($purchase['subtotal']) ?></span>
                </div>
                <?php if (($purchase['discount'] ?? 0) > 0): ?>
                <div style="padding:6px 20px;display:flex;justify-content:space-between;font-size:0.82rem;">
                    <span class="text-muted">Discount</span>
                    <span style="color:var(--danger);font-weight:600;">− <?= pv_money($purchase['discount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)($purchase['landed_cost'] ?? 0) > 0): ?>
                <div style="padding:6px 20px;display:flex;justify-content:space-between;font-size:0.82rem;">
                    <span class="text-muted">Logistics on invoice</span>
                    <span style="font-weight:600;"><?= pv_money($purchase['landed_cost']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;padding:12px 20px;font-weight:800;color:#b45309;font-size:0.95rem;border-top:2px solid #f59e0b;background:rgba(245,158,11,0.04);">
                    <span>Grand total (<?= $totalQty ?> pcs)</span>
                    <span><?= pv_money($purchase['grand_total']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- Amount hero -->
        <div class="card mb-3" style="border:none;background:linear-gradient(135deg,#fffbeb,#fde68a);overflow:hidden;">
            <div class="card-body text-center py-4">
                <p style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#b45309;margin-bottom:6px;">Purchase total</p>
                <p style="font-size:1.75rem;font-weight:800;color:#78350f;margin:0 0 12px;"><?= pv_money($purchase['grand_total']) ?></p>
                <div style="height:6px;background:rgba(120,53,15,0.12);border-radius:99px;overflow:hidden;margin:0 auto 8px;max-width:220px;">
                    <div style="height:100%;width:<?= $paidPct ?>%;background:linear-gradient(90deg,#059669,#10b981);border-radius:99px;transition:width 0.3s;"></div>
                </div>
                <p style="font-size:0.72rem;color:#92400e;margin:0;font-weight:600;"><?= $paidPct ?>% paid</p>
            </div>
        </div>

        <!-- Payment summary -->
        <div class="card mb-3" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:12px 18px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;">
                    <i class="bi bi-wallet2 me-1" style="color:var(--primary);"></i> Payment summary
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border-color);font-size:0.84rem;">
                    <span class="text-muted">Total</span>
                    <span class="fw-semibold"><?= pv_money($purchase['grand_total']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border-color);font-size:0.84rem;">
                    <span class="text-muted">Paid</span>
                    <span style="color:#059669;font-weight:700;"><?= pv_money($purchase['paid_amount']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px 18px;font-size:0.9rem;background:<?= $hasBalance ? 'rgba(245,158,11,0.06)' : 'rgba(16,185,129,0.06)' ?>;">
                    <span class="fw-bold">Balance due</span>
                    <span style="font-weight:800;color:<?= $hasBalance ? '#d97706' : '#059669' ?>;"><?= pv_money($purchase['balance']) ?></span>
                </div>
                <details style="padding:10px 18px 14px;font-size:0.75rem;color:var(--text-muted);">
                    <summary style="cursor:pointer;font-weight:600;color:var(--text-muted);user-select:none;">What is invoice balance?</summary>
                    <p class="mb-0 mt-2" style="line-height:1.5;">
                        Amount still due on <em>this</em> bill (grand total minus payments linked here).
                        Your overall position with the supplier is on
                        <?php if (Auth::can('suppliers', 'view') || Auth::can('customers', 'view')): ?>
                        <a href="?page=parties&amp;action=detail&amp;id=<?= (int)($purchase['party_id'] ?? 0) ?>">their party ledger</a>
                        <?php else: ?>
                        their party ledger
                        <?php endif; ?>
                        (all sales, purchases, returns, and payments).
                    </p>
                </details>
            </div>
        </div>

        <?php if (!empty($importShipment)): ?>
        <div class="card mb-3" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:12px 18px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:700;font-size:0.82rem;"><i class="bi bi-globe2 me-1" style="color:#6366f1;"></i> Import logistics</span>
                    <a href="?page=landedcost&action=view&id=<?= (int)$importShipment['id'] ?>" class="small fw-semibold text-decoration-none">View shipment</a>
                </div>
                <div style="padding:12px 18px;">
                    <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($importShipment['shipment_no']) ?></div>
                    <?php if ((float)($purchase['landed_cost'] ?? 0) > 0): ?>
                    <div class="small text-muted mt-1">On this invoice: <strong><?= pv_money($purchase['landed_cost']) ?></strong></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($importCostLines)): ?>
                <table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
                    <thead>
                        <tr style="background:rgba(99,102,241,0.05);">
                            <th style="padding:6px 18px;font-weight:600;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">Charge</th>
                            <th style="padding:6px 8px;font-weight:600;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">Where</th>
                            <th style="padding:6px 18px 6px 8px;text-align:right;font-weight:600;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">KWD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($importCostLines as $cl): ?>
                        <tr style="border-top:1px solid var(--border-color);">
                            <td style="padding:8px 18px;">
                                <?= htmlspecialchars($cl['description']) ?>
                                <span class="text-muted">(<?= ucfirst($cl['cost_category'] ?? '') ?>)</span>
                            </td>
                            <td style="padding:8px;"><?= ucfirst($cl['pay_location'] ?? '') ?></td>
                            <td style="padding:8px 18px 8px 8px;text-align:right;font-weight:600;"><?= number_format((float)$cl['amount'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Linked payments -->
        <div class="card" style="border:none;">
            <div class="card-body px-0 py-0">
                <div style="padding:12px 18px;border-bottom:1px solid var(--border-color);font-weight:700;font-size:0.82rem;">
                    <i class="bi bi-cash-stack me-1" style="color:#059669;"></i> Payments
                </div>
                <?php if (empty($purchase['payments'])): ?>
                <p class="text-muted text-center py-4 mb-0 small">No payments linked to this purchase</p>
                <?php else: ?>
                <?php foreach ($purchase['payments'] as $pay): ?>
                <a href="?page=payments&amp;action=detail&amp;id=<?= (int)($pay['id'] ?? 0) ?>"
                   class="pv-pay-row d-flex justify-content-between align-items-center text-decoration-none">
                    <div>
                        <div style="font-weight:700;font-size:0.84rem;color:var(--primary);"><?= htmlspecialchars($pay['payment_no'] ?? '') ?></div>
                        <small class="text-muted"><?= date('d M Y', strtotime($pay['date'])) ?></small>
                    </div>
                    <span style="color:#059669;font-weight:700;font-size:0.88rem;"><?= pv_money($pay['amount']) ?></span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.pv-pay-row {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border-color);
    color: inherit;
    transition: background 0.15s;
}
.pv-pay-row:hover {
    background: rgba(99, 102, 241, 0.04);
}
</style>

<?php if (Auth::can('purchases', 'delete') && $pStatus !== 'cancelled'): ?>
<script>
(function () {
    var cancelForm = document.getElementById('formCancelPurchase');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function (e) {
            if (!confirm('Cancel this purchase? Stock and linked payments will be reversed.')) {
                e.preventDefault();
            }
        });
    }
    var reverseForm = document.getElementById('formReverseToPo');
    if (reverseForm) {
        reverseForm.addEventListener('submit', function (e) {
            if (!confirm('Reverse this purchase back to its PO?\n\nThis will:\n• Cancel the purchase invoice\n• Remove stock added by this invoice\n• Reopen the PO for editing\n\nProceed?')) {
                e.preventDefault();
            }
        });
    }
})();
</script>
<?php endif; ?>
