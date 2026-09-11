<?php
$grouped = [];
foreach ($records as $r) {
    $retKey  = $r['return_no'];
    $itemKey = $r['item_name'];
    if (!isset($grouped[$retKey])) {
        $grouped[$retKey] = [
            'return_no' => $r['return_no'],
            'date'      => $r['date'],
            'items'     => [],
            'count'     => 0,
        ];
    }
    if (!isset($grouped[$retKey]['items'][$itemKey])) {
        $grouped[$retKey]['items'][$itemKey] = [
            'item_name' => $r['item_name'],
            'brand'     => $r['brand'],
            'model'     => $r['model'],
            'imeis'     => [],
        ];
    }
    $grouped[$retKey]['items'][$itemKey]['imeis'][] = $r;
    $grouped[$retKey]['count']++;
}

$totalImei    = count($records);
$totalReturns = count($grouped);
$hasImei2     = false;
foreach ($records as $r) {
    if (!empty($r['imei2'])) {
        $hasImei2 = true;
        break;
    }
}

$companyName  = $settings['company_name'] ?? PDF_COMPANY_NAME;
$companyPhone = $settings['company_phone'] ?? PDF_COMPANY_PHONE;
$periodLabel  = ($fromDate !== '' ? date('d M Y', strtotime($fromDate)) : 'All time')
    . ($toDate !== '' ? ' — ' . date('d M Y', strtotime($toDate)) : '');

$slotsPerRow = $hasImei2 ? 2 : 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Return IMEI — <?= htmlspecialchars($party['name']) ?></title>
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
            border-bottom: 2px solid #dc2626;
        }
        .company-name { font-size: 20px; font-weight: 800; color: #dc2626; letter-spacing: -0.2px; }
        .company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 17px; font-weight: 700; color: #dc2626; text-align: right; }
        .doc-sub { font-size: 11px; color: #64748b; text-align: right; margin-top: 3px; }

        .party-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 11px 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .party-name { font-size: 15px; font-weight: 400; color: #b91c1c; }
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
            border: 1px solid #fca5a5;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            color: #dc2626;
        }

        .summary-row { display: flex; gap: 8px; margin-bottom: 16px; }
        .sbox {
            flex: 1;
            border: 1px solid #fecaca;
            border-radius: 7px;
            padding: 8px 10px;
            text-align: center;
            background: #f8fafc;
        }
        .sbox-label { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.35px; }
        .sbox-value { font-size: 16px; font-weight: 800; color: #dc2626; margin-top: 2px; }

        .invoice-block { margin-bottom: 12px; }
        .item-block { break-inside: avoid-page; }
        .inv-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #dc2626;
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
            background: #fef2f2;
            border-left: 1px solid #fecaca;
            border-right: 1px solid #fecaca;
            font-size: 12px;
            font-weight: 600;
            color: #7f1d1d;
        }
        .item-label .item-sub { font-weight: 400; color: #64748b; margin-left: 6px; }

        table.imei-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #fecaca;
            border-top: none;
            margin-bottom: 0;
            table-layout: fixed;
        }
        .invoice-block .item-block:last-child table.imei-table { border-radius: 0 0 6px 6px; }

        table.imei-table thead tr { background: #fee2e2; }
        table.imei-table thead th {
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            color: #b91c1c;
            text-align: left;
            border-bottom: 1px solid #fecaca;
        }
        table.imei-table thead th.col-num { width: 4%; text-align: center; }
        table.imei-table thead th.slot-divider {
            width: 1px;
            padding: 0;
            background: #fecaca;
            border-bottom: 1px solid #fecaca;
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
        .col-orig {
            font-family: 'Consolas', 'Courier New', monospace;
            color: #6366f1;
            font-size: 10px;
            white-space: nowrap;
        }
        .cell-empty { background: #fafafa; }

        .grand-total {
            margin-top: 12px;
            padding: 10px 14px;
            text-align: center;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #b91c1c;
        }
        .grand-total strong { color: #dc2626; font-size: 14px; }

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
            <div class="doc-title">Customer Return IMEI</div>
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
            <?php if ($returnNo !== ''): ?>
            <span class="filter-tag">Return: <?= htmlspecialchars($returnNo) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="empty-msg">No return IMEI records found for the selected filters.</div>
    <?php else: ?>

    <div class="summary-row">
        <div class="sbox">
            <div class="sbox-label">Total IMEIs</div>
            <div class="sbox-value"><?= (int) $totalImei ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Returns</div>
            <div class="sbox-value"><?= (int) $totalReturns ?></div>
        </div>
        <div class="sbox">
            <div class="sbox-label">Total Items</div>
            <div class="sbox-value"><?= (int) array_sum(array_map(fn($inv) => count($inv['items']), $grouped)) ?></div>
        </div>
    </div>

    <?php $globalNum = 1; ?>
    <?php foreach ($grouped as $ret): ?>
    <div class="invoice-block">
        <div class="inv-bar">
            <span>Return Number: <?= htmlspecialchars($ret['return_no']) ?></span>
            <span class="inv-date"><?= date('d M Y', strtotime($ret['date'])) ?></span>
            <span class="inv-qty"><?= (int) $ret['count'] ?> unit<?= $ret['count'] === 1 ? '' : 's' ?></span>
        </div>

        <?php foreach ($ret['items'] as $item): ?>
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
                    'orig'  => $r['original_invoice'] ?? '',
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
                        <th class="col-orig">Orig. Inv</th>
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
                        <td class="col-orig"><?= $chunk[$s]['orig'] !== '' ? htmlspecialchars($chunk[$s]['orig']) : '—' ?></td>
                        <?php else: ?>
                        <td class="col-num cell-empty"></td>
                        <td class="col-imei cell-empty"></td>
                        <?php if ($hasImei2): ?><td class="col-imei2 cell-empty"></td><?php endif; ?>
                        <td class="col-orig cell-empty"></td>
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
        across <strong><?= (int) $totalReturns ?></strong> return<?= $totalReturns === 1 ? '' : 's' ?>
    </div>

    <?php endif; ?>

    <div class="footer">
        <span><?= htmlspecialchars(APP_NAME) ?> — Customer Return IMEI Report</span>
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
