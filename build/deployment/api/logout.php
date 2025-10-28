<?php
/**
 * Logout API Endpoint
 * POST /api/logout
 */

requireAuth();

if (function_exists('auditLog')) { try { auditLog('logout', 'user', Session::userId());

Session::logout();

jsonResponse(['message' => 'Logged out successfully']);
