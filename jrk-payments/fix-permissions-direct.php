<?php
/**
 * Direct Permission Fix Tool
 * Run this to add permissions to admin user WITHOUT needing to login
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Permission Fix - ManageMyParking</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        .status { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { background: #fee; border-left: 4px solid #dc2626; color: #991b1b; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; }
        .btn { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }
        .btn:hover { background: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th { background: #f1f5f9; text-align: left; padding: 10px; }
        table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Direct Permission Fix Tool</h1>
        <p>This tool adds permissions to the admin user directly, without requiring login.</p>
    </div>

<?php
$fixed = false;
$error = null;

if (isset($_POST['fix_permissions'])) {
    try {
        $db = Database::getInstance();
        
        // Get admin user
        $admin = $db->query("SELECT id, username, role FROM users WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception('Admin user not found in database');
        }
        
        $adminId = $admin['id'];
        
        // Delete existing permissions
        $db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$adminId]);
        
        // Insert new permissions
        $stmt = $db->prepare("
            INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete, created_at, updated_at)
            VALUES (UUID(), ?, ?, TRUE, TRUE, TRUE, NOW(), NOW())
        ");
        
        $modules = ['vehicles', 'users', 'properties', 'violations', 'database'];
        foreach ($modules as $module) {
            $stmt->execute([$adminId, $module]);
        }
        
        $fixed = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Always show current status
try {
    $db = Database::getInstance();
    
    // Check if user_permissions table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'user_permissions'")->fetch();
    
    if (!$tableCheck) {
        echo '<div class="card">';
        echo '<div class="status error">';
        echo '<strong>❌ ERROR: user_permissions table does not exist!</strong><br><br>';
        echo 'You need to create the table first. Run this SQL in phpMyAdmin:<br><br>';
        echo '<code>See COMPLETE-INSTALL.sql for table structure</code>';
        echo '</div></div>';
        echo '</body></html>';
        exit;
    }
    
    // Get admin user
    $admin = $db->query("SELECT id, username, role FROM users WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo '<div class="card">';
        echo '<div class="status error">❌ Admin user not found in database!</div>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<div class="card">';
    echo '<h2>👤 Admin User</h2>';
    echo '<table>';
    echo '<tr><th>Username</th><td>' . htmlspecialchars($admin['username']) . '</td></tr>';
    echo '<tr><th>Role</th><td>' . htmlspecialchars($admin['role']) . '</td></tr>';
    echo '<tr><th>User ID</th><td><code>' . htmlspecialchars($admin['id']) . '</code></td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Check current permissions
    $stmt = $db->prepare("SELECT module, can_view, can_edit, can_create_delete FROM user_permissions WHERE user_id = ? ORDER BY module");
    $stmt->execute([$admin['id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="card">';
    echo '<h2>🔐 Current Permissions</h2>';
    
    if (empty($permissions)) {
        echo '<div class="status error">';
        echo '<strong>❌ NO PERMISSIONS FOUND</strong><br>';
        echo 'The admin user has no permissions. This is causing the 401 errors.';
        echo '</div>';
        
        echo '<form method="POST">';
        echo '<button type="submit" name="fix_permissions" class="btn">🔧 Fix Permissions Now</button>';
        echo '</form>';
        
    } else {
        echo '<div class="status success">✅ Permissions exist in database</div>';
        
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
        
        $requiredModules = ['vehicles', 'users', 'properties', 'violations', 'database'];
        $existingModules = array_column($permissions, 'module');
        $missingModules = array_diff($requiredModules, $existingModules);
        
        if (!empty($missingModules)) {
            echo '<div class="status warning">';
            echo '<strong>⚠️ Missing modules:</strong> ' . implode(', ', $missingModules);
            echo '</div>';
            echo '<form method="POST">';
            echo '<button type="submit" name="fix_permissions" class="btn">🔧 Add Missing Permissions</button>';
            echo '</form>';
        }
    }
    
    echo '</div>';
    
    if ($fixed) {
        echo '<div class="card">';
        echo '<div class="status success">';
        echo '<strong>✅ PERMISSIONS FIXED!</strong><br><br>';
        echo '<strong>IMPORTANT - Do these steps NOW:</strong><br>';
        echo '1. <strong>Log out</strong> from the app (if you\'re logged in)<br>';
        echo '2. <strong>Clear browser cache:</strong> Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)<br>';
        echo '3. <strong>Close all browser tabs</strong> for this site<br>';
        echo '4. <strong>Log back in</strong> with username: admin<br>';
        echo '5. The 401 errors should be gone!<br><br>';
        echo '<em>Clearing cache is critical - your old session has cached empty permissions.</em>';
        echo '</div>';
        echo '</div>';
    }
    
    if ($error) {
        echo '<div class="card">';
        echo '<div class="status error">';
        echo '<strong>❌ Error:</strong> ' . htmlspecialchars($error);
        echo '</div>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="card">';
    echo '<div class="status error">';
    echo '<strong>❌ Database Error:</strong><br>' . htmlspecialchars($e->getMessage());
    echo '</div></div>';
}
?>

    <div class="card" style="background: #f8fafc; border: 1px solid #cbd5e1;">
        <p style="margin:0; color: #64748b; font-size: 14px;">
            <strong>File:</strong> fix-permissions-direct.php<br>
            <strong>Security:</strong> Delete this file after fixing permissions
        </p>
    </div>

</body>
</html>
