-- Bank / rounding adjustment on purchase orders (signed KWD).
-- Lets staff raise or lower PO total to match the exact bank amount deducted.
-- Run on production after deploy.

ALTER TABLE purchase_orders
    ADD COLUMN adjustment_kwd DECIMAL(15,3) NOT NULL DEFAULT 0.000
    AFTER other_charges_kwd;
