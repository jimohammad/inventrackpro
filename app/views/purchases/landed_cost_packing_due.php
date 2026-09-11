<?php
$packingParty = $packingParty ?? null;
$partyName = (string) ($packingParty['name'] ?? 'Union Logistics FZCO');
$monthRows   = $monthRows ?? [];
$monthTotal  = (float) ($monthTotal ?? 0);
$monthLabel  = $monthLabel ?? date('F Y');
$settleMonth = $settleMonth ?? date('Y-m');
$dueMonths   = $dueMonths ?? [];
$monthCount  = count($monthRows);
$accounts    = $accounts ?? [];
$canPay      = $canPay ?? false;
$totalDue    = (float) ($totalDue ?? 0);
$rows        = $rows ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0">Packing due</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">
            Packing DXB — <strong><?= htmlspecialchars($partyName) ?></strong>
            <?php if (!empty($packingParty['party_code'])): ?>
            <span class="text-muted">(<?= htmlspecialchars($packingParty['party_code']) ?>)</span>
            <?php endif; ?>
            — pay from <strong>Union’s invoice</strong> (ERP lines are estimates until billed)
        </p>
    </div>
    <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Shipments</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="?page=landedcost&action=packingDue" class="btn btn-sm btn-info text-white">
        <i class="bi bi-box-seam me-1"></i> Packing due
    </a>
    <a href="?page=landedcost&action=payables" class="btn btn-sm btn-outline-success">
        <i class="bi bi-truck me-1"></i> Freight payables
    </a>
    <a href="?page=landedcost&action=partnerDue" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-calendar-event me-1"></i> Partner due
    </a>
</div>

<div class="alert alert-light border mb-3" style="border-radius:12px;font-size:0.85rem;">
    <strong>ERP practice (invoice AP):</strong> Shipment packing lines are <em>costing estimates</em> only —
    they do <strong>not</strong> sit on Union’s party statement.
    When the vendor invoice arrives, pay (or link) the <strong>invoice KWD</strong> → ERP posts a
    <strong>vendor bill</strong> matched 1:1 to the PAY. Statement stays Clear without opening-balance hacks.
</div>

<?php if (Auth::isAdmin() || Auth::can('payments', 'delete')): ?>
<div class="alert alert-secondary border mb-3" style="border-radius:12px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="small mb-0">
            <strong>One-time migrate (admin):</strong> move historical packing estimates off the party ledger
            and create vendor bills for existing packing PAYs (opening reset to 0).
            <?php if (!empty($unionLedger)): ?>
            <span class="text-warning">Current statement shows
                <?= APP_CURRENCY ?> <?= number_format(abs((float) $unionLedger['display']), DECIMAL_PLACES) ?>
                <?= ((float) $unionLedger['display']) > 0 ? 'DR' : 'CR' ?>.</span>
            <?php endif; ?>
        </div>
        <form method="POST" action="?page=landedcost&action=migrateUnionPackingToVendorBills" id="unionMigrateVendorBillsForm">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Migrate → vendor bill AP
            </button>
        </form>
    </div>
