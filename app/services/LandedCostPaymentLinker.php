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
        if ($paymentId <= 0 || $chargeIds === []) {
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
                'UPDATE shipment_item_charges SET partner_payment_id = ? WHERE id = ? AND partner_payment_id IS NULL',
                [$paymentId, $chargeId]
            );
            ImportPayableAccrualService::markPaidByCharge($db, 'shipment_partner', $chargeId, $paymentId);
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
        if ($paymentId <= 0 || $chargeIds === []) {
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
                'UPDATE shipment_item_charges SET partner_payment_id = ? WHERE id = ?',
                [$paymentId, $chargeId]
            );
            ImportPayableAccrualService::markPaidByCharge($db, 'shipment_partner', $chargeId, $paymentId);
            $linked++;
        }

        return $linked;
    }

    /** Undo a partner settlement — reopen accruals and clear charge payment links. */
    public static function reopenPartnerPayment(Database $db, int $paymentId): int {
        if ($paymentId <= 0) {
            return 0;
        }

        $rows = $db->fetchAll(
            "SELECT id FROM import_payable_accruals
             WHERE payment_id = ? AND leg = 'partner' AND status = 'paid'",
            [$paymentId]
        );
        if ($rows === []) {
            return 0;
        }

        $db->execute(
            "UPDATE import_payable_accruals
             SET status = 'open', payment_id = NULL
             WHERE payment_id = ? AND leg = 'partner' AND status = 'paid'",
            [$paymentId]
        );
        $db->execute(
            'UPDATE shipment_item_charges SET partner_payment_id = NULL WHERE partner_payment_id = ?',
            [$paymentId]
        );

        return count($rows);
    }
}
