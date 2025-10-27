<?php
/**
 * Path Configuration API
 * Returns the current installation path for administrative purposes
 */

require_once __DIR__ . '/../includes/config-loader.php';
require_once __DIR__ . '/../includes/session.php';

Session::start();

// Only admins can view path configuration
if (!Session::isAuthenticated() || Session::user()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$config = ConfigLoader::load();
$response = [
    'app_name' => $config['app_name'] ?? 'MyParkingManager',
    'app_url' => $config['app_url'] ?? '',
    'base_path' => ConfigLoader::getBasePath(),
    'install_path' => ConfigLoader::getInstallPath(),
    'api_base' => ConfigLoader::getApiBase()
];

header('Content-Type: application/json');
echo json_encode($response);