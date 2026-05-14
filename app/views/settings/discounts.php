<style>
.disc-page { display:flex; flex-direction:column; gap:14px; }
.disc-hero {
    display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;
    padding:16px 18px; border:1px solid #dbe3f4; border-radius:14px;
    background:linear-gradient(135deg,#eef2ff,#f8faff 55%, #ecfeff);
}
.disc-hero h1 { margin:0; font-size:1.35rem; font-weight:800; color:#1e3a5f; }
.disc-hero p { margin:2px 0 0; color:#64748b; font-size:.86rem; }
.disc-btn-primary {
    border:none; border-radius:10px; padding:9px 14px; font-size:.84rem; font-weight:700;
    color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); box-shadow:0 6px 18px rgba(79,70,229,.28);
}
.disc-btn-primary:hover { filter:brightness(.98); transform:translateY(-1px); }

.disc-card {
    border:1px solid #dbe3f4; border-radius:14px; overflow:hidden; background:#fff;
    box-shadow:0 6px 18px rgba(2,6,23,.04);
}
.disc-card-head {
    display:flex; justify-content:space-between; align-items:center; gap:10px;
    padding:11px 16px; border-bottom:1px solid #e8eefb;
    background:linear-gradient(135deg,#f8faff,#eef3ff);
}
.disc-card-title { font-size:.86rem; font-weight:800; color:#334155; letter-spacing:.2px; }
.disc-card-sub { font-size:.74rem; color:#64748b; font-weight:600; }

.disc-form-wrap { padding:16px; background:#fbfdff; }
.disc-label { font-size:.72rem; font-weight:700; text-transform:uppercase; color:#475569; letter-spacing:.4px; margin-bottom:5px; }
.disc-form-wrap .form-control, .disc-form-wrap .form-select, .disc-form-wrap .input-group-text {
    border-radius:10px; border:1.6px solid #cbd5e1; min-height:36px; font-size:.84rem;
}
.disc-form-wrap .form-control:focus, .disc-form-wrap .form-select:focus {
    border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12);
}

.disc-table-wrap { padding:0; }
#discountTable thead th {
    background:#f8fafc; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.45px;
    border-bottom:1.5px solid #e2e8f0; padding:.68rem .7rem;
}
#discountTable tbody td { padding:.65rem .7rem; font-size:.84rem; vertical-align:middle; }
#discountTable tbody tr:hover { background:#f8fbff; }
.disc-no { font-weight:700; color:#4f46e5; }
.disc-amt { font-weight:800; color:#059669; }
.disc-general { color:#94a3b8; font-style:italic; }
.disc-muted { color:#64748b; font-size:.8rem; }

.disc-act { display:inline-flex; gap:6px; }
.disc-act .btn {
    border:none; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center;
    border-radius:8px; padding:0;
}
.disc-edit  { background:rgba(245,158,11,.14); color:#b45309; }
.disc-print { background:rgba(16,185,129,.14); color:#047857; }
.disc-del   { background:rgba(239,68,68,.14); color:#dc2626; }
</style>

<?php $openNewDiscount = isset($_GET['new']) && $_GET['new'] === '1'; ?>

<div class="disc-page">
    <div class="disc-hero">
        <div>
            <h1>Customer Discounts</h1>
            <p>Give discounts and automatically reduce outstanding customer balances.</p>
        </div>
        <a id="toggleDiscountForm" class="disc-btn-primary" href="?page=discounts&new=1#discountForm" role="button">
            <i class="bi bi-plus-lg me-1"></i> New Discount
        </a>
    </div>

    <div class="disc-card" id="discountForm" style="display:<?= $openNewDiscount ? 'block' : 'none' ?>;">
        <div class="disc-card-head">
            <span class="disc-card-title"><i class="bi bi-tag me-2" style="color:#6366f1;"></i>Give Discount</span>
            <span class="disc-card-sub">Quick formula: per piece × qty = total</span>
        </div>
        <div class="disc-form-wrap">
            <form method="POST" action="?page=discounts&action=store" id="discountCreateForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="discount_form_nonce" value="<?= htmlspecialchars($discountFormNonce ?? '') ?>">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="disc-label">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="disc-label">Customer <span class="text-danger">*</span></label>
                        <select name="party_id" class="form-select form-select-sm" required>
                            <option value="">Select customer...</option>
                            <?php foreach ($parties as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="disc-label">Item <small class="text-muted" style="text-transform:none;">(optional)</small></label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">General</option>
                            <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="disc-label">Per Piece</label>
                        <input type="number" id="discPerPiece" class="form-control form-control-sm" step="0.001" min="0.001" required placeholder="0.000" style="font-weight:700;">
                    </div>
                    <div class="col-md-1">
                        <label class="disc-label">Qty</label>
                        <input type="number" id="discQty" class="form-control form-control-sm" min="1" value="1" required style="font-weight:700;text-align:center;">
                    </div>
                    <div class="col-md-2">
                        <label class="disc-label">Total <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="font-weight:800;"><?= APP_CURRENCY ?></span>
                            <input type="number" name="amount" id="discTotalAmt" class="form-control" step="0.001" min="0.001" required readonly placeholder="0.000" style="font-weight:800;color:#059669;background:#f8fafc;">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="disc-label">Reason</label>
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Price adjustment">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="disc-btn-primary"><i class="bi bi-check-lg me-1"></i> Apply Discount</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelDiscountForm">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="disc-card">
        <div class="disc-card-head">
            <span class="disc-card-title"><i class="bi bi-list-check me-2" style="color:#6366f1;"></i>Discount History</span>
            <span class="disc-card-sub"><?= count($discounts) ?> records</span>
        </div>
        <div class="disc-table-wrap">
            <?php if (empty($discounts)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-tag fs-2 d-block mb-2" style="opacity:.3;"></i>
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
                        <td class="disc-no"><?= $d['discount_no'] ?></td>
                        <td><?= date('d M Y', strtotime($d['date'])) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($d['party_name']) ?></td>
                        <td class="disc-muted">
                            <?= $d['item_name'] ? htmlspecialchars($d['item_name']) : '<span class="disc-general">General</span>' ?>
                        </td>
                        <td class="text-center disc-amt"><?= APP_CURRENCY ?> <?= number_format($d['amount'], DECIMAL_PLACES) ?></td>
                        <td class="disc-muted"><?= htmlspecialchars($d['reason'] ?? '—') ?></td>
                        <td class="disc-muted"><?= htmlspecialchars($d['created_by_name'] ?? '—') ?></td>
                        <td>
                            <div class="disc-act">
                                <a href="?page=discounts&action=edit&id=<?= $d['id'] ?>" class="btn disc-edit pin-protect" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?page=discounts&action=print&id=<?= $d['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn disc-print" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <form method="POST" action="?page=discounts&action=delete" style="display:inline;" class="discount-delete-form" data-amount="<?= number_format($d['amount'], DECIMAL_PLACES) ?>">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn disc-del pin-protect" title="Reverse & Delete">
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
</div>

<script>
if (window.jQuery) {
    jQuery(function($) {
        if ($.fn.DataTable && $('#discountTable').length) {
            $('#discountTable').DataTable({
                pageLength: 25,
                order: [],
                language: { search: '', searchPlaceholder: 'Search discount no, customer, reason...' }
            });
        }
    });
}

function calcDiscTotal() {
    var perPiece = parseFloat(document.getElementById('discPerPiece').value) || 0;
    var qty = parseInt(document.getElementById('discQty').value) || 1;
    var total = perPiece * qty;
    document.getElementById('discTotalAmt').value = total > 0 ? total.toFixed(3) : '';
}

document.addEventListener('DOMContentLoaded', function() {
    var formWrap = document.getElementById('discountForm');
    var discountCreateForm = document.getElementById('discountCreateForm');
    var cancelBtn = document.getElementById('cancelDiscountForm');
    var perPiece = document.getElementById('discPerPiece');
    var qty = document.getElementById('discQty');
    var totalInput = document.getElementById('discTotalAmt');

    if (!formWrap) {
        return;
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() { formWrap.style.display = 'none'; });
    }
    if (perPiece) {
        perPiece.addEventListener('input', calcDiscTotal);
        perPiece.addEventListener('change', calcDiscTotal);
        perPiece.addEventListener('keyup', calcDiscTotal);
    }
    if (qty) {
        qty.addEventListener('input', calcDiscTotal);
        qty.addEventListener('change', calcDiscTotal);
        qty.addEventListener('keyup', calcDiscTotal);
    }
    if (perPiece && qty) {
        calcDiscTotal();
    }
    if (discountCreateForm) {
        discountCreateForm.addEventListener('submit', function() {
            calcDiscTotal();
            if (totalInput) {
                if (!totalInput.value || parseFloat(totalInput.value) <= 0) {
                    totalInput.setCustomValidity('Total must be greater than zero (Per Piece x Qty).');
                } else {
                    totalInput.setCustomValidity('');
                }
            }
        });
    }
    if (totalInput) {
        totalInput.addEventListener('input', function() {
            totalInput.setCustomValidity('');
        });
    }

    document.querySelectorAll('.discount-delete-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var amount = form.getAttribute('data-amount') || '0.000';
            if (!confirm('Reverse this discount? ' + '<?= APP_CURRENCY ?> ' + amount + ' will be added back to customer balance.')) {
                e.preventDefault();
            }
        });
    });
});
</script>
