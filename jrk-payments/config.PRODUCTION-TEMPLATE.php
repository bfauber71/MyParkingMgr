<?php
/**
 * MyParkingManager Configuration - PRODUCTION TEMPLATE
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Update all values marked with "CHANGE THIS"
 * 3. Delete this comment block after configuration
 */

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    
    // CHANGE THIS: Use your actual production domain
    // Examples:
    //   'app_url' => 'https://yourdomain.com',              (root install)
    //   'app_url' => 'https://yourdomain.com/parking',      (subdirectory)
    'app_url' => 'https://yourdomain.com',  // CHANGE THIS
    
    // CHANGE THIS: Set base path
    // Examples:
    //   'base_path' => '',           (installed at root: https://yourdomain.com/)
    //   'base_path' => '/parking',   (installed in subdir: https://yourdomain.com/parking/)
    'base_path' => '',  // CHANGE THIS if in subdirectory
    
    // Database Configuration - MySQL ONLY
    // CHANGE ALL THESE to match your cPanel MySQL database
    'db' => [
        'host' => 'localhost',              // Usually 'localhost' for shared hosting
        'port' => '3306',                   // Standard MySQL port
        'database' => 'your_db_name',       // CHANGE THIS: Your database name
        'username' => 'your_db_username',   // CHANGE THIS: Your MySQL username
        'password' => 'your_db_password',   // CHANGE THIS: Your MySQL password
        'charset' => 'utf8mb4',
    ],
    
    // Session Configuration
    'session' => [
        'name' => 'myparkingmanager_session',
        'lifetime' => 1440,                 // 24 hours in minutes
        'secure' => true,                   // Set to true for HTTPS (recommended)
        'httponly' => true,
    ],
    
    // Security
    'password_cost' => 10,
    'setup_token' => 'reconfigure',         // Change this to prevent unauthorized setup access
    
    // File Upload
    'max_upload_size' => 52428800,          // 50MB
    'max_csv_rows' => 10000,
];
