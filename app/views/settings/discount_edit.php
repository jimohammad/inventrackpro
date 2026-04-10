<!-- Edit Discount -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Edit Discount — <?= $discount['discount_no'] ?></h1>
    </div>
    <a href="?page=discounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="card" style="border-radius:12px;max-width:700px;">
    <div class="card-body" style="padding:24px;">
        <form method="POST" action="?page=discounts&action=update">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $discount['id'] ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= $discount['date'] ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" class="form-select form-select-sm" required>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $discount['party_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Item <small class="text-muted">(optional)</small></label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">General</option>
                        <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id'] ?>" <?= $it['id'] == $discount['item_id'] ? 'selected' : '' ?>><?= htmlspecialchars($it['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Amount <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="font-weight:700;"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                               value="<?= number_format($discount['amount'], DECIMAL_PLACES, '.', '') ?>" style="font-weight:700;">
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Reason</label>
                    <input type="text" name="reason" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($discount['reason'] ?? '') ?>" placeholder="e.g. Price adjustment">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                <a href="?page=discounts" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
