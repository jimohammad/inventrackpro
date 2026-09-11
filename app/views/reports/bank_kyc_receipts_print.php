<?php
/**
 * Bank KYC — A4 incoming payments / receipts register (ISO 216).
 * Date-grouped detail, method/account bars, compact formal letterhead.
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
$methodLabel = static function (string $m): string {
    $m = trim($m);
    if ($m === 'bank_like') {
        return 'Bank transfer / cheque / card';
    }
    if ($m === '' || $m === 'other' || $m === 'unspecified') {
        return 'Unspecified';
    }
    return ucwords(str_replace('_', ' ', $m));
};
$scopeBits = [];
$scopeBits[] = ($method === '') ? 'All payment methods' : $methodLabel($method);
$scopeBits[] = $excludeDiscount ? 'Discounts excluded' : 'Discounts included';
$scopeLabel = implode(' · ', $scopeBits);

$grandTotal = (float) ($summary['grand_total'] ?? 0);
$byMethod   = $summary['by_method'] ?? [];
$byAccount  = $summary['by_account'] ?? [];

// Group receipts by date for cleaner scanning
$byDate = [];
foreach ($receipts as $r) {
    $d = (string) ($r['date'] ?? '');
    if (!isset($byDate[$d])) {
        $byDate[$d] = ['date' => $d, 'rows' => [], 'total' => 0.0, 'count' => 0];
    }
    $byDate[$d]['rows'][] = $r;
    $byDate[$d]['total'] += (float) ($r['amount'] ?? 0);
    $byDate[$d]['count']++;
}

$rowNum = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank KYC Payments Received — <?= htmlspecialchars($periodLabel) ?></title>
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
            --ok: #0f766e;
            --ok-soft: #ecfdf5;
            --paper: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", "Helvetica Neue", Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: var(--ink);
            background: #dbe4f0;
            line-height: 1.35;
        }
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

        /* Header */
        .topbar {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 12px; margin-bottom: 10px;
        }
        .company-name {
            font-size: 13.5pt; font-weight: 800; color: var(--band);
            text-transform: uppercase; letter-spacing: 0.4px; line-height: 1.1;
        }
        .company-sub { font-size: 7pt; color: var(--muted); margin-top: 2px; max-width: 460px; }
        .badge-col { text-align: right; flex-shrink: 0; }
        .confidential {
            display: inline-block; font-size: 6.5pt; font-weight: 800; letter-spacing: 0.8px;
            text-transform: uppercase; color: #9f1239; background: #fff1f2;
            border: 1px solid #fecdd3; border-radius: 999px; padding: 3px 9px; margin-bottom: 5px;
        }
        .doc-ref { font-size: 7pt; color: var(--muted); font-variant-numeric: tabular-nums; }
        .doc-ref strong { color: var(--ink); }

        .hero {
            background: linear-gradient(135deg, var(--band) 0%, var(--band-mid) 100%);
            color: #fff; border-radius: 8px; padding: 12px 14px;
            margin-bottom: 10px;
            display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; align-items: center;
        }
        .hero-title {
            font-size: 11pt; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.7px; line-height: 1.2;
        }
        .hero-sub { font-size: 7.5pt; opacity: 0.82; margin-top: 3px; }
        .hero-total { text-align: right; }
        .hero-total .lbl {
            font-size: 6.5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.6px; opacity: 0.75;
        }
        .hero-total .val {
            font-size: 16pt; font-weight: 800; font-variant-numeric: tabular-nums;
            color: #5eead4; line-height: 1.15; margin-top: 2px;
        }
        .hero-total .cnt { font-size: 7.5pt; opacity: 0.8; margin-top: 2px; }

        .meta-strip {
            display: flex; flex-wrap: wrap; gap: 4px 0;
            padding: 7px 0 9px; margin-bottom: 10px;
            border-bottom: 1px solid var(--line-soft);
            font-size: 7.5pt;
        }
        .meta-chip {
            display: inline-flex; align-items: baseline; gap: 4px;
            padding: 0 10px; border-right: 1px solid var(--line-soft);
        }
        .meta-chip:first-child { padding-left: 0; }
        .meta-chip:last-child { border-right: none; }
        .meta-chip .k {
            color: var(--muted); font-weight: 700; text-transform: uppercase;
            font-size: 6pt; letter-spacing: 0.4px;
        }
        .meta-chip .v { font-weight: 700; color: var(--ink); }

        /* Breakdown with bars */
        .break-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;
        }
        .panel {
            border: 1px solid var(--line-soft); border-radius: 7px; overflow: hidden;
            background: #fff;
        }
        .panel-h {
            padding: 5px 9px; background: var(--band-soft);
            font-size: 6.5pt; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.55px; color: var(--muted);
            border-bottom: 1px solid var(--line-soft);
        }
        .bar-row {
            display: grid; grid-template-columns: 1fr 52px 78px;
            gap: 6px; align-items: center;
            padding: 5px 9px; border-bottom: 1px solid var(--line-soft);
            font-size: 7.5pt;
        }
        .bar-row:last-child { border-bottom: none; }
        .bar-name { font-weight: 650; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bar-track {
            grid-column: 1 / -1; height: 4px; background: #eef2f7;
            border-radius: 999px; overflow: hidden; margin: -1px 0 2px;
        }
        .bar-fill {
            height: 100%; background: linear-gradient(90deg, #0f766e, #14b8a6);
            border-radius: 999px;
        }
        .bar-fill.alt { background: linear-gradient(90deg, #1e3a5f, #3b82f6); }
        .bar-count { text-align: right; color: var(--muted); font-size: 7pt; font-variant-numeric: tabular-nums; }
        .bar-amt { text-align: right; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--ok); white-space: nowrap; }
        .panel-foot {
            display: grid; grid-template-columns: 1fr 52px 78px; gap: 6px;
            padding: 5px 9px; background: var(--band-soft);
            font-size: 7.5pt; font-weight: 800; border-top: 1px solid var(--line);
        }
        .panel-foot .r { text-align: right; font-variant-numeric: tabular-nums; }

        .section-label {
            font-size: 7pt; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.7px; color: var(--muted); margin: 2px 0 7px;
        }

        /* Date groups */
        .day {
            margin-bottom: 8px;
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
            break-inside: auto;
        }
        .day-head {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 10px; padding: 4px 9px;
            background: var(--band); color: #fff; font-size: 7.5pt;
        }
        .day-head .d { font-weight: 800; letter-spacing: 0.2px; }
        .day-head .m { opacity: 0.85; font-weight: 500; }
        .day-head .t { font-weight: 800; font-variant-numeric: tabular-nums; margin-left: auto; }

        table.rows {
            width: 100%; border-collapse: collapse; font-size: 7.5pt;
        }
        table.rows th {
            background: #edf2f7; color: var(--muted); font-size: 6pt;
            text-transform: uppercase; letter-spacing: 0.35px; font-weight: 700;
            padding: 3px 6px; text-align: left; border-bottom: 1px solid var(--line-soft);
        }
        table.rows th.num { text-align: right; }
        table.rows td {
            padding: 3.5px 6px; border-bottom: 1px solid var(--line-soft);
            vertical-align: middle;
        }
        table.rows tr:last-child td { border-bottom: none; }
        table.rows tr:nth-child(even) td { background: #f8fafc; }
        table.rows .idx { width: 22px; text-align: center; color: #94a3b8; font-size: 6.5pt; font-weight: 600; }
        table.rows .payno { font-weight: 800; white-space: nowrap; color: var(--band); }
        table.rows .party { font-weight: 650; }
        table.rows .sub { color: var(--muted); font-size: 6.5pt; font-weight: 400; }
        table.rows .pill {
            display: inline-block; padding: 1px 6px; border-radius: 999px;
            background: #eef2ff; color: #3730a3; font-size: 6.5pt; font-weight: 700;
            white-space: nowrap;
        }
        table.rows .pill.cash { background: #ecfdf5; color: #047857; }
        table.rows .pill.bank { background: #eff6ff; color: #1d4ed8; }
        table.rows .pill.card { background: #f5f3ff; color: #6d28d9; }
        table.rows .pill.cheque { background: #fff7ed; color: #c2410c; }
        table.rows .pill.wallet { background: #fdf2f8; color: #be185d; }
        table.rows .acc { color: #334155; }
        table.rows .ref { color: #475569; }
        table.rows .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        table.rows .amt { font-weight: 800; color: var(--ok); }
        table.rows tr { break-inside: avoid; }

        .grand {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 10px; padding: 8px 12px;
            background: var(--ok-soft); border: 1px solid #99f6e4; border-radius: 7px;
            font-size: 9pt; font-weight: 700; color: #115e59;
        }
        .grand .val { font-size: 12pt; font-weight: 800; font-variant-numeric: tabular-nums; }

        .empty {
            padding: 24px; text-align: center; color: var(--muted);
            border: 1px dashed var(--line); border-radius: 6px;
        }

        .declaration {
            margin-top: 14px; padding: 11px 12px; border: 1px solid var(--line);
            border-radius: 7px; background: #fafbfc; break-inside: avoid;
            font-size: 7.5pt; color: #334155;
        }
        .declaration h3 {
            font-size: 8pt; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.55px; color: var(--band); margin-bottom: 5px;
        }
        .declaration p { margin-bottom: 4px; text-align: justify; line-height: 1.45; }
        .sign-row {
            display: flex; justify-content: space-between; align-items: flex-end;
            gap: 24px; margin-top: 14px;
        }
        .sign-box { width: 46%; }
        .sign-box-stamp { text-align: center; }
        .sign-media {
            min-height: 72px; display: flex; align-items: flex-end; justify-content: flex-start;
            margin-bottom: 2px;
        }
        .stamp-media { justify-content: center; align-items: center; min-height: 96px; }
        .sign-media-empty { border-bottom: none; min-height: 56px; }
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
            border-top: 1px solid var(--ink); padding-top: 4px;
            color: var(--ink); font-weight: 650; font-size: 7pt;
        }
        .sign-name {
            display: block; margin-top: 2px; font-weight: 500; color: var(--muted); font-size: 6.5pt;
        }

        .footer {
            margin-top: 12px; padding-top: 5px; border-top: 1px solid var(--line-soft);
            display: flex; justify-content: space-between; font-size: 6.5pt; color: var(--muted);
        }

        @page { size: A4 portrait; margin: 9mm 9mm 11mm; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none !important; }
            .page { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .declaration { break-inside: avoid; }
            .day-head { break-after: avoid; }
            table.rows thead { display: table-header-group; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button type="button" class="btn-pdf" id="btnSavePdf">Save as PDF (A4)</button>
    <button type="button" class="btn-print" id="btnPrint">Print</button>
    <a class="btn-back" href="?page=reports&action=bankKycReceipts&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>&method=<?= urlencode($method) ?>&exclude_discount=<?= $excludeDiscount ? '1' : '0' ?>">Back</a>
    <span class="hint">Print → Save as PDF · Paper: A4 · Background graphics: On</span>
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
            <div class="hero-title">Incoming Payments / Receipts Register</div>
            <div class="hero-sub">Supporting schedule of money received for bank know-your-customer (KYC) documentation</div>
        </div>
        <div class="hero-total">
            <div class="lbl">Total received</div>
            <div class="val"><?= $money($grandTotal) ?></div>
            <div class="cnt"><?= number_format((int) $summary['count']) ?> receipts · <?= number_format(count($byAccount)) ?> accounts</div>
        </div>
    </div>

    <div class="meta-strip">
        <div class="meta-chip"><span class="k">Period</span><span class="v"><?= htmlspecialchars($periodLabel) ?></span></div>
        <div class="meta-chip"><span class="k">Branch</span><span class="v"><?= htmlspecialchars((string) $branchName) ?></span></div>
        <div class="meta-chip"><span class="k">Scope</span><span class="v"><?= htmlspecialchars($scopeLabel) ?></span></div>
        <div class="meta-chip"><span class="k">Prepared</span><span class="v"><?= htmlspecialchars((string) $preparedBy) ?> · <?= date('d M Y, H:i') ?></span></div>
    </div>

    <?php if (!empty($byMethod) || !empty($byAccount)): ?>
    <div class="break-grid">
        <div class="panel">
            <div class="panel-h">By payment method</div>
            <?php foreach ($byMethod as $bm):
                $pct = $grandTotal > 0 ? min(100, ((float) $bm['total'] / $grandTotal) * 100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-name"><?= htmlspecialchars($methodLabel((string) $bm['method'])) ?></div>
                <div class="bar-count"><?= (int) $bm['count'] ?></div>
                <div class="bar-amt"><?= $money((float) $bm['total']) ?></div>
                <div class="bar-track"><div class="bar-fill" style="width:<?= number_format($pct, 1) ?>%"></div></div>
            </div>
            <?php endforeach; ?>
            <div class="panel-foot">
                <div>Total</div>
                <div class="r"><?= (int) $summary['count'] ?></div>
                <div class="r"><?= $money($grandTotal) ?></div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-h">By account</div>
            <?php foreach ($byAccount as $ba):
                $pct = $grandTotal > 0 ? min(100, ((float) $ba['total'] / $grandTotal) * 100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-name"><?= htmlspecialchars((string) $ba['account']) ?></div>
                <div class="bar-count"><?= (int) $ba['count'] ?></div>
                <div class="bar-amt"><?= $money((float) $ba['total']) ?></div>
                <div class="bar-track"><div class="bar-fill alt" style="width:<?= number_format($pct, 1) ?>%"></div></div>
            </div>
            <?php endforeach; ?>
            <div class="panel-foot">
                <div>Total</div>
                <div class="r"><?= (int) $summary['count'] ?></div>
                <div class="r"><?= $money($grandTotal) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($receipts)): ?>
    <div class="empty">No incoming payments found for the selected period and filters.</div>
    <?php else: ?>

    <div class="section-label">Receipt detail — grouped by date</div>

    <?php foreach ($byDate as $day):
        $methodPill = static function (string $m): string {
            $m = strtolower(trim($m));
            if ($m === 'cash') return 'cash';
            if ($m === 'bank_transfer') return 'bank';
            if ($m === 'card') return 'card';
            if ($m === 'cheque') return 'cheque';
            if ($m === 'mobile_wallet') return 'wallet';
            return '';
        };
    ?>
    <div class="day">
        <div class="day-head">
            <span class="d"><?= $day['date'] !== '' ? date('l, d F Y', strtotime($day['date'])) : 'Unknown date' ?></span>
            <span class="m"><?= (int) $day['count'] ?> receipt<?= $day['count'] === 1 ? '' : 's' ?></span>
            <span class="t"><?= $money((float) $day['total']) ?></span>
        </div>
        <table class="rows">
            <thead>
                <tr>
                    <th class="idx">#</th>
                    <th>Payment</th>
                    <th>Party / payer</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th>Reference</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($day['rows'] as $r):
                $rowNum++;
                $pm = (string) ($r['payment_method'] ?? '');
                $pillClass = $methodPill($pm);
                $refParts = [];
                if (!empty($r['sale_invoice_no'])) {
                    $refParts[] = (string) $r['sale_invoice_no'];
                } elseif (!empty($r['ref_type'])) {
                    $refParts[] = ucfirst(str_replace('_', ' ', (string) $r['ref_type']));
                }
                if (!empty($r['cheque_no'])) {
                    $refParts[] = 'Chq ' . (string) $r['cheque_no'];
                }
            ?>
                <tr>
                    <td class="idx"><?= $rowNum ?></td>
                    <td class="payno"><?= htmlspecialchars((string) $r['payment_no']) ?></td>
                    <td>
                        <div class="party"><?= htmlspecialchars((string) ($r['party_name'] ?? '—')) ?></div>
                        <?php if (!empty($r['party_code']) || !empty($r['party_phone'])): ?>
                        <div class="sub">
                            <?php if (!empty($r['party_code'])): ?><?= htmlspecialchars((string) $r['party_code']) ?><?php endif; ?>
                            <?php if (!empty($r['party_phone'])): ?><?= !empty($r['party_code']) ? ' · ' : '' ?><?= htmlspecialchars((string) $r['party_phone']) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="pill<?= $pillClass !== '' ? ' ' . $pillClass : '' ?>"><?= htmlspecialchars($methodLabel($pm)) ?></span>
                    </td>
                    <td class="acc"><?= htmlspecialchars((string) ($r['account_name'] ?? '—')) ?></td>
                    <td class="ref"><?= $refParts ? htmlspecialchars(implode(' · ', $refParts)) : '—' ?></td>
                    <td class="num amt"><?= $money((float) $r['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <div class="grand">
        <span><?= number_format((int) $summary['count']) ?> receipts across <?= count($byDate) ?> day<?= count($byDate) === 1 ? '' : 's' ?></span>
        <span class="val"><?= $money($grandTotal) ?></span>
    </div>
    <?php endif; ?>

    <?php
    ob_start();
    ?>
        <p>
            We hereby certify that this document is a true extract from the computerized payment records of
            <strong><?= htmlspecialchars((string) $companyName) ?></strong>
            for the period stated above, listing <strong>incoming receipts</strong> recorded against company accounts.
            This schedule is issued solely for submission to our banking partner for know-your-customer (KYC) and compliance purposes.
        </p>
        <p>
            Cancelled payments are excluded<?= $excludeDiscount ? '; customer discount ledger entries are excluded' : '' ?>.
            Figures are denominated in <?= htmlspecialchars(APP_CURRENCY) ?> as recorded in the ERP.
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
