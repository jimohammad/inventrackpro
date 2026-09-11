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
// Foreign price is captured as a reference/record only (no KWD conversion), so the
// exchange rate is 1. Only surface rate/foreign-paid lines if a real rate was used.
$showRate     = abs((float)($po['exchange_rate'] ?? 1) - 1.0) > 0.0000001;
$hasForeign   = (float)($po['subtotal_foreign'] ?? 0) > 0 && ($po['currency'] ?? 'KWD') !== 'KWD';
$otherChargesKwd = (float)($po['other_charges_kwd'] ?? 0);
$adjustmentKwd   = (float)($po['adjustment_kwd'] ?? 0);
$poTotalKwd      = (float)$po['subtotal_kwd'] + $otherChargesKwd + $adjustmentKwd;
$kwdUnknown      = $hasForeign && $poTotalKwd <= 0.001 && (float)($po['paid_kwd'] ?? 0) <= 0.001;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title" style=""><?= $po['po_no'] ?></h1>
        <p class="page-subtitle">
            <?= htmlspecialchars($po['supplier_name']) ?> ·
            <?= date('d M Y', strtotime($po['date'])) ?>
            <span style="background:<?= $statusConfig['bg'] ?>;color:<?= $statusConfig['color'] ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;margin-left:8px;">
                <?= $statusConfig['label'] ?>
            </span>
            <?php if (!empty($openShipment)): ?>
            <a href="?page=landedcost&action=view&id=<?= (int) $openShipment['id'] ?>"
               style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;margin-left:8px;text-decoration:none;">
                On shipment <?= htmlspecialchars($openShipment['shipment_no']) ?>
            </a>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="?page=purchaseorders" style="padding:8px 18px;border-radius:8px;border:1.5px solid #e5e7eb;color:#64748b;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <?php
        $poUnpaidShow = max(0, round($poTotalKwd - (float)($po['paid_kwd'] ?? 0), 3));
        $hasSupplierCredits = !empty($supplierCredits);
        ?>
        <?php if ($hasSupplierCredits && $poUnpaidShow > 0.001 && Auth::can('purchases', 'edit')): ?>
        <button type="button" onclick="document.getElementById('applyCreditModal').style.display='flex'"
           style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-wallet2"></i> Apply Supplier Credit
        </button>
        <div id="applyCreditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:520px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px;">
                    <i class="bi bi-wallet2" style="color:#0284c7;"></i> Apply Supplier Credit
                </h5>
                <p style="color:#64748b;font-size:0.85rem;margin-bottom:14px;">
                    Use existing Payment Out rows (already deducted from bank) against this PO.
                    Tick one or more. Oldest is applied first. <strong>Bank will not be charged again.</strong>
                    PO unpaid: <strong><?= number_format($poUnpaidShow, DECIMAL_PLACES) ?> KWD</strong>.
                </p>
                <form method="POST" action="?page=purchaseorders&action=applyCredit" id="applyCreditForm">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="po_applycredit_nonce" value="<?= htmlspecialchars($poApplyCreditNonce ?? '') ?>">
                    <input type="hidden" name="id" value="<?= (int)$po['id'] ?>">
                    <?php
                    $creditNeedLeft = $poUnpaidShow;
                    $creditAvailSum = 0.0;
                    foreach ($supplierCredits as $credSum) {
                        $creditAvailSum = round($creditAvailSum + (float)($credSum['amount'] ?? 0), 3);
                    }
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;">
                        <label style="font-size:0.78rem;font-weight:700;color:#64748b;margin:0;">Supplier payments <span style="color:#dc2626;">*</span></label>
                        <label style="font-size:0.75rem;color:#0284c7;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" id="applyCreditSelectAll" style="accent-color:#0284c7;"> Select all
                        </label>
                    </div>
                    <div id="applyCreditList" style="max-height:220px;overflow:auto;border:2px solid #e0e7ff;border-radius:10px;margin-bottom:14px;padding:4px 0;">
                        <?php foreach ($supplierCredits as $cred):
                            $credAmt = round((float)($cred['amount'] ?? 0), 3);
                            $preCheck = $creditNeedLeft > 0.001;
                            if ($preCheck) {
                                $creditNeedLeft = round(max(0, $creditNeedLeft - $credAmt), 3);
                            }
                        ?>
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:9px 12px;cursor:pointer;font-size:0.875rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">
                            <input type="checkbox" name="payment_ids[]" class="apply-credit-pay"
                                   value="<?= (int)$cred['id'] ?>"
                                   data-amount="<?= htmlspecialchars(number_format($credAmt, 3, '.', '')) ?>"
                                   <?= $preCheck ? 'checked' : '' ?>
                                   style="margin-top:3px;accent-color:#0284c7;">
                            <span>
                                <strong><?= htmlspecialchars($cred['payment_no']) ?></strong>
                                · <?= date('d M Y', strtotime((string)$cred['date'])) ?>
                                · <?= number_format($credAmt, DECIMAL_PLACES) ?> KWD
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p id="applyCreditSelectedSum" style="color:#0369a1;font-size:0.78rem;font-weight:600;margin:-8px 0 12px;"></p>
                    <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:6px;">Amount to apply (KWD)</label>
                    <input type="number" name="amount" id="applyCreditAmount" step="0.001" min="0.001"
                           value="<?= htmlspecialchars(number_format(min($poUnpaidShow, $creditAvailSum), 3, '.', '')) ?>"
                           style="width:100%;padding:9px 12px;border:2px solid #e0e7ff;border-radius:10px;font-size:0.875rem;color:#1e293b;margin-bottom:16px;outline:none;">
                    <p style="color:#94a3b8;font-size:0.75rem;margin:-8px 0 16px;">Leave as unpaid total to cover this PO only; leftover stays as supplier credit for other items.</p>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" onclick="document.getElementById('applyCreditModal').style.display='none'"
                            style="padding:8px 18px;border-radius:8px;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;cursor:pointer;font-size:0.85rem;">
                            Cancel
                        </button>
                        <button type="submit"
                            style="padding:8px 22px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;">
                            <i class="bi bi-check-lg"></i> Apply Credit
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        (function () {
            var form = document.getElementById('applyCreditForm');
            var amt = document.getElementById('applyCreditAmount');
            var boxes = document.querySelectorAll('.apply-credit-pay');
            var selectAll = document.getElementById('applyCreditSelectAll');
            var sumEl = document.getElementById('applyCreditSelectedSum');
            var unpaid = <?= json_encode(round($poUnpaidShow, 3)) ?>;
            if (!form || !amt || !boxes.length) return;

            function selectedSum() {
                var total = 0;
                boxes.forEach(function (box) {
                    if (box.checked) total += parseFloat(box.getAttribute('data-amount') || '0') || 0;
                });
                return Math.round(total * 1000) / 1000;
            }
            function syncAmount() {
                var avail = selectedSum();
                var next = Math.min(avail, unpaid);
                if (next > 0) amt.value = next.toFixed(3);
                if (selectAll) {
                    var n = 0;
                    boxes.forEach(function (box) { if (box.checked) n++; });
                    selectAll.checked = n === boxes.length;
                    selectAll.indeterminate = n > 0 && n < boxes.length;
                }
                if (sumEl) {
                    sumEl.textContent = 'Selected credit: ' + avail.toFixed(3) + ' KWD'
                        + (avail + 0.0005 >= unpaid ? ' — covers this PO in full' : '');
                }
            }
            boxes.forEach(function (box) {
                box.addEventListener('change', syncAmount);
            });
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    boxes.forEach(function (box) { box.checked = selectAll.checked; });
                    syncAmount();
                });
            }
            form.addEventListener('submit', function (e) {
                var n = 0;
                boxes.forEach(function (box) { if (box.checked) n++; });
                if (n === 0) {
                    e.preventDefault();
                    alert('Select at least one supplier payment.');
                }
            });
            syncAmount();
        })();
        </script>
        <?php endif; ?>
        <?php if ($po['status'] === 'draft' && empty($kwdUnknown)): ?>
        <button type="button" onclick="document.getElementById('markPaidModal').style.display='flex'"
           style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-cash-coin"></i> Mark as Paid
        </button>
        <!-- Mark as Paid Modal -->
        <div id="markPaidModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px;"><i class="bi bi-cash-coin" style="color:#f59e0b;"></i> Mark as Paid</h5>
                <p style="color:#64748b;font-size:0.85rem;margin-bottom:18px;">This will deduct <strong><?= number_format($poUnpaidShow > 0.001 ? $poUnpaidShow : $poTotalKwd, DECIMAL_PLACES) ?> KWD</strong> from the selected account and mark the PO as paid.
                <?php if ($hasSupplierCredits): ?>
                <br><span style="color:#b45309;">If bank was already paid via Payment Out, use <strong>Apply Supplier Credit</strong> instead — do not Mark as Paid.</span>
                <?php endif; ?>
                </p>
                <form method="POST" action="?page=purchaseorders&action=markPaid">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="po_markpaid_nonce" value="<?= htmlspecialchars($poMarkPaidNonce ?? '') ?>">
                    <input type="hidden" name="id" value="<?= $po['id'] ?>">
                    <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:6px;">Pay From Account <span style="color:#dc2626;">*</span></label>
                    <select name="account_id" required
                        style="width:100%;padding:9px 12px;border:2px solid #e0e7ff;border-radius:10px;font-size:0.875rem;color:#1e293b;margin-bottom:16px;outline:none;">
                        <option value="">— Select Account —</option>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars(BaseController::formatAccountLabel($acc, true)) ?></option>
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
        <?php if (in_array($po['status'], ['draft','paid']) && !empty($openShipment)): ?>
        <span class="small text-muted align-self-center" style="max-width:280px;">Receive via <a href="?page=landedcost&action=view&id=<?= (int) $openShipment['id'] ?>">Import Logistics shipment</a> to convert and apply logistics.</span>
        <?php endif; ?>
        <?php if ($po['status'] === 'converted' && $po['purchase_invoice_no']): ?>
        <a href="?page=purchases&action=detail&id=<?= $po['converted_to'] ?>"
           style="padding:8px 20px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;font-size:0.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-receipt"></i> View Invoice <?= $po['purchase_invoice_no'] ?>
        </a>
        <?php if (!empty($canReverseToPo) && Auth::can('purchases', 'delete')): ?>
        <form method="POST" action="?page=purchaseorders&action=reverseToPo" class="d-inline" id="formPoReverseToPo">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$po['id'] ?>">
            <button type="submit"
               style="padding:8px 20px;border-radius:8px;background:linear-gradient(135deg,#64748b,#475569);border:none;color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-arrow-counterclockwise"></i> Reverse to PO
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (!in_array($po['status'], ['converted','cancelled'])): ?>
        <form method="POST" action="?page=purchaseorders&action=cancel" style="display:inline;"
              onsubmit="return confirm('Cancel this Purchase Order?\n\nPosted bank payments will NOT be reversed. The amount stays as supplier credit on the ledger.')">
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
                        <?= htmlspecialchars($po['currency']) ?><?= $showRate ? ' · Rate: ' . number_format($po['exchange_rate'], 4) : '' ?>
                    </span>
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">#</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Item</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:center;">Qty</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Price (<?= htmlspecialchars($po['currency']) ?>)</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Total (<?= htmlspecialchars($po['currency']) ?>)</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Price (KWD)</th>
                            <th style="padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Total (KWD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $n => $item):
                            $qty = (int)($item['quantity'] ?? 0);
                            $unitKwd = (float)($item['unit_price_kwd'] ?? 0);
                            if ($unitKwd <= 0 && (float)($item['total_kwd'] ?? 0) > 0 && $qty > 0) {
                                $unitKwd = round((float)$item['total_kwd'] / $qty, 3);
                            }
                        ?>
                        <tr style="background:#fff;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background='#fff'">
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;"><?= $n+1 ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;">
                                <?= htmlspecialchars($item['item_name']) ?>
                                <div style="font-size:0.72rem;color:#94a3b8;"><?= htmlspecialchars((string) ($item['sku'] ?? '')) ?></div>
                            </td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;font-weight:700;color:#4338ca;"><?= $qty ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;color:#475569;"><?= number_format((float)$item['unit_price_foreign'], DECIMAL_PLACES) ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:#f59e0b;"><?= number_format((float)$item['total_foreign'], DECIMAL_PLACES) ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:#1e3a5f;"><?= $unitKwd > 0 ? number_format($unitKwd, DECIMAL_PLACES) : '—' ?></td>
                            <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#6366f1;"><?= (float)$item['total_kwd'] > 0.0005 ? number_format((float)$item['total_kwd'], DECIMAL_PLACES) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8f9ff;">
                            <td colspan="4" style="padding:9px 14px;font-weight:600;color:#64748b;">Subtotal</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;color:#f59e0b;"><?= number_format((float)$po['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($po['currency']) ?></td>
                            <td style="padding:9px 14px;text-align:right;color:#94a3b8;">—</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;color:#6366f1;"><?= $kwdUnknown ? '—' : number_format((float)$po['subtotal_kwd'], DECIMAL_PLACES) . ' KWD' ?></td>
                        </tr>
                        <?php if ($otherChargesKwd > 0.001): ?>
                        <tr style="background:#f8f9ff;">
                            <td colspan="6" style="padding:9px 14px;font-weight:600;color:#64748b;">Other Charges (delivery, etc.)</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;color:#b45309;"><?= number_format($otherChargesKwd, DECIMAL_PLACES) ?> KWD</td>
                        </tr>
                        <?php endif; ?>
                        <?php if (abs($adjustmentKwd) > 0.0005): ?>
                        <tr style="background:#f8f9ff;">
                            <td colspan="6" style="padding:9px 14px;font-weight:600;color:#64748b;">Bank Adjustment</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;color:<?= $adjustmentKwd < 0 ? '#b91c1c' : '#047857' ?>;"><?= ($adjustmentKwd > 0 ? '+' : '') . number_format($adjustmentKwd, DECIMAL_PLACES) ?> KWD</td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                            <td colspan="4" style="padding:11px 14px;font-weight:700;color:#4338ca;">Total</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:800;color:#f59e0b;"><?= number_format((float)$po['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($po['currency']) ?></td>
                            <td style="padding:11px 14px;text-align:right;color:#94a3b8;">—</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:800;color:#6366f1;"><?= $kwdUnknown ? '—' : number_format($poTotalKwd, DECIMAL_PLACES) . ' KWD' ?></td>
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

        <?php
        $poDocuments = $poDocuments ?? [];
        $poDocTypes  = $poDocTypes ?? [
            'supplier_invoice' => 'Supplier Invoice',
            'money_transfer'   => 'TT Copy',
        ];
        $docsByType = [
            'supplier_invoice' => [],
            'money_transfer'   => [],
        ];
        foreach ($poDocuments as $doc) {
            $dtype = (string) ($doc['doc_type'] ?? '');
            if (!isset($docsByType[$dtype])) {
                continue;
            }
            $docsByType[$dtype][] = $doc;
        }
        $docSlots = [
            'supplier_invoice' => [
                'label' => 'Supplier Invoice',
                'hint'  => 'PDF or image of supplier invoice',
                'icon'  => 'bi-receipt',
                'color' => '#1d4ed8',
                'bg'    => '#eff6ff',
                'border'=> '#bfdbfe',
            ],
            'money_transfer' => [
                'label' => 'TT Copy',
                'hint'  => 'Bank / telegraphic transfer proof',
                'icon'  => 'bi-bank',
                'color' => '#047857',
                'bg'    => '#ecfdf5',
                'border'=> '#a7f3d0',
            ],
        ];
        $totalDocs = count($docsByType['supplier_invoice']) + count($docsByType['money_transfer']);
        $canUploadPoDocs = ($po['status'] ?? '') !== 'cancelled'
            && (Auth::can('purchases', 'edit') || Auth::can('purchases', 'add'));
        ?>
        <div class="card mt-3" id="poDocumentsCard">
            <div class="card-body p-0">
                <div style="padding:12px 20px;background:linear-gradient(135deg,#f8faff,#f0f4ff);border-bottom:1px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:0.78rem;font-weight:700;color:#4338ca;text-transform:uppercase;letter-spacing:0.5px;">
                        <i class="bi bi-paperclip me-1"></i> Documents
                        <span style="background:#e0e7ff;color:#4338ca;padding:2px 8px;border-radius:999px;font-size:0.7rem;margin-left:6px;"><?= $totalDocs ?></span>
                    </span>
                    <span style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span style="font-size:0.72rem;color:#94a3b8;">
                            <?php if (($po['status'] ?? '') === 'cancelled'): ?>
                            Uploads locked (PO cancelled)
                            <?php else: ?>
                            Attach anytime (before or after convert) · PDF / JPG / PNG / WEBP · max 10 MB
                            <?php endif; ?>
                        </span>
                        <?php if ($totalDocs > 0): ?>
                        <a href="?page=purchaseorders&action=printDocs&id=<?= (int) $po['id'] ?>"
                           target="_blank" rel="noopener"
                           style="padding:4px 10px;border-radius:6px;border:1.5px solid #bfdbfe;background:#fff;color:#1d4ed8;font-size:0.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                            <i class="bi bi-file-earmark-pdf"></i> One PDF for bank
                        </a>
                        <?php else: ?>
                        <span style="font-size:0.72rem;color:#94a3b8;">Upload files first to make one PDF for the bank</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div style="padding:16px 20px;">
                    <div class="row g-3">
                        <?php foreach ($docSlots as $typeKey => $slot):
                            $files = $docsByType[$typeKey];
                        ?>
                        <div class="col-md-6">
                            <div style="height:100%;border:1.5px solid <?= $slot['border'] ?>;background:<?= $slot['bg'] ?>;border-radius:12px;padding:14px 16px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:<?= empty($files) && !$canUploadPoDocs ? '0' : '10px' ?>;flex-wrap:wrap;">
                                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                        <i class="bi <?= $slot['icon'] ?>" style="color:<?= $slot['color'] ?>;font-size:1.1rem;"></i>
                                        <div>
                                            <div style="font-weight:700;color:#1e293b;font-size:0.9rem;"><?= htmlspecialchars($slot['label']) ?></div>
                                            <div style="font-size:0.72rem;color:#64748b;"><?= htmlspecialchars($slot['hint']) ?></div>
                                        </div>
                                    </div>
                                    <?php if ($canUploadPoDocs): ?>
                                    <form method="POST" action="?page=purchaseorders&action=uploadDoc" enctype="multipart/form-data"
                                          class="po-doc-upload-form js-unsaved-ignore" data-unsaved-ignore
                                          style="flex-shrink:0;margin:0;">
                                        <?= Auth::csrfField() ?>
                                        <input type="hidden" name="po_id" value="<?= (int) $po['id'] ?>">
                                        <input type="hidden" name="doc_type" value="<?= htmlspecialchars($typeKey) ?>">
                                        <input type="file" name="doc_file" id="poDocFile_<?= htmlspecialchars($typeKey) ?>" required
                                               class="po-doc-file-input"
                                               style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;clip:rect(0,0,0,0);"
                                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                               tabindex="-1" aria-hidden="true"
                                               aria-label="Upload <?= htmlspecialchars($slot['label']) ?>">
                                        <button type="button" class="po-doc-browse-btn"
                                                data-target="poDocFile_<?= htmlspecialchars($typeKey) ?>"
                                                style="padding:0.3rem 0.75rem;background:#fff;border:1px solid <?= $slot['border'] ?>;border-radius:var(--bs-border-radius-sm, 0.25rem);color:<?= $slot['color'] ?>;font-size:0.8rem;font-weight:600;white-space:nowrap;cursor:pointer;line-height:1.5;box-sizing:border-box;display:inline-flex;align-items:center;gap:5px;">
                                            <i class="bi bi-upload"></i> Upload
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($files)): ?>
                                <div style="font-size:0.8rem;color:#94a3b8;padding:10px 12px;background:#fff;border-radius:8px;border:1px dashed <?= $slot['border'] ?>;">
                                    No file uploaded yet.
                                </div>
                                <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <?php foreach ($files as $doc):
                                        $sizeKb = max(1, (int) round(((int) ($doc['file_size'] ?? 0)) / 1024));
                                    ?>
                                    <div style="background:#fff;border-radius:8px;border:1px solid <?= $slot['border'] ?>;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                        <div style="min-width:0;flex:1;">
                                            <div style="font-weight:600;color:#1e293b;font-size:0.82rem;word-break:break-all;">
                                                <?= htmlspecialchars((string) $doc['original_name']) ?>
                                            </div>
                                            <div style="font-size:0.7rem;color:#94a3b8;">
                                                <?= $sizeKb ?> KB
                                                <?php if (!empty($doc['created_at'])): ?>
                                                · <?= date('d M Y H:i', strtotime($doc['created_at'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="display:flex;gap:6px;flex-shrink:0;">
                                            <a href="?page=purchaseorders&action=downloadDoc&id=<?= (int) $doc['id'] ?>"
                                               target="_blank" rel="noopener"
                                               style="padding:4px 10px;border-radius:6px;border:1.5px solid #c7d2fe;color:#4338ca;font-size:0.75rem;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                                <i class="bi bi-eye"></i> Open
                                            </a>
                                            <?php if (Auth::isAdmin()): ?>
                                            <form method="POST" action="?page=purchaseorders&action=deleteDoc" class="po-doc-delete-form">
                                                <?= Auth::csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                                                <button type="submit"
                                                        style="padding:4px 10px;border-radius:6px;border:1.5px solid #fecaca;color:#dc2626;background:#fff;font-size:0.75rem;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"
                                                        title="Admin only">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
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
                    <span style="font-size:0.78rem;color:#6366f1;"><?= htmlspecialchars($po['supplier_ref'] ?? '—') ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Warehouse</span>
                    <span style="font-weight:600;"><?= htmlspecialchars($po['warehouse_name']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Currency</span>
                    <span style="font-weight:700;color:<?= $po['currency']==='AED'?'#1d4ed8':'#854d0e' ?>;"><?= $po['currency'] ?></span>
                </div>
                <?php if ($showRate): ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Exchange Rate</span>
                    <span style="font-size:0.82rem;">1 <?= htmlspecialchars($po['currency']) ?> = <?= $po['exchange_rate'] ?> KWD</span>
                </div>
                <?php endif; ?>
                <?php if ($hasForeign): ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Order Total (<?= htmlspecialchars($po['currency']) ?>)</span>
                    <span style="font-weight:700;color:#f59e0b;"><?= number_format($po['subtotal_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($po['currency']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($showRate): ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Paid</span>
                    <span style="font-weight:600;color:#10b981;"><?= number_format($po['paid_foreign'], DECIMAL_PLACES) ?> <?= htmlspecialchars($po['currency']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($otherChargesKwd > 0.001): ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Other Charges</span>
                    <span style="font-weight:600;color:#b45309;"><?= number_format($otherChargesKwd, DECIMAL_PLACES) ?> KWD</span>
                </div>
                <?php endif; ?>
                <?php if (abs($adjustmentKwd) > 0.0005): ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Bank Adjustment</span>
                    <span style="font-weight:600;color:<?= $adjustmentKwd < 0 ? '#b91c1c' : '#047857' ?>;"><?= ($adjustmentKwd > 0 ? '+' : '') . number_format($adjustmentKwd, DECIMAL_PLACES) ?> KWD</span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:2px solid #e0e7ff;margin-top:6px;">
                    <span style="font-weight:700;color:#1e293b;">Total in KWD</span>
                    <span style="font-size:1.1rem;font-weight:800;color:#6366f1;"><?= $kwdUnknown ? '—' : number_format($poTotalKwd, DECIMAL_PLACES) . ' KWD' ?></span>
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
                $steps = $poProgress ?? [
                    ['label'=>'PO Created',          'done'=>true,                                               'icon'=>'bi-file-earmark-plus', 'at'=>$po['created_at'] ?? null],
                    ['label'=>'Payment Sent',         'done'=>in_array($po['status'],['paid','converted']),      'icon'=>'bi-cash-coin', 'at'=>null],
                    ['label'=>'Goods Received',       'done'=>$po['status']==='converted',                      'icon'=>'bi-box-seam', 'at'=>null],
                    ['label'=>'Converted to Invoice', 'done'=>$po['status']==='converted',                      'icon'=>'bi-receipt-cutoff', 'at'=>null],
                ];
                foreach ($steps as $step):
                    $stepAt = !empty($step['at']) ? strtotime((string) $step['at']) : false;
                    $stepAtLabel = ($stepAt !== false)
                        ? date('d M Y H:i', $stepAt)
                        : null;
                ?>
                <div style="display:flex;align-items:flex-start;gap:12px;padding:8px 0;border-bottom:1px solid #f8fafc;">
                    <div style="width:28px;height:28px;border-radius:50%;background:<?= $step['done']?'#d1fae5':'#f1f5f9' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <i class="<?= $step['icon'] ?>" style="color:<?= $step['done']?'#059669':'#cbd5e1' ?>;font-size:0.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="color:<?= $step['done']?'#1e293b':'#94a3b8' ?>;font-weight:<?= $step['done']?'600':'400' ?>;">
                                <?= htmlspecialchars($step['label']) ?>
                            </span>
                            <?php if ($step['done']): ?>
                            <i class="bi bi-check-lg" style="color:#10b981;margin-left:auto;"></i>
                            <?php endif; ?>
                        </div>
                        <?php if ($step['done'] && $stepAtLabel): ?>
                        <div style="color:#94a3b8;font-size:0.72rem;margin-top:2px;font-weight:500;">
                            <i class="bi bi-clock" style="font-size:0.68rem;"></i>
                            <?= htmlspecialchars($stepAtLabel) ?>
                        </div>
                        <?php elseif ($step['done']): ?>
                        <div style="color:#cbd5e1;font-size:0.72rem;margin-top:2px;">Time not recorded</div>
                        <?php endif; ?>
                    </div>
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
<?php if (!empty($canReverseToPo) && Auth::can('purchases', 'delete')): ?>
<script>
(function () {
    var form = document.getElementById('formPoReverseToPo');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        if (!confirm('Reverse this PO conversion?\n\nThis will cancel the linked purchase invoice, remove its stock, and reopen this PO for editing.\n\nProceed?')) {
            e.preventDefault();
        }
    });
})();
</script>
<?php endif; ?>
<script>
(function () {
    document.querySelectorAll('.po-doc-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Delete this document?')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.po-doc-browse-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-target');
            var input = id ? document.getElementById(id) : null;
            if (input) {
                input.click();
            }
        });
    });

    var maxBytes = 10 * 1024 * 1024;

    document.querySelectorAll('.po-doc-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            if (!(input.files && input.files[0])) {
                return;
            }
            var form = input.closest('form');
            if (!form) {
                return;
            }
            var file = input.files[0];
            if (file.size > maxBytes) {
                alert('File must be under 10 MB.');
                input.value = '';
                return;
            }

            var btn = form.querySelector('.po-doc-browse-btn');
            function resetBtn() {
                if (!btn) return;
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-upload"></i> Upload';
            }
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading…';
            }

            // form.submit() does not fire "submit", so the global unsaved-changes
            // guard can cancel navigation and leave the button stuck on Uploading…
            if (window.UnsavedGuard && typeof window.UnsavedGuard.clearDirty === 'function') {
                window.UnsavedGuard.clearDirty();
            }

            try {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            } catch (err) {
                resetBtn();
                input.value = '';
                alert('Could not start upload. Please try again.');
            }
        });
    });
})();
</script>
