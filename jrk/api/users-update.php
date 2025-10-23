<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

Session::start();

if (!Session::isAuthenticated()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$user = Session::user();

if (strcasecmp($user['role'], 'admin') !== 0) {
    jsonResponse(['error' => 'Access denied. Admin only.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$id = trim($input['id'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = strtolower($input['role'] ?? 'user');

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
    
    if (!empty($password)) {
        $config = require __DIR__ . '/../config.php';
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $config['password_cost']]);
        
        $stmt = $db->prepare("
            UPDATE users 
            SET username = ?, email = ?, password = ?, role = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $passwordHash, $role, $id]);
    } else {
        $stmt = $db->prepare("
            UPDATE users 
            SET username = ?, email = ?, role = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $role, $id]);
    }
    
    auditLog('update_user', 'users', $id, "Updated user: $username ($role)");
    
    jsonResponse([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} catch (PDOException $e) {
    error_log("User Update Error: " . $e->getMessage());
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
