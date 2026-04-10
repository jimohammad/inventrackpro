<!-- Customer Discounts -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Customer Discounts</h1>
        <p class="page-subtitle">Give discount to a customer — deducts from their outstanding balance</p>
    </div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('discountForm').style.display = document.getElementById('discountForm').style.display === 'none' ? 'block' : 'none'">
        <i class="bi bi-plus-lg me-1"></i> New Discount
    </button>
</div>

<!-- New Discount Form -->
<div class="card mb-4" id="discountForm" style="display:none;border-radius:12px;border:2px solid #c7d2fe;">
    <div class="card-header" style="background:linear-gradient(135deg,#eff6ff,#f0f4ff);padding:12px 20px;font-weight:700;font-size:0.9rem;">
        <i class="bi bi-tag me-2" style="color:#6366f1;"></i> Give Discount
    </div>
    <div class="card-body" style="padding:20px;">
        <form method="POST" action="?page=discounts&action=store">
            <?= Auth::csrfField() ?>
            <div class="row g-3">
                <!-- Date -->
                <div class="col-md-2">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Customer -->
                <div class="col-md-2">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" class="form-select form-select-sm" required>
                        <option value="">Select customer...</option>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Item (optional) -->
                <div class="col-md-2">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Item <small class="text-muted">(optional)</small></label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">General</option>
                        <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Per Piece -->
                <div class="col-md-1">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Per Piece</label>
                    <input type="number" id="discPerPiece" class="form-control form-control-sm" step="0.001" min="0" placeholder="0.000"
                           oninput="calcDiscTotal()" style="font-weight:600;">
                </div>

                <!-- Qty -->
                <div class="col-md-1">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Qty</label>
                    <input type="number" id="discQty" class="form-control form-control-sm" min="1" value="1"
                           oninput="calcDiscTotal()" style="font-weight:600;text-align:center;">
                </div>

                <!-- Total Amount -->
                <div class="col-md-2">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Total <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="font-weight:700;"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" id="discTotalAmt" class="form-control" step="0.001" min="0.001" required placeholder="0.000"
                               style="font-weight:700;color:#10b981;">
                    </div>
                </div>

                <!-- Reason -->
                <div class="col-md-2">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Reason</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Price adjustment">
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Apply Discount</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('discountForm').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Discounts Table -->
<div class="card" style="border-radius:12px;">
    <div class="card-header" style="padding:10px 20px;font-weight:700;font-size:0.88rem;">
        <i class="bi bi-list-check me-2" style="color:#6366f1;"></i> Discount History
        <span style="font-size:0.75rem;color:var(--text-muted);margin-left:8px;">(<?= count($discounts) ?> records)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($discounts)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-tag fs-2 d-block mb-2" style="opacity:0.3;"></i>
            No discounts given yet
        </div>
        <?php else: ?>
        <table class="table mb-0" id="discountTable">
            <thead>
                <tr>
                    <th>Disc #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th class="text-center">Amount</th>
                    <th>Reason</th>
                    <th>By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($discounts as $d): ?>
                <tr>
                    <td style="font-weight:600;color:#6366f1;"><?= $d['discount_no'] ?></td>
                    <td><?= date('d M Y', strtotime($d['date'])) ?></td>
                    <td style="font-weight:500;"><?= htmlspecialchars($d['party_name']) ?></td>
                    <td style="font-size:0.82rem;color:var(--text-muted);">
                        <?= $d['item_name'] ? htmlspecialchars($d['item_name']) : '<span style="color:#94a3b8;">General</span>' ?>
                    </td>
                    <td class="text-center" style="font-weight:700;color:#10b981;">
                        <?= APP_CURRENCY ?> <?= number_format($d['amount'], DECIMAL_PLACES) ?>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($d['reason'] ?? '—') ?></td>
                    <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($d['created_by_name'] ?? '—') ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?page=discounts&action=edit&id=<?= $d['id'] ?>"
                               class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.12);color:#d97706;border:none;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?page=discounts&action=print&id=<?= $d['id'] ?>" target="_blank"
                               class="btn btn-sm" style="background:rgba(16,185,129,0.12);color:#10b981;border:none;" title="Print">
                                <i class="bi bi-printer"></i>
                            </a>
                            <form method="POST" action="?page=discounts&action=delete" style="display:inline;">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn btn-sm pin-protect" style="background:rgba(239,68,68,0.12);color:#ef4444;border:none;" title="Reverse & Delete"
                                        onclick="return confirm('Reverse this discount? KWD <?= number_format($d['amount'], DECIMAL_PLACES) ?> will be added back to customer balance.')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#discountTable').DataTable({
        pageLength: 25,
        order: [],
        language: { search: '', searchPlaceholder: 'Search...' }
    });
});

function calcDiscTotal() {
    var perPiece = parseFloat(document.getElementById('discPerPiece').value) || 0;
    var qty = parseInt(document.getElementById('discQty').value) || 1;
    var total = perPiece * qty;
    document.getElementById('discTotalAmt').value = total > 0 ? total.toFixed(3) : '';
}
</script>
