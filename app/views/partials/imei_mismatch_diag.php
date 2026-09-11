<?php
/** @var array $diag */
$detail = (string) ($diag['likely_detail'] ?? '');
$warranty = $diag['warranty_no_imei'] ?? [];
$sales = $diag['unscanned_sales'] ?? [];
$stale = $diag['stale_available'] ?? [];
$dups = $diag['duplicate_imeis'] ?? [];
?>
<div class="card mb-3" style="border:1.5px solid #fecaca;background:#fef2f2;">
    <div class="card-body py-3">
        <div class="fw-bold text-danger mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i>
            IMEI over: <?= (int) ($diag['imei_available'] ?? 0) ?> serials vs qty <?= (int) ($diag['stock_qty'] ?? 0) ?>
            (<?= (int) ($diag['over'] ?? 0) ?> extra)
        </div>
        <div class="small" style="color:#7f1d1d;"><?= htmlspecialchars($detail) ?></div>
        <div class="small text-muted mt-1">Document rebuild qty (excludes warranty): <?= (int) ($diag['rebuild_qty'] ?? 0) ?></div>

        <?php if ($warranty !== []): ?>
        <div class="small mt-2">
            <strong>Warranty without replacement IMEI</strong>
            <?php foreach ($warranty as $wr): ?>
            — <a href="?page=warranty&action=view&id=<?= (int) ($wr['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($wr['replacement_no'] ?? '')) ?></a>
            (<?= htmlspecialchars((string) ($wr['date'] ?? '')) ?><?= !empty($wr['customer_name']) ? ', ' . htmlspecialchars((string) $wr['customer_name']) : '' ?>)
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($stale !== []): ?>
        <div class="small mt-2">
            <strong>Still available but sold on invoice</strong>
            <?php foreach (array_slice($stale, 0, 8) as $row): ?>
            — <code><?= htmlspecialchars((string) ($row['imei'] ?? '')) ?></code>
            <a href="?page=sales&action=view&id=<?= (int) ($row['sale_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($row['invoice_no'] ?? '')) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($sales !== []): ?>
        <div class="small mt-2">
            <strong>Sales missing IMEI scans</strong>
            <?php foreach (array_slice($sales, 0, 8) as $row): ?>
            — <a href="?page=sales&action=view&id=<?= (int) ($row['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($row['invoice_no'] ?? '')) ?></a>
            (<?= (int) ($row['linked'] ?? 0) ?>/<?= (int) ($row['quantity'] ?? 0) ?>)
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($dups !== []): ?>
        <div class="small mt-2">
            <strong>Duplicate available IMEIs</strong>
            <?php foreach ($dups as $row): ?>
            — <code><?= htmlspecialchars((string) ($row['imei'] ?? '')) ?></code> ×<?= (int) ($row['c'] ?? 0) ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
