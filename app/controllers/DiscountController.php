<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';

class DiscountController extends BaseController {

    public function index(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'view');
        $db = Database::getInstance();

        $discounts = $db->fetchAll(
            "SELECT d.*, p.name as party_name, i.name as item_name, s.invoice_no as sale_invoice_no,
                    u.name as created_by_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN items i ON i.id = d.item_id
             LEFT JOIN sales s ON s.id = d.sale_id
             LEFT JOIN users u ON u.id = d.created_by
             ORDER BY d.id DESC LIMIT 100"
        );

        $parties = $db->fetchAll(
            "SELECT id, name, phone FROM parties WHERE is_active = 1 AND (type = 'customer' OR type = 'both') ORDER BY name"
        );

        $discountFormNonce = bin2hex(random_bytes(16));
        $_SESSION['discount_form_nonce'] = $discountFormNonce;

        $pageTitle = 'Customer Discounts';
        $page      = 'discounts';

        ob_start();
        include __DIR__ . '/../views/settings/discounts.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** JSON: recent invoices for a customer (branch-scoped). */
    public function customerInvoices(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'view');
        $partyId = $this->inputInt('party_id', 0, 'get');
        if ($partyId <= 0) {
            $this->json(['ok' => true, 'invoices' => []]);
            return;
        }

        $db = Database::getInstance();
        $whId = (int) Auth::warehouseId();
        $rows = $db->fetchAll(
            "SELECT id, invoice_no, date, grand_total, balance, status
             FROM sales
             WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'
               AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             ORDER BY id DESC
             LIMIT 80",
            [$partyId, $whId]
        );

