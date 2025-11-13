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
requirePermission(MODULE_VIOLATIONS, ACTION_VIEW);

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

// Check if status columns exist (for backward compatibility)
$hasStatus = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
    $hasStatus = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasStatus = false;
}

// Check if custom_note column exists
$hasCustomNote = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'custom_note'");
    $hasCustomNote = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasCustomNote = false;
}

// Check if issued_by_user_id column exists
$hasIssuedByUserId = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'issued_by_user_id'");
    $hasIssuedByUserId = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasIssuedByUserId = false;
}

// Check if issued_by_username column exists
$hasIssuedByUsername = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'issued_by_username'");
    $hasIssuedByUsername = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasIssuedByUsername = false;
}

// Build SQL query with violation items and fines
$ticketTypeField = $hasTicketType ? "vt.ticket_type," : "'VIOLATION' as ticket_type,";
$statusField = $hasStatus ? "vt.status," : "'active' as status,";
$dispositionField = $hasStatus ? "vt.fine_disposition," : "NULL as fine_disposition,";
$closedAtField = $hasStatus ? "vt.closed_at," : "NULL as closed_at,";
$closedByField = $hasStatus ? "vt.closed_by_user_id," : "NULL as closed_by_user_id,";
$customNoteField = $hasCustomNote ? "vt.custom_note," : "NULL as custom_note,";
$issuedByUserIdField = $hasIssuedByUserId ? "vt.issued_by_user_id," : "NULL as issued_by_user_id,";
$issuedByUsernameField = $hasIssuedByUsername ? "vt.issued_by_username as issuing_user," : "NULL as issuing_user,";

$sql = "SELECT 
    vt.id,
    vt.vehicle_id,
    $customNoteField
    vt.created_at,
    $issuedByUserIdField
    $issuedByUsernameField
    vt.vehicle_year as year,
    vt.vehicle_make as make,
    vt.vehicle_model as model,
    vt.vehicle_color as color,
    v.plate_number,
    v.tag_number,
    COALESCE(v.property, vt.property) as property,
    $ticketTypeField
    $statusField
    $dispositionField
    $closedAtField
    $closedByField
    GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR ', ') as violation_list,
    SUM(COALESCE(violations.fine_amount, 0)) as total_fine
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN violation_ticket_items vti ON vt.id = vti.ticket_id
LEFT JOIN violations ON vti.violation_id = violations.id
WHERE 1=1";

$params = [];

// Add property filter
if (!empty($propertyNames)) {
    $placeholders = implode(',', array_fill(0, count($propertyNames), '?'));
    $sql .= " AND ((v.id IS NULL OR v.property IN ($placeholders)) OR vt.property IN ($placeholders))";
    $params = array_merge($propertyNames, $propertyNames);
}

// Date range filter
if ($startDate) {
    $sql .= " AND DATE(vt.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND DATE(vt.created_at) <= ?";
    $params[] = $endDate;
}

// Property filter - handle both property text name and property_id
if ($property) {
    // Check if vehicles table has property_id column (v2.0) or just property (v1.x)
    $hasPropertyIdColumn = false;
    try {
        $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM vehicles LIKE 'property_id'");
        $hasPropertyIdColumn = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasPropertyIdColumn = false;
    }
    
    // Try to find property_id for this property name
    $propertyId = null;
    foreach ($accessibleProperties as $prop) {
        if ($prop['name'] === $property) {
            $propertyId = $prop['id'];
            break;
        }
    }
    
    if ($hasPropertyIdColumn && $propertyId !== null) {
        // Filter using property_id (v2.0) OR property name (v1.x backward compatibility)
        $sql .= " AND ((v.property_id = ? OR v.property = ?) OR vt.property = ?)";
        $params[] = $propertyId;
        $params[] = $property;
        $params[] = $property;
    } else {
        // Fallback to property name only (v1.x)
        $sql .= " AND (v.property = ? OR vt.property = ?)";
        $params[] = $property;
        $params[] = $property;
    }
}

// Search query (vehicle info or notes) - Use WHERE, not HAVING
if ($searchQuery) {
    $searchPattern = '%' . $searchQuery . '%';
    if ($hasCustomNote) {
        $sql .= " AND (
            v.plate_number LIKE ? OR
            v.tag_number LIKE ? OR
            vt.vehicle_make LIKE ? OR
            vt.vehicle_model LIKE ? OR
            vt.custom_note LIKE ?
        )";
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    } else {
        $sql .= " AND (
            v.plate_number LIKE ? OR
            v.tag_number LIKE ? OR
            vt.vehicle_make LIKE ? OR
            vt.vehicle_model LIKE ?
        )";
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    }
}

// GROUP BY for aggregation - only group by ticket ID since all other fields are from the same ticket
$sql .= " GROUP BY vt.id";

// Violation type filter (HAVING clause after GROUP BY)
if ($violationType) {
    $sql .= " HAVING GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR ', ') LIKE ?";
    $params[] = '%' . $violationType . '%';
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
    error_log("Violation search params: " . json_encode($params));
    
    // Return detailed error for debugging
    jsonResponse([
        'error' => 'Database query failed',
        'details' => $e->getMessage(),
        'sql_preview' => substr($sql, 0, 200) . '...'
    ], 500);
}
