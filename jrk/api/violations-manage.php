<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

if (!Session::isAuthenticated()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if (!Session::hasPermission('violations', 'view')) {
    jsonResponse(['error' => 'Permission denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all violations including fines and tow deadlines
    try {
        $sql = "SELECT id, name, fine_amount, tow_deadline_hours, display_order, is_active
                FROM violations
                ORDER BY display_order ASC, name ASC";
        
        $violations = Database::query($sql);
        
        jsonResponse([
            'success' => true,
            'violations' => $violations
        ]);
    } catch (Exception $e) {
        error_log('Fetch violations error: ' . $e->getMessage());
        jsonResponse(['error' => 'Failed to fetch violations'], 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update violation
    if (!Session::hasPermission('violations', 'edit')) {
        jsonResponse(['error' => 'Permission denied'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['name'])) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    if (!validateCsrfToken($data['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    
    try {
        $sql = "UPDATE violations 
                SET name = ?, 
                    fine_amount = ?,
                    tow_deadline_hours = ?,
                    display_order = ?,
                    is_active = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $params = [
            $data['name'],
            isset($data['fine_amount']) && $data['fine_amount'] !== '' ? $data['fine_amount'] : null,
            isset($data['tow_deadline_hours']) && $data['tow_deadline_hours'] !== '' ? $data['tow_deadline_hours'] : null,
            $data['display_order'] ?? 0,
            isset($data['is_active']) ? (bool)$data['is_active'] : true,
            $data['id']
        ];
        
        Database::execute($sql, $params);
        
        logAudit('UPDATE', 'violation', $data['id'], [
            'name' => $data['name'],
            'fine_amount' => $data['fine_amount'] ?? null,
            'tow_deadline_hours' => $data['tow_deadline_hours'] ?? null
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Violation updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log('Violation update error: ' . $e->getMessage());
        jsonResponse(['error' => 'Failed to update violation'], 500);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}