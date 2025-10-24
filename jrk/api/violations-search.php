<?php
/**
 * Violation Search API Endpoint
 * POST /api/violations-search
 * Search violation tickets with filters
 */

requireAuth();
requirePermission(MODULE_DATABASE, ACTION_VIEW);

$input = json_decode(file_get_contents('php://input'), true);

$startDate = $input['start_date'] ?? null;
$endDate = $input['end_date'] ?? null;
$property = $input['property'] ?? null;
$violationType = $input['violation_type'] ?? null;
$searchQuery = $input['query'] ?? '';

// Get accessible properties for filtering
$accessibleProperties = getAccessibleProperties();
$propertyNames = array_column($accessibleProperties, 'name');

if (empty($propertyNames)) {
    jsonResponse(['violations' => [], 'total' => 0]);
}

// Build SQL query
$sql = "SELECT 
    vt.id,
    vt.vehicle_id,
    vt.violation_types,
    vt.custom_note,
    vt.created_at,
    vt.created_by,
    v.year,
    v.make,
    v.model,
    v.color,
    v.plate_number,
    v.tag_number,
    v.property,
    u.username as issuing_user
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN users u ON vt.created_by = u.id
WHERE v.property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")";

$params = $propertyNames;

// Date range filter
if ($startDate) {
    $sql .= " AND DATE(vt.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND DATE(vt.created_at) <= ?";
    $params[] = $endDate;
}

// Property filter
if ($property) {
    $sql .= " AND v.property = ?";
    $params[] = $property;
}

// Violation type filter
if ($violationType) {
    $sql .= " AND vt.violation_types LIKE ?";
    $params[] = '%' . $violationType . '%';
}

// Search query (vehicle info or notes)
if ($searchQuery) {
    $sql .= " AND (
        v.plate_number LIKE ? OR
        v.tag_number LIKE ? OR
        v.make LIKE ? OR
        v.model LIKE ? OR
        vt.custom_note LIKE ?
    )";
    $searchPattern = '%' . $searchQuery . '%';
    $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
}

$sql .= " ORDER BY vt.created_at DESC LIMIT 500";

try {
    $violations = Database::query($sql, $params);
    
    // Parse violation types JSON for each record
    foreach ($violations as &$violation) {
        $violation['violation_types_array'] = json_decode($violation['violation_types'], true) ?? [];
    }
    
    jsonResponse([
        'violations' => $violations,
        'total' => count($violations),
        'limit_reached' => count($violations) >= 500
    ]);
} catch (Exception $e) {
    error_log("Violation search error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to search violations'], 500);
}