</div>
<script>
(function () {
    var f = document.getElementById('unionMigrateVendorBillsForm');
    if (!f) return;
    f.addEventListener('submit', function (e) {
        if (!window.confirm('Migrate Union packing to invoice AP (vendor bills)? No bank cash is changed.')) {
            e.preventDefault();
        }
    });
})();
</script>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100" style="border-radius:12px;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">All unpaid (ERP estimate)</div>
                    <div class="text-muted" style="font-size:0.75rem;">Until Union invoice arrives</div>
                </div>
                <span class="fw-bold" style="font-size:1.2rem;color:#0e7490;">KWD <?= number_format($totalDue, DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100" style="border-radius:12px;border-left:4px solid #0e7490;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($monthLabel) ?> (ERP estimate)</div>
                    <div class="text-muted" style="font-size:0.75rem;">Shipment lines this month</div>
                </div>
                <span class="fw-bold" style="font-size:1.2rem;color:#0e7490;">KWD <?= number_format($monthTotal, DECIMAL_PLACES) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (($totalDue > 0.001 || $monthCount > 0) && !empty($canPay)): ?>
<div class="card mb-4" style="border-radius:12px;border:1px solid #a5f3fc;background:linear-gradient(135deg,#ecfeff,#cffafe);">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-receipt" style="font-size:1.2rem;color:#0e7490;"></i>
            <div>
                <div class="fw-bold">Pay Union invoice — one payment</div>
                <div class="text-muted" style="font-size:0.82rem;">
                    Cash leaves the bank for the <strong>invoice amount only</strong>.
                    Default scope clears <strong>all unpaid packing to date</strong> (recommended when the bill is “till today”).
                </div>
            </div>
        </div>
        <form method="POST" action="?page=landedcost&action=payPackingBulk" class="row g-3 align-items-end" id="packingBulkPayForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="settle_month" value="<?= htmlspecialchars($settleMonth) ?>">
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">List month</label>
                <select name="month_display" class="form-select" id="packingMonthSelect">
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
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Settle scope</label>
                <select name="settle_scope" class="form-select" id="packingSettleScope">
                    <option value="to_date" selected>All unpaid to date (<?= number_format($totalDue, DECIMAL_PLACES) ?> est.)</option>
                    <option value="month">This month only (<?= number_format($monthTotal, DECIMAL_PLACES) ?> est.)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Union invoice KWD</label>
                <input type="number" name="invoice_amount" id="packingInvoiceAmount" class="form-control" required
                       step="0.001" min="0.001" placeholder="e.g. 394.449"
                       value="">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Invoice # (opt.)</label>
                <input type="text" name="invoice_ref" class="form-control" placeholder="Union inv no.">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">
                    Bank account <span class="text-danger">*</span>
                </label>
                <select name="account_id" id="packingPayAccount" class="form-select border-info" required style="border-width:2px;">
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
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Note (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="e.g. till <?= date('d M Y') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info text-white w-100" id="packingBulkPayBtn">
                    <i class="bi bi-cash-stack me-1"></i> Pay Union invoice
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canLink) && ($totalDue > 0.001 || $monthCount > 0)): ?>
<div class="card mb-4" style="border-radius:12px;border:1px dashed #0e7490;">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-link-45deg" style="font-size:1.2rem;color:#0e7490;"></i>
            <div>
                <div class="fw-bold">Already transferred? — link existing PAY</div>
                <div class="text-muted" style="font-size:0.82rem;">
                    No new cash. Use when Union was paid in Payment Out / bank transfer
                    (e.g. <strong>303.588 on 2 Jul</strong>) but June packing is still open.
                    If the PAY is missing below, record it under <a href="?page=payments&amp;action=create&amp;type=out">Payment Out</a> to Union first, then return here.
                </div>
            </div>
        </div>
        <form method="POST" action="?page=landedcost&action=linkPackingExisting" class="row g-3 align-items-end" id="packingLinkForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="settle_month" value="<?= htmlspecialchars($settleMonth) ?>">
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Settle scope</label>
                <select name="settle_scope" class="form-select">
                    <option value="month" selected>This month only (<?= htmlspecialchars($monthLabel) ?> · <?= number_format($monthTotal, DECIMAL_PLACES) ?> est.)</option>
                    <option value="to_date">All unpaid to date (<?= number_format($totalDue, DECIMAL_PLACES) ?> est.)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Existing PAY(s)</label>
                <?php if (!empty($existingPayments)): ?>
                <select name="payment_ids[]" class="form-select" multiple size="4" id="packingExistingPays">
                    <?php foreach ($existingPayments as $ep): ?>
                    <option value="<?= (int) $ep['id'] ?>"
                        <?= !empty($ep['amount_match']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $ep['payment_no']) ?>
                        · <?= number_format((float) $ep['amount'], DECIMAL_PLACES) ?> KWD
                        · <?= htmlspecialchars((string) ($ep['date'] ?? '')) ?>
                        <?php if (!empty($ep['account_name'])): ?>
                        · <?= htmlspecialchars((string) $ep['account_name']) ?>
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text" style="font-size:0.72rem;">Ctrl/Cmd-click for several PAYs</div>
                <?php else: ?>
                <div class="form-control bg-light text-muted" style="font-size:0.82rem;">
                    No unlinked Union outbound PAYs found — type PAY no. below or create Payment Out first.
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Or PAY no.</label>
                <input type="text" name="payment_no" class="form-control" placeholder="PAY-000123">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-info w-100" id="packingLinkBtn">
                    <i class="bi bi-link-45deg me-1"></i> Link PAY → vendor bill
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($monthCount === 0 && $totalDue > 0.001): ?>
<div class="alert alert-info mb-4" style="font-size:0.85rem;">
    No unpaid packing for <strong><?= htmlspecialchars($monthLabel) ?></strong>.
    Choose another month from the list below.
