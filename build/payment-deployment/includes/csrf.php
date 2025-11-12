<?php
/**
 * CSRF Token Validation Wrapper
 * Simple wrapper for backward compatibility
 */

require_once __DIR__ . '/security.php';

/**
 * Validate CSRF token from request
 * @throws Exception if validation fails
 */
function validateCsrfToken() {
    Security::validateRequest();
}
