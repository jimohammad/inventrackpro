<?php
$stages = ServiceController::stages();
$currentStage = (int)$record['device_stage'];
$curr = APP_CURRENCY;
$trackUrl = app_service_track_url((string) $record['tracking_token']);
?>
<style>
.sd-page { max-width:800px;margin:0 auto; }
.sd-head { display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap; }
.sd-head a.back { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none; }
.sd-head h1 { font-size:1.15rem;font-weight:700;margin:0; }
.sd-head .sd-badge { padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:700;color:#fff; }

.sd-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;margin-bottom:14px;overflow:hidden; }
.sd-sep { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;padding:12px 16px 4px;display:flex;align-items:center;gap:6px;justify-content:space-between; }

/* Journey */
.sd-journey { padding:20px 16px;display:flex;justify-content:space-between;align-items:center;gap:4px;overflow-x:auto; }
.sd-step { flex:1;min-width:110px;text-align:center;position:relative; }
.sd-step-icon { width:44px;height:44px;border-radius:50%;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:var(--bg-main);border:2px solid var(--border-color);color:var(--text-muted);transition:all .2s; }
.sd-step.active .sd-step-icon { border-color:currentColor;box-shadow:0 0 0 4px currentColor; }
.sd-step.done .sd-step-icon { background:currentColor;color:#fff !important; }
.sd-step-label { font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.3px; }
.sd-step.active .sd-step-label { color:currentColor;font-weight:800; }
.sd-step::after { content:'';position:absolute;top:22px;left:calc(50% + 22px);right:calc(-50% + 22px);height:2px;background:var(--border-color); }
.sd-step:last-child::after { display:none; }
.sd-step.done::after { background:currentColor; }

/* Rows */
.sd-rows { padding:6px 16px 14px; }
.sd-row { display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border-color);font-size:.85rem; }
.sd-row:last-child { border-bottom:none; }
.sd-row-label { color:var(--text-muted); }
.sd-row-value { font-weight:600;color:var(--text-main);text-align:right; }
.sd-row-value a { color:var(--primary);text-decoration:none; }

