<style>
.pf-page { max-width: 760px; margin: 0 auto; padding: 6px 4px 18px; }
.pf-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:18px; }
.pf-head .left { display:flex;align-items:center;gap:12px; }
.pf-head a.back { width:34px;height:34px;border-radius:10px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;background:#fff;transition:all .2s ease; }
.pf-head a.back:hover { border-color:var(--primary);color:var(--primary);box-shadow:0 4px 12px rgba(99,102,241,.18);transform:translateY(-1px); }
.pf-head h1 { font-size:1.24rem;font-weight:800;margin:0;letter-spacing:.1px; }

/* Toggle pill for In/Out — compact, in header */
.pf-toggle { display:inline-flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:0; }
.pf-toggle button { background:transparent;border:none;padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:600;color:#64748b;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s; }
.pf-toggle button.active.in  { background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 2px 6px rgba(16,185,129,.4); }
.pf-toggle button.active.out { background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;box-shadow:0 2px 6px rgba(239,68,68,.4); }

/* Card — colored top accent per section */
.pf-card { background:var(--bg-card);border:1px solid #e7eaf3;border-radius:16px;padding:20px 22px;margin-bottom:14px;position:relative;overflow:hidden;box-shadow:0 10px 24px rgba(15,23,42,.05);transition:box-shadow .2s, border-color .2s; }
.pf-card:hover { box-shadow:0 14px 30px rgba(15,23,42,.08); border-color:#d9dff0; }
.pf-card::before { content:'';position:absolute;left:0;top:0;width:4px;height:100%;background:var(--card-accent,var(--primary)); }
.pf-card.c-party    { --card-accent: linear-gradient(180deg,#8b5cf6,#6366f1); }
.pf-card.c-party    { background:linear-gradient(135deg,#fff,#fafaff); }
.pf-card.c-account  { --card-accent: linear-gradient(180deg,#10b981,#059669); }
.pf-card.c-account  { background:linear-gradient(135deg,#fff,#f7fdfa); }
.pf-card.c-notes    { --card-accent: linear-gradient(180deg,#f59e0b,#d97706); }
.pf-card.c-notes    { background:linear-gradient(135deg,#fff,#fffdf7); }

.pf-sec { display:flex;align-items:center;gap:8px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px; }
.pf-sec .num { width:24px;height:24px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:.74rem;color:#fff;font-weight:800;box-shadow:0 2px 6px rgba(0,0,0,.15); }
.pf-card.c-party   .pf-sec { color:#6d28d9; }
.pf-card.c-party   .pf-sec .num { background:linear-gradient(135deg,#8b5cf6,#6366f1); }
.pf-card.c-account .pf-sec { color:#047857; }
.pf-card.c-account .pf-sec .num { background:linear-gradient(135deg,#10b981,#059669); }
.pf-card.c-notes   .pf-sec { color:#b45309; }
.pf-card.c-notes   .pf-sec .num { background:linear-gradient(135deg,#f59e0b,#d97706); }

/* Meta row: PAY# + date */
.pf-meta { display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:12px;padding:10px 14px;margin-bottom:14px;box-shadow:0 4px 14px rgba(99,102,241,.12); }
.pf-meta .num { font-family:monospace;font-weight:800;color:#4338ca;font-size:.88rem;display:flex;align-items:center;gap:5px; }
.pf-meta .num i { color:#6366f1; }
.pf-meta input[type=date] { width:auto;padding:6px 10px;border:1.5px solid #a5b4fc;border-radius:7px;font-size:.82rem;font-weight:600;background:#fff;color:#1e293b;outline:none; }
.pf-meta input[type=date]:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15); }

/* Party search */
.pf-party-wrap { position:relative; }
.pf-party-wrap .select2-container { width:100% !important; }
.pf-party-wrap .select2-selection--single {
    height:48px !important; border-radius:11px !important;
    border:1.5px solid #dbe2ee !important;
    background:var(--bg-main) !important;
}
.pf-party-wrap .select2-selection__rendered { line-height:44px !important; padding-left:14px !important; font-size:.92rem !important; font-weight:600 !important; color:var(--text-main) !important; }
.pf-party-wrap .select2-selection__placeholder { color:var(--text-muted) !important; font-weight:400 !important; }
.pf-party-wrap .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
}

/* Balance card */
.pf-bal { display:none;margin-top:10px;border-radius:10px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between; }
.pf-bal.show { display:flex; }
.pf-bal.owes  { background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1.5px solid #fdba74; }
.pf-bal.youowe{ background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #93c5fd; }
.pf-bal.clear { background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac; }
.pf-bal-label { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px; }
.pf-bal-amt { font-size:1.3rem;font-weight:800;font-family:monospace; }
.pf-bal.owes  .pf-bal-label { color:#9a3412; }
.pf-bal.owes  .pf-bal-amt   { color:#c2410c; }
.pf-bal.youowe .pf-bal-label{ color:#1e40af; }
.pf-bal.youowe .pf-bal-amt  { color:#1d4ed8; }
.pf-bal.clear .pf-bal-label { color:#166534; }
.pf-bal.clear .pf-bal-amt   { color:#15803d; }
.pf-bal-fill { background:none;border:1.5px solid currentColor;color:inherit;padding:5px 12px;border-radius:7px;font-size:.74rem;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:.4px; }
.pf-bal-fill:hover { opacity:.85; }

/* Account + amount grid */
.pf-grid2 { display:grid;grid-template-columns:1.2fr 1fr;gap:12px; }
.pf-field label { display:block;font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px; }
.pf-field select, .pf-field input, .pf-field textarea {
    width:100%;padding:0 14px;border:1.5px solid #dbe2ee;border-radius:11px;
    font-size:.95rem;background:var(--bg-main);color:var(--text-main);outline:none;font-family:inherit;
    height:49px;line-height:49px;box-sizing:border-box;transition:border-color .15s, box-shadow .15s, background-color .15s;
}
.pf-field textarea { height:auto;padding:10px 14px;line-height:1.5; }
.pf-field input[type=number] { font-weight:700;font-size:1.15rem; }
.pf-field input[type=date] { font-weight:600; }
.pf-field input:focus, .pf-field select:focus, .pf-field textarea:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#fff; }

/* Method pills */
.pf-method { display:flex;gap:6px;flex-wrap:wrap;margin-top:6px; }
.pf-method-pill { padding:6px 14px;border-radius:20px;background:var(--bg-main);border:1.5px solid var(--border-color);color:var(--text-muted);font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;user-select:none; }
.pf-method-pill:hover { border-color:var(--primary);color:var(--primary); }
.pf-method-pill.active { background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 2px 6px rgba(99,102,241,.3); }

/* Ref strip when coming from invoice */
.pf-ref { background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(139,92,246,.05));border:1px solid rgba(99,102,241,.25);border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;font-size:.84rem; }
.pf-ref i { color:var(--primary);font-size:1rem; }
.pf-ref strong { color:var(--text-main); }

/* Footer */
.pf-foot { position:sticky;bottom:10px;z-index:8;display:flex;justify-content:flex-end;gap:8px;padding:10px;border:1px solid #e5e7eb;border-radius:12px;background:rgba(255,255,255,.9);backdrop-filter:blur(4px);box-shadow:0 8px 24px rgba(2,6,23,.08);margin-top:4px; }
.pf-btn { padding:10px 18px;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;border:none;text-decoration:none;transition:all .15s ease; }
.pf-btn.cancel { background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted); }
.pf-btn.cancel:hover { border-color:#ef4444;color:#ef4444;background:#fff5f5; }
.pf-btn.save { background:var(--bg-card);color:var(--primary);border:1.5px solid var(--primary); }
.pf-btn.save:hover { background:rgba(99,102,241,.06); transform:translateY(-1px); }
.pf-btn.print { background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;box-shadow:0 2px 6px rgba(99,102,241,.3); }
.pf-btn.print:hover { transform:translateY(-1px);box-shadow:0 4px 10px rgba(99,102,241,.4); }

@media (max-width: 540px) {
    .pf-grid2 { grid-template-columns:1fr; }
    .pf-head { align-items:flex-start; gap:8px; }
    .pf-head h1 { font-size:1.05rem; }
    .pf-foot { position:static; padding:0; border:none; box-shadow:none; background:transparent; backdrop-filter:none; display:grid; grid-template-columns:1fr; }
    .pf-btn { justify-content:center; width:100%; }
}

/* Lightweight motion trial (for this page only) */
.pf-anim-enter { opacity:0; transform:translateY(10px) scale(.995); transition:opacity .28s ease, transform .28s ease; will-change:opacity,transform; }
.pf-anim-enter.is-visible { opacity:1; transform:translateY(0) scale(1); }
.pf-bal.pf-bal-pop { animation:pfBalPop .28s ease; }
@keyframes pfBalPop {
    0%   { transform:scale(.985); }
    60%  { transform:scale(1.01); }
    100% { transform:scale(1); }
}
@media (prefers-reduced-motion: reduce) {
    .pf-anim-enter, .pf-anim-enter.is-visible { transition:none; opacity:1; transform:none; }
    .pf-bal.pf-bal-pop { animation:none; }
}
</style>

<?php
    // Mode-driven theme
    $mode      = $mode ?? 'in';
    $isReceive = $mode === 'in';
    $modeTitle = $isReceive ? 'Receive Payment' : 'Make Payment';
    $modeIcon  = $isReceive ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill';
    $modeColor = $isReceive ? '#10b981' : '#ef4444';
    $partyLbl  = $isReceive ? 'Customer'  : 'Supplier';
    $accentBg  = $isReceive
        ? 'linear-gradient(135deg,#10b981,#059669)'
        : 'linear-gradient(135deg,#ef4444,#dc2626)';
    $preselectPartyId = $preselectPartyId ?? 0;
?>
<div class="pf-page">
    <div class="pf-head">
        <div class="left">
            <a href="?page=payments" class="back"><i class="bi bi-arrow-left"></i></a>
            <h1>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:<?= $accentBg ?>;color:#fff;margin-right:8px;box-shadow:0 2px 8px rgba(0,0,0,.15);">
                    <i class="bi <?= $modeIcon ?>"></i>
                </span>
                <?= $modeTitle ?>
            </h1>
        </div>
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;padding:6px 14px;border-radius:20px;background:<?= $accentBg ?>;color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.15);">
            <?= $isReceive ? 'IN — Money Received' : 'OUT — Money Paid' ?>
        </div>
    </div>
    <p style="margin:-8px 0 14px 46px;color:#64748b;font-size:.82rem;">
        Record payment quickly with clean account selection and live party balance feedback.
    </p>

    <form method="POST" action="?page=payments&action=store" id="payForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="payment_form_nonce" value="<?= htmlspecialchars($paymentFormNonce ?? '') ?>">
        <input type="hidden" name="print_mode"     id="printMode"     value="0">
        <input type="hidden" name="payment_type"   value="<?= $mode ?>">
        <!-- payment_method derived server-side from account.type unless cheque_no provided -->

        <?php if ($refData): ?>
        <div class="pf-ref">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Linked to <strong><?= htmlspecialchars($refData['invoice_no']) ?></strong> · <?= htmlspecialchars($refData['party_name']) ?> · Balance: <strong><?= APP_CURRENCY ?> <?= number_format($refData['balance'], DECIMAL_PLACES) ?></strong></span>
        </div>
        <input type="hidden" name="ref_type" value="<?= htmlspecialchars($refType) ?>">
        <input type="hidden" name="ref_id"   value="<?= (int)$refId ?>">
        <?php else: ?>
        <input type="hidden" name="ref_type" value="<?= htmlspecialchars($refType) ?>">
        <input type="hidden" name="ref_id"   value="">
        <?php endif; ?>

        <!-- Meta row -->
        <div class="pf-meta">
            <span class="num"><i class="bi bi-hash me-1"></i><?= htmlspecialchars($nextPayNo) ?></span>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
        </div>

        <!-- Party -->
        <div class="pf-card c-party">
            <div class="pf-sec"><span class="num">1</span> <?= $partyLbl ?></div>
            <div class="pf-party-wrap">
                <select name="party_id" class="form-select" required id="partySelect">
                    <option value="">Search <?= strtolower($partyLbl) ?> by name or phone...</option>
                    <?php foreach ($parties as $p):
                        $isSelected = ($refData && $refData['party_id'] == $p['id']) ||
                                      ($preselectPartyId && $preselectPartyId == $p['id']);
                    ?>
                    <option value="<?= $p['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?><?= $p['phone'] ? ' · ' . htmlspecialchars($p['phone']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pf-bal" id="partyBal">
                <div>
                    <div class="pf-bal-label" id="balLabel"></div>
                    <div class="pf-bal-amt"   id="balAmount"></div>
                </div>
            </div>
        </div>

        <!-- Account + Amount -->
        <div class="pf-card c-account">
            <div class="pf-sec"><span class="num">2</span> Account &amp; Amount</div>
            <div class="pf-grid2">
                <div class="pf-field">
                    <label>Account <span id="acctTypeBadge" style="font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:4px;margin-left:5px;display:none;text-transform:uppercase;letter-spacing:.4px;"></span></label>
                    <select name="account_id" id="accountSelect" required onchange="onAccountChange()">
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" data-type="<?= htmlspecialchars($acc['type']) ?>"><?= htmlspecialchars($acc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pf-field">
                    <label>Amount</label>
                    <input type="number" name="amount" id="amt1" step="0.001" min="0.001" required
                           value="<?= $refData ? $refData['balance'] : '' ?>" placeholder="0.000">
                </div>
            </div>

            <!-- Cheque toggle (collapsed by default) -->
            <div style="margin-top:12px;">
                <a href="javascript:void(0)" onclick="toggleCheque()" id="chequeToggle"
                   style="font-size:.78rem;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:6px;border:1px dashed var(--border-color);">
                    <i class="bi bi-card-text"></i> <span id="chequeToggleLabel">Cheque payment? Add cheque number</span>
                </a>
                <div id="chequeRow" style="display:none;margin-top:8px;">
                    <input type="text" name="cheque_no" id="chequeInput" placeholder="Cheque number"
                           style="width:100%;padding:9px 12px;border:1.5px solid #fcd34d;border-radius:9px;font-size:.92rem;background:#fffbeb;color:#78350f;outline:none;font-family:monospace;font-weight:600;">
                    <small style="color:#92400e;font-size:.72rem;margin-top:4px;display:block;">
                        <i class="bi bi-info-circle me-1"></i>Filling this records the payment as <strong>Cheque</strong> regardless of account type.
                    </small>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="pf-card c-notes">
            <div class="pf-sec"><span class="num">3</span> Notes (Optional)</div>
            <div class="pf-field">
                <textarea name="notes" rows="2" placeholder="Reference, remarks..."></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="pf-foot">
            <a href="?page=payments" class="pf-btn cancel">Cancel</a>
            <button type="submit" class="pf-btn save" onclick="document.getElementById('printMode').value='0'">
                <i class="bi bi-check-lg"></i> Save
            </button>
            <button type="submit" class="pf-btn print" onclick="document.getElementById('printMode').value='1'">
                <i class="bi bi-printer"></i> Save &amp; Print
            </button>
        </div>
    </form>
</div>

<script>
var partyBalance = 0;

function onAccountChange() {
    var sel = document.getElementById('accountSelect');
    var opt = sel.options[sel.selectedIndex];
    var type = opt ? (opt.dataset.type || 'cash') : 'cash';
    var badge = document.getElementById('acctTypeBadge');
    var styles = {
        cash:          { bg:'#d1fae5', color:'#065f46', label:'Cash' },
        bank:          { bg:'#dbeafe', color:'#1e40af', label:'Bank' },
        mobile_wallet: { bg:'#ede9fe', color:'#5b21b6', label:'Wallet' },
        other:         { bg:'#fef3c7', color:'#92400e', label:'Other' }
    };
    var s = styles[type] || styles.other;
    badge.style.background = s.bg;
    badge.style.color = s.color;
    badge.textContent = s.label;
    badge.style.display = 'inline-block';
}

function toggleCheque() {
    var row = document.getElementById('chequeRow');
    var lbl = document.getElementById('chequeToggleLabel');
    var open = row.style.display === 'none' || !row.style.display;
    row.style.display = open ? 'block' : 'none';
    lbl.textContent = open ? 'Cancel cheque payment' : 'Cheque payment? Add cheque number';
    if (open) document.getElementById('chequeInput').focus();
    else document.getElementById('chequeInput').value = '';
}

function showPartyBalance() {
    var sel    = document.getElementById('partySelect');
    var box    = document.getElementById('partyBal');
    var label  = document.getElementById('balLabel');
    var amount = document.getElementById('balAmount');
    if (!sel || !sel.value) { box.classList.remove('show','owes','youowe','clear'); return; }

    label.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Loading...';
    amount.textContent = '';
    box.className = 'pf-bal show clear';

    fetch('?page=payments&action=partyBalance&id=' + sel.value)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var bal  = parseFloat(data.balance) || 0;
            partyBalance = bal;
            var curr = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
            box.className = 'pf-bal show';
            if (bal > 0.001) {
                box.classList.add('owes');
                label.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $isReceive ? 'Customer Owes You' : 'You Are Owed (credit)' ?>';
                amount.textContent = curr + ' ' + bal.toFixed(3);
            } else if (bal < -0.001) {
                box.classList.add('youowe');
                label.innerHTML = '<i class="bi bi-info-circle-fill me-1"></i> <?= $isReceive ? 'You Owe (advance held)' : 'You Owe Supplier' ?>';
                amount.textContent = curr + ' ' + Math.abs(bal).toFixed(3);
            } else {
                box.classList.add('clear');
                label.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Account Clear';
                amount.textContent = curr + ' 0.000';
            }
            box.classList.remove('pf-bal-pop');
            // retrigger tiny emphasis animation when fresh balance arrives
            void box.offsetWidth;
            box.classList.add('pf-bal-pop');
        })
        .catch(function() { box.classList.remove('show'); });
}

document.addEventListener('DOMContentLoaded', function() {
    var payForm = document.getElementById('payForm');
    if (payForm) {
        payForm.addEventListener('submit', function(e) {
            if (payForm.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }
            payForm.dataset.submitting = '1';
            payForm.querySelectorAll('button[type="submit"]').forEach(function(btn) {
                btn.disabled = true;
            });
        });
    }

    // Page-entry reveal animation (staggered)
    var revealTargets = document.querySelectorAll('.pf-meta, .pf-ref, .pf-card, .pf-foot');
    revealTargets.forEach(function(el, i) {
        el.classList.add('pf-anim-enter');
        setTimeout(function() { el.classList.add('is-visible'); }, 30 + (i * 55));
    });

    onAccountChange();
    var checkReady = setInterval(function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            clearInterval(checkReady);
            var $p = jQuery('#partySelect');
            $p.select2({ placeholder: 'Search party by name or phone...' });
            $p.on('select2:select', function() { showPartyBalance(); });
            $p.on('select2:clear',  function() { document.getElementById('partyBal').classList.remove('show'); });
            if ($p.val()) showPartyBalance();
        }
    }, 50);
});
</script>
