<?php
$isEdit = !empty($shipment);
$formAction = $isEdit ? '?page=landedcost&action=update' : '?page=landedcost&action=store';
$selectedPoIds = $selectedPoIds ?? [];
$existingChargesMap = $existingChargesMap ?? [];
$partnerLabel = !empty($importPartner['name']) ? $importPartner['name'] : 'Partner';
?>
<style>
.import-ship-wrap { display: flex; flex-direction: column; gap: 16px; }
.import-ship-hero {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    padding: 18px 22px; border-radius: 14px;
    background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #134e4a 100%);
    color: #fff; box-shadow: 0 8px 24px rgba(15, 118, 110, 0.22);
}
.import-ship-hero h1 { font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; }
.import-ship-hero p { margin: 6px 0 0; font-size: 0.82rem; color: rgba(255,255,255,0.82); max-width: 720px; }
.import-ship-hero .btn-back {
    border: 1.5px solid rgba(255,255,255,0.35); color: #fff; background: rgba(255,255,255,0.08);
    border-radius: 8px; padding: 6px 14px; font-size: 0.82rem; text-decoration: none; white-space: nowrap;
}
.import-ship-hero .btn-back:hover { background: rgba(255,255,255,0.16); color: #fff; }
.import-legends { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.import-legend {
    display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px;
    font-size: 0.72rem; font-weight: 600; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);
}
.import-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.import-card { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,0.04); }
.import-card-head {
    padding: 12px 18px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
    color: #475569; background: linear-gradient(180deg, #f8fafc, #f1f5f9); border-bottom: 1px solid #e2e8f0;
}
.import-card-body { padding: 18px; }
.import-meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 992px) { .import-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 576px) { .import-meta-grid { grid-template-columns: 1fr; } }
.import-meta-grid .span-2 { grid-column: span 2; }
@media (max-width: 576px) { .import-meta-grid .span-2 { grid-column: span 1; } }
.import-field label { display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 5px; }
.import-field .form-control, .import-field .form-select { border-radius: 8px; border-color: #cbd5e1; font-size: 0.88rem; }
.import-field .form-control:focus, .import-field .form-select:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
.import-field .form-control[readonly] { background: #f8fafc; color: #334155; font-weight: 600; }
table.po-pick-tbl { width: 100%; font-size: 0.84rem; margin: 0; }
table.po-pick-tbl th {
    padding: 10px 16px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
}
table.po-pick-tbl td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
table.po-pick-tbl tbody tr { transition: background 0.12s; cursor: pointer; }
table.po-pick-tbl tbody tr:hover { background: #f8fafc; }
table.po-pick-tbl tbody tr.po-row-selected { background: #ecfdf5; }
table.po-pick-tbl tbody tr.po-row-selected:hover { background: #d1fae5; }
table.po-pick-tbl .form-check-input { width: 1.05rem; height: 1.05rem; cursor: pointer; border-color: #94a3b8; }
table.po-pick-tbl .form-check-input:checked { background-color: #0d9488; border-color: #0d9488; }
.po-no-badge { font-weight: 700; color: #0f766e; }
.po-kwd { font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a; }
.ic-table-wrap { overflow-x: auto; }
table.ic-charges-tbl { width: 100%; min-width: 980px; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; margin: 0; }
table.ic-charges-tbl th {
    padding: 8px 10px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; vertical-align: bottom;
}
table.ic-charges-tbl th.ic-group {
    text-align: center; color: #fff; border-bottom: none; padding: 7px 8px; font-size: 0.68rem;
}
table.ic-charges-tbl th.ic-group-hk { background: #2563eb; }
table.ic-charges-tbl th.ic-group-pack { background: #7c3aed; }
table.ic-charges-tbl th.ic-group-kw { background: #0d9488; }
table.ic-charges-tbl th.ic-group-partner { background: #d97706; }
table.ic-charges-tbl th.ic-group-landed { background: #059669; color: #fff; }
table.ic-charges-tbl td { padding: 8px 10px; border-bottom: 1px solid #eef2f7; vertical-align: top; background: #fff; }
table.ic-charges-tbl tbody tr:hover td { background: #fafcff; }
table.ic-charges-tbl tbody tr:hover td.ic-landed-cell { background: #ecfdf5; }
table.ic-charges-tbl .col-item { min-width: 200px; max-width: 240px; }
table.ic-charges-tbl .col-item .ic-po-no { font-size: 0.72rem; font-weight: 700; color: #0d9488; }
table.ic-charges-tbl .col-item .ic-item-name { display: block; font-size: 0.78rem; color: #334155; line-height: 1.35; margin-top: 2px; }
table.ic-charges-tbl .col-qty, table.ic-charges-tbl .col-price { text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; }
table.ic-charges-tbl .col-price { text-align: right; color: #64748b; font-weight: 600; }
.ic-charge-cell { min-width: 96px; }
.ic-charge-cell input[type="number"] {
    width: 100%; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;
    border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 8px; font-size: 0.8rem; background: #fff;
}
.ic-charge-cell input[type="number"]:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.12); outline: none; }
.ic-charge-cell select {
    width: 100%; margin-top: 4px; font-size: 0.68rem; color: #64748b; border: 1px solid #f1f5f9;
    border-radius: 6px; padding: 3px 6px; background: #f8fafc;
}
.ic-charge-cell select:focus { border-color: #cbd5e1; outline: none; background: #fff; }
.ic-charge-cell.ic-kw-cell input[type="number"] { border-color: #99f6e4; background: #f0fdfa; }
.ic-charge-cell.ic-kw-cell input[type="number"]:focus { border-color: #0d9488; }
.ic-landed-cell {
    text-align: right; font-variant-numeric: tabular-nums; font-weight: 800; color: #047857;
    background: #ecfdf5 !important; min-width: 88px; white-space: nowrap;
}
.ic-placeholder {
    text-align: center; padding: 48px 20px; color: #94a3b8; font-size: 0.88rem;
}
.ic-placeholder i { font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5; }
.import-save-bar {
    display: flex; justify-content: flex-end; align-items: center; gap: 10px; padding: 14px 18px;
    border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: 0 -2px 12px rgba(15,23,42,0.05);
    position: sticky; bottom: 12px; z-index: 20;
}
.import-save-bar .btn-primary { background: linear-gradient(135deg, #0d9488, #0f766e); border: none; font-weight: 700; padding: 9px 22px; border-radius: 9px; box-shadow: 0 4px 12px rgba(13,148,136,0.28); }
.import-save-bar .btn-primary:hover { background: linear-gradient(135deg, #0f766e, #115e59); transform: translateY(-1px); }
.import-hiq-lumpsum {
    display: none; flex-direction: column; gap: 0; border-bottom: 1px solid #e2e8f0;
}
.import-hiq-lumpsum.is-visible { display: block; }
.import-lump-row {
    display: grid;
    grid-template-columns: 240px 140px 118px;
    align-items: center;
    column-gap: 14px;
    padding: 12px 18px;
}
@media (max-width: 768px) {
    .import-lump-row {
        grid-template-columns: 1fr;
        row-gap: 8px;
        padding: 12px 16px;
    }
}
.import-lump-row--hk { background: #eff6ff; }
.import-lump-row--hiq { background: #f0fdfa; }
.import-lump-row label {
    margin: 0; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; white-space: nowrap;
}
.import-lump-row--hk label { color: #1d4ed8; }
.import-lump-row--hiq label { color: #0f766e; }
.import-lump-row .lump-input {
    width: 100%; max-width: 140px; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;
    border-radius: 8px; padding: 6px 10px; font-size: 0.88rem; background: #fff;
}
@media (max-width: 768px) {
    .import-lump-row .lump-input { max-width: none; }
}
.import-lump-row--hk .lump-input { border: 1px solid #bfdbfe; }
.import-lump-row--hk .lump-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); outline: none; }
.import-lump-row--hiq .lump-input { border: 1px solid #99f6e4; }
.import-lump-row--hiq .lump-input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); outline: none; }
.import-lump-row .btn-lump-apply {
    width: 100%; background: #fff; border-radius: 8px; padding: 6px 10px; font-size: 0.78rem; font-weight: 700; white-space: nowrap;
    text-align: center;
}
.import-lump-row--hk .btn-lump-apply { border: 1px solid #2563eb; color: #1d4ed8; }
.import-lump-row--hk .btn-lump-apply:hover { background: #dbeafe; color: #1e3a8a; }
.import-lump-row--hiq .btn-lump-apply { border: 1px solid #0d9488; color: #0f766e; }
.import-lump-row--hiq .btn-lump-apply:hover { background: #ccfbf1; color: #115e59; }
</style>

<div class="import-ship-wrap">
    <div class="import-ship-hero">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i><?= $isEdit ? 'Edit Import Shipment' : 'New Import Shipment' ?></h1>
            <p>Consolidate PO lines, enter per-piece logistics in KWD, and preview true landed cost before Kuwait receipt.</p>
            <div class="import-legends">
                <span class="import-legend"><span class="import-legend-dot" style="background:#60a5fa;"></span> HK→DXB · <?= htmlspecialchars($defaultFreightHkPartyName ?? 'Logix One FZE') ?></span>
                <span class="import-legend"><span class="import-legend-dot" style="background:#a78bfa;"></span> Packing · Union Logistics</span>
                <span class="import-legend"><span class="import-legend-dot" style="background:#2dd4bf;"></span> DXB→KW · Hi-IQ</span>
                <?php if (!empty($importPartner)): ?>
                <span class="import-legend"><span class="import-legend-dot" style="background:#fbbf24;"></span> Partner · <?= htmlspecialchars($importPartner['name']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?= $isEdit ? '?page=landedcost&action=view&id=' . (int) $shipment['id'] : '?page=landedcost' ?>" class="btn-back">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if (empty($importPartner)): ?>
    <div class="alert alert-warning border-0 shadow-sm small mb-0 py-2 px-3">
        Import partner (account <?= htmlspecialchars(defined('IMPORT_PARTNER_PARTY_CODE') ? IMPORT_PARTNER_PARTY_CODE : '26014') ?>) not found. Partner profit cannot be saved until the party is active.
    </div>
    <?php endif; ?>
    <?php if (empty($defaultFreightDxbPartyId)): ?>
    <div class="alert alert-warning border-0 shadow-sm small mb-0 py-2 px-3">
        DXB→KW party (<?= htmlspecialchars(defined('IMPORT_FREIGHT_DXB_PARTY_CODE') ? IMPORT_FREIGHT_DXB_PARTY_CODE : '26044') ?>) not found. Add active party <strong>HI-IQ Toys and Computer Kids Co.</strong>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($formAction) ?>" id="shipmentForm">
        <?= Auth::csrfField() ?>
        <?php if ($isEdit): ?>
        <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id'] ?>">
        <?php endif; ?>

        <div class="import-card">
            <div class="import-card-head">Shipment details</div>
            <div class="import-card-body">
                <div class="import-meta-grid">
                    <div class="import-field">
                        <label>Shipment no</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($nextNo) ?>" readonly>
                    </div>
                    <div class="import-field">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($isEdit ? ($shipment['date'] ?? date('Y-m-d')) : date('Y-m-d')) ?>" required>
                    </div>
                    <div class="import-field">
                        <label>Route stage</label>
                        <select name="route_stage" class="form-select">
                            <option value="dubai_hub"<?= ($isEdit && ($shipment['route_stage'] ?? '') === 'kuwait_inbound') ? '' : ' selected' ?>>Dubai hub (consolidation)</option>
                            <option value="kuwait_inbound"<?= ($isEdit && ($shipment['route_stage'] ?? '') === 'kuwait_inbound') ? ' selected' : '' ?>>Kuwait inbound</option>
                        </select>
                    </div>
                    <div class="import-field">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"<?= ($isEdit && ($shipment['status'] ?? '') === 'draft') ? ' selected' : '' ?>>Draft</option>
                            <option value="in_transit"<?= ($isEdit && ($shipment['status'] ?? '') === 'in_transit') || !$isEdit ? ' selected' : '' ?>>In transit</option>
                        </select>
                    </div>
                    <div class="import-field span-2">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Mixed HK container — March" value="<?= htmlspecialchars($isEdit ? ($shipment['description'] ?? '') : '') ?>">
                    </div>
                    <div class="import-field span-2">
                        <label>Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional internal notes" value="<?= htmlspecialchars($isEdit ? ($shipment['notes'] ?? '') : '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="import-card">
            <div class="import-card-head d-flex justify-content-between align-items-center">
                <span>Purchase orders</span>
                <span class="text-muted fw-normal text-lowercase" style="letter-spacing:0;font-size:0.72rem;">import + local POs (AED/USD/KWD) · draft / paid · not yet in Kuwait stock</span>
            </div>
            <div style="max-height:260px;overflow-y:auto;">
                <?php if (empty($purchaseOrders)): ?>
                <div class="ic-placeholder"><i class="bi bi-inbox"></i>No open POs available.</div>
                <?php else: ?>
                <table class="po-pick-tbl">
                    <thead><tr><th style="width:44px;"></th><th>PO</th><th>Supplier</th><th class="text-end">KWD</th></tr></thead>
                    <tbody>
                    <?php foreach ($purchaseOrders as $po): ?>
                    <tr class="po-pick-row<?= in_array((int) $po['id'], $selectedPoIds, true) ? ' po-row-selected' : '' ?>">
                        <td><input type="checkbox" name="po_ids[]" value="<?= (int) $po['id'] ?>" class="form-check-input po-check"<?= in_array((int) $po['id'], $selectedPoIds, true) ? ' checked' : '' ?>></td>
                        <td>
                            <span class="po-no-badge"><?= htmlspecialchars($po['po_no']) ?></span><br>
                            <small class="text-muted"><?= date('d M Y', strtotime($po['date'])) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($po['supplier_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($po['currency'] ?? 'KWD') ?></small>
                        </td>
                        <td class="text-end po-kwd"><?= number_format((float) $po['subtotal_kwd'], DECIMAL_PLACES) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="import-card">
            <div class="import-card-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Item charges (KWD)</span>
                <?php if (empty($freightForwarders ?? [])): ?>
                <span class="text-muted fw-normal" style="letter-spacing:0;font-size:0.72rem;text-transform:none;">
                    <a href="?page=parties&action=create">Add freight forwarder</a> in Party Master first
                </span>
                <?php else: ?>
                <span class="text-muted fw-normal" style="letter-spacing:0;font-size:0.72rem;text-transform:none;">one row per PO line · all amounts per piece</span>
                <?php endif; ?>
            </div>
            <div class="import-hiq-lumpsum" id="lumpSumBars">
                <div class="import-lump-row import-lump-row--hk">
                    <label for="hkLumpSumInput"><i class="bi bi-calculator me-1"></i>HK~DXB Charges (KWD)</label>
                    <input type="number" id="hkLumpSumInput" class="lump-input" step="0.001" min="0" placeholder="0.000" aria-label="HK to DXB total amount for selected PO lines">
                    <button type="button" id="hkLumpSumApply" class="btn-lump-apply">Divide to /pc</button>
                </div>
                <div class="import-lump-row import-lump-row--hiq">
                    <label for="hiqLumpSumInput"><i class="bi bi-calculator me-1"></i>Hi-IQ lump sum (KWD)</label>
                    <input type="number" id="hiqLumpSumInput" class="lump-input" step="0.001" min="0" placeholder="0.000" aria-label="Hi-IQ total amount for selected PO lines">
                    <button type="button" id="hiqLumpSumApply" class="btn-lump-apply">Divide to /pc</button>
                </div>
            </div>
            <div id="itemChargesPlaceholder" class="ic-placeholder">
                <i class="bi bi-ui-checks-grid"></i>Select at least one PO above to load item rows.
            </div>
            <div class="ic-table-wrap" id="itemChargesWrap" style="display:none;">
                <table class="ic-charges-tbl" id="itemChargesTable">
                    <thead>
                        <tr>
                            <th colspan="3" style="background:#fff;border-bottom:none;"></th>
                            <th colspan="1" class="ic-group ic-group-hk">HK → DXB</th>
                            <th colspan="1" class="ic-group ic-group-pack">Packing DXB</th>
                            <th colspan="1" class="ic-group ic-group-kw">DXB → KW</th>
                            <th colspan="1" class="ic-group ic-group-partner">Partner</th>
                            <th colspan="1" class="ic-group ic-group-landed">Landed</th>
                        </tr>
                        <tr>
                            <th class="col-item">PO / Item</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-price">Price/pc</th>
                            <th>/pc · <?= htmlspecialchars($defaultFreightHkPartyName ?? 'Logix One FZE') ?></th>
                            <th>/pc · Union Logistics</th>
                            <th>/pc · Hi-IQ</th>
                            <th>/pc</th>
                            <th class="ic-landed-cell" style="background:#ecfdf5;color:#047857;">cost/pc</th>
                        </tr>
                    </thead>
                    <tbody id="itemChargesBody"></tbody>
                </table>
            </div>
        </div>

        <div class="import-save-bar">
            <a href="<?= $isEdit ? '?page=landedcost&action=view&id=' . (int) $shipment['id'] : '?page=landedcost' ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update shipment' : 'Save shipment' ?></button>
        </div>
    </form>
</div>

<template id="itemChargeRowTpl">
    <tr class="item-charge-row">
        <td class="col-item">
            <input type="hidden" name="ic_po_item_id[]" class="ic-po-item-id">
            <input type="hidden" name="ic_item_id[]" class="ic-item-id">
            <input type="hidden" name="ic_quantity[]" class="ic-quantity">
            <span class="ic-po-no"></span>
            <span class="ic-item-name"></span>
        </td>
        <td class="col-qty ic-qty-display"></td>
        <td class="col-price ic-price-display"></td>
        <td>
            <div class="ic-charge-cell">
                <input type="number" name="ic_freight_hk[]" class="ic-charge-input" step="0.001" min="0" placeholder="0.000">
                <select name="ic_freight_hk_party[]">
                    <option value="">— Forwarder —</option>
                    <?php foreach (($freightForwarders ?? []) as $ff): ?>
                    <option value="<?= (int) $ff['id'] ?>"<?= ((int) ($defaultFreightHkPartyId ?? 0) === (int) $ff['id']) ? ' selected' : '' ?>><?= htmlspecialchars($ff['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </td>
        <td>
            <div class="ic-charge-cell">
                <input type="number" name="ic_packing_dxb[]" class="ic-charge-input" step="0.001" min="0" placeholder="0.000">
                <select name="ic_packing_dxb_party[]">
                    <option value="">— Forwarder —</option>
                    <?php foreach (($freightForwarders ?? []) as $ff): ?>
                    <option value="<?= (int) $ff['id'] ?>"<?= ((int) ($defaultPackingPartyId ?? 0) === (int) $ff['id']) ? ' selected' : '' ?>><?= htmlspecialchars($ff['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </td>
        <td>
            <div class="ic-charge-cell ic-kw-cell">
                <input type="hidden" name="ic_freight_dxb_party[]" value="<?= (int) ($defaultFreightDxbPartyId ?? 0) ?>">
                <input type="number" name="ic_freight_dxb[]" class="ic-charge-input" step="0.001" min="0" placeholder="0.000">
            </div>
        </td>
        <td>
            <div class="ic-charge-cell">
                <input type="number" name="ic_partner_pc[]" class="ic-charge-input" step="0.001" min="0" placeholder="0.250" value="<?= htmlspecialchars(number_format((float) (defined('IMPORT_PARTNER_PROFIT_PER_PC') ? IMPORT_PARTNER_PROFIT_PER_PC : 0.250), DECIMAL_PLACES, '.', '')) ?>">
            </div>
        </td>
        <td class="ic-landed-cell ic-landed-pc">0.000</td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const DECIMAL_PLACES = <?= (int) DECIMAL_PLACES ?>;
    const existingCharges = <?= json_encode($existingChargesMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const checks = document.querySelectorAll('.po-check');
    const body = document.getElementById('itemChargesBody');
    const wrap = document.getElementById('itemChargesWrap');
    const placeholder = document.getElementById('itemChargesPlaceholder');
    const tpl = document.getElementById('itemChargeRowTpl');
    const lumpSumBars = document.getElementById('lumpSumBars');
    let loadTimer = null;

    const lumpSumLegs = [
        {
            input: document.getElementById('hkLumpSumInput'),
            applyBtn: document.getElementById('hkLumpSumApply'),
            fieldName: 'ic_freight_hk[]'
        },
        {
            input: document.getElementById('hiqLumpSumInput'),
            applyBtn: document.getElementById('hiqLumpSumApply'),
            fieldName: 'ic_freight_dxb[]'
        }
    ];

    function parseAmount(value) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : 0;
    }

    function formatAmount(value) {
        return parseAmount(value).toFixed(DECIMAL_PLACES);
    }

    function updatePoRowStyles() {
        checks.forEach(function (c) {
            const tr = c.closest('.po-pick-row');
            if (tr) {
                tr.classList.toggle('po-row-selected', c.checked);
            }
        });
    }

    function applyExistingCharges(tr, poItemId) {
        const saved = existingCharges[String(poItemId)] || existingCharges[poItemId];
        if (!saved) {
            return;
        }
        const setInput = function (name, value) {
            const el = tr.querySelector('[name="' + name + '"]');
            if (el && value > 0) {
                el.value = formatAmount(value);
            }
        };
        const setSelect = function (name, value) {
            const el = tr.querySelector('[name="' + name + '"]');
            if (el && value > 0) {
                el.value = String(value);
            }
        };
        setInput('ic_freight_hk[]', saved.freight_hk);
        setSelect('ic_freight_hk_party[]', saved.freight_hk_party_id);
        setInput('ic_packing_dxb[]', saved.packing_dxb);
        setSelect('ic_packing_dxb_party[]', saved.packing_dxb_party_id);
        setInput('ic_freight_dxb[]', saved.freight_dxb);
        const partnerEl = tr.querySelector('[name="ic_partner_pc[]"]');
        if (partnerEl) {
            partnerEl.value = formatAmount(saved.partner_pc);
        }
    }

    function recalcLandedPerPc(row) {
        const unitPrice = parseAmount(row.dataset.unitPrice);
        const freightHk = parseAmount(row.querySelector('[name="ic_freight_hk[]"]')?.value);
        const packingDxb = parseAmount(row.querySelector('[name="ic_packing_dxb[]"]')?.value);
        const freightDxb = parseAmount(row.querySelector('[name="ic_freight_dxb[]"]')?.value);
        const partnerPc = parseAmount(row.querySelector('[name="ic_partner_pc[]"]')?.value);
        const landedPerPc = unitPrice + freightHk + packingDxb + freightDxb + partnerPc;
        const cell = row.querySelector('.ic-landed-pc');
        if (cell) {
            cell.textContent = formatAmount(landedPerPc);
        }
    }

    function bindChargeInputs(row) {
        row.querySelectorAll('.ic-charge-input').forEach(function (input) {
            input.addEventListener('input', function () {
                recalcLandedPerPc(row);
            });
        });
    }

    function selectedPoIds() {
        return Array.from(checks).filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
    }

    function chargeRows() {
        return Array.from(body.querySelectorAll('.item-charge-row'));
    }

    function totalChargeQty(rows) {
        return rows.reduce(function (sum, row) {
            return sum + parseAmount(row.dataset.qty);
        }, 0);
    }

    function lineTotalForField(row, fieldName) {
        const qty = parseAmount(row.dataset.qty);
        const rate = parseAmount(row.querySelector('[name="' + fieldName + '"]')?.value);
        return qty * rate;
    }

    function syncLumpSumFromRows(leg) {
        if (!leg.input) {
            return;
        }
        const rows = chargeRows();
        if (!rows.length) {
            leg.input.value = '';
            return;
        }
        const total = rows.reduce(function (sum, row) {
            return sum + lineTotalForField(row, leg.fieldName);
        }, 0);
        leg.input.value = total > 0 ? formatAmount(total) : '';
    }

    function syncAllLumpSumsFromRows() {
        lumpSumLegs.forEach(syncLumpSumFromRows);
    }

    function resetAllLumpSums() {
        lumpSumLegs.forEach(function (leg) {
            if (leg.input) {
                leg.input.value = '';
            }
        });
    }

    function setLumpSumBarsVisible(show) {
        if (lumpSumBars) {
            lumpSumBars.classList.toggle('is-visible', !!show);
        }
    }

    function distributeLumpSum(leg) {
        const lumpSum = parseAmount(leg.input?.value);
        const rows = chargeRows();
        const totalQty = totalChargeQty(rows);
        if (!rows.length || lumpSum <= 0 || totalQty <= 0) {
            return;
        }

        let allocated = 0;
        rows.forEach(function (row, index) {
            const qty = parseAmount(row.dataset.qty);
            const input = row.querySelector('[name="' + leg.fieldName + '"]');
            if (!input || qty <= 0) {
                return;
            }
            let rate;
            if (index === rows.length - 1) {
                rate = (lumpSum - allocated) / qty;
            } else {
                rate = lumpSum / totalQty;
                allocated += rate * qty;
            }
            input.value = formatAmount(rate);
            recalcLandedPerPc(row);
        });
    }

    function bindLumpSumLeg(leg) {
        if (leg.applyBtn) {
            leg.applyBtn.addEventListener('click', function () {
                distributeLumpSum(leg);
            });
        }
        if (leg.input) {
            leg.input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    distributeLumpSum(leg);
                }
            });
        }
    }

    lumpSumLegs.forEach(bindLumpSumLeg);

    function loadItems() {
        const ids = selectedPoIds();
        updatePoRowStyles();
        if (!ids.length) {
            body.innerHTML = '';
            wrap.style.display = 'none';
            setLumpSumBarsVisible(false);
            resetAllLumpSums();
            placeholder.style.display = '';
            placeholder.innerHTML = '<i class="bi bi-ui-checks-grid"></i>Select at least one PO above to load item rows.';
            return;
        }
        const qs = ids.map(function (id) { return 'po_ids[]=' + encodeURIComponent(id); }).join('&');
        fetch('?page=landedcost&action=poItems&' + qs)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                body.innerHTML = '';
                (data.items || []).forEach(function (item) {
                    const row = tpl.content.cloneNode(true);
                    const tr = row.querySelector('tr');
                    const unitPrice = parseAmount(item.unit_price_kwd);
                    tr.dataset.qty = String(parseAmount(item.quantity));
                    tr.dataset.unitPrice = String(unitPrice);
                    tr.querySelector('.ic-po-item-id').value = item.po_item_id;
                    tr.querySelector('.ic-item-id').value = item.item_id;
                    tr.querySelector('.ic-quantity').value = item.quantity;
                    tr.querySelector('.ic-po-no').textContent = item.po_no;
                    tr.querySelector('.ic-item-name').textContent = item.item_name + (item.sku ? ' (' + item.sku + ')' : '');
                    tr.querySelector('.ic-qty-display').textContent = item.quantity;
                    tr.querySelector('.ic-price-display').textContent = formatAmount(unitPrice);
                    applyExistingCharges(tr, item.po_item_id);
                    bindChargeInputs(tr);
                    recalcLandedPerPc(tr);
                    body.appendChild(row);
                });
                const hasItems = !!(data.items || []).length;
                wrap.style.display = hasItems ? '' : 'none';
                setLumpSumBarsVisible(hasItems);
                placeholder.style.display = hasItems ? 'none' : '';
                if (!hasItems) {
                    placeholder.innerHTML = '<i class="bi bi-exclamation-circle"></i>Selected POs have no line items.';
                    placeholder.style.display = '';
                    resetAllLumpSums();
                } else {
                    syncAllLumpSumsFromRows();
                }
            })
            .catch(function () {
                placeholder.innerHTML = '<i class="bi bi-wifi-off"></i>Could not load PO items.';
                placeholder.style.display = '';
                wrap.style.display = 'none';
                setLumpSumBarsVisible(false);
            });
    }

    function scheduleLoad() {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(loadItems, 200);
    }

    checks.forEach(function (c) {
        c.addEventListener('change', scheduleLoad);
        const tr = c.closest('.po-pick-row');
        if (tr) {
            tr.addEventListener('click', function (e) {
                if (e.target === c || e.target.closest('input')) {
                    return;
                }
                c.checked = !c.checked;
                scheduleLoad();
            });
        }
    });

    if (selectedPoIds().length) {
        loadItems();
    } else {
        updatePoRowStyles();
    }
});
</script>
