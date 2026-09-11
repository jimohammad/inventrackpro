<?php
/**
 * Shared Bank KYC / Manpower certification block with company stamp.
 * Authorized signature image is optional (`$kycShowSignature`, default true).
 *
 * Expected vars:
 *   $companyName (string)
 *   $certBodyHtml (string) — already-escaped HTML paragraphs for the certification text
 *   Optional: $preparedBy (string)
 *   Optional: $kycShowSignature (bool) — false skips the computer-generated signature image
 *   Optional: $kycSignatoryLabel (string) — defaults to "Authorized signatory"
 *   Optional: $kycShowSignerName (bool) — false hides $preparedBy under the sign line
 *
 * Images are embedded as data-URIs so print/PDF never depends on relative URL resolution.
 */
$kycRoot = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'kyc' . DIRECTORY_SEPARATOR;
$kycSigPath   = $kycRoot . 'authorized_signature.png';
$kycStampPath = $kycRoot . 'company_stamp.png';
$kycShowSignature = ($kycShowSignature ?? true) !== false;
$kycSignatoryLabel = trim((string) ($kycSignatoryLabel ?? 'Authorized signatory'));
if ($kycSignatoryLabel === '') {
    $kycSignatoryLabel = 'Authorized signatory';
}
$kycShowSignerName = ($kycShowSignerName ?? true) !== false;

$kycDataUri = static function (string $path): ?string {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $bin = @file_get_contents($path);
    if ($bin === false || $bin === '') {
        return null;
    }
    return 'data:image/png;base64,' . base64_encode($bin);
};

$kycSigSrc   = $kycShowSignature ? $kycDataUri($kycSigPath) : null;
$kycStampSrc = $kycDataUri($kycStampPath);
$signerName  = trim((string) ($preparedBy ?? ''));
?>
<div class="declaration">
    <h3>Certification</h3>
    <?= $certBodyHtml ?>
    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-media<?= $kycSigSrc === null ? ' sign-media-empty' : '' ?>">
                <?php if ($kycSigSrc !== null): ?>
                <img class="sig-img" src="<?= htmlspecialchars($kycSigSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Authorized signature" width="210" height="90">
                <?php endif; ?>
            </div>
            <div class="sign-line">
                <?= htmlspecialchars($kycSignatoryLabel) ?>
                <?php if ($kycShowSignerName && $signerName !== ''): ?>
                <span class="sign-name"><?= htmlspecialchars($signerName) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="sign-box sign-box-stamp">
            <div class="sign-media stamp-media">
                <?php if ($kycStampSrc !== null): ?>
                <img class="stamp-img" src="<?= htmlspecialchars($kycStampSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company stamp" width="110" height="110">
                <?php endif; ?>
            </div>
            <div class="sign-line">
                Company stamp
                <span class="sign-name"><?= htmlspecialchars((string) $companyName) ?></span>
            </div>
        </div>
    </div>
</div>
