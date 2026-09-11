<?php
/**
 * Public Authority of Manpower — A4 certified sales invoice copies.
 * Cover + index, then one formal invoice per page. Browser Print → Save as PDF.
 */
$companyName    = $settings['company_name'] ?? PDF_COMPANY_NAME;
// Temporary for this PAM pack only — do not change Settings / other documents.
$companyName    = trim((string) preg_replace('/\bL\.?L\.?C\.?\b/i', 'WLL', (string) $companyName));
$companyNameAr  = trim((string) ($settings['company_name_ar'] ?? ''));
if ($companyNameAr === '') {
    $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
}
$companyAddress   = $settings['company_address'] ?? (defined('PDF_COMPANY_ADDRESS') ? PDF_COMPANY_ADDRESS : '');
$companyAddressAr = trim((string) ($settings['company_address_ar'] ?? ''));
$companyPhone     = $settings['company_phone'] ?? PDF_COMPANY_PHONE;
$companyEmail     = $settings['company_email'] ?? (defined('PDF_COMPANY_EMAIL') ? PDF_COMPANY_EMAIL : '');
$branchName       = $warehouse['name'] ?? 'Main Branch';
$periodLabel      = date('d F Y', strtotime($fromDate)) . ' — ' . date('d F Y', strtotime($toDate));
$backQs           = 'from_date=' . urlencode((string) $fromDate)
    . '&to_date=' . urlencode((string) $toDate)
    . '&include_copies=' . ($includeCopies ? '1' : '0');
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$fmtQty = static function ($q): string {
    return rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
};

$kycRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'kyc' . DIRECTORY_SEPARATOR;
$kycStampPath = $kycRoot . 'company_stamp.png';
$stampSrc = null;
if (is_file($kycStampPath) && is_readable($kycStampPath)) {
    $bin = @file_get_contents($kycStampPath);
    if ($bin !== false && $bin !== '') {
        $stampSrc = 'data:image/png;base64,' . base64_encode($bin);
    }
}
$verifyUrl = trim((string) ($verifyUrl ?? ''));
$verifyHost = (string) (parse_url(APP_URL, PHP_URL_HOST) ?: 'iqbal.app');
$coverHeading = is_array($coverHeading ?? null) ? $coverHeading : [
    'en'     => 'Sales Invoices',
    'ar'     => 'فواتير المبيعات',
    'sub_en' => 'Certified extracts from computerized sales records',
    'sub_ar' => 'صور فواتير المبيعات الرسمية',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) $coverHeading['en']) ?> — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        :root {
            --ink: #0b1220;
            --muted: #64748b;
            --line: #d0d7e2;
            --line-soft: #e8edf4;
            --band: #0b1f36;
            --paper: #ffffff;
            --teal: #0f766e;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", "Helvetica Neue", Arial, sans-serif;
            font-size: 9pt;
            color: var(--ink);
            background: #dbe4f0;
            line-height: 1.4;
        }
        .ar { unicode-bidi: embed; }

        .toolbar {
            position: sticky; top: 0; z-index: 50;
            display: flex; flex-wrap: wrap; gap: 8px;
            align-items: center; justify-content: center;
            padding: 10px 14px; background: var(--band); color: #fff; font-size: 12px;
        }
        .toolbar button, .toolbar a {
            border: none; border-radius: 5px; padding: 7px 14px;
            font-weight: 700; cursor: pointer; text-decoration: none; color: #fff; font-size: 12px;
        }
        .toolbar .btn-pdf { background: #dc2626; }
        .toolbar .btn-print { background: #2563eb; }
        .toolbar .btn-back { background: #475569; }
        .toolbar .hint { opacity: 0.85; font-size: 11px; }

        .page {
            width: 210mm; max-width: 100%;
            margin: 12px auto 24px; padding: 12mm 12mm 14mm;
            background: var(--paper);
            box-shadow: 0 10px 30px rgba(11, 31, 54, 0.14);
        }

        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 10px; }
        .brand { flex: 1 1 auto; min-width: 0; }
        .brand-names {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
        }
        .topbar .brand-names {
            padding-bottom: 6px;
            margin-bottom: 5px;
            border-bottom: 1.5px solid var(--band);
        }
        .company-name {
            font-size: 13.5pt; font-weight: 800; color: var(--band);
            letter-spacing: 0.06em; text-transform: uppercase; line-height: 1.15;
        }
        .company-name-ar {
            font-size: 11pt; font-weight: 700; color: var(--band);
            line-height: 1.45; unicode-bidi: isolate; text-align: left;
        }
        .company-sub { font-size: 8pt; color: var(--muted); margin-top: 2px; line-height: 1.35; }
        .company-sub.ar { text-align: left; unicode-bidi: isolate; }
        .badge-col { text-align: right; flex: 0 0 auto; }
        .verify-box {
            display: inline-block; text-align: center;
            padding: 6px 7px 6px; border-radius: 8px; background: #fff;
        }
        .verify-cap {
            font-size: 6.5pt; font-weight: 800; letter-spacing: 0.4px; text-transform: uppercase;
            color: var(--teal); margin-bottom: 4px;
        }
        .verify-cap .ar { display: block; text-transform: none; letter-spacing: 0; font-weight: 700; margin-top: 1px; }
        #verify-qr-main { width: 38mm; height: 38mm; margin: 0 auto; }
        #verify-qr-main svg, .verify-qr-slot svg {
            display: block; background: #fff;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        #verify-qr-main svg { width: 38mm; height: 38mm; }
        .verify-url { font-size: 6.5pt; color: var(--muted); margin-top: 4px; word-break: break-all; max-width: 38mm; }
        .verify-qr-slot { width: 24mm; height: 24mm; flex-shrink: 0; }
        .verify-qr-slot svg { width: 24mm; height: 24mm; }
        .confidential {
            display: inline-block; padding: 3px 8px; border: 1px solid #b45309;
            color: #b45309; font-size: 7pt; font-weight: 800; letter-spacing: 0.4px;
            text-transform: uppercase; border-radius: 3px;
        }
        .doc-ref { font-size: 7.5pt; color: var(--muted); margin-top: 6px; }

        .masthead {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: stretch;
            background: #fff;
            color: var(--ink);
            padding: 10px 12px 10px 14px;
            border: 1px solid var(--line);
            border-left: 4px solid var(--band);
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .masthead-kicker {
            font-size: 7pt; font-weight: 800; letter-spacing: 1.1px; text-transform: uppercase;
            color: var(--teal); margin-bottom: 5px;
        }
        .masthead-title { font-size: 16pt; font-weight: 800; line-height: 1.15; letter-spacing: -0.2px; color: var(--band); }
        .masthead-title-ar { font-size: 12pt; font-weight: 700; margin-top: 3px; color: var(--ink); }
        .masthead-sub { font-size: 8pt; color: var(--muted); margin-top: 6px; max-width: 130mm; line-height: 1.4; }
        .masthead-sub .ar { display: block; margin-top: 2px; }
        .masthead-qr {
            background: #fff; border-radius: 8px; padding: 6px 7px 5px;
            border: 1px solid var(--line);
            display: flex; align-items: center; justify-content: center;
        }
        .masthead-qr .verify-box { margin: 0; border: none; padding: 0; }

        .stat-row {
            display: grid;
            grid-template-columns: 1.3fr 0.8fr 1.3fr 0.9fr;
            gap: 6px;
            margin-bottom: 10px;
        }
        .stat-tile {
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #f8fafc;
            padding: 7px 9px;
        }
        .stat-tile .k {
            display: block; font-size: 6.5pt; font-weight: 800; letter-spacing: 0.5px;
            text-transform: uppercase; color: var(--muted);
        }
        .stat-tile .k .ar { display: block; text-transform: none; letter-spacing: 0; font-weight: 700; margin-top: 1px; }
        .stat-tile .v { display: block; font-size: 10pt; font-weight: 800; color: var(--band); margin-top: 3px; font-variant-numeric: tabular-nums; }
        .stat-tile .s { display: block; font-size: 7.5pt; color: #64748b; margin-top: 1px; }
        .stat-tile-accent { background: #ecfdf5; border-color: #99f6e4; }
        .stat-tile-accent .v { color: var(--teal); }

        .meta-strip { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
        .meta-chip {
            border: 1px solid var(--line); border-radius: 5px; padding: 4px 8px; background: #fff;
        }
        .meta-chip .k { display: block; font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); font-weight: 700; }
        .meta-chip .v { font-size: 8pt; font-weight: 700; }

        .idx-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        .idx-table th {
            background: #fff; color: var(--band); text-align: left; padding: 5px 6px;
            font-size: 7pt; font-weight: 700; letter-spacing: 0.3px;
            border-bottom: 1.5px solid var(--band);
        }
        .idx-table td { padding: 4px 6px; border-bottom: 1px solid var(--line-soft); }
        .idx-table tr:nth-child(even) td { background: #f8fafc; }
        .idx-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; }
        .idx-table .ctr { text-align: center; }

        .declaration {
            margin-top: 16px; padding: 12px 14px; border: 1px solid #99f6e4;
            border-radius: 7px; background: linear-gradient(180deg, #f0fdfa, #fafbfc);
            break-inside: avoid; font-size: 8pt; color: #334155;
        }
        .declaration h3 {
            font-size: 8.5pt; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.6px; color: var(--teal); margin-bottom: 6px;
        }
        .declaration p { margin-bottom: 5px; text-align: justify; line-height: 1.45; }
        .sign-row { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-top: 14px; }
        .sign-box { width: 46%; }
        .sign-box-stamp { text-align: center; }
        .sign-media { min-height: 72px; display: flex; align-items: flex-end; justify-content: flex-start; margin-bottom: 2px; }
        .sign-media-empty { min-height: 56px; }
        .stamp-media { justify-content: center; align-items: center; min-height: 96px; }
        .sig-img { max-height: 90px; max-width: 230px; width: auto; height: auto; object-fit: contain; display: block; }
        .stamp-img { max-height: 118px; max-width: 118px; width: auto; height: auto; object-fit: contain; display: block; }
        .sign-line { border-top: 1px solid var(--ink); padding-top: 4px; color: var(--ink); font-weight: 650; font-size: 7.5pt; }
        .sign-name { display: block; font-weight: 700; font-size: 8pt; margin-top: 2px; }

        .cover-foot {
            margin-top: 14px; padding-top: 8px; border-top: 1px solid var(--line-soft);
            display: flex; justify-content: space-between; font-size: 6.5pt; color: var(--muted);
        }

        .invoice-copy {
            width: 210mm; max-width: 100%;
            margin: 12px auto 24px; padding: 12mm 12mm 14mm;
            background: var(--paper);
            box-shadow: 0 10px 30px rgba(11, 31, 54, 0.14);
            page-break-before: always; break-before: page;
            position: relative;
        }
        .copy-banner {
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
            border-bottom: 2px solid var(--band); padding-bottom: 8px; margin-bottom: 10px;
        }
        .copy-mark {
            font-size: 7pt; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;
            color: #b45309; border: 1px solid #fbbf24; background: #fffbeb;
            padding: 3px 7px; border-radius: 3px;
        }
        .inv-head {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 10px;
            margin-bottom: 12px;
            align-items: stretch;
        }
        .inv-card {
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #f8fafc;
            padding: 8px 10px 9px;
            min-height: 28mm;
        }
        .inv-card-doc {
            background: #fff;
            border-color: #c5d0dc;
            border-left: 4px solid var(--band);
        }
        .inv-card-kicker {
            font-size: 7pt; font-weight: 800; letter-spacing: 0.4px; text-transform: uppercase;
            color: var(--teal); margin-bottom: 4px;
        }
        .inv-card .name { font-weight: 800; font-size: 11pt; color: var(--band); line-height: 1.25; }
        .inv-card .phone { font-size: 8.5pt; color: #475569; margin-top: 2px; }
        .inv-doc-title {
            font-size: 12pt; font-weight: 800; color: var(--band); line-height: 1.2; margin-bottom: 8px;
        }
        .inv-doc-title .ar { display: block; font-size: 10.5pt; font-weight: 700; margin-top: 1px; }
        .inv-meta-list { width: 100%; border-collapse: collapse; }
        .inv-meta-list th, .inv-meta-list td {
            padding: 3px 0; font-size: 8pt; vertical-align: top; border-bottom: 1px solid var(--line-soft);
        }
        .inv-meta-list tr:last-child th, .inv-meta-list tr:last-child td { border-bottom: none; }
        .inv-meta-list th {
            text-align: left; font-weight: 700; color: var(--muted); width: 42%;
            padding-right: 8px; letter-spacing: 0.2px;
        }
        .inv-meta-list th .ar { display: block; font-weight: 650; text-transform: none; letter-spacing: 0; margin-top: 1px; }
        .inv-meta-list td { font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }

        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .inv-table th {
            background: #fff; color: var(--band); padding: 6px 7px; font-size: 7.5pt;
            font-weight: 700; text-align: left; vertical-align: bottom;
            border-bottom: 1.5px solid var(--band);
        }
        .inv-table td { padding: 5px 7px; border-bottom: 1px solid var(--line-soft); font-size: 8.5pt; vertical-align: top; }
        .inv-table tr:nth-child(even) td { background: #f8fafc; }
        .item-ar { font-size: 8pt; color: #1a1a1a; margin-top: 1px; }
        .item-sku { font-size: 7.5pt; color: #888; }
        .inv-table .r { text-align: right; font-variant-numeric: tabular-nums; }
        .inv-table .c { text-align: center; }

        .totals { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .totals-box { width: 70mm; }
        .t-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; font-size: 8.5pt; border-bottom: 1px solid var(--line-soft); }
        .t-grand { font-size: 11pt; font-weight: 800; color: var(--band); border-top: 1.5px solid var(--band); padding-top: 6px; margin-top: 3px; border-bottom: none; }

        .inv-copy-foot {
            display: flex; justify-content: space-between; align-items: flex-end; gap: 12px;
            border-top: 1px solid var(--line); padding-top: 8px; margin-top: 8px;
            font-size: 7.5pt; color: var(--muted);
        }
        .inv-copy-foot .stamp-mini { max-height: 72px; max-width: 72px; }

        .index-head {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; margin-bottom: 10px; border-bottom: 2px solid var(--band); padding-bottom: 6px;
        }
        .index-head h2 { font-size: 12pt; font-weight: 800; color: var(--band); }
        .index-head .ar { font-size: 10pt; font-weight: 700; }

        @page { size: A4 portrait; margin: 10mm 10mm 12mm; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none !important; }
            .page, .invoice-copy {
                width: auto; margin: 0; padding: 0; box-shadow: none;
            }
            .cover-page { page-break-after: always; break-after: page; }
            .index-page { page-break-before: always; break-before: page; }
            .invoice-copy { page-break-before: always; break-before: page; }
            #verify-qr-main, #verify-qr-main svg { width: 38mm !important; height: 38mm !important; }
            .verify-qr-slot, .verify-qr-slot svg { width: 24mm !important; height: 24mm !important; }
            .copy-mark, .inv-card-doc {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
<?php
$verifyQr = (isset($verifyQr) && is_array($verifyQr)) ? $verifyQr : null;
$verifyQrId  = (string) ($verifyQr['id'] ?? '');
$verifyQrDim = (int) ($verifyQr['dim'] ?? 0);
$verifyQrOk  = $verifyQrId !== '' && $verifyQrDim > 0 && !empty($verifyQr['group']);
$verifyQrPlaced = false;
$renderVerifyQr = static function (string $size) use ($verifyQr, $verifyQrOk, $verifyQrId, $verifyQrDim, &$verifyQrPlaced): void {
    if (!$verifyQrOk) {
        return;
    }
    $id = htmlspecialchars($verifyQrId, ENT_QUOTES, 'UTF-8');
    $size = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $verifyQrDim . ' ' . $verifyQrDim
        . '" width="' . $size . '" height="' . $size . '">'
        . '<rect width="100%" height="100%" fill="#ffffff"/>';
    if (!$verifyQrPlaced) {
        echo $verifyQr['group'];
        $verifyQrPlaced = true;
    } else {
        echo '<use href="#' . $id . '"/>';
    }
    echo '</svg>';
};
?>

<div class="toolbar no-print">
    <button type="button" class="btn-pdf" id="btnSavePdf">Save as PDF (A4)</button>
    <button type="button" class="btn-print" id="btnPrint">Print</button>
    <a class="btn-back" href="?page=reports&action=manpowerInvoices&<?= htmlspecialchars($backQs) ?>">Back</a>
    <span class="hint">Print → Save as PDF · Paper: A4 · Margins: Default · Background graphics: On</span>
</div>

<div class="page cover-page" id="pam-cover">
    <div class="topbar">
        <div class="brand">
            <div class="brand-names">
                <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
                <div class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
            </div>
            <?php if ($companyAddress !== ''): ?>
            <div class="company-sub"><?= htmlspecialchars((string) $companyAddress) ?></div>
            <?php endif; ?>
            <?php if ($companyAddressAr !== ''): ?>
            <div class="company-sub ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyAddressAr) ?></div>
            <?php endif; ?>
            <div class="company-sub">
                <?php if ($companyPhone !== ''): ?>Tel <?= htmlspecialchars((string) $companyPhone) ?><?php endif; ?>
                <?php if ($companyEmail !== ''): ?><?= $companyPhone !== '' ? '  ·  ' : '' ?><?= htmlspecialchars((string) $companyEmail) ?><?php endif; ?>
            </div>
        </div>
        <div class="badge-col">
            <div class="confidential">For Public Authority of Manpower</div>
            <div class="doc-ref ar" dir="rtl" lang="ar">للهيئة العامة للقوى العاملة</div>
            <div class="doc-ref">Ref <strong><?= htmlspecialchars((string) $docRef) ?></strong></div>
        </div>
    </div>

    <div class="masthead">
        <div>
            <div class="masthead-kicker">Public Authority of Manpower</div>
            <div class="masthead-title"><?= htmlspecialchars((string) ($coverHeading['en'] ?? 'Sales Invoices')) ?></div>
            <div class="masthead-title-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars((string) ($coverHeading['ar'] ?? 'فواتير المبيعات')) ?></div>
            <div class="masthead-sub">
                <?= htmlspecialchars((string) ($coverHeading['sub_en'] ?? 'Certified extracts from computerized sales records')) ?>
                <span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars((string) ($coverHeading['sub_ar'] ?? 'صور فواتير المبيعات الرسمية')) ?></span>
            </div>
        </div>
        <?php if ($verifyUrl !== ''): ?>
        <div class="masthead-qr">
            <div class="verify-box">
                <div class="verify-cap">Scan to verify<span class="ar" dir="rtl" lang="ar">امسح للتحقق</span></div>
                <div id="verify-qr-main"><?php $renderVerifyQr('38mm'); ?></div>
                <div class="verify-url"><?= htmlspecialchars($verifyHost) ?>/v/…</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="stat-row">
        <div class="stat-tile stat-tile-accent">
            <span class="k">Sales total<span class="ar" dir="rtl" lang="ar">إجمالي المبيعات</span></span>
            <span class="v"><?= $money((float) $summary['grand_total']) ?></span>
        </div>
        <div class="stat-tile">
            <span class="k">Invoices<span class="ar" dir="rtl" lang="ar">الفواتير</span></span>
            <span class="v"><?= number_format((int) $summary['invoice_count']) ?></span>
        </div>
        <div class="stat-tile">
            <span class="k">Period<span class="ar" dir="rtl" lang="ar">الفترة</span></span>
            <span class="v" style="font-size:8.5pt;"><?= htmlspecialchars($periodLabel) ?></span>
        </div>
        <div class="stat-tile">
            <span class="k">Branch<span class="ar" dir="rtl" lang="ar">الفرع</span></span>
            <span class="v" style="font-size:9pt;"><?= htmlspecialchars((string) $branchName) ?></span>
            <span class="s"><?= htmlspecialchars(APP_CURRENCY) ?> · <?= htmlspecialchars((string) $preparedBy) ?></span>
        </div>
    </div>

    <?php if (!empty($summary['by_month'])): ?>
    <div class="meta-strip">
        <?php foreach ($summary['by_month'] as $m): ?>
        <div class="meta-chip">
            <span class="k"><?= htmlspecialchars((string) $m['label']) ?></span>
            <span class="v"><?= (int) $m['count'] ?> inv · <?= $money((float) $m['total']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($invoices)): ?>
    <div style="padding:28px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:6px;">
        No sales invoices in this period.
    </div>
    <?php else: ?>
    <table class="idx-table">
        <thead>
            <tr>
                <th style="width:28px;">#</th>
                <th>Invoice</th>
                <th>Date</th>
                <th>Customer</th>
                <th class="ctr" style="width:40px;">Lines</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $i => $inv): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars((string) $inv['invoice_no']) ?></td>
                <td><?= date('d M Y', strtotime((string) $inv['date'])) ?></td>
                <td><?= htmlspecialchars((string) $inv['party_name']) ?></td>
                <td class="ctr"><?= (int) $inv['line_count'] ?></td>
                <td class="num"><?= $money((float) $inv['grand_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php
    ob_start();
    ?>
        <p>
            We hereby certify that the invoices listed above, and the copies attached, are true extracts from the
            computerized sales records of <strong><?= htmlspecialchars((string) $companyName) ?></strong>
            for the period stated. This pack is issued solely for submission to the
            <strong>Public Authority of Manpower</strong>
            (<span class="ar" dir="rtl" lang="ar">الهيئة العامة للقوى العاملة</span>)
            to verify the company’s commercial activity.
        </p>
        <p>
            Cancelled invoices are excluded. Amounts are in <?= htmlspecialchars(APP_CURRENCY) ?>.
            Each attached page is a certified copy of an issued sales invoice.
            Scan the QR code to confirm this pack against live sales records at
            <?= htmlspecialchars($verifyHost) ?>.
        </p>
    <?php
    $certBodyHtml = ob_get_clean();
    $kycShowSignature  = false;
    $kycSignatoryLabel = 'Account department';
    $kycShowSignerName = false;
    include __DIR__ . '/partials/bank_kyc_certification.php';
    ?>

    <div class="cover-foot">
        <span><?= htmlspecialchars((string) $docRef) ?> · <?= htmlspecialchars(APP_NAME) ?></span>
        <span>A4 (ISO 216) · Generated <?= date('d M Y') ?></span>
    </div>
</div>

<?php if ($includeCopies): ?>
<?php foreach ($invoices as $inv): ?>
<div class="invoice-copy">
    <div class="copy-banner">
        <div class="brand">
            <div class="brand-names">
                <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
                <div class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
            </div>
            <div class="company-sub">
                <?php if ($companyAddress !== ''): ?><?= htmlspecialchars((string) $companyAddress) ?><?php endif; ?>
                <?php if ($companyPhone !== ''): ?><?= $companyAddress !== '' ? ' · ' : '' ?>Tel <?= htmlspecialchars((string) $companyPhone) ?><?php endif; ?>
            </div>
        </div>
            <div class="copy-mark">Certified copy · <span class="ar" dir="rtl" lang="ar">صورة طبق الأصل</span></div>
    </div>

    <div class="inv-head">
        <div class="inv-card">
            <div class="inv-card-kicker">Customer / <span class="ar" dir="rtl" lang="ar">العميل</span></div>
            <div class="name"><?= htmlspecialchars((string) $inv['party_name']) ?></div>
            <?php if (!empty($inv['party_phone'])): ?>
            <div class="phone" dir="ltr"><?= htmlspecialchars((string) $inv['party_phone']) ?></div>
            <?php endif; ?>
        </div>
        <div class="inv-card inv-card-doc">
            <div class="inv-doc-title">
                Sales Invoice
                <span class="ar" dir="rtl" lang="ar">فاتورة مبيعات</span>
            </div>
            <table class="inv-meta-list">
                <tr>
                    <th>Invoice number<span class="ar" dir="rtl" lang="ar">رقم الفاتورة</span></th>
                    <td dir="ltr"><?= htmlspecialchars((string) $inv['invoice_no']) ?></td>
                </tr>
                <tr>
                    <th>Date<span class="ar" dir="rtl" lang="ar">التاريخ</span></th>
                    <td dir="ltr"><?= date('d M Y', strtotime((string) $inv['date'])) ?></td>
                </tr>
                <tr>
                    <th>Branch<span class="ar" dir="rtl" lang="ar">الفرع</span></th>
                    <td><?= htmlspecialchars((string) ($inv['warehouse_name'] ?? $branchName)) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <table class="inv-table">
        <thead>
            <tr>
                <th style="width:22px;" class="c">#</th>
                <th>Description<br><span class="ar" dir="rtl" lang="ar">البيان</span></th>
                <th style="width:48px;" class="c">Qty<br><span class="ar" dir="rtl" lang="ar">الكمية</span></th>
                <th style="width:72px;" class="r">Price<br><span class="ar" dir="rtl" lang="ar">السعر</span></th>
                <th style="width:80px;" class="r">Amount<br><span class="ar" dir="rtl" lang="ar">المبلغ</span></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($inv['items'] as $n => $item): ?>
            <tr>
                <td class="c" style="color:#888;"><?= $n + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars((string) $item['item_name']) ?></strong>
                    <?php $lineNameAr = trim((string) ($item['item_name_ar'] ?? '')); ?>
                    <?php if ($lineNameAr !== ''): ?>
                    <div class="item-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($lineNameAr) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($item['sku'])): ?>
                    <span class="item-sku"><?= htmlspecialchars((string) $item['sku']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="c"><?= $fmtQty($item['quantity']) ?></td>
                <td class="r"><?= number_format((float) $item['unit_price'], DECIMAL_PLACES) ?></td>
                <td class="r"><strong><?= number_format((float) $item['total'], DECIMAL_PLACES) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <?php if ((float) ($inv['discount'] ?? 0) > 0): ?>
            <div class="t-row">
                <span>Discount / <span class="ar" dir="rtl" lang="ar">خصم</span></span>
                <span>- <?= $money((float) $inv['discount']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float) ($inv['tax'] ?? 0) > 0): ?>
            <div class="t-row">
                <span>Tax / <span class="ar" dir="rtl" lang="ar">ضريبة</span></span>
                <span><?= $money((float) $inv['tax']) ?></span>
            </div>
            <?php endif; ?>
            <div class="t-row t-grand">
                <span>Total / <span class="ar" dir="rtl" lang="ar">الإجمالي</span></span>
                <span><?= $money((float) $inv['grand_total']) ?></span>
            </div>
        </div>
    </div>

    <div class="inv-copy-foot">
        <div>
            Certified true copy for Public Authority of Manpower<br>
            <span class="ar" dir="rtl" lang="ar">صورة طبق الأصل — الهيئة العامة للقوى العاملة</span><br>
            <?= htmlspecialchars((string) $docRef) ?> · <?= date('d M Y') ?>
        </div>
        <div style="display:flex;align-items:flex-end;gap:10px;">
            <?php if ($verifyQrOk): ?>
            <div class="verify-qr-slot" aria-hidden="true"><?php $renderVerifyQr('24mm'); ?></div>
            <?php endif; ?>
            <?php if ($stampSrc !== null): ?>
            <img class="stamp-mini" src="<?= htmlspecialchars($stampSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company stamp" width="72" height="72">
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
    function doPrint() { window.print(); }
    var btnPdf = document.getElementById('btnSavePdf');
    var btnPrint = document.getElementById('btnPrint');
    if (btnPdf) btnPdf.addEventListener('click', doPrint);
    if (btnPrint) btnPrint.addEventListener('click', doPrint);
    window.addEventListener('load', function () {
        setTimeout(doPrint, 400);
    });
})();
</script>
</body>
</html>
