<?php

/**
 * Rebuild stock.quantity from documents (independent of IMEI scans).
 * IMEI registration does not create or change stock qty — arrivals do.
 * Admin write-offs of physically missing units live in stock_adjustments and are
 * included in rebuild so the missing qty cannot come back.
 */
class StockQuantityService {

    /**
     * TEMP: admin physical-count / IMEI-pending qty write-off.
     * Set true to restore Stock List click-qty and IMEI Audit write-off.
     */
    public const TEMP_ALLOW_STOCK_WRITEOFF = false;

    public static function writeOffEnabled(): bool {
        return self::TEMP_ALLOW_STOCK_WRITEOFF;
    }

    /** @var bool|null */
    private static $adjustmentsReady = null;

    /**
     * Create stock_adjustments if the deployed DB has not run the migration yet.
     * Must not run inside a transaction (MySQL DDL commits implicitly).
     */
    public static function ensureAdjustmentsSchema(Database $db): void {
        if (self::$adjustmentsReady === true) {
            return;
        }
        if ($db->inTransaction()) {
            return;
        }
        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS stock_adjustments (
                    id              INT AUTO_INCREMENT PRIMARY KEY,
                    warehouse_id    INT NOT NULL,
                    item_id         INT NOT NULL,
                    quantity_delta  INT NOT NULL,
                    reason          VARCHAR(64) NOT NULL DEFAULT 'imei_pending_writeoff',
                    notes           VARCHAR(500) NULL,
                    created_by      INT NULL,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_sa_item_wh (item_id, warehouse_id),
                    INDEX idx_sa_wh (warehouse_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::$adjustmentsReady = true;
        } catch (Throwable $e) {
            self::$adjustmentsReady = false;
            error_log('[StockQuantityService] ensureAdjustmentsSchema: ' . $e->getMessage());
        }
    }

    /**
     * Book qty from opening + purchases − sales ± returns ± transfers ± adjustments.
     */
    public static function computedQuantity(Database $db, int $itemId, int $warehouseId): int {
        $opening = (int) ($db->fetchOne(
            "SELECT quantity FROM opening_stock_log WHERE item_id = ? AND warehouse_id = ?",
            [$itemId, $warehouseId]
        )['quantity'] ?? 0);

        $purchased = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(pi.quantity), 0) AS q
             FROM purchase_items pi
             JOIN purchases p ON p.id = pi.purchase_id
             WHERE pi.item_id = ? AND p.warehouse_id = ? AND p.status != 'cancelled'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $sold = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(si.quantity), 0) AS q
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE si.item_id = ? AND s.warehouse_id = ? AND s.status != 'cancelled'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $saleReturned = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) AS q
             FROM return_items ri
             JOIN `returns` r ON r.id = ri.return_id
             WHERE ri.item_id = ? AND r.warehouse_id = ?
               AND r.type = 'sale_return' AND r.status = 'approved'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $purchaseReturned = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) AS q
             FROM return_items ri
             JOIN `returns` r ON r.id = ri.return_id
             WHERE ri.item_id = ? AND r.warehouse_id = ?
               AND r.type = 'purchase_return' AND r.status = 'approved'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $transferIn = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(sti.quantity), 0) AS q
             FROM stock_transfer_items sti
             JOIN stock_transfers st ON st.id = sti.transfer_id
             WHERE sti.item_id = ? AND st.to_warehouse_id = ? AND st.status = 'completed'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $transferOut = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(sti.quantity), 0) AS q
             FROM stock_transfer_items sti
             JOIN stock_transfers st ON st.id = sti.transfer_id
             WHERE sti.item_id = ? AND st.from_warehouse_id = ? AND st.status = 'completed'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);

        $interIn  = 0;
        $interOut = 0;
        if (is_file(__DIR__ . '/../models/IntershopTransfer.php')) {
            require_once __DIR__ . '/../models/IntershopTransfer.php';
            $interIn  = IntershopTransfer::qtyInbound($db, $itemId, $warehouseId);
            $interOut = IntershopTransfer::qtyOutbound($db, $itemId, $warehouseId);
        }

        $after = $opening + $purchased - $sold + $saleReturned - $purchaseReturned
            + $transferIn - $transferOut + $interIn - $interOut
            + self::sumAdjustments($db, $itemId, $warehouseId);
        return $after < 0 ? 0 : $after;
    }

    /**
     * Preview for IMEI Audit: how many book units sit above scanned IMEIs.
     *
     * @return array{stock_qty:int, imei_count:int, book_qty:int, write_off_qty:int}
     */
    public static function pendingWriteOffPreview(Database $db, int $itemId, int $warehouseId): array {
        $stockQty = (int) ($db->fetchOne(
            "SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?",
            [$itemId, $warehouseId]
        )['quantity'] ?? 0);
        $imeiCount = (int) ($db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records
             WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
            [$itemId, $warehouseId]
        )['c'] ?? 0);
        $bookQty = self::computedQuantity($db, $itemId, $warehouseId);

        return [
            'stock_qty'     => $stockQty,
            'imei_count'    => $imeiCount,
            'book_qty'      => $bookQty,
            'write_off_qty' => max(0, $bookQty - $imeiCount),
        ];
    }

    /**
     * Reduce book qty to a physical count (write-off only — never increases).
     * IMEI-tracked items cannot go below scanned in-stock serials.
     *
     * @return array{item_id:int, warehouse_id:int, before:int, after:int, written_off:int, rebuilt_only:bool}
     */
    public static function writeOffToQuantity(
        Database $db,
        int $itemId,
        int $warehouseId,
        int $targetQty,
        ?int $userId,
        string $notes = '',
        string $reason = 'physical_count'
    ): array {
        if ($itemId <= 0 || $warehouseId <= 0) {
            throw new InvalidArgumentException('item_id and warehouse_id are required.');
        }
        if (!$db->inTransaction()) {
            throw new RuntimeException('writeOffToQuantity must run inside a transaction.');
        }
        if ($targetQty < 0) {
            throw new InvalidArgumentException('Physical count cannot be negative.');
        }

        $item = $db->fetchOne(
            "SELECT id, has_imei, name FROM items WHERE id = ? FOR UPDATE",
            [$itemId]
        );
        if (!$item) {
            throw new RuntimeException('Item not found.');
        }

        $beforeRow = $db->fetchOne(
            "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
            [$itemId, $warehouseId]
        );
        $before = (int) ($beforeRow['quantity'] ?? 0);

        $imeiCount = 0;
        if (!empty($item['has_imei'])) {
            $imeiCount = (int) ($db->fetchOne(
                "SELECT COUNT(*) AS c FROM imei_records
                 WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
                [$itemId, $warehouseId]
            )['c'] ?? 0);
            if ($targetQty < $imeiCount) {
                throw new RuntimeException(
                    'Cannot write off below ' . $imeiCount
                    . ' scanned IMEIs. Reconcile extra serials on IMEI Audit first.'
                );
            }
        }

        $bookQty = self::computedQuantity($db, $itemId, $warehouseId);
        $delta = $targetQty - $bookQty;

        if ($delta > 0) {
            throw new RuntimeException(
                'Write-off can only reduce stock. Receive a purchase to add units.'
            );
        }

        $reason = preg_replace('/[^a-z0-9_]/', '', strtolower($reason)) ?: 'physical_count';
        $note = trim($notes);
        if ($note === '') {
            $note = $reason === 'imei_pending_writeoff'
                ? 'Physical count matches IMEIs; missing units written off'
                : 'Physical count write-off';
        }

        if ($delta === 0) {
            $rebuilt = self::rebuildForItem($db, $itemId, $warehouseId);
            if (!$rebuilt['changed'] && $before === $targetQty) {
                throw new RuntimeException('Stock qty already matches the physical count.');
            }
            return [
                'item_id'      => $itemId,
                'warehouse_id' => $warehouseId,
                'before'       => $before,
                'after'        => $rebuilt['after'],
                'written_off'  => 0,
                'rebuilt_only' => true,
            ];
        }

        $db->insert(
            "INSERT INTO stock_adjustments
                (warehouse_id, item_id, quantity_delta, reason, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$warehouseId, $itemId, $delta, $reason, $note, $userId]
        );

        $rebuilt = self::rebuildForItem($db, $itemId, $warehouseId);

        return [
            'item_id'      => $itemId,
            'warehouse_id' => $warehouseId,
            'before'       => $before,
            'after'        => $rebuilt['after'],
            'written_off'  => abs($delta),
            'rebuilt_only' => false,
        ];
    }

    /**
     * Reduce book qty to match in-stock IMEIs (physically missing units).
     *
     * @return array{item_id:int, warehouse_id:int, before:int, after:int, written_off:int, rebuilt_only:bool}
     */
    public static function writeOffImeiPending(
        Database $db,
        int $itemId,
        int $warehouseId,
        ?int $userId,
        string $notes = ''
    ): array {
        if ($itemId <= 0 || $warehouseId <= 0) {
            throw new InvalidArgumentException('item_id and warehouse_id are required.');
        }
        if (!$db->inTransaction()) {
            throw new RuntimeException('writeOffImeiPending must run inside a transaction.');
        }

        $item = $db->fetchOne(
            "SELECT id, has_imei FROM items WHERE id = ? FOR UPDATE",
            [$itemId]
        );
        if (!$item || empty($item['has_imei'])) {
            throw new RuntimeException('IMEI write-off is only for serial-tracked items. Use Stock List to write off accessories.');
        }

        $imeiCount = (int) ($db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records
             WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
            [$itemId, $warehouseId]
        )['c'] ?? 0);

        return self::writeOffToQuantity(
            $db,
            $itemId,
            $warehouseId,
            $imeiCount,
            $userId,
            $notes,
            'imei_pending_writeoff'
        );
    }

    private static function sumAdjustments(Database $db, int $itemId, int $warehouseId): int {
        if (self::$adjustmentsReady === false) {
            return 0;
        }
        try {
            $q = (int) ($db->fetchOne(
                "SELECT COALESCE(SUM(quantity_delta), 0) AS q
                 FROM stock_adjustments
                 WHERE item_id = ? AND warehouse_id = ?",
                [$itemId, $warehouseId]
            )['q'] ?? 0);
            self::$adjustmentsReady = true;
            return $q;
        } catch (Throwable $e) {
            self::$adjustmentsReady = false;
            return 0;
        }
    }

    /**
     * Recompute stock for one item at one warehouse from opening + movements.
     *
     * @return array{item_id:int, warehouse_id:int, before:int, after:int, changed:bool}
     */
    public static function rebuildForItem(Database $db, int $itemId, int $warehouseId): array {
        if ($itemId <= 0 || $warehouseId <= 0) {
            throw new InvalidArgumentException('item_id and warehouse_id are required.');
        }

        $db->fetchOne(
            "SELECT id FROM items WHERE id = ? FOR UPDATE",
            [$itemId]
        );

        $beforeRow = $db->fetchOne(
            "SELECT id, quantity FROM stock WHERE item_id = ? AND warehouse_id = ? FOR UPDATE",
            [$itemId, $warehouseId]
        );
        $before = (int) ($beforeRow['quantity'] ?? 0);

        $after = self::computedQuantity($db, $itemId, $warehouseId);

        if ($beforeRow) {
            $db->execute(
                "UPDATE stock SET quantity = ? WHERE id = ?",
                [$after, (int) $beforeRow['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO stock (item_id, warehouse_id, quantity) VALUES (?,?,?)",
                [$itemId, $warehouseId, $after]
            );
        }

        return [
            'item_id'      => $itemId,
            'warehouse_id' => $warehouseId,
            'before'       => $before,
            'after'        => $after,
            'changed'      => $before !== $after,
        ];
    }

    /**
     * Rebuild stock for every distinct item on a purchase (same warehouse as the invoice).
     *
     * @return array{rebuilt:int, changed:int, results:list<array{item_id:int, warehouse_id:int, before:int, after:int, changed:bool}>}
     */
    public static function rebuildForPurchase(Database $db, int $purchaseId): array {
        $purchase = $db->fetchOne(
            "SELECT id, warehouse_id, status FROM purchases WHERE id = ? FOR UPDATE",
            [$purchaseId]
        );
        if (!$purchase) {
            throw new Exception('Purchase not found.');
        }
        if (($purchase['status'] ?? '') === 'cancelled') {
            throw new Exception('Cancelled purchases cannot rebuild stock.');
        }

        $warehouseId = (int) $purchase['warehouse_id'];
        $itemIds = array_values(array_unique(array_map(
            'intval',
            array_column(
                $db->fetchAll(
                    "SELECT DISTINCT item_id FROM purchase_items WHERE purchase_id = ?",
                    [$purchaseId]
                ),
                'item_id'
            )
        )));

        $results = [];
        $changed = 0;
        foreach ($itemIds as $itemId) {
            if ($itemId <= 0) {
                continue;
            }
            $row = self::rebuildForItem($db, $itemId, $warehouseId);
            $results[] = $row;
            if ($row['changed']) {
                $changed++;
            }
        }

        return [
            'rebuilt' => count($results),
            'changed' => $changed,
            'results' => $results,
        ];
    }

    /**
     * Current warehouse qty + IMEI scanned count for purchase lines (UI / mismatch checks).
     *
     * @return list<array{item_id:int, quantity:int, stock_qty:int, imei_scanned:int, has_imei:int}>
     */
    public static function purchaseLineStockSnapshot(Database $db, int $purchaseId, int $warehouseId): array {
        return $db->fetchAll(
            "SELECT pi.item_id, pi.quantity,
                    COALESCE(s.quantity, 0) AS stock_qty,
                    (SELECT COUNT(*) FROM imei_records ir
                      WHERE ir.purchase_id = pi.purchase_id AND ir.item_id = pi.item_id) AS imei_scanned,
                    COALESCE(i.has_imei, 0) AS has_imei
             FROM purchase_items pi
             JOIN items i ON i.id = pi.item_id
             LEFT JOIN stock s ON s.item_id = pi.item_id AND s.warehouse_id = ?
             WHERE pi.purchase_id = ?
             ORDER BY pi.id",
            [$warehouseId, $purchaseId]
        );
    }

    /**
     * True when a purchase line has qty but warehouse stock row is missing or zero
     * while IMEI scans are also zero — typical "arrived but not on Stock List" case.
     */
    public static function purchaseLooksMissingFromStock(array $lineSnapshots): bool {
        foreach ($lineSnapshots as $line) {
            $qty = (int) ($line['quantity'] ?? 0);
            $stock = (int) ($line['stock_qty'] ?? 0);
            if ($qty > 0 && $stock <= 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Explain IMEI-over (available serials > stock.quantity) for one item/branch.
     *
     * @return array{
     *   stock_qty:int, imei_available:int, over:int, rebuild_qty:int,
     *   likely_cause:string, likely_detail:string,
     *   warranty_no_imei:list<array<string,mixed>>,
     *   unscanned_sales:list<array<string,mixed>>,
     *   stale_available:list<array<string,mixed>>,
     *   duplicate_imeis:list<array<string,mixed>>
     * }
     */
    public static function imeiOverDiagnosis(Database $db, int $itemId, int $warehouseId): array {
        $stockQty = (int) ($db->fetchOne(
            "SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?",
            [$itemId, $warehouseId]
        )['quantity'] ?? 0);

        $imeiAvailable = (int) ($db->fetchOne(
            "SELECT COUNT(*) AS c FROM imei_records
             WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')",
            [$itemId, $warehouseId]
        )['c'] ?? 0);

        $opening = (int) ($db->fetchOne(
            "SELECT quantity FROM opening_stock_log WHERE item_id = ? AND warehouse_id = ?",
            [$itemId, $warehouseId]
        )['quantity'] ?? 0);
        $purchased = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(pi.quantity), 0) AS q
             FROM purchase_items pi JOIN purchases p ON p.id = pi.purchase_id
             WHERE pi.item_id = ? AND p.warehouse_id = ? AND p.status != 'cancelled'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);
        $sold = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(si.quantity), 0) AS q
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             WHERE si.item_id = ? AND s.warehouse_id = ? AND s.status != 'cancelled'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);
        $saleReturned = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) AS q
             FROM return_items ri JOIN `returns` r ON r.id = ri.return_id
             WHERE ri.item_id = ? AND r.warehouse_id = ?
               AND r.type = 'sale_return' AND r.status = 'approved'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);
        $purchaseReturned = (int) ($db->fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) AS q
             FROM return_items ri JOIN `returns` r ON r.id = ri.return_id
             WHERE ri.item_id = ? AND r.warehouse_id = ?
               AND r.type = 'purchase_return' AND r.status = 'approved'",
            [$itemId, $warehouseId]
        )['q'] ?? 0);
        $rebuildQty = $opening + $purchased - $sold + $saleReturned - $purchaseReturned
            + self::sumAdjustments($db, $itemId, $warehouseId);
        if ($rebuildQty < 0) {
            $rebuildQty = 0;
        }

        $warrantyNoImei = $db->fetchAll(
            "SELECT wr.id, wr.replacement_no, wr.date, wr.new_imei, p.name AS customer_name
             FROM warranty_replacements wr
             LEFT JOIN parties p ON p.id = wr.party_id
             WHERE wr.new_item_id = ? AND wr.warehouse_id = ?
               AND (wr.new_imei IS NULL OR wr.new_imei = '')
             ORDER BY wr.date DESC, wr.id DESC
             LIMIT 10",
            [$itemId, $warehouseId]
        );

        $unscannedSales = $db->fetchAll(
            "SELECT s.id, s.invoice_no, s.date, si.quantity,
                    (SELECT COUNT(*) FROM sale_item_imei sii WHERE sii.sale_item_id = si.id) AS linked
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE si.item_id = ? AND s.warehouse_id = ? AND s.status != 'cancelled'
               AND (SELECT COUNT(*) FROM sale_item_imei sii WHERE sii.sale_item_id = si.id) < si.quantity
             ORDER BY s.date DESC, s.id DESC
             LIMIT 15",
            [$itemId, $warehouseId]
        );

        $staleAvailable = $db->fetchAll(
            "SELECT ir.id, ir.imei, ir.status, s.invoice_no, s.date AS sale_date, s.id AS sale_id
             FROM imei_records ir
             JOIN sale_item_imei sii ON sii.imei_id = ir.id
             JOIN sale_items si ON si.id = sii.sale_item_id
             JOIN sales s ON s.id = si.sale_id AND s.status != 'cancelled'
             LEFT JOIN purchases p ON p.id = ir.purchase_id AND p.status != 'cancelled'
             WHERE ir.item_id = ? AND ir.warehouse_id = ?
               AND ir.status IN ('in_stock','returned')
               AND NOT EXISTS (
                   SELECT 1
                   FROM return_item_imei rii
                   JOIN return_items ri ON ri.id = rii.return_item_id
                   JOIN `returns` r ON r.id = ri.return_id
                   WHERE rii.imei_id = ir.id
                     AND r.type = 'sale_return'
                     AND r.status = 'approved'
                     AND r.date >= s.date
               )
               AND (p.id IS NULL OR p.date < s.date)
             ORDER BY s.date DESC, ir.id DESC
             LIMIT 15",
            [$itemId, $warehouseId]
        );

        $duplicateImeis = $db->fetchAll(
            "SELECT imei, COUNT(*) AS c
             FROM imei_records
             WHERE item_id = ? AND warehouse_id = ? AND status IN ('in_stock','returned')
             GROUP BY imei
             HAVING c > 1
             LIMIT 10",
            [$itemId, $warehouseId]
        );

        $over = max(0, $imeiAvailable - $stockQty);
        $likelyCause = 'unknown';
        $likelyDetail = 'Could not pin a single cause. Open IMEI Audit for this item.';

        if ($duplicateImeis !== []) {
            $likelyCause = 'duplicate_imei';
            $likelyDetail = 'The same IMEI is stored twice as available.';
        } elseif ($warrantyNoImei !== []) {
            $likelyCause = 'warranty_no_imei';
            $nos = array_map(static fn($r) => (string) ($r['replacement_no'] ?? ''), $warrantyNoImei);
            $likelyDetail = 'Warranty replacement deducted stock without marking a replacement IMEI sold: '
                . implode(', ', array_filter($nos)) . '.';
        } elseif ($staleAvailable !== []) {
            $likelyCause = 'stale_available';
            $imeis = array_map(static fn($r) => (string) ($r['imei'] ?? ''), $staleAvailable);
            $likelyDetail = 'Serial(s) still marked available but linked to an active sale: '
                . implode(', ', array_slice(array_filter($imeis), 0, 5)) . '.';
        } elseif ($unscannedSales !== []) {
            $likelyCause = 'unscanned_sale';
            $inv = array_map(static fn($r) => (string) ($r['invoice_no'] ?? ''), $unscannedSales);
            $likelyDetail = 'Sale line(s) reduced qty without a matching IMEI scan: '
                . implode(', ', array_slice(array_filter($inv), 0, 5)) . '.';
        } elseif ($rebuildQty === $imeiAvailable && $rebuildQty !== $stockQty) {
            $likelyCause = 'stock_qty_stale';
            $likelyDetail = 'Document rebuild qty is ' . $rebuildQty
                . ' (matches IMEIs). Stock qty ' . $stockQty . ' is stale — Rebuild stock on a purchase of this item.';
        }

        return [
            'stock_qty'         => $stockQty,
            'imei_available'    => $imeiAvailable,
            'over'              => $over,
            'rebuild_qty'       => $rebuildQty,
            'likely_cause'      => $likelyCause,
            'likely_detail'     => $likelyDetail,
            'warranty_no_imei'  => $warrantyNoImei,
            'unscanned_sales'   => $unscannedSales,
            'stale_available'   => $staleAvailable,
            'duplicate_imeis'   => $duplicateImeis,
        ];
    }
}
