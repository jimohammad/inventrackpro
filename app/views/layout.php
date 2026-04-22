<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Performance: preconnect to CDN sources -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= APP_NAME ?></title>
    <script>
        // Apply theme before CSS loads to prevent flash
        (function() {
            const t = localStorage.getItem('invt_theme') || 'dark';
            document.getElementById('htmlRoot').setAttribute('data-theme', t);
        })();
    </script>

    <!-- Preconnect to CDN for faster resource loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <!-- DataTables - only on list pages -->
    <?php $dtPages = ['sales','purchases','payments','returns','parties','expenses','stock','items','reports','purchaseorders','warranty','discounts']; ?>
    <?php if (isset($page) && in_array($page, $dtPages)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    <!-- Select2 - only on pages that need it -->
    <?php if (isset($page) && in_array($page, ['payments'])): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
    <?php endif; ?>

    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --bg-sidebar: #1e293b;
            --border-color: #334155;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --sidebar-width: 260px;
        }

        /* ── Light Theme ── */
        [data-theme="light"] {
            --bg-main:     #f1f5f9;
            --bg-card:     #ffffff;
            --bg-sidebar:  #1e293b;
            --border-color:#e2e8f0;
            --text-main:   #0f172a;
            --text-muted:  #64748b;
            --primary:     #6366f1;
            --primary-hover:#4f46e5;
        }
        [data-theme="light"] body { background: var(--bg-main); color: var(--text-main); }
        [data-theme="light"] .table { color: var(--text-main); }
        [data-theme="light"] .table th { background: #f8fafc !important; color: var(--text-muted); }
        [data-theme="light"] .table th.th-blue { background: #c7d2fe !important; color: #312e81 !important; }
        [data-theme="light"] .th-blue-card { background: #c7d2fe !important; color: #312e81 !important; }
        [data-theme="light"] .table tbody tr:hover { background: rgba(99,102,241,0.04); }
        [data-theme="light"] .form-control,
        [data-theme="light"] .form-select {
            background: #fff;
            border-color: var(--border-color);
            color: var(--text-main);
        }
        [data-theme="light"] .form-control:focus,
        [data-theme="light"] .form-select:focus {
            background: #fff;
            color: var(--text-main);
        }
        [data-theme="light"] .dropdown-menu { background: #fff; }
        [data-theme="light"] .dropdown-item { color: var(--text-main); }
        [data-theme="light"] .dropdown-item:hover { background: #f1f5f9; }
        [data-theme="light"] .select2-container--default .select2-selection--single { background:#fff; }
        [data-theme="light"] .select2-dropdown { background:#fff; }
        [data-theme="light"] .select2-results__option { color: var(--text-main); }
        [data-theme="light"] .select2-search__field { background:#fff !important; color:var(--text-main) !important; }
        [data-theme="light"] ::-webkit-scrollbar-thumb { background: #cbd5e1; }
        [data-theme="light"] .search-box input { background: #fff; color: var(--text-main); }

        /* Smooth transition on theme switch */
        body, .sidebar, .topbar, .card, .stat-card,
        .form-control, .form-select, .table, .main-content {
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }

        /* Theme toggle button */
        .theme-toggle {
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.25);
            color: var(--text-muted);
            border-radius: 8px;
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem;
            transition: all 0.15s;
        }
        .theme-toggle:hover { background: rgba(99,102,241,0.25); color: var(--primary); }

        * { box-sizing: border-box; }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 0.9rem;
        }

        /* ---- Sidebar ---- */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #1e293b;
            border-right: 1px solid #2d3f55;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #818cf8;
            border-bottom: 1px solid #2d3f55;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar-brand span { color: #f1f5f9; }

        .sidebar-label {
            padding: 0.75rem 1.5rem 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 1.5rem;
            color: #ffffff;
            text-decoration: none;
            border-radius: 0;
            transition: all 0.15s;
            font-size: 0.875rem;
        }
        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(99,102,241,0.2);
            color: #ffffff;
        }
        .sidebar-link.active {
            border-left: 3px solid #6366f1;
        }
        .sidebar-link i { font-size: 1rem; width: 20px; }

        /* Quick-create + button */
        .sidebar-link-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .sidebar-link-wrap .sidebar-link {
            flex: 1;
            padding-right: 2.2rem;
        }
        .quick-add-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 22px; height: 22px;
            border-radius: 6px;
            background: rgba(99,102,241,0.18);
            border: 1.5px solid rgba(99,102,241,0.4);
            color: #a5b4fc;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
            line-height: 1;
            cursor: pointer;
        }
        .quick-add-btn:hover {
            background: #6366f1 !important;
            border-color: #6366f1 !important;
            color: #fff !important;
            transform: translateY(-50%) scale(1.12);
        }

        /* ---- Top Bar ---- */
        .topbar {
            margin-left: var(--sidebar-width);
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 0 1.5rem;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Global Search */
        .search-box {
            position: relative;
            width: 320px;
        }
        .search-box input {
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 8px;
            padding: 0.45rem 1rem 0.45rem 2.5rem;
            width: 100%;
            font-size: 0.875rem;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .search-box .search-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-top: 4px;
            max-height: 360px;
            overflow-y: auto;
            display: none;
            z-index: 999;
        }
        .search-result-item {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
        }
        .search-result-item:hover { background: rgba(99,102,241,0.1); }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-type {
            font-size: 0.7rem;
            background: rgba(99,102,241,0.2);
            color: var(--primary);
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* ---- Main Content ---- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: calc(100vh - 58px);
        }

        /* ---- Cards ---- */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        .card-body { padding: 1.25rem; }

        /* Stat Cards on Dashboard */
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
        }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-card .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .stat-card .stat-label {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* ---- Tables ---- */
        .table {
            color: var(--text-main);
            border-color: var(--border-color);
        }
        .table th {
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-color: var(--border-color);
            background: rgba(15,23,42,0.4);
        }
        .table th.th-blue {
            background: rgba(99,102,241,0.4) !important;
            color: #c7d2fe !important;
        }
        .th-blue-card {
            background: rgba(99,102,241,0.4) !important;
            color: #c7d2fe !important;
        }
        .table td { border-color: var(--border-color); vertical-align: middle; }
        .table tbody tr:hover { background: rgba(99,102,241,0.05); }

        /* ---- Forms ---- */
        .form-control, .form-select {
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 8px;
        }
        .form-control:focus, .form-select:focus {
            background: var(--bg-main);
            border-color: var(--primary);
            color: var(--text-main);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .form-label { color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }

        /* JetBrains Mono for all code/number elements */
        code, pre, kbd,
        [style*="font-family:monospace"],
        [style*="font-family: monospace"],
        .font-monospace { font-family: 'SFMono-Regular', Menlo, Consolas, 'Courier New', monospace !important; }

        /* Inter font refinements */
        h1, h2, h3, h4, h5, h6, .page-title { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.02em; }
        .btn { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-weight: 500; letter-spacing: -0.01em; }
        .stat-value { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.03em; }
        table th { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-weight: 600; }
        table td { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        /* ---- Buttons ---- */
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }

        /* ---- Badges ---- */
        .badge-paid    { background: rgba(16,185,129,0.15); color: var(--success); }
        .badge-partial { background: rgba(245,158,11,0.15); color: var(--warning); }
        .badge-draft   { background: rgba(100,116,139,0.2); color: #94a3b8; }
        .badge-pending { background: rgba(245,158,11,0.15); color: var(--warning); }

        /* Flash messages */
        .flash-msg {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
            min-width: 280px;
            max-width: 400px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }

        /* Page title */
        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0;
        }
        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Select2 dark theme override */
        .select2-container--default .select2-selection--single {
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            height: 38px;
            border-radius: 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-main);
            line-height: 38px;
            padding-left: 12px;
        }
        .select2-dropdown {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }
        .select2-results__option {
            color: var(--text-main);
        }
        .select2-container--default .select2-results__option--highlighted {
            background: var(--primary);
        }
        .select2-search__field {
            background: var(--bg-main) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
        }

        /* Remove spinner arrows from all number inputs globally */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] { -moz-appearance: textfield; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar, .main-content { margin-left: 0; }
            .search-box { width: 200px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-boxes"></i>
        Inven<span>Track</span>
    </div>

    <div class="sidebar-label">Main</div>
    <a href="?page=dashboard" class="sidebar-link <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-label">Transactions</div>
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
        <a href="?page=sales" class="sidebar-link <?= ($page ?? '') === 'sales' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Sales
        </a>
        <?php if (Auth::can('sales', 'add')): ?>
        <a href="?page=sales&action=create" class="quick-add-btn" title="New Sale Invoice  (Alt+S)">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('payments', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=payments" class="sidebar-link <?= ($page ?? '') === 'payments' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i> Payments
        </a>
        <?php if (Auth::can('payments', 'add')): ?>
        <a href="?page=payments&action=create&type=in" class="quick-add-btn" title="New Payment In (Alt+I) / Out (Alt+O)">+</a>
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
    <!-- PARTY_SIDEBAR_V2 -->
    <?php if (Auth::can('customers', 'view') || Auth::can('suppliers', 'view')): ?>
    <div class="sidebar-label">Parties</div>
    <div class="sidebar-link-wrap">
        <a href="?page=parties" class="sidebar-link <?= ($page ?? '') === 'parties' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Party Master
        </a>
        <?php if (Auth::can('customers', 'add') || Auth::can('suppliers', 'add')): ?>
        <a href="?page=parties&action=create" class="quick-add-btn" title="New Party">+</a>
        <?php endif; ?>
    </div>
    <?php if (Auth::can('supplier_contacts', 'view')): ?>
    <div class="sidebar-link-wrap">
        <a href="?page=suppliercontacts" class="sidebar-link <?= ($page ?? '') === 'suppliercontacts' ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Supplier Contacts
        </a>
        <?php if (Auth::can('supplier_contacts', 'add')): ?>
        <a href="?page=suppliercontacts&action=add" class="quick-add-btn" title="New Contact">+</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (Auth::can('inventory', 'view')): ?>
    <div class="sidebar-label">Inventory</div>
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
    <?php if (Auth::can('stock', 'view')): ?>
    <a href="?page=stock" class="sidebar-link <?= ($page ?? '') === 'stock' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-data"></i> Stock List
    </a>
    <?php endif; ?>
    <a href="?page=transfers" class="sidebar-link <?= ($page ?? '') === 'transfers' ? 'active' : '' ?>">
        <i class="bi bi-arrow-left-right"></i> Stock Transfers
    </a>
    <a href="?page=openingstock" class="sidebar-link <?= ($page ?? '') === 'openingstock' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-down"></i> Opening Stock
    </a>
    <?php if (Auth::isAdmin() || Auth::can('imei', 'view')): ?>
    <a href="?page=imei&action=register" class="sidebar-link <?= ($page ?? '') === 'imei' && ($_GET['action'] ?? '') === 'register' ? 'active' : '' ?>">
        <i class="bi bi-upc-scan"></i> IMEI Scanner
    </a>
    <?php endif; ?>
    <a href="?page=imei&action=lifecycle" class="sidebar-link <?= ($page ?? '') === 'imei' && ($_GET['action'] ?? '') === 'lifecycle' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i> IMEI Lifecycle
    </a>
    <?php endif; ?>

    <?php if (Auth::can('payments', 'view') || Auth::can('expenses', 'view')): ?>
    <div class="sidebar-label">Finance</div>
    <a href="?page=accounts" class="sidebar-link <?= ($page ?? '') === 'accounts' ? 'active' : '' ?>">
        <i class="bi bi-wallet2"></i> Accounts
    </a>
    <a href="?page=landedcost" class="sidebar-link <?= ($page ?? '') === 'landedcost' ? 'active' : '' ?>">
        <i class="bi bi-calculator"></i> Landed Cost
    </a>
    <a href="?page=discounts" class="sidebar-link <?= ($page ?? '') === 'discounts' ? 'active' : '' ?>">
        <i class="bi bi-tag"></i> Discounts
    </a>
    <?php endif; ?>

    <?php if (Auth::can('reports', 'view')): ?>
    <div class="sidebar-label">Reports</div>
    <a href="?page=reports" class="sidebar-link <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i> All Reports
    </a>
    <?php endif; ?>

    <?php if (Auth::can('settings', 'view')): ?>
    <div class="sidebar-label">Settings</div>
    <?php if (Auth::isAdmin()): ?>
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
    <?php endif; ?>
    <?php endif; ?>

    <!-- Logout at bottom -->
    <div style="padding: 1.5rem;">
        <a href="?page=logout&action=logout" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</nav>

<!-- Top Bar -->
<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile toggle -->
        <button class="btn btn-sm d-md-none" id="sidebarToggle" style="color:var(--text-muted);background:transparent;border:none;">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Global Search -->
        <div class="search-box">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="globalSearch" placeholder="Search invoices, IMEI, parties..." autocomplete="off">
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- Keyboard shortcuts hint -->
        <div style="display:flex;align-items:center;gap:5px;font-size:0.72rem;color:#475569;" class="d-none d-lg-flex">
            <span style="background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);color:#818cf8;border-radius:5px;padding:2px 6px;font-family:monospace;font-weight:700;">Alt+S</span>Sale
            <span style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);color:#34d399;border-radius:5px;padding:2px 6px;font-family:monospace;font-weight:700;">Alt+P</span>Purchase
            <span style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.25);color:#fbbf24;border-radius:5px;padding:2px 6px;font-family:monospace;font-weight:700;">Alt+E</span>Expense
            <span style="background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);color:#60a5fa;border-radius:5px;padding:2px 6px;font-family:monospace;font-weight:700;">Alt+I/O</span>Payment
        </div>

        <!-- Current Warehouse Badge -->
        <?php if (Auth::warehouseId()): ?>
        <div class="d-flex align-items-center gap-1" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:4px 10px;">
            <i class="bi bi-building" style="color:#10b981;font-size:0.8rem;"></i>
            <span style="color:#10b981;font-size:0.8rem;font-weight:600;"><?= htmlspecialchars(Auth::warehouseName()) ?></span>
            <?php if (Auth::isAdmin()): ?>
            <a href="?page=warehouse&switch=1" title="Switch warehouse"
               style="color:#10b981;margin-left:4px;font-size:0.75rem;text-decoration:none;opacity:0.7;" >
                <i class="bi bi-arrow-left-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pricelist Toggle — only on stock page -->
        <?php if (($page ?? '') === 'stock'): ?>
        <button class="theme-toggle" id="pricelistBtn" title="Show prices on public pricelist for 3 min"
                style="position:relative;" onclick="togglePricelist()">
            <i class="bi bi-tag" id="pricelistIcon"></i>
            <span id="pricelistTimer" style="display:none;position:absolute;top:-6px;right:-6px;background:#dc2626;color:#fff;font-size:0.6rem;font-weight:800;padding:1px 5px;border-radius:10px;line-height:1.3;"></span>
        </button>
        <?php endif; ?>

        <!-- Theme Toggle -->
        <button class="theme-toggle" id="themeToggleBtn" title="Toggle light/dark theme">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>

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
                <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                <?php if (Auth::isAdmin()): ?>
                <li>
                    <a class="dropdown-item" href="?page=warehouse&switch=1">
                        <i class="bi bi-building me-2"></i>Switch Warehouse
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item" href="?page=logout&action=logout" style="color:var(--danger);">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<?php if (isset($page) && in_array($page, $dtPages ?? [])): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<?php endif; ?>
<?php if (isset($page) && in_array($page, ['payments'])): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<?php endif; ?>
<?php if (isset($page) && $page === 'reports'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>
<?php endif; ?>

<script>
// ── Report Export Helpers ──
function exportReportCSV(tableId, title) {
    var table = document.getElementById(tableId);
    if (!table) { alert('No table found to export.'); return; }
    var rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
    var csv = rows.map(function(row) {
        return Array.from(row.querySelectorAll('th, td')).map(function(cell) {
            return '"' + cell.innerText.replace(/"/g, '""').replace(/\r?\n/g, ' ').trim() + '"';
        }).join(',');
    });
    var blob = new Blob(['\uFEFF' + csv.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = title.replace(/[^a-z0-9]/gi, '_') + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
function exportReportPDF() { window.print(); }

// Keyboard shortcuts
// Mobile sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('open');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (!e.altKey) return;
    const key = e.key.toLowerCase();
    if (key === 's') { e.preventDefault(); window.location.href = '?page=sales&action=create'; }
    if (key === 'p') { e.preventDefault(); window.location.href = '?page=purchases&action=create'; }
    if (key === 'e') { e.preventDefault(); window.location.href = '?page=expenses&new=1'; }
    if (key === 'i') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=in'; }
    if (key === 'o') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=out'; }
});

// ── Theme Toggle ──
const htmlRoot   = document.getElementById('htmlRoot');
const themeBtn   = document.getElementById('themeToggleBtn');
const themeIcon  = document.getElementById('themeIcon');

function applyTheme(theme) {
    htmlRoot.setAttribute('data-theme', theme);
    localStorage.setItem('invt_theme', theme);
    if (theme === 'light') {
        themeIcon.className = 'bi bi-sun-fill';
        themeBtn.title = 'Switch to dark mode';
    } else {
        themeIcon.className = 'bi bi-moon-stars-fill';
        themeBtn.title = 'Switch to light mode';
    }
}

// Set correct icon on load
applyTheme(localStorage.getItem('invt_theme') || 'dark');

themeBtn?.addEventListener('click', () => {
    const current = htmlRoot.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

// Auto-dismiss flash messages
setTimeout(() => {
    document.querySelectorAll('.flash-msg .alert').forEach(el => {
        new bootstrap.Alert(el).close();
    });
}, 4000);

// Global Search (AJAX)
let searchTimeout;
const searchInput  = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

searchInput?.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`?page=dashboard&action=search&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.results || data.results.length === 0) {
                    searchResults.innerHTML = '<div class="search-result-item text-muted">No results found</div>';
                } else {
                    searchResults.innerHTML = data.results.map(r => `
                        <a href="${r.url}" class="search-result-item text-decoration-none d-flex align-items-center gap-2">
                            <span class="search-result-type">${r.type}</span>
                            <span style="color:var(--text-main)">${r.label}</span>
                            <small style="color:var(--text-muted);margin-left:auto">${r.sub ?? ''}</small>
                        </a>
                    `).join('');
                }
                searchResults.style.display = 'block';
            });
    }, 300);
});

// Close search when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-box')) {
        searchResults.style.display = 'none';
    }
});

// Initialize DataTables with dark styling
function initDataTable(selector, options = {}) {
    return $(selector).DataTable({
        pageLength: 25,
        responsive: true,
        language: { search: '', searchPlaceholder: 'Filter...' },
        ...options
    });
}
</script>

<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>

<!-- Admin PIN Verification Modal -->
<div id="pinModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
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
<style>
.pin-digit {
    width:48px;height:52px;text-align:center;font-size:1.4rem;font-weight:800;
    border:2px solid var(--border-color);border-radius:10px;outline:none;
    background:var(--bg-main);color:var(--text-main);transition:border-color 0.15s;
}
.pin-digit:focus { border-color:#f59e0b !important; box-shadow:0 0 0 4px rgba(245,158,11,0.3) !important; background:#fffbeb !important; }
.pin-digit.err { border-color:#ef4444; animation: pinShake 0.3s; }
@keyframes pinShake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)} }
</style>
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

// Universal PIN protection — intercepts all .pin-protect links and buttons
document.addEventListener('click', function(e) {
    var el = e.target.closest('.pin-protect');
    if (!el) return;
    if (_pinVerified) return;

    e.preventDefault();
    e.stopPropagation();

    if (el.tagName === 'A') {
        requirePin(function() { window.location = el.href; });
        return;
    }

    if (el.tagName === 'BUTTON' && el.type === 'submit') {
        var form = el.closest('form');
        if (form) {
            var isCancel = form.action && form.action.indexOf('cancel') !== -1;
            requirePin(function() {
                if (isCancel && !confirm('Are you sure you want to cancel this?')) return;
                form.submit();
            });
        }
        return;
    }
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

</body>
</html>
