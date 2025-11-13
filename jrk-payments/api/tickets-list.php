<?php
/**
 * Tickets List API Endpoint
 * GET /api/tickets-list
 * List all violation tickets with status filtering
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();
requirePermission(MODULE_VIOLATIONS, ACTION_VIEW);

$status = $_GET['status'] ?? null;
$property = $_GET['property'] ?? null;

// Get accessible properties for filtering
$accessibleProperties = getAccessibleProperties();
$propertyNames = array_column($accessibleProperties, 'name');

if (empty($propertyNames)) {
    echo json_encode(['tickets' => [], 'total' => 0]);
    exit;
}

// Check if status and ticket_type columns exist
$hasStatus = false;
$hasTicketType = false;

try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
    $hasStatus = $checkStmt->rowCount() > 0;
    
    $checkStmt2 = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
    $hasTicketType = $checkStmt2->rowCount() > 0;
} catch (PDOException $e) {
    $hasStatus = false;
    $hasTicketType = false;
}

// Build SQL query
$statusField = $hasStatus ? "vt.status," : "'active' as status,";
$dispositionField = $hasStatus ? "vt.fine_disposition," : "NULL as fine_disposition,";
$closedAtField = $hasStatus ? "vt.closed_at," : "NULL as closed_at,";
$ticketTypeField = $hasTicketType ? "vt.ticket_type," : "'VIOLATION' as ticket_type,";

$sql = "SELECT 
    vt.id,
    vt.vehicle_id,
    vt.custom_note,
    vt.created_at,
    vt.issued_by_user_id,
    vt.issued_by_username,
    $statusField
    $dispositionField
    $closedAtField
    $ticketTypeField
    vt.vehicle_year as year,
    vt.vehicle_make as make,
    vt.vehicle_model as model,
    vt.vehicle_color as color,
    v.plate_number,
    v.tag_number,
    COALESCE(v.property, vt.property_name) as property,
    GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR ', ') as violation_list,
    SUM(COALESCE(violations.fine_amount, 0)) as total_fine
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN violation_ticket_items vti ON vt.id = vti.ticket_id
LEFT JOIN violations ON vti.violation_id = violations.id
WHERE (
    (v.id IS NOT NULL AND v.property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . "))
    OR (v.id IS NULL AND vt.property_name IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . "))
)";

$params = array_merge($propertyNames, $propertyNames);

// Status filter
if ($status && $hasStatus) {
    $sql .= " AND vt.status = ?";
    $params[] = $status;
}

// Property filter - handle both property text name and property_id
if ($property) {
    // Try to find property_id for this property name
    $propertyId = null;
    foreach ($accessibleProperties as $prop) {
        if ($prop['name'] === $property) {
            $propertyId = $prop['id'];
            break;
        }
    }
    
    if ($propertyId !== null) {
        // Filter using property_id (v2.0) OR property name (v1.x backward compatibility)
        $sql .= " AND ((v.property_id = ? OR v.property = ?) OR vt.property_name = ?)";
        $params[] = $propertyId;
        $params[] = $property;
        $params[] = $property;
    } else {
        // Fallback to property name only
        $sql .= " AND (v.property = ? OR vt.property_name = ?)";
        $params[] = $property;
        $params[] = $property;
    }
}

$sql .= " GROUP BY vt.id ORDER BY vt.created_at DESC LIMIT 500";

try {
    $stmt = Database::getInstance()->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'total' => count($tickets)
    ]);
} catch (PDOException $e) {
    error_log("Tickets list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
