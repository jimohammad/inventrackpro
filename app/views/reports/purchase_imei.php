<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-start gap-2">
        <a href="?page=reports" class="btn btn-sm btn-outline-secondary mt-1" title="Back to Reports"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">Purchase IMEI Report</h1>
            <p class="page-subtitle">IMEI list received from a supplier, grouped by purchase invoice & item</p>
        </div>
    </div>
    <?php if (!empty($records)):
        $imeiPrintQs = 'supplier_id=' . (int) $supplierId
            . '&from_date=' . urlencode((string) $fromDate)
            . '&to_date=' . urlencode((string) $toDate);
        if (!empty($invoiceNo)) {
            $imeiPrintQs .= '&invoice_no=' . urlencode((string) $invoiceNo);
        }
        $imeiPrintUrl   = '?page=reports&action=purchaseImeiPrint&' . $imeiPrintQs;
        $imeiExportUrl  = '?page=reports&action=purchaseImeiExport&' . $imeiPrintQs;
    ?>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($imeiExportUrl) ?>" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="<?= htmlspecialchars($imeiPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="<?= htmlspecialchars($imeiPrintUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="purchaseImei">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $supplierId == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?> <?= $s['phone'] ? "({$s['phone']})" : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Purchase Invoice No</label>
                <input type="text" name="invoice_no" class="form-control form-control-sm"
                       value="<?= htmlspecialchars((string) ($invoiceNo ?? '')) ?>"
                       placeholder="e.g. PUR-000042">
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                    <a href="?page=reports&action=purchaseImei" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($supplierId && !empty($records)): ?>
<?php
    $party = $records[0];

    $grouped = [];
    foreach ($records as $r) {
        $invKey  = $r['invoice_no'];
        $itemKey = $r['item_name'];
        if (!isset($grouped[$invKey])) {
            $grouped[$invKey] = [
                'invoice_no'  => $r['invoice_no'],
                'purchase_id' => $r['purchase_id'],
                'date'        => $r['date'],
                'items'       => [],
                'count'       => 0,
            ];
        }
        if (!isset($grouped[$invKey]['items'][$itemKey])) {
            $grouped[$invKey]['items'][$itemKey] = [
                'item_name' => $r['item_name'],
                'brand'     => $r['brand'],
                'model'     => $r['model'],
                'imeis'     => [],
            ];
        }
        $grouped[$invKey]['items'][$itemKey]['imeis'][] = $r;
        $grouped[$invKey]['count']++;
    }
    $totalImei = count($records);
    $totalInvoices = count($grouped);
?>

<!-- Supplier Info -->
<div class="card mb-3 customer-info-card">
    <div class="card-body py-2">
        <table style="width:100%;font-size:0.85rem;">
            <tr>
                <td>Supplier Name: <strong><?= htmlspecialchars($party['party_name']) ?></strong>
                    <?php if ($party['party_code']): ?>
                    <span class="badge" style="background:rgba(245,158,11,0.15);color:#d97706;font-size:0.7rem;margin-left:4px;"><?= htmlspecialchars($party['party_code']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($party['party_phone'] ?? '')) ?></td>
                <td>
                    <?= $fromDate ? date('d M Y', strtotime($fromDate)) : 'All time' ?><?= $toDate ? ' — ' . date('d M Y', strtotime($toDate)) : '' ?>
                    <?php if (!empty($invoiceNo)): ?>
                    <span class="badge ms-1" style="background:rgba(245,158,11,0.15);color:#d97706;font-size:0.7rem;"><?= htmlspecialchars($invoiceNo) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <strong><?= $totalImei ?></strong> IMEI<?= $totalImei > 1 ? 's' : '' ?> /
                    <strong><?= $totalInvoices ?></strong> inv.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Grouped by Purchase Invoice then by Item -->
<?php $globalNum = 1; ?>
<?php foreach ($grouped as $inv): ?>
<div class="card mb-3 invoice-group">
    <div class="card-header inv-header" style="background:rgba(245,158,11,0.08);border-bottom:2px solid rgba(245,158,11,0.2);padding:8px 14px;">
        <div class="d-flex justify-content-between align-items-center">
            <a href="?page=purchases&action=detail&id=<?= $inv['purchase_id'] ?>" style="color:#d97706;font-weight:700;text-decoration:none;font-size:0.9rem;" class="inv-link">
                Purchase Invoice: <?= htmlspecialchars($inv['invoice_no']) ?>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="date-badge" style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:5px;font-size:0.75rem;font-weight:600;">
                    <?= date('d M Y', strtotime($inv['date'])) ?>
                </span>
                <span class="count-badge" style="background:rgba(16,185,129,0.12);color:#059669;padding:3px 10px;border-radius:5px;font-size:0.75rem;font-weight:600;">
                    <?= $inv['count'] ?> pcs
                </span>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
    <?php foreach ($inv['items'] as $item): ?>
        <div class="item-header" style="padding:6px 14px;background:rgba(245,158,11,0.04);border-bottom:1px solid var(--border-color);font-size:0.82rem;">
            <strong><?= htmlspecialchars($item['item_name']) ?></strong>
            <?php if ($item['brand'] || $item['model']): ?>
            <small class="text-muted ms-1"><?= htmlspecialchars(trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''))) ?></small>
            <?php endif; ?>
            <span class="text-muted" style="float:right;font-size:0.75rem;"><?= count($item['imeis']) ?> pcs</span>
        </div>
        <table class="table mb-0" style="font-size:0.82rem;">
            <tbody>
                <?php foreach ($item['imeis'] as $r): ?>
                <tr>
                    <td style="width:36px;color:var(--text-muted);font-size:0.75rem;text-align:center;"><?= $globalNum++ ?></td>
                    <td style="font-family:'Courier New',monospace;font-weight:600;letter-spacing:0.5px;"><?= htmlspecialchars($r['imei']) ?></td>
                    <td style="font-family:'Courier New',monospace;color:var(--text-muted);"><?= htmlspecialchars($r['imei2'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Summary -->
<div class="card summary-card">
    <div class="card-body py-2 text-center" style="background:rgba(245,158,11,0.06);font-size:0.85rem;">
        Total: <strong style="color:#d97706;"><?= $totalImei ?></strong> IMEI<?= $totalImei > 1 ? 's' : '' ?>
        across <strong style="color:#d97706;"><?= $totalInvoices ?></strong> purchase invoice<?= $totalInvoices > 1 ? 's' : '' ?>
    </div>
</div>

<?php elseif ($supplierId && empty($records)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 text-muted">No IMEI records found for this supplier with the selected filters.</p>
    </div>
</div>
<?php endif; ?>
