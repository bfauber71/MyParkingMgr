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

if ($user['role'] === 'Operator') {
    http_response_code(403);
    echo json_encode(['error' => 'Operators have read-only access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$property = trim($input['property'] ?? '');
$vehicleId = isset($input['id']) && $input['id'] !== '' ? intval($input['id']) : null;

if (empty($property)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property is required']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id FROM properties WHERE name = ?");
    $stmt->execute([$property]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propertyData) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    $propertyId = $propertyData['id'];
    
    // Check if user has access to this property
    if (!canAccessProperty($propertyId)) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to this property']);
        exit;
    }
    
    if ($vehicleId) {
        $stmt = $db->prepare("
            UPDATE vehicles SET
                property_id = ?,
                property = ?,
                tag_number = ?,
                plate_number = ?,
                state = ?,
                make = ?,
                model = ?,
                color = ?,
                year = ?,
                apt_number = ?,
                owner_name = ?,
                owner_phone = ?,
                owner_email = ?,
                reserved_space = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $propertyId,
            $property,
            $input['tagNumber'] ?? null,
            $input['plateNumber'] ?? null,
            $input['state'] ?? null,
            $input['make'] ?? null,
            $input['model'] ?? null,
            $input['color'] ?? null,
            $input['year'] ?? null,
            $input['aptNumber'] ?? null,
            $input['ownerName'] ?? null,
            $input['ownerPhone'] ?? null,
            $input['ownerEmail'] ?? null,
            $input['reservedSpace'] ?? null,
            $vehicleId
        ]);
        
        $identifier = $input['tagNumber'] ?: $input['plateNumber'] ?: "ID $vehicleId";
        auditLog('update_vehicle', 'vehicles', $vehicleId, "Updated vehicle: $identifier");
        
        echo json_encode([
            'success' => true,
            'id' => $vehicleId,
            'message' => 'Vehicle updated successfully'
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                property_id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $propertyId,
            $property,
            $input['tagNumber'] ?? null,
            $input['plateNumber'] ?? null,
            $input['state'] ?? null,
            $input['make'] ?? null,
            $input['model'] ?? null,
            $input['color'] ?? null,
            $input['year'] ?? null,
            $input['aptNumber'] ?? null,
            $input['ownerName'] ?? null,
            $input['ownerPhone'] ?? null,
            $input['ownerEmail'] ?? null,
            $input['reservedSpace'] ?? null
        ]);
        
        $newId = $db->lastInsertId();
        
        $identifier = $input['tagNumber'] ?: $input['plateNumber'] ?: "ID $newId";
        auditLog('create_vehicle', 'vehicles', $newId, "Created vehicle: $identifier");
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Vehicle created successfully'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
