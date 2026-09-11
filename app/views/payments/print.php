<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $isIn = ($payment['payment_type'] ?? $payment['type'] ?? 'in') === 'in'; ?>
<title><?= $isIn ? 'Payment Receipt Voucher' : 'Payment Voucher' ?> <?= htmlspecialchars((string)($payment['payment_no'] ?? '')) ?></title>
<?php
// Receipt/thermal layout: controller flag, any `thermal` query key (Hostinger paste behavior), or thermalPrint action.
$paymentPrintThermal = isset($paymentPrintThermal) ? (bool) $paymentPrintThermal : false;
$act                 = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
$thermal             = $paymentPrintThermal
    || (isset($isThermal) && $isThermal)
    || (isset($_GET['thermal']) && !isset($_GET['autopdf']))
    || ($act === 'thermalprint');
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

<?php if (!$thermal): ?>
/* ══════════════════════════════════
   A5 PDF — GCC official voucher (bilingual EN/AR)
══════════════════════════════════ */
<?php
$gccBand   = '#0b1f36';
$gccAccent = $isIn ? '#0f766e' : '#9f1239';
$gccGold   = '#b8954a';
$gccInk    = '#0b1220';
$gccMuted  = '#5b6b82';
$gccLine   = '#c5d0e0';
?>
/* ══════════════════════════════════
   A5 PDF — GCC official voucher (bilingual EN/AR)
══════════════════════════════════ */
body { font-family: 'Segoe UI', Tahoma, 'Noto Naskh Arabic', Arial, sans-serif; font-size: 8.5px; color: <?= $gccInk ?>; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 6mm 8mm; }
.ar { font-family: 'Segoe UI', Tahoma, 'Noto Naskh Arabic', Arial, sans-serif; unicode-bidi: isolate; }

