<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = Session::user();

if (strcasecmp($user['role'], 'admin') !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id = trim($input['id'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = strtolower($input['role'] ?? 'user');

if (empty($id) || empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID and username are required']);
    exit;
}

if (!in_array($role, ['admin', 'user', 'operator'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
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
    
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} catch (PDOException $e) {
    error_log("User Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
