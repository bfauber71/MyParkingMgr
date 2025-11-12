<?php
/**
 * Get Current User API Endpoint
 * GET /api/user
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';

requireAuth();

$user = Session::user();

jsonResponse([
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'email' => $user['email'] ?? '',
        'permissions' => $user['permissions'] ?? []
    ]
]);
