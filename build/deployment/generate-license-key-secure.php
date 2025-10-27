<?php
/**
 * Secure License Key Generator
 * Generates cryptographically signed license keys
 * 
 * Usage: 
 *   php generate-license-key-secure.php <customer_email> [install_id]
 *   php generate-license-key-secure.php <customer_email> universal
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/license-keys.php';

echo "\n";
echo "========================================\n";
echo "  Secure License Key Generator v2.0    \n";
echo "========================================\n\n";

// Get arguments
$customerEmail = isset($argv[1]) ? $argv[1] : null;
$installIdArg = isset($argv[2]) ? $argv[2] : null;

if (!$customerEmail) {
    echo "Usage:\n";
    echo "  php generate-license-key-secure.php <customer_email> [install_id]\n";
    echo "  php generate-license-key-secure.php <customer_email> universal\n\n";
    echo "Examples:\n";
    echo "  php generate-license-key-secure.php customer@example.com\n";
    echo "  php generate-license-key-secure.php customer@example.com abc123-def456\n";
    echo "  php generate-license-key-secure.php customer@example.com universal\n\n";
    exit(1);
}

// Validate email
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email format\n";
    exit(1);
}

// Generate the appropriate key type
if ($installIdArg === 'universal') {
    // Generate universal key
    echo "Generating UNIVERSAL license key...\n";
    $keyData = LicenseKeys::generateUniversalKey($customerEmail);
    $keyType = "Universal (works on any installation)";
} elseif ($installIdArg) {
    // Generate installation-specific key
    echo "Generating installation-specific license key...\n";
    $keyData = LicenseKeys::generateSignedKey($installIdArg, $customerEmail);
    $keyType = "Installation-specific (Install ID: $installIdArg)";
} else {
    // Generate universal key by default
    echo "No install ID provided, generating universal key...\n";
    $keyData = LicenseKeys::generateUniversalKey($customerEmail);
    $keyType = "Universal (works on any installation)";
}

// Try to store in database (if available)
try {
    if (class_exists('Database')) {
        // Check if table exists
        $tableExists = Database::queryOne("SHOW TABLES LIKE 'license_keys_issued'");
        
        if (!$tableExists) {
            // Create table
            $sql = file_get_contents(__DIR__ . '/sql/add-license-keys-table.sql');
            Database::execute($sql);
        }
        
        // Store the issued key
        LicenseKeys::storeIssuedKey($keyData);
        $stored = true;
    }
} catch (Exception $e) {
    $stored = false;
    echo "Note: Could not store key in database: " . $e->getMessage() . "\n";
}

// Create local record
$licensesDir = __DIR__ . '/licenses';
if (!file_exists($licensesDir)) {
    mkdir($licensesDir, 0700);
}

$filename = $licensesDir . '/' . date('Y-m-d_His') . '_' . str_replace('@', '_', $customerEmail) . '.json';
file_put_contents($filename, json_encode($keyData, JSON_PRETTY_PRINT));

// Display results
echo "\n";
echo "✅ License Key Generated Successfully!\n";
echo "========================================\n\n";
echo "Customer Email: " . $customerEmail . "\n";
echo "Key Type: " . $keyType . "\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
if ($stored) {
    echo "Database: ✓ Key stored in database\n";
}
echo "\n";
echo "LICENSE KEY:\n";
echo "========================================\n";
echo "\n  " . $keyData['key'] . "\n\n";
echo "========================================\n\n";

// Validation test
echo "Testing key validation...\n";
$testInstallId = $installIdArg && $installIdArg !== 'universal' ? $installIdArg : 'test-install-123';
$validation = LicenseKeys::validateKey($keyData['key'], $testInstallId);

if ($validation['valid']) {
    echo "✅ Key validates successfully";
    if (isset($validation['type']) && $validation['type'] === 'universal') {
        echo " (universal key)\n";
    } else {
        echo "\n";
    }
} else {
    echo "⚠️  Key validation failed: " . $validation['error'] . "\n";
}

echo "\n";
echo "📧 Email Template:\n";
echo "========================================\n";
echo "To: $customerEmail\n";
echo "Subject: Your MyParkingManager License Key\n\n";
echo "Dear Customer,\n\n";
echo "Thank you for purchasing MyParkingManager!\n\n";
echo "Your license key is:\n";
echo $keyData['key'] . "\n\n";
echo "Key Type: $keyType\n\n";
echo "To activate your license:\n";
echo "1. Log into your MyParkingManager admin panel\n";
echo "2. Navigate to the License section\n";
echo "3. Enter your license key exactly as shown above\n";
echo "4. Click 'Activate License'\n\n";
echo "This license key is unique and can only be used once.\n";
echo "Please keep it safe for your records.\n\n";
echo "If you have any issues activating your license,\n";
echo "please contact our support team.\n\n";
echo "Best regards,\n";
echo "MyParkingManager Team\n";
echo "========================================\n\n";

echo "📁 License record saved to:\n";
echo "   $filename\n\n";

echo "⚠️  Security Notes:\n";
echo "   • This key uses cryptographic signatures\n";
echo "   • Keys are validated against installation ID\n";
echo "   • Store all records securely\n";
echo "   • Never expose the SECRET_KEY from license-keys.php\n\n";