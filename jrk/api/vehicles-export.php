<?php
/**
 * Export Vehicles API Endpoint
 * GET /api/vehicles/export
 */

requireAuth();

// Get accessible properties
$accessibleProperties = getAccessibleProperties();
$propertyNames = array_column($accessibleProperties, 'name');

if (empty($propertyNames)) {
    jsonResponse(['vehicles' => []]);
}

// Get all vehicles
$sql = "SELECT * FROM vehicles WHERE property IN (" . implode(',', array_fill(0, count($propertyNames), '?')) . ") ORDER BY property, created_at DESC";
$vehicles = Database::query($sql, $propertyNames);

// Prepare CSV data
$csvData = [];
foreach ($vehicles as $vehicle) {
    $csvData[] = [
        $vehicle['property'],
        $vehicle['tag_number'] ?? '',
        $vehicle['plate_number'] ?? '',
        $vehicle['state'] ?? '',
        $vehicle['make'] ?? '',
        $vehicle['model'] ?? '',
        $vehicle['color'] ?? '',
        $vehicle['year'] ?? '',
        $vehicle['apt_number'] ?? '',
        $vehicle['owner_name'] ?? '',
        $vehicle['owner_phone'] ?? '',
        $vehicle['owner_email'] ?? '',
        $vehicle['reserved_space'] ?? ''
    ];
}

auditLog('export', 'vehicle', null, ['count' => count($vehicles)]);

$headers = ['property', 'tagNumber', 'plateNumber', 'state', 'make', 'model', 'color', 'year', 
            'aptNumber', 'ownerName', 'ownerPhone', 'ownerEmail', 'reservedSpace'];

exportToCsv($csvData, 'vehicles_' . date('Y-m-d') . '.csv', $headers);
