<?php
$partyType = (string) ($party['type'] ?? '');
$isSupplierSide = in_array($partyType, ['supplier', 'both', 'freight_forwarder'], true);
$isCustomerSideView = in_array($partyType, ['customer', 'both'], true);

$typeVisual = match ($partyType) {
    'customer'          => ['bg' => 'rgba(16,185,129,0.14)', 'fg' => '#059669', 'icon' => 'bi-person-fill', 'wash' => 'linear-gradient(135deg, #ecfdf5 0%, #ffffff 62%)'],
    'supplier'          => ['bg' => 'rgba(99,102,241,0.14)',  'fg' => '#4338ca', 'icon' => 'bi-truck',        'wash' => 'linear-gradient(135deg, #eef2ff 0%, #ffffff 62%)'],
    'freight_forwarder' => ['bg' => 'rgba(14,165,233,0.14)',  'fg' => '#0284c7', 'icon' => 'bi-globe2',       'wash' => 'linear-gradient(135deg, #e0f2fe 0%, #ffffff 62%)'],
    default             => ['bg' => 'rgba(245,158,11,0.16)',  'fg' => '#b45309', 'icon' => 'bi-people',       'wash' => 'linear-gradient(135deg, #fffbeb 0%, #ffffff 62%)'],
};

$listType = match ($partyType) {
    'supplier'          => 'supplier',
    'freight_forwarder' => 'freight_forwarder',
    'customer'          => 'customer',
    'both'              => 'both',
    default             => 'all',
};

$netBal   = (float) ($party['net_balance'] ?? 0);
$display  = Party::displayBalanceDue($party, $netBal);
$shownBal = (float) $display['amount'];
$opening  = (float) ($party['opening_balance'] ?? 0);
$salesN   = (int) ($linkedSalesCount ?? 0);
$purchN   = (int) ($linkedPurchasesCount ?? 0);

$nameParts = preg_split('/\s+/', trim((string) ($party['name'] ?? ''))) ?: [];
$initials  = strtoupper(substr((string) ($nameParts[0] ?? 'P'), 0, 1) . substr((string) ($nameParts[1] ?? ''), 0, 1));
if ($initials === '') {
    $initials = 'P';
}

$phone  = trim((string) ($party['phone'] ?? ''));
$phone2 = trim((string) ($party['phone2'] ?? ''));
$email  = trim((string) ($party['email'] ?? ''));
$city   = trim((string) ($party['city'] ?? ''));
$country = trim((string) ($party['country'] ?? ''));
$address = trim((string) ($party['address'] ?? ''));
$contact = trim((string) ($party['contact_person'] ?? ''));
$placeParts = array_values(array_filter([$city, $country], static fn($v) => $v !== ''));
$place = $placeParts !== [] ? implode(', ', $placeParts) : '';

$tlExpiry = trim((string) ($party['trade_license_expires_on'] ?? ''));
$tlFile   = trim((string) ($party['trade_license_file'] ?? ''));
$showTradeLicense = $isSupplierSide && ($tlExpiry !== '' || $tlFile !== '');
$tlExpired = $tlExpiry !== '' && $tlExpiry < date('Y-m-d');

$canEditParty = in_array($partyType, ['supplier', 'freight_forwarder'], true)
    ? Auth::can('suppliers', 'edit')
    : Auth::canAny(['parties', 'customers'], 'edit');

$allocationGapAbs = abs((float) ($saleAllocationGap ?? 0));
$showAllocationRepair = Auth::isAdmin() && in_array($partyType, ['customer', 'both', 'supplier'], true);
$allocationNeedsRepair = $showAllocationRepair && $allocationGapAbs > 0.001;

if ($display['perspective'] === 'payable') {
    if ($shownBal > 0.001) {
        $balTone = 'due';
        $balTitle = APP_CURRENCY . ' ' . number_format($shownBal, DECIMAL_PLACES);
        $balHint = 'Amount owed to supplier';
    } elseif ($shownBal < -0.001) {
        $balTone = 'credit';
        $balTitle = '-' . APP_CURRENCY . ' ' . number_format(abs($shownBal), DECIMAL_PLACES);
        $balHint = 'Overpaid to supplier';
    } else {
        $balTone = 'clear';
        $balTitle = 'Clear';
        $balHint = 'Nothing outstanding on this branch';
    }
} elseif ($shownBal > 0.001) {
    $balTone = 'due';
    $balTitle = APP_CURRENCY . ' ' . number_format($shownBal, DECIMAL_PLACES);
    $balHint = 'They owe you';
} elseif ($shownBal < -0.001) {
    $balTone = 'credit';
    $balTitle = '-' . APP_CURRENCY . ' ' . number_format(abs($shownBal), DECIMAL_PLACES);
    $balHint = 'You owe them';
} else {
    $balTone = 'clear';
    $balTitle = 'Clear';
    $balHint = 'Nothing outstanding on this branch';
}

