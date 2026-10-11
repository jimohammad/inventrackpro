<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Invoice IMEI Barcodes</h1>
            <p class="page-subtitle">Pick a customer, then one of their invoices</p>
        </div>
    </div>
    <?php if (!empty($sale) && !empty($groups)): ?>
    <a href="?page=reports&action=invoiceImeiBarcodePrint&sale_id=<?= (int) $sale['id'] ?>"
       target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer me-1"></i> Print
    </a>
    <?php endif; ?>
</div>

<div class="card mb-3" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="invoiceImeiBarcode">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Customer</label>
                <select name="party_id" class="form-select form-select-sm"
                        onchange="var s=this.form.querySelector('[name=sale_id]'); if(s) s.value=''; var n=this.form.querySelector('[name=invoice_no]'); if(n) n.value=''; this.form.submit();">
                    <option value="">-- Select customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($partyId ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $c['name']) ?><?= !empty($c['phone']) ? ' (' . htmlspecialchars((string) $c['phone']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Invoice</label>
                <select name="sale_id" class="form-select form-select-sm" <?= empty($partyId) ? 'disabled' : '' ?>
                        onchange="var n=this.form.querySelector('[name=invoice_no]'); if(n) n.value=''; if (this.value) this.form.submit();">
                    <option value=""><?= empty($partyId) ? '-- Select a customer first --' : '-- Select invoice --' ?></option>
                    <?php foreach ($invoices as $inv): ?>
                    <option value="<?= (int) $inv['id'] ?>" <?= !empty($sale) && (int) $sale['id'] === (int) $inv['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $inv['invoice_no']) ?>
                        <?php if (!empty($inv['date'])): ?> — <?= date('d M Y', strtotime((string) $inv['date'])) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Or invoice no.</label>
                <input type="text" name="invoice_no" class="form-control form-control-sm"
                       value="<?= htmlspecialchars((string) ($invoiceNo ?? '')) ?>"
                       placeholder="805">
            </div>
            <div class="col-12 col-md-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Show barcodes</button>
                    <a href="?page=reports&action=invoiceImeiBarcode" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($note)): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:0.85rem;">
    <?= htmlspecialchars((string) $note) ?>
</div>
<?php endif; ?>

<?php if (!empty($matches)): ?>
<div class="card mb-3" style="border:none;">
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:0.85rem;">
            <thead>
                <tr>
                    <th style="padding:8px 14px;">Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $m): ?>
                <tr>
                    <td style="padding:8px 14px;font-weight:700;"><?= htmlspecialchars((string) $m['invoice_no']) ?></td>
                    <td><?= !empty($m['date']) ? date('d M Y', strtotime((string) $m['date'])) : '' ?></td>
                    <td><?= htmlspecialchars((string) ($m['party_name'] ?? '')) ?></td>
                    <td class="text-end" style="padding-right:14px;">
                        <a class="btn btn-sm btn-outline-primary"
                           href="?page=reports&action=invoiceImeiBarcode&sale_id=<?= (int) $m['id'] ?>">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($sale)): ?>
<?php
    $cancelled = (($sale['status'] ?? '') === 'cancelled');
?>
<div class="card mb-3" style="border:none;">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size:0.88rem;">
            <div>
                <div>Customer: <strong><?= htmlspecialchars((string) ($sale['party_name'] ?? '')) ?></strong>
                    <?php if (!empty($sale['party_phone'])): ?>
                    <span class="text-muted ms-1"><?= htmlspecialchars((string) $sale['party_phone']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size:0.8rem;">
                    Invoice <?= htmlspecialchars((string) $sale['invoice_no']) ?>
                    · <?= !empty($sale['date']) ? date('d M Y', strtotime((string) $sale['date'])) : '' ?>
                    <?php if ($cancelled): ?>
                    · <span class="text-danger">Cancelled</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <a href="?page=sales&action=detail&id=<?= (int) $sale['id'] ?>" class="btn btn-sm btn-outline-secondary">Open invoice</a>
            </div>
        </div>
    </div>
</div>

<?php if (empty($groups)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-upc-scan" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 text-muted mb-0">This invoice has no scanned IMEIs yet.</p>
        <a href="?page=sales&action=detail&id=<?= (int) $sale['id'] ?>" class="btn btn-sm btn-outline-primary mt-3">Open invoice to scan</a>
    </div>
</div>
<?php else: ?>
<style>
.bc-item { margin-bottom: 16px; }
.bc-item-name {
    font-weight: 700;
    font-size: 0.88rem;
    padding: 8px 12px;
    background: rgba(8,145,178,0.08);
    border: 1px solid rgba(8,145,178,0.2);
    border-radius: 8px 8px 0 0;
}
.bc-item-sub { font-weight: 500; color: var(--text-muted); margin-left: 6px; }
.bc-item-count { float: right; font-weight: 600; font-size: 0.75rem; color: #0e7490; }
.bc-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 6px;
    padding: 8px;
    border: 1px solid var(--border-color);
    border-top: none;
    border-radius: 0 0 8px 8px;
    background: var(--bg-card, #fff);
}
.bc-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 4px 5px 5px;
    background: #fff;
    break-inside: avoid;
}
.bc-num { font-size: 0.62rem; font-weight: 700; color: #64748b; margin-bottom: 2px; }
.bc-svg svg { width: 100%; height: 32px; display: block; }
.bc-svg-2 svg { height: 24px; }
.bc-code {
    font-family: Consolas, 'Courier New', monospace;
    font-weight: 700;
    font-size: 0.68rem;
    letter-spacing: 0;
    text-align: center;
    margin-top: 2px;
}
.bc-code-label { font-size: 0.65rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 8px; }
.bc-code-2 { font-weight: 600; color: #475569; font-size: 0.75rem; }
@media (max-width: 1100px) {
    .bc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 700px) {
    .bc-grid { grid-template-columns: 1fr; }
}
</style>
<?php include __DIR__ . '/_invoice_imei_barcode_sheet.php'; ?>
<?php endif; ?>
<?php endif; ?>
