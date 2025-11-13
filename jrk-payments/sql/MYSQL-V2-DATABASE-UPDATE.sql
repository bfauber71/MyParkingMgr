-- ============================================================================
-- ManageMyParking v2.0 - MySQL/MariaDB Compatible Database Update
-- ============================================================================
-- IMPORTANT: This script is safe to run multiple times
-- If a column already exists, you'll see an error but it won't break anything
-- ============================================================================

-- ============================================================================
-- PART 1: ADD MISSING COLUMNS TO violation_tickets
-- ============================================================================

-- Add custom_note
ALTER TABLE violation_tickets ADD COLUMN custom_note TEXT AFTER issued_at;

-- Add issued_by columns
ALTER TABLE violation_tickets ADD COLUMN issued_by_user_id VARCHAR(36) AFTER property;
ALTER TABLE violation_tickets ADD COLUMN issued_by_username VARCHAR(255) AFTER issued_by_user_id;
ALTER TABLE violation_tickets ADD COLUMN issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER issued_by_username;

-- Add vehicle snapshot columns
ALTER TABLE violation_tickets ADD COLUMN vehicle_year VARCHAR(10) AFTER custom_note;
ALTER TABLE violation_tickets ADD COLUMN vehicle_color VARCHAR(50) AFTER vehicle_year;
ALTER TABLE violation_tickets ADD COLUMN vehicle_make VARCHAR(100) AFTER vehicle_color;
ALTER TABLE violation_tickets ADD COLUMN vehicle_model VARCHAR(100) AFTER vehicle_make;

-- Add tag/plate snapshot
ALTER TABLE violation_tickets ADD COLUMN tag_number VARCHAR(100) AFTER vehicle_model;
ALTER TABLE violation_tickets ADD COLUMN plate_number VARCHAR(100) AFTER tag_number;

-- Add property snapshot
ALTER TABLE violation_tickets ADD COLUMN property_address TEXT AFTER plate_number;
ALTER TABLE violation_tickets ADD COLUMN property_contact_name VARCHAR(255) AFTER property_address;
ALTER TABLE violation_tickets ADD COLUMN property_contact_phone VARCHAR(50) AFTER property_contact_name;
ALTER TABLE violation_tickets ADD COLUMN property_contact_email VARCHAR(255) AFTER property_contact_phone;

-- ============================================================================
-- PART 2: ADD TICKET MANAGEMENT COLUMNS
-- ============================================================================

ALTER TABLE violation_tickets ADD COLUMN ticket_type ENUM('WARNING', 'VIOLATION') DEFAULT 'VIOLATION' AFTER property_contact_email;
ALTER TABLE violation_tickets ADD COLUMN status ENUM('active', 'closed') DEFAULT 'active' AFTER ticket_type;
ALTER TABLE violation_tickets ADD COLUMN fine_disposition ENUM('collected', 'dismissed', 'pending') NULL AFTER status;
ALTER TABLE violation_tickets ADD COLUMN closed_at DATETIME NULL AFTER fine_disposition;
ALTER TABLE violation_tickets ADD COLUMN closed_by_user_id VARCHAR(36) NULL AFTER closed_at;

-- ============================================================================
-- PART 3: ADD PAYMENT COLUMNS
-- ============================================================================

ALTER TABLE violation_tickets ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER closed_by_user_id;
ALTER TABLE violation_tickets ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_status;
ALTER TABLE violation_tickets ADD COLUMN payment_link_id VARCHAR(255) NULL AFTER amount_paid;
ALTER TABLE violation_tickets ADD COLUMN qr_code_generated_at DATETIME NULL AFTER payment_link_id;

-- ============================================================================
-- PART 4: CREATE PAYMENT TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id VARCHAR(36) NOT NULL,
    processor_type ENUM('stripe', 'square', 'paypal', 'disabled') DEFAULT 'disabled',
    api_key_encrypted TEXT,
    api_secret_encrypted TEXT,
    webhook_secret_encrypted TEXT,
    publishable_key VARCHAR(255),
    is_live_mode BOOLEAN DEFAULT FALSE,
    enable_qr_codes BOOLEAN DEFAULT TRUE,
    enable_online_payments BOOLEAN DEFAULT TRUE,
    payment_description_template VARCHAR(500) DEFAULT 'Parking Violation - Ticket #{ticket_id}',
    success_redirect_url VARCHAR(500),
    failure_redirect_url VARCHAR(500),
    allow_cash_payments BOOLEAN DEFAULT TRUE,
    allow_check_payments BOOLEAN DEFAULT TRUE,
    allow_manual_card BOOLEAN DEFAULT TRUE,
    require_check_number BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_property_settings (property_id),
    INDEX idx_property_id (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    payment_method ENUM('cash', 'check', 'card_manual', 'stripe_online', 'square_online', 'paypal_online') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_number VARCHAR(50) NULL,
    transaction_id VARCHAR(255) NULL,
    payment_link_url TEXT NULL,
    qr_code_path VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    recorded_by_user_id VARCHAR(36) NOT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    payment_url TEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
