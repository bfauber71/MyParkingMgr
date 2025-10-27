<?php
/**
 * License Status API
 * GET /api/license-status
 * Returns current license status and trial information
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/license.php';

// No authentication required - license status is public within the app
header('Content-Type: application/json');

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
        'message' => $e->getMessage()
    ], 500);
}