<?php
/**
 * Public Price List — No login required
 * Prices controlled by admin toggle from ERP backend
 * URL: https://iqbal.app/pricelist (also /pricelist.php)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Prefer pretty URL so the PWA scope (/pricelist) matches install/start_url
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (
    !isset($_GET['check_prices'])
    && stripos($requestUri, 'pricelist.php') !== false
) {
    $query = $_GET;
    $qs = http_build_query($query);
    header('Location: /pricelist' . ($qs !== '' ? '?' . $qs : ''), true, 302);
    exit;
}

$db = Database::getInstance();

// STRICT: Check if prices should be visible (admin-controlled, server-side only)
// Prices are NEVER sent to the browser when hidden — no data-attributes, no hidden fields
$priceRow = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'pricelist_visible_until'");
$priceUntil = $priceRow['value'] ?? '2000-01-01';
$showPrices = strtotime($priceUntil) > time();
$priceSeconds = $showPrices ? max(0, strtotime($priceUntil) - time()) : 0;

// Cap at 180 seconds max — prevent tampering with the database value
if ($priceSeconds > 180) {
    $priceSeconds = 0;
    $showPrices = false;
}

// AJAX status check — returns ONLY status, never prices
if (isset($_GET['check_prices'])) {
    // Re-read fresh from DB each time
    $freshRow = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'pricelist_visible_until'");
    $freshUntil = $freshRow['value'] ?? '2000-01-01';
    $freshShow = strtotime($freshUntil) > time();
    $freshSec = $freshShow ? min(180, max(0, strtotime($freshUntil) - time())) : 0;
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['show' => $freshShow, 'seconds' => $freshSec, 'reload' => true]);
    exit;
}

// Items created within the last 7 days show a "New Arrival" badge
$newArrivalCutoff = date('Y-m-d H:i:s', strtotime('-7 days'));

// Get all active items with stock and category
$items = $db->fetchAll(
    "SELECT i.id, i.name, i.sku, i.brand, i.model, i.sale_price, i.has_imei,
            COALESCE(i.has_nfc, 0) AS has_nfc, i.unit, i.created_at,
            c.name as category_name, c.id as category_id,
            COALESCE(SUM(s.quantity), 0) as stock
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     LEFT JOIN stock s ON s.item_id = i.id
     WHERE i.is_active = 1
     GROUP BY i.id
     HAVING stock > 0
     ORDER BY c.name ASC, i.name ASC"
);

// Group by category
$grouped = [];
$allCount = 0;
foreach ($items as $item) {
    $cat = $item['category_name'] ?: 'Uncategorized';
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $grouped[$cat][] = $item;
    $allCount++;
}

$catCounts = [];
foreach ($grouped as $cat => $catItems) {
    $catCounts[$cat] = count($catItems);
}

$companyName  = 'Iqbal Electronics Co. LLC';
$companyPhone = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_phone'")['value'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#e6efe7">
<title>Price List — <?= htmlspecialchars($companyName) ?></title>
<link rel="apple-touch-icon" href="/assets/img/icare-logo.png">
<link rel="icon" type="image/png" href="/assets/img/icare-logo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --pl-bg: #e6efe7;
    --pl-text: #14261c;
    --pl-muted: #3f5648;
    --pl-soft: #4a6356;
    --pl-accent: #2e7d32;
    --pl-accent-ink: #1b5e20;
    --pl-price: #14532d;
    --pl-shadow: #c2d0c4;
    --pl-light: #ffffff;
    --pl-gutter: 20px;
    --pl-raised: 6px 6px 14px var(--pl-shadow), -6px -6px 14px var(--pl-light);
    --pl-raised-sm: 3px 3px 8px var(--pl-shadow), -3px -3px 8px var(--pl-light);
    --pl-inset: inset 4px 4px 9px var(--pl-shadow), inset -4px -4px 9px var(--pl-light);
    --pl-inset-sm: inset 3px 3px 6px var(--pl-shadow), inset -3px -3px 6px var(--pl-light);
    --pl-ease: 0.18s ease;
    color-scheme: light;
}

body {
    font-family: 'DM Sans', -apple-system, sans-serif;
    background: var(--pl-bg);
    color: var(--pl-text);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

::selection { background: #b7d7bc; color: var(--pl-text); }

/* Header — same base colour, raised so it floats */
.pl-header {
    background: var(--pl-bg);
    padding: 16px var(--pl-gutter) 10px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.pl-header-inner {
    max-width: 900px;
    margin: 0 auto;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--pl-bg);
    border-radius: 22px;
    box-shadow: var(--pl-raised);
}
.pl-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}
.pl-brand-icon {
    width: 44px;
    height: 44px;
    object-fit: contain;
    border-radius: 14px;
    flex-shrink: 0;
    display: block;
    background: var(--pl-bg);
    box-shadow: var(--pl-raised-sm);
}
.pl-brand-name {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--pl-text);
    letter-spacing: -0.02em;
}
.pl-brand-sub { font-size: 0.75rem; color: var(--pl-soft); font-weight: 500; }
.pl-header-right {
    display: flex;
    align-items: center;
    gap: 14px;
}
.pl-contact {
    font-size: 0.8rem;
    color: var(--pl-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}
.pl-contact a {
    color: var(--pl-accent-ink);
    text-decoration: none;
    font-weight: 700;
    border-radius: 8px;
}
.pl-contact a:focus-visible {
    outline: 2px solid var(--pl-accent-ink);
    outline-offset: 3px;
}

/* Price status — recessed when hidden, raised when visible */
.pl-price-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--pl-bg);
    color: var(--pl-muted);
    box-shadow: var(--pl-inset-sm);
}
.pl-price-status.on {
    color: var(--pl-accent-ink);
    box-shadow: var(--pl-raised-sm);
}
.pl-price-status .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.pl-price-status.on .dot {
    background: var(--pl-accent);
    animation: pricePulse 2s ease infinite;
}
.pl-price-status.off .dot { background: #5c6b62; }

@keyframes pricePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Search — inset well */
.pl-search-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 16px var(--pl-gutter) 8px;
}
.pl-search {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: none;
    border-radius: 16px;
    font-size: 1rem;
    font-family: inherit;
    font-weight: 500;
    color: var(--pl-text);
    caret-color: var(--pl-accent-ink);
    background: var(--pl-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%234a6356' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.442.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") 16px center / 18px no-repeat;
    outline: none;
    box-shadow: var(--pl-inset);
    -webkit-appearance: none;
    appearance: none;
    transition: box-shadow var(--pl-ease);
}
.pl-search:focus {
    box-shadow:
        inset 5px 5px 10px var(--pl-shadow),
        inset -5px -5px 10px var(--pl-light),
        0 0 0 3px rgba(46, 125, 50, 0.22);
}
.pl-search::placeholder { color: var(--pl-soft); }

/* Category pills — raised at rest, inset when pressed or selected */
.pl-cats {
    max-width: 900px;
    margin: 0 auto;
    padding: 6px var(--pl-gutter) 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.pl-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    min-height: 38px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: var(--pl-bg);
    color: var(--pl-muted);
    box-shadow: var(--pl-raised-sm);
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    transition: box-shadow var(--pl-ease), color var(--pl-ease);
}
.pl-cat-pill:active:not(.active) {
    box-shadow: var(--pl-inset-sm);
    color: var(--pl-accent-ink);
}
.pl-cat-pill.active {
    background: var(--pl-accent);
    color: #fff;
    box-shadow: inset 3px 3px 7px #1b5e20, inset -3px -3px 7px #5cb860;
}
.pl-cat-count {
    background: var(--pl-bg);
    color: var(--pl-accent-ink);
    padding: 1px 7px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.5;
    box-shadow: var(--pl-inset-sm);
}
.pl-cat-pill.active .pl-cat-count {
    background: transparent;
    color: #fff;
    box-shadow: none;
}

/* Main content */
.pl-main {
    max-width: 900px;
    margin: 0 auto;
    padding: 2px var(--pl-gutter) 20px;
}

/* Category section — raised chip, accent used as a small dot */
.pl-section { margin-bottom: 22px; }
.pl-section-head {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px 8px 16px;
    margin-bottom: 12px;
    background: var(--pl-bg);
    border-radius: 999px;
    box-shadow: var(--pl-raised-sm);
}
.pl-section-name {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 800;
    color: var(--pl-text);
    letter-spacing: -0.01em;
}
.pl-section-name::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--pl-accent);
    box-shadow: inset 1px 1px 2px #1b5e20, -1px -1px 2px #8fd18f;
    flex-shrink: 0;
}
.pl-section-badge {
    font-size: 0.75rem;
    background: var(--pl-bg);
    color: var(--pl-accent-ink);
    min-width: 1.7em;
    text-align: center;
    padding: 2px 7px;
    border-radius: 999px;
    font-weight: 700;
    box-shadow: var(--pl-inset-sm);
}

