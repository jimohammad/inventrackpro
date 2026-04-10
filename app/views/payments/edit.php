<!-- Edit Payment -->
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=payments&action=detail&id=<?= $editPayment['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Edit Payment: <?= $editPayment['payment_no'] ?></h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card" style="border-radius:14px;overflow:hidden;">
            <div class="card-header" style="font-weight:700;font-size:0.95rem;padding:1rem 1.25rem;">
                <i class="bi bi-pencil-square me-2" style="color:#d97706;"></i>Edit Payment Details
            </div>
            <div class="card-body" style="padding:1.5rem;">

                <!-- Non-editable summary -->
                <?php $isIn = ($editPayment['payment_type'] ?? 'in') === 'in'; ?>
                <div style="background:rgba(99,102,241,0.06);border:1.5px solid rgba(99,102,241,0.2);border-radius:10px;padding:14px 18px;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:0.78rem;color:var(--text-muted);font-weight:600;">Payment No</span>
                        <span style="font-weight:700;color:var(--primary);"><?= $editPayment['payment_no'] ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <span style="font-size:0.78rem;color:var(--text-muted);font-weight:600;">Type</span>
                        <span style="padding:3px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;
                            background:<?= $isIn ? '#d1fae5' : '#fee2e2' ?>;
                            color:<?= $isIn ? '#065f46' : '#991b1b' ?>;">
                            <?= $isIn ? '↓ Payment In' : '↑ Payment Out' ?>
                        </span>
                    </div>
                    <div style="border-top:1px dashed rgba(99,102,241,0.2);padding-top:12px;">
                        <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Party</div>
                        <div style="position:relative;">
                            <input type="text" id="editPartySearch" class="form-control"
                                   value="<?= htmlspecialchars($editPayment['party_name'] ?? '') ?>"
                                   placeholder="Search party..." autocomplete="off"
                                   style="font-size:1rem;font-weight:700;border-color:#10b981;background:#f0fdf4;">
                            <input type="hidden" id="editPartyId" value="<?= $editPayment['party_id'] ?>">
                            <div id="editPartyDrop" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:200px;overflow-y:auto;margin-top:4px;"></div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="?page=payments&action=update&id=<?= $editPayment['id'] ?>" id="editPayForm">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= $editPayment['id'] ?>">
                    <input type="hidden" name="party_id" id="partyIdSubmit" value="<?= $editPayment['party_id'] ?>">

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-weight:700;"><?= APP_CURRENCY ?></span>
                            <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                                   value="<?= number_format($editPayment['amount'], DECIMAL_PLACES, '.', '') ?>"
                                   style="font-weight:700;font-size:1.1rem;color:#059669;">
                        </div>
                    </div>

                    <!-- Account -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-select" required>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= ($editPayment['account_id'] == $acc['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" required
                               value="<?= htmlspecialchars($editPayment['date']) ?>">
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label" style="font-weight:600;">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Optional notes..."><?= htmlspecialchars($editPayment['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="?page=payments&action=detail&id=<?= $editPayment['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary pin-protect">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <button type="submit" class="btn btn-outline-primary pin-protect" onclick="document.getElementById('printAfterSave').value='1'">
                            <i class="bi bi-printer me-1"></i> Save & Print
                        </button>
                    </div>
                    <input type="hidden" name="print_after_save" id="printAfterSave" value="0">
                </form>
            </div>
        </div>
    </div>
</div>


<script>
let editPartyTimer;
document.getElementById('editPartySearch').addEventListener('input', function() {
    document.getElementById('editPartyId').value = '';
    this.style.borderColor = '#e5e7eb';
    this.style.background = '#fafbff';
    clearTimeout(editPartyTimer);
    const q = this.value.trim();
    const drop = document.getElementById('editPartyDrop');
    if (q.length < 1) { drop.style.display = 'none'; return; }
    editPartyTimer = setTimeout(() => {
        fetch(`?page=sales&action=searchParties&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(parties => {
                if (!parties.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = parties.map(p => `
                    <div style="padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;"
                         onmousedown="selectEditParty(${p.id},'${p.name.replace(/'/g,"\\'")}')"
                         onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                        <strong>${p.name}</strong>
                        <small style="color:#94a3b8;margin-left:8px;">${p.phone || ''}</small>
                    </div>`).join('');
                drop.style.display = 'block';
            });
    }, 250);
});

function selectEditParty(id, name) {
    document.getElementById('editPartyId').value = id;
    document.getElementById('partyIdSubmit').value = id;
    const inp = document.getElementById('editPartySearch');
    inp.value = name;
    inp.style.borderColor = '#10b981';
    inp.style.background = '#f0fdf4';
    document.getElementById('editPartyDrop').style.display = 'none';
}

document.getElementById('editPartySearch').addEventListener('blur', () => {
    setTimeout(() => document.getElementById('editPartyDrop').style.display = 'none', 200);
});
</script>
