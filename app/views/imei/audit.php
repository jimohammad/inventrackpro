<style>
.au-page { max-width: 980px; }
.au-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:18px; }
.au-head h1 { font-size:1.18rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px; }
.au-head .au-wh { font-size:.78rem;color:var(--text-muted); }
.au-head .au-wh strong { color:var(--text-main); }

.au-info { background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.84rem;color:#3730a3;display:flex;gap:8px;align-items:flex-start; }
.au-info i { font-size:1rem;flex-shrink:0;margin-top:2px; }

.au-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden; }
.au-tbl { width:100%;border-collapse:collapse;font-size:.85rem; }
.au-tbl th { padding:11px 14px;font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;background:var(--bg-main);border-bottom:2px solid var(--border-color);text-align:left; }
.au-tbl td { padding:10px 14px;border-bottom:1px solid var(--border-color);vertical-align:middle; }
.au-tbl tr:hover { background:rgba(99,102,241,.04); }

.au-delta { font-weight:800;font-family:monospace; }
.au-delta.surplus { color:#dc2626; }
.au-delta.deficit { color:#f59e0b; }

.au-num { font-family:monospace;font-weight:700; }
.au-num.stock { color:#3b82f6; }
.au-num.imei  { color:#7c3aed; }

.au-fix { padding:6px 14px;background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;border:none;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px; }
.au-fix:hover { opacity:.9;color:#fff; }

.au-empty { text-align:center;padding:50px 20px;color:var(--text-muted); }
.au-empty i { font-size:2.5rem;color:#22c55e;display:block;margin-bottom:10px; }
.au-empty p { margin:6px 0;font-size:.92rem; }
.au-empty .ok { color:#16a34a;font-weight:700; }
</style>

<div class="au-page">
    <div class="au-head">
        <h1><i class="bi bi-clipboard-check" style="color:var(--primary);"></i> Stock Audit — IMEI Reconciliation</h1>
        <div class="au-wh">Warehouse: <strong><?= htmlspecialchars($currentWh['name'] ?? '—') ?></strong></div>
    </div>

    <div class="au-info">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            Lists items where IMEI count does not match stock quantity. For each mismatched item, click <strong>Reconcile</strong>
            and scan all phones physically present. Unscanned IMEIs will be marked as <strong>transferred</strong>.
        </div>
    </div>

    <div class="au-card">
        <?php if (empty($mismatches)): ?>
        <div class="au-empty">
            <i class="bi bi-check-circle-fill"></i>
            <p class="ok">All IMEI counts match stock!</p>
            <p style="font-size:.82rem;">No reconciliation needed for this warehouse.</p>
        </div>
        <?php else: ?>
        <table class="au-tbl">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align:center;">Stock Qty</th>
                    <th style="text-align:center;">IMEI Count</th>
                    <th style="text-align:center;">Δ</th>
                    <th style="text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mismatches as $m):
                    $delta = (int)$m['imei_count'] - (int)$m['stock'];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text-main);"><?= htmlspecialchars($m['name']) ?></div>
                        <?php if ($m['sku']): ?><div style="font-size:.72rem;color:var(--text-muted);font-family:monospace;"><?= htmlspecialchars($m['sku']) ?></div><?php endif; ?>
                    </td>
                    <td style="text-align:center;"><span class="au-num stock"><?= (int)$m['stock'] ?></span></td>
                    <td style="text-align:center;"><span class="au-num imei"><?= (int)$m['imei_count'] ?></span></td>
                    <td style="text-align:center;">
                        <span class="au-delta <?= $delta > 0 ? 'surplus' : 'deficit' ?>">
                            <?= $delta > 0 ? '+' : '' ?><?= $delta ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <a href="?page=imei&action=audit&item_id=<?= $m['id'] ?>" class="au-fix">
                            <i class="bi bi-tools"></i> Reconcile
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
