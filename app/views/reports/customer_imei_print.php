<?php
$grouped = [];
foreach ($records as $r) {
    $invKey  = $r['invoice_no'];
    $itemKey = $r['item_name'];
    if (!isset($grouped[$invKey])) {
        $grouped[$invKey] = [
            'invoice_no' => $r['invoice_no'],
            'date'       => $r['date'],
            'items'      => [],
            'count'      => 0,
        ];
    }
    if (!isset($grouped[$invKey]['items'][$itemKey])) {
        $grouped[$invKey]['items'][$itemKey] = [
            'item_name' => $r['item_name'],
            'brand'     => $r['brand'],
            'model'     => $r['model'],
            'imeis'     => [],
        ];
    }
    $grouped[$invKey]['items'][$itemKey]['imeis'][] = $r;
    $grouped[$invKey]['count']++;
}

$totalImei     = count($records);
$totalInvoices = count($grouped);
$hasImei2      = false;
foreach ($records as $r) {
    if (!empty($r['imei2'])) {
        $hasImei2 = true;
        break;
    }
}

$companyName = $settings['company_name'] ?? PDF_COMPANY_NAME;
$companyPhone = $settings['company_phone'] ?? PDF_COMPANY_PHONE;
$periodLabel = ($fromDate !== '' ? date('d M Y', strtotime($fromDate)) : 'All time')
    . ($toDate !== '' ? ' — ' . date('d M Y', strtotime($toDate)) : '');

