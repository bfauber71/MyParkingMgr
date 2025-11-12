<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

// Require authentication and create/delete permission for vehicles
requirePermission(MODULE_VEHICLES, ACTION_CREATE_DELETE);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);
$vehicleId = trim($input['id'] ?? '');

if (empty($vehicleId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid vehicle ID']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT tag_number, plate_number FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    
    $identifier = $vehicle['tag_number'] ?: $vehicle['plate_number'] ?: "ID $vehicleId";
    if (function_exists('auditLog')) { try { auditLog('delete_vehicle', 'vehicles', $vehicleId, "Deleted vehicle: $identifier"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
