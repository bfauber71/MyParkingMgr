<?php
/**
 * API Initialization - MUST be included at the top of every API endpoint
 * This file ensures all security headers and protections are applied globally
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';

// Start session
Session::start();

// Set security headers globally
Security::setSecurityHeaders();

// Set JSON content type for all API responses
header('Content-Type: application/json');
