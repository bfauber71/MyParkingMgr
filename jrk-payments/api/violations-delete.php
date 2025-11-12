<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

// Require authentication and create/delete permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_CREATE_DELETE);

$user = Session::user();

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Violation ID is required']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT name FROM violations WHERE id = ?");
    $stmt->execute([$id]);
    $violation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$violation) {
        http_response_code(404);
        echo json_encode(['error' => 'Violation not found']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM violations WHERE id = ?");
    $stmt->execute([$id]);
    
    if (function_exists('auditLog')) { try { auditLog('delete_violation', 'violations', $id, "Deleted violation: {$violation['name']}"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Violation delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error. Cannot delete violations that are in use.']);
}
