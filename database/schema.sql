
-- ============================================================
-- USERS & PERMISSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'manager', 'cashier', 'viewer') DEFAULT 'cashier',
    phone       VARCHAR(20),
    is_active   TINYINT(1) DEFAULT 1,
    last_login  TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    module      VARCHAR(100) NOT NULL,
    can_view    TINYINT(1) DEFAULT 0,
    can_add     TINYINT(1) DEFAULT 0,
    can_edit    TINYINT(1) DEFAULT 0,
    can_delete  TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module)
);

-- ============================================================
-- WAREHOUSES & USER ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS warehouses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    location    TEXT,
    phone       VARCHAR(20),
    notes       TEXT,
    is_default  TINYINT(1) DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warehouse_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id  INT NOT NULL,
    user_id       INT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_warehouse_user (warehouse_id, user_id)
);

-- ============================================================
-- PARTIES (Customers + Suppliers)
-- ============================================================

CREATE TABLE IF NOT EXISTS parties (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    party_code      VARCHAR(20),
    name            VARCHAR(255) NOT NULL,
    contact_person  VARCHAR(255),
    type            ENUM('supplier', 'customer', 'both') NOT NULL,
    phone           VARCHAR(20),
    phone2          VARCHAR(20),
    email           VARCHAR(100),
    address         TEXT,
    city            VARCHAR(100),
    country         VARCHAR(100) DEFAULT 'Kuwait',
    tax_no          VARCHAR(100),
    id_card         VARCHAR(20),
    opening_balance DECIMAL(15,3) DEFAULT 0.000,
    credit_limit    DECIMAL(15,3) DEFAULT 0.000,
    is_active       TINYINT(1) DEFAULT 1,
    notes           TEXT,
    warehouse_id    INT,
    statement_token VARCHAR(64),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    INDEX idx_parties_active_type_name (is_active, type, name)
);

-- ============================================================
-- ACCOUNTS (Cash, Bank, Wallet)
-- ============================================================

CREATE TABLE IF NOT EXISTS accounts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    type            ENUM('cash', 'bank', 'mobile_wallet', 'other') DEFAULT 'cash',
    gl_code         VARCHAR(10) DEFAULT NULL,
    account_no      VARCHAR(100),
    bank_name       VARCHAR(255),
    opening_balance DECIMAL(15,3) DEFAULT 0.000,
    current_balance DECIMAL(15,3) DEFAULT 0.000,
    is_default      TINYINT(1) DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    sort_order      INT DEFAULT 0,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- ACCOUNT TRANSFERS
-- ============================================================

CREATE TABLE IF NOT EXISTS account_transfers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transfer_no     VARCHAR(50) NOT NULL UNIQUE,
    from_account_id INT NOT NULL,
    to_account_id   INT NOT NULL,
    amount          DECIMAL(15,3) NOT NULL,
    date            DATE NOT NULL,
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id),
    FOREIGN KEY (to_account_id) REFERENCES accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Manual balance corrections (Adjust Balance); included in balance recalculation
CREATE TABLE IF NOT EXISTS account_balance_adjustments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    account_id      INT NOT NULL,
    direction       ENUM('add', 'subtract') NOT NULL,
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

-- ============================================================
-- INVENTORY
-- ============================================================

CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    parent_id   INT DEFAULT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    sku             VARCHAR(100) UNIQUE,
    barcode         VARCHAR(100),
    category_id     INT,
    brand           VARCHAR(100),
    model           VARCHAR(100),
    unit            VARCHAR(50) DEFAULT 'pcs',
    has_imei        TINYINT(1) DEFAULT 0,
    purchase_price  DECIMAL(15,3) DEFAULT 0.000,
    sale_price      DECIMAL(15,3) DEFAULT 0.000,
    price_aed       DECIMAL(15,3) DEFAULT 0.000,
    price_usd       DECIMAL(15,3) DEFAULT 0.000,
    min_stock       INT DEFAULT 0,
    description     TEXT,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS stock (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    item_id         INT NOT NULL,
    warehouse_id    INT NOT NULL,
    quantity        INT DEFAULT 0,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_item_warehouse (item_id, warehouse_id)
);

