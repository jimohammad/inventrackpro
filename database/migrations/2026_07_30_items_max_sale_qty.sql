-- Max quantity a cashier/viewer may sell of this item on one invoice (0 = unlimited).
-- Run on deployed DB after verifying column does not exist:
--   SHOW COLUMNS FROM items LIKE 'max_sale_qty';

ALTER TABLE items
    ADD COLUMN max_sale_qty INT NOT NULL DEFAULT 0 AFTER min_stock;
