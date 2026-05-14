<?php
$isEdit = isset($row) && is_array($row) && !empty($row['id']);
$months = $isEdit ? (int) ($row['interval_months'] ?? 3) : 3;
if ($months < 1 || $months > 24) {
    $months = 3;
}
$customers = $customers ?? [];
$currentPartyId = $isEdit ? (int) ($row['party_id'] ?? 0) : 0;
?>
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="?page=mandoob_inventory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title mb-0"><?= $isEdit ? 'Edit Mandoob' : 'Add Mandoob' ?></h1>
        <p class="page-subtitle mb-0">Choose a customer from Party Master. Phone is taken from their record. Leave “Next due” empty to auto-calculate from last count + interval.</p>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px;">
    <div class="card-body">
        <form method="post" action="?page=mandoob_inventory&action=<?= $isEdit ? 'update' : 'store' ?>">
            <?= Auth::csrfField() ?>
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="miPartySelect">Customer <span class="text-danger">*</span></label>
                <select name="party_id" id="miPartySelect" class="form-select" required>
                    <option value="">— Select customer —</option>
                    <?php foreach ($customers as $c):
                        $pid = (int) ($c['id'] ?? 0);
                        $p1  = trim((string) ($c['phone'] ?? ''));
                        $p2  = trim((string) ($c['phone2'] ?? ''));
                        $label = (string) ($c['name'] ?? '');
                        $code  = trim((string) ($c['party_code'] ?? ''));
                        if ($code !== '') {
                            $label .= ' (' . $code . ')';
                        }
                        $sel = ($currentPartyId === $pid) ? ' selected' : '';
                        ?>
                    <option value="<?= $pid ?>"<?= $sel ?>
                        data-phone="<?= htmlspecialchars($p1, ENT_QUOTES, 'UTF-8') ?>"
                        data-phone2="<?= htmlspecialchars($p2, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($customers === []): ?>
                <div class="form-text text-warning">No customers found for this warehouse. Add parties (customer / both) in Party Master first.</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="miPartyPhone">Phone <span class="text-muted fw-normal">(from customer)</span></label>
                <input type="text" id="miPartyPhone" class="form-control bg-light" readonly tabindex="-1"
                       value="<?= $isEdit ? htmlspecialchars((string) ($row['phone'] ?? '')) : '' ?>"
                       aria-readonly="true">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Reminder interval (months)</label>
                <input type="number" name="interval_months" class="form-control" min="1" max="24" value="<?= $months ?>">
                <div class="form-text">Default 3 — used when you press “Counted today” and when next due is auto-filled from last count.</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last physical count</label>
                    <input type="date" name="last_count_date" class="form-control"
                           value="<?= $isEdit && !empty($row['last_count_date']) ? htmlspecialchars((string) $row['last_count_date']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Next due (optional)</label>
                    <input type="date" name="next_due_date" class="form-control"
                           value="<?= $isEdit && !empty($row['next_due_date']) ? htmlspecialchars((string) $row['next_due_date']) : '' ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="2000"><?= $isEdit ? htmlspecialchars((string) ($row['notes'] ?? '')) : '' ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create' ?></button>
                <a href="?page=mandoob_inventory" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var sel = document.getElementById('miPartySelect');
    var phoneInput = document.getElementById('miPartyPhone');
    if (!sel || !phoneInput) return;
    function applyPhone() {
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) {
            phoneInput.value = '';
            return;
        }
        var p1 = (opt.getAttribute('data-phone') || '').trim();
        var p2 = (opt.getAttribute('data-phone2') || '').trim();
        if (p1 && p2) {
            phoneInput.value = (p1 + ' / ' + p2).slice(0, 40);
        } else {
            phoneInput.value = (p1 || p2).slice(0, 40);
        }
    }
    sel.addEventListener('change', applyPhone);
    applyPhone();
})();
</script>
