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

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = strtolower($input['role'] ?? 'user');

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
    
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$username, $email, $passwordHash, $role]);
    
    $userId = $db->lastInsertId();
    
    auditLog('create_user', 'users', $userId, "Created user: $username ($role)");
    
    echo json_encode([
        'success' => true,
        'id' => $userId,
        'message' => 'User created successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
