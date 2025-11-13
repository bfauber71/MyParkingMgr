<?php
/**
 * Database Update Script - ManageMyParking v2.0
 * 
 * This script safely adds all missing columns and tables to your database.
 * It checks what exists first, so it's safe to run multiple times.
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your web directory
 * 2. Visit it in your browser: https://yourdomain.com/update-database.php
 * 3. Delete this file after running successfully
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Update - ManageMyParking v2.0</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 5px; }
        .error { color: red; padding: 5px; }
        .info { color: blue; padding: 5px; }
        .warning { color: orange; padding: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>ManageMyParking v2.0 - Database Update</h1>
    <hr>
    
<?php

$db = Database::getInstance();
$errors = [];
$success = [];
$skipped = [];

echo "<h2>Step 1: Adding Missing Columns to violation_tickets</h2>\n";

// Define all columns that should exist
$columnsToAdd = [
    'tag_number' => "VARCHAR(100)",
    'plate_number' => "VARCHAR(100)",
    'property_name' => "VARCHAR(255)",
    'property_address' => "TEXT",
    'property_contact_name' => "VARCHAR(255)",
    'property_contact_phone' => "VARCHAR(50)",
    'property_contact_email' => "VARCHAR(255)",
    'ticket_type' => "ENUM('VIOLATION', 'WARNING') DEFAULT 'VIOLATION'",
    'status' => "ENUM('active', 'closed') DEFAULT 'active'",
    'fine_disposition' => "ENUM('collected', 'dismissed') DEFAULT NULL",
    'closed_at' => "TIMESTAMP NULL DEFAULT NULL",
    'closed_by_user_id' => "VARCHAR(36) DEFAULT NULL",
    'payment_status' => "ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid'",
    'amount_paid' => "DECIMAL(10,2) DEFAULT 0.00",
    'qr_code_generated_at' => "TIMESTAMP NULL DEFAULT NULL"
];

foreach ($columnsToAdd as $columnName => $columnDef) {
    try {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE '$columnName'");
        if ($stmt->rowCount() > 0) {
            $skipped[] = "Column '$columnName' already exists";
            echo "<div class='info'>✓ Skipped: $columnName (already exists)</div>\n";
        } else {
            // Add the column
            $sql = "ALTER TABLE violation_tickets ADD COLUMN $columnName $columnDef";
            $db->exec($sql);
            $success[] = "Added column: $columnName";
            echo "<div class='success'>✓ Added: $columnName</div>\n";
        }
    } catch (PDOException $e) {
        $errors[] = "Error with column '$columnName': " . $e->getMessage();
        echo "<div class='error'>✗ Error adding $columnName: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    }
}

echo "<h2>Step 2: Adding Index on status column</h2>\n";

try {
    $stmt = $db->query("SHOW INDEX FROM violation_tickets WHERE Key_name = 'idx_status'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>✓ Skipped: idx_status (already exists)</div>\n";
        $skipped[] = "Index idx_status already exists";
    } else {
        $db->exec("ALTER TABLE violation_tickets ADD INDEX idx_status(status)");
        echo "<div class='success'>✓ Added: Index on status column</div>\n";
        $success[] = "Added index: idx_status";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error adding index: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    $errors[] = "Index creation error: " . $e->getMessage();
}

echo "<h2>Step 3: Creating Payment Tables</h2>\n";

// Create payment_settings table
try {
    $sql = "CREATE TABLE IF NOT EXISTS payment_settings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✓ Created: payment_settings table</div>\n";
    $success[] = "Created table: payment_settings";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<div class='info'>✓ Skipped: payment_settings (already exists)</div>\n";
        $skipped[] = "Table payment_settings already exists";
    } else {
        echo "<div class='error'>✗ Error creating payment_settings: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        $errors[] = "payment_settings error: " . $e->getMessage();
    }
}

// Create ticket_payments table
try {
    $sql = "CREATE TABLE IF NOT EXISTS ticket_payments (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✓ Created: ticket_payments table</div>\n";
    $success[] = "Created table: ticket_payments";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<div class='info'>✓ Skipped: ticket_payments (already exists)</div>\n";
        $skipped[] = "Table ticket_payments already exists";
    } else {
        echo "<div class='error'>✗ Error creating ticket_payments: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        $errors[] = "ticket_payments error: " . $e->getMessage();
    }
}

// Create qr_codes table
try {
    $sql = "CREATE TABLE IF NOT EXISTS qr_codes (
        id VARCHAR(36) PRIMARY KEY,
        ticket_id VARCHAR(36) NOT NULL,
        qr_code_data TEXT NOT NULL,
        payment_link VARCHAR(500),
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        INDEX idx_ticket_id (ticket_id),
        INDEX idx_generated_at (generated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✓ Created: qr_codes table</div>\n";
    $success[] = "Created table: qr_codes";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<div class='info'>✓ Skipped: qr_codes (already exists)</div>\n";
        $skipped[] = "Table qr_codes already exists";
    } else {
        echo "<div class='error'>✗ Error creating qr_codes: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        $errors[] = "qr_codes error: " . $e->getMessage();
    }
}

echo "<hr>\n";
echo "<h2>Summary</h2>\n";
echo "<p><strong>Success:</strong> " . count($success) . " items</p>\n";
echo "<p><strong>Skipped:</strong> " . count($skipped) . " items (already existed)</p>\n";
echo "<p><strong>Errors:</strong> " . count($errors) . " items</p>\n";

if (count($errors) > 0) {
    echo "<h3 class='error'>Errors:</h3>\n";
    echo "<pre>" . htmlspecialchars(implode("\n", $errors)) . "</pre>\n";
}

if (count($errors) === 0) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h2 style='color: #155724; margin-top: 0;'>🎉 Database Update Complete!</h2>\n";
    echo "<p style='color: #155724;'>Your database has been successfully updated to v2.0!</p>\n";
    echo "<p style='color: #155724;'><strong>IMPORTANT:</strong> Delete this file (update-database.php) now for security.</p>\n";
    echo "<p style='color: #155724;'>Next step: Clear your browser cache (Ctrl+Shift+R) and test the application.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3 style='color: #721c24; margin-top: 0;'>⚠️ Update Completed with Errors</h3>\n";
    echo "<p style='color: #721c24;'>Some items could not be updated. Please check the errors above.</p>\n";
    echo "<p style='color: #721c24;'>You may need to manually fix these issues or contact support.</p>\n";
    echo "</div>\n";
}

?>

</body>
</html>
