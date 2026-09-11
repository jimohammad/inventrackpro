-- Employee HR records (civil ID, passport, residence, salary, document scans).
-- Also auto-created on first visit via Employee::ensureSchema().

CREATE TABLE IF NOT EXISTS employees (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_no           VARCHAR(20)  NOT NULL,
    warehouse_id          INT          NOT NULL,
    name                  VARCHAR(150) NOT NULL,
    job_title             VARCHAR(100) NULL,
    phone                 VARCHAR(40)  NULL,
    nationality           VARCHAR(80)  NULL,
    passport_no           VARCHAR(50)  NULL,
    passport_expires_on   DATE         NULL,
    kuwait_id_no          VARCHAR(30)  NULL,
    residence_expires_on  DATE         NULL,
    salary                DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    kuwait_id_file        VARCHAR(255) NULL,
    passport_file         VARCHAR(255) NULL,
    work_permit_file      VARCHAR(255) NULL,
    notes                 TEXT         NULL,
    is_active             TINYINT(1)   NOT NULL DEFAULT 1,
    created_by            INT          NULL,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_no_wh (warehouse_id, employee_no),
    INDEX idx_emp_wh_active (warehouse_id, is_active, name),
    INDEX idx_emp_residence (warehouse_id, residence_expires_on, is_active),
    INDEX idx_emp_passport (warehouse_id, passport_expires_on, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
