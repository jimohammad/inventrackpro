<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title">Sales</h1>
        <?php if (!empty($filters['voided_only'])): ?>
        <p class="page-subtitle mb-0">Voided invoices only (this branch; excluded from normal list & ledgers).</p>
        <?php endif; ?>
        <?php if (Auth::isAdmin() && empty($filters['voided_only'])): ?>
        <p class="mb-0 mt-1"><a href="?page=sales&view=voided" class="small fw-semibold" style="color:#64748b;">View voided invoices →</a></p>
        <?php elseif (Auth::isAdmin() && !empty($filters['voided_only'])): ?>
        <p class="mb-0 mt-1"><a href="?page=sales" class="small fw-semibold" style="color:#64748b;">← Active sales</a></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 align-items-center">
    <?php if (Auth::can('sales', 'add')): ?>
    <a href="?page=sales&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Sale
    </a>
    <?php endif; ?>
    </div>
</div>


<!-- Filters -->
<form method="GET" action="" id="salesFilterForm" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="sales">
    <?php if (Auth::isAdmin() && !empty($filters['voided_only'])): ?>
    <input type="hidden" name="view" value="voided">
    <?php endif; ?>
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Invoice, phone, code, email..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-person me-1"></i>Customer
            </label>
            <select name="party_id" id="salesCustomerFilter"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All customers</option>
                <?php if (!empty($filterCustomer)): ?>
                <option value="<?= (int) $filterCustomer['id'] ?>" selected>
                    <?= htmlspecialchars($filterCustomer['name']) ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-box-seam me-1"></i>Item
            </label>
            <select name="item" id="salesItemFilter"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All items</option>
                <?php if (!empty($filterItem)): ?>
                <option value="<?= (int) $filterItem['id'] ?>" selected>
                    <?= htmlspecialchars($filterItem['name']) ?><?= ($filterItem['sku'] ?? '') !== '' ? ' (' . htmlspecialchars($filterItem['sku']) . ')' : '' ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From <span style="font-weight:600;opacity:0.65;">optional</span>
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) $filters['from_date']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To <span style="font-weight:600;opacity:0.65;">optional</span>
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <?php if (Auth::isAdmin() && empty($filters['voided_only'])): ?>
        <div style="display:flex;align-items:center;gap:8px;padding-bottom:2px;">
            <label class="small fw-semibold mb-0 d-flex align-items-center gap-2" style="color:#475569;cursor:pointer;white-space:nowrap;">
                <input type="checkbox" name="include_voided" value="1" <?= !empty($filters['include_voided']) ? 'checked' : '' ?> style="accent-color:#6366f1;">
                Include voided
            </label>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="<?= !empty($filters['voided_only']) ? '?page=sales&view=voided' : '?page=sales' ?>"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<?php
$listPageName = 'sales';
$listPageExtra = [];
if (!empty($filters['voided_only'])) {
    $listPageExtra['view'] = 'voided';
}
if (!empty($filters['include_voided'])) {
    $listPageExtra['include_voided'] = '1';
}
if (!empty($filters['party_id'])) {
    $listPageExtra['party_id'] = (int) $filters['party_id'];
}
if (($filters['item'] ?? '') !== '') {
    $listPageExtra['item'] = (string) $filters['item'];
}
if (($filters['search'] ?? '') !== '') {
    $listPageExtra['search'] = (string) $filters['search'];
}
if (($filters['status'] ?? '') !== '') {
    $listPageExtra['status'] = (string) $filters['status'];
}
$datesDefaulted = false;
include __DIR__ . '/../partials/list_page_alerts.php';
?>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="salesTable">
                <thead>
                    <tr>
                        <th class="th-blue">Invoice No</th>
                        <th class="th-blue">Date</th>
                        <th class="th-blue">Party</th>
                        <th class="th-blue">Total</th>
                        <th class="th-blue">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No sales found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($sales as $s):
                        $rowCancelled = ($s['status'] ?? '') === 'cancelled';
                        $invoiceAt    = $s['created_at'] ?? $s['date'];
                        $invoiceDay   = substr(trim((string) $invoiceAt), 0, 10);
                        $isToday      = !$rowCancelled && $invoiceDay === date('Y-m-d');
                    ?>
                    <tr class="<?= $rowCancelled ? 'table-secondary' : ($isToday ? 'sale-row-today' : '') ?>">
                        <td>
                            <a href="?page=sales&action=detail&id=<?= $s['id'] ?>"
                               class="sale-inv-no">
                                <?= htmlspecialchars((string) $s['invoice_no']) ?>
                            </a>
                            <?php if ($rowCancelled): ?>
                            <span class="badge bg-secondary ms-1 sale-void-badge">Voided</span>
                            <?php endif; ?>
                        </td>
                        <td data-order="<?= htmlspecialchars(date('Y-m-d H:i:s', strtotime((string) $invoiceAt))) ?>">
                            <span class="sale-date"><?= date('m/d/Y, h:i A', strtotime((string) $invoiceAt)) ?></span>
                        </td>
                        <td class="sale-party"><?= htmlspecialchars($s['party_name']) ?></td>
                        <td class="sale-total"><?= APP_CURRENCY ?> <?= number_format($s['grand_total'], DECIMAL_PLACES) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=sales&action=detail&id=<?= $s['id'] ?>"
                                   class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="?page=sales&action=print&id=<?= $s['id'] ?>&thermal=1&autoprint=1"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="?page=sales&action=thermalPrint&id=<?= $s['id'] ?>&thermal=1&autoprint=1"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm" style="background:rgba(5,150,105,0.15);color:#059669;border:none;" title="Thermal Print">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a href="?page=sales&action=print&id=<?= $s['id'] ?>&autopdf=1"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:none;" title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <?php if (Auth::isAdmin() && $s['status'] !== 'cancelled'): ?>
                                <a href="?page=sales&action=edit&id=<?= $s['id'] ?>"
                                   class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('sales', 'delete') && $s['status'] !== 'cancelled'): ?>
                                <form method="POST" action="?page=sales&action=cancel" style="display:inline;">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm pin-protect" style="background:rgba(239,68,68,0.15);color:var(--danger);border:none;" title="Cancel">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Isolate the list so a 1px overflow cannot toggle scrollbars and shake the page. */
