-- Link customer discounts to a sale invoice (optional).
-- Run once on production after verifying column is absent:
--   SHOW COLUMNS FROM customer_discounts LIKE 'sale_id';

ALTER TABLE customer_discounts
  ADD COLUMN sale_id INT NULL AFTER item_id;
