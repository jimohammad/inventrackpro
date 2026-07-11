<?php
/**
 * Numeric screen PIN input (alternative to pattern lock).
 *
 * @var string $screenPinValue  Existing PIN or ''
 */
require_once __DIR__ . '/../../../helpers/ServiceLockPattern.php';

$screenPinValue = method_exists(ServiceLockPattern::class, 'parseScreenPin')
    ? (ServiceLockPattern::parseScreenPin($screenPinValue ?? '') ?? '')
    : trim((string) ($screenPinValue ?? ''));
?>
<div class="svc-f svc-screen-pin-wrap">
    <label>Screen PIN code <span style="font-weight:500;text-transform:none;letter-spacing:0;">(optional — numeric)</span></label>
    <div class="svc-pattern-hint" style="font-size:.72rem;color:var(--text-muted);margin-bottom:8px;line-height:1.4;">
        4–16 digits if the customer uses a PIN instead of a pattern. Stored for staff; printed only if you enable it below.
    </div>
    <input
        type="text"
        name="screen_pin"
        id="svcScreenPinInput"
        value="<?= htmlspecialchars($screenPinValue) ?>"
        inputmode="numeric"
        pattern="\d{4,16}"
        maxlength="16"
        autocomplete="off"
        placeholder="e.g. 1234"
        class="svc-screen-pin-field"
        style="font-family:monospace;font-size:1.05rem;letter-spacing:2px;"
    >
</div>
