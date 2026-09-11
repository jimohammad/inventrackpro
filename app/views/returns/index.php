<!-- Returns List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Returns</h1>
        <p class="page-subtitle">Sale returns and purchase returns to suppliers</p>
    </div>
    <?php if (Auth::can('returns','add')): ?>
    <div class="d-flex gap-2">
        <a href="?page=returns&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Sale Return
        </a>
        <a href="?page=returns&action=purchaseCreate" class="btn btn-outline-primary" style="border-color:#b45309;color:#b45309;">
            <i class="bi bi-building me-1"></i> Purchase Return
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="GET" action="" id="returnsFilterForm" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="returns">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-tags me-1"></i>Type
            </label>
            <select name="type"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All Types</option>
                <option value="sale_return" <?= ($filters['type'] ?? '') === 'sale_return' ? 'selected' : '' ?>>Sale Return</option>
                <option value="purchase_return" <?= ($filters['type'] ?? '') === 'purchase_return' ? 'selected' : '' ?>>Purchase Return</option>
            </select>
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-person me-1"></i>Customer
            </label>
            <select name="party_id" id="returnsCustomerFilter"
                    style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All customers</option>
                <?php if (!empty($filterParty)): ?>
                <option value="<?= (int) $filterParty['id'] ?>" selected>
                    <?= htmlspecialchars($filterParty['name']) ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) $filters['from_date']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?: date('Y-m-d'))) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=returns"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="returnsTable">
                <thead>
                    <tr>
                        <th class="th-blue">Return No</th>
                        <th class="th-blue">Type</th>
                        <th class="th-blue">Date</th>
                        <th class="th-blue">Party</th>
                        <th class="th-blue text-end">Amount</th>
                        <th class="th-blue">Status</th>
                        <th class="th-blue">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No returns found</td></tr>
                    <?php else: ?>
                    <?php foreach ($returns as $r): ?>
                    <tr>
                        <td>
                            <a href="?page=returns&action=detail&id=<?= (int) $r['id'] ?>" class="ret-no">
                                <?= htmlspecialchars((string) ($r['return_no'] ?? '')) ?>
                            </a>
                        </td>
                        <td>
                            <?php if (($r['type'] ?? '') === 'purchase_return'): ?>
                            <span class="ret-type ret-type-purchase">Purchase</span>
                            <?php else: ?>
                            <span class="ret-type ret-type-sale">Sale</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ret-date"><?= date('m/d/Y, h:i A', strtotime($r['created_at'] ?? $r['date'])) ?></span>
                        </td>
                        <td class="ret-party"><?= htmlspecialchars($r['party_name']) ?></td>
                        <td class="ret-amt text-end"><?= APP_CURRENCY ?> <?= number_format($r['grand_total'], DECIMAL_PLACES) ?></td>
                        <td>
                            <span class="badge badge-<?= $r['status'] === 'approved' ? 'paid' : ($r['status']==='rejected'?'draft':($r['status']==='cancelled'?'draft':'pending')) ?> px-2 py-1" style="border-radius:6px;<?= $r['status']==='cancelled'?'opacity:0.75;':'' ?>">
                                <?= $r['status'] === 'cancelled' ? 'Voided' : ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                            <a href="?page=returns&action=detail&id=<?= $r['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="?page=returns&action=print&id=<?= $r['id'] ?>&autoprint=1" class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:#059669;border:none;" title="Print">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="?page=returns&action=print&id=<?= $r['id'] ?>&autopdf=1" class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:none;" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <?php if (Auth::isAdmin() && ($r['type'] ?? '') === 'sale_return' && $r['status'] !== 'cancelled'): ?>
                            <a href="?page=returns&action=edit&id=<?= $r['id'] ?>" class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (Auth::can('returns', 'delete') && ($r['status'] ?? '') === 'approved'): ?>
                            <form method="POST" action="?page=returns&action=cancel" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn btn-sm pin-protect" style="background:rgba(220,38,38,0.12);color:#dc2626;border:none;" title="Void return">
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
.card:has(#returnsTable) .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
#returnsTable {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.83rem;
}
#returnsTable.dataTable,
.card:has(#returnsTable) .dataTables_wrapper {
    font-size: 0.83rem;
}
#returnsTable.dataTable tbody td,
#returnsTable.dataTable thead th {
    font-size: inherit;
}
#returnsTable .ret-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
    text-decoration: none;
}
#returnsTable .ret-party {
    font-weight: 600;
    color: #1e293b;
}
#returnsTable .ret-amt {
    font-weight: 700;
    white-space: nowrap;
    color: #475569;
}
#returnsTable .ret-date {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    background: #e0f2fe;
    color: #0369a1;
}
#returnsTable .ret-type {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
}
#returnsTable .ret-type-sale {
    background: rgba(99,102,241,0.12);
    color: #6366f1;
}
#returnsTable .ret-type-purchase {
    background: rgba(245,158,11,0.15);
    color: #b45309;
}
#returnsCustomerFilter + .select2-container { width:100% !important; }
#returnsCustomerFilter + .select2-container .select2-selection--single {
    min-height:38px;border:1.5px solid #c7d2fe;border-radius:10px;
}
#returnsCustomerFilter + .select2-container .select2-selection__rendered {
    line-height:36px;padding-left:12px;font-size:0.85rem;
}
#returnsCustomerFilter + .select2-container .select2-selection__arrow { height:36px !important; }
</style>
<script>
(function () {
    var form = document.getElementById('returnsFilterForm');
    var customer = document.getElementById('returnsCustomerFilter');
    var submitting = false;
    function submitReturnsFilter() {
        if (!form || submitting) return;
        submitting = true;
        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
    }
    window.iqbalWhenIdle(function () {
        if (typeof jQuery === 'undefined') return;
        if (jQuery.fn && jQuery.fn.DataTable && !jQuery.fn.DataTable.isDataTable('#returnsTable')) {
            jQuery('#returnsTable').DataTable({ pageLength:25, order:[[2,'desc']], deferRender:true, searching:false, lengthChange:false, columnDefs:[{orderable:false,targets:[6]}] });
        }
        if (jQuery.fn && jQuery.fn.select2 && customer && !jQuery(customer).hasClass('select2-hidden-accessible')) {
            jQuery(customer).select2({
                placeholder: 'Search customer...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: '?page=sales&action=searchParties',
                    dataType: 'json',
                    delay: 250,
                    cache: true,
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
                }
            });
            jQuery(customer).on('select2:select select2:clear', submitReturnsFilter);
        }
    });
})();
</script>
