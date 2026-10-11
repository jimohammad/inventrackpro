<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="data:,">
    <title>Login | <?= APP_NAME ?></title>
    <style>
        html { background: #fff; color-scheme: light; }
        body {
            background: #fff;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: "Segoe UI", sans-serif;
        }
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-top: 3px solid #f59e0b;
            border-radius: 0;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
        }
        .login-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fbbf24;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }
        .login-logo span { color: #f8fafc; }
        .login-sub {
            color: #94a3b8;
            text-align: center;
            margin: 0.5rem 0 0;
            font-size: 0.875rem;
        }
        .field { margin-bottom: 1rem; }
        .field label {
            color: #cbd5e1;
            font-size: 0.875rem;
            font-weight: 500;
            display: block;
            margin-bottom: 0.35rem;
        }
        .field-row {
            display: flex;
            align-items: stretch;
        }
        .field-icon {
            display: flex;
            align-items: center;
            padding: 0 0.85rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-right: none;
            border-radius: 0;
            color: #fbbf24;
        }
        .field input {
            flex: 1;
            min-width: 0;
            background: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            border-radius: 0;
            padding: 0.75rem 1rem;
            font: inherit;
            outline: none;
        }
        .field input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
        }
        .field input:-webkit-autofill,
        .field input:-webkit-autofill:hover,
        .field input:-webkit-autofill:focus {
            -webkit-text-fill-color: #f8fafc;
            caret-color: #f8fafc;
            box-shadow: 0 0 0 1000px #0f172a inset;
        }
        .btn-login {
            width: 100%;
            margin-top: 0.5rem;
            background: #f59e0b;
            border: none;
            border-radius: 0;
            padding: 0.75rem;
            color: #1c1917;
            font: inherit;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }
        .btn-login:hover { background: #fbbf24; }
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fecaca;
            border-radius: 0;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .login-foot {
            text-align: center;
            margin: 1.5rem 0 0;
            color: #94a3b8;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M7.752.066a.5.5 0 0 1 .496 0l3.75 2.143a.5.5 0 0 1 .252.434v3.995l3.498 2A.5.5 0 0 1 16 9.07v4.286a.5.5 0 0 1-.252.434l-3.75 2.143a.5.5 0 0 1-.496 0l-3.502-2-3.502 2.001a.5.5 0 0 1-.496 0l-3.75-2.143A.5.5 0 0 1 0 13.357V9.071a.5.5 0 0 1 .252-.434L3.75 6.638V2.643a.5.5 0 0 1 .252-.434zM4.25 7.504 1.508 9.071l2.742 1.567 2.742-1.567zM7.5 9.933l-2.75 1.571v3.134l2.75-1.571zm1 3.134 2.75 1.571v-3.134L8.5 9.933zm.508-3.996 2.742 1.567 2.742-1.567-2.742-1.567zm2.242-2.433V3.504L8.5 5.076V8.21zM7.5 8.21V5.076L4.75 3.504v3.134zM5.258 2.643 8 4.21l2.742-1.567L8 1.076zM15 9.933l-2.75 1.571v3.134L15 13.067zM3.75 14.638v-3.134L1 9.933v3.134z"/></svg>
            Iqbal<span>Erp</span>
        </div>
        <p class="login-sub">Sign in to your account</p>

        <?php if (!empty($error)): ?>
            <div class="alert-danger" role="alert" style="margin-top:1.25rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div style="height:1.25rem;"></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= $csrfField ?? Auth::csrfField() ?>
            <div class="field">
                <label for="email">Email Address</label>
                <div class="field-row">
                    <span class="field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/></svg>
                    </span>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="admin@system.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="field-row">
                    <span class="field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2M5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1"/></svg>
                    </span>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn-login">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                Sign In
            </button>
        </form>

        <p class="login-foot">
            <?= APP_NAME ?> &copy; <?= date('Y') ?>
        </p>
    </div>
</body>
</html>
