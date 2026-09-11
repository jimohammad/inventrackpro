<!-- Purchase Orders List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Purchase Orders</h1>
    </div>
    <a href="?page=purchaseorders&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Purchase Order
    </a>
</div>

<!-- Filters -->
<form method="GET" action="" id="poFilterForm" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="purchaseorders">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-person me-1"></i>Supplier
            </label>
            <select name="party_id" id="poSupplierFilter"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All suppliers</option>
                <?php if (!empty($filterSupplier)): ?>
                <option value="<?= (int) $filterSupplier['id'] ?>" selected>
                    <?= htmlspecialchars($filterSupplier['name']) ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-box-seam me-1"></i>Item
            </label>
            <select name="item" id="poItemFilter"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All items</option>
                <?php if (!empty($filterItem)): ?>
                <option value="<?= (int) $filterItem['id'] ?>" selected>
                    <?= htmlspecialchars($filterItem['name']) ?><?= ($filterItem['sku'] ?? '') !== '' ? ' (' . htmlspecialchars($filterItem['sku']) . ')' : '' ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-funnel me-1"></i>Status
            </label>
            <select name="status"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
                <option value="">All</option>
                <option value="draft"     <?= ($status??'')==='draft'     ?'selected':'' ?>>Draft</option>
                <option value="paid"      <?= ($status??'')==='paid'      ?'selected':'' ?>>Paid — Awaiting Goods</option>
                <option value="converted" <?= ($status??'')==='converted' ?'selected':'' ?>>Converted</option>
                <option value="cancelled" <?= ($status??'')==='cancelled' ?'selected':'' ?>>Cancelled</option>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From <span style="font-weight:600;opacity:0.65;">optional</span>
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) ($fromDate ?? '')) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To <span style="font-weight:600;opacity:0.65;">optional</span>
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($toDate ?? '')) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=purchaseorders"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<?php
$poHasEntity = ((int) ($partyId ?? 0) > 0) || (($itemQ ?? '') !== '');
$poHasDates  = (($fromDate ?? '') !== '') || (($toDate ?? '') !== '');
if ($poHasEntity && !$poHasDates):
?>
<div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" role="status">
    <i class="bi bi-calendar-range"></i>
    <span class="small mb-0">Showing all dates for this <?= ((int) ($partyId ?? 0) > 0) ? 'supplier' : 'item' ?>. Add a date range and click Filter to narrow.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size:2.5rem;color:#cbd5e1;"></i>
            <p class="mt-3 mb-0" style="color:#94a3b8;">No purchase orders found.</p>
            <a href="?page=purchaseorders&action=create" class="btn btn-primary mt-3">Create First PO</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="po-list-table">
            <colgroup>
                <col class="col-po">
                <col class="col-docs">
                <col class="col-date">
                <col class="col-supplier">
                <col class="col-ref">
                <col class="col-ccy">
                <col class="col-amt">
                <col class="col-kwd">
                <col class="col-status">
                <col class="col-act">
            </colgroup>
            <thead>
                <tr>
                    <th>PO No</th>
                    <th class="is-center" title="Documents"><i class="bi bi-paperclip"></i> Docs</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Ref</th>
                    <th class="is-center" title="Currency">Currency</th>
                    <th class="is-num">Amount</th>
                    <th class="is-num">KWD</th>
                    <th class="is-center">Status</th>
                    <th class="is-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <?php
                $stColor = match($o['status']) {
                    'draft'     => ['#e0e7ff','#3730a3','Draft'],
                    'paid'      => ['#fef3c7','#92400e','Paid — Awaiting'],
                    'converted' => ['#d1fae5','#065f46','Converted'],
                    'cancelled' => ['#f1f5f9','#94a3b8','Cancelled'],
                    default     => ['#f1f5f9','#64748b',$o['status']],
                };
                ?>
                <?php
                $hasInvoice = !empty($o['has_invoice_doc']);
                $hasTt      = !empty($o['has_tt_doc']);
                    $docCount   = (int) ($o['doc_count'] ?? 0);
                    $poStatus   = (string) ($o['status'] ?? '');
                    $canAttach  = !in_array($poStatus, ['cancelled'], true);
                    $docsComplete = $hasInvoice && $hasTt;
                    if ($docsComplete) {
                        $docColor = '#059669';
                        $docTitle = 'Invoice + TT Copy uploaded — open to view or add more';
                    } elseif ($docCount > 0) {
                        $docColor = '#d97706';
                        $parts = [];
                        if ($hasInvoice) { $parts[] = 'Invoice'; }
                        if ($hasTt) { $parts[] = 'TT Copy'; }
                        $docTitle = 'Uploaded: ' . implode(' · ', $parts) . ' (incomplete) — click to attach';
                    } elseif ($canAttach) {
                        // Open / ordered POs: look active so staff know they can attach before convert
                        $docColor = '#6366f1';
                        $docTitle = 'Attach Supplier Invoice / TT Copy';
                    } else {
                        $docColor = '#cbd5e1';
                        $docTitle = 'No documents (cancelled)';
                    }
                    ?>
                    <tr>
                        <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;">
                            <a href="?page=purchaseorders&action=show&id=<?= $o['id'] ?>"
                               style="font-size:0.8rem;font-weight:700;color:#6366f1;text-decoration:none;">
                                <?= htmlspecialchars($o['po_no']) ?>
                            </a>
                        </td>
                        <td style="padding:9px 8px;border-bottom:1px solid #cbd5e1;text-align:center;">
                            <?php if ($docCount > 0): ?>
                            <a href="?page=purchaseorders&action=show&id=<?= (int) $o['id'] ?>#poDocumentsCard"
                               title="<?= htmlspecialchars($docTitle) ?>"
                               style="color:<?= $docColor ?>;font-size:0.95rem;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:3px;min-width:28px;min-height:28px;padding:0 7px;border-radius:8px;background:<?= $docsComplete ? '#d1fae5' : '#ffedd5' ?>;">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <span style="font-size:0.68rem;font-weight:700;"><?= $docCount ?></span>
                            </a>
                            <?php else: ?>
                            <a href="?page=purchaseorders&action=show&id=<?= (int) $o['id'] ?>#poDocumentsCard"
                               title="<?= htmlspecialchars($docTitle) ?>"
                               style="color:<?= $docColor ?>;font-size:1.05rem;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;min-width:28px;min-height:28px;border-radius:8px;<?= $canAttach ? 'background:#eef2ff;' : '' ?>">
                                <i class="bi bi-paperclip"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($o['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;">
                        <?= htmlspecialchars($o['supplier_name']) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#94a3b8;font-size:0.78rem;">
                        <?= htmlspecialchars($o['supplier_ref'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <span style="background:<?= $o['currency']==='AED' ? '#dbeafe' : '#fef9c3' ?>;color:<?= $o['currency']==='AED' ? '#1d4ed8' : '#854d0e' ?>;padding:2px 9px;border-radius:6px;font-size:0.72rem;font-weight:700;">
                            <?= $o['currency'] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#f59e0b;">
                        <?= number_format($o['subtotal_foreign'], DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;color:#475569;">
                        <?php
                        $listKwd = (float)$o['subtotal_kwd'] + (float)($o['other_charges_kwd'] ?? 0) + (float)($o['adjustment_kwd'] ?? 0);
                        $listForeign = (($o['currency'] ?? 'KWD') !== 'KWD') && $listKwd <= 0.001;
                        echo $listForeign ? '—' : number_format($listKwd, DECIMAL_PLACES) . ' KWD';
                        ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <span style="background:<?= $stColor[0] ?>;color:<?= $stColor[1] ?>;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;">
                            <?= $stColor[2] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:center;">
                        <a href="?page=purchaseorders&action=show&id=<?= $o['id'] ?>"
                           style="color:#6366f1;font-size:1rem;margin:0 4px;" title="View"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::isAdmin() && $o['status'] !== 'converted'): ?>
                        <a href="?page=purchaseorders&action=edit&id=<?= $o['id'] ?>"
                           style="color:#f59e0b;font-size:1rem;margin:0 4px;" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.po-list-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
    font-size: 0.83rem;
}
.po-list-table col.col-po { width: 11%; }
.po-list-table col.col-docs { width: 6%; }
.po-list-table col.col-date { width: 10%; }
.po-list-table col.col-supplier { width: 17%; }
.po-list-table col.col-ref { width: 10%; }
.po-list-table col.col-ccy { width: 8%; }
.po-list-table col.col-amt { width: 10%; }
.po-list-table col.col-kwd { width: 10%; }
.po-list-table col.col-status { width: 12%; }
.po-list-table col.col-act { width: 6%; }
.po-list-table thead th {
    background: rgba(37, 99, 235, 0.12);
    color: #1e3a8a;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 12px;
    border: 0;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    vertical-align: middle;
    text-align: left;
}
.po-list-table thead th + th {
    box-shadow: inset 1px 0 0 rgba(30, 58, 138, 0.08);
}
.po-list-table thead th.is-center { text-align: center; }
.po-list-table thead th.is-num { text-align: right; }
.po-list-table thead th i {
    font-size: 0.82rem;
    opacity: 0.75;
    margin-right: 2px;
    vertical-align: -1px;
}
.po-list-table thead th:first-child { padding-left: 16px; }
.po-list-table thead th:last-child { padding-right: 16px; }
.po-list-table tbody tr { background: #fff; }
.po-list-table tbody tr:hover { background: #f8faff; }
.po-list-table tbody td {
    overflow: hidden;
    text-overflow: ellipsis;
}
#poSupplierFilter + .select2-container,
#poItemFilter + .select2-container { width:100% !important; }
#poSupplierFilter + .select2-container .select2-selection--single,
#poItemFilter + .select2-container .select2-selection--single {
    height:38px !important; border:1.5px solid #c7d2fe !important; border-radius:10px !important; background:#fff !important;
}
#poSupplierFilter + .select2-container .select2-selection__rendered,
#poItemFilter + .select2-container .select2-selection__rendered {
    line-height:36px !important; padding-left:14px !important; font-size:0.85rem !important; color:#1e293b !important;
}
#poSupplierFilter + .select2-container .select2-selection__arrow,
#poItemFilter + .select2-container .select2-selection__arrow { height:36px !important; }
#poSupplierFilter + .select2-container--default.select2-container--focus .select2-selection--single,
#poItemFilter + .select2-container--default.select2-container--focus .select2-selection--single {
    border-color:#6366f1 !important;
}
</style>
<script>
(function () {
    var form = document.getElementById('poFilterForm');
    var datesTouched = false;
    if (form) {
        form.querySelectorAll('[name="from_date"],[name="to_date"]').forEach(function (el) {
            el.addEventListener('change', function () { datesTouched = true; });
        });
        form.addEventListener('submit', function () {
            var party = form.querySelector('[name="party_id"]');
            var item = form.querySelector('[name="item"]');
            var hasEntity = (party && party.value) || (item && item.value);
            if (hasEntity && !datesTouched) {
                var from = form.querySelector('[name="from_date"]');
                var to = form.querySelector('[name="to_date"]');
                if (from) from.value = '';
                if (to) to.value = '';
            }
        });
    }

    window.iqbalWhenIdle(function () {
        if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) return;
        if (jQuery('#poSupplierFilter').hasClass('select2-hidden-accessible')) return;
        var ajaxCommon = { dataType: 'json', delay: 250, cache: true };
        jQuery('#poSupplierFilter').select2({
            placeholder: 'Search supplier...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: Object.assign({}, ajaxCommon, {
                url: '?page=sales&action=searchParties',
                data: function (params) {
                    return { q: params.term || '', type: 'purchase', balances: '0' };
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
        jQuery('#poItemFilter').select2({
            placeholder: 'Search item by name or SKU...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: Object.assign({}, ajaxCommon, {
                url: '?page=sales&action=searchItems',
                data: function (params) {
                    return { q: params.term || '', stock: '0' };
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
        jQuery('#poSupplierFilter, #poItemFilter').on('select2:select select2:clear', function () {
            if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    });
})();
</script>
