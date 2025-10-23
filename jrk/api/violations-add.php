<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and create/delete permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_CREATE_DELETE);

$user = Session::user();

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$displayOrder = (int)($data['display_order'] ?? 0);
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Violation name is required']);
    exit;
}

$db = Database::getInstance();

try {
    $id = Database::uuid();
    
    $stmt = $db->prepare("
        INSERT INTO violations (id, name, display_order, is_active)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$id, $name, $displayOrder, $isActive ? 1 : 0]);
    
    auditLog('create_violation', 'violations', $id, "Created violation: $name");
    
    echo json_encode([
        'success' => true,
        'id' => $id
    ]);
} catch (PDOException $e) {
    error_log("Violation create error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
