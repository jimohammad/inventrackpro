<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/PaymentReceiptVerify.php';

/**
 * Public verification for a payment receipt QR (no login).
 */
class PaymentVerifyController extends BaseController
{
    public function index(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        $settings = self::getSettings();
        $companyName = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
        $companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
        if ($companyNameAr === '') {
            $companyNameAr = 'شركة إقبال للأجهزة الإلكترونية ذ.م.م';
        }

        $error = null;
        $status = 'invalid';
        $claims = null;
        $live = null;

        if (self::ipRateLimited('payment_receipt_verify_rl', 40, 300)) {
            http_response_code(429);
            $error = 'Too many requests. Please try again in a few minutes.';
            include __DIR__ . '/../views/public/payment_verify.php';
            return;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            $error = 'This verification link is incomplete.';
            include __DIR__ . '/../views/public/payment_verify.php';
            return;
        }

        $db = Database::getInstance();
        $claims = PaymentReceiptVerify::parse($db, $token);
        if ($claims === null) {
            http_response_code(400);
            $error = 'This verification code is not valid.';
            include __DIR__ . '/../views/public/payment_verify.php';
            return;
        }

        try {
            $live = PaymentReceiptVerify::live(
                $db,
                (int) $claims['payment_id'],
                (int) $claims['warehouse_id']
            );
        } catch (Throwable $e) {
            error_log('[ERP] Payment receipt verify: ' . $e->getMessage());
            http_response_code(500);
            $error = 'Could not load this receipt just now. Please try again.';
            include __DIR__ . '/../views/public/payment_verify.php';
            return;
        }

        if ($live === null) {
            http_response_code(404);
            $error = 'No matching receipt was found.';
            include __DIR__ . '/../views/public/payment_verify.php';
            return;
        }

        $liveStatus = strtolower((string) ($live['status'] ?? ''));
        if ($liveStatus === 'cancelled' || $liveStatus === 'void') {
            $status = 'voided';
        } else {
            $signedFils = (int) round(((float) $claims['amount']) * 1000);
            $liveFils = (int) round(((float) $live['amount']) * 1000);
            $status = ($signedFils === $liveFils) ? 'match' : 'changed';
        }

        include __DIR__ . '/../views/public/payment_verify.php';
    }
}
