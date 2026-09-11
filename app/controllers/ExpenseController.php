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

        $search     = $this->inputSearch('search', '', 'get');
        $categoryId = $this->inputInt('category_id', 0, 'get');
        $hasEntity  = ($search !== '') || ($categoryId > 0);

        $dateRange = ListPage::resolveDateFiltersFromGet();
        if ($hasEntity && !empty($dateRange['dates_defaulted'])) {
            $dateRange = [
                'from_date'       => '',
                'to_date'         => '',
                'all_dates'       => true,
                'dates_defaulted' => false,
            ];
        }

        $filters = [
            'search'      => $search,
            'category_id' => $categoryId,
            'from_date'   => $dateRange['from_date'],
            'to_date'     => $dateRange['to_date'],
            'all_dates'   => $dateRange['all_dates'],
        ];

        $listPage  = $this->expenseModel->getIndexPage($filters, Expense::INDEX_LIST_LIMIT);
        $expenses  = $listPage['items'];
        $categories = $this->expenseModel->getCategories();

        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd   = date('Y-m-d');
        $lastMonthStart  = date('Y-m-01', strtotime('first day of last month'));
        $lastMonthEnd    = date('Y-m-t', strtotime('last month'));
        $monthTotals      = $this->expenseModel->sumThisAndLastMonth(
            $thisMonthStart, $thisMonthEnd, $lastMonthStart, $lastMonthEnd
        );
        $expenseThisMonth = $monthTotals['this_month'];
        $expenseLastMonth = $monthTotals['last_month'];
        $accounts   = self::getAccounts();
        $pageTitle  = 'Expenses';
        $page       = 'expenses';
        $skipListAssets = true;

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

        $when      = $this->resolveExpenseDateTime($this->input('date'), $this->input('time'));
        $date      = $when['date'];
        $createdAt = $when['created_at'];
        $accountId = $this->inputInt('account_id');
        $rows      = $_POST['rows'] ?? [];

        if (empty($rows) || !$accountId) {
            $this->flash('error', 'Please add at least one expense row.');
            $this->redirect('?page=expenses&new=1');
        }

        // Single transaction for the whole batch: either every row (and its account
        // deduction) is saved, or none are. Previously each row committed on its own,
        // so a mid-batch failure left partial deductions applied.
        $db       = Database::getInstance();
        $saved    = 0;
        $savedIds = [];
        $db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $amount = (float)($row['amount'] ?? 0);
                if ($amount <= 0) continue;

                $id = $this->expenseModel->createInTransaction([
                    'category_id' => (int)($row['category_id'] ?? 0) ?: null,
                    'account_id'  => $accountId,
                    'amount'      => $amount,
                    'date'        => $date,
                    'created_at'  => $createdAt,
                    'description' => trim($row['description'] ?? ''),
                ]);

                if ($id) {
                    $savedIds[] = (int)$id;
                    $saved++;
                }
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Expense batch store failed: ' . $e->getMessage());
            $this->flash('error', 'Save failed — no expenses were recorded. Please try again.');
            $this->redirect('?page=expenses');
            return;
        }

        foreach ($savedIds as $sid) {
            $this->logActivity('create_expense', 'expenses', $sid);
        }

        if ($saved > 0) {
            self::clearDashboardCache(Auth::warehouseId());
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
        $result = $this->expenseModel->delete($id);
        if (!$result) {
            $this->flash('warning', 'Expense not found or already deleted.');
        } else {
            $this->logActivity('delete_expense', 'expenses', $id);
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', 'Expense deleted.');
        }
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
             WHERE e.id = ? AND e.warehouse_id = ?", [$id, Auth::warehouseId()]
        );
        if (!$expense) { $this->flash('error', 'Expense not found.'); $this->redirect('?page=expenses'); return; }

        $categories = $this->expenseModel->getCategories();
        $accounts   = self::getAccounts();
        $returnAccountId = $this->inputInt('return_account_id', 0, 'get');
        $accountsReturnUrl = $returnAccountId > 0 ? '?page=accounts&account_id=' . $returnAccountId : '';
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
        $returnAccountId = $this->inputInt('return_account_id') ?: $this->inputInt('return_account_id', 0, 'get');
        $editReturnQs = $returnAccountId > 0 ? '&return_account_id=' . $returnAccountId : '';
        $db = Database::getInstance();

        // Zero/negative amounts would inflate the account balance in the diff math below.
        $newAmount = $this->inputFloat('amount');
        if ($newAmount <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=expenses&action=edit&id=' . $id . $editReturnQs);
            return;
        }

        $db->beginTransaction();
        try {
            $old = $db->fetchOne(
                "SELECT * FROM expenses WHERE id = ? AND warehouse_id = ? FOR UPDATE",
                [$id, Auth::warehouseId()]
            );
            if (!$old) { 
                $db->rollback();
                $this->flash('error', 'Expense not found.'); 
                $this->redirect('?page=expenses'); 
                return;
            }

            $oldAmount    = (float)$old['amount'];
            $newAccountId = $this->inputInt('account_id') ?: (int)$old['account_id'];
            $oldAccountId = (int)$old['account_id'];

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

            $when = $this->resolveExpenseDateTime(
                $this->input('date'),
                $this->input('time'),
                (string) ($old['date'] ?? ''),
                (string) ($old['created_at'] ?? '')
            );

            $db->execute(
                "UPDATE expenses SET category_id=?, account_id=?, amount=?, date=?, description=?, created_at=? WHERE id=?",
                [
                    $this->inputInt('category_id') ?: null,
                    $newAccountId,
                    $newAmount,
                    $when['date'],
                    $this->input('description'),
                    $when['created_at'],
                    $id,
                ]
            );

            $db->commit();
            $this->logActivity('edit_expense', 'expenses', $id, "Edited {$old['expense_no']}");
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', "Expense {$old['expense_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Expense update failed (id={$id}): " . $e->getMessage());
            $this->flash('error', 'Failed to update expense. Please try again or check server logs.');
        }

        if ($returnAccountId > 0) {
            $this->redirect('?page=accounts&account_id=' . $returnAccountId);
            return;
        }

        $this->redirect('?page=expenses');
    }

    /**
     * Combine posted date + time into DATE + DATETIME for expenses.date / created_at.
     * @return array{date:string,created_at:string}
     */
    private function resolveExpenseDateTime(string $date, string $time, string $fallbackDate = '', string $fallbackDateTime = ''): array {
        $date = trim($date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $fallbackDay = substr($fallbackDate, 0, 10);
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fallbackDay) ? $fallbackDay : date('Y-m-d');
        }

        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }
        if (!preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            $fromFallback = $fallbackDateTime !== '' ? strtotime($fallbackDateTime) : false;
            $time = $fromFallback ? date('H:i:s', $fromFallback) : date('H:i:s');
        }
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $m)) {
            $hour = max(0, min(23, (int) $m[1]));
            $min  = max(0, min(59, (int) $m[2]));
            $sec  = max(0, min(59, (int) $m[3]));
            $time = sprintf('%02d:%02d:%02d', $hour, $min, $sec);
        }

        return [
            'date'       => $date,
            'created_at' => $date . ' ' . $time,
        ];
    }
}
