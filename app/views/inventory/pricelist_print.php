<?php
/**
 * Customer wholesale pricelist — one A4 page, same look as public /pricelist.
 */
$companyName  = (string) ($companyName ?? PDF_COMPANY_NAME);
$companyPhone = trim((string) ($companyPhone ?? ''));
$companyEmail = trim((string) ($companyEmail ?? ''));
$printedAt    = (string) ($printedAt ?? date('d M Y, h:i A'));
$pdfFilename  = (string) ($pdfFilename ?? 'Pricelist.pdf');
$itemCount    = (int) ($itemCount ?? 0);
$grouped      = is_array($grouped ?? null) ? $grouped : [];
$catCounts    = is_array($catCounts ?? null) ? $catCounts : [];
$logoSrc      = $logoSrc ?? null;
$newArrivalCutoff = (string) ($newArrivalCutoff ?? date('Y-m-d H:i:s', strtotime('-7 days')));
$money = static function (float $v): string {
    return APP_CURRENCY . ' ' . number_format($v, DECIMAL_PLACES);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Price List — <?= htmlspecialchars($companyName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #c8e6c9; }
        body {
            font-family: 'DM Sans', 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            line-height: 1.3;
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
            background: #1b5e20;
            color: #fff;
            font-size: 12px;
        }
        .toolbar button, .toolbar a {
            border: none;
            border-radius: 6px;
            padding: 7px 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            font-size: 12px;
            font-family: inherit;
        }
        .toolbar .btn-pdf { background: #dc2626; }
        .toolbar .btn-share { background: #16a34a; }
        .toolbar .btn-print { background: #2563eb; }
        .toolbar .btn-back { background: #475569; }
        .toolbar .hint { opacity: 0.88; font-size: 11px; }
        .toolbar button:disabled { opacity: 0.65; cursor: wait; }

        .stage { padding: 14px 10px 28px; }

        .sheet {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: #c8e6c9;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.18);
        }

        .pl-header {
            flex: 0 0 auto;
            background: #fff;
            border-bottom: 1px solid #a5d6a7;
            padding: 8mm 9mm 6mm;
        }
        .pl-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .pl-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .pl-brand-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
            flex-shrink: 0;
            display: block;
        }
        .pl-brand-name {
            font-size: 12.5pt;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.15;
        }
        .pl-brand-sub {
            font-size: 7.5pt;
            color: #94a3b8;
            font-weight: 500;
            margin-top: 2px;
        }
        .pl-header-right {
            text-align: right;
            flex-shrink: 0;
        }
        .pl-contact {
            font-size: 8.5pt;
            color: #2e7d32;
            font-weight: 700;
        }
        .pl-date {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        .pl-cats {
            flex: 0 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 4mm 9mm 2mm;
        }
        .pl-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 7.5pt;
            font-weight: 600;
            border: 1.5px solid #a5d6a7;
            background: #fff;
            color: #64748b;
        }
        .pl-cat-pill.is-all {
            background: #2e7d32;
            color: #fff;
            border-color: #2e7d32;
        }
        .pl-cat-count {
            background: rgba(0,0,0,0.08);
            padding: 0 6px;
            border-radius: 10px;
            font-size: 6.5pt;
            font-weight: 700;
        }
        .pl-cat-pill.is-all .pl-cat-count { background: rgba(255,255,255,0.25); }

        .pl-main {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            padding: 2mm 9mm 0;
            font-size: 10px;
        }
        .pl-section { margin-bottom: 3.2mm; }
        .pl-section:last-child { margin-bottom: 0; }
        /* Dark text on mint — html2canvas often drops white-on-green labels */
        .pl-section-head {
            display: inline-block;
            margin-bottom: 2mm;
            padding: 4px 14px 5px;
            background: #dcfce7;
            border: 1.5px solid #2e7d32;
            border-radius: 20px;
            color: #14532d;
            font-size: 10pt;
            font-weight: 800;
            line-height: 1.3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            -webkit-text-fill-color: #14532d;
        }
        .pl-section-name {
            font-size: 10pt;
            font-weight: 800;
            color: #14532d;
            -webkit-text-fill-color: #14532d;
        }
        .pl-section-badge {
            display: inline-block;
            margin-left: 6px;
            font-size: 8pt;
            font-weight: 700;
            background: #2e7d32;
            color: #f7fee7;
            -webkit-text-fill-color: #f7fee7;
            padding: 1px 8px;
            border-radius: 10px;
            vertical-align: middle;
        }

        .pl-item {
            display: grid;
            grid-template-columns: 1fr 48px 1fr;
            align-items: center;
            column-gap: 6px;
            padding: 0.55em 0.85em;
            background: #fff;
            border: 1px solid #e8f5e9;
            border-radius: 8px;
            margin-bottom: 1.4mm;
        }
        .pl-item-info { min-width: 0; }
        .pl-item-name-row {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }
        .pl-item-name {
            font-size: 1.05em;
            font-weight: 700;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .pl-new-badge {
            flex-shrink: 0;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 0.62em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #4f46e5;
            color: #fff;
            -webkit-text-fill-color: #fff;
            line-height: 1.4;
        }
        .pl-item-meta {
            font-size: 0.78em;
            color: #94a3b8;
            margin-top: 1px;
        }
        .pl-item-nfc {
            width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            justify-self: center;
        }
        .pl-nfc-badge {
            flex-shrink: 0;
            display: inline-block;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 0.68em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #0d9488;
            color: #fff;
            -webkit-text-fill-color: #fff;
            line-height: 1.4;
        }
        .pl-item-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            min-width: 0;
        }
        .pl-item-stock { text-align: center; }
        .pl-stock-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 700;
            white-space: nowrap;
        }
        .pl-stock-badge.in { background: #dcfce7; color: #15803d; }
        .pl-stock-badge.low { background: #fef9c3; color: #a16207; }
        .pl-item-price {
            font-size: 1.15em;
            font-weight: 800;
            color: #1e293b;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .pl-footer {
            flex: 0 0 auto;
            text-align: center;
            padding: 3.5mm 9mm 5mm;
            font-size: 7pt;
            color: #64748b;
            border-top: 1px solid #a5d6a7;
            background: #fff;
        }

        .empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 11pt;
            padding: 20mm;
            text-align: center;
        }

        @page { size: A4 portrait; margin: 0; }
        @media print {
            html, body { background: #c8e6c9; width: 210mm; height: 297mm; }
            .toolbar, .stage { padding: 0; }
            .toolbar { display: none !important; }
            .sheet { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" class="btn-pdf" id="btnPdf">Download PDF</button>
    <button type="button" class="btn-share" id="btnShare" hidden>Share</button>
    <button type="button" class="btn-print" id="btnPrint">Print</button>
    <a class="btn-back" href="?page=stock">Back to Stock List</a>
    <span class="hint">One A4 page · same look as the online pricelist</span>
</div>

<div class="stage">
    <div class="sheet" id="sheet">
        <header class="pl-header">
            <div class="pl-header-inner">
                <div class="pl-brand">
                    <?php if ($logoSrc): ?>
                    <img class="pl-brand-icon" src="<?= htmlspecialchars($logoSrc) ?>" alt="">
                    <?php endif; ?>
                    <div>
                        <div class="pl-brand-name"><?= htmlspecialchars($companyName) ?></div>
                        <div class="pl-brand-sub">Product Catalog &amp; Price List</div>
                    </div>
                </div>
                <div class="pl-header-right">
                    <?php if ($companyPhone !== ''): ?>
                    <div class="pl-contact"><?= htmlspecialchars($companyPhone) ?></div>
                    <?php endif; ?>
                    <div class="pl-date"><?= htmlspecialchars($printedAt) ?></div>
                </div>
            </div>
        </header>

        <?php if ($itemCount === 0): ?>
        <div class="empty">No in-stock items to list for this branch.</div>
        <?php else: ?>
        <div class="pl-cats">
            <span class="pl-cat-pill is-all">All <span class="pl-cat-count"><?= (int) $itemCount ?></span></span>
            <?php foreach ($catCounts as $cat => $count): ?>
            <span class="pl-cat-pill"><?= htmlspecialchars((string) $cat) ?> <span class="pl-cat-count"><?= (int) $count ?></span></span>
            <?php endforeach; ?>
        </div>

        <div class="pl-main" id="catalog">
            <?php foreach ($grouped as $cat => $catItems): ?>
            <div class="pl-section">
                <div class="pl-section-head">
                    <span class="pl-section-name"><?= htmlspecialchars((string) $cat) ?></span>
                    <span class="pl-section-badge"><?= count($catItems) ?></span>
                </div>
                <?php foreach ($catItems as $item):
                    $stock = (int) ($item['stock'] ?? 0);
                    $isNewArrival = !empty($item['created_at']) && (string) $item['created_at'] >= $newArrivalCutoff;
                    $brand = trim((string) ($item['brand'] ?? ''));
                ?>
                <div class="pl-item">
                    <div class="pl-item-info">
                        <div class="pl-item-name-row">
                            <div class="pl-item-name" title="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></div>
                            <?php if ($isNewArrival): ?>
                            <span class="pl-new-badge">New Arrival</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($brand !== ''): ?>
                        <div class="pl-item-meta"><?= htmlspecialchars($brand) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="pl-item-nfc">
                        <?php if (!empty($item['has_nfc'])): ?>
                        <span class="pl-nfc-badge" title="NFC available">NFC</span>
                        <?php endif; ?>
                    </div>
                    <div class="pl-item-right">
                    <div class="pl-item-stock">
                        <?php if ($stock > 10): ?>
                        <span class="pl-stock-badge in">In Stock</span>
                        <?php else: ?>
                        <span class="pl-stock-badge low">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="pl-item-price"><?= $money((float) ($item['sale_price'] ?? 0)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <footer class="pl-footer">
            <?= htmlspecialchars($companyName) ?> &mdash; Prices may change without notice &mdash; Updated <?= htmlspecialchars($printedAt) ?>
            <?php if ($companyEmail !== ''): ?>
            &mdash; <?= htmlspecialchars($companyEmail) ?>
            <?php endif; ?>
        </footer>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
(function () {
    var filename = <?= json_encode($pdfFilename) ?>;
    var itemCount = <?= (int) $itemCount ?>;
    var pdfBlob = null;
    var busy = false;
    var btnPdf = document.getElementById('btnPdf');
    var btnShare = document.getElementById('btnShare');
    var btnPrint = document.getElementById('btnPrint');
    var pdfCdns = [
        'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js',
        'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js'
    ];

    function setBusy(on, label) {
        busy = on;
        if (btnPdf) {
            btnPdf.disabled = on;
            btnPdf.textContent = on ? (label || 'Generating…') : 'Download PDF';
        }
        if (btnShare) btnShare.disabled = on;
    }

    function fitCatalog() {
        var catalog = document.getElementById('catalog');
        if (!catalog) return;
        catalog.style.fontSize = '';
        var sizePx = parseFloat(window.getComputedStyle(catalog).fontSize) || 10;
        var guard = 0;
        while (catalog.scrollHeight > catalog.clientHeight + 1 && sizePx > 7 && guard < 48) {
            sizePx -= 0.2;
            catalog.style.fontSize = sizePx + 'px';
            guard++;
        }
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('load failed')); };
            document.head.appendChild(s);
        });
    }

    async function ensureHtml2pdf() {
        if (typeof html2pdf === 'function') return;
        var i;
        for (i = 0; i < pdfCdns.length; i++) {
            try {
                await loadScript(pdfCdns[i]);
                if (typeof html2pdf === 'function') return;
            } catch (e) { /* try next CDN */ }
        }
        throw new Error('PDF library failed to load. Check the internet connection and try Download PDF again.');
    }

    function pageCount(pdf) {
        if (pdf && typeof pdf.getNumberOfPages === 'function') return pdf.getNumberOfPages();
        if (pdf && pdf.internal && typeof pdf.internal.getNumberOfPages === 'function') {
            return pdf.internal.getNumberOfPages();
        }
        return 1;
    }

    async function buildPdf() {
        if (itemCount < 1) throw new Error('No items to export');
        await ensureHtml2pdf();
        if (document.fonts && document.fonts.ready) {
            try { await document.fonts.ready; } catch (e) { /* continue */ }
        }
        fitCatalog();
        var el = document.getElementById('sheet');
        var worker = html2pdf().set({
            margin: 0,
            filename: filename,
            image: { type: 'jpeg', quality: 0.95 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                backgroundColor: '#c8e6c9',
                logging: false,
                onclone: function (doc) {
                    doc.querySelectorAll('.pl-section-head, .pl-section-name').forEach(function (el) {
                        el.style.color = '#14532d';
                        el.style.webkitTextFillColor = '#14532d';
                    });
                    doc.querySelectorAll('.pl-nfc-badge').forEach(function (el) {
                        el.style.color = '#ffffff';
                        el.style.webkitTextFillColor = '#ffffff';
                    });
                }
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all'] }
        }).from(el).toPdf();
        var pdf = await worker.get('pdf');
        var pages = pageCount(pdf);
        while (pages > 1) {
            pdf.deletePage(pages);
            pages = pageCount(pdf);
        }
        pdfBlob = pdf.output('blob');
        return pdf;
    }

    async function downloadPdf() {
        if (busy) return;
        setBusy(true);
        try {
            var pdf = await buildPdf();
            pdf.save(filename);
        } catch (err) {
            alert(err && err.message ? err.message : 'Could not create PDF.');
        } finally {
            setBusy(false);
        }
    }

    async function sharePdf() {
        if (busy) return;
        setBusy(true, 'Preparing…');
        try {
            if (!pdfBlob) await buildPdf();
            var file = new File([pdfBlob], filename, { type: 'application/pdf' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: 'Price List', text: 'Wholesale price list' });
                return;
            }
            var pdf = await buildPdf();
            pdf.save(filename);
        } catch (err) {
            if (err && err.name === 'AbortError') return;
            alert(err && err.message ? err.message : 'Could not share PDF.');
        } finally {
            setBusy(false);
        }
    }

    function start() {
        fitCatalog();
        if (itemCount > 0) setTimeout(downloadPdf, 500);
    }
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(start).catch(start);
    } else {
        start();
    }
    window.addEventListener('resize', fitCatalog);

    if (btnPdf) btnPdf.addEventListener('click', downloadPdf);
    if (btnPrint) btnPrint.addEventListener('click', function () {
        fitCatalog();
        window.print();
    });
    if (btnShare && navigator.canShare) {
        btnShare.hidden = false;
        btnShare.addEventListener('click', sharePdf);
    }
})();
</script>
</body>
</html>
