<?php
/**
 * Direct License Test - Bypasses all caching
 * DELETE THIS FILE AFTER TESTING
 */

// Force no caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

// Clear opcode cache if possible
if (function_exists('opcache_reset')) {
    opcache_reset();
}

try {
    require_once __DIR__ . '/includes/database.php';
    require_once __DIR__ . '/includes/license.php';
    
    // Direct database query - no caching
    $pdo = Database::connect();
    $stmt = $pdo->query("SELECT * FROM license_instances ORDER BY installed_at ASC LIMIT 1");
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'test' => 'direct_database_query',
        'timestamp' => date('Y-m-d H:i:s'),
        'cache_cleared' => function_exists('opcache_reset'),
        'database_connected' => ($pdo !== null),
        'license_found' => ($license !== false),
        'license_data' => $license,
        'license_class_status' => License::getStatus()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
