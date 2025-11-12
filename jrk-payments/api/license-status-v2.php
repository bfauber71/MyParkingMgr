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
    // Return default trial status if database fails
    // Assume 30-day trial from installation
    // This ensures the UI always shows something instead of breaking
    
    // Calculate trial expiration (30 days from config file modified date or current date)
    $configFile = __DIR__ . '/../config.php';
    $installDate = file_exists($configFile) ? filemtime($configFile) : time();
    $trialExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days', $installDate));
    
    // Calculate days remaining
    $now = new DateTime();
    $expiresDate = new DateTime($trialExpiresAt);
    $interval = $now->diff($expiresDate);
    $daysRemaining = $interval->invert ? 0 : $interval->days;
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'license' => [
            'status' => 'trial',
            'install_id' => 'N/A',
            'licensed_to' => null,
            'trial_expires_at' => $trialExpiresAt,
            'days_remaining' => $daysRemaining,
            'activation_date' => null,
            'license_key_prefix' => null
        ],
        'features' => [],
        'timestamp' => date('Y-m-d H:i:s'),
        'warning' => 'Database connection unavailable - showing trial status'
    ]);
}
