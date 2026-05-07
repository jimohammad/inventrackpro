-- ────────────────────────────────────────────────────────────────────────────
-- ONE-TIME CLEANUP: undo HTML entities that the old input() helper saved.
--
-- Before this fix, BaseController::input() ran htmlspecialchars() on every
-- POST/GET value, so user-typed text was stored pre-escaped:
--     O'Brien   ->  O&#039;Brien
--     "Wamd CBK" ->  &quot;Wamd CBK&quot;
--     Q & A     ->  Q &amp; A
--     <span>    ->  &lt;span&gt;
--
-- After the fix, new rows are stored raw. This script un-escapes already-
-- saved rows so the display path (which now escapes on output) shows them
-- correctly.
--
-- HOW TO USE:
--   1. BACKUP the database first (phpMyAdmin -> Export -> Quick -> SQL).
--   2. Run section 1 (PREVIEW) to see how many rows would change.
--   3. If counts look right, run section 2 (APPLY) inside a transaction.
--   4. Spot-check key tables (parties, items) afterwards.
--
-- The order of REPLACE() matters: undo &amp; LAST so we don't double-decode
-- entities that legitimately contain '&'.
-- ────────────────────────────────────────────────────────────────────────────


-- ── Section 1. PREVIEW (read-only) ─────────────────────────────────────────
-- Counts rows per table that contain at least one HTML entity from old input().
SELECT 'parties.name'           AS field, COUNT(*) AS rows_with_entities
FROM parties WHERE name LIKE '%&#039;%' OR name LIKE '%&quot;%' OR name LIKE '%&amp;%' OR name LIKE '%&lt;%' OR name LIKE '%&gt;%'
UNION ALL
SELECT 'parties.contact_person',  COUNT(*) FROM parties
WHERE contact_person LIKE '%&#039;%' OR contact_person LIKE '%&quot;%' OR contact_person LIKE '%&amp;%' OR contact_person LIKE '%&lt;%' OR contact_person LIKE '%&gt;%'
UNION ALL
SELECT 'parties.address',         COUNT(*) FROM parties
WHERE address LIKE '%&#039;%' OR address LIKE '%&quot;%' OR address LIKE '%&amp;%' OR address LIKE '%&lt;%' OR address LIKE '%&gt;%'
UNION ALL
SELECT 'parties.notes',           COUNT(*) FROM parties
WHERE notes LIKE '%&#039;%' OR notes LIKE '%&quot;%' OR notes LIKE '%&amp;%' OR notes LIKE '%&lt;%' OR notes LIKE '%&gt;%'
UNION ALL
SELECT 'items.name',              COUNT(*) FROM items
WHERE name LIKE '%&#039;%' OR name LIKE '%&quot;%' OR name LIKE '%&amp;%' OR name LIKE '%&lt;%' OR name LIKE '%&gt;%'
UNION ALL
SELECT 'items.description',       COUNT(*) FROM items
WHERE description LIKE '%&#039;%' OR description LIKE '%&quot;%' OR description LIKE '%&amp;%' OR description LIKE '%&lt;%' OR description LIKE '%&gt;%'
UNION ALL
SELECT 'sales.notes',             COUNT(*) FROM sales
WHERE notes LIKE '%&#039;%' OR notes LIKE '%&quot;%' OR notes LIKE '%&amp;%' OR notes LIKE '%&lt;%' OR notes LIKE '%&gt;%'
UNION ALL
SELECT 'purchases.notes',         COUNT(*) FROM purchases
WHERE notes LIKE '%&#039;%' OR notes LIKE '%&quot;%' OR notes LIKE '%&amp;%' OR notes LIKE '%&lt;%' OR notes LIKE '%&gt;%'
UNION ALL
SELECT 'payments.notes',          COUNT(*) FROM payments
WHERE notes LIKE '%&#039;%' OR notes LIKE '%&quot;%' OR notes LIKE '%&amp;%' OR notes LIKE '%&lt;%' OR notes LIKE '%&gt;%'
UNION ALL
SELECT 'returns.reason',          COUNT(*) FROM `returns`
WHERE reason LIKE '%&#039;%' OR reason LIKE '%&quot;%' OR reason LIKE '%&amp;%' OR reason LIKE '%&lt;%' OR reason LIKE '%&gt;%'
UNION ALL
SELECT 'expenses.description',    COUNT(*) FROM expenses
WHERE description LIKE '%&#039;%' OR description LIKE '%&quot;%' OR description LIKE '%&amp;%' OR description LIKE '%&lt;%' OR description LIKE '%&gt;%';


-- ── Section 2. APPLY (uncomment to run inside a transaction) ──────────────
-- Order matters: do &amp; LAST so we don't accidentally double-decode.
-- START TRANSACTION;
--
-- UPDATE parties SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE parties SET contact_person = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(contact_person,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE parties SET address = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(address,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE parties SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE parties SET city = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(city,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE parties SET email = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(email,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE items SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE items SET description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE items SET sku = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sku,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE items SET brand = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(brand,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE items SET model = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(model,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE sales SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE purchases SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE payments SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE `returns` SET reason = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(reason,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE `returns` SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
-- UPDATE expenses SET description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description,
--   '&#039;', ''''), '&quot;', '"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&');
--
-- -- After verifying spot-checks look correct:
-- COMMIT;
-- -- Otherwise:
-- -- ROLLBACK;
