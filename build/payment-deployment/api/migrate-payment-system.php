<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Require authentication and admin role
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if (!hasPermission('manage_users')) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

try {
    $db = Database::connect();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $results = [];
    
    // Create payment_settings table
    $db->exec("CREATE TABLE IF NOT EXISTS payment_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
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
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        UNIQUE KEY unique_property_settings (property_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = 'payment_settings table created';
    
    // Create ticket_payments table
    $db->exec("CREATE TABLE IF NOT EXISTS ticket_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        payment_method ENUM('cash', 'check', 'card_manual', 'stripe_online', 'square_online', 'paypal_online') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        check_number VARCHAR(50) NULL,
        transaction_id VARCHAR(255) NULL,
        payment_link_url TEXT NULL,
        qr_code_path VARCHAR(255) NULL,
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
        recorded_by_user_id INT NOT NULL,
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES violation_tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by_user_id) REFERENCES users(id),
        INDEX idx_ticket_id (ticket_id),
        INDEX idx_payment_date (payment_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = 'ticket_payments table created';
    
    // Check if payment columns already exist in violation_tickets
    $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'payment_status'");
    $hasPaymentColumns = $stmt->rowCount() > 0;
    
    if (!$hasPaymentColumns) {
        $db->exec("ALTER TABLE violation_tickets 
            ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
            ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00,
            ADD COLUMN payment_link_id VARCHAR(255) NULL,
            ADD COLUMN qr_code_generated_at DATETIME NULL,
            ADD INDEX idx_payment_status (payment_status)");
        $results[] = 'Payment columns added to violation_tickets';
    } else {
        $results[] = 'Payment columns already exist in violation_tickets';
    }
    
    // Create qr_codes table
    $db->exec("CREATE TABLE IF NOT EXISTS qr_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        payment_url TEXT NOT NULL,
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES violation_tickets(id) ON DELETE CASCADE,
        INDEX idx_ticket_id (ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = 'qr_codes table created';
    
    // Insert default payment settings for existing properties
    $stmt = $db->query("SELECT COUNT(*) FROM properties");
    $propertyCount = $stmt->fetchColumn();
    
    if ($propertyCount > 0) {
        $db->exec("INSERT IGNORE INTO payment_settings (property_id, processor_type, enable_qr_codes, enable_online_payments)
            SELECT id, 'disabled', TRUE, FALSE FROM properties");
        $results[] = "Default payment settings created for {$propertyCount} properties";
    } else {
        $results[] = 'No properties found, skipping default payment settings';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment system migration completed successfully',
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Payment migration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Migration failed: ' . $e->getMessage()
    ]);
}