</div>
<?php endif; ?>

<?php if (!empty($dueMonths) && count($dueMonths) > 1): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted" style="font-size:0.82rem;">Show month:</span>
    <?php foreach ($dueMonths as $m): ?>
    <a href="?page=landedcost&action=packingDue&amp;month=<?= urlencode($m) ?>"
       class="btn btn-sm <?= $m === $settleMonth ? 'btn-info text-white' : 'btn-outline-secondary' ?>">
        <?= date('M Y', strtotime($m . '-01')) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="border-radius:12px;">
    <div class="card-header bg-white fw-semibold">
        Packing lines received in <?= htmlspecialchars($monthLabel) ?> (<?= (int) $monthCount ?>) — ERP estimate
    </div>
    <div class="card-body p-0">
        <?php if (empty($monthRows)): ?>
        <p class="text-center text-muted py-5 mb-0">No unpaid packing lines for this month.</p>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>Shipment</th>
                    <th>Received</th>
                    <th>PO / Item</th>
                    <th>Pay to</th>
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
                <td><?= htmlspecialchars($r['party_name'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($r['rate_per_pc'] ?? 0), DECIMAL_PLACES) ?></td>
                <td class="text-end"><?= (int) ($r['quantity'] ?? 0) ?></td>
                <td class="text-end fw-semibold"><?= number_format((float) ($r['amount'] ?? 0), DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#ecfeff;">
                    <td colspan="6" class="fw-bold">ERP estimate this month (not the invoice)</td>
                    <td class="text-end fw-bold"><?= number_format($monthTotal, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var monthSelect = document.getElementById('packingMonthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            window.location.href = '?page=landedcost&action=packingDue&month=' + encodeURIComponent(monthSelect.value);
        });
    }

    var form = document.getElementById('packingBulkPayForm');
    var btn  = document.getElementById('packingBulkPayBtn');
    var amt  = document.getElementById('packingInvoiceAmount');
    if (!form || !btn) return;
    form.addEventListener('submit', function (e) {
        var v = amt ? parseFloat(String(amt.value || '').replace(/,/g, '')) : 0;
        if (!(v > 0)) {
            e.preventDefault();
            window.alert('Enter Union’s invoice amount (exact KWD from their bill).');
            if (amt) amt.focus();
            return;
        }
        var acct = document.getElementById('packingPayAccount');
        if (!acct || !acct.value) {
            e.preventDefault();
            window.alert('Please select the bank account to pay from.');
            if (acct) acct.focus();
            return;
        }
        var label = acct.options[acct.selectedIndex] ? acct.options[acct.selectedIndex].text : '';
        if (!window.confirm('Deduct ' + v.toFixed(3) + ' KWD from:\n' + label + '\n\nContinue?')) {
            e.preventDefault();
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Paying…';
    });
    var linkForm = document.getElementById('packingLinkForm');
    var linkBtn  = document.getElementById('packingLinkBtn');
    if (linkForm && linkBtn) {
        linkForm.addEventListener('submit', function (e) {
            var sel = document.getElementById('packingExistingPays');
            var typed = linkForm.querySelector('input[name="payment_no"]');
            var hasSel = sel && Array.prototype.some.call(sel.options, function (o) { return o.selected; });
            var hasTyped = typed && String(typed.value || '').trim() !== '';
            if (!hasSel && !hasTyped) {
                e.preventDefault();
                window.alert('Select an existing PAY or type the PAY number (e.g. the 2 Jul 303.588 transfer).');
                return;
            }
            if (!window.confirm('Link existing PAY to packing — no new cash will leave the account?')) {
                e.preventDefault();
                return;
            }
            linkBtn.disabled = true;
            linkBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Linking…';
        });
    }
});
</script>
