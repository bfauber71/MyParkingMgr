<?php
/**
 * Violation Search API Endpoint
 * POST /api/violations-search
 * Search violation tickets with filters
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

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

// Debug logging
error_log("Violation Search - Accessible properties: " . json_encode($propertyNames));
error_log("Violation Search - Filters: start_date=$startDate, end_date=$endDate, property=$property, violation_type=$violationType, query=$searchQuery");

if (empty($propertyNames)) {
    error_log("Violation Search - No accessible properties found, returning empty results");
    jsonResponse(['violations' => [], 'total' => 0, 'debug' => 'No accessible properties']);
}

// Check if ticket_type column exists
$hasTicketType = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
    $hasTicketType = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasTicketType = false;
}

// Build SQL query with violation items and fines
$ticketTypeField = $hasTicketType ? "vt.ticket_type," : "'VIOLATION' as ticket_type,";
$sql = "SELECT 
    vt.id,
    vt.vehicle_id,
    vt.custom_note,
    vt.created_at,
    vt.issued_by_user_id,
    vt.issued_by_username as issuing_user,
    vt.vehicle_year as year,
    vt.vehicle_make as make,
    vt.vehicle_model as model,
    vt.vehicle_color as color,
    v.plate_number,
    v.tag_number,
    COALESCE(v.property, vt.property_name) as property,
    $ticketTypeField
    GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR ', ') as violation_list,
    SUM(COALESCE(violations.fine_amount, 0)) as total_fine
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN violation_ticket_items vti ON vt.id = vti.ticket_id
LEFT JOIN violations ON vti.violation_id = violations.id
WHERE (v.id IS NULL OR v.property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")) 
   OR vt.property_name IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")";

$params = array_merge($propertyNames, $propertyNames);

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
    $sql .= " AND (v.property = ? OR vt.property_name = ?)";
    $params[] = $property;
    $params[] = $property;
}

// GROUP BY for aggregation
$sql .= " GROUP BY vt.id";

// Violation type filter (HAVING clause after GROUP BY)
if ($violationType) {
    $sql .= " HAVING GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR ', ') LIKE ?";
    $params[] = '%' . $violationType . '%';
}

// Search query (vehicle info or notes)
if ($searchQuery) {
    $searchPattern = '%' . $searchQuery . '%';
    if ($violationType) {
        // Already have HAVING clause, add AND
        $sql .= " AND (
            v.plate_number LIKE ? OR
            v.tag_number LIKE ? OR
            vt.vehicle_make LIKE ? OR
            vt.vehicle_model LIKE ? OR
            vt.custom_note LIKE ?
        )";
    } else {
        // Need to start HAVING clause
        $sql .= " HAVING (
            v.plate_number LIKE ? OR
            v.tag_number LIKE ? OR
            vt.vehicle_make LIKE ? OR
            vt.vehicle_model LIKE ? OR
            vt.custom_note LIKE ?
        )";
    }
    $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
}

$sql .= " ORDER BY vt.created_at DESC LIMIT 500";

// Debug logging
error_log("Violation Search - SQL: " . $sql);
error_log("Violation Search - Params: " . json_encode($params));

try {
    $violations = Database::query($sql, $params);
    
    error_log("Violation Search - Found " . count($violations) . " violations");
    
    // Parse violation list into array for each record
    foreach ($violations as &$violation) {
        $violation['violation_types_array'] = $violation['violation_list'] 
            ? explode(', ', $violation['violation_list']) 
            : [];
    }
    
    jsonResponse([
        'violations' => $violations,
        'total' => count($violations),
        'limit_reached' => count($violations) >= 500
    ]);
} catch (Exception $e) {
    error_log("Violation search error: " . $e->getMessage());
    error_log("Violation search SQL: " . $sql);
    jsonResponse(['error' => 'Failed to search violations: ' . $e->getMessage()], 500);
}
