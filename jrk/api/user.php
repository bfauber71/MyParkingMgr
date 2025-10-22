<?php
/**
 * Get Current User API Endpoint
 * GET /api/user
 */

requireAuth();

$user = Session::user();

jsonResponse([
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ]
]);
