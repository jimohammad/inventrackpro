<!-- Account Statement Report -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Account Statement</h1>
        <p class="page-subtitle">Full transaction history per account</p>
    </div>
    <?php if ($account && !empty($transactions)): ?>
    <div class="d-flex gap-2">
        <button onclick="exportReportCSV('accountStmtTable','Account_Statement')" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
        <button onclick="exportReportPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
    <?php endif; ?>
</div>

<!-- Filter Card -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="accountStatement">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">Account</label>
                <select name="account_id" class="form-select" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>" <?= $accountId == $acc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['name']) ?> — <?= number_format($acc['current_balance'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars((string) $fromDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-weight:600;font-size:0.82rem;">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars((string) $toDate) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> View
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($account): ?>


<?php if (!empty($transactions)): ?>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">

        <!-- Print Header (hidden on screen) -->
        <div class="print-only" style="padding:20px 24px 10px;border-bottom:2px solid #e2e8f0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h2 style="margin:0;font-size:1.3rem;color:#1e293b;"><?= APP_NAME ?? 'Account Statement' ?></h2>
                    <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Account Statement</p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-weight:700;font-size:1rem;color:#1e293b;"><?= htmlspecialchars($account['name']) ?></p>
                    <p style="margin:2px 0 0;color:#64748b;font-size:0.82rem;"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></p>
                </div>
            </div>
        </div>

        <table id="accountStmtTable" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
            <thead>
                <tr>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Date</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Reference</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Type</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;">Party / Description</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Credit</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Debit</th>
                    <th style="padding:10px 14px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening balance row -->
                <tr style="background:#f8faff;">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-weight:600;color:#1e293b;"><?= date('d M Y', strtotime($fromDate)) ?></td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;" colspan="3">
                        <span style="color:#6366f1;font-weight:600;">Opening Balance</span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;">—</td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;">—</td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:#6366f1;">
                        <?= number_format($openingBalance, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>

                <?php foreach ($transactions as $tx):
                    $credit = (float)$tx['credit'];
                    $debit  = (float)$tx['debit'];

                    // Type badge colors
                    $typeColors = [
                        'Payment In'   => ['bg' => '#d1fae5', 'color' => '#065f46'],
                        'Payment Out'  => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                        'Expense'      => ['bg' => '#fef3c7', 'color' => '#92400e'],
                        'Transfer In'  => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
                        'Transfer Out' => ['bg' => '#f3e8ff', 'color' => '#6b21a8'],
                    ];
                    $tc = $typeColors[$tx['type']] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                ?>
                <tr style="background:#fff;transition:background 0.1s;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background='#fff'">
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#475569;white-space:nowrap;">
                        <?= date('d M Y', strtotime($tx['date'])) ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:#1e293b;white-space:nowrap;">
                        <?= htmlspecialchars($tx['ref'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;">
                        <span style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;padding:2px 8px;border-radius:6px;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                            <?= $tx['type'] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;color:#1e293b;">
                        <?= htmlspecialchars($tx['party_name'] ?? '') ?>
                        <?php if (!empty($tx['notes'])): ?>
                        <br><small style="color:#94a3b8;"><?= htmlspecialchars($tx['notes']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:<?= $credit > 0 ? '#10b981' : '#cbd5e1' ?>;">
                        <?= $credit > 0 ? number_format($credit, DECIMAL_PLACES) : '—' ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:600;color:<?= $debit > 0 ? '#dc2626' : '#cbd5e1' ?>;">
                        <?= $debit > 0 ? number_format($debit, DECIMAL_PLACES) : '—' ?>
                    </td>
                    <td style="padding:9px 14px;border-bottom:1px solid #cbd5e1;text-align:right;font-weight:700;color:<?= $tx['running'] >= 0 ? '#1e293b' : '#dc2626' ?>;">
                        <?= number_format($tx['running'], DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Closing balance row -->
                <tr style="background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                    <td style="padding:11px 14px;font-weight:700;color:#1e293b;" colspan="4">
                        <span style="color:#4338ca;">Closing Balance</span>
                        <span style="color:#94a3b8;font-weight:400;font-size:0.78rem;margin-left:8px;"><?= date('d M Y', strtotime($toDate)) ?></span>
                    </td>
                    <td style="padding:11px 14px;text-align:right;font-weight:700;color:#10b981;">
                        <?= number_format(array_sum(array_column($transactions, 'credit')), DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:11px 14px;text-align:right;font-weight:700;color:#dc2626;">
                        <?= number_format(array_sum(array_column($transactions, 'debit')), DECIMAL_PLACES) ?>
                    </td>
                    <td style="padding:11px 14px;text-align:right;font-size:1rem;font-weight:800;color:<?= $closingBalance >= 0 ? '#6366f1' : '#dc2626' ?>;">
                        <?= number_format($closingBalance, DECIMAL_PLACES) ?> <?= APP_CURRENCY ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-journal-text" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">No transactions found for this period.</p>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-bank" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0" style="color:#94a3b8;">Select an account and date range to view the statement.</p>
    </div>
</div>
<?php endif; ?>

<script>$(document).ready(function(){
    if($('#accountStmtTable tbody tr').length){
        $('#accountStmtTable').DataTable({ pageLength:100, paging:false, order:[], language:{search:'',searchPlaceholder:'Search...'}, pageLength:100, paging:false, order:[] });
    }
});</script>
<style>
@media print {
    .no-print, .sidebar, nav, .topbar { display:none !important; }
    .print-only { display:block !important; }
    body { background:#fff !important; }
    .card { box-shadow:none !important; border:none !important; }
    .stat-card { border:1px solid #e2e8f0 !important; box-shadow:none !important; }
}
.print-only { display:none; }
</style>
