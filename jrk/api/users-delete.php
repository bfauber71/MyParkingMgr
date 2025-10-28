<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and create/delete permission for users
requirePermission(MODULE_USERS, ACTION_CREATE_DELETE);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);
$userId = trim($input['id'] ?? '');

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

if ($userId === $user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot delete your own account']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    if (function_exists('auditLog')) { try { auditLog('delete_user', 'users', $userId, "Deleted user: {$targetUser['username']}"); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
