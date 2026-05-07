<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Customer IMEI Report</h1>
        <p class="page-subtitle">IMEI list sold to a customer, grouped by invoice & item</p>
    </div>
    <?php if (!empty($records)): ?>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm no-print">
        <i class="bi bi-printer me-1"></i> Print
    </button>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-3 no-print" style="border:none;">
    <div class="card-body py-2 th-blue-card">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="customerImei">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600;">Customer</label>
                <select name="party_id" class="form-select form-select-sm" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $partyId == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?> <?= $c['phone'] ? "({$c['phone']})" : '' ?>
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
            <div class="col-6 col-md-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                    <a href="?page=reports&action=customerImei" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($partyId && !empty($records)): ?>
<?php
    $party = $records[0];

    // Group: Invoice -> Item -> IMEIs
    $grouped = [];
    foreach ($records as $r) {
        $invKey  = $r['invoice_no'];
        $itemKey = $r['item_name'];
        if (!isset($grouped[$invKey])) {
            $grouped[$invKey] = [
                'invoice_no' => $r['invoice_no'],
                'sale_id'    => $r['sale_id'],
                'date'       => $r['date'],
                'items'      => [],
                'count'      => 0,
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

<!-- Print Header -->
<div class="print-header">
    <h2 style="margin:0;font-size:16px;font-weight:700;"><?= PDF_COMPANY_NAME ?></h2>
    <p style="margin:2px 0 0;font-size:11px;color:#666;"><?= PDF_COMPANY_PHONE ?></p>
    <p style="margin:8px 0 4px;font-size:13px;font-weight:700;color:#333;">Customer IMEI Report</p>
</div>

<!-- Customer Info -->
<div class="card mb-3 customer-info-card">
    <div class="card-body py-2">
        <table style="width:100%;font-size:0.85rem;">
            <tr>
                <td><strong><?= htmlspecialchars($party['party_name']) ?></strong>
                    <?php if ($party['party_code']): ?>
                    <span class="badge" style="background:rgba(99,102,241,0.15);color:var(--primary);font-size:0.7rem;margin-left:4px;"><?= $party['party_code'] ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($party['party_phone'] ?? '')) ?></td>
                <td>
                    <?= $fromDate ? date('d M Y', strtotime($fromDate)) : 'All time' ?><?= $toDate ? ' — ' . date('d M Y', strtotime($toDate)) : '' ?>
                </td>
                <td class="text-end">
                    <strong><?= $totalImei ?></strong> IMEI<?= $totalImei > 1 ? 's' : '' ?> /
                    <strong><?= $totalInvoices ?></strong> inv.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Grouped by Invoice then by Item -->
<?php $globalNum = 1; ?>
<?php foreach ($grouped as $inv): ?>
<div class="card mb-3 invoice-group">
    <!-- Invoice Header -->
    <div class="card-header inv-header" style="background:rgba(99,102,241,0.08);border-bottom:2px solid rgba(99,102,241,0.2);padding:8px 14px;">
        <div class="d-flex justify-content-between align-items-center">
            <a href="?page=sales&action=detail&id=<?= $inv['sale_id'] ?>" style="color:var(--primary);font-weight:700;text-decoration:none;font-size:0.9rem;" class="inv-link">
                <?= htmlspecialchars($inv['invoice_no']) ?>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="date-badge" style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:5px;font-size:0.75rem;font-weight:600;">
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
        <!-- Item sub-header -->
        <div class="item-header" style="padding:6px 14px;background:rgba(99,102,241,0.04);border-bottom:1px solid var(--border-color);font-size:0.82rem;">
            <strong><?= htmlspecialchars($item['item_name']) ?></strong>
            <?php if ($item['brand'] || $item['model']): ?>
            <small class="text-muted ms-1"><?= htmlspecialchars(trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''))) ?></small>
            <?php endif; ?>
            <span class="text-muted" style="float:right;font-size:0.75rem;"><?= count($item['imeis']) ?> pcs</span>
        </div>
        <!-- IMEI rows -->
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
    <div class="card-body py-2 text-center" style="background:rgba(99,102,241,0.06);font-size:0.85rem;">
        Total: <strong style="color:var(--primary);"><?= $totalImei ?></strong> IMEI<?= $totalImei > 1 ? 's' : '' ?>
        across <strong style="color:var(--primary);"><?= $totalInvoices ?></strong> invoice<?= $totalInvoices > 1 ? 's' : '' ?>
    </div>
</div>

<?php elseif ($partyId && empty($records)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 text-muted">No IMEI records found for this customer in the selected period.</p>
    </div>
</div>
<?php endif; ?>

<style>
.print-header { display: none; }

@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 10px; }
    .page-title { font-size: 13px !important; margin: 0 !important; }
    .page-subtitle { display: none; }

    * { color: #000 !important; }

    .card, .invoice-group, .summary-card {
        box-shadow: none !important;
        border: 1px solid #999 !important;
        break-inside: avoid;
    }
    .card-header, .inv-header {
        background: #fff !important;
        border-bottom: 1px solid #999 !important;
        padding: 4px 10px !important;
    }
    .item-header {
        background: #fff !important;
        border-bottom: 1px solid #ccc !important;
        padding: 3px 10px !important;
        font-size: 11px !important;
    }
    .customer-info-card .card-body,
    .summary-card .card-body {
        background: #fff !important;
    }
    .th-blue, .th-blue-card {
        background: #fff !important;
        color: #000 !important;
    }
    .badge, .date-badge, .count-badge {
        background: #fff !important;
        color: #000 !important;
        border: 1px solid #999 !important;
        padding: 1px 6px !important;
        font-size: 9px !important;
    }
    table { font-size: 10px !important; border-collapse: collapse !important; }
    td, th { padding: 2px 8px !important; border-bottom: 1px solid #ddd !important; }
    .table { margin: 0 !important; }
    .invoice-group { margin-bottom: 8px !important; }
    .mb-4, .mb-3 { margin-bottom: 6px !important; }
}
</style>
