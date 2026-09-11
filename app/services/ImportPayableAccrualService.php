<?php

/**
 * Creates open payables (vendor-bill style) when import shipments are received.
 * Cleared when Payments links via LandedCostPaymentLinker.
 */
class ImportPayableAccrualService {

    private const LEG_LABELS = [
        'freight_hk'  => 'Freight HK→DXB',
        'packing_dxb' => 'Packing in DXB',
        'freight_dxb' => 'Freight DXB→Kuwait',
        'partner'     => 'Partner profit',
    ];

    private const REF_TYPE_TO_LEG = [
        'shipment_freight_hk'   => 'freight_hk',
        'shipment_packing_dxb'  => 'packing_dxb',
        'shipment_freight_dxb'  => 'freight_dxb',
        'shipment_partner'      => 'partner',
    ];

    public static function createFromShipment(Database $db, int $shipmentId, string $date, ?int $warehouseId): int {
        $shipment = $db->fetchOne("SELECT shipment_no, warehouse_id FROM shipments WHERE id = ?", [$shipmentId]);
        if (!$shipment) {
            return 0;
        }
        $wh  = $warehouseId ?: (int) ($shipment['warehouse_id'] ?? 0) ?: null;
        $shp = (string) $shipment['shipment_no'];

        $charges = $db->fetchAll(
            "SELECT * FROM shipment_item_charges WHERE shipment_id = ? AND is_applied = 1",
            [$shipmentId]
        );

        $created = 0;
        foreach ($charges as $ch) {
            $chargeId = (int) $ch['id'];
            $hkAmt    = (float) $ch['freight_hk_dxb'];
            $hkParty  = (int) ($ch['freight_hk_dxb_party_id'] ?? 0);
            if ($hkAmt > 0.001 && $hkParty > 0 && empty($ch['freight_hk_payment_id'])) {
                if (self::insertAccrual($db, $shipmentId, $chargeId, 'freight_hk', $hkParty, $hkAmt, $date, $wh, "Freight HK→DXB — {$shp}")) {
                    $created++;
                }
            }

            $packAmt   = (float) ($ch['packing_dxb'] ?? 0);
            $packParty = (int) ($ch['packing_dxb_party_id'] ?? 0);
            if ($packAmt > 0.001 && $packParty > 0 && empty($ch['packing_dxb_payment_id'])) {
                if (self::insertAccrual($db, $shipmentId, $chargeId, 'packing_dxb', $packParty, $packAmt, $date, $wh, "Packing in DXB — {$shp}")) {
                    $created++;
                }
            }

            $dxbAmt   = (float) $ch['freight_dxb_kwt'];
            $dxbParty = (int) ($ch['freight_dxb_kwt_party_id'] ?? 0);
            if ($dxbAmt > 0.001 && $dxbParty > 0 && empty($ch['freight_dxb_payment_id'])) {
                if (self::insertAccrual($db, $shipmentId, $chargeId, 'freight_dxb', $dxbParty, $dxbAmt, $date, $wh, "Freight DXB→KW — {$shp}")) {
                    $created++;
                }
            }

            $ptPc    = (float) $ch['partner_profit_per_pc'];
            $ptParty = (int) ($ch['partner_party_id'] ?? 0);
            $ptAmt   = round($ptPc * max(1, (int) $ch['quantity']), 3);
            if ($ptAmt > 0.001 && $ptParty > 0 && empty($ch['partner_payment_id'])) {
                if (self::insertAccrual($db, $shipmentId, $chargeId, 'partner', $ptParty, $ptAmt, $date, $wh, "Partner profit — {$shp}")) {
                    $created++;
                }
            }
        }

        return $created;
    }

    public static function markPaidByCharge(Database $db, string $refType, int $chargeId, int $paymentId): void {
        if ($chargeId <= 0 || $paymentId <= 0) {
            return;
        }
        $leg = self::REF_TYPE_TO_LEG[$refType] ?? null;
        if (!$leg) {
            return;
        }
        $db->execute(
            "UPDATE import_payable_accruals
             SET status = 'paid', payment_id = ?
             WHERE shipment_item_charge_id = ? AND leg = ? AND status = 'open'",
            [$paymentId, $chargeId, $leg]
        );
    }

    /**
     * Write off open accruals (no cash movement) — removes liability from freight payables + party ledger.
     * Use only when there is no matching outbound PAY left on the books. If PAYs exist, link them
     * (`LandedCostPaymentLinker`) so status becomes `paid` — cancelling while payments remain
     * leaves a false Party Master credit (payments count; cancelled accruals do not).
     *
     * @param list<int> $chargeIds
     */
    public static function cancelOpenByCharges(Database $db, string $refType, array $chargeIds, string $reason = ''): int {
        $leg = self::REF_TYPE_TO_LEG[$refType] ?? null;
        if (!$leg || $chargeIds === []) {
            return 0;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $chargeIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return 0;
        }

        $note = trim($reason);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        if ($note !== '') {
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'cancelled',
                     description = CONCAT(COALESCE(description, ''), CASE WHEN description IS NULL OR description = '' THEN '' ELSE ' · ' END, ?)
                 WHERE shipment_item_charge_id IN ({$ph}) AND leg = ? AND status = 'open'",
                array_merge([$note], $ids, [$leg])
            );
        } else {
            $db->execute(
                "UPDATE import_payable_accruals
                 SET status = 'cancelled'
                 WHERE shipment_item_charge_id IN ({$ph}) AND leg = ? AND status = 'open'",
                array_merge($ids, [$leg])
            );
        }

        $left = $db->fetchOne(
            "SELECT COUNT(*) AS c FROM import_payable_accruals
             WHERE shipment_item_charge_id IN ({$ph}) AND leg = ? AND status = 'open'",
            array_merge($ids, [$leg])
        );

        return count($ids) - (int) ($left['c'] ?? 0);
    }

    public static function legLabel(string $leg): string {
        return self::LEG_LABELS[$leg] ?? $leg;
    }

    public static function refTypeForLeg(string $leg): string {
        foreach (self::REF_TYPE_TO_LEG as $ref => $l) {
            if ($l === $leg) {
                return $ref;
            }
        }
        return '';
    }

    private static function insertAccrual(
        Database $db,
        int $shipmentId,
        int $chargeId,
        string $leg,
        int $partyId,
        float $amount,
        string $date,
        ?int $warehouseId,
        string $description
    ): bool {
        $exists = $db->fetchOne(
            "SELECT id FROM import_payable_accruals WHERE shipment_item_charge_id = ? AND leg = ?",
            [$chargeId, $leg]
        );
        if ($exists) {
            return false;
        }

        $suffix = match ($leg) {
            'freight_hk'  => 'HK',
            'packing_dxb' => 'PKG',
            'freight_dxb' => 'DXB',
            'partner'     => 'PT',
            default       => strtoupper(substr($leg, 0, 3)),
        };
        $accrualNo = 'IPA-' . $chargeId . '-' . $suffix;

        $db->insert(
            "INSERT INTO import_payable_accruals
                (accrual_no, shipment_id, shipment_item_charge_id, leg, party_id, amount, date, warehouse_id, status, description)
             VALUES (?,?,?,?,?,?,?,?,'open',?)",
            [
                $accrualNo,
                $shipmentId,
                $chargeId,
                $leg,
                $partyId,
                round($amount, 3),
                $date,
                $warehouseId,
                $description,
            ]
        );
        return true;
    }
}
