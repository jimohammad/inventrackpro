<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement — Error</title>
    <style>
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f1f5f9; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .box { background:#fff; border-radius:14px; padding:40px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.08); max-width:400px; }
        .box i { font-size:3rem; color:#ef4444; margin-bottom:16px; display:block; }
        .box h2 { font-size:1.2rem; margin-bottom:8px; color:#1e293b; }
        .box p { color:#64748b; font-size:0.88rem; }
    </style>
</head>
<body>
    <div class="box">
        <i>⚠️</i>
        <h2><?= htmlspecialchars($msg) ?></h2>
        <p>Please contact the business for a valid statement link.</p>
    </div>
</body>
</html>
