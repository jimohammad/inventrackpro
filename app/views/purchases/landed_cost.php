<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-ship me-2"></i>Shipments & Landed Costs</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">Distribute shipping, customs & import charges across multiple purchase orders</p>
    </div>
    <a href="?page=landedcost&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Shipment
    </a>
</div>

<div class="card" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <?php if (empty($shipments)): ?>
        <div class="text-center py-5">
            <i class="bi bi-ship fs-1 text-muted d-block mb-3"></i>
            <p class="fw-600 mb-1">No shipments recorded yet</p>
            <p class="text-muted mb-3" style="font-size:0.85rem;">Create a shipment to distribute landed costs across multiple purchase orders</p>
            <a href="?page=landedcost&action=create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Shipment
            </a>
        </div>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:10px 16px;">Shipment</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-center">POs</th>
                    <th class="text-end">Total Cost</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $s): ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;">
                    <a href="?page=landedcost&action=view&id=<?= $s['id'] ?>"
                       style="font-weight:700;color:#6366f1;text-decoration:none;">
                        <?= htmlspecialchars($s['shipment_no']) ?>
                    </a>
                </td>
                <td style="color:#64748b;"><?= date('d M Y', strtotime($s['date'])) ?></td>
                <td><?= htmlspecialchars($s['description'] ?? '—') ?></td>
                <td class="text-center">
                    <span style="background:#e0e7ff;color:#4338ca;border-radius:6px;padding:2px 10px;font-weight:700;font-size:0.78rem;">
                        <?= $s['po_count'] ?> PO<?= $s['po_count'] != 1 ? 's' : '' ?>
                    </span>
                </td>
                <td class="text-end fw-semibold" style="color:#059669;">
                    KWD <?= number_format($s['total_cost'], DECIMAL_PLACES) ?>
                </td>
                <td class="text-center">
                    <span style="background:#dcfce7;color:#15803d;border-radius:5px;padding:2px 10px;font-size:0.75rem;font-weight:700;">
                        Applied
                    </span>
                </td>
                <td>
                    <a href="?page=landedcost&action=view&id=<?= $s['id'] ?>"
                       class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;padding:2px 8px;">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
