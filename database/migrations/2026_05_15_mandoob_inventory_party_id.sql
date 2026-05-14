-- Link Mandoob Inventory rows to Party Master (customer). Run after 2026_05_14 migration.
-- mysql -u ... -p DBNAME < database/migrations/2026_05_15_mandoob_inventory_party_id.sql

ALTER TABLE mandoob_inventory_schedules
    ADD COLUMN party_id INT DEFAULT NULL AFTER warehouse_id,
    ADD FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL,
    ADD UNIQUE KEY uniq_mandoob_wh_party (warehouse_id, party_id);
