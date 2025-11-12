<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

// Require authentication and create/delete permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_CREATE_DELETE);

$user = Session::user();

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$fineAmount = isset($data['fine_amount']) && $data['fine_amount'] !== '' ? floatval($data['fine_amount']) : null;
$towDeadlineHours = isset($data['tow_deadline_hours']) && $data['tow_deadline_hours'] !== '' ? intval($data['tow_deadline_hours']) : null;
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
        INSERT INTO violations (id, name, fine_amount, tow_deadline_hours, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$id, $name, $fineAmount, $towDeadlineHours, $displayOrder, $isActive ? 1 : 0]);
    
    $details = "Created violation: $name";
    if ($fineAmount !== null) {
        $details .= ", fine: \$$fineAmount";
    }
    if ($towDeadlineHours !== null) {
        $details .= ", tow deadline: {$towDeadlineHours}hrs";
    }
    
    if (function_exists('auditLog')) { try { auditLog('create_violation', 'violations', $id, $details); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode([
        'success' => true,
        'id' => $id
    ]);
} catch (PDOException $e) {
    error_log("Violation create error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
