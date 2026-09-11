<?php
$isEdit = isset($editMode);
$formAction = $isEdit ? 'update' : 'store';
$submitLabel = $isEdit ? 'Update Item' : 'Create Item';
$serialKind = (($item['serial_kind'] ?? 'phone') === 'tablet') ? 'tablet' : 'phone';
$catNamesJs = [];
foreach ($categories as $cat) {
    $catNamesJs[(int) $cat['id']] = strtolower((string) ($cat['name'] ?? ''));
}
?>
<style>
.item-form {
    --if-accent: var(--success, #16a34a);
    --if-accent-soft: rgba(22, 163, 74, 0.10);
    --if-radius: 0;
    --if-ease: 160ms ease;
    max-width: 880px;
    margin: 0 auto 2rem;
    color: var(--text-main);
    caret-color: var(--if-accent);
}
@media (prefers-reduced-motion: reduce) {
    .item-form *,
    .item-form *::before,
    .item-form *::after {
        transition: none !important;
        animation: none !important;
    }
}

.item-form__top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 1.15rem;
}
.item-form__top h1 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.item-form__top p {
    margin: 3px 0 0;
    font-size: 0.82rem;
    color: var(--text-muted);
}
.item-form__back {
    width: 38px;
    height: 38px;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    color: var(--text-main);
    text-decoration: none;
    flex-shrink: 0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: background var(--if-ease), border-color var(--if-ease), color var(--if-ease);
}
.item-form__back:hover {
    background: var(--if-accent-soft);
    border-color: var(--if-accent);
    color: var(--if-accent);
}
.item-form__back:focus-visible {
    outline: 2px solid var(--if-accent);
    outline-offset: 2px;
}

