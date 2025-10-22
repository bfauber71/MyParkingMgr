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
$router->get('/api/vehicles/search', __DIR__ . '/api/vehicles-search.php');
$router->get('/api/vehicles/export', __DIR__ . '/api/vehicles-export.php');
$router->post('/api/vehicles', __DIR__ . '/api/vehicles-create.php');

// Property routes
$router->get('/api/properties', __DIR__ . '/api/properties.php');

// Serve frontend for all other routes
$router->get('/.*', function() use ($config) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Serve static assets directly
    if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $uri)) {
        $file = __DIR__ . '/public' . str_replace($config['base_path'], '', $uri);
        if (file_exists($file)) {
            $mime = mime_content_type($file);
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
