<?php

require_once __DIR__ . '/BaseController.php';

class AccountController extends BaseController {

    public function index(): void {
        Auth::authorize('settings', 'view');

        $db       = Database::getInstance();
        $accounts = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");

        // Recent transfers
        $transfers = $db->fetchAll(
            "SELECT t.*, 
                    fa.name as from_name, fa.type as from_type,
                    ta.name as to_name,   ta.type as to_type,
                    u.name  as created_by_name
             FROM account_transfers t
             JOIN accounts fa ON fa.id = t.from_account_id
             JOIN accounts ta ON ta.id = t.to_account_id
             LEFT JOIN users u ON u.id = t.created_by
             ORDER BY t.created_at DESC
             LIMIT 50"
        );

        $pageTitle = 'Accounts';
        $page      = 'accounts';

        ob_start();
        include __DIR__ . '/../views/settings/accounts.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=accounts'); }

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO accounts (name, type, current_balance) VALUES (?,?,?)",
            [$this->input('name'), $this->input('type'), $this->inputFloat('opening_balance')]
        );

        $this->flash('success', 'Account created.');
        $this->redirect('?page=accounts');
    }

    public function transfer(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=accounts'); }

        $db     = Database::getInstance();
        $fromId = $this->inputInt('from_account_id');
        $toId   = $this->inputInt('to_account_id');
        $amount = $this->inputFloat('amount');
        $date   = $this->input('date') ?: date('Y-m-d');
        $notes  = $this->input('notes');

        if ($fromId === $toId) {
            $this->flash('error', 'Cannot transfer to the same account.');
            $this->redirect('?page=accounts');
        }
        if ($amount <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=accounts');
        }

        // Check source balance
        $from = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$fromId]);
        if (!$from) { $this->flash('error', 'Source account not found.'); $this->redirect('?page=accounts'); }
        if ($from['current_balance'] < $amount) {
            $this->flash('error', "Insufficient balance in {$from['name']}. Available: " . number_format($from['current_balance'], 3));
            $this->redirect('?page=accounts');
        }

        $db->beginTransaction();
        try {
            // Generate transfer number (locked inside transaction)
            $last = $db->fetchOne("SELECT transfer_no FROM account_transfers ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $num  = $last ? (int) substr($last['transfer_no'], 4) + 1 : 1;
            $transferNo = 'TRF-' . str_pad($num, 6, '0', STR_PAD_LEFT);

            // Debit from source, credit to destination
            $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$amount, $fromId]);
            $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$amount, $toId]);

            // Record the transfer
            $db->insert(
                "INSERT INTO account_transfers (transfer_no, from_account_id, to_account_id, amount, date, notes, created_by)
                 VALUES (?,?,?,?,?,?,?)",
                [$transferNo, $fromId, $toId, $amount, $date, $notes, Auth::id()]
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Transfer failed. Please try again.');
            $this->redirect('?page=accounts');
        }

        $to = $db->fetchOne("SELECT name FROM accounts WHERE id = ?", [$toId]);
        $this->flash('success', "Transfer {$transferNo} done. " . number_format($amount, 3) . " moved from {$from['name']} to {$to['name']}.");
        $this->redirect('?page=accounts');
    }

    public function adjust(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=accounts'); return; }

        $db        = Database::getInstance();
        $accountId = $this->inputInt('account_id');
        $amount    = $this->inputFloat('amount');
        $type      = $this->input('adjust_type'); // 'add' or 'subtract'
        $reason    = $this->input('reason');

        if ($amount <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=accounts');
            return;
        }

        $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
        if (!$account) {
            $this->flash('error', 'Account not found.');
            $this->redirect('?page=accounts');
            return;
        }

        if ($type === 'subtract' && (float)$account['current_balance'] < $amount) {
            $this->flash('error', "Insufficient balance. Available: " . APP_CURRENCY . " " . number_format($account['current_balance'], DECIMAL_PLACES));
            $this->redirect('?page=accounts');
            return;
        }

        $db->beginTransaction();
        try {
            if ($type === 'add') {
                $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$amount, $accountId]);
            } else {
                $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$amount, $accountId]);
            }

            $this->logActivity('adjust_account', 'accounts', $accountId,
                ($type === 'add' ? '+' : '-') . number_format($amount, DECIMAL_PLACES) . " on {$account['name']}" . ($reason ? " — {$reason}" : ''));

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Adjustment failed.');
            $this->redirect('?page=accounts');
            return;
        }

        $newBal = $db->fetchOne("SELECT current_balance FROM accounts WHERE id = ?", [$accountId]);
        $this->flash('success',
            "Balance adjusted: {$account['name']} " .
            ($type === 'add' ? '+' : '-') . " " . APP_CURRENCY . " " . number_format($amount, DECIMAL_PLACES) .
            ". New balance: " . APP_CURRENCY . " " . number_format($newBal['current_balance'], DECIMAL_PLACES)
        );
        $this->redirect('?page=accounts');
    }

    public function delete(): void {
        Auth::authorize('settings', 'delete');

        // AUDIT FIX S5: Require POST for destructive action
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=accounts');
            return;
        }

        $id = $this->inputInt('id');
        $db = Database::getInstance();

        $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$id]);
        if (!$account) {
            $this->flash('error', 'Account not found.');
            $this->redirect('?page=accounts');
        }

        // Check for linked transactions
        $payments = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM payments WHERE account_id = ?", [$id]
        )['c'] ?? 0);

        $expenses = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM expenses WHERE account_id = ?", [$id]
        )['c'] ?? 0);

        $transfersFrom = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM account_transfers WHERE from_account_id = ?", [$id]
        )['c'] ?? 0);

        $transfersTo = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM account_transfers WHERE to_account_id = ?", [$id]
        )['c'] ?? 0);

        $total = $payments + $expenses + $transfersFrom + $transfersTo;

        if ($total > 0) {
            $details = [];
            if ($payments)     $details[] = "{$payments} payment(s)";
            if ($expenses)     $details[] = "{$expenses} expense(s)";
            if ($transfersFrom + $transfersTo > 0) $details[] = ($transfersFrom + $transfersTo) . " transfer(s)";
            $this->flash('error', "Cannot delete \"{$account['name']}\" — it has " . implode(', ', $details) . " linked to it.");
            $this->redirect('?page=accounts');
        }

        $db->execute("DELETE FROM accounts WHERE id = ?", [$id]);
        $this->flash('success', "Account \"{$account['name']}\" deleted.");
        $this->redirect('?page=accounts');
    }
}
