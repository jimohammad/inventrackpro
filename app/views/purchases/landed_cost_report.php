<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0">True import cost</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">Goods vs logistics (KWD) after Kuwait receipt</p>
    </div>
    <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Shipments</a>
</div>

<div class="card" style="border-radius:12px;">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <p class="text-center text-muted py-5 mb-0">No applied import shipments yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0" id="importCostRptTable" style="font-size:0.82rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th>Shipment</th>
                        <th>Received</th>
                        <th>Invoice</th>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Goods KWD</th>
                        <th class="text-end">Logistics KWD</th>
                        <th class="text-end">Unit landed</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['shipment_no']) ?></td>
                    <td><?= $r['received_date'] ? date('d M Y', strtotime($r['received_date'])) : '—' ?></td>
                    <td><?= htmlspecialchars($r['invoice_no']) ?></td>
                    <td><?= htmlspecialchars($r['item_name']) ?></td>
                    <td class="text-center"><?= (int) $r['quantity'] ?></td>
                    <td class="text-end"><?= number_format(max(0, (float) $r['goods_kwd']), DECIMAL_PLACES) ?></td>
                    <td class="text-end"><?= number_format((float) $r['logistics_kwd'], DECIMAL_PLACES) ?></td>
                    <td class="text-end fw-semibold"><?= number_format((float) $r['unit_landed_kwd'], DECIMAL_PLACES) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
