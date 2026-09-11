<?php
$groups  = $groups ?? [];
$accounts = $accounts ?? [];
$canPay  = $canPay ?? false;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0">Freight payables</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">
            Open import freight payables (HK→DXB / DXB→Kuwait). Packing (Union Logistics) is monthly —
            use <a href="?page=landedcost&action=packingDue">Packing due</a>.
        </p>
    </div>
    <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Shipments</a>
</div>

<div class="card mb-3" style="border-radius:12px;">
    <div class="card-body d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Total unpaid freight</span>
        <span class="fw-bold" style="font-size:1.35rem;color:#059669;">KWD <?= number_format((float) ($totalDue ?? 0), DECIMAL_PLACES) ?></span>
    </div>
</div>

<?php if (!empty($logixClear) && !empty($canWriteOff)): ?>
<div class="alert alert-warning border-warning mb-3" style="border-radius:12px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold"><?= htmlspecialchars($logixClear['party_name']) ?> — clear all</div>
            <div class="small text-muted mb-0">
                <?php if ((int) ($logixClear['open_count'] ?? 0) > 0): ?>
                Open HK freight: <strong><?= (int) $logixClear['open_count'] ?> line(s)</strong>
                · <strong>KWD <?= number_format((float) $logixClear['open_total'], DECIMAL_PLACES) ?></strong>
                <?php else: ?>
                No open freight lines
                <?php endif; ?>
                <?php if ((int) ($logixClear['cancelled_count'] ?? 0) > 0): ?>
                · Cancelled (needs repair): <strong><?= (int) $logixClear['cancelled_count'] ?></strong>
                · <strong>KWD <?= number_format((float) ($logixClear['cancelled_total'] ?? 0), DECIMAL_PLACES) ?></strong>
                <?php endif; ?>
                <?php if (abs((float) ($logixClear['opening_balance'] ?? 0)) > 0.001): ?>
                · Opening balance <strong><?= number_format((float) $logixClear['opening_balance'], DECIMAL_PLACES) ?></strong>
                <?php endif; ?>
                — click once after upload: links PAYs if possible, then <strong>forces Party Master to 0</strong>
                (opening offset for leftover payments). Hard-refresh Party Master after.
            </div>
        </div>
        <form method="POST" action="?page=landedcost&action=writeOffLogixAll" id="logixClearAllForm">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-slash-circle me-1"></i> Clear all Logix — zero ledger
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($groups) && ($canPay || !empty($canLink))): ?>
<style>
.freight-bulk-pay-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-end;
    gap: 0.65rem;
}
.freight-bulk-meta {
    flex: 1 1 160px;
    min-width: 140px;
    max-width: 240px;
    padding-bottom: 2px;
}
.freight-bulk-field {
    flex: 0 0 110px;
}
.freight-bulk-field--account {
    flex: 1 1 160px;
    min-width: 140px;
}
.freight-bulk-field--note {
    flex: 1 1 120px;
    min-width: 100px;
}
.freight-bulk-action {
    flex: 0 0 130px;
}
.freight-bulk-pay-row .form-label {
    white-space: nowrap;
}
.freight-bulk-link-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-end;
    gap: 0.65rem;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
    border-top: 1px dashed rgba(13, 148, 136, 0.35);
}
.freight-bulk-link-meta {
    flex: 1 1 180px;
    min-width: 140px;
    padding-bottom: 2px;
    font-size: 0.78rem;
    color: #0f766e;
}
.freight-bulk-link-field {
    flex: 1 1 260px;
    min-width: 180px;
}
.freight-bulk-link-field .freight-pay-multi {
    min-height: 4.5rem;
}
.freight-bulk-link-or {
    flex: 0 0 150px;
}
.freight-bulk-link-action {
    flex: 0 0 150px;
}
@media (max-width: 991.98px) {
    .freight-bulk-pay-row,
    .freight-bulk-link-row {
        flex-wrap: wrap;
    }
    .freight-bulk-meta,
    .freight-bulk-link-meta {
        flex: 1 1 100%;
        max-width: none;
        margin-bottom: 0.25rem;
    }
    .freight-bulk-field,
    .freight-bulk-field--account,
    .freight-bulk-field--note,
    .freight-bulk-action,
    .freight-bulk-link-field,
    .freight-bulk-link-or,
    .freight-bulk-link-action {
        flex: 1 1 calc(50% - 0.65rem);
        min-width: 120px;
    }
}
</style>
<?php foreach ($groups as $g):
    $lineCount = (int) ($g['line_count'] ?? 0);
    if ($lineCount < 1) {
        continue;
    }
    $allocated = (float) ($g['allocated'] ?? 0);
    $invoiceAmt = (float) ($g['invoice_amount'] ?? $allocated);
    $poLabel = !empty($g['po_nos']) ? implode(', ', $g['po_nos']) : '—';
    $existingPayments = $g['existing_payments'] ?? [];
    $canLinkGroup = !empty($canLink);
