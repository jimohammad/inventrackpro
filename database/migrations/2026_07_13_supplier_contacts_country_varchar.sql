-- Supplier contacts: allow India / UAE country values
-- Run once on production if contacts saved with India/UAE are missing from the list.
-- Safe to re-run.

ALTER TABLE supplier_contacts
  MODIFY COLUMN country VARCHAR(50) NOT NULL DEFAULT 'UAE';

UPDATE supplier_contacts
SET country = 'UAE'
WHERE country = 'Dubai';

UPDATE supplier_contacts
SET country = 'Other'
WHERE country = '' OR country IS NULL;

UPDATE supplier_contacts
SET is_active = 1
WHERE is_active IS NULL;
