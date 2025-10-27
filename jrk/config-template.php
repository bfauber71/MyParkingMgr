<?php
/**
 * MyParkingManager Configuration Template
 * 
 * IMPORTANT: Copy this file to 'config.php' and edit the values below
 * for your hosting environment.
 * 
 * For guided configuration, use the setup wizard: /setup.php
 */

return [
    // Application Settings
    'app_name' => 'MyParkingManager',
    'app_url' => '',  // Full URL to your application (e.g., https://yourdomain.com/parking)
    'base_path' => '',  // Subdirectory path or empty string for root (auto-detected)
    'install_id' => '',  // Unique installation identifier (generated automatically)
    
    // Database Configuration - REQUIRED
    // Edit these values to match your MySQL database
    'db' => [
        'host' => 'localhost',  // MySQL hostname (usually 'localhost' for shared hosting)
        'port' => '3306',       // MySQL port (default 3306)
        'database' => 'your_database_name',  // Your MySQL database name
        'username' => 'your_db_username',    // Your MySQL username
        'password' => 'your_db_password',    // Your MySQL password
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
    'setup_token' => '', // Generated automatically during setup
    
    // File Upload
    'max_upload_size' => 52428800, // 50MB in bytes
    'max_csv_rows' => 10000,
];
