<!-- Party Master -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Party Master</h1><p class="page-subtitle">Customers and Suppliers</p></div>
    <?php if (Auth::can('customers','add') || Auth::can('suppliers','add')): ?>
    <a href="?page=parties&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Party</a>
    <?php endif; ?>
</div>

<?php
$totalReceivable   = array_sum(array_map(fn($p) => max(0, (float)($p['balance_due'] ?? 0)), $parties));
$totalPayable      = array_sum(array_map(fn($p) => abs(min(0, (float)($p['balance_due'] ?? 0))), $parties));
$partiesWithBalance = count(array_filter($parties, fn($p) => abs((float)($p['balance_due'] ?? 0)) > 0.001));
?>

<!-- Type tabs -->
<div class="d-flex gap-2 mb-3">
    <?php
    $canCustomers = Auth::can('customers', 'view');
    $canSuppliers = Auth::can('suppliers', 'view');
    $tabs = [];
    if ($canCustomers && $canSuppliers) $tabs['all'] = 'All';
    if ($canCustomers) $tabs['customer'] = 'Customers';
    if ($canSuppliers) $tabs['supplier'] = 'Suppliers';
    if ($canCustomers && $canSuppliers) $tabs['both'] = 'Both';
    ?>
    <?php foreach ($tabs as $t => $label): ?>
    <a href="?page=parties&type=<?= $t ?>"
       class="btn btn-sm <?= ($type ?? 'all') === $t ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="mb-3" style="position:relative;">
    <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:1rem;z-index:2;pointer-events:none;"></i>
    <input type="text" id="partySearch" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
           placeholder="Search by account no, name, phone, area..."
           style="padding:14px 16px 14px 46px;font-size:1.15rem;font-weight:600;border:2px solid #e0e7ff;border-radius:10px;background:#fafbff;outline:none;transition:border-color 0.2s,box-shadow 0.2s;"
           onfocus="this.style.borderColor='#818cf8';this.style.boxShadow='0 0 0 4px rgba(99,102,241,0.12)';this.style.background='#ffffff';"
           onblur="this.style.borderColor='#e0e7ff';this.style.boxShadow='';this.style.background='#fafbff';">
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="partiesTable">
            <thead style="text-transform:none;font-size:0.82rem;font-weight:600;letter-spacing:0;">
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:110px;">Acc No</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Phone</th>
                    <th>Area</th>
                    <th>Status</th>
                    <th style="text-align:right;">Balance Due</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parties)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-people fs-2 d-block mb-2"></i>No parties found</td></tr>
                <?php else: ?>
                <?php foreach ($parties as $i => $p): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;text-align:center;"><?= $i + 1 ?></td>
                    <td>
                        <?php if (!empty($p['party_code'])): ?>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;font-weight:700;color:#4338ca;background:#eff6ff;padding:2px 8px;border-radius:5px;border:1px solid #c7d2fe;letter-spacing:1px;white-space:nowrap;">
                            <?= htmlspecialchars($p['party_code']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?page=parties&action=detail&id=<?= $p['id'] ?>"
                           style="color:var(--primary);font-weight:600;text-decoration:none;">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge" style="border-radius:5px;background:<?= $p['type']==='customer' ? 'rgba(16,185,129,0.15)' : ($p['type']==='supplier'?'rgba(99,102,241,0.15)':'rgba(245,158,11,0.15)') ?>;color:<?= $p['type']==='customer' ? 'var(--success)' : ($p['type']==='supplier'?'var(--primary)':'var(--warning)') ?>;">
                            <?= ucfirst($p['type']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['phone'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($p['city'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $p['is_active'] ? 'badge-paid' : 'badge-draft' ?> px-2" style="border-radius:5px;">
                            <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <?php $bal = (float)($p['balance_due'] ?? 0); ?>
                        <?php if ($bal > 0.001): ?>
                        <span style="font-weight:800;color:#ef4444;font-size:0.9rem;"><?= APP_CURRENCY ?> <?= number_format($bal, DECIMAL_PLACES) ?></span>
                        <?php elseif ($bal < -0.001): ?>
                        <span style="font-weight:800;color:#6366f1;font-size:0.9rem;">-<?= APP_CURRENCY ?> <?= number_format(abs($bal), DECIMAL_PLACES) ?></span>
                        <?php else: ?>
                        <span style="color:#10b981;font-weight:600;font-size:0.85rem;">✓ Clear</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?page=parties&action=detail&id=<?= $p['id'] ?>"
                               class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="Ledger"><i class="bi bi-eye"></i></a>
                            <?php if (in_array($p['type'], ['customer','both'])): ?>
                            <a href="?page=parties&action=agentStatement&id=<?= $p['id'] ?>"
                               class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Agent Statement"><i class="bi bi-person-lines-fill"></i></a>
                            <?php if (!empty($p['statement_token'])): ?>
                            <button type="button" onclick="copyStatementLink('<?= $p['statement_token'] ?>', this)"
                               class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:#6366f1;border:none;" title="Copy Field Statement Link"><i class="bi bi-link-45deg"></i></button>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php $editMod = $p['type'] === 'supplier' ? 'suppliers' : 'customers'; ?>
                            <?php if (Auth::can($editMod, 'edit')): ?>
                            <a href="?page=parties&action=edit&id=<?= $p['id'] ?>"
                               class="btn btn-sm" style="background:rgba(245,158,11,0.15);color:var(--warning);border:none;"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
window.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('partySearch');
    if (!input) return;
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        var rows = document.getElementById('partiesTable').getElementsByTagName('tr');
        for (var i = 1; i < rows.length; i++) {
            var text = rows[i].textContent || rows[i].innerText;
            rows[i].style.display = (!q || text.toLowerCase().indexOf(q) > -1) ? '' : 'none';
        }
    });
});
function copyStatementLink(token, btn) {
    const url = window.location.origin + '/statement.php?token=' + token;
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        btn.style.background = 'rgba(16,185,129,0.2)';
        btn.style.color = '#10b981';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = '#6366f1'; }, 2000);
    });
}
</script>
