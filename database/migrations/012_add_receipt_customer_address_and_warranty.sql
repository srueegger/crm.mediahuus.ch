-- Migration: Add customer address fields to documents and warranty to receipt_items
-- Date: 2026-03-13
-- Description: Adds optional customer address (street, zip, city) to documents table
--              and warranty period to receipt_items for receipt enhancements

-- Add customer address fields to documents (optional, for receipts)
ALTER TABLE documents
    ADD COLUMN customer_street VARCHAR(255) NULL DEFAULT NULL AFTER customer_email,
    ADD COLUMN customer_zip VARCHAR(10) NULL DEFAULT NULL AFTER customer_street,
    ADD COLUMN customer_city VARCHAR(255) NULL DEFAULT NULL AFTER customer_zip;

-- Add warranty field to receipt_items
ALTER TABLE receipt_items
    ADD COLUMN warranty VARCHAR(20) NULL DEFAULT NULL AFTER line_total_chf;
