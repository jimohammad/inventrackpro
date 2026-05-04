<!-- Party Ledger View -->
<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <a href="?page=parties" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="page-title mb-0"><?= htmlspecialchars($party['name']) ?></h1>
            <span class="badge" style="background:rgba(99,102,241,0.15);color:var(--primary);"><?= ucfirst($party['type']) ?></span>
        </div>
        <?php if (!empty($party['party_code'])): ?>
        <div class="mt-1 d-flex align-items-center gap-2">
            <span style="font-size:0.72rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Account No</span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:800;color:#4338ca;background:#eff6ff;padding:2px 12px;border-radius:6px;border:1.5px solid #c7d2fe;letter-spacing:2px;">
                <?= htmlspecialchars($party['party_code']) ?>
            </span>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($party['party_code']) ?>');this.innerHTML='<i class=\'bi bi-check-lg\'></i>';setTimeout(()=>this.innerHTML='<i class=\'bi bi-copy\'></i>',1500)"
                style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:2px 6px;font-size:0.85rem;" title="Copy account number">
                <i class="bi bi-copy"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php if (Auth::can('settings','edit')): ?>
    <a href="?page=parties&action=edit&id=<?= $party['id'] ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><p class="stat-label mb-1">Phone</p><p class="fw-semibold"><?= htmlspecialchars($party['phone'] ?: '—') ?></p></div></div>
    <div class="col-md-3"><div class="stat-card"><p class="stat-label mb-1">City</p><p><?= htmlspecialchars($party['city'] ?? '—') ?></p></div></div>
    <div class="col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Opening Balance</p>
            <p class="fw-bold" style="color:#64748b;"><?= APP_CURRENCY ?> <?= number_format($party['opening_balance'] ?? 0, DECIMAL_PLACES) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Active sales</p>
            <p class="fw-semibold" style="color:#4338ca;"><?= (int)($linkedSalesCount ?? 0) ?> <span style="font-size:0.75rem;font-weight:600;color:#94a3b8;">(excl. cancelled)</span></p>
            <?php if (!empty($cancelledSalesCount)): ?>
            <p class="mb-0 mt-1" style="font-size:0.72rem;color:#94a3b8;">Cancelled: <strong style="color:#64748b;"><?= (int)$cancelledSalesCount ?></strong></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Net Balance</p>
            <?php $netBal = (float)($party['net_balance'] ?? 0); ?>
            <?php if ($netBal > 0.001): ?>
            <p class="fw-bold" style="color:#ef4444;"><?= APP_CURRENCY ?> <?= number_format($netBal, DECIMAL_PLACES) ?></p>
            <small style="color:#ef4444;">They owe you</small>
            <?php elseif ($netBal < -0.001): ?>
            <p class="fw-bold" style="color:#6366f1;">-<?= APP_CURRENCY ?> <?= number_format(abs($netBal), DECIMAL_PLACES) ?></p>
            <small style="color:#6366f1;">You owe them</small>
            <?php else: ?>
            <p class="fw-bold" style="color:#10b981;">✓ Clear</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($ledgerMismatch)): ?>
<div class="alert alert-warning border-0 mb-3" style="border-radius:12px;">
    <strong>Data check:</strong> This account has <?= (int)$linkedSalesCount ?> non-cancelled sale invoice(s) in the database, but the ledger table below is empty.
    Try refreshing the page. If it persists, note party ID <code><?= (int)$party['id'] ?></code> for support — the unified ledger query may need inspection on the server.
</div>
<?php elseif (!empty($ledgerReturnWrongParty)): ?>
<div class="alert alert-danger border-0 mb-3" style="border-radius:12px;">
    <strong>Likely wrong customer on a return:</strong> This ledger has payments/returns/purchases but <strong>no sale invoices</strong> tied to account <?= htmlspecialchars($party['party_code'] ?? '') ?> (ID <?= (int)$party['id'] ?>).
    A <strong>sale return</strong> may have been saved while a different duplicate customer was selected; the real invoices sit on another party with the same name.
    Open the return (e.g. from Returns), confirm the original invoice, then run <code>database/fix_return_party_mismatch.sql</code> on the server (after backup) to copy <code>party_id</code> from each sale into its linked return.
