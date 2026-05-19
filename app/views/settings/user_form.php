<!-- New / Edit User Form -->
<style>
.perm-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.perm-table th {
    background:#1e3a5f; color:#fff; padding:9px 14px;
    font-size:0.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.4px; border:none; white-space:nowrap;
}
.perm-table td {
    padding:9px 14px; border-bottom:1px solid var(--border-color);
    vertical-align:middle;
}
.perm-table tbody tr:hover { background:rgba(99,102,241,0.04); }
.perm-table tbody tr:last-child td { border-bottom:none; }
.module-label { font-weight:600; color:var(--text-main); display:flex; align-items:center; gap:8px; }
.module-icon  { font-size:1rem; color:#6366f1; }
.perm-check   { width:18px; height:18px; accent-color:#6366f1; cursor:pointer; }
.check-cell   { text-align:center; }
.preset-btn   { padding:5px 14px; border:1.5px solid var(--border-color); border-radius:7px; font-size:0.8rem; font-weight:600; cursor:pointer; background:transparent; color:var(--text-main); transition:all 0.15s; }
.preset-btn:hover { border-color:#6366f1; color:#6366f1; background:rgba(99,102,241,0.06); }
</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=users" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editUser) ? 'Edit User' : 'New User' ?></h1>
</div>

<form method="POST" action="?page=users&action=<?= isset($editUser) ? 'update&id='.(int)$editUser['id'] : 'store' ?>">
    <?= Auth::csrfField() ?>
    <?php if (isset($editUser)): ?>
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
    <?php endif; ?>
<div class="row g-3">

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header" style="font-weight:700;font-size:0.875rem;">
                <i class="bi bi-person-circle me-2" style="color:#6366f1;"></i>User Details
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Full Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= isset($editUser) ? htmlspecialchars($editUser['name']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= isset($editUser) ? htmlspecialchars($editUser['email']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">
                        Password <?= isset($editUser) ? '<small class="text-muted fw-normal">(blank = keep)</small>' : '' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= isset($editUser) ? '' : 'required minlength="8"' ?>
                           placeholder="<?= isset($editUser) ? 'Enter new password...' : 'Min 8 characters' ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Role</label>
                    <select name="role" class="form-select" id="roleSelect" onchange="applyPreset(this.value)">
                        <option value="cashier" <?= (isset($editUser) && $editUser['role']==='cashier') ? 'selected':'' ?>>Cashier</option>
                        <option value="manager" <?= (isset($editUser) && $editUser['role']==='manager') ? 'selected':'' ?>>Manager</option>
                        <option value="viewer"  <?= (isset($editUser) && $editUser['role']==='viewer')  ? 'selected':'' ?>>Viewer</option>
                        <option value="admin"   <?= (isset($editUser) && $editUser['role']==='admin')   ? 'selected':'' ?>>Admin</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-check-lg me-1"></i> <?= isset($editUser) ? 'Save Changes' : 'Create User' ?>
                </button>
                <a href="?page=users" class="btn btn-outline-secondary w-100">Cancel</a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php
        $existing = [];
        if (isset($editUser)) {
            $db   = Database::getInstance();
            $rows = $db->fetchAll("SELECT * FROM permissions WHERE user_id = ?", [$editUser['id']]);
            foreach ($rows as $r) $existing[$r['module']] = $r;
        }
        ?>

        <!-- Module Permissions -->
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between" style="font-weight:700;font-size:0.875rem;">
                <span><i class="bi bi-shield-check me-2" style="color:#6366f1;"></i>Module Permissions</span>
                <div class="d-flex gap-2">
                    <button type="button" class="preset-btn" onclick="checkAllModules(true)">Check All</button>
                    <button type="button" class="preset-btn" onclick="checkAllModules(false)">Clear All</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Module</th>
                            <th class="check-cell" style="width:80px;">View</th>
                            <th class="check-cell" style="width:80px;">Add</th>
                            <th class="check-cell" style="width:80px;">Edit</th>
                            <th class="check-cell" style="width:80px;">Delete</th>
                            <th class="check-cell" style="width:70px;">All</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $modules = [
                        'dashboard' => ['Dashboard',  'bi-house'],
                        'sales'     => ['Sales',      'bi-receipt'],
                        'purchases' => ['Purchases',  'bi-cart3'],
                        'returns'   => ['Returns',    'bi-arrow-return-left'],
                        'inventory' => ['Inventory',  'bi-boxes'],
                        'stock'     => ['Stock List', 'bi-clipboard-data'],
                        'payments'  => ['Payments',   'bi-credit-card'],
                        'expenses'  => ['Expenses',   'bi-cash-stack'],
                        'customers' => ['Customers',  'bi-people'],
                        'suppliers' => ['Suppliers',  'bi-truck'],
                        'reports'   => ['Reports (master)', 'bi-bar-chart-line'],
                        'imei'      => ['IMEI Scanner',  'bi-upc-scan'],
                        'service'   => ['Service Center',  'bi-tools'],
                        'warranty'  => ['Warranty Replace', 'bi-shield-check'],
                        'supplier_contacts' => ['Supplier Contacts', 'bi-building'],
                        'mandoob_inventory' => ['Mandoob Inventory', 'bi-truck-front'],
                        'settings'  => ['Settings',   'bi-gear'],
                    ];
                    foreach ($modules as $mod => [$label, $icon]):
                        $v = $existing[$mod]['can_view']   ?? 0;
                        $a = $existing[$mod]['can_add']    ?? 0;
                        $e = $existing[$mod]['can_edit']   ?? 0;
                        $d = $existing[$mod]['can_delete'] ?? 0;
                    ?>
                    <tr>
                        <td><span class="module-label"><i class="bi <?= $icon ?> module-icon"></i><?= $label ?></span></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check mod-<?= $mod ?>" name="perms[<?= $mod ?>][view]"   value="1" <?= $v?'checked':'' ?> onchange="syncRowAll('<?= $mod ?>')"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check mod-<?= $mod ?>" name="perms[<?= $mod ?>][add]"    value="1" <?= $a?'checked':'' ?> onchange="syncRowAll('<?= $mod ?>')"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check mod-<?= $mod ?>" name="perms[<?= $mod ?>][edit]"   value="1" <?= $e?'checked':'' ?> onchange="syncRowAll('<?= $mod ?>')"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check mod-<?= $mod ?>" name="perms[<?= $mod ?>][delete]" value="1" <?= $d?'checked':'' ?> onchange="syncRowAll('<?= $mod ?>')"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check row-all" id="all_<?= $mod ?>" onchange="toggleRow('<?= $mod ?>',this.checked)" <?= ($v&&$a&&$e&&$d)?'checked':'' ?>></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Individual Report Permissions -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between" style="font-weight:700;font-size:0.875rem;">
                <span><i class="bi bi-bar-chart-line me-2" style="color:#0891b2;"></i>Individual Report Access</span>
                <div class="d-flex gap-2">
                    <button type="button" class="preset-btn" onclick="checkAllReports(true)">All Reports</button>
                    <button type="button" class="preset-btn" onclick="checkAllReports(false)">Clear</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div style="padding:10px 14px;font-size:0.75rem;color:var(--text-muted);background:rgba(8,145,178,0.06);border-bottom:1px solid var(--border-color);">
                    If "Reports (master)" is ON above, user sees all reports. Use these to grant access to specific reports only.
                </div>
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th style="min-width:220px;">Report</th>
                            <th class="check-cell" style="width:80px;">Access</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $reportModules = [
                        'rpt_daybook'       => ['Day Book',              'bi-journal-text'],
                        'rpt_sales'         => ['Sales Report',          'bi-bag-check'],
                        'rpt_profit'        => ['Profit & Loss',         'bi-graph-up-arrow'],
                        'rpt_stock'         => ['Stock Valuation',       'bi-boxes'],
                        'rpt_payments'      => ['Payments Report',       'bi-cash-stack'],
                        'rpt_party'         => ['Customer Statement',    'bi-person-lines-fill'],
                        'rpt_item_sales'         => ['Item Sales Report',        'bi-box-seam'],
                        'rpt_customer_purchases' => ['Customer Purchases Report','bi-bag-check-fill'],
                        'rpt_reconciliation'=> ['Balance Reconciliation','bi-shield-check'],
                        'rpt_account_stmt'  => ['Account Statement',     'bi-bank'],
                        'rpt_expenses'      => ['Expenses Report',       'bi-receipt'],
                        'rpt_sales_returns' => ['Sales Returns Report',  'bi-arrow-return-left'],
                        'rpt_supplier_stmt' => ['Supplier Statement',    'bi-building'],
                        'rpt_balance_sheet' => ['Balance Sheet',         'bi-clipboard-data'],
                        'rpt_customer_imei' => ['Customer IMEI Report',  'bi-phone'],
                    ];
                    foreach ($reportModules as $mod => [$label, $icon]):
                        $v = $existing[$mod]['can_view'] ?? 0;
                    ?>
                    <tr>
                        <td><span class="module-label"><i class="bi <?= $icon ?> module-icon" style="color:#0891b2;"></i><?= $label ?></span></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check rpt-check" name="perms[<?= $mod ?>][view]" value="1" <?= $v?'checked':'' ?>></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</form>

<script>
const presets = {
    admin:   { dashboard:[1,1,1,1], sales:[1,1,1,1], purchases:[1,1,1,1], returns:[1,1,1,1], inventory:[1,1,1,1], stock:[1,1,1,1], payments:[1,1,1,1], expenses:[1,1,1,1], customers:[1,1,1,1], suppliers:[1,1,1,1], reports:[1,1,1,1], imei:[1,1,1,1], service:[1,1,1,1], warranty:[1,1,1,1], supplier_contacts:[1,1,1,1], mandoob_inventory:[1,1,1,1], settings:[1,1,1,1] },
    manager: { dashboard:[1,0,0,0], sales:[1,1,1,0], purchases:[1,1,1,0], returns:[1,1,1,0], inventory:[1,1,1,0], stock:[1,0,0,0], payments:[1,1,0,0], expenses:[1,1,0,0], customers:[1,1,1,0], suppliers:[1,1,1,0], reports:[1,0,0,0], imei:[1,1,1,0], service:[1,1,1,0], warranty:[1,1,1,0], supplier_contacts:[1,1,1,0], mandoob_inventory:[1,1,1,0], settings:[0,0,0,0] },
    cashier: { dashboard:[1,0,0,0], sales:[1,1,0,0], purchases:[0,0,0,0], returns:[1,1,0,0], inventory:[1,0,0,0], stock:[1,0,0,0], payments:[1,1,0,0], expenses:[0,0,0,0], customers:[1,1,0,0], suppliers:[0,0,0,0], reports:[0,0,0,0], imei:[1,1,0,0], service:[1,1,1,0], warranty:[1,1,0,0], supplier_contacts:[0,0,0,0], mandoob_inventory:[0,0,0,0], settings:[0,0,0,0] },
    viewer:  { dashboard:[1,0,0,0], sales:[1,0,0,0], purchases:[1,0,0,0], returns:[1,0,0,0], inventory:[1,0,0,0], stock:[1,0,0,0], payments:[1,0,0,0], expenses:[1,0,0,0], customers:[1,0,0,0], suppliers:[1,0,0,0], reports:[1,0,0,0], imei:[1,0,0,0], service:[1,0,0,0], warranty:[1,0,0,0], supplier_contacts:[0,0,0,0], mandoob_inventory:[1,0,0,0], settings:[0,0,0,0] },
};
const rptPresets = {
    admin:   1,
    manager: 1,
    cashier: 0,
    viewer:  0,
};
const acts = ['view','add','edit','delete'];

function applyPreset(role) {
    const p = presets[role]; if (!p) return;
    Object.keys(p).forEach(mod => {
        p[mod].forEach((val, i) => {
            const cb = document.querySelector(`input[name="perms[${mod}][${acts[i]}]"]`);
            if (cb) cb.checked = !!val;
        });
        syncRowAll(mod);
    });
    // Set report checkboxes based on role
    const rptState = rptPresets[role] ?? 0;
    document.querySelectorAll('.rpt-check').forEach(cb => cb.checked = !!rptState);
}
function toggleRow(mod, checked) {
    document.querySelectorAll(`.mod-${mod}:not(.row-all)`).forEach(cb => cb.checked = checked);
}
function syncRowAll(mod) {
    const cbs = [...document.querySelectorAll(`.mod-${mod}:not(.row-all)`)];
    const allBox = document.getElementById('all_' + mod);
    if (allBox) allBox.checked = cbs.every(cb => cb.checked);
}
function checkAllModules(state) {
    document.querySelectorAll('.perm-table .perm-check:not(.row-all):not(.rpt-check)').forEach(function (cb) {
        cb.checked = state;
    });
}
function checkAllReports(state) {
    document.querySelectorAll('.rpt-check').forEach(cb => cb.checked = state);
}
<?php if (!isset($editUser)): ?>
applyPreset(document.getElementById('roleSelect').value);
<?php endif; ?>
</script>
