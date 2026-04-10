<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';

class FieldStatementController extends BaseController {

    public function index(): void {
        $token = trim($_GET['token'] ?? '');
        if (!$token) { $this->showError('Invalid link.'); return; }

        $db = Database::getInstance();

        // Find party by token
        $party = $db->fetchOne(
            "SELECT p.*,
                p.opening_balance
                + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled'), 0)
                - COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount')), 0)
                - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved'), 0)
                as net_balance
             FROM parties p WHERE p.statement_token = ?",
            [$token]
        );

        if (!$party) { $this->showError('Invalid or expired link.'); return; }

        // Get transactions
        $transactions = $db->fetchAll(
            "SELECT 'Sale' as type, invoice_no as ref_no, date, grand_total as debit, 0 as credit, status, created_at
             FROM sales WHERE party_id = ? AND status != 'cancelled'
             UNION ALL
             SELECT 'Payment', payment_no, date, 0, amount, 'paid', created_at
             FROM payments WHERE party_id = ? AND ref_type IN ('sale','discount')
             UNION ALL
             SELECT 'Return', return_no, date, 0, grand_total, status, created_at
             FROM returns WHERE party_id = ? AND type = 'sale_return' AND status = 'approved'
             ORDER BY date ASC, created_at ASC",
            [$party['id'], $party['id'], $party['id']]
        );

        // Get company info
        $company = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_name'");
        $companyName = $company['value'] ?? 'Iqbal Sons';

        $companyPhone = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_phone'");
        $companyPhoneVal = $companyPhone['value'] ?? '';

        include __DIR__ . '/../views/public/field_statement.php';
        exit;
    }

    // AJAX: Get invoice details for public view
    public function invoiceDetail(): void {
        header('Content-Type: application/json');

        $token  = trim($_GET['token'] ?? '');
        $refNo  = trim($_GET['ref'] ?? '');

        if (!$token || !$refNo) { echo json_encode(['error' => 'Invalid request']); return; }

        $db = Database::getInstance();

        // Verify token belongs to a real party
        $party = $db->fetchOne("SELECT id FROM parties WHERE statement_token = ?", [$token]);
        if (!$party) { echo json_encode(['error' => 'Invalid token']); return; }

        // Get sale with items — only if it belongs to this party
        $sale = $db->fetchOne(
            "SELECT s.invoice_no, s.date, s.subtotal, s.discount, s.grand_total, s.paid_amount, s.balance, s.status
             FROM sales s WHERE s.invoice_no = ? AND s.party_id = ? AND s.status != 'cancelled'",
            [$refNo, $party['id']]
        );

        if (!$sale) { echo json_encode(['error' => 'Invoice not found']); return; }

        $items = $db->fetchAll(
            "SELECT i.name as item_name, si.quantity, si.unit_price, si.discount, si.total
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.invoice_no = ? AND s.party_id = ?",
            [$refNo, $party['id']]
        );

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
