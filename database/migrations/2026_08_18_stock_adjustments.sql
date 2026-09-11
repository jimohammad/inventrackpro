-- Durable stock qty write-off (physically missing units vs scanned IMEIs).
-- Also auto-created by StockQuantityService::ensureAdjustmentsSchema() on IMEI Audit.

CREATE TABLE IF NOT EXISTS stock_adjustments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id    INT NOT NULL,
    item_id         INT NOT NULL,
    quantity_delta  INT NOT NULL,
    reason          VARCHAR(64) NOT NULL DEFAULT 'imei_pending_writeoff',
    notes           VARCHAR(500) NULL,
    created_by      INT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sa_item_wh (item_id, warehouse_id),
    INDEX idx_sa_wh (warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
