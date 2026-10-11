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
$defaultPayAccountId = BaseController::preferredPayAccountId($accounts);
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
<div class="packing-panel packing-panel-pay mb-4">
    <div class="packing-panel-head">
        <span class="packing-panel-icon"><i class="bi bi-receipt"></i></span>
        <div>
            <div class="packing-panel-title">Pay Union invoice</div>
            <div class="packing-panel-sub">Fixed packing rate × qty. Discount reduces cash only — bank is not touched for that part.</div>
        </div>
    </div>
    <form method="POST" action="?page=landedcost&action=payPackingBulk" id="packingBulkPayForm"
          data-due-all="<?= htmlspecialchars(number_format($totalDue, DECIMAL_PLACES, '.', '')) ?>"
          data-due-month="<?= htmlspecialchars(number_format($monthTotal, DECIMAL_PLACES, '.', '')) ?>">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="settle_month" value="<?= htmlspecialchars($settleMonth) ?>">

        <div class="packing-amt-row">
            <div class="packing-amt-tile">
                <span class="packing-fld-lbl">ERP due</span>
                <div class="packing-amt-value" id="packingDueBaseLabel"><?= number_format($totalDue, DECIMAL_PLACES) ?></div>
                <span class="packing-amt-hint">Rate × qty</span>
            </div>
            <div class="packing-amt-tile">
                <span class="packing-fld-lbl">Discount</span>
                <div class="packing-due-step">
                    <button type="button" class="packing-due-step-btn" data-packing-disc-step="-1" title="Decrease discount">−</button>
                    <input type="number" name="discount_amount" id="packingDiscountAmount" class="form-control packing-amt-input" step="0.001" min="0"
                           value="0.000" inputmode="decimal" title="Reduce cash — posted as discount at payment">
                    <button type="button" class="packing-due-step-btn" data-packing-disc-step="1" title="Increase discount">+</button>
                </div>
                <span class="packing-amt-hint">At payment — no extra cash</span>
            </div>
            <div class="packing-amt-tile is-pay">
                <span class="packing-fld-lbl">Pay now</span>
                <input type="number" name="invoice_amount" id="packingInvoiceAmount" class="form-control packing-amt-input packing-amt-pay" required
                       step="0.001" min="0.001"
                       value="<?= htmlspecialchars(number_format($totalDue, DECIMAL_PLACES, '.', '')) ?>">
                <span class="packing-amt-hint">Cash from bank</span>
            </div>
        </div>

        <div class="packing-field-grid">
            <label class="packing-fld">
                <span class="packing-fld-lbl">List month</span>
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
            </label>
            <label class="packing-fld">
                <span class="packing-fld-lbl">Settle scope</span>
                <select name="settle_scope" class="form-select" id="packingSettleScope">
                    <option value="to_date" selected>All unpaid to date (<?= number_format($totalDue, DECIMAL_PLACES) ?> est.)</option>
                    <option value="month">This month only (<?= number_format($monthTotal, DECIMAL_PLACES) ?> est.)</option>
                </select>
            </label>
            <label class="packing-fld">
                <span class="packing-fld-lbl">Invoice # <span class="packing-opt">optional</span></span>
                <input type="text" name="invoice_ref" class="form-control" placeholder="Union inv no.">
            </label>
            <label class="packing-fld">
                <span class="packing-fld-lbl">Payment date</span>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </label>
            <label class="packing-fld packing-fld-wide">
                <span class="packing-fld-lbl">Bank account <span class="text-danger">*</span></span>
                <select name="account_id" id="packingPayAccount" class="form-select packing-bank" required>
                    <option value="">— Select bank account —</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= (int) $acc['id'] ?>"<?= (int) $acc['id'] === $defaultPayAccountId ? ' selected' : '' ?>><?= htmlspecialchars(BaseController::formatAccountLabel($acc, true)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="packing-fld packing-fld-wide">
                <span class="packing-fld-lbl">Note <span class="packing-opt">optional</span></span>
                <input type="text" name="notes" class="form-control" placeholder="e.g. till <?= date('d M Y') ?>">
            </label>
        </div>

        <div class="packing-panel-actions">
            <button type="submit" class="btn btn-info text-white packing-pay-btn" id="packingBulkPayBtn">
                <i class="bi bi-cash-stack me-1"></i> Pay Union invoice
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($canLink) && ($totalDue > 0.001 || $monthCount > 0)): ?>
<div class="packing-panel packing-panel-link mb-4">
    <div class="packing-panel-head">
        <span class="packing-panel-icon"><i class="bi bi-link-45deg"></i></span>
        <div>
            <div class="packing-panel-title">Already transferred? — link existing PAY</div>
            <div class="packing-panel-sub">
                No new cash. If the transfer is less than the due, enter that shortfall as Discount.
                Missing PAY? Record it under <a href="?page=payments&amp;action=create&amp;type=out">Payment Out</a> first.
            </div>
        </div>
    </div>
    <form method="POST" action="?page=landedcost&action=linkPackingExisting" id="packingLinkForm"
          data-due-all="<?= htmlspecialchars(number_format($totalDue, DECIMAL_PLACES, '.', '')) ?>"
          data-due-month="<?= htmlspecialchars(number_format($monthTotal, DECIMAL_PLACES, '.', '')) ?>">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="settle_month" value="<?= htmlspecialchars($settleMonth) ?>">
        <div class="packing-field-grid packing-link-grid">
            <label class="packing-fld">
                <span class="packing-fld-lbl">Settle scope</span>
                <select name="settle_scope" class="form-select" id="packingLinkScope">
                    <option value="month" selected>This month only (<?= htmlspecialchars($monthLabel) ?> · <?= number_format($monthTotal, DECIMAL_PLACES) ?> est.)</option>
                    <option value="to_date">All unpaid to date (<?= number_format($totalDue, DECIMAL_PLACES) ?> est.)</option>
                </select>
            </label>
            <label class="packing-fld">
                <span class="packing-fld-lbl">Discount</span>
                <div class="packing-due-step">
                    <button type="button" class="packing-due-step-btn" data-packing-link-disc-step="-1" title="Decrease discount">−</button>
                    <input type="number" name="discount_amount" id="packingLinkDiscount" class="form-control packing-amt-input" step="0.001" min="0"
                           value="0.000" inputmode="decimal" title="If the PAY is short of the packing due">
                    <button type="button" class="packing-due-step-btn" data-packing-link-disc-step="1" title="Increase discount">+</button>
                </div>
            </label>
            <label class="packing-fld">
                <span class="packing-fld-lbl">Or PAY no.</span>
                <input type="text" name="payment_no" class="form-control" placeholder="PAY-000123">
            </label>
            <label class="packing-fld packing-fld-full">
                <span class="packing-fld-lbl">Existing PAY(s)</span>
                <?php if (!empty($existingPayments)): ?>
                <select name="payment_ids[]" class="form-select" multiple size="4" id="packingExistingPays">
                    <?php foreach ($existingPayments as $ep): ?>
                    <option value="<?= (int) $ep['id'] ?>"
                        data-amount="<?= htmlspecialchars(number_format((float) $ep['amount'], DECIMAL_PLACES, '.', '')) ?>"
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
                <span class="packing-amt-hint">Ctrl/Cmd-click for several PAYs</span>
                <?php else: ?>
                <div class="packing-empty-pay">No unlinked Union PAYs — type a PAY number or create Payment Out first.</div>
                <?php endif; ?>
            </label>
        </div>
        <div class="packing-panel-actions">
            <button type="submit" class="btn btn-outline-info packing-pay-btn" id="packingLinkBtn">
                <i class="bi bi-link-45deg me-1"></i> Link PAY → vendor bill
            </button>
        </div>
    </form>
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

