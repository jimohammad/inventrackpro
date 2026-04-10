<?php

require_once __DIR__ . '/BaseModel.php';

class SaleReturn extends BaseModel {
    protected string $table = 'returns';

    // BUG FIX: Changed JOIN to LEFT JOIN on parties in getAll() and findFull().
    // If a party is hard-deleted, INNER JOIN silently drops all their returns from listings.
    public function getAll(array $filters = []): array {
        $where  = "WHERE r.type = 'sale_return'";
        $params = [];

        if (Auth::warehouseId()) {
            $where .= " AND r.warehouse_id = ?";
            $params[] = Auth::warehouseId();
        }

        if (!empty($filters['party_id'])) {
            $where .= " AND r.party_id = ?"; $params[] = $filters['party_id'];
        }
        if (!empty($filters['status'])) {
            $where .= " AND r.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where .= " AND r.date >= ?"; $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where .= " AND r.date <= ?"; $params[] = $filters['to_date'];
        }

        return $this->db->fetchAll(
            "SELECT r.*, p.name as party_name, s.invoice_no as original_invoice,
                    u.name as created_by_name
             FROM returns r
             LEFT JOIN parties p ON p.id = r.party_id
             LEFT JOIN sales s ON s.id = r.ref_id
             LEFT JOIN users u ON u.id = r.created_by
             {$where}
             ORDER BY r.created_at DESC
             LIMIT 500",
            $params
        );
    }

    public function findFull(int $id): array|false {
        $ret = $this->db->fetchOne(
            "SELECT r.*, p.name as party_name, p.phone as party_phone,
                    s.invoice_no as original_invoice, w.name as warehouse_name
             FROM returns r
             LEFT JOIN parties p ON p.id = r.party_id
             LEFT JOIN sales s ON s.id = r.ref_id
             LEFT JOIN warehouses w ON w.id = r.warehouse_id
             WHERE r.id = ?",
            [$id]
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
     * AUDIT FIX #1: Check if IMEI was already returned in any non-rejected return.
     * Prevents the same phone being "returned" multiple times, which inflates
     * stock counts and credits customers multiple times for one device.
     */
    public function isImeiAlreadyReturned(int $imeiId): array|false {
        return $this->db->fetchOne(
            "SELECT r.return_no, r.date, r.id as return_id
             FROM return_item_imei rii
             JOIN return_items ri ON ri.id = rii.return_item_id
             JOIN returns r ON r.id = ri.return_id
             WHERE rii.imei_id = ? AND r.status != 'rejected'
             LIMIT 1",
            [$imeiId]
        );
    }

    public function create(array $data): array {
        $this->db->beginTransaction();
        try {
            $returnNo  = $this->nextReturnNo();
            $subtotal  = 0;

            // Validate return quantities against original sale if linked
            if (!empty($data['ref_id'])) {
                $saleItems = $this->db->fetchAll(
                    "SELECT si.item_id, SUM(si.quantity) as sold_qty,
                            COALESCE(ret.returned_qty, 0) as already_returned
                     FROM sale_items si
                     LEFT JOIN (
                         SELECT ri.item_id, SUM(ri.quantity) as returned_qty
                         FROM return_items ri
                         JOIN returns r ON r.id = ri.return_id
                         WHERE r.ref_id = ? AND r.status = 'approved'
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

            $returnId = $this->db->insert(
                "INSERT INTO returns (return_no, type, ref_id, party_id, warehouse_id, date, subtotal, grand_total, reason, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $returnNo, 'sale_return',
                    $data['ref_id'] ?? null,
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

                // Handle IMEIs
                if (!empty($item['imeis'])) {
                    foreach ($item['imeis'] as $imei) {
                        $imei = trim($imei);
                        if (!$imei) continue;
                        $imeiRow = $this->db->fetchOne(
                            "SELECT id FROM imei_records WHERE imei = ?", [$imei]
                        );
                        if ($imeiRow) {
                            // ========================================================
                            // AUDIT FIX #1: Block IMEI already returned
                            // This was the #1 critical bug. Same IMEI was being returned
                            // 2-3 times. Each return added stock + credited the customer.
                            // Now we check return_item_imei before allowing it through.
                            // ========================================================
                            $alreadyReturned = $this->isImeiAlreadyReturned($imeiRow['id']);
                            if ($alreadyReturned) {
                                throw new Exception(
                                    "IMEI {$imei} was already returned in " .
                                    $alreadyReturned['return_no'] .
                                    " on " . $alreadyReturned['date'] .
                                    ". Cannot return the same device twice."
                                );
                            }

                            $this->db->execute(
                                "UPDATE imei_records SET status='returned', sale_id=NULL WHERE id=?",
                                [$imeiRow['id']]
                            );
                            $this->db->insert(
                                "INSERT INTO return_item_imei (return_item_id, imei_id) VALUES (?,?)",
                                [$retItemId, $imeiRow['id']]
                            );
                        }
                    }
                }
            }

            // PREVIOUS FIX: Only reduce balance on linked sale, never inflate paid_amount
            if (!empty($data['ref_id'])) {
                $this->db->execute(
                    "UPDATE sales SET
                        balance = GREATEST(0, balance - ?),
                        status  = CASE
                            WHEN GREATEST(0, balance - ?) < 0.001 THEN 'paid'
                            WHEN paid_amount > 0 THEN 'partial'
                            ELSE status
                        END
                     WHERE id = ?",
                    [$subtotal, $subtotal, $data['ref_id']]
                );
            }

            $this->db->commit();
            return ['success' => true, 'id' => (int)$returnId, 'return_no' => $returnNo];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
