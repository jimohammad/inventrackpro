<?php
/**
 * Daily Sales & Payments Summary — Cron Job
 *
 * Setup in Hostinger hPanel > Cron Jobs:
 *   Command: /usr/bin/php /home/u793102776/public_html/cron/daily_summary.php
 *   Schedule: Once a day (e.g. 11:00 PM)
 */

// Bootstrap
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$db   = Database::getInstance();
$date = date('Y-m-d');
$to   = 'javid@iqbalelectronics.com';

// --- Fetch today's data ---

// Sales
$sales = $db->fetchOne(
    "SELECT COUNT(*) as count,
            COALESCE(SUM(grand_total), 0) as total,
            COALESCE(SUM(paid_amount), 0) as paid,
            COALESCE(SUM(balance), 0) as balance
     FROM sales WHERE date = ? AND status != 'cancelled'",
    [$date]
);

// Payments received today
$paymentsIn = $db->fetchOne(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
     FROM payments WHERE date = ? AND payment_type = 'in'",
    [$date]
);

// Payments sent today
$paymentsOut = $db->fetchOne(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
     FROM payments WHERE date = ? AND payment_type = 'out'",
    [$date]
);

// Total outstanding across all sales
$outstanding = $db->fetchOne(
    "SELECT COALESCE(SUM(balance), 0) as total
     FROM sales WHERE status IN ('confirmed', 'partial')"
);

// Per-warehouse breakdown
$warehouseSales = $db->fetchAll(
    "SELECT w.name, COUNT(s.id) as count, COALESCE(SUM(s.grand_total), 0) as total
     FROM sales s
     JOIN warehouses w ON w.id = s.warehouse_id
     WHERE s.date = ? AND s.status != 'cancelled'
     GROUP BY w.id, w.name
     ORDER BY total DESC",
    [$date]
);

// --- Format numbers ---
$cur = APP_CURRENCY;
$dp  = DECIMAL_PLACES;

$salesCount    = $sales['count'];
$salesTotal    = number_format($sales['total'], $dp);
$salesPaid     = number_format($sales['paid'], $dp);
$salesBalance  = number_format($sales['balance'], $dp);
$pInCount      = $paymentsIn['count'];
$pInTotal      = number_format($paymentsIn['total'], $dp);
$pOutCount     = $paymentsOut['count'];
$pOutTotal     = number_format($paymentsOut['total'], $dp);
$totalOutstand = number_format($outstanding['total'], $dp);

// --- Build email ---
$dateFormatted = date('l, d M Y', strtotime($date));
$subject = "Daily Summary - {$dateFormatted} | " . APP_NAME;

$warehouseRows = '';
foreach ($warehouseSales as $ws) {
    $warehouseRows .= "<tr>
        <td style='padding:8px 12px;border-bottom:1px solid #eee;'>{$ws['name']}</td>
        <td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:center;'>{$ws['count']}</td>
        <td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:right;font-weight:600;'>{$cur} " . number_format($ws['total'], $dp) . "</td>
    </tr>";
}
if (empty($warehouseRows)) {
    $warehouseRows = "<tr><td colspan='3' style='padding:12px;text-align:center;color:#999;'>No sales today</td></tr>";
}

