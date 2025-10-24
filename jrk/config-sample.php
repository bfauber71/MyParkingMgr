<?php
/**
 * MyParkingManager Configuration Sample
 * Copy this file to config.php and update with your settings
 * OR use setup.php for guided configuration
 */

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    'app_url' => 'https://yourdomain.com/path',  // Full URL to your application
    'base_path' => '/path',  // Subdirectory path (e.g., /jrk) or empty string for root
    
    // Database Configuration
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'your_database_name',
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
    'setup_token' => 'reconfigure', // Token required to access setup.php after initial configuration
    
    // File Upload
    'max_upload_size' => 52428800, // 50MB in bytes
    'max_csv_rows' => 10000,
];
