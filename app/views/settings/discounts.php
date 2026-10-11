<style>
.disc-page { display:flex; flex-direction:column; gap:10px; }
.disc-head {
    display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.disc-head .page-title { margin:0; font-size:1.15rem; }
.disc-count {
    font-size:.72rem; font-weight:700; color:#64748b;
    background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:3px 9px;
}

.disc-shell {
    border:1px solid #d7e0ee; border-radius:10px; overflow:hidden;
    background:#fff;
}

.disc-create { padding:8px 10px 18px; background:#eef2ff; border-bottom:1px solid #dbe3f4; }
.disc-grid {
    display:grid;
    grid-template-columns: 110px minmax(0,1.2fr) minmax(0,1.25fr) 70px 52px 122px max-content;
    gap:6px 8px;
    align-items:end;
}
.disc-field { min-width:0; position:relative; }
.disc-label {
    display:block; font-size:.62rem; font-weight:800; text-transform:uppercase;
    color:#64748b; letter-spacing:.4px; margin-bottom:3px; line-height:1.2;
}
.disc-create .form-control,
.disc-create .form-select,
.disc-create .input-group-text {
    border-radius:7px; border:1.4px solid #c9d5e8; min-height:32px; height:32px;
    font-size:.8rem; padding:2px 8px; background:#fff;
}
.disc-create .input-group { flex-wrap:nowrap; height:32px; }
.disc-create .input-group > .form-control { min-width:0; }
.disc-create .input-group-text {
    font-size:.68rem; font-weight:800; padding:0 8px; color:#475569; background:#f1f5f9;
}
.disc-create .form-control:focus,
.disc-create .form-select:focus {
    border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.14);
}
.disc-create #discTotalAmt { font-weight:800; color:#059669; }
.disc-hint {
    position:absolute; left:0; top:100%; margin:0; font-size:.62rem; color:#94a3b8; line-height:1.2; white-space:nowrap;
}
.disc-hint.is-ready { color:#059669; font-weight:600; }

.disc-actions { display:flex; gap:6px; flex-wrap:nowrap; align-items:center; padding-bottom:1px; }
.disc-btn {
    border:none; border-radius:7px; height:32px; padding:0 12px;
    font-size:.78rem; font-weight:700; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;
}
.disc-btn-primary { color:#fff; background:#4f46e5; }
.disc-btn-primary:hover { background:#4338ca; color:#fff; }
.disc-btn-print { border:1.4px solid #10b981; color:#047857; background:#ecfdf5; }
.disc-btn-print:hover { background:#d1fae5; color:#065f46; }

.disc-table-wrap { padding:0; }
.disc-table-wrap .dataTables_wrapper { padding:4px 8px 8px; }
.disc-table-wrap .dataTables_filter,
.disc-table-wrap .dataTables_length { margin-top:4px; }
.disc-table-wrap .dataTables_filter input {
    height:28px; font-size:.78rem; border:1px solid #dbe3ee; border-radius:6px; padding:0 8px;
}
.disc-table-wrap .dataTables_length,
.disc-table-wrap .dataTables_info,
.disc-table-wrap .dataTables_paginate { font-size:.72rem; }
#discountTable { font-size:.8rem; margin:0; }
#discountTable thead th {
    background:#f8fafc; color:#64748b; font-size:.65rem; text-transform:uppercase; letter-spacing:.4px;
    border-bottom:1px solid #e2e8f0; padding:6px 8px; white-space:nowrap; font-weight:700;
}
#discountTable tbody td { padding:4px 8px; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
#discountTable tbody tr { height:34px; }
#discountTable tbody tr:hover { background:#f8fbff; }
.disc-no { font-weight:700; color:#4f46e5; font-variant-numeric:tabular-nums; }
.disc-amt { font-weight:800; color:#059669; font-variant-numeric:tabular-nums; white-space:nowrap; }
.disc-inv {
    display:inline-flex; align-items:center; gap:4px;
    font-weight:700; color:#0f766e; text-decoration:none; font-size:.78rem;
}
.disc-inv:hover { text-decoration:underline; }
.disc-muted { color:#64748b; font-size:.76rem; }
.disc-general { color:#94a3b8; }
.disc-empty { text-align:center; color:#94a3b8; padding:28px 12px; font-size:.82rem; }

.disc-act { display:inline-flex; gap:4px; }
.disc-act .btn {
    border:none; width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center;
    border-radius:6px; padding:0; font-size:.75rem;
}
.disc-edit  { background:rgba(245,158,11,.14); color:#b45309; }
.disc-print { background:rgba(16,185,129,.14); color:#047857; }
.disc-del   { background:rgba(239,68,68,.14); color:#dc2626; }

@media (max-width: 992px) {
    .disc-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .disc-actions { grid-column:1 / -1; }
}
@media (max-width: 640px) {
    .disc-grid { grid-template-columns: 1fr 1fr; }
    .disc-actions { flex-direction:column; align-items:stretch; }
    .disc-btn { width:100%; justify-content:center; }
}
</style>

<div class="disc-page">
    <div class="disc-head">
        <h1 class="page-title">Customer Discounts</h1>
        <span class="disc-count"><?= count($discounts) ?> records</span>
    </div>

    <div class="disc-shell" id="discountForm">
        <form method="POST" action="?page=discounts&action=store" id="discountCreateForm" class="disc-create">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="discount_form_nonce" value="<?= htmlspecialchars($discountFormNonce ?? '') ?>">
            <input type="hidden" name="print_after_save" id="printAfterSave" value="0">

            <div class="disc-grid">
                <div class="disc-field">
                    <label class="disc-label" for="discDate">Date</label>
                    <input type="date" name="date" id="discDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="disc-field">
                    <label class="disc-label" for="discParty">Customer <span class="text-danger">*</span></label>
                    <select name="party_id" id="discParty" class="form-select" required>
                        <option value="">Select customer...</option>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="disc-field">
                    <label class="disc-label" for="discSale">Invoice <span style="text-transform:none;font-weight:600;color:#94a3b8;">optional</span></label>
                    <select name="sale_id" id="discSale" class="form-select" disabled>
                        <option value="">Select customer first...</option>
                    </select>
                    <div class="disc-hint" id="discInvoiceHint">Last 7 days</div>
                </div>
                <div class="disc-field">
                    <label class="disc-label" for="discPerPiece">Per Pc</label>
                    <input type="number" id="discPerPiece" class="form-control" step="0.001" min="0" value="0.250" placeholder="0.000" style="font-weight:700;">
                </div>
                <div class="disc-field">
                    <label class="disc-label" for="discQty">Qty</label>
                    <input type="number" id="discQty" class="form-control" min="1" value="1" required style="font-weight:700;text-align:center;">
                </div>
                <div class="disc-field">
                    <label class="disc-label" for="discTotalAmt">Total <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><?= APP_CURRENCY ?></span>
                        <input type="number" name="amount" id="discTotalAmt" class="form-control" step="0.001" min="0.001" required placeholder="0.000">
                    </div>
                </div>
                <div class="disc-actions">
                    <button type="submit" class="disc-btn disc-btn-primary" id="applyDiscountBtn">
                        <i class="bi bi-check-lg"></i> Apply
                    </button>
                    <button type="submit" class="disc-btn disc-btn-print" id="applyPrintDiscountBtn">
                        <i class="bi bi-printer"></i> Apply &amp; Print
                    </button>
                </div>
            </div>
        </form>

        <div class="disc-table-wrap">
            <?php if (empty($discounts)): ?>
            <div class="disc-empty">No discounts given yet</div>
            <?php else: ?>
            <table class="table mb-0" id="discountTable">
                <thead>
                    <tr>
                        <th>Disc #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th class="text-end">Amount</th>
                        <th>By</th>
                        <th style="width:92px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $d): ?>
                    <tr>
                        <td class="disc-no"><?= htmlspecialchars($d['discount_no']) ?></td>
                        <td class="disc-muted"><?= date('d M Y', strtotime($d['date'])) ?></td>
                        <td style="font-weight:650;"><?= htmlspecialchars($d['party_name']) ?></td>
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
                        <td class="text-end disc-amt"><?= APP_CURRENCY ?> <?= number_format($d['amount'], DECIMAL_PLACES) ?></td>
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
            hint.textContent = invoices.length + ' in last 7 days';
            hint.classList.add('is-ready');
        } else {
            hint.textContent = 'No invoices in last 7 days';
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
        hint.textContent = 'Last 7 days';
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
        hint.textContent = 'Loading...';
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
                hint.textContent = 'Could not load invoices';
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

    calcDiscTotal(true);

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
