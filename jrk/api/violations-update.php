<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and edit permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_EDIT);

$user = Session::user();

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? '';
$name = trim($data['name'] ?? '');
$displayOrder = (int)($data['display_order'] ?? 0);
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

if (empty($id) || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Violation ID and name are required']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        UPDATE violations 
        SET name = ?, display_order = ?, is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $displayOrder, $isActive ? 1 : 0, $id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Violation not found']);
        exit;
    }
    
    auditLog('update_violation', 'violations', $id, "Updated violation: $name");
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Violation update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
