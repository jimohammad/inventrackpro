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
        $asOfToday     = date('Y-m-d');
        // Cap the lines at today so the running column matches the Balance card and Party Master Due.
        $transactions  = $partyModel->getUnifiedStatementTransactions((int) $party['id'], '', $asOfToday, $statementWhId);
        $closingBal = $partyModel->computeBalanceAsOf((int) $party['id'], $asOfToday, $statementWhId);

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
        $kind   = trim($_GET['kind'] ?? 'sale');
        if (!in_array($kind, ['sale', 'return'], true)) {
            $kind = 'sale';
        }

        if (!$token || !$refNo) { echo json_encode(['error' => 'Invalid request']); return; }

        $db = Database::getInstance();

        // Same token verification path as index() — resolves warehouse in one shot.
        $partyModel = new Party();
        $party = $partyModel->findByStatementToken($token);
        if (!$party) { echo json_encode(['error' => 'Invalid token']); return; }

        $statementWhId = (int) ($party['statement_warehouse_id'] ?? $partyModel->resolvePublicStatementWarehouseId((int) $party['id']));
        if ($kind === 'return') {
            $docSql = "SELECT return_no AS invoice_no, date, created_at, subtotal,
                              0 AS discount, grand_total, 0 AS paid_amount, 0 AS balance,
                              status, reason
                       FROM `returns`
                       WHERE return_no = ? AND party_id = ? AND status = 'approved'";
            $docParams = [$refNo, $party['id']];
            if ($statementWhId > 0) {
                $docSql .= ' AND warehouse_id = ?';
                $docParams[] = $statementWhId;
            }
            $sale = $db->fetchOne($docSql, $docParams);
            if (!$sale) { echo json_encode(['error' => 'Return not found']); return; }

            $itemsSql = "SELECT i.name AS item_name, ri.quantity, ri.unit_price, 0 AS discount, ri.total
                 FROM return_items ri
                 JOIN items i ON i.id = ri.item_id
                 JOIN `returns` r ON r.id = ri.return_id
                 WHERE r.return_no = ? AND r.party_id = ? AND r.status = 'approved'";
            $itemsParams = [$refNo, $party['id']];
            if ($statementWhId > 0) {
                $itemsSql .= ' AND r.warehouse_id = ?';
                $itemsParams[] = $statementWhId;
            }
            $sale['doc_kind'] = 'return';
        } else {
            $docSql = "SELECT s.invoice_no, s.date, s.created_at, s.subtotal, s.discount, s.grand_total, s.paid_amount, s.balance, s.status
                 FROM sales s WHERE s.invoice_no = ? AND s.party_id = ? AND s.status != 'cancelled'";
            $docParams = [$refNo, $party['id']];
            if ($statementWhId > 0) {
                $docSql .= ' AND s.warehouse_id = ?';
                $docParams[] = $statementWhId;
            }
            $sale = $db->fetchOne($docSql, $docParams);
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
            $sale['doc_kind'] = 'sale';
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
