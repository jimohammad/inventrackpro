<?php

require_once __DIR__ . '/BaseController.php';

class AccountController extends BaseController {

    public function index(): void {
        Auth::authorize('settings', 'view');

        $db       = Database::getInstance();
        $accounts = $db->fetchAll("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name");
        foreach ($accounts as &$accRow) {
            $accRow['normalized_type'] = parent::normalizeAccountType(
                (string) ($accRow['type'] ?? ''),
                (string) ($accRow['name'] ?? '')
            );
        }
        unset($accRow);

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

        // Account transaction ledger
        $selectedAccountId = (int)($this->input('account_id', 0, 'get'));
        $selectedAccount   = null;
        $accountTxns       = [];

        // One-time token to prevent double-submit transfers
        $_SESSION['account_transfer_nonce'] = bin2hex(random_bytes(16));
        $accountTransferNonce               = $_SESSION['account_transfer_nonce'];

        $editingTransfer = null;
        $editTransferId  = $this->inputInt('edit_transfer', 0, 'get');
        if ($editTransferId > 0) {
            if (!Auth::can('settings', 'edit')) {
                $this->flash('error', 'You do not have permission to edit transfers.');
                $this->redirect('?page=accounts');
            }
            $editingTransfer = $db->fetchOne(
                "SELECT t.*, fa.name as from_name, ta.name as to_name
                 FROM account_transfers t
                 JOIN accounts fa ON fa.id = t.from_account_id
                 JOIN accounts ta ON ta.id = t.to_account_id
                 WHERE t.id = ?",
                [$editTransferId]
            );
            if (!$editingTransfer) {
                $this->flash('error', 'Transfer not found.');
                $this->redirect('?page=accounts');
            }
        }

        if ($selectedAccountId) {
            $selectedAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$selectedAccountId]);
            if ($selectedAccount) {
                // Payments linked to this account
                $payments = $db->fetchAll(
                    "SELECT 'payment' as txn_type, p.id, p.payment_no as ref_no,
                            p.date,
                            CASE WHEN p.payment_type = 'out' THEN -p.amount ELSE p.amount END as amount,
                            CONCAT(UPPER(p.ref_type), ' / ', UPPER(p.payment_type)) as note,
                            COALESCE(pa.name, '—') as party,
                            NULL as invoice_ref
                     FROM payments p
                     LEFT JOIN parties pa ON pa.id = p.party_id
                     WHERE p.account_id = ? AND p.ref_type != 'discount'
                     ORDER BY p.date DESC, p.id DESC
                     LIMIT 200",
                    [$selectedAccountId]
                );

                // Expenses from this account
                $expenses = $db->fetchAll(
                    "SELECT 'expense' as txn_type, e.id, e.expense_no as ref_no,
                            e.date, e.amount, e.description as note,
                            COALESCE(ec.name, '—') as party,
                            NULL as invoice_ref
                     FROM expenses e
                     LEFT JOIN expense_categories ec ON ec.id = e.category_id
                     WHERE e.account_id = ?
                     ORDER BY e.date DESC, e.id DESC
                     LIMIT 200",
                    [$selectedAccountId]
                );

                // Transfers involving this account
                $txnTransfers = $db->fetchAll(
                    "SELECT 'transfer' as txn_type, t.id,
                            t.transfer_no as ref_no, t.date,
                            CASE WHEN t.from_account_id = ? THEN -t.amount ELSE t.amount END as amount,
                            t.notes as note,
                            CASE WHEN t.from_account_id = ? THEN ta.name ELSE fa.name END as party,
                            NULL as invoice_ref
                     FROM account_transfers t
                     JOIN accounts fa ON fa.id = t.from_account_id
                     JOIN accounts ta ON ta.id = t.to_account_id
                     WHERE t.from_account_id = ? OR t.to_account_id = ?
                     ORDER BY t.date DESC, t.id DESC
                     LIMIT 200",
                    [$selectedAccountId, $selectedAccountId, $selectedAccountId, $selectedAccountId]
                );

                // PO payments not yet in payments table (historical + any without payment record)
                $poPayments = $db->fetchAll(
                    "SELECT 'po_payment' as txn_type, po.id,
                            po.po_no as ref_no, po.date,
                            -po.paid_kwd as amount,
                            CONCAT(po.currency, ' @ ', po.exchange_rate) as note,
                            p.name as party,
                            NULL as invoice_ref
                     FROM purchase_orders po
                     JOIN parties p ON p.id = po.party_id
                     LEFT JOIN payments pay ON pay.ref_type = 'purchase_order' AND pay.ref_id = po.id
                     LEFT JOIN payments pay2 ON pay2.ref_type = 'purchase' AND pay2.ref_id = po.converted_to
                     LEFT JOIN payments pay3 ON pay3.ref_type = 'purchase'
                        AND pay3.party_id = po.party_id
                        AND ABS(pay3.amount - po.paid_kwd) < 0.001
                        AND pay3.date BETWEEN DATE_SUB(po.date, INTERVAL 1 DAY) AND DATE_ADD(po.date, INTERVAL 1 DAY)
                     WHERE po.account_id = ? AND po.paid_kwd > 0
                      AND po.status NOT IN ('cancelled')
                      AND pay.id IS NULL
                      AND pay2.id IS NULL
                      AND pay3.id IS NULL
                     ORDER BY po.date DESC, po.id DESC
                     LIMIT 200",
                    [$selectedAccountId]
                );

                $balanceAdjustments = $db->fetchAll(
                    "SELECT 'adjustment' as txn_type, aba.id,
                            CONCAT('ADJ-', LPAD(aba.id, 6, '0')) as ref_no,
                            aba.date,
                            CASE WHEN aba.direction = 'add' THEN aba.amount ELSE -aba.amount END as amount,
                            TRIM(CONCAT(UPPER(aba.direction), CASE WHEN aba.reason IS NOT NULL AND aba.reason != '' THEN CONCAT(' — ', aba.reason) ELSE '' END)) as note,
                            'Manual adjustment' as party,
                            NULL as invoice_ref
                     FROM account_balance_adjustments aba
                     WHERE aba.account_id = ?
                     ORDER BY aba.date DESC, aba.id DESC
                     LIMIT 200",
                    [$selectedAccountId]
                );

                $accountTxns = array_merge($payments, $expenses, $txnTransfers, $poPayments, $balanceAdjustments);
                usort($accountTxns, fn($a, $b) => strcmp($b['date'] . $b['id'], $a['date'] . $a['id']));
                $accountTxns = array_slice($accountTxns, 0, 200);
            }
        }

        $pageTitle = 'Accounts';
        $page      = 'accounts';

        ob_start();
        include __DIR__ . '/../views/settings/accounts.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Admin/authorized tool: rebuild accounts.current_balance from ledger tables.
     * Uses accounts.opening_balance as baseline, plus payments, expenses, transfers,
     * account_balance_adjustments, and PO paid_kwd booked on orders without matching payments rows.
     */
    public function recalcBalance(): void {
        Auth::authorize('settings', 'edit');

        if (!$this->isPost()) {
            $this->redirect('?page=accounts');
        }

        $accountId = $this->inputInt('account_id');
        if ($accountId <= 0) {
            $this->flash('error', 'Invalid account.');
            $this->redirect('?page=accounts');
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $acc = $db->fetchOne("SELECT * FROM accounts WHERE id = ? FOR UPDATE", [$accountId]);
            if (!$acc) {
                throw new Exception('Account not found.');
            }

            $opening = (float)($acc['opening_balance'] ?? 0);

            $payRow = $db->fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN payment_type='out' THEN -amount ELSE amount END),0) as net
                 FROM payments
                 WHERE account_id = ? AND ref_type != 'discount'",
                [$accountId]
            );
            $paymentsNet = (float)($payRow['net'] ?? 0);

            $expRow = $db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) as total
                 FROM expenses
                 WHERE account_id = ?",
                [$accountId]
            );
            $expensesTotal = (float)($expRow['total'] ?? 0);

            $trRow = $db->fetchOne(
                "SELECT
                    COALESCE((SELECT SUM(amount) FROM account_transfers WHERE to_account_id = ?),0) as in_total,
                    COALESCE((SELECT SUM(amount) FROM account_transfers WHERE from_account_id = ?),0) as out_total",
                [$accountId, $accountId]
            );
            $transferNet = (float)($trRow['in_total'] ?? 0) - (float)($trRow['out_total'] ?? 0);

            $adjRow = $db->fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN direction = 'add' THEN amount WHEN direction = 'subtract' THEN -amount END), 0) AS net
                 FROM account_balance_adjustments
                 WHERE account_id = ?",
                [$accountId]
            );
            $adjustmentsNet = (float)($adjRow['net'] ?? 0);

            // PO payouts recorded on PO only until a mirror payment exists (aligned with reconciliation & Accounts ledger)
            $poRow = $db->fetchOne(
                "SELECT COALESCE(SUM(po.paid_kwd), 0) AS total
                 FROM purchase_orders po
                 LEFT JOIN payments pay ON pay.ref_type = 'purchase_order' AND pay.ref_id = po.id
                 LEFT JOIN payments pay2 ON pay2.ref_type = 'purchase' AND pay2.ref_id = po.converted_to
                 LEFT JOIN payments pay3 ON pay3.ref_type = 'purchase'
                    AND pay3.party_id = po.party_id
                    AND ABS(pay3.amount - po.paid_kwd) < 0.001
                    AND pay3.date BETWEEN DATE_SUB(po.date, INTERVAL 1 DAY) AND DATE_ADD(po.date, INTERVAL 1 DAY)
                 WHERE po.account_id = ? AND po.paid_kwd > 0
                  AND po.status NOT IN ('cancelled')
                  AND pay.id IS NULL AND pay2.id IS NULL AND pay3.id IS NULL",
                [$accountId]
            );
            $poUnlinkedOut = (float)($poRow['total'] ?? 0);

            $newBalance = round($opening + $paymentsNet - $expensesTotal + $transferNet + $adjustmentsNet - $poUnlinkedOut, 3);
            $oldBalance = (float)($acc['current_balance'] ?? 0);

            $db->execute("UPDATE accounts SET current_balance = ? WHERE id = ?", [$newBalance, $accountId]);
            $db->commit();

            $this->logActivity(
                'recalc_account_balance',
                'accounts',
                $accountId,
                "Recalculated {$acc['name']} from ledger: old=" . number_format($oldBalance, 3) . " new=" . number_format($newBalance, 3)
            );

            $this->flash(
                'success',
                "Recalculated {$acc['name']}. Old: " . APP_CURRENCY . " " . number_format($oldBalance, 3) .
                " → New: " . APP_CURRENCY . " " . number_format($newBalance, 3)
            );
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Recalculate failed: ' . $e->getMessage());
        }

        $this->redirect('?page=accounts&account_id=' . $accountId);
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=accounts'); }

        $name = trim($this->input('name'));
        if ($name === '') {
            $this->flash('error', 'Account name is required.');
            $this->redirect('?page=accounts');
        }

        $typeAllowed = ['cash', 'bank', 'mobile_wallet', 'other'];
        $typeRaw     = strtolower(trim($this->input('type', 'cash')));
        $type        = in_array($typeRaw, $typeAllowed, true) ? $typeRaw : 'cash';

        $glRaw = trim($this->input('gl_code'));
        $gl    = $glRaw !== ''
            ? (function_exists('mb_substr') ? mb_substr($glRaw, 0, 10, 'UTF-8') : substr($glRaw, 0, 10))
            : null;

        $opening = round($this->inputFloat('opening_balance'), DECIMAL_PLACES);

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO accounts (name, type, gl_code, opening_balance, current_balance)
             VALUES (?,?,?,?,?)",
            [$name, $type, $gl, $opening, $opening]
        );

        $this->flash('success', 'Account created.');
        $this->redirect('?page=accounts');
    }

    public function transfer(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=accounts'); }

        $db     = Database::getInstance();
        $postedNonce = isset($_POST['account_transfer_nonce']) ? trim((string)$_POST['account_transfer_nonce']) : '';
        $sessNonce   = $_SESSION['account_transfer_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This transfer was already submitted or expired. Please check transfer history before trying again.');
            $this->redirect('?page=accounts');
        }
        unset($_SESSION['account_transfer_nonce']);

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

        $db->beginTransaction();
        try {
            $from = $db->fetchOne("SELECT id, name, current_balance FROM accounts WHERE id = ? FOR UPDATE", [$fromId]);
            $to   = $db->fetchOne("SELECT id, name FROM accounts WHERE id = ? FOR UPDATE", [$toId]);

            if (!$from) {
                $db->rollback();
                $this->flash('error', 'Source account not found.');
                $this->redirect('?page=accounts');
            }
            if (!$to) {
                $db->rollback();
                $this->flash('error', 'Destination account not found.');
                $this->redirect('?page=accounts');
            }
            if ((float) $from['current_balance'] < $amount) {
                $db->rollback();
                $this->flash('error', "Insufficient balance in {$from['name']}. Available: " . number_format($from['current_balance'], DECIMAL_PLACES));
                $this->redirect('?page=accounts');
            }

            // Generate transfer number (locked inside transaction)
            $row = $db->fetchOne(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(transfer_no, 5) AS UNSIGNED)), 0) AS max_no
                 FROM account_transfers
                 WHERE transfer_no LIKE 'TRF-%'
                 FOR UPDATE"
            );
            $num  = (int)($row['max_no'] ?? 0);
            $transferNo = 'TRF-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

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
            $this->flash('success', "Transfer {$transferNo} done. " . number_format($amount, DECIMAL_PLACES) . " moved from {$from['name']} to {$to['name']}.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Transfer failed. Please try again.');
        }

        $this->redirect('?page=accounts');
    }

    /**
     * Edit an existing account transfer: reverse the old movement on balances, then apply the new one.
     * transfer_no is preserved.
     */
    public function updateTransfer(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) {
            $this->redirect('?page=accounts');
        }

        $id                = $this->inputInt('id');
        $fromId            = $this->inputInt('from_account_id');
        $toId              = $this->inputInt('to_account_id');
        $amount            = $this->inputFloat('amount');
        $date              = $this->input('date') ?: date('Y-m-d');
        $notes             = $this->input('notes');
        $returnAccountId   = $this->inputInt('return_account_id');

        $redirectSuffix = $returnAccountId > 0 ? '&account_id=' . $returnAccountId : '';

        if ($id <= 0 || $fromId === $toId || $amount <= 0) {
            $this->flash('error', 'Invalid transfer details.');
            $this->redirect('?page=accounts' . $redirectSuffix);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $old = $db->fetchOne("SELECT * FROM account_transfers WHERE id = ? FOR UPDATE", [$id]);
            if (!$old) {
                $db->rollback();
                $this->flash('error', 'Transfer not found.');
                $this->redirect('?page=accounts' . $redirectSuffix);
            }

            $accIds = array_values(array_unique(array_filter([
                (int) $old['from_account_id'],
                (int) $old['to_account_id'],
                $fromId,
                $toId,
            ])));
            sort($accIds, SORT_NUMERIC);
            foreach ($accIds as $aid) {
                $db->fetchOne("SELECT id FROM accounts WHERE id = ? FOR UPDATE", [$aid]);
            }

            $oldAmtRounded = round((float) $old['amount'], DECIMAL_PLACES);
            $newAmtRounded = round($amount, DECIMAL_PLACES);
            $sameRoute       = (int) $old['from_account_id'] === $fromId && (int) $old['to_account_id'] === $toId;

            if ($sameRoute && $oldAmtRounded === $newAmtRounded) {
                $db->execute(
                    "UPDATE account_transfers SET date = ?, notes = ? WHERE id = ?",
                    [$date, $notes, $id]
                );
                $db->commit();
                $this->logActivity(
                    'edit_account_transfer',
                    'account_transfers',
                    $id,
                    "Updated date/notes for {$old['transfer_no']}"
                );
                $this->flash('success', "Transfer {$old['transfer_no']} updated.");
                $this->redirect('?page=accounts' . $redirectSuffix);
            }

            // Reverse original posting
            $db->execute(
                "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                [$old['amount'], $old['from_account_id']]
            );
            $db->execute(
                "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                [$old['amount'], $old['to_account_id']]
            );

            $fromAcc = $db->fetchOne("SELECT id, name, current_balance FROM accounts WHERE id = ?", [$fromId]);
            if (!$fromAcc) {
                throw new Exception('Source account not found.');
            }
            if ((float) $fromAcc['current_balance'] < $newAmtRounded) {
                throw new Exception(
                    "Insufficient balance in {$fromAcc['name']}. Available: " .
                    number_format((float) $fromAcc['current_balance'], DECIMAL_PLACES)
                );
            }

            $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$amount, $fromId]);
            $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$amount, $toId]);

            $db->execute(
                "UPDATE account_transfers SET from_account_id = ?, to_account_id = ?, amount = ?, date = ?, notes = ? WHERE id = ?",
                [$fromId, $toId, $amount, $date, $notes, $id]
            );

            $db->commit();
            $this->logActivity(
                'edit_account_transfer',
                'account_transfers',
                $id,
                "Updated {$old['transfer_no']} (accounts/amount/date)"
            );
            $this->flash('success', "Transfer {$old['transfer_no']} updated.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect('?page=accounts' . $redirectSuffix);
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

        if ($type !== 'add' && $type !== 'subtract') {
            $this->flash('error', 'Invalid adjustment type.');
            $this->redirect('?page=accounts');
            return;
        }

        $adjDateRaw = trim((string)$this->input('adj_date'));
        $adjDate    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $adjDateRaw) ? $adjDateRaw : date('Y-m-d');
        $reasonTrim = trim($reason) !== '' ? trim($reason) : null;

        $db->beginTransaction();
        try {
            $locked = $db->fetchOne("SELECT id, name, current_balance FROM accounts WHERE id = ? FOR UPDATE", [$accountId]);
            if (!$locked) {
                $db->rollback();
                $this->flash('error', 'Account not found.');
                $this->redirect('?page=accounts');
                return;
            }
            if ($type === 'subtract' && (float) $locked['current_balance'] < $amount) {
                $db->rollback();
                $this->flash('error', "Insufficient balance. Available: " . APP_CURRENCY . " " . number_format($locked['current_balance'], DECIMAL_PLACES));
                $this->redirect('?page=accounts');
                return;
            }

            if ($type === 'add') {
                $db->execute("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?", [$amount, $accountId]);
            } else {
                $db->execute("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?", [$amount, $accountId]);
            }

            $db->insert(
                "INSERT INTO account_balance_adjustments (account_id, direction, amount, reason, date, created_by)
                 VALUES (?,?,?,?,?,?)",
                [$accountId, $type, round($amount, DECIMAL_PLACES), $reasonTrim, $adjDate, Auth::id() ?: null]
            );

            $this->logActivity('adjust_account', 'accounts', $accountId,
                ($type === 'add' ? '+' : '-') . number_format($amount, DECIMAL_PLACES) . " on {$locked['name']}" . ($reasonTrim ? " — {$reasonTrim}" : ''));

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

        $adjustments = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM account_balance_adjustments WHERE account_id = ?", [$id]
        )['c'] ?? 0);

        $purchaseOrders = (int)($db->fetchOne(
            "SELECT COUNT(*) as c FROM purchase_orders WHERE account_id = ?", [$id]
        )['c'] ?? 0);

        $total = $payments + $expenses + $transfersFrom + $transfersTo + $adjustments;

        if ($total > 0 || $purchaseOrders > 0) {
            $details = [];
            if ($payments)     $details[] = "{$payments} payment(s)";
            if ($expenses)     $details[] = "{$expenses} expense(s)";
            if ($transfersFrom + $transfersTo > 0) $details[] = ($transfersFrom + $transfersTo) . " transfer(s)";
            if ($adjustments)  $details[] = "{$adjustments} balance adjustment(s)";
            if ($purchaseOrders) $details[] = "{$purchaseOrders} purchase order(s) linked to this account";
            $this->flash('error', "Cannot delete \"{$account['name']}\" — it has " . implode(', ', $details) . " linked to it.");
            $this->redirect('?page=accounts');
        }

        $db->execute("DELETE FROM accounts WHERE id = ?", [$id]);
        $this->flash('success', "Account \"{$account['name']}\" deleted.");
        $this->redirect('?page=accounts');
    }
}
