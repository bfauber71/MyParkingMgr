<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = Session::user();
$role = strtolower($user['role']);

if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        SELECT id, name, is_active, display_order
        FROM violations
        ORDER BY display_order ASC, name ASC
    ");
    $stmt->execute();
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($violations as &$violation) {
        $violation['is_active'] = (bool)$violation['is_active'];
    }
    
    echo json_encode(['violations' => $violations]);
} catch (PDOException $e) {
    error_log("Violations list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
