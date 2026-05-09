-- Service module: allow new delivery-type statuses
-- This migration widens service_records.status to VARCHAR so we can store:
-- Fixed / Replaced / No Repair and their Delivered variants.
--
-- Run on deployed DB (Hostinger) via phpMyAdmin or mysql client.

START TRANSACTION;

-- If status is ENUM in your DB, this converts it to a flexible string column.
ALTER TABLE service_records
  MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending';

-- Optional: normalize legacy values (safe no-ops if values don't exist)
UPDATE service_records SET status = 'Fixed' WHERE status IN ('Completed');
UPDATE service_records SET status = 'No Repair & Delivered' WHERE status IN ('Returned (No Repair)');
UPDATE service_records SET status = 'Fixed & Delivered' WHERE status IN ('Delivered');

COMMIT;

