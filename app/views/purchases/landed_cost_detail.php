<?php
$statusLabels = [
    'draft'      => ['Draft', '#e0e7ff', '#3730a3'],
    'in_transit' => ['In transit', '#fef3c7', '#92400e'],
    'received'   => ['Received', '#dbeafe', '#1d4ed8'],
    'applied'    => ['Applied — Kuwait', '#d1fae5', '#065f46'],
];
$st = $statusLabels[$shipment['status'] ?? 'draft'] ?? ['Unknown', '#f1f5f9', '#64748b'];
$canReceive = !in_array($shipment['status'], ['applied'], true);
$canEdit = Auth::canAny(['import_logistics', 'purchases'], 'add') && ($shipment['status'] ?? '') !== 'applied' && empty($shipment['purchases']);
$itemCharges = $shipment['item_charges'] ?? [];
$itemTotal = array_sum(array_map(static function ($c) {
    return (float) $c['freight_hk_dxb'] + (float) ($c['packing_dxb'] ?? 0) + (float) $c['freight_dxb_kwt']
        + (float) $c['partner_profit_per_pc'] * (int) $c['quantity'];
}, $itemCharges));
$itemApplied = array_sum(array_map(static function ($c) {
    if (empty($c['is_applied'])) {
        return 0.0;
    }
    return (float) $c['freight_hk_dxb'] + (float) ($c['packing_dxb'] ?? 0) + (float) $c['freight_dxb_kwt']
        + (float) $c['partner_profit_per_pc'] * (int) $c['quantity'];
}, $itemCharges));
$legacyCost = array_sum(array_map(static fn ($c) => (float) $c['amount'], $shipment['costs'] ?? []));
$legacyApplied = array_sum(array_map(static fn ($c) => !empty($c['is_applied']) ? (float) $c['amount'] : 0, $shipment['costs'] ?? []));
$totalCost = $itemTotal + $legacyCost;
$appliedCost = $itemApplied + $legacyApplied;

$fmtShipmentWhen = static function (?string $at, ?string $dayOnly = null): string {
    if ($at !== null && $at !== '' && $at !== '0000-00-00 00:00:00') {
        $ts = strtotime($at);
        if ($ts) {
            return date('d M Y H:i', $ts);
        }
    }
    if ($dayOnly !== null && $dayOnly !== '' && $dayOnly !== '0000-00-00') {
        $ts = strtotime($dayOnly);
        if ($ts) {
            return date('d M Y', $ts);
        }
    }
    return '';
};

