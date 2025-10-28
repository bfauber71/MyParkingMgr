<?php
/**
 * Database Connection Test Endpoint
 * Used by setup.php to test database connectivity
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$dbHost = $_POST['db_host'] ?? '';
$dbPort = $_POST['db_port'] ?? '3306';
$dbName = $_POST['db_name'] ?? '';
$dbUser = $_POST['db_user'] ?? '';
$dbPass = $_POST['db_pass'] ?? '';

if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
    echo json_encode(['error' => 'Missing required database parameters']);
    exit;
}

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful! MySQL version: ' . $result['version']
    ]);
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    
    // Provide helpful error messages
    if (strpos($errorMessage, 'Access denied') !== false) {
        $error = 'Access denied. Check username and password.';
    } elseif (strpos($errorMessage, 'Unknown database') !== false) {
        $error = 'Database does not exist. Please create it first.';
    } elseif (strpos($errorMessage, 'Connection refused') !== false) {
        $error = 'Connection refused. Is MySQL running?';
    } else {
        $error = 'Connection failed: ' . $errorMessage;
    }
    
    echo json_encode([
        'success' => false,
        'error' => $error
    ]);
}
