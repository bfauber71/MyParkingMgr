<?php
/**
 * ManageMyParking Diagnostic Test
 * 
 * This file helps diagnose common installation issues.
 * Upload this to your root directory and visit:
 * https://yourdomain.com/diagnostic-test.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManageMyParking - Diagnostic Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 { color: #1e293b; margin-bottom: 30px; }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        .test-section h2 {
            color: #334155;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .pass {
            background: #dcfce7;
            border-color: #22c55e;
        }
        .pass h2 { color: #166534; }
        .fail {
            background: #fee2e2;
            border-color: #ef4444;
        }
        .fail h2 { color: #991b1b; }
        .warn {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .warn h2 { color: #92400e; }
        .result {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            color: #1e293b;
        }
        .result strong { color: #0f172a; }
        ul { margin: 10px 0 10px 20px; }
        li { margin: 5px 0; }
        .action {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #3b82f6;
        }
        code {
            background: #f8fafc;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #dc2626;
        }
        .delete-notice {
            margin-top: 30px;
            padding: 15px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            color: #991b1b;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ManageMyParking - Diagnostic Test</h1>
        
        <?php
        $tests = [];
        
        // Test 1: PHP Version
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        $tests[] = [
            'name' => 'PHP Version',
            'pass' => $phpOk,
            'message' => $phpOk 
                ? "✅ PHP $phpVersion (OK)" 
                : "❌ PHP $phpVersion (Need 7.4+)",
            'action' => $phpOk ? null : 'Contact hosting to upgrade PHP to 7.4 or higher'
        ];
        
        // Test 2: Required Extensions
        $extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];
        $missingExt = [];
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExt[] = $ext;
            }
        }
        $tests[] = [
            'name' => 'PHP Extensions',
            'pass' => empty($missingExt),
            'message' => empty($missingExt) 
                ? '✅ All required extensions loaded' 
                : '❌ Missing extensions: ' . implode(', ', $missingExt),
            'action' => empty($missingExt) ? null : 'Contact hosting to enable: ' . implode(', ', $missingExt)
        ];
        
        // Test 3: Config File
        $configExists = file_exists(__DIR__ . '/config.php');
        $tests[] = [
            'name' => 'Configuration File',
            'pass' => $configExists,
            'message' => $configExists 
                ? '✅ config.php found' 
                : '❌ config.php not found',
            'action' => $configExists ? null : 'Upload config.php to root directory'
        ];
        
        // Test 4: API Directory
        $apiExists = is_dir(__DIR__ . '/api');
        $tests[] = [
            'name' => 'API Directory',
            'pass' => $apiExists,
            'message' => $apiExists 
                ? '✅ api/ directory found' 
                : '❌ api/ directory not found',
            'action' => $apiExists ? null : 'Upload api/ folder with all PHP files'
        ];
        
        // Test 5: Critical API Files
        if ($apiExists) {
            $apiFiles = ['csrf-token.php', 'login.php', 'user.php'];
            $missingApi = [];
            foreach ($apiFiles as $file) {
                if (!file_exists(__DIR__ . '/api/' . $file)) {
                    $missingApi[] = $file;
                }
            }
            $tests[] = [
                'name' => 'API Files',
                'pass' => empty($missingApi),
                'message' => empty($missingApi) 
                    ? '✅ All critical API files present' 
                    : '❌ Missing API files: ' . implode(', ', $missingApi),
                'action' => empty($missingApi) ? null : 'Upload missing files to api/ directory'
            ];
        }
        
        // Test 6: .htaccess File
        $htaccessExists = file_exists(__DIR__ . '/.htaccess');
        $tests[] = [
            'name' => '.htaccess File',
            'pass' => $htaccessExists,
            'message' => $htaccessExists 
                ? '✅ .htaccess found' 
                : '❌ .htaccess not found',
            'action' => $htaccessExists ? null : 'Upload .htaccess to root directory (enable "Show Hidden Files" in file manager)'
        ];
        
        // Test 7: mod_rewrite (if .htaccess exists)
        if ($htaccessExists && function_exists('apache_get_modules')) {
            $modRewrite = in_array('mod_rewrite', apache_get_modules());
            $tests[] = [
                'name' => 'Apache mod_rewrite',
                'pass' => $modRewrite,
                'message' => $modRewrite 
                    ? '✅ mod_rewrite enabled' 
                    : '❌ mod_rewrite disabled',
                'action' => $modRewrite ? null : 'Contact hosting to enable mod_rewrite'
            ];
        }
        
        // Test 8: Write Permissions
        $qrDir = __DIR__ . '/qrcodes';
        $qrWritable = is_dir($qrDir) && is_writable($qrDir);
        $tests[] = [
            'name' => 'QR Codes Directory',
            'pass' => $qrWritable,
            'message' => $qrWritable 
                ? '✅ qrcodes/ directory writable' 
                : '⚠️ qrcodes/ directory not writable',
            'action' => $qrWritable ? null : 'Create qrcodes/ folder and set permissions to 755',
            'warn' => !$qrWritable
        ];
        
        // Test 9: Config Directory (for encryption key)
        $configDir = __DIR__ . '/config';
        $configDirWritable = is_dir($configDir) && is_writable($configDir);
        $tests[] = [
            'name' => 'Config Directory',
            'pass' => $configDirWritable,
            'message' => $configDirWritable 
                ? '✅ config/ directory writable' 
                : '⚠️ config/ directory not writable',
            'action' => $configDirWritable ? null : 'Create config/ folder and set permissions to 755',
            'warn' => !$configDirWritable
        ];
        
        // Test 10: Database Connection (if config exists)
        if ($configExists) {
            try {
                $config = require __DIR__ . '/config.php';
                $dbConfig = $config['db'] ?? null;
                
                if ($dbConfig) {
                    try {
                        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
                        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        $tests[] = [
                            'name' => 'Database Connection',
                            'pass' => true,
                            'message' => '✅ Database connection successful',
                            'action' => null
                        ];
                        
                        // Check if tables exist
                        $stmt = $pdo->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (count($tables) > 0) {
                            $tests[] = [
                                'name' => 'Database Tables',
                                'pass' => true,
                                'message' => '✅ Found ' . count($tables) . ' tables',
                                'action' => null
                            ];
                        } else {
                            $tests[] = [
                                'name' => 'Database Tables',
                                'pass' => false,
                                'message' => '❌ No tables found in database',
                                'action' => 'Import COMPLETE-INSTALL.sql via phpMyAdmin'
                            ];
                        }
                        
                    } catch (PDOException $e) {
                        $tests[] = [
                            'name' => 'Database Connection',
                            'pass' => false,
                            'message' => '❌ Database connection failed: ' . $e->getMessage(),
                            'action' => 'Check database credentials in config.php'
                        ];
                    }
                }
            } catch (Exception $e) {
                $tests[] = [
                    'name' => 'Configuration',
                    'pass' => false,
                    'message' => '❌ Error loading config: ' . $e->getMessage(),
                    'action' => 'Check config.php syntax'
                ];
            }
        }
        
        // Display Results
        foreach ($tests as $test) {
            $class = $test['pass'] ? 'pass' : (isset($test['warn']) && $test['warn'] ? 'warn' : 'fail');
            echo '<div class="test-section ' . $class . '">';
            echo '<h2>' . htmlspecialchars($test['name']) . '</h2>';
            echo '<div class="result">';
            echo htmlspecialchars($test['message']);
            if ($test['action']) {
                echo '<div class="action"><strong>Action Required:</strong> ' . htmlspecialchars($test['action']) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        // Summary
        $passed = count(array_filter($tests, function($t) { return $t['pass']; }));
        $total = count($tests);
        ?>
        
        <div class="test-section <?php echo $passed === $total ? 'pass' : 'warn'; ?>">
            <h2>Summary</h2>
            <div class="result">
                <strong><?php echo $passed; ?> of <?php echo $total; ?> tests passed</strong>
                
                <?php if ($passed === $total): ?>
                    <p style="margin-top: 10px;">
                        ✅ All tests passed! Your installation looks good.<br>
                        If you're still having issues, try accessing the API directly:<br>
                        <code>https://yourdomain.com/api/csrf-token.php</code>
                    </p>
                <?php else: ?>
                    <p style="margin-top: 10px;">
                        ⚠️ Some tests failed. Fix the issues above and refresh this page.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="delete-notice">
            🗑️ Delete this file (diagnostic-test.php) after fixing issues
        </div>
    </div>
</body>
</html>
