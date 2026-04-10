<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount — <?= $discount['discount_no'] ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#fff; color:#1e293b; padding:20px; }
        .receipt { max-width:400px; margin:0 auto; }
        .header { text-align:center; padding-bottom:16px; border-bottom:2px dashed #cbd5e1; margin-bottom:16px; }
        .header h2 { font-size:1.1rem; font-weight:800; color:#1e3a5f; }
        .header .company { font-size:0.85rem; color:#64748b; margin-bottom:4px; }
        .header .doc-no { font-size:0.9rem; font-weight:700; color:#6366f1; margin-top:8px; }
        .row-item { display:flex; justify-content:space-between; padding:6px 0; font-size:0.85rem; }
        .row-item .label { color:#64748b; }
        .row-item .value { font-weight:600; }
        .divider { border-top:1px dashed #cbd5e1; margin:12px 0; }
        .total-row { display:flex; justify-content:space-between; padding:10px 0; font-size:1.1rem; font-weight:800; }
        .total-row .value { color:#10b981; }
        .footer { text-align:center; margin-top:20px; font-size:0.75rem; color:#94a3b8; border-top:2px dashed #cbd5e1; padding-top:12px; }
        .print-btn { display:block; margin:20px auto 0; padding:8px 24px; background:#6366f1; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:600; }
        @media print { .print-btn { display:none; } body { padding:0; } }
    </style>
</head>
<body>

<div class="receipt">
    <div class="header">
        <div class="company"><?= htmlspecialchars($companyName) ?></div>
        <h2>Discount Note</h2>
        <div class="doc-no"><?= $discount['discount_no'] ?></div>
    </div>

    <div class="row-item">
        <span class="label">Date</span>
        <span class="value"><?= date('d M Y', strtotime($discount['date'])) ?></span>
    </div>

    <div class="row-item">
        <span class="label">Customer</span>
        <span class="value"><?= htmlspecialchars($discount['party_name']) ?></span>
    </div>

    <?php if ($discount['party_phone']): ?>
    <div class="row-item">
        <span class="label">Phone</span>
        <span class="value"><?= htmlspecialchars($discount['party_phone']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($discount['item_name']): ?>
    <div class="row-item">
        <span class="label">Item</span>
        <span class="value"><?= htmlspecialchars($discount['item_name']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($discount['reason']): ?>
    <div class="row-item">
        <span class="label">Reason</span>
        <span class="value"><?= htmlspecialchars($discount['reason']) ?></span>
    </div>
    <?php endif; ?>

    <div class="divider"></div>

    <div class="total-row">
        <span>Discount Amount</span>
        <span class="value"><?= APP_CURRENCY ?> <?= number_format($discount['amount'], DECIMAL_PLACES) ?></span>
    </div>

    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.95rem;font-weight:700;">
        <span style="color:#64748b;">Remaining Balance</span>
        <span style="color:<?= $remainingBalance > 0.001 ? '#ef4444' : '#10b981' ?>;">
            <?= $remainingBalance > 0.001 ? APP_CURRENCY . ' ' . number_format($remainingBalance, DECIMAL_PLACES) : '✓ Clear' ?>
        </span>
    </div>

    <div class="divider"></div>

    <div class="row-item">
        <span class="label">Issued By</span>
        <span class="value"><?= htmlspecialchars($discount['created_by_name'] ?? '—') ?></span>
    </div>
    <div class="row-item">
        <span class="label">Created</span>
        <span class="value"><?= date('d M Y, h:i A', strtotime($discount['created_at'])) ?></span>
    </div>

    <div class="footer">
        <p>This discount has been applied to your account.</p>
        <p><?= htmlspecialchars($companyName) ?></p>
    </div>

    <button class="print-btn" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
