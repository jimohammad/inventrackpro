<style>
/* ══ EXPENSE PAGE ══ */
.exp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
.exp-header-left h1{font-size:1.4rem;font-weight:800;color:var(--text-main);margin:0;}
.exp-header-left p{color:var(--text-muted);font-size:0.82rem;margin:2px 0 0;}
.btn-add-exp{display:inline-flex;align-items:center;gap:8px;padding:9px 20px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border:none;color:#fff;border-radius:10px;font-size:0.875rem;font-weight:700;cursor:pointer;box-shadow:0 3px 12px rgba(139,92,246,0.35);transition:all 0.15s;text-decoration:none;}
.btn-add-exp:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(139,92,246,0.45);color:#fff;}

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
.exp-save-row{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);}
.btn-exp-save{padding:9px 24px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border:none;color:#fff;border-radius:9px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.15s;}
.btn-exp-save:hover{transform:translateY(-1px);}
.btn-exp-cancel{padding:9px 18px;background:var(--bg-main);border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:9px;font-weight:500;font-size:0.88rem;cursor:pointer;}

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

<!-- Header -->
<div class="exp-header">
    <div class="exp-header-left">
        <h1><i class="bi bi-receipt me-2" style="color:#8b5cf6;"></i>Expenses</h1>
        <p>Track and manage all business expenses</p>
    </div>
    <button class="btn-add-exp" onclick="toggleExpForm()">
        <i class="bi bi-plus-lg"></i> Add Expense
    </button>
</div>


<!-- Add Expense Form Panel -->
<div class="exp-form-panel" id="addExpenseForm" style="display:<?= isset($_GET['new']) ? 'block' : 'none' ?>;">
    <div class="exp-form-header">
        <span><i class="bi bi-plus-circle-fill"></i> Add Expenses</span>
        <button class="exp-form-close" onclick="toggleExpForm()">×</button>
    </div>
    <div class="exp-form-body">
        <form method="POST" action="?page=expenses&action=store">
            <?= Auth::csrfField() ?>

            <!-- Date & Account (shared for all rows) -->
            <div style="display:flex;gap:14px;margin-bottom:16px;flex-wrap:wrap;">
                <div class="exp-field" style="width:160px;">
                    <label>Date <span style="color:#8b5cf6;">*</span></label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="exp-field" style="width:200px;">
                    <label>Account <span style="color:#8b5cf6;">*</span></label>
                    <select name="account_id" required>
                        <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Expense Rows -->
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;" id="expRowsTable">
                <thead>
                    <tr style="background:rgba(139,92,246,0.06);">
                        <th style="padding:8px 10px;text-align:left;font-size:0.72rem;font-weight:700;color:var(--text-muted);width:40px;">#</th>
                        <th style="padding:8px 10px;text-align:left;font-size:0.72rem;font-weight:700;color:var(--text-muted);">CATEGORY</th>
                        <th style="padding:8px 10px;text-align:left;font-size:0.72rem;font-weight:700;color:var(--text-muted);">DESCRIPTION</th>
                        <th style="padding:8px 10px;text-align:left;font-size:0.72rem;font-weight:700;color:var(--text-muted);width:140px;">AMOUNT</th>
                        <th style="padding:8px 10px;width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="expRowsBody">
                </tbody>
                <tfoot>
                    <tr style="border-top:2px solid var(--border-color);">
                        <td colspan="3" style="padding:10px;text-align:right;font-weight:800;color:#8b5cf6;">Grand Total</td>
                        <td style="padding:10px;text-align:right;font-weight:800;font-size:1rem;color:#8b5cf6;" id="expGrandTotal">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:1px solid var(--border-color);">
                <button type="button" onclick="addExpRow()" style="background:rgba(139,92,246,0.1);color:#8b5cf6;border:1px dashed #8b5cf6;border-radius:8px;padding:6px 16px;font-size:0.82rem;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg me-1"></i> Add Row
                </button>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn-exp-cancel" onclick="toggleExpForm()">Cancel</button>
                    <button type="submit" class="btn-exp-save"><i class="bi bi-check-lg me-1"></i> Save All Expenses</button>
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
            <input type="date" name="from_date" value="<?= $filters['from_date'] ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= $filters['to_date'] ?: date('Y-m-d') ?>"
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
                    <td><span class="exp-no"><?= $e['expense_no'] ?></span></td>
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
var categories = <?= json_encode($categories) ?>;

function addExpRow() {
    expRowCount++;
    var catOptions = '<option value="">—</option>';
    categories.forEach(function(c) {
        catOptions += '<option value="' + c.id + '">' + c.name + '</option>';
    });

    var tr = document.createElement('tr');
    tr.id = 'expRow_' + expRowCount;
    tr.style.borderBottom = '1px solid var(--border-color)';
    tr.innerHTML =
        '<td style="padding:8px 10px;text-align:center;color:var(--text-muted);">' + expRowCount + '</td>' +
        '<td style="padding:8px 6px;"><select name="rows[' + expRowCount + '][category_id]" style="width:100%;padding:6px 8px;border:1.5px solid var(--border-color);border-radius:7px;font-size:0.82rem;background:var(--bg-main);color:var(--text-main);">' + catOptions + '</select></td>' +
        '<td style="padding:8px 6px;"><input type="text" name="rows[' + expRowCount + '][description]" placeholder="Description..." style="width:100%;padding:6px 8px;border:1.5px solid var(--border-color);border-radius:7px;font-size:0.82rem;background:var(--bg-main);color:var(--text-main);"></td>' +
        '<td style="padding:8px 6px;"><input type="number" name="rows[' + expRowCount + '][amount]" step="0.001" min="0.001" placeholder="0.000" required oninput="calcExpTotal()" style="width:100%;padding:6px 8px;border:1.5px solid var(--border-color);border-radius:7px;font-size:0.85rem;font-weight:700;text-align:right;background:var(--bg-main);color:var(--text-main);"></td>' +
        '<td style="padding:8px 4px;text-align:center;"><button type="button" onclick="removeExpRow(' + expRowCount + ')" style="background:none;border:none;color:#fca5a8;cursor:pointer;font-size:1.1rem;" onmouseover="this.style.color=\'#dc2626\'" onmouseout="this.style.color=\'#fca5a8\'">×</button></td>';

    document.getElementById('expRowsBody').appendChild(tr);
    // Focus the description field of the new row
    tr.querySelector('input[type="text"]').focus();
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
        }
        setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    }
}

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
