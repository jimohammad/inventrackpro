-- Dump Credit needs IMEI status `dumped`. Live DBs often still have the original ENUM
-- without that value; DeviceDump::ensureSchema() applies the same ALTER on first dump page load.
ALTER TABLE imei_records
    MODIFY COLUMN status ENUM('in_stock', 'sold', 'returned', 'transferred', 'defective', 'dumped')
    DEFAULT 'in_stock';
