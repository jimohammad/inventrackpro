<!-- Party Master -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Party Master</h1></div>
    <?php if (Auth::can('customers','add') || Auth::can('suppliers','add')): ?>
    <a href="?page=parties&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Party</a>
    <?php endif; ?>
</div>

<?php
$balanceFilter = $balanceFilter ?? 'due';
$partyListUrl = static function (string $tabType, string $balance) use ($type): string {
    return '?page=parties&type=' . urlencode($tabType) . '&balance=' . urlencode($balance);
};
$totalReceivable   = array_sum(array_map(fn($p) => max(0, (float)($p['balance_due'] ?? 0)), $parties));
$totalPayable      = array_sum(array_map(fn($p) => abs(min(0, (float)($p['balance_due'] ?? 0))), $parties));
$partiesWithBalance = count(array_filter($parties, fn($p) => abs((float)($p['balance_due'] ?? 0)) > 0.001));
?>

<style>
.party-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: nowrap;
    width: 100%;
    overflow-x: auto;
    margin-bottom: 1rem;
    padding: 8px 12px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    scrollbar-width: thin;
}
.party-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.party-filter-group--balance {
    margin-left: auto;
}
.party-filter-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    white-space: nowrap;
    min-width: 3.2rem;
}
.party-segmented {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0;
    background: transparent;
}
.party-segmented a {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    border-radius: 999px;
    white-space: nowrap;
    border: 1px solid transparent;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}
.party-segmented a:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
}
.party-segmented a.is-active {
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.14);
}

/* Type colors */
.party-segmented a.pf-all { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }
.party-segmented a.pf-all.is-active { background: #64748b; color: #fff; border-color: #64748b; }

.party-segmented a.pf-customer { color: #047857; background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.28); }
.party-segmented a.pf-customer.is-active { background: #10b981; color: #fff; border-color: #10b981; }

.party-segmented a.pf-supplier { color: #4338ca; background: rgba(99, 102, 241, 0.14); border-color: rgba(99, 102, 241, 0.28); }
.party-segmented a.pf-supplier.is-active { background: #6366f1; color: #fff; border-color: #6366f1; }

.party-segmented a.pf-both { color: #b45309; background: rgba(245, 158, 11, 0.16); border-color: rgba(245, 158, 11, 0.32); }
.party-segmented a.pf-both.is-active { background: #f59e0b; color: #fff; border-color: #f59e0b; }

.party-segmented a.pf-freight_forwarder { color: #0369a1; background: rgba(14, 165, 233, 0.14); border-color: rgba(14, 165, 233, 0.28); }
.party-segmented a.pf-freight_forwarder.is-active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }

/* Balance colors */
.party-segmented a.pf-bal-all { color: #4338ca; background: rgba(99, 102, 241, 0.12); border-color: rgba(99, 102, 241, 0.24); }
.party-segmented a.pf-bal-all.is-active { background: #6366f1; color: #fff; border-color: #6366f1; }

.party-segmented a.pf-bal-due { color: #dc2626; background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.26); }
.party-segmented a.pf-bal-due.is-active { background: #ef4444; color: #fff; border-color: #ef4444; }

.party-segmented a.pf-bal-clear { color: #059669; background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.28); }
.party-segmented a.pf-bal-clear.is-active { background: #10b981; color: #fff; border-color: #10b981; }
</style>

<!-- Filters: type + balance on one line -->
<div class="party-filter-bar">
    <div class="party-filter-group">
        <span class="party-filter-label">Type</span>
        <div class="party-segmented" role="group" aria-label="Party type">
            <?php
            $canCustomers = Auth::can('customers', 'view');
            $canSuppliers = Auth::can('suppliers', 'view');
            $tabs = [];
            if ($canCustomers && $canSuppliers) $tabs['all'] = 'All';
            if ($canCustomers) $tabs['customer'] = 'Customers';
            if ($canSuppliers) $tabs['supplier'] = 'Suppliers';
            if ($canCustomers && $canSuppliers) $tabs['both'] = 'Both';
            if ($canSuppliers) $tabs['freight_forwarder'] = 'Freight forwarders';
            ?>
            <?php foreach ($tabs as $t => $label): ?>
            <a href="<?= htmlspecialchars($partyListUrl($t, $balanceFilter)) ?>"
               class="pf-<?= htmlspecialchars($t) ?> <?= ($type ?? 'all') === $t ? 'is-active' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="party-filter-group party-filter-group--balance">
        <span class="party-filter-label">Balance</span>
        <div class="party-segmented" role="group" aria-label="Balance filter">
            <a href="<?= htmlspecialchars($partyListUrl($type ?? 'all', 'all')) ?>"
               class="pf-bal-all <?= $balanceFilter === 'all' ? 'is-active' : '' ?>">All</a>
            <a href="<?= htmlspecialchars($partyListUrl($type ?? 'all', 'due')) ?>"
               class="pf-bal-due <?= $balanceFilter === 'due' ? 'is-active' : '' ?>">With balance</a>
            <a href="<?= htmlspecialchars($partyListUrl($type ?? 'all', 'clear')) ?>"
               class="pf-bal-clear <?= $balanceFilter === 'clear' ? 'is-active' : '' ?>">Without balance</a>
        </div>
    </div>
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
                <tr class="party-empty-row"><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    <?= match ($balanceFilter) {
                        'clear' => 'No parties without balance',
                        'due'   => 'No parties with outstanding balance',
                        default => 'No parties found',
                    } ?>
                </td></tr>
                <?php else: ?>
                <?php foreach ($parties as $i => $p): ?>
                <?php $bal = (float)($p['balance_due'] ?? 0); ?>
                <tr class="party-data-row">
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
                        <?php
                        $typeBg = match ($p['type']) {
                            'customer'          => ['rgba(16,185,129,0.15)', 'var(--success)'],
                            'supplier'          => ['rgba(99,102,241,0.15)', 'var(--primary)'],
                            'freight_forwarder' => ['rgba(14,165,233,0.15)', '#0284c7'],
                            default             => ['rgba(245,158,11,0.15)', 'var(--warning)'],
                        };
                        ?>
                        <span class="badge" style="border-radius:5px;background:<?= $typeBg[0] ?>;color:<?= $typeBg[1] ?>;">
                            <?= htmlspecialchars(Party::typeLabel($p['type'])) ?>
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
                            <?php $editMod = in_array($p['type'], ['supplier', 'freight_forwarder'], true) ? 'suppliers' : 'customers'; ?>
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
    var table = document.getElementById('partiesTable');
    if (!input || !table) return;

    input.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        table.querySelectorAll('tbody tr.party-data-row').forEach(function(row) {
            var text = row.textContent || row.innerText;
            row.style.display = (!q || text.toLowerCase().indexOf(q) > -1) ? '' : 'none';
        });
    });
});
function copyStatementLink(token, btn) {
    const url = window.location.origin + '/s/' + encodeURIComponent(token);
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        btn.style.background = 'rgba(16,185,129,0.2)';
        btn.style.color = '#10b981';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = '#6366f1'; }, 2000);
    });
}
</script>
