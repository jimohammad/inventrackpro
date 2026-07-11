<?php
$partnerNavActive = $partnerNavActive ?? 'due';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="?page=landedcost&action=partnerDue"
       class="btn btn-sm <?= $partnerNavActive === 'due' ? 'btn-warning' : 'btn-outline-warning' ?>">
        <i class="bi bi-exclamation-circle me-1"></i> Unpaid due
    </a>
    <a href="?page=landedcost&action=partnerHistory"
       class="btn btn-sm <?= $partnerNavActive === 'history' ? 'btn-success' : 'btn-outline-success' ?>">
        <i class="bi bi-clock-history me-1"></i> Paid history
    </a>
    <a href="?page=reports&action=partnerProfit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-bar-graph me-1"></i> Report
    </a>
</div>
