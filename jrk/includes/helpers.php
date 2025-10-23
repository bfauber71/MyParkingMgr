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
 * Get request input as JSON
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
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
 * Check if user has role
 */
function hasRole($role) {
    $user = Session::user();
    return $user && $user['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('Admin');
}

/**
 * Check if user is operator
 */
function isOperator() {
    return hasRole('Operator');
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
    
    // Admins and operators can access all properties
    if ($user['role'] === 'Admin' || $user['role'] === 'Operator') {
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
    
    // Admins and operators see all properties
    if ($user['role'] === 'Admin' || $user['role'] === 'Operator') {
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
