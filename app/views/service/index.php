<?php
$stages = ServiceController::stages();
?>
<style>
.sv-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px; }
.sv-head h1 { font-size:1.15rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }

.sv-stats { display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px; }
.sv-stat { background:var(--bg-card);border:1.5px solid var(--border-color);border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;cursor:pointer;transition:all .18s;box-shadow:0 1px 4px rgba(0,0,0,.05); }
.sv-stat:hover { transform:translateY(-1px);box-shadow:0 4px 16px rgba(0,0,0,.1); }
.sv-stat-icon { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0; }
.sv-stat-value { font-size:1.6rem;font-weight:800;color:var(--text-main);line-height:1.1; }
.sv-stat-label { font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:1px; }

.sv-filters { background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:12px;padding:14px 18px;margin-bottom:16px; }
.sv-filters form { display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end; }
.sv-filters .f-field { flex:1;min-width:140px; }
.sv-filters label { display:block;font-size:.68rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px; }
.sv-filters input, .sv-filters select { width:100%;padding:7px 12px;border:1.5px solid #c7d2fe;border-radius:8px;font-size:.85rem;background:#fff;outline:none; }

.sv-tbl { width:100%;border-collapse:collapse;font-size:.84rem; }
.sv-tbl th { padding:10px 12px;font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;background:var(--bg-main);border-bottom:2px solid var(--border-color);text-align:left; }
.sv-tbl td { padding:8px 12px;border-bottom:1px solid var(--border-color);vertical-align:middle; }
.sv-tbl tr:hover { background:rgba(99,102,241,.04); }
.sv-tbl a { color:var(--primary);text-decoration:none;font-weight:600; }
.sv-tbl a:hover { text-decoration:underline; }

.sv-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700; }
.sv-imei { font-family:monospace;font-size:.78rem;color:var(--text-muted); }
.sv-actions { display:flex;gap:6px; }
.sv-act { width:26px;height:26px;border-radius:6px;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;text-decoration:none; }
.sv-act.view { background:rgba(99,102,241,.1);color:var(--primary); }
.sv-act.view:hover { background:rgba(99,102,241,.2); }
.sv-act.edit { background:rgba(245,158,11,.1);color:#f59e0b; }
.sv-act.edit:hover { background:rgba(245,158,11,.2); }
.sv-act.del { background:rgba(239,68,68,.08);color:#ef4444; border:none;cursor:pointer; }
.sv-act.del:hover { background:rgba(239,68,68,.18); }
.sv-act.track { background:rgba(14,165,233,.12); color:#0ea5e9; }
.sv-act.track:hover { background:rgba(14,165,233,.22); }
.sv-act.track:disabled { opacity:.45; cursor:not-allowed; filter:none; }
.sv-act.track.copied { background:rgba(34,197,94,.18); color:#16a34a; }

.sv-status-edit {
    --sv-accent: #6b7280;
    --sv-bg: rgba(107,114,128,.10);
    display:flex;
    align-items:center;
    gap:8px;
    white-space:nowrap;
}
.sv-status-dot{
    width:8px;height:8px;border-radius:50%;
    background:var(--sv-accent);
    box-shadow:0 0 0 3px var(--sv-bg);
    flex-shrink:0;
}
.sv-status-edit select {
    padding:6px 28px 6px 10px;
    border:1.6px solid rgba(148,163,184,.55);
    border-radius:999px;
    background:linear-gradient(180deg, rgba(255,255,255,.14), rgba(255,255,255,.04)), var(--sv-bg);
    color:var(--text-main);
    font-size:.76rem;
    font-weight:800;
    letter-spacing:.1px;
    outline:none;
    min-width:140px;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease, filter .12s ease;
}
[data-theme="light"] .sv-status-edit select{
    background:linear-gradient(180deg, rgba(255,255,255,.9), rgba(255,255,255,.72)), var(--sv-bg);
}
.sv-status-edit select:hover{
    filter:brightness(1.03);
}
.sv-status-edit select:focus{
    border-color: var(--sv-accent);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--sv-accent) 18%, transparent);
}
.sv-status-edit.is-saving select{
    opacity:.75;
    cursor:wait;
}
.sv-status-edit.is-saving .sv-status-dot{
    animation: svPulse 0.9s ease-in-out infinite;
}
@keyframes svPulse{
  0%,100% { transform: scale(1); opacity: .9; }
  50% { transform: scale(1.25); opacity: .6; }
}
.sv-status-saving { font-size:.72rem; color:var(--text-muted); display:none; }
.sv-row-updated { animation: svFlash 0.9s ease; }
@keyframes svFlash {
  0% { background: rgba(16,185,129,.12); }
  100% { background: transparent; }
}

.sv-delivered-date { font-size:.78rem;color:#10b981;font-weight:600;white-space:nowrap; }
.sv-received-sub { font-size:.7rem;color:var(--text-muted); }

.sv-delivery-modal {
    position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;
    background:rgba(15,23,42,.45);padding:16px;
}
.sv-delivery-modal.is-open { display:flex; }
.sv-delivery-dialog {
    width:100%;max-width:380px;background:var(--bg-card);border:1px solid var(--border-color);
    border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.2);overflow:hidden;
}
.sv-delivery-head { padding:16px 18px 8px;font-weight:700;font-size:.95rem; }
.sv-delivery-body { padding:0 18px 16px; }
.sv-delivery-body label { display:block;font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px; }
.sv-delivery-body input[type=date] {
    width:100%;padding:10px 12px;border:1.5px solid var(--border-color);border-radius:10px;
    font-size:.9rem;background:var(--bg-main);color:var(--text-main);
}
.sv-delivery-foot { display:flex;gap:8px;justify-content:flex-end;padding:12px 18px 16px;border-top:1px solid var(--border-color); }
</style>

<div class="sv-head">
    <h1><i class="bi bi-tools" style="color:var(--primary);"></i> Service Center</h1>
    <?php if (Auth::can('service', 'add')): ?>
    <a href="?page=service&action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Service</a>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="sv-stats">
    <a href="?page=service" class="sv-stat" style="<?= (!$filters['status'] && $filters['stage']==='') ? 'border-color:#6366f1;box-shadow:0 4px 14px rgba(99,102,241,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;box-shadow:0 3px 10px rgba(99,102,241,.35);"><i class="bi bi-list-ul"></i></div>
        <div><div class="sv-stat-value"><?= $counts['total'] ?? 0 ?></div><div class="sv-stat-label">Total</div></div>
    </a>
    <a href="?page=service&status=Pending" class="sv-stat" style="<?= $filters['status']==='Pending' ? 'border-color:#f59e0b;box-shadow:0 4px 14px rgba(245,158,11,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;box-shadow:0 3px 10px rgba(245,158,11,.35);"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="sv-stat-value"><?= $counts['pending'] ?? 0 ?></div><div class="sv-stat-label">Pending</div></div>
    </a>
    <a href="?page=service&status=In+Progress" class="sv-stat" style="<?= $filters['status']==='In Progress' ? 'border-color:#3b82f6;box-shadow:0 4px 14px rgba(59,130,246,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);color:#fff;box-shadow:0 3px 10px rgba(59,130,246,.35);"><i class="bi bi-tools"></i></div>
        <div><div class="sv-stat-value"><?= $counts['in_progress'] ?? 0 ?></div><div class="sv-stat-label">In Progress</div></div>
    </a>
    <a href="?page=service&status=Fixed" class="sv-stat" style="<?= $filters['status']==='Fixed' ? 'border-color:#22c55e;box-shadow:0 4px 14px rgba(34,197,94,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#22c55e,#4ade80);color:#fff;box-shadow:0 3px 10px rgba(34,197,94,.35);"><i class="bi bi-check-circle"></i></div>
        <div><div class="sv-stat-value"><?= $counts['fixed'] ?? 0 ?></div><div class="sv-stat-label">Fixed</div></div>
    </a>
    <a href="?page=service&status=Replaced" class="sv-stat" style="<?= $filters['status']==='Replaced' ? 'border-color:#8b5cf6;box-shadow:0 4px 14px rgba(139,92,246,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff;box-shadow:0 3px 10px rgba(139,92,246,.35);"><i class="bi bi-arrow-repeat"></i></div>
        <div><div class="sv-stat-value"><?= $counts['replaced'] ?? 0 ?></div><div class="sv-stat-label">Replaced</div></div>
    </a>

    <a href="?page=service&status=No+Repair" class="sv-stat" style="<?= $filters['status']==='No Repair' ? 'border-color:#ef4444;box-shadow:0 4px 14px rgba(239,68,68,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);color:#fff;box-shadow:0 3px 10px rgba(239,68,68,.35);"><i class="bi bi-shield-x"></i></div>
        <div><div class="sv-stat-value"><?= $counts['no_repair'] ?? 0 ?></div><div class="sv-stat-label">No Repair</div></div>
    </a>
    <a href="?page=service&stage=4" class="sv-stat" style="<?= $filters['stage']==='4' ? 'border-color:#10b981;box-shadow:0 4px 14px rgba(16,185,129,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);color:#fff;box-shadow:0 3px 10px rgba(16,185,129,.35);"><i class="bi bi-bag-check"></i></div>
        <div><div class="sv-stat-value"><?= $counts['delivered'] ?? 0 ?></div><div class="sv-stat-label">Delivered</div></div>
    </a>
</div>

<!-- Filters -->
<div class="sv-filters">
    <form method="GET">
        <input type="hidden" name="page" value="service">
        <div class="f-field" style="flex:2;min-width:220px;">
            <label><i class="bi bi-search me-1"></i> Search</label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Service #, IMEI, customer, model...">
        </div>
        <div class="f-field">
            <label>Status</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach (ServiceController::allowedStatuses() as $s): ?>
                <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['status']===$s?'selected':'' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="f-field">
            <label>Stage</label>
            <select name="stage" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach ($stages as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filters['stage']!==''&&(int)$filters['stage']===$k?'selected':'' ?>><?= $v['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i></button></div>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
            <p style="margin-top:10px;">No service records yet</p>
        </div>
        <?php else: ?>
        <table class="sv-tbl">
            <thead>
                <tr>
                    <th>Service #</th>
                    <th>Received</th>
                    <th>Delivered</th>
                    <th>Customer</th>
                    <th>Device</th>
                    <th>IMEI</th>
                    <th>Stage</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r):
                    $st = $stages[(int)$r['device_stage']] ?? $stages[0];
                    $customer = $r['party_name'] ?: $r['customer_name'];
                    $trackTok = trim((string)($r['tracking_token'] ?? ''));
                    $trackUrl = $trackTok !== '' ? app_service_track_url($trackTok) : '';
                ?>
                <tr>
                    <td><a href="?page=service&action=detail&id=<?= $r['id'] ?>"><?= htmlspecialchars($r['service_no']) ?></a></td>
                    <td style="color:var(--text-muted);font-size:.8rem;">
                        <?= date('d M Y', strtotime($r['received_date'] ?: $r['created_at'])) ?>
                    </td>
                    <td>
                        <?php if (!empty($r['delivered_date'])): ?>
                        <span class="sv-delivered-date" data-delivered-cell="<?= (int)$r['id'] ?>"><?= date('d M Y', strtotime($r['delivered_date'])) ?></span>
                        <?php else: ?>
                        <span class="sv-received-sub" data-delivered-cell="<?= (int)$r['id'] ?>">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($customer ?: '—') ?><?php if ($r['customer_phone']): ?><br><small style="color:var(--text-muted);"><?= htmlspecialchars($r['customer_phone']) ?></small><?php endif; ?></td>
                    <td><?= htmlspecialchars(trim($r['device_brand'] . ' ' . $r['device_model']) ?: '—') ?></td>
                    <td class="sv-imei"><?= htmlspecialchars($r['imei']) ?></td>
                    <td><span class="sv-badge" style="background:<?= $st['color'] ?>15;color:<?= $st['color'] ?>;"><i class="bi <?= $st['icon'] ?>"></i> <?= $st['label'] ?></span></td>
                    <td>
                        <?php if (Auth::can('service', 'edit')): ?>
                        <div class="sv-status-edit" data-service-id="<?= (int)$r['id'] ?>" data-status="<?= htmlspecialchars($r['status']) ?>" data-delivered-date="<?= htmlspecialchars($r['delivered_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <span class="sv-status-dot" aria-hidden="true"></span>
                            <select class="sv-status-select" aria-label="Change status">
                                <?php foreach (ServiceController::allowedStatuses() as $s): ?>
                                <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $r['status']===$s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sv-status-saving">Saving…</span>
                        </div>
                        <?php else: ?>
                        <span class="sv-badge" style="background:<?= ServiceController::statusColor($r['status']) ?>15;color:<?= ServiceController::statusColor($r['status']) ?>;"><?= $r['status'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="sv-actions">
                        <button type="button"
                            class="sv-act track svc-copy-track"
                            data-track-url="<?= htmlspecialchars($trackUrl, ENT_QUOTES, 'UTF-8') ?>"
                            title="Copy tracking URL"
                            aria-label="Copy tracking URL"
                            <?= $trackUrl === '' ? 'disabled' : '' ?>>
                            <i class="bi bi-link-45deg"></i>
                        </button>
                        <a href="?page=service&action=thermalReceipt&amp;id=<?= (int) $r['id'] ?>&amp;autoprint=1" class="sv-act view" style="background:rgba(5,150,105,.12);color:#059669;" target="_blank" rel="noopener noreferrer" title="Thermal customer receipt"><i class="bi bi-receipt-cutoff"></i></a>
                        <a href="?page=service&action=detail&id=<?= $r['id'] ?>" class="sv-act view" title="View"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::can('service', 'edit')): ?>
                        <a href="?page=service&action=edit&id=<?= $r['id'] ?>" class="sv-act edit pin-protect" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::can('service', 'delete')): ?>
                        <form method="POST" action="?page=service&action=delete" style="display:inline;" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($r['service_no'])) ?>?');">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="sv-act del pin-protect" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div id="svDeliveryModal" class="sv-delivery-modal" role="dialog" aria-modal="true" aria-labelledby="svDeliveryModalTitle">
    <div class="sv-delivery-dialog">
        <div class="sv-delivery-head" id="svDeliveryModalTitle">Delivery date</div>
        <div class="sv-delivery-body">
            <label for="svDeliveryDateInput">When was the device delivered to the customer?</label>
            <input type="date" id="svDeliveryDateInput" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="sv-delivery-foot">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="svDeliveryCancel">Cancel</button>
            <button type="button" class="btn btn-sm btn-primary" id="svDeliveryConfirm">Save status</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrf = '<?= Auth::csrfToken() ?>';
    var DELIVERED_STATUSES = ['Fixed & Delivered', 'Replaced & Delivered', 'No Repair & Delivered'];

    function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                ok ? resolve() : reject(new Error('execCommand failed'));
            } catch (e) {
                document.body.removeChild(ta);
                reject(e);
            }
        });
    }

    document.querySelectorAll('.svc-copy-track').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = btn.getAttribute('data-track-url') || '';
            if (!url) {
                alert('No tracking token for this service.');
                return;
            }
            copyTextToClipboard(url)
                .then(function() {
                    btn.classList.add('copied');
                    var prev = btn.getAttribute('title') || '';
                    btn.setAttribute('title', 'Copied!');
                    setTimeout(function() {
                        btn.classList.remove('copied');
                        btn.setAttribute('title', prev || 'Copy tracking URL');
                    }, 1200);
                })
                .catch(function() {
                    prompt('Copy this tracking URL:', url);
                });
        });
    });

    function applyStatusTheme(wrap, status) {
        var accent = '#6b7280';
        var bg = 'rgba(107,114,128,.10)';
        if (status === 'Pending') { accent = '#f59e0b'; bg = 'rgba(245,158,11,.14)'; }
        if (status === 'In Progress') { accent = '#3b82f6'; bg = 'rgba(59,130,246,.14)'; }
        if (status === 'Fixed') { accent = '#22c55e'; bg = 'rgba(34,197,94,.14)'; }
        if (status === 'Fixed & Delivered') { accent = '#10b981'; bg = 'rgba(16,185,129,.14)'; }
        if (status === 'Replaced') { accent = '#8b5cf6'; bg = 'rgba(139,92,246,.14)'; }
        if (status === 'Replaced & Delivered') { accent = '#7c3aed'; bg = 'rgba(124,58,237,.14)'; }
        if (status === 'No Repair') { accent = '#ef4444'; bg = 'rgba(239,68,68,.14)'; }
        if (status === 'No Repair & Delivered') { accent = '#b91c1c'; bg = 'rgba(185,28,28,.14)'; }
        wrap.style.setProperty('--sv-accent', accent);
        wrap.style.setProperty('--sv-bg', bg);
        wrap.setAttribute('data-status', status);
    }

    function isDeliveredStatus(status) {
        return DELIVERED_STATUSES.indexOf(status) !== -1;
    }

    function formatDeliveredLabel(ymd) {
        if (!ymd) return '—';
        var parts = ymd.split('-');
        if (parts.length !== 3) return ymd;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var m = parseInt(parts[1], 10) - 1;
        return parts[2] + ' ' + (months[m] || parts[1]) + ' ' + parts[0];
    }

    function updateDeliveredCell(serviceId, ymd) {
        var cell = document.querySelector('[data-delivered-cell="' + serviceId + '"]');
        if (!cell) return;
        if (ymd) {
            cell.textContent = formatDeliveredLabel(ymd);
            cell.className = 'sv-delivered-date';
        } else {
            cell.textContent = '—';
            cell.className = 'sv-received-sub';
        }
    }

    var deliveryModal = document.getElementById('svDeliveryModal');
    var deliveryDateInput = document.getElementById('svDeliveryDateInput');
    var deliveryCancelBtn = document.getElementById('svDeliveryCancel');
    var deliveryConfirmBtn = document.getElementById('svDeliveryConfirm');
    var pendingDelivery = null;

    function closeDeliveryModal() {
        if (deliveryModal) deliveryModal.classList.remove('is-open');
        pendingDelivery = null;
    }

    function openDeliveryModal(wrap, select, status) {
        if (!deliveryModal || !deliveryDateInput) {
            submitStatusUpdate(wrap, select, status, '');
            return;
        }
        pendingDelivery = { wrap: wrap, select: select, status: status, prev: wrap.getAttribute('data-status') };
        var existing = wrap.getAttribute('data-delivered-date') || '';
        deliveryDateInput.value = existing || new Date().toISOString().slice(0, 10);
        deliveryModal.classList.add('is-open');
        deliveryDateInput.focus();
    }

    if (deliveryCancelBtn) {
        deliveryCancelBtn.addEventListener('click', function() {
            if (pendingDelivery) {
                pendingDelivery.select.value = pendingDelivery.prev;
                applyStatusTheme(pendingDelivery.wrap, pendingDelivery.prev);
            }
            closeDeliveryModal();
        });
    }

    if (deliveryConfirmBtn) {
        deliveryConfirmBtn.addEventListener('click', function() {
            if (!pendingDelivery) return;
            var dateVal = deliveryDateInput ? deliveryDateInput.value : '';
            if (!dateVal) {
                alert('Please choose a delivery date.');
                return;
            }
            var ctx = pendingDelivery;
            closeDeliveryModal();
            submitStatusUpdate(ctx.wrap, ctx.select, ctx.status, dateVal);
        });
    }

    if (deliveryModal) {
        deliveryModal.addEventListener('click', function(e) {
            if (e.target === deliveryModal) {
                if (pendingDelivery) {
                    pendingDelivery.select.value = pendingDelivery.prev;
                    applyStatusTheme(pendingDelivery.wrap, pendingDelivery.prev);
                }
                closeDeliveryModal();
            }
        });
    }

    function submitStatusUpdate(wrap, select, status, deliveredDate) {
        var id = wrap.getAttribute('data-service-id');
        var saving = wrap.querySelector('.sv-status-saving');
        if (!id) return;

        if (saving) saving.style.display = 'inline';
        select.disabled = true;
        wrap.classList.add('is-saving');
        applyStatusTheme(wrap, status);

        var body = new URLSearchParams();
        body.set('csrf_token', csrf);
        body.set('id', id);
        body.set('status', status);
        if (deliveredDate) body.set('delivered_date', deliveredDate);

        fetch('?page=service&action=updateStatus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.success) {
                throw new Error((res && res.message) ? res.message : 'Failed');
            }
            wrap.setAttribute('data-status', status);
            if (res.delivered_date) {
                wrap.setAttribute('data-delivered-date', res.delivered_date);
            } else {
                wrap.removeAttribute('data-delivered-date');
            }
            updateDeliveredCell(id, res.delivered_date || '');
            var tr = wrap.closest('tr');
            if (tr) {
                tr.classList.remove('sv-row-updated');
                void tr.offsetWidth;
                tr.classList.add('sv-row-updated');
            }
        })
        .catch(function(err) {
            select.value = wrap.getAttribute('data-status') || select.value;
            applyStatusTheme(wrap, select.value);
            alert('Status update failed: ' + (err && err.message ? err.message : 'Unknown error'));
        })
        .finally(function() {
            if (saving) saving.style.display = 'none';
            select.disabled = false;
            wrap.classList.remove('is-saving');
        });
    }

    document.querySelectorAll('.sv-status-edit').forEach(function(wrap) {
        var select = wrap.querySelector('.sv-status-select');
        if (!select) return;

        applyStatusTheme(wrap, select.value);

        select.addEventListener('change', function() {
            var status = select.value;
            var prev = wrap.getAttribute('data-status') || select.value;
            if (isDeliveredStatus(status)) {
                openDeliveryModal(wrap, select, status);
                return;
            }
            submitStatusUpdate(wrap, select, status, '');
        });
    });
});
</script>
