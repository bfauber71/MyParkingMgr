<?php
/**
 * Diagnostic Script - Upload this to check what's wrong
 * Access it at: https://2clv.com/jrk/diagnostic.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ManageMyParking Diagnostic</h1>";
echo "<pre>";

// Test 1: PHP Version
echo "=== PHP VERSION ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Required: 7.4 or higher\n";
echo "Status: " . (version_compare(phpversion(), '7.4.0', '>=') ? '✅ OK' : '❌ FAIL') . "\n\n";

// Test 2: Required Extensions
echo "=== PHP EXTENSIONS ===\n";
$required = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "$ext: " . ($loaded ? '✅ Loaded' : '❌ Missing') . "\n";
}
echo "\n";

// Test 3: Config File
echo "=== CONFIG FILE ===\n";
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    echo "config.php: ✅ Exists\n";
    try {
        $config = require $configPath;
        echo "Database Host: " . $config['db']['host'] . "\n";
        echo "Database Name: " . $config['db']['database'] . "\n";
        echo "Database User: " . $config['db']['username'] . "\n";
        echo "Database Password: " . (strlen($config['db']['password']) > 0 ? '***SET***' : '❌ EMPTY') . "\n\n";
    } catch (Exception $e) {
        echo "❌ Error loading config: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "config.php: ❌ NOT FOUND\n\n";
}

// Test 4: Database Connection
echo "=== DATABASE CONNECTION ===\n";
try {
    require_once __DIR__ . '/config.php';
    $config = require $configPath;
    
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['database']};charset={$config['db']['charset']}";
    $pdo = new PDO(
        $dsn,
        $config['db']['username'],
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "Connection: ✅ SUCCESS\n";
    
    // Test tables
    echo "\n=== DATABASE TABLES ===\n";
    $tables = ['users', 'properties', 'vehicles', 'property_contacts', 'user_assigned_properties', 'audit_logs', 'sessions'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "$table: ✅ {$result['count']} rows\n";
        } catch (PDOException $e) {
            echo "$table: ❌ " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Connection: ❌ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nCommon fixes:\n";
    echo "- Check database credentials in config.php\n";
    echo "- Make sure database exists\n";
    echo "- Import install.sql to create tables\n";
}

echo "\n=== FILE PERMISSIONS ===\n";
$files = ['includes/database.php', 'includes/session.php', 'api/properties.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "$file: ✅ Exists (permissions: " . substr(sprintf('%o', fileperms($path)), -4) . ")\n";
    } else {
        echo "$file: ❌ NOT FOUND\n";
    }
}

echo "\n=== SESSION TEST ===\n";
try {
    require_once __DIR__ . '/includes/session.php';
    Session::start();
    Session::set('test', 'value');
    $value = Session::get('test');
    echo "Session: " . ($value === 'value' ? '✅ Working' : '❌ Failed') . "\n";
    echo "Session ID: " . session_id() . "\n";
} catch (Exception $e) {
    echo "Session: ❌ " . $e->getMessage() . "\n";
}

echo "\n=== API ENDPOINT TEST ===\n";
echo "Testing /api/properties endpoint...\n";
try {
    require_once __DIR__ . '/includes/database.php';
    require_once __DIR__ . '/includes/session.php';
    
    Session::start();
    // Simulate logged in admin user
    Session::set('user_id', '550e8400-e29b-41d4-a716-446655440000');
    Session::set('user', ['id' => '550e8400-e29b-41d4-a716-446655440000', 'username' => 'admin', 'role' => 'admin']);
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, name, address FROM properties ORDER BY name ASC LIMIT 5");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Properties query: ✅ SUCCESS\n";
    echo "Found " . count($properties) . " properties\n";
    
    if (count($properties) > 0) {
        echo "\nSample property:\n";
        print_r($properties[0]);
    }
    
} catch (Exception $e) {
    echo "Properties query: ❌ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If any tests show ❌, fix those issues first</li>";
echo "<li>Check your server's error_log file for more details</li>";
echo "<li>Make sure config.php has correct database credentials</li>";
echo "<li>Make sure install.sql was imported</li>";
echo "</ul>";
?>
