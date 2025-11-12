<?php
/**
 * Logout API Endpoint
 * POST /api/logout
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


requireAuth();

if (function_exists('auditLog')) {
    try {
        auditLog('logout', 'user', Session::userId());
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

Session::logout();

jsonResponse(['message' => 'Logged out successfully']);
