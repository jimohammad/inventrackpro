<style>
.disc-page { display:flex; flex-direction:column; gap:16px; }

.disc-hero {
    display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap;
    padding:18px 20px; border-radius:16px; border:1px solid #d7e3f8;
    background:
        radial-gradient(1200px 180px at 0% 0%, rgba(99,102,241,.12), transparent 55%),
        linear-gradient(135deg, #f4f7ff 0%, #f8fbff 48%, #eefcf8 100%);
}
.disc-hero h1 { margin:0; font-size:1.4rem; font-weight:800; color:#1e3a5f; letter-spacing:-.02em; }
.disc-hero p { margin:4px 0 0; color:#64748b; font-size:.86rem; }

.disc-card {
    border:1px solid #d7e3f8; border-radius:16px; overflow:hidden; background:#fff;
    box-shadow:0 10px 28px rgba(15,23,42,.05);
}
.disc-card-head {
    display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;
    padding:12px 18px; border-bottom:1px solid #e8eefb;
    background:linear-gradient(135deg,#f8faff,#eef3ff);
}
.disc-card-title { font-size:.9rem; font-weight:800; color:#1e3a5f; }
.disc-card-sub { font-size:.74rem; color:#64748b; font-weight:600; }

.disc-create-body { padding:18px; background:linear-gradient(180deg,#fcfdff 0%, #f7faff 100%); }
.disc-label {
    display:block; font-size:.7rem; font-weight:800; text-transform:uppercase;
    color:#64748b; letter-spacing:.45px; margin-bottom:6px;
}
.disc-create-body .form-control,
.disc-create-body .form-select,
.disc-create-body .input-group-text {
    border-radius:11px; border:1.6px solid #c9d5e8; min-height:40px; font-size:.88rem;
    background:#fff;
}
.disc-create-body .form-control:focus,
.disc-create-body .form-select:focus {
    border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.14);
}
.disc-invoice-hint {
    margin-top:6px; font-size:.72rem; color:#94a3b8; min-height:1em;
}
.disc-invoice-hint.is-ready { color:#059669; font-weight:600; }

.disc-actions {
    display:flex; gap:10px; flex-wrap:wrap; align-items:center;
    margin-top:16px; padding-top:14px; border-top:1px dashed #dbe3f4;
}
.disc-btn-primary {
    border:none; border-radius:11px; padding:10px 16px; font-size:.86rem; font-weight:700;
    color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5);
    box-shadow:0 8px 20px rgba(79,70,229,.28);
}
.disc-btn-primary:hover { filter:brightness(.98); transform:translateY(-1px); }
.disc-btn-print {
    border:1.6px solid #10b981; border-radius:11px; padding:9px 16px;
    font-size:.86rem; font-weight:700; color:#047857; background:#ecfdf5;
}
.disc-btn-print:hover { background:#d1fae5; color:#065f46; }

.disc-table-wrap { padding:0; }
#discountTable thead th {
    background:#f8fafc; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.45px;
    border-bottom:1.5px solid #e2e8f0; padding:.7rem .75rem;
}
#discountTable tbody td { padding:.68rem .75rem; font-size:.84rem; vertical-align:middle; }
#discountTable tbody tr:hover { background:#f8fbff; }
.disc-no { font-weight:700; color:#4f46e5; }
.disc-amt { font-weight:800; color:#059669; }
.disc-inv {
    display:inline-flex; align-items:center; gap:4px;
    font-weight:700; color:#0f766e; text-decoration:none;
}
.disc-inv:hover { text-decoration:underline; }
.disc-muted { color:#64748b; font-size:.8rem; }
.disc-general { color:#94a3b8; font-style:italic; }

.disc-act { display:inline-flex; gap:6px; }
.disc-act .btn {
    border:none; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center;
    border-radius:8px; padding:0;
}
.disc-edit  { background:rgba(245,158,11,.14); color:#b45309; }
.disc-print { background:rgba(16,185,129,.14); color:#047857; }
.disc-del   { background:rgba(239,68,68,.14); color:#dc2626; }

@media (max-width: 768px) {
    .disc-actions { flex-direction:column; align-items:stretch; }
    .disc-btn-primary, .disc-btn-print { width:100%; text-align:center; }
}
</style>

<div class="disc-page">
    <div class="disc-hero">
        <div>
            <h1>Customer Discounts</h1>
        </div>
    </div>

    <div class="disc-card" id="discountForm">
        <div class="disc-card-head">
            <span class="disc-card-title"><i class="bi bi-tag-fill me-2" style="color:#6366f1;"></i>New Discount</span>
            <span class="disc-card-sub">Per piece × qty fills total — edit total anytime</span>
        </div>
        <div class="disc-create-body">
            <form method="POST" action="?page=discounts&action=store" id="discountCreateForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="discount_form_nonce" value="<?= htmlspecialchars($discountFormNonce ?? '') ?>">
                <input type="hidden" name="print_after_save" id="printAfterSave" value="0">

                <div class="row g-3 align-items-start">
                    <div class="col-6 col-md-2">
                        <label class="disc-label" for="discDate">Date</label>
                        <input type="date" name="date" id="discDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="disc-label" for="discParty">Customer <span class="text-danger">*</span></label>
                        <select name="party_id" id="discParty" class="form-select" required>
                            <option value="">Select customer...</option>
                            <?php foreach ($parties as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="disc-label" for="discSale">Invoice <small style="text-transform:none;font-weight:600;color:#94a3b8;">(optional)</small></label>
                        <select name="sale_id" id="discSale" class="form-select" disabled>
                            <option value="">Select customer first...</option>
                        </select>
                        <div class="disc-invoice-hint" id="discInvoiceHint">Last 7 days — choose a customer to load invoices</div>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="disc-label" for="discPerPiece">Per Pc</label>
                        <input type="number" id="discPerPiece" class="form-control" step="0.001" min="0" placeholder="0.000" style="font-weight:700;">
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="disc-label" for="discQty">Qty</label>
                        <input type="number" id="discQty" class="form-control" min="1" value="1" required style="font-weight:700;text-align:center;">
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="disc-label" for="discTotalAmt">Total <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-weight:800;"><?= APP_CURRENCY ?></span>
                            <input type="number" name="amount" id="discTotalAmt" class="form-control" step="0.001" min="0.001" required placeholder="0.000" style="font-weight:800;color:#059669;">
                        </div>
                    </div>
                </div>

                <div class="disc-actions">
                    <button type="submit" class="disc-btn-primary" id="applyDiscountBtn">
                        <i class="bi bi-check-lg me-1"></i> Apply Discount
                    </button>
                    <button type="submit" class="disc-btn-print" id="applyPrintDiscountBtn">
                        <i class="bi bi-printer me-1"></i> Apply &amp; Print
                    </button>
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
                        <th>Invoice</th>
                        <th class="text-center">Amount</th>
                        <th>By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $d): ?>
                    <tr>
                        <td class="disc-no"><?= htmlspecialchars($d['discount_no']) ?></td>
                        <td><?= date('d M Y', strtotime($d['date'])) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($d['party_name']) ?></td>
                        <td>
                            <?php if (!empty($d['sale_invoice_no']) && !empty($d['sale_id'])): ?>
                            <a class="disc-inv" href="?page=sales&action=detail&id=<?= (int) $d['sale_id'] ?>">
                                <i class="bi bi-receipt"></i> <?= htmlspecialchars($d['sale_invoice_no']) ?>
                            </a>
                            <?php elseif (!empty($d['item_name'])): ?>
                            <span class="disc-muted" title="Legacy item link"><?= htmlspecialchars($d['item_name']) ?></span>
                            <?php else: ?>
                            <span class="disc-general">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center disc-amt"><?= APP_CURRENCY ?> <?= number_format($d['amount'], DECIMAL_PLACES) ?></td>
                        <td class="disc-muted"><?= htmlspecialchars($d['created_by_name'] ?? '—') ?></td>
                        <td>
                            <div class="disc-act">
                                <a href="?page=discounts&action=edit&id=<?= (int) $d['id'] ?>" class="btn disc-edit pin-protect" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?page=discounts&action=print&id=<?= (int) $d['id'] ?>" class="btn disc-print" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <form method="POST" action="?page=discounts&action=delete" style="display:inline;" class="discount-delete-form" data-amount="<?= number_format($d['amount'], DECIMAL_PLACES) ?>">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
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
                language: { search: '', searchPlaceholder: 'Search discount no, customer, invoice...' }
            });
        }
    });
}

var discTotalManual = false;
var discCurrency = <?= json_encode(APP_CURRENCY) ?>;
var discDecimals = <?= (int) DECIMAL_PLACES ?>;

function calcDiscTotal(force) {
    if (discTotalManual && !force) {
        return;
    }
    var perPieceEl = document.getElementById('discPerPiece');
    var qtyEl = document.getElementById('discQty');
    var totalEl = document.getElementById('discTotalAmt');
    if (!perPieceEl || !qtyEl || !totalEl) {
        return;
    }
    var perPiece = parseFloat(perPieceEl.value) || 0;
    var qty = parseInt(qtyEl.value, 10) || 1;
    if (perPiece <= 0) {
        return;
    }
    var total = perPiece * qty;
    totalEl.value = total > 0 ? total.toFixed(3) : '';
}

function formatMoney(n) {
    return discCurrency + ' ' + (Number(n) || 0).toFixed(discDecimals);
}

function setInvoiceOptions(invoices, selectedId) {
    var saleSelect = document.getElementById('discSale');
    var hint = document.getElementById('discInvoiceHint');
    if (!saleSelect) {
        return;
    }
    saleSelect.innerHTML = '';
    var blank = document.createElement('option');
    blank.value = '';
    blank.textContent = invoices.length ? 'No invoice (general)' : 'No invoices found';
    saleSelect.appendChild(blank);

    invoices.forEach(function(inv) {
        var opt = document.createElement('option');
        opt.value = String(inv.id);
        opt.textContent = inv.invoice_no + ' · ' + inv.date + ' · Bal ' + formatMoney(inv.balance);
        if (selectedId && String(selectedId) === String(inv.id)) {
            opt.selected = true;
        }
        saleSelect.appendChild(opt);
    });

    saleSelect.disabled = false;
    if (hint) {
        if (invoices.length) {
            hint.textContent = invoices.length + ' invoice' + (invoices.length === 1 ? '' : 's') + ' in last 7 days';
            hint.classList.add('is-ready');
        } else {
            hint.textContent = 'No invoices in the last 7 days for this customer';
            hint.classList.remove('is-ready');
        }
    }
}

function resetInvoiceSelect() {
    var saleSelect = document.getElementById('discSale');
    var hint = document.getElementById('discInvoiceHint');
    if (!saleSelect) {
        return;
    }
    saleSelect.innerHTML = '<option value="">Select customer first...</option>';
    saleSelect.disabled = true;
    if (hint) {
        hint.textContent = 'Last 7 days — choose a customer to load invoices';
        hint.classList.remove('is-ready');
    }
}

function loadCustomerInvoices(partyId) {
    var saleSelect = document.getElementById('discSale');
    var hint = document.getElementById('discInvoiceHint');
    if (!partyId) {
        resetInvoiceSelect();
        return;
    }
    if (saleSelect) {
        saleSelect.disabled = true;
        saleSelect.innerHTML = '<option value="">Loading invoices...</option>';
    }
    if (hint) {
        hint.textContent = 'Loading last 7 days...';
        hint.classList.remove('is-ready');
    }
    fetch('?page=discounts&action=customerInvoices&party_id=' + encodeURIComponent(partyId))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setInvoiceOptions((data && data.invoices) ? data.invoices : []);
        })
        .catch(function() {
            resetInvoiceSelect();
            if (hint) {
                hint.textContent = 'Could not load invoices. Try again.';
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    var discountCreateForm = document.getElementById('discountCreateForm');
    var printAfterSave = document.getElementById('printAfterSave');
    var applyBtn = document.getElementById('applyDiscountBtn');
    var applyPrintBtn = document.getElementById('applyPrintDiscountBtn');
    var perPiece = document.getElementById('discPerPiece');
    var qty = document.getElementById('discQty');
    var totalInput = document.getElementById('discTotalAmt');
    var partySelect = document.getElementById('discParty');

    if (!discountCreateForm) {
        return;
    }

    if (partySelect) {
        partySelect.addEventListener('change', function() {
            loadCustomerInvoices(partySelect.value);
        });
        partySelect.focus();
    }

    function onFormulaInput() {
        calcDiscTotal(false);
    }
    if (perPiece) {
        perPiece.addEventListener('input', onFormulaInput);
        perPiece.addEventListener('change', onFormulaInput);
    }
    if (qty) {
        qty.addEventListener('input', onFormulaInput);
        qty.addEventListener('change', onFormulaInput);
    }
    if (totalInput) {
        totalInput.addEventListener('input', function() {
            discTotalManual = true;
            totalInput.setCustomValidity('');
        });
    }
    if (applyBtn && printAfterSave) {
        applyBtn.addEventListener('click', function() {
            printAfterSave.value = '0';
        });
    }
    if (applyPrintBtn && printAfterSave) {
        applyPrintBtn.addEventListener('click', function() {
            printAfterSave.value = '1';
        });
    }
    discountCreateForm.addEventListener('submit', function() {
        if (totalInput) {
            if (!totalInput.value || parseFloat(totalInput.value) <= 0) {
                totalInput.setCustomValidity('Total must be greater than zero.');
            } else {
                totalInput.setCustomValidity('');
            }
        }
    });

    document.querySelectorAll('.discount-delete-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var amount = form.getAttribute('data-amount') || '0.000';
            if (!confirm('Reverse this discount? ' + discCurrency + ' ' + amount + ' will be added back to customer balance.')) {
                e.preventDefault();
            }
        });
    });
});
</script>
