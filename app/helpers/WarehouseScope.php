<?php

/**
 * Enforces branch isolation: Main Branch and Fahaheel Branch are separate entities.
 * All logged-in UI and reports must scope data to Auth::warehouseId().
 */
final class WarehouseScope {

    public static function currentId(): int {
        return (int) (Auth::warehouseId() ?? 0);
    }

    public static function requireCurrentId(): int {
        $id = self::currentId();
        if ($id <= 0) {
            throw new RuntimeException('No warehouse selected in session.');
        }
        return $id;
    }

    /**
     * SQL fragment for equality filter on warehouse_id (or aliased column).
     *
     * @return array{0:string,1:list<int>}
     */
    public static function sqlAnd(string $column = 'warehouse_id'): array {
        $id = self::currentId();
        if ($id <= 0) {
            return ['', []];
        }
        return [' AND ' . $column . ' = ?', [$id]];
    }

    /** Opening balance applies only when party is unassigned or home branch matches. */
    public static function openingBalanceForParty(array $party, int $warehouseId): float {
        $opening = (float) ($party['opening_balance'] ?? 0);
        if ($warehouseId <= 0) {
            return $opening;
        }
        $partyWh = $party['warehouse_id'] ?? null;
        if ($partyWh === null || (int) $partyWh === $warehouseId) {
            return $opening;
        }
        return 0.0;
    }
}
