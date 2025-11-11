<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and create/delete permission for properties
requirePermission(MODULE_PROPERTIES, ACTION_CREATE_DELETE);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);
$propertyId = trim($input['id'] ?? '');

if (empty($propertyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid property ID']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT name FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE property = ?");
    $stmt->execute([$property['name']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete property with existing vehicles']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    
    if (function_exists('auditLog')) { try { auditLog('delete_property', 'properties', $propertyId, "Deleted property: {$property['name']}"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully'
    ]);
} catch (PDOException $e) {
    error_log("Property Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
