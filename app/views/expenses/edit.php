<!-- Edit Expense -->
<?php
    $accountsReturnUrl = $accountsReturnUrl ?? '';
    $cancelUrl = $accountsReturnUrl !== '' ? $accountsReturnUrl : '?page=expenses';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Edit: <?= htmlspecialchars($expense['expense_no']) ?></h1>
</div>

<div class="card" style="border-radius:12px;max-width:600px;">
    <div class="card-body" style="padding:24px;">
        <form method="POST" action="?page=expenses&action=update">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $expense['id'] ?>">
            <?php if (!empty($returnAccountId)): ?>
            <input type="hidden" name="return_account_id" value="<?= (int)$returnAccountId ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $expense['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Amount <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="font-weight:700;"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                               value="<?= number_format($expense['amount'], DECIMAL_PLACES, '.', '') ?>"
                               style="font-weight:700;font-size:1rem;color:#8b5cf6;">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Date &amp; Time <span class="text-danger">*</span></label>
                    <?php
                        $editTime = '';
                        if (!empty($expense['created_at']) && strtotime((string) $expense['created_at'])) {
                            $editTime = date('H:i', strtotime((string) $expense['created_at']));
                        } else {
                            $editTime = date('H:i');
                        }
                    ?>
                    <div class="d-flex gap-2">
                        <input type="date" name="date" class="form-control form-control-sm" required
                               value="<?= htmlspecialchars(substr((string) ($expense['date'] ?? ''), 0, 10)) ?>">
                        <input type="time" name="time" class="form-control form-control-sm" required
                               value="<?= htmlspecialchars($editTime) ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Account <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select form-select-sm" required>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= $acc['id'] == $expense['account_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Description</label>
                    <input type="text" name="description" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($expense['description'] ?? '') ?>"
                           placeholder="What was this expense for?">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm pin-protect">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
