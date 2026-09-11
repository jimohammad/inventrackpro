-- Strict sale rule: IMEI-tracked items must always be scanned at sale.
-- Clear leftover items.imei_optional flags (column kept for schema compatibility;
-- sale paths ignore it; item master no longer exposes the toggle).

UPDATE items SET imei_optional = 0 WHERE imei_optional <> 0;
