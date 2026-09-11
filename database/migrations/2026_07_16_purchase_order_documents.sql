-- PO document attachments (supplier invoice, money transfer, other)
-- Run on deployed DB after verifying table does not exist:
--   SHOW TABLES LIKE 'purchase_order_documents';

CREATE TABLE IF NOT EXISTS purchase_order_documents (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    po_id           INT NOT NULL,
    warehouse_id    INT NOT NULL,
    doc_type        VARCHAR(40) NOT NULL DEFAULT 'other',
    original_name   VARCHAR(255) NOT NULL,
    stored_path     VARCHAR(255) NOT NULL,
    mime_type       VARCHAR(100) NOT NULL,
    file_size       INT NOT NULL DEFAULT 0,
    notes           VARCHAR(500) DEFAULT NULL,
    uploaded_by     INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po_docs_po (po_id),
    INDEX idx_po_docs_wh (warehouse_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
