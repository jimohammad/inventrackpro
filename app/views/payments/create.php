<!-- New Payment Form -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=payments" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">New Payment</h1>
</div>

<style>
.pay-type-wrap { display:flex; gap:10px; margin-bottom:18px; }
.pay-type-btn {
    flex:1; padding:12px; border-radius:10px; border:2px solid var(--border-color);
    background:var(--bg-card); color:var(--text-muted); cursor:pointer;
    text-align:center; font-weight:600; font-size:0.875rem;
    transition:all 0.15s;
}
.pay-type-btn i { display:block; font-size:1.4rem; margin-bottom:4px; }
.pay-type-btn.active-in  { border-color:#10b981; background:rgba(16,185,129,0.1); color:#10b981; }
.pay-type-btn.active-out { border-color:#ef4444; background:rgba(239,68,68,0.1); color:#ef4444; }
</style>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card" style="border-radius:14px;overflow:hidden;">
            <div class="card-header" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <i class="bi bi-cash-stack me-2" style="color:var(--primary);"></i>Payment Details
            </div>
            <div class="card-body" style="padding:1.5rem;">
                <form method="POST" action="?page=payments&action=store" id="payForm">
    <?= Auth::csrfField() ?>

                    <?php if ($refData): ?>
                    <div style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.25);border-radius:8px;padding:10px 14px;margin-bottom:18px;font-size:0.875rem;color:var(--text-main);">
                        <i class="bi bi-receipt me-1" style="color:var(--primary);"></i>
                        <strong><?= $refData['invoice_no'] ?></strong> &mdash;
                        <?= htmlspecialchars($refData['party_name']) ?> &mdash;
                        Balance: <strong><?= APP_CURRENCY ?> <?= number_format($refData['balance'], DECIMAL_PLACES) ?></strong>
                    </div>
                    <input type="hidden" name="ref_type" value="<?= $refType ?>">
                    <input type="hidden" name="ref_id"   value="<?= $refId ?>">
                    <?php else: ?>
                    <input type="hidden" name="ref_type" value="sale">
                    <input type="hidden" name="ref_id"   value="">
                    <?php endif; ?>

                    <input type="hidden" name="print_mode"    id="printMode"   value="0">
                    <input type="hidden" name="payment_type"  id="payTypeInput" value="in">

                    <!-- Transaction Number + Date -->
                    <div class="mb-3 d-flex align-items-center justify-content-between"
                         style="background:rgba(99,102,241,0.07);border:1px dashed rgba(99,102,241,0.35);border-radius:8px;padding:9px 14px;">
                        <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                            <i class="bi bi-hash me-1"></i>Transaction No
                        </span>
                        <span style="font-size:1rem;font-weight:800;color:var(--primary);letter-spacing:1px;">
                            <?= htmlspecialchars($nextPayNo) ?>
                        </span>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required
                               style="width:auto;max-width:160px;font-size:0.85rem;font-weight:600;border:1.5px solid rgba(99,102,241,0.3);border-radius:7px;">
                    </div>

                    <!-- Payment Type Toggle -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Payment Type</label>
                        <div class="pay-type-wrap">
                            <div class="pay-type-btn active-in" id="btnIn" onclick="setPayType('in')">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                                Payment In
                                <small style="display:block;font-weight:400;font-size:0.73rem;margin-top:2px;">Receiving money</small>
                            </div>
                            <div class="pay-type-btn" id="btnOut" onclick="setPayType('out')">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                                Payment Out
                                <small style="display:block;font-weight:400;font-size:0.73rem;margin-top:2px;">Paying money</small>
                            </div>
                        </div>
                    </div>

                    <!-- Party -->
                    <div class="mb-3">
                        <label class="form-label">Customer / Supplier <span class="text-danger">*</span></label>
                        <select name="party_id" class="form-select select2-party" required id="partySelect">
                            <option value="">Select party...</option>
                            <?php foreach ($parties as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($refData && $refData['party_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (<?= ucfirst($p['type']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="partyBalBadge" style="display:none;margin-top:8px;padding:12px 16px;border-radius:10px;font-size:0.9rem;font-weight:700;">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <span id="balLabel" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"></span>
                                <span id="balAmount" style="font-size:1.15rem;font-weight:800;"></span>
                            </div>
                        </div>
                    </div>


                    <!-- Account & Amount -->
                    <div class="mb-2">
                        <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:12px;">
                            <div style="font-size:0.7rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                <i class="bi bi-wallet2 me-1" style="color:#6366f1;"></i> Account
                            </div>
                            <select name="account_id" class="form-select form-select-sm mb-2" required style="font-size:0.85rem;">
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="font-size:0.7rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                <i class="bi bi-cash-stack me-1" style="color:#10b981;"></i> Amount
                            </div>
                            <input type="number" name="amount" id="amt1" class="form-control form-control-sm" step="0.001" min="0.001"
                                value="<?= $refData ? $refData['balance'] : '' ?>" required placeholder="0.000"
                                oninput="calcSplitTotal()" style="font-size:1rem;font-weight:700;">
                        </div>
                    </div>

                    <input type="hidden" name="payment_method" value="cash">

                    <!-- Total -->
                    <div class="mb-3" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:2px solid #86efac;border-radius:10px;padding:8px 18px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:0.75rem;color:#166534;font-weight:600;text-transform:uppercase;">Total Payment</span>
                        <span id="splitTotalAmt" style="font-size:1.2rem;font-weight:800;color:#059669;">0.000</span>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="?page=payments" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-outline-primary"
                                onclick="document.getElementById('printMode').value='0'">
                            <i class="bi bi-check-lg me-1"></i> Save
                        </button>
                        <button type="submit" class="btn btn-primary"
                                onclick="document.getElementById('printMode').value='1'">
                            <i class="bi bi-printer me-1"></i> Print & Save
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showPartyBalance() {
    var badge = document.getElementById('partyBalBadge');
    var label = document.getElementById('balLabel');
    var amount = document.getElementById('balAmount');
    var sel = document.getElementById('partySelect');
    if (!sel || !sel.value) { badge.style.display = 'none'; return; }

    // Fetch balance via AJAX — fast, no page reload
    label.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Loading...';
    label.style.color = '#64748b';
    amount.textContent = '';
    badge.style.background = '#f8fafc';
    badge.style.border = '1px solid #e2e8f0';
    badge.style.display = 'block';

    fetch('?page=payments&action=partyBalance&id=' + sel.value)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var bal = parseFloat(data.balance) || 0;
            var curr = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
            if (bal > 0.001) {
                label.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Party Owes You';
                amount.textContent = curr + ' ' + bal.toFixed(3);
                badge.style.background = 'linear-gradient(135deg,#fef2f2,#fff1f2)';
                badge.style.border = '2px solid #fecaca';
                label.style.color = '#dc2626';
                amount.style.color = '#dc2626';
            } else if (bal < -0.001) {
                label.innerHTML = '<i class="bi bi-info-circle-fill me-1"></i> You Owe This Party';
                amount.textContent = '-' + curr + ' ' + Math.abs(bal).toFixed(3);
                badge.style.background = 'linear-gradient(135deg,#f5f3ff,#ede9fe)';
                badge.style.border = '2px solid #c4b5fd';
                label.style.color = '#7c3aed';
                amount.style.color = '#7c3aed';
            } else {
                label.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Balance Clear';
                amount.textContent = curr + ' 0.000';
                badge.style.background = 'linear-gradient(135deg,#f0fdf4,#ecfdf5)';
                badge.style.border = '2px solid #86efac';
                label.style.color = '#059669';
                amount.style.color = '#059669';
            }
        });
}

function calcSplitTotal() {
    var a1 = parseFloat(document.getElementById('amt1').value) || 0;
    document.getElementById('splitTotalAmt').textContent = a1.toFixed(3);
}

function setPayType(type) {
    document.getElementById('payTypeInput').value = type;
    var btnIn  = document.getElementById('btnIn');
    var btnOut = document.getElementById('btnOut');
    if (type === 'in') {
        btnIn.className  = 'pay-type-btn active-in';
        btnOut.className = 'pay-type-btn';
    } else {
        btnIn.className  = 'pay-type-btn';
        btnOut.className = 'pay-type-btn active-out';
    }
}

// Wait for jQuery + Select2 to be ready
document.addEventListener('DOMContentLoaded', function() {
    var checkReady = setInterval(function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            clearInterval(checkReady);
            var $p = jQuery('#partySelect');
            $p.select2({ placeholder: 'Search party...' });
            $p.on('select2:select', function() { showPartyBalance(); });
            $p.on('select2:clear', function() { document.getElementById('partyBalBadge').style.display='none'; });
            if ($p.val()) showPartyBalance();
            calcSplitTotal();
        }
    }, 50);
});
</script>
