<?php
/**
 * MyParkingManager Configuration - Development
 */

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    'app_url' => 'http://localhost:5000',
    'base_path' => '',
    
    // Database Configuration - MySQL
    'db' => [
        'host' => 'your_db_host',
        'port' => '3306',
        'database' => 'your_db_name',
        'username' => 'your_db_username',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    
    // Session Configuration
    'session' => [
        'name' => 'myparkingmanager_session',
        'lifetime' => 1440,
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
    ],
    
    // Security
    'password_cost' => 10,
    'setup_token' => 'reconfigure',
    
    // File Upload
    'max_upload_size' => 52428800,
    'max_csv_rows' => 10000,
];
