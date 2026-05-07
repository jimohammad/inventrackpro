<!-- PO Show Page -->
<?php
$statusConfig = match($po['status']) {
    'draft'     => ['label'=>'Draft',             'bg'=>'#e0e7ff','color'=>'#3730a3'],
    'paid'      => ['label'=>'Paid — Awaiting Goods','bg'=>'#fef3c7','color'=>'#92400e'],
    'converted' => ['label'=>'Converted to Invoice','bg'=>'#d1fae5','color'=>'#065f46'],
    'cancelled' => ['label'=>'Cancelled',          'bg'=>'#f1f5f9','color'=>'#94a3b8'],
    default     => ['label'=>ucfirst($po['status']),'bg'=>'#f1f5f9','color'=>'#64748b'],
};

$paidAccountName = '—';
if (!empty($po['account_id'])) {
    foreach (($accounts ?? []) as $acc) {
        if ((int)$acc['id'] === (int)$po['account_id']) { $paidAccountName = $acc['name']; break; }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title" style="font-family:'JetBrains Mono',monospace;"><?= $po['po_no'] ?></h1>
        <p class="page-subtitle">
            <?= htmlspecialchars($po['supplier_name']) ?> ·
            <?= date('d M Y', strtotime($po['date'])) ?>
            <span style="background:<?= $statusConfig['bg'] ?>;color:<?= $statusConfig['color'] ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;margin-left:8px;">
                <?= $statusConfig['label'] ?>
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="?page=purchaseorders" style="padding:8px 18px;border-radius:8px;border:1.5px solid #e5e7eb;color:#64748b;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <?php if ($po['status'] === 'draft'): ?>
        <button type="button" onclick="document.getElementById('markPaidModal').style.display='flex'"
           style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-cash-coin"></i> Mark as Paid
        </button>
        <!-- Mark as Paid Modal -->
        <div id="markPaidModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px;"><i class="bi bi-cash-coin" style="color:#f59e0b;"></i> Mark as Paid</h5>
                <p style="color:#64748b;font-size:0.85rem;margin-bottom:18px;">This will deduct <strong><?= number_format($po['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</strong> from the selected account and mark the PO as paid.</p>
                <form method="POST" action="?page=purchaseorders&action=markPaid">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="po_markpaid_nonce" value="<?= htmlspecialchars($poMarkPaidNonce ?? '') ?>">
                    <input type="hidden" name="id" value="<?= $po['id'] ?>">
                    <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:6px;">Pay From Account <span style="color:#dc2626;">*</span></label>
                    <select name="account_id" required
                        style="width:100%;padding:9px 12px;border:2px solid #e0e7ff;border-radius:10px;font-size:0.875rem;color:#1e293b;margin-bottom:16px;outline:none;">
                        <option value="">— Select Account —</option>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= number_format($acc['current_balance'], DECIMAL_PLACES) ?> KWD)</option>
                        <?php endforeach; ?>
                    </select>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" onclick="document.getElementById('markPaidModal').style.display='none'"
                            style="padding:8px 18px;border-radius:8px;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;cursor:pointer;font-size:0.85rem;">
                            Cancel
                        </button>
                        <button type="submit"
                            style="padding:8px 22px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;">
                            <i class="bi bi-check-lg"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?php if (in_array($po['status'], ['draft','paid'])): ?>
        <a href="?page=purchaseorders&action=convert&id=<?= $po['id'] ?>"
           onclick="return confirm('Convert this PO to a Purchase Invoice?\n\nThis will:\n✓ Create a Purchase Invoice in KWD\n✓ Add stock to your warehouse\n✓ Update item purchase prices\n\nProceed?')"
           style="padding:8px 20px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);border:none;color:#fff;font-size:0.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(16,185,129,0.4);">
            <i class="bi bi-arrow-repeat"></i> Convert to Purchase Invoice
        </a>
        <?php endif; ?>
        <?php if ($po['status'] === 'converted' && $po['purchase_invoice_no']): ?>
        <a href="?page=purchases&action=detail&id=<?= $po['converted_to'] ?>"
           style="padding:8px 20px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;font-size:0.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-receipt"></i> View Invoice <?= $po['purchase_invoice_no'] ?>
        </a>
        <?php endif; ?>
        <?php if (!in_array($po['status'], ['converted','cancelled'])): ?>
        <form method="POST" action="?page=purchaseorders&action=cancel" style="display:inline;"
              onsubmit="return confirm('Cancel this Purchase Order?')">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $po['id'] ?>">
            <button type="submit"
               style="padding:8px 14px;border-radius:8px;border:1.5px solid #fca5a5;color:#dc2626;font-size:0.85rem;background:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-x-lg"></i> Cancel
            </button>
        </form>
        <?php endif; ?>
        <?php if ($po['status'] === 'cancelled' && Auth::isAdmin()): ?>
        <form method="POST" action="?page=purchaseorders&action=reactivate" style="display:inline;"
              onsubmit="return confirm('Reactivate this cancelled PO back to Draft?')">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $po['id'] ?>">
            <button type="submit"
               style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-arrow-counterclockwise"></i> Reactivate
            </button>
        </form>
        <?php endif; ?>
        <?php if (Auth::isAdmin() && $po['status'] !== 'converted'): ?>
        <a href="?page=purchaseorders&action=edit&id=<?= $po['id'] ?>"
           style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#fff;font-size:0.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">

    <!-- Left: Item details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <div style="padding:12px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:0.78rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;">
                        <i class="bi bi-list-ul me-1"></i> Ordered Items
                    </span>
                    <span style="background:<?= $po['currency']==='AED'?'#dbeafe':'#fef9c3' ?>;color:<?= $po['currency']==='AED'?'#1d4ed8':'#854d0e' ?>;padding:2px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;">
                        <?= $po['currency'] ?> · Rate: <?= number_format($po['exchange_rate'], 4) ?>
                    </span>
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">#</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Item</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:center;">Qty</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Price (<?= $po['currency'] ?>)</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Total (<?= $po['currency'] ?>)</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Total (KWD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $n => $item): ?>
                        <tr style="background:#fff;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background='#fff'">
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;"><?= $n+1 ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;">
                                <?= htmlspecialchars($item['item_name']) ?>
                                <div style="font-size:0.72rem;color:#94a3b8;font-family:'JetBrains Mono',monospace;"><?= htmlspecialchars((string) ($item['sku'] ?? '')) ?></div>
                            </td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;font-weight:700;color:#4338ca;"><?= $item['quantity'] ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;color:#475569;"><?= number_format($item['unit_price_foreign'], DECIMAL_PLACES) ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:#f59e0b;"><?= number_format($item['total_foreign'], DECIMAL_PLACES) ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#6366f1;"><?= number_format($item['total_kwd'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                            <td colspan="4" style="padding:11px 14px;font-weight:700;color:#4338ca;">Total</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:800;color:#f59e0b;"><?= number_format($po['subtotal_foreign'], DECIMAL_PLACES) ?> <?= $po['currency'] ?></td>
                            <td style="padding:11px 14px;text-align:right;font-weight:800;color:#6366f1;"><?= number_format($po['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if ($po['notes']): ?>
        <div class="card mt-3">
            <div class="card-body" style="font-size:0.83rem;color:#475569;">
                <strong style="color:#64748b;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Notes</strong>
                <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($po['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Summary card -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body" style="font-size:0.83rem;">
                <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:14px;">Order Summary</p>

                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Supplier</span>
                    <span style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($po['supplier_name']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Supplier Ref</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#6366f1;"><?= htmlspecialchars($po['supplier_ref'] ?? '—') ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Warehouse</span>
                    <span style="font-weight:600;"><?= htmlspecialchars($po['warehouse_name']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Currency</span>
                    <span style="font-weight:700;color:<?= $po['currency']==='AED'?'#1d4ed8':'#854d0e' ?>;"><?= $po['currency'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Exchange Rate</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">1 <?= $po['currency'] ?> = <?= $po['exchange_rate'] ?> KWD</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Order Total</span>
                    <span style="font-weight:700;color:#f59e0b;"><?= number_format($po['subtotal_foreign'], DECIMAL_PLACES) ?> <?= $po['currency'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Paid</span>
                    <span style="font-weight:600;color:#10b981;"><?= number_format($po['paid_foreign'], DECIMAL_PLACES) ?> <?= $po['currency'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:2px solid #e0e7ff;margin-top:6px;">
                    <span style="font-weight:700;color:#1e293b;">Total in KWD</span>
                    <span style="font-size:1.1rem;font-weight:800;color:#6366f1;"><?= number_format($po['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;">
                    <span style="color:#94a3b8;font-size:0.78rem;">Paid in KWD</span>
                    <span style="color:#10b981;font-weight:600;font-size:0.82rem;"><?= number_format($po['paid_kwd'], DECIMAL_PLACES) ?> KWD</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;">
                    <span style="color:#94a3b8;font-size:0.78rem;">Paid From</span>
                    <span style="font-weight:700;font-size:0.82rem;color:#1e293b;">
                        <?= ($po['paid_kwd'] ?? 0) > 0 ? htmlspecialchars($paidAccountName) : '—' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-body" style="font-size:0.82rem;">
                <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:14px;">Progress</p>
                <?php
                $steps = [
                    ['label'=>'PO Created',          'done'=>true,                                               'icon'=>'bi-file-earmark-plus'],
                    ['label'=>'Payment Sent',         'done'=>in_array($po['status'],['paid','converted']),      'icon'=>'bi-cash-coin'],
                    ['label'=>'Goods Received',       'done'=>$po['status']==='converted',                      'icon'=>'bi-box-seam'],
                    ['label'=>'Converted to Invoice', 'done'=>$po['status']==='converted',                      'icon'=>'bi-receipt-cutoff'],
                ];
                foreach ($steps as $step): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f8fafc;">
                    <div style="width:28px;height:28px;border-radius:50%;background:<?= $step['done']?'#d1fae5':'#f1f5f9' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="<?= $step['icon'] ?>" style="color:<?= $step['done']?'#059669':'#cbd5e1' ?>;font-size:0.85rem;"></i>
                    </div>
                    <span style="color:<?= $step['done']?'#1e293b':'#94a3b8' ?>;font-weight:<?= $step['done']?'600':'400' ?>;">
                        <?= $step['label'] ?>
                    </span>
                    <?php if ($step['done']): ?>
                    <i class="bi bi-check-lg" style="color:#10b981;margin-left:auto;"></i>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="font-size:0.72rem;color:#94a3b8;margin-top:10px;text-align:right;">
            Created by <?= htmlspecialchars($po['created_by_name'] ?? '—') ?> ·
            <?= date('d M Y H:i', strtotime($po['created_at'])) ?>
        </div>
    </div>
</div>
