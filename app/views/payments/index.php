<style>
.pay-list, .pay-list *,
.pay-list .select2-container .select2-selection--single,
.pay-list .select2-container .select2-selection,
.select2-dropdown, .select2-search--dropdown .select2-search__field {
    border-radius: 0 !important;
}
</style>
<div class="pay-list">
<?php
    $paymentsListMode   = $paymentsListMode ?? 'in';
    $isOutList          = $paymentsListMode === 'out';
    $paymentsListBase   = $paymentsListBase ?? ($isOutList ? '?page=payments&action=out' : '?page=payments');
    $paymentsPermModule = $paymentsPermModule ?? ($isOutList ? 'payments_out' : 'payments');
    $pageHeading        = $isOutList ? 'Payment Out' : 'Payment In';
?>
<!-- Payments List (In or Out — same engine, filtered) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($pageHeading) ?></h1>
    </div>
    <?php if (Auth::can($paymentsPermModule, 'add')): ?>
    <div style="display:flex;gap:8px;">
        <?php if ($isOutList): ?>
        <a href="?page=payments&action=pay"
           style="display:inline-flex;align-items:center;gap:6px;height:48px;padding:0 16px;border-radius:0;background:#dc2626;color:#fff;font-size:.88rem;font-weight:700;text-decoration:none;">
            <i class="bi bi-arrow-up-circle-fill"></i> Make Payment
        </a>
        <?php else: ?>
        <a href="?page=payments&action=receive"
           style="display:inline-flex;align-items:center;gap:6px;height:48px;padding:0 16px;border-radius:0;background:#059669;color:#fff;font-size:.88rem;font-weight:700;text-decoration:none;">
            <i class="bi bi-arrow-down-circle-fill"></i> Receive Payment
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>


