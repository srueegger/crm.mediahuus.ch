CREATE TABLE IF NOT EXISTS insurance_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    damage_type VARCHAR(50) NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(255) NOT NULL,
    damage_description TEXT,
    assessment_result ENUM('total_loss', 'repair_recommended', 'not_repairable') NOT NULL,
    device_value_chf DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    repair_cost_chf DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_document_id (document_id),
    INDEX idx_assessment_result (assessment_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
