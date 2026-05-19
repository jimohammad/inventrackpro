<style>
.rpt-hub { max-width: 1000px; }

.rpt-section { margin-bottom: 24px; }
.rpt-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 2px solid var(--section-color, var(--border-color));
}
.rpt-section-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--section-color);
    flex-shrink: 0;
}
.rpt-section-title {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--section-color);
    margin: 0;
}
.rpt-section-count {
    margin-left: auto;
    font-size: 0.65rem;
    color: var(--text-muted);
    font-weight: 600;
}

.rpt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 8px;
}

.rpt-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-left: 3px solid var(--tile-color, var(--primary));
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    animation: rptSlideIn 0.3s ease both;
}
.rpt-tile:hover {
    border-left-width: 5px;
    transform: translateX(4px);
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    background: var(--tile-hover-bg, rgba(99,102,241,0.03));
}
.rpt-tile:active { transform: translateX(2px) scale(0.99); }

.rpt-tile-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    background: var(--tile-bg);
    color: var(--tile-color);
    transition: transform 0.2s;
}
.rpt-tile:hover .rpt-tile-icon { transform: scale(1.1); }

.rpt-tile-text {
    flex: 1;
    min-width: 0;
}
.rpt-tile-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    line-height: 1.3;
}
.rpt-tile-desc {
    font-size: 0.68rem;
    color: var(--text-muted);
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-height: 0;
    opacity: 0;
    transition: max-height 0.2s, opacity 0.2s, margin 0.2s;
}
.rpt-tile:hover .rpt-tile-desc {
    max-height: 20px;
    opacity: 1;
    margin-top: 2px;
}

.rpt-tile-arrow {
    font-size: 0.8rem;
    color: var(--text-muted);
    opacity: 0;
    transform: translateX(-6px);
    transition: all 0.2s;
    flex-shrink: 0;
}
.rpt-tile:hover .rpt-tile-arrow {
    opacity: 1;
    transform: translateX(0);
    color: var(--tile-color);
}