/* Item row — name | NFC column (near name) | spacer | stock | price */
.pl-item {
    display: grid;
    grid-template-columns: minmax(0, min(360px, 46%)) 46px minmax(8px, 1fr) auto auto;
    align-items: center;
    column-gap: 8px;
    padding: 12px 14px;
    background: var(--pl-bg);
    border: none;
    border-radius: 18px;
    margin-bottom: 16px;
    box-shadow: var(--pl-raised);
    transition: box-shadow var(--pl-ease);
}
.pl-item:active {
    box-shadow: 2px 2px 5px var(--pl-shadow), -1px -1px 4px var(--pl-light);
}
.pl-item-info { min-width: 0; }
.pl-item-name-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 1px;
}
.pl-item-name { font-size: 0.92rem; font-weight: 700; color: var(--pl-text); }
.pl-new-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #e8eefc;
    color: #1e3a8a;
    box-shadow: 2px 2px 5px #c5cce0, -2px -2px 5px #ffffff;
    flex-shrink: 0;
    line-height: 1.4;
}
.pl-item-nfc {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 1.4em;
}
.pl-nfc-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #e5f6f4;
    color: #0f5f5a;
    box-shadow: 2px 2px 5px #c3ddda, -2px -2px 5px #ffffff;
    line-height: 1.4;
}
.pl-item-meta { font-size: 0.75rem; color: var(--pl-soft); }
.pl-item-price {
    grid-column: 5;
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--pl-price);
    text-align: right;
    min-width: 110px;
    white-space: nowrap;
    background: var(--pl-bg);
    border-radius: 14px;
    padding: 8px 12px;
    box-shadow: var(--pl-raised-sm);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
}
.pl-item-price.locked {
    color: var(--pl-muted);
    font-size: 0.8rem;
    font-weight: 600;
    font-style: italic;
    box-shadow: var(--pl-inset-sm);
    letter-spacing: 0;
}
.pl-item-stock {
    grid-column: 4;
    min-width: 70px;
    text-align: center;
}
.pl-stock-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.01em;
}
.pl-stock-badge.in {
    background: #e5f6e8;
    color: #146c2e;
    box-shadow: 2px 2px 5px #c5dcc9, -2px -2px 5px #ffffff;
}
.pl-stock-badge.low {
    background: #fff6dc;
    color: #7c4a03;
    box-shadow: 2px 2px 5px #e6d7b0, -2px -2px 5px #ffffff;
}
.pl-stock-badge.out {
    background: #fde8e8;
    color: #9f1239;
    box-shadow: 2px 2px 5px #e4c8c8, -2px -2px 5px #ffffff;
}

