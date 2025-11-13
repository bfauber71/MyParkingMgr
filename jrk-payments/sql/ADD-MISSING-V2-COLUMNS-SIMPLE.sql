-- ============================================
-- ADD MISSING v2.0 COLUMNS TO EXISTING DATABASE
-- ============================================
-- This file adds v2.0 payment and status columns
-- SIMPLE VERSION - Works with phpMyAdmin
-- 
-- IMPORTANT: Some statements may show errors if columns already exist.
-- This is NORMAL and SAFE - just ignore those error messages.
-- ============================================

-- ============================================
-- 1. ADD TICKET STATUS COLUMNS
-- ============================================

-- Add ticket_type column (VIOLATION vs WARNING)
ALTER TABLE violation_tickets 
ADD COLUMN ticket_type ENUM('VIOLATION', 'WARNING') DEFAULT 'VIOLATION' AFTER property_contact_email;

-- Add status column (active vs closed)
ALTER TABLE violation_tickets 
ADD COLUMN status ENUM('active', 'closed') DEFAULT 'active' AFTER ticket_type;

-- Add fine_disposition column (how fine was resolved)
ALTER TABLE violation_tickets 
ADD COLUMN fine_disposition ENUM('collected', 'dismissed') DEFAULT NULL AFTER status;

-- Add closed_at timestamp
ALTER TABLE violation_tickets 
ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL AFTER fine_disposition;

-- Add closed_by_user_id
ALTER TABLE violation_tickets 
ADD COLUMN closed_by_user_id VARCHAR(36) DEFAULT NULL AFTER closed_at;

-- Add index on status
ALTER TABLE violation_tickets 
ADD INDEX idx_status(status);

-- ============================================
-- 2. ADD PAYMENT TRACKING COLUMNS
-- ============================================

-- Add payment_status column
ALTER TABLE violation_tickets 
ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER closed_by_user_id;

-- Add amount_paid column
ALTER TABLE violation_tickets 
ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_status;

-- Add qr_code_generated_at timestamp
ALTER TABLE violation_tickets 
ADD COLUMN qr_code_generated_at TIMESTAMP NULL DEFAULT NULL AFTER amount_paid;

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

SELECT 'v2.0 columns and tables created successfully!' AS status;

-- Show the updated violation_tickets columns
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE table_schema = DATABASE()
  AND table_name = 'violation_tickets' 
  AND COLUMN_NAME IN ('ticket_type', 'status', 'fine_disposition', 'closed_at', 
                      'closed_by_user_id', 'payment_status', 'amount_paid', 'qr_code_generated_at')
ORDER BY ORDINAL_POSITION;

-- Show payment tables
SELECT TABLE_NAME, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES 
WHERE table_schema = DATABASE()
  AND TABLE_NAME IN ('payment_settings', 'ticket_payments', 'qr_codes');
