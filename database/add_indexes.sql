-- ================================================================
-- IqbalERP Performance Indexes
-- Run in phpMyAdmin → SQL tab
-- Uses DROP IF EXISTS + CREATE to avoid duplicate errors
-- ================================================================

-- SALES
CREATE INDEX idx_sales_party_status ON sales (party_id, status);
CREATE INDEX idx_sales_date ON sales (date);
CREATE INDEX idx_sales_warehouse ON sales (warehouse_id);
CREATE INDEX idx_sales_created_at ON sales (created_at);

-- PAYMENTS
CREATE INDEX idx_pay_party_reftype ON payments (party_id, ref_type);
CREATE INDEX idx_pay_date ON payments (date);
CREATE INDEX idx_pay_reftype_refid ON payments (ref_type, ref_id);
CREATE INDEX idx_pay_account_type ON payments (account_id, payment_type);

-- RETURNS
CREATE INDEX idx_ret_party_type_status ON returns (party_id, type, status);
CREATE INDEX idx_ret_date ON returns (date);
CREATE INDEX idx_ret_refid ON returns (ref_id);

-- PURCHASES
CREATE INDEX idx_pur_party_status ON purchases (party_id, status);
CREATE INDEX idx_pur_date ON purchases (date);
CREATE INDEX idx_pur_warehouse ON purchases (warehouse_id);

-- SALE ITEMS
CREATE INDEX idx_si_item ON sale_items (item_id);

-- PURCHASE ITEMS
CREATE INDEX idx_pi_item ON purchase_items (item_id);

-- RETURN ITEMS
CREATE INDEX idx_ri_return ON return_items (return_id);
CREATE INDEX idx_ri_item ON return_items (item_id);

-- IMEI RECORDS
CREATE INDEX idx_imei_sale ON imei_records (sale_id);
CREATE INDEX idx_imei_purchase_item ON imei_records (purchase_id, item_id);
CREATE INDEX idx_imei_status ON imei_records (status);
CREATE INDEX idx_imei_warehouse ON imei_records (warehouse_id);

-- EXPENSES
CREATE INDEX idx_exp_date ON expenses (date);
CREATE INDEX idx_exp_account ON expenses (account_id);
CREATE INDEX idx_exp_warehouse ON expenses (warehouse_id);

-- PARTIES
CREATE INDEX idx_party_token ON parties (statement_token);
CREATE INDEX idx_party_type_active ON parties (type, is_active);
CREATE INDEX idx_party_warehouse ON parties (warehouse_id);
CREATE INDEX idx_party_code ON parties (party_code);

-- ACTIVITY LOG
CREATE INDEX idx_actlog_created ON activity_log (created_at);

-- STOCK TRANSFERS
CREATE INDEX idx_st_created ON stock_transfers (created_at);
