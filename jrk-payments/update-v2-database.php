<?php
/**
 * ManageMyParking v2.0 Database Update Script
 * Adds all missing columns and tables required for v2.0
 * Safe to run multiple times - checks what exists before adding
 */

require_once __DIR__ . '/includes/database.php';

// Disable time limit for long migrations
set_time_limit(0);

// Track results
$results = [];
$errors = [];

try {
    $db = Database::getInstance();
    
    if (!$db) {
        die("ERROR: Could not connect to database");
    }
    
    echo "<h1>ManageMyParking v2.0 Database Update</h1>";
    echo "<pre>";
    
    // Helper function to check if column exists
    function columnExists($db, $table, $column) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Helper function to check if table exists
    function tableExists($db, $table) {
        try {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Helper function to add column
    function addColumn($db, $table, $column, $definition, &$results, &$errors) {
        if (columnExists($db, $table, $column)) {
            echo "✓ $table.$column already exists\n";
            return true;
        }
        
        try {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $db->exec($sql);
            $results[] = "Added $table.$column";
            echo "✓ Added $table.$column\n";
            return true;
        } catch (Exception $e) {
            $errors[] = "Failed to add $table.$column: " . $e->getMessage();
            echo "✗ Failed to add $table.$column: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    echo "=== UPDATING violation_tickets TABLE ===\n\n";
    
    // Add core columns
    addColumn($db, 'violation_tickets', 'custom_note', 'TEXT', $results, $errors);
    addColumn($db, 'violation_tickets', 'issued_by_user_id', 'VARCHAR(36)', $results, $errors);
    addColumn($db, 'violation_tickets', 'issued_by_username', 'VARCHAR(255)', $results, $errors);
    addColumn($db, 'violation_tickets', 'issued_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', $results, $errors);
    
    // Add vehicle snapshot columns
    addColumn($db, 'violation_tickets', 'vehicle_year', 'VARCHAR(10)', $results, $errors);
    addColumn($db, 'violation_tickets', 'vehicle_color', 'VARCHAR(50)', $results, $errors);
    addColumn($db, 'violation_tickets', 'vehicle_make', 'VARCHAR(100)', $results, $errors);
    addColumn($db, 'violation_tickets', 'vehicle_model', 'VARCHAR(100)', $results, $errors);
    
    // Add tag/plate snapshot
    addColumn($db, 'violation_tickets', 'tag_number', 'VARCHAR(100)', $results, $errors);
    addColumn($db, 'violation_tickets', 'plate_number', 'VARCHAR(100)', $results, $errors);
    
    // Add property snapshot
    addColumn($db, 'violation_tickets', 'property_address', 'TEXT', $results, $errors);
    addColumn($db, 'violation_tickets', 'property_contact_name', 'VARCHAR(255)', $results, $errors);
    addColumn($db, 'violation_tickets', 'property_contact_phone', 'VARCHAR(50)', $results, $errors);
    addColumn($db, 'violation_tickets', 'property_contact_email', 'VARCHAR(255)', $results, $errors);
    
    // Add ticket management columns
    addColumn($db, 'violation_tickets', 'ticket_type', "ENUM('WARNING', 'VIOLATION') DEFAULT 'VIOLATION'", $results, $errors);
    addColumn($db, 'violation_tickets', 'status', "ENUM('active', 'closed') DEFAULT 'active'", $results, $errors);
    addColumn($db, 'violation_tickets', 'fine_disposition', "ENUM('collected', 'dismissed', 'pending') NULL", $results, $errors);
    addColumn($db, 'violation_tickets', 'closed_at', 'DATETIME NULL', $results, $errors);
    addColumn($db, 'violation_tickets', 'closed_by_user_id', 'VARCHAR(36) NULL', $results, $errors);
    
    // Add payment columns
    addColumn($db, 'violation_tickets', 'payment_status', "ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid'", $results, $errors);
    addColumn($db, 'violation_tickets', 'amount_paid', 'DECIMAL(10,2) DEFAULT 0.00', $results, $errors);
    addColumn($db, 'violation_tickets', 'payment_link_id', 'VARCHAR(255) NULL', $results, $errors);
    addColumn($db, 'violation_tickets', 'qr_code_generated_at', 'DATETIME NULL', $results, $errors);
    
    echo "\n=== CREATING PAYMENT TABLES ===\n\n";
    
    // Create payment_settings table
    if (tableExists($db, 'payment_settings')) {
        echo "✓ payment_settings table already exists\n";
    } else {
        try {
            $db->exec("CREATE TABLE payment_settings (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "✓ Created payment_settings table\n";
            $results[] = "Created payment_settings table";
        } catch (Exception $e) {
            echo "✗ Failed to create payment_settings: " . $e->getMessage() . "\n";
            $errors[] = "Failed to create payment_settings: " . $e->getMessage();
        }
    }
    
    // Create ticket_payments table
    if (tableExists($db, 'ticket_payments')) {
        echo "✓ ticket_payments table already exists\n";
    } else {
        try {
            $db->exec("CREATE TABLE ticket_payments (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "✓ Created ticket_payments table\n";
            $results[] = "Created ticket_payments table";
        } catch (Exception $e) {
            echo "✗ Failed to create ticket_payments: " . $e->getMessage() . "\n";
            $errors[] = "Failed to create ticket_payments: " . $e->getMessage();
        }
    }
    
    // Create qr_codes table
    if (tableExists($db, 'qr_codes')) {
        echo "✓ qr_codes table already exists\n";
    } else {
        try {
            $db->exec("CREATE TABLE qr_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id VARCHAR(36) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                payment_url TEXT NOT NULL,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                INDEX idx_ticket_id (ticket_id),
                INDEX idx_generated_at (generated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "✓ Created qr_codes table\n";
            $results[] = "Created qr_codes table";
        } catch (Exception $e) {
            echo "✗ Failed to create qr_codes: " . $e->getMessage() . "\n";
            $errors[] = "Failed to create qr_codes: " . $e->getMessage();
        }
    }
    
    echo "\n=== SUMMARY ===\n\n";
    echo "Changes made: " . count($results) . "\n";
    echo "Errors: " . count($errors) . "\n\n";
    
    if (count($results) > 0) {
        echo "Successfully applied:\n";
        foreach ($results as $result) {
            echo "  ✓ $result\n";
        }
    }
    
    if (count($errors) > 0) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "  ✗ $error\n";
        }
    }
    
    echo "\n✅ Database update complete!\n";
    echo "\nNext steps:\n";
    echo "1. Clear your browser cache (Ctrl+Shift+R)\n";
    echo "2. Test violations search\n";
    echo "3. Test creating tickets\n";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>FATAL ERROR</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
