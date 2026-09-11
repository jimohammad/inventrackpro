-- Arabic item name for bilingual sale receipts (MOCI)
-- Run on deployed DB after verifying column does not exist:
--   SHOW COLUMNS FROM items LIKE 'name_ar';

ALTER TABLE items
    ADD COLUMN name_ar VARCHAR(255) NULL DEFAULT NULL AFTER name;
