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

$name = trim($input['name'] ?? '');
$address = trim($input['address'] ?? '');

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property name is required']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id FROM properties WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Property name already exists']);
        exit;
    }
    
    $stmt = $db->prepare("
        INSERT INTO properties (name, address, created_at, updated_at)
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$name, $address]);
    
    $propertyId = $db->lastInsertId();
    
    auditLog('create_property', 'properties', $propertyId, "Created property: $name");
    
    echo json_encode([
        'success' => true,
        'id' => $propertyId,
        'message' => 'Property created successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
