<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0">Partner profit due</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">
            Partner: <strong><?= htmlspecialchars($importPartner['name'] ?? 'Muhammad Faisal') ?></strong>
            <?php if (!empty($importPartner['party_code'])): ?>
            <span class="text-muted">(<?= htmlspecialchars($importPartner['party_code']) ?>)</span>
            <?php endif; ?>
            — pay once per month (one transaction in Payments)
        </p>
    </div>
    <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Shipments</a>
</div>

<?php
$partnerNavActive = 'due';
include __DIR__ . '/landed_cost_partner_nav.php';

$monthRows   = $monthRows ?? [];
$monthTotal  = (float) ($monthTotal ?? 0);
$monthLabel  = $monthLabel ?? date('F Y');
$settleMonth = $settleMonth ?? date('Y-m');
$dueMonths   = $dueMonths ?? [];
$monthCount  = count($monthRows);
?>

<?php if (!empty($settlementMismatch)): ?>
<?php
$mismatch = $settlementMismatch;
$isDup    = ($mismatch['type'] ?? '') === 'duplicate_settlements';
$dupIds   = $mismatch['pay_ids'] ?? [];
$lumps    = $mismatch['lump_payments'] ?? [];
$canFixDuplicates = $canFixDuplicates ?? false;
?>
<div class="alert alert-warning border-warning mb-3" style="border-radius:12px;">
    <div class="d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
        <div class="flex-grow-1">
            <?php if ($isDup): ?>
            <div class="fw-bold">Duplicate partner payments detected</div>
            <p class="mb-2" style="font-size:0.88rem;">
                On <strong><?= date('d M Y', strtotime((string) ($mismatch['date'] ?? ''))) ?></strong>,
                <strong><?= (int) ($mismatch['count'] ?? 0) ?> payments</strong>
                (<?= htmlspecialchars((string) ($mismatch['pay_nos'] ?? '')) ?>)
                total <strong><?= APP_CURRENCY ?> <?= number_format((float) ($mismatch['total'] ?? 0), DECIMAL_PLACES) ?></strong>
                were recorded separately. Partner due shows
                <strong><?= APP_CURRENCY ?> <?= number_format((float) ($mismatch['open_due'] ?? 0), DECIMAL_PLACES) ?></strong>
                but the party account was debited an extra
                <strong><?= APP_CURRENCY ?> <?= number_format((float) ($mismatch['total'] ?? 0), DECIMAL_PLACES) ?></strong>.
            </p>
            <?php if ($lumps !== []): ?>
            <p class="mb-2 text-muted" style="font-size:0.82rem;">
                You also have an older lump payment
                <?php foreach ($lumps as $lp): ?>
                <strong><?= htmlspecialchars($lp['payment_no'] ?? '') ?></strong>
                (<?= APP_CURRENCY ?> <?= number_format((float) ($lp['amount'] ?? 0), DECIMAL_PLACES) ?>)
                <?php endforeach; ?>
                not linked to profit lines.
            </p>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canFixDuplicates): ?>
                <form method="POST" action="?page=landedcost&action=partnerReconcileDuplicates" class="d-inline">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="mode" value="undo_only">
                    <?php foreach ($dupIds as $did): ?>
                    <input type="hidden" name="duplicate_ids[]" value="<?= (int) $did ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-sm btn-warning">
                        Remove duplicates — restore Partner due
                    </button>
                </form>
                <?php if ($lumps !== []): ?>
                <?php $firstLump = $lumps[0]; ?>
                <form method="POST" action="?page=landedcost&action=partnerReconcileDuplicates" class="d-inline">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="mode" value="link_lump">
                    <input type="hidden" name="lump_payment_id" value="<?= (int) ($firstLump['id'] ?? 0) ?>">
                    <?php foreach ($dupIds as $did): ?>
                    <input type="hidden" name="duplicate_ids[]" value="<?= (int) $did ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        Remove duplicates &amp; link to <?= htmlspecialchars($firstLump['payment_no'] ?? 'lump payment') ?>
                    </button>
                </form>
                <?php endif; ?>
                <?php else: ?>
                <p class="mb-0 text-muted" style="font-size:0.82rem;">
                    Ask an admin to click fix, or undo PAY-569…577 one by one in
                    <a href="?page=landedcost&action=partnerHistory">Partner history</a>.
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="fw-bold">Unlinked partner payment</div>
            <p class="mb-0" style="font-size:0.88rem;">
                Partner due is clear but an older payment was never linked to profit lines.
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100" style="border-radius:12px;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span class="fw-semibold">All unpaid (every month)</span>
                <span class="fw-bold" style="font-size:1.2rem;color:#b45309;">KWD <?= number_format((float) ($totalDue ?? 0), DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100" style="border-radius:12px;border-left:4px solid #b45309;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><?= htmlspecialchars($monthLabel) ?> due</span>
                <span class="fw-bold" style="font-size:1.2rem;color:#b45309;">KWD <?= number_format($monthTotal, DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($monthCount > 0 && !empty($canPay)): ?>
<div class="card mb-4" style="border-radius:12px;border:1px solid #fde68a;background:linear-gradient(135deg,#fffbeb,#fef3c7);">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-calendar-check" style="font-size:1.2rem;color:#b45309;"></i>
            <div>
                <div class="fw-bold">Monthly settlement — one payment</div>
                <div class="text-muted" style="font-size:0.82rem;">
                    Pays <strong><?= htmlspecialchars($importPartner['name'] ?? 'Muhammad Faisal') ?></strong> for
                    <strong><?= htmlspecialchars($monthLabel) ?></strong> only — creates
                    <strong>one</strong> row in Payments / account (not one per shipment line).
                </div>
            </div>
        </div>
        <form method="POST" action="?page=landedcost&action=payPartnerBulk" class="row g-3 align-items-end" id="partnerBulkPayForm"
              data-pay-amount="<?= htmlspecialchars(number_format($monthTotal, DECIMAL_PLACES, '.', '')) ?>"
              data-pay-month="<?= htmlspecialchars($monthLabel) ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="settle_month" value="<?= htmlspecialchars($settleMonth) ?>">
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Month</label>
                <select name="month_display" class="form-select" id="partnerMonthSelect">
                    <?php foreach ($dueMonths as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $m === $settleMonth ? 'selected' : '' ?>>
                        <?= date('F Y', strtotime($m . '-01')) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php if ($dueMonths === []): ?>
                    <option value="<?= htmlspecialchars($settleMonth) ?>"><?= htmlspecialchars($monthLabel) ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">
                    Bank account <span class="text-danger">*</span>
                </label>
                <select name="account_id" id="partnerPayAccount" class="form-select border-warning" required
                        style="border-width:2px;">
                    <option value="">— Select bank account —</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= (int) $acc['id'] ?>"><?= htmlspecialchars(BaseController::formatAccountLabel($acc, true)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Payment date</label>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Note (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="<?= htmlspecialchars($monthLabel) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100" id="partnerBulkPayBtn">
                    <i class="bi bi-cash-stack me-1"></i> Pay <?= number_format($monthTotal, DECIMAL_PLACES) ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php elseif (empty($rows)): ?>
<?php else: ?>
<div class="alert alert-info mb-4" style="font-size:0.85rem;">
    No unpaid lines for <strong><?= htmlspecialchars($monthLabel) ?></strong>.
    <?php if (($totalDue ?? 0) > 0): ?> Choose another month from the list below.<?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($dueMonths) && count($dueMonths) > 1): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted" style="font-size:0.82rem;">Show month:</span>
    <?php foreach ($dueMonths as $m): ?>
    <a href="?page=landedcost&action=partnerDue&amp;month=<?= urlencode($m) ?>"
       class="btn btn-sm <?= $m === $settleMonth ? 'btn-warning' : 'btn-outline-secondary' ?>">
        <?= date('M Y', strtotime($m . '-01')) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="border-radius:12px;">
    <div class="card-header bg-white fw-semibold">
        Lines received in <?= htmlspecialchars($monthLabel) ?> (<?= (int) $monthCount ?>)
    </div>
    <div class="card-body p-0">
        <?php if (empty($monthRows)): ?>
        <p class="text-center text-muted py-5 mb-0">No unpaid lines for this month.</p>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>Shipment</th>
                    <th>Received</th>
                    <th>PO / Item</th>
                    <th class="text-end">Rate/pc</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">KWD</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($monthRows as $r): ?>
            <tr>
                <td><a href="?page=landedcost&action=view&id=<?= (int) ($r['shipment_id'] ?? 0) ?>"><?= htmlspecialchars($r['shipment_no']) ?></a></td>
                <td><?= !empty($r['received_date']) ? date('d M Y', strtotime($r['received_date'])) : '—' ?></td>
                <td>
                    <span class="text-muted"><?= htmlspecialchars($r['po_no'] ?? '') ?></span><br>
                    <?= htmlspecialchars($r['item_name'] ?? '') ?>
                </td>
                <td class="text-end"><?= number_format((float) ($r['partner_profit_per_pc'] ?? 0), DECIMAL_PLACES) ?></td>
                <td class="text-end"><?= (int) ($r['quantity'] ?? 0) ?></td>
                <td class="text-end fw-semibold"><?= number_format((float) ($r['amount'] ?? 0), DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fffbeb;">
                    <td colspan="5" class="fw-bold">Included in monthly payment</td>
                    <td class="text-end fw-bold"><?= number_format($monthTotal, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var monthSelect = document.getElementById('partnerMonthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            window.location.href = '?page=landedcost&action=partnerDue&month=' + encodeURIComponent(monthSelect.value);
        });
    }

    var form = document.getElementById('partnerBulkPayForm');
    var btn  = document.getElementById('partnerBulkPayBtn');
    var acct = document.getElementById('partnerPayAccount');
    if (!form || !btn || !acct) return;
    form.addEventListener('submit', function (e) {
        if (!acct.value) {
            e.preventDefault();
            alert('Please select the bank account to pay from.');
            acct.focus();
            return;
        }
        var label = acct.options[acct.selectedIndex] ? acct.options[acct.selectedIndex].text : '';
        var amt = form.getAttribute('data-pay-amount') || '';
        var month = form.getAttribute('data-pay-month') || '';
        var msg = 'Deduct ' + amt + ' KWD from:\n' + label
            + '\n\nPartner profit — ' + month
            + '\n\nContinue?';
        if (!window.confirm(msg)) {
            e.preventDefault();
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Paying…';
    });
});
</script>
