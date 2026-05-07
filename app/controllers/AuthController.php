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
        // Already logged in - go to dashboard
        if (Auth::check()) {
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }

        $error = '';
        $db    = Database::getInstance();
        $ip    = self::clientIp();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifyCsrf()) {
                $error = 'Invalid request. Please try again.';
            } else {
                // Check brute force - max 5 attempts per 15 minutes
                $email    = trim($_POST['email'] ?? '');
                $attempts = $db->fetchOne(
                    "SELECT COUNT(*) as c FROM login_attempts
                     WHERE (ip = ? OR email = ?) AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                    [$ip, $email]
                );

                if ($attempts && (int) ($attempts['c'] ?? 0) >= 5) {
                    $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
                } else {
                    $password = $_POST['password'] ?? '';

                    if (empty($email) || empty($password)) {
                        $error = 'Please enter your email and password.';
                    } else {
                        $result = Auth::login($email, $password);
                        if ($result === true) {
                            // Clear failed attempts on success
                            $db->execute("DELETE FROM login_attempts WHERE ip = ? OR email = ?", [$ip, $email]);
                            $this->redirect(APP_URL . '/?page=warehouse');
                        } else {
                            // Record failed attempt
                            $db->execute(
                                "INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())",
                                [$ip, $email]
                            );
                            $error = $result;
                        }
                    }
                }
            }
        }

        // Render login view
        require __DIR__ . '/../../app/views/login.php';
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
