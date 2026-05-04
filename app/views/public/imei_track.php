<?php
/** @var string $imei */
/** @var string|null $saleDate */
/** @var string|null $remainingText */
/** @var string|null $error */
/** @var bool $isExpired */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMEI Warranty Track</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fafc; }
        .track-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 18px; }
        .track-card { width: 100%; max-width: 620px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: 0 14px 30px rgba(15,23,42,0.06); }
        .track-head { padding: 18px 20px 12px; border-bottom: 1px solid #eef2f7; }
        .track-body { padding: 16px 20px 20px; }
        .result-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; background: #f8fafc; }
        .result-row { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; }
        .result-label { font-size: 0.78rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 700; }
        .result-value { font-size: 0.98rem; font-weight: 700; color: #0f172a; text-align: right; }
    </style>
</head>
<body>
<div class="track-wrap">
    <div class="track-card">
        <div class="track-head">
            <h5 class="mb-1"><i class="bi bi-shield-check text-primary me-2"></i>IMEI Warranty Tracking</h5>
            <div class="text-muted" style="font-size:0.88rem;">Warranty policy: 13 months from selling date</div>
        </div>
        <div class="track-body">
            <form method="GET" action="">
                <input type="hidden" name="page" value="imeitrack">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        name="imei"
                        value="<?= htmlspecialchars($imei ?? '') ?>"
                        placeholder="Enter IMEI / Serial Number"
                        autocomplete="off"
                        required
                    >
                    <button class="btn btn-primary" type="submit">Check</button>
                </div>
            </form>

            <?php if (!empty($error)): ?>
                <div class="alert alert-warning mb-0"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($saleDate) && !empty($remainingText)): ?>
                <div class="result-box">
                    <div class="result-row">
                        <div class="result-label">Date of Selling</div>
                        <div class="result-value"><?= htmlspecialchars(date('d M Y', strtotime($saleDate))) ?></div>
                    </div>
                    <div class="result-row" style="border-top:1px dashed #dbeafe;">
                        <div class="result-label">Remaining Warranty</div>
                        <div class="result-value" style="color:<?= !empty($isExpired) ? '#dc2626' : '#16a34a' ?>;">
                            <?= htmlspecialchars($remainingText) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
