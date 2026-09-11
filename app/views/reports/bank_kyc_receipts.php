<?php
$periodQs = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate)
    . '&method=' . urlencode((string) $method)
    . '&exclude_discount=' . ($excludeDiscount ? '1' : '0');
$printUrl = '?page=reports&action=bankKycReceiptsPrint&' . $periodQs;
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$methodLabel = static function (string $m): string {
    if ($m === 'bank_like') {
        return 'Bank transfer / cheque / card';
    }
    return ucwords(str_replace('_', ' ', $m));
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Bank KYC — Payments Received</h1>
            <p class="page-subtitle mb-0">A4 register of incoming receipts for bank / KYC submission</p>
        </div>
    </div>
    <?php if (!empty($receipts)): ?>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF (A4)
        </a>
        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="bankKycReceipts">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" required
                       value="<?= htmlspecialchars((string) $toDate) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Payment method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="" <?= $method === '' ? 'selected' : '' ?>>All methods</option>
                    <option value="bank_like" <?= $method === 'bank_like' ? 'selected' : '' ?>>Bank-like (transfer / cheque / card)</option>
                    <option value="bank_transfer" <?= $method === 'bank_transfer' ? 'selected' : '' ?>>Bank transfer</option>
                    <option value="cheque" <?= $method === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                    <option value="card" <?= $method === 'card' ? 'selected' : '' ?>>Card</option>
                    <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="mobile_wallet" <?= $method === 'mobile_wallet' ? 'selected' : '' ?>>Mobile wallet</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Discounts</label>
                <select name="exclude_discount" class="form-select form-select-sm">
                    <option value="1" <?= $excludeDiscount ? 'selected' : '' ?>>Exclude discount entries (recommended)</option>
                    <option value="0" <?= !$excludeDiscount ? 'selected' : '' ?>>Include discounts</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                    <a href="?page=reports&action=bankKycReceipts" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reportError)): ?>
<div class="alert alert-warning"><?= htmlspecialchars((string) $reportError) ?></div>
<?php elseif ($generated): ?>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Receipts</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= number_format((int) $summary['count']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Total received</div>
                <div class="fs-5 fw-bold" style="color:#0e7490;"><?= $money((float) $summary['grand_total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Methods</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= count($summary['by_method']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:#f8fafc;">
            <div class="card-body py-3 text-center">
                <div class="text-muted text-uppercase small fw-bold">Accounts</div>
                <div class="fs-4 fw-bold" style="color:#0e7490;"><?= count($summary['by_account']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($summary['by_method'])): ?>
<div class="card border-0 mb-3">
    <div class="card-body py-2">
        <div class="small text-muted text-uppercase fw-bold mb-2">By method</div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($summary['by_method'] as $bm): ?>
            <span class="badge text-bg-light border" style="font-weight:600;font-size:0.8rem;">
                <?= htmlspecialchars($methodLabel((string) $bm['method'])) ?>:
                <?= $money((float) $bm['total']) ?>
                <span class="text-muted">(<?= (int) $bm['count'] ?>)</span>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($receipts)): ?>
<div class="alert alert-info mb-0">No incoming payments found for this period and filters.</div>
<?php else: ?>

<div class="card border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Payment No</th>
                    <th>Date</th>
                    <th>Party</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th>Reference</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($receipts as $i => $r): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars((string) $r['payment_no']) ?></td>
                    <td><?= date('d M Y', strtotime((string) $r['date'])) ?></td>
                    <td><?= htmlspecialchars((string) ($r['party_name'] ?? '—')) ?></td>
                    <td><?= htmlspecialchars($methodLabel((string) ($r['payment_method'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars((string) ($r['account_name'] ?? '—')) ?></td>
                    <td>
                        <?php
                        $refBits = [];
                        if (!empty($r['ref_type'])) {
                            $refBits[] = ucfirst(str_replace('_', ' ', (string) $r['ref_type']));
                        }
                        if (!empty($r['sale_invoice_no'])) {
                            $refBits[] = (string) $r['sale_invoice_no'];
                        }
                        if (!empty($r['cheque_no'])) {
                            $refBits[] = 'Chq ' . (string) $r['cheque_no'];
                        }
                        echo $refBits ? htmlspecialchars(implode(' · ', $refBits)) : '—';
                        ?>
                    </td>
                    <td class="text-end fw-semibold" style="color:#047857;"><?= $money((float) $r['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="7" class="text-end">Total received</th>
                    <th class="text-end" style="color:#047857;"><?= $money((float) $summary['grand_total']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">
    Branch: <?= htmlspecialchars((string) ($warehouse['name'] ?? 'Current')) ?> ·
    Incoming only (`payment_type = in`) ·
    Open <strong>Download PDF (A4)</strong> and choose <em>Save as PDF</em> for the bank file.
</p>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-secondary mb-0">
    Select a period and click <strong>Generate</strong>. The PDF lists all money received (incoming payments) in that range — suitable for company bank KYC.
    Related: <a href="?page=reports&action=bankKyc">Bank KYC Sales (IMEI)</a>.
</div>
<?php endif; ?>
