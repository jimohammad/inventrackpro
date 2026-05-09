-- Optional: align deployed DB with app/views + AccountController INSERT (accounts.gl_code).
-- Validate on production before running — skip if column already exists.
ALTER TABLE accounts
ADD COLUMN gl_code VARCHAR(10) DEFAULT NULL AFTER type;
