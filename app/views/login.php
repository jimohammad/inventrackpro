<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
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
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .login-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #6366f1;
        }
        .login-logo span {
            color: #e2e8f0;
        }
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            background: #0f172a;
            border-color: #6366f1;
            color: #e2e8f0;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
        }
        .form-label {
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .btn-login {
            background: #6366f1;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #4f46e5;
        }
        .alert-danger {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            border-radius: 8px;
        }
        .input-group-text {
            background: #0f172a;
            border: 1px solid #334155;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="login-logo">
                <i class="bi bi-boxes"></i>
                Inven<span>Track</span>
            </div>
            <p class="text-secondary mt-2 mb-0" style="font-size:0.875rem;">Sign in to your account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
    <?= Auth::csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="admin@system.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-login btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <p class="text-center mt-4 mb-0" style="color:#475569;font-size:0.8rem;">
            <?= APP_NAME ?> &copy; <?= date('Y') ?>
        </p>
    </div>
</body>
</html>
