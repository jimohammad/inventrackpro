<!-- Agent Statement -->
<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <a href="?page=parties" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title mb-0"><?= htmlspecialchars($party['name']) ?></h1>
        <small class="text-muted"><?= $party['phone'] ?> &nbsp;·&nbsp; Agent Statement</small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="?page=payments&action=create&party_id=<?= $party['id'] ?>" class="btn btn-sm btn-success">
            <i class="bi bi-cash me-1"></i> Record Payment
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <?php if (!empty($party['statement_token'])): ?>
        <button type="button" onclick="copyStatementLink('<?= $party['statement_token'] ?>', this)"
            class="btn btn-sm" style="background:rgba(99,102,241,0.12);color:#6366f1;border:1px solid rgba(99,102,241,0.3);">
            <i class="bi bi-link-45deg me-1"></i> Copy Field Link
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Total Dispatched</p>
            <p class="stat-value" style="font-size:1.1rem;"><?= APP_CURRENCY ?> <?= number_format($totalDispatched, DECIMAL_PLACES) ?></p>
            <p class="mb-0 text-muted" style="font-size:0.78rem;"><?= count($invoices) ?> invoice(s)</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Total Collected</p>
            <p class="stat-value" style="font-size:1.1rem;color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($totalPaid, DECIMAL_PLACES) ?></p>
            <p class="mb-0 text-muted" style="font-size:0.78rem;"><?= count($payments) ?> payment(s)</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <p class="stat-label mb-1">Outstanding Balance</p>
            <p class="stat-value" style="font-size:1.1rem;color:<?= $totalOutstanding > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                <?= APP_CURRENCY ?> <?= number_format($totalOutstanding, DECIMAL_PLACES) ?>
            </p>
            <p class="mb-0 text-muted" style="font-size:0.78rem;">
                <?= $totalOutstanding > 0 ? count($unpaidInvoices) . ' unpaid invoice(s)' : 'Account clear' ?>
            </p>
        </div>
    </div>
</div>

<!-- Credit Limit Bar -->
<?php $creditLimit = (float)($party['credit_limit'] ?? 0); ?>
<?php if ($creditLimit > 0): ?>
<?php $usedPct = min(100, round(($totalOutstanding / $creditLimit) * 100)); ?>
<?php $barColor = $usedPct >= 90 ? '#ef4444' : ($usedPct >= 70 ? '#f59e0b' : '#10b981'); ?>
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-weight:600;font-size:0.9rem;">Credit Limit Usage</span>
            <span style="font-size:0.85rem;color:var(--text-muted);">
                <?= APP_CURRENCY ?> <?= number_format($totalOutstanding, DECIMAL_PLACES) ?>
                used of
                <?= APP_CURRENCY ?> <?= number_format($creditLimit, DECIMAL_PLACES) ?>
                &nbsp;·&nbsp;
                <strong style="color:<?= $barColor ?>;"><?= $usedPct ?>%</strong>
            </span>
        </div>
        <div style="height:10px;background:var(--bg-secondary);border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:<?= $usedPct ?>%;background:<?= $barColor ?>;border-radius:99px;transition:width 0.4s;"></div>
        </div>
        <?php if ($usedPct >= 90): ?>
        <p class="mb-0 mt-2" style="font-size:0.78rem;color:#ef4444;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Credit limit almost reached. New invoices above <?= APP_CURRENCY ?> <?= number_format($creditLimit - $totalOutstanding, DECIMAL_PLACES) ?> will be blocked.
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Invoices + Payments -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2"></i>Dispatch Invoices</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:0.84rem;">
                    <thead style="background:var(--bg-secondary);">
                        <tr>
                            <th class="px-3 py-2">Invoice</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Branch</th>
                            <th class="px-3 py-2 text-end">Amount</th>
                            <th class="px-3 py-2 text-end">Paid</th>
                            <th class="px-3 py-2 text-end">Balance</th>
                            <th class="px-3 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No invoices found</td></tr>
                        <?php endif; ?>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="px-3 py-2">
                                <a href="?page=sales&action=detail&id=<?= $inv['id'] ?>" style="color:var(--primary);font-weight:600;">
                                    <?= $inv['invoice_no'] ?>
                                </a>
                            </td>
                            <td class="px-3 py-2 text-muted"><?= date('d M Y', strtotime($inv['date'])) ?></td>
                            <td class="px-3 py-2 text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($inv['warehouse_name'] ?? '-') ?></td>
                            <td class="px-3 py-2 text-end"><?= number_format($inv['grand_total'], DECIMAL_PLACES) ?></td>
                            <td class="px-3 py-2 text-end" style="color:var(--success);"><?= number_format($inv['paid_amount'], DECIMAL_PLACES) ?></td>
                            <td class="px-3 py-2 text-end fw-bold" style="color:<?= $inv['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= number_format($inv['balance'], DECIMAL_PLACES) ?>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge badge-<?= $inv['status'] ?>" style="border-radius:5px;font-size:0.72rem;">
                                    <?= ucfirst($inv['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($invoices)): ?>
                    <tfoot style="background:var(--bg-secondary);font-weight:600;">
                        <tr>
                            <td colspan="3" class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-end"><?= number_format($totalDispatched, DECIMAL_PLACES) ?></td>
                            <td class="px-3 py-2 text-end" style="color:var(--success);"><?= number_format($totalPaid, DECIMAL_PLACES) ?></td>
                            <td class="px-3 py-2 text-end" style="color:<?= $totalOutstanding > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= number_format($totalOutstanding, DECIMAL_PLACES) ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cash-stack me-2"></i>Payments Received</span>
                <a href="?page=payments&action=create&party_id=<?= $party['id'] ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-plus me-1"></i> Add
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:0.84rem;">
                    <thead style="background:var(--bg-secondary);">
                        <tr>
                            <th class="px-3 py-2">Ref</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Method</th>
                            <th class="px-3 py-2 text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No payments yet</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="px-3 py-2" style="color:var(--primary);font-weight:600;"><?= $pay['payment_no'] ?></td>
                            <td class="px-3 py-2 text-muted"><?= date('d M Y', strtotime($pay['date'])) ?></td>
                            <td class="px-3 py-2 text-muted" style="font-size:0.78rem;"><?= ucfirst(str_replace('_',' ',$pay['payment_method'])) ?></td>
                            <td class="px-3 py-2 text-end fw-bold" style="color:var(--success);">
                                <?= number_format($pay['amount'], DECIMAL_PLACES) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($payments)): ?>
                    <tfoot style="background:var(--bg-secondary);font-weight:600;">
                        <tr>
                            <td colspan="3" class="px-3 py-2">Total Collected</td>
                            <td class="px-3 py-2 text-end" style="color:var(--success);"><?= number_format($totalPaid, DECIMAL_PLACES) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function copyStatementLink(token, btn) {
    const url = window.location.origin + '/statement.php?token=' + token;
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Copied!';
        btn.style.background = 'rgba(16,185,129,0.15)';
        btn.style.color = '#10b981';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = '#6366f1'; }, 2500);
    });
}
</script>
