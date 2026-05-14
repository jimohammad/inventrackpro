<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">

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
            font-family: 'Segoe UI', system-ui, sans-serif;
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
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0;
            transition: all 0.15s;
            font-size: 0.875rem;
        }
        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(99,102,241,0.2);
            color: #a5b4fc;
        }
        .sidebar-link.active {
            border-left: 3px solid #6366f1;
        }
        .sidebar-link i { font-size: 1rem; width: 20px; }

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
    <a href="?page=purchases" class="sidebar-link <?= ($page ?? '') === 'purchases' ? 'active' : '' ?>">
        <i class="bi bi-cart-plus"></i> Purchases
    </a>
    <a href="?page=sales" class="sidebar-link <?= ($page ?? '') === 'sales' ? 'active' : '' ?>">
        <i class="bi bi-bag-check"></i> Sales
    </a>
    <a href="?page=payments" class="sidebar-link <?= ($page ?? '') === 'payments' ? 'active' : '' ?>">
        <i class="bi bi-cash-stack"></i> Payments
    </a>
    <a href="?page=returns" class="sidebar-link <?= ($page ?? '') === 'returns' ? 'active' : '' ?>">
        <i class="bi bi-arrow-return-left"></i> Returns
    </a>
    <a href="?page=expenses" class="sidebar-link <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i> Expenses
    </a>

    <div class="sidebar-label">Inventory</div>
    <a href="?page=items" class="sidebar-link <?= ($page ?? '') === 'items' ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> Items
    </a>
    <a href="?page=categories" class="sidebar-link <?= ($page ?? '') === 'categories' ? 'active' : '' ?>">
        <i class="bi bi-tag"></i> Categories
    </a>
    <a href="?page=stock" class="sidebar-link <?= ($page ?? '') === 'stock' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-data"></i> Stock List
    </a>
    <a href="?page=transfers" class="sidebar-link <?= ($page ?? '') === 'transfers' ? 'active' : '' ?>">
        <i class="bi bi-arrow-left-right"></i> Stock Transfers
    </a>
    <a href="?page=imei" class="sidebar-link <?= ($page ?? '') === 'imei' ? 'active' : '' ?>">
        <i class="bi bi-upc-scan"></i> IMEI History
    </a>

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

    <div class="sidebar-label">Reports</div>
    <a href="?page=reports" class="sidebar-link <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i> All Reports
    </a>

    <?php if (Auth::can('settings', 'view')): ?>
    <div class="sidebar-label">Settings</div>
    <a href="?page=parties" class="sidebar-link <?= ($page ?? '') === 'parties' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Party Master
    </a>
    <?php if (Auth::isAdmin()): ?>
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
        <!-- Quick stats in topbar -->
        <span class="text-muted d-none d-md-block" style="font-size:0.8rem;">
            <i class="bi bi-calendar2 me-1"></i><?= date('d M Y') ?>
        </span>

        <!-- Current Warehouse Badge -->
        <?php if (Auth::warehouseId()): ?>
        <div class="d-flex align-items-center gap-1" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:4px 10px;">
            <i class="bi bi-building" style="color:#10b981;font-size:0.8rem;"></i>
            <span style="color:#10b981;font-size:0.8rem;font-weight:600;"><?= htmlspecialchars(Auth::warehouseName()) ?></span>
            <a href="?page=warehouse&switch=1" title="Switch warehouse"
               style="color:#10b981;margin-left:4px;font-size:0.75rem;text-decoration:none;opacity:0.7;" >
                <i class="bi bi-arrow-left-right"></i>
            </a>
        </div>
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
                <span class="d-none d-md-inline"><?= Auth::name() ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background:var(--bg-card);border-color:var(--border-color);">
                <li>
                    <span class="dropdown-item-text" style="color:var(--text-muted);font-size:0.8rem;">
                        <?= ucfirst(Auth::role()) ?>
                    </span>
                </li>
                <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                <li>
                    <a class="dropdown-item" href="?page=warehouse&switch=1">
                        <i class="bi bi-building me-2"></i>Switch Warehouse
                    </a>
                </li>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" defer></script>

<script>
// Mobile sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('open');
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

</body>
</html>
