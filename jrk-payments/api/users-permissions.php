<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

Session::start();

// Require authentication and view permission for users
requirePermission(MODULE_USERS, ACTION_VIEW);

$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    jsonResponse(['error' => 'User ID is required'], 400);
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        SELECT module, can_view, can_edit, can_create_delete
        FROM user_permissions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $permissions = [];
    foreach ($results as $row) {
        if ($row['can_view']) {
            $permissions[] = ['module' => $row['module'], 'action' => 'view'];
        }
        if ($row['can_edit']) {
            $permissions[] = ['module' => $row['module'], 'action' => 'edit'];
        }
        if ($row['can_create_delete']) {
            $permissions[] = ['module' => $row['module'], 'action' => 'create_delete'];
        }
    }
    
    jsonResponse([
        'success' => true,
        'permissions' => $permissions
    ]);
} catch (PDOException $e) {
    error_log("Error fetching user permissions: " . $e->getMessage());
    jsonResponse(['error' => 'Database error'], 500);
}
