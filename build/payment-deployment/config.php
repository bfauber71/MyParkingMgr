<?php
/**
 * ManageMyParking v2.0 - Production Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Rename this file or create a copy as config.php
 * 2. Update all database settings with your MySQL credentials
 * 3. Change base_path if not installed in root directory
 * 4. Keep this file secure - do not commit to version control
 */

return [
    'base_path' => '/',  // Change to '/subdirectory' if not in root
    
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'YOUR_DATABASE_NAME',
        'username' => 'YOUR_DATABASE_USER',
        'password' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
        'unix_socket' => '',  // Leave empty for standard TCP connection
    ],
    
    'session' => [
        'name' => 'mmp_session',
        'lifetime' => 86400,
        'secure' => true,      // Set to true for HTTPS (recommended)
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    
    'app' => [
        'name' => 'ManageMyParking',
        'version' => '2.0',
        'timezone' => 'America/New_York',  // Change to your timezone
    ],
];
