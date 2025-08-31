CREATE TABLE estimates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    issue_text TEXT NOT NULL,
    price_chf DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    
    INDEX idx_document_id (document_id),
    INDEX idx_price (price_chf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;