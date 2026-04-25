-- ================================================================
-- IqbalERP Performance Indexes (idempotent / rerunnable)
-- Safe to run multiple times in phpMyAdmin.
-- ================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS add_index_if_missing $$
CREATE PROCEDURE add_index_if_missing(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128),
    IN p_columns VARCHAR(512)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*)
      INTO v_count
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = p_table
       AND index_name = p_index;

    IF v_count = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', p_index, ' ON ', p_table, ' (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- SALES
CALL add_index_if_missing('sales', 'idx_sales_party_status', 'party_id, status');
CALL add_index_if_missing('sales', 'idx_sales_date', 'date');
CALL add_index_if_missing('sales', 'idx_sales_warehouse', 'warehouse_id');
CALL add_index_if_missing('sales', 'idx_sales_created_at', 'created_at');

-- PAYMENTS
CALL add_index_if_missing('payments', 'idx_pay_party_reftype', 'party_id, ref_type');
CALL add_index_if_missing('payments', 'idx_pay_date', 'date');
CALL add_index_if_missing('payments', 'idx_pay_reftype_refid', 'ref_type, ref_id');
CALL add_index_if_missing('payments', 'idx_pay_account_type', 'account_id, payment_type');

-- RETURNS
CALL add_index_if_missing('returns', 'idx_ret_party_type_status', 'party_id, type, status');
CALL add_index_if_missing('returns', 'idx_ret_date', 'date');
CALL add_index_if_missing('returns', 'idx_ret_refid', 'ref_id');

-- PURCHASES
CALL add_index_if_missing('purchases', 'idx_pur_party_status', 'party_id, status');
CALL add_index_if_missing('purchases', 'idx_pur_date', 'date');
CALL add_index_if_missing('purchases', 'idx_pur_warehouse', 'warehouse_id');

-- SALE ITEMS
CALL add_index_if_missing('sale_items', 'idx_si_item', 'item_id');

-- PURCHASE ITEMS
CALL add_index_if_missing('purchase_items', 'idx_pi_item', 'item_id');

-- RETURN ITEMS
CALL add_index_if_missing('return_items', 'idx_ri_return', 'return_id');
CALL add_index_if_missing('return_items', 'idx_ri_item', 'item_id');

-- IMEI RECORDS
CALL add_index_if_missing('imei_records', 'idx_imei_sale', 'sale_id');
CALL add_index_if_missing('imei_records', 'idx_imei_purchase_item', 'purchase_id, item_id');
CALL add_index_if_missing('imei_records', 'idx_imei_status', 'status');
CALL add_index_if_missing('imei_records', 'idx_imei_warehouse', 'warehouse_id');

-- EXPENSES
CALL add_index_if_missing('expenses', 'idx_exp_date', 'date');
CALL add_index_if_missing('expenses', 'idx_exp_account', 'account_id');
CALL add_index_if_missing('expenses', 'idx_exp_warehouse', 'warehouse_id');

-- PARTIES
CALL add_index_if_missing('parties', 'idx_party_token', 'statement_token');
CALL add_index_if_missing('parties', 'idx_party_type_active', 'type, is_active');
CALL add_index_if_missing('parties', 'idx_party_warehouse', 'warehouse_id');
CALL add_index_if_missing('parties', 'idx_party_code', 'party_code');

-- ACTIVITY LOG
CALL add_index_if_missing('activity_log', 'idx_actlog_created', 'created_at');

-- STOCK TRANSFERS
CALL add_index_if_missing('stock_transfers', 'idx_st_created', 'created_at');

-- SERVICE RECORDS
CALL add_index_if_missing('service_records', 'idx_svc_tracking_token', 'tracking_token');
CALL add_index_if_missing('service_records', 'idx_svc_imei', 'imei');
CALL add_index_if_missing('service_records', 'idx_svc_wh_status', 'warehouse_id, status');
CALL add_index_if_missing('service_records', 'idx_svc_wh_stage', 'warehouse_id, device_stage');
CALL add_index_if_missing('service_records', 'idx_svc_party', 'party_id');

-- SERVICE HISTORY
CALL add_index_if_missing('service_history', 'idx_svchist_service', 'service_id');

-- Additional search-focused indexes (dashboard global search)
CALL add_index_if_missing('sales', 'idx_sales_wh_invoice', 'warehouse_id, invoice_no');
CALL add_index_if_missing('purchases', 'idx_purchases_wh_invoice', 'warehouse_id, invoice_no');
CALL add_index_if_missing('imei_records', 'idx_imei_wh_imei', 'warehouse_id, imei');
CALL add_index_if_missing('imei_records', 'idx_imei_wh_imei2', 'warehouse_id, imei2');
CALL add_index_if_missing('parties', 'idx_parties_name', 'name');
CALL add_index_if_missing('parties', 'idx_parties_phone', 'phone');

DROP PROCEDURE IF EXISTS add_index_if_missing;
