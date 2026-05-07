<?php
require_once __DIR__ . '/BaseController.php';

class LandedCostController extends BaseController {

    private function db() { return Database::getInstance(); }

    // ── List all shipments ────────────────────────────────────────────────────
    public function index(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();

        $shipments = $db->fetchAll(
            "SELECT s.*, COUNT(DISTINCT sp.purchase_id) as po_count,
                    COALESCE(SUM(sc.amount),0) as total_cost
             FROM shipments s
             LEFT JOIN shipment_purchases sp ON sp.shipment_id = s.id
             LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id
             GROUP BY s.id ORDER BY s.created_at DESC"
        );

        $pageTitle = 'Shipments & Landed Costs';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ── New shipment form ─────────────────────────────────────────────────────
    public function create(): void {
        Auth::authorize('purchases', 'add');
        $db = $this->db();

        // All non-cancelled purchases for selection
        $purchases = $db->fetchAll(
            "SELECT p.id, p.invoice_no, p.date, p.grand_total, par.name as supplier_name
             FROM purchases p JOIN parties par ON par.id = p.party_id
             WHERE p.status != 'cancelled'
             ORDER BY p.date DESC, p.id DESC LIMIT 100"
        );
        $accounts  = self::getAccounts();
        $nextNo    = $this->nextShipmentNo();

        $pageTitle = 'New Shipment';
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // ── Save shipment + apply costs ───────────────────────────────────────────
    public function store(): void {
        Auth::authorize('purchases', 'add');
        if (!$this->isPost()) { $this->redirect('?page=landedcost'); }

        $db          = $this->db();
        $purchaseIds = array_filter(array_map('intval', $_POST['purchase_ids'] ?? []));
        $description = $this->input('description') ?: 'Shipment';
        $date        = $this->input('date') ?: date('Y-m-d');
        $notes       = $this->input('notes');

        // Cost lines
        $costDescs    = $_POST['cost_desc']   ?? [];
        $costAmounts  = $_POST['cost_amount'] ?? [];
        $costMethods  = $_POST['cost_method'] ?? [];
        $costAccounts = $_POST['cost_account'] ?? [];

        if (empty($purchaseIds)) {
            $this->flash('error', 'Select at least one purchase order.');
            $this->redirect('?page=landedcost&action=create');
        }

        $costs = [];
        foreach ($costDescs as $i => $desc) {
            $amt = (float)($costAmounts[$i] ?? 0);
            if ($desc && $amt > 0) {
                $costs[] = [
                    'desc'    => $desc,
                    'amount'  => $amt,
                    'method'  => $costMethods[$i] ?? 'by_qty',
                    'account' => (int)($costAccounts[$i] ?? 0) ?: null,
                ];
            }
        }

        if (empty($costs)) {
            $this->flash('error', 'Add at least one cost line.');
            $this->redirect('?page=landedcost&action=create');
        }

        // Load all items from selected purchases
        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $allItems = $db->fetchAll(
            "SELECT pi.id, pi.purchase_id, pi.item_id, pi.quantity, pi.unit_price, pi.total
             FROM purchase_items pi WHERE pi.purchase_id IN ($placeholders)",
            $purchaseIds
        );

        if (empty($allItems)) {
            $this->flash('error', 'No items found in selected purchases.');
            $this->redirect('?page=landedcost&action=create');
        }

        $totalQty   = array_sum(array_column($allItems, 'quantity'));
        $totalValue = array_sum(array_column($allItems, 'total'));

        $db->beginTransaction();
        try {
            // Create shipment
            $shipmentId = $db->insert(
                "INSERT INTO shipments (shipment_no, description, date, status, notes, created_by)
                 VALUES (?,?,?,'applied',?,?)",
                [$this->nextShipmentNo(), $description, $date, $notes ?: null, Auth::id()]
            );

            // Link purchases
            foreach ($purchaseIds as $pid) {
                $db->insert(
                    "INSERT INTO shipment_purchases (shipment_id, purchase_id) VALUES (?,?)",
                    [$shipmentId, $pid]
                );
            }

            // Track final landed unit prices per item_id across all purchase lines and all cost lines.
            // We'll update the item master price once per item_id at the end (not per cost line).
            $finalUnitPriceByItemId = [];

            // Apply each cost line
            foreach ($costs as $cost) {
                $db->insert(
                    "INSERT INTO shipment_costs (shipment_id, description, amount, allocation_method, account_id)
                     VALUES (?,?,?,?,?)",
                    [$shipmentId, $cost['desc'], $cost['amount'], $cost['method'], $cost['account']]
                );

                // Deduct from account
                if ($cost['account']) {
                    $db->execute(
                        "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                        [$cost['amount'], $cost['account']]
                    );
                }

                // Distribute to items
                foreach ($allItems as &$item) {
                    if ($cost['method'] === 'by_qty') {
                        $share = $totalQty > 0 ? ($item['quantity'] / $totalQty) * $cost['amount'] : 0;
                    } elseif ($cost['method'] === 'by_value') {
                        $share = $totalValue > 0 ? ($item['total'] / $totalValue) * $cost['amount'] : 0;
                    } else {
                        $share = $cost['amount'] / count($allItems);
                    }

                    $extraPerUnit      = $item['quantity'] > 0 ? $share / $item['quantity'] : 0;
                    $item['unit_price'] = round((float)$item['unit_price'] + $extraPerUnit, 3);
                    $item['total']      = round($item['unit_price'] * $item['quantity'], 3);

                    $db->execute(
                        "UPDATE purchase_items SET unit_price=?, total=? WHERE id=?",
                        [$item['unit_price'], $item['total'], $item['id']]
                    );

                    $finalUnitPriceByItemId[(int) $item['item_id']] = (float) $item['unit_price'];
                }
                unset($item);
            }

            // Apply final landed purchase_price to each item once (last computed landed unit price wins).
            foreach ($finalUnitPriceByItemId as $itemId => $unitPrice) {
                $db->execute(
                    "UPDATE items SET purchase_price = ? WHERE id = ?",
                    [(float) $unitPrice, (int) $itemId]
                );
            }

            // Update each purchase grand_total
            foreach ($purchaseIds as $pid) {
                $newTotal = $db->fetchOne(
                    "SELECT COALESCE(SUM(total),0) as t FROM purchase_items WHERE purchase_id=?", [$pid]
                );
                $db->execute(
                    "UPDATE purchases SET grand_total=?, subtotal=? WHERE id=?",
                    [$newTotal['t'], $newTotal['t'], $pid]
                );
            }

            $db->commit();
            $totalCost = array_sum(array_column($costs, 'amount'));
            $this->flash('success', "Shipment created. KWD {$totalCost} distributed across " . count($allItems) . " item lines from " . count($purchaseIds) . " purchase(s).");
            $this->redirect('?page=landedcost');

        } catch (Exception $e) {
            $db->rollback();
            $this->flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('?page=landedcost&action=create');
        }
    }

    // ── View shipment detail ──────────────────────────────────────────────────
    public function view(): void {
        Auth::authorize('purchases', 'view');
        $db = $this->db();
        $id = $this->inputInt('id', 0, 'get');

        $shipment = $db->fetchOne("SELECT * FROM shipments WHERE id=?", [$id]);
        if (!$shipment) { $this->flash('error', 'Not found.'); $this->redirect('?page=landedcost'); }

        $shipment['purchases'] = $db->fetchAll(
            "SELECT p.id, p.invoice_no, p.date, p.grand_total, par.name as supplier_name
             FROM shipment_purchases sp
             JOIN purchases p ON p.id = sp.purchase_id
             JOIN parties par ON par.id = p.party_id
             WHERE sp.shipment_id=?", [$id]
        );
        $shipment['costs'] = $db->fetchAll(
            "SELECT sc.*, a.name as account_name FROM shipment_costs sc
             LEFT JOIN accounts a ON a.id = sc.account_id
             WHERE sc.shipment_id=?", [$id]
        );

        $pageTitle = $shipment['shipment_no'];
        $page      = 'landedcost';
        ob_start();
        include __DIR__ . '/../views/purchases/landed_cost_detail.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    private function nextShipmentNo(): string {
        $last = $this->db()->fetchOne("SELECT shipment_no FROM shipments ORDER BY id DESC LIMIT 1");
        $num  = $last ? (int)substr($last['shipment_no'], 4) : 0;
        return 'SHP-' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }
}
