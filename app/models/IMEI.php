<?php

require_once __DIR__ . '/BaseModel.php';

class IMEI extends BaseModel {
    protected string $table = 'imei_records';

    // Full history for one IMEI
    public function findByIMEI(string $imei): array|false {
        return $this->db->fetchOne(
            "SELECT ir.*, i.name as item_name, i.sku,
                    w.name as warehouse_name,
                    s.invoice_no as sale_invoice,
                    p.invoice_no as purchase_invoice
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN warehouses w ON w.id = ir.warehouse_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN purchases p ON p.id = ir.purchase_id
             WHERE ir.imei = ? OR ir.imei2 = ?",
            [$imei, $imei]
        );
    }

    // Get all IMEIs with filters
    public function getAll(array $filters = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND ir.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['item_id'])) {
            $where .= " AND ir.item_id = ?"; $params[] = $filters['item_id'];
        }
        if (!empty($filters['warehouse_id'])) {
            $where .= " AND ir.warehouse_id = ?"; $params[] = $filters['warehouse_id'];
        }
        if (!empty($filters['search'])) {
            $like    = '%' . $filters['search'] . '%';
            $where  .= " AND (ir.imei LIKE ? OR i.name LIKE ?)";
            $params  = array_merge($params, [$like, $like]);
        }

        return $this->db->fetchAll(
            "SELECT ir.*, i.name as item_name, i.sku,
                    w.name as warehouse_name,
                    s.invoice_no as sale_invoice,
                    pu.invoice_no as purchase_invoice
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN warehouses w ON w.id = ir.warehouse_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN purchases pu ON pu.id = ir.purchase_id
             {$where}
             ORDER BY ir.created_at DESC
             LIMIT 500",
            $params
        );
    }

    // Check if IMEI exists and is available (in_stock)
    public function isAvailable(string $imei): bool {
        $row = $this->db->fetchOne(
            "SELECT status FROM imei_records WHERE imei = ?", [$imei]
        );
        // If not in DB yet, it's new and available
        if (!$row) return true;
        return $row['status'] === 'in_stock';
    }

    // Validate multiple IMEIs for a sale item — single batch query
    public function validateList(array $imeis, int $itemId, ?int $warehouseId = null): array {
        $errors = [];
        $seen   = [];
        $clean  = [];

        foreach ($imeis as $imei) {
            $imei = trim($imei);
            if (!$imei) continue;
            if (in_array($imei, $seen)) {
                $errors[] = "IMEI {$imei} is duplicated in this invoice.";
                continue;
            }
            $seen[]  = $imei;
            $clean[] = $imei;
        }

        if (empty($clean)) return $errors;

        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id, imei, status, item_id, warehouse_id, sale_id
             FROM imei_records
             WHERE imei IN ({$placeholders})
             ORDER BY id DESC",
            $clean
        );

        $rowsByImei = [];
        $soldIds    = [];
        foreach ($rows as $row) {
            $key = (string)($row['imei'] ?? '');
            if ($key === '') {
                continue;
            }
            $rowsByImei[$key][] = $row;
            if (($row['status'] ?? '') === 'sold') {
                $soldIds[] = (int)$row['id'];
            }
        }

        // A sold IMEI should be blocked only when it is still linked to an active (non-cancelled) sale line.
        // If an approved sale return happened after the latest sale movement, the sold status is stale.
        $activeSoldMap       = [];
        $activeSaleItemByImei = [];
        $returnQtyBySaleItem = [];
        $soldCountBySaleItem = [];
        if (!empty($soldIds)) {
            $soldIds       = array_values(array_unique(array_filter($soldIds)));
            $soldPh        = implode(',', array_fill(0, count($soldIds), '?'));
            $activeSoldIds = $this->db->fetchAll(
                "SELECT DISTINCT sii.imei_id
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN sales s ON s.id = si.sale_id
                 WHERE sii.imei_id IN ({$soldPh}) AND s.status != 'cancelled'",
                $soldIds
            );
            foreach ($activeSoldIds as $as) {
                $activeSoldMap[(int)$as['imei_id']] = true;
            }

            $activeSaleRows = $this->db->fetchAll(
                "SELECT sii.imei_id, si.sale_id, si.item_id
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN sales s ON s.id = si.sale_id
                 WHERE sii.imei_id IN ({$soldPh}) AND s.status != 'cancelled'",
                $soldIds
            );
            foreach ($activeSaleRows as $ar) {
                $iid = (int)$ar['imei_id'];
                if (!isset($activeSaleItemByImei[$iid])) {
                    $activeSaleItemByImei[$iid] = [
                        'sale_id' => (int)$ar['sale_id'],
                        'item_id' => (int)$ar['item_id'],
                    ];
                }
            }

            if (!empty($activeSaleRows)) {
                $pairSeen = [];
                $saleIds  = [];
                $itemIds  = [];
                foreach ($activeSaleRows as $ar) {
                    $s = (int)$ar['sale_id'];
                    $i = (int)$ar['item_id'];
                    $k = $s . ':' . $i;
                    if (!isset($pairSeen[$k])) {
                        $pairSeen[$k] = true;
                        $saleIds[] = $s;
                        $itemIds[] = $i;
                    }
                }

                $saleIds = array_values(array_unique($saleIds));
                $itemIds = array_values(array_unique($itemIds));
                if (!empty($saleIds) && !empty($itemIds)) {
                    $salePh = implode(',', array_fill(0, count($saleIds), '?'));
                    $itemPh = implode(',', array_fill(0, count($itemIds), '?'));

                    $returnRows = $this->db->fetchAll(
                        "SELECT r.ref_id AS sale_id, ri.item_id, SUM(ri.quantity) AS returned_qty
                         FROM returns r
                         JOIN return_items ri ON ri.return_id = r.id
                         WHERE r.type = 'sale_return'
                           AND r.status = 'approved'
                           AND r.ref_id IN ({$salePh})
                           AND ri.item_id IN ({$itemPh})
                         GROUP BY r.ref_id, ri.item_id",
                        array_merge($saleIds, $itemIds)
                    );
                    foreach ($returnRows as $rr) {
                        $key = ((int)$rr['sale_id']) . ':' . ((int)$rr['item_id']);
                        $returnQtyBySaleItem[$key] = (int)($rr['returned_qty'] ?? 0);
                    }

                    $soldRows = $this->db->fetchAll(
                        "SELECT si.sale_id, si.item_id, COUNT(DISTINCT sii.imei_id) AS sold_qty
                         FROM sale_item_imei sii
                         JOIN sale_items si ON si.id = sii.sale_item_id
                         JOIN sales s ON s.id = si.sale_id
                         JOIN imei_records ir ON ir.id = sii.imei_id
                         WHERE s.status != 'cancelled'
                           AND ir.status = 'sold'
                           AND si.sale_id IN ({$salePh})
                           AND si.item_id IN ({$itemPh})
                         GROUP BY si.sale_id, si.item_id",
                        array_merge($saleIds, $itemIds)
                    );
                    foreach ($soldRows as $sr) {
                        $key = ((int)$sr['sale_id']) . ':' . ((int)$sr['item_id']);
                        $soldCountBySaleItem[$key] = (int)($sr['sold_qty'] ?? 0);
                    }
                }
            }
        }

        $latestSaleTokenMap     = [];
        $latestReturnTokenMap   = [];
        $returnedAfterSaleByImei = [];
        if (!empty($soldIds)) {
            $soldPh = implode(',', array_fill(0, count($soldIds), '?'));
            $saleMovements = $this->db->fetchAll(
                "SELECT sii.imei_id,
                        MAX(CONCAT(DATE_FORMAT(COALESCE(s.date, '1970-01-01'), '%Y%m%d'), '-', LPAD(s.id, 12, '0'))) AS sale_token
                 FROM sale_item_imei sii
                 JOIN sale_items si ON si.id = sii.sale_item_id
                 JOIN sales s ON s.id = si.sale_id
                 WHERE sii.imei_id IN ({$soldPh}) AND s.status != 'cancelled'
                 GROUP BY sii.imei_id",
                $soldIds
            );
            foreach ($saleMovements as $mv) {
                $latestSaleTokenMap[(int)$mv['imei_id']] = (string)($mv['sale_token'] ?? '');
            }

            $returnMovements = $this->db->fetchAll(
                "SELECT rii.imei_id,
                        MAX(CONCAT(DATE_FORMAT(COALESCE(r.date, '1970-01-01'), '%Y%m%d'), '-', LPAD(r.id, 12, '0'))) AS return_token
                 FROM return_item_imei rii
                 JOIN return_items ri ON ri.id = rii.return_item_id
                 JOIN returns r ON r.id = ri.return_id
                 WHERE rii.imei_id IN ({$soldPh})
                   AND r.type = 'sale_return'
                   AND r.status = 'approved'
                 GROUP BY rii.imei_id",
                $soldIds
            );
            foreach ($returnMovements as $rv) {
                $latestReturnTokenMap[(int)$rv['imei_id']] = (string)($rv['return_token'] ?? '');
            }
        }

        // Duplicate IMEI rows can split history across multiple imei_ids.
        // Also compare movements by IMEI string to detect a real "returned after sale" state.
        $imeiSaleMovements = $this->db->fetchAll(
            "SELECT ir.imei,
                    MAX(CONCAT(DATE_FORMAT(COALESCE(s.date, '1970-01-01'), '%Y%m%d'), '-', LPAD(s.id, 12, '0'))) AS sale_token
             FROM sale_item_imei sii
             JOIN sale_items si ON si.id = sii.sale_item_id
             JOIN sales s ON s.id = si.sale_id
             JOIN imei_records ir ON ir.id = sii.imei_id
             WHERE ir.imei IN ({$placeholders}) AND s.status != 'cancelled'
             GROUP BY ir.imei",
            $clean
        );
        $imeiSaleTokenMap = [];
        foreach ($imeiSaleMovements as $mv) {
            $imeiSaleTokenMap[(string)$mv['imei']] = (string)($mv['sale_token'] ?? '');
        }

        $imeiReturnMovements = $this->db->fetchAll(
            "SELECT ir.imei,
                    MAX(CONCAT(DATE_FORMAT(COALESCE(r.date, '1970-01-01'), '%Y%m%d'), '-', LPAD(r.id, 12, '0'))) AS return_token
             FROM return_item_imei rii
             JOIN return_items ri ON ri.id = rii.return_item_id
             JOIN returns r ON r.id = ri.return_id
             JOIN imei_records ir ON ir.id = rii.imei_id
             WHERE ir.imei IN ({$placeholders})
               AND r.type = 'sale_return'
               AND r.status = 'approved'
             GROUP BY ir.imei",
            $clean
        );
        $imeiReturnTokenMap = [];
        foreach ($imeiReturnMovements as $rv) {
            $imeiReturnTokenMap[(string)$rv['imei']] = (string)($rv['return_token'] ?? '');
        }
        foreach ($clean as $imei) {
            $st = $imeiSaleTokenMap[$imei] ?? '';
            $rt = $imeiReturnTokenMap[$imei] ?? '';
            $returnedAfterSaleByImei[$imei] = ($rt !== '' && ($st === '' || strcmp($rt, $st) >= 0));
        }

        // Heal stale sold rows (no active sale link) so sale creation can continue.
        $staleSoldToHeal = [];
        foreach ($rowsByImei as $imei => $imeiRows) {
            foreach ($imeiRows as $idx => $row) {
                if (($row['status'] ?? '') !== 'sold') {
                    continue;
                }
                $id = (int)$row['id'];
                $saleToken   = $latestSaleTokenMap[$id] ?? '';
                $returnToken = $latestReturnTokenMap[$id] ?? '';
                $returnedAfterSale = ($returnToken !== '' && ($saleToken === '' || strcmp($returnToken, $saleToken) >= 0));
                $returnEntitled = false;
                if (isset($activeSaleItemByImei[$id])) {
                    $sid = (int)$activeSaleItemByImei[$id]['sale_id'];
                    $iid = (int)$activeSaleItemByImei[$id]['item_id'];
                    $rk  = $sid . ':' . $iid;
                    $retQty  = (int)($returnQtyBySaleItem[$rk] ?? 0);
                    $soldQty = (int)($soldCountBySaleItem[$rk] ?? 0);
                    if ($retQty > 0 && $soldQty > 0) {
                        $expectedRemain = max(0, $soldQty - $retQty);
                        $otherSold      = max(0, $soldQty - 1);
                        if ($otherSold >= $expectedRemain) {
                            $returnEntitled = true;
                        }
                    }
                }

                $returnedAfterSaleByText = !empty($returnedAfterSaleByImei[$imei]);
                if (!isset($activeSoldMap[$id]) || $returnedAfterSale || $returnedAfterSaleByText || $returnEntitled) {
                    $staleSoldToHeal[] = $id;
                    $rowsByImei[$imei][$idx]['status']  = 'in_stock';
                    $rowsByImei[$imei][$idx]['sale_id'] = null;
                }
            }
        }
        if (!empty($staleSoldToHeal)) {
            $staleSoldToHeal = array_values(array_unique($staleSoldToHeal));
            $healPh          = implode(',', array_fill(0, count($staleSoldToHeal), '?'));
            $this->db->execute(
                "UPDATE imei_records
                 SET status = 'in_stock', sale_id = NULL
                 WHERE id IN ({$healPh}) AND status = 'sold'",
                $staleSoldToHeal
            );
        }

        foreach ($clean as $imei) {
            $imeiRows = $rowsByImei[$imei] ?? [];
            if (empty($imeiRows)) {
                continue;
            }

            $availableInCurrentWh = false;
            $availableOtherWh     = false;
            $hasSameItemRecord    = false;
            $hasDifferentItemOnly = true;
            $hasBlockingSold      = false;

            foreach ($imeiRows as $row) {
                $rowItemId = (int)($row['item_id'] ?? 0);
                $rowWhId   = isset($row['warehouse_id']) ? (int)$row['warehouse_id'] : 0;
                $status    = (string)($row['status'] ?? '');

                if ($rowItemId === $itemId) {
                    $hasSameItemRecord = true;
                    $hasDifferentItemOnly = false;
                }

                if ($rowItemId === $itemId && in_array($status, ['in_stock', 'returned'], true)) {
                    if ($warehouseId === null || $warehouseId <= 0 || $rowWhId === $warehouseId) {
                        $availableInCurrentWh = true;
                    } else {
                        $availableOtherWh = true;
                    }
                }

                if ($rowItemId === $itemId && $status === 'sold' && isset($activeSoldMap[(int)$row['id']])) {
                    $hasBlockingSold = true;
                }
            }

            if ($availableInCurrentWh) {
                continue;
            }

            if ($hasBlockingSold) {
                if (!empty($returnedAfterSaleByImei[$imei])) {
                    continue;
                }
                $errors[] = "IMEI {$imei} is already sold.";
                continue;
            }

            if ($availableOtherWh) {
                $errors[] = "IMEI {$imei} is available in a different warehouse.";
                continue;
            }

            if ($hasDifferentItemOnly || !$hasSameItemRecord) {
                $errors[] = "IMEI {$imei} belongs to a different product.";
            }
        }

        return $errors;
    }
}
