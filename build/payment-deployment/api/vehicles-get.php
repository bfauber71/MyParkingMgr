<?php
/**
 * Get Single Vehicle API Endpoint
 * GET /api/vehicles-get?id=xxx
 */

require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


// CRITICAL: Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

requireAuth();

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    jsonResponse(['error' => 'Vehicle ID is required']);
}

try {
    $db = Database::getInstance();
    
    // Get the vehicle
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        http_response_code(404);
        jsonResponse(['error' => 'Vehicle not found']);
    }
    
    // Check if user has access to this vehicle's property
    $accessibleProperties = getAccessibleProperties();
    $propertyNames = array_column($accessibleProperties, 'name');
    $propertyIds = array_column($accessibleProperties, 'id');
    $allowedProperties = array_merge($propertyNames, $propertyIds);
    
    if (!in_array($vehicle['property'], $allowedProperties)) {
        http_response_code(403);
        jsonResponse(['error' => 'Access denied to this vehicle']);
    }
    
    jsonResponse(['vehicle' => $vehicle]);
    
} catch (Exception $e) {
    error_log("Error fetching vehicle: " . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'Failed to fetch vehicle']);
}
