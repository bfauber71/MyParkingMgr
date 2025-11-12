<?php
/**
 * CSRF Token Endpoint
 * GET /api/csrf-token
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

// Start session
Session::start();

// Generate and return CSRF token
$token = Security::generateCsrfToken();

jsonResponse([
    'token' => $token
]);