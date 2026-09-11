<?php
$companyName  = (string) ($companyName ?? PDF_COMPANY_NAME);
$poNo         = (string) ($po['po_no'] ?? '');
$supplierName = (string) ($po['supplier_name'] ?? '');
$poDateLabel  = !empty($po['date']) ? date('d M Y', strtotime((string) $po['date'])) : '';
$poTotalLabel = number_format((float) ($poTotalKwd ?? 0), DECIMAL_PLACES);
$paidLabel    = number_format((float) ($paidKwd ?? 0), DECIMAL_PLACES);
$packFilename = (string) ($packFilename ?? 'PO_bank_docs.pdf');
$backId       = (int) ($po['id'] ?? 0);
$companyPhone = (string) ($companyPhone ?? (defined('PDF_COMPANY_PHONE') ? PDF_COMPANY_PHONE : ''));
$companyAddress = (string) ($companyAddress ?? '');
$verifyCover  = is_array($verifyCover ?? null) ? $verifyCover : ['verify_url' => '', 'qr_png' => null];
$verifyUrl    = (string) ($verifyCover['verify_url'] ?? '');
$invCount = 0;
$ttCount  = 0;
foreach (($packDocs ?? []) as $_d) {
    if ((($_d['type'] ?? '') === 'money_transfer')) {
        $ttCount++;
    } else {
        $invCount++;
    }
}
$packJson     = json_encode($packDocs ?? [], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$coverJson    = json_encode([
    'style'         => 'po',
    'company'       => $companyName,
    'phone'         => $companyPhone,
    'address'       => $companyAddress,
    'heading'       => 'Bank document pack',
    'po_no'         => $poNo,
    'supplier'      => $supplierName,
    'po_date'       => $poDateLabel,
    'total'         => $poTotalLabel . ' KWD',
    'paid'          => $paidLabel . ' KWD',
    'invoice_count' => $invCount,
    'tt_count'      => $ttCount,
    'verify_url'    => $verifyUrl,
    'qr_png'        => $verifyCover['qr_png'] ?? null,
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($poNo) ?> — bank documents</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; }
.toolbar {
    position: sticky; top: 0; z-index: 10;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 12px 20px; background: #0f172a; color: #e2e8f0;
}
.toolbar .title { font-weight: 700; font-size: 0.95rem; margin-right: auto; }
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px; border: none;
    font-size: 0.82rem; font-weight: 700; cursor: pointer; text-decoration: none;
}
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-pdf { background: #dc2626; color: #fff; }
.btn-print { background: #1d4ed8; color: #fff; }
.btn-back { background: #334155; color: #e2e8f0; }
.wrap { max-width: 720px; margin: 28px auto; padding: 0 16px 48px; }
.card {
    background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06); overflow: hidden;
}
.hero { padding: 22px 24px; background: linear-gradient(135deg, #eff6ff, #f8fafc); border-bottom: 1px solid #e2e8f0; }
.hero h1 { font-size: 1.15rem; font-weight: 800; color: #1e3a5f; }
.hero p { margin-top: 6px; font-size: 0.85rem; color: #64748b; }
.meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; padding: 18px 24px; }
.meta .k { display: block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: #94a3b8; }
.meta .v { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.status {
    margin: 0 24px 18px; padding: 12px 14px; border-radius: 10px;
    background: #eff6ff; color: #1d4ed8; font-size: 0.85rem; font-weight: 600;
}
.status.ok { background: #ecfdf5; color: #047857; }
.status.err { background: #fef2f2; color: #b91c1c; }
.status.warn { background: #fffbeb; color: #92400e; }
.files { padding: 0 24px 22px; }
.files h2 { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 10px; }
.file {
    display: flex; justify-content: space-between; gap: 12px; align-items: center;
    padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; font-size: 0.82rem;
}
.file .name { font-weight: 600; word-break: break-all; }
.file .type { font-size: 0.72rem; color: #64748b; }
.badge { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #475569; white-space: nowrap; }
.badge.skip { background: #fef2f2; color: #b91c1c; }
.badge.ok { background: #ecfdf5; color: #047857; }
.hint { padding: 0 24px 22px; font-size: 0.78rem; color: #94a3b8; }
@media (max-width: 640px) { .meta { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="toolbar">
    <span class="title"><?= htmlspecialchars($poNo) ?> · bank pack</span>
    <button type="button" class="btn btn-pdf" id="btnDownload" disabled>Download PDF</button>
    <button type="button" class="btn btn-print" id="btnPrint" disabled>Print</button>
    <a class="btn btn-back" href="?page=purchaseorders&action=show&id=<?= $backId ?>">Back to PO</a>
</div>

<div class="wrap">
    <div class="card">
        <div class="hero">
            <h1>One PDF for the bank</h1>
            <p>Cover page, then supplier invoices, then TT copies. Password-protected files are skipped. Scan the QR on the cover to verify this pack.</p>
        </div>
        <div class="meta">
            <div><span class="k">Company</span><span class="v"><?= htmlspecialchars($companyName) ?></span></div>
            <div><span class="k">PO date</span><span class="v"><?= htmlspecialchars($poDateLabel) ?></span></div>
            <div><span class="k">Supplier</span><span class="v"><?= htmlspecialchars($supplierName) ?></span></div>
            <div><span class="k">Total / paid</span><span class="v"><?= htmlspecialchars($poTotalLabel) ?> / <?= htmlspecialchars($paidLabel) ?> KWD</span></div>
            <?php if ($verifyUrl !== ''): ?>
            <div><span class="k">Verify</span><span class="v" style="word-break:break-all;font-weight:600;"><?= htmlspecialchars($verifyUrl) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="status" id="statusBox">Building PDF…</div>
        <div class="files">
            <h2>Files in this pack</h2>
            <?php foreach (($packDocs ?? []) as $i => $doc): ?>
            <div class="file" data-doc-index="<?= (int) $i ?>">
                <div>
                    <div class="name"><?= htmlspecialchars((string) ($doc['name'] ?? '')) ?></div>
                    <div class="type"><?= htmlspecialchars((string) ($doc['label'] ?? '')) ?></div>
                </div>
                <span class="badge" data-badge>Pending</span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="hint">If download is blocked, use Print → Save as PDF. Arabic names on the cover may show as ? because the PDF font is English-only; the attached files are unchanged.</p>
    </div>
</div>

<script>
window.PO_DOCS_PACK = {
    pack: <?= $packJson ?: '[]' ?>,
    cover: <?= $coverJson ?: '{}' ?>,
    filename: <?= json_encode($packFilename, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="assets/js/po-docs-pack.js?v=<?= htmlspecialchars(ASSETS_VER) ?>"></script>
</body>
</html>
