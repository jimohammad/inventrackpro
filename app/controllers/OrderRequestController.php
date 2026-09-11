<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/OrderRequest.php';

/**
 * Staff: review customer order requests from /apps (prior confirmation).
 */
class OrderRequestController extends BaseController {

    public function index(): void {
        Auth::authorize('sales', 'view');
        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $wh = (int) (Auth::warehouseId() ?: 0);
        $status = preg_replace('/[^a-z]/', '', strtolower((string) ($_GET['status'] ?? 'pending')));
        if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        $params = [];
        $where = '1=1';
        if ($wh > 0) {
            $where .= ' AND o.warehouse_id = ?';
            $params[] = $wh;
        }
        if ($status !== 'all') {
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }

        $rows = $db->fetchAll(
            "SELECT o.*, u.name AS reviewed_by_name
             FROM order_requests o
             LEFT JOIN users u ON u.id = o.reviewed_by
             WHERE {$where}
             ORDER BY
               CASE o.status WHEN 'pending' THEN 0 ELSE 1 END,
               o.id DESC
             LIMIT 200",
            $params
        );

        $pendingCount = OrderRequest::countPending($db, $wh > 0 ? $wh : null);
        $pageTitle = 'Order Requests';
        $page = 'orderrequests';

        ob_start();
        include __DIR__ . '/../views/order_requests/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function view(): void {
        Auth::authorize('sales', 'view');
        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $id = (int) ($_GET['id'] ?? 0);
        $row = $this->findScoped($db, $id);
        if (!$row) {
            $this->flash('error', 'Order request not found.');
            header('Location: ?page=orderrequests');
            exit;
        }

        $reviewer = null;
        if (!empty($row['reviewed_by'])) {
            $reviewer = $db->fetchOne('SELECT name FROM users WHERE id = ?', [(int) $row['reviewed_by']]);
        }

        $order = $row;
        $pageTitle = 'Order ' . ($row['request_no'] ?? '');
        $page = 'orderrequests';

        ob_start();
        include __DIR__ . '/../views/order_requests/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function decide(): void {
        if (!Auth::can('sales', 'edit') && !Auth::can('sales', 'add')) {
            Auth::authorize('sales', 'edit');
        }
        $db = Database::getInstance();
        OrderRequest::ensureTable($db);

        $id = (int) ($_POST['id'] ?? 0);
        $decision = preg_replace('/[^a-z]/', '', strtolower((string) ($_POST['decision'] ?? '')));
        $staffNote = trim((string) ($_POST['staff_note'] ?? ''));
        if (mb_strlen($staffNote) > 2000) {
            $staffNote = mb_substr($staffNote, 0, 2000);
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            $this->flash('error', 'Invalid decision.');
            header('Location: ?page=orderrequests');
            exit;
        }

        $row = $this->findScoped($db, $id);
        if (!$row) {
            $this->flash('error', 'Order request not found.');
            header('Location: ?page=orderrequests');
            exit;
        }
        if (($row['status'] ?? '') !== 'pending') {
            $this->flash('error', 'This request was already reviewed.');
            header('Location: ?page=orderrequests&action=view&id=' . $id);
            exit;
        }

        $db->execute(
            "UPDATE order_requests
             SET status = ?, staff_note = ?, reviewed_by = ?, reviewed_at = NOW(),
                 customer_notified_at = NULL
             WHERE id = ? AND status = 'pending'",
            [
                $decision,
                $staffNote !== '' ? $staffNote : null,
                Auth::id(),
                $id,
            ]
        );

        $label = $decision === 'approved' ? 'approved' : 'rejected';
        $this->flash('success', 'Order request ' . $label . '. Customer will be notified when they open the apps status page.');
        header('Location: ?page=orderrequests&action=view&id=' . $id);
        exit;
    }

    /** Lightweight poll endpoint for ERP desktop notifications. */
    public function pendingJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!Auth::check() || !Auth::can('sales', 'view')) {
            http_response_code(403);
            echo json_encode(['ok' => false]);
            exit;
        }

        $db = Database::getInstance();
        $wh = (int) (Auth::warehouseId() ?: 0);
        $whArg = $wh > 0 ? $wh : null;
        try {
            $poll = OrderRequest::pendingPoll($db, $whArg);
        } catch (Throwable $e) {
            OrderRequest::ensureTable($db);
            $poll = OrderRequest::pendingPoll($db, $whArg);
        }
        $count    = $poll['count'];
        $latestId = $poll['latest_id'];

        $latest = null;
        if ($latestId > 0) {
            $r = $db->fetchOne(
                'SELECT id, request_no, customer_name, customer_phone, created_at
                 FROM order_requests WHERE id = ?',
                [$latestId]
            );
            if ($r) {
                $latest = [
                    'id'             => (int) $r['id'],
                    'request_no'     => $r['request_no'],
                    'customer_name'  => $r['customer_name'],
                    'customer_phone' => $r['customer_phone'],
                    'created_at'     => $r['created_at'],
                ];
            }
        }

        echo json_encode([
            'ok'         => true,
            'count'      => $count,
            'latest_id'  => $latestId,
            'latest'     => $latest,
        ]);
        exit;
    }

    /** @return array<string,mixed>|null */
    private function findScoped(Database $db, int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $wh = (int) (Auth::warehouseId() ?: 0);
        if ($wh > 0) {
            $row = $db->fetchOne(
                'SELECT * FROM order_requests WHERE id = ? AND warehouse_id = ?',
                [$id, $wh]
            );
        } else {
            $row = $db->fetchOne('SELECT * FROM order_requests WHERE id = ?', [$id]);
        }
        return $row ?: null;
    }
}
