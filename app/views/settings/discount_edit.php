<style>
.disc-edit-page { max-width: 920px; }
.disc-edit-head {
    display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;
    margin-bottom:10px;
}
.disc-edit-head .page-title { margin:0; font-size:1.15rem; }
.disc-edit-card {
    border:1px solid var(--border-color, #e2e8f0); border-radius:10px; background:#fff; padding:10px 12px 12px;
}
.disc-edit-grid {
    display:grid;
    grid-template-columns: 118px minmax(150px,1.2fr) minmax(170px,1.3fr) 140px auto;
    gap:6px 8px;
    align-items:end;
}
.disc-edit-card .form-label {
    display:block; font-size:.62rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.4px; color:#64748b; margin-bottom:3px;
}
.disc-edit-card .form-control,
.disc-edit-card .form-select,
.disc-edit-card .input-group-text {
    border-radius:7px; border:1.4px solid #c9d5e8; min-height:32px; height:32px;
    font-size:.8rem; padding:2px 8px;
}
.disc-edit-card .input-group-text { font-size:.68rem; font-weight:800; background:#f1f5f9; padding:0 8px; }
.disc-edit-actions { display:flex; gap:6px; padding-bottom:1px; }
.disc-edit-actions .btn { height:32px; padding:0 12px; font-size:.78rem; font-weight:700; border-radius:7px; }
@media (max-width: 800px) {
    .disc-edit-grid { grid-template-columns: 1fr 1fr; }
    .disc-edit-actions { grid-column:1 / -1; }
}
</style>

<div class="disc-edit-page">
    <div class="disc-edit-head">
        <h1 class="page-title">Edit Discount — <?= htmlspecialchars($discount['discount_no']) ?></h1>
        <a href="?page=discounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="disc-edit-card">
        <form method="POST" action="?page=discounts&action=update" id="discountEditForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $discount['id'] ?>">

            <div class="disc-edit-grid">
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($discount['date'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" id="editDiscParty" class="form-select" required>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $discount['party_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Invoice <span style="text-transform:none;font-weight:600;color:#94a3b8;">optional</span></label>
                    <select name="sale_id" id="editDiscSale" class="form-select">
                        <option value="">No invoice (general)</option>
                        <?php foreach (($invoices ?? []) as $inv): ?>
                        <option value="<?= (int) $inv['id'] ?>" <?= (int) $inv['id'] === (int) ($discount['sale_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inv['invoice_no']) ?> · <?= date('d M Y', strtotime($inv['date'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                               value="<?= number_format($discount['amount'], DECIMAL_PLACES, '.', '') ?>" style="font-weight:800;color:#059669;">
                    </div>
                </div>
                <div class="disc-edit-actions">
                    <button type="submit" class="btn btn-primary pin-protect"><i class="bi bi-check-lg me-1"></i> Save</button>
                    <a href="?page=discounts" class="btn btn-outline-secondary">Cancel</a>
                </div>
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
