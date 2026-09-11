<?php
$canWriteOff = !empty($canWriteOff);
$totalSkus = count($stockList);
$visibleSkus = 0;
$below50Count = 0;
foreach ($stockList as $row) {
    $rowQty = (int) ($row['quantity'] ?? 0);
    if ($rowQty > 0) {
        $visibleSkus++;
        if ($rowQty < 50) {
            $below50Count++;
        }
    }
}
?>
<style>
/* Stock List — light indigo/purple chrome to match Sales, Purchases, Payments */
.stock-page .visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}
.stock-page {
    --sp-primary: #6366f1;
    --sp-primary-deep: #4f46e5;
    --sp-accent: #7c3aed;
    --sp-accent-soft: rgba(99, 102, 241, 0.12);
    --sp-warn: #d97706;
    --sp-warn-soft: rgba(217, 119, 6, 0.12);
    --sp-danger: #dc2626;
    --sp-danger-soft: rgba(220, 38, 38, 0.1);
    --sp-ok: #059669;
    --sp-ok-soft: rgba(5, 150, 105, 0.12);
    --sp-below50: #0f766e;
    --sp-below50-mid: #14b8a6;
    --sp-below50-soft: #f0fdfa;
    --sp-below50-hover: #ccfbf1;
    --sp-fg: #0f172a;
    --sp-muted: #64748b;
    --sp-surface: #ffffff;
    --sp-border: #c7d2fe;
    --sp-ring: #6366f1;
    --sp-radius: 16px;
    --sp-ease: 160ms ease;
    --sp-row-h: 34px;
    color: var(--sp-fg);
}
@media (prefers-reduced-motion: reduce) {
    .stock-page *,
    .stock-page *::before,
    .stock-page *::after {
        transition: none !important;
        animation: none !important;
    }
}

.stock-page__head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-bottom: 18px;
}
.stock-page__title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--sp-fg);
    line-height: 1.2;
}
.stock-page__title-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #818cf8 0%, #4f46e5 100%);
    color: #fff;
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.28);
    flex-shrink: 0;
    font-size: 1.05rem;
}
.stock-page__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-left: auto;
}
.stock-page__wh {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    background: linear-gradient(135deg, #eef2ff, #ede9fe);
    border: 1px solid var(--sp-border);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--sp-primary-deep);
}
.stock-page__count {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--sp-muted);
}
.stock-page__count strong {
    color: var(--sp-primary-deep);
    font-weight: 700;
}

