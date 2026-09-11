<?php

/**
 * Links Payments module rows back to shipment_item_charges payables.
 */
class LandedCostPaymentLinker {

    private const REF_TO_COLUMN = [
        'shipment_freight_hk'   => 'freight_hk_payment_id',
        'shipment_packing_dxb'  => 'packing_dxb_payment_id',
        'shipment_freight_dxb'  => 'freight_dxb_payment_id',
        'shipment_partner'      => 'partner_payment_id',
    ];

    public static function linkItemChargePayment(Database $db, string $refType, int $refId, int $paymentId): void {
        if ($refId <= 0 || $paymentId <= 0) {
            return;
        }
        $column = self::REF_TO_COLUMN[$refType] ?? null;
        if (!$column) {
            return;
        }
        $db->execute(
            "UPDATE shipment_item_charges SET {$column} = ? WHERE id = ? AND {$column} IS NULL",
            [$paymentId, $refId]
        );

        require_once __DIR__ . '/ImportPayableAccrualService.php';
        ImportPayableAccrualService::markPaidByCharge($db, $refType, $refId, $paymentId);
    }

    /**
     * One settlement payment clearing many partner-profit charge lines.
     *
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    public static function linkPartnerBulkPayment(Database $db, int $paymentId, array $chargeIds): int {
        return self::linkBulkPayment($db, 'shipment_partner', $paymentId, $chargeIds);
    }

    /**
     * One settlement payment clearing many freight/packing charge lines (same party + shipment + leg).
     *
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    public static function linkFreightBulkPayment(Database $db, string $refType, int $paymentId, array $chargeIds): int {
        if (!isset(self::REF_TO_COLUMN[$refType]) || $refType === 'shipment_partner') {
            return 0;
        }
        return self::linkBulkPayment($db, $refType, $paymentId, $chargeIds);
    }

    /**
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    private static function linkBulkPayment(Database $db, string $refType, int $paymentId, array $chargeIds): int {
        if ($paymentId <= 0 || $chargeIds === []) {
            return 0;
        }

        $column = self::REF_TO_COLUMN[$refType] ?? null;
        if (!$column) {
            return 0;
        }

        require_once __DIR__ . '/ImportPayableAccrualService.php';

        $linked = 0;
        foreach ($chargeIds as $chargeId) {
            $chargeId = (int) $chargeId;
            if ($chargeId <= 0) {
                continue;
            }
            $db->execute(
                "UPDATE shipment_item_charges SET {$column} = ? WHERE id = ? AND {$column} IS NULL",
                [$paymentId, $chargeId]
            );
            ImportPayableAccrualService::markPaidByCharge($db, $refType, $chargeId, $paymentId);
            $linked++;
        }

        return $linked;
    }

    /**
     * Mark open partner accruals paid by an existing payment (no new cash movement).
     * Use when an old lump-sum payment (e.g. PAY-000567) was recorded before accrual linking existed.
     *
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    public static function linkExistingPartnerPayment(Database $db, int $paymentId, array $chargeIds): int {
        return self::linkExistingPayment($db, 'shipment_partner', $paymentId, $chargeIds);
    }

    /**
     * Mark open freight/packing accruals paid by an existing payment (no new cash movement).
     *
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    public static function linkExistingFreightPayment(Database $db, string $refType, int $paymentId, array $chargeIds): int {
        if (!isset(self::REF_TO_COLUMN[$refType]) || $refType === 'shipment_partner') {
            return 0;
        }
        return self::linkExistingPayment($db, $refType, $paymentId, $chargeIds);
    }

    /**
     * Force-link charge lines to an existing payment (overwrites NULL payment columns).
     *
     * @param list<int> $chargeIds shipment_item_charges.id values
     */
    public static function linkExistingPayment(Database $db, string $refType, int $paymentId, array $chargeIds): int {
        if ($paymentId <= 0 || $chargeIds === []) {
            return 0;
        }

        $column = self::REF_TO_COLUMN[$refType] ?? null;
        if (!$column) {
            return 0;
        }

        require_once __DIR__ . '/ImportPayableAccrualService.php';

        $linked = 0;
        foreach ($chargeIds as $chargeId) {
            $chargeId = (int) $chargeId;
            if ($chargeId <= 0) {
                continue;
            }
            $db->execute(
                "UPDATE shipment_item_charges SET {$column} = ? WHERE id = ?",
                [$paymentId, $chargeId]
            );
            ImportPayableAccrualService::markPaidByCharge($db, $refType, $chargeId, $paymentId);
            $linked++;
        }

        return $linked;
    }

    /** Undo a settlement payment — reopen accruals and clear all charge payment links. */
    public static function reopenPaymentLinks(Database $db, int $paymentId): int {
        if ($paymentId <= 0) {
            return 0;
        }

        $rows = $db->fetchAll(
            "SELECT id FROM import_payable_accruals
             WHERE payment_id = ? AND status = 'paid'",
            [$paymentId]
        );

        $db->execute(
            "UPDATE import_payable_accruals
             SET status = 'open', payment_id = NULL
             WHERE payment_id = ? AND status = 'paid'",
            [$paymentId]
        );

        foreach (self::REF_TO_COLUMN as $column) {
            $db->execute(
                "UPDATE shipment_item_charges SET {$column} = NULL WHERE {$column} = ?",
                [$paymentId]
            );
        }

        return count($rows);
    }

    /** @deprecated Use reopenPaymentLinks — partner-only reopen left for older call sites. */
    public static function reopenPartnerPayment(Database $db, int $paymentId): int {
        return self::reopenPaymentLinks($db, $paymentId);
    }
}
