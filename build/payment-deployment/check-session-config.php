<!DOCTYPE html>
<html>
<head>
    <title>Session Cookie Diagnostic - ManageMyParking</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        h2 { color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .status { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { background: #fee; border-left: 4px solid #dc2626; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th { background: #f1f5f9; text-align: left; padding: 10px; border-bottom: 2px solid #cbd5e1; }
        table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .fix-btn { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .fix-btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Session Cookie Diagnostic Tool</h1>
        <p>This tool helps diagnose and fix the 401 Unauthorized error you're experiencing.</p>
    </div>

    <?php
    // Load configuration
    $config = require __DIR__ . '/config.php';
    
    // Detect current settings
    $basePath = $config['base_path'] ?? '';
    $cookiePath = $basePath ? $basePath . '/' : '/';
    $sessionName = $config['session']['name'] ?? 'myparkingmanager_session';
    
    // Check if session cookie exists
    session_name($sessionName);
    $sessionExists = isset($_COOKIE[$sessionName]);
    
    // Problem detection
    $hasProblem = !empty($basePath);
    ?>

    <div class="card">
        <h2>📊 Current Configuration</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Current Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><strong>Base Path</strong></td>
                <td><code><?php echo htmlspecialchars($basePath ?: '(empty)'); ?></code></td>
                <td><?php echo $hasProblem ? '<span style="color:#dc2626">❌ PROBLEM</span>' : '<span style="color:#10b981">✅ OK</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Session Cookie Path</strong></td>
                <td><code><?php echo htmlspecialchars($cookiePath); ?></code></td>
                <td><?php echo $hasProblem ? '<span style="color:#dc2626">❌ TOO RESTRICTIVE</span>' : '<span style="color:#10b981">✅ OK</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Session Name</strong></td>
                <td><code><?php echo htmlspecialchars($sessionName); ?></code></td>
                <td><span style="color:#10b981">✅ OK</span></td>
            </tr>
            <tr>
                <td><strong>Session Cookie Exists</strong></td>
                <td><?php echo $sessionExists ? 'Yes' : 'No'; ?></td>
                <td><?php echo $sessionExists ? '<span style="color:#10b981">✅ Found</span>' : '<span style="color:#f59e0b">⚠️ Not Found</span>'; ?></td>
            </tr>
        </table>
    </div>

    <?php if ($hasProblem): ?>
    <div class="card">
        <div class="status error">
            <h2 style="margin-top:0;">❌ Problem Detected!</h2>
            <p><strong>Your session cookie path is set to:</strong> <code><?php echo htmlspecialchars($cookiePath); ?></code></p>
            <p>This means the browser only sends the session cookie to URLs starting with <code><?php echo htmlspecialchars($cookiePath); ?></code></p>
            <p><strong>But your API endpoints are at:</strong> <code>/api/users-list</code>, <code>/api/violations-list</code>, etc.</p>
            <p>Since <code>/api</code> is not under <code><?php echo htmlspecialchars($cookiePath); ?></code>, the API never receives the session cookie!</p>
        </div>

        <h2>🔧 The Fix</h2>
        <p>You need to update your <code>config.php</code> file to set <code>base_path</code> to an empty string:</p>
        
        <pre><code>return [
    'app_name' => 'MyParkingManager',
    'app_url' => 'https://yoursite.com',
    <strong>'base_path' => '',  // ← CHANGE THIS TO EMPTY STRING</strong>
    
    'db' => [
        // ... your database settings ...
    ],
    // ... rest of config ...
];</code></pre>

        <p><strong>Steps to fix:</strong></p>
        <ol>
            <li>Open <code>config.php</code> in your hosting file manager or FTP</li>
            <li>Find the line with <code>'base_path' => '...'</code></li>
            <li>Change it to <code>'base_path' => ''</code> (empty string)</li>
            <li>Save the file</li>
            <li>Log out and log back in to your app</li>
        </ol>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="status success">
            <h2 style="margin-top:0;">✅ Configuration Looks Good!</h2>
            <p>Your <code>base_path</code> is correctly set to an empty string.</p>
            <p>Session cookies should be sent to all paths including <code>/api/*</code></p>
        </div>

        <h2>🔍 Next Steps</h2>
        <p>If you're still seeing 401 errors, the issue might be:</p>
        <ol>
            <li><strong>Cached session:</strong> Log out completely and log back in</li>
            <li><strong>Browser cache:</strong> Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)</li>
            <li><strong>Missing permissions:</strong> Run the fix-admin-permissions.sql script in phpMyAdmin</li>
        </ol>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>📋 Permission Fix SQL</h2>
        <p>After fixing the session cookie path, you still need to grant permissions to the admin user:</p>
        <pre><code>-- Run this in phpMyAdmin SQL tab:

SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);

DELETE FROM user_permissions WHERE user_id = @admin_user_id;

INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete)
VALUES 
    (UUID(), @admin_user_id, 'vehicles', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'users', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'properties', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'violations', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'database', TRUE, TRUE, TRUE);

-- Verify it worked:
SELECT u.username, p.module, p.can_view, p.can_edit, p.can_create_delete
FROM user_permissions p
JOIN users u ON p.user_id = u.id
WHERE u.username = 'admin';</code></pre>
    </div>

    <div class="card">
        <h2>🎯 Quick Test</h2>
        <p>After fixing config.php and running the SQL:</p>
        <ol>
            <li>Log out of the app completely</li>
            <li>Clear browser cache (Ctrl+Shift+Del)</li>
            <li>Log back in with username: <code>admin</code></li>
            <li>Try accessing Settings → Users or Settings → Violations</li>
            <li>Should work without 401 errors!</li>
        </ol>
    </div>

    <div class="card" style="background: #f8fafc; border: 1px solid #cbd5e1;">
        <p style="margin:0; color: #64748b; font-size: 14px;">
            <strong>File Location:</strong> check-session-config.php<br>
            <strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Delete this file after fixing the issue for security.</strong>
        </p>
    </div>
</body>
</html>
