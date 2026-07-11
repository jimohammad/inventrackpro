<?php

/**
 * Distribute shipment logistics costs across purchase item lines (KWD).
 */
class LandedCostAllocator {

    /**
     * Set total amount on per-piece partner lines (qty known only after Kuwait receive).
     */
    public static function finalizePerPieceAmounts(Database $db, int $shipmentId, array $purchaseIds): int {
        if (empty($purchaseIds)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $totalQty     = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(quantity),0) as q FROM purchase_items WHERE purchase_id IN ($placeholders)",
            $purchaseIds
        )['q'] ?? 0);

        $lines = $db->fetchAll(
            "SELECT id, per_piece_kwd FROM shipment_costs
             WHERE shipment_id = ? AND is_applied = 0
               AND (allocation_method = 'per_piece' OR cost_category = 'partner_profit')
               AND per_piece_kwd > 0",
            [$shipmentId]
        );

        foreach ($lines as $line) {
            $amount = round((float) $line['per_piece_kwd'] * $totalQty, 3);
            $db->execute(
                "UPDATE shipment_costs SET amount = ? WHERE id = ?",
                [$amount, (int) $line['id']]
            );
        }

        return $totalQty;
    }

    /**
     * Apply all unapplied cost lines on a shipment to linked purchase items.
     *
     * @param array<int> $purchaseIds
     * @return array{items_touched:int, total_applied:float}
     * @throws Exception
     */
    public static function applyShipmentCosts(Database $db, int $shipmentId, array $purchaseIds): array {
        if (empty($purchaseIds)) {
            throw new Exception('No purchase invoices linked to this shipment.');
        }

        $costs = $db->fetchAll(
            "SELECT * FROM shipment_costs WHERE shipment_id = ? AND is_applied = 0",
            [$shipmentId]
        );
        if (empty($costs)) {
            return ['items_touched' => 0, 'total_applied' => 0.0];
        }

        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $allItems     = $db->fetchAll(
            "SELECT pi.id, pi.purchase_id, pi.item_id, pi.quantity, pi.unit_price, pi.total
             FROM purchase_items pi WHERE pi.purchase_id IN ($placeholders)",
            $purchaseIds
        );
        if (empty($allItems)) {
            throw new Exception('No purchase lines found to allocate costs.');
        }

        $totalQty   = array_sum(array_column($allItems, 'quantity'));
        $totalValue = array_sum(array_column($allItems, 'total'));
        $goodsByPurchase = [];
        foreach ($allItems as $row) {
            $pid = (int) $row['purchase_id'];
            $goodsByPurchase[$pid] = ($goodsByPurchase[$pid] ?? 0) + (float) $row['total'];
        }
        $finalUnitPriceByItemId = [];
        $purchaseIdByItemId     = [];
        $totalApplied           = 0.0;

        foreach ($costs as $cost) {
            $amount    = (float) $cost['amount'];
            $method    = $cost['allocation_method'] ?? 'by_qty';
            $perPiece  = (float) ($cost['per_piece_kwd'] ?? 0);
            $isPerPiece = $method === 'per_piece'
                || ($cost['cost_category'] ?? '') === 'partner_profit'
                || $perPiece > 0;
            $totalApplied += $amount;

            foreach ($allItems as &$item) {
                $qty = (int) $item['quantity'];
                if ($isPerPiece && $perPiece > 0) {
                    $extraPerUnit = $perPiece;
                } elseif ($method === 'by_qty') {
                    $share        = $totalQty > 0 ? ($qty / $totalQty) * $amount : 0;
                    $extraPerUnit = $qty > 0 ? $share / $qty : 0;
                } elseif ($method === 'by_value') {
                    $share        = $totalValue > 0 ? ((float) $item['total'] / $totalValue) * $amount : 0;
                    $extraPerUnit = $qty > 0 ? $share / $qty : 0;
                } else {
                    $share        = $amount / count($allItems);
                    $extraPerUnit = $qty > 0 ? $share / $qty : 0;
                }

                $item['unit_price'] = round((float) $item['unit_price'] + $extraPerUnit, 3);
                $item['total']      = round($item['unit_price'] * $qty, 3);

                $db->execute(
                    "UPDATE purchase_items SET unit_price=?, total=? WHERE id=?",
                    [$item['unit_price'], $item['total'], $item['id']]
                );

                $finalUnitPriceByItemId[(int) $item['item_id']] = (float) $item['unit_price'];
                $purchaseIdByItemId[(int) $item['item_id']]   = (int) $item['purchase_id'];
            }
            unset($item);

            $db->execute("UPDATE shipment_costs SET is_applied = 1 WHERE id = ?", [(int) $cost['id']]);
        }

        require_once __DIR__ . '/ItemCostService.php';
        foreach ($finalUnitPriceByItemId as $itemId => $unitPrice) {
            $pid = (int) ($purchaseIdByItemId[$itemId] ?? 0);
            if ($pid > 0) {
                ItemCostService::syncAfterPurchaseLine($db, (int) $itemId, $pid, (float) $unitPrice);
            }
        }

        $costTotal  = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as t FROM shipment_costs WHERE shipment_id = ?",
            [$shipmentId]
        )['t'] ?? 0);
        $goodsTotal = array_sum($goodsByPurchase);

        foreach ($purchaseIds as $pid) {
            $newTotal = (float) ($db->fetchOne(
                "SELECT COALESCE(SUM(total),0) as t FROM purchase_items WHERE purchase_id=?",
                [$pid]
            )['t'] ?? 0);
            $goodsBefore = $goodsByPurchase[$pid] ?? 0;
            $landedShare = $goodsTotal > 0 ? ($goodsBefore / $goodsTotal) * $costTotal : ($costTotal / max(1, count($purchaseIds)));
            $db->execute(
                "UPDATE purchases SET subtotal=?, grand_total=?, landed_cost=? WHERE id=?",
                [$newTotal, $newTotal, round($landedShare, 3), $pid]
            );
        }

        return [
            'items_touched' => count($allItems),
            'total_applied' => round($totalApplied, 3),
        ];
    }

    /**
     * Map PO lines to purchase lines after Kuwait receive.
     *
     * @param array<int,int> $poItemToPurchaseItem po_item_id => purchase_item_id from conversion
     */
    public static function linkItemChargesToPurchases(
        Database $db,
        int $shipmentId,
        array $purchaseIds,
        array $poItemToPurchaseItem = []
    ): void {
        if (empty($purchaseIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $charges = $db->fetchAll(
            "SELECT sic.id, sic.po_item_id, sic.item_id
             FROM shipment_item_charges sic
             WHERE sic.shipment_id = ?",
            [$shipmentId]
        );
        foreach ($charges as $ch) {
            $poItemId = (int) $ch['po_item_id'];
            $piId     = (int) ($poItemToPurchaseItem[$poItemId] ?? 0);

            if (!$piId) {
                $pi = $db->fetchOne(
                    "SELECT pi.id
                     FROM purchase_order_items poi
                     JOIN purchase_orders po ON po.id = poi.po_id
                     JOIN purchase_items pi ON pi.purchase_id = po.converted_to
                       AND pi.item_id = poi.item_id
                       AND pi.quantity = poi.quantity
                     WHERE poi.id = ?
                       AND po.converted_to IN ($placeholders)
                     LIMIT 1",
                    array_merge([$poItemId], $purchaseIds)
                );
                $piId = (int) ($pi['id'] ?? 0);
            }

            if ($piId) {
                $db->execute(
                    "UPDATE shipment_item_charges SET purchase_item_id = ? WHERE id = ?",
                    [$piId, (int) $ch['id']]
                );
            }
        }
    }

    /**
     * Apply per-item freight + partner profit to purchase unit costs.
     *
     * @param array<int,int> $poItemToPurchaseItem po_item_id => purchase_item_id from conversion
     * @return array{items_touched:int, total_applied:float}
     */
    public static function applyItemCharges(
        Database $db,
        int $shipmentId,
        array $purchaseIds,
        array $poItemToPurchaseItem = []
    ): array {
        $charges = $db->fetchAll(
            "SELECT * FROM shipment_item_charges WHERE shipment_id = ? AND is_applied = 0",
            [$shipmentId]
        );
        if (empty($charges)) {
            return ['items_touched' => 0, 'total_applied' => 0.0];
        }

        self::linkItemChargesToPurchases($db, $shipmentId, $purchaseIds, $poItemToPurchaseItem);

        $totalApplied         = 0.0;
        $itemsTouched         = 0;
        $finalUnitPriceByItem = [];
        $purchaseIdByItem     = [];
        $landedByPurchase     = [];

        foreach ($charges as $ch) {
            $piId = (int) ($ch['purchase_item_id'] ?? 0);
            if (!$piId) {
                $poItemId = (int) $ch['po_item_id'];
                $piId     = (int) ($poItemToPurchaseItem[$poItemId] ?? 0);
            }
            if (!$piId) {
                $poLine = $db->fetchOne(
                    "SELECT poi.id, po.po_no, i.name as item_name
                     FROM purchase_order_items poi
                     JOIN purchase_orders po ON po.id = poi.po_id
                     JOIN items i ON i.id = poi.item_id
                     WHERE poi.id = ?",
                    [(int) $ch['po_item_id']]
                );
                $label = $poLine
                    ? ($poLine['po_no'] . ' — ' . $poLine['item_name'])
                    : ('PO item #' . (int) $ch['po_item_id']);
                throw new Exception(
                    'Could not link item charge to purchase line (' . $label . '). '
                    . 'Edit the shipment, re-save item charges from the linked POs, then receive again.'
                );
            }

            $pi = $db->fetchOne(
                "SELECT id, purchase_id, item_id, quantity, unit_price, total FROM purchase_items WHERE id = ? FOR UPDATE",
                [$piId]
            );
            if (!$pi) {
                continue;
            }

            $qty       = max(1, (int) $pi['quantity']);
            $freightHk  = (float) $ch['freight_hk_dxb'];
            $packingDxb = (float) ($ch['packing_dxb'] ?? 0);
            $freightKw  = (float) $ch['freight_dxb_kwt'];
            $partnerPc  = (float) $ch['partner_profit_per_pc'];
            $lineExtra  = $freightHk + $packingDxb + $freightKw + ($partnerPc * $qty);
            $totalApplied += $lineExtra;

            $extraPerUnit = ($freightHk + $packingDxb + $freightKw) / $qty + $partnerPc;
            $unitPrice    = round((float) $pi['unit_price'] + $extraPerUnit, 3);
            $lineTotal    = round($unitPrice * $qty, 3);

            $db->execute(
                "UPDATE purchase_items SET unit_price=?, total=? WHERE id=?",
                [$unitPrice, $lineTotal, $piId]
            );
            $db->execute("UPDATE shipment_item_charges SET is_applied = 1 WHERE id = ?", [(int) $ch['id']]);

            $finalUnitPriceByItem[(int) $pi['item_id']] = $unitPrice;
            $purchaseIdByItem[(int) $pi['item_id']]     = (int) $pi['purchase_id'];
            $pid = (int) $pi['purchase_id'];
            $landedByPurchase[$pid] = ($landedByPurchase[$pid] ?? 0) + $lineExtra;
            $itemsTouched++;
        }

        require_once __DIR__ . '/ItemCostService.php';
        foreach ($finalUnitPriceByItem as $itemId => $unitPrice) {
            $pid = (int) ($purchaseIdByItem[$itemId] ?? 0);
            if ($pid > 0) {
                ItemCostService::syncAfterPurchaseLine($db, (int) $itemId, $pid, (float) $unitPrice);
            }
        }

        foreach ($purchaseIds as $pid) {
            $newTotal = (float) ($db->fetchOne(
                "SELECT COALESCE(SUM(total),0) as t FROM purchase_items WHERE purchase_id=?",
                [$pid]
            )['t'] ?? 0);
            $landed = (float) ($landedByPurchase[$pid] ?? 0);
            $db->execute(
                "UPDATE purchases SET subtotal=?, grand_total=?, landed_cost=? WHERE id=?",
                [$newTotal, $newTotal, round($landed, 3), $pid]
            );
        }

        return [
            'items_touched' => $itemsTouched,
            'total_applied' => round($totalApplied, 3),
        ];
    }
}
