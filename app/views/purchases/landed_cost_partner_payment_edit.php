<?php
$partnerNavActive = 'history';
$linesTotal = (float) array_sum(array_column($linkedLines, 'amount'));
$lockAmount = count($linkedLines) > 0;
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="?page=landedcost&action=partnerHistory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Edit partner payment</h1>
</div>

<?php include __DIR__ . '/landed_cost_partner_nav.php'; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card" style="border-radius:12px;">
            <div class="card-header fw-bold"><i class="bi bi-pencil-square me-1"></i> Payment details</div>
            <div class="card-body">
                <div class="mb-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="small text-muted">Payment no</div>
                    <div class="fw-bold"><?= htmlspecialchars($payment['payment_no'] ?? '') ?></div>
                    <div class="small text-muted mt-2">Partner</div>
                    <div class="fw-semibold"><?= htmlspecialchars($payment['party_name'] ?? '') ?></div>
                </div>

                <form method="POST" action="?page=landedcost&action=partnerPaymentUpdate&id=<?= (int) $payment['id'] ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $payment['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (<?= APP_CURRENCY ?>)</label>
                        <input type="number" name="amount" class="form-control" step="0.001" min="0.001" required
                               value="<?= number_format((float) $payment['amount'], DECIMAL_PLACES, '.', '') ?>"
                               <?= $lockAmount ? 'readonly style="background:#f1f5f9;"' : '' ?>>
                        <?php if ($lockAmount): ?>
                        <small class="text-muted">Locked to linked lines total (<?= number_format($linesTotal, DECIMAL_PLACES) ?>). Undo payment to change lines.</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account</label>
                        <select name="account_id" class="form-select" required>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= (int) $acc['id'] ?>" <?= (int) ($payment['account_id'] ?? 0) === (int) $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(BaseController::formatAccountLabel($acc)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars((string) ($payment['date'] ?? '')) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($payment['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
                        <a href="?page=landedcost&action=partnerHistory" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card" style="border-radius:12px;">
            <div class="card-header fw-bold"><i class="bi bi-list-check me-1"></i> Linked profit lines (<?= count($linkedLines) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($linkedLines)): ?>
                <p class="text-muted text-center py-4 mb-0">No linked accrual lines found for this payment.</p>
                <?php else: ?>
                <table class="table mb-0" style="font-size:0.83rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th>Shipment</th>
                            <th>PO / Item</th>
                            <th class="text-end">KWD</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($linkedLines as $ln): ?>
                    <tr>
                        <td><?= htmlspecialchars($ln['shipment_no'] ?? '') ?></td>
                        <td>
                            <span class="text-muted"><?= htmlspecialchars($ln['po_no'] ?? '') ?></span><br>
                            <?= htmlspecialchars($ln['item_name'] ?? '') ?>
                            <small class="text-muted d-block"><?= number_format((float) ($ln['partner_profit_per_pc'] ?? 0), DECIMAL_PLACES) ?> / pc × <?= (int) ($ln['quantity'] ?? 0) ?></small>
                        </td>
                        <td class="text-end fw-semibold"><?= number_format((float) ($ln['amount'] ?? 0), DECIMAL_PLACES) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#ecfdf5;">
                            <td colspan="2" class="fw-bold">Total</td>
                            <td class="text-end fw-bold"><?= number_format($linesTotal, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
