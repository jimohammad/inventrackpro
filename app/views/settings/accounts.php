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
</style>

<!-- Header -->
<div class="acc-header">
    <div>
        <h1><i class="bi bi-wallet2 me-2" style="color:#6366f1;"></i>Accounts</h1>
        <p>Manage cash, bank and wallet balances</p>
    </div>
    <div class="acc-actions">
        <button class="btn-acc" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 3px 10px rgba(245,158,11,0.3);" onclick="togglePanel('adjustPanel')">
            <i class="bi bi-sliders"></i> Adjust Balance
        </button>
        <button class="btn-acc btn-transfer" onclick="togglePanel('transferPanel')">
            <i class="bi bi-arrow-left-right"></i> Transfer Funds
        </button>
        <button class="btn-acc btn-new-acc" onclick="togglePanel('newAccPanel')">
            <i class="bi bi-plus-lg"></i> New Account
        </button>
    </div>
</div>

<!-- Accounts List -->
<div class="acc-list">
    <?php if (empty($accounts)): ?>
    <div style="text-align:center;padding:40px;color:var(--text-muted);">No accounts yet. Create one above.</div>
    <?php endif; ?>
    <?php
    $totalBal = 0;
    $typeColors = ['cash' => '#10b981', 'bank' => '#3b82f6', 'mobile_wallet' => '#8b5cf6', 'other' => '#f59e0b'];
    $typeIcons  = ['cash' => 'bi-cash-stack', 'bank' => 'bi-bank', 'mobile_wallet' => 'bi-phone', 'other' => 'bi-wallet2'];
    foreach ($accounts as $acc):
        $accType = $acc['normalized_type'] ?? $acc['type'];
        $color = $typeColors[$accType] ?? '#6366f1';
        $icon  = $typeIcons[$accType] ?? 'bi-wallet2';
        $isPos = $acc['current_balance'] >= 0;
        $totalBal += (float)$acc['current_balance'];
    ?>
    <?php $isActive = ($selectedAccountId === (int)$acc['id']); ?>
    <a href="?page=accounts<?= $isActive ? '' : '&account_id=' . $acc['id'] ?>" style="text-decoration:none;color:inherit;">
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
            <?= APP_CURRENCY ?> <?= number_format($acc['current_balance'], DECIMAL_PLACES) ?>
        </div>
    </div>
    </a>
    <?php endforeach; ?>
    <?php if (!empty($accounts)): ?>
    <div class="acc-total-bar" style="grid-column:1/-1;">
        <span>Total Balance</span>
        <span class="acc-total-val"><?= APP_CURRENCY ?> <?= number_format($totalBal, DECIMAL_PLACES) ?></span>
    </div>
    <?php endif; ?>
</div>

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
                  onsubmit="return confirm('Recalculate this account balance from ledger?\\n\\nThis sets current_balance = opening_balance + payments - expenses + transfers.\\n\\nNote: Manual Adjust Balance changes are not stored separately and may be overridden by recalculation.');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="account_id" value="<?= (int)$selectedAccount['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary pin-protect" style="font-size:.78rem;">
                    <i class="bi bi-arrow-repeat me-1"></i> Recalculate
                </button>
            </form>
            <?php endif; ?>
            <a href="?page=accounts" style="font-size:.78rem;color:var(--text-muted);text-decoration:none;" title="Close">✕ Close</a>
        </div>
    </div>
    <?php if (empty($accountTxns)): ?>
    <div class="hist-empty"><i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>No transactions found for this account.</div>
    <?php else: ?>
    <table class="hist-tbl">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Party / Description</th>
                <th style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($accountTxns as $txn):
            $isCredit = (float)$txn['amount'] > 0;
            $typeLabel = match($txn['txn_type']) {
                'payment'    => ['label'=>'Payment',    'color'=>'#10b981', 'bg'=>'rgba(16,185,129,.1)',  'icon'=>'bi-cash'],
                'expense'    => ['label'=>'Expense',    'color'=>'#ef4444', 'bg'=>'rgba(239,68,68,.1)',   'icon'=>'bi-receipt'],
                'transfer'   => ['label'=>'Transfer',   'color'=>'#6366f1', 'bg'=>'rgba(99,102,241,.1)',  'icon'=>'bi-arrow-left-right'],
                'po_payment' => ['label'=>'PO Payment', 'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.1)', 'icon'=>'bi-box-arrow-in-down'],
                default      => ['label'=>$txn['txn_type'], 'color'=>'#6b7280', 'bg'=>'rgba(107,114,128,.1)', 'icon'=>'bi-circle'],
            };
        ?>
        <tr>
            <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;"><?= date('d M Y', strtotime($txn['date'])) ?></td>
            <td><span class="trf-no"><?= htmlspecialchars($txn['ref_no'] ?: '—') ?></span><?php if (!empty($txn['invoice_ref'])): ?><br><span style="font-size:.7rem;color:var(--text-muted);"><?= htmlspecialchars($txn['invoice_ref']) ?></span><?php endif; ?></td>
            <td><span style="background:<?= $typeLabel['bg'] ?>;color:<?= $typeLabel['color'] ?>;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;"><i class="bi <?= $typeLabel['icon'] ?> me-1"></i><?= $typeLabel['label'] ?></span></td>
            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($txn['party'] . ($txn['note'] ? ' — ' . $txn['note'] : '')) ?>">
                <?= htmlspecialchars($txn['party']) ?>
                <?php if ($txn['note']): ?><br><span style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($txn['note']) ?></span><?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:800;font-size:.9rem;color:<?= (float)$txn['amount'] >= 0 ? '#10b981' : '#ef4444' ?>;white-space:nowrap;">
                <?= (float)$txn['amount'] >= 0 ? '+' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs((float)$txn['amount']), DECIMAL_PLACES) ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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
            </div>
            <div class="acc-field" style="margin-top:14px;">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. Cash count adjustment, short change, excess found...">
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

