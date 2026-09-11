<?php
/**
 * Bank KYC — A4 sales invoice register with IMEI (ISO 216).
 * Compact multi-column IMEI grids; formal navy letterhead.
 * Use browser Print → Save as PDF (A4, portrait).
 */
$companyName    = $settings['company_name'] ?? PDF_COMPANY_NAME;
$companyAddress = $settings['company_address'] ?? (defined('PDF_COMPANY_ADDRESS') ? PDF_COMPANY_ADDRESS : '');
$companyPhone   = $settings['company_phone'] ?? PDF_COMPANY_PHONE;
$companyEmail   = $settings['company_email'] ?? (defined('PDF_COMPANY_EMAIL') ? PDF_COMPANY_EMAIL : '');
$branchName     = $warehouse['name'] ?? 'Main Branch';
$periodLabel    = date('d F Y', strtotime($fromDate)) . ' — ' . date('d F Y', strtotime($toDate));
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
$fmtQty = static function ($q): string {
    return rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
};
$imeiCols = 4;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank KYC Sales Register — <?= htmlspecialchars($periodLabel) ?></title>
    <style>
        :root {
            --ink: #0b1220;
            --muted: #64748b;
            --line: #d0d7e2;
            --line-soft: #e8edf4;
            --band: #0b1f36;
            --band-mid: #163455;
            --band-soft: #f3f6fa;
            --accent: #1a3a5c;
            --teal: #0f766e;
            --teal-soft: #ecfdf5;
            --cyan: #0891b2;
            --cyan-soft: #ecfeff;
            --indigo: #4f46e5;
            --amber: #b45309;
            --ok: #047857;
            --paper: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", "Helvetica Neue", Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: var(--ink);
            background: #dbe4f0;
            line-height: 1.4;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            background: var(--band);
            color: #fff;
            font-size: 12px;
        }
        .toolbar button, .toolbar a {
            border: none;
            border-radius: 5px;
            padding: 7px 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            font-size: 12px;
        }
        .toolbar .btn-pdf { background: #dc2626; }
        .toolbar .btn-print { background: #2563eb; }
        .toolbar .btn-back { background: #475569; }
        .toolbar .hint { opacity: 0.85; font-size: 11px; }

        .page {
            width: 210mm;
            max-width: 100%;
            margin: 12px auto 24px;
            padding: 12mm 12mm 14mm;
            background: var(--paper);
            box-shadow: 0 10px 30px rgba(11, 31, 54, 0.14);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 13.5pt;
            font-weight: 800;
            color: var(--band);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.1;
        }
        .company-sub {
            font-size: 7pt;
            color: var(--muted);
            margin-top: 2px;
            max-width: 460px;
        }
        .badge-col { text-align: right; flex-shrink: 0; }
        .confidential {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #9f1239;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 999px;
            padding: 3px 9px;
            margin-bottom: 5px;
        }
        .doc-ref {
            font-size: 7pt;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }
        .doc-ref strong { color: var(--ink); }

        .hero {
            background: linear-gradient(135deg, var(--band) 0%, #0e4a5c 55%, var(--teal) 100%);
            color: #fff;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 12px;
            align-items: center;
        }
        .hero-title {
            font-size: 11pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            line-height: 1.2;
        }
        .hero-sub { font-size: 7.5pt; opacity: 0.85; margin-top: 3px; }
        .hero-total { text-align: right; }
        .hero-total .lbl {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            opacity: 0.8;
        }
        .hero-total .val {
            font-size: 15pt;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: #5eead4;
            line-height: 1.15;
            margin-top: 2px;
        }
        .hero-total .cnt { font-size: 7.5pt; opacity: 0.85; margin-top: 2px; }

        .meta-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 0;
            padding: 7px 0 9px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--line-soft);
            font-size: 7.5pt;
        }
        .meta-chip {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            padding: 0 10px;
            border-right: 1px solid var(--line-soft);
        }
        .meta-chip:first-child { padding-left: 0; }
        .meta-chip:last-child { border-right: none; }
        .meta-chip .k {
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 6pt;
            letter-spacing: 0.4px;
        }
        .meta-chip .v { font-weight: 700; color: var(--ink); }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .sbox {
            border-radius: 7px;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid transparent;
        }
        .sbox.inv { background: #eef2ff; border-color: #c7d2fe; }
        .sbox.lines { background: #f5f3ff; border-color: #ddd6fe; }
        .sbox.imei { background: var(--cyan-soft); border-color: #a5f3fc; }
        .sbox.total { background: var(--teal-soft); border-color: #99f6e4; }
        .sbox-label {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
        }
        .sbox.inv .sbox-value { color: var(--indigo); }
        .sbox.lines .sbox-value { color: #7c3aed; }
        .sbox.imei .sbox-value { color: var(--cyan); }
        .sbox.total .sbox-value { color: var(--teal); }
        .sbox-value {
            margin-top: 3px;
            font-size: 12pt;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .sbox-value.sm { font-size: 9.5pt; }

        .section-label {
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--muted);
            margin: 2px 0 7px;
        }

        .invoice {
            margin-bottom: 10px;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            overflow: hidden;
            break-inside: auto;
            box-shadow: 0 1px 0 rgba(15, 118, 110, 0.06);
        }
        .inv-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
            padding: 5px 9px;
            background: linear-gradient(90deg, var(--band) 0%, #0f4c5c 70%, var(--teal) 100%);
            color: #fff;
            font-size: 8pt;
        }
        .inv-head .inv-no {
            font-weight: 800;
            font-size: 9pt;
            letter-spacing: 0.2px;
        }
        .inv-head .inv-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            opacity: 0.95;
            font-weight: 500;
        }
        .inv-head .inv-total {
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            margin-left: auto;
            color: #5eead4;
        }
        .status-pill {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 999px;
            background: rgba(255,255,255,0.2);
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-pill.paid { background: #d1fae5; color: #065f46; }
        .status-pill.partial { background: #fef3c7; color: #92400e; }
        .status-pill.unpaid { background: #fee2e2; color: #991b1b; }
        .status-pill.approved { background: #dbeafe; color: #1e40af; }

        .inv-customer {
            padding: 4px 9px;
            font-size: 7.5pt;
            color: #334155;
            background: linear-gradient(90deg, #ecfeff, #f0fdfa);
            border-bottom: 1px solid #a5f3fc;
        }
        .inv-customer strong { color: #0f766e; font-weight: 800; }
        .inv-customer .sep { color: #67e8f9; margin: 0 4px; }

        .item-block {
            border-bottom: 1px solid var(--line-soft);
            break-inside: avoid;
        }
        .item-block:last-child { border-bottom: none; }
        .item-bar {
            display: grid;
            grid-template-columns: 22px 1fr 40px 78px;
            gap: 6px;
            align-items: baseline;
            padding: 5px 9px 3px;
            font-size: 8pt;
            background: #fff;
        }
        .item-bar .n {
            color: var(--cyan);
            font-weight: 800;
            font-size: 7pt;
            text-align: center;
        }
        .item-bar .name { font-weight: 700; color: var(--ink); }
        .item-bar .sub {
            font-weight: 400;
            color: var(--muted);
            font-size: 7pt;
            margin-top: 1px;
        }
        .item-bar .qty {
            text-align: center;
            font-variant-numeric: tabular-nums;
            color: #334155;
            font-size: 7.5pt;
            font-weight: 700;
        }
        .item-bar .amt {
            text-align: right;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--ok);
            font-size: 7.5pt;
            white-space: nowrap;
        }

        .imei-wrap {
            padding: 0 8px 6px;
            background: linear-gradient(180deg, #f0fdfa 0%, #fff 40%);
        }
        .imei-caption {
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--teal);
            margin: 0 1px 3px;
        }
        table.imei-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7pt;
        }
        table.imei-grid td {
            padding: 2px 4px;
            border: 1px solid #99f6e4;
            vertical-align: middle;
            background: #fff;
        }
        table.imei-grid tr:nth-child(even) td { background: #ecfeff; }
        table.imei-grid .idx {
            width: 18px;
            text-align: center;
            color: #0e7490;
            font-size: 6pt;
            font-weight: 700;
            background: #cffafe !important;
        }
        table.imei-grid .serial {
            font-family: "Consolas", "Cascadia Mono", "Courier New", monospace;
            font-weight: 700;
            font-size: 7pt;
            letter-spacing: 0.1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0f766e;
        }
        table.imei-grid .serial2 {
            display: block;
            color: #0891b2;
            font-weight: 500;
            font-size: 6pt;
        }
        table.imei-grid td.empty {
            background: #f8fafc !important;
            border-color: #e2e8f0;
        }
        .no-imei {
            padding: 2px 9px 6px;
            font-size: 7pt;
            color: #94a3b8;
            font-style: italic;
            background: #f8fafc;
        }

        .inv-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 9px;
            background: linear-gradient(90deg, #ecfdf5, #ecfeff);
            border-top: 1px solid #5eead4;
            font-size: 7.5pt;
            font-weight: 700;
            color: #115e59;
        }
        .inv-foot .tot { color: var(--teal); font-size: 8.5pt; font-weight: 800; }

        .grand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0 4px;
            padding: 8px 12px;
            background: var(--teal-soft);
            border: 1px solid #99f6e4;
            border-radius: 7px;
            font-size: 9pt;
            font-weight: 700;
            color: #115e59;
        }
        .grand .val {
            font-size: 12pt;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--teal);
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--line);
            border-radius: 6px;
        }

        .declaration {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px solid #99f6e4;
            border-radius: 7px;
            background: linear-gradient(180deg, #f0fdfa, #fafbfc);
            break-inside: avoid;
            font-size: 8pt;
            color: #334155;
        }
        .declaration h3 {
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--teal);
            margin-bottom: 6px;
        }
        .declaration p {
            margin-bottom: 5px;
            text-align: justify;
            line-height: 1.45;
        }
        .sign-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin-top: 14px;
        }
        .sign-box { width: 46%; }
        .sign-box-stamp { text-align: center; }
        .sign-media {
            min-height: 72px;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            margin-bottom: 2px;
        }
        .stamp-media { justify-content: center; align-items: center; min-height: 96px; }
        .sign-media-empty { min-height: 56px; }
        .sig-img {
            max-height: 90px; max-width: 230px; width: auto; height: auto;
            object-fit: contain; display: block;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .stamp-img {
            max-height: 118px; max-width: 118px; width: auto; height: auto;
            object-fit: contain; display: block;
            opacity: 1;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .sign-line {
            border-top: 1px solid var(--ink);
            padding-top: 4px;
            color: var(--ink);
            font-weight: 650;
            font-size: 7.5pt;
        }
        .sign-name {
            display: block;
            margin-top: 2px;
            font-weight: 500;
            color: var(--muted);
            font-size: 7pt;
        }

        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid var(--line-soft);
            display: flex;
            justify-content: space-between;
            font-size: 6.5pt;
            color: var(--muted);
        }

        @page {
            size: A4 portrait;
            margin: 10mm 10mm 12mm;
        }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none !important; }
            .page {
                width: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .item-block { break-inside: avoid; }
            .declaration { break-inside: avoid; }
            .invoice { break-inside: auto; }
            .hero, .sbox, .inv-head, .inv-customer, .imei-wrap, .inv-foot, .grand {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button type="button" class="btn-pdf" id="btnSavePdf">Save as PDF (A4)</button>
    <button type="button" class="btn-print" id="btnPrint">Print</button>
    <a class="btn-back" href="?page=reports&action=bankKyc&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>&imei_only=<?= $imeiOnly ? '1' : '0' ?>">Back</a>
    <span class="hint">Print → Save as PDF · Paper: A4 · Margins: Default · Background graphics: On</span>
</div>

<div class="page" id="kyc-doc">

    <div class="topbar">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <?php if ($companyAddress !== ''): ?>
            <div class="company-sub"><?= htmlspecialchars((string) $companyAddress) ?></div>
            <?php endif; ?>
            <div class="company-sub">
                <?php if ($companyPhone !== ''): ?>Tel <?= htmlspecialchars((string) $companyPhone) ?><?php endif; ?>
                <?php if ($companyEmail !== ''): ?><?= $companyPhone !== '' ? '  ·  ' : '' ?><?= htmlspecialchars((string) $companyEmail) ?><?php endif; ?>
            </div>
        </div>
        <div class="badge-col">
            <div class="confidential">Confidential — Banking / KYC</div>
            <div class="doc-ref">Ref <strong><?= htmlspecialchars((string) $docRef) ?></strong></div>
        </div>
    </div>

    <div class="hero">
        <div>
            <div class="hero-title">Sales Invoice Register with IMEI / Serial Numbers</div>
            <div class="hero-sub">Supporting schedule for company bank know-your-customer (KYC) documentation</div>
        </div>
        <div class="hero-total">
            <div class="lbl">Sales total</div>
            <div class="val"><?= $money((float) $summary['grand_total']) ?></div>
            <div class="cnt">
                <?= number_format((int) $summary['invoice_count']) ?> invoices ·
                <?= number_format((int) $summary['imei_count']) ?> IMEI units
            </div>
        </div>
    </div>

    <div class="meta-strip">
        <div class="meta-chip"><span class="k">Period</span><span class="v"><?= htmlspecialchars($periodLabel) ?></span></div>
        <div class="meta-chip"><span class="k">Branch</span><span class="v"><?= htmlspecialchars((string) $branchName) ?></span></div>
        <div class="meta-chip"><span class="k">Scope</span><span class="v"><?= $imeiOnly ? 'Invoices with IMEI / serials' : 'All sales (IMEI where recorded)' ?></span></div>
        <div class="meta-chip"><span class="k">Prepared</span><span class="v"><?= htmlspecialchars((string) $preparedBy) ?> · <?= date('d M Y, H:i') ?></span></div>
    </div>

    <div class="summary">
        <div class="sbox inv">
            <div class="sbox-label">Invoices</div>
            <div class="sbox-value"><?= number_format((int) $summary['invoice_count']) ?></div>
        </div>
        <div class="sbox lines">
            <div class="sbox-label">Line items</div>
            <div class="sbox-value"><?= number_format((int) $summary['line_count']) ?></div>
        </div>
        <div class="sbox imei">
            <div class="sbox-label">IMEI units</div>
            <div class="sbox-value"><?= number_format((int) $summary['imei_count']) ?></div>
        </div>
        <div class="sbox total">
            <div class="sbox-label">Sales total</div>
            <div class="sbox-value sm"><?= $money((float) $summary['grand_total']) ?></div>
        </div>
    </div>

    <?php if (empty($invoices)): ?>
    <div class="empty">No sales invoices found for the selected period and scope.</div>
    <?php else: ?>

    <div class="section-label">Invoice detail</div>

    <?php foreach ($invoices as $idx => $inv):
        $globalImei = 1;
        $st = strtolower(trim((string) ($inv['status'] ?? '')));
        $statusClass = match ($st) {
            'paid' => 'paid',
            'partial', 'partially_paid' => 'partial',
            'unpaid', 'pending' => 'unpaid',
            'approved' => 'approved',
            default => '',
        };
    ?>
    <div class="invoice">
        <div class="inv-head">
            <span class="inv-no"><?= (int) ($idx + 1) ?>. <?= htmlspecialchars((string) $inv['invoice_no']) ?></span>
            <span class="inv-meta">
                <span><?= date('d M Y', strtotime((string) $inv['date'])) ?></span>
                <span class="status-pill<?= $statusClass !== '' ? ' ' . $statusClass : '' ?>"><?= htmlspecialchars(ucfirst((string) $inv['status'])) ?></span>
            </span>
            <span class="inv-total"><?= $money((float) $inv['grand_total']) ?></span>
        </div>
        <div class="inv-customer">
            <strong><?= htmlspecialchars((string) $inv['party_name']) ?></strong>
            <?php if (!empty($inv['party_code'])): ?>
            <span class="sep">·</span>Code <?= htmlspecialchars((string) $inv['party_code']) ?>
            <?php endif; ?>
            <?php if (!empty($inv['party_phone'])): ?>
            <span class="sep">·</span><?= htmlspecialchars((string) $inv['party_phone']) ?>
            <?php endif; ?>
            <?php if (!empty($inv['tax_no'])): ?>
            <span class="sep">·</span>Tax/CR <?= htmlspecialchars((string) $inv['tax_no']) ?>
            <?php endif; ?>
            <?php if (!empty($inv['id_card'])): ?>
            <span class="sep">·</span>ID <?= htmlspecialchars((string) $inv['id_card']) ?>
            <?php endif; ?>
        </div>

        <?php if (empty($inv['items'])): ?>
        <div class="no-imei">No line items on this invoice.</div>
        <?php else: ?>
            <?php foreach ($inv['items'] as $li => $line):
                $sub = trim(($line['brand'] ?? '') . ' ' . ($line['model'] ?? ''));
                $imeis = $line['imeis'] ?? [];
                $hasImei2 = false;
                foreach ($imeis as $im) {
                    if (!empty($im['imei2'])) {
                        $hasImei2 = true;
                        break;
                    }
                }
                $cols = $hasImei2 ? 3 : $imeiCols;
            ?>
            <div class="item-block">
                <div class="item-bar">
                    <div class="n"><?= (int) ($li + 1) ?></div>
                    <div>
                        <div class="name"><?= htmlspecialchars((string) $line['item_name']) ?></div>
                        <?php if ($sub !== '' || !empty($line['sku'])): ?>
                        <div class="sub">
                            <?php if ($sub !== ''): ?><?= htmlspecialchars($sub) ?><?php endif; ?>
                            <?php if (!empty($line['sku'])): ?><?= $sub !== '' ? ' · ' : '' ?>SKU <?= htmlspecialchars((string) $line['sku']) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="qty">×<?= $fmtQty($line['quantity']) ?></div>
                    <div class="amt"><?= $money((float) $line['total']) ?></div>
                </div>

                <?php if (empty($imeis)): ?>
                <div class="no-imei">No IMEI / serial recorded for this line</div>
                <?php else:
                    $rows = [];
                    foreach ($imeis as $im) {
                        $rows[] = [
                            'n'     => $globalImei++,
                            'imei'  => (string) ($im['imei'] ?? ''),
                            'imei2' => (string) ($im['imei2'] ?? ''),
                        ];
                    }
                    $chunks = array_chunk($rows, $cols);
                ?>
                <div class="imei-wrap">
                    <div class="imei-caption"><?= count($imeis) ?> serial<?= count($imeis) === 1 ? '' : 's' ?></div>
                    <table class="imei-grid">
                        <tbody>
                        <?php foreach ($chunks as $chunk): ?>
                            <tr>
                            <?php for ($c = 0; $c < $cols; $c++): ?>
                                <?php if (isset($chunk[$c])): ?>
                                <td class="idx"><?= (int) $chunk[$c]['n'] ?></td>
                                <td class="serial">
                                    <?= htmlspecialchars($chunk[$c]['imei']) ?>
                                    <?php if ($hasImei2 && $chunk[$c]['imei2'] !== ''): ?>
                                    <span class="serial2"><?= htmlspecialchars($chunk[$c]['imei2']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php else: ?>
                                <td class="idx empty"></td>
                                <td class="serial empty"></td>
                                <?php endif; ?>
                            <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="inv-foot">
            <span><?= (int) $inv['imei_count'] ?> IMEI unit<?= ((int) $inv['imei_count'] === 1) ? '' : 's' ?> · <?= (int) $inv['line_count'] ?> line<?= ((int) $inv['line_count'] === 1) ? '' : 's' ?></span>
            <span class="tot">Invoice total <?= $money((float) $inv['grand_total']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="grand">
        <span><?= number_format((int) $summary['invoice_count']) ?> invoices · <?= number_format((int) $summary['imei_count']) ?> IMEI units</span>
        <span class="val"><?= $money((float) $summary['grand_total']) ?></span>
    </div>

    <?php endif; ?>

    <?php
    ob_start();
    ?>
        <p>
            We hereby certify that this document is a true extract from the computerized sales records of
            <strong><?= htmlspecialchars((string) $companyName) ?></strong>
            for the period stated above, listing sales invoices and, where applicable, the International Mobile
            Equipment Identity (IMEI) / serial numbers of devices sold. This schedule is issued solely for
            submission to our banking partner for know-your-customer (KYC) and compliance purposes.
        </p>
        <p>
            Cancelled invoices are excluded. Figures are denominated in <?= htmlspecialchars(APP_CURRENCY) ?>.
            Device serials are as recorded at the time of sale.
        </p>
    <?php
    $certBodyHtml = ob_get_clean();
    include __DIR__ . '/partials/bank_kyc_certification.php';
    ?>

    <div class="footer">
        <span><?= htmlspecialchars((string) $docRef) ?> · <?= htmlspecialchars(APP_NAME) ?></span>
        <span>A4 (ISO 216) · Generated <?= date('d M Y') ?></span>
    </div>
</div>

<script>
(function () {
    function doPrint() { window.print(); }
    var btnPdf = document.getElementById('btnSavePdf');
    var btnPrint = document.getElementById('btnPrint');
    if (btnPdf) btnPdf.addEventListener('click', doPrint);
    if (btnPrint) btnPrint.addEventListener('click', doPrint);
    window.addEventListener('load', function () {
        setTimeout(doPrint, 500);
    });
})();
</script>
</body>
</html>