-- IMEI Registry - every device tracked individually
CREATE TABLE IF NOT EXISTS imei_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    imei            VARCHAR(20) NOT NULL UNIQUE,
    imei2           VARCHAR(20),
    item_id         INT NOT NULL,
    warehouse_id    INT,
    status          ENUM('in_stock', 'sold', 'returned', 'transferred', 'defective') DEFAULT 'in_stock',
    purchase_id     INT DEFAULT NULL,
    sale_id         INT DEFAULT NULL,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Opening stock log
CREATE TABLE IF NOT EXISTS opening_stock_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    item_id      INT NOT NULL,
    quantity     INT NOT NULL DEFAULT 0,
    cost_price   DECIMAL(15,3) DEFAULT 0.000,
    total_value  DECIMAL(15,3) DEFAULT 0.000,
    date         DATE,
    created_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_item_warehouse (item_id, warehouse_id)
);

-- ============================================================
-- PURCHASES
-- ============================================================

CREATE TABLE IF NOT EXISTS purchases (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no          VARCHAR(50) NOT NULL UNIQUE,
    supplier_invoice_no VARCHAR(100),
    reference_no        VARCHAR(100),
    party_id            INT NOT NULL,
    warehouse_id        INT NOT NULL,
    date                DATE NOT NULL,
    due_date            DATE,
    subtotal            DECIMAL(15,3) DEFAULT 0.000,
    discount            DECIMAL(15,3) DEFAULT 0.000,
    tax                 DECIMAL(15,3) DEFAULT 0.000,
    landed_cost         DECIMAL(15,3) DEFAULT 0.000,
    grand_total         DECIMAL(15,3) DEFAULT 0.000,
    paid_amount         DECIMAL(15,3) DEFAULT 0.000,
    balance             DECIMAL(15,3) DEFAULT 0.000,
    status              ENUM('draft', 'confirmed', 'partial', 'paid', 'cancelled') DEFAULT 'draft',
    notes               TEXT,
    created_by          INT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_purchases_wh_created (warehouse_id, created_at),
    INDEX idx_purchases_party_wh (party_id, warehouse_id),
    INDEX idx_purchases_party_status_date (party_id, status, date),
    INDEX idx_purchases_date_status (date, status)
);

CREATE TABLE IF NOT EXISTS purchase_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id     INT NOT NULL,
    item_id         INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15,3) DEFAULT 0.000,
    discount        DECIMAL(15,3) DEFAULT 0.000,
    tax             DECIMAL(15,3) DEFAULT 0.000,
    total           DECIMAL(15,3) DEFAULT 0.000,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS purchase_item_imei (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    purchase_item_id INT NOT NULL,
    imei_id          INT NOT NULL,
    FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id) ON DELETE CASCADE,
    FOREIGN KEY (imei_id) REFERENCES imei_records(id)
);

-- ============================================================
-- PURCHASE ORDERS
-- ============================================================

CREATE TABLE IF NOT EXISTS purchase_orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    po_no            VARCHAR(50) NOT NULL UNIQUE,
    party_id         INT NOT NULL,
    warehouse_id     INT NOT NULL,
    date             DATE NOT NULL,
    currency         VARCHAR(10) DEFAULT 'AED',
    exchange_rate    DECIMAL(10,6) DEFAULT 1.000000,
    subtotal_foreign DECIMAL(15,3) DEFAULT 0.000,
    subtotal_kwd     DECIMAL(15,3) DEFAULT 0.000,
    paid_foreign     DECIMAL(15,3) DEFAULT 0.000,
    paid_kwd         DECIMAL(15,3) DEFAULT 0.000,
    status           VARCHAR(20) DEFAULT 'draft',
    supplier_ref     VARCHAR(255),
    notes            TEXT,
    created_by       INT,
    account_id       INT,
    converted_to     INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (converted_to) REFERENCES purchases(id)
);

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    po_id              INT NOT NULL,
    item_id            INT NOT NULL,
    quantity           INT NOT NULL DEFAULT 1,
    unit_price_foreign DECIMAL(15,3) DEFAULT 0.000,
    unit_price_kwd     DECIMAL(15,3) DEFAULT 0.000,
    total_foreign      DECIMAL(15,3) DEFAULT 0.000,
    total_kwd          DECIMAL(15,3) DEFAULT 0.000,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- ============================================================
