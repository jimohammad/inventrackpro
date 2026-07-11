<!-- Purchases List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Purchases</h1></div>
    <?php if (Auth::can('purchases','add')): ?>
    <a href="?page=purchases&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Purchase</a>
    <?php endif; ?>
</div>


<!-- Filters -->
<form method="GET" action="" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
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
                <?php foreach (($allItems ?? []) as $it): ?>
                <option value="<?= (int)$it['id'] ?>" <?= ((string)($filters['item'] ?? '') === (string)$it['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($it['name']) ?><?= !empty($it['sku']) ? ' (' . htmlspecialchars($it['sku']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
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
$listDateDefaultLabel = 'last two months by default';
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
                        <th class="th-blue">Invoice No</th><th class="th-blue">Date</th><th class="th-blue">Supplier</th><th class="th-blue">Warehouse</th>
                        <th class="th-blue text-end">Total</th><th class="th-blue text-end">Paid</th>
                        <th class="th-blue text-end">Balance</th><th class="th-blue">Status</th><th class="th-blue text-center">IMEI</th><th class="th-blue">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No purchases found</td></tr>
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
                        <td><a href="?page=purchases&action=detail&id=<?= $p['id'] ?>" style="color:var(--primary);font-weight:600;text-decoration:none;"><?= $p['invoice_no'] ?></a></td>
                        <td><span style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;white-space:nowrap;"><?= date('m/d/Y, h:i A', strtotime($p['created_at'] ?? $p['date'])) ?></span></td>
                        <td><?= htmlspecialchars($p['party_name']) ?></td>
                        <td><?= htmlspecialchars($p['warehouse_name'] ?? '—') ?></td>
                        <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format($p['grand_total'], DECIMAL_PLACES) ?></td>
                        <td class="text-end" style="color:var(--success);"><?= APP_CURRENCY ?> <?= number_format($p['paid_amount'], DECIMAL_PLACES) ?></td>
                        <td class="text-end" style="color:<?= $p['balance']>0?'var(--warning)':'var(--success)' ?>;"><?= APP_CURRENCY ?> <?= number_format($p['balance'], DECIMAL_PLACES) ?></td>
                        <td><span class="badge badge-<?= $p['status'] ?> px-2" style="border-radius:6px;"><?= ucfirst($p['status']) ?></span></td>
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
                                <?php if ($showImeiScanBtn): ?>
                                <a href="?page=purchases&action=imeiScan&id=<?= (int) $p['id'] ?>"
                                   class="btn btn-sm pur-imei-scan-btn"
                                   title="Scan <?= $imeiPending ?> remaining IMEI(s)">
                                    <i class="bi bi-upc-scan"></i>
                                </a>
                                <?php endif; ?>
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
.pur-imei-scan-btn{
    background:linear-gradient(135deg,#f59e0b,#d97706) !important;
    color:#fff !important;border:none !important;font-weight:700;
}
.pur-imei-scan-btn:hover{
    background:linear-gradient(135deg,#d97706,#b45309) !important;
    color:#fff !important;
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
    var ready = setInterval(function () {
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
            clearInterval(ready);
            jQuery('#purchaseItemFilter').select2({
                placeholder: 'Search item by name or SKU...',
                allowClear: true,
                width: '100%'
            });
        }
    }, 60);
})();
</script>
<script>$(document).ready(() => { $('#purchasesTable').DataTable({ pageLength:25, order:[[1,'desc']], columnDefs:[{orderable:false,targets:[8,9]}], language:{search:'',searchPlaceholder:'Search...'} }); });</script>
