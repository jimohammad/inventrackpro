<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Party.php';

class PaymentController extends BaseController {
    private Payment $paymentModel;
    private Party   $partyModel;

    public function __construct() {
        parent::__construct();
        $this->paymentModel = new Payment();
        $this->partyModel   = new Party();
    }

    public function index(): void {
        Auth::authorize('payments', 'view');

        $filters = [
            'search'    => $this->input('search', '', 'get'),
            'ref_type'  => $this->input('ref_type', '', 'get'),
            'from_date' => $this->input('from_date', date('Y-m-01'), 'get'),
            'to_date'   => $this->input('to_date', date('Y-m-d'), 'get'),
        ];

        $payments  = $this->paymentModel->getAll($filters);
        $summary   = $this->paymentModel->getSummary($filters['from_date'], $filters['to_date']);
        $pageTitle = 'Payments';
        $page      = 'payments';

        ob_start();
        include __DIR__ . '/../views/payments/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('payments', 'add');

        $db      = Database::getInstance();
        // Light query — just names for dropdown, no balance calculation
        $parties = $db->fetchAll(
            "SELECT id, name, phone, type FROM parties WHERE is_active = 1 ORDER BY name ASC"
        );
        $accounts = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

        // Pre-fill from ref
        $refType = $this->input('ref_type', 'sale', 'get');
        $refId   = $this->inputInt('ref_id', 0, 'get');
        $refData = null;

        if ($refId) {
            $table   = $refType === 'sale' ? 'sales' : 'purchases';
            $refData = $db->fetchOne(
                "SELECT t.*, p.name as party_name FROM {$table} t
                 JOIN parties p ON p.id = t.party_id
                 WHERE t.id = ? AND t.warehouse_id = ?",
                [$refId, Auth::warehouseId()]
            );
        }

        $pageTitle = 'New Payment';
        $page      = 'payments';
        // Light preview — no lock, lock only happens during actual save
        $lastPay = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1");
        $nextNum = $lastPay ? (int) substr($lastPay['payment_no'], 4) + 1 : 1;
        $nextPayNo = 'PAY-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        ob_start();
        include __DIR__ . '/../views/payments/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // AJAX: Get party balance (scoped to current warehouse)
    public function partyBalance(): void {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['balance' => 0]); return; }

