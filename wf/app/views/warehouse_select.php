<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Warehouse | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .wh-wrap { width: 100%; max-width: 560px; padding: 1rem; }
        .wh-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .wh-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #6366f1;
        }
        .wh-logo span { color: #e2e8f0; }
        .wh-subtitle {
            color: #64748b;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        .wh-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            cursor: pointer;
            transition: all 0.18s;
            text-decoration: none;
            margin-bottom: 0.75rem;
            width: 100%;
        }
        .wh-card:hover {
            border-color: #6366f1;
            background: rgba(99,102,241,0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99,102,241,0.2);
        }
        .wh-icon {
            width: 52px; height: 52px;
            background: rgba(99,102,241,0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            color: #818cf8;
            flex-shrink: 0;
        }
        .wh-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #e2e8f0;
        }
        .wh-location {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 2px;
        }
        .wh-arrow {
            margin-left: auto;
            color: #475569;
            font-size: 1.1rem;
        }
        .wh-user {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1rem;
            background: #1e293b;
            border-radius: 10px;
            border: 1px solid #334155;
        }
        .wh-user-name { color: #e2e8f0; font-weight: 600; }
        .wh-user-role { color: #64748b; font-size: 0.8rem; }
        .logout-link {
            display: block;
            text-align: center;
            color: #475569;
            font-size: 0.8rem;
            margin-top: 1.5rem;
            text-decoration: none;
        }
        .logout-link:hover { color: #ef4444; }
    </style>
</head>
<body>
<div class="wh-wrap">
    <div class="wh-header">
        <div class="wh-logo"><i class="bi bi-boxes"></i> Inven<span>Track</span></div>
        <p class="wh-subtitle">Select your warehouse to continue</p>
    </div>

    <div class="wh-user">
        <div class="wh-user-name"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars(Auth::name()) ?></div>
        <div class="wh-user-role"><?= ucfirst(Auth::role()) ?></div>
    </div>

    <?php if (empty($warehouses)): ?>
    <div style="text-align:center;color:#64748b;padding:2rem;">
        <i class="bi bi-exclamation-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
        No active warehouses found. Ask your admin to set one up.
    </div>
    <?php else: ?>
    <form method="POST" action="?page=warehouse&action=select" id="whForm">
        <input type="hidden" name="warehouse_id" id="whIdInput" value="">
        <?php foreach ($warehouses as $wh): ?>
        <button type="button" class="wh-card" onclick="pickWarehouse(<?= $wh['id'] ?>)">
            <div class="wh-icon"><i class="bi bi-building"></i></div>
            <div>
                <div class="wh-name"><?= htmlspecialchars($wh['name']) ?></div>
                <?php if (!empty($wh['location'])): ?>
                <div class="wh-location"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($wh['location']) ?></div>
                <?php endif; ?>
            </div>
            <i class="bi bi-arrow-right-circle wh-arrow"></i>
        </button>
        <?php endforeach; ?>
    </form>
    <?php endif; ?>

    <a href="?page=logout" class="logout-link"><i class="bi bi-box-arrow-left me-1"></i>Sign out</a>
</div>

<script>
function pickWarehouse(id) {
    document.getElementById('whIdInput').value = id;
    document.getElementById('whForm').submit();
}
</script>
</body>
</html>
