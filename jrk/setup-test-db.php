<?php
/**
 * Database Connection Test Endpoint
 * Used by setup.php to test database credentials via AJAX
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if this is a test request
if (!isset($_POST['action']) || $_POST['action'] !== 'test_db') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Get database credentials from POST
$dbHost = trim($_POST['db_host'] ?? 'localhost');
$dbPort = trim($_POST['db_port'] ?? '3306');
$dbName = trim($_POST['db_name'] ?? '');
$dbUser = trim($_POST['db_user'] ?? '');
$dbPass = $_POST['db_pass'] ?? '';

// Validate required fields
if (empty($dbName)) {
    echo json_encode(['success' => false, 'error' => 'Database name is required']);
    exit;
}

if (empty($dbUser)) {
    echo json_encode(['success' => false, 'error' => 'Database username is required']);
    exit;
}

// Test the connection
try {
    // Check if PDO MySQL extension is available
    if (!extension_loaded('pdo_mysql')) {
        throw new Exception('PDO MySQL extension is not installed. Please install php-mysql or php-pdo_mysql.');
    }
    
    // Build DSN (Data Source Name)
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    
    // Create PDO connection with error handling
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
    ]);
    
    // Test with a simple query
    $result = $pdo->query('SELECT 1 as test');
    if (!$result) {
        throw new Exception('Failed to execute test query');
    }
    
    // Try to get MySQL version for additional info
    $versionStmt = $pdo->query('SELECT VERSION() as version');
    $versionInfo = $versionStmt->fetch();
    $mysqlVersion = $versionInfo['version'] ?? 'Unknown';
    
    // Check if database has any tables (to detect if it's empty)
    $tablesStmt = $pdo->query('SHOW TABLES');
    $tableCount = $tablesStmt->rowCount();
    
    // Success response
    $message = "Connection successful! MySQL {$mysqlVersion}";
    if ($tableCount == 0) {
        $message .= " (Database is empty - ready for installation)";
    } else {
        $message .= " (Database contains {$tableCount} tables)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'mysql_version' => $mysqlVersion,
        'table_count' => $tableCount
    ]);
    
} catch (PDOException $e) {
    // Handle PDO-specific errors
    $error = 'Database connection failed: ';
    
    // Parse error code for common issues
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'Access denied') !== false) {
        $error .= 'Invalid username or password';
    } elseif (strpos($errorMessage, 'Unknown database') !== false) {
        $error .= "Database '{$dbName}' does not exist";
    } elseif (strpos($errorMessage, 'Connection refused') !== false) {
        $error .= "Cannot connect to MySQL server at {$dbHost}:{$dbPort}";
    } elseif (strpos($errorMessage, 'No such host') !== false || strpos($errorMessage, 'getaddrinfo failed') !== false) {
        $error .= "Unknown host: {$dbHost}";
    } elseif (strpos($errorMessage, 'driver not found') !== false) {
        $error .= 'PDO MySQL driver not installed';
    } else {
        // For other errors, show a sanitized version of the message
        $error .= preg_replace('/\[.*?\]/', '', $errorMessage); // Remove sensitive info in brackets
    }
    
    echo json_encode([
        'success' => false,
        'error' => $error,
        'debug_info' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser
        ]
    ]);
    
} catch (Exception $e) {
    // Handle general exceptions
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>