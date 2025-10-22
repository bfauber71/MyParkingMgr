<?php
/**
 * Search Vehicles API Endpoint
 * GET /api/vehicles/search?q=keyword&property=name
 */

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

jsonResponse(['vehicles' => $vehicles]);
