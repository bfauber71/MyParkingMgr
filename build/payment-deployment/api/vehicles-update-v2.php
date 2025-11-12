<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

// Require authentication (includes CSRF validation)
requireAuth();

// Check permissions
requirePermission(MODULE_VEHICLES, ACTION_EDIT);

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    // Validate required fields
    $required = ['id', 'tag_number', 'state'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $id = $input['id']; // Keep as string (UUID)
    $tag_number = sanitizeInput($input['tag_number']);
    $state = sanitizeInput($input['state']);
    $make = isset($input['make']) ? sanitizeInput($input['make']) : '';
    $model = isset($input['model']) ? sanitizeInput($input['model']) : '';
    $color = isset($input['color']) ? sanitizeInput($input['color']) : '';
    $year = isset($input['year']) ? sanitizeInput($input['year']) : '';
    $property = isset($input['property']) ? sanitizeInput($input['property']) : '';
    $owner_name = isset($input['owner_name']) ? sanitizeInput($input['owner_name']) : '';
    $apt_number = isset($input['apt_number']) ? sanitizeInput($input['apt_number']) : '';
    $owner_phone = isset($input['owner_phone']) ? sanitizeInput($input['owner_phone']) : '';
    $owner_email = isset($input['owner_email']) ? sanitizeInput($input['owner_email']) : '';
    $reserved_space = isset($input['reserved_space']) ? sanitizeInput($input['reserved_space']) : '';
    $plate_number = isset($input['plate_number']) ? sanitizeInput($input['plate_number']) : '';
    
    // Validate tag number format (allow letters, numbers, hyphens, and spaces)
    if (!preg_match('/^[A-Z0-9\-\s]+$/i', $tag_number)) {
        throw new Exception('Invalid tag number format');
    }
    
    // Get database connection
    $pdo = Database::getInstance();
    
    // Check if vehicle exists
    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Vehicle not found');
    }
    
    // Check for duplicate tag number (excluding current vehicle)
    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE tag_number = ? AND state = ? AND id != ?");
    $stmt->execute([$tag_number, $state, $id]);
    if ($stmt->fetch()) {
        throw new Exception('A vehicle with this tag number and state already exists');
    }
    
    // Update the vehicle
    $sql = "UPDATE vehicles SET 
            tag_number = ?,
            plate_number = ?,
            state = ?,
            make = ?,
            model = ?,
            color = ?,
            year = ?,
            property = ?,
            owner_name = ?,
            apt_number = ?,
            owner_phone = ?,
            owner_email = ?,
            reserved_space = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tag_number,
        $plate_number,
        $state,
        $make,
        $model,
        $color,
        $year,
        $property,
        $owner_name,
        $apt_number,
        $owner_phone,
        $owner_email,
        $reserved_space,
        $id
    ]);
    
    // Log the action (safe - function may not exist on all systems)
    if (function_exists('logAudit')) {
        logAudit('update_vehicle', [
            'vehicle_id' => $id,
            'tag_number' => $tag_number,
            'state' => $state
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle updated successfully',
        'vehicle_id' => $id
    ]);
    
} catch (Exception $e) {
    error_log("Vehicle update error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
