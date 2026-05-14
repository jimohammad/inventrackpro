-- Listing + ledger performance indexes (sales, parties, purchases, payments, returns, expenses).
-- Apply on deployed DB after backup.
-- Uses CREATE INDEX IF NOT EXISTS (MySQL 8.0.29+, MariaDB 10.5.2+) so re-runs skip indexes that already exist.
-- Older MySQL: run each line manually and ignore "Duplicate key name" errors for any index already present.
-- Note: parties.warehouse_id is already indexed by the FK; no separate idx on warehouse_id.

CREATE INDEX IF NOT EXISTS idx_parties_active_type_name ON parties (is_active, type, name);

CREATE INDEX IF NOT EXISTS idx_purchases_wh_created ON purchases (warehouse_id, created_at);
CREATE INDEX IF NOT EXISTS idx_purchases_party_wh ON purchases (party_id, warehouse_id);
CREATE INDEX IF NOT EXISTS idx_purchases_party_status_date ON purchases (party_id, status, date);
CREATE INDEX IF NOT EXISTS idx_purchases_date_status ON purchases (date, status);

CREATE INDEX IF NOT EXISTS idx_sales_wh_created ON sales (warehouse_id, created_at);
CREATE INDEX IF NOT EXISTS idx_sales_party_wh ON sales (party_id, warehouse_id);
CREATE INDEX IF NOT EXISTS idx_sales_party_status_date ON sales (party_id, status, date);
CREATE INDEX IF NOT EXISTS idx_sales_date_status ON sales (date, status);

CREATE INDEX IF NOT EXISTS idx_payments_party_date ON payments (party_id, date);
CREATE INDEX IF NOT EXISTS idx_payments_party_reftype ON payments (party_id, ref_type);
CREATE INDEX IF NOT EXISTS idx_payments_party_wh ON payments (party_id, warehouse_id);
CREATE INDEX IF NOT EXISTS idx_payments_ref ON payments (ref_type, ref_id);

CREATE INDEX IF NOT EXISTS idx_returns_party_status_date ON `returns` (party_id, status, date);
CREATE INDEX IF NOT EXISTS idx_returns_party_wh ON `returns` (party_id, warehouse_id);
CREATE INDEX IF NOT EXISTS idx_returns_ref_type_status ON `returns` (ref_id, type, status);

CREATE INDEX IF NOT EXISTS idx_expenses_party_date ON expenses (party_id, date);