-- SALES
-- ============================================================

CREATE TABLE IF NOT EXISTS sales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no      VARCHAR(50) NOT NULL UNIQUE,
    party_id        INT NOT NULL,
    warehouse_id    INT NOT NULL,
    date            DATE NOT NULL,
    due_date        DATE,
    subtotal        DECIMAL(15,3) DEFAULT 0.000,
    discount        DECIMAL(15,3) DEFAULT 0.000,
    tax             DECIMAL(15,3) DEFAULT 0.000,
    grand_total     DECIMAL(15,3) DEFAULT 0.000,
    paid_amount     DECIMAL(15,3) DEFAULT 0.000,
    balance         DECIMAL(15,3) DEFAULT 0.000,
    status          ENUM('draft', 'confirmed', 'partial', 'paid', 'cancelled') DEFAULT 'draft',
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_sales_wh_created (warehouse_id, created_at),
    INDEX idx_sales_party_wh (party_id, warehouse_id),
    INDEX idx_sales_party_status_date (party_id, status, date),
    INDEX idx_sales_date_status (date, status)
);

CREATE TABLE IF NOT EXISTS sale_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sale_id         INT NOT NULL,
    item_id         INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15,3) DEFAULT 0.000,
    discount        DECIMAL(15,3) DEFAULT 0.000,
    tax             DECIMAL(15,3) DEFAULT 0.000,
    total           DECIMAL(15,3) DEFAULT 0.000,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS sale_item_imei (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sale_item_id    INT NOT NULL,
    imei_id         INT NOT NULL,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE CASCADE,
    FOREIGN KEY (imei_id) REFERENCES imei_records(id)
);

-- ============================================================
-- PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    payment_no      VARCHAR(50) NOT NULL UNIQUE,
    ref_type        ENUM('purchase', 'sale', 'expense', 'opening', 'transfer', 'discount') NOT NULL,
    ref_id          INT NOT NULL,
    party_id        INT,
    phone_no        VARCHAR(20),
    payment_type    VARCHAR(5) DEFAULT 'in',
    warehouse_id    INT,
    account_id      INT NOT NULL,
    amount          DECIMAL(15,3) NOT NULL,
    payment_method  ENUM('cash', 'bank_transfer', 'cheque', 'mobile_wallet', 'card') DEFAULT 'cash',
    cheque_no       VARCHAR(100),
    date            DATE NOT NULL,
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_payments_party_date (party_id, date),
    INDEX idx_payments_party_reftype (party_id, ref_type),
    INDEX idx_payments_party_wh (party_id, warehouse_id),
    INDEX idx_payments_ref (ref_type, ref_id)
);

-- ============================================================
-- RETURNS
-- ============================================================

CREATE TABLE IF NOT EXISTS returns (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    return_no       VARCHAR(50) NOT NULL UNIQUE,
    type            ENUM('purchase_return', 'sale_return') NOT NULL,
    ref_id          INT NOT NULL,
    party_id        INT NOT NULL,
    warehouse_id    INT NOT NULL,
    date            DATE NOT NULL,
    subtotal        DECIMAL(15,3) DEFAULT 0.000,
    grand_total     DECIMAL(15,3) DEFAULT 0.000,
    reason          TEXT,
    status          ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_returns_party_status_date (party_id, status, date),
    INDEX idx_returns_party_wh (party_id, warehouse_id),
    INDEX idx_returns_ref_type_status (ref_id, type, status)
);

