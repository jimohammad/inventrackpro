<?php

/**
 * Force a party’s branch ledger net to 0 via opening_balance offset.
 * Uses Party Statement debit/credit rules (computeBalanceAsOf) so Reports → Party Statement closes Clear.
 */
class PartyLedgerZeroService {

    /**
     * @return array{
     *   ok: bool,
     *   party_id: int,
     *   party_name: string,
     *   net_before: float,
     *   net_after: float,
     *   opening_before: float,
     *   opening_after: float,
     *   warehouse_cleared: bool,
     *   message: string
     * }
     */
    public static function forceNetToZero(
        Database $db,
        Party $partyModel,
        int $partyId,
        int $warehouseId,
        string $auditNote = ''
    ): array {
        $party = $db->fetchOne(
            "SELECT id, name, opening_balance, warehouse_id, notes FROM parties WHERE id = ?",
            [$partyId]
        );
        if (!$party) {
            return [
                'ok'                => false,
                'party_id'          => $partyId,
                'party_name'        => '',
                'net_before'        => 0.0,
                'net_after'         => 0.0,
                'opening_before'    => 0.0,
                'opening_after'     => 0.0,
                'warehouse_cleared' => false,
                'message'           => 'Party not found.',
            ];
        }

        $name = (string) ($party['name'] ?? '');
        $openingBefore = round((float) ($party['opening_balance'] ?? 0), 3);
        $warehouseCleared = false;

        // Opening only counts when party.warehouse_id is NULL or equals the active branch.
        // Do NOT silently clear warehouse_id — that would leak opening across branches.
        $partyWh = $party['warehouse_id'] ?? null;
        if ($partyWh !== null && (int) $partyWh !== $warehouseId) {
            return [
                'ok'                => false,
                'party_id'          => $partyId,
                'party_name'        => $name,
                'net_before'        => 0.0,
                'net_after'         => 0.0,
                'opening_before'    => $openingBefore,
                'opening_after'     => $openingBefore,
                'warehouse_cleared' => false,
                'message'           => $name . ' is assigned to another branch (warehouse_id='
                    . (int) $partyWh . '). Fix party branch first — cannot force-zero from warehouse '
                    . $warehouseId . '.',
            ];
        }

        Party::clearBalanceListCache();

        // Statement rules (debit − credit), not only Party Master UNION — keeps Party Statement Clear.
        $asOf = date('Y-m-d');
        $netBefore = round($partyModel->computeBalanceAsOf($partyId, $asOf, $warehouseId), 3);

        if (abs($netBefore) <= 0.001) {
            return [
                'ok'                => true,
                'party_id'          => $partyId,
                'party_name'        => $name,
                'net_before'        => $netBefore,
                'net_after'         => $netBefore,
                'opening_before'    => $openingBefore,
                'opening_after'     => $openingBefore,
                'warehouse_cleared' => $warehouseCleared,
                'message'           => $name . ' statement already nets to 0.',
            ];
        }

        $fresh = $db->fetchOne(
            "SELECT opening_balance, warehouse_id FROM parties WHERE id = ?",
            [$partyId]
        );
        $openingAfter = round((float) ($fresh['opening_balance'] ?? 0) - $netBefore, 3);
        $noteLine = sprintf(
            '[%s] Statement force-zero: opening %s → %s (offset statement net %s)%s',
            date('Y-m-d H:i'),
            number_format($openingBefore, DECIMAL_PLACES),
            number_format($openingAfter, DECIMAL_PLACES),
            number_format($netBefore, DECIMAL_PLACES),
            $auditNote !== '' ? ' — ' . $auditNote : ''
        );

        $db->execute(
            "UPDATE parties
             SET opening_balance = ?,
                 notes = TRIM(CONCAT(COALESCE(notes, ''), CASE WHEN COALESCE(notes, '') = '' THEN '' ELSE '\n' END, ?))
             WHERE id = ?",
            [$openingAfter, $noteLine, $partyId]
        );

        Party::clearBalanceListCache();
        $netAfter = round($partyModel->computeBalanceAsOf($partyId, $asOf, $warehouseId), 3);
        $ok = abs($netAfter) <= 0.001;

        return [
            'ok'                => $ok,
            'party_id'          => $partyId,
            'party_name'        => $name,
            'net_before'        => $netBefore,
            'net_after'         => $netAfter,
            'opening_before'    => $openingBefore,
            'opening_after'     => $openingAfter,
            'warehouse_cleared' => $warehouseCleared,
            'message'           => $ok
                ? sprintf(
                    '%s statement set to Clear: opening %s → %s (was %s). Refresh this report.',
                    $name,
                    number_format($openingBefore, DECIMAL_PLACES),
                    number_format($openingAfter, DECIMAL_PLACES),
                    number_format($netBefore, DECIMAL_PLACES)
                )
                : sprintf(
                    '%s still shows statement net %s after opening adjust.',
                    $name,
                    number_format($netAfter, DECIMAL_PLACES)
                ),
        ];
    }
}
