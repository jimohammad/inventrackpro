-- Add item category "HTC" for New Item dropdown (?page=items&action=create)
-- Idempotent: safe to run more than once.

INSERT INTO categories (name, parent_id, description)
SELECT 'HTC', NULL, 'HTC devices'
WHERE NOT EXISTS (
    SELECT 1 FROM categories WHERE name = 'HTC'
);
