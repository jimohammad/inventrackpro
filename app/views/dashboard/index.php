<?php
// Format currency helper
function money($val) {
    return APP_CURRENCY . ' ' . number_format($val, DECIMAL_PLACES);
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars(Auth::name()) ?> — <?= date('l, d F Y') ?></p>
    </div>
</div>

<?php
$dashNotifs = [];
if (!empty($mandoobInvDash) && (($mandoobInvDash['overdue'] ?? 0) > 0 || ($mandoobInvDash['due_soon'] ?? 0) > 0)) {
    $miOverdue = (int) ($mandoobInvDash['overdue'] ?? 0);
    $miSoon = (int) ($mandoobInvDash['due_soon'] ?? 0);
    $dashNotifs[] = [
        'tone' => $miOverdue > 0 ? 'danger' : 'warning',
        'icon' => 'bi-truck-front',
        'title' => 'Mandoob Inventory',
        'chips' => [
            ['label' => 'Overdue', 'value' => $miOverdue, 'hot' => $miOverdue > 0],
            ['label' => 'Due in 7 days', 'value' => $miSoon, 'hot' => false],
        ],
        'href' => Auth::can('mandoob_inventory', 'view') ? '?page=mandoob_inventory' : null,
        'action' => 'Open schedule',
    ];
}
if (!empty($serviceOverdueDash) && ($serviceOverdueDash['count'] ?? 0) > 0) {
    $svCount = (int) $serviceOverdueDash['count'];
    $dashNotifs[] = [
        'tone' => 'danger',
        'icon' => 'bi-tools',
        'title' => 'Service overdue',
        'chips' => [
            ['label' => 'Devices', 'value' => $svCount, 'hot' => true],
        ],
        'href' => '?page=service&overdue=1',
        'action' => 'View devices',
    ];
}
if (!empty($employeeResidenceDash) && (($employeeResidenceDash['expired'] ?? 0) > 0 || ($employeeResidenceDash['due_soon'] ?? 0) > 0)) {
    $erExpired = (int) ($employeeResidenceDash['expired'] ?? 0);
    $erSoon = (int) ($employeeResidenceDash['due_soon'] ?? 0);
    $dashNotifs[] = [
        'tone' => $erExpired > 0 ? 'danger' : 'warning',
        'icon' => 'bi-person-badge',
        'title' => 'Employee residence',
        'chips' => [
            ['label' => 'Expired', 'value' => $erExpired, 'hot' => $erExpired > 0],
            ['label' => 'Due in 30 days', 'value' => $erSoon, 'hot' => false],
        ],
        'href' => Auth::can('employees', 'view') ? '?page=employees' : null,
        'action' => 'Open employees',
    ];
}
if (!empty($tradeLicenseDash) && (($tradeLicenseDash['expired'] ?? 0) > 0 || ($tradeLicenseDash['due_soon'] ?? 0) > 0)) {
    $tlExpired = (int) ($tradeLicenseDash['expired'] ?? 0);
    $tlSoon = (int) ($tradeLicenseDash['due_soon'] ?? 0);
    $dashNotifs[] = [
        'tone' => $tlExpired > 0 ? 'danger' : 'warning',
        'icon' => 'bi-file-earmark-text',
        'title' => 'Supplier trade license',
        'chips' => [
            ['label' => 'Expired', 'value' => $tlExpired, 'hot' => $tlExpired > 0],
            ['label' => 'Due in 7 days', 'value' => $tlSoon, 'hot' => false],
        ],
        'href' => Auth::can('suppliers', 'view') ? '?page=parties&type=supplier' : null,
        'action' => 'Open suppliers',
    ];
}
$notifTone = [
    'warning' => [
        'stripe' => '#f59e0b',
        'wash'   => 'linear-gradient(135deg, rgba(245,158,11,0.10) 0%, rgba(245,158,11,0.03) 55%, transparent 100%)',
        'iconBg' => 'rgba(245,158,11,0.14)',
        'iconFg' => '#b45309',
    ],
    'danger' => [
        'stripe' => '#ef4444',
        'wash'   => 'linear-gradient(135deg, rgba(239,68,68,0.10) 0%, rgba(239,68,68,0.03) 55%, transparent 100%)',
        'iconBg' => 'rgba(239,68,68,0.14)',
        'iconFg' => '#b91c1c',
    ],
    'info' => [
        'stripe' => '#3b82f6',
        'wash'   => 'linear-gradient(135deg, rgba(59,130,246,0.10) 0%, rgba(59,130,246,0.03) 55%, transparent 100%)',
        'iconBg' => 'rgba(59,130,246,0.14)',
        'iconFg' => '#1d4ed8',
    ],
];
?>
<?php if (!empty($dashNotifs)): ?>
<style>
.dash-notif {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.85rem;
}
.dash-notif__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 12px 4px;
}
.dash-notif__title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--text-muted);
}
.dash-notif__title i {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(245,158,11,0.14);
    color: #d97706;
    font-size: 0.72rem;
}
.dash-notif__count {
    font-size: 0.64rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(245,158,11,0.14);
    color: #b45309;
}
.dash-notif__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0 10px 10px;
}
.dash-notif__item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1 1 280px;
    max-width: min(100%, 480px);
    padding: 7px 10px 7px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.dash-notif__item::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 100%;
    border-radius: 8px 0 0 8px;
    background: var(--notif-stripe);
}
.dash-notif__item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    border-color: color-mix(in srgb, var(--notif-stripe) 35%, var(--border-color));
}
.dash-notif__icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.85rem;
}
.dash-notif__main {
    min-width: 0;
    flex: 1;
}
.dash-notif__name {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.2;
}
.dash-notif__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 3px;
}
.dash-notif__chip {
    display: inline-flex;
    align-items: baseline;
    gap: 3px;
    padding: 1px 6px;
    border-radius: 5px;
    font-size: 0.62rem;
    font-weight: 600;
    color: var(--text-muted);
    background: rgba(148, 163, 184, 0.12);
}
.dash-notif__chip strong {
    font-size: 0.7rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: var(--text-main);
}
.dash-notif__chip.is-hot {
    background: rgba(239,68,68,0.12);
    color: #991b1b;
}
.dash-notif__chip.is-hot strong { color: #b91c1c; }
.dash-notif__action {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 700;
    text-decoration: none;
    color: #fff;
    background: #1e293b;
    flex-shrink: 0;
    white-space: nowrap;
    transition: background 0.15s;
}
.dash-notif__action:hover {
    background: #0f172a;
    color: #fff;
}
.dash-notif__action i { font-size: 0.65rem; }
</style>
<div class="dash-notif" role="region" aria-label="Notifications">
    <div class="dash-notif__head">
        <span class="dash-notif__title"><i class="bi bi-bell"></i>Notifications</span>
        <span class="dash-notif__count"><?= count($dashNotifs) ?> active</span>
    </div>
    <div class="dash-notif__grid">
        <?php foreach ($dashNotifs as $n):
            $tone = $notifTone[$n['tone']] ?? $notifTone['info'];
        ?>
        <div class="dash-notif__item" style="--notif-stripe:<?= $tone['stripe'] ?>;background:<?= $tone['wash'] ?>;">
            <span class="dash-notif__icon" style="background:<?= $tone['iconBg'] ?>;color:<?= $tone['iconFg'] ?>;">
                <i class="bi <?= htmlspecialchars($n['icon']) ?>"></i>
            </span>
            <div class="dash-notif__main">
                <div class="dash-notif__name"><?= htmlspecialchars($n['title']) ?></div>
                <?php if (!empty($n['chips'])): ?>
                <div class="dash-notif__chips">
                    <?php foreach ($n['chips'] as $chip): ?>
                    <span class="dash-notif__chip<?= !empty($chip['hot']) ? ' is-hot' : '' ?>">
                        <strong><?= (int) $chip['value'] ?></strong>
                        <?= htmlspecialchars($chip['label']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($n['href'])): ?>
            <a class="dash-notif__action" href="<?= htmlspecialchars($n['href']) ?>">
                <?= htmlspecialchars($n['action']) ?>
                <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Stat Cards - All 6 in one row -->
<style>
.dash-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 12px 14px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s, box-shadow 0.15s;
    min-height: 118px;
    height: 100%;
}
.dash-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); cursor: pointer; }
.dash-card .dc-stripe { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 10px 0 0 10px; }
.dash-card .dc-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.dash-card .dc-label { font-size: 0.68rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
.dash-card .dc-value { font-size: 0.95rem; font-weight: 800; color: var(--text-main); line-height: 1.2; }
.dash-card .dc-sub { font-size: 0.7rem; color: var(--text-muted); margin-top: 4px; }
.dash-link { display: block; height: 100%; text-decoration: none; color: inherit; }
</style>

<div class="row g-2 mb-4">
    <!-- Today's Sales -->
    <div class="col-6 col-md-2">
        <a href="?page=sales" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#6366f1;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(99,102,241,0.12);"><i class="bi bi-bag-check" style="color:#6366f1;"></i></div>
                <div class="dc-label">Today Sales</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#6366f1;"><?= money($todaySales['total']) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-receipt me-1"></i><?= $todaySales['count'] ?> invoices</div>
        </div>
        </a>
    </div>

    <!-- Today's Amount Received -->
    <div class="col-6 col-md-2">
        <a href="?page=payments" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#10b981;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(16,185,129,0.12);"><i class="bi bi-cash-coin" style="color:#10b981;"></i></div>
                <div class="dc-label">Amount Received</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#10b981;"><?= money($todayCash['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;">
                <i class="bi bi-wallet2 me-1"></i><?= $todayCash['count'] ?? 0 ?> payments
                <div>Main Cash: <?= money($todayCash['main_cash'] ?? 0) ?></div>
                <div>All Banks: <?= money($todayCash['bank_total'] ?? 0) ?></div>
            </div>
        </div>
        </a>
    </div>

    <!-- Cash by User (today's receipts grouped by app user who recorded them) -->
    <div class="col-6 col-md-2">
        <a href="?page=payments" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#e11d48;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(225,29,72,0.12);"><i class="bi bi-person-check" style="color:#e11d48;"></i></div>
                <div class="dc-label">Cash by User</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#e11d48;"><?= money($myReceived['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;">
                <?php
                $cashUsers = $myReceived['users'] ?? [];
                if ($cashUsers === []):
                ?>
                <i class="bi bi-person me-1"></i>No Main Cash today
                <?php else: ?>
                <?php foreach ($cashUsers as $cu): ?>
                <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($cu['name']) ?> · <?= money($cu['total']) ?></div>
                <?php endforeach; ?>
                <div>Main Cash · <?= (int)($myReceived['count'] ?? 0) ?> payments · <?= (int)($myReceived['user_count'] ?? count($cashUsers)) ?> users</div>
                <?php endif; ?>
            </div>
        </div>
        </a>
    </div>

    <!-- Stock Value -->
    <div class="col-6 col-md-2">
        <a href="?page=stock" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#0d9488;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(13,148,136,0.12);"><i class="bi bi-boxes" style="color:#0d9488;"></i></div>
                <div class="dc-label">Stock Value</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#0d9488;"><?= money($stockValue['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-box-seam me-1"></i><?= number_format($stockValue['units'] ?? 0) ?> units</div>
        </div>
        </a>
    </div>

    <!-- Cash already sent to suppliers — goods not in (not a receivable) -->
    <div class="col-6 col-md-2">
        <a href="?page=purchaseorders&status=paid&all_dates=1" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#3b82f6;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(59,130,246,0.12);"><i class="bi bi-file-earmark-text" style="color:#3b82f6;"></i></div>
                <div class="dc-label">Awaiting Goods</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#3b82f6;"><?= money($pendingPOs['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;">
                <i class="bi bi-cash-stack me-1"></i><?= (int) ($pendingPOs['count'] ?? 0) ?> paid · not refundable
                <?php if ((int) ($pendingPOs['unpaid_draft_count'] ?? 0) > 0): ?>
                <div><i class="bi bi-pencil-square me-1"></i><?= (int) $pendingPOs['unpaid_draft_count'] ?> unpaid drafts</div>
                <?php endif; ?>
            </div>
        </div>
        </a>
    </div>

    <!-- Trade receivables — customers/both; supplier advances excluded -->
    <div class="col-6 col-md-2">
        <a href="?page=parties&type=customer&balance=due" class="dash-link">
        <div class="dash-card">
            <div class="dc-stripe" style="background:#f59e0b;"></div>
            <div class="d-flex align-items-center gap-2 mb-1" style="padding-left:6px;">
                <div class="dc-icon" style="background:rgba(245,158,11,0.12);"><i class="bi bi-clock-history" style="color:#f59e0b;"></i></div>
                <div class="dc-label">Receivables</div>
            </div>
            <div class="dc-value" style="padding-left:6px;color:#f59e0b;"><?= money($pendingReceivables['total'] ?? 0) ?></div>
            <div class="dc-sub" style="padding-left:6px;"><i class="bi bi-people me-1"></i><?= (int) ($pendingReceivables['count'] ?? 0) ?> customers owing you</div>
        </div>
        </a>
    </div>
</div>

<!-- Bottom Cards Row -->
<div class="row g-2">
    <!-- Account Balances -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-wallet2 me-1" style="color:#3b82f6;"></i>Accounts</span>
                <span style="font-size:0.68rem;color:var(--text-muted);"><?= count($accounts) ?></span>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php
                $typeIcons  = ['cash' => 'bi-cash', 'bank' => 'bi-bank', 'other' => 'bi-wallet2'];
                $typeColors = ['cash' => '#10b981', 'bank' => '#3b82f6', 'other' => '#8b5cf6'];
                $totalBal   = 0;
                ?>
                <?php foreach ($accounts as $acc):
                    $totalBal += (float)$acc['current_balance'];
                    $accType = $acc['normalized_type'] ?? $acc['type'];
                    $icon  = $typeIcons[$accType]  ?? 'bi-wallet2';
                    $color = $typeColors[$accType] ?? '#8b5cf6';
                    $bal   = (float)$acc['current_balance'];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(99,102,241,0.04)'" onmouseout="this.style.background=''">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:0.72rem;width:14px;text-align:center;"></i>
                        <span style="font-size:0.72rem;font-weight:500;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><span style="font-family:monospace;color:#6366f1;font-weight:700;">#<?= (int)$acc['id'] ?></span> <?= htmlspecialchars($acc['name']) ?></span>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:<?= $bal >= 0 ? $color : '#ef4444' ?>;font-family:monospace;flex-shrink:0;">
                        <?= number_format($bal, DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($accounts)): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:5px 8px;margin-top:2px;border-top:1.5px solid var(--border-color);">
                    <span style="font-size:0.68rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total</span>
                    <span style="font-size:0.78rem;font-weight:800;color:#1e293b;font-family:monospace;">
                        <?= APP_CURRENCY ?> <?= number_format($totalBal, DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Items This Month -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-trophy me-1" style="color:#f59e0b;"></i>Top Items</span>
                <a href="?page=reports&action=itemSales" style="font-size:0.68rem;color:var(--text-muted);text-decoration:none;">View All</a>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php if (empty($topItems)): ?>
                <p class="text-muted text-center py-2 mb-0" style="font-size:0.75rem;">No sales this month</p>
                <?php else: ?>
                <?php foreach ($topItems as $i => $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(245,158,11,0.04)'" onmouseout="this.style.background=''">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                        <span style="font-size:0.68rem;font-weight:800;color:<?= $i === 0 ? '#f59e0b' : '#94a3b8' ?>;width:12px;"><?= $i+1 ?></span>
                        <div style="min-width:0;">
                            <span style="font-size:0.72rem;font-weight:500;color:var(--text-main);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                <?= htmlspecialchars($item['name']) ?>
                            </span>
                            <span style="font-size:0.64rem;color:var(--text-muted);"><?= $item['qty_sold'] ?> sold</span>
                        </div>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:#10b981;flex-shrink:0;font-family:monospace;">
                        <?= number_format($item['revenue'], DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-receipt me-1" style="color:#6366f1;"></i>Recent Sales</span>
                <a href="?page=sales" style="font-size:0.68rem;color:#6366f1;text-decoration:none;font-weight:600;">View All →</a>
            </div>
            <div class="card-body" style="padding:4px 6px;">
                <?php if (empty($recentSales)): ?>
                <p class="text-muted text-center py-2 mb-0" style="font-size:0.75rem;">No sales yet</p>
                <?php else: ?>
                <?php foreach ($recentSales as $s): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:3px 8px;border-radius:4px;"
                     onmouseover="this.style.background='rgba(99,102,241,0.04)'" onmouseout="this.style.background=''">
                    <div style="min-width:0;">
                        <span style="font-size:0.72rem;font-weight:600;color:#6366f1;"><?= $s['invoice_no'] ?></span>
                        <span style="font-size:0.66rem;color:var(--text-muted);margin-left:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($s['party_name']) ?></span>
                    </div>
                    <span style="font-size:0.72rem;font-weight:700;color:var(--text-main);flex-shrink:0;font-family:monospace;">
                        <?= number_format($s['grand_total'], DECIMAL_PLACES) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Comparison (horizontal bars) -->
    <?php
    $mc = $monthCompare ?? [
        'this_label' => date('M Y'),
        'last_label' => date('M Y', strtotime('first day of last month')),
        'sales' => ['this_month' => 0, 'last_month' => 0],
        'purchases' => ['this_month' => 0, 'last_month' => 0],
        'expenses' => ['this_month' => 0, 'last_month' => 0],
        'receipts' => ['this_month' => 0, 'last_month' => 0],
    ];
    $mc['receipts'] = $mc['receipts'] ?? ['this_month' => 0, 'last_month' => 0];
    ?>
    <div class="col-md-3">
        <div class="card h-100" style="border-radius:10px;">
            <div class="card-header d-flex justify-content-between align-items-center" style="padding:8px 12px;">
                <span style="font-size:0.8rem;font-weight:700;"><i class="bi bi-bar-chart me-1" style="color:#6366f1;"></i>Monthly Compare</span>
                <span style="font-size:0.64rem;color:var(--text-muted);">vs last month</span>
            </div>
            <div class="card-body" style="padding:6px 10px 10px;">
                <div style="height:220px;">
                    <canvas id="monthCompareChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const monthCompare = <?= json_encode($mc) ?>;

    function initCompareChart() {
        if (typeof Chart === 'undefined' || window._dashCompareChartsInit) {
            return;
        }
        window._dashCompareChartsInit = true;

        const el = document.getElementById('monthCompareChart');
        if (!el) {
            return;
        }

        const fmt = function (val) {
            return '<?= APP_CURRENCY ?> ' + Number(val).toLocaleString(undefined, {
                minimumFractionDigits: <?= DECIMAL_PLACES ?>,
                maximumFractionDigits: <?= DECIMAL_PLACES ?>
            });
        };

        const chartFont = {
            family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            size: 11,
            weight: '500',
            lineHeight: 1.2
        };

        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Sales', 'Purchases', 'Received', 'Expenses'],
                datasets: [
                    {
                        label: monthCompare.last_label,
                        data: [
                            monthCompare.sales.last_month,
                            monthCompare.purchases.last_month,
                            monthCompare.receipts.last_month,
                            monthCompare.expenses.last_month
                        ],
                        backgroundColor: 'rgba(148,163,184,0.5)',
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: 10
                    },
                    {
                        label: monthCompare.this_label,
                        data: [
                            monthCompare.sales.this_month,
                            monthCompare.purchases.this_month,
                            monthCompare.receipts.this_month,
                            monthCompare.expenses.this_month
                        ],
                        backgroundColor: ['#6366f1', '#3b82f6', '#22c55e', '#ef4444'],
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: 10
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            boxWidth: 10,
                            boxHeight: 10,
                            font: chartFont,
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + fmt(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8',
                            font: { ...chartFont, size: 9, weight: '400' },
                            maxTicksLimit: 5,
                            callback: function (val) {
                                return Number(val).toLocaleString();
                            }
                        },
                        grid: { color: 'rgba(148,163,184,0.12)' }
                    },
                    y: {
                        ticks: {
                            color: '#334155',
                            font: chartFont,
                            padding: 6
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    window.addEventListener('load', initCompareChart);
})();
</script>