</div>
<?php elseif (!empty($cancelledSalesCount)): ?>
<div class="alert alert-info border-0 mb-3" style="border-radius:12px;">
    <strong>Cancelled (voided) invoices:</strong> This customer has <strong><?= (int)$cancelledSalesCount ?></strong> sale(s) marked <code>cancelled</code> in the database.
    They are <strong>excluded</strong> from this ledger and from the main sales list on purpose (same rules as <code>Party::getLedger</code> / active totals).
    <?php if (!empty($cancelledSalesList)): ?>
    <ul class="mb-0 mt-2 small">
        <?php foreach ($cancelledSalesList as $cs): ?>
        <li>
            <?php if (Auth::can('sales', 'view') && !empty($cs['id'])): ?>
            <a href="?page=sales&action=detail&id=<?= (int)$cs['id'] ?>" style="font-weight:600;"><?= htmlspecialchars($cs['invoice_no']) ?></a>
            <?php else: ?>
            <code><?= htmlspecialchars($cs['invoice_no']) ?></code>
            <?php endif; ?>
            — <?= htmlspecialchars($cs['date']) ?> — <?= APP_CURRENCY ?> <?= number_format((float)$cs['grand_total'], DECIMAL_PLACES) ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="small mb-0 mt-2 text-muted">If you did not use Cancel on these invoices, open each invoice from Sales (search by invoice number); the detail page shows <strong>who/when</strong> from <code>activity_log</code> when the app recorded a cancel. If that section is empty, status may have been changed outside the app (e.g. phpMyAdmin).</p>
    <p class="small mb-0 mt-2"><strong>Find them in Sales:</strong> as admin, use the sidebar <strong>Voided invoices</strong> (or <a href="?page=sales&view=voided" class="fw-bold">this link</a>) — the list includes <strong>all warehouses</strong>. Or tick <strong>Include voided</strong> on the main sales filter (shows voided from any warehouse plus active sales for your current warehouse). Then open the invoice and use <strong>Reinstate voided invoice</strong> if appropriate.</p>
</div>
<?php elseif ((int)($linkedSalesCount ?? 0) === 0 && empty($ledger)): ?>
<div class="alert alert-light border mb-3" style="border-radius:12px;color:#64748b;">
    No sales, purchases, or payments are linked to this party record yet. If you already posted invoices under this name, you may have a <strong>duplicate customer</strong> (same name, different account number) — open the invoice from Sales and use “View customer” to reach the ledger that has those transactions.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Transaction Ledger</div>
    <div class="card-body p-0">
        <table class="table mb-0" id="ledgerTable">
            <thead>
                <tr>
                    <th>Ref No</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ledger)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No transactions found</td></tr>
                <?php else: ?>
                <?php
                    $running = (float)($party['opening_balance'] ?? 0);
                    $typeColors = [
                        'sale'     => '#6366f1',
                        'purchase' => '#f59e0b',
                        'payment'  => '#10b981',
                        'return'   => '#dc2626',
                        'expense'  => '#8b5cf6',
                    ];
                    $typeLabels = [
                        'sale'     => 'Sale',
                        'purchase' => 'Purchase',
                        'payment'  => 'Payment',
                        'return'   => 'Return',
                        'expense'  => 'Expense',
                    ];
                ?>
                <?php if (abs($running) > 0.001): ?>
                <tr style="background:#f8fafc;">
                    <td colspan="3" style="font-weight:600;color:#64748b;">Opening Balance</td>
                    <?php if ($running > 0): ?>
                    <td class="text-end" style="font-weight:700;"><?= APP_CURRENCY ?> <?= number_format($running, DECIMAL_PLACES) ?></td>
                    <td class="text-end">—</td>
                    <?php else: ?>
                    <td class="text-end">—</td>
                    <td class="text-end" style="font-weight:700;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <?php endif; ?>
                    <td class="text-end fw-semibold"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
                <?php foreach ($ledger as $l):
                    $debit  = (float)$l['debit'];
                    $credit = (float)$l['credit'];
                    $running += $debit - $credit;
                    $tColor = $typeColors[$l['type']] ?? '#94a3b8';
                    $tLabel = $typeLabels[$l['type']] ?? ucfirst(str_replace('_',' ',$l['type']));
                ?>
                <tr>
                    <td style="font-weight:600;color:var(--primary);"><?= $l['ref_no'] ?></td>
                    <td>
                        <span class="badge" style="background:<?= $tColor ?>20;color:<?= $tColor ?>;border-radius:5px;">
                            <?= $tLabel ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($l['date'])) ?></td>
                    <td class="text-end"><?= $debit > 0 ? APP_CURRENCY . ' ' . number_format($debit, DECIMAL_PLACES) : '—' ?></td>
                    <td class="text-end" style="color:var(--success);"><?= $credit > 0 ? APP_CURRENCY . ' ' . number_format($credit, DECIMAL_PLACES) : '—' ?></td>
                    <td class="text-end fw-semibold"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td><span class="badge badge-<?= $l['status'] ?>" style="border-radius:5px;"><?= ucfirst($l['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f0f4ff;font-weight:700;">
                    <td colspan="5" style="text-align:right;color:#4338ca;">Closing Balance</td>
                    <td class="text-end" style="color:#4338ca;"><?= $running < 0 ? '-' : '' ?><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#ledgerTable').DataTable({ pageLength: 50, order:[], language: { search: '', searchPlaceholder: 'Search...' } }); });</script>
