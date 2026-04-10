<!-- Customer Statement -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Party Statement</h1>
        <p class="page-subtitle">Full transaction history per party (unified account)</p>
    </div>
    <?php if ($party && !empty($transactions)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('partyStmtTable','Party_Statement')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <a href="?page=reports&action=partyPrint&party_id=<?= $partyId ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</a>
    </div>
    <?php endif; ?>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="party">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Party</label>
                <select name="party_id" class="form-select" required>
                    <option value="">-- Select Party --</option>
                    <?php foreach ($parties as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $partyId == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                        <span style="color:#94a3b8;">(<?= ucfirst($p['type']) ?>)</span>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($party): ?>


<!-- Transactions Table -->
<?php if (!empty($transactions)): ?>
<div class="card">
    <div class="card-body p-0">
        <table id="partyStmtTable" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
            <thead>
                <tr style="background:#1e3a5f;">
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Date</th>
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Ref No</th>
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;">Type</th>
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;text-align:right;">Debit</th>
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;text-align:right;">Credit</th>
                    <th style="color:#fff;padding:10px 14px;border:none;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;text-align:right;">Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php if (abs($openingBal) > 0.001): ?>
            <tr style="background:#f0f9ff;border-bottom:2px solid #bae6fd;">
                <td style="padding:9px 14px;color:var(--text-muted);font-weight:600;"><?= date('d M Y', strtotime($fromDate)) ?></td>
                <td style="padding:9px 14px;font-weight:600;">—</td>
                <td style="padding:9px 14px;">
                    <span style="background:#0ea5e91a;color:#0ea5e9;border-radius:5px;padding:2px 8px;font-size:0.72rem;font-weight:700;text-transform:uppercase;">
                        Opening Bal
                    </span>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:600;color:#ef4444;">
                    <?= $openingBal > 0 ? APP_CURRENCY . ' ' . number_format($openingBal, DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:600;color:#10b981;">
                    <?= $openingBal < 0 ? APP_CURRENCY . ' ' . number_format(abs($openingBal), DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:700;color:<?= $openingBal > 0 ? '#f59e0b' : '#10b981' ?>;">
                    <?= APP_CURRENCY ?> <?= number_format(abs($openingBal), DECIMAL_PLACES) ?>
                    <?= $openingBal > 0 ? '<small style="font-size:0.68rem;opacity:0.75;">DR</small>' : '<small style="font-size:0.68rem;opacity:0.75;">CR</small>' ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($transactions as $i => $t):
                $typeColors = [
                    'sale'     => '#6366f1',
                    'purchase' => '#f59e0b',
                    'payment'  => '#10b981',
                    'return'   => '#dc2626',
                ];
                $typeLabels = [
                    'sale'     => 'Sale',
                    'purchase' => 'Purchase',
                    'payment'  => 'Payment',
                    'return'   => 'Return',
                ];
                $typeColor = $typeColors[$t['txn_type']] ?? '#94a3b8';
                $typeLabel = $typeLabels[$t['txn_type']] ?? ucfirst($t['txn_type']);

                $balColor = $t['running_balance'] > 0 ? '#f59e0b' : '#10b981';
            ?>
            <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#fafbff' ?>;border-bottom:1px solid #f0f3f8;">
                <td style="padding:9px 14px;color:var(--text-muted);"><?= date('d M Y', strtotime($t['date'])) ?></td>
                <td style="padding:9px 14px;">
                    <?php if ($t['txn_type'] === 'sale'): ?>
                    <a href="?page=sales&action=detail&id=<?= $t['id'] ?>" style="color:#6366f1;text-decoration:none;font-weight:600;"><?= $t['ref_no'] ?></a>
                    <?php elseif ($t['txn_type'] === 'purchase'): ?>
                    <a href="?page=purchases&action=detail&id=<?= $t['id'] ?>" style="color:#f59e0b;text-decoration:none;font-weight:600;"><?= $t['ref_no'] ?></a>
                    <?php elseif ($t['txn_type'] === 'return'): ?>
                    <span style="color:#dc2626;font-weight:600;"><?= $t['ref_no'] ?></span>
                    <?php else: ?>
                    <span style="color:#10b981;font-weight:600;"><?= $t['ref_no'] ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding:9px 14px;">
                    <span style="background:<?= $typeColor ?>1a;color:<?= $typeColor ?>;border-radius:5px;padding:2px 8px;font-size:0.72rem;font-weight:700;text-transform:uppercase;">
                        <?= $typeLabel ?>
                    </span>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:600;color:#ef4444;">
                    <?= $t['debit'] > 0 ? APP_CURRENCY . ' ' . number_format($t['debit'], DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:600;color:#10b981;">
                    <?= $t['credit'] > 0 ? APP_CURRENCY . ' ' . number_format($t['credit'], DECIMAL_PLACES) : '—' ?>
                </td>
                <td style="padding:9px 14px;text-align:right;font-weight:700;color:<?= $balColor ?>;">
                    <?= APP_CURRENCY ?> <?= number_format(abs($t['running_balance']), DECIMAL_PLACES) ?>
                    <?= $t['running_balance'] > 0 ? '<small style="font-size:0.68rem;opacity:0.75;">DR</small>' : '<small style="font-size:0.68rem;opacity:0.75;">CR</small>' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8f9ff;border-top:2px solid #e0e7ff;">
                    <td colspan="3" style="padding:10px 14px;font-weight:700;font-size:0.85rem;">Closing Balance</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#ef4444;">
                    <?php
                    $debitRows  = array_filter($transactions, function($t) { return $t['debit'] > 0; });
                    $totalDebit = array_sum(array_column(array_values($debitRows), 'debit'));
                    echo APP_CURRENCY . ' ' . number_format($totalDebit, DECIMAL_PLACES);
                    ?>
                    </td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:#10b981;">
                        <?= APP_CURRENCY ?> <?= number_format(array_sum(array_column($transactions, 'credit')), DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:10px 14px;text-align:right;font-weight:700;color:<?= $summary['balance'] > 0 ? '#f59e0b' : '#10b981' ?>;">
                        <?= APP_CURRENCY ?> <?= number_format(abs($summary['balance']), DECIMAL_PLACES) ?>
                        <?= $summary['balance'] > 0 ? '<small style="font-size:0.7rem;"> DR</small>' : '<small style="font-size:0.7rem;"> CR</small>' ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-file-earmark-text" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        No transactions found for <strong><?= htmlspecialchars($party['name']) ?></strong> in this date range.
    </div>
</div>
<?php endif; ?>

<?php elseif ($partyId === 0): ?>
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-person-lines-fill" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        Select a party above and click <strong>View</strong> to generate the statement.
    </div>
</div>
<?php endif; ?>
<script>$(document).ready(function(){
    if($('#partyStmtTable tbody tr').length){
        $('#partyStmtTable').DataTable({ pageLength:100, paging:false, order:[], language:{search:'',searchPlaceholder:'Search...'}, pageLength:100, paging:false, order:[] });
    }
});</script>
