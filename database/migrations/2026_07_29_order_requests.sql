-- Customer order requests from iqbal.app/apps (prior confirmation before sale)
-- Run on production if ensureTable has not already created this.

CREATE TABLE IF NOT EXISTS order_requests (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_no           VARCHAR(32)  NOT NULL,
    public_token         VARCHAR(64)  NOT NULL,
    customer_name        VARCHAR(150) NOT NULL,
    customer_phone       VARCHAR(40)  NOT NULL,
    items_text           TEXT         NOT NULL,
    customer_notes       TEXT         NULL,
    status               ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    staff_note           TEXT         NULL,
    warehouse_id         INT UNSIGNED NOT NULL DEFAULT 1,
    reviewed_by          INT UNSIGNED NULL,
    reviewed_at          DATETIME     NULL,
    customer_notified_at DATETIME     NULL,
    ip_address           VARCHAR(45)  NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_order_requests_token (public_token),
    UNIQUE KEY uk_order_requests_no (request_no),
    KEY idx_order_requests_status_created (status, created_at),
    KEY idx_order_requests_wh_status (warehouse_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
