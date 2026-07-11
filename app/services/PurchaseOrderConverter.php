<?php

/**
 * Convert a purchase order to a purchase invoice + stock (shared by PO screen and import receive).
 */
class PurchaseOrderConverter {

    /**
     * @return int New purchase id
     * @throws Exception
     */
    public static function convert(
        Database $db,
        int $poId,
        ?string $receiveDate = null,
        bool $skipItemMasterPriceUpdate = false,
        string $notesSuffix = '',
        ?array &$poItemToPurchaseItem = null
    ): int {
        $po = $db->fetchOne("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE", [$poId]);
        if (!$po) {
            throw new Exception('Purchase order not found.');
        }
        if ($po['status'] === 'converted') {
            throw new Exception("PO {$po['po_no']} is already converted.");
        }
        if (!in_array($po['status'], ['paid', 'draft'], true)) {
            throw new Exception("PO {$po['po_no']} cannot be converted (status: {$po['status']}).");
        }

        $items = $db->fetchAll(
            "SELECT poi.*, i.name as item_name, i.has_imei
             FROM purchase_order_items poi
             JOIN items i ON i.id = poi.item_id
             WHERE poi.po_id = ?
             ORDER BY poi.id",
            [$poId]
        );
        if (empty($items)) {
            throw new Exception("PO {$po['po_no']} has no items.");
        }

        $receiveDate = $receiveDate ?: date('Y-m-d');

        $last      = $db->fetchOne("SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $num       = $last ? (int) substr($last['invoice_no'], strlen(PURCHASE_PREFIX)) : 0;
        $invoiceNo = PURCHASE_PREFIX . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

        $itemSubtotal = (float) $po['subtotal_kwd'];
        $otherCharges = (float) ($po['other_charges_kwd'] ?? 0);
        $grandTotal   = round($itemSubtotal + $otherCharges, 3);
        $paid         = (float) $po['paid_kwd'];
        $balance      = max(0, $grandTotal - $paid);
        $status       = $balance < 0.001 ? 'paid' : 'partial';

        $notes = "Converted from PO: {$po['po_no']}. Currency: {$po['currency']} @ rate {$po['exchange_rate']}. "
            . ($otherCharges > 0.001 ? "Other charges: {$otherCharges} KWD. " : '')
            . ($po['notes'] ?: '')
            . ($notesSuffix !== '' ? ' ' . $notesSuffix : '');

        $purchaseId = (int) $db->insert(
            "INSERT INTO purchases
                (invoice_no, party_id, warehouse_id, date, subtotal, discount, tax,
                 grand_total, paid_amount, balance, status,
                 supplier_invoice_no, notes, created_by)
             VALUES (?,?,?,?,?,0,0,?,?,?,?,?,?,?)",
            [
                $invoiceNo,
                $po['party_id'],
                $po['warehouse_id'],
                $receiveDate,
                $itemSubtotal,
                $grandTotal,
                $paid,
                $balance,
                $status,
                $po['supplier_ref'] ?: $po['po_no'],
                trim($notes),
                Auth::id(),
            ]
        );

        foreach ($items as $item) {
            $lineTotal = round((float) $item['unit_price_kwd'] * (int) $item['quantity'], 3);
            $purchaseItemId = (int) $db->insert(
                "INSERT INTO purchase_items (purchase_id, item_id, quantity, unit_price, total)
                 VALUES (?,?,?,?,?)",
                [$purchaseId, $item['item_id'], $item['quantity'], $item['unit_price_kwd'], $lineTotal]
            );
            if ($poItemToPurchaseItem !== null) {
                $poItemToPurchaseItem[(int) $item['id']] = $purchaseItemId;
            }

            $db->execute(
                "INSERT INTO stock (item_id, warehouse_id, quantity)
                 VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                [$item['item_id'], $po['warehouse_id'], $item['quantity'], $item['quantity']]
            );

            if (!$skipItemMasterPriceUpdate) {
                require_once __DIR__ . '/ItemCostService.php';
                ItemCostService::syncAfterPurchaseLine(
                    $db,
                    (int) $item['item_id'],
                    $purchaseId,
                    (float) $item['unit_price_kwd']
                );
            }
        }

        $db->execute(
            "UPDATE purchase_orders SET status='converted', converted_to=? WHERE id=?",
            [$purchaseId, $poId]
        );

        $db->execute(
            "UPDATE payments SET ref_type='purchase', ref_id=?
             WHERE ref_type='purchase_order' AND ref_id=? AND status='active'",
            [$purchaseId, $poId]
        );

        if ($paid > 0) {
            $hasPayment = $db->fetchOne(
                "SELECT id FROM payments WHERE ref_type='purchase' AND ref_id=? AND status='active'",
                [$purchaseId]
            );
            if (!$hasPayment) {
                $lastPay = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum  = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo   = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);
                $accId   = (int) ($po['account_id'] ?? 0)
                    ?: (int) ($db->fetchOne("SELECT id FROM accounts WHERE is_active=1 ORDER BY sort_order LIMIT 1")['id'] ?? 0);
                // NOTE: Do NOT deduct accounts.current_balance here. The PO's paid amount
                // was already deducted when the PO was saved/marked paid (or is reflected
                // via the unlinked-PO term in balance recalc). Deducting again here
                // double-counts the payout. This row only backfills the missing ledger entry.
                $db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'purchase', $purchaseId, $po['party_id'], 'out', $accId, $paid, 'bank', $receiveDate, $po['warehouse_id'], Auth::id()]
                );
            }
        }

        $paidSumRow = $db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE ref_type='purchase' AND ref_id=? AND status='active'",
            [$purchaseId]
        );
        $paidSum    = (float) ($paidSumRow['total'] ?? 0);
        $newBalance = max(0, $grandTotal - $paidSum);
        $newStatus  = $newBalance < 0.001 ? 'paid' : ($paidSum > 0 ? 'partial' : 'confirmed');
        $db->execute(
            "UPDATE purchases SET paid_amount=?, balance=?, status=? WHERE id=?",
            [round($paidSum, 3), round($newBalance, 3), $newStatus, $purchaseId]
        );

        return $purchaseId;
    }

    /**
     * Reopen PO(s) linked to a cancelled purchase so they can be edited and converted again.
     *
     * @return string[] Reopened PO numbers
     */
    public static function reopenConvertedPosForPurchase(Database $db, int $purchaseId): array {
        $rows = $db->fetchAll(
            "SELECT id, po_no FROM purchase_orders WHERE converted_to = ? AND status = 'converted'",
            [$purchaseId]
        );
        $reopened = [];
        foreach ($rows as $row) {
            if (self::reopenConvertedPo($db, (int) $row['id'])) {
                $reopened[] = (string) $row['po_no'];
            }
        }
        return $reopened;
    }

    /**
     * Reopen a single converted PO when its linked purchase was cancelled or removed.
     */
    public static function reopenConvertedPo(Database $db, int $poId): bool {
        $po = $db->fetchOne(
            "SELECT id, po_no, status, paid_kwd, subtotal_kwd, other_charges_kwd, converted_to
             FROM purchase_orders WHERE id = ?",
            [$poId]
        );
        if (!$po || ($po['status'] ?? '') !== 'converted') {
            return false;
        }

        $convertedTo = (int) ($po['converted_to'] ?? 0);
        if ($convertedTo > 0) {
            $purchase = $db->fetchOne("SELECT status FROM purchases WHERE id = ?", [$convertedTo]);
            if ($purchase && ($purchase['status'] ?? '') !== 'cancelled') {
                return false;
            }
            self::reactivateCancelledPaymentsForPo($db, $poId, $convertedTo);
        }

        $totalKwd = (float) $po['subtotal_kwd'] + (float) ($po['other_charges_kwd'] ?? 0);
        $paidKwd  = (float) ($po['paid_kwd'] ?? 0);
        $newStatus = self::resolvePoStatusAfterReopen($paidKwd, $totalKwd);

        $db->execute(
            "UPDATE purchase_orders SET status = ?, converted_to = NULL WHERE id = ?",
            [$newStatus, $poId]
        );

        return true;
    }

    /**
     * Fix PO marked paid with no active bank payment after a cancelled purchase (reopen/edit path).
     */
    public static function reconcilePoPaymentLink(Database $db, int $poId): bool {
        $po = $db->fetchOne(
            "SELECT id, po_no, status, paid_kwd, converted_to
             FROM purchase_orders WHERE id = ?",
            [$poId]
        );
        if (!$po || ($po['status'] ?? '') === 'cancelled' || ($po['status'] ?? '') === 'converted') {
            return false;
        }

        $paidKwd = (float) ($po['paid_kwd'] ?? 0);
        if ($paidKwd <= 0.001) {
            return false;
        }

        if ($db->fetchOne(
            "SELECT id FROM payments WHERE ref_type='purchase_order' AND ref_id=? AND status='active' LIMIT 1",
            [$poId]
        )) {
            return false;
        }

        $convertedTo = (int) ($po['converted_to'] ?? 0);
        if ($convertedTo > 0 && $db->fetchOne(
            "SELECT id FROM payments WHERE ref_type='purchase' AND ref_id=? AND status='active' LIMIT 1",
            [$convertedTo]
        )) {
            return false;
        }

        if ($db->fetchOne(
            "SELECT p.id FROM payments p
             INNER JOIN purchases pur ON pur.id = p.ref_id AND pur.status != 'cancelled'
             WHERE p.status = 'active' AND p.ref_type = 'purchase'
               AND pur.notes LIKE ?",
            ['%Converted from PO: ' . $po['po_no'] . '%']
        )) {
            return false;
        }

        $cancelledPay = $db->fetchOne(
            "SELECT pay.id, pay.account_id, pay.amount, pay.ref_id AS purchase_id
             FROM payments pay
             JOIN purchases pur ON pur.id = pay.ref_id
             WHERE pay.ref_type = 'purchase'
               AND pay.status = 'cancelled'
               AND pur.status = 'cancelled'
               AND pur.notes LIKE ?
               AND ABS(pay.amount - ?) < 0.001
             ORDER BY pay.id DESC
             LIMIT 1",
            ['%Converted from PO: ' . $po['po_no'] . '%', $paidKwd]
        );
        if (!$cancelledPay) {
            return false;
        }

        return self::reactivateCancelledPaymentsForPo($db, $poId, (int) $cancelledPay['purchase_id']) > 0;
    }

    /**
     * Move cancelled purchase payment(s) back to the PO and re-deduct the bank account.
     *
     * @return int Number of payments reactivated
     */
    private static function reactivateCancelledPaymentsForPo(Database $db, int $poId, int $purchaseId): int {
        if ($purchaseId <= 0) {
            return 0;
        }

        if ($db->fetchOne(
            "SELECT id FROM payments WHERE ref_type = 'purchase_order' AND ref_id = ? AND status = 'active' LIMIT 1",
            [$poId]
        )) {
            return 0;
        }

        $rows = $db->fetchAll(
            "SELECT id, account_id, amount FROM payments
             WHERE ref_type = 'purchase' AND ref_id = ? AND status = 'cancelled'",
            [$purchaseId]
        );
        if (empty($rows)) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $accId = (int) ($row['account_id'] ?? 0);
            $amount = (float) ($row['amount'] ?? 0);
            if ($accId > 0 && $amount > 0) {
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?",
                    [$amount, $accId]
                );
            }
            $db->execute(
                "UPDATE payments SET status = 'active', ref_type = 'purchase_order', ref_id = ? WHERE id = ?",
                [$poId, (int) $row['id']]
            );
            $count++;
        }

        return $count;
    }

    private static function resolvePoStatusAfterReopen(float $paidKwd, float $totalKwd): string {
        if ($paidKwd <= 0) {
            return 'draft';
        }
        $tolerance = min(0.100, max(0.001, round($totalKwd * 0.00001, 3)));
        return round($totalKwd - $paidKwd, 3) <= $tolerance ? 'paid' : 'draft';
    }
}
