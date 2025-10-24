<?php
/**
 * Database Connection Test Endpoint
 * Used by setup.php to validate database credentials
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'test_db') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$dbHost = trim($_POST['db_host'] ?? 'localhost');
$dbPort = trim($_POST['db_port'] ?? '3306');
$dbName = trim($_POST['db_name'] ?? '');
$dbUser = trim($_POST['db_user'] ?? '');
$dbPass = $_POST['db_pass'] ?? '';

if (empty($dbName) || empty($dbUser)) {
    echo json_encode(['success' => false, 'error' => 'Database name and username are required']);
    exit;
}

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test query
    $pdo->query('SELECT 1');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Database connection successful! Server version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Connection failed: ' . $e->getMessage()
    ]);
}
