<?php
/** @var string|null $error */
/** @var string $status */
/** @var array<string,mixed>|null $claims */
/** @var array<string,mixed>|null $live */
/** @var string $companyName */
/** @var string $companyNameAr */

$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$ok = empty($error) && is_array($claims) && is_array($live);
$isIn = is_array($live) && (($live['payment_type'] ?? 'in') === 'in');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Receipt verification — <?= htmlspecialchars($companyName) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
            background: #e8eef6; color: #0b1220; line-height: 1.45;
        }
        .wrap { max-width: 520px; margin: 0 auto; padding: 20px 14px 40px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(11,31,54,.12); overflow: hidden; }
        .head { background: #0b1f36; color: #fff; padding: 18px 20px; text-align: center; }
        .head h1 { font-size: 1.05rem; font-weight: 800; }
        .head .ar { font-size: .95rem; font-weight: 700; margin-top: 4px; }
        .head p { font-size: .78rem; opacity: .85; margin-top: 6px; }
        .body { padding: 18px 20px 22px; }
        .banner {
            border-radius: 8px; padding: 12px 14px; font-weight: 800; margin-bottom: 16px;
            text-align: center;
        }
        .banner.match { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .banner.changed { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .banner.voided { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .banner.bad { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .banner .sub { display: block; font-weight: 600; font-size: .8rem; margin-top: 4px; }
        .amt { text-align: center; margin: 8px 0 16px; }
        .amt .k { font-size: .75rem; font-weight: 700; color: #5b6b82; }
        .amt .v { font-size: 1.55rem; font-weight: 800; color: #0b1f36; margin-top: 2px; }
        .chip { background: #f8fafc; border: 1px solid #d0d7e2; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
        .chip .k { display: block; font-size: .65rem; font-weight: 800; color: #64748b; }
        .chip .v { font-weight: 700; font-size: .92rem; }
        .foot { margin-top: 16px; font-size: .75rem; color: #64748b; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <h1><?= htmlspecialchars($companyName) ?></h1>
            <div class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
            <p>Payment Receipt Voucher verification · التحقق من سند القبض</p>
        </div>
        <div class="body">
            <?php if (!$ok): ?>
            <div class="banner bad">
                Not verified
                <span class="sub"><?= htmlspecialchars((string) $error) ?></span>
            </div>
            <?php elseif ($status === 'match'): ?>
            <div class="banner match">
                Authentic — receipt matches
                <span class="sub">This QR matches the company’s live payment record.</span>
            </div>
            <?php elseif ($status === 'changed'): ?>
            <div class="banner changed">
                Record changed after print
                <span class="sub">The receipt number is valid, but the amount no longer matches.</span>
            </div>
            <?php else: ?>
            <div class="banner voided">
                Voided
                <span class="sub">This payment has been cancelled.</span>
            </div>
            <?php endif; ?>

            <?php if ($ok): ?>
            <div class="amt">
                <div class="k"><?= $isIn ? 'Amount received / المبلغ المستلم' : 'Amount paid / المبلغ المدفوع' ?></div>
                <div class="v" dir="ltr"><?= $money((float) $live['amount']) ?></div>
            </div>
            <div class="chip">
                <span class="k">Receipt No / رقم الإيصال</span>
                <span class="v" dir="ltr"><?= htmlspecialchars((string) $live['payment_no']) ?></span>
            </div>
            <div class="chip">
                <span class="k">Date / التاريخ</span>
                <span class="v" dir="ltr"><?= htmlspecialchars(date('d M Y', strtotime((string) $live['date']))) ?></span>
            </div>
            <?php if (trim((string) $live['party_name']) !== ''): ?>
            <div class="chip">
                <span class="k"><?= $isIn ? 'Customer Name / اسم العميل' : 'Paid to / صرف إلى' ?></span>
                <span class="v"><?= htmlspecialchars((string) $live['party_name']) ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <p class="foot">Computer generated electronic receipt · إيصال صادر إلكترونياً</p>
        </div>
    </div>
</div>
</body>
</html>
