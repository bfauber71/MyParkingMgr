<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require authentication and view permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_VIEW);

$user = Session::user();

try {
    // Use Database static methods instead of getInstance
    $violations = Database::query("
        SELECT id, name, fine_amount, tow_deadline_hours, is_active, display_order
        FROM violations
        ORDER BY display_order ASC, name ASC
    ");
    
    foreach ($violations as &$violation) {
        $violation['is_active'] = (bool)$violation['is_active'];
        $violation['fine_amount'] = $violation['fine_amount'] !== null ? (float)$violation['fine_amount'] : null;
        $violation['tow_deadline_hours'] = $violation['tow_deadline_hours'] !== null ? (int)$violation['tow_deadline_hours'] : null;
    }
    
    echo json_encode(['violations' => $violations]);
} catch (Exception $e) {
    error_log("Violations list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
