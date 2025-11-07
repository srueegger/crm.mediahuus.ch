CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,

    -- Verkäufer-Daten
    seller_street VARCHAR(255) NOT NULL,
    seller_zip VARCHAR(10) NOT NULL,
    seller_city VARCHAR(100) NOT NULL,

    -- Geräte-Daten
    device_type ENUM('smartphone', 'tablet', 'watch', 'other') NOT NULL,
    device_brand VARCHAR(100) NOT NULL,
    device_model VARCHAR(255) NOT NULL,
    imei VARCHAR(50),
    serial_number VARCHAR(100),

    -- Gerätezustand
    device_condition TEXT,
    accessories TEXT COMMENT 'Zubehör (Ladekabel, OVP, etc.)',

    -- Ankaufspreis
    purchase_price_chf DECIMAL(10,2) NOT NULL,

    -- Ausweis-Dokumente (Dateinamen/Pfade)
    id_document_front VARCHAR(255) NOT NULL COMMENT 'Vorderseite Ausweis',
    id_document_back VARCHAR(255) COMMENT 'Rückseite Ausweis (bei Ausweis erforderlich)',

    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,

    INDEX idx_document_id (document_id),
    INDEX idx_device_type (device_type),
    INDEX idx_imei (imei)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
