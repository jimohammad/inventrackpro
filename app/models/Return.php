<?php

require_once __DIR__ . '/BaseModel.php';

class SaleReturn extends BaseModel {
    protected string $table = 'returns';

    // BUG FIX: Changed JOIN to LEFT JOIN on parties in getAll() and findFull().
    // If a party is hard-deleted, INNER JOIN silently drops all their returns from listings.
    public function getAll(array $filters = []): array {
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['type']) && in_array($filters['type'], ['sale_return', 'purchase_return'], true)) {
            $where .= ' AND r.type = ?';
            $params[] = $filters['type'];
        }

        if (Auth::warehouseId()) {
            $where .= ' AND r.warehouse_id = ?';
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['party_id'])) {
            $where .= ' AND r.party_id = ?';
            $params[] = $filters['party_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= ' AND r.date >= ?';
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= ' AND r.date <= ?';
            $params[] = $filters['to_date'];
        }

        return $this->db->fetchAll(
            "SELECT r.*, p.name as party_name,
                    COALESCE(s.invoice_no, pur.invoice_no) as original_invoice,
                    u.name as created_by_name
             FROM returns r
             LEFT JOIN parties p ON p.id = r.party_id
             LEFT JOIN sales s ON s.id = r.ref_id AND r.type = 'sale_return'
             LEFT JOIN purchases pur ON pur.id = r.ref_id AND r.type = 'purchase_return'
             LEFT JOIN users u ON u.id = r.created_by
             {$where}
             ORDER BY r.created_at DESC
             LIMIT 500",
            $params
        );
    }

    /**
     * Credit notes that reversed THIS sale for THIS customer.
     *
     * After a unit is returned to the warehouse it is company stock. A later sale
     * (and that customer's return) is a new deal — sale_item_imei still holds the
     * old invoice link, but that must not list the later return on the original invoice.
     *
     * @return list<array<string,mixed>>
     */
    public function getLinkedToSale(int $saleId, ?int $warehouseId = null): array {
        if ($saleId <= 0) {
            return [];
        }

        $params = [$saleId];
        $whSql  = '';
        $wid    = $warehouseId !== null ? (int) $warehouseId : (int) (Auth::warehouseId() ?: 0);
        if ($wid > 0) {
            $whSql    = ' AND r.warehouse_id = ?';
            $params[] = $wid;
        }

        $resoldAfterThisSale = $this->sqlImeiResoldAfterSale('sale', 'r');

        return $this->db->fetchAll(
            "SELECT DISTINCT r.id, r.return_no, r.date, r.grand_total, r.status, r.created_at,
                    r.party_id, p.name AS party_name, u.name AS created_by_name
             FROM returns r
             INNER JOIN sales sale ON sale.id = ?
             LEFT JOIN parties p ON p.id = r.party_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.type = 'sale_return'
               AND r.status != 'cancelled'
               AND r.party_id = sale.party_id
               AND (
                 (
                   r.ref_id = sale.id
                   AND NOT {$resoldAfterThisSale}
                 )
                 OR (
                   (r.ref_id IS NULL OR r.ref_id = 0)
                   AND EXISTS (
                       SELECT 1
                       FROM return_items ri
                       INNER JOIN return_item_imei rii ON rii.return_item_id = ri.id
                       INNER JOIN sale_item_imei sii ON sii.imei_id = rii.imei_id
                       INNER JOIN sale_items si ON si.id = sii.sale_item_id AND si.sale_id = sale.id
                       WHERE ri.return_id = r.id
                         AND NOT {$resoldAfterThisSale}
                   )
                 )
               )
               {$whSql}
             ORDER BY r.created_at DESC
             LIMIT 50",
            $params
        );
    }

    /**
     * True when any IMEI on return $returnAlias was sold again after $saleAlias
     * and before that return (restocked, then sold to someone else).
     */
    private function sqlImeiResoldAfterSale(string $saleAlias, string $returnAlias): string {
        $saleAlias   = preg_replace('/[^a-zA-Z0-9_]/', '', $saleAlias) ?: 'sale';
        $returnAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $returnAlias) ?: 'r';

        return "EXISTS (
            SELECT 1
            FROM return_items ri_rs
            INNER JOIN return_item_imei rii_rs ON rii_rs.return_item_id = ri_rs.id
            INNER JOIN sale_item_imei sii_rs ON sii_rs.imei_id = rii_rs.imei_id
            INNER JOIN sale_items si_rs ON si_rs.id = sii_rs.sale_item_id
            INNER JOIN sales s_rs ON s_rs.id = si_rs.sale_id
            WHERE ri_rs.return_id = {$returnAlias}.id
              AND s_rs.id != {$saleAlias}.id
              AND s_rs.status != 'cancelled'
              AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 23:59:59'))
                  > COALESCE({$saleAlias}.created_at, CONCAT({$saleAlias}.date,' 00:00:00'))
              AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 00:00:00'))
                  <= COALESCE({$returnAlias}.created_at, CONCAT({$returnAlias}.date,' 23:59:59'))
        )";
    }

    /** Same as sqlImeiResoldAfterSale, source sale taken from the return header ref_id. */
    private function sqlImeiResoldAfterReturnRef(string $returnAlias = 'r'): string {
        $returnAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $returnAlias) ?: 'r';

        return "EXISTS (
            SELECT 1
            FROM return_items ri_rs
            INNER JOIN return_item_imei rii_rs ON rii_rs.return_item_id = ri_rs.id
            INNER JOIN sale_item_imei sii_rs ON sii_rs.imei_id = rii_rs.imei_id
            INNER JOIN sale_items si_rs ON si_rs.id = sii_rs.sale_item_id
            INNER JOIN sales s_rs ON s_rs.id = si_rs.sale_id
            INNER JOIN sales orig_rs ON orig_rs.id = {$returnAlias}.ref_id
            WHERE ri_rs.return_id = {$returnAlias}.id
              AND s_rs.id != orig_rs.id
              AND s_rs.status != 'cancelled'
              AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 23:59:59'))
                  > COALESCE(orig_rs.created_at, CONCAT(orig_rs.date,' 00:00:00'))
              AND COALESCE(s_rs.created_at, CONCAT(s_rs.date,' 00:00:00'))
                  <= COALESCE({$returnAlias}.created_at, CONCAT({$returnAlias}.date,' 23:59:59'))
        )";
    }

    /**
     * Purchase returns linked to a purchase invoice (header ref_id).
     *
     * @return list<array<string,mixed>>
     */
    public function getLinkedToPurchase(int $purchaseId, ?int $warehouseId = null): array {
        if ($purchaseId <= 0) {
            return [];
        }

        $params = [$purchaseId];
        $whSql  = '';
        $wid    = $warehouseId !== null ? (int) $warehouseId : (int) (Auth::warehouseId() ?: 0);
        if ($wid > 0) {
            $whSql    = ' AND r.warehouse_id = ?';
            $params[] = $wid;
        }

        return $this->db->fetchAll(
            "SELECT r.id, r.return_no, r.date, r.grand_total, r.status, r.created_at,
                    u.name AS created_by_name
             FROM returns r
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.type = 'purchase_return'
               AND r.status != 'cancelled'
               AND r.ref_id = ?
               {$whSql}
             ORDER BY r.created_at DESC
             LIMIT 50",
            $params
        );
    }

    public function findFull(int $id): array|false {
        $params = [$id];
        $where  = 'WHERE r.id = ?';
        if (Auth::warehouseId()) {
            $where   .= ' AND r.warehouse_id = ?';
            $params[] = Auth::warehouseId();
        }

        $ret = $this->db->fetchOne(
            "SELECT r.*, p.name as party_name, p.phone as party_phone,
                    COALESCE(s.invoice_no, pur.invoice_no) as original_invoice,
                    w.name as warehouse_name
             FROM returns r
             LEFT JOIN parties p ON p.id = r.party_id
             LEFT JOIN sales s ON s.id = r.ref_id AND r.type = 'sale_return'
             LEFT JOIN purchases pur ON pur.id = r.ref_id AND r.type = 'purchase_return'
             LEFT JOIN warehouses w ON w.id = r.warehouse_id
             {$where}",
            $params
        );
        if (!$ret) return false;

        $ret['items'] = $this->db->fetchAll(
            "SELECT ri.*, i.name as item_name, i.sku,
                    GROUP_CONCAT(ir.imei SEPARATOR '||') as imei_list
             FROM return_items ri
             JOIN items i ON i.id = ri.item_id
             LEFT JOIN return_item_imei rii ON rii.return_item_id = ri.id
             LEFT JOIN imei_records ir ON ir.id = rii.imei_id
             WHERE ri.return_id = ?
             GROUP BY ri.id",
            [$id]
        );

        return $ret;
    }

    // AUDIT FIX F1: FOR UPDATE prevents duplicate return numbers
    public function nextReturnNo(): string {
        $last = $this->db->fetchOne("SELECT return_no FROM returns ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num  = $last ? (int) substr($last['return_no'], strlen(RETURN_PREFIX)) : 0;
        return RETURN_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * AUDIT FIX #1: Check if IMEI was already returned for the SAME sale.
     * Prevents the same phone being returned twice on the same invoice.
     * Scoped to ref_id so a phone that was returned, re-sold, and returned again
     * is not incorrectly blocked by its earlier return history.
     * Optional warehouse_id keeps the check branch-scoped (Main vs Fahaheel).
     */
    public function isImeiAlreadyReturned(int $imeiId, ?int $saleId = null, ?int $warehouseId = null): array|false {
        $sql    = "SELECT r.return_no, r.date
                   FROM return_item_imei rii
                   JOIN return_items ri ON ri.id = rii.return_item_id
                   JOIN returns r ON r.id = ri.return_id
                   WHERE rii.imei_id = ? AND r.status = 'approved' AND r.type = 'sale_return'";
        $params = [$imeiId];

        if ($saleId) {
            $sql .= ' AND r.ref_id = ?';
            $params[] = $saleId;
        }
        if ($warehouseId !== null && $warehouseId > 0) {
            $sql .= ' AND r.warehouse_id = ?';
            $params[] = $warehouseId;
        }

        return $this->db->fetchOne($sql . ' LIMIT 1', $params);
    }

    public function isImeiAlreadyReturnedToSupplier(int $imeiId, ?int $purchaseId = null, ?int $warehouseId = null): array|false {
        $sql    = "SELECT r.return_no, r.date
                   FROM return_item_imei rii
                   JOIN return_items ri ON ri.id = rii.return_item_id
                   JOIN returns r ON r.id = ri.return_id
                   WHERE rii.imei_id = ? AND r.status = 'approved' AND r.type = 'purchase_return'";
        $params = [$imeiId];

        if ($purchaseId) {
            $sql .= ' AND r.ref_id = ?';
            $params[] = $purchaseId;
        }
        if ($warehouseId !== null && $warehouseId > 0) {
            $sql .= ' AND r.warehouse_id = ?';
            $params[] = $warehouseId;
        }

        return $this->db->fetchOne($sql . ' LIMIT 1', $params);
    }

    private function parseReturnSourceSaleIdFromNotes(string $notes): ?int {
        if (preg_match('/return_src_sale:(\d+)/', $notes, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function stripReturnSourceSaleNote(string $notes): string {
        return trim(preg_replace('/\s*\|\s*return_src_sale:\d+/', '', $notes));
    }

    /**
     * Move a sold IMEI back to in_stock for a sale return. Throws if not returnable.
     */
    private function markSaleReturnImeiToStock(int $imeiId, string $imeiLabel, int $warehouseId, ?int $refSaleId): void {
        if ($refSaleId !== null && $refSaleId > 0) {
            $aff = $this->db->execute(
                "UPDATE imei_records SET status='in_stock', sale_id=NULL, warehouse_id=?,
                 notes = TRIM(BOTH ' |' FROM CONCAT(COALESCE(notes,''), ' | return_src_sale:', ?))
                 WHERE id=? AND status='sold' AND sale_id=?",
                [$warehouseId, $refSaleId, $imeiId, $refSaleId]
            );
        } else {
            $aff = $this->db->execute(
                "UPDATE imei_records SET status='in_stock', sale_id=NULL, warehouse_id=?
                 WHERE id=? AND status='sold'",
                [$warehouseId, $imeiId]
            );
        }
        if ($aff > 0) {
            return;
        }

        $row = $this->db->fetchOne('SELECT status FROM imei_records WHERE id = ?', [$imeiId]);
        if (!$row) {
            throw new Exception("IMEI {$imeiLabel} record disappeared during return.");
        }
        if (in_array($row['status'], ['in_stock', 'returned'], true)) {
            throw new Exception("IMEI {$imeiLabel} is already in stock — cannot return again.");
        }
        throw new Exception("IMEI {$imeiLabel} is not in a returnable sold state (status: {$row['status']}).");
    }

    /**
     * Force every sale-return line to the customer's original sold unit price.
     * Never uses current catalog sale_price. Posted client prices are ignored.
     *
     * @param array{ref_id?:int|null, items:array} $data
     * @return array items with unit_price overwritten from the original sale
     */
    private function applySoldReturnPrices(array $data): array {
        $refId = (int) ($data['ref_id'] ?? 0);
        $items = $data['items'] ?? [];
        foreach ($items as $idx => $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $qty    = (int) ($item['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            $imeis = [];
            if (!empty($item['imeis']) && is_array($item['imeis'])) {
                foreach ($item['imeis'] as $imei) {
                    $imei = trim((string) $imei);
                    if ($imei !== '') {
                        $imeis[] = $imei;
                    }
                }
            }
            $items[$idx]['unit_price'] = $this->resolveSaleReturnUnitPrice(
                $itemId,
                $imeis,
                $refId > 0 ? $refId : null,
                null
            );
        }
        return $items;
    }

    /**
     * Resolve the unit price the customer originally paid.
     * Prefer exact sale line via sale_item_imei; else unique price on linked sale.
     *
     * @param list<string> $imeis
     * @param float|null   $fallback only for edit of legacy lines when sale link is gone
     */
    public function resolveSaleReturnUnitPrice(
        int $itemId,
        array $imeis = [],
        ?int $saleId = null,
        ?float $fallback = null
    ): float {
        $nameRow  = $this->db->fetchOne('SELECT name FROM items WHERE id = ?', [$itemId]);
        $itemName = (string) ($nameRow['name'] ?? ('item #' . $itemId));

        $cleanImeis = [];
        foreach ($imeis as $imei) {
            $imei = trim((string) $imei);
            if ($imei !== '') {
                $cleanImeis[] = $imei;
            }
        }

        if (!empty($cleanImeis)) {
            return $this->soldUnitPriceFromImeis($cleanImeis, $itemId, $itemName);
        }

        if ($saleId !== null && $saleId > 0) {
            return $this->soldUnitPriceFromSale($saleId, $itemId, $itemName);
        }

        if ($fallback !== null && $fallback > 0) {
            return round($fallback, 3);
        }

        throw new Exception(
            "Cannot determine sold price for \"{$itemName}\". Scan the IMEI or link the original sale invoice."
        );
    }

    /** @param list<string> $imeis */
    private function soldUnitPriceFromImeis(array $imeis, int $itemId, string $itemName): float {
        $prices = [];
        foreach ($imeis as $imei) {
            $line = $this->db->fetchOne(
                "SELECT si.unit_price
                 FROM imei_records ir
                 JOIN sale_item_imei sii ON sii.imei_id = ir.id
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 WHERE ir.imei = ? AND ir.item_id = ?
                   AND (ir.sale_id IS NULL OR si.sale_id = ir.sale_id)
                 ORDER BY sii.id DESC
                 LIMIT 1",
                [$imei, $itemId]
            );
            if (!$line) {
                // Fallback when sale_item_imei link is missing (legacy data)
                $line = $this->db->fetchOne(
                    "SELECT si.unit_price
                     FROM imei_records ir
                     JOIN sale_items si ON si.sale_id = ir.sale_id AND si.item_id = ir.item_id
                     WHERE ir.imei = ? AND ir.item_id = ? AND ir.status = 'sold' AND ir.sale_id IS NOT NULL
                     ORDER BY si.id DESC
                     LIMIT 1",
                    [$imei, $itemId]
                );
            }
            if (!$line || $line['unit_price'] === null) {
                throw new Exception("No sold price found for IMEI {$imei} (\"{$itemName}\").");
            }
            $p = round((float) $line['unit_price'], 3);
            if ($p <= 0) {
                throw new Exception("Sold price for IMEI {$imei} is zero — cannot return.");
            }
            $prices[] = $p;
        }

        $unique = array_values(array_unique($prices));
        if (count($unique) > 1) {
            throw new Exception(
                "\"{$itemName}\" was sold at different prices on the scanned IMEIs. Return them on separate lines."
            );
        }
        return $unique[0];
    }

    private function soldUnitPriceFromSale(int $saleId, int $itemId, string $itemName): float {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT ROUND(unit_price, 3) AS unit_price
             FROM sale_items
             WHERE sale_id = ? AND item_id = ?',
            [$saleId, $itemId]
        );
        if (empty($rows)) {
            throw new Exception("\"{$itemName}\" was not on the linked sale invoice.");
        }
        $prices = [];
        foreach ($rows as $row) {
            $prices[] = round((float) $row['unit_price'], 3);
        }
        $prices = array_values(array_unique($prices));
        if (count($prices) > 1) {
            throw new Exception(
                "\"{$itemName}\" has multiple sold prices on this invoice. Scan IMEIs so each unit uses its sold price."
            );
        }
        if ($prices[0] <= 0) {
            throw new Exception("Sold price for \"{$itemName}\" is zero — cannot return.");
        }
        return $prices[0];
    }

    public function create(array $data): array {
        $this->db->beginTransaction();
        try {
            $returnNo  = $this->nextReturnNo();
            $subtotal  = 0;

            // Validate return quantities against original sale if linked (bulk, single-invoice path)
            if (!empty($data['ref_id'])) {
                // M1 fix: don't trust the controller alone — refuse returns against cancelled
                // invoices in case this model is ever called from a script/API path.
                $saleStatus = $this->db->fetchOne(
                    "SELECT status FROM sales WHERE id = ?",
                    [$data['ref_id']]
                );
                if (!$saleStatus) {
                    throw new Exception('Original invoice not found.');
                }
                if (($saleStatus['status'] ?? '') === 'cancelled') {
                    throw new Exception('Cannot post a return against a cancelled invoice.');
                }

                $saleItems = $this->db->fetchAll(
                    "SELECT si.item_id, SUM(si.quantity) as sold_qty,
                            COALESCE(ret.returned_qty, 0) as already_returned
                     FROM sale_items si
                     LEFT JOIN (
                         SELECT ri.item_id, SUM(ri.quantity) as returned_qty
                         FROM return_items ri
                         JOIN returns r ON r.id = ri.return_id
                         WHERE r.ref_id = ? AND r.type = 'sale_return' AND r.status = 'approved'
                           AND NOT {$this->sqlImeiResoldAfterReturnRef('r')}
                         GROUP BY ri.item_id
                     ) ret ON ret.item_id = si.item_id
                     WHERE si.sale_id = ?
                     GROUP BY si.item_id",
                    [$data['ref_id'], $data['ref_id']]
                );
                $saleLimits = [];
                foreach ($saleItems as $si) {
                    $saleLimits[(int)$si['item_id']] = (int)$si['sold_qty'] - (int)$si['already_returned'];
                }
                foreach ($data['items'] as $item) {
                    $itemId = (int)$item['item_id'];
                    $qty    = (int)$item['quantity'];
                    $maxAllowed = $saleLimits[$itemId] ?? 0;
                    if ($qty > $maxAllowed) {
                        $itemName = $this->db->fetchOne("SELECT name FROM items WHERE id = ?", [$itemId]);
                        throw new Exception(
                            "Cannot return {$qty} of \"{$itemName['name']}\" — only {$maxAllowed} remaining from this sale."
                        );
                    }
                }
            }

            // Overwrite client prices with original sold prices before totaling.
            $data['items'] = $this->applySoldReturnPrices($data);

            foreach ($data['items'] as $item) {
                $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
            }

            $saleRefId = !empty($data['ref_id']) ? (int) $data['ref_id'] : 0;

            $returnId = $this->db->insert(
                "INSERT INTO returns (return_no, type, ref_id, party_id, warehouse_id, date, subtotal, grand_total, reason, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $returnNo, 'sale_return',
                    $saleRefId,
                    $data['party_id'],
                    $data['warehouse_id'],
                    $data['date'] ?? date('Y-m-d'),
                    $subtotal, $subtotal,
                    $data['reason'] ?? null,
                    'approved',
                    Auth::id(),
                ]
            );

            foreach ($data['items'] as $item) {
                $lineTotal = (float)$item['unit_price'] * (int)$item['quantity'];
                $retItemId = $this->db->insert(
                    "INSERT INTO return_items (return_id, item_id, quantity, unit_price, total)
                     VALUES (?,?,?,?,?)",
                    [$returnId, $item['item_id'], $item['quantity'], $item['unit_price'], $lineTotal]
                );

                // Restore stock under row lock (same discipline as purchase return / void).
                $this->lockAndIncrementStock((int) $item['item_id'], (int) $data['warehouse_id'], (int) $item['quantity']);

                // Handle IMEIs — explicit scan path
                if (!empty($item['imeis'])) {
                    foreach ($item['imeis'] as $imei) {
                        $imei = trim($imei);
                        if (!$imei) continue;
                        $headerRefId = (int)($data['ref_id'] ?? 0);
                        if ($headerRefId > 0) {
                            $imeiRow = $this->db->fetchOne(
                                "SELECT id, status, sale_id
                                 FROM imei_records
                                 WHERE imei = ?
                                 ORDER BY
                                    CASE
                                        WHEN sale_id = ? AND status = 'sold' THEN 0
                                        WHEN status = 'sold' THEN 1
                                        ELSE 2
                                    END,
                                    id DESC
                                 LIMIT 1",
                                [$imei, $headerRefId]
                            );
                        } else {
                            $imeiRow = $this->db->fetchOne(
                                "SELECT id, status, sale_id
                                 FROM imei_records
                                 WHERE imei = ?
                                 ORDER BY
                                    CASE
                                        WHEN status = 'sold' THEN 0
                                        ELSE 1
                                    END,
                                    id DESC
                                 LIMIT 1",
                                [$imei]
                            );
                        }
                        if ($imeiRow) {
                            $imeiSaleId = (int) ($imeiRow['sale_id'] ?? 0);
                            $checkSaleId = $headerRefId > 0 ? $headerRefId : ($imeiSaleId > 0 ? $imeiSaleId : null);
                            $markSaleId  = $headerRefId > 0 ? $headerRefId : ($imeiSaleId > 0 ? $imeiSaleId : null);

                            $alreadyReturned = $this->isImeiAlreadyReturned(
                                (int) $imeiRow['id'],
                                $checkSaleId,
                                (int) $data['warehouse_id']
                            );
                            if ($alreadyReturned) {
                                throw new Exception(
                                    "IMEI {$imei} was already returned in " .
                                    $alreadyReturned['return_no'] .
                                    " on " . $alreadyReturned['date'] .
                                    ". Cannot return the same device twice."
                                );
                            }

                            $this->markSaleReturnImeiToStock(
                                (int) $imeiRow['id'],
                                $imei,
                                (int) $data['warehouse_id'],
                                $markSaleId
                            );
                            $this->db->insert(
                                "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                                [$retItemId, (int) $imeiRow['id']]
                            );
                        } else {
                            throw new Exception(
                                "IMEI {$imei} is not a sold unit in the system. Scan a serial that was sold from this branch."
                            );
                        }
                    }
                } elseif (!empty($data['ref_id'])) {
                    // No IMEIs scanned — only auto-restore non-IMEI (or optional) lines.
                    $meta = $this->db->fetchOne(
                        'SELECT has_imei, COALESCE(imei_optional,0) AS imei_optional, name FROM items WHERE id = ?',
                        [$item['item_id']]
                    );
                    if ($meta && !empty($meta['has_imei']) && empty($meta['imei_optional'])) {
                        throw new Exception(
                            "Item \"{$meta['name']}\" requires IMEI scan on return. Do not use bulk restore for serial-tracked goods."
                        );
                    }
                    $this->db->execute(
                        "UPDATE imei_records
                         SET status='in_stock', sale_id=NULL, warehouse_id=?
                         WHERE sale_id=? AND item_id=? AND status='sold'
                         ORDER BY id
                         LIMIT ?",
                        [$data['warehouse_id'], $data['ref_id'], $item['item_id'], (int)$item['quantity']]
                    );
                }
            }

            // Sale returns credit the party ledger (Party balance uses returns.grand_total).
            // Do NOT reduce sales.balance — invoice AR stays grand_total - paid; return is a
            // separate credit note. Optional ref_id is for IMEI / qty validation / audit only.

            $this->db->commit();
            return ['success' => true, 'id' => (int)$returnId, 'return_no' => $returnNo];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Return goods to supplier — reduces stock, removes IMEIs from sellable inventory,
     * updates purchase balance via Purchase::recomputeBalanceAfterReturns.
     */
    public function createPurchaseReturn(array $data): array {
        $this->db->beginTransaction();
        try {
            $refId = (int) ($data['ref_id'] ?? 0);
            if ($refId <= 0) {
                throw new Exception('Select the original purchase invoice.');
            }

            $purchase = $this->db->fetchOne(
                "SELECT id, party_id, status, warehouse_id FROM purchases WHERE id = ?",
                [$refId]
            );
            if (!$purchase) {
                throw new Exception('Original purchase not found.');
            }
            if (($purchase['status'] ?? '') === 'cancelled') {
                throw new Exception('Cannot post a return against a cancelled purchase.');
            }
            if ((int) $purchase['warehouse_id'] !== (int) $data['warehouse_id']) {
                throw new Exception('Return warehouse must match the purchase warehouse.');
            }

            $partyId = (int) $purchase['party_id'];
            if ((int) ($data['party_id'] ?? 0) !== $partyId) {
                throw new Exception('Supplier must match the purchase invoice.');
            }

            $purchaseItems = $this->db->fetchAll(
                "SELECT pi.item_id, SUM(pi.quantity) as purchased_qty,
                        COALESCE(ret.returned_qty, 0) as already_returned
                 FROM purchase_items pi
                 LEFT JOIN (
                     SELECT ri.item_id, SUM(ri.quantity) as returned_qty
                     FROM return_items ri
                     JOIN returns r ON r.id = ri.return_id
                     WHERE r.ref_id = ? AND r.type = 'purchase_return' AND r.status = 'approved'
                     GROUP BY ri.item_id
                 ) ret ON ret.item_id = pi.item_id
                 WHERE pi.purchase_id = ?
                 GROUP BY pi.item_id",
                [$refId, $refId]
            );
            $purchaseLimits = [];
            foreach ($purchaseItems as $pi) {
                $purchaseLimits[(int) $pi['item_id']] = (int) $pi['purchased_qty'] - (int) $pi['already_returned'];
            }

            $subtotal = 0.0;
            foreach ($data['items'] as $item) {
                $itemId     = (int) $item['item_id'];
                $qty        = (int) $item['quantity'];
                $maxAllowed = $purchaseLimits[$itemId] ?? 0;
                if ($qty > $maxAllowed) {
                    $itemName = $this->db->fetchOne('SELECT name FROM items WHERE id = ?', [$itemId]);
                    throw new Exception(
                        'Cannot return ' . $qty . ' of "' . ($itemName['name'] ?? 'item') .
                        '" — only ' . $maxAllowed . ' remaining from this purchase.'
                    );
                }
                $subtotal += (float) $item['unit_price'] * $qty;
            }

            $returnNo = $this->nextReturnNo();
            $returnId = $this->db->insert(
                "INSERT INTO returns (return_no, type, ref_id, party_id, warehouse_id, date, subtotal, grand_total, reason, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $returnNo,
                    'purchase_return',
                    $refId,
                    $partyId,
                    (int) $data['warehouse_id'],
                    $data['date'] ?? date('Y-m-d'),
                    $subtotal,
                    $subtotal,
                    $data['reason'] ?? null,
                    'approved',
                    Auth::id(),
                ]
            );

            $warehouseId = (int) $data['warehouse_id'];

            foreach ($data['items'] as $item) {
                $itemId    = (int) $item['item_id'];
                $qty       = (int) $item['quantity'];
                $lineTotal = (float) $item['unit_price'] * $qty;

                $stockRow = $this->db->fetchOne(
                    'SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
                    [$itemId, $warehouseId]
                );
                $onHand = (int) ($stockRow['quantity'] ?? 0);
                if ($onHand < $qty) {
                    $itemName = $this->db->fetchOne('SELECT name FROM items WHERE id = ?', [$itemId]);
                    throw new Exception(
                        'Insufficient stock for "' . ($itemName['name'] ?? 'item') .
                        "\" — only {$onHand} available in this warehouse."
                    );
                }
                $this->db->execute(
                    'UPDATE stock SET quantity = quantity - ? WHERE id = ?',
                    [$qty, (int) $stockRow['id']]
                );

                $retItemId = $this->db->insert(
                    'INSERT INTO return_items (return_id, item_id, quantity, unit_price, total) VALUES (?,?,?,?,?)',
                    [$returnId, $itemId, $qty, $item['unit_price'], $lineTotal]
                );

                if (!empty($item['imeis'])) {
                    foreach ($item['imeis'] as $imei) {
                        $imei = trim((string) $imei);
                        if ($imei === '') {
                            continue;
                        }
                        $imeiRow = $this->db->fetchOne(
                            "SELECT id, status, sale_id
                             FROM imei_records
                             WHERE imei = ? AND item_id = ? AND warehouse_id = ?
                               AND purchase_id = ? AND status IN ('in_stock','returned')
                             LIMIT 1",
                            [$imei, $itemId, $warehouseId, $refId]
                        );
                        if (!$imeiRow) {
                            throw new Exception("IMEI {$imei} is not in stock from this purchase.");
                        }
                        if (!empty($imeiRow['sale_id'])) {
                            throw new Exception("IMEI {$imei} has already been sold.");
                        }
                        $dup = $this->isImeiAlreadyReturnedToSupplier((int) $imeiRow['id'], $refId, $warehouseId);
                        if ($dup) {
                            throw new Exception(
                                "IMEI {$imei} was already returned in {$dup['return_no']} on {$dup['date']}."
                            );
                        }
                        $aff = $this->db->execute(
                            "UPDATE imei_records
                             SET status = 'transferred', purchase_id = NULL, sale_id = NULL, warehouse_id = NULL,
                                 notes = CONCAT(COALESCE(notes,''), ' | Returned to supplier ', ?)
                             WHERE id = ? AND purchase_id = ? AND status IN ('in_stock','returned')",
                            [$returnNo, (int) $imeiRow['id'], $refId]
                        );
                        if ($aff === 0) {
                            throw new Exception("IMEI {$imei} could not be marked as returned to supplier.");
                        }
                        $this->db->insert(
                            'INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)',
                            [$retItemId, (int) $imeiRow['id']]
                        );
                    }
                } else {
                    $imeiRows = $this->db->fetchAll(
                        "SELECT id FROM imei_records
                         WHERE purchase_id = ? AND item_id = ? AND warehouse_id = ?
                           AND status IN ('in_stock','returned') AND (sale_id IS NULL OR sale_id = 0)
                         ORDER BY id ASC
                         LIMIT ?",
                        [$refId, $itemId, $warehouseId, $qty]
                    );
                    $linked = 0;
                    foreach ($imeiRows as $imeiRow) {
                        $imeiId = (int) $imeiRow['id'];
                        $dup    = $this->isImeiAlreadyReturnedToSupplier($imeiId, $refId, $warehouseId);
                        if ($dup) {
                            continue;
                        }
                        $aff = $this->db->execute(
                            "UPDATE imei_records
                             SET status = 'transferred', purchase_id = NULL, sale_id = NULL, warehouse_id = NULL,
                                 notes = CONCAT(COALESCE(notes,''), ' | Returned to supplier ', ?)
                             WHERE id = ? AND purchase_id = ? AND status IN ('in_stock','returned')",
                            [$returnNo, $imeiId, $refId]
                        );
                        if ($aff === 0) {
                            continue;
                        }
                        $this->db->insert(
                            'INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)',
                            [$retItemId, $imeiId]
                        );
                        $linked++;
                    }
                    if ($linked < $qty) {
                        $hasImei = $this->db->fetchOne(
                            'SELECT has_imei FROM items WHERE id = ?',
                            [$itemId]
                        );
                        if ($hasImei && (int) ($hasImei['has_imei'] ?? 0) === 1) {
                            throw new Exception(
                                "Scan {$qty} IMEI(s) for this item — only {$linked} could be matched in stock from this purchase."
                            );
                        }
                    }
                }
            }

            require_once __DIR__ . '/Purchase.php';
            (new Purchase())->recomputeBalanceAfterReturns($refId);

            $this->db->commit();
            return ['success' => true, 'id' => (int) $returnId, 'return_no' => $returnNo];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Void an approved return — reverse stock/IMEI.
     * Sale returns: party ledger credit is removed by status=cancelled (invoice balance untouched).
     * Purchase returns: purchase invoice balance is recomputed.
     *
     * @return array{success:bool, error?:string}
     */
    public function cancel(int $id): array {
        $ret = $this->findFull($id);
        if (!$ret) {
            return ['success' => false, 'error' => 'Return not found.'];
        }
        if (($ret['status'] ?? '') === 'cancelled') {
            return ['success' => false, 'error' => 'Return is already voided.'];
        }
        if (($ret['status'] ?? '') !== 'approved') {
            return ['success' => false, 'error' => 'Only approved returns can be voided.'];
        }

        $type       = (string) ($ret['type'] ?? '');
        $warehouseId = (int) ($ret['warehouse_id'] ?? 0);
        $refId      = (int) ($ret['ref_id'] ?? 0);
        $returnNo   = (string) ($ret['return_no'] ?? '');

        $this->db->beginTransaction();
        try {
            foreach ($ret['items'] as $item) {
                $retItemId = (int) ($item['id'] ?? 0);
                $itemId    = (int) ($item['item_id'] ?? 0);
                $qty       = (int) ($item['quantity'] ?? 0);
                if ($retItemId <= 0 || $itemId <= 0 || $qty <= 0) {
                    continue;
                }

                if ($type === 'purchase_return') {
                    $this->reversePurchaseReturnItem($retItemId, $itemId, $qty, $warehouseId, $refId, $returnNo);
                } else {
                    $this->reverseSaleReturnItem($retItemId, $itemId, $qty, $warehouseId, $refId);
                }
            }

            $aff = $this->db->execute(
                "UPDATE `returns` SET status = 'cancelled' WHERE id = ? AND status = 'approved'",
                [$id]
            );
            if ($aff === 0) {
                throw new Exception('Return could not be marked as voided.');
            }

            if ($type === 'purchase_return' && $refId > 0) {
                require_once __DIR__ . '/Purchase.php';
                (new Purchase())->recomputeBalanceAfterReturns($refId);
            }
            // Sale returns are party ledger credits only — voiding removes them from the
            // party balance via status=cancelled; do not touch sales.balance.

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function reverseSaleReturnItem(
        int $retItemId,
        int $itemId,
        int $qty,
        int $warehouseId,
        int $refSaleId
    ): void {
        $this->db->fetchOne(
            'SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
            [$itemId, $warehouseId]
        );

        $imeiRows = $this->db->fetchAll(
            "SELECT rii.imei_id, ir.imei, ir.notes
             FROM return_item_imei rii
             JOIN imei_records ir ON ir.id = rii.imei_id
             WHERE rii.return_item_id = ?",
            [$retItemId]
        );
        $this->db->execute('DELETE FROM return_item_imei WHERE return_item_id = ?', [$retItemId]);

        foreach ($imeiRows as $imeiRow) {
            $imeiId = (int) $imeiRow['imei_id'];
            $notes  = (string) ($imeiRow['notes'] ?? '');
            if (str_contains($notes, 'Auto-created via sale return')) {
                $delAff = $this->db->execute(
                    "DELETE FROM imei_records WHERE id = ? AND status IN ('in_stock','returned')",
                    [$imeiId]
                );
                if ($delAff === 0) {
                    throw new Exception(
                        'Cannot void return — IMEI ' . ($imeiRow['imei'] ?? '') . ' created on this return is no longer available.'
                    );
                }
            } else {
                $saleIdForImei = $refSaleId > 0
                    ? $refSaleId
                    : $this->parseReturnSourceSaleIdFromNotes($notes);
                if ($saleIdForImei === null || $saleIdForImei <= 0) {
                    throw new Exception(
                        'Cannot void return — IMEI ' . ($imeiRow['imei'] ?? '') .
                        ' has no source sale to restore (multi-invoice return missing return_src_sale note).'
                    );
                }
                $restoredNotes = $this->stripReturnSourceSaleNote($notes);
                $aff = $this->db->execute(
                    "UPDATE imei_records
                     SET status = 'sold', sale_id = ?, warehouse_id = ?, notes = ?
                     WHERE id = ? AND status IN ('in_stock','returned')",
                    [$saleIdForImei, $warehouseId, $restoredNotes !== '' ? $restoredNotes : null, $imeiId]
                );
                if ($aff === 0) {
                    throw new Exception(
                        'Cannot void return — IMEI ' . ($imeiRow['imei'] ?? '') . ' is no longer in stock (may have been sold again).'
                    );
                }
            }
        }

        $linked    = count($imeiRows);
        $remainder = $qty - $linked;
        if ($remainder > 0) {
            $itemMeta = $this->db->fetchOne('SELECT has_imei FROM items WHERE id = ?', [$itemId]);
            // Serial-tracked lines must reverse only the linked IMEIs — never re-bind arbitrary stock.
            if ((int) ($itemMeta['has_imei'] ?? 0) === 1) {
                throw new Exception(
                    'Cannot void return — IMEI links incomplete for this line ' .
                    "({$linked} linked, {$qty} qty). Re-scan or fix return_item_imei before voiding."
                );
            }
            // Non-IMEI accessories: stock qty reverse below is enough.
        }

        $affected = $this->db->execute(
            'UPDATE stock SET quantity = quantity - ? WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?',
            [$qty, $itemId, $warehouseId, $qty]
        );
        if ($affected === 0) {
            throw new Exception(
                'Cannot void return — insufficient stock to reverse (returned goods may have been sold again).'
            );
        }
    }

    private function reversePurchaseReturnItem(
        int $retItemId,
        int $itemId,
        int $qty,
        int $warehouseId,
        int $refPurchaseId,
        string $returnNo
    ): void {
        $this->db->fetchOne(
            'SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
            [$itemId, $warehouseId]
        );

        $imeiRows = $this->db->fetchAll(
            "SELECT rii.imei_id, ir.imei
             FROM return_item_imei rii
             JOIN imei_records ir ON ir.id = rii.imei_id
             WHERE rii.return_item_id = ?",
            [$retItemId]
        );

        $this->db->execute('DELETE FROM return_item_imei WHERE return_item_id = ?', [$retItemId]);

        foreach ($imeiRows as $imeiRow) {
            $aff = $this->db->execute(
                "UPDATE imei_records
                 SET status = 'in_stock', purchase_id = ?, warehouse_id = ?, sale_id = NULL
                 WHERE id = ? AND status = 'transferred'",
                [$refPurchaseId, $warehouseId, (int) $imeiRow['imei_id']]
            );
            if ($aff === 0) {
                throw new Exception(
                    'Cannot void return — IMEI ' . ($imeiRow['imei'] ?? '') . ' is not in transferred state.'
                );
            }
        }

        $linked    = count($imeiRows);
        $remainder = $qty - $linked;
        $itemMeta  = $this->db->fetchOne('SELECT has_imei FROM items WHERE id = ?', [$itemId]);
        $hasImei   = (int) ($itemMeta['has_imei'] ?? 0) === 1;

        if ($remainder > 0 && $hasImei) {
            // Prefer linked rows; notes fallback only when return_item_imei was incomplete historically.
            if ($returnNo === '') {
                throw new Exception(
                    'Cannot void return — IMEI links incomplete and return number is missing.'
                );
            }
            $noteLike = '%Returned to supplier ' . $returnNo . '%';
            $candidates = $this->db->fetchAll(
                "SELECT id FROM imei_records
                 WHERE item_id = ? AND status = 'transferred' AND notes LIKE ?
                 ORDER BY id ASC
                 LIMIT ?",
                [$itemId, $noteLike, $remainder]
            );
            foreach ($candidates as $candidate) {
                $aff = $this->db->execute(
                    "UPDATE imei_records
                     SET status = 'in_stock', purchase_id = ?, warehouse_id = ?, sale_id = NULL
                     WHERE id = ? AND status = 'transferred'",
                    [$refPurchaseId, $warehouseId, (int) $candidate['id']]
                );
                if ($aff === 0) {
                    throw new Exception('Cannot void return — an IMEI could not be restored to stock.');
                }
            }
            $remainder -= count($candidates);
            if ($remainder > 0) {
                throw new Exception(
                    'Cannot void return — not enough transferred IMEIs matched this purchase return ' .
                    "(need {$remainder} more). Prefer void only when return_item_imei links are complete."
                );
            }
        }

        $this->db->execute(
            'INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?',
            [$itemId, $warehouseId, $qty, $qty]
        );
    }

    /**
     * Lock stock row then increase qty (sale return create). Creates the row if missing.
     */
    private function lockAndIncrementStock(int $itemId, int $warehouseId, int $qty): void {
        if ($itemId <= 0 || $warehouseId <= 0 || $qty <= 0) {
            return;
        }
        $row = $this->db->fetchOne(
            'SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
            [$itemId, $warehouseId]
        );
        if (!$row) {
            try {
                $this->db->execute(
                    'INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, 0)',
                    [$itemId, $warehouseId]
                );
            } catch (Exception $e) {
                // Concurrent insert — row now exists.
            }
            $row = $this->db->fetchOne(
                'SELECT id FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE',
                [$itemId, $warehouseId]
            );
            if (!$row) {
                throw new Exception('Could not lock stock row for return restore.');
            }
        }
        $this->db->execute(
            'UPDATE stock SET quantity = quantity + ? WHERE id = ?',
            [$qty, (int) $row['id']]
        );
    }
}
