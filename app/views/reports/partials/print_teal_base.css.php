<?php
/**
 * Shared print typography for teal report templates (readable hard-copy size).
 * Include inside <style> in each *_print.php view.
 */
?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            background: #fff;
            line-height: 1.45;
        }
        .page { padding: 14px 16px; width: 100%; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0e7490;
        }
        .company-name { font-size: 18px; font-weight: 800; color: #0e7490; }
        .company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 14px; font-weight: 700; color: #0e7490; text-align: right; }
        .doc-meta { font-size: 11px; color: #64748b; text-align: right; margin-top: 2px; }
        .doc-total { font-size: 13px; font-weight: 800; color: #0e7490; text-align: right; margin-top: 2px; }

        .period-box {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #0f766e;
        }
        .period-box strong { color: #0e7490; }

        .summary-row { display: flex; gap: 8px; margin-bottom: 10px; }
        .sbox {
            flex: 1;
            border: 1px solid #a5f3fc;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            background: #f8fafc;
        }
        .sbox-label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.35px; }
        .sbox-value { font-size: 14px; font-weight: 800; color: #0e7490; margin-top: 3px; }

        .summary-grid { display: flex; gap: 8px; margin-bottom: 10px; }
        .summary-panel {
            flex: 1;
            border: 1px solid #a5f3fc;
            border-radius: 6px;
            overflow: hidden;
            background: #f8fafc;
        }
        .summary-panel h3 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            color: #0f766e;
            background: #ccfbf1;
            padding: 6px 8px;
            border-bottom: 1px solid #99f6e4;
        }
        .mini-table { width: 100%; border-collapse: collapse; }
        .mini-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f766e;
            padding: 4px 6px;
            text-align: left;
            background: #f0fdfa;
            border-bottom: 1px solid #99f6e4;
        }
        .mini-table th.num, .mini-table td.num { text-align: right; }
        .mini-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .mini-table tbody tr:nth-child(even) { background: #f8fafc; }
        .mini-table tr:last-child td { border-bottom: none; }

        .detail-wrap { border: 1px solid #99f6e4; border-radius: 6px; overflow: hidden; }
        .detail-bar {
            background: #0e7490;
            color: #fff;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }
        table.detail { width: 100%; border-collapse: collapse; }
        table.detail thead tr { background: #ccfbf1; }
        table.detail thead th {
            padding: 5px 7px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f766e;
            border-bottom: 1px solid #99f6e4;
            text-align: left;
        }
        table.detail thead th.num { text-align: right; }
        table.detail tbody td {
            padding: 4px 7px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            vertical-align: top;
        }
        table.detail tbody tr:nth-child(even) { background: #f8fafc; }
        table.detail tbody td.num { text-align: right; font-weight: 700; white-space: nowrap; }
        table.detail tbody td.ref {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 10px;
            color: #0e7490;
            font-weight: 600;
        }
        table.detail tbody td.party strong { font-weight: 800; color: #134e4a; }
        table.detail tfoot td {
            padding: 6px 8px;
            font-size: 12px;
            font-weight: 700;
            background: #ecfeff;
            border-top: 1px solid #67e8f9;
            color: #0f766e;
        }
        table.detail tfoot td.num { text-align: right; }

        .empty {
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border: 1px dashed #a5f3fc;
            border-radius: 6px;
            background: #ecfeff;
        }
        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #a5f3fc;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }

        @page { margin: 12mm 10mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { padding: 0; }
            table.detail thead { display: table-header-group; }
            table.detail tr { break-inside: avoid; }
        }
