<?php
/**
 * MyParkingManager Configuration
 * Edit these settings for your hosting environment
 */

// Auto-detect environment (Replit or development server)
$isReplit = getenv('REPL_ID') !== false || PHP_SAPI === 'cli-server';
$basePath = $isReplit ? '' : '/jrk';

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    'app_url' => $isReplit ? 'http://localhost:5000' : 'https://2clv.com/jrk',
    'base_path' => $basePath,
    
    // Database Configuration
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'myparkingmanager',
        'username' => 'your_db_username',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    
    // Session Configuration
    'session' => [
        'name' => 'myparkingmanager_session',
        'lifetime' => 1440, // 24 hours in minutes
        'secure' => true, // HTTPS required (recommended for production)
        'httponly' => true,
    ],
    
    // Security
    'password_cost' => 10, // bcrypt cost factor
    
    // File Upload
    'max_upload_size' => 52428800, // 50MB in bytes
    'max_csv_rows' => 10000,
];
