<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0">Freight payables</h1>
        <p class="text-muted mb-0" style="font-size:0.83rem;">Open import payables (posted to forwarder ledger on receive) — clear via <a href="?page=payments&action=pay">Payments</a></p>
    </div>
    <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Shipments</a>
</div>

<div class="card mb-3" style="border-radius:12px;">
    <div class="card-body d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Total unpaid freight</span>
        <span class="fw-bold" style="font-size:1.35rem;color:#059669;">KWD <?= number_format((float) ($totalDue ?? 0), DECIMAL_PLACES) ?></span>
    </div>
</div>

<div class="card" style="border-radius:12px;">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <p class="text-center text-muted py-5 mb-0">No unpaid freight lines.</p>
        <?php else: ?>
        <table class="table mb-0" style="font-size:0.84rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>Ref</th>
                    <th>Shipment</th>
                    <th>Received</th>
                    <th>Charge</th>
                    <th>PO / Item</th>
                    <th>Pay to</th>
                    <th class="text-end">KWD</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $notes = $r['charge_label'] . ' — ' . $r['shipment_no'] . ' / ' . $r['po_no'] . ' / ' . $r['item_name'];
                $payUrl = '?page=payments&action=pay&import_payable=1'
                    . '&party_id=' . (int) ($r['party_id'] ?? 0)
                    . '&amount=' . urlencode(number_format((float) $r['amount'], 3, '.', ''))
                    . '&ref_type=' . urlencode($r['ref_type'])
                    . '&ref_id=' . (int) $r['charge_id']
                    . '&notes=' . urlencode($notes);
            ?>
            <tr>
                <td><code class="small"><?= htmlspecialchars($r['accrual_no'] ?? '') ?></code></td>
                <td><a href="?page=landedcost&action=view&id=<?= (int) ($r['shipment_id'] ?? 0) ?>"><?= htmlspecialchars($r['shipment_no']) ?></a></td>
                <td><?= !empty($r['received_date']) ? date('d M Y', strtotime($r['received_date'])) : '—' ?></td>
                <td><?= htmlspecialchars($r['charge_label']) ?></td>
                <td><span class="text-muted"><?= htmlspecialchars($r['po_no']) ?></span><br><?= htmlspecialchars($r['item_name']) ?></td>
                <td><?= htmlspecialchars($r['party_name']) ?></td>
                <td class="text-end fw-semibold"><?= number_format((float) $r['amount'], DECIMAL_PLACES) ?></td>
                <td><a href="<?= htmlspecialchars($payUrl) ?>" class="btn btn-sm btn-outline-success">Pay in Payments</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
