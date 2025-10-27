<?php
/**
 * MyParkingManager Setup Wizard
 * Comprehensive installation with admin user creation
 */

session_start();
require_once __DIR__ . '/includes/admin-setup.php';

$step = $_GET['step'] ?? 1;
$configFile = __DIR__ . '/config.php';
$configExists = file_exists($configFile);

// Check if already configured
$isConfigured = false;
$dbConnection = null;

if ($configExists) {
    try {
        $config = require $configFile;
        if (isset($config['db']) && 
            $config['db']['username'] !== 'your_db_username' && 
            $config['db']['password'] !== 'your_db_password') {
            $isConfigured = true;
            
            // Try to establish database connection
            try {
                $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['database']};charset=utf8mb4";
                $dbConnection = new PDO($dsn, $config['db']['username'], $config['db']['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                // Database connection failed
            }
        }
    } catch (Exception $e) {
        // Config file exists but is invalid
    }
}

// Check if admin exists (if database is connected)
$adminExists = false;
if ($dbConnection) {
    $adminExists = adminUserExists($dbConnection);
}

// If configured and admin exists, require token to proceed
if ($isConfigured && $adminExists) {
    if (!isset($_SESSION['setup_authenticated'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_token'])) {
            $validToken = 'reconfigure';
            if (isset($config['setup_token']) && !empty($config['setup_token'])) {
                $validToken = $config['setup_token'];
            }
            
            if ($_POST['setup_token'] === $validToken) {
                $_SESSION['setup_authenticated'] = true;
            } else {
                $authError = 'Invalid setup token.';
            }
        }
        
        if (!isset($_SESSION['setup_authenticated'])) {
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Setup Authentication - MyParkingManager</title>
                <link rel="stylesheet" href="public/css/setup.css">
            </head>
            <body>
                <div class="container">
                    <h1>🔒 Setup Authentication Required</h1>
                    <p class="subtitle">Your system is already configured. Enter the setup token to make changes.</p>
                    
                    <?php if (isset($authError)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($authError); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="setup_token">Setup Token</label>
                            <input type="password" id="setup_token" name="setup_token" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Authenticate</button>
                    </form>
                    
                    <div class="info-box">
                        <p>Default token: <code>reconfigure</code></p>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Handle form submissions
$errors = [];
$success = false;

// Step 1: Database Configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $appName = trim($_POST['app_name'] ?? 'MyParkingManager');
    $appUrl = trim($_POST['app_url'] ?? '');
    $basePath = trim($_POST['base_path'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    
    // Validation
    if (empty($appUrl)) $errors[] = 'Application URL is required';
    if (empty($dbName)) $errors[] = 'Database name is required';
    if (empty($dbUser)) $errors[] = 'Database username is required';
    
    if (empty($errors)) {
        try {
            // Test connection
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $testPdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Save to session for next step (don't store PDO object)
            $_SESSION['setup_config'] = [
                'app_name' => $appName,
                'app_url' => $appUrl,
                'base_path' => $basePath,
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass
            ];
            
            header('Location: setup-wizard.php?step=2');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

// Step 2: Database Installation
if ($step == 2 && isset($_SESSION['setup_config'])) {
    // Recreate PDO connection from saved credentials
    $pdo = null;
    try {
        $cfg = $_SESSION['setup_config'];
        $dsn = "mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname={$cfg['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        $errors[] = 'Database connection failed: ' . $e->getMessage();
    }
    
    if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Import database schema
        $sqlFile = __DIR__ . '/sql/install.sql';
        if (file_exists($sqlFile)) {
            $result = importSQLFile($pdo, $sqlFile);
            if ($result['success']) {
                header('Location: setup-wizard.php?step=3');
                exit;
            } else {
                $errors[] = 'Database installation failed: ' . $result['error'];
            }
        } else {
            $errors[] = 'SQL installation file not found';
        }
    }
}

// Step 3: Admin User Creation
if ($step == 3 && isset($_SESSION['setup_config'])) {
    // Recreate PDO connection from saved credentials
    $pdo = null;
    try {
        $cfg = $_SESSION['setup_config'];
        $dsn = "mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname={$cfg['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        $errors[] = 'Database connection failed: ' . $e->getMessage();
    }
    
    if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['admin_username'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input
        $validationErrors = validateAdminInput($username, $email, $password, $confirmPassword);
        
        if (empty($validationErrors)) {
            $result = createAdminUser($pdo, $username, $email, $password);
            if ($result['success']) {
                // Save config file
                $cfg = $_SESSION['setup_config'];
                $setupToken = trim($_POST['setup_token'] ?? 'reconfigure');
                
                // Generate install ID for license system
                require_once __DIR__ . '/includes/license.php';
                $installId = License::generateUUID();
                
                // Initialize license system after saving config
                License::initialize($installId);
                
                // Determine install path and base path
                $installPath = dirname(__FILE__);
                
                // Ensure base_path is properly set without trailing slashes
                if (isset($cfg['base_path'])) {
                    $cfg['base_path'] = rtrim($cfg['base_path'], '/');
                }
                
                $configContent = '<?php
/**
 * MyParkingManager Configuration
 * Generated by Setup Wizard
 * Last Updated: ' . date('Y-m-d H:i:s') . '
 */

return [
    // Application Settings
    \'app_name\' => ' . var_export($cfg['app_name'], true) . ',
    \'app_url\' => ' . var_export($cfg['app_url'], true) . ',
    \'base_path\' => ' . var_export($cfg['base_path'], true) . ',
    \'install_path\' => ' . var_export($installPath, true) . ',
    
    // Database Configuration
    \'db\' => [
        \'host\' => ' . var_export($cfg['db_host'], true) . ',
        \'port\' => ' . var_export($cfg['db_port'], true) . ',
        \'database\' => ' . var_export($cfg['db_name'], true) . ',
        \'username\' => ' . var_export($cfg['db_user'], true) . ',
        \'password\' => ' . var_export($cfg['db_pass'], true) . ',
        \'charset\' => \'utf8mb4\',
    ],
    
    // Session Configuration
    \'session\' => [
        \'name\' => \'myparkingmanager_session\',
        \'lifetime\' => 1440,
        \'secure\' => true,
        \'httponly\' => true,
    ],
    
    // Security
    \'password_cost\' => 10,
    \'setup_token\' => ' . var_export($setupToken, true) . ',
    
    // License System
    \'install_id\' => ' . var_export($installId, true) . ',
    
    // File Upload
    \'max_upload_size\' => 52428800,
    \'max_csv_rows\' => 10000,
];
';
                
                if (file_put_contents($configFile, $configContent)) {
                    $_SESSION['setup_complete'] = true;
                    $_SESSION['admin_username'] = $username;
                    unset($_SESSION['setup_config']);
                    header('Location: setup-wizard.php?step=4');
                    exit;
                } else {
                    $errors[] = 'Failed to write config file';
                }
            } else {
                $errors[] = $result['error'];
            }
        } else {
            $errors = $validationErrors;
        }
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
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { 
            max-width: 600px; 
            width: 100%;
            background: white; 
            border-radius: 12px; 
            padding: 40px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #333; 
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle { 
            color: #666; 
            margin-bottom: 30px; 
            font-size: 14px; 
        }
        .progress-bar {
            display: flex;
            margin-bottom: 40px;
            position: relative;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .progress-step::before {
            content: attr(data-step);
            display: block;
            width: 30px;
            height: 30px;
            background: #e1e1e1;
            border-radius: 50%;
            line-height: 30px;
            margin: 0 auto 10px;
            font-weight: bold;
            color: white;
        }
        .progress-step.active::before {
            background: #667eea;
        }
        .progress-step.completed::before {
            background: #48bb78;
            content: '✓';
        }
        .progress-step span {
            display: block;
            font-size: 12px;
            color: #666;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: calc(50% + 15px);
            width: calc(100% - 30px);
            height: 2px;
            background: #e1e1e1;
        }
        .progress-step.completed:not(:last-child)::after {
            background: #48bb78;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #fc8181;
        }
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert-info {
            background: #bee3f8;
            color: #2c5282;
            border: 1px solid #90cdf4;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"], 
        input[type="email"], 
        input[type="password"], 
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #e1e1e1;
            color: #333;
            margin-right: 10px;
        }
        .btn-secondary:hover {
            background: #d1d1d1;
        }
        .password-requirements {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
            font-size: 12px;
        }
        .requirement {
            display: block;
            margin: 4px 0;
            color: #666;
        }
        .requirement.valid {
            color: #48bb78;
        }
        .requirement.valid::before {
            content: '✓ ';
        }
        .requirement::before {
            content: '○ ';
            color: #cbd5e0;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        @media (max-width: 600px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        .success-icon {
            font-size: 48px;
            color: #48bb78;
            text-align: center;
            margin-bottom: 20px;
        }
        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>" data-step="1">
                <span>Database Config</span>
            </div>
            <div class="progress-step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>" data-step="2">
                <span>Install Schema</span>
            </div>
            <div class="progress-step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>" data-step="3">
                <span>Create Admin</span>
            </div>
            <div class="progress-step <?php echo $step >= 4 ? 'completed' : ''; ?>" data-step="4">
                <span>Complete</span>
            </div>
        </div>

        <?php if ($step == 1): ?>
            <!-- Step 1: Database Configuration -->
            <h1>Database Configuration</h1>
            <p class="subtitle">Configure your database connection settings</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="app_name">Application Name</label>
                    <input type="text" id="app_name" name="app_name" value="MyParkingManager" required>
                </div>

                <div class="form-group">
                    <label for="app_url">Application URL</label>
                    <input type="text" id="app_url" name="app_url" placeholder="https://yourdomain.com" required>
                    <p class="help-text">The full URL where your application will be accessed</p>
                </div>

                <div class="form-group">
                    <label for="base_path">Base Path (optional)</label>
                    <input type="text" id="base_path" name="base_path" placeholder="/subdirectory or leave empty">
                    <p class="help-text">Leave empty if installed in root directory, or enter subdirectory (e.g., /myapp)</p>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label for="db_port">Port</label>
                        <input type="number" id="db_port" name="db_port" value="3306" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" required>
                </div>

                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>

                <button type="submit" class="btn btn-primary">Next: Install Database →</button>
            </form>

        <?php elseif ($step == 2): ?>
            <!-- Step 2: Database Installation -->
            <h1>Install Database Schema</h1>
            <p class="subtitle">Ready to create the database tables</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php 
            $missingTables = [];
            if (isset($pdo)) {
                $missingTables = checkDatabaseTables($pdo);
            }
            ?>

            <?php if (empty($missingTables)): ?>
                <div class="alert alert-info">
                    Database tables already exist. Click next to continue.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    The following tables will be created:
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <?php foreach ($missingTables as $table): ?>
                            <li><?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php if (!empty($missingTables)): ?>
                    <button type="submit" class="btn btn-primary">Install Database Tables</button>
                <?php else: ?>
                    <a href="setup-wizard.php?step=3" class="btn btn-primary">Next: Create Admin User →</a>
                <?php endif; ?>
            </form>

        <?php elseif ($step == 3): ?>
            <!-- Step 3: Create Admin User -->
            <h1>Create Admin User</h1>
            <p class="subtitle">Set up your administrator account</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            $adminAlreadyExists = false;
            if (isset($pdo)) {
                $adminAlreadyExists = adminUserExists($pdo);
            }
            ?>

            <?php if ($adminAlreadyExists): ?>
                <div class="alert alert-info">
                    An admin user already exists. You can skip this step.
                </div>
                <a href="setup-wizard.php?step=4" class="btn btn-primary">Complete Setup →</a>
            <?php else: ?>
                <form method="POST" onsubmit="return validatePassword()">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" id="admin_username" name="admin_username" required pattern="[a-zA-Z0-9_]+" minlength="3">
                        <p class="help-text">Letters, numbers, and underscores only (3+ characters)</p>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input type="password" id="admin_password" name="admin_password" required minlength="8">
                        <div class="password-requirements">
                            <strong>Password must contain:</strong>
                            <span class="requirement" id="req-length">At least 8 characters</span>
                            <span class="requirement" id="req-upper">One uppercase letter</span>
                            <span class="requirement" id="req-lower">One lowercase letter</span>
                            <span class="requirement" id="req-number">One number</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-group">
                        <label for="setup_token">Setup Token (for future access)</label>
                        <input type="text" id="setup_token" name="setup_token" value="reconfigure" required>
                        <p class="help-text">Save this token - you'll need it to access setup in the future</p>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Admin & Complete Setup</button>
                </form>

                <script>
                    const passwordInput = document.getElementById('admin_password');
                    const requirements = {
                        length: document.getElementById('req-length'),
                        upper: document.getElementById('req-upper'),
                        lower: document.getElementById('req-lower'),
                        number: document.getElementById('req-number')
                    };

                    passwordInput.addEventListener('input', function() {
                        const password = this.value;
                        
                        // Check length
                        if (password.length >= 8) {
                            requirements.length.classList.add('valid');
                        } else {
                            requirements.length.classList.remove('valid');
                        }
                        
                        // Check uppercase
                        if (/[A-Z]/.test(password)) {
                            requirements.upper.classList.add('valid');
                        } else {
                            requirements.upper.classList.remove('valid');
                        }
                        
                        // Check lowercase
                        if (/[a-z]/.test(password)) {
                            requirements.lower.classList.add('valid');
                        } else {
                            requirements.lower.classList.remove('valid');
                        }
                        
                        // Check number
                        if (/\d/.test(password)) {
                            requirements.number.classList.add('valid');
                        } else {
                            requirements.number.classList.remove('valid');
                        }
                    });

                    function validatePassword() {
                        const password = document.getElementById('admin_password').value;
                        const confirm = document.getElementById('confirm_password').value;
                        
                        if (password !== confirm) {
                            alert('Passwords do not match!');
                            return false;
                        }
                        
                        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                            alert('Password does not meet all requirements!');
                            return false;
                        }
                        
                        return true;
                    }
                </script>
            <?php endif; ?>

        <?php elseif ($step == 4): ?>
            <!-- Step 4: Complete -->
            <div class="success-icon">🎉</div>
            <h1>Setup Complete!</h1>
            <p class="subtitle">Your MyParkingManager installation is ready</p>

            <div class="alert alert-success">
                <strong>✅ Installation successful!</strong> Your system is now configured and ready to use.
            </div>

            <div style="background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-bottom: 15px;">Your Login Credentials:</h3>
                <?php if (isset($_SESSION['admin_username'])): ?>
                    <p><strong>Username:</strong> <code><?php echo htmlspecialchars($_SESSION['admin_username']); ?></code></p>
                    <p><strong>Password:</strong> The password you just created</p>
                <?php else: ?>
                    <p>Use the admin credentials you created during setup.</p>
                <?php endif; ?>
            </div>

            <div style="background: #fff5f5; border: 1px solid #feb2b2; border-radius: 6px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #c53030; margin-bottom: 10px;">⚠️ Important Security Steps:</h3>
                <ol style="margin-left: 20px; color: #742a2a;">
                    <li>Delete or rename <code>setup-wizard.php</code> and <code>setup.php</code></li>
                    <li>Remove <code>setup-test-db.php</code> if it exists</li>
                    <li>Secure your <code>config.php</code> file permissions</li>
                    <li>Change the setup token in <code>config.php</code> or remove it entirely</li>
                </ol>
            </div>

            <a href="index.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                Go to Application →
            </a>

            <?php
            // Clean up session
            unset($_SESSION['setup_complete']);
            unset($_SESSION['admin_username']);
            unset($_SESSION['setup_authenticated']);
            ?>
        <?php endif; ?>
    </div>
</body>
</html>