$lastActivity = '';
if (!empty($ledger) && is_array($ledger)) {
    $lastRow = $ledger[array_key_last($ledger)];
    if (!empty($lastRow['date'])) {
        $ts = strtotime((string) $lastRow['date']);
        if ($ts) {
            $lastActivity = date('d M Y', $ts);
        }
    }
}

$phoneHref = $phone !== '' ? preg_replace('/[^\d+]/', '', $phone) : '';
$phone2Href = $phone2 !== '' ? preg_replace('/[^\d+]/', '', $phone2) : '';

$ledgerHref = static function (array $row): string {
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return '';
    }
    return match ($row['type'] ?? '') {
        'sale'       => Auth::can('sales', 'view') ? '?page=sales&action=detail&id=' . $id : '',
        'purchase'   => Auth::can('purchases', 'view') ? '?page=purchases&action=detail&id=' . $id : '',
        'payment', 'po_advance', 'discount'
                     => (Auth::can('payments', 'view') || Auth::can('payments_out', 'view'))
                        ? '?page=payments&action=detail&id=' . $id : '',
        'return'     => Auth::can('returns', 'view') ? '?page=returns&action=detail&id=' . $id : '',
        'dump'       => Auth::can('dumps', 'view') ? '?page=dumps&action=view&id=' . $id : '',
        default      => '',
    };
};
?>
<style>
.pv-hero {
    --pv-accent: <?= htmlspecialchars($typeVisual['fg']) ?>;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 1.15rem;
    box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 10px 28px rgba(15,23,42,0.045);
}
.pv-hero-main {
    position: relative;
    padding: 1rem 1.25rem 1.05rem 1.4rem;
    min-width: 0;
}
.pv-hero-main::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 5px;
    background: var(--pv-accent);
}
.pv-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.pv-who { display: flex; align-items: center; gap: 12px; min-width: 0; }
.pv-avatar {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.95rem; letter-spacing: -0.03em;
    background: <?= $typeVisual['bg'] ?>;
    color: var(--pv-accent);
    box-shadow: inset 0 0 0 1px rgba(15,23,42,0.04);
}
.pv-name { font-size: 1.28rem; font-weight: 800; letter-spacing: -0.03em; color: var(--text-main); line-height: 1.2; }
.pv-sub { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin-top: 5px; font-size: 0.8rem; color: #64748b; }
.pv-acc {
    font-size: 0.82rem; font-weight: 800; color: var(--pv-accent);
    background: <?= $typeVisual['bg'] ?>;
    padding: 1px 8px; border-radius: 6px; letter-spacing: 1.2px;
}
.pv-copy { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0 2px; line-height: 1; }
.pv-copy:hover { color: var(--pv-accent); }
.pv-type {
    display: inline-flex; align-items: center; gap: 4px;
    background: <?= $typeVisual['bg'] ?>; color: var(--pv-accent);
    border-radius: 999px; padding: 3px 9px; font-size: 0.72rem; font-weight: 700;
}
.pv-actions { display: flex; flex-wrap: wrap; gap: 7px; }
.pv-pills {
    display: flex; flex-wrap: wrap; gap: 8px;
    margin-top: 14px; padding-top: 13px;
    border-top: 1px dashed #e2e8f0;
}
.pv-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 11px; border-radius: 11px;
    background: #f8fafc; border: 1px solid #eef2f7;
    font-size: 0.8rem; font-weight: 600; color: #0f172a;
    text-decoration: none; max-width: 100%;
}
.pv-pill:hover { color: #0f172a; background: #f1f5f9; }
.pv-pill i { color: var(--pv-accent); font-size: 0.92rem; }
.pv-pill span { color: #64748b; font-weight: 600; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.04em; }
.pv-stamp {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: 1.15rem 1.1rem 1rem;
    border-left: 1px solid rgba(15,23,42,0.06);
    background-image: radial-gradient(rgba(15,23,42,0.05) 1px, transparent 1px);
    background-size: 11px 11px;
}
.pv-stamp.tone-clear { background-color: #ecfdf5; }
.pv-stamp.tone-due { background-color: #fef2f2; }
.pv-stamp.tone-credit { background-color: #eef2ff; }
.pv-stamp-kicker { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; margin: 0 0 6px; }
.pv-stamp.tone-clear .pv-stamp-kicker { color: #059669; }
.pv-stamp.tone-due .pv-stamp-kicker { color: #dc2626; }
.pv-stamp.tone-credit .pv-stamp-kicker { color: #4338ca; }
.pv-stamp-amount { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.04em; line-height: 1.1; margin: 0; }
.pv-stamp.tone-clear .pv-stamp-amount { color: #047857; }
.pv-stamp.tone-due .pv-stamp-amount { color: #b91c1c; }
.pv-stamp.tone-credit .pv-stamp-amount { color: #3730a3; }
.pv-stamp-hint { font-size: 0.72rem; font-weight: 600; margin: 6px 0 0; max-width: 11.5rem; line-height: 1.35; }
.pv-stamp.tone-clear .pv-stamp-hint { color: #047857; }
.pv-stamp.tone-due .pv-stamp-hint { color: #991b1b; }
.pv-stamp.tone-credit .pv-stamp-hint { color: #4338ca; }
.pv-stamp-meta { margin-top: 10px; font-size: 0.72rem; font-weight: 600; color: #475569; }
.pv-ledger-ref { font-weight: 700; color: var(--primary); text-decoration: none; }
.pv-ledger-ref:hover { text-decoration: underline; }
.pv-admin { border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; }
.pv-admin summary {
    cursor: pointer; list-style: none; font-weight: 700; font-size: 0.82rem; color: #64748b;
    padding: 0.7rem 1rem;
}
.pv-admin summary::-webkit-details-marker { display: none; }
.pv-admin summary::before { content: '▸ '; color: #94a3b8; }
.pv-admin[open] summary::before { content: '▾ '; }
.pv-admin .pv-admin-body { padding: 0 1rem 1rem; }
.pv-month {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    margin-bottom: 1.15rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15,23,42,0.04);
}
.pv-month-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    padding: 10px 16px;
    background: linear-gradient(135deg, #f8faff, #f1f5f9);
    border-bottom: 1px solid #eef2f7;
}
.pv-month-head strong { font-size: 0.82rem; font-weight: 800; color: #334155; letter-spacing: -0.01em; }
.pv-month-head span { font-size: 0.72rem; font-weight: 600; color: #64748b; }
.pv-month-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 0;
}
.pv-month-metric { padding: 14px 16px 16px; min-width: 0; }
.pv-month-metric + .pv-month-metric { border-left: 1px solid #eef2f7; }
.pv-month-kicker {
    font-size: 0.65rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase;
    color: #64748b; margin-bottom: 10px;
}
.pv-month-pair { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.pv-month-stat .lbl { display: block; font-size: 0.68rem; font-weight: 600; color: #94a3b8; margin-bottom: 2px; }
.pv-month-stat .amt { font-size: 1.02rem; font-weight: 800; letter-spacing: -0.03em; color: #0f172a; line-height: 1.2; }
.pv-month-stat .amt.is-this { color: #4338ca; }
.pv-month-delta {
    display: inline-block; margin-top: 4px; font-size: 0.7rem; font-weight: 700;
    padding: 1px 7px; border-radius: 999px;
}
.pv-month-delta.up { background: #ecfdf5; color: #047857; }
.pv-month-delta.down { background: #fef2f2; color: #b91c1c; }
.pv-month-delta.flat { background: #f1f5f9; color: #64748b; }
.pv-month-bars { margin-top: 12px; display: flex; flex-direction: column; gap: 5px; }
.pv-month-bar-row { display: flex; align-items: center; gap: 8px; }
.pv-month-bar-row .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.pv-month-bar-row .dot.prev { background: #cbd5e1; }
.pv-month-bar-row .dot.last { background: #64748b; }
.pv-month-bar-row .dot.this { background: #6366f1; }
.pv-month-track { flex: 1; height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.pv-month-fill { height: 100%; border-radius: 999px; min-width: 0; }
.pv-month-fill.prev { background: #e2e8f0; }
.pv-month-fill.last { background: #94a3b8; }
.pv-month-fill.this { background: linear-gradient(90deg, #818cf8, #4f46e5); }
@media (max-width: 767.98px) {
    .pv-hero { grid-template-columns: 1fr; }
    .pv-stamp { border-left: none; border-top: 1px solid rgba(15,23,42,0.06); flex-direction: row; flex-wrap: wrap; gap: 8px 14px; justify-content: flex-start; text-align: left; padding: 0.9rem 1.1rem; }
    .pv-stamp-amount { font-size: 1.3rem; }
    .pv-stamp-hint { max-width: none; margin-top: 0; }
    .pv-month-grid { grid-template-columns: 1fr; }
    .pv-month-metric + .pv-month-metric { border-left: none; border-top: 1px solid #eef2f7; }
    .pv-month-stat .amt { font-size: 0.9rem; }
}
</style>

<div class="pv-hero">
    <div class="pv-hero-main">
        <div class="pv-hero-top">
            <div class="pv-who">
                <a href="?page=parties&type=<?= urlencode($listType) ?>" class="btn btn-sm btn-outline-secondary" title="Back to list"><i class="bi bi-arrow-left"></i></a>
                <div class="pv-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="page-title pv-name mb-0"><?= htmlspecialchars($party['name']) ?></h1>
                        <span class="pv-type"><i class="bi <?= $typeVisual['icon'] ?>"></i> <?= htmlspecialchars(Party::typeLabel($partyType)) ?></span>
                        <?php if ($isCustomerSideView): ?>
                        <?php $kindLabel = Party::customerKindLabel($party['customer_kind'] ?? null); ?>
                        <span class="badge" style="border-radius:6px;background:<?= Party::isRetailCustomer($party['customer_kind'] ?? null) ? 'rgba(99,102,241,0.14);color:#4338ca' : 'rgba(245,158,11,0.16);color:#b45309' ?>;">
                            <?= htmlspecialchars($kindLabel) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ((int) ($party['is_active'] ?? 1) !== 1): ?>
                        <span class="badge badge-draft">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <div class="pv-sub">
                        <?php if (!empty($party['party_code'])): ?>
                        <span class="pv-acc" id="partyAccNo"><?= htmlspecialchars($party['party_code']) ?></span>
                        <button type="button" class="pv-copy" title="Copy account number" onclick="(function(b){navigator.clipboard.writeText(document.getElementById('partyAccNo').textContent.trim());b.innerHTML='<i class=\'bi bi-check-lg\'></i>';setTimeout(function(){b.innerHTML='<i class=\'bi bi-copy\'></i>';},1500);})(this)">
                            <i class="bi bi-copy"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($contact !== ''): ?>
                        <span>· <?= htmlspecialchars($contact) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="pv-actions">
                <?php if ($isCustomerSideView && Auth::can('payments', 'add')): ?>
                <a href="?page=payments&action=receive&party_id=<?= (int) $party['id'] ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-cash-coin me-1"></i> Receive
                </a>
                <?php endif; ?>
                <?php if (Auth::can('payments_out', 'add')): ?>
                <a href="?page=payments&action=pay&party_id=<?= (int) $party['id'] ?>" class="btn btn-sm <?= $isCustomerSideView ? 'btn-outline-danger' : 'btn-danger' ?>">
                    <i class="bi bi-arrow-up-right me-1"></i> Pay
                </a>
                <?php endif; ?>
                <?php if ($isCustomerSideView): ?>
                <a href="?page=parties&action=agentStatement&id=<?= (int) $party['id'] ?>" class="btn btn-sm btn-outline-success" title="Agent statement">
                    <i class="bi bi-person-lines-fill me-1"></i> Statement
                </a>
                <?php if (!empty($party['statement_token'])): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" title="Copy field statement link"
                    onclick="(function(btn){navigator.clipboard.writeText(window.location.origin+'/s/'+encodeURIComponent('<?= htmlspecialchars((string) $party['statement_token'], ENT_QUOTES) ?>'));var o=btn.innerHTML;btn.innerHTML='<i class=\'bi bi-check-lg me-1\'></i> Copied';setTimeout(function(){btn.innerHTML=o;},1600);})(this)">
                    <i class="bi bi-link-45deg me-1"></i> Field link
                </button>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($canEditParty): ?>
                <a href="?page=parties&action=edit&id=<?= (int) $party['id'] ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="pv-pills">
            <?php if ($phone !== ''): ?>
            <a class="pv-pill" href="tel:<?= htmlspecialchars($phoneHref) ?>"><i class="bi bi-telephone"></i><?= htmlspecialchars($phone) ?></a>
            <?php endif; ?>
            <?php if ($phone2 !== ''): ?>
            <a class="pv-pill" href="tel:<?= htmlspecialchars($phone2Href) ?>"><i class="bi bi-telephone-plus"></i><?= htmlspecialchars($phone2) ?></a>
            <?php endif; ?>
            <?php if ($place !== ''): ?>
            <span class="pv-pill"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($place) ?></span>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
            <a class="pv-pill" href="mailto:<?= htmlspecialchars($email) ?>"><i class="bi bi-envelope"></i><?= htmlspecialchars($email) ?></a>
            <?php endif; ?>
            <?php if ($address !== ''): ?>
            <span class="pv-pill"><i class="bi bi-signpost-2"></i><?= htmlspecialchars($address) ?></span>
            <?php endif; ?>
            <?php if ($isCustomerSideView): ?>
            <span class="pv-pill"><i class="bi bi-receipt"></i><span>Sales</span> <?= $salesN ?></span>
            <?php endif; ?>
            <?php if ($isSupplierSide): ?>
            <span class="pv-pill"><i class="bi bi-box-seam"></i><span>Purchases</span> <?= $purchN ?></span>
            <?php endif; ?>
            <?php if ($lastActivity !== ''): ?>
            <span class="pv-pill"><i class="bi bi-clock-history"></i><span>Last</span> <?= htmlspecialchars($lastActivity) ?></span>
            <?php endif; ?>
            <?php if ($showTradeLicense): ?>
            <span class="pv-pill" style="<?= $tlExpired ? 'color:#b91c1c;border-color:#fecaca;background:#fef2f2;' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <?php if ($tlExpiry !== ''): ?>
                <?= $tlExpired ? 'License expired' : 'License' ?> <?= htmlspecialchars(date('d M Y', strtotime($tlExpiry))) ?>
                <?php else: ?>
                Trade license
                <?php endif; ?>
                <?php if ($tlFile !== ''): ?>
                <a href="?page=parties&action=downloadTradeLicense&id=<?= (int) $party['id'] ?>" class="ms-1 fw-bold" target="_blank" rel="noopener">File</a>
                <?php endif; ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($cancelledSalesCount)): ?>
            <span class="pv-pill"><i class="bi bi-slash-circle"></i><span>Voided</span> <?= (int) $cancelledSalesCount ?></span>
            <?php endif; ?>
        </div>
    </div>

    <aside class="pv-stamp tone-<?= $balTone ?>">
        <p class="pv-stamp-kicker">Net balance</p>
        <p class="pv-stamp-amount">
            <?php if ($balTone === 'clear'): ?><i class="bi bi-check-circle-fill" style="font-size:1.15rem;vertical-align:-2px;"></i> <?php endif; ?>
            <?= htmlspecialchars($balTitle) ?>
        </p>
        <p class="pv-stamp-hint"><?= htmlspecialchars($balHint) ?></p>
        <div class="pv-stamp-meta">Opening <?= APP_CURRENCY ?> <?= number_format($opening, DECIMAL_PLACES) ?></div>
    </aside>
</div>

<?php if ($isCustomerSideView && !empty($partyMonthCompare)): ?>
<?php
    $pmc = $partyMonthCompare;
    $pmcMoney = static function (float $v): string {
        return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
    };
    $pmcDelta = static function (float $thisAmt, float $lastAmt): array {
        if ($lastAmt < 0.001 && $thisAmt < 0.001) {
            return ['class' => 'flat', 'text' => 'Same'];
        }
        if ($lastAmt < 0.001) {
            return ['class' => 'up', 'text' => 'New'];
        }
        $pct = (($thisAmt - $lastAmt) / $lastAmt) * 100;
        if (abs($pct) < 0.5) {
            return ['class' => 'flat', 'text' => 'Same'];
        }
        $sign = $pct > 0 ? '+' : '';
        return [
            'class' => $pct > 0 ? 'up' : 'down',
            'text'  => $sign . number_format($pct, 0) . '%',
        ];
    };
    $pmcBar = static function (float $amt, float $max): string {
        if ($amt < 0.001 || $max < 0.001) {
            return '0%';
        }
        return max(4, min(100, round(($amt / $max) * 100))) . '%';
    };
    $salesDelta = $pmcDelta((float) $pmc['sales']['this_month'], (float) $pmc['sales']['last_month']);
    $recvDelta  = $pmcDelta((float) $pmc['receipts']['this_month'], (float) $pmc['receipts']['last_month']);
    $salesMax   = max((float) $pmc['sales']['this_month'], (float) $pmc['sales']['last_month'], (float) ($pmc['sales']['prev_month'] ?? 0));
    $recvMax    = max((float) $pmc['receipts']['this_month'], (float) $pmc['receipts']['last_month'], (float) ($pmc['receipts']['prev_month'] ?? 0));
    $prevLabel  = (string) ($pmc['prev_label'] ?? '');
?>
<div class="pv-month">
    <div class="pv-month-head">
        <strong><i class="bi bi-bar-chart-line me-1" style="color:#6366f1;"></i>Last 3 months</strong>
        <span><?= htmlspecialchars($prevLabel) ?> · <?= htmlspecialchars($pmc['last_label']) ?> · <?= htmlspecialchars($pmc['this_label']) ?> · this branch</span>
    </div>
    <div class="pv-month-grid">
        <div class="pv-month-metric">
            <div class="pv-month-kicker">Sales</div>
            <div class="pv-month-pair">
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($prevLabel) ?></span>
                    <div class="amt"><?= $pmcMoney((float) ($pmc['sales']['prev_month'] ?? 0)) ?></div>
                </div>
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($pmc['last_label']) ?></span>
                    <div class="amt"><?= $pmcMoney((float) $pmc['sales']['last_month']) ?></div>
                </div>
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($pmc['this_label']) ?></span>
                    <div class="amt is-this"><?= $pmcMoney((float) $pmc['sales']['this_month']) ?></div>
                    <span class="pv-month-delta <?= $salesDelta['class'] ?>"><?= htmlspecialchars($salesDelta['text']) ?></span>
                </div>
            </div>
            <div class="pv-month-bars">
                <div class="pv-month-bar-row">
                    <span class="dot prev"></span>
                    <div class="pv-month-track"><div class="pv-month-fill prev" style="width:<?= $pmcBar((float) ($pmc['sales']['prev_month'] ?? 0), $salesMax) ?>"></div></div>
                </div>
                <div class="pv-month-bar-row">
                    <span class="dot last"></span>
                    <div class="pv-month-track"><div class="pv-month-fill last" style="width:<?= $pmcBar((float) $pmc['sales']['last_month'], $salesMax) ?>"></div></div>
                </div>
                <div class="pv-month-bar-row">
                    <span class="dot this"></span>
                    <div class="pv-month-track"><div class="pv-month-fill this" style="width:<?= $pmcBar((float) $pmc['sales']['this_month'], $salesMax) ?>"></div></div>
                </div>
            </div>
        </div>
        <div class="pv-month-metric">
            <div class="pv-month-kicker">Amount received</div>
            <div class="pv-month-pair">
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($prevLabel) ?></span>
                    <div class="amt"><?= $pmcMoney((float) ($pmc['receipts']['prev_month'] ?? 0)) ?></div>
                </div>
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($pmc['last_label']) ?></span>
                    <div class="amt"><?= $pmcMoney((float) $pmc['receipts']['last_month']) ?></div>
                </div>
                <div class="pv-month-stat">
                    <span class="lbl"><?= htmlspecialchars($pmc['this_label']) ?></span>
                    <div class="amt is-this"><?= $pmcMoney((float) $pmc['receipts']['this_month']) ?></div>
                    <span class="pv-month-delta <?= $recvDelta['class'] ?>"><?= htmlspecialchars($recvDelta['text']) ?></span>
                </div>
            </div>
            <div class="pv-month-bars">
                <div class="pv-month-bar-row">
                    <span class="dot prev"></span>
                    <div class="pv-month-track"><div class="pv-month-fill prev" style="width:<?= $pmcBar((float) ($pmc['receipts']['prev_month'] ?? 0), $recvMax) ?>"></div></div>
                </div>
                <div class="pv-month-bar-row">
                    <span class="dot last"></span>
                    <div class="pv-month-track"><div class="pv-month-fill last" style="width:<?= $pmcBar((float) $pmc['receipts']['last_month'], $recvMax) ?>"></div></div>
                </div>
                <div class="pv-month-bar-row">
                    <span class="dot this"></span>
                    <div class="pv-month-track"><div class="pv-month-fill this" style="width:<?= $pmcBar((float) $pmc['receipts']['this_month'], $recvMax) ?>"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($ledgerMismatch)): ?>
<div class="alert alert-warning border-0 mb-3" style="border-radius:12px;">
    <strong>Data check:</strong> This account has <?= (int)$linkedSalesCount ?> non-cancelled sale invoice(s) in the database, but the ledger table below is empty.
    Try refreshing the page. If it persists, note party ID <code><?= (int)$party['id'] ?></code> for support — the unified ledger query may need inspection on the server.
</div>
<?php elseif (!empty($ledgerReturnWrongParty)): ?>
<div class="alert alert-danger border-0 mb-3" style="border-radius:12px;">
    <strong>Returns without matching sales:</strong> This account has <strong>approved sale return(s)</strong> but <strong>no active sale invoices</strong> on account <?= htmlspecialchars($party['party_code'] ?? '') ?> (ID <?= (int)$party['id'] ?>).
    That can be intentional (returning an IMEI originally sold to another party — credit goes to the customer on the return), or a duplicate-customer mistake.
    Open each return from <a href="?page=returns" class="fw-bold">Returns</a> and confirm the customer should receive the credit. If the return was posted to the wrong account, correct <code>party_id</code> on the return after backup.
</div>
<?php elseif (!empty($cancelledSalesCount)): ?>
<div class="alert alert-info border-0 mb-3" style="border-radius:12px;">
    <strong>Cancelled (voided) invoices:</strong> This customer has <strong><?= (int)$cancelledSalesCount ?></strong> sale(s) marked <code>cancelled</code> in the database.
    They are <strong>excluded</strong> from this ledger and from the main sales list on purpose (same rules as <code>Party::getLedger</code> / active totals).
    <?php if (!empty($cancelledSalesList)): ?>
    <ul class="mb-0 mt-2 small">
        <?php foreach ($cancelledSalesList as $cs): ?>
        <li>
            <?php if (Auth::can('sales', 'view') && !empty($cs['id'])): ?>
            <a href="?page=sales&action=detail&id=<?= (int)$cs['id'] ?>" style="font-weight:600;"><?= htmlspecialchars($cs['invoice_no']) ?></a>
            <?php else: ?>
            <code><?= htmlspecialchars($cs['invoice_no']) ?></code>
            <?php endif; ?>
            — <?= htmlspecialchars($cs['date']) ?> — <?= APP_CURRENCY ?> <?= number_format((float)$cs['grand_total'], DECIMAL_PLACES) ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="small mb-0 mt-2 text-muted">If you did not use Cancel on these invoices, open each invoice from Sales (search by invoice number); the detail page shows <strong>who/when</strong> from <code>activity_log</code> when the app recorded a cancel. If that section is empty, status may have been changed outside the app (e.g. phpMyAdmin).</p>
    <p class="small mb-0 mt-2"><strong>Find them in Sales:</strong> as admin, use the sidebar <strong>Voided invoices</strong> (or <a href="?page=sales&view=voided" class="fw-bold">this link</a>) — the list is for the <strong>current branch</strong> only. Or tick <strong>Include voided</strong> on the main sales filter. Then open the invoice and use <strong>Reinstate voided invoice</strong> if appropriate.</p>
</div>
<?php elseif ($salesN === 0 && $purchN === 0 && empty($ledger)): ?>
<div class="alert alert-light border mb-3" style="border-radius:12px;color:#64748b;">
    No sales, purchases, or payments are linked to this party record yet. If you already posted invoices under this name, you may have a <strong>duplicate</strong> (same name, different account number) — open the invoice and use “View customer” / the supplier name to reach the ledger that has those transactions.
</div>
<?php endif; ?>

<?php if ($allocationNeedsRepair): ?>
<div class="alert alert-warning border-0 mb-3" style="border-radius:12px;">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <p class="fw-bold mb-1">Invoice Paid / Partial labels may be out of date</p>
            <p class="small mb-0">
                Payments on this branch and sale-invoice “paid” totals differ by
                <strong><?= APP_CURRENCY ?> <?= number_format($allocationGapAbs, DECIMAL_PLACES) ?></strong>.
                The <strong>Net Balance</strong> card is still the source of truth — this only fixes invoice badges.
                Rebuild does not change the ledger, payment rows, or cash accounts.
            </p>
        </div>
        <form method="POST" action="?page=parties&action=rebuildAllocation" id="formRebuildAllocation" class="flex-shrink-0">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="party_id" value="<?= (int) $party['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning" id="btnRebuildAllocation">
                <i class="bi bi-arrow-repeat me-1"></i> Rebuild invoice badges
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Transaction ledger</span>
        <span class="small text-muted fw-normal">This branch only</span>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" id="ledgerTable">
            <thead>
                <tr>
                    <th>Ref No</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ledger)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No transactions on this branch yet</td></tr>
                <?php else: ?>
                <?php
                    $running = (float)($ledgerOpeningBal ?? $party['opening_balance'] ?? 0);
                    $typeColors = [
                        'sale'           => '#6366f1',
                        'purchase'       => '#f59e0b',
                        'import_payable' => '#0ea5e9',
                        'vendor_bill'    => '#0891b2',
                        'payment'        => '#10b981',
                        'po_advance'     => '#c2410c',
                        'return'         => '#dc2626',
                        'dump'           => '#c2410c',
                        'expense'        => '#8b5cf6',
                        'discount'       => '#a855f7',
                    ];
                    $typeLabels = [
                        'sale'           => 'Sale',
                        'purchase'       => 'Purchase',
                        'import_payable' => 'Import payable',
                        'vendor_bill'    => 'Vendor bill',
                        'payment'        => 'Payment',
                        'po_advance'     => 'PO Advance',
                        'return'         => 'Return',
                        'dump'           => 'Dump',
                        'expense'        => 'Expense',
                        'discount'       => 'Discount',
                    ];
                ?>
                <?php if (abs($running) > 0.001): ?>
                <tr style="background:#f8fafc;">
                    <td colspan="3" style="font-weight:600;color:#64748b;">Opening Balance</td>
                    <?php if ($running > 0): ?>
                    <td class="text-end" style="font-weight:700;"><?= APP_CURRENCY ?> <?= number_format($running, DECIMAL_PLACES) ?></td>
                    <td class="text-end">—</td>
                    <?php else: ?>
                    <td class="text-end">—</td>
                    <td class="text-end" style="font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <?php endif; ?>
                    <td class="text-end fw-semibold"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
                <?php foreach ($ledger as $l):
                    $debit  = (float)$l['debit'];
                    $credit = (float)$l['credit'];
                    $running += $debit - $credit;
                    $tColor = $typeColors[$l['type']] ?? '#94a3b8';
                    $tLabel = $typeLabels[$l['type']] ?? ucfirst(str_replace('_',' ',$l['type']));
                    $refHref = $ledgerHref($l);
                    $refNo = (string) ($l['ref_no'] ?? '');
                ?>
                <tr>
                    <td>
                        <?php if ($refHref !== ''): ?>
                        <a class="pv-ledger-ref" href="<?= htmlspecialchars($refHref) ?>"><?= htmlspecialchars($refNo) ?></a>
                        <?php else: ?>
                        <span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($refNo) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $tColor ?>20;color:<?= $tColor ?>;border-radius:5px;">
                            <?= $tLabel ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($l['date'])) ?></td>
                    <td class="text-end"><?= $debit > 0 ? APP_CURRENCY . ' ' . number_format($debit, DECIMAL_PLACES) : '—' ?></td>
                    <td class="text-end" style="color:var(--success);"><?= $credit > 0 ? APP_CURRENCY . ' ' . number_format($credit, DECIMAL_PLACES) : '—' ?></td>
                    <td class="text-end fw-semibold"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td><span class="badge badge-<?= $l['status'] ?>" style="border-radius:5px;"><?= ucfirst($l['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f0f4ff;font-weight:700;">
                    <td colspan="5" style="text-align:right;color:#4338ca;">Closing Balance</td>
                    <td class="text-end" style="color:#4338ca;"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($showAllocationRepair && !$allocationNeedsRepair): ?>
<details class="pv-admin mt-3">
    <summary>Admin: rebuild invoice badges</summary>
    <div class="pv-admin-body">
        <p class="small text-muted mb-2">
            Use this only if an invoice still shows <strong>Partial</strong> while Net Balance looks right.
            It replays this branch’s payments onto sale/purchase invoices (oldest first) and updates Paid / Partial / balance on those invoices.
            It does <strong>not</strong> change the party ledger, payment rows, or cash accounts.
        </p>
        <form method="POST" action="?page=parties&action=rebuildAllocation" id="formRebuildAllocation">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="party_id" value="<?= (int) $party['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary" id="btnRebuildAllocation">
                <i class="bi bi-arrow-repeat me-1"></i> Rebuild invoice badges
            </button>
        </form>
    </div>
</details>
<?php endif; ?>

<?php if ($showAllocationRepair): ?>
<script>
(function () {
    var f = document.getElementById('formRebuildAllocation');
    if (!f) return;
    f.addEventListener('submit', function (e) {
        if (!confirm('Rebuild invoice Paid / Partial / balance from payments for this party on the current branch?\n\nThe party ledger and cash accounts will not change.')) {
            e.preventDefault();
        }
    });
})();
</script>
<?php endif; ?>
<script>$(document).ready(() => { if (typeof initDataTable === 'function') { initDataTable('#ledgerTable', { pageLength: 50, order: [], responsive: false, language: { search: '', searchPlaceholder: 'Search...' } }); } });</script>
