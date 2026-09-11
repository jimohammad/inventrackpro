<!-- Party list (Party Master / Customers / Suppliers) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Party Master') ?></h1></div>
    <?php if (Auth::canAny(['parties', 'customers', 'suppliers'], 'add')): ?>
    <a href="?page=parties&action=create<?= ($type ?? '') === 'freight_forwarder' ? '&type=freight_forwarder' : '' ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Party</a>
    <?php endif; ?>
</div>

<div id="logixZeroBanner" class="alert alert-warning border-warning mb-3" style="border-radius:12px;display:none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold"><span id="logixZeroName"></span> balance is wrong</div>
            <div class="small text-muted mb-0">
                Shows <strong id="logixZeroBalance"></strong>
                but should be Clear. Click once to force ledger to 0 (admin).
            </div>
        </div>
        <form method="POST" action="?page=parties&action=zeroLogixBalance" id="logixZeroBalanceForm">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-slash-circle me-1"></i> Set Logix to Clear
            </button>
        </form>
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('logixZeroBalanceForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        if (!window.confirm('Set Logix One balance to Clear / zero?')) {
            e.preventDefault();
        }
    });
})();
</script>

<?php
$balanceFilter = $balanceFilter ?? 'all';
$partyListUrl = static function (string $tabType, string $balance) use ($type): string {
    return '?page=parties&type=' . urlencode($tabType) . '&balance=' . urlencode($balance);
};
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

/* Match Sales list typography */
#partiesTable {
    border-collapse: collapse;
    width: auto;
    max-width: 100%;
    table-layout: auto;
    font-size: 0.83rem;
}
#partiesTable th.party-acc-col,
#partiesTable td.party-acc-col {
    width: 1%;
    white-space: nowrap;
    padding-right: 10px;
}
#partiesTable th.party-name-col,
#partiesTable td.party-name-col {
    width: auto;
    min-width: 280px;
    max-width: 480px;
    white-space: nowrap;
    padding-left: 8px;
}
#partiesTable .party-name {
    display: block;
    max-width: 480px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 600;
    color: #6366f1;
    text-decoration: none;
}
#partiesTable .btn-sm {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
#partiesTable .party-acc-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
#partiesTable .party-balance {
    font-weight: 700;
    white-space: nowrap;
}
#partiesTable .party-balance.is-due { color: #ef4444; }
#partiesTable .party-balance.is-credit { color: #6366f1; }
#partiesTable .party-balance.is-clear { color: #10b981; font-weight: 600; }
#partiesTable .party-balance.is-pending { color: #94a3b8; font-weight: 500; }
#partiesTable tr.party-awaiting-bal { display: none; }
#partySearch {
    padding: 8px 14px 8px 40px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1.5px solid #c7d2fe;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    outline: none;
    transition: border-color 0.15s;
}
#partySearch:focus {
    border-color: #6366f1;
}
</style>

