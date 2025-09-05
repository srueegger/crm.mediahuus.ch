-- Migration: Add 'receipt' type to documents table
-- Date: 2025-01-05
-- Description: Extends the doc_type ENUM to include 'receipt' type

ALTER TABLE documents 
MODIFY COLUMN doc_type ENUM('estimate', 'purchase', 'insurance', 'receipt') NOT NULL;