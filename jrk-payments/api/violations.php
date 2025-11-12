<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        SELECT id, name, fine_amount, tow_deadline_hours, display_order
        FROM violations
        WHERE is_active = 1
        ORDER BY display_order ASC, name ASC
    ");
    $stmt->execute();
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['violations' => $violations]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
