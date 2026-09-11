-- Mandoob inventory: store counted items when "Inventory done" is recorded.
-- Snapshot only — does not change warehouse stock or IMEI status.
-- Applied automatically by MandoobInventoryController::ensureSchema() on first page load.

ALTER TABLE mandoob_inventory_history
    ADD COLUMN line_count INT NOT NULL DEFAULT 0 AFTER notes,
    ADD COLUMN total_qty INT NOT NULL DEFAULT 0 AFTER line_count;

CREATE TABLE IF NOT EXISTS mandoob_inventory_count_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    history_id      INT NOT NULL,
    warehouse_id    INT NOT NULL,
    item_id         INT NOT NULL,
    item_name       VARCHAR(200) NOT NULL,
    sku             VARCHAR(80) DEFAULT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    imeis           TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mi_ci_hist FOREIGN KEY (history_id) REFERENCES mandoob_inventory_history(id) ON DELETE CASCADE,
    CONSTRAINT fk_mi_ci_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    CONSTRAINT fk_mi_ci_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    INDEX idx_mi_ci_hist (history_id),
    INDEX idx_mi_ci_wh (warehouse_id, history_id)
);