.stock-shell {
    background: var(--sp-surface);
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(99, 102, 241, 0.08);
}
.stock-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    padding: 14px 16px;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border-bottom: 1px solid var(--sp-border);
}
.stock-toolbar__search {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1 1 220px;
    min-width: 180px;
    min-height: 40px;
    padding: 0 14px;
    background: var(--sp-surface);
    border: 1.5px solid var(--sp-border);
    border-radius: 10px;
    transition: border-color var(--sp-ease), box-shadow var(--sp-ease);
}
.stock-toolbar__search:focus-within {
    border-color: var(--sp-primary);
    box-shadow: 0 0 0 3px var(--sp-accent-soft);
}
.stock-toolbar__search i {
    color: var(--sp-primary);
    font-size: 0.95rem;
    flex-shrink: 0;
}
.stock-toolbar__search input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--sp-fg);
    font-size: 0.9rem;
    padding: 8px 0;
}
.stock-toolbar__search input::placeholder { color: #94a3b8; }
.stock-toolbar__chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.stock-toolbar__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}
.stock-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 10px;
    border: 0;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 3px 10px rgba(22, 163, 74, 0.28);
    transition: filter var(--sp-ease), transform var(--sp-ease);
}
.stock-action:hover {
    color: #fff;
    filter: brightness(1.06);
}
.stock-action:focus-visible {
    outline: 2px solid var(--sp-ring);
    outline-offset: 2px;
}
.stock-filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 10px;
    border: 1.5px solid var(--sp-border);
    background: var(--sp-surface);
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    user-select: none;
    transition: background var(--sp-ease), color var(--sp-ease), border-color var(--sp-ease), box-shadow var(--sp-ease);
}
.stock-filter i { font-size: 0.85rem; }
.stock-filter:hover {
    color: var(--sp-primary-deep);
    border-color: #a5b4fc;
    background: #faf5ff;
}
.stock-filter:has(input:focus-visible) {
    outline: 2px solid var(--sp-ring);
    outline-offset: 2px;
}
.stock-filter:has(input:checked) {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.32);
}
.stock-filter--alert:has(input:checked) {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 3px 10px rgba(217, 119, 6, 0.28);
}
.stock-filter--below50:has(input:checked) {
    background: linear-gradient(135deg, #2dd4bf, #0d9488);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 3px 10px rgba(13, 148, 136, 0.28);
}
/* Isolate the list so overflow-x cannot compute overflow-y:auto and shake the page. */
.stock-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
.stock-table {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}
.stock-table tbody tr.is-hidden { display: none; }
.stock-table thead th {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #5b21b6;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 10px 12px;
    border-bottom: 1px solid #ddd6fe;
    white-space: nowrap;
}
.stock-table tbody td {
    padding: 6px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f3e8ff;
    height: var(--sp-row-h);
}
.stock-table tbody tr:last-child td { border-bottom: none; }
.stock-table tbody tr {
    transition: background var(--sp-ease);
}
.stock-table tbody tr:nth-child(even) {
    background: #faf5ff;
}
.stock-table tbody tr:hover {
    background: #ede9fe;
}
.stock-table tbody tr.is-low,
.stock-table tbody tr.is-low:nth-child(even) {
    background: #fff7ed;
    box-shadow: inset 4px 0 0 #f59e0b;
}
.stock-table tbody tr.is-low:hover {
    background: #ffedd5;
}
.stock-table tbody tr.is-imei-over {
    background: #fef2f2;
}
.stock-table tbody tr.is-imei-over:hover {
    background: #fee2e2;
}
.stock-table tbody tr.is-below50,
.stock-table tbody tr.is-below50:nth-child(even) {
    background: var(--sp-below50-soft);
    box-shadow: inset 4px 0 0 var(--sp-below50-mid);
}
.stock-table tbody tr.is-below50:hover {
    background: var(--sp-below50-hover);
}
.stock-table__idx {
    width: 44px;
    text-align: center;
    color: var(--sp-muted);
    font-size: 0.78rem;
    font-variant-numeric: tabular-nums;
}
.stock-item__name {
    font-weight: 650;
    color: var(--sp-fg);
    letter-spacing: -0.01em;
}
.stock-diag {
    margin-top: 3px;
    max-width: 42rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #b91c1c;
    line-height: 1.35;
}
.stock-diag a {
    color: #7c3aed;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.stock-item__tags {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 8px;
    vertical-align: middle;
}
.stock-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.3;
    white-space: nowrap;
}
.stock-tag--muted {
    background: rgba(99, 102, 241, 0.12);
    color: #4f46e5;
}
.stock-tag--warn {
    background: var(--sp-warn-soft);
    color: var(--sp-warn);
}
.stock-price {
    font-weight: 650;
    font-variant-numeric: tabular-nums;
    color: var(--sp-fg);
    white-space: nowrap;
}
.stock-qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    min-width: 3.2rem;
    width: 3.2rem;
    padding: 1px 0;
    border-radius: 4px;
    font-weight: 750;
    font-variant-numeric: tabular-nums;
    font-size: 0.88rem;
    text-align: center;
}
.stock-qty--ok {
    background: var(--sp-ok-soft);
    color: var(--sp-ok);
}
.stock-qty--low {
    background: var(--sp-danger-soft);
    color: var(--sp-danger);
}
.stock-qty--below50 {
    background: #ccfbf1;
    color: #c2410c;
    box-shadow: 0 0 0 1px rgba(20, 184, 166, 0.35);
}
.stock-qty--zero {
    background: rgba(100, 116, 139, 0.12);
    color: #64748b;
}
.stock-qty--btn {
    border: 0;
    cursor: pointer;
    font-family: inherit;
    line-height: inherit;
}
.stock-qty--btn:hover,
.stock-qty--btn:focus-visible {
    outline: 2px solid var(--sp-ring);
    outline-offset: 1px;
}
.stock-imei {
    font-variant-numeric: tabular-nums;
    color: var(--sp-muted);
    font-size: 0.85rem;
}
.stock-imei--pending {
    color: var(--sp-warn);
    font-weight: 700;
}
.stock-imei--over {
    color: var(--sp-danger);
    font-weight: 700;
}
.stock-imei__link {
    color: inherit;
    text-decoration: none;
    border-bottom: 1px dotted currentColor;
    cursor: pointer;
}
.stock-imei__link:hover {
    color: var(--sp-accent);
}
.stock-min {
    color: var(--sp-muted);
    font-variant-numeric: tabular-nums;
}
.stock-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.stock-status__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.stock-status--ok { color: var(--sp-ok); }
.stock-status--ok .stock-status__dot { background: var(--sp-ok); }
.stock-status--low { color: var(--sp-warn); }
.stock-status--low .stock-status__dot { background: var(--sp-warn); }
.stock-status--below50 { color: var(--sp-below50); }
.stock-status--below50 .stock-status__dot { background: var(--sp-below50-mid); }
.stock-status--mismatch { color: var(--sp-danger); }
.stock-status--mismatch .stock-status__dot { background: var(--sp-danger); }

