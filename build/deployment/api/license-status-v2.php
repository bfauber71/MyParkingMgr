<?php
/**
 * License Status API v2 - Cache-busted version
 * GET /api/license-status-v2
 * Returns current license status and trial information
 */

// Force no caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json');

// Clear OPcache for this file if possible
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/license.php';

try {
    $status = License::getStatus();
    
    // Add additional information for frontend
    $response = [
        'success' => true,
        'license' => $status,
        'features' => License::getRestrictedFeatures(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add warning messages if applicable
    if ($status['status'] === 'trial' && isset($status['days_remaining'])) {
        if ($status['days_remaining'] <= 7) {
            $response['warning'] = "Your trial expires in {$status['days_remaining']} days. Please enter a license key to continue using all features.";
        }
    } elseif ($status['status'] === 'expired') {
        $response['warning'] = "Your trial has expired. Please enter a license key to continue using premium features.";
    }
    
    jsonResponse($response);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => 'Failed to retrieve license status',
        'message' => $e->getMessage(),
        'debug' => $e->getTraceAsString()
    ], 500);
}
