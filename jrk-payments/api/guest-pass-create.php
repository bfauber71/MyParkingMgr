<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


// Require authentication (includes CSRF validation)
requireAuth();

// Check permission: guest pass creation requires create_delete permission on vehicles
requirePermission(MODULE_VEHICLES, ACTION_CREATE_DELETE);

$input = json_decode(file_get_contents('php://input'), true);
$user = Session::user();

$property = trim($input['property'] ?? '');
$plateNumber = trim($input['plate_number'] ?? '');

if (empty($property) || empty($plateNumber)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property and Plate Number are required']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if new columns exist (for backward compatibility)
    $hasResidentFields = false;
    $hasExpirationField = false;
    
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM vehicles LIKE 'resident'");
        $hasResidentFields = $checkStmt->rowCount() > 0;
        
        $checkStmt2 = $db->query("SHOW COLUMNS FROM vehicles LIKE 'expiration_date'");
        $hasExpirationField = $checkStmt2->rowCount() > 0;
    } catch (PDOException $e) {
        $hasResidentFields = false;
        $hasExpirationField = false;
    }
    
    // Get property details
    $stmt = $db->prepare("SELECT * FROM properties WHERE name = ?");
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
    
    // Calculate expiration date (7 days from now)
    $expirationDate = isset($input['expiration_date']) ? $input['expiration_date'] : date('Y-m-d', strtotime('+7 days'));
    
    $newId = Database::uuid();
    
    // Build INSERT query based on available fields
    if ($hasResidentFields && $hasExpirationField) {
        // Full schema with resident/guest and expiration fields
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space,
                resident, guest, guest_of, expiration_date, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $newId,
            $property,
            'GUEST', // Always set tag_number to GUEST for guest passes
            $plateNumber,
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
            0, // resident = false for guest pass
            1, // guest = true for guest pass
            $input['guest_of'] ?? null,
            $expirationDate
        ]);
    } elseif ($hasResidentFields && !$hasExpirationField) {
        // Schema with resident/guest but no expiration
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space,
                resident, guest, guest_of, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $newId,
            $property,
            'GUEST', // Always set tag_number to GUEST for guest passes
            $plateNumber,
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
            0, // resident = false for guest pass
            1, // guest = true for guest pass
            $input['guest_of'] ?? null
        ]);
    } else {
        // Old schema without resident/guest or expiration fields
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $newId,
            $property,
            'GUEST', // Always set tag_number to GUEST for guest passes
            $plateNumber,
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
    
    // Fetch the created vehicle
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$newId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Audit log
    if (function_exists('auditLog')) { 
        try { 
            auditLog('create_guest_pass', 'vehicles', $newId, "Created guest pass for: $plateNumber"); 
        } catch (Exception $e) { 
            error_log("Audit log error: " . $e->getMessage()); 
        } 
    }
    
    echo json_encode([
        'success' => true,
        'vehicle' => $vehicle,
        'property' => $propertyData,
        'message' => 'Guest pass created successfully'
    ]);
} catch (PDOException $e) {
    error_log("Guest pass creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
