<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Party.php';

class DiscountController extends BaseController {

    public function index(): void {
        Auth::authorizeAny(['discounts', 'settings'], 'view');
        $db = Database::getInstance();

        $whId = (int) Auth::warehouseId();
        $discounts = $db->fetchAll(
            "SELECT d.*, p.name as party_name, i.name as item_name, s.invoice_no as sale_invoice_no,
                    u.name as created_by_name
             FROM customer_discounts d
             JOIN parties p ON p.id = d.party_id
             LEFT JOIN items i ON i.id = d.item_id
             LEFT JOIN sales s ON s.id = d.sale_id
             LEFT JOIN users u ON u.id = d.created_by
             LEFT JOIN payments py ON py.id = d.payment_id
             WHERE d.payment_id IS NULL
                OR py.id IS NULL
                OR (
                    py.ref_type = 'discount'
                    AND py.payment_type = 'in'
                    AND (py.warehouse_id = ? OR (? = 1 AND py.warehouse_id IS NULL))
                )
             ORDER BY d.id DESC LIMIT 100",
            [$whId, $whId]
        );

        $parties = (new Party())->listForFilter('customer');

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
        if (!$party || !(new Party())->isVisibleInCurrentWarehouse($partyId)) {
            $this->flash('error', 'Invalid customer for this branch.');
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

            // Customer discount: payment_type=in, ref_type=discount. No bank movement.
            require_once __DIR__ . '/../models/Payment.php';
            $payNo = (new Payment())->nextPaymentNo();

            $acc = $db->fetchOne("SELECT id FROM accounts WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
            $accId = $acc['id'] ?? 1;

            $payNote = 'Discount ' . $discountNo . ($invoiceLabel !== '' ? ' — Inv ' . $invoiceLabel : '');
            $paymentId = $db->insert(
                "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, account_id, amount, payment_type, payment_method, date, notes, warehouse_id, status, created_by)
                 VALUES (?, 'discount', 0, ?, ?, ?, 'in', 'cash', ?, ?, ?, 'active', ?)",
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
            [$payWhere, $payParams] = $this->customerDiscountPaymentWhere();
            if ($payId > 0) {
                // Customer discounts only (payment_type=in). Packing discounts stay on Packing due.
                // NULL warehouse_id is legacy Main, same as the party balance.
                $pay = $db->fetchOne(
                    "SELECT id FROM payments WHERE id = ? AND {$payWhere} FOR UPDATE",
                    array_merge([$payId], $payParams)
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot reverse this discount: linked payment row #' . $payId . ' not found in this branch. '
                        . 'Locate the PAY-* row in Payments (ref_type=discount) and remove it manually, '
                        . 'or contact support to relink it.'
                    );
                }
                $db->execute(
                    "DELETE FROM payments WHERE id = ? AND {$payWhere} LIMIT 1",
                    array_merge([$payId], $payParams)
                );
            } else {
                // M4 fix: legacy rows (no payment_id link) — if the fuzzy lookup misses,
                // the customer_discounts row would be deleted while the payment lingers,
                // leaving the customer balance silently reduced. Fail loudly instead.
                $pay = $db->fetchOne(
                    "SELECT id FROM payments
                     WHERE {$payWhere}
                       AND party_id = ?
                       AND amount = ?
                       AND notes LIKE ?
                     ORDER BY id DESC
                     LIMIT 1
                     FOR UPDATE",
                    array_merge($payParams, [$disc['party_id'], $disc['amount'], '%' . $disc['discount_no'] . '%'])
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot reverse this discount: linked payment row not found. '
                        . 'Locate the PAY-* row in Payments (ref_type=discount) and remove it manually, '
                        . 'or contact support to relink it.'
                    );
                }
                $db->execute(
                    "DELETE FROM payments WHERE id = ? AND {$payWhere} LIMIT 1",
                    array_merge([(int) $pay['id']], $payParams)
                );
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

        $parties = (new Party())->listForFilter('customer');

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
        if (!$party || !(new Party())->isVisibleInCurrentWarehouse($partyId)) {
            $this->flash('error', 'Invalid customer for this branch.');
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
            [$payWhere, $payParams] = $this->customerDiscountPaymentWhere();
            $whId = (int) Auth::warehouseId();
            if ($payId > 0) {
                $pay = $db->fetchOne(
                    "SELECT id FROM payments WHERE id = ? AND {$payWhere} FOR UPDATE",
                    array_merge([$payId], $payParams)
                );
                if (!$pay || empty($pay['id'])) {
                    throw new \Exception(
                        'Cannot update this discount: linked payment row #' . $payId . ' not found in this branch. '
                        . 'It may have been deleted. Please reload and try again, or contact support to relink it.'
                    );
                }
                $db->execute(
                    "UPDATE payments SET party_id=?, amount=?, date=?, notes=?, warehouse_id=?
                     WHERE id=? AND {$payWhere} LIMIT 1",
                    array_merge([$partyId, $amount, $date, $note, $whId, $payId], $payParams)
                );
            } else {
                $db->execute(
                    "UPDATE payments SET party_id=?, amount=?, date=?, notes=?, warehouse_id=?
                     WHERE {$payWhere} AND notes LIKE ? AND party_id=? LIMIT 1",
                    array_merge(
                        [$partyId, $amount, $date, $note, $whId],
                        $payParams,
                        ['%' . $disc['discount_no'] . '%', $disc['party_id']]
                    )
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

    /**
     * Customer discount payments on this branch.
     * Legacy NULL warehouse_id counts as Main (id 1) only. Packing discounts are payment_type=out.
     *
     * @return array{0:string,1:list<int>}
     */
    private function customerDiscountPaymentWhere(string $alias = ''): array {
        $col = $alias !== '' ? $alias . '.' : '';
        $wh = (int) Auth::warehouseId();
        if ($wh <= 0) {
            return ['1 = 0', []];
        }
        $whSql = $wh === 1
            ? "({$col}warehouse_id = ? OR {$col}warehouse_id IS NULL)"
            : "{$col}warehouse_id = ?";

        return [
            "{$col}ref_type = 'discount' AND {$col}payment_type = 'in' AND {$whSql}",
            [$wh],
        ];
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
