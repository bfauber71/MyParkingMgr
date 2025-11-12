<?php
/**
 * Web-Based Encryption Key Generator
 * 
 * This script generates a secure encryption key through your web browser.
 * Access it at: https://yourdomain.com/jrk-payments/web-generate-key.php
 * 
 * SECURITY:
 * - Delete this file after generating your key
 * - Only run once during initial setup
 * - Backup the generated encryption.key file securely
 */

// Security: Only allow access from localhost or specific IPs during setup
// Comment out these lines if you need to access from remote
// if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']) {
//     die('Access denied. Run from localhost only.');
// }

require_once __DIR__ . '/lib/CryptoHelper.php';

$keyFile = __DIR__ . '/config/encryption.key';
$configDir = __DIR__ . '/config';
$message = '';
$error = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'generate') {
            try {
                // Check if key already exists
                $keyExists = file_exists($keyFile);
                
                if ($keyExists && !isset($_POST['confirm_overwrite'])) {
                    $error = 'Encryption key already exists! Check the box to confirm overwriting it.';
                } else {
                    // Backup existing key if present
                    if ($keyExists) {
                        $backupFile = $keyFile . '.backup.' . date('YmdHis');
                        if (copy($keyFile, $backupFile)) {
                            $message .= "✓ Existing key backed up to: " . basename($backupFile) . "<br>";
                        }
                    }
                    
                    // Create config directory if needed
                    if (!is_dir($configDir)) {
                        if (!mkdir($configDir, 0755, true)) {
                            throw new Exception('Failed to create config directory');
                        }
                        $message .= "✓ Created config directory<br>";
                    }
                    
                    // Generate new encryption key
                    $keyAscii = CryptoHelper::generateKey();
                    
                    // Write key to file
                    if (file_put_contents($keyFile, $keyAscii) === false) {
                        throw new Exception('Failed to write key file');
                    }
                    
                    // Set restrictive permissions (if not on Windows)
                    if (PHP_OS_FAMILY !== 'Windows') {
                        chmod($keyFile, 0600);
                    }
                    
                    $success = true;
                    $message .= "<strong>✓ Encryption key generated successfully!</strong><br>";
                    $message .= "Key location: config/encryption.key<br>";
                    $message .= "Key length: " . strlen($keyAscii) . " bytes<br>";
                }
                
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        
        if ($_POST['action'] === 'verify') {
            try {
                if (!file_exists($keyFile)) {
                    $error = 'No encryption key found. Please generate one first.';
                } else {
                    // Test encryption/decryption
                    $testData = 'Test String ' . time();
                    $encrypted = CryptoHelper::encrypt($testData);
                    $decrypted = CryptoHelper::decrypt($encrypted);
                    
                    if ($testData === $decrypted) {
                        $success = true;
                        $message = "<strong>✓ Encryption is working correctly!</strong><br>";
                        $message .= "Key file: config/encryption.key<br>";
                        $message .= "File size: " . filesize($keyFile) . " bytes<br>";
                        $message .= "File permissions: " . substr(sprintf('%o', fileperms($keyFile)), -4);
                    } else {
                        $error = 'Encryption test failed. Key may be corrupted.';
                    }
                }
            } catch (Exception $e) {
                $error = "Verification error: " . $e->getMessage();
            }
        }
    }
}

$keyExists = file_exists($keyFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encryption Key Generator - ManageMyParking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #1e293b;
            font-size: 28px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #64748b;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .status-box.info {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            color: #1e40af;
        }
        
        .status-box.success {
            background: #dcfce7;
            border: 1px solid #22c55e;
            color: #166534;
        }
        
        .status-box.error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        
        .status-box.warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #334155;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .icon {
            font-size: 20px;
        }
        
        label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        label:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .warning-box {
            background: #fffbeb;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .warning-box h3 {
            color: #92400e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-box ul {
            color: #78350f;
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .next-steps {
            background: #f0fdf4;
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .next-steps h3 {
            color: #166534;
            margin-bottom: 10px;
        }
        
        .next-steps ol {
            color: #14532d;
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .delete-notice {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            color: #991b1b;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Encryption Key Generator</h1>
        <div class="subtitle">ManageMyParking Payment System v2.0</div>
        
        <?php if ($error): ?>
            <div class="status-box error">
                <strong>❌ Error:</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="status-box <?php echo $success ? 'success' : 'info'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($keyExists && !$success): ?>
            <div class="status-box warning">
                <strong>⚠️ Encryption Key Already Exists</strong><br>
                A key file has been found at: <code>config/encryption.key</code><br>
                Generating a new key will make existing encrypted data unreadable!
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div class="warning-box">
                <h3>⚠️ Important Security Notes</h3>
                <ul>
                    <li><strong>Run this only once</strong> during initial setup</li>
                    <li><strong>Backup the key file</strong> - Lost keys = Lost API credentials</li>
                    <li><strong>Delete this file</strong> after generating your key</li>
                    <li><strong>Never commit</strong> encryption.key to version control</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="form-section">
                    <h2><span class="icon">🔑</span> Generate Encryption Key</h2>
                    
                    <?php if ($keyExists): ?>
                        <label>
                            <input type="checkbox" name="confirm_overwrite" value="1" required>
                            <span>I understand this will overwrite the existing key and make current encrypted data unreadable</span>
                        </label>
                    <?php endif; ?>
                    
                    <input type="hidden" name="action" value="generate">
                    <button type="submit" class="btn-primary">
                        <?php echo $keyExists ? '🔄 Generate New Key' : '🔐 Generate Encryption Key'; ?>
                    </button>
                </div>
            </form>
            
            <?php if ($keyExists): ?>
                <form method="POST">
                    <div class="form-section">
                        <h2><span class="icon">✅</span> Verify Existing Key</h2>
                        <input type="hidden" name="action" value="verify">
                        <button type="submit" class="btn-secondary">
                            🔍 Test Encryption
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
        <?php else: ?>
            
            <div class="next-steps">
                <h3>✅ Next Steps</h3>
                <ol>
                    <li><strong>Secure the key file:</strong> Set permissions to 600 (read/write for owner only)</li>
                    <li><strong>Backup the key:</strong> Download <code>config/encryption.key</code> to a secure location</li>
                    <li><strong>Delete this file:</strong> Remove <code>web-generate-key.php</code> from your server</li>
                    <li><strong>Configure payments:</strong> Go to Settings → Payments and enter your API keys</li>
                    <li><strong>Test encryption:</strong> Your API keys will be encrypted automatically</li>
                </ol>
            </div>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="verify">
                <button type="submit" class="btn-secondary">
                    🔍 Verify Encryption Works
                </button>
            </form>
            
        <?php endif; ?>
        
        <div class="delete-notice">
            🗑️ Remember to delete this file after generating your key!
        </div>
    </div>
</body>
</html>
