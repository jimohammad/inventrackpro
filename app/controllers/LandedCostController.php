<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/PurchaseOrderConverter.php';
require_once __DIR__ . '/../services/LandedCostAllocator.php';
require_once __DIR__ . '/../services/ImportPayableAccrualService.php';

class LandedCostController extends BaseController {

    private function db(): Database {
        return Database::getInstance();
    }

    private function warehouseId(): int {
        return (int) Auth::warehouseId();
    }

    public function index(): void {
        Auth::authorize('purchases', 'view');
        $db  = $this->db();
        $wh  = $this->warehouseId();

        $shipments = $db->fetchAll(
            "SELECT s.*,
                    COUNT(DISTINCT spo.po_id) as po_count,
                    COUNT(DISTINCT sp.purchase_id) as purchase_count,
                    COALESCE((
                        SELECT SUM(sic.freight_hk_dxb + COALESCE(sic.packing_dxb, 0) + sic.freight_dxb_kwt + sic.partner_profit_per_pc * sic.quantity)
                        FROM shipment_item_charges sic WHERE sic.shipment_id = s.id
                    ), 0) + COALESCE(SUM(sc.amount), 0) as total_cost,
                    COALESCE((
                        SELECT SUM(CASE WHEN sic.is_applied = 1
                            THEN sic.freight_hk_dxb + COALESCE(sic.packing_dxb, 0) + sic.freight_dxb_kwt + sic.partner_profit_per_pc * sic.quantity ELSE 0 END)
                        FROM shipment_item_charges sic WHERE sic.shipment_id = s.id
                    ), 0) + COALESCE(SUM(CASE WHEN sc.is_applied = 1 THEN sc.amount ELSE 0 END), 0) as applied_cost
             FROM shipments s
             LEFT JOIN shipment_purchase_orders spo ON spo.shipment_id = s.id
             LEFT JOIN shipment_purchases sp ON sp.shipment_id = s.id
             LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id
             WHERE s.warehouse_id = ? OR s.warehouse_id IS NULL
             GROUP BY s.id
             ORDER BY s.created_at DESC",
            [$wh]
        );

        $pageTitle = 'Import Logistics';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('purchases', 'add');
        $ctx = $this->prepareShipmentFormContext(null);

        $pageTitle = 'New Import Shipment';
        $page      = 'landedcost';
        ob_start();
        extract($ctx);
        include __DIR__ . '/../views/purchases/landed_cost_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function edit(): void {
        Auth::authorize('purchases', 'add');
        $db  = $this->db();
        $id  = $this->inputInt('id', 0, 'get');
        $shipment = $this->loadShipment($db, $id);
        if (!$shipment) {
            $this->flash('error', 'Shipment not found.');
            $this->redirect('?page=landedcost');
            return;
        }

        try {
            $this->assertShipmentEditable($shipment);
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=landedcost&action=view&id=' . $id);
            return;
        }

        $ctx = $this->prepareShipmentFormContext($shipment);

        $pageTitle = 'Edit ' . $shipment['shipment_no'];
        $page      = 'landedcost';
        ob_start();
        extract($ctx);
        include __DIR__ . '/../views/purchases/landed_cost_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db           = $this->db();
        $wh           = $this->warehouseId();
        $poIds        = array_filter(array_map('intval', $_POST['po_ids'] ?? []));
        $description  = $this->input('description') ?: 'Import shipment';
        $routeStage   = $this->input('route_stage') === 'kuwait_inbound' ? 'kuwait_inbound' : 'dubai_hub';
        $status       = $this->input('status') === 'in_transit' ? 'in_transit' : 'draft';
        $date         = $this->input('date') ?: date('Y-m-d');
        $notes        = $this->input('notes');
        $parentId     = $this->inputInt('parent_shipment_id', 0);

        if (empty($poIds)) {
            $this->flash('error', 'Select at least one purchase order.');
            $this->redirect('?page=landedcost&action=create');
            return;
        }

        $itemCharges = $this->parseItemCharges($_POST);
        if (empty($itemCharges)) {
            $this->flash('error', 'Enter at least one item charge (freight or partner profit per piece).');
            $this->redirect('?page=landedcost&action=create');
            return;
        }
        $hasPartnerPc = array_filter($itemCharges, static fn ($c) => (float) ($c['partner_profit_per_pc'] ?? 0) > 0);
        if ($hasPartnerPc && !$this->importPartnerPartyId()) {
            $this->flash('error', 'Import partner not found (party code ' . $this->importPartnerPartyCode() . '). Check Party Master.');
            $this->redirect('?page=landedcost&action=create');
            return;
        }

        $db->beginTransaction();
        try {
            $shipmentId = (int) $db->insert(
                "INSERT INTO shipments (shipment_no, description, route_stage, parent_shipment_id, date, status, notes, warehouse_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $this->nextShipmentNo(),
                    $description,
                    $routeStage,
                    $parentId > 0 ? $parentId : null,
                    $date,
                    $status,
                    $notes ?: null,
                    $wh,
                    Auth::id(),
                ]
            );

            foreach ($poIds as $poId) {
                $this->assertPoLinkable($db, $poId, $wh, null);
                $db->insert(
                    "INSERT INTO shipment_purchase_orders (shipment_id, po_id) VALUES (?,?)",
                    [$shipmentId, $poId]
                );
            }

            $this->assertItemChargesBelongToPos($db, $poIds, $itemCharges);
            $this->insertItemCharges($db, $shipmentId, $itemCharges);

            $db->commit();
            $total = array_sum(array_map(static function ($c) {
                return (float) $c['freight_hk_dxb'] + (float) ($c['packing_dxb'] ?? 0) + (float) $c['freight_dxb_kwt']
                    + (float) $c['partner_profit_per_pc'] * (int) $c['quantity'];
            }, $itemCharges));
            $this->flash('success', "Shipment saved. KWD " . number_format($total, DECIMAL_PLACES) . " accrued (not applied to stock cost until Kuwait receipt).");
            $this->redirect('?page=landedcost&action=view&id=' . $shipmentId);
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=landedcost&action=create');
        }
    }

    public function update(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db         = $this->db();
        $wh         = $this->warehouseId();
        $shipmentId = $this->inputInt('shipment_id');
        $shipment   = $this->loadShipment($db, $shipmentId);
        if (!$shipment) {
            $this->flash('error', 'Shipment not found.');
            $this->redirect('?page=landedcost');
            return;
        }

        try {
            $this->assertShipmentEditable($shipment);
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('?page=landedcost&action=view&id=' . $shipmentId);
            return;
        }

        $poIds       = array_filter(array_map('intval', $_POST['po_ids'] ?? []));
        $description = $this->input('description') ?: 'Import shipment';
        $routeStage  = $this->input('route_stage') === 'kuwait_inbound' ? 'kuwait_inbound' : 'dubai_hub';
        $status      = $this->input('status') === 'in_transit' ? 'in_transit' : 'draft';
        $date        = $this->input('date') ?: date('Y-m-d');
        $notes       = $this->input('notes');

        if (empty($poIds)) {
            $this->flash('error', 'Select at least one purchase order.');
            $this->redirect('?page=landedcost&action=edit&id=' . $shipmentId);
            return;
        }

        $itemCharges = $this->parseItemCharges($_POST);
        if (empty($itemCharges)) {
            $this->flash('error', 'Enter at least one item charge (freight or partner profit per piece).');
            $this->redirect('?page=landedcost&action=edit&id=' . $shipmentId);
            return;
        }
        $hasPartnerPc = array_filter($itemCharges, static fn ($c) => (float) ($c['partner_profit_per_pc'] ?? 0) > 0);
        if ($hasPartnerPc && !$this->importPartnerPartyId()) {
            $this->flash('error', 'Import partner not found (party code ' . $this->importPartnerPartyCode() . '). Check Party Master.');
            $this->redirect('?page=landedcost&action=edit&id=' . $shipmentId);
            return;
        }

        $db->beginTransaction();
        try {
            $db->execute(
                "UPDATE shipments SET description = ?, route_stage = ?, date = ?, status = ?, notes = ? WHERE id = ?",
                [$description, $routeStage, $date, $status, $notes ?: null, $shipmentId]
            );

            $this->syncShipmentPurchaseOrders($db, $shipmentId, $poIds, $wh);
            $this->assertItemChargesBelongToPos($db, $poIds, $itemCharges);
            $this->replaceItemCharges($db, $shipmentId, $itemCharges);

            $db->commit();
            $total = array_sum(array_map(static function ($c) {
                return (float) $c['freight_hk_dxb'] + (float) ($c['packing_dxb'] ?? 0) + (float) $c['freight_dxb_kwt']
                    + (float) $c['partner_profit_per_pc'] * (int) $c['quantity'];
            }, $itemCharges));
            $this->flash('success', "Shipment updated. KWD " . number_format($total, DECIMAL_PLACES) . " accrued (not applied to stock cost until Kuwait receipt).");
            $this->redirect('?page=landedcost&action=view&id=' . $shipmentId);
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=landedcost&action=edit&id=' . $shipmentId);
        }
    }

    public function view(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();
        $id = $this->inputInt('id', 0, 'get');

        $shipment = $this->loadShipment($db, $id);
        if (!$shipment) {
            $this->flash('error', 'Shipment not found.');
            $this->redirect('?page=landedcost');
            return;
        }

        $importPartner = $this->importPartnerParty($db);

        $pageTitle = $shipment['shipment_no'];
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_detail.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function addCost(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db = $this->db();
        $id = $this->inputInt('shipment_id');
        $shipment = $this->loadShipment($db, $id);
        if (!$shipment || $shipment['status'] === 'applied') {
            $this->flash('error', 'Cannot add costs to a closed shipment.');
            $this->redirect('?page=landedcost');
            return;
        }

        $costs = $this->parseCostLines($_POST);
        if (empty($costs)) {
            $this->flash('error', 'Add at least one cost line.');
            $this->redirect('?page=landedcost&action=view&id=' . $id);
            return;
        }

        $db->beginTransaction();
        try {
            $this->insertCostLines($db, $id, $costs);
            $db->commit();
            $this->flash('success', 'Cost line(s) added.');
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('?page=landedcost&action=view&id=' . $id);
    }

    public function receive(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db = $this->db();
        $id = $this->inputInt('shipment_id');
        $receivedDate = $this->input('received_date') ?: date('Y-m-d');

        $shipment = $db->fetchOne("SELECT * FROM shipments WHERE id = ? FOR UPDATE", [$id]);
        if (!$shipment) {
            $this->flash('error', 'Shipment not found.');
            $this->redirect('?page=landedcost');
            return;
        }
        if ($shipment['status'] === 'applied') {
            $this->flash('error', 'Shipment already received and applied.');
            $this->redirect('?page=landedcost&action=view&id=' . $id);
            return;
        }

        $poIds = array_column(
            $db->fetchAll("SELECT po_id FROM shipment_purchase_orders WHERE shipment_id = ?", [$id]),
            'po_id'
        );
        $purchaseIds = array_map('intval', array_column(
            $db->fetchAll("SELECT purchase_id FROM shipment_purchases WHERE shipment_id = ?", [$id]),
            'purchase_id'
        ));

        if (empty($poIds) && empty($purchaseIds)) {
            $this->flash('error', 'No POs or purchases linked.');
            $this->redirect('?page=landedcost&action=view&id=' . $id);
            return;
        }

        $db->beginTransaction();
        try {
            if (!empty($poIds)) {
                $this->assertItemChargesMatchLinkedPos($db, $id, $poIds);
            }

            $poItemToPurchaseItem = [];
            foreach ($poIds as $poId) {
                $purchaseId = PurchaseOrderConverter::convert(
                    $db,
                    (int) $poId,
                    $receivedDate,
                    true,
                    '(Import receive ' . $shipment['shipment_no'] . ')',
                    $poItemToPurchaseItem
                );
                $exists = $db->fetchOne(
                    "SELECT id FROM shipment_purchases WHERE shipment_id = ? AND purchase_id = ?",
                    [$id, $purchaseId]
                );
                if (!$exists) {
                    $db->insert(
                        "INSERT INTO shipment_purchases (shipment_id, purchase_id) VALUES (?,?)",
                        [$id, $purchaseId]
                    );
                }
                $purchaseIds[] = $purchaseId;
            }
            $purchaseIds = array_values(array_unique(array_map('intval', $purchaseIds)));

            $itemChargeCount = (int) ($db->fetchOne(
                "SELECT COUNT(*) as c FROM shipment_item_charges WHERE shipment_id = ?",
                [$id]
            )['c'] ?? 0);

            if ($itemChargeCount > 0) {
                $result = LandedCostAllocator::applyItemCharges($db, $id, $purchaseIds, $poItemToPurchaseItem);
                ImportPayableAccrualService::createFromShipment(
                    $db,
                    $id,
                    $receivedDate,
                    (int) ($shipment['warehouse_id'] ?? 0) ?: $this->warehouseId()
                );
            } else {
                LandedCostAllocator::finalizePerPieceAmounts($db, $id, $purchaseIds);
                $result = LandedCostAllocator::applyShipmentCosts($db, $id, $purchaseIds);
            }

            $db->execute(
                "UPDATE shipments SET status = 'applied', received_date = ? WHERE id = ?",
                [$receivedDate, $id]
            );

            $db->commit();
            self::clearDashboardCache($this->warehouseId());

            $this->flash(
                'success',
                "Received in Kuwait. {$result['items_touched']} line(s) updated; KWD "
                . number_format($result['total_applied'], DECIMAL_PLACES) . ' logistics applied to true unit cost.'
            );
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Receive failed: ' . $e->getMessage());
        }
        $this->redirect('?page=landedcost&action=view&id=' . $id);
    }

    public function recordPayment(): void {
        Auth::authorize('payments', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db      = $this->db();
        $costId  = $this->inputInt('cost_id');
        $partyId = $this->inputInt('party_id');
        $accId   = $this->inputInt('account_id');
        $date    = $this->input('date') ?: date('Y-m-d');
        $notes   = $this->input('notes');

        $cost = $db->fetchOne(
            "SELECT sc.*, s.shipment_no, s.warehouse_id, s.id as shipment_id
             FROM shipment_costs sc
             JOIN shipments s ON s.id = sc.shipment_id
             WHERE sc.id = ?",
            [$costId]
        );
        if (!$cost) {
            $this->flash('error', 'Cost line not found.');
            $this->redirect('?page=landedcost');
            return;
        }
        if (!empty($cost['payment_id'])) {
            $this->flash('error', 'This cost line already has a payment recorded.');
            $this->redirect('?page=landedcost&action=view&id=' . (int) $cost['shipment_id']);
            return;
        }
        if (!$partyId && !empty($cost['partner_party_id'])) {
            $partyId = (int) $cost['partner_party_id'];
        }
        if (!$partyId || !$accId) {
            $this->flash('error', 'Supplier and account are required.');
            $this->redirect('?page=landedcost&action=view&id=' . (int) $cost['shipment_id']);
            return;
        }

        $amount = (float) $cost['amount'];
        $wh     = (int) ($cost['warehouse_id'] ?: $this->warehouseId());

        $db->beginTransaction();
        try {
            $last  = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $num   = $last ? (int) substr($last['payment_no'], 4) : 0;
            $payNo = 'PAY-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

            $paymentId = (int) $db->insert(
                "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, notes, warehouse_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $payNo,
                    'shipment_cost',
                    $costId,
                    $partyId,
                    'out',
                    $accId,
                    $amount,
                    'bank_transfer',
                    $date,
                    trim('Logistics: ' . $cost['description'] . ' (' . $cost['shipment_no'] . '). ' . $notes),
                    $wh,
                    Auth::id(),
                ]
            );

            $db->execute(
                "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                [$amount, $accId]
            );
            $db->execute(
                "UPDATE shipment_costs SET payment_id = ?, account_id = ? WHERE id = ?",
                [$paymentId, $accId, $costId]
            );

            $db->commit();
            $this->flash('success', "Payment {$payNo} recorded for logistics charge.");
        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Payment failed: ' . $e->getMessage());
        }
        $this->redirect('?page=landedcost&action=view&id=' . (int) $cost['shipment_id']);
    }

    public function poItems(): void {
        header('Content-Type: application/json');
        Auth::authorize('purchases', 'view');
        $raw = $_GET['po_ids'] ?? [];
        if (!is_array($raw)) {
            $raw = array_filter(explode(',', (string) $raw));
        }
        $poIds = array_values(array_filter(array_map('intval', $raw)));
        if (empty($poIds)) {
            echo json_encode(['items' => []]);
            return;
        }
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $rows = $this->db()->fetchAll(
            "SELECT poi.id as po_item_id, poi.po_id, poi.item_id, poi.quantity,
                    poi.unit_price_kwd, poi.total_kwd,
                    i.name as item_name, i.sku,
                    po.po_no
             FROM purchase_order_items poi
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id IN ($placeholders)
             ORDER BY po.po_no, i.name",
            $poIds
        );
        echo json_encode(['items' => $rows]);
    }

    public function payables(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();
        $wh = $this->warehouseId();

        $accrualRows = $db->fetchAll(
            "SELECT ipa.*, s.shipment_no, s.received_date,
                    i.name as item_name, po.po_no, p.name as party_name
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open'
               AND ipa.leg IN ('freight_hk', 'packing_dxb', 'freight_dxb')
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)
             ORDER BY ipa.date DESC, ipa.id DESC",
            [$wh, $wh]
        );
        $rows = [];
        foreach ($accrualRows as $r) {
            $rows[] = [
                'charge_id'    => (int) $r['shipment_item_charge_id'],
                'accrual_no'   => $r['accrual_no'],
                'ref_type'     => ImportPayableAccrualService::refTypeForLeg((string) $r['leg']),
                'charge_label' => ImportPayableAccrualService::legLabel((string) $r['leg']),
                'shipment_id'  => (int) $r['shipment_id'],
                'shipment_no'  => $r['shipment_no'],
                'received_date'=> $r['received_date'] ?? $r['date'],
                'po_no'        => $r['po_no'],
                'item_name'    => $r['item_name'],
                'party_id'     => (int) $r['party_id'],
                'party_name'   => $r['party_name'],
                'amount'       => (float) $r['amount'],
            ];
        }
        $totalDue = array_sum(array_map(static fn ($r) => (float) $r['amount'], $rows));

        $pageTitle = 'Freight payables';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_payables.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function partnerDue(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();
        $due = $this->fetchPartnerDueRows($db, $this->warehouseId());
        $rows = $due['rows'];
        $totalDue = $due['totalDue'];

        $dueMonths = $this->partnerDueMonths($rows);
        $settleMonth = trim($this->input('month', '', 'get'));
        if ($settleMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $settleMonth)) {
            $settleMonth = $dueMonths[0] ?? date('Y-m');
        }

        $monthRows  = $this->filterPartnerDueRowsByMonth($rows, $settleMonth);
        $monthTotal = (float) array_sum(array_map(static fn ($r) => (float) $r['amount'], $monthRows));
        $monthLabel = date('F Y', strtotime($settleMonth . '-01'));

        $importPartner = $this->importPartnerParty($db);
        $accounts = self::getAccounts();
        $canPay = Auth::can('payments', 'add');
        $canUndo = Auth::can('payments', 'delete');
        $canFixDuplicates = Auth::isAdmin() || $canUndo;
        $settlementMismatch = $this->fetchPartnerSettlementMismatch($db, $this->warehouseId(), $this->importPartnerPartyId());

        $pageTitle = 'Partner profit due';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_partner_due.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** Pay one month's partner profit in a single account transaction (monthly settlement). */
    public function payPartnerBulk(): void {
        Auth::authorize('payments', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        $accountId   = $this->inputInt('account_id');
        $date        = $this->input('date', date('Y-m-d'), 'post');
        $extraNotes  = trim($this->input('notes', '', 'post'));
        $settleMonth = trim($this->input('settle_month', '', 'post'));

        if ($accountId <= 0) {
            $this->flash('error', 'Please select the account to pay from.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }
        if ($settleMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $settleMonth)) {
            $this->flash('error', 'Please choose a valid settlement month.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        $db  = $this->db();
        $due = $this->fetchPartnerDueRows($db, $this->warehouseId());
        $payableRows = $this->filterPartnerDueRowsByMonth($due['rows'], $settleMonth);

        if (empty($payableRows)) {
            $this->flash('warning', 'Nothing to pay for ' . date('F Y', strtotime($settleMonth . '-01')) . '.');
            $this->redirect('?page=landedcost&action=partnerDue&month=' . urlencode($settleMonth));
            return;
        }

        require_once __DIR__ . '/../models/Payment.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';

        $partnerId = $this->importPartnerPartyId();
        if ($partnerId <= 0) {
            $partnerId = (int) ($payableRows[0]['partner_party_id'] ?? 0);
        }

        $chargeIds = [];
        $paidTotal = 0.0;
        foreach ($payableRows as $r) {
            $chargeId = (int) ($r['id'] ?? 0);
            $amount   = (float) ($r['amount'] ?? 0);
            if ($chargeId <= 0 || $amount <= 0.001) {
                continue;
            }
            $chargeIds[] = $chargeId;
            $paidTotal += $amount;
        }

        if ($chargeIds === [] || $partnerId <= 0 || $paidTotal <= 0.001) {
            $this->flash('error', 'Could not resolve partner or payable lines.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        $mismatch = $this->fetchPartnerSettlementMismatch($db, $this->warehouseId(), $partnerId);
        if ($mismatch !== null && ($mismatch['type'] ?? '') === 'duplicate_settlements') {
            $this->flash(
                'error',
                'Duplicate partner payments detected — undo them on Partner due before paying again.'
            );
            $this->redirect('?page=landedcost&action=partnerDue&month=' . urlencode($settleMonth));
            return;
        }

        $unlinkedLump = $this->findUnlinkedPartnerLumpPayment($db, $partnerId, $this->warehouseId(), $paidTotal);
        if ($unlinkedLump !== null) {
            $this->flash(
                'warning',
                'An unlinked payment ' . ($unlinkedLump['payment_no'] ?? '')
                . ' already exists for this amount — use “Link to existing payment” on Partner due instead.'
            );
            $this->redirect('?page=landedcost&action=partnerDue&month=' . urlencode($settleMonth));
            return;
        }

        $lineCount = count($chargeIds);
        $monthLabel = date('F Y', strtotime($settleMonth . '-01'));
        $notes = sprintf('Partner profit — %s (%d lines)', $monthLabel, $lineCount);
        if ($extraNotes !== '') {
            $notes .= ' · ' . $extraNotes;
        }

        $paymentModel = new Payment();
        $method       = $this->paymentMethodForAccount($accountId);

        $payId = $paymentModel->createStandalone([
            'party_id'       => $partnerId,
            'payment_type'   => 'out',
            'account_id'     => $accountId,
            'ref_type'       => 'shipment_partner',
            'ref_id'         => 0,
            'amount'         => round($paidTotal, 3),
            'payment_method' => $method,
            'date'           => $date,
            'notes'          => $notes,
        ]);

        if (!$payId) {
            $err = trim($paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Payment failed: ' . $err) : 'Could not save partner profit payment.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        $linked = LandedCostPaymentLinker::linkPartnerBulkPayment($db, (int) $payId, $chargeIds);
        $this->logActivity('create_payment', 'payments', (int) $payId);
        self::clearDashboardCache($this->warehouseId());

        if ($linked === $lineCount) {
            $this->flash(
                'success',
                sprintf(
                    'One payment recorded — %s %s for %s (%d lines).',
                    APP_CURRENCY,
                    number_format($paidTotal, DECIMAL_PLACES),
                    $monthLabel,
                    $lineCount
                )
            );
        } else {
            $this->flash(
                'warning',
                sprintf(
                    'Payment saved but only %d of %d lines were linked — check Partner due.',
                    $linked,
                    $lineCount
                )
            );
        }

        $this->redirect('?page=landedcost&action=partnerDue&month=' . urlencode($settleMonth));
    }

    /** @param list<array<string, mixed>> $rows */
    private function partnerRowMonth(array $row): ?string {
        $d = $row['received_date'] ?? $row['date'] ?? null;
        if ($d === null || $d === '') {
            return null;
        }
        $ts = strtotime((string) $d);
        return $ts ? date('Y-m', $ts) : null;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private function partnerDueMonths(array $rows): array {
        $months = [];
        foreach ($rows as $r) {
            if (!empty($r['legacy_cost'])) {
                continue;
            }
            $m = $this->partnerRowMonth($r);
            if ($m !== null) {
                $months[$m] = true;
            }
        }
        $list = array_keys($months);
        rsort($list);
        return $list;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterPartnerDueRowsByMonth(array $rows, string $month): array {
        return array_values(array_filter(
            $rows,
            fn (array $r): bool => empty($r['legacy_cost']) && $this->partnerRowMonth($r) === $month
        ));
    }

    public function partnerHistory(): void {
        Auth::authorize('purchases', 'view');
        $db       = $this->db();
        $wh       = $this->warehouseId();
        $fromDate = $this->input('from_date', date('Y-m-01'), 'get');
        $toDate   = $this->input('to_date', date('Y-m-t'), 'get');

        $history       = $this->fetchPartnerPaidHistory($db, $wh, $fromDate, $toDate);
        $payments      = $history['payments'];
        $linesByPayId  = $history['linesByPayId'];
        $totalPaid     = $history['totalPaid'];
        $importPartner = $this->importPartnerParty($db);
        $canEdit       = Auth::isAdmin();
        $canUndo       = Auth::can('payments', 'delete');
        $canFixDuplicates = Auth::isAdmin() || $canUndo;
        $settlementMismatch = $this->fetchPartnerSettlementMismatch($db, $wh, $this->importPartnerPartyId());

        $pageTitle = 'Partner profit history';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_partner_history.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function partnerPaymentEdit(): void {
        Auth::authorize('purchases', 'view');
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required to edit partner payments.');
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        $id = $this->inputInt('id', 0, 'get');
        $db = $this->db();
        $payment = $this->loadPartnerSettlementPayment($db, $id);
        if (!$payment) {
            $this->flash('error', 'Partner payment not found.');
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        $linkedLines = $this->fetchPartnerLinesForPayment($db, $id);
        $accounts    = self::getAccounts();
        $importPartner = $this->importPartnerParty($db);

        $pageTitle = 'Edit partner payment — ' . ($payment['payment_no'] ?? '');
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_partner_payment_edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function partnerPaymentUpdate(): void {
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Admin access required.');
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        $id = $this->inputInt('id', 0, 'get') ?: $this->inputInt('id');
        $db = $this->db();
        $payment = $this->loadPartnerSettlementPayment($db, $id);
        if (!$payment) {
            $this->flash('error', 'Partner payment not found.');
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        $linkedLines = $this->fetchPartnerLinesForPayment($db, $id);
        $linesTotal  = (float) array_sum(array_column($linkedLines, 'amount'));
        $newAmount   = $this->inputFloat('amount');
        $newAccount  = $this->inputInt('account_id') ?: (int) $payment['account_id'];
        $newDate     = $this->input('date') ?: $payment['date'];
        $newNotes    = trim($this->input('notes', '', 'post'));
        $oldAmount   = (float) $payment['amount'];

        if ($newAmount <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->redirect('?page=landedcost&action=partnerPaymentEdit&id=' . $id);
            return;
        }

        if ($linesTotal > 0.001 && abs($newAmount - $linesTotal) > 0.01) {
            $this->flash('error', 'Amount must match linked lines total (' . number_format($linesTotal, DECIMAL_PLACES) . ' ' . APP_CURRENCY . ').');
            $this->redirect('?page=landedcost&action=partnerPaymentEdit&id=' . $id);
            return;
        }

        $db->beginTransaction();
        try {
            if ($payment['payment_type'] === 'in') {
                $db->execute('UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?', [$oldAmount, $payment['account_id']]);
                $db->execute('UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?', [$newAmount, $newAccount]);
            } else {
                $db->execute('UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?', [$oldAmount, $payment['account_id']]);
                $db->execute('UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?', [$newAmount, $newAccount]);
            }

            $db->execute(
                'UPDATE payments SET amount = ?, date = ?, account_id = ?, notes = ? WHERE id = ?',
                [$newAmount, $newDate, $newAccount, $newNotes !== '' ? $newNotes : null, $id]
            );

            $db->commit();
            $this->logActivity('edit_payment', 'payments', $id, 'Edited partner settlement ' . ($payment['payment_no'] ?? ''));
            self::clearDashboardCache($this->warehouseId());
            $this->flash('success', 'Partner payment updated.');
        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect('?page=landedcost&action=partnerHistory');
    }

    /** Reopen linked profit lines and remove the payment (fix mistakes). */
    public function partnerPaymentUndo(): void {
        Auth::authorize('payments', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        $id = $this->inputInt('id');
        $db = $this->db();
        $payment = $this->loadPartnerSettlementPayment($db, $id);
        if (!$payment) {
            $this->flash('error', 'Partner payment not found.');
            $this->redirect('?page=landedcost&action=partnerHistory');
            return;
        }

        require_once __DIR__ . '/../models/Payment.php';
        $paymentModel = new Payment();
        $payNo        = (string) ($payment['payment_no'] ?? '');

        if ($paymentModel->deleteWithReversal($id)) {
            $this->logActivity('delete_payment', 'payments', $id, 'Undid partner settlement ' . $payNo);
            self::clearDashboardCache($this->warehouseId());
            $this->flash('success', "Payment {$payNo} removed — lines moved back to Partner due.");
        } else {
            $err = trim($paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Could not undo: ' . $err) : 'Could not undo payment.');
        }

        $this->redirect('?page=landedcost&action=partnerHistory');
    }

    /**
     * Fix duplicate shipment_partner payments (e.g. nine rows instead of one lump sum).
     * mode=undo_only — remove duplicates and restore Partner due.
     * mode=link_lump — remove duplicates then attach lines to an existing lump payment (no extra cash).
     */
    public function partnerReconcileDuplicates(): void {
        if (!Auth::isAdmin() && !Auth::can('payments', 'delete')) {
            $this->flash('error', 'Admin or Payments → Delete permission required.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        $mode       = trim($this->input('mode', 'undo_only', 'post'));
        $lumpPayId  = $this->inputInt('lump_payment_id');
        $duplicateIds = array_values(array_filter(array_map(
            static fn ($v) => (int) $v,
            (array) ($_POST['duplicate_ids'] ?? [])
        )));

        if ($duplicateIds === []) {
            $this->flash('error', 'No duplicate payments selected.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        require_once __DIR__ . '/../models/Payment.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';

        $db           = $this->db();
        $wh           = $this->warehouseId();
        $partnerId    = $this->importPartnerPartyId();
        $paymentModel = new Payment();
        $reopened     = 0;
        $removed      = 0;
        $removedTotal = 0.0;
        $lump         = null;

        foreach ($duplicateIds as $dupId) {
            $dup = $this->loadPaymentForPartnerReconcile($db, $dupId, $partnerId, $wh);
            if (!$dup) {
                $this->flash('error', "Payment id {$dupId} not found or not eligible for reconcile.");
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }
            $removedTotal += (float) ($dup['amount'] ?? 0);
            LandedCostPaymentLinker::reopenPartnerPayment($db, $dupId);
            if (!$paymentModel->deleteWithReversal($dupId)) {
                $this->flash('error', trim($paymentModel->getLastError()) ?: 'Could not remove duplicate payment.');
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }
            $removed++;
        }

        if ($removed === 0) {
            $this->flash('error', 'No duplicate partner payments were removed.');
            $this->redirect('?page=landedcost&action=partnerDue');
            return;
        }

        if ($mode === 'link_lump' && $lumpPayId > 0) {
            $lump = $db->fetchOne(
                "SELECT p.* FROM payments p
                 WHERE p.id = ? AND p.party_id = ? AND p.payment_type = 'out'
                   AND p.ref_type = 'purchase' AND (p.ref_id IS NULL OR p.ref_id = 0)
                   AND p.status = 'active' AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)",
                [$lumpPayId, $partnerId, $wh]
            );
            if (!$lump) {
                $this->flash('error', 'Lump-sum payment not found or not eligible for linking.');
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }

            $due = $this->fetchPartnerDueRows($db, $wh);
            $chargeIds = [];
            foreach ($due['rows'] as $row) {
                if (!empty($row['legacy_cost'])) {
                    continue;
                }
                $cid = (int) ($row['id'] ?? 0);
                if ($cid > 0) {
                    $chargeIds[] = $cid;
                }
            }

            if ($chargeIds === []) {
                $this->flash('error', 'No open partner lines to link after removing duplicates.');
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }

            $openTotal = (float) $due['totalDue'];
            $lumpAmt   = (float) ($lump['amount'] ?? 0);
            if (abs($openTotal - $lumpAmt) > 0.02) {
                $this->flash(
                    'warning',
                    sprintf(
                        'Duplicates removed (%s %s restored to Partner due). Lump payment %s is %s — amounts differ; pay or link manually.',
                        APP_CURRENCY,
                        number_format($openTotal, DECIMAL_PLACES),
                        $lump['payment_no'] ?? '',
                        number_format($lumpAmt, DECIMAL_PLACES)
                    )
                );
                self::clearDashboardCache($wh);
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }

            $linked = LandedCostPaymentLinker::linkExistingPartnerPayment($db, $lumpPayId, $chargeIds);
            if ($linked !== count($chargeIds)) {
                $this->flash('error', "Only {$linked} of " . count($chargeIds) . ' lines linked to lump payment.');
                $this->redirect('?page=landedcost&action=partnerDue');
                return;
            }
            $reopened = $linked;
        } else {
            $countRow = $db->fetchOne(
                "SELECT COUNT(*) as c FROM import_payable_accruals
                 WHERE party_id = ? AND leg = 'partner' AND status = 'open'",
                [$partnerId]
            );
            $reopened = (int) ($countRow['c'] ?? 0);
        }

        self::clearDashboardCache($wh);

        if ($mode === 'link_lump' && $lumpPayId > 0 && $lump !== null) {
            $lumpNo = (string) ($lump['payment_no'] ?? '');
            $this->flash(
                'success',
                sprintf(
                    'Removed %d duplicate payment(s) (%s %s) and linked %d lines to %s.',
                    $removed,
                    APP_CURRENCY,
                    number_format($removedTotal, DECIMAL_PLACES),
                    $reopened,
                    $lumpNo
                )
            );
        } else {
            $this->flash(
                'success',
                sprintf(
                    'Removed %d duplicate payment(s) (%s %s). Partner due restored — %d line(s) open.',
                    $removed,
                    APP_CURRENCY,
                    number_format($removedTotal, DECIMAL_PLACES),
                    $reopened
                )
            );
        }

        $this->redirect('?page=landedcost&action=partnerDue');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPartnerSettlementMismatch(Database $db, int $wh, int $partnerId): ?array {
        if ($partnerId <= 0) {
            return null;
        }

        $whSql = '(p.warehouse_id = ? OR p.warehouse_id IS NULL)';

        // Paid accruals split across multiple payments on one day (most reliable signal).
        $accrualDup = $db->fetchAll(
            "SELECT p.date, COUNT(DISTINCT p.id) as cnt,
                    SUM(p.amount) as total,
                    GROUP_CONCAT(DISTINCT p.id ORDER BY p.id) as pay_ids,
                    GROUP_CONCAT(DISTINCT p.payment_no ORDER BY p.id SEPARATOR ', ') as pay_nos
             FROM import_payable_accruals ipa
             JOIN payments p ON p.id = ipa.payment_id AND p.status = 'active'
             WHERE ipa.leg = 'partner' AND ipa.party_id = ? AND ipa.status = 'paid'
               AND ipa.payment_id IS NOT NULL AND {$whSql}
             GROUP BY p.date
             HAVING cnt > 1
             ORDER BY p.date DESC
             LIMIT 1",
            [$partnerId, $wh]
        );

        $dupGroups = $accrualDup;
        if ($dupGroups === []) {
            $dupGroups = $db->fetchAll(
                "SELECT p.date, COUNT(*) as cnt, SUM(p.amount) as total,
                        GROUP_CONCAT(p.id ORDER BY p.id) as pay_ids,
                        GROUP_CONCAT(p.payment_no ORDER BY p.id SEPARATOR ', ') as pay_nos
                 FROM payments p
                 WHERE p.party_id = ? AND p.payment_type = 'out' AND p.status = 'active'
                   AND {$whSql}
                   AND (
                     p.ref_type = 'shipment_partner'
                     OR p.notes LIKE '%Partner profit%'
                     OR p.notes LIKE '%partner profit%'
                   )
                 GROUP BY p.date
                 HAVING cnt > 1
                 ORDER BY p.date DESC
                 LIMIT 1",
                [$partnerId, $wh]
            );
        }

        // Same-day cluster of small outbound pays to partner (e.g. PAY-569…577).
        if ($dupGroups === []) {
            $dupGroups = $db->fetchAll(
                "SELECT p.date, COUNT(*) as cnt, SUM(p.amount) as total,
                        GROUP_CONCAT(p.id ORDER BY p.id) as pay_ids,
                        GROUP_CONCAT(p.payment_no ORDER BY p.id SEPARATOR ', ') as pay_nos
                 FROM payments p
                 WHERE p.party_id = ? AND p.payment_type = 'out' AND p.status = 'active'
                   AND {$whSql}
                 GROUP BY p.date
                 HAVING cnt >= 3
                 ORDER BY p.date DESC
                 LIMIT 1",
                [$partnerId, $wh]
            );
        }

        $lumps = $this->fetchUnlinkedPartnerLumpPayments($db, $partnerId, $wh);
        $due   = $this->fetchPartnerDueRows($db, $wh);

        if ($dupGroups !== []) {
            $g = $dupGroups[0];
            return [
                'type'          => 'duplicate_settlements',
                'date'          => $g['date'],
                'pay_ids'       => array_map('intval', explode(',', (string) $g['pay_ids'])),
                'pay_nos'       => (string) $g['pay_nos'],
                'total'         => (float) $g['total'],
                'count'         => (int) $g['cnt'],
                'open_due'      => (float) $due['totalDue'],
                'lump_payments' => $lumps,
            ];
        }

        if ((float) $due['totalDue'] < 0.001 && $lumps !== []) {
            return [
                'type'          => 'unlinked_lump',
                'open_due'      => 0.0,
                'lump_payments' => $lumps,
            ];
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function loadPaymentForPartnerReconcile(Database $db, int $id, int $partnerId, int $wh): ?array {
        if ($id <= 0 || $partnerId <= 0) {
            return null;
        }
        return $db->fetchOne(
            "SELECT p.*, pa.name as party_name, a.name as account_name
             FROM payments p
             JOIN parties pa ON pa.id = p.party_id
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.id = ? AND p.party_id = ? AND p.payment_type = 'out'
               AND p.status = 'active'
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)",
            [$id, $partnerId, $wh]
        ) ?: null;
    }

    /** @return array<string, mixed>|null */
    private function loadPartnerSettlementPayment(Database $db, int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        return $db->fetchOne(
            "SELECT p.*, pa.name as party_name, a.name as account_name
             FROM payments p
             JOIN parties pa ON pa.id = p.party_id
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.id = ? AND p.ref_type = 'shipment_partner' AND p.status = 'active'
               AND p.payment_type = 'out'
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)",
            [$id, $this->warehouseId()]
        ) ?: null;
    }

    /** @return list<array<string, mixed>> */
    private function fetchUnlinkedPartnerLumpPayments(Database $db, int $partnerId, int $wh): array {
        return $db->fetchAll(
            "SELECT p.id, p.payment_no, p.amount, p.date, a.name as account_name
             FROM payments p
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.party_id = ? AND p.payment_type = 'out' AND p.status = 'active'
               AND p.ref_type = 'purchase' AND (p.ref_id IS NULL OR p.ref_id = 0)
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)
               AND NOT EXISTS (
                 SELECT 1 FROM import_payable_accruals ipa
                 WHERE ipa.payment_id = p.id AND ipa.leg = 'partner'
               )
             ORDER BY p.date DESC, p.id DESC",
            [$partnerId, $wh]
        );
    }

    /** @return array<string, mixed>|null */
    private function findUnlinkedPartnerLumpPayment(Database $db, int $partnerId, int $wh, float $amount): ?array {
        foreach ($this->fetchUnlinkedPartnerLumpPayments($db, $partnerId, $wh) as $row) {
            if (abs((float) ($row['amount'] ?? 0) - $amount) < 0.02) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @return array{payments: list<array<string, mixed>>, linesByPayId: array<int, list<array<string, mixed>>>, totalPaid: float}
     */
    private function fetchPartnerPaidHistory(Database $db, int $wh, string $fromDate, string $toDate): array {
        $payments = $db->fetchAll(
            "SELECT p.id, p.payment_no, p.date, p.amount, p.notes, p.ref_id, p.account_id,
                    a.name as account_name, pa.name as partner_name,
                    (SELECT COUNT(*) FROM import_payable_accruals ipa
                     WHERE ipa.payment_id = p.id AND ipa.leg = 'partner') as line_count
             FROM payments p
             JOIN parties pa ON pa.id = p.party_id
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.ref_type = 'shipment_partner' AND p.status = 'active'
               AND p.payment_type = 'out' AND p.warehouse_id = ?
               AND p.date BETWEEN ? AND ?
             ORDER BY p.date DESC, p.id DESC",
            [$wh, $fromDate, $toDate]
        );

        $payIds = array_map(static fn ($p) => (int) $p['id'], $payments);
        $linesByPayId = [];
        $totalPaid = 0.0;

        foreach ($payments as $p) {
            $totalPaid += (float) $p['amount'];
        }

        if ($payIds !== []) {
            $ph = implode(',', array_fill(0, count($payIds), '?'));
            $lines = $db->fetchAll(
                "SELECT ipa.payment_id, ipa.amount, ipa.accrual_no, ipa.date as accrual_date,
                        s.shipment_no, s.id as shipment_id, s.received_date,
                        po.po_no, i.name as item_name,
                        sic.partner_profit_per_pc, sic.quantity
                 FROM import_payable_accruals ipa
                 JOIN shipments s ON s.id = ipa.shipment_id
                 JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
                 JOIN items i ON i.id = sic.item_id
                 JOIN purchase_order_items poi ON poi.id = sic.po_item_id
                 JOIN purchase_orders po ON po.id = poi.po_id
                 WHERE ipa.leg = 'partner' AND ipa.status = 'paid' AND ipa.payment_id IN ({$ph})
                 ORDER BY ipa.payment_id DESC, ipa.date DESC, ipa.id DESC",
                $payIds
            );
            foreach ($lines as $line) {
                $pid = (int) ($line['payment_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $linesByPayId[$pid][] = $line;
            }
        }

        return [
            'payments'     => $payments,
            'linesByPayId' => $linesByPayId,
            'totalPaid'    => $totalPaid,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fetchPartnerLinesForPayment(Database $db, int $paymentId): array {
        if ($paymentId <= 0) {
            return [];
        }
        return $db->fetchAll(
            "SELECT ipa.amount, ipa.accrual_no, s.shipment_no, s.id as shipment_id,
                    po.po_no, i.name as item_name, sic.partner_profit_per_pc, sic.quantity
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             WHERE ipa.payment_id = ? AND ipa.leg = 'partner' AND ipa.status = 'paid'
             ORDER BY s.received_date DESC, ipa.id DESC",
            [$paymentId]
        );
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totalDue: float}
     */
    private function fetchPartnerDueRows(Database $db, int $wh): array {
        $accrualRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as id, ipa.shipment_id, ipa.amount,
                    ipa.accrual_no, s.shipment_no, s.received_date, ipa.date,
                    p.name as partner_name, p.id as partner_party_id,
                    sic.partner_profit_per_pc, sic.quantity,
                    i.name as item_name, po.po_no
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open' AND ipa.leg = 'partner'
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)
             ORDER BY ipa.date DESC, ipa.id DESC",
            [$wh, $wh]
        );
        $rows = [];
        foreach ($accrualRows as $ar) {
            $rows[] = [
                'id'                    => (int) $ar['id'],
                'shipment_id'           => (int) $ar['shipment_id'],
                'amount'                => (float) $ar['amount'],
                'shipment_no'           => $ar['shipment_no'],
                'received_date'         => $ar['received_date'] ?? $ar['date'],
                'partner_name'          => $ar['partner_name'],
                'partner_party_id'      => (int) $ar['partner_party_id'],
                'item_name'             => $ar['item_name'],
                'po_no'                 => $ar['po_no'],
                'partner_profit_per_pc' => (float) $ar['partner_profit_per_pc'],
                'quantity'              => (int) $ar['quantity'],
                'accrual_no'            => $ar['accrual_no'],
            ];
        }

        $legacy = $db->fetchAll(
            "SELECT sc.id, sc.shipment_id, sc.amount, sc.description,
                    s.shipment_no, s.received_date, p.name as partner_name, sc.partner_party_id
             FROM shipment_costs sc
             JOIN shipments s ON s.id = sc.shipment_id
             LEFT JOIN parties p ON p.id = sc.partner_party_id
             WHERE sc.pay_timing = 'monthly'
               AND sc.payment_id IS NULL
               AND sc.is_applied = 1
               AND sc.amount > 0
               AND (s.warehouse_id = ? OR s.warehouse_id IS NULL)",
            [$wh]
        );
        foreach ($legacy as $lr) {
            $rows[] = [
                'id'               => (int) $lr['id'],
                'shipment_id'      => (int) $lr['shipment_id'],
                'amount'           => (float) $lr['amount'],
                'shipment_no'      => $lr['shipment_no'],
                'received_date'    => $lr['received_date'],
                'partner_name'     => $lr['partner_name'],
                'partner_party_id' => (int) ($lr['partner_party_id'] ?? 0),
                'item_name'        => null,
                'po_no'            => null,
                'description'      => $lr['description'],
                'legacy_cost'      => true,
            ];
        }

        return [
            'rows'     => $rows,
            'totalDue' => (float) array_sum(array_map(static fn ($r) => (float) $r['amount'], $rows)),
        ];
    }

    private function paymentMethodForAccount(int $accountId): string {
        if ($accountId <= 0) {
            return 'cash';
        }
        $row = $this->db()->fetchOne('SELECT type, name FROM accounts WHERE id = ?', [$accountId]);
        $type = strtolower(trim((string) ($row['type'] ?? '')));
        $map  = [
            'cash'          => 'cash',
            'bank'          => 'bank_transfer',
            'bank_transfer' => 'bank_transfer',
            'mobile_wallet' => 'mobile_wallet',
            'card'          => 'card',
        ];
        return $map[$type] ?? 'cash';
    }

    public function report(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();
        $wh = $this->warehouseId();

        $rows = $db->fetchAll(
            "SELECT s.shipment_no, s.received_date,
                    p.invoice_no, i.name as item_name, pi.quantity, pi.unit_price as unit_landed_kwd,
                    pi.total as line_total, p.grand_total, COALESCE(p.landed_cost, 0) as purchase_landed_cost,
                    CASE WHEN p.grand_total > 0.001
                         THEN (pi.total / p.grand_total) * COALESCE(p.landed_cost, 0)
                         ELSE 0 END as logistics_kwd
             FROM shipments s
             JOIN shipment_purchases sp ON sp.shipment_id = s.id
             JOIN purchases p ON p.id = sp.purchase_id
             JOIN purchase_items pi ON pi.purchase_id = p.id
             JOIN items i ON i.id = pi.item_id
             WHERE s.status = 'applied' AND (s.warehouse_id = ? OR s.warehouse_id IS NULL)
             ORDER BY s.received_date DESC, s.id DESC, p.invoice_no, pi.id
             LIMIT 500",
            [$wh]
        );
        foreach ($rows as &$row) {
            $row['goods_kwd'] = max(0, (float) $row['line_total'] - (float) $row['logistics_kwd']);
        }
        unset($row);

        $pageTitle = 'True Import Cost';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_report.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    private function loadShipment(Database $db, int $id): ?array {
        $shipment = $db->fetchOne("SELECT * FROM shipments WHERE id = ?", [$id]);
        if (!$shipment) {
            return null;
        }

        $shipment['purchase_orders'] = $db->fetchAll(
            "SELECT po.id, po.po_no, po.date, po.subtotal_kwd, po.status, po.currency, par.name as supplier_name
             FROM shipment_purchase_orders spo
             JOIN purchase_orders po ON po.id = spo.po_id
             JOIN parties par ON par.id = po.party_id
             WHERE spo.shipment_id = ?
             ORDER BY po.date DESC",
            [$id]
        );
        $shipment['purchases'] = $db->fetchAll(
            "SELECT p.id, p.invoice_no, p.date, p.grand_total, p.landed_cost, par.name as supplier_name
             FROM shipment_purchases sp
             JOIN purchases p ON p.id = sp.purchase_id
             JOIN parties par ON par.id = p.party_id
             WHERE sp.shipment_id = ?",
            [$id]
        );
        $shipment['costs'] = $db->fetchAll(
            "SELECT sc.*, a.name as account_name, pay.payment_no, pt.name as partner_name
             FROM shipment_costs sc
             LEFT JOIN accounts a ON a.id = sc.account_id
             LEFT JOIN payments pay ON pay.id = sc.payment_id
             LEFT JOIN parties pt ON pt.id = sc.partner_party_id
             WHERE sc.shipment_id = ?
             ORDER BY sc.id",
            [$id]
        );
        $shipment['item_charges'] = $db->fetchAll(
            "SELECT sic.*, i.name as item_name, i.sku, po.po_no,
                    phk.name as hk_party_name, ppack.name as packing_party_name,
                    pkwt.name as kwt_party_name, pp.name as partner_name,
                    pay_hk.payment_no as hk_payment_no,
                    pay_pack.payment_no as packing_payment_no,
                    pay_dxb.payment_no as dxb_payment_no,
                    pay_pt.payment_no as partner_payment_no
             FROM shipment_item_charges sic
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             LEFT JOIN parties phk ON phk.id = sic.freight_hk_dxb_party_id
             LEFT JOIN parties ppack ON ppack.id = sic.packing_dxb_party_id
             LEFT JOIN parties pkwt ON pkwt.id = sic.freight_dxb_kwt_party_id
             LEFT JOIN parties pp ON pp.id = sic.partner_party_id
             LEFT JOIN payments pay_hk ON pay_hk.id = sic.freight_hk_payment_id
             LEFT JOIN payments pay_pack ON pay_pack.id = sic.packing_dxb_payment_id
             LEFT JOIN payments pay_dxb ON pay_dxb.id = sic.freight_dxb_payment_id
             LEFT JOIN payments pay_pt ON pay_pt.id = sic.partner_payment_id
             WHERE sic.shipment_id = ?
             ORDER BY po.po_no, i.name",
            [$id]
        );

        return $shipment;
    }

    /** @return array<string,mixed> */
    private function prepareShipmentFormContext(?array $shipment): array {
        $db         = $this->db();
        $wh         = $this->warehouseId();
        $shipmentId = $shipment ? (int) $shipment['id'] : null;

        $freightDxbParty = $this->freightDxbForwarderParty($db);
        $defaultFreightHkPartyId = $this->resolveFreightForwarderId($db, $this->defaultFreightHkForwarderName());

        return [
            'purchaseOrders'           => $this->purchaseOrdersForForm($db, $wh, $shipmentId),
            'freightForwarders'        => $this->freightForwarderParties($db),
            'importPartner'            => $this->importPartnerParty($db),
            'defaultFreightHkPartyId'  => $defaultFreightHkPartyId,
            'defaultFreightHkPartyName' => $this->forwarderDisplayName($db, $defaultFreightHkPartyId, $this->defaultFreightHkForwarderName()),
            'defaultPackingPartyId'    => $this->resolveFreightForwarderId($db, $this->defaultPackingDxbForwarderName()),
            'defaultFreightDxbPartyId' => $freightDxbParty ? (int) $freightDxbParty['id'] : 0,
            'fixedFreightDxbPartyName' => $freightDxbParty
                ? (string) $freightDxbParty['name']
                : $this->defaultFreightDxbForwarderName(),
            'nextNo'                   => $shipment ? (string) $shipment['shipment_no'] : $this->nextShipmentNo(),
            'shipment'                 => $shipment,
            'selectedPoIds'            => $shipment
                ? array_map('intval', array_column($shipment['purchase_orders'] ?? [], 'id'))
                : [],
            'existingChargesMap'       => $shipment
                ? $this->existingChargesMap($shipment['item_charges'] ?? [])
                : [],
        ];
    }

    /** @param array<string,mixed> $shipment */
    private function assertShipmentEditable(array $shipment): void {
        if (($shipment['status'] ?? '') === 'applied') {
            throw new Exception('This shipment was received in Kuwait and cannot be edited.');
        }

        $db = $this->db();
        $id = (int) $shipment['id'];

        $purchaseCount = (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM shipment_purchases WHERE shipment_id = ?",
            [$id]
        )['c'] ?? 0);
        if ($purchaseCount > 0) {
            throw new Exception('This shipment has linked purchase invoices and cannot be edited.');
        }

        $lockedCharge = $db->fetchOne(
            "SELECT id FROM shipment_item_charges
             WHERE shipment_id = ?
               AND (
                   is_applied = 1
                   OR freight_hk_payment_id IS NOT NULL
                   OR packing_dxb_payment_id IS NOT NULL
                   OR freight_dxb_payment_id IS NOT NULL
                   OR partner_payment_id IS NOT NULL
               )
             LIMIT 1",
            [$id]
        );
        if ($lockedCharge) {
            throw new Exception('Logistics costs on this shipment are already applied or paid.');
        }
    }

    /** @param list<array<string,mixed>> $itemCharges */
    private function existingChargesMap(array $itemCharges): array {
        $map = [];
        foreach ($itemCharges as $c) {
            $poItemId = (int) ($c['po_item_id'] ?? 0);
            if ($poItemId <= 0) {
                continue;
            }
            $qty = max(1, (int) ($c['quantity'] ?? 1));
            $map[$poItemId] = [
                'freight_hk'            => round((float) ($c['freight_hk_dxb'] ?? 0) / $qty, 3),
                'freight_hk_party_id'   => (int) ($c['freight_hk_dxb_party_id'] ?? 0),
                'packing_dxb'           => round((float) ($c['packing_dxb'] ?? 0) / $qty, 3),
                'packing_dxb_party_id'  => (int) ($c['packing_dxb_party_id'] ?? 0),
                'freight_dxb'           => round((float) ($c['freight_dxb_kwt'] ?? 0) / $qty, 3),
                'freight_dxb_party_id'  => (int) ($c['freight_dxb_kwt_party_id'] ?? 0),
                'partner_pc'            => (float) ($c['partner_profit_per_pc'] ?? 0),
            ];
        }
        return $map;
    }

    /** @param list<int> $poIds */
    private function syncShipmentPurchaseOrders(Database $db, int $shipmentId, array $poIds, int $wh): void {
        $current = array_map('intval', array_column(
            $db->fetchAll("SELECT po_id FROM shipment_purchase_orders WHERE shipment_id = ?", [$shipmentId]),
            'po_id'
        ));
        $newIds = array_values(array_unique(array_map('intval', $poIds)));

        foreach (array_diff($newIds, $current) as $poId) {
            $this->assertPoLinkable($db, $poId, $wh, $shipmentId);
            $db->insert(
                "INSERT INTO shipment_purchase_orders (shipment_id, po_id) VALUES (?,?)",
                [$shipmentId, $poId]
            );
        }

        foreach (array_diff($current, $newIds) as $poId) {
            $db->execute(
                "DELETE FROM shipment_purchase_orders WHERE shipment_id = ? AND po_id = ?",
                [$shipmentId, $poId]
            );
        }
    }

    /** @param list<array<string,mixed>> $charges */
    private function replaceItemCharges(Database $db, int $shipmentId, array $charges): void {
        $db->execute("DELETE FROM shipment_item_charges WHERE shipment_id = ?", [$shipmentId]);
        $this->insertItemCharges($db, $shipmentId, $charges);
    }

    /** @return list<array<string,mixed>> */
    private function purchaseOrdersForForm(Database $db, int $wh, ?int $exceptShipmentId): array {
        return $db->fetchAll(
            "SELECT po.id, po.po_no, po.date, po.subtotal_kwd, po.currency, po.status, par.name as supplier_name
             FROM purchase_orders po
             JOIN parties par ON par.id = po.party_id
             WHERE po.status IN ('draft','paid')
               AND po.warehouse_id = ?
               AND po.id NOT IN (
                   SELECT spo.po_id FROM shipment_purchase_orders spo
                   JOIN shipments s ON s.id = spo.shipment_id
                   WHERE s.status != 'applied'
                     AND (? IS NULL OR s.id != ?)
               )
             ORDER BY po.date DESC, po.id DESC
             LIMIT 120",
            [$wh, $exceptShipmentId, $exceptShipmentId]
        );
    }

    /** @return list<array<string,mixed>> */
    private function openPurchaseOrders(Database $db, int $wh): array {
        return $this->purchaseOrdersForForm($db, $wh, null);
    }

    /** @param list<int> $poIds @param list<array<string,mixed>> $charges */
    private function assertItemChargesBelongToPos(Database $db, array $poIds, array $charges): void {
        if (empty($poIds) || empty($charges)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        foreach ($charges as $ch) {
            $poItemId = (int) ($ch['po_item_id'] ?? 0);
            if ($poItemId <= 0) {
                continue;
            }
            $row = $db->fetchOne(
                "SELECT poi.id, po.po_no, i.name as item_name
                 FROM purchase_order_items poi
                 JOIN purchase_orders po ON po.id = poi.po_id
                 JOIN items i ON i.id = poi.item_id
                 WHERE poi.id = ? AND poi.po_id IN ($placeholders)",
                array_merge([$poItemId], $poIds)
            );
            if (!$row) {
                throw new Exception(
                    'Item charge row #' . $poItemId . ' is not on the selected POs. '
                    . 'Reload the form so PO lines refresh, then save again.'
                );
            }
        }
    }

    /** @param list<int> $poIds */
    private function assertItemChargesMatchLinkedPos(Database $db, int $shipmentId, array $poIds): void {
        if (empty($poIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $orphan = $db->fetchOne(
            "SELECT sic.po_item_id, po.po_no, i.name as item_name
             FROM shipment_item_charges sic
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN items i ON i.id = sic.item_id
             WHERE sic.shipment_id = ?
               AND poi.po_id NOT IN ($placeholders)
             LIMIT 1",
            array_merge([$shipmentId], $poIds)
        );
        if ($orphan) {
            throw new Exception(
                'Item charge for ' . $orphan['po_no'] . ' (' . $orphan['item_name'] . ') '
                . 'does not belong to a PO linked to this shipment. Edit the shipment and re-save item charges.'
            );
        }

        $stale = $db->fetchOne(
            "SELECT sic.po_item_id, i.name as item_name
             FROM shipment_item_charges sic
             LEFT JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN items i ON i.id = sic.item_id
             WHERE sic.shipment_id = ?
               AND poi.id IS NULL
             LIMIT 1",
            [$shipmentId]
        );
        if ($stale) {
            throw new Exception(
                'Item charge for ' . $stale['item_name'] . ' references an old PO line (#' . (int) $stale['po_item_id'] . '). '
                . 'The PO was edited after this shipment was saved — edit the shipment and re-save item charges.'
            );
        }
    }

    private function assertPoLinkable(Database $db, int $poId, int $wh, ?int $exceptShipmentId): void {
        $po = $db->fetchOne("SELECT id, po_no, status, warehouse_id, currency FROM purchase_orders WHERE id = ?", [$poId]);
        if (!$po) {
            throw new Exception("PO #{$poId} not found.");
        }
        if ((int) $po['warehouse_id'] !== $wh) {
            throw new Exception("PO {$po['po_no']} belongs to another warehouse.");
        }
        if ($po['status'] === 'converted') {
            throw new Exception("PO {$po['po_no']} is already converted.");
        }
        if (!in_array($po['status'], ['draft', 'paid'], true)) {
            throw new Exception("PO {$po['po_no']} cannot be added (status: {$po['status']}).");
        }
        $open = $db->fetchOne(
            "SELECT s.shipment_no FROM shipment_purchase_orders spo
             JOIN shipments s ON s.id = spo.shipment_id
             WHERE spo.po_id = ? AND s.status != 'applied'
               AND (? IS NULL OR s.id != ?)",
            [$poId, $exceptShipmentId, $exceptShipmentId]
        );
        if ($open) {
            throw new Exception("PO {$po['po_no']} is already on shipment {$open['shipment_no']}.");
        }
    }

    /** @return list<array<string,mixed>> */
    private function freightForwarderParties(Database $db): array {
        return $db->fetchAll(
            "SELECT id, name FROM parties WHERE type = 'freight_forwarder' AND is_active = 1 ORDER BY name"
        );
    }

    private function defaultFreightHkForwarderName(): string {
        return defined('IMPORT_FREIGHT_HK_FORWARDER_NAME') ? (string) IMPORT_FREIGHT_HK_FORWARDER_NAME : 'Logix One FZE';
    }

    private function defaultFreightDxbForwarderName(): string {
        return defined('IMPORT_FREIGHT_DXB_FORWARDER_NAME') ? (string) IMPORT_FREIGHT_DXB_FORWARDER_NAME : 'Hi-iq';
    }

    private function defaultFreightDxbPartyCode(): string {
        if (defined('IMPORT_FREIGHT_DXB_PARTY_CODE')) {
            return (string) IMPORT_FREIGHT_DXB_PARTY_CODE;
        }
        return '26044';
    }

    private function freightDxbForwarderPartyId(): int {
        $party = $this->freightDxbForwarderParty($this->db());
        return $party ? (int) $party['id'] : 0;
    }

    /** @return array{id:int,name:string,party_code:?string}|null */
    private function freightDxbForwarderParty(Database $db): ?array {
        $code = $this->defaultFreightDxbPartyCode();
        $row  = $db->fetchOne(
            "SELECT id, name, party_code FROM parties WHERE party_code = ? AND is_active = 1",
            [$code]
        );
        if ($row) {
            return $row;
        }
        if (ctype_digit($code)) {
            $row = $db->fetchOne(
                "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
                [(int) $code]
            );
            if ($row) {
                return $row;
            }
        }
        $id = $this->resolveFreightForwarderId($db, $this->defaultFreightDxbForwarderName());
        if ($id > 0) {
            return $db->fetchOne(
                "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
                [$id]
            ) ?: null;
        }
        return null;
    }

    private function defaultPackingDxbForwarderName(): string {
        return defined('IMPORT_PACKING_DXB_FORWARDER_NAME') ? (string) IMPORT_PACKING_DXB_FORWARDER_NAME : 'Union Logistics FZCO';
    }

    private function normalizeForwarderName(string $name): string {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $name) ?? '');
    }

    private function resolveFreightForwarderId(Database $db, string $needle): int {
        $needleNorm = $this->normalizeForwarderName($needle);
        if ($needleNorm === '') {
            return 0;
        }
        foreach ($this->freightForwarderParties($db) as $row) {
            $nameNorm = $this->normalizeForwarderName((string) $row['name']);
            if ($nameNorm === $needleNorm || str_contains($nameNorm, $needleNorm) || str_contains($needleNorm, $nameNorm)) {
                return (int) $row['id'];
            }
        }
        return 0;
    }

    private function forwarderDisplayName(Database $db, int $partyId, string $fallback): string {
        if ($partyId > 0) {
            $row = $db->fetchOne("SELECT name FROM parties WHERE id = ? AND is_active = 1", [$partyId]);
            if (!empty($row['name'])) {
                return (string) $row['name'];
            }
        }
        return $fallback;
    }

    private function importPartnerPartyCode(): string {
        if (defined('IMPORT_PARTNER_PARTY_CODE')) {
            return (string) IMPORT_PARTNER_PARTY_CODE;
        }
        if (defined('IMPORT_PARTNER_PARTY_ID')) {
            return (string) IMPORT_PARTNER_PARTY_ID;
        }
        return '26014';
    }

    private function importPartnerPartyId(): int {
        $party = $this->importPartnerParty($this->db());
        return $party ? (int) $party['id'] : 0;
    }

    /** @return array{id:int,name:string,party_code:?string}|null */
    private function importPartnerParty(Database $db): ?array {
        $code = $this->importPartnerPartyCode();
        $row  = $db->fetchOne(
            "SELECT id, name, party_code FROM parties WHERE party_code = ? AND is_active = 1",
            [$code]
        );
        if ($row) {
            return $row;
        }
        if (ctype_digit($code)) {
            return $db->fetchOne(
                "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
                [(int) $code]
            ) ?: null;
        }
        return null;
    }

    /** @param array<string,mixed> $post */
    private function parseCostLines(array $post): array {
        $costDescs        = $post['cost_desc'] ?? [];
        $costAmounts      = $post['cost_amount'] ?? [];
        $costPerPiece     = $post['cost_per_piece'] ?? [];
        $costMethods      = $post['cost_method'] ?? [];
        $costCategories   = $post['cost_category'] ?? [];
        $payLocations     = $post['pay_location'] ?? [];
        $partnerPartyIds  = $post['partner_party_id'] ?? [];
        $costs            = [];
        $categories       = ['freight','consolidation','customs','clearance','handling','insurance','partner_profit','other'];

        foreach ($costDescs as $i => $desc) {
            $desc = trim((string) $desc);
            if ($desc === '') {
                continue;
            }
            $cat = $costCategories[$i] ?? 'freight';
            if (!in_array($cat, $categories, true)) {
                $cat = 'freight';
            }
            $loc    = ($payLocations[$i] ?? 'kuwait') === 'dubai' ? 'dubai' : 'kuwait';
            $perPc  = (float) ($costPerPiece[$i] ?? 0);
            $amt    = (float) ($costAmounts[$i] ?? 0);
            $partnerId = (int) ($partnerPartyIds[$i] ?? 0) ?: null;

            if ($cat === 'partner_profit') {
                if ($perPc <= 0) {
                    continue;
                }
                $costs[] = [
                    'desc'             => $desc,
                    'amount'           => 0,
                    'per_piece_kwd'    => $perPc,
                    'method'           => 'per_piece',
                    'category'         => $cat,
                    'location'         => $loc,
                    'pay_timing'       => 'monthly',
                    'partner_party_id' => $partnerId,
                ];
                continue;
            }

            if ($amt <= 0) {
                continue;
            }
            $method = $costMethods[$i] ?? 'by_qty';
            if (!in_array($method, ['by_qty', 'by_value', 'equal'], true)) {
                $method = 'by_qty';
            }
            $costs[] = [
                'desc'             => $desc,
                'amount'           => $amt,
                'per_piece_kwd'    => null,
                'method'           => $method,
                'category'         => $cat,
                'location'         => $loc,
                'pay_timing'       => 'immediate',
                'partner_party_id' => null,
            ];
        }
        return $costs;
    }

    private function insertCostLines(Database $db, int $shipmentId, array $costs): void {
        foreach ($costs as $cost) {
            $db->insert(
                "INSERT INTO shipment_costs
                    (shipment_id, description, cost_category, pay_location, pay_timing, partner_party_id,
                     amount, per_piece_kwd, allocation_method, is_applied)
                 VALUES (?,?,?,?,?,?,?,?,?,0)",
                [
                    $shipmentId,
                    $cost['desc'],
                    $cost['category'],
                    $cost['location'],
                    $cost['pay_timing'],
                    $cost['partner_party_id'],
                    $cost['amount'],
                    $cost['per_piece_kwd'],
                    $cost['method'],
                ]
            );
        }
    }

    /** @param array<string,mixed> $post */
    private function parseItemCharges(array $post): array {
        $poItemIds   = $post['ic_po_item_id'] ?? [];
        $itemIds     = $post['ic_item_id'] ?? [];
        $quantities  = $post['ic_quantity'] ?? [];
        $freightHk   = $post['ic_freight_hk'] ?? [];
        $freightHkP  = $post['ic_freight_hk_party'] ?? [];
        $packingDxb  = $post['ic_packing_dxb'] ?? [];
        $packingDxbP = $post['ic_packing_dxb_party'] ?? [];
        $freightDxb  = $post['ic_freight_dxb'] ?? [];
        $freightDxbP = $post['ic_freight_dxb_party'] ?? [];
        $partnerPc   = $post['ic_partner_pc'] ?? [];
        $partnerId   = $this->importPartnerPartyId();
        $charges     = [];

        foreach ($poItemIds as $i => $poItemId) {
            $poItemId = (int) $poItemId;
            if ($poItemId <= 0) {
                continue;
            }
            $hk   = (float) ($freightHk[$i] ?? 0);
            $pack = (float) ($packingDxb[$i] ?? 0);
            $dxb  = (float) ($freightDxb[$i] ?? 0);
            $ppc  = (float) ($partnerPc[$i] ?? 0);
            if ($hk <= 0 && $pack <= 0 && $dxb <= 0 && $ppc <= 0) {
                continue;
            }
            $qty = max(1, (int) ($quantities[$i] ?? 1));
            $dxbPartyId = (int) ($freightDxbP[$i] ?? 0)
                ?: ($dxb > 0 ? $this->freightDxbForwarderPartyId() : 0);
            $charges[] = [
                'po_item_id'              => $poItemId,
                'item_id'                 => (int) ($itemIds[$i] ?? 0),
                'quantity'                => $qty,
                'freight_hk_dxb'          => round($hk * $qty, 3),
                'freight_hk_dxb_party_id' => (int) ($freightHkP[$i] ?? 0) ?: null,
                'packing_dxb'             => round($pack * $qty, 3),
                'packing_dxb_party_id'    => (int) ($packingDxbP[$i] ?? 0) ?: null,
                'freight_dxb_kwt'         => round($dxb * $qty, 3),
                'freight_dxb_kwt_party_id'=> $dxbPartyId ?: null,
                'partner_profit_per_pc'   => $ppc,
                'partner_party_id'        => $ppc > 0 ? $partnerId : null,
            ];
        }
        return $charges;
    }

    private function insertItemCharges(Database $db, int $shipmentId, array $charges): void {
        foreach ($charges as $ch) {
            if ((int) $ch['item_id'] <= 0) {
                $poItem = $db->fetchOne(
                    "SELECT item_id, quantity FROM purchase_order_items WHERE id = ?",
                    [(int) $ch['po_item_id']]
                );
                if (!$poItem) {
                    throw new Exception('PO line #' . (int) $ch['po_item_id'] . ' not found.');
                }
                $ch['item_id']  = (int) $poItem['item_id'];
                $ch['quantity'] = (int) $poItem['quantity'];
            }
            $db->insert(
                "INSERT INTO shipment_item_charges
                    (shipment_id, po_item_id, item_id, quantity,
                     freight_hk_dxb, freight_hk_dxb_party_id,
                     packing_dxb, packing_dxb_party_id,
                     freight_dxb_kwt, freight_dxb_kwt_party_id,
                     partner_profit_per_pc, partner_party_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $shipmentId,
                    (int) $ch['po_item_id'],
                    (int) $ch['item_id'],
                    (int) $ch['quantity'],
                    (float) $ch['freight_hk_dxb'],
                    $ch['freight_hk_dxb_party_id'],
                    (float) ($ch['packing_dxb'] ?? 0),
                    $ch['packing_dxb_party_id'] ?? null,
                    (float) $ch['freight_dxb_kwt'],
                    $ch['freight_dxb_kwt_party_id'],
                    (float) $ch['partner_profit_per_pc'],
                    $ch['partner_party_id'],
                ]
            );
        }
    }

    private function nextShipmentNo(): string {
        $last = $this->db()->fetchOne("SELECT shipment_no FROM shipments ORDER BY id DESC LIMIT 1");
        $num  = $last ? (int) substr($last['shipment_no'], 4) : 0;
        return 'SHP-' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }
}
