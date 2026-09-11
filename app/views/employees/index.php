<?php
$search    = $search ?? '';
$status    = $status ?? 'active';
$resFilter = $resFilter ?? 'all';
$today     = $today ?? date('Y-m-d');
$dueLimit  = $dueLimit ?? date('Y-m-d', strtotime('+30 days'));
$expiryCounts = $expiryCounts ?? ['expired' => 0, 'due_soon' => 0];
$rows      = $rows ?? [];

$tabUrl = static function (string $st, string $res) use ($search): string {
    $q = ['page' => 'employees', 'status' => $st, 'residence' => $res];
    if ($search !== '') {
        $q['search'] = $search;
    }
    return '?' . http_build_query($q);
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Employees</h1>
        <p class="page-subtitle mb-0">Passport, Civil ID, salary, and residence documents for <?= htmlspecialchars(Auth::warehouseName()) ?></p>
    </div>
    <?php if (Auth::can('employees', 'add')): ?>
    <a href="?page=employees&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Employee
    </a>
    <?php endif; ?>
</div>

<?php if ((int) $expiryCounts['expired'] > 0 || (int) $expiryCounts['due_soon'] > 0): ?>
<div class="alert <?= (int) $expiryCounts['expired'] > 0 ? 'alert-danger' : 'alert-warning' ?> border-0 shadow-sm mb-3">
    <i class="bi bi-calendar-x me-1"></i>
    <?php if ((int) $expiryCounts['expired'] > 0): ?>
    <strong><?= (int) $expiryCounts['expired'] ?></strong> residence<?= (int) $expiryCounts['expired'] === 1 ? '' : 's' ?> expired.
    <?php endif; ?>
    <?php if ((int) $expiryCounts['due_soon'] > 0): ?>
    <strong><?= (int) $expiryCounts['due_soon'] ?></strong> due within 30 days.
    <?php endif; ?>
    <a href="<?= htmlspecialchars($tabUrl($status, 'expired')) ?>" class="fw-semibold ms-1">Show expired</a>
    ·
    <a href="<?= htmlspecialchars($tabUrl($status, 'due_soon')) ?>" class="fw-semibold">Show due soon</a>
</div>
<?php endif; ?>

<form method="GET" action="" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <input type="hidden" name="page" value="employees">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, Civil ID, passport, phone…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Residence</label>
                <select name="residence" class="form-select">
                    <option value="all" <?= $resFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="expired" <?= $resFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="due_soon" <?= $resFilter === 'due_soon' ? 'selected' : '' ?>>Due in 30 days</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filter</button>
                <a href="?page=employees" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>
    </div>
</form>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= htmlspecialchars($tabUrl('active', $resFilter)) ?>"
       class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-outline-secondary' ?>">Active</a>
    <a href="<?= htmlspecialchars($tabUrl('inactive', $resFilter)) ?>"
       class="btn btn-sm <?= $status === 'inactive' ? 'btn-primary' : 'btn-outline-secondary' ?>">Inactive</a>
    <a href="<?= htmlspecialchars($tabUrl('all', $resFilter)) ?>"
       class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
</div>

<?php if (empty($rows)): ?>
<div class="alert alert-info border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i>No employee records match these filters.
    <?php if (Auth::can('employees', 'add')): ?>
    <a href="?page=employees&action=create" class="fw-semibold">Add the first employee</a>.
    <?php endif; ?>
</div>
<?php else: ?>
<div class="table-responsive card border-0 shadow-sm">
    <table class="table table-hover align-middle mb-0" style="font-size:0.88rem;">
        <thead class="table-light">
            <tr>
                <th>Employee</th>
                <th>Civil ID</th>
                <th>Passport</th>
                <th>Passport expiry</th>
                <th>Residence expiry</th>
                <th class="text-end">Salary</th>
                <th>Documents</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
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
                    $label .= ' · Due soon';
                }
                return [$class, $label];
            };
            [$expClass, $expLabel] = $fmtExpiry((string) ($r['residence_expires_on'] ?? ''));
            [$passClass, $passLabel] = $fmtExpiry((string) ($r['passport_expires_on'] ?? ''));
            $docs = [];
            if (!empty($r['kuwait_id_file'])) { $docs[] = 'ID'; }
            if (!empty($r['passport_file'])) { $docs[] = 'Passport'; }
            if (!empty($r['work_permit_file'])) { $docs[] = 'Permit'; }
            ?>
            <tr>
                <td>
                    <a href="?page=employees&action=view&id=<?= (int) $r['id'] ?>" class="fw-semibold text-decoration-none">
                        <?= htmlspecialchars((string) $r['name']) ?>
                    </a>
                    <div class="small text-muted">
                        <?= htmlspecialchars((string) $r['employee_no']) ?>
                        <?php if (!empty($r['job_title'])): ?>
                        · <?= htmlspecialchars((string) $r['job_title']) ?>
                        <?php endif; ?>
                        <?php if (!empty($r['nationality'])): ?>
                        · <?= htmlspecialchars((string) $r['nationality']) ?>
                        <?php endif; ?>
                        <?php if ((int) ($r['is_active'] ?? 1) !== 1): ?>
                        <span class="badge bg-secondary ms-1">Inactive</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?= htmlspecialchars((string) ($r['kuwait_id_no'] ?: '—')) ?></td>
                <td><?= htmlspecialchars((string) ($r['passport_no'] ?: '—')) ?></td>
                <td class="<?= $passClass ?>"><?= htmlspecialchars($passLabel) ?></td>
                <td class="<?= $expClass ?>"><?= htmlspecialchars($expLabel) ?></td>
                <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format((float) $r['salary'], DECIMAL_PLACES) ?></td>
                <td class="small text-muted"><?= $docs ? htmlspecialchars(implode(' · ', $docs)) : '—' ?></td>
                <td class="text-end text-nowrap">
                    <a href="?page=employees&action=view&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <?php if (Auth::can('employees', 'edit')): ?>
                    <a href="?page=employees&action=edit&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