<style>
.packing-panel {
    border-radius: 14px;
    padding: 18px 20px 16px;
    background: #fff;
}
.packing-panel-pay {
    border: 1px solid #a5f3fc;
    background: linear-gradient(180deg, #f0fdff 0%, #ffffff 42%);
}
.packing-panel-link {
    border: 1px dashed #67e8f9;
    background: #fff;
}
.packing-panel-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}
.packing-panel-icon {
    width: 36px; height: 36px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border-radius: 10px;
    background: #cffafe; color: #0e7490; font-size: 1.05rem;
}
.packing-panel-title { font-weight: 700; color: #0f172a; line-height: 1.25; }
.packing-panel-sub { font-size: 0.8rem; color: #64748b; margin-top: 2px; line-height: 1.4; }
.packing-panel-sub a { color: #0e7490; }
.packing-fld-lbl {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
}
.packing-opt { font-weight: 600; text-transform: none; letter-spacing: 0; color: #94a3b8; }
.packing-amt-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.packing-amt-tile {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    min-height: 92px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.packing-amt-tile.is-pay {
    border-color: #67e8f9;
    background: #ecfeff;
}
.packing-amt-value {
    font-size: 1.35rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: #0f172a;
    line-height: 1.2;
    min-height: 38px;
    display: flex;
    align-items: center;
}
.packing-amt-hint { font-size: 0.7rem; color: #94a3b8; margin-top: 6px; }
.packing-due-step { display: flex; align-items: stretch; gap: 6px; }
.packing-due-step-btn {
    width: 36px; flex-shrink: 0;
    border: 1.5px solid #a5f3fc; border-radius: 9px; background: #ecfeff;
    color: #0e7490; font-weight: 800; cursor: pointer; line-height: 1; font-size: 1.05rem;
}
.packing-due-step-btn:hover { background: #cffafe; }
.packing-amt-input {
    height: 38px;
    text-align: right;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.packing-amt-pay { border-color: #22d3ee; font-size: 1.05rem; }
.packing-field-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px 14px;
    align-items: start;
}
.packing-fld { display: block; margin: 0; min-width: 0; }
.packing-fld .form-control,
.packing-fld .form-select { height: 40px; }
.packing-fld .form-select[multiple] { height: auto; min-height: 96px; }
.packing-fld-wide { grid-column: span 2; }
.packing-fld-full { grid-column: 1 / -1; }
.packing-bank { border: 2px solid #67e8f9; }
.packing-panel-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
}
.packing-pay-btn {
    min-width: 220px;
    height: 42px;
    font-weight: 700;
    border-radius: 10px;
}
.packing-empty-pay {
    min-height: 40px;
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 8px;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.8rem;
}
.packing-link-grid { grid-template-columns: 1.4fr 0.9fr 0.9fr; }
@media (max-width: 991px) {
    .packing-amt-row { grid-template-columns: 1fr; }
    .packing-field-grid,
    .packing-link-grid { grid-template-columns: 1fr 1fr; }
    .packing-fld-wide { grid-column: span 2; }
    .packing-fld-full { grid-column: 1 / -1; }
    .packing-pay-btn { width: 100%; min-width: 0; }
}
@media (max-width: 575px) {
    .packing-field-grid,
    .packing-link-grid { grid-template-columns: 1fr; }
    .packing-fld-wide,
    .packing-fld-full { grid-column: auto; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function money(n) {
        return (Math.round((parseFloat(n) || 0) * 1000) / 1000).toFixed(3);
    }
    function parseAmt(el) {
        return Math.round((parseFloat(String(el && el.value || '').replace(/,/g, '')) || 0) * 1000) / 1000;
    }
    function formDue(form, scopeVal) {
        if (!form) return 0;
        var all = parseFloat(form.getAttribute('data-due-all') || '0') || 0;
        var month = parseFloat(form.getAttribute('data-due-month') || '0') || 0;
        return scopeVal === 'month' ? month : all;
    }

    var monthSelect = document.getElementById('packingMonthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            window.location.href = '?page=landedcost&action=packingDue&month=' + encodeURIComponent(monthSelect.value);
        });
    }

    var form = document.getElementById('packingBulkPayForm');
    var btn  = document.getElementById('packingBulkPayBtn');
    var amt  = document.getElementById('packingInvoiceAmount');
    var disc = document.getElementById('packingDiscountAmount');
    var scope = document.getElementById('packingSettleScope');
    var dueLabel = document.getElementById('packingDueBaseLabel');
    var syncingPay = false;

    function currentDue() {
        return formDue(form, scope ? scope.value : 'to_date');
    }
    function applyDiscountToPay() {
        if (!form || !amt || !disc) return;
        var due = currentDue();
        var d = Math.max(0, parseAmt(disc));
        if (d > due) {
            d = due;
            disc.value = money(d);
        }
        syncingPay = true;
        amt.value = money(Math.max(0, due - d));
        syncingPay = false;
        if (dueLabel) dueLabel.textContent = money(due);
    }
    function applyPayToDiscount() {
        if (!form || !amt || !disc || syncingPay) return;
        var due = currentDue();
        var pay = Math.max(0, parseAmt(amt));
        disc.value = money(Math.max(0, due - pay));
    }
    if (scope) {
        scope.addEventListener('change', function () {
            if (disc) disc.value = '0.000';
            applyDiscountToPay();
        });
    }
    if (disc) {
        disc.addEventListener('input', applyDiscountToPay);
    }
    if (amt) {
        amt.addEventListener('input', applyPayToDiscount);
    }
    document.querySelectorAll('[data-packing-disc-step]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!disc) return;
            var step = parseFloat(b.getAttribute('data-packing-disc-step') || '0') || 0;
            var next = Math.max(0, parseAmt(disc) + step);
            var due = currentDue();
            if (next > due) next = due;
            disc.value = money(next);
            applyDiscountToPay();
        });
    });

    if (form && btn) {
        form.addEventListener('submit', function (e) {
            var v = amt ? parseAmt(amt) : 0;
            var d = disc ? parseAmt(disc) : 0;
            if (!(v > 0)) {
                e.preventDefault();
                window.alert('Enter the cash to pay (ERP due minus discount).');
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
            var msg = 'Deduct ' + v.toFixed(3) + ' KWD from:\n' + label;
            if (d > 0.001) {
                msg += '\n\nDiscount at payment: ' + d.toFixed(3) + ' KWD (no extra cash).';
            }
            if (!window.confirm(msg + '\n\nContinue?')) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Paying…';
        });
    }

    var linkForm = document.getElementById('packingLinkForm');
    var linkBtn  = document.getElementById('packingLinkBtn');
    var linkDisc = document.getElementById('packingLinkDiscount');
    var linkScope = document.getElementById('packingLinkScope');
    var linkPays = document.getElementById('packingExistingPays');

    function selectedPaySum() {
        if (!linkPays) return 0;
        var sum = 0;
        Array.prototype.forEach.call(linkPays.options, function (o) {
            if (o.selected) sum += parseFloat(o.getAttribute('data-amount') || '0') || 0;
        });
        return Math.round(sum * 1000) / 1000;
    }
    function suggestLinkDiscount() {
        if (!linkForm || !linkDisc) return;
        var due = formDue(linkForm, linkScope ? linkScope.value : 'month');
        var pay = selectedPaySum();
        if (pay > 0.001) {
            linkDisc.value = money(Math.max(0, due - pay));
        }
    }
    if (linkScope) linkScope.addEventListener('change', suggestLinkDiscount);
    if (linkPays) linkPays.addEventListener('change', suggestLinkDiscount);
    suggestLinkDiscount();
    document.querySelectorAll('[data-packing-link-disc-step]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!linkDisc) return;
            var step = parseFloat(b.getAttribute('data-packing-link-disc-step') || '0') || 0;
            var next = Math.max(0, parseAmt(linkDisc) + step);
            var due = formDue(linkForm, linkScope ? linkScope.value : 'month');
            if (next > due) next = due;
            linkDisc.value = money(next);
        });
    });

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
            var d = linkDisc ? parseAmt(linkDisc) : 0;
            var msg = 'Link existing PAY to packing — no new cash will leave the account?';
            if (d > 0.001) {
                msg = 'Link existing PAY and record ' + d.toFixed(3) + ' KWD packing discount (no extra cash)?';
            }
            if (!window.confirm(msg)) {
                e.preventDefault();
                return;
            }
            linkBtn.disabled = true;
            linkBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Linking…';
        });
    }
});
</script>
