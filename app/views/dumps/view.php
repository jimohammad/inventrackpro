<?php
$dump = $dump ?? [];
$isVoided = ($dump['status'] ?? '') === 'cancelled';
?>
<style>
@media print {
    .sidebar, .topbar, .app-topbar, .no-print, .btn, form { display: none !important; }
    .content-area, .main-content { margin: 0 !important; padding: 0 !important; }
}
</style>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <a href="?page=dumps" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i></a>
        <span class="page-title"><?= htmlspecialchars((string) $dump['dump_no']) ?></span>
        <?php if ($isVoided): ?>
        <span class="badge ms-2" style="background:#fee2e2;color:#991b1b;">Voided</span>
        <?php else: ?>
        <span class="badge ms-2" style="background:#ffedd5;color:#9a3412;">Approved</span>
        <?php endif; ?>
    </div>
    <div>
        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <?php if (!$isVoided && Auth::can('dumps', 'delete')): ?>
        <?php
        $hasLegacy = false;
        foreach ($dump['items'] as $it) {
            if (!empty($it['is_legacy'])) {
                $hasLegacy = true;
                break;
            }
        }
        $voidMsg = $hasLegacy
            ? 'Void this dump? Party credit will be reversed. In-app IMEIs go back to sold. Older devices not in the app will be removed from the IMEI list.'
            : 'Void this dump? IMEI will be marked sold again and the party credit reversed.';
        ?>
        <form method="POST" action="?page=dumps&action=void" class="d-inline"
              onsubmit="return confirm(<?= json_encode($voidMsg) ?>);">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $dump['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Void dump</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3" style="border-radius:10px;">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-4">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">PARTY</p>
                <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) $dump['party_name']) ?></p>
                <?php if (!empty($dump['party_phone'])): ?>
                <p class="mb-0 text-muted" style="font-size:0.82rem;"><?= htmlspecialchars((string) $dump['party_phone']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-sm-4">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">DATE</p>
                <p class="mb-0"><?= date('d M Y', strtotime((string) $dump['date'])) ?></p>
            </div>
            <div class="col-sm-4">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">BRANCH</p>
                <p class="mb-0"><?= htmlspecialchars((string) ($dump['warehouse_name'] ?? '')) ?></p>
            </div>
            <div class="col-sm-4">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">CREDIT</p>
                <p class="mb-0 fw-bold" style="color:#c2410c;"><?= APP_CURRENCY ?> <?= number_format((float) $dump['grand_total'], DECIMAL_PLACES) ?></p>
            </div>
            <div class="col-sm-4">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">RECORDED BY</p>
                <p class="mb-0"><?= htmlspecialchars((string) ($dump['created_by_name'] ?? '—')) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card" style="border-radius:10px;">
    <div class="card-header" style="background:#fff7ed;font-weight:700;color:#9a3412;">
        Dumped units (not restocked)
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:0.85rem;">
            <thead>
                <tr>
                    <th style="padding-left:16px;">IMEI</th>
                    <th>Item</th>
                    <th>Original invoice</th>
                    <th class="text-end pe-3">Credit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dump['items'] as $it): ?>
                <tr>
                    <td style="padding-left:16px;"><code><?= htmlspecialchars((string) $it['imei']) ?></code></td>
                    <td><?= htmlspecialchars((string) $it['item_name']) ?></td>
                    <td>
                        <?php if (!empty($it['is_legacy'])): ?>
                        <span class="badge" style="background:#fff7ed;color:#9a3412;">Not in app</span>
                        <?php elseif (!empty($it['sale_id']) && !empty($it['sale_invoice_no'])): ?>
                        <a href="?page=sales&action=detail&id=<?= (int) $it['sale_id'] ?>"><?= htmlspecialchars((string) $it['sale_invoice_no']) ?></a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3"><?= APP_CURRENCY ?> <?= number_format((float) $it['unit_price'], DECIMAL_PLACES) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff7ed;">
                    <td colspan="3" class="text-end fw-bold" style="padding:10px 16px;">Party credit</td>
                    <td class="text-end fw-bold pe-3"><?= APP_CURRENCY ?> <?= number_format((float) $dump['grand_total'], DECIMAL_PLACES) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
