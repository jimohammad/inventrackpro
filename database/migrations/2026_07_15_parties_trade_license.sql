-- Supplier trade license file + expiry on party master.
-- Run once on production after verifying columns are absent:
--   SHOW COLUMNS FROM parties LIKE 'trade_license%';

ALTER TABLE parties
  ADD COLUMN trade_license_file VARCHAR(255) NULL AFTER id_card,
  ADD COLUMN trade_license_expires_on DATE NULL AFTER trade_license_file;

CREATE INDEX idx_parties_trade_license_expiry
  ON parties (trade_license_expires_on, type, is_active);
