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
    
    $id = intval($input['id']);
    $tag_number = sanitizeInput($input['tag_number']);
    $state = sanitizeInput($input['state']);
    $make = isset($input['make']) ? sanitizeInput($input['make']) : '';
    $model = isset($input['model']) ? sanitizeInput($input['model']) : '';
    $color = isset($input['color']) ? sanitizeInput($input['color']) : '';
    $year = isset($input['year']) ? intval($input['year']) : null;
    $property_id = isset($input['property_id']) && $input['property_id'] ? intval($input['property_id']) : null;
    $resident_name = isset($input['resident_name']) ? sanitizeInput($input['resident_name']) : '';
    $unit_number = isset($input['unit_number']) ? sanitizeInput($input['unit_number']) : '';
    $notes = isset($input['notes']) ? sanitizeInput($input['notes']) : '';
    
    // Validate tag number format
    if (!preg_match('/^[A-Z0-9\-]+$/i', $tag_number)) {
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
            state = ?,
            make = ?,
            model = ?,
            color = ?,
            year = ?,
            property_id = ?,
            resident_name = ?,
            unit_number = ?,
            notes = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tag_number,
        $state,
        $make,
        $model,
        $color,
        $year,
        $property_id,
        $resident_name,
        $unit_number,
        $notes,
        $id
    ]);
    
    // Log the action
    logAudit('update_vehicle', [
        'vehicle_id' => $id,
        'tag_number' => $tag_number,
        'state' => $state
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle updated successfully',
        'vehicle_id' => $id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