?>
<div class="card mb-3" style="border-radius:12px;border:1px solid #99f6e4;background:linear-gradient(135deg,#f0fdfa,#ccfbf1);">
    <div class="card-body py-3">
        <?php if (!empty($canPay)): ?>
        <form method="POST" action="?page=landedcost&action=payFreightBulk" class="freight-bulk-pay-row">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="party_id" value="<?= (int) ($g['party_id'] ?? 0) ?>">
            <input type="hidden" name="shipment_id" value="<?= (int) ($g['shipment_id'] ?? 0) ?>">
            <input type="hidden" name="ref_type" value="<?= htmlspecialchars($g['ref_type'] ?? '') ?>">
            <?php foreach ($g['charge_ids'] as $cid): ?>
            <input type="hidden" name="charge_ids[]" value="<?= (int) $cid ?>">
            <?php endforeach; ?>

            <div class="freight-bulk-meta">
                <div class="fw-bold text-truncate" title="<?= htmlspecialchars($g['party_name'] ?? '') ?>"><?= htmlspecialchars($g['party_name'] ?? '') ?></div>
                <div class="text-muted text-truncate" style="font-size:0.75rem;" title="<?= htmlspecialchars(($g['charge_label'] ?? '') . ' · ' . ($g['shipment_no'] ?? '') . ' · ' . $poLabel) ?>">
                    <?= htmlspecialchars($g['charge_label'] ?? '') ?>
                    · <a href="?page=landedcost&action=view&id=<?= (int) ($g['shipment_id'] ?? 0) ?>"><?= htmlspecialchars($g['shipment_no'] ?? '') ?></a>
                    · <?= htmlspecialchars($poLabel) ?>
                    · <?= $lineCount ?> line<?= $lineCount === 1 ? '' : 's' ?>
                    <?php if (abs($invoiceAmt - $allocated) > 0.001): ?>
                    · alloc <?= number_format($allocated, DECIMAL_PLACES) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="freight-bulk-field">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Invoice (KWD)</label>
                <input type="number" name="amount" class="form-control form-control-sm" step="0.001" min="0.001"
                       value="<?= number_format($invoiceAmt, 3, '.', '') ?>" required
                       title="<?= abs($invoiceAmt - $allocated) > 0.001 ? 'Snapped from ' . number_format($allocated, DECIMAL_PLACES) . ' (rounding)' : 'Invoice amount' ?>">
            </div>

            <div class="freight-bulk-field freight-bulk-field--account">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Bank account <span class="text-danger">*</span></label>
                <select name="account_id" class="form-select form-select-sm" required>
                    <option value="">— Select bank —</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= (int) $acc['id'] ?>"><?= htmlspecialchars(BaseController::formatAccountLabel($acc, true)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="freight-bulk-field">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="freight-bulk-field freight-bulk-field--note">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Note</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Invoice #">
            </div>

            <div class="freight-bulk-action">
                <label class="form-label mb-1" style="font-size:0.72rem;visibility:hidden;">Pay</label>
                <button type="submit" class="btn btn-success btn-sm text-nowrap w-100">
                    <i class="bi bi-cash-stack me-1"></i>
                    Pay <?= number_format($invoiceAmt, DECIMAL_PLACES) ?>
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="freight-bulk-meta mb-2">
            <div class="fw-bold"><?= htmlspecialchars($g['party_name'] ?? '') ?></div>
            <div class="text-muted" style="font-size:0.75rem;">
                <?= htmlspecialchars($g['charge_label'] ?? '') ?>
                · <?= htmlspecialchars($g['shipment_no'] ?? '') ?>
                · <?= htmlspecialchars($poLabel) ?>
                · invoice <?= number_format($invoiceAmt, DECIMAL_PLACES) ?> KWD
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canLinkGroup): ?>
        <form method="POST" action="?page=landedcost&action=linkFreightExisting" class="freight-bulk-link-row freight-link-form"
              data-allocated="<?= htmlspecialchars(number_format($allocated, 3, '.', '')) ?>"
              data-invoice="<?= htmlspecialchars(number_format($invoiceAmt, 3, '.', '')) ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="party_id" value="<?= (int) ($g['party_id'] ?? 0) ?>">
            <input type="hidden" name="shipment_id" value="<?= (int) ($g['shipment_id'] ?? 0) ?>">
            <input type="hidden" name="ref_type" value="<?= htmlspecialchars($g['ref_type'] ?? '') ?>">
            <?php foreach ($g['charge_ids'] as $cid): ?>
            <input type="hidden" name="charge_ids[]" value="<?= (int) $cid ?>">
            <?php endforeach; ?>

            <div class="freight-bulk-link-meta">
                <strong>Already paid?</strong> Select one or more PAYs (Ctrl/Cmd-click) — no new cash.
                <div class="freight-link-sum text-muted" style="font-size:0.72rem;">Selected: <span class="fw-semibold">0.000</span> / due <?= number_format($invoiceAmt, DECIMAL_PLACES) ?></div>
            </div>

            <div class="freight-bulk-link-field">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Existing payment(s)</label>
                <select name="payment_ids[]" class="form-select form-select-sm freight-pay-multi" multiple size="<?= min(4, max(2, count($existingPayments))) ?>">
                    <?php foreach ($existingPayments as $ep):
                        $match = !empty($ep['amount_match']);
                        $amt = (float) ($ep['amount'] ?? 0);
                        $label = ($ep['payment_no'] ?? '')
                            . ' · ' . number_format($amt, DECIMAL_PLACES)
                            . ' · ' . (!empty($ep['date']) ? date('d M Y', strtotime((string) $ep['date'])) : '');
                        if ($match) {
                            $label .= ' ✓ match';
                        }
                    ?>
                    <option value="<?= (int) ($ep['id'] ?? 0) ?>"
                            data-amount="<?= htmlspecialchars(number_format($amt, 3, '.', '')) ?>"
                            <?= $match ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="freight-bulk-link-or">
                <label class="form-label fw-semibold mb-1" style="font-size:0.72rem;">Or PAY nos.</label>
                <input type="text" name="payment_no" class="form-control form-control-sm" placeholder="PAY-476, PAY-462" title="Comma-separated if paid in multiple transfers">
            </div>

            <div class="freight-bulk-link-action">
                <label class="form-label mb-1" style="font-size:0.72rem;visibility:hidden;">Link</label>
                <button type="submit" class="btn btn-outline-teal btn-sm w-100" style="border-color:#0d9488;color:#0f766e;">
                    <i class="bi bi-link-45deg me-1"></i> Mark paid
                </button>
            </div>
        </form>
        <?php if ($existingPayments === []): ?>
        <div class="text-muted mt-1" style="font-size:0.72rem;">
            No PAY list found — type PAY numbers (e.g. <code>PAY-000476, PAY-000462</code>), or open
            <a href="?page=payments&amp;action=pay&amp;party_id=<?= (int) ($g['party_id'] ?? 0) ?>">Payments</a>.
        </div>
        <?php else: ?>
        <div class="text-muted mt-1" style="font-size:0.72rem;">
            Tip: paid in two transfers? Select both PAYs so the sum matches ~<?= number_format($invoiceAmt, DECIMAL_PLACES) ?> KWD.
        </div>
        <?php endif; ?>

        <?php if (!empty($canWriteOff)): ?>
        <form method="POST" action="?page=landedcost&action=writeOffFreightOpen" class="freight-writeoff-form mt-2"
              data-party="<?= htmlspecialchars($g['party_name'] ?? '') ?>"
              data-amount="<?= htmlspecialchars(number_format($invoiceAmt, 3, '.', '')) ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="party_id" value="<?= (int) ($g['party_id'] ?? 0) ?>">
            <input type="hidden" name="shipment_id" value="<?= (int) ($g['shipment_id'] ?? 0) ?>">
            <input type="hidden" name="ref_type" value="<?= htmlspecialchars($g['ref_type'] ?? '') ?>">
            <input type="hidden" name="reason" value="Forwarder account already zero / settled">
            <?php foreach ($g['charge_ids'] as $cid): ?>
            <input type="hidden" name="charge_ids[]" value="<?= (int) $cid ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-slash-circle me-1"></i>
                Write off <?= number_format($invoiceAmt, DECIMAL_PLACES) ?> — no cash (zero ledger)
            </button>
            <span class="text-muted ms-2" style="font-size:0.72rem;">Use for Logix/etc. when already settled and party should be 0. Prefer Mark paid if PAYs exist.</span>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="card" style="border-radius:12px;">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <p class="text-center text-muted py-5 mb-0">No unpaid freight lines.</p>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>Ref</th>
                    <th>Shipment</th>
                    <th>Received</th>
                    <th>Charge</th>
                    <th>PO / Item</th>
                    <th>Pay to</th>
                    <th class="text-end">KWD</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $notes = $r['charge_label'] . ' — ' . $r['shipment_no'] . ' / ' . $r['po_no'] . ' / ' . $r['item_name'];
                $payUrl = '?page=payments&action=pay&import_payable=1'
                    . '&party_id=' . (int) ($r['party_id'] ?? 0)
                    . '&amount=' . urlencode(number_format((float) $r['amount'], 3, '.', ''))
                    . '&ref_type=' . urlencode($r['ref_type'])
                    . '&ref_id=' . (int) $r['charge_id']
                    . '&notes=' . urlencode($notes);
            ?>
            <tr>
                <td><code class="small"><?= htmlspecialchars($r['accrual_no'] ?? '') ?></code></td>
                <td><a href="?page=landedcost&action=view&id=<?= (int) ($r['shipment_id'] ?? 0) ?>"><?= htmlspecialchars($r['shipment_no']) ?></a></td>
                <td><?= !empty($r['received_date']) ? date('d M Y', strtotime($r['received_date'])) : '—' ?></td>
                <td><?= htmlspecialchars($r['charge_label']) ?></td>
                <td><span class="text-muted"><?= htmlspecialchars($r['po_no']) ?></span><br><?= htmlspecialchars($r['item_name']) ?></td>
                <td><?= htmlspecialchars($r['party_name']) ?></td>
                <td class="text-end fw-semibold"><?= number_format((float) $r['amount'], DECIMAL_PLACES) ?></td>
                <td>
                    <?php if ($canPay): ?>
                    <a href="<?= htmlspecialchars($payUrl) ?>" class="btn btn-sm btn-outline-secondary">Pay line</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateLinkSum(form) {
        var sel = form.querySelector('.freight-pay-multi');
        var sumEl = form.querySelector('.freight-link-sum span');
        if (!sel || !sumEl) return;
        var total = 0;
        Array.prototype.forEach.call(sel.selectedOptions, function (opt) {
            total += parseFloat(opt.getAttribute('data-amount') || '0') || 0;
        });
        sumEl.textContent = total.toFixed(3);
        var due = parseFloat(form.getAttribute('data-invoice') || '0') || 0;
        var diff = Math.abs(total - due);
        sumEl.style.color = (total > 0.001 && diff <= Math.max(2, due * 0.02)) ? '#059669' : '';
    }

    document.querySelectorAll('.freight-link-form').forEach(function (form) {
        var sel = form.querySelector('.freight-pay-multi');
        if (sel) {
            sel.addEventListener('change', function () { updateLinkSum(form); });
            updateLinkSum(form);
        }
    });

    document.querySelectorAll('.freight-writeoff-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var party = form.getAttribute('data-party') || 'forwarder';
            var amt = form.getAttribute('data-amount') || '';
            var ok = confirm(
                'Write off open freight for ' + party + ' (' + amt + ' KWD)?\n\n' +
                '• Removes due from Freight payables and party ledger\n' +
                '• Does NOT move cash\n' +
                '• Only use if already settled / account is zero\n' +
                '• If PAYs exist, cancel and use Mark paid instead'
            );
            if (!ok) {
                e.preventDefault();
            }
        });
    });

    var logixForm = document.getElementById('logixClearAllForm');
    if (logixForm) {
        logixForm.addEventListener('submit', function (e) {
            var ok = confirm(
                'Clear ALL Logix One open HK freight and zero opening balance?\n\n' +
                '• Every open Logix freight line will be written off\n' +
                '• Party ledger should show 0\n' +
                '• No cash will move\n\n' +
                'Continue only if Logix is fully settled.'
            );
            if (!ok) {
                e.preventDefault();
            }
        });
    }
});
</script>
