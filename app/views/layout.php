<!DOCTYPE html>
<html lang="en" id="htmlRoot" class="iq-boot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Preconnect only to CDNs we actually load (cdnjs) -->
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= APP_NAME ?></title>
    <?php
        $iqFont = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    ?>
    <style>
        :root {
            --iq-font: <?= $iqFont ?>;
            --bs-font-sans-serif: <?= $iqFont ?>;
            --bs-font-serif: <?= $iqFont ?>;
            --bs-body-font-family: <?= $iqFont ?>;
            --bs-font-monospace: <?= $iqFont ?>;
            --bs-body-font-size: 0.9rem;
            --header-height: 56px;
        }
        html { background: #f6f7f9; }
        html, body, h1, h2, h3, h4, h5, h6, p, button, input, select, textarea, .btn, .form-control, .form-select, table, .page-title {
            font-family: <?= $iqFont ?> !important;
        }
        body { font-size: 0.9rem !important; line-height: 1.5; }
        /* Hold first paint until icon font + jQuery widgets are applied (no FOUT / Select2 jump). */
        html.iq-boot body { visibility: hidden; }
        .app-topbar { height: 56px; }
        .main-content { margin-left: 0; }
    </style>
    <noscript><style>html.iq-boot body { visibility: visible; }</style></noscript>
    <script>
        (function() {
            document.getElementById('htmlRoot').setAttribute('data-theme', 'light');
        })();
        window.iqbalReadyQueue = [];
        window.iqbalLibsReady = false;
        window.iqbalWhenIdle = function (fn) {
            if (typeof fn !== 'function') return;
            if (window.iqbalLibsReady) fn();
            else window.iqbalReadyQueue.push(fn);
        };
        window.iqbalReveal = function () {
            document.documentElement.classList.remove('iq-boot');
        };
        setTimeout(function () { window.iqbalReveal(); }, 2000);
    </script>

    <!-- Preconnect to CDN for faster resource loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preload" href="assets/fonts/bootstrap-icons.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Bootstrap 5 from cdnjs (same origin as JS). Litera @imports Google Fonts and FOUT-shakes the page. -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <!-- Bootstrap Icons (local; font-display:optional so icons never swap after paint) -->
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css?v=<?= htmlspecialchars(ASSETS_VER) ?>">
    <!-- DataTables - only on list pages -->
    <?php $dtPages = ['returns','parties','expenses','items','reports','warranty','discounts']; ?>
    <?php if (isset($page) && in_array($page, $dtPages) && empty($skipListAssets)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css">
    <?php endif; ?>
    <!-- Select2 - only on pages that need it -->
    <?php if (isset($page) && in_array($page, ['sales', 'payments', 'purchases', 'purchaseorders', 'returns']) && empty($skipListAssets)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
    <?php endif; ?>

    <link rel="preload" href="assets/css/layout.css?v=<?= htmlspecialchars(ASSETS_VER) ?>" as="style">
    <link rel="preload" href="assets/js/app.js?v=<?= htmlspecialchars(ASSETS_VER) ?>" as="script">
    <link rel="stylesheet" href="assets/css/layout.css?v=<?= htmlspecialchars(ASSETS_VER) ?>">
</head>
<body>

<!-- App header: light top navigation -->
<header class="app-topbar">
    <button type="button" class="nav-toggle" id="sidebarToggle" aria-label="Open menu" aria-controls="appNav" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>
    <a href="<?= Auth::can('dashboard', 'view') ? '?page=dashboard' : '#' ?>" class="sidebar-brand">
        <span class="sidebar-brand-mark" aria-hidden="true"><i class="bi bi-boxes"></i></span>
        <span class="sidebar-brand-text">Iqbal<span>Erp</span></span>
    </a>
    <nav class="sidebar" id="appNav">
    <div class="sidebar-nav">
    <?php
        $navPage = (string) ($page ?? '');
        $navGroupOpen = [
            'transactions' => in_array($navPage, ['purchaseorders', 'purchases', 'sales', 'orderrequests', 'saleedits', 'payments', 'returns', 'warranty', 'dumps', 'expenses', 'service'], true),
            'parties'      => in_array($navPage, ['parties', 'suppliercontacts'], true),
            'inventory'    => in_array($navPage, ['items', 'categories', 'transfers', 'openingstock', 'stock', 'mandoob_inventory', 'imei', 'imeitrack'], true),
            'finance'      => in_array($navPage, ['accounts', 'landedcost', 'discounts'], true),
            'employees'    => ($navPage === 'employees'),
            'reports'      => ($navPage === 'reports'),
            'settings'     => in_array($navPage, ['warehouses', 'users', 'backups', 'settings'], true),
        ];
        $showTransactions = Auth::can('purchases', 'view')
            || Auth::can('sales', 'view')
            || Auth::can('payments', 'view')
            || Auth::can('payments_out', 'view')
            || Auth::can('returns', 'view')
            || Auth::can('warranty', 'view')
            || Auth::can('dumps', 'view')
            || Auth::can('expenses', 'view')
            || Auth::can('service', 'view');
        $sgClass = static function (string $id, array $open): string {
            return !empty($open[$id]) ? ' has-active' : '';
        };
        $sgAria = static function (): string {
            return 'false';
        };
    ?>

    <?php if (Auth::can('dashboard', 'view')): ?>
    <div class="sidebar-label">Main</div>
    <a href="?page=dashboard" class="sidebar-link <?= $navPage === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <?php endif; ?>

    <?php if ($showTransactions): ?>
    <div class="sidebar-group<?= $sgClass('transactions', $navGroupOpen) ?>" data-group="transactions">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('transactions', $navGroupOpen) ?>">
        <span>Transactions</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">

    <?php if (Auth::can('purchases', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=purchaseorders" class="sidebar-link <?= ($page ?? '') === 'purchaseorders' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Purchase Orders
        </a>
        <?php if (Auth::can('purchases', 'add')): ?>
        <a href="?page=purchaseorders&action=create" class="quick-add-btn" title="New Purchase Order">+</a>
        <?php endif; ?>
    </div>
    <div class="sidebar-link-wrap">
        <a href="?page=purchases" class="sidebar-link <?= ($page ?? '') === 'purchases' ? 'active' : '' ?>">
            <i class="bi bi-cart-plus"></i> Purchases
        </a>
        <?php if (Auth::can('purchases', 'add')): ?>
        <a href="?page=purchases&action=create" class="quick-add-btn" title="New Purchase  (Alt+P)">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('sales', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=sales" class="sidebar-link <?= ($page ?? '') === 'sales' && (($_GET['view'] ?? '') !== 'voided') ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Sales
        </a>
        <?php if (Auth::can('sales', 'add')): ?>
        <a href="?page=sales&action=create" class="quick-add-btn" title="New Sale Invoice  (Alt+S)">+</a>
        <?php endif; ?>
    </div>
    <a href="?page=orderrequests" class="sidebar-link <?= ($page ?? '') === 'orderrequests' ? 'active' : '' ?>" id="navOrderRequests">
        <i class="bi bi-bag-check"></i> Order Requests
        <span id="orderReqBadge" class="badge bg-warning text-dark ms-auto" style="display:none;font-size:0.65rem;">0</span>
    </a>
    <?php if (Auth::isAdmin()): ?>
    <a href="?page=saleedits" class="sidebar-link <?= ($page ?? '') === 'saleedits' ? 'active' : '' ?>" id="navSaleEdits">
        <i class="bi bi-unlock"></i> Sale edit requests
        <span id="saleEditBadge" class="badge bg-warning text-dark ms-auto" style="display:none;font-size:0.65rem;">0</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>
    <?php
        $payNavAction = (string) ($_GET['action'] ?? '');
        $isPayOutNav  = ($page ?? '') === 'payments' && in_array($payNavAction, ['out', 'pay'], true);
        $isPayInNav   = ($page ?? '') === 'payments' && !$isPayOutNav;
    ?>
    <?php if (Auth::can('payments', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=payments" class="sidebar-link <?= $isPayInNav ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i> Payment In
        </a>
        <?php if (Auth::can('payments', 'add')): ?>
        <a href="?page=payments&action=receive" class="quick-add-btn" title="Receive Payment">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('payments_out', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=payments&action=out" class="sidebar-link <?= $isPayOutNav ? 'active' : '' ?>">
            <i class="bi bi-arrow-up-circle"></i> Payment Out
        </a>
        <?php if (Auth::can('payments_out', 'add')): ?>
        <a href="?page=payments&action=pay" class="quick-add-btn" title="Make Payment">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('returns', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=returns" class="sidebar-link <?= ($page ?? '') === 'returns' ? 'active' : '' ?>">
            <i class="bi bi-arrow-return-left"></i> Returns
        </a>
        <?php if (Auth::can('returns', 'add')): ?>
        <a href="?page=returns&action=create" class="quick-add-btn" title="New Return">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('warranty', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=warranty" class="sidebar-link <?= ($page ?? '') === 'warranty' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Warranty Replace
        </a>
        <?php if (Auth::can('warranty', 'add')): ?>
        <a href="?page=warranty&action=create" class="quick-add-btn" title="New Warranty Replacement">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('dumps', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=dumps" class="sidebar-link <?= ($page ?? '') === 'dumps' ? 'active' : '' ?>">
            <i class="bi bi-recycle"></i> Dump Credit
        </a>
        <?php if (Auth::can('dumps', 'add')): ?>
        <a href="?page=dumps&action=create" class="quick-add-btn" title="New Dump Credit">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('expenses', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=expenses" class="sidebar-link <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Expenses
        </a>
        <?php if (Auth::can('expenses', 'add')): ?>
        <a href="?page=expenses&new=1" class="quick-add-btn" title="New Expense  (Alt+E)">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('service', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=service" class="sidebar-link <?= ($page ?? '') === 'service' ? 'active' : '' ?>">
            <i class="bi bi-tools"></i> Service Center
        </a>
        <?php if (Auth::can('service', 'add')): ?>
        <a href="?page=service&action=create" class="quick-add-btn" title="New Service">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
    </div>
    <?php endif; ?>
    <!-- Parties: Customers + Suppliers + Freight Forwarders; Party Master = All / Both -->
    <?php
        $showPartyMaster = Auth::can('parties', 'view') && !Auth::isSalesFloor();
        $showCustomersList = Auth::can('customers', 'view') || Auth::can('parties', 'view');
        // Party Master must not reveal suppliers — salesman often has parties but not suppliers.
        $showSuppliersList = Auth::can('suppliers', 'view');
        $showFreightForwardersList = $showSuppliersList;
        $showSupplierContacts = Auth::can('supplier_contacts', 'view');

        $isPartiesPage = ($page ?? '') === 'parties';
        $partyListType = isset($_GET['type']) ? (string) $_GET['type'] : 'all';
        $partyAction   = isset($_GET['action']) ? (string) $_GET['action'] : '';
        $onPartyList   = $isPartiesPage && ($partyAction === '' || $partyAction === 'index');
        $onPartyCreate = $isPartiesPage && $partyAction === 'create';

        $customersActive = ($onPartyList || $onPartyCreate) && $partyListType === 'customer';
        $suppliersActive = ($onPartyList || $onPartyCreate) && $partyListType === 'supplier';
        $freightActive   = ($onPartyList || $onPartyCreate) && $partyListType === 'freight_forwarder';
        $partyMasterActive = $isPartiesPage && !$customersActive && !$suppliersActive && !$freightActive;
    ?>
    <?php if ($showPartyMaster || $showCustomersList || $showSuppliersList || $showSupplierContacts): ?>
    <div class="sidebar-group<?= $sgClass('parties', $navGroupOpen) ?>" data-group="parties">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('parties', $navGroupOpen) ?>">
        <span>Parties</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <?php if ($showCustomersList): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=parties&type=customer" class="sidebar-link <?= $customersActive ? 'active' : '' ?>">
            <i class="bi bi-person"></i> Customers
        </a>
        <?php if (Auth::canAny(['parties', 'customers'], 'add')): ?>
        <a href="?page=parties&action=create&type=customer" class="quick-add-btn" title="New Customer">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($showSuppliersList): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=parties&type=supplier" class="sidebar-link <?= $suppliersActive ? 'active' : '' ?>">
            <i class="bi bi-truck"></i> Suppliers
        </a>
        <?php if (Auth::can('suppliers', 'add')): ?>
        <a href="?page=parties&action=create&type=supplier" class="quick-add-btn" title="New Supplier">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($showFreightForwardersList): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=parties&type=freight_forwarder" class="sidebar-link <?= $freightActive ? 'active' : '' ?>">
            <i class="bi bi-boxes"></i> Freight Forwarders
        </a>
        <?php if (Auth::can('suppliers', 'add')): ?>
        <a href="?page=parties&action=create&type=freight_forwarder" class="quick-add-btn" title="New Freight Forwarder">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($showPartyMaster): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=parties" class="sidebar-link <?= $partyMasterActive ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Party Master
        </a>
        <?php if (Auth::can('parties', 'add')): ?>
        <a href="?page=parties&action=create" class="quick-add-btn" title="New Party">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($showSupplierContacts): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=suppliercontacts" class="sidebar-link <?= ($page ?? '') === 'suppliercontacts' ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Supplier Contacts
        </a>
        <?php if (Auth::can('supplier_contacts', 'add')): ?>
        <a href="?page=suppliercontacts&action=add" class="quick-add-btn" title="New Contact">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
    </div>
    <?php endif; ?>

    <?php if (Auth::can('employees', 'view')): ?>
    <div class="sidebar-group<?= $sgClass('employees', $navGroupOpen) ?>" data-group="employees">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria() ?>">
        <span>Employees</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <div class="sidebar-link-wrap">
        <a href="?page=employees" class="sidebar-link <?= ($page ?? '') === 'employees' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> All Employees
        </a>
        <?php if (Auth::can('employees', 'add')): ?>
        <a href="?page=employees&action=create" class="quick-add-btn" title="New Employee">+</a>
        <?php endif; ?>
    </div>
    </div>
    </div>
    <?php endif; ?>

    <?php
        $canInventory = Auth::can('inventory', 'view');
        $canStock     = Auth::canAny(['stock', 'inventory'], 'view');
        $canMandoob   = Auth::can('mandoob_inventory', 'view');
        $canImei      = Auth::can('imei', 'view');
        $showInventorySection = $canInventory || $canStock || $canMandoob || $canImei || Auth::can('intershop', 'view');
    ?>
    <?php if ($showInventorySection): ?>
    <div class="sidebar-group<?= $sgClass('inventory', $navGroupOpen) ?>" data-group="inventory">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('inventory', $navGroupOpen) ?>">
        <span>Inventory</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <?php if ($canInventory): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=items" class="sidebar-link <?= ($page ?? '') === 'items' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Items
        </a>
        <?php if (Auth::can('inventory', 'add')): ?>
        <a href="?page=items&action=create" class="quick-add-btn" title="New Item">+</a>
        <?php endif; ?>
    </div>
    <a href="?page=categories" class="sidebar-link <?= ($page ?? '') === 'categories' ? 'active' : '' ?>">
        <i class="bi bi-tag"></i> Categories
    </a>
    <a href="?page=transfers" class="sidebar-link <?= ($page ?? '') === 'transfers' ? 'active' : '' ?>">
        <i class="bi bi-arrow-left-right"></i> Stock Transfers
    </a>
    <a href="?page=openingstock" class="sidebar-link <?= ($page ?? '') === 'openingstock' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-down"></i> Opening Stock
    </a>
    <?php endif; ?>
    <?php if ($canStock): ?>
    <a href="?page=stock" class="sidebar-link <?= ($page ?? '') === 'stock' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-data"></i> Stock List
    </a>
    <?php endif; ?>
    <?php if (Auth::can('intershop', 'view')): ?>
    <a href="?page=intershop" class="sidebar-link <?= ($page ?? '') === 'intershop' ? 'active' : '' ?>">
        <i class="bi bi-shop-window"></i> Shop transfer
    </a>
    <?php endif; ?>
    <?php if ($canMandoob): ?>
    <a href="?page=mandoob_inventory" class="sidebar-link <?= ($page ?? '') === 'mandoob_inventory' ? 'active' : '' ?>">
        <i class="bi bi-truck-front"></i> Mandoob Inventory
    </a>
    <?php endif; ?>
    <?php if ($canImei): ?>
    <a href="?page=imei&action=lifecycle" class="sidebar-link <?= ($page ?? '') === 'imei' && ($_GET['action'] ?? '') === 'lifecycle' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i> IMEI Lifecycle
    </a>
    <?php endif; ?>
    <?php if ($canImei || $canInventory): ?>
    <a href="/imei" class="sidebar-link <?= ($page ?? '') === 'imeitrack' ? 'active' : '' ?>">
        <i class="bi bi-shield-check"></i> IMEI Warranty Track
    </a>
    <?php endif; ?>
    <?php if (Auth::isAdmin()): ?>
    <a href="?page=imei&action=audit" class="sidebar-link <?= ($page ?? '') === 'imei' && ($_GET['action'] ?? '') === 'audit' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check"></i> Stock Audit
    </a>
    <?php endif; ?>
    </div>
    </div>
    <?php endif; ?>

    <?php if (Auth::canAny(['payments', 'expenses', 'settings', 'discounts', 'import_logistics'], 'view')): ?>
    <div class="sidebar-group<?= $sgClass('finance', $navGroupOpen) ?>" data-group="finance">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('finance', $navGroupOpen) ?>">
        <span>Finance</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <?php if (Auth::canAny(['settings', 'payments', 'rpt_account_stmt'], 'view')): ?>
    <a href="?page=accounts" class="sidebar-link <?= ($page ?? '') === 'accounts' ? 'active' : '' ?>">
        <i class="bi bi-wallet2"></i> Accounts
    </a>
    <?php endif; ?>
    <?php if (Auth::canAny(['import_logistics', 'purchases'], 'view')): ?>
    <a href="?page=landedcost" class="sidebar-link <?= ($page ?? '') === 'landedcost' ? 'active' : '' ?>">
        <i class="bi bi-globe2"></i> Import Logistics
    </a>
    <?php endif; ?>
    <?php if (Auth::canAny(['discounts', 'settings'], 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=discounts" class="sidebar-link <?= ($page ?? '') === 'discounts' ? 'active' : '' ?>">
            <i class="bi bi-tag"></i> Discounts
        </a>
        <?php if (Auth::canAny(['discounts', 'settings'], 'add')): ?>
        <a href="?page=discounts&new=1" class="quick-add-btn" title="New Discount">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
    </div>
    <?php endif; ?>

    <?php if (Auth::hasAnyReportAccess()): ?>
    <div class="sidebar-group<?= $sgClass('reports', $navGroupOpen) ?>" data-group="reports">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('reports', $navGroupOpen) ?>">
        <span>Reports</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <a href="?page=reports" class="sidebar-link <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i> All Reports
    </a>
    </div>
    </div>
    <?php endif; ?>

    <?php if (Auth::isAdmin()): ?>
    <div class="sidebar-group<?= $sgClass('settings', $navGroupOpen) ?>" data-group="settings">
    <button type="button" class="sidebar-group-toggle" aria-expanded="<?= $sgAria('settings', $navGroupOpen) ?>">
        <span>Settings</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="sidebar-group-body">
    <a href="?page=warehouses" class="sidebar-link <?= ($page ?? '') === 'warehouses' ? 'active' : '' ?>">
        <i class="bi bi-building"></i> Warehouses
    </a>
    <a href="?page=users" class="sidebar-link <?= ($page ?? '') === 'users' ? 'active' : '' ?>">
        <i class="bi bi-shield-person"></i> User Management
    </a>
    <a href="?page=backups" class="sidebar-link <?= ($page ?? '') === 'backups' ? 'active' : '' ?>">
        <i class="bi bi-cloud-arrow-down"></i> Backups
    </a>
    <a href="?page=settings" class="sidebar-link <?= ($page ?? '') === 'settings' ? 'active' : '' ?>">
        <i class="bi bi-gear"></i> Settings
    </a>
    </div>
    </div>
    <?php endif; ?>

    </div>
    </nav>

    <div class="app-topbar-tools">
        <!-- Current branch label (no switcher — Main is the only operational branch) -->
        <?php if (Auth::warehouseId()): ?>
        <div class="d-flex align-items-center gap-1" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:4px 10px;">
            <i class="bi bi-building" style="color:#10b981;font-size:0.8rem;"></i>
            <span style="color:#10b981;font-size:0.8rem;font-weight:600;"><?= htmlspecialchars(Auth::warehouseName()) ?></span>
            <?php if (defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER && Auth::isAdmin()): ?>
            <a href="?page=warehouse&switch=1" title="Switch warehouse"
               style="color:#10b981;margin-left:4px;font-size:0.75rem;text-decoration:none;opacity:0.7;" >
                <i class="bi bi-arrow-left-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (Auth::can('sales', 'view')): ?>
        <a href="?page=orderrequests" id="topOrderReqBell" title="Order requests"
           style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.28);color:#d97706;text-decoration:none;">
            <i class="bi bi-bell"></i>
            <span id="topOrderReqBadge" style="display:none;position:absolute;top:-5px;right:-5px;background:#dc2626;color:#fff;font-size:0.62rem;font-weight:800;padding:1px 5px;border-radius:10px;line-height:1.3;">0</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
        <a href="?page=saleedits" id="topSaleEditBell" title="Sale edit requests"
           style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.28);color:#4f46e5;text-decoration:none;">
            <i class="bi bi-unlock"></i>
            <span id="topSaleEditBadge" style="display:none;position:absolute;top:-5px;right:-5px;background:#dc2626;color:#fff;font-size:0.62rem;font-weight:800;padding:1px 5px;border-radius:10px;line-height:1.3;">0</span>
        </a>
        <?php endif; ?>

        <!-- Pricelist Toggle — only on stock page -->
        <?php if (($page ?? '') === 'stock'): ?>
        <button class="theme-toggle" id="pricelistBtn" title="Show prices on public pricelist for 3 min"
                style="position:relative;" onclick="togglePricelist()">
            <i class="bi bi-tag" id="pricelistIcon"></i>
            <span id="pricelistTimer" style="display:none;position:absolute;top:-6px;right:-6px;background:#dc2626;color:#fff;font-size:0.6rem;font-weight:800;padding:1px 5px;border-radius:10px;line-height:1.3;"></span>
        </button>
        <?php endif; ?>

        <!-- User dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2"
                style="background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);color:var(--primary);"
                data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-md-inline"><?= htmlspecialchars(Auth::name()) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background:var(--bg-card);border-color:var(--border-color);">
                <li>
                    <span class="dropdown-item-text" style="color:var(--text-muted);font-size:0.8rem;">
                        <?= ucfirst(Auth::role()) ?>
                    </span>
                </li>
                <li>
                    <?php $curTpl = Auth::printTemplate(); ?>
                    <div class="dropdown-item-text" style="padding:8px 16px;">
                        <div style="font-size:0.72rem;color:var(--text-muted);font-weight:700;letter-spacing:.3px;text-transform:uppercase;margin-bottom:6px;">
                            Default Print
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="btn btn-sm <?= $curTpl === 'a5' ? 'btn-primary' : 'btn-outline-secondary' ?>" id="tplA5Btn" style="flex:1;">
                                A5
                            </button>
                            <button type="button" class="btn btn-sm <?= $curTpl === 'thermal' ? 'btn-success' : 'btn-outline-secondary' ?>" id="tplThermalBtn" style="flex:1;">
                                Thermal
                            </button>
                        </div>
                        <div id="tplSavedMsg" style="display:none;margin-top:6px;font-size:0.72rem;color:#059669;font-weight:700;">
                            Saved
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                <?php if (defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER && Auth::isAdmin()): ?>
                <li>
                    <a class="dropdown-item" href="?page=warehouse&switch=1">
                        <i class="bi bi-building me-2"></i>Switch Warehouse
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <form method="post" action="?page=logout&action=logout" class="m-0 px-2 py-1">
                        <?= Auth::csrfField() ?>
                        <button type="submit" class="dropdown-item" style="color:var(--danger);">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Flash Message -->
<?php $flash = BaseController::getFlash(); if ($flash): ?>
<div class="flash-msg">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Main Content Area -->
<main class="main-content">
    <?= $content ?? '' ?>
</main>

<!-- Scripts -->
<?php if (empty($skipJquery)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<?php if (isset($page) && in_array($page, $dtPages ?? []) && empty($skipListAssets)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<?php endif; ?>
<?php if (isset($page) && in_array($page, ['sales', 'payments', 'purchases', 'purchaseorders', 'returns']) && empty($skipListAssets)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<?php endif; ?>
<?php if (!empty($loadChartJs) || ($page ?? '') === 'dashboard'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>
<?php endif; ?>
<script src="assets/js/app.js?v=<?= htmlspecialchars(ASSETS_VER) ?>"></script>
<script src="assets/js/imei-format.js?v=<?= htmlspecialchars(ASSETS_VER) ?>"></script>

<?php if (isset($extraJs)): ?>
<?php
    // Prevent breaking out of the <script> tag if $extraJs contains "</script".
    // $extraJs must remain server-controlled (never user input).
    $extraJsSafe = str_replace('</script', '<\/script', (string)$extraJs);
?>
<script><?= $extraJsSafe ?></script>
<?php endif; ?>

<!-- Admin PIN Verification Modal -->
<div id="pinModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.72);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:16px;padding:28px;width:320px;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;">
        <div style="width:52px;height:52px;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <i class="bi bi-shield-lock" style="font-size:1.4rem;color:#f59e0b;"></i>
        </div>
        <h6 style="font-weight:700;margin-bottom:4px;color:var(--text-main);">Admin PIN Required</h6>
        <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:16px;">Enter 4-digit PIN to proceed</p>
        <div style="display:flex;gap:8px;justify-content:center;margin-bottom:10px;">
            <input type="password" id="pin1" maxlength="1" class="pin-digit" oninput="pinNext(1)" onkeydown="pinBack(event,1)">
            <input type="password" id="pin2" maxlength="1" class="pin-digit" oninput="pinNext(2)" onkeydown="pinBack(event,2)">
            <input type="password" id="pin3" maxlength="1" class="pin-digit" oninput="pinNext(3)" onkeydown="pinBack(event,3)">
            <input type="password" id="pin4" maxlength="1" class="pin-digit" oninput="pinNext(4)" onkeydown="pinBack(event,4)">
        </div>
        <div id="pinError" style="font-size:0.78rem;color:#ef4444;font-weight:600;min-height:22px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:8px;justify-content:center;">
            <button onclick="closePin()" style="padding:7px 20px;border-radius:8px;border:1.5px solid var(--border-color);background:transparent;color:var(--text-muted);cursor:pointer;font-size:0.85rem;">Cancel</button>
            <button onclick="submitPin()" id="pinSubmitBtn" style="padding:7px 24px;border-radius:8px;border:none;background:#f59e0b;color:#fff;cursor:pointer;font-size:0.85rem;font-weight:700;">Verify</button>
        </div>
    </div>
</div>
<script>
var _pinCallback = null;
var _pinVerified = false;

function requirePin(callback) {
    if (_pinVerified) { callback(); return; }
    _pinCallback = callback;
    document.getElementById('pinModal').style.display = 'flex';
    document.getElementById('pinError').textContent = '';
    ['pin1','pin2','pin3','pin4'].forEach(id => { document.getElementById(id).value = ''; document.getElementById(id).classList.remove('err'); });
    setTimeout(() => document.getElementById('pin1').focus(), 100);
}

function pinNext(n) {
    if (n < 4 && document.getElementById('pin'+n).value) document.getElementById('pin'+(n+1)).focus();
    if (n === 4 && document.getElementById('pin4').value) submitPin();
}

function pinBack(e, n) {
    if (e.key === 'Backspace' && !document.getElementById('pin'+n).value && n > 1) document.getElementById('pin'+(n-1)).focus();
    if (e.key === 'Escape') closePin();
}

function closePin() {
    document.getElementById('pinModal').style.display = 'none';
    _pinCallback = null;
}

function submitPin() {
    var pin = document.getElementById('pin1').value + document.getElementById('pin2').value +
              document.getElementById('pin3').value + document.getElementById('pin4').value;
    if (pin.length < 4) { document.getElementById('pinError').textContent = 'Enter all 4 digits'; return; }

    document.getElementById('pinSubmitBtn').textContent = '...';
    fetch('?page=settings&action=verifyPin', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= Auth::csrfToken() ?>&pin=' + encodeURIComponent(pin)
    })
        .then(r => r.json())
        .then(data => {
            document.getElementById('pinSubmitBtn').textContent = 'Verify';
            if (data.valid) {
                _pinVerified = true;
                document.getElementById('pinModal').style.display = 'none';
                if (_pinCallback) _pinCallback();
            } else {
                document.getElementById('pinError').textContent = 'Wrong PIN';
                ['pin1','pin2','pin3','pin4'].forEach(id => {
                    document.getElementById(id).classList.add('err');
                    document.getElementById(id).value = '';
                });
                setTimeout(() => document.getElementById('pin1').focus(), 200);
            }
        })
        .catch(() => {
            document.getElementById('pinSubmitBtn').textContent = 'Verify';
            document.getElementById('pinError').textContent = 'Error — try again';
        });
}

// Close on overlay click / Escape
document.getElementById('pinModal').addEventListener('click', function(e) { if (e.target === this) closePin(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && document.getElementById('pinModal').style.display === 'flex') closePin(); });

// ── Default print template (session-only) ──
(function () {
    var btnA5 = document.getElementById('tplA5Btn');
    var btnTh = document.getElementById('tplThermalBtn');
    if (!btnA5 || !btnTh) return;

    function setTpl(tpl) {
        fetch('?page=settings&action=setPrintTemplate', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=<?= Auth::csrfToken() ?>&tpl=' + encodeURIComponent(tpl)
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.success) return;
                // Quick refresh so all links/buttons use the new default
                var msg = document.getElementById('tplSavedMsg');
                if (msg) {
                    msg.style.display = '';
                    setTimeout(function () { msg.style.display = 'none'; }, 1200);
                }
                setTimeout(function () { window.location.reload(); }, 200);
            })
            .catch(function () {});
    }

    btnA5.addEventListener('click', function () { setTpl('a5'); });
    btnTh.addEventListener('click', function () { setTpl('thermal'); });
})();

// Universal PIN protection — intercepts all .pin-protect links and buttons
document.addEventListener('click', function(e) {
    var el = e.target.closest('.pin-protect');
    if (!el) return;
    if (_pinVerified) return;

    e.preventDefault();
    e.stopPropagation();

    function replayAfterPin() {
        _pinVerified = true;
        try { el.click(); } catch (err) {}
    }

    if (el.tagName === 'A') {
        var rawHref = (el.getAttribute('href') || '').trim();
        var dest = el.href || rawHref;
        var isPlaceholder = !rawHref || rawHref === '#' || rawHref.toLowerCase().indexOf('javascript:') === 0;
        requirePin(function() {
            if (!isPlaceholder && dest) {
                window.location.href = dest;
                return;
            }
            replayAfterPin();
        });
        return;
    }

    if (el.tagName === 'BUTTON' && el.type === 'submit') {
        var form = el.form || el.closest('form');
        if (form) {
            var confirmMsg = el.getAttribute('data-confirm') || '';
            if (!confirmMsg && form.action && form.action.indexOf('cancel') !== -1) {
                confirmMsg = 'Are you sure you want to cancel this?';
            }
            requirePin(function() {
                if (confirmMsg && !confirm(confirmMsg)) return;
                form.submit();
            });
            return;
        }
    }

    requirePin(replayAfterPin);
}, true);

// ── Pricelist Toggle (stock page only) ──
<?php if (($page ?? '') === 'stock'): ?>
var plTimer = null;
function togglePricelist() {
    var btn = document.getElementById('pricelistBtn');
    var icon = document.getElementById('pricelistIcon');
    var isActive = icon.classList.contains('bi-tag-fill');

    fetch('?page=settings&action=togglePricelist', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= Auth::csrfToken() ?>&action_type=' + (isActive ? 'off' : 'on')
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            if (res.seconds > 0) {
                startPricelistCountdown(res.seconds);
            } else {
                stopPricelistCountdown();
            }
        }
    });
}

function startPricelistCountdown(seconds) {
    var icon = document.getElementById('pricelistIcon');
    var timerEl = document.getElementById('pricelistTimer');
    var btn = document.getElementById('pricelistBtn');

    icon.className = 'bi bi-tag-fill';
    btn.style.background = 'rgba(16,185,129,0.25)';
    btn.style.color = '#059669';
    timerEl.style.display = '';

    clearInterval(plTimer);
    plTimer = setInterval(function() {
        seconds--;
        if (seconds <= 0) { stopPricelistCountdown(); return; }
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    }, 1000);
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
}

function stopPricelistCountdown() {
    clearInterval(plTimer);
    var icon = document.getElementById('pricelistIcon');
    var timerEl = document.getElementById('pricelistTimer');
    var btn = document.getElementById('pricelistBtn');
    icon.className = 'bi bi-tag';
    btn.style.background = '';
    btn.style.color = '';
    timerEl.style.display = 'none';
    timerEl.textContent = '';
}

// Check pricelist status on page load
fetch('?page=settings&action=pricelistStatus')
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.active && res.seconds > 0) {
            startPricelistCountdown(res.seconds);
        }
    });
<?php endif; // stock page only ?>
</script>
<script>
(function () {
    window.iqbalLibsReady = true;
    var q = window.iqbalReadyQueue || [];
    window.iqbalReadyQueue = [];
    for (var i = 0; i < q.length; i++) {
        try { q[i](); } catch (e) {}
    }
    function reveal() {
        if (typeof window.iqbalReveal === 'function') window.iqbalReveal();
        else document.documentElement.classList.remove('iq-boot');
    }
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(reveal);
        setTimeout(reveal, 150);
    } else {
        reveal();
    }
})();
</script>

</body>
</html>
