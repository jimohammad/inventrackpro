<?php require_once __DIR__ . '/../../helpers/PhoneInput.php'; ?>
<?php if (!isset($editMode)): ?>
<style>
.party-type-picker { text-align: center; margin-bottom: 1.35rem; }
.party-type-tabs {
    display: flex;
    justify-content: center;
    gap: 10px;
}
.party-type-tab {
    flex: 1;
    max-width: 168px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    padding: 14px 10px 12px;
    border: 1.5px solid var(--border-color);
    border-radius: 0;
    background: var(--bg-card);
    color: var(--text-muted);
    cursor: pointer;
    font-family: inherit;
    font-weight: 700;
    font-size: 0.84rem;
    line-height: 1.2;
    transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}
.party-type-tab:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,23,42,0.08); }
.party-type-tab:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
.party-type-icon {
    width: 36px;
    height: 36px;
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: background 0.18s ease, color 0.18s ease;
}
.party-type-customer .party-type-icon { background: rgba(16,185,129,0.14); color: #10b981; }
.party-type-supplier .party-type-icon { background: rgba(99,102,241,0.14); color: #6366f1; }
.party-type-freight .party-type-icon { background: rgba(14,165,233,0.14); color: #0ea5e9; }
.party-type-customer.is-active {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
    box-shadow: 0 6px 16px rgba(16,185,129,0.28);
}
.party-type-supplier.is-active {
    background: #6366f1;
    border-color: #6366f1;
    color: #fff;
    box-shadow: 0 6px 16px rgba(99,102,241,0.28);
}
.party-type-freight.is-active {
    background: #0ea5e9;
    border-color: #0ea5e9;
    color: #fff;
    box-shadow: 0 6px 16px rgba(14,165,233,0.28);
}
.party-type-tab.is-active .party-type-icon {
    background: rgba(255,255,255,0.22);
    color: #fff;
}
@media (max-width: 575.98px) {
    .party-type-tabs { gap: 8px; }
    .party-type-tab { max-width: none; padding: 12px 6px 10px; font-size: 0.78rem; }
    .party-type-icon { width: 32px; height: 32px; font-size: 1rem; }
}
</style>
<?php endif; ?>
<style>
.customer-kind-toggle {
    display: flex;
    align-items: stretch;
    width: 100%;
    border: 1.5px solid var(--border-color);
    border-radius: 0;
    overflow: hidden;
    background: #f8fafc;
    min-height: 38px;
}
.party-form-page .card,
.party-form-page .card-header,
.party-form-page .form-control,
.party-form-page .form-select,
.party-form-page .btn,
.party-form-page .input-group-text,
.party-form-page .input-group > :not(:first-child),
.party-form-page .input-group > :not(:last-child),
.party-form-page .party-type-tab,
.party-form-page .party-type-icon,
.party-form-page .customer-kind-toggle,
.party-form-page .party-section-icon {
    border-radius: 0 !important;
}
.customer-kind-toggle button {
    flex: 1;
    border: none;
    border-radius: 0;
    background: transparent;
    color: #64748b;
    font-family: inherit;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 7px 14px;
    cursor: pointer;
    line-height: 1.2;
}
.customer-kind-toggle button + button {
    border-left: 1.5px solid var(--border-color);
}
.customer-kind-toggle button.is-active {
    background: #6366f1;
    color: #fff;
}
.customer-kind-toggle button:focus-visible { outline: 2px solid #6366f1; outline-offset: 2px; }
</style>
<!-- Party Form -->
<div class="party-form-page">
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=parties" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editMode) ? 'Edit Party' : htmlspecialchars($pageTitle ?? 'New Party') ?></h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <?php if (!isset($editMode)): ?>
        <div class="party-type-picker">
            <div class="party-type-tabs" role="tablist" aria-label="Party type">
                <button type="button" id="tabCustomer" class="party-type-tab party-type-customer is-active"
                    data-party-type="customer" role="tab" aria-selected="true">
                    <span class="party-type-icon"><i class="bi bi-person-fill"></i></span>
                    Customer
                </button>
                <?php if (Auth::can('suppliers', 'add')): ?>
                <button type="button" id="tabSupplier" class="party-type-tab party-type-supplier"
                    data-party-type="supplier" role="tab" aria-selected="false">
                    <span class="party-type-icon"><i class="bi bi-truck"></i></span>
                    Supplier
                </button>
                <button type="button" id="tabFreight" class="party-type-tab party-type-freight"
                    data-party-type="freight_forwarder" role="tab" aria-selected="false">
                    <span class="party-type-icon"><i class="bi bi-globe2"></i></span>
                    Freight
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <form method="POST" action="?page=parties&action=<?= isset($editMode) ? 'update' : 'store' ?>" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <?php if (isset($editMode)): ?>
            <input type="hidden" name="id" value="<?= $party['id'] ?>">
            <?php endif; ?>

            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(99,102,241,0.08);border-bottom:2px solid rgba(99,102,241,0.2);">
                    <div class="party-section-icon" style="width:32px;height:32px;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill" style="color:#6366f1;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Basic Information</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-500">Company / Party Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($party['name'] ?? '') ?>" placeholder="Business or party name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-500">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control"
                                value="<?= htmlspecialchars($party['contact_person'] ?? '') ?>" placeholder="Person name">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-500">Party ID</label>
                            <input type="text" class="form-control" readonly
                                value="<?= isset($editMode) ? $party['party_code'] : ($nextCode ?? '') ?>"
                                style="background:#f1f5f9;font-weight:700;color:#4338ca;letter-spacing:1px;">
                        </div>
                        <?php
                            $savedKind = Party::normalizeCustomerKind($party['customer_kind'] ?? null, $party['type'] ?? 'customer');
                            $showKindOnLoad = !isset($editMode) || in_array(($party['type'] ?? 'customer'), ['customer', 'both'], true);
                        ?>
                        <div class="col-md-3" id="fieldCustomerKind" style="<?= $showKindOnLoad ? '' : 'display:none;' ?>">
                            <label class="form-label fw-500 d-block">Customer pricing <span class="text-danger">*</span></label>
                            <input type="hidden" name="customer_kind" id="customerKindInput" value="<?= htmlspecialchars($savedKind) ?>">
                            <div class="customer-kind-toggle" role="radiogroup" aria-label="Customer pricing">
                                <button type="button" id="tabKindWholesale" data-customer-kind="wholesale"
                                    class="<?= $savedKind !== 'retail' ? 'is-active' : '' ?>"
                                    role="radio" aria-checked="<?= $savedKind !== 'retail' ? 'true' : 'false' ?>">Wholesale</button>
                                <button type="button" id="tabKindRetail" data-customer-kind="retail"
                                    class="<?= $savedKind === 'retail' ? 'is-active' : '' ?>"
                                    role="radio" aria-checked="<?= $savedKind === 'retail' ? 'true' : 'false' ?>">Retail</button>
                            </div>
                        </div>
                        <?php if (isset($editMode)): ?>
                        <div class="col-md-3">
                            <label class="form-label fw-500">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <?php if (Auth::canAny(['parties', 'customers'], 'add') || Auth::canAny(['parties', 'customers'], 'edit')): ?>
                                <option value="customer" <?= ($party['type'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer / Agent</option>
                                <?php endif; ?>
                                <?php if (Auth::can('suppliers', 'add') || Auth::can('suppliers', 'edit')): ?>
                                <option value="supplier" <?= ($party['type'] ?? '') === 'supplier' ? 'selected' : '' ?>>Supplier</option>
                                <option value="freight_forwarder" <?= ($party['type'] ?? '') === 'freight_forwarder' ? 'selected' : '' ?>>Freight forwarder</option>
                                <option value="both"     <?= ($party['type'] ?? '') === 'both'     ? 'selected' : '' ?>>Both</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="type" id="partyTypeInput" value="customer">
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label fw-500">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($party['address'] ?? '') ?>" placeholder="Street address">
                        </div>
                        <div class="col-md-4" id="fieldArea">
                            <label class="form-label fw-500">Area</label>
                            <select name="city" class="form-select">
                                <option value="">Select Area...</option>
                                <?php foreach (['Sharq','Fahaheel','Mahboula','Margab','Maliya','Jahra','Jaleeb','Souk Wataniya','Hawally','Salmiya','Khaitan','Farwaniya'] as $area): ?>
                                <option value="<?= $area ?>" <?= ($party['city'] ?? '') === $area ? 'selected' : '' ?>><?= $area ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Country</label>
                            <?php
                                $partyCountries = ['Kuwait', 'UAE', 'Hong Kong', 'China', 'Malaysia'];
                                $selectedCountry = trim((string)($party['country'] ?? 'Kuwait'));
                                if ($selectedCountry === '') {
                                    $selectedCountry = 'Kuwait';
                                }
                            ?>
                            <select name="country" id="countrySelect" class="form-select">
                                <?php if ($selectedCountry !== '' && !in_array($selectedCountry, $partyCountries, true)): ?>
                                <option value="<?= htmlspecialchars($selectedCountry) ?>" selected><?= htmlspecialchars($selectedCountry) ?></option>
                                <?php endif; ?>
                                <?php foreach ($partyCountries as $countryOption): ?>
                                <option value="<?= htmlspecialchars($countryOption) ?>" <?= $selectedCountry === $countryOption ? 'selected' : '' ?>><?= htmlspecialchars($countryOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="fieldIdCard">
                            <label class="form-label fw-500">Kuwait Civil ID</label>
                            <input type="text" name="id_card" class="form-control" maxlength="12"
                                value="<?= htmlspecialchars($party['id_card'] ?? '') ?>"
                                placeholder="12-digit Civil ID">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(16,185,129,0.08);border-bottom:2px solid rgba(16,185,129,0.2);">
                    <div class="party-section-icon" style="width:32px;height:32px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-telephone-fill" style="color:#10b981;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Contact Details</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-500">Phone</label>
                            <?php
                                $phone1 = $party['phone'] ?? '';
                                $split1 = PhoneInput::splitStoredPhone($phone1);
                                $cc1 = $split1['cc'];
                                $num1 = $split1['num'];
                            ?>
                            <div class="d-flex gap-1">
                                <select id="cc1" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:0;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(1)">
                                    <?php foreach (PhoneInput::PARTY_COUNTRY_CODES as $code): ?>
                                    <option value="<?= htmlspecialchars($code) ?>" <?= $cc1 === $code ? 'selected' : '' ?>><?= htmlspecialchars($code) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="num1" class="form-control" value="<?= htmlspecialchars($num1) ?>"
                                       placeholder="XXXX XXXX" maxlength="15" oninput="combinePhone(1)">
                            </div>
                            <input type="hidden" name="phone" id="phone1_combined" value="<?= htmlspecialchars($phone1) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Phone 2</label>
                            <?php
                                $phone2 = $party['phone2'] ?? '';
                                $split2 = PhoneInput::splitStoredPhone($phone2);
                                $cc2 = $split2['cc'];
                                $num2 = $split2['num'];
                            ?>
                            <div class="d-flex gap-1">
                                <select id="cc2" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:0;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(2)">
                                    <?php foreach (PhoneInput::PARTY_COUNTRY_CODES as $code): ?>
                                    <option value="<?= htmlspecialchars($code) ?>" <?= $cc2 === $code ? 'selected' : '' ?>><?= htmlspecialchars($code) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="num2" class="form-control" value="<?= htmlspecialchars($num2) ?>"
                                       placeholder="Optional" maxlength="15" oninput="combinePhone(2)">
                            </div>
                            <input type="hidden" name="phone2" id="phone2_combined" value="<?= htmlspecialchars($phone2) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($party['email'] ?? '') ?>" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade License (supplier / freight / both) -->
            <?php
                $editType = $party['type'] ?? 'customer';
                $showLicenseOnLoad = isset($editMode) && in_array($editType, ['supplier', 'both', 'freight_forwarder'], true);
                $licenseFile = $party['trade_license_file'] ?? '';
                $licenseExpiry = $party['trade_license_expires_on'] ?? '';
                $licenseExpired = $licenseExpiry !== '' && $licenseExpiry < date('Y-m-d');
            ?>
            <div class="card mb-3" id="sectionTradeLicense" style="<?= $showLicenseOnLoad ? '' : 'display:none;' ?>">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(239,68,68,0.06);border-bottom:2px solid rgba(239,68,68,0.18);">
                    <div class="party-section-icon" style="width:32px;height:32px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-file-earmark-text" style="color:#ef4444;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Trade License</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-500">License Expiry Date</label>
                            <input type="date" name="trade_license_expires_on" class="form-control"
                                value="<?= htmlspecialchars((string) $licenseExpiry) ?>">
                            <?php if ($licenseExpired): ?>
                            <small class="text-danger fw-semibold">Expired — renew and update the date.</small>
                            <?php else: ?>
                            <small class="text-muted">Dashboard alerts when this date has passed.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-500">License File (PDF or image)</label>
                            <input type="file" name="trade_license_file" class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                            <small class="text-muted">Max 5 MB. PDF, JPG, PNG, or WEBP.</small>
                            <?php if (!empty($licenseFile) && isset($editMode)): ?>
                            <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
                                <a href="?page=parties&action=downloadTradeLicense&id=<?= (int) $party['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                    <i class="bi bi-eye me-1"></i>View current file
                                </a>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="remove_trade_license" value="1" id="removeTradeLicense">
                                    <label class="form-check-label" for="removeTradeLicense">Remove file</label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(245,158,11,0.08);border-bottom:2px solid rgba(245,158,11,0.2);">
                    <div class="party-section-icon" style="width:32px;height:32px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-wallet2" style="color:#f59e0b;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Financial Settings</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6" id="fieldCreditLimit">
                            <label class="form-label fw-500">Credit Limit</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(245,158,11,0.1);border-color:var(--border-color);color:#f59e0b;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="credit_limit" class="form-control" step="0.001" min="0"
                                    value="<?= number_format((float)($party['credit_limit'] ?? 0), DECIMAL_PLACES, '.', '') ?>"
                                    placeholder="0.000 = no limit">
                            </div>
                            <small class="text-muted">0 = no limit. Any amount above 0 is a hard cap on unpaid invoices for every user, including admin. Collect payment to free credit — the sale screen cannot override it.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-500">Opening Balance</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(245,158,11,0.1);border-color:var(--border-color);color:#f59e0b;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="opening_balance" class="form-control" step="0.001" readonly
                                       value="<?= number_format((float)($party['opening_balance'] ?? 0), DECIMAL_PLACES, '.', '') ?>"
                                       title="Opening balance is locked">
                            </div>
                            <small class="text-muted">Locked — not editable. New parties start at 0; existing value is preserved on save.</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    <?= ($party['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="?page=parties" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i><?= isset($editMode) ? 'Update Party' : 'Save Party' ?>
                </button>
            </div>

        </form>
    </div>
</div>
</div>

<script>
function combinePhone(n) {
    var cc  = document.getElementById('cc' + n).value;
    var num = document.getElementById('num' + n).value.trim();
    document.getElementById('phone' + n + '_combined').value = num ? (cc + num) : '';
}

function setCustomerKind(kind) {
    var inp = document.getElementById('customerKindInput');
    if (inp) inp.value = kind === 'retail' ? 'retail' : 'wholesale';
    ['tabKindWholesale', 'tabKindRetail'].forEach(function (id) {
        var tab = document.getElementById(id);
        if (!tab) return;
        var on = tab.getAttribute('data-customer-kind') === (inp ? inp.value : kind);
        tab.classList.toggle('is-active', on);
        tab.setAttribute('aria-checked', on ? 'true' : 'false');
    });
}

function setPartyType(type) {
    var inp = document.getElementById('partyTypeInput');
    if (inp) inp.value = type;

    var tabs = {
        customer: document.getElementById('tabCustomer'),
        supplier: document.getElementById('tabSupplier'),
        freight_forwarder: document.getElementById('tabFreight')
    };
    Object.keys(tabs).forEach(function (key) {
        var tab = tabs[key];
        if (!tab) return;
        var on = key === type;
        tab.classList.toggle('is-active', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    var sup = (type === 'supplier' || type === 'freight_forwarder');
    var showLicense = (type === 'supplier' || type === 'freight_forwarder' || type === 'both');

    // Area field — hide for supplier
    var fa = document.getElementById('fieldArea');
    if (fa) fa.style.display = sup ? 'none' : '';

    // Kuwait Civil ID — hide for supplier
    var fi = document.getElementById('fieldIdCard');
    if (fi) fi.style.display = sup ? 'none' : '';

    // Credit Limit — hide for supplier
    var fc = document.getElementById('fieldCreditLimit');
    if (fc) fc.style.display = sup ? 'none' : '';

    // Wholesale / retail — customers and both only
    var fk = document.getElementById('fieldCustomerKind');
    if (fk) fk.style.display = (type === 'customer' || type === 'both') ? '' : 'none';
    if (type !== 'customer' && type !== 'both') {
        setCustomerKind('wholesale');
    }

    // Trade License — show for supplier / freight / both
    var tl = document.getElementById('sectionTradeLicense');
    if (tl) tl.style.display = showLicense ? '' : 'none';

}

document.addEventListener('DOMContentLoaded', function() {
    combinePhone(1);
    combinePhone(2);
    ['tabCustomer', 'tabSupplier', 'tabFreight'].forEach(function (id) {
        var tab = document.getElementById(id);
        if (!tab) return;
        tab.addEventListener('click', function () {
            setPartyType(tab.getAttribute('data-party-type'));
        });
    });
    ['tabKindWholesale', 'tabKindRetail'].forEach(function (id) {
        var tab = document.getElementById(id);
        if (!tab) return;
        tab.addEventListener('click', function () {
            setCustomerKind(tab.getAttribute('data-customer-kind'));
        });
    });
    if (document.getElementById('partyTypeInput')) {
        var initial = 'customer';
        try {
            var q = new URLSearchParams(window.location.search).get('type');
            if (q === 'supplier' || q === 'freight_forwarder' || q === 'customer' || q === 'both') {
                if ((q === 'supplier' || q === 'freight_forwarder') && !document.getElementById('tabSupplier')) {
                    q = 'customer';
                }
                initial = q;
            }
        } catch (e) {}
        setPartyType(initial);
    } else {
        var typeSelect = document.querySelector('select[name="type"]');
        if (typeSelect) {
            var syncType = function () { setPartyType(typeSelect.value); };
            typeSelect.addEventListener('change', syncType);
            syncType();
        }
    }
});
</script>