        $db = Database::getInstance();
        $whId = Auth::warehouseId();
        $row = $db->fetchOne(
            "SELECT p.opening_balance
                + COALESCE(sl.total, 0)
                - COALESCE(py_s.total, 0)
                - COALESCE(rt_s.total, 0)
                - COALESCE(pr.total, 0)
                + COALESCE(py_p.total, 0)
                + COALESCE(rt_p.total, 0)
                as balance
             FROM parties p
             LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM sales WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled' GROUP BY party_id) sl ON sl.party_id = p.id
             LEFT JOIN (SELECT party_id, SUM(CASE WHEN payment_type='in' THEN amount ELSE -amount END) as total FROM payments WHERE party_id = ? AND warehouse_id = ? AND ref_type IN ('sale','discount') GROUP BY party_id) py_s ON py_s.party_id = p.id
             LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM returns WHERE party_id = ? AND warehouse_id = ? AND type = 'sale_return' AND status = 'approved' GROUP BY party_id) rt_s ON rt_s.party_id = p.id
             LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM purchases WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled' GROUP BY party_id) pr ON pr.party_id = p.id
             LEFT JOIN (SELECT party_id, SUM(amount) as total FROM payments WHERE party_id = ? AND warehouse_id = ? AND ref_type = 'purchase' GROUP BY party_id) py_p ON py_p.party_id = p.id
             LEFT JOIN (SELECT party_id, SUM(grand_total) as total FROM returns WHERE party_id = ? AND warehouse_id = ? AND type = 'purchase_return' AND status = 'approved' GROUP BY party_id) rt_p ON rt_p.party_id = p.id
             WHERE p.id = ?",
            [$id, $whId, $id, $whId, $id, $whId, $id, $whId, $id, $whId, $id, $whId, $id]
        );
        echo json_encode(['balance' => (float)($row['balance'] ?? 0)]);
    }

    public function store(): void {
        Auth::authorize('payments', 'add');

        if (!$this->isPost()) {
            $this->redirect('?page=payments&action=create');
        }

        $errors = $this->validate([
            'party_id'   => 'required',
            'account_id' => 'required',
            'amount'     => 'required',
            'date'       => 'required',
        ]);

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('?page=payments&action=create');
        }

        // Validate positive amount
        if ($this->inputFloat('amount') <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=payments&action=create');
        }

        $id = $this->paymentModel->createStandalone([
            'party_id'       => $this->inputInt('party_id'),
            'phone_no'       => $this->input('phone_no'),
            'payment_type'   => $this->input('payment_type') ?: 'in',
            'account_id'     => $this->inputInt('account_id'),
            'ref_type'       => $this->input('ref_type') ?: 'sale',
            'ref_id'         => $this->inputInt('ref_id'),
            'amount'         => $this->inputFloat('amount'),
            'payment_method' => $this->input('payment_method'),
            'cheque_no'      => $this->input('cheque_no'),
            'date'           => $this->input('date'),
            'notes'          => $this->input('notes'),
        ]);

        // Second split payment if amount2 > 0
        $amount2 = $this->inputFloat('amount2');
        $id2 = null;
        if ($amount2 > 0) {
            $id2 = $this->paymentModel->createStandalone([
                'party_id'       => $this->inputInt('party_id'),
                'phone_no'       => $this->input('phone_no'),
                'payment_type'   => $this->input('payment_type') ?: 'in',
                'account_id'     => $this->inputInt('account_id2'),
                'ref_type'       => $this->input('ref_type') ?: 'sale',
                'ref_id'         => $this->inputInt('ref_id'),
                'amount'         => $amount2,
                'payment_method' => $this->input('payment_method2'),
                'cheque_no'      => '',
                'date'           => $this->input('date'),
                'notes'          => $this->input('notes') ? $this->input('notes') . ' (Split 2)' : 'Split payment 2',
            ]);
            if ($id2) $this->logActivity('create_payment', 'payments', (int)$id2);
        }

        if ($id) {
            $this->logActivity('create_payment', 'payments', (int)$id);
            $printMode = $this->input('print_mode') === '1';
            if ($printMode) {
                $this->redirect("?page=payments&action=print&id={$id}");
            }
            $this->flash('success', 'Payment recorded successfully.');
        } else {
            $this->flash('error', 'Failed to save payment.');
        }

        $this->redirect('?page=payments');
    }

    public function print(): void {
        Auth::authorize('payments', 'view');
        $id      = $this->inputInt('id', 0, 'get');
        $payment = $this->paymentModel->findFull($id);
        if (!$payment) die('Payment not found.');

        $db       = Database::getInstance();
        $settings = self::getSettings();

        // Party balance
        $partyBalance = $this->partyModel->findWithBalance($payment['party_id']);
        $currentBalance  = (float)($partyBalance['net_balance'] ?? 0);
        $previousBalance = $currentBalance + (float)$payment['amount']; // before this payment was received

        include __DIR__ . '/../views/payments/print.php';
    }

    public function detail(): void {
        Auth::authorize('payments', 'view');
        $id      = $this->inputInt('id', 0, 'get');
        $payment = $this->paymentModel->findFull($id);

        if (!$payment) {
            $this->flash('error', 'Payment not found.');
            $this->redirect('?page=payments');
        }

        $pageTitle = 'Payment: ' . $payment['payment_no'];
        $page      = 'payments';

        ob_start();
        include __DIR__ . '/../views/payments/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Edit payment form
    public function edit(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin access required.'); $this->redirect('?page=payments'); return; };

        $id          = $this->inputInt('id', 0, 'get');
        $editPayment = $this->paymentModel->findFull($id);

        if (!$editPayment) {
            $this->flash('error', 'Payment not found.');
            $this->redirect('?page=payments');
            return;
        }

        $db       = Database::getInstance();
        $accounts = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

        $pageTitle = 'Edit Payment: ' . $editPayment['payment_no'];
        $page      = 'payments';

        ob_start();
        include __DIR__ . '/../views/payments/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Update payment (non-financial fields only)
    public function update(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin access required.'); $this->redirect('?page=payments'); return; };

        if (!$this->isPost()) {
            $this->redirect('?page=payments');
            return;
        }

        $id = $this->inputInt('id', 0, 'get') ?: $this->inputInt('id');
        $payment = $this->paymentModel->findFull($id);

        if (!$payment) {
            $this->flash('error', 'Payment not found.');
            $this->redirect('?page=payments');
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $newPartyId = $this->inputInt('party_id');
            $newAmount  = $this->inputFloat('amount');
            $oldAmount  = (float) $payment['amount'];
            $partyChanged  = $newPartyId && $newPartyId != $payment['party_id'];
            $amountChanged = abs($newAmount - $oldAmount) > 0.001;

            // Validate positive amount
            if ($newAmount <= 0) {
                throw new \Exception('Amount must be greater than zero.');
            }

            $newAccountId = $this->inputInt('account_id') ?: $payment['account_id'];

            // Fix account balances: fully reverse old, then apply new
            if ($payment['ref_type'] !== 'discount') {
                // Step 1: Reverse old amount from old account
                if ($payment['payment_type'] === 'in') {
                    $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$oldAmount, $payment['account_id']]);
                } else {
                    $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$oldAmount, $payment['account_id']]);
                }
                // Step 2: Apply new amount to new (or same) account
                if ($payment['payment_type'] === 'in') {
                    $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$newAmount, $newAccountId]);
                } else {
                    $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$newAmount, $newAccountId]);
                }
            }

            $db->execute(
                "UPDATE payments SET amount=?, date=?, account_id=?, notes=?, party_id=? WHERE id=?",
                [
                    $newAmount,
                    $this->input('date') ?: $payment['date'],
                    $newAccountId,
                    $this->input('notes') ?: null,
                    $newPartyId ?: $payment['party_id'],
                    $id,
                ]
            );

            // Update linked sale/purchase balances when amount changes (warehouse-scoped)
            if ($amountChanged && $payment['ref_id'] > 0) {
                $diff = $newAmount - $oldAmount;
                $whId = Auth::warehouseId();
                if ($payment['ref_type'] === 'sale') {
                    $db->execute(
                        "UPDATE sales SET paid_amount = paid_amount + ?, balance = GREATEST(0, balance - ?),
                         status = CASE WHEN GREATEST(0, balance - ?) < 0.001 THEN 'paid'
                                       WHEN paid_amount + ? > 0 THEN 'partial' ELSE status END
                         WHERE id = ? AND warehouse_id = ?",
                        [$diff, $diff, $diff, $diff, $payment['ref_id'], $whId]
                    );
                } elseif ($payment['ref_type'] === 'purchase') {
                    $db->execute(
                        "UPDATE purchases SET paid_amount = paid_amount + ?, balance = GREATEST(0, balance - ?),
                         status = CASE WHEN GREATEST(0, balance - ?) < 0.001 THEN 'paid'
                                       WHEN paid_amount + ? > 0 THEN 'partial' ELSE status END
                         WHERE id = ? AND warehouse_id = ?",
                        [$diff, $diff, $diff, $diff, $payment['ref_id'], $whId]
                    );
                }
            }

            $db->commit();

            $logMsg = "Edited {$payment['payment_no']}";
            if ($amountChanged) {
                $logMsg .= " — Amount changed from " . number_format($oldAmount, 3) . " to " . number_format($newAmount, 3);
            }
            if ($partyChanged) {
                $newParty = $db->fetchOne("SELECT name FROM parties WHERE id = ?", [$newPartyId]);
                $logMsg .= " — Party changed from {$payment['party_name']} to " . ($newParty['name'] ?? 'Unknown');
            }
            $this->logActivity('edit_payment', 'payments', $id, $logMsg);
            $this->flash('success', "Payment {$payment['payment_no']} updated.");

        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed to update: ' . $e->getMessage());
        }

        // Redirect to print if requested
        if ($this->input('print_after_save') === '1') {
            $this->redirect("?page=payments&action=print&id={$id}");
            return;
        }

        $this->redirect("?page=payments&action=detail&id={$id}");
    }
}
