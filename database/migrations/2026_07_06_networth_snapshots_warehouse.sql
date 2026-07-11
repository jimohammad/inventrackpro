-- Branch-scoped net worth snapshots (run once on deployed DB before using Capture on balance sheet).
-- Safe to re-run: skips if warehouse_id already exists.

SET @has_wh := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'net_worth_snapshots'
      AND COLUMN_NAME = 'warehouse_id'
);

SET @sql := IF(
    @has_wh = 0,
    'ALTER TABLE net_worth_snapshots
        ADD COLUMN warehouse_id INT NOT NULL DEFAULT 1 AFTER snapshot_date,
        DROP INDEX uniq_snapshot_date,
        ADD UNIQUE KEY uniq_snapshot_date_wh (snapshot_date, warehouse_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

