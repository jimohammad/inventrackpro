<!-- Transfers List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Stock Transfers</h1><p class="page-subtitle">Move stock between warehouses</p></div>
    <?php if (Auth::can('inventory','add')): ?>
    <a href="?page=transfers&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Transfer</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0" id="transfersTable">
            <thead>
                <tr><th>Transfer No</th><th>Date</th><th>From</th><th>To</th><th>Status</th><th>By</th></tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No transfers found</td></tr>
                <?php else: ?>
                <?php foreach ($transfers as $t): ?>
                <tr>
                    <td class="fw-semibold" style="color:var(--primary);"><?= $t['transfer_no'] ?></td>
                    <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                    <td><?= htmlspecialchars($t['from_warehouse']) ?></td>
                    <td><?= htmlspecialchars($t['to_warehouse']) ?></td>
                    <td><span class="badge badge-<?= $t['status']==='completed'?'paid':'pending' ?>" style="border-radius:5px;"><?= ucfirst($t['status']) ?></span></td>
                    <td><small class="text-muted"><?= htmlspecialchars($t['created_by_name'] ?? '—') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>$(document).ready(() => { $('#transfersTable').DataTable({ pageLength:25, order:[[1,'desc']] }); });</script>
