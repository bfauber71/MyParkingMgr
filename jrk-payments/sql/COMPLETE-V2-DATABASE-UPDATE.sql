-- ============================================================================
-- ManageMyParking v2.0 - Complete Database Update Script
-- ============================================================================
-- This script adds ALL missing columns and tables required for:
-- 1. Ticket creation (violations-create.php)
-- 2. Violation search (violations-search.php)  
-- 3. Payment processing (payment system)
-- 4. Ticket status management (ticket-close.php)
--
-- SAFE TO RUN: Uses IF NOT EXISTS and ALTER TABLE ... ADD COLUMN IF NOT EXISTS
-- ============================================================================

-- ============================================================================
-- PART 1: ADD MISSING COLUMNS TO violation_tickets TABLE
-- ============================================================================

-- Add custom_note column (used in violations-create.php, violations-search.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS custom_note TEXT AFTER issued_at;

-- Add issued_by_user_id column (used in violations-create.php, tickets-list.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS issued_by_user_id VARCHAR(36) AFTER property;

-- Add issued_by_username column (used in violations-create.php, violations-search.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS issued_by_username VARCHAR(255) AFTER issued_by_user_id;

-- Add issued_at column (replaces created_at for ticket issuance timestamp)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER issued_by_username;

-- Add vehicle snapshot columns (used in violations-create.php, violations-search.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS vehicle_year VARCHAR(10) AFTER custom_note;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS vehicle_color VARCHAR(50) AFTER vehicle_year;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS vehicle_make VARCHAR(100) AFTER vehicle_color;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS vehicle_model VARCHAR(100) AFTER vehicle_make;

-- Add tag and plate snapshot columns (used in violations-create.php, violations-zpl.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS tag_number VARCHAR(100) AFTER vehicle_model;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS plate_number VARCHAR(100) AFTER tag_number;

-- Add property snapshot columns (used in violations-create.php, violations-ticket.php)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS property_address TEXT AFTER plate_number;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS property_contact_name VARCHAR(255) AFTER property_address;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS property_contact_phone VARCHAR(50) AFTER property_contact_name;

ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS property_contact_email VARCHAR(255) AFTER property_contact_phone;

-- ============================================================================
-- PART 2: ADD v2.0 TICKET MANAGEMENT COLUMNS
-- ============================================================================

-- Add ticket_type column (WARNING vs VIOLATION)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS ticket_type ENUM('WARNING', 'VIOLATION') DEFAULT 'VIOLATION' AFTER property_contact_email;

-- Add status column (active vs closed)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'closed') DEFAULT 'active' AFTER ticket_type;

-- Add fine_disposition column (collected, dismissed, etc.)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS fine_disposition ENUM('collected', 'dismissed', 'pending') NULL AFTER status;

-- Add closed_at timestamp
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS closed_at DATETIME NULL AFTER fine_disposition;

-- Add closed_by_user_id
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS closed_by_user_id VARCHAR(36) NULL AFTER closed_at;

-- ============================================================================
-- PART 3: ADD v2.0 PAYMENT COLUMNS
-- ============================================================================

-- Add payment_status column (unpaid, partial, paid)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER closed_by_user_id;

-- Add amount_paid column
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_status;

-- Add payment_link_id column (for Stripe/Square/PayPal payment links)
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS payment_link_id VARCHAR(255) NULL AFTER amount_paid;

-- Add qr_code_generated_at column
ALTER TABLE violation_tickets 
ADD COLUMN IF NOT EXISTS qr_code_generated_at DATETIME NULL AFTER payment_link_id;

-- ============================================================================
-- PART 4: CREATE PAYMENT-RELATED TABLES
-- ============================================================================

-- Create payment_settings table
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

-- Create ticket_payments table
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

-- Create qr_codes table
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

-- ============================================================================
-- PART 5: ADD INDEXES FOR PERFORMANCE
-- ============================================================================

-- Add indexes for common query patterns (ignore if already exist)
CREATE INDEX IF NOT EXISTS idx_vt_status ON violation_tickets(status);
CREATE INDEX IF NOT EXISTS idx_vt_payment_status ON violation_tickets(payment_status);
CREATE INDEX IF NOT EXISTS idx_vt_ticket_type ON violation_tickets(ticket_type);
CREATE INDEX IF NOT EXISTS idx_vt_closed_at ON violation_tickets(closed_at);

-- ============================================================================
-- VERIFICATION QUERIES (Comment out in production)
-- ============================================================================

-- Show all columns in violation_tickets table
SELECT 'violation_tickets columns:' AS info;
SHOW COLUMNS FROM violation_tickets;

-- Show payment tables
SELECT 'Payment tables created:' AS info;
SHOW TABLES LIKE 'payment%';
SHOW TABLES LIKE 'qr_codes';

-- Show count of tickets
SELECT 'Total tickets:' AS info, COUNT(*) as count FROM violation_tickets;

SELECT '✅ Database update complete!' AS status;
