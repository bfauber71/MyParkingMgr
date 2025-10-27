<?php
/**
 * Search Vehicles API Endpoint
 * GET /api/vehicles/search?q=keyword&property=name
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

$query = $_GET['q'] ?? '';
$propertyFilter = $_GET['property'] ?? '';

// Get accessible properties
$accessibleProperties = getAccessibleProperties();
$propertyIds = array_column($accessibleProperties, 'id');
$propertyNames = array_column($accessibleProperties, 'name');

if (empty($propertyNames)) {
    jsonResponse(['vehicles' => []]);
}

// Build SQL query
$sql = "SELECT * FROM vehicles WHERE property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")";
$params = $propertyNames;

// Add property filter
if ($propertyFilter) {
    $sql .= " AND property = ?";
    $params[] = $propertyFilter;
}

// Add search query
if ($query) {
    $sql .= " AND (
        tag_number LIKE ? OR
        plate_number LIKE ? OR
        make LIKE ? OR
        model LIKE ? OR
        owner_name LIKE ? OR
        apt_number LIKE ? OR
        color LIKE ? OR
        year LIKE ?
    )";
    $searchTerm = '%' . $query . '%';
    for ($i = 0; $i < 8; $i++) {
        $params[] = $searchTerm;
    }
}

$sql .= " ORDER BY created_at DESC LIMIT 1000";

$vehicles = Database::query($sql, $params);

// Add violation count to each vehicle
try {
    $db = Database::getInstance();
    
    // Check if violation_tickets table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'violation_tickets'");
    $tableExists = $tableCheck->fetch() !== false;
    
    if ($tableExists) {
        foreach ($vehicles as &$vehicle) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM violation_tickets WHERE vehicle_id = ?");
            $stmt->execute([$vehicle['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $vehicle['violation_count'] = (int)($result['count'] ?? 0);
        }
        unset($vehicle); // Break reference
    } else {
        // Table doesn't exist yet, set all counts to 0
        foreach ($vehicles as &$vehicle) {
            $vehicle['violation_count'] = 0;
        }
        unset($vehicle); // Break reference
    }
} catch (Exception $e) {
    // If there's an error, set all counts to 0 and log it
    error_log("Error fetching violation counts: " . $e->getMessage());
    foreach ($vehicles as &$vehicle) {
        $vehicle['violation_count'] = 0;
    }
    unset($vehicle); // Break reference
}

jsonResponse(['vehicles' => $vehicles]);
