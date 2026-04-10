<?php

require_once __DIR__ . '/BaseController.php';

class DiscountController extends BaseController {

    public function index(): void {
        Auth::authorize('settings', 'view');
        $db = Database::getInstance();

        $discounts = $db->fetchAll(
            "SELECT d.*, p.name as party_name, i.name as item_name, u.name as created_by_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN items i ON i.id = d.item_id
             LEFT JOIN users u ON u.id = d.created_by
             ORDER BY d.id DESC LIMIT 100"
        );

        $parties = $db->fetchAll(
            "SELECT id, name, phone FROM parties WHERE is_active = 1 AND (type = 'customer' OR type = 'both') ORDER BY name"
        );

        $items = $db->fetchAll(
            "SELECT id, name FROM items WHERE is_active = 1 ORDER BY name"
        );

        $pageTitle = 'Customer Discounts';
        $page      = 'discounts';

        ob_start();
        include __DIR__ . '/../views/settings/discounts.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('settings', 'add');
        if (!$this->isPost()) { $this->redirect('?page=discounts'); }

        $partyId = $this->inputInt('party_id');
        $itemId  = $this->inputInt('item_id') ?: null;
        $amount  = $this->inputFloat('amount');
        $reason  = $this->input('reason');
        $date    = $this->input('date') ?: date('Y-m-d');

        if (!$partyId || $amount <= 0) {
            $this->flash('error', 'Customer and amount are required.');
            $this->redirect('?page=discounts');
        }

        $db = Database::getInstance();

        $last = $db->fetchOne("SELECT discount_no FROM customer_discounts ORDER BY id DESC LIMIT 1");
        $num  = $last ? (int) substr($last['discount_no'], 5) : 0;
        $discountNo = 'DISC-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

        $db->beginTransaction();
        try {
            $db->insert(
                "INSERT INTO customer_discounts (discount_no, party_id, item_id, amount, reason, date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$discountNo, $partyId, $itemId, $amount, $reason, $date, Auth::id()]
            );

            // Create payment record with ref_type='discount' — reduces customer balance
            // but does NOT affect account balances (no real money received)
            $payLast = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $payNum  = $payLast ? (int) substr($payLast['payment_no'], 4) : 0;
            $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);

            // Use first active account (required field, but won't affect balance)
            $acc = $db->fetchOne("SELECT id FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
            $accId = $acc['id'] ?? 1;

            $db->insert(
                "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, account_id, amount, payment_type, payment_method, date, notes, created_by)
                 VALUES (?, 'discount', 0, ?, ?, ?, 'in', 'cash', ?, ?, ?)",
                [$payNo, $partyId, $accId, $amount, $date, 'Discount ' . $discountNo . ($reason ? ' — ' . $reason : ''), Auth::id()]
            );

            $db->commit();
            $this->flash('success', "Discount {$discountNo} — " . APP_CURRENCY . " " . number_format($amount, DECIMAL_PLACES) . " applied.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed: ' . $e->getMessage());
        }

        $this->redirect('?page=discounts');
    }

    public function delete(): void {
        Auth::authorize('settings', 'delete');
        if (!$this->isPost()) { $this->redirect('?page=discounts'); }

        $id = $this->inputInt('id');
        $db = Database::getInstance();

        $disc = $db->fetchOne("SELECT * FROM customer_discounts WHERE id = ?", [$id]);
        if (!$disc) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
        }

        $db->beginTransaction();
        try {
            $db->execute(
                "DELETE FROM payments WHERE notes LIKE ? AND party_id = ? AND amount = ? LIMIT 1",
                ['%' . $disc['discount_no'] . '%', $disc['party_id'], $disc['amount']]
            );
            $db->execute("DELETE FROM customer_discounts WHERE id = ?", [$id]);
            $db->commit();
            $this->flash('success', 'Discount reversed and removed.');
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed to delete.');
        }

        $this->redirect('?page=discounts');
    }

    public function edit(): void {
        Auth::authorize('settings', 'edit');
        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $discount = $db->fetchOne(
            "SELECT d.*, p.name as party_name, i.name as item_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN items i ON i.id = d.item_id
             WHERE d.id = ?", [$id]
        );
        if (!$discount) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
        }

        $parties = $db->fetchAll(
            "SELECT id, name FROM parties WHERE is_active = 1 AND (type = 'customer' OR type = 'both') ORDER BY name"
        );
        $items = $db->fetchAll("SELECT id, name FROM items WHERE is_active = 1 ORDER BY name");

        $pageTitle = 'Edit Discount';
        $page      = 'discounts';

        ob_start();
        include __DIR__ . '/../views/settings/discount_edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorize('settings', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=discounts'); }

        $id      = $this->inputInt('id');
        $partyId = $this->inputInt('party_id');
        $itemId  = $this->inputInt('item_id') ?: null;
        $amount  = $this->inputFloat('amount');
        $reason  = $this->input('reason');
        $date    = $this->input('date') ?: date('Y-m-d');

        if (!$partyId || $amount <= 0) {
            $this->flash('error', 'Customer and amount are required.');
            $this->redirect('?page=discounts&action=edit&id=' . $id);
        }

        $db   = Database::getInstance();
        $disc = $db->fetchOne("SELECT * FROM customer_discounts WHERE id = ?", [$id]);
        if (!$disc) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
        }

        $db->beginTransaction();
        try {
            // Update discount record
            $db->execute(
                "UPDATE customer_discounts SET party_id=?, item_id=?, amount=?, reason=?, date=? WHERE id=?",
                [$partyId, $itemId, $amount, $reason, $date, $id]
            );

            // Update associated payment
            $db->execute(
                "UPDATE payments SET party_id=?, amount=?, date=?, notes=? WHERE notes LIKE ? AND party_id=? LIMIT 1",
                [
                    $partyId, $amount, $date,
                    'Discount ' . $disc['discount_no'] . ($reason ? ' — ' . $reason : ''),
                    '%' . $disc['discount_no'] . '%',
                    $disc['party_id']
                ]
            );

            $db->commit();
            $this->flash('success', "Discount {$disc['discount_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Failed to update: ' . $e->getMessage());
        }

        $this->redirect('?page=discounts');
    }

    public function print(): void {
        Auth::authorize('settings', 'view');
        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $discount = $db->fetchOne(
            "SELECT d.*, p.name as party_name, p.phone as party_phone, i.name as item_name, u.name as created_by_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN items i ON i.id = d.item_id
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.id = ?", [$id]
        );
        if (!$discount) { die('Discount not found'); }

        // Get customer remaining balance
        $balRow = $db->fetchOne(
            "SELECT p.opening_balance
                + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled'), 0)
                - COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount')), 0)
                - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved'), 0)
                as net_balance
             FROM parties p WHERE p.id = ?",
            [$discount['party_id']]
        );
        $remainingBalance = max(0, (float)($balRow['net_balance'] ?? 0));

        $company = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'company_name'");
        $companyName = $company['value'] ?? 'Iqbal Sons';

        include __DIR__ . '/../views/settings/discount_print.php';
        exit;
    }
}
