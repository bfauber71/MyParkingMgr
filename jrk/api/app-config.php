<?php
/**
 * Application Configuration API
 * Returns non-sensitive configuration for frontend use
 */

require_once __DIR__ . '/../includes/config-loader.php';

// Allow unauthenticated access to basic config
header('Content-Type: application/javascript');
header('Cache-Control: public, max-age=3600');

$config = ConfigLoader::load();
$basePath = ConfigLoader::getBasePath();
$apiBase = ConfigLoader::getApiBase();

// Generate JavaScript configuration
echo "// MyParkingManager Configuration\n";
echo "window.MPM_CONFIG = {\n";
echo "    basePath: " . json_encode($basePath) . ",\n";
echo "    apiBase: " . json_encode($apiBase) . ",\n";
echo "    appName: " . json_encode($config['app_name'] ?? 'MyParkingManager') . "\n";
echo "};\n";
echo "\n";
echo "// Legacy support\n";
echo "if (typeof API_BASE === 'undefined') {\n";
echo "    window.API_BASE = window.MPM_CONFIG.apiBase;\n";
echo "}\n";