<?php
$isEdit   = isset($employee) && is_array($employee) && !empty($employee['id']);
$e        = $isEdit ? $employee : [];
$today    = date('Y-m-d');
$exp      = trim((string) ($e['residence_expires_on'] ?? ''));
$expGone  = $exp !== '' && $exp < $today;
$passExp  = trim((string) ($e['passport_expires_on'] ?? ''));
$passGone = $passExp !== '' && $passExp < $today;
$nationalities = Employee::NATIONALITIES;
$natCurrent    = (string) ($e['nationality'] ?? '');
$accept   = '.pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp';

$docSlots = [
    ['key' => 'kuwait_id',   'field' => 'kuwait_id_file',   'label' => 'Kuwait ID card', 'icon' => 'bi-person-vcard', 'col' => 'kuwait_id_file'],
    ['key' => 'passport',    'field' => 'passport_file',    'label' => 'Passport',       'icon' => 'bi-journal-richtext', 'col' => 'passport_file'],
    ['key' => 'work_permit', 'field' => 'work_permit_file', 'label' => 'Work permit',    'icon' => 'bi-file-earmark-check', 'col' => 'work_permit_file'],
];
?>
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $isEdit ? '?page=employees&action=view&id=' . (int) $e['id'] : '?page=employees' ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="page-title mb-0"><?= $isEdit ? 'Edit Employee' : 'New Employee' ?></h1>
        <?php if ($isEdit): ?>
        <p class="page-subtitle mb-0"><?= htmlspecialchars((string) $e['employee_no']) ?></p>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="?page=employees&action=<?= $isEdit ? 'update' : 'store' ?>" enctype="multipart/form-data">
    <?= Auth::csrfField() ?>
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(99,102,241,0.06);border-bottom:2px solid rgba(99,102,241,0.16);">
                    <i class="bi bi-person-circle" style="color:#6366f1;"></i>
                    <span class="fw-semibold">Employee details</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Full name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="150"
                                   value="<?= htmlspecialchars((string) ($e['name'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Job title</label>
                            <input type="text" name="job_title" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars((string) ($e['job_title'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" maxlength="40"
                                   value="<?= htmlspecialchars((string) ($e['phone'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nationality</label>
                            <select name="nationality" class="form-select">
                                <option value="">— Select —</option>
                                <?php foreach ($nationalities as $nat): ?>
                                <option value="<?= htmlspecialchars($nat) ?>"<?= $natCurrent === $nat ? ' selected' : '' ?>><?= htmlspecialchars($nat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(14,165,233,0.06);border-bottom:2px solid rgba(14,165,233,0.16);">
                    <i class="bi bi-passport" style="color:#0284c7;"></i>
                    <span class="fw-semibold">Identity & residence</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Passport number</label>
                            <input type="text" name="passport_no" class="form-control" maxlength="50"
                                   value="<?= htmlspecialchars((string) ($e['passport_no'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Passport expiry date</label>
                            <input type="date" name="passport_expires_on" class="form-control"
                                   value="<?= htmlspecialchars($passExp) ?>">
                            <?php if ($passGone): ?>
                            <small class="text-danger fw-semibold">Expired — renew and update the date.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kuwait Civil ID number</label>
                            <input type="text" name="kuwait_id_no" class="form-control" maxlength="30"
                                   value="<?= htmlspecialchars((string) ($e['kuwait_id_no'] ?? '')) ?>"
                                   placeholder="12-digit Civil ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Residence expiry date</label>
                            <input type="date" name="residence_expires_on" class="form-control"
                                   value="<?= htmlspecialchars($exp) ?>">
                            <?php if ($expGone): ?>
                            <small class="text-danger fw-semibold">Expired — renew and update the date.</small>
                            <?php else: ?>
                            <small class="text-muted">Dashboard alerts when this date has passed or is within 30 days.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly salary</label>
                            <div class="input-group">
                                <span class="input-group-text fw-semibold"><?= APP_CURRENCY ?></span>
                                <input type="number" name="salary" class="form-control" step="0.001" min="0"
                                       value="<?= $isEdit ? number_format((float) ($e['salary'] ?? 0), DECIMAL_PLACES, '.', '') : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" maxlength="4000"><?= htmlspecialchars((string) ($e['notes'] ?? '')) ?></textarea>
                    <?php if ($isEdit): ?>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="0" id="empInactive"
                            <?= (int) ($e['is_active'] ?? 1) === 0 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="empInactive">Mark inactive (left the company)</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header d-flex align-items-center gap-2" style="background:rgba(239,68,68,0.06);border-bottom:2px solid rgba(239,68,68,0.16);">
                    <i class="bi bi-paperclip" style="color:#ef4444;"></i>
                    <span class="fw-semibold">Document scans</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">PDF or image, max 5 MB each. Files are only viewable after login.</p>
                    <?php foreach ($docSlots as $slot):
                        $existing = (string) ($e[$slot['col']] ?? '');
                        ?>
                    <div class="mb-3 pb-3 <?= $slot['key'] !== 'work_permit' ? 'border-bottom' : '' ?>">
                        <label class="form-label fw-semibold">
                            <i class="bi <?= $slot['icon'] ?> me-1"></i><?= htmlspecialchars($slot['label']) ?>
                        </label>
                        <input type="file" name="<?= htmlspecialchars($slot['field']) ?>" class="form-control" accept="<?= $accept ?>">
                        <?php if ($isEdit && $existing !== ''): ?>
                        <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
                            <a href="?page=employees&action=download&id=<?= (int) $e['id'] ?>&doc=<?= urlencode($slot['key']) ?>"
                               class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                <i class="bi bi-eye me-1"></i>View current
                            </a>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="remove_<?= htmlspecialchars($slot['key']) ?>" value="1"
                                       id="remove_<?= htmlspecialchars($slot['key']) ?>">
                                <label class="form-check-label" for="remove_<?= htmlspecialchars($slot['key']) ?>">Remove file</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Save changes' : 'Save employee' ?>
                    </button>
                    <a href="<?= $isEdit ? '?page=employees&action=view&id=' . (int) $e['id'] : '?page=employees' ?>"
                       class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
