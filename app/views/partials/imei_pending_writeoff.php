<?php
/** @var array $pendingWriteOff */
/** @var array $item */
$stockQty    = (int) ($pendingWriteOff['stock_qty'] ?? 0);
$imeiCount   = (int) ($pendingWriteOff['imei_count'] ?? 0);
$bookQty     = (int) ($pendingWriteOff['book_qty'] ?? 0);
$writeOffQty = (int) ($pendingWriteOff['write_off_qty'] ?? 0);
$itemId      = (int) ($item['id'] ?? 0);
$itemName    = (string) ($item['name'] ?? 'this item');
?>
<div class="card mb-3" style="border:1.5px solid #fde68a;background:#fffbeb;">
    <div class="card-body py-3">
        <div class="fw-bold mb-1" style="color:#92400e;">
            <i class="bi bi-exclamation-circle me-1"></i>
            IMEI pending: <?= $imeiCount ?> serial<?= $imeiCount === 1 ? '' : 's' ?> vs qty <?= $stockQty ?>
            (<?= max(0, $stockQty - $imeiCount) ?> not scanned)
        </div>
        <div class="small" style="color:#78350f;">
            Book qty from purchases / sales / returns is <strong><?= $bookQty ?></strong>.
            If those extra units are physically missing, write them off so stock matches the
            <strong><?= $imeiCount ?></strong> scanned IMEI<?= $imeiCount === 1 ? '' : 's' ?> on the shelf.
            If the supplier still owes the phones, use a <strong>purchase return</strong> instead.
        </div>
        <?php if ($writeOffQty > 0): ?>
        <form method="POST" action="?page=imei&action=auditWriteOffPending" class="mt-3"
              onsubmit="return confirm('Write off <?= $writeOffQty ?> missing unit(s) of <?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?>?\n\nStock qty will change from <?= $stockQty ?> to <?= $imeiCount ?>. Rebuild stock will not put them back.');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <label class="small d-block mb-1" style="color:#78350f;" for="writeOffNotes">Note (optional)</label>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <input type="text" name="notes" id="writeOffNotes" class="form-control form-control-sm"
                       maxlength="500" placeholder="e.g. 2 pcs missing from shelf"
                       style="max-width:280px;">
                <button type="submit" class="btn btn-sm btn-warning fw-bold">
                    Write off <?= $writeOffQty ?> missing unit<?= $writeOffQty === 1 ? '' : 's' ?>
                </button>
            </div>
        </form>
        <?php elseif ($stockQty > $imeiCount): ?>
        <form method="POST" action="?page=imei&action=auditWriteOffPending" class="mt-3"
              onsubmit="return confirm('Rebuild stock qty from <?= $stockQty ?> to <?= $imeiCount ?> to match scanned IMEIs?');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <button type="submit" class="btn btn-sm btn-outline-warning fw-bold">
                Rebuild qty to <?= $imeiCount ?> (matches IMEIs)
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
