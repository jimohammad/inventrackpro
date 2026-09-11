<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Edit Discount — <?= htmlspecialchars($discount['discount_no']) ?></h1>
    </div>
    <a href="?page=discounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="card" style="border-radius:14px;max-width:760px;border:1px solid #d7e3f8;box-shadow:0 10px 28px rgba(15,23,42,.05);">
    <div class="card-body" style="padding:22px;">
        <form method="POST" action="?page=discounts&action=update" id="discountEditForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $discount['id'] ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($discount['date'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" id="editDiscParty" class="form-select form-select-sm" required>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $discount['party_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Invoice <small class="text-muted">(optional)</small></label>
                    <select name="sale_id" id="editDiscSale" class="form-select form-select-sm">
                        <option value="">No invoice (general)</option>
                        <?php foreach (($invoices ?? []) as $inv): ?>
                        <option value="<?= (int) $inv['id'] ?>" <?= (int) $inv['id'] === (int) ($discount['sale_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inv['invoice_no']) ?> · <?= date('d M Y', strtotime($inv['date'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Amount <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="font-weight:700;"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                               value="<?= number_format($discount['amount'], DECIMAL_PLACES, '.', '') ?>" style="font-weight:700;color:#059669;">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-sm pin-protect"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                <a href="?page=discounts" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var partySelect = document.getElementById('editDiscParty');
    var saleSelect = document.getElementById('editDiscSale');
    if (!partySelect || !saleSelect) {
        return;
    }

    function fillInvoices(invoices, selectedId) {
        saleSelect.innerHTML = '';
        var blank = document.createElement('option');
        blank.value = '';
        blank.textContent = 'No invoice (general)';
        saleSelect.appendChild(blank);
        (invoices || []).forEach(function(inv) {
            var opt = document.createElement('option');
            opt.value = String(inv.id);
            opt.textContent = inv.invoice_no + ' · ' + inv.date;
            if (selectedId && String(selectedId) === String(inv.id)) {
                opt.selected = true;
            }
            saleSelect.appendChild(opt);
        });
    }

    partySelect.addEventListener('change', function() {
        var partyId = partySelect.value;
        var keepId = saleSelect.value;
        saleSelect.innerHTML = '<option value="">Loading...</option>';
        if (!partyId) {
            fillInvoices([]);
            return;
        }
        fetch('?page=discounts&action=customerInvoices&party_id=' + encodeURIComponent(partyId))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                fillInvoices((data && data.invoices) ? data.invoices : [], keepId);
            })
            .catch(function() {
                fillInvoices([]);
            });
    });
});
</script>
