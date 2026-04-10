<?php
$curr = APP_CURRENCY;
$dp = DECIMAL_PLACES;
$isPaid = $purchase['balance'] < 0.01;
$statusColor = $isPaid ? '#22c55e' : '#f59e0b';
$statusLabel = ucfirst($purchase['status']);
?>
<style>
.pv-page { max-width:720px;margin:0 auto; }
.pv-head { display:flex;align-items:center;gap:12px;margin-bottom:20px; }
.pv-head a { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;font-size:.85rem; }
.pv-head a:hover { border-color:var(--primary);color:var(--primary); }
.pv-head h1 { font-size:1.15rem;font-weight:700;margin:0; }
.pv-badge { padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:700;color:#fff; }

/* Amount card */
.pv-amount-card {
    background:linear-gradient(135deg,#1e3a5f,#2d5a9e);
    border-radius:14px;padding:24px;text-align:center;margin-bottom:16px;
    box-shadow:0 4px 20px rgba(30,58,95,.2);
}
.pv-amount-label { font-size:.72rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:1px;font-weight:600; }
.pv-amount-value { font-size:2rem;font-weight:800;color:#fff;margin:6px 0;letter-spacing:.5px; }
.pv-amount-meta { display:flex;justify-content:center;gap:20px;margin-top:10px; }
.pv-amount-meta span { font-size:.78rem;color:rgba(255,255,255,.7); }
.pv-amount-meta strong { color:#fff; }

/* Detail card */
.pv-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;margin-bottom:12px;overflow:hidden; }
.pv-card-head { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;padding:12px 16px 0;display:flex;align-items:center;gap:6px; }
.pv-card-head i { font-size:.8rem; }

/* Detail rows */
.pv-rows { padding:10px 16px 14px; }
.pv-row { display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border-color);font-size:.84rem; }
.pv-row:last-child { border-bottom:none; }
.pv-row-label { color:var(--text-muted);font-weight:500; }
.pv-row-value { font-weight:600;color:var(--text-main);text-align:right; }
.pv-row-value a { color:var(--primary);text-decoration:none; }

/* Items table */
.pv-tbl { width:100%;border-collapse:collapse;font-size:.82rem; }
.pv-tbl th { padding:8px 12px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);background:var(--bg-main);border-bottom:1.5px solid var(--border-color); }
.pv-tbl td { padding:6px 12px;border-bottom:1px solid var(--border-color);vertical-align:middle; }
.pv-tbl tr:last-child td { border-bottom:none; }
.pv-tbl .item-name { font-weight:600;color:var(--text-main); }
.pv-tbl .imei-tag { display:inline-block;background:rgba(99,102,241,.08);color:var(--primary);font-size:.7rem;padding:1px 6px;border-radius:4px;margin:1px 2px;font-family:monospace; }

/* Payment list */
.pv-pay-item { display:flex;justify-content:space-between;align-items:center;padding:8px 16px;border-bottom:1px solid var(--border-color);font-size:.82rem; }
.pv-pay-item:last-child { border-bottom:none; }
.pv-empty { text-align:center;padding:16px;color:var(--text-muted);font-size:.82rem; }

/* Actions */
.pv-actions { display:flex;gap:8px;justify-content:center;margin-top:4px; }
.pv-btn { padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;cursor:pointer;border:none; }
.pv-btn-print { background:rgba(99,102,241,.1);color:var(--primary); }
.pv-btn-print:hover { background:rgba(99,102,241,.18); }
.pv-btn-pdf { background:rgba(34,197,94,.1);color:#16a34a; }
.pv-btn-pdf:hover { background:rgba(34,197,94,.18); }
</style>

<div class="pv-page">
    <!-- Header -->
    <div class="pv-head">
        <a href="?page=purchases"><i class="bi bi-arrow-left"></i></a>
        <h1><?= $purchase['invoice_no'] ?></h1>
        <span class="pv-badge" style="background:<?= $statusColor ?>;"><?= $statusLabel ?></span>
    </div>

    <!-- Amount Card -->
    <div class="pv-amount-card">
        <div class="pv-amount-label">Grand Total</div>
        <div class="pv-amount-value"><?= $curr ?> <?= number_format($purchase['grand_total'], $dp) ?></div>
        <div class="pv-amount-meta">
            <span>Paid: <strong><?= $curr ?> <?= number_format($purchase['paid_amount'], $dp) ?></strong></span>
            <span>Balance: <strong style="color:<?= $isPaid ? '#86efac' : '#fde68a' ?>;"><?= $curr ?> <?= number_format($purchase['balance'], $dp) ?></strong></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="pv-actions">
        <a href="?page=purchases&action=print&id=<?= $purchase['id'] ?>" class="pv-btn pv-btn-print" target="_blank">
            <i class="bi bi-printer"></i> Print
        </a>
        <a href="?page=purchases&action=print&id=<?= $purchase['id'] ?>&autopdf=1" class="pv-btn pv-btn-pdf" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    </div>

    <!-- Purchase Details -->
    <div class="pv-card">
        <div class="pv-card-head"><i class="bi bi-file-earmark-text"></i> Purchase Details</div>
        <div class="pv-rows">
            <div class="pv-row">
                <span class="pv-row-label">Date</span>
                <span class="pv-row-value"><?= date('d M Y', strtotime($purchase['date'])) ?></span>
            </div>
            <div class="pv-row">
                <span class="pv-row-label">Supplier</span>
                <span class="pv-row-value"><?= htmlspecialchars($purchase['party_name']) ?></span>
            </div>
            <?php if (!empty($purchase['party_phone'])): ?>
            <div class="pv-row">
                <span class="pv-row-label">Phone</span>
                <span class="pv-row-value"><a href="tel:<?= htmlspecialchars($purchase['party_phone']) ?>"><?= htmlspecialchars($purchase['party_phone']) ?></a></span>
            </div>
            <?php endif; ?>
            <div class="pv-row">
                <span class="pv-row-label">Warehouse</span>
                <span class="pv-row-value"><?= htmlspecialchars($purchase['warehouse_name'] ?? '—') ?></span>
            </div>
            <?php if (!empty($purchase['supplier_invoice_no'])): ?>
            <div class="pv-row">
                <span class="pv-row-label">Supplier Invoice</span>
                <span class="pv-row-value" style="font-family:monospace;letter-spacing:.5px;"><?= htmlspecialchars($purchase['supplier_invoice_no']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($purchase['notes'])): ?>
            <div class="pv-row">
                <span class="pv-row-label">Notes</span>
                <span class="pv-row-value" style="max-width:60%;text-align:right;"><?= htmlspecialchars($purchase['notes']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items -->
    <div class="pv-card">
        <div class="pv-card-head"><i class="bi bi-box-seam"></i> Items (<?= count($purchase['items']) ?>)</div>
        <table class="pv-tbl">
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Item</th>
                    <th style="text-align:center;width:55px;">Qty</th>
                    <th style="text-align:right;width:90px;">Price</th>
                    <th style="text-align:right;width:100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchase['items'] as $i => $item): ?>
                <tr>
                    <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                    <td>
                        <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                        <?php if (!empty($item['imei_list'])): ?>
                        <br>
                        <?php foreach (explode('||', $item['imei_list']) as $imei): ?>
                        <span class="imei-tag"><?= trim($imei) ?></span>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;font-weight:600;"><?= $item['quantity'] ?></td>
                    <td style="text-align:right;"><?= number_format($item['unit_price'], $dp) ?></td>
                    <td style="text-align:right;font-weight:600;"><?= number_format($item['total'], $dp) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Payments -->
    <?php if (!empty($purchase['payments'])): ?>
    <div class="pv-card">
        <div class="pv-card-head"><i class="bi bi-cash-stack"></i> Payments (<?= count($purchase['payments']) ?>)</div>
        <?php foreach ($purchase['payments'] as $pay): ?>
        <div class="pv-pay-item">
            <div>
                <span style="font-weight:600;"><?= $pay['payment_no'] ?></span>
                <br><small style="color:var(--text-muted);"><?= date('d M Y', strtotime($pay['date'])) ?></small>
            </div>
            <span style="font-weight:700;color:#22c55e;"><?= $curr ?> <?= number_format($pay['amount'], $dp) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
