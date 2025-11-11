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
$fineAmount = isset($data['fine_amount']) && $data['fine_amount'] !== '' ? floatval($data['fine_amount']) : null;
$towDeadlineHours = isset($data['tow_deadline_hours']) && $data['tow_deadline_hours'] !== '' ? intval($data['tow_deadline_hours']) : null;
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
        SET name = ?, fine_amount = ?, tow_deadline_hours = ?, display_order = ?, is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $fineAmount, $towDeadlineHours, $displayOrder, $isActive ? 1 : 0, $id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Violation not found']);
        exit;
    }
    
    $details = "Updated violation: $name";
    if ($fineAmount !== null) {
        $details .= ", fine: \$$fineAmount";
    }
    if ($towDeadlineHours !== null) {
        $details .= ", tow deadline: {$towDeadlineHours}hrs";
    }
    
    if (function_exists('auditLog')) { try { auditLog('update_violation', 'violations', $id, $details); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Violation update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
