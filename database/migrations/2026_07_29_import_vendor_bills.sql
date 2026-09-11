-- Vendor bills for import packing (Union Logistics) — AP from invoice, not shipment estimates.
-- Estimates remain on import_payable_accruals / shipment lines for landed cost only.
-- Run on Hostinger if auto-CREATE in PackingVendorBillService has not run yet.

CREATE TABLE IF NOT EXISTS import_vendor_bills (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    bill_no             VARCHAR(50) NOT NULL,
    party_id            INT NOT NULL,
    leg                 ENUM('packing_dxb') NOT NULL DEFAULT 'packing_dxb',
    period_ym           CHAR(7) NULL COMMENT 'YYYY-MM settlement period',
    bill_date           DATE NOT NULL,
    amount              DECIMAL(15,3) NOT NULL,
    erp_estimate        DECIMAL(15,3) NOT NULL DEFAULT 0 COMMENT 'Sum of ERP packing lines cleared into this bill',
    vendor_invoice_ref  VARCHAR(100) NULL,
    warehouse_id        INT NULL,
    status              ENUM('open', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
    payment_id          INT NULL,
    notes               VARCHAR(500) NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ivb_bill_no (bill_no),
    UNIQUE KEY uq_ivb_payment (payment_id),
    INDEX idx_ivb_party_status (party_id, status),
    INDEX idx_ivb_period (period_ym),
    CONSTRAINT fk_ivb_party FOREIGN KEY (party_id) REFERENCES parties(id),
    CONSTRAINT fk_ivb_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_ivb_pay FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
