<?php
$stages = ServiceController::stages();
?>
<style>
.sv-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px; }
.sv-head h1 { font-size:1.15rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }

.sv-stats { display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px; }
.sv-stat { background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px; }
.sv-stat-icon { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
.sv-stat-value { font-size:1.2rem;font-weight:800;color:var(--text-main); }
.sv-stat-label { font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px; }

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
</style>

<div class="sv-head">
    <h1><i class="bi bi-tools" style="color:var(--primary);"></i> Service Center</h1>
    <?php if (Auth::can('service', 'add')): ?>
    <a href="?page=service&action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Service</a>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="sv-stats">
    <div class="sv-stat">
        <div class="sv-stat-icon" style="background:rgba(99,102,241,.1);color:var(--primary);"><i class="bi bi-list-ul"></i></div>
        <div><div class="sv-stat-value"><?= $counts['total'] ?? 0 ?></div><div class="sv-stat-label">Total</div></div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon" style="background:rgba(245,158,11,.1);color:#f59e0b;"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="sv-stat-value"><?= $counts['pending'] ?? 0 ?></div><div class="sv-stat-label">Pending</div></div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="bi bi-tools"></i></div>
        <div><div class="sv-stat-value"><?= $counts['in_progress'] ?? 0 ?></div><div class="sv-stat-label">In Progress</div></div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon" style="background:rgba(34,197,94,.1);color:#22c55e;"><i class="bi bi-check-circle"></i></div>
        <div><div class="sv-stat-value"><?= $counts['completed'] ?? 0 ?></div><div class="sv-stat-label">Completed</div></div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6;"><i class="bi bi-arrow-repeat"></i></div>
        <div><div class="sv-stat-value"><?= $counts['replaced'] ?? 0 ?></div><div class="sv-stat-label">Replaced</div></div>
    </div>
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
                <?php foreach (['Pending','In Progress','Completed','Replaced'] as $s): ?>
                <option value="<?= $s ?>" <?= $filters['status']===$s?'selected':'' ?>><?= $s ?></option>
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
                    <th>Date</th>
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
                ?>
                <tr>
                    <td><a href="?page=service&action=detail&id=<?= $r['id'] ?>"><?= htmlspecialchars($r['service_no']) ?></a></td>
                    <td style="color:var(--text-muted);font-size:.8rem;"><?= date('d M Y', strtotime($r['received_date'] ?: $r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($customer ?: '—') ?><?php if ($r['customer_phone']): ?><br><small style="color:var(--text-muted);"><?= htmlspecialchars($r['customer_phone']) ?></small><?php endif; ?></td>
                    <td><?= htmlspecialchars(trim($r['device_brand'] . ' ' . $r['device_model']) ?: '—') ?></td>
                    <td class="sv-imei"><?= htmlspecialchars($r['imei']) ?></td>
                    <td><span class="sv-badge" style="background:<?= $st['color'] ?>15;color:<?= $st['color'] ?>;"><i class="bi <?= $st['icon'] ?>"></i> <?= $st['label'] ?></span></td>
                    <td><span class="sv-badge" style="background:<?= ServiceController::statusColor($r['status']) ?>15;color:<?= ServiceController::statusColor($r['status']) ?>;"><?= $r['status'] ?></span></td>
                    <td class="sv-actions">
                        <a href="?page=service&action=detail&id=<?= $r['id'] ?>" class="sv-act view" title="View"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