.stock-empty {
    text-align: center;
    padding: 48px 20px;
    color: var(--sp-muted);
}
.stock-empty i {
    display: block;
    font-size: 1.75rem;
    margin-bottom: 10px;
    color: #a78bfa;
}
.stock-empty strong {
    display: block;
    color: var(--sp-fg);
    font-size: 0.95rem;
    margin-bottom: 4px;
}
</style>

<div class="stock-page">
    <div class="stock-page__head">
        <h1 class="page-title stock-page__title">
            <span class="stock-page__title-icon" aria-hidden="true"><i class="bi bi-boxes"></i></span>
            Stock List
        </h1>
        <div class="stock-page__meta">
            <span class="stock-page__wh">
                <i class="bi bi-building" aria-hidden="true"></i>
                <?= htmlspecialchars(Auth::warehouseName()) ?>
            </span>
            <span class="stock-page__count" id="stockVisibleLabel" aria-live="polite">
                Showing <strong id="stockVisibleCount"><?= (int) $visibleSkus ?></strong> of <?= (int) $totalSkus ?> items
            </span>
            <?php if ($below50Count > 0): ?>
            <span class="stock-page__wh" style="background:linear-gradient(135deg,#f0fdfa,#ccfbf1);border-color:#99f6e4;color:#0f766e;">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <?= (int) $below50Count ?> below 50 pcs
            </span>
            <?php endif; ?>
            <?php if ($canWriteOff): ?>
            <span class="stock-page__count">Click a qty to write off missing units.</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="stock-shell">
        <div class="stock-toolbar" role="search">
            <div class="stock-toolbar__search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="stockSearch" class="visually-hidden">Search items</label>
                <input type="search" id="stockSearch" placeholder="Search by name, brand, or model…" autocomplete="off">
            </div>
            <div class="stock-toolbar__chips">
                <label class="stock-filter" for="stockInOnly">
                    <input type="checkbox" id="stockInOnly" class="visually-hidden" checked>
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                    In stock
                </label>
                <label class="stock-filter stock-filter--below50" for="stockBelow50">
                    <input type="checkbox" id="stockBelow50" class="visually-hidden">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    Below 50
                    <?php if ($below50Count > 0): ?>
                    (<?= (int) $below50Count ?>)
                    <?php endif; ?>
                </label>
                <label class="stock-filter stock-filter--alert" for="stockImeiMismatch">
                    <input type="checkbox" id="stockImeiMismatch" class="visually-hidden">
                    <i class="bi bi-exclamation-diamond" aria-hidden="true"></i>
                    IMEI mismatch
                </label>
            </div>
            <div class="stock-toolbar__actions">
                <a class="stock-action" href="?page=stock&action=pricelistPrint" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Pricelist PDF
                </a>
            </div>
        </div>
        <div class="stock-table-wrap">
            <table class="stock-table" id="stockTable">
                <thead>
                    <tr>
                        <th class="stock-table__idx" scope="col">#</th>
                        <th scope="col">Item</th>
                        <th class="text-end" scope="col">Selling price</th>
                        <th class="text-center" scope="col">Qty</th>
                        <th class="text-center" scope="col">IMEIs</th>
                        <th class="text-center" scope="col">Min</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockList)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="stock-empty">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                <strong>No stock data</strong>
                                <span>Items with quantity will appear here for this branch.</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($stockList as $i => $s): ?>
                    <?php
                        $qty = (int) ($s['quantity'] ?? 0);
                        $min = (int) ($s['min_stock'] ?? 0);
                        $isBelow50 = $qty > 0 && $qty < 50;
                        $isLow = $isBelow50 || ($min > 0 && $qty > 0 && $qty <= $min);
                        $hasImei = !empty($s['has_imei']);
                        $imeiInStock = (int) ($s['imei_in_stock'] ?? 0);
                        $inactive = empty($s['is_active']);
                        $imeiDelta = $hasImei ? ($qty - $imeiInStock) : 0;
                        $imeiPendingRow = $imeiDelta > 0 ? $imeiDelta : 0;
                        $imeiOverRow = $imeiDelta < 0 ? abs($imeiDelta) : 0;
                        $isImeiMismatch = $imeiPendingRow > 0 || $imeiOverRow > 0;
                        $qtyClass = $qty <= 0
                            ? 'stock-qty--zero'
                            : ($isBelow50 ? 'stock-qty--below50' : ($isLow ? 'stock-qty--low' : 'stock-qty--ok'));
                        $searchBlob = strtolower(trim(
                            ($s['name'] ?? '') . ' ' . ($s['brand'] ?? '') . ' ' . ($s['model'] ?? '') . ' ' . ($s['sku'] ?? '')
                        ));
                        $rowClass = trim(($isBelow50 ? 'is-below50 ' : ($isLow ? 'is-low ' : '')) . ($imeiOverRow > 0 ? 'is-imei-over ' : '') . ($qty <= 0 ? 'is-hidden' : ''));
                        $imeiHref = ($imeiOverRow > 0 || $imeiPendingRow > 0)
                            ? '?page=imei&action=audit&item_id=' . (int) ($s['item_id'] ?? 0)
                            : '?page=imei&item_id=' . (int) ($s['item_id'] ?? 0);
                        $diag = is_array($s['imei_diag'] ?? null) ? $s['imei_diag'] : null;
                    ?>
                    <tr data-qty="<?= $qty ?>"
                        data-item-id="<?= (int) ($s['item_id'] ?? 0) ?>"
                        data-name="<?= htmlspecialchars($s['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-has-imei="<?= $hasImei ? '1' : '0' ?>"
                        data-imei="<?= $imeiInStock ?>"
                        data-low="<?= $isLow ? '1' : '0' ?>"
                        data-below50="<?= $isBelow50 ? '1' : '0' ?>"
                        data-imei-pending="<?= $imeiPendingRow ?>"
                        data-imei-over="<?= $imeiOverRow ?>"
                        data-imei-mismatch="<?= $isImeiMismatch ? '1' : '0' ?>"
                        data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= htmlspecialchars($rowClass) ?>">
                        <td class="stock-table__idx"><?= $i + 1 ?></td>
                        <td>
                            <span class="stock-item__name"><?= htmlspecialchars($s['name']) ?></span>
                            <?php if ($imeiOverRow > 0 && $diag): ?>
                            <div class="stock-diag">
                                <?= htmlspecialchars((string) ($diag['likely_detail'] ?? 'IMEI count is higher than qty.')) ?>
                                <a href="<?= htmlspecialchars($imeiHref) ?>">Open IMEI audit</a>
                            </div>
                            <?php endif; ?>
                            <?php if ($inactive): ?>
                            <span class="stock-item__tags">
                                <span class="stock-tag stock-tag--muted">Inactive</span>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="stock-price"><?= APP_CURRENCY ?> <?= number_format((float) ($s['sale_price'] ?? 0), DECIMAL_PLACES) ?></span>
                        </td>
                        <td class="text-center">
                            <?php
                                $qtyTitle = $canWriteOff ? 'Write off to physical count' : '';
                                if ($isBelow50) {
                                    $qtyTitle = trim($qtyTitle . ($qtyTitle ? ' — ' : '') . 'Below 50 pcs');
                                }
                            ?>
                            <?php if ($canWriteOff): ?>
                            <button type="button"
                                    class="stock-qty stock-qty--btn <?= $qtyClass ?>"
                                    title="<?= htmlspecialchars($qtyTitle) ?>">
                                <?= $qty ?>
                            </button>
                            <?php else: ?>
                            <span class="stock-qty <?= $qtyClass ?>"<?= $qtyTitle !== '' ? ' title="' . htmlspecialchars($qtyTitle) . '"' : '' ?>><?= $qty ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($hasImei): ?>
                                <span class="stock-imei">
                                    <?php if ($imeiOverRow > 0): ?>
                                    <a class="stock-imei__link" href="<?= htmlspecialchars($imeiHref) ?>" title="Open IMEI history for this item">
                                        <?= $imeiInStock ?>
                                        <span class="stock-imei--over"> / <?= $imeiOverRow ?> over</span>
                                    </a>
                                    <?php elseif ($imeiPendingRow > 0): ?>
                                    <a class="stock-imei__link" href="<?= htmlspecialchars($imeiHref) ?>" title="Open IMEI audit to scan or write off missing units">
                                        <?= $imeiInStock ?>
                                        <span class="stock-imei--pending"> / <?= $imeiPendingRow ?> pending</span>
                                    </a>
                                    <?php else: ?>
                                    <?= $imeiInStock ?>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="stock-imei">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center stock-min"><?= $min ?></td>
                        <td>
                            <?php if ($imeiOverRow > 0): ?>
                            <span class="stock-status stock-status--mismatch" title="More IMEIs marked available than stock qty">
                                <span class="stock-status__dot" aria-hidden="true"></span>
                                Mismatch
                            </span>
                            <?php elseif ($isBelow50): ?>
                            <span class="stock-status stock-status--below50" title="Quantity below 50 pcs">
                                <span class="stock-status__dot" aria-hidden="true"></span>
                                Below 50
                            </span>
                            <?php elseif ($isLow): ?>
                            <span class="stock-status stock-status--low">
                                <span class="stock-status__dot" aria-hidden="true"></span>
                                Low
                            </span>
                            <?php else: ?>
                            <span class="stock-status stock-status--ok">
                                <span class="stock-status__dot" aria-hidden="true"></span>
                                OK
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canWriteOff): ?>
<div class="modal fade" id="stockWriteOffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="?page=stock&action=writeOffStock" id="stockWriteOffForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="item_id" id="woItemId" value="">
            <div class="modal-header" style="background:linear-gradient(135deg,rgba(139,92,246,0.12),rgba(99,102,241,0.06));border-bottom:1px solid #ddd6fe;">
                <h5 class="modal-title" style="font-size:1rem;font-weight:700;color:#4f46e5;">
                    <i class="bi bi-clipboard-minus me-1"></i>Write off missing stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" style="font-size:.9rem;font-weight:650;" id="woItemName"></p>
                <p class="small text-muted mb-3">
                    Enter the count on the shelf. Extra book qty is written off and will not come back on Rebuild stock.
                    If the supplier still owes the units, use a purchase return instead.
                </p>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1" for="woBookQty">On books</label>
                        <input type="text" class="form-control form-control-sm" id="woBookQty" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="woPhysicalQty">Physical count</label>
                        <input type="number" class="form-control form-control-sm" name="physical_qty" id="woPhysicalQty"
                               min="0" step="1" required>
                    </div>
                </div>
                <p class="small mb-2" id="woDelta" style="color:#92400e;font-weight:650;"></p>
                <p class="small text-muted mb-2" id="woImeiHint" style="display:none;"></p>
                <label class="form-label small mb-1" for="woNotes">Note (optional)</label>
                <input type="text" class="form-control form-control-sm" name="notes" id="woNotes"
                       maxlength="500" placeholder="e.g. Buds physical count 110">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm fw-bold" id="woSubmit"
                        style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;">Write off</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const searchEl = document.getElementById('stockSearch');
    const inOnlyEl = document.getElementById('stockInOnly');
    const below50El = document.getElementById('stockBelow50');
    const mismatchEl = document.getElementById('stockImeiMismatch');
    const rows = document.querySelectorAll('#stockTable tbody tr[data-qty]');
    const visibleCountEl = document.getElementById('stockVisibleCount');

    function applyStockFilter() {
        const q = (searchEl?.value || '').toLowerCase().trim();
        const inOnly = !!inOnlyEl?.checked;
        const below50Only = !!below50El?.checked;
        const mismatchOnly = !!mismatchEl?.checked;
        let visible = 0;

        rows.forEach(row => {
            const haystack = (row.dataset.search || row.textContent || '').toLowerCase();
            const qty = parseInt(row.dataset.qty || '0', 10) || 0;
            const okSearch = !q || haystack.includes(q);
            const okStock = !inOnly || qty > 0;
            const okBelow50 = !below50Only || row.dataset.below50 === '1';
            const okMismatch = !mismatchOnly || row.dataset.imeiMismatch === '1';
            const show = okSearch && okStock && okBelow50 && okMismatch;
            row.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });

        if (visibleCountEl) visibleCountEl.textContent = String(visible);
    }

    searchEl?.addEventListener('input', applyStockFilter);
    inOnlyEl?.addEventListener('change', applyStockFilter);
    below50El?.addEventListener('change', applyStockFilter);
    mismatchEl?.addEventListener('change', applyStockFilter);

    <?php if ($canWriteOff): ?>
    const woModalEl = document.getElementById('stockWriteOffModal');
    const woForm = document.getElementById('stockWriteOffForm');
    const woItemId = document.getElementById('woItemId');
    const woItemName = document.getElementById('woItemName');
    const woBookQty = document.getElementById('woBookQty');
    const woPhysicalQty = document.getElementById('woPhysicalQty');
    const woDelta = document.getElementById('woDelta');
    const woImeiHint = document.getElementById('woImeiHint');
    const woSubmit = document.getElementById('woSubmit');
    const woNotes = document.getElementById('woNotes');
    let woMin = 0;
    let woBook = 0;

    function woRefreshDelta() {
        const physical = parseInt(woPhysicalQty.value, 10);
        if (Number.isNaN(physical)) {
            woDelta.textContent = '';
            woSubmit.disabled = true;
            return;
        }
        if (physical < woMin) {
            woDelta.textContent = 'Cannot go below ' + woMin + ' scanned IMEIs.';
            woSubmit.disabled = true;
            return;
        }
        if (physical > woBook) {
            woDelta.textContent = 'Write-off can only reduce qty. Receive a purchase to add stock.';
            woSubmit.disabled = true;
            return;
        }
        const n = woBook - physical;
        woDelta.textContent = n === 0
            ? 'Qty already matches this count.'
            : 'Will write off ' + n + ' unit' + (n === 1 ? '' : 's') + '.';
        woSubmit.disabled = n <= 0;
    }

    document.getElementById('stockTable')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.stock-qty--btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (!row) return;
        woBook = parseInt(row.dataset.qty || '0', 10) || 0;
        const hasImei = row.dataset.hasImei === '1';
        const imei = parseInt(row.dataset.imei || '0', 10) || 0;
        woMin = hasImei ? imei : 0;
        woItemId.value = row.dataset.itemId || '';
        woItemName.textContent = row.dataset.name || '';
        woBookQty.value = String(woBook);
        woPhysicalQty.value = String(woBook);
        woPhysicalQty.min = String(woMin);
        woPhysicalQty.max = String(woBook);
        woNotes.value = '';
        if (hasImei) {
            woImeiHint.style.display = '';
            woImeiHint.textContent = imei + ' IMEI(s) scanned — physical count cannot go below that.';
        } else {
            woImeiHint.style.display = 'none';
            woImeiHint.textContent = '';
        }
        woRefreshDelta();
        const modal = bootstrap.Modal.getOrCreateInstance(woModalEl);
        modal.show();
        setTimeout(function () { woPhysicalQty.focus(); woPhysicalQty.select(); }, 300);
    });

    woPhysicalQty?.addEventListener('input', woRefreshDelta);

    woForm?.addEventListener('submit', function (e) {
        const physical = parseInt(woPhysicalQty.value, 10);
        const n = woBook - physical;
        if (Number.isNaN(physical) || n <= 0 || physical < woMin) {
            e.preventDefault();
            return;
        }
        if (!confirm('Write off ' + n + ' missing unit(s) of ' + (woItemName.textContent || 'this item')
            + '?\n\nQty will change from ' + woBook + ' to ' + physical + '.')) {
            e.preventDefault();
        }
    });
    <?php endif; ?>
})();
</script>
