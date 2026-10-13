CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(70) NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    phone_normalized VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    street VARCHAR(255) NULL,
    zip VARCHAR(16) NULL,
    city VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_phone_normalized (phone_normalized),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents
    ADD COLUMN customer_id INT NULL AFTER user_id,
    ADD INDEX idx_customer_id (customer_id),
    ADD CONSTRAINT fk_documents_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE SET NULL;
