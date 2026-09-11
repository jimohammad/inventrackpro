<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/ManpowerVerify.php';

/**
 * Public verification for Manpower invoice packs (QR scan, no login).
 */
class VerifyController extends BaseController {

    public function index(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        if (self::ipRateLimited('manpower_verify_rl', 40, 300)) {
            http_response_code(429);
            $error = 'Too many requests. Please try again in a few minutes.';
            include __DIR__ . '/../views/public/manpower_verify.php';
            return;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $error = null;
        $claims = null;
        $live = [
            'invoice_count' => 0,
            'grand_total'   => 0.0,
            'invoices'      => [],
        ];
        $status = 'invalid';
        $warehouse = null;
        $settings = self::getSettings();
        $companyName = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
        $companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
        if ($companyNameAr === '') {
            $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
        }

        if ($token === '') {
            $error = 'This verification link is incomplete.';
            include __DIR__ . '/../views/public/manpower_verify.php';
            return;
        }

        $db = Database::getInstance();
        $claims = ManpowerVerify::parse($db, $token);
        if ($claims === null) {
            http_response_code(400);
            $error = 'This verification code is not valid. It may have been typed incorrectly.';
            include __DIR__ . '/../views/public/manpower_verify.php';
            return;
        }

        try {
            $live = ManpowerVerify::livePack(
                $db,
                (int) $claims['warehouse_id'],
                (string) $claims['from_date'],
                (string) $claims['to_date']
            );
            $warehouse = $db->fetchOne(
                'SELECT id, name FROM warehouses WHERE id = ?',
                [(int) $claims['warehouse_id']]
            );
        } catch (Throwable $e) {
            error_log('[ERP] Manpower verify: ' . $e->getMessage());
            http_response_code(500);
            $error = 'Could not load sales records just now. Please try again.';
            include __DIR__ . '/../views/public/manpower_verify.php';
            return;
        }

        $signedCount = (int) $claims['invoice_count'];
        $liveCount   = (int) $live['invoice_count'];
        $signedFils  = (int) round(((float) $claims['grand_total']) * 1000);
        $liveFils    = (int) round(((float) $live['grand_total']) * 1000);
        $status = ($signedCount === $liveCount && $signedFils === $liveFils) ? 'match' : 'changed';

        include __DIR__ . '/../views/public/manpower_verify.php';
    }
}
