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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost');
            return;
        }

        $db           = $this->db();
        $wh           = $this->warehouseId();
        $poIds        = array_filter(array_map('intval', $_POST['po_ids'] ?? []));
        $description  = 'Import shipment';
        $routeStage   = 'dubai_hub';
        $status       = 'in_transit';
        $date         = $this->input('date') ?: date('Y-m-d');
        $notes        = null;
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
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

        $poIds = array_filter(array_map('intval', $_POST['po_ids'] ?? []));
        $date  = $this->input('date') ?: date('Y-m-d');

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
                "UPDATE shipments SET date = ? WHERE id = ?",
                [$date, $shipmentId]
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'add');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
               AND ipa.leg IN ('freight_hk', 'freight_dxb')
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
        $groups   = $this->groupFreightPayables($rows);
        $accounts = self::getAccounts();
        $canPay   = Auth::can('payments', 'add');
        $canLink  = Auth::can('payments', 'add') || Auth::can('payments', 'edit') || Auth::isAdmin();
        $canWriteOff = Auth::isAdmin() || Auth::can('payments', 'delete');

        // Batch once per party (avoid N slow NOT EXISTS scans that timed out nginx).
        $paymentsByParty = [];
        if ($canLink && $groups !== []) {
            foreach ($groups as $g) {
                $pid = (int) ($g['party_id'] ?? 0);
                if ($pid > 0 && !isset($paymentsByParty[$pid])) {
                    $paymentsByParty[$pid] = $this->fetchUnlinkedFreightPayments($db, $pid, $wh, 0.0);
                }
            }
        }
        foreach ($groups as &$g) {
            $pid = (int) ($g['party_id'] ?? 0);
            $candidates = $paymentsByParty[$pid] ?? [];
            $target = (float) ($g['invoice_amount'] ?? 0);
            $g['existing_payments'] = $this->rankFreightPaymentsByAmount($candidates, $target);
        }
        unset($g);

        // Logix One (HK→DXB) — banner for one-click clear when already settled
        $logixClear = null;
        if ($canWriteOff) {
            $hkParty = $this->findActivePartyByCode($db, $this->defaultFreightHkPartyCode());
            $hkId = $hkParty ? (int) $hkParty['id'] : $this->resolveFreightForwarderId(
                $db,
                defined('IMPORT_FREIGHT_HK_FORWARDER_NAME')
                    ? (string) IMPORT_FREIGHT_HK_FORWARDER_NAME
                    : 'Logix One FZE'
            );
            if ($hkId > 0) {
                $logixParty = $db->fetchOne(
                    "SELECT id, name, party_code, opening_balance FROM parties WHERE id = ?",
                    [$hkId]
                );
                if ($logixParty) {
                    $openLogix = $db->fetchOne(
                        "SELECT COUNT(*) AS c, COALESCE(SUM(ipa.amount), 0) AS t
                         FROM import_payable_accruals ipa
                         JOIN shipments s ON s.id = ipa.shipment_id
                         WHERE ipa.party_id = ? AND ipa.status = 'open' AND ipa.leg = 'freight_hk'
                           AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)",
                        [(int) $logixParty['id'], $wh, $wh]
                    );
                    $openCount = (int) ($openLogix['c'] ?? 0);
                    $openTotal = round((float) ($openLogix['t'] ?? 0), 3);
                    $opening = round((float) ($logixParty['opening_balance'] ?? 0), 3);
                    $cancelledLogix = $db->fetchOne(
                        "SELECT COUNT(*) AS c, COALESCE(SUM(ipa.amount), 0) AS t
                         FROM import_payable_accruals ipa
                         JOIN shipments s ON s.id = ipa.shipment_id
                         WHERE ipa.party_id = ? AND ipa.status = 'cancelled' AND ipa.leg = 'freight_hk'
                           AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)",
                        [(int) $logixParty['id'], $wh, $wh]
                    );
                    $cancelledCount = (int) ($cancelledLogix['c'] ?? 0);
                    $cancelledTotal = round((float) ($cancelledLogix['t'] ?? 0), 3);
                    // Always show for admin — residual PAY-only imbalance needs Clear even with 0 open/cancelled.
                    $logixClear = [
                        'party_id'         => (int) $logixParty['id'],
                        'party_name'       => (string) $logixParty['name'],
                        'open_count'       => $openCount,
                        'open_total'       => $openTotal,
                        'cancelled_count'  => $cancelledCount,
                        'cancelled_total'  => $cancelledTotal,
                        'opening_balance'  => $opening,
                    ];
                }
            }
        }

        $pageTitle = 'Freight payables';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_payables.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Link open freight lines to payment(s) already recorded (no new cash / account movement).
     * Supports one PAY or several PAYs that together equal the invoice (e.g. Hi-IQ 503+387=890).
     */
    public function linkFreightExisting(): void {
        if (!(Auth::can('payments', 'add') || Auth::can('payments', 'edit') || Auth::isAdmin())) {
            Auth::authorize('payments', 'add');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $partyId    = $this->inputInt('party_id');
        $shipmentId = $this->inputInt('shipment_id');
        $refType    = trim($this->input('ref_type', '', 'post'));
        $chargeIds  = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['charge_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        $paymentIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['payment_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        // Back-compat single select
        $singleId = $this->inputInt('payment_id');
        if ($singleId > 0 && !in_array($singleId, $paymentIds, true)) {
            $paymentIds[] = $singleId;
        }

        $paymentNosRaw = trim($this->input('payment_no', '', 'post'));
        $paymentNos = [];
        if ($paymentNosRaw !== '') {
            foreach (preg_split('/[\s,;]+/', strtoupper($paymentNosRaw)) ?: [] as $no) {
                $no = trim((string) $no);
                if ($no !== '' && preg_match('/^PAY-\d+$/', $no)) {
                    $paymentNos[] = $no;
                }
            }
            $paymentNos = array_values(array_unique($paymentNos));
        }

        $allowedRefs = ['shipment_freight_hk', 'shipment_freight_dxb'];
        if ($partyId <= 0 || $shipmentId <= 0 || !in_array($refType, $allowedRefs, true) || $chargeIds === []) {
            $this->flash('error', 'Missing payee, shipment, or freight lines.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }
        if ($paymentIds === [] && $paymentNos === []) {
            $this->flash('error', 'Select one or more existing PAYs (Ctrl/Cmd-click), or type PAY numbers.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';

        $leg = match ($refType) {
            'shipment_freight_hk'  => 'freight_hk',
            default                => 'freight_dxb',
        };

        $db = $this->db();
        $wh = $this->warehouseId();

        $payments = [];
        if ($paymentIds !== []) {
            $ph = implode(',', array_fill(0, count($paymentIds), '?'));
            $found = $db->fetchAll(
                "SELECT id, payment_no, amount, party_id, status, payment_type
                 FROM payments
                 WHERE id IN ({$ph}) AND party_id = ? AND payment_type = 'out' AND status = 'active'
                   AND (warehouse_id = ? OR warehouse_id IS NULL)
                 ORDER BY amount DESC, id ASC",
                array_merge($paymentIds, [$partyId, $wh])
            );
            foreach ($found as $p) {
                $payments[(int) $p['id']] = $p;
            }
            if (count($payments) !== count($paymentIds)) {
                $this->flash('error', 'One or more selected payments were not found for this forwarder.');
                $this->redirect('?page=landedcost&action=payables');
                return;
            }
        }
        foreach ($paymentNos as $payNo) {
            $p = $db->fetchOne(
                "SELECT id, payment_no, amount, party_id, status, payment_type
                 FROM payments
                 WHERE payment_no = ? AND party_id = ? AND payment_type = 'out' AND status = 'active'
                   AND (warehouse_id = ? OR warehouse_id IS NULL)",
                [$payNo, $partyId, $wh]
            );
            if (!$p) {
                $this->flash('error', "Payment {$payNo} not found for this forwarder.");
                $this->redirect('?page=landedcost&action=payables');
                return;
            }
            $payments[(int) $p['id']] = $p;
        }

        $paymentsList = array_values($payments);
        usort($paymentsList, static fn ($a, $b) => ((float) $b['amount'] <=> (float) $a['amount']) ?: ((int) $a['id'] <=> (int) $b['id']));

        $placeholders = implode(',', array_fill(0, count($chargeIds), '?'));
        $openRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as charge_id, ipa.amount, s.shipment_no, p.name as party_name
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open'
               AND ipa.leg = ?
               AND ipa.party_id = ?
               AND ipa.shipment_id = ?
               AND ipa.shipment_item_charge_id IN ({$placeholders})
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)
             ORDER BY ipa.amount DESC, ipa.id ASC",
            array_merge([$leg, $partyId, $shipmentId], $chargeIds, [$wh, $wh])
        );

        if (count($openRows) !== count($chargeIds)) {
            $this->flash('error', 'Some lines are already paid or do not match — refresh and try again.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $allocated = round(array_sum(array_map(static fn ($r) => (float) $r['amount'], $openRows)), 3);
        $invoiceAmt = $this->suggestFreightInvoiceAmount($allocated);
        $paySum = round(array_sum(array_map(static fn ($p) => (float) $p['amount'], $paymentsList)), 3);
        $tolerance = max(2.0, round($allocated * 0.02, 3)); // allow ~890 vs 888 rounding
        if (abs($paySum - $invoiceAmt) > $tolerance && abs($paySum - $allocated) > $tolerance) {
            $payNos = implode(' + ', array_map(static fn ($p) => (string) $p['payment_no'], $paymentsList));
            $this->flash(
                'error',
                sprintf(
                    'Selected %s = %s does not match invoice ~%s / allocated %s — select PAYs that together equal the Hi-IQ total.',
                    $payNos,
                    number_format($paySum, DECIMAL_PLACES),
                    number_format($invoiceAmt, DECIMAL_PLACES),
                    number_format($allocated, DECIMAL_PLACES)
                )
            );
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        // Distribute charge lines across payments (fill largest PAY first).
        $assignments = $this->assignFreightChargesToPayments($openRows, $paymentsList);
        $linked = 0;
        $usedPayNos = [];
        foreach ($assignments as $payId => $chargeIdList) {
            if ($chargeIdList === []) {
                continue;
            }
            $n = LandedCostPaymentLinker::linkExistingFreightPayment($db, $refType, (int) $payId, $chargeIdList);
            $linked += $n;
            foreach ($paymentsList as $p) {
                if ((int) $p['id'] === (int) $payId) {
                    $usedPayNos[] = (string) $p['payment_no'];
                    break;
                }
            }
            $this->logActivity('link_freight_payment', 'payments', (int) $payId);
        }
        require_once __DIR__ . '/../models/Party.php';
        Party::clearBalanceListCache();
        self::clearDashboardCache($wh);

        $partyName = (string) ($openRows[0]['party_name'] ?? '');
        $shipmentNo = (string) ($openRows[0]['shipment_no'] ?? '');
        $payLabel = implode(', ', $usedPayNos !== [] ? $usedPayNos : array_map(static fn ($p) => (string) $p['payment_no'], $paymentsList));

        if ($linked === count($chargeIds)) {
            $this->flash(
                'success',
                sprintf(
                    'Marked paid via %s (%s KWD) — %d lines on %s cleared, no new cash.',
                    $payLabel,
                    number_format($paySum, DECIMAL_PLACES),
                    $linked,
                    $shipmentNo !== '' ? $shipmentNo : 'shipment'
                )
            );
        } else {
            $this->flash('warning', sprintf('Only %d of %d lines linked to %s.', $linked, count($chargeIds), $payLabel));
        }

        $this->redirect('?page=landedcost&action=payables');
    }

    /**
     * Greedy fill: assign open charge lines to existing payments by remaining capacity.
     *
     * @param list<array<string, mixed>> $charges  each: charge_id, amount
     * @param list<array<string, mixed>> $payments each: id, amount
     * @return array<int, list<int>> payment_id => charge_ids
     */
    private function assignFreightChargesToPayments(array $charges, array $payments): array {
        $remaining = [];
        $out = [];
        foreach ($payments as $p) {
            $pid = (int) ($p['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $remaining[$pid] = round((float) ($p['amount'] ?? 0), 3);
            $out[$pid] = [];
        }
        if ($remaining === []) {
            return [];
        }

        $payIds = array_keys($remaining);
        foreach ($charges as $c) {
            $chargeId = (int) ($c['charge_id'] ?? 0);
            $amt = round((float) ($c['amount'] ?? 0), 3);
            if ($chargeId <= 0) {
                continue;
            }

            $chosen = null;
            foreach ($payIds as $pid) {
                if ($remaining[$pid] + 0.001 >= $amt) {
                    $chosen = $pid;
                    break;
                }
            }
            if ($chosen === null) {
                // Last payment absorbs leftover rounding / over-allocation
                $chosen = $payIds[count($payIds) - 1];
            }
            $out[$chosen][] = $chargeId;
            $remaining[$chosen] = round($remaining[$chosen] - $amt, 3);
        }

        return $out;
    }

    /**
     * Write off open freight lines with no cash movement (cancel accruals).
     * Use when the forwarder (e.g. Logix One) is already settled and party/cash is zero —
     * do NOT use if matching PAYs exist (use Mark paid / multi-PAY link instead).
     */
    public function writeOffFreightOpen(): void {
        if (!(Auth::isAdmin() || Auth::can('payments', 'delete'))) {
            $this->flash('error', 'Only admin can write off open freight without a payment.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $partyId    = $this->inputInt('party_id');
        $shipmentId = $this->inputInt('shipment_id');
        $refType    = trim($this->input('ref_type', '', 'post'));
        $reason     = trim($this->input('reason', '', 'post'));
        $chargeIds  = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['charge_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        $allowedRefs = ['shipment_freight_hk', 'shipment_freight_dxb'];
        if ($partyId <= 0 || $shipmentId <= 0 || !in_array($refType, $allowedRefs, true) || $chargeIds === []) {
            $this->flash('error', 'Missing payee, shipment, or freight lines.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';

        $leg = match ($refType) {
            'shipment_freight_hk' => 'freight_hk',
            default               => 'freight_dxb',
        };

        $db = $this->db();
        $wh = $this->warehouseId();
        $placeholders = implode(',', array_fill(0, count($chargeIds), '?'));
        $openRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as charge_id, ipa.amount, s.shipment_no, p.name as party_name
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open'
               AND ipa.leg = ?
               AND ipa.party_id = ?
               AND ipa.shipment_id = ?
               AND ipa.shipment_item_charge_id IN ({$placeholders})
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)",
            array_merge([$leg, $partyId, $shipmentId], $chargeIds, [$wh, $wh])
        );

        if ($openRows === []) {
            $this->flash('warning', 'Nothing left to write off — already cleared.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $allocated = round(array_sum(array_map(static fn ($r) => (float) $r['amount'], $openRows)), 3);
        $partyName = (string) ($openRows[0]['party_name'] ?? 'forwarder');
        $shipmentNo = (string) ($openRows[0]['shipment_no'] ?? '');
        $note = $reason !== ''
            ? ('WRITE-OFF no cash: ' . $reason)
            : sprintf('WRITE-OFF no cash — %s settled / account zero (%s)', $partyName, $shipmentNo);

        $cancelled = ImportPayableAccrualService::cancelOpenByCharges(
            $db,
            $refType,
            array_map(static fn ($r) => (int) $r['charge_id'], $openRows),
            $note
        );

        $this->logActivity('writeoff_freight', 'import_payable_accruals', $shipmentId, $note);
        require_once __DIR__ . '/../models/Party.php';
        Party::clearBalanceListCache();
        self::clearDashboardCache($wh);

        $this->flash(
            'success',
            sprintf(
                'Wrote off %d open line(s) — %s %s on %s (%s). Freight payables and party ledger liability cleared; no cash moved.',
                $cancelled,
                APP_CURRENCY,
                number_format($allocated, DECIMAL_PLACES),
                $shipmentNo !== '' ? $shipmentNo : 'shipment',
                $partyName
            )
        );
        $this->redirect('?page=landedcost&action=payables');
    }

    /**
     * Clear Logix One on Party Master + freight payables (admin).
     * Prefer link existing PAYs → paid accruals; cancel only leftovers; then force net ledger to 0
     * via opening_balance offset (prior clear set opening=0 and left PAYs → false -KWD on Party Master).
     */
    public function writeOffLogixAll(): void {
        // Opening-balance offset is high risk — admin only (not payments:delete).
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Only admin can clear all Logix freight.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
        require_once __DIR__ . '/../models/Party.php';

        $db = $this->db();
        $wh = $this->warehouseId();
        $hkParty = $this->findActivePartyByCode($db, $this->defaultFreightHkPartyCode());
        $partyId = $hkParty ? (int) $hkParty['id'] : $this->resolveFreightForwarderId(
            $db,
            defined('IMPORT_FREIGHT_HK_FORWARDER_NAME')
                ? (string) IMPORT_FREIGHT_HK_FORWARDER_NAME
                : 'Logix One FZE'
        );
        if ($partyId <= 0) {
            $this->flash('error', 'Logix One party not found in Party Master.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $party = $db->fetchOne(
            "SELECT id, name, opening_balance, warehouse_id FROM parties WHERE id = ?",
            [$partyId]
        );
        $openingBefore = round((float) ($party['opening_balance'] ?? 0), 3);
        $scopeSql = ' AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)';

        // Restore cancelled HK freight so they can be marked paid against existing cash.
        $cancelledRows = $db->fetchAll(
            "SELECT ipa.id
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             WHERE ipa.party_id = ? AND ipa.status = 'cancelled' AND ipa.leg = 'freight_hk'{$scopeSql}",
            [$partyId, $wh, $wh]
        );
        $restored = 0;
        if ($cancelledRows !== []) {
            $ids = array_map(static fn ($r) => (int) $r['id'], $cancelledRows);
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'open', payment_id = NULL
                 WHERE id IN ({$ph}) AND status = 'cancelled'",
                $ids
            );
            $restored = count($ids);
        }

        $openRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as charge_id, ipa.amount
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             WHERE ipa.party_id = ? AND ipa.status = 'open' AND ipa.leg = 'freight_hk'{$scopeSql}
             ORDER BY ipa.amount DESC, ipa.id ASC",
            [$partyId, $wh, $wh]
        );

        $openTotal = round(array_sum(array_map(static fn ($r) => (float) $r['amount'], $openRows)), 3);
        $linked = 0;
        $cancelled = 0;

        // All balance-counting outbound PAYs (not only freight-shaped refs / last 18 months).
        $paymentsList = $this->fetchLogixOutboundPaymentsForClear($db, $partyId, $wh);
        if ($openRows !== [] && $paymentsList !== []) {
            $assignments = $this->assignFreightChargesToPayments($openRows, $paymentsList);
            foreach ($assignments as $payId => $chargeIdList) {
                if ($chargeIdList === []) {
                    continue;
                }
                $linked += LandedCostPaymentLinker::linkExistingFreightPayment(
                    $db,
                    'shipment_freight_hk',
                    (int) $payId,
                    $chargeIdList
                );
            }
        }

        // Leftover open with no PAY capacity → write off liability only.
        $stillOpen = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as charge_id, ipa.amount
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             WHERE ipa.party_id = ? AND ipa.status = 'open' AND ipa.leg = 'freight_hk'{$scopeSql}",
            [$partyId, $wh, $wh]
        );
        if ($stillOpen !== []) {
            $openTotal = round(array_sum(array_map(static fn ($r) => (float) $r['amount'], $stillOpen)), 3);
            $cancelled = ImportPayableAccrualService::cancelOpenByCharges(
                $db,
                'shipment_freight_hk',
                array_map(static fn ($r) => (int) $r['charge_id'], $stillOpen),
                'WRITE-OFF all Logix HK freight — account already clear / settled'
            );
        }

        // Guarantee Party Master = 0 (handles wrong party.warehouse_id that blocked prior clears).
        require_once __DIR__ . '/../services/PartyLedgerZeroService.php';
        Party::clearBalanceListCache();
        $zero = PartyLedgerZeroService::forceNetToZero(
            $db,
            new Party(),
            $partyId,
            $wh,
            sprintf('after restore=%d link=%d cancel=%d', $restored, $linked, $cancelled)
        );
        $openingAdjusted = abs($zero['opening_after'] - $zero['opening_before']) > 0.001
            || !empty($zero['warehouse_cleared']);
        $openingAfter = $zero['opening_after'];
        $net = $zero['net_before'];

        $this->logActivity(
            'writeoff_logix_all',
            'import_payable_accruals',
            $partyId,
            sprintf(
                'Cleared Logix: restored %d, linked %d, cancelled %d, %s',
                $restored,
                $linked,
                $cancelled,
                $zero['message']
            )
        );
        self::clearDashboardCache($wh);

        $bits = [];
        if ($restored > 0) {
            $bits[] = sprintf('restored %d cancelled line(s)', $restored);
        }
        if ($linked > 0) {
            $bits[] = sprintf('linked %d line(s) to existing PAY(s)', $linked);
        }
        if ($cancelled > 0) {
            $bits[] = sprintf(
                'wrote off %d open line(s) (%s KWD)',
                $cancelled,
                number_format($openTotal, DECIMAL_PLACES)
            );
        }
        if (!empty($zero['warehouse_cleared'])) {
            $bits[] = 'cleared wrong home warehouse so opening applies on Main';
        }
        if ($openingAdjusted || abs($zero['net_before']) > 0.001) {
            $bits[] = sprintf(
                'opening %s → %s (offset residual %s)',
                number_format($zero['opening_before'], DECIMAL_PLACES),
                number_format($zero['opening_after'], DECIMAL_PLACES),
                number_format($zero['net_before'], DECIMAL_PLACES)
            );
        }
        if (!$zero['ok']) {
            $this->flash('error', $zero['message']);
        } elseif ($bits === []) {
            $this->flash('success', ($party['name'] ?? 'Logix') . ' ledger already nets to 0.');
        } else {
            $this->flash(
                'success',
                ($party['name'] ?? 'Logix One') . ' cleared: ' . implode('; ', $bits)
                . '. Hard-refresh Freight Forwarders (Ctrl+F5).'
            );
        }
        $this->redirect('?page=landedcost&action=payables');
    }

    /**
     * Outbound payments that count on party balance for Logix clear/link (any non-excluded ref_type).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchLogixOutboundPaymentsForClear(Database $db, int $partyId, int $wh): array {
        if ($partyId <= 0) {
            return [];
        }
        $rows = $db->fetchAll(
            "SELECT p.id, p.payment_no, p.amount, p.date, p.ref_type, p.notes
             FROM payments p
             WHERE p.party_id = ?
               AND p.payment_type = 'out'
               AND p.status = 'active'
               AND p.ref_type IS NOT NULL AND p.ref_type != ''
               AND p.ref_type NOT IN ('discount', 'expense', 'purchase_order')
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)
             ORDER BY p.amount DESC, p.id ASC",
            [$partyId, $wh]
        );

        // Prefer payments not already tied to a paid accrual (still allow reuse if only cancelled).
        $payIds = array_values(array_filter(array_map(static fn ($r) => (int) ($r['id'] ?? 0), $rows)));
        $paidLinked = [];
        if ($payIds !== []) {
            $ph = implode(',', array_fill(0, count($payIds), '?'));
            foreach ($db->fetchAll(
                "SELECT DISTINCT payment_id FROM import_payable_accruals
                 WHERE payment_id IN ({$ph}) AND status = 'paid'",
                $payIds
            ) as $lr) {
                $paidLinked[(int) $lr['payment_id']] = true;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0 || isset($paidLinked[$id])) {
                continue;
            }
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Candidate outbound payments already recorded for this forwarder (not yet linked to freight accruals).
     * Fast path: recent payments first, then filter linked IDs in PHP (avoids slow correlated NOT EXISTS).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchUnlinkedFreightPayments(Database $db, int $partyId, int $wh, float $targetAmount): array {
        if ($partyId <= 0) {
            return [];
        }

        // Recent outbound only — party+date index friendly; LIMIT keeps Hostinger under gateway timeout.
        $since = date('Y-m-d', strtotime('-18 months'));
        $candidates = $db->fetchAll(
            "SELECT p.id, p.payment_no, p.amount, p.date, p.ref_type, p.notes, a.name as account_name
             FROM payments p
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.party_id = ?
               AND p.payment_type = 'out'
               AND p.status = 'active'
               AND p.date >= ?
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)
               AND (
                    (p.ref_type = 'purchase' AND (p.ref_id IS NULL OR p.ref_id = 0))
                 OR p.ref_type IN ('shipment_freight_hk', 'shipment_packing_dxb', 'shipment_freight_dxb', 'shipment_cost')
               )
             ORDER BY p.date DESC, p.id DESC
             LIMIT 60",
            [$partyId, $since, $wh]
        );

        if ($candidates === []) {
            return [];
        }

        $payIds = array_values(array_filter(array_map(static fn ($r) => (int) ($r['id'] ?? 0), $candidates)));
        $linked = [];
        if ($payIds !== []) {
            $ph = implode(',', array_fill(0, count($payIds), '?'));
            $linkedRows = $db->fetchAll(
                "SELECT DISTINCT payment_id
                 FROM import_payable_accruals
                 WHERE payment_id IN ({$ph})
                   AND leg IN ('freight_hk', 'packing_dxb', 'freight_dxb')",
                $payIds
            );
            foreach ($linkedRows as $lr) {
                $linked[(int) $lr['payment_id']] = true;
            }
        }

        $unlinked = [];
        foreach ($candidates as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0 || isset($linked[$id])) {
                continue;
            }
            $unlinked[] = $r;
        }

        return $this->rankFreightPaymentsByAmount($unlinked, $targetAmount);
    }

    /**
     * Prefer payments closest to the invoice/allocated amount.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function rankFreightPaymentsByAmount(array $rows, float $targetAmount): array {
        $tolerance = max(1.0, round(abs($targetAmount) * 0.01, 3));
        $scored = [];
        foreach ($rows as $r) {
            $amt = (float) ($r['amount'] ?? 0);
            $diff = abs($amt - $targetAmount);
            $r['amount_match'] = $targetAmount > 0.001 && $diff <= $tolerance;
            $r['amount_diff'] = $diff;
            $scored[] = $r;
        }

        usort($scored, static function (array $a, array $b): int {
            if (($a['amount_match'] ?? false) !== ($b['amount_match'] ?? false)) {
                return ($a['amount_match'] ?? false) ? -1 : 1;
            }
            $cmp = ($a['amount_diff'] ?? 0) <=> ($b['amount_diff'] ?? 0);
            return $cmp !== 0 ? $cmp : ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
        });

        return array_slice($scored, 0, 40);
    }

    /**
     * One payment for all open freight lines of the same party + shipment + leg
     * (e.g. Hi-IQ invoice covering every PO line on the shipment).
     */
    public function payFreightBulk(): void {
        Auth::authorize('payments', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $accountId  = $this->inputInt('account_id');
        $partyId    = $this->inputInt('party_id');
        $shipmentId = $this->inputInt('shipment_id');
        $refType    = trim($this->input('ref_type', '', 'post'));
        $date       = $this->input('date', date('Y-m-d'), 'post');
        $extraNotes = trim($this->input('notes', '', 'post'));
        $payAmount  = round($this->inputFloat('amount'), 3);
        $chargeIds  = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['charge_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        $allowedRefs = ['shipment_freight_hk', 'shipment_freight_dxb'];
        if ($accountId <= 0 || $partyId <= 0 || $shipmentId <= 0 || !in_array($refType, $allowedRefs, true)) {
            $this->flash('error', 'Missing payee, shipment, charge type, or account.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }
        if ($chargeIds === []) {
            $this->flash('error', 'No freight lines selected.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';
        $leg = match ($refType) {
            'shipment_freight_hk'  => 'freight_hk',
            default                => 'freight_dxb',
        };

        $db = $this->db();
        $wh = $this->warehouseId();
        $placeholders = implode(',', array_fill(0, count($chargeIds), '?'));
        $openRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as charge_id, ipa.amount, ipa.party_id, ipa.shipment_id,
                    s.shipment_no, p.name as party_name
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open'
               AND ipa.leg = ?
               AND ipa.party_id = ?
               AND ipa.shipment_id = ?
               AND ipa.shipment_item_charge_id IN ({$placeholders})
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)",
            array_merge([$leg, $partyId, $shipmentId], $chargeIds, [$wh, $wh])
        );

        if (count($openRows) !== count($chargeIds)) {
            $this->flash('error', 'Some lines are already paid or do not match this shipment — refresh and try again.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $allocated = round(array_sum(array_map(static fn ($r) => (float) $r['amount'], $openRows)), 3);
        if ($payAmount <= 0.001) {
            $payAmount = $this->suggestFreightInvoiceAmount($allocated);
        }
        $tolerance = max(1.0, round($allocated * 0.01, 3));
        if (abs($payAmount - $allocated) > $tolerance) {
            $this->flash(
                'error',
                sprintf(
                    'Invoice amount %s must be within %s of allocated %s (per-line split).',
                    number_format($payAmount, DECIMAL_PLACES),
                    number_format($tolerance, DECIMAL_PLACES),
                    number_format($allocated, DECIMAL_PLACES)
                )
            );
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        require_once __DIR__ . '/../models/Payment.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';

        $shipmentNo = (string) ($openRows[0]['shipment_no'] ?? '');
        $partyName  = (string) ($openRows[0]['party_name'] ?? '');
        $lineCount  = count($chargeIds);
        $chargeLabel = ImportPayableAccrualService::legLabel($leg);
        $notes = sprintf(
            '%s — %s / %s (%d lines, allocated %s)',
            $chargeLabel,
            $shipmentNo,
            $partyName,
            $lineCount,
            number_format($allocated, DECIMAL_PLACES)
        );
        if ($extraNotes !== '') {
            $notes .= ' · ' . $extraNotes;
        }

        $paymentModel = new Payment();
        $payId = $paymentModel->createStandalone([
            'party_id'       => $partyId,
            'payment_type'   => 'out',
            'account_id'     => $accountId,
            'ref_type'       => $refType,
            'ref_id'         => 0,
            'amount'         => $payAmount,
            'payment_method' => $this->paymentMethodForAccount($accountId),
            'date'           => $date,
            'notes'          => $notes,
        ]);

        if (!$payId) {
            $err = trim($paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Payment failed: ' . $err) : 'Could not save freight payment.');
            $this->redirect('?page=landedcost&action=payables');
            return;
        }

        $linked = LandedCostPaymentLinker::linkFreightBulkPayment($db, $refType, (int) $payId, $chargeIds);
        $this->logActivity('create_payment', 'payments', (int) $payId);
        self::clearDashboardCache($wh);

        if ($linked === $lineCount) {
            $this->flash(
                'success',
                sprintf(
                    'One payment recorded — %s %s to %s (%d lines on %s).',
                    APP_CURRENCY,
                    number_format($payAmount, DECIMAL_PLACES),
                    $partyName !== '' ? $partyName : 'forwarder',
                    $lineCount,
                    $shipmentNo !== '' ? $shipmentNo : 'shipment'
                )
            );
        } else {
            $this->flash(
                'warning',
                sprintf('Payment saved but only %d of %d lines were linked — check Freight payables.', $linked, $lineCount)
            );
        }

        $this->redirect('?page=landedcost&action=payables');
    }

    /**
     * Group open freight accruals by party + shipment + leg for one invoice payment.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function groupFreightPayables(array $rows): array {
        $groups = [];
        foreach ($rows as $r) {
            $key = (int) $r['party_id'] . '|' . (int) $r['shipment_id'] . '|' . (string) $r['ref_type'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'party_id'     => (int) $r['party_id'],
                    'party_name'   => (string) $r['party_name'],
                    'shipment_id'  => (int) $r['shipment_id'],
                    'shipment_no'  => (string) $r['shipment_no'],
                    'ref_type'     => (string) $r['ref_type'],
                    'charge_label' => (string) $r['charge_label'],
                    'po_nos'       => [],
                    'charge_ids'   => [],
                    'lines'        => [],
                    'allocated'    => 0.0,
                ];
            }
            $groups[$key]['charge_ids'][] = (int) $r['charge_id'];
            $groups[$key]['lines'][] = $r;
            $groups[$key]['allocated'] += (float) $r['amount'];
            $poNo = (string) ($r['po_no'] ?? '');
            if ($poNo !== '' && !in_array($poNo, $groups[$key]['po_nos'], true)) {
                $groups[$key]['po_nos'][] = $poNo;
            }
        }

        $out = [];
        foreach ($groups as $g) {
            $allocated = round((float) $g['allocated'], 3);
            $g['allocated'] = $allocated;
            $g['invoice_amount'] = $this->suggestFreightInvoiceAmount($allocated);
            $g['line_count'] = count($g['charge_ids']);
            $out[] = $g;
        }

        return $out;
    }

    /** Prefer whole-KWD invoice when per-line split is only rounding noise (e.g. 500.175 → 500). */
    private function suggestFreightInvoiceAmount(float $allocated): float {
        $allocated = round($allocated, 3);
        $whole = (float) round($allocated);
        if (abs($allocated - $whole) <= 0.5) {
            return $whole;
        }
        return $allocated;
    }

    /** Union Logistics packing (DXB) — monthly / invoice settlement. */
    public function packingDue(): void {
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
        $db = $this->db();
        $wh = $this->warehouseId();
        $due = $this->fetchPackingDueRows($db, $wh);
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

        $packingParty = $this->importPackingParty($db);
        $accounts = self::getAccounts();
        $canPay = Auth::can('payments', 'add');
        $canLink = Auth::can('payments', 'add') || Auth::can('payments', 'edit') || Auth::isAdmin();
        $canRepair = Auth::isAdmin() || Auth::can('payments', 'delete');

        $existingPayments = [];
        if ($canLink && $packingParty) {
            $existingPayments = $this->fetchUnlinkedPackingPayments(
                $db,
                (int) $packingParty['id'],
                $wh,
                max($monthTotal, $totalDue)
            );
        }

        $unionLedger = null;
        if ($canRepair && $packingParty) {
            require_once __DIR__ . '/../models/Party.php';
            $partyModel = new Party();
            $stmtNet = round($partyModel->computeBalanceAsOf((int) $packingParty['id'], date('Y-m-d'), $wh), 3);
            if (abs($stmtNet) > 0.001) {
                $unionLedger = [
                    'party_id'   => (int) $packingParty['id'],
                    'party_name' => (string) $packingParty['name'],
                    'net'        => $stmtNet,
                    'display'    => $stmtNet,
                ];
            }
        }

        $pageTitle = 'Packing due (Union Logistics)';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_packing_due.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Permanent fix: migrate Union packing AP to vendor bills (invoice ↔ payment).
     * Estimates stay on shipment costing / Packing due only — not on party statement.
     */
    public function migrateUnionPackingToVendorBills(): void {
        if (!(Auth::isAdmin() || Auth::can('payments', 'delete'))) {
            $this->flash('error', 'Only admin can migrate Union packing to vendor-bill AP.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        require_once __DIR__ . '/../services/PackingVendorBillService.php';

        $db = $this->db();
        $wh = $this->warehouseId();
        $packingParty = $this->importPackingParty($db);
        if (!$packingParty) {
            $this->flash('error', 'Union Logistics party not found.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        $result = PackingVendorBillService::migratePartyToInvoiceAp(
            $db,
            (int) $packingParty['id'],
            $wh
        );
        self::clearDashboardCache($wh);
        $this->logActivity(
            'migrate_union_vendor_bills',
            'parties',
            (int) $packingParty['id'],
            $result['message']
        );
        $this->flash($result['ok'] ? 'success' : 'warning', $result['message']);
        $this->redirect('?page=landedcost&action=packingDue');
    }

    /**
     * @deprecated Prefer migrateUnionPackingToVendorBills (invoice AP). Kept for old bookmarks.
     */
    public function repairUnionPackingLedger(): void {
        $this->migrateUnionPackingToVendorBills();
    }

    /**
     * Link open packing lines to an existing Union PAY (no new cash) — e.g. bank transfer already in Payments.
     */
    public function linkPackingExisting(): void {
        if (!(Auth::can('payments', 'add') || Auth::can('payments', 'edit') || Auth::isAdmin())) {
            Auth::authorize('payments', 'add');
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        $settleMonth = trim($this->input('settle_month', '', 'post'));
        $settleScope = trim($this->input('settle_scope', 'month', 'post'));
        if (!in_array($settleScope, ['month', 'to_date'], true)) {
            $settleScope = 'month';
        }

        $paymentIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['payment_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        $singleId = $this->inputInt('payment_id');
        if ($singleId > 0 && !in_array($singleId, $paymentIds, true)) {
            $paymentIds[] = $singleId;
        }

        $paymentNosRaw = trim($this->input('payment_no', '', 'post'));
        $paymentNos = [];
        if ($paymentNosRaw !== '') {
            foreach (preg_split('/[\s,;]+/', strtoupper($paymentNosRaw)) ?: [] as $no) {
                $no = trim((string) $no);
                if ($no !== '' && preg_match('/^PAY-\d+$/', $no)) {
                    $paymentNos[] = $no;
                }
            }
            $paymentNos = array_values(array_unique($paymentNos));
        }

        if ($settleMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $settleMonth)) {
            $this->flash('error', 'Please choose a valid settlement month.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }
        if ($paymentIds === [] && $paymentNos === []) {
            $this->flash('error', 'Select the existing PAY (e.g. 2 Jul transfer) or type the PAY number.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        require_once __DIR__ . '/../models/Party.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
        require_once __DIR__ . '/../services/ImportPayableAccrualService.php';

        $db = $this->db();
        $wh = $this->warehouseId();
        $packingParty = $this->importPackingParty($db);
        $partyId = $packingParty ? (int) $packingParty['id'] : 0;
        if ($partyId <= 0) {
            $this->flash('error', 'Union Logistics party not found.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        $payments = [];
        if ($paymentIds !== []) {
            $ph = implode(',', array_fill(0, count($paymentIds), '?'));
            $found = $db->fetchAll(
                "SELECT id, payment_no, amount, party_id, status, payment_type, date
                 FROM payments
                 WHERE id IN ({$ph}) AND party_id = ? AND payment_type = 'out' AND status = 'active'
                   AND (warehouse_id = ? OR warehouse_id IS NULL)
                 ORDER BY amount DESC, id ASC",
                array_merge($paymentIds, [$partyId, $wh])
            );
            foreach ($found as $p) {
                $payments[(int) $p['id']] = $p;
            }
            if (count($payments) !== count($paymentIds)) {
                $this->flash('error', 'Selected payment not found for Union Logistics (check party on the PAY).');
                $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
                return;
            }
        }
        foreach ($paymentNos as $payNo) {
            $p = $db->fetchOne(
                "SELECT id, payment_no, amount, party_id, status, payment_type, date
                 FROM payments
                 WHERE payment_no = ? AND party_id = ? AND payment_type = 'out' AND status = 'active'
                   AND (warehouse_id = ? OR warehouse_id IS NULL)",
                [$payNo, $partyId, $wh]
            );
            if (!$p) {
                $this->flash('error', "{$payNo} not found for Union Logistics — open Payment Out and confirm party.");
                $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
                return;
            }
            $payments[(int) $p['id']] = $p;
        }

        $paymentsList = array_values($payments);
        $paySum = round(array_sum(array_map(static fn ($p) => (float) $p['amount'], $paymentsList)), 3);

        $due = $this->fetchPackingDueRows($db, $wh);
        $payableRows = $settleScope === 'month'
            ? $this->filterPartnerDueRowsByMonth($due['rows'], $settleMonth)
            : $due['rows'];

        if ($payableRows === []) {
            $this->flash('warning', 'No open packing lines in this scope.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
        require_once __DIR__ . '/../services/PackingVendorBillService.php';
        PackingVendorBillService::ensureSchema($db);

        $accruedTotal = round(array_sum(array_map(static fn ($r) => (float) ($r['amount'] ?? 0), $payableRows)), 3);
        $chargeIds = [];
        foreach ($payableRows as $r) {
            $cid = (int) ($r['id'] ?? 0);
            if ($cid > 0) {
                $chargeIds[] = $cid;
            }
        }
        if ($chargeIds === []) {
            $this->flash('error', 'No packing charge lines in scope.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        // Distribute charge links across PAYs for shipment UI; AP uses vendor bills = payment amounts.
        $chargesForAssign = array_map(static fn ($id) => [
            'charge_id' => $id,
            'amount'    => (float) (array_values(array_filter(
                $payableRows,
                static fn ($r) => (int) ($r['id'] ?? 0) === $id
            ))[0]['amount'] ?? 0),
        ], $chargeIds);
        usort($paymentsList, static fn ($a, $b) => ((float) $b['amount'] <=> (float) $a['amount'])
            ?: ((int) $a['id'] <=> (int) $b['id']));
        $assignments = $this->assignFreightChargesToPayments($chargesForAssign, $paymentsList);

        $linked = 0;
        $usedPayNos = [];
        foreach ($assignments as $payId => $chargeIdList) {
            if ($chargeIdList === []) {
                continue;
            }
            $n = LandedCostPaymentLinker::linkExistingFreightPayment(
                $db,
                'shipment_packing_dxb',
                (int) $payId,
                $chargeIdList
            );
            $linked += $n;
            foreach ($paymentsList as $p) {
                if ((int) $p['id'] === (int) $payId) {
                    $usedPayNos[] = (string) $p['payment_no'];
                    break;
                }
            }
            $this->logActivity('link_packing_payment', 'payments', (int) $payId);
        }

        PackingVendorBillService::cancelPaidPackingAccruals(
            $db,
            $chargeIds,
            'Cleared into vendor bill — packing estimate not on party AP'
        );
        PackingVendorBillService::clearPackingAccrualsIntoBill(
            $db,
            $chargeIds,
            'Cleared into vendor bill — packing estimate not on party AP'
        );

        $bills = 0;
        foreach ($paymentsList as $p) {
            $pid = (int) ($p['id'] ?? 0);
            $amt = round((float) ($p['amount'] ?? 0), 3);
            if ($pid <= 0 || $amt <= 0.001) {
                continue;
            }
            $shareEst = $paySum > 0.001 ? round($accruedTotal * ($amt / $paySum), 3) : 0.0;
            $id = PackingVendorBillService::createPaidBill(
                $db,
                $partyId,
                $pid,
                $amt,
                (string) ($p['date'] ?? date('Y-m-d')),
                $settleScope === 'month' ? $settleMonth : substr((string) ($p['date'] ?? ''), 0, 7),
                $shareEst,
                $wh,
                '',
                'Linked existing PAY — packing vendor bill AP'
            );
            if ($id > 0) {
                $bills++;
            }
        }

        Party::clearBalanceListCache();
        self::clearDashboardCache($wh);

        $payLabel = implode(', ', $usedPayNos !== [] ? $usedPayNos : array_map(
            static fn ($p) => (string) $p['payment_no'],
            $paymentsList
        ));
        $this->flash(
            'success',
            sprintf(
                'Linked %s (%s KWD) → %d vendor bill(s); ERP estimate %s cleared from party AP. Statement should match invoice ↔ payment.',
                $payLabel,
                number_format($paySum, DECIMAL_PLACES),
                $bills,
                number_format($accruedTotal, DECIMAL_PLACES)
            )
        );
        $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
    }

    /**
     * Outbound Union payments not yet linked to packing accruals (any ref_type that can be a transfer).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchUnlinkedPackingPayments(Database $db, int $partyId, int $wh, float $targetAmount): array {
        if ($partyId <= 0) {
            return [];
        }

        $since = date('Y-m-d', strtotime('-24 months'));
        $candidates = $db->fetchAll(
            "SELECT p.id, p.payment_no, p.amount, p.date, p.ref_type, p.notes, a.name as account_name
             FROM payments p
             LEFT JOIN accounts a ON a.id = p.account_id
             WHERE p.party_id = ?
               AND p.payment_type = 'out'
               AND p.status = 'active'
               AND p.date >= ?
               AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)
               AND p.ref_type IS NOT NULL AND p.ref_type != ''
               AND p.ref_type NOT IN ('discount', 'expense', 'purchase_order')
             ORDER BY p.date DESC, p.id DESC
             LIMIT 80",
            [$partyId, $since, $wh]
        );

        if ($candidates === []) {
            return [];
        }

        $payIds = array_values(array_filter(array_map(static fn ($r) => (int) ($r['id'] ?? 0), $candidates)));
        $linked = [];
        if ($payIds !== []) {
            $ph = implode(',', array_fill(0, count($payIds), '?'));
            foreach ($db->fetchAll(
                "SELECT DISTINCT payment_id FROM import_payable_accruals
                 WHERE payment_id IN ({$ph}) AND leg = 'packing_dxb' AND status = 'paid'",
                $payIds
            ) as $lr) {
                $linked[(int) $lr['payment_id']] = true;
            }
            require_once __DIR__ . '/../services/PackingVendorBillService.php';
            PackingVendorBillService::ensureSchema($db);
            foreach ($db->fetchAll(
                "SELECT DISTINCT payment_id FROM import_vendor_bills
                 WHERE payment_id IN ({$ph}) AND status != 'cancelled'",
                $payIds
            ) as $lr) {
                $linked[(int) $lr['payment_id']] = true;
            }
        }

        $unlinked = [];
        foreach ($candidates as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0 || isset($linked[$id])) {
                continue;
            }
            $unlinked[] = $r;
        }

        return $this->rankFreightPaymentsByAmount($unlinked, $targetAmount);
    }

    /** Pay Union Logistics packing from their invoice (ERP lines are estimates until billed). */
    public function payPackingBulk(): void {
        Auth::authorize('payments', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        $accountId     = $this->inputInt('account_id');
        $date          = $this->input('date', date('Y-m-d'), 'post');
        $extraNotes    = trim($this->input('notes', '', 'post'));
        $settleMonth   = trim($this->input('settle_month', '', 'post'));
        $invoiceRaw    = trim($this->input('invoice_amount', '', 'post'));
        $invoiceRef    = trim($this->input('invoice_ref', '', 'post'));
        $settleScope   = trim($this->input('settle_scope', 'to_date', 'post'));
        if (!in_array($settleScope, ['month', 'to_date'], true)) {
            $settleScope = 'to_date';
        }

        if ($accountId <= 0) {
            $this->flash('error', 'Please select the account to pay from.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }
        if ($settleMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $settleMonth)) {
            $this->flash('error', 'Please choose a valid settlement month.');
            $this->redirect('?page=landedcost&action=packingDue');
            return;
        }

        $invoiceAmount = round((float) str_replace(',', '', $invoiceRaw), 3);
        if ($invoiceAmount <= 0.001) {
            $this->flash('error', 'Enter Union’s invoice amount (exact billed KWD).');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        $db  = $this->db();
        $due = $this->fetchPackingDueRows($db, $this->warehouseId());
        $payableRows = $settleScope === 'month'
            ? $this->filterPartnerDueRowsByMonth($due['rows'], $settleMonth)
            : $due['rows'];

        if (empty($payableRows)) {
            $this->flash('warning', 'Nothing unpaid to settle for this scope.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        require_once __DIR__ . '/../models/Payment.php';
        require_once __DIR__ . '/../models/Party.php';
        require_once __DIR__ . '/../services/LandedCostPaymentLinker.php';
        require_once __DIR__ . '/../services/PackingVendorBillService.php';

        PackingVendorBillService::ensureSchema($db);

        $packingParty = $this->importPackingParty($db);
        $partyId = $packingParty ? (int) $packingParty['id'] : 0;
        if ($partyId <= 0) {
            $partyId = (int) ($payableRows[0]['party_id'] ?? 0);
        }

        // Invoice AP: clear ALL open packing estimates in scope; bill = Union invoice amount.
        $chargeIds = [];
        $erpEstimate = 0.0;
        foreach ($payableRows as $r) {
            $cid = (int) ($r['id'] ?? 0);
            $amt = round((float) ($r['amount'] ?? 0), 3);
            if ($cid <= 0 || $amt <= 0.001) {
                continue;
            }
            $chargeIds[] = $cid;
            $erpEstimate += $amt;
        }
        $erpEstimate = round($erpEstimate, 3);

        if ($chargeIds === [] || $partyId <= 0) {
            $this->flash('error', 'Could not resolve Union Logistics or packing lines.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        $scopeLabel = $settleScope === 'month'
            ? date('F Y', strtotime($settleMonth . '-01'))
            : 'to date';
        $partyName = (string) ($packingParty['name'] ?? ($payableRows[0]['party_name'] ?? 'Union Logistics'));
        $notes = sprintf(
            'Packing DXB — %s — vendor invoice %s KWD (%s; ERP est. %s)',
            $partyName,
            number_format($invoiceAmount, DECIMAL_PLACES),
            $scopeLabel,
            number_format($erpEstimate, DECIMAL_PLACES)
        );
        if ($invoiceRef !== '') {
            $notes .= ' · Inv ' . $invoiceRef;
        }
        if ($extraNotes !== '') {
            $notes .= ' · ' . $extraNotes;
        }

        $paymentModel = new Payment();
        $payId = $paymentModel->createStandalone([
            'party_id'       => $partyId,
            'payment_type'   => 'out',
            'account_id'     => $accountId,
            'ref_type'       => 'shipment_packing_dxb',
            'ref_id'         => 0,
            'amount'         => $invoiceAmount,
            'payment_method' => $this->paymentMethodForAccount($accountId),
            'date'           => $date,
            'notes'          => $notes,
        ]);

        if (!$payId) {
            $err = trim($paymentModel->getLastError());
            $this->flash('error', $err !== '' ? ('Payment failed: ' . $err) : 'Could not save packing payment.');
            $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
            return;
        }

        // Shipment cost lines: mark payment on charges; then remove packing accruals from AP.
        LandedCostPaymentLinker::linkFreightBulkPayment($db, 'shipment_packing_dxb', (int) $payId, $chargeIds);
        PackingVendorBillService::cancelPaidPackingAccruals(
            $db,
            $chargeIds,
            'Cleared into vendor bill — packing estimate not on party AP'
        );
        PackingVendorBillService::clearPackingAccrualsIntoBill(
            $db,
            $chargeIds,
            'Cleared into vendor bill — packing estimate not on party AP'
        );

        $billId = PackingVendorBillService::createPaidBill(
            $db,
            $partyId,
            (int) $payId,
            $invoiceAmount,
            $date,
            $settleScope === 'month' ? $settleMonth : substr($date, 0, 7),
            $erpEstimate,
            $this->warehouseId(),
            $invoiceRef,
            $notes
        );

        $this->logActivity('create_payment', 'payments', (int) $payId);
        Party::clearBalanceListCache();
        self::clearDashboardCache($this->warehouseId());

        $this->flash(
            'success',
            sprintf(
                'Union vendor invoice posted & paid: %s %s (ERP estimate was %s). Party AP = invoice ↔ payment.',
                APP_CURRENCY,
                number_format($invoiceAmount, DECIMAL_PLACES),
                number_format($erpEstimate, DECIMAL_PLACES)
            ) . ($billId > 0 ? ' Bill #' . $billId . '.' : '')
        );

        $this->redirect('?page=landedcost&action=packingDue&month=' . urlencode($settleMonth));
    }

    /**
     * Match open packing lines to a Union invoice: FIFO pay-link up to invoice, write off ERP excess in scope.
     *
     * @param list<array<string, mixed>> $rows each: id, amount, received_date
     * @return array{pay_charge_ids: list<int>, write_off_charge_ids: list<int>, matched_accrued: float}
     */
    private function selectPackingChargesForInvoice(array $rows, float $invoiceAmount): array {
        $invoiceAmount = round($invoiceAmount, 3);
        $sorted = $rows;
        usort($sorted, static function (array $a, array $b): int {
            $da = (string) ($a['received_date'] ?? '');
            $db = (string) ($b['received_date'] ?? '');
            if ($da !== $db) {
                return $da <=> $db;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $payIds = [];
        $running = 0.0;
        $tolerance = max(2.0, round($invoiceAmount * 0.02, 3));

        foreach ($sorted as $r) {
            $id = (int) ($r['id'] ?? 0);
            $amt = round((float) ($r['amount'] ?? 0), 3);
            if ($id <= 0 || $amt <= 0.001) {
                continue;
            }
            // Keep taking lines while under invoice, or one more if still short within tolerance band.
            if ($running + 0.001 >= $invoiceAmount) {
                break;
            }
            $payIds[] = $id;
            $running = round($running + $amt, 3);
            // Stop if we met/exceeded invoice (allow small overshoot from last line).
            if ($running + 0.001 >= $invoiceAmount) {
                break;
            }
        }

        // If still far below invoice, take remaining lines (invoice higher than ERP estimate).
        if ($running + $tolerance < $invoiceAmount) {
            foreach ($sorted as $r) {
                $id = (int) ($r['id'] ?? 0);
                if ($id <= 0 || in_array($id, $payIds, true)) {
                    continue;
                }
                $payIds[] = $id;
                $running = round($running + (float) ($r['amount'] ?? 0), 3);
            }
        }

        $paySet = array_fill_keys($payIds, true);
        $writeOffIds = [];
        foreach ($sorted as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id > 0 && !isset($paySet[$id])) {
                $writeOffIds[] = $id;
            }
        }

        return [
            'pay_charge_ids'       => $payIds,
            'write_off_charge_ids' => $writeOffIds,
            'matched_accrued'      => $running,
        ];
    }

    /**
     * Open packing_dxb accruals (Union Logistics — monthly settlement).
     *
     * @return array{rows: list<array<string, mixed>>, totalDue: float}
     */
    private function fetchPackingDueRows(Database $db, int $wh): array {
        $accrualRows = $db->fetchAll(
            "SELECT ipa.shipment_item_charge_id as id, ipa.shipment_id, ipa.amount,
                    ipa.accrual_no, s.shipment_no, s.received_date, ipa.date,
                    p.name as party_name, p.id as party_id,
                    sic.packing_dxb, sic.quantity,
                    i.name as item_name, po.po_no
             FROM import_payable_accruals ipa
             JOIN shipments s ON s.id = ipa.shipment_id
             JOIN shipment_item_charges sic ON sic.id = ipa.shipment_item_charge_id
             JOIN items i ON i.id = sic.item_id
             JOIN purchase_order_items poi ON poi.id = sic.po_item_id
             JOIN purchase_orders po ON po.id = poi.po_id
             JOIN parties p ON p.id = ipa.party_id
             WHERE ipa.status = 'open' AND ipa.leg = 'packing_dxb'
               AND (ipa.warehouse_id = ? OR ipa.warehouse_id IS NULL OR s.warehouse_id = ? OR s.warehouse_id IS NULL)
             ORDER BY ipa.date DESC, ipa.id DESC",
            [$wh, $wh]
        );
        $rows = [];
        foreach ($accrualRows as $ar) {
            $qty = max(1, (int) $ar['quantity']);
            $packTotal = (float) ($ar['packing_dxb'] ?? $ar['amount']);
            $rows[] = [
                'id'            => (int) $ar['id'],
                'shipment_id'   => (int) $ar['shipment_id'],
                'amount'        => (float) $ar['amount'],
                'shipment_no'   => $ar['shipment_no'],
                'received_date' => $ar['received_date'] ?? $ar['date'],
                'party_name'    => $ar['party_name'],
                'party_id'      => (int) $ar['party_id'],
                'item_name'     => $ar['item_name'],
                'po_no'         => $ar['po_no'],
                'rate_per_pc'   => round($packTotal / $qty, 3),
                'quantity'      => $qty,
                'accrual_no'    => $ar['accrual_no'],
            ];
        }

        return [
            'rows'     => $rows,
            'totalDue' => (float) array_sum(array_map(static fn ($r) => (float) $r['amount'], $rows)),
        ];
    }

    /** @return array{id:int,name:string,party_code:?string}|null */
    private function importPackingParty(Database $db): ?array {
        $id = $this->resolveFreightForwarderId($db, $this->defaultPackingDxbForwarderName());
        if ($id <= 0) {
            return null;
        }
        return $db->fetchOne(
            "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
            [$id]
        ) ?: null;
    }

    public function partnerDue(): void {
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
        Auth::authorizeAny(['import_logistics', 'purchases'], 'view');
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
            "SELECT sc.*, a.name as account_name, pay.payment_no, pay.date as payment_date,
                    pay.created_at as payment_created_at, pt.name as partner_name
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
                    pay_hk.date as hk_payment_date,
                    pay_hk.created_at as hk_payment_at,
                    pay_pack.payment_no as packing_payment_no,
                    pay_pack.date as packing_payment_date,
                    pay_pack.created_at as packing_payment_at,
                    pay_dxb.payment_no as dxb_payment_no,
                    pay_dxb.date as dxb_payment_date,
                    pay_dxb.created_at as dxb_payment_at,
                    pay_pt.payment_no as partner_payment_no,
                    pay_pt.date as partner_payment_date,
                    pay_pt.created_at as partner_payment_at
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
        $hkFreightForwarders = $this->freightHkForwarderParties($db, $shipment);
        $defaultFreightHkPartyId = $this->resolveFreightHkDefaultPartyId($db, $shipment, $hkFreightForwarders);

        return [
            'purchaseOrders'           => $this->purchaseOrdersForForm($db, $wh, $shipmentId),
            'freightForwarders'        => $this->freightForwarderParties($db),
            'hkFreightForwarders'      => $hkFreightForwarders,
            'hkFreightForwarderMissing'=> $this->missingFreightHkPartyCodes($db),
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

    private function defaultFreightHkPartyCode(): string {
        return defined('IMPORT_FREIGHT_HK_PARTY_CODE') ? (string) IMPORT_FREIGHT_HK_PARTY_CODE : '26049';
    }

    /** @return list<string> Default first, then alternates (Logiverse, …). */
    private function freightHkForwarderPartyCodes(): array {
        $codes = [];
        $push = static function (string $code) use (&$codes): void {
            $code = trim($code);
            if ($code !== '' && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        };
        $push($this->defaultFreightHkPartyCode());
        $alt = defined('IMPORT_FREIGHT_HK_ALT_PARTY_CODES')
            ? (string) IMPORT_FREIGHT_HK_ALT_PARTY_CODES
            : '26045';
        foreach (explode(',', $alt) as $code) {
            $push($code);
        }
        return $codes;
    }

    /** @return array{id:int,name:string,party_code:?string}|null */
    private function findActivePartyByCode(Database $db, string $code): ?array {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $row = $db->fetchOne(
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
        return null;
    }

    /**
     * HK→DXB parties: Logix One (26049) and Logiverse (26045), looked up by account no.
     * Type need not be freight_forwarder — Party Master code is the source of truth.
     *
     * @param array<string,mixed>|null $shipment
     * @return list<array{id:int,name:string,party_code:?string}>
     */
    private function freightHkForwarderParties(Database $db, ?array $shipment): array {
        $seen = [];
        $out  = [];
        foreach ($this->freightHkForwarderPartyCodes() as $code) {
            $party = $this->findActivePartyByCode($db, $code);
            if (!$party) {
                continue;
            }
            $id = (int) $party['id'];
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = $party;
        }
        if ($out === []) {
            $id = $this->resolveFreightForwarderId($db, $this->defaultFreightHkForwarderName());
            if ($id > 0) {
                $row = $db->fetchOne(
                    "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
                    [$id]
                );
                if ($row) {
                    $seen[(int) $row['id']] = true;
                    $out[] = $row;
                }
            }
        }
        if ($shipment) {
            foreach ($shipment['item_charges'] ?? [] as $c) {
                $pid = (int) ($c['freight_hk_dxb_party_id'] ?? 0);
                if ($pid <= 0 || isset($seen[$pid])) {
                    continue;
                }
                $row = $db->fetchOne(
                    "SELECT id, name, party_code FROM parties WHERE id = ? AND is_active = 1",
                    [$pid]
                );
                if ($row) {
                    $seen[$pid] = true;
                    $out[] = $row;
                }
            }
        }
        return $out;
    }

    /** @return list<string> */
    private function missingFreightHkPartyCodes(Database $db): array {
        $missing = [];
        foreach ($this->freightHkForwarderPartyCodes() as $code) {
            if (!$this->findActivePartyByCode($db, $code)) {
                $missing[] = $code;
            }
        }
        return $missing;
    }

    /**
     * @param list<array{id:int,name:string,party_code:?string}> $hkParties
     * @param array<string,mixed>|null $shipment
     */
    private function resolveFreightHkDefaultPartyId(Database $db, ?array $shipment, array $hkParties): int {
        if ($shipment) {
            $counts = [];
            foreach ($shipment['item_charges'] ?? [] as $c) {
                $pid = (int) ($c['freight_hk_dxb_party_id'] ?? 0);
                if ($pid > 0) {
                    $counts[$pid] = ($counts[$pid] ?? 0) + 1;
                }
            }
            if ($counts !== []) {
                arsort($counts);
                return (int) array_key_first($counts);
            }
        }
        if ($hkParties !== []) {
            return (int) $hkParties[0]['id'];
        }
        return $this->resolveFreightForwarderId($db, $this->defaultFreightHkForwarderName());
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
        return '26058';
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
