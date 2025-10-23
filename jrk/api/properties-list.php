<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        SELECT id, name, address, created_at 
        FROM properties 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get contacts for each property
    foreach ($properties as &$property) {
        $contactStmt = $db->prepare("
            SELECT name, phone, email, position
            FROM property_contacts 
            WHERE property_id = ? 
            ORDER BY position ASC
        ");
        $contactStmt->execute([$property['id']]);
        $property['contacts'] = $contactStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['properties' => $properties]);
} catch (PDOException $e) {
    error_log("Properties List API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
