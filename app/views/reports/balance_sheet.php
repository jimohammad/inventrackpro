<style>
.bs-wrap { max-width: 900px; margin: 0 auto; }
.bs-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.bs-date-form { display:flex; align-items:center; gap:10px; }
.bs-date-form input { border:2px solid var(--border-color); border-radius:8px; padding:6px 12px; font-size:0.85rem; background:var(--bg-main); color:var(--text-main); font-weight:600; }
.bs-date-form button { padding:6px 18px; border:none; border-radius:8px; background:var(--primary); color:#fff; font-weight:700; font-size:0.85rem; cursor:pointer; }

.bs-section { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; margin-bottom:20px; overflow:hidden; }
.bs-section-head { padding:14px 20px; font-weight:800; font-size:0.9rem; display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color); }
.bs-section-head.assets { background:linear-gradient(135deg,rgba(16,185,129,0.08),rgba(5,150,105,0.04)); color:#059669; }
.bs-section-head.liabilities { background:linear-gradient(135deg,rgba(239,68,68,0.08),rgba(220,38,38,0.04)); color:#dc2626; }
.bs-section-head.equity { background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(79,70,229,0.04)); color:#6366f1; }

.bs-group { padding:12px 20px; border-bottom:1px solid var(--border-color); }
.bs-group:last-child { border-bottom:none; }
.bs-group-title { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.bs-row { display:flex; justify-content:space-between; align-items:center; padding:5px 0; font-size:0.85rem; }
.bs-row .name { color:var(--text-main); }
.bs-row .name small { color:var(--text-muted); margin-left:6px; }
.bs-row .amt { font-weight:700; font-family:'JetBrains Mono',monospace; font-size:0.85rem; }
.bs-row .amt.green { color:#059669; }
.bs-row .amt.red { color:#dc2626; }
.bs-row .amt.blue { color:#6366f1; }

.bs-subtotal { display:flex; justify-content:space-between; padding:10px 20px; font-weight:800; font-size:0.95rem; border-top:2px solid var(--border-color); background:rgba(0,0,0,0.02); }
[data-theme="dark"] .bs-subtotal { background:rgba(255,255,255,0.02); }

.bs-grand { background:var(--bg-card); border:2px solid var(--border-color); border-radius:14px; padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
.bs-grand .label { font-size:1rem; font-weight:800; color:var(--text-main); }
.bs-grand .value { font-size:1.4rem; font-weight:800; font-family:'JetBrains Mono',monospace; }
.bs-grand .value.pos { color:#059669; }
.bs-grand .value.neg { color:#dc2626; }

/* Summary cards */
.bs-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
.bs-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:16px 20px; position:relative; overflow:hidden; }
.bs-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.bs-card.green::before { background:#10b981; }
.bs-card.red::before { background:#ef4444; }
.bs-card.blue::before { background:#6366f1; }
.bs-card-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:4px; }
.bs-card-val { font-size:1.3rem; font-weight:800; font-family:'JetBrains Mono',monospace; }

@media (max-width:768px) { .bs-cards { grid-template-columns:1fr; } }
</style>

<div class="bs-wrap">
    <div class="bs-header">
        <div>
            <h1 class="page-title">Balance Sheet</h1>
            <p class="page-subtitle">Financial position as of <?= date('d M Y', strtotime($date)) ?></p>
        </div>
        <form class="bs-date-form" method="GET">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="balanceSheet">
            <label style="font-size:0.8rem;color:var(--text-muted);font-weight:600;">As of:</label>
            <input type="date" name="as_of" value="<?= htmlspecialchars((string) $date) ?>">
            <button type="submit"><i class="bi bi-funnel me-1"></i>Update</button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="bs-cards">
        <div class="bs-card green">
            <div class="bs-card-label">Total Assets</div>
            <div class="bs-card-val" style="color:#059669;"><?= APP_CURRENCY ?> <?= number_format($totalAssets, DECIMAL_PLACES) ?></div>
        </div>
        <div class="bs-card red">
            <div class="bs-card-label">Total Liabilities</div>
            <div class="bs-card-val" style="color:#dc2626;"><?= APP_CURRENCY ?> <?= number_format($totalLiabilities, DECIMAL_PLACES) ?></div>
        </div>
        <div class="bs-card blue">
            <div class="bs-card-label">Net Worth</div>
            <div class="bs-card-val" style="color:<?= $netWorth >= 0 ? '#6366f1' : '#dc2626' ?>;"><?= APP_CURRENCY ?> <?= number_format($netWorth, DECIMAL_PLACES) ?></div>
        </div>
    </div>

    <!-- ASSETS -->
    <div class="bs-section">
        <div class="bs-section-head assets">
            <i class="bi bi-arrow-up-circle-fill"></i> ASSETS
        </div>

        <!-- Cash & Bank -->
        <div class="bs-group">
            <div class="bs-group-title"><i class="bi bi-wallet2"></i> Cash & Bank Accounts</div>
            <?php if (empty($accounts)): ?>
            <div class="bs-row"><span class="name text-muted">No accounts</span></div>
            <?php else: ?>
            <?php foreach ($accounts as $a): ?>
            <div class="bs-row">
                <span class="name">
                    <?= htmlspecialchars($a['name']) ?>
                    <small><?= ucfirst(str_replace('_',' ',$a['type'])) ?></small>
                </span>
                <span class="amt green"><?= APP_CURRENCY ?> <?= number_format($a['current_balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Receivables -->
        <div class="bs-group">
            <div class="bs-group-title"><i class="bi bi-people"></i> Accounts Receivable (Customers Owe You)</div>
            <?php if (empty($receivables)): ?>
            <div class="bs-row"><span class="name text-muted">No outstanding receivables</span></div>
            <?php else: ?>
            <?php foreach ($receivables as $r): ?>
            <div class="bs-row">
                <span class="name">
                    <?= htmlspecialchars($r['name']) ?>
                    <small><?= $r['party_code'] ?></small>
                </span>
                <span class="amt green"><?= APP_CURRENCY ?> <?= number_format($r['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Supplier Advances (PO) -->
        <div class="bs-group">
            <div class="bs-group-title"><i class="bi bi-box-arrow-up-right"></i> Supplier Advances (Paid on PO)</div>
            <?php if (empty($poAdvances ?? [])): ?>
            <div class="bs-row"><span class="name text-muted">No PO advances</span></div>
            <?php else: ?>
            <?php foreach (($poAdvances ?? []) as $adv): ?>
            <div class="bs-row">
                <span class="name">
                    <?= htmlspecialchars($adv['name']) ?>
                    <small><?= htmlspecialchars($adv['party_code'] ?? '') ?></small>
                </span>
                <span class="amt green"><?= APP_CURRENCY ?> <?= number_format((float)$adv['amount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Stock -->
        <div class="bs-group">
            <div class="bs-group-title"><i class="bi bi-box-seam"></i> Inventory (at cost)</div>
            <div class="bs-row">
                <span class="name">Stock on Hand</span>
                <span class="amt green"><?= APP_CURRENCY ?> <?= number_format($stockVal, DECIMAL_PLACES) ?></span>
            </div>
        </div>

        <div class="bs-subtotal">
            <span style="color:#059669;">Total Assets</span>
            <span style="color:#059669;"><?= APP_CURRENCY ?> <?= number_format($totalAssets, DECIMAL_PLACES) ?></span>
        </div>
    </div>

    <!-- LIABILITIES -->
    <div class="bs-section">
        <div class="bs-section-head liabilities">
            <i class="bi bi-arrow-down-circle-fill"></i> LIABILITIES
        </div>

        <div class="bs-group">
            <div class="bs-group-title"><i class="bi bi-building"></i> Accounts Payable (You Owe Suppliers)</div>
            <?php if (empty($payables)): ?>
            <div class="bs-row"><span class="name text-muted">No outstanding payables</span></div>
            <?php else: ?>
            <?php foreach ($payables as $p): ?>
            <div class="bs-row">
                <span class="name">
                    <?= htmlspecialchars($p['name']) ?>
                    <small><?= $p['party_code'] ?></small>
                </span>
                <span class="amt red"><?= APP_CURRENCY ?> <?= number_format($p['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bs-subtotal">
            <span style="color:#dc2626;">Total Liabilities</span>
            <span style="color:#dc2626;"><?= APP_CURRENCY ?> <?= number_format($totalLiabilities, DECIMAL_PLACES) ?></span>
        </div>
    </div>

    <!-- NET WORTH -->
    <div class="bs-grand">
        <span class="label"><i class="bi bi-trophy-fill me-2" style="color:#6366f1;"></i>Net Worth (Assets − Liabilities)</span>
        <span class="value <?= $netWorth >= 0 ? 'pos' : 'neg' ?>"><?= APP_CURRENCY ?> <?= number_format($netWorth, DECIMAL_PLACES) ?></span>
    </div>
</div>
