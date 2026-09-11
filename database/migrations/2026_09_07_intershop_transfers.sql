-- Shop-to-shop stock transfer (stock only, no invoices).
-- Hostinger: tables are also created on first use by IntershopTransfer::ensureSchema().

CREATE TABLE IF NOT EXISTS intershop_transfers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    transfer_no      VARCHAR(32) NOT NULL,
    direction        ENUM('outbound','inbound') NOT NULL,
    peer_name        VARCHAR(120) NOT NULL DEFAULT '',
    peer_ref         VARCHAR(32) NULL,
    idempotency_key  VARCHAR(64) NOT NULL,
    warehouse_id     INT NOT NULL,
    date             DATE NOT NULL,
    status           VARCHAR(24) NOT NULL DEFAULT 'pending',
    notes            VARCHAR(500) NULL,
    last_error       VARCHAR(500) NULL,
    created_by       INT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_transfer_no (transfer_no),
    UNIQUE KEY uniq_idem (idempotency_key),
    INDEX idx_ist_wh_date (warehouse_id, date),
    INDEX idx_ist_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intershop_transfer_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    transfer_id   INT NOT NULL,
    item_id       INT NOT NULL,
    sku           VARCHAR(80) NOT NULL,
    item_name     VARCHAR(200) NOT NULL DEFAULT '',
    quantity      INT NOT NULL,
    INDEX idx_isti_transfer (transfer_id),
    INDEX idx_isti_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intershop_transfer_imei (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    transfer_item_id   INT NOT NULL,
    imei               VARCHAR(32) NOT NULL,
    imei_id            INT NULL,
    INDEX idx_istm_item (transfer_item_id),
    INDEX idx_istm_imei (imei)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
