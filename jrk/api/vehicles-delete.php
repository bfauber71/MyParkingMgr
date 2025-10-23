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

if (strcasecmp($user['role'], 'operator') === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Operators cannot delete vehicles']);
    exit;
}

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
    auditLog('delete_vehicle', 'vehicles', $vehicleId, "Deleted vehicle: $identifier");
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
