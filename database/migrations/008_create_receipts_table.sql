-- Migration: Create receipts and receipt_items tables
-- Date: 2025-01-05
-- Description: Creates tables for receipt/purchase system with line items

CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    total_amount_chf DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    
    INDEX idx_document_id (document_id),
    INDEX idx_total_amount (total_amount_chf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    item_description TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price_chf DECIMAL(10,2) NOT NULL,
    line_total_chf DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE,
    
    INDEX idx_receipt_id (receipt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;