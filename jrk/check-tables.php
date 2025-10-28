<?php
/**
 * Quick table checker - DELETE AFTER USE
 */
header('Content-Type: application/json');

require_once __DIR__ . '/includes/database.php';

try {
    $pdo = Database::connect();
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required = ['users', 'properties', 'vehicles', 'violations', 'property_violations', 
                 'property_contacts', 'user_assigned_properties', 'audit_logs', 'sessions',
                 'license_instances', 'license_attempts', 'license_audit'];
    
    $missing = [];
    foreach ($required as $table) {
        if (!in_array($table, $tables)) {
            $missing[] = $table;
        }
    }
    
    echo json_encode([
        'all_tables' => $tables,
        'required_tables' => $required,
        'missing_tables' => $missing,
        'status' => empty($missing) ? 'OK' : 'MISSING_TABLES'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
