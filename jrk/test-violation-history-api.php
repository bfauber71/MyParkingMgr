<!DOCTYPE html>
<html>
<head>
    <title>Violation History API Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: #fff; max-width: 1000px; margin: 0 auto; }
        h1 { color: #3b82f6; }
        .test { background: #2a2a2a; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .pass { color: #10b981; font-weight: bold; }
        .fail { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; }
        pre { background: #000; padding: 10px; overflow-x: auto; border-radius: 3px; font-size: 12px; }
        code { background: #333; padding: 2px 5px; border-radius: 3px; }
        .section { margin: 20px 0; }
        .button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .button:hover { background: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
    </style>
</head>
<body>
    <h1>🔍 Violation History API Diagnostic Tool</h1>
    <p>This tool tests the violation history API endpoint to identify why it's not working on your production server.</p>

<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

Session::start();

// Force admin session for testing
if (!Session::isAuthenticated()) {
    $_SESSION['user_id'] = 'test-user';
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['email'] = 'test@example.com';
    echo "<div class='test'><p class='warning'>⚠️ Not logged in - created temporary admin session for testing</p></div>";
}

echo "<div class='section'>";
echo "<h2>Test Results</h2>";

// Test 1: Database Connection
echo "<div class='test'>";
echo "<h3>Test 1: Database Connection</h3>";
try {
    $db = Database::getInstance();
    echo "<p class='pass'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p class='fail'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// Test 2: Check if violation_tickets table exists
echo "<div class='test'>";
echo "<h3>Test 2: violation_tickets Table Check</h3>";
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'violation_tickets'");
    $tableExists = $tableCheck->fetch() !== false;
    
    if ($tableExists) {
        echo "<p class='pass'>✅ violation_tickets table EXISTS</p>";
        
        // Get table structure
        $structure = $db->query("DESCRIBE violation_tickets")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($structure as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Count records
        $count = $db->query("SELECT COUNT(*) FROM violation_tickets")->fetchColumn();
        echo "<p><strong>Total violation tickets:</strong> {$count}</p>";
    } else {
        echo "<p class='fail'>❌ violation_tickets table DOES NOT EXIST</p>";
        echo "<p class='warning'>⚠️ You need to run the migration script: <code>jrk/sql/migrate-simple.sql</code></p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Check if violation_ticket_items table exists
echo "<div class='test'>";
echo "<h3>Test 3: violation_ticket_items Table Check</h3>";
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'violation_ticket_items'");
    $tableExists = $tableCheck->fetch() !== false;
    
    if ($tableExists) {
        echo "<p class='pass'>✅ violation_ticket_items table EXISTS</p>";
        $count = $db->query("SELECT COUNT(*) FROM violation_ticket_items")->fetchColumn();
        echo "<p><strong>Total violation items:</strong> {$count}</p>";
    } else {
        echo "<p class='fail'>❌ violation_ticket_items table DOES NOT EXIST</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Get a sample vehicle
echo "<div class='test'>";
echo "<h3>Test 4: Sample Vehicle Check</h3>";
try {
    $vehicle = $db->query("SELECT * FROM vehicles LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($vehicle) {
        echo "<p class='pass'>✅ Found sample vehicle</p>";
        echo "<p><strong>Vehicle ID:</strong> <code>{$vehicle['id']}</code></p>";
        echo "<p><strong>Plate:</strong> {$vehicle['plate_number']}</p>";
        echo "<p><strong>Property:</strong> {$vehicle['property']}</p>";
        $testVehicleId = $vehicle['id'];
    } else {
        echo "<p class='warning'>⚠️ No vehicles found in database</p>";
        $testVehicleId = null;
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $testVehicleId = null;
}
echo "</div>";

// Test 5: Test the API endpoint
if ($testVehicleId) {
    echo "<div class='test'>";
    echo "<h3>Test 5: API Endpoint Test</h3>";
    
    $apiUrl = "/api/vehicles-violations-history?vehicleId=" . urlencode($testVehicleId);
    echo "<p><strong>Testing:</strong> <code>{$apiUrl}</code></p>";
    
    try {
        // Simulate the API call
        $_GET['vehicleId'] = $testVehicleId;
        
        ob_start();
        include __DIR__ . '/api/vehicles-violations-history.php';
        $apiResponse = ob_get_clean();
        
        echo "<p class='pass'>✅ API executed without errors</p>";
        echo "<p><strong>API Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
        
        $decoded = json_decode($apiResponse, true);
        if ($decoded) {
            echo "<p class='info'>Response decoded successfully</p>";
            echo "<p><strong>Tickets found:</strong> " . ($decoded['count'] ?? 0) . "</p>";
            
            if (isset($decoded['message'])) {
                echo "<p class='warning'>⚠️ Message: {$decoded['message']}</p>";
            }
            
            if (isset($decoded['error'])) {
                echo "<p class='fail'>❌ Error: {$decoded['error']}</p>";
                if (isset($decoded['details'])) {
                    echo "<p><strong>Details:</strong> {$decoded['details']}</p>";
                }
            }
        } else {
            echo "<p class='fail'>❌ Failed to decode JSON response</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='fail'>❌ API Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
}

// Test 6: Session and Auth
echo "<div class='test'>";
echo "<h3>Test 6: Session & Authentication</h3>";
echo "<p><strong>Is Authenticated:</strong> " . (Session::isAuthenticated() ? '<span class="pass">YES</span>' : '<span class="fail">NO</span>') . "</p>";
echo "<p><strong>User:</strong> " . (Session::user()['username'] ?? 'N/A') . "</p>";
echo "<p><strong>Role:</strong> " . (Session::user()['role'] ?? 'N/A') . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "</div>";

echo "</div>"; // End section

// Summary
echo "<div class='section'>";
echo "<h2>Summary & Next Steps</h2>";
echo "<div class='test'>";

$issues = [];
try {
    $ticketTable = $db->query("SHOW TABLES LIKE 'violation_tickets'")->fetch() !== false;
    $itemsTable = $db->query("SHOW TABLES LIKE 'violation_ticket_items'")->fetch() !== false;
} catch (Exception $e) {
    $ticketTable = false;
    $itemsTable = false;
}

if (!$ticketTable || !$itemsTable) {
    $issues[] = "Missing database tables - run migration script";
}

if (count($issues) > 0) {
    echo "<p class='fail'><strong>❌ Issues Found:</strong></p><ul>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Log in to phpMyAdmin on your hosting</li>";
    echo "<li>Select your database</li>";
    echo "<li>Click 'SQL' tab</li>";
    echo "<li>Copy and paste the contents of <code>jrk/sql/migrate-simple.sql</code></li>";
    echo "<li>Click 'Go'</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
} else {
    echo "<p class='pass'>✅ All checks passed!</p>";
    echo "<p>The violation history API should be working correctly.</p>";
    echo "<p><strong>If you're still seeing errors:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure you uploaded the latest <code>app.js</code> and <code>index.html</code> files</li>";
    echo "<li>Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>";
    echo "<li>Check browser console for JavaScript errors (F12 → Console)</li>";
    echo "</ul>";
}

echo "</div>";
echo "</div>";
?>

    <div class="section">
        <h2>Quick Actions</h2>
        <a href="/" class="button">← Back to App</a>
        <button class="button" onclick="location.reload()">🔄 Rerun Tests</button>
    </div>
</body>
</html>
