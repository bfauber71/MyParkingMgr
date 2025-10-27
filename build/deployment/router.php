<?php
/**
 * Router for PHP Built-in Web Server
 * This file routes requests when using: php -S 0.0.0.0:5000 router.php
 * 
 * For production (Apache/Nginx), use .htaccess or nginx config instead
 */

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove leading slash and base path if present
$uri = ltrim($uri, '/');

// If requesting the root, serve index.html
if (empty($uri) || $uri === 'index.html') {
    require 'index.html';
    return true;
}

// If requesting a static file that exists, serve it
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|json|txt|map)$/', $uri)) {
    $filePath = __DIR__ . '/' . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        // Add no-cache headers for JS and CSS to prevent caching issues
        if (preg_match('/\.(css|js)$/', $uri)) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        return false; // Let PHP serve the file
    }
}

// If requesting an API endpoint
if (strpos($uri, 'api/') === 0) {
    $apiFile = __DIR__ . '/' . $uri . '.php';
    if (file_exists($apiFile) && is_file($apiFile)) {
        require $apiFile;
        return true;
    }
    
    // 404 for missing API endpoints
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found: ' . $uri]);
    return true;
}

// If requesting violations-print.html or other HTML pages
if (file_exists(__DIR__ . '/' . $uri)) {
    return false; // Let PHP serve the file
}

// Default: serve index.html for SPA routing
if (file_exists(__DIR__ . '/index.html')) {
    require 'index.html';
    return true;
}

// 404 fallback
http_response_code(404);
echo '404 - Not Found';
return true;
