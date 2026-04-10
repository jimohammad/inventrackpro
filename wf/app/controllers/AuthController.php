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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF verification
            if (!Auth::verifyCsrf()) {
                $error = 'Invalid form submission. Please try again.';
                include __DIR__ . '/../views/login.php';
                return;
            }

            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Please enter your email and password.';
            } else {
                $result = Auth::login($email, $password);
                if ($result === true) {
                    header('Location: ' . APP_URL . '/?page=warehouse');
                    exit;
                } else {
                    $error = $result;
                }
            }
        }

        include __DIR__ . '/../views/login.php';
    }

    // Logout
    public function logout(): void {
        Auth::clearWarehouse();
        Auth::logout();
        header('Location: ' . APP_URL . '/?page=login');
        exit;
    }
}
