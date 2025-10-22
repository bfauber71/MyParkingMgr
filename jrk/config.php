<?php
/**
 * ManageMyParking Configuration
 * Edit these settings for your hosting environment
 */

return [
    // Application Settings
    'app_name' => 'ManageMyParking',
    'app_url' => 'https://2clv.com/jrk',
    'base_path' => '/jrk',
    
    // Database Configuration
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'managemyparking',
        'username' => 'your_db_username',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    
    // Session Configuration
    'session' => [
        'name' => 'managemyparking_session',
        'lifetime' => 1440, // 24 hours in minutes
        'secure' => true, // Set to true for HTTPS
        'httponly' => true,
    ],
    
    // Security
    'password_cost' => 10, // bcrypt cost factor
    
    // File Upload
    'max_upload_size' => 52428800, // 50MB in bytes
    'max_csv_rows' => 10000,
];