<!-- Transfer Funds Panel -->
<div class="acc-panel" id="transferPanel" style="display:<?= isset($_GET['transfer']) ? 'block' : 'none' ?>;">
    <div class="acc-panel-header green">
        <div class="acc-panel-title"><i class="bi bi-arrow-left-right" style="color:#10b981;"></i> Transfer Funds Between Accounts</div>
        <button class="panel-close" onclick="togglePanel('transferPanel')">×</button>
    </div>
    <div class="acc-panel-body">
        <form method="POST" action="?page=accounts&action=transfer">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="account_transfer_nonce" value="<?= htmlspecialchars($accountTransferNonce ?? '') ?>">
            <div class="acc-form-row" style="grid-template-columns:1fr 60px 1fr 160px 200px;">

                <div class="acc-field acc-field-green">
                    <label>From Account <span style="color:#ef4444;">*</span></label>
                    <select name="from_account_id" id="fromAcc" onchange="updateBalance()" required>
                        <option value="">Select source account...</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" data-balance="<?= $a['current_balance'] ?>">
                            <?= htmlspecialchars($a['name']) ?> — <?= APP_CURRENCY ?> <?= number_format($a['current_balance'], DECIMAL_PLACES) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="fromBalance" style="margin-top:5px;font-size:0.77rem;color:#10b981;font-weight:600;min-height:16px;"></div>
                </div>

                <div class="transfer-arrow"><i class="bi bi-arrow-right-circle-fill" style="color:#10b981;"></i></div>

                <div class="acc-field acc-field-green">
                    <label>To Account <span style="color:#ef4444;">*</span></label>
                    <select name="to_account_id" required>
                        <option value="">Select destination...</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="acc-field acc-field-green">
                    <label>Amount <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="amount" step="0.001" min="0.001" placeholder="0.000" required>
                </div>

                <div class="acc-field acc-field-green">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="acc-form-row" style="margin-top:0;">
                <div class="acc-field acc-field-green" style="grid-column:1/-1;">
                    <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="text" name="notes" placeholder="Reason for transfer...">
                </div>
            </div>
            <div class="acc-save-row">
                <button type="button" class="btn-panel-cancel" onclick="togglePanel('transferPanel')">Cancel</button>
                <button type="submit" class="btn-panel-save green"><i class="bi bi-arrow-left-right me-1"></i> Execute Transfer</button>
            </div>
        </form>
    </div>
</div>

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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr><td colspan="8"><div class="hist-empty"><i class="bi bi-arrow-left-right" style="font-size:2rem;opacity:0.2;display:block;margin-bottom:8px;"></i>No transfers yet</div></td></tr>
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
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePanel(id) {
    const panel = document.getElementById(id);
    const allPanels = ['newAccPanel', 'transferPanel'];
    allPanels.forEach(p => {
        if (p !== id) document.getElementById(p).style.display = 'none';
    });
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
}

function updateBalance() {
    const sel = document.getElementById('fromAcc');
    const opt = sel.options[sel.selectedIndex];
    const bal = parseFloat(opt.dataset.balance || 0);
    const el  = document.getElementById('fromBalance');
    if (sel.value) {
        el.textContent = 'Available: <?= APP_CURRENCY ?> ' + bal.toFixed(<?= DECIMAL_PLACES ?>);
        el.style.color = bal > 0 ? '#10b981' : '#ef4444';
    } else {
        el.textContent = '';
    }
}

$(document).ready(() => {
    if (document.getElementById('transferTable').querySelectorAll('tbody tr').length > 5) {
        $('#transferTable').DataTable({ pageLength: 25, order: [[0, 'desc']], columnDefs: [{ orderable: false, targets: [3] }] });
    }
});
</script>
