<!-- Purchases List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Purchases</h1></div>
    <?php if (Auth::can('purchases','add')): ?>
    <a href="?page=purchases&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Purchase</a>
    <?php endif; ?>
</div>


<!-- Filters -->
<form method="GET" action="" id="purchasesFilterForm" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="purchases">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Invoice no, supplier..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #c7d2fe;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#c7d2fe'">
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-box-seam me-1"></i>Item
            </label>
            <select name="item" id="purchaseItemFilter"
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
                <option value="">All Status</option>
                <option value="confirmed" <?= $filters['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="partial"   <?= $filters['status']==='partial'?'selected':'' ?>>Partial</option>
                <option value="paid"      <?= $filters['status']==='paid'?'selected':'' ?>>Paid</option>
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

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(99,102,241,0.3);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=purchases"
               style="padding:8px 16px;background:#fff;color:#64748b;border:1.5px solid #c7d2fe;border-radius:10px;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all 0.15s;"
               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#c7d2fe'">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<?php
$listPageName = 'purchases';
$listHideDateDefaultAlert = true;
$listPageExtra = [];
if (($filters['status'] ?? '') !== '') {
    $listPageExtra['status'] = (string) $filters['status'];
}
if (($filters['search'] ?? '') !== '') {
    $listPageExtra['search'] = (string) $filters['search'];
}
if (($filters['item'] ?? '') !== '') {
    $listPageExtra['item'] = (string) $filters['item'];
}
include __DIR__ . '/../partials/list_page_alerts.php';
?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="purchasesTable">
                <thead>
                    <tr>
                        <th class="th-blue">Invoice No</th><th class="th-blue">Date</th><th class="th-blue">Supplier</th>
                        <th class="th-blue text-end">Total</th><th class="th-blue text-end">Paid</th>
                        <th class="th-blue text-end">Balance</th><th class="th-blue">Status</th><th class="th-blue text-center">IMEI</th><th class="th-blue">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No purchases found</td></tr>
                    <?php else: ?>
                    <?php foreach ($purchases as $p):
                        $imeiScannable = (int) ($p['imei_scannable_qty'] ?? 0);
                        $imeiPending   = (int) ($p['imei_pending_qty'] ?? 0);
                        $imeiComplete  = $imeiScannable > 0 && $imeiPending === 0;
                        $showImeiScanBtn = $imeiPending > 0
                            && ($p['status'] ?? '') !== 'cancelled'
                            && Auth::can('purchases', 'edit');
                    ?>
                    <tr>
                        <td>
                            <a href="?page=purchases&action=detail&id=<?= $p['id'] ?>" class="pur-inv-no">
                                <?= htmlspecialchars((string) $p['invoice_no']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="pur-date"><?= date('m/d/Y, h:i A', strtotime($p['created_at'] ?? $p['date'])) ?></span>
                        </td>
                        <td class="pur-party"><?= htmlspecialchars($p['party_name']) ?></td>
                        <td class="text-end pur-amt"><?= APP_CURRENCY ?> <?= number_format($p['grand_total'], DECIMAL_PLACES) ?></td>
                        <td class="text-end pur-amt pur-paid"><?= APP_CURRENCY ?> <?= number_format($p['paid_amount'], DECIMAL_PLACES) ?></td>
                        <td class="text-end pur-amt" style="color:<?= $p['balance']>0?'var(--warning)':'var(--success)' ?>;"><?= APP_CURRENCY ?> <?= number_format($p['balance'], DECIMAL_PLACES) ?></td>
                        <td><span class="badge badge-<?= $p['status'] ?> px-2 pur-status"><?= ucfirst($p['status']) ?></span></td>
                        <td class="text-center">
                            <?php if ($imeiComplete): ?>
                            <span class="pur-imei-done" title="100% IMEI scanning completed">
                                <i class="bi bi-check-circle-fill"></i>
                            </span>
                            <?php elseif ($imeiScannable > 0): ?>
                            <?php if ($showImeiScanBtn): ?>
                            <a href="?page=purchases&action=imeiScan&id=<?= (int) $p['id'] ?>"
                               class="pur-imei-pending pur-imei-pending-link"
                               title="Scan <?= $imeiPending ?> remaining IMEI(s)">
                                <i class="bi bi-upc-scan"></i>
                            </a>
                            <?php else: ?>
                            <span class="pur-imei-pending" title="<?= $imeiPending ?> IMEI(s) still to scan">
                                <i class="bi bi-upc-scan"></i>
                            </span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:0.75rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=purchases&action=detail&id=<?= $p['id'] ?>" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="?page=purchases&action=thermalPrint&id=<?= $p['id'] ?>&thermal=1&autoprint=1"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <?php if (Auth::isAdmin() && $p['status'] !== 'cancelled'): ?>
                                <a href="?page=purchases&action=edit&id=<?= $p['id'] ?>"
                                   class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('purchases','delete') && $p['status'] !== 'cancelled'): ?>
                                <form method="POST" action="?page=purchases&action=cancel" style="display:inline;"
                                      onsubmit="return confirm('Cancel this purchase? Stock and linked payments will be reversed.');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm pin-protect"
                                            style="background:rgba(239,68,68,0.15);color:#dc2626;border:none;" title="Delete">
                                        <i class="bi bi-trash"></i>
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
.card:has(#purchasesTable) .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
#purchasesTable {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.83rem;
}
#purchasesTable .pur-inv-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
    text-decoration: none;
}
#purchasesTable .pur-party {
    font-weight: 600;
    color: #1e293b;
}
#purchasesTable .pur-amt {
    font-weight: 700;
    white-space: nowrap;
}
#purchasesTable .pur-paid { color: var(--success); }
#purchasesTable .pur-status { border-radius: 6px; }
#purchasesTable .pur-date {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.pur-imei-done{
    display:inline-flex;align-items:center;justify-content:center;
    width:28px;height:28px;border-radius:50%;
    background:rgba(16,185,129,0.14);color:#059669;font-size:1.05rem;
}
.pur-imei-pending{
    display:inline-flex;align-items:center;justify-content:center;
    width:28px;height:28px;border-radius:50%;
    background:rgba(245,158,11,0.14);color:#d97706;font-size:0.9rem;
}
.pur-imei-pending-link{
    text-decoration:none;transition:transform 0.15s,box-shadow 0.15s;
}
.pur-imei-pending-link:hover{
    transform:scale(1.08);box-shadow:0 2px 8px rgba(245,158,11,0.35);color:#b45309;
}
#purchaseItemFilter + .select2-container { width:100% !important; }
#purchaseItemFilter + .select2-container .select2-selection--single {
    height:38px !important; border:1.5px solid #c7d2fe !important; border-radius:10px !important; background:#fff !important;
}
#purchaseItemFilter + .select2-container .select2-selection__rendered {
    line-height:36px !important; padding-left:14px !important; font-size:0.85rem !important; color:#1e293b !important;
}
#purchaseItemFilter + .select2-container .select2-selection__arrow { height:36px !important; }
#purchaseItemFilter + .select2-container--default.select2-container--focus .select2-selection--single {
    border-color:#6366f1 !important;
}
</style>
<script>
(function () {
    var form = document.getElementById('purchasesFilterForm');
    var datesTouched = false;
    if (form) {
        form.querySelectorAll('[name="from_date"],[name="to_date"]').forEach(function (el) {
            el.addEventListener('change', function () { datesTouched = true; });
        });
        form.addEventListener('submit', function () {
            var item = form.querySelector('[name="item"]');
            var search = form.querySelector('[name="search"]');
            var hasEntity = (item && item.value) || (search && search.value.trim());
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
        if (jQuery.fn && jQuery.fn.select2 && !jQuery('#purchaseItemFilter').hasClass('select2-hidden-accessible')) {
            jQuery('#purchaseItemFilter').select2({
                placeholder: 'Search item by name or SKU...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: '?page=sales&action=searchItems',
                    dataType: 'json',
                    delay: 250,
                    cache: true,
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
                }
            });
            jQuery('#purchaseItemFilter').on('select2:select select2:clear', function () {
                if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
            });
        }
    });
})();
</script>
