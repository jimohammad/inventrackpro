<?php require_once __DIR__ . '/../../helpers/PhoneInput.php'; ?>
<!-- Party Form -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=parties" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editMode) ? 'Edit Party' : 'New Party' ?></h1>
</div>

<?php if (!isset($editMode)): ?>
<!-- Type tabs for new party -->
<div class="d-flex gap-0 mb-4" style="border-radius:12px;overflow:hidden;border:2px solid #e0e7ff;background:#f8faff;max-width:560px;">
    <button type="button" id="tabCustomer" data-party-type="customer"
        style="flex:1;padding:12px 14px;border:none;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.18s;background:rgba(16,185,129,0.15);color:#10b981;display:flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-person-fill"></i> Customer
    </button>
    <div style="width:2px;background:#e0e7ff;flex-shrink:0;"></div>
    <button type="button" id="tabSupplier" data-party-type="supplier"
        style="flex:1;padding:12px 14px;border:none;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.18s;background:transparent;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-truck"></i> Supplier
    </button>
    <div style="width:2px;background:#e0e7ff;flex-shrink:0;"></div>
    <button type="button" id="tabFreight" data-party-type="freight_forwarder"
        style="flex:1;padding:12px 14px;border:none;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.18s;background:transparent;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-globe2"></i> Freight
    </button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <form method="POST" action="?page=parties&action=<?= isset($editMode) ? 'update' : 'store' ?>">
            <?= Auth::csrfField() ?>
            <?php if (isset($editMode)): ?>
            <input type="hidden" name="id" value="<?= $party['id'] ?>">
            <?php endif; ?>

            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(99,102,241,0.08);border-bottom:2px solid rgba(99,102,241,0.2);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
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
                            <small class="text-muted">Auto generated</small>
                        </div>
                        <?php if (isset($editMode)): ?>
                        <div class="col-md-3">
                            <label class="form-label fw-500">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <?php if (Auth::can('customers', 'add') || Auth::can('customers', 'edit')): ?>
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
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;">
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
                                <select id="cc1" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(1)">
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
                                <select id="cc2" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(2)">
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

            <!-- Financial -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(245,158,11,0.08);border-bottom:2px solid rgba(245,158,11,0.2);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;">
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
                            <small class="text-muted">Set to 0 for no limit. Agent cannot be issued invoices above this amount.</small>
                        </div>
                        <?php if (!isset($editMode)): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-500">Opening Balance</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(245,158,11,0.1);border-color:var(--border-color);color:#f59e0b;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="opening_balance" class="form-control" step="0.001" value="0.000">
                            </div>
                            <small class="text-muted">Any existing balance before using this system.</small>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($editMode)): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-500">Opening Balance</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(245,158,11,0.1);border-color:var(--border-color);color:#f59e0b;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="opening_balance" class="form-control" step="0.001"
                                       value="<?= number_format((float)($party['opening_balance'] ?? 0), DECIMAL_PLACES, '.', '') ?>"
                                       <?= Auth::isAdmin() ? '' : 'readonly' ?>>
                            </div>
                            <small class="text-muted"><?= Auth::isAdmin() ? 'Balance before using this system. Changes affect all reports.' : 'Only admin can change this.' ?></small>
                        </div>
                        <?php endif; ?>
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

<script>
function combinePhone(n) {
    var cc  = document.getElementById('cc' + n).value;
    var num = document.getElementById('num' + n).value.trim();
    document.getElementById('phone' + n + '_combined').value = num ? (cc + num) : '';
}

function setPartyType(type) {
    var inp = document.getElementById('partyTypeInput');
    if (!inp) return;
    inp.value = type;

    var tabs = {
        customer: document.getElementById('tabCustomer'),
        supplier: document.getElementById('tabSupplier'),
        freight_forwarder: document.getElementById('tabFreight')
    };
    var activeStyles = {
        customer: ['rgba(16,185,129,0.85)', '#fff'],
        supplier: ['rgba(99,102,241,0.85)', '#fff'],
        freight_forwarder: ['rgba(14,165,233,0.85)', '#fff']
    };
    Object.keys(tabs).forEach(function (key) {
        var tab = tabs[key];
        if (!tab) return;
        if (key === type) {
            tab.style.background = activeStyles[key][0];
            tab.style.color = activeStyles[key][1];
        } else {
            tab.style.background = 'transparent';
            tab.style.color = '#94a3b8';
        }
    });

    var sup = (type === 'supplier' || type === 'freight_forwarder');

    // Area field — hide for supplier
    var fa = document.getElementById('fieldArea');
    if (fa) fa.style.display = sup ? 'none' : '';

    // Kuwait Civil ID — hide for supplier
    var fi = document.getElementById('fieldIdCard');
    if (fi) fi.style.display = sup ? 'none' : '';

    // Credit Limit — hide for supplier
    var fc = document.getElementById('fieldCreditLimit');
    if (fc) fc.style.display = sup ? 'none' : '';

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
    if (document.getElementById('partyTypeInput')) {
        setPartyType('customer');
    }
});
</script>
