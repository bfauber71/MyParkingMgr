<?php
/**
 * Violation Export API Endpoint
 * POST /api/violations-export
 * Export violation tickets as CSV
 */

require_once __DIR__ . '/../includes/database.php';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

Session::start();

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
error_log("Violation Export - Accessible properties: " . json_encode($propertyNames));

if (empty($propertyNames)) {
    error_log("Violation Export - No accessible properties found");
    jsonResponse(['error' => 'No accessible properties'], 403);
}

// Check if ticket_type column exists
$hasTicketType = false;
try {
    $checkStmt = Database::getInstance()->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
    $hasTicketType = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasTicketType = false;
}

// Build SQL query with violation items
$ticketTypeField = $hasTicketType ? "vt.ticket_type," : "'VIOLATION' as ticket_type,";
$sql = "SELECT 
    vt.id,
    vt.created_at as ticket_date,
    COALESCE(v.property, vt.property_name) as property,
    $ticketTypeField
    vt.vehicle_year as year,
    vt.vehicle_make as make,
    vt.vehicle_model as model,
    vt.vehicle_color as color,
    v.plate_number,
    v.tag_number,
    GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR '; ') as violation_list,
    vt.custom_note,
    vt.issued_by_username as issued_by
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN violation_ticket_items vti ON vt.id = vti.ticket_id
WHERE (v.id IS NULL OR v.property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")) 
   OR vt.property_name IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ")";

$params = array_merge($propertyNames, $propertyNames);

// Apply same filters as search
if ($startDate) {
    $sql .= " AND DATE(vt.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND DATE(vt.created_at) <= ?";
    $params[] = $endDate;
}

if ($property) {
    $sql .= " AND (v.property = ? OR vt.property_name = ?)";
    $params[] = $property;
    $params[] = $property;
}

// GROUP BY for aggregation
$sql .= " GROUP BY vt.id";

// Violation type filter (HAVING clause after GROUP BY)
if ($violationType) {
    $sql .= " HAVING GROUP_CONCAT(vti.description ORDER BY vti.display_order SEPARATOR '; ') LIKE ?";
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

$sql .= " ORDER BY vt.created_at DESC LIMIT 10000";

// Debug logging
error_log("Violation Export - SQL: " . $sql);
error_log("Violation Export - Params: " . json_encode($params));

try {
    $violations = Database::query($sql, $params);
    
    error_log("Violation Export - Found " . count($violations) . " violations to export");
    
    // Generate CSV
    $filename = 'violations_' . date('Y-m-d_His') . '.csv';
    
    // iOS Safari-compatible headers - use octet-stream with nosniff
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Ticket ID',
        'Date/Time',
        'Property',
        'Ticket Type',
        'Year',
        'Make',
        'Model',
        'Color',
        'Plate Number',
        'Tag Number',
        'Violations',
        'Notes',
        'Issued By'
    ]);
    
    // CSV Data
    foreach ($violations as $violation) {
        fputcsv($output, [
            $violation['id'],
            $violation['ticket_date'],
            $violation['property'],
            $violation['ticket_type'] ?? 'VIOLATION',
            $violation['year'],
            $violation['make'],
            $violation['model'],
            $violation['color'],
            $violation['plate_number'],
            $violation['tag_number'],
            $violation['violation_list'] ?? '',
            $violation['custom_note'],
            $violation['issued_by']
        ]);
    }
    
    fclose($output);
    
    // Log export
    logAudit('violation_export', null, [
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'property' => $property,
            'violation_type' => $violationType,
            'query' => $searchQuery
        ],
        'count' => count($violations)
    ]);
    
    exit;
} catch (Exception $e) {
    error_log("Violation export error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to export violations'], 500);
}
