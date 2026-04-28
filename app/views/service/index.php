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
    <a href="?page=service&status=Completed" class="sv-stat" style="<?= $filters['status']==='Completed' ? 'border-color:#22c55e;box-shadow:0 4px 14px rgba(34,197,94,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#22c55e,#4ade80);color:#fff;box-shadow:0 3px 10px rgba(34,197,94,.35);"><i class="bi bi-check-circle"></i></div>
        <div><div class="sv-stat-value"><?= $counts['completed'] ?? 0 ?></div><div class="sv-stat-label">Completed</div></div>
    </a>
    <a href="?page=service&status=Replaced" class="sv-stat" style="<?= $filters['status']==='Replaced' ? 'border-color:#8b5cf6;box-shadow:0 4px 14px rgba(139,92,246,.18);' : '' ?>">
        <div class="sv-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff;box-shadow:0 3px 10px rgba(139,92,246,.35);"><i class="bi bi-arrow-repeat"></i></div>
        <div><div class="sv-stat-value"><?= $counts['replaced'] ?? 0 ?></div><div class="sv-stat-label">Replaced</div></div>
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