<!-- Filters: type + balance on one line -->
<div class="party-filter-bar">
    <div class="party-filter-group">
        <span class="party-filter-label">Type</span>
        <div class="party-segmented" role="group" aria-label="Party type">
            <?php
            $canCustomers = Auth::canAny(['parties', 'customers'], 'view');
            $canSuppliers = Auth::can('suppliers', 'view');
            $hasPartyMaster = Auth::can('parties', 'view');
            $tabs = [];
            // Without Party Master, show only the type(s) they are allowed — no All/Both/Freight mix
            if ($hasPartyMaster) {
                if ($canCustomers && $canSuppliers) $tabs['all'] = 'All';
                if ($canCustomers) $tabs['customer'] = 'Customers';
                if ($canSuppliers) $tabs['supplier'] = 'Suppliers';
                if ($canCustomers && $canSuppliers) $tabs['both'] = 'Both';
                if ($canSuppliers) $tabs['freight_forwarder'] = 'Freight forwarders';
            } else {
                if ($canCustomers) $tabs['customer'] = 'Customers';
                if ($canSuppliers) $tabs['supplier'] = 'Suppliers';
            }
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
    <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6366f1;font-size:0.9rem;z-index:2;pointer-events:none;"></i>
    <input type="text" id="partySearch" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
           placeholder="Search by account no, name, phone, area...">
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0" id="partiesTable">
            <thead>
                <tr>
                    <th class="th-blue" style="width:40px;">#</th>
                    <th class="th-blue party-acc-col">Acc No</th>
                    <th class="th-blue party-name-col">Name</th>
                    <th class="th-blue" style="width:120px;">Type</th>
                    <th class="th-blue" style="width:80px;">Status</th>
                    <th class="th-blue" style="width:120px;text-align:right;">Balance Due</th>
                    <th class="th-blue" style="width:118px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parties)): ?>
                <tr class="party-empty-row"><td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    <?= 'No parties found' ?>
                </td></tr>
                <?php else: ?>
                <tr class="party-empty-filter-row" style="display:none;"><td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    <span class="party-empty-filter-msg"></span>
                </td></tr>
                <?php foreach ($parties as $i => $p): ?>
                <tr class="party-data-row<?= ($balanceFilter === 'due' || $balanceFilter === 'clear') ? ' party-awaiting-bal' : '' ?>"
                    data-party-id="<?= (int) $p['id'] ?>"
                    data-phone="<?= htmlspecialchars((string) ($p['phone'] ?? ''), ENT_QUOTES) ?>"
                    data-area="<?= htmlspecialchars((string) ($p['city'] ?? ''), ENT_QUOTES) ?>">
                    <td style="color:var(--text-muted);text-align:center;"><?= $i + 1 ?></td>
                    <td class="party-acc-col">
                        <?php if (!empty($p['party_code'])): ?>
                        <span class="party-acc-no"><?= htmlspecialchars($p['party_code']) ?></span>
                        <?php else: ?>
                        <span style="color:#94a3b8;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="party-name-col">
                        <a href="?page=parties&action=detail&id=<?= $p['id'] ?>" class="party-name">
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
                        <?php if (in_array($p['type'], ['customer', 'both'], true)): ?>
                        <span class="badge ms-1" style="border-radius:5px;background:<?= Party::isRetailCustomer($p['customer_kind'] ?? null) ? 'rgba(99,102,241,0.14);color:#4338ca' : 'rgba(245,158,11,0.14);color:#b45309' ?>;">
                            <?= htmlspecialchars(Party::customerKindLabel($p['customer_kind'] ?? null)) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $p['is_active'] ? 'badge-paid' : 'badge-draft' ?> px-2" style="border-radius:5px;">
                            <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td style="text-align:right;" class="party-bal-cell">
                        <span class="party-balance is-pending">…</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?page=parties&action=detail&id=<?= $p['id'] ?>"
                               class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="Ledger"><i class="bi bi-eye"></i></a>
                            <?php if (in_array($p['type'], ['customer','both'])): ?>
                            <a href="?page=parties&action=agentStatement&id=<?= $p['id'] ?>"
                               class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Agent Statement"><i class="bi bi-person-lines-fill"></i></a>
                            <?php if (!empty($p['statement_token'])): ?>
                            <button type="button" onclick="copyStatementLink('<?= htmlspecialchars((string) $p['statement_token'], ENT_QUOTES, 'UTF-8') ?>', this)"
                               class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:#6366f1;border:none;" title="Copy Field Statement Link (iqbal.app/s/…)"><i class="bi bi-link-45deg"></i></button>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php
                            $isSupplierSide = in_array($p['type'], ['supplier', 'freight_forwarder'], true);
                            $canEditRow = $isSupplierSide
                                ? Auth::can('suppliers', 'edit')
                                : Auth::canAny(['parties', 'customers'], 'edit');
                            ?>
                            <?php if ($canEditRow): ?>
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
</div>
<script>
(function () {
    var CUR = <?= json_encode(APP_CURRENCY) ?>;
    var DEC = <?= (int) DECIMAL_PLACES ?>;
    var balanceFilter = <?= json_encode($balanceFilter) ?>;
    var listType = <?= json_encode($type ?? 'all') ?>;
    var balancesReady = false;

    function formatNum(n) {
        return Number(n).toLocaleString('en-US', { minimumFractionDigits: DEC, maximumFractionDigits: DEC });
    }
    function formatBal(amount) {
        if (Math.abs(amount) < 0.001) {
            return { html: '\u2713 Clear', cls: 'is-clear' };
        }
        if (amount > 0) {
            return { html: CUR + ' ' + formatNum(amount), cls: 'is-due' };
        }
        return { html: '-' + CUR + ' ' + formatNum(Math.abs(amount)), cls: 'is-credit' };
    }
    function applyFilters() {
        var table = document.getElementById('partiesTable');
        var input = document.getElementById('partySearch');
        if (!table) return;
        var q = (input && input.value ? input.value : '').toLowerCase();
        var shown = 0;
        table.querySelectorAll('tbody tr.party-data-row').forEach(function (row) {
            if (!balancesReady && (balanceFilter === 'due' || balanceFilter === 'clear')) {
                row.classList.add('party-awaiting-bal');
                row.style.display = '';
                return;
            }
            row.classList.remove('party-awaiting-bal');
            var text = (row.textContent || '') + ' ' + (row.getAttribute('data-phone') || '') + ' ' + (row.getAttribute('data-area') || '');
            var textOk = !q || text.toLowerCase().indexOf(q) > -1;
            var bal = parseFloat(row.getAttribute('data-balance') || '0');
            var balOk = true;
            if (balancesReady && balanceFilter === 'due') balOk = Math.abs(bal) > 0.001;
            if (balancesReady && balanceFilter === 'clear') balOk = Math.abs(bal) <= 0.001;
            var vis = textOk && balOk;
            row.style.display = vis ? '' : 'none';
            if (vis) {
                shown++;
                var numCell = row.querySelector('td');
                if (numCell) numCell.textContent = String(shown);
            }
        });
        var empty = table.querySelector('tbody tr.party-empty-filter-row');
        if (empty) {
            var msg = empty.querySelector('.party-empty-filter-msg');
            if (balancesReady && shown === 0 && table.querySelectorAll('tbody tr.party-data-row').length) {
                empty.style.display = '';
                if (msg) {
                    msg.textContent = q
                        ? 'No matching parties'
                        : (balanceFilter === 'due'
                            ? 'No parties with outstanding balance'
                            : (balanceFilter === 'clear' ? 'No parties without balance' : 'No parties found'));
                }
            } else {
                empty.style.display = 'none';
            }
        }
    }

    window.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('partySearch');
        if (input) input.addEventListener('input', applyFilters);

        var params = new URLSearchParams({ page: 'parties', action: 'listBalances', type: listType });
        fetch('?' + params.toString(), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var map = (data && data.balances) ? data.balances : {};
                document.querySelectorAll('#partiesTable tbody tr.party-data-row').forEach(function (row) {
                    var id = row.getAttribute('data-party-id');
                    var amount = parseFloat(map[id] != null ? map[id] : 0);
                    row.setAttribute('data-balance', String(amount));
                    var cell = row.querySelector('.party-bal-cell');
                    if (!cell) return;
                    var fmt = formatBal(amount);
                    cell.innerHTML = '<span class="party-balance ' + fmt.cls + '">' + fmt.html + '</span>';
                });
                balancesReady = true;
                applyFilters();
                if (data && data.logixZero) {
                    var wrap = document.getElementById('logixZeroBanner');
                    var nameEl = document.getElementById('logixZeroName');
                    var balEl = document.getElementById('logixZeroBalance');
                    if (wrap && nameEl && balEl) {
                        nameEl.textContent = data.logixZero.party_name || '';
                        var lz = formatBal(parseFloat(data.logixZero.balance || 0));
                        balEl.textContent = lz.html;
                        wrap.style.display = '';
                    }
                }
            })
            .catch(function () {
                document.querySelectorAll('#partiesTable .party-balance.is-pending').forEach(function (el) {
                    el.textContent = '—';
                });
                balancesReady = true;
                if (balanceFilter === 'due' || balanceFilter === 'clear') {
                    balanceFilter = 'all';
                }
                applyFilters();
            });
    });
})();
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
