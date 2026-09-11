<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Auth Controller
 * Handles login and logout only
 */
class AuthController extends BaseController {

    // Show login page or handle login POST
    public function index(): void {
        ob_start();
        try {
            try {
                if (Auth::check()) {
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    header('Location: ' . APP_URL . '/?page=dashboard');
                    exit;
                }
            } catch (Throwable $e) {
                error_log('[ERP] Login Auth::check: ' . $e->getMessage());
            }

            $error = '';
            if (($_GET['reason'] ?? '') === 'new_day') {
                $error = 'Your session ended because the date changed. Please sign in again.';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handleLoginPost($error);
            }

            $view = __DIR__ . '/../views/login.php';
            if (!is_readable($view)) {
                $view = __DIR__ . '/../../app/views/login.php';
            }
            if (!is_readable($view)) {
                throw new RuntimeException('Login view is missing.');
            }
            require $view;
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            error_log('[ERP] Login page: ' . get_class($e) . ': ' . $e->getMessage()
                . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->renderFallbackLogin(
                'We could not load the sign-in page. Please try again.',
                $e
            );
        }
    }

    /** @param-out string $error */
    private function handleLoginPost(string &$error): void {
        if (!Auth::verifyCsrf()) {
            $error = 'Invalid request. Please try again.';
            return;
        }

        $db    = Database::getInstance();
        $ip    = self::clientIp();
        $email = trim($_POST['email'] ?? '');

        $this->ensureLoginAttemptsTable($db);

        $attempts = null;
        try {
            $attempts = $db->fetchOne(
                "SELECT COUNT(*) as c FROM login_attempts
                 WHERE (ip = ? OR email = ?) AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                [$ip, $email]
            );
        } catch (Throwable $e) {
            error_log('[ERP] login_attempts count: ' . $e->getMessage());
        }

        if ($attempts && (int) ($attempts['c'] ?? 0) >= 5) {
            $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
            return;
        }

        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $error = 'Please enter your email and password.';
            return;
        }

        $result = Auth::login($email, $password);
        if ($result === true) {
            try {
                $db->execute("DELETE FROM login_attempts WHERE ip = ? OR email = ?", [$ip, $email]);
            } catch (Throwable $e) {
                error_log('[ERP] login_attempts clear: ' . $e->getMessage());
            }
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            Auth::startSession();
            if (!(defined('WAREHOUSE_UI_SWITCHER') && WAREHOUSE_UI_SWITCHER)) {
                Auth::autoSelectOperationalWarehouse();
                Auth::releaseSession();
                $this->redirect(APP_URL . '/?page=dashboard');
            }
            Auth::releaseSession();
            $this->redirect(APP_URL . '/?page=warehouse');
            return;
        }

        try {
            $db->execute(
                "INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())",
                [$ip, $email]
            );
        } catch (Throwable $e) {
            error_log('[ERP] login_attempts insert: ' . $e->getMessage());
        }
        $error = $result;
    }

    private function ensureLoginAttemptsTable(Database $db): void {
        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip VARCHAR(45) NOT NULL DEFAULT '',
                    email VARCHAR(191) NOT NULL DEFAULT '',
                    created_at DATETIME NOT NULL,
                    KEY idx_login_attempts_ip_created (ip, created_at),
                    KEY idx_login_attempts_email_created (email, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
            error_log('[ERP] login_attempts ensure: ' . $e->getMessage());
        }
    }

    private function renderFallbackLogin(string $error, ?Throwable $cause = null): void {
        $csrf = '';
        try {
            $csrf = Auth::csrfField();
        } catch (Throwable $e) {
            error_log('[ERP] Login csrf: ' . $e->getMessage());
        }
        $app = defined('APP_NAME') ? APP_NAME : 'IqbalErp';
        $year = date('Y');
        $email = htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $err = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        $diag = '';
        if ($cause && isset($_GET['erp_diag']) && (string) $_GET['erp_diag'] === '1') {
            $diag = '<!-- ERP-LOGIN ' . htmlspecialchars(
                get_class($cause) . ': ' . $cause->getMessage()
                . ' @ ' . basename($cause->getFile()) . ':' . $cause->getLine(),
                ENT_QUOTES,
                'UTF-8'
            ) . ' -->';
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Login | ' . htmlspecialchars($app, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>
                body{background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;font-family:Segoe UI,sans-serif}
                .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2.5rem;width:100%;max-width:420px}
                h1{color:#6366f1;font-size:1.8rem;margin:0 0 .5rem;text-align:center}
                p{color:#94a3b8;text-align:center;margin:0 0 1rem}
                label{color:#94a3b8;font-size:.875rem;display:block;margin:0 0 .35rem}
                input{width:100%;box-sizing:border-box;background:#0f172a;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:.75rem 1rem;margin:0 0 1rem}
                button{width:100%;background:#6366f1;border:none;border-radius:8px;padding:.75rem;color:#fff;font-weight:600;cursor:pointer}
                .alert{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5;border-radius:8px;padding:.75rem;margin:0 0 1rem}
            </style></head><body>' . $diag
            . '<div class="card"><h1>IqbalErp</h1><p>Sign in to your account</p>'
            . ($err !== '' ? '<div class="alert">' . $err . '</div>' : '')
            . '<form method="POST" action="">' . $csrf
            . '<label>Email Address</label><input type="email" name="email" value="' . $email . '" required autofocus>'
            . '<label>Password</label><input type="password" name="password" required>'
            . '<button type="submit">Sign In</button></form>'
            . '<p style="margin-top:1.5rem;color:#475569;font-size:.8rem">' . htmlspecialchars($app, ENT_QUOTES, 'UTF-8')
            . ' &copy; ' . $year . '</p></div></body></html>';
    }

    // Logout user
    public function logout(): void {
        if (!$this->isPost()) {
            $this->redirect(APP_URL . '/?page=login');
            return;
        }
        Auth::logout();
        $this->redirect(APP_URL . '/?page=login');
    }
}
