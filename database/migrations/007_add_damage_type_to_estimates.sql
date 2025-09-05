-- Migration: Add damage_type field to estimates table
-- Date: 2025-01-05
-- Description: Adds predefined damage types for cost estimates

ALTER TABLE estimates 
ADD COLUMN damage_type ENUM(
    'battery_damage',
    'screen_damage', 
    'water_damage',
    'total_damage',
    'charging_port',
    'speaker_microphone',
    'camera_damage',
    'button_damage',
    'software_issue',
    'other'
) NOT NULL DEFAULT 'other' AFTER document_id;

-- Add index for damage_type for better query performance
ALTER TABLE estimates 
ADD INDEX idx_damage_type (damage_type);