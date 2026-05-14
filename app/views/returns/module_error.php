<?php
/** Standalone full-page error for returns module (no layout). */
$home = defined('APP_URL') ? rtrim((string) APP_URL, '/') . '/?page=returns' : '/?page=returns';
$new  = defined('APP_URL') ? rtrim((string) APP_URL, '/') . '/?page=returns&action=create' : '/?page=returns&action=create';
$errTitle = isset($returnsErrorTitle) ? (string) $returnsErrorTitle : 'Returns could not load';
$errMsg   = isset($returnsErrorMsg) ? (string) $returnsErrorMsg : 'Something went wrong while loading this page. Your work was not saved here. Use the links below to continue with returns.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($errTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:#0f172a; color:#e2e8f0; font-family:system-ui,-apple-system,'Segoe UI',sans-serif; }
        .box { max-width:26rem; padding:1.75rem; text-align:center; }
        h1 { font-size:1.25rem; color:#f87171; margin:0 0 0.75rem; font-weight:700; }
        p { color:#94a3b8; margin:0 0 1.25rem; line-height:1.55; font-size:0.95rem; }
        .actions { display:flex; flex-direction:column; gap:0.6rem; align-items:center; }
        a { color:#818cf8; text-decoration:none; font-weight:600; padding:0.5rem 1rem; border-radius:8px;
            border:1px solid rgba(129,140,248,0.35); display:inline-block; }
        a:hover { background:rgba(129,140,248,0.12); }
        a.primary { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; border:none; }
    </style>
</head>
<body>
<div class="box">
    <h1><?= htmlspecialchars($errTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="actions">
        <a class="primary" href="<?= htmlspecialchars($new, ENT_QUOTES, 'UTF-8') ?>">New return</a>
        <a href="<?= htmlspecialchars($home, ENT_QUOTES, 'UTF-8') ?>">Returns list</a>
    </div>
</div>
</body>
</html>
