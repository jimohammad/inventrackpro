<?php
/** Report statement notices: validation errors. */
?>
<?php if (!empty($reportError)): ?>
<div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-start gap-2" role="alert">
    <i class="bi bi-exclamation-octagon-fill flex-shrink-0"></i>
    <span class="small mb-0"><?= htmlspecialchars((string) $reportError) ?></span>
</div>
<?php endif; ?>
