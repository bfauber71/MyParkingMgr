<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require authentication (includes CSRF validation)
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$vehicleId = isset($input['id']) && $input['id'] !== '' ? trim($input['id']) : null;

// Check permission: create requires create_delete, update requires edit
if ($vehicleId) {
    // Updating existing vehicle - requires edit permission
    requirePermission(MODULE_VEHICLES, ACTION_EDIT);
} else {
    // Creating new vehicle - requires create/delete permission
    requirePermission(MODULE_VEHICLES, ACTION_CREATE_DELETE);
}

$user = Session::user();

$property = trim($input['property'] ?? '');

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
        if (function_exists('auditLog')) { try { auditLog('update_vehicle', 'vehicles', $vehicleId, "Updated vehicle: $identifier"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
        
        echo json_encode([
            'success' => true,
            'id' => $vehicleId,
            'message' => 'Vehicle updated successfully'
        ]);
    } else {
        $newId = Database::uuid();
        
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $newId,
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
        
        $identifier = $input['tagNumber'] ?: $input['plateNumber'] ?: "New Vehicle";
        if (function_exists('auditLog')) { try { auditLog('create_vehicle', 'vehicles', $newId, "Created vehicle: $identifier"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
        
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