$fmtPayLine = static function (string $prefix, ?string $payNo, ?string $createdAt, ?string $payDate) use ($fmtShipmentWhen): string {
    if ($payNo === null || $payNo === '') {
        return '';
    }
    $when = $fmtShipmentWhen($createdAt, $payDate);
    $html = htmlspecialchars($prefix . ': ' . $payNo);
    if ($when !== '') {
        $html .= ' <span class="text-muted">· ' . htmlspecialchars($when) . '</span>';
    }
    return $html;
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-0"><?= htmlspecialchars($shipment['shipment_no']) ?></h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">
            <span style="background:<?= $st[1] ?>;color:<?= $st[2] ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;margin-left:8px;"><?= $st[0] ?></span>
            <?php
            $createdWhen = $fmtShipmentWhen($shipment['created_at'] ?? null, $shipment['date'] ?? null);
            $receivedWhen = $fmtShipmentWhen(null, $shipment['received_date'] ?? null);
            ?>
            <?php if ($createdWhen !== ''): ?>
            <span class="ms-2">· Created <?= htmlspecialchars($createdWhen) ?></span>
            <?php endif; ?>
            <?php if ($receivedWhen !== ''): ?>
            <span class="ms-2">· Received <?= htmlspecialchars($receivedWhen) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> List</a>
        <?php if ($canEdit): ?>
        <a href="?page=landedcost&action=edit&id=<?= (int) $shipment['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="?page=landedcost&action=payables" class="btn btn-outline-success btn-sm"><i class="bi bi-truck"></i> Freight payables</a>
        <a href="?page=landedcost&action=packingDue" class="btn btn-outline-info btn-sm"><i class="bi bi-box-seam"></i> Packing due</a>
        <a href="?page=landedcost&action=partnerDue" class="btn btn-outline-warning btn-sm"><i class="bi bi-calendar-event"></i> Partner due</a>
        <a href="?page=landedcost&action=report" class="btn btn-outline-primary btn-sm"><i class="bi bi-table"></i> Cost report</a>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm small mb-3" role="note">
    <strong>Costing vs payment</strong><br>
    <span class="text-muted">Per-item freight and partner profit are added to <strong>true unit cost</strong> on <em>Receive in Kuwait</em>.</span><br>
    <span class="text-muted"><strong>Freight HK→DXB / DXB→Kuwait</strong> — on receive, open payables post to the forwarder party ledger; clear via <a href="?page=landedcost&action=payables">Freight payables</a> → <a href="?page=payments&action=pay">Payments</a>.</span><br>
    <span class="text-muted"><strong>Packing DXB</strong> (<?= htmlspecialchars(defined('IMPORT_PACKING_DXB_FORWARDER_NAME') ? IMPORT_PACKING_DXB_FORWARDER_NAME : 'Union Logistics') ?>) — pay monthly via <a href="?page=landedcost&action=packingDue">Packing due</a>.</span><br>
    <span class="text-muted"><strong>Partner profit</strong><?php if (!empty($importPartner)): ?> (<strong><?= htmlspecialchars($importPartner['name']) ?></strong>)<?php endif; ?> — pay monthly via <a href="?page=landedcost&action=partnerDue">Partner due</a> → Payments (e.g. 1st of month).</span>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-muted small mb-1">Accrued logistics (KWD)</p>
            <p class="fw-bold mb-0" style="font-size:1.4rem;color:#059669;"><?= number_format($totalCost, DECIMAL_PLACES) ?></p>
            <?php if ($shipment['status'] === 'applied'): ?>
            <p class="small text-muted mb-0 mt-1">Applied: <?= number_format($appliedCost, DECIMAL_PLACES) ?></p>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-muted small mb-1">Linked POs</p>
            <p class="fw-bold mb-0" style="font-size:1.4rem;"><?= count($shipment['purchase_orders'] ?? []) ?></p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-muted small mb-1">Purchase invoices</p>
            <p class="fw-bold mb-0" style="font-size:1.4rem;"><?= count($shipment['purchases'] ?? []) ?></p>
            <?php if (!empty($shipment['received_date'])): ?>
            <p class="small text-muted mb-0">Received <?= htmlspecialchars($fmtShipmentWhen(null, $shipment['received_date'])) ?></p>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php if ($canReceive && Auth::canAny(['import_logistics', 'purchases'], 'add')): ?>
<div class="card mb-3 border-success" style="border-radius:12px;">
    <div class="card-body">
        <h6 class="fw-bold text-success mb-2"><i class="bi bi-box-arrow-in-down me-1"></i> Receive in Kuwait</h6>
        <p class="small text-muted mb-3">Converts all linked POs to purchase invoices, adds stock to Main warehouse, and applies all logistics costs to true unit cost.</p>
        <form method="POST" action="?page=landedcost&action=receive" class="d-flex flex-wrap gap-2 align-items-end" id="receiveForm">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id'] ?>">
            <div>
                <label class="form-label small fw-bold">Receipt date</label>
                <input type="date" name="received_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="btn btn-success" id="receiveBtn"><i class="bi bi-check2-circle me-1"></i> Receive &amp; apply costs</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card" style="border-radius:12px;">
            <div class="card-header fw-bold small">Purchase orders</div>
            <div class="card-body p-0">
                <?php if (empty($shipment['purchase_orders'])): ?>
                <p class="text-muted text-center py-3 mb-0 small">None</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($shipment['purchase_orders'] as $po): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <a href="?page=purchaseorders&action=show&id=<?= (int) $po['id'] ?>"><?= htmlspecialchars($po['po_no']) ?></a>
                    <span><?= number_format((float) $po['subtotal_kwd'], DECIMAL_PLACES) ?> KWD</span>
                </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card" style="border-radius:12px;">
            <div class="card-header fw-bold small">Purchase invoices</div>
            <div class="card-body p-0">
                <?php if (empty($shipment['purchases'])): ?>
                <p class="text-muted text-center py-3 mb-0 small">Created on receive from POs</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($shipment['purchases'] as $p): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <a href="?page=purchases&action=detail&id=<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['invoice_no']) ?></a>
                    <span>
                        <?= number_format((float) $p['grand_total'], DECIMAL_PLACES) ?>
                        <?php if ((float) ($p['landed_cost'] ?? 0) > 0): ?>
                        <small class="text-muted">(+<?= number_format((float) $p['landed_cost'], DECIMAL_PLACES) ?> log.)</small>
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3" style="border-radius:12px;">
    <div class="card-header fw-bold small">Item charges (KWD)</div>
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:0.78rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>PO / Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">HK→DXB</th>
                    <th>Party</th>
                    <th class="text-end">Packing DXB</th>
                    <th>Party</th>
                    <th class="text-end">DXB→KW</th>
                    <th>Party</th>
                    <th class="text-end">Partner/pc</th>
                    <th>Partner</th>
                    <th>Applied</th>
                    <th>Payments</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($itemCharges)): ?>
            <tr><td colspan="12" class="text-center text-muted py-4">No per-item charges (legacy aggregate costs below if any)</td></tr>
            <?php else: ?>
            <?php foreach ($itemCharges as $c):
                $partnerLine = (float) $c['partner_profit_per_pc'] * (int) $c['quantity'];
            ?>
            <tr>
                <td>
                    <span class="text-muted"><?= htmlspecialchars($c['po_no']) ?></span><br>
                    <?= htmlspecialchars($c['item_name']) ?>
                </td>
                <td class="text-center"><?= (int) $c['quantity'] ?></td>
                <td class="text-end"><?= (float) $c['freight_hk_dxb'] > 0 ? number_format((float) $c['freight_hk_dxb'], DECIMAL_PLACES) : '—' ?></td>
                <td><?= htmlspecialchars($c['hk_party_name'] ?? '—') ?></td>
                <td class="text-end"><?= (float) ($c['packing_dxb'] ?? 0) > 0 ? number_format((float) $c['packing_dxb'], DECIMAL_PLACES) : '—' ?></td>
                <td><?= htmlspecialchars($c['packing_party_name'] ?? '—') ?></td>
                <td class="text-end"><?= (float) $c['freight_dxb_kwt'] > 0 ? number_format((float) $c['freight_dxb_kwt'], DECIMAL_PLACES) : '—' ?></td>
                <td><?= htmlspecialchars($c['kwt_party_name'] ?? '—') ?></td>
                <td class="text-end">
                    <?= (float) $c['partner_profit_per_pc'] > 0 ? number_format((float) $c['partner_profit_per_pc'], DECIMAL_PLACES) : '—' ?>
                    <?php if ($partnerLine > 0): ?><br><small class="text-muted">= <?= number_format($partnerLine, DECIMAL_PLACES) ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['partner_name'] ?? '—') ?></td>
                <td><?= !empty($c['is_applied']) ? '<span class="text-success">Yes</span>' : '<span class="text-warning">Pending</span>' ?></td>
                <td class="small">
                    <?php
                    $payBits = array_filter([
                        $fmtPayLine('HK', $c['hk_payment_no'] ?? null, $c['hk_payment_at'] ?? null, $c['hk_payment_date'] ?? null),
                        $fmtPayLine('Pkg', $c['packing_payment_no'] ?? null, $c['packing_payment_at'] ?? null, $c['packing_payment_date'] ?? null),
                        $fmtPayLine('DXB', $c['dxb_payment_no'] ?? null, $c['dxb_payment_at'] ?? null, $c['dxb_payment_date'] ?? null),
                        $fmtPayLine('Pt', $c['partner_payment_no'] ?? null, $c['partner_payment_at'] ?? null, $c['partner_payment_date'] ?? null),
                    ]);
                    echo $payBits !== [] ? implode('<br>', $payBits) : '—';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($shipment['costs'])): ?>
