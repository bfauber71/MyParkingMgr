<?php
/**
 * License System Diagnostic Tool
 * Access this file directly to check license system health
 * 
 * SECURITY: Remove this file after troubleshooting!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>License System Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .section { margin: 20px 0; padding: 15px; background: #2a2a2a; border-left: 4px solid #00ff00; }
        .error { border-color: #ff0000; color: #ff6666; }
        .success { border-color: #00ff00; color: #66ff66; }
        h2 { color: #00ffff; margin: 0 0 10px 0; }
        pre { background: #0a0a0a; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 License System Diagnostic</h1>
    
    <?php
    // Test 1: Files exist
    echo '<div class="section">';
    echo '<h2>1. Required Files</h2>';
    $files = [
        'includes/database.php',
        'includes/license.php',
        'api/license-status.php',
        'config.php'
    ];
    foreach ($files as $file) {
        $exists = file_exists(__DIR__ . '/' . $file);
        $status = $exists ? '✓' : '✗';
        $class = $exists ? 'success' : 'error';
        echo "<div class='$class'>$status $file</div>";
    }
    echo '</div>';
    
    // Test 2: Config
    echo '<div class="section">';
    echo '<h2>2. Configuration</h2>';
    try {
        $config = require __DIR__ . '/config.php';
        echo "<div class='success'>✓ config.php loaded</div>";
        echo "<pre>";
        echo "DB Host: " . ($config['db']['host'] ?? 'NOT SET') . "\n";
        echo "DB Name: " . ($config['db']['database'] ?? 'NOT SET') . "\n";
        echo "DB User: " . ($config['db']['username'] ?? 'NOT SET') . "\n";
        echo "Install ID: " . (empty($config['install_id']) ? 'EMPTY' : 'SET') . "\n";
        echo "</pre>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Config error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo '</div>';
    
    // Test 3: Database connection
    echo '<div class="section">';
    echo '<h2>3. Database Connection</h2>';
    try {
        require_once __DIR__ . '/includes/database.php';
        echo "<div class='success'>✓ Database class loaded</div>";
        
        $pdo = Database::getPDO();
        echo "<div class='success'>✓ Database connected</div>";
        
        // Check for license tables
        $tables = ['license_instances', 'license_attempts', 'license_audit'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch() !== false;
            $status = $exists ? '✓' : '✗';
            $class = $exists ? 'success' : 'error';
            echo "<div class='$class'>$status Table: $table</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo '</div>';
    
    // Test 4: License data
    echo '<div class="section">';
    echo '<h2>4. License Instance Data</h2>';
    try {
        $stmt = $pdo->query("SELECT * FROM license_instances LIMIT 1");
        $license = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($license) {
            echo "<div class='success'>✓ License record found</div>";
            
            // Check for install_id mismatch
            $dbInstallId = $license['install_id'] ?? '';
            $configInstallId = $config['install_id'] ?? '';
            
            echo "<pre>";
            echo "Database Install ID: " . (empty($dbInstallId) ? 'EMPTY' : htmlspecialchars($dbInstallId)) . "\n";
            echo "Config Install ID: " . (empty($configInstallId) ? 'EMPTY' : htmlspecialchars($configInstallId)) . "\n";
            echo "Status: " . ($license['status'] ?? 'NULL') . "\n";
            echo "Trial Expires: " . ($license['trial_expires_at'] ?? 'NULL') . "\n";
            echo "Installed At: " . ($license['installed_at'] ?? 'NULL') . "\n";
            echo "</pre>";
            
            // Warn about mismatch
            if ($dbInstallId !== $configInstallId) {
                echo "<div class='error'>⚠️ MISMATCH DETECTED! Database install_id doesn't match config install_id.</div>";
                echo "<div class='error'>This is why your license API returns empty data!</div>";
                echo "<div class='success'>FIX: Run this SQL in phpMyAdmin:<br><code>UPDATE license_instances SET install_id = '' WHERE status = 'trial';</code></div>";
            }
        } else {
            echo "<div class='error'>✗ No license record found in database</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Query error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo '</div>';
    
    // Test 5: License class
    echo '<div class="section">';
    echo '<h2>5. License Class Test</h2>';
    try {
        require_once __DIR__ . '/includes/license.php';
        echo "<div class='success'>✓ License class loaded</div>";
        
        $status = License::getStatus();
        echo "<div class='success'>✓ License::getStatus() called successfully</div>";
        echo "<pre>";
        echo json_encode($status, JSON_PRETTY_PRINT);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ License class error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo '</div>';
    
    // Test 6: PHP Version
    echo '<div class="section">';
    echo '<h2>6. Environment</h2>';
    echo "<div>PHP Version: " . PHP_VERSION . "</div>";
    echo "<div>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
    echo '</div>';
    ?>
    
    <div class="section error">
        <h2>⚠️ IMPORTANT</h2>
        <p>Delete this diagnostic.php file after troubleshooting for security!</p>
    </div>
</body>
</html>
