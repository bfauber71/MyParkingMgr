<?php
/**
 * Test file to verify API fixes are deployed
 * Upload this to your server and access it directly
 */

echo "=== MyParkingManager API Fix Verification ===\n\n";

// Test 1: Check if database.php has the whitelist fix
echo "1. Checking database.php for endpoint whitelist...\n";
$dbFile = __DIR__ . '/includes/database.php';
if (file_exists($dbFile)) {
    $dbContent = file_get_contents($dbFile);
    if (strpos($dbContent, 'noDbRequired') !== false && 
        strpos($dbContent, '/api/app-config') !== false) {
        echo "   ✓ database.php has whitelist fix\n\n";
    } else {
        echo "   ✗ database.php missing whitelist fix\n";
        echo "   ACTION: Re-upload includes/database.php\n\n";
    }
} else {
    echo "   ✗ database.php not found\n\n";
}

// Test 2: Check if index.php has the csrf-token route
echo "2. Checking index.php for CSRF token route...\n";
$indexFile = __DIR__ . '/index.php';
if (file_exists($indexFile)) {
    $indexContent = file_get_contents($indexFile);
    if (strpos($indexContent, '/api/csrf-token') !== false) {
        echo "   ✓ index.php has CSRF token route\n\n";
    } else {
        echo "   ✗ index.php missing CSRF token route\n";
        echo "   ACTION: Re-upload index.php\n\n";
    }
} else {
    echo "   ✗ index.php not found\n\n";
}

// Test 3: Try to test the endpoints
echo "3. Testing endpoints...\n";
echo "   Note: Run this test by accessing the URLs below:\n";
echo "   - https://2clv.com/jrk/api/app-config\n";
echo "   - https://2clv.com/jrk/api/csrf-token\n\n";

// Test 4: PHP version check
echo "4. PHP Environment:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   OPcache Enabled: " . (function_exists('opcache_get_status') ? 'Yes' : 'No') . "\n";
if (function_exists('opcache_get_status')) {
    echo "   ACTION: Clear OPcache or restart PHP-FPM\n";
}
echo "\n";

echo "=== END OF TEST ===\n";