<div class="card mt-3" style="border-radius:12px;">
    <div class="card-header fw-bold small text-muted">Legacy aggregate cost lines</div>
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead><tr><th>Description</th><th>Category</th><th class="text-end">KWD</th><th>Applied</th><th>Payment</th></tr></thead>
            <tbody>
            <?php foreach ($shipment['costs'] as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['description']) ?></td>
                <td><?= str_replace('_', ' ', $c['cost_category'] ?? 'freight') ?></td>
                <td class="text-end"><?= number_format((float) $c['amount'], DECIMAL_PLACES) ?></td>
                <td><?= !empty($c['is_applied']) ? 'Yes' : 'Pending' ?></td>
                <td>
                    <?php if (!empty($c['payment_no'])): ?>
                    <?= htmlspecialchars($c['payment_no']) ?>
                    <?php
                    $legacyWhen = $fmtShipmentWhen($c['payment_created_at'] ?? null, $c['payment_date'] ?? null);
                    if ($legacyWhen !== ''):
                    ?>
                    <br><small class="text-muted"><?= htmlspecialchars($legacyWhen) ?></small>
                    <?php endif; ?>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const receiveForm = document.getElementById('receiveForm');
    if (receiveForm) {
        receiveForm.addEventListener('submit', function (e) {
            if (!confirm('Receive in Kuwait?\n\nThis will convert POs, add stock, and apply all logistics to unit cost. Cannot be undone easily.')) {
                e.preventDefault();
            }
        });
    }
});
</script>
