<?php
/**
 * Device brand dropdown (shared by service create/edit).
 *
 * @var string $deviceBrandValue Current or default brand (plain text).
 */
$value = trim((string) ($deviceBrandValue ?? ''));
$opts  = ServiceController::deviceBrandOptions();
?>
<select name="device_brand" id="deviceBrandSelect">
    <option value="">— Select brand —</option>
    <?php if ($value !== '' && !in_array($value, $opts, true)): ?>
    <option value="<?= htmlspecialchars($value) ?>" selected><?= htmlspecialchars($value) ?></option>
    <?php endif; ?>
    <?php foreach ($opts as $b): ?>
    <option value="<?= htmlspecialchars($b) ?>" <?= $value === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
    <?php endforeach; ?>
</select>
