<?php
/**
 * Create Vehicle API Endpoint
 * POST /api/vehicles
 */

requireAuth();

// Operators are read-only
if (isOperator()) {
    jsonResponse(['error' => 'Operators have read-only access'], 403);
}

$data = getJsonInput();

// Validate required fields
$missing = validateRequired($data, ['property']);
if (!empty($missing)) {
    jsonResponse(['error' => 'Property is required'], 400);
}

// Check if property exists
$property = Database::queryOne("SELECT id, name FROM properties WHERE name = ?", [$data['property']]);
if (!$property) {
    jsonResponse(['error' => 'Property not found'], 404);
}

// Check access
if (!canAccessProperty($property['id'])) {
    jsonResponse(['error' => 'You do not have access to this property'], 403);
}

// Create vehicle
$id = Database::uuid();
$sql = "INSERT INTO vehicles (id, property, tag_number, plate_number, state, make, model, color, year, 
        apt_number, owner_name, owner_phone, owner_email, reserved_space, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

Database::execute($sql, [
    $id,
    $data['property'],
    $data['tagNumber'] ?? null,
    $data['plateNumber'] ?? null,
    $data['state'] ?? null,
    $data['make'] ?? null,
    $data['model'] ?? null,
    $data['color'] ?? null,
    $data['year'] ?? null,
    $data['aptNumber'] ?? null,
    $data['ownerName'] ?? null,
    $data['ownerPhone'] ?? null,
    $data['ownerEmail'] ?? null,
    $data['reservedSpace'] ?? null
]);

auditLog('create', 'vehicle', $id, ['property' => $data['property']]);

$vehicle = Database::queryOne("SELECT * FROM vehicles WHERE id = ?", [$id]);

jsonResponse(['vehicle' => $vehicle], 201);
