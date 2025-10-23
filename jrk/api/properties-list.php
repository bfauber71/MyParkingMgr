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
$user = Session::user();

try {
    // Get accessible properties based on role (case-insensitive)
    $role = strtolower($user['role']);
    if ($role === 'admin' || $role === 'operator') {
        // Admin and Operator can see all properties
        $stmt = $db->prepare("
            SELECT id, name, address, created_at 
            FROM properties 
            ORDER BY name ASC
        ");
        $stmt->execute();
    } else {
        // Regular users only see assigned properties
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.address, p.created_at
            FROM properties p
            INNER JOIN user_assigned_properties uap ON p.id = uap.property_id
            WHERE uap.user_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user['id']]);
    }
    
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
