<style>
.pf-page { max-width: 760px; margin: 0 auto; padding: 6px 4px 18px; }
.pf-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:18px; }
.pf-head .left { display:flex;align-items:center;gap:12px; }
.pf-head a.back { width:34px;height:34px;border-radius:10px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;background:#fff;transition:all .2s ease; }
.pf-head a.back:hover { border-color:var(--primary);color:var(--primary);box-shadow:0 4px 12px rgba(99,102,241,.18);transform:translateY(-1px); }
.pf-head h1 { font-size:1.24rem;font-weight:800;margin:0;letter-spacing:.1px; }

/* Card — colored top accent per section */
.pf-card { background:var(--bg-card);border:1px solid #e7eaf3;border-radius:16px;padding:20px 22px;margin-bottom:14px;position:relative;overflow:hidden;box-shadow:0 10px 24px rgba(15,23,42,.05);transition:box-shadow .2s, border-color .2s; }
.pf-card:hover { box-shadow:0 14px 30px rgba(15,23,42,.08); border-color:#d9dff0; }
.pf-card::before { content:'';position:absolute;left:0;top:0;width:4px;height:100%;background:var(--card-accent,var(--primary)); }
.pf-card.c-party    { --card-accent: linear-gradient(180deg,#8b5cf6,#6366f1); }
.pf-card.c-party    { background:linear-gradient(135deg,#fff,#fafaff);overflow:visible; }
.pf-card.c-party.is-open { position:relative;z-index:60; }
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

.pf-party-row { display:flex; align-items:stretch; gap:10px; }
.pf-party-row .pf-party-wrap { flex:1; min-width:0; }
.pf-date-input {
    flex:0 0 168px; width:168px; height:48px; padding:0 10px;
    border:1.5px solid #dbe2ee; border-radius:11px;
    font-size:.88rem; font-weight:600; background:var(--bg-main); color:var(--text-main);
    outline:none; box-sizing:border-box;
    transition:border-color .15s, box-shadow .15s, background-color .15s;
}
.pf-date-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#fff; }

/* Party search — AJAX autocomplete */
.pf-party-wrap { position:relative; }
.pf-party-input {
    width:100%;height:48px;padding:0 14px 0 40px;border:1.5px solid #dbe2ee;border-radius:11px;
    font-size:.95rem;font-weight:600;background:var(--bg-main);color:var(--text-main);outline:none;
    box-sizing:border-box;transition:border-color .15s, box-shadow .15s, background-color .15s;
}
.pf-party-input:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.12);background:#fff; }
.pf-party-input.selected { border-color:#8b5cf6;background:linear-gradient(135deg,#fafaff,#f5f3ff); }
.pf-party-wrap .pf-party-icon {
    position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8b5cf6;font-size:1.05rem;pointer-events:none;
}
.pf-party-drop {
    display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:200;
    background:#fff;border:1.5px solid #e0e7ff;border-radius:11px;
    box-shadow:0 10px 28px rgba(15,23,42,.12);max-height:240px;overflow-y:auto;
}
.pf-party-item {
    padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:.88rem;transition:background .1s;
}
.pf-party-item:last-child { border-bottom:none; }
.pf-party-item.active, .pf-party-item:hover { background:linear-gradient(135deg,#f5f3ff,#eef2ff); }
.pf-party-item strong {
    display:block;font-weight:700;color:#1e293b;
    white-space:normal;word-break:break-word;line-height:1.35;
}
.pf-party-item .pf-party-meta { font-size:.76rem;color:#64748b;margin-top:2px;word-break:break-word; }
.pf-party-item .pf-party-due { color:#c2410c;font-weight:600; }
.pf-party-item .pf-party-credit { color:#1d4ed8;font-weight:600; }
.pf-party-item .pf-party-clear { color:#15803d;font-weight:600; }

/* Balance card */
.pf-bal { display:none;margin-top:10px;border-radius:10px;padding:12px 16px;align-items:center;justify-content:space-between; }
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
.pf-field input:focus, .pf-field select:focus, .pf-field textarea:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.12); background:#fff; }

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
    .pf-party-row { flex-direction:column; }
    .pf-date-input { flex:none; width:100%; }
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
.pf-amt-row { display:flex; align-items:stretch; gap:8px; }
.pf-amt-row input { flex:1; min-width:0; }
.pf-fill-due {
    flex-shrink:0; align-self:stretch; padding:0 12px; border-radius:9px; border:1.5px solid #c7d2fe;
    background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#3730a3; font-size:.72rem; font-weight:700;
    cursor:pointer; white-space:nowrap; transition:all .15s; display:none;
}
.pf-fill-due.show { display:inline-flex; align-items:center; gap:4px; }
.pf-fill-due:hover { border-color:#6366f1; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); }
.pf-fill-due:disabled { opacity:.45; cursor:not-allowed; }

.pf-card.c-pos { --card-accent: linear-gradient(180deg,#0ea5e9,#0284c7); }
.pf-card.c-pos { background:linear-gradient(135deg,#fff,#f7fbff); display:none; }
.pf-card.c-pos.show { display:block; }
.pf-card.c-pos .pf-sec { color:#0369a1; }
.pf-card.c-pos .pf-sec .num { background:linear-gradient(135deg,#0ea5e9,#0284c7); }
.pf-pos-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; }
.pf-pos-fillall {
    flex-shrink:0; padding:6px 10px; border-radius:8px; border:1.5px solid #bae6fd;
    background:#e0f2fe; color:#0369a1; font-size:.72rem; font-weight:700;
    cursor:pointer; white-space:nowrap;
}
.pf-pos-fillall:hover { border-color:#0ea5e9; background:#bae6fd; }
.pf-pos-empty { font-size:.82rem; color:#64748b; padding:8px 2px 2px; }
.pf-pos-row {
    border:1.5px solid #e2e8f0; border-radius:12px; padding:12px 12px 10px;
    margin-bottom:8px; background:#fff;
}
.pf-pos-row.is-paying { border-color:#7dd3fc; background:linear-gradient(135deg,#fff,#f0f9ff); }
.pf-pos-meta { display:flex; flex-wrap:wrap; align-items:baseline; gap:6px 10px; margin-bottom:8px; }
.pf-pos-no { font-weight:800; color:#0f172a; font-size:.92rem; }
.pf-pos-date { font-size:.76rem; color:#64748b; }
.pf-pos-cur { font-size:.68rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase;
    background:#e0f2fe; color:#0369a1; padding:2px 7px; border-radius:999px; }
.pf-pos-st { font-size:.68rem; font-weight:700; padding:2px 7px; border-radius:999px; }
.pf-pos-st.draft { background:#e0e7ff; color:#3730a3; }
.pf-pos-st.paid { background:#fef3c7; color:#92400e; }
.pf-pos-figs { display:flex; flex-wrap:wrap; gap:10px 16px; font-size:.76rem; color:#475569; margin-bottom:10px; }
.pf-pos-figs strong { font-family:monospace; font-weight:800; color:#0f172a; }
.pf-pos-grid { display:grid; grid-template-columns:1.1fr 1.3fr auto; gap:8px; align-items:end; }
.pf-pos-adj { display:flex; align-items:stretch; gap:4px; }
.pf-pos-sign {
    width:34px; border:1.5px solid #dbe2ee; border-radius:9px; background:#f8fafc;
    color:#64748b; font-weight:800; cursor:pointer;
}
.pf-pos-sign.is-on.plus { border-color:#86efac; background:#dcfce7; color:#166534; }
.pf-pos-sign.is-on.minus { border-color:#fca5a5; background:#fee2e2; color:#991b1b; }
.pf-pos-duebtn {
    height:40px; padding:0 10px; border-radius:9px; border:1.5px solid #bae6fd;
    background:#f0f9ff; color:#0369a1; font-size:.7rem; font-weight:700; cursor:pointer; white-space:nowrap;
}
.pf-pos-duebtn:hover { background:#e0f2fe; }
.pf-pos-total { margin-top:6px; font-size:.78rem; color:#0369a1; font-weight:700; font-family:monospace; }
.pf-pos-tt {
    margin:0 0 10px; padding:10px 12px; border-radius:10px;
    background:linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1.5px solid #7dd3fc;
}
.pf-pos-tt-title { font-size:.72rem; font-weight:800; color:#0369a1; text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.pf-pos-tt-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.pf-pos-tt-rate { margin-top:8px; font-size:.8rem; font-weight:700; color:#0c4a6e; font-family:monospace; }
.pf-pos-tt-rate:empty { display:none; margin:0; }
.pf-pos-sum { margin-top:10px; padding:10px 12px; border-radius:10px; background:#e0f2fe; border:1.5px solid #7dd3fc;
    display:flex; justify-content:space-between; align-items:center; font-size:.82rem; font-weight:700; color:#0c4a6e; }
@media (max-width: 540px) {
    .pf-pos-grid { grid-template-columns:1fr; }
    .pf-pos-duebtn { width:100%; }
    .pf-pos-tt-grid { grid-template-columns:1fr; }
}

.pf-page, .pf-page * { border-radius: 0 !important; }

</style>

<?php
    // Mode-driven theme
    $mode      = $mode ?? 'in';
    $isReceive = $mode === 'in';
    $modeTitle = $isReceive ? 'Receive Payment' : 'Make Payment';
    $modeIcon  = $isReceive ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill';
    $partyLbl  = $isReceive ? 'Customer'  : (($importPayable ?? false) ? 'Partner' : 'Party');
    $accentBg  = $isReceive ? '#059669' : '#dc2626';
    $preselectParty   = $preselectParty ?? null;
    $partySearchType  = $partySearchType ?? ($isReceive ? 'customer' : 'payment_out');
    $partyPlaceholder = $isReceive
        ? 'Type customer name — Enter to select'
        : (($importPayable ?? false)
            ? 'Import partner — pre-selected from payable'
            : 'Type party name — Enter to select');
    $linkRefId = (int) ($refId ?? 0);
    $importPayableContext = $importPayableContext ?? null;
    $paymentsListBase = $isReceive ? '?page=payments' : '?page=payments&action=out';
    $defaultAccountId = 0;
    foreach ($accounts as $accDefault) {
        $accName = trim((string) ($accDefault['name'] ?? ''));
        if (!empty($accDefault['is_default'])) {
            $defaultAccountId = (int) ($accDefault['id'] ?? 0);
            break;
        }
        if ($defaultAccountId <= 0 && strcasecmp($accName, 'Main Cash') === 0) {
            $defaultAccountId = (int) ($accDefault['id'] ?? 0);
        }
    }
?>
<div class="pf-page">
    <div class="pf-head">
        <div class="left">
            <a href="<?= htmlspecialchars($paymentsListBase) ?>" class="back"><i class="bi bi-arrow-left"></i></a>
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

    <form method="POST" action="?page=payments&action=store" id="payForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="payment_form_nonce" value="<?= htmlspecialchars($paymentFormNonce ?? '') ?>">
        <input type="hidden" name="print_mode"     id="printMode"     value="0">
        <input type="hidden" name="after_save"     id="afterSave"     value="">
        <input type="hidden" name="payment_type"   value="<?= $mode ?>">
        <!-- payment_method derived server-side from account.type unless cheque_no provided -->

        <?php if ($refData): ?>
        <div class="pf-ref">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Linked to <strong><?= htmlspecialchars($refData['invoice_no']) ?></strong> · <?= htmlspecialchars($refData['party_name']) ?> · Balance: <strong><?= APP_CURRENCY ?> <?= number_format($refData['balance'], DECIMAL_PLACES) ?></strong></span>
        </div>
        <input type="hidden" name="ref_type" value="<?= htmlspecialchars($refType) ?>">
        <input type="hidden" name="ref_id"   value="<?= (int)$refId ?>">
        <?php elseif ($importPayableContext): ?>
        <div class="pf-ref" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
            <i class="bi bi-handshake"></i>
            <span>
                <strong><?= htmlspecialchars($importPayableContext['charge_label'] ?? 'Import payable') ?></strong>
                · <?= htmlspecialchars($importPayableContext['shipment_no'] ?? '') ?>
                · <?= htmlspecialchars(trim(($importPayableContext['po_no'] ?? '') . ' / ' . ($importPayableContext['item_name'] ?? ''), ' /')) ?>
                · Pay to <strong><?= htmlspecialchars($importPayableContext['partner_name'] ?? '') ?></strong>
                <?php if ((float) ($importPayableContext['amount'] ?? 0) > 0): ?>
                · <strong><?= APP_CURRENCY ?> <?= number_format((float) $importPayableContext['amount'], DECIMAL_PLACES) ?></strong>
                <?php endif; ?>
            </span>
        </div>
        <input type="hidden" name="ref_type" value="<?= htmlspecialchars($refType) ?>">
        <input type="hidden" name="ref_id"   value="<?= $linkRefId ?>">
        <?php else: ?>
        <input type="hidden" name="ref_type" value="<?= htmlspecialchars($refType) ?>">
        <input type="hidden" name="ref_id"   value="">
        <?php endif; ?>

        <!-- Party -->
        <div class="pf-card c-party" id="partyCard">
            <div class="pf-sec"><span class="num">1</span> <?= $partyLbl ?></div>
            <div class="pf-party-row">
                <div class="pf-party-wrap">
                    <i class="bi bi-person-circle pf-party-icon" aria-hidden="true"></i>
                    <input type="text" id="partySearch"
                           class="pf-party-input<?= $preselectParty ? ' selected' : '' ?>"
                           placeholder="<?= htmlspecialchars($partyPlaceholder) ?>"
                           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                           value="<?= htmlspecialchars($preselectParty['name'] ?? '') ?>"
                           <?= ($importPayableContext && $preselectParty) ? 'readonly' : '' ?>>
                    <input type="hidden" name="party_id" id="partyIdInput"
                           value="<?= $preselectParty ? (int) $preselectParty['id'] : '' ?>" required>
                    <div class="pf-party-drop" id="partyDropdown" role="listbox" aria-label="<?= htmlspecialchars($partyLbl) ?> results"></div>
                </div>
                <input type="date" class="pf-date-input" name="date" id="payDate"
                       value="<?= date('Y-m-d') ?>" required aria-label="Payment date" title="Payment date">
            </div>
            <div class="pf-bal" id="partyBal">
                <div>
                    <div class="pf-bal-label" id="balLabel"></div>
                    <div class="pf-bal-amt"   id="balAmount"></div>
                </div>
            </div>
        </div>

        <?php if (!empty($allocatePos)): ?>
        <div class="pf-card c-pos" id="poCard">
            <div class="pf-pos-head">
                <div class="pf-sec" style="margin-bottom:0;"><span class="num"><i class="bi bi-file-earmark-text"></i></span> Purchase Orders</div>
                <button type="button" class="pf-pos-fillall" id="btnFillAllPos" style="display:none;">
                    Pay all unpaid
                </button>
            </div>
            <div id="poList"></div>
            <div class="pf-pos-sum" id="poSum" style="display:none;">
                <span id="poSumLabel">Paying 0 PO(s)</span>
                <span id="poSumAmt">0.000</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Account + Amount -->
        <div class="pf-card c-account">
            <div class="pf-sec"><span class="num">2</span> Account &amp; Amount</div>
            <div class="pf-grid2">
                <div class="pf-field">
                    <label>Account <span id="acctTypeBadge" style="font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:4px;margin-left:5px;display:none;text-transform:uppercase;letter-spacing:.4px;"></span></label>
                    <select name="account_id" id="accountSelect" required>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" data-type="<?= htmlspecialchars($acc['normalized_type'] ?? $acc['type']) ?>"<?= $defaultAccountId > 0 && (int) $acc['id'] === $defaultAccountId ? ' selected' : '' ?>><?= htmlspecialchars(BaseController::formatAccountLabel($acc)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pf-field">
                    <label>Amount</label>
                    <div class="pf-amt-row">
                        <input type="number" name="amount" id="amt1" step="0.001" min="0.001" required
                               value="<?= $refData ? $refData['balance'] : ((($preselectAmount ?? 0) > 0 ? number_format((float) $preselectAmount, 3, '.', '') : '') ?: ((float) ($importPayableContext['amount'] ?? 0) > 0 ? number_format((float) $importPayableContext['amount'], 3, '.', '') : '')) ?>" placeholder="0.000">
                        <button type="button" class="pf-fill-due" id="btnFillDue" title="Fill amount from party balance">
                            <i class="bi bi-magic"></i> Fill due
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cheque toggle (collapsed by default) -->
            <div style="margin-top:12px;">
                <a href="#" id="chequeToggle"
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
                <textarea name="notes" rows="2" placeholder="Reference, remarks..."><?= htmlspecialchars($preselectNotes ?? '') ?></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="pf-foot">
            <a href="<?= htmlspecialchars($paymentsListBase) ?>" class="pf-btn cancel">Cancel</a>
            <button type="submit" class="pf-btn save" data-print-mode="0" data-after-save="">
                <i class="bi bi-check-lg"></i> Save
            </button>
            <button type="submit" class="pf-btn print" data-print-mode="1" data-after-save=""
                title="Prints with your Default Print (A5 or Thermal) from the profile menu. Shortcut: Ctrl+S / F12">
                <i class="bi bi-printer"></i> Save &amp; Print
            </button>
        </div>
    </form>
</div>

<script>
var partyBalance = 0;
var paymentMode = <?= json_encode($mode) ?>;
var isPaymentOut = paymentMode === 'out';
var partySearchType = <?= json_encode($partySearchType) ?>;
var partyStore = { results: [] };
var partyTimer = null;
var partyHighlightIdx = -1;
var partySearchAbort = null;
var allocatePos = <?= !empty($allocatePos) ? 'true' : 'false' ?>;
var supplierPos = [];
var posAbort = null;
var posMoneyCurr = <?= json_encode(defined('APP_CURRENCY') ? APP_CURRENCY : 'KWD') ?>;

function partyIdSelected() {
    var el = document.getElementById('partyIdInput');
    return el && parseInt(el.value, 10) > 0;
}

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

function setBalLabel(label, iconClass, text) {
    label.textContent = '';
    var i = document.createElement('i');
    i.className = 'bi ' + iconClass + ' me-1';
    label.appendChild(i);
    label.appendChild(document.createTextNode(text));
}

function updateFillDueButton() {
    var btn = document.getElementById('btnFillDue');
    if (!btn) return;
    var hasPos = allocatePos && supplierPos.length > 0;
    var show = partyIdSelected() && (partyBalance > 0.001 || hasPos);
    btn.classList.toggle('show', show);
    btn.disabled = !show;
    btn.innerHTML = hasPos
        ? '<i class="bi bi-magic"></i> Fill POs'
        : '<i class="bi bi-magic"></i> Fill due';
}

function fillDueAmount() {
    if (allocatePos && supplierPos.length) {
        fillAllPoRemaining();
        return;
    }
    if (!(partyBalance > 0.001)) return;
    var amtEl = document.getElementById('amt1');
    if (!amtEl) return;
    amtEl.value = partyBalance.toFixed(3);
    amtEl.focus();
    try { amtEl.select(); } catch (e) { /* ignore */ }
}

function renderPartyBalance(bal) {
    var box    = document.getElementById('partyBal');
    var label  = document.getElementById('balLabel');
    var amount = document.getElementById('balAmount');
    var curr   = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
    partyBalance = bal;
    box.className = 'pf-bal show';
    // IN uses unified net (positive = they owe us).
    // OUT uses payable (positive = we owe them) — same as party search type=payment_out.
    if (bal > 0.001) {
        box.classList.add('owes');
        setBalLabel(
            label,
            'bi-exclamation-triangle-fill',
            isPaymentOut ? 'You Owe' : 'Customer Owes You'
        );
        amount.textContent = curr + ' ' + bal.toFixed(3);
    } else if (bal < -0.001) {
        box.classList.add('youowe');
        setBalLabel(
            label,
            'bi-info-circle-fill',
            isPaymentOut ? 'You Are Owed (credit)' : 'You Owe (advance held)'
        );
        amount.textContent = curr + ' ' + Math.abs(bal).toFixed(3);
    } else {
        box.classList.add('clear');
        setBalLabel(label, 'bi-check-circle-fill', 'Account Clear');
        amount.textContent = curr + ' 0.000';
    }
    box.classList.remove('pf-bal-pop');
    void box.offsetWidth;
    box.classList.add('pf-bal-pop');
    updateFillDueButton();
}

function showPartyBalance(balOverride) {
    var box = document.getElementById('partyBal');
    if (!partyIdSelected()) {
        box.classList.remove('show','owes','youowe','clear');
        partyBalance = 0;
        updateFillDueButton();
        return;
    }
    var partyId = document.getElementById('partyIdInput').value;

    if (balOverride !== undefined && balOverride !== null) {
        renderPartyBalance(parseFloat(balOverride) || 0);
        return;
    }

    var label  = document.getElementById('balLabel');
    var amount = document.getElementById('balAmount');
    setBalLabel(label, 'bi-hourglass-split', 'Loading...');
    amount.textContent = '';
    box.className = 'pf-bal show clear';

    fetch('?page=payments&action=partyBalance&id=' + encodeURIComponent(partyId) + '&mode=' + encodeURIComponent(paymentMode))
        .then(function(r) { return r.json(); })
        .then(function(data) { renderPartyBalance(parseFloat(data.balance) || 0); })
        .catch(function() { box.classList.remove('show'); updateFillDueButton(); });
}

function escapeHtml(text) {
    return String(text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function setPartyDropdownOpen(open) {
    var card = document.getElementById('partyCard');
    if (card) card.classList.toggle('is-open', !!open);
}

function updatePartyHighlight() {
    var drop = document.getElementById('partyDropdown');
    drop.querySelectorAll('.pf-party-item').forEach(function(el) {
        el.classList.toggle('active', parseInt(el.dataset.idx, 10) === partyHighlightIdx);
    });
    var active = drop.querySelector('.pf-party-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function partyBalanceHint(bal) {
    if (bal === null || bal === undefined || bal === '') {
        return '';
    }
    var curr = '<?= defined("APP_CURRENCY") ? APP_CURRENCY : "KWD" ?>';
    var n = parseFloat(bal) || 0;
    if (isPaymentOut) {
        if (n > 0.001) return '<span class="pf-party-due">You owe: ' + curr + ' ' + n.toFixed(3) + '</span>';
        if (n < -0.001) return '<span class="pf-party-credit">Credit: ' + curr + ' ' + Math.abs(n).toFixed(3) + '</span>';
    } else {
        if (n > 0.001) return '<span class="pf-party-due">Due: ' + curr + ' ' + n.toFixed(3) + '</span>';
        if (n < -0.001) return '<span class="pf-party-credit">Credit: ' + curr + ' ' + Math.abs(n).toFixed(3) + '</span>';
    }
    return '<span class="pf-party-clear">Account clear</span>';
}

function renderPartyDropdown(parties, keepHighlight) {
    var drop = document.getElementById('partyDropdown');
    if (!parties.length) { drop.style.display = 'none'; setPartyDropdownOpen(false); partyHighlightIdx = -1; return; }
    partyStore.results = parties;
    if (!keepHighlight) {
        partyHighlightIdx = parties.length === 1 ? 0 : -1;
    } else if (partyHighlightIdx >= parties.length) {
        partyHighlightIdx = parties.length === 1 ? 0 : -1;
    }
    drop.innerHTML = parties.map(function(p, idx) {
        var meta = [];
        if (p.type === 'freight_forwarder') meta.push('<span style="color:#0284c7;font-weight:600;">Freight forwarder</span>');
        if (p.type === 'customer' && isPaymentOut) meta.push('<span style="color:#059669;font-weight:600;">Customer</span>');
        if (p.type === 'supplier' && isPaymentOut) meta.push('<span style="color:#b45309;font-weight:600;">Supplier</span>');
        var hint = partyBalanceHint(p.balance);
        if (hint) meta.push(hint);
        return '<div class="pf-party-item' + (idx === partyHighlightIdx ? ' active' : '') + '" data-idx="' + idx + '" role="option">'
            + '<strong>' + escapeHtml(p.name) + '</strong>'
            + (meta.length ? '<div class="pf-party-meta">' + meta.join(' · ') + '</div>' : '')
            + '</div>';
    }).join('');
    drop.style.display = 'block';
    setPartyDropdownOpen(true);
}

function selectParty(party, focusAmount) {
    var input = document.getElementById('partySearch');
    input.value = party.name;
    input.title = party.name || '';
    input.classList.add('selected');
    document.getElementById('partyIdInput').value = party.id;
    document.getElementById('partyDropdown').style.display = 'none';
    setPartyDropdownOpen(false);
    partyHighlightIdx = -1;
    // Ledger Due is loaded once for the selected party (not for every dropdown row).
    showPartyBalance();
    loadSupplierPos(party.id);
    if (focusAmount !== false) {
        var amtEl = document.getElementById('amt1');
        if (amtEl) {
            setTimeout(function() {
                amtEl.focus();
                try { amtEl.select(); } catch (e) { /* some browsers */ }
            }, 30);
        }
    }
}

function searchParties(q) {
    if (partySearchAbort) partySearchAbort.abort();
    partySearchAbort = new AbortController();
    var signal = partySearchAbort.signal;
    fetch('?page=sales&action=searchParties&q=' + encodeURIComponent(q)
        + '&type=' + encodeURIComponent(partySearchType) + '&balances=0', { signal: signal })
        .then(function(r) { return r.json(); })
        .then(function(parties) {
            renderPartyDropdown(parties);
        })
        .catch(function(err) {
            if (err && err.name !== 'AbortError') {
                document.getElementById('partyDropdown').style.display = 'none';
                setPartyDropdownOpen(false);
            }
        });
}

function poMoney(n) {
    return (parseFloat(n) || 0).toFixed(3);
}

function signedAdjForRow(rowEl) {
    if (!rowEl) return 0;
    var signBtn = rowEl.querySelector('.pf-pos-sign.is-on');
    var magEl = rowEl.querySelector('.pf-pos-adj-amt');
    var mag = Math.abs(parseFloat(magEl && magEl.value) || 0);
    if (mag < 0.0005) return 0;
    return (signBtn && signBtn.classList.contains('minus')) ? -mag : mag;
}

function remainingAfterAdj(po, adj) {
    return Math.max(0, (parseFloat(po.unpaid_kwd) || 0) + (parseFloat(adj) || 0));
}

function setPoAdjFromSigned(row, adj) {
    adj = Math.round((parseFloat(adj) || 0) * 1000) / 1000;
    var mag = Math.abs(adj);
    var isMinus = adj < -0.0005;
    row.querySelectorAll('.pf-pos-sign').forEach(function(btn) {
        var sign = btn.getAttribute('data-sign');
        btn.classList.toggle('is-on', isMinus ? sign === 'minus' : sign === 'plus');
    });
    var magEl = row.querySelector('.pf-pos-adj-amt');
    if (magEl) magEl.value = poMoney(mag);
}

function formatTtRate(rate) {
    var s = rate.toFixed(6);
    s = s.replace(/0+$/, '').replace(/\.$/, '');
    return s;
}

function applyTtBank(poId) {
    var row = document.querySelector('#poList .pf-pos-row[data-po-id="' + poId + '"]');
    if (!row) return;
    var po = supplierPos.find(function(p) { return String(p.id) === String(poId); });
    if (!po) return;
    var bankEl = row.querySelector('.pf-pos-tt-bank');
    var fEl = row.querySelector('.pf-pos-tt-foreign');
    var rateEl = row.querySelector('.pf-pos-tt-rate');
    var bank = parseFloat(bankEl && bankEl.value) || 0;
    var foreign = parseFloat(fEl && fEl.value) || 0;
    if (foreign < 0.001) {
        foreign = parseFloat(po.remaining_foreign) || parseFloat(po.subtotal_foreign) || 0;
    }
    if (rateEl) {
        if (bank > 0.001 && foreign > 0.001) {
            rateEl.textContent = '1 ' + (po.currency || '') + ' = ' + formatTtRate(bank / foreign) + ' KWD';
        } else {
            rateEl.textContent = '';
        }
    }
    if (bank <= 0.001) {
        syncPoPayTotal();
        return;
    }
    var unpaid = parseFloat(po.unpaid_kwd) || 0;
    setPoAdjFromSigned(row, bank - unpaid);
    var payEl = row.querySelector('.pf-pos-pay');
    if (payEl) {
        payEl.value = poMoney(bank);
        payEl.dataset.autofill = '1';
    }
    syncPoPayTotal();
}

function syncPoPayTotal() {
    var amtEl = document.getElementById('amt1');
    var sumBox = document.getElementById('poSum');
    var sumLabel = document.getElementById('poSumLabel');
    var sumAmt = document.getElementById('poSumAmt');
    var total = 0;
    var count = 0;
    document.querySelectorAll('#poList .pf-pos-row').forEach(function(row) {
        var payEl = row.querySelector('.pf-pos-pay');
        var pay = parseFloat(payEl && payEl.value) || 0;
        var adj = signedAdjForRow(row);
        var adjHidden = row.querySelector('.pf-pos-adj-hidden');
        if (adjHidden) adjHidden.value = adj.toFixed(3);
        var po = supplierPos.find(function(p) { return String(p.id) === String(row.getAttribute('data-po-id')); });
        if (po && payEl) {
            var cap = remainingAfterAdj(po, adj);
            if (pay > cap + 0.0005) {
                payEl.value = poMoney(cap);
                pay = cap;
            }
        }
        row.classList.toggle('is-paying', pay > 0.001);
        var lineEl = row.querySelector('.pf-pos-total');
        if (lineEl) {
            lineEl.textContent = pay > 0.001
                ? ('This payment: ' + posMoneyCurr + ' ' + poMoney(pay)
                    + (Math.abs(adj) > 0.001 ? ('  ·  adj ' + (adj > 0 ? '+' : '') + poMoney(adj)) : ''))
                : '';
        }
        if (pay > 0.001) {
            total += pay;
            count += 1;
        }
    });
    total = Math.round(total * 1000) / 1000;
    if (sumBox) {
        sumBox.style.display = count > 0 ? 'flex' : 'none';
        if (sumLabel) sumLabel.textContent = 'Paying ' + count + ' PO' + (count === 1 ? '' : 's');
        if (sumAmt) sumAmt.textContent = posMoneyCurr + ' ' + poMoney(total);
    }
    if (amtEl) {
        if (count > 0) {
            amtEl.value = poMoney(total);
            amtEl.readOnly = true;
            amtEl.title = 'Total from selected purchase orders';
        } else {
            amtEl.readOnly = false;
            amtEl.title = '';
        }
    }
}

function fillPoRemaining(poId) {
    var row = document.querySelector('#poList .pf-pos-row[data-po-id="' + poId + '"]');
    if (!row) return;
    var po = supplierPos.find(function(p) { return String(p.id) === String(poId); });
    if (!po) return;
    if (po.kwd_unknown) return;
    var adj = signedAdjForRow(row);
    var payEl = row.querySelector('.pf-pos-pay');
    if (payEl) {
        payEl.value = poMoney(remainingAfterAdj(po, adj));
        payEl.dataset.autofill = '1';
    }
    syncPoPayTotal();
}

function fillAllPoRemaining() {
    supplierPos.forEach(function(po) { fillPoRemaining(po.id); });
}

function setPoAdjSign(poId, sign) {
    var row = document.querySelector('#poList .pf-pos-row[data-po-id="' + poId + '"]');
    if (!row) return;
    row.querySelectorAll('.pf-pos-sign').forEach(function(btn) {
        btn.classList.toggle('is-on', btn.getAttribute('data-sign') === sign);
    });
    var payEl = row.querySelector('.pf-pos-pay');
    if (payEl && payEl.dataset.autofill === '1') {
        fillPoRemaining(poId);
        return;
    }
    syncPoPayTotal();
}

function onPoAdjMagChange(poId) {
    var row = document.querySelector('#poList .pf-pos-row[data-po-id="' + poId + '"]');
    if (!row) return;
    var payEl = row.querySelector('.pf-pos-pay');
    if (payEl && (payEl.dataset.autofill === '1' || payEl.value === '')) {
        fillPoRemaining(poId);
        return;
    }
    syncPoPayTotal();
}

function clearSupplierPos() {
    supplierPos = [];
    if (posAbort) {
        try { posAbort.abort(); } catch (e) { /* ignore */ }
        posAbort = null;
    }
    var card = document.getElementById('poCard');
    var list = document.getElementById('poList');
    var fillAll = document.getElementById('btnFillAllPos');
    var sumBox = document.getElementById('poSum');
    if (card) card.classList.remove('show');
    if (list) list.innerHTML = '';
    if (fillAll) fillAll.style.display = 'none';
    if (sumBox) sumBox.style.display = 'none';
    var amtEl = document.getElementById('amt1');
    if (amtEl) {
        amtEl.readOnly = false;
        amtEl.title = '';
    }
    updateFillDueButton();
}

function renderSupplierPos(orders) {
    var card = document.getElementById('poCard');
    var list = document.getElementById('poList');
    var fillAll = document.getElementById('btnFillAllPos');
    if (!card || !list) return;
    supplierPos = orders || [];
    card.classList.add('show');
    if (!supplierPos.length) {
        list.innerHTML = '<div class="pf-pos-empty">No unpaid purchase orders for this party. Enter an amount below for a normal Payment Out.</div>';
        if (fillAll) fillAll.style.display = 'none';
        syncPoPayTotal();
        updateFillDueButton();
        return;
    }
    if (fillAll) fillAll.style.display = 'inline-flex';
    list.innerHTML = supplierPos.map(function(po) {
        var st = po.status === 'paid' ? 'paid' : 'draft';
        var stLabel = st === 'paid' ? 'Partial / awaiting goods' : 'Draft';
        var foreign = '';
        if (po.currency && po.currency !== 'KWD' && (parseFloat(po.subtotal_foreign) || 0) > 0) {
            foreign = '<span>' + escapeHtml(po.currency) + ' <strong>' + poMoney(po.subtotal_foreign) + '</strong></span>';
        }
        var dateLabel = '';
        if (po.date) {
            var d = new Date(po.date + 'T00:00:00');
            dateLabel = isNaN(d.getTime()) ? po.date : d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
        }
        var kwdUnknown = !!po.kwd_unknown;
        var remF = parseFloat(po.remaining_foreign) || parseFloat(po.subtotal_foreign) || 0;
        var ttBox = '';
        if (po.currency && po.currency !== 'KWD' && remF > 0.001) {
            ttBox = '<div class="pf-pos-tt">'
                + '<div class="pf-pos-tt-title"><i class="bi bi-currency-exchange"></i> TT rate — type what the bank took</div>'
                + '<div class="pf-pos-tt-grid">'
                + '<div class="pf-field"><label>Foreign this TT (' + escapeHtml(po.currency) + ')</label>'
                + '<input type="number" class="pf-pos-tt-foreign" step="0.001" min="0" value="' + poMoney(remF) + '" inputmode="decimal">'
                + '</div>'
                + '<div class="pf-field"><label>Bank took (KWD)</label>'
                + '<input type="number" class="pf-pos-tt-bank" step="0.001" min="0" placeholder="Exact bank KWD" inputmode="decimal">'
                + '</div></div>'
                + '<div class="pf-pos-tt-rate"></div>'
                + '</div>';
        }
        return '<div class="pf-pos-row" data-po-id="' + po.id + '">'
            + '<div class="pf-pos-meta">'
            + '<span class="pf-pos-no">' + escapeHtml(po.po_no) + '</span>'
            + (dateLabel ? '<span class="pf-pos-date">' + escapeHtml(dateLabel) + '</span>' : '')
            + '<span class="pf-pos-cur">' + escapeHtml(po.currency || 'KWD') + '</span>'
            + '<span class="pf-pos-st ' + st + '">' + stLabel + '</span>'
            + '</div>'
            + '<div class="pf-pos-figs">'
            + '<span>Total <strong>' + (kwdUnknown ? '—' : poMoney(po.total_kwd)) + '</strong></span>'
            + '<span>Paid <strong>' + poMoney(po.paid_kwd) + '</strong></span>'
            + '<span>Unpaid <strong>' + (kwdUnknown ? '—' : poMoney(po.unpaid_kwd)) + '</strong></span>'
            + foreign
            + '</div>'
            + ttBox
            + '<div class="pf-pos-grid">'
            + '<div class="pf-field"><label>Pay amount</label>'
            + '<input type="number" class="pf-pos-pay" name="po_pay[' + po.id + '][amount]" step="0.001" min="0" placeholder="0.000" inputmode="decimal">'
            + '</div>'
            + '<div class="pf-field"><label>Bank adj. (+ / −)</label>'
            + '<div class="pf-pos-adj">'
            + '<button type="button" class="pf-pos-sign plus is-on" data-sign="plus" data-po-id="' + po.id + '" title="Add (bank fee)">+</button>'
            + '<button type="button" class="pf-pos-sign minus" data-sign="minus" data-po-id="' + po.id + '" title="Subtract (rounding / discount)">−</button>'
            + '<input type="number" class="pf-pos-adj-amt" step="0.001" min="0" value="0.000" inputmode="decimal" title="Adjustment amount">'
            + '<input type="hidden" class="pf-pos-adj-hidden" name="po_pay[' + po.id + '][adjustment]" value="0.000">'
            + '</div></div>'
            + (kwdUnknown ? '' : '<button type="button" class="pf-pos-duebtn" data-fill-po="' + po.id + '">Pay unpaid</button>')
            + '</div>'
            + '<div class="pf-pos-total"></div>'
            + '</div>';
    }).join('');
    syncPoPayTotal();
    updateFillDueButton();
}

function loadSupplierPos(partyId) {
    if (!allocatePos) return;
    var id = parseInt(partyId, 10) || 0;
    if (id <= 0) {
        clearSupplierPos();
        return;
    }
    if (posAbort) {
        try { posAbort.abort(); } catch (e) { /* ignore */ }
    }
    posAbort = new AbortController();
    var card = document.getElementById('poCard');
    var list = document.getElementById('poList');
    if (card) card.classList.add('show');
    if (list) list.innerHTML = '<div class="pf-pos-empty">Loading purchase orders…</div>';
    fetch('?page=payments&action=supplierOpenPos&id=' + encodeURIComponent(id), { signal: posAbort.signal })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderSupplierPos((data && data.orders) ? data.orders : []);
        })
        .catch(function(err) {
            if (err && err.name === 'AbortError') return;
            clearSupplierPos();
        });
}

function initPartySearch() {
    var input = document.getElementById('partySearch');
    var drop  = document.getElementById('partyDropdown');
    if (!input || !drop) return;

    input.addEventListener('input', function() {
        input.classList.remove('selected');
        document.getElementById('partyIdInput').value = '';
        document.getElementById('partyBal').classList.remove('show','owes','youowe','clear');
        partyBalance = 0;
        updateFillDueButton();
        clearSupplierPos();
        clearTimeout(partyTimer);
        var q = input.value.trim();
        if (q.length < 1) {
            drop.style.display = 'none';
            setPartyDropdownOpen(false);
            partyHighlightIdx = -1;
            return;
        }
        partyTimer = setTimeout(function() { searchParties(q); }, 300);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' && !e.shiftKey && partyIdSelected()) {
            e.preventDefault();
            var amtEl = document.getElementById('amt1');
            if (amtEl) amtEl.focus();
            return;
        }
        var visible = drop.style.display !== 'none';
        var parties = partyStore.results || [];
        if (!visible || !parties.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            partyHighlightIdx = partyHighlightIdx < parties.length - 1 ? partyHighlightIdx + 1 : 0;
            updatePartyHighlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            partyHighlightIdx = partyHighlightIdx > 0 ? partyHighlightIdx - 1 : parties.length - 1;
            updatePartyHighlight();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var idx = partyHighlightIdx >= 0 ? partyHighlightIdx : 0;
            selectParty(parties[idx]);
        } else if (e.key === 'Escape') {
            drop.style.display = 'none';
            setPartyDropdownOpen(false);
            partyHighlightIdx = -1;
        }
    });

    drop.addEventListener('mousedown', function(e) {
        var item = e.target.closest('.pf-party-item');
        if (!item) return;
        e.preventDefault();
        selectParty(partyStore.results[parseInt(item.dataset.idx, 10)]);
    });

    drop.addEventListener('mouseover', function(e) {
        var item = e.target.closest('.pf-party-item');
        if (!item) return;
        partyHighlightIdx = parseInt(item.dataset.idx, 10);
        updatePartyHighlight();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.pf-party-wrap')) {
            drop.style.display = 'none';
            setPartyDropdownOpen(false);
            partyHighlightIdx = -1;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var payForm = document.getElementById('payForm');
    var accountSelect = document.getElementById('accountSelect');
    var chequeToggle = document.getElementById('chequeToggle');
    var btnFillDue = document.getElementById('btnFillDue');

    if (accountSelect) {
        accountSelect.addEventListener('change', onAccountChange);
    }
    if (chequeToggle) {
        chequeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCheque();
        });
    }

    if (btnFillDue) {
        btnFillDue.addEventListener('click', fillDueAmount);
    }

    var btnFillAllPos = document.getElementById('btnFillAllPos');
    if (btnFillAllPos) {
        btnFillAllPos.addEventListener('click', fillAllPoRemaining);
    }
    var poList = document.getElementById('poList');
    if (poList) {
        poList.addEventListener('click', function(e) {
            var signBtn = e.target.closest('.pf-pos-sign');
            if (signBtn) {
                e.preventDefault();
                setPoAdjSign(signBtn.getAttribute('data-po-id'), signBtn.getAttribute('data-sign'));
                return;
            }
            var fillBtn = e.target.closest('[data-fill-po]');
            if (fillBtn) {
                e.preventDefault();
                fillPoRemaining(fillBtn.getAttribute('data-fill-po'));
            }
        });
        poList.addEventListener('input', function(e) {
            var row = e.target.closest('.pf-pos-row');
            if (!row) return;
            var poId = row.getAttribute('data-po-id');
            if (e.target.classList.contains('pf-pos-pay')) {
                e.target.dataset.autofill = '0';
                syncPoPayTotal();
                return;
            }
            if (e.target.classList.contains('pf-pos-tt-bank') || e.target.classList.contains('pf-pos-tt-foreign')) {
                applyTtBank(poId);
                return;
            }
            if (e.target.classList.contains('pf-pos-adj-amt')) {
                onPoAdjMagChange(poId);
            }
        });
    }

    if (payForm) {
        payForm.querySelectorAll('button[type="submit"][data-print-mode]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var pm = document.getElementById('printMode');
                if (pm) pm.value = btn.getAttribute('data-print-mode') || '0';
                var as = document.getElementById('afterSave');
                if (as) as.value = btn.getAttribute('data-after-save') || '';
            });
        });

        payForm.addEventListener('submit', function(e) {
            if (!partyIdSelected()) {
                e.preventDefault();
                document.getElementById('partySearch').focus();
                return;
            }
            if (allocatePos) {
                syncPoPayTotal();
            }
            if (payForm.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }
            payForm.dataset.submitting = '1';
            payForm.querySelectorAll('button[type="submit"]').forEach(function(btn) {
                btn.disabled = true;
            });
        });

        payForm.addEventListener('keydown', function(e) {
            if (((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) || e.key === 'F12') {
                e.preventDefault();
                var pm = document.getElementById('printMode');
                if (pm) pm.value = '1';
                var as = document.getElementById('afterSave');
                if (as) as.value = '';
                if (typeof payForm.requestSubmit === 'function') {
                    payForm.requestSubmit();
                } else {
                    var pb = payForm.querySelector('button[type="submit"][data-print-mode="1"]');
                    if (pb) pb.click();
                }
            }
        }, true);
    }

    // Page-entry reveal animation (staggered)
    var revealTargets = document.querySelectorAll('.pf-ref, .pf-card, .pf-foot');
    revealTargets.forEach(function(el, i) {
        el.classList.add('pf-anim-enter');
        setTimeout(function() { el.classList.add('is-visible'); }, 30 + (i * 55));
    });

    onAccountChange();
    initPartySearch();
    var partySearchEl = document.getElementById('partySearch');
    var preselectBal = <?= isset($preselectBalance) && $preselectBalance !== null ? json_encode((float) $preselectBalance) : 'null' ?>;
    if (partyIdSelected()) {
        if (partySearchEl && partySearchEl.value) partySearchEl.title = partySearchEl.value;
        showPartyBalance(preselectBal);
        loadSupplierPos(document.getElementById('partyIdInput').value);
    }
    updateFillDueButton();
    setTimeout(function() {
        if (partySearchEl) partySearchEl.focus();
    }, 120);
});

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;

    var partyDrop = document.getElementById('partyDropdown');
    if (partyDrop && partyDrop.style.display !== 'none') {
        e.preventDefault();
        e.stopPropagation();
        partyDrop.style.display = 'none';
        setPartyDropdownOpen(false);
        partyHighlightIdx = -1;
        return;
    }

    window.location.href = <?= json_encode($paymentsListBase) ?>;
}, true);
</script>
