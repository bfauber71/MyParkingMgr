<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

// Require authentication and create/delete permission for users
requirePermission(MODULE_USERS, ACTION_CREATE_DELETE);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = strtolower($input['role'] ?? 'user');
$permissions = $input['permissions'] ?? [];
$assignedProperties = $input['assigned_properties'] ?? [];

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

if (!in_array($role, ['admin', 'user', 'operator'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }
    
    $config = require __DIR__ . '/../config.php';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $config['password_cost']]);
    
    // Generate UUID for user ID
    $userId = $db->query("SELECT UUID()")->fetchColumn();
    
    $stmt = $db->prepare("
        INSERT INTO users (id, username, email, password, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $username, $email, $passwordHash, $role]);
    
    // Save user permissions
    saveUserPermissions($userId, $permissions);
    
    // Save assigned properties
    saveUserAssignedProperties($userId, $assignedProperties);
    
    if (function_exists('auditLog')) {
        try {
            auditLog('create_user', 'users', $userId, "Created user: $username ($role)");
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'id' => $userId,
        'message' => 'User created successfully'
    ]);
} catch (PDOException $e) {
    error_log("User Create Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create user']);
}
