<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Configure your application preferences</p>
    </div>
</div>

<form method="POST" action="?page=settings">
    <?= Auth::csrfField() ?>

    <!-- Company Info -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-building me-2"></i>Company Information</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="settings[company_name]" class="form-control"
                           value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Email</label>
                    <input type="email" name="settings[company_email]" class="form-control"
                           value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Phone</label>
                    <input type="text" name="settings[company_phone]" class="form-control"
                           value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Address</label>
                    <input type="text" name="settings[company_address]" class="form-control"
                           value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Settings -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Invoice Settings</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="settings[currency]" class="form-control"
                           value="<?= htmlspecialchars($settings['currency'] ?? 'KWD') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Decimal Places</label>
                    <input type="number" name="settings[decimal_places]" class="form-control" min="0" max="4"
                           value="<?= htmlspecialchars($settings['decimal_places'] ?? '3') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tax Label</label>
                    <input type="text" name="settings[tax_label]" class="form-control" placeholder="e.g. VAT"
                           value="<?= htmlspecialchars($settings['tax_label'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" name="settings[tax_rate]" class="form-control" step="0.01" min="0"
                           value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Warehouse</label>
                    <select name="settings[default_warehouse]" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= ($settings['default_warehouse'] ?? '') == $wh['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Account</label>
                    <select name="settings[default_account]" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= ($settings['default_account'] ?? '') == $acc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invoice Footer</label>
                    <textarea name="settings[invoice_footer]" class="form-control" rows="2"><?= htmlspecialchars($settings['invoice_footer'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invoice Terms</label>
                    <textarea name="settings[invoice_terms]" class="form-control" rows="2"><?= htmlspecialchars($settings['invoice_terms'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Prefix Settings -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-hash me-2"></i>Number Prefixes</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Sale Prefix</label>
                    <input type="text" name="settings[sale_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['sale_prefix'] ?? 'SAL-') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Prefix</label>
                    <input type="text" name="settings[purchase_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['purchase_prefix'] ?? 'PUR-') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Return Prefix</label>
                    <input type="text" name="settings[return_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['return_prefix'] ?? 'RET-') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expense Prefix</label>
                    <input type="text" name="settings[expense_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['expense_prefix'] ?? 'EXP-') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Prefix</label>
                    <input type="text" name="settings[payment_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['payment_prefix'] ?? 'PAY-') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transfer Prefix</label>
                    <input type="text" name="settings[transfer_prefix]" class="form-control"
                           value="<?= htmlspecialchars($settings['transfer_prefix'] ?? 'TRF-') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Notifications -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-whatsapp me-2"></i>WhatsApp Notifications</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Phone Number ID</label>
                    <input type="text" name="settings[whatsapp_phone_id]" class="form-control" placeholder="e.g. 100234567890"
                           value="<?= htmlspecialchars($settings['whatsapp_phone_id'] ?? '') ?>">
                    <small class="text-muted">From Meta Business > WhatsApp > API Setup</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Access Token</label>
                    <input type="text" name="settings[whatsapp_token]" class="form-control" placeholder="Permanent token"
                           value="<?= htmlspecialchars($settings['whatsapp_token'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recipient Number</label>
                    <input type="text" name="settings[whatsapp_recipient]" class="form-control" placeholder="e.g. 96550123456"
                           value="<?= htmlspecialchars($settings['whatsapp_recipient'] ?? '') ?>">
                    <small class="text-muted">With country code, no + or spaces</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Security -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Security</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Admin PIN (4 digits)</label>
                    <input type="password" name="settings[admin_pin]" class="form-control" maxlength="4" pattern="\d{4}"
                           placeholder="<?= !empty($settings['admin_pin']) ? '****' : 'Set 4-digit PIN' ?>">
                    <small class="text-muted">Used for protected actions. Leave blank to keep current PIN.</small>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
        <i class="bi bi-check-lg me-1"></i> Save Settings
    </button>
</form>
