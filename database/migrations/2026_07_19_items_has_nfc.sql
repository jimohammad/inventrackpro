-- NFC availability flag for item master / public pricelist badge
-- Run on deployed DB after verifying column does not exist:
--   SHOW COLUMNS FROM items LIKE 'has_nfc';

ALTER TABLE items
    ADD COLUMN has_nfc TINYINT(1) NOT NULL DEFAULT 0;
