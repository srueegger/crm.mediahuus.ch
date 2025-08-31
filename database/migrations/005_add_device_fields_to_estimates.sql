ALTER TABLE estimates 
ADD COLUMN device_name VARCHAR(255) NOT NULL AFTER document_id,
ADD COLUMN serial_number VARCHAR(255) NOT NULL AFTER device_name,
ADD INDEX idx_device_name (device_name),
ADD INDEX idx_serial_number (serial_number);