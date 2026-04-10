<!-- Item Form -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=items" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editMode) ? 'Edit Item' : 'New Item' ?></h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <form method="POST" action="?page=items&action=<?= isset($editMode) ? 'update' : 'store' ?>">
            <?= Auth::csrfField() ?>
            <?php if (isset($editMode)): ?>
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <?php endif; ?>

            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(99,102,241,0.08);border-bottom:2px solid rgba(99,102,241,0.2);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-box-seam-fill" style="color:#6366f1;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Item Details</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-500">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($item['name'] ?? '') ?>" placeholder="e.g. Samsung Galaxy A55">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">No Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(16,185,129,0.08);border-bottom:2px solid rgba(16,185,129,0.2);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-tag-fill" style="color:#10b981;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Pricing</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-500">Purchase Price (KWD)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(16,185,129,0.1);border-color:var(--border-color);color:#10b981;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="purchase_price" class="form-control" step="0.001" min="0"
                                    value="<?= number_format((float)($item['purchase_price'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Purchase Price (AED) <small class="text-muted fw-normal">— reference</small></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(29,78,216,0.08);border-color:var(--border-color);color:#1d4ed8;font-weight:600;">AED</span>
                                <input type="number" name="price_aed" class="form-control" step="0.001" min="0"
                                    value="<?= number_format((float)($item['price_aed'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Purchase Price (USD) <small class="text-muted fw-normal">— reference</small></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(133,77,14,0.08);border-color:var(--border-color);color:#854d0e;font-weight:600;">USD</span>
                                <input type="number" name="price_usd" class="form-control" step="0.001" min="0"
                                    value="<?= number_format((float)($item['price_usd'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Sale Price</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(16,185,129,0.1);border-color:var(--border-color);color:#10b981;font-weight:600;"><?= APP_CURRENCY ?></span>
                                <input type="number" name="sale_price" class="form-control" step="0.001" min="0"
                                    value="<?= number_format((float)($item['sale_price'] ?? 0), DECIMAL_PLACES, '.', '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-500">Min Stock Alert</label>
                            <input type="number" name="min_stock" class="form-control" min="0"
                                value="<?= $item['min_stock'] ?? '0' ?>" placeholder="0 = no alert">
                            <small class="text-muted">Alert when stock drops below this</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(245,158,11,0.08);border-bottom:2px solid rgba(245,158,11,0.2);">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-upc-scan" style="color:#f59e0b;font-size:0.9rem;"></i>
                    </div>
                    <span style="font-weight:600;color:var(--text-main);">Tracking</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- IMEI Toggle -->
                        <div class="col-12">
                            <div style="display:flex;align-items:center;gap:14px;background:rgba(99,102,241,0.06);border:1.5px solid rgba(99,102,241,0.25);border-radius:12px;padding:14px 18px;">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="has_imei" value="1"
                                        id="hasImeiCheck" <?= ($item['has_imei'] ?? 1) ? 'checked' : '' ?>
                                        style="width:3.2rem;height:1.7rem;cursor:pointer;">
                                </div>
                                <label for="hasImeiCheck" style="cursor:pointer;margin:0;">
                                    <span style="font-weight:600;color:var(--text-main);font-size:0.95rem;">IMEI / Serial Number Tracking</span>
                                    <small style="display:block;color:var(--text-muted);font-size:0.78rem;margin-top:2px;">Enable to track individual serial numbers per unit</small>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($editMode)): ?>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="isActiveCheck" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActiveCheck">Active</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="?page=items" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i><?= isset($editMode) ? 'Update Item' : 'Create Item' ?>
                </button>
            </div>

        </form>
    </div>
</div>
