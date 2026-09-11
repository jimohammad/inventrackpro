/**
 * Merge uploaded PO documents (PDF / JPG / PNG / WEBP) into one bank PDF.
 * Expects pdf-lib (global PDFLib) and window.PO_DOCS_PACK = { pack, cover, filename }.
 */
(function () {
    const A4 = [595.28, 841.89];
    const MARGIN = 36;

    function C() {
        return {
            band:   PDFLib.rgb(0.043, 0.122, 0.212),
            band2:  PDFLib.rgb(0.086, 0.204, 0.333),
            gold:   PDFLib.rgb(0.78, 0.62, 0.28),
            navy:   PDFLib.rgb(0.11, 0.22, 0.37),
            ink:    PDFLib.rgb(0.07, 0.09, 0.15),
            muted:  PDFLib.rgb(0.42, 0.48, 0.55),
            line:   PDFLib.rgb(0.82, 0.86, 0.90),
            rule:   PDFLib.rgb(0.18, 0.29, 0.45),
            paper:  PDFLib.rgb(0.97, 0.98, 0.99),
            soft:   PDFLib.rgb(0.95, 0.96, 0.98),
            white:  PDFLib.rgb(1, 1, 1),
            inv:    PDFLib.rgb(0.11, 0.30, 0.85),
            invBg:  PDFLib.rgb(0.93, 0.96, 1),
            tt:     PDFLib.rgb(0.02, 0.47, 0.34),
            ttBg:   PDFLib.rgb(0.93, 0.99, 0.96),
            skip:   PDFLib.rgb(0.73, 0.11, 0.11)
        };
    }

    function cfg() {
        return window.PO_DOCS_PACK || {};
    }

    function statusBox() {
        return document.getElementById('statusBox');
    }

    function setStatus(text, kind) {
        const el = statusBox();
        if (!el) return;
        el.textContent = text;
        el.className = 'status' + (kind ? ' ' + kind : '');
    }

    function setBadge(index, text, kind) {
        const row = document.querySelector('.file[data-doc-index="' + index + '"] [data-badge]');
        if (!row) return;
        row.textContent = text;
        row.className = 'badge' + (kind ? ' ' + kind : '');
    }

    function pdfSafe(str) {
        return String(str || '')
            .replace(/[\u2012\u2013\u2014\u2015]/g, '-')
            .replace(/[\u2018\u2019]/g, "'")
            .replace(/[\u201C\u201D]/g, '"')
            .replace(/\u2026/g, '...')
            .replace(/\u00A0/g, ' ')
            .replace(/[^\x20-\x7E]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function wrapText(text, font, size, maxWidth) {
        const raw = pdfSafe(text);
        if (!raw) return [''];
        const words = raw.split(' ');
        const lines = [];
        let line = '';
        for (let i = 0; i < words.length; i++) {
            let word = words[i];
            if (font.widthOfTextAtSize(word, size) > maxWidth) {
                let cut = '';
                for (let c = 0; c < word.length; c++) {
                    const next = cut + word[c];
                    if (font.widthOfTextAtSize(next, size) > maxWidth && cut) {
                        if (line) { lines.push(line); line = ''; }
                        lines.push(cut);
                        cut = word[c];
                    } else {
                        cut = next;
                    }
                }
                word = cut;
            }
            const test = line ? line + ' ' + word : word;
            if (font.widthOfTextAtSize(test, size) <= maxWidth) {
                line = test;
            } else {
                if (line) lines.push(line);
                line = word;
            }
        }
        if (line) lines.push(line);
        return lines.length ? lines : [''];
    }

    function kindFromBytes(bytes, mime) {
        const m = String(mime || '').toLowerCase();
        if (bytes.length >= 4 && bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46) {
            return 'pdf';
        }
        if (bytes.length >= 3 && bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF) {
            return 'jpg';
        }
        if (bytes.length >= 8 && bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47) {
            return 'png';
        }
        if (bytes.length >= 12 && bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46
            && bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50) {
            return 'webp';
        }
        if (m.indexOf('pdf') !== -1) return 'pdf';
        if (m.indexOf('jpeg') !== -1 || m.indexOf('jpg') !== -1) return 'jpg';
        if (m.indexOf('png') !== -1) return 'png';
        if (m.indexOf('webp') !== -1) return 'webp';
        return '';
    }

    async function webpToPngBytes(bytes) {
        const blob = new Blob([bytes], { type: 'image/webp' });
        const url = URL.createObjectURL(blob);
        try {
            const img = await new Promise(function (resolve, reject) {
                const i = new Image();
                i.onload = function () { resolve(i); };
                i.onerror = function () { reject(new Error('WEBP could not be decoded')); };
                i.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth || img.width;
            canvas.height = img.naturalHeight || img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            const pngBlob = await new Promise(function (resolve, reject) {
                canvas.toBlob(function (b) {
                    if (b) resolve(b);
                    else reject(new Error('WEBP convert failed'));
                }, 'image/png');
            });
            return new Uint8Array(await pngBlob.arrayBuffer());
        } finally {
            URL.revokeObjectURL(url);
        }
    }

    async function addImagePage(pdfDoc, image) {
        const page = pdfDoc.addPage(A4);
        const maxW = page.getWidth() - MARGIN * 2;
        const maxH = page.getHeight() - MARGIN * 2;
        const scale = Math.min(maxW / image.width, maxH / image.height);
        const w = image.width * scale;
        const h = image.height * scale;
        page.drawImage(image, {
            x: (page.getWidth() - w) / 2,
            y: (page.getHeight() - h) / 2,
            width: w,
            height: h
        });
    }

    async function fontsFor(pdfDoc) {
        return {
            font: await pdfDoc.embedFont(PDFLib.StandardFonts.Helvetica),
            bold: await pdfDoc.embedFont(PDFLib.StandardFonts.HelveticaBold)
        };
    }

    const qrByDoc = new WeakMap();

    function pngBytesFromDataUri(uri) {
        if (!uri || uri.indexOf('base64,') < 0) {
            return null;
        }
        const raw = uri.split('base64,')[1];
        const bin = atob(raw);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) {
            bytes[i] = bin.charCodeAt(i);
        }
        return bytes;
    }

    async function qrImageFor(pdfDoc) {
        if (qrByDoc.has(pdfDoc)) {
            return qrByDoc.get(pdfDoc);
        }
        const uri = (cfg().cover || {}).qr_png;
        let img = null;
        try {
            const bytes = pngBytesFromDataUri(uri);
            if (bytes && bytes.length) {
                img = await pdfDoc.embedPng(bytes);
            }
        } catch (e) {
            img = null;
        }
        qrByDoc.set(pdfDoc, img);
        return img;
    }

    function drawHeaderBar(page, fonts, company, rightLabel, qrImage) {
        const c = C();
        const w = page.getWidth();
        const h = page.getHeight();

        page.drawRectangle({ x: 0, y: h - 4, width: w, height: 4, color: c.navy });
        page.drawRectangle({ x: 0, y: h - 7, width: w, height: 3, color: c.gold });

        const qrSize = 56;
        const qrPad = 4;
        const qrBox = qrSize + qrPad * 2;
        const qrX = qrImage ? (w - MARGIN - qrBox) : w;
        const qy = h - 14 - qrBox;
        const cover = cfg().cover || {};
        const companyMax = qrImage ? (qrX - MARGIN - 14) : (w - MARGIN * 2);
        const maxW = Math.max(140, companyMax);
        const companyName = pdfSafe(company) || 'Company';
        const companyLines = wrapText(companyName, fonts.bold, 14, maxW);
        let y = h - 32;
        page.drawText(companyLines[0] || 'Company', {
            x: MARGIN, y: y, size: 14, font: fonts.bold, color: c.navy
        });
        y -= 14;

        const rawAddr = String(cover.address || '').replace(/\r\n/g, '\n').split('\n');
        const addrLines = [];
        for (let i = 0; i < rawAddr.length; i++) {
            const wrapped = wrapText(rawAddr[i], fonts.font, 8, maxW);
            for (let j = 0; j < wrapped.length; j++) {
                if (wrapped[j]) {
                    addrLines.push(wrapped[j]);
                }
            }
        }
        const showAddr = addrLines.slice(0, 2);
        for (let i = 0; i < showAddr.length; i++) {
            page.drawText(showAddr[i], {
                x: MARGIN, y: y, size: 8, font: fonts.font, color: c.ink
            });
            y -= 11;
        }

        page.drawText('Confidential  |  Bank submission', {
            x: MARGIN, y: y, size: 8, font: fonts.font, color: c.muted
        });
        y -= 14;

        const tag = pdfSafe(rightLabel || 'SUPPORTING DOCUMENTS');
        if (tag && !qrImage) {
            const tw = fonts.bold.widthOfTextAtSize(tag, 8);
            page.drawText(tag, {
                x: w - MARGIN - tw, y: h - 32, size: 8, font: fonts.bold, color: c.navy
            });
        }

        if (qrImage) {
            page.drawRectangle({
                x: qrX - 1, y: qy - 1, width: qrBox + 2, height: qrBox + 2,
                color: c.white,
                borderColor: c.rule,
                borderWidth: 0.8
            });
            page.drawImage(qrImage, {
                x: qrX + qrPad, y: qy + qrPad, width: qrSize, height: qrSize
            });
        }

        const ruleY = Math.min(y, qy - 8);
        page.drawRectangle({
            x: MARGIN, y: ruleY, width: w - MARGIN * 2, height: 0.7, color: c.line
        });
        page.drawRectangle({ x: MARGIN, y: ruleY, width: 64, height: 2.2, color: c.gold });
    }

    function drawFooterBar(page, fonts, phone, leftText) {
        const c = C();
        const w = page.getWidth();
        page.drawRectangle({ x: MARGIN, y: 26, width: w - MARGIN * 2, height: 0.7, color: c.line });
        page.drawRectangle({ x: MARGIN, y: 26, width: 40, height: 2, color: c.gold });
        const left = leftText || 'Original uploaded files follow this page';
        page.drawText(pdfSafe(left), { x: MARGIN, y: 12, size: 7, font: fonts.font, color: c.muted });
        const tel = pdfSafe(phone);
        if (tel) {
            const tw = fonts.font.widthOfTextAtSize(tel, 7);
            page.drawText(tel, { x: w - MARGIN - tw, y: 12, size: 7, font: fonts.font, color: c.navy });
        }
    }

    function drawCard(page, x, y, w, h, label, value, fonts, valueSize) {
        const c = C();
        page.drawRectangle({
            x: x, y: y, width: w, height: h,
            color: c.paper,
            borderColor: c.line,
            borderWidth: 0.7
        });
        page.drawRectangle({ x: x, y: y, width: 3, height: h, color: c.navy });
        page.drawText(pdfSafe(label).toUpperCase(), {
            x: x + 10, y: y + h - 16, size: 7, font: fonts.bold, color: c.muted
        });
        const valLines = wrapText(value, fonts.bold, valueSize || 12, w - 20);
        let ty = y + h - 36;
        for (let i = 0; i < valLines.length; i++) {
            page.drawText(valLines[i], {
                x: x + 10, y: ty, size: valueSize || 12, font: fonts.bold, color: c.navy
            });
            ty -= (valueSize || 12) + 3;
        }
    }

    function drawChip(page, x, y, w, h, label, count, fonts, fg, bg) {
        const c = C();
        page.drawRectangle({
            x: x, y: y, width: w, height: h,
            color: bg,
            borderColor: c.line,
            borderWidth: 0.7
        });
        page.drawText(pdfSafe(label), {
            x: x + 12, y: y + h - 16, size: 8, font: fonts.bold, color: fg
        });
        page.drawText(String(count), {
            x: x + 12, y: y + 10, size: 16, font: fonts.bold, color: fg
        });
    }

    async function drawPoCover(pdfDoc, info, skipped) {
        const fonts = await fontsFor(pdfDoc);
        const page = pdfDoc.addPage(A4);
        const c = C();
        const cover = cfg().cover || {};
        const w = page.getWidth();
        const h = page.getHeight();
        const poNo = info.po_no || info.label || 'PO';
        const supplier = info.supplier || info.name || '';

        const qrImage = await qrImageFor(pdfDoc);
        drawHeaderBar(page, fonts, cover.company || info.company, 'PURCHASE ORDER FILE', qrImage);
        drawFooterBar(
            page,
            fonts,
            cover.phone || info.phone,
            qrImage ? 'Scan the QR to verify this file on the company website' : 'Original invoice and TT copies follow this page'
        );

        let y = h - 128;
        page.drawText('PURCHASE ORDER', {
            x: MARGIN, y: y, size: 8, font: fonts.bold, color: c.gold
        });
        y -= 28;
        page.drawText(pdfSafe(poNo), {
            x: MARGIN, y: y, size: 26, font: fonts.bold, color: c.navy
        });
        y -= 22;
        const supLines = wrapText(supplier, fonts.font, 12, w - MARGIN * 2);
        for (let i = 0; i < supLines.length; i++) {
            page.drawText(supLines[i], { x: MARGIN, y: y, size: 12, font: fonts.font, color: c.ink });
            y -= 16;
        }

        y -= 18;
        const gap = 10;
        const cardW = (w - MARGIN * 2 - gap * 2) / 3;
        const cardH = 52;
        drawCard(page, MARGIN, y - cardH, cardW, cardH, 'PO date', info.po_date || '-', fonts, 11);
        drawCard(page, MARGIN + cardW + gap, y - cardH, cardW, cardH, 'PO total', info.total || '-', fonts, 11);
        drawCard(page, MARGIN + (cardW + gap) * 2, y - cardH, cardW, cardH, 'Paid', info.paid || '-', fonts, 11);
        y -= cardH + 22;

        const chipW = (w - MARGIN * 2 - gap) / 2;
        const chipH = 44;
        drawChip(page, MARGIN, y - chipH, chipW, chipH, 'Supplier invoices', info.invoice_count || 0, fonts, c.inv, c.invBg);
        drawChip(page, MARGIN + chipW + gap, y - chipH, chipW, chipH, 'TT / transfer copies', info.tt_count || 0, fonts, c.tt, c.ttBg);
        y -= chipH + 28;

        page.drawRectangle({ x: MARGIN, y: y - 70, width: w - MARGIN * 2, height: 70, color: c.soft });
        page.drawRectangle({ x: MARGIN, y: y - 70, width: 4, height: 70, color: c.gold });
        const note = 'The following pages are the original supplier invoice and bank transfer copies uploaded for this purchase order. Please treat this file as confidential bank correspondence.';
        const noteLines = wrapText(note, fonts.font, 9, w - MARGIN * 2 - 24);
        let ny = y - 22;
        for (let i = 0; i < noteLines.length; i++) {
            page.drawText(noteLines[i], { x: MARGIN + 16, y: ny, size: 9, font: fonts.font, color: c.navy });
            ny -= 13;
        }

        if (skipped && skipped.length) {
            y = ny - 24;
            page.drawText('Skipped (attach separately)', {
                x: MARGIN, y: y, size: 9, font: fonts.bold, color: c.skip
            });
            y -= 14;
            for (let i = 0; i < skipped.length && y > 48; i++) {
                const line = (skipped[i].name || '') + ' - ' + (skipped[i].reason || '');
                const lines = wrapText(line, fonts.font, 8, w - MARGIN * 2);
                for (let j = 0; j < lines.length; j++) {
                    page.drawText(lines[j], { x: MARGIN, y: y, size: 8, font: fonts.font, color: c.muted });
                    y -= 12;
                }
            }
        }
    }

    async function drawPackCover(pdfDoc, cover, listItems, skipped) {
        const fonts = await fontsFor(pdfDoc);
        const page = pdfDoc.addPage(A4);
        const c = C();
        const w = page.getWidth();
        const h = page.getHeight();

        const qrImage = await qrImageFor(pdfDoc);
        drawHeaderBar(page, fonts, cover.company, 'BANK DOCUMENT PACK', qrImage);
        drawFooterBar(
            page,
            fonts,
            cover.phone,
            qrImage ? 'Scan the QR to verify this pack on the company website' : 'Original invoice and TT copies follow this list'
        );

        let y = h - 126;
        page.drawText(pdfSafe(cover.heading) || 'Bank document pack', {
            x: MARGIN, y: y, size: 18, font: fonts.bold, color: c.navy
        });
        y -= 16;
        const sub = (cover.period ? 'Period  ' + cover.period : '')
            + (cover.date_means ? '   |   ' + cover.date_means : '')
            + (cover.prepared ? '   |   Prepared ' + cover.prepared : '');
        if (pdfSafe(sub)) {
            page.drawText(pdfSafe(sub), { x: MARGIN, y: y, size: 8, font: fonts.font, color: c.muted });
        }
        y -= 22;

        const gap = 8;
        const kpiW = (w - MARGIN * 2 - gap * 3) / 4;
        const kpiH = 48;
        const kpis = [
            ['Purchase orders', String(cover.po_count || 0)],
            ['Supplier invoices', String(cover.invoice_count || 0)],
            ['TT copies', String(cover.tt_count || 0)],
            ['Branch', cover.branch || '-']
        ];
        for (let i = 0; i < kpis.length; i++) {
            drawCard(page, MARGIN + i * (kpiW + gap), y - kpiH, kpiW, kpiH, kpis[i][0], kpis[i][1], fonts, i === 3 ? 9 : 14);
        }
        y -= kpiH + 22;

        page.drawText('Purchase orders in this pack', {
            x: MARGIN, y: y, size: 9, font: fonts.bold, color: c.navy
        });
        y -= 8;

        const tableX = MARGIN;
        const tableW = w - MARGIN * 2;
        const colNo = 22;
        const colPo = 68;
        const colDate = 62;
        const colForeign = 98;
        const colPaid = 74;
        const colName = tableW - colNo - colPo - colDate - colForeign - colPaid;
        const rowH = 16;

        function headerCell(label, x) {
            page.drawText(label, { x: x, y: y - 12, size: 7, font: fonts.bold, color: c.navy });
        }
        function drawRight(text, rightX, ty, size, font, color) {
            const t = pdfSafe(text);
            const tw = font.widthOfTextAtSize(t, size);
            page.drawText(t, { x: Math.max(rightX - tw, tableX), y: ty, size: size, font: font, color: color });
        }
        page.drawRectangle({ x: tableX, y: y - rowH, width: tableW, height: rowH, color: c.paper });
        page.drawRectangle({ x: tableX, y: y - rowH, width: tableW, height: 1.1, color: c.navy });
        page.drawRectangle({ x: tableX, y: y, width: tableW, height: 1.1, color: c.navy });
        headerCell('#', tableX + 6);
        headerCell('PO', tableX + colNo);
        headerCell('Supplier', tableX + colNo + colPo);
        headerCell('Date', tableX + colNo + colPo + colName);
        headerCell('Foreign', tableX + colNo + colPo + colName + colDate);
        headerCell('Paid KWD', tableX + colNo + colPo + colName + colDate + colForeign);
        y -= rowH;

        const items = listItems || [];
        const maxRows = Math.max(0, Math.floor((y - 56) / rowH));
        const show = items.slice(0, maxRows);
        const xDate = tableX + colNo + colPo + colName;
        const xForeign = xDate + colDate;
        const xPaid = xForeign + colForeign;
        for (let i = 0; i < show.length; i++) {
            if (i % 2 === 0) {
                page.drawRectangle({ x: tableX, y: y - rowH, width: tableW, height: rowH, color: c.soft });
            }
            const row = show[i];
            const ty = y - 11;
            page.drawText(String(i + 1), { x: tableX + 6, y: ty, size: 8, font: fonts.font, color: c.muted });
            page.drawText(pdfSafe(row.label || '').slice(0, 12), {
                x: tableX + colNo, y: ty, size: 8, font: fonts.bold, color: c.navy
            });
            const name = wrapText(row.name || '', fonts.font, 8, colName - 8)[0] || '';
            page.drawText(name, { x: tableX + colNo + colPo, y: ty, size: 8, font: fonts.font, color: c.ink });
            page.drawText(pdfSafe(row.date || ''), {
                x: xDate, y: ty, size: 8, font: fonts.font, color: c.muted
            });
            drawRight(row.foreign || '-', xPaid - 6, ty, 8, fonts.bold, c.navy);
            drawRight(row.paid || '', tableX + tableW - 6, ty, 8, fonts.bold, c.navy);
            y -= rowH;
        }
        if (items.length > show.length) {
            y -= 10;
            page.drawText('... and ' + (items.length - show.length) + ' more purchase orders', {
                x: MARGIN, y: y, size: 8, font: fonts.font, color: c.muted
            });
        }

        if (skipped && skipped.length) {
            y -= 18;
            page.drawText('Skipped (attach separately)', {
                x: MARGIN, y: y, size: 9, font: fonts.bold, color: c.skip
            });
            y -= 14;
            for (let i = 0; i < skipped.length && y > 40; i++) {
                const line = (skipped[i].name || '') + ' - ' + (skipped[i].reason || '');
                page.drawText(wrapText(line, fonts.font, 8, w - MARGIN * 2)[0], {
                    x: MARGIN, y: y, size: 8, font: fonts.font, color: c.muted
                });
                y -= 12;
            }
        }
    }

    function triggerDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
    }

    function printBlob(blob) {
        const url = URL.createObjectURL(blob);
        const w = window.open(url, '_blank');
        if (!w) {
            setStatus('Pop-up blocked. Allow pop-ups, or use Download PDF.', 'warn');
            return;
        }
        const t = setInterval(function () {
            try {
                if (w.document && w.document.readyState === 'complete') {
                    clearInterval(t);
                    w.focus();
                    w.print();
                }
            } catch (e) {
                clearInterval(t);
                w.focus();
                w.print();
            }
        }, 250);
        setTimeout(function () { clearInterval(t); }, 8000);
    }

    async function addFileBytes(attachments, bytes, mime) {
        const kind = kindFromBytes(bytes, mime);
        const before = attachments.getPageCount();
        if (kind === 'pdf') {
            const src = await PDFLib.PDFDocument.load(bytes, { ignoreEncryption: true });
            const copied = await attachments.copyPages(src, src.getPageIndices());
            copied.forEach(function (p) { attachments.addPage(p); });
        } else if (kind === 'jpg') {
            const img = await attachments.embedJpg(bytes);
            await addImagePage(attachments, img);
        } else if (kind === 'png') {
            const img = await attachments.embedPng(bytes);
            await addImagePage(attachments, img);
        } else if (kind === 'webp') {
            const pngBytes = await webpToPngBytes(bytes);
            const img = await attachments.embedPng(pngBytes);
            await addImagePage(attachments, img);
        } else {
            throw new Error('Unsupported file type');
        }
        const added = attachments.getPageCount() - before;
        if (added < 1) {
            throw new Error('No pages found');
        }
        return added;
    }

    async function buildPack() {
        const data = cfg();
        const pack = data.pack || [];
        const cover = data.cover || {};
        const filename = data.filename || 'bank_docs.pdf';
        const btnDownload = document.getElementById('btnDownload');
        const btnPrint = document.getElementById('btnPrint');
        let packBlob = null;

        if (typeof PDFLib === 'undefined') {
            setStatus('Could not load the PDF library. Check internet / CDN and refresh.', 'err');
            return;
        }

        const attachments = await PDFLib.PDFDocument.create();
        const included = [];
        const skipped = [];
        let addedPages = 0;
        const fileTotal = pack.filter(function (d) { return d.kind !== 'divider'; }).length;
        let fileDone = 0;

        for (let i = 0; i < pack.length; i++) {
            const doc = pack[i];
            if (doc.kind === 'divider') {
                continue;
            }

            fileDone += 1;
            setStatus('Adding ' + (doc.label || 'file') + ' (' + fileDone + ' of ' + fileTotal + ')…');
            try {
                const res = await fetch(doc.url, { credentials: 'same-origin', cache: 'no-store' });
                if (!res.ok) {
                    throw new Error('Download failed (' + res.status + ')');
                }
                const buf = await res.arrayBuffer();
                const bytes = new Uint8Array(buf);
                addedPages += await addFileBytes(attachments, bytes, doc.mime);
                included.push(doc);
                setBadge(i, 'Added', 'ok');
            } catch (err) {
                let reason = (err && err.message) ? err.message : 'Could not add file';
                if (/password|encrypt/i.test(reason)) {
                    reason = 'Password protected';
                }
                skipped.push({ name: doc.name || doc.label || 'File', reason: reason });
                setBadge(i, 'Skipped', 'skip');
            }
        }

        if (addedPages < 1 || included.length < 1) {
            setStatus('None of the files could be added. Open them one by one, or re-upload without a password.', 'err');
            return;
        }

        const listItems = (cover.list && cover.list.length)
            ? cover.list
            : included.map(function (f) { return { label: f.label, name: f.name }; });

        const pdfDoc = await PDFLib.PDFDocument.create();
        if ((cover.style || 'pack') === 'po') {
            await drawPoCover(pdfDoc, cover, skipped);
        } else {
            await drawPackCover(pdfDoc, cover, listItems, skipped);
        }
        const copiedAll = await pdfDoc.copyPages(attachments, attachments.getPageIndices());
        copiedAll.forEach(function (p) { pdfDoc.addPage(p); });

        const pdfBytes = await pdfDoc.save();
        packBlob = new Blob([pdfBytes], { type: 'application/pdf' });
        if (btnDownload) btnDownload.disabled = false;
        if (btnPrint) btnPrint.disabled = false;

        if (skipped.length) {
            setStatus('PDF ready with ' + included.length + ' file(s). ' + skipped.length + ' skipped — attach those separately.', 'warn');
        } else {
            setStatus('PDF ready. Downloading ' + filename + '.', 'ok');
        }
        triggerDownload(packBlob, filename);

        if (btnDownload) {
            btnDownload.addEventListener('click', function () {
                if (packBlob) triggerDownload(packBlob, filename);
            });
        }
        if (btnPrint) {
            btnPrint.addEventListener('click', function () {
                if (packBlob) printBlob(packBlob);
            });
        }
    }

    function failBuild(err) {
        setStatus((err && err.message) ? err.message : 'Could not build the PDF.', 'err');
    }

    function start() {
        buildPack().catch(failBuild);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
