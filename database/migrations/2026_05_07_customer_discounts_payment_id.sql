-- Link customer_discounts to the exact payment row to avoid fuzzy deletes/updates.
-- Apply on deployed DB.

ALTER TABLE customer_discounts
  ADD COLUMN payment_id INT NULL,
  ADD KEY idx_payment_id (payment_id);

