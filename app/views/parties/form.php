<!-- Party Form -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=parties" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editMode) ? 'Edit Party' : 'New Party' ?></h1>
</div>

<?php if (!isset($editMode)): ?>
<!-- Type tabs for new party -->
<div class="d-flex gap-0 mb-4" style="border-radius:12px;overflow:hidden;border:2px solid #e0e7ff;background:#f8faff;max-width:400px;">
    <button type="button" id="tabCustomer"
        onclick="setPartyType('customer')"
        style="flex:1;padding:12px 20px;border:none;font-weight:700;font-size:0.95rem;cursor:pointer;transition:all 0.18s;background:rgba(16,185,129,0.15);color:#10b981;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="bi bi-person-fill"></i> Customer
    </button>
    <div style="width:2px;background:#e0e7ff;flex-shrink:0;"></div>
    <button type="button" id="tabSupplier"
        onclick="setPartyType('supplier')"
        style="flex:1;padding:12px 20px;border:none;font-weight:700;font-size:0.95rem;cursor:pointer;transition:all 0.18s;background:transparent;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="bi bi-truck"></i> Supplier
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
                            <?php if (!isset($editMode)): ?>
                            <input type="text" name="country" id="countryText" class="form-control" value="Kuwait" placeholder="Country">
                            <select name="country" id="countrySelect" class="form-select" style="display:none;" disabled>
                                <option value="UAE">UAE</option>
                                <option value="Hong Kong">Hong Kong</option>
                                <option value="China">China</option>
                            </select>
                            <?php else: ?>
                            <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($party['country'] ?? 'Kuwait') ?>">
                            <?php endif; ?>
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
                                $cc1 = '+965'; $num1 = '';
                                if ($phone1 && preg_match('/^(\+\d{1,4})\s*(.*)$/', $phone1, $m)) {
                                    $cc1 = $m[1]; $num1 = $m[2];
                                } elseif ($phone1) {
                                    $num1 = ltrim($phone1, '+');
                                }
                            ?>
                            <div class="d-flex gap-1">
                                <select id="cc1" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(1)">
                                    <option value="+965" <?= $cc1==='+965'?'selected':'' ?>>+965</option>
                                    <option value="+91"  <?= $cc1==='+91'?'selected':'' ?>>+91</option>
                                    <option value="+86"  <?= $cc1==='+86'?'selected':'' ?>>+86</option>
                                    <option value="+92"  <?= $cc1==='+92'?'selected':'' ?>>+92</option>
                                    <option value="+880" <?= $cc1==='+880'?'selected':'' ?>>+880</option>
                                    <option value="+971" <?= $cc1==='+971'?'selected':'' ?>>+971</option>
                                    <option value="+966" <?= $cc1==='+966'?'selected':'' ?>>+966</option>
                                    <option value="+974" <?= $cc1==='+974'?'selected':'' ?>>+974</option>
                                    <option value="+968" <?= $cc1==='+968'?'selected':'' ?>>+968</option>
                                    <option value="+973" <?= $cc1==='+973'?'selected':'' ?>>+973</option>
                                    <option value="+63"  <?= $cc1==='+63'?'selected':'' ?>>+63</option>
                                    <option value="+977" <?= $cc1==='+977'?'selected':'' ?>>+977</option>
                                    <option value="+94"  <?= $cc1==='+94'?'selected':'' ?>>+94</option>
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
                                $cc2 = '+965'; $num2 = '';
                                if ($phone2 && preg_match('/^(\+\d{1,4})\s*(.*)$/', $phone2, $m)) {
                                    $cc2 = $m[1]; $num2 = $m[2];
                                } elseif ($phone2) {
                                    $num2 = ltrim($phone2, '+');
                                }
                            ?>
                            <div class="d-flex gap-1">
                                <select id="cc2" style="width:90px;flex-shrink:0;padding:6px 4px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.85rem;font-weight:600;color:var(--text-main);background:var(--bg-card);cursor:pointer;" onchange="combinePhone(2)">
                                    <option value="+965" <?= $cc2==='+965'?'selected':'' ?>>+965</option>
                                    <option value="+91"  <?= $cc2==='+91'?'selected':'' ?>>+91</option>
                                    <option value="+86"  <?= $cc2==='+86'?'selected':'' ?>>+86</option>
                                    <option value="+92"  <?= $cc2==='+92'?'selected':'' ?>>+92</option>
                                    <option value="+880" <?= $cc2==='+880'?'selected':'' ?>>+880</option>
                                    <option value="+971" <?= $cc2==='+971'?'selected':'' ?>>+971</option>
                                    <option value="+966" <?= $cc2==='+966'?'selected':'' ?>>+966</option>
                                    <option value="+974" <?= $cc2==='+974'?'selected':'' ?>>+974</option>
                                    <option value="+968" <?= $cc2==='+968'?'selected':'' ?>>+968</option>
                                    <option value="+973" <?= $cc2==='+973'?'selected':'' ?>>+973</option>
                                    <option value="+63"  <?= $cc2==='+63'?'selected':'' ?>>+63</option>
                                    <option value="+977" <?= $cc2==='+977'?'selected':'' ?>>+977</option>
                                    <option value="+94"  <?= $cc2==='+94'?'selected':'' ?>>+94</option>
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

    var tc = document.getElementById('tabCustomer');
    var ts = document.getElementById('tabSupplier');
    if (tc && ts) {
        if (type === 'customer') {
            tc.style.background = 'rgba(16,185,129,0.85)';
            tc.style.color = '#fff';
            ts.style.background = 'transparent';
            ts.style.color = '#94a3b8';
        } else {
            ts.style.background = 'rgba(99,102,241,0.85)';
            ts.style.color = '#fff';
            tc.style.background = 'transparent';
            tc.style.color = '#94a3b8';
        }
    }

    var sup = (type === 'supplier');

    // Area field — hide for supplier
    var fa = document.getElementById('fieldArea');
    if (fa) fa.style.display = sup ? 'none' : '';

    // Kuwait Civil ID — hide for supplier
    var fi = document.getElementById('fieldIdCard');
    if (fi) fi.style.display = sup ? 'none' : '';

    // Credit Limit — hide for supplier
    var fc = document.getElementById('fieldCreditLimit');
    if (fc) fc.style.display = sup ? 'none' : '';

    // Country: text input for customer, dropdown for supplier
    var ct = document.getElementById('countryText');
    var cs = document.getElementById('countrySelect');
    if (ct && cs) {
        ct.style.display = sup ? 'none' : '';
        ct.disabled      = sup;
        cs.style.display = sup ? '' : 'none';
        cs.disabled      = !sup;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    combinePhone(1);
    combinePhone(2);
    // Activate default Customer tab on new party page
    if (document.getElementById('partyTypeInput')) {
        setPartyType('customer');
    }
});
</script>
