<?php

/**
 * Union Logistics packing — vendor bill AP (invoice-driven).
 *
 * International practice: shipment packing lines / accruals = inventory costing estimates.
 * Accounts payable = vendor invoice (this table) + payment 1:1. Estimates never sit on the party ledger.
 */
class PackingVendorBillService {

    public static function ensureSchema(Database $db): void {
        static $done = false;
        if ($done) {
            return;
        }
        // MySQL DDL implicitly commits the current transaction. Never run CREATE
        // mid-sale/payment — that leaves PDO thinking a txn is open, then commit/rollback 500s.
        if ($db->inTransaction()) {
            return;
        }
        $done = true;
        try {
            $db->execute(
                "CREATE TABLE IF NOT EXISTS import_vendor_bills (
                    id                  INT AUTO_INCREMENT PRIMARY KEY,
                    bill_no             VARCHAR(50) NOT NULL,
                    party_id            INT NOT NULL,
                    leg                 ENUM('packing_dxb') NOT NULL DEFAULT 'packing_dxb',
                    period_ym           CHAR(7) NULL,
                    bill_date           DATE NOT NULL,
                    amount              DECIMAL(15,3) NOT NULL,
                    erp_estimate        DECIMAL(15,3) NOT NULL DEFAULT 0,
                    vendor_invoice_ref  VARCHAR(100) NULL,
                    warehouse_id        INT NULL,
                    status              ENUM('open', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
                    payment_id          INT NULL,
                    notes               VARCHAR(500) NULL,
                    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_ivb_bill_no (bill_no),
                    UNIQUE KEY uq_ivb_payment (payment_id),
                    INDEX idx_ivb_party_status (party_id, status),
                    INDEX idx_ivb_period (period_ym)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
            error_log('[PackingVendorBillService] ensureSchema: ' . $e->getMessage());
        }
    }

    /**
     * Clear ERP packing accruals from AP scope (costing rows stay; status cancelled so packing due / party ignore them).
     *
     * @param list<int> $chargeIds shipment_item_charges.id
     */
    public static function clearPackingAccrualsIntoBill(
        Database $db,
        array $chargeIds,
        string $reason
    ): int {
        require_once __DIR__ . '/ImportPayableAccrualService.php';
        return ImportPayableAccrualService::cancelOpenByCharges(
            $db,
            'shipment_packing_dxb',
            $chargeIds,
            $reason
        );
    }

    /**
     * Also cancel already-paid packing accruals (historical) so they leave the party statement.
     *
     * @param list<int> $chargeIds
     */
    public static function cancelPaidPackingAccruals(Database $db, array $chargeIds, string $reason): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $chargeIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return 0;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $note = trim($reason);
        if ($note !== '') {
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'cancelled',
                     description = CONCAT(COALESCE(description, ''), CASE WHEN description IS NULL OR description = '' THEN '' ELSE ' · ' END, ?)
                 WHERE shipment_item_charge_id IN ({$ph}) AND leg = 'packing_dxb' AND status = 'paid'",
                array_merge([$note], $ids)
            );
        } else {
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'cancelled'
                 WHERE shipment_item_charge_id IN ({$ph}) AND leg = 'packing_dxb' AND status = 'paid'",
                $ids
            );
        }
        $left = $db->fetchOne(
            "SELECT COUNT(*) AS c FROM import_payable_accruals
             WHERE shipment_item_charge_id IN ({$ph}) AND leg = 'packing_dxb' AND status = 'paid'",
            $ids
        );
        return count($ids) - (int) ($left['c'] ?? 0);
    }

    /**
     * Create a paid vendor bill matched to a payment (invoice amount = payment amount).
     */
    public static function createPaidBill(
        Database $db,
        int $partyId,
        int $paymentId,
        float $amount,
        string $billDate,
        ?string $periodYm,
        float $erpEstimate,
        ?int $warehouseId,
        string $vendorInvoiceRef = '',
        string $notes = ''
    ): int {
        self::ensureSchema($db);
        $amount = round($amount, 3);
        if ($partyId <= 0 || $paymentId <= 0 || $amount <= 0.001) {
            return 0;
        }

        $existing = $db->fetchOne(
            "SELECT id FROM import_vendor_bills WHERE payment_id = ? AND status != 'cancelled'",
            [$paymentId]
        );
        if ($existing) {
            return (int) $existing['id'];
        }

        $billNo = self::nextBillNo($db);
        $db->insert(
            "INSERT INTO import_vendor_bills
                (bill_no, party_id, leg, period_ym, bill_date, amount, erp_estimate,
                 vendor_invoice_ref, warehouse_id, status, payment_id, notes)
             VALUES (?,?,?,?,?,?,?,?,?,'paid',?,?)",
            [
                $billNo,
                $partyId,
                'packing_dxb',
                $periodYm,
                $billDate,
                $amount,
                round($erpEstimate, 3),
                $vendorInvoiceRef !== '' ? $vendorInvoiceRef : null,
                $warehouseId,
                $paymentId,
                $notes !== '' ? $notes : null,
            ]
        );

        $row = $db->fetchOne("SELECT id FROM import_vendor_bills WHERE payment_id = ?", [$paymentId]);
        return (int) ($row['id'] ?? 0);
    }

    private static function nextBillNo(Database $db): string {
        $ym = date('Ym');
        $row = $db->fetchOne(
            "SELECT bill_no FROM import_vendor_bills
             WHERE bill_no LIKE ?
             ORDER BY id DESC LIMIT 1",
            ['IVB-PKG-' . $ym . '-%']
        );
        $seq = 1;
        if ($row && preg_match('/IVB-PKG-\d{6}-(\d+)$/', (string) $row['bill_no'], $m)) {
            $seq = (int) $m[1] + 1;
        }
        return sprintf('IVB-PKG-%s-%03d', $ym, $seq);
    }

    /**
     * One-time: move packing party AP from line accruals → vendor bills matched to packing payments.
     * Opening balance reset to 0 (removes prior statement “force Clear” hacks).
     * Does NOT invent ledger offsets — residual net is reported for review.
     *
     * @return array{ok:bool,bills:int,accruals_cancelled:int,opening_reset:bool,net:float,message:string}
     */
    public static function migratePartyToInvoiceAp(
        Database $db,
        int $partyId,
        int $warehouseId
    ): array {
        self::ensureSchema($db);
        require_once __DIR__ . '/../models/Party.php';

        $note = 'MIGRATE to vendor bill AP — packing estimate removed from party ledger';
        $db->beginTransaction();
        try {
            $before = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM import_payable_accruals
                 WHERE party_id = ? AND leg = 'packing_dxb' AND status IN ('open', 'paid')",
                [$partyId]
            );
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'cancelled',
                     description = CONCAT(COALESCE(description, ''), CASE WHEN description IS NULL OR description = '' THEN '' ELSE ' · ' END, ?)
                 WHERE party_id = ? AND leg = 'packing_dxb' AND status IN ('open', 'paid')",
                [$note, $partyId]
            );
            $cancelled = (int) ($before['c'] ?? 0);

            // All packing PAYs (no date window — must match unbounded accrual cancel).
            $payments = $db->fetchAll(
                "SELECT p.id, p.payment_no, p.amount, p.date, p.notes
                 FROM payments p
                 WHERE p.party_id = ?
                   AND p.payment_type = 'out'
                   AND p.status = 'active'
                   AND (p.warehouse_id = ? OR p.warehouse_id IS NULL)
                   AND (
                        p.ref_type = 'shipment_packing_dxb'
                        OR EXISTS (
                            SELECT 1 FROM shipment_item_charges sic
                            WHERE sic.packing_dxb_payment_id = p.id
                        )
                        OR EXISTS (
                            SELECT 1 FROM import_payable_accruals ipa
                            WHERE ipa.payment_id = p.id AND ipa.leg = 'packing_dxb'
                        )
                   )
                 ORDER BY p.date ASC, p.id ASC",
                [$partyId, $warehouseId]
            );

            $bills = 0;
            foreach ($payments as $p) {
                $payId = (int) ($p['id'] ?? 0);
                $amt = round((float) ($p['amount'] ?? 0), 3);
                if ($payId <= 0 || $amt <= 0.001) {
                    continue;
                }
                $periodYm = substr((string) ($p['date'] ?? ''), 0, 7);
                $id = self::createPaidBill(
                    $db,
                    $partyId,
                    $payId,
                    $amt,
                    (string) ($p['date'] ?? date('Y-m-d')),
                    preg_match('/^\d{4}-\d{2}$/', $periodYm) ? $periodYm : null,
                    0.0,
                    $warehouseId,
                    '',
                    'Migrated from ' . (string) ($p['payment_no'] ?? '') . ' — packing vendor bill AP'
                );
                if ($id > 0) {
                    $bills++;
                }
            }

            $db->execute("UPDATE parties SET opening_balance = 0 WHERE id = ?", [$partyId]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('[PackingVendorBillService] migratePartyToInvoiceAp: ' . $e->getMessage());
            return [
                'ok'                  => false,
                'bills'               => 0,
                'accruals_cancelled'  => 0,
                'opening_reset'       => false,
                'net'                 => 0.0,
                'message'             => 'Migrate failed (rolled back): ' . $e->getMessage(),
            ];
        }

        Party::clearBalanceListCache();

        $partyModel = new Party();
        $net = round($partyModel->computeBalanceAsOf($partyId, date('Y-m-d'), $warehouseId), 3);
        $ok = abs($net) <= 0.001;
        $msg = sprintf(
            'Vendor-bill AP migrate: %d bill(s) from packing PAY(s), %d estimate accrual(s) off party ledger, opening = 0.',
            $bills,
            $cancelled
        );
        if (!$ok) {
            $msg .= sprintf(
                ' Residual statement net %s %s — review non-packing docs (do not use force-Clear).',
                APP_CURRENCY,
                number_format($net, DECIMAL_PLACES)
            );
        } else {
            $msg .= ' Party statement Clear (invoice ↔ payment).';
        }

        return [
            'ok'                  => $ok,
            'bills'               => $bills,
            'accruals_cancelled'  => $cancelled,
            'opening_reset'       => true,
            'net'                 => $net,
            'message'             => $msg,
        ];
    }
}
