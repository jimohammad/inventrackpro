<?php
/**
 * Barcode cards for one sales invoice.
 * Expects $groups from ReportController::groupInvoiceImeiBarcodeRows().
 */
$barcodeNum = 1;
foreach ($groups as $item):
    $sub = trim((string) ($item['brand'] ?? '') . ' ' . (string) ($item['model'] ?? ''));
?>
<div class="bc-item">
    <div class="bc-item-name">
        <?= htmlspecialchars((string) $item['item_name']) ?>
        <?php if ($sub !== ''): ?>
        <span class="bc-item-sub"><?= htmlspecialchars($sub) ?></span>
        <?php endif; ?>
        <span class="bc-item-count"><?= count($item['imeis']) ?> pcs</span>
    </div>
    <div class="bc-grid">
        <?php foreach ($item['imeis'] as $row):
            $imei = (string) ($row['imei'] ?? '');
            $imei2 = trim((string) ($row['imei2'] ?? ''));
            $svg = Code128::svg($imei);
            $svg2 = $imei2 !== '' ? Code128::svg($imei2) : null;
        ?>
        <div class="bc-card">
            <div class="bc-num"><?= (int) $barcodeNum++ ?></div>
            <?php if ($svg !== null): ?>
            <div class="bc-svg"><?= $svg ?></div>
            <?php endif; ?>
            <div class="bc-code"><?= htmlspecialchars($imei) ?></div>
            <?php if ($imei2 !== ''): ?>
            <div class="bc-code-label">IMEI 2</div>
            <?php if ($svg2 !== null): ?>
            <div class="bc-svg bc-svg-2"><?= $svg2 ?></div>
            <?php endif; ?>
            <div class="bc-code bc-code-2"><?= htmlspecialchars($imei2) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
