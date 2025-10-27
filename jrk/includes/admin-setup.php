<?php
/**
 * Admin Setup Component
 * Handles first admin user creation during initial setup
 */

/**
 * Check if any admin users exist in the database
 */
function adminUserExists($pdo) {
    try {
        // Check if users table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$tableCheck) {
            return false;
        }
        
        // Check for admin users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result && $result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create the first admin user
 */
function createAdminUser($pdo, $username, $email, $password) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Generate UUID for user
        $userId = generateUUID();
        
        // Hash the password using bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Create the admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, password, role, email, created_at) 
            VALUES (?, ?, ?, 'admin', ?, NOW())
        ");
        $stmt->execute([$userId, $username, $hashedPassword, $email]);
        
        // Create permissions for all modules
        $modules = ['vehicles', 'users', 'properties', 'violations', 'database'];
        foreach ($modules as $module) {
            $permId = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO user_permissions 
                (id, user_id, module, can_view, can_edit, can_create_delete, created_at) 
                VALUES (?, ?, ?, TRUE, TRUE, TRUE, NOW())
            ");
            $stmt->execute([$permId, $userId, $module]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Admin user created successfully',
            'user_id' => $userId
        ];
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        // Check for duplicate username
        if ($e->getCode() == 23000) {
            return [
                'success' => false,
                'error' => 'Username already exists. Please choose a different username.'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create admin user: ' . $e->getMessage()
        ];
    }
}

/**
 * Validate admin user input
 */
function validateAdminInput($username, $email, $password, $confirmPassword) {
    $errors = [];
    
    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    }
    
    // Confirm password
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    return $errors;
}

/**
 * Generate a UUID v4
 */
function generateUUID() {
    $data = random_bytes(16);
    
    // Set version (4) and variant bits
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return sprintf('%08s-%04s-%04s-%04s-%12s',
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6))
    );
}

/**
 * Check database tables exist
 */
function checkDatabaseTables($pdo) {
    $requiredTables = ['users', 'user_permissions', 'properties', 'vehicles'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            $missingTables[] = $table;
        }
    }
    
    return $missingTables;
}

/**
 * Import SQL file
 */
function importSQLFile($pdo, $filePath) {
    try {
        $sql = file_get_contents($filePath);
        
        // Remove comments and split by semicolon
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\//sm', '', $sql); // Remove /* */ comments
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        // Disable foreign key checks during import
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // Execute queries without transaction (some DDL statements auto-commit)
        $count = 0;
        foreach ($queries as $query) {
            if (!empty($query)) {
                // Use exec() for DDL statements
                $pdo->exec($query);
                $count++;
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        return ['success' => true, 'queries' => $count];
    } catch (PDOException $e) {
        // Make sure to re-enable foreign key checks even on error
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Exception $ignored) {}
        return ['success' => false, 'error' => $e->getMessage()];
    }
}