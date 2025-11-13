<?php
require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


header('Content-Type: application/json');

Session::start();

// Require authentication and edit permission for users
requirePermission(MODULE_USERS, ACTION_EDIT);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);

$id = trim($input['id'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = strtolower($input['role'] ?? 'user');
$permissions = $input['permissions'] ?? [];
$assignedProperties = $input['assigned_properties'] ?? [];

if (empty($id) || empty($username)) {
    jsonResponse(['error' => 'User ID and username are required'], 400);
}

if (!in_array($role, ['admin', 'user', 'operator'])) {
    jsonResponse(['error' => 'Invalid role'], 400);
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already exists'], 400);
    }
    
    // Check if email column exists (backward compatibility)
    $hasEmailColumn = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM users LIKE 'email'");
        $hasEmailColumn = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasEmailColumn = false;
    }
    
    if (!empty($password)) {
        $config = require __DIR__ . '/../config.php';
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $config['password_cost']]);
        
        if ($hasEmailColumn) {
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, email = ?, password = ?, role = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $passwordHash, $role, $id]);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, password = ?, role = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$username, $passwordHash, $role, $id]);
        }
    } else {
        if ($hasEmailColumn) {
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $role, $id]);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, role = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$username, $role, $id]);
        }
    }
    
    // Save user permissions
    saveUserPermissions($id, $permissions);
    
    // Save assigned properties
    saveUserAssignedProperties($id, $assignedProperties);
    
    if (function_exists('auditLog')) {
        try {
            auditLog('update_user', 'users', $id, "Updated user: $username ($role)");
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} catch (PDOException $e) {
    error_log("User Update Error: " . $e->getMessage());
    error_log("User Update SQL Error Code: " . $e->getCode());
    error_log("User Update Stack Trace: " . $e->getTraceAsString());
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