CREATE TABLE IF NOT EXISTS return_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    return_id       INT NOT NULL,
    item_id         INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    unit_price      DECIMAL(15,3) DEFAULT 0.000,
    total           DECIMAL(15,3) DEFAULT 0.000,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS return_item_imei (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    return_item_id  INT NOT NULL,
    imei_id         INT NOT NULL,
    FOREIGN KEY (return_item_id) REFERENCES return_items(id) ON DELETE CASCADE,
    FOREIGN KEY (imei_id) REFERENCES imei_records(id)
);

-- ============================================================
-- WARRANTY REPLACEMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS warranty_replacements (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    replacement_no    VARCHAR(50) NOT NULL UNIQUE,
    sale_id           INT,
    party_id          INT NOT NULL,
    warehouse_id      INT NOT NULL,
    date              DATE NOT NULL,
    old_item_id       INT NOT NULL,
    old_imei          VARCHAR(20),
    old_imei2         VARCHAR(20),
    new_item_id       INT NOT NULL,
    new_imei          VARCHAR(20),
    new_imei2         VARCHAR(20),
    fault_description TEXT,
    notes             TEXT,
    status            VARCHAR(30) DEFAULT 'completed',
    created_by        INT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (old_item_id) REFERENCES items(id),
    FOREIGN KEY (new_item_id) REFERENCES items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- EXPENSES
-- ============================================================

CREATE TABLE IF NOT EXISTS expense_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS expenses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    expense_no      VARCHAR(50) NOT NULL UNIQUE,
    category_id     INT,
    account_id      INT NOT NULL,
    warehouse_id    INT,
    party_id        INT,
    amount          DECIMAL(15,3) NOT NULL,
    date            DATE NOT NULL,
    description     TEXT,
    receipt_file    VARCHAR(255),
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_expenses_party_date (party_id, date)
);

-- ============================================================
-- CUSTOMER DISCOUNTS
-- ============================================================

CREATE TABLE IF NOT EXISTS customer_discounts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    discount_no VARCHAR(50) NOT NULL UNIQUE,
    party_id    INT NOT NULL,
    item_id     INT,
    amount      DECIMAL(15,3) NOT NULL,
    reason      TEXT,
    date        DATE NOT NULL,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id),
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- STOCK TRANSFERS
-- ============================================================

CREATE TABLE IF NOT EXISTS stock_transfers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    transfer_no         VARCHAR(50) NOT NULL UNIQUE,
    from_warehouse_id   INT NOT NULL,
    to_warehouse_id     INT NOT NULL,
    date                DATE NOT NULL,
    status              ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes               TEXT,
    created_by          INT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS stock_transfer_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transfer_id     INT NOT NULL,
    item_id         INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    FOREIGN KEY (transfer_id) REFERENCES stock_transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS stock_transfer_imei (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    transfer_item_id INT NOT NULL,
    imei_id          INT NOT NULL,
    FOREIGN KEY (transfer_item_id) REFERENCES stock_transfer_items(id) ON DELETE CASCADE,
    FOREIGN KEY (imei_id) REFERENCES imei_records(id)
);

-- ============================================================
-- FINANCE
-- ============================================================

-- Landed Costs (extra costs on purchases like shipping, customs)
CREATE TABLE IF NOT EXISTS landed_costs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id         INT NOT NULL,
    description         VARCHAR(255) NOT NULL,
    amount              DECIMAL(15,3) NOT NULL,
    allocation_method   ENUM('by_qty', 'by_value', 'equal') DEFAULT 'by_value',
    account_id          INT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- ============================================================
-- DISCOUNTS (Promotional rules)
-- ============================================================

CREATE TABLE IF NOT EXISTS discounts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    type            ENUM('percentage', 'fixed') NOT NULL,
    value           DECIMAL(10,3) NOT NULL,
    applies_to      ENUM('all', 'category', 'item', 'party') DEFAULT 'all',
    ref_id          INT DEFAULT NULL,
    min_amount      DECIMAL(15,3) DEFAULT 0.000,
    valid_from      DATE,
    valid_to        DATE,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SHIPMENTS (Landed cost grouping across purchases)
-- ============================================================

