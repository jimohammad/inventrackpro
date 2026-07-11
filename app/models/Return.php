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
     */
    public function isImeiAlreadyReturned(int $imeiId, ?int $saleId = null): array|false {
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

        return $this->db->fetchOne($sql . ' LIMIT 1', $params);
    }

    public function isImeiAlreadyReturnedToSupplier(int $imeiId, ?int $purchaseId = null): array|false {
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
     * Cap return unit_price to sold / catalog price so clients cannot inflate party credits.
     * @param array{ref_id?:int, items:array} $data
     */
    private function assertSaleReturnPrices(array $data): void {
        $refId = (int) ($data['ref_id'] ?? 0);
        foreach ($data['items'] as $item) {
            $itemId  = (int) ($item['item_id'] ?? 0);
            $posted  = (float) ($item['unit_price'] ?? 0);
            $qty     = (int) ($item['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            if ($posted <= 0) {
                throw new Exception('Return unit price must be greater than zero.');
            }

            $maxPrice = null;
            $nameRow  = $this->db->fetchOne('SELECT name, sale_price FROM items WHERE id = ?', [$itemId]);
            $itemName = (string) ($nameRow['name'] ?? ('item #' . $itemId));

            if ($refId > 0) {
                $row = $this->db->fetchOne(
                    'SELECT MAX(unit_price) AS mx FROM sale_items WHERE sale_id = ? AND item_id = ?',
                    [$refId, $itemId]
                );
                if ($row && $row['mx'] !== null) {
                    $maxPrice = (float) $row['mx'];
                }
            }

            if ($maxPrice === null && !empty($item['imeis'])) {
                foreach ($item['imeis'] as $imei) {
                    $imei = trim((string) $imei);
                    if ($imei === '') {
                        continue;
                    }
                    $line = $this->db->fetchOne(
                        "SELECT si.unit_price
                         FROM imei_records ir
                         JOIN sale_items si ON si.sale_id = ir.sale_id AND si.item_id = ir.item_id
                         WHERE ir.imei = ? AND ir.status = 'sold' AND ir.item_id = ?
                         ORDER BY ir.id DESC
                         LIMIT 1",
                        [$imei, $itemId]
                    );
                    if ($line) {
                        $p = (float) $line['unit_price'];
                        $maxPrice = $maxPrice === null ? $p : max($maxPrice, $p);
                    }
                }
            }

            if ($maxPrice === null) {
                $catalog = (float) ($nameRow['sale_price'] ?? 0);
                if ($catalog > 0) {
                    $maxPrice = $catalog;
                }
            }

            if ($maxPrice !== null && $posted > $maxPrice + 0.001) {
                throw new Exception(
                    "Return price for \"{$itemName}\" (" . number_format($posted, 3) .
                    ") exceeds sold/catalog price (" . number_format($maxPrice, 3) . ")."
                );
            }
        }
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

            foreach ($data['items'] as $item) {
                $subtotal += (float)$item['unit_price'] * (int)$item['quantity'];
            }

            $this->assertSaleReturnPrices($data);

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

                // Restore stock (INSERT if row doesn't exist)
                $this->db->execute(
                    "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                    [$item['item_id'], $data['warehouse_id'], $item['quantity'], $item['quantity']]
                );

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

                            $alreadyReturned = $this->isImeiAlreadyReturned((int) $imeiRow['id'], $checkSaleId);
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
                                "IMEI {$imei} is not a sold unit in the system. Scan a serial that was sold to this customer."
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
                        $dup = $this->isImeiAlreadyReturnedToSupplier((int) $imeiRow['id'], $refId);
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
                        $dup    = $this->isImeiAlreadyReturnedToSupplier($imeiId, $refId);
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

        $linked   = count($imeiRows);
        $remainder = $qty - $linked;
        if ($remainder > 0) {
            $itemMeta = $this->db->fetchOne('SELECT has_imei FROM items WHERE id = ?', [$itemId]);
            if ((int) ($itemMeta['has_imei'] ?? 0) === 1 && $refSaleId > 0) {
                $candidates = $this->db->fetchAll(
                    "SELECT id FROM imei_records
                     WHERE item_id = ? AND warehouse_id = ? AND status = 'in_stock'
                       AND (sale_id IS NULL OR sale_id = 0)
                     ORDER BY id DESC
                     LIMIT ?",
                    [$itemId, $warehouseId, $remainder]
                );
                if (count($candidates) < $remainder) {
                    throw new Exception(
                        'Cannot void return — not enough matching in-stock IMEIs to reverse (items may have been sold again).'
                    );
                }
                foreach ($candidates as $candidate) {
                    $aff = $this->db->execute(
                        "UPDATE imei_records
                         SET status = 'sold', sale_id = ?, warehouse_id = ?
                         WHERE id = ? AND status = 'in_stock'",
                        [$refSaleId, $warehouseId, (int) $candidate['id']]
                    );
                    if ($aff === 0) {
                        throw new Exception('Cannot void return — an IMEI could not be restored to sold state.');
                    }
                }
            }
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
        if ($remainder > 0 && $returnNo !== '') {
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
                $itemMeta = $this->db->fetchOne('SELECT has_imei FROM items WHERE id = ?', [$itemId]);
                if ((int) ($itemMeta['has_imei'] ?? 0) === 1) {
                    throw new Exception(
                        'Cannot void return — not enough transferred IMEIs matched this purchase return.'
                    );
                }
            }
        }

        $this->db->execute(
            'INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?',
            [$itemId, $warehouseId, $qty, $qty]
        );
    }
}
