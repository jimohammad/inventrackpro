<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/SaleEditRequest.php';

/**
 * Cashier requests invoice edit; admin approves a 30-minute unlock.
 */
class SaleEditRequestController extends BaseController {

    public function index(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }
        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);

        $wh = (int) (Auth::warehouseId() ?: 0);
        $status = preg_replace('/[^a-z]/', '', strtolower((string) ($_GET['status'] ?? 'pending')));
        if (!in_array($status, ['pending', 'approved', 'rejected', 'used', 'expired', 'all'], true)) {
            $status = 'pending';
        }

        $params = [];
        $where = '1=1';
        if ($wh > 0) {
            $where .= ' AND r.warehouse_id = ?';
            $params[] = $wh;
        }
        if ($status !== 'all') {
            $where .= ' AND r.status = ?';
            $params[] = $status;
        }

        $rows = $db->fetchAll(
            "SELECT r.*, s.invoice_no, s.status AS sale_status, p.name AS party_name,
                    u.name AS requested_by_name, ru.name AS reviewed_by_name
             FROM sale_edit_requests r
             JOIN sales s ON s.id = r.sale_id
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = r.requested_by
             LEFT JOIN users ru ON ru.id = r.reviewed_by
             WHERE {$where}
             ORDER BY
               CASE r.status WHEN 'pending' THEN 0 ELSE 1 END,
               r.id DESC
             LIMIT 200",
            $params
        );

        $pendingCount = SaleEditRequest::countPending($db, $wh > 0 ? $wh : null);
        $pageTitle = 'Sale edit requests';
        $page = 'saleedits';

        ob_start();
        include __DIR__ . '/../views/sale_edit_requests/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function view(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }
        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);

        $id = $this->inputInt('id', 0, 'get');
        $row = $this->findScoped($db, $id);
        if (!$row) {
            $this->flash('error', 'Edit request not found.');
            $this->redirect('?page=saleedits');
            return;
        }

        $pageTitle = 'Edit request';
        $page = 'saleedits';

        ob_start();
        include __DIR__ . '/../views/sale_edit_requests/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function request(): void {
        Auth::authorize('sales', 'view');
        if (Auth::role() !== 'cashier') {
            $this->flash('error', 'Only a salesman can request invoice edit.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=sales');
            return;
        }

        $saleId = $this->inputInt('sale_id');
        $reason = trim((string) $this->input('reason'));
        if (mb_strlen($reason) > SaleEditRequest::REASON_MAX) {
            $reason = mb_substr($reason, 0, SaleEditRequest::REASON_MAX);
        }

        $saleModel = new Sale();
        $sale = $saleModel->find($saleId);
        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('?page=sales');
            return;
        }

        $sessionWh = (int) Auth::warehouseId();
        if ($sessionWh > 0 && (int) ($sale['warehouse_id'] ?? 0) !== $sessionWh) {
            $this->flash('error', 'This sale belongs to a different warehouse.');
            $this->redirect('?page=sales');
            return;
        }

        $detailUrl = '?page=sales&action=detail&id=' . $saleId;
        if (($sale['status'] ?? '') === 'cancelled') {
            $this->flash('error', 'Cancelled invoices cannot be edited.');
            $this->redirect($detailUrl);
            return;
        }

        if (mb_strlen($reason) < SaleEditRequest::REASON_MIN) {
            $this->flash('error', 'Please enter a short reason (at least ' . SaleEditRequest::REASON_MIN . ' characters).');
            $this->redirect($detailUrl);
            return;
        }

        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);

        $userId = (int) Auth::id();
        if (SaleEditRequest::findActiveUnlock($db, $saleId, $userId)) {
            $this->flash('warning', 'This invoice is already unlocked. Use Edit.');
            $this->redirect($detailUrl);
            return;
        }
        if (SaleEditRequest::findPendingForSale($db, $saleId)) {
            $this->flash('warning', 'An edit request is already waiting for admin.');
            $this->redirect($detailUrl);
            return;
        }

        $id = SaleEditRequest::createPending(
            $db,
            $saleId,
            (int) ($sale['warehouse_id'] ?? $sessionWh),
            $userId,
            $reason
        );
        $this->logActivity(
            'request_sale_edit',
            'sales',
            $saleId,
            'Requested edit of ' . ($sale['invoice_no'] ?? ('#' . $saleId))
            . ($id > 0 ? (' (request #' . $id . ')') : '')
            . ': ' . $reason
        );
        $this->flash('success', 'Edit request sent. Wait for admin approval.');
        $this->redirect($detailUrl);
    }

    public function decide(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=sales');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=saleedits');
            return;
        }

        $db = Database::getInstance();
        SaleEditRequest::ensureTable($db);
        SaleEditRequest::expireOverdue($db);

        $id = $this->inputInt('id');
        $decision = preg_replace('/[^a-z]/', '', strtolower((string) $this->input('decision')));
        $staffNote = trim((string) $this->input('staff_note'));
        if (mb_strlen($staffNote) > 500) {
            $staffNote = mb_substr($staffNote, 0, 500);
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            $this->flash('error', 'Invalid decision.');
            $this->redirect('?page=saleedits');
            return;
        }

        $row = $this->findScoped($db, $id);
        if (!$row) {
            $this->flash('error', 'Edit request not found.');
            $this->redirect('?page=saleedits');
            return;
        }
        if (($row['status'] ?? '') !== 'pending') {
            $this->flash('error', 'This request was already reviewed.');
            $this->redirect('?page=saleedits&action=view&id=' . $id);
            return;
        }

        $adminId = (int) Auth::id();
        $ok = $decision === 'approved'
            ? SaleEditRequest::approve($db, $id, $adminId, $staffNote)
            : SaleEditRequest::reject($db, $id, $adminId, $staffNote);

        if (!$ok) {
            $this->flash('error', 'Could not save the decision.');
            $this->redirect('?page=saleedits&action=view&id=' . $id);
            return;
        }

        $saleId = (int) ($row['sale_id'] ?? 0);
        $inv = (string) ($row['invoice_no'] ?? ('#' . $saleId));
        $who = (string) ($row['requested_by_name'] ?? 'salesman');
        $this->logActivity(
            $decision === 'approved' ? 'approve_sale_edit' : 'reject_sale_edit',
            'sales',
            $saleId,
            ucfirst($decision) . ' edit of ' . $inv . ' for ' . $who
            . ($staffNote !== '' ? (': ' . $staffNote) : '')
        );

        $mins = SaleEditRequest::UNLOCK_MINUTES;
        $this->flash(
            'success',
            $decision === 'approved'
                ? ('Approved. ' . $who . ' can edit ' . $inv . ' for ' . $mins . ' minutes or until they Save.')
                : ('Request rejected.')
        );
        $this->redirect('?page=saleedits&action=view&id=' . $id);
    }

    public function pendingJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!Auth::check() || !Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false]);
            exit;
        }

        $db = Database::getInstance();
        $wh = (int) (Auth::warehouseId() ?: 0);
        $whArg = $wh > 0 ? $wh : null;
        try {
            $poll = SaleEditRequest::pendingPoll($db, $whArg);
        } catch (Throwable $e) {
            SaleEditRequest::ensureTable($db);
            $poll = SaleEditRequest::pendingPoll($db, $whArg);
        }
        $count    = $poll['count'];
        $latestId = $poll['latest_id'];

        $latest = null;
        if ($latestId > 0) {
            $r = $db->fetchOne(
                "SELECT r.id, r.reason, r.created_at, s.invoice_no, u.name AS requested_by_name
                 FROM sale_edit_requests r
                 JOIN sales s ON s.id = r.sale_id
                 LEFT JOIN users u ON u.id = r.requested_by
                 WHERE r.id = ?",
                [$latestId]
            );
            if ($r) {
                $latest = [
                    'id'               => (int) $r['id'],
                    'invoice_no'       => $r['invoice_no'],
                    'requested_by_name'=> $r['requested_by_name'],
                    'reason'           => $r['reason'],
                    'created_at'       => $r['created_at'],
                ];
            }
        }

        echo json_encode([
            'ok'        => true,
            'count'     => $count,
            'latest_id' => $latestId,
            'latest'    => $latest,
        ]);
        exit;
    }

    /** @return array<string,mixed>|null */
    private function findScoped(Database $db, int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $wh = (int) (Auth::warehouseId() ?: 0);
        $params = [$id];
        $whSql = '';
        if ($wh > 0) {
            $whSql = ' AND r.warehouse_id = ?';
            $params[] = $wh;
        }
        $row = $db->fetchOne(
            "SELECT r.*, s.invoice_no, s.status AS sale_status, s.date AS sale_date,
                    p.name AS party_name, u.name AS requested_by_name, ru.name AS reviewed_by_name
             FROM sale_edit_requests r
             JOIN sales s ON s.id = r.sale_id
             LEFT JOIN parties p ON p.id = s.party_id
             LEFT JOIN users u ON u.id = r.requested_by
             LEFT JOIN users ru ON ru.id = r.reviewed_by
             WHERE r.id = ?{$whSql}",
            $params
        );
        return $row ?: null;
    }
}
