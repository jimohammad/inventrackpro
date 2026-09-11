-- Persistent manual balance corrections (Adjust Balance).
-- Included in AccountController::recalcBalance() so Recalculate preserves them.
-- Apply on deployed DB before relying on the feature.

CREATE TABLE IF NOT EXISTS account_balance_adjustments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    account_id      INT NOT NULL,
    direction       ENUM('add', 'subtract') NOT NULL COMMENT 'add increases balance, subtract decreases',
    amount          DECIMAL(15,3) NOT NULL,
    reason          VARCHAR(500) DEFAULT NULL,
    date            DATE NOT NULL,
    created_by      INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_aba_account_date (account_id, date),
    INDEX idx_aba_account_id (account_id)
);
