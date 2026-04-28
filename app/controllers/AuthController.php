<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';

/**
 * Auth Controller
 * Handles login and logout only
 */
class AuthController {

    public function __construct() {
        Auth::startSession();
    }

    // Show login page or handle login POST
    public function index(): void {
        // Already logged in - go to dashboard
        if (Auth::check()) {
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }

        $error = '';
        $db    = Database::getInstance();
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifyCsrf()) {
                $error = 'Invalid request. Please try again.';
            } else {
                // Check brute force - max 5 attempts per 15 minutes
                $attempts = $db->fetchOne(
                    "SELECT COUNT(*) as c FROM login_attempts 
                     WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                    [$ip]
                );

                if ($attempts && $attempts['c'] >= 5) {
                    $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
                } else {
                    $email    = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';

                    if (empty($email) || empty($password)) {
                        $error = 'Please enter your email and password.';
                    } else {
                        $result = Auth::login($email, $password);
                        if ($result === true) {
                            // Clear failed attempts on success
                            $db->execute("DELETE FROM login_attempts WHERE ip = ?", [$ip]);
                            header('Location: ' . APP_URL . '/?page=warehouse');
                            exit;
                        } else {
                            // Record failed attempt
                            $db->execute(
                                "INSERT INTO login_attempts (ip, created_at) VALUES (?, NOW())",
                                [$ip]
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
        Auth::logout();
        header('Location: ' . APP_URL . '/?page=login');
        exit;
    }
}