/* Actions */
.sd-actions { padding:14px 16px;display:flex;gap:8px;flex-wrap:wrap; }
.sd-btn { padding:8px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px; }
.sd-btn-next { background:var(--primary);color:#fff; }
.sd-btn-next:hover { opacity:.9; }
.sd-btn-custom { background:rgba(99,102,241,.1);color:var(--primary); }
.sd-btn-del { background:rgba(239,68,68,.1);color:#dc2626; }

/* History */
.sd-hist { padding:10px 16px 14px;max-height:300px;overflow-y:auto; }
.sd-hist-item { display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-color);font-size:.82rem; }
.sd-hist-item:last-child { border-bottom:none; }
.sd-hist-dot { width:8px;height:8px;border-radius:50%;margin-top:7px;flex-shrink:0; }
.sd-hist-body { flex:1; }
.sd-hist-head { font-weight:600;color:var(--text-main); }
.sd-hist-meta { font-size:.72rem;color:var(--text-muted);margin-top:2px; }

/* Tracking URL */
.sd-track { background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:.82rem;margin-bottom:14px; }
.sd-track a { color:#16a34a;font-weight:600;text-decoration:none;font-family:monospace;font-size:.78rem; }
.sd-copy { background:rgba(34,197,94,.15);color:#16a34a;border:none;padding:4px 10px;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer; }
</style>

<div class="sd-page">
    <div class="sd-head">
        <a href="?page=service" class="back"><i class="bi bi-arrow-left"></i></a>
        <h1><?= htmlspecialchars($record['service_no']) ?></h1>
        <span class="sd-badge" style="background:<?= ServiceController::statusColor($record['status']) ?>;"><?= $record['status'] ?></span>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <a href="?page=service&action=thermalReceipt&amp;id=<?= (int) $record['id'] ?>&amp;autoprint=1" target="_blank" rel="noopener noreferrer" class="sd-btn sd-btn-custom" style="text-decoration:none;font-size:.78rem;padding:6px 12px;" title="Opens narrow receipt; print dialog for thermal printer">
                <i class="bi bi-receipt-cutoff"></i> Thermal receipt
            </a>
            <span style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($record['received_date'] ?: $record['created_at'])) ?></span>
        </div>
    </div>

    <!-- Public tracking link -->
    <div class="sd-track">
        <i class="bi bi-link-45deg" style="color:#16a34a;"></i>
        <span>Customer tracking:</span>
        <a href="<?= htmlspecialchars($trackUrl) ?>" target="_blank" rel="noopener noreferrer" id="trackUrl"><?= htmlspecialchars($trackUrl) ?></a>
        <button type="button" class="sd-copy" id="svcTrackCopyBtn">Copy</button>
    </div>

    <!-- Journey -->
    <div class="sd-card">
        <div class="sd-sep"><span><i class="bi bi-signpost-split"></i> Device Journey</span></div>
        <div class="sd-journey">
            <?php foreach ($stages as $k => $s):
                // stage=3 means Replaced (factory fault): show 0,1,2 as done, Delivered as future
                if ($currentStage === 3) {
                    $class = $k <= 2 ? 'done' : '';
                } else {
                    $class = $k < $currentStage ? 'done' : ($k === $currentStage ? 'active' : '');
                }
            ?>
            <div class="sd-step <?= $class ?>" style="color:<?= $s['color'] ?>;">
                <div class="sd-step-icon"><i class="bi <?= $s['icon'] ?>"></i></div>
                <div class="sd-step-label"><?= $s['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($currentStage === 3): ?>
        <div style="text-align:center;padding:6px 16px 12px;">
            <span style="background:rgba(139,92,246,0.1);color:#8b5cf6;padding:3px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;"><i class="bi bi-arrow-repeat me-1"></i>Device Replaced (Factory Fault)</span>
        </div>
        <?php endif; ?>

        <?php if (Auth::can('service', 'edit') && $currentStage < 4): ?>
        <div class="sd-actions" style="border-top:1px solid var(--border-color);">
            <?php
                // Normal flow: 0→1→2→4 (skip stage 3)
                $nextStage = $currentStage + 1;
                if ($nextStage === 3) $nextStage = 4;
                if (isset($stages[$nextStage])):
                    $ns = $stages[$nextStage];
            ?>
            <form method="POST" action="?page=service&action=updateStage" style="display:inline;" onsubmit="return confirm('Move to <?= $ns['label'] ?>?');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                <input type="hidden" name="stage" value="<?= $nextStage ?>">
                <button type="submit" class="sd-btn sd-btn-next"><i class="bi <?= $ns['icon'] ?>"></i> Move to <?= $ns['label'] ?></button>
            </form>
            <?php endif; ?>

            <!-- Factory fault replacement — rare action -->
            <?php if ($currentStage !== 3 && $record['status'] !== 'Replaced'): ?>
            <form method="POST" action="?page=service&action=updateStage" style="display:inline;" onsubmit="return confirm('Mark as Replaced (factory fault only)?');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                <input type="hidden" name="stage" value="3">
                <button type="submit" class="sd-btn" style="background:transparent;border:1px solid #d1d5db;color:#9ca3af;font-size:0.78rem;padding:4px 10px;" title="Only for factory fault cases"><i class="bi bi-arrow-repeat"></i> Factory Replacement</button>
            </form>
            <?php endif; ?>

            <!-- Warranty void / customer damage: return without fixing -->
            <?php if ($currentStage < 4): ?>
            <form method="POST" action="?page=service&action=returnNoRepair" style="display:inline;" onsubmit="return confirm('Return this device without fixing (warranty void)?');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                <input type="hidden" name="note" value="No repair & delivered (warranty void / customer damage).">
                <button type="submit" class="sd-btn" style="background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.18);">
                    <i class="bi bi-arrow-return-left"></i> Return Without Fixing
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Customer -->
    <div class="sd-card">
        <div class="sd-sep"><span><i class="bi bi-person"></i> Customer</span></div>
        <div class="sd-rows">
            <div class="sd-row"><span class="sd-row-label">Name</span><span class="sd-row-value"><?= htmlspecialchars($record['party_name'] ?: $record['customer_name']) ?></span></div>
            <?php if ($record['customer_phone']): ?>
            <div class="sd-row"><span class="sd-row-label">Phone</span><span class="sd-row-value"><a href="tel:<?= htmlspecialchars($record['customer_phone']) ?>"><?= htmlspecialchars($record['customer_phone']) ?></a></span></div>
            <?php endif; ?>
            <?php if ($record['party_id']): ?>
            <div class="sd-row"><span class="sd-row-label">Party</span><span class="sd-row-value"><a href="?page=parties&action=detail&id=<?= $record['party_id'] ?>">View Ledger <i class="bi bi-box-arrow-up-right"></i></a></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Device Details -->
    <div class="sd-card">
        <div class="sd-sep"><span><i class="bi bi-phone"></i> Device & Fault</span></div>
        <div class="sd-rows">
            <div class="sd-row"><span class="sd-row-label">IMEI</span><span class="sd-row-value" style="font-family:monospace;"><a href="?page=imei&action=lifecycle&imei=<?= urlencode($record['imei']) ?>"><?= htmlspecialchars($record['imei']) ?></a></span></div>
            <?php if ($record['device_brand'] || $record['device_model']): ?>
            <div class="sd-row"><span class="sd-row-label">Device</span><span class="sd-row-value"><?= htmlspecialchars(trim($record['device_brand'] . ' ' . $record['device_model'])) ?></span></div>
            <?php endif; ?>
            <?php if ($record['fault_category']): ?>
            <div class="sd-row"><span class="sd-row-label">Fault Category</span><span class="sd-row-value"><?= htmlspecialchars($record['fault_category']) ?></span></div>
            <?php endif; ?>
            <?php if ($record['fault_description']): ?>
            <div class="sd-row"><span class="sd-row-label">Description</span><span class="sd-row-value" style="max-width:60%;text-align:right;white-space:pre-wrap;"><?= htmlspecialchars($record['fault_description']) ?></span></div>
            <?php endif; ?>
            <?php if ($record['technician_name']): ?>
            <div class="sd-row"><span class="sd-row-label">Technician</span><span class="sd-row-value"><?= htmlspecialchars($record['technician_name']) ?></span></div>
            <?php endif; ?>
            <div class="sd-row"><span class="sd-row-label">Repair Cost</span><span class="sd-row-value"><?= $curr ?> <?= number_format((float)$record['repair_cost'], DECIMAL_PLACES) ?></span></div>
            <?php if ($record['delivered_date']): ?>
            <div class="sd-row"><span class="sd-row-label">Delivered</span><span class="sd-row-value"><?= date('d M Y', strtotime($record['delivered_date'])) ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History -->
    <?php if (!empty($history)): ?>
    <div class="sd-card">
        <div class="sd-sep"><span><i class="bi bi-clock-history"></i> History (<?= count($history) ?>)</span></div>
        <div class="sd-hist">
            <?php foreach ($history as $h): ?>
            <div class="sd-hist-item">
                <div class="sd-hist-dot" style="background:<?= $h['event_type'] === 'created' ? '#6366f1' : '#3b82f6' ?>;"></div>
                <div class="sd-hist-body">
                    <div class="sd-hist-head"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $h['event_type']))) ?>: <?= htmlspecialchars($h['new_value'] ?? '') ?></div>
                    <?php if ($h['note']): ?><div style="font-size:.78rem;margin-top:2px;"><?= htmlspecialchars($h['note']) ?></div><?php endif; ?>
                    <div class="sd-hist-meta"><?= date('d M Y h:i A', strtotime($h['created_at'])) ?><?= $h['user_name'] ? ' · ' . htmlspecialchars($h['user_name']) : '' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit / Delete -->
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
        <?php if (Auth::can('service', 'edit')): ?>
        <a href="?page=service&action=edit&id=<?= $record['id'] ?>" class="sd-btn pin-protect" style="background:rgba(245,158,11,.1);color:#f59e0b;border:1px solid rgba(245,158,11,.2);text-decoration:none;"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <?php if (Auth::can('service', 'delete')): ?>
        <form method="POST" action="?page=service&action=delete" style="display:inline;" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($record['service_no'])) ?> permanently?');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= $record['id'] ?>">
            <button type="submit" class="sd-btn sd-btn-del pin-protect"><i class="bi bi-trash"></i> Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('svcTrackCopyBtn');
    var a = document.getElementById('trackUrl');
    if (!btn || !a) return;

    btn.addEventListener('click', function () {
        var text = a.textContent || '';
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = 'Copy'; }, 1200);
            }).catch(function () {
                prompt('Copy this URL:', text);
            });
        } else {
            prompt('Copy this URL:', text);
        }
    });
});
</script>
