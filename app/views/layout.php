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

    <link rel="preload" href="assets/css/layout.css?v=20260425" as="style">
    <link rel="preload" href="assets/js/app.js?v=20260425" as="script">
    <link rel="stylesheet" href="assets/css/layout.css?v=20260425">
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
        <a href="?page=payments&action=receive" class="quick-add-btn" title="Receive Payment">+</a>
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
    <?php if (Auth::isAdmin()): ?>
    <a href="?page=imei&action=audit" class="sidebar-link <?= ($page ?? '') === 'imei' && ($_GET['action'] ?? '') === 'audit' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check"></i> Stock Audit
    </a>
    <?php endif; ?>
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
<script src="assets/js/app.js?v=20260425"></script>

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
