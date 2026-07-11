<?php
$partnerNavActive = 'history';
$periodLabel = date('d M Y', strtotime($fromDate)) . ' — ' . date('d M Y', strtotime($toDate));
$paymentCount = count($payments ?? []);
?>

<div class="partner-history-page">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
        <div>
            <h1 class="page-title mb-1">Partner profit history</h1>
            <p class="text-muted mb-0 partner-history-subtitle">
                Settlements paid to
                <strong><?= htmlspecialchars($importPartner['name'] ?? 'Muhammad Faisal') ?></strong>
                <?php if (!empty($importPartner['party_code'])): ?>
                <span class="partner-code-pill"><?= htmlspecialchars($importPartner['party_code']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <a href="?page=landedcost" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Shipments
        </a>
    </div>

    <?php include __DIR__ . '/landed_cost_partner_nav.php'; ?>

    <div class="partner-history-toolbar card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 g-md-3 align-items-end">
                <input type="hidden" name="page" value="landedcost">
                <input type="hidden" name="action" value="partnerHistory">
                <div class="col-6 col-md-auto">
                    <label class="form-label partner-field-label mb-1">From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $fromDate) ?>">
                </div>
                <div class="col-6 col-md-auto">
                    <label class="form-label partner-field-label mb-1">To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $toDate) ?>">
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm w-100 px-4">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                </div>
                <div class="col-12 col-md ms-md-auto">
                    <div class="partner-period-chip text-md-end">
                        <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($periodLabel) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="partner-history-summary mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="partner-stat-card partner-stat-card--primary h-100">
                    <div class="partner-stat-label">Total paid</div>
                    <div class="partner-stat-value"><?= APP_CURRENCY ?> <?= number_format((float) $totalPaid, DECIMAL_PLACES) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="partner-stat-card h-100">
                    <div class="partner-stat-label">Payments</div>
                    <div class="partner-stat-value partner-stat-value--sm"><?= $paymentCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="partner-stat-card h-100">
                    <div class="partner-stat-label">Period</div>
                    <div class="partner-stat-value partner-stat-value--sm partner-stat-period"><?= htmlspecialchars($periodLabel) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($payments)): ?>
    <div class="card border-0 shadow-sm partner-empty-state">
        <div class="card-body text-center py-5">
            <div class="partner-empty-icon mx-auto mb-3">
                <i class="bi bi-inbox"></i>
            </div>
            <h2 class="h5 mb-2">No payments in this period</h2>
            <p class="text-muted mb-0" style="font-size:0.88rem;">Try a wider date range or check Partner due for unpaid lines.</p>
        </div>
    </div>
    <?php else: ?>

    <div class="partner-payment-list d-flex flex-column gap-3">
    <?php foreach ($payments as $p):
        $pid = (int) $p['id'];
        $lines = $linesByPayId[$pid] ?? [];
        $lineCount = (int) ($p['line_count'] ?? count($lines));
        $byShipment = [];
        foreach ($lines as $ln) {
            $sid = (int) ($ln['shipment_id'] ?? 0);
            $key = $sid > 0 ? (string) $sid : (string) ($ln['shipment_no'] ?? 'unknown');
            if (!isset($byShipment[$key])) {
                $byShipment[$key] = [
                    'shipment_id' => $sid,
                    'shipment_no' => (string) ($ln['shipment_no'] ?? ''),
                    'lines'       => [],
                    'total'       => 0.0,
                ];
            }
            $byShipment[$key]['lines'][] = $ln;
            $byShipment[$key]['total'] += (float) ($ln['amount'] ?? 0);
        }
        $shipmentGroups = array_values($byShipment);
        $shipmentCount = count($shipmentGroups);
    ?>
        <article class="card border-0 shadow-sm partner-payment-card">
            <div class="card-body p-0">
                <div class="partner-payment-head">
                    <div class="partner-payment-head-main">
                        <div class="partner-payment-icon" aria-hidden="true">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <a href="?page=payments&action=detail&id=<?= $pid ?>" class="partner-pay-no">
                                    <?= htmlspecialchars($p['payment_no']) ?>
                                </a>
                                <span class="partner-date-badge"><?= date('d M Y', strtotime($p['date'])) ?></span>
                            </div>
                            <div class="partner-payment-meta">
                                <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($p['partner_name'] ?? '') ?></span>
                                <span class="partner-meta-dot">·</span>
                                <span><i class="bi bi-wallet2 me-1"></i><?= htmlspecialchars($p['account_name'] ?? '—') ?></span>
                                <span class="partner-meta-dot">·</span>
                                <span><?= $lineCount ?> line<?= $lineCount === 1 ? '' : 's' ?></span>
                                <?php if ($shipmentCount > 0): ?>
                                <span class="partner-meta-dot">·</span>
                                <span><?= $shipmentCount ?> shipment<?= $shipmentCount === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($p['notes'])): ?>
                            <div class="partner-payment-notes"><?= htmlspecialchars($p['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="partner-payment-head-side">
                        <div class="partner-payment-amount">
                            <?= APP_CURRENCY ?> <?= number_format((float) $p['amount'], DECIMAL_PLACES) ?>
                        </div>
                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                            <?php if (!empty($canEdit)): ?>
                            <a href="?page=landedcost&action=partnerPaymentEdit&id=<?= $pid ?>"
                               class="btn btn-sm btn-light partner-action-btn" title="Edit payment">
                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline ms-1">Edit</span>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($canUndo)): ?>
                            <form method="POST" action="?page=landedcost&action=partnerPaymentUndo" class="partner-undo-form">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= $pid ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger partner-action-btn" title="Undo payment">
                                    <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline ms-1">Undo</span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($shipmentGroups !== []): ?>
                <div class="partner-shipment-panel partner-history-lines">
                    <div class="partner-shipment-panel-bar">
                        <span class="partner-shipment-panel-title">
                            <i class="bi bi-box-seam me-1"></i>Shipment breakdown
                        </span>
                        <?php if ($shipmentCount > 1): ?>
                        <div class="partner-shipment-panel-actions">
                            <button type="button" class="btn btn-link btn-sm p-0 partner-lines-expand-all">Expand all</button>
                            <span class="text-muted">·</span>
                            <button type="button" class="btn btn-link btn-sm p-0 partner-lines-collapse-all">Collapse all</button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="partner-shipment-list">
                    <?php foreach ($shipmentGroups as $gi => $grp):
                        $grpId = 'partner-ship-' . $pid . '-' . $gi;
                        $itemCount = count($grp['lines']);
                        $startOpen = $shipmentCount === 1;
                    ?>
                        <div class="partner-shipment-block<?= $startOpen ? ' is-expanded' : '' ?>" data-shipment-block>
                            <div class="partner-shipment-row">
                                <button type="button"
                                        class="partner-shipment-toggle"
                                        aria-expanded="<?= $startOpen ? 'true' : 'false' ?>"
                                        aria-controls="<?= htmlspecialchars($grpId) ?>">
                                    <span class="partner-shipment-toggle-left">
                                        <span class="partner-shipment-chevron-wrap" aria-hidden="true">
                                            <i class="bi bi-chevron-right partner-shipment-chevron"></i>
                                        </span>
                                        <span class="partner-shipment-badge"><?= htmlspecialchars($grp['shipment_no']) ?></span>
                                        <span class="partner-shipment-count"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></span>
                                    </span>
                                    <span class="partner-shipment-amount">
                                        <?= APP_CURRENCY ?> <?= number_format((float) $grp['total'], DECIMAL_PLACES) ?>
                                    </span>
                                </button>
                                <?php if ((int) $grp['shipment_id'] > 0): ?>
                                <a href="?page=landedcost&action=view&id=<?= (int) $grp['shipment_id'] ?>"
                                   class="partner-shipment-open"
                                   title="Open shipment">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>

                            <div id="<?= htmlspecialchars($grpId) ?>"
                                 class="partner-shipment-detail<?= $startOpen ? '' : ' d-none' ?>">
                                <div class="partner-item-list">
                                <?php foreach ($grp['lines'] as $ln): ?>
                                    <div class="partner-item-row">
                                        <div class="partner-item-info">
                                            <div class="partner-item-po"><?= htmlspecialchars($ln['po_no'] ?? '') ?></div>
                                            <div class="partner-item-name"><?= htmlspecialchars($ln['item_name'] ?? '') ?></div>
                                        </div>
                                        <div class="partner-item-calc">
                                            <?= number_format((float) ($ln['partner_profit_per_pc'] ?? 0), DECIMAL_PLACES) ?>
                                            × <?= (int) ($ln['quantity'] ?? 0) ?>
                                        </div>
                                        <div class="partner-item-amt">
                                            <?= number_format((float) ($ln['amount'] ?? 0), DECIMAL_PLACES) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.partner-undo-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Undo this payment? Lines will return to Partner due and the account balance will be reversed.')) {
                e.preventDefault();
            }
        });
    });

    function setShipmentBlockExpanded(block, expanded) {
        var toggle = block.querySelector('.partner-shipment-toggle');
        var detail = block.querySelector('.partner-shipment-detail');
        if (!toggle || !detail) {
            return;
        }
        block.classList.toggle('is-expanded', expanded);
        detail.classList.toggle('d-none', !expanded);
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    document.querySelectorAll('.partner-shipment-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var block = btn.closest('[data-shipment-block]');
            if (!block) {
                return;
            }
            setShipmentBlockExpanded(block, !block.classList.contains('is-expanded'));
        });
    });

    document.querySelectorAll('.partner-history-lines').forEach(function (wrap) {
        var expandAll = wrap.querySelector('.partner-lines-expand-all');
        var collapseAll = wrap.querySelector('.partner-lines-collapse-all');
        if (expandAll) {
            expandAll.addEventListener('click', function () {
                wrap.querySelectorAll('[data-shipment-block]').forEach(function (block) {
                    setShipmentBlockExpanded(block, true);
                });
            });
        }
        if (collapseAll) {
            collapseAll.addEventListener('click', function () {
                wrap.querySelectorAll('[data-shipment-block]').forEach(function (block) {
                    setShipmentBlockExpanded(block, false);
                });
            });
        }
    });
});
</script>
<style>
.partner-history-page {
    --ph-green: #059669;
    --ph-green-soft: #ecfdf5;
    --ph-green-border: #a7f3d0;
    --ph-slate: #64748b;
    --ph-border: #e2e8f0;
    --ph-bg: #f8fafc;
}
.partner-history-subtitle { font-size: 0.86rem; }
.partner-code-pill {
    display: inline-block;
    margin-left: 0.35rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: var(--ph-bg);
    color: var(--ph-slate);
    font-size: 0.75rem;
    font-weight: 600;
}
.partner-field-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ph-slate);
}
.partner-period-chip {
    font-size: 0.82rem;
    color: var(--ph-slate);
    padding-top: 0.35rem;
}
.partner-stat-card {
    background: #fff;
    border: 1px solid var(--ph-border);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.partner-stat-card--primary {
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 55%, #fff 100%);
    border-color: var(--ph-green-border);
}
.partner-stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ph-slate);
    margin-bottom: 0.35rem;
}
.partner-stat-value {
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--ph-green);
    line-height: 1.2;
}
.partner-stat-value--sm {
    font-size: 1.15rem;
    color: #0f172a;
}
.partner-stat-period {
    font-size: 0.92rem;
    font-weight: 600;
}
.partner-empty-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    background: var(--ph-bg);
    color: var(--ph-slate);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.partner-payment-card {
    border-radius: 16px !important;
    overflow: hidden;
}
.partner-payment-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.1rem 1.15rem;
    border-bottom: 1px solid var(--ph-border);
    background: #fff;
}
.partner-payment-head-main {
    display: flex;
    gap: 0.85rem;
    min-width: 0;
}
.partner-payment-icon {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 12px;
    background: var(--ph-green-soft);
    color: var(--ph-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.partner-pay-no {
    font-weight: 700;
    font-size: 1rem;
    color: #0f766e;
    text-decoration: none;
}
.partner-pay-no:hover { color: #115e59; text-decoration: underline; }
.partner-date-badge {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--ph-slate);
    background: var(--ph-bg);
    border: 1px solid var(--ph-border);
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
}
.partner-payment-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: var(--ph-slate);
}
.partner-meta-dot { opacity: 0.5; }
.partner-payment-notes {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    color: #94a3b8;
}
.partner-payment-head-side {
    text-align: right;
    flex-shrink: 0;
}
.partner-payment-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ph-green);
    margin-bottom: 0.5rem;
    white-space: nowrap;
}
.partner-action-btn {
    border-radius: 8px !important;
    font-size: 0.78rem;
}
.partner-shipment-panel {
    background: var(--ph-bg);
    padding: 0.85rem 1rem 1rem;
}
.partner-shipment-panel-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
}
.partner-shipment-panel-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ph-slate);
}
.partner-shipment-panel-actions {
    font-size: 0.78rem;
}
.partner-shipment-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.partner-shipment-block {
    background: #fff;
    border: 1px solid var(--ph-border);
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.partner-shipment-block.is-expanded {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}
.partner-shipment-row {
    display: flex;
    align-items: stretch;
}
.partner-shipment-toggle {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border: 0;
    background: transparent;
    padding: 0.65rem 0.75rem;
    text-align: left;
}
.partner-shipment-toggle:hover { background: #f1f5f9; }
.partner-shipment-toggle-left {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
}
.partner-shipment-chevron-wrap {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 6px;
    background: var(--ph-bg);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.partner-shipment-chevron {
    font-size: 0.7rem;
    color: var(--ph-slate);
    transition: transform 0.15s ease;
}
.partner-shipment-block.is-expanded .partner-shipment-chevron {
    transform: rotate(90deg);
}
.partner-shipment-badge {
    font-weight: 700;
    font-size: 0.84rem;
    color: #0f172a;
}
.partner-shipment-count {
    font-size: 0.76rem;
    color: var(--ph-slate);
}
.partner-shipment-amount {
    font-weight: 700;
    font-size: 0.84rem;
    color: var(--ph-green);
    white-space: nowrap;
}
.partner-shipment-open {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    border-left: 1px solid var(--ph-border);
    color: var(--ph-slate);
    text-decoration: none;
    flex-shrink: 0;
}
.partner-shipment-open:hover {
    background: #f1f5f9;
    color: #0f766e;
}
.partner-shipment-detail {
    border-top: 1px solid var(--ph-border);
    background: #fcfdfe;
}
.partner-item-list { padding: 0.35rem 0.5rem 0.5rem; }
.partner-item-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 0.65rem;
    align-items: center;
    padding: 0.55rem 0.5rem;
    border-radius: 8px;
}
.partner-item-row + .partner-item-row { border-top: 1px dashed #e2e8f0; }
.partner-item-po {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--ph-slate);
}
.partner-item-name {
    font-size: 0.82rem;
    color: #0f172a;
    line-height: 1.35;
}
.partner-item-calc {
    font-size: 0.76rem;
    color: var(--ph-slate);
    white-space: nowrap;
}
.partner-item-amt {
    font-size: 0.84rem;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    min-width: 4.5rem;
    text-align: right;
}
@media (max-width: 575.98px) {
    .partner-payment-head {
        flex-direction: column;
    }
    .partner-payment-head-side {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
    }
    .partner-payment-amount { margin-bottom: 0; }
    .partner-item-row {
        grid-template-columns: minmax(0, 1fr) auto;
        grid-template-rows: auto auto;
    }
    .partner-item-calc {
        grid-column: 1;
        grid-row: 2;
    }
    .partner-item-amt {
        grid-column: 2;
        grid-row: 1 / span 2;
        align-self: center;
    }
}
</style>
