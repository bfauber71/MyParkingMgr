<?php
/**
 * Permission Verification Tool
 * Use this to check if admin permissions exist in database
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Permission Verification - ManageMyParking</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        h2 { color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .status { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { background: #fee; border-left: 4px solid #dc2626; color: #991b1b; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th { background: #f1f5f9; text-align: left; padding: 10px; border-bottom: 2px solid #cbd5e1; }
        table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Permission Verification Tool</h1>
        <p>This tool checks if admin user permissions exist in the database.</p>
    </div>

<?php
try {
    $db = Database::getInstance();
    
    // Check if user_permissions table exists
    echo '<div class="card"><h2>📊 Database Status</h2>';
    
    $tableCheck = $db->query("SHOW TABLES LIKE 'user_permissions'")->fetch();
    if (!$tableCheck) {
        echo '<div class="status error">';
        echo '<strong>❌ ERROR: user_permissions table does not exist!</strong><br>';
        echo 'You need to run the database migration first.<br>';
        echo 'Go to Settings → Database Operations → Migrate Payment System';
        echo '</div>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<div class="status success">✅ Table <code>user_permissions</code> exists</div>';
    
    // Check admin user
    echo '<h2>👤 Admin User</h2>';
    $adminUser = $db->query("SELECT id, username, role FROM users WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        echo '<div class="status error">❌ ERROR: Admin user not found!</div>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<table>';
    echo '<tr><th>Username</th><td>' . htmlspecialchars($adminUser['username']) . '</td></tr>';
    echo '<tr><th>Role</th><td>' . htmlspecialchars($adminUser['role']) . '</td></tr>';
    echo '<tr><th>User ID</th><td><code>' . htmlspecialchars($adminUser['id']) . '</code></td></tr>';
    echo '</table>';
    
    // Check permissions
    echo '<h2>🔐 Current Permissions</h2>';
    
    $stmt = $db->prepare("
        SELECT module, can_view, can_edit, can_create_delete 
        FROM user_permissions 
        WHERE user_id = ?
        ORDER BY module
    ");
    $stmt->execute([$adminUser['id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($permissions)) {
        echo '<div class="status error">';
        echo '<strong>❌ NO PERMISSIONS FOUND!</strong><br><br>';
        echo 'The admin user exists but has no permissions in the database.<br>';
        echo 'This is why you\'re getting 401 Unauthorized errors.<br><br>';
        echo '<strong>Solution:</strong> Run this SQL in phpMyAdmin:';
        echo '</div>';
        
        echo '<pre><code>SET @admin_user_id = \'' . $adminUser['id'] . '\';

DELETE FROM user_permissions WHERE user_id = @admin_user_id;

INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete)
VALUES 
    (UUID(), @admin_user_id, \'vehicles\', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, \'users\', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, \'properties\', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, \'violations\', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, \'database\', TRUE, TRUE, TRUE);</code></pre>';
        
        echo '<div class="status warning">';
        echo '<strong>⚠️ IMPORTANT:</strong> After running the SQL, you MUST:<br>';
        echo '1. Log out of the app completely<br>';
        echo '2. Clear browser cache (Ctrl+Shift+Delete)<br>';
        echo '3. Log back in<br>';
        echo '4. Then permissions will work!';
        echo '</div>';
        
    } else {
        echo '<div class="status success">✅ Permissions found in database!</div>';
        
        echo '<table>';
        echo '<tr><th>Module</th><th>View</th><th>Edit</th><th>Create/Delete</th></tr>';
        foreach ($permissions as $perm) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($perm['module']) . '</strong></td>';
            echo '<td>' . ($perm['can_view'] ? '✅' : '❌') . '</td>';
            echo '<td>' . ($perm['can_edit'] ? '✅' : '❌') . '</td>';
            echo '<td>' . ($perm['can_create_delete'] ? '✅' : '❌') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check if all required modules are present
        $requiredModules = ['vehicles', 'users', 'properties', 'violations', 'database'];
        $existingModules = array_column($permissions, 'module');
        $missingModules = array_diff($requiredModules, $existingModules);
        
        if (!empty($missingModules)) {
            echo '<div class="status warning">';
            echo '<strong>⚠️ WARNING: Missing permissions for modules:</strong><br>';
            echo implode(', ', $missingModules);
            echo '</div>';
        } else {
            echo '<div class="status success">';
            echo '<strong>✅ All required permissions are present!</strong><br><br>';
            echo 'If you\'re still seeing 401 errors, you need to:<br>';
            echo '1. <strong>Log out completely</strong> from the app<br>';
            echo '2. <strong>Clear browser cache</strong> (Ctrl+Shift+Delete or Cmd+Shift+Delete)<br>';
            echo '3. <strong>Log back in</strong><br>';
            echo '4. Permissions should now work!<br><br>';
            echo '<em>The 401 error happens because your old session has cached empty permissions.<br>';
            echo 'Logging out and back in will load the new permissions from the database.</em>';
            echo '</div>';
        }
    }
    
    echo '</div>'; // Close card
    
} catch (Exception $e) {
    echo '<div class="card">';
    echo '<div class="status error">';
    echo '<strong>❌ Database Error:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
}
?>

    <div class="card" style="background: #f8fafc; border: 1px solid #cbd5e1;">
        <p style="margin:0; color: #64748b; font-size: 14px;">
            <strong>File:</strong> verify-permissions.php<br>
            <strong>Action:</strong> Upload this to your server and access it in your browser<br>
            <strong>Security:</strong> Delete this file after fixing permissions
        </p>
    </div>

</body>
</html>
