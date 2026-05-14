<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bulk invoices — <?= htmlspecialchars(($filters['from_date'] ?? '') . ' → ' . ($filters['to_date'] ?? '')) ?></title>
<?php
$backQ = ['page' => 'sales'];
if (!empty($filters['voided_only'])) {
    $backQ['view'] = 'voided';
}
if (!empty($filters['include_voided'])) {
    $backQ['include_voided'] = '1';
}
if (($filters['search'] ?? '') !== '') {
    $backQ['search'] = (string) $filters['search'];
}
if (($filters['status'] ?? '') !== '') {
    $backQ['status'] = (string) $filters['status'];
}
if (!empty($filters['party_id'])) {
    $backQ['party_id'] = (string) (int) $filters['party_id'];
}
if (($filters['from_date'] ?? '') !== '') {
    $backQ['from_date'] = (string) $filters['from_date'];
}
if (($filters['to_date'] ?? '') !== '') {
    $backQ['to_date'] = (string) $filters['to_date'];
}
$backHref = '?' . http_build_query($backQ);
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9px; color: #1a1a1a; background: #e5e7eb; }
.invoice-wrap { max-width: 148mm; width: 100%; margin: 0 auto; padding: 8mm 10mm; background: #fff; }

.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; border-bottom: 2px solid #1e3a5f; padding-bottom: 7px; }
.company-name { font-size: 12px; font-weight: 800; color: #1e3a5f; }
.company-info { font-size: 8px; color: #555; margin-top: 3px; line-height: 1.5; }
.inv-title { text-align: right; }
.inv-title h1 { font-size: 18px; font-weight: 800; color: #1e3a5f; letter-spacing: 1px; }
.inv-title p { font-size: 8px; color: #444; margin-top: 3px; line-height: 1.7; }

.customer-row { background:#f8f9ff; border:1px solid #e0e7ff; border-radius:5px; padding:5px 8px; margin-bottom:8px; display:flex; align-items:center; gap:10px; font-size:9px; }
.customer-row .clbl { font-weight:700; text-transform:uppercase; color:#6366f1; letter-spacing:0.5px; white-space:nowrap; }
.customer-row .cname { font-weight:700; color:#1a1a1a; }
.customer-row .cphone { color:#555; }

table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
thead th { background:#1e3a5f; color:#fff; padding:5px 6px; font-size:8px; text-transform:uppercase; letter-spacing:0.3px; white-space:nowrap; }
tbody td { padding:4px 6px; border-bottom:1px solid #f0f0f0; font-size:8.5px; vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:nth-child(even) { background:#f8f9ff; }
.item-name { font-weight:600; }
.item-sku  { font-size:7.5px; color:#888; }

.totals-section { display:flex; justify-content:flex-end; margin-bottom:8px; }
.totals-box { width:55mm; }
.total-row { display:flex; justify-content:space-between; padding:3px 0; font-size:8.5px; border-bottom:1px solid #f0f0f0; }
.total-row:last-child { border-bottom:none; }
.total-row .lbl { color:#555; }
.t-grand { font-size:10px; font-weight:800; color:#1e3a5f; border-top:1.5px solid #1e3a5f !important; padding-top:5px !important; margin-top:2px; }
.t-prev { color:#92400e !important; border-top:1px dashed #fde68a !important; padding-top:4px !important; margin-top:2px; }
.t-outstanding { font-size:10px; font-weight:800; color:#6366f1; border-top:1.5px solid #6366f1 !important; padding-top:5px !important; margin-top:2px; }

.notes-box { margin-bottom:7px; padding:5px 7px; background:#fffbeb; border:1px solid #fde68a; border-radius:4px; font-size:8px; color:#555; }
.notes-box strong { display:block; color:#888; font-size:7.5px; text-transform:uppercase; margin-bottom:2px; }
.inv-footer { border-top:1px solid #e5e7eb; padding-top:6px; text-align:center; color:#888; font-size:8px; }

.bulk-toolbar {
    position: sticky; top: 0; z-index: 50;
    background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    padding: 12px 16px; margin-bottom: 16px;
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.06);
}
.bulk-toolbar .summary { font-size: 13px; color: #334155; flex: 1; min-width: 200px; }
.bulk-toolbar .warn { color: #b45309; font-weight: 600; }
.bulk-toolbar .err { color: #b91c1c; font-weight: 600; }
.bulk-toolbar button, .bulk-toolbar a.btn-link {
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
    border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
}
#bulkBtnPrint { background: #6366f1; color: #fff; }
#bulkBtnPdf { background: #dc2626; color: #fff; }
#bulkBtnClose { background: #e2e8f0; color: #334155; }

.invoice-page {
    margin-bottom: 24px;
    page-break-after: always;
    break-after: page;
}
.invoice-page:last-child {
    page-break-after: auto;
    break-after: auto;
    margin-bottom: 0;
}

@media print {
    body { background: #fff; padding: 0; }
    .bulk-toolbar { display: none !important; }
    .invoice-wrap { box-shadow: none; max-width: 100%; padding: 0; }
    @page { size: A5 portrait; margin: 8mm 10mm; }
}
</style>
</head>
<body>

<div class="bulk-toolbar no-print">
    <div class="summary">
        <?php if (!empty($missingDates)): ?>
            <span class="err">Set both <strong>From</strong> and <strong>To</strong> dates on the Sales page, then open this tool again.</span>
        <?php elseif (empty($bulkSales)): ?>
            <span class="warn">No invoices match the current filters for this date range.</span>
        <?php else: ?>
            <strong><?= count($bulkSales) ?></strong> invoice<?= count($bulkSales) === 1 ? '' : 's' ?>
            · <?= htmlspecialchars((string) $filters['from_date']) ?> → <?= htmlspecialchars((string) $filters['to_date']) ?>
            <?php if (!empty($truncated)): ?>
                <span class="warn"> (first 200 only — narrow the range for the rest)</span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php if (empty($missingDates) && !empty($bulkSales)): ?>
        <button type="button" id="bulkBtnPrint">Print</button>
        <button type="button" id="bulkBtnPdf">Download PDF</button>
        <?php endif; ?>
        <a class="btn-link" id="bulkBtnClose" href="<?= htmlspecialchars($backHref) ?>">← Back to sales</a>
    </div>
</div>

<div id="bulkPdfRoot"
     data-from="<?= htmlspecialchars((string) ($filters['from_date'] ?? '')) ?>"
     data-to="<?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?>">
<?php if (empty($missingDates) && !empty($bulkSales)): ?>
    <?php foreach ($bulkSales as $sale): ?>
    <div class="invoice-page">
        <div class="invoice-wrap">
            <?php include __DIR__ . '/_print_invoice_a5_inner.php'; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
(function () {
    var root = document.getElementById('bulkPdfRoot');
    var printBtn = document.getElementById('bulkBtnPrint');
    var pdfBtn = document.getElementById('bulkBtnPdf');

    if (printBtn) {
        printBtn.addEventListener('click', function () { window.print(); });
    }

    if (pdfBtn && root && root.querySelector('.invoice-wrap')) {
        pdfBtn.addEventListener('click', function () {
            var pages = Array.prototype.slice.call(root.querySelectorAll('.invoice-page'));
            if (!pages.length) return;

            var h2p = window.html2pdf;
            if (typeof h2p !== 'function') {
                alert('PDF library failed to load. Refresh the page, or use Print and choose Save as PDF.');
                return;
            }

            pdfBtn.disabled = true;
            var prev = pdfBtn.textContent;
            pdfBtn.textContent = 'Generating…';

            var from = (root.getAttribute('data-from') || 'from').replace(/[^\d-]/g, '');
            var to = (root.getAttribute('data-to') || 'to').replace(/[^\d-]/g, '');
            var fname = 'sales-invoices-' + from + '_to_' + to + '.pdf';

            /* One html2canvas pass over #bulkPdfRoot builds a single canvas tall enough to
               blow past browser canvas limits (html2pdf.js known issue: max canvas size).
               Render each .invoice-page into its own PDF page and merge via jsPDF.addPage. */
            var opt = {
                margin: [8, 10, 8, 10],
                filename: fname,
                image: { type: 'jpeg', quality: 0.92 },
                html2canvas: {
                    scale: 1.35,
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: 'tr' }
            };

            var doc = h2p().set(opt).from(pages[0]).toContainer().toCanvas().toPdf();
            var j;
            for (j = 1; j < pages.length; j++) {
                (function (el) {
                    doc = doc.get('pdf').then(function (pdf) {
                        pdf.addPage();
                    }).from(el).set(opt).toContainer().toCanvas().toPdf();
                })(pages[j]);
            }

            doc.save().then(function () {
                pdfBtn.disabled = false;
                pdfBtn.textContent = prev;
            }).catch(function (err) {
                console.error('Bulk PDF generation failed', err);
                if (err && err.stack) {
                    console.error(err.stack);
                }
                pdfBtn.disabled = false;
                pdfBtn.textContent = prev;
                var detail = (err && err.message) ? err.message : String(err);
                if (window.confirm(
                    'Could not build the combined PDF in the browser.\n\n' + detail +
                    '\n\nOpen the print dialog? Choose “Save as PDF” or “Microsoft Print to PDF” as the printer.'
                )) {
                    window.print();
                }
            });
        });
    }
})();
</script>
</body>
</html>
