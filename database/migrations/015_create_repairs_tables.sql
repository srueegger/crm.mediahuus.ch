-- Migration: Reparaturen
-- Description: Erweitert documents.doc_type um 'repair' und legt repairs / repair_events an

ALTER TABLE documents
    MODIFY COLUMN doc_type ENUM('estimate', 'purchase', 'insurance', 'receipt', 'invoice', 'repair') NOT NULL;

CREATE TABLE repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    status ENUM('received', 'in_progress', 'waiting_parts', 'ready', 'picked_up', 'cancelled')
        NOT NULL DEFAULT 'received',
    status_token CHAR(32) NOT NULL UNIQUE,
    device_name VARCHAR(255) NOT NULL,
    imei_serial VARCHAR(64) NULL,
    damage_type VARCHAR(50) NULL,
    issue_text TEXT NOT NULL,
    work_text TEXT NULL,
    price_chf DECIMAL(10,2) NULL,
    due_date DATE NULL,
    accessories VARCHAR(255) NULL,
    unlock_code VARCHAR(100) NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE repair_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    event_type ENUM('status', 'email_sent', 'email_failed', 'note') NOT NULL,
    status VARCHAR(20) NULL,
    message VARCHAR(500) NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_repair_created (repair_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
