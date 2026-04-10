<?php
/**
 * Public Price List — No login required
 * Prices controlled by admin toggle from ERP backend
 * URL: https://iqbal.app/pricelist.php
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

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

// Get all active items with stock and category
$items = $db->fetchAll(
    "SELECT i.id, i.name, i.sku, i.brand, i.model, i.sale_price, i.has_imei, i.unit,
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

$companyName  = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_name'")['value'] ?? APP_NAME;
$companyPhone = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_phone'")['value'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Price List — <?= htmlspecialchars($companyName) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', -apple-system, sans-serif;
    background: #c8e6c9;
    color: #1e293b;
    min-height: 100vh;
}

/* Header */
.pl-header {
    background: #fff;
    border-bottom: 1px solid #a5d6a7;
    padding: 20px 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 6px rgba(46,125,50,0.1);
}
.pl-header-inner {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pl-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}
.pl-brand-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #2e7d32, #1b5e20);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
}
.pl-brand-name { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.pl-brand-sub { font-size: 0.72rem; color: #94a3b8; font-weight: 500; }
.pl-header-right {
    display: flex;
    align-items: center;
    gap: 14px;
}
.pl-contact {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pl-contact a { color: #2e7d32; text-decoration: none; font-weight: 600; }

/* Price status badge */
.pl-price-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.pl-price-status.on {
    background: #dcfce7;
    color: #15803d;
    animation: priceGlow 2s ease infinite;
}
.pl-price-status.off {
    background: #f1f5f9;
    color: #94a3b8;
}
.pl-price-status .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.pl-price-status.on .dot { background: #15803d; }
.pl-price-status.off .dot { background: #cbd5e1; }

@keyframes priceGlow {
    0%, 100% { box-shadow: 0 0 0 0 rgba(21,128,61,0.2); }
    50% { box-shadow: 0 0 0 6px rgba(21,128,61,0); }
}

/* Search */
.pl-search-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 16px 20px;
}
.pl-search {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid #a5d6a7;
    border-radius: 12px;
    font-size: 0.95rem;
    font-family: inherit;
    color: #1e293b;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.442.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") 14px center / 18px no-repeat;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.pl-search:focus {
    border-color: #2e7d32;
    box-shadow: 0 0 0 4px rgba(46,125,50,0.12);
}
.pl-search::placeholder { color: #94a3b8; }

/* Category pills */
.pl-cats {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.pl-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid #a5d6a7;
    background: #fff;
    color: #64748b;
    transition: all 0.15s;
}
.pl-cat-pill:hover { border-color: #2e7d32; color: #2e7d32; }
.pl-cat-pill.active {
    background: #2e7d32;
    color: #fff;
    border-color: #2e7d32;
}
.pl-cat-count {
    background: rgba(0,0,0,0.08);
    padding: 1px 7px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}
.pl-cat-pill.active .pl-cat-count { background: rgba(255,255,255,0.25); }

/* Main content */
.pl-main {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px 40px;
}

/* Category section */
.pl-section { margin-bottom: 20px; }
.pl-section-head {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    margin-bottom: 8px;
    background: #2e7d32;
    border-radius: 25px;
}
.pl-section-name {
    font-size: 1rem;
    font-weight: 800;
    color: #fff;
}
.pl-section-badge {
    font-size: 0.8rem;
    background: rgba(255,255,255,0.25);
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 700;
}

/* Item row */
.pl-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid #e8f5e9;
    border-radius: 10px;
    margin-bottom: 4px;
    transition: all 0.15s;
}
.pl-item:hover {
    border-color: #a5d6a7;
    box-shadow: 0 2px 8px rgba(46,125,50,0.08);
    transform: translateX(2px);
}
.pl-item-info { flex: 1; min-width: 0; }
.pl-item-name { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin-bottom: 1px; }
.pl-item-meta { font-size: 0.72rem; color: #94a3b8; }
.pl-item-price {
    font-size: 1rem;
    font-weight: 800;
    color: #1e293b;
    text-align: right;
    min-width: 110px;
    flex-shrink: 0;
}
.pl-item-price.locked {
    color: #cbd5e1;
    font-size: 0.8rem;
    font-weight: 600;
    font-style: italic;
}
.pl-item-stock {
    min-width: 70px;
    text-align: center;
    flex-shrink: 0;
}
.pl-stock-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
}
.pl-stock-badge.in { background: #dcfce7; color: #15803d; }
.pl-stock-badge.low { background: #fef9c3; color: #a16207; }
.pl-stock-badge.out { background: #fee2e2; color: #dc2626; }

/* Footer */
.pl-footer {
    text-align: center;
    padding: 20px;
    font-size: 0.75rem;
    color: #94a3b8;
    border-top: 1px solid #a5d6a7;
    max-width: 900px;
    margin: 0 auto;
}

.pl-empty {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.pl-empty i { font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.4; }

@media (max-width: 600px) {
    .pl-header-inner { flex-direction: column; gap: 8px; text-align: center; }
    .pl-header-right { flex-direction: column; gap: 6px; }
    .pl-item { flex-wrap: wrap; }
    .pl-item-price, .pl-item-stock { min-width: auto; }
}
</style>
</head>
<body>

<header class="pl-header">
    <div class="pl-header-inner">
        <div class="pl-brand">
            <div class="pl-brand-icon">IQ</div>
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
        <?php foreach ($catItems as $item): ?>
        <div class="pl-item" data-name="<?= htmlspecialchars(strtolower($item['name'] . ' ' . ($item['brand'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['sku'] ?? ''))) ?>">
            <div class="pl-item-info">
                <div class="pl-item-name"><?= htmlspecialchars($item['name']) ?></div>
                <?php if ($item['brand'] || $item['sku']): ?>
                <div class="pl-item-meta">
                    <?= htmlspecialchars(trim(($item['brand'] ?? '') . ($item['sku'] ? ' · ' . $item['sku'] : ''))) ?>
                </div>
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
    fetch('pricelist.php?check_prices=1&_=' + Date.now())
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
</body>
</html>