.card:has(#salesTable) .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
#salesTable {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.83rem;
}
#salesTable .sale-inv-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
    text-decoration: none;
}
#salesTable .sale-party {
    font-weight: 600;
    color: #1e293b;
}
#salesTable .sale-total {
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
}
#salesTable .sale-void-badge { font-size: 0.72rem; }
#salesTable .sale-date {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    background: #e0f2fe;
    color: #0369a1;
}
#salesTable tr.sale-row-today .sale-date {
    background: #dcfce7;
    color: #166534;
}
#salesCustomerFilter + .select2-container,
#salesItemFilter + .select2-container { width:100% !important; }
#salesCustomerFilter + .select2-container .select2-selection--single,
#salesItemFilter + .select2-container .select2-selection--single {
    min-height:38px;border:1.5px solid #c7d2fe;border-radius:10px;
}
#salesCustomerFilter + .select2-container .select2-selection__rendered,
#salesItemFilter + .select2-container .select2-selection__rendered {
    line-height:36px;padding-left:12px;font-size:0.85rem;
}
#salesCustomerFilter + .select2-container .select2-selection__arrow,
#salesItemFilter + .select2-container .select2-selection__arrow { height:36px !important; }
</style>
<script>
(function () {
    var form = document.getElementById('salesFilterForm');
    var datesTouched = false;
    if (form) {
        form.querySelectorAll('[name="from_date"],[name="to_date"]').forEach(function (el) {
            el.addEventListener('change', function () { datesTouched = true; });
        });
        form.addEventListener('submit', function () {
            var item = form.querySelector('[name="item"]');
            var party = form.querySelector('[name="party_id"]');
            var search = form.querySelector('[name="search"]');
            var hasEntity = (item && item.value) || (party && party.value) || (search && search.value.trim());
            if (hasEntity && !datesTouched) {
                var from = form.querySelector('[name="from_date"]');
                var to = form.querySelector('[name="to_date"]');
                if (from) from.value = '';
                if (to) to.value = '';
            }
        });
    }
    window.iqbalWhenIdle(function () {
        if (typeof jQuery === 'undefined') return;
        if (!(jQuery.fn && jQuery.fn.select2) || jQuery('#salesCustomerFilter').hasClass('select2-hidden-accessible')) {
            return;
        }
        var ajaxCommon = { dataType: 'json', delay: 250, cache: true };
        jQuery('#salesCustomerFilter').select2({
            placeholder: 'Search customer...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: Object.assign({}, ajaxCommon, {
                url: '?page=sales&action=searchParties',
                data: function (params) {
                    return { q: params.term || '', type: 'customer', balances: '0' };
                },
                processResults: function (data) {
                    var rows = Array.isArray(data) ? data : [];
                    return {
                        results: rows.map(function (p) {
                            return { id: p.id, text: p.name || ('#' + p.id) };
                        })
                    };
                }
            })
        });
        jQuery('#salesItemFilter').select2({
            placeholder: 'Search item by name or SKU...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: Object.assign({}, ajaxCommon, {
                url: '?page=sales&action=searchItems',
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    var rows = Array.isArray(data) ? data : [];
                    return {
                        results: rows.map(function (it) {
                            var label = it.name || ('#' + it.id);
                            if (it.sku) label += ' (' + it.sku + ')';
                            return { id: it.id, text: label };
                        })
                    };
                }
            })
        });
        jQuery('#salesCustomerFilter, #salesItemFilter').on('select2:select select2:clear', function () {
            if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    });
})();
</script>
