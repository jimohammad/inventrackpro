<style>
/* ══ EXPENSE PAGE ══ */
.exp-page-top{margin-bottom:22px;}
.exp-page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.exp-add-btn{
    display:inline-flex;align-items:center;gap:6px;cursor:pointer;
    padding:8px 16px;border-radius:9px;font-size:0.88rem;font-weight:700;
    background:rgba(139,92,246,0.10);color:#7c3aed;border:1px solid rgba(139,92,246,0.28);
    transition:background 0.15s,border-color 0.15s,transform 0.15s;
}
.exp-add-btn:hover{background:rgba(139,92,246,0.18);border-color:rgba(139,92,246,0.45);transform:translateY(-1px);}
.exp-add-btn:focus{outline:2px solid #a78bfa;outline-offset:2px;}
.exp-hero-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px;}
@media (max-width:900px){.exp-hero-cards{grid-template-columns:1fr;}}
.exp-hero-card{
    display:flex;flex-direction:column;align-items:flex-start;justify-content:center;
    min-height:100px;padding:16px 18px;border-radius:14px;border:1px solid var(--border-color);
    background:var(--bg-card);text-align:left;text-decoration:none;color:inherit;
    box-sizing:border-box;transition:transform 0.15s,box-shadow 0.15s,border-color 0.15s;
    position:relative;overflow:hidden;
}
a.exp-hero-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(15,23,42,0.08);border-color:#c4b5fd;}
.exp-metric-label{font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:6px;}
.exp-metric-value{font-size:1.25rem;font-weight:800;color:var(--text-main);line-height:1.15;}
.exp-metric-hint{font-size:0.72rem;color:var(--text-muted);margin-top:8px;}
.exp-hero-card-accent::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.exp-card-this::before{background:linear-gradient(90deg,#6366f1,#818cf8);}
.exp-card-last::before{background:linear-gradient(90deg,#f59e0b,#fbbf24);}

/* Add Expense modal (same overlay pattern as Accounts → Transfer Funds) */
.exp-modal-backdrop{
    position:fixed;inset:0;z-index:1050;
    background:rgba(15,23,42,0.45);backdrop-filter:blur(2px);
    display:none;align-items:center;justify-content:center;
    padding:16px;
}
.exp-modal-backdrop.is-open{display:flex;}
.exp-modal{
    width:100%;max-width:880px;max-height:min(92vh,900px);
    display:flex;flex-direction:column;
    overflow:hidden;
    background:var(--bg-card);border:1px solid var(--border-color);
    border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,0.28);
    animation:expModalIn 0.18s ease-out;
}
.exp-modal:has(.exp-acc-picker.is-open){overflow:visible;}
@keyframes expModalIn{
    from{opacity:0;transform:translateY(10px) scale(0.98);}
    to{opacity:1;transform:translateY(0) scale(1);}
}
body.exp-modal-open{overflow:hidden;}
@media (max-width:640px){
    .exp-modal{max-height:96vh;border-radius:14px;}
    .exp-form-header,.exp-form-actions{border-radius:0;}
    .exp-form-body{padding:14px 12px 6px;}
    .exp-form-actions{padding:12px;}
    .exp-row-cell-amt,.exp-rows-table thead th.col-amt{width:140px;}
}
.exp-form-header{
    display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
    padding:14px 20px;
    background:linear-gradient(135deg,rgba(139,92,246,0.10),rgba(251,113,133,0.05));
    border-bottom:1px solid var(--border-color);
    border-radius:16px 16px 0 0;
}
.exp-form-header span{font-weight:700;font-size:0.9rem;color:var(--text-main);display:flex;align-items:center;gap:8px;}
.exp-form-header span i{color:#8b5cf6;}
.exp-form-close{background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer;line-height:1;padding:0;transition:color 0.15s;}
.exp-form-close:hover{color:#8b5cf6;}
.exp-modal form{display:flex;flex-direction:column;min-height:0;flex:1;}
.exp-form-body{padding:20px 20px 8px;overflow-y:auto;flex:1;min-height:0;}
.exp-modal:has(.exp-acc-picker.is-open) .exp-form-body{overflow:visible;}

/* Meta fields: date + account */
.exp-meta-row{display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.exp-meta-field{
    display:flex;align-items:stretch;border-radius:12px;border:1.5px solid;
    overflow:hidden;transition:box-shadow 0.15s,border-color 0.15s,transform 0.15s;
}
.exp-meta-field:hover{transform:translateY(-1px);}
.exp-meta-field:focus-within{box-shadow:0 6px 20px rgba(15,23,42,0.08);}
.exp-meta-date{
    flex:1.2 1 0;min-width:280px;
    background:linear-gradient(135deg,#fafaff,#f5f3ff);border-color:#ddd6fe;
}
.exp-meta-date:focus-within{border-color:#8b5cf6;box-shadow:0 6px 20px rgba(139,92,246,0.14);}
.exp-acc-picker{
    position:relative;
    flex:1 1 0;min-width:220px;
    z-index:5;
}
.exp-acc-picker.is-open{z-index:40;}
.exp-acc-picker .exp-meta-account{width:100%;position:relative;}
.exp-meta-account{
    background:linear-gradient(135deg,#f0fdf9,#ecfdf5);border-color:#a7f3d0;
}
.exp-meta-account:hover{transform:none;}
.exp-meta-account:focus-within{border-color:#10b981;box-shadow:0 6px 20px rgba(16,185,129,0.14);}
.exp-meta-icon{
    display:flex;align-items:center;justify-content:center;width:46px;flex-shrink:0;
    font-size:1.1rem;
}
.exp-meta-date .exp-meta-icon{background:linear-gradient(180deg,rgba(139,92,246,0.16),rgba(99,102,241,0.08));color:#7c3aed;}
.exp-meta-account .exp-meta-icon{background:linear-gradient(180deg,rgba(16,185,129,0.16),rgba(5,150,105,0.08));color:#059669;}
.exp-meta-content{
    flex:1;min-width:0;padding:10px 14px 10px 2px;
    display:flex;flex-direction:column;justify-content:center;gap:3px;
}
.exp-meta-content label{
    display:flex;align-items:center;gap:4px;
    font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;
    margin:0;
}
.exp-meta-date .exp-meta-content label{color:#7c3aed;}
.exp-meta-account .exp-meta-content label{color:#047857;}
.exp-meta-req{font-size:0.72rem;line-height:1;}
.exp-meta-date .exp-meta-req{color:#a78bfa;}
.exp-meta-account .exp-meta-req{color:#34d399;}
.exp-meta-content input{
    width:100%;border:none;background:transparent;padding:0;
    font-family:inherit;font-size:0.85rem;font-weight:600;color:var(--text-main);outline:none;
    cursor:pointer;line-height:1.3;
}
.exp-meta-datetime{display:flex;align-items:center;gap:10px;}
.exp-meta-datetime input[type="date"]{flex:1.15;min-width:0;}
.exp-meta-datetime input[type="time"]{flex:0.85;min-width:0;}
.exp-meta-content input[type="date"]::-webkit-calendar-picker-indicator,
.exp-meta-content input[type="time"]::-webkit-calendar-picker-indicator{cursor:pointer;opacity:0.55;}
.exp-meta-content input[type="date"]::-webkit-calendar-picker-indicator:hover,
.exp-meta-content input[type="time"]::-webkit-calendar-picker-indicator:hover{opacity:0.85;}
.exp-acc-search{padding-right:20px !important;cursor:text;}
.exp-acc-caret{
    position:absolute;right:12px;top:50%;transform:translateY(-50%);
    color:#64748b;font-size:0.72rem;pointer-events:none;transition:transform 0.15s;
}
.exp-acc-picker.is-open .exp-acc-caret{transform:translateY(-50%) rotate(180deg);}
.exp-acc-drop{
    display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:40;
    background:#fff;border:1.5px solid #a7f3d0;border-radius:12px;
    box-shadow:0 12px 28px rgba(15,23,42,0.12);max-height:220px;overflow-y:auto;
}
.exp-acc-picker.is-open .exp-acc-drop{display:block;}
.exp-acc-opt{
    display:block;width:100%;text-align:left;border:none;background:#fff;color:#0f172a;
    padding:9px 12px;font-size:0.84rem;font-weight:650;cursor:pointer;
    border-bottom:1px solid #f1f5f9;
}
.exp-acc-opt:last-child{border-bottom:none;}
.exp-acc-opt:hover,.exp-acc-opt.is-active{background:#ecfdf5;}
.exp-acc-opt.is-selected{color:#047857;font-weight:800;}
.exp-acc-empty{padding:10px 12px;font-size:0.8rem;font-weight:600;color:#b45309;}

/* Expense line items */
.exp-lines-card{
    border:1.5px solid #e9e5ff;border-radius:14px;overflow:hidden;
    background:linear-gradient(180deg,#fff,#fcfbff);
    box-shadow:0 4px 18px rgba(139,92,246,0.06);
}
.exp-lines-table-wrap{overflow-x:auto;}
.exp-rows-table{width:100%;border-collapse:collapse;font-size:0.85rem;}
.exp-rows-table thead th{
    padding:10px 12px;font-size:0.68rem;font-weight:700;text-transform:uppercase;
    letter-spacing:0.06em;color:#94a3b8;background:#f8fafc;
    border-bottom:1.5px solid #e2e8f0;white-space:nowrap;
}
.exp-rows-table thead th.col-num{width:44px;text-align:center;}
.exp-rows-table thead th.col-amt{width:200px;text-align:right;font-size:0.72rem;}
.exp-rows-table thead th.col-act{width:44px;}
.exp-data-row{transition:background 0.12s;}
.exp-data-row:hover{background:rgba(139,92,246,0.03);}
.exp-data-row td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.exp-data-row:last-child td{border-bottom:none;}
.exp-row-num-cell{text-align:center;}
.exp-row-num{
    display:inline-flex;align-items:center;justify-content:center;
    width:26px;height:26px;border-radius:8px;
    background:rgba(139,92,246,0.1);color:#7c3aed;
    font-size:0.75rem;font-weight:800;
}
.exp-row-select,.exp-row-input{
    width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:9px;
    font-size:0.84rem;background:#fff;color:var(--text-main);outline:none;
    transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;
}
.exp-row-amount{
    width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;
    font-family:inherit;font-size:1.1rem;font-weight:700;text-align:right;
    background:#fff;color:var(--text-main);outline:none;
    transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;
    min-height:46px;
}
.exp-row-select:focus,.exp-row-input:focus,.exp-row-amount:focus{
    border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,0.1);
}
.exp-row-cell-amt{width:200px;}
.exp-row-cell-act{text-align:center;}
.exp-row-remove{
    width:28px;height:28px;border-radius:8px;
    border:1px solid rgba(239,68,68,0.2);background:rgba(239,68,68,0.06);color:#ef4444;
    display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:0.72rem;transition:all 0.15s;padding:0;
}
.exp-row-remove:hover{background:#ef4444;color:#fff;border-color:#ef4444;}
.exp-rows-total{
    display:flex;align-items:center;justify-content:flex-end;gap:14px;
    padding:12px 16px;
    background:linear-gradient(135deg,rgba(139,92,246,0.08),rgba(167,139,250,0.04));
    border-top:1.5px solid rgba(139,92,246,0.12);
}
.exp-rows-total-label{
    font-size:0.76rem;font-weight:700;text-transform:uppercase;
    letter-spacing:0.05em;color:#7c3aed;
}
.exp-rows-total-value{
    font-size:1.15rem;font-weight:800;color:#7c3aed;
    font-variant-numeric:tabular-nums;min-width:96px;text-align:right;
}
.exp-form-actions{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    flex-wrap:wrap;flex-shrink:0;
    margin:0;padding:14px 20px 18px;
    border-top:1px solid var(--border-color);
    background:var(--bg-card);
    border-radius:0 0 16px 16px;
}
.exp-form-actions-right{display:flex;gap:10px;flex-wrap:wrap;}
.btn-exp-add-row{
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 18px;background:#fff;color:#7c3aed;
    border:1.5px dashed #c4b5fd;border-radius:10px;
    font-size:0.82rem;font-weight:600;cursor:pointer;transition:all 0.15s;
}
.btn-exp-add-row:hover{background:rgba(139,92,246,0.08);border-color:#8b5cf6;border-style:solid;}

.btn-exp-save{
    padding:9px 24px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border:none;color:#fff;
    border-radius:10px;font-weight:700;font-size:0.88rem;cursor:pointer;
    transition:all 0.15s;box-shadow:0 4px 14px rgba(139,92,246,0.3);
    display:inline-flex;align-items:center;gap:6px;
}
.btn-exp-save:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(139,92,246,0.38);}
.btn-exp-cancel{
    padding:9px 18px;background:#fff;border:1.5px solid var(--border-color);color:var(--text-muted);
    border-radius:10px;font-weight:600;font-size:0.88rem;cursor:pointer;transition:all 0.15s;
}
.btn-exp-cancel:hover{border-color:#c4b5fd;color:#7c3aed;background:#fafaff;}

/* Table — same type scale as Sales / Payments / Purchases */
.card:has(#expensesTable) .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
#expensesTable {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.83rem;
}
#expensesTable .exp-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
}
#expensesTable .exp-account {
    font-weight: 600;
    color: #1e293b;
}
#expensesTable .exp-amount {
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
}
#expensesTable .exp-date {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    background: #e0f2fe;
    color: #0369a1;
}
#expensesTable tr.exp-row-today .exp-date {
    background: #dcfce7;
    color: #166534;
}
.exp-cat-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);}
.exp-desc{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.btn-del{width:26px;height:26px;border-radius:6px;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.2);color:#8b5cf6;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.15s;text-decoration:none;font-size:0.78rem;}
.btn-del:hover{background:#8b5cf6;color:#fff;border-color:#8b5cf6;}
</style>

<!-- Title + month cards + add (same row height) -->
<div class="exp-page-top">
    <div class="exp-page-head">
        <div>
            <h1 class="page-title mb-0"><i class="bi bi-receipt me-2" style="color:#8b5cf6;"></i>Expenses</h1>
        </div>
        <button type="button" class="exp-add-btn" id="expBtnOpenAddForm">
            <i class="bi bi-plus-lg"></i> Add expense
        </button>
    </div>
    <div class="exp-hero-cards">
        <a class="exp-hero-card exp-hero-card-accent exp-card-this"
           href="?page=expenses&amp;from_date=<?= htmlspecialchars($thisMonthStart) ?>&amp;to_date=<?= htmlspecialchars($thisMonthEnd) ?>">
            <span class="exp-metric-label">This month</span>
            <span class="exp-metric-value"><?= APP_CURRENCY ?> <?= number_format($expenseThisMonth ?? 0, DECIMAL_PLACES) ?></span>
            <span class="exp-metric-hint"><?= htmlspecialchars(date('M j', strtotime($thisMonthStart)) . ' – ' . date('M j, Y', strtotime($thisMonthEnd))) ?> · show in list</span>
        </a>
        <a class="exp-hero-card exp-hero-card-accent exp-card-last"
           href="?page=expenses&amp;from_date=<?= htmlspecialchars($lastMonthStart) ?>&amp;to_date=<?= htmlspecialchars($lastMonthEnd) ?>">
            <span class="exp-metric-label">Last month</span>
            <span class="exp-metric-value"><?= APP_CURRENCY ?> <?= number_format($expenseLastMonth ?? 0, DECIMAL_PLACES) ?></span>
            <span class="exp-metric-hint"><?= htmlspecialchars(date('M j', strtotime($lastMonthStart)) . ' – ' . date('M j, Y', strtotime($lastMonthEnd))) ?> · show in list</span>
        </a>
    </div>
</div>


<!-- Add Expense modal -->
<div class="exp-modal-backdrop<?= isset($_GET['new']) ? ' is-open' : '' ?>" id="addExpenseForm"
     role="dialog" aria-modal="true" aria-labelledby="expModalTitle" aria-hidden="<?= isset($_GET['new']) ? 'false' : 'true' ?>">
    <div class="exp-modal" role="document">
        <div class="exp-form-header">
            <span id="expModalTitle"><i class="bi bi-plus-circle-fill"></i> Add Expenses</span>
            <button type="button" class="exp-form-close" id="expFormCloseBtn" aria-label="Close">×</button>
        </div>
        <form method="POST" action="?page=expenses&action=store">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="expense_form_nonce" value="<?= htmlspecialchars($expenseFormNonce ?? '') ?>">
            <div class="exp-form-body">

            <!-- Date & Account (shared for all rows) -->
            <div class="exp-meta-row">
                <div class="exp-meta-field exp-meta-date">
                    <div class="exp-meta-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></div>
                    <div class="exp-meta-content">
                        <label for="expFormDate">Date &amp; Time <span class="exp-meta-req">*</span></label>
                        <div class="exp-meta-datetime">
                            <input type="date" id="expFormDate" name="date" value="<?= date('Y-m-d') ?>" required>
                            <input type="time" id="expFormTime" name="time" value="<?= date('H:i') ?>" required>
                        </div>
                    </div>
                </div>
                <?php
                    $expAccountChoices = [];
                    foreach ($accounts as $accChoice) {
                        if (!isset($accChoice['is_active']) || (int) $accChoice['is_active'] === 1) {
                            $expAccountChoices[] = $accChoice;
                        }
                    }
                    if (empty($expAccountChoices)) {
                        $expAccountChoices = $accounts;
                    }
                    $expDefaultAccountId = 0;
                    $expDefaultAccountName = '';
                    foreach ($expAccountChoices as $accDefault) {
                        $accName = trim((string) ($accDefault['name'] ?? ''));
                        if (!empty($accDefault['is_default'])) {
                            $expDefaultAccountId = (int) ($accDefault['id'] ?? 0);
                            $expDefaultAccountName = $accName;
                            break;
                        }
                        if ($expDefaultAccountId <= 0 && strcasecmp($accName, 'Main Cash') === 0) {
                            $expDefaultAccountId = (int) ($accDefault['id'] ?? 0);
                            $expDefaultAccountName = $accName;
                        }
                    }
                    if ($expDefaultAccountId <= 0 && !empty($expAccountChoices[0]['id'])) {
                        $expDefaultAccountId = (int) $expAccountChoices[0]['id'];
                        $expDefaultAccountName = trim((string) ($expAccountChoices[0]['name'] ?? ''));
                    } elseif ($expDefaultAccountName === '') {
                        foreach ($expAccountChoices as $accNamed) {
                            if ((int) ($accNamed['id'] ?? 0) === $expDefaultAccountId) {
                                $expDefaultAccountName = trim((string) ($accNamed['name'] ?? ''));
                                break;
                            }
                        }
                    }
                    $expAccountsJson = [];
                    foreach ($expAccountChoices as $accJson) {
                        $expAccountsJson[] = [
                            'id'   => (int) ($accJson['id'] ?? 0),
                            'name' => (string) ($accJson['name'] ?? ''),
                        ];
                    }
                ?>
                <div class="exp-acc-picker" id="expAccPicker">
                    <div class="exp-meta-field exp-meta-account">
                        <div class="exp-meta-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></div>
                        <div class="exp-meta-content">
                            <label for="expAccSearch">Paid From <span class="exp-meta-req">*</span></label>
                            <?php if (empty($expAccountChoices)): ?>
                            <div class="exp-acc-empty">No accounts available. Add one in Accounts first.</div>
                            <input type="hidden" id="expFormAccount" name="account_id" value="">
                            <?php else: ?>
                            <input type="text" id="expAccSearch" class="exp-acc-search"
                                   value="<?= htmlspecialchars($expDefaultAccountName) ?>"
                                   placeholder="Type to find account…"
                                   autocomplete="off" autocorrect="off" spellcheck="false"
                                   role="combobox" aria-expanded="false" aria-controls="expAccDrop" aria-autocomplete="list">
                            <input type="hidden" id="expFormAccount" name="account_id" value="<?= $expDefaultAccountId ?>">
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($expAccountChoices)): ?>
                        <i class="bi bi-chevron-down exp-acc-caret" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="exp-acc-drop" id="expAccDrop" role="listbox" aria-label="Accounts"></div>
                </div>
            </div>

            <!-- Expense Rows -->
            <div class="exp-lines-card">
                <div class="exp-lines-table-wrap">
                    <table class="exp-rows-table" id="expRowsTable">
                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="col-amt">Amount</th>
                                <th class="col-act"></th>
                            </tr>
                        </thead>
                        <tbody id="expRowsBody">
                        </tbody>
                    </table>
                </div>
                <div class="exp-rows-total">
                    <span class="exp-rows-total-label">Grand Total</span>
                    <span class="exp-rows-total-value" id="expGrandTotal">0.000</span>
                </div>
            </div>
            </div>

            <div class="exp-form-actions">
                <button type="button" class="btn-exp-add-row" id="expAddRowBtn">
                    <i class="bi bi-plus-lg"></i> Add Row
                </button>
                <div class="exp-form-actions-right">
                    <button type="button" class="btn-exp-cancel" id="expCancelBtn">Cancel</button>
                    <button type="submit" class="btn-exp-save"><i class="bi bi-check-lg"></i> Save All Expenses</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:18px;">
    <input type="hidden" name="page" value="expenses">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Expense no, description..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:150px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-tag me-1"></i>Category
            </label>
            <select name="category_id"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filters['category_id']==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) $filters['from_date']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=expenses"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0" id="expensesTable">
            <thead>
                <tr>
                    <th class="th-blue">Expense No</th>
                    <th class="th-blue">Date</th>
                    <th class="th-blue">Category</th>
                    <th class="th-blue">Account</th>
                    <th class="th-blue">Description</th>
                    <th class="th-blue">Amount</th>
                    <th class="th-blue">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No expenses found
                    </td>
                </tr>
                <?php else: ?>
                <?php
                    $todayYmd = date('Y-m-d');
                    foreach ($expenses as $e):
                        $expDay     = substr(trim((string) ($e['date'] ?? '')), 0, 10);
                        $expTimeSrc = (string) ($e['created_at'] ?? $e['date'] ?? '');
                        $isToday    = $expDay === $todayYmd;
                ?>
                <tr<?= $isToday ? ' class="exp-row-today"' : '' ?>>
                    <td><span class="exp-no"><?= htmlspecialchars($e['expense_no']) ?></span></td>
                    <td>
                        <span class="exp-date"><?= date('m/d/Y', strtotime((string) ($e['date'] ?? 'now'))) ?>, <?= date('h:i A', strtotime($expTimeSrc ?: 'now')) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($e['category_name'])): ?>
                        <span class="exp-cat-badge"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($e['category_name']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="exp-account"><?= htmlspecialchars($e['account_name'] ?? '—') ?></td>
                    <td><span class="exp-desc" title="<?= htmlspecialchars($e['description'] ?? '') ?>"><?= htmlspecialchars($e['description'] ?? '—') ?></span></td>
                    <td><span class="exp-amount"><?= APP_CURRENCY ?> <?= number_format($e['amount'], DECIMAL_PLACES) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                        <a href="?page=expenses&action=edit&id=<?= $e['id'] ?>" class="btn-del pin-protect" title="Edit" style="color:#d97706;background:rgba(245,158,11,0.1);border-color:rgba(245,158,11,0.2);">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if (Auth::can('expenses','delete')): ?>
                        <form method="POST" action="?page=expenses&action=delete" style="display:inline;"
                              onsubmit="return confirm('Delete this expense?')">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                            <button type="submit" class="btn-del pin-protect" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
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
var expRowCount = 0;
var categories = <?= json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
var expAccounts = <?= json_encode($expAccountsJson ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

function escHtml(s) {
    return String(s).replace(/[&<>"']/g, function(ch) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
    });
}
var defaultGeneralCategoryId = '';
for (var i = 0; i < categories.length; i++) {
    var name = String(categories[i].name || '').trim().toLowerCase();
    if (name === 'general') {
        defaultGeneralCategoryId = String(categories[i].id);
        break;
    }
}

function focusLastAmountField() {
    var amountInputs = document.querySelectorAll('#expRowsBody input[name$="[amount]"]');
    var target = amountInputs.length ? amountInputs[amountInputs.length - 1] : null;
    if (target) {
        target.focus();
        target.select();
    }
}

function defaultDescriptionForCategory(categoryId) {
    if (defaultGeneralCategoryId !== '' && String(categoryId) === defaultGeneralCategoryId) {
        return 'General';
    }
    return '';
}

function addExpRow() {
    expRowCount++;
    var catOptions = '<option value="">—</option>';
    var defaultCatId = defaultGeneralCategoryId;
    categories.forEach(function(c) {
        var selected = (defaultGeneralCategoryId !== '' && String(c.id) === defaultGeneralCategoryId) ? ' selected' : '';
        catOptions += '<option value="' + escHtml(c.id) + '"' + selected + '>' + escHtml(c.name) + '</option>';
    });
    var defaultDesc = defaultDescriptionForCategory(defaultCatId);

    var tr = document.createElement('tr');
    tr.id = 'expRow_' + expRowCount;
    tr.className = 'exp-data-row';
    tr.innerHTML =
        '<td class="exp-row-num-cell"><span class="exp-row-num">' + expRowCount + '</span></td>' +
        '<td class="exp-row-cell"><select class="exp-row-select" name="rows[' + expRowCount + '][category_id]">' + catOptions + '</select></td>' +
        '<td class="exp-row-cell exp-row-cell-desc"><input type="text" class="exp-row-input" name="rows[' + expRowCount + '][description]" value="' + escHtml(defaultDesc) + '" placeholder="What was this for?"></td>' +
        '<td class="exp-row-cell exp-row-cell-amt"><input type="number" class="exp-row-amount" name="rows[' + expRowCount + '][amount]" step="0.001" min="0.001" placeholder="0.000" required></td>' +
        '<td class="exp-row-cell exp-row-cell-act"><button type="button" class="exp-row-remove" data-row="' + expRowCount + '" title="Remove row" aria-label="Remove row"><i class="bi bi-x-lg"></i></button></td>';

    document.getElementById('expRowsBody').appendChild(tr);
    // Keep entry flow fast: jump directly to amount
    requestAnimationFrame(function() {
        focusLastAmountField();
    });
}

function removeExpRow(n) {
    var row = document.getElementById('expRow_' + n);
    if (row) { row.remove(); calcExpTotal(); }
}

function calcExpTotal() {
    var total = 0;
    document.querySelectorAll('#expRowsBody input[type="number"]').forEach(function(el) {
        total += parseFloat(el.value) || 0;
    });
    document.getElementById('expGrandTotal').textContent = total.toFixed(3);
}

function stampExpFormNow() {
    var dateInput = document.getElementById('expFormDate');
    var timeInput = document.getElementById('expFormTime');
    if (!dateInput || !timeInput) return;
    var now = new Date();
    var y = now.getFullYear();
    var m = String(now.getMonth() + 1).padStart(2, '0');
    var d = String(now.getDate()).padStart(2, '0');
    var hh = String(now.getHours()).padStart(2, '0');
    var mm = String(now.getMinutes()).padStart(2, '0');
    if (!dateInput.value) dateInput.value = y + '-' + m + '-' + d;
    timeInput.value = hh + ':' + mm;
}

function openExpForm() {
    var panel = document.getElementById('addExpenseForm');
    if (!panel) return;
    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    document.body.classList.add('exp-modal-open');
    stampExpFormNow();
    if (document.querySelectorAll('#expRowsBody tr').length === 0) {
        addExpRow();
    } else {
        requestAnimationFrame(function() {
            focusLastAmountField();
        });
    }
}

function closeExpForm() {
    var panel = document.getElementById('addExpenseForm');
    if (!panel) return;
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('exp-modal-open');
    var picker = document.getElementById('expAccPicker');
    if (picker) picker.classList.remove('is-open');
}

document.getElementById('expBtnOpenAddForm')?.addEventListener('click', function() {
    openExpForm();
});
document.getElementById('expFormCloseBtn')?.addEventListener('click', closeExpForm);
document.getElementById('expCancelBtn')?.addEventListener('click', closeExpForm);
document.getElementById('expAddRowBtn')?.addEventListener('click', function() {
    addExpRow();
});
document.addEventListener('keydown', function(e) {
    var panel = document.getElementById('addExpenseForm');
    if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
        var picker = document.getElementById('expAccPicker');
        if (picker && picker.classList.contains('is-open')) return;
        closeExpForm();
    }
});

(function initExpAccountPicker() {
    var picker = document.getElementById('expAccPicker');
    var search = document.getElementById('expAccSearch');
    var hidden = document.getElementById('expFormAccount');
    var drop = document.getElementById('expAccDrop');
    if (!picker || !search || !hidden || !drop || !expAccounts.length) return;

    var visible = [];
    var activeIdx = -1;

    function selectedName() {
        var id = String(hidden.value);
        for (var i = 0; i < expAccounts.length; i++) {
            if (String(expAccounts[i].id) === id) return String(expAccounts[i].name || '');
        }
        return '';
    }

    function closeDrop() {
        picker.classList.remove('is-open');
        search.setAttribute('aria-expanded', 'false');
        activeIdx = -1;
        search.value = selectedName();
    }

    function highlight(idx) {
        var opts = drop.querySelectorAll('.exp-acc-opt');
        opts.forEach(function(el) { el.classList.remove('is-active'); });
        if (idx < 0 || idx >= opts.length) return;
        opts[idx].classList.add('is-active');
        opts[idx].scrollIntoView({ block: 'nearest' });
        activeIdx = idx;
    }

    function render(query) {
        var q = String(query || '').toLowerCase().trim();
        visible = [];
        var html = '';
        var selectedId = String(hidden.value);
        for (var i = 0; i < expAccounts.length; i++) {
            var name = String(expAccounts[i].name || '');
            if (q && name.toLowerCase().indexOf(q) === -1) continue;
            visible.push(expAccounts[i]);
            var on = String(expAccounts[i].id) === selectedId;
            html += '<button type="button" class="exp-acc-opt' + (on ? ' is-selected' : '') + '"'
                + ' role="option" data-id="' + escHtml(expAccounts[i].id) + '"'
                + ' data-name="' + escHtml(name) + '">' + escHtml(name) + '</button>';
        }
        drop.innerHTML = html || '<div class="exp-acc-empty">No matching account</div>';
        activeIdx = -1;
    }

    function openDrop(query) {
        render(query);
        picker.classList.add('is-open');
        search.setAttribute('aria-expanded', 'true');
    }

    function pick(id, name) {
        hidden.value = id;
        search.value = name;
        closeDrop();
    }

    search.addEventListener('focus', function() {
        search.select();
        openDrop('');
    });
    search.addEventListener('input', function() {
        openDrop(search.value);
    });
    search.addEventListener('keydown', function(e) {
        var open = picker.classList.contains('is-open');
        if (e.key === 'Escape') {
            e.stopPropagation();
            closeDrop();
            search.blur();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!open) openDrop(search.value);
            highlight(Math.min(activeIdx + 1, Math.max(visible.length - 1, 0)));
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!open) return;
            highlight(Math.max(activeIdx - 1, 0));
            return;
        }
        if (e.key === 'Enter') {
            if (!open) return;
            e.preventDefault();
            var choice = visible[activeIdx >= 0 ? activeIdx : 0];
            if (choice) pick(choice.id, choice.name);
        }
    });
    drop.addEventListener('mousedown', function(e) {
        var opt = e.target.closest('.exp-acc-opt');
        if (!opt) return;
        e.preventDefault();
        pick(opt.getAttribute('data-id'), opt.getAttribute('data-name'));
    });
    document.addEventListener('mousedown', function(e) {
        if (!picker.contains(e.target)) closeDrop();
    });
})();

// Delegated handlers for dynamically added rows (no inline handlers)
var expRowsBody = document.getElementById('expRowsBody');
expRowsBody?.addEventListener('input', function(e) {
    if (e.target.matches('input[type="number"]')) calcExpTotal();
});
expRowsBody?.addEventListener('change', function(e) {
    if (!e.target.matches('select[name$="[category_id]"]')) return;
    var row = e.target.closest('tr');
    if (!row) return;
    var descInput = row.querySelector('input[name$="[description]"]');
    if (!descInput) return;
    var suggested = defaultDescriptionForCategory(e.target.value);
    if (suggested && !descInput.value.trim()) {
        descInput.value = suggested;
    }
});
expRowsBody?.addEventListener('click', function(e) {
    var btn = e.target.closest('.exp-row-remove');
    if (btn) removeExpRow(btn.getAttribute('data-row'));
});

(function initExpModalDeepLink() {
    var panel = document.getElementById('addExpenseForm');
    if (!panel) return;
    var shouldOpen = panel.classList.contains('is-open');
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('new') === '1') shouldOpen = true;
        if (params.get('new') === '1' && window.history && window.history.replaceState) {
            params.delete('new');
            var clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
            window.history.replaceState({}, '', clean);
        }
    } catch (err) { /* ignore */ }
    if (shouldOpen) openExpForm();
})();
</script>
