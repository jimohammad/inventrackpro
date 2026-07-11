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

    /** @return array<string,int> */
    private static function paymentFormNonceBag(): array {
        if (!isset($_SESSION['payment_form_nonces']) || !is_array($_SESSION['payment_form_nonces'])) {
            $_SESSION['payment_form_nonces'] = [];
        }
        return $_SESSION['payment_form_nonces'];
    }

    /** Issue a one-time token (supports multiple open payment tabs). */
    private function issuePaymentFormNonce(): string {
        unset($_SESSION['payment_form_nonce']); // legacy single-nonce key
        $nonce = bin2hex(random_bytes(16));
        $bag   = self::paymentFormNonceBag();
        $bag[$nonce] = time();
        $cutoff = time() - 7200;
        foreach ($bag as $k => $ts) {
            if ($ts < $cutoff) {
                unset($bag[$k]);
            }
        }
        if (count($bag) > 20) {
            asort($bag);
            while (count($bag) > 20) {
                unset($bag[array_key_first($bag)]);
            }
        }
        $_SESSION['payment_form_nonces'] = $bag;
        return $nonce;
    }

    /** Consume a posted token; returns false if missing, expired, or already used. */
    private function consumePaymentFormNonce(string $postedNonce): bool {
        if ($postedNonce === '') {
            return false;
        }
        $legacy = $_SESSION['payment_form_nonce'] ?? '';
        if ($legacy !== '' && hash_equals($legacy, $postedNonce)) {
            unset($_SESSION['payment_form_nonce']);
            return true;
        }
        $bag = self::paymentFormNonceBag();
        if (!isset($bag[$postedNonce])) {
            return false;
        }
        unset($bag[$postedNonce]);
        $_SESSION['payment_form_nonces'] = $bag;
        return true;
    }

    private function paymentFormReturnAction(string $paymentType = ''): string {
        return ($paymentType === 'out') ? 'pay' : 'receive';
    }

    /**
     * Derive payment_method from account.type. Cheque overrides if cheque_no provided.
     * Note: payments.payment_method enum expects bank_transfer/card, while accounts.type can be bank/other.
     */
    private function derivePaymentMethod(int $accountId, string $chequeNo): string {
        if ($chequeNo !== '') return 'cheque';
        if (!$accountId)      return 'cash';
        $row = Database::getInstance()->fetchOne("SELECT type, name FROM accounts WHERE id = ?", [$accountId]);
        $type = self::normalizeAccountType(
            (string)($row['type'] ?? ''),
            (string)($row['name'] ?? '')
        );
        // Map account.type -> payments.payment_method enum values
        $map = [
            'cash'          => 'cash',
            'bank'          => 'bank_transfer',
            'bank_transfer' => 'bank_transfer',
            'mobile_wallet' => 'mobile_wallet',
            'card'          => 'card',
            'other'         => 'cash', // safe fallback for legacy account types
        ];
        return $map[$type] ?? 'cash';
    }

    public function index(): void {
        Auth::authorize('payments', 'view');

        $dateRange = ListPage::resolveDateFiltersFromGet();

        $filters = [
            'search'    => $this->inputSearch('search', '', 'get'),
            'party_id'  => $this->inputInt('party_id', 0, 'get'),
            'ref_type'  => $this->input('ref_type', '', 'get'),
            'from_date' => $dateRange['from_date'],
            'to_date'   => $dateRange['to_date'],
            'all_dates' => $dateRange['all_dates'],
        ];

        $parties        = $this->partyModel->listForFilter('all');
        $listPage       = $this->paymentModel->getIndexPage($filters);
        $payments       = $listPage['items'];
        $listTruncated  = $listPage['truncated'];
        $listLimit      = $listPage['limit'];
        $datesDefaulted = $dateRange['dates_defaulted'];

        [$summaryFrom, $summaryTo] = ListPage::summaryDateRange($filters);
        $summary   = $this->paymentModel->getSummary($summaryFrom, $summaryTo);
        $pageTitle = 'Payments';
        $page      = 'payments';

        ob_start();
        include __DIR__ . '/../views/payments/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // Back-compat: route /create (and old ?type=in/out links) to the new split pages
    public function create(): void {
        $type = $this->input('type', '', 'get');
        $qs   = '';
        if ($this->input('party_id', '', 'get')) $qs .= '&party_id=' . urlencode($this->input('party_id', '', 'get'));
        if ($this->input('ref_type', '', 'get')) $qs .= '&ref_type=' . urlencode($this->input('ref_type', '', 'get'));
        if ($this->input('ref_id',   '', 'get')) $qs .= '&ref_id='   . urlencode($this->input('ref_id', '', 'get'));
        $action = ($type === 'out') ? 'pay' : 'receive';
        $this->redirect("?page=payments&action={$action}{$qs}");
    }

    /** Payment IN — receive money from customer */
    public function receive(): void {
        $this->renderForm('in');
    }

    /** Payment OUT — pay supplier */
    public function pay(): void {
        $this->renderForm('out');
    }

    /**
     * Shared form renderer. $mode = 'in' (receive) or 'out' (pay).
     * Filters party list by type, picks color theme, sets next payment no, etc.
     */
    private function renderForm(string $mode): void {
        Auth::authorize('payments', 'add');

        $db = Database::getInstance();

        $importPayable = $mode === 'out' && $this->input('import_payable', '', 'get') === '1';
        $partySearchType = $mode === 'in'
            ? 'customer'
            : ($importPayable ? 'all' : 'payment_out');

        $accounts = self::getAccounts();

        // Pre-fill from ref (sale/purchase invoice, or import shipment charge)
        $refType = $this->input('ref_type', '', 'get');
        $refId   = $this->inputInt('ref_id', 0, 'get');
        $refData = null;
        $importPayableContext = null;

        $shipmentRefTypes = [
            'shipment_freight_hk',
            'shipment_packing_dxb',
            'shipment_freight_dxb',
            'shipment_partner',
            'shipment_cost',
        ];

        if ($refId > 0 && in_array($refType, $shipmentRefTypes, true)) {
            $importPayable = true;
            $importPayableContext = $this->loadImportPayableContext($db, $refType, $refId);
        } elseif ($refId > 0) {
            $defaultRef = ($mode === 'in') ? 'sale' : 'purchase';
            $refType    = $refType ?: $defaultRef;
            $refTableMap = ['sale' => 'sales', 'purchase' => 'purchases'];
            if (isset($refTableMap[$refType])) {
                $table   = $refTableMap[$refType];
                $refData = $db->fetchOne(
                    "SELECT t.*, p.name as party_name FROM {$table} t
                     JOIN parties p ON p.id = t.party_id
                     WHERE t.id = ? AND t.warehouse_id = ?",
                    [$refId, Auth::warehouseId()]
                );
            }
        } else {
            $refType = ($mode === 'in') ? 'sale' : 'purchase';
        }

        // Optional party preselect via ?party_id=
        $preselectPartyId = $this->inputInt('party_id', 0, 'get');
        $preselectAmount  = $this->inputFloat('amount', 0, 'get');
        $preselectNotes   = trim($this->input('notes', '', 'get'));

        $preselectParty = null;
        if ($importPayableContext) {
            $preselectParty = [
                'id'    => (int) ($importPayableContext['party_id'] ?? $preselectPartyId),
                'name'  => (string) ($importPayableContext['partner_name'] ?? ''),
                'phone' => '',
            ];
        } elseif ($refData) {
            $preselectParty = [
                'id'    => (int) $refData['party_id'],
                'name'  => (string) $refData['party_name'],
                'phone' => '',
            ];
        } elseif ($preselectPartyId > 0) {
            $preselectParty = $db->fetchOne(
                "SELECT id, name, phone FROM parties WHERE id = ? AND is_active = 1",
                [$preselectPartyId]
            ) ?: null;
        }

        $pageTitle = ($mode === 'in') ? 'Receive Payment' : 'Make Payment';
        $page      = 'payments';

        $lastPay   = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1");
        $nextNum   = $lastPay ? (int) substr($lastPay['payment_no'], 4) + 1 : 1;
        $nextPayNo = 'PAY-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        // One-time token per form load — CSRF alone stays valid across submits, so double-click could create duplicate PAY rows
        $paymentFormNonce = $this->issuePaymentFormNonce();

        ob_start();
        include __DIR__ . '/../views/payments/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** @return array<string, mixed>|null */
    private function loadImportPayableContext(Database $db, string $refType, int $chargeId): ?array {
        if ($chargeId <= 0) {
            return null;
        }

        $legMap = [
            'shipment_freight_hk'  => 'freight_hk',
            'shipment_packing_dxb' => 'packing_dxb',
            'shipment_freight_dxb' => 'freight_dxb',
            'shipment_partner'     => 'partner',
        ];
        $leg = $legMap[$refType] ?? null;

        $row = $db->fetchOne(
            "SELECT sic.id as charge_id, s.shipment_no, s.received_date,
                    po.po_no, i.name as item_name,
                    COALESCE(ipa.party_id, sic.partner_party_id, sic.freight_hk_dxb_party_id,
                             sic.packing_dxb_party_id, sic.freight_dxb_kwt_party_id) as party_id,
                    COALESCE(p.name, phk.name, ppack.name, pkwt.name, pp.name) as partner_name,
                    COALESCE(ipa.amount, 0) as amount, ipa.accrual_no, ipa.status
             FROM shipment_item_charges sic
             JOIN shipments s ON s.id = sic.shipment_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN items i ON i.id = sic.item_id
             LEFT JOIN import_payable_accruals ipa ON ipa.shipment_item_charge_id = sic.id
                 AND ipa.leg = ?
             LEFT JOIN parties p ON p.id = ipa.party_id
             LEFT JOIN parties phk ON phk.id = sic.freight_hk_dxb_party_id
             LEFT JOIN parties ppack ON ppack.id = sic.packing_dxb_party_id
             LEFT JOIN parties pkwt ON pkwt.id = sic.freight_dxb_kwt_party_id
             LEFT JOIN parties pp ON pp.id = sic.partner_party_id
             WHERE sic.id = ?",
            [$leg ?? 'partner', $chargeId]
        );

        if (!$row) {
            return null;
        }

        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';
        $row['charge_label'] = $leg ? ImportPayableAccrualService::legLabel($leg) : 'Import payable';

        return $row;
    }

    // AJAX: Get party balance (scoped to current warehouse)
    public function partyBalance(): void {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['balance' => 0]); return; }

        // Centralized balance logic (directional CASE WHEN rules) lives in Party::findWithBalance().
        $partyModel = new Party();
        $party = $partyModel->findWithBalance($id);
        echo json_encode(['balance' => (float)($party['net_balance'] ?? 0)]);
    }

    public function store(): void {
        Auth::authorize('payments', 'add');

        $returnAction = $this->paymentFormReturnAction($this->input('payment_type') ?: 'in');

        if (!$this->isPost()) {
            $this->redirect('?page=payments&action=' . $returnAction);
            return;
        }

        $errors = $this->validate([
            'party_id'   => 'required',
            'account_id' => 'required',
            'amount'     => 'required',
            'date'       => 'required',
        ]);

        if (!empty($errors) || $this->inputInt('party_id') <= 0) {
            if ($this->inputInt('party_id') <= 0 && empty($errors)) {
                $errors[] = 'Customer is required.';
            }
            $this->flash('error', implode(' ', $errors));
            $this->redirect('?page=payments&action=' . $returnAction);
            return;
        }

        // Validate positive amount
        if ($this->inputFloat('amount') <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=payments&action=' . $returnAction);
            return;
        }

        $postedNonce = isset($_POST['payment_form_nonce']) ? trim((string) $_POST['payment_form_nonce']) : '';
        if (!$this->consumePaymentFormNonce($postedNonce)) {
            $this->flash(
                'warning',
                'This payment form expired or was already used. Check Payments list first — if it is not there, open Receive Payment again and retry.'
            );
            $this->redirect('?page=payments&action=' . $returnAction);
            return;
        }

        // Derive payment_method from account.type unless cheque_no provided
        $accountId = $this->inputInt('account_id');
        $chequeNo  = trim($this->input('cheque_no'));
        $method    = $this->derivePaymentMethod($accountId, $chequeNo);

        $id = $this->paymentModel->createStandalone([
            'party_id'       => $this->inputInt('party_id'),
            'phone_no'       => $this->input('phone_no'),
            'payment_type'   => $this->input('payment_type') ?: 'in',
            'account_id'     => $accountId,
            'ref_type'       => $this->input('ref_type') ?: 'sale',
            'ref_id'         => $this->inputInt('ref_id'),
            'amount'         => $this->inputFloat('amount'),
            'payment_method' => $method,
            'cheque_no'      => $chequeNo ?: null,
            'date'           => $this->input('date'),
            'notes'          => $this->input('notes'),
        ]);

        // Second split payment if amount2 > 0
        $amount2 = $this->inputFloat('amount2');
        $id2 = null;
        if ($amount2 > 0) {
            $accountId2 = $this->inputInt('account_id2');
            $method2    = $this->derivePaymentMethod($accountId2, '');
            $id2 = $this->paymentModel->createStandalone([
                'party_id'       => $this->inputInt('party_id'),
                'phone_no'       => $this->input('phone_no'),
                'payment_type'   => $this->input('payment_type') ?: 'in',
                'account_id'     => $accountId2,
                'ref_type'       => $this->input('ref_type') ?: 'sale',
                'ref_id'         => $this->inputInt('ref_id'),
                'amount'         => $amount2,
                'payment_method' => $method2,
                'cheque_no'      => '',
                'date'           => $this->input('date'),
                'notes'          => $this->input('notes') ? $this->input('notes') . ' (Split 2)' : 'Split payment 2',
            ]);
            if ($id2) $this->logActivity('create_payment', 'payments', (int)$id2);
        }

        if ($id) {
            require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
            LandedCostPaymentLinker::linkItemChargePayment(
                Database::getInstance(),
                (string) ($this->input('ref_type') ?: 'sale'),
                $this->inputInt('ref_id'),
                (int) $id
            );
            $this->logActivity('create_payment', 'payments', (int)$id);
            self::clearDashboardCache(Auth::warehouseId());
            $printMode = (string)($this->input('print_mode') ?? '');
            if ($printMode === '1') {
                // Payment voucher PDF/print is always the colorful A5 layout (thermal is print_mode 2 only).
                $this->redirect("?page=payments&action=print&id={$id}&autoprint=1");
            }
            if ($printMode === '2') {
                $this->redirect("?page=payments&action=print&id={$id}&autoprint=1&thermal=1");
            }
            $this->flash('success', 'Payment recorded successfully.');
        } else {
            $err = trim($this->paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Failed to save payment: ' . $err) : 'Failed to save payment.');
            $retryQs = '';
            $partyId = $this->inputInt('party_id');
            if ($partyId > 0) {
                $retryQs .= '&party_id=' . $partyId;
            }
            $retryAmount = $this->inputFloat('amount');
            if ($retryAmount > 0) {
                $retryQs .= '&amount=' . urlencode(number_format($retryAmount, 3, '.', ''));
            }
            $this->redirect('?page=payments&action=' . $returnAction . $retryQs);
            return;
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

        // Party balance — only fetch if a party is linked (PO/expense payments may have null party)
        $currentBalance  = 0.0;
        $previousBalance = 0.0;
        $partyId = (int)($payment['party_id'] ?? 0);
        if ($partyId > 0) {
            $partyBalance   = $this->partyModel->findWithBalance($partyId);
            $currentBalance = is_array($partyBalance) ? (float)($partyBalance['net_balance'] ?? 0) : 0.0;
            // Reverse this payment to get prior balance: IN reduces party balance, OUT increases it
            $isIn = ($payment['payment_type'] ?? 'in') === 'in';
            $previousBalance = $isIn
                ? $currentBalance + (float)$payment['amount']
                : $currentBalance - (float)$payment['amount'];
        }

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
        $accounts = self::getAccounts();

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
                $whId = Auth::warehouseId();
                if ($payment['ref_type'] === 'sale') {
                    $sale = $db->fetchOne("SELECT grand_total FROM sales WHERE id = ? AND warehouse_id = ?", [$payment['ref_id'], $whId]);
                    if ($sale) {
                        $totalPaid = (float)($db->fetchOne("SELECT SUM(amount) as tot FROM payments WHERE ref_type = 'sale' AND ref_id = ? AND status = 'active'", [$payment['ref_id']])['tot'] ?? 0);
                        $returnsTot = (float)($db->fetchOne("SELECT SUM(grand_total) as tot FROM `returns` WHERE ref_id = ? AND type = 'sale_return' AND status = 'approved'", [$payment['ref_id']])['tot'] ?? 0);
                        
                        $newBalance = max(0, (float)$sale['grand_total'] - $totalPaid - $returnsTot);
                        $newStatus  = $newBalance < 0.001 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'confirmed');
                        
                        $db->execute(
                            "UPDATE sales SET paid_amount = ?, balance = ?, status = ? WHERE id = ? AND warehouse_id = ?",
                            [$totalPaid, $newBalance, $newStatus, $payment['ref_id'], $whId]
                        );
                    }
                } elseif ($payment['ref_type'] === 'purchase') {
                    $purch = $db->fetchOne("SELECT grand_total FROM purchases WHERE id = ? AND warehouse_id = ?", [$payment['ref_id'], $whId]);
                    if ($purch) {
                        $totalPaid = (float)($db->fetchOne("SELECT SUM(amount) as tot FROM payments WHERE ref_type = 'purchase' AND ref_id = ? AND status = 'active'", [$payment['ref_id']])['tot'] ?? 0);
                        $returnsTot = (float)($db->fetchOne("SELECT SUM(grand_total) as tot FROM `returns` WHERE ref_id = ? AND type = 'purchase_return' AND status = 'approved'", [$payment['ref_id']])['tot'] ?? 0);
                        
                        $newBalance = max(0, (float)$purch['grand_total'] - $totalPaid - $returnsTot);
                        $newStatus  = $newBalance < 0.001 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'confirmed');
                        
                        $db->execute(
                            "UPDATE purchases SET paid_amount = ?, balance = ?, status = ? WHERE id = ? AND warehouse_id = ?",
                            [$totalPaid, $newBalance, $newStatus, $payment['ref_id'], $whId]
                        );
                    }
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
            self::clearDashboardCache((int) ($payment['warehouse_id'] ?? 0));
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

    /**
     * Delete a mistaken payment row and reverse account + FIFO invoice effects.
     * Requires Payments → Delete permission (admins have all actions).
     */
    public function delete(): void {
        Auth::authorize('payments', 'delete');

        if (!$this->isPost()) {
            $this->redirect('?page=payments');
            return;
        }

        $id = $this->inputInt('id');
        if ($id <= 0) {
            $this->flash('error', 'Invalid payment.');
            $this->redirect('?page=payments');
            return;
        }

        $payment = $this->paymentModel->findFull($id);
        if (!$payment) {
            $this->flash('error', 'Payment not found.');
            $this->redirect('?page=payments');
            return;
        }

        if (($payment['ref_type'] ?? '') === 'discount') {
            $this->flash('error', 'Remove discount-linked payments from the Discounts module.');
            $this->redirect('?page=payments');
            return;
        }

        $ok = $this->paymentModel->deleteWithReversal($id);
        if ($ok) {
            $this->logActivity('delete_payment', 'payments', $id, 'Deleted ' . ($payment['payment_no'] ?? ''));
            self::clearDashboardCache((int) ($payment['warehouse_id'] ?? 0));
            $this->flash('success', 'Payment ' . ($payment['payment_no'] ?? '') . ' deleted; balances reversed.');
        } else {
            $err = trim($this->paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Could not delete: ' . $err) : 'Could not delete payment.');
        }

        $this->redirect('?page=payments');
    }
}
