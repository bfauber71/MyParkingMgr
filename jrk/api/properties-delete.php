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

$user = Session::user();

if (strcasecmp($user['role'], 'admin') !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$propertyId = trim($input['id'] ?? '');

if (empty($propertyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid property ID']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE property_id = ?");
    $stmt->execute([$propertyId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete property with existing vehicles']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT name FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    
    auditLog('delete_property', 'properties', $propertyId, "Deleted property: {$property['name']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
