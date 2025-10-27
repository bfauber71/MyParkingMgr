<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request input as JSON (with caching to prevent body consumption)
 */
function getJsonInput() {
    static $cachedInput = null;
    
    if ($cachedInput === null) {
        $input = file_get_contents('php://input');
        $cachedInput = json_decode($input, true) ?: [];
    }
    
    return $cachedInput;
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Sanitize string
 */
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Get client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Get user agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

/**
 * Log audit event
 */
function auditLog($action, $entityType, $entityId = null, $details = null) {
    $user = Session::user();
    if (!$user) {
        return;
    }
    
    $sql = "INSERT INTO audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    Database::execute($sql, [
        $user['id'],
        $user['username'],
        $action,
        $entityType,
        $entityId,
        $details ? json_encode($details) : null,
        getClientIp(),
        getUserAgent()
    ]);
}

/**
 * Save user assigned properties
 * @param string $userId User ID
 * @param array $propertyIds Array of property IDs to assign to the user
 */
function saveUserAssignedProperties($userId, $propertyIds) {
    $db = Database::getInstance();
    
    try {
        // Check if table exists
        $tableExists = $db->query("SHOW TABLES LIKE 'user_assigned_properties'")->fetch();
        if (!$tableExists) {
            return; // Silently skip if table doesn't exist
        }
        
        // Delete existing assignments for this user
        $stmt = $db->prepare("DELETE FROM user_assigned_properties WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new assignments
        if (!empty($propertyIds) && is_array($propertyIds)) {
            $stmt = $db->prepare("
                INSERT INTO user_assigned_properties (user_id, property_id, assigned_at)
                VALUES (?, ?, NOW())
            ");
            
            foreach ($propertyIds as $propertyId) {
                if (!empty($propertyId)) {
                    $stmt->execute([$userId, $propertyId]);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error saving user assigned properties: " . $e->getMessage());
        // Don't throw - allow operation to continue even if property assignment save fails
    }
}

/**
 * Save user permissions
 * @param string $userId User ID
 * @param array $permissions Permissions array [module => [can_view, can_edit, can_create_delete]]
 */

function saveUserPermissions($userId, $permissions) {
    if (empty($permissions) || !is_array($permissions)) {
        return;
    }
    
    $db = Database::getInstance();
    
    try {
        // Check if permissions table exists
        $tableExists = $db->query("SHOW TABLES LIKE 'user_permissions'")->fetch();
        if (!$tableExists) {
            return; // Silently skip if table doesn't exist (backward compatibility)
        }
        
        // Delete existing permissions for this user
        $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new permissions
        $stmt = $db->prepare("
            INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete, created_at, updated_at)
            VALUES (UUID(), ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($permissions as $module => $perms) {
            if (!in_array($module, [MODULE_VEHICLES, MODULE_USERS, MODULE_PROPERTIES, MODULE_VIOLATIONS, MODULE_DATABASE])) {
                continue;
            }
            
            $stmt->execute([
                $userId,
                $module,
                !empty($perms['can_view']) ? 1 : 0,
                !empty($perms['can_edit']) ? 1 : 0,
                !empty($perms['can_create_delete']) ? 1 : 0
            ]);
        }
    } catch (PDOException $e) {
        error_log("Save User Permissions Error: " . $e->getMessage());
        // Don't throw - allow operation to continue even if permissions save fails
    }
}

/**
 * Check if user has role (case-insensitive)
 */
function hasRole($role) {
    $user = Session::user();
    return $user && strcasecmp($user['role'], $role) === 0;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is operator
 */
function isOperator() {
    return hasRole('operator');
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!Session::isAuthenticated()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        jsonResponse(['error' => 'Forbidden - Admin access required'], 403);
    }
}

/**
 * Check if user can access property
 */
function canAccessProperty($propertyId) {
    $user = Session::user();
    if (!$user) {
        return false;
    }
    
    // Admins and operators can access all properties (case-insensitive)
    $role = strtolower($user['role']);
    if ($role === 'admin' || $role === 'operator') {
        return true;
    }
    
    // Check if user is assigned to this property
    $sql = "SELECT 1 FROM user_assigned_properties 
            WHERE user_id = ? AND property_id = ? LIMIT 1";
    $result = Database::queryOne($sql, [$user['id'], $propertyId]);
    return $result !== false;
}

/**
 * Get user's accessible properties
 */
function getAccessibleProperties() {
    $user = Session::user();
    if (!$user) {
        return [];
    }
    
    // Admins and operators see all properties (case-insensitive)
    $role = strtolower($user['role']);
    if ($role === 'admin' || $role === 'operator') {
        return Database::query("SELECT id, name FROM properties ORDER BY name");
    }
    
    // Users see only assigned properties
    $sql = "SELECT p.id, p.name 
            FROM properties p
            INNER JOIN user_assigned_properties uap ON p.id = uap.property_id
            WHERE uap.user_id = ?
            ORDER BY p.name";
    return Database::query($sql, [$user['id']]);
}

/**
 * Export array to CSV
 */
function exportToCsv($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * ============================================================================
 * PERMISSION MATRIX SYSTEM
 * ============================================================================
 */

// Module constants
define('MODULE_VEHICLES', 'vehicles');
define('MODULE_USERS', 'users');
define('MODULE_PROPERTIES', 'properties');
define('MODULE_VIOLATIONS', 'violations');
define('MODULE_DATABASE', 'database');

// Action constants
define('ACTION_VIEW', 'view');
define('ACTION_EDIT', 'edit');
define('ACTION_CREATE_DELETE', 'create_delete');

/**
 * Check if user has specific permission
 * @param string $module Module name (vehicles, users, properties, violations, database)
 * @param string $action Action (view, edit, create_delete)
 * @return bool True if user has permission
 */
function hasPermission($module, $action) {
    $user = Session::user();
    if (!$user) {
        return false;
    }
    
    // Check if permissions are loaded
    if (!isset($user['permissions']) || empty($user['permissions'])) {
        // BACKWARD COMPATIBILITY: Fall back to role-based permissions
        // if permissions table doesn't exist yet
        return hasPermissionByRole($module, $action, $user['role'] ?? '');
    }
    
    // Get module permissions
    $modulePerms = $user['permissions'][$module] ?? null;
    if (!$modulePerms) {
        return false;
    }
    
    // Check action with hierarchy enforcement
    // create_delete implies edit and view
    // edit implies view
    switch ($action) {
        case ACTION_VIEW:
            return $modulePerms['can_view'] || $modulePerms['can_edit'] || $modulePerms['can_create_delete'];
        
        case ACTION_EDIT:
            return $modulePerms['can_edit'] || $modulePerms['can_create_delete'];
        
        case ACTION_CREATE_DELETE:
            return $modulePerms['can_create_delete'];
        
        default:
            return false;
    }
}

/**
 * Legacy role-based permission check (backward compatibility)
 * Matches original RBAC behavior plus database module:
 * - Admin: All permissions on all modules (including database)
 * - User: All permissions on vehicles ONLY
 * - Operator: View-only on vehicles ONLY
 * 
 * @param string $module Module name
 * @param string $action Action
 * @param string $role User role
 * @return bool True if role has permission
 */
function hasPermissionByRole($module, $action, $role) {
    $role = strtolower($role);
    
    // Admin has all permissions on all modules (vehicles, users, properties, violations, database)
    if ($role === 'admin') {
        return true;
    }
    
    // Operator has view-only on vehicles ONLY
    if ($role === 'operator') {
        return $module === MODULE_VEHICLES && $action === ACTION_VIEW;
    }
    
    // User role has all permissions on vehicles ONLY
    if ($role === 'user') {
        return $module === MODULE_VEHICLES;
    }
    
    return false;
}

/**
 * Require specific permission or return 403 error
 * @param string $module Module name
 * @param string $action Action
 */
function requirePermission($module, $action) {
    requireAuth();
    
    if (!hasPermission($module, $action)) {
        $actionLabel = ucfirst(str_replace('_', '/', $action));
        $moduleLabel = ucfirst($module);
        jsonResponse([
            'error' => "Forbidden - You don't have permission to {$actionLabel} {$moduleLabel}"
        ], 403);
    }
}

/**
 * Get all permissions for current user
 * @return array Associative array of module => permissions
 */
function getPermissions() {
    $user = Session::user();
    if (!$user || !isset($user['permissions'])) {
        return [];
    }
    
    return $user['permissions'];
}

/**
 * Check if user has any permission for a module
 * @param string $module Module name
 * @return bool True if user has at least view permission
 */
function canAccessModule($module) {
    return hasPermission($module, ACTION_VIEW);
}

/**
 * Load user permissions from database
 * @param string $userId User ID
 * @return array Associative array of module => permissions
 */
function loadUserPermissions($userId) {
    $sql = "SELECT module, can_view, can_edit, can_create_delete 
            FROM user_permissions 
            WHERE user_id = ?";
    
    $rows = Database::query($sql, [$userId]);
    
    $permissions = [];
    foreach ($rows as $row) {
        $permissions[$row['module']] = [
            'can_view' => (bool)$row['can_view'],
            'can_edit' => (bool)$row['can_edit'],
            'can_create_delete' => (bool)$row['can_create_delete']
        ];
    }
    
    // Ensure all modules exist with default false values
    $modules = [MODULE_VEHICLES, MODULE_USERS, MODULE_PROPERTIES, MODULE_VIOLATIONS, MODULE_DATABASE];
    foreach ($modules as $module) {
        if (!isset($permissions[$module])) {
            $permissions[$module] = [
                'can_view' => false,
                'can_edit' => false,
                'can_create_delete' => false
            ];
        }
    }
    
    return $permissions;
}
