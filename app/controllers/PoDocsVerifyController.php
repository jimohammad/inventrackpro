<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/PoDocsVerify.php';

/**
 * Public verification for PO bank document packs (QR scan, no login).
 */
class PoDocsVerifyController extends BaseController {

    public function index(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        if (self::ipRateLimited('po_docs_verify_rl', 40, 300)) {
            http_response_code(429);
            $error = 'Too many requests. Please try again in a few minutes.';
            $status = 'invalid';
            $claims = null;
            $live = ['po_count' => 0, 'invoice_count' => 0, 'tt_count' => 0, 'fingerprint' => '', 'rows' => []];
            $warehouse = null;
            $this->render($error, $status, $claims, $live, $warehouse);
            return;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $settings = self::getSettings();
        $companyName = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
        $companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
        if ($companyNameAr === '') {
            $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
        }

        $error = null;
        $claims = null;
        $live = ['po_count' => 0, 'invoice_count' => 0, 'tt_count' => 0, 'fingerprint' => '', 'rows' => []];
        $status = 'invalid';
        $warehouse = null;

        if ($token === '') {
            $error = 'This verification link is incomplete.';
            $this->render($error, $status, $claims, $live, $warehouse, $companyName, $companyNameAr);
            return;
        }

        $db = Database::getInstance();
        $claims = PoDocsVerify::parse($db, $token);
        if ($claims === null) {
            http_response_code(400);
            $error = 'This verification code is not valid. It may have been typed incorrectly.';
            $this->render($error, $status, $claims, $live, $warehouse, $companyName, $companyNameAr);
            return;
        }

        try {
            $whId = (int) $claims['warehouse_id'];
            if (($claims['kind'] ?? '') === 'po') {
                $live = PoDocsVerify::livePo($db, $whId, (int) $claims['po_id']);
            } else {
                $live = PoDocsVerify::livePack(
                    $db,
                    $whId,
                    (string) $claims['from_date'],
                    (string) $claims['to_date'],
                    (string) $claims['date_field']
                );
            }
            $warehouse = $db->fetchOne(
                'SELECT id, name FROM warehouses WHERE id = ?',
                [$whId]
            );
        } catch (Throwable $e) {
            error_log('[ERP] PO docs verify: ' . $e->getMessage());
            http_response_code(500);
            $error = 'Could not load purchase-order documents just now. Please try again.';
            $this->render($error, $status, $claims, $live, $warehouse, $companyName, $companyNameAr);
            return;
        }

        $status = $this->matchStatus($claims, $live);
        $this->render($error, $status, $claims, $live, $warehouse, $companyName, $companyNameAr);
    }

    /**
     * @param array<string,mixed> $claims
     * @param array<string,mixed> $live
     */
    private function matchStatus(array $claims, array $live): string {
        if (!empty($live['cancelled'])) {
            return 'changed';
        }
        $sameCounts = ((int) $claims['invoice_count'] === (int) ($live['invoice_count'] ?? 0))
            && ((int) $claims['tt_count'] === (int) ($live['tt_count'] ?? 0));
        if (($claims['kind'] ?? '') === 'pack') {
            $sameCounts = $sameCounts && ((int) $claims['po_count'] === (int) ($live['po_count'] ?? 0));
        }
        $sameFp = hash_equals(
            strtolower((string) ($claims['fingerprint'] ?? '')),
            strtolower((string) ($live['fingerprint'] ?? ''))
        );
        return ($sameCounts && $sameFp) ? 'match' : 'changed';
    }

    /**
     * @param array<string,mixed>|null $claims
     * @param array<string,mixed> $live
     * @param array<string,mixed>|null $warehouse
     */
    private function render(
        ?string $error,
        string $status,
        ?array $claims,
        array $live,
        ?array $warehouse,
        string $companyName = '',
        string $companyNameAr = ''
    ): void {
        if ($companyName === '') {
            $settings = self::getSettings();
            $companyName = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
            $companyNameAr = trim((string) ($settings['company_name_ar'] ?? ''));
        }
        if ($companyNameAr === '') {
            $companyNameAr = 'شركة إقبال للأجهزة إلكترونية ذ.م.م';
        }
        include __DIR__ . '/../views/public/po_docs_verify.php';
    }
}
