<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and view permission for users
requirePermission(MODULE_USERS, ACTION_VIEW);

$user = Session::user();

$db = Database::getInstance();

// Get search parameter if provided
$search = $_GET['search'] ?? '';
$search = trim($search);

try {
    if (!empty($search)) {
        // Search by username or email
        $stmt = $db->prepare("
            SELECT id, username, email, role, created_at 
            FROM users 
            WHERE username LIKE :search 
               OR email LIKE :search
            ORDER BY created_at DESC
        ");
        $stmt->execute(['search' => '%' . $search . '%']);
    } else {
        // Return all users
        $stmt = $db->prepare("
            SELECT id, username, email, role, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['users' => $users]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
