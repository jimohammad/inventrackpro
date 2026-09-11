<style>
.acc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
.acc-header h1{font-size:1.4rem;font-weight:800;color:var(--text-main);margin:0;}
.acc-header p{color:var(--text-muted);font-size:0.82rem;margin:2px 0 0;}
.acc-actions{display:flex;gap:10px;}
.btn-acc{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:0.875rem;font-weight:700;cursor:pointer;border:none;transition:all 0.15s;text-decoration:none;}
.btn-new-acc{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 3px 10px rgba(99,102,241,0.3);}
.btn-new-acc:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(99,102,241,0.4);color:#fff;}
.btn-transfer{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 3px 10px rgba(16,185,129,0.3);}
.btn-transfer:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(16,185,129,0.4);color:#fff;}

/* Account Grid */
.acc-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;margin-bottom:16px;}
.acc-row{
    display:flex;flex-direction:column;gap:4px;
    background:var(--bg-card);border:1px solid var(--border-color);
    border-left:3px solid var(--acc-color,var(--primary));
    border-radius:8px;padding:10px 12px;
    transition:all 0.15s;position:relative;
}
.acc-row:hover{box-shadow:0 2px 8px rgba(0,0,0,0.08);border-left-width:4px;}
.acc-row.active{box-shadow:0 0 0 2px var(--acc-color,var(--primary));border-left-width:4px;background:rgba(99,102,241,0.03);}
a.acc-row-link{text-decoration:none;display:contents;}
.acc-row-top{display:flex;align-items:center;gap:8px;}
.acc-row-icon{
    width:24px;height:24px;border-radius:6px;
    display:flex;align-items:center;justify-content:center;
    font-size:0.75rem;flex-shrink:0;
}
.acc-row-info{flex:1;min-width:0;}
.acc-row-name{font-weight:700;font-size:0.82rem;color:var(--text-main);line-height:1.2;}
.acc-row-type{font-size:0.62rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.4px;}
.acc-row-bal{
    font-size:1rem;font-weight:800;
    color:#10b981;margin-top:2px;
}
.acc-row-bal.neg{color:#ef4444;}
.acc-row-del{
    position:absolute;top:6px;right:8px;
    opacity:0;transition:opacity 0.15s;
}
.acc-row:hover .acc-row-del{opacity:1;}
.acc-row-del button{
    background:none;border:none;cursor:pointer;
    color:var(--text-muted);font-size:0.72rem;padding:2px;
}
.acc-row-del button:hover{color:#ef4444;}

/* Total bar */
.acc-total-bar{
    display:flex;justify-content:flex-end;align-items:center;gap:10px;
    padding:8px 4px;
    font-size:0.8rem;color:var(--text-muted);
    border-top:1px solid var(--border-color);
    margin-top:4px;
}
.acc-total-val{font-size:1rem;font-weight:800;color:var(--primary);}

/* Panel (new account + transfer forms) */
.acc-panel{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;margin-bottom:24px;overflow:hidden;}
.acc-panel-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);}
.acc-panel-header.purple{background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(139,92,246,0.05));}
.acc-panel-header.green{background:linear-gradient(135deg,rgba(16,185,129,0.08),rgba(5,150,105,0.05));}
.acc-panel-title{font-weight:700;font-size:0.9rem;color:var(--text-main);display:flex;align-items:center;gap:8px;}
.panel-close{background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer;padding:0;line-height:1;}
.panel-close:hover{color:var(--danger);}
.acc-panel-body{padding:20px;}
.acc-field label{display:block;font-size:0.77rem;font-weight:600;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.4px;}
.acc-field input,.acc-field select,.acc-field textarea{width:100%;padding:9px 12px;border:2px solid var(--border-color);border-radius:9px;font-size:0.85rem;background:var(--bg-main);color:var(--text-main);outline:none;transition:border-color 0.15s;}
.acc-field input:focus,.acc-field select:focus,.acc-field textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.acc-field-green input:focus,.acc-field-green select:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,0.1);}
.acc-form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;}
.acc-save-row{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);}
.btn-panel-save{padding:9px 24px;border:none;color:#fff;border-radius:9px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.15s;}
.btn-panel-save.purple{background:linear-gradient(135deg,#6366f1,#4f46e5);}
.btn-panel-save.green{background:linear-gradient(135deg,#10b981,#059669);}
.btn-panel-cancel{padding:9px 18px;background:var(--bg-main);border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:9px;font-weight:500;font-size:0.88rem;cursor:pointer;}

/* Transfer arrow */
.transfer-arrow{display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#10b981;padding-top:18px;}

/* History table */
.hist-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;}
.hist-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);}
.hist-head span{font-weight:700;font-size:0.875rem;color:var(--text-main);display:flex;align-items:center;gap:8px;}
table.hist-tbl{width:100%;border-collapse:collapse;font-size:0.83rem;}
table.hist-tbl th{padding:9px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);border-bottom:2px solid var(--border-color);}
table.hist-tbl td{padding:10px 14px;border-bottom:1px solid var(--border-color);color:var(--text-main);vertical-align:middle;}
table.hist-tbl tbody tr:last-child td{border-bottom:none;}
table.hist-tbl tbody tr:hover{background:rgba(16,185,129,0.03);}
.trf-no{font-weight:700;color:#10b981;font-family:monospace;font-size:0.8rem;}
.trf-from{background:rgba(239,68,68,0.1);color:#ef4444;padding:3px 9px;border-radius:20px;font-size:0.72rem;font-weight:700;}
.trf-to{background:rgba(16,185,129,0.1);color:#10b981;padding:3px 9px;border-radius:20px;font-size:0.72rem;font-weight:700;}
.trf-arrow{color:var(--text-muted);font-size:0.9rem;margin:0 6px;}
.trf-amount{font-weight:800;color:#1e293b;font-size:0.9rem;}
[data-theme="dark"] .trf-amount{color:#e2e8f0;}
.hist-empty{text-align:center;padding:40px;color:var(--text-muted);font-size:0.85rem;}
.trf-edit{
    display:inline-flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:8px;border:none;padding:0;
    background:rgba(245,158,11,.14);color:#b45309;text-decoration:none;
}
.trf-edit:hover{color:#92400e;filter:brightness(1.05);}

/* Transfer modal */
.acc-modal-backdrop{
    position:fixed;inset:0;z-index:1050;
    background:rgba(15,23,42,0.45);backdrop-filter:blur(2px);
    display:none;align-items:center;justify-content:center;
    padding:16px;
}
.acc-modal-backdrop.is-open{display:flex;}
.acc-modal{
    width:100%;max-width:640px;max-height:min(92vh,820px);overflow:auto;
    background:var(--bg-card);border:1px solid var(--border-color);
    border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,0.28);
    animation:accModalIn 0.18s ease-out;
}
@keyframes accModalIn{
    from{opacity:0;transform:translateY(10px) scale(0.98);}
    to{opacity:1;transform:translateY(0) scale(1);}
}
.acc-modal .acc-panel-header{position:sticky;top:0;z-index:1;background:linear-gradient(135deg,rgba(16,185,129,0.12),rgba(5,150,105,0.06));}
.acc-form-row.trf-form-row{
    display:grid;
    grid-template-columns:minmax(0,1fr) 40px minmax(0,1fr);
    gap:14px;
    align-items:start;
}
.acc-form-row.trf-form-meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:14px;
}
@media(max-width:640px){
    .acc-form-row.trf-form-row{grid-template-columns:1fr;}
    .acc-form-row.trf-form-row .transfer-arrow{padding-top:0;transform:rotate(90deg);}
    .acc-form-row.trf-form-meta{grid-template-columns:1fr;}
}
body.acc-modal-open{overflow:hidden;}

/* Account filters */
.acc-filter-bar{
    display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    width:100%;margin-bottom:12px;padding:8px 12px;
    background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
    border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,0.04);
}
.acc-filter-group{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.acc-filter-group--balance{margin-left:auto;}
.acc-filter-label{
    font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;
    color:#94a3b8;white-space:nowrap;
}
.acc-segmented{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;}
.acc-segmented button{
    display:inline-flex;align-items:center;gap:4px;padding:6px 12px;
    font-size:0.8rem;font-weight:600;line-height:1.2;border-radius:999px;white-space:nowrap;
    border:1px solid transparent;cursor:pointer;background:transparent;
    transition:background 0.15s ease,color 0.15s ease,box-shadow 0.15s ease,transform 0.15s ease;
}
.acc-segmented button:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(15,23,42,0.08);}
.acc-segmented button.is-active{box-shadow:0 2px 8px rgba(15,23,42,0.14);}
.acc-segmented button.af-all{color:#475569;background:#f1f5f9;border-color:#e2e8f0;}
.acc-segmented button.af-all.is-active{background:#64748b;color:#fff;border-color:#64748b;}
.acc-segmented button.af-cash{color:#047857;background:rgba(16,185,129,0.14);border-color:rgba(16,185,129,0.28);}
.acc-segmented button.af-cash.is-active{background:#10b981;color:#fff;border-color:#10b981;}
.acc-segmented button.af-bank{color:#1d4ed8;background:rgba(59,130,246,0.14);border-color:rgba(59,130,246,0.28);}
.acc-segmented button.af-bank.is-active{background:#3b82f6;color:#fff;border-color:#3b82f6;}
.acc-segmented button.af-mobile_wallet{color:#6d28d9;background:rgba(139,92,246,0.14);border-color:rgba(139,92,246,0.28);}
.acc-segmented button.af-mobile_wallet.is-active{background:#8b5cf6;color:#fff;border-color:#8b5cf6;}
.acc-segmented button.af-other{color:#b45309;background:rgba(245,158,11,0.16);border-color:rgba(245,158,11,0.32);}
.acc-segmented button.af-other.is-active{background:#f59e0b;color:#fff;border-color:#f59e0b;}
.acc-segmented button.af-bal-all{color:#4338ca;background:rgba(99,102,241,0.12);border-color:rgba(99,102,241,0.24);}
.acc-segmented button.af-bal-all.is-active{background:#6366f1;color:#fff;border-color:#6366f1;}
.acc-segmented button.af-bal-positive{color:#059669;background:rgba(16,185,129,0.14);border-color:rgba(16,185,129,0.28);}
.acc-segmented button.af-bal-positive.is-active{background:#10b981;color:#fff;border-color:#10b981;}
.acc-segmented button.af-bal-zero{color:#64748b;background:#f1f5f9;border-color:#e2e8f0;}
.acc-segmented button.af-bal-zero.is-active{background:#64748b;color:#fff;border-color:#64748b;}
.acc-segmented button.af-bal-negative{color:#dc2626;background:rgba(239,68,68,0.12);border-color:rgba(239,68,68,0.26);}
.acc-segmented button.af-bal-negative.is-active{background:#ef4444;color:#fff;border-color:#ef4444;}
.acc-filter-meta{font-size:0.75rem;color:var(--text-muted);margin:-6px 0 12px;min-height:1.1em;}
.acc-card-wrap.is-hidden{display:none !important;}
.acc-empty-filter{
    grid-column:1/-1;text-align:center;padding:28px 16px;color:var(--text-muted);font-size:0.85rem;display:none;
}
.acc-empty-filter.is-visible{display:block;}
.txn-filter-bar{
    display:flex;align-items:center;gap:12px;flex-wrap:wrap;
    padding:12px 16px;border-bottom:1px solid var(--border-color);background:rgba(248,250,252,0.7);
}
.txn-search-wrap{position:relative;flex:1;min-width:180px;}
.txn-search-wrap .bi-search{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6366f1;pointer-events:none;font-size:0.9rem;
}
.txn-search-input{
    width:100%;padding:8px 12px 8px 36px;border:1.5px solid #e0e7ff;border-radius:8px;
    font-size:0.85rem;background:#fff;outline:none;color:var(--text-main);
}
.txn-search-input:focus{border-color:#818cf8;box-shadow:0 0 0 3px rgba(99,102,241,0.1);}
.txn-type-select{
    padding:8px 12px;border:1.5px solid #e0e7ff;border-radius:8px;font-size:0.82rem;
    font-weight:600;background:#fff;color:var(--text-main);outline:none;min-width:140px;
}
.txn-type-select:focus{border-color:#818cf8;}
.txn-dir-select{
    padding:8px 12px;border:1.5px solid #e0e7ff;border-radius:8px;font-size:0.82rem;
    font-weight:600;background:#fff;color:var(--text-main);outline:none;min-width:120px;
}
.txn-dir-select:focus{border-color:#818cf8;}
.txn-filter-meta{font-size:0.72rem;color:var(--text-muted);white-space:nowrap;}
#accountTxnTable tbody tr.is-hidden{display:none;}
#accountTxnTable tbody tr.txn-selectable{cursor:pointer;}
#accountTxnTable tbody tr.is-selected{background:rgba(99,102,241,0.08);}
#accountTxnTable tbody tr.is-selected td{box-shadow:inset 3px 0 0 #6366f1;}
.txn-edit-selected{
    font-size:.78rem;font-weight:700;text-decoration:none;
    opacity:.45;
}
.txn-edit-selected:not(.is-ready){cursor:not-allowed;}
.txn-edit-selected.is-ready{opacity:1;cursor:pointer;}
.txn-lock{opacity:.35;cursor:not-allowed;}
a.trf-no{text-decoration:none;}
a.trf-no:hover{text-decoration:underline;}
</style>

<!-- Header -->
<div class="acc-header">
    <div>
        <h1><i class="bi bi-wallet2 me-2" style="color:#6366f1;"></i>Accounts</h1>
        <p>Manage cash, bank and wallet balances</p>
    </div>
    <div class="acc-actions">
        <?php if (Auth::can('settings', 'add') || Auth::isAdmin()): ?>
        <button type="button" class="btn-acc btn-transfer" onclick="openTransferModal()" title="Transfer funds (Alt+T)">
            <i class="bi bi-arrow-left-right"></i> Transfer
            <kbd style="margin-left:4px;padding:1px 5px;border-radius:4px;background:rgba(255,255,255,0.22);font-size:0.68rem;font-weight:700;letter-spacing:0.02em;">Alt+T</kbd>
        </button>
        <?php endif; ?>
        <?php if (Auth::can('settings', 'edit') || Auth::isAdmin()): ?>
        <button class="btn-acc" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 3px 10px rgba(245,158,11,0.3);" onclick="togglePanel('adjustPanel')">
            <i class="bi bi-sliders"></i> Adjust Balance
        </button>
        <?php endif; ?>
        <?php if (Auth::can('settings', 'add') || Auth::isAdmin()): ?>
        <button class="btn-acc btn-new-acc" onclick="togglePanel('newAccPanel')">
            <i class="bi bi-plus-lg"></i> New Account
        </button>
        <?php endif; ?>
    </div>
</div>

<?php
$typeColors = ['cash' => '#10b981', 'bank' => '#3b82f6', 'mobile_wallet' => '#8b5cf6', 'other' => '#f59e0b'];
$typeIcons  = ['cash' => 'bi-cash-stack', 'bank' => 'bi-bank', 'mobile_wallet' => 'bi-phone', 'other' => 'bi-wallet2'];
$typeCounts = ['cash' => 0, 'bank' => 0, 'mobile_wallet' => 0, 'other' => 0];
$totalBal = 0;
foreach ($accounts as $accCountRow) {
    $t = $accCountRow['normalized_type'] ?? $accCountRow['type'] ?? 'other';
    if (!isset($typeCounts[$t])) {
        $t = 'other';
    }
    $typeCounts[$t]++;
    $totalBal += (float) $accCountRow['current_balance'];
}
$accCountAll = count($accounts);
?>

<?php if (!empty($accounts)): ?>
<div class="acc-filter-bar" id="accFilterBar">
    <div class="acc-filter-group">
        <span class="acc-filter-label">Type</span>
        <div class="acc-segmented" role="group" aria-label="Account type">
            <button type="button" class="af-all is-active" data-acc-type="all">All <span style="opacity:.75;">(<?= $accCountAll ?>)</span></button>
            <?php if ($typeCounts['cash'] > 0): ?>
            <button type="button" class="af-cash" data-acc-type="cash"><i class="bi bi-cash-stack"></i> Cash <span style="opacity:.75;">(<?= $typeCounts['cash'] ?>)</span></button>
            <?php endif; ?>
            <?php if ($typeCounts['bank'] > 0): ?>
            <button type="button" class="af-bank" data-acc-type="bank"><i class="bi bi-bank"></i> Bank <span style="opacity:.75;">(<?= $typeCounts['bank'] ?>)</span></button>
            <?php endif; ?>
            <?php if ($typeCounts['mobile_wallet'] > 0): ?>
            <button type="button" class="af-mobile_wallet" data-acc-type="mobile_wallet"><i class="bi bi-phone"></i> Wallet <span style="opacity:.75;">(<?= $typeCounts['mobile_wallet'] ?>)</span></button>
            <?php endif; ?>
            <?php if ($typeCounts['other'] > 0): ?>
            <button type="button" class="af-other" data-acc-type="other"><i class="bi bi-wallet2"></i> Other <span style="opacity:.75;">(<?= $typeCounts['other'] ?>)</span></button>
            <?php endif; ?>
        </div>
    </div>
    <div class="acc-filter-group acc-filter-group--balance">
        <span class="acc-filter-label">Balance</span>
        <div class="acc-segmented" role="group" aria-label="Balance filter">
            <button type="button" class="af-bal-all is-active" data-acc-balance="all">All</button>
            <button type="button" class="af-bal-positive" data-acc-balance="positive">Positive</button>
            <button type="button" class="af-bal-zero" data-acc-balance="zero">Zero</button>
            <button type="button" class="af-bal-negative" data-acc-balance="negative">Negative</button>
        </div>
    </div>
</div>
<div class="acc-filter-meta" id="accFilterMeta"></div>
<?php endif; ?>

<!-- Accounts List -->
<div class="acc-list" id="accList">
    <?php if (empty($accounts)): ?>
    <div class="alert alert-info border-0 text-start mx-auto" style="max-width:520px;padding:24px;">
        <div class="fw-bold mb-2"><i class="bi bi-bank me-1"></i> No ledger accounts yet</div>
        <?php if (Auth::can('settings', 'add') || Auth::isAdmin()): ?>
        <ol class="small mb-3 ps-3">
            <li>Click the purple <strong>New Account</strong> button above</li>
            <li>Name it (e.g. <em>NBK Bank Account</em>, <em>Main Cash</em>)</li>
            <li>Choose type: <strong>Bank</strong> for bank accounts, <strong>Cash</strong> for drawer</li>
            <li>Enter opening balance if you know today’s balance</li>
            <li>Click <strong>Create Account</strong></li>
        </ol>
        <button type="button" class="btn btn-sm btn-primary" onclick="togglePanel('newAccPanel')">
            <i class="bi bi-plus-lg me-1"></i> New Account
        </button>
        <?php else: ?>
        <p class="small mb-0">Refresh this page — default accounts may appear automatically. To add more, ask an admin with Settings access.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php foreach ($accounts as $acc):
        $accType = $acc['normalized_type'] ?? $acc['type'];
        $color = $typeColors[$accType] ?? '#6366f1';
        $icon  = $typeIcons[$accType] ?? 'bi-wallet2';
        $bal   = (float) $acc['current_balance'];
        $isPos = $bal >= 0;
        $isActive = ($selectedAccountId === (int)$acc['id']);
    ?>
    <a href="?page=accounts<?= $isActive ? '' : '&account_id=' . $acc['id'] ?>"
       class="acc-card-wrap"
       style="text-decoration:none;color:inherit;"
       data-acc-type="<?= htmlspecialchars((string) $accType) ?>"
       data-acc-balance="<?= $bal > 0.0005 ? 'positive' : ($bal < -0.0005 ? 'negative' : 'zero') ?>"
       data-acc-amount="<?= htmlspecialchars((string) $bal) ?>">
    <div class="acc-row <?= $isActive ? 'active' : '' ?>" style="--acc-color:<?= $color ?>;cursor:pointer;">
        <div class="acc-row-del">
            <form method="POST" action="?page=accounts&action=delete" style="display:inline;"
                  onsubmit="event.stopPropagation();return confirm('Delete this account?')">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                <button type="submit" title="Delete"><i class="bi bi-trash3"></i></button>
            </form>
        </div>
        <div class="acc-row-top">
            <div class="acc-row-icon" style="background:<?= $color ?>15;color:<?= $color ?>;">
                <i class="bi <?= $icon ?>"></i>
            </div>
            <div class="acc-row-info">
                <div class="acc-row-name"><?= htmlspecialchars($acc['name']) ?><?= $acc['is_default'] ? ' <span style="color:var(--primary);font-size:0.6rem;">✦</span>' : '' ?></div>
                <div class="acc-row-type">
                    <?php if (!empty($acc['gl_code'])): ?>
                    <span style="font-family:monospace;background:rgba(99,102,241,0.1);color:#6366f1;padding:1px 5px;border-radius:4px;font-size:0.6rem;font-weight:700;margin-right:4px;"><?= htmlspecialchars($acc['gl_code']) ?></span>
                    <?php endif; ?>
                    <?= ucfirst(str_replace('_',' ',$accType)) ?>
                </div>
            </div>
        </div>
        <div class="acc-row-bal <?= $isPos ? 'pos' : 'neg' ?>">
            <?= APP_CURRENCY ?> <?= number_format($bal, DECIMAL_PLACES) ?>
        </div>
    </div>
    </a>
    <?php endforeach; ?>
    <div class="acc-empty-filter" id="accEmptyFilter">
        <i class="bi bi-search" style="font-size:1.6rem;opacity:.35;display:block;margin-bottom:8px;"></i>
        No accounts match your filters.
    </div>
    <?php if (!empty($accounts)): ?>
    <div class="acc-total-bar" id="accTotalBar" style="grid-column:1/-1;">
        <span id="accTotalLabel">Total Balance</span>
        <span class="acc-total-val" id="accTotalVal" data-currency="<?= htmlspecialchars(APP_CURRENCY) ?>" data-decimals="<?= (int) DECIMAL_PLACES ?>"><?= APP_CURRENCY ?> <?= number_format($totalBal, DECIMAL_PLACES) ?></span>
    </div>
    <?php endif; ?>
</div>

<?php if (Auth::can('settings', 'add') || Auth::isAdmin()): ?>
<!-- Transfer Funds Modal -->
<div class="acc-modal-backdrop" id="transferModal" role="dialog" aria-modal="true" aria-labelledby="transferModalTitle">
    <div class="acc-modal" role="document">
        <div class="acc-panel-header green">
            <div class="acc-panel-title" id="transferModalTitle"><i class="bi bi-arrow-left-right" style="color:#10b981;"></i> Transfer Funds Between Accounts</div>
            <button type="button" class="panel-close" onclick="closeTransferModal()" aria-label="Close">×</button>
        </div>
        <div class="acc-panel-body">
            <form method="POST" action="?page=accounts&action=transfer" id="transferForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="account_transfer_nonce" value="<?= htmlspecialchars($accountTransferNonce ?? '') ?>">
                <div class="acc-form-row trf-form-row">
                    <div class="acc-field acc-field-green">
                        <label>From Account <span style="color:#ef4444;">*</span></label>
                        <select name="from_account_id" id="fromAcc" required>
                            <option value="">Select source account...</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>" data-balance="<?= $a['current_balance'] ?>" <?= (int) $a['id'] === (int) ($transferDefaultFromId ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name']) ?> — <?= APP_CURRENCY ?> <?= number_format($a['current_balance'], DECIMAL_PLACES) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="fromBalance" style="margin-top:5px;font-size:0.77rem;color:#10b981;font-weight:600;min-height:16px;"></div>
                    </div>

                    <div class="transfer-arrow"><i class="bi bi-arrow-right-circle-fill" style="color:#10b981;"></i></div>

                    <div class="acc-field acc-field-green">
                        <label>To Account <span style="color:#ef4444;">*</span></label>
                        <select name="to_account_id" id="toAcc" required>
                            <option value="">Select destination...</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= (int) $a['id'] === (int) ($transferDefaultToId ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="acc-form-row trf-form-meta">
                    <div class="acc-field acc-field-green">
                        <label>Amount <span style="color:#ef4444;">*</span></label>
                        <input type="number" name="amount" step="0.001" min="0.001" placeholder="0.000" required>
                    </div>
                    <div class="acc-field acc-field-green">
                        <label>Date</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="acc-field acc-field-green" style="margin-top:14px;">
                    <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="text" name="notes" placeholder="Reason for transfer...">
                </div>
                <div class="acc-save-row">
                    <button type="button" class="btn-panel-cancel" onclick="closeTransferModal()">Cancel</button>
                    <button type="submit" class="btn-panel-save green"><i class="bi bi-arrow-left-right me-1"></i> Execute Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Account Transactions Panel -->
<?php if ($selectedAccount): ?>
<?php
    $txnIn  = array_sum(array_map(fn($t) => $t['amount'] > 0 ? $t['amount'] : 0, $accountTxns));
    $txnOut = array_sum(array_map(fn($t) => $t['amount'] < 0 ? abs($t['amount']) : 0, $accountTxns));
?>
<div class="hist-card" style="margin-bottom:24px;">
    <div class="hist-head" style="background:linear-gradient(135deg,rgba(99,102,241,0.06),rgba(139,92,246,0.03));">
        <span>
            <i class="bi bi-clock-history" style="color:var(--primary);"></i>
            <?= htmlspecialchars($selectedAccount['name']) ?> — Transactions
            <span style="font-size:.72rem;font-weight:500;color:var(--text-muted);margin-left:6px;">(last 200)</span>
        </span>
        <div style="display:flex;align-items:center;gap:16px;">
            <span style="font-size:.78rem;"><span style="color:#10b981;font-weight:700;">↑ IN <?= APP_CURRENCY ?> <?= number_format($txnIn, DECIMAL_PLACES) ?></span>&nbsp;&nbsp;<span style="color:#ef4444;font-weight:700;">↓ OUT <?= APP_CURRENCY ?> <?= number_format($txnOut, DECIMAL_PLACES) ?></span></span>
            <?php if (Auth::can('settings','edit')): ?>
            <form method="POST" action="?page=accounts&action=recalcBalance" style="display:inline;"
                  onsubmit="return confirm('Recalculate this account balance from ledger?\\n\\nUses: opening_balance + payments (excl. discount) − expenses + transfers + manual adjustments − PO paid amounts that have no linked payment row.');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="account_id" value="<?= (int)$selectedAccount['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary pin-protect" style="font-size:.78rem;">
                    <i class="bi bi-arrow-repeat me-1"></i> Recalculate
                </button>
            </form>
            <?php endif; ?>
            <?php if (Auth::isAdmin() && !empty($accountTxns)): ?>
            <button type="button" id="txnEditSelectedBtn" class="btn btn-sm btn-outline-warning txn-edit-selected"
                    aria-disabled="true" title="Select a transaction first">
                <i class="bi bi-pencil me-1"></i> Edit selected
            </button>
            <?php endif; ?>
            <a href="?page=accounts" style="font-size:.78rem;color:var(--text-muted);text-decoration:none;" title="Close">✕ Close</a>
        </div>
    </div>
    <?php if (empty($accountTxns)): ?>
    <div class="hist-empty"><i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>No transactions found for this account.</div>
    <?php else: ?>
    <div class="txn-filter-bar" id="txnFilterBar">
        <div class="txn-search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="txnSearch" class="txn-search-input" autocomplete="off" spellcheck="false"
                   placeholder="Search ref, party, notes…">
        </div>
        <select id="txnTypeFilter" class="txn-type-select" aria-label="Transaction type">
            <option value="all">All types</option>
            <option value="payment">Payment</option>
            <option value="expense">Expense</option>
            <option value="transfer">Transfer</option>
            <option value="po_payment">PO Payment</option>
            <option value="adjustment">Adjustment</option>
        </select>
        <select id="txnDirFilter" class="txn-dir-select" aria-label="Direction">
            <option value="all">In + Out</option>
            <option value="in">In only</option>
            <option value="out">Out only</option>
        </select>
        <span class="txn-filter-meta" id="txnFilterMeta"></span>
    </div>
    <table class="hist-tbl" id="accountTxnTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Party / Description</th>
                <th style="text-align:right;">Amount</th>
                <?php if (Auth::isAdmin()): ?>
                <th style="text-align:center;width:56px;">Edit</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $accLedgerId = (int) $selectedAccount['id'];
        foreach ($accountTxns as $txn):
            $amt = (float) $txn['amount'];
            $typeLabel = match($txn['txn_type']) {
                'payment'    => ['label'=>'Payment',    'color'=>'#10b981', 'bg'=>'rgba(16,185,129,.1)',  'icon'=>'bi-cash'],
                'expense'    => ['label'=>'Expense',    'color'=>'#ef4444', 'bg'=>'rgba(239,68,68,.1)',   'icon'=>'bi-receipt'],
                'transfer'   => ['label'=>'Transfer',   'color'=>'#6366f1', 'bg'=>'rgba(99,102,241,.1)',  'icon'=>'bi-arrow-left-right'],
                'po_payment' => ['label'=>'PO Payment', 'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.1)', 'icon'=>'bi-box-arrow-in-down'],
                'adjustment' => ['label'=>'Adjustment', 'color'=>'#d97706', 'bg'=>'rgba(245,158,11,.18)', 'icon'=>'bi-sliders'],
                default      => ['label'=>$txn['txn_type'], 'color'=>'#6b7280', 'bg'=>'rgba(107,114,128,.1)', 'icon'=>'bi-circle'],
            };
            $txnSearch = strtolower(trim(
                ($txn['ref_no'] ?? '') . ' ' .
                ($txn['invoice_ref'] ?? '') . ' ' .
                ($txn['party'] ?? '') . ' ' .
                ($txn['note'] ?? '') . ' ' .
                ($typeLabel['label'] ?? '')
            ));
            $txnId = (int) ($txn['id'] ?? 0);
            $txnKind = (string) ($txn['txn_type'] ?? '');
            $editUrl = '';
            $lockReason = '';
            $refUrl = '';
            if ($txnKind === 'payment') {
                $refUrl = '?page=payments&action=detail&id=' . $txnId;
                $rt = (string) ($txn['pay_ref_type'] ?? '');
                if ($rt === 'discount') {
                    $lockReason = 'Discount payments are edited from Discounts.';
                } elseif ($rt === 'purchase_order') {
                    $lockReason = 'PO advances cannot be edited.';
                } else {
                    $editUrl = '?page=payments&action=edit&id=' . $txnId . '&return_account_id=' . $accLedgerId;
                }
            } elseif ($txnKind === 'expense') {
                $editUrl = '?page=expenses&action=edit&id=' . $txnId . '&return_account_id=' . $accLedgerId;
            } elseif ($txnKind === 'transfer') {
                $editUrl = '?page=accounts&edit_transfer=' . $txnId . '&account_id=' . $accLedgerId;
            } elseif ($txnKind === 'adjustment') {
                $editUrl = '?page=accounts&account_id=' . $accLedgerId . '&edit_txn=adjustment&txn_id=' . $txnId;
            } elseif ($txnKind === 'po_payment') {
                $refUrl = '?page=purchaseorders&action=show&id=' . $txnId;
                $lockReason = 'Unlinked PO payouts cannot be edited here.';
            }
        ?>
        <tr data-txn-type="<?= htmlspecialchars((string) $txn['txn_type']) ?>"
            data-txn-dir="<?= $amt >= 0 ? 'in' : 'out' ?>"
            data-txn-search="<?= htmlspecialchars($txnSearch) ?>"
            data-txn-edit-url="<?= htmlspecialchars($editUrl) ?>"
            data-txn-lock-reason="<?= htmlspecialchars($lockReason) ?>"
            class="<?= Auth::isAdmin() ? 'txn-selectable' : '' ?>">
            <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;"><?= date('d M Y', strtotime($txn['date'])) ?></td>
            <td><?php if ($refUrl !== ''): ?><a class="trf-no" href="<?= htmlspecialchars($refUrl) ?>"><?= htmlspecialchars($txn['ref_no'] ?: '—') ?></a><?php else: ?><span class="trf-no"><?= htmlspecialchars($txn['ref_no'] ?: '—') ?></span><?php endif; ?><?php if (!empty($txn['invoice_ref'])): ?><br><span style="font-size:.7rem;color:var(--text-muted);"><?= htmlspecialchars($txn['invoice_ref']) ?></span><?php endif; ?></td>
            <td><span style="background:<?= $typeLabel['bg'] ?>;color:<?= $typeLabel['color'] ?>;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;"><i class="bi <?= $typeLabel['icon'] ?> me-1"></i><?= $typeLabel['label'] ?></span></td>
            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($txn['party'] . ($txn['note'] ? ' — ' . $txn['note'] : '')) ?>">
                <?= htmlspecialchars($txn['party']) ?>
                <?php if ($txn['note']): ?><br><span style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($txn['note']) ?></span><?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:800;font-size:.9rem;color:<?= $amt >= 0 ? '#10b981' : '#ef4444' ?>;white-space:nowrap;">
                <?= $amt >= 0 ? '+' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($amt), DECIMAL_PLACES) ?>
            </td>
            <?php if (Auth::isAdmin()): ?>
            <td style="text-align:center;" onclick="event.stopPropagation();">
                <?php if ($editUrl !== ''): ?>
                <a href="<?= htmlspecialchars($editUrl) ?>" class="trf-edit pin-protect" title="Edit transaction"><i class="bi bi-pencil"></i></a>
                <?php else: ?>
                <span class="trf-edit txn-lock" title="<?= htmlspecialchars($lockReason !== '' ? $lockReason : 'Cannot edit this transaction') ?>"><i class="bi bi-lock"></i></span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="hist-empty" id="txnEmptyFilter" style="display:none;">
        <i class="bi bi-search" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>
        No transactions match your search / filters.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Adjust Balance Panel -->
<div class="acc-panel" id="adjustPanel" style="display:none;">
    <div class="acc-panel-header" style="background:linear-gradient(135deg,rgba(245,158,11,0.08),rgba(217,119,6,0.05));">
        <div class="acc-panel-title"><i class="bi bi-sliders" style="color:#f59e0b;"></i> Adjust Account Balance</div>
        <button class="panel-close" onclick="togglePanel('adjustPanel')">×</button>
    </div>
    <div class="acc-panel-body">
        <form method="POST" action="?page=accounts&action=adjust">
            <?= Auth::csrfField() ?>
            <div class="acc-form-row">
                <div class="acc-field">
                    <label>Account <span style="color:#ef4444;">*</span></label>
                    <select name="account_id" required>
                        <option value="">-- Select Account --</option>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= APP_CURRENCY ?> <?= number_format($acc['current_balance'], DECIMAL_PLACES) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="acc-field">
                    <label>Adjustment Type <span style="color:#ef4444;">*</span></label>
                    <select name="adjust_type" required>
                        <option value="add">Add (Cash found / correction +)</option>
                        <option value="subtract">Subtract (Cash short / correction -)</option>
                    </select>
                </div>
                <div class="acc-field">
                    <label>Amount <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="amount" step="0.001" min="0.001" placeholder="0.000" required>
                </div>
                <div class="acc-field">
                    <label>Date</label>
                    <input type="date" name="adj_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="acc-field" style="margin-top:14px;">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. Cash count adjustment, short change, excess found...">
                <p style="font-size:0.72rem;color:var(--text-muted);margin:8px 0 0;">Each adjustment is stored and included when you use Recalculate.</p>
            </div>
            <div class="acc-save-row">
                <button type="button" class="btn-panel-cancel" onclick="togglePanel('adjustPanel')">Cancel</button>
                <button type="submit" class="btn-panel-save" style="background:linear-gradient(135deg,#f59e0b,#d97706);" onclick="return confirm('Are you sure you want to adjust this account balance?')">
                    <i class="bi bi-check-lg me-1"></i> Adjust Balance
                </button>
            </div>
        </form>
    </div>
</div>

<!-- New Account Panel -->
<div class="acc-panel" id="newAccPanel" style="display:none;">
    <div class="acc-panel-header purple">
        <div class="acc-panel-title"><i class="bi bi-plus-circle-fill" style="color:#6366f1;"></i> New Account</div>
        <button class="panel-close" onclick="togglePanel('newAccPanel')">×</button>
    </div>
    <div class="acc-panel-body">
        <form method="POST" action="?page=accounts&action=store">
            <?= Auth::csrfField() ?>
            <div class="acc-form-row">
                <div class="acc-field">
                    <label>Account Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" placeholder="e.g. Main Cash, BNKQ Account" required>
                </div>
                <div class="acc-field">
                    <label>Type</label>
                    <select name="type">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="mobile_wallet">Mobile Wallet</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="acc-field">
                    <label>GL Code <span style="color:var(--text-muted);font-weight:400;">(e.g. 1001)</span></label>
                    <input type="text" name="gl_code" placeholder="e.g. 1001" maxlength="10" style="font-family:monospace;">
                </div>
                <div class="acc-field">
                    <label>Opening Balance</label>
                    <input type="number" name="opening_balance" step="0.001" value="0.000" placeholder="0.000">
                </div>
            </div>
            <div class="acc-save-row">
                <button type="button" class="btn-panel-cancel" onclick="togglePanel('newAccPanel')">Cancel</button>
                <button type="submit" class="btn-panel-save purple"><i class="bi bi-check-lg me-1"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transfer Panel -->
<?php if (!empty($editingTransfer) && Auth::can('settings', 'edit')): ?>
<div class="acc-panel" id="editTransferPanel" style="display:block;border-color:rgba(245,158,11,0.35);">
    <div class="acc-panel-header" style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(217,119,6,0.05));">
        <div class="acc-panel-title"><i class="bi bi-pencil-square" style="color:#f59e0b;"></i> Edit Transfer <span style="font-size:0.78rem;font-weight:600;color:var(--text-muted);margin-left:8px;font-family:monospace;"><?= htmlspecialchars($editingTransfer['transfer_no']) ?></span></div>
        <a class="panel-close" href="?page=accounts<?= !empty($selectedAccountId) ? '&account_id=' . (int)$selectedAccountId : '' ?>" style="text-decoration:none;" title="Close">×</a>
    </div>
    <div class="acc-panel-body">
        <form method="POST" action="?page=accounts&action=updateTransfer">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$editingTransfer['id'] ?>">
            <input type="hidden" name="return_account_id" value="<?= (int)($selectedAccountId ?? 0) ?>">
            <div class="acc-form-row" style="grid-template-columns:1fr 60px 1fr 160px 200px;">

                <div class="acc-field acc-field-green">
                    <label>From Account <span style="color:#ef4444;">*</span></label>
                    <select name="from_account_id" id="fromAccEdit" required>
                        <option value="">Select source account...</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" data-balance="<?= htmlspecialchars((string)$a['current_balance']) ?>" <?= (int)$editingTransfer['from_account_id'] === (int)$a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['name']) ?> — <?= APP_CURRENCY ?> <?= number_format($a['current_balance'], DECIMAL_PLACES) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="fromBalanceEdit" style="margin-top:5px;font-size:0.77rem;color:#10b981;font-weight:600;min-height:16px;"></div>
                </div>

                <div class="transfer-arrow"><i class="bi bi-arrow-right-circle-fill" style="color:#f59e0b;"></i></div>

                <div class="acc-field acc-field-green">
                    <label>To Account <span style="color:#ef4444;">*</span></label>
                    <select name="to_account_id" required>
                        <option value="">Select destination...</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= (int)$editingTransfer['to_account_id'] === (int)$a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="acc-field acc-field-green">
                    <label>Amount <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="amount" step="0.001" min="0.001" placeholder="0.000" required value="<?= htmlspecialchars(number_format((float)$editingTransfer['amount'], DECIMAL_PLACES, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="acc-field acc-field-green">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($editingTransfer['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="acc-form-row" style="margin-top:0;">
                <div class="acc-field acc-field-green" style="grid-column:1/-1;">
                    <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="text" name="notes" placeholder="Reason for transfer..." value="<?= htmlspecialchars($editingTransfer['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="acc-save-row">
                <a class="btn-panel-cancel" href="?page=accounts<?= !empty($selectedAccountId) ? '&account_id=' . (int)$selectedAccountId : '' ?>" style="display:inline-flex;align-items:center;text-decoration:none;">Cancel</a>
                <button type="submit" class="btn-panel-save" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Edit Adjustment Panel -->
<?php if (!empty($editingAdjustment) && Auth::isAdmin()): ?>
<div class="acc-panel" id="editAdjustmentPanel" style="display:block;border-color:rgba(245,158,11,0.35);">
    <div class="acc-panel-header" style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(217,119,6,0.05));">
        <div class="acc-panel-title"><i class="bi bi-pencil-square" style="color:#f59e0b;"></i> Edit Adjustment <span style="font-size:0.78rem;font-weight:600;color:var(--text-muted);margin-left:8px;font-family:monospace;">ADJ-<?= str_pad((string)$editingAdjustment['id'], 6, '0', STR_PAD_LEFT) ?></span></div>
        <a class="panel-close" href="?page=accounts<?= !empty($selectedAccountId) ? '&account_id=' . (int)$selectedAccountId : '' ?>" style="text-decoration:none;" title="Close">×</a>
    </div>
    <div class="acc-panel-body">
        <form method="POST" action="?page=accounts&action=updateAdjustment">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$editingAdjustment['id'] ?>">
            <input type="hidden" name="return_account_id" value="<?= (int)($selectedAccountId ?? 0) ?>">
            <div class="acc-form-row">
                <div class="acc-field">
                    <label>Account <span style="color:#ef4444;">*</span></label>
                    <select name="account_id" required>
                        <option value="">-- Select Account --</option>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= (int)$editingAdjustment['account_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['name']) ?> (<?= APP_CURRENCY ?> <?= number_format($acc['current_balance'], DECIMAL_PLACES) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="acc-field">
                    <label>Adjustment Type <span style="color:#ef4444;">*</span></label>
                    <select name="adjust_type" required>
                        <option value="add" <?= ($editingAdjustment['direction'] ?? '') === 'add' ? 'selected' : '' ?>>Add (Cash found / correction +)</option>
                        <option value="subtract" <?= ($editingAdjustment['direction'] ?? '') === 'subtract' ? 'selected' : '' ?>>Subtract (Cash short / correction -)</option>
                    </select>
                </div>
                <div class="acc-field">
                    <label>Amount <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="amount" step="0.001" min="0.001" placeholder="0.000" required
                           value="<?= htmlspecialchars(number_format((float)$editingAdjustment['amount'], DECIMAL_PLACES, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="acc-field">
                    <label>Date</label>
                    <input type="date" name="adj_date" value="<?= htmlspecialchars($editingAdjustment['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="acc-field" style="margin-top:14px;">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. Cash count adjustment, short change, excess found..."
                       value="<?= htmlspecialchars($editingAdjustment['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="acc-save-row">
                <a class="btn-panel-cancel" href="?page=accounts<?= !empty($selectedAccountId) ? '&account_id=' . (int)$selectedAccountId : '' ?>" style="display:inline-flex;align-items:center;text-decoration:none;">Cancel</a>
                <button type="submit" class="btn-panel-save pin-protect" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Transfer History -->
<div class="hist-card">
    <div class="hist-head">
        <span><i class="bi bi-clock-history" style="color:#10b981;"></i> Transfer History</span>
        <span style="font-size:0.75rem;background:rgba(16,185,129,0.1);color:#10b981;padding:3px 10px;border-radius:20px;font-weight:700;"><?= count($transfers) ?> transfers</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="hist-tbl" id="transferTable">
            <thead>
                <tr>
                    <th>Transfer No</th>
                    <th>Date</th>
                    <th>From</th>
                    <th></th>
                    <th>To</th>
                    <th style="text-align:right;">Amount</th>
                    <th>Notes</th>
                    <th>By</th>
                    <?php if (Auth::can('settings', 'edit')): ?>
                    <th style="text-align:center;width:56px;">Edit</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr><td colspan="<?= Auth::can('settings', 'edit') ? '9' : '8' ?>"><div class="hist-empty"><i class="bi bi-arrow-left-right" style="font-size:2rem;opacity:0.2;display:block;margin-bottom:8px;"></i>No transfers yet</div></td></tr>
                <?php else: ?>
                <?php foreach ($transfers as $t): ?>
                <tr>
                    <td><span class="trf-no"><?= $t['transfer_no'] ?></span></td>
                    <td style="color:var(--text-muted);font-size:0.82rem;"><?= date('d M Y', strtotime($t['date'])) ?></td>
                    <td><span class="trf-from"><i class="bi bi-dash-circle me-1"></i><?= htmlspecialchars($t['from_name']) ?></span></td>
                    <td><i class="bi bi-arrow-right trf-arrow"></i></td>
                    <td><span class="trf-to"><i class="bi bi-plus-circle me-1"></i><?= htmlspecialchars($t['to_name']) ?></span></td>
                    <td style="text-align:right;"><span class="trf-amount"><?= APP_CURRENCY ?> <?= number_format($t['amount'], DECIMAL_PLACES) ?></span></td>
                    <td style="color:var(--text-muted);font-size:0.82rem;max-width:180px;"><?= htmlspecialchars($t['notes'] ?? '—') ?></td>
                    <td style="color:var(--text-muted);font-size:0.78rem;"><?= htmlspecialchars($t['created_by_name'] ?? '—') ?></td>
                    <?php if (Auth::can('settings', 'edit')): ?>
                    <td style="text-align:center;">
                        <a href="?page=accounts&edit_transfer=<?= (int)$t['id'] ?><?= !empty($selectedAccountId) ? '&account_id=' . (int)$selectedAccountId : '' ?>" class="trf-edit pin-protect" title="Edit transfer"><i class="bi bi-pencil"></i></a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function closeTransferModal() {
    var modal = document.getElementById('transferModal');
    if (!modal) return;
    modal.classList.remove('is-open');
    document.body.classList.remove('acc-modal-open');
}

function openTransferModal() {
    var modal = document.getElementById('transferModal');
    if (!modal) return;
    ['newAccPanel', 'adjustPanel'].forEach(function (p) {
        var el = document.getElementById(p);
        if (el) el.style.display = 'none';
    });
    modal.classList.add('is-open');
    document.body.classList.add('acc-modal-open');
    updateBalance();
    setTimeout(function () {
        var amount = modal.querySelector('input[name="amount"]');
        if (amount) amount.focus();
    }, 50);
}

function togglePanel(id) {
    closeTransferModal();
    const panel = document.getElementById(id);
    if (!panel) return;
    const allPanels = ['newAccPanel', 'adjustPanel'];
    allPanels.forEach(p => {
        const el = document.getElementById(p);
        if (el && p !== id) el.style.display = 'none';
    });
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
}

function updateBalance() {
    const sel = document.getElementById('fromAcc');
    const el  = document.getElementById('fromBalance');
    if (!sel || !el) return;
    const opt = sel.options[sel.selectedIndex];
    const bal = parseFloat(opt.dataset.balance || 0);
    if (sel.value) {
        el.textContent = 'Available: <?= APP_CURRENCY ?> ' + bal.toFixed(<?= DECIMAL_PLACES ?>);
        el.style.color = bal > 0 ? '#10b981' : '#ef4444';
    } else {
        el.textContent = '';
    }
}

(function initTransferModal() {
    var modal = document.getElementById('transferModal');
    if (!modal) return;
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeTransferModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeTransferModal();
        }
    });
    // Deep-link: ?page=accounts&transfer=1 (from Alt+T elsewhere)
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('transfer') === '1') {
            openTransferModal();
            params.delete('transfer');
            var clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', clean);
            }
        }
    } catch (err) { /* ignore */ }
})();

(function initAccountFilters() {
    var typeFilter = 'all';
    var balanceFilter = 'all';
    var meta = document.getElementById('accFilterMeta');
    var emptyEl = document.getElementById('accEmptyFilter');
    var totalVal = document.getElementById('accTotalVal');
    var totalLabel = document.getElementById('accTotalLabel');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.acc-card-wrap'));
    if (!cards.length) return;

    var currency = totalVal ? (totalVal.getAttribute('data-currency') || 'KWD') : 'KWD';
    var decimals = totalVal ? parseInt(totalVal.getAttribute('data-decimals') || '3', 10) : 3;

    function formatMoney(n) {
        return currency + ' ' + Number(n).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function applyFilters() {
        var visible = 0;
        var sum = 0;
        cards.forEach(function (card) {
            var typeOk = typeFilter === 'all' || card.getAttribute('data-acc-type') === typeFilter;
            var balOk = balanceFilter === 'all' || card.getAttribute('data-acc-balance') === balanceFilter;
            var show = typeOk && balOk;
            card.classList.toggle('is-hidden', !show);
            if (show) {
                visible++;
                sum += parseFloat(card.getAttribute('data-acc-amount') || '0') || 0;
            }
        });
        if (emptyEl) emptyEl.classList.toggle('is-visible', visible === 0);
        if (totalVal) totalVal.textContent = formatMoney(sum);
        if (totalLabel) {
            totalLabel.textContent = (visible === cards.length) ? 'Total Balance' : ('Filtered Total (' + visible + ')');
        }
        if (meta) {
            if (visible === cards.length && typeFilter === 'all' && balanceFilter === 'all') {
                meta.textContent = '';
            } else {
                meta.textContent = 'Showing ' + visible + ' of ' + cards.length + ' accounts';
            }
        }
    }

    document.querySelectorAll('#accFilterBar [data-acc-type]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            typeFilter = btn.getAttribute('data-acc-type') || 'all';
            document.querySelectorAll('#accFilterBar [data-acc-type]').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
            applyFilters();
        });
    });
    document.querySelectorAll('#accFilterBar [data-acc-balance]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            balanceFilter = btn.getAttribute('data-acc-balance') || 'all';
            document.querySelectorAll('#accFilterBar [data-acc-balance]').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
            applyFilters();
        });
    });
    applyFilters();
})();

(function initTxnFilters() {
    var table = document.getElementById('accountTxnTable');
    if (!table) return;
    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    var searchInput = document.getElementById('txnSearch');
    var typeSelect = document.getElementById('txnTypeFilter');
    var dirSelect = document.getElementById('txnDirFilter');
    var meta = document.getElementById('txnFilterMeta');
    var emptyEl = document.getElementById('txnEmptyFilter');
    if (!rows.length) return;

    function applyTxnFilters() {
        var q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var type = typeSelect ? typeSelect.value : 'all';
        var dir = dirSelect ? dirSelect.value : 'all';
        var visible = 0;
        rows.forEach(function (row) {
            var typeOk = type === 'all' || row.getAttribute('data-txn-type') === type;
            var dirOk = dir === 'all' || row.getAttribute('data-txn-dir') === dir;
            var searchOk = !q || (row.getAttribute('data-txn-search') || '').indexOf(q) !== -1;
            var show = typeOk && dirOk && searchOk;
            row.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });
        if (emptyEl) emptyEl.style.display = visible === 0 ? 'block' : 'none';
        if (meta) {
            meta.textContent = visible === rows.length
                ? (rows.length + ' txns')
                : (visible + ' of ' + rows.length);
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyTxnFilters);
    if (typeSelect) typeSelect.addEventListener('change', applyTxnFilters);
    if (dirSelect) dirSelect.addEventListener('change', applyTxnFilters);
    applyTxnFilters();
})();

(function initTxnSelect() {
    var table = document.getElementById('accountTxnTable');
    var btn = document.getElementById('txnEditSelectedBtn');
    if (!table || !btn) return;
    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    if (!rows.length) return;
    var selectedUrl = '';
    var selectedLock = 'Select a transaction first';

    function selectRow(row) {
        rows.forEach(function (r) { r.classList.remove('is-selected'); });
        row.classList.add('is-selected');
        var url = row.getAttribute('data-txn-edit-url') || '';
        var reason = row.getAttribute('data-txn-lock-reason') || 'Cannot edit this transaction';
        selectedUrl = url;
        selectedLock = reason || 'Cannot edit this transaction';
        if (url) {
            btn.classList.add('is-ready');
            btn.setAttribute('aria-disabled', 'false');
            btn.title = 'Edit selected transaction';
        } else {
            btn.classList.remove('is-ready');
            btn.setAttribute('aria-disabled', 'true');
            btn.title = selectedLock;
        }
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a,button,form')) return;
            selectRow(row);
        });
    });

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedUrl) {
            alert(selectedLock || 'Select a transaction first');
            return;
        }
        if (typeof requirePin === 'function') {
            requirePin(function () { window.location.href = selectedUrl; });
        } else {
            window.location.href = selectedUrl;
        }
    });
})();

(function initTransferTableWhenReady() {
    const fromAcc = document.getElementById('fromAcc');
    if (fromAcc) {
        fromAcc.addEventListener('change', updateBalance);
        updateBalance();
    }

    function initDt() {
        var tt = document.getElementById('transferTable');
        if (!tt || tt.querySelectorAll('tbody tr').length <= 5) return;
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) return;
        if (jQuery.fn.DataTable.isDataTable(tt)) return;
        var noOrder = [3];
        <?php if (Auth::can('settings', 'edit')): ?>noOrder.push(8);<?php endif; ?>
        jQuery(tt).DataTable({ pageLength: 25, order: [[0, 'desc']], columnDefs: [{ orderable: false, targets: noOrder }] });
    }
    if (typeof window.iqbalWhenIdle === 'function') window.iqbalWhenIdle(initDt);
    else if (document.readyState === 'complete') initDt();
    else window.addEventListener('load', initDt);
})();

(function() {
    var sel = document.getElementById('fromAccEdit');
    var el = document.getElementById('fromBalanceEdit');
    if (!sel || !el) return;
    function upd() {
        var opt = sel.options[sel.selectedIndex];
        var bal = parseFloat(opt.getAttribute('data-balance') || '0');
        if (sel.value) {
            el.textContent = 'Available: <?= APP_CURRENCY ?> ' + bal.toFixed(<?= (int)DECIMAL_PLACES ?>);
            el.style.color = bal > 0 ? '#10b981' : '#ef4444';
        } else {
            el.textContent = '';
        }
    }
    sel.addEventListener('change', upd);
    upd();
})();

<?php if (!empty($editingTransfer)): ?>
setTimeout(function() {
    var p = document.getElementById('editTransferPanel');
    if (p) p.scrollIntoView({ behavior: 'smooth', block: 'start' });
}, 80);
<?php endif; ?>
<?php if (!empty($editingAdjustment)): ?>
setTimeout(function() {
    var p = document.getElementById('editAdjustmentPanel');
    if (p) p.scrollIntoView({ behavior: 'smooth', block: 'start' });
}, 80);
<?php endif; ?>
</script>
