-- Cash custody: count salesman cash, then move Main Cash → Custody.
-- Payment In is unchanged. Auto-applied by CashCustodyController::ensureSchema().

CREATE TABLE IF NOT EXISTS cash_handovers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    handover_no         VARCHAR(20) NOT NULL,
    warehouse_id        INT NOT NULL,
    salesman_user_id    INT DEFAULT NULL,
    from_date           DATE NOT NULL,
    to_date             DATE NOT NULL,
    expected_amount     DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    counted_amount      DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    variance            DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    status              ENUM('counted', 'in_custody') NOT NULL DEFAULT 'counted',
    source_account_id   INT NOT NULL,
    custody_account_id  INT NOT NULL,
    transfer_id         INT DEFAULT NULL,
    counted_by          INT DEFAULT NULL,
    counted_at          DATETIME DEFAULT NULL,
    received_by         INT DEFAULT NULL,
    received_at         DATETIME DEFAULT NULL,
    notes               VARCHAR(500) DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cash_handover_no (handover_no),
    INDEX idx_cash_h_wh_status (warehouse_id, status, id),
    INDEX idx_cash_h_salesman (warehouse_id, salesman_user_id, from_date, to_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_handover_payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    handover_id     INT NOT NULL,
    payment_id      INT NOT NULL,
    amount          DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cash_h_pay (payment_id),
    INDEX idx_cash_h_pay_h (handover_id),
    CONSTRAINT fk_cash_h_pay_h FOREIGN KEY (handover_id) REFERENCES cash_handovers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