$html = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'></head>
<body style='font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;'>
<div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);'>

    <!-- Header -->
    <div style='background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:24px 30px;'>
        <h1 style='margin:0;font-size:20px;'>Daily Summary</h1>
        <p style='margin:6px 0 0;opacity:0.9;font-size:14px;'>{$dateFormatted}</p>
    </div>

    <div style='padding:24px 30px;'>

        <!-- Sales Summary -->
        <h2 style='font-size:16px;color:#374151;margin:0 0 16px;border-bottom:2px solid #6366f1;padding-bottom:8px;'>
            Sales
        </h2>
        <table style='width:100%;border-collapse:collapse;margin-bottom:24px;'>
            <tr>
                <td style='padding:10px;background:#f0f9ff;border-radius:8px;text-align:center;width:25%;'>
                    <div style='font-size:24px;font-weight:700;color:#0369a1;'>{$salesCount}</div>
                    <div style='font-size:11px;color:#666;margin-top:4px;'>Invoices</div>
                </td>
                <td style='width:4%;'></td>
                <td style='padding:10px;background:#f0fdf4;border-radius:8px;text-align:center;width:25%;'>
                    <div style='font-size:16px;font-weight:700;color:#15803d;'>{$cur} {$salesTotal}</div>
                    <div style='font-size:11px;color:#666;margin-top:4px;'>Total Sales</div>
                </td>
                <td style='width:4%;'></td>
                <td style='padding:10px;background:#fefce8;border-radius:8px;text-align:center;width:25%;'>
                    <div style='font-size:16px;font-weight:700;color:#a16207;'>{$cur} {$salesBalance}</div>
                    <div style='font-size:11px;color:#666;margin-top:4px;'>Unpaid Today</div>
                </td>
            </tr>
        </table>

        <!-- Per Warehouse -->
        <h2 style='font-size:16px;color:#374151;margin:0 0 12px;border-bottom:2px solid #6366f1;padding-bottom:8px;'>
            By Branch
        </h2>
        <table style='width:100%;border-collapse:collapse;margin-bottom:24px;font-size:14px;'>
            <tr style='background:#f9fafb;'>
                <th style='padding:8px 12px;text-align:left;font-weight:600;'>Branch</th>
                <th style='padding:8px 12px;text-align:center;font-weight:600;'>Invoices</th>
                <th style='padding:8px 12px;text-align:right;font-weight:600;'>Total</th>
            </tr>
            {$warehouseRows}
        </table>

        <!-- Payments -->
        <h2 style='font-size:16px;color:#374151;margin:0 0 12px;border-bottom:2px solid #6366f1;padding-bottom:8px;'>
            Payments
        </h2>
        <table style='width:100%;border-collapse:collapse;margin-bottom:24px;font-size:14px;'>
            <tr>
                <td style='padding:10px;background:#f0fdf4;border-radius:8px;text-align:center;width:48%;'>
                    <div style='font-size:18px;font-weight:700;color:#15803d;'>{$cur} {$pInTotal}</div>
                    <div style='font-size:11px;color:#666;margin-top:4px;'>Received ({$pInCount})</div>
                </td>
                <td style='width:4%;'></td>
                <td style='padding:10px;background:#fef2f2;border-radius:8px;text-align:center;width:48%;'>
                    <div style='font-size:18px;font-weight:700;color:#dc2626;'>{$cur} {$pOutTotal}</div>
                    <div style='font-size:11px;color:#666;margin-top:4px;'>Sent ({$pOutCount})</div>
                </td>
            </tr>
        </table>

        <!-- Outstanding -->
        <div style='background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:16px;text-align:center;margin-bottom:16px;'>
            <div style='font-size:12px;color:#9a3412;text-transform:uppercase;letter-spacing:1px;'>Total Outstanding Balance</div>
            <div style='font-size:28px;font-weight:700;color:#c2410c;margin-top:6px;'>{$cur} {$totalOutstand}</div>
        </div>

    </div>

    <!-- Footer -->
    <div style='background:#f9fafb;padding:16px 30px;text-align:center;font-size:12px;color:#999;border-top:1px solid #eee;'>
        " . APP_NAME . " &mdash; Auto-generated daily summary
    </div>
</div>
</body>
</html>
";

// --- Send email ---
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . APP_NAME . " <noreply@iqbal.app>\r\n";

$sent = mail($to, $subject, $html, $headers);

if ($sent) {
    echo date('Y-m-d H:i:s') . " - Summary sent to {$to}\n";
} else {
    echo date('Y-m-d H:i:s') . " - FAILED to send email\n";
}