<!-- Filters -->
<form method="GET" action="" id="paymentsFilterForm" style="background:<?= $isOutList ? '#fef2f2' : '#eef2ff' ?>;border:1px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="payments">
    <?php if ($isOutList): ?>
    <input type="hidden" name="action" value="out">
    <?php endif; ?>
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Payment no..."
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
        </div>

        <div style="flex:2;min-width:180px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-person me-1"></i>Party
            </label>
            <select name="party_id" id="paymentPartyFilter"
                    style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All parties</option>
                <?php if (!empty($filterParty)): ?>
                <option value="<?= (int) $filterParty['id'] ?>" selected>
                    <?= htmlspecialchars($filterParty['name']) ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <?php if ($isOutList): ?>
        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-funnel me-1"></i>Type
            </label>
            <select name="ref_type"
                    style="width:100%;height:48px;padding:0 14px;border:1.5px solid #fecaca;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All Types</option>
                <option value="purchase" <?= $filters['ref_type']==='purchase'?'selected':'' ?>>Purchases</option>
                <option value="purchase_order" <?= $filters['ref_type']==='purchase_order'?'selected':'' ?>>PO Advances</option>
                <option value="expense"  <?= $filters['ref_type']==='expense'?'selected':'' ?>>Expenses</option>
            </select>
        </div>
        <?php endif; ?>

        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-wallet2 me-1"></i>Account
            </label>
            <select name="account_id"
                    style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All accounts</option>
                <?php foreach (($accounts ?? []) as $acc): ?>
                <?php
                    $accName = trim((string) ($acc['name'] ?? ''));
                    if ($accName === '') {
                        $accName = 'Account #' . (int) ($acc['id'] ?? 0);
                    }
                    if (isset($acc['is_active']) && (int) $acc['is_active'] !== 1) {
                        $accName .= ' [inactive]';
                    }
                ?>
                <option value="<?= (int) $acc['id'] ?>" <?= ((int) ($filters['account_id'] ?? 0) === (int) $acc['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($accName) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex:1;min-width:150px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-person-badge me-1"></i>By
            </label>
            <select name="created_by"
                    style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
                <option value="">All users</option>
                <?php foreach (($users ?? []) as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= ((int) ($filters['created_by'] ?? 0) === (int) $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>From
            </label>
            <input type="date" name="from_date" value="<?= htmlspecialchars((string) $filters['from_date']) ?>"
                   style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
        </div>

        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:<?= $isOutList ? '#dc2626' : '#6366f1' ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-calendar3 me-1"></i>To
            </label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?>"
                   style="width:100%;height:48px;padding:0 14px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit"
                    style="height:48px;padding:0 22px;background:<?= $isOutList ? '#dc2626' : '#4f46e5' ?>;color:#fff;border:none;border-radius:0;font-weight:700;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="<?= htmlspecialchars($paymentsListBase) ?>"
               style="height:48px;padding:0 16px;background:#fff;color:#64748b;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;font-weight:600;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </div>

    </div>
</form>

<?php
$listPageName = 'payments';
$listHideDateDefaultAlert = true;
$listPageExtra = [];
if ($isOutList) {
    $listPageExtra['action'] = 'out';
}
if (($filters['ref_type'] ?? '') !== '') {
    $listPageExtra['ref_type'] = (string) $filters['ref_type'];
}
if (($filters['search'] ?? '') !== '') {
    $listPageExtra['search'] = (string) $filters['search'];
}
if (!empty($filters['party_id'])) {
    $listPageExtra['party_id'] = (int) $filters['party_id'];
}
if (!empty($filters['created_by'])) {
    $listPageExtra['created_by'] = (int) $filters['created_by'];
}
if (!empty($filters['account_id'])) {
    $listPageExtra['account_id'] = (int) $filters['account_id'];
}
include __DIR__ . '/../partials/list_page_alerts.php';
?>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="paymentsTable">
                <thead>
                    <tr>
                        <th class="th-blue">Payment No</th>
                        <th class="th-blue">Date</th>
                        <th class="th-blue">Party</th>
                        <?php if ($isOutList): ?><th class="th-blue">Type</th><?php endif; ?>
                        <th class="th-blue">Account</th>
                        <th class="th-blue">Amount</th>
                        <th class="th-blue">By</th>
                        <th class="th-blue">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="<?= $isOutList ? 8 : 7 ?>" class="text-center text-muted py-5">No payments found</td></tr>
                    <?php else: ?>
                    <?php
                    $todayYmd = date('Y-m-d');
                    foreach ($payments as $p):
                        $payDay  = substr(trim((string) ($p['date'] ?? '')), 0, 10);
                        $isToday = $payDay === $todayYmd;
                    ?>
                    <tr<?= $isToday ? ' class="pay-row-today"' : '' ?>>
                        <td data-order="<?= (int) substr((string) ($p['payment_no'] ?? ''), 4) ?>">
                            <a href="?page=payments&action=detail&id=<?= $p['id'] ?>"
                               class="pay-no">
                                <?= htmlspecialchars((string) $p['payment_no']) ?>
                            </a>
                        </td>
                        <td data-order="<?= htmlspecialchars((string) ($p['created_at'] ?? '')) ?>"><span class="pay-date"><?= date('m/d/Y', strtotime($p['date'])) ?>, <?= date('h:i A', strtotime($p['created_at'])) ?></span></td>
                        <td class="pay-party"><?= htmlspecialchars($p['party_name'] ?? '—') ?></td>
                        <?php if ($isOutList): ?>
                        <td>
                            <?php
                            $typeBadges = [
                                'sale'     => ['bg' => 'rgba(99,102,241,0.12)', 'color' => '#6366f1'],
                                'purchase'       => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b'],
                                'purchase_order' => ['bg' => 'rgba(14,165,233,0.14)', 'color' => '#0284c7'],
                                'expense'        => ['bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6'],
                                'discount' => ['bg' => 'rgba(236,72,153,0.12)', 'color' => '#ec4899'],
                            ];
                            $tb = $typeBadges[$p['ref_type']] ?? ['bg' => 'rgba(100,116,139,0.12)', 'color' => '#64748b'];
                            $typeLabel = match ((string) ($p['ref_type'] ?? '')) {
                                'purchase_order' => 'PO Advance',
                                default => ucfirst((string) ($p['ref_type'] ?? '')),
                            };
                            ?>
                            <span class="badge" style="background:<?= $tb['bg'] ?>;color:<?= $tb['color'] ?>;">
                                <?= htmlspecialchars($typeLabel) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($p['account_name'] ?? '—') ?></td>
                        <td class="pay-amt">
                            <?php $isOut = ($p['payment_type'] ?? 'in') === 'out'; ?>
                            <span style="color:<?= $isOut ? '#dc2626' : '#059669' ?>;">
                                <i class="bi bi-arrow-<?= $isOut ? 'up' : 'down' ?>-circle-fill"
                                   style="font-size:0.85rem;margin-right:3px;"></i>
                                <?= APP_CURRENCY ?> <?= number_format($p['amount'], DECIMAL_PLACES) ?>
                            </span>
                        </td>
                        <td class="pay-by"><?= htmlspecialchars($p['created_by_name'] ?? '—') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=payments&action=detail&id=<?= $p['id'] ?>"
                                   class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:var(--primary);border:none;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="?page=payments&action=print&id=<?= $p['id'] ?>&autoprint=1"
                                   class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="?page=payments&action=thermalPrint&id=<?= $p['id'] ?>&thermal=1&autoprint=1"
                                   class="btn btn-sm" style="background:rgba(5,150,105,0.16);color:#047857;border:none;" title="Thermal Print">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a href="?page=payments&action=print&id=<?= $p['id'] ?>&autopdf=1"
                                   class="btn btn-sm" style="background:rgba(220,38,38,0.15);color:#dc2626;border:none;" title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <?php if (Auth::isAdmin() && ($p['ref_type'] ?? '') !== 'discount' && ($p['ref_type'] ?? '') !== 'purchase_order'): ?>
                                <a href="?page=payments&action=edit&id=<?= $p['id'] ?>"
                                   class="btn btn-sm pin-protect" style="background:rgba(245,158,11,0.15);color:#d97706;border:none;" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can($paymentsPermModule, 'delete') && ($p['ref_type'] ?? '') !== 'discount' && ($p['ref_type'] ?? '') !== 'purchase_order'): ?>
                                <form method="POST" action="?page=payments&action=delete" class="js-payment-delete" style="display:inline;">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm pin-protect" style="background:rgba(239,68,68,0.15);color:#dc2626;border:none;" title="Delete">
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
</div>

<style>
.card:has(#paymentsTable) .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
#paymentsTable {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.83rem;
}
#paymentsTable .pay-no {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
    text-decoration: none;
}
#paymentsTable .pay-party {
    font-weight: 600;
    color: #1e293b;
}
#paymentsTable .pay-amt {
    font-weight: 700;
    white-space: nowrap;
}
#paymentsTable .pay-by {
    color: #64748b;
}
#paymentsTable .pay-date {
    padding: 4px 10px;
    border-radius: 0;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    background: #e0f2fe;
    color: #0369a1;
}
#paymentsTable tr.pay-row-today .pay-date {
    background: #dcfce7;
    color: #166534;
}
#paymentPartyFilter + .select2-container { width:100% !important; }
#paymentPartyFilter + .select2-container .select2-selection--single {
    min-height:48px;height:48px;border:1.5px solid <?= $isOutList ? '#fecaca' : '#c7d2fe' ?>;border-radius:0;
}
#paymentPartyFilter + .select2-container .select2-selection__rendered {
    line-height:46px;padding-left:12px;font-size:0.85rem;
}
#paymentPartyFilter + .select2-container .select2-selection__arrow { height:46px !important; }
</style>
<script>
(function () {
    document.querySelectorAll('form.js-payment-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Delete this payment permanently? Account and invoice balances will be reversed.')) {
                e.preventDefault();
            }
        });
    });
    window.iqbalWhenIdle(function () {
        if (typeof jQuery === 'undefined') return;
        if (jQuery.fn && jQuery.fn.select2 && !jQuery('#paymentPartyFilter').hasClass('select2-hidden-accessible')) {
            var partySearchType = <?= json_encode($isOutList ? 'payment_out' : 'customer') ?>;
            jQuery('#paymentPartyFilter').select2({
                placeholder: <?= json_encode($isOutList ? 'Search party...' : 'Search customer...') ?>,
                allowClear: true,
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: '?page=sales&action=searchParties',
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: function (params) {
                        return { q: params.term || '', type: partySearchType, balances: '0' };
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
            jQuery('#paymentPartyFilter').on('select2:select select2:clear', function () {
                var form = document.getElementById('paymentsFilterForm');
                if (form) {
                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                }
            });
        }
    });
})();
</script>
