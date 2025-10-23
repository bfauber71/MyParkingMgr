<?php
/**
 * ManageMyParking - Front Controller
 * All requests are routed through this file
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include dependencies
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/router.php';

// Start session
Session::start();

// CORS headers (adjust for your domain in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router
$config = require __DIR__ . '/config.php';
$router = new Router($config['base_path']);

// API Routes
$router->post('/api/login', __DIR__ . '/api/login.php');
$router->post('/api/logout', __DIR__ . '/api/logout.php');
$router->get('/api/user', __DIR__ . '/api/user.php');

// Vehicle routes
$router->get('/api/vehicles-search', __DIR__ . '/api/vehicles-search.php');
$router->get('/api/vehicles/search', __DIR__ . '/api/vehicles-search.php');
$router->get('/api/vehicles-export', __DIR__ . '/api/vehicles-export.php');
$router->get('/api/vehicles/export', __DIR__ . '/api/vehicles-export.php');
$router->post('/api/vehicles', __DIR__ . '/api/vehicles-create.php');
$router->post('/api/vehicles-create', __DIR__ . '/api/vehicles-create.php');
$router->post('/api/vehicles-import', __DIR__ . '/api/vehicles-import.php');
$router->post('/api/vehicles-delete', __DIR__ . '/api/vehicles-delete.php');

// Property routes
$router->get('/api/properties', __DIR__ . '/api/properties.php');
$router->get('/api/properties-list', __DIR__ . '/api/properties-list.php');
$router->post('/api/properties-create', __DIR__ . '/api/properties-create.php');
$router->post('/api/properties-update', __DIR__ . '/api/properties-update.php');
$router->post('/api/properties-delete', __DIR__ . '/api/properties-delete.php');

// User routes (Admin only)
$router->get('/api/users-list', __DIR__ . '/api/users-list.php');
$router->post('/api/users-create', __DIR__ . '/api/users-create.php');
$router->post('/api/users-delete', __DIR__ . '/api/users-delete.php');

// Violation routes
$router->get('/api/violations', __DIR__ . '/api/violations.php');
$router->post('/api/violations-create', __DIR__ . '/api/violations-create.php');
$router->get('/api/violations-ticket', __DIR__ . '/api/violations-ticket.php');

// Serve frontend for all other routes
$router->get('/.*', function() use ($config) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Serve static assets directly
    if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $uri)) {
        $file = __DIR__ . '/public' . str_replace($config['base_path'], '', $uri);
        if (file_exists($file)) {
            // Proper MIME types for static assets
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $mimeTypes = [
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
    
    // Serve index.html for all other routes
    $indexFile = __DIR__ . '/public/index.html';
    if (file_exists($indexFile)) {
        header('Content-Type: text/html');
        readfile($indexFile);
    } else {
        jsonResponse(['error' => 'Application not found. Please run frontend build.'], 404);
    }
});

// Dispatch request
$router->dispatch();