/* Footer — inset strip */
.pl-footer {
    text-align: center;
    padding: 16px 18px;
    font-size: 0.78rem;
    line-height: 1.5;
    color: var(--pl-muted);
    background: var(--pl-bg);
    border-radius: 18px;
    box-shadow: var(--pl-inset);
    width: calc(100% - (var(--pl-gutter) * 2));
    max-width: calc(900px - (var(--pl-gutter) * 2));
    margin: 0 auto 28px;
}

.pl-empty {
    text-align: center;
    padding: 48px 20px;
    color: var(--pl-muted);
    background: var(--pl-bg);
    border-radius: 20px;
    box-shadow: var(--pl-inset);
}
.pl-empty i {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
    color: var(--pl-accent-ink);
}

@media (hover: hover) and (pointer: fine) {
    .pl-cat-pill:hover:not(.active) { color: var(--pl-accent-ink); }
    .pl-item:hover {
        box-shadow: 8px 8px 18px var(--pl-shadow), -8px -8px 18px var(--pl-light);
    }
    .pl-contact a:hover { text-decoration: underline; }
}

@media (max-width: 600px) {
    :root {
        --pl-gutter: 12px;
        --pl-raised: 5px 5px 12px var(--pl-shadow), -4px -4px 12px var(--pl-light);
        --pl-raised-sm: 3px 3px 7px var(--pl-shadow), -3px -3px 7px var(--pl-light);
    }
    .pl-header-inner {
        flex-direction: column;
        align-items: center;
        gap: 10px;
        text-align: center;
        padding: 16px 14px;
        border-radius: 20px;
    }
    .pl-header-right { flex-direction: column; gap: 8px; }
    .pl-cat-pill { min-height: 40px; }
    .pl-item {
        grid-template-columns: minmax(0, 1fr) 42px auto;
        row-gap: 6px;
        padding: 12px;
        border-radius: 16px;
    }
    .pl-item-info { grid-column: 1; grid-row: 1 / span 2; }
    .pl-item-nfc { grid-column: 2; grid-row: 1 / span 2; }
    .pl-item-stock { grid-column: 3; grid-row: 1; min-width: auto; justify-self: end; }
    .pl-item-price {
        grid-column: 3;
        grid-row: 2;
        min-width: auto;
        justify-self: end;
        padding: 6px 10px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
</head>
<body>

<header class="pl-header">
    <div class="pl-header-inner">
        <div class="pl-brand">
            <img class="pl-brand-icon" src="/assets/img/icare-logo.png" alt="iCARE" width="44" height="44">
            <div>
                <div class="pl-brand-name"><?= htmlspecialchars($companyName) ?></div>
                <div class="pl-brand-sub">Product Catalog & Price List</div>
            </div>
        </div>
        <div class="pl-header-right">
            <span class="pl-price-status <?= $showPrices ? 'on' : 'off' ?>" id="priceStatus">
                <span class="dot"></span>
                <span id="priceStatusText"><?= $showPrices ? 'Prices visible' : 'Prices hidden' ?></span>
                <span id="priceCountdown" style="<?= $showPrices ? '' : 'display:none;' ?>">(<?= floor($priceSeconds/60) ?>:<?= str_pad($priceSeconds%60, 2, '0', STR_PAD_LEFT) ?>)</span>
            </span>
            <?php if ($companyPhone): ?>
            <div class="pl-contact">
                <i class="bi bi-telephone"></i>
                <a href="tel:<?= htmlspecialchars($companyPhone) ?>"><?= htmlspecialchars($companyPhone) ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="pl-search-wrap">
    <input type="text" class="pl-search" id="plSearch" placeholder="Search by item name, brand, model..." autocomplete="off">
</div>

<div class="pl-cats" id="plCats">
    <div class="pl-cat-pill active" data-cat="all">
        All <span class="pl-cat-count"><?= $allCount ?></span>
    </div>
    <?php foreach ($catCounts as $cat => $count): ?>
    <div class="pl-cat-pill" data-cat="<?= htmlspecialchars($cat) ?>">
        <?= htmlspecialchars($cat) ?> <span class="pl-cat-count"><?= $count ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="pl-main" id="plMain">
    <?php foreach ($grouped as $cat => $catItems): ?>
    <div class="pl-section" data-section="<?= htmlspecialchars($cat) ?>">
        <div class="pl-section-head">
            <span class="pl-section-name"><?= htmlspecialchars($cat) ?></span>
            <span class="pl-section-badge"><?= count($catItems) ?></span>
        </div>
        <?php foreach ($catItems as $item):
            $isNewArrival = !empty($item['created_at']) && $item['created_at'] >= $newArrivalCutoff;
        ?>
        <div class="pl-item" data-name="<?= htmlspecialchars(strtolower($item['name'] . ' ' . ($item['brand'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['sku'] ?? ''))) ?>">
            <div class="pl-item-info">
                <div class="pl-item-name-row">
                    <div class="pl-item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <?php if ($isNewArrival): ?>
                    <span class="pl-new-badge">New Arrival</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['brand'])): ?>
                <div class="pl-item-meta">
                    <?= htmlspecialchars((string) $item['brand']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="pl-item-nfc">
                <?php if (!empty($item['has_nfc'])): ?>
                <span class="pl-nfc-badge" title="NFC available">NFC</span>
                <?php endif; ?>
            </div>
            <div class="pl-item-stock">
                <?php if ((int)$item['stock'] > 10): ?>
                <span class="pl-stock-badge in">In Stock</span>
                <?php else: ?>
                <span class="pl-stock-badge low">Low Stock</span>
                <?php endif; ?>
            </div>
            <div class="pl-item-price <?= $showPrices ? '' : 'locked' ?>">
                <?php if ($showPrices): ?>
                    <?= APP_CURRENCY ?> <?= number_format($item['sale_price'], DECIMAL_PLACES) ?>
                <?php else: ?>
                    Ask for price
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <div class="pl-empty" id="plEmpty" style="display:none;">
        <i class="bi bi-search"></i> No items found
    </div>
</div>

<div class="pl-footer">
    <?= htmlspecialchars($companyName) ?> &mdash; Prices may change without notice &mdash; Updated <?= date('d M Y, h:i A') ?>
</div>

<script>
// Search
document.getElementById('plSearch').addEventListener('input', function() {
    filterItems(this.value.toLowerCase().trim(), getActiveCat());
});

// Category filter
document.getElementById('plCats').addEventListener('click', function(e) {
    var pill = e.target.closest('.pl-cat-pill');
    if (!pill) return;
    document.querySelectorAll('.pl-cat-pill').forEach(function(p) { p.classList.remove('active'); });
    pill.classList.add('active');
    filterItems(document.getElementById('plSearch').value.toLowerCase().trim(), pill.dataset.cat);
});

function getActiveCat() {
    var active = document.querySelector('.pl-cat-pill.active');
    return active ? active.dataset.cat : 'all';
}

function filterItems(q, cat) {
    var sections = document.querySelectorAll('.pl-section');
    var totalVisible = 0;
    sections.forEach(function(sec) {
        var catMatch = (cat === 'all' || sec.dataset.section === cat);
        if (!catMatch) { sec.style.display = 'none'; return; }
        var items = sec.querySelectorAll('.pl-item');
        var vis = 0;
        items.forEach(function(item) {
            var match = !q || item.dataset.name.includes(q);
            item.style.display = match ? '' : 'none';
            if (match) vis++;
        });
        sec.style.display = vis > 0 ? '' : 'none';
        totalVisible += vis;
    });
    document.getElementById('plEmpty').style.display = totalVisible === 0 ? '' : 'none';
}

// ══ STRICT PRICE PROTECTION ══
// Layer 1: Hard deadline timestamp (survives tab sleep)
// Layer 2: setInterval countdown (cosmetic + backup)
// Layer 3: setTimeout hard kill (absolute guarantee)
// Layer 4: visibilitychange handler (catches tab wake-up)
// Layer 5: Server poll every 5s

var priceActive = <?= $showPrices ? 'true' : 'false' ?>;
var priceRemaining = <?= $priceSeconds ?>;
var priceDeadline = Date.now() + (priceRemaining * 1000); // absolute timestamp

// Immediately hide prices from DOM (no reload needed — just wipe the text)
function killPrices() {
    document.querySelectorAll('.pl-item-price').forEach(function(el) {
        el.className = 'pl-item-price locked';
        el.textContent = 'Ask for price';
    });
    var status = document.getElementById('priceStatus');
    status.className = 'pl-price-status off';
    document.getElementById('priceStatusText').textContent = 'Prices hidden';
    document.getElementById('priceCountdown').style.display = 'none';
    priceActive = false;
}

// Check if deadline has passed (works even if timers were frozen)
function checkDeadline() {
    if (!priceActive) return;
    var now = Date.now();
    if (now >= priceDeadline) {
        killPrices();
        return;
    }
    var secsLeft = Math.ceil((priceDeadline - now) / 1000);
    var m = Math.floor(secsLeft / 60);
    var s = secsLeft % 60;
    document.getElementById('priceCountdown').textContent = '(' + m + ':' + (s < 10 ? '0' : '') + s + ')';
}

if (priceActive && priceRemaining > 0) {
    // Layer 2: Countdown every second
    setInterval(checkDeadline, 1000);

    // Layer 3: Hard setTimeout at exact deadline (backup)
    setTimeout(killPrices, priceRemaining * 1000);

    // Layer 4: When tab becomes visible again, check immediately
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) checkDeadline();
    });

    // Also check on any user interaction (scroll, click, touch)
    ['scroll', 'click', 'touchstart'].forEach(function(evt) {
        document.addEventListener(evt, checkDeadline, { passive: true });
    });
}

// Layer 5: Server poll every 5 seconds for admin toggle
setInterval(function() {
    fetch('/pricelist?check_prices=1&_=' + Date.now())
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.show && !priceActive) {
                // Admin turned ON — reload to get prices from server
                window.location.reload();
            }
            if (!res.show && priceActive) {
                // Admin turned OFF or expired — kill immediately
                killPrices();
            }
            if (res.show && res.seconds > 0) {
                priceDeadline = Date.now() + (res.seconds * 1000);
            }
        })
        .catch(function() {});
}, 5000);
</script>
<script src="/assets/pwa/apps/nav.js" defer></script>
</body>
</html>
