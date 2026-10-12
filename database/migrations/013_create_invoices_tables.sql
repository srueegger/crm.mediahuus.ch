-- Migration: Rechnungen (Swiss QR-Rechnung)
-- Description: Erweitert documents.doc_type um 'invoice', vergroessert documents.customer_zip
--              (QR-Rechnung erlaubt bis 16 Zeichen) und legt invoices / invoice_items an

ALTER TABLE documents
    MODIFY COLUMN doc_type ENUM('estimate', 'purchase', 'insurance', 'receipt', 'invoice') NOT NULL;

ALTER TABLE documents
    MODIFY COLUMN customer_zip VARCHAR(16) NULL DEFAULT NULL;

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    company_name VARCHAR(70) NULL,
    contact_name VARCHAR(70) NULL,
    invoice_date DATE NOT NULL,
    payment_term_days INT NOT NULL DEFAULT 10,
    due_date DATE NOT NULL,
    total_net_chf DECIMAL(12,2) NOT NULL,
    total_vat_chf DECIMAL(12,2) NOT NULL,
    total_gross_chf DECIMAL(12,2) NOT NULL,
    qr_reference VARCHAR(25) NOT NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    position INT NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price_gross_chf DECIMAL(12,2) NOT NULL,
    vat_rate DECIMAL(4,2) NOT NULL,
    line_total_gross_chf DECIMAL(12,2) NOT NULL,
    INDEX idx_invoice_id (invoice_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
