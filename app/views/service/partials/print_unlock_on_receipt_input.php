<?php
/**
 * Opt-in: show screen PIN / pattern on thermal receipt.
 *
 * @var bool|int|string|null $printUnlockOnReceipt  Existing value (0/1)
 */
$printUnlockChecked = !empty($printUnlockOnReceipt);
?>
<style>
/* Prevent .svc-f input{width:100%} from breaking checkbox layout */
.svc-print-unlock-wrap input[type="checkbox"]{
    width: auto !important;
    padding: 0 !important;
    border-radius: 4px;
    border: 1.5px solid var(--border-color);
    box-shadow: none !important;
}
.svc-print-unlock-wrap .svc-check-label{
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    line-height: 1.4;
    margin-bottom: 0; /* override .svc-f label margin */
    color: var(--text-muted);
}
.svc-print-unlock-wrap .svc-check-text{
    color: var(--text-main);
    font-weight: 700;
}
</style>
<div class="svc-f svc-print-unlock-wrap" style="margin-top:4px;">
    <label class="svc-check-label">
        <input
            type="checkbox"
            name="print_unlock_on_receipt"
            value="1"
            <?= $printUnlockChecked ? 'checked' : '' ?>
            style="margin-top:3px;flex-shrink:0;"
        >
        <span class="svc-check-text">Print screen PIN / lock pattern on receipt</span>
    </label>
    <div class="svc-pattern-hint" style="font-size:.72rem;color:var(--text-muted);margin-top:8px;line-height:1.4;">
        Optional. When enabled, unlock details appear on the thermal receipt for device return. Leave unchecked if the customer does not want them on paper.
    </div>
</div>
