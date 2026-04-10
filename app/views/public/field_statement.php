<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement — <?= htmlspecialchars($party['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f1f5f9; color:#1e293b; min-height:100vh; }
        .container { max-width:800px; margin:0 auto; padding:20px 16px; }
        .header { background:linear-gradient(135deg,#1e3a5f,#2d5a9e); border-radius:14px; padding:24px; margin-bottom:20px; color:#fff; text-align:center; }
        .header h1 { font-size:1.4rem; font-weight:800; margin-bottom:4px; }
        .header .company { font-size:0.82rem; opacity:0.8; }
        .header .phone { font-size:0.78rem; opacity:0.6; margin-top:2px; }
        .summary { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .sum-card { flex:1; min-width:140px; background:#fff; border-radius:10px; padding:14px; border:1px solid #e2e8f0; }
        .sum-card .label { font-size:0.7rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:4px; }
        .sum-card .value { font-size:1.1rem; font-weight:800; }
        .stmt-table { width:100%; background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden; }
        .stmt-table th { font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.3px; color:#64748b; background:#f8fafc; padding:10px 12px; border-bottom:2px solid #e2e8f0; }
        .stmt-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; font-size:0.82rem; }
        .stmt-table tr:last-child td { border-bottom:none; }
        .stmt-table tr:hover td { background:#f8faff; }
        .stmt-table tfoot td { background:#f0f4ff; font-weight:700; border-top:2px solid #c7d2fe; }
        .badge { display:inline-block; padding:2px 8px; border-radius:5px; font-size:0.7rem; font-weight:600; }
        .badge-sale { background:rgba(99,102,241,0.12); color:#6366f1; }
        .badge-payment { background:rgba(16,185,129,0.12); color:#10b981; }
        .badge-return { background:rgba(220,38,38,0.12); color:#dc2626; }
        .footer { text-align:center; margin-top:20px; font-size:0.75rem; color:#94a3b8; }
        .print-btn { display:inline-flex; align-items:center; gap:6px; background:#1e3a5f; color:#fff; border:none; padding:8px 20px; border-radius:8px; cursor:pointer; font-size:0.82rem; font-weight:600; margin-bottom:16px; }
        .print-btn:hover { background:#2d5a9e; }
        @media print {
            body { background:#fff; }
            .container { padding:0; max-width:100%; }
            .print-btn, .footer { display:none; }
            .header { border-radius:0; }
            .stmt-table { border:1px solid #ccc; }
        }
        @media (max-width:600px) {
            .summary { flex-direction:column; }
            .stmt-table th, .stmt-table td { padding:6px 8px; font-size:0.75rem; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="company"><?= htmlspecialchars($companyName) ?></div>
        <?php if ($companyPhoneVal): ?>
        <div class="phone"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($companyPhoneVal) ?></div>
        <?php endif; ?>
        <h1 style="margin-top:10px;"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($party['name']) ?></h1>
        <div style="font-size:0.78rem;opacity:0.7;margin-top:4px;">
            Account Statement as of <?= date('d M Y, h:i A') ?>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()"><i class="bi bi-printer"></i> Print Statement</button>

    <!-- Summary Cards -->
    <div class="summary">
        <div class="sum-card">
            <div class="label">Opening Balance</div>
            <div class="value" style="color:#64748b;"><?= APP_CURRENCY ?> <?= number_format((float)$party['opening_balance'], DECIMAL_PLACES) ?></div>
        </div>
        <div class="sum-card">
            <div class="label">Total Invoices</div>
            <?php $totalDebit = array_sum(array_column($transactions, 'debit')) + (float)$party['opening_balance']; ?>
            <div class="value" style="color:#6366f1;"><?= APP_CURRENCY ?> <?= number_format($totalDebit, DECIMAL_PLACES) ?></div>
        </div>
        <div class="sum-card">
            <div class="label">Total Paid</div>
            <?php $totalCredit = array_sum(array_column($transactions, 'credit')); ?>
            <div class="value" style="color:#10b981;"><?= APP_CURRENCY ?> <?= number_format($totalCredit, DECIMAL_PLACES) ?></div>
        </div>
        <div class="sum-card">
            <div class="label">Balance Due</div>
            <?php $balance = (float)$party['net_balance']; ?>
            <div class="value" style="color:<?= $balance > 0.001 ? '#ef4444' : '#10b981' ?>;">
                <?= $balance > 0.001 ? APP_CURRENCY . ' ' . number_format($balance, DECIMAL_PLACES) : '✓ Clear' ?>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <table class="stmt-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Ref</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Credit</th>
                <th style="text-align:right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $running = (float)$party['opening_balance'];
            if (abs($running) > 0.001):
            ?>
            <tr style="background:#f8fafc;">
                <td>—</td>
                <td><span class="badge" style="background:rgba(100,116,139,0.12);color:#475569;">Opening</span></td>
                <td>—</td>
                <td style="text-align:right;font-weight:600;"><?= $running > 0 ? APP_CURRENCY . ' ' . number_format($running, DECIMAL_PLACES) : '—' ?></td>
                <td style="text-align:right;"><?= $running < 0 ? APP_CURRENCY . ' ' . number_format(abs($running), DECIMAL_PLACES) : '—' ?></td>
                <td style="text-align:right;font-weight:700;"><?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?></td>
            </tr>
            <?php endif; ?>

            <?php foreach ($transactions as $t):
                $debit  = (float)$t['debit'];
                $credit = (float)$t['credit'];
                $running += $debit - $credit;
                $badgeClass = $t['type'] === 'Sale' ? 'badge-sale' : ($t['type'] === 'Payment' ? 'badge-payment' : 'badge-return');
            ?>
            <tr>
                <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $t['type'] ?></span></td>
                <td style="font-weight:600;color:#4338ca;">
                    <?php if ($t['type'] === 'Sale'): ?>
                    <a href="javascript:void(0)" onclick="showInvoice('<?= $t['ref_no'] ?>')" style="color:#4338ca;text-decoration:none;border-bottom:1px dashed #4338ca;">
                        <?= $t['ref_no'] ?> <i class="bi bi-eye" style="font-size:0.7rem;opacity:0.5;"></i>
                    </a>
                    <?php else: ?>
                    <?= $t['ref_no'] ?>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;"><?= $debit > 0 ? APP_CURRENCY . ' ' . number_format($debit, DECIMAL_PLACES) : '—' ?></td>
                <td style="text-align:right;color:#10b981;"><?= $credit > 0 ? APP_CURRENCY . ' ' . number_format($credit, DECIMAL_PLACES) : '—' ?></td>
                <td style="text-align:right;font-weight:700;color:<?= $running > 0.001 ? '#ef4444' : '#10b981' ?>;">
                    <?= APP_CURRENCY ?> <?= number_format(abs($running), DECIMAL_PLACES) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;color:#4338ca;">Closing Balance</td>
                <td></td>
                <td></td>
                <td style="text-align:right;font-size:1rem;color:<?= $running > 0.001 ? '#ef4444' : '#10b981' ?>;">
                    <?= $running > 0.001 ? APP_CURRENCY . ' ' . number_format($running, DECIMAL_PLACES) : '✓ Clear' ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This is a computer-generated statement from <?= htmlspecialchars($companyName) ?>.</p>
        <p>Generated on <?= date('d M Y, h:i A') ?></p>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div id="invModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.5);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(2px);" onclick="if(event.target===this)closeInvModal()">
    <div style="background:#fff;border-radius:14px;width:95%;max-width:550px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;">
            <div>
                <div id="invNo" style="font-size:1.05rem;font-weight:800;color:#4338ca;"></div>
                <div id="invDate" style="font-size:0.78rem;color:#64748b;margin-top:2px;"></div>
            </div>
            <button onclick="closeInvModal()" style="background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1;">×</button>
        </div>
        <div id="invBody" style="padding:16px 20px;">
            <div style="text-align:center;padding:20px;color:#94a3b8;">Loading...</div>
        </div>
    </div>
</div>

<script>
var stmtToken = '<?= htmlspecialchars($token) ?>';

function showInvoice(refNo) {
    document.getElementById('invModal').style.display = 'flex';
    document.getElementById('invNo').textContent = refNo;
    document.getElementById('invDate').textContent = 'Loading...';
    document.getElementById('invBody').innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;">Loading...</div>';

    fetch('?page=fieldstatement&action=invoiceDetail&token=' + stmtToken + '&ref=' + encodeURIComponent(refNo))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                document.getElementById('invBody').innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;">' + data.error + '</div>';
                return;
            }
            var inv = data.invoice;
            var items = data.items;
            document.getElementById('invDate').textContent = inv.date;

            var html = '<table style="width:100%;border-collapse:collapse;font-size:0.82rem;">';
            html += '<thead><tr style="background:#f8fafc;"><th style="padding:8px 10px;text-align:left;font-size:0.7rem;color:#64748b;font-weight:700;">ITEM</th>';
            html += '<th style="padding:8px 10px;text-align:center;font-size:0.7rem;color:#64748b;font-weight:700;">QTY</th>';
            html += '<th style="padding:8px 10px;text-align:right;font-size:0.7rem;color:#64748b;font-weight:700;">PRICE</th>';
            html += '<th style="padding:8px 10px;text-align:right;font-size:0.7rem;color:#64748b;font-weight:700;">TOTAL</th></tr></thead><tbody>';

            items.forEach(function(it) {
                html += '<tr style="border-bottom:1px solid #f1f5f9;">';
                html += '<td style="padding:8px 10px;font-weight:500;">' + it.item_name + '</td>';
                html += '<td style="padding:8px 10px;text-align:center;">' + it.quantity + '</td>';
                html += '<td style="padding:8px 10px;text-align:right;">' + parseFloat(it.unit_price).toFixed(3) + '</td>';
                html += '<td style="padding:8px 10px;text-align:right;font-weight:600;">' + parseFloat(it.total).toFixed(3) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';

            // Totals
            html += '<div style="margin-top:12px;padding-top:12px;border-top:2px solid #e2e8f0;">';
            html += '<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.82rem;color:#64748b;">';
            html += '<span>Subtotal</span><span style="font-weight:600;"><?= APP_CURRENCY ?> ' + parseFloat(inv.subtotal).toFixed(3) + '</span></div>';

            if (parseFloat(inv.discount) > 0) {
                html += '<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.82rem;color:#64748b;">';
                html += '<span>Discount</span><span style="color:#ef4444;">-<?= APP_CURRENCY ?> ' + parseFloat(inv.discount).toFixed(3) + '</span></div>';
            }

            html += '<div style="display:flex;justify-content:space-between;padding:8px 0;font-size:1rem;font-weight:800;color:#1e293b;border-top:1px solid #e2e8f0;margin-top:4px;">';
            html += '<span>Grand Total</span><span style="color:#4338ca;"><?= APP_CURRENCY ?> ' + parseFloat(inv.grand_total).toFixed(3) + '</span></div>';

            html += '<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.82rem;">';
            html += '<span style="color:#64748b;">Paid</span><span style="color:#10b981;font-weight:600;"><?= APP_CURRENCY ?> ' + parseFloat(inv.paid_amount).toFixed(3) + '</span></div>';

            var bal = parseFloat(inv.balance);
            if (bal > 0.001) {
                html += '<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.82rem;">';
                html += '<span style="color:#64748b;">Balance</span><span style="color:#ef4444;font-weight:700;"><?= APP_CURRENCY ?> ' + bal.toFixed(3) + '</span></div>';
            }

            html += '</div>';
            document.getElementById('invBody').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('invBody').innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;">Failed to load. Try again.</div>';
        });
}

function closeInvModal() {
    document.getElementById('invModal').style.display = 'none';
}
</script>

</body>
</html>
