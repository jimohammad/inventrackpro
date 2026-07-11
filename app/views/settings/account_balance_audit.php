<div class="mb-4">
    <a href="?page=accounts&account_id=<?= (int) $accountId ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to <?= htmlspecialchars($acc['name'] ?? 'Account') ?>
    </a>
</div>

<div class="card mb-3">
    <div class="card-header fw-bold">Balance audit — <?= htmlspecialchars($acc['name'] ?? '') ?> (ID <?= (int) $accountId ?>)</div>
    <div class="card-body">
        <p class="text-muted mb-3" style="font-size:.88rem;">
            Stored balance: <strong><?= APP_CURRENCY ?> <?= number_format((float)($acc['current_balance'] ?? 0), DECIMAL_PLACES) ?></strong>
            &nbsp;|&nbsp; Recalc from ledger: <strong><?= APP_CURRENCY ?> <?= number_format((float)($report['ledger']['balance'] ?? 0), DECIMAL_PLACES) ?></strong>
        </p>
        <table class="table table-sm mb-0">
            <tbody>
                <tr><td>Opening balance</td><td class="text-end"><?= number_format((float)$report['ledger']['opening'], 3) ?></td></tr>
                <tr><td>Payments IN</td><td class="text-end text-success">+<?= number_format((float)$report['ledger']['payments_in'], 3) ?></td></tr>
                <tr><td>Payments OUT (active)</td><td class="text-end text-danger">-<?= number_format((float)$report['ledger']['payments_out'], 3) ?></td></tr>
                <tr><td>Payments net</td><td class="text-end"><?= number_format((float)$report['ledger']['payments_net'], 3) ?></td></tr>
                <tr><td>Expenses</td><td class="text-end text-danger">-<?= number_format((float)$report['ledger']['expenses'], 3) ?></td></tr>
                <tr><td>Transfers net</td><td class="text-end"><?= number_format((float)$report['ledger']['transfers_net'], 3) ?></td></tr>
                <tr><td>Manual adjustments</td><td class="text-end"><?= number_format((float)$report['ledger']['adjustments_net'], 3) ?></td></tr>
                <tr><td>PO unlinked (extra subtract)</td><td class="text-end text-danger">-<?= number_format((float)$report['ledger']['po_unlinked_out'], 3) ?></td></tr>
                <tr class="fw-bold"><td>= Calculated balance</td><td class="text-end"><?= number_format((float)$report['ledger']['balance'], 3) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($report['duplicates'])): ?>
<div class="card mb-3 border-danger">
    <div class="card-header text-danger fw-bold">Duplicate active payments (same PO paid twice)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>PO</th><th>Status</th><th>PO payment</th><th>Purchase payment</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($report['duplicates'] as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['po_no']) ?></td>
                    <td><?= htmlspecialchars($d['status']) ?></td>
                    <td><?= htmlspecialchars($d['po_payment']) ?></td>
                    <td><?= htmlspecialchars($d['purchase_payment']) ?></td>
                    <td class="text-end"><?= number_format((float)$d['amount'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer small text-muted">Click <strong>Recalculate</strong> on the account — duplicates are auto-cancelled before recalc.</div>
</div>
<?php endif; ?>

<?php if (!empty($report['blank_ref_dupes'])): ?>
<div class="card mb-3 border-danger">
    <div class="card-header text-danger fw-bold">Blank ref_type duplicate advances</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Payment</th><th>Date</th><th>Party</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($report['blank_ref_dupes'] as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['payment_no']) ?></td>
                    <td><?= htmlspecialchars($d['date']) ?></td>
                    <td><?= htmlspecialchars($d['party_name'] ?? '—') ?></td>
                    <td class="text-end"><?= number_format((float)$d['amount'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer small text-muted">Recalculate voids these when a matching typed outbound payment exists for the same party and amount.</div>
</div>
<?php endif; ?>

<?php if (!empty($report['unlinked_pos'])): ?>
<div class="card mb-3 border-warning">
    <div class="card-header text-warning fw-bold">PO paid amounts counted again (no payment row)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>PO</th><th>Status</th><th class="text-end">paid_kwd</th></tr></thead>
            <tbody>
                <?php foreach ($report['unlinked_pos'] as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['po_no']) ?></td>
                    <td><?= htmlspecialchars($u['status']) ?></td>
                    <td class="text-end"><?= number_format((float)$u['paid_kwd'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header fw-bold">Largest OUT payments on this account</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Payment</th><th>Date</th><th>Party</th><th>Ref</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($report['top_out'] as $p): ?>
                <tr class="<?= ($p['status'] ?? '') !== 'active' ? 'text-muted' : '' ?>">
                    <td><?= htmlspecialchars($p['payment_no']) ?></td>
                    <td><?= htmlspecialchars($p['date']) ?></td>
                    <td><?= htmlspecialchars($p['party_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['ref_type']) ?></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td class="text-end"><?= number_format((float)$p['amount'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <form method="POST" action="?page=accounts&action=recalcBalance">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="account_id" value="<?= (int) $accountId ?>">
        <button type="submit" class="btn btn-primary pin-protect">Recalculate now (cleans duplicates first)</button>
    </form>
</div>
