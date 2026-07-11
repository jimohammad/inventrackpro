<style>
/* ══ EXPENSE PAGE ══ */
.exp-page-top{margin-bottom:22px;}
.exp-page-title h1{font-size:1.4rem;font-weight:800;color:var(--text-main);margin:0;}
.exp-page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.exp-add-btn{
    display:inline-flex;align-items:center;gap:6px;cursor:pointer;
    padding:7px 14px;border-radius:9px;font-size:0.82rem;font-weight:700;
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
.exp-metric-add{
    border:none;cursor:pointer;width:100%;
    background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;border:1px solid transparent;
    box-shadow:0 4px 16px rgba(139,92,246,0.35);align-items:center;text-align:center;
}
.exp-metric-add .exp-metric-label{color:rgba(255,255,255,0.85);}
.exp-metric-add .exp-metric-value{color:#fff;font-size:1.05rem;display:inline-flex;align-items:center;gap:8px;}
.exp-metric-add:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(139,92,246,0.45);color:#fff;}
.exp-metric-add:focus{outline:2px solid #a78bfa;outline-offset:2px;}
.exp-hero-card-accent::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.exp-card-this::before{background:linear-gradient(90deg,#6366f1,#818cf8);}
.exp-card-last::before{background:linear-gradient(90deg,#f59e0b,#fbbf24);}

/* Stats */
.exp-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;}
.exp-stat{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;padding:18px 20px;position:relative;overflow:hidden;transition:transform 0.15s;}
.exp-stat:hover{transform:translateY(-2px);}
.exp-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.exp-stat.total::before{background:linear-gradient(90deg,#8b5cf6,#a78bfa);}
.exp-stat.cat::before{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.exp-stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px;}
.exp-stat.total .exp-stat-icon{background:rgba(139,92,246,0.12);color:#8b5cf6;}
.exp-stat.cat .exp-stat-icon{background:rgba(245,158,11,0.12);color:#f59e0b;}
.exp-stat-value{font-size:1.3rem;font-weight:800;color:var(--text-main);line-height:1;}
.exp-stat-label{font-size:0.75rem;color:var(--text-muted);margin-top:4px;font-weight:500;}
.exp-stat-sub{font-size:0.72rem;color:var(--text-muted);margin-top:6px;}

/* Add Form Panel */
.exp-form-panel{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;margin-bottom:24px;overflow:hidden;}
.exp-form-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(135deg,rgba(139,92,246,0.08),rgba(251,113,133,0.05));border-bottom:1px solid var(--border-color);}
.exp-form-header span{font-weight:700;font-size:0.9rem;color:var(--text-main);display:flex;align-items:center;gap:8px;}
.exp-form-header span i{color:#8b5cf6;}
.exp-form-close{background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer;line-height:1;padding:0;transition:color 0.15s;}
.exp-form-close:hover{color:#8b5cf6;}
.exp-form-body{padding:20px;}
.exp-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;}
.exp-field label{display:block;font-size:0.77rem;font-weight:600;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.4px;}
.exp-field input,.exp-field select{width:100%;padding:9px 12px;border:2px solid var(--border-color);border-radius:9px;font-size:0.85rem;background:var(--bg-main);color:var(--text-main);outline:none;transition:border-color 0.15s;}
.exp-field input:focus,.exp-field select:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,0.1);}

/* Meta fields: date + account */
.exp-meta-row{display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.exp-meta-field{
    display:flex;align-items:stretch;border-radius:12px;border:1.5px solid;
    overflow:hidden;transition:box-shadow 0.15s,border-color 0.15s,transform 0.15s;
}
.exp-meta-field:hover{transform:translateY(-1px);}
.exp-meta-field:focus-within{box-shadow:0 6px 20px rgba(15,23,42,0.08);}
.exp-meta-date{
    flex:0 1 220px;min-width:190px;
    background:linear-gradient(135deg,#fafaff,#f5f3ff);border-color:#ddd6fe;
}
.exp-meta-date:focus-within{border-color:#8b5cf6;box-shadow:0 6px 20px rgba(139,92,246,0.14);}
.exp-meta-account{
    flex:1 1 280px;min-width:240px;
    background:linear-gradient(135deg,#f0fdf9,#ecfdf5);border-color:#a7f3d0;
}
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
.exp-meta-content input,.exp-meta-content select{
    width:100%;border:none;background:transparent;padding:0;
    font-size:0.92rem;font-weight:700;color:var(--text-main);outline:none;
    cursor:pointer;line-height:1.3;
}
.exp-meta-content select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M4.5 6L8 9.5 11.5 6z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 2px center;padding-right:18px;
}
.exp-meta-content input[type="date"]::-webkit-calendar-picker-indicator{cursor:pointer;opacity:0.55;}
.exp-meta-content input[type="date"]::-webkit-calendar-picker-indicator:hover{opacity:0.85;}

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
    margin-top:16px;flex-wrap:wrap;
}
.exp-form-actions-right{display:flex;gap:10px;flex-wrap:wrap;}
.btn-exp-add-row{
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 18px;background:#fff;color:#7c3aed;
    border:1.5px dashed #c4b5fd;border-radius:10px;
    font-size:0.82rem;font-weight:600;cursor:pointer;transition:all 0.15s;
}
.btn-exp-add-row:hover{background:rgba(139,92,246,0.08);border-color:#8b5cf6;border-style:solid;}

.exp-save-row{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);}
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

/* Filters */
.exp-filters{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;padding:14px 18px;margin-bottom:18px;}
.exp-filters form{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.exp-filters input,.exp-filters select{padding:7px 12px;border:1.5px solid var(--border-color);border-radius:8px;font-size:0.82rem;background:var(--bg-main);color:var(--text-main);outline:none;height:36px;}
.exp-filters input:focus,.exp-filters select:focus{border-color:#8b5cf6;}
.btn-filter{padding:7px 16px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border:none;color:#fff;border-radius:8px;font-size:0.82rem;font-weight:600;cursor:pointer;height:36px;}
.btn-clear{padding:7px 12px;background:var(--bg-main);border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:8px;font-size:0.82rem;cursor:pointer;height:36px;text-decoration:none;display:inline-flex;align-items:center;}
.btn-clear:hover{border-color:#8b5cf6;color:#8b5cf6;}

/* Table */
.exp-table-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;}
.exp-table-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border-color);}
.exp-table-head span{font-weight:700;font-size:0.875rem;color:var(--text-main);display:flex;align-items:center;gap:8px;}
.exp-table-head span i{color:#8b5cf6;}
.exp-count{font-size:0.75rem;background:rgba(139,92,246,0.1);color:#8b5cf6;padding:3px 10px;border-radius:20px;font-weight:700;}
table.exp-tbl{width:100%;border-collapse:collapse;font-size:0.82rem;}
table.exp-tbl th{padding:8px 20px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);background:transparent;border-bottom:2px solid var(--border-color);white-space:nowrap;}
table.exp-tbl td{padding:7px 20px;border-bottom:1px solid var(--border-color);vertical-align:middle;color:var(--text-main);}
table.exp-tbl tbody tr:last-child td{border-bottom:none;}
table.exp-tbl tbody tr{transition:background 0.1s;}
table.exp-tbl tbody tr:hover{background:rgba(139,92,246,0.03);}
.exp-no{font-weight:700;color:#8b5cf6;font-family:monospace;font-size:0.82rem;}
.exp-date{font-size:0.82rem;color:var(--text-muted);}
.exp-cat-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);}
.exp-desc{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.exp-amount{font-weight:800;color:#8b5cf6;font-size:0.9rem;text-align:left;white-space:nowrap;}
.btn-del{width:26px;height:26px;border-radius:6px;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.2);color:#8b5cf6;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.15s;text-decoration:none;font-size:0.78rem;}
.btn-del:hover{background:#8b5cf6;color:#fff;border-color:#8b5cf6;}
.exp-empty{text-align:center;padding:60px 20px;color:var(--text-muted);}
.exp-empty i{font-size:2.5rem;opacity:0.3;display:block;margin-bottom:10px;}
</style>

<!-- Title + month cards + add (same row height) -->
<div class="exp-page-top">
    <div class="exp-page-head">
        <div class="exp-page-title">
            <h1><i class="bi bi-receipt me-2" style="color:#8b5cf6;"></i>Expenses</h1>
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


<!-- Add Expense Form Panel -->
<div class="exp-form-panel" id="addExpenseForm" style="display:<?= isset($_GET['new']) ? 'block' : 'none' ?>;">
    <div class="exp-form-header">
        <span><i class="bi bi-plus-circle-fill"></i> Add Expenses</span>
        <button type="button" class="exp-form-close" id="expFormCloseBtn">×</button>
    </div>
    <div class="exp-form-body">
        <form method="POST" action="?page=expenses&action=store">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="expense_form_nonce" value="<?= htmlspecialchars($expenseFormNonce ?? '') ?>">

            <!-- Date & Account (shared for all rows) -->
            <div class="exp-meta-row">
                <div class="exp-meta-field exp-meta-date">
                    <div class="exp-meta-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></div>
                    <div class="exp-meta-content">
                        <label for="expFormDate">Expense Date <span class="exp-meta-req">*</span></label>
                        <input type="date" id="expFormDate" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="exp-meta-field exp-meta-account">
                    <div class="exp-meta-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></div>
                    <div class="exp-meta-content">
                        <label for="expFormAccount">Paid From <span class="exp-meta-req">*</span></label>
                        <select id="expFormAccount" name="account_id" required>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?: date('Y-m-d'))) ?>"
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
<div class="exp-table-card">
    <div class="exp-table-head">
        <span><i class="bi bi-list-ul"></i> Expense Records</span>
        <span class="exp-count"><?= count($expenses) ?> records</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="exp-tbl" id="expensesTable">
            <thead>
                <tr>
                    <th>Expense No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="width:80px;text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                <tr><td colspan="7">
                    <div class="exp-empty">
                        <i class="bi bi-receipt"></i>
                        No expenses found
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><span class="exp-no"><?= htmlspecialchars($e['expense_no']) ?></span></td>
                    <td><span class="exp-date"><?= date('d M Y', strtotime($e['date'])) ?></span></td>
                    <td>
                        <?php if (!empty($e['category_name'])): ?>
                        <span class="exp-cat-badge"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($e['category_name']) ?></span>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted);font-size:0.82rem;"><?= htmlspecialchars($e['account_name'] ?? '—') ?></td>
                    <td><span class="exp-desc" title="<?= htmlspecialchars($e['description'] ?? '') ?>"><?= htmlspecialchars($e['description'] ?? '—') ?></span></td>
                    <td style="text-align:left;"><span class="exp-amount"><?= APP_CURRENCY ?> <?= number_format($e['amount'], DECIMAL_PLACES) ?></span></td>
                    <td style="text-align:center;">
                        <div class="d-flex gap-1 justify-content-center">
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

<script>
var expRowCount = 0;
var categories = <?= json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

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

function toggleExpForm() {
    const panel = document.getElementById('addExpenseForm');
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        if (document.querySelectorAll('#expRowsBody tr').length === 0) {
            addExpRow(); // Start with one row
        } else {
            requestAnimationFrame(function() {
                focusLastAmountField();
            });
        }
        setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    }
}

document.getElementById('expBtnOpenAddForm')?.addEventListener('click', function() {
    toggleExpForm();
});
document.getElementById('expFormCloseBtn')?.addEventListener('click', toggleExpForm);
document.getElementById('expCancelBtn')?.addEventListener('click', toggleExpForm);
document.getElementById('expAddRowBtn')?.addEventListener('click', function() {
    addExpRow();
});

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

<?php if (isset($_GET['new'])): ?>
document.addEventListener('DOMContentLoaded', () => {
    addExpRow();
    setTimeout(() => document.getElementById('addExpenseForm')?.scrollIntoView({ behavior: 'smooth' }), 100);
});
<?php endif; ?>

$(document).ready(() => {
    $('#expensesTable').DataTable({
        pageLength: 25,
        order: [[1, 'desc']],
        columnDefs: [{ orderable: false, targets: 6 }]
    });
});
</script>
