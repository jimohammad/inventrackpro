<?php

/**
 * Keep items.purchase_price aligned with the latest non-cancelled purchase line unit cost.
 * After import receive, purchase_items.unit_price already includes freight + partner profit.
 */
class ItemCostService {

    /**
     * Latest purchase line cost for an item (true landed unit price when logistics were applied).
     *
     * @return array{unit_price:float, purchase_id:int, date:string, invoice_no:string, landed_cost:float}|null
     */
    public static function latestRealUnitCost(Database $db, int $itemId): ?array {
        if ($itemId <= 0) {
            return null;
        }

        $row = $db->fetchOne(
            "SELECT pi.unit_price, pi.purchase_id, p.date, p.invoice_no, COALESCE(p.landed_cost, 0) AS landed_cost
             FROM purchase_items pi
             INNER JOIN purchases p ON p.id = pi.purchase_id
             WHERE pi.item_id = ? AND p.status != 'cancelled'
             ORDER BY p.date DESC, p.id DESC, pi.id DESC
             LIMIT 1",
            [$itemId]
        );

        if (!$row) {
            return null;
        }

        return [
            'unit_price'   => round((float) $row['unit_price'], 3),
            'purchase_id'  => (int) $row['purchase_id'],
            'date'         => (string) $row['date'],
            'invoice_no'   => (string) $row['invoice_no'],
            'landed_cost'  => round((float) $row['landed_cost'], 3),
        ];
    }

    /**
     * Set item master cost from the latest purchase line (or 0 when none).
     */
    public static function syncItemMasterFromLatestPurchase(Database $db, int $itemId): ?float {
        if ($itemId <= 0) {
            return null;
        }

        $latest = self::latestRealUnitCost($db, $itemId);
        $price  = $latest ? $latest['unit_price'] : 0.0;

        $db->execute(
            "UPDATE items SET purchase_price = ? WHERE id = ?",
            [$price, $itemId]
        );

        return $latest ? $price : null;
    }

    /**
     * Update item master when a purchase line changes, only if that purchase is the latest basis.
     */
    public static function syncAfterPurchaseLine(
        Database $db,
        int $itemId,
        int $purchaseId,
        float $unitPrice
    ): bool {
        if ($itemId <= 0 || $purchaseId <= 0) {
            return false;
        }

        $unitPrice = round($unitPrice, 3);
        $latest    = self::latestRealUnitCost($db, $itemId);

        if (!$latest || (int) $latest['purchase_id'] === $purchaseId) {
            $db->execute(
                "UPDATE items SET purchase_price = ? WHERE id = ?",
                [$unitPrice, $itemId]
            );
            return true;
        }

        $incoming = $db->fetchOne(
            "SELECT id, date FROM purchases WHERE id = ? AND status != 'cancelled'",
            [$purchaseId]
        );
        if (!$incoming) {
            return false;
        }

        $incomingDate = (string) $incoming['date'];
        $latestDate   = (string) $latest['date'];
        $isNewer      = $incomingDate > $latestDate
            || ($incomingDate === $latestDate && $purchaseId >= (int) $latest['purchase_id']);

        if (!$isNewer) {
            return false;
        }

        $db->execute(
            "UPDATE items SET purchase_price = ? WHERE id = ?",
            [$unitPrice, $itemId]
        );

        return true;
    }

    /**
     * Rebuild item master costs from purchase history (admin backfill).
     *
     * @return array{updated:int, skipped:int}
     */
    public static function syncAllFromPurchases(Database $db): array {
        $itemIds = $db->fetchAll(
            "SELECT DISTINCT pi.item_id AS id
             FROM purchase_items pi
             INNER JOIN purchases p ON p.id = pi.purchase_id
             WHERE p.status != 'cancelled'
             ORDER BY pi.item_id"
        );

        $updated = 0;
        foreach ($itemIds as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $before = $db->fetchOne("SELECT purchase_price FROM items WHERE id = ?", [$itemId]);
            self::syncItemMasterFromLatestPurchase($db, $itemId);
            $after = $db->fetchOne("SELECT purchase_price FROM items WHERE id = ?", [$itemId]);
            if ((float) ($before['purchase_price'] ?? 0) !== (float) ($after['purchase_price'] ?? 0)) {
                $updated++;
            }
        }

        return [
            'updated' => $updated,
            'skipped' => max(0, count($itemIds) - $updated),
        ];
    }
}