        $invoices = [];
        foreach ($rows as $r) {
            $invoices[] = [
                'id'          => (int) $r['id'],
                'invoice_no'  => (string) $r['invoice_no'],
                'date'        => date('d M Y', strtotime($r['date'])),
                'grand_total' => (float) $r['grand_total'],
                'balance'     => (float) $r['balance'],
                'status'      => (string) $r['status'],
            ];
        }
        $this->json(['ok' => true, 'invoices' => $invoices]);
    }

    public function store(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'add');
        if (!$this->isPost()) { $this->redirect('?page=discounts'); }

        $nonce = $_POST['discount_form_nonce'] ?? '';
        if (empty($_SESSION['discount_form_nonce']) || !hash_equals($_SESSION['discount_form_nonce'], $nonce)) {
            $this->flash('error', 'Duplicate submission or expired form. Please try again.');
            $this->redirect('?page=discounts');
        }
        unset($_SESSION['discount_form_nonce']);

        $partyId = $this->inputInt('party_id');
        $saleId  = $this->inputInt('sale_id') ?: null;
        $amount  = $this->inputFloat('amount');
        $date    = $this->input('date') ?: date('Y-m-d');

        if (!$partyId || $amount <= 0) {
            $this->flash('error', 'Customer and amount are required.');
            $this->redirect('?page=discounts');
        }

        $db = Database::getInstance();

        $party = $db->fetchOne(
            "SELECT id FROM parties WHERE id = ? AND is_active = 1 AND type IN ('customer','both')",
            [$partyId]
        );
        if (!$party) {
            $this->flash('error', 'Invalid customer.');
            $this->redirect('?page=discounts');
            return;
        }

        $invoiceLabel = '';
        if ($saleId) {
            $sale = $this->resolveCustomerSale($db, $saleId, $partyId);
            if (!$sale) {
                $this->flash('error', 'Invalid invoice for this customer.');
                $this->redirect('?page=discounts');
                return;
            }
            $invoiceLabel = (string) $sale['invoice_no'];
        }

        $discountNo = '';
        $discountId = 0;
        $savedOk = false;
        $db->beginTransaction();
        try {
            // Sequence read locked inside the transaction — same pattern as payment_no below —
            // so concurrent saves cannot produce duplicate discount numbers.
            $last = $db->fetchOne("SELECT discount_no FROM customer_discounts ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $num  = $last ? (int) substr($last['discount_no'], 5) : 0;
            $discountNo = 'DISC-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

            $discountId = (int) $db->insert(
                "INSERT INTO customer_discounts (discount_no, party_id, item_id, sale_id, amount, reason, date, created_by)
                 VALUES (?, ?, NULL, ?, ?, NULL, ?, ?)",
                [$discountNo, $partyId, $saleId, $amount, $date, Auth::id()]
            );

            // Create payment record with ref_type='discount' — reduces customer balance
            // but does NOT affect account balances (no real money received)
            $payLast = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $payNum  = $payLast ? (int) substr($payLast['payment_no'], 4) : 0;
            $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);

            // Use first active account (required field, but won't affect balance)
            $acc = $db->fetchOne("SELECT id FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
            $accId = $acc['id'] ?? 1;

            $payNote = 'Discount ' . $discountNo . ($invoiceLabel !== '' ? ' — Inv ' . $invoiceLabel : '');
            $paymentId = $db->insert(
                "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, account_id, amount, payment_type, payment_method, date, notes, warehouse_id, created_by)
                 VALUES (?, 'discount', 0, ?, ?, ?, 'in', 'cash', ?, ?, ?, ?)",
                [$payNo, $partyId, $accId, $amount, $date, $payNote, Auth::warehouseId(), Auth::id()]
            );

            // Store the exact payment id for safe future updates/deletes (requires migration adding customer_discounts.payment_id)
            if ($paymentId) {
                $db->execute(
                    "UPDATE customer_discounts SET payment_id = ? WHERE discount_no = ?",
                    [(int) $paymentId, $discountNo]
                );
            }

            $db->commit();
            $savedOk = true;
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', "Discount {$discountNo} — " . APP_CURRENCY . " " . number_format($amount, DECIMAL_PLACES) . " applied.");
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Discount store failed: ' . $e->getMessage());
            $this->flash('error', 'Failed to save discount. Please try again or check server logs.');
        }

        if ($savedOk && $discountId > 0 && $this->input('print_after_save') === '1') {
            $this->redirect('?page=discounts&action=print&id=' . $discountId);
            return;
        }

        $this->redirect('?page=discounts');
    }

    public function delete(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'delete');
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
            // Safe delete: prefer linked payment_id; otherwise lock and delete only ref_type='discount'
            $payId = (int)($disc['payment_id'] ?? 0);
            if ($payId > 0) {
                // Branch-scoped + verified lock: never touch another warehouse's payment,
                // and fail loudly if the linked row is missing or its ref_type changed.
                $pay = $db->fetchOne(
                    "SELECT id FROM payments WHERE id = ? AND ref_type = 'discount' AND warehouse_id = ? FOR UPDATE",
                    [$payId, Auth::warehouseId()]
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot reverse this discount: linked payment row #' . $payId . ' not found in this branch. '
                        . 'Locate the PAY-* row in Payments (ref_type=discount) and remove it manually, '
                        . 'or contact support to relink it.'
                    );
                }
                $db->execute("DELETE FROM payments WHERE id = ? AND ref_type = 'discount' AND warehouse_id = ? LIMIT 1", [$payId, Auth::warehouseId()]);
            } else {
                // M4 fix: legacy rows (no payment_id link) — if the fuzzy lookup misses,
                // the customer_discounts row would be deleted while the payment lingers,
                // leaving the customer balance silently reduced. Fail loudly instead.
                $pay = $db->fetchOne(
                    "SELECT id FROM payments
                     WHERE ref_type = 'discount'
                       AND party_id = ?
                       AND amount = ?
                       AND warehouse_id = ?
                       AND notes LIKE ?
                     ORDER BY id DESC
                     LIMIT 1
                     FOR UPDATE",
                    [$disc['party_id'], $disc['amount'], Auth::warehouseId(), '%' . $disc['discount_no'] . '%']
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot reverse this discount: linked payment row not found. '
                        . 'Locate the PAY-* row in Payments (ref_type=discount) and remove it manually, '
                        . 'or contact support to relink it.'
                    );
                }
                $db->execute("DELETE FROM payments WHERE id = ? LIMIT 1", [(int)$pay['id']]);
            }
            $db->execute("DELETE FROM customer_discounts WHERE id = ?", [$id]);
            $this->logActivity('delete_discount', 'customer_discounts', $id,
                'Reversed ' . ($disc['discount_no'] ?? '#' . $id));
            $db->commit();
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', 'Discount reversed and removed.');
        } catch (\Exception $e) {
            $db->rollBack();
            if (str_starts_with($e->getMessage(), 'Cannot reverse this discount')) {
                // Deliberate operator-guidance message — safe to show as-is.
                $this->flash('error', $e->getMessage());
            } else {
                error_log('Discount delete failed for #' . $id . ': ' . $e->getMessage());
                $this->flash('error', 'Failed to delete discount. Please try again or check server logs.');
            }
        }

        $this->redirect('?page=discounts');
    }

    public function edit(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'edit');
        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $discount = $db->fetchOne(
            "SELECT d.*, p.name as party_name, s.invoice_no as sale_invoice_no
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN sales s ON s.id = d.sale_id
             WHERE d.id = ?", [$id]
        );
        if (!$discount) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
        }

        $parties = $db->fetchAll(
            "SELECT id, name FROM parties WHERE is_active = 1 AND (type = 'customer' OR type = 'both') ORDER BY name"
        );

        $invoices = $db->fetchAll(
            "SELECT id, invoice_no, date, grand_total, balance
             FROM sales
             WHERE party_id = ? AND warehouse_id = ? AND status != 'cancelled'
               AND (
                 date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 OR id = ?
               )
             ORDER BY id DESC
             LIMIT 80",
            [(int) $discount['party_id'], (int) Auth::warehouseId(), (int) ($discount['sale_id'] ?? 0)]
        );

        $pageTitle = 'Edit Discount';
        $page      = 'discounts';

        ob_start();
        include __DIR__ . '/../views/settings/discount_edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'edit');
        if (!$this->isPost()) { $this->redirect('?page=discounts'); }

        $id      = $this->inputInt('id');
        $partyId = $this->inputInt('party_id');
        $saleId  = $this->inputInt('sale_id') ?: null;
        $amount  = $this->inputFloat('amount');
        $date    = $this->input('date') ?: date('Y-m-d');

        if (!$partyId || $amount <= 0) {
            $this->flash('error', 'Customer and amount are required.');
            $this->redirect('?page=discounts&action=edit&id=' . $id);
        }

        $db = Database::getInstance();

        $party = $db->fetchOne(
            "SELECT id FROM parties WHERE id = ? AND is_active = 1 AND type IN ('customer','both')",
            [$partyId]
        );
        if (!$party) {
            $this->flash('error', 'Invalid customer.');
            $this->redirect('?page=discounts&action=edit&id=' . $id);
            return;
        }

        $invoiceLabel = '';
        if ($saleId) {
            $sale = $this->resolveCustomerSale($db, $saleId, $partyId);
            if (!$sale) {
                $this->flash('error', 'Invalid invoice for this customer.');
                $this->redirect('?page=discounts&action=edit&id=' . $id);
                return;
            }
            $invoiceLabel = (string) $sale['invoice_no'];
        }

        $disc = $db->fetchOne("SELECT * FROM customer_discounts WHERE id = ?", [$id]);
        if (!$disc) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
            return;
        }

        $db->beginTransaction();
        try {
            $db->execute(
                "UPDATE customer_discounts SET party_id=?, item_id=NULL, sale_id=?, amount=?, reason=NULL, date=? WHERE id=?",
                [$partyId, $saleId, $amount, $date, $id]
            );

            $note = 'Discount ' . $disc['discount_no'] . ($invoiceLabel !== '' ? ' — Inv ' . $invoiceLabel : '');
            $payId = (int)($disc['payment_id'] ?? 0);
            if ($payId > 0) {
                // Lock and verify the linked payment (branch-scoped) before updating,
                // so a concurrent delete cannot leave the discount pointing at nothing.
                $pay = $db->fetchOne(
                    "SELECT id FROM payments WHERE id = ? AND ref_type = 'discount' AND warehouse_id = ? FOR UPDATE",
                    [$payId, Auth::warehouseId()]
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot update this discount: linked payment row #' . $payId . ' not found in this branch. '
                        . 'It may have been deleted. Please reload and try again, or contact support to relink it.'
                    );
                }
                $db->execute(
                    "UPDATE payments SET party_id=?, amount=?, date=?, notes=? WHERE id=? AND ref_type='discount' AND warehouse_id=? LIMIT 1",
                    [$partyId, $amount, $date, $note, $payId, Auth::warehouseId()]
                );
            } else {
                $db->execute(
                    "UPDATE payments SET party_id=?, amount=?, date=?, notes=?
                     WHERE ref_type='discount' AND notes LIKE ? AND party_id=? AND warehouse_id=? LIMIT 1",
                    [$partyId, $amount, $date, $note, '%' . $disc['discount_no'] . '%', $disc['party_id'], Auth::warehouseId()]
                );
            }

            $db->commit();
            self::clearDashboardCache(Auth::warehouseId());
            $this->flash('success', "Discount {$disc['discount_no']} updated.");
        } catch (\Exception $e) {
            $db->rollBack();
            if (str_starts_with($e->getMessage(), 'Cannot update this discount')) {
                // Deliberate operator-guidance message — safe to show as-is.
                $this->flash('error', $e->getMessage());
            } else {
                error_log('Discount update failed for #' . $id . ': ' . $e->getMessage());
                $this->flash('error', 'Failed to update discount. Please try again or check server logs.');
            }
        }

        $this->redirect('?page=discounts');
    }

    public function print(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'view');
        $id = $this->inputInt('id', 0, 'get');
        $db = Database::getInstance();

        $discount = $db->fetchOne(
            "SELECT d.*, p.name as party_name, p.phone as party_phone,
                    s.invoice_no as sale_invoice_no, u.name as created_by_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN sales s ON s.id = d.sale_id
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.id = ?", [$id]
        );
        if (!$discount) {
            $this->flash('error', 'Discount not found.');
            $this->redirect('?page=discounts');
            return;
        }

        // Get customer remaining balance (Party model unified net)
        $partyModel       = new Party();
        $remainingBalance = max(0, $partyModel->currentNetBalance((int) $discount['party_id']));

        $companyName = self::getSettings()['company_name'] ?? PDF_COMPANY_NAME;

        include __DIR__ . '/../views/settings/discount_print.php';
        exit;
    }

    /** @return array<string,mixed>|null */
    private function resolveCustomerSale(Database $db, int $saleId, int $partyId): ?array {
        $sale = $db->fetchOne(
            "SELECT id, invoice_no FROM sales
             WHERE id = ? AND party_id = ? AND warehouse_id = ? AND status != 'cancelled'",
            [$saleId, $partyId, Auth::warehouseId()]
        );
        return $sale ?: null;
    }
}
