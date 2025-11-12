<?php
/**
 * Application Configuration API
 * Returns non-sensitive configuration for frontend use
 * INCLUDES LICENSE STATUS (shared-hosting safe approach)
 */

require_once __DIR__ . '/../includes/config-loader.php';

// Allow unauthenticated access to basic config
header('Content-Type: application/javascript');
header('Cache-Control: public, max-age=3600');

$config = ConfigLoader::load();
$basePath = ConfigLoader::getBasePath();
$apiBase = ConfigLoader::getApiBase();

// Get license status (safe fallback if license system fails)
$licenseData = ['status' => 'active', 'warnings' => []];
try {
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/license.php';
    
    // Only check license status if database is available
    $pdo = Database::getInstance();
    if ($pdo !== null) {
        $licenseStatus = License::getStatus();
        $licenseData = [
            'status' => $licenseStatus['status'] ?? 'active',
            'warnings' => $licenseStatus['warnings'] ?? [],
            'customer_email' => $licenseStatus['customer_email'] ?? null
        ];
    }
} catch (Exception $e) {
    // If license check fails, continue with default (active)
    // This ensures app remains functional even if license system has issues
}

// Generate JavaScript configuration
echo "// MyParkingManager Configuration\n";
echo "window.MPM_CONFIG = {\n";
echo "    basePath: " . json_encode($basePath) . ",\n";
echo "    apiBase: " . json_encode($apiBase) . ",\n";
echo "    appName: " . json_encode($config['app_name'] ?? 'MyParkingManager') . ",\n";
echo "    license: " . json_encode($licenseData) . "\n";
echo "};\n";
echo "\n";
echo "// Legacy support\n";
echo "if (typeof API_BASE === 'undefined') {\n";
echo "    window.API_BASE = window.MPM_CONFIG.apiBase;\n";
echo "}\n";