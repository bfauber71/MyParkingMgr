<?php
/**
 * License Status API v2 - Simplified for shared hosting
 * GET /api/license-status-v2
 */

// Force no caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json');

// Clear OPcache if available
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

// Error handling for shared hosting
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Check if required files exist before including
    $requiredFiles = [
        __DIR__ . '/../includes/database.php',
        __DIR__ . '/../includes/helpers.php',
        __DIR__ . '/../includes/license.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file missing: " . basename($file));
        }
    }
    
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/helpers.php';
    require_once __DIR__ . '/../includes/license.php';
    
    // Check if License class exists
    if (!class_exists('License')) {
        throw new Exception("License class not found");
    }
    
    $status = License::getStatus();
    
    $response = [
        'success' => true,
        'license' => $status,
        'features' => License::getRestrictedFeatures(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($status['status'] === 'trial' && isset($status['days_remaining'])) {
        if ($status['days_remaining'] <= 7) {
            $response['warning'] = "Your trial expires in {$status['days_remaining']} days.";
        }
    } elseif ($status['status'] === 'expired') {
        $response['warning'] = "Your trial has expired.";
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return default unlicensed status if database fails
    // This ensures the UI always shows something instead of breaking
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'license' => [
            'status' => 'unlicensed',
            'install_id' => 'N/A',
            'licensed_to' => null,
            'trial_expires_at' => null,
            'days_remaining' => null,
            'activation_date' => null,
            'license_key_prefix' => null
        ],
        'features' => [],
        'timestamp' => date('Y-m-d H:i:s'),
        'warning' => 'Database connection required to check license status'
    ]);
}
