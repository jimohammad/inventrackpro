-- ────────────────────────────────────────────────────────────────────────────
-- HEAL: imei_records that show "sold to <party>" even though the sale line
-- was already removed from the invoice (or the sale was cancelled).
--
-- Cause:
--   Older Sales edit ("Edit invoice" → delete row) deleted the sale_items
--   row (CASCADE wiped sale_item_imei) but never updated imei_records.
--   imei_records.status stayed 'sold' and imei_records.sale_id kept pointing
--   at the now-empty invoice. The fix in app/controllers/SalesController.php
--   stops new occurrences; this script fixes the existing rows.
--
-- HOW TO USE:
--   1. Take a database backup first.
--   2. Run section 1 to PREVIEW (read-only) — shows every IMEI that will heal.
--   3. If the preview is correct, run section 2 (UPDATE) inside a transaction.
--
-- This is safe: it only touches IMEIs that have NO surviving link in
-- sale_item_imei to any non-cancelled sale.
-- ────────────────────────────────────────────────────────────────────────────


-- ── Section 1. PREVIEW ─────────────────────────────────────────────────────
-- Lists IMEIs whose status='sold' but no real (non-cancelled) sale line links to them.
-- These are the rows that section 2 will reset to in_stock + sale_id = NULL.
SELECT
    ir.id              AS imei_record_id,
    ir.imei,
    ir.status          AS current_status,
    ir.sale_id         AS stale_sale_id,
    s.invoice_no       AS stale_invoice_no,
    s.status           AS stale_invoice_status,
    pa.name            AS stale_customer
FROM imei_records ir
LEFT JOIN sales s   ON s.id  = ir.sale_id
LEFT JOIN parties pa ON pa.id = s.party_id
WHERE ir.status = 'sold'
  AND NOT EXISTS (
      SELECT 1
      FROM sale_item_imei sii
      JOIN sale_items si ON si.id = sii.sale_item_id
      JOIN sales s2      ON s2.id = si.sale_id
      WHERE sii.imei_id = ir.id
        AND s2.status <> 'cancelled'
  )
ORDER BY ir.id;


-- ── Section 2. APPLY (uncomment to run) ────────────────────────────────────
-- START TRANSACTION;
--
-- UPDATE imei_records ir
-- LEFT JOIN (
--     SELECT DISTINCT sii.imei_id
--     FROM sale_item_imei sii
--     JOIN sale_items si ON si.id = sii.sale_item_id
--     JOIN sales s2      ON s2.id = si.sale_id
--     WHERE s2.status <> 'cancelled'
-- ) live ON live.imei_id = ir.id
-- SET ir.status  = 'in_stock',
--     ir.sale_id = NULL
-- WHERE ir.status = 'sold'
--   AND live.imei_id IS NULL;
--
-- -- Confirm the count looks right; if yes:
-- COMMIT;
-- -- Otherwise:
-- -- ROLLBACK;
