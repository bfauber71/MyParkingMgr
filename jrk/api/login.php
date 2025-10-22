<?php
/**
 * Login API Endpoint
 * POST /api/login
 */

$data = getJsonInput();

// Validate input
$missing = validateRequired($data, ['username', 'password']);
if (!empty($missing)) {
    jsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
}

$username = sanitize($data['username']);
$password = $data['password'];

// Find user
$sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
$user = Database::queryOne($sql, [$username]);

if (!$user || !password_verify($password, $user['password'])) {
    auditLog('login_failed', 'user', null, ['username' => $username]);
    jsonResponse(['error' => 'Invalid credentials'], 401);
}

// Login successful
Session::login($user);

auditLog('login', 'user', $user['id']);

jsonResponse([
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ]
]);
