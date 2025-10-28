<?php
/**
 * License Activation API
 * POST /api/license-activate
 * Activates a license key
 */

require_once __DIR__ . '/../includes/license.php';

requireAdmin(); // Only admins can activate licenses

$data = getJsonInput();

// Validate input
if (empty($data['license_key'])) {
    jsonResponse(['error' => 'License key is required'], 400);
}

$licenseKey = trim($data['license_key']);
$customerEmail = isset($data['email']) ? trim($data['email']) : null;

// Attempt to activate the license
$result = License::activateLicense($licenseKey, $customerEmail);

if ($result['success']) {
    // Log successful activation
    auditLog('license_activated', 'license', License::getInstallId(), [
        'email' => $customerEmail,
        'key_prefix' => substr($licenseKey, 0, 10)
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => $result['message'],
        'status' => License::getStatus()
    ]);
} else {
    jsonResponse([
        'success' => false,
        'error' => $result['error']
    ], 400);
}