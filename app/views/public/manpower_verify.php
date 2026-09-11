<?php
/** @var string|null $error */
/** @var string $status */
/** @var array<string,mixed>|null $claims */
/** @var array<string,mixed> $live */
/** @var array<string,mixed>|null $warehouse */
/** @var string $companyName */
/** @var string $companyNameAr */

$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$periodLabel = '';
if (is_array($claims)) {
    $periodLabel = date('d F Y', strtotime((string) $claims['from_date']))
        . ' — ' . date('d F Y', strtotime((string) $claims['to_date']));
}
$ok = empty($error) && is_array($claims);
$isMatch = $ok && $status === 'match';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Invoice pack verification — <?= htmlspecialchars($companyName) ?></title>
    <style>
        :root { --ink:#0b1220; --muted:#64748b; --line:#d0d7e2; --band:#0b1f36; --ok:#047857; --warn:#b45309; --bad:#b91c1c; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
            background: #e8eef6; color: var(--ink); line-height: 1.45;
        }
        .wrap { max-width: 720px; margin: 0 auto; padding: 20px 14px 40px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(11,31,54,.12); overflow: hidden; }
        .head { background: var(--band); color: #fff; padding: 18px 20px; }
        .head h1 { font-size: 1.15rem; font-weight: 800; }
        .head .ar { font-size: 1rem; font-weight: 700; margin-top: 4px; opacity: .95; }
        .head p { font-size: .8rem; opacity: .8; margin-top: 6px; }
        .body { padding: 18px 20px 22px; }
        .banner {
            border-radius: 8px; padding: 12px 14px; font-weight: 800; margin-bottom: 16px;
            text-align: center; letter-spacing: .2px;
        }
        .banner.match { background: #ecfdf5; color: var(--ok); border: 1px solid #a7f3d0; }
        .banner.changed { background: #fffbeb; color: var(--warn); border: 1px solid #fde68a; }
        .banner.bad { background: #fef2f2; color: var(--bad); border: 1px solid #fecaca; }
        .banner .sub { display: block; font-weight: 600; font-size: .8rem; margin-top: 4px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .chip { background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; }
        .chip .k { display: block; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
        .chip .v { font-weight: 700; font-size: .92rem; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th { text-align: left; background: var(--band); color: #fff; padding: 7px 8px; font-size: .72rem; }
        td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; }
        tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; }
        .foot { margin-top: 16px; font-size: .75rem; color: var(--muted); text-align: center; }
        @media (max-width: 560px) { .meta { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <h1><?= htmlspecialchars($companyName) ?></h1>
            <div class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
            <p>Official sales invoice pack verification · التحقق من فواتير المبيعات الرسمية</p>
        </div>
        <div class="body">
            <?php if (!$ok): ?>
            <div class="banner bad">
                Not verified
                <span class="sub"><?= htmlspecialchars((string) $error) ?></span>
            </div>
            <?php elseif ($isMatch): ?>
            <div class="banner match">
                Authentic — records match
                <span class="sub">This pack matches the company’s live sales records for the period.</span>
            </div>
            <?php else: ?>
            <div class="banner changed">
                Signed pack — figures have changed
                <span class="sub">The QR is genuine, but live invoices now differ from what was printed (for example a later cancellation).</span>
            </div>
            <?php endif; ?>

            <?php if ($ok): ?>
            <div class="meta">
                <div class="chip"><span class="k">Period</span><span class="v"><?= htmlspecialchars($periodLabel) ?></span></div>
                <div class="chip"><span class="k">Branch</span><span class="v"><?= htmlspecialchars((string) ($warehouse['name'] ?? '—')) ?></span></div>
                <div class="chip">
                    <span class="k">Invoices on printed pack</span>
                    <span class="v"><?= number_format((int) $claims['invoice_count']) ?> · <?= $money((float) $claims['grand_total']) ?></span>
                </div>
                <div class="chip">
                    <span class="k">Live records now</span>
                    <span class="v"><?= number_format((int) $live['invoice_count']) ?> · <?= $money((float) $live['grand_total']) ?></span>
                </div>
                <div class="chip"><span class="k">Pack issued</span><span class="v"><?= date('d M Y', strtotime((string) $claims['issued'])) ?></span></div>
                <div class="chip"><span class="k">Currency</span><span class="v"><?= htmlspecialchars(APP_CURRENCY) ?></span></div>
            </div>

            <?php if (!empty($live['invoices'])): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($live['invoices'] as $i => $inv): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars((string) $inv['invoice_no']) ?></td>
                        <td><?= date('d M Y', strtotime((string) $inv['date'])) ?></td>
                        <td class="num"><?= $money((float) $inv['grand_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php endif; ?>

            <p class="foot">
                Public Authority of Manpower · الهيئة العامة للقوى العاملة<br>
                Customer names are not shown on this public page.
            </p>
        </div>
    </div>
</div>
</body>
</html>
