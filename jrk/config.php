<?php
/**
 * MyParkingManager Configuration
 * Edit these settings for your hosting environment
 * OR use setup.php for guided configuration
 */

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    'app_url' => '',  // Full URL to your application (set via setup wizard)
    'base_path' => '',  // Subdirectory path or empty string for root (auto-detected)
    
    // Database Configuration
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'myparkingmanager',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    
    // Session Configuration
    'session' => [
        'name' => 'myparkingmanager_session',
        'lifetime' => 1440, // 24 hours in minutes
        'secure' => 'auto', // Auto-detect HTTPS (use true for production with HTTPS)
        'httponly' => true,
    ],
    
    // Security
    'password_cost' => 10, // bcrypt cost factor
    'setup_token' => 'reconfigure', // Token required to access setup.php after initial configuration
    
    // File Upload
    'max_upload_size' => 52428800, // 50MB in bytes
    'max_csv_rows' => 10000,
];