CREATE TABLE IF NOT EXISTS shipments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    shipment_no VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    date        DATE NOT NULL,
    status      ENUM('draft', 'applied') DEFAULT 'draft',
    notes       TEXT,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shipment_costs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id       INT NOT NULL,
    description       VARCHAR(255) NOT NULL,
    amount            DECIMAL(15,3) NOT NULL,
    allocation_method ENUM('by_qty', 'by_value', 'equal') DEFAULT 'by_qty',
    account_id        INT,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

CREATE TABLE IF NOT EXISTS shipment_purchases (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL,
    purchase_id INT NOT NULL,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- ============================================================
-- API KEYS
-- ============================================================

CREATE TABLE IF NOT EXISTS api_keys (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    app_name        VARCHAR(255) NOT NULL,
    api_key         VARCHAR(255) NOT NULL UNIQUE,
    permissions     JSON,
    is_active       TINYINT(1) DEFAULT 1,
    last_used_at    TIMESTAMP NULL,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS api_rate_limits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id  INT NOT NULL,
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_key_time (api_key_id, request_time)
);

-- ============================================================
-- LOGIN ATTEMPTS (brute force protection)
-- ============================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip          VARCHAR(45) NOT NULL,
    email       VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_time (ip, created_at)
);

-- ============================================================
-- ACTIVITY LOG
-- ============================================================

CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    action      VARCHAR(100) NOT NULL,
    module      VARCHAR(100),
    ref_id      INT,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- MANDOOB INVENTORY (van physical stock count schedule)
-- ============================================================

CREATE TABLE IF NOT EXISTS mandoob_inventory_schedules (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id     INT NOT NULL,
    party_id         INT DEFAULT NULL,
    name             VARCHAR(255) NOT NULL,
    phone            VARCHAR(40) DEFAULT NULL,
    interval_months  TINYINT UNSIGNED NOT NULL DEFAULT 3,
    last_count_date  DATE DEFAULT NULL,
    next_due_date    DATE DEFAULT NULL,
    notes            TEXT,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_by       INT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_mandoob_wh_party (warehouse_id, party_id),
    INDEX idx_mandoob_wh_due (warehouse_id, next_due_date),
    INDEX idx_mandoob_wh_active (warehouse_id, is_active)
);

-- ============================================================
-- BACKUP LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS backup_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(255) NOT NULL,
    size_kb     INT DEFAULT 0,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    key_name    VARCHAR(100) NOT NULL UNIQUE,
    value       TEXT,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default admin user (password: Admin@123)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default warehouse
INSERT INTO warehouses (name, location, is_default) VALUES
('Main Warehouse', 'Main Branch', 1);

-- Default cash account
INSERT INTO accounts (name, type, is_default, current_balance) VALUES
('Main Cash', 'cash', 1, 0.000),
('Bank Account', 'bank', 0, 0.000);

-- Default expense categories
INSERT INTO expense_categories (name) VALUES
('Rent'), ('Utilities'), ('Salaries'), ('Transport'), ('Maintenance'), ('Office Supplies'), ('Other');

-- Default settings
INSERT INTO settings (key_name, value) VALUES
('company_name', 'Your Company Name'),
('company_address', 'Your Address'),
('company_phone', '+965 XXXX XXXX'),
('company_email', 'info@company.com'),
('currency', 'KWD'),
('decimal_places', '3'),
('low_stock_alert', '5'),
('invoice_footer', 'Thank you for your business!'),
('tax_enabled', '0'),
('tax_rate', '0');

-- Default permissions for admin (all access)
INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete) VALUES
(1, 'dashboard',    1,1,1,1),
(1, 'purchases',    1,1,1,1),
(1, 'sales',        1,1,1,1),
(1, 'payments',     1,1,1,1),
(1, 'returns',      1,1,1,1),
(1, 'expenses',     1,1,1,1),
(1, 'inventory',    1,1,1,1),
(1, 'finance',      1,1,1,1),
(1, 'reports',      1,1,1,1),
(1, 'settings',     1,1,1,1);
