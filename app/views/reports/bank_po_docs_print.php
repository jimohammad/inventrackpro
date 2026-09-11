<?php
$companyName = (string) ($companyName ?? PDF_COMPANY_NAME);
$periodLabel = (string) ($periodLabel ?? '');
$packFilename = (string) ($packFilename ?? 'PO_docs_bank.pdf');
$fromDate = (string) ($fromDate ?? '');
$toDate = (string) ($toDate ?? '');
$dateField = (string) ($dateField ?? 'uploaded');
$packJson = json_encode($packDocs ?? [], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$coverJson = json_encode($cover ?? [], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$backQs = 'from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&date_field=' . urlencode($dateField);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PO invoices &amp; TT — <?= htmlspecialchars($periodLabel) ?></title>
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
    <span class="title">PO invoices &amp; TT · <?= htmlspecialchars($periodLabel) ?></span>
    <button type="button" class="btn btn-pdf" id="btnDownload" disabled>Download PDF</button>
    <button type="button" class="btn btn-print" id="btnPrint" disabled>Print</button>
    <a class="btn btn-back" href="?page=reports&action=bankPoDocs&<?= htmlspecialchars($backQs) ?>">Back</a>
</div>

<div class="wrap">
    <div class="card">
        <div class="hero">
            <h1>One PDF for the bank</h1>
            <p>One cover with the PO list, then the original invoices and TT copies. Password-protected files are skipped. Scan the QR on the cover to verify this pack.</p>
        </div>
        <div class="meta">
            <div><span class="k">Company</span><span class="v"><?= htmlspecialchars($companyName) ?></span></div>
            <div><span class="k">Period</span><span class="v"><?= htmlspecialchars($periodLabel) ?></span></div>
            <div><span class="k">POs</span><span class="v"><?= (int) ($summary['po_count'] ?? 0) ?></span></div>
            <div><span class="k">Files</span><span class="v"><?= (int) ($summary['file_count'] ?? 0) ?>
                (<?= (int) ($summary['invoice_count'] ?? 0) ?> invoices · <?= (int) ($summary['tt_count'] ?? 0) ?> TT)</span></div>
            <?php if (!empty($cover['verify_url'])): ?>
            <div><span class="k">Verify</span><span class="v" style="word-break:break-all;font-weight:600;"><?= htmlspecialchars((string) $cover['verify_url']) ?></span></div>
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
        <p class="hint">If download is blocked, use Print → Save as PDF. Arabic names on the cover may show as ?; the attached files are unchanged.</p>
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
