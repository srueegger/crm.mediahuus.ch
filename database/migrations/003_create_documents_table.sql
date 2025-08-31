CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_type ENUM('estimate', 'purchase', 'insurance') NOT NULL,
    doc_number VARCHAR(20) NOT NULL UNIQUE,
    branch_id INT NOT NULL,
    user_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_doc_type (doc_type),
    INDEX idx_doc_number (doc_number),
    INDEX idx_branch_id (branch_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;