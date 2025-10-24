<?php
/**
 * MyParkingManager Setup Wizard
 * Initial installation and configuration management
 */

session_start();

// Security: Check if config already exists and is properly configured
$configFile = __DIR__ . '/config.php';
$configExists = file_exists($configFile);
$isConfigured = false;

if ($configExists) {
    try {
        $config = require $configFile;
        // Check if database credentials are set (not default placeholders)
        if (isset($config['db']) && 
            $config['db']['username'] !== 'your_db_username' && 
            $config['db']['password'] !== 'your_db_password') {
            $isConfigured = true;
        }
    } catch (Exception $e) {
        // Config file exists but is invalid
    }
}

// If already configured, require a setup token to proceed
if ($isConfigured) {
    if (!isset($_SESSION['setup_authenticated'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_token'])) {
            // Simple token for re-configuration: "reconfigure" or check config file for custom token
            $validToken = 'reconfigure';
            if (isset($config['setup_token']) && !empty($config['setup_token'])) {
                $validToken = $config['setup_token'];
            }
            
            if ($_POST['setup_token'] === $validToken) {
                $_SESSION['setup_authenticated'] = true;
            } else {
                $authError = 'Invalid setup token. Check your config.php file for the setup_token value.';
            }
        }
        
        if (!isset($_SESSION['setup_authenticated'])) {
            // Show authentication form
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Setup Authentication - MyParkingManager</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #1a1a1a; color: #e1e1e1; padding: 20px; }
                    .container { max-width: 600px; margin: 50px auto; background: #2a2a2a; border-radius: 8px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
                    h1 { color: #4a90e2; margin-bottom: 10px; }
                    .subtitle { color: #94a3b8; margin-bottom: 30px; }
                    .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
                    .form-group { margin-bottom: 20px; }
                    label { display: block; margin-bottom: 8px; font-weight: 500; color: #cbd5e1; }
                    input[type="text"], input[type="password"] { width: 100%; padding: 12px; background: #1e1e1e; border: 1px solid #404040; border-radius: 4px; color: #e1e1e1; font-size: 14px; }
                    input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #4a90e2; }
                    .btn { padding: 12px 24px; background: #4a90e2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; width: 100%; }
                    .btn:hover { background: #3a7bc8; }
                    .info-box { background: #1e3a5f; border: 1px solid #2563eb; padding: 15px; border-radius: 4px; margin-top: 20px; }
                    .info-box p { color: #93c5fd; font-size: 13px; line-height: 1.6; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>🔒 Setup Authentication Required</h1>
                    <p class="subtitle">Your MyParkingManager installation is already configured. Enter the setup token to make changes.</p>
                    
                    <?php if (isset($authError)): ?>
                        <div class="alert"><?php echo htmlspecialchars($authError); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="setup_token">Setup Token</label>
                            <input type="password" id="setup_token" name="setup_token" required placeholder="Enter setup token">
                        </div>
                        <button type="submit" class="btn">Authenticate</button>
                    </form>
                    
                    <div class="info-box">
                        <p><strong>Default Token:</strong> reconfigure</p>
                        <p><strong>Custom Token:</strong> If you've set a custom setup_token in config.php, use that instead.</p>
                        <p><strong>Security Note:</strong> After making changes, consider changing or removing the setup_token in config.php to prevent unauthorized access.</p>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $errors = [];
    $success = false;
    
    // Validate inputs
    $appName = trim($_POST['app_name'] ?? 'MyParkingManager');
    $appUrl = trim($_POST['app_url'] ?? '');
    $basePath = trim($_POST['base_path'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $setupToken = trim($_POST['setup_token'] ?? 'reconfigure');
    
    if (empty($appUrl)) {
        $errors[] = 'Application URL is required';
    }
    if (empty($dbName)) {
        $errors[] = 'Database name is required';
    }
    if (empty($dbUser)) {
        $errors[] = 'Database username is required';
    }
    
    // Test database connection
    if (empty($errors)) {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $testPdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Test a simple query
            $testPdo->query('SELECT 1');
            
            // Connection successful, write config file
            $configContent = '<?php
/**
 * MyParkingManager Configuration
 * Generated by Setup Wizard
 * Last Updated: ' . date('Y-m-d H:i:s') . '
 */

return [
    // Application Settings
    \'app_name\' => ' . var_export($appName, true) . ',
    \'app_url\' => ' . var_export($appUrl, true) . ',
    \'base_path\' => ' . var_export($basePath, true) . ',
    
    // Database Configuration
    \'db\' => [
        \'host\' => ' . var_export($dbHost, true) . ',
        \'port\' => ' . var_export($dbPort, true) . ',
        \'database\' => ' . var_export($dbName, true) . ',
        \'username\' => ' . var_export($dbUser, true) . ',
        \'password\' => ' . var_export($dbPass, true) . ',
        \'charset\' => \'utf8mb4\',
    ],
    
    // Session Configuration
    \'session\' => [
        \'name\' => \'myparkingmanager_session\',
        \'lifetime\' => 1440, // 24 hours in minutes
        \'secure\' => true, // HTTPS required (recommended for production)
        \'httponly\' => true,
    ],
    
    // Security
    \'password_cost\' => 10, // bcrypt cost factor
    \'setup_token\' => ' . var_export($setupToken, true) . ', // Token required to access setup.php after initial configuration
    
    // File Upload
    \'max_upload_size\' => 52428800, // 50MB in bytes
    \'max_csv_rows\' => 10000,
];
';
            
            if (file_put_contents($configFile, $configContent)) {
                $success = true;
                $_SESSION['setup_authenticated'] = false; // Force re-auth next time
            } else {
                $errors[] = 'Failed to write config.php. Check file permissions.';
            }
            
        } catch (PDOException $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

// Load current config if exists
$currentConfig = [
    'app_name' => 'MyParkingManager',
    'app_url' => '',
    'base_path' => '',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'setup_token' => 'reconfigure'
];

if ($configExists) {
    try {
        $config = require $configFile;
        $currentConfig['app_name'] = $config['app_name'] ?? 'MyParkingManager';
        $currentConfig['app_url'] = $config['app_url'] ?? '';
        $currentConfig['base_path'] = $config['base_path'] ?? '';
        $currentConfig['db_host'] = $config['db']['host'] ?? 'localhost';
        $currentConfig['db_port'] = $config['db']['port'] ?? '3306';
        $currentConfig['db_name'] = $config['db']['database'] ?? '';
        $currentConfig['db_user'] = $config['db']['username'] ?? '';
        $currentConfig['db_pass'] = $config['db']['password'] ?? '';
        $currentConfig['setup_token'] = $config['setup_token'] ?? 'reconfigure';
    } catch (Exception $e) {
        // Use defaults
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - MyParkingManager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #1a1a1a; color: #e1e1e1; padding: 20px; }
        .container { max-width: 800px; margin: 30px auto; background: #2a2a2a; border-radius: 8px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h1 { color: #4a90e2; margin-bottom: 10px; }
        .subtitle { color: #94a3b8; margin-bottom: 30px; font-size: 14px; }
        .alert-success { padding: 15px; border-radius: 4px; margin-bottom: 20px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { padding: 15px; border-radius: 4px; margin-bottom: 20px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #404040; }
        .section:last-child { border-bottom: none; }
        .section-title { font-size: 18px; font-weight: 600; color: #cbd5e1; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #cbd5e1; }
        .help-text { font-size: 12px; color: #94a3b8; margin-top: 4px; }
        input[type="text"], input[type="password"], input[type="number"] { width: 100%; padding: 12px; background: #1e1e1e; border: 1px solid #404040; border-radius: 4px; color: #e1e1e1; font-size: 14px; }
        input[type="text"]:focus, input[type="password"]:focus, input[type="number"]:focus { outline: none; border-color: #4a90e2; }
        .btn-primary { padding: 14px 28px; background: #4a90e2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; font-weight: 500; width: 100%; margin-top: 10px; }
        .btn-primary:hover { background: #3a7bc8; }
        .btn-test { padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; margin-top: 10px; }
        .btn-test:hover { background: #059669; }
        .info-box { background: #1e3a5f; border: 1px solid #2563eb; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .info-box p { color: #93c5fd; font-size: 13px; line-height: 1.6; margin-bottom: 8px; }
        .info-box p:last-child { margin-bottom: 0; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 15px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-update { background: #dbeafe; color: #1e40af; }
        .next-steps { background: #064e3b; border: 1px solid #10b981; padding: 20px; border-radius: 4px; margin-top: 20px; }
        .next-steps h3 { color: #6ee7b7; margin-bottom: 15px; }
        .next-steps ol { margin-left: 20px; }
        .next-steps li { color: #d1fae5; margin-bottom: 8px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ MyParkingManager Setup Wizard</h1>
        <p class="subtitle">
            <?php if ($isConfigured): ?>
                <span class="status-badge status-update">Configuration Update</span> Update your installation settings for migration or environment changes
            <?php else: ?>
                <span class="status-badge status-new">Initial Setup</span> Configure your MyParkingManager installation
            <?php endif; ?>
        </p>
        
        <?php if (isset($success) && $success): ?>
            <div class="alert-success">
                <strong>✅ Configuration saved successfully!</strong>
            </div>
            <div class="next-steps">
                <h3>📋 Next Steps:</h3>
                <ol>
                    <li>Delete or rename <strong>setup.php</strong> for security (optional but recommended)</li>
                    <li>Import the database schema using <strong>sql/install.sql</strong> via phpMyAdmin</li>
                    <li>Access your application at: <strong><?php echo htmlspecialchars($appUrl); ?></strong></li>
                    <li>Login with default credentials: <strong>admin</strong> / <strong>admin123</strong></li>
                    <li>Change the default admin password immediately!</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert-error">
                <strong>❌ Configuration Errors:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="setupForm">
            <input type="hidden" name="action" value="save_config">
            
            <!-- Application Settings -->
            <div class="section">
                <h2 class="section-title">Application Settings</h2>
                
                <div class="form-group">
                    <label for="app_name">Application Name</label>
                    <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($currentConfig['app_name']); ?>" required>
                    <p class="help-text">Display name for your parking management system</p>
                </div>
                
                <div class="form-group">
                    <label for="app_url">Application URL</label>
                    <input type="text" id="app_url" name="app_url" value="<?php echo htmlspecialchars($currentConfig['app_url']); ?>" required placeholder="https://yourdomain.com/path">
                    <p class="help-text">Full URL where your application will be accessed (include https:// and any subdirectory)</p>
                </div>
                
                <div class="form-group">
                    <label for="base_path">Base Path (Subdirectory)</label>
                    <input type="text" id="base_path" name="base_path" value="<?php echo htmlspecialchars($currentConfig['base_path']); ?>" placeholder="/jrk or leave empty for root">
                    <p class="help-text">If installed in a subdirectory, enter it here (e.g., /jrk). Leave empty if installed in root directory.</p>
                </div>
            </div>
            
            <!-- Database Settings -->
            <div class="section">
                <h2 class="section-title">Database Configuration</h2>
                
                <div class="info-box">
                    <p><strong>📌 Before proceeding:</strong> Create a MySQL database using your hosting control panel (cPanel/phpMyAdmin)</p>
                    <p>You'll need the database name, username, and password to continue.</p>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($currentConfig['db_host']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_port">Port</label>
                        <input type="number" id="db_port" name="db_port" value="<?php echo htmlspecialchars($currentConfig['db_port']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($currentConfig['db_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($currentConfig['db_user']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($currentConfig['db_pass']); ?>">
                </div>
                
                <button type="button" class="btn-test" onclick="testDatabaseConnection()">🔍 Test Database Connection</button>
                <div id="dbTestResult" style="margin-top: 10px;"></div>
            </div>
            
            <!-- Security Settings -->
            <div class="section">
                <h2 class="section-title">Security Settings</h2>
                
                <div class="form-group">
                    <label for="setup_token">Setup Token</label>
                    <input type="text" id="setup_token" name="setup_token" value="<?php echo htmlspecialchars($currentConfig['setup_token']); ?>" required>
                    <p class="help-text">Required to access this setup page in the future. Keep it secure! Default: reconfigure</p>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">💾 Save Configuration</button>
        </form>
    </div>
    
    <script>
        function testDatabaseConnection() {
            const resultDiv = document.getElementById('dbTestResult');
            resultDiv.innerHTML = '<span style="color: #94a3b8;">Testing connection...</span>';
            
            const formData = new FormData();
            formData.append('action', 'test_db');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            
            fetch('setup-test-db.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<span style="color: #10b981;">✅ ' + data.message + '</span>';
                } else {
                    resultDiv.innerHTML = '<span style="color: #ef4444;">❌ ' + data.error + '</span>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<span style="color: #ef4444;">❌ Test failed: ' + error.message + '</span>';
            });
        }
    </script>
</body>
</html>
