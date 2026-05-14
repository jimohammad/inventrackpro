<!-- Purchase View -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="?page=purchases" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i></a>
        <span class="page-title"><?= $purchase['invoice_no'] ?></span>
        <span class="badge ms-2 badge-<?= $purchase['status'] ?> px-2" style="border-radius:6px;"><?= ucfirst($purchase['status']) ?></span>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
        <a href="?page=purchases&action=print&id=<?= $purchase['id'] ?>&autoprint=1" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        <?php if (Auth::can('purchases','delete') && ($purchase['status'] ?? '') !== 'cancelled'): ?>
        <form method="POST" action="?page=purchases&action=cancel" style="display:inline;"
              onsubmit="return confirm('Cancel this purchase? Stock and linked payments will be reversed.');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$purchase['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger pin-protect">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php
    $db2 = Database::getInstance();
    $imeiItemCount = (int)($db2->fetchOne(
        "SELECT COUNT(*) as c FROM purchase_items pi JOIN items i ON i.id=pi.item_id WHERE pi.purchase_id=? AND i.has_imei=1",
        [$purchase['id']]
    )['c'] ?? 0);
    ?>
    <?php if ($imeiItemCount > 0): ?>
    <a href="?page=purchases&action=imeiScan&id=<?= $purchase['id'] ?>"
       class="btn btn-sm"
       style="background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-weight:600;border:none;display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;">
        <i class="bi bi-upc-scan"></i> Scan IMEIs
    </a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Purchase Details</span>
                <small class="text-muted"><?= date('d M Y', strtotime($purchase['date'])) ?></small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><p class="text-muted mb-1" style="font-size:0.8rem;">SUPPLIER</p><p class="fw-semibold mb-0"><?= htmlspecialchars($purchase['party_name']) ?></p><?php if ($purchase['party_phone']): ?><small class="text-muted"><?= htmlspecialchars($purchase['party_phone']) ?></small><?php endif; ?></div>
                    <div class="col-md-6"><p class="text-muted mb-1" style="font-size:0.8rem;">WAREHOUSE</p><p><?= htmlspecialchars($purchase['warehouse_name'] ?? '—') ?></p></div>
                    <?php if (!empty($purchase['supplier_invoice_no'])): ?>
                    <div class="col-md-6 mt-3">
                        <p class="text-muted mb-1" style="font-size:0.8rem;">SUPPLIER INVOICE #</p>
                        <p class="fw-semibold mb-0" style="font-family:monospace;letter-spacing:0.5px;"><?= htmlspecialchars($purchase['supplier_invoice_no']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Items</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Item</th><th class="text-center">Qty</th><th class="text-end">Cost</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($purchase['items'] as $i => $item): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></span>
                            </td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end"><?= APP_CURRENCY ?> <?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                            <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($item['total'], DECIMAL_PLACES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end"><?= APP_CURRENCY ?> <?= number_format($purchase['subtotal'], DECIMAL_PLACES) ?></td></tr>
                        <?php if ($purchase['discount'] > 0): ?><tr><td colspan="4" class="text-end text-danger">Discount</td><td class="text-end">- <?= APP_CURRENCY ?> <?= number_format($purchase['discount'], DECIMAL_PLACES) ?></td></tr><?php endif; ?>
                        <tr><td colspan="4" class="text-end fw-bold" style="font-size:1rem;">Grand Total</td><td class="text-end fw-bold" style="font-size:1rem;"><?= APP_CURRENCY ?> <?= number_format($purchase['grand_total'], DECIMAL_PLACES) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Payment Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total</span><span class="fw-semibold"><?= APP_CURRENCY ?> <?= number_format($purchase['grand_total'], DECIMAL_PLACES) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paid</span><span style="color:var(--success);font-weight:600;"><?= APP_CURRENCY ?> <?= number_format($purchase['paid_amount'], DECIMAL_PLACES) ?></span></div>
                <hr style="border-color:var(--border-color);">
                <div class="d-flex justify-content-between"><span class="fw-bold">Balance</span><span style="font-weight:700;color:<?= $purchase['balance'] > 0 ? 'var(--warning)':'var(--success)' ?>;"><?= APP_CURRENCY ?> <?= number_format($purchase['balance'], DECIMAL_PLACES) ?></span></div>
                <p class="small text-muted mb-0 mt-2" style="line-height:1.45;">
                    <strong>Invoice balance</strong> = amount still due on <em>this</em> bill (grand total minus payments linked to this purchase). Recording a supplier payment reduces this when allocated to this invoice. Your overall position with the supplier is <strong>Net balance</strong> on
                    <?php if (Auth::can('suppliers', 'view') || Auth::can('customers', 'view')): ?>
                    <a href="?page=parties&amp;action=detail&amp;id=<?= (int)($purchase['party_id'] ?? 0) ?>">their party ledger</a>
                    <?php else: ?>
                    their party ledger
                    <?php endif; ?>
                    (sales, purchases, returns, and all payments). If that ledger does not list this purchase, check for a <strong>duplicate supplier</strong> with the same name — match <strong>Account No</strong> here to the party you open in Party Master.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Payments</div>
            <div class="card-body p-0">
                <?php if (empty($purchase['payments'])): ?>
                <p class="text-muted text-center py-3 mb-0">No payments recorded</p>
                <?php else: ?>
                <?php foreach ($purchase['payments'] as $pay): ?>
                <div class="d-flex justify-content-between px-3 py-2" style="border-bottom:1px solid var(--border-color);">
                    <div><p class="mb-0" style="font-size:0.82rem;"><?= $pay['payment_no'] ?></p><small class="text-muted"><?= date('d M Y', strtotime($pay['date'])) ?></small></div>
                    <span style="color:var(--success);font-weight:600;"><?= APP_CURRENCY ?> <?= number_format($pay['amount'], DECIMAL_PLACES) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
