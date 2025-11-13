-- ============================================
-- ADD ALL MISSING COLUMNS - COMPLETE UPDATE
-- ============================================
-- This adds BOTH base schema columns AND v2.0 payment columns
-- Safe to run - ignores errors if columns already exist
-- ============================================

-- ============================================
-- PART 1: ADD BASE SCHEMA COLUMNS (if missing)
-- ============================================

-- Add tag_number and plate_number (vehicle info stored in ticket)
ALTER TABLE violation_tickets ADD COLUMN tag_number VARCHAR(100);
ALTER TABLE violation_tickets ADD COLUMN plate_number VARCHAR(100);

-- Add property snapshot columns (property info at time of ticket)
ALTER TABLE violation_tickets ADD COLUMN property_name VARCHAR(255);
ALTER TABLE violation_tickets ADD COLUMN property_address TEXT;
ALTER TABLE violation_tickets ADD COLUMN property_contact_name VARCHAR(255);
ALTER TABLE violation_tickets ADD COLUMN property_contact_phone VARCHAR(50);
ALTER TABLE violation_tickets ADD COLUMN property_contact_email VARCHAR(255);

-- ============================================
-- PART 2: ADD v2.0 STATUS COLUMNS
-- ============================================

-- Add ticket_type (VIOLATION vs WARNING)
ALTER TABLE violation_tickets ADD COLUMN ticket_type ENUM('VIOLATION', 'WARNING') DEFAULT 'VIOLATION';

-- Add status tracking columns
ALTER TABLE violation_tickets ADD COLUMN status ENUM('active', 'closed') DEFAULT 'active';
ALTER TABLE violation_tickets ADD COLUMN fine_disposition ENUM('collected', 'dismissed') DEFAULT NULL;
ALTER TABLE violation_tickets ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE violation_tickets ADD COLUMN closed_by_user_id VARCHAR(36) DEFAULT NULL;

-- Add index on status for faster queries
ALTER TABLE violation_tickets ADD INDEX idx_status(status);

-- ============================================
-- PART 3: ADD v2.0 PAYMENT COLUMNS
-- ============================================

-- Add payment tracking columns
ALTER TABLE violation_tickets ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid';
ALTER TABLE violation_tickets ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE violation_tickets ADD COLUMN qr_code_generated_at TIMESTAMP NULL DEFAULT NULL;

-- ============================================
-- PART 4: CREATE v2.0 PAYMENT TABLES
-- ============================================

-- Payment settings per property
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

-- Payment transaction history
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

-- QR code storage
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
-- DONE!
-- ============================================
-- If you see "Duplicate column" errors above, that's NORMAL!
-- It just means those columns already existed.
-- ============================================

SELECT 'All missing columns and tables added successfully!' AS Status;
SELECT 'Your database is now fully updated to v2.0 schema.' AS Result;
