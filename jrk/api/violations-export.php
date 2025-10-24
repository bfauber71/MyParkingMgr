<?php
/**
 * Violation Export API Endpoint
 * POST /api/violations-export
 * Export violation tickets as CSV
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

// Debug logging
error_log("Violation Export - Accessible properties: " . json_encode($propertyNames));

if (empty($propertyNames)) {
    error_log("Violation Export - No accessible properties found");
    jsonResponse(['error' => 'No accessible properties'], 403);
}

// Build SQL query (same as search endpoint)
$sql = "SELECT 
    vt.id,
    vt.created_at as ticket_date,
    v.property,
    v.year,
    v.make,
    v.model,
    v.color,
    v.plate_number,
    v.tag_number,
    vt.violation_types,
    vt.custom_note,
    u.username as issued_by
FROM violation_tickets vt
LEFT JOIN vehicles v ON vt.vehicle_id = v.id
LEFT JOIN users u ON vt.created_by = u.id
WHERE (v.id IS NULL OR v.property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . "))";

$params = $propertyNames;

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
    $sql .= " AND v.property = ?";
    $params[] = $property;
}

if ($violationType) {
    $sql .= " AND vt.violation_types LIKE ?";
    $params[] = '%' . $violationType . '%';
}

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

$sql .= " ORDER BY vt.created_at DESC LIMIT 10000";

// Debug logging
error_log("Violation Export - SQL: " . $sql);
error_log("Violation Export - Params: " . json_encode($params));

try {
    $violations = Database::query($sql, $params);
    
    error_log("Violation Export - Found " . count($violations) . " violations to export");
    
    // Generate CSV
    $filename = 'violations_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Ticket ID',
        'Date/Time',
        'Property',
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
        $violationTypes = json_decode($violation['violation_types'], true) ?? [];
        $violationTypesStr = implode('; ', $violationTypes);
        
        fputcsv($output, [
            $violation['id'],
            $violation['ticket_date'],
            $violation['property'],
            $violation['year'],
            $violation['make'],
            $violation['model'],
            $violation['color'],
            $violation['plate_number'],
            $violation['tag_number'],
            $violationTypesStr,
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
