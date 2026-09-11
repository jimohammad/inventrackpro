<?php

require_once __DIR__ . '/../models/IMEI.php';
require_once __DIR__ . '/../models/Party.php';

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
     * @param bool $enforceMaxSaleQty When true (cashier/viewer), reject if invoice qty for an
     *   item exceeds items.max_sale_qty (0 = unlimited). Sums duplicate lines of the same item.
     *
     * @param string $customerKind wholesale (catalog list) or retail (catalog + 0.500 / 1.000, always reject)
     * @return array{items: array<int, array>, subtotal: float, catalog_floor: float}
     * @throws Exception on validation failures
     */
    public static function normalizeItems(Database $db, IMEI $imeiModel, array $rawItems, int $warehouseId, string $priceFloorMode = 'none', float $eps = 0.001, bool $enforceMaxSaleQty = false, string $customerKind = Party::CUSTOMER_KIND_WHOLESALE): array {
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
            "SELECT id, name, has_imei, imei_optional, sale_price, COALESCE(max_sale_qty, 0) AS max_sale_qty
             FROM items
             WHERE id IN ({$ph})",
            $allItemIds
        );

        $itemMap = [];
        foreach ($itemRows as $r) {
            $itemMap[(int) $r['id']] = $r;
        }

        $customerKind = Party::normalizeCustomerKind($customerKind, 'customer');
        $isRetail = Party::isRetailCustomer($customerKind);
        // Retail floor is a hard business rule — never clamp silently or skip for admin/API override.
        if ($isRetail) {
            $priceFloorMode = 'reject';
        }

        $subtotal = 0.0;
        $catalogFloor = 0.0;

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

            // Strict: IMEI-tracked items cannot be sold without scanning every unit
            // (ignores items.imei_optional — that flag no longer bypasses sale serials).
            if (!empty($itemInfo['has_imei']) && count($imeis) !== $qty) {
                throw new Exception(
                    "Item \"{$itemInfo['name']}\": must scan {$qty} IMEI(s) before selling (currently " . count($imeis) . "). "
                    . 'Selling without serials causes stock / IMEI mismatch.'
                );
            }

            // Validate IMEI availability
            if (!empty($imeis)) {
                $errors = $imeiModel->validateList($imeis, $itemId, $warehouseId);
                if (!empty($errors)) {
                    throw new Exception(implode(' | ', $errors));
                }
            }

            // Price floor: wholesale = catalog sale_price; retail = catalog + 0.500 (<40) or +1.000 (40+)
            $catalogPrice = (float) ($itemInfo['sale_price'] ?? 0);
            $unitFloor    = Party::unitPriceFloor($catalogPrice, $customerKind);
            if ($priceFloorMode === 'clamp') {
                if ($price + $eps < $unitFloor) {
                    $price = $unitFloor;
                }
            } elseif ($priceFloorMode === 'reject') {
                if ($price + $eps < $unitFloor) {
                    throw new Exception(self::belowFloorMessage((string) $itemInfo['name'], $unitFloor, $isRetail));
                }
            }

            $lineGross = $price * $qty;
            if ($lineDisc > $lineGross + $eps) {
                throw new Exception('Item discount cannot exceed the line amount.');
            }
            if ($priceFloorMode === 'clamp' || $priceFloorMode === 'reject') {
                $lineFloor = $unitFloor * $qty;
                $maxDisc   = max(0.0, $lineGross - $lineFloor);
                if ($lineDisc > $maxDisc + $eps) {
                    if ($priceFloorMode === 'reject') {
                        throw new Exception(
                            $isRetail
                                ? ("Item \"{$itemInfo['name']}\": discount cannot sell below retail minimum ("
                                    . number_format($unitFloor, DECIMAL_PLACES) . ').')
                                : ("Item \"{$itemInfo['name']}\": discount cannot sell below catalog price ("
                                    . number_format($catalogPrice, DECIMAL_PLACES) . ').')
                        );
                    }
                    $lineDisc = $maxDisc;
                }
            }

            $items[] = [
                'item_id'    => $itemId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'discount'   => $lineDisc,
                'imeis'      => $imeis,
            ];

            $subtotal     += $lineGross - $lineDisc;
            $catalogFloor += $unitFloor * $qty;
        }

        if (empty($items)) {
            throw new Exception('Please add at least one item.');
        }

        if ($enforceMaxSaleQty) {
            $qtyByItem = [];
            foreach ($items as $line) {
                $id = (int) $line['item_id'];
                $qtyByItem[$id] = ($qtyByItem[$id] ?? 0) + (int) $line['quantity'];
            }
            foreach ($qtyByItem as $itemId => $totalQty) {
                $info = $itemMap[$itemId] ?? null;
                if (!$info) {
                    continue;
                }
                $maxQty = (int) ($info['max_sale_qty'] ?? 0);
                if ($maxQty > 0 && $totalQty > $maxQty) {
                    throw new Exception(
                        "Item \"{$info['name']}\": salesman max qty is {$maxQty} per invoice "
                        . "(you entered {$totalQty}). Ask a manager/admin to sell more, or lower the quantity."
                    );
                }
            }
        }

        return ['items' => $items, 'subtotal' => $subtotal, 'catalog_floor' => $catalogFloor];
    }

    /**
     * Header invoice discount. When a price floor is on, grand total cannot fall
     * below the sum of catalog sale_price × qty (same rule as unit price).
     */
    public static function normalizeHeaderDiscount(
        float $headerDisc,
        float $subtotal,
        float $catalogFloor,
        string $priceFloorMode = 'none',
        float $eps = 0.001
    ): float {
        if ($headerDisc < 0 || $headerDisc > $subtotal + $eps) {
            throw new Exception('Invoice discount must be between zero and the item subtotal.');
        }
        if ($priceFloorMode !== 'clamp' && $priceFloorMode !== 'reject') {
            return $headerDisc;
        }

        $maxHeader = max(0.0, $subtotal - max(0.0, $catalogFloor));
        if ($headerDisc <= $maxHeader + $eps) {
            return min($headerDisc, $maxHeader);
        }
        if ($priceFloorMode === 'reject') {
            throw new Exception('Invoice discount cannot reduce the total below the customer price floor.');
        }

        return $maxHeader;
    }

    /**
     * Branch-scoped receivable outstanding (positive balance only) for credit checks.
     * Uses Party::currentNetBalance — same ledger as Party Master / statements.
     * $db kept for call-site compatibility.
     */
    public static function partyOutstanding(Database $db, int $partyId): float {
        unset($db);
        return max(0.0, (new Party())->currentNetBalance($partyId));
    }

    /**
     * Enforce credit limit if set (>0). Throws on failure.
     * No role bypass — admin, manager, cashier, and API keys are all blocked.
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
                " — exceeds limit. Collect payment first. This cannot be overridden on the invoice (including admin)."
            );
        }
    }

    /** Raise a wholesale cashier unit price up to catalog. Retail stays unchanged (assertRetail rejects). */
    public static function applyCashierUnitFloor(
        float $unitPrice,
        float $catalogPrice,
        string $customerKind,
        float $eps = 0.001
    ): float {
        if (Party::isRetailCustomer($customerKind)) {
            return $unitPrice;
        }
        $floor = Party::unitPriceFloor($catalogPrice, $customerKind);
        if ($unitPrice + $eps < $floor) {
            return $floor;
        }
        return $unitPrice;
    }

    /** After sale_items mutations: cashier cannot exceed items.max_sale_qty on the invoice. */
    public static function assertMaxSaleQtyForSale(Database $db, int $saleId): void {
        $rows = $db->fetchAll(
            "SELECT i.name, COALESCE(i.max_sale_qty, 0) AS max_sale_qty, SUM(si.quantity) AS qty
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             WHERE si.sale_id = ?
             GROUP BY si.item_id, i.name, i.max_sale_qty
             HAVING max_sale_qty > 0 AND SUM(si.quantity) > max_sale_qty",
            [$saleId]
        );
        if (empty($rows)) {
            return;
        }
        $r = $rows[0];
        throw new Exception(
            'Item "' . ($r['name'] ?? '') . '": salesman max qty is ' . (int) $r['max_sale_qty']
            . ' per invoice (you entered ' . (int) $r['qty'] . '). Ask a manager/admin to sell more, or lower the quantity.'
        );
    }

    /**
     * Catalog-floor sum for header-discount clamp after an invoice's lines are already saved.
     */
    public static function catalogFloorForSale(Database $db, int $saleId, string $customerKind): float {
        $rows = $db->fetchAll(
            "SELECT si.quantity, i.sale_price
             FROM sale_items si
             JOIN items i ON i.id = si.item_id
             WHERE si.sale_id = ?",
            [$saleId]
        );
        $floor = 0.0;
        foreach ($rows as $row) {
            $unit = Party::unitPriceFloor((float) ($row['sale_price'] ?? 0), $customerKind);
            $floor += $unit * (int) ($row['quantity'] ?? 0);
        }
        return $floor;
    }

    /**
     * Retail-only hard floor (wholesale + 0.500 below 40 KWD, + 1.000 at 40+). No-op for wholesale.
     * Used on sale edit / add-item paths that do not go through normalizeItems.
     */
    public static function assertRetailUnitPrice(
        string $itemName,
        float $unitPrice,
        float $lineDisc,
        int $qty,
        float $catalogPrice,
        string $customerKind,
        float $eps = 0.001
    ): void {
        if (!Party::isRetailCustomer($customerKind)) {
            return;
        }
        $floor = Party::unitPriceFloor($catalogPrice, $customerKind);
        if ($unitPrice + $eps < $floor) {
            throw new Exception(self::belowFloorMessage($itemName, $floor, true));
        }
        if ($qty < 1) {
            return;
        }
        $lineGross = $unitPrice * $qty;
        $maxDisc   = max(0.0, $lineGross - ($floor * $qty));
        if ($lineDisc > $maxDisc + $eps) {
            throw new Exception(
                "Item \"{$itemName}\": discount cannot sell below retail minimum ("
                . number_format($floor, DECIMAL_PLACES) . ').'
            );
        }
    }

    private static function belowFloorMessage(string $itemName, float $floor, bool $retail): string {
        if ($retail) {
            return "Item \"{$itemName}\": retail price must be at least "
                . number_format($floor, DECIMAL_PLACES)
                . ' (wholesale + 0.500 if under 40 KWD, + 1.000 if 40 KWD or more). You may increase the price.';
        }
        return "Item \"{$itemName}\": price cannot be below catalog price ("
            . number_format($floor, DECIMAL_PLACES) . ').';
    }
}

