<?php
/**
 * Search Vehicles API Endpoint (v2 - cache-busted)
 * GET /api/vehicles-search-v2?q=keyword&property=name
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// CRITICAL: Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

requireAuth();

$query = $_GET['q'] ?? '';
$propertyFilter = $_GET['property'] ?? '';

// Get accessible properties
$accessibleProperties = getAccessibleProperties();
$propertyIds = array_column($accessibleProperties, 'id');
$propertyNames = array_column($accessibleProperties, 'name');

// DEBUG: Log what we're searching for
error_log("VEHICLES SEARCH DEBUG:");
error_log("Accessible properties count: " . count($accessibleProperties));
error_log("Property IDs: " . json_encode($propertyIds));
error_log("Property Names: " . json_encode($propertyNames));
error_log("Search query: " . $query);
error_log("Property filter: " . $propertyFilter);

if (empty($propertyIds)) {
    jsonResponse(['vehicles' => []]);
}

// Build SQL query - FIXED: Use property IDs instead of names
// Vehicles can have EITHER property IDs (UUID) OR property names, so check both
$placeholders = implode(',', array_fill(0, count($propertyIds) + count($propertyNames), '?'));
$sql = "SELECT * FROM vehicles WHERE property IN (" . $placeholders . ")";
$params = array_merge($propertyIds, $propertyNames);

error_log("SQL: " . $sql);
error_log("Params: " . json_encode($params));

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

// DEBUG: Log results
error_log("Vehicles found: " . count($vehicles));
if (count($vehicles) > 0) {
    error_log("First vehicle property value: " . ($vehicles[0]['property'] ?? 'NULL'));
    error_log("First vehicle data: " . json_encode($vehicles[0]));
}

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