.item-form__card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--if-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 14px;
}
.item-form__card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    font-weight: 650;
    font-size: 0.9rem;
    color: var(--text-main);
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
}
.item-form__card-head i {
    width: 28px;
    height: 28px;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.item-form__card-head--id i { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
.item-form__card-head--price i { background: var(--if-accent-soft); color: var(--if-accent); }
.item-form__card-head--serial i { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
.item-form__card-head small {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--text-muted);
}
.item-form__body {
    padding: 18px 20px 20px;
}

.item-form .form-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.item-form__lang {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 16px;
    padding: 0 5px;
    border-radius: 0;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1;
}
.item-form__lang--en { background: rgba(37, 99, 235, 0.12); color: #1d4ed8; }
.item-form__lang--ar { background: rgba(14, 165, 233, 0.14); color: #0369a1; }

.item-form__grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.item-form__meta {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin-top: 16px;
}
.item-form__meta.is-edit {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto auto;
}

.item-form .form-control,
.item-form .form-select,
.item-form .btn,
.item-form .input-group-text,
.item-form .input-group > :not(:first-child),
.item-form .input-group > :not(:last-child) {
    border-radius: 0 !important;
}
.item-form .form-control,
.item-form .form-select {
    min-height: 44px;
    border-color: var(--border-color);
    background: #fff;
    color: var(--text-main);
    transition: border-color var(--if-ease), box-shadow var(--if-ease);
}
.item-form .form-control:hover,
.item-form .form-select:hover {
    border-color: #cbd5e1;
}
.item-form .form-control:focus,
.item-form .form-select:focus {
    border-color: var(--if-accent);
    box-shadow: 0 0 0 3px var(--if-accent-soft);
}
.item-form .form-control[dir="rtl"] {
    background: #f8fafc;
}
.item-form .form-control[dir="rtl"]:focus {
    background: #fff;
}
.item-form .input-group-text {
    border-radius: 0;
    background: #f8fafc;
    border-color: var(--border-color);
    color: var(--text-muted);
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    min-width: 52px;
    justify-content: center;
}
.item-form .input-group .form-control {
    border-radius: 0;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

.item-form__money {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.item-form__money-field {
    padding: 12px 14px 14px;
    border: 1px solid var(--border-color);
    border-radius: 0;
    background: #f8fafc;
}
.item-form__money-field--sale {
    background: var(--if-accent-soft);
    border-color: rgba(22, 163, 74, 0.22);
}
.item-form__money-field .form-label { margin-bottom: 8px; }
.item-form__stock {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.item-form__chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    padding: 0 14px;
    border: 1px solid var(--border-color);
    border-radius: 0;
    background: #fff;
    cursor: pointer;
    transition: border-color var(--if-ease), background var(--if-ease);
    white-space: nowrap;
}
.item-form__chip.is-on {
    border-color: var(--if-accent);
    background: var(--if-accent-soft);
}
.item-form__chip .form-check-input {
    width: 2.2em;
    height: 1.15em;
    margin: 0;
    cursor: pointer;
    flex-shrink: 0;
}
.item-form__chip .form-check-input:checked {
    background-color: var(--if-accent);
    border-color: var(--if-accent);
}
.item-form__chip label {
    margin: 0;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-main);
}

.item-form__toggle {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 0;
    border: 1px solid var(--border-color);
    background: #fff;
    cursor: pointer;
    transition: border-color var(--if-ease), background var(--if-ease);
}
.item-form__toggle:hover { border-color: #cbd5e1; }
.item-form__toggle.is-on {
    border-color: rgba(22, 163, 74, 0.35);
    background: var(--if-accent-soft);
}
.item-form__toggle .form-check {
    order: 2;
    margin-left: auto;
}
.item-form__toggle .form-check-input {
    width: 2.7rem;
    height: 1.4rem;
    cursor: pointer;
    margin: 0;
    flex-shrink: 0;
}
.item-form__toggle .form-check-input:checked {
    background-color: var(--if-accent);
    border-color: var(--if-accent);
}
.item-form__toggle .form-check-input:focus {
    box-shadow: 0 0 0 3px var(--if-accent-soft);
}
.item-form__toggle label {
    margin: 0;
    cursor: pointer;
    min-width: 0;
    flex: 1;
}
.item-form__toggle strong {
    display: block;
    font-size: 0.92rem;
    font-weight: 650;
    line-height: 1.3;
}
.item-form__toggle span {
    display: block;
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 3px;
}

.item-form__serial-kind {
    margin-top: 12px;
}
.item-form__serial-kind-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.item-form__serial-opts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.item-form__serial-opt {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 0;
    border: 1px solid var(--border-color);
    background: #fff;
    cursor: pointer;
    margin: 0;
    transition: border-color var(--if-ease), background var(--if-ease);
}
.item-form__serial-opt:hover { border-color: #cbd5e1; }
.item-form__serial-opt:focus-within {
    outline: 2px solid var(--if-accent);
    outline-offset: 2px;
}
.item-form__serial-opt:has(input:checked) {
    border-color: rgba(22, 163, 74, 0.45);
    background: var(--if-accent-soft);
}
.item-form__serial-opt input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.item-form__serial-icon {
    width: 36px;
    height: 36px;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #475569;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.item-form__serial-opt:has(input:checked) .item-form__serial-icon {
    background: var(--if-accent);
    color: #fff;
}
.item-form__serial-opt strong { display: block; font-size: 0.86rem; }
.item-form__serial-opt small { display: block; font-size: 0.74rem; color: var(--text-muted); margin-top: 2px; }

.item-form__actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 14px 4px 0;
}
.item-form__actions .btn {
    min-width: 120px;
    border-radius: 0 !important;
    padding: 0.58rem 1.15rem;
    font-weight: 600;
}
.item-form__actions .btn:focus-visible {
    outline: 2px solid var(--if-accent);
    outline-offset: 2px;
}
.item-form__actions .btn-primary {
    background: var(--if-accent);
    border-color: var(--if-accent);
    color: #fff;
    margin-left: auto;
}
.item-form__actions .btn-primary:hover {
    background: #15803d;
    border-color: #15803d;
}
.item-form__actions .btn-primary:disabled {
    opacity: 0.7;
    cursor: wait;
}

@media (max-width: 720px) {
    .item-form__grid-2,
    .item-form__money,
    .item-form__stock,
    .item-form__serial-opts { grid-template-columns: 1fr; }
    .item-form__meta,
    .item-form__meta.is-edit { grid-template-columns: 1fr; }
    .item-form__chip { width: 100%; justify-content: space-between; }
    .item-form__actions .btn-primary { margin-left: 0; width: 100%; }
}
</style>

<div class="item-form">
    <div class="item-form__top">
        <a href="?page=items" class="item-form__back" title="Back to items" aria-label="Back to items">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <div>
            <h1 class="page-title"><?= $isEdit ? 'Edit Item' : 'New Item' ?></h1>
            <p><?= $isEdit ? 'Update names, prices, and serial rules' : 'Add a product to the catalog' ?></p>
        </div>
    </div>

    <form method="POST" action="?page=items&action=<?= $formAction ?>" id="itemMasterForm">
        <?= Auth::csrfField() ?>
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <?php endif; ?>

        <section class="item-form__card">
            <div class="item-form__card-head item-form__card-head--id">
                <i class="bi bi-tag" aria-hidden="true"></i>
                Identity
            </div>
            <div class="item-form__body">
                <div class="item-form__grid-2">
                    <div>
                        <label class="form-label" for="itemName">
                            <span class="item-form__lang item-form__lang--en">EN</span>
                            Item name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="itemName" class="form-control" required
                            value="<?= htmlspecialchars($item['name'] ?? '') ?>"
                            placeholder="Samsung Galaxy A55 8/256"
                            autocomplete="off"
                            <?= $isEdit ? '' : 'autofocus' ?>>
                    </div>
                    <div>
                        <label class="form-label" for="itemNameAr">
                            <span class="item-form__lang item-form__lang--ar">AR</span>
                            الاسم بالعربية
                        </label>
                        <input type="text" name="name_ar" id="itemNameAr" class="form-control" dir="rtl" lang="ar"
                            value="<?= htmlspecialchars($item['name_ar'] ?? '') ?>"
                            placeholder="مثال: سماعات ريلمي بادز T100 لايت"
                            autocomplete="off">
                    </div>
                </div>

                <div class="item-form__meta<?= $isEdit ? ' is-edit' : '' ?>">
                    <div>
                        <label class="form-label" for="itemSku">SKU</label>
                        <input type="text" name="sku" id="itemSku" class="form-control"
                            value="<?= htmlspecialchars((string) ($item['sku'] ?? '')) ?>"
                            placeholder="Same code on both shops, e.g. A17-8-256"
                            maxlength="80"
                            autocomplete="off"
                            style="text-transform:uppercase;">
                    </div>
                    <div>
                        <label class="form-label" for="itemCategory">Category</label>
                        <select name="category_id" id="itemCategory" class="form-select">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= ($item['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="item-form__chip<?= !empty($item['has_nfc']) ? ' is-on' : '' ?>" data-toggle-card>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="has_nfc" value="1"
                                id="hasNfcCheck" <?= !empty($item['has_nfc']) ? 'checked' : '' ?>>
                        </div>
                        <label for="hasNfcCheck">NFC</label>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="item-form__chip<?= ($item['is_active'] ?? 1) ? ' is-on' : '' ?>" data-toggle-card>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="isActiveCheck" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                        <label for="isActiveCheck">Active</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="item-form__card">
            <div class="item-form__card-head item-form__card-head--price">
                <i class="bi bi-currency-exchange" aria-hidden="true"></i>
                Pricing &amp; stock
                <small>Cost updates from purchases</small>
            </div>
            <div class="item-form__body">
                <div class="item-form__money">
                    <div class="item-form__money-field">
                        <label class="form-label" for="purchasePrice">Real cost</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= APP_CURRENCY ?></span>
                            <input type="number" name="purchase_price" id="purchasePrice" class="form-control"
                                step="0.001" min="0"
                                value="<?= number_format((float)($item['purchase_price'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                        </div>
                    </div>
                    <div class="item-form__money-field item-form__money-field--sale">
                        <label class="form-label" for="salePrice">Sale price</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= APP_CURRENCY ?></span>
                            <input type="number" name="sale_price" id="salePrice" class="form-control"
                                step="0.001" min="0"
                                value="<?= number_format((float)($item['sale_price'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                        </div>
                    </div>
                </div>
                <div class="item-form__stock">
                    <div>
                        <label class="form-label" for="minStock">Minimum stock</label>
                        <input type="number" name="min_stock" id="minStock" class="form-control" min="0"
                            value="<?= (int)($item['min_stock'] ?? 20) ?>" placeholder="0 = no alert">
                    </div>
                    <div>
                        <label class="form-label" for="maxSaleQty">Salesman max qty</label>
                        <input type="number" name="max_sale_qty" id="maxSaleQty" class="form-control" min="0"
                            value="<?= (int)($item['max_sale_qty'] ?? 0) ?>" placeholder="0 = unlimited">
                    </div>
                </div>
            </div>
        </section>

        <section class="item-form__card">
            <div class="item-form__card-head item-form__card-head--serial">
                <i class="bi bi-upc-scan" aria-hidden="true"></i>
                Serial tracking
            </div>
            <div class="item-form__body">
                <div id="imeiToggleGrid">
                    <div class="item-form__toggle<?= ($item['has_imei'] ?? 1) ? ' is-on' : '' ?>" data-toggle-card>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="has_imei" value="1"
                                id="hasImeiCheck" <?= ($item['has_imei'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                        <label for="hasImeiCheck">
                            <strong>IMEI / serial required</strong>
                            <span>Scan every unit before sale</span>
                        </label>
                    </div>
                </div>
                <div id="serialKindWrap" class="item-form__serial-kind"<?= ($item['has_imei'] ?? 1) ? '' : ' hidden' ?>>
                    <div class="item-form__serial-kind-label">Serial type</div>
                    <div class="item-form__serial-opts">
                        <label class="item-form__serial-opt">
                            <input type="radio" name="serial_kind" value="phone" <?= $serialKind === 'phone' ? 'checked' : '' ?>>
                            <span class="item-form__serial-icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
                            <span>
                                <strong>Phone IMEI</strong>
                                <small>15–18 digits (H40 uses 13)</small>
                            </span>
                        </label>
                        <label class="item-form__serial-opt">
                            <input type="radio" name="serial_kind" value="tablet" <?= $serialKind === 'tablet' ? 'checked' : '' ?>>
                            <span class="item-form__serial-icon" aria-hidden="true"><i class="bi bi-tablet"></i></span>
                            <span>
                                <strong>Tablet serial</strong>
                                <small>Letters + numbers</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <div class="item-form__actions">
            <a href="?page=items" class="btn btn-outline-secondary">Cancel</a>
            <?php if ($isEdit && Auth::isAdmin() && Auth::can('inventory', 'delete')): ?>
            <button type="submit" form="itemDeleteForm" class="btn btn-outline-danger pin-protect"
                    data-confirm="Permanently delete this item? Only unused items (no sales/purchases/IMEI/stock) can be deleted. Admin PIN required.">
                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
            </button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" id="itemSubmitBtn">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i><?= $submitLabel ?>
            </button>
        </div>
    </form>
    <?php if ($isEdit && Auth::isAdmin() && Auth::can('inventory', 'delete')): ?>
    <form method="POST" action="?page=items&action=delete" id="itemDeleteForm" class="d-none">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
    </form>
    <?php endif; ?>
</div>

<script>
(function () {
    var nameInput = document.getElementById('itemName');
    <?php if (!$isEdit): ?>
    if (nameInput) nameInput.focus();
    <?php endif; ?>

    var hasImei = document.getElementById('hasImeiCheck');
    var form = document.getElementById('itemMasterForm');
    var submitBtn = document.getElementById('itemSubmitBtn');

    function syncCard(input) {
        var card = input.closest('[data-toggle-card]');
        if (card) card.classList.toggle('is-on', input.checked);
    }

    if (hasImei) {
        hasImei.addEventListener('change', function () {
            syncCard(hasImei);
            var wrap = document.getElementById('serialKindWrap');
            if (wrap) wrap.hidden = !hasImei.checked;
        });
        syncCard(hasImei);
    }

    var catNames = <?= json_encode($catNamesJs ?? new stdClass()) ?>;
    var catSelect = document.getElementById('itemCategory');
    function suggestTabletSerial() {
        if (!hasImei || !hasImei.checked) return;
        var blob = ((nameInput && nameInput.value) || '') + ' ' + (catNames[catSelect && catSelect.value] || '');
        blob = blob.toLowerCase();
        if (blob.indexOf('tablet') === -1 && blob.indexOf('galaxy tab') === -1) return;
        var tab = document.querySelector('input[name="serial_kind"][value="tablet"]');
        if (tab) tab.checked = true;
    }
    if (nameInput) nameInput.addEventListener('change', suggestTabletSerial);
    if (catSelect) catSelect.addEventListener('change', suggestTabletSerial);
    <?php if (!$isEdit): ?>
    suggestTabletSerial();
    <?php endif; ?>

    document.querySelectorAll('#hasNfcCheck, #isActiveCheck').forEach(function (el) {
        el.addEventListener('change', function () { syncCard(el); });
    });

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving…';
        });
    }
})();
</script>
