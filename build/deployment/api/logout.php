<?php
/**
 * Logout API Endpoint
 * POST /api/logout
 */

requireAuth();

auditLog('logout', 'user', Session::userId());

Session::logout();

jsonResponse(['message' => 'Logged out successfully']);
