-- Repair sale returns posted to the wrong customer (duplicate party / wrong picker).
-- Run on the live DB after backup. Re-links party_id from the original invoice.
-- Safe when ref_id points at sales.id; skips if already correct.

UPDATE returns r
INNER JOIN sales s ON s.id = r.ref_id AND r.type = 'sale_return'
SET r.party_id = s.party_id
WHERE r.party_id <> s.party_id;