/** IMEI slots per table row (uses full page width). Fewer when IMEI 2 is shown. */
$slotsPerRow = $hasImei2 ? 2 : 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer IMEI — <?= htmlspecialchars($party['name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            background: #fff;
            line-height: 1.45;
        }
        .page { padding: 14px 16px; width: 100%; max-width: none; margin: 0; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0e7490;
        }
        .company-name { font-size: 20px; font-weight: 800; color: #0e7490; letter-spacing: -0.2px; }
        .company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 17px; font-weight: 700; color: #0e7490; text-align: right; }
        .doc-sub { font-size: 11px; color: #64748b; text-align: right; margin-top: 3px; }

        .party-box {
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 8px;
            padding: 11px 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .party-name { font-size: 15px; font-weight: 400; color: #0f766e; }
        .party-name strong { font-weight: 800; }
        .party-meta { font-size: 11px; color: #64748b; margin-top: 4px; }
        .party-meta span + span::before { content: ' · '; color: #94a3b8; }
        .meta-right { text-align: right; font-size: 11px; color: #475569; }
        .meta-right strong { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; margin-bottom: 2px; }
        .filter-tag {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            background: #fff;
            border: 1px solid #67e8f9;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            color: #0e7490;
        }

        .summary-row { display: flex; gap: 8px; margin-bottom: 16px; }
        .sbox {
            flex: 1;
            border: 1px solid #a5f3fc;
            border-radius: 7px;
            padding: 8px 10px;
            text-align: center;
            background: #f8fafc;
        }
        .sbox-label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.35px; }
        .sbox-value { font-size: 16px; font-weight: 800; color: #0e7490; margin-top: 2px; }

        .invoice-block { margin-bottom: 12px; }
        .item-block { break-inside: avoid-page; }
        .inv-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0e7490;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px 6px 0 0;
            font-size: 13px;
            font-weight: 700;
        }
        .inv-bar .inv-date { font-weight: 500; font-size: 11px; opacity: 0.92; }
        .inv-bar .inv-qty { font-size: 11px; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; }

        .item-label {
            padding: 6px 12px;
            background: #f0fdfa;
            border-left: 1px solid #99f6e4;
            border-right: 1px solid #99f6e4;
            font-size: 12px;
            font-weight: 600;
            color: #134e4a;
        }
        .item-label .item-sub { font-weight: 400; color: #64748b; margin-left: 6px; }

        table.imei-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #99f6e4;
            border-top: none;
            margin-bottom: 0;
        }
        table.imei-table:last-of-type { border-radius: 0 0 6px 6px; overflow: hidden; }
        .invoice-block table.imei-table:last-child { margin-bottom: 0; border-radius: 0 0 6px 6px; }
        .invoice-block .item-block:last-child table.imei-table { border-radius: 0 0 6px 6px; }

        table.imei-table thead tr { background: #ccfbf1; }
        table.imei-table thead th {
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            color: #0f766e;
            text-align: left;
            border-bottom: 1px solid #99f6e4;
        }
        table.imei-table thead th.col-num { width: 4%; text-align: center; }
        table.imei-table thead th.col-imei { width: auto; }
        table.imei-table thead th.col-imei2 { width: auto; }
        table.imei-table { table-layout: fixed; }
        table.imei-table thead th.slot-divider {
            width: 1px;
            padding: 0;
            background: #99f6e4;
            border-bottom: 1px solid #99f6e4;
        }
        table.imei-table tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            vertical-align: middle;
        }
        table.imei-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.imei-table tbody tr:last-child td { border-bottom: none; }
        table.imei-table tbody td.slot-divider {
            padding: 0;
            width: 1px;
            background: #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .col-num { text-align: center; color: #64748b; font-size: 10px; font-weight: 600; }
        .col-imei {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: 600;
            letter-spacing: 0.2px;
            font-size: 11px;
            white-space: nowrap;
        }
        .col-imei2 {
            font-family: 'Consolas', 'Courier New', monospace;
            color: #64748b;
            font-size: 10px;
            white-space: nowrap;
        }
        .cell-empty { background: #fafafa; }

        .grand-total {
            margin-top: 12px;
            padding: 10px 14px;
            text-align: center;
            background: #ecfeff;
            border: 1px solid #67e8f9;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #0f766e;
        }
        .grand-total strong { color: #0e7490; font-size: 14px; }

        .empty-msg {
            padding: 24px;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }

        .footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }

        @page { margin: 14mm 12mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name"><?= htmlspecialchars((string) $companyName) ?></div>
            <?php if (!empty($settings['company_address'])): ?>
            <div class="company-sub"><?= htmlspecialchars((string) $settings['company_address']) ?></div>
            <?php endif; ?>
            <?php if ($companyPhone !== ''): ?>
            <div class="company-sub">Tel: <?= htmlspecialchars((string) $companyPhone) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="doc-title">Customer IMEI Report</div>
            <div class="doc-sub">Generated <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <div class="party-box">
        <div>
            <div class="party-name">Customer Name: <strong><?= htmlspecialchars($party['name']) ?></strong></div>
            <div class="party-meta">
                <?php if (!empty($party['party_code'])): ?>
                <span>Code: <?= htmlspecialchars((string) $party['party_code']) ?></span>
                <?php endif; ?>
                <?php if (!empty($party['phone'])): ?>
                <span><?= htmlspecialchars((string) $party['phone']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="meta-right">
            <strong>Period</strong>
            <?= htmlspecialchars($periodLabel) ?>
            <?php if (!empty($itemName)): ?>
            <span class="filter-tag">Item: <?= htmlspecialchars($itemName) ?></span>
            <?php endif; ?>
            <?php if ($invoiceNo !== ''): ?>
            <span class="filter-tag">Invoice: <?= htmlspecialchars($invoiceNo) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="empty-msg">No IMEI records found for the selected filters.</div>
    <?php else: ?>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Total IMEIs</div>
            <div class="sbox-value"><?= (int) $totalImei ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Invoices</div>
            <div class="sbox-value"><?= (int) $totalInvoices ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Items</div>
            <div class="sbox-value"><?= (int) array_sum(array_map(fn($inv) => count($inv['items']), $grouped)) ?></div>
        </div>
    </div>

    <?php $globalNum = 1; ?>
    <?php foreach ($grouped as $inv): ?>
    <div class="invoice-block">
        <div class="inv-bar">
            <span>Invoice Number: <?= htmlspecialchars($inv['invoice_no']) ?></span>
            <span class="inv-date"><?= date('d M Y', strtotime($inv['date'])) ?></span>
            <span class="inv-qty"><?= (int) $inv['count'] ?> unit<?= $inv['count'] === 1 ? '' : 's' ?></span>
        </div>

        <?php foreach ($inv['items'] as $item): ?>
        <div class="item-block">
            <div class="item-label">
                <?= htmlspecialchars($item['item_name']) ?>
                <?php
                $sub = trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''));
                if ($sub !== ''):
                ?>
                <span class="item-sub"><?= htmlspecialchars($sub) ?></span>
                <?php endif; ?>
            </div>
            <?php
            $itemRows = [];
            foreach ($item['imeis'] as $r) {
                $itemRows[] = [
                    'num'   => $globalNum++,
                    'imei'  => $r['imei'],
                    'imei2' => $r['imei2'] ?? '',
                ];
            }
            $rowChunks = array_chunk($itemRows, $slotsPerRow);
            ?>
            <table class="imei-table">
                <thead>
                    <tr>
                        <?php for ($s = 0; $s < $slotsPerRow; $s++): ?>
                        <?php if ($s > 0): ?><th class="slot-divider" aria-hidden="true"></th><?php endif; ?>
                        <th class="col-num">#</th>
                        <th class="col-imei">IMEI</th>
                        <?php if ($hasImei2): ?><th class="col-imei2">IMEI 2</th><?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rowChunks as $chunk): ?>
                    <tr>
                        <?php for ($s = 0; $s < $slotsPerRow; $s++): ?>
                        <?php if ($s > 0): ?><td class="slot-divider" aria-hidden="true"></td><?php endif; ?>
                        <?php if (isset($chunk[$s])): ?>
                        <td class="col-num"><?= (int) $chunk[$s]['num'] ?></td>
                        <td class="col-imei"><?= htmlspecialchars($chunk[$s]['imei']) ?></td>
                        <?php if ($hasImei2): ?>
                        <td class="col-imei2"><?= $chunk[$s]['imei2'] !== '' ? htmlspecialchars($chunk[$s]['imei2']) : '—' ?></td>
                        <?php endif; ?>
                        <?php else: ?>
                        <td class="col-num cell-empty"></td>
                        <td class="col-imei cell-empty"></td>
                        <?php if ($hasImei2): ?><td class="col-imei2 cell-empty"></td><?php endif; ?>
                        <?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="grand-total">
        Total: <strong><?= (int) $totalImei ?></strong> IMEI<?= $totalImei === 1 ? '' : 's' ?>
        across <strong><?= (int) $totalInvoices ?></strong> invoice<?= $totalInvoices === 1 ? '' : 's' ?>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Customer IMEI Report</span>
        <span><?= date('d M Y') ?></span>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
</body>
</html>