@keyframes rptSlideIn {
    from { opacity: 0; transform: translateX(-12px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Dark mode adjustments */
[data-theme="light"] .rpt-tile:hover {
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
}
</style>

<?php
$hasMaster = Auth::can('reports', 'view');

$sectionColors = [
    'Overview'             => '#0d9488',
    'Sales & Customers'    => '#6366f1',
    'Payments & Accounts'  => '#3b82f6',
    'Purchases & Suppliers'=> '#f59e0b',
    'Inventory'            => '#10b981',
];

$categories = [
    'Overview' => [
        ['page' => 'reports', 'action' => 'daybook',      'perm' => 'rpt_daybook',       'icon' => 'bi-journal-text',   'color' => '#0d9488', 'bg' => 'rgba(13,148,136,0.1)',  'title' => 'Day Book',       'desc' => 'All transactions for a specific day'],
        ['page' => 'reports', 'action' => 'balanceSheet',  'perm' => 'rpt_balance_sheet', 'icon' => 'bi-clipboard-data', 'color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.1)',  'title' => 'Balance Sheet',  'desc' => 'Assets, liabilities & net worth'],
        ['page' => 'reports', 'action' => 'profit',        'perm' => 'rpt_profit',        'icon' => 'bi-graph-up-arrow', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)',  'title' => 'Profit & Loss',  'desc' => 'Revenue, COGS & net profit'],
    ],
    'Sales & Customers' => [
        ['page' => 'reports', 'action' => 'sales',         'perm' => 'rpt_sales',         'icon' => 'bi-bag-check',         'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.1)',  'title' => 'Sales Report',          'desc' => 'Sales by date, party, or item'],
        ['page' => 'reports', 'action' => 'party',         'perm' => 'rpt_party',         'icon' => 'bi-person-lines-fill', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.1)',  'title' => 'Customer Statement',    'desc' => 'Transaction history per customer'],
        ['page' => 'reports', 'action' => 'itemSales',         'perm' => 'rpt_item_sales',         'icon' => 'bi-box-seam',           'color' => '#f43f5e', 'bg' => 'rgba(244,63,94,0.1)',   'title' => 'Item Sales',            'desc' => 'Who bought what, when'],
        ['page' => 'reports', 'action' => 'customerPurchases', 'perm' => 'rpt_customer_purchases', 'icon' => 'bi-bag-check-fill',     'color' => '#a855f7', 'bg' => 'rgba(168,85,247,0.1)',  'title' => 'Customer Purchases',    'desc' => 'What a customer bought, by date'],
        ['page' => 'reports', 'action' => 'salesReturns',  'perm' => 'rpt_sales_returns', 'icon' => 'bi-arrow-return-left', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)',   'title' => 'Sales Returns',         'desc' => 'Returned sales & item details'],
        ['page' => 'reports', 'action' => 'customerImei',  'perm' => 'rpt_customer_imei', 'icon' => 'bi-phone',             'color' => '#0891b2', 'bg' => 'rgba(8,145,178,0.1)',   'title' => 'Customer IMEI',         'desc' => 'IMEIs sold with invoice & date'],
    ],
    'Payments & Accounts' => [
        ['page' => 'reports', 'action' => 'payments',         'perm' => 'rpt_payments',      'icon' => 'bi-cash-stack',   'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)',  'title' => 'Payments Report',       'desc' => 'Collections by method & account'],
        ['page' => 'reports', 'action' => 'accountStatement', 'perm' => 'rpt_account_stmt',  'icon' => 'bi-bank',         'color' => '#0ea5e9', 'bg' => 'rgba(14,165,233,0.1)',  'title' => 'Account Statement',     'desc' => 'Per-account running balance'],
        ['page' => 'reports', 'action' => 'reconciliation',   'perm' => 'rpt_reconciliation','icon' => 'bi-shield-check', 'color' => '#059669', 'bg' => 'rgba(5,150,105,0.1)',   'title' => 'Reconciliation',        'desc' => 'Verify balances match records'],
    ],
    'Purchases & Suppliers' => [
        ['page' => 'reports', 'action' => 'supplierStatement','perm' => 'rpt_supplier_stmt', 'icon' => 'bi-building',  'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)', 'title' => 'Supplier Statement', 'desc' => 'Purchase & payment history'],
        ['page' => 'reports', 'action' => 'expenses',         'perm' => 'rpt_expenses',      'icon' => 'bi-receipt',   'color' => '#d97706', 'bg' => 'rgba(217,119,6,0.1)',  'title' => 'Expenses Report',    'desc' => 'By date, category & account'],
    ],
    'Inventory' => [
        ['page' => 'reports', 'action' => 'stock', 'perm' => 'rpt_stock', 'icon' => 'bi-boxes', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'title' => 'Stock Valuation', 'desc' => 'Current levels & value'],
    ],
];

$animDelay = 0;
?>

<div class="rpt-hub">
    <div class="mb-4">
        <h1 class="page-title">Reports</h1>
    </div>

    <?php foreach ($categories as $catName => $reports): ?>
    <?php
        $visible = [];
        foreach ($reports as $r) {
            if (empty($r['perm']) || $hasMaster || Auth::can($r['perm'], 'view')) {
                $visible[] = $r;
            }
        }
        if (empty($visible)) continue;
        $secColor = $sectionColors[$catName] ?? '#6366f1';
    ?>
    <div class="rpt-section" style="--section-color: <?= $secColor ?>;">
        <div class="rpt-section-head">
            <div class="rpt-section-dot"></div>
            <h6 class="rpt-section-title"><?= $catName ?></h6>
            <span class="rpt-section-count"><?= count($visible) ?></span>
        </div>
        <div class="rpt-grid">
            <?php foreach ($visible as $r): ?>
            <a href="?page=<?= $r['page'] ?>&action=<?= $r['action'] ?>"
               class="rpt-tile"
               style="--tile-color:<?= $r['color'] ?>;--tile-bg:<?= $r['bg'] ?>;--tile-hover-bg:<?= $r['bg'] ?>;animation-delay:<?= $animDelay ?>ms;">
                <div class="rpt-tile-icon">
                    <i class="bi <?= $r['icon'] ?>"></i>
                </div>
                <div class="rpt-tile-text">
                    <p class="rpt-tile-name"><?= $r['title'] ?></p>
                    <p class="rpt-tile-desc"><?= $r['desc'] ?></p>
                </div>
                <i class="bi bi-arrow-right rpt-tile-arrow"></i>
            </a>
            <?php $animDelay += 40; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
