<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';

class FieldStatementController extends BaseController {

    /**
     * Public statement endpoints trigger heavy balance/aggregate queries, so
     * unauthenticated hits are capped per IP (shared BaseController limiter).
     */
    private static function rateLimited(): bool {
        return self::ipRateLimited('field_stmt_rl');
    }

    private static function sendPublicSecurityHeaders(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
    }

    public function index(): void {
        self::sendPublicSecurityHeaders();

        if (self::rateLimited()) {
            http_response_code(429);
            $this->showError('Too many requests. Please try again in a few minutes.');
            return;
        }

        $token = trim($_GET['token'] ?? '');
        if (!$token) { $this->showError('Invalid link.'); return; }

        $partyModel = new Party();
        $party = $partyModel->findByStatementToken($token);

        if (!$party) { $this->showError('Invalid or expired link.'); return; }

        $statementWhId = (int) ($party['statement_warehouse_id'] ?? $partyModel->resolvePublicStatementWarehouseId((int) $party['id']));
        $transactions  = $partyModel->getUnifiedStatementTransactions((int) $party['id'], '', '', $statementWhId);

        // Date-capped closing balance so the Balance card matches the running-balance
        // column (net_balance from the batch union has no date cap and would include
        // future-dated entries).
        $closingBal = $partyModel->computeBalanceAsOf((int) $party['id'], date('Y-m-d'), $statementWhId);

        // Get company info
        $companyName = self::getSettings()['company_name'] ?? PDF_COMPANY_NAME;

        $companyPhone = $this->db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_phone'");
        $companyPhoneVal = $companyPhone['value'] ?? '';

        include __DIR__ . '/../views/public/field_statement.php';
        exit;
    }

    // AJAX: Get invoice details for public view
    public function invoiceDetail(): void {
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

        if (self::rateLimited()) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please try again in a few minutes.']);
            return;
        }

        $token  = trim($_GET['token'] ?? '');
        $refNo  = trim($_GET['ref'] ?? '');

        if (!$token || !$refNo) { echo json_encode(['error' => 'Invalid request']); return; }

        $db = Database::getInstance();

        // Same token verification path as index() — resolves warehouse in one shot.
        $partyModel = new Party();
        $party = $partyModel->findByStatementToken($token);
        if (!$party) { echo json_encode(['error' => 'Invalid token']); return; }

        $statementWhId = (int) ($party['statement_warehouse_id'] ?? $partyModel->resolvePublicStatementWarehouseId((int) $party['id']));
        $saleSql = "SELECT s.invoice_no, s.date, s.created_at, s.subtotal, s.discount, s.grand_total, s.paid_amount, s.balance, s.status
             FROM sales s WHERE s.invoice_no = ? AND s.party_id = ? AND s.status != 'cancelled'";
        $saleParams = [$refNo, $party['id']];
        if ($statementWhId > 0) {
            $saleSql .= ' AND s.warehouse_id = ?';
            $saleParams[] = $statementWhId;
        }
        $sale = $db->fetchOne($saleSql, $saleParams);

        if (!$sale) { echo json_encode(['error' => 'Invoice not found']); return; }

        $itemsSql = "SELECT i.name as item_name, si.quantity, si.unit_price, si.discount, si.total
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.invoice_no = ? AND s.party_id = ?";
        $itemsParams = [$refNo, $party['id']];
        if ($statementWhId > 0) {
            $itemsSql .= ' AND s.warehouse_id = ?';
            $itemsParams[] = $statementWhId;
        }
        $items = $db->fetchAll($itemsSql, $itemsParams);

        if (is_array($sale)) {
            $sale['when_label'] = Party::statementWhenLabel($sale['date'] ?? '', $sale['created_at'] ?? '');
        }
        echo json_encode([
            'invoice' => $sale,
            'items'   => $items,
        ]);
    }

    private function showError(string $msg): void {
        include __DIR__ . '/../views/public/statement_error.php';
        exit;
    }
}
