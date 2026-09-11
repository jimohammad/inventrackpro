<?php
$e        = $employee;
$today    = date('Y-m-d');
$dueLimit = date('Y-m-d', strtotime('+30 days'));
$fmtExpiry = static function (string $raw) use ($today, $dueLimit): array {
    $raw = trim($raw);
    if ($raw === '') {
        return ['text-muted', '—'];
    }
    $label = date('d M Y', strtotime($raw));
    $class = 'text-muted';
    if ($raw < $today) {
        $class = 'text-danger fw-semibold';
        $label .= ' · Expired';
    } elseif ($raw <= $dueLimit) {
        $class = 'text-warning fw-semibold';
        $label .= ' · Due within 30 days';
    }
    return [$class, $label];
};
[$expClass, $expLabel] = $fmtExpiry((string) ($e['residence_expires_on'] ?? ''));
[$passClass, $passLabel] = $fmtExpiry((string) ($e['passport_expires_on'] ?? ''));

$docs = [
    ['key' => 'kuwait_id',   'label' => 'Kuwait ID card', 'col' => 'kuwait_id_file',   'icon' => 'bi-person-vcard'],
    ['key' => 'passport',    'label' => 'Passport',       'col' => 'passport_file',    'icon' => 'bi-journal-richtext'],
    ['key' => 'work_permit', 'label' => 'Work permit',    'col' => 'work_permit_file', 'icon' => 'bi-file-earmark-check'],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="?page=employees" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title mb-0"><?= htmlspecialchars((string) $e['name']) ?></h1>
            <p class="page-subtitle mb-0">
                <?= htmlspecialchars((string) $e['employee_no']) ?>
                <?php if ((int) ($e['is_active'] ?? 1) !== 1): ?>
                <span class="badge bg-secondary ms-1">Inactive</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::can('employees', 'edit')): ?>
        <a href="?page=employees&action=edit&id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <?php if (Auth::can('employees', 'delete')): ?>
        <form method="POST" action="?page=employees&action=delete" class="d-inline"
              onsubmit="return confirm('Delete this employee record and all uploaded documents? This cannot be undone.');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Job title</p>
                        <p class="mb-0"><?= htmlspecialchars((string) ($e['job_title'] ?: '—')) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Phone</p>
                        <p class="mb-0"><?= htmlspecialchars((string) ($e['phone'] ?: '—')) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Nationality</p>
                        <p class="mb-0"><?= htmlspecialchars((string) ($e['nationality'] ?: '—')) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Salary</p>
                        <p class="mb-0 fw-semibold"><?= APP_CURRENCY ?> <?= number_format((float) $e['salary'], DECIMAL_PLACES) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Passport number</p>
                        <p class="mb-0"><?= htmlspecialchars((string) ($e['passport_no'] ?: '—')) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Passport expiry</p>
                        <p class="mb-0 <?= $passClass ?>"><?= htmlspecialchars($passLabel) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Kuwait Civil ID</p>
                        <p class="mb-0"><?= htmlspecialchars((string) ($e['kuwait_id_no'] ?: '—')) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 small text-muted fw-semibold text-uppercase">Residence expiry</p>
                        <p class="mb-0 <?= $expClass ?>"><?= htmlspecialchars($expLabel) ?></p>
                    </div>
                </div>
                <?php if (!empty($e['notes'])): ?>
                <hr>
                <p class="mb-1 small text-muted fw-semibold text-uppercase">Notes</p>
                <p class="mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars((string) $e['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold">Documents</div>
            <div class="list-group list-group-flush">
                <?php foreach ($docs as $d):
                    $has = trim((string) ($e[$d['col']] ?? '')) !== '';
                    ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi <?= $d['icon'] ?> me-2 text-muted"></i><?= htmlspecialchars($d['label']) ?></span>
                    <?php if ($has): ?>
                    <a href="?page=employees&action=download&id=<?= (int) $e['id'] ?>&doc=<?= urlencode($d['key']) ?>"
                       class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <?php else: ?>
                    <span class="small text-muted">Not uploaded</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