.letterhead { width: 100%; text-align: center; margin-bottom: 6px; }
.company-name { font-size: 13px; font-weight: 800; color: <?= $gccBand ?>; letter-spacing: 0.2px; line-height: 1.2; }
.company-name-ar { font-size: 12px; font-weight: 700; color: <?= $gccBand ?>; line-height: 1.35; margin-top: 2px; }
.company-info { font-size: 7.5px; color: <?= $gccMuted ?>; margin-top: 3px; line-height: 1.45; }
.company-contact { display: block; margin-top: 2px; color: #334155; }
.id-strip {
    border-top: 1px solid <?= $gccGold ?>;
    border-bottom: 1px solid <?= $gccGold ?>;
    padding: 3px 0;
    margin-bottom: 7px;
    font-size: 7px;
    color: #334155;
    font-weight: 600;
    text-align: center;
}
.id-strip .sep { color: <?= $gccGold ?>; padding: 0 6px; }

.doc-head { width: 100%; border-collapse: collapse; margin-bottom: 7px; background: #f4f7fb; color: <?= $gccInk ?>; border: 1px solid <?= $gccLine ?>; }
.doc-head td { vertical-align: middle; padding: 8px 10px; }
.doc-head .doc-title { font-size: 9px; font-weight: 700; color: <?= $gccMuted ?>; line-height: 1.3; }
.doc-head .doc-no { font-size: 15px; font-weight: 800; letter-spacing: 0.3px; margin-top: 2px; color: <?= $gccBand ?>; }
.doc-head .qr-cell { width: 24mm; text-align: right; padding: 5px 7px; }
.doc-head .qr-cell img { width: 20mm; height: 20mm; background: #fff; padding: 2px; border: 1px solid <?= $gccLine ?>; }

.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 7px; table-layout: fixed; }
.meta-table td { border: 1px solid <?= $gccLine ?>; padding: 5px 7px; vertical-align: top; background: #f7f9fc; }
.meta-table .mlbl { display: block; font-size: 6.5px; font-weight: 700; color: <?= $gccMuted ?>; line-height: 1.3; margin-bottom: 2px; }
.meta-table .mval { font-size: 8.5px; font-weight: 700; color: <?= $gccInk ?>; }

.party-table { width: 100%; border-collapse: collapse; margin-bottom: 7px; table-layout: fixed; }
.party-table td { border: 1px solid <?= $gccLine ?>; padding: 6px 8px; vertical-align: top; }
.party-table .plbl { display: block; font-size: 6.5px; font-weight: 700; color: <?= $gccAccent ?>; line-height: 1.3; margin-bottom: 2px; }
.party-table .pval { font-size: 9px; font-weight: 800; color: <?= $gccInk ?>; line-height: 1.35; }
.party-table .pmeta { font-size: 8px; color: #334155; margin-top: 2px; }

.amount-hero {
    text-align: center;
    margin: 0 0 6px;
    padding: 10px 10px 8px;
    background: #f4f7fb;
    color: <?= $gccBand ?>;
    border: 1px solid <?= $gccLine ?>;
}
.amount-hero .amount-label { font-size: 8px; font-weight: 700; line-height: 1.35; color: <?= $gccAccent ?>; }
.amount-hero .amount-value { font-size: 22px; font-weight: 800; margin-top: 2px; letter-spacing: 0.3px; font-variant-numeric: tabular-nums; }

.words-box {
    border: 1px solid <?= $gccGold ?>;
    background: #fbf8f1;
    padding: 6px 8px;
    margin-bottom: 7px;
    text-align: center;
}
.words-box .wlbl { font-size: 6.5px; font-weight: 700; color: #8a7040; margin-bottom: 2px; }
.words-box .wen { font-size: 8px; font-weight: 700; color: <?= $gccInk ?>; line-height: 1.35; }
.words-box .war { font-size: 8.5px; font-weight: 700; color: <?= $gccBand ?>; margin-top: 2px; line-height: 1.4; text-align: center; }

.ledger-table { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
.ledger-table td { padding: 5px 8px; border-bottom: 1px solid #e8edf4; font-size: 8.5px; }
.ledger-table .lbl { color: #445066; }
.ledger-table .val { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
.ledger-table tr.t-payment td { color: <?= $gccAccent ?>; font-weight: 800; }
.ledger-table tr.t-current td {
    background: #f4f7fb;
    color: <?= $gccBand ?>;
    font-size: 10px;
    font-weight: 800;
    border-bottom: none;
    border-top: 1px solid <?= $gccLine ?>;
}
.ledger-table tr.t-current td.val { font-size: 11px; }

.notes-box {
    margin-bottom: 7px;
    padding: 5px 8px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    font-size: 8px;
    color: #555;
}
.notes-box strong { display: block; color: #92400e; font-size: 7px; margin-bottom: 2px; }

.legal-line {
    font-size: 7px;
    color: <?= $gccMuted ?>;
    margin-bottom: 8px;
    padding: 3px 0;
    border-top: 1px dashed <?= $gccLine ?>;
}

.inv-footer { text-align: center; color: #7b8aa0; font-size: 7px; line-height: 1.45; }

.no-print {
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin: 0 auto 16px;
    width: max-content;
    max-width: 100%;
    overflow-x: auto;
}
.no-print button {
    flex: 0 0 auto;
    white-space: nowrap;
    background: #6366f1;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    font-family: system-ui, sans-serif;
}
.no-print button.close-btn { background: #e5e7eb; color: #444; }
.no-print button.edit-btn  { background: #f59e0b; }

@media screen {
    body { background: #dbe4f0; padding: 20px; }
    .wrap { box-shadow: 0 8px 28px rgba(11,31,54,0.16); background: #fff; }
}
@media print {
    body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .wrap { max-width: 100%; width: 100%; padding: 0; margin: 0; box-shadow: none; }
    @page { size: A5 portrait; margin: 8mm 8mm; }
}

<?php else: ?>
/* ══════════════════════════════════
   THERMAL — Receipt Style (bilingual EN/AR)
══════════════════════════════════ */
<?php include __DIR__ . '/../partials/thermal_print_font.css.php'; ?>
body {
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 8px 2px;
}
.ar {
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    unicode-bidi: isolate;
}

.no-print {
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin: 0 auto 16px;
    width: max-content;
    max-width: 100%;
    overflow-x: auto;
}
.no-print button {
    flex: 0 0 auto;
    white-space: nowrap;
    background: #6366f1; color: #fff; border: none;
    padding: 6px 12px; border-radius: 5px; font-size: 12px;
    cursor: pointer;
    font-family: system-ui, sans-serif;
}
.no-print button.close-btn { background: #e5e7eb; color: #444; }
.no-print button.edit-btn  { background: #f59e0b; }

.wrap {
    max-width: 80mm;
    margin: 0 auto;
    padding: 8px 3px;
    font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
}

.receipt-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #e5e7eb; }
.company-name { font-size: 18px; font-weight: 800; color: #000; }
.company-name-ar { font-size: 13px; font-weight: 700; margin-top: 3px; line-height: 1.35; }
.company-info { font-size: 11px; color: #000; margin-top: 4px; line-height: 1.35; }

.receipt-title {
    text-align: center;
    font-size: 11px; font-weight: 800;
    text-transform: none;
    letter-spacing: 0;
    color: #000;
    margin: 14px 0;
    line-height: 1.35;
}

.receipt-row {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11.5px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .lbl { color: #000; font-size: 10.5px; max-width: 58%; line-height: 1.25; direction: ltr; unicode-bidi: isolate; }
.receipt-row .val { font-weight: 400; color: #000; text-align: right; flex-shrink: 0; }

.current-balance-row {
    border-top: 2px solid #000;
    padding-top: 8px;
    margin-top: 4px;
    border-bottom: none;
}
.current-balance-row .lbl,
.current-balance-row .val {
    font-weight: bold !important;
    font-size: 12px !important;
    color: #000 !important;
}
.current-balance-row .val { font-size: 14px !important; }

.amount-box {
    margin: 18px 0;
    padding: 14px;
    text-align: center;
    background: transparent;
    border: 2px solid #000;
    border-radius: 0;
}
.amount-label { font-size: 10px; color: #000; font-weight: 700; text-transform: none; letter-spacing: 0; line-height: 1.3; }
.amount-value { font-size: 26px; font-weight: 800; color: #000; margin-top: 4px; }
.words-thermal { margin: 8px 0 12px; font-size: 9.5px; line-height: 1.4; text-align: center; }
.legal-thermal { font-size: 8.5px; color: #000; margin-top: 6px; line-height: 1.35; }

.footer {
    text-align: center;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 2px dashed #e5e7eb;
    font-size: 10px;
    color: #888;
}

@media print {
    body {
        padding: 0;
        background: #fff;
        font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
        font-weight: 400;
        -webkit-font-smoothing: none;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .no-print { display: none !important; }
    .wrap {
        border: none;
        padding: 0;
        width: 100%;
        max-width: 100%;
        font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif;
    }
    .ar { font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", Arial, sans-serif; }
    @page { size: 80mm 297mm; margin: 2mm; }
}
<?php endif; ?>
</style>
</head>
<body>

<div class="no-print">
    <button type="button" id="btnPrint"><i>⎙</i> Print</button>
    <?php if (!$thermal): ?>
    <button type="button" id="btnThermal" style="background:#059669;color:#fff;"><b>🖨 Thermal</b></button>
    <?php else: ?>
    <button type="button" id="btnA5" style="background:#6366f1;color:#fff;"><b>⬚ A5 PDF</b></button>
    <?php endif; ?>
    <button type="button" id="btnPdf" style="background:#dc2626;color:#fff;"><b>⤓ PDF</b></button>
    <?php if (!empty($canEditPayment)): ?>
    <button type="button" class="edit-btn" id="btnEdit"><i>✎</i> Edit</button>
    <?php endif; ?>
    <button type="button" class="close-btn" id="btnClose">Close</button>
</div>

<?php
require __DIR__ . '/../sales/_thermal_labels_ar.php';
require_once __DIR__ . '/../../helpers/MoneyWords.php';

$companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
if ($companyNameAr === '') {
    $companyNameAr = 'شركة إقبال للأجهزة الإلكترونية ذ.م.م';
}
$companyAddressAr = trim((string) ($settings['company_address_ar'] ?? ''));
$companyPhone     = trim((string) ($settings['company_phone'] ?? (defined('PDF_COMPANY_PHONE') ? PDF_COMPANY_PHONE : '')));
$companyEmail     = trim((string) ($settings['company_email'] ?? (defined('PDF_COMPANY_EMAIL') ? PDF_COMPANY_EMAIL : '')));
$companyCr        = trim((string) ($settings['company_cr'] ?? ''));
$companyLicense   = trim((string) ($settings['company_license'] ?? ''));
$invoiceFooter = trim((string) ($settings['invoice_footer'] ?? ''));
$useDefaultThankYou = ($invoiceFooter === '' || $invoiceFooter === 'Thank you for your business!');
$amountLabelKey = $isIn ? 'amount_received' : 'amount_paid';
$paymentMoveKey = $isIn ? 'payment_received' : 'payment_made';
$docTitleKey    = $isIn ? 'payment_receipt' : 'payment_voucher';
$docNoKey       = $isIn ? 'receipt_no' : 'voucher_no';
$partyRoleKey   = $isIn ? 'received_from' : 'paid_to';
$methodRaw      = strtolower(str_replace('-', '_', (string) ($payment['payment_method'] ?? 'cash')));
if (!empty($payment['cheque_no']) || $methodRaw === 'cheque') {
    $modeLabelKey = 'cheque';
} elseif (in_array($methodRaw, ['bank', 'bank_transfer'], true)) {
    $modeLabelKey = 'bank_account';
} elseif ($methodRaw === 'card') {
    $modeLabelKey = 'card';
} elseif ($methodRaw === 'mobile_wallet') {
    $modeLabelKey = 'mobile_wallet';
} else {
    $modeLabelKey = 'cash';
}

$partyPhone = trim((string) ($payment['phone_no'] ?? ''));
if ($partyPhone === '') {
    $partyPhone = trim((string) ($payment['party_phone'] ?? ''));
}
$amountWords = MoneyWords::kwd((float) ($payment['amount'] ?? 0));
$payDate = date('d M Y', strtotime($payment['date'] ?? 'now'));
$payNo = (string) ($payment['payment_no'] ?? '');
$moneyFmt = static function ($n): string {
    return APP_CURRENCY . ' ' . number_format((float) $n, DECIMAL_PLACES);
};

$qrSrc = null;
try {
    require_once __DIR__ . '/../../helpers/PaymentReceiptVerify.php';
    require_once __DIR__ . '/../../helpers/QrSvg.php';
    $qrSrc = PaymentReceiptVerify::qrPngDataUri(Database::getInstance(), $payment);
} catch (Throwable $e) {
    $qrSrc = null;
}
?>
<div class="wrap">
<?php if (!$thermal): ?>
    <div class="letterhead">
        <div class="company-name"><?= htmlspecialchars((string)($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string)($settings['company_address'] ?? ''))) ?>
            <?php if ($companyAddressAr !== ''): ?>
            <br><span class="ar" dir="rtl" lang="ar"><?= nl2br(htmlspecialchars($companyAddressAr)) ?></span>
            <?php endif; ?>
            <?php if ($companyPhone !== '' || $companyEmail !== ''): ?>
            <span class="company-contact">
                <?php if ($companyPhone !== ''): ?>
                Tel: <span dir="ltr"><?= htmlspecialchars($companyPhone) ?></span>
                <?php endif; ?>
                <?php if ($companyPhone !== '' && $companyEmail !== ''): ?>
                <span> · </span>
                <?php endif; ?>
                <?php if ($companyEmail !== ''): ?>
                <span dir="ltr"><?= htmlspecialchars($companyEmail) ?></span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($companyCr !== '' || $companyLicense !== ''): ?>
    <div class="id-strip">
        <?php if ($companyCr !== ''): ?>
        <?= thermalBiLabel('cr_no') ?>: <span dir="ltr"><?= htmlspecialchars($companyCr) ?></span>
        <?php endif; ?>
        <?php if ($companyCr !== '' && $companyLicense !== ''): ?>
        <span class="sep">|</span>
        <?php endif; ?>
        <?php if ($companyLicense !== ''): ?>
        <?= thermalBiLabel('license_no') ?>: <span dir="ltr"><?= htmlspecialchars($companyLicense) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <table class="doc-head">
        <tr>
            <td>
                <div class="doc-title"><?= thermalBiLabel($docTitleKey) ?></div>
                <div class="doc-no" dir="ltr"><?= htmlspecialchars($payNo) ?></div>
            </td>
            <?php if ($qrSrc): ?>
            <td class="qr-cell">
                <img src="<?= htmlspecialchars($qrSrc, ENT_QUOTES, 'UTF-8') ?>" alt="QR" width="76" height="76">
            </td>
            <?php endif; ?>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <span class="mlbl"><?= thermalBiLabel($docNoKey) ?></span>
                <span class="mval" dir="ltr"><?= htmlspecialchars($payNo) ?></span>
            </td>
            <td>
                <span class="mlbl"><?= thermalBiLabel('date') ?></span>
                <span class="mval" dir="ltr"><?= htmlspecialchars($payDate) ?></span>
            </td>
            <td>
                <span class="mlbl"><?= thermalBiLabel('payment_mode') ?></span>
                <span class="mval"><?= thermalBiLabel($modeLabelKey) ?></span>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <span class="plbl"><?= thermalBiLabel($partyRoleKey) ?></span>
                <span class="pval"><?= htmlspecialchars((string)($payment['party_name'] ?? '—')) ?></span>
                <?php if ($partyPhone !== ''): ?>
                <div class="pmeta"><?= thermalBiLabel('phone') ?>: <span dir="ltr"><?= htmlspecialchars($partyPhone) ?></span></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="plbl"><?= thermalBiLabel('account') ?></span>
                <span class="pval"><?= htmlspecialchars((string)($payment['account_name'] ?? '—')) ?></span>
                <?php if (!empty($payment['cheque_no'])): ?>
                <div class="pmeta"><?= thermalBiLabel('cheque_no') ?>: <?= htmlspecialchars((string)$payment['cheque_no']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="amount-hero">
        <div class="amount-label"><?= thermalBiLabel($amountLabelKey) ?></div>
        <div class="amount-value" dir="ltr"><?= $moneyFmt($payment['amount'] ?? 0) ?></div>
    </div>

    <div class="words-box">
        <div class="wlbl"><?= thermalBiLabel('amount_in_words') ?></div>
        <div class="wen"><?= htmlspecialchars($amountWords['en']) ?></div>
        <div class="war ar" dir="rtl" lang="ar"><?= htmlspecialchars($amountWords['ar']) ?></div>
    </div>

    <?php if (!empty($payment['notes'])): ?>
    <div class="notes-box">
        <strong><?= thermalBiLabel('notes') ?></strong>
        <?= nl2br(htmlspecialchars((string)$payment['notes'])) ?>
    </div>
    <?php endif; ?>

    <table class="ledger-table">
        <tr>
            <td class="lbl"><?= thermalBiLabel('previous_balance') ?></td>
            <td class="val"><?= $moneyFmt($previousBalance) ?></td>
        </tr>
        <tr class="t-payment">
            <td class="lbl"><?= thermalBiLabel($paymentMoveKey) ?></td>
            <td class="val"><?= $isIn ? '−' : '+' ?> <?= $moneyFmt($payment['amount'] ?? 0) ?></td>
        </tr>
        <tr class="t-current">
            <td class="lbl"><?= thermalBiLabel('current_balance') ?></td>
            <td class="val"><?= $moneyFmt($currentBalance) ?></td>
        </tr>
    </table>

    <div class="legal-line"><?= thermalBiLabel('vat_not_applicable') ?></div>

    <div class="inv-footer">
        <?php if ($useDefaultThankYou): ?>
        <p><?= thermalBiLabel('thank_you') ?></p>
        <?php else: ?>
        <p><?= htmlspecialchars($invoiceFooter) ?></p>
        <p style="margin-top:2px;"><span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($GLOBALS['thermalLabels']['thank_you']['ar'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php endif; ?>
        <p style="margin-top:3px;"><?= thermalBiLabel('printed') ?> <span dir="ltr"><?= date('d M Y, h:i A') ?></span></p>
        <p><?= thermalBiLabel('computer_generated_receipt') ?></p>
    </div>

<?php else: ?>
    <div class="receipt-header">
        <div class="company-name"><?= htmlspecialchars((string)($settings['company_name'] ?? APP_NAME)) ?></div>
        <div class="company-name-ar ar" dir="rtl" lang="ar"><?= htmlspecialchars($companyNameAr) ?></div>
        <div class="company-info">
            <?= nl2br(htmlspecialchars((string)($settings['company_address'] ?? ''))) ?><br>
            <?php if ($companyAddressAr !== ''): ?>
            <span class="ar" dir="rtl" lang="ar"><?= nl2br(htmlspecialchars($companyAddressAr)) ?></span><br>
            <?php endif; ?>
            <?= htmlspecialchars((string)($settings['company_phone'] ?? '')) ?>
            <?php if ($companyCr !== ''): ?>
            <br><?= thermalBiLabel('cr_no') ?>: <?= htmlspecialchars($companyCr) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="receipt-title"><?= thermalBiLabel($docTitleKey) ?></div>

    <div class="amount-box">
        <div class="amount-label"><?= thermalBiLabel($amountLabelKey) ?></div>
        <div class="amount-value"><?= $moneyFmt($payment['amount'] ?? 0) ?></div>
    </div>
    <div class="words-thermal">
        <?= htmlspecialchars($amountWords['en']) ?><br>
        <span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($amountWords['ar']) ?></span>
    </div>

    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel($docNoKey) ?></span><span class="val"><?= htmlspecialchars($payNo) ?></span></div>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('date') ?></span><span class="val" dir="ltr"><?= htmlspecialchars($payDate) ?></span></div>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel($partyRoleKey) ?></span><span class="val"><?= htmlspecialchars((string)($payment['party_name'] ?? '—')) ?></span></div>
    <?php if ($partyPhone !== ''): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('phone') ?></span><span class="val"><?= htmlspecialchars($partyPhone) ?></span></div>
    <?php endif; ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('account') ?></span><span class="val"><?= htmlspecialchars((string)($payment['account_name'] ?? '—')) ?></span></div>
    <?php if (!empty($payment['cheque_no'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('cheque_no') ?></span><span class="val"><?= htmlspecialchars((string)$payment['cheque_no']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($payment['notes'])): ?>
    <div class="receipt-row"><span class="lbl"><?= thermalBiLabel('notes') ?></span><span class="val"><?= htmlspecialchars((string)$payment['notes']) ?></span></div>
    <?php endif; ?>

    <div style="margin-top:14px;padding-top:12px;border-top:2px dashed #e5e7eb;">
        <div class="receipt-row">
            <span class="lbl"><?= thermalBiLabel('previous_balance') ?></span>
            <span class="val"><?= APP_CURRENCY ?> <?= number_format($previousBalance, DECIMAL_PLACES) ?></span>
        </div>
        <div class="receipt-row">
            <span class="lbl"><?= thermalBiLabel($paymentMoveKey) ?></span>
            <span class="val"><?= $isIn ? '-' : '+' ?> <?= APP_CURRENCY ?> <?= number_format((float)$payment['amount'], DECIMAL_PLACES) ?></span>
        </div>
        <div class="receipt-row current-balance-row">
            <span class="lbl"><strong><?= thermalBiLabel('current_balance') ?></strong></span>
            <span class="val"><strong><?= APP_CURRENCY ?> <?= number_format($currentBalance, DECIMAL_PLACES) ?></strong></span>
        </div>
    </div>

    <div class="footer">
        <?php if ($useDefaultThankYou): ?>
        <p><?= thermalBiLabel('thank_you') ?></p>
        <?php else: ?>
        <p><?= htmlspecialchars($invoiceFooter) ?></p>
        <p style="margin-top:2px;"><span class="ar" dir="rtl" lang="ar"><?= htmlspecialchars($GLOBALS['thermalLabels']['thank_you']['ar'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php endif; ?>
        <p style="margin-top:4px;"><?= thermalBiLabel('printed') ?> <span dir="ltr"><?= date('d M Y, h:i A') ?></span></p>
        <p class="legal-thermal"><?= thermalBiLabel('vat_not_applicable') ?></p>
        <p style="margin-top:8px;font-size:9px;color:#666;"><?= thermalBiLabel('computer_generated_receipt') ?></p>
    </div>
<?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
(function () {
    var paymentId = <?= (int)$payment['id'] ?>;
    var isThermal = <?= $thermal ? 'true' : 'false' ?>;
    var paymentNo = <?= json_encode((string)($payment['payment_no'] ?? 'receipt')) ?>;
    var paymentsListUrl = <?= json_encode((string)($paymentsListBase ?? '?page=payments')) ?>;

    document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });

    var btnThermal = document.getElementById('btnThermal');
    if (btnThermal) {
        btnThermal.addEventListener('click', function () {
            window.location = '?page=payments&action=thermalPrint&id=' + paymentId + '&thermal=1&autoprint=1';
        });
    }

    var btnA5 = document.getElementById('btnA5');
    if (btnA5) {
        btnA5.addEventListener('click', function () {
            window.location = '?page=payments&action=print&id=' + paymentId;
        });
    }

    var btnEdit = document.getElementById('btnEdit');
    if (btnEdit) {
        btnEdit.addEventListener('click', function () {
            window.location = '?page=payments&action=edit&id=' + paymentId;
        });
    }

    document.getElementById('btnClose').addEventListener('click', function () {
        window.location = paymentsListUrl;
    });

    function exportPDF() {
        if (isThermal) {
            window.location = '?page=payments&action=print&id=' + paymentId + '&autopdf=1';
            return;
        }
        var btn = document.getElementById('btnPdf');
        btn.disabled = true;
        btn.innerHTML = '<b>Generating...</b>';
        html2pdf().set({
            margin: [5, 7, 5, 7],
            filename: paymentNo + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
        }).from(document.querySelector('.wrap')).save().then(function () {
            btn.disabled = false;
            btn.innerHTML = '<b>⤓ PDF</b>';
        });
    }

    document.getElementById('btnPdf').addEventListener('click', exportPDF);

    window.addEventListener('load', function () {
        var params = new URLSearchParams(window.location.search);
        var nextAction = params.get('next') || '';
        var nextParty = params.get('party_id') || '';
        var goNextDone = false;
        function goNextForm() {
            if (goNextDone) return;
            if (nextAction !== 'receive' && nextAction !== 'pay') return;
            goNextDone = true;
            var nextUrl = '?page=payments&action=' + encodeURIComponent(nextAction);
            if (nextParty) nextUrl += '&party_id=' + encodeURIComponent(nextParty);
            window.location = nextUrl;
        }
        if (params.get('autoprint') === '1') {
            setTimeout(function () { window.print(); }, 400);
            if (nextAction === 'receive' || nextAction === 'pay') {
                window.addEventListener('afterprint', goNextForm);
                setTimeout(goNextForm, 6000);
            }
        }
        if (params.get('autopdf') === '1') setTimeout(exportPDF, 600);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        e.preventDefault();
        window.location.href = '?page=dashboard';
    });
})();
</script>
</body>
</html>
