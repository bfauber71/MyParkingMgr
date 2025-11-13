-- ============================================
-- ADD MISSING v2.0 COLUMNS TO EXISTING DATABASE
-- ============================================
-- This file adds v2.0 payment and status columns that are MISSING
-- from the COMPLETE-V2-MIGRATION.sql file.
-- 
-- Run this AFTER running COMPLETE-V2-MIGRATION.sql
-- Safe to run multiple times (idempotent)
-- ============================================

-- ============================================
-- 1. ADD TICKET STATUS COLUMNS
-- ============================================

-- Add ticket_type column (VIOLATION vs WARNING)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'ticket_type') > 0,
    'SELECT 1',
    "ALTER TABLE violation_tickets ADD COLUMN ticket_type ENUM('VIOLATION', 'WARNING') DEFAULT 'VIOLATION' AFTER property_contact_email"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column (active vs closed)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'status') > 0,
    'SELECT 1',
    "ALTER TABLE violation_tickets ADD COLUMN status ENUM('active', 'closed') DEFAULT 'active' AFTER ticket_type"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add fine_disposition column (how fine was resolved)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'fine_disposition') > 0,
    'SELECT 1',
    "ALTER TABLE violation_tickets ADD COLUMN fine_disposition ENUM('collected', 'dismissed') DEFAULT NULL AFTER status"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add closed_at timestamp
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'closed_at') > 0,
    'SELECT 1',
    'ALTER TABLE violation_tickets ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL AFTER fine_disposition'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add closed_by_user_id
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'closed_by_user_id') > 0,
    'SELECT 1',
    'ALTER TABLE violation_tickets ADD COLUMN closed_by_user_id VARCHAR(36) DEFAULT NULL AFTER closed_at'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on status
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'violation_tickets' AND index_name = 'idx_status') > 0,
    'SELECT 1',
    'ALTER TABLE violation_tickets ADD INDEX idx_status(status)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. ADD PAYMENT TRACKING COLUMNS
-- ============================================

-- Add payment_status column
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'payment_status') > 0,
    'SELECT 1',
    "ALTER TABLE violation_tickets ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER closed_by_user_id"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add amount_paid column
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'amount_paid') > 0,
    'SELECT 1',
    'ALTER TABLE violation_tickets ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_status'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add qr_code_generated_at timestamp
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violation_tickets' AND column_name = 'qr_code_generated_at') > 0,
    'SELECT 1',
    'ALTER TABLE violation_tickets ADD COLUMN qr_code_generated_at TIMESTAMP NULL DEFAULT NULL AFTER amount_paid'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 3. CREATE PAYMENT SETTINGS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS payment_settings (
    id VARCHAR(36) PRIMARY KEY,
    property_id VARCHAR(36) NOT NULL,
    payment_provider ENUM('stripe', 'square', 'paypal', 'manual') DEFAULT 'manual',
    api_key_encrypted TEXT,
    payment_link_template VARCHAR(500),
    auto_close_on_payment BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_property (property_id),
    INDEX idx_property_id (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. CREATE TICKET PAYMENTS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS ticket_payments (
    id VARCHAR(36) PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    payment_method ENUM('stripe', 'square', 'paypal', 'cash', 'check', 'card_manual') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(255),
    notes TEXT,
    recorded_by_user_id VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CREATE QR CODES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS qr_codes (
    id VARCHAR(36) PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    qr_code_data TEXT NOT NULL,
    payment_link VARCHAR(500),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

SELECT 'Missing v2.0 columns added successfully!' AS status;

-- Show the updated violation_tickets schema
SELECT 'Checking violation_tickets columns...' AS step;
SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE table_name = 'violation_tickets' 
  AND COLUMN_NAME IN ('ticket_type', 'status', 'fine_disposition', 'closed_at', 
                      'closed_by_user_id', 'payment_status', 'amount_paid', 'qr_code_generated_at')
ORDER BY ORDINAL_POSITION;

-- Show payment tables
SELECT 'Checking payment tables...' AS step;
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN ('payment_settings', 'ticket_payments', 'qr_codes');
