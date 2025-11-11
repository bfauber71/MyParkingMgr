<?php
/**
 * License Middleware
 * Enforces license restrictions on API endpoints
 */

require_once __DIR__ . '/license.php';

/**
 * Check if the current request requires a valid license
 */
function checkLicenseAccess() {
    // Get current request path
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    
    // Define endpoints that require valid license
    $restrictedEndpoints = [
        '/api/vehicles-create',
        '/api/vehicles-delete',
        '/api/vehicles-import',
        '/api/vehicles-export',
        '/api/vehicles-bulk-delete',
        '/api/violations-create',
        '/api/violations-ticket',
        '/api/violations-export',
        '/api/properties-create',
        '/api/properties-update',
        '/api/properties-delete',
        '/api/users-create',
        '/api/users-update',
        '/api/users-delete'
    ];
    
    // Always allow essential endpoints
    $allowedEndpoints = [
        '/api/login',
        '/api/logout',
        '/api/user',
        '/api/license-status',
        '/api/license-activate',
        '/api/vehicles-search', // Read-only access
        '/api/properties-list', // Read-only access
        '/api/users-list' // Read-only access
    ];
    
    // Check if endpoint is always allowed
    foreach ($allowedEndpoints as $allowed) {
        if (strpos($requestPath, $allowed) !== false) {
            return true;
        }
    }
    
    // Check license status
    $licenseStatus = License::getStatus();
    
    // If license is valid (trial or licensed), allow access
    if (isset($licenseStatus['is_valid']) && $licenseStatus['is_valid'] === true) {
        return true;
    }
    
    // Check if this is a restricted endpoint
    foreach ($restrictedEndpoints as $restricted) {
        if (strpos($requestPath, $restricted) !== false) {
            // License required but not valid
            jsonResponse([
                'error' => 'License Required',
                'message' => 'This feature requires an active license. Your trial has expired.',
                'license_status' => $licenseStatus['status'],
                'action_required' => 'Please activate a license key to continue using this feature.'
            ], 402); // 402 Payment Required
        }
    }
    
    // Default allow for non-restricted endpoints
    return true;
}

/**
 * Check if a specific feature is available
 */
function requireLicenseForFeature($feature) {
    if (!License::hasFeatureAccess($feature)) {
        $status = License::getStatus();
        jsonResponse([
            'error' => 'Feature Restricted',
            'message' => "The '{$feature}' feature requires an active license.",
            'license_status' => $status['status'],
            'trial_expired' => $status['status'] === 'expired',
            'action_required' => 'Please activate a license key to access this feature.'
        ], 402);
    }
}