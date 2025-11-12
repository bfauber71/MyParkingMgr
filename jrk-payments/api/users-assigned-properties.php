<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


header('Content-Type: application/json');

Session::start();

// Require authentication and view permission for users
requirePermission(MODULE_USERS, ACTION_VIEW);

$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    jsonResponse(['error' => 'User ID is required'], 400);
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        SELECT property_id 
        FROM user_assigned_properties 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $propertyIds = array_map(function($row) {
        return $row['property_id'];
    }, $results);
    
    jsonResponse([
        'success' => true,
        'property_ids' => $propertyIds
    ]);
} catch (PDOException $e) {
    error_log("Error fetching user assigned properties: " . $e->getMessage());
    jsonResponse(['error' => 'Database error'], 500);
}
