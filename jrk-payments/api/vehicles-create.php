<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


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
    // Check if new columns exist (for backward compatibility)
    $hasResidentFields = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM vehicles LIKE 'resident'");
        $hasResidentFields = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasResidentFields = false;
    }
    
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
        if ($hasResidentFields) {
            // New schema with resident/guest fields
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
                    resident = ?,
                    guest = ?,
                    guest_of = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $propertyId,
                $property,
                $input['tag_number'] ?? null,
                $input['plate_number'] ?? null,
                $input['state'] ?? null,
                $input['make'] ?? null,
                $input['model'] ?? null,
                $input['color'] ?? null,
                $input['year'] ?? null,
                $input['apt_number'] ?? null,
                $input['owner_name'] ?? null,
                $input['owner_phone'] ?? null,
                $input['owner_email'] ?? null,
                $input['reserved_space'] ?? null,
                isset($input['resident']) ? ($input['resident'] ? 1 : 0) : 1,
                isset($input['guest']) ? ($input['guest'] ? 1 : 0) : 0,
                $input['guest_of'] ?? null,
                $vehicleId
            ]);
        } else {
            // Old schema without resident/guest fields
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
                $input['tag_number'] ?? null,
                $input['plate_number'] ?? null,
                $input['state'] ?? null,
                $input['make'] ?? null,
                $input['model'] ?? null,
                $input['color'] ?? null,
                $input['year'] ?? null,
                $input['apt_number'] ?? null,
                $input['owner_name'] ?? null,
                $input['owner_phone'] ?? null,
                $input['owner_email'] ?? null,
                $input['reserved_space'] ?? null,
                $vehicleId
            ]);
        }
        
        $identifier = $input['tag_number'] ?: $input['plate_number'] ?: "ID $vehicleId";
        if (function_exists('auditLog')) { try { auditLog('update_vehicle', 'vehicles', $vehicleId, "Updated vehicle: $identifier"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
        
        echo json_encode([
            'success' => true,
            'id' => $vehicleId,
            'message' => 'Vehicle updated successfully'
        ]);
    } else {
        $newId = Database::uuid();
        
        if ($hasResidentFields) {
            // New schema with resident/guest fields
            $stmt = $db->prepare("
                INSERT INTO vehicles (
                    id, property_id, property, tag_number, plate_number, state, make, model, color, year,
                    apt_number, owner_name, owner_phone, owner_email, reserved_space,
                    resident, guest, guest_of, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $newId,
                $propertyId,
                $property,
                $input['tag_number'] ?? null,
                $input['plate_number'] ?? null,
                $input['state'] ?? null,
                $input['make'] ?? null,
                $input['model'] ?? null,
                $input['color'] ?? null,
                $input['year'] ?? null,
                $input['apt_number'] ?? null,
                $input['owner_name'] ?? null,
                $input['owner_phone'] ?? null,
                $input['owner_email'] ?? null,
                $input['reserved_space'] ?? null,
                isset($input['resident']) ? ($input['resident'] ? 1 : 0) : 1,
                isset($input['guest']) ? ($input['guest'] ? 1 : 0) : 0,
                $input['guest_of'] ?? null
            ]);
        } else {
            // Old schema without resident/guest fields
            $stmt = $db->prepare("
                INSERT INTO vehicles (
                    id, property_id, property, tag_number, plate_number, state, make, model, color, year,
                    apt_number, owner_name, owner_phone, owner_email, reserved_space, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $newId,
                $propertyId,
                $property,
                $input['tag_number'] ?? null,
                $input['plate_number'] ?? null,
                $input['state'] ?? null,
                $input['make'] ?? null,
                $input['model'] ?? null,
                $input['color'] ?? null,
                $input['year'] ?? null,
                $input['apt_number'] ?? null,
                $input['owner_name'] ?? null,
                $input['owner_phone'] ?? null,
                $input['owner_email'] ?? null,
                $input['reserved_space'] ?? null
            ]);
        }
        
        $identifier = $input['tag_number'] ?: $input['plate_number'] ?: "New Vehicle";
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
