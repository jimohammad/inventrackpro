<?php

require_once __DIR__ . '/../models/IMEI.php';

final class SaleValidator {
    /**
     * Normalize posted items, validate IMEIs, and enforce price floor when requested.
     *
     * @param array $rawItems Each item row can contain:
     *   - item_id (int)
     *   - quantity (int)
     *   - unit_price (float)
     *   - discount (float) line discount
     *   - imeis (string with newlines OR array of strings)
     *
     * @return array{items: array<int, array>, subtotal: float}
     * @throws Exception on validation failures
     */
    public static function normalizeItems(Database $db, IMEI $imeiModel, array $rawItems, int $warehouseId, string $priceFloorMode = 'none', float $eps = 0.001): array {
        $items = [];

        $allItemIds = array_values(array_unique(array_filter(array_map(
            static fn($r) => (int) ($r['item_id'] ?? 0),
            $rawItems
        ))));

        if (empty($allItemIds)) {
            throw new Exception('Please add at least one item.');
        }

        $ph = implode(',', array_fill(0, count($allItemIds), '?'));
        $itemRows = $db->fetchAll(
            "SELECT id, name, has_imei, imei_optional, sale_price
             FROM items
             WHERE id IN ({$ph})",
            $allItemIds
        );

        $itemMap = [];
        foreach ($itemRows as $r) {
            $itemMap[(int) $r['id']] = $r;
        }

        $subtotal = 0.0;

        foreach ($rawItems as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty    = (int) ($row['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }

            $price = (float) ($row['unit_price'] ?? 0);
            if ($price < 0) {
                throw new Exception('Item price cannot be negative.');
            }

            $lineDisc = (float) ($row['discount'] ?? 0);
            if ($lineDisc < 0) {
                throw new Exception('Item discount cannot be negative.');
            }

            $imeis = [];
            if (isset($row['imeis'])) {
                if (is_array($row['imeis'])) {
                    $imeis = array_filter(array_map('trim', array_map('strval', $row['imeis'])));
                } else {
                    $imeis = array_filter(array_map('trim', explode("\n", (string) $row['imeis'])));
                }
            }

            $itemInfo = $itemMap[$itemId] ?? null;
            if (!$itemInfo) {
                throw new Exception("Invalid item_id {$itemId}.");
            }

            // Validate IMEI count matches qty for IMEI items (unless optional)
            if (!empty($itemInfo['has_imei']) && empty($itemInfo['imei_optional']) && count($imeis) !== $qty) {
                throw new Exception("Item \"{$itemInfo['name']}\": IMEI count must match quantity ({$qty}).");
            }

            // Validate IMEI availability
            if (!empty($imeis)) {
                $errors = $imeiModel->validateList($imeis, $itemId, $warehouseId);
                if (!empty($errors)) {
                    throw new Exception(implode(' | ', $errors));
                }
            }

            // Price floor: catalog sale_price
            $catalogPrice = (float) ($itemInfo['sale_price'] ?? 0);
            if ($priceFloorMode === 'clamp') {
                if ($price + $eps < $catalogPrice) {
                    $price = $catalogPrice;
                }
            } elseif ($priceFloorMode === 'reject') {
                if ($price + $eps < $catalogPrice) {
                    throw new Exception("Item \"{$itemInfo['name']}\": price cannot be below catalog price (" . number_format($catalogPrice, DECIMAL_PLACES) . ').');
                }
            }

            $items[] = [
                'item_id'    => $itemId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'discount'   => $lineDisc,
                'imeis'      => $imeis,
            ];

            $subtotal += ($price * $qty) - $lineDisc;
        }

        if (empty($items)) {
            throw new Exception('Please add at least one item.');
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    /**
     * Compute current outstanding balance for a party (same formula as party statement).
     */
    public static function partyOutstanding(Database $db, int $partyId): float {
        $balRow = $db->fetchOne(
            "SELECT
                p.opening_balance
                + COALESCE((SELECT SUM(grand_total) FROM sales WHERE party_id = p.id AND status != 'cancelled'), 0)
                - COALESCE((SELECT SUM(amount) FROM payments WHERE party_id = p.id AND ref_type IN ('sale','discount')), 0)
                - COALESCE((SELECT SUM(grand_total) FROM returns WHERE party_id = p.id AND type = 'sale_return' AND status = 'approved'), 0)
                as net_balance
             FROM parties p WHERE p.id = ?",
            [$partyId]
        );
        return max(0, (float) ($balRow['net_balance'] ?? 0));
    }

    /**
     * Enforce credit limit if set (>0). Throws on failure.
     */
    public static function enforceCreditLimit(Database $db, int $partyId, float $newInvoiceTotal): void {
        $party = $db->fetchOne(
            "SELECT name, credit_limit FROM parties WHERE id = ? AND is_active = 1",
            [$partyId]
        );
        if (!$party) {
            throw new Exception('Customer is inactive or does not exist.');
        }

        $creditLimit = (float) ($party['credit_limit'] ?? 0);
        if ($creditLimit <= 0) {
            return;
        }

        $outstandingTotal  = self::partyOutstanding($db, $partyId);
        $totalAfterInvoice = $outstandingTotal + $newInvoiceTotal;

        if ($totalAfterInvoice > $creditLimit) {
            $name = (string) ($party['name'] ?? 'Customer');
            throw new Exception(
                "{$name}'s credit limit is " . APP_CURRENCY . " " . number_format($creditLimit, DECIMAL_PLACES) .
                ". Current outstanding: " . APP_CURRENCY . " " . number_format($outstandingTotal, DECIMAL_PLACES) .
                " + this invoice: " . APP_CURRENCY . " " . number_format($newInvoiceTotal, DECIMAL_PLACES) .
                " = " . APP_CURRENCY . " " . number_format($totalAfterInvoice, DECIMAL_PLACES) .
                " — exceeds limit. Collect payment first or increase the credit limit."
            );
        }
    }
}

