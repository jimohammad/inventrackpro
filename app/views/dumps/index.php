<?php
$search    = $search ?? '';
$fromDate  = $fromDate ?? '';
$toDate    = $toDate ?? '';
$dumps     = $dumps ?? [];
$datesDefaulted = $datesDefaulted ?? false;
$listPageName = $listPageName ?? 'dumps';
$listDateDefaultLabel = $listDateDefaultLabel ?? 'last two months by default';
$listTruncated = $listTruncated ?? false;
$listLimit = $listLimit ?? ListPage::MAX_ROWS;
$filters = [
    'from_date' => $fromDate,
    'to_date'   => $toDate,
    'all_dates' => !empty($dateRange['all_dates']),
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="page-title"><i class="bi bi-recycle me-2"></i>Dump Credit</span>
    <?php if (Auth::can('dumps', 'add')): ?>
    <a href="?page=dumps&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Dump
    </a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/list_page_alerts.php'; ?>

<form method="GET" action="" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fdba74;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
    <input type="hidden" name="page" value="dumps">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="flex:2;min-width:200px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#c2410c;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" placeholder="Party, IMEI, item, dump no..."
                   value="<?= htmlspecialchars($search) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #fdba74;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;outline:none;">
        </div>
        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#c2410c;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">From</label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #fdba74;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;">
        </div>
        <div style="flex:1;min-width:130px;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#c2410c;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;">To</label>
            <input type="date" name="to_date" value="<?= htmlspecialchars((string) $toDate) ?>"
                   style="width:100%;padding:8px 14px;border:1.5px solid #fdba74;border-radius:10px;font-size:0.85rem;background:#fff;color:#1e293b;">
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="submit" class="btn btn-sm" style="padding:8px 22px;background:linear-gradient(135deg,#ea580c,#c2410c);color:#fff;border:none;border-radius:10px;font-weight:700;">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="?page=dumps" class="btn btn-sm btn-outline-secondary" style="padding:8px 16px;border-radius:10px;">Clear</a>
        </div>
    </div>
</form>

<div class="card" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:0.85rem;">
            <thead style="background:var(--bg-subtle);">
                <tr>
                    <th style="padding:10px 16px;">Dump No</th>
                    <th>Date</th>
                    <th>Party</th>
                    <th class="text-end">Units</th>
                    <th class="text-end">Credit</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($dumps)): ?>
                <tr><td colspan="7" class="text-center py-5 text-muted">No dump credits found.</td></tr>
            <?php else: ?>
                <?php foreach ($dumps as $r): ?>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:10px 16px;">
                        <a href="?page=dumps&action=view&id=<?= (int) $r['id'] ?>" style="font-weight:700;color:#c2410c;text-decoration:none;">
                            <?= htmlspecialchars((string) $r['dump_no']) ?>
                        </a>
                    </td>
                    <td><?= date('d M Y', strtotime((string) $r['date'])) ?></td>
                    <td><?= htmlspecialchars((string) ($r['party_name'] ?? '')) ?></td>
                    <td class="text-end"><?= (int) ($r['unit_count'] ?? 0) ?></td>
                    <td class="text-end fw-semibold"><?= APP_CURRENCY ?> <?= number_format((float) $r['grand_total'], DECIMAL_PLACES) ?></td>
                    <td>
                        <?php if (($r['status'] ?? '') === 'cancelled'): ?>
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">Voided</span>
                        <?php else: ?>
                        <span class="badge" style="background:#ffedd5;color:#9a3412;">Approved</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="?page=dumps&action=view&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (!empty($listTruncated)): ?>
<p class="small text-muted mt-2 mb-0">Showing latest <?= (int) $listLimit ?> rows. Narrow dates or search to find older dumps.</p>
<?php endif; ?>
