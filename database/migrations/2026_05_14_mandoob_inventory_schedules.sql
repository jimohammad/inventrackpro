-- Mandoob Inventory: physical van stock count reminders (~3 months per mandoob).
-- Run on deployed DB after review: mysql -u ... -p DBNAME < database/migrations/2026_05_14_mandoob_inventory_schedules.sql

CREATE TABLE IF NOT EXISTS mandoob_inventory_schedules (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id     INT NOT NULL,
    party_id         INT DEFAULT NULL,
    name             VARCHAR(255) NOT NULL,
    phone            VARCHAR(40) DEFAULT NULL,
    interval_months  TINYINT UNSIGNED NOT NULL DEFAULT 3,
    last_count_date  DATE DEFAULT NULL,
    next_due_date    DATE DEFAULT NULL,
    notes            TEXT,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_by       INT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_mandoob_wh_party (warehouse_id, party_id),
    INDEX idx_mandoob_wh_due (warehouse_id, next_due_date),
    INDEX idx_mandoob_wh_active (warehouse_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional (once): give every user who can view Inventory the same access to Mandoob Inventory:
-- INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete)
-- SELECT user_id, 'mandoob_inventory', can_view, can_add, can_edit, can_delete
-- FROM permissions WHERE module = 'inventory'
-- ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_add=VALUES(can_add), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete);
