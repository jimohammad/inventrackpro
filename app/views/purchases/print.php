<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase <?= $purchase['invoice_no'] ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 12px; color: #1a1a1a;
        background: #fff; padding: 20px;
    }
    .invoice-wrap { max-width: 800px; margin: 0 auto; padding: 24px; }

    .inv-header { display: flex; justify-content: space-between; margin-bottom: 24px; }
    .company-name { font-size: 20px; font-weight: 800; color: #1e3a5f; }
    .company-info { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }
    .inv-title { text-align: right; }
    .inv-title h1 { font-size: 24px; font-weight: 800; color: #8b5cf6; letter-spacing: 1px; }
    .inv-title p { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }

    .party-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
    .party-box { background: #f8f9ff; border: 1px solid #e0e7ff; border-radius: 8px; padding: 12px 16px; min-width: 200px; }
    .party-box label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #6366f1; letter-spacing: 0.5px; }
    .party-box p { font-size: 12px; font-weight: 600; color: #1a1a1a; margin-top: 2px; }
    .party-box small { color: #666; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th {
        background: #1e3a5f; color: #fff;
        padding: 8px 10px; font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.4px;
    }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:nth-child(even) { background: #f8f9ff; }

    .totals-section { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .totals-box { min-width: 240px; }
    .total-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
    .total-row:last-child { border-bottom: none; }
    .total-row.grand { font-size: 14px; font-weight: 800; color: #1e3a5f; border-top: 2px solid #1e3a5f; padding-top: 8px; margin-top: 4px; }
    .total-row .label { color: #666; }

    .status-badge {
        display: inline-block; padding: 4px 12px; border-radius: 20px;
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-paid     { background: #d1fae5; color: #065f46; }
    .status-partial  { background: #fef3c7; color: #92400e; }
    .status-confirmed{ background: #dbeafe; color: #1e40af; }

    .inv-footer { border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center; color: #888; font-size: 11px; }

    @media print {
        body { padding: 0; }
        .no-print { display: none !important; }
        .invoice-wrap { padding: 12px; }
        @page { margin: 10mm; }
    }
</style>
</head>
<body>

<div class="no-print" style="text-align:right;margin-bottom:12px;">
    <button onclick="window.print()"
        style="background:#8b5cf6;color:#fff;border:none;padding:7px 20px;border-radius:5px;font-size:13px;cursor:pointer;">
        <b>⎙ Print</b>
    </button>
    <button onclick="window.location='?page=purchases&action=detail&id=<?= $purchase['id'] ?>'"
        style="background:#f3f4f6;color:#444;border:1px solid #e5e7eb;padding:7px 20px;border-radius:5px;font-size:13px;cursor:pointer;margin-left:6px;">
        Close
    </button>
</div>

<div class="invoice-wrap">
    <!-- Header -->
    <div class="inv-header">
        <div>
            <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?? APP_NAME) ?></div>
            <div class="company-info">
                <?= nl2br(htmlspecialchars($settings['company_address'] ?? '')) ?><br>
                <?= htmlspecialchars($settings['company_phone'] ?? '') ?><br>
                <?= htmlspecialchars($settings['company_email'] ?? '') ?>
            </div>
        </div>
        <div class="inv-title">
            <h1>PURCHASE</h1>
            <p>
                <strong># <?= $purchase['invoice_no'] ?></strong><br>
                <?php if (!empty($purchase['supplier_invoice_no'])): ?>
                Supplier Ref: <strong><?= htmlspecialchars($purchase['supplier_invoice_no']) ?></strong><br>
                <?php endif; ?>
                Date: <?= date('d M Y', strtotime($purchase['date'])) ?><br>
                Warehouse: <?= htmlspecialchars($purchase['warehouse_name'] ?? '—') ?>
            </p>
        </div>
    </div>

    <!-- Party Info -->
    <div class="party-section">
        <div class="party-box">
            <label>Supplier</label>
            <p><?= htmlspecialchars($purchase['party_name']) ?></p>
            <?php if ($purchase['party_phone']): ?>
            <small><?= htmlspecialchars($purchase['party_phone']) ?></small>
            <?php endif; ?>
        </div>
        <div class="party-box">
            <label>Payment Status</label>
            <p style="margin-top:6px;">
                <span class="status-badge status-<?= $purchase['status'] ?>">
                    <?= ucfirst($purchase['status']) ?>
                </span>
            </p>
            <?php if ($purchase['balance'] > 0): ?>
            <small style="color:#dc2626;">Balance: <?= APP_CURRENCY ?> <?= number_format($purchase['balance'], DECIMAL_PLACES) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Item</th>
                <th style="width:50px;text-align:center;">Qty</th>
                <th style="width:100px;text-align:right;">Price</th>
                <th style="width:110px;text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchase['items'] as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                    <?php if ($item['sku']): ?>
                    <br><small style="color:#888;"><?= htmlspecialchars((string) $item['sku']) ?></small>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;"><?= number_format($item['unit_price'], DECIMAL_PLACES) ?></td>
                <td style="text-align:right;font-weight:600;"><?= number_format($item['total'], DECIMAL_PLACES) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span class="label">Subtotal</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['subtotal'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if ($purchase['discount'] > 0): ?>
            <div class="total-row">
                <span class="label">Discount</span>
                <span style="color:#dc2626;">- <?= APP_CURRENCY ?> <?= number_format($purchase['discount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>Total</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['grand_total'], DECIMAL_PLACES) ?></span>
            </div>
            <?php if ($purchase['paid_amount'] > 0): ?>
            <div class="total-row" style="color:#059669;">
                <span class="label">Paid</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['paid_amount'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($purchase['balance'] > 0): ?>
            <div class="total-row" style="color:#dc2626;font-weight:700;">
                <span>Balance Due</span>
                <span><?= APP_CURRENCY ?> <?= number_format($purchase['balance'], DECIMAL_PLACES) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($purchase['notes']): ?>
    <div style="margin-bottom:16px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;">
        <p style="font-size:11px;color:#888;font-weight:700;margin-bottom:3px;">NOTES</p>
        <p style="font-size:12px;color:#555;"><?= nl2br(htmlspecialchars($purchase['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <div class="inv-footer">
        <p><?= htmlspecialchars($settings['invoice_footer'] ?? 'Thank you for your business!') ?></p>
        <p style="margin-top:4px;">Printed on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars(Auth::name()) ?></p>
    </div>
</div>

<script>
    <?php if (isset($_GET['autoprint'])): ?>
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    <?php endif; ?>
</script>
</body>
</html>
