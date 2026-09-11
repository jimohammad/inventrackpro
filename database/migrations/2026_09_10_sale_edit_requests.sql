-- Salesman invoice-edit unlock requests (admin approve → 30 min or first invoice Save).
-- Hostinger: table is also created on first use via SaleEditRequest::ensureTable.

CREATE TABLE IF NOT EXISTS sale_edit_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id         INT UNSIGNED NOT NULL,
    warehouse_id    INT UNSIGNED NOT NULL,
    requested_by    INT UNSIGNED NOT NULL,
    reason          VARCHAR(500) NOT NULL,
    status          ENUM('pending','approved','rejected','used','expired') NOT NULL DEFAULT 'pending',
    staff_note      VARCHAR(500) NULL,
    reviewed_by     INT UNSIGNED NULL,
    reviewed_at     DATETIME NULL,
    unlocked_until  DATETIME NULL,
    used_at         DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ser_sale_status (sale_id, status),
    KEY idx_ser_wh_status (warehouse_id, status),
    KEY idx_ser_unlock (requested_by, status, unlocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
