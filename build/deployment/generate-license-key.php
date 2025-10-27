<?php
/**
 * License Key Generator Utility
 * Command-line tool to generate unique license keys for customers
 * 
 * Usage: php generate-license-key.php [customer_email]
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

require_once __DIR__ . '/includes/license.php';

echo "\n";
echo "========================================\n";
echo "  MyParkingManager License Generator    \n";
echo "========================================\n\n";

// Get customer email from command line argument
$customerEmail = isset($argv[1]) ? $argv[1] : null;

if (!$customerEmail) {
    echo "Usage: php generate-license-key.php <customer_email>\n\n";
    echo "Example: php generate-license-key.php customer@example.com\n";
    exit(1);
}

// Validate email format
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email format\n";
    exit(1);
}

// Generate a unique license key
$licenseKey = License::generateLicenseKey();

// Generate additional metadata
$metadata = [
    'generated_at' => date('Y-m-d H:i:s'),
    'generated_for' => $customerEmail,
    'valid_from' => date('Y-m-d'),
    'generator_version' => '1.0',
    'product' => 'MyParkingManager v2.0'
];

// Save to a license record file (for admin records)
$licenseRecord = [
    'key' => $licenseKey,
    'email' => $customerEmail,
    'metadata' => $metadata
];

// Create licenses directory if it doesn't exist
$licensesDir = __DIR__ . '/licenses';
if (!file_exists($licensesDir)) {
    mkdir($licensesDir, 0700);
}

// Save license record to file (for admin backup)
$filename = $licensesDir . '/' . date('Y-m-d_His') . '_' . str_replace('@', '_', $customerEmail) . '.json';
file_put_contents($filename, json_encode($licenseRecord, JSON_PRETTY_PRINT));

// Display the generated license
echo "✅ License Key Generated Successfully!\n";
echo "========================================\n\n";
echo "Customer Email: " . $customerEmail . "\n";
echo "Generated Date: " . date('Y-m-d H:i:s') . "\n\n";
echo "LICENSE KEY:\n";
echo "----------------------------------------\n";
echo "  " . $licenseKey . "\n";
echo "----------------------------------------\n\n";
echo "📧 Email Template:\n";
echo "----------------------------------------\n";
echo "Subject: Your MyParkingManager License Key\n\n";
echo "Dear Customer,\n\n";
echo "Thank you for purchasing MyParkingManager!\n\n";
echo "Your license key is:\n";
echo $licenseKey . "\n\n";
echo "To activate your license:\n";
echo "1. Log into your MyParkingManager admin panel\n";
echo "2. Go to Settings > License\n";
echo "3. Enter your license key\n";
echo "4. Click 'Activate License'\n\n";
echo "This license key is unique to your installation.\n";
echo "Please keep it safe for future reference.\n\n";
echo "If you have any questions, please contact support.\n\n";
echo "Best regards,\n";
echo "MyParkingManager Team\n";
echo "----------------------------------------\n\n";
echo "💾 License record saved to:\n";
echo "   " . $filename . "\n\n";
echo "⚠️  Security Notes:\n";
echo "   - This key is unique and can only be used once\n";
echo "   - Store license records securely\n";
echo "   - Never share keys publicly\n\n";