<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-globe2 me-2"></i>Import Logistics</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">HK/Dubai → Kuwait: consolidate POs, accrue KWD charges, receive in Kuwait for true unit cost</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?page=landedcost&action=payables" class="btn btn-outline-success btn-sm"><i class="bi bi-truck me-1"></i> Freight payables</a>
        <a href="?page=landedcost&action=packingDue" class="btn btn-outline-info btn-sm"><i class="bi bi-box-seam me-1"></i> Packing due</a>
        <a href="?page=landedcost&action=partnerDue" class="btn btn-outline-warning btn-sm"><i class="bi bi-calendar-event me-1"></i> Partner due</a>
        <a href="?page=landedcost&action=partnerHistory" class="btn btn-outline-success btn-sm"><i class="bi bi-clock-history me-1"></i> Partner history</a>
        <a href="?page=landedcost&action=report" class="btn btn-outline-primary btn-sm"><i class="bi bi-table me-1"></i> Cost report</a>
        <?php if (Auth::canAny(['import_logistics', 'purchases'], 'add')): ?>
        <a href="?page=landedcost&action=create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New shipment
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <?php if (empty($shipments)): ?>
        <div class="text-center py-5">
            <i class="bi bi-globe2 fs-1 text-muted d-block mb-3"></i>
            <p class="fw-600 mb-1">No import shipments yet</p>
            <p class="text-muted mb-3" style="font-size:0.85rem;">Attach paid/draft POs, add Dubai/Kuwait logistics in KWD, then receive in Kuwait to update stock cost.</p>
            <?php if (Auth::canAny(['import_logistics', 'purchases'], 'add')): ?>
            <a href="?page=landedcost&action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New shipment</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:10px 16px;">Shipment</th>
                    <th>Date</th>
                    <th class="text-center">POs</th>
                    <th class="text-center">Purchases</th>
                    <th class="text-end">Accrued KWD</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $statusMap = [
                'draft' => ['Draft', '#e0e7ff', '#3730a3'],
                'in_transit' => ['In transit', '#fef3c7', '#92400e'],
                'received' => ['Received', '#dbeafe', '#1d4ed8'],
                'applied' => ['Applied', '#d1fae5', '#065f46'],
            ];
            foreach ($shipments as $s):
                $sm = $statusMap[$s['status'] ?? 'draft'] ?? ['—', '#f1f5f9', '#64748b'];
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;">
                    <a href="?page=landedcost&action=view&id=<?= (int) $s['id'] ?>"
                       style="font-weight:700;color:#6366f1;text-decoration:none;">
                        <?= htmlspecialchars($s['shipment_no']) ?>
                    </a>
                </td>
                <td style="color:#64748b;"><?= date('d M Y', strtotime($s['date'])) ?></td>
                <td class="text-center"><?= (int) ($s['po_count'] ?? 0) ?></td>
                <td class="text-center"><?= (int) ($s['purchase_count'] ?? 0) ?></td>
                <td class="text-end fw-semibold" style="color:#059669;">
                    <?= number_format((float) ($s['total_cost'] ?? 0), DECIMAL_PLACES) ?>
                    <?php if (($s['status'] ?? '') === 'applied' && (float) ($s['applied_cost'] ?? 0) > 0): ?>
                    <br><small class="text-muted fw-normal">applied <?= number_format((float) $s['applied_cost'], DECIMAL_PLACES) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span style="background:<?= $sm[1] ?>;color:<?= $sm[2] ?>;border-radius:5px;padding:2px 10px;font-size:0.75rem;font-weight:700;">
                        <?= $sm[0] ?>
                    </span>
                </td>
                <td class="text-nowrap">
                    <a href="?page=landedcost&action=view&id=<?= (int) $s['id'] ?>"
                       class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;">View</a>
                    <?php if (Auth::canAny(['import_logistics', 'purchases'], 'add') && ($s['status'] ?? '') !== 'applied'): ?>
                    <a href="?page=landedcost&action=edit&id=<?= (int) $s['id'] ?>"
                       class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
