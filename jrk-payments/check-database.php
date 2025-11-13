<?php
/**
 * Database Diagnostic Script
 * Run this from your browser: https://myparkingmgr.com/check-database.php
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        h2 { color: #00ffff; border-bottom: 2px solid #00ffff; padding-bottom: 10px; }
        table { border-collapse: collapse; margin: 20px 0; background: #2a2a2a; }
        th, td { border: 1px solid #444; padding: 8px 12px; text-align: left; }
        th { background: #333; color: #ffff00; }
        .error { color: #ff0000; font-weight: bold; }
        .success { color: #00ff00; font-weight: bold; }
        .warning { color: #ffaa00; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔍 Database Diagnostic Report</h1>

<?php

try {
    $db = Database::getInstance();
    echo "<p class='success'>✅ Database connection: OK</p>\n";
    
    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>📋 Tables Found (" . count($tables) . ")</h2>\n";
    echo "<ul>\n";
    foreach ($tables as $table) {
        echo "<li>$table</li>\n";
    }
    echo "</ul>\n";
    
    // Check properties table structure
    echo "<h2>🏢 Properties Table Structure</h2>\n";
    if (in_array('properties', $tables)) {
        $stmt = $db->query("DESCRIBE properties");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>\n";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Check if custom_ticket_text exists
        $hasCustomText = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'custom_ticket_text') {
                $hasCustomText = true;
                break;
            }
        }
        
        if ($hasCustomText) {
            echo "<p class='success'>✅ custom_ticket_text column exists</p>\n";
        } else {
            echo "<p class='error'>❌ custom_ticket_text column MISSING</p>\n";
        }
    } else {
        echo "<p class='error'>❌ properties table does NOT exist!</p>\n";
    }
    
    // Check property_contacts table
    echo "<h2>📞 Property Contacts Table</h2>\n";
    if (in_array('property_contacts', $tables)) {
        $stmt = $db->query("DESCRIBE property_contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>\n";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>\n";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<p class='success'>✅ property_contacts table exists</p>\n";
    } else {
        echo "<p class='error'>❌ property_contacts table MISSING - This will cause 500 errors!</p>\n";
    }
    
    // Check user_assigned_properties table
    echo "<h2>👥 User Assigned Properties Table</h2>\n";
    if (in_array('user_assigned_properties', $tables)) {
        echo "<p class='success'>✅ user_assigned_properties table exists</p>\n";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM user_assigned_properties");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📊 Assignments: $count</p>\n";
    } else {
        echo "<p class='warning'>⚠️ user_assigned_properties table MISSING - Property filtering won't work!</p>\n";
    }
    
    // Count properties
    if (in_array('properties', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM properties");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<h2>📊 Data Counts</h2>\n";
        echo "<p>Properties: $count</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

?>

<hr>
<p><strong>Next Steps:</strong></p>
<ul>
    <li>If tables are missing, you need to run the database migration</li>
    <li>If columns are missing, you need to update the schema</li>
    <li>Delete this file after checking (security risk to leave it)</li>
</ul>

</body>
</html>
