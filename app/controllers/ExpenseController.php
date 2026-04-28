<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Expense.php';

class ExpenseController extends BaseController {
    private Expense $expenseModel;

    public function __construct() {
        parent::__construct();
        $this->expenseModel = new Expense();
    }

    public function index(): void {
        Auth::authorize('expenses', 'view');
        $filters = [
            'search'      => $this->input('search', '', 'get'),
            'category_id' => $this->inputInt('category_id', 0, 'get'),
            'from_date'   => $this->input('from_date', date('Y-m-01'), 'get'),
            'to_date'     => $this->input('to_date', date('Y-m-d'), 'get'),
        ];
        $expenses   = $this->expenseModel->getAll($filters);
        $categories = $this->expenseModel->getCategories();
        $summary    = $this->expenseModel->getSummaryByCategory($filters['from_date'], $filters['to_date']);
        $totalAmt   = array_sum(array_column($expenses, 'amount'));
        $db         = Database::getInstance();
        $accounts   = self::getAccounts();
        $pageTitle  = 'Expenses';
        $page       = 'expenses';

        // One-time token to prevent double-submit bulk expense save
        $_SESSION['expense_form_nonce'] = bin2hex(random_bytes(16));
        $expenseFormNonce               = $_SESSION['expense_form_nonce'];

        ob_start();
        include __DIR__ . '/../views/expenses/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('expenses', 'add');
        if (!$this->isPost()) { $this->redirect('?page=expenses'); }

        $postedNonce = isset($_POST['expense_form_nonce']) ? trim((string)$_POST['expense_form_nonce']) : '';
        $sessNonce   = $_SESSION['expense_form_nonce'] ?? '';
        if ($sessNonce === '' || !hash_equals($sessNonce, $postedNonce)) {
            $this->flash('warning', 'This expense form was already submitted or expired. Please check Expenses list before trying again.');
            $this->redirect('?page=expenses');
        }
        unset($_SESSION['expense_form_nonce']);

        $date      = $this->input('date') ?: date('Y-m-d');
        $accountId = $this->inputInt('account_id');
        $rows      = $_POST['rows'] ?? [];

        if (empty($rows) || !$accountId) {
            $this->flash('error', 'Please add at least one expense row.');
            $this->redirect('?page=expenses&new=1');
        }

        $saved = 0;
        foreach ($rows as $row) {
            $amount = (float)($row['amount'] ?? 0);
            if ($amount <= 0) continue;

            $id = $this->expenseModel->create([
                'category_id' => (int)($row['category_id'] ?? 0) ?: null,
                'account_id'  => $accountId,
                'amount'      => $amount,
                'date'        => $date,
                'description' => trim($row['description'] ?? ''),
            ]);

            if ($id) {
                $this->logActivity('create_expense', 'expenses', (int)$id);
                $saved++;
            }
        }

        if ($saved > 0) {
            $this->flash('success', "{$saved} expense(s) recorded successfully.");
        } else {
            $this->flash('error', 'No expenses saved. Check amounts.');
        }
        $this->redirect('?page=expenses');
    }

    public function delete(): void {
        Auth::authorize('expenses', 'delete');

        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=expenses');
            return;
        }

        $id = $this->inputInt('id');
        $this->expenseModel->delete($id);
        $this->logActivity('delete_expense', 'expenses', $id);
        $this->flash('success', 'Expense deleted.');
        $this->redirect('?page=expenses');
    }

    public function edit(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=expenses'); return; }

        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $expense = $db->fetchOne(
            "SELECT e.*, ec.name as category_name, a.name as account_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN accounts a ON a.id = e.account_id
             WHERE e.id = ?", [$id]
        );
        if (!$expense) { $this->flash('error', 'Expense not found.'); $this->redirect('?page=expenses'); }

        $categories = $this->expenseModel->getCategories();
        $accounts   = self::getAccounts();
        $pageTitle  = 'Edit Expense: ' . $expense['expense_no'];
        $page       = 'expenses';

        ob_start();
        include __DIR__ . '/../views/expenses/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        if (!Auth::isAdmin()) { $this->flash('error', 'Admin only.'); $this->redirect('?page=expenses'); return; }
        if (!$this->isPost()) { $this->redirect('?page=expenses'); return; }

        $id = $this->inputInt('id');
        $db = Database::getInstance();
        $old = $this->expenseModel->find($id);
        if (!$old) { $this->flash('error', 'Expense not found.'); $this->redirect('?page=expenses'); }

        $newAmount    = $this->inputFloat('amount');
        $oldAmount    = (float)$old['amount'];
        $newAccountId = $this->inputInt('account_id') ?: (int)$old['account_id'];
        $oldAccountId = (int)$old['account_id'];

        $db->beginTransaction();
        try {
            if ($newAccountId !== $oldAccountId) {
                // Account changed: restore full amount to old account, deduct full amount from new account
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [$oldAmount, $oldAccountId]
                );
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$newAmount, $newAccountId]
                );
            } elseif (abs($newAmount - $oldAmount) > 0.001) {
                // Same account, amount changed: apply the diff
                $diff = $newAmount - $oldAmount;
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$diff, $oldAccountId]
                );
            }

            $db->execute(
                "UPDATE expenses SET category_id=?, account_id=?, amount=?, date=?, description=? WHERE id=?",
                [
                    $this->inputInt('category_id') ?: null,
                    $newAccountId,
                    $newAmount,
                    $this->input('date') ?: $old['date'],
                    $this->input('description'),
                    $id,
                ]
            );

            $db->commit();
            $this->logActivity('edit_expense', 'expenses', $id, "Edited {$old['expense_no']}");
            $this->flash('success', "Expense {$old['expense_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed: ' . $e->getMessage());
        }

        $this->redirect('?page=expenses');
    }
}
