<?php
/**
 * MyParkingManager - Front Controller
 * All requests are routed through this file
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if this is a direct access to setup.php or setup-wizard.php
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/setup.php') !== false) {
    // Redirect to actual setup.php file
    require_once __DIR__ . '/setup.php';
    exit;
}
if (strpos($requestUri, '/setup-wizard.php') !== false) {
    // Redirect to actual setup-wizard.php file
    require_once __DIR__ . '/setup-wizard.php';
    exit;
}
if (strpos($requestUri, '/setup-test-db.php') !== false) {
    // Redirect to actual setup-test-db.php file  
    require_once __DIR__ . '/setup-test-db.php';
    exit;
}

// Include dependencies
require_once __DIR__ . '/includes/config-loader.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/router.php';
require_once __DIR__ . '/includes/license.php';
require_once __DIR__ . '/includes/middleware.php';

// Start session
Session::start();

// Check license access for protected endpoints
checkLicenseAccess();

// CORS headers (adjust for your domain in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router with dynamic configuration
$config = ConfigLoader::load();
$router = new Router(ConfigLoader::getBasePath());

// Configuration route (for frontend)
$router->get('/api/app-config', __DIR__ . '/api/app-config.php');

// API Routes
$router->post('/api/login', __DIR__ . '/api/login.php');
$router->post('/api/logout', __DIR__ . '/api/logout.php');
$router->get('/api/user', __DIR__ . '/api/user.php');

// License routes
$router->get('/api/license-status', __DIR__ . '/api/license-status.php');
$router->post('/api/license-activate', __DIR__ . '/api/license-activate.php');

// Vehicle routes
$router->get('/api/vehicles-search', __DIR__ . '/api/vehicles-search.php');
$router->get('/api/vehicles/search', __DIR__ . '/api/vehicles-search.php');
$router->get('/api/vehicles-export', __DIR__ . '/api/vehicles-export.php');
$router->get('/api/vehicles/export', __DIR__ . '/api/vehicles-export.php');
$router->get('/api/vehicles-violations-history', __DIR__ . '/api/vehicles-violations-history.php');
$router->post('/api/vehicles', __DIR__ . '/api/vehicles-create.php');
$router->post('/api/vehicles-create', __DIR__ . '/api/vehicles-create.php');
$router->post('/api/vehicles-import', __DIR__ . '/api/vehicles-import.php');
$router->post('/api/vehicles-delete', __DIR__ . '/api/vehicles-delete.php');

// Database/Bulk Operation routes
$router->delete('/api/vehicles-bulk-delete', __DIR__ . '/api/vehicles-bulk-delete.php');
$router->post('/api/vehicles-duplicates', __DIR__ . '/api/vehicles-duplicates.php');

// Property routes
$router->get('/api/properties', __DIR__ . '/api/properties.php');
$router->get('/api/properties-list', __DIR__ . '/api/properties-list.php');
$router->post('/api/properties-create', __DIR__ . '/api/properties-create.php');
$router->post('/api/properties-update', __DIR__ . '/api/properties-update.php');
$router->post('/api/properties-delete', __DIR__ . '/api/properties-delete.php');

// User routes (Admin only)
$router->get('/api/users-list', __DIR__ . '/api/users-list.php');
$router->post('/api/users-create', __DIR__ . '/api/users-create.php');
$router->post('/api/users-update', __DIR__ . '/api/users-update.php');
$router->post('/api/users-delete', __DIR__ . '/api/users-delete.php');

// Violation routes
$router->get('/api/violations', __DIR__ . '/api/violations.php');
$router->post('/api/violations-create', __DIR__ . '/api/violations-create.php');
$router->get('/api/violations-ticket', __DIR__ . '/api/violations-ticket.php');
$router->get('/api/violations-list', __DIR__ . '/api/violations-list.php');
$router->post('/api/violations-add', __DIR__ . '/api/violations-add.php');
$router->post('/api/violations-update', __DIR__ . '/api/violations-update.php');
$router->post('/api/violations-delete', __DIR__ . '/api/violations-delete.php');
$router->post('/api/violations-search', __DIR__ . '/api/violations-search.php');
$router->post('/api/violations-export', __DIR__ . '/api/violations-export.php');

// Serve frontend for all other routes
$router->get('/.*', function() use ($config) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Serve static assets directly
    if (preg_match('/\.(html|js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $uri)) {
        // Remove base_path from URI to get relative path
        $relativePath = str_replace($config['base_path'], '', $uri);
        
        $file = __DIR__ . $relativePath;
        if (file_exists($file)) {
            // Proper MIME types for static assets
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $mimeTypes = [
                'html' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            $mime = $mimeTypes[$extension] ?? 'application/octet-stream';
            header('Content-Type: ' . $mime);
            readfile($file);
            exit;
        }
        http_response_code(404);
        exit;
    }
    
    // Serve index.html for all other routes with injected config
    $indexFile = __DIR__ . DIRECTORY_SEPARATOR . 'index.html';
    
    if (file_exists($indexFile)) {
        header('Content-Type: text/html');
        $html = file_get_contents($indexFile);
        
        // Inject config into HTML as script variable
        $configScript = sprintf(
            '<script>window.APP_CONFIG = %s;</script>',
            json_encode([
                'basePath' => $config['base_path'],
                'appName' => $config['app_name']
            ])
        );
        
        // Insert before closing </head> tag
        $html = str_replace('</head>', $configScript . "\n</head>", $html);
        
        echo $html;
    } else {
        jsonResponse(['error' => 'Application not found. Please run frontend build.'], 404);
    }
});

// Dispatch request
$router->dispatch();
